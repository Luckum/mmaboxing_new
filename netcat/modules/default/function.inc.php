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
function getThumbNow($src, $width, $height, $mode=0, $nocache = false, $quality = 85, $rgb = 0xFFFFFF) {
    global $HTTP_IMAGES_PATH, $INCLUDE_FOLDER;
    
    $imageFile =     $_SERVER['DOCUMENT_ROOT'].$src;
if(!is_file($imageFile)) return false; // Если файла не существует, то возвращаем false
	
	$ext = pathinfo($imageFile, PATHINFO_EXTENSION);
    
    $im = imagecreatefromjpeg($imageFile);
            //$stamp = imagecreatefrompng($_SERVER['DOCUMENT_ROOT'].'/images/watermark_light.png');
            // Установка полей для штампа и получение высоты/ширины штампа
            //$marge_right = 10;
            //$marge_bottom = 10;
            //$sx = imagesx($stamp);
            //$sy = imagesy($stamp);
            // Копирование изображения штампа на фотографию с помощью смещения края
            // и ширины фотографии для расчета позиционирования штампа. 
            //imagecopy($im, $stamp, imagesx($im) - $sx - $marge_right, imagesy($im) - $sy - $marge_bottom, 0, 0, imagesx($stamp), imagesy($stamp));
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
function setTwitCountFgt($id)
{
    $sql = "UPDATE `Message2007` SET twits_cnt = twits_cnt + 1 WHERE `Message_ID` = " . $id;
    mysql_query($sql);
}

function getTwitCountFgt($id)
{
    $sql = mysql_fetch_assoc(mysql_query("SELECT twits_cnt FROM `Message2007` WHERE `Message_ID` = " . $id . " LIMIT 0,1"));
    return $sql;
}

function setFbCountFgt($id)
{
    $sql = "UPDATE `Message2007` SET fb_cnt = fb_cnt + 1 WHERE `Message_ID` = " . $id;
    mysql_query($sql);
}

function getFbCountFgt($id)
{
    $sql = mysql_fetch_assoc(mysql_query("SELECT fb_cnt FROM `Message2007` WHERE `Message_ID` = " . $id . " LIMIT 0,1"));
    return $sql;
}
function setVkCountFgt($id)
{
    $sql = "UPDATE `Message2007` SET vk_cnt = vk_cnt + 1 WHERE `Message_ID` = " . $id;
    mysql_query($sql);
}

function getVkCountFgt($id)
{
    $sql = mysql_fetch_assoc(mysql_query("SELECT vk_cnt FROM `Message2007` WHERE `Message_ID` = " . $id . " LIMIT 0,1"));
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

function setBreadcrumbs($cur_url, $action = '', $inner = false, $title = '')
{
    $header = mysql_fetch_assoc(mysql_query("SELECT Value FROM `Classificator_pageheaders` WHERE `pageheaders_Name` = '" . $cur_url . "' LIMIT 0,1"));
    if (!empty($action) && $action == 'results') {
        $header = mysql_fetch_assoc(mysql_query("SELECT Value FROM `Classificator_pageheaders` WHERE `pageheaders_Name` = '" . $cur_url . $action . "' LIMIT 0,1"));
    }
    $page_title = '';
    $add_class = $add_path_class = $add_link_class = $add_arrow_class = '';
    
    switch ($cur_url) {
        case '/news/':
            $page_title = '<h1>' . $header['Value'] . '</h1>';
        break;
        case '/news/news-kickboxing/':
            if ($inner) {
                $add_path_class = ' inner-path-item';
                $add_class = ' inner-pagetitle';
                $add_link_class = 'inner-path-link';
                $add_arrow_class = ' inner-arrow-class';
                
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/news" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Новости</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
                
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/news/news-kickboxing" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Новости кикбоксинга</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
            } else {
                $page_title = '<h1>' . $header['Value'] . '</h1>';
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/news" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Новости</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
            }
        break;
        case '/news/news-boxing/':
            if ($inner) {
                $add_path_class = ' inner-path-item';
                $add_class = ' inner-pagetitle';
                $add_link_class = 'inner-path-link';
                $add_arrow_class = ' inner-arrow-class';
                
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/news" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Новости</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
                
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/news/news-boxing" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Новости бокса</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
            } else {
                $page_title = '<h1>' . $header['Value'] . '</h1>';
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/news" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Новости</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
            }
        break;
        case '/news/news-mma/':
            if ($inner) {
                $add_path_class = ' inner-path-item';
                $add_class = ' inner-pagetitle';
                $add_link_class = 'inner-path-link';
                $add_arrow_class = ' inner-arrow-class';
                
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/news" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Новости</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
                
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/news/news-mma" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Новости ММА</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
            } else {
                $page_title = '<h1>' . $header['Value'] . '</h1>';
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/news" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Новости</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
            }
        break;
        case '/video/':
            if ($inner) {
                $add_path_class = ' inner-path-item';
                $add_class = ' inner-pagetitle';
                $add_link_class = 'inner-path-link';
                $add_arrow_class = ' inner-arrow-class';
                
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/video" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Видео</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
            } else {
                $page_title = '<h1>' . $header['Value'] . '</h1>';
            }
        break;
        case '/video/mma/':
            if ($inner) {
                $add_path_class = ' inner-path-item';
                $add_class = ' inner-pagetitle';
                $add_link_class = 'inner-path-link';
                $add_arrow_class = ' inner-arrow-class';
                
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/video" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Видео</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
                
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/video/mma" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Видео боев ММА</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
            } else {
                $page_title = '<h1>' . $header['Value'] . '</h1>';
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/video" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Видео</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
            }
        break;
        case '/video/boxing/':
            if ($inner) {
                $add_path_class = ' inner-path-item';
                $add_class = ' inner-pagetitle';
                $add_link_class = 'inner-path-link';
                $add_arrow_class = ' inner-arrow-class';
                
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/video" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Видео</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
                
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/video/boxing" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Видео боев по боксу</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
            } else {
                $page_title = '<h1>' . $header['Value'] . '</h1>';
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/video" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Видео</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
            }
        break;
        case '/video/kickboxing/':
            if ($inner) {
                $add_path_class = ' inner-path-item';
                $add_class = ' inner-pagetitle';
                $add_link_class = 'inner-path-link';
                $add_arrow_class = ' inner-arrow-class';
                
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/video" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Видео</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
                
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/video/kickboxing" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Видео боев по кикбоксингу</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
            } else {
                $page_title = '<h1>' . $header['Value'] . '</h1>';
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/video" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Видео</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
            }
        break;
        case '/video/archive/':
            if ($inner) {
                $add_path_class = ' inner-path-item';
                $add_class = ' inner-pagetitle';
                $add_link_class = 'inner-path-link';
                $add_arrow_class = ' inner-arrow-class';
                
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/video" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Видео</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
                
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/video/archive" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Видеоархив боев</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
            } else {
                $page_title = '<h1>' . $header['Value'] . '</h1>';
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/video" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Видео</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
            }
        break;
        case '/video/translation/':
            if ($inner) {
                $add_path_class = ' inner-path-item';
                $add_class = ' inner-pagetitle';
                $add_link_class = 'inner-path-link';
                $add_arrow_class = ' inner-arrow-class';
                
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/video" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Видео</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
                
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/video/translation" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Переводы боев</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
            } else {
                $page_title = '<h1>' . $header['Value'] . '</h1>';
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/video" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Видео</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
            }
        break;
        case '/video/interview/':
            if ($inner) {
                $add_path_class = ' inner-path-item';
                $add_class = ' inner-pagetitle';
                $add_link_class = 'inner-path-link';
                $add_arrow_class = ' inner-arrow-class';
                
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/video" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Видео</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
                
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/video/interview" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Интервью бойцов</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
            } else {
                $page_title = '<h1>' . $header['Value'] . '</h1>';
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/video" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Видео</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
            }
        break;
        case '/articles/':
            $page_title = '<h1>' . $header['Value'] . '</h1>';
        break;
        case '/articles/mma/':
            if ($inner) {
                $add_path_class = ' inner-path-item';
                $add_class = ' inner-pagetitle';
                $add_link_class = 'inner-path-link';
                $add_arrow_class = ' inner-arrow-class';
                
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/articles" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Статьи</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
                
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/articles/mma" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Статьи о ММА</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
            } else {
                $page_title = '<h1>' . $header['Value'] . '</h1>';
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/articles" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Статьи</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
            }
        break;
        case '/articles/boxing/':
            if ($inner) {
                $add_path_class = ' inner-path-item';
                $add_class = ' inner-pagetitle';
                $add_link_class = 'inner-path-link';
                $add_arrow_class = ' inner-arrow-class';
                
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/articles" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Статьи</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
                
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/articles/boxing" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Статьи о боксе</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
            } else {
                $page_title = '<h1>' . $header['Value'] . '</h1>';
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/articles" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Статьи</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
            }
        break;
        case '/articles/kickboxing/':
            if ($inner) {
                $add_path_class = ' inner-path-item';
                $add_class = ' inner-pagetitle';
                $add_link_class = 'inner-path-link';
                $add_arrow_class = ' inner-arrow-class';
                
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/articles" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Статьи</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
                
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/articles/kickboxing" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Статьи о кикбоксинге</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
            } else {
                $page_title = '<h1>' . $header['Value'] . '</h1>';
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/articles" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Статьи</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
            }
        break;
        case '/fight-calendar/':
            $page_title = '<h1>' . $header['Value'] . '</h1>';
        break;
        case '/fight-calendar/mma/':
            if ($inner) {
                $add_path_class = ' inner-path-item';
                $add_class = ' inner-pagetitle';
                $add_link_class = 'inner-path-link';
                $add_arrow_class = ' inner-arrow-class';
                
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/fight-calendar" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">События</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
                
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/fight-calendar/mma" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">События боев ММА</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
            } else {
                $page_title = '<h1>' . $header['Value'] . '</h1>';
                
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/fight-calendar" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">События</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
            }
        break;
        case '/fight-calendar/boxing/':
            if ($inner) {
                $add_path_class = ' inner-path-item';
                $add_class = ' inner-pagetitle';
                $add_link_class = 'inner-path-link';
                $add_arrow_class = ' inner-arrow-class';
                
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/fight-calendar" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">События</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
                
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/fight-calendar/boxing" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">События боев по боксу</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
            } else {
                $page_title = '<h1>' . $header['Value'] . '</h1>';
                
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/fight-calendar" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">События</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
            }
        break;
        case '/fight-calendar/kickboxing/':
            if ($inner) {
                $add_path_class = ' inner-path-item';
                $add_class = ' inner-pagetitle';
                $add_link_class = 'inner-path-link';
                $add_arrow_class = ' inner-arrow-class';
                
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/fight-calendar" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">События</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
                
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/fight-calendar/kickboxing" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">События боев по кикбоксингу</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
            } else {
                $page_title = '<h1>' . $header['Value'] . '</h1>';
                
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/fight-calendar" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">События</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
            }
        break;
        case '/ratings/mma/':
            $page_title = '<h1>' . $header['Value'] . '</h1>';
            $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
            $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
            $breadcrumbs_inner .= '<a href="/ratings" itemprop="url" class="' . $add_link_class . '">';
            $breadcrumbs_inner .= '<span itemprop="title">Рейтинги</span>';
            $breadcrumbs_inner .= '</a>';
            $breadcrumbs_inner .= '</div>';
        break;
        case '/ratings/boxing/':
            $page_title = '<h1>' . $header['Value'] . '</h1>';
            $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
            $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
            $breadcrumbs_inner .= '<a href="/ratings" itemprop="url" class="' . $add_link_class . '">';
            $breadcrumbs_inner .= '<span itemprop="title">Рейтинги</span>';
            $breadcrumbs_inner .= '</a>';
            $breadcrumbs_inner .= '</div>';
        break;
        case '/ratings/kickboxing/':
            $page_title = '<h1>' . $header['Value'] . '</h1>';
            $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
            $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
            $breadcrumbs_inner .= '<a href="/ratings" itemprop="url" class="' . $add_link_class . '">';
            $breadcrumbs_inner .= '<span itemprop="title">Рейтинги</span>';
            $breadcrumbs_inner .= '</a>';
            $breadcrumbs_inner .= '</div>';
        break;
        case '/blogs/':
            $page_title = '<h1>' . $header['Value'] . '</h1>';
        break;
        case '/videoblogi/':
            if ($inner) {
                $add_path_class = ' inner-path-item';
                $add_class = ' inner-pagetitle';
                $add_link_class = 'inner-path-link';
                $add_arrow_class = ' inner-arrow-class';
                
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/videoblogi" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Видеоблоги</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
            } else {
                $page_title = '<h1>' . $header['Value'] . '</h1>';
            }
        break;
        case '/translyatsii/':
            if ($inner) {
                $add_path_class = ' inner-path-item';
                $add_class = ' inner-pagetitle';
                $add_link_class = 'inner-path-link';
                $add_arrow_class = ' inner-arrow-class';
                
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/translyatsii" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Трансляции</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
            } else {
                $page_title = '<h1>' . $header['Value'] . '</h1>';
            }
        break;
        case '/tag/':
            $page_title = '<h1>Новости по теме ' . getRuTagName($_GET['tags']) . '</h1>';
        break;
        case '/fighters/mma/':
            if ($inner) {
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/fighters/mma" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Бойцы ММА</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
                
                if (!empty($title)) {
                    $page_title = '<h1>' . $title . '</h1>';
                }
            } else {
                $page_title = '<h1>' . $header['Value'] . '</h1>';
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/fighters" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Бойцы</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
            }
        break;
        case '/fighters/boxing/':
            if ($inner) {
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/fighters/boxing" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Бойцы бокс</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
                
                if (!empty($title)) {
                    $page_title = '<h1>' . $title . '</h1>';
                }
            } else {
                $page_title = '<h1>' . $header['Value'] . '</h1>';
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/fighters" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Бойцы</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
            }
        break;
        case '/fighters/kickboxing/':
            if ($inner) {
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/fighters/kickboxing" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Бойцы кик</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
                
                if (!empty($title)) {
                    $page_title = '<h1>' . $title . '</h1>';
                }
            } else {
                $page_title = '<h1>' . $header['Value'] . '</h1>';
                $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                $breadcrumbs_inner .= '<a href="/fighters" itemprop="url" class="' . $add_link_class . '">';
                $breadcrumbs_inner .= '<span itemprop="title">Бойцы</span>';
                $breadcrumbs_inner .= '</a>';
                $breadcrumbs_inner .= '</div>';
            }
        break;
        default:
            if (!$inner) {
                $page_title = '<h1>' . $header['Value'] . '</h1>';
            }
            $subs_arr = $subs_arch_arr = [];
            $blogsUrls = nc_get_sub_children('41');
            $res = mysql_query("SELECT Subdivision_ID, Subdivision_Name, Hidden_URL FROM Subdivision WHERE Subdivision_ID IN (".join(',', $blogsUrls).")");
            while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
                if ($row['Subdivision_ID'] != 41) {
                    $subs_arr[] = $row;
                }
            }
            foreach ($subs_arr as $sub) {
                if ($sub['Hidden_URL'] == $cur_url) {
                    if ($inner) {
                        $add_path_class = ' inner-path-item';
                        $add_class = ' inner-pagetitle';
                        $add_link_class = 'inner-path-link';
                        $add_arrow_class = ' inner-arrow-class';
                        
                        $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                        $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                        $breadcrumbs_inner .= '<a href="/blogs" itemprop="url" class="' . $add_link_class . '">';
                        $breadcrumbs_inner .= '<span itemprop="title">Блоги</span>';
                        $breadcrumbs_inner .= '</a>';
                        $breadcrumbs_inner .= '</div>';
                        
                        $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                        $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                        $breadcrumbs_inner .= '<a href="' . $sub['Hidden_URL'] . '" itemprop="url" class="' . $add_link_class . '">';
                        $breadcrumbs_inner .= '<span itemprop="title">' . $sub['Subdivision_Name'] . '</span>';
                        $breadcrumbs_inner .= '</a>';
                        $breadcrumbs_inner .= '</div>';
                    } else {
                        $page_title = '<h1>' . $sub['Subdivision_Name'] . '</h1>';
                        $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                        $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                        $breadcrumbs_inner .= '<a href="/blogs" itemprop="url" class="' . $add_link_class . '">';
                        $breadcrumbs_inner .= '<span itemprop="title">Блоги</span>';
                        $breadcrumbs_inner .= '</a>';
                        $breadcrumbs_inner .= '</div>';
                    }
                }
            }
            $archiveUrls = nc_get_sub_children('153');
            $res = mysql_query("SELECT Subdivision_ID, Subdivision_Name, Hidden_URL FROM Subdivision WHERE Subdivision_ID IN (".join(',', $archiveUrls).")");
            while ($row = mysql_fetch_array($res, MYSQL_ASSOC)) {
                if ($row['Subdivision_ID'] != 41) {
                    $subs_arch_arr[] = $row;
                }
            }
            foreach ($subs_arch_arr as $sub) {
                if ($sub['Hidden_URL'] == $cur_url) {
                    $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                    $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                    $breadcrumbs_inner .= '<a href="/video" itemprop="url" class="' . $add_link_class . '">';
                    $breadcrumbs_inner .= '<span itemprop="title">Видео</span>';
                    $breadcrumbs_inner .= '</a>';
                    $breadcrumbs_inner .= '</div>';
                    
                    $breadcrumbs_inner .= '<span class="path-arrow' . $add_arrow_class . '"></span>';
                    $breadcrumbs_inner .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
                    $breadcrumbs_inner .= '<a href="/video/archive" itemprop="url" class="' . $add_link_class . '">';
                    $breadcrumbs_inner .= '<span itemprop="title">Архив боев</span>';
                    $breadcrumbs_inner .= '</a>';
                    $breadcrumbs_inner .= '</div>';
                }
            }
        break;
    }
    
    $breadcrumbs = '<div class="breadcrumbs-path">';
    $breadcrumbs .= '<div itemscope itemtype="http://data-vocabulary.org/Breadcrumb" class="path-item' . $add_path_class . '">';
    $breadcrumbs .= '<a href="/" itemprop="url" class="' . $add_link_class . '">';
    $breadcrumbs .= '<span itemprop="title">Главная</span>';
    $breadcrumbs .= '</a>';
    $breadcrumbs .= '</div>';        
    $breadcrumbs .= $breadcrumbs_inner;    
    $breadcrumbs .= '<span class="path-last' . $add_arrow_class . '"></span>';
    $breadcrumbs .= '</div>';
    $breadcrumbs .= '<div class="breadcrumbs-pagetitle' . $add_class . '">';
    $breadcrumbs .= $page_title;
    $breadcrumbs .= '</div>';
        
    return $breadcrumbs;
}

function getRuTagName($enTag)
{
    $sql = mysql_fetch_assoc(mysql_query("SELECT myTags, EnTags FROM `Message2000` WHERE `EnTags` LIKE '%" . $enTag . "%' LIMIT 0,1"));
    $RuTags = explode(',', $sql['myTags']);
    $EnTags = explode(',', $sql['EnTags']);
    $Tags = array_combine($RuTags, $EnTags);
    foreach ($Tags as $key => $value) {
        if ($value == $enTag) {
            return $key;
        }
    }
    return false;
}

/*function setTags()
{
    $nc_tags = new nc_tags();
    set_time_limit(10000);
    
    $sql = "SELECT myTags, Message_ID, Sub_Class_ID, Subdivision_ID FROM `Message2000`";
    $res = mysql_query($sql);
    while ($row = mysql_fetch_assoc($res)) {
        $nc_tags->add_message(1, $row['Subdivision_ID'], $row['Sub_Class_ID'], 2000, $row['Message_ID']);
    }
}*/

function calcAge($date)
{
    $date_ts = strtotime($date);
    $age = date('Y') - date('Y', $date_ts);
    if (date('md') < date('md', $date_ts)) {
        $age--;
    }
    return $age;
}

function metricToFoot($metric)
{
    $foots = intval($metric / 30.48);
    $inches = (($metric / 30.48) - $foots) * 30.48 / 2.54;
    $result = $foots . "'" . round($inches) . "''";
    return $result;
}

function kgrammsToLbs($kg)
{
    return round($kg * 2.20462);
}

function setFightersStatistic($events, $other_fights, $type, $f_id)
{
    $fights = [];
    if ($events) {
        foreach ($events as $val) {
            if ($val['type'] == $type) {
                $result = $val['main_card_winner'] == $f_id ? 1 : 2;
                
                $o_id = $val['main_card_fighter_1'] != $f_id ? $o_id = $val['main_card_fighter_1'] : $val['main_card_fighter_2'];
                $opponent = mysql_fetch_assoc(mysql_query("SELECT * FROM Message2007 WHERE Message_ID = " . $o_id));
                
                $fights[] = [
                    'result' => $result,
                    'o_name_ru' => $opponent['myName_ru'],
                    'o_name_en' => $opponent['myName_en'],
                    'event_name' => $val['myName'],
                    'event_date' => $val['myDate'],
                    'referee' => $val['referee'],
                    'win_type' => $val['win_type'],
                    'win_round' => $val['win_round'],
                    'win_time' => $val['win_time'],
                    'video' => $val['video']
                ];
            }
        }
    }
    if ($other_fights) {
        foreach ($other_fights as $val) {
            if ($val['type'] == $type) {
                $result = $val['winner'] == $f_id ? 1 : 2;
                
                $o_id = $val['fighter_1'] != $f_id ? $o_id = $val['fighter_1'] : $val['fighter_2'];
                $opponent = mysql_fetch_assoc(mysql_query("SELECT * FROM Message2007 WHERE Message_ID = " . $o_id));
                
                $event = mysql_fetch_assoc(mysql_query("SELECT * FROM Message2010 WHERE Message_ID = " . $val['event']));
                
                if ($event['myDate'] < date("Y-m-d H:i:s")) {
                    $fights[] = [
                        'result' => $result,
                        'o_name_ru' => $opponent['myName_ru'],
                        'o_name_en' => $opponent['myName_en'],
                        'event_name' => $event['myName'],
                        'event_date' => $event['myDate'],
                        'referee' => $val['referee'],
                        'win_type' => $val['win_type'],
                        'win_round' => $val['win_round'],
                        'win_time' => $val['win_time'],
                        'video' => $val['video']
                    ];
                }
            }
        }
    }
    
    return $fights;
}

function getFrameSrc($str)
{
    require_once $_SERVER['DOCUMENT_ROOT']."/netcat/require/lib/simple_html_dom.php";
    
    $html = str_get_html(htmlspecialchars_decode($str));
    $frame = $html->find('iframe', 0);
    return $frame->src;
}

function getVideoPreview($src, $photo = "")
{
    require_once $_SERVER['DOCUMENT_ROOT']."/netcat/require/lib/simple_html_dom.php";
    
    $url = parse_url($src);
    if ($url['host'] == 'www.youtube.com') {
        $v_name = explode('/', $url['path']);
        $res = '//img.youtube.com/vi/' . $v_name[count($v_name) - 1] . '/hqdefault.jpg';
    }
    if ($url['host'] == 'vk.com') {
        $html = file_get_html('https:' . trim($src));
        $img = $html->find('#video_player', 0);
        $res = $img->poster;
    }
    
    if (empty($res) && !empty($photo)) {
        $res = $photo;
    }
    
    return $res;
}

function setFrameSrc($get)
{
    $url = parse_url($get['src']);
    if ($url['host'] == 'www.youtube.com') {
        return $get['src'] . "?autoplay=1";
    }
    if ($url['host'] == 'vk.com') {
        return $get['src'] . "&id=" . $get['id'] . "&hash=" . $get['hash'] . "&hd=" . $get['hd'] . "&autoplay=1";
    }
}

function getVideoTitle($src)
{
    $url = parse_url($src);
    if ($url['host'] == 'www.youtube.com') {
        $v_name = explode('/', $url['path']);
        $content = file_get_contents("http://youtube.com/get_video_info?video_id=" . $v_name[count($v_name) - 1]);
        parse_str($content, $ytarr);
        return $ytarr['title'];
    }
    if ($url['host'] == 'vk.com') {
        $html = file_get_html('https:' . $src);
        
    }
}

function getBanner()
{
    //$banner = mysql_fetch_assoc(mysql_query("SELECT * FROM Message2052 WHERE status = 1 ORDER BY Created DESC LIMIT 0,1"));
    $banner = mysql_fetch_assoc(mysql_query("SELECT * FROM Message2051 WHERE status = 1 ORDER BY Created DESC LIMIT 0,1"));
    if ($banner) {
        $file = explode(':', $banner['file']);
        $res = [
            'link' => $banner['link'],
            'type' => $banner['type'],
            'src' => $banner['type'] == 1 ? '/netcat_files/' . $file[3] : $banner['text'],
        ];
        return $res;
    }
    return false;
}

function formatFBArticle($text)
{
    require_once $_SERVER['DOCUMENT_ROOT']."/netcat/require/lib/simple_html_dom.php";
    $html = str_get_html(htmlspecialchars_decode($text));
    $frames = $html->find('iframe');
    foreach ($frames as $frame) {
        $src = parse_url($frame->src);
        if (!isset($src['scheme'])) {
            $frame->src = 'https:' . $frame->src;
        }
        $parent = $frame->parent();
        if ($parent->tag != 'root') {
            while ($parent->tag != 'p') {
                $parent = $parent->parent();
            }
            $parent->outertext = $frame;
        }
    }
    
    $imgs = $html->find('img');
    foreach ($imgs as $img) {
        $parent = $img->parent();
        if ($parent->tag != 'root') {
            if ($parent->tag == 'p') {
                $parent->outertext = $img;
            }
        }
    }
    
    $text = $html;
    return $text;
}

function isExclusive()
{
    $exc = mysql_fetch_assoc(mysql_query("SELECT Message_ID FROM Message2000 WHERE exclusive = 1"));
    if ($exc) {
        return true;
    }
    return false;
}

function getExclusive()
{
    $exc = mysql_fetch_assoc(mysql_query("SELECT * FROM Message2000 WHERE exclusive = 1 ORDER BY LastUpdated DESC LIMIT 0,1"));
    if ($exc) {
        $sub = mysql_fetch_assoc(mysql_query("SELECT Hidden_URL FROM Subdivision WHERE Subdivision_ID = " . $exc['Subdivision_ID']));
        $exc['fullLink'] = $sub['Hidden_URL'] . $exc['Keyword'] . ".html";
        
        $exc_photo = explode(":", $exc['myPhoto']);
        $exc['myPhoto'] = '/netcat_files/' . $exc_photo[3];
        
        $exc_type = explode(",", $exc['newsType']);
        $exc['newsType_id'] = $exc_type[1];
        
        $exc['Date_year'] = date('Y', strtotime($exc['Date']));
        $exc['Date_month'] = date('m', strtotime($exc['Date']));
        $exc['Date_day'] = date('d', strtotime($exc['Date']));
        $exc['Date_hours'] = date('H', strtotime($exc['Date']));
        $exc['Date_minutes'] = date('i', strtotime($exc['Date']));
        $exc['Created_year'] = date('Y', strtotime($exc['Created']));
        $exc['Created_month'] = date('m', strtotime($exc['Created']));
        $exc['Created_day'] = date('d', strtotime($exc['Created']));
        $exc['Created_hours'] = date('H', strtotime($exc['Created']));
        $exc['Created_minutes'] = date('i', strtotime($exc['Created']));
        
        $user_name = mysql_fetch_assoc(mysql_query("SELECT ForumName FROM User WHERE User_ID = " . $exc['User_ID'] . " LIMIT 0,1"));
        $exc['user_name'] = $user_name['ForumName'];
        
        $exc['likes_cnt'] = $exc['fb_cnt'] + $exc['vk_cnt'] + $exc['twits_cnt'];
        return $exc;
    }
    return false;
}