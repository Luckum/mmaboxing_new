<?php
/* $Id: nc_minishop_admin.class.php 5240 2011-08-30 10:04:15Z denis $ */

class nc_minishop_admin {

    protected $db, $UI_CONFIG;
    protected $MODULE_FOLDER, $MODULE_PATH, $ADMIN_TEMPLATE;
    protected $settings;

    public function __construct() {
        // system superior object
        $nc_core = nc_Core::get_object();

        // global variables
        global $UI_CONFIG;

        // global variables to internal
        $this->db = &$nc_core->db;
        $this->UI_CONFIG = $UI_CONFIG;
        $this->ADMIN_TEMPLATE = $nc_core->ADMIN_TEMPLATE;
        $this->MODULE_FOLDER = $nc_core->MODULE_FOLDER;
        $this->MODULE_PATH = str_replace($nc_core->DOCUMENT_ROOT, "", $nc_core->MODULE_FOLDER) . "minishop/";

        $this->settings = $this->get_all_settings();
        //$this->create_module_settings();
    }

    public function get_mainsettings_url() {
        return "#module.minishop.settings";
    }

    /**
     * Показ информации о всех заказах
     * @global <type> $nc_minishop
     */
    public function info_show() {
        $db = nc_Core::get_object()->db;
        global $nc_minishop;
        $html = '';

        $cname = 'MinishopStatus';
        $class_id = $nc_minishop->order_class_id();
        $url = $nc_minishop->order_url();

        // возможные статусы заказов
        $res = $db->get_results("SELECT `" . $cname . "_ID` AS `id`, 	`" . $cname . "_Name` AS `name` FROM `Classificator_" . $cname . "` WHERE `Checked` = 1 ORDER BY `" . $cname . "_Priority`", ARRAY_A);
        foreach ($res as $v) {
            $order_status[$v['id']] = $v['name'];
        }

        // выборка за текущий день
        $res = $db->get_results("SELECT COUNT(`Message_ID`) as `cnt`, `Status` FROM `Message" . $class_id . "` WHERE UNIX_TIMESTAMP(`Created`) > UNIX_TIMESTAMP(CURDATE()) GROUP BY `Status`", ARRAY_A);
        $stat['today'] = array();
        if ($res)
            foreach ($res as $v) {
                $stat['today'][$v['Status']] = $v['cnt'];
            }

        // выборка за предыдущий день
        $stat['yesterday'] = array();
        $res = $db->get_results("SELECT COUNT(`Message_ID`) as `cnt`, `Status` FROM `Message" . $class_id . "` WHERE UNIX_TIMESTAMP(`Created`) BETWEEN UNIX_TIMESTAMP(CURDATE()-86400) and UNIX_TIMESTAMP(CURDATE()) GROUP BY `Status`", ARRAY_A);
        if ($res)
            foreach ($res as $v) {
                $stat['yesterday'][$v['Status']] = $v['cnt'];
            }

        // выборка за текущий месяц
        $stat['month'] = array();
        $res = $db->get_results("SELECT COUNT(`Message_ID`) as `cnt`, `Status` FROM `Message" . $class_id . "` WHERE MONTH(`Created`) = MONTH(NOW()) AND YEAR(`Created`) = YEAR(NOW()) GROUP BY `Status`", ARRAY_A);
        if ($res)
            foreach ($res as $v) {
                $stat['month'][$v['Status']] = $v['cnt'];
            }

        // выборка за предыдущий
        $stat['lastmonth'] = array();
        if (date("m") == 1) {
            $month_where = "12";
            $year_where = "YEAR(NOW()) - 1";
        } else {
            $month_where = "MONTH(NOW())-1";
            $year_where = "YEAR(NOW())";
        }
        $res = $db->get_results("SELECT COUNT(`Message_ID`) as `cnt`, `Status` FROM `Message" . $class_id . "` WHERE MONTH(`Created`) = " . $month_where . " AND YEAR(`Created`) = " . $year_where . " GROUP BY `Status`", ARRAY_A);
        if ($res)
            foreach ($res as $v) {
                $stat['lastmonth'][$v['Status']] = $v['cnt'];
            }

        // все
        $stat['total'] = array();
        $res = $db->get_results("SELECT COUNT(`Message_ID`) as `cnt`, `Status` FROM `Message" . $class_id . "` GROUP BY `Status`", ARRAY_A);
        if ($res)
            foreach ($res as $v) {
                $stat['total'][$v['Status']] = $v['cnt'];
            }

        $html.= '<fieldset><legend>' . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_INFO_TAB_TABLENAME . '</legend>';
        $html.= '<table cellspacing="1" cellpadding="5" class="admin_table stat">';
        $html.= '<col style="width: 25%;"><col style="width: 15%;"><col style="width: 15%;"><col style="width: 15%;"><col style="width: 15%;"><col style="width: 15%;">';
        $html.= '<tbody>';
        // шапка
        $html.= '<tr><th></th>';
        foreach ($order_status as $v) {
            $html .= '<th>' . $v . '</th>';
        }
        $html .= '<th>' . NETCAT_MODELE_MINISHOP_ADMIN_TEMPLATE_INFO_TAB_TOTALCOL . '</th></tr>';

        // каждая строка
        foreach ($stat as $type => $v) {
            $html.= '<tr><td class="type">' . constant("NETCAT_MODELE_MINISHOP_ADMIN_TEMPLATE_INFO_TAB_" . strtoupper($type) . "ROW") . '</td>';
            $s = 0;
            foreach ($order_status as $status_id => $status) {
                $s += $stat[$type][$status_id];
                if ($type <> 'total' || !$stat[$type][$status_id]) {
                    $html .= '<td class="info">' . ($stat[$type][$status_id] + 0) . '</td>';
                } else {
                    $html .= '<td class="info"><a target="_blank" href="' . $url . '?srchPat[0]=' . $status_id . '"><b>' . ($stat[$type][$status_id] + 0) . '</b></a></td>';
                }
            }
            // колонка "всего"
            if ($type <> 'total' || !$s) {
                $html .= '<td class="info"><b>' . ($s + 0) . '</b></td></tr>';
            } else {
                $html .= '<td class="info"><a target="_blank" href="' . $url . '"><b>' . $s . '</b></td></tr>';
            }
        }

        $html.= '</tbody></table></fieldset>';

        echo $html;
    }

