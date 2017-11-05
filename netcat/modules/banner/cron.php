<?php

/* $Id: cron.php 6206 2012-02-10 10:12:34Z denis $ */

// Удалите эту и следующую строку, если вы используете этот скрипт
exit;

// if register_globals==off
$param = $_GET['param'];

// Укажите значение параметра, заданного в 'Управление задачами'
$check = "test";

if ($check != $param) {
    echo "Non-authorized access!";
    exit;
}

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($MODULE_FOLDER."banner/admin.inc.php");
require ($MODULE_FOLDER."banner/function.inc.php");
require ($ROOT_FOLDER."connect_io.php");

//LoadModuleEnv();
$MODULE_VARS = $nc_core->modules->load_env();

$isConsole = 1;

banner_CreateReports();

$db->query("DELETE FROM `Banner_Log` WHERE DATE_FORMAT(`Created`, '%Y-%m-%d') < DATE_FORMAT(CURRENT_DATE(), '%Y-%m-%d')");

echo "OK";
?>