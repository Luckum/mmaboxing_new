<?php

class nc_netshop_condition_cart_item extends nc_netshop_condition {

    /**
     * Количество определённого товара в корзине
     *  - qty
     *  - item:  "ComponentID:ItemID"
     */

    protected $op;
    protected $qty;
    protected $component_id;
    protected $item_id;


    public function __construct($parameters = array()) {
        $this->op = $parameters['op'];
        $this->qty = $parameters['qty'];

        $item = explode(":", $parameters['item']);
        $this->component_id = $item[0];
        $this->item_id = $item[1];
    }


    public function evaluate(nc_netshop_condition_context $context, $current_item = null) {
        $cart = $context->get_cart_contents();
        foreach ($cart as $item) {
            if ($item['Class_ID'] == $this->component_id && ($item['Message_ID'] == $this->item_id || $item['Parent_Message_ID'] == $this->item_id)) {
                return $this->compare($item['Qty'], $this->op, $this->qty);
            }
        }
        return false;
    }

    /**
     * Короткое описание (только значение, для повторяющихся условий)
     * @param nc_netshop $netshop
     * @return string
     */
    public function get_short_description(nc_netshop $netshop) {
        $item = nc_netshop_admin_helpers::get_item($this->component_id, $this->item_id);
        return $this->add_operator_description($this->qty) . ' ' .
               NETCAT_MODULE_NETSHOP_COND_ORDERS_ITEM_UNITS . ' ' .
               sprintf(NETCAT_MODULE_NETSHOP_COND_QUOTED_VALUE, "<a target='_blank' href='$item[URL]'>$item[Name]</a>");
    }

}