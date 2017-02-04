<?php
if (!class_exists('nc_core')) {
    die;
}
/**
 * @var $company nc_bills_company
 */
?>

<?php if ($status && $status == 'ok') { ?>
    <?php nc_print_status(NETCAT_MODULE_BILLS_SETTINGS_SAVED, 'ok'); ?>
<?php } ?>

<form action="<?= nc_core()->HTTP_ROOT_PATH; ?>modules/bills/admin/?controller=settings&action=save" method="POST" class="nc-form nc--vertical" enctype="multipart/form-data">
    <h2><?= NETCAT_MODULE_BILLS_SETTINGS?></h2>

    <div class="nc-form-row">
        <label><?= NETCAT_MODULE_BILLS_SETTINGS_VAT?>, %<br><input type="text" name="settings[vat]" class="nc--xlarge" value="<?= $settings['vat']; ?>" data-cip-id="cIPJQ342845675"></label>
    </div>
    <div class="nc-form-row">
        <label><?= NETCAT_MODULE_BILLS_SETTINGS_SECRET_KEY; ?><br><input type="text" name="settings[key]" class="nc--xlarge" value="<?= $settings['key']; ?>" data-cip-id="cIPJQ342845675"></label>
    </div>
    <h2><?= NETCAT_MODULE_BILLS_SETTINGS_COMPANY_DETAILS?></h2>

    <div class="nc-form-row"><label><?= NETCAT_MODULE_BILLS_SETTINGS_OPF?><br>

            <div class="nc-select">
                <select style="width:150px" name="company[opf]" data-cip-id="cIPJQ342845707">
                    <?
                    $opf = array(
                        'ИП',
                        'ООО',
                        'ОАО',
                    );
                    ?>
                    <option value=""><?= NETCAT_MODULE_BILLS_SELECT?></option>
                    <?php foreach ($opf as $value) { ?>
                        <option value="<?= $value; ?>" <?= ($company->get('opf') == $value) ? 'selected="selected"' : ''; ?>><?= $value; ?></option>
                    <?php } ?>
                </select>
                <i class="nc-caret"></i>
            </div>
        </label>
    </div>
    <div class="nc-form-row">
        <label><?= NETCAT_MODULE_BILLS_SETTINGS_NAME?><br><input type="text" name="company[name]" class="nc--xlarge" value="<?= $company->get('name'); ?>" data-cip-id="cIPJQ342845675"></label>
    </div>
    <div class="nc-form-row">
        <label><?= NETCAT_MODULE_BILLS_SETTINGS_LEGAL_ADDRESS?><br><input type="text" name="company[address]" class="nc--xlarge" value="<?= $company->get('address'); ?>" data-cip-id="cIPJQ342845676"></label>
    </div>
    <div class="nc-form-row">
        <label><?= NETCAT_MODULE_BILLS_SETTINGS_PHONE?><br><input type="text" name="company[phone]" class="nc--large" value="<?= $company->get('phone'); ?>" data-cip-id="cIPJQ342845677"></label>
    </div>
    <div class="nc-form-row">
        <label><?= NETCAT_MODULE_BILLS_SETTINGS_INN?><br><input type="text" name="company[inn]" class="nc--large" value="<?= $company->get('inn'); ?>" data-cip-id="cIPJQ342845678"></label>
    </div>
    <div class="nc-form-row">
        <label><?= NETCAT_MODULE_BILLS_SETTINGS_KPP?><br><input type="text" name="company[kpp]" class="nc--large" value="<?= $company->get('kpp'); ?>" data-cip-id="cIPJQ342845679"></label>
    </div>
    <h2><?= NETCAT_MODULE_BILLS_SETTINGS_PAYMENT_DETAILS?></h2>

    <div class="nc-form-row">
        <label><?= NETCAT_MODULE_BILLS_SETTINGS_BANK_NAME?><br><input type="text" name="company[bank_name]" class="nc--large" value="<?= $company->get('bank_name'); ?>" data-cip-id="cIPJQ342845680"></label>
    </div>
    <div class="nc-form-row">
        <label><?= NETCAT_MODULE_BILLS_SETTINGS_BANK_CURRENT_ACCOUNT?><br><input type="text" name="company[bank_account]" class="nc--large" value="<?= $company->get('bank_account'); ?>" data-cip-id="cIPJQ342845681"></label>
    </div>
    <div class="nc-form-row">
        <label><?= NETCAT_MODULE_BILLS_SETTINGS_BANK_CORRESPONDENT_ACCOUNT?><br><input type="text" name="company[bank_corr_account]" class="nc--large" value="<?= $company->get('bank_corr_account'); ?>" data-cip-id="cIPJQ342845682"></label>
    </div>
    <div class="nc-form-row">
        <label><?= NETCAT_MODULE_BILLS_SETTINGS_BANK_INN?><br><input type="text" name="company[bank_inn]" class="nc--large" value="<?= $company->get('bank_inn'); ?>" data-cip-id="cIPJQ342845683"></label>
    </div>
    <div class="nc-form-row">
        <label><?= NETCAT_MODULE_BILLS_SETTINGS_BANK_BIK?><br><input type="text" name="company[bank_bik]" class="nc--large" value="<?= $company->get('bank_bik'); ?>" data-cip-id="cIPJQ342845685"></label>
    </div>

    <h2><?= NETCAT_MODULE_BILLS_SETTINGS_GRAPHIC_ELEMENTS?></h2>

    <div class="nc-form-row">
        <label><?= NETCAT_MODULE_BILLS_SETTINGS_LOGO?>:</label>
        <?php if ($settings['logo']) { ?>
            <a href="<?= $settings['logo']; ?>" target="_blank"><?= NETCAT_MODULE_BILLS_SHOW?></a>
            <label style="display: inline-block;"><input type="checkbox" name="settings[delete][logo]"/> <?= NETCAT_MODULE_BILLS_DELETE?></label><br>
        <?php } ?>
        <input type="file" name="settings[logo]">
    </div>

    <div class="nc-form-row">
        <label><?= NETCAT_MODULE_BILLS_SETTINGS_DIRECTOR_SIGN?>:</label>
        <?php if ($settings['director_sign']) { ?>
            <a href="<?= $settings['director_sign']; ?>" target="_blank"><?= NETCAT_MODULE_BILLS_SHOW?></a>
            <label style="display: inline-block;"><input type="checkbox" name="settings[delete][director_sign]"/> <?= NETCAT_MODULE_BILLS_DELETE?></label><br>
        <?php } ?>
        <input type="file" name="settings[director_sign]">
    </div>

    <div class="nc-form-row">
        <label><?= NETCAT_MODULE_BILLS_SETTINGS_BUCH_SIGN?>:</label>
        <?php if ($settings['accountant_sign']) { ?>
            <a href="<?= $settings['accountant_sign']; ?>" target="_blank"><?= NETCAT_MODULE_BILLS_SHOW?></a>
            <label style="display: inline-block;"><input type="checkbox" name="settings[delete][accountant_sign]"/> <?= NETCAT_MODULE_BILLS_DELETE?></label><br>
        <?php } ?>
        <input type="file" name="settings[accountant_sign]">
    </div>

    <div class="nc-form-row">
        <label><?= NETCAT_MODULE_BILLS_SETTINGS_PRINT?>:</label>
        <?php if ($settings['stamp']) { ?>
            <a href="<?= $settings['stamp']; ?>" target="_blank"><?= NETCAT_MODULE_BILLS_SHOW?></a>
            <label style="display: inline-block;"><input type="checkbox" name="settings[delete][stamp]"/> <?= NETCAT_MODULE_BILLS_DELETE?></label><br>
        <?php } ?>
        <input type="file" name="settings[stamp]">
    </div>
</form>