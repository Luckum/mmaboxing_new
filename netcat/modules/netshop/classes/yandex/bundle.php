<?php

class nc_netshop_yandex_bundle extends nc_netshop_record_conditional
{

  protected $primary_key = 'bundle_id';
  protected $properties = array(
      'bundle_id' => null,
      'catalogue_id' => null,
      'name' => null,
      'last_updated' => null,
      'type' => 'simple',
  );
  protected $table_name = 'Netshop_YandexBundles';
  protected $mapping = array(
      "_generate" => true
  );
  public $defaults_to_try = array(
      'Name' => 'name', 'Price' => 'price', 'Currency' => 'currencyId', 'Image' => 'picture'
  );
  public $default_types = array(
      'simple' => NETCAT_MODULE_NETSHOP_YANDEX_MARKET_BUNDLE_TYPE_SIMPLE,
      'full' => NETCAT_MODULE_NETSHOP_YANDEX_MARKET_BUNDLE_TYPE_FULL,
  );

  public function get_default_types()
  {
    return $this->default_types;
  }

  public function get_fields_object($type)
  {
    if (in_array($type, array_keys($this->default_types))) {
      $className = "nc_netshop_yandex_fields_" . $type;
      return new $className();
    } else {
      return false;
    }
  }

  public function save_map($bundle_id, $map_fields)
  {
    foreach ($map_fields as $class_id => $fields) {
      foreach ($fields as $string => $field_id) {
        if ($field_id != '-1') {
          $str = "INSERT INTO `Netshop_YandexBundlesMap` SET "
                  . " `Bundle_ID` = '" . intval($bundle_id) . "',"
                  . " `Class_ID` = '" . intval($class_id) . "',"
                  . " `String` = '" . nc_db()->escape($string) . "',"
                  . " `Field_ID` = '" . intval($field_id) . "'";
          nc_db()->query($str);
        }
      }
    }
  }

  public function delete_map($bundle_id)
  {
    nc_db()->query("DELETE FROM `Netshop_YandexBundlesMap` WHERE Bundle_ID='" . intval($bundle_id) . "' ");
  }

  /**
   *
   * @param type $bundle_id
   * @param type $type
   * @param type $param
   * @return array
   */
  public function get_map_values($bundle_id, $type = 'class', $param)
  {
    $sql = "SELECT `String`, `Field_ID` FROM `Netshop_YandexBundlesMap` "
            . "WHERE `Bundle_ID` = '" . intval($bundle_id) . "' ";
    switch ($type) {
      case 'class':
        $sql .= " AND `Class_ID` = '" . intval($param) . "' ";
        break;
      case 'fields':
        $sql .= " AND `Field_ID` IN (" . implode(",", array_keys($param)) . ") ";
        break;
    }

    $map_values = array();
    $results = nc_db()->get_results($sql, ARRAY_A);
    if (count($results) > 0) {
      foreach ($results as $res) {
        $map_values[$res['String']] = $res['Field_ID'];
      }
    }
    return $map_values;
  }

  public function get_xml_fields()
  {
    $fields_obj = $this->get_fields_object($this->get('type'));

    $xml_fields = $fields_obj->get_fields();
    foreach ($xml_fields as $key => $attrs) {
      if (isset($attrs['editable']) && $attrs['editable'] === false) {
        unset($xml_fields[$key]);
      }
    }
    return $xml_fields;
  }

  public function get_goods_tables()
  {
    $netshop = nc_netshop::get_instance($this->get('catalogue_id'));
    $goods_tables = array();
    if ($netshop->is_netshop_v1_in_use()) {
      foreach (explode(',', $nc_core->modules->get_vars('netshop', 'GOODS_TABLE')) as $table) {
        $goods_tables[] = (int) trim($table);
      }
    } else {
      $goods_tables = $netshop->get_goods_components_ids();
    }
    return $goods_tables;
  }

  public function try_defaults(nc_netshop_yandex $yandex)
  {
    $xml_fields = $this->get_xml_fields();
    $goods_tables = $this->get_goods_tables();

    $map = array();
    foreach ($goods_tables as $goods_table) {
      $netcat_fields = $yandex->get_netcat_fields($goods_table, true);
      foreach ($netcat_fields as $field_name => $field_id) {
        if (isset($this->defaults_to_try[$field_name]) && isset($xml_fields[$this->defaults_to_try[$field_name]])) {
          $map[$goods_table][$this->defaults_to_try[$field_name]] = $field_id;
        }
      }
    }
    if (count($map) > 0) {
      $this->save_map($this->get('bundle_id'), $map);
    }
  }

}
