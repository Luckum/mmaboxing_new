<?php

function getEvents()
{
    global $MODULE_VARS, $INCLUDE_FOLDER;
    require_once $INCLUDE_FOLDER . "lib/simple_html_dom.php";
    
    extract($MODULE_VARS['parser']);
    
    if ($event_parsing_count > 10) {
        $event_parsing_count = 10;
    }
    $events_links = [];
    
    $html = file_get_html($event_url);
    if ($html) {
        $table = $html->find("#upcoming_tab", 1);
        if ($table) {
            $trs = $table->find("tr");
            foreach ($trs as $tr) {
                $link = $tr->find("[itemprop=url]", 0);
                if (isset($link)) {
                    $l_arr = explode("-", $link->href);
                    $numb = array_pop($l_arr);
                    
                    if (!eventExists($numb)) {
                        $events_links[] = $link->href;
                        if (count($events_links) == $event_parsing_count) {
                            break;
                        }
                    }
                }
            }
        }
    }
    
    if (count($events_links)) {
        foreach ($events_links as $event_link) {
            getEvent($event_link);
        }
    }
    
    return true;
}

function getEvent($link)
{
    global $MODULE_VARS, $INCLUDE_FOLDER;
    require_once $INCLUDE_FOLDER . "lib/simple_html_dom.php";
    
    extract($MODULE_VARS['parser']);
    
    $html = file_get_html($parse_base_url . $link);
    if ($html) {
        $main_section = $html->find("[itemprop=subEvent]", 0);
        if (isset($main_section)) {
            $left_side = $main_section->find(".left_side", 0);
            $f_1_link = $left_side->find("[itemprop=url]", 0);
            $l_arr = explode("-", $f_1_link->href);
            $f_1_numb = array_pop($l_arr);
            if (!fighterExists($f_1_numb)) {
                $inserted_f_1 = getFighter($f_1_link->href);
                saveFighterNumber($f_1_numb, $inserted_f_1);
            }
            
            $right_side = $main_section->find(".right_side", 0);
            $f_2_link = $right_side->find("[itemprop=url]", 0);
            $l_arr = explode("-", $f_2_link->href);
            $f_2_numb = array_pop($l_arr);
            if (!fighterExists($f_2_numb)) {
                $inserted_f_2 = getFighter($f_2_link->href);
                saveFighterNumber($f_2_numb, $inserted_f_2);
            }
            
            $event = [];
            $event_detail = $html->find(".event_detail", 0);
            
            $res = $event_detail->find("[itemprop=startDate]", 0);
            $event['date'] = date("Y-m-d 00:00:00", strtotime($res->content));
            
            $res = $event_detail->find("[itemprop=location]", 0);
            $event['location'] = $res->plaintext;
            
            $event['fighter_1'] = getSavedFighter($f_1_numb);
            $event['fighter_2'] = getSavedFighter($f_2_numb);
            
            $res = $html->find("[property=og:url]", 0);
            $event['keyword'] = $res->content;
            
            $res = $html->find("[property=og:title]", 0);
            $event['name'] = $res->content;
            
            $inserted = saveEvent($event);
            $l_arr = explode("-", $link);
            $numb = array_pop($l_arr);
            saveEventNumber($numb, $inserted);
            
            $other_fights = $html->find(".event_match", 0);
            if ($other_fights) {
                $trs = $other_fights->find("[itemprop=subEvent]");
                foreach ($trs as $tr) {
                    $link = $tr->find("[itemprop=url]", 0);
                    if (isset($link)) {
                        $l_arr = explode("-", $link->href);
                        $numb_1 = array_pop($l_arr);
                        
                        if (!fighterExists($numb_1)) {
                            $inserted_f_1 = getFighter($link->href);
                            saveFighterNumber($numb_1, $inserted_f_1);
                        }
                    }
                    
                    $link = $tr->find("[itemprop=url]", 1);
                    if (isset($link)) {
                        $l_arr = explode("-", $link->href);
                        $numb_2 = array_pop($l_arr);
                        
                        if (!fighterExists($numb_2)) {
                            $inserted_f_2 = getFighter($link->href);
                            saveFighterNumber($numb_2, $inserted_f_2);
                        }
                    }
                    
                    $event['fighter_1'] = getSavedFighter($numb_1);
                    $event['fighter_2'] = getSavedFighter($numb_2);
                    $event['event'] = $inserted;
                    
                    saveOtherFight($event);
                }
            }
        }
    }
    
    return true;
}

