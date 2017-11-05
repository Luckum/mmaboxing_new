<?php

/* $Id: index.php 8333 2012-11-02 14:45:08Z vadim $ */

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ADMIN_FOLDER."function.inc.php");
require_once($INCLUDE_FOLDER."s_files.inc.php");
require_once($INCLUDE_FOLDER."s_common.inc.php");
require($ADMIN_FOLDER."template/function.inc.php");

$main_section = "control";
$item_id = 10;
$Delimeter = " &gt ";
$Title2 = CONTROL_TEMPLATE;
$Title3 = "<a href=\"".$ADMIN_PATH."template/\">".CONTROL_TEMPLATE."</a>";
$Title5 = GetTemplateDescription($TemplateID);
$Title6 = CONTROL_TEMPLATE_ADD;
$Title7 = CONTROL_TEMPLATE_EDIT;


$Title15 = "<a href=\"index.php?phase=4&TemplateID=$TemplateID\">".GetTemplateDescription($TemplateID)."</a>";
$Title16 = CONTROL_TEMPLATE_OPT_ADD;
$Title17 = CONTROL_TEMPLATE_OPT_EDIT;



if (!isset($phase)) {
    $phase = 1;
}

$File_Mode = +$_REQUEST['fs'];

if (in_array($phase, array(3, 5, 7))) {
    if (!$nc_core->token->verify()) {
        if ($_POST["NC_HTTP_REQUEST"] || NC_ADMIN_ASK_PASSWORD === false) { // AJAX call
            header($_SERVER['SERVER_PROTOCOL']." 401 Authorization Required");
            exit;
        }

        BeginHtml($Title2, $Title2, "");
        nc_print_status(NETCAT_TOKEN_INVALID, 'error');
        EndHtml();
        exit;
    }
}

$File_Mode = nc_get_file_mode('Template', $phase == 3 ? $_POST['ParentTemplateID'] : $TemplateID);

