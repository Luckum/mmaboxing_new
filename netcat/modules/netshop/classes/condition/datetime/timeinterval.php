<?php

class nc_netshop_condition_datetime_timeinterval extends nc_netshop_condition {

    /**
     * Parameters:
     *   time_from
     *   time_to
     */

    protected $time_from;
    protected $time_to;

    public function __construct($parameters = array()) {
        $this->time_from = date("His", $this->get_datetime_value($parameters['time_from']));
        $this->time_to = date("His", $this->get_datetime_value($parameters['time_to']));
    }


    /**
     * @param nc_netshop_condition_context $context
     * @param null $current_item
     * @return bool
     */
    public function evaluate(nc_netshop_condition_context $context, $current_item = null) {
        // compare time as strings (conversion to integer is not so straightforward)
        $time = date("His", $context->get_time());
        return ($this->time_from <= $time) && ($time <= $this->time_to);
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
        return sprintf(NETCAT_MODULE_NETSHOP_COND_TIME_INTERVAL,
               $this->reformat_time($this->time_from),
               $this->reformat_time($this->time_to));
    }

    protected function reformat_time($time) {
        return substr($time, 0, 2) . ":" . substr($time, 2, 2);
    }
}