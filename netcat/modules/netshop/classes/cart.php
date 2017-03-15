<?php

/**
 * Class nc_netshop_cart
 */
class nc_netshop_cart {

    protected $order_table_name = "Netshop_OrderGoods";

    /** @var array [ component_id => [ message_id => [ Qty => qty, OrderParameters = array() ] ] ] */
    protected $raw_contents = array();

    /** @var nc_netshop_item_collection  */
    protected $items;

    /** @var int */
    protected $catalogue_id;

    /** @var  nc_netshop_cart_notifications    Предупреждения о невозможности заказа выбранного количества товаров */
    protected $quantity_notifications;

    /**
     *
     */
    public function __construct(nc_netshop $netshop) {
        $this->catalogue_id = (int)$netshop->get_catalogue_id();
        $this->raw_contents = $_SESSION[$this->get_session_key()];
    }

    /**
     *
     */
    public function __destruct() {
        $_SESSION[$this->get_session_key()] = $this->raw_contents;
    }

    /**
     * @return string
     */
    protected function get_session_key() {
        return "nc_netshop_" . ($this->catalogue_id) . "_cart";
    }

    /**
     * Проверка заказа перед оформлением
     * @param nc_netshop_order $order
     * @param nc_netshop_condition_context $context
     * @return array
     */
    public function check_new_order(nc_netshop_order $order, nc_netshop_condition_context $context) {
        $errors = array();
        if (!$this->get_item_count()) {
            $errors[] = NETCAT_MODULE_NETSHOP_ERROR_CART_EMPTY;
        }
        return $errors;
    }

    /**
     * Сохраняет содержимое корзины как содержимое заказа $order,
     * очищает корзину.
     * Не должно использоваться отдельно от nc_netshop::place_order().
     * @param nc_netshop_order $order
     * @return bool
     */
    public function checkout(nc_netshop_order $order) {
        $order_id = $order->get_id();
        if (!$this->raw_contents || !$order_id) { return false; }

        $db = nc_db();
        foreach ($this->get_items() as $item) {
            $item_type = (int)$item['Class_ID'];
            $item_id = (int)$item['Message_ID'];

            $params = $this->raw_contents[$item_type][$item_id]['OrderParameters'];
            if ($params) { $params = serialize($params); }
                    else { $params = ''; }

            $query = "INSERT INTO `$this->order_table_name`
                         SET `Order_ID` = $order_id,
                             `Item_Type` = $item_type,
                             `Item_ID` = $item_id,
                             `Qty` = '" . $db->escape($item['Qty']) . "',
                             `OriginalPrice` = '" . $db->escape($item['OriginalPrice']) . "',
                             `ItemPrice` = '" . $db->escape($item['ItemPrice']) . "',
                             `OrderParameters` = '" . $db->escape($params) . "'";

            $db->query($query);
        }

        $this->clear();

        return true;
    }

    /**
     *
     */
    protected function ignore_stock_units_value() {
        return nc_netshop::get_instance($this->catalogue_id)->get_setting('IgnoreStockUnitsValue');
    }

    /**
     * Добавляет товар в корзину.
     * Удаляет товар из корзины, если qty == 0.
     *
     * @param int $component_id  ID компонента товара
     * @param int $item_id  ID объекта (товара)
     * @param int $qty  Количество товара
     * @param bool $replace_existing   Если FALSE, то указанное количество и дополнительные
     *    параметры добавляются к уже имеющимся; если TRUE, то имеющееся количество
     *    и дополнительные параметры заменяются на указанные
     * @param array $additional_params   Дополнительные параметры, будут доступны в $item['OrderParameters']
     * @return bool
     */
    public function add_item($component_id, $item_id, $qty = 1, $replace_existing = true, array $additional_params = null) {
        $component_id = (int)$component_id;
        $item_id = (int)$item_id;
        if (!$component_id || !$item_id) { return false; }

        if (!is_numeric($qty)) { $qty = 0; }
        if ($qty == 0) { // remove item
            return $this->remove_item($component_id, $item_id); // ---- RETURN ----
        }

        if (!$replace_existing && isset($this->raw_contents[$component_id][$item_id])) {
            $row = &$this->raw_contents[$component_id][$item_id];
            $qty = $qty + $row['Qty'];
            if (isset($additional_params, $row['OrderParameters'])) {
                $additional_params = array_merge($row['OrderParameters'], $additional_params);
            }
        }

        $this->raw_contents[$component_id][$item_id] = array(
            'Qty' => $qty,
            'OrderParameters' => $additional_params,
        );

        // (a) item is already in the cart:
        $item = ($this->items ? $this->items->get_item_by_id($component_id, $item_id) : false);
        if ($item) {
            $item['Qty'] = $qty;
            $item['OrderParameters'] = $additional_params;
        }
        else { // (b) this item wasn’t in the cart
            $item = $this->create_item($component_id, $item_id, $qty, $additional_params);
            // update $items if the collection is already initialized
            if ($this->items) { $this->items->add($item); }
        }

        if (!$this->ignore_stock_units_value() && strlen($item['StockUnits'])) {
            $qty = min($item['StockUnits'], $item['Qty']);
        }

        if (!$item['Checked'] || $qty == 0) { // ooops! looks like item is not in stock anymore
            $this->remove_item($component_id, $item_id); // cannot add this item to the cart, sorry
            return false; // ---- RETURN ----
        }

        return true;
    }

