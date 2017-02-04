<?php

class nc_netshop_condition_orders_sumperiod extends nc_netshop_condition {

    /**
     * Parameters:
     *   period_value
     *   period_type:  day|week|month|year
     *   op
     *   value
     */

    protected $period_value;
    protected $period_type;
    protected $op;
    protected $value;

    protected $date_from;

    public function __construct($parameters = array()) {
        $this->period_value = $parameters['period_value'];
        $this->period_type = $parameters['period_type'];
        $this->op = $parameters['op'];
        $this->value = $this->convert_decimal_point($parameters['value']);

        // start date is calculated only once, when the condition is instantiated
        $this->date_from = strtotime("-" . $parameters['period_value'] . " " . $parameters['period_type']);
    }


    public function evaluate(nc_netshop_condition_context $context, $current_item = null) {
        return $this->compare(
            $context->get_user_previous_orders_sum($this->date_from),
            $this->op,
            $this->value
        );
    }

    /**
     * @param nc_netshop $netshop
     * @return string
     */
    public function get_short_description(nc_netshop $netshop) {
        $period_name_forms = explode(" ", constant("NETCAT_MODULE_NETSHOP_COND_ORDERS_SUMPERIOD_" . strtoupper($this->period_type)));
        $period_name = nc_netshop_word_form($this->period_value, $period_name_forms[0], $period_name_forms[1], $period_name_forms[2]);

        return NETCAT_MODULE_NETSHOP_COND_ORDERS_SUMPERIOD_FOR . ' ' .
               $this->period_value . ' ' .
               $period_name . ' ' .
               $this->add_operator_description($netshop->format_price($this->value));
    }

}