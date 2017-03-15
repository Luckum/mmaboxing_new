<?php if (!class_exists('nc_core')) { die; } ?>

<?= $ui->controls->site_select($catalogue_id) ?>

<table class="nc-table nc--bordered nc--wide">
<tr>
    <th class='nc--compact'></th>
    <th><?=$fields['Name']['title'] ?></th>
<!--    <th>--><?//=$fields['PaymentSystem_ID']['title'] ?><!--</th>-->
    <th class='nc--compact'></th>
    <th class='nc--compact'></th>
</tr>
<? foreach ($methods as $row): ?>
    <?php
        $checked = $row['Checked'];
        $id = $row['PaymentMethod_ID'];
        $edit_hash = "#module.netshop.payment.edit($id)";
        $remove_action = $current_url . 'remove&id=' . $id;
        $status_action = $current_url . 'toggle&Checked=' . (int)(!$row['Checked']) . '&id=' . $id;

        $method = new nc_netshop_payment_method();
        $method->set_values_from_database_result($row);
        $condition_info = $method->get_condition_description(false);
        if ($condition_info) { $condition_info = NETCAT_MODULE_NETSHOP_COND . $condition_info; }

    ?>
    <tr>
        <td><a href="<?=$status_action ?>" class='nc-label nc--<?=$checked ? 'green' : 'red' ?>'>
                <?=$checked ? NETCAT_MODERATION_OBJ_ON : NETCAT_MODERATION_OBJ_OFF ?></a>
        </td>
        <td>
            <?= $ui->helper->hash_link($edit_hash, $row['Name'], 'nc-netshop-list-item-title') ?>
            <div class="nc-netshop-list-condition-info"><?= $condition_info ?></div>
        </td>
<!--        <td>--><?//=$payment_systems[$row['PaymentSystem_ID']] ?><!--</td>-->
        <td><?= $ui->helper->hash_link($edit_hash, '<i class="nc-icon nc--settings"></i>') ?></td>
        <td><a onclick="return confirm('<?=NETCAT_MODULE_NETSHOP_SOURCES_DELETE_CONFIRM ?>')"
               href="<?=$remove_action ?>"><i class="nc-icon nc--remove"></i></a></td>
    </tr>
<? endforeach ?>
</table>