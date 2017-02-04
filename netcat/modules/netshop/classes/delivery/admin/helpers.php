<?php

class nc_netshop_delivery_admin_helpers {

    static protected $classifier_table_name = "Classificator_ShopDeliveryService";

    /**
     * (Список служб доставки — для админки)
     * @param nc_netshop $netshop
     * @param $selected_delivery_service
     * @param $current_mapping
     * @return string
     */
    static public function get_delivery_type_select(nc_netshop $netshop, $selected_delivery_service, $current_mapping) {

        $nc_core = nc_Core::get_object();
        /** @var nc_db $db */
        $db = $nc_core->db;

        $delivery_types = array(
            0 => NETCAT_MODULE_NETSHOP_DELIVERY_TYPE_DONT_USE,
        );

        $delivery_services = $db->get_results(
            "SELECT `ShopDeliveryService_ID`, `ShopDeliveryService_Name`, `Value` AS 'class'
               FROM `" . self::$classifier_table_name . "`
              WHERE `Checked` = 1",
            ARRAY_N
        );

        $delivery_mapped_fields = array();

        foreach ($delivery_services as $delivery_service) {
            list($delivery_service_id, $delivery_service_name, $delivery_service_class) = $delivery_service;
            if (@class_exists($delivery_service_class) && is_subclass_of($delivery_service_class, "nc_netshop_delivery_service")) {
                /** @var $delivery_service nc_netshop_delivery_service */
                $delivery_service = new $delivery_service_class(array());

                $delivery_types[$delivery_service_id] = $delivery_service_name;
                $delivery_mapped_fields[$delivery_service_id] = $delivery_service->get_mapped_fields();
            }
        }

        $order_table = $netshop->get_setting('OrderComponentID');

        $sql = "SELECT `Field_ID`, `Field_Name`, `Description` FROM `Field` WHERE `Class_ID` = {$order_table} ORDER BY `Priority`";
        $order_fields = (array)$db->get_results($sql, ARRAY_A);

        $shop_fields = nc_netshop_admin_helpers::get_shop_fields();

        $html = nc_admin_select_simple(NETCAT_MODULE_NETSHOP_DELIVERY_METHOD_SERVICE . ': ', 'data[ShopDeliveryService_ID]', $delivery_types, $selected_delivery_service);
        $html .= "<input type='hidden' name='data[ShopDeliveryService_Mapping]' value='{$current_mapping}'/>";

        foreach ($delivery_mapped_fields as $type => $mapped_fields) {
            $html .= "<div class='delivery-type-mapping type-{$type}' style='display: none;'>
            <span>" . sprintf(NETCAT_MODULE_NETSHOP_DELIVERY_TYPE_FIELD_MAPPING, $delivery_types[$type]) . ":</span><blockquote>";
            foreach ($mapped_fields as $field => $name) {
                $html .= "<div>{$name}: <select name='delivery_type_{$type}_field_{$field}' style='width: 350px;'>";
                $html .= "<option value=''>-</option>";

                $html .= "<optgroup label='" . htmlspecialchars(NETCAT_MODULE_NETSHOP_DELIVERY_TYPE_FIELD_MAPPING_ORDER, ENT_QUOTES) . "'>";
                foreach ($order_fields as $netcat_field) {
                    $html .= "<option value='order_{$netcat_field['Field_ID']}'>" . ($netcat_field['Description'] ? $netcat_field['Description'] : $netcat_field['Field_Name']) . "</option>";
                }
                $html .= "</optgroup>";

                $html .= "<optgroup label='" . htmlspecialchars(NETCAT_MODULE_NETSHOP_DELIVERY_TYPE_FIELD_MAPPING_SHOP, ENT_QUOTES) . "'>";
                foreach ($shop_fields as $field_name => $field_options) {
                    $html .= "<option value='shop_{$field_name}'>" . htmlspecialchars($field_options['caption']) . "</option>";
                }
                $html .= "</optgroup>";

                $html .= "</select></div>";
            }
            $html .= "</blockquote></div>";
        }

        $nc = '$nc';
        $html .= <<<END_OF_FRAGMENT
        <script type='text/javascript'>
        (function() {
            var delivery_type_select = $nc('SELECT[name="data[ShopDeliveryService_ID]"]'),
                delivery_mapping_field = $nc('INPUT[name="data[ShopDeliveryService_Mapping]"]');

            // restore mapping
            var delivery_type = delivery_type_select.val();
            if (delivery_type) {
                var mapping = delivery_mapping_field.val(),
                    mapped_fields = null;

                try {
                    mapped_fields = JSON.parse(mapping);
                } catch (e) {
                }

                if (mapped_fields) {
                    for (var field in mapped_fields) {
                        $nc('SELECT[name=delivery_type_' + delivery_type + '_field_' + field + ']').val(mapped_fields[field]);
                    }
                }
            }


            var save_mapping = function() {
                var delivery_type = delivery_type_select.val(),
                    mapping = '';

                if (delivery_type) {
                    mapping = {};
                    var mapped_fields = $nc('SELECT[name^=delivery_type_' + delivery_type + '_field_]');
                    mapped_fields.each(function(){
                        var el = $nc(this),
                            match = /^delivery_type_(.+?)_field_(.+)/.exec(el.attr('name'));
                        if (match) {
                            mapping[match[2]] = el.val();
                        }
                        return true;
                    });

                    if (!$nc.isEmptyObject(mapping)) {
                        mapping = JSON.stringify(mapping);
                    }
                    else {
                        mapping = '';
                    }
                }

                delivery_mapping_field.val(mapping);

                return true;
            };

            // delivery_type_select.closest('form').on('submit', save_mapping);
            $nc('SELECT[name^=delivery_type_]').change(save_mapping);

            delivery_type_select.on('change', function(){
                var type = $nc(this).val();
                $nc('.delivery-type-mapping').hide().filter('.type-' + type).show();
                save_mapping();
                return true;
            }).change();

        })();
        </script>
END_OF_FRAGMENT;

        return $html;
    }

}