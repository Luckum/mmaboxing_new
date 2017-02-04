<?php

class nc_payment_system_paypal extends nc_payment_system {

    const ERROR_SOME_PARAMETERS_ARE_NOT_VALID = NETCAT_MODULE_PAYMENT_PAYPAL_ERROR_SOME_PARAMETRS_ARE_NOT_VALID;
    const ERROR_PAYPAL_MAIL_IS_NOT_VALID = NETCAT_MODULE_PAYMENT_PAYPAL_ERROR_PAYPAL_MAIL_IS_NOT_VALID;

    const TARGET_URL = "https://www.paypal.com/cgi-bin/webscr";

    protected $automatic = TRUE;

    // принимаемые валюты
    protected $accepted_currencies = array('USD', 'EUR');

    // передаваемые параметры
    protected $request_settings = array();

    // параметры сайта в платежной системе
    protected $settings = array(
        'PaypalBizMail' => null,
        'PaymentSuccessPage' => null,
        'PaymentFailedPage' => null,
    );

    /**
     *
     */
    public function execute_payment_request(nc_payment_invoice $invoice) {
        ob_end_clean();
        // TODO пересчет $this->get_amount() в USD ?
        $form = "
            <html>
             <body>
             <form id='fpaypal' action='" . self::TARGET_URL . "' method='post'>" .
            $this->make_inputs(array(
                'charset' => nc_core('NC_CHARSET'),
                'cmd' => '_xclick',
                'business' => $this->get_setting('PaypalBizMail'),
                'item_name' => $invoice->get_description(),
                'item_number' => $invoice->get_id(),
                'amount' => $invoice->get_amount(),
                'currency_code' => $this->get_currency_code($invoice->get_currency()),
                'no_shipping' => 1,
                'rm' => 2,
                'return' => $this->get_setting('PaymentSuccessPage'),
                'cancel_return' => $this->get_setting('PaymentFailedPage'),
                'notify_url' => $this->get_callback_script_url()
            )) . "
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

    public function validate_payment_request_parameters() {
        if (!filter_var($this->get_setting('PaypalBizMail'), FILTER_VALIDATE_EMAIL)) {
            $this->add_error(self::ERROR_PAYPAL_MAIL_IS_NOT_VALID);
        }
    }

    public function on_response(nc_payment_invoice $invoice = null) {
        if ($this->get_response_value('payment_status') == 'Completed') {
            $this->on_payment_success($invoice);
        } else {
            $this->on_payment_failure($invoice);
        }
    }

    public function validate_payment_callback_response(nc_payment_invoice $invoice = null) {
        /** @var nc_input $input */
        $input = nc_core('input');
        $ipn_post_data = (array)$input->fetch_post();

        $post_fields = array_merge($ipn_post_data, array('cmd' => '_notify-validate'));
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => self::TARGET_URL,
            CURLOPT_POST => TRUE,
            CURLOPT_POSTFIELDS => http_build_query($post_fields),
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HEADER => FALSE,
            CURLOPT_SSL_VERIFYHOST => TRUE,
            CURLOPT_SSL_VERIFYPEER => FALSE,
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        $is_response_invalid = (
            $response !== "VERIFIED" ||
            $input->fetch_post('receiver_email') !== htmlspecialchars($this->get_setting('PaypalBizMail')) ||
            $input->fetch_post('txn_type') !== "web_accept"
        );

        if ($is_response_invalid) {
            $this->add_error(self::ERROR_SOME_PARAMETERS_ARE_NOT_VALID);
        }
    }

    public function load_invoice_on_callback() {
        return $this->load_invoice($this->get_response_value('item_number'));
    }
}
