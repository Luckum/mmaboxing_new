<?php

class Payment_qiwi {

    private $shop = null;

    function __construct($shop) {
        $this->shop = $shop;

        if (!$shop->OrderID && $_GET["OrderNumber"]) {
            $this->shop->LoadOrder($_GET["OrderNumber"]);
        }
    }

    function create_bill($to_string = false) {
        $shop = $this->shop;

        $qiwi = "<form id='fqiwi' action='http://w.qiwi.ru/setInetBill_utf.do' method='post'>
                   <input name='txn_id' type='hidden' value='".htmlspecialchars($shop->OrderID)."' />
                   <input name='from' type='hidden' value='".htmlspecialchars($shop->QiwiFrom)."' />
                   <input id='to' name='to' type='hidden' value='' />
                   <input name='summ' type='hidden' value='".htmlspecialchars($shop->CartSum())."' />
                  ";

        if (!$to_string) {
            echo $qiwi;
            echo "<input type=submit value='".NETCAT_MODULE_NETSHOP_PAYMENT_SUBMIT."'></form>";
            return true;
        } else {
            return $qiwi.'</form>';
        }
    }

}