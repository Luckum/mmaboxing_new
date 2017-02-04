<?php


class nc_netshop_pricerule extends nc_netshop_record_conditional {

    protected $primary_key = "id";
    protected $properties = array(
        "id" => null,
        "catalogue_id" => null,
        "name" => '',
        "user_group" => null, // for compatibility with older versions
        "condition" => '',
        "price_column" => '',
//        "priority" => 0,
        "enabled" => null,
    );

    protected $table_name = "Netshop_PriceRule";
    protected $mapping = array(
        "id" => "Netshop_PriceRule_ID",
        "catalogue_id" => "Catalogue_ID",
        "name" => "Name",
        "user_group" => "UserGroup",
        "condition" => "Condition",
        "price_column" => "ActivePriceColumn",
//        "priority" => "Priority",
        "enabled" => "Checked",
    );

    protected function get_conditions() {
        if (!$this->get('conditions') && $this->get('user_group')) {
            // совместимость со старыми версиями (свойство UserGroup)
            $user_group_condition = new nc_netshop_condition_and(array(
                'conditions' => array(array(
                    'type' => 'user_group',
                    'op' => 'eq',
                    'value' => $this->get('user_group')
                ))
            ));
            return $user_group_condition;
        }

        return parent::get_conditions();
    }

}