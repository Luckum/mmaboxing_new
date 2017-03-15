<?php

class nc_payment_system_webmoney extends nc_payment_system {

    const ERROR_PURSE_IS_NOT_VALID = NETCAT_MODULE_PAYMENT_WEBMONEY_ERROR_PURSE_IS_NOT_VALID;
    const ERROR_PRIVATE_SECURITY_IS_NOT_VALID = NETCAT_MODULE_PAYMENT_WEBMONEY_ERROR_PRIVATE_SECURITY_IS_NOT_VALID;
    const ERROR_ORDER_ID_IS_LONG = NETCAT_MODULE_PAYMENT_WEBMONEY_ERROR_ORDER_ID_IS_LONG;

    const TARGET_URL = "https://merchant.webmoney.ru/lmi/payment.asp";

    protected $automatic = TRUE;

    // принимаемые валюты
    protected $accepted_currencies = array('RUB', 'RUR');

    public $paramsForHash = array('LMI_PAYEE_PURSE', 'LMI_PAYMENT_AMOUNT', 'LMI_PAYMENT_DESC', 'LMI_SIM_MODE', 'Salt');
    public $paramsReciverQweryForHash = array('LMI_PAYEE_PURSE', 'LMI_PAYMENT_AMOUNT', 'LMI_PAYMENT_NO', 'LMI_MODE', 'LMI_SYS_INVS_NO', 'LMI_SYS_TRANS_NO', 'LMI_SYS_TRANS_DATE', 'WebmoneySecretKey', 'LMI_PAYER_PURSE', 'LMI_PAYER_WM');

    // параметры сайта в платежной системе
    protected $settings = array(
        'LMI_PAYEE_PURSE' => null,
        'WebmoneySecretKey' => null,
        'Salt' => null,
    );

    // передаваемые параметры
    protected $request_parameters = array(
        // 'LMI_PAYMENT_DESC' => "Платеж"
        // 'LMI_SIM_MODE' => 0
    );

    protected $callback_response = array(
        'LMI_PAYMENT_AMOUNT' => null,
        'LMI_PAYMENT_DESC' => null, // pre-request
        'LMI_PAYMENT_NO' => null,
        'LMI_MODE' => null,
        'LMI_SYS_INVS_NO' => null,
        'LMI_SYS_TRANS_NO' => null,
        'LMI_SYS_TRANS_DATE' => null,
        'LMI_PAYER_PURSE' => null,
        'LMI_PAYER_WM' => null,
    );

    /**
     *
     */
    public function execute_payment_request(nc_payment_invoice $invoice) {
        $amount = $invoice->get_amount();
        $description = $invoice->get_description();

        $key_values = array(
            $this->get_setting('LMI_PAYEE_PURSE'),
            $amount,
            $description,
            0, // 'LMI_SIM_MODE'
            $this->get_setting('Salt')
        );
        $key = md5(join('', $key_values));

        ob_end_clean();
        $form = "
            <html>
              <body>
                    <form action='" . nc_payment_system_webmoney::TARGET_URL . "' method='post'>" .
                        $this->make_inputs(array(
                            'LMI_PAYEE_PURSE' => $this->get_setting('LMI_PAYEE_PURSE'),
                            'LMI_PAYMENT_AMOUNT' => $amount,
                            /* @todo check: currency */
                            'LMI_PAYMENT_DESC' => $description,
                            'LMI_SIM_MODE' => 0,
                            'key' => $key,
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
        // предполагается, что в настройках Webmoney в URL callback вызова
        // передается GET параметр action (success — платеж прошёл, error — нет)
        if ($this->get_response_value('action') == 'success') {
            $this->on_payment_success($invoice);
        }
        else {
            $this->on_payment_failure($invoice);
        }
    }

    /**
     *
     */
    public function validate_payment_request_parameters() {
        if (!preg_match("/[ZREUD]\d{12}$/", $this->get_setting('LMI_PAYEE_PURSE'))) {
            $this->add_error(nc_payment_system_webmoney::ERROR_PURSE_IS_NOT_VALID);
        }
    }

    /**
     * @param nc_payment_invoice $invoice
     */
    protected function validate_invoice(nc_payment_invoice $invoice) {
        parent::validate_invoice($invoice);
        if (strlen($invoice->get_description()) > 255) {
            $this->add_error(nc_payment_system_webmoney::ERROR_ORDER_ID_IS_LONG);
        }
    }


    /**
     * @param nc_payment_invoice $invoice
     */
    public function validate_payment_callback_response(nc_payment_invoice $invoice = null) {
        // предварительная проверка со стороны платежной системы перед получением средств
        if ($this->get_response_value('LMI_PREREQUEST') == "1") {
            $this->process_prerequest();
        }

        $hash_values = array(
            $this->get_setting('LMI_PAYEE_PURSE'),
            $this->get_response_value('LMI_PAYMENT_AMOUNT'),
            $this->get_response_value('LMI_PAYMENT_NO'),
            $this->get_response_value('LMI_MODE'),
            $this->get_response_value('LMI_SYS_INVS_NO'),
            $this->get_response_value('LMI_SYS_TRANS_NO'),
            $this->get_response_value('LMI_SYS_TRANS_DATE'),
            $this->get_setting('WebmoneySecretKey'),
            $this->get_response_value('LMI_PAYER_PURSE'),
            $this->get_response_value('LMI_PAYER_WM')
        );
        $our_key = strtoupper(md5(join('', $hash_values)));
        $their_key = $this->get_response_value('LMI_HASH');

        if ($our_key != $their_key) {
            $this->add_error(nc_payment_system_webmoney::ERROR_PRIVATE_SECURITY_IS_NOT_VALID);
        }
    }

    /**
     *
     */
    public function process_prerequest() {
        $hash_values = array(
            $this->get_setting('LMI_PAYEE_PURSE'),
            $this->get_response_value('LMI_PAYMENT_AMOUNT'),
            $this->get_response_value('LMI_PAYMENT_DESC'),
            $this->get_response_value('LMI_SIM_MODE'),
            $this->get_setting('Salt')
        );
        $our_key = md5(join('', $hash_values));

        $their_key = $this->get_response_value('key');

        // Если все правильно, отвечаем YES
        print (($their_key == $our_key) ? "YES" : "Something went wrong");

        // that's all folks
        die;
    }

    public function load_invoice_on_callback() {
        return $this->load_invoice($this->get_response_value('LMI_PAYMENT_NO'));
    }
}