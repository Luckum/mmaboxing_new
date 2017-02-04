<?php

require '../../../no_header.inc.php';

/**
 * Subdivision list for the <select> (subdivision selection dialog)
 */

/** @var nc_input $input */
$input = nc_core('input');
$node = $input->fetch_get_post('node');
$site_id = (int)$input->fetch_get_post('site_id');

if (!$site_id) { trigger_error("'site_id' parameter is required", E_USER_ERROR); }

$result = array();
$subdivisions = nc_netshop_condition_admin_helpers::get_subdivisions_with_goods($site_id);

foreach ($subdivisions as $sub) {
    $result[$sub['Subdivision_ID']] =
        str_repeat("&nbsp; &nbsp; &nbsp", $sub["Depth"] - 1) .
        "$sub[Subdivision_ID]. $sub[Subdivision_Name]";
}

echo nc_array_json($result);