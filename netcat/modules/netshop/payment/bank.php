<?

class Payment_bank {

    private $shop = null;
    private $params = array();

    function __construct($shop) {
        $this->shop = $shop;
    }

    function crc() {
        return md5($this->shop->secret_key . $this->shop->OrderID);
    }

    function create_bill($to_string = false) {
        global $SUB_FOLDER, $HTTP_ROOT_PATH;
        $form = "<form id='fbank' action='" . $SUB_FOLDER . $HTTP_ROOT_PATH . "modules/netshop/post.php' method=get target=_blank>
        <input type=hidden name=action value=print_bill>
        <input type=hidden name=system value=bank>
        <input type=hidden name=mode value=print_bill>
        <input type=hidden name=order_id value=" . $this->shop->OrderID . ">
        <input type=hidden name=key value=" . $this->crc() . ">";
        if (!$to_string) {
            echo $form;
            echo "<input type=submit value='" . NETCAT_MODULE_NETSHOP_BANK_PRINT_BILL . "'></form>";
            return true;
        } else {
            return $form . '</form>';
        }
    }

    // печать квитанции для оплаты через сбербанк
    function print_bill() {
        if ($_GET["key"] != $this->crc()) die(NETCAT_MODULE_NETSHOP_NO_RIGTHS);
        ?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv='Content-Type' content='text/html; charset=<?= nc_Core::get_object()->NC_CHARSET ?>'>
    <title></title>
    <style type=text/css>
        body { font-family: arial; font-size: 11pt; }
        table { border-collapse: collapse; }
        td { border: 1px solid black; font-family: arial; font-size: 11pt; }
        div.billHeader { font-size: 12pt; font-weight: bold; }
        p.p1 { margin: 0; padding: 0; }
        p.p1:first-letter { text-transform: capitalize; }
        p.p2 { margin: 0; padding: 0; }
    </style>
</head>
<body>
    <p>
        <b><u><?= $this->shop->CompanyName ?></u></b>
    </p>

    <p>
        <b><?= NETCAT_MODULE_NETSHOP_BANK_ADDRESS ?>: <?= $this->shop->Address ?>, <?= NETCAT_MODULE_NETSHOP_BANK_PHONE ?>: <?= $this->shop->Phone ?></b>
    </p>

    <p>
        <div align='center'><b><?= NETCAT_MODULE_NETSHOP_BANK_EXAMPLE ?></b></div>
        <br>
        <table cellspacing='0' cellpadding='2' width='100%'>
            <tr>
                <td><?= NETCAT_MODULE_NETSHOP_BANK_INN ?> <?= $this->shop->INN ?></td>
                <td><?= NETCAT_MODULE_NETSHOP_BANK_KPP ?> <?= $this->shop->KPP ?></td>
                <td rowspan='2' valign='bottom' align='center'><?= NETCAT_MODULE_NETSHOP_BANK_BILL ?> &#8470;</td>

                <td rowspan='2' valign='bottom'><?= $this->shop->BankAccount ?></td>
            </tr>
            <tr>
                <td colspan='2'><?= NETCAT_MODULE_NETSHOP_BANK_RECEIVER ?><br><?= $this->shop->CompanyName ?></td>
            </tr>
            <tr>
                <td colspan='2' rowspan='2'><?= NETCAT_MODULE_NETSHOP_BANK_RECEIVER_BANK ?><br><?= $this->shop->BankName ?></td>
                <td align='center'><?= NETCAT_MODULE_NETSHOP_BANK_BIK ?></td>
                <td rowspan='2'><?= $this->shop->BIK ?><br><?= $this->shop->CorrespondentAccount ?></td>
            </tr>
            <tr>
                <td align='center'><?= NETCAT_MODULE_NETSHOP_BANK_BILL ?> &#8470;</td>
            </tr>
        </table>
    </p>

    <p>
        <div align='center' class='billHeader'>
            <?= NETCAT_MODULE_NETSHOP_BANK_BILL_FULL ?> &#8470; <?= $this->shop->OrderID ?><?= NETCAT_MODULE_NETSHOP_BANK_BILL_SUFFIX ?> <?= NETCAT_MODULE_NETSHOP_BANK_FROM ?> <? print strftime("%d") . " " .
            $GLOBALS["NETSHOP_MONTHS_GENETIVE"][(int)strftime("%m")] . " " .
            strftime("%Y"); ?> <?= NETCAT_MODULE_NETSHOP_BANK_YEAR ?>
        </div>
    </p>
    <p>
        <br>
        <?= NETCAT_MODULE_NETSHOP_BANK_CUSTOMER ?>:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<br>

        <?= NETCAT_MODULE_NETSHOP_BANK_PAYER ?>:
    </p>

    <p>
    <table cellspacing='0' cellpadding='2' width='100%'>
        <tr>
            <td align='center'>&#8470;</td>
            <td align='center'><?= NETCAT_MODULE_NETSHOP_BANK_GOODS_TITLE ?></td>
            <td align='center'><?= NETCAT_MODULE_NETSHOP_BANK_UNIT ?></td>

            <td align='center'><?= NETCAT_MODULE_NETSHOP_BANK_AMOUNT ?></td>
            <td align='center'><?= NETCAT_MODULE_NETSHOP_BANK_PRICE ?></td>
            <td align='center'><?= NETCAT_MODULE_NETSHOP_BANK_SUM ?></td>
        </tr>

        <?
        $VAT = 0; // НДС

        foreach ($this->shop->CartContents as $i => $row) {
            print "<tr>
                        <td align='right'>" . ($i + 1) . "</td>
                        <td>$row[Name]</td>
                        <td align='center'>$row[Units]</td>
                        <td align='right'>$row[Qty]</td>
                        <td align='right'>$row[ItemPriceF]</td>
                        <td align='right'>$row[TotalPriceF]</td>
                       </tr>";

            if (strlen($row["VAT"]) || $this->shop->VAT) {
                $VAT += $row["TotalPrice"] *
                    (strlen($row["VAT"]) ? $row["VAT"] : $this->shop->VAT) / 100;
            }
        }

        // доставка отдельной строкой
        if ($this->shop->Order["DeliveryCost"]) {
            $delivery_cost = $this->shop->FormatCurrency($this->shop->Order["DeliveryCost"]);
            print "<tr>
                        <td align='right'>" . ($i + 2) . "</td>
                        <td>" . NETCAT_MODULE_NETSHOP_BANK_SHIPPING . " (" .
                value1("SELECT Name
                                   FROM Message{$this->shop->delivery_methods_table}
                                   WHERE Message_ID='{$this->shop->Order[DeliveryMethod]}'")
                . ")
                        </td>
                        <td align='center'>шт</td>
                        <td align='right'>1</td>
                        <td align='right'>$delivery_cost</td>
                        <td align='right'>$delivery_cost</td>
                       </tr>";
        }


        if ($this->shop->CartDiscountSum) {
            foreach ($this->shop->CartDiscounts as $discount) {
                print "<tr>
                           <td colspan='5' align='right' style='border:none;'>$discount[Name]:</td>
                           <td align='right'>" . ($this->shop->FormatCurrency($discount["Sum"])) . "</td>
                          </tr>";
            }

            if ($VAT) { // пропорционально скидке уменьшить НДС %-/
                $goods_sum = $this->shop->CartFieldSum("ItemPrice");
                $VAT *= (($goods_sum - $this->shop->CartDiscountSum) / $goods_sum);
            }
        }

        $to2paid = $this->shop->CartSum();
        $to2paidf = $this->shop->FormatCurrency($to2paid);
        ?>
        <tr>
            <td colspan='5' align='right' style='border:none;'><b><?= NETCAT_MODULE_NETSHOP_BANK_TOTAL ?>:</b></td>
            <td align='right'><b><?= $to2paidf
                ?></b></td>
        </tr>

        <tr>
            <td colspan='5' align='right' style='border:none;'>
                <b><?= ($VAT ? (NETCAT_MODULE_NETSHOP_BANK_VAT_INCLUDED . ":") : (NETCAT_MODULE_NETSHOP_BANK_VAT_NOT_INCLUDED . "&nbsp;")) ?></b>
            </td>
            <td align='right'><b><?= ($VAT ? $this->shop->FormatCurrency($VAT) : "&mdash;") ?></b></td>
        </tr>
        <tr>
            <td colspan='5' align='right' style='border:none;'><b><?= NETCAT_MODULE_NETSHOP_BANK_TOTAL_SUM ?>:</b></td>
            <td align='right'><b><?= $to2paidf ?></b></td>
        </tr>
    </table>
    </p>
    <p class='p2'>
        <?= NETCAT_MODULE_NETSHOP_BANK_TOTAL_TITLES ?> <?= $this->shop->CartCount() + ($this->shop->Order["DeliveryCost"] ? 1 : 0) ?>, <?= NETCAT_MODULE_NETSHOP_BANK_WITH_SUM ?>
        <?
        // pheow :-(
        $currency = $this->shop->CurrencyDetails[$this->shop->Currencies[$this->shop->Order["OrderCurrency"]]];
        print "$to2paidf (";
        print netshop_language_in_words($to2paid, $currency["NameCases"], $currency["DecimalName"]);
        print ")";
        ?>
    </p>
    <b><?= NETCAT_MODULE_NETSHOP_BANK_TIP ?></b>
    </body>
    </html>

    <?
    }
}

?>