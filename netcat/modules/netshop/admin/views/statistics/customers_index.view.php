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
                    <th><?=NETCAT_MODULE_NETSHOP_CUSTOMERS ?></th>
                    <th></th>
                    <th><b><?=NECTAT_MODULE_NETSHOP_TOTAL_ORDERS ?></b></th>
                    <th><?=NECTAT_MODULE_NETSHOP_PURCHASED_GOODS ?></th>
                    <th><?=NECTAT_MODULE_NETSHOP_SALES_AMOUNT ?></th>
                </tr>
                <? foreach ($customers_by_tatal_orders as $row): ?>
                <tr class='nc-text-right'>
                    <td class='nc-text-left'>
                    	<? if ($row['User_ID']): ?>
                    		<a href="<?=$ADMIN_PATH ?>user/index.php?phase=4&amp;UserID=<?=$row['User_ID'] ?> ?>"><?=$row['ContactName'] ?></a>
                    	<? else: ?>
                    		<?=$row['ContactName'] ?>
                    	<? endif ?>
                    </td>
                    <td class='nc-text-left'><?=$row['Email'] ?></td>
                    <td><b><?=$row['TotalOrders'] ?></b></td>
                    <td><?=$row['TotalGoods'] ?></td>
                    <td><?=$row['TotalPrice'] ?></td>
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