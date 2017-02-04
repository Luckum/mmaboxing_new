<?php

class nc_netshop_condition_orders_component extends nc_netshop_condition {

    /**
     * Parameters:
     *   value: component ID
     */
    protected $value;

    public function evaluate(nc_netshop_condition_context $context, $current_item = null) {
        return $context->previous_orders_had_item($this->value);
    }

    /**
     * Короткое описание (только значение, для повторяющихся условий)
     * @param nc_netshop $netshop
     * @return string
     */
    public function get_short_description(nc_netshop $netshop) {
        $description = nc_netshop_condition_admin_helpers::get_component_link($this->value);
        return $this->add_operator_description($description);
    }
}