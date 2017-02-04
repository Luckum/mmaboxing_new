<?php

class nc_payment_system_mailru extends nc_payment_system {

    const ERROR_SIGNATURE_IS_NOT_VALID = NETCAT_MODULE_PAYMENT_MAIL_ERROR_SIGNATURE_IS_NOT_VALID;
    const ERROR_MAIL_SHOP_ID = NETCAT_MODULE_PAYMENT_MAIL_ERROR_SHOP_ID;
    const ERROR_MAIL_SECRET_KEY_IS_NULL = NETCAT_MODULE_PAYMENT_MAIL_ERROR_SECRET_KEY_IS_NULL;
    const ERROR_MAIL_HASH_IS_NULL = NETCAT_MODULE_PAYMENT_MAIL_ERROR_HASH_IS_NULL;

    const STATUS_PAID = 'PAID';
    const STATUS_REJECTED = 'REJECTED';

    const TARGET_URL = "https://money.mail.ru/pay/light/";

    protected $automatic = TRUE;

    // принимаемые валюты
    protected $accepted_currencies = array('RUB', 'RUR', 'USD', 'EUR');
    protected $currency_map = array('RUB' => 'RUR');

    // получаемые параметры
    protected $callback_response = array(
        'auth_method' => null,
        'issuer_id' => null,
        'item_number' => null,
        'serial' => null,
        'status' => null,
        'type' => null,
        'signature' => null,
    );

    // параметры сайта в платежной системе
    protected $settings = array(
        'MailShopID' => null,
        'MailSecretKey' => null,
        'MailHash' => null,
    );

    /**
     *
     */
    public function execute_payment_request(nc_payment_invoice $invoice) {
        ob_end_clean();
        $currency = $this->get_currency_code($invoice->get_currency());
        $signature = sha1(
            $currency .
            $invoice->get_description() .
            $invoice->get_id() .
            $this->get_setting('MailShopID') .
            $invoice->get_amount() .
            $this->get_setting('MailHash')
        );

        $form = "
            <html>
             <body>
             <form id='fmail' action='" . self::TARGET_URL . "' method='post'>" .
            $this->make_inputs(array(
                'shop_id' => $this->get_setting('MailShopID'),
                'currency' => $currency,
                'sum' => $invoice->get_amount(),
                'description' => $invoice->get_description(),
                'issuer_id' => $invoice->get_id(),
                'signature' => $signature,
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
        if (!is_numeric($this->get_setting('MailShopID'))) {
            $this->add_error(self::ERROR_MAIL_SHOP_ID);
        }

        if (!($this->get_setting('MailSecretKey'))) {
            $this->add_error(self::ERROR_MAIL_SECRET_KEY_IS_NULL);
        }

        if (!($this->get_setting('MailHash'))) {
            $this->add_error(self::ERROR_MAIL_HASH_IS_NULL);
        }
    }

    public function validate_payment_callback_response(nc_payment_invoice $invoice = null) {
        $calc_signature = sha1($this->get_response_value('auth_method') .
            $this->get_response_value('issuer_id') .
            $this->get_response_value('item_number') .
            $this->get_response_value('serial') .
            $this->get_response_value('status') .
            $this->get_response_value('type') .
            $this->get_setting('MailSecretKey'));

        if ($this->get_response_value('signature') !== $calc_signature) {
            $this->add_error(self::ERROR_SIGNATURE_IS_NOT_VALID);
        }
    }

    public function on_response(nc_payment_invoice $invoice = null) {
        if ($this->get_response_value('status') == self::STATUS_PAID) {
            $this->on_payment_success($invoice);
        } else {
            $this->on_payment_failure($invoice);
        }
    }

    public function load_invoice_on_callback() {
        return $this->load_invoice($this->get_response_value('issuer_id'));
    }
}
