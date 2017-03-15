<?php
/* $Id: function.inc.php 8397 2012-11-12 10:02:18Z vadim $ */

if (!class_exists("nc_System"))
    die("Unable to load file.");
$systemMessageID = $TemplateID;
$systemTableName = "Template";
$systemTableID = GetSystemTableID($systemTableName);

function ConvertForm($phase = '1', $source = '') {
    if ($source) {
        $source = stripslashes($source);
        $source = str_replace("\\", "\\\\", $source);
        $source = str_replace("\$", "\\$", $source);
        $source = addcslashes($source, chr(34));
    }
    print "
    <form method=post action='converter.php'>
      <table width='100%' height='100%' cellpadding='5' cellspacing='0' border='0'>
        <tr>
          <td height='" . ($phase == '2' ? "35%" : "95%") . "'>" . nc_admin_textarea_simple('source', stripslashes($source), '', 0, 0, "style='height: 160px;'") . "</td>
        </tr>";

    print "
        <tr>
          <td height='5%'>
            <input type='hidden' name='phase' value='2'>
            <input type=submit value='" . constant('CONTROL_TEMPLATE_CLASSIFICATOR_EKRAN') . "'>
          </td>
        </tr>";

    if ($phase == '2') {
        print "
        <tr>
          <td height='60%'>
            " . nc_admin_textarea_simple('', $source, constant('CONTROL_TEMPLATE_CLASSIFICATOR_RES') . ":<br>", 0, 0, "style='height: 160px;'") . "
          </td>
        </tr>";
    }
    print "</table>
		<input type='submit' class='hidden'>
  	   </form>";
}


/**
 * Функция рисует форму добавления макета дизайна
 *
 * @param unknown_type $TemplateID
 * @param unknown_type $phase
 * @param unknown_type $type
 */
