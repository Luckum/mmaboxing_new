<?php


class nc_netshop_statistics {

    // const PERIOD_DAY   = 'DAY';
    // const PERIOD_WEEK  = 'WEEK';
    // const PERIOD_MONTH = 'MONTH';
    // const PERIOD_ALL   = '';

    //-------------------------------------------------------------------------

    protected $order_table;
    protected $order_goods_table;
    protected $order_status_table;
    protected $order_status_list;
    protected $order_sum_status_ids;

    protected $order_table_catalogue_subs = array();

    protected $catalogue_id;

    //-------------------------------------------------------------------------

    public function __construct(nc_netshop $netshop) {
        $this->netshop = $netshop;

        $this->catalogue_id = $netshop->get_catalogue_id();

        // Таблица заказов
        $orders_table_name = $netshop->get_order_table_name();
        $this->order_table  = nc_db_table::make($orders_table_name, 'Message_ID');

        // Таблица заказанных товаров
        $this->order_goods_table  = nc_db_table::make('Netshop_OrderGoods');

        // Таблица статусов заказа
        $this->order_status_table = nc_db_table::make('Classificator_ShopOrderStatus', 'ShopOrderStatus_ID');

        // Список заказов [Status_ID] => Status_Name
        $this->order_status_list = $this->order_status_table->get_list('ShopOrderStatus_Name');

        // Список идентификаторов статусов заказа по которым считается сумма покупок
        $order_sum_status_ids       = explode(',', $this->netshop->get_setting('PrevOrdersSumStatusID'));
        $this->order_sum_status_ids = array_map('intval', $order_sum_status_ids);

        // ...
        // $order_paid_status_id = $this->netshop->get_setting('PaidOrderStatusID');

        // Соотв-ие разделов (заказы) с id сайтов
        $order_class_id = $netshop->get_setting('OrderComponentID');
        $result = nc_db_table::make('Sub_Class')->where('Class_ID', $order_class_id)->get_list('Subdivision_ID', 'Catalogue_ID');
        foreach ($result as $sub_id => $cat_id) {
            $this->order_table_catalogue_subs[$cat_id][$sub_id] = $sub_id;
        }

        // Денормализация данных при необходимости
        $message_id = $this->order_table->select('Message_ID')->where('TotalGoods IS NULL')->order_by('Message_ID')->get_value();
        if ($message_id) {
            nc_core()->db->query("UPDATE $orders_table_name SET
                TotalPrice = (SELECT SUM(ItemPrice * Qty) FROM Netshop_OrderGoods WHERE Order_ID = Message_ID GROUP BY Order_ID),
                TotalGoods = (SELECT COUNT(*) FROM Netshop_OrderGoods WHERE Order_ID = Message_ID GROUP BY Order_ID)
                WHERE Message_ID >= {$message_id}");
        }

        $pk      = $this->order_goods_table->get_primary_key();
        $last_id = $this->order_goods_table->select($pk)->where('Catalogue_ID IS NULL')->order_by($pk)->get_value();
        if ($last_id) {
            $table = $this->order_goods_table->get_table();
            nc_core()->db->query("UPDATE {$table} SET
                Catalogue_ID = (SELECT Catalogue_ID From Sub_Class WHERE Class_ID = Item_Type)
                WHERE {$pk} >= {$last_id}");
        }
    }

    //-------------------------------------------------------------------------

    public function _create_fake_orders($orders = 30, $days_ago = 30) {
        $user_agent   = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/31.0.1650.57 Safari/537.36';
        $sub_id       = 376;
        $sub_class_id = 585;
        $uid          = 1;
        $user_ip      = '127.0.0.1';

        $users = array(
            // User_ID | ContactName | Email
            array(1, 'admin', 'admin@test.loc', 1),
            array(0, 'Dima', 'dima@test.loc', 1),
            array(0, 'Zorro', 'zorro@test.loc', 2),
            array(0, 'Viktor', 'viktor@test.loc', 1),
            array(0, 'John Snow', 'snow@test.loc', 1),
            array(0, 'Vika', 'vika@test.loc', 1),
            array(0, 'Rukkola', 'rukkola@test.loc', 1),
            array(0, 'Poops', 'poops@test.loc', 1),
            array(0, 'Amely', 'amely@test.loc', 1),
        );

        $products = array(
            array(521, 1, 20735),
            array(521, 4, 20100),
            array(523, 1, 4578),
            array(527, 2, 15482),
            array(527, 1, 890),
            array(524, 13, 37990),
        );

        for ($i=0; $i<$orders; $i++) {

            $user = $users[rand(0, count($users)-1)];
            $date = date("Y-m-d H:i:s", rand(time()-60*60*24*$days_ago, time()));

            $order = array(
                'User_ID'           => $user[0],
                'Subdivision_ID'    => $sub_id,
                'Sub_Class_ID'      => $sub_class_id,
                'Checked'           => 1,
                'IP'                => $user_ip,
                'UserAgent'         => $user_agent,
                'Created'           => $date,
                'LastUser_ID'       => $uid,
                'LastIP'            => $user_ip,
                'LastUserAgent'     => $user_agent,
                'OrderCurrency'     => 1,
                'ContactName'       => $user[1],
                'City'              => $user[3],
                'Email'             => $user[2],
                // 'PaymentCost'    => NULL,
                // 'PaymentMethod'  => 0,
                // 'PaymentInfo'    => '',
                // 'DeliveryMethod' => NULL,
                // 'DeliveryCost'   => NULL,
                'Status'            => rand(0, count($this->order_status_list)),
                // 'user_hash'      => NULL,
                // 'TotalPrice'     => 31308,
                // 'TotalGoods'     => 3,
            );

            $order_id = $this->order_table->insert($order);

            $total_goods    = rand(1,rand(1,rand(1,7)));
            $order_products = array();
            while ($total_goods--) {
                $prod = $products[rand(0, count($products)-1)];
                $order_products[] = array(
                    'Order_ID'      => $order_id,
                    'Item_Type'     => $prod[0],
                    'Item_ID'       => $prod[1],
                    'Qty'           => rand(1,rand(1,rand(1,3))),
                    'OriginalPrice' => $prod[2],
                    'ItemPrice'     => $prod[2],
                );
            }

            foreach ($order_products as $prod) {
                $this->order_goods_table->insert($prod);
            }

            // idebug( $order_products );
            // idebug($row);
            // exit;
        }
    }

    //-------------------------------------------------------------------------

    /**
     * Кол-во заказов по каждому статусу
     * @return array [status_name=>count]
     */
    public function get_order_status_counts() {
        $order_status_counts = array();

        $this->set_current_catalogue($this->order_table);
        $result = $this->order_table->select('Status, COUNT(*) AS totals')->group_by('Status')->get_list('Status', 'totals');

        foreach ($result as $id => $count) {
            $name = $id ? $this->order_status_list[$id] : NETCAT_MODULE_NETSHOP_MAILER_CUSTOMER_ORDER;
            $order_status_counts[$name] = $count;
        }

        // Проставляем нулевые значения
        // Новый заказ
        if (!isset($order_status_counts[NETCAT_MODULE_NETSHOP_MAILER_CUSTOMER_ORDER])) {
            $order_status_counts[NETCAT_MODULE_NETSHOP_MAILER_CUSTOMER_ORDER] = 0;
        }
        // По каждому статусу
        foreach ($this->order_status_list as $id => $name) {
            if (!isset($order_status_counts[$name])) {
                $order_status_counts[$name] = 0;
            }
        }

        return $order_status_counts;
    }

    //-------------------------------------------------------------------------

    /**
     * Общая статистика по заказам за периоды
     * @param  string $period DAY | WEEK | MONTH | YEAR
     * @return array
     */
    public function get_order_totals($period = 'DAY', $from_offset = 0) {
        $this->init_period_query($this->order_table, 'Created', $period, $from_offset);

        // Всего заказов
        $this->set_current_catalogue($this->order_table->load_query('period'));
        $total_orders = $this->order_table->count_all();

        // Завершенные заказы
        $this->set_current_catalogue($this->order_table->load_query('period'));
        $order = $this->order_table
            ->select('COUNT(*) completed_orders, SUM(TotalPrice) sales_amount, SUM(TotalGoods) purchased_goods')
            ->where_in('Status', $this->order_sum_status_ids)
            ->get_row();

        return array(
            // Всего заказов
            'total_orders' => $total_orders,
            // Заказов выполнено (оплачено или завершено)
            'completed_orders' => $order['completed_orders'],
            // Процент успешных заказов
            'successful_orders_percentage' => ($total_orders ? floor(($order['completed_orders'] / $total_orders) * 100) : '0') . '%',
            // Куплено товаров
            'purchased_goods' => (int) $order['purchased_goods'],
            // Проданно на сумму (учитывая скидки)
            'sales_amount' => (float) $order['sales_amount'],
        );
    }

    //-------------------------------------------------------------------------

    public function get_order_avg_totals($period = 'DAY', $avg_period = 'WEEK', $avg_offset = 0) {

        $this->init_period_query($this->order_table, 'Created', $avg_period, $avg_offset);

        // Всего заказов
        $this->set_current_catalogue($this->order_table->load_query('period'));
        $result = $this->order_table->select("{$period}(`Created`) period, COUNT(*)")->group_by("period")->get_list('period', 'COUNT(*)');

        $avg_orders = $result ? number_format(array_sum($result)/count($result), 1) : 0;

        // Завершенные заказы
        $this->set_current_catalogue($this->order_table->load_query('period'));
        $orders = $this->order_table
            ->select("{$period}(`Created`) period, COUNT(*) completed_orders, SUM(TotalPrice) sales_amount, SUM(TotalGoods) purchased_goods")
            ->where_in('Status', $this->order_sum_status_ids)
            ->group_by('period')
            ->get_result();

        $avg = array();
        foreach ($orders as $row) {
            if (!$avg) {
                $avg = $row;
            } else {
                foreach ($row as $k => $val) {
                    $avg[$k] = $avg[$k] + $val;
                }
            }
        }

        $period_dividers = array('WEEK' => 7, 'MONTH' => 30, 'YEAR' => 356);
        $divider = isset($period_dividers[$avg_period]) ? $period_dividers[$avg_period] : count($orders);
        foreach ($avg as &$val) {
            $val = number_format($val / $divider, 1, '.', '');
        }

        return array(
            // Всего заказов
            'total_orders' => $avg_orders,
            // Заказов выполнено (оплачено или завершено)
            'completed_orders' => $avg['completed_orders'],
            // Процент успешных заказов
            'successful_orders_percentage' => ($avg_orders ? floor(($avg['completed_orders'] / $avg_orders) * 100) : '0') . '%',
            // Куплено товаров
            'purchased_goods' => (int) $avg['purchased_goods'],
            // Проданно на сумму (учитывая скидки)
            'sales_amount' => (int) $avg['sales_amount'],
        );
    }

    //-------------------------------------------------------------------------

    public function get_order_stat($period = 30, $group_by = 'day') {
        static $grouping_settings = array(
            'day' => array(
                'date_format'     => 'd.m.Y',
                'sql_date_format' => "DATE_FORMAT({table}.`Created`, '%d.%m.%Y')",
            ),
            'week' => array(
                'date_format'     => 'oW',
                'sql_date_format' => "YEARWEEK({table}.`Created`, 3)",
            ),
            'month' => array(
                'date_format'     => 'm.Y',
                'sql_date_format' => "DATE_FORMAT({table}.`Created`, '%m.%Y')",
            ),
        );

        if (empty($grouping_settings[$group_by])) {
            return array();
        }

        $settings = $grouping_settings[$group_by];
        $dt       = new DateTime;
        $dt_from  = new DateTime;
        $dt_to    = new DateTime;

        if (is_numeric($period)) {
            $from_date = $dt->modify("-{$period} day")->format('Y-m-d');
            $to_date   = date('Y-m-d');
        } elseif (is_array($period)) {
            $from_date = $period[0];
            $to_date   = $period[1];
        }

        call_user_func_array(array($dt_from, 'setDate'), explode('-', $from_date));
        call_user_func_array(array($dt_to,   'setDate'), explode('-', $to_date));

        // return [
        //     'from_date' => $from_date,
        //     'to_date'   => $to_date,
        //     'group_by'  => $group_by,
        // ];


        // static $period_dates    = array();

        // Заполняем массив названиями каждого дня/недели/месяца периода
        // $period_key = $group_by . $items;
        // if (!isset($period_dates[$period_key])) {
        //     $dt = new DateTime();
        //     $dt->modify('-' . $items . ' ' . $group_by);

        //     $date_format = $settings['date_format'];

        //     switch ($group_by) {
        //         case 'DAY':
        //             $last_month = '';
        //             for ($i=0; $i<=$items; $i++) {
        //                 $month = constant('NETCAT_MODULE_STATS_OPENSTAT_SHORT_MONTH_' . $dt->format('n'));
        //                 $period_dates[$period_key][$dt->format($date_format)] = $dt->format('j') . ($month != $last_month ? '<br>' . $month : '');
        //                 $dt->modify('+1 day');
        //                 $last_month = $month;
        //             }
        //             break;
        //         case 'WEEK':
        //             for ($i=0; $i<=$items; $i++) {
        //                 $period_dates[$period_key][$dt->format($dt->format($date_format))] = $dt->format('W');
        //                 $dt->modify('+1 week');
        //             }
        //             break;
        //         case 'MONTH':
        //             $year = '';
        //             for ($i=0; $i<=$items; $i++) {
        //                 $year = $dt->format('Y');
        //                 $period_dates[$period_key][$dt->format($dt->format($date_format))] = constant('NETCAT_MODULE_STATS_OPENSTAT_SHORT_MONTH_' . $dt->format('n')) . ($year != $last_year ? '<br>' . $year : '');
        //                 $dt->modify('+1 month');
        //                 $last_year = $year;
        //             }
        //             break;
        //     }
        // }

        $sql_period = $settings['sql_date_format'] . " AS period";

        // Всего заказов
        $total_orders = $this->set_current_catalogue($this->order_table)
            ->select("{$sql_period}, COUNT(*) total_orders")
            ->where("{table}.`Created` >= DATE('{$from_date} 00:00:00')")
            ->where("{table}.`Created` < DATE('{$to_date}')")
            ->group_by('period')
            ->index_by('period')
            ->order_by('{table}.Created')
            ->get_result();

        // echo $this->order_table->get_last_query();
        // idebug($total_orders);exit;

        // Завершенные заказы
        $orders = $this->set_current_catalogue($this->order_table)
            ->select("{$sql_period}, COUNT(*) completed_orders, SUM(TotalPrice) sales_amount, SUM(TotalGoods) purchased_goods")
            ->where("{table}.`Created` >= DATE('{$from_date} 00:00:00')")
            ->where("{table}.`Created` < DATE('{$to_date}')")
            ->where_in('Status', $this->order_sum_status_ids)
            ->group_by('period')
            ->index_by('period')
            ->order_by('{table}.Created')
            ->get_result();

        // echo $this->order_table->get_last_query();
        // idebug($orders);exit;


        $empty_row = array(
            'period'                       => '',
            'completed_orders'             => '0',
            'sales_amount'                 => '0',
            'purchased_goods'              => '0',
            'total_orders'                 => '0',
            'successful_orders_percentage' => '0%',
        );

        $result = array();

        // call_user_func_array(array($dt, 'setDate'), explode('-', $from_date));
        // call_user_func_array(array($dt, 'setDate'), explode('-', $from_date));
        // echo $dt_from->format('Y-m-d') . ' - ' . $dt_to->format('Y-m-d') . '<br>';

        // Заполняем каждый день (период) пустыми значениями
        while ($dt_to > $dt_from) {
            $key = $dt_from->format($settings['date_format']);
            $dt_from->modify('+1 ' . $group_by);
            $result[$key] = $empty_row;
            $result[$key]['period'] = $key;
        }

        foreach ($orders as $period => $order) {
            if (empty($result[$period])) {
                $result[$period] = $empty_row;
                $result[$period]['period'] = $key;
            }
            $result[$period] = array_merge($result[$period], $order);
        }

        foreach ($total_orders as $period => $order) {
            $result[$period]['total_orders']                 = $order['total_orders'];
            $result[$period]['successful_orders_percentage'] = ($order['total_orders'] ? ($result[$period]['completed_orders'] / $order['total_orders'] * 100) : 0) . '%';
        }

        switch ($group_by) {
            case 'day':
                $full_data = count($result) > 31;
                $last_y = 0;
                $last_m = 0;
                foreach ($result as $period => $stat) {
                    $d = substr($period, 0, 2);
                    $m = substr($period, 3, 2);
                    $y = substr($period, 6 + ($full_data ? 2 : 0), 4);

                    $month = '';
                    if ($last_m != $m || $full_data) {
                        $month = '<br>' . constant('NETCAT_MODULE_STATS_OPENSTAT_SHORT_MONTH_' . intval($m)) . ' ' . $y;
                    }
                    $result[$period]['period'] = $d . $month;
                    $last_y = $y;
                    $last_m = $m;
                }
                break;

            case 'week':
                $last_y = 0;
                foreach ($result as $period => $stat) {
                    $y = substr($period, 0, 4);
                    $w = substr($period, 4, 2);
                    $result[$period]['period'] = $w . ($last_y != $y ? '<br>' . $y : '');
                    $last_y = $y;
                }
                break;
            case 'month':
                $last_y = 0;
                foreach ($result as $period => $stat) {
                    $m = substr($period, 0, 2);
                    $y = substr($period, 3, 4);
                    $result[$period]['period'] = $m . ($last_y != $y ? '<br>' . $y : '');
                    $last_y = $y;
                }
                break;
        }

        return $result;
    }

    //-------------------------------------------------------------------------

    public function get_customers_total() {
        $sql = $this->set_current_catalogue($this->order_table)->select('1')->group_by('Email')->make_query();
        return nc_db()->get_var("SELECT COUNT(*) FROM ({$sql}) AS a");
    }

    //-------------------------------------------------------------------------

    public function get_customers_by_tatal_orders($items = 30, $page = 1) {
        $items = (int)$items;
        $page  = $page < 1 ? 1 : (int)$page;

        $this->set_current_catalogue($this->order_table);
        return $this->order_table
            ->select('COUNT(*) AS TotalOrders, SUM(TotalGoods) AS TotalGoods, SUM(TotalPrice) AS TotalPrice, User_ID, Email, ContactName')
            ->group_by('Email')
            ->order_by('TotalOrders', 'DESC')
            ->limit(($page-1)*$items, $items)
            ->get_result();
    }

    //-------------------------------------------------------------------------

    public function get_order_goods_total() {
        $sql = $this->set_current_catalogue($this->order_table)->select('1')->group_by('Item_Type')->group_by('Item_ID')->make_query();
        return nc_db()->get_var("SELECT COUNT(*) FROM ({$sql}) AS a");
    }

    //-------------------------------------------------------------------------

    public function get_order_goods_by_qty($items = 20, $page = 1) {
        return $this->get_order_goods_by('Qty', $items, $page);
    }

    //-------------------------------------------------------------------------

    public function get_order_goods_by_sales_amount($items = 20, $page = 1) {
        return $this->get_order_goods_by('SalesAmount', $items, $page);
    }

    //-------------------------------------------------------------------------

    protected function get_order_goods_by($by, $items = 20, $page = 1) {
        $items = (int)$items;
        $page  = $page < 1 ? 1 : (int)$page;

        $order_goods = $this->order_goods_table
            ->select('`Item_Type` AS Class_ID, `Item_ID` AS Message_ID, SUM(Qty) AS Qty, SUM(ItemPrice) AS SalesAmount')
            ->where('Catalogue_ID', $this->catalogue_id)
            ->group_by('`Item_Type`, `Item_ID`')
            ->order_by($by, 'DESC')
            ->limit(($page-1)*$items, $items)
            ->get_result();

        $result = array();
        foreach ($order_goods as $row) {
            $result[] = new nc_netshop_item($row);
        }

        return $result;
    }

    /**************************************************************************
        Protected methods
    **************************************************************************/

    protected function init_period_query($table, $date_field, $period = 'DAY', $from_date = 0) {
        switch ($period) {
            case 'DAY':
                $from = abs($from_date);
                $to   = abs($from_date) - 1;
                break;

            default:
                $from = 1 + abs($from_date);
                $to   = abs($from_date);
                break;
        }

        $table->where("{table}.`{$date_field}` > CURRENT_DATE - INTERVAL {$from} {$period}");
        if ($from_date) {
            $table->where("{table}.`{$date_field}` < CURRENT_DATE - INTERVAL {$to} {$period}");
        }
        $table->save_query('period');
    }

    //-------------------------------------------------------------------------

    protected function set_current_catalogue($table) {
        if (!empty($this->order_table_catalogue_subs[$this->catalogue_id])) {
            $table->where_in('Subdivision_ID', $this->order_table_catalogue_subs[$this->catalogue_id]);
        }

        return $table;
    }
}