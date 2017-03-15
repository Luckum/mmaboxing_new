<?php

/**
 * [Array to Form]
 * Класс для преобразования массива с данными + массива с настройками
 * в элементы html-формы и сохранения результатов формы в виде строки
 */
class nc_a2f {

    /** @var nc_a2f_field[]   поля формы */
    protected $fields = array();

    // ошибки, возникшие при проверке значений
    protected $validation_errors = array();
    /**
     * префикс: использовать у элементов формы в названии элементов
     * (e.g. если prefix='settings', названия полей формы будут
     *  вида name='settings[field1]')
     */
    protected $array_name;
    protected $settings_array;

    /** @var array  параметры полей по умолчанию */
    protected $field_defaults = array();

    /** @var bool  показывать колонку со значениями по умолчанию? */
    protected $show_default_values = true;

    /** @var bool  показывать строку с заголовками столбцов таблицы? */
    protected $show_header = true;

    /**
     * конструктор
     * @param mixed массив с настройками или соответствующая строка
     *   E.g. $settings_array = array(
     *                            "field1_name" => array("type" => "string")
     *                          );
     * @param string   имя массива в форме
     * @param string
     */
    public function __construct($field_settings, $array_name = "nc_a2f") {

        $this->array_name = $array_name;
        $settings_array = is_array($field_settings) ? $field_settings : $this->eval_value($field_settings);

        if (!is_array($settings_array)) {
            trigger_error("nc_a2f::nc_a2f() - first parameter is not an array or cannot be evaluated as an array", E_USER_WARNING);
            return;
        }

        // $this->settings_array = $settings_array;

        foreach ($settings_array as $field_name => $field_settings) {
            $type = $field_settings['type'];
            if (!$type) {
                trigger_error("(nc_a2f) missing type for the field '" . $field_name . "'", E_USER_WARNING);
                continue;
            }
            $class_name = "nc_a2f_field_" . $type;
            if (isset($field_settings['subtype']) && $field_settings['subtype']) {
                $class_name .= '_' . $field_settings['subtype'];
            }

            if (!class_exists($class_name)) {
                trigger_error("(nc_a2f) wrong subtype '" . $field_settings['subtype'] . "' for the field '" . $field_name . "'. " . $class_name . " not found.", E_USER_WARNING);
                continue;
            }

            // push name into settings array
            $field_settings['name'] = $field_name;
            $this->fields[$field_name] = new $class_name($field_settings, $this);
        }
    }

    public function get_fields() {
        return $this->fields;
    }

    /**
     * @param $field_type
     * @param array $defaults
     * @return $this
     */
    public function set_field_defaults($field_type, array $defaults) {
        foreach ($this->fields as $field) {
            if ($field->get_type() == $field_type || $field->get_full_type() == $field_type) {
                $field->set_defaults($defaults);
            }
        }
        return $this;
    }

    /**
     * @param bool $new_value
     * @return $this
     */
    public function show_default_values($new_value) {
        $this->show_default_values = (bool)$new_value;
        return $this;
    }

    /**
     * @param $new_value
     * @return $this
     */
    public function show_header($new_value) {
        $this->show_header = (bool)$new_value;
        return $this;
    }

    /**
     * @return bool
     */
    public function should_show_default_values() {
        return $this->show_default_values;
    }

    /**
     * @param array $values
     * @return $this
     */
    public function save($values) {
        $values = (array) $values;
        foreach ($this->fields as $name => $f) {
            $f->save($values[$name]);
        }
        return $this;
    }

    /**
     * @param string|array|ArrayAccess|Iterator $values
     * @return $this
     */
    public function set_value($values) {
        if (!(is_array($values) || (is_object($values) && $values instanceof ArrayAccess && $values instanceof Iterator))) {
            $values = $this->eval_value($values);
        }

        foreach ($this->fields as $name => $f) {
            $value = isset($values[$name]) ? $values[$name] : null;
            $f->set_value($value);
        }
        return $this;
    }

    /**
     * Alias for set_value()
     * @param $values
     * @return $this
     */
    public function set_values($values) {
        return $this->set_value($values);
    }

    /**
     * Eval PHP-кода в значение
     */
    public function eval_value($value_string) {
        if (!$value_string) { return $value_string; }

        $ret = "";
        $value_string = nc_preg_replace("/;\s*$/", "", $value_string);
        @eval("\$ret = ($value_string);");
        if (strlen($value_string) > 1 && !is_array($ret)) {
            trigger_error("nc_a2f::eval_value - wrong parameter?<pre>" . htmlspecialchars($value_string) . "</pre>", E_USER_WARNING);
        }
        return $ret;
    }

    /**
     * Вывод элементов
     * @param string|bool $header
     * @param string|bool $template
     * @param string|bool $footer
     * @param string|bool $divider
     * @return string
     */
    public function render($header = "", $template = "", $footer = "", $divider = "") {
        $ret = $this->render_header($header);

        foreach ($this->fields as $field) {
            $field_template = ($field->get_type() == 'divider' && $divider !== false)
                ? $divider
                : $template;

            $ret .= $field->render($field_template);
        }

        $ret .= $this->render_footer($footer);

        return $ret;
    }

