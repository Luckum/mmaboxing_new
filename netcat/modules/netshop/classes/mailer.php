<?php

class nc_netshop_mailer {

    protected $sender_address;
    protected $sender_name;

    /** @var nc_netshop  */
    protected $netshop;

    /**
     *
     */
    public function __construct(nc_netshop $netshop) {
        $this->netshop = $netshop;

        // nc_mail2queue(), CMIMEMail class:
        require_once(nc_core('ADMIN_FOLDER') . "mail.inc.php");
    }

    /**
     *
     */
    protected function get_template_variables() {
        $nc_core = nc_core();
        return array(
            'site' => $nc_core->catalogue->get_by_id($this->netshop->get_catalogue_id()),
            'user' => $nc_core->user->get_by_id($GLOBALS['AUTH_USER_ID']),
            'shop' => $this->netshop->settings,
        );
    }

    /**
     * Создаёт письмо (nc_netshop_mailer_message) на основе указанного шаблона.
     * Если шаблона с указанным ID нет или шаблон отключён, возвращает NULL.
     *
     * $message = $mailer->compose_message('order', $variables)
     * if ($message) { $mailer->send($recipient, $message); }
     *
     * @param $template_type
     * @param array $template_variables
     * @return nc_netshop_mailer_message|null
     */
    public function compose_message($template_type, array $template_variables) {
        $template = nc_netshop_mailer_template::by_type($this->netshop->get_catalogue_id(), $template_type);
        if (!$template || !$template->get('enabled')) { return null; }
        $template_variables = array_merge($this->get_template_variables(), $template_variables);
        return $template->compose_message($template_variables);
    }

    /**
     *
     */
    public function get_sender_address() {
        if (!$this->sender_address) {
            $this->sender_address = $this->netshop->get_setting('MailFrom');
        }
        return $this->sender_address;
    }

    /**
     *
     */
    public function get_sender_name() {
        if (!$this->sender_name) {
            $this->sender_name = $this->netshop->get_setting('ShopName');
        }
        return $this->sender_name;
    }

    /**
     * @param $recipient_address
     * @param nc_netshop_mailer_message $message
     * @param array $attachment_form_types
     */
    public function send($recipient_address, nc_netshop_mailer_message $message, $attachment_form_types = array()) {
        $sender_address = $this->get_sender_address();
        $sender_name = $this->get_sender_name();

        $mailer = new CMIMEMail();
        $mail_body = nc_mail_attachment_attach($mailer, $message->get_body(), $attachment_form_types);
        $mailer->mailbody('', $mail_body);
        $mailer->send($recipient_address, $sender_address, $sender_address,
                      $message->get_subject(), $sender_name);
    }

    /**
     * @param $recipient_address
     * @param nc_netshop_mailer_message $message
     */
    public function queue($recipient_address, nc_netshop_mailer_message $message) {
        nc_mail2queue($recipient_address,
                      $this->get_sender_address(),
                      $message->get_subject(),
                      '',
                      $message->get_body());
    }

