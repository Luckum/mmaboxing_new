<?php

class nc_netshop_promotion_coupon_generator {

    protected $catalogue_id;

    protected $options = array(
        'deal_type' => '',
        'deal_id' => '',
        'code' => null,
        'code_prefix' => '',
        'code_symbols' => NETCAT_MODULE_NETSHOP_PROMOTION_GENERATED_COUPON_CODE_SYMBOLS_DEFAULT_VALUE,
        'code_length' => 10,
        'expires' => false,
        'valid_till' => null, // if you want to set 'valid_till', 'expires' MUST be true
        'max_usages' => 0,
    );

    protected $batch_id;

    const MAX_CODE_LENGTH = 64;
    protected $table_name = "Netshop_Coupon";

    protected $symbols = array();
    protected $generated_codes = array();
    protected $unavailable_codes = array();
    protected $max_possible_codes = 0;

    protected $num_cycles_until_switch_to_sequential_generation = 10;

    /** @var nc_db */
    protected $db;

    /** @var array  used in sequential code generation */
    protected $last_sequence_indexes;

    /**
     * @param array $options
     * @param $batch_id
     */
    public function __construct(array $options, $batch_id) {
        $deal = nc_netshop_promotion_deal::by_id($options['deal_type'], $options['deal_id']);
        if (!$deal) { trigger_error('Wrong deal type or ID', E_USER_ERROR); }
        $this->catalogue_id = (int)$deal->get('catalogue_id');

        foreach ($options as $k => $v) {
            if (array_key_exists($k, $this->options)) { $this->options[$k] = $v; }
        }

        if ($this->options['expires'] && $this->options['valid_till']) {
            $this->options['valid_till'] = date("Y-m-d H:i:s", strtotime($this->options['valid_till']));
        }
        else {
            $this->options['valid_till'] = null;
        }

        $this->batch_id = $batch_id;

        $this->set_symbols();
        $this->db = nc_core('db');
    }

    /**
     *
     */
    protected function set_symbols() {
        $nc_core = nc_core();
        $symbols = preg_replace("/\s+/", "", $this->options['code_symbols']);
        $symbols = mb_convert_case($symbols, MB_CASE_UPPER, $nc_core->NC_CHARSET);

        if ($nc_core->NC_UNICODE) {
            $this->symbols = array_unique(preg_split("//u", $symbols, -1, PREG_SPLIT_NO_EMPTY));
        }
        else {
            $this->symbols = array_unique(str_split($symbols));
        }

        $this->max_possible_codes = pow(sizeof($this->symbols), $this->options['code_length']);
    }

    /**
     *
     */
    public function generate($num_codes, $part_of_batch = false) {
        if ($num_codes < 0) { return false; }

        if ($num_codes == 1 && !$part_of_batch) {
            $code = $this->options['code'];
            if (!$this->check_single_code($code)) { return false; }
            $this->create_coupon($code)->save();
            return true;
        }

        // ensure we can have required number of new coupons
        $t = microtime(true);
        if (!$this->generate_and_check_codes($num_codes)) { return false; }

        // save coupons
        $coupon_options = array(
            "Catalogue_ID" => (int) $this->catalogue_id,
            "Code" => '',
            "Deal_Type" => "'" . $this->db->escape($this->options['deal_type']) . "'",
            "Deal_ID" => (int)$this->options['deal_id'],
            "MaxUsages" => (int)$this->options['max_usages'],
            "UsageCount" => 0,
            "ValidTill" => $this->options['valid_till'] ? "'" . $this->db->escape($this->options['valid_till']) . "'" : "NULL",
            "Enabled" => 1,
            "Batch_ID" => (int)$this->batch_id,
        );
        $insert_data = array();
        foreach ($this->generated_codes as $code) {
            // $this->create_coupon($code)->save();
            $coupon_options['Code'] = "'" . $this->db->escape($code) . "'";
            $insert_data[] = "(" . join(",", $coupon_options) . ")";
        }
        $query = "INSERT INTO `{$this->table_name}` (`" . join("`, `", array_keys($coupon_options)) . "`) VALUES " .
                 join(",", $insert_data);

        $this->db->query($query);

        return true;
    }

    /**
     * This method can be used to create an email preview
     */
    public function get_fake_coupon($use_single_code = false) {
        if ($use_single_code && $this->options['code']) {
            $code = $this->options['code'];
        }
        else {
            $this->generate_and_check_codes(1);
            $code = array_shift($this->generated_codes);
        }

        return $this->create_coupon($code);
    }

