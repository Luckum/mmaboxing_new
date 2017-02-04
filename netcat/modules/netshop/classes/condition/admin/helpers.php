<?php
/**
 *
 */
class nc_netshop_condition_admin_helpers {

    /**
     * Выводит HTML-фрагмент для вставки скриптов редактора условий
     * @return void
     */
    static public function include_condition_editor_js() {
        $js_path = nc_module_path('netshop') . "admin/condition/js";
        // Condition editor string constants and the condition editor itself:
        $html = "<script src='$js_path/editor_strings.js.php'></script>\n" .
                 "<script src='$js_path/editor.min.js'></script>\n";

        echo $html;
    }

    /**
     * Возвращает все разделы указанного сайта, содержащие компоненты товаров
     *
     * @param int $catalogue_id
     * @return array
     */
    static public function get_subdivisions_with_goods($catalogue_id) {
        /** @var nc_db $db */
        $db = nc_core('db');
        $shop = nc_netshop::get_instance($catalogue_id);

        $component_ids = join(', ', $shop->get_goods_components_ids());
        $catalogue_id = (int)$catalogue_id;

        // step (1): all subdivisions with components in it
        $subdivisions = $db->get_results(
            "SELECT s.`Hidden_URL`
               FROM `Subdivision` AS s, `Sub_Class` as c
              WHERE s.`Catalogue_ID` = $catalogue_id
                AND s.`Subdivision_ID` = c.`Subdivision_ID`
                AND c.`Class_ID` IN ($component_ids)",
            ARRAY_A);

        // No results?!
        if (!$subdivisions) { return array(); }

        $all_parent_paths = array();
        // step (2): get all ascendant subdivisions to the root
        foreach ($subdivisions as $sub) {
            $path_parts = explode("/", trim($sub['Hidden_URL'], '/'));
            $path = "/";
            foreach ($path_parts as $p) {
                $path .= "$p/";
                $all_parent_paths[$path] = "'$path'";
            }
        }

        // step (2): all subdivision data
        // there is no function to count number of substring occurrences in MySQL 5
        $subdivision_data = $db->get_results(
            "SELECT `Catalogue_ID`, `Subdivision_ID`, `Parent_Sub_ID`, `Subdivision_Name`, `Hidden_URL`, `Checked`,
                    (LENGTH(`Hidden_URL`) - LENGTH(REPLACE(`Hidden_URL`, '/', '')) - 1) AS `Depth`
               FROM `Subdivision`
              WHERE `Catalogue_ID` = $catalogue_id
                AND `Hidden_URL` IN (" . join(", ", $all_parent_paths) . ")
              ORDER BY `Depth`, `Parent_Sub_ID`, `Priority`",
            ARRAY_A
        );
        return $subdivision_data;
    }

    protected static $field_types = array(
        NC_FIELDTYPE_STRING => "string",
        NC_FIELDTYPE_INT => "integer",
        NC_FIELDTYPE_TEXT => "text",
        NC_FIELDTYPE_SELECT => "select",
        NC_FIELDTYPE_BOOLEAN => "boolean",
        NC_FIELDTYPE_FLOAT => "float",
        NC_FIELDTYPE_DATETIME => "datetime",
        NC_FIELDTYPE_MULTISELECT => "multiselect"
    );

    /**
     * Возвращает фрагмент для вставки в SQL-запрос, который фильтрует записи
     * по TypeOfData_ID, которые могут быть использованы в полях редактора условий
     *
     * @return array
     */
    public static function get_field_types_to_export_for_query() {
        return "`TypeOfData_ID` IN (" . join(", ", array_keys(self::$field_types)) . ")";
    }

    /**
     * Used to export data about component and user table fields
     * $field must have following entries: Class_ID, Field_Name, Description, TypeOfData_ID, Format
     */
    public static function export_field(array $field, $override_class_id = null, $skip_class_id = false) {
        $class_id = $override_class_id ? $override_class_id : $field["Class_ID"];
        $field_id = ($skip_class_id
                        ? $field["Field_Name"]
                        : "$class_id:$field[Field_Name]:$field[TypeOfData_ID]"
                    );
        $result = array(
                    "id" => $field_id,
//                    "name" => $field["Name"],
                    "description" => $field["Description"],
//                    "class_id" => $class_id,
                    "type" => self::$field_types[$field["TypeOfData_ID"]],
        );

        if ($result["type"] == "select" || $result["type"] == "multiselect") {
            list($classifier) = explode(":", $field["Format"]);
            $result["classifier"] = $classifier;
        }

        return $result;
    }

    /**
     * Возвращает hash-ссылку на компонент (для использования при формировании
     * текстовых описаний условий)
     */
    static public function get_component_link($component_id) {
        static $cache = array();
        $component_id = (int)$component_id;

        if (!isset($cache[$component_id])) {
            list($component_exists, $name, $fs) = nc_db()->get_row(
                "SELECT `Class_ID`, `Class_Name`, `File_Mode` FROM `Class` WHERE `Class_ID` = $component_id",
                ARRAY_N
            );

            if (!$component_exists) {
                $cache[$component_id] = "<em class='nc--status-error'>" . NETCAT_MODULE_NETSHOP_COND_NONEXISTENT_COMPONENT . "</em>";
            }
            else {
                $hash = "#dataclass" . ($fs ? "_fs" : "") . ".info($component_id)";
                $link = nc_ui_helper::get()->hash_link($hash, $name);
                $cache[$component_id] = sprintf(NETCAT_MODULE_NETSHOP_COND_QUOTED_VALUE, $link);
            }
        }

        return $cache[$component_id];
    }

    /**
     * @param $iso_date
     * @return bool|string
     */
    static public function format_date($iso_date) {
        return date(NETCAT_MODULE_NETSHOP_DATE_FORMAT, strtotime($iso_date));
    }

    /**
     *
     */
    static public function get_field_data($component_id, $field_name, $field_type, nc_netshop $netshop) {
        static $cache = array();
        $key = "$component_id:$field_name:$field_type";

        if (!isset($cache[$key])) {
            if ($component_id == "*") {
                $component_ids = $netshop->get_goods_components_ids();
            }
            else {
                $component_ids = array($component_id);
            }

            $cache[$field_name] = false;
            foreach ($component_ids as $component_id) {
                $component = new nc_component($component_id);
                $field_data = $component->get_field($field_name);
                if (!$field_type || $field_data['type'] == $field_type) {
                    $cache[$key] = $field_data;
                    break;
                }
            }

        }
        return $cache[$key];
    }

}