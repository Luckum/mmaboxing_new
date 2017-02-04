<?php

// Если скрипт вызывают напрямую а не через modules_json.php
if (empty($NETCAT_FOLDER)) {

    $NETCAT_FOLDER = realpath(dirname(__FILE__) . '/../../..') . DIRECTORY_SEPARATOR;
    require_once $NETCAT_FOLDER . "vars.inc.php";
    require_once $ADMIN_FOLDER . "function.inc.php";

    // Показываем дерево разработчика, если у пользователя есть на это права
    if (!$perm->isAccess(NC_PERM_MODULE, 0, 0, 0)) {
        exit(NETCAT_MODERATION_ERROR_NORIGHT);
    }
}

//--------------------------------------------------------------------------

if (empty($nc_core)) {
    $nc_core = nc_core();
}

//--------------------------------------------------------------------------

$module_node_id = "module-" . $module['Module_ID'];

// Возвращаем путь (массив с ключами родительских элементов) к текущему разделу
if ($nc_core->input->fetch_get('action') == 'get_path') {
    $ret = array($module_node_id);
    echo nc_array_json($ret);
    exit;
}

//--------------------------------------------------------------------------

$node_children = array();

switch ($node_type) {

    case 'module':
        $node_children = array(
            // Заказы
            array(
                "nodeId" => "netshop-order",
                "parentNodeId" => $module_node_id,
                "name" => NETCAT_MODULE_NETSHOP_SALES,
                "href" => "#module.netshop.order",
                "sprite" => "folder-dark",
                "hasChildren" => false,
                "expand" => false,
            ),
            // Статистика
            array(
                "nodeId" => "netshop-statistics",
                "parentNodeId" => $module_node_id,
                "name" => NETCAT_MODULE_NETSHOP_STATISTICS,
                "href" => "#module.netshop.statistics",
                "sprite" => "folder-dark",
                "hasChildren" => false,
                "expand" => false,
            ),
            // --- 1c ---
            array(
                "nodeId" => "netshop-1c",
                "parentNodeId" => $module_node_id,
                "name" => NETCAT_MODULE_NETSHOP_1C_INTEGRATION,
                "href" => "#module.netshop.1c.sources",
                "sprite" => "folder-dark",
                "hasChildren" => true,
                "expand" => false,
            ),
            array(
                "nodeId" => "netshop-1c.import",
                "parentNodeId" => "netshop-1c",
                "name" => NETCAT_MODULE_NETSHOP_1C_INTEGRATION_IMPORT,
                "href" => "#module.netshop.1c.import",
                "sprite" => "folder-dark",
                "hasChildren" => false,
                "expand" => false,
            ),

            // --- markets ---
            array(
                "nodeId" => "netshop-markets",
                "parentNodeId" => $module_node_id,
                "name" => NETCAT_MODULE_NETSHOP_MARKETS,
                "href" => "#module.netshop.yandex",
                "sprite" => "folder-dark",
                "hasChildren" => true,
                "expand" => true
            ),
            // --- yandex ---
            array(
                "nodeId" => "netshop-yandex",
                "parentNodeId" => "netshop-markets",
                "name" => NETCAT_MODULE_NETSHOP_YANDEX_MARKET,
                "href" => "#module.netshop.yandex",
                "sprite" => "folder-dark",
                "hasChildren" => false
            ),

            // --- discounts ---
            array(
                "nodeId" => "netshop-promotion.discount",
                "parentNodeId" => $module_node_id,
                "name" => NETCAT_MODULE_NETSHOP_PROMOTION_DISCOUNTS,
                "href" => "#module.netshop.promotion.discount.item",
                "sprite" => "folder-dark",
                "hasChildren" => true,
                "expand" => false,
            ),
            array(
                "nodeId" => "netshop-promotion.discount.item",
                "parentNodeId" => "netshop-promotion.discount",
                "name" => NETCAT_MODULE_NETSHOP_PROMOTION_ITEM_DISCOUNTS,
                "href" => "#module.netshop.promotion.discount.item",
                "sprite" => "folder-dark",
                "hasChildren" => false,
            ),
            array(
                "nodeId" => "netshop-promotion.discount.delivery",
                "parentNodeId" => "netshop-promotion.discount",
                "name" => NETCAT_MODULE_NETSHOP_PROMOTION_DELIVERY_DISCOUNTS,
                "href" => "#module.netshop.promotion.discount.delivery",
                "sprite" => "folder-dark",
                "hasChildren" => false,
            ),

            // --- settings ---
            array(
                "nodeId" => "netshop-settings",
                "parentNodeId" => $module_node_id,
                "name" => NETCAT_MODULE_NETSHOP_SETTINGS,
                "href" => "#module.netshop.settings",
                "sprite" => "settings",
                "hasChildren" => true,
                "expand" => false,
            ),

            // --- currency ---
            array(
                "nodeId" => "netshop-currency",
                "parentNodeId" => "netshop-settings",
                "name" => NETCAT_MODULE_NETSHOP_CURRENCIES,
                "href" => "#module.netshop.currency",
                "sprite" => "folder-dark",
                "hasChildren" => false,
                "expand" => false,
            ),

            // --- pricerule ---
            array(
                "nodeId" => "netshop-pricerule",
                "parentNodeId" => "netshop-settings",
                "name" => NETCAT_MODULE_NETSHOP_PRICE_RULES_TAB,
                "href" => "#module.netshop.pricerule",
                "sprite" => "folder-dark",
                "hasChildren" => false,
                "expand" => false,
            ),

            // --- delivery ---
            array(
                "nodeId" => "netshop-delivery",
                "parentNodeId" => "netshop-settings",
                "name" => NETCAT_MODULE_NETSHOP_DELIVERY,
                "href" => "#module.netshop.delivery",
                "sprite" => "folder-dark",
                "hasChildren" => false,
                "expand" => false,
            ),

            // --- payment ---
            array(
                "nodeId" => "netshop-payment",
                "parentNodeId" => "netshop-settings",
                "name" => NETCAT_MODULE_NETSHOP_PAYMENT,
                "href" => "#module.netshop.payment",
                "sprite" => "folder-dark",
                "hasChildren" => false,
                "expand" => false,
            ),

            // --- mailer ---
            array(
                "nodeId" => "netshop-mailer",
                "parentNodeId" => "netshop-settings",
                "name" => NETCAT_MODULE_NETSHOP_MAILER_ROOT,
                "href" => "#module.netshop.mailer.template",
                "sprite" => "folder-dark",
                "hasChildren" => false,
                "expand" => false,
            ),

            array(
                "nodeId" => "netshop-forms",
                "parentNodeId" => "netshop-settings",
                "name" => NETCAT_MODULE_NETSHOP_FORMS,
                "href" => "#module.netshop.forms",
                "sprite" => "folder-dark",
                "hasChildren" => false,
                "expand" => false,
            ),
        );


}


echo nc_array_json($node_children);