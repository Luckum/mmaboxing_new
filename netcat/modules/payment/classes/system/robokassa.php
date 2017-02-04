<?

class nc_payment_system_robokassa extends nc_payment_system {

    const ERROR_MRCHLOGIN_IS_NOT_VALID = NETCAT_MODULE_PAYMENT_ROBOKASSA_ERROR_MRCHLOGIN_IS_NOT_VALID;
    const ERROR_INVID_IS_NOT_VALID = NETCAT_MODULE_PAYMENT_ROBOKASSA_ERROR_INVID_IS_NOT_VALID;
    const ERROR_INVDESC_ID_IS_LONG = NETCAT_MODULE_PAYMENT_ROBOKASSA_ERROR_INVDESC_ID_IS_LONG;
    const ERROR_PRIVATE_SECURITY_IS_NOT_VALID = NETCAT_MODULE_PAYMENT_ROBOKASSA_ERROR_PRIVATE_SECURITY_IS_NOT_VALID;

    const TARGET_URL = "https://merchant.roboxchange.com/Index.aspx";

    protected $automatic = TRUE;

    // принимаемые валюты
    protected $accepted_currencies = array('RUB', 'RUR');

    // параметры сайта в платежной системе
    protected $settings = array(
        'MrchLogin' => null,
        'MerchantPass1' => null,
        'MerchantPass2' => null,
    );

    // передаваемые параметры
    protected $request_parameters = array( // 'InvId' => null,
        // 'InvDesc' => null,
    );

    // получаемые параметры
    protected $callback_response = array(
        'InvId' => null,
        'OutSum' => null,
    );

    /**
     *
     */
    public function execute_payment_request(nc_payment_invoice $invoice) {
        $amount = $invoice->get_amount("%0.2F");

        $signature_values = array(
            $this->get_setting('MrchLogin'),
            $amount,
            $invoice->get_id(),
            $this->get_setting('MerchantPass1'),
        );
        $signature = md5(implode(":", $signature_values));

        ob_end_clean();
        $form = "
            <html>
              <body>
                    <form action='" . nc_payment_system_robokassa::TARGET_URL . "' method='post'>" .
                    $this->make_inputs(array(
                        'MrchLogin' => $this->get_setting('MrchLogin'),
                        'OutSum' => $amount,
                        'InvId' => $invoice->get_id(),
                        'InvDesc' => $invoice->get_description(),
                        'SignatureValue' => $signature,
                    )) . "
                </form>
                <script>
                  document.forms[0].submit();
                </script>
              </body>
            </html>";
        echo $form;
        exit;
    }

    /**
     * @param nc_payment_invoice $invoice
     */
    public function on_response(nc_payment_invoice $invoice = null) {
        $invoice->set('status', nc_payment_invoice::STATUS_SUCCESS);
        $invoice->save();
        $this->on_payment_success($invoice);
        echo 'OK' . $invoice->get_id();
    }

    /**
     *
     */
    public function validate_payment_request_parameters() {
        if (!$this->get_setting('MrchLogin')) {
            $this->add_error(nc_payment_system_robokassa::ERROR_MRCHLOGIN_IS_NOT_VALID);
        }
    }

    /**
     * @param nc_payment_invoice $invoice
     */
    public function validate_payment_callback_response(nc_payment_invoice $invoice = null) {
        $their_key = $this->get_response_value('SignatureValue');
        $out_sum = $this->get_response_value('OutSum');

        $signature_values = array(
            $this->get_response_value('OutSum'),
            $this->get_response_value('InvId'),
            $this->get_setting('MerchantPass2')
        );
        $our_key = strtoupper(md5(join(":", $signature_values)));

        if (!$invoice || $our_key != $their_key || $out_sum != $invoice->get_amount()) {
            $this->add_error(nc_payment_system_robokassa::ERROR_PRIVATE_SECURITY_IS_NOT_VALID);

            if ($invoice) {
                $invoice->set('status', nc_payment_invoice::STATUS_CALLBACK_ERROR);
                $invoice->save();
            }
        }
    }

    public function load_invoice_on_callback() {
        return $this->load_invoice($this->get_response_value('InvId'));
    }
}

