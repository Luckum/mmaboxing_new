<?php

/**
 * Изменение рейтинга товара
 *
 * Запросы должны выполняться методом GET.
 * Входящие параметры:
 *
 *   - class_id: идентификатор компонента товара
 *
 *   - item_id: идентификатор объекта
 *
 *   - rate: значение рейтинга
 *
 */

require realpath(dirname(__FILE__) . "/../../../../") . "/vars.inc.php";
require_once $INCLUDE_FOLDER . "index.php";

/** @var nc_input $input */
$input = nc_core('input');

$class_id = (int)$input->fetch_get('class_id');
$item_id = (int)$input->fetch_get('item_id');
$rate = (int)$input->fetch_get('rate');

if ($rate < 0) { $rate = 0; }
if ($rate > 5) { $rate = 5; }

$cookie_name = 'nc_rate_' . $class_id . '_' . $item_id;

$netshop = nc_netshop::get_instance();

if ($rate && in_array($class_id, $netshop->get_goods_components_ids())) {
    if (!isset($_COOKIE[$cookie_name])) {
        $expire = time() + 2592000;
        setcookie($cookie_name, 1, $expire, "/");

        $sql = "UPDATE `Message{$class_id}`
                   SET `RateTotal` = IFNULL(`RateTotal`,0) + $rate,
                       `RateCount` = IFNULL(`RateCount`,0) + 1
                 WHERE `Message_ID` = $item_id";

        nc_core('db')->query($sql);
    }
}

$redirect_url = $_SERVER['HTTP_REFERER'];
if ($redirect_url) {
    header('Location: ' . $redirect_url);
}
