<?

class Payment_sberbank {

    private $shop = null;
    private $params = array();

    function __construct(&$shop) { // constructor
        $this->shop = & $shop;
    }

    function crc() {
        return md5($this->shop->secret_key .
                $this->shop->OrderID
        );
    }

    function create_bill($to_string = false) {
        global $SUB_FOLDER, $HTTP_ROOT_PATH;
        $form = "<form id='fsberbank' action='" . $SUB_FOLDER . $HTTP_ROOT_PATH . "modules/netshop/post.php' method=get target=_blank>
        <input type=hidden name=action value=print_bill>
        <input type=hidden name=system value=sberbank>
        <input type=hidden name=mode value=print_bill>
        <input type=hidden name=order_id value=" . $this->shop->OrderID . ">
        <input type=hidden name=key value=" . $this->crc() . ">";
        if (!$to_string) {
            echo $form;
            echo "<input type=submit value='" . NETCAT_MODULE_NETSHOP_SBERBANK_PRINT_BILL . "'></form>";
            return true;
        } else {
            return $form . '</form>';
        }
    }

    // печать квитанции для оплаты через сбербанк
    function print_bill() {
        if ($_GET["key"] != $this->crc()) die(NETCAT_MODULE_NETSHOP_NO_RIGTHS);

        $RUR = $this->shop->CurrencyDetails["RUR"]["Currency"];
        $sum = $this->shop->ConvertCurrency($this->shop->CartSum(), $this->shop->DefaultCurrencyID, $RUR);
        $sum = $this->shop->FormatCurrency($sum, 'RUR');
        ?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv='Content-Type' content='text/html; charset=<?= nc_Core::get_object()->NC_CHARSET ?>'>
</head>
<body>
<table CELLSPACING="0" BORDER="1" CELLPADDING="3" WIDTH="640" bordercolorlight="#000000" bordercolordark="#FFFFFF">
    <tr>
        <td ALIGN="left" WIDTH="240" VALIGN="middle">
            &nbsp;&nbsp;<b><?= NETCAT_MODULE_NETSHOP_SBERBANK_NOTICE ?></b>
            <br><br><br><br><br><br><br><br><br><br><br><br><br>
            &nbsp;&nbsp;<?= NETCAT_MODULE_NETSHOP_SBERBANK_CASHIER ?><br>
        </td>
        <td ALIGN="right" WIDTH="400" VALIGN="middle">

            <table CELLSPACING="0" BORDER="1" CELLPADDING="3" WIDTH="410" bordercolorlight="#000000" height=100% bordercolordark="#FFFFFF">
                <tr>
                    <td colspan="3">
                        <?= NETCAT_MODULE_NETSHOP_SBERBANK_PAYMENT_RECEIVER ?>: <?= $this->shop->CompanyName ?><br>
                        <?= NETCAT_MODULE_NETSHOP_SBERBANK_INN ?>: <?= $this->shop->INN ?><br>
                        <?= NETCAT_MODULE_NETSHOP_SBERBANK_RS ?>: <?= $this->shop->BankAccount ?>, <?= $this->shop->BankName ?>
                        <br>
                        <?= NETCAT_MODULE_NETSHOP_SBERBANK_KS ?>: <?= $this->shop->CorrespondentAccount ?><br>
                        <?= NETCAT_MODULE_NETSHOP_SBERBANK_KPP ?>: <?= $this->shop->KPP ?><br>
                        <?= NETCAT_MODULE_NETSHOP_SBERBANK_BIK ?>: <?= $this->shop->BIK ?>
                    </td>
                </tr>
                <tr>
                    <td COLSPAN="3">
                        <br><br>
                        <hr size="1" color="#000000">
                        <div align="center" style="font-family: sans-serif; font-size: xx-small"><?= NETCAT_MODULE_NETSHOP_SBERBANK_NAME_ADDR ?></div>
                    </td>
                </tr>
                <tr>
                    <td ALIGN="center"><?= NETCAT_MODULE_NETSHOP_SBERBANK_PAYMENT_TYPE ?></td>
                    <td ALIGN="center" width=15%><?= NETCAT_MODULE_NETSHOP_SBERBANK_DATE ?></td>
                    <td ALIGN="center" width=15%><?= NETCAT_MODULE_NETSHOP_SBERBANK_AMOUNT ?></td>
                </tr>
                <tr>
                    <td ALIGN="left"><? printf(NETCAT_MODULE_NETSHOP_PAYMENT_DESCRIPTION, $this->shop->OrderID, $this->shop->ShopName); ?></td>
                    <td valign="bottom">__________</td>
                    <td valign="bottom"><?= $sum ?></td>
                </tr>
                <tr>
                    <td ALIGN="left" ROWSPAN="2" colspan="3" valign="center"><?= NETCAT_MODULE_NETSHOP_SBERBANK_PAYER ?>:</td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        <td ALIGN="left" WIDTH="240" VALIGN="middle">
            &nbsp;&nbsp;<b><?= NETCAT_MODULE_NETSHOP_SBERBANK_RECEIPT ?></b>
            <br><br><br><br><br><br><br><br><br><br><br><br><br>
            &nbsp;&nbsp;<?= NETCAT_MODULE_NETSHOP_SBERBANK_CASHIER ?><br>
            </span>
        </td>
        <td ALIGN="right" VALIGN="middle">
            <table CELLSPACING="0" BORDER="1" CELLPADDING="3" WIDTH="410" height=100% bordercolorlight="#000000" bordercolordark="#FFFFFF">
                <tr>
                    <td colspan="3">
                        <?= NETCAT_MODULE_NETSHOP_SBERBANK_PAYMENT_RECEIVER ?>: <?= $this->shop->CompanyName ?><br>
                        <?= NETCAT_MODULE_NETSHOP_SBERBANK_INN ?>: <?= $this->shop->INN ?><br>
                        <?= NETCAT_MODULE_NETSHOP_SBERBANK_RS ?>: <?= $this->shop->BankAccount ?>, <?= $this->shop->BankName ?>
                        <br>
                        <?= NETCAT_MODULE_NETSHOP_SBERBANK_KS ?>: <?= $this->shop->CorrespondentAccount ?><br>
                        <?= NETCAT_MODULE_NETSHOP_SBERBANK_KPP ?>: <?= $this->shop->KPP ?><br>
                        <?= NETCAT_MODULE_NETSHOP_SBERBANK_BIK ?>: <?= $this->shop->BIK ?>
                    </td>
                </tr>
                <tr>
                    <td COLSPAN="3"><br><br>
                        <hr size="1" color="#000000">
                        <div align="center" style="font-family: sans-serif; font-size: xx-small"><?= NETCAT_MODULE_NETSHOP_SBERBANK_NAME_ADDR ?></div>
                    </td>
                </tr>
                <tr>
                    <td ALIGN="center"><?= NETCAT_MODULE_NETSHOP_SBERBANK_PAYMENT_TYPE ?></td>
                    <td ALIGN="center"><?= NETCAT_MODULE_NETSHOP_SBERBANK_DATE ?></td>
                    <td ALIGN="center"><?= NETCAT_MODULE_NETSHOP_SBERBANK_AMOUNT ?></td>
                </tr>
                <tr>
                    <td ALIGN="left"><? printf(NETCAT_MODULE_NETSHOP_PAYMENT_DESCRIPTION, $this->shop->OrderID, $this->shop->ShopName); ?></td>
                    <td valign="bottom">__________</td>
                    <td valign="bottom"><?= $sum ?></td>
                </tr>
                <tr>
                    <td ALIGN="left" ROWSPAN="2" colspan="3" valign="center"><?= NETCAT_MODULE_NETSHOP_SBERBANK_PAYER ?>:</td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
    <?
    }
}

?>