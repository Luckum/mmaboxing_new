<?

/**
 * Абстрактный класс платежной системы
 *
 */

abstract class nc_payment_system {

    /**
     * Общие ошибки
     */
    const ERROR_INVOICE_NOT_FOUND = NETCAT_MODULE_PAYMENT_ERROR_INVOICE_NOT_FOUND;
    const ERROR_INVALID_SIGNATURE = NETCAT_MODULE_PAYMENT_ERROR_PRIVATE_SECURITY_IS_NOT_VALID;
    const ERROR_INVALID_SUM = NETCAT_MODULE_PAYMENT_ERROR_INVALID_SUM;

    /**
     * Названия генерируемых событий
     */
    const EVENT_ON_INIT = "onPayInit";
    const EVENT_BEFORE_PAY_REQUEST = "beforePayRequest";
    const EVENT_AFTER_PAY_REQUEST = "afterPayRequest";
    const EVENT_ON_PAY_REQUEST_ERROR = "onPayRequestError";
    const EVENT_BEFORE_PAY_CALLBACK = "beforePayCallback";
    const EVENT_AFTER_PAY_CALLBACK = "afterPayCallback";
    const EVENT_ON_PAY_CALLBACK_ERROR = "onPayCallbackError";
    const EVENT_ON_PAY_SUCCESS = "onPaySuccess";
    const EVENT_ON_PAY_FAILURE = "onPayFailure";

    /**
     * @var int   ID платёжной системы (классификатор PaymentSystem)
     */
    protected $id;

    /**
     * @var boolean  TRUE — автоматический прием платежа, FALSE — ручная проверка
     */
    protected $automatic;

    /**
     * @var array  Коды валют, которые принимает платежная система (трехбуквенные коды ISO 4217)
     */
    protected $accepted_currencies = array('RUB');

    /**
     * @var array  Автоматический маппинг кодов валют из внешних в принятые в платежной системе
     */
    protected $currency_map = array();

    /**
     * @var array  Настройки платёжной системы
     */
    protected $settings = array();

    /**
     * @var array  Дополнительные (изменяемые) параметры запроса к платежной системе
     */
    protected $request_parameters = array();

    /**
     * @var array  Ответ платёжной системы, @see self::set_callback_response()
     */
    protected $callback_response = array();

    /**
     * @var array  Массив с ошибками
     */
    protected $errors = array();

    /**
     * Конструктор объекта платежной системы
     *
     */
    public function __construct() {
        $this->notify_listeners(nc_payment_system::EVENT_ON_INIT);
    }

    // --- Геттеры и сеттеры ---------------------------------------------------
    /**
     * @param int $id
     */
    public function set_id($id) {
        $this->id = (int)$id;
    }

    /**
     * @return int
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Установка значений параметров платёжной системы
     */
    public function set_settings(array $settings) {
        $this->settings = array_merge($this->settings, $settings);
    }

    /**
     * Значение параметра для платёжной системы
     */
    public function get_setting($setting_name) {
        return isset($this->settings[$setting_name])
            ? $this->settings[$setting_name]
            : null;
    }

    /**
     * Устанавливает дополнительный параметр запроса к платежной системе
     */
    public function set_request_parameters(array $params) {
        $this->request_parameters = array_merge($this->request_parameters, $params);
    }

    /**
     *
     */
    public function get_request_parameter($param) {
        return isset($this->request_parameters[$param])
            ? $this->request_parameters[$param]
            : null;
    }


    /**
     * Возвращает массив с параметрами сайта в платежной системе
     *
     * @return    integer
     */
    public function get_settings_list() {
        return array_keys($this->settings);
    }

    /**
     * Регистрирует ответ от платежной системы (callback)
     * @see callback.php
     */
    protected function set_callback_response(array $response) {
        $this->callback_response = array_merge($this->callback_response, $response);
    }

    /**
     * Возвращает параметр ответа платежной системы
     */
    public function get_response_value($param) {
        return isset($this->callback_response[$param])
            ? $this->callback_response[$param]
            : null;
    }

    /**
     * Возвращает весь полученный ответ
     */
    public function get_response() {
        return $this->callback_response;
    }

