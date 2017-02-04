<?php

class nc_netshop_yandex_admin_ui extends nc_netshop_admin_ui {

    /**
     * @param $catalogue_id
     * @param string $current_action
     */
    public function __construct($catalogue_id, $current_action = "index") {
        parent::__construct('yandex', NETCAT_MODULE_NETSHOP_YANDEX_MARKET);

        $this->catalogue_id = $catalogue_id;
        $this->activeTab = $current_action;
    }


    /**
     * Сгенерировать табы непосредственно перед выводом (потому что catalogue_id
     * может поменяться в процессе выполнения action)
     *
     * @todo Перенести обратно в __construct после создания универсального интерфейса  для посайтового управления модулями.
     *
     * @return string
     */
    public function to_json() {
        $current_action = $this->activeTab;
        $catalogue = $this->catalogue_id ? "($this->catalogue_id)" : "";

        if ($this->locationHash == 'module.netshop.yandex') {
            if ($current_action != 'index') {
                $this->locationHash = "module.netshop.yandex.$current_action";
            }
            $this->locationHash .= $catalogue;
        }
/*
        $this->tabs = array(
            array(
                'id'       => 'index',
                'caption'  => NETCAT_MODULE_NETSHOP_YANDEX_MARKET_BUNDLES,
                'location' => "module.netshop.yandex" . $catalogue,
                'group'    => "admin",
            ),
            array(
                'id'       => 'module',
                'caption'  => NETCAT_MODULE_NETSHOP_SETTINGS,
                'location' => "module.netshop.yandex.module" . $catalogue,
                'group'    => "admin",
            ),
        );
 */

        return parent::to_json();
    }

}