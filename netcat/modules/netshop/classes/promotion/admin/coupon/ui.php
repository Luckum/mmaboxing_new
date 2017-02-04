<?php

class nc_netshop_promotion_admin_coupon_ui extends nc_netshop_admin_ui {

    protected $_deal;

    /**
     *
     */
    public function __construct(nc_netshop_promotion_deal $deal) {
        $this->_deal = $deal;
        $deal_type = $deal->get_deal_type();
        $deal_name = $deal->get('name');

        $type_with_dots = str_replace("_", ".", $deal_type);

        $deal_link = "#module.netshop.promotion.$type_with_dots.edit(" . $deal->get_id() . ")";
        $sub_header = "NETCAT_MODULE_NETSHOP_PROMOTION_COUPONS_FOR_" . strtoupper($deal_type) . "_HEADER";
        $deal_name_and_link = "<a href='$deal_link'>$deal_name</a>";
        $sub_header = sprintf(constant($sub_header), $deal_name_and_link);

        $this->headerText = NETCAT_MODULE_NETSHOP;
        $this->subheaderText = $sub_header;

        $this->locationHash = "module.netshop.promotion.coupon";

        $this->treeMode = "modules";
        $this->treeSelectedNode = "netshop-promotion.$type_with_dots";
    }

    /**
     *
     */
    public function add_back_to_deals_button() {
        $deal_type = $this->_deal->get_deal_type();
        $catalogue_id = $this->_deal->get('catalogue_id');

        if (strpos($deal_type, "discount_") === 0) {
            $button_text = NETCAT_MODULE_NETSHOP_PROMOTION_BACK_TO_DISCOUNT_LIST;
        }
        else {
            $button_text = NETCAT_MODULE_NETSHOP_PROMOTION_BACK_TO_DEAL_LIST;
        }

        $this->actionButtons[] = array(
            "id" => "to_deals_list",
            "caption" => $button_text,
            "location" => "#module.netshop.promotion." . str_replace("_", ".", $deal_type) . "($catalogue_id)",
            "align" => "left"
        );
    }

    /**
     *
     */
    public function add_generate_coupons_button() {
        $deal_type = $this->_deal->get_deal_type();
        $deal_id = $this->_deal->get_id();

        $this->actionButtons[] = array(
            "id" => "generate",
            "caption" => NETCAT_MODULE_NETSHOP_PROMOTION_GENERATE_COUPONS_BUTTON,
            "location" => "#module.netshop.promotion.coupon.generate($deal_type,$deal_id)",
            "align" => "left"
        );
    }


}