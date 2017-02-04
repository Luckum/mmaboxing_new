<? $error && nc_print_status($error, 'error') ?>

<form class="nc-form nc--vertical" action="<?=$action_url . $action . ($action == 'edit' && $partial_name ? "&partial={$partial_name}" : '')?>" method="post">

    <? if ($action == 'add'): ?>
        <div class="nc-form-row">
            <label><?= CONTROL_TEMPLATE_PARTIALS_NAME_FIELD ?>*</label>
            <input type="text" name="partial_name" value="<?= htmlspecialchars($partial_name) ?>" class='nc--large'>
        </div>
    <? endif ?>

    <div class="nc-form-row">
        <label><?= CONTROL_TEMPLATE_PARTIALS_SOURCE_FIELD ?></label>
        <textarea name="partial_source" cols="30" rows="15"><?= htmlspecialchars($partial_source) ?></textarea>
    </div>
</form>