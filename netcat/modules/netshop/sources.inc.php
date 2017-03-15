<?php

/**
 * Вывод списка источников
 *
 * @return bool
 */
function SourcesList() {
    global $nc_core, $db, $UI_CONFIG;

    $sql = "SELECT `source_id`, `name`, `catalogue_id`, `last_update` FROM `Netshop_ImportSources` ORDER BY `source_id` ASC";
    $sources = $db->get_results($sql, ARRAY_A);

    if ($sources) {

        foreach ($sources as $index => $source) {
            $id = (int)$source['source_id'];
            $last_update = strtotime($source['last_update']);
            $last_update = $last_update ? date('d.m.Y H:i:s', $last_update) : '-';

            $sql = "SELECT COUNT(*) FROM `Netshop_Stores` WHERE `Import_Source_ID` = {$id}";
            $stores = (int)$db->get_var($sql);

            $goods = 0;

            $sql = "SELECT `value` " .
                "FROM `Netshop_ImportMap` " .
                "WHERE `source_id` = '{$id}' " .
                "AND `type` = 'section'";
            $subdivisions = $db->get_col($sql);

            if ($subdivisions) {
                $sql = "SELECT DISTINCT `Class_ID` " .
                    "FROM `Sub_Class` " .
                    "WHERE `Subdivision_ID` IN (" . join(",", $subdivisions) . ") " .
                    "ORDER BY `Priority` DESC";
                $classes = (array)$db->get_col($sql);

                foreach ($classes AS $class) {
                    $class = (int)$class;
                    $sql = "SELECT COUNT(*) FROM `Message{$class}` WHERE `ImportSourceID` = {$id}";
                    $goods += (int)$db->get_var($sql);
                }
            }

            $sources[$index]['last_update'] = $last_update;
            $sources[$index]['goods'] = $goods;
            $sources[$index]['stores'] = $stores;
        }
        ?>
        <form method='post' action='sources.php'>
            <table class='nc-table nc--striped nc--hovered nc--wide'>
                <tr>
                    <th class='nc-text-center nc--compact'>ID</th>
                    <th><?= NETCAT_MODULE_NETSHOP_SOURCES_SOURCE_NAME; ?></th>
                    <th width="80"><?= NETCAT_MODULE_NETSHOP_SOURCES_CATALOGUE_ID; ?></th>
                    <th width="100"><?= NETCAT_MODULE_NETSHOP_SOURCES_GOODS_IMPORTED; ?></th>
                    <th width="100"><?= NETCAT_MODULE_NETSHOP_SOURCES_STORES_IMPORTED; ?></th>
                    <th width="120"><?= NETCAT_MODULE_NETSHOP_SOURCES_LAST_SYNC; ?></th>
                    <th width="120"><?= NETCAT_MODULE_NETSHOP_SOURCES_EDIT_MAPPING; ?></th>
                    <th class='nc-text-center nc--compact'>
                        <i class='nc-icon nc--remove nc--hovered' title='<?= NETCAT_MODULE_NETSHOP_SOURCES_DELETE_SOURCE; ?>'></i>
                    </th>
                </tr>
                <?php foreach ($sources as $source) { ?>
                    <tr>
                        <td><?= $source['source_id']; ?></td>
                        <td>
                            <a href="sources.php?phase=2&source_id=<?= $source['source_id']; ?>"><?= $source['name']; ?></a>
                        </td>
                        <td><?= $source['catalogue_id']; ?></td>
                        <td><?= $source['goods']; ?></td>
                        <td><?= $source['stores']; ?></td>
                        <td><?= $source['last_update']; ?></td>
                        <td>
                            <a href="sources.php?phase=6&source_id=<?= $source['source_id']; ?>"><?= NETCAT_MODULE_NETSHOP_SOURCES_EDIT; ?></a>
                        </td>
                        <td class='nc-text-center'>
                            <input type='checkbox' name='delete[]' value='<?= $source['source_id']; ?>'>
                        </td>
                    </tr>
                <?php } ?>
            </table>
            <input type='hidden' name='phase' value='3'>
        </form>
    <?php
    } else {
        nc_print_status(NETCAT_MODULE_NETSHOP_SOURCES_NO_SOURCES, "info");
        $msg = NETCAT_MODULE_NETSHOP_SOURCES_NO_SOURCES_MESSAGE;
        $msg .= nc_core()->ui->btn(nc_core()->SUB_FOLDER . nc_core()->HTTP_ROOT_PATH . 'modules/netshop/import.php', NETCAT_MODULE_NETSHOP_1C_INTEGRATION_IMPORT)->mini()->blue();
        echo nc_core()->ui->alert($msg)->blue();
    }

    if ($sources) {
        $UI_CONFIG->actionButtons[] = array(
            "id" => "submit",
            "caption" => NETCAT_MODULE_NETSHOP_SOURCES_DELETE_SELECTED,
            "action" => "mainView.submitIframeForm()",
            "red_border" => true,
        );
    }

    return true;
}