    /**
     * @param bool|string $template
     * @return string
     */
    protected function render_header($template = "") {
        if ($template === false) { return ""; }

        if (!$template) {
            $ret = "<div class='ncf_container'>\n";
            if ($this->show_header) {
                $ret .=
                    "<div class='ncf_header_row'>" .
                        "<div class='ncf_header_caption'>" . CONTROL_CLASS_CUSTOM_SETTINGS_PARAMETER . "</div>" .
                        ($this->show_default_values
                            ? "<div class='ncf_header_default'>" . CONTROL_CLASS_CUSTOM_SETTINGS_DEFAULT . "</div>"
                            : "").
                        "<div class='ncf_header_value'>" . CONTROL_CLASS_CUSTOM_SETTINGS_VALUE . "</div>" .
                    "</div>\n";
            }
        }
        else {
            $ret = str_replace(
                array("%CAPTION", "%DEFAULT", "%VALUE"),
                array(CONTROL_CLASS_CUSTOM_SETTINGS_PARAMETER, CONTROL_CLASS_CUSTOM_SETTINGS_DEFAULT, CONTROL_CLASS_CUSTOM_SETTINGS_VALUE),
                $template
            );
        }

        return $ret;
    }

    /**
     * @param string|bool $template
     * @return string
     */
    protected function render_footer($template = "") {
        if ($template === false) { return ""; }
        if (!$template) { return "</div>"; }
        else { return $template; }
    }

    /**
     * @param string $header
     * @param string $template
     * @param string $footer
     * @param string $divider
     * @return string
     */
    public function render_settings($header = "", $template = "", $footer = "", $divider = "") {
        $ret = $header;

        foreach ($this->fields as $field) {
            $field_template = $field->get_type() == 'divider' && $divider
                ? $divider
                : ($template ? $template : "");
            $ret .= $field->render_settings($field_template);
        }

        $ret .= $footer;

        return $ret;
    }

    /**
     * Получить строку со значениями (php $values_array)
     * @return string  'f1'=>'value', 'f2=>'value'...
     */
    public function get_values_as_string() {
        $all_values = array();
        foreach ($this->fields as $field_name => $field) {
            $val = $this->fields[$field_name]->get_value(); // don't use default value
            if (isset($val)) {
                $val = str_replace("'", "\\'", $val);
                $all_values[] = "'" . $field_name . "' => '" . $val . "'";
            }
        }

        if (sizeof($all_values)) {
            $ret = "$" . $this->array_name . " = array(" . join(', ', $all_values) . ");";
            return $ret;
        }
        return "";
    }

    /**
     * Получить все значения в виде массива
     * @return array
     */
    public function get_values_as_array() {
        $values = array();
        foreach ($this->fields as $field_name => $field) {
            $values[$field_name] = $this->fields[$field_name]->get_value();
        }
        return $values;
    }

    /**
     * @param $ar
     * @internal param int $l
     * @return string
     */
    public static function array_to_string($ar /*, $l = 1*/) {
        return var_export($ar, true);
//        if (empty($ar)) {
//            return "";
//        }
//        $ret = "array(\r\n";
//        foreach ($ar as $k => $v) {
//            $ret .= str_repeat("\t", $l) . "'" . $k . "' => ";
//            $ret .= is_array($v) ? self::array_to_string($v, $l + 1) : "'" . $v . "'";
//            $ret .= ",\r\n";
//        }
//        $ret = nc_substr($ret, 0, nc_strlen($ret) - 3);
//        $ret .= ')';
//        return $ret;
    }

    /**
     * принять сообщение об ошибке от поля
     */
    public function set_validation_error($field_name, $error_msg) {
        if (!$error_msg) {
            $error_msg = str_replace("%NAME", $this->fields[$field_name]->get_caption(), NETCAT_MODERATION_MSG_TWO);
        }
        $this->validation_errors[$field_name] = $error_msg;
    }

    /**
     * @return string
     */
    public function get_validation_errors() {
        if (!sizeof($this->validation_errors)) { return ""; }

        $ret = "";
        foreach ($this->validation_errors as $field => $error) {
            $ret .= $this->fields[$field]->get_caption() . ": $error<br>\n";
        }

        return $ret;
    }

    /**
     * @param $field_name
     * @return mixed
     */
    public function get_field_error($field_name) {
        return $this->validation_errors[$field_name];
    }

    /**
     * были ли ошибки при заполнении формы?
     * @return boolean
     */
    public function has_errors() {
        return (sizeof($this->validation_errors) ? true : false);
    }

    /**
     * @return string
     */
    public function get_array_name() {
        return $this->array_name;
    }

    /**
     * @param $values
     * @return bool
     */
    public function validate($values) {
        $result = true;
        if (!is_array($values)) {
            $values = $this->eval_value($values);
        }

        foreach ($this->fields as $name => $f) {
            if (!$f->validate($values[$name])) {
                $result = false;
            }
        }

        return $result;
    }

}