    /**
     * @param $catalogue_id
     * @param string $type
     * @param bool $add_empty_entry
     * @return array
     */
    public function get_template_list($catalogue_id, $type = 'master', $add_empty_entry = true) {
        /** @var nc_db $db */
        $db = nc_core('db');
        $templates = $db->get_results("SELECT `Template_ID`, `Name`
                                         FROM `Netshop_MailTemplate`
                                        WHERE `Catalogue_ID`=" . (int)$catalogue_id . "
                                          AND `Type` = '" . $db->escape($type) . "'", ARRAY_A);
        $result = $add_empty_entry
                        ? array(0 => NETCAT_MODULE_NETSHOP_MAILER_TEMPLATE_NO_PARENT_TEMPLATE)
                        : array();
        foreach ((array)$templates as $template) {
            $result[$template['Template_ID']] = $template['Name'];
        }

        return $result;
    }

    /**
     * @return array
     */
    public function get_user_email_fields() {
        static $fields = array();
        if (!$fields) {
            $results = nc_core('db')->get_results("SELECT `Field_Name`, `Description`
                                                     FROM `Field`
                                                    WHERE `System_Table_ID` = '3'
                                                      AND `TypeOfData_ID` = '1'
                                                      AND `Format` = 'email'
                                                    ORDER BY `Priority`", ARRAY_A);
            foreach ((array)$results as $row) {
                $fields[$row['Field_Name']] = "[$row[Field_Name]] $row[Description]";
            }
        }
        return $fields;
    }
    
    /**
     * 
     */
    public function checkout(nc_netshop_order $order) {
        $this->process_order_status_change($order);
    }

    /**
     * @param nc_netshop_order $order
     * @return nc_netshop_record_conditional_collection
     */
    public function get_manager_message_rules(nc_netshop_order $order) {
        $query = "SELECT *
                   FROM `%t%`
                  WHERE `Catalogue_ID` = " . (int)$order->get_catalogue_id() . "
                    AND `Checked` = 1";

        return nc_record_collection::load('nc_netshop_mailer_rule', $query);
    }

    /**
     * @param nc_netshop_order $order
     */
    public function process_order_status_change(nc_netshop_order $order) {
        $nc_core = nc_core::get_object();
        $catalogue_id = $order->get_catalogue_id();
        $netshop_catalogue_id = $this->netshop->get_catalogue_id();

        $new_status = $order->get('Status');

        $variables = array(
            'user' => $nc_core->user->get_by_id($order->get('User_ID')),
            'order' => $order,
            'cart' => $order->get_items(),
        );

        // --- Письма для клиента ---
        // Пример имени шаблона писем: "customer_order", "customer_status_5", "manager_status_5"
        // (у только что созданных заказов по умолчанию нет значения Status...)
        $customer_template_type = 'customer_' . ($new_status ? "status_{$new_status}" : "order");

        $message = $this->compose_message($customer_template_type, $variables);

        if ($message) {
            $attachment_form_types = array();

            $customer_template = nc_netshop_mailer_template::by_type($netshop_catalogue_id, $customer_template_type);
            $parent_template_id = $customer_template->get('parent_template_id');

            if ($parent_template_id) {
                $attachment_form_types[] = 'netshop_' . $netshop_catalogue_id . '_master_' . $parent_template_id;
            }

            $attachment_form_types[] = 'netshop_' . $netshop_catalogue_id . '_' . $customer_template_type;

            $this->send($order->get('Email'), $message, $attachment_form_types);
        }

        // --- Письма для менеджера (менеджеров) ---
        $manager_template_type = 'manager_' . ($new_status ? "status_{$new_status}" : "order");

        $message = $this->compose_message($manager_template_type, $variables);
        if ($message) {
            $attachment_form_types = array();

            $manager_template = nc_netshop_mailer_template::by_type($netshop_catalogue_id, $manager_template_type);
            $parent_template_id = $manager_template->get('parent_template_id');

            if ($parent_template_id) {
                $attachment_form_types[] = 'netshop_' . $netshop_catalogue_id . '_master_' . $parent_template_id;
            }

            $attachment_form_types[] = 'netshop_' . $netshop_catalogue_id . '_' . $manager_template_type;

            // Письмо на основной адрес («Email для оповещения»)
            $this->send($this->netshop->get_setting('ManagerEmail'), $message, $attachment_form_types);

            // Дополнительные адреса, в зависимости от свойств заказа
            $rule_condition_context = new nc_netshop_condition_context($catalogue_id);
            $rule_condition_context->set_order($order);

            $rules = $this->get_manager_message_rules($order)->matching($rule_condition_context);
            foreach ($rules as $rule) {
                /** @var nc_netshop_mailer_rule $rule */
                $this->send($rule->get('email'), $message, $attachment_form_types);
            }
        }

    }

}