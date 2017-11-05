<?

include_once 'error.class.php';
include_once 'log.class.php';

class Example_Extend_Factory extends nc_payment_factory {

    public static function create($system) {
        $logger = new Example_Logger();
        $payError = new Example_PayError();

        return parent::create($system);
    }

}

