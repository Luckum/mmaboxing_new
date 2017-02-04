<?php

/* $Id: index.php 5266 2011-09-01 14:50:02Z gaika $ */
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once($NETCAT_FOLDER."vars.inc.php");
require_once ($ROOT_FOLDER."connect_io.php");

$nc_core->modules->load_env();

$ajax = 0;

if ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' || $nc_core->input->fetch_get_post('ajax')) {
    $ajax = 1;
}

if ($adminact == 'check_discount') {
    Authorize();
    if (!is_object($perm) || !$perm->isSupervisor()) exit;

    require_once ($MODULE_FOLDER."minishop/nc_minishop_admin.class.php");
    $nc_minishop_admin = new nc_minishop_admin();
    die(json_encode($nc_minishop_admin->make_discount()));
}

$result = true;
$hash = array();
$good = $nc_core->input->fetch_get_post('good');
if (!empty($good))
        foreach ($good as $key => &$val) {

        if ($massput && (!isset($val['quantity']) || (isset($val['quantity']) && $val['quantity']+0 <= 0))) {
            continue;
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            foreach ($val as &$value)
                $value = rawurldecode($value);
        }

        if (!$nc_minishop->in_cart($val['name'], $val['price'], $val['quantity'])) {
            $hash[] = $val['hash'];
            if (!$nc_minishop->put_good($val['name'], $val['price'], $val['hash'], $val['quantity'] ? $val['quantity'] : 1, $val['uri'] ? $val['uri'] : '')) {
                $result = false;
                break;
            }
        }
    }

if (!$result) {
    die($ajax ? "{status: 'error'}" : NETCAT_MODULE_MINISHOP_GOOD_PUT_IN_CART_ERROR);
}

// axaj - вернуть результат в JSON
if ($ajax) {
    $result = array('status' => 'ok');
    $result['cart'] = $nc_minishop->show_cart_state();
    if (!$nc_core->NC_UNICODE)
            $result['cart'] = $nc_core->utf8->win2utf($result['cart']);
    $result['incart'] = $nc_minishop->show_put_button('', 1, '', 0, 1);
    if (!$nc_core->NC_UNICODE)
            $result['incart'] = $nc_core->utf8->win2utf($result['incart']);
    $result['hash'] = $hash;
    $result['notify'] = $nc_minishop->get_notify();
    if (!$nc_core->NC_UNICODE)
            $result['notify']['text'] = $nc_core->utf8->win2utf($result['notify']['text']);
    echo json_encode($result);
}
else { // не ajax - делаем редирект
    $redirect_url = ($_POST["redirect_url"] ? $_POST["redirect_url"] : $HTTP_REFERER);
    if ($redirect_url) {
        ob_end_clean();
        //session_write_close();

        header("Location: $redirect_url");
        die();
    }
}
?>