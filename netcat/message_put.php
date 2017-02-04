<?php

if (!class_exists("nc_System")) {
    die("Unable to load file.");
}

$updateString = "";
$fieldString = "";
$valueString = "";

// в случае, если страница редактирования (не добавления) вызвана из
// шаблона с ignore_sub=1 и ignore_cc=1, значения $cc и $sub могут
// оказаться ошибочными, что приведет к сохранению файлов по
// неправильному пути
if (!$systemTableName && $message) {
    list($message_sub, $message_cc) = $db->get_row("SELECT m.`Subdivision_ID`, m.`Sub_Class_ID`
		FROM `Message" . $classID . "` AS m
		WHERE `Message_ID` = '" . $message . "'", ARRAY_N);
} else {
    $message_sub = $sub;
    $message_cc = $cc;
}

$SQL_multifield = array();

// $i - счетчик полей
// $j - счетчик закаченный файлов

$multiple_changes = +$_POST['multiple_changes'];
$nc_multiple_changes = (array)$_POST['nc_multiple_changes'];
$updateStrings_tmp = array();


/**
 * @var int $fldCount
 * @var array $fld
 * @var array $fldType
 * @var array $fldNotNull
 * @var array $fldFmt
 * @var array $fldTypeOfEdit
 * @var array $fldDefault
 * @var array $fldID
 * @var array $fldFS
 * @var array $fldDisposition
 * @var nc_core $nc_core
 * @var nc_db $db
 */

do {
    if ($multiple_changes) {
        if (list($msg_id, $multiple_changes_fields) = each($nc_multiple_changes)) {
            foreach ($multiple_changes_fields as $multiple_changes_key => $multiple_changes_value) {
                $fldValue[array_search($multiple_changes_key, $fld)] = $multiple_changes_value;
            }
        } else {
            break; // выход из цикла do() если были перебраны все записи в $nc_multiple_changes
        }

        foreach (array('Priority', 'Keyword') as $system_field) {
            if (isset($nc_multiple_changes[$msg_id][$system_field])) {
                $updateStrings_tmp[] = "`$system_field` = '" . $db->escape($nc_multiple_changes[$msg_id][$system_field]) . "'";
            }
        }
    }

    for ($i = 0, $j = 0; $i < $fldCount; $i++) {
        if (!(
                ($fldType[$i] == NC_FIELDTYPE_BOOLEAN && $fldNotNull[$i]) ||
                $fldType[$i] == NC_FIELDTYPE_RELATION ||
                $fldType[$i] == NC_FIELDTYPE_DATETIME ||
                ($fldType[$i] == NC_FIELDTYPE_MULTISELECT && !$multiple_changes))
            && !isset($_REQUEST["f_" . $fld[$i]])
            && !isset(${"f_" . $fld[$i]})
            && !isset($multiple_changes_fields[$fld[$i]]
            )
        ) {
            $fldValue[$i] = '""';
            continue;
        }

        // set zero value for checkbox, if not checked - not in $_REQUEST
        if ($fldType[$i] == NC_FIELDTYPE_BOOLEAN && $fldNotNull[$i] && !isset($_REQUEST["f_" . $fld[$i]]) && !isset(${"f_" . $fld[$i]})) {
            $fldValue[$i] = 0;
            ${"f_" . $fld[$i]} = 0;
        }

        // для даты персонально
        if ($fldType[$i] == NC_FIELDTYPE_DATETIME) {
            $format = nc_field_parse_format($fldFmt[$i], NC_FIELDTYPE_DATETIME);
            switch ($format['type']) {
                case "event":
                    if (!(isset($_REQUEST["f_" . $fld[$i] . "_day"]) && isset($_REQUEST["f_" . $fld[$i] . "_month"]) && isset($_REQUEST["f_" . $fld[$i] . "_year"]) && isset($_REQUEST["f_" . $fld[$i] . "_hours"]) && isset($_REQUEST["f_" . $fld[$i] . "_minutes"]) && isset($_REQUEST["f_" . $fld[$i] . "_seconds"]))) {
                        continue 2;
                    }
                    break;
                case "event_date":
                    if (!(isset($_REQUEST["f_" . $fld[$i] . "_day"]) && isset($_REQUEST["f_" . $fld[$i] . "_month"]) && isset($_REQUEST["f_" . $fld[$i] . "_year"]))) {
                        continue 2;
                    }
                    break;
                case "event_time":
                    if (!(isset($_REQUEST["f_" . $fld[$i] . "_hours"]) && isset($_REQUEST["f_" . $fld[$i] . "_minutes"]) && isset($_REQUEST["f_" . $fld[$i] . "_seconds"]))) {
                        continue 2;
                    }
                    break;
                default: // В общем случае - меняем только если прислали хотя бы одно поле
                    if (!(isset($_REQUEST["f_" . $fld[$i] . "_day"]) || isset($_REQUEST["f_" . $fld[$i] . "_month"]) || isset($_REQUEST["f_" . $fld[$i] . "_year"]) || isset($_REQUEST["f_" . $fld[$i] . "_hours"]) || isset($_REQUEST["f_" . $fld[$i] . "_minutes"]) || isset($_REQUEST["f_" . $fld[$i] . "_seconds"]))) {
                        continue 2;
                    }
                    break;
            }
        }

        if ($fldType[$i] == NC_FIELDTYPE_STRING || $fldType[$i] == NC_FIELDTYPE_TEXT || $fldType[$i] == NC_FIELDTYPE_DATETIME || $fldType[$i] == NC_FIELDTYPE_MULTISELECT) {
            if (NC_FIELDTYPE_TEXT == $fldType[$i]) {
                $format = nc_field_parse_format($fldFmt[$i], NC_FIELDTYPE_TEXT);
            }

            //транслитерация
            if (NC_FIELDTYPE_STRING == $fldType[$i]) {
                //транслитерируем только, если пользователь сам не ввел значение поля, чтобы позволить ему вводить свои собственные
                if ($format_string[$i]['use_transliteration'] == 1 && empty($_REQUEST['f_' . $format_string[$i]['transliteration_field']])) {
                    $fieldValue = nc_transliterate($fldValue[$i], ($format_string[$i]['use_url_rules'] == 1 ? true : false));
                    if ($format_string[$i]['transliteration_field'] == 'Keyword') {
                        $fieldValue = nc_check_keyword_name($message, $fieldValue, $classID);
                    }
                    $updateString .= "`" . $format_string[$i]['transliteration_field'] . "` = \"" . $fieldValue . "\", ";
                    ${$format_string[$i]['transliteration_field'] . 'Defined'} = true;
                    ${$format_string[$i]['transliteration_field'] . 'NewValue'} = "\"" . $fieldValue . "\"";
                }
            }
            $fldValue[$i] = str_replace("\\'", "'", addslashes($fldValue[$i]));

            if ($fldType[$i] == 8 && empty($fldValue[$i])) {
                $fldValue[$i] = "NULL";
            } else {
                $fldValue[$i] = "\"" . $fldValue[$i] . "\"";
            }
        }

        if ($fldValue[$i] == "" && ($fldType[$i] == NC_FIELDTYPE_INT || $fldType[$i] == NC_FIELDTYPE_FLOAT || $fldType[$i] == NC_FIELDTYPE_SELECT || $fldType[$i] == NC_FIELDTYPE_RELATION)) {

            if ($fldNotNull[$i]) {
                if ($fldTypeOfEdit[$i] == 1) {
                    $fldValue[$i] = "NULL";
                }
                if ($fldTypeOfEdit[$i] > 1 && $fldDefault[$i] != "") {
                    $fldValue[$i] = "\"\"";
                }
            } else {
                if ($fldTypeOfEdit[$i] > 1 && $fldDefault[$i] != "") {
                    $fldValue[$i] = "\"\"";
                } // int
                elseif ($fldType[$i] == NC_FIELDTYPE_INT && $fldDefault[$i] != "" && $fldDefault[$i] == strval(intval($fldDefault[$i]))) {
                    $fldValue[$i] = "\"" . $fldDefault[$i] . "\"";
                } // float
                elseif ($fldType[$i] == NC_FIELDTYPE_FLOAT && $fldDefault[$i] != "" && $fldDefault[$i] == strval(str_replace(",", ".", floatval($fldDefault[$i])))) {
                    $fldValue[$i] = "\"" . $fldDefault[$i] . "\"";
                } // list
                elseif ($fldType[$i] == NC_FIELDTYPE_SELECT && $fldValue[$i] !== false) {
                    $fldValue[$i] = 0;
                } else {
                    $fldValue[$i] = "NULL";
                }
            }
        }

        if (NC_FIELDTYPE_MULTIFILE == $fldType[$i]) {
            $settings = $_POST['settings_' . $fld[$i]];

            if (!function_exists('nc_message_put_set_if')) {

                function nc_message_put_set_if($array, $key = 0) {
                    return "IF(ID = $array[$key], $key, " . (isset($array[++$key]) ? nc_message_put_set_if($array, $key) : --$key) . ")";
                }

            }

            $priority_array = array_map('intval', (array)$_POST['priority_multifile'][$fldID[$i]]);

            if ($priority_array[0]) {
                $SQL = "UPDATE `Multifield`
                           SET `Priority` = " . nc_message_put_set_if($priority_array) . "
                         WHERE `ID` IN (" . join(', ', $priority_array) . ")";
                $db->query($SQL);
            }

            if (!$settings['path']) {
                $settings['http_path'] = nc_standardize_path_to_folder($nc_core->HTTP_FILES_PATH . "/multifile/{$fldID[$i]}/");
            } else {
                $settings['http_path'] = $db->escape(nc_standardize_path_to_folder($settings['path']));
            }
            $settings['path'] = nc_standardize_path_to_folder($nc_core->DOCUMENT_ROOT . '/' . $nc_core->SUB_FOLDER . '/' . $settings['http_path']);

            if (!is_dir($settings['path'])) {
                $folders = explode('/', rtrim($settings['path'], '/'));

                for ($all = $end = count($folders) - 1; $all > 0; --$all) {
                    $folder_tmp[] = array_pop($folders);
                    if (is_dir(join('/', $folders))) {
                        break;
                    }
                }

                $folder_tmp = array_reverse($folder_tmp);

                for ($start = 0; $all <= $end; ++$start, ++$all) {
                    $folders[] = $folder_tmp[$start];
                    mkdir(join('/', $folders));
                }
            }

            if (!function_exists('nc_message_put_clear_file_name')) {

                function nc_message_put_clear_file_name($file_name) {
                    return nc_Core::get_object()->db->escape(strip_tags($file_name));
                }

            }

            $files_name = array_map('nc_message_put_clear_file_name', (array)$_FILES["f_{$fld[$i]}_file"]['name']);
            foreach ($files_name as $index => $filename) {
                if (is_uploaded_file($_FILES["f_{$fld[$i]}_file"]['tmp_name'][$index]) && !$_FILES["f_{$fld[$i]}_file"]['error'][$index]) {
                    $files_name[$index] = nc_get_filename_for_original_fs($files_name[$index], $settings['path']);
                    move_uploaded_file($_FILES["f_{$fld[$i]}_file"]['tmp_name'][$index], $settings['path'] . $files_name[$index]);
                }
            }

            if (is_array($settings['resize']) || is_array($settings['preview']) || is_array($settings['crop'])) {
                require_once $nc_core->INCLUDE_FOLDER . "classes/nc_imagetransform.class.php";
                foreach ($files_name as $file_name) {
                    if (!is_file($settings['path'] . $file_name)) {
                        continue;
                    }

                    if ($settings['preview']['width'] && $settings['preview']['height']) {
                        @nc_ImageTransform::imgResize($settings['path'] . $file_name, $settings['path'] . 'preview_' . $file_name, $settings['preview']['width'], $settings['preview']['height'], $settings['preview']['mode']);
                    }

                    if ($settings['resize']['width'] && $settings['resize']['height']) {
                        @nc_ImageTransform::imgResize($settings['path'] . $file_name, $settings['path'] . $file_name, $settings['resize']['width'], $settings['resize']['height'], $settings['resize']['mode']);
                    }

                    if ($settings['crop']['x1'] && $settings['crop']['y1']) {
                        @nc_ImageTransform::imgCrop($settings['path'] . $file_name, $settings['path'] . $file_name, $settings['crop']['x0'], $settings['crop']['y0'], $settings['crop']['x1'], $settings['crop']['y1'],
                            NULL, 90, 0, 0,
                            $settings['crop_ignore']['width'] && $settings['crop_ignore']['height'] ? $settings['crop_ignore']['width'] : 0,
                            $settings['crop_ignore']['width'] && $settings['crop_ignore']['height'] ? $settings['crop_ignore']['height'] : 0
                        );
                    }
                }
            }

            foreach ($files_name as $index => $filename) {
                $SQL_multifield[] = "($fldID[$i], %msgID%, '" . $db->escape($_POST["f_{$fld[$i]}_name"][$index]) . "', {$_FILES["f_{$fld[$i]}_file"]['size'][$index]}, '{$settings['http_path']}{$filename}', '" . ($settings['preview'] ? "{$settings['http_path']}preview_{$filename}" : "") . "', " . ((int)$index) . ")";
            }
            $fldValue[$i] = '""';
        }

        if ($fldType[$i] == NC_FIELDTYPE_FILE) {
            $fldValue[$i] = $_FILES["f_" . $fld[$i]]["tmp_name"];

            if ($fldValue[$i] && $fldValue[$i] != "none" && is_uploaded_file($fldValue[$i])) {
                $_FILES["f_" . $fld[$i]]["name"] = str_replace(array('<', '>'), '_', $_FILES["f_" . $fld[$i]]["name"]);
                $filename = $_FILES["f_" . $fld[$i]]["name"]; // оригинальное имя
                $filetype = $_FILES["f_" . $fld[$i]]["type"];
                $filesize = $_FILES["f_" . $fld[$i]]["size"];

                $tmp_original_file = tmpfile();

                // расширение файла
                $ext = substr($filename, strrpos($filename, "."));

                $srcFile = $fldValue[$i];
                $fldValue[$i] = $filename . ":" . $filetype . ":" . '{filesize}';

                $tmpFile[$j] = (substr(php_uname(), 0, 7) == "Windows") ? strrchr($srcFile, "\\") : strrchr($srcFile, "/");
                $tmpFile[$j] = substr($tmpFile[$j], 1, strlen($tmpFile[$j]) - 1);

                if ($user_table_mode && $action != "add" && !$message) {
                    $message = $AUTH_USER_ID;
                }

                #delete old file
                if ($message) {
                    DeleteFile($fldID[$i], $fld[$i], $classID, $systemTableName, $message);
                }

                $File_PathNew[$j] = '';
                // будущее имя файла на диске + путь
                switch ($fldFS[$i]) {
                    case NC_FS_PROTECTED: // hash
                        // имя файла
                        $put_file_name = md5($filename . date("H:i:s d.m.Y") . uniqid("netcat"));
                        // путь
                        if ($sub && $cc && !$systemTableID) {
                            $File_Path[$j] = $message_sub . "/" . $message_cc . "/";
                        } elseif ($systemTableID == 1) {
                            $File_Path[$j] = "c/";
                        } elseif ($systemTableID == 3) {
                            $File_Path[$j] = "u/";
                        } elseif ($systemTableID == 4) {
                            $File_Path[$j] = "t/";
                            $message = $TemplateID;
                        } else {
                            $File_Path[$j] = $message . "/";
                            // при создании раздела имя папки не известно
                            $File_PathNew[$j] = "\$message/";
                        }
                        break;

                    case NC_FS_ORIGINAL:
                        $folder = ${"f_" . $fld[$i]}['folder'];
                        $folder = trim($folder, '/');
                        // пользователь сам указал папку
                        if ($folder && preg_match("/^[a-z][a-z0-9\/]+$/is", $folder)) {
                            $File_Path[$j] = $folder . "/";
                        } else {
                            if ($sub && $cc && !$systemTableID) {
                                $File_Path[$j] = $message_sub . "/" . $message_cc . "/";
                            } elseif ($systemTableID == 1) {
                                $File_Path[$j] = "c/";
                            } elseif ($systemTableID == 3) {
                                $File_Path[$j] = "u/";
                            } elseif ($systemTableID == 4) {
                                $File_Path[$j] = "t/";
                                $message = $TemplateID;
                            } else {
                                // $File_Path[$j] = ($message ? $message : "\$message")."/";
                                $File_Path[$j] = $message . "/";
                                // при создании раздела имя папки не известно
                                $File_PathNew[$j] = "\$message/";
                                $fld_name[$j] = $fld[$i];
                            }
                        }
                        // сгенерировать имя файла
                        $put_file_name = nc_get_filename_for_original_fs($filename, $FILES_FOLDER . $File_Path[$j], $tmpNewFile);
                        // то, что пойдет в БД
                        $fldValue[$i] .= ":" . ($File_PathNew[$j] ? $File_PathNew[$j] : $File_Path[$j]) . $put_file_name;
                        break;

                    case NC_FS_SIMPLE: // FieldID_MessageID.ext
                        $put_file_name = $fldID[$i] . "_\$message" . $ext;
                        $File_Path[$j] = ''; // в папку netcat_files
                        break;
                }
                $FileFS[$j] = $fldFS[$i]; // i и j - разные счетчики. В файлах add.php, message.php, etc работа идет с j
                $tmpNewFile[$j] = $put_file_name;

                # Create dirs
                if (!isset($DIRCHMOD)) {
                    $DIRCHMOD = 0777;
                }

                if ($fldFS[$i] == NC_FS_PROTECTED) {
                    @mkdir($FILES_FOLDER . $sub, $DIRCHMOD);
                    @chmod($FILES_FOLDER . $sub, $DIRCHMOD);
                    @mkdir($FILES_FOLDER . rtrim($File_Path[$j], "/"), $DIRCHMOD);
                    @chmod($FILES_FOLDER . rtrim($File_Path[$j], "/"), $DIRCHMOD);
                }

                if ($fldFS[$i] == NC_FS_ORIGINAL) {
                    @mkdir($FILES_FOLDER . $sub, $DIRCHMOD);
                    @chmod($FILES_FOLDER . $sub, $DIRCHMOD);
                    $folder = rtrim($File_Path[$j], "/"); // может содержать вложения, "aa/bb/cc"
                    // массив со всеми директориями, которые надо создать
                    $folders = explode('/', $folder);
                    $full_path = $FILES_FOLDER; // полный путь до создаваемой папки
                    foreach ($folders as $v) {
                        $folder_path = $full_path . $v;
                        $full_path .= $v . '/';
                        @mkdir($folder_path, $DIRCHMOD);
                        @chmod($folder_path, $DIRCHMOD);
                    }
                }

                $save_path = $FILES_FOLDER . $File_Path[$j] . ($fldFS[$i] == NC_FS_SIMPLE ? $tmpFile[$j] : $put_file_name);
                $save_path_preview = $FILES_FOLDER . $File_Path[$j] . 'preview_' . ($fldFS[$i] == NC_FS_SIMPLE ? $tmpFile[$j] : $put_file_name);


                @move_uploaded_file($srcFile, $save_path);

                $resize_format = nc_field_parse_resize_options($fldFmt[$i]);

                require_once $nc_core->INCLUDE_FOLDER . "classes/nc_imagetransform.class.php";

                if ($resize_format['use_preview']) {
                    @nc_ImageTransform::imgResize($save_path, $save_path_preview, $resize_format['preview_width'], $resize_format['preview_height']);
                }

                if ($resize_format['use_resize']) {
                    @nc_ImageTransform::imgResize($save_path, $save_path, $resize_format['resize_width'], $resize_format['resize_height']);
                    $filesize = filesize($save_path);
                }

                if ($resize_format['use_crop']) {
                    @nc_ImageTransform::imgCrop($save_path, $save_path, $resize_format['crop_x0'], $resize_format['crop_y0'], $resize_format['crop_x1'], $resize_format['crop_y1'],
                        NULL, 90, 0, 0,
                        $resize_format['crop_ignore'] ? $resize_format['crop_ignore_width'] : 0, $resize_format['crop_ignore'] ? $resize_format['crop_ignore_height'] : 0);
                    $filesize = filesize($save_path);
                }

                // в этом случаe надо записать в базу
                if ($fldFS[$i] == NC_FS_PROTECTED) {
                    $query = $db->query(
                        "INSERT INTO `Filetable`
                                    (`Real_Name`, `File_Path`, `Virt_Name`, `File_Type`, `File_Size`, `Field_ID`, `Content_Disposition`)
                             VALUES ('" . $db->escape($filename) . "', '/" . $db->escape($File_Path[$j]) . "', '" . $db->escape($put_file_name) . "', '" . $db->escape($filetype) . "',
                                    '" . intval($filesize) . "', '" . intval($fldID[$i]) . "', '" . intval($fldDisposition[$i]) . "')"
                    );

                    if ($query) {
                        $filetable_lastid[] = $db->insert_id;
                    }
                }

                $fldValue[$i] = str_replace('{filesize}', $filesize, $fldValue[$i]);


                // save file path in the $f_Field_url
                ${"f_" . $fld[$i] . "_url"} = $SUB_FOLDER . $HTTP_FILES_PATH . $File_Path[$j] . $tmpNewFile[$j];
                ${"f_" . $fld[$i] . "_preview_url"} = $SUB_FOLDER . $HTTP_FILES_PATH . $File_Path[$j] . 'preview_' . $tmpNewFile[$j];
                ${"f_" . $fld[$i] . "_name"} = $filename;
                ${"f_" . $fld[$i] . "_size"} = $filesize;
                ${"f_" . $fld[$i] . "_type"} = $filetype;

                $j++;
            } elseif ($fldValue[$i] == "" || $fldValue[$i] == "none") {
                eval("\$fldValue[\$i] = \$f_" . $fld[$i] . "_old;");
            }

            $fldValue[$i] = "\"" . $fldValue[$i] . "\"";
        }

        if (($fldTypeOfEdit[$i] == 1 || (nc_field_check_admin_perm() && $fldTypeOfEdit[$i] == 2)) && empty(${$fld[$i] . "Defined"})) {
            $fieldString .= "`" . $fld[$i] . "`,";
            $valueString .= $fldValue[$i] . ",";
            if ($action == "change" && !($user_table_mode && $fld[$i] == $AUTHORIZE_BY && !($nc_core->get_settings('allow_change_login', 'auth') || in_array($current_user['UserType'], array('fb', 'vk', 'twitter', 'openid'))))) {
                $updateString .= "`" . $fld[$i] . "` = " . $fldValue[$i] . ", ";
            }
        }

        if ($multiple_changes) {
            $updateStrings_tmp[] = "`{$fld[$i]}` = {$fldValue[$i]}";
        }
    }

    $updateStrings[$msg_id] = join(', ', $updateStrings_tmp);
    $updateStrings_tmp = array();

} while ($multiple_changes);

if (!$user_table_mode && $cc && is_object($perm) && $perm->isSubClass($cc, MASK_MODERATE)) {
    foreach (array('ncTitle', 'ncKeywords', 'ncDescription') as $nc_field) {
        if (!$nc_multiple_changes || isset($_REQUEST["f_$nc_field"])) {
            $nc_field_value = $db->escape(${"f_$nc_field"});
            $updateString .= "`$nc_field` = '$nc_field_value', ";
            $fieldString .= "`$nc_field`,";
            $valueString .= "'$nc_field_value',";
        }
    }
}

/**
 *
 * Функция проверки Keywords на уникальность, в случае совпадения
 * возвращает с числовым постфиксом "-номер", увеличенным на 1
 *
 * @param type $Message_ID
 * @param type $string
 * @param type $tablePostfix
 * @return string
 */
function nc_check_keyword_name($Message_ID = 0, $string = "", $tablePostfix = "") {
    global $db;
    $sql = "SELECT COUNT(*) as matches FROM Message" . $tablePostfix . " WHERE Keyword = '" . $db->escape($string) . "' AND Message_ID <> " . intval($Message_ID) . " ";
    $result = $db->get_row($sql, ARRAY_A);

    if ($result['matches'] > 0) {
        if (preg_match('/(-\d+)$/', $string, $match)) {
            $old_num = str_replace("-", "", end($match));
            $string = preg_replace('/(-' . $old_num . ')$/', "-" . ($old_num + 1), $string);
        }
    }
    return $string;
}
