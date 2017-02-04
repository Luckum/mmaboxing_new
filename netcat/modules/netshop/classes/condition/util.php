<?php


class nc_netshop_condition_util {

    /**
     * Возвращает ID родительского раздела для указанного раздела.
     *
     * @param int $sub_id
     * @return int|null
     */
    public static function get_parent_sub($sub_id) {
        static $parent_cache = array();
        if (!array_key_exists($sub_id, $parent_cache)) {
            $parent_cache[$sub_id] = nc_db()->get_var(
                'SELECT `Parent_Sub_ID` FROM `Subdivision` WHERE `Subdivision_ID` = ' . (int)$sub_id
            );
        }
        return $parent_cache[$sub_id];
    }

    /**
     * Проверяет, есть ли у компонента свойство указанного типа
     *
     * @param $component_id
     * @param $field_name
     * @param $field_type
     * @return boolean
     */
    public static function component_has_field($component_id, $field_name, $field_type) {
        static $cache = array();
        $cache_key = "$component_id:$field_name:$field_type";
        if (!isset($cache[$cache_key])) {
            $item_component = new nc_component($component_id);
            $matching_fields = $item_component->get_fields($field_type, false);
            $cache[$cache_key] = in_array($field_name, $matching_fields);
        }
        return $cache[$cache_key];
    }
}