    public function info_save() {
        return;
    }

    /**
     * Вкладка "Общие настройки"
     */
    public function settings_show() {
        global $UI_CONFIG;
        $UI_CONFIG->add_settings_toolbar();
        $nc_core = nc_Core::get_object();
        $payment_clft = $nc_core->db->get_var("SELECT `Classificator_ID` FROM `Classificator` WHERE `Table_Name` = 'MinishopPayment'");
        $delivery_clft = $nc_core->db->get_var("SELECT `Classificator_ID` FROM `Classificator` WHERE `Table_Name` = 'MinishopDelivery'");
        $html = '';

        $html.="<form method='post' action='admin.php' id='MainSettigsForm' style='padding:0; margin:0;'>\n" .
                "<input type='hidden' name='view' value='settings' />\n" .
                "<input type='hidden' name='act' value='save' />\n" .
                "<fieldset>\n" .
                "<legend>\n" .
                "" . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_SETTINGS_TAB_TITLE . "\n" .
                "</legend>\n" .
                "<div style='margin:10px 0; _padding:0;'>
        " . nc_admin_input(NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_SETTINGS_TAB_SHOPNAME, 'shopname', $this->settings, 40) . "
      </div>
      <div style='margin:10px 0; _padding:0;'>\n
        " . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_SETTINGS_TAB_SHOP_CURRENCY . "<br/>
        <input id='currency0' type='radio' name='currency' value='0' " . (!$this->settings['currency'] ? " checked='checked' " : "") . " />
        <label for='currency0'>" . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_SETTINGS_TAB_CURRENCY_RUB . "</label><br/>
        <input id='currency1' type='radio' name='currency' value='1' " . ($this->settings['currency'] ? " checked='checked' " : "") . " />
        <label for='currency1'>" . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_SETTINGS_TAB_CURRENCY_ALT . "</label>,
        " . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_SETTINGS_TAB_CURRENCY_ALT_NAME . ": <input type='text' name='currency_name' value='" . $this->settings['currency_name'] . "' />
        </div>\n" .
                "</fieldset>\n" .
                "<div style='margin:10px 0; _padding:0;'>\n
        <fieldset>\n" .
                "<legend>\n" .
                "" . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_SETTINGS_TAB_AUTHORIZE_ONORDER . "\n" .
                "</legend>\n" .
                "<div style='margin:10px 0; _padding:0;'>\n
        <input id='auth0' type='radio' name='auth' value='0' " . (!$this->settings['auth'] ? " checked='checked' " : "") . " />
        <label for='auth0'>" . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_SETTINGS_TAB_AUTHORIZE_NO . "</label><br/>
        <input id='auth1' type='radio' name='auth' value='1' " . ($this->settings['auth'] == 1 ? " checked='checked' " : "") . " />
        <label for='auth1'>" . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_SETTINGS_TAB_AUTHORIZE_OFFER . "</label><br/>
        <input id='auth2' type='radio' name='auth' value='2' " . ($this->settings['auth'] == 2 ? " checked='checked' " : "") . " />
        <label for='auth2'>" . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_SETTINGS_TAB_AUTHORIZE_YES . "</label><br/>
        </div>\n" .
                "</fieldset>\n" .
                "<fieldset>\n" .
                "<legend>\n" .
                "" . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_SETTINGS_TAB_DELIVERY_PAYMENT . "\n" .
                "</legend>\n" .
                nc_admin_checkbox(NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_SETTINGS_TAB_DELIVERY . ' (<a target="_blank" href="' . $nc_core->ADMIN_PATH . '#classificator.edit(' . $delivery_clft . ')">' . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_SETTINGS_TAB_DELIVERY_SETTINGS . '</a>)', 'delivery_allow', $this->settings['delivery_allow']) .
                nc_admin_checkbox(NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_SETTINGS_TAB_PAYMENT . ' (<a target="_blank" href="' . $nc_core->ADMIN_PATH . '#classificator.edit(' . $payment_clft . ')">' . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_SETTINGS_TAB_DELIVERY_SETTINGS . '</a>)', 'payment_allow', $this->settings['payment_allow']) .
                "</fieldset>\n" .
                "<fieldset>\n" .
                "<legend>\n" .
                "" . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_SETTINGS_TAB_NOTIFY_TITLE . "\n" .
                "</legend>\n" .
                nc_admin_checkbox(NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_SETTINGS_TAB_NOTIFYCLIENT, 'notify_mail', $this->settings) .
                nc_admin_input(NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_SETTINGS_TAB_SHOPEMAIL . " (" . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_SETTINGS_TAB_DEFAULT_EMAIL . " " . $nc_core->get_settings('SpamFromEmail') . ")", 'shop_email', $this->settings) .
                nc_admin_input(NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_SETTINGS_TAB_ADMINEMAIL . " (" . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_SETTINGS_TAB_DEFAULT_EMAIL . " " . $nc_core->get_settings('SpamFromEmail') . ")", 'admin_email', $this->settings) .
                "</fieldset>\n" .
                "</form>\n";

        // admin buttons
        $UI_CONFIG->actionButtons[] = array(
                "id" => "submit",
                "caption" => NETCAT_MODULE_MINISHOP_ADMIN_SETTINGS_SAVE,
                "action" => "mainView.submitIframeForm('MainSettingsForm')"
        );

        echo $html;
    }

