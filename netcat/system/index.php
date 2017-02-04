<?php

/* $Id: index.php 6864 2012-05-04 12:43:59Z russuckoff $ */

$NETCAT_FOLDER = realpath(dirname(__FILE__) . '/../..') . DIRECTORY_SEPARATOR;

include_once $NETCAT_FOLDER . "vars.inc.php";

// запрет прямого обращения
if (!function_exists('nc_strlen')) die("Unable to load file.");


// include need classes
include_once ($SYSTEM_FOLDER."nc_system.class.php");
include_once ($SYSTEM_FOLDER."nc_exception.class.php");
include_once ($SYSTEM_FOLDER."nc_core.class.php");
include_once ($SYSTEM_FOLDER."nc_essence.class.php");
include_once ($SYSTEM_FOLDER."nc_db_table.class.php");


try {
    session_start();
    // initialize superior system object
    $nc_core = nc_Core::get_object();
    // load default extensions
    $nc_core->load_default_extensions();
    $nc_core->load_files();

    ob_start();
    header("Content-Type: ".$nc_core->get_content_type());
    //global variables
    $LinkID = &$nc_core->db->dbh;
    $parsed_url = $nc_core->url->parse_url();
    $client_url = $nc_core->url->source_url();
    extract($nc_core->input->prepare_extract());
    $_cache = array();
} catch (Exception $e) {
    //$nc_core->errorMessage($e);
    die($e->getMessage());
}
