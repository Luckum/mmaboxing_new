<?php

class Payment_mail {

    private $shop = null;

    function __construct($shop) {
        $this->shop = $shop;

        if (!$shop->OrderID && $_GET["OrderNumber"]) {
            $this->shop->LoadOrder($_GET["OrderNumber"]);
        }
    }

    function create_bill($to_string = false) {
        $shop = $this->shop;
        $signatue = sha1($shop->Currencies[$shop->DefaultCurrencyID].
                        NETCAT_MODULE_NETSHOP_ORDERS_NUMBER.htmlspecialchars($shop->OrderID).
                        $shop->OrderID.
                        $shop->MailShopID.
                        $shop->CartSum().
                        $shop->MailHash);

        $mailru = "<form id='fmail' action='https://money.mail.ru/pay/light/' method='post'>
                   <input type=hidden name='shop_id' value='".htmlspecialchars($shop->MailShopID)."'>
                   <input type=hidden name='currency' value='".htmlspecialchars($shop->Currencies[$shop->DefaultCurrencyID])."'>
                   <input type=hidden name='sum' value='".htmlspecialchars($shop->CartSum())."'>
                   <input type=hidden name='description' value='".NETCAT_MODULE_NETSHOP_ORDERS_NUMBER.htmlspecialchars($shop->OrderID)."'>
                   <input type=hidden name='issuer_id' value='".htmlspecialchars($shop->OrderID)."'>
                   <input type=hidden name='signature' value='$signatue'>
                  ";

        if (!$to_string) {
            echo $mailru;
            echo "<input type=submit value='".NETCAT_MODULE_NETSHOP_PAYMENT_SUBMIT."'></form>";
            return true;
        } else {
            return $mailru.'</form>';
        }
    }

}
