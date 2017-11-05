<?php

/* $Id: img.php 7302 2012-06-25 21:12:35Z alive $ */

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
//require ($INCLUDE_FOLDER."index.php");
require ($ROOT_FOLDER."connect_io.php");
require_once $MODULE_FOLDER."captcha/function.inc.php";

if ( preg_match('/^[a-f0-9]{32}$/i', $_GET['code']) ) {
    $code = $_GET['code'];
} else {
    $code = '';
}

while (ob_get_level() && @ob_end_clean())
    continue;

if (function_exists('imagegif')) {
    if ($use_gzip_compression) header("Content-Encoding: ");
    header("Content-Type: image/gif");
    nc_captcha_image($code);
}
else {
    header('Content-Type: text/plain');
    echo 'Can\'t generate CAPTCHA image: GD Library with GIF support is not installed.';
}