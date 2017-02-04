<?

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)) . (strstr(__FILE__, "/") ? "/" : "\\");
require_once($NETCAT_FOLDER . "vars.inc.php");
require($ADMIN_FOLDER . "function.inc.php");

if (is_file($MODULE_FOLDER . "payment/" . MAIN_LANG . ".lang.php")) {
    require_once($MODULE_FOLDER . "payment/" . MAIN_LANG . ".lang.php");
} else {
    require_once($MODULE_FOLDER . "payment/en.lang.php");
}

// load modules env
if (!isset($MODULE_VARS)) $MODULE_VARS = $nc_core->modules->get_module_vars();

// UI config
require_once($ADMIN_FOLDER . "modules/ui.php");
// default
if (!$page) $page = "settings";
require_once($MODULE_FOLDER . "payment/ui_config.php");

$UI_CONFIG = new ui_config_module_payment('admin', $page);

$perm->ExitIfNotAccess(NC_PERM_MODULE, 0, 0, 0, 1);

global $UI_CONFIG;

$Title1 = NETCAT_MODULES;
$Title2 = NETCAT_MODULE_PAYMENT_NAME;


BeginHtml($Title2, $Title1, "http://" . $DOC_DOMAIN . "/settings/modules/payment/");

// show settings form

$catalogue = (int)$nc_core->input->fetch_get('catalogue');
$system = (int)$nc_core->input->fetch_get('system');

if (!$catalogue) {
    $catalogue = $nc_core->catalogue->id();
}

// Обработка POST запроса - установка параметров
if ($nc_core->input->fetch_post('row')) {
    foreach ($nc_core->input->fetch_post('row') as $id => $row) {
        if ($row['Delete']) {
            $sql = "
				DELETE FROM " . NC_PAYMENT_SYSTEM_PARAM_TABLE . "
				WHERE Param_ID = " . $db->escape($id) . "
			";
        } else {
            $sql = "
				UPDATE " . NC_PAYMENT_SYSTEM_PARAM_TABLE . " SET
					System_ID = '" . $db->escape($system) . "',
					Catalogue_ID = '" . $db->escape($catalogue) . "',
					Param_Name = '" . $db->escape($row['Param_Name']) . "',
					Param_Value = '" . $db->escape($row['Param_Value']) . "'
				WHERE Param_ID = " . $db->escape($id) . "
			";
        }
        $db->query($sql);
    }
}

$postAdd = $nc_core->input->fetch_post('add');
if ($postAdd) {
    foreach ($postAdd['Param_Name'] as $i => $value) {
        if (!$value) continue;
        $sql = "
			INSERT " . NC_PAYMENT_SYSTEM_PARAM_TABLE . " SET
				System_ID = '" . $db->escape($system) . "',
				Catalogue_ID = '" . $db->escape($catalogue) . "',
				Param_Name = '" . $db->escape($value) . "',
				Param_Value = '" . $db->escape($postAdd['Param_Value'][$i]) . "'
		";
        $db->query($sql);
    }
}


// список сайтов
//"<option value='0'>".NETCAT_MODULE_PAYMENT_CHOICE_SITE."</option>".
$selectCatalogue = listQuery("SELECT Catalogue_ID, Catalogue_Name, IF(Catalogue_ID=" . $db->escape($catalogue) . " , 'selected' , '') AS selected FROM Catalogue",
    "<option value='\$data[Catalogue_ID]' \$data[selected]>\$data[Catalogue_ID]. \$data[Catalogue_Name]</option>");
?>
    <fieldset>
        <legend>
            <?= NETCAT_MODULE_PAYMENT_SITES ?>
        </legend>
        <div class='nc-select'>
            <select name='catalogue' id='catalogue'><?= $selectCatalogue ?></select>
            <i class="nc-caret"></i>
        </div>
    </fieldset>
<?

