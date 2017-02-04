<?php

class nc_netshop_promotion_discount_delivery_collection extends nc_netshop_promotion_discount_collection {

    /** @var nc_netshop_promotion_discount_delivery[] */
    protected $items = array();

    /** @var string  */
    protected $items_class = 'nc_netshop_promotion_discount_delivery';

    /** @var  string   MUST BE DEFINED IN THE CHILD CLASS */
    protected $deal_type = 'discount_delivery';

    /** @var string */
    protected $discounts_table_name = "Netshop_DeliveryDiscount";

    /**
     * Возвращает сумму скидки на доставку
     *
     * @param float|int $full_delivery_price
     * @param int|null $current_delivery_method_id
     * @return float|int
     */
    public function get_discount_sum($full_delivery_price, $current_delivery_method_id = null) {
        $cumulative_sum = 0;
        $exclusive_sum = 0;

        foreach ($this->items as $discount) {
            $discount_value = $discount->get_discount_sum($this->context, $full_delivery_price, $current_delivery_method_id);
            if ($discount->get('cumulative')) {
                $cumulative_sum += $discount_value;
            }
            else {
                $exclusive_sum = max($exclusive_sum, $discount_value);
            }
        } // of "foreach delivery discount"

        $discount_sum = $cumulative_sum + $exclusive_sum;

        if ($discount_sum >= $full_delivery_price) { return $full_delivery_price; }

        return $discount_sum;
    }

    /**
     * Выбирает скидки, применимые к доставке. Если есть несколько конкурирующих
     * некумулятивных скидок, выбирает одну с максимальной абсолютной суммой скидки.
     *
     * @param float|int $full_delivery_price
     * @return nc_netshop_promotion_discount_delivery_collection
     */
    public function get_applicable_discounts($full_delivery_price) {
        $result = new self;
        $result->set_context($this->context);

        $max_exclusive_discount = null;
        $max_exclusive_discount_value = 0;
        $cumulative_discounts_sum = 0;

        foreach ($this->items as $discount) {
            if (!$discount->evaluate_conditions($this->context)) {
                continue; // next, please!
            }

            $discount_value = $discount->get_discount_sum($this->context, $full_delivery_price);

            if ($discount->get('cumulative')) {
                $cumulative_discounts_sum += $discount_value;
                $result->add($discount);
            }
            else { // exclusive discount
                if ($discount_value > $max_exclusive_discount_value) {
                    $max_exclusive_discount_value = $discount_value;
                    $max_exclusive_discount = $discount;
                }
            }

            // stop when discounts are equal or more than the original price
            if ($max_exclusive_discount_value + $cumulative_discounts_sum >= $full_delivery_price) {
                break;
            }
        } // of "foreach discount"

        if ($max_exclusive_discount) { $result->add($max_exclusive_discount); }

        return $result;
    }

}