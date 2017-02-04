<?php
require_once("old/header.inc.php");
require_once("sources.inc.php");

$UI_CONFIG = new ui_config_module_netshop('admin', '1c');
$UI_CONFIG->subheaderText    = NETCAT_MODULE_NETSHOP_SOURCES;
$UI_CONFIG->treeSelectedNode = false;
$UI_CONFIG->toolbar          = false;
$UI_CONFIG->tabs             = false;
$UI_CONFIG->locationHash = "module.netshop.1c.sources";

if (!isset($phase)) {
    $phase = 1;
}

//BeginHtml();

switch ($phase) {
    case 1:
        //Вывод всех источников
        SourcesList();
        break;

    case 2:
        //Вывод информации по источнику
        $source_id = $source_id ? (int)$source_id : 0;
        if (!ViewSource($source_id)) {
            nc_print_status(NETCAT_MODULE_NETSHOP_SOURCES_NOT_EXISTS_SOURCE, 'info');
            SourcesList();
        }
        break;

    case 3:
        //Подтверждение удаления источников
        if (!DeleteConfirmationForm()) {
            nc_print_status(NETCAT_MODULE_NETSHOP_SOURCES_SOURCES_NOT_SELECTED, 'info');
        }
        break;

    case 4:
        //Удаление источников
        if (DeleteSources()) {
            nc_print_status(NETCAT_MODULE_NETSHOP_SOURCES_SOURCES_DELETED, 'ok');
        } else {
            nc_print_status(NETCAT_MODULE_NETSHOP_SOURCES_SOURCES_DELETE_ERROR, 'error');
        }
        SourcesList();
        break;

    case 5:
        //Просмотр остатков склада
        $store_id = $store_id ? (int)$store_id : 0;
        if (!ViewStore($store_id)) {
            nc_print_status(NETCAT_MODULE_NETSHOP_SOURCES_NOT_EXISTS_STORE, "info");
        }
        break;
    case 6:
        //Редактирование соответствий полей
        $source_id = $source_id ? (int)$source_id : 0;
        if (!ViewSourceMapping($source_id)) {
            nc_print_status(NETCAT_MODULE_NETSHOP_SOURCES_NOT_EXISTS_SOURCE, "info");
        }
        break;
    case 7:
        //Сохранение соответсвий полей
        $source_id = $source_id ? (int)$source_id : 0;
        if (SaveSourceMapping($source_id)) {
            nc_print_status(NETCAT_MODULE_NETSHOP_SOURCES_MAPPING_SAVED, 'ok');
        } else {
            nc_print_status(NETCAT_MODULE_NETSHOP_SOURCES_MAPPING_NOT_SAVED, 'error');
        }
        SourcesList();
        break;
    case 8:
        //Сохранение настроек источника
        $source_id = $source_id ? (int)$source_id : 0;
        if (SaveSource($source_id)) {
            nc_print_status(NETCAT_MODULE_NETSHOP_SOURCES_SOURCE_SAVED, 'ok');
        } else {
            nc_print_status(NETCAT_MODULE_NETSHOP_SOURCES_SOURCE_NOT_SAVED, 'error');
        }
        ViewSource($source_id);
        break;
}

EndHtml();