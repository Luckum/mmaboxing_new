<?php

/**
 * Функции-хелперы для быстрого доступа к объектам системы
 */

/**
 * Быстрый доступ к объекту nc_Core;
 *
 * @param string|null $sub_object
 * @return mixed
 */
function nc_core($sub_object = null) {
    static $nc_core;

    if ($nc_core === null) {
        $nc_core = nc_core::get_object();
    }

    if ($sub_object) {
        return $nc_core->$sub_object;
    }

    return $nc_core;
}

/**
 * Быстрый доступ к объекту nc_Modules;
 *
 * @param string|null $keyword
 * @return mixed
 */
function nc_modules($keyword = null) {
    if ($keyword) {
        return nc_core('modules')->$keyword;
    }

    return nc_core('modules');
}

/**
 * Быстрый доступ к объекту nc_db
 *
 * @return nc_db
 */
function nc_db() {
    return nc_core('db');
}

/**
 * Путь в файловой системе к папке с модулями (или с конкретным модулем)
 */
function nc_module_folder() {
    $path = '';
    if (func_num_args()) {
        $args = func_get_args();
        $path = implode(DIRECTORY_SEPARATOR, $args) . DIRECTORY_SEPARATOR;
    }

    return nc_core('MODULE_FOLDER') . $path;
}

/**
 * Путь (от корня сайта) к папке с модулями.
 * E.g. nc_module_path('auth') → '/netcat/modules/auth/'
 *
 * @return string
 */
function nc_module_path() {
    $path = '';
    if (func_num_args()) {
        $args = func_get_args();
        $path = implode('/', $args) . '/';
    }

    return nc_core('SUB_FOLDER') . nc_core('HTTP_ROOT_PATH') . 'modules/' . $path;
}