    public function settings_save() {
        $nc_core = nc_Core::get_object();
        $params = array('shopname', 'currency', 'currency_name', 'auth',
                'delivery_allow', 'payment_allow', 'notify_mail',
                'shop_email', 'admin_email');
        foreach ($params as $v) {
            $nc_core->set_settings($v, $nc_core->input->fetch_get_post($v), 'minishop');
        }
        // доступ на добавление заказа
        $aid = ( $nc_core->input->fetch_get_post('auth') == nc_minishop::AUTH_REQUIRE ) ? 2 : 1;
        $class_id = intval($this->settings['order_class_id']);
        $nc_core->db->query("UPDATE `Sub_Class` SET `Write_Access_ID` = '" . $aid . "' WHERE `Class_ID` = '" . $class_id . "' OR `Class_Template_ID` = '" . $class_id . "'  ");

        $this->settings = $this->get_all_settings();
    }

    public function discount_show() {
        global $UI_CONFIG, $nc_core;
        $UI_CONFIG->add_settings_toolbar();

        $d = array();
        $d_str = $this->settings['discounts'];
        if ($d_str)
            $d = unserialize($d_str);

        echo "
      <div id='value_null' class='status_error'>" . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_DISCOUNT_TAB_DISCOUNT_ERROR_VALUE . "</div>
      <div id='error_from_to' class='status_error'>" . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_DISCOUNT_TAB_DISCOUNT_ERROR_LIMIT . "</div>
      <div id='error_range' class='status_error'>" . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_DISCOUNT_TAB_DISCOUNT_ERROR_LOWER . "</div>
      <div id='error_overlap' class='status_error'>" . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_DISCOUNT_TAB_DISCOUNT_ERROR_RANGE . "</div>
      <form method='post' action='admin.php' id='adminForm' class='nc-form'>
      <input type='hidden' name='view' value='discount' />
      <input type='hidden' name='act' value='save' />
    <div style='padding-bottom: 5px; padding-top: 5px;'>
        " . nc_admin_checkbox(NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_DISCOUNT_TAB_DISCOUNT_ONORDERSUM, 'discount_enabled', $this->settings) . "
    </div>
    <div id='all'>
        <div id='discounts'></div>
    <div>
        <span id='add' style='color: #1A87C2;'>
         " . NETCAT_MODULE_SEARCH_ADMIN_ADD . "
        </span>
     </div>
     </div>
     <style> div.icon_delete { position: relative;top: -3px; } </style>";
        ?>
        <script type="text/javascript">
            function check_form () { // elem.css('border', '2pt solid red');
                jQuery('.status_error').hide();
                var params = {adminact: 'check_discount'};
                jQuery('#adminForm').find(".val").each(function(){
                    var elem = jQuery(this);
                    elem.removeClass('error_input');
                    params[elem.attr("name")] = elem.val();
                });

                jQuery.ajax({ url: '<?php echo $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH; ?>modules/minishop/index.php',
                    async: false,
                    data: params,
                    dataType: 'json',
                    type: 'POST',
                    success: function(data) {
                        if ( data.status == 'ok') {
                            document.getElementById('adminForm').submit();
                            return true;
                        }
                        var i;
                        for ( i = 0; i < data.res.length; i++) {
                            if ( data.res[i].type == 1 ) {
                                jQuery(document.getElementById("value["+data.res[i].id+"]")).addClass('error_input');
                                jQuery("#value_null").show();
                            }
                            if ( data.res[i].type == 2 ) {
                                jQuery(document.getElementById("from["+data.res[i].id+"]")).addClass('error_input');
                                jQuery(document.getElementById("to["+data.res[i].id+"]")).addClass('error_input');
                                jQuery("#error_from_to").show();
                            }
                            if ( data.res[i].type == 3 ) {
                                jQuery(document.getElementById("from["+data.res[i].id+"]")).addClass('error_input');
                                jQuery(document.getElementById("to["+data.res[i].id+"]")).addClass('error_input');
                                jQuery("#error_range").show();
                            }
                            if ( data.res[i].type == 4 ) {
                                jQuery("#error_overlap").show();
                            }
                        }
                    }
                });
            }

            $nc("#discount_enabled").on('change', function() {
                var $this = $nc(this);
                var $form = $nc('#all');

                if ($this.is(':checked')) {
                    $form.show();
                } else {
                    $form.hide();
                }

                return true;
            });

            (function(discounts) {
                var tpl = '<div class="discount index%x">' +
                    '<?php echo NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_DISCOUNT_TAB_ORDER_SUM . " " . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_DISCOUNT_TAB_ORDER_FROM; ?>' +
                    '<input type="text" id="from[%x]" name="from[%x]" class="from val" size="5" value="0"/>' +
                    '<?php echo NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_DISCOUNT_TAB_ORDER_TO; ?>' +
                    '<input type="text" id="to[%x]" name="to[%x]" class="to val" size="5" value="0"/>' +
                    ' &#8212;&nbsp; <?php echo NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_DISCOUNT_TAB_DISCOUNTSUM; ?> ' +
                    '<input type="text" id="value[%x]" name="value[%x]" class="value val" size="3" value="0"/>' +
                    ' % ' +
                    "<?= nc_admin_img("delete", NETCAT_MODULE_SEARCH_ADMIN_DELETE) ?>" +
                    '</div>';
                var last = 0;


                function add (index, disc) {
                    var div = jQuery(tpl.replace(/%x/g, index));
                    div.find("input.from").val(disc.from);
                    div.find("input.to").val(disc.to);
                    div.find("input.value").val(disc.value);
                    div.find("div.icon_delete").click(function() { jQuery(this).parent().remove(); });
                    div.appendTo('#discounts');
                }

                jQuery('#add').click(function() { add(last++, { from: 0, to: 0, value: 0} )});
                for (var i in discounts) { add(last++, discounts[i]); }

            })(<?= nc_array_json($d) ?>);

            jQuery("#discount_enabled").change();
        </script>
        <?php
        echo "</form>";
        $UI_CONFIG->actionButtons[] = array(
                "id" => "submit",
                "caption" => NETCAT_MODULE_MINISHOP_ADMIN_SETTINGS_SAVE,
                "action" => "document.getElementById('mainViewIframe').contentWindow.check_form()"
        );
    }