// Обработка POST запроса - включение платежной системы на сайте
if ($nc_core->input->fetch_post('setSettings')) {
    $db->query("UPDATE " . NC_PAYMENT_SYSTEM_CATALOGUE_TABLE . " SET Checked=0 WHERE Catalogue_ID = " . $db->escape($catalogue));
    ?>
    <div class="nc_print_status status_ok" id="statusMessage">
        <div class="nc_print_status_icon nc_print_status_icon_ok"></div>
        <div class="nc_print_status_text"><?= NETCAT_MODULE_PAYMENT_ADMIN_SETTINGS_SAVED; ?></div>
        <div class="nc_clear"></div>
    </div>
<?
}
if ($nc_core->input->fetch_post('systemCatalogue')) {
    foreach ($nc_core->input->fetch_post('systemCatalogue') as $systemID => $value) {
        $systemOn[] = "( " . $db->escape($systemID) . " , $catalogue , 1 )";
    }
    $systemOn = implode(",", $systemOn);
    $db->query("REPLACE INTO " . NC_PAYMENT_SYSTEM_CATALOGUE_TABLE . " ( PaymentSystem_ID , Catalogue_ID , Checked ) VALUES $systemOn");
    $system = array_key_exists($system, $nc_core->input->fetch_post('systemCatalogue')) ? $system : NULL;
}

echo "<form method='post' id='setSettings' action=''>";
echo "<input type='hidden' name='setSettings' value='1' />";

if ($catalogue) {
    echo "<fieldset>
			<legend>" . NETCAT_MODULE_PAYMENT_LIST_PAYMENT_SYSTEMS . "</legend>
			<table width='100%' class='admin_table'>
                <tbody>
                	<tr>
                  		<td width='90%'>" . NETCAT_MODULE_PAYMENT_PAYMENT_SYSTEM . "</td>
                  		<td align='center'>" . NETCAT_MODULE_PAYMENT_SETTINGS_PAYMENT_SYSTEM . "</td>
                  		<td align='center'>" . NETCAT_MODULE_PAYMENT_ONOFF_PAYMENT_SYSTEM . "</td>
                	</tr>";

    $sql = "
		SELECT 	a.PaymentSystem_ID, b.Checked,
				PaymentSystem_Name
		FROM 	" . NC_PAYMENT_SYSTEM_CLASSIFIER_TABLE . " a
		LEFT JOIN " . NC_PAYMENT_SYSTEM_CATALOGUE_TABLE . " b ON a.PaymentSystem_ID = b.PaymentSystem_ID AND b.Catalogue_ID = " . $db->escape($catalogue) . "
	";
    $result = (array)nc_core('db')->get_results($sql, ARRAY_A);
    foreach ($result as $row) {
        if (!$nc_core->NC_UNICODE) {
            $row['PaymentSystem_Name'] = $nc_core->utf8->utf2win($row['PaymentSystem_Name']);
        }

        $checked = $row['Checked'] == 1;
        $payment_settings = $checked ? "<a href='?catalogue={$catalogue}&system={$row['PaymentSystem_ID']}#sett'><div title='" . NETCAT_MODULE_PAYMENT_ADMIN_BUTTON_CHANGE_SETTINGS . "' class='icons icon_settings'></div></a>" :
            "<div title='" . NETCAT_MODULE_PAYMENT_ADMIN_CHANGE_PARAMETERS . "' class='icons icon_settings icon_disabled'></div>";

        echo "<tr>
							<td>{$row['PaymentSystem_Name']}</td>
							<td>{$payment_settings}</td>
							<td><input type='checkbox' " . ($checked ? "checked='checked'" : "") . " class='systemCatalogue' name='systemCatalogue[{$row['PaymentSystem_ID']}]' /> </td>
						</tr>";
    }

    echo "
                </tbody>
            </table>
		</fieldset>
	";
    $UI_CONFIG->actionButtons[] = array(
        "id" => "submit",
        "caption" => NETCAT_MODULE_PAYMENT_ADMIN_BUTTON_SAVE,
        "action" => "mainView.submitIframeForm('setSettings')"
    );

}

