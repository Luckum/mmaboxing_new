<?php

class nc_netshop_delivery_service_ems extends nc_netshop_delivery_service {

    /**
     * Название службы
     *
     * @var string
     */
    protected $delivery_type_name = NETCAT_MODULE_NETSHOP_DELIVERY_EMS;

    /**
     * Поля, которым нужны соответствия
     *
     * @var array
     */
    protected $mapped_fields = array(
        'from_city' => NETCAT_MODULE_NETSHOP_DELIVERY_METHOD_SERVICE_FROM_CITY,
        'to_city' => NETCAT_MODULE_NETSHOP_DELIVERY_METHOD_SERVICE_TO_CITY,
    );

    /**
     * Рассчитать стоимость посылки.
     * При успешном выполнении возвращается массив:
     * array(
     *     'price' => стоимость доставки,
     *     'currency' => 'RUB',
     *     'min_days' => минимальное количество дней на доставку
     *     'max_days' => максимальное количество дней на доставку
     * )
     *
     * При ошибке возвращается null
     *
     * @return array|null
     */
    public function calculate_delivery() {
        $delivery_data = $this->data;

        $weight = (float)$delivery_data['weight'] / 1000; // вес в кг

        $max_weight = $this->_ems_api_request('ems.get.max.weight');
        $max_weight = (float)$max_weight['max_weight'];
        if ($weight <= 0 || $weight > $max_weight) {
            $this->last_error = NETCAT_MODULE_NETSHOP_DELIVERY_METHOD_SERVICE_INCORRECT_WEIGHT;
            $this->last_error_code = self::ERROR_WRONG_WEIGHT;
            return null;
        }

        $countries = array();
        $cities = array();

        /** @todo ЭТО СЛЕДУЕТ КЭШИРОВАТЬ  */
        $countries_request = $this->_ems_api_request('ems.get.locations', array(
            'type' => 'countries',
            'plain' => 'true',
        ));

        if (!$countries_request) {
            return null;
        }
        foreach ($countries_request['locations'] as $location) {
            $countries[$location['value']] = $location['name'];
        }

        $cities_request = $this->_ems_api_request('ems.get.locations', array(
            'type' => 'cities',
            'plain' => 'true',
        ));

        if (!$cities_request) {
            return null;
        }
        foreach ($cities_request['locations'] as $location) {
            $cities[$location['value']] = $location['name'];
        }

        $international = false;
        $from_id = null;
        $to_id = null;

        if ($delivery_data['from_city']) {
            $from_city = mb_strtolower($delivery_data['from_city'], 'utf-8');
            foreach ($cities as $city_id => $city) {
                if ($from_city == mb_strtolower($city, 'utf-8')) {
                    $from_id = $city_id;
                }
            }
        }

        if ($delivery_data['to_country']) {
            $to_country = mb_strtolower($delivery_data['to_country'], 'utf-8');
            if ($to_country != 'россия' && $to_country != 'российская федерация') {
                foreach ($countries as $country_id => $country) {
                    if ($to_country == mb_strtolower($country, 'utf-8')) {
                        $international = true;
                        $to_id = $country_id;
                    }
                }
            }
        }

        if (!$to_id && $delivery_data['to_city']) {
            $to_city = mb_strtolower($delivery_data['to_city'], 'utf-8');
            foreach ($cities as $city_id => $city) {
                if ($to_city == mb_strtolower($city, 'utf-8')) {
                    $to_id = $city_id;
                }
            }
        }

        if (!$international && !$from_id) {
            $this->last_error_code = self::ERROR_WRONG_SENDER;
            $this->last_error = NETCAT_MODULE_NETSHOP_DELIVERY_METHOD_SERVICE_INCORRECT_SENDER_ADDRESS;
            return null;
        }

        if (!$to_id) {
            $this->last_error_code = self::ERROR_WRONG_RECIPIENT;
            $this->last_error = NETCAT_MODULE_NETSHOP_DELIVERY_METHOD_SERVICE_INCORRECT_RECIPIENT_ADDRESS;
            return null;
        }

        $params = array(
            'from' => $from_id,
            'to' => $to_id,
            'weight' => sprintf("%0.3F", $weight),
        );

        if ($international) {
            $params['type'] = 'att';
        }

        $calculate_request = $this->_ems_api_request('ems.calculate', $params);

        if (!$calculate_request) {
            $this->last_error_code = self::ERROR_CANNOT_CONNECT_TO_GATE;
            $this->last_error = NETCAT_MODULE_NETSHOP_DELIVERY_METHOD_SERVICE_NO_REMOTE_SERVER_RESPONSE;
            return null;
        }

        $this->last_error = '';
        $this->last_error_code = self::ERROR_OK;

        return array(
            'price' => isset($calculate_request['price']) ? $calculate_request['price'] : 0,
            'currency' => 'RUB',
            'min_days' => isset($calculate_request['term']) ? $calculate_request['term']['min'] : null,
            'max_days' => isset($calculate_request['term']) ? $calculate_request['term']['max'] : null,
        );
    }