    /**
     * Проверка правильности суммы
     */
    public function is_amount_valid($amount) {
        return is_numeric($amount) && $amount > 0;
    }

    /**
     * Возвращает код валюты с учётом $this->currency_map
     */
    protected function get_currency_code($iso_currency_code) {
        if (isset($this->currency_map[$iso_currency_code])) {
            return $this->currency_map[$iso_currency_code];
        }
        return $iso_currency_code;
    }

    /**
     * Проверяет, принимает ли платежная система указанную валюту
     * @param $currency_code
     * @return bool
     */
    public function is_currency_accepted($currency_code) {
        return in_array($currency_code, $this->accepted_currencies);
    }

    // --- Получение путей к обработчикам запросов/ответов ---------------------
    /**
     *
     */
    protected function get_module_url() {
        $https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off');
        $protocol = $https ? "https" : "http";
        $domain = nc_core('catalogue')->get_current('Domain');
        return "$protocol://$domain" . nc_module_path('payment');
    }

    /**
     *
     */
    protected function get_request_script_path() {
        return nc_module_path('payment') . "pay_request.php";
    }

    /**
     *
     */
    protected function get_callback_script_url() {
        return $this->get_module_url() . "callback.php";
    }

    // --- Хелперы для работы с формами ----------------------------------------
    /**
     *
     */
    protected function make_input($name, $value, $type = "hidden") {
        return "<input type='{$type}' name='" . htmlspecialchars($name, ENT_QUOTES) .
               "' value='" . htmlspecialchars($value, ENT_QUOTES) . "' />\n";
    }

    protected function make_inputs(array $values, $type = "hidden") {
        $result = "";
        foreach ($values as $name => $value) {
            $result .= $this->make_input($name, $value, $type);
        }
        return $result;
    }

    // --- Работа с запросами и ответами платёжной системы ---------------------

    /**
     * @param string $event_name
     * @param nc_payment_invoice $invoice
     */
    protected function notify_listeners($event_name, nc_payment_invoice $invoice = null) {
        nc_core('event')->execute($event_name, $this, $invoice);
    }

    /**
     * Добавление ошибки к массиву ошибок
     *
     * @param    string $string
     */
    protected function add_error($string) {
        $this->errors[] = $string;
    }

    /**
     * Возвращает массив ошибкой
     *
     * @return    array
     */
    public function get_errors() {
        return $this->errors;
    }

    /**
     *
     */
    public function reset_errors() {
        $this->errors = array();
    }

    /**
     * Возвращает значение свойства automatic
     *
     * @return    integer
     */
    public function is_automatic() {
        return $this->automatic;
    }

    /**
     * Запрос на проведение платежа
     *
     */
    final public function process_payment_request(nc_payment_invoice $invoice) {
        $this->notify_listeners(nc_payment_system::EVENT_BEFORE_PAY_REQUEST, $invoice);

        $this->validate_invoice($invoice);
        $this->validate_payment_request_parameters();

        if (!count($this->errors)) {

            $invoice->set('payment_system_id', $this->get_id())->save();
            $this->execute_payment_request($invoice);

            $invoice->set('status', nc_payment_invoice::STATUS_SENT_TO_PAYMENT_SYSTEM)->save();
            $this->notify_listeners(nc_payment_system::EVENT_AFTER_PAY_REQUEST, $invoice);

        } else {
            $this->notify_listeners(nc_payment_system::EVENT_ON_PAY_REQUEST_ERROR, $invoice);
            throw new nc_payment_exception(NETCAT_MODULE_PAYMENT_REQUEST_ERROR);
        }

    }

    /**
     * Запрос на обработку обратного вызова платежной системы
     *
     */
    final public function process_callback_response(array $response = array()) {
        $this->set_callback_response($response);

        $invoice = $this->load_invoice_on_callback();

        if ($invoice instanceof nc_payment_invoice) {
            $invoice->set('last_response', json_encode($response));
            $invoice->save();
        }
        else if ($invoice === false) {
            $this->add_error(NETCAT_MODULE_PAYMENT_CANNOT_LOAD_INVOICE_ON_CALLBACK);
            $invoice = null;
        }
        else {
            $invoice = null;
        }

        $this->validate_payment_callback_response($invoice);
        $this->notify_listeners(nc_payment_system::EVENT_BEFORE_PAY_CALLBACK, $invoice);

        if (!count($this->errors)) {
            $this->on_response($invoice);
            $this->notify_listeners(nc_payment_system::EVENT_AFTER_PAY_CALLBACK, $invoice);
        } else {
            $this->notify_listeners(nc_payment_system::EVENT_ON_PAY_CALLBACK_ERROR, $invoice);
        }
    }