switch ($phase) {

    case 1:
        # покажем список всех темплейтов
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/design/");
        $perm->ExitIfNotAccess(NC_PERM_TEMPLATE, 0, 0, 0, 0);
        $UI_CONFIG = new ui_config_template('list', $TemplateID);
        FullTemplateList ();
        break;

    case 2:
        # покажем форму добавления темплейта
        BeginHtml($Title6, $Title3.$Delimeter.$Title6, "http://".$DOC_DOMAIN."/management/design/form/");
        $perm->ExitIfNotAccess(NC_PERM_TEMPLATE, 0, 0, 0, 0);
        TemplateForm(0, 3, 1, $File_Mode);
        break;

    case 3:
        # собственно добавление темлпейта
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/design/");
        $perm->ExitIfNotAccess(NC_PERM_TEMPLATE, 0, 0, 0, 1);
        if ($posting == 1 && $Description) {
            $systemMessageID = ActionTemplateCompleted($type, $File_Mode);
            if ($systemMessageID) {
                FormAsyncSaveConfigScript();
                TemplateForm($systemMessageID, 5, 2, $File_Mode, true);
            }
        } elseif ($posting == 1 && !$Description) {
            nc_print_status(CONTROL_TEMPLATE_ERR_NAME, 'error');
            TemplateForm(0, 3, 1, $File_Mode, true);
        }
        break;

    case 4:
        # покажем форму редактирования темплейта
        $AJAX_SAVER = true;
        if ($perm->isGuest()) $AJAX_SAVER = false;

        BeginHtml($Title7, $Title3.$Delimeter.$Title5, "http://".$DOC_DOMAIN."/management/design/form/");
        $perm->ExitIfNotAccess(NC_PERM_TEMPLATE, 0, 0, 0, 0);

        if (+$_REQUEST['isNaked']) {
            ob_clean();
            TemplateForm_for_modal($TemplateID, $File_Mode);
            exit;
        } else {
            TemplateForm($TemplateID, 5, 2, $File_Mode);
        }

        break;

    case 5:
        # собственно проапдейтим темплейт
        $AJAX_SAVER = true;
        if ($perm->isGuest()) $AJAX_SAVER = false;

        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/design/");
        $perm->ExitIfNotAccess(NC_PERM_TEMPLATE, 0, 0, 0, 1);

        if ($posting == 1 && $Description) {
            if (ActionTemplateCompleted($type, $File_Mode)) {
                /*if (+$_REQUEST['isNaked']) {
                    ob_clean();
                    echo 'OK';
                    exit;
                }*/
                TemplateForm($TemplateID, 5, 2, $File_Mode);
                global $UI_CONFIG;
                $updateNode = array(
                    "nodeId" => "template-{$TemplateID}",
                    "name" => $TemplateID.". ".$Description
                );
                $UI_CONFIG->treeChanges['updateNode'][] = $updateNode;
                $GLOBALS["_RESPONSE"]["ui_config"] = (array)$UI_CONFIG;
            }
        } elseif ($posting == 1 && !$Description) {
            if ($_POST["NC_HTTP_REQUEST"]) {
                $GLOBALS["_RESPONSE"]["error"] = CONTROL_TEMPLATE_ERR_NAME;
            }

            nc_print_status(CONTROL_TEMPLATE_ERR_NAME, 'error');
            TemplateForm($TemplateID, 5, 2, $File_Mode);
        }
        break;

    case 6:
        # удаление темплейтов
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/design/");
        $perm->ExitIfNotAccess(NC_PERM_TEMPLATE, 0, 0, 0, 1);
        AscIfDeleteTemplate();
        break;

    case 7:
        # удаление темплейтов
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/design/");
        $perm->ExitIfNotAccess(NC_PERM_TEMPLATE, 0, 0, 0, 1);
        DeleteTemplates ();
        $treeChanges = $UI_CONFIG->treeChanges;
        $UI_CONFIG = new ui_config_template('list', $TemplateID);
        $UI_CONFIG->treeChanges = $treeChanges;
        nc_print_status(CONTROL_TEMPLATE_DELETED, 'ok');
        FullTemplateList ();
        break;

    case 8:
        # список пользовательских настроек
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/design/");
        $UI_CONFIG = new ui_config_template('custom', $TemplateID);
        $perm->ExitIfNotAccess(NC_PERM_TEMPLATE, 0, 0, 0, 1);
        require_once $ADMIN_FOLDER.'array_to_form.inc.php';
        $custom_settings = $nc_core->template->get_by_id($TemplateID, 'CustomSettings');
        nc_customsettings_show(0, $TemplateID, $custom_settings);
        break;
    case 81:
        # массовое удаление пользовательских настроек
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/design/");
        $UI_CONFIG = new ui_config_template('custom', $TemplateID);
        $perm->ExitIfNotAccess(NC_PERM_TEMPLATE, 0, 0, 0, 1);
        require_once $ADMIN_FOLDER.'array_to_form.inc.php';
        $custom_settings = $nc_core->template->get_by_id($TemplateID, 'CustomSettings');
        $custom_settings = nc_customsettings_drop(0, $TemplateID, $custom_settings);
        nc_print_status(NETCAT_CUSTOM_PARAMETR_UPDATED, 'ok');
        nc_customsettings_show(0, $TemplateID, $custom_settings);
        break;
    case 9:
        # форма редактирования одного параметра
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/design/");
        $perm->ExitIfNotAccess(NC_PERM_TEMPLATE, 0, 0, 0, 1);
        $UI_CONFIG = new ui_config_template('custom', $TemplateID);
        $UI_CONFIG->locationHash = $param ? '#template.custom.edit('.$TemplateID.', '.$param.')' : '#template.custom.new('.$TemplateID.')';
        require_once $ADMIN_FOLDER.'array_to_form.inc.php';
        nc_customsettings_show_once(0, $TemplateID, $param);
        break;

    case 91:
        # добавлние/измнение одного параметра
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/design/");
        $perm->ExitIfNotAccess(NC_PERM_TEMPLATE, 0, 0, 0, 1);
        $UI_CONFIG = new ui_config_template('custom', $TemplateID);
        require_once $ADMIN_FOLDER.'array_to_form.inc.php';

        try {
            nc_customsettings_save_once ();
        } catch (Exception $e) {
            nc_print_status($e->getMessage(), 'error');
            nc_customsettings_show_once(0, $TemplateID, $param);
            break;
        }

        $custom_settings = $nc_core->template->get_by_id($TemplateID, 'CustomSettings');
        nc_print_status($param ? NETCAT_CUSTOM_PARAMETR_UPDATED : NETCAT_CUSTOM_PARAMETR_ADDED, 'ok');
        nc_customsettings_show(0, $TemplateID, $custom_settings);
        break;

    case 10:
        # ручное редактирование
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/design/");
        $perm->ExitIfNotAccess(NC_PERM_TEMPLATE, 0, 0, 0, 1);
        $UI_CONFIG = new ui_config_template('custom', $TemplateID);
        $UI_CONFIG->locationHash = '#template.custom.manual('.$TemplateID.')';
        nc_customsettings_show_manual(0, $TemplateID);
        break;

    case 101:
        # ручное редактирование
        BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/design/");
        $perm->ExitIfNotAccess(NC_PERM_TEMPLATE, 0, 0, 0, 1);
        $UI_CONFIG = new ui_config_template('custom', $TemplateID);
        $UI_CONFIG->locationHash = '#template.custom.manual('.$TemplateID.')';
        $nc_core->template->update($TemplateID, array('CustomSettings' => $nc_core->input->fetch_get_post('CustomSettings')));
        nc_print_status(NETCAT_CUSTOM_PARAMETR_UPDATED, 'ok');
        nc_customsettings_show_manual(0, $TemplateID);
        break;


    case 15:
        # вывод переменных и функций макета
        $BBCODE = true;
        BeginHtml($Title8, $Title8, "http://".$DOC_DOMAIN."/management/class/groupofclass/");
        $perm->ExitIfNotAccess(NC_PERM_TEMPLATE, 0, 0, 0, 0);
        nc_form_data_insert($formtype, $window, $form, $textarea);
        break;
}

EndHtml ();
?>