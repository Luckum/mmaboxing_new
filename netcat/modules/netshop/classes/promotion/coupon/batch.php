<?php

class nc_netshop_promotion_coupon_batch extends nc_record {

    protected $primary_key = 'batch_id';

    protected $properties = array(
        'batch_id' => null,
        'coupon_options' => array(
            'deal_type' => null,
            'deal_id' => null,
            'code' => null,
            'code_prefix' => null,
            'code_symbols' => null,
            'code_length' => null,
            'expires' => null,
            'valid_till' => null,
            'max_usages' => null,
        ),
        'mail_options' => array(
            'user_email_field' => null,
            'subject' => null,
            'body' => null,
        ),
        /* Comma-separated user ID list. To get an array, use $this->get_user_ids() */
        'user_ids' => '',
        'num_codes_total' => 0,
        'num_codes_generated' => 0,
        'num_codes_sent' => 0,
    );

    protected $table_name = "Netshop_CouponBatch";
    protected $mapping = array(
        "batch_id" => "Batch_ID",
        "coupon_options" => "CouponOptions",
        "mail_options" => "MailOptions",
        "user_ids" => "UserIDs",
        "num_codes_total" => "NumCodesTotal",
        "num_codes_generated" => "NumCodesGenerated",
        "num_codes_sent" => "NumCodesSent",
    );

    protected $serialized_properties = array('coupon_options', 'mail_options');

    protected $coupon_table_name = "Netshop_Coupon";

    protected $user_ids;
    protected $catalogue_id;

    /**
     *
     */
    public function process_all() {
        if (!$this->get_id()) { $this->save(); } // to get a batch id. Well, that's an overhead; just don’t want to make this a special case
        $batch_size = $this->get('num_codes_total');

        $generate_result = $this->generate_codes($batch_size);
        if (!$generate_result['success']) { return $generate_result; }

        $send_result = $this->send_codes($batch_size);

        $this->delete();

        return $send_result;
    }

    /**
     * @param int|null $batch_size    defaults to 'num_codes_total'
     * @return array
     */
    public function resume($batch_size = null) {
        if (!$this->get_id()) { $this->save(); }

        if (!$batch_size) { $batch_size = $this->get('num_codes_total'); }

        if (!$this->are_all_codes_generated()) { return $this->generate_codes($batch_size); }
        if (!$this->are_all_codes_sent()) { return $this->send_codes($batch_size); }

        return $this->make_response();
    }

    /**
     *
     */
    protected function ceil_batch_size($batch_size, $batch_type) {
        $num_codes_total = $this->get('num_codes_total');
        $num_codes_processed = $this->get($batch_type);

        if ($batch_size + $num_codes_processed > $num_codes_total) {
            $batch_size = $num_codes_total - $num_codes_processed;
        }

        return $batch_size;
    }

    /**
     *
     */
    protected function generate_codes($batch_size) {
        $batch_size = $this->ceil_batch_size($batch_size, 'num_codes_generated');

        $generator = new nc_netshop_promotion_coupon_generator($this->get('coupon_options'), $this->get_id());
        $success = $generator->generate($batch_size, $this->get('num_codes_total') > 1);

        if ($success) {
            $error_message = "";
            // update generated coupon count
            $this->increase_counter("num_codes_generated", $batch_size);
        }
        else {
            // remove this batch! (rollback)
            $this->destroy_batch();
            $error_message = $batch_size <= 1 ? NETCAT_MODULE_NETSHOP_PROMOTION_CANNOT_CREATE_COUPON
                                              : NETCAT_MODULE_NETSHOP_PROMOTION_CANNOT_GENERATE_COUPONS;
        }

        $response = $this->make_response(array(
            "success" => $success,
            "error_message" => $error_message,
            "current_step" => "generate_codes"
        ));

        if ($this->is_finished()) { $this->delete(); }
        return $response;
    }

