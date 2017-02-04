<?php

class Payment_cash {

    private $shop = null;

    function __construct($shop) {
        $this->shop = $shop;

        if (!$shop->OrderID && $_GET["OrderNumber"]) {
            $this->shop->LoadOrder($_GET["OrderNumber"]);
        }
    }

    function create_bill($to_string = false) {
        $shop = $this->shop;

        $qiwi = "<form id='fcash' action='' method='get'>";

        if (!$to_string) {
            echo $qiwi;
            echo "<input type=submit value='".NETCAT_MODULE_NETSHOP_PAYMENT_SUBMIT."'></form>";
            return true;
        } else {
            return $qiwi.'</form>';
        }
    }

}
?>