    /**
     *
     */
    protected function generate_and_check_codes($num_codes) {
        // is it possible to create required number of codes with this settings?
        if ($this->max_possible_codes < $num_codes) { return false; }

        // check max length constraints
        if ($this->options['code_length'] + nc_strlen($this->options['code_prefix']) > self::MAX_CODE_LENGTH) {
            return false;
        }

        $generation_strategy = "get_random_string";
        $cycle_number = 0;

        do {
            if (++$cycle_number == $this->num_cycles_until_switch_to_sequential_generation) {
                $generation_strategy = "get_next_sequence";
            }

            // can’t do anything else
            if ($this->count_total_generated_codes() >= $this->max_possible_codes) {
                return sizeof($this->generated_codes) == $num_codes;
            }

            // (1) generate codes
            $codes = $this->generate_codes($num_codes - sizeof($this->generated_codes), $generation_strategy);
            // (2) check generated codes
            $this->check_codes_in_database($codes);


        } while (sizeof($this->generated_codes) < $num_codes);

        return true;
    }

    /**
     * (May return LESS THAN $num_codes codes!)
     */
    protected function generate_codes($num_codes, $strategy = "get_random_string") {
        $codes = array();
        $code_prefix = $this->options['code_prefix'];
        $length = $this->options['code_length'];

        for ($i = 0; $i < $num_codes; $i++) {
            $code = $code_prefix . $this->$strategy($length);
            if (!in_array($code, $this->unavailable_codes) && !in_array($code, $this->generated_codes)) {
                $codes[] = $code;
            }
        }
        return array_unique($codes);
    }

    /**
     * @return int
     */
    protected function count_total_generated_codes() {
        return sizeof($this->generated_codes) + sizeof($this->unavailable_codes);
    }

    /**
     * @param $codes
     */
    protected function check_codes_in_database($codes) {
        $all_codes = array();
        foreach ($codes as $code) {
            $all_codes[] = "'" . $this->db->escape($code) . "'";
        }

        $existing_codes = $this->db->get_col(
            "SELECT `Code`
               FROM `$this->table_name`
              WHERE `Catalogue_ID` = $this->catalogue_id
                AND `Code` IN (" . join(', ', $all_codes) . ")"
        );

        if ($existing_codes) {
            $this->unavailable_codes = array_merge($this->unavailable_codes, $existing_codes);
            $codes = array_values(array_diff($codes, $existing_codes));
        }
        $this->generated_codes = array_merge($this->generated_codes, $codes);
    }

    /**
     * @param $code
     * @return bool
     */
    public function check_single_code($code) {
        $code_length = nc_strlen($code);
        if ($code_length == 0) { return false; }

        $exists = (bool)$this->db->get_var("SELECT 1 as 'result'
                                              FROM `$this->table_name`
                                             WHERE `Catalogue_ID` = $this->catalogue_id
                                               AND `Code` = '" . $this->db->escape($code) . "'");
        return !$exists && $code_length <= self::MAX_CODE_LENGTH;
    }

    /**
     * @param $code
     * @return nc_netshop_promotion_coupon
     */
    protected function create_coupon($code) {
        $coupon = new nc_netshop_promotion_coupon(array(
            "catalogue_id" => $this->catalogue_id,
            "code" => $code,
            "deal_type" => $this->options['deal_type'],
            "deal_id" => $this->options['deal_id'],
            "max_usages" => $this->options['max_usages'],
            "usage_count" => 0,
            "valid_till" => $this->options['valid_till'],
            "enabled" => true,
            "batch_id" => $this->batch_id
        ));

        return $coupon;
    }

    /**
     * @param $length
     * @return string
     */
    protected function get_random_string($length) {
        $str = "";
        $num_symbols = count($this->symbols) - 1;
        for ($i=0; $i < $length; $i++) {
            //$str .= $this->symbols[array_rand($this->symbols)];  ← poor randomness; just a bit (4-7%) slower
            $str .= $this->symbols[mt_rand(0, $num_symbols)];
        }
        return $str;
    }


    /**
     * @param $length
     * @return string
     */
    protected function get_next_sequence($length) {
        $max_value = count($this->symbols) - 1;
        if (!$this->last_sequence_indexes) {
            $this->last_sequence_indexes = array_fill(0, $length, 0);
        }
        else {
            $this->increment_sequence_position($this->last_sequence_indexes, $max_value);
        }

        $string = "";
        foreach ($this->last_sequence_indexes as $i) {
            $string .= $this->symbols[$i];
        }

        return $string;
    }

    /**
     * @param $array
     * @param $max
     */
    protected function increment_sequence_position(&$array, $max) {
        $i = sizeof($array) - 1;

        while (true) {
            if ($array[$i] < $max) { $array[$i]++; return; }
            if ($i == 0) { return; }
            $array[$i] = 0;
            $i--;
        }
    }

}