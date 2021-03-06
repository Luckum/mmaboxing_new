<?php

/**
 * Accessed via __get():
 * @property nc_netshop_settings $settings
 * @property nc_netshop_cart $cart
 * @property nc_netshop_filter $filter
 * @property nc_netshop_delivery $delivery
 * @property nc_netshop_payment $payment
 * @property nc_netshop_promotion $promotion
 * @property nc_netshop_forms $forms
 * @property nc_netshop_mailer $mailer
 * @property nc_netshop_statistics $statistics
 * @property nc_netshop_yandex $yandex
 * @property nc_netshop_goodslist_recent $goodslist_recent
 * @property nc_netshop_goodslist_favorite $goodslist_favorite
 * @property nc_netshop_goodslist_compare $goodslist_compare
 */
class nc_netshop {

    /** @var array  (значение должно быть true) */
    protected $sub_modules = array(
        'settings' => true,
        'cart' => true,
        'promotion' => true,
        'filter' => true,
        'forms' => true,
        'delivery' => true,
        'payment' => true,
        'mailer' => true,
        'goodslist_recent' => true,
        'goodslist_favorite' => true,
        'goodslist_compare' => true,
        'statistics' => true,
        'yandex' => true,
    );

    /** @var  int */
    protected $catalogue_id;

    /** @var nc_netshop_condition_context */
    protected $condition_context;

    /** @var nc_netshop_record_conditional_collection */
    protected $price_rules;

    /**
     * @param int|null $catalogue_id   Если null, возвращает объект nc_netshop для текущего сайта
     * @return nc_netshop
     */
    public static function get_instance($catalogue_id = null) {
        static $instances = array();
        $catalogue_id = (int)$catalogue_id;

        if (!$catalogue_id) {
            $catalogue_id = nc_core('catalogue')->get_current('Catalogue_ID');
        }

        if (!isset($instances[$catalogue_id])) {
            $instances[$catalogue_id] = new self($catalogue_id);
        }

        return $instances[$catalogue_id];
    }

    /**
     * Используйте nc_netshop::get_instance()
     */
    protected function __construct($catalogue_id) {
        $this->catalogue_id = $catalogue_id;
    }

    /**
     * Обеспечивает загрузку «подмодулей» по запросу
     * @param $sub_module_name
     * @return null|object
     */
    public function __get($sub_module_name) {
        if (!isset($this->sub_modules[$sub_module_name])) {
            return null;
        }

        if ($this->sub_modules[$sub_module_name] === true) {
            $class_name = "nc_netshop_" . $sub_module_name;
            $this->sub_modules[$sub_module_name] = new $class_name($this);
        }

        return $this->sub_modules[$sub_module_name];
    }

    /**
     * Настройки магазина для текущего сайта.
     * $netshop->get_setting('CurrencyDetails')
     * $netshop->get_setting('CurrencyDetails', 1)
     *
     * @internal param string $setting_key, Имя параметра
     *
     * @return mixed
     */
    public function get_setting() {
        $args = func_get_args();
        switch (count($args)) {
            case 1: return $this->settings->get($args[0]);
            case 2: return $this->settings->get($args[0], $args[1]);
            case 3: return $this->settings->get($args[0], $args[1], $args[2]);
            case 4: return $this->settings->get($args[0], $args[1], $args[2], $args[3]);
            default: return null;
        }
    }

    /**
     * Возвращает название колонки цен для товара исходя из имеющихся
     * правил выбора цен
     *
     * @param nc_netshop_item $item
     * @return string
     */
    public function get_price_column(nc_netshop_item $item) {
        $rules = $this->get_all_price_rules()
                      ->matching($this->get_condition_context(), $item);

        // Если имеется несколько вариантов, возвращается название колонки цен,
        // которая содержит минимальную среди всех вариантов цену для товара.
        // Возвращаются только названия колонок, которые существуют у товара $item
        // и значения которых у товара не равно null
        // (однако цена может быть равна нулю)
        $min_price = INF;
        $min_price_column = 'Price';

        /** @var $rule nc_netshop_pricerule */
        foreach ($rules as $rule) {
            $price_column = $rule->get('price_column');
            $price = $item[$price_column];
            $currency_column = $this->get_currency_column($price_column);
            if (is_numeric($price)) {
                // пересчёт цены в основную валюту
                $price = $this->convert_currency($price, $item[$currency_column]);
                if ($price < $min_price) {
                    $min_price = $price;
                    $min_price_column = $price_column;
                }
            }
        }

        return $min_price_column;
    }

