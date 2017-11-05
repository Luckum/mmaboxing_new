<?php

class nc_a2f_field_float extends nc_a2f_field {

    protected $size;
    protected $has_default = 1;

    public function render_value_field($html = true) {
        if (!$this->size) { $this->size = 20; }
        $ret = "<input name='".$this->get_field_name()."' type='text' value='".htmlspecialchars(($this->value ? $this->value : $this->default_value), ENT_QUOTES)."' size='".$this->size."'  class='ncf_value_text'>";
        if ($html) {
            $ret = "<div class='ncf_value'>".$ret."</div>\n";
        }

        return $ret;
    }

    public function get_extend_parameters() {
        return array('min' => array('type' => 'int', 'caption' => NETCAT_CUSTOM_EX_MIN),
                'max' => array('type' => 'int', 'caption' => NETCAT_CUSTOM_EX_MAX));
    }

    public function validate($value) {
        $value = str_replace(',', '.', $value);
        if ($value != strval(doubleval($value))) {
            $this->parent->set_validation_error($this->name, NETCAT_CUSTOM_ERROR_REQUIRED_FLOAT);
            return false;
        }

        if (isset($this->min) && $this->min > $value) {
            $this->parent->set_validation_error($this->name, sprintf(NETCAT_CUSTOM_ERROR_MIN_VALUE, $this->min));
            return false;
        }


        if (isset($this->max) && $this->max < $value) {
            $this->parent->set_validation_error($this->name, sprintf(NETCAT_CUSTOM_ERROR_MAX_VALUE, $this->max));
            return false;
        }

        return true;
    }

}