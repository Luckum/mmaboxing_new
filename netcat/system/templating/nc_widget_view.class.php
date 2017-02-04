<?php

class nc_widget_view {

    private $template = null;

    public function __construct($path, nc_Db $db) {
        $this->template = new nc_tpl($path, $db);
    }

    public function load($id) {
        $this->template->load($id, 'Widget_Class', "/$id/");
        return $this;
    }

    public function get_field_path($field) {
        return $this->template->fields->get_path($field);
    }
}