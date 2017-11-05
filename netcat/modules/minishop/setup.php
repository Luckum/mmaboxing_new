<?php

/* $Id: setup.php 4051 2010-10-08 12:04:52Z denis $ */

$module_keyword = "minishop";
$main_section = "settings";
//$item_id = 3;

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");
require_once ($ADMIN_FOLDER."modules/ui.php");

$Title1 = NETCAT_MODULES_TUNING;
$Title2 = NETCAT_MODULES;

$UI_CONFIG = new ui_config_tool(TOOLS_MODULES_LIST, TOOLS_MODULES_LIST, 'i_modules_big.gif', 'module.list');

if (!($perm->isSupervisor() || $perm->isGuest())) {
    BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/");
    nc_print_status($NO_RIGHTS_MESSAGE, 'error');
    EndHtml();
    exit;
}

// проверка, установлен этот модуль или нет
$res = $db->get_row("SELECT * FROM `Module` WHERE `Keyword` = '".$db->escape($module_keyword)."' AND `Installed` = 0", ARRAY_A);

if (!$db->num_rows) {
    BeginHtml($Title2, $Title1, "http://".$DOC_DOMAIN."/settings/modules/");
    nc_print_status(NETCAT_MODULE_INSTALLCOMPLIED, 'ok');
    EndHtml();
    exit;
} else {
    $module_data = $res;
}

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

        $db->query("INSERT INTO `Subdivision` (`Catalogue_ID`, `Parent_Sub_ID`, `Subdivision_Name`, `Template_ID`, `ExternalURL`, `EnglishName`, `LastUpdated`, `Created`, `LastModified`, `LastModifiedType`, `Hidden_URL`, `Read_Access_ID`, `Write_Access_ID`, `Priority`, `Checked`, `Edit_Access_ID`, `Checked_Access_ID`, `Delete_Access_ID`, `Subscribe_Access_ID`, `Moderation_ID`, `Favorite`, `TemplateSettings`, `UseMultiSubClass`, `UseEditDesignTemplate`, `DisallowIndexing`) VALUES (1,0,'".to_u('МиниМагазин')."',0,'','minishop','2011-03-29 11:08:47','2011-03-23 13:10:02','2011-03-29 15:08:47',0,'/minishop/',0,0,1,0,0,0,0,0,0,0,'',0,0,-1);");
        $db->debug();
        $shop_sub_id = $db->insert_id;
        $db->query("INSERT INTO `Sub_Class` (`Subdivision_ID`, `Class_ID`, `Sub_Class_Name`, `Priority`, `Read_Access_ID`, `Write_Access_ID`, `EnglishName`, `Checked`, `Catalogue_ID`, `Edit_Access_ID`, `Checked_Access_ID`, `Delete_Access_ID`, `Subscribe_Access_ID`, `Moderation_ID`, `DaysToHold`, `AllowTags`, `RecordsPerPage`, `SortBy`, `Created`, `LastUpdated`, `DefaultAction`, `NL2BR`, `UseCaptcha`, `CustomSettings`, `Class_Template_ID`, `isNaked`, `AllowRSS`, `AllowXML`) VALUES(".$shop_sub_id.", 1, '".to_u('МиниМагазин')."', 0, 0, 0, 'minishop', 1, 1, 0, 0, 0, 0, 0, NULL, -1, NULL, '', '2011-03-18 19:39:50', '2011-03-18 19:39:50', 'index', -1, -1, '', 0, 0, 0, 0);");
        printf(CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_SECTIONWASCREATED, to_u('МиниМагазин'));
        $db->query("INSERT INTO `Subdivision` (`Catalogue_ID`, `Parent_Sub_ID`, `Subdivision_Name`, `Template_ID`, `ExternalURL`, `EnglishName`, `LastUpdated`, `Created`, `LastModified`, `LastModifiedType`, `Hidden_URL`, `Read_Access_ID`, `Write_Access_ID`, `Priority`, `Checked`, `Edit_Access_ID`, `Checked_Access_ID`, `Delete_Access_ID`, `Subscribe_Access_ID`, `Moderation_ID`, `Favorite`, `TemplateSettings`, `UseMultiSubClass`, `UseEditDesignTemplate`, `DisallowIndexing`) VALUES (1,".$shop_sub_id.",'".to_u('Корзина')."',0,'','cart','2011-03-25 14:39:18','2011-03-23 13:10:02','2011-03-25 17:39:18',0,'/minishop/cart/',0,0,0,1,0,0,0,0,0,0,'',0,0,-1);");
        $cart_sub_id = $db->insert_id;
        $db->query("INSERT INTO `Sub_Class` (`Subdivision_ID`, `Class_ID`, `Sub_Class_Name`, `Priority`, `Read_Access_ID`, `Write_Access_ID`, `EnglishName`, `Checked`, `Catalogue_ID`, `Edit_Access_ID`, `Checked_Access_ID`, `Delete_Access_ID`, `Subscribe_Access_ID`, `Moderation_ID`, `DaysToHold`, `AllowTags`, `RecordsPerPage`, `SortBy`, `Created`, `LastUpdated`, `DefaultAction`, `NL2BR`, `UseCaptcha`, `CustomSettings`, `Class_Template_ID`, `isNaked`, `AllowRSS`, `AllowXML`) VALUES(".$cart_sub_id.", ".$nc_core->get_settings('cart_class_id', 'minishop').", '".to_u('Корзина')."', 0, 0, 0, 'cart', 1, 1, 0, 0, 0, 0, 0, NULL, -1, NULL, '', '2011-03-18 19:40:54', '2011-03-18 19:40:54', 'index', -1, -1, '', 0, 0, 0, 0);");
        $cart_cc_id = $db->insert_id;
        printf(CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_SECTIONWASCREATED, to_u('Корзина'));
        $db->query("INSERT INTO `Settings` (`Key`, `Value`, `Module`) VALUES('cart_cc', '".$cart_cc_id."', 'minishop'); ");
        $db->query("INSERT INTO `Subdivision` (`Catalogue_ID`, `Parent_Sub_ID`, `Subdivision_Name`, `Template_ID`, `ExternalURL`, `EnglishName`, `LastUpdated`, `Created`, `LastModified`, `LastModifiedType`, `Hidden_URL`, `Read_Access_ID`, `Write_Access_ID`, `Priority`, `Checked`, `Edit_Access_ID`, `Checked_Access_ID`, `Delete_Access_ID`, `Subscribe_Access_ID`, `Moderation_ID`, `Favorite`, `TemplateSettings`, `UseMultiSubClass`, `UseEditDesignTemplate`, `DisallowIndexing`) VALUES (1,".$shop_sub_id.",'".to_u('Заказы')."',0,'','orders','2011-03-25 14:39:18','2011-03-23 13:10:02','2011-03-25 17:39:18',0,'/minishop/orders/',3,1,1,0,0,0,0,0,0,0,'',0,0,-1);");
        $order_sub_id = $db->insert_id;
        $db->query("INSERT INTO `Sub_Class` (`Subdivision_ID`, `Class_ID`, `Sub_Class_Name`, `Priority`, `Read_Access_ID`, `Write_Access_ID`, `EnglishName`, `Checked`, `Catalogue_ID`, `Edit_Access_ID`, `Checked_Access_ID`, `Delete_Access_ID`, `Subscribe_Access_ID`, `Moderation_ID`, `DaysToHold`, `AllowTags`, `RecordsPerPage`, `SortBy`, `Created`, `LastUpdated`, `DefaultAction`, `NL2BR`, `UseCaptcha`, `CustomSettings`, `Class_Template_ID`, `isNaked`, `AllowRSS`, `AllowXML`) VALUES(".$order_sub_id.", ".$nc_core->get_settings('order_class_id', 'minishop').", '".to_u('Заказы')."', 0, 0, 0, 'order', 1, 1, 0, 0, 0, 0, 0, NULL, -1, NULL, '', '2011-03-18 19:42:01', '2011-03-18 19:42:15', 'index', -1, -1, '', 0, 0, 0, 0);");
        $order_cc_id = $db->insert_id;
        $db->query("INSERT INTO `Settings` (`Key`, `Value`, `Module`) VALUES('order_cc', '".$order_cc_id."', 'minishop'); ");
        printf(CONTROL_CONTENT_CATALOUGE_FUNCS_SHOWCATALOGUELIST_SECTIONWASCREATED, to_u('Заказы'));
        $db->query("UPDATE `Module` SET `Installed` = 1 WHERE `Module_ID` = '".intval($module_data["Module_ID"])."'");
        echo "<br/><br/>";
        nc_print_status(NETCAT_MODULE_INSTALLCOMPLIED, 'ok');
        break;
}

EndHtml();

function to_uni($string) {
    global $nc_core;
    return ($nc_core->NC_UNICODE ? $string : $nc_core->utf8->utf2win($string));
}

function to_u($string) {
    return to_uni($string);
}
?>