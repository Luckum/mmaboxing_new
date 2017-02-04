<?php

/**
 * nc_netshop_order
 *
 * Внимание: для загрузки заказа используйте метод nc_netshop->load_order().
 * Стандартный для nc_record вызов конструктора — new nc_netshop_order($order_id) —
 * вызовет ошибку, так как имя таблицы MessageX, в которой хранятся данные,
 * зависит от настроек интернет-магазина на конкретном сайте.
 */
class nc_netshop_order extends nc_record {

    protected $strict_property_mode = false;

    protected $table_name;  // устанавливается при вызове set_catalogue_id()
    protected $primary_key = "Message_ID";

    protected $mapping = false; // имена колонок и имена опций совпадают

    /** @var  nc_netshop_item_collection */
    protected $items;

    /** @var  int */
    protected $catalogue_id;

    /** @var  array */
    protected $cart_discounts;

    /** @var  float */
    protected $cart_discount_sum;

    /** @var  nc_netshop_delivery_estimate */
    protected $delivery_estimate;

    protected $order_items_table_name = "Netshop_OrderGoods";
    protected $order_discounts_table_name = "Netshop_OrderDiscounts";

    /**
     * Создание объекта order на основании данных из POST (где поля компонента
     * имеют префикс "f_") на этапе оформления заказа.
     * Устанавливает ID сайта из настроек магазина, список товаров (items) — из
     * содержимого текущей корзины.
     *
     * @param array $post_data
     * @param nc_netshop $netshop
     * @return nc_netshop_order
     */
    static public function from_post_data(array $post_data, nc_netshop $netshop) {
        $data = array();
        foreach ($post_data as $field => $value) {
            if (strpos($field, "f_") === 0) {
                $data[substr($field, 2)] = $value;
            }
        }

        $order = new self($data);
        $order->set_catalogue_id($netshop->get_catalogue_id());
        $order->set_items($netshop->get_cart_contents());

        return $order;
    }

    /**
     * @param $catalogue_id
     * @return $this
     */
    public function set_catalogue_id($catalogue_id) {
        $this->catalogue_id = $catalogue_id;
        $this->table_name = $this->get_netshop()->get_order_table_name();
        return $this;
    }

    /**
     *
     */
    public function get_catalogue_id() {
        if (!$this->catalogue_id) {
            $subdivision_id = $this->get('Subdivision_ID');
            if ($subdivision_id) {
                $this->catalogue_id = nc_core::get_object()->subdivision->get_by_id($subdivision_id, "Catalogue_ID");
            }
        }
        return $this->catalogue_id;
    }

    /**
     * @return nc_netshop
     */
    protected function get_netshop() {
        return nc_netshop::get_instance($this->get_catalogue_id());
    }

    /**
     *
     */
    protected function format_price($price, $currency = null) {
        return $this->get_netshop()->format_price($price, $currency);
    }

    /**
     *
     */
    public function set_items(nc_netshop_item_collection $items) {
        $this->items = $items;
        return $this;
    }

    /**
     * @param array[]|nc_netshop_item_collection $new_items
     * @return bool
     */
    public function save_items(nc_netshop_item_collection $new_items) {
        $old_items = $this->load_items();
        $new_items->set_index_property('_ItemKey');

        $old_items_keys = $old_items->each('get', '_ItemKey');
        $new_items_keys = $new_items->each('get', '_ItemKey');

        $deleted_items_keys = array_diff($old_items_keys, $new_items_keys);
        $added_items_keys = array_diff($new_items_keys, $old_items_keys);
        $same_items_keys = array_intersect($old_items_keys, $new_items_keys);

        // Удаление товара из заказа
        foreach ($deleted_items_keys as $key) {
            $this->remove_item_from_database($old_items->offsetGet($key));
        }

        // Новый товар в заказе
        foreach ($added_items_keys as $key) {
            /** @var nc_netshop_item $new_item */
            $new_item = $new_items->offsetGet($key);
            if ($new_item['Qty'] > 0) {
                $this->save_item_in_database($new_item, true);
            }
        }

        // Изменение параметров товара (цена, количество, скидка)
        // Скидка: если указана напрямую, удалить информацию об автоматически применённых скидках
        foreach ($same_items_keys as $key) {
            /** @var nc_netshop_item $new_item */
            /** @var nc_netshop_item $old_item */
            $new_item = $new_items->offsetGet($key);
            $old_item = $old_items->offsetGet($key);

            if ($new_item['Qty'] == 0) { // удалить запись, если количество == 0
                $this->remove_item_from_database($new_item);
            }
            else {
                $update_discount_info = false;
                if ($new_item['ItemDiscount'] != $old_item['ItemDiscount']) {
                    $new_item['Discounts'] = array(
                        array(
                            'id' => 0,
                            'name' => NETCAT_MODULE_NETSHOP_DISCOUNT_MANUAL,
                            'description' => '',
                            'sum' => $new_item['TotalDiscount'],
                            'price_minimum' => 0
                        )
                    );
                    $update_discount_info = true;
                }

                $this->save_item_in_database($new_item, false, $update_discount_info);
            }
        }

        $this->items = $new_items;

        return true;
    }

