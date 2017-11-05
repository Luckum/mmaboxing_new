<?php

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");

require ($ADMIN_FOLDER."function.inc.php");
require ($ADMIN_FOLDER."catalogue/function.inc.php");
require ($ADMIN_FOLDER."subdivision/function.inc.php");

$main_section = "control";
$item_id = 2;
$Delimeter = " &gt ";
$Title2 = CONTROL_CONTENT_SUBDIVISION_FULL_TITLE;

if ($CatalogueID) {
    $cookie_domain = ($MODULE_VARS['auth']['COOKIES_WITH_SUBDOMAIN'] ? str_replace("www.", "", $HTTP_HOST) : NULL);
    setcookie("NetCat_Sitemap_ID", NULL, NULL, "/", $cookie_domain);
    setcookie("NetCat_Sitemap_ID", $CatalogueID, time() + 2592000, "/", $cookie_domain);
}

$UI_CONFIG = new ui_config_catalogue('map', $CatalogueID);

BeginHtml($Title2, $Title2, "http://".$DOC_DOMAIN."/management/full/");

ShowFullSubdivisionList();

EndHtml();
?>