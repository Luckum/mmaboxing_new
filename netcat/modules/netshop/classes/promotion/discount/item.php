<?php

/**
 * Class nc_netshop_promotion_discount_item
 *
 * "discount on an item"
 */
class nc_netshop_promotion_discount_item extends nc_netshop_promotion_discount {

    const TYPE_ABSOLUTE = 1;
    const TYPE_RELATIVE = 2;

    protected $primary_key = 'discount_id';

    protected $properties = array(
        'discount_id' => null,
        'catalogue_id' => null,
        'coupon_required' => false,
        'item_activation_required' => false,
        'name' => null,
        'description' => null,
        'amount' => null,
        'amount_type' => self::TYPE_ABSOLUTE,
        'cumulative' => false,
        'condition' => null,
        'enabled' => true,
    );

    protected $table_name = 'Netshop_ItemDiscount';
    protected $mapping = array(
        "_generate" => true
    );


    /**
     * @param nc_netshop_item $item
     * @param nc_netshop_condition_context $context
     * @param bool $check_conditions_only
     * @return bool
     */
    public function applies_to(nc_netshop_item $item, nc_netshop_condition_context $context, $check_conditions_only = false) {
        if (!$check_conditions_only) {
            // discounts with 'item_activation_required'
            $is_activated = !$this->get('item_activation_required') ||
                             $context->is_item_discount_activated($this->get_id(), $item);
            if (!$is_activated) { return false; }
        }

        return $this->evaluate_conditions($context, $item);
    }

    /**
     * @param nc_netshop_item $item
     * @param nc_netshop_condition_context $context
     * @param bool $check_conditions_only
     * @return int|float
     */
    public function get_discount_sum_for(nc_netshop_item $item, nc_netshop_condition_context $context, $check_conditions_only = false) {
        $sum = 0;
        if ($this->applies_to($item, $context, $check_conditions_only)) {
            $amount = $this->get('amount');
            if ($this->is_relative()) {
                $sum = $this->round($item["OriginalPrice"] * $amount / 100);
            }
            else {
                $sum = min($item["OriginalPrice"], $amount);
            }
        }
        return $sum;
    }

}