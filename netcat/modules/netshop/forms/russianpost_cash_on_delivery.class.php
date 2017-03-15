<?php


class russianpost_cash_on_delivery_netshop_form extends nc_netshop_form {

    //--------------------------------------------------------------------------

    public $name = NETCAT_MODULE_NETSHOP_RUSSIANPOST_CASH_ON_DELIVERY;
    public $keyword = 'russianpost_cash_on_delivery';

    //--------------------------------------------------------------------------

    public function template($data) {
        $nc_core = nc_Core::get_object();

        $from_fullname = '';
        if ($data['from_legal_entity']) {
            $from_fullname = $data['from_legal_entity'];
        }

        if ($data['from_fullname']) {
            if ($from_fullname) {
                $from_fullname .= ', ';
            }
            $from_fullname .= $data['from_fullname'];
        }
        $data['from_fullname'] = $from_fullname;

        $to_fullname = '';
        if ($data['to_legal_entity']) {
            $to_fullname = $data['to_legal_entity'];
        }

        if ($data['to_fullname']) {
            if ($to_fullname) {
                $to_fullname .= ', ';
            }
            $to_fullname .= $data['to_fullname'];
        }
        $data['to_fullname'] = $to_fullname;

        $from_address_line1 = $data['from_street'];
        if ($data['from_house']) {
            if ($data['from_block']) {
                $from_address_line1 .= ', д. ' . $data['from_house'];
                $from_address_line1 .= ', стр. ' . $data['from_block'];
            } else {
                $from_address_line1 .= ', д. ' . $data['from_house'];
            }

            if ($data['from_apartment']) {
                $from_address_line1 .= ', кв. ' . $data['from_apartment'];
            }
        }
        if (!$nc_core->NC_UNICODE) {
            $from_address_line1 = $nc_core->utf8->utf2win($from_address_line1);
        }
        $data['from_address_line1'] = $from_address_line1;

        $from_address_line2 = $data['from_zipcode'];

        if ($data['from_city']) {
            if ($from_address_line2) {
                $from_address_line2 .= ', ';
            }
            $from_address_line2 .= $data['from_city'];
        }

        if ($data['from_country']) {
            if ($from_address_line2) {
                $from_address_line2 .= ', ';
            }
            $from_address_line2 .= $data['from_country'];
        }
        $data['from_address_line2'] = $from_address_line2;

        $to_address_line1 = $data['to_street'];
        if ($data['to_house']) {
            if ($data['to_block']) {
                $to_address_line1 .= ', д. ' . $data['to_house'];
                $to_address_line1 .= ', стр. ' . $data['to_block'];
            } else {
                $to_address_line1 .= ', д. ' . $data['to_house'];
            }

            if ($data['to_apartment']) {
                $to_address_line1 .= ', кв. ' . $data['to_apartment'];
            }
        }
        if (!$nc_core->NC_UNICODE) {
            $to_address_line1 = $nc_core->utf8->utf2win($to_address_line1);
        }
        $data['to_address_line1'] = $to_address_line1;

        $to_address_line2 = $data['to_zipcode'];

        if ($data['to_city']) {
            if ($to_address_line2) {
                $to_address_line2 .= ', ';
            }
            $to_address_line2 .= $data['to_city'];
        }

        if ($data['to_country']) {
            if ($to_address_line2) {
                $to_address_line2 .= ', ';
            }
            $to_address_line2 .= $data['to_country'];
        }
        $data['to_address_line2'] = $to_address_line2;

        $cash_on_delivery = round($data['cash_on_delivery'], 2);
        $data['cash_on_delivery'] = $cash_on_delivery;
        if ($data['cash_on_delivery']) {
            $int = (int)$data['cash_on_delivery'];

            $decimals = round($data['cash_on_delivery'] - $int, 2) * 100;
            $decimals = sprintf('%02d', $decimals);

            $data['cash_on_delivery_int'] = $int;
            $data['cash_on_delivery_dec'] = $decimals;
            $data['cash_on_delivery_text'] = $int . ' (' .
                nc_netshop_amount_in_full($data['cash_on_delivery'], false, false) .
                ') руб. ' . $decimals . ' коп.';
            if (!$nc_core->NC_UNICODE) {
                $data['cash_on_delivery_text'] = $nc_core->utf8->utf2win($data['cash_on_delivery_text']);
            }
        } else {
            $data['cash_on_delivery_int'] = '';
            $data['cash_on_delivery_dec'] = '';
            $data['cash_on_delivery_text'] = '';
        }

        return parent::template($data);
    }

    //--------------------------------------------------------------------------
}