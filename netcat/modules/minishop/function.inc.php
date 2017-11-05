<?php

/* $Id: function.inc.php 5222 2011-08-25 12:04:52Z gaika $ */
if (!class_exists("nc_System")) die("Unable to load file.");

global $MODULE_FOLDER;
include_once ($MODULE_FOLDER."minishop/nc_minishop_templates.class.php");
include_once ($MODULE_FOLDER."minishop/nc_minishop.class.php");
include_once ($MODULE_FOLDER."minishop/nc_minishop_order.class.php");


global $nc_minishop;
$nc_minishop = nc_minishop::get_object();