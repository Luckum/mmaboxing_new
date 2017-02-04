<?php

class nc_netshop_condition_cart_itemsub extends nc_netshop_condition {

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
            if ($item['Subdivision_ID'] == $this->subdivision) {
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
        try {
            $subdivision = nc_core('subdivision')->get_by_id($this->subdivision);
            return $this->add_operator_description($this->qty) . ' ' .
                   NETCAT_MODULE_NETSHOP_COND_ORDERS_ITEM_UNITS . ' ' .
                   NETCAT_MODULE_NETSHOP_COND_CART_ITEMPARENTSUB_FROM . ' ' .
                   sprintf(
                      NETCAT_MODULE_NETSHOP_COND_QUOTED_VALUE,
                      "<a href='$subdivision[HiddenURL]' target='_blank'>$subdivision[Subdivision_Name]</a>"
                   );
        }
        catch (Exception $e) {
            return "<em class='nc--status-error'>" . NETCAT_MODULE_NETSHOP_COND_NONEXISTENT_SUB . "</em>";
        }
    }

}