<?php

/**
 * Return apache headers as array
 *
 * @return array server headers
 */
if (!function_exists("apache_request_headers")) {

    function apache_request_headers() {
        $arh = array();
        $rx_http = '/\AHTTP_/';
        foreach ($_SERVER AS $key => $val) {
            if (nc_preg_match($rx_http, $key)) {
                $arh_key = preg_replace($rx_http, '', $key);
                $rx_matches = array();
                // do some nasty string manipulations to restore the original letter case
                // this should work in most cases
                $rx_matches = explode('_', $arh_key);
                if (count($rx_matches) > 0 AND strlen($arh_key) > 2) {
                    foreach ($rx_matches AS $ak_key => $ak_val)
                        $rx_matches[$ak_key] = ucfirst(strtolower($ak_val));
                    $arh_key = implode('-', $rx_matches);
                }
                if ($val != '') {
                    $arh[$arh_key] = $val;
                }
            }
        }
        return ($arh);
    }

}

/**
 * Format timestamp as GMT date "D, d M Y H:i:s"
 *
 * @param mixed $timestamp
 *
 * @return string GMT date
 */
function nc_timestamp_to_gmt($timestamp) {
    // format timestamp as GMT date
    return gmdate("D, d M Y H:i:s", $timestamp) . " GMT";
}

/**
 * Redirect 304 Not Modified
 *
 * @param string $timestamp
 * @return bool false (not attempted)
 */
function nc_attempt_last_modified_redirect($timestamp) {
    // system superior object
    $nc_core = nc_Core::get_object();

    // format timestamp as GMT date
    $last_modified = nc_timestamp_to_gmt($timestamp);

    // get apache headers
    $request_headers = apache_request_headers();

    // check headers
    foreach ($request_headers AS $key => $value) {
        if (nc_preg_match("/^If-Modified-Since$/is", $key)) {
            if ($value && strtotime($value) >= strtotime($last_modified)) {
                if ($nc_core->PHP_TYPE != "cgi") {
                    header($_SERVER['SERVER_PROTOCOL'] . " 304 Not Modified");
                }
                else {
                    header("Status: 304 Not Modified");
                }
                exit;
            }
        }
    }

    // not attempted
    return false;
}

