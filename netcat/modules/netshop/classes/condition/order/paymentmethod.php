<?php

class nc_netshop_condition_order_paymentmethod extends nc_netshop_condition {

    /**
     * Parameters:
     *    'value'  − ID of the payment method
     */

    protected $op;
    protected $value;


    public function evaluate(nc_netshop_condition_context $context, $current_item = null) {
        return $this->compare(
            $context->get_order_property('PaymentMethod'),
            $this->op,
            $this->value
        );
    }

    /**
     * Короткое описание (только значение, для повторяющихся условий)
     * @param nc_netshop $netshop
     * @return string
     */
    public function get_short_description(nc_netshop $netshop) {
        static $cache = array();
        if (!isset($cache[$this->value])) {
            try {
                $method = new nc_netshop_payment_method($this->value);
                $cache[$this->value] = nc_ui_helper::get()->hash_link(
                    "module.netshop.payment.edit($this->value)",
                    $method->get('name')
                );
            }
            catch (nc_record_exception $e) {
                $cache[$this->value] = "<em class='nc--status-error'>" . NETCAT_MODULE_NETSHOP_COND_NONEXISTENT_DELIVERY_METHOD . "</em>";
            }
        }
        return $this->add_operator_description($cache[$this->value]);
    }
}