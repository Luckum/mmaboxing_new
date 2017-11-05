<?php


/**
 *
 *
 */
class nc_netshop_pricerule_admin_controller extends nc_netshop_admin_table_controller {

    /** @var  nc_netshop_pricerule_admin_ui */
    protected $ui_config;

    protected $data_type = 'pricerule';

    /**
     * @return nc_ui_view
     */
    protected function action_index() {
        $table = new nc_netshop_pricerule_table();
        $rules = $table->for_site($this->site_id)->get_result();

        if (count($rules)) {
            $view = $this->view('pricerule_list')->with('rules', $rules);
        }
        else {
            $view = $this->view('empty_list')
                         ->with('message', NETCAT_MODULE_NETSHOP_SETTINGS_NO_PRICE_RULES_ON_SITE);
        }

        $this->ui_config->add_create_button("pricerule.add($this->site_id)");

        return $view;
    }

    /**
     * @return nc_ui_view
     */
    protected function action_add() {
        return $this->basic_table_edit_action(0, 'pricerule_form')
                    ->with('condition_json', '{}');
    }

    /**
     * @param $id
     * @return nc_ui_view
     */
    protected function action_edit($id) {
        $view = $this->basic_table_edit_action($id, 'pricerule_form');

        // обеспечение совместимости с правилами старых версий, в которых
        // была только выборка только по группе пользователя
        $record = $view->record;
        $condition_json = $record['Condition'];
        if (!$condition_json) {
            if ($record['UserGroup']) {
                $condition_json =
                    '{"type":"and","conditions":[' .
                        '{"type":"user_group","op":"eq","value":"' . $record['UserGroup'] . '"}' .
                    ']}';
            }
            else {
                $condition_json = "{}";
            }
        }

        return $view->with('condition_json', $condition_json);
    }

}