function ObjectExists($classID, $sysTbl, $cc, $keyword, $date = "", $return_keyword = false) {
    static $storage = array();
    global $nc_core, $db;

    switch ($sysTbl) {
        // system table
        case true:
            if (!isset($storage[1][$keyword])) {
                $storage[1][$keyword] = intval($db->get_var("SELECT `User_ID`, `Keyword` FROM `User` WHERE `Keyword` = '" . $db->escape($keyword) . "'"));
                $storage[2][$keyword] = $db->get_var(null, 1);
            }

            return $return_keyword
                    ? array($storage[1][$keyword], $storage[2][$keyword])
                    : $storage[1][$keyword];

        // simple component
        default:
            $nc_core = nc_Core::get_object();

            $mirror_cc = $nc_core->sub_class->get_by_id($cc, 'SrcMirror');
            $cc = $mirror_cc ? $mirror_cc : $cc;
            $cond_date = "";

            if ($date && strtotime($date) > 0) {
                $field_name = $nc_core->get_component($classID)->get_date_field();
                if (!$field_name) {
                    return false;
                }
                // set date condition
                $cond_date = " AND m.`" . $db->escape($field_name) . "` LIKE '" . $db->escape($date) . "%'";
            }

            if (!isset($storage[3][$classID][$cc][$keyword][$cond_date])) {
                $storage[3][$classID][$cc][$keyword][$cond_date] = intval($db->get_var(
                    "SELECT m.`Message_ID`, m.`Keyword`
					             FROM `Message" . intval($classID) . "` AS m
					             LEFT JOIN `Sub_Class` AS sc ON sc.`Sub_Class_ID` = m.`Sub_Class_ID`
                      WHERE m.`Keyword` = '" . $db->escape($keyword) . "'
                        AND sc.`Sub_Class_ID` = '" . intval($cc) . "' " . $cond_date));
                $storage[4][$classID][$cc][$keyword][$cond_date] = $db->get_var(null, 1);
            }

            return $return_keyword
                        ? array($storage[3][$classID][$cc][$keyword][$cond_date], $storage[4][$classID][$cc][$keyword][$cond_date])
                        : $storage[3][$classID][$cc][$keyword][$cond_date];
    }
}

function ObjectExistsByID($classID, $sysTbl, $id, $date = "", $return_keyword = false) {
    static $storage = array();
    global $db;

    switch ($sysTbl) {
        // system table
        case true:
            if (!isset($storage[1][$id])) {
                $storage[1][$id] = intval($db->get_var(
                    "SELECT `User_ID`, `Keyword`
                       FROM `User`
                      WHERE `User_ID` = '" . intval($id) . "'"));
                $storage[2][$id] = $db->get_var(null, 1);
            }
            return $return_keyword
                    ? array($storage[1][$id], $storage[2][$id])
                    : $storage[1][$id];

        // simple component
        default:
            if ($date && strtotime($date) > 0) {
                $field_name = nc_core::get_object()->get_component($classID)->get_date_field();
                if (!$field_name) {
                    return false;
                }
                // set date condition
                $cond_date = " AND m.`" . $db->escape($field_name) . "` LIKE '" . $db->escape($date) . "%'";
            }
            else {
                $cond_date = "";
            }

            if (!isset($storage[3][$classID][$id][$cond_date])) {
                $storage[3][$classID][$id][$cond_date] = intval($db->get_var(
                    "SELECT m.`Message_ID`, m.`Keyword`
                       FROM `Message" . intval($classID) . "` AS m
                      WHERE m.`Message_ID` = '" . intval($id) . "'" . $cond_date));
                $storage[4][$classID][$id][$cond_date] = $db->get_var(null, 1);
            }

            return $return_keyword
                        ? array($storage[3][$classID][$id][$cond_date], $storage[4][$classID][$id][$cond_date])
                        : $storage[3][$classID][$id][$cond_date];
    }
}

function AttemptToRedirect($url) {
    // system superior object
    $nc_core = nc_Core::get_object();
    // GET data
    $get_data = $nc_core->input->fetch_get();
    // REQUEST_URI не надо учитывать
    if ($get_data['REQUEST_URI']) {
        unset($get_data['REQUEST_URI']);
    }

    if (!empty($get_data)) {
        $url .= '?' . $nc_core->url->build_url($get_data);
    }

    $nc_core->db->num_rows = 0;

    $escaped_url = $nc_core->db->escape($url);

    $SQL = "
        SELECT REPLACE(NewURL,'*','$'),
               REPLACE(OldURL,'*','([[:alnum:]-]+)'),
               `Header`
          FROM `Redirect`
         WHERE '$escaped_url' LIKE CONCAT('http://', REPLACE(REPLACE(OldURL,'_','\\\_'),'*','%'))
            OR '$escaped_url' LIKE CONCAT('http://www.',REPLACE(REPLACE(OldURL,'_','\\\_'),'*','%'))
         ORDER BY LENGTH(OldURL) DESC
         LIMIT 1";
    $res = $nc_core->db->get_row($SQL, ARRAY_N);


    if (!$nc_core->db->num_rows) {
        return 0;
    }

    list($new_url, $old_url, $header_code) = $res;

    // заголовок по умолчанию
    if ($header_code != 301 && $header_code != 302) {
        $header_code = 301;
    }

    if (strchr($new_url, '$')) {
        $result_url = preg_replace('@' . $old_url . '@i', $new_url, $url, -1, $c);
    }
    else {
        $result_url = "http://" . $new_url;
    }

    if ($nc_core->REDIRECT_STATUS == "on") {
        if ($nc_core->AUTHORIZATION_TYPE == 'session') {
            if (substr($result_url, -1) != 'l' && substr($result_url, -1) != '/') {
                $result_url .= "&" . session_name() . "=" . session_id();
            }
            else {
                $result_url .= "?" . session_name() . "=" . session_id();
            }
        }

        if ($nc_core->PHP_TYPE == 'cgi') {
            header('Status: 301 Moved Permanently');
        }

        header("Location: " . $result_url, true, $header_code);
    }
    else {
        if ($nc_core->PHP_TYPE == 'cgi') {
            header('Status: 200 OK');
        }
        echo "<meta http-equiv='refresh' content='0;url=http://" . $result_url . "'>";
    }
    exit;
}

/**
 * @param nc_url|string $url Объект nc_url или строка
 * @param string $method GET|POST
 * @param int|null $site_id    Если null — текущий сайт
 * @return array|false   Массив с информацией об объекте, на который ссылается путь,
 *    или FALSE.
 *    array(
 *         resource_type => folder|infoblock|object|script
 *         site_id => идентификатор сайта
 *         folder_id => идентификатор раздела
 *         infoblock_id => [идентификатор инфоблока]
 *         object_id => идентификатор объекта в инфоблоке
 *         action => действие над инфоблоком или объектом
 *         format => html|rss|xml
 *         variables => массив с дополнительными переменными (только для модуля маршрутизации)
 *         date => дата в пути
 *         script_path => путь к скрипту от папки DOCUMENT_ROOT/SUB_FOLDER (только для модуля маршрутизации для resource_type=script)
 *         redirect_to_url => при запросе всегда будет выполняться переадресация
 *    )
 */
function nc_resolve_url($url, $method = null, $site_id = null) {
    $nc_core = nc_core::get_object();
    $routing_module_enabled = nc_module_check_by_keyword('routing');

    // --- Приведение параметра $url к nc_url ---
    if (!$url instanceof nc_url) {
        $url = new nc_url($url);
    }
    else if (!$routing_module_enabled) {
        // Создадим клон $url, так как в процессе работы будут изменяться свойства этого объекта
        $url = clone $url;
    }

    // --- Определение сайта ---
    if (!$site_id) {
        $site_settings = $nc_core->catalogue->get_by_host_name($url->get_parsed_url('host'));
        if (isset($site_settings['Catalogue_ID'])) {
            $site_id = $site_settings['Catalogue_ID'];
        }
        else {
            $site_id = $nc_core->catalogue->id();
        }
    }

    if (!$site_id) { return false; }

    // --- Использование модуля маршрутизации ---
    if ($routing_module_enabled) {
        $result = nc_routing::resolve(
            new nc_routing_request($site_id, $method, $url->get_parsed_url())
        );

        if ($result) {
            $result = $result->to_array();
            $result['site_id'] = $site_id;
            return $result;
        }
        else {
            return false;
        }
    }

    // --- «Классическая» маршрутизация ---
    $result = array(
                 'resource_type' => 'folder',
                 'site_id' => $site_id,
                 'folder_id' => null,
                 'infoblock_id' => null,
                 'object_id' => null,
                 'action' => null,
                 'format' => 'html',
                 'variables' => array(),
                 'date' => null,
                 'redirect_to_url' => null,
    );

    // Инициализация переменных
    $component_id = 0;
    $default_action = null;
    $page_not_found = false;

    // Имя «файла»
    $req_file = strrchr($url->get_parsed_url('path'), '/');

    // Определяем раздел по пути
    $result['folder_id'] = $nc_core->subdivision->get_by_uri($url->get_parsed_url('path'), $site_id, 'Subdivision_ID', true, true);

    // Если раздел не найден, дальнейшая обработка адреса не имеет смысла,
    // так как мы в любом случае должны вернуть FALSE
    if (!$result['folder_id']) {
        return false;
    }

    $file_name = '';
    $file_extension = '';

    $uri_date = $url->get_uri_date();

    if ($req_file != '/') {
        $req_file = substr($req_file, 1);
        if (strpos($req_file, '.')) {
            $req_file_parts = explode(".", $req_file);
            $file_name = $req_file_parts[0];
            $file_extension = strtolower($req_file_parts[count($req_file_parts) - 1]);
        }

        if (in_array($file_extension, array('html', 'rss', 'xml'))) {
            // name without extension
            $url->set_parsed_url_item('path', substr($url->get_parsed_url('path'), 0, strlen($url->get_parsed_url('path')) - strlen($req_file)));
        }
        else {
            // append trailing slash
            $url->set_parsed_url_item('path', rtrim($url->get_parsed_url('path'), "/") . "/");
        }

        // Адрес имеет расширение (.html, .rss, .xml) — это адрес объекта или инфоблока
        if (in_array($file_extension, array('html', 'rss', 'xml'))) {
            $result['format'] = $file_extension;

            $infoblocks_in_folder = $nc_core->sub_class->get_by_subdivision_id($result['folder_id']);

            // keyword.html — совпадение по ключевому слову объекта
            if (nc_preg_match("/^([_a-zа-я0-9-]+)$/i", $file_name, $regs)) {
                if (!empty($infoblocks_in_folder)) {
                    foreach ($infoblocks_in_folder AS $infoblock_settings) {
                        if ($file_extension == 'rss' && !$infoblock_settings['AllowRSS']) {
                            continue;
                        }
                        if ($file_extension == 'xml' && !$infoblock_settings['AllowXML']) {
                            continue;
                        }
                        // Находим объект, подходящий под имеющиеся параметры
                        if ($object_id = ObjectExists($infoblock_settings['Class_ID'], $infoblock_settings['sysTbl'], $infoblock_settings['Sub_Class_ID'], $file_name, $uri_date)) {
                            $component_id = $infoblock_settings['Class_ID'];
                            $result['resource_type'] = 'object';
                            $result['infoblock_id'] = $_db_cc = $infoblock_settings['Sub_Class_ID'];
                            $result['object_id'] = $object_id;
                            $result['action'] = 'full';
                            break;
                        }
                    }
                }
            }

            // news.html — ключевое слово компонента, при условии, что нет такого объекта
            if (!$result['object_id'] && nc_preg_match("/^([a-zа-я0-9-]+)$/i", $file_name, $regs)) {
                if (!empty($infoblocks_in_folder)) {
                    foreach ($infoblocks_in_folder as $infoblock_settings) {
                        if ($infoblock_settings['EnglishName'] == $regs[1]) {
                            if ($file_extension == 'rss' && !$infoblock_settings['AllowRSS']) {
                                continue;
                            }
                            if ($file_extension == 'xml' && !$infoblock_settings['AllowXML']) {
                                continue;
                            }
                            $result['resource_type'] = 'infoblock';
                            $result['infoblock_id'] = $_db_cc = $infoblock_settings['Sub_Class_ID'];
                            // action может быть задан в get'e или post'e
                            if (!$result['action']) {
                                $result['action'] = $infoblock_settings['DefaultAction'];
                            }
                            break;
                        }
                    }
                }
            }

            // add_news.html, search_news.html, subscribe_news.html — добавление, поиск, подписка в компоненте
            if (nc_preg_match("/^(add|search|subscribe)_((?i:[a-zа-я0-9-]+))$/", $file_name, $regs)) {
                if (!empty($infoblocks_in_folder)) {
                    foreach ($infoblocks_in_folder as $infoblock_settings) {
                        if ($infoblock_settings['EnglishName'] != $regs[2]) {
                            continue;
                        }
                        $result['resource_type'] = 'infoblock';
                        $result['infoblock_id'] = $_db_cc = $infoblock_settings['Sub_Class_ID'];
                        $result['action'] = $regs[1];
                        break;
                    }
                }
            }

            // news_5.html — отображение объекта по компоненту и идентификатору
            if (nc_preg_match("/^([a-zа-я0-9-]+)_([0-9]+)$/i", $file_name, $regs) && ($file_name == $regs[1] . "_" . $regs[2])) {
                if (!empty($infoblocks_in_folder)) {
                    foreach ($infoblocks_in_folder as $infoblock_settings) {
                        // check component in sub keyword
                        if ($infoblock_settings['EnglishName'] != $regs[1] /* && $cc_mirror_data['EnglishName'] != $regs[1] */) {
                            continue;
                        }
                        if ($file_extension == 'rss' && !$infoblock_settings['AllowRSS']) {
                            continue;
                        }
                        if ($file_extension == 'xml' && !$infoblock_settings['AllowXML']) {
                            continue;
                        }
                        // find message with requested params
                        if ($object_id = ObjectExistsByID($infoblock_settings['Class_ID'], $infoblock_settings['sysTbl'], $regs[2], $uri_date)) {
                            $component_id = $infoblock_settings['Class_ID'];
                            $result['resource_type'] = 'object';
                            $result['infoblock_id'] = $_db_cc = $infoblock_settings['Sub_Class_ID'];
                            $result['object_id'] = $object_id;
                            $result['action'] = 'full';
                            break;
                        }
                    }
                }
            }

            // edit_object.html — изменение объекта по ДЕЙСТВИЮ и КЛЮЧЕВОМУ СЛОВУ, при условии, что нет объекта по компоненту и идентификатору
            if (!$result['object_id'] && nc_preg_match("/^(edit|delete|drop|checked|subscribe)_((?i:[_a-zа-я0-9-]+))$/", $file_name, $regs)) {
                if (!empty($infoblocks_in_folder)) {
                    foreach ($infoblocks_in_folder AS $infoblock_settings) {
                        // find message with need params
                        if ($object_id = ObjectExists($infoblock_settings['Class_ID'], $infoblock_settings['sysTbl'], $infoblock_settings['Sub_Class_ID'], $regs[2])) {
                            $component_id = $infoblock_settings['Class_ID'];
                            $result['resource_type'] = 'object';
                            $result['infoblock_id'] = $_db_cc = $infoblock_settings['Sub_Class_ID'];
                            $result['object_id'] = $object_id;
                            $result['action'] = $regs[1];
                            break;
                        }
                    }
                }
            }

            // edit_news_5.html — изменение объекта по действию, компоненту и идентификатору объекта
            if (nc_preg_match("/^(edit|delete|drop|checked|subscribe)_((?i:[_a-zа-я0-9-]+))_([0-9]+)$/", $file_name, $regs)) {
                if (!empty($infoblocks_in_folder)) {
                    foreach ($infoblocks_in_folder AS $infoblock_settings) {
                        // check component in sub keyword
                        if ($infoblock_settings['EnglishName'] != $regs[2] /* && $cc_mirror_data['EnglishName'] != $regs[2] */) {
                            continue;
                        }
                        // find message with need params
                        if ($object_id = ObjectExistsByID($infoblock_settings['Class_ID'], $infoblock_settings['sysTbl'], $regs[3])) {
                            $component_id = $infoblock_settings['Class_ID'];
                            $result['resource_type'] = 'object';
                            $result['infoblock_id'] = $_db_cc = $infoblock_settings['Sub_Class_ID'];
                            $result['object_id'] = $object_id;
                            $result['action'] = $regs[1];
                            break;
                        }
                    }
                }
            }
        }
        else { // У «файла» нет расширения, либо нестандартное расширение
            // Добавить "/" и сделать переадресацию
            $result['redirect_to_url'] = $url->source_url() . // выше к пути уже был добавлен слэш
                ($url->get_parsed_url('query') ? "?" . $url->get_parsed_url('query') : "") .
                ($url->get_parsed_url('fragment') ? "#" . $url->get_parsed_url('fragment') : "");
        }
    }


    // Для разделов установить ID первого инфоблока
    if ($result['resource_type'] == 'folder' && !$file_name && $result['folder_id']) {
        if (empty($infoblocks_in_folder)) {
            $infoblocks_in_folder = $nc_core->sub_class->get_by_subdivision_id($result['folder_id']);
        }

        foreach ((array)$infoblocks_in_folder as $infoblock_settings) {
            if ($infoblock_settings['Checked'] || $infoblock_settings['sysTbl'] == 3) {
                $component_id = $infoblock_settings['Class_ID'];

                if ($uri_date && !$nc_core->get_component($component_id)->get_date_field()) {
                    continue;
                }

                $result['infoblock_id'] = $infoblock_settings['Sub_Class_ID'];

                if (!$result['action']) {
                    $result['action'] = $infoblock_settings['DefaultAction'];
                }
                break;
            }
        }
    }

    // Если есть «имя файла», но не определён по крайней мере ID инфоблока, то это неправильный путь
    if ($file_name && !$result['infoblock_id']) {
        $page_not_found = true;
    }

    // Дата в пути
    if (!$page_not_found && $uri_date) {
        if (!$result['infoblock_id'] || ($result['infoblock_id'] && !$nc_core->get_component($component_id)->get_date_field())) {
            // if there is a date in URI segments and no "event" field in the corresponding component, it is an incorrect path
            $page_not_found = true;
        }
        else {
            $result['date'] = $uri_date;
        }
    }

    return ($page_not_found ? false : $result);

}