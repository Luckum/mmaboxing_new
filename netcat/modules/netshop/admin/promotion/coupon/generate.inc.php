<?php

// "Generate coupon codes" form (for coupon.php action=generate_ask, generate_act)

if (!class_exists('nc_core')) { die; }

?>
<style>
    #nc_netshop_promotion_coupons_generate_form .user-filter-row {
        margin-bottom: 20px;
    }
    #nc_netshop_promotion_coupons_generate_form .user-filter-row .nc-netshop-condition-editor {
        margin: 8px 0;
    }
    #nc_netshop_promotion_coupons_generate_form .user-filter-row .filter-results {
        color: #646464;
        font-size: 12px;
        margin-left: 20px;
    }
</style>
<?php

$coupon_options = (array)$input->fetch_post('coupon_options');
$mail_options = (array)$input->fetch_post('mail_options');

/** @var nc_ui_form $form */
$form = $tpl->form('coupon.php')->vertical()
            ->id("nc_netshop_promotion_coupons_generate_form")
            ->style("display:none");

$form->add()->input('hidden', 'action', 'generate_act');
$form->add()->input('hidden', 'redirect_url', $input->fetch_get_post('redirect_url'));
// required for the parent script (coupon.php)
$form->add()->input('hidden', 'deal_type', $deal_type);
$form->add()->input('hidden', 'deal_id', $deal_id);
$form->add()->input('hidden', 'catalogue_id', $deal->get('catalogue_id'));

// required for the coupon generator
$form->add()->input('hidden', 'coupon_options[deal_type]', $deal_type);
$form->add()->input('hidden', 'coupon_options[deal_id]', $deal_id);
$form->add()->input('hidden', 'batch_size', 200);


$form->add_row()->checkbox('send_to_users',
                           $input->fetch_post('send_to_users'),
                           NETCAT_MODULE_NETSHOP_PROMOTION_COUPON_SEND_CODES_TO_USERS)
                ->value('1');

$form->add()->input('hidden', 'user_ids', $input->fetch_post('user_ids'));

$num_codes = (int)$input->fetch_post('num_codes');
$form->add_row(NETCAT_MODULE_NETSHOP_PROMOTION_NUMBER_OF_COUPONS_TO_GENERATE)->class_name("num-codes-row")
     ->input('number', 'num_codes', $num_codes ? $num_codes : 1)
     ->small();

$form->add_row(NETCAT_MODULE_NETSHOP_PROMOTION_USER_EMAIL_FIELD)->class_name("mail-options")
     ->select('mail_options[user_email_field]',
              $shop->mailer->get_user_email_fields(),
              @$mail_options_options['user_email_field']);
/* @todo user name ? */

$form->add()->div(
    "<div>" . NETCAT_MODULE_NETSHOP_PROMOTION_COUPON_USERS_SELECTION . "</div>" .
    $tpl->html->input('hidden', 'submitted_user_conditions', $input->fetch_post('user_conditions')) .
    "<div id='nc_netshop_promotion_coupons_form_user_conditions'></div>" .
    $tpl->btn('#', NETCAT_MODULE_NETSHOP_BUTTON_APPLY_FILTER)->class_name('apply-filter-button')->mini() .
    "<span class='filter-results'>" .
        "<span class='loading'>" . $tpl->icon('loading') . "</span>" .
        "<span class='loaded'>" .
          NETCAT_MODULE_NETSHOP_PROMOTION_COUPON_NUMBER_OF_USERS_SELECTED .
          "<a href='#' class='filter-results-count' title='" .
            htmlspecialchars(NETCAT_MODULE_NETSHOP_PROMOTION_COUPON_SHOW_SELECTED_USERS, ENT_QUOTES) .
          "'>0</a>" .
        "</span>" .
    "</span>"
)->class_name("user-filter-row");

// num_codes = 1
$single_code_row = $form->add_row(NETCAT_MODULE_NETSHOP_PROMOTION_GENERATED_COUPON_CODE)->class_name("single-code");
$single_code_row->input('text', 'coupon_options[code]', @$coupon_options['code'])->large()->attr('maxlength', 64);
$single_code_status_span = $single_code_row->span("&nbsp;&nbsp;")->class_name('code-status');
$single_code_status_span->span()->icon("status-success")->class_name('code-status-ok');
$single_code_status_span->span('&nbsp;')->icon("status-error")->class_name('code-status-error')
                        ->span()->class_name('code-error-message');

