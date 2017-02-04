<?php


class nc_netshop_order_admin_controller extends nc_netshop_admin_controller {

    /** @var string  Должен быть задан, или должен быть переопределён метод before_action() */
    protected $ui_config_class = 'nc_netshop_order_admin_ui';

    /**
     *
     */
    protected function action_index() {
        $db = nc_db();
        $order_component_id = (int)$this->netshop->get_setting('OrderComponentID');

        // в качестве шаблона использовать помеченный как шаблон для режима администрирования
        $template_id = $db->get_var(
            "SELECT `Class_ID`
               FROM `Class`
              WHERE (`Class_ID` = $order_component_id OR `ClassTemplate` = $order_component_id)
              ORDER BY `Type` = 'inside_admin' DESC
              LIMIT 1");
        if (!$template_id) { $template_id = $order_component_id; }

        // нужны ID раздела, ID инфоблока с компонентом «Заказ» на выбранном сайте
        list($subdivision_id, $infoblock_id) = $db->get_row(
            "SELECT `ib`.`Subdivision_ID`, `ib`.`Sub_Class_ID`
               FROM `Sub_Class` AS `ib`,
                    `Subdivision` AS `sub`
              WHERE `ib`.`Class_ID` = $order_component_id
                AND `ib`.`Subdivision_ID` = `sub`.`Subdivision_ID`
                AND `sub`.`Catalogue_ID` = $this->site_id
              ORDER BY (`ib`.`Class_Template_ID` = $template_id OR `ib`.`Edit_Class_Template` = $template_id) DESC
              LIMIT 1", ARRAY_N);

        if (!$subdivision_id) {
            return $this->view('error_message')->with('message', NETCAT_MODULE_NETSHOP_ORDER_NO_INFOBLOCK);
        }

        // установка параметров для правильного вывода списка
        nc_core('catalogue')->set_current_by_id($this->site_id);

        $GLOBALS['inside_admin'] = 1; // @see nc_postprocess_admin_page()
        nc_core()->inside_admin = 1;
        ob_start("nc_postprocess_admin_page");

        $list_vars = "nc_ctpl=$template_id" .
                     "&isMainContent=1" .
                     "&catalogue={$this->site_id}" .
                     "&controller_url=" . urlencode($this->get_script_path() . "index&catalogue_id={$this->site_id}");

        foreach (nc_core('input')->fetch_get_post() as $k => $v) {
            $list_vars .= "&$k=" . urlencode($v);
        }

        // генерирование списка
        $order_list = nc_objects_list($subdivision_id, $infoblock_id, $list_vars, true);

        return $this->view('order_list')->with('order_list', $order_list);
    }

}