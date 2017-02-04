<?php

class nc_netshop_condition_orders_sumdates extends nc_netshop_condition {

    /**
     * Parameters:
     *   date_from
     *   date_to
     *   op
     *   value
     */

    protected $date_from;
    protected $date_to;
    protected $op;
    protected $value;

    public function __construct($parameters = array()) {
        $this->op = $parameters['op'];
        $this->value = $this->convert_decimal_point($parameters['value']);
        $this->date_from = $this->get_datetime_value($parameters['date_from']);
        $this->date_to = $this->get_datetime_value($parameters['date_to']);
    }

    public function evaluate(nc_netshop_condition_context $context, $current_item = null) {
        return $this->compare(
            $context->get_user_previous_orders_sum($this->date_from, $this->date_to),
            $this->op,
            $this->value
        );
    }

    /**
     * @param nc_netshop $netshop
     * @return string
     */
    public function get_short_description(nc_netshop $netshop) {
        return NETCAT_MODULE_NETSHOP_COND_DATE_FROM . ' ' .
               date(NETCAT_MODULE_NETSHOP_DATE_FORMAT, $this->date_from) . ' ' .
               NETCAT_MODULE_NETSHOP_COND_DATE_TO . ' ' .
               date(NETCAT_MODULE_NETSHOP_DATE_FORMAT, $this->date_to) . ' ' .
               $this->add_operator_description($netshop->format_price($this->value));
    }

}