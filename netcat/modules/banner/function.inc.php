<?php

/* $Id: function.inc.php 6206 2012-02-10 10:12:34Z denis $ */

function banner_rotate($zone, $number=1, $rand=false, $class = "") {
    global $db, $MODULE_VARS;
    global $catalogue, $sub, $cc, $message;
    global $HTTP_FILES_PATH, $HTTP_ROOT_PATH, $admin_mode;
    global $HTTP_HOST, $REQUEST_URI, $SUB_FOLDER;

    $zone = (int) $zone;
    if (!$zone) return;

    extract($MODULE_VARS['banner']);

    $campaign_cond_ext = '';
    $final_code = '';

    $sub_cond = banner_getparentsections($sub, "e.Section", "e.Section='$sub' ");

    $excluded_scenarios = $db->get_results(
                    "SELECT DISTINCT Script
                     FROM Message{$SCRIPT_EXCLUSION_TABLE} as e
                    WHERE (Site = '$catalogue'
                           AND (Section IS NULL OR Section = 0)
                           AND (Class IS NULL OR Class = 0)
                           AND (Object IS NULL OR Object = 0))
                       OR ((ExcludeSubsections=1 AND ($sub_cond))
                            OR (ExcludeSubsections=0 AND Section=$sub)
                           AND (Class IS NULL OR Class = 0)
                           AND (Object IS NULL OR Object = 0))
                       OR (Class = '$cc'
                           AND (Object = '$message' OR Object IS NULL OR Object = 0))
                       OR ((Class IS NULL OR Class = 0) AND Object = '$message')", ARRAY_N);

    if ($excluded_scenarios) {
        // flatten array
        for ($i = 0; $i < sizeof($excluded_scenarios); $i++) {
            $excluded_scenarios[$i] = $excluded_scenarios[$i][0];
        }

        // join to use in query
        $excluded_scenarios = " AND d.Script NOT IN (".join(',', $excluded_scenarios).") ";
    } else {
        $excluded_scenarios = "";
    }

    list($size_id, $banner_divider) = $db->get_row("SELECT Size, BannerDivider FROM Message{$ZONE_TABLE} WHERE Message_ID=$zone", ARRAY_N);

    for ($nmb = 1; $nmb <= $number; $nmb++) {
        $selectmain = "SELECT a.Message_ID
                         FROM Message{$CAMPAIGN_TABLE} AS a,
                              Banner_CampaignZone AS b
                        WHERE a.Main=1
                          AND a.Checked=1
                          AND b.Zone_ID='$zone'
                          AND a.Message_ID=b.Campaign_ID
                        ORDER BY a.LastUpdated DESC";

        $campaign_id = $db->get_var($selectmain);

        if ($campaign_id) {
            $campaign_cond_ext = " AND c.Message_ID=".$campaign_id;
        }

        $curday = date("w");
        if (!$curday) {
            $curday = 7;
        }

        $multiCond = "";
        if (isset($old_banID) && $old_banID) {

            foreach ($old_banID as $cur)
                $multiCond.=" AND a.Banner_ID<>$cur";
        }

        $fields = "a.Banner_ID,
                   a.Campaign_ID,
                   d.Type,
                   h.BannerSize_Name,
                   d.File,
                   d.Alt,
                   d.Text,
                   g.Field_ID,
                   d.NewWindow";

        $tables = "(Banner_CampaignBanner AS a,
                    Banner_CampaignZone AS b,
                    Message{$CAMPAIGN_TABLE} AS c,
                    Message{$BANNER_TABLE} as d,
                    Message{$ZONE_TABLE} AS e,
                    Message{$SCRIPT_TABLE} AS f,
                    Field AS g)
                    LEFT JOIN Classificator_BannerSize AS h
                           ON h.BannerSize_ID=d.Size";

        $campaign_cond = "((c.Start <= IF((c.Start IS NOT NULL OR c.Start != 0), NOW(), 0)
                          AND c.Stop > IF((c.Stop IS NOT NULL OR c.Stop != 0),  NOW(), 0))
                          AND IF ((c.Shows IS NOT NULL OR c.Shows != 0),
                                  (c.Showed+1),
                                  0)
                              <= IF ((c.Shows IS NOT NULL OR c.Shows != 0),
                                     c.Shows,
                                     0))
                          AND a.Campaign_ID=b.Campaign_ID
                          AND c.Checked=1
                          AND a.Campaign_ID=c.Message_ID".$campaign_cond_ext;

        $banner_cond = "AND f.Message_ID = d.Script AND d.Status=1 AND d.Checked=1 AND ".($size_id ? "c.Size=d.Size AND d.Size=e.Size" : "(c.Size IS NULL OR c.Size = 0) AND (d.Size IS NULL OR d.Size = 0) AND (e.Size IS NULL OR e.Size = 0)")." AND a.Banner_ID=d.Message_ID AND f.Message_ID=d.Script ".($multiCond ? "$multiCond" : "")."";

        $script_cond = "AND f.TimeZone".(date("H") + 1)."=1
                        AND f.Day".$curday."=1
                        $excluded_scenarios";

        $zone_cond = "AND b.Zone_ID='$zone'
	              AND b.Zone_ID=e.Message_ID
	              AND ((e.Site='$catalogue' AND (e.Section IS NULL OR e.Section = 0))
	                    OR (e.Site='$catalogue' AND ($sub_cond))
	                    OR (($sub_cond) AND (e.Site IS NULL OR e.Site = 0))
	                    OR (e.Class='$cc' AND e.Object='$message')
	                    OR (e.Class='$cc' AND (e.Object IS NULL OR e.Object = 0)))";

        $field_cond = "AND g.Class_ID={$BANNER_TABLE}
                       AND g.TypeOfData_ID=6
                       AND g.Field_Name='File'";


        $conditions = "{$campaign_cond} {$zone_cond} {$banner_cond} {$script_cond} {$field_cond}";

        if (!$rand) {
            $res_sort = "a.LastShow";
        } else {
            $res_sort = "RAND()";
        }

        $final_select = "SELECT $fields
                           FROM $tables
                          WHERE $conditions
                          ORDER BY $res_sort
                          LIMIT 1";

        $res = $db->get_row($final_select, ARRAY_N);

        if (!$db->num_rows) {
            continue;
        }
        if ($nmb != 1) {
            $final_code.=$banner_divider;
        }

        list($banner_id, $campaign_id, $banner_type, $banner_size, $banner_file, $banner_alt, $banner_text, $field_id, $banner_new_window) = $res;

        $old_banID[] = $banner_id;

        list($banner_width, $banner_height) = explode("x", $banner_size);
        list($banner_filename, $banner_filesize, $banner_fileformat) = explode(":", $banner_file);

        // выводим рисунки
        $file = $db->get_row("SELECT File_Path, Virt_Name
                                FROM Filetable
                               WHERE Field_ID = '$field_id'
                                 AND Message_ID = '$banner_id'", ARRAY_A);

        if ($file) { // 2.4-style file storage
            $file_url = $SUB_FOLDER.rtrim($HTTP_FILES_PATH, '/').$file["File_Path"]."h_".$file["Virt_Name"];
        } else { // old file storage
            $banner_fileext = strrchr($banner_filename, ".");
            $file_url = $SUB_FOLDER.$HTTP_FILES_PATH.$field_id."_".$banner_id.$banner_fileext;
        }

        $url_for_stats = nc_preg_replace("/\?.*$/", "", "http://".$HTTP_HOST.$REQUEST_URI);

        $random_value = md5(uniqid(rand(), true));

        $click_url = "http://".$HTTP_HOST.$SUB_FOLDER.$HTTP_ROOT_PATH."modules/banner/?b={$banner_id}&amp;c={$campaign_id}&amp;z={$zone}&amp;r={$url_for_stats}&amp;rnd={$random_value}&amp;click=1";
        $click_target = $banner_new_window ? "target='_blank' " : "";

        switch ($banner_type) {
            case 1:
                $code = "<a ".$click_target." href='".$SUB_FOLDER.$HTTP_ROOT_PATH."modules/banner/?b={$banner_id}&amp;c={$campaign_id}&amp;z={$zone}&amp;r={$url_for_stats}&amp;rnd={$random_value}&amp;click=1'>";
                $code .= "<img style='border:none;' class='{$class}' src='{$file_url}' width='{$banner_width}' height='{$banner_height}' alt='{$banner_alt}' /></a>";
                break;

            case 2:
                $click_url = urlencode($click_url);

                $code = "<object classid='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000' codebase='http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0' width='{$banner_width}' height='{$banner_height}' id='banner{$banner_id}'>";
                $code .= "<param name='movie' value='{$file_url}?banner_url={$click_url}&banner_target={$click_target}' />";
                $code .= "<param name='quality' value='high' />";
                $code .= "<param name='FlashVars' value='banner_url={$click_url}&banner_target={$click_target}' />";
                $code .= "<embed src='{$file_url}?banner_url={$click_url}&banner_target={$click_target}' name='banner{$banner_id}' quality='high' bgcolor='ffffff' width='{$banner_width}' height='{$banner_height}' type='application/x-shockwave-flash' pluginspage='http://www.macromedia.com/go/getflashplayer'></embed>";
                $code .= "</object>";
                break;

            case 3:
                $code = $banner_text;
                $code = str_replace("%banner_url%", $click_url, $code);
                $code = str_replace("%banner_target%", $click_target, $code);
                break;
        }

        $adm_mode = ($admin_mode ? "&amp;adm=1" : "");

        $code .= "<img src='".$SUB_FOLDER.$HTTP_ROOT_PATH."modules/banner/index.php?rnd={$random_value}&amp;c={$campaign_id}&amp;z={$zone}&amp;b={$banner_id}&amp;r={$url_for_stats}{$adm_mode}' width='1' height='1' alt='' />";
        $final_code .=$code;
    }

    return ($final_code);
}

