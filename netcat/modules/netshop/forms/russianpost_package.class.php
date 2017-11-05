<?php


class russianpost_package_netshop_form extends nc_netshop_form {

    //--------------------------------------------------------------------------

    public $name = NETCAT_MODULE_NETSHOP_RUSSIANPOST_PACKAGE;
    public $keyword = 'russianpost_package';

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

        $valuation = round($data['valuation'], 2);
        $data['valuation'] = $valuation;
        if ($data['valuation']) {
            $int = (int)$data['valuation'];

            $decimals = round($data['valuation'] - $int, 2) * 100;
            $decimals = sprintf('%02d', $decimals);

            $data['valuation_text'] = $int . '(' .
                nc_netshop_amount_in_full($data['valuation'], false, false) .
                ') руб. ' . $decimals . ' коп.';
            if (!$nc_core->NC_UNICODE) {
                $data['valuation_text'] = $nc_core->utf8->utf2win($data['valuation_text']);
            }
        } else {
            $data['valuation_text'] = '';
        }

        $cash_on_delivery = round($data['cash_on_delivery'], 2);
        $data['cash_on_delivery'] = $cash_on_delivery;
        if ($data['cash_on_delivery']) {
            $int = (int)$data['cash_on_delivery'];

            $decimals = round($data['cash_on_delivery'] - $int, 2) * 100;
            $decimals = sprintf('%02d', $decimals);

            $data['cash_on_delivery_text'] = $int . ' (' .
                nc_netshop_amount_in_full($data['cash_on_delivery'], false, false) .
                ') руб. ' . $decimals . ' коп.';

            if (!$nc_core->NC_UNICODE) {
                $data['cash_on_delivery_text'] = $nc_core->utf8->utf2win($data['cash_on_delivery_text']);
            }
        } else {
            $data['cash_on_delivery_text'] = '';
        }

        $data['weight'] = round($data['weight'], 3);
        if ($data['weight']) {
            $int = (int)$data['weight'];

            $decimals = round($data['weight'] - $int, 3) * 1000;

            $data['weight'] = $int . ' кг ' . $decimals . ' гр';
            if (!$nc_core->NC_UNICODE) {
                $data['weight'] = $nc_core->utf8->utf2win($data['weight']);
            }
        }

        return parent::template($data);
    }

    //--------------------------------------------------------------------------
}