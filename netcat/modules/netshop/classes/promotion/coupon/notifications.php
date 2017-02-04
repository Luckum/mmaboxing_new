<?php

class nc_netshop_promotion_coupon_notifications {

    protected $messages = array();
    protected $status = "ok";

    /**
     * @return string  'ok', 'error'
     */
    public function get_status() {
        return $this->status;
    }

    /**
     * @param $type
     * @param $message
     * @param $coupon_code
     */
    public function add($type, $message, $coupon_code) {
        $this->messages[] = array(
            'type' => $type, // 'error', 'notice', 'info'
            'message' => @sprintf($message, $coupon_code),
            'coupon_code' => $coupon_code
        );
        if ($type == 'error') { $this->status = 'error'; }
    }

    /**
     * @return array
     */
    public function get_all() {
        return $this->messages;
    }

    /**
     * @return string
     */
    public function output() {
        if (!$this->messages) { return ""; }
        $result = "<div class='tpl-block-message tpl-block-netshop-coupon-messages tpl-status-{$this->status}'>";
        foreach ($this->messages as $message) {
            $result .= "<div class='tpl-block-netshop-coupon-message tpl-status-$message[type]'>$message[message]</div>";
        }
        $result .= "</div>";
        return $result;
    }

    /**
     * @return string
     */
    public function __toString() {
       return $this->output();
    }

}