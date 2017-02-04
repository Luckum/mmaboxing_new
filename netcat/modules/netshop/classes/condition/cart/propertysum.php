<?php

class nc_netshop_condition_cart_propertysum extends nc_netshop_condition {

    /**
     * Parameters:
     *   field
     *   op
     *   value
     */
    protected $op;
    protected $value;

    protected $component_id;
    protected $field_name;
    protected $field_type;

    public function __construct($parameters = array()) {
        // assuming all components of the 'field' parameter must be set
        list($component_id, $field_name, $field_type) = explode(":", $parameters['field']);
        $this->component_id = $component_id;
        $this->field_name = $field_name;
        $this->field_type = $field_type;

        $this->op = $parameters['op'];
        $this->value = $this->convert_decimal_point($parameters['value']);
    }


    public function evaluate(nc_netshop_condition_context $context, $current_item = null) {
        $sum = 0;
        $items = $context->get_cart_contents();
        foreach ($items as $item) {
            if ($this->component_id == '*' || $item['Class_ID'] == $this->component_id) {
                $sum += $item[$this->field_name] * $item['Qty'];
            }
        }
        return $this->compare($sum, $this->op, $this->value);
    }


    public function get_full_description(nc_netshop $netshop) {
        $field_data = nc_netshop_condition_admin_helpers::get_field_data(
            $this->component_id,
            $this->field_name,
            $this->field_type,
            $netshop);

        if (!$field_data) { return "<em class='nc--status-error'>" . NETCAT_MODULE_NETSHOP_COND_NONEXISTENT_FIELD . "</em>"; } // what?!

        return sprintf(NETCAT_MODULE_NETSHOP_COND_CART_PROPERTYSUM, $field_data['description']) . ' ' .
               $this->get_short_description($netshop);
    }

}