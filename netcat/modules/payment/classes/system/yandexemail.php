<?

class nc_payment_system_yandexemail extends nc_payment_system {

    const ERROR_RECEIVER = NETCAT_MODULE_PAYMENT_YANDEX_EMAIL_ERROR_RECEIVER;

    const TARGET_URL = "https://money.yandex.ru/quickpay/confirm.xml";

    protected $automatic = FALSE;

    // принимаемые валюты
    protected $accepted_currencies = array('RUB', 'RUR');

    protected $settings = array(
        'Receiver' => null,
    );

    /**
     *
     */
    public function execute_payment_request(nc_payment_invoice $invoice) {
        ob_end_clean();
        $form = "
		<html>
			<body>
        		<form action='" . nc_payment_system_yandexemail::TARGET_URL . "' method='post'>" .
            $this->make_inputs(array(
                'receiver' => $this->get_setting('Receiver'),
                'sum' => $invoice->get_amount(),
                'short-dest' => $invoice->get_description(),
                'targets' => $invoice->get_description(),
                'label' => $invoice->get_id(),
                'quickpay-form' => 'shop',
            )) . "
				</form>
				<script>
					document.forms[0].submit();
				</script>
			</body>
		</html>
		";
        echo $form;
        exit;
    }

    /**
     * @param nc_payment_invoice $invoice
     */
    public function on_response(nc_payment_invoice $invoice = null) {
    }

    /**
     *
     */
    public function validate_payment_request_parameters() {
        if (!$this->get_setting('Receiver')) {
            $this->add_error(nc_payment_system_yandexemail::ERROR_RECEIVER);
        }
    }

    /**
     * @param nc_payment_invoice $invoice
     */
    public function validate_payment_callback_response(nc_payment_invoice $invoice = null) {
    }

}
