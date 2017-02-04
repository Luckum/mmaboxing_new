<?php

class nc_netshop_condition_item_sub extends nc_netshop_condition {

    /**
     * Parameters:
     *   op
     *   value   -- ID of the subdivision
     */

    protected $op;
    protected $value;

    /**
     * @param nc_netshop_condition_context $context
     * @param null $current_item
     * @return bool
     */
    public function evaluate(nc_netshop_condition_context $context, $current_item = null) {
        if (!$current_item instanceof nc_netshop_item) { return false; }
        return $this->compare($this->value, $this->op, $current_item['Subdivision_ID']);
    }

    /**
     * @param nc_netshop $netshop
     * @return string
     */
    public function get_full_description(nc_netshop $netshop) {
        return ($this->op == 'ne'
                    ? NETCAT_MODULE_NETSHOP_COND_ITEM_PARENTSUB_NE
                    : NETCAT_MODULE_NETSHOP_COND_ITEM_PARENTSUB) .
               ' ' . $this->get_short_description($netshop);
    }

    /**
     * Короткое описание (только значение, для повторяющихся условий)
     * @param nc_netshop $netshop
     * @return string
     */
    public function get_short_description(nc_netshop $netshop) {
        try {
            $subdivision = nc_core('subdivision')->get_by_id($this->value);
           return sprintf(
                     NETCAT_MODULE_NETSHOP_COND_QUOTED_VALUE,
                     "<a href='$subdivision[HiddenURL]' target='_blank'>$subdivision[Subdivision_Name]</a>"
                  );
        }
        catch (Exception $e) {
            return "<em class='nc--status-error'>" . NETCAT_MODULE_NETSHOP_COND_NONEXISTENT_SUB . "</em>";
        }
    }

}