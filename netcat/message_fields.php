<?php

/* $Id: message_fields.php 8162 2012-09-28 12:08:22Z ewind $ */

if (!class_exists("nc_System"))
    die("Unable to load file.");

if ($user_table_mode)
    $systemTableName = "User";

if ($f_Keyword && $admin_mode) {
    $isDuplicatedKeyword = $db->get_var("SELECT COUNT(*)
		 FROM `" . ($user_table_mode || $systemTableID ? "User" : "Message" . $classID) . "`
		 WHERE `Keyword` = '" . $f_Keyword . "'
		 " . ($user_table_mode || $systemTableID ? "" : " AND `Subdivision_ID` = '" . $sub . "'") . "
		 " . ($action == "change" ? " AND `" . ($user_table_mode || $systemTableID ? "User" : "Message") . "_ID` <> '" . $message . "'" : ""));

    if (!$isDuplicatedKeyword && !$user_table_mode) {
        $isDuplicatedKeyword = $db->get_var("SELECT COUNT(*) FROM `Sub_Class`
			WHERE `EnglishName` = '" . $f_Keyword . "' AND `Subdivision_ID` = '" . $sub . "'");
    }
} else {
    $isDuplicatedKeyword = false;
}

$SQL = "SELECT `Field_ID`,
	`Field_Name`,
	`Description`,
	`TypeOfData_ID`,
	`Format`,
	`NotNull`,
	`Inheritance`,
	`DefaultState`,
	`TypeOfEdit_ID`,
	" . ($systemTableID ? "1 AS `DoSearch`" : "`DoSearch`") . "
	FROM `Field`
	WHERE `Checked` = 1 AND " . ($systemTableID ? " `System_Table_ID` = " . $systemTableID : "`Class_ID` = " . $classID) . "
	ORDER BY `Priority`";

$res = $db->get_results($SQL, ARRAY_N);

$fldCount = $db->num_rows;
$i = 0;
// "старые" значения полей типа "файл". Нужно иметь возможность откатиться назад
// при posting = 0
$old_file_values = array();
// файлы, которые были помечены к удалению
$file_to_delete = array();

$multifile_field = array();
$multifile_field_id = array();
if (is_array($res)) {
    foreach ($res as $field) {
        if ($field[3] == 11) {
            $multifile_field[] = $field;
            $multifile_field_id[] = $field[0];
        }
    }
}
if (isset($multifile_field[0])) {
    $SQL = "SELECT Name,
                   Size,
                   Path,
                   Field_ID,
                   Preview,
                   ID,
                   Priority
                 FROM Multifield
                     WHERE Field_ID IN (" . join(', ', $multifile_field_id) . ")
                       AND Message_ID = " . +($systemTableID == 3 ? $UserID : $message) . "
                         ORDER BY `Priority`";
    $multifile_fields_data = (array) $db->get_results($SQL);

    foreach ($multifile_fields_data as $field) {
        ${'multifile_fields_data_array' . $field->Field_ID}[] = $field;
    }

    foreach ($multifile_field as $field) {
        ${'f_' . $field[1]} = new nc_multifield($field[1], $field[2], $field[4]);
        if (isset(${'multifile_fields_data_array' . $field[0]})) {
            ${'f_' . $field[1]}->set_data(${'multifile_fields_data_array' . $field[0]});
        }
    }
}

if (!empty($res)) {
    foreach ($res AS $value) {
        // possibly there are no additional fields
        list($fldID[$i], $fld[$i], $fldName[$i], $fldType[$i], $fldFmt[$i], $fldNotNull[$i], $fldInheritance[$i], $fldDefault[$i], $fldTypeOfEdit[$i], $fldDoSearch[$i]) = $value;
        eval("\$checkKillFile = \$f_KILL" . $fldID[$i] . ";");

        if ($checkKillFile && $action == "change" && $message && $posting) {
            // delete old & new types of file if checked Delete when change
            $file_to_delete[] = $i;
        }
        //MultiSelect
        if ($fldType[$i] == 10) {
            eval("\$fldValue[\$i] = \$f_" . $fld[$i] . ";");
        } else if ($fldType[$i] != 6) {
            eval("\$fldValue[\$i] = trim((is_array(\$f_" . $fld[$i] . ")?\$_FILES['f_" . $fld[$i] . "']['tmp_name']:stripslashes(\$f_" . $fld[$i] . ")));");
        }

        if ($fldType[$i] == 8) {
            eval("if (\$f_" . $fld[$i] . "_day || \$f_" . $fld[$i] . "_month || \$f_" . $fld[$i] . "_year || \$f_" . $fld[$i] . "_hours || \$f_" . $fld[$i] . "_minutes || \$f_" . $fld[$i] . "_seconds) \$fldValue[\$i] = sprintf(\"%04d-%02d-%02d %02d:%02d:%02d\",\$f_" . $fld[$i] . "_year,\$f_" . $fld[$i] . "_month,\$f_" . $fld[$i] . "_day,\$f_" . $fld[$i] . "_hours,\$f_" . $fld[$i] . "_minutes,\$f_" . $fld[$i] . "_seconds);");
        }

        $i++;
    }
}

# текст сообщений об ошибке
$errDescr[1] = NETCAT_MODERATION_MSG_ONE;
$errDescr[2] = NETCAT_MODERATION_MSG_TWO;
$errDescr[6] = NETCAT_MODERATION_MSG_SIX;
$errDescr[7] = NETCAT_MODERATION_MSG_SEVEN;
$errDescr[8] = NETCAT_MODERATION_MSG_EIGHT;
$errDescr[21] = NETCAT_MODERATION_MSG_TWENTYONE;

if ($isDuplicatedKeyword && $posting) {
    $posting = 0;
    $errCode = 21;
    $warnText = $errDescr[$errCode];
}

if (nc_strlen($f_Keyword) > 0 && $posting) {
    if (!nc_preg_match("/^[a-z" . NETCAT_RUALPHABET . "0-9\-_]+$/i", $f_Keyword)) {
        $posting = 0;
        $errCode = 21;
        $warnText = $errDescr[$errCode];
    }
}

if ($user_table_mode && $nc_core->modules->get_by_keyword('auth') && $action == 'add' && !$nc_core->inside_admin && $posting) {
    // самостоятельная регистрация запрещена
    if ($nc_core->get_settings('deny_reg', 'auth')) {
        $posting = 0;
        $warnText = NETCAT_MODULE_AUTH_SELFREG_DISABLED;
    }
    // пользовательское соглашение
    if (!$nc_agreed && $nc_core->get_settings('agreed', 'auth')) {
        $posting = 0;
        $warnText = NETCAT_MODERATION_MSG_NEED_AGREED . "<br/>";
    }
}


if ($user_table_mode && $posting && ( $action == 'add' || (isset($Password1) && $action == 'change'))) {
    // совпадение паролей
    if ($Password1 != $Password2 || !$Password1) {
        $warnText = NETCAT_MODERATION_MSG_RETRYPASS . "<br/>";
        $posting = 0;
    }
    // минимальная длина пароля
    $pass_min = $nc_core->get_settings('pass_min', 'auth');
    if ($pass_min && nc_strlen($Password1) < $pass_min) {
        $warnText = sprintf(NETCAT_MODERATION_MSG_PASSMIN, $pass_min) . "<br/>";
        $posting = 0;
    }

    $Password = $Password1;
}

if ($posting) {
    $multiple_changes = +$_POST['multiple_changes'];
    $nc_multiple_changes = (array) $_POST['nc_multiple_changes'];

    do {
        if ($multiple_changes) {
            if (list($msg_id, $multiple_changes_fields) = each($nc_multiple_changes)) {
                foreach ($multiple_changes_fields as $multiple_changes_key => $multiple_changes_value) {
                    $fldValue[array_search($multiple_changes_key, $fld)] = $multiple_changes_value;
                }
            } else {
                break;
            }
        }

        for ($i = 0; $i < $fldCount; $i++) {
            if ($action == 'change' && !isset($_REQUEST["f_" . $fld[$i]]) && !isset(${"f_" . $fld[$i]}) && !isset($multiple_changes_fields[$fld[$i]]))
                continue;
            $errCode = 0;

            switch ($fldType[$i]) {
                # string
                case NC_FIELDTYPE_STRING:
                    $format_string[$i] = nc_field_parse_format($fldFmt[$i], NC_FIELDTYPE_STRING);
                    $fldFmt[$i] = $format_string[$i]['format'];

                    if ($fldNotNull[$i] && $fldValue[$i] == "" && !($action == 'change' && $fld[$i] == $AUTHORIZE_BY))
                        $errCode = 1;
                    if ($fldNotNull[$i] && $fldFmt[$i] == "url" && ($fldValue[$i] == 'http://' || $fldValue[$i] == 'ftp://'))
                        $errCode = 1;
                    if ($fldFmt[$i] == "email" && $fldValue[$i] && !nc_preg_match("/^[a-z0-9\._-]+@[a-z0-9\._-]+\.[a-z]{2,6}$/i", $fldValue[$i]))
                        $errCode = 2;
                    if ($fldFmt[$i] == "phone" && $fldValue[$i] && !nc_preg_match("/^ (\+?\d-?)?  (((\(\d{3}\))|(\d{3})?)-?)?  \d{3}-?\d{2}-?\d{2} $/x", str_replace(array(" ", " \t"), '', $fldValue[$i])))
                        $errCode = 2;
                    if ($fldType[$i] == 1 && $fldFmt[$i] == "url" && ($fldValue[$i] == 'http://' || $fldValue[$i] == 'ftp://'))
                        $fldValue[$i] = "";
                    if ($fldFmt[$i] == "url" && $fldValue[$i] && !isURL($fldValue[$i]))
                        $errCode = 2;
                    break;

                # int
                case NC_FIELDTYPE_INT:
                    if ($fldNotNull[$i] && $fldValue[$i] == "")
                        $errCode = 1;
                    if ($fldValue[$i] != "" && $fldValue[$i] != strval(intval($fldValue[$i])))
                        $errCode = 2;
                    break;

                # text
                case NC_FIELDTYPE_TEXT:
                    if ($fldNotNull[$i] && $fldValue[$i] == "")
                        $errCode = 1;
                    break;

                # select
                case NC_FIELDTYPE_SELECT:
                    global $db;
                    $ClassificatorName = strtok($fldFmt[$i], ':');
                    if ($fldNotNull[$i] && !$fldValue[$i])
                        $errCode = 1;
                    if ($fldValue[$i] != "")
                        $fldValue[$i]+=0;

                    $var_name = "f_" . $fld[$i] . "_name";
                    $var_name_id = "f_" . $fld[$i] . "_id";
                    if ($fldValue[$i])
                        $$var_name = $db->get_var("SELECT `" . $ClassificatorName . "_Name`
				                             FROM   `Classificator_" . $ClassificatorName . "`
				                             WHERE  `" . $ClassificatorName . "_ID`='" . $fldValue[$i] . "'");
                    $$var_name_id = $fldValue[$i];
                    break;

                #bool
                case NC_FIELDTYPE_BOOLEAN:
                    # если "checkbox"
                    if ($fldNotNull[$i] && $fldValue[$i] == "")
                        $fldValue[$i] = 0;
                    # если есть значение и оно не "1" и не "NULL", то 1
                    if ($fldValue[$i] && !is_int($fldValue[$i]) && $fldValue[$i] != "NULL")
                        $fldValue[$i] = 1;
                    # если значение "NULL" и по умолчанию "1"
                    if ($fldDefault[$i] && $fldValue[$i] == "NULL")
                        $fldValue[$i] = 1;
                    break;

                # file
                case NC_FIELDTYPE_FILE:
                    eval("\$fldValue[\$i] = trim((is_array(\$f_" . $fld[$i] . ")?\$_FILES['f_" . $fld[$i] . "']['tmp_name']:stripslashes(\$f_" . $fld[$i] . ")));");
                    if ($action == "change") {
                        $oldValue = "f_" . $fld[$i] . "_old";
                        $oldValue = $$oldValue;
                        $old_file_values[$i] = $oldValue;
                        if ($oldValue && ($fldValue[$i] == "" || $fldValue[$i] == "none"))
                            $fldValue[$i] = $oldValue;
                    }

                    if ($fldNotNull[$i] && ($fldValue[$i] == "" || $fldValue[$i] == "none"))
                        $errCode = 6;
                    if ($fldValue[$i] && $fldValue[$i] != "none" && !$oldValue && (!file_exists($fldValue[$i]) || !@filesize($fldValue[$i])))
                        $errCode = 2;
                    if (!$fldValue[$i] && ($_FILES["f_" . $fld[$i]]['error'] == 1 || $_FILES["f_" . $fld[$i]]['error'] == 2))
                        $errCode = 7;

                    if ($fldValue[$i] && $fldValue[$i] != "none" && is_uploaded_file($fldValue[$i])) {
                        // формат поля
                        $parsedFormat = nc_field_parse_format($fldFmt[$i], NC_FIELDTYPE_FILE);
                        // тип файловой системы
                        $fldFS[$i] = $parsedFormat['fs'];
                        // закачиваемый файл?
                        $fldDisposition[$i] = $parsedFormat['disposition'] ? 1 : 0;
                        //$fileSettings = explode(":",$fldFmt[$i]);
                        $filetype = $_FILES["f_" . $fld[$i]]['type'];
                        $filesize = $_FILES["f_" . $fld[$i]]['size'];
                        // Проверка размера файла
                        if ($parsedFormat['size'] > 0 && ($filesize > $parsedFormat['size']))
                            $errCode = 7;

                        //Проверка на тип файла
                        if (!empty($parsedFormat['type'])) {
                            $filetypeNotAllowed = true; // подходит тип или нет
                            $filetypeParsed = explode("/", $filetype);

                            foreach ($parsedFormat['type'] as $v) {
                                if ($filetypeParsed[0] != $v[0])
                                    continue;
                                if ($filetypeParsed[1] == $v[1] || $v[1] == '*') {
                                    $filetypeNotAllowed = false; // файл подходит по типу
                                    break;
                                }
                            }

                            if ($filetypeNotAllowed)
                                $errCode = 8;
                        }
                    }

                    if ($errCode)
                        $fldValue[$i] = $oldValue;
                    break;

                #float
                case NC_FIELDTYPE_FLOAT:
                    if ($fldNotNull[$i] && $fldValue[$i] == "")
                        $errCode = 1;
                    if ($fldValue[$i] != "" && !preg_match("/^\-?[0-9]+(\.[0-9]+)?$/is", str_replace(",", ".", $fldValue[$i])))
                        $errCode = 2;
                    if (preg_match("/,/is", $fldValue[$i]))
                        $fldValue[$i] = str_replace(",", ".", $fldValue[$i]);

                    break;

                #datetime
                case NC_FIELDTYPE_DATETIME:
                    if ($fldNotNull[$i] && $fldValue[$i] == "") {
                        $errCode = 1;
                    }
                    if ($fldValue[$i] != "" && $fldValue[$i] != '0000-00-00 00:00:00' && $fldFmt[$i] != 'event_time' && !checkdate(nc_substr($fldValue[$i], 5, 2), nc_substr($fldValue[$i], 8, 2), nc_substr($fldValue[$i], 0, 4))) {
                        $errCode = 2;
                        $fldValue[$i] = "";
                    }
                    break;

                #relation
                case NC_FIELDTYPE_RELATION:
                    if ($fldValue[$i])
                        $fldValue[$i] = (int) $fldValue[$i];
                    if ($fldNotNull[$i] && !$fldValue[$i])
                        $errCode = 1;
                    break;

                #multiselect
                case NC_FIELDTYPE_MULTISELECT:
                    if ($fldNotNull[$i] && !count($fldValue[$i]))
                        $errCode = 1;
                    $ClassificatorName = strtok($fldFmt[$i], ':');
                    $tmp = ",";
                    $var_name = "f_" . $fld[$i] . "_name";
                    $var_name_id = "f_" . $fld[$i] . "_id";

                    if (!empty($fldValue[$i])) {
                        foreach ($fldValue[$i] as $v) {
                            $tmp .=$v . ",";
                            ${$var_name_id}[] = $v;
                        }
                        $fldValue[$i] = $tmp;

                        if (!empty($$var_name_id))
                            $$var_name = $db->get_col("SELECT `" . $ClassificatorName . "_Name`
  				                             FROM   `Classificator_" . strtok($fldFmt[$i], ':') . "`
  				                             WHERE  `" . $ClassificatorName . "_ID` IN (" . join(',', $$var_name_id) . ")");
                    }
                    else {
                        $fldValue[$i] = "";
                        $$var_name = array();
                    }

                    unset($ClassificatorName);
                    break;

                case NC_FIELDTYPE_MULTIFILE:
                    if (!isset($_POST['settings_' . $fld[$i]])) {
                        break;
                    }

                    if ($_FILES && isset($_FILES["f_{$fld[$i]}_file"])) {
                        $fileFields = array('name', 'type', 'size', 'tmp_name', 'error');

                        foreach($fileFields as $fileField) {
                            ksort($_FILES["f_{$fld[$i]}_file"][$fileField]);
                        }

                        $priority = null;
                        $files = array();
                        $fileNames = array();

                        foreach($_FILES["f_{$fld[$i]}_file"]['tmp_name'] as $index => $file) {
                            if ($priority === null) {
                                $priority = $index;
                            }

                            if (is_array($file)) {
                                foreach($file as $index2 => $file2) {
                                    foreach($fileFields as $fileField) {
                                        $files[$fileField][$priority] = $_FILES["f_{$fld[$i]}_file"][$fileField][$index][$index2];
                                    }
                                    if (isset($_POST["f_{$fld[$i]}_name"][$index])) {
                                        $fileNames[$priority] = $_POST["f_{$fld[$i]}_name"][$index];
                                    }
                                    $priority++;
                                }
                            } else {
                                foreach($fileFields as $fileField) {
                                    $files[$fileField][$priority] = $_FILES["f_{$fld[$i]}_file"][$fileField][$index];
                                }
                                if (isset($_POST["f_{$fld[$i]}_name"][$index])) {
                                    $fileNames[$priority] = $_POST["f_{$fld[$i]}_name"][$index];
                                }
                                $priority++;
                            }
                        }

                        $_FILES["f_{$fld[$i]}_file"] = $files;
                        if (isset($_POST["f_{$fld[$i]}_name"])) {
                            $_POST["f_{$fld[$i]}_name"] = $fileNames;
                        }
                    }

                    if (
                        in_array(1, (array)$_FILES["f_{$fld[$i]}_file"]['error']) ||
                        in_array(2, (array)$_FILES["f_{$fld[$i]}_file"]['error'])
                    ) {
                        $errCode = 7;
                        break;
                    }

                    foreach((array)$_FILES["f_{$fld[$i]}_file"]['error'] as $index => $error) {
                        if ($error == 4) {
                            unset(
                            $_FILES["f_{$fld[$i]}_file"]['name'][$index],
                            $_FILES["f_{$fld[$i]}_file"]['type'][$index],
                            $_FILES["f_{$fld[$i]}_file"]['size'][$index],
                            $_FILES["f_{$fld[$i]}_file"]['tmp_name'][$index],
                            $_FILES["f_{$fld[$i]}_file"]['error'][$index]
                            );
                        }
                    }

                    $cnt = 0;
                    if (is_array($_FILES["f_{$fld[$i]}_file"]['tmp_name'])) {
                        foreach ($_FILES["f_{$fld[$i]}_file"]['tmp_name'] as $tmp) {
                            if ($tmp) {
                                ++$cnt;
                            }
                        }
                    }

                    $str_hash = '';
                    $str_hash .= $_POST['settings_' . $fld[$i]]['use_name'];
                    $str_hash .= $_POST['settings_' . $fld[$i]]['path'];
                    $str_hash .= +$_POST['settings_' . $fld[$i]]['preview']['width'];
                    $str_hash .= +$_POST['settings_' . $fld[$i]]['preview']['height'];
                    $str_hash .= +$_POST['settings_' . $fld[$i]]['preview']['mode'];
                    $str_hash .= +$_POST['settings_' . $fld[$i]]['resize']['width'];
                    $str_hash .= +$_POST['settings_' . $fld[$i]]['resize']['height'];
                    $str_hash .= +$_POST['settings_' . $fld[$i]]['resize']['mode'];
                    $str_hash .= +$_POST['settings_' . $fld[$i]]['min'];
                    $str_hash .= +$_POST['settings_' . $fld[$i]]['max'];

                    $str_hash .= nc_Core::get_object()->get_settings('SecretKey');

                    $hash = md5($str_hash);

                    $res_hash = $hash != $_POST["settings_{$fld[$i]}_hash"];
                    $res_max = $_POST['settings_' . $fld[$i]]['max'] && $cnt > $_POST['settings_' . $fld[$i]]['max'];

                    if ($res_hash || $res_max) {
                        echo 'Substitution of data!';
                        exit;
                    }

                    $not_null = $fldNotNull[$i] && count($_FILES["f_{$fld[$i]}_file"]['name']) == 0;
                    $not_min = $cnt < $_POST['settings_' . $fld[$i]]['min'];
                    if (($not_null || $not_min) && $action != "change") {
                        $errCode = 1;
                        break;
                    }

                    $multifile_error_desc = array(
                            NETCAT_MODERATION_MULTIFILE_ZERO,
                            NETCAT_MODERATION_MULTIFILE_ONE,
                            NETCAT_MODERATION_MULTIFILE_TWO
                    );

                    $multifile_warnText_array = array();
                    $multifile_format_types = array();

                    $multifile_format = array_shift(explode(';', $fldFmt[$i]));

                    if ($multifile_format) {
                        $parsedFormat = nc_field_parse_format($multifile_format, NC_FIELDTYPE_FILE);
                        $multifile_format_size = $parsedFormat['size'];
                        $multifile_format_types = (array) $parsedFormat['type'];
                    }

                    foreach ((array)$_FILES["f_{$fld[$i]}_file"]['name'] as $index => $name) {
                        $current_file_name = strip_tags($name);

                        if (!$fldFmt[$i]) { continue; }

                        if ($multifile_format_size && ($_FILES["f_{$fld[$i]}_file"]['size'][$index] > $multifile_format_size)) {
                            $multifile_warnText_array[1][$start] = $current_file_name;
                        }

                        if (empty($multifile_format_types[0])) { continue; }

                        $file_type_parsed = explode("/", $_FILES["f_{$fld[$i]}_file"]['type'][$index]);

                        foreach ($multifile_format_types as $multifile_format_type) {
                            if ($file_type_parsed[0] != $multifile_format_type[0]) { continue; }
                            if ($file_type_parsed[1] == $multifile_format_type[1] || $multifile_format_type[1] == '*') { continue 2; }
                        }

                        $multifile_warnText_array[2][$index] = $current_file_name;
                    }

                    if (!isset($multifile_warnText)) {
                        $multifile_warnText = '';
                    }

                    for ($current = 0, $end = count($multifile_error_desc); $current < $end; ++$current) {
                        $error_file_names = join(', ', (array) $multifile_warnText_array[$current]);
                        if ($error_file_names) {
                            $multifile_warnText .= "{$multifile_error_desc[$current]}: <b>$error_file_names</b><br />";
                        }
                    }

                    break;
            }

            if ($user_table_mode) {
                // проверка  поля, по которму идет авторизация
                if ($fld[$i] == $AUTHORIZE_BY && ($e = $nc_core->user->check_login($fldValue[$i], $action == 'add' ? 0 : $message))) {
                    if ($e == NC_AUTH_LOGIN_EXISTS)
                        $warnText = sprintf(NETCAT_MODERATION_MSG_LOGINALREADY . "<br/>", $fldValue[$i]);
                    if ($e == NC_AUTH_LOGIN_INCORRECT)
                        $warnText = NETCAT_MODERATION_MSG_LOGININCORRECT . "<br/>";
                    $posting = 0;
                    break;
                }
            }

            $warnUser = ($fldTypeOfEdit[$i] == 1) ? true : ( ($fldTypeOfEdit[$i] == 2) ? $admin_mode : false);
            if ($warnUser && $errCode) {
                $warnText = $errDescr[$errCode];
                $warnText = str_replace("%NAME", $fldName[$i], $warnText);
                $posting = 0;
                break;
            }

            if ($multifile_warnText) {
                $warnText = $multifile_warnText;
                $posting = 0;
                break;
            }
        }

    } while ($multiple_changes);

    # проверка изображения на картинке
    if (!$AUTH_USER_ID && $action == "add" && $current_cc["UseCaptcha"] && $MODULE_VARS["captcha"] && function_exists("imagegif")) {
        if (!nc_captcha_verify_code($nc_captcha_code)) {
            $warnText = NETCAT_MODULE_CAPTCHA_WRONG_CODE;
            $posting = 0;
        }
    }

    // обертка для вывода ошибки в админке
    if ($warnText && ($nc_core->inside_admin || $isNaked)) {
        ob_start();
        nc_print_status($warnText, 'error');
        $warnText = ob_get_clean();
    }

    // в случае ошибки нужно сохранить предыдущие значения полей типа файл
    if (!$posting && !empty($old_file_values)) {
        foreach ($old_file_values as $k => $v) {
            $fldValue[$k] = $v;
        }
    }

    // ошибок при заполнении формы нет - можно удалть файлы
    if ($posting && !empty($file_to_delete)) {
        foreach ($file_to_delete as $v) {
            DeleteFile($fldID[$v], $fld[$v], $classID, $systemTableName, $message);
        }
    }

    unset($old_file_values);
    unset($file_to_delete);
}

?>