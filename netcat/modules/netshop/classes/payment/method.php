<?php

class nc_netshop_payment_method extends nc_netshop_record_conditional {

    protected $primary_key = "id";
    protected $properties = array(
        "id" => null,
        "catalogue_id" => null,
        "name" => '',
        "description" => '',
        "condition" => '',
        "handler_id" => null,
        "extra_charge_absolute" => null,
        "extra_charge_relative" => null,
        "priority" => 0,
        "enabled" => null,
    );

    protected $handler_classifier_table = "Classificator_PaymentSystem";

    protected $table_name = "Netshop_PaymentMethod";
    protected $mapping = array(
        "id" => "PaymentMethod_ID",
        "catalogue_id" => "Catalogue_ID",
        "name" => "Name",
        "description" => "Description",
        "condition" => "Condition",
        "handler_id" => "PaymentSystem_ID",
        "extra_charge_absolute" => "ExtraChargeAbsolute",
        "extra_charge_relative" => "ExtraChargeRelative",
        "priority" => "Priority",
        "enabled" => "Checked",
    );


    /**
     * Возвращает наценку при использовании данного способа оплаты.
     *
     * @param nc_netshop_order $order
     * @return int|float
     */
    public function get_extra_cost(nc_netshop_order $order) {
        $cart_contents = $order->get_items();
        $extra = $this->get('extra_charge_absolute') +
                 $this->get('extra_charge_relative') * $cart_contents->sum('TotalPrice') / 100;
        return nc_netshop::get_instance($this->get('catalogue_id'))->round_price($extra);
    }

    /**
     * Проверяет, зависит ли способ оплаты от каких-либо данных, указываемых
     * при оформлении заказа.
     *
     * @return bool
     */
    public function depends_on_order_data() {
        return $this->has_condition_of_type('order');
    }
    
    /**
     * Проверяет, зависит ли способ оплаты от способа доставки.
     *
     * @return bool
     */
    public function depends_on_delivery_method() {
        return $this->has_condition_of_type('order_deliverymethod');
    }


}