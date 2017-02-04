<?php

class nc_netshop_condition_cart_count extends nc_netshop_condition {

    protected $op;
    protected $value;

    public function evaluate(nc_netshop_condition_context $context, $current_item = null) {
        return $this->compare(count($context->get_cart_contents()), $this->op, $this->value);
    }

}