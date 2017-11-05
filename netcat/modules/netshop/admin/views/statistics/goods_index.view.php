<?php if (!class_exists('nc_core')) { die; } ?>

<div class="nc-padding-10">
    <?= $ui->controls->site_select($catalogue_id) ?>
</div>

<div id="nc_netshop_stat">

    <div class="nc-panel nc--left nc-margin-10">
    	<div class="nc-panel-header"><?=NETCAT_MODULE_NETSHOP_GOODS_BY_QTY ?></div>
        <div class="nc-panel-content nc-bg-lighten">
            <table class='nc-table nc--striped'>
                <tr>
                    <th><?=NETCAT_MODULE_NETSHOP_BANK_GOODS_TITLE ?></th>
                    <th><b><?=NECTAT_MODULE_NETSHOP_PURCHASED_GOODS ?></b></th>
                    <th><?=NECTAT_MODULE_NETSHOP_SALES_AMOUNT ?></th>
                </tr>
                <? foreach ($goods_by_qty as $row): ?>
                <tr class='nc-text-right'>
                    <td class='nc-text-left'>
                    	<? if ($row['Name']): ?>
                    		<a href="<?=$SUB_FOLDER . $HTTP_ROOT_PATH ?>full.php?sub=<?=$row['Subdivision_ID'] ?>&cc=<?=$row['Sub_Class_ID'] ?>&message=<?=$row['Message_ID'] ?>"><?=$row['Name'] ?></a>
                    	<? else: ?>
                    		<span class='nc-text-grey'>Unknown product</span>
                    	<? endif ?>
                    </td>
                    <td><b><?=$row['Qty'] ?></b></td>
                    <td><?=$row['SalesAmount'] ?></td>
                </tr>
                <? endforeach ?>
            </table>
        </div>
    </div>

    <div class="nc-panel nc--left nc-margin-10">
    	<div class="nc-panel-header"><?=NETCAT_MODULE_NETSHOP_GOODS_BY_SALES_AMOUNT ?></div>
        <div class="nc-panel-content nc-bg-lighten">
            <table class='nc-table nc--striped'>
                <tr>
                    <th><?=NETCAT_MODULE_NETSHOP_BANK_GOODS_TITLE ?></th>
                    <th><?=NECTAT_MODULE_NETSHOP_PURCHASED_GOODS ?></th>
                    <th><b><?=NECTAT_MODULE_NETSHOP_SALES_AMOUNT ?></b></th>
                </tr>
                <? foreach ($goods_by_sales_amount as $row): ?>
                <tr class='nc-text-right'>
                    <td class='nc-text-left'>
                    	<? if ($row['Name']): ?>
                    		<a href="<?=$SUB_FOLDER . $HTTP_ROOT_PATH ?>full.php?sub=<?=$row['Subdivision_ID'] ?>&cc=<?=$row['Sub_Class_ID'] ?>&message=<?=$row['Message_ID'] ?>"><?=$row['Name'] ?></a>
                    	<? else: ?>
                    		<span class='nc-text-grey'>Unknown product</span>
                    	<? endif ?>
                    </td>
                    <td><?=$row['Qty'] ?></td>
                    <td><b><?=$row['SalesAmount'] ?></b></td>
                </tr>
                <? endforeach ?>
            </table>
        </div>
    </div>

	<div class="nc--clearfix"></div>

    <? if ($pagination): ?>
        <div class="nc-margin-10"><?=$pagination ?></div>
    <? endif ?>

</div>