    /**
     * Возвращает название колонки, соответствующей колонке с ценой
     * E.g. PriceMinimum → CurrencyMinimum
     * @param $price_column
     * @return mixed
     */
    public function get_currency_column($price_column) {
        static $cache = array();
        if (!isset($cache[$price_column])) {
            $cache[$price_column] = preg_replace("/^Price/", "Currency", $price_column);
        }
        return $cache[$price_column];
    }

    /**
     * @return nc_netshop_record_conditional_collection
     */
    protected function get_all_price_rules() {
        if (!isset($this->price_rules)) {
            $this->price_rules = nc_record_collection::load(
                "nc_netshop_pricerule",
                "SELECT * FROM `%t%` WHERE `Catalogue_ID` = " . (int)$this->get_catalogue_id() . " AND `Checked` = 1"
            );
        }
        return $this->price_rules;
    }

    /**
     * @return int
     */
    public function get_catalogue_id() {
        return $this->catalogue_id;
    }

    /**
     *
     * @param $currency_code_or_id
     * @return int
     */
    public function get_currency_id($currency_code_or_id) {
        if (!$currency_code_or_id) {
            return (int)$this->get_setting('DefaultCurrencyID');
        }

        $currency_codes = $this->get_setting('Currencies');

        // (a) it looks like an ID
        if (is_numeric($currency_code_or_id)) {
            if (isset($currency_codes[$currency_code_or_id])) {
                return (int)$currency_code_or_id;
            } else {
                trigger_error("Unknown currency ID: $currency_code_or_id", E_USER_WARNING);
                return false;
            }
        }

        // (b) it looks like a code
        $currency_code_or_id = strtoupper($currency_code_or_id);

        // treat RUB and RUR as the same currency code
        if ($currency_code_or_id == 'RUR' && in_array('RUB', $currency_codes)) {
            $currency_code_or_id = 'RUB';
        } elseif ($currency_code_or_id == 'RUB' && in_array('RUR', $currency_codes)) {
            $currency_code_or_id = 'RUR';
        }

        // get ID
        $currency_id = array_search($currency_code_or_id, $currency_codes);
        if ($currency_id === false) {
            trigger_error("Unknown currency code: " . htmlspecialchars($currency_code_or_id), E_USER_WARNING);
            return (int)$this->get_setting('DefaultCurrencyID');
        } else {
            return (int)$currency_id;
        }
    }

    /**
     *
     * @param $currency_id
     * @return string
     */
    public function get_currency_code($currency_id = null) {
        if (!$currency_id) { $currency_id = $this->get_setting('DefaultCurrencyID'); }
        return $this->get_setting('Currencies', $currency_id);
    }

    /**
     * @return nc_netshop_condition_context
     */
    public function get_condition_context() {
        if (!$this->condition_context) {
            $this->condition_context = new nc_netshop_condition_context($this->get_catalogue_id());
            $this->condition_context->set_user_id($GLOBALS['AUTH_USER_ID']);
            $this->condition_context->set_cart_contents($this->cart->get_items());
            $this->promotion->set_condition_context_data($this->condition_context);
        }
        return $this->condition_context;
    }

    /**
     *
     */
    public function set_order_in_condition_context(nc_netshop_order $order) {
        $this->get_condition_context()->set_order($order);
    }

    /**
     * Конвертация валют
     *
     * @param float $price
     * @param int|string $from_currency
     * @param int|string|null $to_currency   Если не указана, то конвертирует в базовую валюту
     * @return float
     */
    public function convert_currency($price, $from_currency, $to_currency = null) {
        $from_currency_id = $this->get_currency_id($from_currency);
        $to_currency_id = $this->get_currency_id($to_currency);

        if (!$price || $from_currency_id == $to_currency_id || !$this->get_setting('Rates', $from_currency_id)) {
            return $price;
        }

        $rates = (array)$this->get_setting('Rates');

        if (!isset($rates[$to_currency_id])) {
            $rates[$to_currency_id] = 1;
        }

        $price = $price * ($rates[$from_currency_id] / $rates[$to_currency_id]);

        if ($conversion_percent = (int)$this->get_setting('CurrencyConversionPercent')) {
            $price = $price * (100 + $conversion_percent) / 100;
        }

        // округлить до знака, указанного в настройках
        return $this->round_price($price);
    }

