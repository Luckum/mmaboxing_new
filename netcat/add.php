<?php

$action = "add";

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -2)) . (strstr(__FILE__, "/") ? "/" : "\\");
@include_once($NETCAT_FOLDER . "vars.inc.php");

require($INCLUDE_FOLDER . "index.php");
require_once($INCLUDE_FOLDER . "classes/nc_imagetransform.class.php");

ob_start();

$file = fopen('log_add.txt', 'a');
fputs($file, "start\n");
do {
    // Выводить данные только одного ифоблока
    $cc_only = (int)$nc_core->input->fetch_get('cc_only');

    // security section
    $catalogue = (int)$catalogue;
    $sub = (int)$sub;
    $cc = (int)$cc;
    $classID = (int)$classID;
    $curPos = (int)$curPos;

    $cc_env = $current_cc;
    $to_cc = (int)$_POST['to_cc'];
    $to_sub = (int)$_POST['to_sub'];

    $_db_cc = $cc;
    $_db_sub = $sub;
    $_db_catalogue = $catalogue;

    if ($current_cc['SrcMirror']) {
        $mirror_data = $nc_core->sub_class->get_by_id($current_cc['SrcMirror']);
        $cc = (int)$mirror_data['Sub_Class_ID'];
        $sub = (int)$mirror_data['Subdivision_ID'];
        $catalogue = (int)$mirror_data['Catalogue_ID'];
    }

    if (!isset($use_multi_sub_class)) {
        // subdivision multisubclass option
        $use_multi_sub_class = $nc_core->subdivision->get_current("UseMultiSubClass");
    }
    if ($use_multi_sub_class == 2) { //во вкладках
        $use_multi_sub_class = 0;
    }

    
    if ($classPreview == ($current_cc["Class_Template_ID"] ? $current_cc["Class_Template_ID"] : $current_cc["Class_ID"])) {
        $magic_gpc = get_magic_quotes_gpc();
        $addTemplate = $magic_gpc ? stripslashes($_SESSION["PreviewClass"][$classPreview]["AddTemplate"]) : $_SESSION["PreviewClass"][$classPreview]["AddTemplate"];
        $addCond = $magic_gpc ? stripslashes($_SESSION["PreviewClass"][$classPreview]["AddCond"]) : $_SESSION["PreviewClass"][$classPreview]["AddCond"];
        $addActionTemplate = $magic_gpc ? stripslashes($_SESSION["PreviewClass"][$classPreview]["AddActionTemplate"]) : $_SESSION["PreviewClass"][$classPreview]["AddActionTemplate"];
        fputs($file, "add tpl\n");
    }
    
    $alter_goBackLink = "";
    $alter_goBackLink_true = false;

    if (isset($_REQUEST['goBackLink'])) {
        $alter_goBackLink = $_REQUEST['goBackLink'];
        if ($admin_mode && preg_match("/^[\/a-z0-9_-]+\?catalogue=[[:digit:]]+&sub=[[:digit:]]+&cc=[[:digit:]]+(&curPos=[[:digit:]]{0,12})?$/im", $alter_goBackLink)) {
            $alter_goBackLink_true = true;
        }
        if (!$admin_mode && preg_match("/^[\/a-z0-9_-]+(\.html)?(\?curPos=[[:digit:]]{0,12})?$/im", $alter_goBackLink)) {
            $alter_goBackLink_true = true;
        }
    }

    if (!$alter_goBackLink_true) {
        if ($admin_mode) {
            $goBackLink = $admin_url_prefix . "?catalogue=" . $catalogue . "&sub=" . $sub . "&cc=" . $cc . "&curPos=" . $curPos;
        }
        else {
            $goBackLink = ($user_table_mode
                            ? nc_folder_url($current_sub['Subdivision_ID'])
                            : nc_infoblock_url($current_cc['Sub_Class_ID'])
                          ) .
                          ($curPos ? "?curPos=" . $curPos : "");
        }
    }
    else {
        $goBackLink = $alter_goBackLink;
    }

    $goBack = "<a href='" . $goBackLink . "'>" . NETCAT_MODERATION_BACKTOSECTION . "</a>";

    $cc_settings = nc_get_visual_settings($cc);

    $nc_core->page->set_current_metatags($current_sub);

    if ($posting && $nc_core->token->is_use($action)) {
        if (!$nc_core->token->verify()) {
            echo NETCAT_TOKEN_INVALID;
            break;
        }
    }

    if (!isset($cc_env['File_Mode'])) {
        try {
            $Class_Template_ID = nc_Core::get_object()->sub_class->get_by_id($cc, 'Class_Template_ID');
        } catch (Exception $e) {
            $posting = 0;
        }
        if (is_array($cc_env)) {
            $cc_env = array_merge($cc_env, nc_get_file_mode_and_file_path($Class_Template_ID ? $Class_Template_ID : $classID));
        }
        else {
            $cc_env = nc_get_file_mode_and_file_path($Class_Template_ID ? $Class_Template_ID : $classID);
        }
    }

    if ($cc_env['File_Mode']) {
        $file_class = new nc_class_view($CLASS_TEMPLATE_FOLDER, $db);
        $file_class->load($cc_env['Real_Class_ID'], $cc_env['File_Path'], $cc_env['File_Hash']);
        require $INCLUDE_FOLDER . "classes/nc_class_aggregator_editor.class.php";
        $nc_class_aggregator = nc_class_aggregator_editor::init($file_class);
        if (is_object($nc_class_aggregator) && +$_REQUEST['nc_get_message_select']) {
            if (!$nc_class_aggregator->ignore_catalogue) {
                $nc_class_aggregator->catalogue_id = $cc_env['Catalogue_ID'];
            }

            ob_clean();
            echo $nc_class_aggregator->get_message_select(+$_REQUEST['db_Class_ID'], (array)$_POST['nc_select_attrs'], (array)$_POST['nc_option_attrs'], +$_REQUEST['db_selected']);
            exit;
        }
    }

    if ($posting) {
        if ($cc_env['File_Mode']) {
            $nc_parent_field_path = $file_class->get_parent_fiend_path('AddCond');
            $nc_field_path = $file_class->get_field_path('AddCond');
            // check and include component part
            try {
                if (nc_check_php_file($nc_field_path)) {
                    include $nc_field_path;
                }
            } catch (Exception $e) {
                if (is_object($perm) && $perm->isSubClassAdmin($cc)) {
                    // do not post this
                    $posting = 0;
                    // error message
                    echo sprintf(CONTROL_CLASS_CLASSFORM_CHECK_ERROR, CONTROL_CLASS_CLASS_FORMS_ADDRULES);
                }
            }
            $nc_parent_field_path = null;
            $nc_field_path = null;
        }
        else {
            eval($addCond);
        }
    }
    
    require $ROOT_FOLDER . "message_fields.php";
    if (!$cc_only) {
        if (!$posting) {
            if ($cc_env['File_Mode']) {
                $addTemplate = file_get_contents($file_class->get_field_path('AddTemplate'));
            }

            if ($addTemplate) {
                if ($warnText) {
                    nc_preg_match_all('#\$([a-z0-9_]+)#i', $addTemplate, $all_template_variables);
                    foreach ($all_template_variables[1] as $template_variable) {
                        if ($_REQUEST[$template_variable] == $$template_variable) {
                            $$template_variable = stripslashes($$template_variable);
                        }
                    }
                }
                if ($cc_env['File_Mode']) {
                    // обертка для вывода ошибки в админке
                    if ($warnText && ($nc_core->inside_admin || $isNaked)) {
                        ob_start();
                        nc_print_status($warnText, 'error');
                        $warnText = ob_get_clean();
                    }

                    $nc_parent_field_path = $file_class->get_parent_fiend_path('AddTemplate');
                    $nc_field_path = $file_class->get_field_path('AddTemplate');
                    $addForm = '';
                    // check and include component part
                    try {
                        if (nc_check_php_file($nc_field_path)) {
                            ob_start();
                            include $nc_field_path;
                            $addForm = ob_get_clean();
                        }
                    } catch (Exception $e) {
                        if (is_object($perm) && $perm->isSubClassAdmin($cc)) {
                            // error message
                            $addForm = sprintf(CONTROL_CLASS_CLASSFORM_CHECK_ERROR, CONTROL_CLASS_CLASS_FORMS_ADDFORM);
                        }
                    }
                    $nc_parent_field_path = null;
                    $nc_field_path = null;
                }
                else {
                    eval("\$addForm = \"" . $addTemplate . "\";");
                }
                echo nc_prepare_message_form($addForm, $action, $admin_mode, $user_table_mode, $sys_table_id, $current_cc, $f_Checked = null, $f_Priority = '', $f_Keyword = '', $f_ncTitle = '', $f_ncKeywords = '', $f_ncDescription = '');
            }
            else {
                require($ROOT_FOLDER . "message_edit.php");
            }


            if ($inside_admin && $UI_CONFIG && $goBackLink) {
                $UI_CONFIG->actionButtons[] = array("id" => "goback",
                    "caption" => CONTROL_AUTH_HTML_BACK,
                    "align" => 'left',
                    "action" => "mainView.loadIframe('" . $goBackLink . "&inside_admin=1')");
            }
        }
        else {
            if ($systemTableID == "3") {
                $message = null;
            }

            include($ROOT_FOLDER . "message_put.php");
            $IsChecked = 2 - $moderationID;

            if ($admin_mode) {
                $IsChecked = $f_Checked ? 1 : 0;
            }

            if (!$user_table_mode) {
                // check permission
                if (!(
                    $cc_env['Write_Access_ID'] == 1 ||
                    ($cc_env['Write_Access_ID'] == 2 && $AUTH_USER_ID) ||
                    (is_object($perm) && $perm->isSubClass($cc, MASK_ADD))
                )
                ) {
                    nc_print_status(NETCAT_MODERATION_ERROR_NORIGHTS, 'error');
                }
                else {
                    $f_Parent_Message_ID = (int)$f_Parent_Message_ID;
                    $fieldString .= "`Created`, `Parent_Message_ID`, `IP`, `UserAgent`, ";
                    $valueString .= "\"" . date("Y-m-d H:i:s") . "\", \"" . $f_Parent_Message_ID . "\", \"" . $db->escape($REMOTE_ADDR) . "\", \"" . $db->escape($HTTP_USER_AGENT) . "\", ";
                    $SQL = "INSERT INTO `Message" . $classID . "`
					(`Subdivision_ID`, `Sub_Class_ID`, " . $fieldString . " `Checked`, `Keyword`, `User_ID`)
					VALUES
					(" . ($to_sub ? $to_sub : $sub) . ", " . ($to_cc ? $to_cc : $cc) . ", " . $valueString . $IsChecked . ", '" . ($admin_mode ? $f_Keyword : "") . "', '" . $AUTH_USER_ID . "')";

                    // execute core action
                    $nc_core->event->execute("addMessagePrep", $catalogue, ($to_sub ? $to_sub : $sub), ($to_cc ? $to_cc : $cc), $classID, 0);

                    $resMsg = $db->query($SQL);
                    $msgID = $db->insert_id;
                    if (is_array($SQL_multifield)) {
                        $SQL_multifield = array_reverse($SQL_multifield);
                        $SQL_multifield = str_replace('%msgID%', $msgID, join(', ', $SQL_multifield));
                        if ($SQL_multifield) {
                            $SQL = "INSERT INTO Multifield(`Field_ID`, `Message_ID`, `Name`, `Size`, `Path`, `Preview`, `Priority`)
									VALUES $SQL_multifield";
                            $db->query($SQL);
                        }
                    }
                    if ($f_Priority) {
                        $f_Priority = $f_Priority + 0;
                        if ($admin_mode) {
                            // get ids
                            $_messages = $db->get_col("SELECT `Message_ID` FROM `Message" . $classID . "`
				  WHERE `Priority`>=" . $f_Priority . " AND `Subdivision_ID` = '" . ($to_sub ? $to_sub : $sub) . "' AND `Sub_Class_ID` = '" . ($to_cc ? $to_cc : $cc) . "'");
                            // update info
                            if (!empty($_messages)) {
                                // execute core action
                                $nc_core->event->execute("updateMessagePrep", $catalogue, ($to_sub ? $to_sub : $sub), ($to_cc ? $to_cc : $cc), $classID, $_messages);

                                $res = $db->query("UPDATE `Message" . $classID . "`
					SET `Priority` = `Priority` + 1, `LastUpdated` = `LastUpdated`
					WHERE `Message_ID` IN (" . join(", ", $_messages) . ")");
                                // execute core action
                                $nc_core->event->execute("updateMessage", $catalogue, ($to_sub ? $to_sub : $sub), ($to_cc ? $to_cc : $cc), $classID, $_messages);
                            }
                            // for current message
                            $res = $db->query("UPDATE `Message" . $classID . "`
				  SET `Priority` = '" . $f_Priority . "', `LastUpdated` = `LastUpdated`
				  WHERE `Message_ID` = '" . $msgID . "'");
                        }
                    }
                    else {
                        $maxPriority = $db->get_var("SELECT MAX(`Priority`) FROM `Message" . $classID . "`
						WHERE `Subdivision_ID` = '" . ($to_sub ? $to_sub : $sub) . "' AND `Sub_Class_ID` = '" . ($to_cc ? $to_cc : $cc) . "' AND `Parent_Message_ID` = '" . $f_Parent_Message_ID . "'");
                        $res = $db->query("UPDATE `Message" . $classID . "`
						SET `Priority` = " . ($maxPriority + 1) . ", `LastUpdated` = `LastUpdated`
						WHERE `Message_ID` = '" . $msgID . "'");
                    }
                    // execute core action
                    $nc_core->event->execute("addMessage", $catalogue, ($to_sub ? $to_sub : $sub), ($to_cc ? $to_cc : $cc), $classID, $msgID);
                }
            }
            else {
                $RegistrationCode = md5(uniqid(rand()));
                $IsChecked = ($nc_core->get_settings('premoderation', 'auth') || $nc_core->get_settings('confirm', 'auth')) ? 0 : 1;
                $groups = explode(",", $nc_core->get_settings('group', 'auth'));
                $mainGroup = intval(min((array)$groups));

                // execute core action
                $nc_core->event->execute("addUserPrep", 0);
                
                $fieldString = "`Email`, `Login`, `ForumName`, ";
                $valueString = "'" . $Email . "', '" . $Email . "', '" . $u_name . "', ";
                
                
                $resMsg = $db->query("INSERT INTO `User`
    			(" . $fieldString . "`Password`, `PermissionGroup_ID`, `Checked`, `Created`" . ($nc_core->get_settings('confirm', 'auth') ? ", `Confirmed`" : "") . ", Catalogue_ID)
    			VALUES
    			(" . $valueString . " " . $nc_core->MYSQL_ENCRYPT . "('" . $Password . "'), '" . $mainGroup . "', '" . $IsChecked . "', \"" . date("Y-m-d H:i:s") . "\"" . ($nc_core->get_settings('confirm', 'auth') ? ",'0'" : "") . ", " . $catalogue . ")");
                $msgID = $db->insert_id;
                // execute core action
                $nc_core->event->execute("addUser", $msgID);

                //add user to group
                if ($msgID) {
                    foreach ((array)$groups as $group_id) {
                        nc_usergroup_add_to_group($msgID, $group_id);
                    }
                }
                $ConfirmationLink = "http://" . $HTTP_HOST . $SUB_FOLDER . $HTTP_ROOT_PATH . "modules/auth/confirm.php?id=" . $msgID . "&code=" . $RegistrationCode;
                send_mail($Email, $Password);
            }
            if (!$message) {
                $message = $msgID;
            }

            if ($filetable_lastid) {
                $resMsgArr = array();
                foreach ($filetable_lastid AS $id) {
                    $resMsgArr[] = $id;
                }
                if (!empty($resMsgArr)) {
                    $resMsg = $db->query("UPDATE `Filetable` SET `Message_ID` = '" . $message . "' WHERE ID IN (" . join(", ", $resMsgArr) . ")");
                }
            }

            for ($i = 0; $i < count($tmpFile); $i++) {
                # array $File_Path is defined in message_put.php
                # !!possibly we've moved file there already!!
                eval("\$tmpNewFile[\$i] = \"" . $tmpNewFile[$i] . "\";");
                @rename($FILES_FOLDER . $tmpFile[$i], $FILES_FOLDER . $File_Path[$i] . $tmpNewFile[$i]);
                @chmod($FILES_FOLDER . $File_Path[$i] . $tmpNewFile[$i], $FILECHMOD);
            }

            if (!empty($fldFS)) {
                foreach ($fldFS as $id => $type) {
                    if (NC_FS_SIMPLE == $type) {
                        $field_url = ${"f_" . $fldName[$id] . "_url"};
                        ${"f_" . $fldName[$id] . "_url"} = str_replace('$message', $message, $field_url);
                    }
                }
            }

            if (nc_module_check_by_keyword("comments")) {
                // get rule id
                $CommentData = nc_comments::getRuleData($db, array($catalogue, $sub, $cc, $message));
                $CommentRelationID = $CommentData['ID'];
                $comm_env = array($catalogue, $sub, $cc, $message);
                // do something
                switch (true) {
                    case $CommentAccessID > 0 && $CommentRelationID:
                        // update comment rules
                        nc_comments::updateRule($db, $comm_env, $CommentAccessID, $CommentsEditRules, $CommentsDeleteRules);
                        break;
                    case $CommentAccessID > 0 && !$CommentRelationID:
                        // add comment relation
                        $CommentRelationID = nc_comments::addRule($db, $comm_env, $CommentAccessID, $CommentsEditRules, $CommentsDeleteRules);
                        break;
                    case $CommentAccessID <= 0 && $CommentRelationID:
                        // delete comment rules
                        nc_comments::dropRule($db, $comm_env);
                        $CommentRelationID = 0;
                        break;
                }
            }
            fputs($file, "here\n");
            if ($resMsg) {
                fputs($file, "here1\n");
                if ($cc && !$user_table_mode && $IsChecked && $MODULE_VARS['subscriber']
                    && (!$MODULE_VARS['subscriber']['VERSION'] || $MODULE_VARS['subscriber']['VERSION'] == 1)
                ) {
                    fputs($file, "here2\n");
                    eval("\$mailbody = \"" . $subscribeTemplate . "\";");
                    subscribe_sendmail(($to_cc ? $to_cc : $cc), $mailbody);
                }

                if ($cc_env['File_Mode']) {
                    fputs($file, "here3\n");
                    $nc_parent_field_path = $file_class->get_parent_fiend_path('AddActionTemplate');
                    fputs($file, "here3 - " . $nc_parent_field_path . "\n");
                    $nc_field_path = $file_class->get_field_path('AddActionTemplate');
                    fputs($file, "here3 - " . $nc_field_path . "\n");
                    $action_exists = filesize($nc_field_path) > 0 ? true : false;
                }

                if ($cc_env['File_Mode'] && $action_exists) {
                    fputs($file, "here4\n");
                    // check and include component part
                    try {
                        if (nc_check_php_file($nc_field_path)) {
                            include $nc_field_path;
                        }
                    } catch (Exception $e) {
                        if (is_object($perm) && $perm->isSubClassAdmin($cc)) {
                            // error message
                            echo sprintf(CONTROL_CLASS_CLASSFORM_CHECK_ERROR, CONTROL_CLASS_CLASS_FORMS_ADDLASTACTION);
                        }
                    }
                    $nc_parent_field_path = null;
                    $nc_field_path = null;
                }
                else if ($addActionTemplate) {
                    fputs($file, "12\n");
                    eval("echo \"" . $addActionTemplate . "\";");
                }
                else {
                    fputs($file, "here5\n");
                    if ($inside_admin) {
                        ob_end_clean();
                        header("Location: " . $goBackLink . "&inside_admin=1");
                        exit;
                    }
                    else {
                        echo ($IsChecked ? NETCAT_MODERATION_MSG_OBJADD : NETCAT_MODERATION_MSG_OBJADDMOD) . "<br/><br/>" . $goBack;
                    }
                }
            }
            else {
                fputs($file, "21\n");
                echo NETCAT_MODERATION_ERROR_NOOBJADD . "<br/><br/>" . $goBack;
            }
        }
    }
    fputs($file, "4545\n");
    $cc_add = $cc;
    if (count($cc_array) > 1 && $use_multi_sub_class && !$inside_admin) {
        foreach ($cc_array AS $cc) {
            if ($cc_only && $cc_only != $cc) {
                continue;
            }
            if (($cc && $cc != $cc_add) || $user_table_mode) {
                $current_cc = $nc_core->sub_class->set_current_by_id($cc);
                echo s_list_class($sub, $cc, $parsed_url['query'] . ($date ? "&date=" . $date : "") . "&isMainContent=1&isSubClassArray=1");
            }
        }
        $current_cc = $nc_core->sub_class->set_current_by_id($cc_add);
    }
} while (false);

$nc_result_msg = ob_get_clean();

if ($File_Mode) {
    require_once $INCLUDE_FOLDER . 'index_fs.inc.php';
    if (!$templatePreview) {
        echo $template_header;
        echo $nc_result_msg;
        echo $template_footer;
    }
    else {
        eval('?>' . $template_header);
        echo $nc_result_msg;
        eval('?>' . $template_footer);
    }
}
else {
    eval("echo \"" . $template_header . "\";");
    echo $nc_result_msg;
    eval("echo \"" . $template_footer . "\";");
}
fclose($file);

// выполнить необходимую обработку кода страницы и отдать результат пользователю:
$nc_core->output_page_buffer();


function send_mail($to, $pass)
{
    $email_tpl = mysql_fetch_assoc(mysql_query("SELECT * FROM Message2046 WHERE Message_ID = 1"));
    $email_from = $email_tpl['email_from'];
    $name_from = $email_tpl['name_from'];
    $search = array('%email%', '%password%');
    $replace = array($to, $pass);
    $body = str_replace($search, $replace, $email_tpl['email_body']);
    $subject = $email_tpl['email_subject'];
    $boundary = "--".md5(uniqid(time()));
    $headers = "From: " . $name_from . "<" . $email_from . ">\r\n";   
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .="Content-Type: multipart/mixed; boundary=\"".$boundary."\"\r\n";
    $multipart = "--".$boundary."\r\n";
    $multipart .= "Content-type: text/plain; charset=\"utf-8\"\r\n";
    $multipart .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";

    $body = $body."\r\n\r\n";

    $multipart .= $body;

    $file = '';
    
    $multipart .= $file."--".$boundary."--\r\n";
    mail($to, $subject, $multipart, $headers);
}