    /**
     * Возврат HTML кода сформированного
     * бланка посылки
     *
     * @return string
     */
    public function print_package_form() {
        $forms = nc_netshop::get_instance()->forms->get_objects();

        $delivery_data = $this->data;

        $international = false;

        if ($delivery_data['to_country']) {
            $to_country = mb_strtolower($delivery_data['to_country'], 'utf-8');
            if ($to_country != 'россия' && $to_country != 'российская федерация') {
                $international = true;
            }
        }

        $form = $international ? $forms->ems_package_international : $forms->ems_package_russia;

        ob_start();
        $form->template($delivery_data);
        return ob_get_clean();
    }

    /**
     * Возврат HTML кода сформированного
     * бланка наложенного платежа
     *
     * @return string
     */
    public function print_cash_on_delivery_form() {
        return $this->print_package_form();
    }

    /**
     * Возврат информации по точкам
     * следования посылки
     *
     * @return array|null
     */
    public function get_tracking_information() {
        $nc_core = nc_Core::get_object();

        $tracking_number = urlencode($this->data['tracking_number']);

        $result = $this->_http_request('http://www.emspost.ru/ru/tracking/?id=' . $tracking_number, null, "http://www.emspost.ru/ru/");

        $tracking = null;

        if ($result) {
            $result = $nc_core->utf8->utf2win($result);

            $tracking_results_start = strpos($result, '<div id="trackingResult"');
            $tracking_results = substr($result, $tracking_results_start);

            $table_start = strpos($tracking_results, '<table');
            $table_start = strpos($tracking_results, '<table', $table_start + 6);
            $table_start = strpos($tracking_results, '<table', $table_start + 6);

            $tracking_results = substr($tracking_results, $table_start);
            $tracking_results = substr($tracking_results,0, strpos($tracking_results, '/table>') + 7);

            $rows = explode('</tr>', $tracking_results);
            foreach($rows as $row) {
                $cols = explode('</td>', $row);
                foreach($cols as $index => $col) {
                    $cols[$index] = trim(strip_tags($col));
                }

                if (count($cols) == 5) {
                    if ($tracking === null) {
                        $tracking = array();
                    }

                    $tracking[] = array(
                        'time' => $cols[0],
                        'index' => $cols[1],
                        'index_description' => $cols[2],
                        'status' => $cols[3],
                    );
                }
            }
        }

        if ($tracking && $nc_core->NC_UNICODE) {
            $tracking = $nc_core->utf8->array_win2utf($tracking);
        }

        return $tracking;
    }

    /**
     * API запрос к EMS
     *
     * @param $method
     * @param array $params
     * @return null
     */
    private function _ems_api_request($method, $params = array()) {
        $result = null;

        $params['method'] = $method;
        $get = http_build_query($params, null, '&');

        $url = 'http://emspost.ru/api/rest/?' . $get;

        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

            $result = curl_exec($curl);
        } else {
            $result = @file_get_contents($url);
        }

        if ($result = @json_decode($result, true)) {
            if (isset($result['rsp'])) {
                $status = $result['rsp']['stat'];
                if ($status == 'ok') {
                    $this->last_error_code = self::ERROR_OK;
                    $this->last_error = '';

                    return $result['rsp'];
                } else {
                    $this->last_error_code = self::ERROR_GATE_ERROR;
                    $this->last_error = $result['rsp']['err']['msg'];
                }
            }
        } else {
            $this->last_error_code = self::ERROR_CANNOT_CONNECT_TO_GATE;
            $this->last_error = 'No response from the remote server';
        }

        return null;
    }
}