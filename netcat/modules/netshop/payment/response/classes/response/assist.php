<?php

class nc_mod_netshop_payment_response_assist extends nc_mod_netshop_payment_response {

    protected $order_number = null;
    protected $comment = null;
    protected $status_message = null;
    protected $status_id = null;

    public function __construct($type) {
        parent::__construct($type);
        $this->order_number = intval($_POST['ordernumber']);
        $this->comment = $this->db->escape(toCP1251($_POST['customermessage']));
        $this->status_message = $this->db->escape($_POST['orderstate']);
        $this->status_id = $this->get_status_id();
    }

    protected function get_status_id() {
        return $this->status_message == 'Approved' ? 3 : 1;
    }

    public function check() {
        return $_POST['checkvalue'] == strtoupper(md5(strtoupper(md5($this->shop->AssistSecretWord).md5($_POST['merchant_id'].$this->order_number.$_POST['amount'].$_POST['currency'].$this->status_message))));
    }

    public function get_error_message() {
        
    }

    public function update_order() {
        parent::update_order();
    }

}