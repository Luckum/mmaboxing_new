<?php
if (!class_exists("nc_System")) die("Unable to load file.");

global $MODULE_FOLDER;
require_once ($MODULE_FOLDER."netshop/old/kxlib.php");
// do output only if invoked from add/edit page
if (preg_match("{/(add|message)\.php$}", $_SERVER["SCRIPT_NAME"], $script_name_regs) ||
        preg_match("{/(add|message)\.php$}", $_SERVER["PATH_INFO"], $script_name_regs)) {
    if (!is_object($shop)) die("Error initializing shop");
    $shop->LoadOrder($message);

    $prop = array_combine($fld, $fldName);
    ?>
    <style>

        .insert_text { padding:0px 25px; cursor: pointer;
                       background: url('<?= $SUB_FOLDER.$HTTP_ROOT_PATH
    ?>modules/netshop/images/stamp.gif') no-repeat;
        }

        .before_order_table { font: bold 8pt Tahoma; padding: 5px 0px }

        .order_table { border: 1px solid #777777; border-collapse: collapse; }
        .order_table ul { margin: 0px 0px 0px 20px; }
        .order_table img, .order_table input { vertical-align: middle; }
        .order_table td,  .order_table th { border: 1px solid #777777; font: 8pt Tahoma; padding: 1px 2px }
        .order_table .highlight td, .order_table .highlight input { background: #F0F0F0 }
        .order_table .highlight input { font-weight: bold }
        .order_table th { background: #CCCCCC; font-weight: bold; padding:10px 3px; }
        .order_table td { height:22px; }
        .order_table input { border: 1px solid #CCCCCC; font: 8pt Tahoma; text-align:center; width:100%; }
        .order_table .name { padding-left:5px  }
        .order_table .ro { border: none; padding: 1px; }

        #client_details p { margin: 6px 0px }

    </style>

    <script>
        // format number
        function fnum(num) { return Math.round(new Number(num)*100)/100; }

        /**
         * if 'percent' string contains '%', return sum*percent/100,
         * otherwise return 'percent' back
         */
        function percent(sum, percent)
        {
            if (percent.match(/(\-?\d+\.?\d*)\s?%/)) // e.g. discount in percent
            { return (sum * new Number(RegExp.$1) / 100); }
            else
            { return percent; }
        }

        // refresh sums in order.
        // @param row : row that was changed
        function calc(row) {
            var f = document.adminForm;

            if (row) // recalculate row
            {
                var original_price = f["item"+row+"[OriginalPrice]"].value,
                discount = percent(original_price, f["discount"+row].value);


                f["item"+row+"[ItemPrice]"].value = fnum(original_price - discount);
                f["totals"+row].value = fnum(f["item"+row+"[ItemPrice]"].value * f["item"+row+"[Qty]"].value);

                // get cart sum (without discounts) explicitly
                var cart_sum = 0;
                for (var i in item_ids) {
                    if (typeof item_ids[i] != 'string') {
                        continue;
                    }
                    cart_sum += new Number(f["totals"+item_ids[i]].value);
                }
                f.cart_totals.value = fnum(cart_sum);
            }

            // recalculate other sums

            // minus cart discount, plus delivery and payment costs
            var fields = {//'cart_discount_sum': '-',
                'f_PaymentCost': '+',
                'f_DeliveryCost': '+' };

            var totals = new Number(f.cart_totals.value),
            cost_w_discount = totals - percent(totals, f.cart_discount_sum.value),
            totals = cost_w_discount;

            for (var i in fields)
            {
                var multiplier = new Number(fields[i]+"1");
                totals += fnum(multiplier * percent(cost_w_discount, f[i].value));
            }

            f.totals.value = fnum(totals);
        }

        function switch_client_details()
        {
            var dst = document.getElementById('client_details');
            dst.style.display = (dst.style.display=='none' ? '' : 'none');
        }

        function insert_text(dst, text)
        {
            document.adminForm[dst].focus();
            document.adminForm[dst].value += (document.adminForm[dst].value ? "\n":"") + text;
            document.adminForm[dst].focus();
        }

        function form_submit()
        {
            var f = document.adminForm,
            fields = {//'cart_discount_sum': '-',
                'f_PaymentCost': '+',
                'f_DeliveryCost': '+' },
            totals = new Number(f.cart_totals.value),
            cart_discount = percent(totals, f.cart_discount_sum.value),
            cost_w_discount = totals - cart_discount,
            totals = cost_w_discount;

            f['cart_discount_sum'].value = cart_discount; // convert percents to absolute

            for (var i in fields)
            {
                var multiplier = new Number(fields[i]+"1");
                f[i].value = fnum(percent(cost_w_discount, f[i].value)); // to absolute
            }

            return true;
        }

    </script>
    <?
    print "<h4>";
    // number and date of the order:
    print strftime(sprintf(NETCAT_MODULE_NETSHOP_ORDER_EDIT, $message), timestamp($shop->Order["Created"]));
    print "</h4>
         <form name='adminForm' id='adminForm' method='post' onsubmit='return form_submit();'
          action='{$admin_url_prefix}{$script_name_regs[1]}.php'>";

    if ($admin_mode) print "<input type='hidden' name='admin_mode' value='1'>";
    if ($inside_admin)
            print "<input type='hidden' name='inside_admin' value='1'>";

    print "<input name='catalogue' type='hidden' value='$catalogue'>
         <input name='sub' type='hidden' value='$sub'>
         <input name='cc' type='hidden' value='$cc'>
         <input name='message' type='hidden' value='$message'>
         ".$nc_core->token->get_input()."
         <input name=posting type=hidden value=1>

         <table border=0 cellspacing=0 cellpadding=2 width=100%>
          <tr valign=top>
           <td width=25% align=right>".NETCAT_MODULE_NETSHOP_CUSTOMER.":&nbsp;</td>
           <td>";

    if ($shop->Order["User_ID"]) {
        $user = row("SELECT * FROM User WHERE User_ID={$shop->Order[User_ID]}");

        print "[{$shop->Order[User_ID]}] <b>$row[Name]</b> &nbsp; <u onclick='switch_client_details()' style='cursor:hand;cursor:pointer;'>".NETCAT_MODULE_NETSHOP_MORE."</u>
             <div style='display:none' id=client_details>";

        // Client Details
        $user = row("SELECT * FROM User WHERE User_ID = {$shop->Order[User_ID]}");
        $user_fields = assoc_array("SELECT Field_Name, Description FROM Field WHERE System_Table_ID=3 ORDER BY Priority");

        $type_of_fields = assoc_array("SELECT Field_Name,TypeOfData_ID  FROM Field WHERE System_Table_ID=3 ORDER BY Priority");

        foreach ($user_fields as $k => $v) {
            if ($type_of_fields[$k] != '6') {
                print "<p><b>$v:</b> ".nl2br($user[$k]);
            }
        }

        print "</div><br>";
    } else {
        print NETCAT_MODULE_NETSHOP_NOT_REGISTERED_USER;
    }

    print "</td></tr>";

    $res_field = q("SELECT *
                FROM Field
                WHERE Class_ID=$classID
                  AND TypeOfEdit_ID=1
                ORDER BY Priority");

    while ($row = mysql_fetch_assoc($res_field)) {
        if (!preg_match("/^(Status|Comments|OrderCurrency|
                          PaymentMethod|PaymentCost|PaymentInfo|
                          DeliveryMethod|DeliveryCost)$/x", $row["Field_Name"])) {
            print "<tr>
                <td align=right>$row[Description]:&nbsp;</td>
                <td>";

            switch ($row["TypeOfData_ID"]) {
                case 1:
                    # String
                    print nc_string_field("$f_$row[Field_Name]", "", $classID, 0)."\n";
                    break;

                case 2:
                    # Int
                    print nc_int_field("$row[Field_Name]", "", $classID, 0)."\n";
                    break;

                case 3:
                    # Text
                    print nc_text_field("$row[Field_Name]", "", $classID, 0)."\n";
                    break;

                case 4:
                    # List
                    print nc_list_field("$row[Field_Name]", "", $classID, 0)."\n";
                    break;

                case 5:
                    # Bool
                    print nc_bool_field("$row[Field_Name]", "", $classID, 0)."\n";
                    break;

                case 6:
                    # File
                    print nc_file_field("$row[Field_Name]", "", $classID, 0)."\n";
                    break;

                case 7:
                    # Float
                    print nc_float_field("$row[Field_Name]", "", $classID, 0)."\n";
                    break;

                case 8:
                    # DateTime
                    print nc_date_field("$row[Field_Name]", "", $classID, 0)."\n";
                    break;

                case 9:
                    # Relation
                    print nc_related_field("$row[Field_Name]")."\n";
                    break;

                case 10:
                    # Multiselect
                    print nc_multilist_field("$row[Field_Name]", "", "", $classID, 0, $selected)."\n";
                    break;
            }
            print "</td>
                 </tr>";
        }
    }

    print "<tr valign=top>
           <td align=right>$prop[Comments]:&nbsp;</td>
           <td><textarea name=f_Comments rows=3 cols=45 style='width:100%'>".htmlspecialchars($f_Comments)."</textarea></td>
          </tr>
          <tr><td colspan=2>&nbsp;</td></tr>

          <tr valign=top>
           <td align=right><b>$prop[PaymentInfo]:&nbsp;</b></td>
           <td>
             <textarea name=f_PaymentInfo rows=3 cols=45 style='width:100%'>".htmlspecialchars($f_PaymentInfo)."</textarea><br />
             <a class=insert_text onclick=\"insert_text('f_PaymentInfo',this.innerHTML)\">".strftime(NETCAT_MODULE_NETSHOP_PAYED_ON)."</a> &nbsp;
             <a class=insert_text onclick=\"insert_text('f_PaymentInfo',this.innerHTML)\">".strftime(NETCAT_MODULE_NETSHOP_PAYMENT_DOCUMENT)."</a>

           </td>
          </tr>
          <tr><td colspan=2>&nbsp;</td></tr>
          ";

    print "<tr><td align=right><b>$prop[Status]:&nbsp;</b></td><td>"; //<select name=f_Status>";

    $res = q("SELECT ShopOrderStatus_ID, ShopOrderStatus_Name FROM Classificator_ShopOrderStatus WHERE Checked = 1");
    while (list($k, $v) = mysql_fetch_row($res)) {
        if ($f_Status == $k) $v = "<b>$v</b>";
        print "<nobr><input type=radio name=f_Status value=$k id=rbSt$k".
                ($f_Status == $k ? " checked" : "").
                "><label for=rbSt$k>$v</label></nobr>&nbsp; ";
    }


    // OUTPUT ORDER CONTENTS:::

    print "</table><br />

         <table border=0 cellspacing=0 cellpadding=0 width=100%>
          <tr>
           <td class=before_order_table>
             $prop[OrderCurrency]: {$shop->Currencies[$f_OrderCurrency]}
             <input type=hidden name=f_OrderCurrency value=$f_OrderCurrency>
           </td>
           <td align=right class=before_order_table>
             <a href='".$SUB_FOLDER.$HTTP_ROOT_PATH."modules/netshop/export/commerceml.php?order_id=$shop->OrderID'>".NETCAT_MODULE_NETSHOP_EXPORT_COMMERCEML."</a>
           </td>
          </tr>
         </table>

         <table border=0 cellspacing=0 width=100% class=order_table>
           <tr>
            <th width=40%>".NETCAT_MODULE_NETSHOP_ITEM."</th>
            <th width=10%>".NETCAT_MODULE_NETSHOP_ITEM_PRICE."</th>
            <th width=10%>".NETCAT_MODULE_NETSHOP_DISCOUNT."</th>
            <th width=10%>".NETCAT_MODULE_NETSHOP_PRICE_WITH_DISCOUNT."</th>
            <th width=10%>".NETCAT_MODULE_NETSHOP_QTY."</th>
            <th width=15%>".NETCAT_MODULE_NETSHOP_COST."</th>
           </tr>
         ";

    $item_ids = array();
    foreach ($shop->CartContents as $item) {
        $item_ids[] = $item["RowID"];

        print "<tr><td class=name><font color=gray>".($item["ItemID"] ? $item["ItemID"] : $item["Message_ID"])."</font> &nbsp; ";
        print "<a target=_blank href='".$SUB_FOLDER.$HTTP_ROOT_PATH."message.php?catalogue=$catalogue&sub=$item[Subdivision_ID]&cc=$item[Sub_Class_ID]&message=$item[Message_ID]' tabindex=-1>$item[Name]</a></td>
              <td><input onkeyup='calc(\"$item[RowID]\")' type=text name='item".$item["RowID"]."[OriginalPrice]' value='$item[OriginalPrice]' size=6></td>
              <td><nobr><input onkeyup='calc(\"$item[RowID]\")' type=text name='discount".$item["RowID"]."' value='".($item["OriginalPrice"] - $item["ItemPrice"])."' style='width:60%' size=6>";

        if ($item["Discounts"]) {
            print " <img src='".$SUB_FOLDER.$HTTP_ROOT_PATH."modules/netshop/interface/qmark.png' width=16 height=16 border=0 alt=\"\n";
            foreach ($item["Discounts"] as $discount) {
                print "   ".$shop->FormatCurrency($discount["Sum"] / $item["Qty"])." (".htmlspecialchars($discount["Name"]).")   \n";
            }

            if ($discount["PriceMinimum"]) {
                printf("\n   ".NETCAT_MODULE_NETSHOP_ITEM_MINIMAL_PRICE_REACHED."\n", $item["ItemPriceF"]);
            }

            print "\" />";
        }

        print "</nobr></td>
              <td><input class=ro tabindex=-1 readonly type=text name='item".$item["RowID"]."[ItemPrice]' value='$item[ItemPrice]' size=6></td>
              <td><input onkeyup='calc(\"$item[RowID]\")' type=text name='item".$item["RowID"]."[Qty]' value='$item[Qty]' size=4></td>
              <td><input class=ro tabindex=-1 readonly type=text name='totals".$item["RowID"]."' value='$item[TotalPrice]' size=10></td>
             </tr>";
    }

    print "<tr class=highlight>
           <td colspan=5 class=name><b>".NETCAT_MODULE_NETSHOP_ITEM_COST."</b></td>
           <td><input class=ro tabindex=-1 readonly type=text name='cart_totals' value='".$shop->CartFieldSum("ItemPrice")."' size=10></td>
          </tr>";
    {
        print "<tr><td colspan=5 class=name><b>";
        print (sizeof($shop->CartDiscounts) > 1) ? NETCAT_MODULE_NETSHOP_DISCOUNTS.":</b><ul><li>" :
                        NETCAT_MODULE_NETSHOP_DISCOUNT.":</b> ";

        foreach ((array) $shop->CartDiscounts as $i => $discount) {
            if ($i > 0) print "<li>";
            print "$discount[Name] &mdash; $discount[SumF]";
        }

        if (sizeof($shop->CartDiscounts) > 1) print "</ul>";

        print "</td><td><input onkeyup='calc()' type=text name='cart_discount_sum' value='$shop->CartDiscountSum' size=10></td></tr>";
    }

    print "<tr><td class=name colspan=5><b>$prop[DeliveryMethod]:</b>
           <input type=hidden name=f_DeliveryMethod value=$f_DeliveryMethod>".
            value1("SELECT Name FROM Message$shop->delivery_methods_table WHERE Message_ID='$f_DeliveryMethod'").
            "</td><td><input onkeyup='calc()' type=text name='f_DeliveryCost' value='$f_DeliveryCost' size=10></td></tr>

          <tr><td class=name colspan=5><b>$prop[PaymentMethod]:</b>
           <input type=hidden name=f_PaymentMethod value=$f_PaymentMethod>".
            value1("SELECT Name FROM Message$shop->payment_methods_table WHERE Message_ID='$f_PaymentMethod'").
            "</td><td><input onkeyup='calc()' type=text name='f_PaymentCost' value='$f_PaymentCost' size=10></td></tr>

          <tr class=highlight>
           <td colspan=5 class=name><b>".NETCAT_MODULE_NETSHOP_SUM."</b></td>
           <td><input class=ro tabindex=-1 readonly type=text name='totals' value='".$shop->CartSum()."' size=10></td>
          </tr>
         ";

    print "</table>";
    print "<input type=hidden name=f_Checked value='$f_Checked'>";
    print "<br /><center><input type=submit value='".CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_SAVE."'</center>";
    print "</form><script>item_ids = ['".join("','", $item_ids)."'];</script>";
}
?>