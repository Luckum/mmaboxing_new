<?php

class nc_netshop_delivery_service_russianpost extends nc_netshop_delivery_service {

    /**
     * Название службы
     *
     * @var string
     */
    protected $delivery_type_name = NETCAT_MODULE_NETSHOP_DELIVERY_RUSSIANPOST;

    /**
     * НДС
     *
     * @var float
     */
    protected $tax = 0.18;

    /**
     * Поля, которым нужны соответствия
     *
     * @var array
     */
    protected $mapped_fields = array(
        'to_zipcode' => NETCAT_MODULE_NETSHOP_DELIVERY_METHOD_SERVICE_TO_ZIP_CODE,
        'to_city' => NETCAT_MODULE_NETSHOP_DELIVERY_METHOD_SERVICE_TO_CITY,
    );

    private $countries = array(
        'АВСТРАЛИЯ' => 36,
        'АВСТРИЯ' => 40,
        'АЗЕРБАЙДЖАН' => 31,
        'АЛАНДСКИЕ ОСТРОВА' => 949,
        'АЛБАНИЯ' => 8,
        'АЛЖИР' => 12,
        'АНГИЛЬЯ' => 660,
        'АНГОЛА' => 24,
        'АНДОРРА' => 20,
        'АНТАРКТИКА' => 10,
        'АНТИГУА И БАРБУДА' => 28,
        'АРГЕНТИНА' => 32,
        'АРМЕНИЯ' => 51,
        'АРУБА' => 533,
        'АФГАНИСТАН' => 4,
        'БАГАМСКИЕ ОСТРОВА' => 44,
        'БАНГЛАДЕШ' => 50,
        'БАРБАДОС' => 52,
        'БАХРЕЙН' => 48,
        'БЕЛИЗ' => 84,
        'БЕЛОРУССИЯ' => 112,
        'БЕЛЬГИЯ' => 56,
        'БЕНИН' => 204,
        'БЕРМУДСКИЕ ОСТРОВА' => 60,
        'БОЛГАРИЯ' => 100,
        'БОЛИВИЯ' => 68,
        'БОСНИЯ И ГЕРЦЕГОВИНА' => 70,
        'БОТСВАНА' => 72,
        'БРАЗИЛИЯ' => 76,
        'БРИТАНСКИЕ ВИРГИНСКИЕ ОСТРОВА' => 92,
        'БРИТАНСКИЕ ТЕРРИТОРИИ В ИНДИЙСКОМ ОКЕАНЕ' => 86,
        'БРУНЕЙ' => 96,
        'БУВЕ ОСТРОВА' => 74,
        'БУРКИНА-ФАСО' => 854,
        'БУРУНДИ' => 108,
        'БУТАН' => 64,
        'ВАНУАТУ' => 548,
        'ВАТИКАН' => 336,
        'ВЕЛИКОБРИТАНИЯ' => 826,
        'ВЕНГРИЯ' => 348,
        'ВЕНЕСУЭЛА' => 862,
        'ВИРГИНСКИЕ ОСТРОВА (США)' => 850,
        'ВОСТОЧНОЕ САМОА' => 16,
        'ВОСТОЧНЫЙ ТИМОР' => 626,
        'ВЬЕТНАМ' => 704,
        'ГАБОН' => 266,
        'ГАИТИ' => 332,
        'ГАЙАНА' => 328,
        'ГАМБИЯ' => 270,
        'ГАНА' => 288,
        'ГВАДЕЛУПА' => 312,
        'ГВАТЕМАЛА' => 320,
        'ГВИНЕЯ' => 324,
        'ГВИНЕЯ-БИСАУ' => 624,
        'ГЕРМАНИЯ' => 276,
        'ГИБРАЛТАР' => 292,
        'ГОНДУРАС' => 340,
        'ГОНКОНГ' => 344,
        'ГРЕНАДА' => 308,
        'ГРЕНЛАНДИЯ' => 304,
        'ГРЕЦИЯ' => 300,
        'ГРУЗИЯ' => 268,
        'ГУАМ' => 316,
        'ДАНИЯ' => 208,
        'ДЕМОКРАТИЧЕСКАЯ РЕСПУБЛИКА КОНГО' => 180,
        'ДЖИБУТИ' => 262,
        'ДОМИНИКА' => 212,
        'ДОМИНИКАНСКАЯ РЕСПУБЛИКА' => 214,
        'ЕГИПЕТ' => 818,
        'ЗАМБИЯ' => 894,
        'ЗАПАДНАЯ САХАРА' => 732,
        'ЗАПАДНОЕ САМОА' => 882,
        'ЗИМБАБВЕ' => 716,
        'ИЗРАИЛЬ' => 376,
        'ИНДИЯ' => 356,
        'ИНДОНЕЗИЯ' => 360,
        'ИОРДАНИЯ' => 400,
        'ИРАК' => 368,
        'ИРАН' => 364,
        'ИРЛАНДИЯ' => 372,
        'ИСЛАНДИЯ' => 352,
        'ИСПАНИЯ' => 724,
        'ИТАЛИЯ' => 380,
        'ЙЕМЕН' => 887,
        'КАБО-ВЕРДЕ' => 132,
        'КАЗАХСТАН' => 398,
        'КАЙМАНОВЫ ОСТРОВА' => 136,
        'КАМБОДЖА' => 116,
        'КАМЕРУН' => 120,
        'КАНАДА' => 124,
        'КАТАР' => 634,
        'КЕНИЯ' => 404,
        'КИПР' => 196,
        'КИРГИЗИЯ' => 417,
        'КИРИБАТИ' => 296,
        'КИТАЙ' => 156,
        'КНДР' => 408,
        'КОКОС ОСТРОВА' => 166,
        'КОЛУМБИЯ' => 170,
        'КОМОРСКИЕ ОСТРОВА' => 174,
        'КОРЕЯ' => 410,
        'КОСТА-РИКА' => 188,
        'КОТ-Д"ИВУАР' => 384,
        'КУБА' => 192,
        'КУВЕЙТ' => 414,
        'КУКА ОСТРОВА' => 184,
        'ЛАОС' => 418,
        'ЛАТВИЯ' => 428,
        'ЛЕСОТО' => 426,
        'ЛИБЕРИЯ' => 430,
        'ЛИВАН' => 422,
        'ЛИВИЯ' => 434,
        'ЛИТВА' => 440,
        'ЛИХТЕНШТЕЙН' => 438,
        'ЛЮКСЕМБУРГ' => 442,
        'МАВРИКИЙ' => 480,
        'МАВРИТАНИЯ' => 478,
        'МАДАГАСКАР' => 450,
        'МАЙОТТ ОСТРОВ' => 175,
        'МАКАО' => 446,
        'МАКЕДОНИЯ' => 807,
        'МАЛАВИ' => 454,
        'МАЛАЙЗИЯ' => 458,
        'МАЛИ' => 466,
        'МАЛЬДИВСКИЕ ОСТРОВА' => 462,
        'МАЛЬТА' => 470,
        'МАРОККО' => 504,
        'МАРТИНИКА' => 474,
        'МАРШАЛЛОВЫ ОСТРОВА' => 584,
        'МЕКСИКА' => 484,
        'МИКРОНЕЗИЯ' => 583,
        'МОЗАМБИК' => 508,
        'МОЛДАВИЯ' => 498,
        'МОНАКО' => 492,
        'МОНГОЛИЯ' => 496,
        'МОНТСЕРРАТ' => 500,
        'МЬЯНМА' => 104,
        'НАМИБИЯ' => 516,
        'НАУРУ' => 520,
        'НЕПАЛ' => 524,
        'НИГЕР' => 562,
        'НИГЕРИЯ' => 566,
        'НИДЕРЛАНДСКИЕ АНТИЛЫ' => 530,
        'НИДЕРЛАНДЫ' => 528,
        'НИКАРАГУА' => 558,
        'НИУЭ' => 570,
        'НОВАЯ ЗЕЛАНДИЯ' => 554,
        'НОВАЯ КАЛЕДОНИЯ' => 540,
        'НОРВЕГИЯ' => 578,
        'НОРФОЛК ОСТРОВ' => 574,
        'ОБЪЕДИНЕННЫЕ АРАБСКИЕ ЭМИРАТЫ' => 784,
        'ОМАН' => 512,
        'ПАКИСТАН' => 586,
        'ПАЛАУ' => 585,
        'ПАНАМА' => 591,
        'ПАПУА НОВАЯ ГВИНЕЯ' => 598,
        'ПАРАГВАЙ' => 600,
        'ПЕРУ' => 604,
        'ПИТКЕРН' => 612,
        'ПОДОПЕЧНЫЕ ТЕРРИТОРИИ США В ТИХОМ ОКЕАНЕ' => 581,
        'ПОЛЬША' => 616,
        'ПОРТУГАЛИЯ' => 620,
        'ПУЭРТО-РИКО' => 630,
        'РЕСПУБЛИКА КОНГО' => 178,
        'РЕЮНЬОН' => 638,
        'РОЖДЕСТВА ОСТРОВ' => 162,
        'РУАНДА' => 646,
        'РУМЫНИЯ' => 642,
        'САЛЬВАДОР' => 222,
        'САН-МАРИНО' => 674,
        'САН-ТОМЕ И ПРИНСИПИ' => 678,
        'САУДОВСКАЯ АРАВИЯ' => 682,
        'СВАЗИЛЕНД' => 748,
        'СВЯТОЙ ЕЛЕНЫ ОСТРОВ' => 654,
        'СЕВЕРНЫЕ МАРИАНСКИЕ ОСТРОВА' => 580,
        'СЕЙШЕЛЬСКИЕ ОСТРОВА' => 690,
        'СЕН-ПЬЕР И МИКЕЛОН' => 666,
        'СЕНЕГАЛ' => 686,
        'СЕНТ-ВИНСЕНТ И ГРЕНАДИНЫ' => 670,
        'СЕНТ-КИТТС И НЕВИС' => 659,
        'СЕНТ-ЛЮСИЯ' => 662,
        'СЕРБИЯ' => 688,
        'СЕРБИЯ И ЧЕРНОГОРИЯ' => 950,
        'СИНГАПУР' => 702,
        'СИРИЯ' => 760,
        'СЛОВАКИЯ' => 703,
        'СЛОВЕНИЯ' => 705,
        'СОЛОМОНОВЫ ОСТРОВА' => 90,
        'СОМАЛИ' => 706,
        'СУДАН' => 736,
        'СУРИНАМ' => 740,
        'США' => 840,
        'СЬЕРРА-ЛЕОНЕ' => 694,
        'ТАДЖИКИСТАН' => 762,
        'ТАИЛАНД' => 764,
        'ТАЙВАНЬ' => 158,
        'ТАНЗАНИЯ' => 834,
        'ТЕРКС И КАЙКОС ОСТРОВА' => 796,
        'ТОГО' => 768,
        'ТОКЕЛАУ' => 772,
        'ТОНГА' => 776,
        'ТРИНИДАД И ТОБАГО' => 780,
        'ТУВАЛУ' => 798,
        'ТУНИС' => 788,
        'ТУРКМЕНИСТАН' => 795,
        'ТУРЦИЯ' => 792,
        'УГАНДА' => 800,
        'УЗБЕКИСТАН' => 860,
        'УКРАИНА' => 804,
        'УОЛЛEС И ФУТУНА ОСТРОВА' => 876,
        'УРУГВАЙ' => 858,
        'ФАРЕРСКИЕ ОСТРОВА' => 234,
        'ФИДЖИ' => 242,
        'ФИЛИППИНЫ' => 608,
        'ФИНЛЯНДИЯ' => 246,
        'ФОЛКЛЕНДСКИЕ (МАЛЬВИНСКИЕ) ОСТРОВА' => 238,
        'ФРАНЦИЯ' => 250,
        'ФРАНЦИЯ, МЕТРОПОЛИЯ' => 249,
        'ФРАНЦУЗСКАЯ ГВИАНА' => 254,
        'ФРАНЦУЗСКАЯ ПОЛИНЕЗИЯ' => 258,
        'ХЕРД И МАКДОНАЛЬД ОСТРОВА' => 334,
        'ХОРВАТИЯ' => 191,
        'ЦЕНТРАЛЬНО-АФРИКАНСКАЯ РЕСПУБЛИКА' => 140,
        'ЧАД' => 148,
        'ЧЕРНОГОРИЯ' => 499,
        'ЧЕХИЯ' => 203,
        'ЧИЛИ' => 152,
        'ШВЕЙЦАРИЯ' => 756,
        'ШВЕЦИЯ' => 752,
        'ШПИЦБЕРГЕН И ЯН-МАЙЕН' => 744,
        'ШРИ ЛАНКА' => 144,
        'ЭКВАДОР' => 218,
        'ЭКВАТОРИАЛЬНАЯ ГВИНЕЯ' => 226,
        'ЭРИТРЕЯ' => 232,
        'ЭСТОНИЯ' => 233,
        'ЭФИОПИЯ' => 231,
        'ЮГОСЛАВИЯ' => 891,
        'ЮЖНАЯ ДЖОРДЖИЯ И ЮЖНЫЕ САНДВИЧЕВЫ ОСТРОВА' => 239,
        'ЮЖНАЯ ОСЕТИЯ' => 896,
        'ЮЖНО-АФРИКАНСКАЯ РЕСПУБЛИКА' => 710,
        'ЮЖНЫЕ ФРАНЦУЗСКИЕ ТЕРРИТОРИИ' => 260,
        'ЯМАЙКА' => 388,
        'ЯПОНИЯ' => 392,
    );

