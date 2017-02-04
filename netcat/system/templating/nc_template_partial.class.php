<?php

/**
 * Класс для вспомогательных шаблонов (partials) в макетах дизайна
 */
class nc_template_partial {

    //--------------------------------------------------------------------------

    protected $data = array();

    protected $template_view;
    protected $partial_file;

    //--------------------------------------------------------------------------

    public function __construct(nc_template_view $template_view, $partial_file, array $data = array()) {

        $this->template_view = $template_view;

        if (nc_check_file($partial_file)) {
            $this->partial_file = $partial_file;
        }

        $this->data = $data;
        $this->data['nc_core'] = nc_core();
        $this->data['db']      = nc_db();
    }

    //--------------------------------------------------------------------------

    /**
     * Рендренг шаблона
     */
    public function make() {
        $this->view_file = $this->path . $this->view;

        extract($GLOBALS);

        if ($this->data) {
            extract($this->data);
        }

        ob_start();

        include $this->partial_file;

        return ob_get_clean();
    }

    //--------------------------------------------------------------------------

    public function __toString() {
        return $this->make();
    }

    //-------------------------------------------------------------------------

    public function __set($name, $value) {
        $this->with($name, $value);
    }

    //-------------------------------------------------------------------------

    public function __get($name) {
        return isset($this->data[$name]) ? $this->data[$name] : null;
    }

    //--------------------------------------------------------------------------

    /**
     * Присвоение переменной шаблона
     * @param  string $key  Название переменной
     * @param  mixed $value Значение переменой
     * @return $this
     */
    public function with($key, $value) {
        $this->data[$key] = $value;
        return $this;
    }

    //-------------------------------------------------------------------------

    public function value($name, $default = null) {
        return isset($this->data[$name]) ? $this->data[$name] : $default;
    }

    //--------------------------------------------------------------------------

    public function partial($name, $data = array()) {
        return $this->template_view->partial($name, $data);
    }

    //-------------------------------------------------------------------------
}