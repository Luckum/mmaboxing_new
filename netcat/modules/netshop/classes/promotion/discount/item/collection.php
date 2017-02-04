<?php

class nc_netshop_promotion_discount_item_collection extends nc_netshop_promotion_discount_collection {

    /** @var nc_netshop_promotion_discount_item[] */
    protected $items = array();

    /** @var string  */
    protected $items_class = 'nc_netshop_promotion_discount_item';

    /** @var  string   MUST BE DEFINED IN THE CHILD CLASS */
    protected $deal_type = 'discount_item';

    /** @var string */
    protected $discounts_table_name = "Netshop_ItemDiscount";

    /**
     * @param nc_netshop_item $item
     * @param bool $check_conditions_only
     * @return float
     */
    public function get_discount_sum(nc_netshop_item $item, $check_conditions_only = false) {
        $cumulative_sum = 0;
        $exclusive_sum = 0;

        foreach ($this->items as $discount) {
            $discount_value = $discount->get_discount_sum_for($item, $this->context, $check_conditions_only);
            if ($discount->get('cumulative')) {
                $cumulative_sum += $discount_value;
            }
            else {
                $exclusive_sum = max($exclusive_sum, $discount_value);
            }
        } // of "foreach item discount"

        // take 'PriceMinimum' into consideration (stored in base currency in '_MinimumPrice')
        $max_discount = $item['OriginalPrice'] - $item['_MinimumPrice'];
        $discount_sum = $cumulative_sum + $exclusive_sum;
        if ($discount_sum && $discount_sum > $max_discount) {
            $discount_sum = $max_discount;
        }

        return $discount_sum;
    }

    /**
     * Выбирает скидки, применимые к товару. Если есть несколько конкурирующих
     * некумулятивных скидок, выбирает одну с максимальной абсолютной суммой скидки.
     * @param nc_netshop_item $item
     * @param bool $check_conditions_only  не проверять дополнительные условия (в частности, активирована ли скидка)
     * @return nc_netshop_promotion_discount_item_collection
     */
    public function get_applicable_discounts(nc_netshop_item $item, $check_conditions_only = false) {
        $result = new self;
        $result->set_context($this->context);

        $max_exclusive_discount = null;
        $max_exclusive_discount_value = 0;
        foreach ($this->items as $discount) {
            if (!$discount->applies_to($item, $this->context, $check_conditions_only)) {
                continue; // next, please!
            }

            if ($discount->get('cumulative')) {
                $result->add($discount);
            }
            else { // exclusive discount
                $discount_value = $discount->get_discount_sum_for($item, $this->context, $check_conditions_only);
                if ($discount_value > $max_exclusive_discount_value) {
                    $max_exclusive_discount_value = $discount_value;
                    $max_exclusive_discount = $discount;
                }
            }
        } // of "foreach discount"

        if ($max_exclusive_discount) { $result->add($max_exclusive_discount); }

        return $result;
    }

}