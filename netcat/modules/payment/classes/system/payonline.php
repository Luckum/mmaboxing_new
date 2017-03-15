<?

class nc_payment_system_payonline extends nc_payment_system {

    const ERROR_MERCHANT_ID = NETCAT_MODULE_PAYMENT_PAYONLINE_ERROR_MERCHANT_ID;
    const ERROR_PRIVATE_SECURITY_KEY_IS_NULL = NETCAT_MODULE_PAYMENT_PAYONLINE_ERROR_PRIVATE_SECURITY_KEY_IS_NULL;
    const ERROR_PRIVATE_SECURITY_IS_NOT_VALID = NETCAT_MODULE_PAYMENT_PAYONLINE_ERROR_PRIVATE_SECURITY_IS_NOT_VALID;

    const TARGET_URL = "https://secure.payonlinesystem.com/ru/payment/select/";

    protected $automatic = TRUE;

    // принимаемые валюты
    protected $accepted_currencies = array('RUB', 'RUR', 'USD', 'EUR');
    protected $currency_map = array('RUR' => 'RUB');

    // параметры сайта в платежной системе
    protected $settings = array(
        'MerchantId' => null,
        'PrivateSecurityKey' => null,
    );

    // передаваемые параметры
    protected $request_parameters = array(
        // 'Amount' => null,
        // 'Currency' => 'RUB',
        'ValidUntil' => null,
        // 'OrderDescription' => null,
        // 'SecurityKey' => null, // вычисляется
        // 'ReturnUrl' => null,
        // 'FailUrl' => null,
    );

    // получаемые параметры
    protected $callback_response = array(
        'DateTime' => null,
        'TransactionID' => null,
        'Provider' => null, // [Card, Qiwi, WebMoney, YandexMoney]
        'IpAddress' => null,
        'IpCountry' => null,
        // ???????
        'OrderId' => null,
        'Amount' => null,
        'Currency' => null,
        // при оплате с карты
        'CardHolder' => null,
        'CardNumber' => null,
        'Country' => null,
        'City' => null,
        'Address' => null,
        'BinCountry' => null,
        // при оплате QIWI
        'Phone' => null,
        // при оплате WebMoney
        'WmTranId' => null,
        'WmInvId' => null,
        'WmId' => null,
        'WmPurse' => null,
        // при оплате Яндекс.Деньги
        'YmInvoiceId' => null,
        'YmMode' => null,
        'YmPayerCode' => null,
    );

    /**
     *
     */
    public function execute_payment_request(nc_payment_invoice $invoice) {
        ob_end_clean();
        header("Location: " . $this->get_pay_request_url($invoice));
        exit;
    }

    /**
     * @param nc_payment_invoice $invoice
     * @return string
     */
    protected function get_pay_request_url(nc_payment_invoice $invoice) {
        // вычисление значения для параметра SecurityKey
        $security_key_values = array(
            'MerchantId' => $this->get_setting('MerchantId'),
            'OrderId' => $invoice->get_id(),
            'Amount' => $invoice->get_amount('%0.2F'),
            'Currency' => $this->get_currency_code($invoice->get_currency()),
            'ValidUntil' => $this->get_request_parameter('ValidUntil'),
            'OrderDescription' => $invoice->get_description(),
            'PrivateSecurityKey' => $this->get_setting('PrivateSecurityKey'),
        );
        $security_key = $this->calculate_security_key($security_key_values);

        // подготовка параметров для запроса
        $query_values = array_merge($this->request_parameters, array(
            'MerchantId' => $this->get_setting('MerchantId'),
            'OrderId' => $invoice->get_id(),
            'Amount' => $invoice->get_amount('%0.2F'),
            'Currency' => $this->get_currency_code($invoice->get_currency()),
            'ValidUntil' => $this->get_request_parameter('ValidUntil'),
            'OrderDescription' => $invoice->get_description(),
            'SecurityKey' => $security_key,
            'ReturnUrl' => $this->get_callback_script_url() . "?action=success", // $this->get_setting('ReturnUrl'),
            'FailUrl' => $this->get_callback_script_url() . "?action=error", // $this->get_setting('FailUrl'),
        ));
        $query = $this->make_query_string($query_values);

        return nc_payment_system_payonline::TARGET_URL . "?" . $query;
    }

    /**
     *
     */
    protected function calculate_security_key(array $secret_key_values) {
        return md5($this->make_query_string($secret_key_values));
    }

    /**
     * @param array $params
     * @return string
     */
    protected function make_query_string($params) {
        return http_build_query($params, '', '&');
    }

    /**
     *
     */
    public function validate_payment_request_parameters() {
        if (!is_numeric($this->get_setting('MerchantId'))) {
            $this->add_error(nc_payment_system_payonline::ERROR_MERCHANT_ID);
        }

        if (!($this->get_setting('PrivateSecurityKey'))) {
            $this->add_error(nc_payment_system_payonline::ERROR_PRIVATE_SECURITY_KEY_IS_NULL);
        }

    }

    /**
     * @param nc_payment_invoice $invoice
     */
    public function on_response(nc_payment_invoice $invoice = null) {
        // предполагается, что в настройках PayOnline в URL callback вызова передается
        // GET-параметр action (success — платеж прошёл, error — нет)
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
        $security_key_values = array(
            'DateTime' => $this->get_response_value('DateTime'),
            'TransactionID' => $this->get_response_value('TransactionID'),
            'OrderId' => $this->get_response_value('OrderId'),   // ??? $invoice->get_id(),
            'Amount' => $this->get_response_value('Amount'),     // ??? $invoice->get_amount("%0.2F"),
            'Currency' => $this->get_response_value('Currency'), // ???
            'PrivateSecurityKey' => $this->get_setting('PrivateSecurityKey')
        );
        $security_key = $this->calculate_security_key($security_key_values);

        if ($security_key != $this->get_response_value('SecurityKey')) {
            $this->add_error(self::ERROR_PRIVATE_SECURITY_IS_NOT_VALID);
        }
    }

    public function load_invoice_on_callback() {
        return $this->load_invoice($this->get_response_value('OrderId'));
    }
}
