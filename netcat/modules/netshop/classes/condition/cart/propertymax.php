<?php

class nc_netshop_condition_cart_propertymax extends nc_netshop_condition {

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
        $max_value = null;
        $items = $context->get_cart_contents();
        foreach ($items as $item) {
            if ($this->component_id == '*' || $item['Class_ID'] == $this->component_id) {
                if ($max_value == null) {
                    $max_value = $item[$this->field_name];
                }
                else {
                    $max_value = max($max_value, $item[$this->field_name]);
                }
            }
        }
        return $this->compare($max_value, $this->op, $this->value);
    }


    public function get_full_description(nc_netshop $netshop) {
        $field_data = nc_netshop_condition_admin_helpers::get_field_data(
            $this->component_id,
            $this->field_name,
            $this->field_type,
            $netshop);

        if (!$field_data) { return "<em class='nc--status-error'>" . NETCAT_MODULE_NETSHOP_COND_NONEXISTENT_FIELD . "</em>"; } // what?!

        return sprintf(NETCAT_MODULE_NETSHOP_COND_CART_PROPERTYMAX, $field_data['description']) . ' ' .
               $this->get_short_description($netshop);
    }

}