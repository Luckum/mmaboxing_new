<?php if (!class_exists('nc_core')) { die; } ?>

<?= $ui->controls->site_select($catalogue_id) ?>

<table class="nc-table nc--bordered nc--wide">
<tr>
    <th class='nc--compact'></th>
    <th><?=NETCAT_MODULE_NETSHOP_RULE ?></th>
    <th><?=NETCAT_MODULE_NETSHOP_PRICE_RULE_PRICE_COLUMN ?></th>
    <th class='nc--compact'></th>
    <th class='nc--compact'></th>
</tr>
<?php

foreach ($rules as $row) {
    $rule = new nc_netshop_pricerule();
    $rule->set_values_from_database_result($row);

    $post_actions_params = array(
        'controller' => $controller_name,
        'id' => $rule->get_id(),
    );

    $edit_link_hash = "#module.netshop.pricerule.edit({$rule->get_id()})";

    ?>
    <tr>
        <td>
            <?= $ui->controls->toggle_button($rule->get('enabled'), $post_actions_params); ?>
        </td>
        <td>
            <?= $ui->helper->hash_link($edit_link_hash, $rule->get('name')) ?>
            <br>
            <?= $rule->get_condition_description() ?>
        </td>
        <td><?=$rule->get('price_column') ?></td>
        <td>
            <?= $ui->helper->hash_link($edit_link_hash, '<i class="nc-icon nc--settings"></i>') ?>
        </td>
        <td>
            <?= $ui->controls->delete_button(
                        sprintf(NETCAT_MODULE_NETSHOP_PRICE_RULE_CONFIRM_DELETE, $rule->get('name')),
                        $post_actions_params)
            ?>
        </td>
    </tr>
    <?php
}
?>
</table>
<br>