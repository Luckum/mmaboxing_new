<?php

class nc_netshop_promotion_coupon extends nc_record {

    protected $primary_key = "coupon_id";

    protected $properties = array(
        "coupon_id" => null,
        "catalogue_id" => 0,
        "code" => null,
        "deal_type" => null,
        "deal_id" => null,
        "max_usages" => 0,
        "usage_count" => 0,
        "valid_till" => null,
        "enabled" => true,
        "batch_id" => 0,
        "sent_to_user_id" => null,
    );

    protected $table_name = "Netshop_Coupon";
    protected $mapping = array(
        "coupon_id" => "Coupon_ID",
        "catalogue_id" => "Catalogue_ID",
        "code" => "Code",
        "deal_type" => "Deal_Type",
        "deal_id" => "Deal_ID",
        "max_usages" => "MaxUsages",
        "usage_count" => "UsageCount",
        "valid_till" => "ValidTill",
        "enabled" => "Enabled",
        "batch_id" => "Batch_ID",
        "sent_to_user_id" => "SentTo_User_ID",
    );

    protected $deal_loaded = false;
    protected $deal;

    /**
     * @return bool
     */
    public function is_expired() {
        $valid_till = $this->get('valid_till');
        if ($valid_till) {
            return (strtotime($valid_till) < time());
        }
        else {
            return false;
        }
    }

    public function is_used_up() {
        $max_usages = ($this->get('max_usages'));
        if (!$max_usages) { return false; }
        return $this->get('usage_count') >= $max_usages;
    }

    /**
     * @return nc_netshop_promotion_deal|null
     */
    public function get_deal() {
        if (!$this->deal_loaded) {
            $this->deal = nc_netshop_promotion_deal::by_id(
                $this->get('deal_type'),
                $this->get('deal_id')
            );
            $this->deal_loaded = true;
        }
        return $this->deal;
    }

    /**
     *
     */
    public function register_usage() {
        $this->properties['usage_count']++;

        $query = "UPDATE `{$this->table_name}`
                     SET `UsageCount` = `UsageCount` + 1,
                         `Created` = `Created`
                   WHERE `Coupon_ID` = " . $this->escape_value($this->get_id());

        nc_db()->query($query);
    }

    /**
     *
     */
    public function load_by_code($catalogue_id, $coupon_code) {
        return $this->select_from_database("SELECT *
                                              FROM `%t%`
                                             WHERE `Catalogue_ID` = " . (int)$catalogue_id . "
                                               AND `Code` = " . $this->escape_value($coupon_code));
    }
}