/**
 * Форма подтверждения удаления источников
 *
 * @return bool
 */
function DeleteConfirmationForm() {
    global $nc_core, $db, $UI_CONFIG;

    $delete = (array)$nc_core->input->fetch_post('delete');

    foreach ($delete as $index => $item) {
        $delete[$index] = (int)$item;
    }

    $sources = null;
    if (count($delete)) {
        $condition = implode(',', $delete);
        $sql = "SELECT `source_id`, `name` FROM `Netshop_ImportSources`" .
            "WHERE `source_id` IN ({$condition})";
        $sources = $db->get_results($sql, ARRAY_A);
    }

    if ($sources) {
        ?>
        <form action="sources.php" method="post">
            <?= NETCAT_MODULE_NETSHOP_SOURCES_REALLY_WANT_TO_DELETE_SOURCES; ?>
            <ul>
                <?php foreach ($sources as $source) { ?>
                    <li>
                        <input type="hidden" name="delete[]" value="<?= $source['source_id']; ?>"/> <?= $source['name']; ?>
                    </li>
                <?php } ?>
            </ul>
            <?= $nc_core->token->get_input(); ?>
            <input type="hidden" name="phase" value="4"/>
        </form>
    <?php
    }

    $UI_CONFIG->actionButtons = array(
        array(
            "id" => "cancel",
            "caption" => NETCAT_MODULE_NETSHOP_SOURCES_CANCEL,
            "location" => "module.netshop.1c.sources",
            "align" => "left"
        )
    );

    if ($sources) {
        $UI_CONFIG->actionButtons[] = array(
            "id" => "submit",
            "caption" => NETCAT_MODULE_NETSHOP_SOURCES_DELETE_CONFIRM,
            "action" => "mainView.submitIframeForm()",
            "red_border" => true,
        );
    }

    return $sources ? true : false;
}

/**
 * Удаление источников
 *
 * @return bool
 */
function DeleteSources() {
    global $nc_core, $db, $UI_CONFIG;

    $delete = (array)$nc_core->input->fetch_post('delete');

    foreach ($delete as $index => $item) {
        $delete[$index] = (int)$item;
    }

    if (count($delete)) {
        $condition = implode(',', $delete);
        $sql = "DELETE FROM `Netshop_ImportSources`" .
            "WHERE `source_id` IN ({$condition})";
        $db->query($sql);

        $sql = "DELETE FROM `Netshop_ImportMap`" .
            "WHERE `source_id` IN ({$condition})";
        $db->query($sql);

        $sql = "DELETE FROM `Netshop_Stores`" .
            "WHERE `Import_Source_ID` IN ({$condition})";
        $db->query($sql);

        $sql = "DELETE FROM `Netshop_StoreGoods`" .
            "WHERE `Netshop_Store_ID` NOT IN (SELECT `Netshop_Store_ID` FROM `Netshop_Stores`)";
        $db->query($sql);
    }

    return true;
}

/**
 * Вывод информации по источнику
 *
 * @param $id
 * @return bool
 */
