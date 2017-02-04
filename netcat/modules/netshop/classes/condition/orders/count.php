<?php

class nc_netshop_condition_orders_count extends nc_netshop_condition {

    protected $op;
    protected $value;

    public function evaluate(nc_netshop_condition_context $context, $current_item = null) {
        return $this->compare($context->get_user_previous_orders_count(), $this->op, $this->value);
    }

}