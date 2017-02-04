<?php

class nc_netshop_delivery_method extends nc_netshop_record_conditional {

    protected $primary_key = "id";
    protected $properties = array(
        "id" => null,
        "catalogue_id" => null,
        "name" => '',
        "description" => '',
        "condition" => '',
        "handler_id" => null,
        "handler_options" => null,
        "extra_charge_absolute" => null,
        "extra_charge_relative" => null,
        "minimum_delivery_days" => null,
        "maximum_delivery_days" => null,
        "shipment_days_of_week" => '', // '1,2,3,4,5,6,7'
        "shipment_time" => '', // '00:00'
        "priority" => 0,
        "enabled" => null,
    );

    protected $handler_classifier_table = "Classificator_ShopDeliveryService";
    protected $handler_classifier_table_pk = "ShopDeliveryService_ID";

    protected $table_name = "Netshop_DeliveryMethod";
    protected $mapping = array(
        "id" => "DeliveryMethod_ID",
        "catalogue_id" => "Catalogue_ID",
        "name" => "Name",
        "description" => "Description",
        "condition" => "Condition",
        "handler_id" => "ShopDeliveryService_ID",
        "handler_mapping" => "ShopDeliveryService_Mapping",
        "extra_charge_absolute" => "ExtraChargeAbsolute",
        "extra_charge_relative" => "ExtraChargeRelative",
        "minimum_delivery_days" => "MinimumDeliveryDays",
        "maximum_delivery_days" => "MaximumDeliveryDays",
        "shipment_days_of_week" => "ShipmentDaysOfWeek",
        "shipment_time" => 'ShipmentTime',
        "priority" => "Priority",
        "enabled" => "Checked",
    );

    /** @var  nc_netshop_delivery_service */
    protected $handler;

    /** @var  array   массив для хранения оценок стоимости и времени доставки */
    protected $estimations_cache = array();


    /**
     * Возвращает стоимость доставки указанного заказа. В возникновения ошибки
     * при расчёте стоимости возвращает NULL (следует отличать от 0, то есть
     * бесплатной доставки).
     *
     * @param nc_netshop_order $order
     * @return int|float|null
     */
    public function get_delivery_price(nc_netshop_order $order) {
        $estimate = $this->get_estimate($order);

        // error occurred:
        if (isset($estimate['error_code']) || !isset($estimate['price'])) {
            return null;
        }

        return $estimate['price'];
    }

    /**
     * Проверяет, зависит ли способ доставки от каких-либо данных, указываемых
     * при оформлении заказа.
     *
     * @return bool
     */
    public function depends_on_order_data() {
        return ($this->get('handler_id') || $this->has_condition_of_type('order'));
    }

