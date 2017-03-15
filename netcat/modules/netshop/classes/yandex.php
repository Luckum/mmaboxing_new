<?php

class nc_netshop_yandex
{

  protected $bundles_table;
  public $catalogue_id;

  /**
   * Constructor
   * @param nc_netshop $netshop
   */
  public function __construct(nc_netshop $netshop)
  {
    $this->netshop = $netshop;

    $this->catalogue_id = $netshop->get_catalogue_id();

    $this->bundles_table = 'Netshop_YandexBundles';
  }

  /**
   *
   * @return type
   */
  public function get_bundles_list()
  {

    return $bundles = nc_db()->get_results("
            SELECT b.*
              FROM `$this->bundles_table` AS b
             WHERE b.`Catalogue_ID` = $this->catalogue_id
             ORDER BY b.`Name`", ARRAY_A);
  }

  /**
   * 
   * @param type $goods_table
   * @return string
   */
  public function get_netcat_fields($goods_table, $reverced = false)
  {
    $sql = "SELECT `Field_ID`, `Field_Name`, `Description` FROM `Field` WHERE `Class_ID` = '".intval($goods_table)."'";
    $netcat_fields = array();

    foreach ((array) nc_db()->get_results($sql, ARRAY_A) as $netcat_field) {
      if ($reverced == true) {
        $netcat_fields[$netcat_field['Field_Name']] = $netcat_field['Field_ID'];
      } else {
        $netcat_fields[$netcat_field['Field_ID']] = '[' . $netcat_field['Field_Name'] . '] - ' . ($netcat_field['Description'] ? $netcat_field['Description'] : $netcat_field['Field_Name']);
      }
    }
    return $netcat_fields;
  }

}