    /**
     * Форматирование денежной суммы в соответствии с настройками валюты
     *
     * @param int|float $price           Деньги
     * @param int|string|null $currency  ID или код валюты. Если null, то валюта по умолчанию
     * @param bool $no_nbsp              Если true, пробелы не заменяются на &nbsp;
     * @param bool $no_currency_name     Если true, название валюты не добавляется
     * @return string
     */
    public function format_price($price, $currency = null, $no_nbsp = false, $no_currency_name = false) {
        $currency_id = $this->get_currency_id($currency);
        $params = $this->get_setting('CurrencyDetails', $currency_id);

        $currency_name = '';

        if ($params) {
            $currency_name = $params["NameShort"];
        }

        if ($params["ThousandSep"] == '[space]') {
            $params["ThousandSep"] = ' ';
        }

        $decimals = strlen($params["Decimals"]) ? $params["Decimals"] : NETCAT_MODULE_NETSHOP_CURRENCY_DECIMALS;
        $dec_point = $params["DecPoint"] ? $params["DecPoint"] : NETCAT_MODULE_NETSHOP_CURRENCY_DEC_POINT;
        $thousand_sep = $params["ThousandSep"] ? $params["ThousandSep"] : NETCAT_MODULE_NETSHOP_CURRENCY_THOUSAND_SEP;
        $format = $params["Format"] ? $params["Format"] : NETCAT_MODULE_NETSHOP_CURRENCY_FORMAT;

        $result = number_format($price, $decimals, chr(1), chr(2));
        $result = strtr($result, array(chr(1) => $dec_point, chr(2) => $thousand_sep));

        if (!$no_nbsp) {
            $format = str_replace(" ", "&nbsp;", $format);
            $result = str_replace(" ", "&nbsp;", $result);
        }

        if (!$no_currency_name) {
            $result = sprintf(str_replace("#", $currency_name, $format), $result);
        }

        return $result;
    }

    /**
     * Округлить до знака, как указано в настройках валюты (если не указано - до 2 знаков после зпт)
     *
     * @param float $price Округляемое значение
     * @param int|string|null $currency     Если null, то валюта по умолчанию
     *
     * @return float
     */
    public function round_price($price, $currency = null) {
        $currency_id = $this->get_currency_id($currency);
        if (!$currency_id) {
            $currency_id = $this->get_setting('DefaultCurrencyID');
        }

        $decimals = $this->get_setting('CurrencyDetails', $currency_id, 'Decimals');
        if (!$decimals) {
            $decimals = 2;
        }

        return round($price, $decimals);
    }

    /**
     * Получить указанные поля для компонентов товаров
     * @param string $fields
     * @return array
     */
    public function get_goods_components_data($fields = "1") {
        // components which have 'Price', 'Currency', 'Name', 'ItemID', 'ImportSourceID' columns (~duck typing)
        // @todo ??? decide if we should use GOODS_TABLE instead
        $result = nc_db()->get_results(
            "SELECT c.`Class_ID`, $fields
               FROM `Class` AS c
                     JOIN `Field` AS f1 USING (`Class_ID`)
                     JOIN `Field` AS f2 USING (`Class_ID`)
                     JOIN `Field` AS f3 USING (`Class_ID`)
                     JOIN `Field` AS f4 USING (`Class_ID`)
                     JOIN `Field` AS f5 USING (`Class_ID`)
              WHERE f1.`Field_Name` = 'Price'
                AND f2.`Field_Name` = 'Currency'
                AND f3.`Field_Name` = 'Name'
                AND f4.`Field_Name` = 'ItemID'
                AND f5.`Field_Name` = 'ImportSourceID'",
            ARRAY_A, 'Class_ID');

        return $result;
    }

    /**
     * @return int[]
     */
    public function get_goods_components_ids() {
        // @todo ??? decide if we should use GOODS_TABLE instead
        // (there were similar methods: NetShop::get_goods_table(), NetShop->GuessGoodsTypeIDs())
        static $ids;
        return ($ids ? $ids : $ids = array_keys($this->get_goods_components_data()));
    }

    /**
     * @return string
     */
    public function get_order_table_name() {
        return "Message" . (int)$this->get_setting('OrderComponentID');
    }