    /**
     * Возвращает массив с оценкой стоимости и времени доставки заказа с указанными
     * параметрами.
     *
     * @param nc_netshop_order $order
     * @return nc_netshop_delivery_estimate
     */
    public function get_estimate(nc_netshop_order $order) {
        $order_data = $order->to_array();
        $cart_contents = $order->get_items();
        // cache responses for re-use
        $cache_key = sha1(serialize($order_data) . "\n/" . $cart_contents->get_hash());
        if ($this->estimations_cache[$cache_key]) {
            return $this->estimations_cache[$cache_key];
        }

        $result = array(
            'catalogue_id' => $this->get('catalogue_id'),
            'delivery_method_id' => $this->get_id(),
            'delivery_method_name' => $this->get('name'),
            'order_id' => $order->get_id(),
            'calculation_timestamp' => time(),
            'full_price' => null,
            'price' => null,
            'discount' => null,
            'min_days' => null,
            'max_days' => null,
            'error_code' => nc_netshop_delivery_estimate::ERROR_OK,
            'error' => '',
        );

        $netshop = nc_netshop::get_instance($this->get('catalogue_id'));

        // Постоянная (независящая от службы доставки) часть стоимости
        $order_totals = $cart_contents->sum('TotalPrice');
        $delivery_cost = $this->get('extra_charge_absolute') +
                         $this->get('extra_charge_relative') * $order_totals / 100;

        $service_min_days = null;
        $service_max_days = null;

        $handler = $this->get_handler();
        if ($handler) {
            $data_for_handler = $this->map_handler_params($order_data);
            $weight = $cart_contents->sum('Weight');
            $data_for_handler['weight'] = $weight ? $weight : 100;
            $data_for_handler['valuation'] = $order_totals;
            $handler->set_data($data_for_handler);

            $estimate = $handler->calculate_delivery();

            if ($handler->get_last_error_code() != nc_netshop_delivery_service::ERROR_OK) {
                // коды ошибок — одинаковые в estimate и service
                $result['error_code'] = $handler->get_last_error_code();
                $result['error'] = $handler->get_last_error();
            }
            else {
                $service_price = $netshop->convert_currency($estimate['price'], $estimate['currency']);
                $delivery_cost += $service_price;
                $service_min_days = isset($estimate['min_days']) ? $estimate['min_days'] : null;
                $service_max_days = isset($estimate['max_days']) ? $estimate['max_days'] : null;
            }
        }

        if (!$result['error_code']) {
            $delivery_cost = $netshop->round_price($delivery_cost);
            $result['full_price'] = $delivery_cost;

            // Учёт скидок на доставку
            if ($delivery_cost) {
                $discount = $netshop->promotion->get_delivery_discount_sum($delivery_cost, $this->get_id());
                $delivery_cost = $delivery_cost - $discount;
                $result['discount'] = $discount;
            }

            // Добавить в результат цену и отформатированную цену (с учётом скидок)
            $result['price'] = $delivery_cost;

            // Сроки доставки
            if ($service_min_days != null || is_numeric($this->get('minimum_delivery_days'))) {
                // День начала доставки
                $shipment_time = $now = time();

                // Поиск ближайшего дня недели, когда возможна отправка
                $shipment_days = $this->get('shipment_days_of_week');
                if (!preg_match('/^[\d,]+$/', $shipment_days)) { $shipment_days = '1,2,3,4,5,6,7'; }
                $shipment_days = explode(",", $shipment_days);

                // Если отправка возможна в текущий день недели, но время, до которого
                // возможна отправка, прошло, прибавить день
                $the_train_is_off = in_array(date('N', $now), $shipment_days) &&
                                    strtotime($this->get('shipment_time')) <= $now;
                // (для простоты здесь и далее возможные проблемы с переводом часов игнорируются)
                if ($the_train_is_off) { $shipment_time += 86400; }

                $security_counter = 30;
                while ($security_counter && !in_array(date('N', $shipment_time), $shipment_days)) {
                    $shipment_time += 86400;
                    $security_counter--;
                }

                // Через сколько дней возможно начало доставки?
                $days_until_shipment = round(($shipment_time - $now) / 86400);

                // Теперь можно определиться с тем, когда может быть осуществлена доставка
                $min_days = intval($days_until_shipment +
                                   $this->get('minimum_delivery_days') +
                                   $service_min_days);

                $max_days = intval($days_until_shipment +
                                   $this->get('maximum_delivery_days') +
                                   ($service_max_days ? $service_max_days : $service_min_days));

                $result['min_days'] = $min_days;
                $result['max_days'] = max($min_days, $max_days);
            }

        }

        $this->estimations_cache[$cache_key] = new nc_netshop_delivery_estimate($result);

        return $this->estimations_cache[$cache_key];
    }

    /**
     * @return nc_netshop_delivery_service|null
     */
    protected function get_handler() {
        if ($this->handler) { return $this->handler; }

        $handler_id = (int)$this->get('handler_id');
        if (!$handler_id) { return null; }
        $handler_class = nc_db()->get_var(
            "SELECT `Value`
               FROM `$this->handler_classifier_table`
              WHERE `$this->handler_classifier_table_pk` = $handler_id"
        );
        if (is_subclass_of($handler_class, "nc_netshop_delivery_service")) {
            $this->handler = new $handler_class;
            return $this->handler;
        }
        else {
            trigger_error('Wrong delivery service ID or class name', E_USER_WARNING);
        }

        return null;
    }

    /**
     * @param array $order_data  Значения полей заказа (без префикса f_)
     * @return array
     */
    protected function map_handler_params(array $order_data) {
        $result = array();

        $mapping = $this->get('handler_mapping');
        if (!$mapping) { return $result; }

        /** @var nc_netshop $netshop */
        $netshop = nc_modules('netshop');  // экземпляр nc_netshop для текущего сайта

        $mapping = @json_decode($mapping, true);
        if (!is_array($mapping)) { return $result; }

        $order_component = new nc_component($netshop->get_setting('OrderComponentID'));
        $order_fields = $order_component->get_fields(0, 1);
        $shop_fields = nc_netshop_admin_helpers::get_shop_fields();

        foreach ($mapping as $to => $from) {
            $value = null;
            list ($from_source, $from_field) = explode("_", $from, 2);

            if ($from_source == 'shop') {
                $value = $netshop->get_setting($from_field);
                if (isset($shop_fields[$from_field]['classificator'])) {
                    $value = nc_get_list_item_name($shop_fields[$from_field]['classificator'], $value);
                }
            }
            elseif ($from_source == 'order') {
                foreach ($order_fields as $field) {
                    if ($field['id'] == $from_field) { // $from_field is a field ID
                        $value = $order_data[$field['name']];
                        if ($field['type'] == NC_FIELDTYPE_SELECT) {
                            list($classifier) = explode(":", $field['format']);
                            $value = nc_get_list_item_name($field['table'], $value);
                        }
                        break; // exit inner foreach
                    }
                }
            }
            $result[$to] = $value;
        }

        return $result;
    }

}