<?php

$_POST["NC_HTTP_REQUEST"] = true;
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");
require ($ADMIN_FOLDER."array_to_form.inc.php");
//$db->trace=1;
if (!isset($sub_id) || !isset($template_id)) {
    trigger_error("Wrong params", E_USER_ERROR);
}

settype($catalogue_id, "integer");
settype($sub_id, "integer");
settype($template_id, "integer");

if ($template_id == 0 || $_REQUEST['is_parent_template'] == 'true') { //  наследование
    print CONTROL_TEMPLATE_CUSTOM_SETTINGS_INHERITED;
    exit;
}

$qry_join = "";
if ($catalogue_id && !$sub_id) {
    $qry_join = "LEFT JOIN Catalogue as this ON (this.Catalogue_ID=$catalogue_id AND this.Template_ID=t.Template_ID)";
} else if ($sub_id) {
    $qry_join = "LEFT JOIN Subdivision as this ON (this.Subdivision_ID=$sub_id AND this.Template_ID=t.Template_ID)";
} else {
    trigger_error("No catalogue_id nor sub_id", E_USER_ERROR);
}

$tpl_data = $db->get_row("SELECT t.CustomSettings, this.TemplateSettings
                            FROM Template as t
                                 $qry_join
                           WHERE t.Template_ID=$template_id", ARRAY_A);

if (!$tpl_data['CustomSettings']) {
    print CONTROL_TEMPLATE_CUSTOM_SETTINGS_NOT_AVAILABLE;
    exit;
}

$a2f = new nc_a2f($tpl_data['CustomSettings'], 'TemplateSettings');
$a2f->set_value($tpl_data['TemplateSettings']);
// this is only for inside_admin mode
$vs_template_header = "<table class='admin_table' style='width: 100%;'><tr><th>%CAPTION</th><th>%VALUE</th><th>%DEFAULT</th></tr>";
$vs_template_object = "<tr><td>%CAPTION&nbsp</td><td>%VALUE&nbsp</td><td>%DEFAULT&nbsp</td></tr>";
$vs_template_footer = "</table>";
$vs_template_divider = "<tr><td colspan='3'>%CAPTION</td></tr>";

print $a2f->render($vs_template_header, $vs_template_object, $vs_template_footer, $vs_template_divider);
