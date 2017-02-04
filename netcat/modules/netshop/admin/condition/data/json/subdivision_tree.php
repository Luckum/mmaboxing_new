<?php

require '../../../no_header.inc.php';

/**
 * Subdivision list for the tree in item selection dialog
 */

/** @var nc_input $input */
$input = nc_core('input');
$node = $input->fetch_get_post('node');
$site_id = (int)$input->fetch_get_post('site_id');

if (!$site_id) { trigger_error("'site_id' parameter is required", E_USER_ERROR); }

$ret_sites = array();
$ret_sub = array();

// site ----------------------------
$site = nc_db()->get_row("SELECT `Catalogue_ID`, `Catalogue_Name`, `Checked`, `ncMobile`, `ncResponsive`
                        FROM `Catalogue`
                       WHERE `Catalogue_ID` = $site_id", ARRAY_A);

$icon = 'nc-icon nc--site'
      . ($site['ncMobile'] ? '-mobile' : '')
      . ($site['ncResponsive'] ? '-adaptive' : '')
      . ($site['Checked'] ? '' : ' nc--disabled');

$ret_sites[] = array(
      "nodeId" => "site-$site[Catalogue_ID]",
      "name" => $site["Catalogue_ID"] . ".  " . $site["Catalogue_Name"],
      "sprite" => $icon,
      "href" => "",
      "action" => "return false;",
      "hasChildren" => true
);

// subdivisions --------------------

$subdivisions = nc_netshop_condition_admin_helpers::get_subdivisions_with_goods($site_id);

foreach ((array)$subdivisions as $sub) {
    $icon = "folder" . ($sub["Checked"] ? "" : " nc--disabled");
    $ret_sub[$sub['Subdivision_ID']] = array(
            "nodeId" => "sub-$sub[Subdivision_ID]",
            "parentNodeId" => ($sub['Parent_Sub_ID'] ? "sub-$sub[Parent_Sub_ID]" : "site-$sub[Catalogue_ID]"),
            "name" => $sub["Subdivision_ID"] . ". " . $sub["Subdivision_Name"],
            "href" => "",
            "action" => "return this.actions.selectNode($sub[Subdivision_ID]);",
            "sprite" => $icon,
            "hasChildren" => false,
            "dragEnabled" => false,
            "className" => ($sub["Checked"] ? "" : "disabled"));
}

// add hasChildren for nodes with children
if ($ret_sub) {
    foreach ((array)$subdivisions as $sub) {
        $ret_sub[$sub['Parent_Sub_ID']]['hasChildren'] = true;
    }
} // of "hasChildren?"

$ret = array_merge(array_values($ret_sites), array_values($ret_sub));
print nc_array_json($ret);
