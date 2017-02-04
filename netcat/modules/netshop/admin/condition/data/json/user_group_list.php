<?php

require '../../../no_header.inc.php';

/**
 * Subdivision list for the tree in item selection dialog
 */

/* @var nc_db $db */
$db = nc_core('db');

$groups = $db->get_results("SELECT `PermissionGroup_ID`, `PermissionGroup_Name`
                              FROM `PermissionGroup`", ARRAY_A);

$ret = array();
foreach ($groups as $grp) {
    $ret[$grp["PermissionGroup_ID"]] = $grp["PermissionGroup_Name"];
}


print nc_array_json($ret);
