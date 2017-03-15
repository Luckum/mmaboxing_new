<?php

/**
 * Класс для реализации поля типа "Логическая переменная"
 */
class nc_a2f_field_checkbox extends nc_a2f_field {

    public function render_value_field($html = true) {

        $ret = "<input name='" . $this->get_field_name() . "' type='checkbox' " .
               ($this->value == true ? " checked='checked'" : "") .
               "  class='ncf_value_checkbox'>";

        if ($html) {
            $ret = "<div class='ncf_value'>" . $ret . "</div>\n";
        }

        return $ret;
    }

}