if ($catalogue && $system) {

    $UI_CONFIG->actionButtons[] = array(
        "id" => "submit",
        "caption" => NETCAT_MODULE_PAYMENT_ADMIN_BUTTON_ADD_PARAMETER,
        "action" => "document.getElementById('mainViewIframe').contentWindow.addParam()",
        "align" => "left"
    );
    // получаем класс системы по её ID
    $row = $db->get_row("SELECT Value, PaymentSystem_Name FROM " . NC_PAYMENT_SYSTEM_CLASSIFIER_TABLE . " WHERE PaymentSystem_ID = " . $db->escape($system), ARRAY_A);
//	list($payClass, $payName) = $db->get_row("SELECT value, PaymentSystem_Name FROM ".TABLE_PAY_SYSTEMS." WHERE PaymentSystem_ID = $system", ARRAY_A);
    $payClass = $row['Value'];
    $payName = $row['PaymentSystem_Name'];
    $ps = new $payClass();
    $requireParams = $ps->get_settings_list();
    $payParams = $db->get_results("SELECT * FROM " . NC_PAYMENT_SYSTEM_PARAM_TABLE . " WHERE Catalogue_ID = " . $db->escape($catalogue) . " AND System_ID = " . $db->escape($system), ARRAY_A);
    ?>  <a name="sett"></a>
    <fieldset>
        <legend><?= NETCAT_MODULE_PAYMENT_PAYMENT_SYSTEM_PARAMETERS ?> "<?= $payName ?>"</legend>
        <div style='margin:10px 0; _padding:0;'>
            <table id="tableParam" style="width:100%;" class="admin_table">
                <colgroup>
                    <col style="width:35%">
                    <col style="width:60%">
                    <col style="width:5%">
                </colgroup>
                <tbody>
                <tr>
                    <td class="align-center first_col"><?= NETCAT_MODULE_PAYMENT_PARAMETER ?></td>
                    <td class="align-center"><?= NETCAT_MODULE_PAYMENT_PARAMETER_VALUE ?></td>
                    <td class="align-center last_col">
                        <div title="<?= NETCAT_MODULE_PAYMENT_ADMIN_BUTTON_DELETE ?>" class="icons icon_delete"></div>
                    </td>
                </tr>

                <? if ($payParams) foreach ($payParams as $row) {
                    $index = FALSE;
                    if (($index = array_search($row['Param_Name'], $requireParams)) !== FALSE) {
                        unset($requireParams[$index]);
                    }
                    ?>
                    <tr>
                        <td class="first_col">
                            <? if ($index !== FALSE) : ?>
                                <input name="row[<?= $row['Param_ID'] ?>][Param_Name]" value="<?= $row['Param_Name'] ?>" type="hidden"/> <?= $row['Param_Name'] ?>
                            <? else : ?>
                                <input name="row[<?= $row['Param_ID'] ?>][Param_Name]" value="<?= $row['Param_Name'] ?>" style="width:100%; font-family: &quot;Courier New&quot;, Courier, monospace"/>
                            <? endif ?>
                        </td>
                        <td>
                            <input name="row[<?= $row['Param_ID'] ?>][Param_Value]" value="<?= $row['Param_Value'] ?>" style="width:100%; font-family: &quot;Courier New&quot;, Courier, monospace"/>
                        </td>
                        <td class="last_col">
                            <? if ($index === FALSE) : ?>
                                <input type="checkbox" name="row[<?= $row[Param_ID] ?>][Delete]">
                            <? endif ?>
                        </td>
                    </tr>
                <?
                }
                foreach ($requireParams as $value) {
                    ?>
                    <tr>
                        <td class="first_col">
                            <input name="add[Param_Name][]" value="<?= $value ?>" type="hidden"/> <?= $value ?>
                        </td>
                        <td>
                            <input name="add[Param_Value][]" style="width:100%; font-family: &quot;Courier New&quot;, Courier, monospace"/>
                        </td>
                    </tr>
                <?
                }
                ?>
                </tbody>
            </table>
        </div>
    </fieldset>

<?
}
echo "</form>";
?>


    <script>
        function addParam() {
            $('#tableParam tbody').append(
                '<tr>' +
                    '<td class="first_col">' +
                    '<input name="add[Param_Name][]" style="width:100%; font-family: &quot;Courier New&quot;, Courier, monospace"/>' +
                    '</td><td>' +
                    '<input name="add[Param_Value][]" style="width:100%; font-family: &quot;Courier New&quot;, Courier, monospace"/>' +
                    '</td><td>' +
                    '<div class="icons icon_delete" onclick="newParamDelete(this); return false;" title="Удалить" style="cursor:pointer;"></div>' +
                    '</td>' +
                    '</tr>'
            );
        }

        $('#catalogue').on('change', function () {
            document.location.href = document.location.pathname + '?catalogue=' + $(this).val();
        });

        function newParamDelete(t) {
            $(t).parent().parent().remove();
        }

    </script>


<?


EndHtml();


?>