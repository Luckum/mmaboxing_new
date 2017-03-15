<?

class Payment_paymaster {

    private $shop = null;

    function __construct($shop) { 
        $this->shop = $shop;
        
        if (!$shop->OrderID && $_POST["LMI_MERCHANT_ID"]) {
            $this->shop->LoadOrder($_POST["LMI_PAYMENT_NO"]);
        }
    }

    function create_bill($to_string = false) {
        $paymaster = "<form id='fpaymaster' action='https://paymaster.ru/Payment/Init' method='post'>
                      <input type=hidden name='LMI_MERCHANT_ID' value='".htmlspecialchars($this->shop->PaymasterID)."'>
                      <input type=hidden name='LMI_PAYMENT_AMOUNT' value='".htmlspecialchars($this->shop->CartSum())."'>
                      <input type=hidden name='LMI_CURRENCY' value='".htmlspecialchars(($this->shop->Currencies[$this->shop->DefaultCurrencyID] == "RUR" ? "RUB" : $this->shop->Currencies[$this->shop->DefaultCurrencyID]))."'>
                      <input type=hidden name='LMI_PAYMENT_NO' value='".htmlspecialchars($this->shop->OrderID)."'>
                      <input type=hidden name='LMI_SIM_MODE' value='0'>
                      <input type=hidden name='LMI_PAYMENT_DESC' value='".htmlspecialchars(sprintf(NETCAT_MODULE_NETSHOP_PAYMENT_DESCRIPTION, $this->shop->OrderID, $this->shop->ShopName))."'>";
       
        if (!$to_string) {
            echo $paymaster;
            print "<input type=submit value='".NETCAT_MODULE_NETSHOP_PAYMENT_SUBMIT."'></form>";
            return true;
        } else {
            return $paymaster.'</form>';
        }
    }
}
?>