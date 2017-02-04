<?php

require '../../../no_header.inc.php';

/**
 * JSON list of all 'goods' components
 */

/** @var nc_netshop $shop */
$shop = nc_modules('netshop');

$results = array();
$rows = $shop->get_goods_components_data('Class_Name');

foreach ($rows as $row) {
    $results[$row['Class_ID']] = "$row[Class_ID]. $row[Class_Name]";
}

echo nc_array_json($results);