function ViewSource($id) {
    global $nc_core, $db, $UI_CONFIG;

    $source_id = (int)$id;

    $sql = "SELECT * FROM `Netshop_ImportSources` WHERE `source_id` = {$source_id}";
    $source = $db->get_row($sql, ARRAY_A);

    $owner = $source['owner'];
    $owner = @json_decode($owner, true);
    $catalogue_id = $source['catalogue_id'];

    $last_update = strtotime($source['last_update']);
    $last_update = $last_update ? date('d.m.Y H:i:s', $last_update) : '-';

    $goods_count = 0;

    $sql = "SELECT `value` " .
        "FROM `Netshop_ImportMap` " .
        "WHERE `source_id` = '{$source_id}' " .
        "AND `type` = 'section'";
    $subdivisions = $db->get_col($sql);

    if ($subdivisions) {
        $sql = "SELECT DISTINCT `Class_ID` " .
            "FROM `Sub_Class` " .
            "WHERE `Subdivision_ID` IN (" . join(",", $subdivisions) . ") " .
            "ORDER BY `Priority` DESC";
        $classes = (array)$db->get_col($sql);
        foreach ($classes AS $class) {
            $class = (int)$class;
            $sql = "SELECT COUNT(*) FROM `Message{$class}` WHERE `ImportSourceID` = {$source_id}";
            $goods_count += (int)$db->get_var($sql);
        }
    }

    $sql = "SELECT `Netshop_Store_ID`, `Import_Store_ID`, `Name` FROM `Netshop_Stores` " .
        "WHERE `Import_Source_ID` = {$source_id}";
    $stores = (array)$db->get_results($sql, ARRAY_A);
    foreach ($stores as $index => $store) {
        $netshop_store_id = (int)$store['Netshop_Store_ID'];

        $sql = "SELECT SUM(`Quantity`) FROM `Netshop_StoreGoods` WHERE `Netshop_Store_ID` = {$netshop_store_id}";
        $goods = (float)$db->get_var($sql);

        $stores[$index]['goods'] = $goods;
    }

    if ($source) {
        $netshop = nc_netshop::get_instance($catalogue_id);
        $is_netshop_v1_in_use = $netshop->is_netshop_v1_in_use();
        ?>
        <div class="nc_admin_fieldset_head">
            <b><?= NETCAT_MODULE_NETSHOP_SOURCES_SOURCE; ?> "<?= $source['name']; ?>"</b></div>
        <div class="nc_admin_fieldset_head">Настройки</div>
        <form action="sources.php" method="post">
            <input type="hidden" name="phase" value="8"/>
            <input type="hidden" name="source_id" value="<?= $source_id; ?>"/>

            <div class="nc-form">
                Название источника:<br>
                <input type="text" name="name" style="width: 200px;" value="<?= $source['name']; ?>"/><br>
                <?php if (!$is_netshop_v1_in_use) {
                    echo NETCAT_MODULE_NETSHOP_IMPORT_ROOT_SUBDIVISION . "<br>";
                    echo "<input type='text' name='root_subdivision_id' value='{$source['root_subdivision_id']}' size='5'><br>";
                } ?>
                <?= NETCAT_MODULE_NETSHOP_IMPORT_ACTION_NONEXISTANT; ?><br>
                <select class='nc-select' name='nonexistant' style='width: 200px;'>
                    <?php
                    $nonexistant_options = array(
                        "disable" => NETCAT_MODULE_NETSHOP_IMPORT_ACTION_NONEXISTANT_DISABLE,
                        "ignore" => NETCAT_MODULE_NETSHOP_IMPORT_ACTION_NONEXISTANT_IGNORE,
                        //"delete"=> NETCAT_MODULE_NETSHOP_IMPORT_ACTION_NONEXISTANT_DELETE,
                    );
                    foreach ($nonexistant_options AS $option => $text) {
                        ?>
                        <option value='<?= $option; ?>' <?= $source["nonexistant"] == $option ? " selected" : ""; ?>><?= htmlspecialchars($text); ?></option>
                    <?php } ?>
                </select><br>
                <?= NETCAT_MODULE_NETSHOP_IMPORT_AUTO_ADD_SECTIONS; ?><br>
                <select name='auto_add_sections' style='width: 200px;'>
                    <option value='0'><?= NETCAT_MODULE_NETSHOP_IMPORT_AUTO_ADD_SECTIONS_DONT_ADD; ?></option>
                    <?php
                    $components = array();
                    if ($is_netshop_v1_in_use) {
                        $MODULE_VARS = $nc_core->modules->get_module_vars();
                        foreach (explode(',', $MODULE_VARS['netshop']['GOODS_TABLE']) as $component) {
                            $component = (int)trim($component);
                            if ($component) {
                                $components[] = $component;
                            }
                        }
                    } else {
                        $components = $netshop->get_goods_components_ids();
                    }
                    foreach ($components as $component) {
                        $class_name = $nc_core->component->get_by_id($component, 'Class_Name'); ?>
                        <option value='<?= $component; ?>' <?= $component == $source['auto_add_sections'] ? "selected='selected'" : ""; ?>>[<?= $component; ?>] <?= $class_name; ?></option>
                    <?php } ?>
                </select><br>
                <input type='checkbox' name='auto_move_sections' value='1' id='auto_move_sections' <?= $source['auto_move_sections'] ? 'checked="checked"' : ''; ?>>
                <label for='auto_move_sections'><?= NETCAT_MODULE_NETSHOP_IMPORT_AUTO_MOVE_SECTIONS; ?></label><br>
                <input type='checkbox' name='delete_tmp_files' value='1' id='delete_tmp_files' <?= $source['delete_tmp_files'] ? 'checked="checked"' : ''; ?>>
                <label for='delete_tmp_files'><?= NETCAT_MODULE_NETSHOP_IMPORT_DELETE_TMP_FILES; ?></label><br>
                <input type='checkbox' name='auto_rename_subdivisions' value='1' id='auto_rename_subdivisions' <?= $source['auto_rename_subdivisions'] ? "checked='checked'" : ""; ?>>
                <label for='auto_rename_subdivisions'><?= NETCAT_MODULE_NETSHOP_IMPORT_AUTO_RENAME_SUBDIVISIONS; ?></label><br>
                <input type='checkbox' name='auto_change_subdivision_links' value='1' id='auto_change_subdivision_links' <?= $source['auto_change_subdivision_links'] ? "checked='checked'" : ""; ?>>
                <label for='auto_change_subdivision_links'><?= NETCAT_MODULE_NETSHOP_IMPORT_AUTO_CHANGE_SUBDIVISION_LINKS; ?></label>
            </div>
        </form>
        <div class="nc_admin_fieldset_head"><?= NETCAT_MODULE_NETSHOP_SOURCES_MANUAL_SYNC; ?></div>
        <a href="export/cml2_catalog.php?source_id=<?php echo $source_id; ?>"><?php echo NETCAT_MODULE_NETSHOP_SOURCES_EXPORT_CATALOGUE; ?></a>
        <br>
        <a href="export/cml2_offers.php?source_id=<?php echo $source_id; ?>"><?php echo NETCAT_MODULE_NETSHOP_SOURCES_EXPORT_OFFERS; ?></a>
        <br>
        <a href="export/cml2_orders.php?source_id=<?php echo $source_id; ?>"><?php echo NETCAT_MODULE_NETSHOP_SOURCES_EXPORT_ORDERS; ?></a>
        <br>
        <div class="nc_admin_fieldset_head"><?= NETCAT_MODULE_NETSHOP_SOURCES_OWNER; ?></div>
        <?php if ($owner) { ?>
            <b><?= NETCAT_MODULE_NETSHOP_SOURCES_ID; ?>:</b> <?= $owner['id']; ?><br>
            <b><?= NETCAT_MODULE_NETSHOP_SOURCES_NAME; ?>:</b> <?= $owner['name']; ?><br>
            <b><?= NETCAT_MODULE_NETSHOP_SOURCES_OFFICIAL_NAME; ?>:</b> <?= $owner['official_name']; ?><br>
            <b><?= NETCAT_MODULE_NETSHOP_SOURCES_ADDRESS; ?>:</b> <?= $owner['address']; ?><br>
            <b><?= NETCAT_MODULE_NETSHOP_SOURCES_INN; ?>:</b> <?= $owner['inn'] ? $owner['inn'] : ''; ?><br>
            <b><?= NETCAT_MODULE_NETSHOP_SOURCES_KPP; ?>:</b> <?= $owner['kpp'] ? $owner['kpp'] : ''; ?>
        <?php } else { ?>
            <?= NETCAT_MODULE_NETSHOP_SOURCES_INFORMATION_NOT_AVAILABLE; ?>
        <?php } ?>

        <div class="nc_admin_fieldset_head"><?= NETCAT_MODULE_NETSHOP_SOURCES_INFORMATION; ?></div>
        <?= NETCAT_MODULE_NETSHOP_SOURCES_CATALOGUE_ID; ?>: <?= $source['catalogue_id']; ?><br>
        <?= NETCAT_MODULE_NETSHOP_SOURCES_GOODS_IMPORTED; ?>: <?= $goods_count; ?><br>
        <?= NETCAT_MODULE_NETSHOP_SOURCES_LAST_SYNC; ?>: <?= $last_update; ?>

        <?php if ($stores) { ?>
            <div class="nc_admin_fieldset_head"><?= NETCAT_MODULE_NETSHOP_SOURCES_IMPORTED_STORES; ?></div>
            <table class='nc-table nc--striped nc--hovered nc--wide'>
                <tr>
                    <th class='nc-text-center nc--compact'>ID</th>
                    <th><?= NETCAT_MODULE_NETSHOP_SOURCES_STORE_NAME; ?></th>
                    <th width="150"><?= NETCAT_MODULE_NETSHOP_SOURCES_1C_ID; ?></th>
                    <th width="150"><?= NETCAT_MODULE_NETSHOP_SOURCES_REMAIN_GOODS; ?></th>
                </tr>
                <?php foreach ($stores as $store) { ?>
                    <tr>
                        <td><?= $store['Netshop_Store_ID']; ?></td>
                        <td>
                            <a href="sources.php?phase=5&store_id=<?= $store['Netshop_Store_ID']; ?>" title="<?= NETCAT_MODULE_NETSHOP_SOURCES_VIEW_GOODS; ?>"><?= $store['Name']; ?></a>
                        </td>
                        <td><?= $store['Import_Store_ID']; ?></td>
                        <td><?= $store['goods']; ?></td>
                    </tr>
                <?php } ?>
            </table>
            </div>
        <?php } ?>
        <?php
        $UI_CONFIG->actionButtons = array(
            array(
                "id" => "cancel",
                "caption" => NETCAT_MODULE_NETSHOP_SOURCES_BACK,
                "location" => "module.netshop.1c.sources",
                "align" => "left"
            )
        );

        $UI_CONFIG->actionButtons[] = array(
            "id" => "submit",
            "caption" => NETCAT_MODULE_NETSHOP_SOURCES_SAVE,
            "action" => "mainView.submitIframeForm()",
        );
    }

    return $source ? true : false;
}

