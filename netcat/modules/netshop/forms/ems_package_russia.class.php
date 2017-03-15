<?php


class ems_package_russia_netshop_form extends nc_netshop_form {

    //--------------------------------------------------------------------------

    public $name = NETCAT_MODULE_NETSHOP_EMS_RUSSIA;
    public $keyword = 'ems_package_russia';

    //--------------------------------------------------------------------------

    public function template($data) {
        $valuation = ceil($data['valuation']);
        $data['valuation'] = $valuation;
        if ($data['valuation']) {
            $data['valuation_text'] = nc_netshop_amount_in_full($data['valuation'], true, false);
        } else {
            $data['valuation_text'] = '';
        }

        $cash_on_delivery = round($data['cash_on_delivery'], 2);
        $data['cash_on_delivery'] = $cash_on_delivery;
        if ($data['cash_on_delivery']) {
            $data['cash_on_delivery_int'] = (int)$data['cash_on_delivery'];

            $decimals = round($data['cash_on_delivery'] - $data['cash_on_delivery_int'], 2) * 100;
            $decimals = sprintf('%02d', $decimals);

            $data['cash_on_delivery_dec'] = $decimals;
            $data['cash_on_delivery_text'] = nc_netshop_amount_in_full($data['cash_on_delivery'], true, true);
        } else {
            $data['cash_on_delivery_int'] = '';
            $data['cash_on_delivery_dec'] = '';
            $data['cash_on_delivery_text'] = '';
        }

        $weight = $data['weight'];
        $data['weight'] = round($weight, 3);
        if ($data['weight']) {
            $data['weight_int'] = (int)$data['weight'];

            $decimals = round($data['weight'] - $data['weight_int'], 2) * 100;
            $decimals = sprintf('%03d', $decimals);

            $data['weight_dec'] = $decimals;
        } else {
            $data['weight_int'] = '';
            $data['weight_dec'] = '';
        }

        return parent::template($data);
    }

    //--------------------------------------------------------------------------
}