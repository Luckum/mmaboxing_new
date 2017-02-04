<?php

class nc_module_tpl_iew {
    private $template = null;
    private static $instanse = null;

    public function __construct() {
        self::$instanse = $this;
        $this->template = new nc_tpl(nc_Core::get_object()->MODULE_TEMPLATE_FOLDER, nc_Core::get_object()->db);
    }
    
    public function load($id, $type) {
        $this->template->load($id, $type, "/$id/$type/");
    }
    
    public function get_field($field){
        $field_path = $this->get_field_path($field);        
        return nc_check_file($field_path) ? nc_get_file($field_path) : false;
    }
    
    public function get_field_path($field) {
        return $this->template->fields->get_path($field);
    }
    
    public static function get_instanse() {
        return self::$instanse;
    }
}