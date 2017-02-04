<?php

/**
 * Input:
 * - batch_id: continue this batch
 *
 * if batch_id is not set:
 * - options (array): coupon options
 * - mail (array): message options
 * - send_to_users (0|1): whether should send messages or not
 * - user_ids (string): comma-delimited list of user IDs
 * - num_codes (number)
 *
 * Response: JSON object; @see nc_netshop_promotion_coupon_batch::make_response()
 *
 */
require '../../no_header.inc.php';

@set_time_limit(0);

/** @var nc_input $input */
$input = nc_core('input');

$batch_id = (int)$input->fetch_post('batch_id');
$batch_size = (int)$input->fetch_post('batch_size');

if (!$batch_id) {
    $batch = new nc_netshop_promotion_coupon_batch(array(
        'coupon_options' => $input->fetch_post('coupon_options'),
        'mail_options' => $input->fetch_post('mail_options'),
        'user_ids' => $input->fetch_post('send_to_users') ? $input->fetch_post('user_ids') : '',
        'num_codes_total' => $input->fetch_post('num_codes'),
    ));
}
else {
    $batch = new nc_netshop_promotion_coupon_batch($batch_id);
}

echo nc_array_json($batch->resume($batch_size));