function TemplateForm($TemplateID, $phase, $type, $File_Mode, $refresh = false) {
    # type = 1 - это insert
    # type = 2 - это update

    global $ROOT_FOLDER, $HTTP_FILES_PATH, $DOMAIN_NAME;
    global $systemTableID, $systemMessageID, $systemTableName;
    global $ParentTemplateID, $admin_mode;
    global $INCLUDE_FOLDER;
    global $UI_CONFIG, $ADMIN_PATH;

    $nc_core = nc_Core::get_object();
    $db = $nc_core->db;

    if ($File_Mode) {
        $template_editor = new nc_template_editor($nc_core->TEMPLATE_FOLDER, $nc_core->db);
    }

    $TemplateID = +$TemplateID;

    $params = array('Description', 'Settings', 'Header', 'Footer', 'CustomSettings', 'ParentTemplateID');

    foreach ($params as $v) {
        global $$v;
    }

    $st = new nc_Component(0, 4);
    foreach ($st->get_fields(0, 0) as $v) {
        $v = 'f_' . $v;
        $$v = $nc_core->input->fetch_get_post($v);
    }

    require_once ($INCLUDE_FOLDER . "s_files.inc.php");
    $is_there_any_files = getFileCount(0, $systemTableID);

    if ($type == 1) {
        $UI_CONFIG = new ui_config_template('add', $TemplateID);
        $Array['Description'] = stripslashes($Description);
        $Array['Settings'] = stripslashes($Settings);
        $Array['Header'] = stripslashes($Header);
        $Array['Footer'] = stripslashes($Footer);
        $Array['CustomSettings'] = stripslashes($CustomSettings);
    } else if ($type == 2) {
        $UI_CONFIG = new ui_config_template('edit', $TemplateID);
        $SQL = "select Description,
                                      Settings,
                                      Header,
                                      Footer,
                                      CustomSettings,
                                      File_Hash
                                   from Template
                                       where Template_ID = " . $TemplateID;
        $Array = $db->get_row($SQL, ARRAY_A);
    }

    if ($File_Mode && $phase != 3) {
        $template_editor->load_template($TemplateID, null, $Array['File_Hash']);

        $template_absolute_path = $template_editor->get_absolute_path();
        $template_filemanager_link = $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . "modules/filemanager/admin.php?page=manager&phase=1&dir=" . $nc_core->SUB_FOLDER . $nc_core->HTTP_TEMPLATE_PATH . 'template' . $template_editor->get_relative_path();

        $template_editor->fill_fields();
        $new_template = $template_editor->get_standart_fields();
        $Array = array_merge($Array, $new_template);
    }

    if ($type == 1 && !$Array['Settings'] && $File_Mode) {
        $Array['Settings'] = "<?php\n\n\n?>";
    }

    if (!$File_Mode) {
        echo "<br /><font color=gray>" . CONTROL_TEMPLATE_INFO_CONVERT . "</font>";
    }

    $set = $nc_core->get_settings();

    if ($TemplateID && $refresh) {
        ?>

        <script>
            parent.window.frames[0].window.location.href += '&selected_node=template-<?= $TemplateID; ?>';
        </script>
        <?
    }
    if($set['CMEmbeded']) {
            ?>
            <div id="templateFields" class="completionData" style="display:none"></div>
            <script>
               $nc('#templateFields').data('completionData', $nc.parseJSON("<?=addslashes(json_safe_encode(getCompletionDataForTemplateFields($systemTableID)))?>"));
            </script>
            <?
    }
    ?>

    <form id='TemplateForm' <?= $is_there_any_files ? "enctype=multipart/form-data" : "" ?> method=post action="index.php">
        <?= $File_Mode ? "<input type='hidden' name='fs' value='1'>" : "" ?>
        <br />
        <? if ($File_Mode && $phase != 3): ?>
        <div><?= sprintf(CONTROL_TEMPLATE_FILES_PATH, $template_filemanager_link, $template_absolute_path) ?></div>
        <? endif; ?>
        <br />
        <font color=gray><?= CONTROL_TEMPLATE_TEPL_NAME ?>:<br>
        <?= nc_admin_input_simple('Description', $Array["Description"], 50, '', "maxlength='64'") ?>
        <br><br>
        <?= nc_admin_textarea_resize('Settings', $Array["Settings"], CONTROL_TEMPLATE_TEPL_MENU . ':', 12, 60, "Settings"); ?>
        <br><br>
        <?= nc_admin_textarea_resize('Header', $Array["Header"], CONTROL_TEMPLATE_TEPL_HEADER . ':', 20, 60, "TemplateHeader"); ?>
        <br><br>
        <?= nc_admin_textarea_resize('Footer', $Array["Footer"], CONTROL_TEMPLATE_TEPL_FOOTER . ':', 20, 60, "TemplateFooter"); ?>
        <br><br>

        <div style='display: none'>
            <?= nc_admin_textarea_resize('CustomSettings', $Array["CustomSettings"], '', 8, 60, "CustomSettings"); ?>
        </div>
        <?
        if ($type == 1) {
            $action = "add";
        }
        if ($type == 2) {
            $action = "change";
            $message = $TemplateID;
        }

        require $ROOT_FOLDER . "message_fields.php";

        if ($fldCount) {
            if ($type == 2) {
                $fieldQuery = '`' . join($fld, "`,`") . '`';
				$fldValue = $db->get_row("SELECT " . $fieldQuery . " FROM `Template` WHERE `Template_ID` = '" . $systemMessageID . "'", ARRAY_N);
            }
?>
            <br />
            <legend><a href=<?= "" . $ADMIN_PATH . "field/index.php?isSys=1&amp;fs=$File_Mode&amp;Id=" . $systemTableID ?>><?= CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_EXTFIELDS ?></a></legend>
            <table border=0 cellpadding=6 cellspacing=0 width=100%>
                <tr>
                    <td><font color=gray>
                        <?
                        require $ROOT_FOLDER . "message_edit.php";
                        ?>
                    </td>
                </tr>
            </table>
            <br>
            <?
        } else {
            echo "
     <hr size=1 color=cccccc>";
        }

        echo "
 <div align=right>";
        if ($type == 1) {
            $UI_CONFIG->actionButtons[] = array(
                    "id" => "submit",
                    "caption" => CONTROL_TEMPLATE_TEPL_CREATE,
                    "action" => "mainView.submitIframeForm()");
        } else if ($type == 2) {
            $UI_CONFIG->actionButtons[] = array(
                    "id" => "submit",
                    "caption" => CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_SAVE,
                    "action" => 'return false;" id="nc_class_save');//"mainView.submitIframeForm()");
            global $system_env;
            if ($system_env['SyntaxCheck']) {
                $UI_CONFIG->actionButtons[] = array(
                        "id" => "debug",
                        "caption" => NETCAT_DEBUG_BUTTON_CAPTION,
                        "action" => "document.getElementById('mainViewIframe').contentWindow.FormAsyncDebug()"
                );
            }
            $UI_CONFIG->actionButtons[] = array(
                    "id" => "preview",
                    "caption" => NETCAT_PREVIEW_BUTTON_CAPTIONTEMPLATE,
                    "align" => "left",
                    "action" => "document.getElementById('mainViewIframe').contentWindow.SendTemplatePreview('','../../index.php')"
            );
        }
        echo "
 </div>
 <INPUT TYPE=hidden NAME=posting VALUE=1>
 <INPUT TYPE=hidden NAME=type VALUE=" . $type . ">
 <input type=hidden name=phase value=" . $phase . ">
 <input type=hidden name=TemplateID value=" . $TemplateID . ">
 <input type=hidden name=ParentTemplateID value=" . $ParentTemplateID . ">
 <input type='submit' class='hidden'>
 " . $nc_core->token->get_input();
    if ($nc_core->get_settings('TextareaResize')) {
        echo '<script type="text/javascript">bindTextareaResizeButtons();</script>';
    }
    $UI_CONFIG->remind[] = 'remind_template_edit';
 echo "</form>
 <a href='" . $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . "action.php?ctrl=admin.backup&amp;action=export_run&amp;raw=1&amp;type=template&amp;id=" . $TemplateID . "&amp;" . $nc_core->token->get_url() . "'>" . CONTROL_TEMPLATE_EXPORT . "</a>";

        nc_print_admin_save_script('TemplateForm');

}


