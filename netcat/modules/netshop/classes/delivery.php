<?php

class nc_netshop_delivery {

    /** @var nc_netshop  */
    protected $netshop;

    /**
     *
     */
    public function __construct(nc_netshop $netshop) {
        $this->netshop = $netshop;
    }

    /**
     * Список включённых способов доставки для текущего сайта
     * @return nc_netshop_record_conditional_collection nc_netshop_delivery_method[]
     */
    public function get_enabled_methods() {
        $query = "SELECT *
                   FROM `%t%`
                  WHERE `Catalogue_ID` = " . (int)$this->netshop->get_catalogue_id() . "
                    AND `Checked` = 1
                  ORDER BY `Priority`";

        return nc_record_collection::load('nc_netshop_delivery_method', $query);
    }

    /**
     * Список всех способов доставки для текущего сайта
     * @return nc_netshop_record_conditional_collection nc_netshop_delivery_method[]
     */
    public function get_all_methods() {
        $query = "SELECT *
                   FROM `%t%`
                  WHERE `Catalogue_ID` = " . (int)$this->netshop->get_catalogue_id() . "
                  ORDER BY `Priority`";

        return nc_record_collection::load('nc_netshop_delivery_method', $query);
    }

    /**
     * Возвращает объект nc_netshop_delivery_method с указанным ID, при условии
     * что он привязан к текущему сайту, включён и удовлетворяет условиям ($context);
     * иначе возвращает NULL
     *
     * @param $method_id
     * @param nc_netshop_condition_context $context
     * @return nc_netshop_delivery_method|null
     */
    public function get_method_if_enabled($method_id, nc_netshop_condition_context $context) {
        try {
            $method = new nc_netshop_delivery_method($method_id);
            if ($method->get_id() &&
                $method->get('enabled') &&
                $method->get('catalogue_id') == $this->netshop->get_catalogue_id() &&
                $method->evaluate_conditions($context)
            ) { return $method; }
        }
        catch (Exception $e) {}
        return null;
    }

    /**
     * Проверка заказа перед оформлением
     * @param nc_netshop_order $order
     * @param nc_netshop_condition_context $context
     * @return array
     */
    public function check_new_order(nc_netshop_order $order, nc_netshop_condition_context $context) {
        $errors = array();
        // Проверка на существование и применимость метода оплаты
        $method_id = $order->get('DeliveryMethod');
        if ($method_id && !$this->get_method_if_enabled($method_id, $context)) {
            $errors[] = NETCAT_MODULE_NETSHOP_CHECKOUT_INCORRECT_DELIVERY_METHOD;
        }
        return $errors;
    }

    /**
     * @param nc_netshop_order $order
     */
    public function checkout(nc_netshop_order $order) {
        $delivery_method_id = $order->get('DeliveryMethod');
        if (!$delivery_method_id) { return; }

        // Ранее должна была проведена проверка на то, существует ли метод доставки
        // и возможен ли такой способ доставки для оформляемого заказа.

        // Оценка стоимости доставки:
        $estimate = $this->get_estimate($delivery_method_id, $order);

        // Сохранить всю оценку доставки в заказе (понадобится при сохранении скидок в nc_netshop_promotion::checkout
        $order->set_delivery_estimate($estimate);

        // Установить стоимость доставки в заказе:
        $order->set('DeliveryCost', $estimate->get('price'));
    }

    /**
     * @param $delivery_method_id
     * @param $order
     * @return nc_netshop_delivery_estimate
     */
    public function get_estimate($delivery_method_id, nc_netshop_order $order) {
        try {
            $delivery_method = new nc_netshop_delivery_method($delivery_method_id);
        }
        catch (Exception $e) {
            // Error: cannot instantiate delivery method object (most likely ID is wrong)
            return new nc_netshop_delivery_estimate(array(
                'error_code' => nc_netshop_delivery_estimate::ERROR_WRONG_METHOD_ID,
                'error' => NETCAT_MODULE_NETSHOP_DELIVERY_METHOD_INCORRECT_METHOD_ID
            ));
        }

        if (!$delivery_method->get_id() ||         // does not exist (e.g. $delivery_method_id = 0)
            !$delivery_method->get('enabled') ||   // is not enabled
             $delivery_method->get('catalogue_id') != $order->get_catalogue_id())   // not from the same site
        {
            return new nc_netshop_delivery_estimate(array(
                'error_code' => nc_netshop_delivery_estimate::ERROR_WRONG_METHOD,
                'error' => NETCAT_MODULE_NETSHOP_DELIVERY_METHOD_INCORRECT_METHOD_ID
            ));
        }

        return $delivery_method->get_estimate($order);
    }

}

