<?php

abstract class nc_mod_netshop_payment_response {

    protected $shop = null;
    protected $order_table_id = null;
    protected $db = null;
    protected $status_id = null;
    protected $type = null;
    protected $comment = null;
    protected $order_number = null;
    protected $payments_table_id = null;
    protected $secret_word = null;

    public function __construct($type) {
        $this->type = $type;
        $nc_core = nc_Core::get_object();
        $this->shop = new NetShop();
        $this->order_table_id = $nc_core->modules->get_vars('netshop', 'ORDER_TABLE');
        $this->payments_table_id = $nc_core->modules->get_vars('netshop', 'PAYMENT_METHODS_TABLE');
        $this->db = $nc_core->db;
    }

    abstract public function check();

    abstract public function get_error_message();

    public function update_order() {
        $SQL_order_stat = "UPDATE `Message{$this->order_table_id}` 
                               SET `Comments` = '".NETCAT_MODULE_NETSHOP_RESPONSE_STAT_MESSAGE." {$this->type}: {$this->status_message}, ".
                NETCAT_MODULE_NETSHOP_RESPONSE_COMMENT.": {$this->comment}', 
                                   `Status` = {$this->status_id}
                                        WHERE `Message_ID` = {$this->order_number}";

        $this->db->query($SQL_order_stat);
    }

}