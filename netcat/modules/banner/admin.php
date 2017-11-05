<?php

$main_section = "settings";
$item_id = 3;

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($MODULE_FOLDER."banner/admin.inc.php");
require ($ADMIN_FOLDER."function.inc.php");


require_once ($ADMIN_FOLDER."modules/ui.php");
require_once ($MODULE_FOLDER."banner/ui_config.php");
$UI_CONFIG = new ui_config_module_banner();

if (is_file($MODULE_FOLDER."banner/".MAIN_LANG.".lang.php")) {
    require_once ($MODULE_FOLDER."banner/".MAIN_LANG.".lang.php");
} else {
    require_once ($MODULE_FOLDER."banner/en.lang.php");
}

$Delimeter = " &gt ";
$Title1 = NETCAT_MODULE_BANNER;
$Title2 = "<a href=".$ADMIN_PATH."modules/>".NETCAT_MODULES."</a>";

BeginHtml($Title1, $Title2.$Delimeter.$Title1, "http://".$DOC_DOMAIN."/settings/modules/banner/");
$perm->ExitIfNotAccess(NC_PERM_MODULE, 0, 0, 0, 1);

//LoadModuleEnv();
$MODULE_VARS = $nc_core->modules->get_module_vars();

$phase+=0;

banner_CreateReports ();



if ($date_start_y && $date_start_m && $date_start_d)
        $date_start = $date_start_y."-".$date_start_m."-".$date_start_d;
if ($date_end_y && $date_end_m && $date_end_d)
        $date_end = $date_end_y."-".$date_end_m."-".$date_end_d;

if (!$date_start) $date_start = date("Y-m-d");
if (!$date_end) $date_end = date("Y-m-d");

$date_start_y = substr($date_start, 0, 4);
$date_start_m = substr($date_start, 5, 2);
$date_start_d = substr($date_start, 8, 2);

$date_end_y = substr($date_end, 0, 4);
$date_end_m = substr($date_end, 5, 2);
$date_end_d = substr($date_end, 8, 2);

banner_NavBar ();

switch ($phase) {
    case 0:
	banner_ShowReportTotal($export_excel);
        break;
    case 1:
        banner_ShowReport($date_start, $date_end, $export_excel);
        break;
    case 2:
        banner_ShowReport($date_start, $date_end, $export_excel);
        break;
    case 3:
        banner_ShowReport($date_start, $date_end, $export_excel);
        break;
    case 4:
        banner_ShowReportReferer($date_start, $date_end, $export_excel);
        break;
}

EndHtml ();
?>