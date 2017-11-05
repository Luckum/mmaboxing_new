<?php

/* $Id: commerceml.php 8358 2012-11-07 11:36:56Z lemonade $ */
if (!class_exists("nc_System"))
    die("Unable to load file.");
do {

    if (!$silent_1c_import) {
        ?>
        <style>
            .divadd { border: 1px solid #DDDDDD; background-color: #F0F0F0;
                padding: 3px; }
            select { width: auto }
        </style>
        <script>
            function switch_divadd(gid){
                var sel = document.getElementById('map_groups' + gid),
                    val = sel.options[sel.selectedIndex].value;
                document.getElementById('divadd' + gid).style.display = (val == 'new' ? '' : 'none');
            }
        </script>
    <?

    } // of if (!silent_1c_import)
    @set_time_limit(0);

    // settings:
    // * $packets[$ext_name] = array(num=>, column=>)
    // * $groups[$ext_id] = array("name" => $name,
    //                        "sub_id" => $sub_id,
    //                        "parent_id" => $parent_id
    //                       );
    // * $units[unit_name] => id
    // * $currency[currency_name] => id
    // * $templates[subdivision_id] => array(class_id=>, subclass_id=>)
    // Логика работы:
    // * First pass: определить, у каких разделов/типов цен нет соответствия
    // * Second pass: записать соответствия, сохранить настройки в файл (кэш)
    // * Third+ pass: обработка товарных позиций


    $everything_clear = true;
    $settings = array();
    $units = array();
    $currency = array();
    $templates = array();

    // include xml library
    $NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -5)) . (strstr(__FILE__, "/") ? "/" : "\\");
    @include_once($NETCAT_FOLDER . "vars.inc.php");
    require_once("{$MODULE_FOLDER}netshop/old/xml.lib.php");

    // check!
    if (!function_exists('domxml_open_mem')) {
        print NETCAT_MODULE_NETSHOP_PHP4_DOMXML_REQUIRED;
        EndHtml();
        if ($silent_1c_import) {
            break;
        } else {
            exit;
        }
    }

    // load XML
    if (!$doc = @domxml_open_mem(join('', file($TMP_FOLDER . $filename)))) {
        print NETCAT_MODULE_NETSHOP_IMPORT_COMMERCEML_NOT_WELL_FORMED .
            ".<br><a href=import.php>" . NETCAT_MODULE_NETSHOP_BACK . "</a>";
        EndHtml();
        if ($silent_1c_import) {
            break;
        } else {
            exit;
        }
    }


    // Get list of goods templates -----------
    $templates_as_options = "";
    $res = q("SELECT DISTINCT c.Class_ID, c.Class_Name
                    FROM Class as c, Field as f
                    WHERE c.Class_ID=f.Class_ID
                      AND f.Field_Name LIKE 'Price%'
                      AND (c.Class_Group='Netshop' OR c.Class_Group='Интернет-магазин')
                    ORDER BY c.Priority, c.Class_ID
                   ");

    $templates_count = mysql_num_rows($res);
    $goods_template_ids = array();
    $i = 0;
    while (list($id, $name) = mysql_fetch_row($res)) {
        $templates_as_options .= "<option value=$id" . (
        $i == 0 ? " selected" : "") . ">$name</option>\n";
        $goods_template_ids[] = $id;
        $i++;
    }

    $GLOBALS["catalogue"] = $catalogue_id;
    $netshop = nc_netshop::get_instance($catalogue_id);


    // first/second pass: get settings and save them in the file
    if (!($cached = @join('', @file("$TMP_FOLDER$filename.cache")))) { // no cache
        $not_mapped_sections = 0;
        $not_mapped_packets = 0;

        // Get structure of the shop -------------
        if ($netshop->is_netshop_v1_in_use($catalogue_id)) {
            $shop = GetSubdivisionByType($GLOBALS["MODULE_VARS"]["netshop"]["SHOP_TABLE"], "Subdivision_ID, Subdivision_Name", $catalogue_id);
            $shop_subdivision_id = $shop["Subdivision_ID"];
        } else {
            $sql = "SELECT `root_subdivision_id` FROM `Netshop_ImportSources` WHERE `source_id` = '{$source_id}'";
            $shop_subdivision_id = value1($sql);
        }

        // external (1C) catalogueID and company ID
        // (исходя из предположения, что каталог один)
        $nodes = xpath($doc, "//*[local-name()='Каталог']");
        $ext_cat_id = xml_attr($nodes->nodeset[0], "Идентификатор");
        $ext_company_id = xml_attr($nodes->nodeset[0], "Владелец");
        q("UPDATE Netshop_ImportSources SET external_id='$ext_company_id $ext_cat_id'
          WHERE source_id=$source_id");

        // Группы -------------------------------------------------------------

        $nodes = xpath($doc, "//*[local-name()='Группа']");
        $groups = array();

        // группы могут идти не по порядку...
        // поэтому может понадобиться пересортировать их таким образом, чтобы
        // дочерние группы обрабатывались после групп более высокого уровня
        $groups_struct = array(); // родитель->дитё
        $groups_data = array(); // id->node
        $groups_list = array(); // id,id,id...
        foreach ($nodes->nodeset as $node) {
            $id = xml_attr($node, "Идентификатор");
            $parent_id = xml_attr($node, "Родитель");
            if (!$parent_id)
                $parent_id = 0;
            $groups_struct[$parent_id][] = $id;
            $groups_data[$id] = $node;
            $parent_ids[] = $parent_id;
        }

        $parent_index = $parent_ids[0];

        function nc_netshop_flatten_struct(&$struct, $index = 0) {
            $ret = array();
            if (!is_array($struct[$index])) {
                return $ret;
            }
            foreach ($struct[$index] as $item) {
                $ret[] = $item;
                if ($struct[$item]) {
                    $ret = array_merge($ret, nc_netshop_flatten_struct($struct, $item));
                }
            }
            return $ret;
        }

        $groups_list = nc_netshop_flatten_struct($groups_struct, $parent_index);

        foreach ($groups_list as $id) {
            $node = & $groups_data[$id];
            $name = xml_attr($node, "Наименование");
            $parent_id = xml_attr($node, "Родитель");
            $sub_id = 0;

            // Second pass, указано соответствие разделу? Сохранить. - - - - - -
            if ($_POST["map_groups"][$id] == -1) { // IGNORE!
                $sub_id = -1;
                $parent_sub_id = (int)$groups[$parent_id]["sub_id"];
                if (!$parent_sub_id)
                    $parent_sub_id = $shop_subdivision_id;
            } elseif ($_POST["map_groups"][$id] == "new") {
                // id of the parent subdivision, or shop id by default
                $parent_sub_id = (int)$groups[$parent_id]["sub_id"];
                if (!$parent_sub_id)
                    $parent_sub_id = $shop_subdivision_id;

                $english_name = nc_preg_replace("/\W+/", "", ucwords(tr($name)));

                // parent's settings
                if (!$parent[$parent_sub_id])
                    $parent[$parent_sub_id] = row("SELECT * FROM Subdivision WHERE Subdivision_ID=$parent_sub_id");

                $priority = (int)value1("SELECT MAX(Priority)+1 FROM Subdivision WHERE Parent_Sub_ID=$parent_sub_id");

                // user set the class (if there were alternatives)
                $template_id = $new_group[$id]["template"];
                // default is the only class

                $english_name_suffix = "";
                while (value1("SELECT COUNT(*) FROM Subdivision WHERE Parent_Sub_ID=$parent_sub_id AND EnglishName='" . ($english_name . $english_name_suffix) . "'")) {
                    $english_name_suffix += 1;
                }
                $english_name .= (string)$english_name_suffix;

                // create subdivision
                q("INSERT INTO Subdivision
                SET Catalogue_ID=$catalogue_id,
                    Parent_Sub_ID=$parent_sub_id,
                    Subdivision_Name='" . mysql_real_escape_string($name) . "',
                    Template_ID=0,
                    EnglishName='$english_name',
                    LastUpdated=NOW(), Created=NOW(),
                    Hidden_URL='{$parent[$parent_sub_id]['Hidden_URL']}$english_name/',
                    Priority=$priority,
                    Checked=1
                   ");

                $sub_id = mysql_insert_id();

                // link data template to newly created subdivision
                q("INSERT INTO Sub_Class
                SET Subdivision_ID=$sub_id,
                    Class_ID='{$new_group[$id]['template']}',
                    Sub_Class_Name='$name',
                    EnglishName='$english_name',
                    Priority=0,
                    Checked=1,
                    Catalogue_ID=$catalogue_id,
                    DefaultAction='index',
                    Created=NOW(),
                    LastUpdated=NOW()");

                $template_id = mysql_insert_id();

                // save mapping
                q("REPLACE INTO Netshop_ImportMap
                SET source_id=$source_id,
                    type='section',
                    source_string='" . mysql_real_escape_string($id) . "',
                    value=$sub_id
               ");
            } elseif (int($_POST["map_groups"][$id])) { // указано соответствие
                q("REPLACE INTO Netshop_ImportMap
                SET source_id=$source_id,
                    type='section',
                    source_string='" . mysql_real_escape_string($id) . "',
                    value=" . intval($_POST["map_groups"][$id])
                );
            } else {
                // Найти соответствие разделу (по внешнему идентификатору)
                $sub_id = value1("SELECT m.value
                               FROM Netshop_ImportMap as m, Subdivision as s
                               WHERE m.type='section'
                                 AND m.source_string='" . mysql_real_escape_string($id) . "'
                                 AND m.value=s.Subdivision_ID
                               ORDER BY m.source_id=$source_id DESC
                               LIMIT 1");

                if (!$sub_id) {
                    $not_mapped_sections++;
                } // спросить потом
            }

            $groups[$id] = array("name" => $name,
                "sub_id" => $sub_id,
                "parent_id" => $parent_id
            );
        }

        // Свойства ----------------------------------------------------------------
        $nodes = xpath($doc, "//*[local-name()='Свойство']");

        $properties = array();
        $property_price_type = 0;
        foreach ($nodes->nodeset as $node) {
            $id = xml_attr($node, "Идентификатор");
            $name = xml_attr($node, "Наименование");
            $properties[$id]["name"] = $name;

            // запомнить отдельно свойство "тип цены"
            if ($name == "Тип цены") {
                $property_price_type = $id;
            }
        }

        if (sizeof($properties)) {
            $res = q("SELECT source_string as id, value as pid
                    FROM Netshop_ImportMap
                    WHERE source_id=$source_id
                      AND type='property'
                      AND source_string IN ('" . join("','", array_keys($properties[$id])) . "')");
            while (list($id, $pid) = mysql_fetch_row($res)) {
                $properties[$id]["pid"] = $pid;
            }
        }
        // Пакеты предложений ------------------------------------------------------
        if (!$property_price_type) {
            print("Не найдены пакеты предложений.");
            if ($silent_1c_import) {
                break;
            } else {
                exit;
            }
        }

        $nodes = xpath($doc, "//*[local-name()='ПакетПредложений']/*[local-name()='ЗначениеСвойства'][@ИдентификаторСвойства='$property_price_type']");

        foreach ($nodes->nodeset as $num => $node) {
            $name = mysql_real_escape_string(xml_attr($node, "Значение"));
            $packets[$name]["num"] = $num + 1;

            // Записать соответствия (second pass)
            if ($map_packets[urlencode($name)]) {
                q("REPLACE INTO Netshop_ImportMap
                SET source_id=$source_id,
                    type='price',
                    source_string='$name',
                    value='" . $map_packets[urlencode($name)] . "'");
            }
        }

        $res = q("SELECT source_string as id, value
                 FROM Netshop_ImportMap
                 WHERE source_id=$source_id
                   AND type='price'
                   AND source_string IN ('" . join("','", array_keys($packets)) . "')");

        while (list($id, $value) = mysql_fetch_row($res)) {
            $packets[$id]["column"] = $value;
        }

        $not_mapped_packets = sizeof($packets) - mysql_num_rows($res);


        // Спросить, что не ясно (Группы/Пакеты) -------------------------------

        if ($not_mapped_sections && !$silent_1c_import) {
            $everything_clear = false;

            $sections = GetStructure($shop_subdivision_id, "Checked=1");

            $sections_as_options = "";
            foreach ($sections as $row) {
                $sections_as_options .= "<option value='$row[Subdivision_ID]'>" .
                    str_repeat("&nbsp;", ($row["level"] + 1) * 4) .
                    "$row[Subdivision_Name]</option>\n";
            }


            // Ask about groups we don't know --------

            print "<b>" . NETCAT_MODULE_NETSHOP_IMPORT_MAP_SECTION . ":</b>\n
                 <table border=0 cellspacing=8 cellpadding=0>";

            foreach ($groups as $gid => $group) {
                if (!$group["sub_id"]) {
                    $parent = $group['parent_id'];
                    if (!$parent)
                        $parent = "[root]";
                    print "<tr valign=top><td title='$gid &larr; $parent'>$group[name]</td><td>&rarr;</td><td>
                        <select name='map_groups[$gid]'" .
                        ($templates_count > 1 ? " onchange='switch_divadd(\"$gid\")'" : "") .
                        " id='map_groups$gid'>
                         <option value='new' style='color:navy'>" . NETCAT_MODULE_NETSHOP_IMPORT_CREATE_SECTION .
                        ($templates_count > 1 ? " &nbsp; &darr; &nbsp;" : "") .
                        "<option value='-1'>" . NETCAT_MODULE_NETSHOP_IMPORT_IGNORE_SECTION . "
                         <option value='-1'>----------------------------------------
                         $sections_as_options
                        </select>
                        <div class=divadd id='divadd$gid'" .
                        ($templates_count == 1 ? " style='display:none'" : "")
                        . ">";


                    print NETCAT_MODULE_NETSHOP_IMPORT_TEMPLATE . ":
                          <select name='new_group[" . htmlspecialchars($gid) . "][template]'>
                            $templates_as_options
                          </select>
                        </div>
                       </td></tr>\n";
                }
            }

            print "</table><br>";
        } //  of "if ($not_mapped_sections)"

        if ($not_mapped_packets && !$silent_1c_import) {
            $everything_clear = false;

            print "<b>" . NETCAT_MODULE_NETSHOP_IMPORT_MAP_PRICE . ":</b>
                 <table border=0 cellspacing=8 cellpadding=0>";

            $num_goods_types = value1("SELECT COUNT(DISTINCT(c.Class_ID))
                                     FROM Field as f, Class as c
                                     WHERE f.Field_Name LIKE 'Price%'
                                       AND c.Class_ID=f.Class_ID
                                       AND (c.Class_Group='Netshop' OR c.Class_Group='Интернет-магазин')
                                    ");

            $res = q("SELECT f.Field_Name, f.Description, COUNT(f.Field_Name) as cnt
                    FROM Class as c, Field as f
                    WHERE c.Class_ID=f.Class_ID
                      AND f.Field_Name LIKE 'Price%'
                      AND (c.Class_Group='Netshop' OR c.Class_Group='Интернет-магазин')
                    GROUP BY f.Field_Name
                    HAVING cnt=$num_goods_types
                   ");

            $price_col_options = "";

            while (list($id, $name, $num_occured) = mysql_fetch_row($res)) {
                $price_col_options .= "<option value='$id'>[$id] $name\n";
            }

            foreach ($packets as $name => $arr) {
                if (!$arr["column"]) {
                    print "<tr><td>$name</td><td>&rarr;</td>
                       <td><select name='map_packets[" . urlencode($name) . "]'>
                         <option value='-1'>
                         $price_col_options
                       </select></td></tr>";
                }
            }
            print "</table><br>";
        } // of "if not_mapped_packets"
    } else { // there are cached settings, get 'em
        $settings = unserialize($cached);
        extract($settings);
    }

    // Load currencies, units and data templates
    if ($everything_clear && (!$units || !$currency)) {
        // currencies
        $res = q("SELECT ShopCurrency_ID, ShopCurrency_Name FROM Classificator_ShopCurrency");
        while (list($id, $name) = mysql_fetch_row($res)) {
            $currency[$name] = $id;
        }

        // units
        $res = q("SELECT ShopUnits_ID, ShopUnits_Name FROM Classificator_ShopUnits");
        while (list($id, $name) = mysql_fetch_row($res)) {
            $units[$name] = $id;
        }

        // data templates (classes)
        $sub_ids = array();
        foreach ($groups as $row) { // if template is unknown, get it
            if (!$templates[$row["sub_id"]]["subclass_id"] && $row["sub_id"]) {
                $sub_ids[] = $row["sub_id"];
            }
        }


        if ($sub_ids) {
            $res = q("SELECT Subdivision_ID, Class_ID, Sub_Class_ID
                   FROM Sub_Class
                   WHERE Subdivision_ID IN (" . join(",", $sub_ids) . ")
                   ORDER BY Priority DESC");
            while (list($sub_id, $class_id, $subclass_id) = mysql_fetch_row($res)) {
                $templates[$sub_id]["class_id"] = $class_id;
                $templates[$sub_id]["subclass_id"] = $subclass_id;
            }
            /* !!! т.о. будет взят первый шаблон */
        }
    }

    // number of goods in the source
//   $count = xpath($doc, "count(//Товар)"); // doesn't work with php5+convertor
//   $count = $count->value;
    $count = xpath($doc, "//*[local-name()='Товар']");
    $count = sizeof($count->nodeset); // :-(

    if ($everything_clear && !$silent_1c_import) {
        //<div id='import_progress'></div>
        print "
    <div id='import_progress_line' style='position:absolute; border:1px solid #FFF; height:20px; width:0px; background:#5699c7;'></div>\r\n
    <div style='position:absolute; border:1px solid #333; text-align:center; height:20px; width:420px; background:none; color:#264863'><p id='import_progress_text' style='padding:0; margin:2px 0 0'>0%</p></div>\r\n
    <br clear='all'/>\r\n
     <script>
       function iprcnt(p) {
         try {
           document.getElementById('import_progress_line').style.width = (4.2 * Math.floor(p) ) + 'px';\r\n
           document.getElementById('import_progress_text').innerHTML = p + '%';\r\n
           document.getElementById('import_progress_text').style.color = '#FFF';
         } catch (e) {}
       }
      </script>
     ";
    }

    while (@ob_get_level()) {
        @ob_end_flush();
    }
    flush();

    if ($everything_clear) {
        //disable all positions
        $disable_positions_query = $db->get_row("SELECT `nonexistant` FROM `Netshop_ImportSources` WHERE `source_id` = '{$source_id}'", ARRAY_A);
        $disable_positions = $disable_positions_query && $disable_positions_query['nonexistant'] == 'disable';
        if ($disable_positions) {
            $class_ids = array();
            $sql = "SELECT `value` FROM `Netshop_ImportMap` WHERE `type` = 'section' AND `source_id` = '{$source_id}'";
            $subdivisions_query = $db->get_results($sql, ARRAY_A);

            foreach ((array)$subdivisions_query as $subdivision) {
                $subdivision_id = (int)$subdivision['value'];
                if ($subdivision_id) {
                    $sql = "SELECT `Class_ID` FROM `Sub_Class` WHERE `Subdivision_ID` = {$subdivision_id} LIMIT 1";
                    $class_id_query = $db->get_row($sql, ARRAY_A);
                    if ($class_id_query) {
                        $class_id = $class_id_query['Class_ID'];
                        if (!in_array($class_id, $class_ids)) {
                            $class_ids[] = $class_id;
                        }
                    }
                }
            }

            $sql = "SELECT `value` FROM `Netshop_ImportMap` WHERE `type` <> 'section' AND `value` <> -1 AND `source_id` = '{$source_id}'";
            $fields_query = $db->get_results($sql, ARRAY_A);

            foreach ((array)$fields_query as $field) {
                $field_id = (int)$field['value'];
                if ($field_id) {
                    $sql = "SELECT `Class_ID` FROM `Field` WHERE `Field_ID` = {$field_id} LIMIT 1";
                    $class_id_query = $db->get_row($sql, ARRAY_A);
                    if ($class_id_query) {
                        $class_id = $class_id_query['Class_ID'];
                        if (!in_array($class_id, $class_ids)) {
                            $class_ids[] = $class_id;
                        }
                    }
                }
            }

            foreach ($class_ids AS $class_id) {
                $sql = "UPDATE `Message{$class_id}` SET `Checked` = 0 WHERE `ImportSourceID` = '{$source_id}'";
                $db->query($sql);
            }
        }
    }

    // FOREACH GOODS while everything is clear --------------------------------
    for ($current_num++; $current_num <= $count && $everything_clear; $current_num++) {
        $this_prop = array();

        $nodes = xpath($doc, "//*[local-name()='Товар'][$current_num]");
        $node = $nodes->nodeset[0];


        $this_prop["Checked"] = 1;
        $this_prop["ItemID"] = xml_attr($node, "Идентификатор");

        $parent_ext_id = xml_attr($node, "Родитель");
        $this_prop["Subdivision_ID"] = $groups[$parent_ext_id]["sub_id"];
        $this_class = $templates[$this_prop["Subdivision_ID"]]["class_id"];

        if ($this_prop["Subdivision_ID"] == -1) { // IGNORE SECTION
            continue; // go to next item
        }
        // we don't know what it is
        if (!$this_class || !in_array($this_class, $goods_template_ids)) {
            continue;
        } // next item

        $this_prop["Sub_Class_ID"] = $templates[$this_prop["Subdivision_ID"]]["subclass_id"];

        // basic properties
        $this_prop["Name"] = xml_attr($node, "Наименование");

        $this_units = xml_attr($node, "Единица");
        if (!$units[$this_units]) { // we don't know these units
            q("INSERT INTO Classificator_ShopUnits SET ShopUnits_Name='$this_units'");
            $units[$this_units] = mysql_insert_id();
        }
        $this_prop["Units"] = $units[$this_units];
        $this_prop["ImportSourceID"] = $source_id;


        // цены товара
        foreach ($packets as $packet) {
            if ($packet["column"] != -1) {
                $price = xpath($doc, "//*[local-name()='ПакетПредложений'][$packet[num]]/*[local-name()='Предложение'][@ИдентификаторТовара='{$this_prop[ItemID]}']");
                // get price
                $this_prop[$packet["column"]] = xml_attr($price->nodeset[0], "Цена");
                //get StockUnits
                $this_prop["StockUnits"] = xml_attr($price->nodeset[0], "Количество");
                // get currency
                $this_currency = xml_attr($price->nodeset[0], "Валюта");
                if ($this_currency == "руб.")
                    $this_currency = "RUR";

                // check if currency exists, else add it
                if (!$this_currency) { // валюта не указана
                    $this_currency = "RUR";
                } else if (!$currency[$this_currency]) {
                    q("INSERT INTO Classificator_ShopCurrency SET ShopCurrency_Name='$this_currency'");
                    $currency[$this_currency] = mysql_insert_id();
                }
                /* !!! TODO: check (a) price column exists in template */
                $currency_add = str_replace("Price", "", $packet["column"]);
                $this_prop["Currency$currency_add"] = $currency[$this_currency];
            }
        } // of "foreach packet"
        // save data
        // (a) try to find goods with same Item ID
        $this_id = value1("SELECT Message_ID
                         FROM Message$this_class
                         WHERE ItemID='$this_prop[ItemID]'");
        // (b) try to find goods with same name in that subdivision
        if (!$this_id) {
            /* !!! исходим из допущения, что имя товара уникально в данном разделе */
            $this_id = value1("SELECT Message_ID
                            FROM Message$this_class
                            WHERE Subdivision_ID='{$groups[$this_prop[ItemID]][sub_id]}'
                              AND Name='" . mysql_real_escape_string($this_prop["Name"]) . "'");
        }

        $qry = array();
        foreach ($this_prop as $k => $v) {
            $qry[] = "$k = '" . mysql_real_escape_string($v) . "'";
        }
        $qry = "SET " . join($qry, ", ");
        if (!$this_id) { // create new goods
            q("INSERT INTO Message$this_class $qry");
        } else { //  update existing data
            q("UPDATE Message$this_class $qry WHERE Message_ID=$this_id");
        }

        //if (!($current_num % 10)) {
        //if ($silent_1c_import) { print " "; }
        //else {
        $percent = sprintf("%.2f", ($current_num / $count * 100));
        print "<script>iprcnt($percent);</script>\n";
        //}
        flush();
        //}
    }

    print "<br>";
    if ($current_num > $count && $everything_clear) {
        unlink("$TMP_FOLDER$filename");
        @unlink("$TMP_FOLDER$filename.cache");

        $sql = "UPDATE `Netshop_ImportSources` SET `last_update` = NOW() WHERE `source_id` = $source_id";
        $db->query($sql);

        if (!$silent_1c_import) {
            //print "<script>
            //  try {
            //    document.getElementById('import_progress').innerHTML = '';
            //  } catch (e) {}
            // </script>
            //";
            print "<h3>" . NETCAT_MODULE_NETSHOP_IMPORT_DONE . ".</h3>";
            $our_key = $netshop->is_netshop_v1_in_use($catalogue_id) ?
                $GLOBALS["MODULE_VARS"]["netshop"]["SECRET_KEY"] :
                $netshop->get_setting('1cSecretKey');
            printf(NETCAT_MODULE_NETSHOP_IMPORT_1C_LINK, "http://" . $HTTP_HOST . $SUB_FOLDER . $HTTP_ROOT_PATH . "modules/netshop/import/1c.php?source_id=$source_id&key=" .
                md5("$our_key$source_id") . "&a"); // sic!
            EndHtml();
            exit;
        } else {
            break;
        }
    }


    // =========================================================================
    // CACHE SETTINGS IN FILE (presumably, it can save some time on
    // script startup since we don't need to search for the settings in
    // the database
    if ($currency) { // simple and not reliable check whether it's time to create cache
        $settings = array("groups" => $groups, "packets" => $packets,
            "currency" => $currency, "units" => $units,
            "templates" => $templates);

        $cached = serialize($settings);
        $fp = fopen("$TMP_FOLDER$filename.cache", "w");
        fputs($fp, $cached);
        fclose($fp);
    }
} while (0);
?>