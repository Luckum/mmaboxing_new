<?php


class cashmemo_netshop_form extends nc_netshop_form {

    //--------------------------------------------------------------------------

    public $name    = NETCAT_MODULE_NETSHOP_CASHMEMO;
    public $keyword = 'cashmemo';

    //--------------------------------------------------------------------------

    public function fields() {
        return array(
            'company_name'    => NETCAT_MODULE_NETSHOP_CASHMEMO_COMPANY,
            'company_inn'     => NETCAT_MODULE_NETSHOP_BANK_INN,
            'company_address' => NETCAT_MODULE_NETSHOP_BANK_ADDRESS,
            'seller_position' => NETCAT_MODULE_NETSHOP_CASHMEMO_SELLER_POSITION,
            'seller_fio'      => NETCAT_MODULE_NETSHOP_FULL_NAME,
        );
    }

    //--------------------------------------------------------------------------
}