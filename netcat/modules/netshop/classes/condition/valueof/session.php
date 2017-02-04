<?php

class nc_netshop_condition_valueof_session extends nc_netshop_condition {

    /**
     * Parameters:
     *    'key'
     *    'op'
     *    'value'
     */

    protected $key;
    protected $op;
    protected $value;


    public function evaluate(nc_netshop_condition_context $context, $current_item = null) {
        return $this->compare($_SESSION[$this->key], $this->op, $this->value );
    }

    public function get_full_description(nc_netshop $netshop) {
        return '$_SESSION["' . $this->key . '"] — ' . $this->get_short_description($netshop);
    }

}