<?php


/**
 *
 */
class nc_netshop_settings_admin_controller extends nc_netshop_admin_controller {

    /** @var  nc_netshop_settings_admin_ui */
    protected $ui_config;

    protected $ui_config_class = 'nc_netshop_settings_admin_ui';

    /**
     *
     */
    protected function init() {
        parent::init();
        $this->bind('settings_save', array('settings', 'next_action'));
    }

    /**
     * @return nc_ui_view
     */
    protected function action_index() {
        $this->ui_config->add_submit_button();
        $view = $this->view('org_settings');
        return $view;
    }

    /**
     * @return nc_ui_view
     */
    protected function action_module() {
        $this->ui_config->add_submit_button();
        $view = $this->view('module_settings');
        return $view;
    }

    /**
     * @param $settings
     * @param $next_action
     */
    protected function action_settings_save($settings, $next_action) {
        /** @var nc_core $nc_core */
        $nc_core = nc_core();
        foreach ($settings as $k=>$v) {
            $nc_core->set_settings($k, $v, 'netshop', $this->site_id);
        }
        $this->redirect_to_index_action($next_action);
    }

}