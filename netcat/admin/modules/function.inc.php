<?

###############################################################

function UninstallationAborted() {
    global $TMP_FOLDER;

    print TOOLS_MODULES_ERR_UNINSTALL.".<br>\n";
    DeleteFilesInDirectory($TMP_FOLDER);
    EndHtml ();
    exit;
}

###############################################################

function CheckFiles() {
    global $TMP_FOLDER;

    $files = array("id.txt", "sql.txt", "sql_int.txt", "files.txt", "parameters.txt",
            "index.php", "install.php", "function.inc.php", "en.lang.php", "ru.lang.php");
    $files[] = ( MAIN_LANG != "ru" ) ? "message_int.txt" : "message.txt";

    foreach ($files as $v) {
        if (!is_readable($TMP_FOLDER.$v)) {
            print TOOLS_MODULES_ERR_CANTOPEN." ".$v.".<br />\n";
            InstallationAborted ();
        }
    }
}

###############################################################

/**
 * Вывод списка модулей
 *
 */
function ModuleList() {
    global $nc_core, $db, $UI_CONFIG;
    global $DOMAIN_NAME, $MODULE_FOLDER, $SUB_FOLDER, $HTTP_ROOT_PATH, $ADMIN_TEMPLATE;
    // загрузка всех модулей
    $nc_core->modules->load_env($language, false, true, true);

    if (($modules = $nc_core->modules->get_data(1, 1))) {

        $res = "<form name='adminForm' id='adminForm' class='nc-form' method='post'>";
        $res .= "<table border='0' cellpadding='0' cellspacing='0' width='100%'><tr><td>
              <table width='100%' class='admin_table'>
                <tr>
                  <th></th>
                  <th width='90%'>".TOOLS_MODULES_MOD_NAME."</th>
                  <th align='center'>".TOOLS_MODULES_MOD_PREFS."</th>
                  <th align='center'>".NETCAT_MODULE_ALWAYS_LOAD."</th>
                  <th align='center'>".NETCAT_MODULE_ONOFF."</th>
                </tr>";

        // проход по каждому модулю
        foreach ($modules as $module) {
            $keyword = $module['Keyword'];
            $name = constant($module['Module_Name']);
            $title = $name;
            $res .= "<tr><td><i class='nc-icon nc--mod-{$keyword}";
            if (!$module['Checked']) {
                $res .= " nc--disabled";
                $name = "<font class='nc-text-grey'>$name</font";
            }
            $res .= "' title='$title'></i></td>";
            $res .= "<td>".(file_exists($nc_core->MODULE_FOLDER.$keyword."/admin.php") && $module['Checked'] ?
                            "<a href=".$nc_core->SUB_FOLDER.$nc_core->HTTP_ROOT_PATH."modules/".$keyword."/admin.php>" : "").$name.(file_exists($nc_core->MODULE_FOLDER.$keyword."/admin.php") ? "</a>" : "")."</td>".
                    (( file_exists($MODULE_FOLDER.$keyword."/setup.php") && (!$module["Installed"])) ?
                            "<a href=".$SUB_FOLDER.$HTTP_ROOT_PATH."modules/".$keyword."/setup.php><font size=-2 color=cc3300 ><b>".TOOLS_MODULES_MOD_GOINSTALL." &raquo;</a></td>" : "").
                    "</td>
               </td>";
            $res .= "<td align=center><a href=\"index.php?phase=2&&module_name=".$keyword."\"><div class='icons icon_settings".( $module['Checked'] ? "" : " icon_disabled")."' title='".TOOLS_MODULES_MOD_EDIT."'></div></a></td>";
            $res .= "<td align=center>".nc_admin_checkbox_simple("inside_admin".$module['Module_ID'], 1, '', $module['Inside_Admin'])."</td>";
            $res .= "<td align=center>".nc_admin_checkbox_simple("check".$module['Module_ID'], 1, '', $module['Checked'])."</td>";
            $res .= "</tr>";
        }

        echo $res;
        echo "</table></td></tr></table>";
        echo $nc_core->token->get_input();
        echo "<input type='hidden' name='phase' value='6'></form>";
    }

    //кнопка "Сохранить изменения"
    $UI_CONFIG->actionButtons[] = array("id" => "submit",
            "caption" => NETCAT_MODERATION_BUTTON_CHANGE,
            "action" => "mainView.submitIframeForm()");
}

