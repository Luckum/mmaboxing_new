<?php

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require_once ($ROOT_FOLDER.'connect_io.php');
require ($NETCAT_FOLDER . "netcat/modules/parser/function.inc.php");

$MODULE_VARS = $nc_core->modules->get_module_vars();

echo checkEvents();