<?php

function CheckForNewPatch() {
    global $LAST_LOCAL_PATCH, $LAST_PATCH, $PATCH_CHECK_DATE, $SYSTEM_ID, $VERSION_ID;
    global $IsInsideAdmin;

    $IsInsideAdmin = 1;
    //LoadSettings();
    $an = new nc_AdminNotice();
    $LAST_PATCH = $an->update(true);
}

function CreatLinks() {
    global $TMP_FOLDER, $DOCUMENT_ROOT, $SUB_FOLDER;

    $File = "symlinks.txt";
    $COPY_FOLDER = $DOCUMENT_ROOT.$SUB_FOLDER;

    # сколько ссылок создно, сколько всего ссылок
    $result = array("links" => 0, "total" => 0);

    $fp = fopen($TMP_FOLDER.$File, "r");

    while (!feof($fp)) {
        $string = chop(fgets($fp, 4096));
        if (strlen($string) == 0) break;
        $From = strtok($string, " ");
        $To = strtok(" ");
        $directory = dirname($To);

        $tmpDirectory = $COPY_FOLDER;
        $tok = strtok($directory, "/");
        while ($tok) {
            $tmpDirectory.= "/".$tok;
            @mkdir($tmpDirectory, 0775);
            $tok = strtok("/");
        }
        # Для Windows-платформ эта функция не реализована.
        $linked = @symlink($COPY_FOLDER."/".$From, $COPY_FOLDER."/".$To);
        if ($linked) $result["links"]++;

        $result["total"]++;
    }
    fclose($fp);

    return $result;
}

/*
 * Функция чтения файла changelog.txt, входящего в состав патча
 * @param string $file_name имя файла с changelog
 * @return string текст файла
 */

function nc_patch_changelog($file_name = "") {
    global $TMP_FOLDER;

    $result = false;
    $changelog_arr = array();
    if (!$file_name) {
        $int_pref = (defined("MAIN_LANG") && MAIN_LANG == "ru" ? "" : "_int");
        $file_name = "changelog".$int_pref.".txt";
    }

    if (!file_exists($TMP_FOLDER.$file_name)) return false;

    $result = file_get_contents($TMP_FOLDER.$file_name);
    $result = htmlspecialchars($result);

    return $result;
}

function nc_patch_requset_data() {
    global $db, $nc_core;

    $sys = $nc_core->get_settings();

    $mod = $db->get_col("SELECT `Keyword` FROM `Module`");

    $response = "<?xml version='1.0' encoding='UTF-8'?>\n".
            "<netcat>\n".
            "<reason>update</reason>\n".
            "<version>\n".
            "<patch>".$db->get_var("SELECT `Patch_Name` FROM `Patch` as `p` ORDER BY p.`Patch_Name` DESC LIMIT 1")."</patch>\n".
            "<name>".$sys['SystemID']."</name>\n".
            "</version>\n".
            "<core>".$sys['ProductNumber']."</core>\n".
            "<code>".$sys['Code']."</code>\n".
            "<unicode>".intval($nc_core->NC_UNICODE)."</unicode>\n".
            "<host>\n".
            "<ip>".$_SERVER["SERVER_ADDR"]."</ip>\n".
            "<url>".$_SERVER["HTTP_HOST"]."</url>\n".
            "</host>\n".
            "<modules>\n";
    if (!empty($mod)) {
        foreach ($mod as $value) {
            $response.=
                    "<module>\n".
                    "<number></number>\n".
                    "<name>".$value."</name>\n".
                    "</module>\n";
        }
    }
    $response.=
            "</modules>\n".
            "</netcat>\n";

    return $response;
}

