<?php

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
//require ($MODULE_FOLDER."parser/admin.inc.php");
require ($ADMIN_FOLDER."function.inc.php");

//require ($ADMIN_FOLDER."modules/ui.php");
//$UI_CONFIG = new ui_config_module('parser');

if (is_file($MODULE_FOLDER."parser/".MAIN_LANG.".lang.php")) {
    require_once ($MODULE_FOLDER."parser/".MAIN_LANG.".lang.php");
} else {
    require_once ($MODULE_FOLDER."parser/en.lang.php");
}

$perm->ExitIfNotAccess(NC_PERM_MODULE, 0, 0, 0, 1);
$MODULE_VARS = $nc_core->modules->get_module_vars();

if (isset($_POST['submit_btn'])) {
    set_time_limit(10000);
    if (getEvents()) {
        echo "Парсинг завершен"; 
    }
}

if (isset($_POST['submit_recent_btn'])) {
    set_time_limit(10000);
    if (getRecentEvents()) {
        echo "Парсинг завершен"; 
    }
    //echo checkEvents();
}

echo 
"<form method='post'>
    <input type='hidden' name='hidden' value='1'>
    <input name='submit_btn' value='Начать парсинг' type='submit'>
</form>";

echo 
"<form method='post'>
    <input type='hidden' name='hidden' value='1'>
    <input name='submit_recent_btn' value='Начать парсинг прошедших событий' type='submit'>
</form>";