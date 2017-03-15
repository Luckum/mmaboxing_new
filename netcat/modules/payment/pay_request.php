<?php

include_once("function.inc.php");

/** @var nc_input $input */
$input = nc_core('input');

$invoice_id = $input->fetch_post('invoice_id');
$payment_system_name = $input->fetch_post('payment_system');

try {
    $invoice = new nc_payment_invoice($invoice_id);

    if ($invoice->get('status') === nc_payment_invoice::STATUS_SUCCESS) {
        die(NETCAT_MODULE_PAYMENT_ERROR_ALREADY_PAID);
    }

    if ($invoice->get('customer_id') && $AUTH_USER_ID != $invoice->get('customer_id')) {
        die("Wrong customer ID");
    }

    $payment_system = nc_payment_factory::create($payment_system_name);
    $params = array();

    foreach ((array)$input->fetch_post() as $key => $value) {
        if (strpos($key, 'param_') === 0) {
            $key = substr($key, strlen('param_'));
            $params[$key] = $value;
        }
    }

    $payment_system->set_request_parameters($params);
    $payment_system->process_payment_request($invoice);
}
catch (Exception $e) {
    echo $e->getMessage();

    if (isset($perm) && is_object($perm) && $perm->isCatalogueAdmin($CatalogueID) && isset($payment_system)) {
        echo "<ul><li>" . join("</li>\n<li>", $payment_system->get_errors()) . "</li></ul>";
    }
}