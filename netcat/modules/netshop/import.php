<?php
if (isset($_POST['collected_post'])) {
    $post_data = array();
    parse_str(($_POST['collected_post']), $post_data);

    foreach ($post_data as $name => $value) {
        $_REQUEST[$name] = $value;
    }

    $_POST = $post_data;
    unset($_REQUEST['collected_post']);
}

require_once("old/header.inc.php");

$UI_CONFIG = new ui_config_module_netshop('admin', 'import');
$UI_CONFIG->subheaderText = NETCAT_MODULE_NETSHOP_IMPORT_COMMERCEML;
$UI_CONFIG->treeSelectedNode = "netshop-1c.import";
$UI_CONFIG->toolbar = false;
$UI_CONFIG->tabs = false;
$UI_CONFIG->locationHash = "module.netshop.1c.import";

$netshop = nc_netshop::get_instance();


/**
 * Get CML version from importing file
 * @param filename importing file path
 * @return cml_version
 */
function nc_netshop_get_cml_version($filename) {
    global $INCLUDE_FOLDER;
    // check file existance
    if (!file_exists($filename)) return false;
    // get info from file
    $import_file = fopen($filename, "r");
    $first_string = fgets($import_file);
    $second_string = fgets($import_file);
    fclose($import_file);
    nc_preg_match("/<\?xml\s+.*?encoding=\"([\w\d-]+)\".*?\?>/is", $first_string, $matches);

    $xml_charset = isset($matches[1]) ? strtolower($matches[1]) : 'utf-8';
    if ($xml_charset != 'utf8' && $xml_charset != 'utf-8') {
        $second_string = nc_Core::get_object()->utf8->win2utf($second_string);
    }

    $reqex = "/<КоммерческаяИнформация\s+.*?ВерсияСхемы=\"([\d\.]+)\".*?/is";

    nc_preg_match($reqex, $second_string, $matches);
    $cml_version = isset($matches[1]) && str_replace(".", "", $matches[1]) >= 203 ? 2 : 1;
    // return version
    return $cml_version;
}

