<?php

require '../../../no_header.inc.php';

/**
 * User list as HTML table
 */

/* @var nc_db $db */
$db = nc_core('db');

/** @var nc_input $input */
$input = nc_core('input');
$group_id = (int)$input->fetch_get_post('group_id');

$result = $db->get_results("SELECT *
                              FROM `User`
                             WHERE `PermissionGroup_ID` = $group_id", ARRAY_A);

/** @var nc_ui $ui */
$ui = nc_core('ui');

if (!$result) {
    echo "<div class='no_results'>",
            $ui->alert->info(NETCAT_MODULE_NETSHOP_CONDITION_USER_LIST_NO_RESULTS),
         "</div>";
}
else {
    // Using nc_ui in this case is too memory-consuming
    echo "<table class='nc-table nc--wide nc--striped nc--hovered'>\n";
    foreach ($result as $row) {
        $name = "[".$row[nc_core()->AUTHORIZE_BY]."] $row[ForumName]";
        echo "<tr><td class='item' data-user-id='$row[User_ID]'>" .
                 "<span class='essence-id'>$row[User_ID].</span> <span class='essence-caption'>$name</span>" .
             "</td></tr>\n";
    }
    echo '</table>';
}