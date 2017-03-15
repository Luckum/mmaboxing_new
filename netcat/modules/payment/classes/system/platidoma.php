<?

class nc_payment_system_platidoma extends nc_payment_system {

    const ERROR_PGSHOPID_IS_NOT_VALID = NETCAT_MODULE_PAYMENT_PLATIDOMA_ERROR_PGSHOPID_IS_NOT_VALID;
    const ERROR_PGLOGIN_IS_NOT_VALID = NETCAT_MODULE_PAYMENT_PLATIDOMA_ERROR_PGLOGIN_IS_NOT_VALID;
    const ERROR_PGGATEPASSWORD_IS_NOT_VALID = NETCAT_MODULE_PAYMENT_PLATIDOMA_ERROR_PGGATEPASSWORD_IS_NOT_VALID;
    const ERROR_PRIVATE_SECURITY_KEY_IS_NULL = NETCAT_MODULE_PAYMENT_PLATIDOMA_ERROR_PRIVATE_SECURITY_KEY_IS_NULL;
    const ERROR_PRIVATE_KEY_IS_NOT_VALID = NETCAT_MODULE_PAYMENT_PLATIDOMA_ERROR_PRIVATE_KEY_IS_NOT_VALID;
    const ERROR_PRIVATE_SECURITY_IS_NOT_VALID = NETCAT_MODULE_PAYMENT_PLATIDOMA_ERROR_PRIVATE_SECURITY_IS_NOT_VALID;
    const ERROR_ORDER_ID_IS_LONG = NETCAT_MODULE_PAYMENT_PLATIDOMA_ERROR_ORDER_ID_IS_LONG;

    const TARGET_URL = "https://pg.platidoma.ru/payment.php";

    protected $automatic = TRUE;

    // принимаемые валюты
    protected $accepted_currencies = array('RUB', 'RUR');

    // параметры сайта в платежной системе
    protected $settings = array(
        'pd_shop_id' => null,
        'pd_login' => null,
        'pd_gate_password' => null,
    );

    // передаваемые параметры
    protected $request_parameters = array(
        // 'pd_order_id' => null, // $this->get_order_id()
        // 'pd_descr' => null, // $this->get_order_description()
        'pd_email' => null,
        'pd_phone' => null,
        // 'pd_rnd' => null,  // calculated
        // 'pd_sign' => null, // calculated
    );

    // получаемые параметры
    protected $callback_response = array(
        'pd_amount' => null,
        'pd_trans_id' => null,
        'pd_order_id' => null,
        'pd_rnd' => null,
    );

    /**
     *
     */
    public function execute_payment_request(nc_payment_invoice $invoice) {
        ob_end_clean();

        $pd_amount = str_replace(',', '.', $invoice->get_amount("%.2F"));
        $pd_rnd = md5($invoice->get_id() . "&" . $pd_amount);

        // calculate 'pd_sign'
        $signature_values = array(
            $this->get_setting('pd_shop_id'),
            $this->get_setting('pd_login'),
            $this->get_setting('pd_gate_password'),
            $pd_rnd,
            $pd_amount
        );
        $pd_sign = md5(implode(":", $signature_values));

        $form = "
            <html>
              <body>
                    <form action='" . nc_payment_system_platidoma::TARGET_URL . "' method='post'>" .
                    $this->make_inputs(array(
                        'pd_shop_id' => $this->get_setting('pd_shop_id'),
                        'pd_login' => $this->get_setting('pd_login'),
                        'pd_amount' => $pd_amount,
                        'pd_order_id' => $invoice->get_id(),
                        'pd_descr' => $invoice->get_description(),
                        'pd_email' => $invoice->get('customer_email'),
                        'pd_phone' => $invoice->get('customer_phone'),
                        'pd_rnd' => $pd_rnd,
                        'pd_sign' => $pd_sign,
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
        if (!$this->get_setting('pd_shop_id')) {
            $this->add_error(nc_payment_system_platidoma::ERROR_PGSHOPID_IS_NOT_VALID);
        }

        if (!$this->get_setting('pd_login')) {
            $this->add_error(nc_payment_system_platidoma::ERROR_PGLOGIN_IS_NOT_VALID);
        }

        if (!$this->get_setting('pd_gate_password')) {
            $this->add_error(nc_payment_system_platidoma::ERROR_PGGATEPASSWORD_IS_NOT_VALID);
        }
    }

    /**
     * @param nc_payment_invoice $invoice
     */
    public function on_response(nc_payment_invoice $invoice = null) {
        //предполагается, что в настройках Platidoma в URL callback вызова
        // передается GET параметр action (success — платеж прошёл, error — нет)
        if ($this->get_response_value('action') == 'success') {
            $this->on_payment_success($invoice);
        } else {
            $this->on_payment_failure($invoice);
        }
    }

    /**
     * @param nc_payment_invoice $invoice
     */
    public function validate_payment_callback_response(nc_payment_invoice $invoice = null) {
        // check pd_rnd (not sure if this has any sense at all)
        $their_rnd = $this->get_response_value('pd_rnd');
        $our_rnd = md5($this->get_response_value('pd_order_id') . "&" . $this->get_response_value('pd_amount'));

        if ($their_rnd != $our_rnd) {
            $this->add_error(nc_payment_system_platidoma::ERROR_PRIVATE_KEY_IS_NOT_VALID);
        }

        $their_signature = $this->get_response_value('pd_sign');
        $signature_values = array(
            $this->get_setting('pd_shop_id'),
            $this->get_setting('pd_login'),
            $this->get_setting('pd_gate_password'),
            $this->get_response_value('pd_rnd'),
            $this->get_response_value('pd_trans_id'),
            $this->get_response_value('pd_order_id'),
            $this->get_response_value('pd_amount'),
        );
        $our_signature = md5(implode(":", $signature_values));

        if ($their_signature != $our_signature) {
            $this->add_error(nc_payment_system_platidoma::ERROR_PRIVATE_SECURITY_IS_NOT_VALID);
        }
    }

    public function load_invoice_on_callback() {
        return $this->load_invoice($this->get_response_value('pd_order_id'));
    }
}