echo "<form method='post' enctype='multipart/form-data'>";
# new source (name)
if ($_POST['name']) {
    $db->query("INSERT INTO `Netshop_ImportSources`
      SET `name` = '" . $db->escape($_POST["name"]) . "',
      `catalogue_id` = '" . intval($catalogue_id) . "',
      `scheme` = '" . intval($cml_version) . "'");
    $source_id = $db->insert_id;
}

$source_id = (int)$source_id;

if (($_FILES["upload"] || $_POST['ftp_path']) && $source_id) {
    if ($_FILES["upload"]) {
        # save file
        $filename = uniqid("importcml");
        move_uploaded_file($_FILES["upload"]["tmp_name"], $TMP_FOLDER . $filename);
    }
    if ($_POST['ftp_path'] && file_exists($TMP_FOLDER . $_POST['ftp_path'])) {
        $filename = $_POST['ftp_path'];
    }
    if ($filename) {
        # save settings
        $settings = array("nonexistant", "delete_tmp_files", "auto_add_sections", "auto_move_sections", "auto_rename_subdivisions", "auto_change_subdivision_links", "auto_add_goods");
        if (!$netshop->is_netshop_v1_in_use($catalogue_id)) {
            $settings[] = 'root_subdivision_id';
        }
        $qry = array();
        foreach ($settings AS $i) {
            $qry[] = "`" . $i . "` = '" . $db->escape($_POST[$i]) . "'";
        }
        $db->query("UPDATE `Netshop_ImportSources` SET " . join(", ", $qry) . " WHERE `source_id` = '" . $source_id . "'");
    }
}

# get CML version from file
if ($filename && file_exists($TMP_FOLDER . $filename) && !$cml_version)
    $cml_version = nc_netshop_get_cml_version($TMP_FOLDER . $filename);

$sql = "SELECT `Catalogue_ID`, `Catalogue_Name` FROM `Catalogue` ORDER BY `Catalogue_ID`";
$catalogues = (array)$db->get_results($sql, ARRAY_A);

switch (true) {
    # Новый источник
    case!$source_id:
        echo "<h2 style='font-size: 16px;'>" . NETCAT_MODULE_NETSHOP_IMPORT_SOURCE_TITLE . "</h2><br />";
        echo "<div>" . NETCAT_MODULE_NETSHOP_SHOP . ":</div>";
        echo "<div><select name='catalogue_id'>";
        foreach ($catalogues AS $catalogue) {
            echo "<option value='{$catalogue['Catalogue_ID']}'>{$catalogue['Catalogue_ID']}. {$catalogue['Catalogue_Name']}</option>\r\n";
        }
        echo "</select><div><br />";

        $res = $db->get_results("SELECT `source_id` AS id, `name`, `scheme` FROM `Netshop_ImportSources`", ARRAY_A);

        if ($db->num_rows) {

            $js_scheme_rel = array();
            if (!empty($res)) {
                foreach ($res as $value) {
                    $js_scheme_rel[] = "'" . $value['id'] . "':'" . $value['scheme'] . "'";
                }
            }

            echo "<div>" . NETCAT_MODULE_NETSHOP_IMPORT_SOURCE_NAME . ":</div>";
            echo "<div>";
            echo "<script type='text/javascript'>";
            echo "function nc_import_scheme_rel (id) {" .
                "  var scheme_rel = {" . join(",", $js_scheme_rel) . "};" .
                "  var scheme_selector = document.getElementById('cml_version');" .
                "  scheme_selector.selectedIndex = scheme_rel[id];" .
                "}";
            echo "</script>";
            echo "<select name='source_id' onchange='nc_import_scheme_rel(this.value)'>\r\n";
            if (!empty($res)) {
                foreach ($res as $key => $value) {
                    echo "<option value='" . $value['id'] . "'" . ($key == 0 ? " selected" : "") . ">" . $value['name'] . "</option>\r\n";
                }
            }
            echo "</select><div><br />";
        }

        echo "<div>" . NETCAT_MODULE_NETSHOP_IMPORT_COMMERCEML_SCHEME_VER . ":</div><div>";
        $detect_cml_arr = array(
            0 => NETCAT_MODULE_NETSHOP_IMPORT_COMMERCEML_SCHEME_VER_0,
            1 => NETCAT_MODULE_NETSHOP_IMPORT_COMMERCEML_SCHEME_VER_1,
            2 => NETCAT_MODULE_NETSHOP_IMPORT_COMMERCEML_SCHEME_VER_2
        );
        echo "<select name='cml_version' id='cml_version'>\n";
        foreach ($detect_cml_arr as $key => $value) {
            echo "<option value='" . $key . "'" . ($key == $res[0]['scheme'] ? " selected" : "") . ">" . $value . "</option>\n";
        }
        echo "</select>\n";
        echo "</div><br />";

        echo "<div><input type='checkbox' name='new_source' id='cbNew'>";
        echo "<label for='cbNew'>" . NETCAT_MODULE_NETSHOP_IMPORT_SOURCE_NEW . "</label>\r\n";
        echo "</div>";

        echo "<div>
                <input type='text' size='62' name='name' onkeyup=\"document.getElementById('cbNew').checked = (this.value ? true : false);\">
              </div>";
        break;

    # выбор файла для загрузки
    case!$filename:

        $row = $db->get_row("SELECT * FROM `Netshop_ImportSources` WHERE `source_id` = '" . $source_id . "'", ARRAY_A);

        $options = array("disable" => NETCAT_MODULE_NETSHOP_IMPORT_ACTION_NONEXISTANT_DISABLE,
            "ignore" => NETCAT_MODULE_NETSHOP_IMPORT_ACTION_NONEXISTANT_IGNORE); //"delete"=> NETCAT_MODULE_NETSHOP_IMPORT_ACTION_NONEXISTANT_DELETE,

        echo "<b>" . NETCAT_MODULE_NETSHOP_IMPORT_FILE_UPLOAD_TITLE . "</b><br/><br/>\r\n";
        echo "<table cellpadding='5' cellspacing='1' style='width:auto; '>\r\n";
        echo "<tr><td>\r\n" . NETCAT_MODULE_NETSHOP_IMPORT_FILE . "\r\n</td>\r\n</tr>\r\n";
        echo "<tr><td>\r\n<input type='file' id='netshop_xml_import_upload' name='upload' size='50'>\r\n</td>\r\n</tr>\r\n";
        echo "<tr><td>\r\n" . NETCAT_MODULE_NETSHOP_IMPORT_FILE_FTP_PATH . "\r\n</td>\r\n</tr>\r\n";
        echo "<tr><td>\r\n<input type='text' name='ftp_path' size='62' onkeyup=\"document.getElementById('netshop_xml_import_upload').disabled = (this.value ? true : false); document.getElementById('netshop_xml_import_upload').value = (this.value ? '' : document.getElementById('netshop_xml_import_upload').value);\">\r\n</td>\r\n</tr>\r\n";
        if (!$netshop->is_netshop_v1_in_use()) {
            echo "<tr><td>\r\n" . NETCAT_MODULE_NETSHOP_IMPORT_ROOT_SUBDIVISION . "\r\n</td>\r\n</tr>\r\n";
            echo "<tr><td>\r\n<input type='text' name='root_subdivision_id' value='{$row['root_subdivision_id']}' size='5'>\r\n</td>\r\n</tr>\r\n";
        }
        echo "<tr><td>\r\n" . NETCAT_MODULE_NETSHOP_IMPORT_ACTION_NONEXISTANT . "\r\n</td>\r\n</tr>\r\n";
        echo "<tr><td>\r\n<select name='nonexistant'>\r\n";
        foreach ($options AS $option => $text) {
            echo "<option value='" . $option . "'" . ($row["nonexistant"] == $option ? " selected" : "") . ">" . htmlspecialchars($text) . "</option>\r\n";
        }
        echo "</select>\r\n</td>\r\n</tr>\r\n";
        echo "<tr><td>" . NETCAT_MODULE_NETSHOP_IMPORT_AUTO_ADD_SECTIONS . "<br>";
        echo "<select name='auto_add_sections'>";
        echo "<option value='0'>" . NETCAT_MODULE_NETSHOP_IMPORT_AUTO_ADD_SECTIONS_DONT_ADD . "</option>";
        $components = array();
        if ($netshop->is_netshop_v1_in_use()) {
            $MODULE_VARS = $nc_core->modules->get_module_vars();
            foreach (explode(',', $MODULE_VARS['netshop']['GOODS_TABLE']) as $component) {
                $component = (int)trim($component);
                if ($component) {
                    $components[] = $component;
                }
            }
        } else {
            $components = $netshop->get_goods_components_ids();
        }

        foreach ($components as $component) {
            $class_name = $nc_core->component->get_by_id($component, 'Class_Name');
            echo "<option value='{$component}'" . ($row['auto_add_sections'] == $component ? "selected='selected'" : "") . ">[{$component}] {$class_name}</option>";
        }
        echo "</select>";
        echo "<tr><td><input type='checkbox' name='auto_move_sections' value='1' id='auto_move_sections' " . ($row['auto_move_sections'] ? "checked='checked'" : "") . "> <label for='auto_move_sections'>" . NETCAT_MODULE_NETSHOP_IMPORT_AUTO_MOVE_SECTIONS . "</label></td>\r\n</tr>\r\n";
        echo "<tr><td><input type='checkbox' name='delete_tmp_files' value='1' id='delete_tmp_files' " . ($row['delete_tmp_files'] ? "checked='checked'" : "") . "> <label for='delete_tmp_files'>" . NETCAT_MODULE_NETSHOP_IMPORT_DELETE_TMP_FILES . "</label></td>\r\n</tr>\r\n";
        echo "<tr><td><input type='checkbox' name='auto_rename_subdivisions' value='1' id='auto_rename_subdivisions' " . ($row['auto_rename_subdivisions'] ? "checked='checked'" : "") . "> <label for='auto_rename_subdivisions'>" . NETCAT_MODULE_NETSHOP_IMPORT_AUTO_RENAME_SUBDIVISIONS . "</label></td>\r\n</tr>\r\n";
        echo "<tr><td><input type='checkbox' name='auto_change_subdivision_links' value='1' id='auto_change_subdivision_links' " . ($row['auto_change_subdivision_links'] ? "checked='checked'" : "") . "> <label for='auto_change_subdivision_links'>" . NETCAT_MODULE_NETSHOP_IMPORT_AUTO_CHANGE_SUBDIVISION_LINKS . "</label></td>\r\n</tr>\r\n";
        echo "</table>\r\n";

        break;

    # source
    default:
        require("import/commerceml" . ($cml_version == 2 ? "2" : "") . ".php");
}

# hidden save
$save = array("catalogue_id", "source_id", "filename", "current_num", "cml_version");

foreach ($save AS $i) {
    if ($$i)
        echo "<input type='hidden' name='" . $i . "' value='" . htmlspecialchars($$i) . "'>\r\n";
}

$UI_CONFIG->actionButtons[] = array("id" => "submit",
    "caption" => NETCAT_MODULE_NETSHOP_NEXT,
    "action" => "mainView.submitIframeFormWithJson()");

echo "</form>";

EndHtml();