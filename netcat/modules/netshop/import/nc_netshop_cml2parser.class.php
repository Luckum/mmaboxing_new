<?php

/**
 * CML2 import class
 */
class nc_netshop_cml2parser {

    var $source_id, $catalogue_id, $everything_clear;

    function nc_netshop_cml2parser(&$db, &$_UTFConverter, $source_id, $catalogue_id, $filename, $quite = false) {
        // system superior object
        $nc_core = nc_Core::get_object();
        $this->nc_core = $nc_core;
        global $MODULE_FOLDER;
        if ($this->nc_core->NC_UNICODE) {
            require_once $MODULE_FOLDER . "netshop/ru_utf8.lang.php";
        } else {
            require_once $MODULE_FOLDER . "netshop/ru_cp1251.lang.php";
        }
        require_once($MODULE_FOLDER . "../require/classes/nc_imagetransform.class.php");
        // get netshop module's vars
        $this->MODULE_VARS = $nc_core->modules->get_vars('netshop');

        // set variables
        $this->db = $db;
        $this->uniconv = $_UTFConverter;
        $this->filedir = $GLOBALS['TMP_FOLDER'];
        $this->filename = $filename;
        $this->filename_path = $this->filedir . $this->filename;
        $this->source_id = intval($source_id);
        $this->catalogue_id = intval($catalogue_id);
        $this->everything_clear = true;
        //
        $this->not_mapped_sections = 0;
        $this->not_mapped_fields = 0;
        $this->not_mapped_stores = 0;
        $this->not_mapped_characteristics = 0;
        $this->not_mapped_packets = 0;
        $this->quite = $quite;
        $this->debug = true;

        $this->import_ignore_tags = array(); //"Группы", "ЗначенияРеквизитов", "СтавкиНалогов"
        // cml2 class
        require_once($GLOBALS['MODULE_FOLDER'] . "netshop/import/cml2.class.php");
        $this->cml2 = new cml2();

        // Load currencies, units
        $res = $this->db->get_results("SELECT `ShopCurrency_ID`, `ShopCurrency_Name` FROM `Classificator_ShopCurrency`", ARRAY_A);
        foreach ($res AS $value) {
            $this->currency[$value['ShopCurrency_Name']] = $value['ShopCurrency_ID'];
        }
        $res = $this->db->get_results("SELECT `ShopUnits_ID`, `ShopUnits_Name` FROM `Classificator_ShopUnits`", ARRAY_A);
        foreach ($res AS $value) {
            $this->units[$value['ShopUnits_Name']] = $value['ShopUnits_ID'];
        }

        // Get list of goods templates
        $netshop = nc_netshop::get_instance($catalogue_id);

        if ($netshop->is_netshop_v1_in_use($catalogue_id)) {
            $shop = GetSubdivisionByType($this->MODULE_VARS["SHOP_TABLE"], "Subdivision_ID, Subdivision_Name", $catalogue_id);
            $this->shop_subdivision_id = $shop['Subdivision_ID'];
            $this->shop_classes = $this->db->get_results("SELECT DISTINCT c.`Class_ID` AS id, c.`Class_Name` AS name
      FROM `Class` AS c
			LEFT JOIN `Field` AS f ON c.`Class_ID` = f.`Class_ID`
      WHERE f.`Field_Name` LIKE 'Price%'
      AND c.`Class_ID` IN (" . $this->MODULE_VARS['GOODS_TABLE'] . ")
      ORDER BY c.Priority, c.`Class_ID`", ARRAY_A);
        } else {
            $sql = "SELECT `root_subdivision_id` FROM `Netshop_ImportSources` WHERE ";
            if ($source_id) {
                $sql .= "`source_id` = {$source_id}";
            } else {
                $sql .= "`catalogue_id` = {$catalogue_id}";
            }
            $sql .= " LIMIT 1";
            $this->shop_subdivision_id = $this->db->get_var($sql);
            $goods_table = implode(',', $netshop->get_goods_components_ids());
            $this->shop_classes = $this->db->get_results("SELECT DISTINCT c.`Class_ID` AS id, c.`Class_Name` AS name
      FROM `Class` AS c
			LEFT JOIN `Field` AS f ON c.`Class_ID` = f.`Class_ID`
      WHERE f.`Field_Name` LIKE 'Price%'
      AND c.`Class_ID` IN ({$goods_table})
      ORDER BY c.Priority, c.`Class_ID`", ARRAY_A);
        }

        // logging
        if ($this->debug) {
            $this->debug("==================================================");
            $this->debug(__METHOD__ . " OK - source[" . $source_id . "], catalog[" . $catalogue_id . "], filename[" . $filename . "]");
        }
    }

    function debug($text, $clear = false) {
        // log message
        return file_put_contents($GLOBALS['TMP_FOLDER'] . "/1c8debug.log", date("Y-m-d H:i:s") . ' - ' . $text . PHP_EOL, (!$clear ? FILE_APPEND : NULL));
    }

    function get_templates() {
        // data templates (classes)
        $sub_ids = array();
        // get data
        $sub_structure = $this->cache_data_out("sub_structure");
        // if template is unknown, get it
        if ($sub_structure) {
            foreach ($sub_structure as $value) {
                if ($value['Subdivision_ID'])
                    $sub_ids[] = $value['Subdivision_ID'];
            }
        } else {
            $sub_ids = $this->db->get_col("SELECT `value`
        FROM `Netshop_ImportMap`
        WHERE `source_id` = '" . $this->source_id . "'
        AND `type` = 'section'");
        }

        if (!empty($sub_ids)) {
            $res = $this->db->get_results("SELECT `Subdivision_ID`, `Class_ID`, `Sub_Class_ID`
        FROM `Sub_Class`
        WHERE `Subdivision_ID` IN (" . join(",", $sub_ids) . ")
        ORDER BY `Priority` DESC", ARRAY_A);
            foreach ($res AS $value) {
                $templates[$value['Subdivision_ID']]['class_id'] = $value['Class_ID'];
                $templates[$value['Subdivision_ID']]['subclass_id'] = $value['Sub_Class_ID'];
            }
        }

        // cache file
        $this->cache_data_in($templates, "templates");

        // logging
        if ($this->debug) $this->debug(__METHOD__ . " OK");

        // return result
        return $templates;
    }

    /**
     * Update source in base
     * @return true if all good
     */
    function update_sources() {
        // get data
        $catalogue_data_properties = $this->cache_data_out("catalogue_data_properties");

        // return if no data found
        if (empty($catalogue_data_properties)) return false;

        // get properties
        $this_id = $catalogue_data_properties[NETCAT_MODULE_NETSHOP_1C_ID];
        $this_classificator_id = $catalogue_data_properties[NETCAT_MODULE_NETSHOP_1C_CLASSIFICATOR_ID];
        $this_name = trim($catalogue_data_properties[NETCAT_MODULE_NETSHOP_1C_NAME]);

        // actual `external_id`
        $actual_external_id = $this_classificator_id . " " . $this_id;

        // get `external_id`
        $external_id = $this->db->get_var("SELECT `external_id`
			FROM `Netshop_ImportSources`
			WHERE `source_id` = '" . $this->source_id . "'");

        // another catalog import attempt
        if ($external_id && $external_id != $actual_external_id) {
            // logging
            if ($this->debug)
                $this->debug(__METHOD__ . " FAIL - another catalog import attempt");
            // return
            return false;
        }

        // update sources
        $this->db->query("UPDATE `Netshop_ImportSources`
      SET `external_id` = '" . $actual_external_id . "'
      WHERE `source_id` = '" . $this->source_id . "'");

        // logging
        if ($this->debug) $this->debug(__METHOD__ . " OK");

        // true or false
        return true;
    }

    /**
     * Put cache file in temp directory /netcat/tmp/
     * @param array to serialise
     * @param string suffix for file name
     * @return bytes in cached file or false
     */
    function cache_data_in(&$arr, $suffix) {
        // check
        if (empty($arr)) return false;

        $_path = $this->filename_path . "." . $suffix . ".cache";
        // make serialise
        $cached = serialize($arr);

        // write cache
        $bytes_writed = file_put_contents($_path, $cached);

        // logging
        if ($this->debug)
            $this->debug(__METHOD__ . "('" . $suffix . "') OK - " . $bytes_writed . " bytes writed");

        // return bytes in file or false
        return $bytes_writed;
    }

    /**
     * Get array from cache file in temp directory /netcat/tmp/
     * @param string suffix for file name
     * @return unserialise array or false
     */
    function cache_data_out($suffix) {
        $_path = $this->filename_path . "." . $suffix . ".cache";
        // check file existence
        if (!file_exists($_path)) return false;
        // get contents from file
        $data = file_get_contents($_path);
        // get array from serialise data
        $arr = unserialize($data);
        if (!$this->nc_core->NC_UNICODE)
            $arr = $this->nc_core->utf8->array_utf2win($arr);
        //echo $suffix;
        //dump($arr);
        // logging
        if ($this->debug) $this->debug(__METHOD__ . "('" . $suffix . "') OK");

        // return return array or false
        return !empty($arr) ? $arr : false;
    }

    /**
     * Delete cached file(s) in temp directory /netcat/tmp/
     * @param mixed file(s) to delete
     * @return true or false
     */
    function cache_data_destroy($suffix = "") {
        // deleted files
        $result = 0;

        // open folder
        if ($handle = opendir($this->filedir)) {
            // walk
            while (($file = readdir($handle)) !== false) {
                // determine file
                if (preg_match("/^" . $this->filename . "\." . $suffix . "/is", $file)) {
                    // file path
                    $_path = $this->filedir . $file;
                    // delete
                    if (file_exists($_path) && unlink($_path)) $result++;
                }
                // oldest file
                if (
                    preg_match("/^importcml/is", $file) &&
                    !strstr($file, $this->filename)
                ) {
                    // file path
                    $_path = $this->filedir . $file;
                    // delete
                    if (file_exists($_path)) unlink($_path);
                }
            }
            // close folder
            closedir($handle);
        }

        // logging
        if ($this->debug)
            $this->debug(__METHOD__ . "('" . $suffix . "') OK - " . $result . " files deleted");

        // return result
        return $result;
    }

    /**
     * Check cached file(s) in temp directory /netcat/tmp/
     * @return true if any file cached or false
     */
    function cache_data_exist($suffix = "") {
        // deleted files
        $result = 0;

        // open folder
        if ($handle = opendir($this->filedir)) {
            // walk
            while (($file = readdir($handle)) !== false) {
                // determine file
                if (preg_match("/^" . $this->filename . "\." . $suffix . "/is", $file))
                    $result++;
            }
            // close folder
            closedir($handle);
        }

        // logging
        if ($this->debug)
            $this->debug(__METHOD__ . "('" . $suffix . "') OK - " . $result . " files exist");

        // return result
        return $result;
    }

    /**
     * Count cached file(s) into the temp directory /netcat/tmp/
     *
     * @param string file suffix (for the multipart cache)
     *
     * @return integer counted value
     */
    function cache_data_count($suffix = "") {
        // counted value
        $result = 0;

        // open folder
        if ($handle = opendir($this->filedir)) {
            // walk
            while (($file = readdir($handle)) !== false) {
                // determine file
                if (preg_match("/^" . $this->filename . "\." . $suffix . "/is", $file))
                    $result++;
            }
            // close folder
            closedir($handle);
        }

        // logging
        if ($this->debug)
            $this->debug(__METHOD__ . "('" . $suffix . "') OK - " . $result . " files counted");

        // return counted value
        return $result;
    }

    /**
     * Get classifier data function
     */
    function get_classifier_data() {
        // file existence
        if (!file_exists($this->filename_path)) {
            // logging
            if ($this->debug)
                $this->debug(__METHOD__ . " FAIL - file " . $this->filename_path . " does't exist");
            // return
            return false;
        }

        $classifier_data = $this->cml2->get_groups($this->filename_path);

        // cache file
        $bytes_writed = $this->cache_data_in($classifier_data, "classifier_data");

        // logging
        if ($this->debug)
            $this->debug(__METHOD__ . "() OK - " . $bytes_writed . " bytes cached");

        //cache classifier properties
        $classifier_properties_data = $this->cml2->get_classifier_properties($this->filename_path);

        // cache file
        $bytes_writed = $this->cache_data_in($classifier_properties_data, "classifier_properties_data");

        // logging
        if ($this->debug)
            $this->debug(__METHOD__ . "() OK - " . $bytes_writed . " bytes cached");
    }

    function find_nearest_parent_subdivision_id($sub_structure, $current_parent_group_id) {
        $subdivision_id = null;
        if (isset($sub_structure[$current_parent_group_id])) {
            $group = $sub_structure[$current_parent_group_id];
            $subdivision_id = $group['Subdivision_ID'];
            if ($subdivision_id == -1) {
                $subdivision_id = $this->find_nearest_parent_subdivision_id($sub_structure, $group['Ext_Parent_Group_ID']);
            }
        }

        return $subdivision_id;
    }

    function import_classifier_data() {
        // get data
        $classifier_data = $this->cache_data_out("classifier_data");
        $sub_structure = $this->cache_data_out("sub_structure");

        // return if no data found
        if (empty($classifier_data)) {
            // logging
            if ($this->debug) $this->debug(__METHOD__ . "() FAIL - no data");
            // return
            return false;
        }

        $sql = "DELETE FROM `Netshop_ImportMap` " .
            "WHERE `source_id` = '{$this->source_id}' " .
            "AND `type` = 'section' " .
            "AND `value` NOT IN (SELECT `Subdivision_ID` FROM `Subdivision`)";
        $this->db->query($sql);

        $parent_sub_id = $this->shop_subdivision_id;

        // after map_sections_dialog(), this arrays is accessible
        if (isset($_POST['map_groups'])) {
            if (!$this->nc_core->NC_UNICODE)
                $_POST['map_groups'] = $this->nc_core->utf8->array_win2utf($_POST['map_groups']);
            $map_groups = $_POST['map_groups'];
            // cache file
            $this->cache_data_in($map_groups, "map_groups");
        } else {
            // get data
            $map_groups = $this->cache_data_out("map_groups");
        }
        if (isset($_POST['new_group'])) {
            $this->new_group = $_POST['new_group'];
            // cache file
            $this->cache_data_in($this->new_group, "new_group");
        } else {
            // get data
            $this->new_group = $this->cache_data_out("new_group");
        }

        $current_num = 1;
        $total_objects = count($classifier_data);

        $sql = "SELECT `auto_add_sections`, `auto_move_sections` FROM `Netshop_ImportSources` WHERE `source_id` = {$this->source_id}";
        $row = $this->db->get_row($sql, ARRAY_A);
        $auto_add_sections = (int)$row['auto_add_sections'];
        $auto_move_sections = (int)$row['auto_move_sections'];
        $autoimport_mode = isset($_COOKIE['nc-import-cookie']);

        foreach ($classifier_data as $key => $value) {

            if (!$this->quite && $current_num == 1 && $map_groups) {
                echo "<b>" . NETCAT_MODULE_NETSHOP_IMPORT_CATALOGUE_STRUCTURE . "</b><br/>\r\n";
                $this->progress_bar_show("structure_progress");
                echo "<br/>\r\n";
            }

            $parent_gid = $value['parent_gid'];
            $group_id = $value[NETCAT_MODULE_NETSHOP_1C_ID];
            $name = $value[NETCAT_MODULE_NETSHOP_1C_NAME];
            $name = trim($name);

            $group_id_escaped = $this->db->escape($group_id);

            if ($map_groups[$group_id])
                $template_id = (int)$this->new_group[$group_id]["template"];

            $sql = "SELECT * FROM `Netshop_ImportMap` WHERE `source_id` = {$this->source_id} AND `type` = 'section' AND `source_string` = '{$group_id_escaped}'";
            $group_already_saved = $this->db->get_row($sql) ? true : false;

            switch (true) {
                // new group posted
                case ($map_groups[$group_id] == "new" || (!$group_already_saved && $autoimport_mode && $auto_add_sections)):
                    // если у раздела есть родители в дзен-массиве то ставим соответствие с Subdivision_ID из массива $parent
                    $parent_sub_id = isset($parent_gid) && $sub_structure[$parent_gid] ? $sub_structure[$parent_gid]["Subdivision_ID"] : $this->shop_subdivision_id;

                    if ($parent_sub_id == -1) {
                        $parent_sub_id = $this->find_nearest_parent_subdivision_id($sub_structure, $sub_structure[$group_id]['Ext_Parent_Group_ID']);

                        if (!$parent_sub_id) {
                            $parent_sub_id = $this->shop_subdivision_id;
                        }
                    }

                    // заменяем любой символ, не образующий "слово", и ставим заглавные буквы в начале каждого слова
                    $english_name = nc_preg_replace("/\W+/", "", ucwords(nc_transliterate($name)));

                    if (!$parent_data[$parent_sub_id]) {
                        $parent_data[$parent_sub_id] = $this->db->get_row("SELECT * FROM `Subdivision` WHERE `Subdivision_ID` = '" . $parent_sub_id . "'", ARRAY_A);
                    }

                    $priority = (int)$this->db->get_var("SELECT MAX(`Priority`) + 1 FROM `Subdivision` WHERE `Parent_Sub_ID` = '" . $parent_sub_id . "'");

                    // если EnglishName повторяется, добавляем дополнительный индекс
                    $english_name_suffix = "";
                    while ($this->db->get_var("SELECT COUNT(*) FROM `Subdivision` WHERE `Parent_Sub_ID` = '" . $parent_sub_id . "' AND `EnglishName` = '" . $english_name . $english_name_suffix . "'")) {
                        $english_name_suffix += 1;
                    }
                    $english_name .= (string)$english_name_suffix;

                    $hidden_url = $parent_data[$parent_sub_id]['Hidden_URL'] . $english_name . "/";

                    if (!$group_already_saved && $autoimport_mode && $auto_add_sections) {
                        $template_id = $auto_add_sections;
                    }

                    // добавляем запись в базу
                    $this->db->query("INSERT INTO `Subdivision`
            SET `Catalogue_ID` = '" . $this->catalogue_id . "',
            `Parent_Sub_ID` = '" . $parent_sub_id . "',
            `Subdivision_Name` = '" . $this->db->escape($name) . "',
            `Template_ID` = 0,
            `EnglishName` = '" . $english_name . "',
            `LastUpdated` = NOW(),
            `Created` = NOW(),
            `Hidden_URL` = '" . $hidden_url . "',
            `Priority` = '" . $priority . "',
            `Checked` = 1");
                    //$this->db->debug();
                    // Subdivision_ID
                    $sub_id = $this->db->insert_id;

                    $this->db->query("INSERT INTO Sub_Class
            SET `Subdivision_ID` = '" . $sub_id . "',
            `Class_ID` = '" . $template_id . "',
            `Sub_Class_Name` = '" . $this->db->escape($name) . "',
            `EnglishName` = '" . $english_name . "',
            `Priority` = 0,
            `Checked` = 1,
            `Catalogue_ID` = '" . $this->catalogue_id . "',
            `DefaultAction` = 'index',
            `Created` = NOW(),
            `LastUpdated` = NOW()");
                    //$this->db->debug();

                    $sub_class_id = $this->db->insert_id;

                    // import map
                    $this->db->query("REPLACE INTO `Netshop_ImportMap`
            SET `source_id` = '" . $this->source_id . "',
            `type` = 'section',
            `source_string` = '" . $this->db->escape($group_id) . "',
            `value` = '" . $sub_id . "'");
                    //$this->db->debug();
                    break;

                case (int($map_groups[$group_id]) || $map_groups[$group_id] == -1):
                    $sub_id = (int)$map_groups[$group_id];
                    // Sub_Class_ID value for $this->sub_structure array
                    $sub_class_id = $this->db->get_var("SELECT `Sub_Class_ID` FROM `Sub_Class` WHERE `Class_ID` = '" . $template_id . "' AND `Subdivision_ID` = '" . $sub_id . "'");
                    // указано соответствие
                    $this->db->query("REPLACE INTO `Netshop_ImportMap`
            SET `source_id` = '" . $this->source_id . "',
            `type` = 'section',
            `source_string` = '" . $this->db->escape($group_id) . "',
            `value` = '" . $sub_id . "'");
                    break;

                default:
                    // Найти соответствие разделу (по внешнему идентификатору)
                    $sub_id = $this->db->get_var("SELECT m.`value`
            FROM `Netshop_ImportMap` AS m
            WHERE m.`type` = 'section'
            AND m.`source_string` = '" . $this->db->escape($group_id) . "'
            AND m.`source_id` = '" . $this->source_id . "'
            ORDER BY m.`source_id` = '" . $this->source_id . "' DESC");
                    if ($sub_id != -1) {
                        $sub_id = $this->db->get_var("SELECT m.`value`
            FROM `Netshop_ImportMap` AS m, Subdivision AS s
            WHERE m.`type` = 'section'
            AND m.`source_string` = '" . $this->db->escape($group_id) . "'
            AND m.`source_id` = '" . $this->source_id . "'
            AND m.`value` = s.`Subdivision_ID`
            ORDER BY m.`source_id` = '" . $this->source_id . "' DESC");

                        if (!$sub_id) {
                            $this->not_mapped_sections++;
                        } else {
                            $sql = "SELECT `auto_rename_subdivisions`, `auto_change_subdivision_links` FROM `Netshop_ImportSources` " .
                                "WHERE `source_id` = {$this->source_id}";

                            $row = $this->db->get_row($sql, ARRAY_A);
                            $auto_rename_subdivisions = $row['auto_rename_subdivisions'];
                            $auto_change_subdivision_links = $row['auto_change_subdivision_links'];

                            if ($auto_rename_subdivisions) {
                                $sql = "SELECT `value` FROM `Netshop_ImportMap` " .
                                    "WHERE `type` = 'section' " .
                                    "AND `source_string` = '" . $this->db->escape($group_id) . "' " .
                                    "AND `source_id` = '" . $this->source_id . "'";
                                $subdivision_id = (int)$this->db->get_var($sql);
                                $subdivision_name = $this->nc_core->subdivision->get_by_id($subdivision_id, 'Subdivision_Name');
                                $parent_id = $this->nc_core->subdivision->get_by_id($subdivision_id, 'Parent_Sub_ID');

                                if ($subdivision_name != $name) {
                                    $name_escaped = $this->db->escape($name);

                                    // заменяем любой символ, не образующий "слово", и ставим заглавные буквы в начале каждого слова
                                    $english_name = nc_preg_replace("/\W+/", "", ucwords(nc_transliterate($name)));

                                    $priority = (int)$this->db->get_var("SELECT MAX(`Priority`) + 1 FROM `Subdivision` WHERE `Parent_Sub_ID` = '" . $parent_id . "'");

                                    // если EnglishName повторяется, добавляем дополнительный индекс
                                    $english_name_suffix = "";
                                    while ($this->db->get_var("SELECT COUNT(*) FROM `Subdivision` WHERE `Parent_Sub_ID` = '" . $parent_id . "' AND `EnglishName` = '" . $english_name . $english_name_suffix . "'")) {
                                        $english_name_suffix += 1;
                                    }
                                    $english_name .= (string)$english_name_suffix;
                                    $english_name = $this->db->escape($english_name);

                                    $sql = "UPDATE `Subdivision` " .
                                        "SET `Subdivision_Name` = '{$name_escaped}', " .
                                        "`EnglishName` = '{$english_name}'";

                                    if ($auto_change_subdivision_links) {
                                        $old_url = $this->nc_core->subdivision->get_by_id($subdivision_id, 'Hidden_URL');
                                        $hidden_url = $this->nc_core->subdivision->get_by_id($parent_id, 'Hidden_URL');
                                        $hidden_url .= $english_name . "/";
                                        $new_url = $hidden_url;
                                        $hidden_url = $this->db->escape($hidden_url);

                                        $old_url = $this->db->escape($old_url);

                                        $sql .= ", `Hidden_URL` = '{$hidden_url}'";

                                        $catalogue_sql = "SELECT `catalogue_id` FROM `Netshop_ImportSources` WHERE `source_id` = {$this->source_id}";
                                        $catalogue_id = $this->db->get_var($catalogue_sql);

                                        $domain = $this->nc_core->catalogue->get_by_id($catalogue_id, 'Domain');

                                        $old_url = $this->db->escape($domain . $old_url);
                                        $new_url = $this->db->escape($domain . $new_url);

                                        $redirect_sql = "INSERT INTO `Redirect` (`OldURL`, `NewURL`, `Header`) VALUES " .
                                            "('{$old_url}', '{$new_url}', '301')";
                                        $this->db->query($redirect_sql);
                                    }

                                    $sql .= " WHERE `Subdivision_ID` = {$subdivision_id}";

                                    $this->db->query($sql);
                                }
                            }
                        }
                    }

            }

            if ($group_already_saved && $parent_gid && $auto_move_sections) {
                $parent_gid_escaped = $this->db->escape($parent_gid);
                $sql = "SELECT `value` FROM `Netshop_ImportMap` WHERE `source_id` = {$this->source_id} AND `type` = 'section' AND `source_string` = '{$parent_gid_escaped}'";
                $parent_group_sub_id = $this->db->get_var($sql);

                if ($parent_group_sub_id) {
                    $sql = "SELECT `Parent_Sub_ID`, `EnglishName` FROM `Subdivision` WHERE `Subdivision_ID` = {$sub_id}";
                    $subdivision = $this->db->get_row($sql, ARRAY_A);
                    $current_parent_sub_id = $subdivision['Parent_Sub_ID'];

                    if ($subdivision && $parent_group_sub_id != $current_parent_sub_id) {
                        //create new hidden url
                        $sql = "SELECT `Hidden_URL` FROM `Subdivision` WHERE `Subdivision_ID` = {$current_parent_sub_id}";
                        $hidden_url = $this->db->get_var($sql);
                        $english_name = $subdivision['EnglishName'];

                        $hidden_url_escaped = $this->db->escape($hidden_url . $english_name . '/');

                        $sql = "SELECT `Subdivision_ID` FROM `Subdivision` WHERE `Hidden_URL` = '{$hidden_url_escaped}' OR `ExternalURL` = '{$hidden_url_escaped}' AND `Subdivision_ID` != {$sub_id}";

                        $suffix = "";
                        while ($this->db->get_row($sql)) {
                            $suffix++;
                            $hidden_url_escaped = $this->db->escape($hidden_url . $english_name . '_' . $suffix . '/');
                            $sql = "SELECT `Subdivision_ID` FROM `Subdivision` WHERE `Hidden_URL` = '{$hidden_url_escaped}' OR `ExternalURL` = '{$hidden_url_escaped}'";
                        }

                        $sql = "UPDATE `Subdivision` SET `Parent_Sub_ID` = {$parent_group_sub_id}, `Hidden_URL` = '{$hidden_url_escaped}' WHERE `Subdivision_ID` = {$sub_id}";
                        $this->db->query($sql);
                        $this->db->debug();
                        $parent_sub_id = $parent_group_sub_id;
                    }
                }

            }

            $sub_structure[$group_id] = array(
                "Ext_Group_ID" => $group_id,
                "Ext_Level" => $value['level'],
                "Ext_Parent_Group_ID" => $value['parent_gid'],
                // imperative
                "Subdivision_ID" => $sub_id,
                "Sub_Class_ID" => $sub_class_id,
                // optional for default and selected compliance Subdivision
                "Parent_Sub_ID" => $parent_sub_id,
                "Name" => $name
            );
            // procents completed
            $percent = intval($current_num / $total_objects * 100);
            $this->progress_bar_update("structure_progress", $percent);
            // increment
            $current_num++;
        }
        if (!$this->nc_core->NC_UNICODE)
            $sub_structure = $this->nc_core->utf8->array_win2utf($sub_structure);
        // cache file
        if (!empty($sub_structure)) {
            $bytes_writed = $this->cache_data_in($sub_structure, "sub_structure");
            // logging
            if ($this->debug)
                $this->debug(__METHOD__ . "() OK - 'sub_structure' data " . $bytes_writed . " bytes cached");
            // return
            return true;
        } else {
            // logging
            if ($this->debug)
                $this->debug(__METHOD__ . "() FAIL - no 'sub_structure' data");
            // return
            return false;
        }
    }

    function map_sections_dialog() {

        echo "<input type='hidden' name='collect_post' value='1'>";
        echo "<style type='text/css'>";
        echo ".divadd {border:1px solid #DDD; background-color:#F0F0F0; padding:3px;}";
        echo "select {width:auto}";
        echo "</style>";
        ?>
        <script type='text/javascript'>
            $nc(function () {
                $nc('SELECT[name^="map_groups["]').on('change', function () {
                    var $this = $nc(this);

                    var toggleComponentSelect = function ($groupSelect, selectedGroup) {
                        var $componentSelect = $groupSelect.closest('TD').next('TD').find('SELECT');

                        if ($componentSelect.find('OPTION').length > 1) {
                            if (selectedGroup == 'new') {
                                $componentSelect.closest('DIV').show();
                            } else {
                                $componentSelect.closest('DIV').hide();
                            }
                        }

                        return true;
                    };

                    var disableChilds = function (parentGroupId, selectedGroup) {
                        $nc('SELECT[data-nc-ext-parent-group-id="' + parentGroupId + '"]').each(function () {
                            var $element = $nc(this);
                            $element.val(-1);

                            toggleComponentSelect($element, selectedGroup);

                            var groupId = /^map_groups\[(.+)\]$/i.exec($element.attr('name'));
                            if (groupId) {
                                groupId = groupId[1];

                                disableChilds(groupId, selectedGroup);
                            }
                        });
                        return true;
                    };

                    var value = $this.val();

                    if (value == -1) {
                        var groupId = /^map_groups\[(.+)\]$/i.exec($this.attr('name'));
                        if (groupId) {
                            groupId = groupId[1];

                            disableChilds(groupId, value);
                        }
                    }

                    toggleComponentSelect($this, value);

                    return true;
                });

                $nc('SELECT[name^="new_group["]').on('change', function () {
                    var $this = $nc(this);

                    var changeChilds = function (parentGroupId, newValue) {
                        $nc('SELECT[data-nc-ext-parent-group-id="' + parentGroupId + '"]').each(function () {
                            var $element = $nc(this);

                            $element.closest('TD').next('TD').find('SELECT').val(newValue);

                            var groupId = /^map_groups\[(.+)\]$/i.exec($element.attr('name'));
                            if (groupId) {
                                groupId = groupId[1];

                                changeChilds(groupId, newValue);
                            }
                        });
                        return true;
                    };

                    var value = $this.val();

                    var groupId = /^map_groups\[(.+)\]$/i.exec($this.closest('TD').prev('TD').find('SELECT').attr('name'));

                    if (groupId) {
                        groupId = groupId[1];

                        changeChilds(groupId, value);
                    }

                    return true;
                });
            });
        </script>
        <?php
        $this->everything_clear = false;

        $sections = GetStructure($this->shop_subdivision_id, "Checked = 1");

        $sections_as_options = "";
        foreach ($sections AS $row) {
            $sections_as_options .= "<option value='" . $row['Subdivision_ID'] . "'>" . str_repeat("&nbsp;", ($row["level"] + 1) * 4) . $row['Subdivision_Name'] . "</option>\r\n";
        }

        // Ask about groups we don't know
        echo "<b>" . NETCAT_MODULE_NETSHOP_IMPORT_MAP_SECTION . ":</b>\r\n";
        echo "<table border='0' cellspacing='8' cellpadding='0'>\r\n";

        $templates_count = count($this->shop_classes);
        $this->goods_template_ids = array();

        $templates_as_options = "";
        // netshop goods classes
        foreach ($this->shop_classes AS $value) {
            $templates_as_options .= "<option value=" . $value['id'] . (!$templates_as_options ? " selected" : "") . ">" . $value['name'] . "</option>\r\n";
            $this->goods_template_ids[] = $value['id'];
        }

        // get data
        $sub_structure = $this->cache_data_out("sub_structure");
        // group -> sub list
        if (is_array($sub_structure) && !empty($sub_structure)) {
            foreach ($sub_structure as $gid => $group) {
                if (!$group['Subdivision_ID']) {
                    $parent = $group['Parent_Sub_ID'];
                    if (!$parent) $parent = "[root]";
                    echo "<tr valign=top>";
                    echo "<td title='" . $gid . " &larr; " . $parent . "'>";
                    for ($i = 0; $i < $group['Ext_Level']; $i++) {
                        echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                    }
                    echo $group['Name'] . "</td>";
                    echo "<td>&rarr;</td>";
                    echo "<td>";
                    echo "<select name='map_groups[" . $gid . "]'" . ($templates_count > 1 ? " onchange='switch_divadd(\"" . $gid . "\")'" : "") . " id='map_groups" . $gid . "' data-nc-ext-parent-group-id='{$group['Ext_Parent_Group_ID']}'>";
                    echo "<option value='new' style='color:navy'>" . NETCAT_MODULE_NETSHOP_IMPORT_CREATE_SECTION . ($templates_count > 1 ? " &nbsp; &darr; &nbsp;" : "") . "</option>";
                    echo "<option value='-1'>" . NETCAT_MODULE_NETSHOP_IMPORT_IGNORE_SECTION . "</option>";
                    echo "<option value='-1'>----------------------------------------</option>";
                    echo $sections_as_options;
                    echo "</select>";
                    echo "</td>";
                    echo "<td>";
                    echo "<div class='divadd' id='divadd" . $gid . "'" . ($templates_count == 1 ? " style='display:none'" : "") . ">";
                    echo NETCAT_MODULE_NETSHOP_IMPORT_TEMPLATE . ":&nbsp;<select name='new_group[" . htmlspecialchars($gid) . "][template]'>\r\n";
                    echo $templates_as_options;
                    echo "</select>";
                    echo "</div>";
                    echo "</td>";
                    echo "</tr>";
                }
            }
        }

        echo "</table><br/>";

        // logging
        if ($this->debug) $this->debug(__METHOD__ . "() OK");
    }

    function get_offers_data_callback($level = 0, $data) {
        // callbacks iterator
        static $i = 0;

        $offers_data_offers = array();
        $offers_data_offers_fields = $this->cache_data_out("offers_data_offers_fields");

        $offers_data_stores = $this->cache_data_out("offers_data_stores");
        if (!is_array($offers_data_stores)) {
            $offers_data_stores = array();
        }

        $offers_data_characteristics = $this->cache_data_out("offers_data_characteristics");
        if (!is_array($offers_data_characteristics)) {
            $offers_data_characteristics = array();
        }

        if (!$this->nc_core->NC_UNICODE)
            $data = $this->nc_core->utf8->array_utf2win($data);
        // walking...
        foreach ($data as $key => $value) {

            $index = $key + $level;

            foreach ($value as $name => $row) {
                /**
                 * $child['n'] - name
                 * $child['d'] - data
                 * $child['c'] - data array
                 */
                // put data in array
                if (!in_array($name, $this->import_ignore_tags) && (!is_array($row))) {
                    $offers_data_offers[$index][$name] = $row;
                    $offers_data_offers_fields[$name]++;
                    continue;
                }
                // prices(s)_id
                if ($name == NETCAT_MODULE_NETSHOP_1C_PRICES) {
                    if (isset($row[NETCAT_MODULE_NETSHOP_1C_PRICE])) {
                        $offers_data_offers[$index][$name] = $row;
                    } else {
                        $offers_data_offers[$index][$name][NETCAT_MODULE_NETSHOP_1C_PRICE][] = $row;
                    }

                    $offers_data_offers_fields[$name]++;
                    continue;
                }

                //stores
                if ($name == NETCAT_MODULE_NETSHOP_1C_STORE_REMAIN) {
                    $stores_remains = $row[NETCAT_MODULE_NETSHOP_1C_REMAIN];
                    if (!isset($stores_remains[0])) {
                        $stores_remains = array($stores_remains);
                    }

                    $offers_data_offers[$index][$name][NETCAT_MODULE_NETSHOP_1C_REMAIN] = $stores_remains;

                    $offers_data_offers_fields[$name]++;

                    foreach ($stores_remains as $store) {
                        if (isset($store[NETCAT_MODULE_NETSHOP_1C_STORE_ID])) {
                            $offers_data_stores[$store[NETCAT_MODULE_NETSHOP_1C_STORE_ID]] = $store[NETCAT_MODULE_NETSHOP_1C_STORE];
                        }
                    }

                    continue;
                }

                //characteristics
                if ($name == NETCAT_MODULE_NETSHOP_1C_PRODUCT_CHARS) {
                    $characteristics_raw = $row[NETCAT_MODULE_NETSHOP_1C_PRODUCT_CHAR];
                    if (!isset($characteristics_raw[0])) {
                        $characteristics_raw = array($characteristics_raw);
                    }

                    $characteristics = array();

                    foreach ($characteristics_raw as $characteristic) {
                        $characteristics[$characteristic[NETCAT_MODULE_NETSHOP_1C_NAME]] = $characteristic[NETCAT_MODULE_NETSHOP_1C_VALUE];
                        if (!in_array($characteristic[NETCAT_MODULE_NETSHOP_1C_NAME], $offers_data_characteristics)) {
                            $offers_data_characteristics[] = $characteristic[NETCAT_MODULE_NETSHOP_1C_NAME];
                        }
                    }

                    $offers_data_offers[$index][$name] = $characteristics;
                    $offers_data_offers_fields[$name]++;
                    continue;
                }
            }
        }

        if (!$this->nc_core->NC_UNICODE) {
            $offers_data_offers = $this->nc_core->utf8->array_win2utf($offers_data_offers);
            $offers_data_offers_fields = $this->nc_core->utf8->array_win2utf($offers_data_offers_fields);
            $offers_data_stores = $this->nc_core->utf8->array_win2utf($offers_data_stores);
            $offers_data_characteristics = $this->nc_core->utf8->array_win2utf($offers_data_characteristics);
        }
        // store data
        $bytes_writed = $this->cache_data_in($offers_data_offers, "offers_data_offers" . $i);
        // logging
        if ($this->debug)
            $this->debug(__METHOD__ . "('" . $level . "') OK - 'offers_data_offers" . $i . "' data " . $bytes_writed . " bytes cached");

        $bytes_writed = $this->cache_data_in($offers_data_offers_fields, "offers_data_offers_fields");
        // logging
        if ($this->debug)
            $this->debug(__METHOD__ . "('" . $level . "') OK - 'offers_data_stores' data " . $bytes_writed . " bytes cached");
        $bytes_writed = $this->cache_data_in($offers_data_stores, "offers_data_stores");
        // logging
        if ($this->debug)
            $this->debug(__METHOD__ . "('" . $level . "') OK - 'offers_data_characteristics' data " . $bytes_writed . " bytes cached");
        $bytes_writed = $this->cache_data_in($offers_data_characteristics, "offers_data_characteristics");
        // logging
        if ($this->debug)
            $this->debug(__METHOD__ . "('" . $level . "') OK - 'offers_data_offers_fields' data " . $bytes_writed . " bytes cached");

        // callbacks iterator
        $i++;

        // continue callback
        return;
    }

    function get_orders_data_callback($data) {
        // callbacks iterator
        static $i = 0;

        $orders_data_orders = array();

        if (!$this->nc_core->NC_UNICODE)
            $data = $this->nc_core->utf8->array_utf2win($data);

        foreach ($data as $order) {
            $orders_data_orders[] = $order;
        }

        if (!$this->nc_core->NC_UNICODE) {
            $orders_data_orders = $this->nc_core->utf8->array_win2utf($orders_data_orders);
        }

        // store data
        $bytes_writed = $this->cache_data_in($orders_data_orders, "orders_data_orders" . $i);
        // logging
        if ($this->debug)
            $this->debug(__METHOD__ . "OK - 'orders_data_orders" . $i . "' data " . $bytes_writed . " bytes cached");

        // callbacks iterator
        $i++;

        // continue callback
        return;
    }

    /**
     * This function get catalogue data
     */
    function get_offers_data() {
        // file existence
        if (!file_exists($this->filename_path)) {
            // logging
            if ($this->debug)
                $this->debug(__METHOD__ . "() FAIL - file " . $this->filename_path . " does't exist");
            // return
            return false;
        }

        // get offers (returned values)
        $offers_data_properties = $this->cml2->get_offers($this->filename_path, $this, 'get_offers_data_callback');

        if (!$this->nc_core->NC_UNICODE) {
            $offers_data_properties = $this->nc_core->utf8->array_utf2win($offers_data_properties);
        }

        $offers_data_price_type_arr = $offers_data_properties[NETCAT_MODULE_NETSHOP_1C_PRICES_TYPE];
        $offers_data_price_type = $offers_data_price_type_arr[NETCAT_MODULE_NETSHOP_1C_PRICE_TYPE];

        if (!$this->nc_core->NC_UNICODE) {
            $offers_data_properties = $this->nc_core->utf8->array_win2utf($offers_data_properties);
            $offers_data_price_type = $this->nc_core->utf8->array_win2utf($offers_data_price_type);
        }
        // store price types
        $bytes_writed = $this->cache_data_in($offers_data_price_type, "offers_data_price_type");
        // logging
        if ($this->debug)
            $this->debug(__METHOD__ . "('" . $level . "') OK - 'offers_data_price_type' data " . $bytes_writed . " bytes cached");

        // store offers properties
        $bytes_writed = $this->cache_data_in($offers_data_properties, "offers_data_properties");
        // logging
        if ($this->debug)
            $this->debug(__METHOD__ . "('" . $level . "') OK - 'offers_data_properties' data " . $bytes_writed . " bytes cached");
    }

    function import_offers_data() {
        // clear?
        //if (!$this->everything_clear) return false;
        // get data
        $offers_data_price_type = $this->cache_data_out("offers_data_price_type");
        // return if no data found
        if (empty($offers_data_price_type)) {
            // logging
            if ($this->debug)
                $this->debug(__METHOD__ . "() FAIL - no data 'offers_data_price_type'");
            // return
            return false;
        }

        if (!$this->templates) $this->templates = $this->get_templates();

        // import price type
        $packets = array();
        if ($offers_data_price_type[0])
            foreach ($offers_data_price_type as $key => $value) {

                $offers_id = $value[NETCAT_MODULE_NETSHOP_1C_ID];
                $name = $value[NETCAT_MODULE_NETSHOP_1C_NAME];
                $name = trim($name);

                $packets[$offers_id]["name"] = $name;
                //if (!$this->nc_core->NC_UNICODE) $_POST['map_packets'] = $this->nc_core->utf8->array_win2utf($_POST['map_packets']);
                $map_packet = $_POST['map_packets'][urlencode($offers_id)];
                // write compliance (second pass)
                if ($map_packet) {
                    $sql = "SELECT `format` FROM `Netshop_ImportMap` " .
                        "WHERE `source_id` = '" . $this->source_id . "' " .
                        "AND `type` = 'price' " .
                        "AND `source_string` = '" . $this->db->escape($offers_id) . "'";

                    $mapped_format = $this->db->get_var($sql);
                    if ($mapped_format) {
                        $mapped_format = @unserialize($mapped_format);
                    }

                    if (!is_array($mapped_format)) {
                        $mapped_format = array();
                    }

                    $mapped_format['name'] = $name;

                    $mapped_format = $this->db->escape(serialize($mapped_format));

                    $this->db->query("REPLACE INTO `Netshop_ImportMap`
          SET `source_id` = '" . $this->source_id . "',
          `type` = 'price',
          `format` = '{$mapped_format}',
          `source_string` = '" . $this->db->escape($offers_id) . "',
          `value` = '" . $this->db->escape($map_packet) . "'");
                    //$this->db->debug();
                }
            } else {
            $offers_id = $offers_data_price_type[NETCAT_MODULE_NETSHOP_1C_ID];
            $name = trim($offers_data_price_type[NETCAT_MODULE_NETSHOP_1C_NAME]);

            $packets[$offers_id]["name"] = $name;
            $map_packet = $_POST['map_packets'][urlencode($offers_id)];
            if ($map_packet) {
                $sql = "SELECT `format` FROM `Netshop_ImportMap` " .
                    "WHERE `source_id` = '" . $this->source_id . "' " .
                    "AND `type` = 'price' " .
                    "AND `source_string` = '" . $this->db->escape($offers_id) . "'";

                $mapped_format = $this->db->get_var($sql);
                if ($mapped_format) {
                    $mapped_format = @unserialize($mapped_format);
                }

                if (!is_array($mapped_format)) {
                    $mapped_format = array();
                }

                $mapped_format['name'] = $name;

                $mapped_format = $this->db->escape(serialize($mapped_format));

                $this->db->query("REPLACE INTO `Netshop_ImportMap`
          SET `source_id` = '" . $this->source_id . "',
          `type` = 'price',
          `format` = '{$mapped_format}',
          `source_string` = '" . $this->db->escape($offers_id) . "',
          `value` = '" . $this->db->escape($map_packet) . "'");
            }
        }
        // destroy variable
        unset($offers_data_price_type);

        #// destroy old file, information may be updated
        $this->cache_data_destroy("offers_data_packets_arr");
        // count packets from base for "price" type
        if (!empty($packets)) {
            $packets_res = $this->db->get_results("SELECT `source_string` AS id, value
        FROM `Netshop_ImportMap`
        WHERE `source_id` = '" . $this->source_id . "'
        AND `type` = 'price'
        AND `source_string` IN ('" . join("','", array_keys($packets)) . "')", ARRAY_A);
            //$this->db->debug();
            $packets_from_base = $this->db->num_rows;
        }
        // mapped packets
        if (!empty($packets_res)) {
            foreach ($packets_res AS $value) {
                $packets[$value['id']]["column"] = $value['value'];
            }
        }
        // not_mapped_packets value
        $this->not_mapped_packets = count($packets) - $packets_from_base;
        // for map_packets_dialog function
        // cache files
        if (!$this->nc_core->NC_UNICODE)
            $packets = $this->nc_core->utf8->array_win2utf($packets);
        $this->cache_data_in($packets, "offers_data_packets_arr");


        //import stores
        $this->not_mapped_stores = 0;
        $mapped_stores = array();

        $offers_data_stores = $this->cache_data_out("offers_data_stores");
        if (!is_array($offers_data_stores)) {
            $offers_data_stores = array();
        }

        foreach ($this->shop_classes as $class) {
            $mapped_stores[$class['id']] = array();
        }

        foreach ($mapped_stores as $class_id => $dummy) {
            foreach ($offers_data_stores as $store_id => $store) {
                $store_id_escaped = $this->db->escape($store_id);

                if (isset($_POST['map_stores'][$class_id]) && is_array($_POST['map_stores'][$class_id])) {
                    foreach ($_POST['map_stores'][$class_id] as $key => $value) {
                        if ($key == $store_id) {
                            $value = (int)$value;

                            $sql = "SELECT `format` FROM `Netshop_ImportMap` " .
                                "WHERE `source_id` = '" . $this->source_id . "' " .
                                "AND `type` = 'store' " .
                                "AND `source_string` = '{$store_id_escaped}'";

                            $mapped_format = $this->db->get_var($sql);
                            if ($mapped_format) {
                                $mapped_format = @unserialize($mapped_format);
                            }

                            if (!is_array($mapped_format)) {
                                $mapped_format = array();
                            }

                            $mapped_format['name'] = $store;

                            $mapped_format = $this->db->escape(serialize($mapped_format));


                            $sql = "REPLACE INTO `Netshop_ImportMap` " .
                                "SET `source_id` = '{$this->source_id}', " .
                                "`type` = 'store', " .
                                "`format` = '{$mapped_format}', " .
                                "`source_string` = '{$store_id_escaped}', " .
                                "`value` = '{$value}'";
                            $this->db->query($sql);
                            break;
                        }
                    }
                }
            }
        }

        foreach ($mapped_stores as $class_id => $dummy) {
            foreach ($offers_data_stores as $store_id => $store) {
                $store_id_escaped = $this->db->escape($store_id);

                $sql = "SELECT `value` FROM `Netshop_ImportMap` " .
                    "WHERE `type` = 'store' AND `source_string` = '{$store_id_escaped}'";
                $values = $this->db->get_col($sql);

                if ($values && count($values) >= count($mapped_stores)) {
                    $mapped_stores[$class_id][$store_id] = -1;
                    foreach ($values as $value) {
                        $field_id = (int)$value;
                        $sql = "SELECT `Field_ID` FROM `Field` WHERE `Field_ID` = {$field_id} AND `Class_ID` = {$class_id}";
                        if ($this->db->get_var($sql)) {
                            $mapped_stores[$class_id][$store_id] = $field_id;
                            break;
                        }
                    }
                } else {
                    $this->not_mapped_stores++;
                }
            }
        }

        //import characteristics
        $this->not_mapped_characteristics = 0;
        $mapped_characteristics = array();

        $offers_data_characteristics = $this->cache_data_out("offers_data_characteristics");
        if (!is_array($offers_data_stores)) {
            $offers_data_characteristics = array($offers_data_characteristics);
        }

        foreach ($this->shop_classes as $class) {
            $mapped_characteristics[$class['id']] = array();
        }

        if ($offers_data_characteristics) {
            foreach ($mapped_characteristics as $class_id => $dummy) {
                foreach ($offers_data_characteristics as $characteristic) {
                    $characteristic_escaped = $this->db->escape($characteristic);

                    if (isset($_POST['map_characteristics'][$class_id]) && is_array($_POST['map_characteristics'][$class_id])) {
                        foreach ($_POST['map_characteristics'][$class_id] as $key => $value) {
                            if ($key == urlencode($characteristic)) {
                                $value = (int)$value;

                                $sql = "SELECT `format` FROM `Netshop_ImportMap` " .
                                    "WHERE `source_id` = '" . $this->source_id . "' " .
                                    "AND `type` = 'ocharacteristic' " .
                                    "AND `source_string` = '{$characteristic_escaped}'";

                                $mapped_format = $this->db->get_var($sql);
                                if ($mapped_format) {
                                    $mapped_format = @unserialize($mapped_format);
                                }

                                if (!is_array($mapped_format)) {
                                    $mapped_format = array();
                                }

                                $mapped_format['name'] = $characteristic;

                                $mapped_format = $this->db->escape(serialize($mapped_format));


                                $sql = "REPLACE INTO `Netshop_ImportMap` " .
                                    "SET `source_id` = '{$this->source_id}', " .
                                    "`type` = 'ocharacteristic', " .
                                    "`format` = '{$mapped_format}', " .
                                    "`source_string` = '{$characteristic_escaped}', " .
                                    "`value` = '{$value}'";
                                $this->db->query($sql);
                                break;
                            }
                        }
                    }
                }
            }

            foreach ($mapped_characteristics as $class_id => $dummy) {
                foreach ($offers_data_characteristics as $characteristic) {
                    $characteristic_escaped = $this->db->escape($characteristic);

                    $sql = "SELECT `value` FROM `Netshop_ImportMap` " .
                        "WHERE `type` = 'ocharacteristic' AND `source_string` = '{$characteristic_escaped}'";
                    $values = $this->db->get_col($sql);

                    if ($values && count($values) >= count($mapped_characteristics)) {
                        $mapped_characteristics[$class_id][$characteristic] = -1;
                        foreach ($values as $value) {
                            $field_id = (int)$value;
                            $sql = "SELECT `Field_ID` FROM `Field` WHERE `Field_ID` = {$field_id} AND `Class_ID` = {$class_id}";
                            if ($this->db->get_var($sql)) {
                                $mapped_characteristics[$class_id][$characteristic] = $field_id;
                                break;
                            }
                        }
                    } else {
                        $this->not_mapped_characteristics++;
                    }
                }
            }
        }

        // get data from cache file
        $this->offers_data_offers_fields = $this->cache_data_out("offers_data_offers_fields");
        // action)
        if (!empty($this->offers_data_offers_fields)) {
            $need_tags = array(NETCAT_MODULE_NETSHOP_1C_QTY);
            foreach ($this->offers_data_offers_fields AS $xml_tag => $tag_count) {
                $xml_tag = trim($xml_tag);
                if (!in_array($xml_tag, $need_tags)) continue;
                if (!$xml_tag) continue;

                $fields[$xml_tag]['name'] = $xml_tag;
                foreach ($this->shop_classes AS $class) {
                    $map_field = $_POST['map_fields'][$class['id']][urlencode($xml_tag)];
                    // write compliance (second pass)
                    if ($map_field) {
                        $sql = "SELECT `format` FROM `Netshop_ImportMap` " .
                            "WHERE `source_id` = '" . $this->source_id . "' " .
                            "AND `type` = 'oproperty' " .
                            "AND `source_string` = '" . $this->db->escape($xml_tag) . "'";

                        $mapped_format = $this->db->get_var($sql);
                        if ($mapped_format) {
                            $mapped_format = @unserialize($mapped_format);
                        }

                        if (!is_array($mapped_format)) {
                            $mapped_format = array();
                        }

                        $mapped_format['name'] = $xml_tag;

                        $mapped_format = $this->db->escape(serialize($mapped_format));

                        $this->db->query("REPLACE INTO `Netshop_ImportMap`
              SET `source_id` = '" . $this->source_id . "',
              `type` = 'oproperty',
              `format` = '{$mapped_format}',
              `source_string` = '" . $this->db->escape($xml_tag) . "',
              `value` = '" . $this->db->escape($map_field) . "'");
                    }
                }
            }
            // destroy variable
            unset($this->offers_data_offers_fields);
        }

        $class_fields = array();

        if ($this->templates) {
            foreach ($this->templates as $template) {
                if (!isset($class_fields[$template['class_id']])) {
                    $class_fields[$template['class_id']] = $fields;
                }
            }
        }
        $fields_from_base = array();

        if (!empty($class_fields)) {
            foreach ($class_fields as $class_id => $fields_array) {
                $class_id = (int)$class_id;

                foreach ($fields_array as $field_name => $field_array) {
                    $field_name_escaped = $this->db->escape($field_name);

                    $sql = "SELECT `value` FROM `Netshop_ImportMap` WHERE " .
                        "`source_id` = {$this->source_id} " .
                        "AND `type` = 'oproperty' " .
                        "AND `source_string` = '{$field_name_escaped}'";

                    $values = $this->db->get_col($sql);

                    if ($values && count($values) >= count($class_fields)) {
                        if (!in_array($field_name, $fields_from_base)) {
                            $fields_from_base[] = $field_name;
                        }
                        $field_column = -1;

                        foreach ($values as $value) {
                            $value = (int)$value;
                            if ($value != -1) {
                                $sql = "SELECT `Field_ID` FROM `Field` WHERE `Field_ID` = {$value} AND `Class_ID` = {$class_id}";
                                if ($this->db->get_var($sql)) {
                                    $field_column = $value;
                                    break;
                                }
                            }
                        }

                        $class_fields[$class_id][$field_name]['column'] = $field_column;
                    }
                }
            }
        }


        $this->not_mapped_fields = count($fields) - count($fields_from_base);
        // for map_fields_dialog function
        $this->not_mapped_fields_arr = is_array($this->not_mapped_fields_arr) ? array_merge($this->not_mapped_fields_arr, $fields) : $fields;

        $autoimport_mode = isset($_COOKIE['nc-import-cookie']);

        // if not_mapped values - return and call dialog(s)
        if ((!$autoimport_mode && ($this->not_mapped_packets || $this->not_mapped_fields || $this->not_mapped_stores || $this->not_mapped_characteristics)) || !$this->everything_clear)
            return false;

        // count components in this source
        $goods_classes = array();

        $sql = "SELECT `value` " .
            "FROM `Netshop_ImportMap` " .
            "WHERE `source_id` = '{$this->source_id}' " .
            "AND `type` = 'section'";
        $sub_ids = $this->db->get_col($sql);

        if ($sub_ids) {
            $sql = "SELECT `Subdivision_ID`, `Class_ID`, `Sub_Class_ID` " .
                "FROM `Sub_Class` " .
                "WHERE `Subdivision_ID` IN (" . join(",", $sub_ids) . ") " .
                "ORDER BY `Priority` DESC";
            $res = (array)$this->db->get_results($sql, ARRAY_A);
            foreach ($res AS $value) {
                $goods_classes[] = $value['Class_ID'];
            }
        }

        foreach ($goods_classes AS $class) {
            $messages_tbl[$class] = "`Message" . $class . "`";
            $this_class = $class;
        }

        //import stores in system table
        foreach ($offers_data_stores as $store_id => $store) {
            $store_id = $this->db->escape($store_id);
            $store = $this->db->escape($store);

            $sql = "INSERT INTO `Netshop_Stores` (`Import_Source_ID`, `Import_Store_ID`, `Name`) VALUES " .
                "({$this->source_id}, '{$store_id}', '{$store}') " .
                "ON DUPLICATE KEY UPDATE `Name` = '{$store}'";

            $this->db->query($sql);
        }

        $current_num = 1;
        $total_objects = 0;
        $total_files = $this->cache_data_count("offers_data_offers(\d)*?\.cache");
        $i = 0;
        while ($offers_data_offers = $this->cache_data_out("offers_data_offers" . $i)) {
            // once count total objects
            if (!$total_objects) {
                $total_objects = $total_files * count($offers_data_offers);
            }

            foreach ($offers_data_offers as $key => $value) {

                if (!$this->quite && $current_num == 1) {
                    echo "<b>" . NETCAT_MODULE_NETSHOP_IMPORT_OFFERS . "</b><br/>\r\n";
                    $this->progress_bar_show("offers_progress");
                    echo "<br/>\r\n";
                }

                $this_id = $value[NETCAT_MODULE_NETSHOP_1C_ID];
                $this_id = preg_replace('/#.*$/', '', $this_id);
                $this_name = trim($value[NETCAT_MODULE_NETSHOP_1C_NAME]);
                $this_currency_arr = $value[NETCAT_MODULE_NETSHOP_1C_PRICES][NETCAT_MODULE_NETSHOP_1C_PRICE];
                $this_prop = array();
                // if components in source > 1,
                //check commodity in all components
                if (!empty($messages_tbl) && count($messages_tbl) > 1) {
                    $sub_class_id = 0;
                    foreach ($messages_tbl as $table) {
                        $sql = "SELECT `Sub_Class_ID` " .
                            "FROM {$table} " .
                            "WHERE `ItemID` = '" . $this_id . "' " .
                            "AND `ImportSourceID` = '{$this->source_id}'";

                        $sub_class_id = (int)$this->db->get_var($sql);

                        if ($sub_class_id) {
                            break;
                        }
                    }

                    $sql = "SELECT `Class_ID` FROM `Sub_Class` WHERE `Sub_Class_ID` = {$sub_class_id}";
                    $this_class = (int)$this->db->get_var($sql);
                }
                if (empty($this_currency_arr[0])) {
                    $this_currencies[$this_currency_arr[NETCAT_MODULE_NETSHOP_1C_PRICE_TYPE_ID]] = $this_currency_arr;
                } else {
                    $this_currencies = array();
                    foreach ($this_currency_arr AS $object_currency) {
                        $this_currencies[$object_currency[NETCAT_MODULE_NETSHOP_1C_PRICE_TYPE_ID]] = $object_currency;
                    }
                }
                // commodity currency

                $variants_price = array();

                foreach ($packets AS $packet_id => $packet) {
                    if (!$packet["column"] || $packet["column"] == -1) continue;
                    $field_name = $packet['column'];
                    if ((int)$packet['column']) {
                        list($field_name, $field_type) = $this->db->get_row("SELECT `Field_Name`, `TypeOfData_ID` FROM `Field`
							WHERE `Field_ID` = '" . (int)$packet['column'] . "'", ARRAY_N);
                    }

                    // get price
                    $this_prop[$field_name] = $this_currencies[$packet_id][NETCAT_MODULE_NETSHOP_1C_PRICE_UNIT];
                    $variants_price[$field_name] = $this_currencies[$packet_id][NETCAT_MODULE_NETSHOP_1C_PRICE_UNIT];
                    // get currency
                    $this_currency = $this_currencies[$packet_id][NETCAT_MODULE_NETSHOP_1C_CURRENCY];

                    if ($this_currency == NETCAT_MODULE_NETSHOP_1C_CURRENCY_DEFAULT)
                        $this_currency = "RUR";

                    // check if currency exists, else add it
                    if (!$this_currency) {
                        $this_currency = "RUR";
                    } else if (!$this->currency[$this_currency]) {
                        $this->db->query("INSERT INTO `Classificator_ShopCurrency` SET `ShopCurrency_Name` = '" . $this_currency . "'");
                        $this->currency[$this_currency] = $this->db->insert_id;
                    }
                    // !!! TODO: check (a) price column exists in template
                    $currency_add = str_replace("Price", "", $field_name);
                    $this_prop["Currency" . $currency_add] = $this->currency[$this_currency];
                    $variants_price["Currency" . $currency_add] = $this->currency[$this_currency];
                }
                // additional fields compile
                $filetable_lastid = 0;
                if (!empty($fields) && !empty($class_fields) && $this_class) {
                    foreach ($class_fields[$this_class] AS $field_key => $field_data) {
                        if ($field_data['column'] == -1) continue;
                        list($field_name, $field_type) = $this->db->get_row("SELECT `Field_Name`, `TypeOfData_ID` FROM `Field`
							WHERE `Field_ID` = '" . (int)$field_data['column'] . "'", ARRAY_N);
                        $this_prop[$field_name] = trim($value[$field_key]);
                    }
                }

                if (isset($value[NETCAT_MODULE_NETSHOP_1C_STORE_REMAIN]) && $this_class) {
                    $stores_qty = $value[NETCAT_MODULE_NETSHOP_1C_STORE_REMAIN][NETCAT_MODULE_NETSHOP_1C_REMAIN];

                    $sql = "SELECT `Message_ID` FROM `Message{$this_class}` WHERE `ItemID` = '{$this_id}' AND `ImportSourceID` = '{$this->source_id}'";
                    $message_id = (int)$this->db->get_var($sql);

                    foreach ($stores_qty as $store) {
                        $store_id = $store[NETCAT_MODULE_NETSHOP_1C_STORE_ID];
                        $store_id_escaped = $this->db->escape($store_id);
                        $qty = (float)$store[NETCAT_MODULE_NETSHOP_1C_STORE_QTY];

                        if (isset($mapped_stores[$this_class][$store_id])) {
                            $field_id = (int)$mapped_stores[$this_class][$store_id];
                            $sql = "SELECT `Field_Name` FROM `Field` WHERE `Field_ID` = {$field_id}";
                            $field_name = $this->db->get_var($sql);
                            if ($field_name) {
                                $this_prop[$field_name] = $qty;
                            }
                        }

                        if ($message_id) {
                            $sql = "SELECT `Netshop_Store_ID` FROM `Netshop_Stores` WHERE " .
                                "`Import_Store_ID` = '{$store_id_escaped}' " .
                                "AND `Import_Source_ID` = {$this->source_id}";
                            $netshop_store_id = (int)$this->db->get_var($sql);

                            if ($netshop_store_id) {
                                $sql = "REPLACE INTO `Netshop_StoreGoods` (`Netshop_Store_ID`, `Netshop_Item_ID`, `Class_ID`, `Quantity`) VALUES " .
                                    "({$netshop_store_id}, {$message_id}, {$this_class}, {$qty})";
                                $this->db->query($sql);
                            }
                        }
                    }
                }

                // collect fields in temp array
                if (!empty($this_prop)) {
                    $query = array();
                    foreach ($this_prop AS $k => $v) {
                        if ($k) {
                            $query[] = "`" . $k . "` = '" . $this->db->escape($v) . "'";
                        }
                    }
                    // equip MySQL append query
                    $query_str = "SET " . join(", ", $query);
                    unset($query);
                }

                // update object
                $this->db->query("UPDATE `Message" . $this_class . "` " . $query_str . " WHERE `ItemID` = '" . $this_id . "' AND `ImportSourceID` = '" . $this->source_id . "'");
                //$this->db->debug();

                if (isset($value[NETCAT_MODULE_NETSHOP_1C_PRODUCT_CHARS]) && $this_class) {
                    $characteristics = $value[NETCAT_MODULE_NETSHOP_1C_PRODUCT_CHARS];

                    $query_fields = array();

                    foreach ($characteristics as $key => $characteristic) {
                        if (isset($mapped_characteristics[$this_class][$key])) {
                            $field_id = (int)$mapped_characteristics[$this_class][$key];
                            $sql = "SELECT `Field_Name` FROM `Field` WHERE `Field_ID` = {$field_id}";
                            $field_name = $this->db->get_var($sql);
                            if ($field_name) {
                                $query_fields[$field_name] = $characteristic;
                            }
                        }
                    }

                    if (count($query_fields) == count($characteristics)) {
                        $sql = "SELECT `Message_ID` FROM `Message{$this_class}` WHERE `ItemID` = '{$this_id}' AND `ImportSourceID` = '{$this->source_id}'";
                        $message_id = (int)$this->db->get_var($sql);

                        $sql = "DELETE FROM `Message{$this_class}` WHERE `Parent_Message_ID` = {$message_id}";
                        foreach ($query_fields as $field => $characteristic) {
                            $sql .= " AND `{$field}` = '" . $this->db->escape($characteristic) . "'";
                        }

                        $this->db->query($sql);

                        $sql = "INSERT INTO `Message{$this_class}` SET `Parent_Message_ID` = {$message_id}";
                        foreach (array_merge($query_fields, $variants_price) as $field => $characteristic) {
                            $sql .= ", `{$field}` = '" . $this->db->escape($characteristic) . "'";
                        }

                        $this->db->query($sql);
                    }
                }


                // procents completed
                $percent = intval($current_num / $total_objects * 100);
                $this->progress_bar_update("offers_progress", $percent);
                // increment
                $current_num++;
            }
            $i++;
        }
        // set 100% complete
        $this->progress_bar_update("offers_progress", 100);

        // logging
        if ($this->debug) $this->debug(__METHOD__ . "() OK");
    }

    function map_stores_dialog() {
        $this->everything_clear = false;
        echo "<input type='hidden' name='collect_post' value='1'>";
        echo "<b>" . NETCAT_MODULE_NETSHOP_IMPORT_MAP_STORES . ":</b>\r\n";
        echo "<table border='0' cellspacing='8' cellpadding='0'>\r\n";

        $exlude_fields_arr = array("ItemID", "Currency", "Price", "ImportSourceID", "TopSellingMultiplier", "TopSellingAddition");

        foreach ($this->shop_classes AS $class) {
            $res = $this->db->get_results("SELECT f.`Field_ID`, f.`Field_Name`, f.`Description`
            FROM `Class` AS c
                LEFT JOIN `Field` AS f ON c.`Class_ID` = f.`Class_ID`
            WHERE c.`Class_ID` = '" . intval($class['id']) . "'
                AND f.`Field_Name` NOT IN ('" . join("', '", $exlude_fields_arr) . "')
            GROUP BY f.`Field_Name`", ARRAY_A);

            $options = "";
            if (!empty($res)) {
                foreach ($res AS $value) {
                    $options .= "<option value='" . $value['Field_ID'] . "'>[" . $value['Field_Name'] . "] " . $value['Description'] . "</option>\r\n";
                }
            }

            echo "<tr><td colspan='3' style='background:#EEE; padding:3px'>[" . $class['id'] . "] " . $class['name'] . "</td></tr>";

            // get data
            $offers_data_stores = $this->cache_data_out("offers_data_stores");
            foreach ($offers_data_stores as $store_id => $store) {
                $store_id_escaped = $this->db->escape($store_id);
                $sql = "SELECT `value` FROM `Netshop_ImportMap` WHERE `source_id` = {$this->source_id} AND `source_string` = '{$store_id_escaped}' AND `type` = 'store'";
                $result = $this->db->get_row($sql) ? true : false;

                if ($result) {
                    continue;
                }

                $description = NETCAT_MODULE_NETSHOP_IMPORT_REMAIN_IN_STORE . ' ' . $store;
                echo "<tr>";
                echo "<td>{$description}</td><td>&rarr;</td>";
                echo "<td><select name='map_stores[" . $class['id'] . "][" . urlencode($store_id) . "]'>";
                echo "<option value='-1'>----------------------------------------</option>";
                echo $options;
                echo "</select></td>";
                echo "</tr>";
            }

        }

        echo "</table><br/>";

        // logging
        if ($this->debug) $this->debug(__METHOD__ . " OK");
    }

    function map_characteristics_dialog() {
        $this->everything_clear = false;
        echo "<input type='hidden' name='collect_post' value='1'>";
        echo "<b>" . NETCAT_MODULE_NETSHOP_IMPORT_MAP_CHARACTERISTICS . ":</b>\r\n";
        echo "<table border='0' cellspacing='8' cellpadding='0'>\r\n";

        $exlude_fields_arr = array("ItemID", "Currency", "Price", "ImportSourceID", "TopSellingMultiplier", "TopSellingAddition");

        foreach ($this->shop_classes AS $class) {
            $res = $this->db->get_results("SELECT f.`Field_ID`, f.`Field_Name`, f.`Description`
            FROM `Class` AS c
                LEFT JOIN `Field` AS f ON c.`Class_ID` = f.`Class_ID`
            WHERE c.`Class_ID` = '" . intval($class['id']) . "'
                AND f.`Field_Name` NOT IN ('" . join("', '", $exlude_fields_arr) . "')
            GROUP BY f.`Field_Name`", ARRAY_A);

            $options = "";
            if (!empty($res)) {
                foreach ($res AS $value) {
                    $options .= "<option value='" . $value['Field_ID'] . "'>[" . $value['Field_Name'] . "] " . $value['Description'] . "</option>\r\n";
                }
            }

            echo "<tr><td colspan='3' style='background:#EEE; padding:3px'>[" . $class['id'] . "] " . $class['name'] . "</td></tr>";

            // get data
            $offers_data_characteristics = $this->cache_data_out("offers_data_characteristics");
            foreach ($offers_data_characteristics as $characteristic) {
                $characteristic_escaped = $this->db->escape($characteristic);
                $sql = "SELECT `value` FROM `Netshop_ImportMap` WHERE `source_id` = {$this->source_id} AND `source_string` = '{$characteristic_escaped}' AND `type` = 'ocharacteristic'";
                $result = $this->db->get_row($sql) ? true : false;

                if ($result) {
                    continue;
                }

                $description = $characteristic;
                echo "<tr>";
                echo "<td>{$description}</td><td>&rarr;</td>";
                echo "<td><select name='map_characteristics[" . $class['id'] . "][" . urlencode($characteristic) . "]'>";
                echo "<option value='-1'>----------------------------------------</option>";
                echo $options;
                echo "</select></td>";
                echo "</tr>";
            }

        }

        echo "</table><br/>";

        // logging
        if ($this->debug) $this->debug(__METHOD__ . " OK");
    }

    function get_owner_data() {
        // file existence
        if (!file_exists($this->filename_path)) {
            // logging
            if ($this->debug)
                $this->debug(__METHOD__ . "() FAIL - file " . $this->filename_path . " does't exist");
            // return
            return false;
        }

        $owner_data = $this->cml2->get_owner($this->filename_path);

        if (!$this->nc_core->NC_UNICODE) {
            $owner_data = $this->nc_core->utf8->array_utf2win($owner_data);
        }

        return $owner_data;
    }

    /**
     * This function gets orders data
     *
     * @return bool
     */
    function get_orders_data() {
        // file existence
        if (!file_exists($this->filename_path)) {
            // logging
            if ($this->debug)
                $this->debug(__METHOD__ . "() FAIL - file " . $this->filename_path . " does't exist");
            // return
            return false;
        }

        // get orders
        $this->cml2->get_orders($this->filename_path, $this, 'get_orders_data_callback');
    }

    function import_orders_data() {
        $current_num = 1;
        $total_objects = 0;
        $total_files = $this->cache_data_count("orders_data_orders(\d)*?\.cache");
        $i = 0;

        $catalogue_id = (int)$this->catalogue_id;

        $netshop = nc_netshop::get_instance($catalogue_id);
        $is_netshop_v1_in_use = $netshop->is_netshop_v1_in_use();

        $currency_table = null;
        $payment_methods_table = null;

        if ($is_netshop_v1_in_use) {
            $shop_table = (int)$this->MODULE_VARS['netshop']['SHOP_TABLE'];
            $order_table = (int)$this->MODULE_VARS['netshop']['ORDER_TABLE'];
            $currency_table = (int)$this->MODULE_VARS['netshop']['CURRENCY_RATES_TABLE'];
            $payment_methods_table = (int)$this->MODULE_VARS['netshop']['PAYMENT_METHODS_TABLE'];
            $sql = "SELECT DISTINCT `Subdivision_ID` FROM `Sub_Class` WHERE `Class_ID` = {$shop_table} AND `Catalogue_ID` = {$catalogue_id} LIMIT 1";
            $shop_subdivision = (int)$this->db->get_var($sql);
        } else {
            $order_table = $netshop->get_setting('OrderComponentID');
            $sql = "SELECT `root_subdivision_id` FROM `Netshop_ImportSources` WHERE `catalogue_id` = {$catalogue_id} LIMIT 1";
            $shop_subdivision = (int)$this->db->get_var($sql);
        }

        //clean Netshop_OrderIds table
        $sql = "DELETE FROM `Netshop_OrderIds` WHERE `Netshop_Order_ID` NOT IN (SELECT `Message_ID` FROM `Message{$order_table}`)";
        $this->db->query($sql);

        if ($this->source_id) {
            $source_ids = array(
                array(
                    'source_id' => $this->source_id,
                )
            );
        } else {
            $sql = "SELECT `source_id` FROM `Netshop_ImportSources` WHERE `catalogue_id` = {$catalogue_id}";
            $source_ids = (array)$this->db->get_results($sql, ARRAY_A);
        }


        $map_id_fields = array();
        foreach ($source_ids as $source_id) {
            $source_id = (int)$source_id['source_id'];
            $sql = "SELECT `value` FROM `Netshop_ImportMap` WHERE `source_id` = {$source_id} AND `source_string` = 'Ид' LIMIT 1";
            $field_id = (int)$this->db->get_var($sql);

            if ($field_id) {
                $sql = "SELECT `Field_Name`, `Class_ID` FROM `Field` WHERE `Field_ID` = {$field_id}";
                $field = $this->db->get_row($sql, ARRAY_A);
                if ($field) {
                    $map_id_fields[] = $field;
                }
            }
        }

        $sql = "SELECT `Sub_Class_ID` FROM `Sub_Class` WHERE `Class_ID` = {$order_table} AND `Subdivision_ID` = {$shop_subdivision} LIMIT 1";
        $order_subclass = $this->db->get_var($sql);

        if (!$shop_subdivision || !$order_subclass) {
            return false;
        }

        while ($orders_data_orders = $this->cache_data_out("orders_data_orders" . $i)) {
            // once count total objects
            if (!$total_objects) {
                $total_objects = $total_files * count($orders_data_orders);
            }

            foreach ($orders_data_orders as $order) {

                if (!$this->quite && $current_num == 1) {
                    echo "<b>" . NETCAT_MODULE_NETSHOP_IMPORT_ORDERS . "</b><br/>\r\n";
                    $this->progress_bar_show("orders_progress");
                    echo "<br/>\r\n";
                }

                $id = (int)$order['Ид'];
                $date = $this->db->escape($order['Дата']);
                $currency = $this->db->escape($order['Валюта']);

                if ($is_netshop_v1_in_use) {
                    $sql = "SELECT `Message_ID` FROM `Message{$currency_table}` WHERE `Subdivision_ID` = {$shop_subdivision} AND `NameShort` = '{$currency}' LIMIT 1";
                    $currency_id = (int)$this->db->get_var($sql);
                } else {
                    $currency_id = $netshop->get_currency_id($currency);
                }

                $payment_method = 0;
                $status = 0;

                if (isset($order['ЗначенияРеквизитов']['ЗначениеРеквизита'])) {
                    $properties = $order['ЗначенияРеквизитов']['ЗначениеРеквизита'];
                    if (!isset($properties[0])) {
                        $properties = array($properties);
                    }

                    foreach ($properties as $property) {
                        if ($property['Наименование'] == 'Метод оплаты') {
                            $payment_method_string = $this->db->escape($property['Значение']);

                            if ($is_netshop_v1_in_use) {
                                $sql = "SELECT `Message_ID` FROM `Message{$payment_methods_table}` WHERE `Subdivision_ID` = {$shop_subdivision} AND `Name` = '{$payment_method_string}' LIMIT 1";
                                $payment_method = (int)$this->db->get_var($sql);
                            } else {
                                $payment_method = new nc_netshop_payment_method();
                                $payment_method->load_where('name', $payment_method_string);
                                $payment_method = $payment_method->get_id();
                            }

                        } else if ($property['Наименование'] == 'Статус заказа') {
                            $status_string = $property['Значение'];

                            switch ($status_string) {
                                case '[N] Новый':
                                    $status = 0;
                                    break;
                                case '[A] Принят':
                                    $status = 1;
                                    break;
                                case '[O] Отклонен':
                                    $status = 2;
                                    break;
                                case '[P] Оплачен':
                                    $status = 3;
                                    break;
                                case '[F] Завершен':
                                    $status = 4;
                                    break;
                            }
                        }
                    }
                }


                $contragent = $order['Контрагенты']['Контрагент'];
                if (isset($contragent[0])) {
                    $contragent = $contragent[0];
                }

                $user_id = (int)$contragent['Ид'];
                $contact_name = $this->db->escape($contragent['Наименование']);

                $address = $this->db->escape($contragent['АдресРегистрации']['Представление']);

                $comment = $this->db->escape($order['Комментарий'] ? $order['Комментарий'] : '');

                $sql = "LOCK TABLES `Netshop_OrderIds` WRITE, `Message{$order_table}` WRITE";
                $this->db->query($sql);

                $sql = "SELECT `Netshop_Order_ID` FROM `Netshop_OrderIds` WHERE `1c_Order_ID` = {$id} AND `Catalogue_ID` = {$catalogue_id}";
                $netcat_order_id = (int)$this->db->get_var($sql);

                if ($netcat_order_id) {
                    $sql = "UPDATE `Message{$order_table}` SET " .
                        "`LastUpdated` = NOW(), " .
                        "`OrderCurrency` = '{$currency_id}', " .
                        "`User_ID` = {$user_id}, " .
                        "`ContactName` = '{$contact_name}', " .
                        "`Address` = '{$address}', " .
                        "`Comments` = '{$comment}', " .
                        "`DeliveryCost` = 0, " .
                        "`PaymentMethod` = {$payment_method}, " .
                        "`Status` = {$status} " .
                        "WHERE `Message_ID` = {$netcat_order_id}";
                    $this->db->query($sql);
                } else {
                    $sql = "SELECT MAX(`priority`) FROM `Message{$order_table}` " .
                        "WHERE `Subdivision_ID` = {$shop_subdivision} " .
                        "AND `Sub_Class_ID` = {$order_subclass}";
                    $priority = (int)$this->db->get_var($sql) + 1;

                    $sql = "INSERT INTO `Message{$order_table}`  " .
                        "(`Subdivision_ID`, `Sub_Class_ID`, `Checked`, `Priority`, `Created`, `LastUpdated`, `OrderCurrency`, `User_ID`, `ContactName`, `Address`, `Comments`, `DeliveryCost`, `PaymentMethod`, `Status`) VALUES " .
                        "({$shop_subdivision}, {$order_subclass}, 1, {$priority}, '{$date}', NOW(), '{$currency_id}', {$user_id}, '{$contact_name}', '{$address}', '{$comment}', 0, {$payment_method}, {$status})";
                    $this->db->query($sql);

                    $netcat_order_id = (int)$this->db->insert_id;

                    if ($netcat_order_id) {
                        $sql = "INSERT INTO `Netshop_OrderIds` (`Netshop_Order_ID`, `Catalogue_ID`, `1c_Order_ID`) VALUES " .
                            "({$netcat_order_id}, {$catalogue_id}, {$id})";
                        $this->db->query($sql);
                    }
                }

                $sql = "UNLOCK TABLES";
                $this->db->query($sql);

                //processing goods
                $goods = array();
                if (isset($order['Товары']['Товар'])) {
                    $goods = $order['Товары']['Товар'];
                    if (!isset($goods[0])) {
                        $goods = array($goods);
                    }
                }

                $goods_ids = array();

                foreach ($goods as $item) {
                    $item_id = $item['Ид'];

                    if ($item_id == 'ORDER_DELIVERY') {
                        if (isset($item['Сумма'])) {
                            $cost = (float)$item['Сумма'];

                            $sql = "UPDATE `Message{$order_table}` SET `DeliveryCost` = {$cost} WHERE `Message_ID` = {$netcat_order_id}";
                            $this->db->query($sql);
                        }
                        continue;
                    }

                    $goods_ids[] = $item_id;

                    $item_class_id = 0;
                    $item_message_id = 0;

                    if (preg_match('/^netcat_(\d+)_(\d+)$/', $item_id, $match)) {
                        $item_class_id = (int)$match[1];
                        $item_message_id = (int)$match[2];

                        $sql = "SELECT `Message_ID` FROM `Message{$item_class_id}` WHERE `Message_ID` = {$item_message_id} LIMIT 1";

                        if (!$this->db->get_var($sql)) {
                            $item_class_id = 0;
                            $item_message_id = 0;
                        }
                    } else {
                        foreach ($map_id_fields as $map_id_field) {
                            $map_id_class = (int)$map_id_field['Class_ID'];
                            $map_id_field_name = $map_id_field['Field_Name'];

                            $sql = "SELECT `Message_ID` FROM `Message{$map_id_class}` WHERE `{$map_id_field_name}` = '" . $this->db->escape($item_id) . "' LIMIT 1";
                            $item_message_id = (int)$this->db->get_var($sql);
                            if ($item_message_id) {
                                $item_class_id = $map_id_class;
                                break;
                            }
                        }
                    }

                    if ($item_class_id && $item_message_id) {
                        $sql = "SELECT `Item_ID` FROM `Netshop_OrderGoods` " .
                            "WHERE `Order_ID` = {$netcat_order_id} " .
                            "AND `Item_Type` = {$item_class_id} " .
                            "AND `Item_ID` = {$item_message_id}";

                        $qty = (float)$item['Количество'];
                        $price = (float)$item['ЦенаЗаЕдиницу'];

                        if ($this->db->get_var($sql)) {
                            $sql = "UPDATE `Netshop_OrderGoods` SET " .
                                "`Qty` = {$qty}, " .
                                "`ItemPrice` = {$price}, " .
                                "`OriginalPrice` = {$price} " .
                                "WHERE `Order_ID` = {$netcat_order_id} " .
                                "AND `Item_Type` = {$item_class_id} " .
                                "AND `Item_ID` = {$item_message_id}";
                        } else {
                            $sql = "INSERT INTO `Netshop_OrderGoods` (`Order_ID`, `Item_Type`, `Item_ID`, `Qty`, `ItemPrice`, `OriginalPrice`) VALUES " .
                                "({$netcat_order_id}, {$item_class_id}, {$item_message_id}, {$qty}, {$price}, {$price})";
                        }
                        $this->db->query($sql);
                    }


                }

                $sql = "SELECT `Item_Type`, `Item_ID` FROM `Netshop_OrderGoods` WHERE `Order_ID` = {$netcat_order_id}";
                $current_goods = (array)$this->db->get_results($sql, ARRAY_A);

                foreach ($current_goods as $item) {
                    $item_type = (int)$item['Item_Type'];
                    $item_id = (int)$item['Item_ID'];

                    $netcat_item_id = 'netcat_' . $item_type . '_' . $item_id;
                    if (in_array($netcat_item_id, $goods_ids)) {
                        continue;
                    }

                    $found = false;
                    foreach ($map_id_fields as $map_id_field) {
                        $map_id_class = (int)$map_id_field['Class_ID'];
                        $map_id_field_name = $map_id_field['Field_Name'];

                        if ($item_type == $map_id_class) {
                            $sql = "SELECT `{$map_id_field_name}` FROM `Message{$map_id_class}` WHERE `Message_ID` = {$item_id} LIMIT 1";
                            $ext_item_id = $this->db->get_var($sql);
                            if ($ext_item_id && in_array($ext_item_id, $goods_ids)) {
                                $found = true;
                                break;
                            }
                        }
                    }

                    if ($found) {
                        continue;
                    }

                    $sql = "DELETE FROM `Netshop_OrderGoods` WHERE `Order_ID` = {$netcat_order_id} AND `Item_Type` = {$item_type} AND `Item_ID` = {$item_id}";
                    $this->db->query($sql);
                }

                // procents completed
                $percent = intval($current_num / $total_objects * 100);
                $this->progress_bar_update("orders_progress", $percent);
                // increment
                $current_num++;
            }
            $i++;
        }
        // set 100% complete
        $this->progress_bar_update("orders_progress", 100);

        // logging
        if ($this->debug) $this->debug(__METHOD__ . "() OK");
    }

    function map_packets_dialog() {

        $this->everything_clear = false;
        echo "<input type='hidden' name='collect_post' value='1'>";
        echo "<b>" . NETCAT_MODULE_NETSHOP_IMPORT_MAP_PRICE . ":</b>\r\n";
        echo "<table border='0' cellspacing='8' cellpadding='0'>\r\n";

        $catalogue_id = $this->catalogue_id;
        $netshop = nc_netshop::get_instance($catalogue_id);

        if ($netshop->is_netshop_v1_in_use($catalogue_id)) {
            $goods_table = intval($this->MODULE_VARS['GOODS_TABLE']);
        } else {
            $goods_table = $netshop->get_goods_components_ids();
            $goods_table = $goods_table[0];
        }

        $res = $this->db->get_results("SELECT f.`Field_Name` AS id, f.`Description` AS name
      FROM `Class` AS c
			LEFT JOIN `Field` AS f ON c.`Class_ID` = f.`Class_ID`
      WHERE f.`Field_Name` LIKE 'Price%'
      AND c.`Class_ID` = '{$goods_table}'
      GROUP BY f.`Field_Name`", ARRAY_A);

        $price_col_options = "";
        if (!empty($res)) {
            foreach ($res AS $value) {
                $price_col_options .= "<option value='" . $value['id'] . "'>[" . $value['id'] . "] " . $value['name'] . "</option>\r\n";
            }
        }

        // get data
        $offers_data_packets_arr = $this->cache_data_out("offers_data_packets_arr");
        // action)
        if (!empty($offers_data_packets_arr)) {
            foreach ($offers_data_packets_arr as $key => $value) {
                $key_escaped = $this->db->escape($key);
                $sql = "SELECT `value` FROM `Netshop_ImportMap` WHERE `source_id` = {$this->source_id} AND `source_string` = '{$key_escaped}' AND `type` = 'price'";
                $result = $this->db->get_row($sql) ? true : false;
                if (!$result && !$value['column']) {
                    echo "<tr>";
                    echo "<td>" . $value['name'] . "</td><td>&rarr;</td>";
                    echo "<td><select name='map_packets[" . urlencode($key) . "]'>";
                    echo "<option value='-1'>----------------------------------------</option>";
                    echo $price_col_options;
                    echo "</select></td>";
                    echo "</tr>";
                }
            }
        }

        echo "</table><br/>";

        // logging
        if ($this->debug) $this->debug(__METHOD__ . " OK");
    }

    function get_catalogue_data_callback($level = 0, $data) {
        // callbacks iterator
        static $i = 0;
        if (!$this->nc_core->NC_UNICODE)
            $data = $this->nc_core->utf8->array_utf2win($data);
        $catalogue_data_commodity = array();
        // collect recursive
        $catalogue_data_commodity_fields = $this->cache_data_out("catalogue_data_commodity_fields");
        $catalogue_data_commodity_characteristics = $this->cache_data_out("catalogue_data_commodity_characteristics");
        $catalogue_data_commodity_requisites = $this->cache_data_out("catalogue_data_commodity_requisites");
        $catalogue_data_commodity_properties = $this->cache_data_out("catalogue_data_commodity_properties");
        $catalogue_data_commodity_tax = $this->cache_data_out("catalogue_data_commodity_tax");

        //get classifier properties
        $classifier_properties_data = $this->cache_data_out("classifier_properties_data");

        // walking...
        foreach ($data as $key => $value) {

            $index = $key + $level;

            foreach ($value as $name => $row) {
                /**
                 * $child['n'] - name
                 * $child['d'] - data
                 * $child['c'] - data array
                 */
                // put data in array, skip ignore tags

                if (!in_array($name, $this->import_ignore_tags) && (!is_array($row) || $name == NETCAT_MODULE_NETSHOP_1C_IMG)) {
                    $catalogue_data_commodity[$index][$name] = $row;
                    $catalogue_data_commodity_fields[$name]++;
                    continue;
                }
                // group(s)_id
                if ($name == NETCAT_MODULE_NETSHOP_1C_GROUPS) {
                    // [Группы] => Array([NETCAT_MODULE_NETSHOP_1C_NAME] => 12345678-a6b7-11de-9109-0025563c5a06)
                    if (isset($row[NETCAT_MODULE_NETSHOP_1C_GROUP])) {
                        $catalogue_data_commodity[$index][$name] = $row;
                    } else {
                        $catalogue_data_commodity[$index][$name][NETCAT_MODULE_NETSHOP_1C_GROUP][] = $row;
                    }

                    // do not show this
                    ###$catalogue_data_commodity_fields[$name]++;
                    continue;
                }
                // characteristics
                if ($name == NETCAT_MODULE_NETSHOP_1C_PRODUCT_CHARS) {
                    if (!$catalogue_data_commodity_characteristics)
                        $catalogue_data_commodity_characteristics = array();

                    if (isset($row[NETCAT_MODULE_NETSHOP_1C_PRODUCT_CHAR]) && !isset($row[NETCAT_MODULE_NETSHOP_1C_PRODUCT_CHAR][0])) {
                        $row[NETCAT_MODULE_NETSHOP_1C_PRODUCT_CHAR] = array(0 => $row[NETCAT_MODULE_NETSHOP_1C_PRODUCT_CHAR]);
                    }

                    foreach ($row[NETCAT_MODULE_NETSHOP_1C_PRODUCT_CHAR] as $v) {
                        $_name = "";
                        $_value = "";
                        foreach ($v as $_n => $_v) {
                            if ($_n == NETCAT_MODULE_NETSHOP_1C_NAME)
                                $_name = $_v;
                            if ($_n == NETCAT_MODULE_NETSHOP_1C_VALUE)
                                $_value = $_v;
                        }
                        $catalogue_data_commodity[$index][$name][$_name] = $_value;
                        if (!in_array($_name, $catalogue_data_commodity_characteristics)) {
                            $catalogue_data_commodity_characteristics[] = $_name;
                        }
                        $catalogue_data_commodity_fields[$_name]++;
                    }
                    continue;
                }
                // requisites
                if ($name == NETCAT_MODULE_NETSHOP_1C_REC_VALUES) {
                    if (!$catalogue_data_commodity_requisites)
                        $catalogue_data_commodity_requisites = array();

                    if (isset($row[NETCAT_MODULE_NETSHOP_1C_REC_VALUE]) && !isset($row[NETCAT_MODULE_NETSHOP_1C_REC_VALUE][0])) {
                        $row[NETCAT_MODULE_NETSHOP_1C_REC_VALUE] = array(0 => $row[NETCAT_MODULE_NETSHOP_1C_REC_VALUE]);
                    }

                    foreach ($row[NETCAT_MODULE_NETSHOP_1C_REC_VALUE] as $v) {
                        $_name = "";
                        $_value = "";
                        foreach ($v as $_n => $_v) {
                            if ($_n == NETCAT_MODULE_NETSHOP_1C_NAME)
                                $_name = $_v;
                            if ($_n == NETCAT_MODULE_NETSHOP_1C_VALUE)
                                $_value = $_v;
                        }
                        $catalogue_data_commodity[$index][$name][$_name] = $_value;
                        if (!in_array($_name, $catalogue_data_commodity_requisites)) {
                            $catalogue_data_commodity_requisites[] = $_name;
                        }
                        $catalogue_data_commodity_fields[$_name]++;
                    }
                    continue;
                }
                // properties
                if ($name == NETCAT_MODULE_NETSHOP_1C_PROPERTIES_VALUES) {
                    if (!$catalogue_data_commodity_properties)
                        $catalogue_data_commodity_properties = array();

                    if (isset($row[NETCAT_MODULE_NETSHOP_1C_PROPERTIES_VALUE]) && !isset($row[NETCAT_MODULE_NETSHOP_1C_PROPERTIES_VALUE][0])) {
                        $row[NETCAT_MODULE_NETSHOP_1C_PROPERTIES_VALUE] = array(0 => $row[NETCAT_MODULE_NETSHOP_1C_PROPERTIES_VALUE]);
                    }

                    if (isset($row[NETCAT_MODULE_NETSHOP_1C_PROPERTIES_VALUE])) {
                        foreach ($row[NETCAT_MODULE_NETSHOP_1C_PROPERTIES_VALUE] as $v) {
                            $_name = "";
                            $_value = "";
                            foreach ($v as $_n => $_v) {
                                if ($_n == NETCAT_MODULE_NETSHOP_1C_ID)
                                    $_name = $_v;
                                if ($_n == NETCAT_MODULE_NETSHOP_1C_VALUE)
                                    $_value = $_v;
                            }

                            $property_value = $_value;

                            if ($_value &&
                                isset($classifier_properties_data[$_name]) &&
                                isset($classifier_properties_data[$_name]['values'][$_value])
                            ) {
                                $property_value = $classifier_properties_data[$_name]['values'][$_value];
                            }


                            if (isset($classifier_properties_data[$_name])) {
                                $_name = $classifier_properties_data[$_name]['name'];
                            }

                            $catalogue_data_commodity[$index][$name][$_name] = $property_value;

                            if (!in_array($_name, $catalogue_data_commodity_properties)) {
                                $catalogue_data_commodity_properties[] = $_name;
                            }
                            $catalogue_data_commodity_fields[$_name]++;
                        }
                    }
                    continue;
                }
                // tax
                if ($name == NETCAT_MODULE_NETSHOP_1C_TAXES) {
                    if (!$catalogue_data_commodity_tax)
                        $catalogue_data_commodity_tax = array();

                    if (isset($row[NETCAT_MODULE_NETSHOP_1C_TAX]) && !isset($row[NETCAT_MODULE_NETSHOP_1C_TAX][0])) {
                        $row[NETCAT_MODULE_NETSHOP_1C_TAX] = array(0 => $row[NETCAT_MODULE_NETSHOP_1C_TAX]);
                    }

                    foreach ($row[NETCAT_MODULE_NETSHOP_1C_TAX] as $v) {
                        $_name = "";
                        $_value = "";
                        foreach ($v as $_n => $_v) {
                            if ($_n == NETCAT_MODULE_NETSHOP_1C_NAME)
                                $_name = $_v;
                            if ($_n == NETCAT_MODULE_NETSHOP_1C_RATE)
                                $_value = $_v;
                        }
                        $catalogue_data_commodity[$index][$name][$_name] = $_value;
                        if (!in_array($_name, $catalogue_data_commodity_tax)) {
                            $catalogue_data_commodity_tax[] = $_name;
                        }
                        $catalogue_data_commodity_fields[$_name]++;
                    }
                    continue;
                }
            }
        }
        if (!$this->nc_core->NC_UNICODE) {
            $catalogue_data_commodity = $this->nc_core->utf8->array_win2utf($catalogue_data_commodity);
            $catalogue_data_commodity_fields = $this->nc_core->utf8->array_win2utf($catalogue_data_commodity_fields);
            $catalogue_data_commodity_characteristics = $this->nc_core->utf8->array_win2utf($catalogue_data_commodity_characteristics);
            $catalogue_data_commodity_properties = $this->nc_core->utf8->array_win2utf($catalogue_data_commodity_properties);
            $catalogue_data_commodity_tax = $this->nc_core->utf8->array_win2utf($catalogue_data_commodity_tax);
        }
        // store data
        $bytes_writed = $this->cache_data_in($catalogue_data_commodity, "catalogue_data_commodity" . $i);
        // logging
        if ($this->debug)
            $this->debug(__METHOD__ . " OK - 'catalogue_data_commodity" . $i . "' data " . $bytes_writed . " bytes cached");

        $bytes_writed = $this->cache_data_in($catalogue_data_commodity_fields, "catalogue_data_commodity_fields");
        // logging
        if ($this->debug)
            $this->debug(__METHOD__ . " OK - 'catalogue_data_commodity_fields' data " . $bytes_writed . " bytes cached");

        $bytes_writed = $this->cache_data_in($catalogue_data_commodity_characteristics, "catalogue_data_commodity_characteristics");
        // logging
        if ($this->debug)
            $this->debug(__METHOD__ . " OK - 'catalogue_data_commodity_characteristics' data " . $bytes_writed . " bytes cached");

        $bytes_writed = $this->cache_data_in($catalogue_data_commodity_requisites, "catalogue_data_commodity_requisites");
        // logging
        if ($this->debug)
            $this->debug(__METHOD__ . " OK - 'catalogue_data_commodity_requisites' data " . $bytes_writed . " bytes cached");

        $bytes_writed = $this->cache_data_in($catalogue_data_commodity_properties, "catalogue_data_commodity_properties");
        // logging
        if ($this->debug)
            $this->debug(__METHOD__ . " OK - 'catalogue_data_commodity_properties' data " . $bytes_writed . " bytes cached");

        $bytes_writed = $this->cache_data_in($catalogue_data_commodity_tax, "catalogue_data_commodity_tax");
        // logging
        if ($this->debug)
            $this->debug(__METHOD__ . " OK - 'catalogue_data_commodity_tax' data " . $bytes_writed . " bytes cached");

        // callbacks iterator
        $i++;

        // continue callback
        return;
    }

    /**
     * This function get catalogue data
     */
    function get_catalogue_data() {
        // file existence
        if (!file_exists($this->filename_path)) return false;

        // get catalog goods and properties (returned values)
        $catalogue_data_properties = $this->cml2->get_catalog($this->filename_path, $this, 'get_catalogue_data_callback');

        // store catalog properties
        $bytes_writed = $this->cache_data_in($catalogue_data_properties, "catalogue_data_properties");

        // logging
        if ($this->debug)
            $this->debug(__METHOD__ . " OK - " . $bytes_writed . " bytes cached");

        return $bytes_writed;
    }

    function import_catalogue_data() {
        // get data
        $catalogue_data_commodity = $this->cache_data_out("catalogue_data_commodity0");
        $sub_structure = $this->cache_data_out("sub_structure");
        // return if no data found
        if (empty($catalogue_data_commodity)) {
            // logging
            if ($this->debug) $this->debug(__METHOD__ . " FAIL - no data");
            // return
            return false;
        }

        $map_field = array();
        // get data
        $catalogue_data_commodity_fields = $this->cache_data_out("catalogue_data_commodity_fields");
        $catalogue_data_commodity_characteristics = $this->cache_data_out("catalogue_data_commodity_characteristics");
        $catalogue_data_commodity_requisites = $this->cache_data_out("catalogue_data_commodity_requisites");
        $catalogue_data_commodity_properties = $this->cache_data_out("catalogue_data_commodity_properties");
        $catalogue_data_commodity_tax = $this->cache_data_out("catalogue_data_commodity_tax");

        // action)
        if (!empty($catalogue_data_commodity_fields)) {
            ###$need_tags = array("Описание", "Статус", "Артикул", "Картинка", "Производитель");
            // добавляем характеристики как будто есть такие теги в родителе "Товар"
            // на самом деле есть только тэг "ХарактеристикиТовара"
            ###if ( isset($catalogue_data_commodity_characteristics) && is_array($catalogue_data_commodity_characteristics) ) {
            ###  $need_tags = array_merge($need_tags, $catalogue_data_commodity_characteristics);
            ###}
            foreach ($catalogue_data_commodity_fields as $xml_tag => $tag_count) {
                $xml_tag = trim($xml_tag);
                ###if ( !in_array($xml_tag, $need_tags) ) continue;
                if (in_array($xml_tag, $this->import_ignore_tags)) continue;
                if (!$xml_tag) continue;

                $fields[$xml_tag]['name'] = $xml_tag;
                foreach ($this->shop_classes as $class) {
                    $map_field = $_POST['map_fields'][$class['id']][urlencode($xml_tag)];

                    $parent_tag = '';
                    if (is_array($catalogue_data_commodity_characteristics) && in_array($xml_tag, $catalogue_data_commodity_characteristics))
                        $parent_tag = NETCAT_MODULE_NETSHOP_1C_PRODUCT_CHARS;
                    if (is_array($catalogue_data_commodity_requisites) && in_array($xml_tag, $catalogue_data_commodity_requisites))
                        $parent_tag = NETCAT_MODULE_NETSHOP_1C_REC_VALUES;
                    if (is_array($catalogue_data_commodity_properties) && in_array($xml_tag, $catalogue_data_commodity_properties))
                        $parent_tag = NETCAT_MODULE_NETSHOP_1C_PROPERTIES_VALUES;
                    if (is_array($catalogue_data_commodity_tax) && in_array($xml_tag, $catalogue_data_commodity_tax))
                        $parent_tag = NETCAT_MODULE_NETSHOP_1C_TAXES;

                    // write compliance (second pass)
                    if ($map_field) {
                        $format = '';

                        if ($xml_tag == NETCAT_MODULE_NETSHOP_1C_IMG) {
                            $resize_format = array(
                                'enabled' => isset($_POST['resize'][$class['id']][urlencode($xml_tag)]),
                                'width' => (int)$_POST['resize_width'][$class['id']][urlencode($xml_tag)],
                                'height' => (int)$_POST['resize_height'][$class['id']][urlencode($xml_tag)],
                            );
                            $preview_format = array(
                                'enabled' => isset($_POST['preview'][$class['id']][urlencode($xml_tag)]),
                                'width' => (int)$_POST['preview_width'][$class['id']][urlencode($xml_tag)],
                                'height' => (int)$_POST['preview_height'][$class['id']][urlencode($xml_tag)],
                            );

                            $format = array(
                                'resize' => $resize_format,
                                'preview' => $preview_format,
                            );

                            $format = $this->db->escape(serialize($format));
                        }

                        $this->db->query("REPLACE INTO `Netshop_ImportMap`
              SET `source_id` = '" . $this->source_id . "',
              `type` = 'property',
              `source_string` = '" . $this->db->escape($xml_tag) . "',
              `format` = '{$format}',
              `value` = '" . $this->db->escape($map_field) . "'" . ($parent_tag ? ", `parent_tag` = '" . $parent_tag . "'" : ""));
                    }
                }
            }
        }

        // get ralation array sub_id - sub_class_id - class_id
        if (!$this->templates) $this->templates = $this->get_templates();

        $class_fields = array();

        if ($this->templates) {
            foreach ($this->templates as $template) {
                if (!isset($class_fields[$template['class_id']])) {
                    $class_fields[$template['class_id']] = $fields;
                }
            }
        }
        $fields_from_base = array();

        if (!empty($class_fields)) {
            foreach ($class_fields as $class_id => $fields_array) {
                $class_id = (int)$class_id;

                foreach ($fields_array as $field_name => $field_array) {
                    $field_name_escaped = $this->db->escape($field_name);

                    $sql = "SELECT `value` FROM `Netshop_ImportMap` WHERE " .
                        "`source_id` = {$this->source_id} " .
                        "AND `type` = 'property' " .
                        "AND `source_string` = '{$field_name_escaped}'";

                    $values = $this->db->get_col($sql);

                    if ($values && count($values) >= count($class_fields)) {
                        if (!in_array($field_name, $fields_from_base)) {
                            $fields_from_base[] = $field_name;
                        }
                        $field_column = -1;

                        foreach ($values as $value) {
                            $value = (int)$value;
                            if ($value != -1) {
                                $sql = "SELECT `Field_ID` FROM `Field` WHERE `Field_ID` = {$value} AND `Class_ID` = {$class_id}";
                                if ($this->db->get_var($sql)) {
                                    $field_column = $value;
                                    break;
                                }
                            }
                        }

                        $class_fields[$class_id][$field_name]['column'] = $field_column;
                    }
                }
            }
        }

        $autoimport_mode = isset($_COOKIE['nc-import-cookie']);

        $this->not_mapped_fields = count($fields) - count($fields_from_base);
        // for map_fields_dialog function
        $this->not_mapped_fields_arr = is_array($this->not_mapped_fields_arr) ? array_merge($this->not_mapped_fields_arr, $fields) : $fields;
        //if ($this->not_mapped_fields) return false;
        // clear?
        if ((!$autoimport_mode && $this->not_mapped_fields) || !$this->everything_clear || !$sub_structure) {
            // logging
            if ($this->debug)
                $this->debug(__METHOD__ . " FAIL - unmapped fields or no 'sub_structure'");
            // return
            return false;
        }

        //disable all positions
        $source_id = $this->db->escape($this->source_id);
        $disable_positions_query = $this->db->get_row("SELECT `nonexistant` FROM `Netshop_ImportSources` WHERE `source_id` = '{$source_id}'", ARRAY_A);
        $disable_positions = $disable_positions_query && $disable_positions_query['nonexistant'] == 'disable';
        if ($disable_positions) {
            $class_ids = array();
            $sql = "SELECT `value` FROM `Netshop_ImportMap` WHERE `type` = 'section' AND `source_id` = '{$source_id}'";
            $subdivisions_query = $this->db->get_results($sql, ARRAY_A);

            foreach ((array)$subdivisions_query as $subdivision) {
                $subdivision_id = (int)$subdivision['value'];
                if ($subdivision_id) {
                    $sql = "SELECT `Class_ID` FROM `Sub_Class` WHERE `Subdivision_ID` = {$subdivision_id} LIMIT 1";
                    $class_id_query = $this->db->get_row($sql, ARRAY_A);
                    if ($class_id_query) {
                        $class_id = $class_id_query['Class_ID'];
                        if (!in_array($class_id, $class_ids)) {
                            $class_ids[] = $class_id;
                        }
                    }
                }
            }

            $sql = "SELECT `value` FROM `Netshop_ImportMap` WHERE `type` <> 'section' AND `value` <> -1 AND `source_id` = '{$source_id}'";
            $fields_query = $this->db->get_results($sql, ARRAY_A);

            foreach ((array)$fields_query as $field) {
                $field_id = (int)$field['value'];
                if ($field_id) {
                    $sql = "SELECT `Class_ID` FROM `Field` WHERE `Field_ID` = {$field_id} LIMIT 1";
                    $class_id_query = $this->db->get_row($sql, ARRAY_A);
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
                $this->db->query($sql);
            }
        }

        $current_num = 1;
        $total_objects = 0;
        $total_files = $this->cache_data_count("catalogue_data_commodity(\d)*?\.cache");
        $i = 0;
        while ($catalogue_data_commodity = $this->cache_data_out("catalogue_data_commodity" . $i)) {
            // once count total objects
            if (!$total_objects) {
                $total_objects = $total_files * count($catalogue_data_commodity);
            }
            foreach ($catalogue_data_commodity as $key => $value) {
                // progress bar
                if (!$this->quite && $current_num == 1) {
                    echo "<b>" . NETCAT_MODULE_NETSHOP_IMPORT_COMMODITIES_IN_CATALOGUE . "</b><br/>\r\n";
                    $this->progress_bar_show("commodity_progress");
                    echo "<br/>\r\n";
                }

                // main XML values
                $this_units = isset($value[NETCAT_MODULE_NETSHOP_1C_BASE_UNIT]) ? $value[NETCAT_MODULE_NETSHOP_1C_BASE_UNIT] : false;
                $this_groups = $value[NETCAT_MODULE_NETSHOP_1C_GROUPS][NETCAT_MODULE_NETSHOP_1C_GROUP][0][NETCAT_MODULE_NETSHOP_1C_ID];
                $this_id = $value[NETCAT_MODULE_NETSHOP_1C_ID];
                $this_name = trim($value[NETCAT_MODULE_NETSHOP_1C_NAME]);
                $this_characteristics = $value[NETCAT_MODULE_NETSHOP_1C_PRODUCT_CHARS];
                $this_requisites = $value[NETCAT_MODULE_NETSHOP_1C_REC_VALUES];
                $this_properties = $value[NETCAT_MODULE_NETSHOP_1C_PROPERTIES_VALUES];
                $this_tax = $value[NETCAT_MODULE_NETSHOP_1C_TAXES];

                // insert unknown units in base
                if ($this_units && !$this->units[$this_units]) {
                    $this->db->query("INSERT INTO `Classificator_ShopUnits` SET `ShopUnits_Name` = '" . $this_units . "'");
                    $this->units[$this_units] = $this->db->insert_id;
                }

                // values for base
                $this_prop = array();
                // ("Name", "ItemID", "Price", "Currency", "ImportSourceID") imperative fields
                $this_prop["Checked"] = 1;
                $this_prop["Name"] = $this_name;
                $this_prop["Units"] = $this->units[$this_units];
                $this_prop["ImportSourceID"] = $this->source_id;
                $this_prop["ItemID"] = $this_id;
                $this_prop["Subdivision_ID"] = $sub_structure[$this_groups]['Subdivision_ID'];
                // Class_ID
                $this_class = $this->templates[$this_prop["Subdivision_ID"]]["class_id"];
                // ignored group, disabled in "map_sections_dialog" dialog
                if ($this_prop["Subdivision_ID"] == -1) {
                    // increment
                    $current_num++;
                    continue;
                }
                // executed after MySQL insert
                $this_prop["Sub_Class_ID"] = $sub_structure[$this_groups]['Sub_Class_ID'];
                // Sub_Class data from base
                if (!$this_prop["Sub_Class_ID"])
                    $this_prop["Sub_Class_ID"] = $this->templates[$this_prop["Subdivision_ID"]]["subclass_id"];
                // Priority
                $this_prop["Priority"] = (int)$this->db->get_var("SELECT MAX(`Priority`) + 1 FROM `Message" . $this_class . "` WHERE `Sub_Class_ID` = '" . $this_prop["Sub_Class_ID"] . "'");

                // try to find goods with same Item ID
                $exist_id = $this->db->get_var("SELECT `Message_ID` FROM `Message" . $this_class . "`
          WHERE `ItemID` = '" . $this_id . "'
          AND `ImportSourceID` = '" . $this->source_id . "'");

                // additional fields compile
                $filetable_lastid = 0;
                $multifield_ids = array();
                if (!empty($class_fields) && !empty($fields) && $this_class)
                    foreach ($class_fields[$this_class] AS $field_key => $field_data) {
                        if ($field_data['column'] == -1) continue;
                        list($field_name, $field_type) = $this->db->get_row("SELECT `Field_Name`, `TypeOfData_ID` FROM `Field`
            WHERE `Field_ID` = '" . (int)$field_data['column'] . "'", ARRAY_N);

                        if (!$field_name) {
                            continue;
                        }

                        $xml_value = !is_array($value[$field_key]) ? trim($value[$field_key]) : '';

                        switch (true) {
                            // в массиве $catalogue_data_commodity есть тэг "ЗначенияРеквизитов"
                            // но значение следует брать из массива $this_requisites, т.к. тэг составной
                            case is_array($this_requisites) && array_key_exists($field_key, $this_requisites):
                                $this_prop[$field_name] = $this_requisites[$field_key];
                                break;
                            // в массиве $catalogue_data_commodity есть тэг "ЗначенияСвойств"
                            // но значение следует брать из массива $this_properties, т.к. тэг составной
                            case is_array($this_properties) && array_key_exists($field_key, $this_properties):
                                $this_prop[$field_name] = $this_properties[$field_key];
                                break;
                            // в массиве $catalogue_data_commodity есть тэг "ХарактеристикиТовара"
                            // но значение следует брать из массива $this_characteristics, т.к. тэг составной
                            case is_array($this_characteristics) && array_key_exists($field_key, $this_characteristics):
                                $this_prop[$field_name] = $this_characteristics[$field_key];
                                break;
                            // в массиве $catalogue_data_commodity есть тэг "СтавкиНалогов"
                            // но значение следует брать из массива $this_tax, т.к. тэг составной
                            case is_array($this_tax) && array_key_exists($field_key, $this_tax):
                                $this_prop[$field_name] = $this_tax[$field_key];
                                break;
                            // тэг "Картинка"
                            case $field_type == 6 && $field_key == NETCAT_MODULE_NETSHOP_1C_IMG:
                                /** filename similarly commodity ID ($this_id) */

                                if (is_array($value[$field_key])) {
                                    $xml_value = trim($value[$field_key][0]);
                                }

                                if (isset($_COOKIE['nc-import-cookie'])) {
                                    $xml_value = 'catalog-' . $_COOKIE['nc-import-cookie'] . '-' . $xml_value;
                                }
                                if (!$xml_value || !file_exists($this->filedir . $xml_value))
                                    continue;
                                // image properties
                                list($filewidth, $fileheight, $filetype, $fileattr) = getimagesize($this->filedir . $xml_value);
                                $filetype = image_type_to_mime_type($filetype);
                                $filename = basename($this->filedir . $xml_value);
                                $filesize = filesize($this->filedir . $xml_value);
                                $this_prop[$field_name] = $filename . ":" . $filetype . ":" . $filesize;
                                $file_copy_path = $this_prop["Subdivision_ID"] . "/" . $this_prop["Sub_Class_ID"] . "/";
                                // md5 name with salt
                                $uniq_file_name = md5($filename . microtime() . uniqid("netcat"));

                                $exist_sql = $this->db->get_row("SELECT `ID`, `Real_Name`, `Virt_Name` FROM `Filetable`
                WHERE `Message_ID` = '" . $exist_id . "' AND `Field_ID` = '" . (int)$field_data['column'] . "'", ARRAY_N);

                                if ($exist_sql) {
                                    list($_id, $_real_name, $_virt_name) = $exist_sql;
                                    $this->db->query("UPDATE `Filetable`
                  SET `Real_Name` = '" . $filename . "', `Virt_Name` = '" . $uniq_file_name . "', `File_Type` = '" . $filetype . "', `File_Size` = '" . $filesize . "'
                  WHERE `ID` = '" . $_id . "'");
                                    # Delete old file ...
                                    @unlink($GLOBALS['FILES_FOLDER'] . $file_copy_path . $_virt_name);
                                } else {
                                    $this->db->query("INSERT INTO `Filetable`
                  (`Real_Name`, `File_Path`, `Virt_Name`, `File_Type`, `File_Size`, `Message_ID`, `Field_ID`)
                  VALUES
                  ('" . $filename . "', '/" . $file_copy_path . "', '" . $uniq_file_name . "', '" . $filetype . "', '" . $filesize . "', '0', '" . (int)$field_data['column'] . "')");
                                    $filetable_lastid = $this->db->insert_id;
                                }

                                // create dirs
                                if (!isset($GLOBALS['DIRCHMOD']))
                                    $GLOBALS['DIRCHMOD'] = 0777;
                                @mkdir($GLOBALS['FILES_FOLDER'] . $this_prop["Subdivision_ID"], $GLOBALS['DIRCHMOD']);
                                @mkdir($GLOBALS['FILES_FOLDER'] . rtrim($file_copy_path, "/"), $GLOBALS['DIRCHMOD']);
                                @chmod($GLOBALS['FILES_FOLDER'] . rtrim($file_copy_path, "/"), $GLOBALS['DIRCHMOD']);
                                // copy file
                                @copy($this->filedir . $xml_value, $GLOBALS['FILES_FOLDER'] . $file_copy_path . $uniq_file_name);

                                $field_key_escaped = $this->db->escape($field_key);
                                $sql = "SELECT `format` FROM `Netshop_ImportMap` WHERE `source_id` = {$source_id} AND `source_string` = '{$field_key_escaped}'";
                                $format = @unserialize($this->db->get_var($sql));
                                if (!$format) {
                                    $format = array(
                                        'resize' => array('enabled' => false),
                                        'preview' => array('enabled' => false),
                                    );
                                }

                                if ($format['resize']['enabled'] && $format['resize']['width'] && $format['resize']['height']) {
                                    @nc_ImageTransform::imgResize($GLOBALS['FILES_FOLDER'] . $file_copy_path . $uniq_file_name, $GLOBALS['FILES_FOLDER'] . $file_copy_path . $uniq_file_name, $format['resize']['width'], $format['resize']['height']);
                                }
                                break;
                            case $field_type == 11 && $field_key == NETCAT_MODULE_NETSHOP_1C_IMG:
                                /** filename similarly commodity ID ($this_id) */
                                $this_prop[$field_name] = '';

                                if (!is_array($value[$field_key])) {
                                    $value[$field_key] = array($value[$field_key]);
                                }
                                $field_key_escaped = $this->db->escape($field_key);
                                $sql = "SELECT `format` FROM `Netshop_ImportMap` WHERE `source_id` = {$source_id} AND `source_string` = '{$field_key_escaped}'";
                                $format = @unserialize($this->db->get_var($sql));
                                if (!$format) {
                                    $format = array(
                                        'resize' => array('enabled' => false),
                                        'preview' => array('enabled' => false),
                                    );
                                }

                                $remove_old_files = false;

                                foreach ($value[$field_key] as $xml_value) {
                                    $xml_value = trim($xml_value);

                                    if (isset($_COOKIE['nc-import-cookie'])) {
                                        $xml_value = 'catalog-' . $_COOKIE['nc-import-cookie'] . '-' . $xml_value;
                                    }
                                    if (!$xml_value || !file_exists($this->filedir . $xml_value))
                                        continue;


                                    $remove_old_files = true;
                                    // image properties
                                    list($filewidth, $fileheight, $filetype, $fileattr) = getimagesize($this->filedir . $xml_value);
                                    $filetype = image_type_to_mime_type($filetype);
                                    $filename = basename($this->filedir . $xml_value);
                                    $filesize = filesize($this->filedir . $xml_value);

                                    $file_copy_path = 'multifile/' . $field_data['column'] . '/';
                                    // md5 name with salt

                                    $uniq_file_name = nc_get_filename_for_original_fs($filename, $GLOBALS['FILES_FOLDER'] . $file_copy_path);

                                    $exist_sql = $this->db->get_row("SELECT `ID`, `Path` FROM `Multifield`
                WHERE `Message_ID` = '" . $exist_id . "' AND `Field_ID` = '" . (int)$field_data['column'] . "'", ARRAY_N);

                                    $size = (int)filesize($this->filedir . $xml_value);

                                    $http_path = nc_Core::get_object()->HTTP_FILES_PATH;

                                    $path = $this->db->escape(nc_standardize_path_to_folder($http_path . '/' . $file_copy_path) . $uniq_file_name);
                                    $preview = $this->db->escape(nc_standardize_path_to_folder($http_path . '/' . $file_copy_path) . 'preview_' . $uniq_file_name);

                                    if (!isset($GLOBALS['DIRCHMOD']))
                                        $GLOBALS['DIRCHMOD'] = 0777;
                                    @mkdir($GLOBALS['FILES_FOLDER'] . rtrim($file_copy_path, "/"), $GLOBALS['DIRCHMOD']);
                                    @chmod($GLOBALS['FILES_FOLDER'] . rtrim($file_copy_path, "/"), $GLOBALS['DIRCHMOD']);
                                    // copy file
                                    @copy($this->filedir . $xml_value, $GLOBALS['FILES_FOLDER'] . $file_copy_path . $uniq_file_name);

                                    if ($format['resize']['enabled'] && $format['resize']['width'] && $format['resize']['height']) {
                                        @nc_ImageTransform::imgResize($GLOBALS['FILES_FOLDER'] . $file_copy_path . $uniq_file_name, $GLOBALS['FILES_FOLDER'] . $file_copy_path . $uniq_file_name, $format['resize']['width'], $format['resize']['height']);
                                    }

                                    if ($format['preview']['enabled'] && $format['preview']['width'] && $format['preview']['height']) {
                                        @nc_ImageTransform::imgResize($GLOBALS['FILES_FOLDER'] . $file_copy_path . $uniq_file_name, $GLOBALS['FILES_FOLDER'] . $file_copy_path . 'preview_' . $uniq_file_name, $format['preview']['width'], $format['preview']['height']);
                                    }

                                    $sql = "SELECT MAX(`Priority`) FROM `Multifield` WHERE `Field_ID` = '{$field_data['column']}'";
                                    $max_priority = (int)$this->db->get_var($sql) + 1;

                                    $sql = "INSERT INTO `Multifield` (`Field_ID`, `Size`, `Path`, `Preview`, `Priority`) VALUES " .
                                        "('{$field_data['column']}', '{$size}', '{$path}', '{$preview}', '{$max_priority}')";


                                    $this->db->query($sql);
                                    $multifield_ids[] = $this->db->insert_id;
                                }

                                if ($remove_old_files) {
                                    if (count($multifield_ids) > 0) {
                                        $new_ids = implode(',', $multifield_ids);

                                        $sql = "SELECT `Path`, `Preview` FROM `Multifield` WHERE `Field_ID` = {$field_data['column']} AND `Message_ID` = {$exist_id} AND `ID` NOT IN ({$new_ids})";
                                        $paths = $this->db->get_results($sql, ARRAY_A);

                                        if ($paths) {
                                            foreach ($paths as $item) {
                                                foreach(array('Path', 'Preview') as $key) {
                                                    @unlink($GLOBALS['DOCUMENT_ROOT'] . $GLOBALS['SUB_FOLDER'] . $item[$key]);
                                                }
                                            }

                                            $sql = "DELETE FROM `Multifield` WHERE `Field_ID` = {$field_data['column']} AND `Message_ID` = {$exist_id} AND `ID` NOT IN ({$new_ids})";
                                            $this->db->query($sql);
                                        }
                                    }
                                }

                                break;
                            default:
                                if ($field_name != 'Units')
                                    $this_prop[$field_name] = $xml_value;
                        }
                    }


                // collect fields in temp array
                $query = array();
                foreach ($this_prop AS $k => $v) {
                    if ($exist_id && (
                            $k == 'Subdivision_ID' ||
                            $k == 'Sub_Class_ID' ||
                            $k == 'ImportSourceID' ||
                            $k == 'ItemID' ||
                            $k == 'Priority'
                        )
                    ) {
                        continue;
                    }
                    if ($v == array()) {
                        $v = '';
                    }
                    $query[] = "`" . $k . "` = '" . $this->db->escape($v) . "'";
                }
                // equip MySQL append query
                $query_str = "SET " . join(", ", $query);
                unset($query);

                // create new goods or update existed
                if (!$exist_id) {
                    $this->db->query("INSERT INTO `Message" . $this_class . "` " . $query_str . ", `Created` = NOW()");
                    //$this->db->debug();
                    $message_id = $this->db->insert_id;
                    if ($filetable_lastid)
                        $this->db->query("UPDATE `Filetable` SET `Message_ID` = '" . (int)$message_id . "' WHERE `ID` = '" . (int)$filetable_lastid . "'");

                    if (count($multifield_ids) > 0) {
                        $new_ids = implode(',', $multifield_ids);

                        $sql = "UPDATE `Multifield` SET `Message_ID` = {$message_id} WHERE `ID` IN ({$new_ids})";
                        $this->db->query($sql);
                    }
                } else {
                    $this->db->query("UPDATE `Message" . $this_class . "` " . $query_str . " WHERE `Message_ID` = '" . $exist_id . "'");
                    if ($filetable_lastid)
                        $this->db->query("UPDATE `Filetable` SET `Message_ID` = '" . (int)$exist_id . "' WHERE `ID` = '" . (int)$filetable_lastid . "'");
                    //$this->db->debug();

                    if (count($multifield_ids) > 0) {
                        $new_ids = implode(',', $multifield_ids);

                        $sql = "UPDATE `Multifield` SET `Message_ID` = " . (int)$exist_id . " WHERE `ID` IN ({$new_ids})";
                        $this->db->query($sql);
                    }
                }

                // procents completed
                $percent = intval($current_num / $total_objects * 100);
                $this->progress_bar_update("commodity_progress", $percent);
                // increment
                $current_num++;
            }
            $i++;
        }
        // set 100% complete
        $this->progress_bar_update("commodity_progress", 100);

        // logging
        if ($this->debug)
            $this->debug(__METHOD__ . " OK - " . $bytes_writed . " bytes cached");
    }

    function map_fields_dialog() {

        $this->everything_clear = false;
        ?>
        <script type="text/javascript">
            $nc(function () {
                $('INPUT[name^="resize["], INPUT[name^="preview["]').on('change', function () {
                    var $this = $nc(this);
                    if ($this.is(':checked')) {
                        $this.nextAll('DIV').eq(0).show();
                    } else {
                        $this.nextAll('DIV').eq(0).hide();
                    }
                    return false;
                });
            });
        </script>
        <?php

        echo "<input type='hidden' name='collect_post' value='1'>";

        echo "<b>" . NETCAT_MODULE_NETSHOP_IMPORT_FIELDS_AND_TAGS_COMPLIANCE . "</b>\r\n";
        echo "<table border='0' cellspacing='8' cellpadding='0'>\r\n";

        //all fields: Name, Description, Details, ItemID, Currency, Price, PriceMinimum, Image, Units, Vendor, StockUnits, ImportSourceID, CurrencyMinimum, TopSellingMultiplier, TopSellingAddition, VAT
        ###$exlude_fields_arr = array("Name", "ItemID", "Currency", "Price", "PriceMinimum", "ImportSourceID", "CurrencyMinimum", "TopSellingMultiplier", "TopSellingAddition", "VAT");
        $exlude_fields_arr = array("ItemID", "Currency", "Price", "ImportSourceID", "TopSellingMultiplier", "TopSellingAddition");

        // netshop goods classes
        foreach ($this->shop_classes AS $class) {

            $class_fields = $this->db->get_results("SELECT `Field_ID`, `Field_Name`, `Description`, `Class_ID` FROM `Field`
        WHERE `Class_ID` = '" . $class['id'] . "'
        AND `Field_Name` NOT IN ('" . join("', '", $exlude_fields_arr) . "')", ARRAY_A);

            $fields_str = "";
            foreach ($class_fields AS $field) {
                $fields_str .= "<option value='" . $field['Field_ID'] . "'>[" . $field['Field_Name'] . "] - " . $field['Description'] . "</option>\r\n";
            }
            echo "<tr><td colspan='3' style='background:#EEE; padding:3px'>[" . $class['id'] . "] " . $class['name'] . "</td></tr>";
            if (!empty($this->not_mapped_fields_arr)) {

                foreach ($this->not_mapped_fields_arr AS $key => $value) {
                    $key_escaped = $this->db->escape($key);
                    $sql = "SELECT `value` FROM `Netshop_ImportMap` WHERE `source_id` = {$this->source_id} AND `source_string` = '{$key_escaped}' AND `type` IN ('property', 'oproperty')";
                    $result = $this->db->get_row($sql) ? true : false;
                    /* todo: check behaviour */
                    $result = false;
                    if (!$result && !$value['column']) {
                        echo "<tr>";
                        echo "<td>" . $value['name'] . "</td><td>&rarr;</td>";
                        echo "<td><select name='map_fields[" . $class['id'] . "][" . urlencode($key) . "]'>";
                        echo "<option value='-1'>----------------------------------------</option>";
                        echo $fields_str;
                        echo "</select></td>";
                        echo "</tr>";


                        if ($key == NETCAT_MODULE_NETSHOP_1C_IMG) {
                            $format = array();
                            if (!isset($format['resize'])) {
                                $format['resize'] = array(
                                    'enabled' => false,
                                    'width' => 0,
                                    'height' => 0,
                                );
                            }

                            if (!isset($format['preview'])) {
                                $format['preview'] = array(
                                    'enabled' => false,
                                    'width' => 0,
                                    'height' => 0,
                                );
                            }
                            ?>
                            <tr>
                                <td></td>
                                <td></td>
                                <td>
                                    <input type="checkbox" name="resize[<?= $class['id']; ?>][<?= urlencode($key); ?>]"
                                           id="nc_use_resize_<?= urlencode($class['id'] . $key); ?>" <?php echo $format['resize']['enabled'] ? 'checked="checked"' : ''; ?> />
                                    <label
                                        for="nc_use_resize_<?= urlencode($class['id'] . $key); ?>"><?= CONTROL_FIELD_MULTIFIELD_USE_IMAGE_RESIZE; ?></label>

                                    <div <?php echo !$format['resize']['enabled'] ? 'style="display: none;"' : ''; ?>>
                                        <?= CONTROL_FIELD_MULTIFIELD_IMAGE_WIDTH; ?>:
                                        <input type="text" value="<?= $format['resize']['width']; ?>"
                                               name="resize_width[<?= $class['id']; ?>][<?= urlencode($key); ?>]"
                                               size="10"/>
                                        <?= CONTROL_FIELD_MULTIFIELD_IMAGE_HEIGHT; ?>:
                                        <input type="text" value="<?= $format['resize']['height']; ?>"
                                               name="resize_height[<?= $class['id']; ?>][<?= urlencode($key); ?>]"
                                               size="10"/>
                                    </div>
                                    <br>
                                    <input type="checkbox" name="preview[<?= $class['id']; ?>][<?= urlencode($key); ?>]"
                                           id="nc_use_preview_<?= urlencode($key); ?>" <?php echo $format['preview']['enabled'] ? 'checked="checked"' : ''; ?> />
                                    <label
                                        for="nc_use_preview_<?= urlencode($key); ?>"><?= CONTROL_FIELD_MULTIFIELD_USE_IMAGE_PREVIEW; ?></label>

                                    <div <?php echo !$format['preview']['enabled'] ? 'style="display: none;"' : ''; ?>>
                                        <?= CONTROL_FIELD_MULTIFIELD_IMAGE_WIDTH; ?>:
                                        <input type="text" value="<?= $format['preview']['width']; ?>" size="10"
                                               name="preview_width[<?= $class['id']; ?>][<?= urlencode($key); ?>]"/>
                                        <?= CONTROL_FIELD_MULTIFIELD_IMAGE_HEIGHT; ?>:
                                        <input type="text" value="<?= $format['preview']['height']; ?>" size="10"
                                               name="preview_height[<?= $class['id']; ?>][<?= urlencode($key); ?>]"/>
                                    </div>
                                </td>
                            </tr>
                        <?php
                        }
                    }
                }
            }
        }
        echo "</table><br/>";

        // logging
        if ($this->debug) $this->debug(__METHOD__ . " OK");
    }

    /**
     * Show HTML progress bar
     * @param html id
     * @param return or echo
     * @return html text
     */
    function progress_bar_show($html_id, $ret = false) {
        // quite mode
        if ($this->quite) return false;

        $result = "<div id='" . $html_id . "_line' style='position:absolute; border:1px solid #FFF; height:20px; width:0px; background:#5699c7;'></div>\r\n";
        $result .= "<div style='position:absolute; border:1px solid #333; text-align:center; height:20px; width:420px; background:none; color:#264863'><p id='" . $html_id . "_text' style='padding:0; margin:2px 0 0'>0%</p></div>\r\n";
        $result .= "<br clear='all'/>\r\n";
        if ($ret) return $result;
        echo $result;
    }

    /**
     * Update HTML progress bar
     * @param html id
     * @param percent
     * @param return or echo
     * @return html text
     */
    function progress_bar_update($html_id, $percent, $ret = false) {
        // quite mode
        if ($this->quite) return false;

        $result = "<script type='text/javascript'>nc_netshop_import_progress('" . $percent . "', '" . $html_id . "');</script>\r\n";
        if ($ret) return $result;
        echo $result;
        flush();
    }

    /**
     * From UTF to Win
     */
    function uc($text) {
        return $text;
        switch (true) {
            case extension_loaded("mbstring"):
                return mb_convert_encoding($text, "cp1251", "UTF-8");
            case extension_loaded("iconv"):
                return iconv("UTF-8", "cp1251", $text);
            default:
                return $this->uniconv->utf8ToStr($text);
        }
    }

    /**
     * From Win to UTF
     */
    function cu($text) {
        switch (true) {
            case extension_loaded("mbstring"):
                return mb_convert_encoding($text, "UTF-8", "cp1251");
            case extension_loaded("iconv"):
                return iconv("cp1251", "UTF-8", $text);
            default:
                return $this->uniconv->strToUtf8($text);
        }
    }

}

?>
