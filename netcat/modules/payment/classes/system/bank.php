<?

class nc_payment_system_bank extends nc_payment_system {

    protected $automatic = FALSE;

    // принимаемые валюты
    protected $accepted_currencies = array('RUB', 'RUR');

    // параметры сайта в платежной системе
    protected $settings = array(
        'companyName' => null,
        'companyAddress' => null,
        'companyPhone' => null,
        'receiver' => null,
        'INN' => null,
        'bankAccount' => null,
        'bankName' => null,
        'correspondentAccount' => null,
        'KPP' => null,
        'BIK' => null,
        'VAT' => null,
        'StampImageURL' => null,
    );

    public function execute_payment_request(nc_payment_invoice $invoice) {
        if ($invoice->get('order_source') !== 'netshop') {
            die("Cannot process invoice with " . __CLASS__ . ": order_source is not supported");
        }

        $netshop = nc_netshop::get_instance();

        $order = $netshop->load_order($invoice->get('order_id'));
        if (!$order) {
            die("Order with id=$invoice[order_id] not found");
        }

        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta http-equiv='Content-Type' content='text/html; charset=<?= nc_core()->NC_CHARSET; ?>'>
            <title></title>
            <style type=text/css>
                body { font-family: arial, sans-serif; font-size: 11pt; }
                table { border-collapse: collapse; }
                td { border: 1px solid black; font-family: arial, sans-serif; font-size: 11pt; }
                div.billHeader { font-size: 12pt; font-weight: bold; }
                p.p1 { margin: 0; padding: 0; }
                p.p1:first-letter { text-transform: capitalize; }
                p.p2 { margin: 0; padding: 0; }
            </style>
        </head>
        <body>
        <p><b><u><?= $this->get_setting('companyName'); ?></u></b></p>

        <p>
            <b><?= NETCAT_MODULE_NETSHOP_BANK_ADDRESS ?>: <?= $this->get_setting('companyAddress'); ?>, <?= NETCAT_MODULE_NETSHOP_BANK_PHONE ?>: <?= $this->get_setting('companyPhone'); ?></b>
        </p>

        <div align='center'><b><?= NETCAT_MODULE_NETSHOP_BANK_EXAMPLE ?></b></div>
        <br>
        <table cellspacing='0' cellpadding='2' width='100%'>
            <tr>
                <td><?= NETCAT_MODULE_NETSHOP_BANK_INN ?> <?= $this->get_setting('INN'); ?></td>
                <td><?= NETCAT_MODULE_NETSHOP_BANK_KPP ?> <?= $this->get_setting('KPP'); ?></td>
                <td rowspan='2' valign='bottom' align='center'><?= NETCAT_MODULE_NETSHOP_BANK_BILL ?> &#8470;</td>
                <td rowspan='2' valign='bottom'><?= $this->get_setting('bankAccount'); ?></td>
            </tr>
            <tr>
                <td colspan='2'><?= NETCAT_MODULE_NETSHOP_BANK_RECEIVER ?><br><?= $this->get_setting('companyName'); ?>
                </td>
            </tr>
            <tr>
                <td colspan='2' rowspan='2'><?= NETCAT_MODULE_NETSHOP_BANK_RECEIVER_BANK ?>
                    <br><?= $this->get_setting('bankName'); ?></td>
                <td align='center'><?= NETCAT_MODULE_NETSHOP_BANK_BIK ?></td>
                <td rowspan='2'><?= $this->get_setting('BIK'); ?><br><?= $this->get_setting('correspondentAccount'); ?>
                </td>
            </tr>
            <tr>
                <td align='center'><?= NETCAT_MODULE_NETSHOP_BANK_BILL ?> &#8470;</td>
            </tr>
        </table>


        <div align='center' class='billHeader'>
            <?= NETCAT_MODULE_NETSHOP_BANK_BILL_FULL ?> &#8470; <?= $invoice->get_id(); ?><?= NETCAT_MODULE_NETSHOP_BANK_BILL_SUFFIX ?> <?= NETCAT_MODULE_NETSHOP_BANK_FROM ?>
            <?php
            $month_names = explode("/", NETCAT_MODULE_NETSHOP_MONTHS_GENITIVE);
            print strftime("%d") . " " .
                $month_names[(int)strftime("%m")] . " " .
                strftime("%Y");
            ?>
            <?= NETCAT_MODULE_NETSHOP_BANK_YEAR ?>
        </div>

        <p>
            <br>
            <?= NETCAT_MODULE_NETSHOP_BANK_CUSTOMER ?>: <?= $invoice->get('customer_name'); ?><br>

            <?= NETCAT_MODULE_NETSHOP_BANK_PAYER ?>: <?= $invoice->get('customer_name'); ?>
        </p>

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


            $i = 0;

            $items = $order->get_items();

            $total_original_sum = 0;
            $total_items_sum = 0;

            foreach ($items as $item) {
                $i++;
                $original_sum = $item['OriginalPrice'] * $item['Qty'];
                $total_original_sum += $original_sum;
                $total_items_sum += $item['ItemPrice'];
                print "<tr>
                        <td align='right'>" . $i . "</td>
                        <td>{$item['Vendor']} {$item['Name']}</td>
                        <td align='center'>$item[Units]</td>
                        <td align='right'>$item[Qty]</td>
                        <td align='right'>$item[OriginalPriceF]</td>
                        <td align='right'>" . $netshop->format_price($original_sum) . "</td>
                       </tr>";

                if (strlen($item["VAT"]) || $this->get_setting('VAT')) {
                    $VAT += $item["TotalPrice"] *
                        (strlen($item["VAT"]) ? $item["VAT"] : $this->get_setting('VAT')) / 100;
                }
            }

            // доставка отдельной строкой
            $shipping_sum = $order['DeliveryCost'] + $order['PaymentCost'];

            if ($shipping_sum) {
                echo "<tr>",
                    "<td align='right'>" . ($i + 1) . "</td>",
                    "<td>" . NETCAT_MODULE_NETSHOP_BANK_SHIPPING . "</td>",
                "<td align='center'>шт</td>",
                "<td align='right'>1</td>",
                "<td align='right'>{$shipping_sum}</td>",
                "<td align='right'>{$shipping_sum}</td>",
                "</tr>";
            }

            $discount = $order->get_discount_sum();

            if ($discount) {
                echo "<tr>",
                    "<td colspan='5' align='right' style='border:none;'><b>" . NETCAT_MODULE_NETSHOP_PROMOTION_DISCOUNT_AMOUNT . ":</b></td>",
                    "<td align='right'><b>" . ($netshop->format_price($discount)) . "</b></td>",
                "</tr>";

                if ($VAT) { // пропорционально скидке уменьшить НДС %-/
                    $VAT *= (($total_original_sum - $discount) / $total_original_sum);
                }
            }

            $total_sum = $order->get_totals();
            $total_sum_formatted = $netshop->format_price($total_sum);
            ?>
            <tr>
                <td colspan='5' align='right' style='border:none;'><b><?= NETCAT_MODULE_NETSHOP_BANK_TOTAL ?>:</b></td>
                <td align='right'><b><?= $total_sum_formatted ?></b></td>
            </tr>

            <tr>
                <td colspan='5' align='right' style='border:none;'>
                    <b><?= ($VAT ? (NETCAT_MODULE_NETSHOP_BANK_VAT_INCLUDED . ":") : (NETCAT_MODULE_NETSHOP_BANK_VAT_NOT_INCLUDED . "&nbsp;")) ?></b>
                </td>
                <td align='right'><b><?= ($VAT ? $netshop->format_price($VAT) : "&mdash;") ?></b></td>
            </tr>
            <tr>
                <td colspan='5' align='right' style='border:none;'><b><?= NETCAT_MODULE_NETSHOP_BANK_TOTAL_SUM ?>:</b>
                </td>
                <td align='right'><b><?= $total_sum_formatted ?></b></td>
            </tr>
        </table>

        <p class='p2'>
            <?= NETCAT_MODULE_NETSHOP_BANK_TOTAL_TITLES ?>
            <?= count($items) + ($order["DeliveryCost"] ? 1 : 0) ?>,
            <?= NETCAT_MODULE_NETSHOP_BANK_WITH_SUM ?>
            <?
            $currency_details = $netshop->get_setting('CurrencyDetails');
            $currencies = $netshop->get_setting('Currencies');
            $currency = $currency_details[$currencies[$order['OrderCurrency']]];
            echo $total_sum_formatted . " (",
            netshop_language_in_words($total_sum, $currency["NameCases"], $currency["DecimalName"]),
            ")";
            ?>
        </p>
        <b><?= NETCAT_MODULE_NETSHOP_BANK_TIP ?></b>
        <? if ($this->get_setting('StampImageURL')) { ?>
            <br/>
            <img src="<?= $this->get_setting('StampImageURL'); ?>" alt=""/>
        <? } ?>

        </body>
        </html>
    <?
    }

    public function on_response(nc_payment_invoice $invoice = null) {
    }

    public function validate_payment_request_parameters() {
    }

    public function validate_payment_callback_response(nc_payment_invoice $invoice = null) {
    }

}