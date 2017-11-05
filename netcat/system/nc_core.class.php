<?php

/**
 *
 * @property nc_ui $ui
 */
class nc_Core extends nc_System {

    public $DOCUMENT_ROOT, $SYSTEM_FOLDER, $INCLUDE_FOLDER, $ROOT_FOLDER, $SUB_FOLDER, $MODULE_FOLDER, $ADMIN_FOLDER,
           $HTTP_ROOT_PATH, $HTTP_FILES_PATH, $FILES_FOLDER,
           $TEMPLATE_FOLDER, $CLASS_TEMPLATE_FOLDER, $WIDGET_TEMPLATE_FOLDER,
           $NC_JQUERY_PATH;

    public $NC_UNICODE, $NC_CHARSET, $PHP_AUTH_LANG, $PHP_TYPE, $MYSQL_ENCRYPT,
           $REDIRECT_STATUS, $AUTHORIZATION_TYPE,
           $NC_DEPRECATED_DISABLED, $NC_REDIRECT_DISABLED,
           $use_gzip_compression,
           $AUTHORIZE_BY, $SECURITY_XSS_CLEAN,
           $HTTP_HOST, $REQUEST_URI, $DOMAIN_NAME;

    public $inside_admin = false;
    public $admin_mode = false;
    public $developer_mode = false;
    public $is_trial = false; // trial version?
    public $beta = false;
    protected $settings = array();

    /** @var  nc_db */
    public $db;
    // значение настроек
    /** @var  nc_Page */
    public $page;

    /** @var  nc_Widget */
    public $widget;

    /** @var  nc_Event */
    public $event;

    /** @var  nc_Files */
    public $files;

    /** @var  nc_file_info */
    public $file_info;

    /** @var  nc_Gzip */
    public $gzip;

    /** @var  nc_Input */
    public $input;

    /** @var  nc_Lang */
    public $lang;

    /** @var  nc_Modules */
    public $modules;

    /** @var  nc_Token */
    public $token;

    /** @var  nc_Url */
    public $url;

    /** @var  nc_Utf8 */
    public $utf8;

    /** @var  nc_Security */
    public $security;

    /** @var  nc_Catalogue */
    public $catalogue;

    /** @var  nc_Subdivision */
    public $subdivision;

    /** @var  nc_Sub_Class */
    public $sub_class;

    /** @var  nc_Component */
    public $component;

    /** @var  nc_Template */
    public $template;

    /** @var  nc_User */
    public $user;

    /** @var  nc_Message */
    public $message;

    /** @var  nc_Trash */
    public $trash;


    // тип страницы (html, rss, xml)
    protected $page_type;
    protected $macrofuncs;

    /**
     * @var array [ class_prefix => path ]
     */
    protected $class_autoload_paths = array();

    protected $component_instances = array();

    protected function __construct() {
        global $SYSTEM_FOLDER, $TEMPLATE_FOLDER, $CLASS_TEMPLATE_FOLDER, $WIDGET_TEMPLATE_FOLDER, $JQUERY_FOLDER, $MODULE_TEMPLATE_FOLDER;
        global $SECURITY_XSS_CLEAN;

        $this->set_variable("SYSTEM_FOLDER", $SYSTEM_FOLDER);
        $this->set_variable("TEMPLATE_FOLDER", $TEMPLATE_FOLDER);
        $this->set_variable("CLASS_TEMPLATE_FOLDER", $CLASS_TEMPLATE_FOLDER);
        $this->set_variable("WIDGET_TEMPLATE_FOLDER", $WIDGET_TEMPLATE_FOLDER);
        $this->set_variable("JQUERY_FOLDER", $JQUERY_FOLDER);
        $this->set_variable("MODULE_TEMPLATE_FOLDER", $MODULE_TEMPLATE_FOLDER);
        $this->set_variable("SECURITY_XSS_CLEAN", $SECURITY_XSS_CLEAN);
        // load parent constructor
        parent::__construct();

        //$this->macrofuncs['NC_OBJECTS_LIST'] = array('func' => 'nc_objects_list');
        //$this->beta = true;

        spl_autoload_register(array($this, 'load_class'));
    }

    /**
     * Set object variable
     *
     * @param string $name variable name
     * @param mixed $value variable value
     */
    public function set_variable($name, $value) {
        // set variable
        $this->$name = $value;
    }

