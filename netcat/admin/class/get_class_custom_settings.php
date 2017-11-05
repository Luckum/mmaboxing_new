<?php

/* $Id: get_class_custom_settings.php 7620 2012-07-11 12:55:12Z alive $ */

$_POST["NC_HTTP_REQUEST"] = true;
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require($ADMIN_FOLDER."function.inc.php");

$custom_settings_template = $db->get_var("SELECT `CustomSettingsTemplate` FROM `Class` WHERE `Class_ID` = '".(int) $class_id."'");

if ($custom_settings_template) {
    require_once($GLOBALS['ADMIN_FOLDER']."array_to_form.inc.php");
    require $GLOBALS['ADMIN_FOLDER'] . 'subdivision/subclass.inc.php';
    $a2f = new nc_a2f($custom_settings_template, 'CustomSettings');

    $fieldset = new nc_admin_fieldset(CONTROL_CONTENT_SUBDIVISION_CUSTOM_SETTINGS_TEMPLATE);
    $fieldset->add(nc_sub_class_get_CustomSettings($a2f));
    echo $fieldset->result();
}

?>