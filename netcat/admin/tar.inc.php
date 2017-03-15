<?php

require ("Tar.php");  // /netcat/require/lib/

nc_tgz_check_exec();

// проверить, есть ли внешний tar и возможность его запустить
function nc_tgz_check_exec() {
    // Global setting: DISABLE_TGZ_EXEC -- установить в true если не работает system("tar")
    // check whether to use system() call to tar [faster]
    if (!$GLOBALS["DISABLE_TGZ_EXEC"] && !preg_match("/Windows/i", php_uname())) {  // it's not Windows
        $err_code = 127;
        $tgz_version = @exec("tar --version", $output, $err_code);
        define("SYSTEM_TAR", ($err_code ? false : true));
    } else {
        define("SYSTEM_TAR", false);
    }
}

// извлечь файл из архива
function nc_tgz_extract($archive_name, $dst_path) {
    global $DOCUMENT_ROOT;

    @set_time_limit(0);
    if (SYSTEM_TAR) {
        exec("cd $DOCUMENT_ROOT; tar -zxf $archive_name -C $dst_path 2>&1", $output, $err_code);
        if ($err_code && !strpos($output[0], "time")) { // ignore "can't utime, permission denied"
            trigger_error("$output[0]", E_USER_WARNING);
            return false;
        }
        return true;
    } else {
        $current_dir = realpath('.');
        chdir($DOCUMENT_ROOT);

        $tar_object = new Archive_Tar($archive_name, "gz");
        $tar_object->setErrorHandling(PEAR_ERROR_PRINT);
        $result = $tar_object->extract($dst_path);

        chdir($current_dir);

        return $result;
    }
}

/* Создание архива формата .tgz
 *
 * @param $archive_name: имя создаваемого архива
 * @param $file_name: имена файлов и/или директорий, добавляемых в архив
 * @param $additional_path: имя начальной директории при создании архива, задается относительно корня системы ($DOCUMENT_ROOT.$SUB_FOLDER). Значение по умолчанию: пустая строка
 * @param $exclude_tag: директории, содержащие файл с указанным именем, не будут добавлены в архив. Значение по умолчанию: NULL
 *  В случае использования системного tar, производится рекурсивный поиск и создается список аргументов --exclude, содержащиъ список исключаемых директорий
 *  В случае использования класса Archive_Tar, производится рекурсивный поиск и создается массив, передаваемый в setIgnoreList($exclude_array)
 *
 * @return (bool):
 *  true в случае удачного создания архива,
 *  false в случае ошибки
 */
function nc_tgz_create($archive_name, $file_name, $additional_path = '', $exclude_tag = NULL) {
    global $DOCUMENT_ROOT, $SUB_FOLDER;

    @set_time_limit(0);

    $path = $DOCUMENT_ROOT.$SUB_FOLDER.$additional_path;
    if (SYSTEM_TAR) {
        $exclude_tag_cmd = '';
        if ($exclude_tag) {
            $exclude_array_tmp = nc_exclude_tag_to_array($path, $exclude_tag);
            $exclude_array = array();
            foreach($exclude_array_tmp as $item) {
                $exclude_array[] = '--exclude=' . preg_quote(ltrim(substr($item, strlen($path)), '/'));
            }
            $exclude_tag_cmd = implode(' ', $exclude_array);
        }
        exec("cd $path; tar -zcf '$archive_name' $exclude_tag_cmd $file_name 2>&1", $output, $err_code);
        if ($err_code) {
            trigger_error("$output[0]", E_USER_WARNING);
            return false;
        }
        return true;
    } else {
        $tar_object = new Archive_Tar($archive_name, "gz");
        $tar_object->setErrorHandling(PEAR_ERROR_PRINT);

        if ($exclude_tag) {
            $exclude_array_tmp = nc_exclude_tag_to_array($path, $exclude_tag);
            $exclude_array = array();
            foreach($exclude_array_tmp as $item) {
                $exclude_array[] = ltrim(substr($item, strlen($path)), '/');
            }
            $tar_object->setIgnoreList($exclude_array);
        }

        chdir($path);

        ob_start();
	$file_name_array = explode(' ', $file_name);
        $res = $tar_object->create($file_name_array);
        if (!$res) ob_end_flush();
        else ob_end_clean();

        return $res;

    }
}

/* Рекурсивный поиск директорий, содержащих указанный файл
 * @param $path: путь, относительно которого следует производить поиск
 * @param $exclude_tag: имя файла, по которому производится поиск
 * @param &$ret: указатель на массив возвращаемых значений (по умолчанию, создается внутри функции)
 *
 * @return (array): массив с именами директорий, включающих $path.
 */
function nc_exclude_tag_to_array($path, $exclude_tag, &$ret = NULL) {
    if ($ret===NULL) $ret = array();

    $dir = opendir($path);
    while (($file = readdir($dir))!==false) {
        if ($file==='.' || $file==='..') continue;
        if (is_dir($path . '/' . $file)) {
            nc_exclude_tag_to_array($path . '/' . $file, $exclude_tag, $ret);
        }
        if ($file===$exclude_tag) {
            $ret[] = $path;
        }
    }
    closedir($dir);
    return $ret;
}

?>
