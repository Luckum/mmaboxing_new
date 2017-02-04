<?php

/**
 * Input:
 *   - code --- code to check
 *   - catalogue_id
 *
 * Response: JSON object
 *   - code
 *   - is_ok
 *   - error_message (optional)
 */

ob_start();
require_once '../../header.inc.php';

ob_end_clean();

$db = nc_core('db');
$input = nc_core('input');
$code = $input->fetch_get_post('code');
$code = trim($code);
$catalogue_id = (int)$input->fetch_get_post('catalogue_id');

$exists = (bool)$db->get_var("SELECT 1 as 'result'
                                FROM `Netshop_Coupon`
                               WHERE `catalogue_id` = $catalogue_id
                                 AND `code` = '" . $db->escape($code) . "'");

$result = array(
    'code' => $code,  // needed in the callback which processes the response
    'is_ok' => !$exists
);

if ($exists) {
    $result['error_message'] = NETCAT_MODULE_NETSHOP_PROMOTION_COUPON_WITH_THIS_CODE_ALREADY_EXISTS;
}


echo nc_array_json($result);