<?php
if ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
    require_once '../../../vars.inc.php';
    require_once '../../../index.php';
    require_once 'function.inc.php';
    
    $start = isset($_POST['start']) ? $_POST['start'] : 0;
    $cnt = isset($_POST['cnt']) ? $_POST['cnt'] : 0;
    $action = $_POST['action'];
    $s_type = isset($_POST['s_type']) ? $_POST['s_type'] : 0;
    $weight_cat = isset($_POST['cat']) ? $_POST['cat'] : 0;
    $url = isset($_POST['url']) ? $_POST['url'] : '';
    $fighter = isset($_POST['fighter']) ? $_POST['fighter'] : '';
    $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
    $end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '';
    $search = isset($_POST['search']) ? "&search=" . $_POST['search'] : '';
    $tag = isset($_POST['tag']) ? "&tag=" . $_POST['tag'] : '';
    $is_mob = isset($_POST['is_mob']) && $_POST['is_mob'] == 1 ? "&is_mob=1" : "";
    $cur_type = isset($_POST['cur_type']) ? "&cur_type=3" : '';
    
    switch ($action) {
        case "news":
            echo s_list_class(3,1,"&recNum=" . $cnt . "&nc_ctpl=2024&curPos=" . $start . "&s_type=" . $s_type . $search . $is_mob . $cur_type . $tag);
        break;
        case "read_also":
            $tags = $_POST['tags'];
            $mesId = $_POST['id'];
            echo s_list_class(3,1,"&nc_ctpl=2018&recNum=" . $cnt . "&searchTags=" . $tags . "&curID=" . $mesId . "&curPos=" . $start);
        break;
        case "videos":
            echo s_list_class(3,1,"&recNum=" . $cnt . "&nc_ctpl=2034&curPos=" . $start . "&s_type=" . $s_type . $is_mob);
        break;
        case "articles":
            echo s_list_class(3,1,"&recNum=" . $cnt . "&nc_ctpl=2036&curPos=" . $start . "&s_type=" . $s_type . $is_mob);
        break;
        case "blogs":
            echo s_list_class(3,1,"&recNum=" . $cnt . "&nc_ctpl=2037&curPos=" . $start . "&s_type=" . $s_type . $is_mob);
        break;
        case "mbps":
            echo s_list_class(3,1,"&recNum=" . $cnt . "&nc_ctpl=2024&curPos=" . $start . "&s_type=" . $s_type . $is_mob);
        break;
        case "weights":
            echo s_list_class(18,23,"&nc_ctpl=2038&weight_cat=" . $weight_cat . "&s_type=" . $s_type);
        break;
        case "archive":
            echo s_list_class(3,1,"&recNum=" . $cnt . "&nc_ctpl=2039&curPos=" . $start . "&url=" . $url . "&fighter=" . $fighter);
        break;
        case "events":
            echo s_list_class(10,28,"&recNum=10&nc_ctpl=2044&showEvents=1" . "&s_type=" . $s_type);
        break;
        case "results":
            echo s_list_class(10,28,"&recNum=10&nc_ctpl=2044&showResults=1" . "&s_type=" . $s_type);
        break;
        case "more_events":
            echo s_list_class(10,28,"&recNum=" . $cnt . "&nc_ctpl=2044&showEvents=1&curPos=" . $start . "&s_type=" . $s_type);
        break;
        case "more_results":
            echo s_list_class(10,28,"&recNum=" . $cnt . "&nc_ctpl=2044&showResults=1&curPos=" . $start . "&s_type=" . $s_type);
        break;
        case "events_days":
            echo s_list_class(10,28,"&nc_ctpl=2044&s_type=" . $s_type . "&s_date=" . $start_date . "&e_date=" . $end_date);
        break;
        case "vote":
            addArrogRes($_POST['vid'], $_POST['cache'], $_POST['class_id'], $_POST['vote_check']);
            echo s_list_class(24,26);
        break;
        case "set_tw_cnt":
            setTwitCount($_POST['page']);
        break;
        case "get_tw_cnt":
            $twits = getTwitCount($_POST['page']);
            echo $twits['twits_cnt'];
        break;
        case "set_fb_cnt":
            setFBCount($_POST['page']);
        break;
        case "get_fb_cnt":
            $fbs = getFbCount($_POST['page']);
            echo $fbs['fb_cnt'];
        break;
        case "set_vk_cnt":
            setVkCount($_POST['page']);
        break;
        case "get_vk_cnt":
            $vks = getVkCount($_POST['page']);
            echo $vks['vk_cnt'];
        break;
        case "more_ratings":
            echo s_list_class(18,23,"&recNum=" . $cnt . "&nc_ctpl=2038&curPos=" . $start . "&s_type=" . $s_type);
        break;
        case "set_tw_cnt_evt":
            setTwitCountEvt($_POST['page']);
        break;
        case "get_tw_cnt_evt":
            $twits = getTwitCountEvt($_POST['page']);
            echo $twits['twits_cnt'];
        break;
        case "set_fb_cnt_evt":
            setFBCountEvt($_POST['page']);
        break;
        case "get_fb_cnt_evt":
            $fbs = getFbCountEvt($_POST['page']);
            echo $fbs['fb_cnt'];
        break;
        case "set_vk_cnt_evt":
            setVkCountEvt($_POST['page']);
        break;
        case "get_vk_cnt_evt":
            $vks = getVkCountEvt($_POST['page']);
            echo $vks['vk_cnt'];
        break;
        case "set_tw_cnt_fgt":
            setTwitCountFgt($_POST['page']);
        break;
        case "get_tw_cnt_fgt":
            $twits = getTwitCountFgt($_POST['page']);
            echo $twits['twits_cnt'];
        break;
        case "set_fb_cnt_fgt":
            setFBCountFgt($_POST['page']);
        break;
        case "get_fb_cnt_fgt":
            $fbs = getFbCountFgt($_POST['page']);
            echo $fbs['fb_cnt'];
        break;
        case "set_vk_cnt_fgt":
            setVkCountFgt($_POST['page']);
        break;
        case "get_vk_cnt_fgt":
            $vks = getVkCountFgt($_POST['page']);
            echo $vks['vk_cnt'];
        break;
        case "set_resolution":
            set_resolution($_POST['resol']);
        break;
        case "chg_breadcrumbs":
            echo setBreadcrumbs($_POST['url'], $_POST['chg_action']);
        break;
        case "get_banner":
            $result = getBanner();
            echo json_encode($result);
        break;
    }
}