/**
 * Вывод информации по складу
 *
 * @param $id
 * @return bool
 */
function ViewStore($id) {
    global $nc_core, $db, $UI_CONFIG;

    $store_id = (int)$id;

    $sql = "SELECT `Name`, `Import_Source_ID` FROM `Netshop_Stores` " .
        "WHERE `Netshop_Store_ID` = {$store_id}";

    $store = $db->get_row($sql, ARRAY_A);

    if ($store) {
        $sql = "SELECT `Netshop_Item_ID`, `Class_ID`, `Quantity` FROM `Netshop_StoreGoods` WHERE " .
            "`Netshop_Store_ID` = {$store_id} ORDER BY `Netshop_Item_ID` ASC";
        $store_goods = $db->get_results($sql, ARRAY_A);

        $goods = array();
        foreach ($store_goods as $item) {
            $message_id = (int)$item['Netshop_Item_ID'];
            $class_id = (int)$item['Class_ID'];

            $sql = "SELECT `Subdivision_ID`, `Sub_Class_ID`, `Name` FROM `Message{$class_id}` WHERE " .
                "`Message_ID` = {$message_id}";

            $row = $db->get_row($sql, ARRAY_A);

            if ($row) {
                $goods[] = array(
                    'Message_ID' => $message_id,
                    'Subdivision_ID' => $row['Subdivision_ID'],
                    'Sub_Class_ID' => $row['Sub_Class_ID'],
                    'Name' => $row['Name'],
                    'Quantity' => $item['Quantity'],
                );
            }
            ?>
            <a href="sources.php?phase=2&source_id=<?= $store['Import_Source_ID']; ?>"><?= NETCAT_MODULE_NETSHOP_SOURCES_GO_BACK; ?></a>

            <h3><?= NETCAT_MODULE_NETSHOP_SOURCES_STORE_REMAIN; ?> <b>"<?= $store['Name']; ?>"</b></h3>
            <table class='nc-table nc--striped nc--hovered nc--wide'>
                <tr>
                    <th><?= NETCAT_MODULE_NETSHOP_SOURCES_ITEM; ?></th>
                    <th width="150"><?= NETCAT_MODULE_NETSHOP_SOURCES_REMAIN; ?></th>
                </tr>
                <?php foreach ($goods as $item) { ?>
                    <tr>
                        <td>
                            <a href="#object.view(<?= $item['Sub_Class_ID']; ?>,<?= $item['Message_ID']; ?>)" onclick="parent.urlDispatcher.load('object.view(<?= $item['Sub_Class_ID']; ?>,<?= $item['Message_ID']; ?>)');"><?= $item['Name']; ?></a>
                        </td>
                        <td><?= $item['Quantity']; ?></td>
                    </tr>
                <?php } ?>
            </table>
        <?php

        }
    }

    return $store ? true : false;
}