    /**
     *
     */
    public function save_cart_discounts(array $discounts) {
        $this->save_discount_info_in_database(0, 0, $discounts);
    }

    /**
     *
     */
    public function update_cart_discount($new_discount_sum) {
        $this->remove_discount_info_from_database(0, 0);
        $this->save_cart_discounts(array(
            array(
                'id' => 0,
                'name' => NETCAT_MODULE_NETSHOP_DISCOUNT_MANUAL,
                'description' => '',
                'sum' => $new_discount_sum,
                'price_minimum' => false,
            )
        ));
    }

    /**
     *
     */
    protected function save_item_in_database(nc_netshop_item $item, $is_new_order = false, $update_discount_info = false) {
        $db = nc_db();

        $values = array(
            "Item_Type" => $item["Class_ID"],
            "Item_ID" => $item["Message_ID"],
            "Qty" => str_replace(',', '.', $item["Qty"]),
            "OriginalPrice" => str_replace(',', '.', $item["OriginalPrice"]),
            "ItemPrice" => str_replace(',', '.', $item["ItemPrice"]),
        );

        if (!$is_new_order && $item->offsetExists('OrderParameters')) {
            // ($item->offsetExists() использует для проверки array_key_exists, поэтому здесь null тоже считается существующим значением)
            $values['OrderParameters'] = $item['OrderParameters'] ? serialize($item["OrderParameters"]) : '';
        }

        $order_id_clause = "`Order_ID` = " . (int)$this->get_id();
        $set_clause = $order_id_clause;
        foreach ($values as $key => $value) {
            $set_clause .= ", `$key` = '" . $db->escape($value) . "'";
        }

        if ($is_new_order) {
            $db->query("INSERT INTO `$this->order_items_table_name` SET $set_clause");
        }
        else {
            $db->query("UPDATE `$this->order_items_table_name` SET $set_clause WHERE $order_id_clause");
        }

        if ($is_new_order || $update_discount_info) {
            if ($update_discount_info) { $this->remove_discount_info_from_database($item['Class_ID'], $item['Message_ID']); }
            $this->save_discount_info_in_database($item['Class_ID'], $item['Message_ID'], $item['Discounts']);
        }
    }

