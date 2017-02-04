<?php

$DOCUMENT_ROOT = rtrim(getenv("DOCUMENT_ROOT"), "/\\");
require_once ($DOCUMENT_ROOT."/vars.inc.php");
require_once ($ROOT_FOLDER."connect_io.php");
require_once ($INCLUDE_FOLDER."index.php");

$s = new SoapServer('IShopClientWS.wsdl', array('classmap' => array('tns:updateBill' => 'Param', 'tns:updateBillResponse' => 'Response')));
$s->setClass('qiwi_response');
$s->handle();

class Response {

    public $updateBillResult;

}

class Param {

    public $login;
    public $password;
    public $txn;
    public $status;

}

class qiwi_response {

    protected $shop = null;
    protected $order_table_id = null;
    protected $db = null;

    function updateBill($param) {

        $nc_core = nc_Core::get_object();
        $this->shop = new NetShop();
        $this->order_table_id = $nc_core->modules->get_vars('netshop', 'ORDER_TABLE');
        $this->db = $nc_core->db;

        $pass = strtoupper(md5($this->shop->QiwiPwd));
        $passforcheck = strtoupper(md5($param->txn.$pass));
        toLog($passforcheck);

        if ($passforcheck == $param->password) {
            if ($param->status == 60) {
                $status_id = 3; //оплачен
                $comment = 'Qiwi:paid';
            } else if ($param->status > 100) {
                // заказ не оплачен (отменен пользователем, недостаточно средств на балансе и т.п.)
                $status_id = 1;
                $comment = 'Qiwi:not paid';
            } else if ($param->status >= 50 && $param->status < 60) {
                $status_id = 1; // счет в процессе проведения
                $comment = 'Qiwi:in process';
            } else {
                $status_id = 1; // неизвестный статус заказа
                $comment = 'Qiwi:unknown status';
            }
            $SQL_order_stat = "UPDATE `Message{$this->order_table_id}` 
                               SET `Comments` = '".NETCAT_MODULE_NETSHOP_RESPONSE_STAT_MESSAGE." QIWI: $param->status, ".
                    NETCAT_MODULE_NETSHOP_RESPONSE_COMMENT.": {$comment}',
                                   `Status` = {$status_id}
                                        WHERE `Message_ID` = {$param->txn}";

            $this->db->query($SQL_order_stat);
        }

        $temp = new Response();
        $temp->updateBillResult = 0;
        return $temp;
    }

}
