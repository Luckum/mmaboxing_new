<?php

/* $Id: admin.inc.php 7201 2012-06-14 16:04:00Z ewind $ */

function banner_NavBar() {
    global $phase, $db;
    global $date_start_y, $date_start_m, $date_start_d;
    global $date_end_y, $date_end_m, $date_end_d, $full;

    $report[0] = NETCAT_MODULE_BANNER_MAINSTATS;
    $report[1] = NETCAT_MODULE_BANNER_BANNERS;
    $report[2] = NETCAT_MODULE_BANNER_ZONES;
    $report[3] = NETCAT_MODULE_BANNER_CAMPAIGNS;
    $report[4] = NETCAT_MODULE_BANNER_PAGES;

    list($today, $yesterday, $weekago, $monthago, $wholeperiod) = $db->get_row("SELECT CURRENT_DATE() AS Today,DATE_ADD(CURRENT_DATE(),INTERVAL -1 DAY) AS Yesterday,DATE_ADD(CURRENT_DATE(),INTERVAL -7 DAY) AS WeekAgo,DATE_ADD(CURRENT_DATE(),INTERVAL -30 DAY) AS MonthAgo,MIN(Date) AS WholePeriod FROM Banner_StatsBanner", ARRAY_N);

    $today_d = substr($today, 8, 2);
    $today_m = substr($today, 5, 2);
    $today_y = substr($today, 0, 4);

    $yesterday_d = substr($yesterday, 8, 2);
    $yesterday_m = substr($yesterday, 5, 2);
    $yesterday_y = substr($yesterday, 0, 4);

    $weekago_d = substr($weekago, 8, 2);
    $weekago_m = substr($weekago, 5, 2);
    $weekago_y = substr($weekago, 0, 4);

    $monthago_d = substr($monthago, 8, 2);
    $monthago_m = substr($monthago, 5, 2);
    $monthago_y = substr($monthago, 0, 4);

    $wholeperiod_d = substr($wholeperiod, 8, 2);
    $wholeperiod_m = substr($wholeperiod, 5, 2);
    $wholeperiod_y = substr($wholeperiod, 0, 4);

    echo "<table border=0 cellpadding=0 cellspacing=0 width=100%><form action=admin.php method=get>";
    echo "<tr><td width=100%><select onchange=\"this.form.full.value='';this.form.submit()\" name=phase>";
    for ($i = 0; $i <= count($report); $i++)
        if ($report[$i]) echo "<option value=$i".($i == $phase ? " selected" : "").">".$report[$i];

    echo "</select></td>";

    if ($phase) {
        echo "<td nowrap>".NETCAT_MODULE_BANNER_PERFROM." ";
        echo "<input type=text size=2 name=date_start_d value='".$date_start_d."'>";
        echo "<input type=text size=2 name=date_start_m value='".$date_start_m."'>";
        echo "<input type=text size=4 name=date_start_y value='".$date_start_y."'>";
        echo " ".NETCAT_MODULE_BANNER_PERTO." ";
        echo "<input type=text size=2 name=date_end_d value='".$date_end_d."'>";
        echo "<input type=text size=2 name=date_end_m value='".$date_end_m."'>";
        echo "<input type=text size=4 name=date_end_y value='".$date_end_y."'>";
        echo " <input type=submit value='".NETCAT_MODULE_BANNER_BTT_SHOW."'></td></tr>";
        echo "<tr><td colspan=2><hr color=cccccc size=1></td></tr>";
        echo "<tr><td><a href='?export_excel=1&".http_build_query($_GET)."'>".NETCAT_MODULE_BANNER_EXCEL_EXPORT."</a></td><td><font size=-2>";

        $link_pref = "?phase=".(!$phase ? 1 : $phase)."&date_end_d=".date("d")."&date_end_m=".date("m")."&date_end_y=".date("Y").($full ? "&full=$full" : "");
        echo "<a href=".$link_pref."&date_start_d=".$today_d."&date_start_m=".$today_m."&date_start_y=".$today_y.">".NETCAT_MODULE_BANNER_TODAY."</a>";
        echo " | <a href=?phase=".(!$phase ? 1 : $phase)."&date_end_d=".$yesterday_d."&date_end_m=".$yesterday_m."&date_end_y=".$yesterday_y."&date_start_d=".$yesterday_d."&date_start_m=".$yesterday_m."&date_start_y=".$yesterday_y.($full ? "&full=$full" : "").">".NETCAT_MODULE_BANNER_YESTERDAY."</a>";
        echo " | <a href=".$link_pref."&date_start_d=".$weekago_d."&date_start_m=".$weekago_m."&date_start_y=".$weekago_y.">".NETCAT_MODULE_BANNER_WEEK."</a>";
        echo " | <a href=".$link_pref."&date_start_d=".$monthago_d."&date_start_m=".$monthago_m."&date_start_y=".$monthago_y.">".NETCAT_MODULE_BANNER_MONTH."</a>";
        echo " | <a href=".$link_pref."&date_start_d=".$wholeperiod_d."&date_start_m=".$wholeperiod_m."&date_start_y=".$wholeperiod_y.">".NETCAT_MODULE_BANNER_ALLPERIOD;
        echo "</td>";
    }
    echo "<input type=hidden name=full value='$full'>";
    echo "</tr></form></table><br>";
}

