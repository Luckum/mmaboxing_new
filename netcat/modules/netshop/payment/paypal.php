<?php

class Payment_paypal {

    private $shop = null;

    function __construct($shop) {
        $this->shop = $shop;

        if (!$shop->OrderID && $_GET["OrderNumber"]) {
            $this->shop->LoadOrder($_GET["OrderNumber"]);
        }
    }

    function create_bill($to_string = false) {
        $shop = $this->shop;
        if ($shop->Currencies[$shop->DefaultCurrencyID] == "RUR") {
            $nc_core = nc_Core::get_object();
            $db = $nc_core->db;
            $rates_table = $nc_core->modules->get_vars('netshop', 'OFFICIAL_RATES_TABLE');
            $SQL = "SELECT Rate 
                        FROM Message{$rates_table} 
                            WHERE Currency=2";
            $rate = $db->get_var($SQL);
            $amount = round($shop->CartSum() / $rate, 2);
        }

        $paypal = "<form id='fpaypal' action='https://www.paypal.com/cgi-bin/webscr' method='post'>
                   <input name='cmd' type='hidden' value='_xclick' />
                   <input name='business' type='hidden' value='".htmlspecialchars($shop->PaypalBizMail)."' />
                   <input name='item_name' type='hidden' value='Order #".htmlspecialchars($shop->OrderID)."' />
                   <input name='item_number' type='hidden' value='".htmlspecialchars($shop->OrderID)."' />
                   <input name='amount' type='hidden' value='".$amount."' />
                   <input name='no_shipping' type='hidden' value='1' />
                   <input name='rm' type='hidden' value='2' />
                   <input name='return' type='hidden' value='".htmlspecialchars($shop->PaymentSuccessPage)."' />
                   <input name='cancel_return' type='hidden' value='".htmlspecialchars($shop->PaymentFailedPage)."' />
                   <input name='currency_code' type='hidden' value='USD' />
                   <input name='notify_url' type='hidden' value='http://".$_SERVER['SERVER_NAME'].$nc_core->SUB_FOLDER.$nc_core->HTTP_ROOT_PATH."modules/netshop/payment/response/paypal.php' />
                  ";

        if (!$to_string) {
            echo $paypal;
            echo "<input type=submit value='".NETCAT_MODULE_NETSHOP_PAYMENT_SUBMIT."'></form>";
            return true;
        } else {
            return $paypal.'</form>';
        }
    }

}
?>