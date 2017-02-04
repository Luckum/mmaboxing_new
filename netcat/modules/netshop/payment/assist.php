<?php

class Payment_assist {

    private $shop = null;

    function __construct($shop) {
        $this->shop = $shop;

        if (!$shop->OrderID && $_GET["OrderNumber"]) {
            $this->shop->LoadOrder($_GET["OrderNumber"]);
        }
    }

    // создание формы на формирование счета
    function create_bill($to_string = false) {


        list($last_name, $first_name) = explode(" ", $GLOBALS["f_ContactName"], 2);
        $shop = $this->shop;

        $assist = "<form id='fassist' action='https://payments.paysecure.ru/pay/order.cfm' method='post'>
                   <input type=hidden name='Merchant_ID' value='".htmlspecialchars($shop->AssistShopId)."'>
                   <input type=hidden name='OrderNumber' value='".htmlspecialchars($shop->OrderID)."'>
                   <input type=hidden name='OrderAmount' value='".htmlspecialchars($shop->CartSum())."'>
                   <input type=hidden name='Language' value='".htmlspecialchars($GLOBALS["current_catalogue"]["Language"] == "ru" ? "RU" : "EN")."'>
                   <input type=hidden name='URL_RETURN' value='".htmlspecialchars("http://".$_SERVER['HTTP_HOST'])."'>
                   <input type=hidden name='URL_RETURN_OK' value='".htmlspecialchars($shop->PaymentSuccessPage)."'>
                   <input type=hidden name='URL_RETURN_NO' value='".htmlspecialchars($shop->PaymentFailedPage)."'>
                   <input type=hidden name='OrderCurrency' value='".htmlspecialchars($shop->Currencies[$shop->DefaultCurrencyID] == "RUR" ? "RUB" : $shop->Currencies[$shop->DefaultCurrencyID])."'>
                   <input type=hidden name='OrderComment' value='".htmlspecialchars(toUTF(sprintf(NETCAT_MODULE_NETSHOP_PAYMENT_DESCRIPTION, $this->shop->OrderID, $this->shop->ShopName)))."'>
                   <input type=hidden name='LastName' value='".htmlspecialchars(toUTF($last_name))."'>
                   <input type=hidden name='FirstName' value='".htmlspecialchars(toUTF($first_name))."'>
                   <input type=hidden name='Email' value='".htmlspecialchars($GLOBALS["f_Email"])."'>
                   <input type=hidden name='CardPayment' value='1'>
                   <input type=hidden name='AssistIDPayment' value='1'>
                   ";

        if (!$to_string) {
            //для тестов "<form action='https://test.paysecure.ru/pay/order.cfm' method='post'>";
            echo $assist;
            echo "<input type=submit value='".NETCAT_MODULE_NETSHOP_PAYMENT_SUBMIT."'></form>";
            return true;
        } else {
            return $assist.'</form>';
        }
    }

}
?>