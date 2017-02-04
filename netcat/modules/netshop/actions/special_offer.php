<?php

/**
 * Принятие и отклонение сиюминутного предложения
 *
 * Запросы должны выполняться методом GET.
 * Входящие параметры:
 *
 *   — action: accept (принятие предложения) или reject (отклонение предложения)
 *   — class_id: идентификатор компонента товара
 *   — item_id: идентификатор объекта
 *   — redirect_url: страница, куда нужно переадресовать после действия
 *     (если не задано, скрипт ничего не выведет)
 *
 */

require realpath(dirname(__FILE__) . "/../../../../") . "/vars.inc.php";
require_once $INCLUDE_FOLDER . "index.php";

/** @var nc_input $input */
$input = nc_core('input');
$netshop = nc_netshop::get_instance();

$action = $input->fetch_get('action');
$class_id = $input->fetch_get('class_id');
$item_id = $input->fetch_get('item_id');

if (in_array($class_id, $netshop->get_goods_components_ids())) {
    $item = nc_netshop_item::by_id($class_id, $item_id);

    if ($action == 'accept') {
        $netshop->promotion->accept_special_offer($item);
    }
    else if ($action == 'reject') {
        $netshop->promotion->reject_special_offer($item);
    }
}

$redirect_url = $input->fetch_get('redirect_url');
if ($redirect_url) {
    header('Location: ' . $redirect_url);
}