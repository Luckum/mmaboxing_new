<?php

require '../../../no_header.inc.php';

/**
 * Order table fields (id: name) as JSON
 *
 * Input:
 *   - site_id
 *
 */

/** @var nc_input $input */
$input = nc_core('input');
$netshop = nc_netshop::get_instance((int)$input->fetch_get_post('site_id'));

$result = array();
foreach ($netshop->delivery->get_all_methods() as $method) {
    /** @var nc_netshop_delivery_method $method */
    $result[$method->get_id()] = $method->get('name');
}

echo nc_array_json($result);