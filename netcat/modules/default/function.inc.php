<?php

// Данные по пользвателю

function getUserData($uid)
{
	
	$sql = mysql_fetch_assoc(mysql_query("SELECT * FROM User WHERE USER_ID=".$uid." LIMIT 0,1"));
	return $sql;
}
// Функции формируют результаты модуля ОПРОСЫ

function interogation_all($field)
{
	$result = 0;
	$arr = explode(',',$field);
	foreach($arr as $key=>$val)
	{
		$res = explode('-',$val);
		$result = $result+$res[1];
	}
	// все результаты
	return $result;
}

function interogation_self($field,$all,$id,$pc=false)
{
	$arr = explode(',',$field);
	foreach($arr as $key=>$val)
	{
		$res = explode('-',$val);
		if($res[0] == $id)
		{
			$pco = round(((100/$all)*$res[1]),2);
			return (!$pc ? $res[1] : $pco);
			break;
		}
	}
}

// Запись результата опроса

function addArrogRes($vid, $check, $class_id, $vote_check)
{
    //if (md5($_SERVER['REMOTE_ADDR'] . $vid) == $check)	{
        setcookie ("interogation" . $vid, $check, time() + 3600*9999, '/');
	    setcookie ("interogation" . $vid . "-answer", $vote_check, time() + 3600*9999, '/');
		if (is_numeric($vid)) {
			$sql = mysql_fetch_assoc(mysql_query("SELECT answers FROM Message" . $class_id . " WHERE Message_ID=" . $vid . " LIMIT 0,1"));
			if ($sql['answers']) {
				$arr = explode(',', $sql['answers']);
				foreach ($arr as $key => $val) {
				    $res = explode('-', $val);
					if ($res[0] == $vote_check) {
					    $arr[$key] = $res[0] . '-' . ($res[1] + 1);
						break;
					}
				}
				$sql_ins = "UPDATE Message" . $class_id . " SET answers='" . implode(',', $arr) . "' WHERE Message_ID=" . $vid;
				mysql_query($sql_ins);
				return 1;
			}
		}
	//}
}


# Функция создает превью на лету, если она не существует
# Возвращает путь к файлу режим уменьшения: 
# -1 — вписывает   в указанные размеры с полями по краям с фоном $rgb
#  0 — пропорционально уменьшает (по умолчанию); 
#  1 — вписывает   в указанные размеры с обрезкой; 
#  3 — аналогично 1, но с приоритетов по ширине; 
#  4 — аналогично 1 но с приоритетом по высоте;
function getThumbNow($src, $width, $height, $mode=0, $nocache = false, $quality = 100, $rgb = 0xFFFFFF) {
    global $HTTP_IMAGES_PATH, $INCLUDE_FOLDER;
    
    $imageFile =     $_SERVER['DOCUMENT_ROOT'].$src;
if(!is_file($imageFile)) return false; // Если файла не существует, то возвращаем false
	
	$ext = pathinfo($imageFile, PATHINFO_EXTENSION);
    
    $im = imagecreatefromjpeg($imageFile);
            $stamp = imagecreatefrompng($_SERVER['DOCUMENT_ROOT'].'/images/watermark_light.png');
            // Установка полей для штампа и получение высоты/ширины штампа
            $marge_right = 10;
            $marge_bottom = 10;
            $sx = imagesx($stamp);
            $sy = imagesy($stamp);
            // Копирование изображения штампа на фотографию с помощью смещения края
            // и ширины фотографии для расчета позиционирования штампа. 
            imagecopy($im, $stamp, imagesx($im) - $sx - $marge_right, imagesy($im) - $sy - $marge_bottom, 0, 0, imagesx($stamp), imagesy($stamp));
            imagepng($im,$imageFile);
    
    
	$newFileName = md5_file($imageFile)."_".$width."xx".$height."x$mode".(!empty($ext) ? ".".$ext : NULL);
    
    $folder = $HTTP_IMAGES_PATH."cache/"; // Создаем директорию для хранения изображений
    if(!$dh=opendir($_SERVER['DOCUMENT_ROOT'].$folder)) {mkdir($_SERVER['DOCUMENT_ROOT'].$folder, 0777);} else closedir($dh); // создаем папке, если нет

    $newFile = $_SERVER['DOCUMENT_ROOT'].$folder.$newFileName;
    
    if(!is_file($newFile) || $nocache) 
    {
        if($mode == -1)
        {
            img_resize($imageFile, $newFile, $width,  $height, $rgb, $quality);
        }
    else {
            require_once($INCLUDE_FOLDER."classes/nc_imagetransform.class.php");
            nc_ImageTransform::imgResize($imageFile, $newFile, $width,  $height, $mode);
                      
         }
    }
    
    return $folder.$newFileName;    
}


