<?php

if (!class_exists("nc_System")) die("Unable to load file.");

/**
 * Класс для облегчения формирования UI в модулях
 */
class ui_config_module_banner extends ui_config_module {

    function ui_config_module_banner($toolbar_action = 'admin') {
        global $db;
        global $MODULE_FOLDER;

        $this->ui_config_module('banner', 'admin');

        $this->toolbar[] = array('id' => "admin",
            'caption' => NETCAT_MODULE_BANNER_MAINSTATS,
            'location' => "module.banner",
            'group' => "grp1"
        );
        $this->toolbar[] = array('id' => "setup",
            'caption' => NETCAT_MODULE_BANNER_SETUP,
            'location' => "module.banner.setup",
            'group' => "grp1"
        );
        $this->activeToolbarButtons[] = $toolbar_action;
    }

}