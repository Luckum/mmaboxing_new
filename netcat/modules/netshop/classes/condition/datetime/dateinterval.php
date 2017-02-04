<?php

class nc_netshop_condition_datetime_dateinterval extends nc_netshop_condition {

    /**
     * Parameters:
     *   date_from
     *   date_to
     */
    protected $date_from;
    protected $date_to;

    public function __construct($parameters = array()) {
        $this->date_from = $this->get_datetime_value($parameters['date_from']);
        $this->date_to = $this->get_datetime_value($parameters['date_to']);
    }


    /**
     * @param nc_netshop_condition_context $context
     * @param null $current_item
     * @return bool
     */
    public function evaluate(nc_netshop_condition_context $context, $current_item = null) {
        $time = $context->get_time();
        return ($this->date_from <= $time) && ($time <= $this->date_to);
    }

    public function get_full_description(nc_netshop $netshop) {
        return $this->get_short_description($netshop);
    }

    public function get_short_description(nc_netshop $netshop) {
        return NETCAT_MODULE_NETSHOP_COND_DATE_FROM . ' ' .
               date(NETCAT_MODULE_NETSHOP_DATE_FORMAT, $this->date_from) . ' ' .
               NETCAT_MODULE_NETSHOP_COND_DATE_TO . ' ' .
               date(NETCAT_MODULE_NETSHOP_DATE_FORMAT, $this->date_to);
    }


}