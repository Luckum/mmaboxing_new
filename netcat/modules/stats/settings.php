<?php

/* $Id: settings.php 4290 2011-02-23 15:32:35Z denis $ */

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require_once ($ADMIN_FOLDER."function.inc.php");

require_once ($ADMIN_FOLDER."modules/ui.php");
require_once ($MODULE_FOLDER."stats/ui_config.php");

$UI_CONFIG = new ui_config_module_stats('settings', '', '');


if (is_file($MODULE_FOLDER."stats/".MAIN_LANG.".lang.php")) {
    require_once($MODULE_FOLDER."stats/".MAIN_LANG.".lang.php");
} else {
    require_once($MODULE_FOLDER."stats/en.lang.php");
}


$Delimeter = " &gt ";
$Title1 = NETCAT_MODULE_STATS;
$Title2 = "<a href=".$ADMIN_PATH."modules/>".NETCAT_MODULES."</a>";

// check permission
$perm->ExitIfNotAccess(NC_PERM_MODULE, 0, 0, 0, 1);

//LoadModuleEnv();
$MODULE_VARS = $nc_core->modules->get_module_vars();

$phase+=0;


BeginHtml($Title1, $Title2.$Delimeter.$Title1, "http://".$DOC_DOMAIN."/settings/modules/stats/tools");


// check permission
$perm->ExitIfNotAccess(NC_PERM_MODULE, 0, 0, 0, 1);


if (isset($DoAction)) {
    if ($nc_stat_enable == 1) {
        $nc_core->set_settings('NC_Stat_Enabled', "1", 'stats');
    } else {
        $nc_core->set_settings('NC_Stat_Enabled', "0", 'stats');
    }

    if ($openstat_enable == 1) {
        $nc_core->set_settings('Openstat_Enabled', "1", 'stats');
    } else {
        $nc_core->set_settings('Openstat_Enabled', "0", 'stats');
    }

    nc_print_status(NETCAT_MODULE_STATS_CHANGES_SAVED, "ok");
} else {
    if ($nc_stat_enable == 1) {
        $nc_core->set_settings('NC_Stat_Enabled', "1", 'stats');
        nc_print_status(NETCAT_MODULE_STATS_CHANGES_SAVED, "ok");
    }

    if ($openstat_enable == 1) {
        $nc_core->set_settings('Openstat_Enabled', "1", 'stats');
        nc_print_status(NETCAT_MODULE_STATS_CHANGES_SAVED, "ok");
    }
}

echo "<form name='ToolsForm' id='ToolsForm' method='post' action='settings.php'>\n".
"<input type='hidden' name='DoAction' value='1'>\n".
nc_admin_checkbox(NETCAT_MODULE_STATS_OPENSTAT_ENABLE, 'openstat_enable', $nc_core->get_settings('Openstat_Enabled', 'stats')).
nc_admin_checkbox(NETCAT_MODULE_STATS_ENABLE, 'nc_stat_enable', $nc_core->get_settings('NC_Stat_Enabled', 'stats')).
"</form>";
$UI_CONFIG->actionButtons[] = array("id" => "submit",
        "caption" => NETCAT_MODULE_STATS_SAVE_CHANGES,
        "action" => "mainView.submitIframeForm('ToolsForm')");


EndHtml ();
?>