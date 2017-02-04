<?php


class nc_netshop_mailer_rule_admin_controller extends nc_netshop_admin_table_controller {

    /** @var  nc_netshop_mailer_admin_ui */
    protected $ui_config;

    /** @var string  */
    protected $data_type = 'mailer_rule';

    /** @var array  */
    protected $form_condition_groups = array('GROUP_CART', 'GROUP_USER', 'GROUP_ORDER', 'GROUP_ORDERS', 'GROUP_EXTENSION');

    /**
     *
     */
    protected function before_action() {
        $this->ui_config = new nc_netshop_mailer_admin_ui($this->site_id, 'rule');
    }

    /**
     *
     */
    protected function action_index() {
        $table = new nc_netshop_mailer_rule_table();
        $rules = $table->for_site($this->site_id)
                       ->order_by('Name')
                       ->as_array()->get_result();

        if (count($rules)) {
            $view = $this->view('rule_list')
                         ->with('rules', $rules);
        }
        else {
            $view = $this->view('empty_list')
                         ->with('message', NETCAT_MODULE_NETSHOP_MAILER_NO_RULES_ON_SITE);
        }

        $this->ui_config->add_create_button("mailer.rule.add($this->site_id)");

        return $view;
    }

    protected function action_add() {
        $view = $this->basic_table_edit_action(0, 'form_with_condition')
                     ->with('condition_groups', $this->form_condition_groups);

        $this->ui_config->set_location_hash("mailer.rule.add($this->site_id)");

        return $view;
    }

    protected function action_edit($id) {
        $view = $this->basic_table_edit_action($id, 'form_with_condition')
                     ->with('condition_groups', $this->form_condition_groups);

        $this->ui_config->set_location_hash("mailer.rule.edit($id)");

        return $view;
    }


}