function nc_patch_get_patch() {
    global $nc_core, $db, $TMP_FOLDER;

    $url = "http://update.netcat.ru/";
    $data = nc_patch_requset_data();
    $result = array();

    $options = array(
            "http" => array(
                    "method" => "POST",
                    "header" => "Content-type: application/x-www-form-urlencoded\n"
                    ."Content-Length: ".strlen($data)."\n",
                    "content" => $data
            )
    );

    $context = stream_context_create($options);

    // get data from update server
    $request = @file_get_contents($url, false, $context);
    // parse requested data
    if ($request) {
        $parser = xml_parser_create();
        xml_parse_into_struct($parser, $request, $values, $indexes);
        xml_parser_free($parser);
    } else {
        nc_print_status(TOOLS_PATCH_ERROR_UPDATE_SERVER_NOT_AVAILABLE, "error");
        return "";
    }
    // check
    if (empty($values)) return "";
    // result flat array
    foreach ($values as $value) {
        if ($value['type'] != "complete") continue;
        $result[$value['tag']] = $value['value'];
    }

    // if operation result (success or failed)
    if ($result['OPERATION'] == "success") {
        if ($result['CODE']) $nc_core->set_settings('Code', $result['CODE']);
    }
    else {
        switch (true) {
            case extension_loaded("mbstring"):
                $comment = mb_convert_encoding($comment, $nc_core->NC_CHARSET, "UTF-8");
                nc_print_status(mb_convert_encoding($result['MESSAGE'], MAIN_ENCODING, "UTF-8"), "error");
                break;
            case extension_loaded("iconv"):
                nc_print_status(iconv("UTF-8", MAIN_ENCODING, $result['MESSAGE']), "error");
                break;
        }
    }

    // get patch file data
    if ($result['LINK']) $update_file = @file_get_contents($result['LINK']);
    // write file on disk
    if ($update_file) {
        // set patch file temp name
        $patch_file_name = "update_".md5(microtime()).".tgz";
        // write data into the file
        if (!is_writable($TMP_FOLDER)) {
            nc_print_status(sprintf(TOOLS_PATCH_ERROR_TMP_FOLDER_NOT_WRITABLE, $TMP_FOLDER), "error");
        }
        elseif (file_put_contents($TMP_FOLDER.$patch_file_name, $update_file)) {
            $result['_FILE'] = $patch_file_name;
            // return file name
            return $result;
        }
    } else {
        nc_print_status(TOOLS_PATCH_ERROR_UPDATE_FILE_NOT_AVAILABLE, "error");
    }

    return "";
}

function nc_patch_check_files_by_list($filepath) {
    global $SUB_FOLDER, $DOCUMENT_ROOT;
    global $DIRCHMOD, $FILECHMOD;

    if (!file_exists($filepath)) return false;

    $files = file($filepath);

    foreach ($files as $file) {
        // file and his dir path
        $file_full_path = $DOCUMENT_ROOT.$SUB_FOLDER.$file;
        $dir_full_path = str_replace(basename($file_full_path), "", $file_full_path);
        // set access mode
        if (is_file($file_full_path)) {
            @chmod($file_full_path, $FILECHMOD);
        } else {
            @chmod($dir_full_path, $DIRCHMOD);
        }
        // check writable
        if (!is_writable($file_full_path)) {
            if (file_exists($file_full_path)) {
                // file exist and not writable
                return false;
            } else {
                // file not exist
                if (is_writable($dir_full_path)) {
                    // dir for new file writable
                    return true;
                } else {
                    // dir for new file not writable
                    return false;
                }
            }
        }
    }

    return true;
}

