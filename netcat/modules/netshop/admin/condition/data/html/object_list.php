<?php

require '../../../no_header.inc.php';

/**
 * Object list as HTML table
 */

/* @var nc_db $db */
$db = nc_core('db');

/** @var nc_netshop $shop */
$shop = nc_modules('netshop');

/** @var nc_input $input */
$input = nc_core('input');
$subdivision_id = (int)$input->fetch_get_post('subdivision_id');

$component_ids = $shop->get_goods_components_ids();

$base_items_queries = array();
$items_variants_queries = array();
foreach ($component_ids as $component_id) {
    $component = new nc_component($component_id);
    $variant_name_column = $component->has_field('VariantName')
        ? "`VariantName`" :
        "'' AS `VariantName`";

    $base_items_queries[] = "(SELECT $component_id as `Class_ID`,
                                    `Message_ID`,
                                    `Parent_Message_ID`,
                                    `Vendor`,
                                    `Name`,
                                    $variant_name_column
                               FROM `Message{$component_id}`
                              WHERE `Subdivision_ID` = $subdivision_id
                                AND `Parent_Message_ID` = 0)";

    $items_variants_queries[] = "(SELECT $component_id AS `Class_ID`,
                                         `Message_ID`,
                                         `Parent_Message_ID`,
                                         `Vendor`,
                                         `Name`,
                                         $variant_name_column
                                    FROM `Message{$component_id}`
                                   WHERE `Subdivision_ID` = $subdivision_id
                                     AND `Parent_Message_ID` != 0
                                    ORDER BY `Vendor`, `Name`)";
}

$base_items_query = join(" UNION ", $base_items_queries) . " ORDER BY `Vendor`, `Name`";
$base_items_data = $db->get_results($base_items_query, ARRAY_A);

$item_variants_query = join(" UNION ", $items_variants_queries);
$item_variants_list = (array)$db->get_results($item_variants_query, ARRAY_A);
$item_variants = array();
foreach ($item_variants_list as $row) { // regroup by parent
    $item_variants["$row[Class_ID]:$row[Parent_Message_ID]"][] = $row;
}

/** @var nc_ui $ui */
$ui = nc_core('ui');

if (!$base_items_data) {
    echo "<div class='no_results'>",
            $ui->alert->info(NETCAT_MODULE_NETSHOP_CONDITION_SUBDIVISION_HAS_LIST_NO_COMPONENTS_OR_OBJECTS),
         "</div>";
}
else {
    // Using nc_ui in this case is too memory-consuming
    echo "<table class='nc-table nc--wide nc--striped nc--hovered'>\n";
    foreach ($base_items_data as $item_data) {
        $item = new nc_netshop_item($item_data);
        $item_key = $item["_ItemKey"];
        echo "<tr><td class='item' data-object='$item_key'>",
                 "<span class='essence-id'>$item[Message_ID].</span> ",
                 "<span class='essence-caption'>$item[FullName]</span>",
             "</td></tr>\n";

        if (isset($item_variants[$item_key])) {
            foreach ($item_variants[$item_key] as $variant_data) {
                $variant = new nc_netshop_item($variant_data, array("_Parent" => $item));
                echo "<tr><td class='item nc-netshop-item-variant' data-object='$variant[Class_ID]:$variant[Message_ID]'>",
                         "<span class='essence-id'>$variant[Message_ID].</span> ",
                         "<span class='essence-caption'>$variant[FullName]</span>",
                     "</td></tr>\n";
            }
        }
    }
    echo "</table>";
}