<?php


/**
 *
 */
class nc_netshop_payment_admin_controller extends nc_netshop_admin_table_controller {

    /** @var  nc_netshop_payment_admin_ui */
    protected $ui_config;

    protected $data_type = 'payment';

    /**
     * @return nc_ui_view
     */
    protected function action_index() {
        $table = new nc_netshop_payment_table();
        $methods = $table->for_site($this->site_id)->as_array()->get_result();

        if (count($methods)) {
            $view = $this->view('paymentmethod_list');
            $view->fields = $table->get_fields();
            $view->methods = $methods;
        } else {
            $view = $this->view('empty_list');
            $view->message = NETCAT_MODULE_NETSHOP_SETTINGS_NO_PAYMENT_METHODS_ON_SITE;
        }
        $this->ui_config->add_create_button("payment.add($this->site_id)");

        return $view;
    }

    /**
     * @return nc_ui_view
     */
    protected function action_add() {
        return $this->basic_table_edit_action(0, 'form_with_condition');
    }

    /**
     * @param $id
     * @return nc_ui_view
     */
    protected function action_edit($id) {
        return $this->basic_table_edit_action($id, 'form_with_condition');
    }

}