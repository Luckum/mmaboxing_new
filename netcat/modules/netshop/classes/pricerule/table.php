<?php

class nc_netshop_pricerule_table extends nc_netshop_table {

    protected $table       = 'Netshop_PriceRule';
    protected $primary_key = 'Netshop_PriceRule_ID';
    protected $fields      = array(
        'Netshop_PriceRule_ID' => array(
            'field' => 'hidden',
        ),
        'Catalogue_ID' => array(
            'field' => 'hidden',
        ),
        'Priority' => array(),
        'Checked' => array(
            'default' => 1,
            'field' => 'hidden',
        ),
        'Name' => array(
            'title' => NETCAT_MODULE_NETSHOP_PRICE_RULE_NAME,
            'field' => 'string',
            'required' => true,
        ),
        'UserGroup' => array(),
        'Condition' => array(
            'title' => NETCAT_MODULE_NETSHOP_CONDITION_FIELD,
            'field' => 'custom',
            'html' => "<div id='nc_netshop_condition_editor'></div>",
            'wrap' => true,
            'required' => true,
        ),
        'ActivePriceColumn' => array(
            'title' => NETCAT_MODULE_NETSHOP_PRICE_RULE_PRICE_COLUMN,
            'field' => 'string',
            'required' => true,
        ),
    );

    /**
     * @param $data
     * @return string
     */
    public function make_form($data) {
        // Сделаем ActivePriceColumn выпадающим списком
        $price_fields = array();
        $goods_component_ids = nc_netshop::get_instance()->get_goods_components_ids();

        while ($first_goods_component_id = array_shift($goods_component_ids)) {
            $component = new nc_component($first_goods_component_id);
            foreach ($component->get_fields() as $field) {
                if (strpos($field['name'], 'Price') !== false) {
                    $price_fields[$field['name']] = $field['description'];
                }
            }
        }

        $this->fields['ActivePriceColumn'] = array(
            'title' => NETCAT_MODULE_NETSHOP_PRICE_RULE_PRICE_COLUMN,
            'field' => 'select',
            'subtype' => 'static',
            'values' => $price_fields,
            'required' => true,
        );

        return parent::make_form($data);
    }


}