<?php

/* $Id: ru.lang.php 4356 2011-03-27 10:24:29Z denis $ */
if (!class_exists("nc_System")) die("Unable to load file.");

if ($nc_core->NC_UNICODE) {
    require_once "ru_utf8.lang.php";
} else {
    require_once "ru_cp1251.lang.php";
}
?>