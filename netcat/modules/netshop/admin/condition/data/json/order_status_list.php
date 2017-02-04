<?php
require '../../../no_header.inc.php';

/**
 * ShopStatus classifier values as JSON
 */

/* @var nc_db $db */
$db = nc_core('db');

/** @var nc_input $input */
$classifier = "ShopOrderStatus";
$table = "Classificator_$classifier";

$rows = $db->get_results("SELECT `{$classifier}_ID`, `{$classifier}_Name`
                            FROM `$table`
                           WHERE `Checked` = 1
                          ORDER BY `{$classifier}_Priority`",
    ARRAY_A);

$result = new stdClass();
$result->{0} = NETCAT_MODULE_NETSHOP_ORDER_NEW; // отдельный файл существует только из-за этой строчки...

/** @var nc_utf8 $utf */
$utf = nc_core('utf8');
foreach ($rows as $row) {
    $result->{$row["{$classifier}_ID"]} = $utf->conv(MAIN_ENCODING, 'utf-8', $row["{$classifier}_Name"]);
}

echo json_encode($result);