<?PHP

/* $Id: ui_config.php 4742 2011-05-31 13:23:08Z gaika $ */

/**
 * Класс для облегчения формирования UI в модулях
 */
class ui_config_module_minishop extends ui_config_module {

    public $headerText = NETCAT_MODULE_MINISHOP;
    public $headerImage = 'i_module_minishop_big.gif';

    function ui_config_module_minishop($view, $params) {
        $this->tabs[] = array(
                'id' => "info",
                'caption' => NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_INFO_TAB,
                'location' => "module.minishop.info",
                'group' => "admin"
        );
        $this->tabs[] = array(
                'id' => "settings",
                'caption' => NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_SETTINGS_TAB,
                'location' => "module.minishop.settings",
                'group' => "admin"
        );

        $this->activeTab = $view;
        $this->locationHash = "module.minishop.".$view.($params ? "(".$params.")" : "");
        $this->treeMode = "modules";

        $module_settings = nc_Core::get_object()->modules->get_by_keyword('minishop');
        $this->treeSelectedNode = "module-".$module_settings['Module_ID'];
    }

    public function add_settings_toolbar() {
        $this->toolbar[] = array(
                'id' => "settings",
                'caption' => NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_GENERAL_TAB,
                'location' => "module.minishop.settings",
                'group' => "admin"
        );

        $this->toolbar[] = array(
                'id' => "discount",
                'caption' => NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_DISCOUNT_TAB,
                'location' => "module.minishop.discount",
                'group' => "admin"
        );

        $this->toolbar[] = array(
                'id' => "display",
                'caption' => NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_DISPLAY_TAB,
                'location' => "module.minishop.display",
                'group' => "admin"
        );

        $this->toolbar[] = array(
                'id' => "mails",
                'caption' => NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_MAILS_TAB,
                'location' => "module.minishop.mails",
                'group' => "admin"
        );

        $this->toolbar[] = array(
                'id' => "system",
                'caption' => NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_SYSTEM_TAB,
                'location' => "module.minishop.system",
                'group' => "admin"
        );


        $this->activeToolbarButtons[] = $this->activeTab;
        $this->activeTab = 'settings';
    }

}
?>
