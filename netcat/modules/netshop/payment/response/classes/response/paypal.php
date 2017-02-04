<?php

class nc_mod_netshop_payment_response_paypal extends nc_mod_netshop_payment_response {

    protected $adminemail = null;
    protected $currency = null;
    protected $status_message = null;
    protected $status_id = null;
    protected $comment = null;
    protected $order_number = null;

    public function __construct($type) {
        parent::__construct($type);
        $this->adminemail = htmlspecialchars($this->shop->PaypalBizMail);
        $this->currency = $this->db->escape($_POST['mc_currency']);
        $this->status_message = $this->db->escape($_POST["payment_status"]);
        $this->status_id = $this->get_status_id();
        $this->comment = $this->db->escape(toCP1251($_POST['customermessage'])).NETCAT_MODULE_NETSHOP_TRANSACTION_NUMBER.' PayPal: '.intval($_POST['txn_id']);
        $this->order_number = intval($_POST['item_number']);
    }

    public function check() {
        $postdata = "";
        foreach ($_POST as $key => $value) {
            $postdata.=$key."=".urlencode($value)."&";
        }
        $postdata .= "cmd=_notify-validate";
        if (function_exists('curl_init')) {
            $curl = curl_init("https://www.paypal.com/cgi-bin/webscr");
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1);
            $response = curl_exec($curl);
            curl_close($curl);
        } else {
            $response = null;
        }
        if ($response == "VERIFIED" && $_POST["receiver_email"] == $this->adminemail && $_POST["txn_type"] == "web_accept") {
            return true;
        } else {
            return false;
        }
    }

    protected function get_status_id() {
        if ($_POST["payment_status"] != "Completed") {
            if ($_POST["payment_status"] == "Pending") {
                return 1; //Pending 
            } else {
                return 1; //неудачная оплата
            }
        } else {
            return 3; //Completed
        }
    }

    public function get_error_message() {
        
    }

    public function update_order() {
        parent::update_order();
    }

}
