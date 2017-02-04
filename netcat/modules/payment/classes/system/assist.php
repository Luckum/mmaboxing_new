<?php

class nc_payment_system_assist extends nc_payment_system {

    const ERROR_CHECKVALUE_IS_NOT_VALID = NETCAT_MODULE_PAYMENT_ASSIST_ERROR_CHECKVALUE_IS_NOT_VALID;
    const ERROR_ASSIST_SHOP_ID = NETCAT_MODULE_PAYMENT_ASSIST_ERROR_ASSIST_SECRET_WORD_IS_NULL;
    const ERROR_ASSIST_SECRET_WORD_IS_NULL = NETCAT_MODULE_PAYMENT_ASSIST_ERROR_ASSIST_SECRET_WORD_IS_NULL;

    const STATUS_APPROVED = 'Approved';

    const TARGET_URL = "https://payments.paysecure.ru/pay/order.cfm";

    protected $automatic = TRUE;

    // принимаемые валюты
    protected $accepted_currencies = array('RUB', 'RUR', 'USD', 'EUR');
    protected $currency_map = array('RUB' => 'RUR');

    // передаваемые параметры
    protected $request_parameters = array();

    // получаемые параметры
    protected $callback_response = array(
        'ordernumber' => null,
        'checkvalue' => null,
        'merchant_id' => null,
        'orderstate' => null,
        'currency' => null,
    );

    // параметры сайта в платежной системе
    protected $settings = array(
        "AssistShopId" => null,
        "AssistSecretWord" => null,
        "PaymentSuccessPage" => null,
        "PaymentFailedPage" => null,
    );

    /**
     *
     */
    public function execute_payment_request(nc_payment_invoice $invoice) {
        ob_end_clean();

        // TODO кодировка ContactName?
        list($last_name, $first_name) = explode(" ", $invoice->get('customer_name'), 2);
        $email = $invoice->get('customer_email');

        $form = "
            <html>
             <body>
             <form id='fassist' action='" . self::TARGET_URL . "' method='post'>" .
            $this->make_inputs(array(
                'Merchant_ID' => $this->get_setting('AssistShopId'),
                'OrderNumber' => $invoice->get_id(),
                'OrderAmount' => $invoice->get_amount(),
                'Language' => 'RU',
                'URL_RETURN' => "http://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'],
                'URL_RETURN_OK' => $this->get_setting('PaymentSuccessPage'),
                'URL_RETURN_NO' => $this->get_setting('PaymentFailedPage'),
                'OrderCurrency' => $this->get_currency_code($invoice->get_currency()),
                'OrderComment' => $invoice->get_description(),
                'LastName' => $last_name,
                'FirstName' => $first_name,
                'Email' => $email,
                'CardPayment' => 1,
                'AssistIDPayment' => 1,
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
        if (!is_numeric($this->get_setting('AssistShopId'))) {
            $this->add_error(self::ERROR_ASSIST_SHOP_ID);
        }

        if (!($this->get_setting('AssistSecretWord'))) {
            $this->add_error(self::ERROR_ASSIST_SECRET_WORD_IS_NULL);
        }
    }

    /**
     * @param nc_payment_invoice $invoice
     */
    public function validate_payment_callback_response(nc_payment_invoice $invoice = null) {
        $checkvalue = strtoupper(
            md5(strtoupper(
                md5($this->get_setting('AssistSecretWord')) .
                    md5($this->get_response_value('merchant_id') .
                        $this->get_response_value('ordernumber') .
                        $this->get_response_value('amount') .
                        $this->get_response_value('currency') .
                        $this->get_response_value('orderstate'))
            )));

        if ($this->get_response_value('checkvalue') !== $checkvalue) {
            $this->add_error(self::ERROR_CHECKVALUE_IS_NOT_VALID);
        }
    }

    /**
     * @param nc_payment_invoice $invoice
     */
    public function on_response(nc_payment_invoice $invoice = null) {
        if ($this->get_response_value('orderstate') == self::STATUS_APPROVED) {
            $this->on_payment_success($invoice);
        } else {
            $this->on_payment_failure($invoice);
        }
    }

    public function load_invoice_on_callback() {
        return $this->load_invoice($this->get_response_value('ordernumber'));
    }
}
