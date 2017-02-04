<?

// $NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)) . ( strstr(__FILE__, "/") ? "/" : "\\" );
// @include_once ($NETCAT_FOLDER . "vars.inc.php");
/* NETSHOP SETUP */
ob_start();

require_once("old/header.inc.php");

$UI_CONFIG = new ui_config_module_netshop('admin', 'setup');

extract($MODULE_VARS["netshop"]);

print "<br /><form method=post>";

$catalogue = (int) $catalogue;

if ($catalogue) {
    // prevent duplicate submit
    if (value1("SELECT sc.Sub_Class_ID
               FROM Catalogue as c,
                    Subdivision as sd,
                    Sub_Class as sc
               WHERE c.Catalogue_ID = $catalogue
                 AND c.Catalogue_ID = sd.Catalogue_ID
                 AND sd.Subdivision_ID = sc.Subdivision_ID
                 AND sc.Class_ID = $SHOP_TABLE
             ")) {
        // ALREADY INSTALLED ON THIS SITE
        EndHtml();
        die();
    }

    $shop_section_name = "shop";

    // add sections:
    // - shop
    // that drives me crazy!
//   $shop_subdivision_id = InsertSub('Каталог товаров', 'Netshop', '', 0, 0, 0, 0, 0, 0, 0, $catalogue, 0, 1);

    $shop_subdivision_id = value1("SELECT Subdivision_ID
                                    FROM Subdivision
                                   WHERE EnglishName = 'Netshop'
                                     AND Catalogue_ID = $catalogue");

    if (!$shop_subdivision_id) {
        q("INSERT INTO Subdivision
         SET Catalogue_ID=$catalogue,
             Parent_Sub_ID=0,
             Subdivision_Name = '" . mysql_real_escape_string(NETCAT_MODULE_NETSHOP_GOODS_CATALOGUE) . "',
             Checked=1,
             EnglishName='Netshop',
             Hidden_URL='/Netshop/',
             Priority='" . (value1("SELECT MAX(Priority) FROM Subdivision where Parent_Sub_ID=0 AND Catalogue_ID='$catalogue'") + 1) . "'");

        $shop_subdivision_id = mysql_insert_id();
    }

    // - cart
    InsertSub(NETCAT_MODULE_NETSHOP_CART, 'Cart', '', 0, 0, 0, 0, 0, $CART_TABLE, $shop_subdivision_id, $catalogue, 0, 0);
    UpdateHiddenURL("/Netshop/", $shop_subdivision_id, $catalogue);

    // ==================================================================================

    /**
     * adds object of a given class to the section (and class to section if it's not here yet)
     * @return integer class_id_in_section
     */
    function add_objects_to_sub($catalogue_id, $sub_id, $class_id, $class_name_in_section, $class_keyword, $checked, $read_access = 0, $write_access = 0, $edit_access = 0, $subscribe_access = 0, $values = array()
    ) {
        $catalogue_id = intval($catalogue_id);
        $sub_id = intval($sub_id);
        $class_id = intval($class_id);
        $read_access = intval($read_access);
        $write_access = intval($write_access);
        $edit_access = intval($edit_access);
        $GLOBALS['PRINT_QUERIES'] = 1;

        // check if class is in sub
        $class_id_in_sub = value1("SELECT Sub_Class_ID FROM Sub_Class WHERE Subdivision_ID = $sub_id AND Class_ID = $class_id");

        if (!$class_id_in_sub) { // the class isn't linked to this sub yet
            $class_priority = value1("SELECT MAX(Priority) FROM Sub_Class WHERE Subdivision_ID=$sub_id") + 1;

            // add class to the sub
            q("INSERT INTO Sub_Class
            SET Subdivision_ID=$sub_id,
                Class_ID=$class_id,
                Sub_Class_Name='" . mysql_real_escape_string($class_name_in_section) . "',
                Priority=$class_priority,
                Read_Access_ID=$read_access,
                Write_Access_ID=$write_access,
                EnglishName='" . mysql_real_escape_string($class_keyword) . "',
                Checked=$checked,
                Catalogue_ID=$catalogue_id,
                Edit_Access_ID=$edit_access");
            $class_id_in_sub = mysql_insert_id();
        }

        // add object, fill values
        if ($values) {
            $object_priority = value1("SELECT MAX(Priority) FROM Message$class_id WHERE Subdivision_ID=$sub_id") + 1;
            foreach ($values as $object_properties) {
                $qry = array();
                foreach ($object_properties as $k => $v) {
                    $qry[] = "`$k` = '" . mysql_real_escape_string($v) . "'";
                }

                if (!sizeof($qry)) {
                    continue;
                }

                $qry[] = "Priority = $object_priority";

                q("INSERT INTO Message$class_id
               SET Subdivision_ID = $sub_id,
               Sub_Class_ID=$class_id_in_sub, " .
                        join(", ", $qry));

                $object_priority++;
            }
        }

        return $class_id_in_sub;
    }

    // add templates to shop============================================================
    // create shop settings template and get its' id (redirect later) [Sec Rights!!!]
    // Настройки магазина Интернет-магазин
    $shop_settings_cc = add_objects_to_sub($catalogue, $shop_subdivision_id, $SHOP_TABLE, NETCAT_MODULE_NETSHOP_SHOP_SETTINGS, 'Settings', 0, 3, 3, 3, 3);

    // Оформление заказа Заказ
    add_objects_to_sub($catalogue, $shop_subdivision_id, $ORDER_TABLE, NETCAT_MODULE_NETSHOP_MAKE_ORDER, 'Order', 0, 3, 0, 3, 3);

    // Компонент Товары
    add_objects_to_sub($catalogue, $shop_subdivision_id, $GOODS_TABLE, NETCAT_MODULE_NETSHOP_GOODS_CATALOGUE, 'Goods', 1, 3, 3, 3, 3);

    $SQL = "CREATE TABLE IF NOT EXISTS `Classificator_RobokassaCurrency` (
                `RobokassaCurrency_ID` int(11) NOT NULL auto_increment,
                `RobokassaCurrency_Name` char(255) character set cp1251 NOT NULL default '',
                `RobokassaCurrency_Priority` int(11) default NULL,
                `Value` text character set cp1251,
                `Checked` int(1) default '1',
                PRIMARY KEY  (`RobokassaCurrency_ID`)
            ) ENGINE=MyISAM
              DEFAULT CHARSET=utf8;";

    $SQL = "SELECT count(*)
                FROM Classificator WHERE
                    Table_Name = 'RobokassaCurrency'";

    if (!$db->get_var($SQL)) {
        $db->query("INSERT INTO `Classificator` (`Classificator_ID`, `Classificator_Name`, `Table_Name`, `System`, `Sort_Type`, `Sort_Direction`) VALUES (49, 'Валюты Робокассы', 'RobokassaCurrency', 0, 0, 0)");
    }

    $SQL = "SELECT count(*)
                FROM Classificator_RobokassaCurrency";

    if (!$db->get_var($SQL)) {
        $db->query("INSERT INTO `Classificator_RobokassaCurrency` (`RobokassaCurrency_ID`, `RobokassaCurrency_Name`, `RobokassaCurrency_Priority`, `Value`, `Checked`) VALUES (1, 'AlfaBankR', 1, '', 1), (2, 'MtsR', 2, '', 1), (3, 'MegafonR', 3, '', 1), (4, 'BANKOCEANMR', 4, '', 1), (5, 'TerminalsPinpayR', 5, '', 1), (6, 'QiwiR', 6, '', 1), (7, 'TerminalsMElementR', 7, '', 1), (8, 'TerminalsNovoplatR', 8, '', 1), (9, 'TerminalsUnikassaR', 9, '', 1), (10, 'ElecsnetR', 10, '', 1), (11, 'ContactR', 11, '', 1), (12, 'IFreeR', 12, '', 1), (13, 'BANKOCEANCHECKR', 13, '', 1), (14, 'QiwiR', 14, '', 1), (15, 'VTB24R', 15, '', 1), (16, 'TerminalsPkbR', 16, '', 1), (17, 'RapidaOceanEurosetR', 17, '', 1), (18, 'EasyPayB', 18, '', 1), (19, 'MoneyMailR', 19, '', 1), (20, 'RuPayR', 20, '', 1), (21, 'TeleMoneyR', 21, '', 1), (22, 'ZPaymentR', 22, '', 1), (23, 'W1R', 23, '', 1), (24, 'LiqPayZ', 24, '', 1), (25, 'WMBM', 25, '', 1), (26, 'WMEM', 26, '', 1), (27, 'WMGM', 27, '', 1), (28, 'WMRM', 28, '', 1), (29, 'WMUM', 29, '', 1), (30, 'WMZM', 30, '', 1), (31, 'MailRuR', 31, '', 1), (32, 'PCR', 32, '', 1)");
    }

    // Валюты Валюты
    // add currencies (rur, usd, euro)
    add_objects_to_sub($catalogue, $shop_subdivision_id, $CURRENCY_RATES_TABLE, NETCAT_MODULE_NETSHOP_CURRENCIES, 'Currencies', 0, 3, 3, 3, 3, array(
            array("Currency" => 3, "NameShort" => "&euro;", "NameCases" => NETCAT_MODULE_NETSHOP_EURO, "DecimalName" => NETCAT_MODULE_NETSHOP_EUROCENT, "Format" => "# %s"),
            array("Currency" => 2, "NameShort" => "$", "NameCases" => NETCAT_MODULE_NETSHOP_USD, "DecimalName" => NETCAT_MODULE_NETSHOP_CENT, "Format" => "# %s"),
            array("Currency" => 1, "Rate" => 1, "NameShort" => "руб", "NameCases" => NETCAT_MODULE_NETSHOP_RUR, "DecimalName" => NETCAT_MODULE_NETSHOP_COPECK, "Format" => "%s #")
            )
    );

	// we're installing shop only once!
    add_objects_to_sub($catalogue, $shop_subdivision_id, $OFFICIAL_RATES_TABLE, NETCAT_MODULE_NETSHOP_CB_RATES, 'OfficialRates', 0, 3, 3, 3, 3);

    q("INSERT INTO CronTasks SET Cron_Minutes = 0, Cron_Hours = 12, Cron_Days = 0, Cron_Months = 0, Cron_Weekdays = 0,
      Cron_Script_URL = '" . $SUB_FOLDER . $HTTP_ROOT_PATH . "modules/netshop/rates_cbr.php?catalogue=$catalogue&key=" . md5($SECRET_KEY) . "'");


    // Цены для разных групп пользователей
    add_objects_to_sub($catalogue, $shop_subdivision_id, $PRICE_RULES_TABLE, NETCAT_MODULE_NETSHOP_PRICE_GROUPS, 'UserPrices', 0, 3, 3, 3, 3);

    // Скидки Скидка
    add_objects_to_sub($catalogue, $shop_subdivision_id, $DISCOUNT_TABLE, NETCAT_MODULE_NETSHOP_DISCOUNTS, 'Discounts', 0, 3, 3, 3, 3);

    // Варианты доставки Варианты доставки
    // create delivery variants
    add_objects_to_sub($catalogue, $shop_subdivision_id, $DELIVERY_METHODS_TABLE, NETCAT_MODULE_NETSHOP_DELIVERY_METHODS, 'DeliveryMethods', 0, 3, 3, 3, 3, array(array("Name" => NETCAT_MODULE_NETSHOP_BY_COURIER, "Checked" => 1))
    );
    // Варианты оплаты Варианты оплаты
    // create payment variants

    add_objects_to_sub($catalogue, $shop_subdivision_id, $PAYMENT_METHODS_TABLE, NETCAT_MODULE_NETSHOP_PAYMENT_METHODS, 'PaymentMethods', 0, 3, 3, 3, 3, array(
            array("Name" => NETCAT_MODULE_NETSHOP_CREDIT_CARD, "Checked" => 0, "Description" => NETCAT_MODULE_NETSHOP_CREDIT_CARD_DESCRIPTION, "Interface" => "assist"),
            array("Name" => NETCAT_MODULE_NETSHOP_YANDEX_MONEY, "Checked" => 0, "Interface" => "paycash_email"),
            array("Name" => NETCAT_MODULE_NETSHOP_WEBMONEY, "Checked" => 0, "Interface" => "webmoney"),
            array("Name" => NETCAT_MODULE_NETSHOP_CASHLESS, "Checked" => 1, "Interface" => "bank"),
            array("Name" => NETCAT_MODULE_NETSHOP_SBERBANK, "Checked" => 1, "Interface" => "sberbank"),
            array("Name" => NETCAT_MODULE_NETSHOP_CASH, "Checked" => 1, "Interface" => "cash")
    ));

    // Шаблоны писем Шаблоны писем
    // create default template
    add_objects_to_sub($catalogue, $shop_subdivision_id, $EMAIL_TEMPLATE_TABLE, NETCAT_MODULE_NETSHOP_EMAIL_TEMPLATES, 'EmailTemplates', 0, 3, 3, 3, 3, array(array("Keyword" => "OrderConfirmation",
                    "Title" => NETCAT_MODULE_NETSHOP_ORDER_EMAIL_HEADER,
                    "Body" => NETCAT_MODULE_NETSHOP_ORDER_EMAIL_BODY
            )));

    // redirect to shop settings
    q("UPDATE Module SET Installed=1 WHERE Keyword='netshop'");
    ob_end_clean();
    header("Location: " . $SUB_FOLDER . $HTTP_ROOT_PATH . "add.php?inside_admin=1&catalogue=$catalogue&sub=$shop_subdivision_id&cc=$shop_settings_cc");
}
// ask where to install shop
else if (!$catalogue) {
    /* !!! IF assoc_array WILL BE REPLACED BY SOME OTHER FUNCTION, CHECK THIS QUERY !!! */
    $all_sites = assoc_array("SELECT c.*, (sc.Class_ID=" . $SHOP_TABLE . ") as has_shop
                                  FROM `Catalogue` as c
                                  LEFT JOIN `Subdivision` as sd ON  c.`Catalogue_ID` = sd.`Catalogue_ID`
                                  LEFT JOIN `Sub_Class` as sc ON sd.`Subdivision_ID` = sc.`Subdivision_ID`
                                  ORDER BY `has_shop` ASC");

    // determine whether there are sites w/o shops
    $sites_without_shop = sizeof($all_sites);
    foreach ($all_sites as $row) {
        if ($row["has_shop"])
            $sites_without_shop--;
    }

    if ($sites_without_shop > 0) {
        print NETCAT_MODULE_NETSHOP_SETUP_ON_SITE . "<br><select name=catalogue>";
        foreach ($all_sites as $row) {
            if (!$row["has_shop"])
                print "<option value=$row[Catalogue_ID]>$row[Catalogue_Name]</div>";
        }
        print "</select><br><br>" . NETCAT_MODULE_NETSHOP_SETUP_SHOP_SETTINGS_REDIRECT;
        $UI_CONFIG->actionButtons[] = array("id" => "submit",
                "caption" => NETCAT_MODULE_NETSHOP_INSTALL,
                "action" => "mainView.submitIframeForm()");
    }
    else {
        nc_print_status(NETCAT_MODULE_NETSHOP_SETUP_EVERYWHERE, 'info');
    }
}

print "</form>";

EndHtml();
?>