    /**
     * @param $component_id
     * @param $item_id
     * @param $qty
     * @param array $additional_params
     * @return bool
     */
    public function set_item_parameters($component_id, $item_id, $qty, array $additional_params = null) {
        return $this->add_item($component_id, $item_id, $qty, true, $additional_params);
    }

    /**
     * @param $component_id
     * @param $item_id
     * @return bool
     */
    public function remove_item($component_id, $item_id) {
        $component_id = (int)$component_id;
        $item_id = (int)$item_id;

        unset($this->raw_contents[$component_id][$item_id]);

        if ($this->items) {
            $this->items->remove_item_by_id($component_id, $item_id);
        }

        return true;
    }

    /**
     * @param $component_id
     * @param $item_id
     * @param $qty
     * @param $params
     * @return nc_netshop_item
     */
    protected function create_item($component_id, $item_id, $qty, $params = null) {
        return new nc_netshop_item(
            array('Class_ID' => $component_id, 'Message_ID' => $item_id),
            array('Qty' => $qty, 'OrderParameters' => $params)
        );
    }

    /**
     * Возвращает содержимое корзины в виде коллекции товаров
     * @return nc_netshop_item_collection
     */
    public function get_items() {
        if (!$this->items) {
            $this->items = new nc_netshop_item_collection();
            $this->items->set_index_property('_ItemKey');

            $ignore_stock_units_value = $this->ignore_stock_units_value();

            foreach ((array)$this->raw_contents as $component_id => $items) {
                foreach ($items as $item_id => $item_data) {
                    $item = $this->create_item($component_id, $item_id, $item_data['Qty'], $item_data['OrderParameters']);

                    // check if the item is still available for order
                    if ($item['Checked'] && ($ignore_stock_units_value || $item['StockUnits'] !== '0')) {
                        if (!$ignore_stock_units_value && $item['StockUnits'] && ($item['Qty'] > $item['StockUnits'])) {
                            // Количество товара «Z» в вашей корзине было изменено, так как количество товара на складе менее выбранного вами.
                            $this->add_quantity_notification(
                                sprintf(NETCAT_MODULE_NETSHOP_ITEM_QTY_CHANGED, $item['FullName']),
                                $item,
                                $item['Qty']
                            );
                            $item['Qty'] = $item['StockUnits'];
                        }
                        $this->items->add($item);
                    }
                    else { // not 'Checked' or StockUnits === '0'
                        // Товар «Z» в настоящее время недоступен для заказа.
                        $this->add_quantity_notification(
                            sprintf(NETCAT_MODULE_NETSHOP_ITEM_CANNOT_BE_ORDERED, $item['FullName']),
                            $item,
                            $item['Qty']
                        );
                    }
                }
            }
        }

        return $this->items;
    }

    /**
     * @param string $message
     * @param nc_netshop_item $item
     * @param int|float $requested_qty
     */
    protected function add_quantity_notification($message, nc_netshop_item $item, $requested_qty) {
        if (!$this->quantity_notifications) { $this->quantity_notifications = new nc_netshop_cart_notifications; }
        $this->quantity_notifications->add($message, $item, $requested_qty);
    }

    /**
     * Возвращает объект с сообщениями об ошибках в количестве товаров
     * (товар более недоступен для заказа; выбранное количество товара больше, чем
     * есть на складе)
     * @return nc_netshop_cart_notifications|null
     */
    public function get_quantity_notifications() {
        $this->get_items(); // "init"
        return $this->quantity_notifications;
    }

    /**
     *
     */
    public function clear() {
        $this->raw_contents = array();
        $this->items = null;
    }

    /**
     * Сумма по полю
     *
     * @param string $field        Название поля
     * @param bool $consider_qty   Учитывать количество товара (Qty)
     * @return number
     */
    public function get_field_sum($field, $consider_qty = true) {
        if (!$consider_qty) {
            return $this->get_items()->sum($field);
        }
        else {
            return $this->get_items()->get_field_sum($field);
        }
    }

    /**
     * Количество товаров в корзине
     *
     * @param boolean $count_individual_items При `true` учитывается количество одной позиции (Qty)
     *
     * @return int
     */
    public function get_item_count($count_individual_items = false) {
        if ($count_individual_items) { return $this->get_field_sum('Qty', false); }
                                else { return $this->get_items()->count(); }
    }

    /**
     * @return number
     */
    public function get_totals() {
        return $this->get_field_sum('TotalPrice', false);
    }

    public function get_discount_sum() {
        return $this->get_field_sum('TotalDiscount', false);
    }
}