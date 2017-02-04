<?php

if (!class_exists("nc_System")) {
    die("Unable to load file.");
}

abstract class nc_Essence extends nc_System {

    protected $current;
    protected $system_tables, $inherit_essences, $essence;
    /** @var  nc_db */
    protected $db;
    protected $data;
    protected $core;

    protected function __construct() {

        // system tables
        $this->system_tables = array("Catalogue", "Subdivision", "User", "Template");
        // inherit essences
        $this->inherit_essences = array("Subdivision", "Sub_Class", "Template");
        // set essence
        $this->essence = str_replace("nc_", "", get_class($this));

        $this->core = nc_Core::get_object();
    }

    public function get_current($item = "") {

        if (empty($this->current)) {
            return false;
        }

        if ($item) {
            return array_key_exists($item, $this->current) ? $this->current[$item] : "";
        }
        else {
            return $this->current;
        }
    }

    public function set_current_by_id($id, $reset = false) {
        // validate
        $id = intval($id);

        if ($id) {
            $this->current = $this->get_by_id($id, "", $reset);
            // return result
            return $this->current;
        }

        // reject
        return false;
    }

    public function set_current_item($item, $value) {

        if (empty($this->current)) {
            return false;
        }

        if (array_key_exists($item, $this->current)) {
            $this->current[$item] = $value;
        }

        return true;
    }