function getFighter($link, $m_id = '')
{
    global $MODULE_VARS, $INCLUDE_FOLDER;
    require_once $INCLUDE_FOLDER . "lib/simple_html_dom.php";
    
    extract($MODULE_VARS['parser']);
    
    $fighter = [];
    
    $html = file_get_html($parse_base_url . $link);
    if ($html) {
        $res = $html->find("[property=og:url]", 0);
        $fighter['keyword'] = $res->content;
        
        $html = $html->find(".bio_fighter", 0);
        
        $res = $html->find('.fn', 0);
        if (isset($res)) {
            $fighter['name'] = trim($res->plaintext);
        } else {
            $fighter['name'] = "";
        }
        
        $res = $html->find('.nickname', 0);
        if (isset($res)) {
            $fighter['nickname'] = trim($res->plaintext);
        } else {
            $fighter['nickname'] = "";
        }
        
        $res = $html->find('[itemprop=nationality]', 0);
        if (isset($res)) {
            $fighter['country'] = $res->plaintext;
        } else {
            $fighter['country'] = "";
        }
        
        $res = $html->find('.weight', 0);
        if (isset($res)) {
            $str = $res->plaintext;
            $pos1 = strpos($str, 'lbs') + 3;
            $pos2 = strpos($str, 'kg');
            $strres = substr($str, $pos1, $pos2 - $pos1);
            $fighter['weight'] = trim($strres);
        } else {
            $fighter['weight'] = "";
        }
        
        $res = $html->find('.height', 0);
        if (isset($res)) {
            $str = $res->plaintext;
            $pos1 = strpos($str, '"') + 1;
            $pos2 = strpos($str, 'cm');
            $strres = substr($str, $pos1, $pos2 - $pos1);
            $fighter['height'] = trim($strres);
        } else {
            $fighter['height'] = "";
        }
        
        $res = $html->find('[itemprop=addressLocality]', 0);
        if (isset($res)) {
            $fighter['birth_place'] = trim($res->plaintext);
        } else {
            $fighter['birth_place'] = "";
        }
        
        $res = $html->find('[itemprop=birthDate]', 0);
        if (isset($res)) {
            $fighter['birth_date'] = trim($res->plaintext);
        } else {
            $fighter['birth_date'] = "";
        }
        
        $res = $html->find('.wclass', 0);
        if (isset($res)) {
            $res = $res->children(0)->children(0)->plaintext;
            $fighter['weight_cat'] = $res;
        } else {
            $fighter['weight_cat'] = "";
        }
        
        $res = $html->find('.graph_tag', 0);
        if (isset($res)) {
            $str = $res->plaintext;
            $pos1 = strpos($str, "KO");
            $strres = substr($str, 0, $pos1);
            $fighter['victory_ko'] = trim($strres);
        } else {
            $fighter['victory_ko'] = 0;
        }
        
        $res = $html->find('.graph_tag', 1);
        if (isset($res)) {
            $str = $res->plaintext;
            $pos1 = strpos($str, "SUBMISSIONS");
            $strres = substr($str, 0, $pos1);
            $fighter['victory_submision'] = trim($strres);
        } else {
            $fighter['victory_submision'] = 0;
        }
        
        $res = $html->find('.graph_tag', 2);
        if (isset($res)) {
            $str = $res->plaintext;
            $pos1 = strpos($str, "DECISIONS");
            $strres = substr($str, 0, $pos1);
            $fighter['victory_decision'] = trim($strres);
        } else {
            $fighter['victory_decision'] = 0;
        }
        
        $is_others = false;
        $graph = 0;
        $res = $html->find('.graph_tag', 3);
        if (isset($res)) {
            $str = $res->plaintext;
            if (($pos1 = strpos($str, "OTHERS")) !== false) {
                $strres = substr($str, 0, $pos1);
                $fighter['victory_decision'] += trim($strres);
                $is_others = true;
                $graph = 1;
            }
        }
        
        $res = $html->find('.graph_tag', 3 + $graph);
        if (isset($res)) {
            $str = $res->plaintext;
            $pos1 = strpos($str, "KO");
            $strres = substr($str, 0, $pos1);
            $fighter['defeat_ko'] = trim($strres);
        } else {
            $fighter['defeat_ko'] = 0;
        }
        
        $res = $html->find('.graph_tag', 4 + $graph);
        if (isset($res)) {
            $str = $res->plaintext;
            $pos1 = strpos($str, "SUBMISSIONS");
            $strres = substr($str, 0, $pos1);
            $fighter['defeat_submision'] = trim($strres);
        } else {
            $fighter['defeat_submision'] = 0;
        }
        
        $res = $html->find('.graph_tag', 5 + $graph);
        if (isset($res)) {
            $str = $res->plaintext;
            $pos1 = strpos($str, "DECISIONS");
            $strres = substr($str, 0, $pos1);
            $fighter['defeat_decision'] = trim($strres);
        } else {
            $fighter['defeat_decision'] = 0;
        }
        
        $res = $html->find('.graph_tag', 7);
        if (isset($res)) {
            $str = $res->plaintext;
            if (($pos1 = strpos($str, "OTHERS")) !== false) {
                $strres = substr($str, 0, $pos1);
                $fighter['defeat_decision'] += trim($strres);
            }
        }
        
        $res = $html->find(".right_side", 0);
        if (isset($res)) {
            $res1 = $res->find(".counter", 0);
            $fighter['draw'] = $res1->plaintext;
        } else {
            $fighter['draw'] = 0;
        }
        
        $res = $html->find(".profile_image", 0);
        if (isset($res)) {
            $fighter['image'] = $res->src;
        } else {
            $fighter['image'] = "";
        }
        
        
    }
    
    if (count($fighter)) {
        if (empty($m_id)) {
            return saveFighter($fighter);
        } else {
            updateFighter($m_id, $fighter);
        }
    }
    return true;
}

