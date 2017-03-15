<?php

/* $Id: function.inc.php 8300 2012-10-29 14:42:06Z vadim $ */

/**
 * Функция для генерации хэша
 * @return string хэш
 */
function nc_captcha_generate_hash() {
    return md5(uniqid(rand(), true));
}

/**
 * Формирование кодя для картинки
 * @param string $captcha_hash соответствующий хэш-код
 * @return string символы для каптчи
 */
function nc_captcha_generate_code($captcha_hash) {
    $nc_core = nc_Core::get_object();
    // БД и настройки модуля
    $db = $nc_core->db;
    $MODULE_VARS = $nc_core->modules->get_module_vars();

    // Настройки параметров алгоритма генерации кода
    $alphabet = ( $MODULE_VARS['captcha']['ALPHABET'] ? $MODULE_VARS['captcha']['ALPHABET'] : 'ABCDEFGHKLMNOPRSTUVWXYZ');
    $num_chars = ( $MODULE_VARS['captcha']['NUMBER_OF_CHARS'] ? $MODULE_VARS['captcha']['NUMBER_OF_CHARS'] : 5);

    if (strpos($num_chars, "..")) {
        $num_chars = nc_preg_split("|\.+|", $num_chars);
        $num_chars = rand($num_chars[0], $num_chars[1]);
    }

    // Генерация случайной последовательности символов
    $alphabet_size = strlen($alphabet);
    $captcha_code = '';
    for ($i = 0; $i < $num_chars; $i++) {
        $captcha_code .= $alphabet[rand(0, $alphabet_size - 1)];
    }

    // Сохранить сгенерированный код
    $db->query("REPLACE INTO `Captchas` (`Captcha_Hash`, `Captcha_Code`)
              VALUES ('".$db->escape($captcha_hash)."', '".$db->escape($captcha_code)."')");

    // Обновление захешированных файлов для аудиокаптчи
    $res = $nc_core->db->get_results("SELECT * FROM `Captchas_Settings`", ARRAY_A);
    if (!empty($res))
            foreach ($res as $v)
            $captcha_settings[$v['Key']] = $v['Value'];

    if ($MODULE_VARS['captcha']['AUDIOCAPTCHA_ENABLED'] &&
        $captcha_settings &&
        is_writable($nc_core->FILES_FOLDER) &&
        is_dir($nc_core->MODULE_FOLDER.'captcha/voice/'.$MODULE_VARS['captcha']['VOICE'].'/') &&
        time() - 3600 >= strtotime($captcha_settings['time']))
    {
        $db->query("UPDATE `Captchas_Settings` SET `Value`= Now() WHERE `Key` = 'time'");
        $from = $nc_core->MODULE_FOLDER.'captcha/voice/'.$MODULE_VARS['captcha']['VOICE'].'/';
        $to = $nc_core->FILES_FOLDER.'captcha/current_voice/';
        $nc_core->files->create_dir($to);

        $enc_mp3_files[] = '';
        $enc_mp3_folder = opendir($to);
        while ($one = readdir($enc_mp3_folder)) {
            if ($one != '.' && $one != '..' && substr(strrchr($one, '.'), 1) == 'mp3') {
                $enc_mp3_files[] = $one;
            }
        }

        $normal_mp3_folder = opendir($from);
        while ($one = readdir($normal_mp3_folder)) {
            $file_hash = nc_captcha_generate_hash();
            if ($one != '.' && $one != '..' && substr(strrchr($one, '.'), 1) == 'mp3') {
                if ($captcha_settings['current_voice'] != $MODULE_VARS['captcha']['VOICE'] || !in_array($captcha_settings[$one], $enc_mp3_files)) {
                    $db->query("UPDATE `Captchas_Settings` SET `Value`= '".$MODULE_VARS['captcha']['VOICE']."' WHERE `Key` = 'current_voice'");
                    if ($captcha_settings['current_voice'] != $MODULE_VARS['captcha']['VOICE']) {
                        unlink($to.$captcha_settings[$one]);
                    }
                    copy($from.$one, $to.$file_hash.'.mp3');
                    $db->query("UPDATE `Captchas_Settings` SET `Value`='".$db->escape($file_hash).".mp3' WHERE `Key` ='".$one."'");
                } else {
                    rename($to.$captcha_settings[$one], $to.$file_hash.'.mp3');
                    $db->query("UPDATE `Captchas_Settings` SET `Value`='".$db->escape($file_hash).".mp3' WHERE `Key` ='".$one."'");
                }
            }
        }
    }
}

/**
 * Проверка кода
 * @param string $user_code символы, введенные с картинки
 * @param string $user_hash соответсвующий ему хэш
 * @param bool $delete_hash удалить хеш после проверки или нет
 * @return bool прошла проверка или нет
 */
function nc_captcha_verify_code($user_code, $user_hash = '', $delete_hash = 1) {
    $nc_core = nc_Core::get_object();
    // БД и настройки модуля
    $db = $nc_core->db;
    $MODULE_VARS = $nc_core->modules->get_module_vars();

    // если код не задан - то он заведомо неверный
    if (!$user_code) {
        return false;
    }
    // имя скрытого поля, через которое передается хэш-код
    $hidden_field_name = ( $MODULE_VARS['captcha']['HIDDEN_FIELD_NAME'] ? $MODULE_VARS['captcha']['HIDDEN_FIELD_NAME'] : 'nc_captcha_hash' );

    // Если хэш не передан, то получить его из GET или POST параметров
    if (!$user_hash || 1) {
        $user_hash = $nc_core->input->fetch_get_post($hidden_field_name);
    }

    static $cashe = array();
    
    if (isset($cashe[$user_code][$user_hash])) {
        return $cashe[$user_code][$user_hash];
    }
    
    // время жизни каптчи
    $captcha_duration = ( $MODULE_VARS['captcha']['DURATION'] ? $MODULE_VARS['captcha']['DURATION'] : 5 * 60 );

    // проверка каптчи
    $captchas_count = (int) $db->get_var("SELECT COUNT(*)
                                        FROM `Captchas`
                                        WHERE UPPER(`Captcha_Code`) = UPPER('".$db->escape($user_code)."')
                                        AND `Captcha_Hash` = '".$db->escape($user_hash)."'
                                        AND UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(`Captcha_Created`) <= ".intval($captcha_duration));

    $captcha_valid = ($captchas_count == 1);
 
    $cashe[$user_code][$user_hash] = $captcha_valid;
    
    // удаление устарвших значений
    if ($delete_hash) {
        $db->query("DELETE FROM `Captchas`
                WHERE `Captcha_Hash` = '".$db->escape($user_hash)."'
                OR UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(`Captcha_Created`) >= ".intval($captcha_duration));
    }

    return $captcha_valid;
}

/**
 *  Функция для вывода формы с картинкой и скрытым полем
 * @param <type> $attributes атрибуты, которые попадут в тэг img
 * @param string $beg - открывающий html-код кнопки аудиокапчи
 * @param string $mid - текст кнопки аудиокапчи
 * @param string $end - закрывающий html-код кнопки аудиокапчи
 * @param string $btn - текст кнопки обновления капчи
 * @param bool $norefresh - не выводить кнопку обновления
 * @param int|string $id - суффикс для работы нескольких каптч на странице
 * @return string html-код
 */
function nc_captcha_formfield($attributes = '', $beg = '', $mid = '', $end = '', $btn = '', $norefresh = 0, $id = 0) {
    static $count = 0;
    $id = $id ? $id : $count;
    $beg = $beg ? $beg : "<span class='nc_captcha_voice' style='margin:0 0 0 10px;'><a class='nc_captcha_js' href='#' onclick='nc_captcha$id.play();return false;'>&#9834;";
    $mid = $mid ? $mid : NETCAT_MODULE_CAPTCHA_AUDIO_LISTEN;
    $end = $end ? $end : "</a><span id='nc_captcha_player' style='display:none;'></span></span>";
    $btn = $btn ? $btn : NETCAT_MODULE_CAPTCHA_REFRESH;
    $audio_captcha = $beg.$mid.$end;
    $nc_core = nc_Core::get_object();
    // настройки модуля
    $MODULE_VARS = $nc_core->modules->get_module_vars();

    $captcha_hash = nc_captcha_generate_hash();
    nc_captcha_generate_code($captcha_hash);
    $hidden_field_name = ( $MODULE_VARS['captcha']['HIDDEN_FIELD_NAME'] ? $MODULE_VARS['captcha']['HIDDEN_FIELD_NAME'] : 'nc_captcha_hash');
    $module_path = $nc_core->SUB_FOLDER.$nc_core->HTTP_ROOT_PATH."modules/captcha/";

    // Аудио каптча
    if ($MODULE_VARS['captcha']['AUDIOCAPTCHA_ENABLED']) {
        $player = "<script type='text/javascript' src='".$module_path."player/swfobject.js'></script><script type='text/javascript' src='".$module_path."player/uppod_player.js'></script><script type='text/javascript' src='".$module_path."nc_audiocaptcha.js'></script>";
        $playlist = "{'playlist':[";
        $code = $nc_core->db->get_row("SELECT `Captcha_Code` FROM `Captchas` WHERE `Captcha_Hash` = '".$captcha_hash."'");
        $code_hash = $nc_core->db->get_results("SELECT * FROM `Captchas_Settings` WHERE `Key` != 'time'");
        foreach (str_split($code->Captcha_Code) as $letter) {
            $letter = strtolower($letter);
            foreach ($code_hash as $hash) {
                if ($hash->Key == $letter.'.mp3') {
                    $h = $hash->Value;
                    break;
                }
            }
            $playlist .= "{'file':'".$nc_core->HTTP_FILES_PATH."captcha/current_voice/".$h."'},";
        }
        $playlist .= "]}";
        $playlist = str_replace('},]}', '}]}', $playlist);
        $player_init = "<script type='text/javascript'>var nc_captcha$id = new nc_audiocaptcha(\"".$module_path."\", \"".$playlist."\");</script>";
        $cap = (is_writable($nc_core->FILES_FOLDER) && is_dir($nc_core->MODULE_FOLDER.'captcha/voice/'.$MODULE_VARS['captcha']['VOICE'].'/') ? $player.$player_init.$audio_captcha : '');
    }

    $result = (!$_GET['nc_get_new_captcha'] && !$norefresh ? "<div id='nc_captcha_container".$id."' style='display:inline-block'>" : "" )."
              <input type='hidden' name='".$hidden_field_name."' value='".$captcha_hash."' />
              <img name='nc_captcha_img' src='".$nc_core->SUB_FOLDER.$nc_core->HTTP_ROOT_PATH."modules/captcha/img.php?code=".$captcha_hash."'".($attributes ? " ".$attributes : "")." />
              ".( $MODULE_VARS['captcha']['AUDIOCAPTCHA_ENABLED'] ? $cap : '').  
              (!$_GET['nc_get_new_captcha'] && !$norefresh ? "</div>
              <button id='nc_captcha_refresh_button".$id."'>".$btn."</button>
              ".nc_ajax_captcha_js('nc_captcha_container'.$id, 'nc_captcha_refresh_button'.$id, 'nc_captcha'.$id ) : "" );

    $count++;
    return $result;
}

function nc_ajax_captcha_js($id_captcha_container, $id_refresh_button, $play_object) {
    $nc_core = nc_Core::get_object();
    $MODULE_VARS = $nc_core->modules->get_module_vars();
    $hidden_field_name = ( $MODULE_VARS['captcha']['HIDDEN_FIELD_NAME'] ? $MODULE_VARS['captcha']['HIDDEN_FIELD_NAME'] : 'nc_captcha_hash');
    $module_path = $nc_core->SUB_FOLDER.$nc_core->HTTP_ROOT_PATH."modules/captcha/";
    return "<script type='text/javascript'>
            jQuery('#" . $id_refresh_button . "').click(function() {
                jQuery.ajax({url: '".$module_path."index.php?nc_get_new_captcha=1',
                        success: function(result) {
                            var res = result.split('#');
                            jQuery('#" . $id_captcha_container . " img[name=nc_captcha_img]').attr('src', '".$module_path."img.php?code=' + res[0]);
                            jQuery('#" . $id_captcha_container . " input[name=".$hidden_field_name."]').attr('value', res[0]);
                            ".$play_object." = new nc_audiocaptcha('".$module_path."', res[1]);
                        }});
                 return false;
            });
        </script>";
}

/**
 * Формирование картинки
 * @param string $captcha_hash хэш
 * @return картинка
 */
function nc_captcha_image($captcha_hash) {

    $nc_core = nc_Core::get_object();
    // настройки модуля
    $MODULE_VARS = $nc_core->modules->get_module_vars();

    $ttf_font_file = $nc_core->MODULE_FOLDER."captcha/font.ttf";
    if (!file_exists($ttf_font_file)) {
        $ttf_font_file = $GLOBALS['ROOT_FOLDER']."require/font/default.ttf";
    }

    //$captcha_code = nc_captcha_generate_code($captcha_hash);
    $captcha_code = $nc_core->db->get_var("SELECT `Captcha_Code` FROM `Captchas` WHERE `Captcha_Hash` = '" . $nc_core->db->escape($captcha_hash) . "'");

    // Если пользователем определена собственная функция для создания картинки,
    // вернуть ее результат
    include_once($nc_core->MODULE_FOLDER."captcha/user_functions.inc.php");
    if (function_exists('nc_captcha_user_image')) {
        return nc_captcha_user_image($captcha_code);
    }

    // "Стандартный" алгоритм
    // Функция работает только при наличии библиотеки GD с поддержкой GIF
    if (!function_exists("imagegif")) {
        return false;
    }

    $num_chars = strlen($captcha_code);

    // Настройки параметров алгоритма генерации картинки

    $img_width = ( $MODULE_VARS['captcha']['IMAGE_WIDTH'] ? $MODULE_VARS['captcha']['IMAGE_WIDTH'] : 150 );
    $img_height = ( $MODULE_VARS['captcha']['IMAGE_HEIGHT'] ? $MODULE_VARS['captcha']['IMAGE_HEIGHT'] : 30 );
    $num_lines = ( isset($MODULE_VARS['captcha']['NUMBER_OF_LINES']) ? $MODULE_VARS['captcha']['NUMBER_OF_LINES'] : 30 );

    $letter_width = (int) ($img_width / $num_chars);
    $captcha_image = imagecreate($img_width, $img_height);

    // Белый фон
    imagecolorallocate($captcha_image, 255, 255, 255);

    $background_color = imagecolorallocate($captcha_image, 255, 255, 255);
    $grey_color = rand(165, 225);
    $noise_color = imagecolorallocate($captcha_image, $grey_color, $grey_color, $grey_color);

    for ($i = 0; $i < $num_lines; $i++) {
        imageline($captcha_image, mt_rand(0, $img_width), mt_rand(0, $img_height), mt_rand(0, $img_width), mt_rand(0, $img_height), $noise_color);
    }

    for ($i = 0; $i < $num_chars; $i++) {
        $font_size = $img_height * round(rand(45, 70) / 100, 2);
        $text_color = imagecolorallocate($captcha_image, rand(0, 128), rand(0, 128), rand(0, 128));
        if (function_exists("imagettftext")) {
            $rand_x = rand(0, $letter_width - $font_size);
            $rand_y = $img_height - round(($img_height - $font_size) / 2);
            imagettftext($captcha_image, $font_size, rand(-30, 30), $i * $letter_width + $rand_x, $rand_y, $text_color, $ttf_font_file, $captcha_code[$i]);
        } else {
            $rand_font = rand(2, 5);
            $rand_x = rand(0, $letter_width - imagefontwidth($rand_font));
            $rand_y = rand(0, $img_height - imagefontheight($rand_font));
            imagestring($captcha_image, $rand_font, $i * $letter_width + $rand_x, $rand_y, $captcha_code[$i], $text_color);
        }
    }

    if (preg_match("/^(\d)/is", phpversion(), $matches) && function_exists("imagettftext") && function_exists("imagefilter")) {
        if ($matches[1] == 5)
                imagefilter($captcha_image, IMG_FILTER_GAUSSIAN_BLUR);
    }

    return imagegif($captcha_image);
}