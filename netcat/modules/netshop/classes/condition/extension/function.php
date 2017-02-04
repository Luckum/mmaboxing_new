<?php

class nc_netshop_condition_extension_function extends nc_netshop_condition {

    /**
     * Parameters:
     *    'function' — name of the function to call
     */

    protected $function;

    public function __construct($parameters = array()) {
        $function = trim(str_replace("()", "", $parameters['function']));
        if (function_exists($function)) {
            $this->function = $function;
        }
        else {
            trigger_error(__CLASS__ . ": specified function does not exist", E_USER_WARNING);
        }
    }

    public function evaluate(nc_netshop_condition_context $context, $current_item = null) {
        if ($this->function) {
            $function = $this->function;
            return (bool)$function($context, $current_item);
        }
        return false;
    }

    public function get_full_description(nc_netshop $netshop) {
        return $this->function . '() == true';
    }

    public function get_short_description(nc_netshop $netshop) {
        return $this->get_full_description($netshop);
    }


}