    /**
     * Get object variable
     *
     * @param string $name variable name
     * @return mixed variable value
     */
    public function get_variable($name) {
        // return value
        return isset($this->$name) ? $this->$name : NULL;
    }

    /**
     * Load system extension
     *
     * @param string $object   class name for loading
     * @param mixed $args,     arguments for class __construct function
     *
     * @return mixed           instantiated object
     */
    public function load() {

        $args = func_get_args();

        $object = array_shift($args);
        $path = '';
        if (strstr($object, "/")) {
            $object_arr = explode("/", trim($object, "/"));
            $object = array_pop($object_arr);
            if (!empty($object_arr)) {
                $path = join("/", $object_arr) . "/";
            }
        }

        if (is_object($this->$object)) {
            $this->debugMessage("System class \"" . $object . "\" already loaded", __FILE__, __LINE__, "info");
            return $this->$object;
        }

        $file_name = $this->SYSTEM_FOLDER . $path . "nc_" . $object . ".class.php";
        if (file_exists($file_name)) {
            include_once($file_name);
        }

        $class_name = "nc_" . ucfirst($object);

        if (class_exists($class_name)) {
            if (sizeof($args)) {
                $this->$object = call_user_func_array(array(new ReflectionClass($class_name), 'newInstance'), $args);
            }
            else {
                $this->$object = new $class_name;
            }
        }

        if (!$this->$object) {
            throw new Exception("Unable load system class \"" . $class_name . "\"!");
        }

        return $this->$object;
    }

    /**
     * @param int $catalogue_id
     * @param bool $reset
     * @return array
     */
    protected function load_all_settings($catalogue_id, $reset) {
        $catalogue_id = (int)$catalogue_id;

        if (!isset($this->settings[$catalogue_id]) || $reset) {
            if ($catalogue_id !== 0) {
                // default settings
                $settings = $this->load_all_settings(0, $reset);
            }
            else {
                $settings = array();
            }

            $res = $this->db->get_results("SELECT `Key`, `Module`, `Value` FROM `Settings` WHERE `Catalogue_ID` = $catalogue_id", ARRAY_A);

            // обработка ошибок
            if ($this->db->is_error) {
                // таблица не существует
                if ($this->db->errno == 1146 || strpos($this->db->last_error, 'exist')) {
                    if ( $this->check_system_install() ) {
                        // DB error
                        print "<p><b>".NETCAT_ERROR_DB_CONNECT."</b></p>";
                        exit;
                    }
                }
                die("Table `Settings`");
            }

            foreach ((array)$res as $row) {
                $settings[$row['Module']][$row['Key']] = $row['Value'];
            }

            $this->settings[$catalogue_id] = $settings;
        }

        return $this->settings[$catalogue_id];
    }

    /**
     * Получить значение параметра из настроек
     * @param string $item  ключ
     * @param string $module имя модуля (system — ядро)
     * @param bool $reset
     * @param int|null $catalogue_id
     *      Если NULL, возвращает настройки для текущего ($nc_core->catalogue->id()) сайта.
     *      Если 0, возвращает настройки «по умолчанию для всех сайтов».
     *      Если другое число — возвращает настройки для указанного сайта.
     * @return mixed значение параметра
     */
    public function get_settings($item = '', $module = '', $reset = false, $catalogue_id = null) {
        if ($catalogue_id === null && $this->catalogue) {
            $catalogue_id = $this->catalogue->id();
        }

        $catalogue_id = (int)$catalogue_id;

        if (!isset($this->settings[$catalogue_id]) || $reset) {
            $this->load_all_settings($catalogue_id, $reset);
        }

        /** @todo (re)move: */
        if (!$catalogue_id && !isset($this->settings[0]['system']['nc_default_settings_filled_500'])) {
            $this->check_default_settings();
            $this->set_settings('nc_default_settings_filled_500', 1);
        }

        // по умолчанию — ядро (1 и true нужно для обратной совместимости)
        if (!$module || $module === 1 || $module === true) {
            $module = 'system';
        }

        // if item requested return item value
        if ($item && is_array($this->settings[$catalogue_id][$module])) {
            return array_key_exists($item, $this->settings[$catalogue_id][$module])
                        ? $this->settings[$catalogue_id][$module][$item]
                        : false;
        }

        // return all settings
        return $this->settings[$catalogue_id][$module];
    }


