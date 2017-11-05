<?
// do output only if invoked from add/edit page
if (preg_match("{/(add|message)\.php$}", $_SERVER["SCRIPT_NAME"], $script_name_regs) ||
        preg_match("{/(add|message)\.php$}", $_SERVER["PATH_INFO"], $script_name_regs)) {
    if (!is_object($shop)) die("Error initializing shop");
?>
    <script>
        function appliesto_onchange() //  hide or show parts of the page depending on f_AppliesTo value
        {
            var o = document.adminForm.f_AppliesTo, v = o.options[o.selectedIndex].value,
            divs = ['Subdivisions','GoodsTypes','Goods', "StopItem"];

            for (var i in divs)
            {
                var dst = document.getElementById('tr_'+divs[i]);
                if (dst) dst.style.display = (v==1 ? '' : 'none');
                document.getElementById('tr_StopCart').style.display = (v==1 ? 'none' : '');
            }
        }

        function showgoods_onchange(checked)
        {
            var frame = document.getElementById('goods_frame');
            if (checked)
            {
                frame.style.display = '';
                update_iframe();
            }
            else
            {
                frame.style.display = 'none';
                document.adminForm.f_Goods.value='';
            }
        }

        function insert_value(field)
        {
            var o = document.adminForm["fields_"+field], v = o.options[o.selectedIndex].value;
            if (v)
            {
                document.adminForm["f_"+field].focus();
                document.adminForm["f_"+field].value += v+" ";
                document.adminForm["f_"+field].focus();
            }
        }

        function update_fields(f)
        {
            var composite_fields = f ? [f] : ['TypeOfPrice', 'UserGroups', 'Subdivisions', 'GoodsTypes'];

            for (var i in composite_fields)
            {
                var val = [],
                field = composite_fields[i],
                src = document.adminForm["f_"+field+"0"]; //.options;

                for (j=0; j < src.options.length; j++)
                {
                    if (src.options[j].selected) val.push(src.options[j].value);
                }

                document.adminForm["f_"+field].value = val.join(",");
            }

            if (f) update_iframe();
        }

        function update_iframe()
        {
            form = document.adminForm;
            if (document.getElementById('showgoods').checked)
            {
                document.getElementById('goods_frame').contentWindow.location =
                    "<?=$SUB_FOLDER.$HTTP_ROOT_PATH
?>modules/netshop/interface/discounts_edit_goods.php" +
                    "?selected=" + escape(form.f_Goods.value) +
                    "&goods_types=" + escape(form.f_GoodsTypes.value) +
                    "&subdivisions=" + escape(form.f_Subdivisions.value) +
                    "&shop_id=<?=$shop->shop_id
?>" +
                    "&catalogue_id=<?=$catalogue
?>";
            }
        }

    </script>
<?
    $prop = array_combine($fld, $fldName);

    print "<h4>".NETCAT_MODULE_NETSHOP_DISCOUNT_EDIT."</h4>
         <form name='adminForm' id='adminForm' method='post' action='{$admin_url_prefix}{$script_name_regs[1]}.php'>";

    if ($admin_mode) print "<input type='hidden' name='admin_mode' value='1'>";

    print "<input name='catalogue' type='hidden' value='$catalogue'>
         <input name='sub' type='hidden' value='$sub'>
         <input name='cc' type='hidden' value='$cc'>
         <input name='message' type='hidden' value='$message'>
         ".$nc_core->token->get_input()."
         <input name=posting type=hidden value=1>

         <table border=0 cellspacing=0 cellpadding=2 width=100%>

          <tr>
           <td align=right width=30%>$prop[Name]:&nbsp;</td>
           <td><input name=f_Name type=text size=50 maxlength=255 value='".htmlspecialchars($f_Name)."' style='width:100%'></td>
          </tr>

          <tr valign=top>
           <td align=right>$prop[Description]:&nbsp;</td>
           <td><textarea name=f_Description rows=4 cols=50 style='width:100%'>".htmlspecialchars($f_Description)."</textarea></td>
          </tr>

          <tr>
           <td align=right>$prop[ValidFrom]:&nbsp;</td>
           <td><input name=f_ValidFrom_day type=text size=2 maxlength=2
                value='$f_ValidFrom_day'>-<input name=f_ValidFrom_month type=text size=2 maxlength=2
                value='$f_ValidFrom_month'>-<input name=f_ValidFrom_year type=text size=4 maxlength=4
                value='$f_ValidFrom_year'></td>
          </tr>

          <tr>
           <td align=right>$prop[ValidTo]:&nbsp;</td>
           <td><input name=f_ValidTo_day type=text size=2 maxlength=2
                value='$f_ValidTo_day'>-<input name=f_ValidTo_month type=text size=2 maxlength=2
                value='$f_ValidTo_month'>-<input name=f_ValidTo_year type=text size=4 maxlength=4
                value='$f_ValidTo_year'></td>
          </tr>";

    // Типы цен
    $res = q("SELECT DISTINCT f.Field_Name, f.Description
                     FROM Class as c, Field as f
                     WHERE c.Class_ID=f.Class_ID
                       AND f.Field_Name LIKE 'Price%'
                       AND c.Class_ID IN (".join(',', NetShop::get_goods_table()).") 
                     ORDER BY c.Priority, c.Class_ID, f.Field_Name
                    ");

    $num_rows = mysql_num_rows($res);
    print "<tr valign=top".($num_rows < 2 ? " style='display:none'" : "").">
              <td align=right>$prop[TypeOfPrice]:&nbsp;</td>
              <td><input type=hidden name=f_TypeOfPrice value='$f_TypeOfPrice'>
                  <select name=f_TypeOfPrice0 size=$num_rows multiple onchange='update_fields(\"TypeOfPrice\")'>\n";

    $tp = ",$f_TypeOfPrice,";

    while (list($k, $v) = mysql_fetch_row($res)) {
        print "<option value=$k";
        if (strpos($tp, ",$k,") !== false) print " selected";

        print ">[$k] $v</option>\n";
    }

    print "</select></td></tr>";


    // Группы пользователей
    $res = q("SELECT PermissionGroup_ID, PermissionGroup_Name
                     FROM PermissionGroup
                     ORDER BY PermissionGroup_ID
                    ");

    print "<tr valign=top>
              <td align=right>$prop[UserGroups]:&nbsp;</td>
              <td><input type=hidden name=f_UserGroups value='$f_UserGroups'>
                  <select name=f_UserGroups0 size=".mysql_num_rows($res)." multiple
                   onchange='update_fields(\"UserGroups\")'>\n";

    $tp = ",$f_UserGroups,";

    while (list($k, $v) = mysql_fetch_row($res)) {
        print "<option value=$k";
        if (strpos($tp, ",$k,") !== false) print " selected";

        print ">[$k] $v</option>\n";
    }

    print "</select></td></tr>";



    print "<tr>
           <td align=right>$prop[AppliesTo]:&nbsp;</td>
           <td><select name=f_AppliesTo onchange='appliesto_onchange()'>
             <option value=1".($f_AppliesTo == 1 ? " selected" : "").">".NETCAT_MODULE_NETSHOP_APPLIES_TO_GOODS."</option>
             <option value=2".($f_AppliesTo == 2 ? " selected" : "").">".NETCAT_MODULE_NETSHOP_APPLIES_TO_CART."</option>
           </td>
          </tr>

          <tr valign=top id=tr_Subdivisions".($f_AppliesTo == 2 ? " style='display:none'" : "").">
           <td align=right>$prop[Subdivisions]:&nbsp;</td>
           <td><input type=hidden name=f_Subdivisions value='$f_Subdivisions'>
               <select name=f_Subdivisions0 size=10 multiple style='width:100%'
                onchange='update_fields(\"Subdivisions\")'>";

    $sections = GetStructure($shop->shop_id, "Checked=1");
    $tp = ",$f_Subdivisions,";

    foreach ($sections as $row) {
        print "<option value='$row[Subdivision_ID]'";
        if (strpos($tp, ",$row[Subdivision_ID],") !== false) print " selected";

        print ">".str_repeat("&nbsp;", ($row["level"] + 1) * 4).
                "$row[Subdivision_Name]</option>\n";
    }

    print "</select>
           <input type=hidden name=f_GoodsTypes value='$f_GoodsTypes'>
          </td></tr>";


    $res = q("SELECT DISTINCT c.Class_ID, c.Class_Name
                    FROM Class as c
                    WHERE c.Class_ID IN (".join(',', NetShop::get_goods_table()).") 
                    ORDER BY c.Priority, c.Class_ID
                   ");

    if (mysql_num_rows($res) > 1) {
        print "<tr valign=top id=tr_GoodsTypes ".($f_AppliesTo == 2 ? " style='display:none'" : "").">
                     <td align=right>$prop[GoodsTypes]:&nbsp;</td>
                     <td><select name=f_GoodsTypes0 size=".mysql_num_rows($res).
                " multiple style='width:100%'
                          onchange='update_fields(\"GoodsTypes\")'>";

        $tp = ",$f_GoodsTypes,";

        while (list($id, $name) = mysql_fetch_row($res)) {
            print "<option value=$id";
            if (strpos($tp, ",$id,") !== false) print " selected";
            print ">$name</option>\n";
        }

        print "</select></td></tr>";
    }

    print "<tr id=tr_Goods".($f_AppliesTo == 2 ? " style='display:none'" : "").">
           <td>&nbsp;</td>
           <td><input type=checkbox id=showgoods".($f_Goods ? " checked" : "").
            " onclick='showgoods_onchange(this.checked)'> <label for=showgoods>$prop[Goods]</label>
            <iframe style='height:200px; width:100%;".($f_Goods ? "" : " display:none").
            "' frameborder=0 border=0 scrolling=no id=goods_frame></iframe>
            <input type=hidden name=f_Goods value='$f_Goods'>
            <script>update_iframe();</script>
           </td>
          </tr>

          <tr><td colspan=2>&nbsp;</td></tr>

          <tr bgcolor=#F0F0F0>
           <td align=right>$prop[Condition]:&nbsp;</td>
           <td><input name=f_Condition type=text size=50 maxlength=255 value='".htmlspecialchars($f_Condition)."' style='width:60%'>
             &larr;
             <select style='width:30%' id='fields_Condition' onchange='insert_value(\"Condition\")'>
               <option>".NETCAT_MODULE_NETSHOP_DISCOUNT_SELECT_FIELD."
               <option value='[TotalPrice]'>[TotalPrice] ".NETCAT_MODULE_NETSHOP_COST."
               <option value='[Qty]'>[Qty] ".NETCAT_MODULE_NETSHOP_QTY."
               <option value='[PrevOrdersSum]'> ".NETCAT_MODULE_NETSHOP_PREV_ORDERS_SUM."
             </select>
           </td>
          </tr>

          <tr bgcolor=#F0F0F0>
           <td align=right>$prop[Function]:&nbsp;</td>
           <td>
             <select name=f_FunctionDestination style='width:40%'>
              <option value='[TotalPrice]'".($f_FunctionDestination == "[TotalPrice]" ? " selected" : "").">[TotalPrice] ".NETCAT_MODULE_NETSHOP_COST."
              <option value='[Qty]'".($f_FunctionDestination == "[Qty]" ? " selected" : "").">[Qty] ".NETCAT_MODULE_NETSHOP_QTY."
             </select>
             <select name=f_FunctionOperator style='width:20%'>";

    $arr = array("*=" => NETCAT_MODULE_NETSHOP_MULTIPLY,
            "+=" => NETCAT_MODULE_NETSHOP_ADD,
            "-=" => NETCAT_MODULE_NETSHOP_SUBSTRACT,
            "=" => NETCAT_MODULE_NETSHOP_EQUALS
    );

    foreach ($arr as $k => $v) {
        print "<option value='$k'";
        if ($k == $f_FunctionOperator) print " selected";
        print ">$v\n";
    }

    print "</select>
             <input name=f_Function type=text size=50 maxlength=255 value='".htmlspecialchars($f_Function)."' style='width:32%'>
           </td>
          </tr>

          <tr bgcolor=#F0F0F0 id=tr_StopItem".($f_AppliesTo == 2 ? " style='display:none'" : "").">
           <td align=right>&nbsp;</td>
           <td><input name=f_StopItem type=checkbox value=1".
            ($f_StopItem ? " checked" : "")
            ." id=cbStopItem> <label for=cbStopItem>$prop[StopItem]</label></td>
          </tr>

          <tr bgcolor=#F0F0F0 id=tr_StopCart".($f_AppliesTo != 2 ? " style='display:none'" : "").">
           <td align=right>&nbsp;</td>
           <td><input name=f_StopCart type=checkbox value=1".
            ($f_StopCart ? " checked" : "")
            ." id=cbStopCart> <label for=cbStopCart>$prop[StopCart]</label></td>
          </tr>

          ";

    print "
           <tr><td colspan=2>&nbsp;</td></tr>
           <!-- - - - - - - -->
           <tr>
            <td align=right>".CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_PRIORITY.":&nbsp;</td>
            <td>
              <input type=text name=f_Priority value='$f_Priority' size=2>
              &nbsp;
              <input type=checkbox name=f_Checked value=1 id=cbChk".($f_Checked || !$message ? " checked" : "")."><label for=cbChk>".
            CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_ON."</label>
            </td>
           </tr>

           <tr>
            <td>&nbsp;</td>
            <td><br><input type=submit value='".CONTROL_CONTENT_CATALOUGE_FUNCS_CATALOGUEFORM_SAVE."'</td>
           </tr>

         </table>
    ";
}
?>