###############################################################

function ModuleUpdateForm($ModuleID) {
    global $db, $MODULE_FOLDER, $nc_core;
    global $ADMIN_PATH, $ADMIN_TEMPLATE;
    $ModuleID = intval($ModuleID);
    $Array = $db->get_row("SELECT * FROM `Module` WHERE `Module_ID` = '".$ModuleID."'", ARRAY_A);

    if (!$Array['Checked']) {
        print NETCAT_MODULE_MODULE_UNCHECKED;
        return;
    }

    $keyword = $Array["Keyword"];

    if ($Array["Keyword"] != 'default') {
        if (file_exists($MODULE_FOLDER.$keyword."/".MAIN_LANG.".lang.php")) {
            require_once($MODULE_FOLDER.$keyword."/".MAIN_LANG.".lang.php");
        } else {
            require_once($MODULE_FOLDER.$keyword."/en.lang.php");
        }
    }
?>
    <form method='post' action='index.php'>
        <table class='admin_table' style='width:100%;' id='tableParam'>
            <col style='width:35%'/><col style='width:60%'/><col style='width:5%'/>
            <tbody>
                <tr>
                    <th class='align-center first_col'><?=NETCAT_MODULES_PARAM ?></th>
                    <th class='align-center' ><?=NETCAT_MODULES_VALUE ?></th>
                    <td class='align-center last_col'><div class='icons icon_delete' title='<?=CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_DELETE ?>'></div></td>
                </tr>

                <?php
                $ParamArray = ConvertToDiffer($Array["Parameters"]);

                foreach ($ParamArray as $k => $v) {
                    print "<tr>\n";
                    print " <td class='first_col'>".nc_admin_input_simple("Name_".$k."' style = 'width:100%; font-family: \"Courier New\", Courier, monospace'", $k)."</td>\n";
                    print " <td>".nc_admin_input_simple("Value_".$k."' style = 'width:100%; font-family: \"Courier New\",Courier,monospace'", $v)."</td>\n";
                    print " <td class='last_col'>".nc_admin_checkbox_simple("Delete_".$k)."</td>\n";
                    print "</tr>\n";
                }
                print "</tbody></table>\n";
                print "<input type='hidden' name='ModuleID' value=".$ModuleID.">\n";
                print "<input type='hidden' name='phase' value='3'>";
                print $nc_core->token->get_input();

                global $UI_CONFIG, $module_name;
                print "<input type='hidden' name='module_name' value='".$module_name."'></form>";

                $UI_CONFIG = new ui_config_module($module_name, 'settings');
                $UI_CONFIG->actionButtons[] = array("id" => "submit",
                        "caption" => CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_SAVE,
                        "action" => "mainView.submitIframeForm()");
                $UI_CONFIG->actionButtons[] = array("id" => "addparam",
                        "caption" => NETCAT_MODULES_ADDPARAM,
                        "align" => "left",
                        "action" => "document.getElementById('mainViewIframe').contentWindow.ModulesAddNewParam()");
            }

