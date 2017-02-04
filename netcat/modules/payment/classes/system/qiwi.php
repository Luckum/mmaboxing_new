<?php

include_once dirname(__FILE__) . '/qiwi/ishop_server_ws_service.php';

class nc_payment_system_qiwi extends nc_payment_system {

    const ERROR_AMOUNT_TOO_LARGE = NETCAT_MODULE_PAYMENT_QIWI_ERROR_AMOUNT_TOO_LARGE;
    const ERROR_QIWI_FORM = NETCAT_MODULE_PAYMENT_QIWI_ERROR_QIWI_FORM;

    const MAX_AMOUNT = 15000;

    /** Выставлен */
    const STATUS_SUBMITTED = 50;
    /** Оплачивается */
    const STATUS_IN_PROCESS = 52;
    /** Оплачен */
    const STATUS_COMPLETED = 60;
    const STATUS_CANCEL_TERMINAL_ERROR = 150;
    /** Отменен по разным причинам, недостаток средств итд.. */
    const STATUS_CANCEL_ERROR = 151;
    const STATUS_CANCEL = 160;
    const STATUS_CANCEL_TIMEOUT = 161;

    /** ResultCodes */
    const RESULT_SUCCESS = 0;
    const RESULT_SERVER_IS_BUSY = 13;
    const RESULT_AUTHENTICATION_ERROR = 150;
    const RESULT_INVOICE_NOT_FOUND = 210;
    const RESULT_UNKNOWN_ERROR = 300;

    const TARGET_URL = "http://w.qiwi.ru/setInetBill.do";
    const TARGET_URL_UTF = "http://w.qiwi.ru/setInetBill_utf.do";

    protected $automatic = TRUE;

    // принимаемые валюты
    protected $accepted_currencies = array('RUB', 'RUR');

    // параметры сайта в платежной системе
    protected $settings = array(
        'From' => null,
        'Password' => null,
        // 'Login' => null, // "будем брать значение From"
    );

    // передаваемые параметры
    protected $request_parameters = array(
        'To' => null,
    );

    /**
     *
     */
    public function execute_payment_request(nc_payment_invoice $invoice) {
        $target_url = nc_core('NC_UNICODE') ? self::TARGET_URL_UTF : self::TARGET_URL;

        ob_end_clean();
        $form = "
            <html>
                <body>
                <form id='fqiwi' action='" . $target_url . "' method='post'>" .
                $this->make_inputs(array(
                    'txn_id' => $invoice->get_id(),
                    'from' => $this->get_setting('From'),
                    'to' => $this->get_request_parameter('To'),
                    'summ' => $invoice->get_amount(),
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

    /**
     * @param nc_payment_invoice $invoice
     */
    public function on_response(nc_payment_invoice $invoice = null) {
        /*
         * SoapServer парсит входящий SOAP-запрос, извлекает значения тегов login,
         * password, txn, status, помещает их в объект класса Param и вызывает
         * функцию updateBill объекта класса nc_payment_system_qiwi.
         * Классы Response и Param находятся в qiwi/ishop_server_ws_service.php
         */
        $server = new SoapServer(
            dirname(__FILE__) . '/qiwi/IShopClientWS.wsdl',
            array('classmap' => array(
                'tns:updateBill' => 'Param',
                'tns:updateBillResponse' => 'Response'
            ))
        );
        $server->setObject($this);
        $server->handle();
    }

    /**
     * SoapServer callback
     * (Do not rename.)
     */
    public function updateBill($param) {
        $invoice = $this->load_invoice($param->txn);

        if ($param->status == self::STATUS_COMPLETED) {
            $this->on_payment_success($invoice);
        }
        else {
            $this->on_payment_failure($invoice);
        }

        $response = new Response();
        $response->updateBillResult = self::RESULT_SUCCESS;

        return $response;
    }

    /**
     *
     */
    public function validate_payment_request_parameters() {
        if (!is_numeric($this->get_setting('From'))) {
            $this->add_error(self::ERROR_QIWI_FORM);
        }

    }

    protected function validate_invoice(nc_payment_invoice $invoice) {
        parent::validate_invoice($invoice);
        if ($invoice->get_amount() > self::MAX_AMOUNT) {
            $this->add_error(self::ERROR_AMOUNT_TOO_LARGE);
        }
    }


    /**
     * @param nc_payment_invoice $invoice
     */
    public function validate_payment_callback_response(nc_payment_invoice $invoice = null) {

    }

    /**
     *
     */
    public function get_request_form(nc_payment_invoice $invoice) {
        $result = "<form action='" . $this->get_request_script_path() . "' method='post' target='_blank' id='nc_form_pay_request_qiwi'>";
        $result .= $this->make_input('invoice_id', $invoice->get_id());
        $result .= $this->make_input('payment_system', get_class($this));
        $result .= "Ваш QIWI кошелек: <input type='text' name='param_To' value='' style='z-index: 10;'><br>";
        $result .= "<input type='submit' value='".NETCAT_MODULE_PAYMENT_FORM_PAY."'>";
        $result .= "</form>";

        return $result;
    }

    public function load_invoice_on_callback() {
        //return $this->load_invoice($this->callback_invoice_id);
        return true;
    }
}