    private function check_default_settings() {
        $this->load('modules');
        $catalogue_ids = (array) $this->db->get_col("select Catalogue_ID from Catalogue");

        $shop_mode = 0;
        if ($this->modules->get_by_keyword('netshop')) { $shop_mode++; }
        if ($this->modules->get_by_keyword('minishop')) { $shop_mode++; }

        foreach ($catalogue_ids as $id) {
            $this->set_settings('nc_shop_mode_' . $id, $shop_mode);
        }
    }

    /**
     * Установить значение параметра
     * @param string $key ключ
     * @param string $value значение параметра
     * @param string $module модуль
     * @param int $catalogue_id
     * @return bool
     */
    public function set_settings($key, $value, $module = 'system', $catalogue_id = 0) {
        // по умолчанию - ядро системы
        if (!$module) $module = 'system';
        $catalogue_id = (int)$catalogue_id;

        // обновляем состояние
        $this->settings[$catalogue_id][$module][$key] = $value;

        // подготовка записи в БД
        $key          = $this->db->escape($key);
        $value        = $this->db->prepare($value);
        $module       = $this->db->escape($module);

        $id = $this->db->get_var("SELECT `Settings_ID` FROM `Settings` WHERE `Key` = '".$key."' AND `Module` = '".$module."' AND `Catalogue_ID` = '".$catalogue_id."'");

        if ($id) {
            $this->db->query("UPDATE `Settings` SET `Value` = '".$value."' WHERE `Settings_ID` = '".$id."' ");
        } else {
            $this->db->query("INSERT INTO `Settings`(`Key`, `Module`, `Value`, `Catalogue_ID`) VALUES('".$key."','".$module."','".$value."','".$catalogue_id."') ");
        }

        return true;
    }

    /**
     * Удаление параметра
     * @param string ключ
     * @param string модуль
     * @return int
     */
    public function drop_settings($key, $module = 'system') {
        // по умолчанию - ядро системы
        if (!$module) $module = 'system';

        // обновляем состояние
        foreach ($this->settings as $catalogue_id => $data) {
            unset($this->settings[$catalogue_id][$module][$key]);
        }

        // подготовка запроса к БД
        $key = $this->db->escape($key);
        $module = $this->db->escape($module);

        $this->db->query("DELETE FROM `Settings` WHERE `Key` = '".$key."' AND `Module` = '".$module."' ");

        return $this->db->rows_affected;
    }

    /**
     * Load default extensions
     *
     * @return array settings data array
     */
    public function load_default_extensions() {
        // call as static
        static $loaded = false;

        if (!$loaded) {
            // load default extensions
            $this->load("security");
            $this->load("files");
            $this->load("file_info");
            $this->load("token");
            $this->load("event");
            $this->load("gzip");
            $this->load("utf8");
            $this->load("input");
            $this->load("url");
            $this->load("db");
            $this->load("page");
            $this->load("lang");
            $this->load("modules");
            $this->load("widget/widget");
            // essences
            $this->load("essences/catalogue");
            $this->load("essences/subdivision");
            $this->load("essences/sub_class");
            $this->load("essences/component");
            $this->load("essences/user");
            $this->load("essences/template");
            $this->load("essences/message");
            $this->load("essences/trash");

            $loaded = true;
        }
    }

