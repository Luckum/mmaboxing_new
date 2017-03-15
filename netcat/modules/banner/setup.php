<?php

/* $Id: setup.php 6206 2012-02-10 10:12:34Z denis $ */

$module_keyword = "banner";
$main_section = "settings";
//$item_id = 3;

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require_once ($ADMIN_FOLDER."function.inc.php");
require_once ($ADMIN_FOLDER."modules/ui.php");
require ($MODULE_FOLDER."banner/ui_config.php");

$Title1 = NETCAT_MODULES_TUNING;
$Title2 = NETCAT_MODULES;

$UI_CONFIG = new ui_config_module_banner('setup');

if (!($perm->isSupervisor() || $perm->isGuest())) {
    BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/");
    nc_print_status($NO_RIGHTS_MESSAGE, 'error');
    EndHtml ();
    exit;
}

$res = $db->get_row("SELECT * FROM `Module` WHERE `Keyword` = '".$db->escape($module_keyword)."'", ARRAY_A);
$module_data = (array)$res;

// load modules env
$lang = $nc_core->lang->detect_lang(1);
$MODULE_VARS = $nc_core->modules->load_env($lang);

if (!isset($phase)) $phase = 2;

switch ($phase) {
    case 1:
        BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/");
        break;

    case 2:
        BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/");

        SelectParentSub();

        break;

    case 3:
        BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/");

        $ClassID_b = intval($MODULE_VARS['banner']['BANNER_TABLE']);
        $ClassID_z = intval($MODULE_VARS['banner']['ZONE_TABLE']);
        $ClassID_c = intval($MODULE_VARS['banner']['CAMPAIGN_TABLE']);
        $ClassID_e = intval($MODULE_VARS['banner']['SCRIPT_EXCLUSION_TABLE']);
        $ClassID_s = intval($MODULE_VARS['banner']['SCRIPT_TABLE']);

        $BanSub = InsertSub(NETCAT_MODULE_BANNER_SETUP_BANNERS, "banner", "", 3, 3, 3, 0, 0, $ClassID_b, $SubdivisionID, $CatalogueID, "index", 1);
        InsertSub(NETCAT_MODULE_BANNER_SETUP_SCRIPTS, "script", "", 3, 3, 3, 0, 0, $ClassID_s, $BanSub, $CatalogueID, "index", 0);
        InsertSub(NETCAT_MODULE_BANNER_SETUP_EXCLUSION, "exclusion", "", 3, 3, 3, 0, 0, $ClassID_e, $BanSub, $CatalogueID, "index", 0);
        InsertSub(NETCAT_MODULE_BANNER_SETUP_ZONES, "zone", "", 3, 3, 3, 0, 0, $ClassID_z, $BanSub, $CatalogueID, "index", 0);
        InsertSub(NETCAT_MODULE_BANNER_SETUP_CAMPAIGN, "campaign", "", 3, 3, 3, 0, 0, $ClassID_c, $BanSub, $CatalogueID, "index", 0);

        UpdateHiddenURL("/", 0, $CatalogueID);

        $db->query("UPDATE `Module` SET `Parameters` = '".$module_data["Parameters"]."' WHERE `Module_ID` = '".intval($module_data["Module_ID"])."'");

        // пометим как установленный
        $db->query("UPDATE `Module` SET `Installed` = 1 WHERE `Module_ID` = '".intval($module_data["Module_ID"])."'");
        echo "<br/><br/>";
        nc_print_status(NETCAT_MODULE_INSTALLCOMPLIED, 'ok');

        break;
}

EndHtml ();
?>