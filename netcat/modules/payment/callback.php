<?php

require_once "function.inc.php";

/** @var nc_input $input */
$input = nc_core('input');

$payment_system_class = $input->fetch_get('paySystem');

$netshop = nc_netshop::get_instance();
if ($netshop) {
    nc_core('event')->bind($netshop, array(
        nc_payment_system::EVENT_ON_PAY_SUCCESS => 'on_payment_success_event_handler',
        nc_payment_system::EVENT_ON_PAY_FAILURE => 'on_payment_failure_event_handler',
    ));
}

$payment = nc_payment_factory::create($payment_system_class);
$payment->process_callback_response($input->fetch_get_post());

