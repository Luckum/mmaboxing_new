<?php

/**
 * Class nc_netshop_promotion_discount_delivery
 *
 * "discount on the delivery price"
 */
class nc_netshop_promotion_discount_delivery extends nc_netshop_promotion_discount {

    const TYPE_ABSOLUTE = 1;
    const TYPE_RELATIVE = 2;

    protected $primary_key = 'discount_id';

    protected $properties = array(
        'discount_id' => null,
        'catalogue_id' => null,
        'coupon_required' => false,
        'name' => null,
        'description' => null,
        'amount' => null,
        'amount_type' => self::TYPE_ABSOLUTE,
        'cumulative' => false,
        'condition' => null,
        'enabled' => true,
    );

    protected $table_name = 'Netshop_DeliveryDiscount';
    protected $mapping = array(
        "_generate" => true
    );


    /**
     * @param nc_netshop_condition_context $context
     * @param float $delivery_price
     * @param int|null $current_delivery_method_id
     * @return float|int
     */
    public function get_discount_sum(nc_netshop_condition_context $context, $delivery_price, $current_delivery_method_id = null) {
        $sum = 0;

        if ($this->evaluate_conditions($context, $current_delivery_method_id)) {
            $amount = $this->get('amount');
            if ($this->is_relative()) {
                $sum = $this->round($delivery_price * $amount / 100);
            }
            else {
                // discount should’t be larger than the price itself
                $sum = min($delivery_price, $amount);
            }
        }
        return $sum;
    }

}