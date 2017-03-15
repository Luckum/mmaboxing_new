<?php

$NETCAT_FOLDER = realpath(dirname(__FILE__) . "/../../../") . "/";
include_once($NETCAT_FOLDER . "vars.inc.php");
require($INCLUDE_FOLDER . "index.php");

define("NC_PAYMENT_SYSTEM_CLASSIFIER_TABLE", "Classificator_PaymentSystem");
define("NC_PAYMENT_SYSTEM_PARAM_TABLE", "Payment_SystemSetting");
define("NC_PAYMENT_SYSTEM_CATALOGUE_TABLE", "Payment_SystemCatalogue");
define("NC_PAYMENT_LOG_TABLE", "Payment_Log");

require_once nc_module_folder("payment") . "nc_payment.class.php";
nc_payment::init();

