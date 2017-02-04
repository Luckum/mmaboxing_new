<?php

class nc_payment_logger {

    public function __construct() {
        nc_core('event')->bind($this,
            array(
                nc_payment_system::EVENT_ON_PAY_SUCCESS => 'on_payment_success',
                nc_payment_system::EVENT_ON_PAY_FAILURE => 'on_payment_failure'
            )
        );
    }

    public function on_payment_success(nc_payment_system $payment_system, nc_payment_invoice $invoice = null) {
        $this->log(nc_payment_system::EVENT_ON_PAY_SUCCESS, $payment_system, $invoice);
    }

    public function on_payment_failure(nc_payment_system $payment_system, nc_payment_invoice $invoice = null) {
        $this->log(nc_payment_system::EVENT_ON_PAY_FAILURE, $payment_system, $invoice);
    }

    protected function log($event_type, nc_payment_system $payment_system, nc_payment_invoice $invoice = null) {
        /** @var nc_db $db */
        $db = nc_core('db');

        $log_string = serialize($payment_system->get_response());
        $sql = "INSERT INTO " . NC_PAYMENT_LOG_TABLE . "
                   SET `Payment_Invoice_ID` = '" . $db->escape($invoice->get_id()) . "',
                       `EventType` = '" . $db->escape($event_type) ."',
                       `Log` = '" . $db->escape($log_string) . "',
                       `Date` = NOW()";

        $db->query($sql);
    }

}
