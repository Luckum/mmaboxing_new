<?php

/**
 * Класс для получения информации о файлах из полей типа «Файл».
 * Обеспечивает кэширование информации для повторного использования.
 */
class nc_file_info {

    /** @var  string */
    protected $files_folder;

    /**
     * @var array   [component => [name => [field settings]]]
     * @see nc_component::get_fields()
     */
    protected $fields_settings = array();

    /** @var array  "component:object:field" => value */
    protected $field_values = array();

    /** @var array  "component:object:field" => [] */
    protected $filetable_values = array();


    /**
     * Возвращает путь к папке netcat_files (от корня сайта), без слэша на конце
     *
     * @return string
     */
    protected function get_files_folder() {
        // cannot do that in the constructor because $nc_core is not initialized there
        if (!isset($this->files_folder)) {
            $this->files_folder = rtrim(nc_core('SUB_FOLDER') . nc_core('HTTP_FILES_PATH'), '/\\');
        }
        return $this->files_folder;
    }

    /**
     * Возвращает настройки всех полей типа «файл» указанного компонента
     *
     * @param int|string $component_id
     * @return array
     */
    protected function get_all_fields($component_id) {
        if (!isset($this->fields_settings[$component_id])) {
            $this->fields_settings[$component_id] = array();
            $component = new nc_component($component_id);

            foreach ($component->get_fields() as $field) {
                if ($field['type'] == NC_FIELDTYPE_FILE) {
                    $this->fields_settings[$component_id][$field['name']] = $field;
                }
            }
        }
        return $this->fields_settings[$component_id];
    }

    /**
     * Возвращает настройки (или одну настройку) поля
     *
     * @param int|string $component_id
     * @param string $field_name
     * @param null|string $option
     * @return array|string
     */
    protected function get_field($component_id, $field_name, $option = null) {
        $fields = $this->get_all_fields($component_id);
        return $option ? $fields[$field_name][$option] : $fields[$field_name];
    }

    /**
     * Возвращает значение поля указанного объекта
     *
     * @param int $component_id
     * @param int $object_id
     * @param $field_name
     */
    protected function get_field_value($component_id, $object_id, $field_name, $cache = 0) {
        $cache_key = "$component_id:$object_id:$field_name";
        if (!$cache || !array_key_exists($cache_key, $this->field_values)) {
            if (preg_match("/^(?:Catalogue|Subdivision|User|Template)$/", $component_id)) {
                $table = $component_id;
                $pk = $component_id . "_ID";
            }
            else {
                $table = "Message" . (int)$component_id;
                $pk = "Message_ID";
            }
            $db = nc_db();

            $this->field_values[$cache_key] = $db->get_var(
                "SELECT `" . $db->escape($field_name) . "` ".
                "  FROM `$table`" .
                " WHERE `$pk` = " . (int)$object_id
            );
        }
        return $this->field_values[$cache_key];
    }

    /**
     * Возвращает значение из таблицы Filetable для указанного поля объекта
     *
     * @param int|string $component_id
     * @param int $object_id
     * @param string $field_name
     * @return array|null
     */
    protected function get_filetable_values($component_id, $object_id, $field_name, $cache = 0) {
        $cache_key = "$component_id:$object_id:$field_name";
        if (!$cache || !array_key_exists($cache_key, $this->filetable_values)) {
            $field_id = $this->get_field($component_id, $field_name, 'id');
            $this->filetable_values[$cache_key] = nc_db()->get_row(
                "SELECT * FROM `Filetable`" .
                " WHERE `Message_ID` = " . (int)$object_id .
                "   AND `Field_ID` = " . (int)$field_id,
                ARRAY_A
            );
        }
        return $this->filetable_values[$cache_key];
    }