    public function load_files($in_admin = 0) {
        static $are_base_files_loaded = false;

        if (!$are_base_files_loaded) {
            // автозагрузка классов из /require/classes
            // $this->register_class_autoload_path("nc_", $this->INCLUDE_FOLDER . "classes", true);

            // автозагрузка классов из /system/data
            $this->register_class_autoload_path("nc_record", $this->SYSTEM_FOLDER . "record", true);

            // автозагрузка классов из /system/form/*
            $this->register_class_autoload_path("nc_form", $this->SYSTEM_FOLDER . "form", true);

            // файлы из /netcat/require/
            $include_files = array(
                    'unicode.inc.php',
                    's_e404.inc.php',
                    's_auth.inc.php',
                    's_browse.inc.php',
                    's_list.inc.php',
                    's_class.inc.php',
                    's_common.inc.php',
                    'typo.inc.php',
                    's_helpers.inc.php',
                    'classes/nc_multifield.class.php',
                    'classes/nc_multifield_settings.class.php',
                    'classes/nc_multifield_template.class.php');

            // deprecated functions
            if (!$this->NC_DEPRECATED_DISABLED) {
                $include_files[] = 'deprecated.inc.php';
            }

            // файлы из /netcat/admin/
            $admin_files = array(
                    'CheckUserFunctions.inc.php',
                    'consts.inc.php',
                    'user.inc.php',
                    'sub_class.inc.php',
                    'class.inc.php',
                    'subdivision.inc.php',
                    'mail.inc.php',
                    'permission.class.php');

            $templating_files = array(
                    'tpl_function.inc',
                    'class_editor.class',
                    'class_view.class',
                    'fields.class',
                    'template_editor.class',
                    'template_partial.class',
                    'template_view.class',
                    'widget_editor.class',
                    'widget_view.class',
                    'tpl_parser.class',
                    'tpl.class',
                    'module_editor.class',
                    'module_view.class',
                    'module_tpl_editor.class',
                    'module_tpl_view.class'
            );

//            require_once $this->SYSTEM_FOLDER . 'nc_module_core.class.php';

            foreach ($templating_files as $file) {
                require_once $this->SYSTEM_FOLDER . 'templating/nc_' . $file . '.php';
            }

            foreach ($include_files as $file) {
                require_once $this->INCLUDE_FOLDER . $file;
            }

            foreach ($admin_files as $file) {
                require_once $this->ADMIN_FOLDER . $file;
            }

            $are_base_files_loaded = true;
        }

        if ($in_admin) { $this->load_admin_files(); }
    }

    protected function load_admin_files() {
        static $are_admin_files_loaded = false;
        if ($are_admin_files_loaded) { return; }

        $admin_files = array(
            'nc_adminnotice.class.php',
            'catalog.inc.php',
            'class.inc.php',
            'template.inc.php',
            'field.inc.php',
            'system_table.inc.php',
            'module.inc.php',
            'admin.inc.php'
        );

        foreach ($admin_files as $file) { require_once $this->ADMIN_FOLDER . $file; }
        $are_admin_files_loaded = true;
    }

    public function get_system_table_fields($item = "") {
        // call as static
        static $storage = array();

        // check cache
        if (empty($storage)) {
            // Load system table field info
            $res = $this->db->get_results(
                "SELECT b.`System_Table_Name` AS system_table_name,
                        a.`Field_ID` as id,
                        a.`Field_Name` as name,
                        a.`TypeOfData_ID` as type,
                        a.`Inheritance` as inheritance,
                        a.`Format` as format
                   FROM `Field` AS a, `System_Table` AS b
                  WHERE a.`System_Table_ID` = b.`System_Table_ID`",
                ARRAY_A);
            //ORDER BY a.`System_Table_ID`, a.`Priority`"
            // compile system table fields array
            if (!empty($res)) {
                foreach ($res AS $row) {
                    $storage[$row['system_table_name']][] = $row;
                }
            }
        }

        // if item requested return item value
        if ($item) {
            return array_key_exists($item, $storage) ? $storage[$item] : array();
        }

        // return data associative array
        return $storage;
    }

    public function load_env($catalogue, $sub, $cc) {
        global $admin_mode;
        global $catalogue, $sub, $cc;
        global $current_catalogue, $cc;
        global $current_sub;
        global $current_cc;
        global $cc_array;
        global $use_multi_sub_class;
        global $system_table_fields, $user_table_mode;
        global $parent_sub_tree, $sub_level_count;

        // load catalogue
        if (!$catalogue) {
            try {
                $current_catalogue = $this->catalogue->get_by_host_name($this->HTTP_HOST, true);
                $catalogue = $current_catalogue['Catalogue_ID'];
            } catch (Exception $e) {
                die("No site in project");
            }
        } else {
            $current_catalogue = $this->catalogue->set_current_by_id($catalogue);
        }

        // load sub
        if (!$sub) {
            $sub = $this->catalogue->get_by_id($catalogue, "Title_Sub_ID");
            if (!$sub)
                    throw new Exception("Unable to find the index page for catalog");
        }

        $this->subdivision->set_current_by_id($sub);


        // load cc
        if (!$cc) {
            $checked_only = $admin_mode ? "" : " AND `Checked` = 1";
            $cc = $this->db->get_var("SELECT `Sub_Class_ID` FROM `Sub_Class` WHERE `Subdivision_ID` = '".intval($sub)."'".$checked_only." ORDER BY `Priority` LIMIT 1");
        }
        if ($cc) {
            try {
                $this->sub_class->set_current_by_id($cc);
            } catch (Exception $e) {
                // todo
            }
        }

        // Load all sub_class id's into array, may be exist in
        if (!is_array($cc_array)) {
            $cc_array = array();
            // get cc(s) data
            $res = $this->sub_class->get_by_subdivision_id($sub);
            if (!empty($res)) {
                foreach ($res as $row) {
                    if ($row['Checked']) { $cc_array[] = $row['Sub_Class_ID']; }
                }
            }
        }

        // load system table fields
        $system_table_fields = $this->get_system_table_fields();
        // set global variables
        $current_catalogue = $this->catalogue->get_current();
        $current_sub = $this->subdivision->get_current();
        $current_cc = $this->sub_class->get_current();

        if ($current_cc['System_Table_ID'] == 3 || $current_sub['Subdivision_ID'] == 27 || in_array($current_sub['Subdivision_ID'], nc_preg_split("/\s*,\s*/", $this->get_settings('modify_sub', 'auth')))) {
//            $action = "message";
            $user_table_mode = true;
        } else {
            $user_table_mode = false;
        }

        $parent_sub_tree[$sub_level_count]["Subdivision_Name"] = $current_catalogue["Catalogue_Name"];
        $parent_sub_tree[$sub_level_count]["Hidden_URL"] = "/";

        return;
    }