function eventExists($number)
{
    global $db;
    
    $event_number = $db->get_row("SELECT * FROM `event_number` WHERE `sh_id` = " . $number, ARRAY_A);

    if (!empty($event_number)) {
        $in_base = $db->get_row("SELECT * FROM `Message2010` WHERE `Message_ID` = " . $event_number['m_id'], ARRAY_A);
        if (!empty($in_base)) {
            return true;
        }
    }
    return false;
}

function fighterExists($number)
{
    global $db;
    
    $fighter_number = $db->get_row("SELECT * FROM `fighter_number` WHERE `sh_id` = " . $number, ARRAY_A);

    if (!empty($fighter_number)) {
        $in_base = $db->get_row("SELECT * FROM `Message2007` WHERE `Message_ID` = " . $fighter_number['m_id'], ARRAY_A);
        if (!empty($in_base)) {
            return true;
        }
    }
    return false;
}

function saveFighter($fighter)
{
    global $db, $MODULE_VARS, $DOCUMENT_ROOT, $SUB_FOLDER, $HTTP_IMAGES_PATH;
    
    extract($MODULE_VARS['parser']);
    
    $k_arr = explode("/", strtolower($fighter['keyword']));
    $k_all = array_pop($k_arr);
    $key_arr = explode("-", $k_all);
    array_pop($key_arr);
    $keyword = implode("-", $key_arr);
    
    $title = str_replace("{{%fighter_name}}", $fighter['name'], $fighter_page_title);
    $title = !empty($fighter['nickname']) ? str_replace("{{%fighter_nickname}}", " (" . trim($fighter['nickname'], '"') . ")", $title) : str_replace("{{%fighter_nickname}}", "", $title);
    $keywords = str_replace("{{%fighter_name}}", $fighter['name'], $fighter_page_keywords);
    $keywords = !empty($fighter['nickname']) ? str_replace("{{%fighter_nickname}}", ", " . trim($fighter['nickname'], '"'), $keywords) : str_replace("{{%fighter_nickname}}", "", $keywords);
    $description = str_replace("{{%fighter_name}}", $fighter['name'], $fighter_page_description);
    $description = !empty($fighter['nickname']) ? str_replace("{{%fighter_nickname}}", " (" . trim($fighter['nickname'], '"') . ")", $description) : str_replace("{{%fighter_nickname}}", " ", $description);
    $created = date("Y-m-d H:i:s");
    $country = $db->get_row("SELECT * FROM `Classificator_country_en` WHERE LOWER(`country_en_Name`) = '" . strtolower($fighter['country']) . "'", ARRAY_A);
    if (!empty($country)) {
        $country_id = $country['country_en_ID'];
        $country_name = '';
    } else {
        $country_id = 0;
        $country_name = $fighter['country'];
    }
    $weight_cat_ru_db = $db->get_row("SELECT * FROM `Classificator_weight_cat_en_ru` WHERE LOWER(`weight_cat_en_ru_Name`) = '" . strtolower($fighter['weight_cat']) . "'", ARRAY_A);
    $weight_cat_ru = $weight_cat_ru_db['Value'];
    $weight = $fighter['weight'];
    $height = $fighter['height'];
    $fullname = $fighter['name'];
    $birth_place = $fighter['birth_place'];
    $birth_date = $fighter['birth_date'];
    $weight_cat = $fighter['weight_cat'];
    $victory_ko = $fighter['victory_ko'];
    $victory_submision = !empty($fighter['victory_submision']) ? $fighter['victory_submision'] : 0;
    $victory_decision = !empty($fighter['victory_decision']) ? $fighter['victory_decision'] : 0;
    $defeat_ko = !empty($fighter['defeat_ko']) ? $fighter['defeat_ko'] : 0;
    $defeat_submision = !empty($fighter['defeat_submision']) ? $fighter['defeat_submision'] : 0;
    $defeat_decision = !empty($fighter['defeat_decision']) ? $fighter['defeat_decision'] : 0;
    $draw = !empty($fighter['draw']) ? $fighter['draw'] : 0;
    $name_arr = explode(" ", $fighter['name']);
    $l_name = array_pop($name_arr);
    $f_name = implode(" ", $name_arr);
    $nickname = trim($fighter['nickname'], '"');
    
    if (strpos($fighter['image'], 'default') === false) {
        $image = str_replace("/200/", "/270/", $fighter['image']);
        $image = str_replace("/300/", "/405/", $image);
        $image_last = $db->get_row("SELECT MAX(CONVERT(SUBSTRING_INDEX(image, '.', 1), UNSIGNED INTEGER)) AS img FROM `Message2007`", ARRAY_A);
        $image_id = $image_last['img'] + 1;
        $file = $DOCUMENT_ROOT . $SUB_FOLDER . $HTTP_IMAGES_PATH . 'fighters/' . $image_id . '.jpg';
        $image_id .= '.jpg'; 
        copy($parse_base_url . $image, $file);
    } else {
        $image_id = '';
    }
    
    
    $sql = "
        INSERT INTO Message2007 (
            User_ID,
            Subdivision_ID,
            Sub_Class_ID,
            Keyword,
            ncTitle,
            ncKeywords,
            ncDescription,
            Created,
            LastUser_ID,
            myName_ru,
            myCountry,
            sportType,
            myWeightCat_ru,
            myWeight,
            myGrouth,
            myName_en,
            birth_place,
            birth_date,
            myWeightCat_en,
            victory_ko,
            victory_decision,
            victory_submision,
            defeat_ko,
            defeat_decision,
            defeat_submision,
            draw,
            country_name,
            f_name_en,
            l_name_en,
            nickname,
            image
        ) VALUES (
            5,
            268,
            277,
            '$keyword',
            '$title',
            '$keywords',
            '$description',
            '$created',
            5,
            '',
            $country_id,
            1,
            '$weight_cat_ru',
            $weight,
            $height,
            '$fullname',
            '$birth_place',
            '$birth_date',
            '$weight_cat',
            $victory_ko,
            $victory_decision,
            $victory_submision,
            $defeat_ko,
            $defeat_decision,
            $defeat_submision,
            $draw,
            '$country_name',
            '$f_name',
            '$l_name',
            '$nickname',
            '$image_id'
        );
    ";
    
    //echo $sql;
    $db->query($sql);
    
    $res = $db->get_row("SELECT MAX(Message_ID) AS last_id FROM Message2007", ARRAY_A);
    $ins_id = $res['last_id'];
    
    $sql = "UPDATE Message2007 SET Keyword = '" . $keyword . "-" . $ins_id . "' WHERE Message_ID = " . $ins_id;
    $db->query($sql);
    $sql = "UPDATE Message2007 SET Priority = " . ($ins_id - 501) . " WHERE Message_ID = " . $ins_id;
    $db->query($sql);
    return $ins_id;
}