function PatchList() {
    global $db;

    $result = $db->get_results("SELECT `Patch_Name`, date_format(`Created`, '%d.%m.%Y') AS Time, `Description` FROM `Patch` ORDER BY `Created` ASC", ARRAY_A);

    if ($result) {
?>
        <table border='0' cellpadding='0' cellspacing='0' width='100%'>
            <tr>
                <td>
                    <table class='admin_table' width='100%'>
                        <tr>
                            <td>ID</td>
                            <td><?=TOOLS_PATCH_LIST_DATE ?></td>
                            <td width='60%'><?=CONTROL_FIELD_LIST_DESCRIPTION ?></td>
                        </tr>
<?php
        foreach ($result AS $value) {
?>
                            <tr>
                                <td><?=$value['Patch_Name'] ?></td>
                    <td><?=$value['Time'] ?></td>
                    <td><?=$value['Description']
?></td>
                </tr>
<?php
            }
?>
            </table>
        </td>
    </tr>
</table>
<?php
        }
    }

    function PatchForm() {
        global $db, $UI_CONFIG, $nc_core;
        global $LAST_PATCH, $LAST_LOCAL_PATCH, $PATCH_CHECK_DATE;
        global $SYSTEM_ID, $VERSION_ID;

        // сравниваем версии патчей, де-факто они должны состоять из 3 цифр
        if ((int) sprintf("%0-3s", $LAST_LOCAL_PATCH) >= (int) sprintf("%0-3s", $LAST_PATCH)) {
            nc_print_status(TOOLS_PATCH_MSG_OK, "ok");
            $need_update = "false";
        } else {
            nc_print_status("<b>".TOOLS_PATCH_INFO_NOTINSTALLED." #".$LAST_PATCH."</b> (<a target=_blank href=http://www.netcat.ru/netcat/modules/default/showpatch.php?system=".$SYSTEM_ID."&version=".$VERSION_ID."&lastpatch=".$LAST_LOCAL_PATCH.">".TOOLS_PATCH_INFO_DOWNLOAD."</a>)", 'info');
            $need_update = "true";
        }

        #echo "<script type='text/javascript'>top.updateUpdateIndicator(".$need_update.")</script>";

        echo "<p> ".TOOLS_PATCH_INFO_LASTCHECK." <b>".date("d.m.Y H:i", $PATCH_CHECK_DATE)."</b> (<a href='?phase=3'>".TOOLS_PATCH_INFO_REFRESH."</a>)</p>";
?>
        <form enctype='multipart/form-data' action='index.php' method='post' id='FormAutoPatch'>
            <fieldset>
                <legend><?=TOOLS_PATCH_INSTALL_ONLINE ?></font>
        </legend>
        <div style='margin:10px 5px'>
            <button name='AutoPatch' onclick='if ( confirm("<?=TOOLS_PATCH_INSTALL_ONLINE_NOTIFY
?>") ) document.getElementById("FormAutoPatch").submit(); else return false;'<?=($need_update == "false" ? " disabled" : "")
?>>
        <?=TOOLS_PATCH_INFO_INSTALL
?>
            </button>
        </div>
    </fieldset>
    <input type='hidden' name='phase' value='4'>
    <input type='submit' class='hidden'>
    <?php echo $nc_core->token->get_input(); ?>
</form>

<form enctype='multipart/form-data' action='index.php' method='post'>
    <!--input type='hidden' name='MAX_FILE_SIZE' value='2097152'-->
    <fieldset>
        <legend><?=TOOLS_PATCH_INSTALL_LOCAL ?></legend>
        <div style='margin:10px 5px'>
            <input size='40' name='FilePatch' type='file'>
            <button type='submit'><?=CONTROL_CLASS_IMPORT_UPLOAD ?></button>
        </div>
    </fieldset>
    <br>
    <input type='hidden' name='phase' value='2'>
    <input type='submit' class='hidden'>
    <?php echo $nc_core->token->get_input(); ?>
</form>
<?php
}

# функции для активации

/**
 * Вывод формы ввода данных для активации
 *
 */
function nc_activation_show_form() {
    global $nc_core, $UI_CONFIG;
    // показ формы активации по ссылке (например, когда срок уже истек)
    $not_ia = (isset($_GET['not_ia']) ? $_GET['not_ia'] : $_POST['not_ia']) + 0;

	$system_settings = $nc_core->get_settings();

	$html = '';

    if ($not_ia) {
		$html .= '<style>body {padding: 20px;}</style>';
        $html .= nc_print_status(sprintf(NETCAT_DEMO_NOTICE, $system_settings['VersionNumber']), 'error', null, true);
    }

    $html .= nc_print_status(TOOLS_ACTIVATION_FORM, 'info', null, true);

    // activation form
    $html .= "<form name='adminForm' id='adminForm' class='nc-form' method='post' action='activation.php'>
          <table border='0'>
             <tr><td>".CONTROL_SETTINGSFILE_BASIC_REGCODE.":</td>
                 <td>".nc_admin_input_simple('license', ($_POST['license'] ? $_POST['license'] : $system_settings['ProductNumber']), 8, '', "maxlength='8'")."</td></tr>
             <tr><td>".TOOLS_ACTIVATION_CODE.":</td>
                 <td>".nc_admin_input_simple('activation_code', ($_POST['activation_code'] ? $_POST['activation_code'] : $system_settings['Code']), 28, '', "maxlength='28'")."</td></tr>
           </table>
          <input type='hidden' name='phase' value='2' />
          <input type='hidden' name='not_ia' value='".$not_ia."' />
          <input type='submit' class='hidden' />
          ".($not_ia ? "<tr><td colspan='2' align='center'><input type='submit' value='".TOOLS_ACTIVATION_VERB."' title='".TOOLS_ACTIVATION_VERB."' style='background: #1A87C2; color: #FFF; padding: 5px; border: none;' /></td></tr>" : "")."
          </form><br />";

    $html .= TOOLS_ACTIVATION_DESC;

    echo $html;

    // ui_config
    $UI_CONFIG->actionButtons[] = array(
		"id" => "activation",
		"caption" => TOOLS_ACTIVATION_VERB,
		"action" => "mainView.submitIframeForm()"
	);

    return 0;
}

