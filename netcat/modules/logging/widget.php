<?php

$NETCAT_FOLDER  = realpath(dirname(__FILE__) . '/../../../') . DIRECTORY_SEPARATOR;

require_once $NETCAT_FOLDER . 'vars.inc.php';
require_once $ADMIN_FOLDER . 'function.inc.php';
// require_once $ADMIN_FOLDER . 'admin.inc.php';

// require_once 'function.inc.php';
require_once ($MODULE_FOLDER."logging/nc_logging_admin.class.php");

$logging = nc_logging::get_object();
$nc_logging_admin = new nc_logging_admin();

switch ($sort) {
    case "event":
        $sort_field = "l.`Event`";
        break;
    case "user":
        $sort_field = "u.`Login`";
        break;
    default:
        $sort_field = "l.`Date`";
}

switch ($sortDir) {
    case "asc":
        $sort_dir_field = "ASC";
        break;
    default:
        $sort_dir_field = "DESC";
}

$query_where_arr = array();

if ($sortViewType && in_array($sortViewType, $allowedActions)) {
    $query_where_arr[] = "l.`Event` REGEXP '^".$sortViewType."'";
}

if ($sortViewEssence && in_array($sortViewEssence, $allowedEssences)) {
    $query_where_arr[] = "l.`Event` REGEXP '^(add|update|drop|check|uncheck|authorize)".$sortViewEssence."$'";
}

$data = $nc_core->db->get_results("SELECT SQL_CALC_FOUND_ROWS l.*, u.`".$nc_core->AUTHORIZE_BY."` AS Login
FROM `Logging` AS l
LEFT JOIN `User` AS u ON l.`User_ID` = u.`User_ID`
".(!empty($query_where_arr) ? "WHERE ".join(" AND ", $query_where_arr) : "")."
ORDER BY ".$sort_field." ".$sort_dir_field.", l.`ID` ".$sort_dir_field."
LIMIT 10", ARRAY_A);

$log_table = '';

if (!empty($data)) {
    $log_table .= "<table class='nc-table nc--small'>
<col width='3%'/><col/><col width='60%'/><col width='8%'/><col width='8%'/>
<tr>
  <th nowrap class='nc-text-center'>".'<a href="'.$GLOBALS['HTTP_ROOT_PATH'].'modules/logging/admin.php" onclick="return nc.ui.dashboard.fullscreen(this)" title="'.NETCAT_MODULE_LOGGING.'">
        <i class="nc-icon nc--mod-logging nc--white"></i>
    </a>'."</th>
  <th nowrap>".NETCAT_MODULE_LOGGING_DATA_EVENT."</th>

  <th nowrap>".NETCAT_MODULE_LOGGING_DATA_DATE."</th>
  <th nowrap class='nc-text-center'>".NETCAT_MODULE_LOGGING_DATA_USER."</th>
</tr>";

    $totRows = $nc_core->db->get_var("SELECT FOUND_ROWS()");

    foreach ($data as $row) {
        // determine event name
        preg_match("/^(add|update|drop|check|uncheck|authorize)/is", $row['Event'], $matches);

        // default color
        $color = "#FFFFFF";

        // set colors
        if (!empty($matches) && isset($matches[1])) {
            switch ($matches[1]) {
                case "add":
                    $color = "nc--green";
                    $tcolor = "nc-text-green";
                    break;
                case "update":
                case "check":
                case "uncheck":
                    $color = "nc--yellow";
                    $tcolor = "nc-text-yellow";
                    break;
                case "drop":
                    $color = "nc--red";
                    $tcolor = "nc-text-red";
                    break;
                case "authorize":
                    $color = "nc--blue";
                    $tcolor = "nc-text-blue";
                    break;
            }
        }

        // row element
        $log_table .= "<tr>".
        "<td class='nc-text-center'><span class='nc-label ".$color."'>".$row['ID']."</label></td>".
        "<td nowrap>".$nc_core->event->event_name($row['Event'])."</td>".
        // "<td><div style='white-space:nowrap; height:1.2em; overflow:hidden;'>".$row['Info']."</div></td>".
        "<td nowrap>".$row['Date']."</td>".
        "<td class='nc-text-center'>".($row['User_ID'] ? "<a href='".$nc_core->ADMIN_PATH."user/index.php?phase=4&UserID=".$row['User_ID']."'>".$row['Login']."</a>" : NETCAT_MODULE_LOGGING_DATA_SYSTEM)."</td>".
        "</tr>";
    }

    $log_table .= "</table>";
}
?>


<?//=$log_table ?>
<div class="nc-widget-scrolled">
    <?=$log_table ?>
</div>