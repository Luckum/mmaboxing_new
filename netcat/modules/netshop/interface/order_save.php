<?php

//$shop = new Netshop();
if (!isset($shop) || !is_object($shop)) die("Error initializing shop");
$shop->LoadOrder(intval($_POST["message"]));

foreach ($_POST["item"] as $type_id => $arr) {
    foreach ($arr as $item_id => $item) {

        if ($_POST["item"][$type_id][$item_id]['Qty'] == 0) {

            if (count($_POST["item"][$type_id]) == 1) {
                $nc_core->message->delete_by_id($_POST["message"], $nc_core->modules->get_vars('netshop', 'ORDER_TABLE'));
                q("DELETE FROM Netshop_OrderGoods WHERE Order_ID = $_POST[message]");
                q("DELETE FROM Netshop_OrderDiscounts WHERE Order_ID = $_POST[message]");
            } else {
                q("DELETE FROM Netshop_OrderGoods
             WHERE Item_Type = ".intval($type_id)."
              AND Item_ID = ".intval($item_id)."
              AND Qty = 0
             ");
                q("DELETE FROM Netshop_OrderDiscounts
             WHERE Item_Type = ".intval($type_id)."
              AND Item_ID = ".intval($item_id)."
              AND Order_ID='".intval($_POST['message'])."'
             ");
            }
        }

        $qry = array();
        foreach ($item as $field => $value) {
            // save values if needed
            if ($shop->Cart[$type_id][$item_id][$field] != $value) {
                $qry[] = " `".$db->escape($field)."` = '".$db->escape($value)."'";
            }
        }

        if ($qry) {
            q("UPDATE Netshop_OrderGoods
            SET ".join(",", $qry)."
            WHERE Order_ID = '".intval($_POST[message])."'
              AND Item_Type = ".intval($type_id)."
              AND Item_ID = ".intval($item_id));
        }

        // if discount != old discount: remove old disocounts
        if (($shop->Cart[$type_id][$item_id]["OriginalPrice"] - $shop->Cart[$type_id][$item_id]["ItemPrice"]) !=
                $item["OriginalPrice"] - $item["ItemPrice"]) {
            q("DELETE FROM Netshop_OrderDiscounts
            WHERE Order_ID = '".intval($_POST[message])."'
              AND Item_Type = ".intval($type_id)."
              AND Item_ID = ".intval($item_id));

            if ($_POST["discount"][$type_id][$item_id]) {
                $discount_sum = ($item['OriginalPrice'] - $item['ItemPrice']) * $item['Qty'];
//          $discount_sum = $_POST["discount"][$type_id][$item_id]*$item["Qty"];

                q("INSERT INTO Netshop_OrderDiscounts
               SET Order_ID='".intval($_POST['message'])."',
                   Item_Type=".intval($type_id).",
                   Item_ID=".intval($item_id).",
                   Discount_ID=0,
                   Discount_Name='".$db->escape(NETCAT_MODULE_NETSHOP_DISCOUNT_MANUAL)."',
                   Discount_Sum='".$db->escape($discount_sum)."',
                   PriceMinimum=0");
            }
        }
    }
} // of foreach
// CART discount
if ($_POST["cart_discount_sum"] != $shop->CartDiscountSum) {
    q("DELETE FROM Netshop_OrderDiscounts
       WHERE Order_ID='".intval($_POST['message'])."'
         AND Item_Type=0
         AND Item_ID=0");

    if ($_POST["cart_discount_sum"]) {
        q("INSERT INTO Netshop_OrderDiscounts
          SET Order_ID='".intval($_POST['message'])."',
              Item_Type=0,
              Item_ID=0,
              Discount_ID=0,
              Discount_Name='".$db->escape(NETCAT_MODULE_NETSHOP_DISCOUNT_MANUAL)."',
              Discount_Sum= '".$db->escape($_POST['cart_discount_sum'])."',
              PriceMinimum=0");
    }
}
?>