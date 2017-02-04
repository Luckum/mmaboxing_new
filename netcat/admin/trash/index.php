<?php

/* $Id */
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");
require ($ADMIN_FOLDER."trash/function.inc.php");

$Delimeter    = " &gt ";
$main_section = "list";
$Title2       = "<a href=\"".$ADMIN_PATH."trash.php\">".TOOLS_TRASH."</a>";

if (!isset($phase)) $phase = 1;

$UI_CONFIG = new ui_config_trash(TRASH_TAB_LIST, 'list', TRASH_TAB_TITLE);

/* Проверка token-а нужна только для сохранения настроек и восстановлениях */
if (in_array($phase, array(2, 4, 5, 6))) {
    if (!$nc_core->token->verify()) {
        BeginHtml(TOOLS_TRASH, $Title2, "http://".$DOC_DOMAIN."/tools/trash/");
        nc_print_status(NETCAT_TOKEN_INVALID, 'error');
        EndHtml();
        exit;
    }
}

if (!$perm->accessToTrash()) {
    die(NETCAT_MODERATION_ERROR_NORIGHTS);
}

switch ($phase) {
    case 1: // список удаленных объектов
        BeginHtml(TOOLS_TRASH, $Title2, "http://".$DOC_DOMAIN."/tools/trash/");
        nc_trash_list(isset($options) ? $options : array());
        break;
    case 2: // Восстановление по номерам в корзине, форма восстановление раздела
        BeginHtml(TOOLS_TRASH, $Title2, "http://".$DOC_DOMAIN."/tools/trash/");
        // форма создания раздела
        if (nc_trash_prerecovery($trash_ids)) {
            break;
        }
    // break не нужен
    case 21: // сообственно восстановление
        if ($phase != 2)
                BeginHtml(TOOLS_TRASH, $Title2, "http://".$DOC_DOMAIN."/tools/trash/");
        if ($sub_name) {
            nc_trash_recovery_sub($trash_ids);
            print "<script>top.frames['treeIframe'].window.location.reload(); </script>";
        }
        if (($c = $nc_core->trash->recovery($trash_ids))) {
            nc_print_status(nc_numeral_inclination($c, array(NETCAT_TRASH_RECOVERED_SK1, NETCAT_TRASH_RECOVERED_SK2, NETCAT_TRASH_RECOVERED_SK3))." ".$c." ".nc_numeral_inclination($c, array(NETCAT_TRASH_MESSAGES_SK1, NETCAT_TRASH_MESSAGES_SK2, NETCAT_TRASH_MESSAGES_SK3)), 'ok');
        }
        nc_trash_list();
        break;

    case 3: // Удаление из корзины объектов
        BeginHtml(TOOLS_TRASH, $Title2, "http://".$DOC_DOMAIN."/tools/trash/");
        require_once ( $INCLUDE_FOLDER."s_files.inc.php" );
        if (($removed = $nc_core->trash->delete($trash_ids))) {
            nc_print_status($removed.' '.nc_numeral_inclination($removed, array(NETCAT_ADMIN_TRASH_OBJECT_HAS_BEEN_REMOVED, NETCAT_ADMIN_TRASH_OBJECTS_REMOVED, NETCAT_ADMIN_TRASH_OBJECT_IS_REMOVED)), 'info');
        }
        nc_trash_list();
        break;
    case 4: // очистка корзины
        # Очистка корзины
        $UI_CONFIG = new ui_config_trash(TRASH_TAB_LIST, 'list', TRASH_TAB_TITLE, TRASH_TAB_SETTINGS);
        BeginHtml(TOOLS_TRASH, $Title2, "http://".$DOC_DOMAIN."/tools/trash/");
        require_once ( $INCLUDE_FOLDER."s_files.inc.php" );
        $nc_core->trash->clean();
        nc_print_status(NETCAT_ADMIN_TRASH_TRASH_HAS_BEEN_SUCCESSFULLY_CLEARNED, 'ok');
        nc_trash_list();
        break;
}

EndHtml();
?>