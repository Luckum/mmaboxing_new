<?php

class nc_mod_netshop_payment_response_mail extends nc_mod_netshop_payment_response {

    protected $type = null;
    protected $status = null;
    protected $item_number = null;
    protected $issuer_id = null;
    protected $serial = null;
    protected $signature = null;
    protected $secret_key = null;
    protected $order_number = null;
    protected $status_message = null;
    protected $status_id = null;
    protected $comment = null;

    public function __construct($type) {
        parent::__construct($type);
        $this->order_number = base64_decode($_REQUEST["issuer_id"]);
        $this->status_message = $_REQUEST["status"];
        $this->status_id = $this->get_status_id();
        $this->comment = $this->db->escape(toCP1251($_POST['customermessage'])).NETCAT_MODULE_NETSHOP_TRANSACTION_NUMBER.' Деньги@Mail.Ru: '.intval($_POST['txn_id']);

        $this->type = $_REQUEST["type"];
        $this->status = $_REQUEST["status"];
        $this->item_number = $_REQUEST["item_number"];
        $this->issuer_id = $_REQUEST["issuer_id"];
        $this->serial = $_REQUEST["serial"];
        $this->signature = $_REQUEST["signature"];
        $this->secret_key = $this->shop->MailSecretKey;
        $this->auth_method = $_REQUEST["auth_method"];
    }

    public function check() {
        if ($this->signature == sha1($this->auth_method.$this->issuer_id.$this->item_number.$this->serial.$this->status.$this->type.$this->secret_key)) {
            echo 'item_number='.$this->item_number;
            echo '</br>';
            echo 'status=ACCEPTED';
            echo '</br>';
            echo 'code=S0004';
            return true;
        }
        return false;
    }

    protected function get_status_id() {
        if ($this->status != "PAID") {
            return 1;
        } else {
            return 3;
        }
    }

    public function get_error_message() {
        echo 'item_number='.$this->item_number;
        echo '</br>';
        echo 'status=REJECTED';
        echo '</br>';
        echo 'code=S0003';
    }

    public function update_order() {
        parent::update_order();
    }

}