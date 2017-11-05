<?php
/* $Id: settings.inc.php 8534 2012-12-14 09:59:06Z ewind $ */

function ShowSettings() {
    global $nc_core, $db, $SYSTEM_NAME, $UI_CONFIG;

    $Array = $nc_core->get_settings();
    ?>
    <table border=0 cellpadding=0 cellspacing=0 width=100%><tr><td>
                <table border=0 cellpadding=6 cellspacing=1 width=100%><tr><td>
                            <table border=0 cellpadding=0 cellspacing=0 width=100%>
                                <tr><td width=70%><?= CONTROL_SETTINGSFILE_BASIC_VERSION
    ?>:</td><td><?= $Array["VersionNumber"]
    ?> <?= $SYSTEM_NAME
    ?></td></tr>
                                <tr><td width=70%><?= CONTROL_SETTINGSFILE_BASIC_REGCODE
    ?>:</td><td><?= $Array["ProductNumber"]
    ?> <?= $PRODUCT_NUMBER
    ?></td></tr>
                            </table>
                        </td></tr><tr><td>
                            <table border=0 cellpadding=0 cellspacing=0 width=100%>
                                <tr><td colspan=2><legend><?= CONTROL_SETTINGSFILE_BASIC_MAIN ?></legend></td></tr>
                    <tr><td width=70%><?= CONTROL_SETTINGSFILE_BASIC_MAIN_NAME ?>:</td><td><?= htmlspecialchars($Array["ProjectName"]) ?></td></tr>
                    <tr>
                        <td width=70%><?= CONTROL_SETTINGSFILE_BASIC_EDIT_TEMPLATE ?>:</td>
                        <td><?
                                        if ($Array["EditDesignTemplateID"]) {
                                            $_editTemplate = $db->get_var("SELECT CONCAT(`Template_ID`, ': ', `Description`)
        FROM `Template`
        WHERE `Template_ID` = '" . intval($Array['EditDesignTemplateID']) . "'");
                                            echo $_editTemplate ? $_editTemplate : CONTROL_TEMPLATE_NONE;
                                        } else {
                                            print CONTROL_SETTINGSFILE_BASIC_EDIT_TEMPLATE_DEFAULT;
                                        }
    ?></td></tr>

                </table>


        <tr><td>

                <table border=0 cellpadding=0 cellspacing=0 width=100%>
                    <tr><td colspan=2><legend><?= CONTROL_SETTINGSFILE_BASIC_EMAILS ?></legend></td></tr>
        <tr><td width=70%><?= CONTROL_SETTINGSFILE_BASIC_EMAILS_FILELD ?>:</td><td>
                <?php
                echo ($Array['UserEmailField'] ? $Array['UserEmailField'] : CONTROL_SETTINGSFILE_BASIC_MAIN_FILEMANAGER_NONE);
                ?></td></tr>
        <tr><td><?= CONTROL_SETTINGSFILE_BASIC_EMAILS_FROMNAME
                ?>:</td><td><?= $Array["SpamFromName"]
                ?></td></tr>
        <tr><td><?= CONTROL_SETTINGSFILE_BASIC_EMAILS_FROMEMAIL
                ?>:</td><td><?= $Array["SpamFromEmail"]
                ?></td></tr>
    </table>

    </td></tr>


    <tr><td>
            <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                <tr>
                    <td colspan='2'><legend><?= NETCAT_SETTINGS_CODEMIRROR ?></legend></td>
    </tr>
    <tr>
        <td width='70%'><?= NETCAT_SETTINGS_CODEMIRROR_EMBEDED
                ?>:</td>
        <td><?= $Array['CMEmbeded'] ? NETCAT_SETTINGS_CODEMIRROR_EMBEDED_ON : NETCAT_SETTINGS_CODEMIRROR_EMBEDED_OFF
                ?></td>
    </tr>
    <tr>
        <td width='70%'><?= NETCAT_SETTINGS_CODEMIRROR_DEFAULT
                ?>:</td>
        <td><?= $Array['CMDefault'] ? NETCAT_SETTINGS_CODEMIRROR_DEFAULT_ON : NETCAT_SETTINGS_CODEMIRROR_DEFAULT_OFF
                ?></td>
    </tr>
    <tr>
        <td width='70%'><?= NETCAT_SETTINGS_CODEMIRROR_AUTOCOMPLETE
                ?>:</td>
        <td><?= $Array['CMAutocomplete'] ? NETCAT_SETTINGS_CODEMIRROR_AUTOCOMPLETE_ON : NETCAT_SETTINGS_CODEMIRROR_AUTOCOMPLETE_OFF
                ?></td>
    </tr>
    <tr>
        <td width='70%'><?= NETCAT_SETTINGS_CODEMIRROR_HELP
                ?>:</td>
        <td><?= $Array['CMHelp'] ? NETCAT_SETTINGS_CODEMIRROR_HELP_ON : NETCAT_SETTINGS_CODEMIRROR_HELP_OFF
                ?></td>
    </tr>
    </table>
    </td></tr>

    <tr><td>
            <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                <tr>
                    <td colspan='2'><legend><?= NETCAT_SETTINGS_TRASHBIN ?></legend></td>
    </tr>
    <tr>
        <td width='70%'><?= NETCAT_SETTINGS_TRASHBIN_USE
                ?>:</td>
        <td><?= $Array['TrashUse'] ? NETCAT_SETTINGS_EDITOR_EMBED_ON : NETCAT_SETTINGS_EDITOR_EMBED_OFF
                ?></td>
    </tr>
    <tr>
        <td width='70%'><?= NETCAT_SETTINGS_TRASHBIN_MAXSIZE
                ?>:</td>
        <td><?= $Array['TrashLimit']
                ?> <?= NETCAT_SIZE_MBYTES
                ?></td>
    </tr>
    </table>
    </td></tr>

    <tr><td>
            <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                <tr>
                    <td colspan='2'><legend><?= NETCAT_SETTINGS_COMPONENTS ?></legend></td>
    </tr>
    <tr>
        <td width='70%'><?= NETCAT_SETTINGS_REMIND_SAVE_INFO ?>:</td>
        <td><?= $Array['RemindSave'] == 1 ? NETCAT_SETTINGS_EDITOR_EMBED_ON : NETCAT_SETTINGS_EDITOR_EMBED_OFF ?></td>
    </tr>
    <tr>
        <td width='70%'><?= NETCAT_SETTINGS_PACKET_OPERATIONS_INFO ?>:</td>
        <td><?= $Array['PacketOperations'] == 1 ? NETCAT_SETTINGS_EDITOR_EMBED_ON : NETCAT_SETTINGS_EDITOR_EMBED_OFF ?></td>
    </tr>
    <tr>
        <td width='70%'><?= NETCAT_SETTINGS_TEXTAREA_RESIZE_INFO ?>:</td>
        <td><?= $Array['TextareaResize'] == 1 ? NETCAT_SETTINGS_EDITOR_EMBED_ON : NETCAT_SETTINGS_EDITOR_EMBED_OFF ?></td>
    </tr>
    </table>
    </td></tr>

    <tr><td>
            <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                <tr>
                    <td colspan='2'><legend><?= NETCAT_SETTINGS_QUICKBAR ?></legend></td>
    </tr>
    <tr>
        <td width='70%'><?= NETCAT_SETTINGS_QUICKBAR_ENABLE
                ?>:</td>
        <td><?= $Array['QuickBar'] == 1 ? NETCAT_SETTINGS_QUICKBAR_ON : NETCAT_SETTINGS_QUICKBAR_OFF
                ?></td>
    </tr>
    </table>
    </td></tr>

    <tr><td>
            <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                <tr>
                    <td colspan='2'><legend><?= NETCAT_SETTINGS_SYNTAXEDITOR ?></legend></td>
    </tr>
    <tr>
        <td width='70%'><?= NETCAT_SETTINGS_SYNTAXEDITOR_ENABLE
                ?>:</td>
        <td><?= $Array['SyntaxEditor'] == 1 ? NETCAT_SETTINGS_EDITOR_EMBED_ON : NETCAT_SETTINGS_EDITOR_EMBED_OFF
                ?></td>
    </tr>
    </table>
    </td></tr>



    <tr><td>
            <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                <tr>
                    <td colspan='2'><legend><?= NETCAT_SETTINGS_ALTBLOCKS ?></legend></td>
    </tr>

    <tr>
        <td width='70%'><?= NETCAT_SETTINGS_ALTBLOCKS
                ?>:</td>
        <td><?= $Array['AdminButtonsType'] == 1 ? NETCAT_SETTINGS_ALTBLOCKS_ON : NETCAT_SETTINGS_ALTBLOCKS_OFF
                ?></td>
    </tr>
    </table>
    </td></tr>

    </table></td></tr></table>
    <?php
    $UI_CONFIG->actionButtons[] = array("id" => "submit",
            "caption" => CONTROL_SETTINGSFILE_BASIC_CHANGEDATA,
            "action" => "urlDispatcher.load('system.edit')"
    );
}

function SettingsForm() {
    global $nc_core;
    global $db, $ADMIN_PATH;

    $Array = $nc_core->get_settings(null, null, true, 0);
    ?>

    <form method='post' action='settings.php' style='overflow:hidden' class="nc-form">
        <fieldset>
            <legend><?= CONTROL_SETTINGSFILE_BASIC_MAIN ?></legend>
            <table border='0' cellpadding='6' cellspacing='0' width='100%'>
                <tr>
                    <td>
                        <?= CONTROL_SETTINGSFILE_BASIC_MAIN_NAME
                        ?>:<br>
                        <?= nc_admin_input_simple('ProjectName', $Array["ProjectName"], 70, '', "maxlength='255'")
                        ?><br>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?php
                        $tpl = $db->get_results("SELECT `Template_ID` as value,
      CONCAT(`Template_ID`, ': ', `Description`) as description,
      `Parent_Template_ID` as parent
      FROM `Template`
      ORDER BY `Priority`, `Template_ID`", ARRAY_A);
                        if (!empty($tpl)) {
                            ?>
                            <?= CONTROL_SETTINGSFILE_BASIC_EDIT_TEMPLATE ?>:<br>
                            <select name="EditDesignTemplateID">
                                <option value="0"><?= CONTROL_SETTINGSFILE_BASIC_EDIT_TEMPLATE_DEFAULT ?></option>
                                <option></option>
                                <?= nc_select_options($tpl, $Array["EditDesignTemplateID"]); ?>
                            </select>
                            <?php
                        } else {
                            echo CONTROL_TEMPLATE_NONE;
                        }
                        ?>
                    </td>
                </tr>
            </table>
        </fieldset>
        <br>
        <fieldset>
            <legend><?= CONTROL_SETTINGSFILE_BASIC_EMAILS ?></legend>
            <table border=0 cellpadding=6 cellspacing=0 width=100%><tr><td>
                        <?= CONTROL_SETTINGSFILE_CHANGE_EMAILS_FIELD
                        ?>:<br>
                        <?php
                        $systable = $db->get_var("SELECT System_Table_ID FROM System_Table WHERE System_Table_Name='User'");

                        $res = $db->get_results("SELECT Field_Name,Description FROM Field WHERE System_Table_ID='" . $systable . "' AND Format LIKE 'email%' ORDER BY Priority", ARRAY_N);

                        if ($count = $db->num_rows) {
                            if ($count == 1) {
                                list($field_id, $field_name) = $res[0];
                                echo "" . $field_name . "<input type=hidden name=UserEmailField value=" . $field_id . ">";
                            } else {
                                echo "<select name=UserEmailField>";
                                foreach ($res as $field) {
                                    list($field_id, $field_name) = $field;
                                    echo "<option " . ($field_id == $Array["UserEmailField"] ? "selected" : "") . " value=" . $field_id . ">" . $field_id . ": " . $field_name;
                                }
                                echo "</select>";
                            }
                        } else {
                            ?>
                            <b><?= CONTROL_SETTINGSFILE_CHANGE_EMAILS_NONE
                            ?></b> (<a href=<?= "" . $ADMIN_PATH . "field/index.php?fs=1&isSys=1&SystemTableID=" . $systable
                            ?>><?= CONTROL_CONTENT_SUBDIVISION_FUNCS_ADD
                            ?></a>)
                        <? } ?></td></tr><tr><td>
                        <?= CONTROL_SETTINGSFILE_BASIC_EMAILS_FROMNAME ?>:<br>
                        <?= nc_admin_input_simple('SpamFromName', $Array["SpamFromName"], 70, '', "maxlength='255'") ?>
                    </td></tr><tr><td>
                        <?= CONTROL_SETTINGSFILE_BASIC_EMAILS_FROMEMAIL ?>:<br>
    <?= nc_admin_input_simple('SpamFromEmail', $Array["SpamFromEmail"], 70, '', "maxlength='255'") ?>
                    </td></tr></table>
        </fieldset>

        <fieldset>
            <legend><?= NETCAT_SETTINGS_EDITOR ?></legend>
            <table border='0' cellpadding='6' cellspacing='0' width='100%'>
                <tr>
                    <td colspan='2'>
						<?
						$kc_block = "<select name='SaveKeycode'>";
						$kc = ($Array['SaveKeycode'] ? $Array['SaveKeycode'] : 83);
						for ($i = 65; $i <= 90; $i++):
							$kc_block .= "<option value='" . $i . "'" . ($i == $kc ? ' selected' : '') . ">" . chr($i) . "</option>";
						endfor;
						$kc_block .= "</select>";
						?>
						<?= sprintf(NETCAT_SETTINGS_EDITOR_KEYCODE, $kc_block) ?>
                    </td>
                </tr>
            </table>
        </fieldset>
        <br>

        <fieldset>
            <legend><?= NETCAT_SETTINGS_CODEMIRROR ?></legend>
            <table border='0' cellpadding='6' cellspacing='0' width='100%'>
                <tr>
                    <td colspan="2">
                        <?= nc_admin_checkbox_simple('CMEmbeded', 1, "" . NETCAT_SETTINGS_CODEMIRROR_EMBEDED . "", $Array['CMEmbeded'])
                        ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <?= nc_admin_checkbox_simple('CMDefault', 1, "" . NETCAT_SETTINGS_CODEMIRROR_DEFAULT . "", $Array['CMDefault'], '', $Array['CMEmbeded'] != 1 ? ' disabled' : '')
                        ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <?= nc_admin_checkbox_simple('CMAutocomplete', 1, "" . NETCAT_SETTINGS_CODEMIRROR_AUTOCOMPLETE . "", $Array['CMAutocomplete'], '', $Array['CMEmbeded'] != 1 ? ' disabled' : '')
                        ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <?= nc_admin_checkbox_simple('CMHelp', 1, "" . NETCAT_SETTINGS_CODEMIRROR_HELP . "", $Array['CMHelp'], '', $Array['CMEmbeded'] != 1 ? ' disabled' : '')
                        ?>
                    </td>
                </tr>
            </table>
        </fieldset>
        <br>
        <script type='text/javascript'>$nc('#CMEmbeded').change(function () {
            var chk = $nc(this).attr('checked');
            $nc('input[name^=\"CM\"]').each(function (i, e) { if($nc(e).attr('id') != 'CMEmbeded') { if(chk) $nc(e).removeAttr('disabled').removeAttr('checked'); else $nc(e).attr('disabled', true); }});
        })</script>

		<fieldset>
            <legend><?= NETCAT_SETTINGS_JS ?></legend>
            <table border='0' cellpadding='6' cellspacing='0' width='100%'>
                <tr>
                    <td>
                        <?= NETCAT_SETTINGS_JS_FUNC_NC_JS ?>:
                    </td>
                </tr>
                <tr>
                    <td>
                        <?= nc_admin_checkbox_simple('JSLoadjQueryDollar', 1, NETCAT_SETTINGS_JS_LOAD_JQUERY_DOLLAR, $Array['JSLoadjQueryDollar']) ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?= nc_admin_checkbox_simple('JSLoadjQueryExtensionsAlways', 1, NETCAT_SETTINGS_JS_LOAD_JQUERY_EXTENSIONS_ALWAYS, $Array['JSLoadjQueryExtensionsAlways']) ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?= nc_admin_checkbox_simple('JSLoadModulesScripts', 1, NETCAT_SETTINGS_JS_LOAD_MODULES_SCRIPTS, $Array['JSLoadModulesScripts']) ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?= nc_admin_checkbox_simple('MinifyStaticFiles', 1, NETCAT_SETTINGS_MINIFY_STATIC_FILES, $Array['MinifyStaticFiles']) ?>
                    </td>
                </tr>
            </table>
        </fieldset>
        <br>

        <!-- Корзина-->
        <fieldset>
            <legend><?= NETCAT_SETTINGS_TRASHBIN
                        ?></legend>
            <table border='0' cellpadding='6' cellspacing='0' width='100%'>
                <tr>
                    <td>
                        <?= nc_admin_checkbox_simple('TrashUse', 1, "" . NETCAT_SETTINGS_TRASHBIN_USE . "", $Array['TrashUse'])
                        ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?= NETCAT_SETTINGS_TRASHBIN_MAXSIZE
                        ?> (<?= NETCAT_SIZE_MBYTES
                        ?>):<br>
                        <?= nc_admin_input_simple('TrashLimit', $Array["TrashLimit"], 70, '', "maxlength='255'")
                        ?>
                    </td>
                </tr>
            </table>
        </fieldset>
        <br>

        <!-- Компоненты -->
        <fieldset>
            <legend><?= NETCAT_SETTINGS_COMPONENTS
                        ?></legend>
            <table border='0' cellpadding='6' cellspacing='0' width='100%'>
                <tr>
                    <td>
                        <?= nc_admin_checkbox_simple('RemindSave', 1, "" . NETCAT_SETTINGS_REMIND_SAVE . "", $Array['RemindSave'])
                        ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?= nc_admin_checkbox_simple('PacketOperations', 1, "" . NETCAT_SETTINGS_PACKET_OPERATIONS . "", $Array['PacketOperations'])
                        ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?= nc_admin_checkbox_simple('TextareaResize', 1, "" . NETCAT_SETTINGS_TEXTAREA_RESIZE . "", $Array['TextareaResize'])
                        ?>
                    </td>
                </tr>
            </table>
        </fieldset>
        <br>

        <!-- NetCat QuickBar -->
        <fieldset>
            <legend><?= NETCAT_SETTINGS_QUICKBAR
                        ?></legend>
            <table border='0' cellpadding='6' cellspacing='0' width='100%'>
                <tr>
                    <td>
                        <?= nc_admin_checkbox_simple('QuickBar', 1, "" . NETCAT_SETTINGS_QUICKBAR_ENABLE . "", $Array['QuickBar'])
                        ?>
                    </td>
                </tr>
            </table>
        </fieldset>
        <br>

        <!-- FirePHP -->
        <!--
        <fieldset>
          <legend><?= NETCAT_SETTINGS_FIREPHP
                        ?></legend>
      <table border='0' cellpadding='6' cellspacing='0' width='100%'>
       <tr>
        <td>
        <?= nc_admin_checkbox_simple('FirePHP', 1, "" . NETCAT_SETTINGS_FIREPHP_ENABLE . "", $Array['FirePHP'], '', "id='FirePHP'")
        ?>
        </td>
      </tr>
      </table>
    </fieldset>
    <br>
        -->
        <!-- Syntax Highlighting -->
        <fieldset>
            <legend><?= NETCAT_SETTINGS_SYNTAXEDITOR
        ?></legend>
            <table border='0' cellpadding='6' cellspacing='0' width='100%'>
                <tr>
                    <td>
                        <?= nc_admin_checkbox_simple('SyntaxEditor', 1, "" . NETCAT_SETTINGS_SYNTAXEDITOR_ENABLE . "", $Array['SyntaxEditor'], '', "id='SyntaxEditor'")
                        ?>
                    </td>
                </tr>
            </table>
        </fieldset>
        <br>
        <!-- Syntax Checking -->

        <!-- Token -->
        <fieldset>
            <legend><?= NETCAT_SETTINGS_USETOKEN
                        ?></legend>
            <table border='0' cellpadding='6' cellspacing='0' width='100%'>
                <tr>
                    <td>
                        <?= nc_admin_checkbox_simple('UseTokenAdd', 1, "" . NETCAT_SETTINGS_USETOKEN_ADD . "", $Array['UseToken'] & NC_TOKEN_ADD, '', "id='UseTokenAdd'")
                        ?>
                        <br/>
                        <?= nc_admin_checkbox_simple('UseTokenEdit', 1, "" . NETCAT_SETTINGS_USETOKEN_EDIT . "", $Array['UseToken'] & NC_TOKEN_EDIT, '', "id='UseTokenEdit'")
                        ?>
                        <br/>
                        <?= nc_admin_checkbox_simple('UseTokenDrop', 1, "" . NETCAT_SETTINGS_USETOKEN_DROP . "", $Array['UseToken'] & NC_TOKEN_DROP, '', "id='UseTokenDrop'")
                        ?>
                        <br/>
                    </td>
                </tr>
            </table>
        </fieldset>
        <br>


        <fieldset>
            <legend><?= NETCAT_SETTINGS_ALTBLOCKS ?></legend>
            <table border='0' cellpadding='6' cellspacing='0' width='100%'>
                <tr>
                    <td>
    <?= nc_admin_checkbox_simple('AdminButtonsType', 1, "" . NETCAT_SETTINGS_ALTBLOCKS_TEXT . "", $Array['AdminButtonsType'], '', "id='AdminButtonsType'") ?>
                    </td>
                </tr>
                <tr>
                    <td>
    <?= nc_admin_textarea("\$f_AdminButtons", "AdminButtons", $Array['AdminButtons'], 1, 0) ?>
                    </td>
                </tr>
                <tr>
                    <td>
    <?= nc_admin_textarea("\$f_AdminCommon", "AdminCommon", $Array['AdminCommon'], 1, 0) ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?= NETCAT_SETTINGS_ALTBLOCKS_PARAMS ?>:<br>
    <?= nc_admin_input_simple('AdminParameters', $Array["AdminParameters"], 70, '', "maxlength='255'") ?>
                    </td>
                </tr>
            </table>
        </fieldset>
        <br>

        <!-- License
        <fieldset>
          <legend><?= NETCAT_SETTINGS_LICENSE ?></legend>
          <table border='0' cellpadding='6' cellspacing='0' width='100%'>
           <tr>
            <td>
        <?= NETCAT_SETTINGS_LICENSE_PRODUCT ?>:<br>
    <?= nc_admin_input_simple('ProductNumber', $Array["ProductNumber"], 70, '', "id='ProductNumber' maxlength='255'") ?>
            </td>
          </tr>
           <tr>
            <td>
        <?= NETCAT_SETTINGS_LICENSE_CODE
        ?>:<br>
        <?= nc_admin_input_simple('Code', $Array["Code"], 70, '', "id='ProductNumber' maxlength='255'")
        ?>
            </td>
          </tr>
          </table>
        </fieldset>
        <br>-->

        <input type=hidden name=phase value=2>
        <?php echo $nc_core->token->get_input(); ?>
        <?php
        global $UI_CONFIG;
        $UI_CONFIG->actionButtons[] = array("id" => "submit",
                "caption" => CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_SAVE,
                "action" => "mainView.submitIframeForm()"
        );
        ?>
        <input type='submit' class='hidden'>
    </form>
    <?php
}

function SettingsCompleted() {
    $nc_core = nc_Core::get_object();


    if (!$nc_core->input->fetch_get_post('ProjectName')) {
        nc_print_status(CONTROL_SETTINGSFILE_DOCHANGE_ERROR_NAME, 'error');
        #SettingsForm();
        return false;
    }

    $input = $nc_core->input->fetch_get_post();
    $p = array('ProjectName', 'UserEmailField', 'SpamFromName', 'SpamFromEmail',
            'RemindSave', 'PacketOperations', 'TextareaResize', 'QuickBar', 'SyntaxEditor', 'SyntaxCheck', 'AdminButtonsType',
            'AdminCommon', 'AdminParameters', 'AdminButtons', 'TrashUse', 'TrashLimit', 'EditDesignTemplateID',
            'CMEmbeded', 'CMDefault', 'CMAutocomplete', 'CMHelp', 'SaveKeycode',
            'JSLoadjQueryDollar', 'JSLoadjQueryExtensionsAlways', 'JSLoadModulesScripts', 'MinifyStaticFiles'/* , 'ProductNumber', 'Code' */);

    foreach ($p as $key) {
        $nc_core->set_settings($key, $input[$key]);
    }

    $nc_core->set_settings('UseToken', NC_TOKEN_ADD * $input['UseTokenAdd'] + NC_TOKEN_EDIT * $input['UseTokenEdit'] + NC_TOKEN_DROP * $input['UseTokenDrop']);

    return true;
}
?>
