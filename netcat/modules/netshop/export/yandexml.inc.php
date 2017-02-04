<?php

class YML_Export_V1 extends nc_mod_netshop
{

  private $_CurrencyArray;
  private $_MaxNameLen;
  private $_class_ids;

  public function __construct()
  {

    parent::__construct();
    $this->_CurrencyArray = Array('RUR', 'RUB', 'USD', 'EUR', 'UAH');
    $this->_MaxNameLen = 20;
    $nc_core = nc_Core::get_object();
    $this->_class_ids = $nc_core->modules->get_vars('netshop', 'GOODS_TABLE');
  }

  /**
   * Экспорт в формате YandexML
   * @param int раздел, который надо экспортировать (по умолчанию - весь магазин)
   */
  public function ExportYML($section = 0)
  {
    global $HTTP_HOST, $SUB_FOLDER;
    global $db, $nc_core;
    global $catalogue;


    if (!$this->shop_id)
      return false;
    $shopName = (nc_strlen($this->ShopName) > $this->_MaxNameLen) ? nc_substr($this->ShopName, 0, $this->_MaxNameLen) : $this->ShopName;
    $default_currency = $this->Currencies[$this->DefaultCurrencyID];

    header("Content-type: text/xml");
    $ret = "<?xml version=\"1.0\" encoding=\"" . $nc_core->NC_CHARSET . "\"?>
              <!DOCTYPE yml_catalog SYSTEM \"shops.dtd\">
              <yml_catalog date=\"" . (strftime("%Y-%m-%d %H:%M")) . "\">
              <shop>
                <name>" . xmlspecialchars($shopName) . "</name>
                <company>" . xmlspecialchars($this->CompanyName) . "</company>
                <url>http://" . $HTTP_HOST . $SUB_FOLDER . "/</url>
                  <currencies>
                    <currency id=\"" . $default_currency . "\" rate=\"1\" />";
    foreach ((array) $this->Currencies as $k => $v) {
      if ($v != $default_currency && $this->Rates[$k] && in_array($v, $this->_CurrencyArray)) {
        $ret .= "<currency id=\"$v\" rate=\"" . $this->Rates[$k] . "\" />";
      }
    }

    $ret .= "</currencies>
              <categories>\n";

    // output categories (shop structure) ---------------------------
    // ----------------------------------------
    if (!$section)
      $section = $this->_class_ids;
    $structure = GetStructureYandexml($section, $catalogue);
    if (!$structure)
      return;

    $all_sections_ids = array(); // потом вытащим на основе этих данных товары

    foreach ($structure as $row) {
      $ret .= "<category id=\"{$row['Subdivision_ID']}\"";

      if (array_key_exists($row['Parent_Sub_ID'], $structure)) {
        $ret .= " parentId=\"{$row['Parent_Sub_ID']}\"";
      }

      $ret .= ">" . xmlspecialchars($row["Subdivision_Name"]) . "</category>\n";

      $all_sections_id[] = $row["Subdivision_ID"];
    }

    $ret .= "</categories>\n<offers>";

    // GOODS CATALOGUE -----------------------------------------------
    $output = array(
        "URL" => "url",
        "Price" => "price",
        "CurrencyID" => "currencyId",
        "Subdivision_ID" => "categoryId",
        "Image" => "picture",
        "Vendor" => "vendor",
        "VendorCode" => "vendorCode",
        "Name" => "name",
        "Description" => "description",
        "SalesNotes" => "sales_notes",
    );

    // получить типы товаров
    $goods_class_ids = $this->GuessGoodsTypeIDs();

    // все разделы магазина
    $subdivision_id = join(",", $all_sections_id);

    foreach ($goods_class_ids as $class_id) {
      $query = "SELECT m.*,
                         ShopCurrency_Name AS CurrencyID,
                         CONCAT(u.Hidden_URL, s.EnglishName, '_', m.Message_ID, '.html') as URL,
                         IFNULL(m.$this->PriceColumn, parent.$this->PriceColumn) as Price4User,
                         IF(m.$this->PriceColumn IS NULL, parent.$this->CurrencyColumn, m.$this->CurrencyColumn) as Currency4User

                FROM (`Message" . $class_id . "` as m, `Subdivision` as u, `Sub_Class` as s)
                  LEFT JOIN Message" . $class_id . " as parent
                    ON (m.`Parent_Message_ID` != 0 AND m.`Parent_Message_ID` = parent.`Message_ID`)
                  LEFT JOIN `Classificator_ShopCurrency`
                    ON Classificator_ShopCurrency.`ShopCurrency_ID` = m.`Currency`
                WHERE m.`Checked` = 1
                    AND m.`Subdivision_ID` IN (" . $subdivision_id . ")
                    AND s.`Subdivision_ID` = m.`Subdivision_ID`
                    AND s.`Sub_Class_ID` = m.`Sub_Class_ID`
                    AND u.`Subdivision_ID` = m.`Subdivision_ID`
                HAVING `Price4User` > 0
                    ";

      $rows = $db->get_results($query, ARRAY_A);
      foreach ((array) $rows as $row) {

        if (strlen($row["StockUnits"])) {
          $row["Available"] = ($row["StockUnits"] ? "true" : "false");
        } else {
          $row["Available"] = "true";
        }

        // convert to default currency
        $row["Price"] = $this->ConvertCurrency($row["Price4User"], $row["Currency4User"]);
        // we'll need an absolute url
        $row["URL"] = "http://" . $HTTP_HOST . $SUB_FOLDER . "$row[URL]";
        $row["CurrencyID"] = $row["CurrencyID"] ? $row["CurrencyID"] : $default_currency;

        if ($row["Image"]) { // replace to image url
          $row["Image"] = "http://" . $HTTP_HOST . $SUB_FOLDER . nc_file_path($class_id, $row["Message_ID"], "Image", "h_");
        }

        $ret .= "<offer id=\"" . sprintf("%d%05d", $class_id, $row["Message_ID"]) . "\"";
        $vendormodel = 0;
        if ($row['Vendor'] || $row['VendorCode']) {
          $ret .= " type=\"vendor.model\"";
          $vendormodel = 1; // произвольный товар
        }
        $ret .= " available=\"$row[Available]\"";
        $ret .= ">\n";

        $curr_comp = new nc_Component($class_id);
        $fields = $curr_comp->get_fields();
        foreach ($fields as $f) {
          $fields_assoc[$f['name']] = $f;
        }

        foreach ($output as $idx => $tag) {
          if ($row[$idx]) {

            $value = $row[$idx];

            if ($fields_assoc[$idx]['type'] == 4) { // список
              $list_name = $db->escape(strtok($fields_assoc[$idx]['format'], ':'));
              if (!isset($classificators[$list_name])) {
                $db->query("SELECT `" . $list_name . "_ID`, `" . $list_name . "_Name` FROM `Classificator_" . $list_name . "`");
                $classificators[$list_name] = array_combine($db->get_col(NULL, 0), $db->get_col(NULL, 1));
              }
              $value = $classificators[$list_name][$value];
            } elseif ($fields_assoc[$idx]['type'] == 10) { //множественный выбор
              $list_name = $db->escape(strtok($fields_assoc[$idx]['format'], ':'));
              if (!isset($classificators[$list_name])) {
                $db->query("SELECT `" . $list_name . "_ID`, `" . $list_name . "_Name` FROM `Classificator_" . $list_name . "`");
                $classificators[$list_name] = array_combine($db->get_col(NULL, 0), $db->get_col(NULL, 1));
              }
              $value_ids = explode(",", $value);
              $value = '';
              foreach ($value_ids as $val_id) {
                if ($val_id) {
                  $value .= $classificators[$list_name][$val_id] . ", ";
                }
              }
              $value = nc_substr($value, 0, -2);
            }

            if ($tag == 'name' && $row['GroupName'] != '') {
              $ret .= "<$tag>" . xmlspecialchars(strip_tags($row['GroupName'])) . " - " . xmlspecialchars(strip_tags($value)) . "</$tag>\n";
            } else {
              if ($vendormodel && $tag == 'name')
                $tag = 'model';
              $ret .= "<$tag>" . xmlspecialchars(strip_tags($value)) . "</$tag>\n";
            }
          }
        }

        $ret .= "</offer>\n";
      }
    }
    // ---------------------------------------------------------------

    $ret .= "</offers>\n</shop>\n</yml_catalog>";
    print $ret;
    // return $ret;
  }

}

