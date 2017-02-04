<?php
/**
 * Returns object caption / name
 *
 * Input: 'object', formatted as "component_id:object_id" (i.e., ":"-delimited)
 * E.g. "20:567"
 *
 * Output: json (contains only 'Name' entry)
 */

require '../../../no_header.inc.php';

/** @var nc_input $input */
$input = nc_core('input');
$object = $input->fetch_get_post('object');

if (!preg_match("/^(\d+):(\d+)$/", $object, $match)) { die; }
$component_id = $match[1];
$object_id = $match[2];

$item = nc_netshop_item::by_id($component_id, $object_id);
$data = array(
    'Message_ID' => $object_id,
    'FullName' => $item["FullName"]
);

echo nc_array_json($data);