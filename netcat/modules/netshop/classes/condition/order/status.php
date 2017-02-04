<?php

class nc_netshop_condition_order_status extends nc_netshop_condition {

    /**
     * Parameters:
     *    'value'  − ID of the delivery method
     */

    protected $op;
    protected $value;


    public function evaluate(nc_netshop_condition_context $context, $current_item = null) {
        return $this->compare(
            (int)$context->get_order_property('Status'),
            $this->op,
            (int)$this->value
        );
    }

    /**
     * Короткое описание (только значение, для повторяющихся условий)
     * @param nc_netshop $netshop
     * @return string
     */
    public function get_short_description(nc_netshop $netshop) {
        if (!$this->value) {
            $status = NETCAT_MODULE_NETSHOP_ORDER_NEW;
        }
        else {
            $status = nc_get_list_item_name('ShopOrderStatus', $this->value);
        }

        if (!strlen($status)) {
            return "<em class='nc--status-error'>" . NETCAT_MODULE_NETSHOP_COND_NONEXISTENT_STATUS . "</em>";
        }

        $quoted_status_name = sprintf(NETCAT_MODULE_NETSHOP_COND_QUOTED_VALUE, $status);
        if ($this->op == 'eq') {
            return $quoted_status_name;
        }
        else {
            return $this->add_operator_description($quoted_status_name);
        }

    }

}