    public function discount_save() {
        $nc_core = nc_Core::get_object();
        $nc_core->set_settings('discount_enabled', $nc_core->input->fetch_get_post('discount_enabled'), 'minishop');
        $d = $this->make_discount();
        if ($d['status'] == 'error') {
            return false;
        }
        $nc_core->set_settings('discounts', serialize($d['res']), 'minishop');
        $this->settings = $this->get_all_settings();
        //dump($a);
    }

    public function make_discount() {
        $nc_core = nc_Core::get_object();
        $discount = array();
        $error = array();

        $values = $nc_core->input->fetch_get_post('value');
        $froms = $nc_core->input->fetch_get_post('from');
        $toes = $nc_core->input->fetch_get_post('to');

        if (!empty($values))
            foreach ($values as $i => $value) {
                $value = intval($value);
                $from = intval($froms[$i]);
                $to = intval($toes[$i]);
                $error_flag = false;
                if (!$value) {
                    $error[] = array('type' => 1, 'id' => $i);
                    $error_flag = true;
                }
                if (!$from && !$to || $from < 0 || $to < 0) {
                    $error[] = array('type' => 2, 'id' => $i);
                    $error_flag = true;
                }
                if ($from > $to) {
                    $error[] = array('type' => 3, 'id' => $i);
                    $error_flag = true;
                }
                if (!$error_flag) {
                    foreach ($discount as $d) {
                        $from1 = $d['from'];
                        $to1 = $d['to'];
                        if ($from < $from1 && $to > $from1 || $from < $to1 && $to > $to1 ||
                                $from1 < $from && $to1 > $from || $from1 < $to && $to1 > $to) {
                            $error[] = array('type' => 4, 'id' => $i);
                            $error_flag = true;
                        }
                    }
                }

                if (!$error_flag) {
                    $discount[] = array('from' => $from, 'to' => $to, 'value' => $value);
                }
            }


        return empty($error) ? array('status' => 'ok', 'res' => $discount) : array('status' => 'error', 'res' => $error);
    }

