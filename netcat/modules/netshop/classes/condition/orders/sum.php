<?php

class nc_netshop_condition_orders_sum extends nc_netshop_condition {

    /**
     * Parameters:
     *   op
     *   value
     */
    protected $op;
    protected $value;

    public function __construct($parameters = array()) {
        $this->op = $parameters['op'];
        $this->value = $this->convert_decimal_point($parameters['value']);
    }


    public function evaluate(nc_netshop_condition_context $context, $current_item = null) {
        return $this->compare($context->get_user_previous_orders_sum(), $this->op, $this->value);
    }

    public function get_short_description(nc_netshop $netshop) {
        return $this->add_operator_description($netshop->format_price($this->value));
    }


}