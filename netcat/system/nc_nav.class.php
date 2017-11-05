<?php


class nc_nav extends nc_System {


    /**
     * Идентификатор текущего сайта
     * @var int
     */
    protected $site_id;

    /**
     * Идентификатор текущего раздела
     * @var int
     */
    protected $subdivision_id;

    /**
     * Уровень вложенности текущего раздела
     * @var int
     */
    protected $sub_level = 0;

    /**
     * Массив родительских массивов (path)
     * @var array
     */
    protected $sub_parents;

    /**
     * @var nc_db_table
     */
    protected $table;

    /**
     * @var nc_db_table
     */
    protected $file_table;

    /**
     * Текущий URL страницы
     * @var string
     */
    protected $current_url;

    /**
     * Текущий URL страницы, без query_string
     * @var string
     */
    protected $current_page_url;

    /**
     * Текущий URL раздела, без query_string и без имени файла в конце (some-keyword.html)
     * @var string
     */
    protected $current_sub_url;

    /**
     * @var nc_core
     */
    protected $nc_core;

    protected $routing_module_enabled = false;

    protected $field_aliases = array(
        'id'      => 'Subdivision_ID',
        'name'    => 'Subdivision_Name',
        'keyword' => 'EnglishName',
    );

    // object, array, json
    protected $default_result_type = 'object';

    // Временные параметры условия выборки разделов
    protected $ignore_check    = false;
    protected $custom_ordering = false;
    protected $result_type     = null;
    protected $result_mode     = null;
    protected $result_data     = null;

    //-------------------------------------------------------------------------

    public static function get_instance() {
        static $instance;

        if ($instance === null) {
            $instance = new nc_nav;
        }

        return $instance;
    }

    //-------------------------------------------------------------------------

    protected function __construct() {
        $this->nc_core = nc_core();

        $this->table      = nc_db_table::make('Subdivision');
        $this->file_table = nc_db_table::make('Filetable', 'ID');

        $this->routing_module_enabled = nc_module_check_by_keyword('routing');

        $this->current_page_url = urldecode(strtok($_SERVER['REQUEST_URI'], '?'));
        $this->current_sub_url  = substr($this->current_page_url, 0, strrpos($this->current_page_url, "/") + 1);

        if (!empty($GLOBALS['current_sub'])) {
            $this->set_current_sub($GLOBALS['current_sub']);
        }

        $this->reset();
    }

    //-------------------------------------------------------------------------

    protected function __clone() {}

    //-------------------------------------------------------------------------

    public function reset() {
        $this->table->reset_query();
        $this->ignore_check    = false;
        $this->custom_ordering = false;
        $this->result_type     = $this->default_result_type;
        $this->result_mode     = null;
        $this->result_data     = null;
    }

    //-------------------------------------------------------------------------

    public function set_current_sub($sub) {
        $this->subdivision_id = (int) $sub['Subdivision_ID'];
        $this->site_id        = (int) $sub['Catalogue_ID'];

        $this->sub_parents = $this->nc_core->subdivision->get_parent_tree($this->subdivision_id);
        $this->sub_level   = $this->nc_core->subdivision->get_level_count($this->subdivision_id);
    }

    //-------------------------------------------------------------------------

    public function set_result_type($type) {
        $valid_types = array('object', 'array', 'json');

        if (in_array($type, $type)) {
            $this->result_type = $type;
        }
    }

    //-------------------------------------------------------------------------

    public function get_subdivision_id() {
        return $this->subdivision_id;
    }

    //-------------------------------------------------------------------------

    public function get_site_id() {
        return $this->site_id;
    }

    //-------------------------------------------------------------------------

