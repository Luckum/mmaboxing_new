<?php

class nc_netshop_condition_user extends nc_netshop_condition {

    /**
     * Parameters:
     *    'op'
     *    'value'  -- user ID
     */

    protected $op;
    protected $value;


    public function evaluate(nc_netshop_condition_context $context, $current_item = null) {
        return $this->compare($context->get_user_id(), $this->op, $this->value);
    }

    /**
     * Короткое описание (только значение, для повторяющихся условий)
     * @param nc_netshop $netshop
     * @return string
     */
    public function get_short_description(nc_netshop $netshop) {
        $nc_core = nc_core::get_object();
        $user = $nc_core->user->get_by_id($this->value);
        if (!$user['User_ID']) {
            return "<em class='nc--status-error'>" . NETCAT_MODULE_NETSHOP_COND_NONEXISTENT_USER . "</em>";
        }
        $text = $user[$nc_core->AUTHORIZE_BY];
        return $this->add_operator_description(nc_ui_helper::get()->hash_link("#user.edit($this->value)", $text));
    }

}