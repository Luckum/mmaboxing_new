<?php

/* $Id: nc_minishop_order.class.php 4760 2011-06-03 14:22:33Z denis $ */

class nc_minishop_order {

    protected $order_id;
    //  стоимость заказа ( без скидки и со скидкой )
    protected $cost, $final_cost;
    // номер компонента "Заказы"
    protected $class_id;
    protected static $info = array();

    public function __construct($order_id) {
        $this->order_id = intval($order_id);
        $this->class_id = nc_minishop::get_object()->order_class_id();
    }

    public function put_goods($goods, $update_final_cost = 1) {
        $db = nc_Core::get_object()->db;
        $id = intval($this->order_id);

        $db->query("DELETE FROM `Minishop_OrderGoods` WHERE `Order_ID` = '".$id."' ");
        if (!empty($goods)) {
            foreach ($goods as $v) {
                $SQL = "INSERT INTO `Minishop_OrderGoods`
                            SET `Order_ID` = '".$id."',
                                `Name` = '".$db->escape($v['name'])."',
                                `Price` = '".str_replace(',', '.', +$v['price'])."',
                                `URL` = '".$db->escape($v['uri'])."',
                                `Quantity` = '".+$v['quantity']."' ";
                $db->query($SQL);
            }
        }

        if ($update_final_cost) {
            $this->cost = $db->get_var("SELECT SUM(`Price` * `Quantity`) FROM `Minishop_OrderGoods` WHERE `Order_ID` = '".intval($this->order_id)."' ");
            self::$info[$this->order_id]['Cost'] = $this->cost;
            $SQL = "
                UPDATE `Message".$this->class_id."`
                    SET `FinalCost` = '".$this->cost."',
                        `Cost` = '".$this->cost."'
                        WHERE `Message_ID` = '".+$this->order_id."'";
            $db->query($SQL);
        }
    }

    /**
     * @todo Загружать все позици сразу за один запрос!
     */
    public function content() {
        $db = nc_Core::get_object()->db;
        static $data;
        static $load;
        return (array) $db->get_results("SELECT `Name` as `name`, `URL` as `uri`,  `Price` as `price`, `Quantity` as `quantity` FROM `Minishop_OrderGoods` WHERE `Order_ID` = '".intval($this->order_id)."' ", ARRAY_A);
    }

    public function apply_discount($discount) {
        $discount = doubleval($discount);
        $this->final_cost = doubleval($this->cost - ($this->cost * $discount) / 100);
        if ($this->final_cost < 0) $this->final_cost = 0;

        nc_Core::get_object()->db->query("UPDATE `Message".$this->class_id."` SET `FinalCost` = '".str_replace(',', '.', $this->final_cost)."', `Discount` = '".$discount."' WHERE `Message_ID` = '".intval($this->order_id)."' ");
    }

    public function load() {
        if (!self::$info[$this->order_id]['Message_ID']) {
            self::$info[$this->order_id] = nc_Core::get_object()->db->get_row("SELECT * FROM `Message".$this->class_id."` WHERE `Message_ID` = '".intval($this->order_id)."' ", ARRAY_A);
        }
    }

    public function get($item) {
        $this->load();
        return self::$info[$this->order_id][$item];
    }

}