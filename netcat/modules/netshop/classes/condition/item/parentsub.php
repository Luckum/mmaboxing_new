<?php

class nc_netshop_condition_item_parentsub extends nc_netshop_condition {

    /**
     * Parameters:
     *   op
     *   value   -- ID of the subdivision
     */

    protected $op;
    protected $value;

    /** @var array [ sub => parent ] */
    protected $parent_cache = array();

    /**
     * @param nc_netshop_condition_context $context
     * @param null $current_item
     * @return bool
     */
    public function evaluate(nc_netshop_condition_context $context, $current_item = null) {
        if (!$current_item instanceof nc_netshop_item) { return false; }

        $condition_sub_id = $this->value;
        $item_sub_id = $current_item['Subdivision_ID'];

        do {
            if ($this->compare($condition_sub_id, $this->op, $item_sub_id)) { return true; }
            $item_sub_id = nc_netshop_condition_util::get_parent_sub($item_sub_id);
        } while ($item_sub_id);

        return false;
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
           $subdivision_link = "<a href='$subdivision[HiddenURL]' target='_blank'>$subdivision[Subdivision_Name]</a>";
           return sprintf(NETCAT_MODULE_NETSHOP_COND_QUOTED_VALUE, $subdivision_link) . ' ' .
                  NETCAT_MODULE_NETSHOP_COND_ITEM_PARENTSUB_DESCENDANTS;
        }
        catch (Exception $e) {
            return "<em class='nc--status-error'>" . NETCAT_MODULE_NETSHOP_COND_NONEXISTENT_SUB . "</em>";
        }

    }

}