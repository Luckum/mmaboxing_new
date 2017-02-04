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
			return (!$pc ? $res[1].' ('.$pco.'%)' : $pco);
			break;
		}
	}
}

// Запись результата опроса

function addArrogRes($vid, $check, $class_id)
{
	if(!is_numeric($_GET['vid'])){ die(); }
	if(md5($_SERVER['REMOTE_ADDR'].$_GET['vid']) == $_GET['cache'])
	{
	setcookie ("interogation".$_GET['vid'], $_GET['cache'], time() + 3600*9999, '/');
		if(is_numeric($vid))
		{
			$sql = mysql_fetch_assoc(mysql_query("SELECT answers FROM Message".$class_id." WHERE Message_ID=".$vid." LIMIT 0,1"));
			if($sql['answers'])
			{
				$arr = explode(',',$sql['answers']);
				foreach($arr as $key=>$val)
					{
						$res = explode('-',$val);
						if($res[0] == $_POST['vote_check'])
						{
							$arr[$key] = $res[0].'-'.($res[1]+1);
							break;
						}
					}
				$sql_ins = "UPDATE Message".$class_id." SET answers='".implode(',',$arr)."' WHERE Message_ID=".$vid;
				mysql_query($sql_ins);
				echo $sql_ins;
				
			}
		}
	}
	header('Location: '.$_SERVER['HTTP_REFERER'].'');
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

?>