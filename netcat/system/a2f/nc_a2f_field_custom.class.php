<?php

/**
 * «Поле» формы, выводящее указанный HTML-фрагмент
 */
class nc_a2f_field_custom extends nc_a2f_field {

    protected $wrap = false;
    protected $html;

    public function render() {
        return ($this->wrap ? $this->render_prefix() . "<div class='ncf_value'>" : "") .
               $this->html .
               ($this->wrap ? "</div>" . $this->render_suffix() : "");
    }

    function render_value_field($html = true) {
        return '';
    }

}