<?

class Payment_webmoney {

    var $shop;
 // object
    var $params; // array

    function __construct(&$shop) { // constructor
        $this->shop = &$shop;
        // load order

        if (!$shop->OrderID && $_POST["LMI_PAYMENT_NO"]) {
            $this->shop->LoadOrder($_POST["LMI_PAYMENT_NO"]);
        }

        $this->params = array(
                "LMI_PAYEE_PURSE" => $shop->WebmoneyPurse,
                "LMI_PAYMENT_AMOUNT" => $shop->CartSum(),
                "LMI_PAYMENT_NO" => $shop->OrderID,
                "LMI_SIM_MODE" => 0
        );
    }

    // создание формы на формирование счета
    function create_bill($to_string = false) {
        $form = "<form id='fwebmoney' action='https://merchant.webmoney.ru/lmi/payment.asp' method='post'>";
        $arr = array_merge($this->params, array("LMI_PAYMENT_DESC" =>
                        sprintf(NETCAT_MODULE_NETSHOP_PAYMENT_DESCRIPTION, $this->shop->OrderID, $this->shop->ShopName)
                ));

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

    // знаем, что данные пришли через post
    // данные заказа подгружаются автоматически (OrderID = LMI_PAYMENT_NO)
    function success() {
        // предварительная проверка со стороны платежной системы перед получением средств?
        if ($_POST["LMI_PREREQUEST"] == "1") {
            $ok = true;
            ob_end_clean();

            foreach ($this->params as $k => $v) {
                if ($v != $_POST[$k]) {
                    $ok = false;
                    break;
                }
            }

            // Если все правильно, отвечаем YES
            print ($ok ? "YES" : "Something went wrong");

            // that's all folks
            die();
        }


        // оповещение об успешной оплате
        // Проверить пришедшие данные
        if ($this->hash($this->params) == $_POST["LMI_HASH"]) { // OK
            // записать факт в комменты, изменить статус
//         $payinfo = $this->shop->PaymentInfo;
            $payinfo = sprintf(NETCAT_MODULE_NETSHOP_PAYMENT_LOG,
                            // ПС, сумма, дата, номер транзакции, id покупателя
                            "WebMoney", $this->shop->FormatCurrency($_POST["LMI_PAYMENT_AMOUNT"], "", 1), $_POST["LMI_SYS_TRANS_DATE"], $_POST["LMI_SYS_TRANS_NO"], $_POST["LMI_PAYER_WM"]
            );

            q("UPDATE Message{$this->shop->order_table}
            SET PaymentInfo='".mysql_real_escape_string($payinfo)."'
            WHERE Message_ID = {$this->shop->OrderID}");
        } else {
            die("LMI_HASH IS WRONG");
        }
    }

    // платеж не прошел
    function failed() {
        
    }

    // проверка md5 (на входе массив со значениями)
    function hash($arr) {
        extract($arr);

        $string = $LMI_PAYEE_PURSE.
                $LMI_PAYMENT_AMOUNT.
                $LMI_PAYMENT_NO.
                $_POST["LMI_MODE"].
                $_POST["LMI_SYS_INVS_NO"].
                $_POST["LMI_SYS_TRANS_NO"].
                $_POST["LMI_SYS_TRANS_DATE"].
                $this->shop->WebmoneySecretKey.
                $_POST["LMI_PAYER_PURSE"].
                $_POST["LMI_PAYER_WM"];

        return strtoupper(md5($string));
    }

}
?>