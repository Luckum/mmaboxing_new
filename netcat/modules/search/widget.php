<?php

require_once realpath("../../../") . "/vars.inc.php";
require_once $ADMIN_FOLDER . "function.inc.php";

require_once("./function.inc.php");

$input['view']         = 'widget';
$input['ui_config']    = false;
$input['print_header'] = false;
$input['print_footer'] = false;

nc_search_admin_controller::process_request($input);