function saveFighterNumber($sh_id, $m_id)
{
    global $db;
    $sql = "INSERT INTO fighter_number(sh_id, m_id)
            VALUES($sh_id, $m_id)";
            
    $db->query($sql);
}

function saveEventNumber($sh_id, $m_id)
{
    global $db;
    $sql = "INSERT INTO event_number(sh_id, m_id)
            VALUES($sh_id, $m_id)";
            
    $db->query($sql);
}

function getSavedFighter($sh_id)
{
    global $db;
    
    $res = $db->get_row("SELECT m_id FROM fighter_number WHERE sh_id = $sh_id", ARRAY_A);
    if (!empty($res)) {
        return $res['m_id'];
    }
    return false; 
}

function getSavedEvent($sh_id)
{
    global $db;
    
    $res = $db->get_row("SELECT m_id FROM event_number WHERE sh_id = $sh_id", ARRAY_A);
    if (!empty($res)) {
        return $res['m_id'];
    }
    return false; 
}

function saveEvent($event)
{
    global $db, $MODULE_VARS;
    
    extract($MODULE_VARS['parser']);
    
    $k_arr = explode("/", strtolower($event['keyword']));
    $k_all = array_pop($k_arr);
    $key_arr = explode("-", $k_all);
    array_pop($key_arr);
    $keyword = implode("-", $key_arr);
    
    $title = str_replace("{{%event_name}}", $event['name'], $event_page_title);
    $keywords = str_replace("{{%event_name}}", $event['name'], $event_page_keywords);
    $description = str_replace("{{%event_name}}", $event['name'], $event_page_description);
    $created = date("Y-m-d H:i:s");
    $date = $event['date'];
    $name = $event['name'];
    $fighter_1 = $event['fighter_1'];
    $fighter_2 = $event['fighter_2'];
    $location = $event['location'];
    
    $res = $db->get_row("SELECT MAX(Priority) AS max_p FROM Message2010;", ARRAY_A);
    $max_p = $res['max_p'] + 1;
    
    $sql = "
        INSERT INTO Message2010 (
            User_ID,
            Subdivision_ID,
            Sub_Class_ID,
            Keyword,
            ncTitle,
            ncKeywords,
            ncDescription,
            Created,
            LastUser_ID,
            myDate,
            myName,
            myType,
            main_card_fighter_1,
            main_card_fighter_2,
            myCountry_name,
            Priority
        ) VALUES (
            5,
            11,
            28,
            '$keyword',
            '$title',
            '$keywords',
            '$description',
            '$created',
            5,
            '$date',
            '$name',
            1,
            $fighter_1,
            $fighter_2,
            '$location',
            $max_p
        );
    ";
    
    $db->query($sql);
    
    $res = $db->get_row("SELECT MAX(Message_ID) AS last_id FROM Message2010", ARRAY_A);
    $ins_id = $res['last_id'];
    
    $sql = "UPDATE Message2010 SET Keyword = '" . $keyword . "-" . $ins_id . "' WHERE Message_ID = " . $ins_id;
    $db->query($sql);
    return $ins_id;
}

