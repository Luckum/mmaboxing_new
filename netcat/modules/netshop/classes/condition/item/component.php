<?php

class nc_netshop_condition_item_component extends nc_netshop_condition {

    /**
     * Parameters:
     *    'op'
     *    'value'  -- component ID
     */

    protected $op;
    protected $value;


    public function evaluate(nc_netshop_condition_context $context, $current_item = null) {
        if ($current_item instanceof nc_netshop_item) {
            return ($this->compare($current_item['Class_ID'], $this->op, $this->value));
        }
        return false;
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