<?

class Payment_paycash_email {

    private $shop = null;
    private $params = array();

    function __construct($shop) {
        $this->shop = $shop;

        if (!$shop->OrderID && $_POST["LMI_PAYMENT_NO"]) {
            $this->shop->LoadOrder($_POST["LMI_PAYMENT_NO"]);
        }
    }

    // создание формы на формирование счета
    function create_bill($to_string = false) {
        $form = "<form id='fpaycash_email' method='POST' action='http://money.yandex.ru/select-wallet.xml'>";
        $form .= $this->shop->PayCashSettings;

        $RUR = $this->shop->CurrencyDetails["RUR"]["Currency"];
        $sum = $this->shop->ConvertCurrency($this->shop->CartSum(), $this->shop->DefaultCurrencyID, $RUR);
        $cart = "";
        foreach ($this->shop->CartContents as $item) {
            $cart .= "$item[Name], $item[Qty] $item[Units] x $item[ItemPriceF] = $item[TotalPriceF]\n";
        }

        if ($this->shop->CartDiscountSum)
                $cart .= "\nСкидка: ".$this->shop->CartDiscountSum."\n";

        if ($this->shop->Order["DeliveryCost"])
                $cart .= "\nДоставка: ".$this->shop->Order["DeliveryCost"]."\n";


        $arr = array("CustomerNumber" => $this->shop->OrderID,
                "Sum" => $sum,
                "CustName" => $_POST["f_ContactName"],
                "CustAddr" => $_POST["f_Address"],
                "CustEMail" => $_POST["f_Email"],
                "OrderDetails" => str_replace("&nbsp;", " ", $cart)
        );

        foreach ($arr as $k => $v) {
            $form .= "<input type=hidden name='$k' value='".htmlspecialchars($v)."'>";
        }

        if (!$to_string) {
            echo $form;
            print "<input type=submit value='".NETCAT_MODULE_NETSHOP_PAYMENT_SUBMIT."'></form>";
            return true;
        } else {
            return $form.'</form>';
        }
    }

}
?>