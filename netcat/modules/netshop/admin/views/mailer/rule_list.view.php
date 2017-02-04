<?php if (!class_exists('nc_core')) { die; } ?>

<?= $ui->controls->site_select($catalogue_id) ?>

<table class="nc-table nc--bordered nc--wide">
    <tr>
        <th class='nc--compact'></th>
        <th><?=NETCAT_MODULE_NETSHOP_NAME_AND_CONDITIONS_HEADER ?></th>
        <th><?=NETCAT_MODULE_NETSHOP_MAILER_RULE_ADDRESS ?></th>
        <th class='nc--compact'></th>
        <th class='nc--compact'></th>
    </tr>
    <? foreach ($rules as $row): ?>
        <?php

        $id = $row['Rule_ID'];
        $edit_hash = "#module.netshop.mailer.rule.edit($id)";
        $remove_action = $current_url . 'remove&id=' . $id;
        $toggle_button = $ui->controls->toggle_button(
            $row['Checked'],
            array('controller' => $controller_name, 'id' => $id)
        );

        $rule = new nc_netshop_delivery_method();
        $rule->set_values_from_database_result($row);
        $condition_info = $rule->get_condition_description(false);
        if ($condition_info) { $condition_info = NETCAT_MODULE_NETSHOP_COND . $condition_info; }

        ?>
        <tr>
            <td><?=$toggle_button ?></td>
            <td>
                <?= $ui->helper->hash_link($edit_hash, $row['Name'], 'nc-netshop-list-item-title') ?>
                <div class="nc-netshop-list-condition-info"><?= $condition_info ?></div>
            </td>
            <td><?=$row['Email'] ?></td>
            <td><?= $ui->helper->hash_link($edit_hash, '<i class="nc-icon nc--settings"></i>') ?></td>
            <td><?= $ui->controls->delete_button(
                    sprintf(NETCAT_MODULE_NETSHOP_MAILER_RULES_CONFIRM_DELETE, $row['Name']),
                    array('controller' => $controller_name, 'id' => $id)
                ) ?></td>
        </tr>
    <? endforeach ?>
</table>
<br>