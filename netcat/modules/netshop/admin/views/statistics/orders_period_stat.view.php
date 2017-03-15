<?php if (!class_exists('nc_core')) { die; } ?>

<div class="nc--clearfix"></div>

<div class="nc-margin-20">
    <div class='nc-chart' id='nc_orders_chart'></div>
</div>

<div class="nc-margin-20">
    <div class='nc-chart' id='nc_goods_chart'></div>
</div>

<div class="nc-margin-20">
    <div class='nc-chart' id='nc_successful_orders_chart'></div>
</div>

<div class="nc--clearfix"></div>

<script type="text/javascript">
(function(){
    var stat = <?=$chart_stat ?>;
    nc.chart.set_defaults(<?=$chart_defaults ?>);
    if (nc.key_exists('total_orders', stat)) {
        stat.purchased_goods.lines  = {show:true};
        stat.purchased_goods.points = {show:true};
        stat.purchased_goods.bars   = {show:false};
        stat.purchased_goods.color  = 2;
        stat.completed_orders.color = 3;
        nc.chart(nc('#nc_orders_chart'), [stat.total_orders, stat.completed_orders, stat.purchased_goods]);
        nc.chart(nc('#nc_goods_chart'), [stat.sales_amount]);
        nc.chart(nc('#nc_successful_orders_chart'), [stat.successful_orders_percentage]);
    } else {
        nc('#nc_orders_chart,#nc_goods_chart,#nc_successful_orders_chart').html('<div class="nc-padding-20"><?=NETCAT_MODULE_NETSHOP_DATA_NOT_AVAILABLE ?></div>');
    }
})();
</script>