    /**
     * @return string
     */
    protected function get_previous_orders_status_ids() {
        static $prev_orders_status_ids;

        if ($prev_orders_status_ids === null) {
            // PREV_ORDERS_SUM_STATUS_ID должен быть в числом или строкой в формате "1,2,3"
            $prev_orders_status_ids = $this->get_setting('PrevOrdersSumStatusID');

            if (!$prev_orders_status_ids || !preg_match('/^\s*\d+(?:\s*,\s*\d+)*\s*$/', $prev_orders_status_ids)) {
                trigger_error(NETCAT_MODULE_NETSHOP_NO_PREV_ORDERS_STATUS_ID, E_USER_WARNING);
                $prev_orders_status_ids = false;
            }
        }

        return $prev_orders_status_ids;
    }

    /**
     * @param int|null $user_id
     * @param int|null $from_date_timestamp
     * @param int|null $to_date_timestamp
     * @return int|float
     */
    public function get_previous_orders_sum($user_id = null, $from_date_timestamp = null, $to_date_timestamp = null) {
        $user_id = (int)$user_id;
        if (!$user_id) {
            $user_id = (int)$GLOBALS['AUTH_USER_ID'];
        }
        if (!$user_id) {
            return 0;
        }

        // cache results
        static $prev_orders_sum = array();
        $cache_key = $user_id . ":" . (int)$from_date_timestamp . ":" . (int)$to_date_timestamp;
        if (isset($prev_orders_sum[$cache_key])) {
            return $prev_orders_sum[$cache_key];
        }

        $prev_orders_status_ids = $this->get_previous_orders_status_ids();
        if (!$prev_orders_status_ids) {
            return 0;
        }

        $query_date = "";
        if ($from_date_timestamp !== null) {
            $query_date .= " AND m.Created >= '" . date("Y-m-d H:i:s", $from_date_timestamp) . "'";
        }
        if ($to_date_timestamp !== null) {
            $query_date .= " AND m.Created <= '" . date("Y-m-d H:i:s", $to_date_timestamp) . "'";
        }

        $order_table = $this->get_order_table_name();
        $db = nc_db();
        $sum = $db->get_var("SELECT SUM(o.`ItemPrice` * o.`Qty`)
                               FROM `Netshop_OrderGoods` AS o,
                                    `$order_table` AS m
                              WHERE m.`User_ID` = $user_id
                                AND m.`Status` IN ($prev_orders_status_ids)
                                AND m.`Message_ID` = o.`Order_ID`" .
            $query_date);

        // also consider cart discounts
        $cart_discounts = $db->get_var("SELECT SUM(d.`Discount_Sum`)
                                          FROM `$order_table` AS m,
                                               `Netshop_OrderDiscounts` AS d
                                         WHERE m.`User_ID` = $user_id
                                           AND m.`Status` IN ($prev_orders_status_ids)
                                           AND m.`Message_ID` = d.`Order_ID`
                                           AND d.`Item_Type` = 0" .
                                           $query_date);

        $prev_orders_sum[$cache_key] = $sum - $cart_discounts;

        return $prev_orders_sum[$cache_key];
    }

    /**
     * @param int|null $user_id
     * @param int|null $from_date_timestamp
     * @param int|null $to_date_timestamp
     * @return int
     */
    public function get_previous_orders_count($user_id = null, $from_date_timestamp = null, $to_date_timestamp = null) {
        $user_id = (int)$user_id;
        if (!$user_id) {
            $user_id = (int)$GLOBALS['AUTH_USER_ID'];
        }
        if (!$user_id) {
            return 0;
        }

        static $prev_orders_count = array();
        $cache_key = $user_id . ":" . (int)$from_date_timestamp . ":" . (int)$to_date_timestamp;
        if (isset($prev_orders_count[$cache_key])) {
            return $prev_orders_count[$cache_key];
        }

        $prev_orders_status_ids = $this->get_previous_orders_status_ids();
        if (!$prev_orders_status_ids) {
            return 0;
        }

        $query_date = "";
        if ($from_date_timestamp !== null) {
            $query_date .= " AND `Created` >= '" . date("Y-m-d H:i:s", $from_date_timestamp) . "'";
        }
        if ($to_date_timestamp !== null) {
            $query_date .= " AND `Created` <= '" . date("Y-m-d H:i:s", $to_date_timestamp) . "'";
        }

        $order_table = $this->get_order_table_name();
        $prev_orders_count[$cache_key] = nc_db()->get_var(
            "SELECT COUNT(*)
               FROM `$order_table`
              WHERE `User_ID` = $user_id
                AND `Status` IN ($prev_orders_status_ids)" .
                $query_date
        );

        return $prev_orders_count[$cache_key];
    }