    /**
     * Рассчитать стоимость посылки.
     * При успешном выполнении возвращается массив:
     * array(
     *     'price' => стоимость доставки,
     *     'currency' => 'RUB',
     *     'min_days' => null (неизвестно, это же Почта России)
     *     'max_days' => null (неизвестно, это же Почта России)
     * )
     *
     * При ошибке возвращается null
     *
     * @return array|null
     */
    public function calculate_delivery() {
        $nc_core = nc_Core::get_object();
        $db = $nc_core->db;

        $delivery_data = $this->data;

        $weight = ceil($delivery_data['weight']); // вес в граммах

        if ($weight <= 0) {
            $this->last_error = NETCAT_MODULE_NETSHOP_DELIVERY_METHOD_SERVICE_INCORRECT_WEIGHT;
            $this->last_error_code = self::ERROR_WRONG_WEIGHT;
            return null;
        }

        $valuation = $delivery_data['valuation'] ? ceil($delivery_data['valuation']) : 0;

        $international = false;
        if ($delivery_data['to_country']) {
            $to_country = mb_strtolower($delivery_data['to_country'], 'utf-8');
            if ($to_country != 'россия' && $to_country != 'российская федерация') {
                $international = true;
            }
        }

        $to_index = null;
        $to_country_code = null;

        if ($international) {
            if ($delivery_data['to_country']) {
                $to_country = mb_strtolower($delivery_data['to_country'], 'utf-8');
                foreach ($this->countries as $country => $id) {
                    $country = mb_strtolower($country, 'utf-8');
                    if ($country == $to_country) {
                        $to_country_code = $id;
                        break;
                    }
                }
            }

            $to_index = 0;
        } else {
            $to_country_code = 643;
            //если есть индекс
            if ($delivery_data['to_zipcode']) {
                $zipcode = $db->escape($delivery_data['to_zipcode']);
                $sql = "SELECT `Index` FROM `Russianpost_Indexes` WHERE `Index` = '{$zipcode}' LIMIT 1";
                $index = $db->get_var($sql);
                if ($index) {
                    $to_index = $index;
                }
            }

            //если есть название города
            if (!$to_index && $delivery_data['to_city']) {
                $city = $db->escape($delivery_data['to_city']);
                $sql = "SELECT `Index` FROM `Russianpost_Indexes` WHERE " .
                    "`Opsname` = '{$city}' OR `City` = '{$city}' " .
                    "ORDER BY `Index` ASC LIMIT 1";
                $index = $db->get_var($sql);
                if ($index) {
                    $to_index = $index;
                }
            }

            //если есть название региона
            if (!$to_index && $delivery_data['to_region']) {
                $region = $db->escape($delivery_data['to_region']);
                $sql = "SELECT `Index` FROM `Russianpost_Indexes` WHERE " .
                    "`Region` = '{$region}' " .
                    "ORDER BY `Index` ASC LIMIT 1";
                $index = $db->get_var($sql);
                if ($index) {
                    $to_index = $index;
                }
            }
        }

        if (($international && !$to_country_code) || (!$international && !$to_index)) {
            $this->last_error_code = self::ERROR_WRONG_RECIPIENT;
            $this->last_error = NETCAT_MODULE_NETSHOP_DELIVERY_METHOD_SERVICE_INCORRECT_RECIPIENT_ADDRESS;
            return null;
        }

        $params = array(
            'viewPost' => 23,
            'typePost' => 1,
            'countryCode' => $to_country_code,
            'weight' => $weight,
            'value1' => $valuation,
            'postOfficeId' => $to_index,
        );

        $get = http_build_query($params, null, '&');

        $url = 'http://www.russianpost.ru/autotarif/Autotarif.aspx?' . $get;

        $result = $this->_http_request($url);

        if (!$result) {
            $this->last_error_code = self::ERROR_CANNOT_CONNECT_TO_GATE;
            $this->last_error = NETCAT_MODULE_NETSHOP_DELIVERY_METHOD_SERVICE_NO_REMOTE_SERVER_RESPONSE;
            return null;
        }

        if (preg_match('#<span id="TarifValue">(.+?)</span>#i', $result, $match)) {
            $price = str_replace(',', '.', $match[1]);
            $price = round($price * (1 + $this->tax), 2);
            return array(
                'price' => $price,
                'currency' => 'RUB',
                'min_days' => null,
                'max_days' => null,
            );
        } else if (preg_match('#name="key" value="([^"]+)"#i', $result, $match)) {
            $key = $match[1];

            $this->_http_request($url, array('key' => $key));
            $result = $this->_http_request($url);

            if (preg_match('#<span id="TarifValue">(.+?)</span>#i', $result, $match)) {
                $price = str_replace(',', '.', $match[1]);
                $price = round($price * (1 + $this->tax), 2);
                return array(
                    'price' => $price,
                    'currency' => 'RUB',
                    'min_days' => null,
                    'max_days' => null,
                );
            } else {
                $this->last_error_code = self::ERROR_GATE_ERROR;
                $this->last_error = NETCAT_MODULE_NETSHOP_DELIVERY_METHOD_SERVICE_INCORRECT_REMOTE_SERVER_RESPONSE;
                return null;
            }
        } else {
            $this->last_error_code = self::ERROR_GATE_ERROR;
            $this->last_error = NETCAT_MODULE_NETSHOP_DELIVERY_METHOD_SERVICE_INCORRECT_REMOTE_SERVER_RESPONSE;
            return null;
        }

        return null;
    }

    /**
     * Возврат HTML кода сформированного
     * бланка посылки
     *
     * @return string
     */
    public function print_package_form() {
        $forms = nc_netshop::get_instance()->forms->get_objects();

        ob_start();
        $forms->russianpost_package->template($this->data);
        return ob_get_clean();
    }

    /**
     * Возврат HTML кода сформированного
     * бланка наложенного платежа
     *
     * @return string
     */
    public function print_cash_on_delivery_form() {
        $forms = nc_netshop::get_instance()->forms->get_objects();

        ob_start();
        $forms->russianpost_cash_on_delivery->template($this->data);
        return ob_get_clean();
    }

    /**
     * Возврат информации по точкам
     * следования посылки
     *
     * @return array|null
     */
    public function get_tracking_information() {
        return null;
    }
}