/**
 * Функция рисует форму редактирования макета дизайна через fron-end
 *
 * @param unknown_type $TemplateID
 * @param unknown_type $File_Mode
 */
function TemplateForm_for_modal($TemplateID, $File_Mode) {
	global $ROOT_FOLDER, $HTTP_FILES_PATH, $DOMAIN_NAME;
	global $systemTableID, $systemMessageID, $systemTableName;
	global $ParentTemplateID, $admin_mode;
	global $INCLUDE_FOLDER;
	global $UI_CONFIG, $ADMIN_PATH;

	$nc_core = nc_Core::get_object();
	$db = $nc_core->db;

	if ($File_Mode) {
		$template_editor = new nc_template_editor($nc_core->TEMPLATE_FOLDER, $nc_core->db);
	}

	$TemplateID = intval($TemplateID);

	$params = array('Description', 'Settings', 'Header', 'Footer', 'CustomSettings', 'ParentTemplateID');

	foreach ($params as $v) {
		global $$v;
	}

	$st = new nc_Component(0, 4);
	foreach ($st->get_fields(0, 0) as $v) {
		$v = 'f_' . $v;
		$$v = $nc_core->input->fetch_get_post($v);
	}

	require_once ($INCLUDE_FOLDER . "s_files.inc.php");
	$is_there_any_files = getFileCount(0, $systemTableID);

	$SQL = "SELECT `Description`, `Settings`, `Header`, `Footer`, `CustomSettings`
		FROM `Template`
		WHERE `Template_ID` = " . $TemplateID;
	$Array = $db->get_row($SQL, ARRAY_A);

	if ($File_Mode) {
		$template_editor->load_template($TemplateID);
		$template_editor->fill_fields();
		$new_template = $template_editor->get_standart_fields();
		$Array = array_merge($Array, $new_template);
	}

	if ($GLOBALS["AJAX_SAVER"]): ?>
		<script>
			var formAsyncSaveEnabled = true;
			var NETCAT_HTTP_REQUEST_SAVING = "<?=str_replace('"', "&quot;", NETCAT_HTTP_REQUEST_SAVING) ?>";
			var NETCAT_HTTP_REQUEST_SAVED  = "<?=str_replace('"', "&quot;", NETCAT_HTTP_REQUEST_SAVED) ?>";
			var NETCAT_HTTP_REQUEST_ERROR  = "<?=str_replace('"', "&quot;", NETCAT_HTTP_REQUEST_ERROR) ?>";
		</script>
	<? else: ?>
		<script>var formAsyncSaveEnabled = false;</script>
	<? endif ?>

	<div class='nc_admin_form_menu' style='padding-top: 20px;'>
		<h2><?= CONTROL_TEMPLATE_EDIT ?></h2>
		<div id='nc_object_slider_menu' class='slider_block_2' style='padding-top: 0px; padding-bottom: 15px;'>
			<ul>
				<li id='nc_template_form_edit' class=''></li>
			</ul>
		</div>
		<div class='nc_admin_form_menu_hr'></div>
	</div>

	<div class='nc_admin_form_body nc-admin'>
		<form id='adminForm' class='TemplateForm nc-form' <?= $is_there_any_files ? "enctype=multipart/form-data" : "" ?> method=post action='<?= $nc_core->ADMIN_PATH; ?>template/index.php'>
			<input type='hidden' name='fs' value='<?= $File_Mode; ?>'>
			<div>
				<div>
					<div>
						<?= CONTROL_TEMPLATE_TEPL_NAME; ?>:
					</div>
					<div>
						<?= nc_admin_input_simple('Description', $Array["Description"], 50, '', "maxlength='64'"); ?>
					</div>
				</div>
				<br />
				<div>
					<?= nc_admin_textarea_simple('Settings', $Array["Settings"], CONTROL_TEMPLATE_TEPL_MENU . ':', 12, 60, "Settings"); ?>
				</div>
				<br />
				<div>
					<?= nc_admin_textarea_simple('Header', $Array["Header"], CONTROL_TEMPLATE_TEPL_HEADER . ':', 20, 60, "TemplateHeader"); ?>
				</div>
				<br />
				<div>
					<?= nc_admin_textarea_simple('Footer', $Array["Footer"], CONTROL_TEMPLATE_TEPL_FOOTER . ':', 20, 60, "TemplateFooter"); ?>
				</div>
				<br />
				<div id='cstOff' style='cursor: pointer;' onclick='this.style.display="none"; document.getElementById("cstOn").style.display="";'>
					<font color='gray'> &#x25BA; <?= CONTROL_TEMPLATE_CUSTOM_SETTINGS; ?></font>
				</div>
				<div id='cstOn' style='display: none'>
					<font color='gray' style='cursor: pointer;' onclick='document.getElementById("cstOn").style.display="none";document.getElementById("cstOff").style.display="";'> &#x25BC;
						<?= CONTROL_TEMPLATE_CUSTOM_SETTINGS; ?>
					</font>
					<?= nc_admin_textarea_simple('CustomSettings', $Array["CustomSettings"], '', 8, 60, "CustomSettings"); ?>
				</div>
			</div>
<?php
	$action = "change";
	$message = $TemplateID;

	require $ROOT_FOLDER . "message_fields.php";

	if ($fldCount):
		$fieldQuery = '`' . join($fld, "`,`") . '`';
		$fldValue = $db->get_row("SELECT " . $fieldQuery . " FROM `Template` WHERE `Template_ID` = '" . $systemMessageID . "'", ARRAY_N);
?>
			<br />
<?php /*
				<a href=<?= "" . $ADMIN_PATH . "field/index.php?isSys=1&amp;Id=" . $systemTableID ?>><font color=gray><b><?= CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_EXTFIELDS ?></b></font></a>
*/ ?>
			<table border='0' cellpadding='6' cellspacing='0' width='100%'>
				<tr>
					<td><font color='gray'>
						<?php require $ROOT_FOLDER . "message_edit.php"; ?>
					</td>
				</tr>
			</table>
			<br />
<?php
	endif;
?>
			<input type='hidden' name='posting' value='1' />
			<input type='hidden' name='isNaked' value='1' />
			<input type='hidden' name='type' value='2' />
			<input type='hidden' name='phase' value='5' />
			<input type='hidden' name='TemplateID' value='<?= $TemplateID ?>' />
			<input type='hidden' name='ParentTemplateID' value='<?= $ParentTemplateID ?>' />
			<?= $nc_core->token->get_input(); ?>
            <?php if ($nc_core->get_settings('TextareaResize')) { ?>
            <script type="text/javascript">bindTextareaResizeButtons();</script>
            <?php } ?>
		</form>
<?php
 	echo include_cd_files();
?>

	</div>

	<div class='nc_admin_form_buttons'>
        <button type='button' class='nc_admin_metro_button nc-btn nc--blue' disable><?= NETCAT_REMIND_SAVE_SAVE; ?></button>
        <button type='button' class='nc_admin_metro_button_cancel nc-btn nc--red nc--bordered nc--right'><?= CONTROL_BUTTON_CANCEL ?></button>
	</div>

	<style>
		a { color:#1a87c2; }
		a:hover { text-decoration:none; }
		a img { border:none; }
		p { margin:0px; padding:0px 0px 18px 0px; }
		h2 { font-size:20px; font-family:'Segoe UI', SegoeWP, Arial; color:#333333; font-weight:normal; margin:0px; padding:20px 0px 10px 0px; line-height:20px; }
		form { margin:0px; padding:0px; }
		input { outline:none; }
		.clear { margin:0px; padding:0px; font-size:0px; line-height:0px; height:1px; clear:both; float:none; }
		select, input, textarea { border:1px solid #dddddd; }
		:focus { outline:none;}
		.input { outline:none; border:1px solid #dddddd; }
	</style>

	<script>
		var nc_admin_metro_buttons = $nc('.nc_admin_metro_button');
		$nc(function() {
			$nc('#adminForm').html('<div class="nc_admin_form_main">' + $nc('#adminForm').html() + '</div>');
		});
		nc_admin_metro_buttons.click(function() {
			$nc('#adminForm').submit();
		});
		$nc('.nc_admin_metro_button_cancel').click(function() {
			$nc.modal.close();
		});
	</script>
<?php
}

/**
 * Функция выводит скрипт с конфигурацией асинхронного сохранения
 */
function FormAsyncSaveConfigScript() {
    echo "<script type='text/javascript'>\n";
    echo "var formAsyncSaveEnabled = true;\n";
    echo "var NETCAT_HTTP_REQUEST_SAVING = \"" . str_replace('"', "&quot;", NETCAT_HTTP_REQUEST_SAVING) . "\";\n";
    echo "var NETCAT_HTTP_REQUEST_SAVED  = \"" . str_replace('"', "&quot;", NETCAT_HTTP_REQUEST_SAVED) . "\";\n";
    echo "var NETCAT_HTTP_REQUEST_ERROR  = \"" . str_replace('"', "&quot;", NETCAT_HTTP_REQUEST_ERROR) . "\";\n";
    echo "</script>";
}

###############################################################################

function ActionTemplateCompleted($type, $File_Mode) {
	global $nc_core, $db, $ROOT_FOLDER, $FILES_FOLDER;
	global $systemTableID, $systemTableName, $systemMessageID;
	global $loc, $perm, $admin_mode;
	global $INCLUDE_FOLDER;
	global $FILECHMOD, $DIRCHMOD;

	if ($File_Mode) {
		$template_editor = new nc_template_editor($nc_core->TEMPLATE_FOLDER, $nc_core->db);
	}

	require_once ($INCLUDE_FOLDER . "s_files.inc.php");
	$is_there_any_files = getFileCount(0, $systemTableID);

	$params = array('TemplateID', 'ParentTemplateID', 'Description', 'Settings', 'Header', 'Footer', 'CustomSettings', 'posting');
	foreach ($params as $v)
		global $$v;
	$st = new nc_Component(0, 4);
	foreach ($st->get_fields() as $v) {
		$name = 'f_' . $v['name'];
		global $$name;
		if ($v['type'] == 6) {
			global ${$name . "_old"};
			global ${"f_KILL" . $v['id']};
		}
	}

	$action = ($type == 1) ? "add" : "change";

	$message = $TemplateID;

	require $ROOT_FOLDER . "message_fields.php";
	require $ROOT_FOLDER . "message_put.php";

	//  ADD template
	if ($type == 1):
		if ($File_Mode) {
			$fields = array(
				'Settings' => $Settings,
				'Header' => $Header,
				'Footer' => $Footer
			);
			$Settings = $Header = $Footer = '';

                        if (!is_writable($nc_core->TEMPLATE_FOLDER)) {
                            nc_print_status(NETCAT_CAN_NOT_WRITE_FILE, 'error');
                            return false;
                        }
		}

		$insert = "INSERT INTO `Template` (";

		for ($i = 0; $i < $fldCount; $i++)
			$insert .= $fld[$i] . ",";

		$insert .= "`Description`, `Parent_Template_ID`, `Settings`, `Header`, `Footer`, `CustomSettings`) ";
		$insert .= "VALUES (";

		for ($i = 0; $i < $fldCount; $i++) {
		    if (isset(${$fld[$i].'Defined'}) && ${$fld[$i].'Defined'} == true) {
                      $insert.= ${$fld[$i].'NewValue'} . ",";
		    } else {
		      $insert .= $fldValue[$i] . ",";
		    }
		}


		$insert .= "'" . $Description . "'," . $ParentTemplateID . ", '" . $Settings . "', '" . $Header . "', '" . $Footer . "', '" . $CustomSettings . "')";

		$nc_core->event->execute("addTemplatePrep", 0);

		$Result = $db->query($insert);
		$message = $db->insert_id;

		if ($File_Mode) {
			if ($ParentTemplateID) {
				$template_editor->load_template($ParentTemplateID);
				$template_editor->load_new_child($message);
			} else {
				$template_editor->load_template($message, "/$message/");
			}

			$template_editor->save_new_template(array_map('stripslashes', $fields), $ParentTemplateID ? true : false);
		}

		$nc_core->event->execute("addTemplate", $message);
	// EDIT template
	else:
		if ($File_Mode) {
			$template_editor->load_template($TemplateID);
			$template_editor->save_fields(
				array_map('stripslashes', array(
						'Settings' => $Settings,
						'Header' => $Header,
						'Footer' => $Footer
					)
				)
			);
			$Settings = $Header = $Footer = '';
		}

		$update = "UPDATE `Template` SET ";

		for ($i = 0; $i < $fldCount; $i++) {
            if (isset(${$fld[$i].'Defined'}) && ${$fld[$i].'Defined'} == true) {
              $update .= $fld[$i] . "=" . ${$fld[$i].'NewValue'} . ",";
            } else {
              $update .= $fld[$i] . "=" . $fldValue[$i] . ",";
            }
		}

		$update .= "Description='" . $Description . "',";
		$update .= "Settings='" . $Settings . "',";
		$update .= "Header='" . $Header . "',";
		$update .= "Footer='" . $Footer . "',";
		$update .= "CustomSettings='" . $CustomSettings . "'";
		$update .= " where Template_ID=" . $TemplateID;
		$message = $TemplateID;

        // execute core action
        $nc_core->event->execute("updateTemplatePrep", $message);

		$Result = $db->query($update);
		// execute core action
		$nc_core->event->execute("updateTemplate", $message);
	endif;

	// Обновление в таблице с файлами
	if (!empty($filetable_lastid)) {
		$db->query("UPDATE `Filetable` SET `Message_ID`='" . $message . "' WHERE ID IN (" . join(',', $filetable_lastid) . ")");
	}

	@mkdir($FILES_FOLDER . "t/", $DIRCHMOD);

	for ($i = 0; $i < count($tmpFile); $i++) {
		eval("\$tmpNewFile[\$i] = \"" . $tmpNewFile[$i] . "\";");
		@rename($FILES_FOLDER . $tmpFile[$i], $FILES_FOLDER . $File_Path[$i] . $tmpNewFile[$i]);
		@chmod($FILES_FOLDER . $File_Path[$i] . $tmpNewFile[$i], $FILECHMOD);
	}

	if ($posting == 0) {
		echo $warnText;
		TemplateForm($TemplateID, $phase, $type, $File_Mode);
		return false;
	}

	return $message;
}

        function AscIfDeleteTemplate() {
            global $db, $nc_core;
            global $UI_CONFIG, $ADMIN_PATH;
            $ask = false;

            echo "<form action=index.php method=post>";
            foreach ($_GET as $key => $val) {
                if (substr($key, 0, 6) == "Delete" && $val) {
                    $ask = true;
                    $tpl_id = substr($key, 6, strlen($key) - 6) + 0;

                    $SelectArray = $db->get_var("select Description from Template where Template_ID=" . $tpl_id);
					// check template existence
					if (!$SelectArray) {
						nc_print_status( sprintf(CONTROL_TEMPLATE_NOT_FOUND, $tpl_id), 'error');
						continue;
					}

                    $arr_templates_id = nc_get_template_children($tpl_id);
                    $arr_templates_list = $db->get_results("SELECT `Template_ID`,`Description` FROM `Template` WHERE `Template_ID` IN (" . join(",", $arr_templates_id) . ")", ARRAY_A);

                    if (count($arr_templates_list) > 1) {
                        echo "<ul>";
                        foreach ($arr_templates_list as $arr_template) {
                            echo "<li>" . $arr_template['Template_ID'] . " " . $arr_template['Description'] . "</li>";

                            if ($res = $db->get_results("SELECT Catalogue_ID,Catalogue_Name FROM Catalogue WHERE Template_ID='" . $arr_template['Template_ID'] . "'", ARRAY_N)) {
                                echo CONTROL_TEMPLATE_ERR_USED_IN_SITE . "<ul>";
                                foreach ($res as $row) {
                                    echo "<li>" . $row[1] . " (<a href=" . $ADMIN_PATH . "#site.edit(" . $row[0] . ") target='_blank'>" . CONTROL_TEMPLATE_PREF_EDIT . "</a>)";
                                }
                                echo "</ul>";
                            }

                            if ($res = $db->get_results("SELECT Subdivision_ID,Subdivision_Name FROM Subdivision WHERE Template_ID=" . $arr_template['Template_ID'], ARRAY_N)) {
                                echo CONTROL_TEMPLATE_ERR_USED_IN_SUB . "<ul>";
                                foreach ($res as $row) {
                                    echo "<li>" . $row[1] . " (<a href=" . $ADMIN_PATH . "#subdivision.edit(" . $row[0] . ") target='_blank'>" . CONTROL_TEMPLATE_PREF_EDIT . "</a>)";
                                }
                                echo "</ul>";
                            }
                        }
                        echo "</ul>";
                        nc_print_status(CONTROL_TEMPLATE_INFO_DELETE_SOME, 'info');
                    } else {
                        nc_print_status(CONTROL_TEMPLATE_INFO_DELETE . " &laquo;" . $SelectArray . "&raquo;", 'info');

                        if ($res = $db->get_results("SELECT Catalogue_ID,Catalogue_Name FROM Catalogue WHERE Template_ID='" . $tpl_id . "'", ARRAY_N)) {
                            //                nc_print_status(CONTROL_TEMPLATE_ERR_USED_IN_SITE, 'info');
                            echo CONTROL_TEMPLATE_ERR_USED_IN_SITE . "<ul>";
                            foreach ($res as $row) {
                                echo "<li>" . $row[1] . " (<a href=" . $ADMIN_PATH . "#site.edit(" . $row[0] . ") target='_blank'>" . CONTROL_TEMPLATE_PREF_EDIT . "</a>)";
                            }
                            echo "</ul>";
                        }

                        if ($res = $db->get_results("SELECT Subdivision_ID,Subdivision_Name FROM Subdivision WHERE Template_ID=" . $tpl_id, ARRAY_N)) {
                            //                nc_print_status(CONTROL_TEMPLATE_ERR_USED_IN_SUB, 'info');
                            echo CONTROL_TEMPLATE_ERR_USED_IN_SUB . "<ul>";
                            foreach ($res as $row) {
                                echo "<li>" . $row[1] . " (<a href=" . $ADMIN_PATH . "#subdivision.edit(" . $row[0] . ") target='_blank'>" . CONTROL_TEMPLATE_PREF_EDIT . "</a>)";
                            }
                            echo "</ul>";
                        }
                    }


                    print "<INPUT TYPE=HIDDEN NAME=" . $key . " VALUE=" . $val . ">";
                    $cat_counter++;
                }
            }

            $UI_CONFIG = new ui_config_template('delete', $tpl_id);

            if (!$ask) {
                return false;
            }

            echo $nc_core->token->get_input();
                ?>
                <input type="hidden" name="fs" value="<?= +$_REQUEST['fs']; ?>">
                <input type=hidden name=phase value=7>
            </form>
            <?
            $UI_CONFIG->actionButtons[] = array(
                "id" => "submit",
                "caption" => CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_CONFIRMDELETE,
                "action" => "mainView.submitIframeForm()",
                "red_border" => true,
            );
            return true;
        }

        /**
         * функция удаляет макет
         *
         */
        function DeleteTemplates() {
            global $nc_core, $db, $UI_CONFIG;

            while (list($key, $val) = each($_POST)) {
                if (substr($key, 0, 6) != "Delete")
                    continue;
                $val = intval($val);
                if (!$val)
                    continue;
                $val = (int) $val;

                $File_Mode = nc_get_file_mode('Template', $val);
                if ($File_Mode) {
                    $template_editor = new nc_template_editor($nc_core->TEMPLATE_FOLDER, $nc_core->db);
                    $template_editor->load_template($val);
                    $template_editor->delete_template();
                }


                $UI_CONFIG = new ui_config_template('delete', $val);


                $arr_templates = nc_get_template_children($val);

                if (count($arr_templates) > 1) {
                    foreach ($arr_templates as $int_template_id) {
                        // execute core action
                        $nc_core->event->execute("dropTemplatePrep", $int_template_id);

                        if (!$db->query("DELETE FROM `Template` WHERE `Template_ID` = '" . $int_template_id . "'")) {
                            $SelectArray = $db->get_var("select Description from Template where Template_ID='" . $int_template_id . "'");
                            nc_print_status(CONTROL_TEMPLATE_ERR_CANTDEL . " " . $SelectArray . ". " . TOOLS_PATCH_ERROR, 'error');
                        } else {
                            // execute core action
                            $nc_core->event->execute("dropTemplate", $int_template_id);
                        }
                        DeleteSystemTableFiles('Template', $int_template_id);
                        $UI_CONFIG->treeChanges['deleteNode'][] = "template-{$int_template_id}";
                    }
                } else {
                    // execute core action
                    $nc_core->event->execute("dropTemplatePrep", $val);

                    if (!$db->query("delete from Template where Template_ID='" . $val . "'")) {
                        $SelectArray = $db->get_var("select Description from Template where Template_ID='" . $val . "'");
                        nc_print_status(CONTROL_TEMPLATE_ERR_CANTDEL . " " . $SelectArray . ". " . TOOLS_PATCH_ERROR, 'error');
                    } else {
                        // execute core action
                        $nc_core->event->execute("dropTemplate", $val);
                    }
                    DeleteSystemTableFiles('Template', $val);
                    $UI_CONFIG->treeChanges['deleteNode'][] = "template-{$val}";
                }
            }
        }

###############################################################################

        function FullTemplateList() {
            global $db;
            global $HTTP_DOMAIN;
            global $UI_CONFIG;

            if ( $result = write_template(0) ) {
                echo $result;
            } else {
                nc_print_status(CONTROL_TEMPLATE_NONE, "info");
            }

            $UI_CONFIG->actionButtons[] = array(
                    "id" => "submit",
                    "caption" => CONTROL_TEMPLATE_TEPL_CREATE,
                    "action" => "urlDispatcher.load('template" . (+$_REQUEST['fs'] ? '_fs' : '') . ".add(0)')",
                    "align" => "left");

            $UI_CONFIG->actionButtons[] = array(
                    "id" => "submit",
                    "caption" => CONTROL_TEMPLATE_TEPL_IMPORT,
                    "action" => "urlDispatcher.load('tools.databackup.import')",
                    "align" => "left");
        }

        /**
         * Рукурсивная функция рисует макет
         *
         * @param нулевой индекс $ParentTemplateID
         */
        function write_template($ParentTemplateID, $count = 0) {
            global $db;
            global $HTTP_DOMAIN, $HTTP_ROOT_PATH, $ADMIN_PATH, $ADMIN_TEMPLATE;
            $ParentTemplateID = +$ParentTemplateID;

            $SQL = "SELECT Template_ID,
                       Description
                    FROM Template
                        where Parent_Template_ID = $ParentTemplateID
                          AND File_Mode = " . +$_REQUEST['fs'] . "
                            ORDER BY Priority, Template_ID";

            if ($Result = $db->get_results($SQL, ARRAY_N)) {
                foreach ($Result as $Array) {
                    $res.= "<table cellpadding='0' cellspacing='0' class='templateMap'>";
                    $res.= "<tr>
        <td class='withBorder' style='padding-left:" . intval($count * ($count == 1 ? 15 : 20)) . "px;" . (!$ParentTemplateID ? " font-weight: bold;" : "") . "'>" . ($ParentTemplateID ? "<img src='" . $ADMIN_PATH . "images/arrow_sec.gif' border='0' width='14' height='10' alt='arrow' title='" . $Array[0] . "'>" : "") . "<span>" . $Array[0] . ". </span><a href='index.php?fs=".+$_REQUEST['fs']."&phase=4&amp;TemplateID=" . $Array[0] . "'>" . $Array[1] . "</a></td>
        <td class='button withBorder'><a href='index.php?fs=".+$_REQUEST['fs']."&phase=2&amp;ParentTemplateID=" . $Array[0] . "'><i class='nc-icon nc--dev-templates-add nc--hovered' title='".CONTROL_TEMPLATE_ADDLINK."'></i></a></td>";
                    $res.= "<td class='button withBorder'>";
                    $res.= "<a href='index.php?fs=".+$_REQUEST['fs']."&phase=6&amp;Delete" . $Array[0] . "=" . $Array[0] . "'><i class='nc-icon nc--remove nc--hovered' title='".CONTROL_TEMPLATE_REMOVETHIS."'></i></a>";
                    $res.= "</td>";
                    $res.= "</tr>";
                    $res.= "</table>";
                    // children
                    $res.= write_template($Array[0], $count + 1);
                }
            }

            return $res;
        }

###############################################################################

        class ui_config_template extends ui_config {

            function ui_config_template($active_tab = 'edit', $template_id = 0) {

                global $db;
                global $ParentTemplateID;

                $fs_suffix = +$_REQUEST['fs'] ? '_fs' : '';

                $template_id = intval($template_id);
                if ($template_id) {
                    $template = $db->get_row("SELECT Description FROM Template WHERE Template_ID = '" . $template_id . "'", ARRAY_A);
                }

                if ($template_id) {
                    $this->headerText = $template["Description"];
                } else {
                    $this->headerText = SECTION_INDEX_DEV_TEMPLATES;
                }

                $this->headerImage = 'i_folder_big.gif';

                if ($active_tab == 'add') {
                    $this->tabs = array(
                            array(
                                    'id' => 'add',
                                    'caption' => CONTROL_TEMPLATE_ADD,
                                    'location' => "template$fs_suffix.add(" . $template_id . ")"));
                    $this->treeSelectedNode = "template-{$ParentTemplateID}";
                }

                if ($active_tab == 'import') {
                    $this->tabs = array(
                            array(
                                    'id' => 'import',
                                    'caption' => CONTROL_TEMPLATE_IMPORT,
                                    'location' => "tools.databackup.import"));
                    $this->treeSelectedNode = "template-{$ParentTemplateID}";
                }

                if ($active_tab == 'edit' || $active_tab == 'custom') {
                    $this->tabs = array(
                            array(
                                    'id' => 'edit',
                                    'caption' => CONTROL_TEMPLATE_EDIT,
                                    'location' => "template$fs_suffix.edit(" . $template_id . ")"),
                            array(
                                    'id' => 'custom',
                                    'caption' => CONTROL_CLASS_CUSTOM,
                                    'location' => "template$fs_suffix.custom(" . $template_id . ")"),);

                    $this->treeSelectedNode = "template-{$template_id}";
                    $this->locationHash = "#template.$active_tab($template_id)";
                }

                if ($active_tab == 'delete') {
                    $this->tabs = array(
                            array(
                                    'id' => 'delete',
                                    'caption' => CONTROL_TEMPLATE_DELETE,
                                    'location' => "template$fs_suffix.delete(" . $template_id . ")"));

                    $this->treeSelectedNode = "template-{$template_id}";
                }

                if ($active_tab == 'list') {
                    $this->tabs = array(
                            array(
                                    'id' => 'list',
                                    'caption' => CONTROL_TEMPLATE,
                                    'location' => "template.list"));

                    $this->locationHash = "#template.list";
                    $this->treeSelectedNode = "template.list";
                }

                $this->activeTab = $active_tab;
                $this->treeMode = 'template' . $fs_suffix;
            }

        }
        ?>