function saveOtherFight($event)
{
    global $db;
    
    $created = date("Y-m-d H:i:s");
    $fighter_1 = $event['fighter_1'];
    $fighter_2 = $event['fighter_2'];
    $event_id = $event['event'];
    
    $sql = "
        INSERT INTO Message2045 (
            User_ID,
            Subdivision_ID,
            Sub_Class_ID,
            Keyword,
            Created,
            LastUser_ID,
            fighter_1,
            fighter_2,
            event,
            type
            " . (isset($event['Checked']) ? ",Checked" : "") . "
        ) VALUES (
            5,
            231,
            240,
            '',
            '$created',
            5,
            $fighter_1,
            $fighter_2,
            $event_id,
            1
            " . (isset($event['Checked']) ? "," . $event['Checked'] : "") . "
        );
    ";
    
    $db->query($sql);
}

function getRecentEvents()
{
    global $MODULE_VARS, $INCLUDE_FOLDER;
    require_once $INCLUDE_FOLDER . "lib/simple_html_dom.php";
    
    extract($MODULE_VARS['parser']);
    
    if ($event_parsing_count > 10) {
        $event_parsing_count = 10;
    }
    $events_links = $events_ids = [];
    
    $html = file_get_html($event_url);
    if ($html) {
        $table = $html->find("#recentfights_tab", 1);
        if ($table) {
            $trs = $table->find("tr");
            foreach ($trs as $tr) {
                $link = $tr->find("[itemprop=url]", 0);
                if (isset($link)) {
                    $l_arr = explode("-", $link->href);
                    $numb = array_pop($l_arr);
                    
                    if (eventCompleted($link->href)) {
                        if (!eventExists($numb)) {
                            getEvent($link->href);
                        }
                        if (!eventFilled($numb)) {
                            $events_links[] = $link->href;
                            $events_ids[] = getSavedEvent($numb);
                            if (count($events_links) == $event_parsing_count) {
                                break;
                            }
                        }
                    }
                }
            }
        }
    }
    
    //$event_link = '/events/Professional-Fighters-League-PFL-5-2018-Regular-Season-67139';
    if (count($events_links)) {
        foreach ($events_links as $k => $event_link) {
            getRecentEvent($event_link, $events_ids[$k]);
        }
    }
    
    return true;
}

