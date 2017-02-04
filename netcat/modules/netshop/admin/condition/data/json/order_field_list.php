<?php

require '../../../no_header.inc.php';

/**
 * Order table fields (id: name) as JSON
 *
 * Input:
 *   - site_id
 */

/** @var nc_input $input */
$input = nc_core('input');
$netshop = nc_netshop::get_instance((int)$input->fetch_get_post('site_id'));
$order_component_id = (int)$netshop->get_setting('OrderComponentID');

$fields = nc_db()->get_results("SELECT `Field_ID`, `Field_Name`, `Description`, `TypeOfData_ID`, `Format`
                                  FROM `Field`
                                 WHERE `Class_ID` = $order_component_id
                                   AND " . nc_netshop_condition_admin_helpers::get_field_types_to_export_for_query() . "
                                 ORDER BY `Priority`", ARRAY_A);

$fields_to_skip = array("Status", "DeliveryMethod", "PaymentMethod");

$result = array();
foreach ($fields as $field) {
    if (in_array($field["Field_Name"], $fields_to_skip)) { continue; }
    $result[$field["Field_Name"]] = nc_netshop_condition_admin_helpers::export_field($field, null, true);
}

echo nc_array_json($result);