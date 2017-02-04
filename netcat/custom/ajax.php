<?php
if ($_POST) {
    $name = htmlspecialchars($_POST["sender_name"]);
    $email = htmlspecialchars($_POST["sender_email"]);
    $message = htmlspecialchars($_POST["sender_comment"]);
    $json = array();
    if (!preg_match("|^[-0-9a-z_\.]+@[-0-9a-z_^\.]+\.[a-z]{2,6}$|i", $email)) {
        $json['error'] = 'Нe вeрный фoрмaт email! >_<';
        echo json_encode($json);
        die();
    }
    
    $to = 'admin@mmaboxing.com';

    if (!empty($_FILES['sender_file']['tmp_name'] ) && $_FILES['sender_file']['error'] == 0) {
        $filepath = $_FILES['sender_file']['tmp_name'];
        $filename = $_FILES['sender_file']['name'];
    } else {
        $filepath = '';
        $filename = '';
    }
 
    $body = "Имя:\r\n".$name."\r\n\r\n";
    $body .= "E-mail:\r\n".$email."\r\n\r\n";
    $body .= "Комментарий:\r\n".$message;
 
    send_mail($to, $body, $email, $filepath, $filename);
    $json['error'] = 0;

    echo json_encode($json); 
}


function send_mail($to, $body, $email, $filepath, $filename)
{
    $subject = 'New material';
    $boundary = "--".md5(uniqid(time()));
    $headers = "From: ".$email."\r\n";   
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .="Content-Type: multipart/mixed; boundary=\"".$boundary."\"\r\n";
    $multipart = "--".$boundary."\r\n";
    $multipart .= "Content-type: text/plain; charset=\"utf-8\"\r\n";
    $multipart .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";

    $body = $body."\r\n\r\n";

    $multipart .= $body;

    $file = '';
    if (!empty($filepath)) {
        $fp = fopen($filepath, "r");
        if ($fp) {
            $content = fread($fp, filesize($filepath));
            fclose($fp);
            $file .= "--".$boundary."\r\n";
            $file .= "Content-Type: application/octet-stream\r\n";
            $file .= "Content-Transfer-Encoding: base64\r\n";
            $file .= "Content-Disposition: attachment; filename==?utf-8?Q?\"".$filename."?=\"\r\n\r\n";
            $file .= chunk_split(base64_encode($content))."\r\n";
        }
    }
    $multipart .= $file."--".$boundary."--\r\n";
    mail($to, $subject, $multipart, $headers);
}