function banner_ShowReportTotal( $excel = false ) {
    global $nc_core, $db;

    $MODULE_VARS = $nc_core->modules->get_module_vars();

    extract($MODULE_VARS['banner']);

    // banner
    $res = $db->get_var("SELECT COUNT(*) FROM Message${BANNER_TABLE}");
    if ($db->num_rows) {
        $banners_total = $res;
    }

    $res = $db->get_var("SELECT COUNT(*) FROM Banner_CampaignBanner");
    if ($db->num_rows) {
        $banners_active = $res;
    }

    $res = $db->get_row("SELECT SUM(Shows),SUM(Clicks),(SUM(Clicks)*100)/SUM(Shows) FROM Banner_StatsBanner", ARRAY_N);
    if ($db->num_rows) {
        list($banners_shows, $banners_clicks, $banners_ctr) = $res;
    }

    // zone
    $res = $db->get_var("SELECT COUNT(*) FROM Message${ZONE_TABLE}");
    if ($db->num_rows) {
        $zone_total = $res;
    }

    $res = $db->get_var("SELECT COUNT(*) FROM Banner_CampaignZone");
    if ($db->num_rows) {
        $zone_active = $res;
    }

    $res = $db->get_row("SELECT SUM(Shows),SUM(Clicks),(SUM(Clicks)*100)/SUM(Shows) FROM Banner_StatsZone", ARRAY_N);
    if ($db->num_rows) {
        list($zone_shows, $zone_clicks, $zone_ctr) = $res;
    }

    // zone
    $res = $db->get_var("SELECT COUNT(*) FROM Message${CAMPAIGN_TABLE}");
    if ($db->num_rows) {
        $campaign_total = $res;
    }

    $res = $db->get_var("SELECT COUNT(*) FROM Message${CAMPAIGN_TABLE} WHERE Checked=1 AND Showed<Shows");
    if ($db->num_rows) {
        $campaign_active = $res;
    }

    $res = $db->get_row("SELECT SUM(Shows),SUM(Clicks),(SUM(Clicks)*100)/SUM(Shows) FROM Banner_StatsCampaign", ARRAY_N);
    if ($db->num_rows) {
        list($campaign_shows, $campaign_clicks, $campaign_ctr) = $res;
    }

    echo "<table border=0 cellpadding=0 cellspacing=0 width=99%><tr><td><table class='border-bottom' border=0 cellpadding=0 cellspacing=0 width=100% style='text-align:center'>";
    echo "<tr>";
    echo "<td width=16%><font size=-1><br></td>";
    echo "<td width=16%><font size=-1>".NETCAT_MODULE_BANNER_COUNT."</td>";
    echo "<td width=16%><font size=-1>".NETCAT_MODULE_BANNER_ACTIVE."</td>";
    echo "<td width=16%><font size=-1>".NETCAT_MODULE_BANNER_SHOWS."</td>";
    echo "<td width=16%><font size=-1>".NETCAT_MODULE_BANNER_CLICKS."</td>";
    echo "<td width=16%><font size=-1>".NETCAT_MODULE_BANNER_CTR."</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td style='text-align:left'><font size=-1>".NETCAT_MODULE_BANNER_BANNERS."</td>";
    echo "<td><font size=-1>".$banners_total."</td>";
    echo "<td><font size=-1>".$banners_active."</td>";
    echo "<td><font size=-1>".$banners_shows."</td>";
    echo "<td><font size=-1>".$banners_clicks."</td>";
    echo "<td><font size=-1>".$banners_ctr."%</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td style='text-align:left'><font size=-1>".NETCAT_MODULE_BANNER_ZONES."</td>";
    echo "<td><font size=-1>".$zone_total."</td>";
    echo "<td><font size=-1>".$zone_active."</td>";
    echo "<td><font size=-1>".$zone_shows."</td>";
    echo "<td><font size=-1>".$zone_clicks."</td>";
    echo "<td><font size=-1>".$zone_ctr."%</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td style='text-align:left'><font size=-1>".NETCAT_MODULE_BANNER_CAMPAIGNS."</td>";
    echo "<td><font size=-1>".$campaign_total."</td>";
    echo "<td><font size=-1>".$campaign_active."</td>";
    echo "<td><font size=-1>".$campaign_shows."</td>";
    echo "<td><font size=-1>".$campaign_clicks."</td>";
    echo "<td><font size=-1>".$campaign_ctr."%</td>";
    echo "</tr>";

    echo "</table></td></tr></table>";
	
    if ( $excel ) {
        unset($excel);
        $excel[0][0] = "";
        $excel[0][1] = NETCAT_MODULE_BANNER_COUNT;
        $excel[0][2] = NETCAT_MODULE_BANNER_ACTIVE;
        $excel[0][3] = NETCAT_MODULE_BANNER_SHOWS;
        $excel[0][4] = NETCAT_MODULE_BANNER_CLICKS;
        $excel[0][5] = NETCAT_MODULE_BANNER_CTR;

        $excel[1][0] = NETCAT_MODULE_BANNER_BANNERS;
        $excel[1][1] = $banners_total;
        $excel[1][2] = $banners_active;
        $excel[1][3] = $banners_shows;
        $excel[1][4] = $banners_clicks;
        $excel[1][5] = $banners_ctr;		

        $excel[2][0] = NETCAT_MODULE_BANNER_ZONES;
        $excel[2][1] = $zone_total;
        $excel[2][2] = $zone_active;
        $excel[2][3] = $zone_shows;
        $excel[2][4] = $zone_clicks;
        $excel[2][5] = $zone_ctr;	

        $excel[3][0] = NETCAT_MODULE_BANNER_CAMPAIGNS;
        $excel[3][1] = $campaign_total;
        $excel[3][2] = $campaign_active;
        $excel[3][3] = $campaign_shows;
        $excel[3][4] = $campaign_clicks;
        $excel[3][5] = $campaign_ctr;

        banner_writeExelFile("report_total_".date("d.m.Y").".xlsx", $excel);
    }
}

