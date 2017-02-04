<?php

class nc_netshop_condition_cart_itemparentsub extends nc_netshop_condition {

    /**
     * Количество товаров из указанного раздела в корзине
     *  - qty
     *  - op
     *  - subdivision
     */

    protected $op;
    protected $qty;
    protected $subdivision;


    public function evaluate(nc_netshop_condition_context $context, $current_item = null) {
        $cart = $context->get_cart_contents();
        $total_qty = 0;


        foreach ($cart as $item) {
            $item_sub_id = $item['Subdivision_ID'];
            $condition_sub_id = $this->subdivision;
            do {
                if ($condition_sub_id == $item_sub_id) {
                    $total_qty += $item['Qty'];
                    break; // exit while()
                }
                // continue moving upwards in the subdivision hierarchy
                $item_sub_id = nc_netshop_condition_util::get_parent_sub($item_sub_id);
            } while ($item_sub_id);
        }

        return $this->compare($total_qty, $this->op, $this->qty);
    }

    /**
     * Короткое описание (только значение, для повторяющихся условий)
     * @param nc_netshop $netshop
     * @return string
     */
    public function get_short_description(nc_netshop $netshop) {
        try {
            $subdivision = nc_core('subdivision')->get_by_id($this->subdivision);
            return $this->add_operator_description($this->qty) . ' ' .
                   NETCAT_MODULE_NETSHOP_COND_ORDERS_ITEM_UNITS . ' ' .
                   NETCAT_MODULE_NETSHOP_COND_CART_ITEMPARENTSUB_FROM . ' ' .
                   sprintf(
                      NETCAT_MODULE_NETSHOP_COND_QUOTED_VALUE,
                      "<a href='$subdivision[HiddenURL]' target='_blank'>$subdivision[Subdivision_Name]</a>"
                   ) . ' ' .
                   NETCAT_MODULE_NETSHOP_COND_CART_ITEMPARENTSUB_FROM_DESCENDANTS;
        }
        catch (Exception $e) {
            return "<em class='nc--status-error'>" . NETCAT_MODULE_NETSHOP_COND_NONEXISTENT_SUB . "</em>";
        }
    }

}