    public function display_show() {
        global $UI_CONFIG;
        $UI_CONFIG->add_settings_toolbar();
        $result = '';
        $html = '';

        $html.="<form method='post' action='admin.php' id='DisplaySettingsForm' style='padding:0; margin:0;'>\n" .
                "<input type='hidden' name='view' value='display' />\n" .
                "<input type='hidden' name='act' value='save' />\n" .
                "<fieldset>\n" .
                "<legend>\n" .
                "" . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_DISPLAY_TAB_CARTPUT_ACTION . "\n" .
                "</legend>\n" .
                "<div style='margin:10px 0; _padding:0;'>\n" .
                "<input id='ajax1' type='radio' name='ajax' value='1'" . ($this->settings['ajax'] ? " checked" : "") . " />
      <label for='ajax1'>" . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_DISPLAY_TAB_CARTPUT_NORELOAD . "</label>
       <br/>
       <div style='padding-left: 15px;'>
         <input id='notify2' type='radio' name='notify' value='" . nc_minishop::NOTIFY_DIV . "' " . ( $this->settings['notify'] == nc_minishop::NOTIFY_DIV ? "checked='checked'" : "") . " />
         <label for='notify2'>" . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_DISPLAY_TAB_CARTPUT_NOTIFY_DIV . "</label><br/>
         <input id='notify1' type='radio' name='notify' value='" . nc_minishop::NOTIFY_ALERT . "' " . ( $this->settings['notify'] == nc_minishop::NOTIFY_ALERT ? "checked='checked'" : "") . " />
         <label for='notify1'>" . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_DISPLAY_TAB_CARTPUT_NOTIFY_ALERT . "</label><br/>
         <input id='notify0' type='radio' name='notify' value='" . nc_minishop::NOTIFY_NONE . "' " . ( $this->settings['notify'] == nc_minishop::NOTIFY_NONE ? "checked='checked'" : "") . "  />
         <label for='notify0'>" . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_DISPLAY_TAB_CARTPUT_NOTIFY_NONE . "</label><br/>
       </div>
      <input id='ajax0' type='radio' name='ajax' value='0'" . (!$this->settings['ajax'] ? " checked" : "") . " />
      <label for='ajax0'>" . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_DISPLAY_TAB_CARTPUT_RELOAD . "</label>" .
                "</div>\n" .
                "</fieldset>" .
                "<fieldset>\n" .
                "<legend>\n" .
                "" . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_DISPLAY_TAB_PUT_BUTTON . "\n" .
                "</legend>\n" .
                "<div style='margin:10px 0; padding:0;'>\n";
        $c = array(nc_minishop::PUT_TEXT, nc_minishop::PUT_IMG, nc_minishop::PUT_TEXTIMG, nc_minishop::PUT_BUTTON, nc_minishop::PUT_FORM);
        foreach ($c as $v) {
            $html .= "<input id='putbutton" . $v . "' type='radio' name='put_button' value='" . $v . "' " . ( $this->settings['put_button'] == $v ? "checked='checked'" : "") . " />
         <label for='putbutton" . $v . "'>" . constant("NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_DISPLAY_TAB_PUT_AS" . $v) . "</label><br/>";
        }
        $html .= "</div>";
        $html .= "<legend style='padding-top: 0px;'>" . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_DISPLAY_TAB_ALREADY_INCART . "</legend>
                        <div style='margin:10px 0; padding:0;'>
                        <input id='already_in_cart0' type='radio' name='already_in_cart' value='0' " . (!$this->settings['already_in_cart'] ? "checked='checked'" : "") . " />
                        <label for='already_in_cart0'>" . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_DISPLAY_TAB_ALREADY_ASTEXT . "</label><br/>
                        <input id='already_in_cart1' type='radio' name='already_in_cart' value='1' " . ( $this->settings['already_in_cart'] ? "checked='checked'" : "") . " />
                        <label for='already_in_cart1'>" . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_DISPLAY_TAB_ALREADY_ASIMG . "</label><br/>";
        $html .= "</div>";
        $html .= nc_admin_checkbox(NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_DISPLAY_TAB_ORDERFORM_INLINE, 'orderform_inline', $this->settings['orderform_inline']) .
                 nc_admin_checkbox(NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_DISPLAY_TAB_ORDERFORM_USECAPTCHA, 'orderform_captcha', $this->settings['orderform_captcha']);

        foreach ($this->settings['file_settings'] as $type => $settings) {
			$pref = ($type == 'old' ? '' : $type.'_');
            $html .= "
                <fieldset>
                    <legend><strong>" . constant("TITLE_" . strtoupper($type)) . "</strong></legend><br/>
                    <div style='margin:10px 0; padding:0;'>
                        " . nc_admin_textarea(NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_DISPLAY_TAB_PUT_ALTERNATE . " (<a href='#' onclick='gen(\"put_button_alternate\",jQuery(\"input[name=\\\"put_button\\\"]:checked\").val(), \"{$pref}\");return false;'>" . NETCAT_MODULE_MINISHOP_ADMIN_SETTINGS_GENERATE_TEMPLATE . "</a>)", $pref . 'put_button_alternate', $settings['put_button_alternate'], 1) . "
                        " . nc_admin_textarea(NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_DISPLAY_TAB_MASSPUT_ALTERNATE . " (<a href='#' onclick='gen(\"mass_put_alternate\", 0, \"{$pref}\");return false;'>" . NETCAT_MODULE_MINISHOP_ADMIN_SETTINGS_GENERATE_TEMPLATE . "</a>)", $pref . 'mass_put_alternate', $settings['mass_put_alternate'], 1) . "
                        " . nc_admin_textarea(NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_DISPLAY_TAB_NO_PRICE, $pref . 'no_price_text', $settings['no_price_text'], 1) . "
                    </div>
                </fieldset>

                <fieldset>
                    <legend style='padding-top: 0px;'>" . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_DISPLAY_TAB_ALREADY_INCART . "</legend>
                    <div style='margin:10px 0; padding:0;'>
                        " . nc_admin_textarea(NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_DISPLAY_TAB_ALREADY_ALTERNATE . " (<a href='#' onclick='gen(\"already_in_cart_alternate\",jQuery(\"input[name=\\\"already_in_cart\\\"]:checked\").val(), \"{$pref}\");return false;'>" . NETCAT_MODULE_MINISHOP_ADMIN_SETTINGS_GENERATE_TEMPLATE . "</a>)", $pref . 'already_in_cart_alternate', $settings['already_in_cart_alternate'], 1) . "
                    </div>
                </fieldset>

                <fieldset>
                    <legend style='padding-top: 0px;'>" . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_DISPLAY_TAB_CART . "</legend>
                    <div style='margin:10px 0; padding:0;'>
                        " . nc_admin_textarea(NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_DISPLAY_TAB_CART_NONEMPTY . " (<a href='#' onclick='gen(\"cart_full\", 0, \"{$pref}\");return false;'>" . NETCAT_MODULE_MINISHOP_ADMIN_SETTINGS_GENERATE_TEMPLATE . "</a>)", $pref . 'cart_full', $settings['cart_full'], 1) . "
                        " . nc_admin_textarea(NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_DISPLAY_TAB_CART_EMPTY . " (<a href='#' onclick='gen(\"cart_empty\", 0, \"{$pref}\");return false;'>" . NETCAT_MODULE_MINISHOP_ADMIN_SETTINGS_GENERATE_TEMPLATE . "</a>)", $pref . 'cart_empty', $settings['cart_empty'], 1) . "
                        " . nc_admin_textarea(NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_DISPLAY_TAB_CART_AFTERTEXT, $pref . 'cart_after', $settings['cart_after'], 1) . "
                    </div>
                </fieldset>

                <fieldset>
                    <legend style='padding-top: 0px;'>" . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_DISPLAY_TAB_NOTIFY . "</legend>
                    " . nc_admin_textarea(NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_DISPLAY_TAB_NOTIFY_ALERT_TEXT, $pref . 'notify_alert', $settings['notify_alert'], 1) . "
                    " . nc_admin_textarea(NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_DISPLAY_TAB_NOTIFY_DIV_TEXT, $pref . 'notify_div', $settings['notify_div'], 1) . "
                </fieldset>
                <fieldset>
                    <legend style='padding-top: 0px;'>" . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_DISPLAY_TAB_ORDERFORM . "</legend>
                    " . nc_admin_textarea(NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_DISPLAY_TAB_ORDERFORM_AFTERTEXT, $pref . 'orderfrom_text', $settings['orderfrom_text'], 1) . "
                </fieldset>
                <br />";
        }

        $html .= "</form>";

        // admin buttons
        $UI_CONFIG->actionButtons[] = array(
                "id" => "submit",
                "caption" => NETCAT_MODULE_MINISHOP_ADMIN_SETTINGS_SAVE,
                "action" => "mainView.submitIframeForm('DisplaySettingsForm')"
        );

        echo $html;
    }