// num_codes > 1
$form->add_row(NETCAT_MODULE_NETSHOP_PROMOTION_GENERATED_COUPON_CODE_PREFIX)->class_name("multiple-codes")
     ->input('text', 'coupon_options[code_prefix]', @$coupon_options['code_prefix']);
$code_symbols_value = (isset($coupon_options['code_symbols']) ? $coupon_options['code_symbols'] : NETCAT_MODULE_NETSHOP_PROMOTION_GENERATED_COUPON_CODE_SYMBOLS_DEFAULT_VALUE);
$form->add_row(NETCAT_MODULE_NETSHOP_PROMOTION_GENERATED_COUPON_CODE_SYMBOLS)->class_name("multiple-codes")
     ->input('text', 'coupon_options[code_symbols]', $code_symbols_value)->xlarge();
$form->add_row(NETCAT_MODULE_NETSHOP_PROMOTION_GENERATED_COUPON_CODE_LENGTH)->class_name("multiple-codes")
     ->select('coupon_options[code_length]', array())
     ->attr('data-selected', @$coupon_options['code_length']);

// for any number of codes
$expiration_row = $form->add_row(NETCAT_MODULE_NETSHOP_PROMOTION_GENERATED_COUPON_CODE_VALID_TILL)->class_name("code-options");
$expiration_row->select('coupon_options[expires]', array(
                        0 => NETCAT_MODULE_NETSHOP_PROMOTION_GENERATED_COUPON_CODE_VALID_INDEFINITELY,
                        1 => NETCAT_MODULE_NETSHOP_PROMOTION_GENERATED_COUPON_CODE_VALID_TILL_DATE
                    ), @$coupon_options['expires']);
$expiration_row->span("&nbsp;");
$expiration_row->input('datetime', 'coupon_options[valid_till]', @$coupon_options['valid_till']);

$form->add_row(NETCAT_MODULE_NETSHOP_PROMOTION_GENERATED_COUPON_MAX_USAGES)->class_name("code-options")
     ->input('number', 'coupon_options[max_usages]', @$coupon_options['max_usages'])->small();

// MAIL OPTIONS
$form->add_row(NETCAT_MODULE_NETSHOP_MAILER_TEMPLATE_PARENT_TEMPLATE)->class_name("mail-options")
     ->select('mail_options[parent_template_id]',
              $shop->mailer->get_template_list($deal->get('catalogue_id')),
              @$mail_options_options['parent_template_id']);

$form->add_row(NETCAT_MODULE_NETSHOP_MAILER_TEMPLATE_SUBJECT)->class_name("mail-options")
     ->input('text', 'mail_options[subject]', @$mail_options_options['subject'])->xlarge();

$form->add_row(NETCAT_MODULE_NETSHOP_MAILER_TEMPLATE_BODY)->class_name("mail-options")
     ->textarea('mail_options[body]', @$mail_options_options['body'])
     ->attr('id', 'nc_netshop_promotion_coupons_generate_form_mail_body')
     ->class_name("no_cm")->xlarge();

$form->add()->div(
    $tpl->btn('#', NETCAT_MODULE_NETSHOP_PROMOTION_PREVIEW_EMAIL)->class_name('preview-message-button')->mini()
)->class_name('mail-options');

$form->add()->div("&nbsp;");

$netshop = nc_netshop::get_instance($catalogue_id);
nc_netshop_condition_admin_helpers::include_condition_editor_js();
nc_netshop_mailer_admin_helpers::include_template_editor_js($netshop);

echo "<script src='coupon/generate.js'></script>",
     $form;

?>
<!-- user filter dialog 'template' -->
<div id="nc_netshop_coupon_user_filter_result_dialog_template" style="display: none">
    <div class="nc_admin_form_menu">
        <h2><?=NETCAT_MODULE_NETSHOP_PROMOTION_COUPON_USERS_SELECTION ?></h2>
        <?php /* @TODO todo REMOVE THIS JUNK */ ?>
        <div class="slider_block_2"><ul><li></li></ul></div>
        <div class="nc_admin_form_menu_hr">&nbsp;</div>
    </div>
    <div class="nc_admin_form_body"><div class="content"></div></div>
    <div class="nc_admin_form_buttons">
        <button class="nc_admin_metro_button_cancel nc-btn nc--blue nc--bordered nc--right"><?=NETCAT_MODULE_NETSHOP_BUTTON_CLOSE_DIALOG ?></button>
    </div>
