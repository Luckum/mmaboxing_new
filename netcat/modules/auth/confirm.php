<?php

/* $Id: confirm.php 4714 2011-05-20 10:48:13Z denis $ */

ob_start();

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require_once ($INCLUDE_FOLDER."index.php");
require_once ($ADMIN_FOLDER."admin.inc.php");

$id = intval($id);
$code = $db->escape($code);
$nc_user_confirm = 0;

if ($id && $code) {
    $IsChecked = ( $nc_core->get_settings('premoderation', 'auth') ) ? 0 : 1;

    // подтверждение пользователя
    $res = $db->query("UPDATE `User`
		SET `Confirmed` = '1', `RegistrationCode` = ''".($IsChecked ? ", `Checked` = '".$IsChecked."'" : "")."
		WHERE `RegistrationCode` = '".$code."' AND `User_ID` = '".$id."'");
    $nc_user_confirm = $db->rows_affected;
    unset($res);

    // если пользователь включен и стоит опция "авторизация после подтверждения"
    if ($nc_user_confirm && $IsChecked && $nc_core->get_settings('autoauthorize', 'auth')) {
        Authorize($id, 'authorize');
    }
}

if ($nc_user_confirm) { // успешное подтверждение
    $CheckActionTemplate = $db->get_var("SELECT `CheckActionTemplate` FROM `Class`
                                       WHERE `System_Table_ID`=3 AND `ClassTemplate` = 0 ");
    eval("echo \"".$CheckActionTemplate."\";");
    echo "<div>".NETCAT_MODULE_AUTH_REG_OK."</div>";
} elseif (!$id || !$code) { // неправильная ссылка
    echo NETCAT_MODULE_AUTH_REG_INVALIDLINK;
} else { // пользователь не найден
    echo "<div>".NETCAT_MODULE_AUTH_REG_ERROR."</div>";
}

$nc_result_msg = ob_get_clean();

if ($File_Mode) {
    require_once $INCLUDE_FOLDER.'index_fs.inc.php';
    
    echo $template_header;
    echo $nc_result_msg;
    echo $template_footer;
} else {
    eval("echo \"".$template_header."\";");
    echo $nc_result_msg;
    eval("echo \"".$template_footer."\";");
}

?>