function banner_zone($size_id, $priority) {
    global $db, $MODULE_VARS;
    global $catalogue, $sub, $cc, $message, $action;

    extract($MODULE_VARS['banner']);

    if ($size_id) $size_cond = "Size='".$size_id."'"; else
            $size_cond = "(Size IS NULL OR Size = 0)";
    $select = "SELECT Message_ID FROM Message".$ZONE_TABLE." WHERE $size_cond AND ";

    $site_cond = "Site=$catalogue";
    $section_cond = "(".banner_getparentsections($sub, "Section", "Section='$sub' ").")";
    $message_cond = "Class=$cc AND Object=$message";

    if ($cc && $message && $action == "full") $where = $message_cond;
    else $where = $section_cond;

    $limit = " LIMIT $priority,1";

    $res = $db->get_var($select.$where.$limit);
    if (!$db->num_rows) {
        $res = $db->get_var($select.$section_cond.$limit);
        if (!$db->num_rows) {
            $res = $db->get_var($select.$site_cond.$limit);
            if (!$db->num_rows) return false;
        }
    }
    $zone = $res;


    return $zone;
}

function banner_stats($campaign, $zone, $banner, $referer, $rnd, $click) {
    global $db, $MODULE_VARS, $REMOTE_ADDR, $HTTP_USER_AGENT;

    $campaign = intval($campaign);
    $zone = intval($zone);
    $banner = intval($banner);
    $current_url = $db->escape(urldecode($referer));
    $REMOTE_ADDR = $db->escape($REMOTE_ADDR);
    $HTTP_USER_AGENT = $db->escape($HTTP_USER_AGENT);
    $rnd = $db->escape($rnd);
    extract($MODULE_VARS['banner']);

    if (!$click) {
        $db->query("UPDATE `Message".intval($CAMPAIGN_TABLE)."`
                SET Showed=Showed+1,
                LastUpdated=LastUpdated
                WHERE Message_ID='".$campaign."'");
        $db->query("INSERT INTO Banner_Log (Campaign_ID,Zone_ID,Banner_ID,Referer,IP,UserAgent,Code)
                VALUES ($campaign,$zone,$banner,'".$current_url."','".$REMOTE_ADDR."','".$HTTP_USER_AGENT."','".$rnd."')");

        list($microsec, $sec) = explode(" ", microtime());

        $db->query("UPDATE Banner_CampaignBanner
                SET LastShow=".intval($sec + $microsec)."
                WHERE Campaign_ID='".$campaign."'
                AND Banner_ID='".$banner."'");
    } else {
        $db->query("UPDATE Banner_Log SET Clicked=1
    WHERE Campaign_ID='$campaign'
    AND Zone_ID='$zone'
    AND Banner_ID='$banner'
    AND Referer='".$current_url."'
    AND DATE_FORMAT(Created, '%Y-%m-%d')= DATE_FORMAT(CURDATE(),'%Y-%m-%d')
    AND IP='".$REMOTE_ADDR."'
    AND UserAgent='".$HTTP_USER_AGENT."'
    AND Code='".$rnd."'");
    }
    return 0;
}

function banner_url($id) {
    global $db, $MODULE_VARS;

    extract($MODULE_VARS['banner']);

    $res = $db->get_var("SELECT Link FROM Message{$BANNER_TABLE} WHERE Message_ID='".intval($id)."'");
    if ($db->num_rows) {
        $link = $res;
    }
    return $link;
}

function banner_getparentsections($sub, $field, $query, $use_parent_sub_tree=true) {
    global $db;

    // По умолчанию взять данные из parent_sub_tree, не делать
    // дополнительные запросы к базе данных
    if ($use_parent_sub_tree && $GLOBALS["parent_sub_tree"]) {
        foreach ($GLOBALS["parent_sub_tree"] as $row) {
            if (isset($row["Parent_Sub_ID"]) && $row["Parent_Sub_ID"])
                    $query .= " OR ".$db->escape($field)." = '".intval($row['Parent_Sub_ID'])."'";
        }
        return $query;
    }

    $parent = $db->get_var("SELECT `Parent_Sub_ID` FROM `Subdivision` WHERE `Subdivision_ID` = '".$sub."'");

    if (!$parent) return $query;

    $query .= " OR ".$db->escape($field)." = '".intval($parent)."'";

    $query = banner_getparentsections($parent, $field, $query, false);
    return $query;
}

function banner_CreateReports() {
    global $db;

    banner_CreateReportBanner();
    banner_CreateReportCampaign();
    banner_CreateReportZone();
    banner_CreateReportReferer();

    $db->query("DELETE FROM Banner_Log WHERE DATE_FORMAT(Created, '%Y-%m-%d') < DATE_FORMAT(CURRENT_DATE(),'%Y-%m-%d')");
}

function banner_CreateReportBanner() {
    global $db;

    $db->query("DELETE FROM Banner_StatsBanner WHERE Date=CURRENT_DATE()");
    $db->query("INSERT IGNORE INTO Banner_StatsBanner (Date,Hour,Banner_ID,Shows,Clicks) SELECT DATE_FORMAT(Created, '%Y-%m-%d') AS Date, DATE_FORMAT(Created, '%H') AS Hour, Banner_ID, COUNT(*), SUM(Clicked) FROM Banner_Log GROUP BY Date, Hour, Banner_ID");
}

function banner_CreateReportCampaign() {
    global $db;

    $db->query("DELETE FROM Banner_StatsCampaign WHERE Date=CURRENT_DATE()");
    $db->query("INSERT IGNORE INTO Banner_StatsCampaign (Date,Hour,Campaign_ID,Shows,Clicks) SELECT DATE_FORMAT(Created, '%Y-%m-%d') AS Date, DATE_FORMAT(Created, '%H') AS Hour,Campaign_ID,COUNT(*),SUM(Clicked) FROM Banner_Log GROUP BY Date,Hour,Campaign_ID");
}

function banner_CreateReportZone() {
    global $db;

    $db->query("DELETE FROM Banner_StatsZone WHERE Date=CURRENT_DATE()");
    $db->query("INSERT IGNORE INTO Banner_StatsZone (Date,Hour,Zone_ID,Shows,Clicks) SELECT DATE_FORMAT(Created, '%Y-%m-%d') AS Date, DATE_FORMAT(Created, '%H') AS Hour,Campaign_ID,COUNT(*),SUM(Clicked) FROM Banner_Log GROUP BY Date,Hour,Zone_ID");
}

function banner_CreateReportReferer() {
    global $db;

    $db->query("DELETE FROM Banner_StatsReferer WHERE Date=CURRENT_DATE()");
    $db->query("INSERT IGNORE INTO Banner_StatsReferer (Date,Referer,Shows,Clicks) SELECT DATE_FORMAT(Created, '%Y-%m-%d') AS Date,Referer,COUNT(*),SUM(Clicked) FROM Banner_Log GROUP BY Date,Referer");
}
function banner_writeExelFile($filename,$ar_data){
    ob_end_clean();

    global $INCLUDE_FOLDER, $TMP_FOLDER;
    require_once $INCLUDE_FOLDER."lib/excel/PHPExcel.php"; // подключаем фреймворк

    $tmp_filename=$TMP_FOLDER.'tmp.xlsx';

    if(file_exists($tmp_filename)) {
        unlink($tmp_filename);
    }

    $pExcel = new PHPExcel(); //создаем рабочий объект
    $pExcel->setActiveSheetIndex(0); // устанавливаем номер рабочего документа
    $aSheet = $pExcel->getActiveSheet(); // получаем объект рабочего документа
    $writer_i=1;

    foreach($ar_data as $ar){ // читаем массив
        $j=0;
        foreach($ar as $val){
            $aSheet->setCellValueByColumnAndRow($j,$writer_i,"$val"); // записываем данные массива в ячейку
            $j++;
        }
        $writer_i++;
    }

    $objWriter = new PHPExcel_Writer_Excel2007($pExcel); // создаем объект для записи excel в файл
    $objWriter->save($tmp_filename); // выводим данные в excel формате
    header('Content-Type: application/vnd.ms-excel'); // посылаем браузеру нужные заголовки для сохранения файла
    header('Content-Disposition: attachment;filename="'.$filename.'"');
    header('Cache-Control: max-age=0');

    readfile($tmp_filename);

    exit;
}
?>