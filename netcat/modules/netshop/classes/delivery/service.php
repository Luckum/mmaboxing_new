<?php

abstract class nc_netshop_delivery_service {

    /**
     * Коды ошибок
     */
    const ERROR_OK = nc_netshop_delivery_estimate::ERROR_OK;
    const ERROR_CANNOT_CONNECT_TO_GATE = nc_netshop_delivery_estimate::ERROR_SERVICE_CANNOT_CONNECT_TO_GATE;
    const ERROR_GATE_ERROR = nc_netshop_delivery_estimate::ERROR_SERVICE_GATE_ERROR;
    const ERROR_WRONG_WEIGHT = nc_netshop_delivery_estimate::ERROR_SERVICE_WRONG_WEIGHT;
    const ERROR_WRONG_RECIPIENT = nc_netshop_delivery_estimate::ERROR_SERVICE_WRONG_RECIPIENT;
    const ERROR_WRONG_SENDER = nc_netshop_delivery_estimate::ERROR_SERVICE_WRONG_SENDER;

    /**
     * Максимальное количество секунд для ожидания ответа от удалённого сервера
     *
     * @var int
     */
    protected $http_request_timeout = 5;

    /**
     * Название службы
     *
     * @var string
     */
    protected $delivery_type_name = '';

    /**
     * Поля, которым нужны соответствия
     *
     * @var array
     */
    protected $mapped_fields = array();

    /**
     * Атрибуты посылки
     *
     * @var array
     */
    protected $fields = array(
        'from_legal_entity', //юр. наименование отправителя
        'from_fullname', //полное имя отправителя
        'from_country', //страна отправления
        'from_city', //город отправления
        'from_region', //регион отправления
        'from_street', //улица отправления
        'from_house', //дом отправления
        'from_block', //строение отправления
        'from_floor', //этаж отправления
        'from_apartment', //квартира/офис отправления
        'from_zipcode', //индекс пункта отправления
        'from_phone', //телефон отправителя
        'to_legal_entity', //юр. наименование получателя
        'to_fullname', //полное имя получателя
        'to_country', //страна получения
        'to_city', //город получения
        'to_region', //регион получения
        'to_street', //улица получения
        'to_house', //дом получения
        'to_block', //строение получения
        'to_floor', //этаж получения
        'to_apartment', //квартира/офис получения
        'to_zipcode', //индекс пункта получения
        'to_phone', //телефон получателя
        'description', //описание посылки
        'weight', //вес посылки
        'valuation', //ценость посылки
        'cash_on_delivery', //сумма наложного платежа
        'receiver_inn', //инн получателя платежа
        'receiver_corr', //корр. счет получателя платежа
        'receiver_account', //номер расчетного счета получателя платежа
        'receiver_bank', //банк получателя платежа
        'receiver_bik', //БИК банка получателя платежа
        'tracking_number', //номер отслеживания посылки
    );

    /**
     * Последняя ошибка
     *
     * @var string
     */
    protected $last_error = '';

    /**
     * Код последней ошибки,
     * 0 - выполнение успешно
     *
     * @var int
     */
    protected $last_error_code = self::ERROR_OK;

    /**
     * Данные посылки
     *
     * @param $data
     */
    public function __construct(array $data = array()) {
        $this->data = array();

        $this->set_data($data);

        foreach ($this->fields as $field) {
            if (!isset($this->data[$field])) {
                $this->data[$field] = null;
            }
        }
    }

    /**
     * @param array $data
     */
    public function set_data(array $data) {
        foreach ($data as $index => $value) {
            if (in_array($index, $this->fields)) {
                $this->data[$index] = $value;
            }
        }
    }

    /**
     * Возвращает имя службы доставки
     *
     * @return string
     */
    public function get_delivery_type_name() {
        return $this->delivery_type_name;
    }

    /**
     * Возвращает поля
     *
     * @return array
     */
    public function get_mapped_fields() {
        return $this->mapped_fields;
    }

    /**
     * Рассчитать стоимость посылки.
     * При успешном выполнении возвращается массив:
     * array(
     *     'price' => стоимость доставки,
     *     'currency' => валюта стоимости доставки
     *     'min_days' => минимальное количество дней на доставку
     *     'max_days' => максимальное количество дней на доставку
     * )
     *
     * При ошибке возвращается null
     *
     * @return array|null
     */
    abstract public function calculate_delivery();

    /**
     * Возврат HTML кода сформированного
     * бланка посылки
     *
     * @return string
     */
    abstract public function print_package_form();

    /**
     * Возврат HTML кода сформированного
     * бланка наложного платежа
     *
     * @return string
     */
    abstract public function print_cash_on_delivery_form();

    /**
     * Возврат информации по точкам
     * следования посылки
     *
     * @return array|null
     */
    abstract public function get_tracking_information();

    /**
     * @return string
     */
    public function get_last_error() {
        return $this->last_error;
    }

    /**
     * @return int
     */
    public function get_last_error_code() {
        return $this->last_error_code;
    }

    /**
     * HTTP-запрос
     *
     * @param $url
     * @param null $post
     * @param null $referer
     * @return mixed|string
     */
    protected function _http_request($url, $post = null, $referer = null) {
        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:7.0.1) Gecko/20100101 Firefox/7.0.1');
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->http_request_timeout);
            curl_setopt($curl, CURLOPT_TIMEOUT, $this->http_request_timeout);

            if ($referer) {
                curl_setopt($curl, CURLOPT_REFERER, $referer);
            }

            if ($post !== null) {
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post, null, '&'));
            }

            $result = curl_exec($curl);
        } else {
            $context = null;

            if ($post) {
                $postdata = http_build_query($post, null, '&');

                $opts = array(
                    'http' => array(
                        'method' => 'POST',
                        'timeout' => $this->http_request_timeout,
                        'header' => 'Content-type: application/x-www-form-urlencoded' .
                                    ($referer ? "\r\nReferer: " . $referer . "\r\n" : ""),
                        'content' => $postdata
                    )
                );

                $context = stream_context_create($opts);
            } else if ($referer) {
                $opts = array(
                    'http' => array(
                        'method' => 'GET',
                        'timeout' => $this->http_request_timeout,
                        'header' => 'Referer: ' . $referer,
                    )
                );

                $context = stream_context_create($opts);
            }
            $result = @file_get_contents($url, false, $context);
        }

        return $result;
    }

    //--------------------------------------------------------------------------

    private function __clone() {}
    private function __wakeup() {}

    //--------------------------------------------------------------------------
}