    /**
     * Get or instance self object
     *
     * @return self object
     */
    public static function get_object() {
        static $storage;
        // check cache
        if (!isset($storage)) {
            // init object
            $storage = new self();
        }
        // return object
        return is_object($storage) ? $storage : false;
    }

    public function set_page_type($type) {
        if (!in_array($type, array('html', 'rss', 'xml'))) $type = 'html';
        $this->page_type = $type;
    }

    public function get_page_type() {
        return $this->page_type ? $this->page_type : 'html';
    }

    public function get_content_type() {
        $type = $this->get_page_type();
        if ($type == 'rss') $type = 'xml';

        return "text/".$type."; charset=".$this->NC_CHARSET;
    }

    public function replace_macrofunc($str) {
        global $action;
        if ($this->inside_admin) {
            return $str;
        }
        preg_match_all("/%([a-z0-9_]+)\(([^\)]+)\)%/i", $str, $matches, PREG_SET_ORDER);

        foreach ($matches as $v) {
            $v[2] = str_replace('&#39;', '\'', $v[2]);
            if (empty($this->macrofuncs[$v[1]])) continue;
            $func = $this->macrofuncs[$v[1]]['func'];
            $obj = $this->macrofuncs[$v[1]]['object'];
            eval("\$args = \$this->_parse_func_arg(".$v[2].");");
            $res = call_user_func_array($obj ? array($obj, $func) : $func, $args);
            if (($action == 'change' || $action == 'add') && (!isset($args[1]) || !$args[1])) {
                $str = str_replace($v[0], '', $str);
            }
            $str = str_replace($v[0], $res, $str);
        }

        return $str;
    }

    /**
     *
     */
    public function output_page_buffer() {
        // Обработчик буфера (например, ob_start('output_page_buffer')
        // не может быть использован, так как nc_core::replace_macrofunc()
        // использует при подготовке результата буферизацию вывода
        // (а вызов ob_start() внутри обработчика буфера запрещён)

        $buffer = ob_get_clean();

        if ($this->inside_admin) {
            $buffer = nc_postprocess_admin_page($buffer);
        }
        else {
            $buffer = $this->replace_macrofunc($buffer);

            if ($this->page->get_canonical_link() && stripos($buffer, '<head')) {
                $buffer = nc_insert_in_head($buffer, "\n" . $this->page->get_canonical_link_tag() . "\n");
            }
        }

        if ($this->use_gzip_compression && $this->gzip->check()) {
            while (ob_get_level()) { ob_end_flush(); }
            ob_start('ob_gzhandler');
        }

        echo $buffer;
    }

