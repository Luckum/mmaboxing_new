<?php

$DOCUMENT_ROOT = rtrim(getenv("DOCUMENT_ROOT"), "/\\");
require_once ($DOCUMENT_ROOT."/vars.inc.php");
require_once ($ROOT_FOLDER."connect_io.php");
require_once ($INCLUDE_FOLDER."index.php");

$CURRENT_FOLDER = dirname(__FILE__);
require_once "$CURRENT_FOLDER/classes/response.php";
require_once "$CURRENT_FOLDER/classes/handler.php";

$response_handler = new nc_mod_netshop_payment_handler($systemtype);

if ($response_handler->check()) {
    $response_handler->update_order();
} else {
    echo $response_handler->error();
}