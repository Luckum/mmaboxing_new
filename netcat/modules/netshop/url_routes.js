urlDispatcher.addRoutes( {
    'module.netshop.setup': NETCAT_PATH + 'modules/netshop/setup.php',
    'module.netshop.forms': NETCAT_PATH + 'modules/netshop/forms.php?phase=index',

    'module.netshop.1c.sources': NETCAT_PATH + 'modules/netshop/sources.php',

    'module.netshop.1c.import': NETCAT_PATH + 'modules/netshop/import.php',

    'module.netshop.order': NETCAT_PATH + 'modules/netshop/admin/?controller=order&action=index&site_id=%1',

    'module.netshop.statistics': NETCAT_PATH + 'modules/netshop/admin/?controller=statistics&action=index&site_id=%1',
    'module.netshop.statistics.goods': NETCAT_PATH + 'modules/netshop/admin/?controller=statistics&action=goods&site_id=%1',
    'module.netshop.statistics.customers': NETCAT_PATH + 'modules/netshop/admin/?controller=statistics&action=customers&site_id=%1',
    'module.netshop.statistics.coupons': NETCAT_PATH + 'modules/netshop/admin/?controller=statistics&action=coupons&site_id=%1',

    'module.netshop.mailer.template': NETCAT_PATH + 'modules/netshop/admin/?controller=mailer_template&action=master_template_index&site_id=%1',
    'module.netshop.mailer.template.add': NETCAT_PATH + 'modules/netshop/admin/?controller=mailer_template&action=master_template_add&site_id=%1',
    'module.netshop.mailer.template.edit': NETCAT_PATH + 'modules/netshop/admin/?controller=mailer_template&action=master_template_edit&id=%1',
    'module.netshop.mailer.customer_mail': NETCAT_PATH + 'modules/netshop/admin/?controller=mailer_template&action=message_template_edit&site_id=%1&recipient_role=customer&order_status=%2',
    'module.netshop.mailer.manager_mail': NETCAT_PATH + 'modules/netshop/admin/?controller=mailer_template&action=message_template_edit&site_id=%1&recipient_role=manager&order_status=%2',
    'module.netshop.mailer.rule': NETCAT_PATH + 'modules/netshop/admin/?controller=mailer_rule&action=index&site_id=%1',
    'module.netshop.mailer.rule.add': NETCAT_PATH + 'modules/netshop/admin/?controller=mailer_rule&action=add&site_id=%1',
    'module.netshop.mailer.rule.edit': NETCAT_PATH + 'modules/netshop/admin/?controller=mailer_rule&action=edit&id=%1',

    'module.netshop.promotion.discount.item': NETCAT_PATH + 'modules/netshop/admin/?controller=promotion_discount&discount_type=item&action=index&site_id=%1',
    'module.netshop.promotion.discount.item.add': NETCAT_PATH + 'modules/netshop/admin/?controller=promotion_discount&discount_type=item&action=edit&site_id=%1',
    'module.netshop.promotion.discount.item.edit': NETCAT_PATH + 'modules/netshop/admin/?controller=promotion_discount&discount_type=item&action=edit&discount_id=%1',

    'module.netshop.promotion.discount.delivery': NETCAT_PATH + 'modules/netshop/admin/?controller=promotion_discount&discount_type=delivery&action=index&site_id=%1',
    'module.netshop.promotion.discount.delivery.add': NETCAT_PATH + 'modules/netshop/admin/?controller=promotion_discount&discount_type=delivery&action=edit&site_id=%1',
    'module.netshop.promotion.discount.delivery.edit': NETCAT_PATH + 'modules/netshop/admin/?controller=promotion_discount&discount_type=delivery&action=edit&discount_id=%1',

    'module.netshop.promotion.coupon': NETCAT_PATH + 'modules/netshop/admin/promotion/coupon.php?deal_type=%1&deal_id=%2',
    'module.netshop.promotion.coupon.generate': NETCAT_PATH + 'modules/netshop/admin/promotion/coupon.php?action=generate_ask&deal_type=%1&deal_id=%2',
    'module.netshop.promotion.coupon.edit': NETCAT_PATH + 'modules/netshop/admin/promotion/coupon.php?action=edit&coupon_code=%1',

    'module.netshop.settings': NETCAT_PATH + 'modules/netshop/admin/?controller=settings&action=index&site_id=%1',
    'module.netshop.settings.module': NETCAT_PATH + 'modules/netshop/admin/?controller=settings&action=module&site_id=%1',

    'module.netshop.payment': NETCAT_PATH + 'modules/netshop/admin/?controller=payment&action=index&site_id=%1',
    'module.netshop.payment.add': NETCAT_PATH + 'modules/netshop/admin/?controller=payment&action=add&site_id=%1',
    'module.netshop.payment.edit': NETCAT_PATH + 'modules/netshop/admin/?controller=payment&action=edit&id=%1',

    'module.netshop.delivery': NETCAT_PATH + 'modules/netshop/admin/?controller=delivery&action=index&site_id=%1',
    'module.netshop.delivery.add': NETCAT_PATH + 'modules/netshop/admin/?controller=delivery&action=add&site_id=%1',
    'module.netshop.delivery.edit': NETCAT_PATH + 'modules/netshop/admin/?controller=delivery&action=edit&id=%1',

    'module.netshop.currency': NETCAT_PATH + 'modules/netshop/admin/?controller=currency&action=index&site_id=%1',
    'module.netshop.currency.add': NETCAT_PATH + 'modules/netshop/admin/?controller=currency&action=add&site_id=%1',
    'module.netshop.currency.edit': NETCAT_PATH + 'modules/netshop/admin/?controller=currency&action=edit&id=%1',
    'module.netshop.currency.settings': NETCAT_PATH + 'modules/netshop/admin/?controller=currency&action=settings&id=%1',

    'module.netshop.currency.officialrate': NETCAT_PATH + 'modules/netshop/admin/?controller=officialrate&action=index&site_id=%1',
    'module.netshop.currency.officialrate.edit': NETCAT_PATH + 'modules/netshop/admin?controller=officialrate&action=edit&id=%1',

    'module.netshop.pricerule': NETCAT_PATH + 'modules/netshop/admin/?controller=pricerule&action=index&site_id=%1',
    'module.netshop.pricerule.add': NETCAT_PATH + 'modules/netshop/admin/?controller=pricerule&action=add&site_id=%1',
    'module.netshop.pricerule.edit': NETCAT_PATH + 'modules/netshop/admin/?controller=pricerule&action=edit&id=%1',
    
    'module.netshop.yandex': NETCAT_PATH + 'modules/netshop/admin/?controller=yandex&action=index&site_id=%1',
    'module.netshop.yandex.bundle.add': NETCAT_PATH + 'modules/netshop/admin/?controller=yandex&action=edit&site_id=%1',
    'module.netshop.yandex.bundle.edit': NETCAT_PATH + 'modules/netshop/admin/?controller=yandex&action=edit&bundle_id=%1',
    'module.netshop.yandex.bundle.edit_fields': NETCAT_PATH + 'modules/netshop/admin/?controller=yandex&action=edit_fields&bundle_id=%1',

    1: '' // dummy entry
} );