    /**
     * Возращает список разделов приведенный к стандартному виду (содержит URL и пр.)
     * @return array
     */
    public function get() {
        static $file_fields;

        // Не были указаны условия выборки (Прямой вызов $nav->get())
        if (!$this->result_mode) {
            $this->reset();
            return array();
        }

        // Получаем список полей типа "Файл"
        if ($file_fields === null) {
            $file_fields = array();

            // Список полей для таблицы Subdivision. (запрос к БД кэшируется внутри метода)
            $subdivision_fields = $this->nc_core->get_system_table_fields('Subdivision');

            foreach ($subdivision_fields as $field) {
                if ($field['type'] == NC_FIELDTYPE_FILE) {
                    $file_fields[$field['id']] = $field['name'];
                }
            }
        }

        if (!$this->result_data) {
            // Получаем список разделов
            $this->set_query_params();
            $data = $this->table->index_by_id()->get_result();
        } else {
            $data = $this->result_data;
        }

        $subdivision_ids = array_keys($data);
        $result          = array();

        // idebug($this->table->get_last_query());
        // $nc_core->file_info->get_all_object_file_variables(3, 83);

        // Получаем список файлов пренадлежащих полученным разделам
        $files = array();
        if ($subdivision_ids && $file_fields) {
            /** @todo use nc_file_info class */
            $files_result = $this->file_table->where_in('Message_ID', $subdivision_ids)->get_result();
            foreach ($files_result as $row) {
                $files[$row['Message_ID']][$row['Field_ID']] = $row;
            }
        }


        $sub_folder    = $this->nc_core->SUB_FOLDER;
        $file_base_url = $sub_folder . $this->nc_core->HTTP_FILES_PATH;


        // Постобработка данных
        foreach ($data as $sub_id => $row) {
            // Обработка файловых полей
            foreach ($file_fields as $field_id => $field_name) {
                if (!empty($row[$field_name])) {
                    $f = explode(':', $row[$field_name]);
                    $row[$field_name . '_name'] = $filename = $f[0];
                    $row[$field_name . '_size'] = $f[1];
                    $row[$field_name . '_type'] = $f[2];

                    // Proteced FileSystem
                    if ($file_data = $files[$sub_id][$field_id]) {
                        $url = $file_base_url . ltrim($file_data['File_Path'], '/');

                        $row[$field_name]          = $url . 'h_' . $file_data['Virt_Name'];
                        $row[$field_name . '_url'] = $url . $file_data['Virt_Name'];

                    // Original FileSystem
                    } elseif (!empty($f[3])) {
                        $row[$field_name] = $row[$field_name . '_url'] = $file_base_url . $file_data[3];

                    // Simple FileSysytem
                    } else {
                        $ext = substr($filename, strrpos($filename, '.'));
                        $row[$field_name] = $row[$field_name . '_url'] = $file_base_url . $field_id . '_' . $sub_id . $ext;
                    }
                }
            }

            // Определяем активный ли элемент
            $external_url = $row['ExternalURL'];
            $hidden_url   = $sub_folder . $row['Hidden_URL'];
            $is_active    = false;
            foreach ($this->sub_parents as $parent) {
                if ($parent['Subdivision_ID'] == $row['Subdivision_ID']) {
                    $is_active = true;
                    break;
                }
            }
            if (!$is_active) {
                $is_active = in_array($this->current_page_url, array($hidden_url, $external_url))
                    || $this->current_sub_url == $external_url;
            }

            $row['active']  = (bool) $is_active;
            $row['current'] = $row['Subdivision_ID'] == $this->subdivision_id;
            $row['url']     = $this->make_subdivision_url($row);

            foreach ($this->field_aliases as $alias => $origin) {
                $row[$alias] = $row[$origin];
            }

            $row['id'] += 0; // to int for json

            if ($this->result_type == 'object') {
                $row = (object)$row;
            }

            $result[] = $row;
        }

        $this->reset();

        return $result;
    }

    //-------------------------------------------------------------------------

    /**
     * Возращает список разделов приведенный к стандартному виду в виде JSON
     * @return string
     */
    // public function get_json($fields = array()) {
    //     $fields = array_unique(array_merge(array('id', 'name', 'url', 'active', 'current'), (array)$fields));
    //     $result = $this->as_array()->get();
    //     $data   = array();

    //     foreach ($result as $i => $row) {
    //         foreach ($fields as $key) {
    //             if (isset($row[$key])) {
    //                 $data[$i][$key] = $row[$key];
    //             }
    //         }
    //     }

    //     return json_safe_encode($data);
    // }

    //-------------------------------------------------------------------------

    public function get_sub($parent_id = 0) {
        return $this->sub($parent_id)->get();
    }

    //-------------------------------------------------------------------------

    public function get_by_level($level) {
        return $this->level($level)->get();
    }

    //-------------------------------------------------------------------------

    public function get_path($offset = null, $length = null) {
        static $result;

        if ($result === null) {
            $this->set_result_mode('path');
            $this->result_data = array_reverse($this->sub_parents);

            $result = $this->get();
        }
        else {
            // Сбрасываем временные параметры если были установлены
            $this->reset();
        }

        if (func_num_args()) {
            $args = func_get_args();
            array_unshift($args, $result);

            return call_user_func_array('array_slice', $args);
        }

        return $result;
    }

