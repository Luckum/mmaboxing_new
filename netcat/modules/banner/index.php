<?php

/* $Id: index.php 6206 2012-02-10 10:12:34Z denis $ */

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require ($ROOT_FOLDER."connect_io.php");



if (is_file($MODULE_FOLDER."banner/".MAIN_LANG.".lang.php")) {
    require_once($MODULE_FOLDER."banner/".MAIN_LANG.".lang.php");
    $modules_lang = "Russian";
} else {
    require_once($MODULE_FOLDER."banner/en.lang.php");
    $modules_lang = "English";
}

$MODULE_VARS = $nc_core->modules->load_env($modules_lang);

$b = $_GET['b'];

if ($f_Size) {
    $size_name=$db->get_var("SELECT `BannerSize_Name` FROM `Classificator_BannerSize` WHERE `BannerSize_ID`='".intval($f_Size)."'");
    ?>
    <hr size=1>
    <?=opt_case($banners2show=listQuery("SELECT `Message_ID`,`Alt` FROM `Message".$MODULE_VARS['banner']['BANNER_TABLE']."` WHERE `Size`".opt_case($f_Size,"='".intval($f_Size)."'"," IS NULL")." AND `Status`=1","<input type='checkbox' value='\$data[Message_ID]' id='b\$data[Message_ID]' name='banner[]'> <label for='b\$data[Message_ID]'>#\$data[Message_ID]: \$data[Alt]</label><br>"),"<b>Показывать следующие баннеры:</b><br>".$banners2show,"Не добавлено ни одного баннера с размером $size_name.")?>."
    <hr size=1>
    <?=opt_case($zone2show=listQuery("SELECT `Message_ID`,`Name` FROM `Message".$MODULE_VARS['banner']['ZONE_TABLE']."` WHERE `Size`".opt_case($f_Size,"='".intval($f_Size)."'"," IS NULL"),"<input type='checkbox' value='\$data[Message_ID]' id='z\$data[Message_ID]' name='zone[]'> <label for='z\$data[Message_ID]'>#\$data[Message_ID]: \$data[Name]</label><br>"),"<b>Показывать выбранные баннеры в следующих зонах:</b><br>".$zone2show,"Не создано ни одной рекламной зоны с размером $size_name.")?>
    <hr size=1>
     <? exit; 
}

if ($click) {
    if (!isset($b) || !is_numeric($b) || !$b) exit;
    if (!isset($z) || !is_numeric($z) || !isset($c) || !is_numeric($c)) exit;

    $link = banner_url($b);
    if (!$link) exit;

    if (!(isset($adm) && $adm == 1)) {
        banner_stats($c, $z, $b, $r, $rnd, 1);
    }

    if ($REDIRECT_STATUS == 'on') header("Location: ".$link);
    else echo "<meta http-equiv=refresh content='0;url={$link}'>";
}
else {
    if (!(isset($adm) && $adm == 1)) {
        banner_stats(intval($c), intval($z), intval($b), $r, $rnd, 0);
    }

    header('Content-type: image/gif');
    readfile('./emp.gif');
    exit;
}
?>