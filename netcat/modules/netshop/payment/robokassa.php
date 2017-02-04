<?php

class Payment_robokassa {

    private $shop = null;

    function __construct($shop) {
        $this->shop = $shop;

        if (!$shop->OrderID && $_GET["OrderNumber"]) {
            $this->shop->LoadOrder($_GET["OrderNumber"]);
        }
    }

    function create_bill($to_string = false) {
        global $db;
        list($last_name, $first_name) = explode(" ", $GLOBALS["f_ContactName"], 2);
        $shop = $this->shop;

        $robokassa = "<form id='frobokassa' action='https://merchant.roboxchange.com/Index.aspx' method='post'>
                   <input type=hidden name='MrchLogin' value='".htmlspecialchars($shop->RobokassaLogin)."'>
                   <input type=hidden name='OutSum' value='".htmlspecialchars(number_format($shop->CartSum(), 2, '.', ''))."'>
                   <input type=hidden name='InvId' value='".htmlspecialchars($shop->OrderID)."'>
                   <input type=hidden name='SignatureValue' value='".md5($shop->RobokassaLogin.":".number_format($shop->CartSum(), 2, '.', '').":".$shop->OrderID.":".$shop->RobokassaPass1)."'>
                   <input type=hidden name='IncCurrLabel' value='".htmlspecialchars($db->get_var('select RobokassaCurrency_Name from Classificator_RobokassaCurrency  where RobokassaCurrency_ID ='.$shop->IncCurrLabel))."'>
                   ";

        if (!$to_string) {
            echo $robokassa;
            echo "<input type=submit value='".NETCAT_MODULE_NETSHOP_PAYMENT_SUBMIT."'></form>";
            return true;
        } else {
            return $robokassa.'</form>';
        }
    }

}