function banner_ShowReportFull($date_start, $date_end, $id, $keyword, $comment) {
    global $db;

    $date_start = $db->escape($date_start);
    $date_end = $db->escape($date_end);
    $keyword = $db->escape($keyword);
    $id = intval($id);

    $res = $db->get_results("SELECT `Hour`, SUM(Shows),SUM(Clicks),(SUM(Clicks)*100)/SUM(Shows) AS CTR
                             FROM `Banner_Stats".$keyword."`
                             WHERE Date>='".$date_start."' AND Date<='".$date_end."'
                             AND `".$keyword."_ID`= '".$id."'
                             GROUP BY `Hour`
                             ORDER BY `Hour` ", ARRAY_N);
    if (!$count = $db->num_rows) return;

    echo "<b><font size=+1>$comment #$id</font></b><hr size=1 color=cccccc>";
    echo "<b><font size=+1></b></font><br><br>";

    echo "<table border=0 cellpadding=0 cellspacing=0 width=100%><tr><td bgcolor=cccccc><table border=0 cellpadding=2 cellspacing=1 width=100%>";
    echo "<tr>";
    echo "<td width=25% bgcolor=eeeeee><font color=gray size=-1>".NETCAT_MODULE_BANNER_HOUR."</td>";
    echo "<td width=25% bgcolor=eeeeee><font color=gray size=-1>".NETCAT_MODULE_BANNER_SHOWS."</td>";
    echo "<td width=25% bgcolor=eeeeee><font color=gray size=-1>".NETCAT_MODULE_BANNER_CLICKS."</td>";
    echo "<td width=25% bgcolor=eeeeee><font color=gray size=-1>".NETCAT_MODULE_BANNER_CTRP."</td>";
    echo "</tr>";

    $sum_shows = 0;
    $sum_clicks = 0;
    $sum_ctr = 0;

    for ($i = 0; $i < $count; $i++) {
        list($hour_tmp, $shows_tmp, $clicks_tmp, $ctr_tmp) = $res[$i];

        $shows[$hour_tmp] = $shows_tmp;
        $clicks[$hour_tmp] = $clicks_tmp;
        $ctr[$hour_tmp] = $ctr_tmp;

        $sum_shows += $shows_tmp;
        $sum_clicks += $clicks_tmp;
        $sum_ctr += $ctr_tmp;
    }

    for ($i = 0; $i < 24; $i++) {
        echo "<tr>";
        echo "<td bgcolor=white><font size=-1><b>".$i."</td>";
        echo "<td bgcolor=white><font size=-1>".$shows[$i]."</td>";
        echo "<td bgcolor=white><font size=-1>".$clicks[$i]."</td>";
        echo "<td bgcolor=white><font size=-1>".$ctr[$i]."</td>";
        echo "</tr>";
    }

    echo "<tr>";
    echo "<td bgcolor=eeeeee><font size=-1><b>".NETCAT_MODULE_BANNER_TOTAL."</td>";
    echo "<td bgcolor=eeeeee><font size=-1><b>".$sum_shows."</td>";
    echo "<td bgcolor=eeeeee><font size=-1><b>".$sum_clicks."</td>";
    echo "<td bgcolor=eeeeee><font size=-1><b>".$sum_ctr."</td>";
    echo "</tr>";

    echo "</table></td></tr></table><br>";


    echo "<br><br><b><font size=+1>".NETCAT_MODULE_BANNER_MSG_AVGCLICKSCOUNTPERPERIOD."</b></font><br><br>";
    banner_ShowReportFullAvg($date_start, $date_end, $id, $keyword, $comment);
}

function banner_ShowReportFullAvg($date_start, $date_end, $id, $keyword, $comment) {
    global $db;

    $date_start = $db->escape($date_start);
    $date_end = $db->escape($date_end);
    $keyword = $db->escape($keyword);
    $id = intval($id);

    $res = $db->get_results("SELECT `Hour`, AVG(Shows),AVG(Clicks),AVG((Clicks*100)/Shows) AS CTR
                                    FROM `Banner_Stats".$keyword."`
                                    WHERE `Date` >='".$date_start."' AND `Date` <='".$date_end."'
                                    AND `".$keyword."_ID` = '".$id."'
                                    GROUP BY `Hour`
                                    ORDER BY `Hour`", ARRAY_N);
    if (!$count = $db->num_rows) return;

    echo "<table border=0 cellpadding=0 cellspacing=0 width=100%><tr><td bgcolor=cccccc><table border=0 cellpadding=2 cellspacing=1 width=100%>";
    echo "<tr>";
    echo "<td width=25% bgcolor=eeeeee><font color=gray size=-1>".NETCAT_MODULE_BANNER_HOUR."</td>";
    echo "<td width=25% bgcolor=eeeeee><font color=gray size=-1>".NETCAT_MODULE_BANNER_SHOWS."</td>";
    echo "<td width=25% bgcolor=eeeeee><font color=gray size=-1>".NETCAT_MODULE_BANNER_CLICKS."</td>";
    echo "<td width=25% bgcolor=eeeeee><font color=gray size=-1>".NETCAT_MODULE_BANNER_CTRP."</td>";
    echo "</tr>";

    $sum_shows = 0;
    $sum_clicks = 0;
    $sum_ctr = 0;

    for ($i = 0; $i < $count; $i++) {
        list($hour_tmp, $shows_tmp, $clicks_tmp, $ctr_tmp) = $res[$i];

        $shows[$hour_tmp] = $shows_tmp;
        $clicks[$hour_tmp] = $clicks_tmp;
        $ctr[$hour_tmp] = $ctr_tmp;

        $sum_shows += $shows_tmp;
        $sum_clicks += $clicks_tmp;
        $sum_ctr += $ctr_tmp;
    }

    for ($i = 0; $i < 24; $i++) {
        echo "<tr>";
        echo "<td bgcolor=white><font size=-1><b>".$i."</td>";
        echo "<td bgcolor=white><font size=-1>".$shows[$i]."</td>";
        echo "<td bgcolor=white><font size=-1>".$clicks[$i]."</td>";
        echo "<td bgcolor=white><font size=-1>".$ctr[$i]."</td>";
        echo "</tr>";
    }

    echo "<tr>";
    echo "<td bgcolor=eeeeee><font size=-1><b>".NETCAT_MODULE_BANNER_TOTAL."</td>";
    echo "<td bgcolor=eeeeee><font size=-1><b>".$sum_shows."</td>";
    echo "<td bgcolor=eeeeee><font size=-1><b>".$sum_clicks."</td>";
    echo "<td bgcolor=eeeeee><font size=-1><b>".$sum_ctr."</td>";
    echo "</tr>";

    echo "</table></td></tr></table><br>";
}

function banner_ShowReport($date_start, $date_end, $output = false ) {
    global $db, $full, $phase;

    $date_start = $db->escape($date_start);
    $date_end = $db->escape($date_end);

    switch ($phase) {
        case 1: $keyword = "Banner";
            $comment = NETCAT_MODULE_BANNER_BANNER;
            break;
        case 2: $keyword = "Zone";
            $comment = NETCAT_MODULE_BANNER_ZONE;
            break;
        case 3: $keyword = "Campaign";
            $comment = NETCAT_MODULE_BANNER_CAMPAIGN;
            break;
    }

    if ($full) {
        banner_ShowReportFull($date_start, $date_end, $full, $keyword, $comment);
        return;
    }

    $res = $db->get_results("SELECT `".$keyword."_ID` ,SUM(Shows) AS Shows,SUM(Clicks) AS Clicks,((SUM(Clicks)*100)/SUM(Shows)) AS CTR
                             FROM `Banner_Stats".$keyword."`
                             WHERE `Date`>='".$date_start."' AND `Date`<='".$date_end."'
                             GROUP BY `".$keyword."_ID`
                             ORDER BY `CTR`
                             DESC LIMIT 50", ARRAY_N);
    if (!$count = $db->num_rows) return;

    echo "<table border=0 cellpadding=0 cellspacing=0 width=100%><tr><td bgcolor=cccccc><table border=0 cellpadding=2 cellspacing=1 width=100%>";
    echo "<tr>";
    echo "<td width=25% bgcolor=eeeeee><font color=gray size=-1>$comment</td>";
    echo "<td width=25% bgcolor=eeeeee><font color=gray size=-1>".NETCAT_MODULE_BANNER_SHOWS."</td>";
    echo "<td width=25% bgcolor=eeeeee><font color=gray size=-1>".NETCAT_MODULE_BANNER_CLICKS."</td>";
    echo "<td width=25% bgcolor=eeeeee><font color=gray size=-1>".NETCAT_MODULE_BANNER_CTR."</td>";
    echo "</tr>";

    $excel[0][] = $comment ;
    $excel[0][] = NETCAT_MODULE_BANNER_SHOWS ;
    $excel[0][] = NETCAT_MODULE_BANNER_CLICKS ;
    $excel[0][] = NETCAT_MODULE_BANNER_CTR ;
	
    for ($i = 0; $i < $count; $i++) {
        list($banner, $shows, $clicks, $ctr) = $res[$i];

        echo "<tr>";
        echo "<td bgcolor=white><font size=-1><a href=?phase=$phase&date_start_y=".$date_start_y."&date_start_m=".$date_start_m."&date_start_d=".$date_start_d."&date_end_y=".$date_end_y."&date_end_m=".$date_end_m."&date_end_d=".$date_end_d."&full=$banner>$comment #$banner</a></td>";
        echo "<td bgcolor=white><font size=-1>".$shows."</td>";
        echo "<td bgcolor=white><font size=-1>".$clicks."</td>";
        echo "<td bgcolor=white><font size=-1>".$ctr."%</td>";
        echo "</tr>";
	if ( $output ) {
            $excel [($i + 1)] [] = $comment ."#$banner";
            $excel [($i + 1)] [] = $shows;
            $excel [($i + 1)] [] = $clicks;
            $excel [($i + 1)] [] = $ctr;
        }
    }

    echo "</table></td></tr></table>";
	
    if ( $output ) {
	banner_writeExelFile("report_".date("d.m.Y").".xlsx", $excel);
    }
}

function banner_ShowReportReferer($date_start, $date_end, $output = false) {
    global $db, $ref;

    global $phase;
    global $date_start_d, $date_start_m, $date_start_y;
    global $date_end_d, $date_end_m, $date_end_y;

    $date_start = $db->escape($date_start);
    $date_end = $db->escape($date_end);

    $res = $db->get_results("SELECT Referer,SUM(Shows) AS Shows,SUM(Clicks) AS Clicks,(SUM(Clicks)*100)/SUM(Shows) AS CTR FROM Banner_StatsReferer WHERE Date>='".$date_start."' AND Date<='".$date_end."' GROUP BY Referer ORDER BY CTR DESC LIMIT 50", ARRAY_N);
    if (!$count = $db->num_rows) return;

    echo "<table border=0 cellpadding=0 cellspacing=0 width=100%><tr><td bgcolor=cccccc><table border=0 cellpadding=2 cellspacing=1 width=100%>";
    echo "<tr>";
    echo "<td width=50% bgcolor=eeeeee><font color=gray size=-1>".NETCAT_MODULE_BANNER_PAGE."</td>";
    echo "<td width=25% bgcolor=eeeeee><font color=gray size=-1>".NETCAT_MODULE_BANNER_SHOWS."</td>";
    echo "<td width=25% bgcolor=eeeeee><font color=gray size=-1>".NETCAT_MODULE_BANNER_CLICKS."</td>";
    echo "<td width=25% bgcolor=eeeeee><font color=gray size=-1>".NETCAT_MODULE_BANNER_CTR."</td>";
    echo "</tr>";

    $excel[0][] = NETCAT_MODULE_BANNER_PAGE ;
    $excel[0][] = NETCAT_MODULE_BANNER_SHOWS ;
    $excel[0][] = NETCAT_MODULE_BANNER_CLICKS ;
    $excel[0][] = NETCAT_MODULE_BANNER_CTR ;
    
    for ($i = 0; $i < $count; $i++) {
        list($referer, $shows, $clicks, $ctr) = $res[$i];

        echo "<tr>";
        echo "<td bgcolor=white><font size=-1><a href=http://".$referer." target=_blank>".(strlen($referer) > 50 ? substr($referer, 0, 50)." ..." : $referer)."</a></td>";
        echo "<td bgcolor=white><font size=-1>".$shows."</td>";
        echo "<td bgcolor=white><font size=-1>".$clicks."</td>";
        echo "<td bgcolor=white><font size=-1>".$ctr."%</td>";
        echo "</tr>";
	if ( $output ) {
            $excel [($i + 1)] [] = (strlen($referer) > 50 ? substr($referer, 0, 50)." ..." : $referer);
            $excel [($i + 1)] [] = $shows;
            $excel [($i + 1)] [] = $clicks;
            $excel [($i + 1)] [] = $ctr;
        }
    }

    echo "</table></td></tr></table>";
	
    if ( $output ) {
	banner_writeExelFile("report_referer_".date("d.m.Y").".xlsx", $excel);
    }
}
?>