/**
 * Формирование запроса для активации
 *
 * @return string request
 */
function nc_activation_requset_data() {
    global $nc_core, $db;

    $sys = $nc_core->get_settings();

    $mod = $db->get_col("SELECT `Keyword` FROM `Module`");

    $response = "<?xml version='1.0' encoding='UTF-8'?>\n".
            "<netcat>\n".
            "<reason>activation</reason>\n".
            "<version>\n".
            "<patch>".$db->get_var("SELECT `Patch_Name` FROM `Patch` as `p` ORDER BY p.`Patch_Name` DESC LIMIT 1")."</patch>\n".
            "<name>".$sys['SystemID']."</name>\n".
            "</version>\n".
            "<core>".$_POST['license']."</core>\n".
            "<code>".$_POST['activation_code']."</code>\n".
            "<host>\n".
            "<ip>".$_SERVER["SERVER_ADDR"]."</ip>\n".
            "<url>".$_SERVER["HTTP_HOST"]."</url>\n".
            "</host>\n".
            "<modules>\n";
    if (!empty($mod)) {
        foreach ($mod as $value) {
            $response.=
                    "<module>\n".
                    "<number></number>\n".
                    "<name>".$value."</name>\n".
                    "</module>\n";
        }
    }
    $response.=
            "</modules>\n".
            "</netcat>\n";

    return $response;
}

/**
 * Функция получает файл с активацией
 * потом копирует его в tmp-директорию
 *
 * @return array
 */
function nc_activation_get_files() {
    global $nc_core;

    $db = $nc_core->db;
    $TMP_FOLDER = $nc_core->TMP_FOLDER;
    $url = "http://update.netcat.ru/";

    $data = nc_activation_requset_data();
    $result = array();

    $options = array(
            "http" => array(
                    "method" => "POST",
                    "header" => "Content-type: application/x-www-form-urlencoded\n"
                    ."Content-Length: ".strlen($data)."\n",
                    "content" => $data
            )
    );

    $context = stream_context_create($options);

    // get data from update server
    $request = @file_get_contents($url, false, $context);

    // parse requested data
    if ($request) {
        $parser = xml_parser_create();
        xml_parse_into_struct($parser, $request, $values, $indexes);
        xml_parser_free($parser);
    } else {
        nc_print_status(TOOLS_PATCH_ERROR_UPDATE_SERVER_NOT_AVAILABLE, "error");
        return "";
    }
    // check
    if (empty($values)) {
        return "";
    }
    // result flat array
    foreach ($values as $value) {
        if ($value['type'] != "complete") continue;
        $result[$value['tag']] = $value['value'];
    }

    if ($result['OPERATION'] != "success") {
        nc_print_status(TOOLS_ACTIVATION_INVALID_KEY_CODE, 'error');
        nc_activation_show_form();
        return "";
    }

    // get patch file data
    if ($result['LINK']) $update_file = @file_get_contents($result['LINK']);

    // write file on disk
    if ($update_file) {
        // set patch file temp name
        $patch_file_name = "update_".md5(microtime()).".tgz";
        // write data into the file
        if (!is_writable($TMP_FOLDER)) {
            nc_print_status(sprintf(TOOLS_PATCH_ERROR_TMP_FOLDER_NOT_WRITABLE, $TMP_FOLDER), "error");
        }
        elseif (file_put_contents($TMP_FOLDER.$patch_file_name, $update_file)) {
            $result['_FILE'] = $patch_file_name;
            // return file name
            return $result;
        }

    } else {
        nc_print_status(TOOLS_PATCH_ERROR_UPDATE_FILE_NOT_AVAILABLE, "error");
    }

    return "";
}
?>