/*
Пример перехвата события "авторизация пользователя"
class ListenUser {
	public function __construct () {
		$nc_core = nc_Core::get_object();
		$nc_core->event->bind($this, array('authorizeUser' => 'authorize_user') );
	}

	public function  authorize_user ( $user_id ) {
		return 0;
	}
}

$listenObj = new  ListenUser();
*/

/*
function my_func () {
}
*/

function cutStr($str, $length=50, $postfix='...')
{
    if (strlen($str) <= $length)
        return $str;
 
    $temp = substr($str, 0, $length);
    return substr($temp, 0, strrpos($temp, ' ') ) . $postfix;
}

function setTwitCount($id)
{
    $sql = "UPDATE `Message2000` SET twits_cnt = twits_cnt + 1 WHERE `Message_ID` = " . $id;
    mysql_query($sql);
}

function getTwitCount($id)
{
    $sql = mysql_fetch_assoc(mysql_query("SELECT twits_cnt FROM `Message2000` WHERE `Message_ID` = " . $id . " LIMIT 0,1"));
    return $sql;
}

function setFbCount($id)
{
    $sql = "UPDATE `Message2000` SET fb_cnt = fb_cnt + 1 WHERE `Message_ID` = " . $id;
    mysql_query($sql);
}

function getFbCount($id)
{
    $sql = mysql_fetch_assoc(mysql_query("SELECT fb_cnt FROM `Message2000` WHERE `Message_ID` = " . $id . " LIMIT 0,1"));
    return $sql;
}
function setVkCount($id)
{
    $sql = "UPDATE `Message2000` SET vk_cnt = vk_cnt + 1 WHERE `Message_ID` = " . $id;
    mysql_query($sql);
}

function getVkCount($id)
{
    $sql = mysql_fetch_assoc(mysql_query("SELECT vk_cnt FROM `Message2000` WHERE `Message_ID` = " . $id . " LIMIT 0,1"));
    return $sql;
}
function setTwitCountEvt($id)
{
    $sql = "UPDATE `Message2010` SET twits_cnt = twits_cnt + 1 WHERE `Message_ID` = " . $id;
    mysql_query($sql);
}

function getTwitCountEvt($id)
{
    $sql = mysql_fetch_assoc(mysql_query("SELECT twits_cnt FROM `Message2010` WHERE `Message_ID` = " . $id . " LIMIT 0,1"));
    return $sql;
}

function setFbCountEvt($id)
{
    $sql = "UPDATE `Message2010` SET fb_cnt = fb_cnt + 1 WHERE `Message_ID` = " . $id;
    mysql_query($sql);
}

function getFbCountEvt($id)
{
    $sql = mysql_fetch_assoc(mysql_query("SELECT fb_cnt FROM `Message2010` WHERE `Message_ID` = " . $id . " LIMIT 0,1"));
    return $sql;
}
function setVkCountEvt($id)
{
    $sql = "UPDATE `Message2010` SET vk_cnt = vk_cnt + 1 WHERE `Message_ID` = " . $id;
    mysql_query($sql);
}