    /**
     * Возвращает форму заполнения деталей платежа.
     * По умолчанию — POST-форма для выполнения запроса через скрипт pay_request.php
     */
    public function get_request_form(nc_payment_invoice $invoice, $show = 1) {
        $result = "<form action='" . $this->get_request_script_path() . "' method='post' target='_blank' id='nc_payment_form'>";
        $result .= $this->make_input('invoice_id', $invoice->get_id());
        $result .= $this->make_input('payment_system', get_class($this));
        $result .= $show ? "<input type='submit' value='".NETCAT_MODULE_PAYMENT_FORM_PAY."'>" : "";
        $result .= "</form>";
        $result .= !$show ? "<script type='text/javascript'>document.getElementById('nc_payment_form').submit();</script>" : "";

        return $result;
    }

    /**
     * Здесь должно происходить конкретное действие проведение платежа
     *
     */
    abstract protected function execute_payment_request(nc_payment_invoice $invoice);

    /**
     * Здесь должен осуществляться анализ обратного вызова платежной системы и
     * вызов методов on_payment_success() или on_payment_failure().
     * Не забудьте установить id заказа:
     * $this->set_order_id($this->get_response_value('ПолеОтветаСодержащееIdЗаказа'));
     *
     * @param nc_payment_invoice $invoice
     * @return
     */
    abstract protected function on_response(nc_payment_invoice $invoice = null);

    /**
     * Здесь должна осуществляться проверка параметров для проведения платежа.
     * В случае ошибок вызывать метод add_error($string)
     *
     */
    abstract public function validate_payment_request_parameters();


    /**
     * Проверка корректности счёта
     */
    protected function validate_invoice(nc_payment_invoice $invoice) {
        if (!($invoice->get_id())) {
            $this->add_error(NETCAT_MODULE_PAYMENT_ORDER_ID_IS_NULL);
        }

        if (!$this->is_amount_valid($invoice->get_amount())) {
            $this->add_error(NETCAT_MODULE_PAYMENT_INCORRECT_PAYMENT_AMOUNT);
        }

        if (!$this->is_currency_accepted($invoice->get_currency())) {
            $error = sprintf(NETCAT_MODULE_PAYMENT_INCORRECT_PAYMENT_CURRENCY,
                             htmlspecialchars($invoice->get_currency()));

            $this->add_error($error);
        }
    }

    /**
     * Здесь должна осуществляться проверка параметров при поступлении обратного
     * вызова платежной системы.
     * В случае ошибок вызывать метод add_error($string)
     *
     * @param nc_payment_invoice $invoice
     * @return
     */
    abstract public function validate_payment_callback_response(nc_payment_invoice $invoice = null);

    /**
     * Здесь могут быть выполнены действия при успешном платеже
     * @param nc_payment_invoice $invoice
     */
    protected function on_payment_success(nc_payment_invoice $invoice = null) {
        $this->notify_listeners(nc_payment_system::EVENT_ON_PAY_SUCCESS, $invoice);
    }

    /**
     * Здесь могут быть выполнены действия при неудачном платеже
     * @param nc_payment_invoice $invoice
     */
    protected function on_payment_failure(nc_payment_invoice $invoice = null) {
        $this->notify_listeners(nc_payment_system::EVENT_ON_PAY_FAILURE, $invoice);
    }

    /**
     * Загрузка объекта платежа
     * @param int $invoice_id
     * @return nc_payment_invoice|boolean
     */
    protected function load_invoice($invoice_id) {
        try {
            return new nc_payment_invoice((int)$invoice_id);
        }
        catch (nc_record_exception $e) {
            return false;
        }
    }

    /**
     * @return nc_payment_invoice|boolean
     */
    protected function load_invoice_on_callback() {
        return true;
    }

}
