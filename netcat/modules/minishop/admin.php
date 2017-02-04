<?php

/* $Id: admin.php 4356 2011-03-27 10:24:29Z denis $ */
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");

// UI config
require_once ($ADMIN_FOLDER."modules/ui.php");
require_once ($MODULE_FOLDER."minishop/ui_config.php");

require_once ($MODULE_FOLDER."minishop/nc_minishop_admin.class.php");
$nc_minishop_admin = new nc_minishop_admin();

if (!$view) $view = 'info';

$Title1 = NETCAT_MODULES;
$Title2 = NETCAT_MODULE_MINISHOP;

$AJAX_SAVER = !( $perm->isGuest() || $view == 'info' || $view == 'discount' );

BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/minishop/", 'minishop');
$perm->ExitIfNotAccess(NC_PERM_MODULE, 0, 0, 0, 1);
$UI_CONFIG = new ui_config_module_minishop($view, '');

// имена методов для сохранения и показа
$method_show = $view."_show";
$method_save = $view."_save";

if (!is_callable(array($nc_minishop_admin, $method_show)) || !is_callable(array($nc_minishop_admin, $method_save))) {
    nc_print_status("Incorrect view: ".htmlspecialchars($view), 'error');
    exit;
}

// сохранение информации
if ($nc_core->input->fetch_get_post('act') === 'save') {
    try {
        $nc_minishop_admin->$method_save();
        nc_print_status(NETCAT_MODULE_MINISHOP_ADMIN_SAVE_OK, 'ok');
    } catch (Exception $e) {
        nc_print_status($e->getMessage(), 'error');
    }
}

// показ какой-либо формы
$nc_minishop_admin->$method_show();

EndHtml();
?>
