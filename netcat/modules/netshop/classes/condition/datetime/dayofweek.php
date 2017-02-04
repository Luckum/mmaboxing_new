<?php

class nc_netshop_condition_datetime_dayofweek extends nc_netshop_condition {

    /**
     * Parameters:
     *   op
     *   value  -- date of week number, with monday being 1 and sunday being 7
     */
    protected $value;
    protected $op;

    /**
     * @param nc_netshop_condition_context $context
     * @param null $current_item
     * @return bool
     */
    public function evaluate(nc_netshop_condition_context $context, $current_item = null) {
        $day = date("N", $context->get_time());
        return $this->compare($day, $this->op, $this->value);
    }

    /**
     * @param nc_netshop $netshop
     * @return string
     */
    public function get_full_description(nc_netshop $netshop) {
        return $this->get_short_description($netshop);
    }

    /**
     * @param nc_netshop $netshop
     * @return string
     */
    public function get_short_description(nc_netshop $netshop) {
        if ($this->op == 'eq') {
            $days = explode("/", NETCAT_MODULE_NETSHOP_COND_DAYOFWEEK_ON_LIST);
        }
        else {
            $days = explode("/", NETCAT_MODULE_NETSHOP_COND_DAYOFWEEK_EXCEPT_LIST);
        }

        return $days[$this->value - 1];
    }

}