    /**************************************************************************
        MODIFICATORS
    **************************************************************************/

    public function sub($parent_id = 0) {
        $this->set_result_mode('sub');

        $this->table->where('Parent_Sub_ID', $parent_id);

        return $this;
    }

    //-------------------------------------------------------------------------

    public function level($level) {
        $i = $this->sub_level - $level;
        if (isset($this->sub_parents[$i])) {
            $this->sub($this->sub_parents[$i]['Subdivision_ID']);
        }

        return $this;
    }

    //-------------------------------------------------------------------------

    public function as_object() {
        $this->set_result_type('object');
        return $this;
    }

    //-------------------------------------------------------------------------

    public function as_array() {
        $this->set_result_type('array');
        return $this;
    }

    //-------------------------------------------------------------------------

    public function ignore_check($ignore = true) {
        $this->ignore_check = $ignore;

        return $this;
    }

    //-------------------------------------------------------------------------

    public function where($field, $operator = null, $value = null) {
        return $this->_where('where', func_get_args());
    }

    //-------------------------------------------------------------------------

    public function or_where($field, $operator = null, $value = null) {
        return $this->_where('or_where', func_get_args());
    }

    //-------------------------------------------------------------------------

    public function where_in($field, $values) {
        return $this->_where('where_in', func_get_args());
    }

    //-------------------------------------------------------------------------

    public function or_where_in($field, $values) {
        return $this->_where('or_where_in', func_get_args());
    }

    //-------------------------------------------------------------------------

    public function order_by($field, $type = 'asc') {

        $field = $this->field_alias($field);

        $this->custom_ordering = true;
        $this->table->order_by($field, $type);

        return $this;
    }

    /**************************************************************************
        PROTECTED HELPERS
    **************************************************************************/

    protected function make_subdivision_url(array $row) {

        $url = '';

        if ($this->nc_core->admin_mode) {
            $url = $this->nc_core->SUB_FOLDER . $this->nc_core->HTTP_ROOT_PATH . '?catalogue=' . $this->site_id . '&amp;sub=' . $row['Subdivision_ID'];
        }
        else {
            if ($row['ExternalURL']) {
                // Абсолютный путь: http://… или /…
                if (preg_match('@^(\w{2,}://|/)@u', $row['ExternalURL'])) {
                    $url = $row['ExternalURL'];
                }
                // Относительный путь
                else {
                    $url = $this->nc_core->SUB_FOLDER . $row['Hidden_URL'] . $row['ExternalURL'];
                }

            }
            elseif ($this->routing_module_enabled) {
                $url = nc_routing::get_folder_path($row['Subdivision_ID']);
            }
            else {
                $url = $this->nc_core->SUB_FOLDER . $row['Hidden_URL'];
            }
        }

        return $url;
    }

    //-------------------------------------------------------------------------

    protected function set_query_params() {
        static $display_type;

        if ($display_type === null) {
            $display_type = $this->nc_core->get_display_type();
        }

        // Условия
        if ($this->result_mode != 'custom') {
            if (!$this->ignore_check) {
                $this->table->where('Checked', 1);
            }

            $this->table->where('Catalogue_ID', $this->site_id);

            if (in_array($display_type, array('longpage_vertical', 'shortpage'))) {
                $this->table->where_in('DisplayType', array('inherit', $display_type));
            }
        }

        // Сортировка
        if (!$this->custom_ordering) {
            $this->table->order_by('Priority')->order_by('Subdivision_Name');
        }

    }

    //-------------------------------------------------------------------------

    protected function set_result_mode($mode, $overwrite = true) {
        if ($overwrite || !$this->result_mode) {
            $this->result_mode = $mode;
        }
    }

    //-------------------------------------------------------------------------

    protected function field_alias($f) {
        return isset($this->field_aliases[$f]) ? $this->field_aliases[$f] : $f;
    }

    //-------------------------------------------------------------------------

    protected function _where($method, $args) {
        $this->set_result_mode('custom', false);

        $args[0] = $this->field_alias($args[0]);

        call_user_func_array(array($this->table, $method), $args);

        return $this;
    }

    //-------------------------------------------------------------------------


}