function getRecentEvent($link, $m_id)
{
    global $MODULE_VARS, $INCLUDE_FOLDER;
    require_once $INCLUDE_FOLDER . "lib/simple_html_dom.php";
    
    extract($MODULE_VARS['parser']);
    
    $html = file_get_html($parse_base_url . $link);
    if ($html) {
        $main_section = $html->find("[itemprop=subEvent]", 0);
        if (isset($main_section)) {
            $event = [];
            $left_side = $main_section->find(".left_side", 0);
            $result = $left_side->find(".final_result", 0);
            if (trim($result->plaintext) !== 'win' && trim($result->plaintext) !== 'loss' && trim($result->plaintext) !== 'draw') {
                return false;
            }
            $f_1_link = $left_side->find("[itemprop=url]", 0);
            $l_arr = explode("-", $f_1_link->href);
            $f_1_numb = array_pop($l_arr);
            if (!fighterExists($f_1_numb)) {
                $inserted_f_1 = getFighter($f_1_link->href);
                saveFighterNumber($f_1_numb, $inserted_f_1);
            }
            if (trim($result->plaintext) == 'win') {
                $event['winner'] = getSavedFighter($f_1_numb);
            }
            
            $right_side = $main_section->find(".right_side", 0);
            $result = $right_side->find(".final_result", 0);
            if (trim($result->plaintext) !== 'win' && trim($result->plaintext) !== 'loss' && trim($result->plaintext) !== 'draw') {
                return false;
            }
            $f_2_link = $right_side->find("[itemprop=url]", 0);
            $l_arr = explode("-", $f_2_link->href);
            $f_2_numb = array_pop($l_arr);
            if (!fighterExists($f_2_numb)) {
                $inserted_f_2 = getFighter($f_2_link->href);
                saveFighterNumber($f_2_numb, $inserted_f_2);
            }
            if (trim($result->plaintext) == 'win') {
                $event['winner'] = getSavedFighter($f_2_numb);
            }
            
            if (trim($result->plaintext) == 'draw') {
                $event['winner'] = '0';
            }
            
            $resume = $main_section->find(".resume", 0);
            $res = $resume->find('td', 1);
            $event['win_type'] = trim($res->plaintext, 'Method');
            
            $res = $resume->find('td', 2);
            $event['referee'] = trim($res->plaintext, 'Referee');
            
            $res = $resume->find('td', 3);
            $event['win_round'] = trim($res->plaintext, 'Round');
            
            $res = $resume->find('td', 4);
            $event['win_time'] = trim($res->plaintext, 'Time');
            
            updateEvent($m_id, $event);
            getFighter($f_1_link->href, getSavedFighter($f_1_numb));
            getFighter($f_2_link->href, getSavedFighter($f_2_numb));
            
            $other_fights = $html->find(".event_match", 0);
            if ($other_fights) {
                $trs = $other_fights->find("[itemprop=subEvent]");
                foreach ($trs as $tr) {
                    $event = [];
                    $f_1_link = $tr->find("[itemprop=url]", 0);
                    if (isset($f_1_link)) {
                        $l_arr = explode("-", $f_1_link->href);
                        $numb_1 = array_pop($l_arr);
                        
                        if (!fighterExists($numb_1)) {
                            $inserted_f_1 = getFighter($f_1_link->href);
                            saveFighterNumber($numb_1, $inserted_f_1);
                        }
                    }
                    
                    $f_2_link = $tr->find("[itemprop=url]", 1);
                    if (isset($f_2_link)) {
                        $l_arr = explode("-", $f_2_link->href);
                        $numb_2 = array_pop($l_arr);
                        
                        if (!fighterExists($numb_2)) {
                            $inserted_f_2 = getFighter($f_2_link->href);
                            saveFighterNumber($numb_2, $inserted_f_2);
                        }
                    }
                    
                    $result = $tr->find(".final_result", 0);
                    if (trim($result->plaintext) == 'win') {
                        $event['winner'] = getSavedFighter($numb_1);
                    } else if (trim($result->plaintext) == 'loss') {
                        $event['winner'] = getSavedFighter($numb_2);
                    } else if (trim($result->plaintext) == 'draw') {
                        $event['winner'] = getSavedFighter($numb_1);
                    } else {
                        continue;
                    }
                    
                    $res = $tr->find('td', 4);
                    $res1 = $tr->find('.sub_line', 0);
                    $event['win_type'] = trim($res->plaintext, $res1->plaintext);
                    $event['referee'] = $res1->plaintext;
                    
                    $res = $tr->find('td', 5);
                    $event['win_round'] = $res->plaintext;
                    
                    $res = $tr->find('td', 6);
                    $event['win_time'] = $res->plaintext;
                    
                    updateOtherFight($m_id, $event);
                    getFighter($f_1_link->href, getSavedFighter($numb_1));
                    getFighter($f_2_link->href, getSavedFighter($numb_2));
                }
            }
        }
    }
}

function eventFilled($number)
{
    global $db;
    
    $event_number = $db->get_row("SELECT * FROM `event_number` WHERE `sh_id` = " . $number, ARRAY_A);

    if (!empty($event_number)) {
        $in_base = $db->get_row("SELECT * FROM `Message2010` WHERE `Message_ID` = " . $event_number['m_id'], ARRAY_A);
        if (!empty($in_base) && !empty($in_base['main_card_winner'])) {
            return true;
        }
    }
    return false;
}

function updateEvent($m_id, $event)
{
    global $db;
    
    $winner = $event['winner'];
    $win_type = trim($event['win_type']);
    $referee = trim($event['referee']);
    $win_round = $event['win_round'];
    $win_time = trim($event['win_time']);
    
    $sql = "
        UPDATE Message2010 SET
            main_card_winner = $winner,
            win_type = '$win_type',
            referee = '$referee',
            win_round = $win_round,
            win_time = '$win_time'
        WHERE Message_ID = $m_id
    ";
    
    $db->query($sql);
}

