<?php

/* $Id: nc_modules.class.php 6611 2012-04-05 12:09:13Z russuckoff $ */
if (!class_exists("nc_System")) die("Unable to load file.");

class nc_Modules extends nc_System {

    //--------------------------------------------------------------------------

    protected $db;

    //--------------------------------------------------------------------------

    public function __construct() {
        // load parent constructor
        parent::__construct();

        // system superior object
        $nc_core = nc_Core::get_object();
        // system db object
        if (is_object($nc_core->db)) $this->db = $nc_core->db;
    }

    //--------------------------------------------------------------------------

    public function __get($keyword)
    {
        static $modules = array();

        if (isset($modules[$keyword])) {
            return $modules[$keyword];
        }

        if ($this->get_by_keyword($keyword)) {
            $class_name = 'nc_' . $keyword;

            if (class_exists($class_name, false) && method_exists($class_name, 'get_instance')) {
                $modules[$keyword] = call_user_func(array($class_name, 'get_instance'));

                return $modules[$keyword];
            }
        }

        return false;
    }

    //--------------------------------------------------------------------------

    public function get_data($reload = false, $ignore_check = false) {
        // set as static
        static $all_modules_data = array();
        // get data from base, once
        if (empty($all_modules_data) || $reload) {
            $this->db->last_error = '';
            $all_modules_data = $this->db->get_results("SELECT * FROM `Module`", ARRAY_A);

            // на случай, если поля нет
            if ($this->db->last_error && strstr($this->db->last_error, 'Checked')) {
                $this->db->query("ALTER TABLE `Module` ADD COLUMN `Checked` TINYINT(4) DEFAULT 1");
                return $this->get_data($ignore_check);
            }
        }
        if (!empty($all_modules_data) && !$ignore_check) {
            $res = array();
            foreach ($all_modules_data as $v) {
                if ($v['Checked']) $res[] = $v;
            }
            return $res;
        }
        // result data array
        return!empty($all_modules_data) ? $all_modules_data : false;
    }

    //--------------------------------------------------------------------------

    /**
     * Check installed module by keyword
     *
     * @param string module keyword
     * @param bool `Installed` column
     *
     * @return array module data or false
     */
    public function get_by_keyword($keyword, $installed = true) {
        // get all modules data
        $all_modules_data = $this->get_data();
        // `Module` table empty
        if (empty($all_modules_data)) return false;
        // walk on array
        foreach ($all_modules_data AS $module_data) {
            if ($module_data['Keyword'] != $keyword) continue;
            if ($installed) {
                if ($module_data['Installed']) {
                    return $module_data;
                } else {
                    return false;
                }
            }
            return $module_data;
        }

        return false;
    }

    //--------------------------------------------------------------------------

    public function load_env($language = "", $only_inside_admin = false, $reload = false, $ignore_check = false) {
        // dummy
        // global $MODULE_VARS;
        // system superior object
        $nc_core = nc_Core::get_object();

        // set static variable
        static $result = array();

        // check
        if (empty($result) || $reload) {

            $modules_data = $this->get_data($reload, $ignore_check);

            if (empty($modules_data)) return false;

            // determine language
            if (!$language && is_object($nc_core->subdivision)) {
                $language = $nc_core->subdivision->get_current("Language");
            }

            if (!$language && is_object($nc_core->catalogue)) {
                $language = $nc_core->catalogue->get_current("Language");
            }

            if (!$language) {
                $language = $nc_core->lang->detect_lang(1);
            }

            if (!$language) {
                return false;
            }

            // MODULE_VARS должен быть доступен в файлах модуля
            $MODULE_VARS = $this->get_module_vars();

            foreach ($modules_data as $row) {
                // load modules marked as "inside_admin" if only_inside_admin == true
                if ($only_inside_admin && !$row['Inside_Admin']) continue;
                // module keyword
                $keyword = $row['Keyword'];
                // include language file
                if (is_file($nc_core->get_variable("MODULE_FOLDER").$keyword."/".$language.".lang.php")) {
                    require_once ($nc_core->get_variable("MODULE_FOLDER").$keyword."/".$language.".lang.php");
                } else {
                    require_once ($nc_core->get_variable("MODULE_FOLDER").$keyword."/en.lang.php");
                }
                // include the module itself
                if (is_file($nc_core->get_variable("MODULE_FOLDER").$keyword."/function.inc.php")) {
                    require_once ($nc_core->get_variable("MODULE_FOLDER").$keyword."/function.inc.php");
                }
            }

            // module_vars может измениться в самом модуле
            $result = $MODULE_VARS;
        }
        // return result
        return $result;
    }

    //--------------------------------------------------------------------------

    public function get_module_vars() {
        // set static variable
        static $result;
        // check
        if (!isset($result)) {
            $modules_data = $this->get_data();

            if (empty($modules_data)) return false;

            foreach ($modules_data as $row) {
                // module keyword and params
                $keyword = $row['Keyword'];
                $params = $row['Parameters'];

                // parse module params
                $query_string = str_replace(array("\n", "\r\n"), "&", $params);
                parse_str($query_string, $result[$keyword]);

                // modules parameters
                if (!empty($result[$keyword])) {
                    foreach ($result[$keyword] as $key => $value) {
                        $result[$keyword][$key] = trim($value);
                    }
                }
            }
        }
        // return result
        return $result;
    }

    //--------------------------------------------------------------------------

    public function get_vars($module, $item = "") {
        // get data for all modules
        $modules_vars = $this->get_module_vars();
        // vars for this module
        if (!empty($modules_vars[$module])) {
            // if item requested return item value
            if ($item) {
                return is_array($modules_vars[$module]) && array_key_exists($item, $modules_vars[$module]) ? $modules_vars[$module][$item] : false;
            } else {
                return $modules_vars[$module];
            }
        }
        // default
        return false;
    }

    //--------------------------------------------------------------------------

}