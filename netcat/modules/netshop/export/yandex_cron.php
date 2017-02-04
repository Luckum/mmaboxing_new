<?php

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -5)) . (strstr(__FILE__, "/") ? "/" : "\\");
include_once($NETCAT_FOLDER . "vars.inc.php");
require_once($ROOT_FOLDER . "connect_io.php");
require_once($MODULE_FOLDER . "netshop/function.inc.php");
require_once('yandexml.inc.php');



$rows = $db->get_results("SELECT Catalogue_ID FROM `Catalogue`", ARRAY_A);
if (count($rows) > 0) {
  foreach ($rows as $result) {
    $catalogue = $result['Catalogue_ID'];
    $netshop = nc_netshop::get_instance($catalogue);
    $export = new YML_Export_V3($netshop, $result['Domain']);
    $export->ExportYML($catalogue);
  }
}
