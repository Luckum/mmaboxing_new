<?php


class nc_netshop_statistics_admin_controller extends nc_netshop_admin_controller {

    //-------------------------------------------------------------------------

    protected $rows_per_page = 30;

    protected $chart_defaults = array(
        'width'  => 700,
        'height' => 250,
    );

    protected $chart_labels = array(
        'total_orders'                 => NECTAT_MODULE_NETSHOP_TOTAL_ORDERS,
        'completed_orders'             => NECTAT_MODULE_NETSHOP_COMPLETED_ORDERS,
        'purchased_goods'              => NECTAT_MODULE_NETSHOP_PURCHASED_GOODS,
        'sales_amount'                 => NECTAT_MODULE_NETSHOP_SALES_AMOUNT,
        'successful_orders_percentage' => NECTAT_MODULE_NETSHOP_SUCCESSFUL_ORDERS_PERCENTAGE,
    );

    //-------------------------------------------------------------------------

    protected function init() {
        parent::init();

        $this->statistics = $this->netshop->statistics;

        $this->bind('orders',    array('period', 'group_by'));
        $this->bind('goods',     array('page'));
        $this->bind('customers', array('page'));
    }

    //-------------------------------------------------------------------------

    protected function before_action() {
        $this->ui_config = new nc_netshop_statistics_admin_ui($this->site_id, $this->current_action);
    }

    //-------------------------------------------------------------------------

    protected function get_action_url() {
        $url = nc_core('SUB_FOLDER') . nc_core('HTTP_ROOT_PATH') . 'modules/netshop/admin/?controller=statistics';

        $params = array(
            'catalogue_id' => $this->site_id
        );

        if (func_num_args()) {
            $args = func_get_args();
            $params = array_merge($params, $args);
        }

        foreach ($params as $name => $value) {
            $url .= '&' . $name . '=' . $value;
        }

        return $url;
    }

    /**************************************************************************
        ACTIONS
    **************************************************************************/

    public function action_index() {
        // $this->statistics->_create_fake_orders(1000, 1000);
        // $this->statistics->_create_fake_orders(30, 30);
        return $this->action_orders();
    }

    /**************************************************************************
        ORDER STAT
    **************************************************************************/

    public function action_orders($period = false, $group_by = false) {
        // $stat = $this->statistics->get_order_stat(array('2013-2-1','2013-3-1'));
        // idebug($stat);
        // exit;

        if ($period) {
            $this->use_layout = false;

            switch ($period) {
                case '7days':
                    $stat = $this->statistics->get_order_stat(7, $group_by);
                    break;

                case '30days':
                    $stat = $this->statistics->get_order_stat(30, $group_by);
                    break;

                case 'monthly':
                    $stat = $this->statistics->get_order_stat(12, $group_by);
                    break;

                default:
                    if (preg_match('@(\d+)-(\d+)-(\d+):(\d+)-(\d+)-(\d+)@', $period)) {
                        $stat = $this->statistics->get_order_stat(explode(':', $period), $group_by);
                    } else {
                        return nc_core()->ui->alert->error('Unknown action!!!');
                    }
            }
            // idebug($stat);exit;

            $chart_stat = array();
            if ($stat) {
                foreach ($stat as $period => $period_stat) {
                    foreach ($period_stat as $k => $val) {
                        if ($k != 'period') {
                            $chart_stat[$k]['data'][] = array($period_stat['period'], $val * 1);
                        }
                    }
                }

                foreach ($chart_stat as $period => $data) {
                    $chart_stat[$period]['label'] = isset($this->chart_labels[$period]) ? $this->chart_labels[$period] : '';
                }
            }

            return $this->view('statistics/orders_period_stat')
                ->with('stat', $stat)
                ->with('chart_stat', json_safe_encode($chart_stat));
        }


        // Итоговые значения за период
        $totals = array(
            'day' => array(
                'today'        => $this->statistics->get_order_totals('DAY'),
                'yesterday'    => $this->statistics->get_order_totals('DAY', -1),
                'avg_for_week' => $this->statistics->get_order_avg_totals('DAY', 'WEEK'),
            ),
            'week' => array(
                'week'      => $this->statistics->get_order_totals('WEEK'),
                'last_week' => $this->statistics->get_order_totals('WEEK', -1),
            ),
            'month' => array(
                'month'      => $this->statistics->get_order_totals('MONTH'),
                'last_month' => $this->statistics->get_order_totals('MONTH', -1),
            ),
        );

        // Кол-во заказов по каждому статусу (за весь период)
        $order_status_counts = $this->statistics->get_order_status_counts();

        return $this->view('statistics/orders_index')
            ->with('order_status_counts', $order_status_counts)
            // ->with('stat', $stat)
            // ->with('json_stat', nc_array_json($json_stat))
            ->with('totals', $totals);
            // ->with('price_columns', $price_columns)
    }

    /**************************************************************************
        GOODS STAT
    **************************************************************************/

    public function action_goods($page) {
        $page  = $page < 1 ? 1 : (int)$page;
        $items = $this->rows_per_page;

        $data = array(
            'items'    => $items,
            'page'     => $page,
            'total'    => $this->statistics->get_order_goods_total(),
            'page_url' => $this->get_action_url(array('action'=>'goods', 'page'=>''))// nc_core()->SUB_FOLDER . nc_core()->HTTP_ROOT_PATH . 'modules/netshop/admin/?controller=statistics&action=goods&page='
        );

        return $this->view('statistics/goods_index', $data)
            ->with('pagination',            $this->view('statistics/pagination', $data))
            ->with('goods_by_qty',          $this->statistics->get_order_goods_by_qty($items, $page))
            ->with('goods_by_sales_amount', $this->statistics->get_order_goods_by_sales_amount($items, $page));
    }

    /**************************************************************************
        CUSTOMERS STAT
    **************************************************************************/

    public function action_customers($page) {
        $page  = $page < 1 ? 1 : (int)$page;
        $items = $this->rows_per_page;

        $data = array(
            'items'    => $items,
            'page'     => $page,
            'total'    => $this->statistics->get_customers_total(),
            'page_url' => $this->get_action_url(array('action'=>'customers', 'page'=>'')) //nc_core()->SUB_FOLDER . nc_core()->HTTP_ROOT_PATH . 'modules/netshop/admin/?controller=statistics&action=customers&page='
        );

        return $this->view('statistics/customers_index')
            ->with('pagination',                $this->view('statistics/pagination', $data))
            ->with('customers_by_tatal_orders', $this->statistics->get_customers_by_tatal_orders($items, $page));
    }

    /**************************************************************************
        COUPONS STAT
    **************************************************************************/

    public function action_coupons() {
        return 'coupons';
    }

    /**************************************************************************
        PROTECTED
    **************************************************************************/

    protected function view($view, $data = array()) {
        // $netcat_path     = nc_core('SUB_FOLDER') . nc_core('HTTP_ROOT_PATH');
        $catalogue_id    = nc_core()->catalogue->id();

        $data['controller_link'] = $this->get_action_url();// $netcat_path . 'modules/netshop/admin/?controller=statistics&catalogue_id=' . $catalogue_id;

        return parent::view($view, $data)
            ->with('chart_defaults', nc_array_json($this->chart_defaults))
            ->with('stat_init', parent::view('statistics/stat_init', $data))
            ->with('chart_init', '<script src="'.nc_core('ADMIN_PATH').'js/nc/nc.chart.min.js"></script>');
    }

    //-------------------------------------------------------------------------
}