    public function display_save() {
		$nc_core = nc_Core::get_object();
		$params = array(
			'put_button_alternate',
			'mass_put_alternate',
			'no_price_text',
			'already_in_cart_alternate',
			'cart_full',
			'cart_empty',
			'cart_after',
			'notify_alert',
			'notify_div',
			'orderfrom_text',
		);
		$data = array();

		foreach ($params as $v) {
			$data[$v] = $nc_core->input->fetch_get_post($v);
		}

		$module_editor = new nc_module_editor();
		$module_editor->load('minishop')->save($_POST);

		$params = array_merge($params, array(
			'put_button',
			'already_in_cart',
			'notify',
			'ajax',
			'orderform_inline',
			'orderform_captcha'
		));

		foreach ($params as $v) {
			$nc_core->set_settings($v, $nc_core->input->fetch_get_post($v), 'minishop');
		}

		$this->settings = $this->get_all_settings();
		return true;
    }

    public function mails_show() {
        global $UI_CONFIG;
        $UI_CONFIG->add_settings_toolbar();
        $this->settings = $this->get_all_settings();

        $result = "<form action='admin.php' method='post' enctype='multipart/form-data'>
               <input type='hidden' name='view' value='mails' />
               <input type='hidden' name='act' value='save' /> ";

        // письмо покупателю
        $result .= "<fieldset><legend>" . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_MAILS_TAB_CLIENTMAIL . "</legend>";
        $result .= nc_admin_input(NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_MAILS_TAB_ADMINMAIL_SUBJECT, 'mail_subject_customer', $this->settings, 0, 'width:100%');
        $result .= nc_admin_textarea(NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_MAILS_TAB_ADMINMAIL_TEXT, 'mail_body_customer', $this->settings, 1, 0, 'height: 15em;');
        $result .= nc_admin_checkbox(NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_MAILS_TAB_ADMINMAIL_HTML, 'mail_ishtml_customer', $this->settings, 1);
        $result .= "</fieldset>";
        $result .= nc_mail_attachment_form('minishop_customer');

        // письмо админку
        $result .= "<fieldset><legend>" . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_MAILS_TAB_ADMINMAIL . "</legend>";
        $result .= nc_admin_input(NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_MAILS_TAB_ADMINMAIL_SUBJECT, 'mail_subject_admin', $this->settings, 0, 'width:100%');
        $result .= nc_admin_textarea(NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_MAILS_TAB_ADMINMAIL_TEXT, 'mail_body_admin', $this->settings, 1, 0, 'height: 15em;');
        $result .= nc_admin_checkbox(NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_MAILS_TAB_ADMINMAIL_HTML, 'mail_ishtml_admin', $this->settings, 1);
        $result .= "</fieldset>";
        $result .= nc_mail_attachment_form('minishop_admin');



        $result .= "</fieldset></form>";
        $result .= nc_admin_js_resize();


        $UI_CONFIG->actionButtons[] = array(
                "id" => "submit",
                "caption" => NETCAT_MODULE_MINISHOP_ADMIN_SETTINGS_SAVE,
                "action" => "mainView.submitIframeForm('SettingsForm')"
        );

        echo $result;
    }

