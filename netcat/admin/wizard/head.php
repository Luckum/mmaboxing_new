<?
/* $Id: head.php 5946 2012-01-17 10:44:36Z denis $ */
// общий заголовок для select_*

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");
?>
<html>
    <head>
        <title><?=WIZARD_PARENTSUB_SELECT_POPUP_TITLE
?></title>
        <link rel='stylesheet' type='text/css' media='screen' href='<?=$ADMIN_TEMPLATE
?>css/main.css'>
        <link rel='stylesheet' type='text/css' media='screen' href='<?=$ADMIN_TEMPLATE
?>css/admin.css'>
        <script type='text/javascript' src='<?=$ADMIN_PATH
?>js/lib.js'></script>
        <script type='text/javascript' src='<?=$ADMIN_PATH
?>js/container.js'></script>
        <script>
            function selectItem(catId,subId) {
                window.location = '<?=$ADMIN_PATH
?>wizard/save.php?cat_id='+catId+'&sub_id='+subId;
            }
        </script>
    </head>