function updateOtherFight($m_id, $event)
{
    global $db;
    
    $winner = $event['winner'];
    $win_type = trim($event['win_type']);
    $referee = trim($event['referee']);
    $win_round = $event['win_round'];
    $win_time = trim($event['win_time']);
    
    if (strpos(strtolower($win_type), 'draw') === false) {
        $winner_s = $winner;
    } else {
        $winner_s = 0;
    }
    
    $sql = "
        UPDATE Message2045 SET
            winner = $winner_s,
            win_type = '$win_type',
            referee = '$referee',
            win_round = $win_round,
            win_time = '$win_time'
        WHERE event = $m_id AND (fighter_1 = $winner OR fighter_2 = $winner)
    ";
    
    $db->query($sql);
}

function updateFighter($m_id, $fighter)
{
    global $db;
    
    $victory_ko = $fighter['victory_ko'];
    $victory_decision = $fighter['victory_decision'];
    $victory_submision = $fighter['victory_submision'];
    $defeat_ko = $fighter['defeat_ko'];
    $defeat_decision = $fighter['defeat_decision'];
    $defeat_submision = $fighter['defeat_submision'];
    $draw = $fighter['draw'];
    
    $sql = "
        UPDATE Message2007 SET
            victory_ko = $victory_ko,
            victory_decision = $victory_decision,
            victory_submision = $victory_submision,
            defeat_ko = $defeat_ko,
            defeat_decision = $defeat_decision,
            defeat_submision = $defeat_submision,
            draw = $draw
        WHERE Message_ID = $m_id
    ";
    
    $db->query($sql);
}

function eventCompleted($link)
{
    global $MODULE_VARS, $INCLUDE_FOLDER;
    require_once $INCLUDE_FOLDER . "lib/simple_html_dom.php";
    
    extract($MODULE_VARS['parser']);
    
    $html = file_get_html($parse_base_url . $link);
    if ($html) {
        $main_section = $html->find("[itemprop=subEvent]", 0);
        if (isset($main_section)) {
            $left_side = $main_section->find(".left_side", 0);
            $result = $left_side->find(".final_result", 0);
            if (trim($result->plaintext) == 'win' || trim($result->plaintext) == 'loss' || trim($result->plaintext) == 'draw') {
                return true;
            }
        }
    }
    
    return false;
}

function checkEvents()
{
    $date = date("Y-m-d H:i:s", mktime(0, 0, 0, date("m"), date("d") + 1, date("Y")));
    //$date = "2018-09-13 00:00:00";
    if ($events = getEventsByDate($date)) {
        foreach ($events as $event) {
            if ($sh_id = getShEventNumber($event['Message_ID'])) {
                $sh_link = trim($event['Keyword'], $event['Message_ID']) . $sh_id;
                getEventFighters($sh_link, $event['Message_ID']);
                clearOtherFights($event['Message_ID']);
            }
        }
        return "completed";
    }
    
    return "no data";
}

function getEventsByDate($date)
{
    global $db;
    
    $events = $db->get_results("SELECT * FROM `Message2010` WHERE `myDate` = '$date'", ARRAY_A);
    if ($events) {
        return $events;
    }
    return false;
}

function getShEventNumber($m_id)
{
    global $db;
    
    $res = $db->get_row("SELECT sh_id FROM event_number WHERE m_id = $m_id", ARRAY_A);
    if (!empty($res)) {
        return $res['sh_id'];
    }
    return false;
}

