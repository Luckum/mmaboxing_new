<?php


class torg12_netshop_form extends nc_netshop_form {

    //--------------------------------------------------------------------------

    public $name    = NETCAT_MODULE_NETSHOP_TORG12;
    public $keyword = 'torg12';

    //--------------------------------------------------------------------------

    public function fields() {
        return array(
            'company_name'         => NETCAT_MODULE_NETSHOP_CASHMEMO_COMPANY,
            'unit'                 => NETCAT_MODULE_NETSHOP_TORG12_UNIT,
            'consignee'            => NETCAT_MODULE_NETSHOP_TORG12_CONSIGNEE,
            'supplier'             => NETCAT_MODULE_NETSHOP_TORG12_SUPPLIER,
            'payer'                => NETCAT_MODULE_NETSHOP_TORG12_PAYER,
            'contract'             => NETCAT_MODULE_NETSHOP_TORG12_CONTRACT,
            'okdp'                 => NETCAT_MODULE_NETSHOP_TORG12_OKDP,
            'okpo1'                => NETCAT_MODULE_NETSHOP_TORG12_OKDP . ' 1',
            'okpo2'                => NETCAT_MODULE_NETSHOP_TORG12_OKDP . ' 2',
            'okpo3'                => NETCAT_MODULE_NETSHOP_TORG12_OKDP . ' 3',
            'okpo4'                => NETCAT_MODULE_NETSHOP_TORG12_OKDP . ' 4',
            'trans_number1'        => NETCAT_MODULE_NETSHOP_TORG12_TRANS_NUMBER . ' 1',
            'trans_date1'          => NETCAT_MODULE_NETSHOP_TORG12_TRANS_DATE . ' 1',
            'trans_number2'        => NETCAT_MODULE_NETSHOP_TORG12_TRANS_NUMBER . ' 2',
            'trans_date2'          => NETCAT_MODULE_NETSHOP_TORG12_TRANS_DATE . ' 2',
            'operation_type'       => NETCAT_MODULE_NETSHOP_TORG12_OPERATION_TYPE,
            'nds'                  => NETCAT_MODULE_NETSHOP_TORG12_NDS,
            'resolved_by_position' => NETCAT_MODULE_NETSHOP_TORG12_RESOLVED_BY_POSITION,
            'resolved_by_surname'  => NETCAT_MODULE_NETSHOP_TORG12_RESOLVED_BY_SURNAME,
            'accountant_surname'   => NETCAT_MODULE_NETSHOP_TORG12_ACCOUNTANT_SURNAME,
            'released_by_position' => NETCAT_MODULE_NETSHOP_TORG12_RELEASED_BY_POSITION,
            'released_by_surname'  => NETCAT_MODULE_NETSHOP_TORG12_RELEASED_BY_SURNAME,
        );
    }

    //--------------------------------------------------------------------------
}