</div>

<!-- batch saving dialog 'template' -->
<div id="nc_netshop_coupon_batch_generation_modal_template" style="display: none">
    <style><?php /* @TODO move styles to somewhere they’re appropriate */ ?>
        #nc_netshop_coupon_batch_generation_modal {
            padding: 30px 30px 30px 14px;
            width: 500px; height: 100px;
        }

        #nc_netshop_coupon_batch_generation_modal .info h2 {
            margin-bottom: 12px;
        }

        #nc_netshop_coupon_batch_generation_modal .info {
            margin-bottom: 24px;
        }

        #nc_netshop_coupon_batch_generation_modal .step {
            height: 36px;
            display: none;
        }
        #nc_netshop_coupon_batch_generation_modal .step > * {
            box-sizing: border-box;
            -moz-box-sizing: border-box;
            -webkit-box-sizing: border-box;
        }
        #nc_netshop_coupon_batch_generation_modal .step1 {
            display: block;
        }

        #nc_netshop_coupon_batch_generation_modal .step .label {
            width: 25%;
            float: left;
            padding-right: 20px;
            text-align: right;
            vertical-align: middle;
            padding-top: 3px;
        }

        #nc_netshop_coupon_batch_generation_modal .step .label,
        #nc_netshop_coupon_batch_generation_modal .step .results .done {
            padding-top: 3px;
        }

        #nc_netshop_coupon_batch_generation_modal .step .results {
            width: 70%;
            float: left;
        }
        #nc_netshop_coupon_batch_generation_modal .step .results .nc-progress {
            margin: 0;
        }
        #nc_netshop_coupon_batch_generation_modal .step .results .done {
            display: none;
        }
        #nc_netshop_coupon_batch_generation_modal .error {
            display: none;
        }
        #nc_netshop_coupon_batch_generation_modal .error .message {
            padding: 10px 0;
            max-height: 200px;
            overflow: auto;
        }

    </style>
    <div class="container">
        <div class="info">
            <h2><?=NETCAT_MODULE_NETSHOP_PROMOTION_COUPON_GENERATION_TITLE ?></h2>
            <div><?=NETCAT_MODULE_NETSHOP_PROMOTION_COUPON_GENERATION_PLEASE_WAIT ?></div>
        </div>
        <div class="step step1">
            <div class="label"><?=NETCAT_MODULE_NETSHOP_PROMOTION_COUPON_GENERATION_STEP_1 ?></div>
            <div class="results">
                <div class="nc-progress"><div class="nc-progress-bar"></div></div>
                <div class="done"><?=NETCAT_MODULE_NETSHOP_PROMOTION_COUPON_GENERATION_STEP_FINISHED ?></div>
            </div>
        </div>
        <div class="step step2">
            <div class="label"><?=NETCAT_MODULE_NETSHOP_PROMOTION_COUPON_GENERATION_STEP_2 ?></div>
            <div class="results">
                <div class="nc-progress"><div class="nc-progress-bar"></div></div>
                <div class="done"><?=NETCAT_MODULE_NETSHOP_PROMOTION_COUPON_GENERATION_STEP_FINISHED ?></div>
            </div>
        </div>
        <div class="error">
            <div class="caption"><?=NETCAT_MODULE_NETSHOP_PROMOTION_COUPON_GENERATION_ERROR_CAPTION ?></div>
            <div class="message"></div>
            <div class="close"><a href="javascript:$nc.modal.close()"><?=NETCAT_MODULE_NETSHOP_PROMOTION_COUPON_GENERATION_DIALOG_CLOSE ?></a></div>
        </div>
    </div>
<!--    <div class="nc_admin_form_buttons">-->
<!--        <button class="nc_admin_metro_button_cancel nc-btn nc--blue nc--bordered nc--right">--><?//=NETCAT_MODULE_NETSHOP_BUTTON_CLOSE_DIALOG ?><!--</button>-->
<!--    </div>-->
</div>
<?php

// ---
$UI_CONFIG->add_save_and_cancel_buttons(NETCAT_MODULE_NETSHOP_PROMOTION_CREATE_COUPONS);
$UI_CONFIG->locationHash = "module.netshop.promotion.coupon.generate($deal_type,$deal_id)";