/**
 * Вывод информации по
 * соответсвиям полей
 *
 * @param $id
 * @return bool
 */
function ViewSourceMapping($id) {
    global $nc_core, $db, $UI_CONFIG;

    $source_id = (int)$id;

    $sql = "SELECT `source_id`, `name`, `owner`, `catalogue_id`, `last_update` FROM `Netshop_ImportSources` WHERE `source_id` = {$source_id}";
    $source = $db->get_row($sql, ARRAY_A);

    if ($source) {
        $catalogue_id = $source['catalogue_id'];

        $netshop = nc_netshop::get_instance($catalogue_id);

        $goods_tables = array();
        if ($netshop->is_netshop_v1_in_use()) {
            foreach (explode(',', $nc_core->modules->get_vars('netshop', 'GOODS_TABLE')) as $table) {
                $goods_tables[] = (int)trim($table);
            }
        } else {
            $goods_tables = $netshop->get_goods_components_ids();
        }
        ?>
        <script type="text/javascript">
            $nc(function(){
                $('INPUT[name^="resize["], INPUT[name^="preview["]').on('change', function(){
                    var $this = $nc(this);
                    if ($this.is(':checked')) {
                        $this.nextAll('DIV').eq(0).show();
                    } else {
                        $this.nextAll('DIV').eq(0).hide();
                    }
                    return false;
                });
            });
        </script>
        <div class="nc_admin_fieldset_head"><?= NETCAT_MODULE_NETSHOP_SOURCES_SOURCE; ?> "<?= $source['name']; ?>"</div>
        <b><?= NETCAT_MODULE_NETSHOP_IMPORT_FIELDS_AND_TAGS_COMPLIANCE; ?></b>
        <form action="sources.php" method="post">
            <input type="hidden" name="phase" value="7"/>
            <input type="hidden" name="source_id" value="<?= $source_id; ?>"/>
            <?php
            $xml_fields = array();

            $sql = "SELECT `type`, `source_string`, `value`, `format` FROM `Netshop_ImportMap` WHERE `source_id` = {$source_id} AND `type` <> 'section'";
            foreach ((array)$db->get_results($sql, ARRAY_A) as $field) {
                if (!in_array($field['source_string'], $xml_fields)) {
                    $xml_fields[] = $field['source_string'];
                }
            }

            foreach ($goods_tables as $goods_table) {
                $sql = "SELECT `Field_ID`, `Field_Name`, `Description` FROM `Field` WHERE `Class_ID` = {$goods_table}";
                $netcat_fields = array();
                foreach ((array)$db->get_results($sql, ARRAY_A) as $netcat_field) {
                    $netcat_fields[$netcat_field['Field_ID']] = '[' . $netcat_field['Field_Name'] . '] - ' . ($netcat_field['Description'] ? $netcat_field['Description'] : $netcat_field['Field_Name']);
                }

                $ext_fields = array();
                foreach ($xml_fields as $xml_field) {
                    $xml_field = $db->escape($xml_field);
                    $sql = "SELECT `type`, `source_string`, `value`, `format` FROM `Netshop_ImportMap` WHERE `source_id` = {$source_id} AND `source_string` = '{$xml_field}'";

                    $values = $db->get_results($sql, ARRAY_A);


                    foreach ($values as $value) {
                        $ext_field = $value;
                        if ($value['value'] != -1) {
                            $field_id = (int)$value['value'];
                            $sql = "SELECT `Field_ID` FROM `Field` WHERE `Field_ID` = {$field_id} AND `Class_ID` = {$goods_table}";
                            if ($db->get_var($sql)) {
                                break;
                            }
                        }
                    }

                    $ext_fields[] = $ext_field;
                }
                $sql = "SELECT `Class_Name` FROM `Class` WHERE `Class_ID` = {$goods_table}";
                $component_name = $db->get_var($sql);
                ?>
                <b style="display: block; margin-top: 30px;"><?= $component_name; ?> [<?= $goods_table; ?>]</b>
                <table cellspacing="8">
                    <?php foreach ($ext_fields as $ext_field) {
                        $format = @unserialize($ext_field['format']);
                        ?>
                        <tr>
                            <td valign="middle"><?= isset($format['name']) ? $format['name'] : $ext_field['source_string']; ?>:</td>
                            <td>
                                <select name="map_fields[<?= $goods_table; ?>][<?= urlencode($ext_field['source_string']); ?>]">
                                    <option value="-1" <?php echo $ext_field['value'] == -1 ? 'selected="selected"' : ''; ?>><?= NETCAT_MODULE_NETSHOP_SOURCES_FIELD_NOT_SELECTED; ?></option>
                                    <?php foreach ($netcat_fields as $field_id => $field_name) { ?>
                                        <option value="<?= $field_id; ?>" <?php echo $ext_field['value'] == $field_id ? 'selected="selected"' : ''; ?>><?= $field_name; ?></option>
                                    <?php } ?>
                                </select>
                                <?php if ($ext_field['source_string'] == NETCAT_MODULE_NETSHOP_1C_IMG) {
                                    if (!isset($format['resize'])) {
                                        $format['resize'] = array(
                                            'enabled' => false,
                                            'width' => 0,
                                            'height' => 0,
                                        );
                                    }

                                    if (!isset($format['preview'])) {
                                        $format['preview'] = array(
                                            'enabled' => false,
                                            'width' => 0,
                                            'height' => 0,
                                        );
                                    }
                                    ?>
                                    <br>
                                    <input type="checkbox" name="resize[<?= $goods_table; ?>][<?= urlencode($ext_field['source_string']); ?>]" id="nc_use_resize_<?= urlencode($goods_table . $ext_field['source_string']); ?>" <?php echo $format['resize']['enabled'] ? 'checked="checked"' : ''; ?> />
                                    <label for="nc_use_resize_<?= urlencode($goods_table . $ext_field['source_string']); ?>"><?= CONTROL_FIELD_MULTIFIELD_USE_IMAGE_RESIZE; ?></label>
                                    <div <?php echo !$format['resize']['enabled'] ? 'style="display: none;"' : ''; ?>>
                                        <?= CONTROL_FIELD_MULTIFIELD_IMAGE_WIDTH; ?>:
                                        <input type="text" value="<?= $format['resize']['width']; ?>" name="resize_width[<?= $goods_table; ?>][<?= urlencode($ext_field['source_string']); ?>]" size="10"/>
                                        <?= CONTROL_FIELD_MULTIFIELD_IMAGE_HEIGHT; ?>:
                                        <input type="text" value="<?= $format['resize']['height']; ?>" name="resize_height[<?= $goods_table; ?>][<?= urlencode($ext_field['source_string']); ?>]" size="10"/>
                                    </div>
                                    <br>
                                    <input type="checkbox" name="preview[<?= $goods_table; ?>][<?= urlencode($ext_field['source_string']); ?>]" id="nc_use_preview_<?= urlencode($ext_field['source_string']); ?>" <?php echo $format['preview']['enabled'] ? 'checked="checked"' : ''; ?> />
                                    <label for="nc_use_preview_<?= urlencode($ext_field['source_string']); ?>"><?= CONTROL_FIELD_MULTIFIELD_USE_IMAGE_PREVIEW; ?></label>
                                    <div <?php echo !$format['preview']['enabled'] ? 'style="display: none;"' : ''; ?>>
                                        <?= CONTROL_FIELD_MULTIFIELD_IMAGE_WIDTH; ?>:
                                        <input type="text" value="<?= $format['preview']['width']; ?>" size="10" name="preview_width[<?= $goods_table; ?>][<?= urlencode($ext_field['source_string']); ?>]"/>
                                        <?= CONTROL_FIELD_MULTIFIELD_IMAGE_HEIGHT; ?>:
                                        <input type="text" value="<?= $format['preview']['height']; ?>" size="10" name="preview_height[<?= $goods_table; ?>][<?= urlencode($ext_field['source_string']); ?>]"/>
                                    </div>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                </table>
            <?php } ?>
        </form>
        <?php
        $UI_CONFIG->actionButtons[] = array(
            "id" => "submit",
            "caption" => NETCAT_MODULE_NETSHOP_SOURCES_SAVE,
            "action" => "mainView.submitIframeForm()",
        );
    }

    return $source ? true : false;
}

/**
 * Сохранение настроек
 *
 * @param $id
 * @return bool
 */
function SaveSource($id) {
    global $nc_core, $db, $UI_CONFIG;

    $source_id = (int)$id;

    $sql = "SELECT `source_id`, `name`, `owner`, `catalogue_id`, `last_update` FROM `Netshop_ImportSources` WHERE `source_id` = {$source_id}";
    $source = $db->get_row($sql, ARRAY_A);
    if ($source) {
        $catalogue_id = $source['catalogue_id'];
        $netshop = nc_netshop::get_instance($catalogue_id);
        $settings = array(
            'name',
            'nonexistant',
            'auto_add_sections',
            'auto_move_sections',
            'auto_rename_subdivisions',
            'auto_change_subdivision_links',
            'delete_tmp_files',
        );

        if (!$netshop->is_netshop_v1_in_use()) {
            $settings[] = 'root_subdivision_id';
        }

        $sql = "UPDATE `Netshop_ImportSources` SET ";
        $i = 0;
        foreach ($settings as $item) {
            if ($i++ > 0) {
                $sql .= ', ';
            }
            $value = $db->escape($nc_core->input->fetch_post($item));

            $sql .= "`{$item}` = '{$value}'";
        }

        $sql .= " WHERE `source_id` = {$source_id}";

        $db->query($sql);
    }

    return $source ? true : false;
}

/**
 * Сохранение соответсвтий
 * полей
 *
 * @param $id
 * @return bool
 */
function SaveSourceMapping($id) {
    global $nc_core, $db, $UI_CONFIG;

    $source_id = (int)$id;

    $sql = "SELECT `source_id`, `name`, `owner`, `catalogue_id`, `last_update` FROM `Netshop_ImportSources` WHERE `source_id` = {$source_id}";
    $source = $db->get_row($sql, ARRAY_A);
    if ($source) {
        $catalogue_id = $source['catalogue_id'];

        $netshop = nc_netshop::get_instance($catalogue_id);

        $goods_tables = array();
        if ($netshop->is_netshop_v1_in_use()) {
            foreach (explode(',', $nc_core->modules->get_vars('netshop', 'GOODS_TABLE')) as $table) {
                $goods_tables[] = (int)trim($table);
            }
        } else {
            $goods_tables = $netshop->get_goods_components_ids();
        }

        $field_names = array();

        $sql = "SELECT `source_string`, `format` FROM `Netshop_ImportMap` WHERE `source_id` = {$source_id} AND `type` <> 'section'";

        foreach ((array)$db->get_results($sql, ARRAY_A) as $field) {
            $format = @unserialize($field['format']);

            $field_names[$field['source_string']] = is_array($format) && isset($format['name']) ? $format['name'] : $field['source_string'];
        }

        $map_fields = $nc_core->input->fetch_post('map_fields');
        $resize = $nc_core->input->fetch_post('resize');
        $resize_width = $nc_core->input->fetch_post('resize_width');
        $resize_height = $nc_core->input->fetch_post('resize_height');
        $preview = $nc_core->input->fetch_post('preview');
        $preview_width = $nc_core->input->fetch_post('preview_width');
        $preview_height = $nc_core->input->fetch_post('preview_height');

        foreach ($field_names as $source_string => $xml_name) {
            $update_values = array();

            foreach ($goods_tables as $goods_table) {
                if (isset($map_fields[$goods_table][urlencode($source_string)])) {
                    if ($source_string == NETCAT_MODULE_NETSHOP_1C_IMG) {
                        $resize_format = array(
                            'enabled' => isset($resize[$goods_table][urlencode($source_string)]),
                            'width' => (int)$resize_width[$goods_table][urlencode($source_string)],
                            'height' => (int)$resize_height[$goods_table][urlencode($source_string)],
                        );
                        $preview_format = array(
                            'enabled' => isset($preview[$goods_table][urlencode($source_string)]),
                            'width' => (int)$preview_width[$goods_table][urlencode($source_string)],
                            'height' => (int)$preview_height[$goods_table][urlencode($source_string)],
                        );
                    } else {
                        $resize_format = null;
                        $preview_format = null;
                    }

                    $update_values[] = array(
                        'field_id' => $map_fields[$goods_table][urlencode($source_string)],
                        'name' => $source_string != $xml_name ? $xml_name : '',
                        'resize' => $resize_format,
                        'preview' => $preview_format,
                    );
                }
            }

            $source_string_escaped = $db->escape($source_string);

            $sql = "SELECT `type`, `parent_tag` FROM `Netshop_ImportMap` WHERE `source_id` = {$source_id} AND `source_string` = '{$source_string_escaped}' LIMIT 1";
            $row = $db->get_row($sql, ARRAY_A);
            $type = $db->escape($row['type']);
            $parent_tag = $db->escape($row['parent_tag']);

            $sql = "DELETE FROM `Netshop_ImportMap` WHERE `source_id` = {$source_id} AND `source_string` = '{$source_string_escaped}'";
            $db->query($sql);

            foreach ($update_values as $update) {
                $field_id = (int)$update['field_id'];

                $format = array();
                if ($update['name']) {
                    $format['name'] = $update['name'];
                }

                if ($update['resize']) {
                    $format['resize'] = $update['resize'];
                }

                if ($update['preview']) {
                    $format['preview'] = $update['preview'];
                }


                $format = $db->escape(serialize($format));

                $sql = "INSERT INTO `Netshop_ImportMap` (`source_string`, `parent_tag`, `type`, `source_id`, `format`, `value`) VALUES " .
                    "('{$source_string_escaped}', '{$parent_tag}', '{$type}', {$source_id}, '{$format}', {$field_id})";

                $db->query($sql);
            }
        }
    }

    return $source ? true : false;
}