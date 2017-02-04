<?php if (!class_exists('nc_core')) { die; } ?>

<?=$chart_init ?>
<?=$stat_init ?>

<?= $ui->controls->site_select($catalogue_id) ?>

<script>
    nc_netshop_stat_settings.action = 'orders';
    nc_netshop_stat_settings.group_by = 'day';
</script>

<div style='display:none; z-index:10000' id='nc_calendar_popup_from_d'></div>
<div style='display:none; z-index:10000' id='nc_calendar_popup_to_d'></div>
<script>
function nc_netshop_period_form(show, el) {
    if (show) {
        nc('#nc_netshop_stat_period_form').slideDown();
    } else {
        nc('#nc_netshop_stat_period_form').hide();
    }

    if (el) {
        nc(el).parents('ul').find('li').removeClass('nc--active');
        nc(el).parents('li').addClass('nc--active');

        var $panel_content = nc(el).parents('div.nc-panel').find('div.nc-panel-content');
        $panel_content.animate({opacity:.2}, 100);
    }

    return false;
}
function nc_netshop_show_period_stat() {
    var v = function(name) {
        return nc('#nc_netshop_stat_period_form input[name='+name+']').val();
    }
    var d = function(name) {
        return v(name + '_y') + '-' + v(name + '_m') + '-' + v(name + '_d');
    }

    return nc_netshop_get_stat(nc('#nc_netshop_period_btn'), {period:d('from') + ':' + d('to')});
}
</script>
<div id="nc_netshop_stat">

    <div class="nc-panel">
        <ul class="nc-nav-pills nc--right">
            <li><?= NETCAT_MODULE_NETSHOP_GROUP_BY ?>:</li>
            <li class='nc--active'><a onclick="return nc_netshop_get_stat(this, {group_by:'day'})" href="#"><?=NETCAT_MODULE_NETSHOP_BY_DAYS ?></a></li>
            <li><a onclick="return nc_netshop_get_stat(this, {group_by:'week'})" href="#"><?=NETCAT_MODULE_NETSHOP_BY_WEEKS ?></a></li>
            <li><a onclick="return nc_netshop_get_stat(this, {group_by:'month'})" href="#"><?=NETCAT_MODULE_NETSHOP_BY_MONTHS ?></a></li>
        </ul>
        <ul class="nc-tabs nc--small">
            <li><a onclick="nc_netshop_period_form(0); return nc_netshop_get_stat(this, {period:'7days'})" href="#"><?=NETCAT_MODULE_NETSHOP_7_DAYS ?></a></li>
            <li><a onclick="nc_netshop_period_form(0); return nc_netshop_get_stat(this, {period:'30days'})" href="#"><?=NETCAT_MODULE_NETSHOP_30_DAYS ?></a></li>
            <li><a id='nc_netshop_period_btn' onclick="return nc_netshop_period_form(1, this)" href="#"><?=NETCAT_MODULE_NETSHOP_OVER_PERIOD ?></a></li>
        </ul>

        <div id='nc_netshop_stat_period_form' class='nc-form nc-padding-10 nc--hide'>
            <?= NETCAT_MODULE_MINISHOP_FILTER_FROM ?>
            <input name="from_d" type="text" class='nc--mini' /> .
            <input name="from_m" type="text" class='nc--mini' /> .
            <input name="from_y" type="text" class='nc--small' />
            <span style='position:relative'>
                <button id='nc_calendar_popup_img_from_d' class='nc-btn nc--light' onclick="nc_calendar_popup('from_d', 'from_m', 'from_y')"><i class="nc-icon nc--calendar"></i></button>
            </span>
            &nbsp;&nbsp;&nbsp;
            <?= NETCAT_MODULE_MINISHOP_FILTER_TO ?>
            <input name="to_d" type="text" class='nc--mini' /> .
            <input name="to_m" type="text" class='nc--mini' /> .
            <input name="to_y" type="text" class='nc--small' />
            <span style='position:relative'>
                <button id='nc_calendar_popup_img_to_d' class='nc-btn nc--light' onclick="nc_calendar_popup('to_d', 'to_m', 'to_y')"><i class="nc-icon nc--calendar"></i></button>
            </span>

            <button onclick="nc_netshop_show_period_stat()" class='nc-btn nc--blue'><?= NETCAT_MODULE_MINISHOP_FILTER_SHOW ?></button>
        </div>
        <div class="nc-panel-content nc-bg-lighten"></div>
    </div>

    <div class="nc-panel">
        <div class="nc-panel-content nc-bg-lighten">
            <div class="nc--clearfix"></div>
            <div class="nc--left nc-margin-10">
                <table class='nc-table nc--bordered nc--striped'>
                    <tr>
                        <th></th>
                        <th><?=NETCAT_MODULE_NETSHOP_TODAY ?></th>
                        <th><?=NETCAT_MODULE_NETSHOP_YESTERDAY ?></th>
                        <th><?=NETCAT_MODULE_NETSHOP_AVG_FOR_7_DAYS ?></th>
                    </tr>
                    <? foreach ($totals['day']['today'] as $k => $value): ?>
                    <tr class='nc-text-right'>
                        <td class='nc-text-left'><?=constant('NECTAT_MODULE_NETSHOP_' . strtoupper($k)) ?></td>
                        <? foreach ($totals['day'] as $period => $period_stat): ?>
                            <td><?=$period_stat[$k] ?></td>
                        <? endforeach ?>
                    </tr>
                    <? endforeach ?>
                </table>
            </div>
            <div class="nc--left nc-margin-10">
                <table class='nc-table nc--bordered nc--striped'>
                    <tr>
                        <th><?=NETCAT_MODULE_NETSHOP_WEEK ?></th>
                        <th><?=NETCAT_MODULE_NETSHOP_LAST_WEEK ?></th>
                    </tr>
                    <? foreach ($totals['week']['week'] as $k => $value): ?>
                    <tr class='nc-text-right'>
                        <? foreach ($totals['week'] as $period => $period_stat): ?>
                            <td><?=$period_stat[$k] ?></td>
                        <? endforeach ?>
                    </tr>
                    <? endforeach ?>
                </table>
            </div>
            <div class="nc--left nc-margin-10">
                <table class='nc-table nc--bordered nc--striped'>
                    <tr>
                        <th><?=NETCAT_MODULE_NETSHOP_MONTH ?></th>
                        <th><?=NETCAT_MODULE_NETSHOP_LAST_MONTH ?></th>
                    </tr>
                    <? foreach ($totals['month']['month'] as $k => $value): ?>
                    <tr class='nc-text-right'>
                        <? foreach ($totals['month'] as $period => $period_stat): ?>
                            <td><?=$period_stat[$k] ?></td>
                        <? endforeach ?>
                    </tr>
                    <? endforeach ?>
                </table>
            </div>
            <div class="nc--left nc-margin-10">
                <table class='nc-table nc--bordered nc--striped' style='text-transform:capitalize'>
                    <tr>
                        <th><?=NETCAT_MODULE_NETSHOP_ORDER_STATUS ?></th>
                        <th><?=NECTAT_MODULE_NETSHOP_TOTAL_ORDERS ?></th>
                    </tr>
                    <? foreach ($order_status_counts as $name => $count): ?>
                    <tr>
                        <td><?=$name ?></td>
                        <td class='nc-text-center'><?=$count ?></td>
                    </tr>
                    <? endforeach ?>
                </table>
            </div>

            <div class="nc--clearfix"></div>
        </div>
    </div>

</div>

<script>
    // activate first tab in all panels
    nc('#nc_netshop_stat ul.nc-tabs>li>a').first().click();
</script>