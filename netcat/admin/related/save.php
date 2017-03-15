<?php
/* $Id: save.php 5946 2012-01-17 10:44:36Z denis $ */
// данный файл существует только потому, что в s_list_class (select_message_list.php)
// и в дереве (tree_json.php mode=select_subdivision)
// неэффективно вычислять название связанного объекта [для формы, открывшей это окно]

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require_once ($ADMIN_FOLDER."function.inc.php");
require_once ($ADMIN_FOLDER."related/format.inc.php");
require_once ($INCLUDE_FOLDER."s_common.inc.php");

$field_id = (int) $field_id;
$object_id = (int) $object_id;
$cs_field_name = htmlspecialchars($cs_field_name, ENT_QUOTES);
$cs_type = htmlspecialchars($cs_type, ENT_QUOTES);
if ((!$field_id && !($cs_field_name || !$cs_type) ) || !$object_id) {
    trigger_error("Wrong params", E_USER_ERROR);
}

if ($field_id) {
    $field_data = field_relation_factory::get_instance_by_field_id($field_id);
} else {
    require ($ADMIN_FOLDER."array_to_form.inc.php");
    $classname = 'nc_a2f_field_'.$cs_type;
    if (!class_exists($classname)) {
        trigger_error("Wrong params", E_USER_ERROR);
    }
    $fl = new $classname ();
    $field_data = $fl->get_relation_object();
}

$qry = $field_data->get_object_query($object_id);
$tpl = $field_data->get_full_admin_template($field_id ? "" : "%ID. <a href='%LINK' target='_blank'>%CAPTION</a>");
$field_caption = str_replace(array("\r", "\n"), "", addslashes(listQuery($qry, $tpl)));
?>
<html>
    <head>
        <title></title>
        <script type='text/javascript'>
            try {
<?php
if ($field_id) {
    echo " window.opener.document.getElementById('nc_rel_".$field_id."_value').value = ".$object_id.";
                   window.opener.document.getElementById('nc_rel_".$field_id."_caption').innerHTML = \"".$field_caption."\";";
} else {
    echo " window.opener.document.getElementById('cs_".$cs_field_name."_value').value = '".$object_id."';
                window.opener.document.getElementById('cs_".$cs_field_name."_caption').innerHTML = '".$field_caption."';";
}
?>
              }
              catch(e) {
                  alert("<?=addslashes(NETCAT_MODERATION_RELATED_ERROR_SAVING)
?>");
              }
              window.close();
        </script>
    </head>

    <body></body>

</html>
