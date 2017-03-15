<?php

class nc_mod_netshop_payment_response_paymaster extends nc_mod_netshop_payment_response {

    protected $order_number = null;
    protected $status_message = null;
    protected $status_id = null;
    protected $comment = null;
    protected $secret_word = null;

    public function __construct($type) {
        parent::__construct($type);
        $this->order_number = intval($_REQUEST["LMI_PAYMENT_NO"]);
        $this->status_message = 'PAID';
        $this->status_id = 3;
        $this->comment = NETCAT_MODULE_NETSHOP_TRANSACTION_NUMBER.' Paymaster '.intval($_REQUEST['LMI_PAYMENT_NO']);
        $this->secret_word = $this->shop->PaymasterWord;
    }

    private function hash() {
        base64_encode(md5($_REQUEST['LMI_MERCHANT_ID'].";".$_REQUEST['LMI_PAYMENT_NO'].";".$_REQUEST['LMI_SYS_PAYMENT_ID'].";".$_REQUEST['LMI_SYS_PAYMENT_DATE'].";".$_REQUEST['LMI_PAYMENT_AMOUNT'].";".$_REQUEST['LMI_CURRENCY'].";".$_REQUEST['LMI_PAID_AMOUNT'].";".$_REQUEST['LMI_PAID_CURRENCY'].";".$_REQUEST['LMI_PAYMENT_SYSTEM'].";".$_REQUEST['LMI_SIM_MODE'].";".$this->secret_word, true));
    }

    public function check() {
        return !$_REQUEST['LMI_PREREQUEST'] && ($_REQUEST['LMI_HASH'] == $this->hash());
    }

    public function get_error_message() {
        echo 'LMI_HASH IS WRONG';
        exit;
    }

    public function update_order() {
        parent::update_order();
    }

}
