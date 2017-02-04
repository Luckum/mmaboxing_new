<?php


class nc_payment {

    static public function init() {
        nc_core()->register_class_autoload_path("nc_payment_", dirname(__FILE__) . "/classes");
        self::register_system_events();
        new nc_payment_logger();
    }

    static protected function register_system_events() {
        /** @var nc_event $event_manager */
        $event_manager = nc_core('event');

        $events = array(
            "EVENT_ON_INIT",
            "EVENT_BEFORE_PAY_REQUEST",
            "EVENT_AFTER_PAY_REQUEST",
            "EVENT_ON_PAY_REQUEST_ERROR",
            "EVENT_BEFORE_PAY_CALLBACK",
            "EVENT_AFTER_PAY_CALLBACK",
            "EVENT_ON_PAY_CALLBACK_ERROR",
            "EVENT_ON_PAY_SUCCESS",
            "EVENT_ON_PAY_FAILURE",
        );

        foreach ($events as $event_constant) {
            $event_name = constant("nc_payment_system::" . $event_constant);
            $event_description = constant("NETCAT_MODULE_PAYMENT_" . $event_constant);
            $event_manager->register_event($event_name, $event_description);
        }

    }

    /**
     * Changes invoice status
     * and triggers events
     *
     * @param $bill_id
     * @param $paid
     */
    static public function change_invoice_status_by_bill_id($bill_id, $paid) {
        $bill_id = (int)$bill_id;
        $sql = "SELECT * FROM %t% WHERE `Order_Source` = 'bills' AND `Order_ID` = {$bill_id}";
        $invoices = nc_record_collection::load('nc_payment_invoice', $sql);

        foreach ($invoices as $invoice) {
            $invoice->set('status', $paid ? nc_payment_invoice::STATUS_SUCCESS : nc_payment_invoice::STATUS_NEW);
            $invoice->save();
            nc_core('event')->execute($paid ? nc_payment_system::EVENT_ON_PAY_SUCCESS : nc_payment_system::EVENT_ON_PAY_FAILURE, nc_payment_factory::create('nc_payment_system_bank'), $invoice);
        }
    }

    /**
     * Returns juridical bill
     * payment system id
     *
     * @return int
     */
    static public function get_juridical_bill_payment_system_id() {
        return self::get_payment_system_id_by_name('nc_payment_system_bank');
    }

    /**
     * Returns physical bill
     * payment system id
     *
     * @return int
     */
    static public function get_physical_bill_payment_system_id() {
        return self::get_payment_system_id_by_name('nc_payment_system_sberbank');
    }

    /**
     * Returns payment system id
     * by name
     *
     * @return int
     */
    static protected function get_payment_system_id_by_name($name) {
        $db = nc_core('db');
        $name = $db->escape($name);
        $sql = "SELECT `PaymentSystem_ID` FROM `Classificator_PaymentSystem` WHERE `Value` = '{$name}'";

        return (int)$db->get_var($sql);
    }

}