###############################################################

            function AddModuleForm() {
                $maxfilesize = ini_get('upload_max_filesize');
                global $db, $UI_CONFIG, $nc_core, $maxfilesize;

                echo "<form enctype='multipart/form-data' action='index.php' method='POST''>";
                echo "<input type='hidden' name='MAX_FILE_SIZE' value='".$maxfilesize."'>";
                echo "<fieldset>";
                echo "<legend>".TOOLS_MODULES_MOD_LOCAL."</legend>";
                echo "<input size='60' name='FilePatch' type='file'>";
                echo "</fieldset>";
                echo "<input type='hidden' name='phase' value='4'>";
                echo "<input type='submit' class='hidden'>";
                echo $nc_core->token->get_input();
                echo "</form>";
                $UI_CONFIG->actionButtons[] = array("id" => "submit",
                        "caption" => CONTROL_CLASS_IMPORT_UPLOAD,
                        "action" => "mainView.submitIframeForm()");
            }

            /**
             * Функция сохранения настроек модуля
             *
             * @return boolean true
             */
            function ModuleUpdateCompleted() {
                global $db;

                reset($_POST);
                $Parameters = "";

                while (list($key, $val) = each($_POST)) {
                    $key = trim($key);
                    $val = trim($val);

                    if (substr($key, 0, 4) == "Name") {
                        if ($add && $tmpParam) $Parameters .= $tmpParam;
                        $add = 1;
                        $tmpParam = $val;
                        $tmpParam .= "=";
                        if (!$val) $add = 0;
                    }
                    else if (substr($key, 0, 5) == "Value") {
                        $tmpParam .= $val;
                        $tmpParam .= "\n";
                    } else if (substr($key, 0, 6) == "Delete") {
                        $add = 0;
                    }
                }
                if ($add && $tmpParam) $Parameters .= $tmpParam;

                $ModuleID = intval($_POST['ModuleID']);

                $db->last_error = "";
                $db->query("UPDATE Module SET Parameters='".$db->escape($Parameters)."' WHERE Module_ID='".$ModuleID."'");

                if (!$db->last_error) {
                    nc_print_status(TOOLS_MODULES_PREFS_SAVED, 'ok');
                } else {
                    nc_print_status(TOOLS_MODULES_PREFS_ERROR, 'error');
                }

                return true;
            }

            function TitleModule() {
                global $perm;

                if (($perm->isSupervisor() || $perm->isGuest()))
                        AddModuleForm ();
                ModuleList ();
            }

            /**
             * Convert parametr to diffrent parametrs
             * @param str
             * @return array, hash - name of param
             */
            function ConvertToDiffer($parametrs) {
                $ret = Array();
                $parametr = explode("\n", $parametrs);

                foreach ($parametr as $v) {
                    $param_name = trim(strtok($v, "="));
                    $parm_value = trim(strtok(""));
                    if ($param_name && substr($param_name, 0, 1) != '/' && substr($param_name, 0, 1) != '#')
                            $ret[$param_name] = $parm_value;
                }

                return $ret;
            }

                /**
                 * Включение\выключение модулей
                 *
                 * Порядок действий:
                 * - загрузка всех модулей ( даже выключенных )
                 * - определение, какие модули включились, какие выключились
                 * - посылка событий
                 *
                 */
                function ActionModulesCompleted() {
                    global $nc_core, $language;
                    $db = $nc_core->db;

                    $nc_core->modules->load_env($language, false, true, true);
                    $modules = $nc_core->modules->get_data(1, 1);

                    $had_module_status_changes = false;

                    if (!empty($modules)) {
                        foreach ($modules as $module) {
                            // старое и новое значение Checked
                            $old_value = (bool)$module['Checked'];
                            $new_value = (bool)$nc_core->input->fetch_get_post('check'.$module['Module_ID']);
                            if ($old_value != $new_value) {
                                $had_module_status_changes = true;
                                // событие в(ы)ключение модуля
                                $nc_core->event->execute($new_value ? "checkModulePrep" : "uncheckModulePrep", $module['Keyword']);
                                // Обновление в таблице module
                                $db->query("UPDATE `Module` SET `Checked` = '".intval($new_value)."' WHERE `Module_ID` = '".$module['Module_ID']."'");
                                // событие в(ы)ключение модуля
                                $nc_core->event->execute($new_value ? "checkModule" : "uncheckModule", $module['Keyword']);
                            }

                            $inside_admin = (int)$nc_core->input->fetch_get_post('inside_admin'.$module['Module_ID']);
                            $inside_admin = $inside_admin ? 1 : 0;
                            $db->query("UPDATE `Module` SET `Inside_Admin` = {$inside_admin} WHERE `Module_ID` = '".$module['Module_ID']."'");
                        }
                    }
                    return $had_module_status_changes;
                }
