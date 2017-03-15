<?

class nc_payment_system_yandexcpp extends nc_payment_system {

    const ERROR_SHOPID_IS_NOT_VALID = NETCAT_MODULE_PAYMENT_YANDEX_CPP_ERROR_SHOPID_IS_NOT_VALID;
    const ERROR_SCID_IS_NOT_VALID = NETCAT_MODULE_PAYMENT_YANDEX_CPP_ERROR_SCID_IS_NOT_VALID;
    const ERROR_SHOP_PASSWORD_IS_NOT_VALID = NETCAT_MODULE_PAYMENT_YANDEX_CPP_ERROR_SHOP_PASSWORD_IS_NOT_VALID;
    const ERROR_AMOUNT = NETCAT_MODULE_PAYMENT_YANDEX_CPP_ERROR_AMOUNT;
    const ERROR_ORDER_ID_IS_NOT_VALID = NETCAT_MODULE_PAYMENT_YANDEX_CPP_ERROR_ORDER_ID_IS_NOT_VALID;
    const ERROR_PRIVATE_SECURITY_IS_NOT_VALID = NETCAT_MODULE_PAYMENT_YANDEX_CPP_ERROR_ORDER_ID_IS_NOT_VALID;

    const TARGET_URL = "https://money.yandex.ru/eshop.xml";

    protected $automatic = TRUE;

    // принимаемые валюты
    protected $accepted_currencies = array('RUB', 'RUR');

    protected $currency_map = array(
        'RUR' => 10643,
        'RUB' => 10643,
    );


    // параметры сайта в платежной системе
    protected $settings = array(
        'shopId' => null,
        'scid' => null,
        'shopPassword' => null,
        'shopFailURL' => null,
        'shopSuccessURL' => null,
        'paymentType' => null,
    );

    /**
     *
     */
    public function execute_payment_request(nc_payment_invoice $invoice) {
        $inputs = array(
            'shopId' => $this->get_setting('shopId'),
            'scid' => $this->get_setting('scid'),
            'Sum' => $invoice->get_amount("%0.2F"),
            'orderNumber' => $invoice->get_id(),
            'customerNumber' => $invoice->get('customer_id'),
            'cps_email' => $invoice->get('customer_email'),
            'cps_phone' => $invoice->get('customer_phone'),
            'cms_name' => 'netcat',
        );

        $unnecessary_settings = array('paymentType', 'shopFailURL', 'shopSuccessURL');

        foreach($unnecessary_settings as $setting) {
            $value = $this->get_setting($setting);
            if ($value) {
                $inputs[$setting] = $value;
            }
        }

        ob_end_clean();
        $form = "
            <html>
              <body>
                    <form action='" . nc_payment_system_yandexcpp::TARGET_URL . "' method='post'>" .
                    $this->make_inputs($inputs) . "
                </form>
                <script>
                  document.forms[0].submit();
                </script>
              </body>
            </html>
            ";
        echo $form;
        exit;
    }


    /**
     * @param nc_payment_invoice $invoice
     */
    public function on_response(nc_payment_invoice $invoice = null) {
        $action = $this->get_response_value('action');

        if ($action == 'checkOrder') {
            $shop_id = $this->get_setting('shopId');
            $invoice_id = $this->get_response_value('invoiceId');

            $invoice->set('status', nc_payment_invoice::STATUS_WAITING);
            $invoice->save();

            $this->print_callback_answer('checkOrderResponse', 0, $invoice_id, $shop_id);
        } else if ($action == 'paymentAviso') {
            $shop_id = $this->get_setting('shopId');
            $invoice_id = $this->get_response_value('invoiceId');

            $invoice->set('status', nc_payment_invoice::STATUS_SUCCESS);
            $invoice->save();

            $this->print_callback_answer('paymentAvisoResponse ', 0, $invoice_id, $shop_id);
            $this->on_payment_success($invoice);
        }
    }

    /**
     *
     */
    public function validate_payment_request_parameters() {
        if (!$this->get_setting('shopId')) {
            $this->add_error(nc_payment_system_yandexcpp::ERROR_SHOPID_IS_NOT_VALID);
        }

        if (!$this->get_setting('scid')) {
            $this->add_error(nc_payment_system_yandexcpp::ERROR_SCID_IS_NOT_VALID);
        }
    }

    /**
     * @param nc_payment_invoice $invoice
     */
    public function validate_payment_callback_response(nc_payment_invoice $invoice = null) {
        $md5 = $this->get_response_value('md5');

        $signature_values = array(
            $this->get_response_value('action'),
            $this->get_response_value('orderSumAmount'),
            $this->get_response_value('orderSumCurrencyPaycash'),
            $this->get_response_value('orderSumBankPaycash'),
            $this->get_response_value('shopId'),
            $this->get_response_value('invoiceId'),
            $this->get_response_value('customerNumber'),
            $this->get_setting('shopPassword')
        );

        $key = strtoupper(md5(join(";", $signature_values)));
        $error = false;
        $action = $this->get_response_value('action');
        $shop_id = $this->get_setting('shopId');
        $invoice_id = $this->get_response_value('invoiceId');

        if ($invoice) {
            if ($key != $md5 || $shop_id != $this->get_setting('shopId')) {
                $error = nc_payment_invoice::STATUS_CALLBACK_ERROR;
                $this->add_error(nc_payment_system_yandexcpp::ERROR_INVALID_SIGNATURE);
            } else if ($invoice->get_amount() != $this->get_response_value('orderSumAmount')) {
                $error = nc_payment_invoice::STATUS_CALLBACK_WRONG_SUM;
                $this->add_error(nc_payment_system_yandexcpp::ERROR_INVALID_SUM);
            }

            if ($error) {
                $invoice->set('status', $error);
                $invoice->save();
            }
        } else {
            $error = true;
            $this->add_error(nc_payment_system_yandexcpp::ERROR_INVOICE_NOT_FOUND);
        }

        if ($error) {
            $this->print_callback_answer($action . 'Response', 1, $invoice_id, $shop_id);
        }
    }

    /**
     * @param string $response_type
     * @param int $code
     * @param int $invoice_id
     * @param int $shop_id
     */
    public function print_callback_answer($response_type, $code, $invoice_id, $shop_id) {
        $datetime = date('c');

        $out = '<?xml version="1.0" encoding="UTF-8"?>
<' . $response_type . ' performedDatetime ="' . $datetime . '" code="' . $code . '" invoiceId="' . $invoice_id . '" shopId="' . $shop_id . '"/>';

        echo $out;
    }

    public function load_invoice_on_callback() {
        return $this->load_invoice($this->get_response_value('orderNumber'));
    }

}