    /**
     * Включение или выключение поля
     * @param string $action check - включение, uncheck - выключение
     * @param mixed $class_id номер компонента или имя системной таблицы
     * @param mixed $field номер поля или его имя
     * @return bool изменено поле или нет
     */
    public function edit_field($action, $class_id = 0, $field = '') {
        $system_tables = array("Catalogue" => 1, "Subdivision" => 2, "User" => 3, "Template" => 4);
        if (is_string($class_id)) {
            $system_table_id = $system_tables[$class_id];
        } else {
            $class_id = intval($class_id);
        }

        $this->db->query("UPDATE `Field`
                      SET `Checked` = '".($action == 'check' ? 1 : 0)."'
                      WHERE
                      ".( is_int($class_id) ? "`Class_ID` = '".$class_id."' AND " : "" )."
                      ".( $system_table_id ? "`System_Table_ID` = '".$system_table_id."' AND " : "" )."
                      ".( is_int($field) ? "`Field_ID` = '".$field."' " :
                        " `Field_Name` = '".$this->db->escape($field)."' "));
        return $this->db->rows_affected;
    }

    /**
     * Включение поля в компоненте или системной таблице
     * @param mixed $class_id номер компонента или имя системной таблицы
     * @param mixed $field номер поля или его имя
     * @return bool изменено поле или нет
     */
    public function check_field($class_id = 0, $field = '') {
        return $this->edit_field('check', $class_id, $field);
    }

    /**
     * Выключение поля в компоненте или системной таблице
     * @param mixed $class_id номер компонента или имя системной таблицы
     * @param mixed $field номер поля или его имя
     * @return bool изменено поле или нет
     */
    public function uncheck_field($class_id = 0, $field = '') {
        return $this->edit_field('uncheck', $class_id, $field);
    }

    /**
     * Метод проверяет, установлено ли расширение php
     * @param string $name имя расширения
     * @return bool
     */
    public function php_ext($name) {
        static $ext = array();
        if (!array_key_exists($name, $ext)) {
            $ext[$name] = extension_loaded($name);
        }

        return $ext[$name];
    }

    /**
     * Строка парсится в аргументы функции
     * @param string
     * @return <type>
     */
    protected function _parse_func_arg($str) {
        return func_get_args();
    }

    /**
     * Регистрация макрофункции
     * @param string $macroname имя макрофункции
     * @param string $func имя функции или метода, результат которой заменяет макрофункцию
     * @param object $object  ссылка на объект, если второй аргумент - метод
     */
    public function register_macrofunc($macroname, $func, $object = null) {
        $this->macrofuncs[$macroname] = array('func' => $func);
        if ($object) $this->macrofuncs[$macroname]['object'] = $object;
    }

    public function return_device() {
        require_once $this->INCLUDE_FOLDER.'lib/mobile_detect.php';
        $detect = new Mobile_Detect();
        return ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'mobile') : 'desktop');
    }


    public function is_mobile() {
        return ($this->return_device() !== 'desktop');
    }

    public function mobile_screen() {
        return $_COOKIE['mobile'];
    }

    public function InsideAdminAccess() {
        global $current_user, $AUTH_USER_ID;
        return ($this->modules->get_by_keyword('auth') && $current_user['InsideAdminAccess']) || !$this->modules->get_by_keyword('auth') && $this->db->get_var("SELECT `InsideAdminAccess` FROM `User` WHERE `User_ID`='" . intval($AUTH_USER_ID) . "'");
    }

    public function get_interface() {
        return $this->catalogue->get_current('ncMobile') ? 'mobile' : ($this->catalogue->get_current('ncResponsive') ? 'responsive' : 'web');
    }

    /**
     *
     * @return nc_ui
     */
    public function ui() {
        require_once $this->SYSTEM_FOLDER . 'admin/ui/nc_ui.class.php';
        return nc_ui::get_instance();
    }

    /**
     *
     * @return nc_dashboard
     */
    public function dashboard() {
        global $ADMIN_FOLDER;
        require_once $ADMIN_FOLDER . 'dashboard/nc_dashboard.class.php';
        return nc_dashboard::get_instance();
    }

    /**
     *
     * @return nc_backup
     */
    public function backup() {
        require_once $this->SYSTEM_FOLDER . 'backup/nc_backup.class.php';
        return nc_backup::get_instance();
    }

    /**
     *
     * @return nc_nav
     */
    public function nav() {
        require_once $this->SYSTEM_FOLDER . 'nc_nav.class.php';
        return nc_nav::get_instance();
    }

    public function __get($name) {
        $result = null;
        if (isset($this->$name)) {
            $result = $this->$name;
        } else if (method_exists('nc_Core', $name)) {
            $result = $this->$name = $this->$name();
        }
        return $result;
    }

    /**
     * Возвращает способ отображения для текущего раздела
     *
     * @return string
     */
    public function get_display_type() {
        $inputDisplayType = $this->input->fetch_get('lsDisplayType');

        if ($inputDisplayType) {
            return $inputDisplayType;
        }

        $catalogue = $this->catalogue->get_current();
        $subdivision = $this->subdivision->get_current();

        $displayType = 'traditional';
        if ($catalogue && $subdivision && $subdivision['Catalogue_ID'] == $catalogue['Catalogue_ID']) {
            if (
                $catalogue['Title_Sub_ID'] == $subdivision['Subdivision_ID'] ||
                $catalogue['E404_Sub_ID'] == $subdivision['Subdivision_ID'] ||
                ($subdivision['DisplayType'] == 'inherit' && !$subdivision['Parent_Sub_ID'])
            ) {
                $displayType = $catalogue['DisplayType'];
            } else {
                $displayType = $subdivision['DisplayType'];

                if ($displayType == 'inherit') {
                    $parentSubdivision = $subdivision;
                    do {
                        $parentSubdivision = $parentSubdivision['Parent_Sub_ID'] ?
                            $this->subdivision->get_by_id($parentSubdivision['Parent_Sub_ID']) : null;

                        if ($parentSubdivision && $parentSubdivision['DisplayType'] != 'inherit') {
                            $displayType = $parentSubdivision['DisplayType'];
                        }
                    } while ($displayType == 'inherit' && $parentSubdivision);

                    if ($displayType == 'inherit') {
                        $displayType = $catalogue['DisplayType'];
                    }
                }
            }
        }

        return $displayType;
    }


    /**
     * Автозагрузка классов.
     *
     * Например, если prefix = "nc_module_search_", path = "/www/netcat/modules/search/lib", full_name = false
     * то при попытке обращения к классу "nc_module_search_document_parser_html"
     * будет загружен файл "/www/netcat/modules/search/lib/document/parser/html.php"
     *
     * Параметр $full_class_name отвечает за формирование имени файла.
     * Если $full_class_name = true,то будет производиться поиск файлов с именем
     * <full_class_name.class.php>.
     * Если $full_name = false, файл будет производиться поиск файлов по пути
     * <full/class/name.php>.
     *
     * Если префикс не заканчивается на "_", то в соответствующей папке будет производиться
     * и поиск класса с названием, равным префиксу, например:
     *    prefix = "nc_record", path = "/system/data", full_name = true
     *    тогда при поиске класса nc_record будет загружен файл "/system/data/nc_record.class.php"
     *
     * @param $class_prefix    (With a trailing underscore!)
     * @param $class_path      (No trailing slash please)
     * @param bool $full_class_name  Если TRUE, то ищет файлы с именем <full_class_name.class.php>
     */
    public function register_class_autoload_path($class_prefix, $class_path, $full_class_name = false) {
        $this->class_autoload_paths[$class_prefix] = array($class_path, $full_class_name);

        // отсортировать массив таким образом, чтобы первыми шли самые длинные ключи,
        // чтобы избежать ненужных проверок на наличие файла в случае использования
        // префикса "nc_" и префиксов, не оканчивающихся на "_"
        // (в PHP 5.3 можно будет использовать SplMaxHeap?)
        krsort($this->class_autoload_paths);
    }


    /**
     * Обработчик для автозагрузки классов (spl_autoload_register)
     * @param $class_name
     */
    public function load_class($class_name) {
        if (!preg_match("/^\w+$/", $class_name)) { return; }

        foreach ($this->class_autoload_paths as $prefix => $options) {
            if (strpos($class_name, $prefix) === 0) {
                list ($path, $full_name) = $options;

                if ($full_name) { // "$path/nc_full_class_name.php"
                    $file_path = $path . '/' . $class_name . ".class.php";
                }
                else { // "$path/full/class/name.php"
                    $file_path = $path . '/' . str_replace("_", "/", substr($class_name, strlen($prefix))) . ".php";
                }

                if (file_exists($file_path)) { require_once $file_path; return; }
            }
        }
    }

    /**
     * Возвращает объект nc_component для указанного компонента
     * @param int $component_id
     * @return nc_component
     */
    public function get_component($component_id) {
        if (!isset($this->component_instances[$component_id])) {
            $this->component_instances[$component_id] = new nc_component($component_id);
        }
        return $this->component_instances[$component_id];
    }

}
