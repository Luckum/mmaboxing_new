<?php

class nc_payment_system_rbk extends nc_payment_system {

    const ERROR_SHOPID_IS_NOT_VALID = NETCAT_MODULE_PAYMENT_RBK_ERROR_ESHOPID_IS_NOT_VALID;
	const ERROR_AMOUNT = NETCAT_MODULE_PAYMENT_RBK_ERROR_AMOUNT;
	const ERROR_ORDER_ID_IS_NOT_VALID = NETCAT_MODULE_PAYMENT_RBK_ERROR_ORDER_ID_IS_NOT_VALID;

    //const TARGET_URL = "http://test-payment-sko-01.rbkmoney.ru/opencms/opencms/prepare.html";
    const TARGET_URL = "https://sko.rbkmoney.ru/opencms/opencms/default/index.html?invoiceId=";



    // параметры сайта в платежной системе
    protected $settings = array(
        'eshopId' => null,
        'failUrl' => null,
        'successUrl' => null
    );


    protected $accepted_currencies = array('RUB', 'RUR');

    /**
     *
     */
    public function validate_payment_request_parameters() {
        if (!$this->get_setting('eshopId')) {
            $this->add_error(nc_payment_system_rbk::ERROR_ESHOPID_IS_NOT_VALID);
        }
	    if (!$this->get_setting('recipientAmount')) {
		    $this->add_error(nc_payment_system_rbk::ERROR_AMOUNT);
	    }
	    if (!$this->get_setting('orderId')) {
		    $this->add_error(nc_payment_system_rbk::ERROR_ORDER_ID_IS_NOT_VALID);
	    }
    }


    /**
     * @param nc_payment_invoice $invoice
     */
    public function on_response(nc_payment_invoice $invoice = null) {
        $action = $this->get_response_value('action');

        /*
        @todo допилить проверку ответа
        */
    }


    /**
     * @param nc_payment_invoice $invoice
     */
    public function validate_payment_callback_response(nc_payment_invoice $invoice = null) {
        // предварительная проверка со стороны платежной системы перед получением средств

    }

    /**
     *
     */
    public function execute_payment_request(nc_payment_invoice $invoice) {

        $inputs = array(
            'eshopId' => $this->get_setting('eshopId'),
            'recipientAmount' => $invoice->get_amount("%0.2F"),
            'orderId' => $invoice->get_id(),
            'recipientCurrency' => $invoice->get_currency(),
            'user_email' => $invoice->get('customer_email'),
        );

        $shop_fail_url = $this->get_setting('failUrl');
        if ($shop_fail_url) {
            $inputs['failUrl'] = $shop_fail_url;
        }

        $shop_success_url = $this->get_setting('successUrl');
        if ($shop_success_url) {
            $inputs['successUrl'] = $shop_success_url;
        }

        ob_end_clean();
        $form = "
            <html>
              <body>
                    <form action='" . nc_payment_system_rbk::TARGET_URL .$invoice->get_id(). "&language=ru
' method='post'>" .
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

}

?>