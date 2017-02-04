<?
/* $Id: save.php 5946 2012-01-17 10:44:36Z denis $ */

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");
require_once ($INCLUDE_FOLDER."s_common.inc.php");

$classID = (int) $classID;
$message = (int) $message;
$cc = (int) $cc;

if (!$classID || !$message || !$cc) {
    trigger_error("Wrong params", E_USER_ERROR);
}

if (!$nc_core->token->verify()) {
    BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/class/");
    nc_print_status(NETCAT_TOKEN_INVALID, 'error');
    EndHtml();
    exit;
}

nc_copy_message($classID, $message, $cc);

$reload_frame = ($cc == $db->get_var("SELECT `Sub_Class_ID` FROM `Message".$classID."` WHERE `Message_ID` = '".$message."' LIMIT 1"));
?>

<html>
    <head>
        <title></title>

        <script type="text/javascript" >
  
<?php if ($reload_frame)
        echo "opener.window.location.reload();" ?>

                 alert("<?=addslashes(NETCAT_MODERATION_COPY_SUCCESS) ?>");
             window.close();

        </script>
    </head>

    <body></body>

</html>