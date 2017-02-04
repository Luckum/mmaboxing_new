<?php

/**
 * Input:
 *  - action: index|edit|delete|generate_ask|generate_act|toggle
 *  - deal_type: discount_item
 *  - deal_id
 *
 *  index
 *  - offset
 *
 *  edit, save:
 *  - coupon_id
 *
 *  delete:
 *  - coupon_ids
 *
 *  generate_act:
 *  - num_codes
 *  - coupon_options[]:
 *      When num_coupons = 1:
 *      - code
 *      When num_coupons > 1:
 *      - code_prefix
 *      - code_symbols
 *      - code_length  (длина генерируемой части)
 *    - expires: 0 -- без срока действия, 1 -- со сроком действия равным valid_till
 *    - valid_till (срок действия)
 *    - max_usages
 *  - send_to_users: 0|1
 *  - user_ids: comma-delimited user id list
 *  - mail_options[]: mail options
 *
 */

require_once '../header.inc.php';

/* @var nc_netshop $shop */
/* @var nc_ui $tpl */
/* @var nc_db $db */
/* @var nc_input $input */
$nc_core = nc_core::get_object();
$tpl = $nc_core->ui;
$db = $nc_core->db;
$input = $nc_core->input;
$shop = nc_modules('netshop');

$action = $input->fetch_get_post('action');
if (empty($action)) { $action = 'index'; }

$deal_type = $input->fetch_get_post('deal_type');
$deal_id = (int)$input->fetch_get_post('deal_id');

// action = edit | toggle
$coupon_id = (int)$input->fetch_get_post('coupon_id');
if ($coupon_id) {
    $coupon = new nc_netshop_promotion_coupon($coupon_id);
    $deal_type = $coupon->get('deal_type');
    $deal_id = $coupon->get('deal_id');
}

$deal = nc_netshop_promotion_deal::by_id($deal_type, $deal_id);
if (!$deal) { die("Incorrect parameter value (deal_type, deal_id)"); }

$index_params = "deal_type=$deal_type&deal_id=$deal_id";
$UI_CONFIG = new nc_netshop_promotion_admin_coupon_ui($deal);

