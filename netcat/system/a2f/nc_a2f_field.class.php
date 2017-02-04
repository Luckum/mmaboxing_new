<?php

/**
 * Базовый класс полей a2f
 */
abstract class nc_a2f_field {

    protected $type;
    protected $typename;
    protected $subtype = '';
    protected $caption;
    protected $name;
    protected $value;
    protected $default_value = "";
    protected $validate_regexp;
    protected $validate_error;
    protected $has_default = 0;

    /** @var nc_a2f */
    protected $parent;

    /**
     * @param array $field_settings
     * @param nc_a2f $parent
     */
    public function __construct(array $field_settings = null, nc_a2f $parent = null) {
        foreach ((array) $field_settings as $k => $v) {
            $this->$k = $v;
        }
        $this->parent = $parent;
    }

    /**
     * @param $value
     */
    public function save($value) {
        $this->value = $value;
    }

    /**
     * @param $value
     */
    function set_value($value) {
        $this->value = $value;
    }

    /**
     * @return int
     */
    public function has_default() {
        return $this->has_default;
    }

    /**
     * @param array $defaults
     */
    public function set_defaults(array $defaults) {
        foreach ($defaults as $property => $value) {
            if ($this->$property === null) { $this->$property = $value; }
        }
    }

    /**
     * @return array
     */
    public function get_extend_parameters() {
        return array();
    }

    /**
     * @return mixed
     */
    public function get_type() {
        return $this->type;
    }

    /**
     * @return string
     */
    public function get_full_type() {
        return $this->type . ($this->subtype ? "_" . $this->subtype : "");
    }

    /**
     * @return mixed
     */
    public function get_caption() {
        return $this->caption;
    }

    /**
     * проверка значения на основе validate_regexp и оповещение
     * родительского объекта (nc_a2f) в случае, если значение не
     * соответствует условиям
     * @param mixed
     * @return boolean passed check
     */
    public function validate_value($value) {
        if ($this->validate_regexp && !preg_match($this->validate_regexp, $value)) {
            $this->parent->set_validation_error($this->name, $this->validate_error);
            return false;
        }
        return true;
    }

    /**
     * @param $value
     * @return bool
     */
    public function validate($value) {
        return true;
    }

    /**
     * @param bool $use_default
     * @return string
     */
    public function get_value($use_default = true) {
        if (isset($this->value) || !$use_default) {
            return ($this->value);
        }
        return $this->default_value;
    }

    /**
     * @param string|bool $template
     * @return string
     */
    public function render($template = "") {
        if ($template === false) { return ""; }

        if (!$template) {
            $ret = $this->render_prefix() .
                   ($this->parent->should_show_default_values()
                       ? $this->render_default_value()
                       : "" ).
                   $this->render_value_field() .
                   $this->render_suffix();
        }
        else {
            $ret = str_replace(
                            array("%CAPTION", "%DEFAULT", "%VALUE"),
                            array($this->render_prefix(false), $this->render_default_value(false), $this->render_value_field(false)),
                            $template
            );
            $ret .= $this->render_suffix(false);
        }

        return $ret;
    }

    /**
     * @param string $template
     * @return mixed
     */
    public function render_settings($template = "") {
        $this->typename = constant('NETCAT_CUSTOM_TYPENAME_' . strtoupper($this->type));
        $ret = str_replace(array('%CAPTION', '%NAME', '%TYPENAME'),
                           array($this->caption, $this->name, $this->typename),
                           $template);

        return $ret;
    }

    /**
     *
     */
    protected function render_prefix($html = true) {

        $err = $this->parent->get_field_error($this->name);

        if ($html) {
            $ret = "<div class='ncf_row".($err ? " ncf_error" : "")."'>".
                    "<div class='".( $this->type == 'divider' ? 'ncf_divider' : 'ncf_caption' )."'>{$this->caption}</div>";
        } else {
            $ret = $this->caption;
        }

        return $ret;
    }

    /**
     * @param bool $html
     * @return mixed
     */
    abstract public function render_value_field($html = true);

    /**
     *
     */
    protected function render_default_value($html = true) {
        $ret = '';
        if ($this->type != 'divider') {
            if ($html) {
                $ret = "<div class='ncf_default'>{$this->default_value}</div>";
            } else {
                $ret = $this->default_value;
            }
        }

        return $ret;
    }

    /**
     *
     */
    protected function render_suffix($html = true) {
        $ret = '';
        if ($html) { $ret = "</div>\n"; }
        return $ret;
    }

    /**
     *
     */
    protected function get_field_name($added = '') {
        $array_name = $this->parent->get_array_name();
        if ($added) $added = '['.$added.']';
        return ($array_name ? $array_name."[".$this->name."]".$added : $this->name.$added );
    }

    /**
     * @return array
     */
    public function get_subtypes() {
        return array();
    }

}