class YML_Export_V2
{

  /**
   * @var nc_netshop
   */
  private $netshop;

  /**
   * @param nc_netshop $netshop
   */
  public function __construct(nc_netshop $netshop)
  {
    $this->netshop = $netshop;
  }

  /**
   * Экспорт в формате YandexML
   * @param int раздел, который надо экспортировать (по умолчанию - весь магазин)
   */
  public function ExportYML($section = 0)
  {
    $nc_core = nc_core();

    header("Content-Type: text/xml");

    echo '<?xml version="1.0" encoding="' . $nc_core->NC_CHARSET . '"?>' . PHP_EOL;
  }

}

class YML_Export_V3
{

  /**
   * @var nc_netshop
   */
  private $netshop;

  /**
   * @param nc_netshop $netshop
   */
  public function __construct(nc_netshop $netshop, $domain)
  {
    $this->netshop = $netshop;
    $this->nc_core = nc_core();
    $this->domain = !empty($domain) ? $domain : $this->nc_core->DOMAIN_NAME;
    $this->_MaxNameLen = 20;
  }

  /**
   * Экспорт в формате YandexML
   */
  public function ExportYML($catalogue)
  {

    $db = nc_db();

    $name = $this->netshop->get_setting('ShopName');
    $name = (nc_strlen($name) > $this->_MaxNameLen) ? nc_substr($name, 0, $this->_MaxNameLen) : $name;
    $company = $this->netshop->get_setting('CompanyName');

    $currencies = $this->netshop->get_setting('Currencies');
    if ($this->netshop->get_setting('DefaultCurrencyID') > 0) {
      $default_currency = $currencies[$this->netshop->get_setting('DefaultCurrencyID')];
    } else {
      $default_currency = reset($currencies);
    }

    $rates = $this->netshop->get_setting('Rates');

    $ret_head = array();
    $ret_head[] = "<?xml version=\"1.0\" encoding=\"" . $this->nc_core->NC_CHARSET . "\"?>\n";
    $ret_head[] = "<!DOCTYPE yml_catalog SYSTEM \"shops.dtd\">\n";
    $ret_head[] = "<yml_catalog date=\"" . (strftime("%Y-%m-%d %H:%M")) . "\">\n";
    $ret_head[] = "\t<shop>\n";
    $ret_head[] = "\t\t<name>" . xmlspecialchars($name) . "</name>\n";
    $ret_head[] = "\t\t<company>" . xmlspecialchars($company) . "</company>\n";
    $ret_head[] = "\t\t<url>http://" . $this->domain . "/</url>\n";
    $ret_head[] = "\t\t<currencies>\n";
    $ret_head[] = "\t\t\t<currency id=\"" . $default_currency . "\" rate=\"1\"/>\n";

    foreach ($currencies as $key => $currency) {
      if ($currency != $default_currency) {
        $ret_head[] = "\t\t\t<currency id=\"" . $currency . "\"" . ((isset($rates[$key]) && $rates[$key] > 0) ? " rate=\"" . $rates[$key] . "\"" : " rate=\"CB\"") . "/>\n";
      }
    }
    $ret_head[] = "\t\t</currencies>\n";
    $ret_head[] = "\t\t<categories>\n";


    //берем структуру каталога
    $structure = GetStructureYandexml(implode(",", $this->netshop->get_goods_components_ids()), $this->netshop->get_catalogue_id());

    $all_sections_id = array();
    if (is_array($structure) && count($structure) > 0) {
      foreach ($structure as $category) {
        $ret_tmp = "\t\t<category id=\"{$category['Subdivision_ID']}\"";

        if (array_key_exists($category['Parent_Sub_ID'], $structure)) {
          $ret_tmp .= " parentId=\"{$category['Parent_Sub_ID']}\"";
        }
        $ret_tmp .= ">" . xmlspecialchars($category["Subdivision_Name"]) . "</category>\n";
        $all_sections_id[$category["Class_ID"]] = $category["Subdivision_ID"];
        $ret_head[] = $ret_tmp;
      }
      $ret_head[] = "\t\t</categories>\n";

      $yandex = new nc_netshop_yandex($this->netshop);

      $bundles = $yandex->get_bundles_list();
      if (is_array($bundles) && count($bundles) > 0) {
        foreach ($bundles as $bundle) {
          $ret_offer = array();
          $bundle_id = $bundle['Bundle_ID'];


          $ret_offer[] = "\t\t<offers>\n";
          foreach ($this->netshop->get_goods_components_ids() as $goods_table) {

            $bundle = new nc_netshop_yandex_bundle($bundle_id);
            $fields_obj = $bundle->get_fields_object($bundle->get('type'));
            $xml_fields = $fields_obj->get_fields();

            // берем map_values
            $map_values = $bundle->get_map_values($bundle_id, 'class', $goods_table);
            if (count($map_values) > 0) {

              // берем поля товара
              $sql = "SELECT `Field_ID`, `Field_Name`, `Description` FROM `Field` WHERE `Class_ID` = {$goods_table}";
              $netcat_fields = array();

              $parent_fields = "";
              $parent_join = "";

              foreach ((array) $db->get_results($sql, ARRAY_A) as $netcat_field) {
                $netcat_fields[$netcat_field['Field_ID']] = $netcat_field['Field_Name'];
                if ($netcat_field['Field_Name'] == 'Name') {
                  $parent_fields .= ", parent.Name as Parent_Name ";
                  $parent_join = " LEFT JOIN `Message{$goods_table}` as parent ON parent.Message_ID = m.Parent_Message_ID AND m.Parent_Message_ID <> 0 ";
                }
              }

              // берем товары
              $goods_data = (array) $db->get_results("SELECT m.*, CONCAT(u.Hidden_URL, s.EnglishName, '_', m.Message_ID, '.html') as URL"
                              . $parent_fields
                              . " FROM `Message{$goods_table}` as m "
                              . " JOIN `Subdivision` as u ON m.`Subdivision_ID`=u.`Subdivision_ID` "
                              . " JOIN `Sub_Class` as s ON s.`Subdivision_ID` = m.`Subdivision_ID` AND s.`Sub_Class_ID` = m.`Sub_Class_ID` "
                              . $parent_join
                              . "WHERE m.`Checked` = 1
                    AND m.`Subdivision_ID` IN (" . $all_sections_id[$goods_table] . ")"
                              . "", ARRAY_A);


              foreach ($goods_data as $row) {
                // stock hook
                if (isset($row["StockUnits"]) && strlen($row["StockUnits"])) {
                  $row["Available"] = ($row["StockUnits"] ? "true" : "false");
                } else {
                  $row["Available"] = "true";
                }
                // image hook
                if (isset($row["Image"])) {
                  $row["Image"] = "http://" . $this->domain . nc_file_path($goods_table, $row["Message_ID"], "Image", "h_");
                }
                // name hook
                if (isset($row["Name"]) && $row["Name"] == "") {
                  $row["Name"] = $row["Parent_Name"];
                }

                $ret_offer[] = "\t\t\t<offer id=\"" . sprintf("%d%05d", $goods_table, $row["Message_ID"]) . "\" available=\"" . $row["Available"] . "\"  " . $fields_obj->get_vendor_type() . ">\n";
                foreach ($xml_fields as $field => $attrs) {
                  $field_value = "";
                  if ($attrs['editable'] == true && !empty($map_values[$field])) {
                    $field_value = $row[$netcat_fields[$map_values[$field]]];
                  } else {
                    //todo improve
                    switch ($field) {
                      case 'categoryId':
                        $field_value = $all_sections_id[$goods_table];
                        break;
                      case 'url':
                        $field_value = "http://" . $this->domain . $row["URL"];
                        break;
                    }
                  }
                  if ((isset($field_value) && $field_value !== '') || $attrs['required'] == true) {
                    $ret_offer[] = "\t\t\t\t<" . $field . ">" . $field_value . "</" . $field . ">\n";
                  }
                }
                $ret_offer[] = "\t\t\t</offer>\n";
              }
            }
          }
          $ret_offer[] = "\t\t</offers>\n";
          $ret_offer[] = "\t</shop>\n";
          $ret_offer[] = "</yml_catalog>";

          $file_name = $this->nc_core->MODULE_FOLDER . "netshop/export/yandex/bundle" . $bundle_id . ".xml";
          $dir_name = dirname($file_name);

          if (!file_exists($dir_name)) {
            mkdir($dir_name, 0777);
          }
          if (!is_writable($dir_name)) {
            @chmod($dir_name, 0777);
          }

          if (!file_exists($dir_name."/shops.dtd") && file_exists($this->nc_core->MODULE_FOLDER . "netshop/export/shops.dtd")) {
            copy($this->nc_core->MODULE_FOLDER . "netshop/export/shops.dtd", $dir_name."/shops.dtd");
          }

          if (file_exists($file_name)) {
            @unlink($file_name);
          }
          $fp = fopen($file_name, "w+");
          foreach (array_merge($ret_head, $ret_offer) as $str) {
            fputs($fp, $str);
          }
          fclose($fp);
          @chmod($file_name, 0777);
        }
      }
    }
  }

}
