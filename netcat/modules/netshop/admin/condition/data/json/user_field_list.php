<?php

require '../../../no_header.inc.php';

/**
 * User table fields (id: name) as JSON
 */

/* @var nc_db $db */
$db = nc_core('db');

/*
$fields = $db->get_results("SELECT f.`Field_ID`, f.`Field_Name`, f.`Description`, f.`TypeOfData_ID`, f.`Format`
                              FROM `System_Table` AS t, `Field` as f
                             WHERE t.`System_Table_Name` = 'User'
                               AND t.`System_Table_ID` = f.`System_Table_ID`
                               AND f." . nc_netshop_condition_admin_helpers::get_field_types_to_export_for_query() . "
                             ORDER BY f.`Priority`", ARRAY_A);
*/
$fields = $db->get_results("SELECT `Field_ID`, `Field_Name`, `Description`, `TypeOfData_ID`, `Format`
                              FROM `Field`
                             WHERE `System_Table_ID` = 3
                               AND " . nc_netshop_condition_admin_helpers::get_field_types_to_export_for_query() . "
                             ORDER BY `Priority`", ARRAY_A);

$result = array();
foreach ($fields as $field) {
    $result[$field["Field_Name"]] = nc_netshop_condition_admin_helpers::export_field($field, null, true);
}

echo nc_array_json($result);