<?php

/* $Id: function.inc.php 6209 2012-02-10 10:28:29Z denis $ */

function poll_alreadyAnswered($classID, $PollID, $ProtectIP, $ProtectUsers) {
    global $db, $REMOTE_ADDR;

    $PollID = intval($PollID);
    $classID = intval($classID);

    if ($ProtectIP == 1) {
        $Result = $db->query("SELECT `Message_ID` FROM `Poll_Protect` WHERE `Message_ID` = '".$PollID."' AND `IP` = '".$db->escape($REMOTE_ADDR)."'");
        if ($db->num_rows) return true;
    }

    if ($ProtectUsers == 1) {
        $User_ID = Authorize();
        if (!$User_ID) return true;

        $Result = $db->query("SELECT `Message_ID` FROM `Poll_Protect` WHERE `Message_ID` = '".$PollID."' AND `User_ID` = '".intval($User_ID)."'");
        if ($db->num_rows) return true;
    }

    if ($_COOKIE["Poll".$PollID."class".$classID]) return true;

    return false;
}

function poll_percentLine($classID, $PollID, $AnswerCount, $MaxWidth, $template) {
    global $db, $MODULE_VARS;

    $PollID = intval($PollID);
    $classID = intval($classID);

    static $storage_votesum = array(), $storage_votemax = array();
    if (!$storage_votesum[$PollID]) {
        $res = $db->get_row("SELECT (IF(Answer1<>'',Count1,0)+IF(Answer2<>'',Count2,0)+IF(Answer3<>'',Count3,0)+IF(Answer4<>'',Count4,0)+IF(Answer5<>'',Count5,0)+IF(Answer6<>'',Count6,0)+IF(Answer7<>'',Count7,0)+IF(Answer8<>'',Count8,0)+IF(Answer9<>'',Count9,0)+IF(Answer10<>'',Count10,0)+IF(Answer11<>'0',Count11,0)) AS Sum, GREATEST(IF(Answer1<>'',Count1,0),IF(Answer2<>'',Count2,0),IF(Answer3<>'',Count3,0),IF(Answer4<>'',Count4,0),IF(Answer5<>'',Count5,0),IF(Answer6<>'',Count6,0),IF(Answer7<>'',Count7,0),IF(Answer8<>'',Count8,0),IF(Answer9<>'',Count9,0),IF(Answer10<>'',Count10,0),IF(Answer11<>'0',Count11,0)) AS Max FROM `Message".intval($classID)."` WHERE `Message_ID` = '".intval($PollID)."'", ARRAY_N);
        $storage_votesum[$PollID] = $res[0];
        $storage_votemax[$PollID] = $res[1];
    }

    $votesum = $storage_votesum[$PollID];
    $votemax = $storage_votemax[$PollID];




    if (!$votemax) $votemax = 1;
    if (!$votesum) $votesum = 1;

    $line_width = round(($MaxWidth / $votemax) * $AnswerCount);
    $line_percent = round(($AnswerCount / $votesum) * 100);

    $template = str_replace("%PERCENT", $line_percent, $template);
    $template = str_replace("%WIDTH", $line_width, $template);
    eval("\$result = \"".$template."\";");

    return $result;
}

function poll_alternativeAnswer($classID, $PollID) {
    global $db, $MODULE_VARS;

    $PollID = intval($PollID);
    $classID = intval($classID);

    $Answers = htmlspecialchars($db->get_var("SELECT `AltAnswer` FROM `Message".intval($classID)."` WHERE `Message_ID` = '".intval($PollID)."'"));
    $Answers = explode("\r\n", $Answers);
    $result = "<ol type='1'>";

    for ($i = 0; $i < count($Answers); $i++) {
        $result.= "<li>".$Answers[$i];
    }

    return $result."</ol>";
}
?>