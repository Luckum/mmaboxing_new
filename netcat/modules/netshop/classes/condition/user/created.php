<?php

class nc_netshop_condition_user_created extends nc_netshop_condition {

    /**
     * Parameters:
     *    'op'
     *    'value'  -- datetime
     */
    protected $op;
    protected $value;

    public function evaluate(nc_netshop_condition_context $context, $current_item = null) {
        return $this->compare(
            $context->get_user_property('Created'),
            $this->op,
            $this->value,
            NC_FIELDTYPE_DATETIME
        );
    }

    public function get_short_description(nc_netshop $netshop) {
        return $this->add_operator_description(
            nc_netshop_condition_admin_helpers::format_date($this->value),
            $this->op . "_DATE"
        );
    }


}