switch ($action) {
    //--------------------------------------------------------------------------
    case 'index':
        $offset = (int)$input->fetch_get_post('offset');
        $limit = 1000;
        $coupons = $db->get_results("SELECT SQL_CALC_FOUND_ROWS *
                                       FROM `Netshop_Coupon`
                                      WHERE `Deal_Type`= '$deal_type'
                                        AND `Deal_ID` = $deal_id
                                      ORDER BY `Enabled` DESC, `Created` DESC
                                      LIMIT $limit OFFSET $offset", ARRAY_A);
        $num_coupons = $db->get_var("SELECT FOUND_ROWS()");

        if ($num_coupons) {
            $table = $tpl->table()->wide()->striped()->bordered()->hovered();
            $thead = $table->thead();
            $thead->th()->compact();
            $thead->th(NETCAT_MODULE_NETSHOP_PROMOTION_COUPON_CODE);
            $thead->th(NETCAT_MODULE_NETSHOP_PROMOTION_COUPON_NUMBER_OF_USAGES)->text_center();
            $thead->th(NETCAT_MODULE_NETSHOP_PROMOTION_COUPON_VALID_TILL)->text_center();
            $thead->th(NETCAT_MODULE_NETSHOP_PROMOTION_LIST_EDIT_HEADER)->text_center();
            $thead->th()->text_center()->icon('remove')->class_name('toggle-remove-checkboxes');

            $tr = $table->row();
            $tr->enabled = $tr->td();
            $tr->code = $tr->td();
            $tr->usage_count = $tr->td()->text_center();
            $tr->valid_till = $tr->td()->text_center();
            $tr->actions = $tr->td()->text_center();
            $action_edit = $actions = $tpl->html->a()->title(NETCAT_MODULE_NETSHOP_ACTION_EDIT)->icon('edit');
            $tr->delete_box = $tr->td()->text_center()->checkbox('coupon_ids[]');

            foreach ($coupons as $row) {
                $tr->enabled->text($tpl->controls->toggle_button(
                    $row['Enabled'],
                    array('action' => 'toggle', 'coupon_id' => $row['Coupon_ID'])
                ));

                $tr->code->text($row['Code']);

                $usage_count = $row['UsageCount'] .
                               ($row['MaxUsages']
                                   ? " " . NETCAT_MODULE_NETSHOP_PROMOTION_COUPON_NUMBER_OF_USAGES_OUT_OF . " " . $row['MaxUsages']
                                   : ""
                               );
                $tr->usage_count->text($usage_count);

                $valid_till_cell = $row['ValidTill']
                                        ? date(NETCAT_MODULE_NETSHOP_DATETIME_FORMAT, strtotime($row['ValidTill']))
                                        : NETCAT_MODULE_NETSHOP_PROMOTION_COUPON_VALID_INDEFINITELY;
                $tr->valid_till->text($valid_till_cell);

                $tr->actions->text($action_edit->href("?action=edit&amp;coupon_id=$row[Coupon_ID]"));
                $tr->delete_box->value($row['Coupon_ID']);
                $table->add_row($tr);
            }

            echo '<form action="?action=delete" method="POST">',
                    '<input type="hidden" name="deal_type" value="' . $deal_type . '" />',
                    '<input type="hidden" name="deal_id" value="' . $deal_id . '" />',
                    $table,
                 '</form><br><div>';

            if ($offset > 0) {
                echo "<div style='float:left'>",
                     "<a href='?deal_type=$deal_type&amp;deal_id=$deal_id&amp;offset=" . max(0, $offset - $limit) . "'>",
                     NETCAT_MODULE_NETSHOP_LIST_PREVIOUS_PAGE,
                     "</a></div>";
            }
            if ($num_coupons > $offset + $limit) {
                echo "<div style='float:right; text-align: right'>",
                     "<a href='?deal_type=$deal_type&amp;deal_id=$deal_id&amp;offset=" . ($offset + $limit) . "'>",
                     NETCAT_MODULE_NETSHOP_LIST_NEXT_PAGE,
                     "</a></div>";
            }
            echo "</div><div style='clear: both; height: 12px'></div>";

            echo "<div style='margin-bottom: 12px'><a href='?action=export_csv&amp;$index_params' target='_blank'>",
                  NETCAT_MODULE_NETSHOP_PROMOTION_COUPON_CSV_LINK,
                 "</a></div>";

            $UI_CONFIG->add_submit_button(NETCAT_MODULE_NETSHOP_BUTTON_DELETE_SELECTED);

            ?>
            <script>
                $nc('.toggle-remove-checkboxes').click(function() {
                    var cb = $nc('input[name="coupon_ids[]"]');
                    cb.prop('checked', (cb.first().is(":checked")) ? false : true);
                })
            </script>
            <?
        }
        else {
            echo $tpl->alert->info(NETCAT_MODULE_NETSHOP_PROMOTION_NO_COUPONS);
        }

        $UI_CONFIG->add_back_to_deals_button();
        $UI_CONFIG->add_generate_coupons_button();
        $UI_CONFIG->locationHash .= "($deal_type,$deal_id)";

        break;

    //--------------------------------------------------------------------------
    case 'edit':
        $form = $tpl->form(basename(__FILE__))->vertical();
        $form->add()->input('hidden', 'action', 'save');
        $form->add()->input('hidden', 'coupon_id', $coupon->get_id());

        $valid_till = $coupon->get('valid_till');
        $expires = ($valid_till != null);
        $expiration_row = $form->add_row(NETCAT_MODULE_NETSHOP_PROMOTION_GENERATED_COUPON_CODE_VALID_TILL);
        $expiration_row->select('expires', array(
                                0 => NETCAT_MODULE_NETSHOP_PROMOTION_GENERATED_COUPON_CODE_VALID_INDEFINITELY,
                                1 => NETCAT_MODULE_NETSHOP_PROMOTION_GENERATED_COUPON_CODE_VALID_TILL_DATE
                            ), $expires)->id('nc_netshop_promotion_coupons_edit_form_expires');
        $expiration_row->span("&nbsp;");
        $expiration_row->input('datetime', 'data[valid_till]', $coupon->get('valid_till'))
                       ->style('display: none')
                       ->id("nc_netshop_promotion_coupons_edit_form_valid_till");

        $max_usages = $coupon->get('max_usages');
        if ($max_usages == 0) { $max_usages = ''; }
        $form->add_row(NETCAT_MODULE_NETSHOP_PROMOTION_COUPON_CODE_MAX_USAGES)
             ->input('number', 'data[max_usages]', $max_usages)->small();

        $row = $form->add_row();
        $row->input('hidden', 'data[enabled]', '0');
        $row->checkbox('data[enabled]',
                       $coupon->get('enabled'),
                       NETCAT_MODULE_NETSHOP_PROMOTION_COUPON_IS_ENABLED)
            ->value('1');

        echo $form,
             '<script>
                $nc("#nc_netshop_promotion_coupons_edit_form_expires").change(function() {
                   var input = $("#nc_netshop_promotion_coupons_edit_form_valid_till");
                   if ($(this).val() == 1) { input.show(); } else { input.hide(); }
                }).change();
             </script>';

        $UI_CONFIG->add_save_and_cancel_buttons();
        $UI_CONFIG->locationHash = "module.netshop.promotion.coupon.edit($coupon_id)";
        break;

    //--------------------------------------------------------------------------
    case 'save':
        $data = (array)$input->fetch_post('data');
        if (!$input->fetch_post('expires') || !$data['valid_till']) {
            $data['valid_till'] = null;
        }
        else {
            $data['valid_till'] = date("Y-m-d H:i:s", strtotime($data['valid_till']));
        }
        foreach ($data as $k => $v) { $coupon->set($k, $v); }
        $coupon->save();

        nc_netshop_admin_helpers::redirect_to_index_action($index_params);
        break;
    //--------------------------------------------------------------------------
    case 'generate_ask':
        require './coupon/generate.inc.php';
        break;

    //--------------------------------------------------------------------------
    case 'generate_act':
        // "batch-less" coupon generation
        @set_time_limit(0);
        $batch = new nc_netshop_promotion_coupon_batch(array(
            'coupon_options' => $input->fetch_post('coupon_options'),
            'mail_options' => $input->fetch_post('mail_options'),
            'user_ids' => $input->fetch_post('send_to_users') ? $input->fetch_post('user_ids') : '',
            'num_codes_total' => $input->fetch_post('num_codes'),
        ));

        $result = $batch->process_all();
        if (!$result['success']) {
            echo $tpl->alert->error($result['error_message']);
            require './coupon/generate.inc.php';
        }
        else {
            $redirect_url = $input->fetch_post('redirect_url');
            if ($redirect_url) {
                ob_end_clean();
                header('Location: ' . $redirect_url);
                die;
            }
            else {
                nc_netshop_admin_helpers::redirect_to_index_action($index_params);
            }
        }
        break;

    //--------------------------------------------------------------------------
    case 'delete':
        $ids = (array)$input->fetch_post('coupon_ids');
//        foreach ($ids as $id) {
//            $coupon = new nc_netshop_promotion_coupon((int)$id);
//            $coupon->delete();
//        }
        $ids = join(',', array_map('intval', $ids));
        $db->query("DELETE FROM `Netshop_Coupon` WHERE `Coupon_ID` IN ($ids)");

        nc_netshop_admin_helpers::redirect_to_index_action($index_params);
        break;

    //--------------------------------------------------------------------------
    case 'toggle':
        if ($coupon) {
            $coupon->set('enabled', $input->fetch_post('enabled'))->save();
            nc_netshop_admin_helpers::redirect_to_index_action($index_params);
        }
        break;

    //--------------------------------------------------------------------------
    case 'export_csv':
        ob_end_clean();
        header("Content-type: text/csv");
        header("Content-disposition: attachment;filename=coupon_codes_{$deal_type}_{$deal_id}.csv");

        $limit = 5000;
        $offset = 0;
        $query = "SELECT `Code`, `MaxUsages`, `UsageCount`, `Created`, `ValidTill`, `Batch_ID`, `SentTo_User_ID`
                    FROM `Netshop_Coupon`
                   WHERE `Deal_Type`= '$deal_type'
                     AND `Deal_ID` = $deal_id
                     AND `Enabled` = 1
                     AND (`ValidTill` IS NULL OR `ValidTill` > NOW())
                     AND (`MaxUsages` = 0 OR `MaxUsages` > `UsageCount`)
                   ORDER BY `Created` DESC
                   LIMIT $limit OFFSET ";

        $first_row = true;
        while ($coupons = $db->get_results($query . $offset, ARRAY_A)) {
            if ($first_row) {
                echo join(';', array_keys($coupons[0])), "\n";
                $first_row = false;
            }

            foreach ($coupons as $c) {
                echo join(';', $c), "\n";
            }
            $offset += $limit;
        }
        die;
        break;
}

EndHtml();