    /**
     *
     */
    protected function remove_item_from_database(nc_netshop_item $item) {
        nc_db()->query("DELETE FROM `$this->order_items_table_name`
                         WHERE `Order_ID` = " . (int)$this->get_id() . "
                           AND `Item_Type` = " . (int)$item['Class_ID'] . "
                           AND `Item_ID` = " . (int)$item['Message_ID']);

        $this->remove_discount_info_from_database($item['Class_ID'], $item['Message_ID']);
    }

    /**
     *
     */
    protected function save_discount_info_in_database($component_id, $object_id, array $discounts) {
        $db = nc_db();
        foreach ($discounts as $discount_info) {
            if (!is_array($discount_info)) { continue; }
            $query = "INSERT INTO `$this->order_discounts_table_name`
                         SET `Order_ID` = " . (int)$this->get_id() .",
                             `Item_Type` = " . (int)$component_id . ",
                             `Item_ID` = " . (int)$object_id . ",
                             `Discount_ID` = " . (int)$discount_info['id'] . ",
                             `Discount_Name` = '" . $db->escape($discount_info['name']) . "',
                             `Discount_Description` = '" . $db->escape($discount_info['description']) . "',
                             `Discount_Sum` = '" . $db->escape(str_replace(',', '.', $discount_info['sum'])) . "',
                             `PriceMinimum` = " . intval($discount_info['price_minimum']) . ",
                             `IsComponentBased` = 0";

            $db->query($query);
        }
    }

    /**
     *
     */
    protected function remove_discount_info_from_database($component_id, $object_id) {
        nc_db()->query("DELETE FROM `$this->order_discounts_table_name`
                         WHERE `Order_ID` = " . (int)$this->get_id() . "
                           AND `Item_Type` = " . (int)$component_id . "
                           AND `Item_ID` = " . (int)$object_id);
    }

    /**
     * @param nc_netshop_delivery_estimate $estimate
     */
    public function set_delivery_estimate(nc_netshop_delivery_estimate $estimate) {
        $this->delivery_estimate = $estimate;
    }

    /**
     *
     * @return nc_netshop_delivery_estimate|null
     */
    public function get_delivery_estimate() {
        if ($this->delivery_estimate == null && $this->get('DeliveryMethod')) {
            $estimate = $this->get_netshop()->delivery->get_estimate($this->get('DeliveryMethod'), $this);
            $this->delivery_estimate = $estimate;
        }
        return $this->delivery_estimate;
    }

    /**
     *
     */
    public function get_items() {
        if (!$this->items) {
            $this->items = $this->load_items();
        }
        return $this->items;
    }

    /**
     *
     */
    protected function load_items() {
        $items = new nc_netshop_item_collection();
        $items->set_index_property('_ItemKey');

        $raw_item_data = (array)nc_db()->get_results(
            "SELECT `Item_Type` AS `Class_ID`,
                    `Item_ID` AS `Message_ID`,
                    `Qty`,
                    `OriginalPrice`,
                    `ItemPrice`,
                    `OriginalPrice` - `ItemPrice` AS `ItemDiscount`,
                    `OrderParameters`
               FROM `$this->order_items_table_name`
              WHERE `Order_ID` = " . (int)$this->get_id(),
            ARRAY_A);

        foreach ($raw_item_data as $row) {
            $row['Catalogue_ID'] = $this->catalogue_id;
            $row['OrderParameters'] = unserialize($row['OrderParameters']);
            $row['Discounts'] = $this->load_discount_info($row['Class_ID'], $row['Message_ID']);
            $items->add(new nc_netshop_item($row));
        }

        return $items;
    }

    /**
     * @param $component_id
     * @param $object_id
     * @return array
     */
    protected function load_discount_info($component_id, $object_id) {
        return (array)nc_db()->get_results(
            "SELECT `Discount_ID` AS `id`,
                    `Discount_Name` AS `name`,
                    `Discount_Description` AS `description`,
                    `Discount_Sum` AS `sum`,
                    `PriceMinimum` AS `price_minimum`
               FROM `{$this->order_discounts_table_name}`
              WHERE `Order_ID` = " . (int)$this->get_id() . "
                AND `Item_Type` = " . (int)$component_id . "
                AND `Item_ID` = " . (int)$object_id,
            ARRAY_A);
    }

    /**
     * @param array $discount_info
     */
    public function add_cart_discount(array $discount_info) {
        if (!is_array($this->cart_discounts)) {
            $this->cart_discounts = array();
        }
        $this->cart_discounts[] = $discount_info;
    }

    /**
     * @return array
     */
    public function get_cart_discounts() {
        if ($this->cart_discounts === null) {
            $this->cart_discounts = $this->load_discount_info(0, 0);
        }
        return $this->cart_discounts;
    }

    /**
     * @return int|float
     */
    public function get_cart_discount_sum() {
        if ($this->cart_discount_sum === null) {
            $this->cart_discount_sum = 0;
            foreach ($this->get_cart_discounts() as $discount) {
                $this->cart_discount_sum += $discount['sum'];
            }
        }
        return $this->cart_discount_sum;
    }

    /**
     * Сумма скидок на товары
     * @return int|float
     */
    public function get_item_discount_sum() {
        return $this->get_items()->sum('TotalDiscount');
    }

    /**
     * Сумма стоимости товаров (с учётом скидок)
     * @return int|float
     */
    public function get_item_totals() {
        return $this->get_items()->sum('TotalPrice');
    }

    /**
     * Сумма всех скидок (на товары + на корзину)
     * @return int|float
     */
    public function get_discount_sum() {
        return $this->get_item_discount_sum() + $this->get_cart_discount_sum();
    }

    /**
     * Сумма к оплате (стоимость товаров + стоимость доставки + наценка за оплату
     * @return int|float
     */
    public function get_totals() {
        return $this->get_item_totals() + $this->get('DeliveryCost') + $this->get('PaymentCost');
    }


    /**
     * Доступ к «вычисляемым» свойствам заказа через ArrayInterface (для
     * упрощения работы с шаблонами писем).
     *
     * Полный список «вычисляемых» свойств:
     *    Class_ID                  равно настройке магазина OrderComponentID
     *    Date                      отформатированная дата
     *    TotalItemPriceF           стоимость товаров с учётом скидок
     *    TotalItemPrice
     *    TotalItemOriginalPriceF   стоимость товаров без скидок
     *    TotalItemOriginalPrice
     *    TotalItemDiscountSumF     сумма скидок на товар
     *    TotalItemDiscountSum
     *    CartDiscountSumF          сумма скидок на корзину (включая скидки на доставку)
     *    CartDiscountSum
     *    TotalPriceF               сумма к оплате
     *    TotalPrice
     *    DiscountSumF              сумма всех скидок (на товары и на корзину)
     *    DiscountSum
     *    DeliveryMethodName        название метода доставки
     *    DeliveryDates             отформатированная дата (или диапазон дат) доставки
     *    DeliveryPriceF            стоимость доставки
     *    DeliveryPrice
     *    PaymentMethodName         название способа оплаты
     *    PaymentPriceF             наценка за способ оплаты
     *    PaymentPrice
     *
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset) {
        switch ($offset) {
            case 'Class_ID': // нужно в nc_netshop_mailer_template
                return $this->get_netshop()->get_setting('OrderComponentID');

            case 'Date':
                return date(NETCAT_MODULE_NETSHOP_DATE_FORMAT, strtotime($this->get('Created')));

            case 'TotalItemPriceF':
                return $this->format_price($this->get_item_totals());

            case 'TotalItemPrice':
                return $this->get_item_totals();

            case 'TotalItemOriginalPriceF':
                return $this->format_price($this->get_items()->get_field_sum('OriginalPrice'));

            case 'TotalItemOriginalPrice':
                return $this->get_items()->get_field_sum('OriginalPrice');

            case 'TotalItemDiscountSumF':
                return $this->format_price($this->get_item_discount_sum());

            case 'TotalItemDiscountSum':
                return $this->get_item_discount_sum();

            case 'CartDiscountSumF':
                return $this->format_price($this->get_cart_discount_sum());

            case 'CartDiscountSum':
                return $this->get_cart_discount_sum();

            case 'TotalPriceF':
                return $this->format_price($this->get_totals());

            case 'TotalPrice':
                return $this->get_totals();

            case 'DiscountSumF':
                return $this->format_price($this->get_discount_sum());

            case 'DiscountSum':
                return $this->get_discount_sum();

            case 'DeliveryMethodName':
                $estimate = $this->get_delivery_estimate();
                return ($estimate ? $estimate->get('delivery_method_name') : null);

            case 'DeliveryDates':
                $estimate = $this->get_delivery_estimate();
                return ($estimate ? $estimate->get_dates_string() : null);

            case 'DeliveryPriceF':
                return $this->format_price($this->get('DeliveryCost'));

            case 'DeliveryPrice':
                return $this->get('DeliveryCost');

            case 'PaymentMethodName':
                $method_id = (int)$this->get('PaymentMethod');
                if ($method_id) {
                    try {
                        $method = new nc_netshop_payment_method($method_id);
                        return $method->get('name');
                    }
                    catch (Exception $e) {}
                }
                return null;

            case 'PaymentPriceF':
                return $this->format_price($this->get('PaymentCost'));

            case 'PaymentPrice':
                return $this->get('PaymentCost');

            default:
                return parent::offsetGet($offset);
        }
   }


}