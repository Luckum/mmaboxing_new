<?php

class nc_netshop_order_admin_ui extends nc_netshop_admin_ui {

    /**
     * @param $catalogue_id
     * @param string $current_action
     */
    public function __construct($catalogue_id, $current_action = "index") {
        parent::__construct('order', NETCAT_MODULE_NETSHOP_ORDERS);

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
        $catalogue = $this->catalogue_id ? "($this->catalogue_id)" : "";

        if ($this->locationHash == 'module.netshop.order') {
            $this->locationHash .= $catalogue;
        }

        $this->tabs = array();

        return parent::to_json();
    }

}