    /**
     * @param int $user_id
     * @param int $component_id
     * @param int|null $item_id   If null, search for any item of the specified component
     * @return bool
     */
    public function previous_orders_had_item($user_id, $component_id, $item_id = null) {
        $user_id = (int)$user_id;
        if (!$user_id) {
            $user_id = (int)$GLOBALS['AUTH_USER_ID'];
        }
        if (!$user_id) {
            return false;
        }

        $component_id = (int)$component_id;
        $item_id = (int)$item_id;

        static $result;
        $cache_key = "$user_id:$component_id:$item_id";
        if (isset($result[$cache_key])) {
            return $result[$cache_key];
        }

        $prev_orders_status_ids = $this->get_previous_orders_status_ids();
        if (!$prev_orders_status_ids) {
            return false;
        }

        $item_id_condition = "";
        if ($item_id) {
            $children = nc_db()->get_col("SELECT `Message_ID` FROM `Message$component_id` WHERE `Parent_Message_ID` = $item_id");
            $item_id_condition = " AND o.`Item_ID` IN (" . $item_id .
                                 ($children ? "," . join(",", $children) : "") .
                                 ") ";
        }

        $order_table = $this->get_order_table_name();
        $result[$cache_key] = (1 == nc_db()->get_var(
            "SELECT 1 AS result
               FROM `$order_table` AS m, `Netshop_OrderGoods` AS o
              WHERE m.`User_ID` = $user_id
                AND m.`Status` IN ($prev_orders_status_ids)
                AND m.`Message_ID` = o.`Order_ID`
                AND o.`Item_Type` = $component_id
                    $item_id_condition
              LIMIT 1"
        ));

        return $result[$cache_key];
    }

    /**
     * Загрузить заказ с указанным идентификатором
     *
     * @param $order_id
     * @return nc_netshop_order|null
     */
    public function load_order($order_id) {
        try {
            $order = new nc_netshop_order();
            $order->set_catalogue_id($this->catalogue_id);
            $order->load($order_id);
            return $order;
        }
        catch (nc_record_exception $e) {
            return null;
        }
    }

    /**
     * Shortcut for $this->cart->get_items()
     * @return nc_netshop_item_collection
     */
    public function get_cart_contents() {
        return $this->cart->get_items();
    }

    /**
     *
     */
    public function get_add_to_cart_url() {
        return nc_module_path('netshop') . "actions/cart.php";
    }

    /**
     *
     */
    public function get_accept_special_offer_url(nc_netshop_item $item, $redirect_to_item_page) {
        return nc_module_path('netshop') . "actions/special_offer.php?action=accept" .
                "&class_id=$item[Class_ID]&item_id=$item[Message_ID]" .
                ($redirect_to_item_page ? "&redirect_url=" . urlencode($item["URL"]) : "");
    }

    /**
     *
     */
    public function get_reject_special_offer_url(nc_netshop_item $item, $redirect_to_item_page) {
        return nc_module_path('netshop') . "actions/special_offer.php?action=reject" .
               "&class_id=$item[Class_ID]&item_id=$item[Message_ID]" .
               ($redirect_to_item_page ? "&redirect_url=" . urlencode($item["URL"]) : "");
    }

    /**
     * Возвращает URL для оформления заказа
     *
     * @return string
     */
    public function get_add_order_url() {
        static $cache = array();

        if (!isset($cache[$this->catalogue_id])) {
            $catalogue_id = (int)$this->catalogue_id;
            $order_component_id = (int)$this->get_setting('OrderComponentID');
            $nc_core = nc_core::get_object();

            $sql = "SELECT sc.`Sub_Class_ID`, s.`Hidden_URL`, sc.`EnglishName`
                      FROM `Subdivision` AS s
                            LEFT JOIN `Sub_Class` AS sc
                            ON s.`Subdivision_ID` = sc.`Subdivision_ID`
                      WHERE sc.`Class_ID` = {$order_component_id}
                        AND s.`Catalogue_ID` = {$catalogue_id}
                      LIMIT 1";

            $row = $nc_core->db->get_row($sql, ARRAY_A);

            if ($row) {
                if (nc_module_check_by_keyword('routing')) {
                    $cache[$this->catalogue_id] = nc_routing::get_infoblock_path($row['Sub_Class_ID'], 'add');
                }
                else {
                    $cache[$this->catalogue_id] = $nc_core->SUB_FOLDER . $row['Hidden_URL'] . 'add_' . $row['EnglishName'] . '.html';
                }
            }
            else {
                $cache[$this->catalogue_id] = '';
            }
        }

        return $cache[$this->catalogue_id];
    }