function getEventFighters($link, $m_id)
{
    global $MODULE_VARS, $INCLUDE_FOLDER;
    require_once $INCLUDE_FOLDER . "lib/simple_html_dom.php";
    
    extract($MODULE_VARS['parser']);
    
    $html = file_get_html($event_url . "/" . $link);
    if ($html) {
        $main_section = $html->find("[itemprop=subEvent]", 0);
        if (isset($main_section)) {
            $left_side = $main_section->find(".left_side", 0);
            $f_1_link = $left_side->find("[itemprop=url]", 0);
            $l_arr = explode("-", $f_1_link->href);
            $f_1_numb = array_pop($l_arr);
            if (!fighterExists($f_1_numb)) {
                $inserted_f_1 = getFighter($f_1_link->href);
                saveFighterNumber($f_1_numb, $inserted_f_1);
            }
            
            $right_side = $main_section->find(".right_side", 0);
            $f_2_link = $right_side->find("[itemprop=url]", 0);
            $l_arr = explode("-", $f_2_link->href);
            $f_2_numb = array_pop($l_arr);
            if (!fighterExists($f_2_numb)) {
                $inserted_f_2 = getFighter($f_2_link->href);
                saveFighterNumber($f_2_numb, $inserted_f_2);
            }
            
            $fighter_1 = getSavedFighter($f_1_numb);
            $fighter_2 = getSavedFighter($f_2_numb);
            
            checkSavedEventFighters('2010', $m_id, $fighter_1, $fighter_2);
            
            $other_fights = $html->find(".event_match", 0);
            if ($other_fights) {
                $trs = $other_fights->find("[itemprop=subEvent]");
                foreach ($trs as $tr) {
                    $link = $tr->find("[itemprop=url]", 0);
                    if (isset($link)) {
                        $l_arr = explode("-", $link->href);
                        $numb_1 = array_pop($l_arr);
                        
                        if (!fighterExists($numb_1)) {
                            $inserted_f_1 = getFighter($link->href);
                            saveFighterNumber($numb_1, $inserted_f_1);
                        }
                    }
                    
                    $link = $tr->find("[itemprop=url]", 1);
                    if (isset($link)) {
                        $l_arr = explode("-", $link->href);
                        $numb_2 = array_pop($l_arr);
                        
                        if (!fighterExists($numb_2)) {
                            $inserted_f_2 = getFighter($link->href);
                            saveFighterNumber($numb_2, $inserted_f_2);
                        }
                    }
                    
                    $fighter_1 = getSavedFighter($numb_1);
                    $fighter_2 = getSavedFighter($numb_2);
                    checkSavedEventFighters('2045', $m_id, $fighter_1, $fighter_2);
                }
            }
        }
    }
}

function checkSavedEventFighters($m_tbl, $m_id, $f_1, $f_2)
{
    global $db;
    
    $sql = "
        SELECT * FROM Message" . $m_tbl . " 
        WHERE " . ($m_tbl == '2010' ? "Message_ID" : "event") . " = $m_id 
        AND " . ($m_tbl == '2010' ? "main_card_fighter_1" : "fighter_1") . " = $f_1 
        AND " . ($m_tbl == '2010' ? "main_card_fighter_2" : "fighter_2") . " = $f_2";
    
    $res = $db->get_row($sql, ARRAY_A);
    if (!empty($res)) {
        $sql = "
            UPDATE Message" . $m_tbl . " SET 
                Checked = 2
            WHERE Message_ID = " . $res['Message_ID'];
        $db->query($sql);
        return true;
    } else {
        $sql = "
            SELECT * FROM Message" . $m_tbl . " 
            WHERE " . ($m_tbl == '2010' ? "Message_ID" : "event") . " = $m_id 
            AND (" . ($m_tbl == '2010' ? "main_card_fighter_1" : "fighter_1") . " = $f_1 
            OR " . ($m_tbl == '2010' ? "main_card_fighter_2" : "fighter_2") . " = $f_1)";
            
        $res = $db->get_row($sql, ARRAY_A);
        if (!empty($res)) {
            $sql = "
                UPDATE Message" . $m_tbl . " SET "
                    . ($m_tbl == '2010' ? "main_card_fighter_1" : "fighter_1") . " = $f_1,"
                    . ($m_tbl == '2010' ? "main_card_fighter_2" : "fighter_2") . " = $f_2,
                    Checked = 2 
                WHERE Message_ID = " . $res['Message_ID'];
            $db->query($sql);
            return true;
        } else {
            $sql = "
                SELECT * FROM Message" . $m_tbl . " 
                WHERE " . ($m_tbl == '2010' ? "Message_ID" : "event") . " = $m_id 
                AND (" . ($m_tbl == '2010' ? "main_card_fighter_1" : "fighter_1") . " = $f_2 
                OR " . ($m_tbl == '2010' ? "main_card_fighter_2" : "fighter_2") . " = $f_2)";
                
            $res = $db->get_row($sql, ARRAY_A);
            if (!empty($res)) {
                $sql = "
                    UPDATE Message" . $m_tbl . " SET "
                        . ($m_tbl == '2010' ? "main_card_fighter_1" : "fighter_1") . " = $f_1,"
                        . ($m_tbl == '2010' ? "main_card_fighter_2" : "fighter_2") . " = $f_2,
                        Checked = 2
                    WHERE Message_ID = " . $res['Message_ID'];
                $db->query($sql);
                return true;
            } else {
                if ($m_tbl == '2045') {
                    $event = [];
                    $event['fighter_1'] = $f_1;
                    $event['fighter_2'] = $f_2;
                    $event['event'] = $m_id;
                    $event['Checked'] = 2;
                    saveOtherFight($event);
                    return true;
                }
            }
        }
    }
    return false;
}

function clearOtherFights($event_id)
{
    global $db;
    $sql = "
        DELETE FROM Message2045
        WHERE event = $event_id AND Checked = 1
    ";
    $db->query($sql);
    
    $sql = "
        UPDATE Message2045 SET
            Checked = 1
        WHERE event = $event_id
    ";
    $db->query($sql);
}