    /**
     * Возвращает массив с информацией о файле
     *
     * @param int|string $component_id
     * @param int $object_id
     * @param string|int $field_name
     * @param bool $include_field_name — если true, то ключи в массиве будут иметь вид FieldName_*
     * @param bool $prefix_with_f      — если true, то ключи в массиве будут иметь вид f_FieldName_*
     * @return array
     *      download_path | FieldName            — ссылка для скачивания под оригинальным именем (ссылка с "h_")
     *      url           | FieldName_url        — путь к файлу от корня сайта
     *      name          | FieldName_name       — изначальное имя файла
     *      size          | FieldName_size       — размер
     *      type          | FieldName_type       — mime-тип
     *      download      | FieldName_download   — количество скачиваний
     */
    public function get_file_info($component_id, $object_id, $field_name, $include_field_name = true, $prefix_with_f = false) {
        // Если передано ID, а не имя поля (nc_file_path() позволяет это делать) — найдём имя поля
        if (is_numeric($field_name)) {
            foreach ($this->get_all_fields($component_id) as $field) {
                if ($field['id'] == $field_name) {
                    $field_name = $field['name'];
                    break;
                }
            }
        }

        if ($include_field_name || $prefix_with_f) {
            $prefix = ($prefix_with_f ? "f_" : "") . $field_name;
            $name_key = "{$prefix}_name";
            $download_path_key = "{$prefix}";
            $path_key = "{$prefix}_url";
            $preview_path_key = "{$prefix}_preview_url";
            $size_key = "{$prefix}_size";
            $type_key = "{$prefix}_type";
            $download_count_key = "{$prefix}_download";
        }
        else {
            $name_key = "name";
            $download_path_key = "download_path";
            $path_key = "url";
            $preview_path_key = "preview_url";
            $size_key = "size";
            $type_key = "type";
            $download_count_key = "download";
        }

        $field_value = $this->get_field_value($component_id, $object_id, $field_name);
        if ($field_value && !is_numeric($field_name)) {
            $filetable_values = $this->get_filetable_values($component_id, $object_id, $field_name);
            $files_folder = $this->get_files_folder();

            if ($filetable_values) { // «Защищённая файловая система»
                $file_info = array(
                    $name_key           => $filetable_values['Real_Name'],
                    $download_path_key  => $files_folder . $filetable_values['File_Path'] . 'h_' . $filetable_values['Virt_Name'],
                    $path_key           => $files_folder . $filetable_values['File_Path'] .        $filetable_values['Virt_Name'],
                    $preview_path_key           => $files_folder . $filetable_values['File_Path'] . 'preview_' .        $filetable_values['Virt_Name'],
                    $size_key           => $filetable_values['File_Size'],
                    $type_key           => $filetable_values['File_Type'],
                    $download_count_key => $filetable_values['Download'],
                );
            }
            else {
                $field_value_parts = explode(":", $field_value);
                $file_name = $field_value_parts[0];
                if (isset($field_value_parts[3])) {  // «Стандартная файловая система»
                    // OriginalName.jpg:image/jpeg:123456:u/OriginalName.jpg
                    $file_path = $files_folder . '/' . $field_value_parts[3];
                    $parts = explode('/', $file_path);
                    $parts[count($parts) - 1] = 'preview_' . $parts[count($parts) - 1];
                    $preview_file_path = implode('/', $parts);
                }
                else { // «Простая файловая система»
                    // OriginalName.jpg:image/jpeg:123456
                    $file_extension = strpos($file_name, ".") ? substr($file_name, strrpos($file_name, ".")) : "";
                    $file_path = $files_folder . '/' .
                                 $this->get_field($component_id, $field_name, 'id') .
                                 "_" .
                                 $object_id .
                                 $file_extension;
                    $preview_file_path = $files_folder . '/' . 'preview_' .
                        $this->get_field($component_id, $field_name, 'id') .
                        "_" .
                        $object_id .
                        $file_extension;
                }
                $file_info = array(
                    $name_key => $file_name,
                    $download_path_key => $file_path,
                    $path_key => $file_path,
                    $preview_path_key => $preview_file_path,
                    $size_key => $field_value_parts[2],
                    $type_key => $field_value_parts[1],
                    $download_count_key => 0,
                );
            }
        }
        else { // NO FIELD VALUE
            $file_info = array(
                $name_key => null,
                $download_path_key => null,
                $path_key => null,
                $preview_path_key => null,
                $size_key => null,
                $type_key => null,
                $download_count_key => null,
            );
        }

        return $file_info;
    }

    /**
     * Возвращает значения всех переменных, связанных с файловыми полями (в виде f_ИмяПоля_*)
     *
     * @param int|string $component_id
     * @param int $object_id
     * @return array
     */
    public function get_all_object_file_variables($component_id, $object_id) {
        $all_files_info = array();
        $all_field_names = $this->get_all_fields($component_id);
        foreach ($all_field_names as $field_name => $field_settings) {
            $all_files_info += $this->get_file_info($component_id, $object_id, $field_name, true, true);
        }
        return $all_files_info;
    }

    /**
     * Сохраняет данные о файлах объекта во внутренний кэш для дальнейшего использования.
     *
     * @param int|string $component_id
     * @param array $object_data
     */
    public function cache_object_data($component_id, array $object_data) {
        $fields = $this->get_all_fields($component_id);
        if (!$fields) { return; }

        if (ctype_alpha($component_id)) {
            $id_key = $component_id . "_ID"; // i.e. "User_ID", "Catalogue_ID", ...
        }
        else {
            $id_key = "Message_ID";
        }

        $object_id = isset($object_data[$id_key]) ? (int)$object_data[$id_key] : null;
        if (!$object_id) { return; }

        foreach ($object_data as $field_name => $value) {
            if (isset($fields[$field_name])) {
                $this->field_values["$component_id:$object_id:$field_name"] = $value;
            }
        }
    }

    /**
     * Сохраняет данные о файлах объектов из списка во внутренний кэш для дальнейшего использования.
     *
     * @param int|string $component_id
     * @param array $object_list_data
     */
    public function cache_object_list_data($component_id, array $object_list_data) {
        foreach ($object_list_data as $row) {
            $this->cache_object_data($component_id, $row);
        }
    }

    /**
     * Загружает данные из таблицы Filetable для всех объектов с указанными ID.
     *
     * @param int|string $component_id
     * @param array $object_ids
     */
    public function preload_filetable_values($component_id, array $object_ids) {
        $fields = $this->get_all_fields($component_id);
        if (!$fields) {
            return; // no file fields
        }

        $empty_values = array();
        $field_ids = "";
        foreach ($fields as $field_name => $field_settings) {
            $field_ids .= "," . $field_settings['id'];
            foreach ($object_ids as $object_id) {
                $empty_values["$component_id:$object_id:$field_name"] = null;
            }
        }
        $field_ids = substr($field_ids, 1); // remove unwanted comma from the beginning of the string

        $db = nc_db();
        $object_ids = join(",", $object_ids);
        if (!preg_match("/^[\d,]+$/", $object_ids)) {
            return; // $ids array seems to contain incorrect values
        }

        $this->filetable_values = array_merge(
            $empty_values,
            $this->filetable_values,
            (array)$db->get_results(
                "SELECT ft.*, CONCAT('" . $db->escape($component_id) . "', ':', ft.`Message_ID`, ':', f.`Field_Name`) AS `Cache_Key`" .
                "  FROM `Filetable` AS ft JOIN `Field` AS f USING (`Field_ID`)" .
                " WHERE ft.`Message_ID` IN ($object_ids)" .
                "   AND ft.`Field_ID` IN ($field_ids)",
                ARRAY_A,
                "Cache_Key"
            )
        );
    }

}