    public function get_by_id($id, $item = "", $reset = false) {
        if (!$id) {
            return null;
        }
        // call as static
        static $storage = array();

        // validate parameters
        $id = intval($id);

        if (empty($this->data[$id])) {
            $this->data[$id] = $this->db->get_row("SELECT * FROM `" . $this->essence . "`
                                        WHERE `" . $this->essence . "_ID` = '" . $id . "'", ARRAY_A);
            $this->data[$id]['_nc_final'] = 0;
        }

        if (!$this->data[$id]['_nc_final']) {
            // convert system fields data
            if (in_array($this->essence, $this->system_tables)) {
                $this->data[$id] = $this->convert_system_vars($this->data[$id]);
            }
            // inherit current data
            if (in_array($this->essence, $this->inherit_essences)) {
                $this->data[$id] = $this->inherit($this->data[$id]);
            }
            $this->data[$id]['_nc_final'] = 1;
        }

        // if item requested return item value
        if ($item && is_array($this->data[$id])) {
            return array_key_exists($item, $this->data[$id]) ? $this->data[$id][$item] : "";
        }

        return $this->data[$id];
    }

    public function delete_by_id($id) {
        // validate parameters
        $id = intval($id);

        $this->db->query("DELETE FROM `" . $this->essence . "` WHERE `" . $this->essence . "_ID` = '" . $id . "'");

        return $this->db->rows_affected;
    }

    public function convert_system_vars($env_array, $reset = 0) {
        static $storage = array();
        static $filetable_storage = array();

        $reset = 1;
        if (!empty($storage[$this->essence][$env_array[$this->essence . "_ID"]]) && !$reset) {
            return $storage[$this->essence][$env_array[$this->essence . "_ID"]];
        }

        /* $debug = debug_backtrace();
          $debug_arr = array();
          foreach ($debug as $value) {
          $debug_arr[] = $value['function'];
          }
          echo join( " -> ",  array_reverse($debug_arr) )."<br/>";
          echo $this->essence." - ".$env_array[$this->essence."_ID"]."<br/><br/>"; */

        // system superior object
        $nc_core = nc_Core::get_object();

        // load system table fields
        $table_fields = $nc_core->get_system_table_fields($this->essence);
        // count
        $field_count = count($table_fields);
        // поля типа файл
        $file_field_ids = array();
        $filetable = array();
        // найдем все поля типа файл
        for ($i = 0; $i < $field_count; $i++) {
            if ($table_fields[$i]['type'] == 6) {
                $file_field_ids[$table_fields[$i]['id']] = $table_fields[$i]['id'];
            }
        }

        // если есть поля типа файл, то выполним запрос к Filetable
        // TODO этот фрагмент требует дальнейшей оптимизации
        // (выбираются все файлы! бред)
        if (!empty($file_field_ids)) {
            $file_field_ids = join(',', $file_field_ids);
            if (!array_key_exists($file_field_ids, $filetable_storage)) {
                $filetable_storage[$file_field_ids] = $this->db->get_results(
                    "SELECT `Virt_Name`, `File_Path`, `Message_ID`, `Field_ID`
                       FROM `Filetable`
                      WHERE `Field_ID` IN ($file_field_ids)",
                    ARRAY_A);
            }

            $file_in_table = $filetable_storage[$file_field_ids];

            if (!empty($file_in_table)) {
                foreach ($file_in_table as $v) {
                    $filetable[$v['Message_ID']][$v['Field_ID']] = array($v['Virt_Name'], $v['File_Path']);
                }
            }
        }

        // Проход по всем полям
        for ($i = 0; $i < $field_count; $i++) {
            $field_id = $table_fields[$i]['id'];
            $field_name = $table_fields[$i]['name'];
            $field_type = $table_fields[$i]['type'];
            $field_format = $this->db->escape($table_fields[$i]['format']);
            $field_inherit = $table_fields[$i]['inheritance'];

            if ($env_array[$field_name]) {
                switch ($field_type) {
                    // Select
                    case 4:
                        $table_name = strtok($field_format, ':');
                        $listname = $this->db->get_var("SELECT `" . $table_name . "_Name` FROM `Classificator_" . $table_name . "` WHERE `" . $table_name . "_ID` = '" . $env_array[$field_name] . "'");

                        $env_array[$field_name . "_id"] = $env_array[$field_name];
                        $env_array[$field_name] = $listname;
                        break;
                    // File
                    case 6:
                        //file_data - массив с ориг.названием, типом, размером, [именем_файла_на_диске]
                        $file_data = explode(':', $env_array[$field_name]);
                        $env_array[$field_name . "_name"] = $file_data[0]; // оригинальное имя
                        $env_array[$field_name . "_type"] = $file_data[1]; // тип
                        $env_array[$field_name . "_size"] = $file_data[2]; // размер
                        $ext = substr($file_data[0], strrpos($file_data[0], ".")); // расширение
                        // запись в таблице Filetable
                        $row = $filetable[$env_array[$this->essence . "_ID"]][$field_id];
                        $row_parent = isset($env_array["Parent_Sub_ID"]) ? $filetable[$env_array["Parent_Sub_ID"]][$field_id] : false;
                        if (!$row && $row_parent) {
                            $row = $row_parent;
                        }
                        if ($row) {
                            // Proteced FileSystem
                            $env_array[$field_name] = $nc_core->get_variable("SUB_FOLDER") . $nc_core->get_variable("HTTP_FILES_PATH") . ltrim($row[1], '/') . "h_" . $row[0];
                            $env_array[$field_name . "_url"] = $nc_core->get_variable("SUB_FOLDER") . $nc_core->get_variable("HTTP_FILES_PATH") . ltrim($row[1], '/') . $row[0];
                        }
                        else {
                            if ($file_data[3]) {
                                // Original FileSystem
                                $env_array[$field_name] = $env_array[$field_name . "_url"] = $nc_core->get_variable("SUB_FOLDER") . $nc_core->get_variable("HTTP_FILES_PATH") . $file_data[3];
                            }
                            else {
                                // Simple FileSysytem
                                $env_array[$field_name] = $env_array[$field_name . "_url"] = $nc_core->get_variable("SUB_FOLDER") . $nc_core->get_variable("HTTP_FILES_PATH") . $field_id . "_" . $env_array[$this->essence . "_ID"] . $ext;
                            }
                        }

                        break;
                    case 8:
                        $env_array[$field_name . "_year"] = substr($env_array[$field_name], 0, 4);
                        $env_array[$field_name . "_month"] = substr($env_array[$field_name], 5, 2);
                        $env_array[$field_name . "_day"] = substr($env_array[$field_name], 8, 2);
                        $env_array[$field_name . "_hours"] = substr($env_array[$field_name], 11, 2);
                        $env_array[$field_name . "_minutes"] = substr($env_array[$field_name], 14, 2);
                        $env_array[$field_name . "_seconds"] = substr($env_array[$field_name], 17, 2);
                        break;
                    // Multiselect
                    case 10:
                        $array_with_id = explode(',', $env_array[$field_name]);

                        if (!$array_with_id[0]) {
                            unset($array_with_id[0]);
                        }
                        if (!$array_with_id[count($array_with_id)]) {
                            unset($array_with_id[count($array_with_id)]);
                        }
                        if (empty($array_with_id)) {
                            break;
                        }
                        // латинское имя списка
                        $table_name = strtok($field_format, ':');
                        // получим сами элементы
                        $listname = $this->db->get_col("SELECT `" . $table_name . "_Name`
                                                          FROM `Classificator_" . $table_name . "`
                                                         WHERE `" . $table_name . "_ID` IN(" . join(',', $array_with_id) . ")");
                        $env_array[$field_name . "_id"] = (array)$array_with_id;
                        $env_array[$field_name] = (array)$listname;
                        break;
                }
            }
        }

        $storage[$this->essence][$env_array[$this->essence . "_ID"]] = $env_array;

        return $env_array;
    }

    public function inherit_system_fields($system_table_name, $parent_array, $child_array) {
        // system superior object
        $nc_core = nc_Core::get_object();

        // load system table fields
        $table_fields = $nc_core->get_system_table_fields($system_table_name);
        // count
        $counted_fileds = count($table_fields);

        for ($i = 0; $i < $counted_fileds; $i++) {
            // не наследуется
            if (!$table_fields[$i]['inheritance']) {
                continue;
            }
            // field name
            $field_name = $table_fields[$i]['name'];

            if (!array_key_exists($field_name, $child_array) || $child_array[$field_name] == "") {
                switch ($table_fields[$i]['type']) {
                    // list
                    case 4:
                        $child_array[$field_name] = (isset($parent_array[$field_name]) && $parent_array[$field_name] ? $parent_array[$field_name] : "");
                        $child_array[$field_name . '_id'] = (isset($parent_array[$field_name . '_id']) && $parent_array[$field_name . '_id'] ? $parent_array[$field_name . '_id'] : "");
                        break;
                    // file
                    case 6:
                        $child_array[$field_name] = (isset($parent_array[$field_name]) && $parent_array[$field_name] ? $parent_array[$field_name] : "");
                        $child_array[$field_name . '_name'] = (isset($parent_array[$field_name . '_name']) && $parent_array[$field_name . '_name'] ? $parent_array[$field_name . '_name'] : "");
                        $child_array[$field_name . '_size'] = (isset($parent_array[$field_name . '_size']) && $parent_array[$field_name . '_size'] ? $parent_array[$field_name . '_size'] : "");
                        $child_array[$field_name . '_type'] = (isset($parent_array[$field_name . '_type']) && $parent_array[$field_name . '_type'] ? $parent_array[$field_name . '_type'] : "");
                        $child_array[$field_name . '_url'] = (isset($parent_array[$field_name . '_url']) && $parent_array[$field_name . '_url'] ? $parent_array[$field_name . '_url'] : "");
                        break;
                    //date
                    case 8:
                        $child_array[$field_name] = (isset($parent_array[$field_name]) && $parent_array[$field_name] ? $parent_array[$field_name] : "");
                        $child_array[$field_name . '_day'] = (isset($parent_array[$field_name . '_day']) && $parent_array[$field_name . '_day'] ? $parent_array[$field_name . '_day'] : "");
                        $child_array[$field_name . '_month'] = (isset($parent_array[$field_name . '_month']) && $parent_array[$field_name . '_month'] ? $parent_array[$field_name . '_month'] : "");
                        $child_array[$field_name . '_year'] = (isset($parent_array[$field_name . '_year']) && $parent_array[$field_name . '_year'] ? $parent_array[$field_name . '_year'] : "");
                        $child_array[$field_name . '_hours'] = (isset($parent_array[$field_name . '_hours']) && $parent_array[$field_name . '_hours'] ? $parent_array[$field_name . '_hours'] : "");
                        $child_array[$field_name . '_minutes'] = (isset($parent_array[$field_name . '_minutes']) && $parent_array[$field_name . '_minutes'] ? $parent_array[$field_name . '_minutes'] : "");
                        $child_array[$field_name . '_seconds'] = (isset($parent_array[$field_name . '_seconds']) && $parent_array[$field_name . '_seconds'] ? $parent_array[$field_name . '_seconds'] : "");
                        break;
                    //multilist
                    case 10:
                        $child_array[$field_name] = (isset($parent_array[$field_name]) && $parent_array[$field_name] ? $parent_array[$field_name] : "");
                        $child_array[$field_name . '_id'] = (isset($parent_array[$field_name . '_id']) && $parent_array[$field_name . '_id'] ? $parent_array[$field_name . '_id'] : "");
                        break;
                    // other fields
                    default:
                        $child_array[$field_name] = (isset($parent_array[$field_name]) && $parent_array[$field_name] ? $parent_array[$field_name] : "");
                }
            }
            // список наследуется - если (элемент == 0)
            else if ($table_fields[$i]['type'] == 4 && $child_array[$field_name . '_id'] == 0) {
                $child_array[$field_name] = $parent_array[$field_name];
                $child_array[$field_name . '_id'] = $parent_array[$field_name . '_id'];
            }
        }

        return $child_array;
    }

    public function check_available($url) {
        if (!function_exists('curl_init')) {
            return false;
        }

        $curlInit = curl_init($url);
        curl_setopt($curlInit, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curlInit, CURLOPT_HEADER, true);
        curl_setopt($curlInit, CURLOPT_NOBODY, true);
        curl_setopt($curlInit, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curlInit);
        curl_close($curlInit);

        if ($response) {
            return true;
        }
        return false;
    }

    public function get_alternative_link() {
        $nc_core = nc_Core::get_object();
        $current_catalogue = $nc_core->catalogue->get_current();
        $current_sub = $nc_core->subdivision->get_current();

        if ($current_catalogue['ncMobile'] == 1) {
            $Catalogue_ID = $current_catalogue['ncMobileSrc'];

            $SQL = "SELECT Sub_Class_ID,
                           Subdivision_ID,
                           SrcMirror
                    FROM Sub_Class
                        WHERE Subdivision_ID = " . $current_sub['Subdivision_ID'];

            $result = (array)$this->db->get_results($SQL);

            $sub_class_id = null;

            foreach ($result as $row) {
                if ($row->SrcMirror) {
                    $sub_class_id = $row->SrcMirror;
                    break;
                }
            }

            if ($sub_class_id) {
                $Hidden_URL = $nc_core->sub_class->get_by_id($sub_class_id, 'Hidden_URL');
            }
        }
        else {
            $Catalogue_ID = $this->db->get_var("SELECT Catalogue_ID FROM Catalogue WHERE ncMobileSrc = " . $current_catalogue['Catalogue_ID']);

            $SQL = "SELECT Sub_Class_ID,
                           Subdivision_ID,
                           SrcMirror
                    FROM Sub_Class
                        WHERE SrcMirror IN (SELECT Sub_Class_ID FROM Sub_Class WHERE Subdivision_ID = {$current_sub['Subdivision_ID']})";

            $result = (array)$this->db->get_row($SQL, ARRAY_A);

            if ($result['Sub_Class_ID']) {
                $Hidden_URL = $nc_core->sub_class->get_by_id($result['Sub_Class_ID'], 'Hidden_URL');
            }
        }

        $Domain = $nc_core->catalogue->get_by_id($Catalogue_ID, 'Domain');
        $suffix = '';

        global $action;

        switch ($action) {
            case 'full' :
                global $f_Keyword, $cc_keyword, $message;
                $suffix = $f_Keyword ? $f_Keyword : $cc_keyword . "_" . $message;
                $suffix .= '.html';
                break;
        }

        $REQUEST_URI = (string)$_SERVER['REQUEST_URI'];

        if (!$Hidden_URL && $REQUEST_URI = !'/') {
            $Hidden_URL = $REQUEST_URI;
            if ($REQUEST_URI[strlen($REQUEST_URI) - 1] != '/') {
                $url_array = explode('/', $REQUEST_URI);
                array_pop($url_array);
                $Hidden_URL = join('/', $url_array) . '/';
            }

            $SQL = "SELECT COUNT(*)
                        FROM Subdivision
                            WHERE Hidden_URL = '$Hidden_URL'
                                AND Catalogue_ID = " . $Catalogue_ID;

            $result = $this->db->get_var($SQL);
            $url = $result ? $Domain . $Hidden_URL . $suffix : $Domain;

        }
        else {
            $url = $Domain . $Hidden_URL . $suffix;
        }
        return $url;
    }

}

?>
