<?

/* $Id: dump.php 8564 2012-12-27 10:49:12Z aix $ */

require_once ("../require/s_common.inc.php");
require ("function.inc.php");
require ("dump.inc.php");
require ("tar.inc.php");


global $HTTP_HOST;
$Delimeter = " &gt; ";
$main_section = "settings";
$item_id = 8;
$Title1 = "";
$Title2 = TOOLS_DUMP;
$Title3 = "<a href='".$ADMIN_PATH."dump.php'>".TOOLS_DUMP."</a>";
$Title4 = TOOLS_DUMP_CREATE;
$Title5 = TOOLS_DUMP;
$Title6 = TOOLS_DUMP_RESTORE;
$Title7 = TOOLS_DUMP_CREATE;

$UI_CONFIG = new ui_config_tool(TOOLS_DUMP, TOOLS_DUMP, 'i_tool_backup_big.gif', 'tools.backup');

$memory_limit = 512;
if ((int) ini_get('memory_limit') < $memory_limit) {
    ini_set('memory_limit', $memory_limit . 'M');
}

//if win
if (substr(php_uname(), 0, 7) == "Windows") {
    $isWin = 1;
} else {
    $isWin = 0;
}

// установка обновления не доступна в демо-версии
if ($nc_core->is_trial) {
    BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/settings/dump/");
    $perm->ExitIfNotAccess(NC_PERM_MODULE, 0, 0, 0, 1);
    nc_print_status(TOOLS_PATCH_NOTAVAIL_DEMO, 'error');
    EndHtml();
    exit();
}

if (!$perm->isSupervisor()) {
    BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/settings/dump/");
    nc_print_status($NO_RIGHTS_MESSAGE, "error");
    EndHtml ();
    exit;
}


if ($phase) {
    switch ($phase) {
        case 1:
            //Само архивирование
            if ($AUTHORIZATION_TYPE == "session") {
                header("Location:".$ADMIN_PATH."dump.php?".session_name()."=".session_id());
            }
            BeginHtml($Title5, $Title2, "http://".$DOC_DOMAIN."/settings/dump/");

	    $backup_options = array('mode' => 'full', 'standalone' => true); // Default settings

	    if (isset($_POST['backup_mode'])) {
		    $backup_mode = $_POST['backup_mode'];
		    switch($backup_mode) {
		    case 'full':
			$backup_options['mode'] = 'full';
			$backup_options['no_standalone'] = false;
			break;
		    case 'simple':
		    	$backup_options['mode'] = 'default';
			$backup_options['no_standalone'] = true;
			break;
		    case 'sql':
			$backup_options['mode'] = 'sql';
			$backup_options['no_standalone'] = true;
			break;
		}
	    }

            mkDump(false, $backup_options);
            showUploadForm();
            print "<br>";
            ShowBackUps();
            break;

        case 2:
            //Удаление файла
            BeginHtml($Title5, $Title2, "http://".$DOC_DOMAIN."/settings/dump/");
            DeleteDump($del);
            showUploadForm();
            print "<br>";
            ShowBackUps();
            break;

        case 3:
            BeginHtml($Title6, $Title3." > ".$Title6, "http://".$DOC_DOMAIN."/settings/dump/");
            DumpQuery($file);
            break;

        case 4:
            BeginHtml($Title2, $Title5, "http://".$DOC_DOMAIN."/settings/dump/");
            mkDump();
            break;

        case 5:
            BeginHtml($Title2, $Title5, "http://".$DOC_DOMAIN."/settings/dump/");
            AskDump();
            break;

        case 6:
            BeginHtml($Title2, $Title5, "http://".$DOC_DOMAIN."/settings/dump/");
            $database = 0;
            $netcat_template = 0;
            $netcat_files = 0;
            $images = 0;
            $modules = 0;
            if (checkBox($what, "database")) $database = 1;
            if (checkBox($what, "netcat_template")) $netcat_template = 1;
            if (checkBox($what, "netcat_files")) $netcat_files = 1;
            if (checkBox($what, "images")) $images = 1;
            if (checkBox($what, "modules")) $modules = 1;
            $err = ReadBackUP($file, $images, $netcat_files, $database, $modules, 0, $netcat_template);
            if (!$err) {
                nc_print_status(TOOLS_DUMP_MSG_RESTORED, 'ok');
            } else {
                nc_print_status($err, 'error');
            }
            break;


        case 7:
            BeginHtml($Title2, $Title5, "http://".$DOC_DOMAIN."/settings/dump/");
            if (!$_FILES['filename']['tmp_name']) {
                nc_print_status(TOOLS_MODULES_ERR_NOTUPLOADED, "error");
                showUploadForm();
                print "<br>";
                ShowBackUps();
                break;
            }
            $file = getFile($_FILES['filename']['tmp_name'], $_FILES['filename']['name']);
            $database = 0;
            $netcat_template = 0;
            $netcat_files = 0;
            $images = 0;
            $modules = 0;

            if (checkBox($what, "database")) $database = 1;
            if (checkBox($what, "netcat_template")) $netcat_template = 1;
            if (checkBox($what, "netcat_files")) $netcat_files = 1;
            if (checkBox($what, "images")) $images = 1;
            if (checkBox($what, "modules")) $modules = 1;

            $err = ReadBackUP($file, $images, $netcat_files, $database, $modules, 1, $netcat_template);
            if (!$err) {
                nc_print_status(TOOLS_DUMP_MSG_RESTORED, "ok");
                showUploadForm();
                print "<br>";
                ShowBackUps();
            } else {
                nc_print_status($err, "error");
                showUploadForm();
                print "<br>";
                ShowBackUps();
            }
            break;
    }
} else {
    BeginHtml($Title5, $Title2, "http://".$DOC_DOMAIN."/settings/dump/");
    showUploadForm();
    print "<br>";
    ShowBackUps();
}

EndHtml ();
?>