    public function mails_save() {
        $nc_core = nc_Core::get_object();
        $params = array('mail_subject_customer', 'mail_body_customer', 'mail_ishtml_customer',
                'mail_subject_admin', 'mail_body_admin', 'mail_ishtml_admin');
        foreach ($params as $v) {
            $nc_core->set_settings($v, $nc_core->input->fetch_get_post($v), 'minishop');
        }

        nc_mail_attachment_form_save('minishop_customer');
        nc_mail_attachment_form_save('minishop_admin');
    }

    public function system_show() {
        global $UI_CONFIG;
        $UI_CONFIG->add_settings_toolbar();
        $settings = nc_Core::get_object()->get_settings('', 'minishop');

        echo "<form  action='admin.php' method='post' >
    <input type='hidden' name='view' value='system' />
    <input type='hidden' name='act' value='save' />
    <fieldset><legend>" . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_MAILS_TAB_SYSTEM . "</legend>
    <table id='systemSettings'>
    <tr><td>" . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_MAILS_TAB_SYSTEM_CART_COMPONENT . ":</td><td>" . nc_admin_select_component('', 'cart_class_id', $settings['cart_class_id']) . "</td></tr>
    <tr><td>" . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_MAILS_TAB_SYSTEM_ORDER_COMPONENT . ":</td><td>" . nc_admin_select_component('', 'order_class_id', $settings['order_class_id']) . "</td></tr>
    <tr><td>" . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_MAILS_TAB_SYSTEM_ORDERADD_LINK . ": </td>" . nc_admin_input_in_text('<td>%input</td>', 'addorder_url', $settings) . "</tr>
    <tr><td>" . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_MAILS_TAB_SYSTEM_ORDER_LINK . ": </td>" . nc_admin_input_in_text('<td>%input</td>', 'order_url', $settings) . "</tr>
    <tr><td>" . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_MAILS_TAB_SYSTEM_CART_LINK . ": </td>" . nc_admin_input_in_text('<td>%input</td>', 'cart_url', $settings) . "</tr>
    <tr><td>" . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_MAILS_TAB_SYSTEM_ORDER_CC . ": </td>" . nc_admin_input_in_text('<td>%input</td>', 'order_cc', $settings) . "</tr>
    <tr><td>" . NETCAT_MODULE_MINISHOP_ADMIN_TEMPLATE_MAILS_TAB_SYSTEM_CART_CC . ": </td>" . nc_admin_input_in_text('<td>%input</td>', 'cart_cc', $settings) . "</tr>
    </table>
    </fieldset></form>";
        $UI_CONFIG->actionButtons[] = array(
                "id" => "submit",
                "caption" => NETCAT_MODULE_MINISHOP_ADMIN_SETTINGS_SAVE,
                "action" => "mainView.submitIframeForm('SettingsForm')"
        );
    }

    public function system_save() {
        $nc_core = nc_Core::get_object();
        $params = array('cart_class_id', 'order_class_id', 'addorder_url', 'order_url', 'cart_url', 'order_cc', 'cart_cc');
        foreach ($params as $v) {
            $nc_core->set_settings($v, $nc_core->input->fetch_get_post($v), 'minishop');
        }
    }

    private function get_all_settings() {
        $nc_core = nc_Core::get_object();

        $all_settings = $nc_core->get_settings('', 'minishop');

        $shop_editor = new nc_module_editor();
        $shop_editor->load('minishop')->fill();
        $file_settings = $shop_editor->get_all_fields();

        $all_settings['file_settings'] = $file_settings;
        $all_settings['file_settings']['old'] = nc_Core::get_object()->get_settings('', 'minishop');

        return $all_settings;
    }

}