    /**
     *
     */
    protected function send_codes($batch_size) {
        $batch_size = $this->ceil_batch_size($batch_size, 'num_codes_sent');

        $all_users = $this->get_user_ids(); // should come sanitized already
        if (!sizeof($all_users)) { // what d’ya want from me?!
            return $this->make_response(array("success" => true, "current_step" => "send_codes"));
        }

        $first_user_index = $this->get('num_codes_sent');
        $batch_user_ids = array_slice($all_users, $first_user_index, $batch_size);

        $mail_options = $this->get('mail_options');
        $catalogue_id = $this->get_catalogue_id();

        // check settings
        $are_settings_ok = isset($mail_options['body']) && strlen($mail_options['body']) &&
                           isset($mail_options['subject']) && strlen($mail_options['subject']) &&
                           $catalogue_id &&
                           preg_match("/^\w+$/", $mail_options['user_email_field']);

        if (!$are_settings_ok) {
            $this->destroy_batch();
            return $this->make_response(array(
                "success" => false,
                "error_message" => NETCAT_MODULE_NETSHOP_PROMOTION_CANNOT_SEND_COUPONS,
                "current_step" => "send_codes"
            ));
        }

        $db = nc_db();
        $user_data = (array)$db->get_results(
            "SELECT *, `$mail_options[user_email_field]` as 'User_Email'
               FROM `User`
              WHERE `User_ID` IN (" . join(",", $batch_user_ids) . ")",
            ARRAY_A
        );

        $coupon_data = (array)$db->get_results(
            "SELECT *
               FROM `$this->coupon_table_name`
              WHERE `Batch_ID` = " . $this->escape_value($this->get_id()) . "
                AND (`SentTo_User_ID` = 0 OR `SentTo_User_ID` IS NULL)
              LIMIT " . (int)$batch_size,
            ARRAY_A
        );

        if (count($coupon_data) < count($user_data)) { // WTF?!
            //$this->destroy_batch();
            $this->delete();
            return $this->make_response(array(
                "success" => false,
                "error_message" => NETCAT_MODULE_NETSHOP_PROMOTION_CANNOT_SEND_COUPONS,
                "current_step" => "send_codes"
            ));
        }

        $netshop = nc_netshop::get_instance($catalogue_id);

        $template = new nc_netshop_mailer_template($mail_options);
        $context = array(
            'site' => nc_core('catalogue')->get_by_id($catalogue_id),
            'shop' => $netshop->settings
        );

        $sending_method = ($this->get('num_codes_total') > 1) ? "queue" : "send";

        foreach ($user_data as $i => $user) {
            $coupon = new nc_netshop_promotion_coupon();
            $coupon->set_values_from_database_result($coupon_data[$i]);
            $coupon->set('sent_to_user_id', $user['User_ID']);

            $context['user'] = $user;
            $context['coupon'] = $coupon;

            $message = $template->compose_message($context);
            $netshop->mailer->$sending_method($user['User_Email'], $message);

            $coupon->save();
        }

        $this->increase_counter("num_codes_sent", $batch_size);

        $response = $this->make_response(array(
            "current_step" => "send_codes"
        ));

        if ($this->is_finished()) { $this->delete(); }

        return $response;
    }

    /**
     * Increase value of 'num_codes_generated' and 'num_codes_sent' options
     */
    protected function increase_counter($option_name, $increase_value) {
        $new_value = min($this->get($option_name) + $increase_value, $this->get('num_codes_total'));
        $this->set($option_name, $new_value);
        $this->save();
    }

    /**
     *
     */
    protected function destroy_batch() {
        nc_db()->query(
            "DELETE FROM `$this->coupon_table_name` WHERE `Batch_ID` = " . $this->escape_value($this->get_id())
        );
        $this->delete();
    }

    /**
     * Makes an array with the batch generation status (e.g. to export as JSON)
     */
    protected function make_response(array $extra_options = array()) {
        return array_merge(
            array(
                "success" => true,
                "batch_id" => $this->get_id(),
                "num_codes_total" => $this->get('num_codes_total'),
                "num_codes_generated" => $this->get('num_codes_generated'),
                "num_codes_sent" => $this->get('num_codes_sent'),
                "current_step" => '', // generate_codes|send_codes
                "error_message" => '',
                "finished" => $this->is_finished(),
            ),
            $extra_options
        );
    }

    /**
     * @return array
     */
    protected function get_user_ids() {
        if ($this->user_ids === null) {
            $this->user_ids = array();
            $raw_ids = $this->get('user_ids');
            if (preg_match("/^[\d,]+$/", $raw_ids)) {
                $this->user_ids = explode(",", $raw_ids);
            }
        }
        return $this->user_ids;
    }

    /**
     *
     */
    protected function get_catalogue_id() {
        if ($this->catalogue_id === null) {
            $options = $this->get('coupon_options');
            /** @var nc_netshop_promotion_deal $deal */
            $deal = nc_netshop_promotion_deal::by_id($options['deal_type'], $options['deal_id']);
            $this->catalogue_id = $deal->get('catalogue_id');
        }
        return $this->catalogue_id;
    }

    /**
     * @return bool
     */
    public function are_all_codes_generated() {
        return ($this->get('num_codes_generated') >= $this->get('num_codes_total'));
    }

    /**
     * @return bool
     */
    public function are_all_codes_sent() {
        return (
            count($this->get_user_ids()) == 0 ||
            $this->get('num_codes_sent') >= $this->get('num_codes_total')
        );
    }

    /**
     * @return bool
     */
    public function is_finished() {
        return ($this->are_all_codes_generated() && $this->are_all_codes_sent());
    }

}