function getVkCountEvt($id)
{
    $sql = mysql_fetch_assoc(mysql_query("SELECT vk_cnt FROM `Message2010` WHERE `Message_ID` = " . $id . " LIMIT 0,1"));
    return $sql;
}
function year_format($i)
{
    ereg("..$", $i, $t);
    $s = $t[0];
    if ($s >= 5 && $s <= 20) {
        $res = $i . " лет";
    } else {
        ereg(".$", $i, $t);
        $s = $t[0];
        if ($s == "1") {
            $res = $i . " год";
        } elseif (in_array($s, array("2", "3", "4"))) {
            $res = $i . " года";
        } else {
            $res = $i . " лет";
        }
    }
    return $res;
}

function set_resolution($resol)
{
    setcookie ("resol", $resol, time() + 3600*9999, '/');
}

function get_resolution()
{
    if ($_COOKIE["resol"] == 1) {
        return true;
    } else {
        return false;
    }
}

function setBreadcrumbs($cur_url, $action = '')
{
    $page_title = '';
    $breadcrumbs = '<div class="breadcrumbs-path">';
    $breadcrumbs .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item">';
    $breadcrumbs .= '<a href="/" itemprop="url">';
    $breadcrumbs .= '<span itemprop="title">Главная</span>';
    $breadcrumbs .= '</a>';
    $breadcrumbs .= '</div>';
    
    switch ($cur_url) {
        case '/news/':
            $page_title = 'Новости мира ММА, бокса и кикбоксинга';
        break;
        case '/news/news-kickboxing/':
            $page_title = 'Новости кикбоксинга: онлайн-трансляции, статистика боев, последние новости';
            $breadcrumbs .= '<span class="path-arrow"></span>';
            $breadcrumbs .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item">';
            $breadcrumbs .= '<a href="/news" itemprop="url">';
            $breadcrumbs .= '<span itemprop="title">Новости</span>';
            $breadcrumbs .= '</a>';
            $breadcrumbs .= '</div>';
        break;
        case '/news/news-boxing/':
            $page_title = 'Новости бокса: онлайн-трансляции, статистика боев, видео';
            $breadcrumbs .= '<span class="path-arrow"></span>';
            $breadcrumbs .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item">';
            $breadcrumbs .= '<a href="/news" itemprop="url">';
            $breadcrumbs .= '<span itemprop="title">Новости</span>';
            $breadcrumbs .= '</a>';
            $breadcrumbs .= '</div>';
        break;
        case '/news/news-mma/':
            $page_title = 'Новости ММА: онлайн-трансляции, статистика боев, видео';
            $breadcrumbs .= '<span class="path-arrow"></span>';
            $breadcrumbs .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item">';
            $breadcrumbs .= '<a href="/news" itemprop="url">';
            $breadcrumbs .= '<span itemprop="title">Новости</span>';
            $breadcrumbs .= '</a>';
            $breadcrumbs .= '</div>';
        break;
        case '/video/':
            $page_title = 'Видео боев ММА, бокса и кикбоксинга';
        break;
        case '/video/mma/':
            $page_title = 'Видео боев ММА: UFC, Bellator';
            $breadcrumbs .= '<span class="path-arrow"></span>';
            $breadcrumbs .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item">';
            $breadcrumbs .= '<a href="/video" itemprop="url">';
            $breadcrumbs .= '<span itemprop="title">Видео</span>';
            $breadcrumbs .= '</a>';
            $breadcrumbs .= '</div>';
        break;
        case '/video/boxing/':
            $page_title = 'Видео боев по профессиональному боксу';
            $breadcrumbs .= '<span class="path-arrow"></span>';
            $breadcrumbs .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item">';
            $breadcrumbs .= '<a href="/video" itemprop="url">';
            $breadcrumbs .= '<span itemprop="title">Видео</span>';
            $breadcrumbs .= '</a>';
            $breadcrumbs .= '</div>';
        break;
        case '/video/kickboxing/':
            $page_title = 'Видео боев по профессиональному кикбоксингу';
            $breadcrumbs .= '<span class="path-arrow"></span>';
            $breadcrumbs .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item">';
            $breadcrumbs .= '<a href="/video" itemprop="url">';
            $breadcrumbs .= '<span itemprop="title">Видео</span>';
            $breadcrumbs .= '</a>';
            $breadcrumbs .= '</div>';
        break;
        case '/video/archive/':
            $page_title = 'Видеоархив боев ММА, бокса, кикбоксинга';
            $breadcrumbs .= '<span class="path-arrow"></span>';
            $breadcrumbs .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item">';
            $breadcrumbs .= '<a href="/video" itemprop="url">';
            $breadcrumbs .= '<span itemprop="title">Видео</span>';
            $breadcrumbs .= '</a>';
            $breadcrumbs .= '</div>';
        break;
        case '/video/translation/':
            $page_title = 'Переводы боев ММА, бокса, кикбоксинга';
            $breadcrumbs .= '<span class="path-arrow"></span>';
            $breadcrumbs .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item">';
            $breadcrumbs .= '<a href="/video" itemprop="url">';
            $breadcrumbs .= '<span itemprop="title">Видео</span>';
            $breadcrumbs .= '</a>';
            $breadcrumbs .= '</div>';
        break;
        case '/video/interview/':
            $page_title = 'Интервью бойцов ММА, бокса, кикбоксинга';
            $breadcrumbs .= '<span class="path-arrow"></span>';
            $breadcrumbs .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item">';
            $breadcrumbs .= '<a href="/video" itemprop="url">';
            $breadcrumbs .= '<span itemprop="title">Видео</span>';
            $breadcrumbs .= '</a>';
            $breadcrumbs .= '</div>';
        break;
        case '/articles/':
            $page_title = 'Статьи о ММА, боксе и кикбоксинге';
        break;
        case '/articles/mma/':
            $page_title = 'Статьи о ММА';
            $breadcrumbs .= '<span class="path-arrow"></span>';
            $breadcrumbs .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item">';
            $breadcrumbs .= '<a href="/articles" itemprop="url">';
            $breadcrumbs .= '<span itemprop="title">Статьи</span>';
            $breadcrumbs .= '</a>';
            $breadcrumbs .= '</div>';
        break;
        case '/articles/boxing/':
            $page_title = 'Статьи о боксе';
            $breadcrumbs .= '<span class="path-arrow"></span>';
            $breadcrumbs .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item">';
            $breadcrumbs .= '<a href="/articles" itemprop="url">';
            $breadcrumbs .= '<span itemprop="title">Статьи</span>';
            $breadcrumbs .= '</a>';
            $breadcrumbs .= '</div>';
        break;
        case '/articles/kickboxing/':
            $page_title = 'Статьи о кикбоксинге';
            $breadcrumbs .= '<span class="path-arrow"></span>';
            $breadcrumbs .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item">';
            $breadcrumbs .= '<a href="/articles" itemprop="url">';
            $breadcrumbs .= '<span itemprop="title">Статьи</span>';
            $breadcrumbs .= '</a>';
            $breadcrumbs .= '</div>';
        break;
        case '/fight-calendar/':
            $page_title = 'События и календарь предстоящих боев ММА, бокса и кикбоксинга';
            if (!empty($action) && $action == 'results') {
                $page_title = 'Результаты прошедших боев ММА, бокса и кикбоксинга';
            }
        break;
        case '/fight-calendar/mma/':
            $page_title = 'События и календарь предстоящих боев ММА';
            if (!empty($action) && $action == 'results') {
                $page_title = 'Результаты прошедших боев ММА';
            }
            $breadcrumbs .= '<span class="path-arrow"></span>';
            $breadcrumbs .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item">';
            $breadcrumbs .= '<a href="/fight-calendar" itemprop="url">';
            $breadcrumbs .= '<span itemprop="title">События</span>';
            $breadcrumbs .= '</a>';
            $breadcrumbs .= '</div>';
        break;
        case '/fight-calendar/boxing/':
            $page_title = 'События и календарь предстоящих боев по боксу';
            if (!empty($action) && $action == 'results') {
                $page_title = 'Результаты прошедших боев по боксу';
            }
            $breadcrumbs .= '<span class="path-arrow"></span>';
            $breadcrumbs .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item">';
            $breadcrumbs .= '<a href="/fight-calendar" itemprop="url">';
            $breadcrumbs .= '<span itemprop="title">События</span>';
            $breadcrumbs .= '</a>';
            $breadcrumbs .= '</div>';
        break;
        case '/fight-calendar/kickboxing/':
            $page_title = 'События и календарь предстоящих боев по кикбоксингу';
            if (!empty($action) && $action == 'results') {
                $page_title = 'Результаты прошедших боев по кикбоксингу';
            }
            $breadcrumbs .= '<span class="path-arrow"></span>';
            $breadcrumbs .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item">';
            $breadcrumbs .= '<a href="/fight-calendar" itemprop="url">';
            $breadcrumbs .= '<span itemprop="title">События</span>';
            $breadcrumbs .= '</a>';
            $breadcrumbs .= '</div>';
        break;
        case '/ratings/mma/':
            $page_title = 'Рейтинг бойцов ММА';
            $breadcrumbs .= '<span class="path-arrow"></span>';
            $breadcrumbs .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item">';
            $breadcrumbs .= '<a href="/ratings" itemprop="url">';
            $breadcrumbs .= '<span itemprop="title">Рейтинги</span>';
            $breadcrumbs .= '</a>';
            $breadcrumbs .= '</div>';
        break;
        case '/ratings/boxing/':
            $page_title = 'Рейтинг бойцов по боксу';
            $breadcrumbs .= '<span class="path-arrow"></span>';
            $breadcrumbs .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item">';
            $breadcrumbs .= '<a href="/ratings" itemprop="url">';
            $breadcrumbs .= '<span itemprop="title">Рейтинги</span>';
            $breadcrumbs .= '</a>';
            $breadcrumbs .= '</div>';
        break;
        case '/ratings/kickboxing/':
            $page_title = 'Рейтинг бойцов по кикбоксингу';
            $breadcrumbs .= '<span class="path-arrow"></span>';
            $breadcrumbs .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item">';
            $breadcrumbs .= '<a href="/ratings" itemprop="url">';
            $breadcrumbs .= '<span itemprop="title">Рейтинги</span>';
            $breadcrumbs .= '</a>';
            $breadcrumbs .= '</div>';
        break;
        case '/blogs/':
            $page_title = 'Блоги о боях и мире ММА, бокса и кикбоксинга';
        break;
        case '/videoblogi/':
            $page_title = 'Mad Bear Live – блог о спорте и спортивной жизни';
        break;
        default:
            $blogsUrls = nc_get_sub_children('41');
            $res = mysql_query("SELECT Subdivision_ID, Subdivision_Name, Hidden_URL FROM Subdivision WHERE Subdivision_ID IN (".join(',', $blogsUrls).")");
            while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
                if ($row['Subdivision_ID'] != 41) {
                    $subs_arr[] = $row;
                }
            }
            foreach ($subs_arr as $sub) {
                if ($sub['Hidden_URL'] == $cur_url) {
                    $page_title = $sub['Subdivision_Name'];
                    $breadcrumbs .= '<span class="path-arrow"></span>';
                    $breadcrumbs .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item">';
                    $breadcrumbs .= '<a href="/blogs" itemprop="url">';
                    $breadcrumbs .= '<span itemprop="title">Блоги</span>';
                    $breadcrumbs .= '</a>';
                    $breadcrumbs .= '</div>';
                }
            }
        break;
    }
            
    $breadcrumbs .= '<span class="path-last"></span>';
    $breadcrumbs .= '</div>';
    $breadcrumbs .= '<div class="breadcrumbs-pagetitle">';
    $breadcrumbs .= '<h1>' . $page_title . '</h1>';
    $breadcrumbs .= '</div>';
        
    return $breadcrumbs;
}

?>