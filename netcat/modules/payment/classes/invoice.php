<?php

class nc_payment_invoice extends nc_record {
    const STATUS_NEW = 1;
    const STATUS_SENT_TO_PAYMENT_SYSTEM = 2;
    const STATUS_CALLBACK_ERROR = 3;
    const STATUS_CALLBACK_WRONG_SUM = 4;
    const STATUS_WAITING = 5;
    const STATUS_SUCCESS = 6;

    protected $primary_key = "id";
    protected $properties = array(
        "id" => null,
        "payment_system_id" => 0,
        "amount" => 0,
        "description" => '',
        "currency" => 'RUB',
        "customer_id" => 0,
        "customer_email" => '',
        "customer_phone" => '',
        "customer_name" => '',
        "order_source" => '',
        "order_id" => 0,
        "status" => self::STATUS_NEW,
        "last_response" => '',
    );

    protected $table_name = "Payment_Invoice";
    protected $mapping = array(
        "id" => "Payment_Invoice_ID",
        "payment_system_id" => "Payment_System_ID",
        "amount" => "Amount",
        "description" => "Description",
        "currency" => "Currency",
        "customer_id" => "Customer_ID",
        "customer_email" => "Customer_Email",
        "customer_phone" => "Customer_Phone",
        "customer_name" => "Customer_Name",
        "order_source" => "Order_Source",
        "order_id" => "Order_ID",
        "status" => "Status",
        "last_response" => "Last_Response",
    );

    /**
     * Возврат суммы счёта с опциональным её форматированием
     * @param null|string $format    формат для sprintf, например "%0.2F"
     * @return number|string
     */
    public function get_amount($format = null) {
        $amount = $this->get('amount');
        return $format ? sprintf($format, $amount) : $amount;
    }

    /**
     * @return string
     */
    public function get_description() {
        return $this->get('description');
    }

    /**
     * @return string
     */
    public function get_currency() {
        return $this->get('currency');
    }

}