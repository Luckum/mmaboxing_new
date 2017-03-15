<style>
.nc-netshop-form-w input {background: #FFFCF9}
.nc-netshop-form-w td input {margin:-5px 0 !important; width:100%; display: block !important;}
.nc-netshop-form {border-collapse: collapse; width: 100%; margin:20px 0}
.nc-netshop-form td, .nc-netshop-form th {border:1px solid #000; padding:5px 15px; height:auto;}
.nc-netshop-form th {font-weight: bold; padding: 10px 15px}
</style>

<div class="nc-netshop-form-w">
<?=$form->company_name ?>, <?=NETCAT_MODULE_NETSHOP_BANK_INN ?> <?=$form->company_inn ?><br>
<?=$form->company_address ?><br>
<div style='font-weight:bold; text-align:center'>
<?=NETCAT_MODULE_NETSHOP_CASHMEMO ?> № <?=$order->Message_ID ?> от <?=$current_date ?> г.
</div>

<table class='nc-netshop-form'>
    <col width=1 /><col /><col width=10% /><col width=10% /><col width=10% />
    <tr>
        <th>№</th>
        <th>Товар</th>
        <th style='text-align:center'><?=NETCAT_MODULE_NETSHOP_BANK_PRICE ?></th>
        <th style='text-align:center'><?=NETCAT_MODULE_NETSHOP_BANK_AMOUNT ?></th>
        <th style='text-align:center'><?=NETCAT_MODULE_NETSHOP_BANK_SUM ?></th>
    </tr>
    <? foreach ($order_items as $i => $product): ?>
    <tr>
        <td><?=$i+1 ?></td>
        <td><?=$product['FullName'] ?></td>
        <td style='text-align:right'><?=$product['ItemPriceF'] ?></td>
        <td style='text-align:center'><?=$product['Qty'] ?></td>
        <td style='text-align:right'><?=$product['TotalPriceF'] ?></td>
    </tr>
    <? endforeach ?>
    <tr style='font-weight:bold'>
        <td colspan=3 style='text-align:right;'><?=NETCAT_MODULE_NETSHOP_BANK_TOTAL ?>:</td>
        <td style='text-align:center'><?=$order_items->count() ?></td>
        <td style='text-align:right'><?=number_format($order_items->sum('TotalPrice'), 0, '.', ' ') ?> <?=NETCAT_MODULE_NETSHOP_1C_CURRENCY_DEFAULT ?></td>
    </tr>
</table>
<br>
<?=NETCAT_MODULE_NETSHOP_CASHMEMO_SELLER ?>: ______________________ <?=$form->seller_position ?> <?=$form->seller_fio ?>
</div>