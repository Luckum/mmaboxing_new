<?

class nc_payment_system_paymaster extends nc_payment_system {

    const ERROR_MERCHANTID_IS_NOT_VALID = NETCAT_MODULE_PAYMENT_PAYMASTER_ERROR_MERCHANTID_IS_NOT_VALID;
    const ERROR_LMI_PAYMENT_DESC_IS_LONG = NETCAT_MODULE_PAYMENT_PAYMASTER_ERROR_LMI_PAYMENT_DESC_IS_LONG;
    const ERROR_PRIVATE_SECURITY_IS_NOT_VALID = NETCAT_MODULE_PAYMENT_PAYMASTER_ERROR_PRIVATE_SECURITY_IS_NOT_VALID;

    const TARGET_URL = "https://paymaster.ru/Payment/Init";

    protected $automatic = TRUE;

    // принимаемые валюты
    protected $accepted_currencies = array('RUB', 'RUR', 'USD', 'EUR');
    protected $currency_map = array('RUR' => 'RUB');

    // параметры сайта в платежной системе
    protected $settings = array(
        'LMI_MERCHANT_ID' => null,
        // 'LMI_CURRENCY' => null,
        'SALT' => null, // секретное слово
    );

    // передаваемые параметры
    protected $request_parameters = array(
//        "LMI_PAYMENT_DESC" => "Платеж"     // $this->get_order_description()
    );

    // получаемые параметры
    protected $callback_response = array(
        'LMI_PAYMENT_NO' => null,
        'LMI_SYS_PAYMENT_ID' => null,
        'LMI_SYS_PAYMENT_DATE' => null,
        'LMI_PAYMENT_AMOUNT' => null,
        'LMI_PAID_AMOUNT' => null,
        'LMI_PAID_CURRENCY' => null,
        'LMI_PAYMENT_SYSTEM' => null,
        'LMI_SIM_MODE' => null,
    );


    /**
     *
     */
    public function execute_payment_request(nc_payment_invoice $invoice) {
        ob_end_clean();
        $form = "
            <html>
              <body>
                  <form action='" . nc_payment_system_paymaster::TARGET_URL . "' method='post'>" .
                        $this->make_inputs(array(
                            'LMI_MERCHANT_ID' => $this->get_setting('LMI_MERCHANT_ID'),
                            'LMI_PAYMENT_AMOUNT' => $invoice->get_amount(),
                            'LMI_CURRENCY' => $this->get_currency_code($invoice->get_currency()),
                            'LMI_SIM_MODE' => 0,
                            'LMI_PAYMENT_DESC' => $invoice->get_description(),
                            'LMI_PAYMENT_NO' => $invoice->get_id(),
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
     *
     */
    public function validate_payment_request_parameters() {
        if (!$this->get_setting('LMI_MERCHANT_ID')) {
            $this->add_error(self::ERROR_MERCHANTID_IS_NOT_VALID);
        }
    }

    /**
     * @param nc_payment_invoice $invoice
     */
    protected function validate_invoice(nc_payment_invoice $invoice) {
        parent::validate_invoice($invoice);
        if (strlen($invoice->get_description()) > 255) {
            $this->add_error(self::ERROR_LMI_PAYMENT_DESC_IS_LONG);
        }
    }

    /**
     * @param nc_payment_invoice $invoice
     */
    public function on_response(nc_payment_invoice $invoice = null) {
        // предполагается, что в настройках Paymaster в URL callback вызова
        // передается GET параметр action (success — платеж прошёл, error — нет)
        if ($this->get_response_value('action') == 'success') {
            $this->on_payment_success($invoice);
        }
        else {
            $this->on_payment_failure($invoice);
        }
    }

    /**
     * @param nc_payment_invoice $invoice
     */
    public function validate_payment_callback_response(nc_payment_invoice $invoice = null) {
        $key = array(
            $this->get_setting('LMI_MERCHANT_ID'),
            $this->get_response_value('LMI_PAYMENT_NO'),
            $this->get_response_value('LMI_SYS_PAYMENT_ID'),
            $this->get_response_value('LMI_SYS_PAYMENT_DATE'),
            $this->get_response_value('LMI_PAYMENT_AMOUNT'),
            $this->get_response_value('LMI_CURRENCY'),
            $this->get_response_value('LMI_PAID_AMOUNT'),
            $this->get_response_value('LMI_PAID_CURRENCY'),
            $this->get_response_value('LMI_PAYMENT_SYSTEM'),
            $this->get_response_value('LMI_SIM_MODE'),
            $this->get_setting('SALT')
        );

        $key = implode(";", $key);
        $key_hash = base64_encode(md5($key, TRUE));

        if ($this->get_response_value('LMI_HASH') != $key_hash) {
            $this->add_error(nc_payment_system_paymaster::ERROR_PRIVATE_SECURITY_IS_NOT_VALID);
        }
    }

    public function load_invoice_on_callback() {
        return $this->load_invoice($this->get_response_value('LMI_PAYMENT_NO'));
    }
}
