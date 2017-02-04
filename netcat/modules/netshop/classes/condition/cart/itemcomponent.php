<?php

class nc_netshop_condition_cart_itemcomponent extends nc_netshop_condition {

    /**
     * Количество товаров указанного компонента в корзине
     *  - qty
     *  - op
     *  - component
     */

    protected $op;
    protected $qty;
    protected $component;


    public function evaluate(nc_netshop_condition_context $context, $current_item = null) {
        $cart = $context->get_cart_contents();
        $total_qty = 0;

        foreach ($cart as $item) {
            if ($item['Class_ID'] == $this->component) {
                $total_qty += $item['Qty'];
            }
        }

        return $this->compare($total_qty, $this->op, $this->qty);
    }

    /**
     * Короткое описание (только значение, для повторяющихся условий)
     * @param nc_netshop $netshop
     * @return string
     */
    public function get_short_description(nc_netshop $netshop) {
        return $this->add_operator_description($this->qty) . ' ' .
               NETCAT_MODULE_NETSHOP_COND_ORDERS_ITEM_UNITS . ' ' .
               NETCAT_MODULE_NETSHOP_COND_CART_ITEMCOMPONENT_FROM . ' ' .
               nc_netshop_condition_admin_helpers::get_component_link($this->component);
    }

}