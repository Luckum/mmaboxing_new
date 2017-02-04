<?php

class nc_mod_netshop_payment_response_robokassa extends nc_mod_netshop_payment_response {

    protected $mrh_pass2 = null;
    protected $out_summ = null;
    protected $inv_id = null;
    protected $crc = null;
    protected $order_number = null;
    protected $status_id = null;

    public function __construct($type) {
        parent::__construct($type);
        $this->mrh_pass2 = $this->shop->RobokassaPass2;
        $this->out_summ = $_REQUEST["OutSum"];
        $this->inv_id = $_REQUEST["InvId"];
        $this->order_number = $this->inv_id;
        $this->crc = strtoupper($_REQUEST["SignatureValue"]);
        $this->status_id = 3;
    }

    public function check() {
        if (strtoupper($_REQUEST["SignatureValue"]) == strtoupper(md5($this->out_summ.":".$this->inv_id.":".$this->mrh_pass2))) {
            echo "OK{$this->inv_id}\n";
            return true;
        }
        return false;
    }

    public function get_error_message() {
        
    }

    public function update_order() {
        parent::update_order();
    }

}