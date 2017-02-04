<?php

class nc_netshop_condition_user_group extends nc_netshop_condition {

    /**
     * Parameters:
     *    'op'
     *    'value'  -- user group ID
     */
    protected $op;
    protected $value;

    public function evaluate(nc_netshop_condition_context $context, $current_item = null) {
        $user_belongs_to_group = $context->user_belongs_to_group($this->value);
        if ($this->op == 'eq') { return $user_belongs_to_group; }
        if ($this->op == 'ne') { return !$user_belongs_to_group; }
        trigger_error("Cannot use operator '$this->op' for user group comparison");
        return false;
    }

    /**
     * @param nc_netshop $netshop
     * @return string
     */
    public function get_short_description(nc_netshop $netshop) {
        $group_id = (int)$this->value;
        static $cache = array();
        if (!isset($cache[$group_id])) {
            list($group_exists, $group_name) = nc_db()->get_row(
                "SELECT `PermissionGroup_ID`, `PermissionGroup_Name`
                   FROM `PermissionGroup`
                  WHERE `PermissionGroup_ID` = $group_id",
                ARRAY_N);

            if (!$group_exists) {
                $cache[$group_id] = "<em class='nc--status-error'>" . NETCAT_MODULE_NETSHOP_COND_NONEXISTENT_USER_GROUP . "</em>";
            }
            else {
                $link = nc_ui_helper::get()->hash_link("usergroup.edit($group_id)", $group_name);
                $cache[$group_id] = sprintf(NETCAT_MODULE_NETSHOP_COND_QUOTED_VALUE, $link);
            }
        }
        return $cache[$group_id];
    }


}