    /**
     * Проверка нового заказа перед оформлением.
     * В случае невозможности оформления заказа возвращает массив с сообщениями
     * о найденных ошибках.
     * Если с заказом всё нормально — возвращает пустой массив.
     *
     * @param nc_netshop_order $order
     * @return array
     */
    public function check_new_order(nc_netshop_order $order) {
        // добавить информацию о заказе в контекст
        $context = $this->get_condition_context();
        $context->set_order($order);

        $errors = array_merge(
            $this->delivery->check_new_order($order, $context),
            $this->payment->check_new_order($order, $context),
            $this->promotion->check_new_order($order, $context),
            $this->cart->check_new_order($order, $context)
        );

        return $errors;
    }

    /**
     * Оформление заказа
     * @param nc_netshop_order $order
     */
    public function place_order(nc_netshop_order $order) {
        // nc_netshop_order отражает данные в таблице MessageXYZ; перед вызовом
        // checkout() у объекта заказа должен быть установлен ID, равный ID
        // записи в таблице MessageXYZ:  $order->set_id($msgID)
        if (!$order->get_id()) {
            trigger_error("nc_netshop::checkout(): order must have an ID", E_USER_ERROR);
            return;
        }

        // (убрать, если будет сделана ленивая загрузка/сохранение nc_record)
        $order->load($order->get_id());

        // добавить информацию о заказе в контекст
        $this->set_order_in_condition_context($order);

        // установить валюту у заказа
        $order->set('OrderCurrency', $this->get_setting('DefaultCurrencyID'));

        // установить стоимость доставки у заказа (DeliveryCost):
        $this->delivery->checkout($order);

        // установить наценку за способ оплаты у заказа (PaymentCost):
        $this->payment->checkout($order);

        // сохранить информацию о применённых скидках, сбросить активацию купонов и скидок, и т.п.
        // NB, должно выполняться после delivery->checkout, так как нужно значение DeliveryCost:
        $this->promotion->checkout($order);

        // записать содержимое корзины в БД, сбросить корзину:
        $this->cart->checkout($order);

        // отправить пользователю и менеджерам письмо
        $this->mailer->checkout($order);

        // записать накопленные изменения в заказ:
        $order->save();
    }

    /**
     * Checks if netshop v1 is used
     *
     * @return bool
     */
    public function is_netshop_v1_in_use() {
        $nc_core = nc_Core::get_object();

        $module_vars = $nc_core->modules->get_module_vars();
        if (isset($module_vars['netshop']['SHOP_TABLE'])) {
            $catalogue_id = (int)$this->catalogue_id;
            $shop_table = (int)$module_vars['netshop']['SHOP_TABLE'];

            $sql = "SELECT s.`Subdivision_ID` FROM `Message{$shop_table}` AS m " .
                   "LEFT JOIN `Subdivision` AS s ON m.`Subdivision_ID` = s.`Subdivision_ID` " .
                   " WHERE m.`Checked` = 1 AND s.`Catalogue_ID` = {$catalogue_id}";

            return $nc_core->db->get_var($sql) ? true : false;
        }

        return false;
    }

    /**
     * onPaySuccess event handler
     *
     * @param nc_payment_system $payment_system
     * @param nc_payment_invoice $invoice
     */
    public function on_payment_success_event_handler(nc_payment_system $payment_system, nc_payment_invoice $invoice = null) {
        if ($invoice && $invoice->get('order_source') == 'netshop') {
            $order_id = $invoice->get('order_id');

            $order = $this->load_order($order_id);
            if ($order) {
                $order->set('Status', $this->get_setting('PaidOrderStatusID'));
                $this->mailer->process_order_status_change($order);
                $order->save();
            }
        }
    }

    /**
     * onPayFailure event handler
     *
     * @param nc_payment_system $payment_system
     * @param nc_payment_invoice $invoice
     */
    public function on_payment_failure_event_handler(nc_payment_system $payment_system, nc_payment_invoice $invoice = null) {
    }

}