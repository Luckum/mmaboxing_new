<?php
/**
 * Returns user caption / name
 *
 * Input: 'user_id'
 *
 * Output: json { User_ID: login+name }
 */

require '../../../no_header.inc.php';

/** @var nc_input $input */
$input = nc_core('input');
$user_id = (int)$input->fetch_get_post('user_id');

if (!$user_id) { trigger_error("Incorrect user_id parameter", E_USER_ERROR); }

/** @var nc_db $db */
$db = nc_core('db');

// `User_ID`, `FullName`, `Login`
$data = $db->get_row("SELECT *
                       FROM `User`
                      WHERE `User_ID` = $user_id", ARRAY_A);

$data["Name"] = "[".$data[nc_core()->AUTHORIZE_BY]."] $data[ForumName]"; // ≈ readable name

echo nc_array_json($data);