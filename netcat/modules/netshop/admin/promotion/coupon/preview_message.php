<?php

require '../../no_header.inc.php';

/**
 * Input
 *   - coupon_options — coupon options
 *   - mail_options — mail options
 *   - catalogue_id
 */

/** @var nc_input $input */
$input = nc_core('input');

$mail_options = (array)$input->fetch_post('mail_options');
$catalogue_id = (int)$input->fetch_post('catalogue_id');

$user_ids = explode(",", $input->fetch_post('user_ids'));
$random_user_id = (int)$user_ids[array_rand($user_ids)];
$random_user = nc_db()->get_row("SELECT * FROM `User` WHERE `User_ID`=$random_user_id", ARRAY_A);

$netshop = nc_netshop::get_instance($catalogue_id);
$coupon_generator = new nc_netshop_promotion_coupon_generator(
    $input->fetch_post('coupon_options'),
    0
);

$template = new nc_netshop_mailer_template($mail_options);
$message = $template->compose_message(array(
    'site' => nc_core('catalogue')->get_by_id($catalogue_id),
    'shop' => $netshop->settings,
    'coupon' => $coupon_generator->get_fake_coupon(count($user_ids) < 2),
    'user' => $random_user,
));


// variables required for the included script:
$mail_to = $random_user[$mail_options['user_email_field']];
$mail_subject = $message->get_subject();
$mail_body = $message->get_body();
// $catalogue_id is already set

require nc_module_folder('netshop') . "admin/mailer/preview_message.inc.php";