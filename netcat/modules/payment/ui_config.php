<?php

if (!class_exists("nc_System")) die("Unable to load file.");

class ui_config_module_payment extends ui_config_module {

    function ui_config_module_payment($active_tab = 'admin', $toolbar_action = 'settings') {
        global $db;
        global $MODULE_FOLDER;

        $this->ui_config_module('payment', $active_tab);

        }

}
?>