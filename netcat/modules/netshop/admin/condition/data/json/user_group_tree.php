<?php

require '../../../no_header.inc.php';

/**
 * User group list for the tree in user selection dialog
 */

/* @var nc_db $db */
$db = nc_core('db');

$groups = $db->get_results("SELECT `PermissionGroup_ID`, `PermissionGroup_Name`
                              FROM `PermissionGroup`", ARRAY_A);

$ret = array();
foreach ($groups as $grp) {
    $id = $grp["PermissionGroup_ID"];
    $ret[] = array(
        "nodeId" => "usergroup-$id",
        "name" => $grp["PermissionGroup_Name"],
        "href" => "",
        "action" => "return this.actions.selectNode($id);",
        "sprite" => "user-group",
        "hasChildren" => false,
        "dragEnabled" => false,
    );
}


print nc_array_json($ret);
