<?php

/**
 * Класс для реализации поля типа "Список"
 */
class nc_a2f_field_select extends nc_a2f_field {

    protected $empty_option_text = NETCAT_MODERATION_LISTS_CHOOSE;
    protected $has_default = 1;

    //  возможные значения (значение => описание)
    protected $values;

    /**
     * <select multiple>
     * Значение ($this->value, $this->default_value) задаются в виде строки,
     * разделённой запятыми ($this->multiple_delimiter), без пробелов
     */
    protected $multiple = false;
    // разделитель значений для multiple-полей
    protected $multiple_delimiter = ",";
    // size для select multiple
    protected $size;

    /**
     * @param bool $html
     * @return string
     */
    public function render_value_field($html = true) {
        $ret = $this->multiple ? $this->render_value_field_multiple()
                               : $this->render_value_field_single();

        if ($html) {
            $ret = "<div class='ncf_value'>" . $ret . "</div>\n";
        }

        return $ret;
    }

    /**
     * @return string
     */
    protected function render_value_field_single() {
        // текущее значение
        $current_value = $this->get_value();

        $ret = "<select name='" . $this->get_field_name() . "'  class='ncf_value_select'>\n";

        // если нет значения по умолчанию - выводим пустую строку тоже
        if (!$this->default_value) {
            $ret .= "<option value=''>" . $this->empty_option_text . "</option>\n";
        }

        foreach ((array)$this->values as $k => $v) {
            $ret .= "<option value='" . htmlspecialchars($k, ENT_QUOTES) . "'" .
                    ($k == $current_value ? " selected='selected'" : "") . ">" .
                    htmlspecialchars($v) . "</option>\n";
        }

        $ret .= "</select>";

        return $ret;
    }

    /**
     * @return string
     */
    protected function render_value_field_multiple() {
        $delimiter = $this->multiple_delimiter;
        $current_value = $this->get_value();
        $value_to_compare = $delimiter . $current_value . $delimiter;

        $field_name = $this->get_field_name();
        $select_id = $this->get_multiple_id();

        $ret = "<select multiple id='$select_id' class='ncf_value_select ncf_value_select_multiple'" .
                ($this->size ? " size='$this->size'" : "") .
               ">\n";

        foreach ((array)$this->values as $k => $v) {
            $is_selected = (strpos($value_to_compare, $delimiter . $k . $delimiter) !== false);
            $ret .= "<option value='" . htmlspecialchars($k, ENT_QUOTES) . "'" .
                    ($is_selected ? " selected='selected'" : "") . ">" .
                    htmlspecialchars($v) . "</option>\n";
        }

        $nc = '$nc';
        $js_delimiter = json_encode($delimiter);
        $ret .= "</select>" .
                "<input type='hidden' name='$field_name' value='" .
                htmlspecialchars($current_value, ENT_QUOTES) . "' id='{$select_id}_value'>" .
                "<script>
                    $nc('#$select_id').change(function() {
                        var value = $nc(this).val();
                        if (value) {
                            $nc('#{$select_id}_value').val(value.join($js_delimiter));
                        }
                    });
                </script>";

        return $ret;
    }

    /**
     *
     */
    protected function get_multiple_id() {
        static $last_id = 0;
        return "nc_ncf_multiple_select_" . (++$last_id);
    }

    /**
     * @param bool $html
     * @return string
     */
    public function render_default_value($html = true) {
        $ret = $this->values[$this->default_value];

        if ($html) {
            $ret = "<div class='ncf_default'>" . $ret . "</div>";
        }

        return $ret;
    }

    /**
     * @return array
     */
    public function get_subtypes() {
        return array('static', 'classificator', 'sql');
    }

}

class nc_a2f_field_select_sql extends nc_a2f_field_select {

    protected $sqlquery;

    public function get_extend_parameters() {
        return array('sqlquery' => array('type' => 'string', 'caption' => NETCAT_CUSTOM_EX_QUERY));
    }

    public function render_value_field($html = true) {
        $nc_core = nc_Core::get_object();

        // просто узнаем элементы списка
        $res = $nc_core->db->get_results($this->sqlquery, ARRAY_A);

        if ($nc_core->db->is_error) {
            return NETCAT_CUSTOM_ONCE_ERROR_QUERY;
        }

        if ($res) {
            foreach ($res as $v) {
                $this->values[$v['id']] = $v['name'];
            }
        }

        // сама прорисовка реализована в родительском классе
        return parent::render_value_field($html);
    }

}

class nc_a2f_field_select_static extends nc_a2f_field_select {

    public function get_extend_parameters() {
        return array('values' => array('type' => 'static', 'caption' => NETCAT_CUSTOM_EX_ELEMENTS));
    }

}

class nc_a2f_field_select_classificator extends nc_a2f_field_select {

    protected $classificator;

    public function get_extend_parameters() {
        return array('classificator' => array('type' => 'classificator', 'caption' => NETCAT_CUSTOM_EX_CLASSIFICATOR));
    }

    protected function load_classificator() {
        static $cache = array();

        if (!isset($cache[$this->classificator])) {
            $db = nc_db();

            $clft = $db->escape($this->classificator);
            $res = $db->get_results("SELECT * FROM `Classificator` WHERE `Table_Name` = '" . $clft . "' ", ARRAY_A);

            if (!$res) {
                return sprintf(NETCAT_CUSTOM_ONCE_ERROR_CLASSIFICATOR, $this->classificator);
            }

            switch ($res['Sort_Type']) {
                case 1:
                    $sort = "`" . $clft . "_Name`";
                    break;
                case 2:
                    $sort = "`" . $clft . "_Priority`";
                    break;
                default:
                    $sort = "`" . $clft . "_ID`";
            }

            // просто узнаем элементы списка
            $elements = $db->get_results("SELECT `" . $clft . "_ID` as `id`, `" . $clft . "_Name` as `name`
                   FROM `Classificator_" . $clft . "`
                   WHERE `Checked` = '1'
                   ORDER BY " . $sort . " " . ($res['Sort_Direction'] == 1 ? "DESC" : "ASC") . "", ARRAY_A);

            if (!$elements) {
                return sprintf(NETCAT_CUSTOM_ONCE_ERROR_CLASSIFICATOR_EMPTY, $res['Classificator_Name']);
            }

            $cache[$this->classificator] = $elements;
        }

        foreach ($cache[$this->classificator] as $v) {
            $this->values[$v['id']] = $v['name'];
        }

        return true;
    }

    public function render_value_field($html = true) {
        $loading_result = $this->load_classificator();
        if ($loading_result !== true) { return $loading_result; }

        // сама прорисовка реализована в родительском класса
        return parent::render_value_field($html);
    }

}