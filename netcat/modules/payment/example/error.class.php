<?

// пример обработки ошибок

class Example_PayErrorHandler {

    public function __construct() {
        $nc_core = nc_Core::get_object();
        $nc_core->event->bind($this, array(nc_payment_system::EVENT_ON_PAY_REQUEST_ERROR => 'show_errors'));
        $nc_core->event->bind($this, array(nc_payment_system::EVENT_ON_PAY_CALLBACK_ERROR => 'show_errors'));
    }

    public function show_errors(nc_payment_system $payment_system) {
        $errors = $payment_system->get_errors();
        // далее действия с массвом ошибок
    }

}

