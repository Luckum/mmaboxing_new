<?php

/* $Id: nc_subscriber_tools.class.php 7302 2012-06-25 21:12:35Z alive $ */

class nc_subscriber_tools {

    protected $core, $db;
    protected $period_table;

    protected function __construct() {
        $this->core = nc_Core::get_object();
        $this->db = $this->core->db;
        $this->period_table = 'SubscriberPeriod';
    }

    public static function get_object() {
        // call as static
        static $storage;
        // check inited object
        if (!isset($storage)) {
            // init object
            $storage = new self();
        }
        // return object
        return is_object($storage) ? $storage : false;
    }

    /**
     * Проверяет сс на существование.
     * При ошибке выбрасывает исключение.
     * @param int $cc номер компонента в разделе
     * @return bool
     */
    public function check_cc($cc, $mailer_type = 1) {
        $cc = intval($cc);
        if (!$cc) {
            throw new Exception("<br/>Incorrect param \$cc = ".$cc.".<br/>");
        }
        $item_exist = $this->db->get_var("SELECT `Sub_Class_ID`
                              FROM `Sub_Class`
                              WHERE `Sub_Class_ID` = '".$cc."'");
        if (!$item_exist) {
            throw new Exception("<br/>Sub class (".$cc.") does not exist.<br/>");
        }
        if ($mailer_type == 4) { //для серийной рассылки нужно проверить обязательное поле.
            $field_exist = $this->db->get_var("SELECT f.`Field_ID`
                                  FROM `Field` as f, `Sub_Class` AS sc
                                  WHERE f.`Class_ID` = sc.`Class_ID`
                                        AND sc.`Sub_Class_ID` = '".$cc."' 
                                        AND f.`Field_Name` = 'ncDuration' ");
            if (!$field_exist) {
                throw new Exception("<br/>Sub class (".$cc.") does not have field 'ncDuration'.<br/>");
            }
        }
        return true;
    }

    /**
     * Проверяет пользователя на существование.
     * При ошибке выбрасывает исключение.
     * @param int $user_id номер пользователя
     * @return bool
     */
    public function check_user($user_id) {
        $user_id = intval($user_id);
        if (!$user_id) {
            throw new Exception("<br/>Incorrect param \$user_id = ".$user_id.".<br/>");
        }
        $item_exist = $this->db->get_var("SELECT `User_ID`
                              FROM `User`
                              WHERE `User_ID` = '".$user_id."'");
        if (!$item_exist) {
            throw new Exception("<br/>User (".$user_id.") does not exist.<br/>");
        }

        return true;
    }

    /**
     * Проверяет период на существование.
     * При ошибке выбрасывает исключение.
     * @param int $period период
     * @return bool
     */
    public function check_period($period) {
        $period = intval($period);
        $item_exist = $this->db->get_var("SELECT `".$this->period_table."_ID`
                                      FROM `Classificator_".$this->period_table."`
                                      WHERE `".$this->period_table."_ID` = '".$period."'");
        if (!$item_exist) {
            throw new Exception("<br/>Period (".$period.") does not exist.<br/>");
        }

        return true;
    }

    /**
     * Возвращает период по умолчанию.
     * При ошибках выбрасывает исключение.
     * @return int период
     */
    public function get_default_period() {
        $this->db->last_error = '';
        // классификатор с периодами
        $classificator = $this->db->get_row("SELECT `Sort_Type`, `Sort_Direction`
                                         FROM `Classificator`
                                         WHERE Table_Name='".$this->period_table."'",
                        ARRAY_A);
        // проверка на ошибки
        if ($this->db->last_error) {
            throw new Exception($this->db_error(__CLASS__, __FUNCTION__));
        }
        if (empty($classificator)) {
            throw new Exception("Classificator ".$this->period_table." does not exist");
        }

        $SortDirection = $classificator['Sort_Direction'];

        // сортировка по полю...
        switch ($classificator['Sort_Type']) {
            case 1:
                $sort = "`".$this->period_table."_Name`";
                break;
            case 2:
                $sort = "`".$this->period_table."_Priority`";
                break;
            default:
                $sort = "`".$this->period_table."_ID`";
        }

        // выбор первого элемента
        $period = $this->db->get_var("SELECT `".$this->period_table."_ID`
                                  FROM `Classificator_".$this->period_table."`
                                  WHERE `Checked` = '1'
                                  ORDER BY ".$sort."
                                  ".($SortDirection == 1 ? "DESC" : "ASC")."
                                  LIMIT 1");

        // проверка на ошибки
        if ($this->db->last_error) {
            throw new Exception($this->db_error(__CLASS__, __FUNCTION__));
        }
        if (!$period) { // таблица пустая
            throw new Exception("There is no period");
        }

        return $period;
    }
    
    /**
     * Собирает настройки периодической рассылки в строку для записи в БД, по ней будет вестить поиск
     * @param array $data - данные из формы изменения периода
     * @return string
     */
    public function prepare_period_mask($data) {
        $rules = array();
        foreach ((array) $data['period_day'] as $day => $value) {
            $rules[] = $day.' '.$data['period_time_'.$day];
        }
        if ($data['period_mask']) {
            $month_rules = explode(',' , $data['period_mask']);
            foreach ($month_rules as $key => $value) {
                if (preg_match("/(first|last|day)+/i", $value)) {
                    $rules[] = preg_replace('/(\s){2,}/',' ',strtolower(trim($value)));
                }
            }
        }
        $mask = implode(', ' , $rules).",";
        return $mask;
        
    }
    
    /**
     * Разбирает строку полученную из базы данных для установки значений формы изменения периода
     * @param string $mask
     * @return array
     */
    public function parse_period_mask($mask) {
        if (!$mask) return false;
        $mask = trim($mask, ',');
        $rules = explode(',' , $mask);
        foreach ($rules as $key => $rule) {
            $rule = trim($rule);
            if (preg_match("/^\d\s\d{1,2}$/", $rule)) {
                $day = explode(' ', $rule);
                $data['period_day_'.$day[0]] = 1;
                $data['period_time_'.$day[0]] = $day[1];
            }
            else {
                $month_rules[] = $rule;
            }
        }
        $data['period_mask'] = !empty($month_rules) ? implode(', ' , $month_rules) : '';
        return $data;
    }

    public function get_default_status($user_id = 0) {
        global $current_user;
        // определение пользователя
        $user_id = intval($user_id);
        if (!$user_id) {
            $user_id = $current_user['User_ID'];
            if (!$user_id) return 'wait';
        }

        $default_status = $this->get_settings('ConfirmType');
        switch ($default_status) {
            case 0: // только незарегистрированным
            default:
                // пользователь зарегистрирован
                if ($this->db->get_var("SELECT `UserType` FROM `User` WHERE `User_ID` = '".$user_id."'") == 'normal')
                        return 'on';
            // break не нужен
            // Идет работа с незарегистрированным, нужно определить, был ли он ранее подписан, как в случае 1
            case 1: // только при первой подписке
                $res = $this->db->get_var("SELECT `ID` FROM `Subscriber_Subscription` WHERE `User_ID` = '".$user_id."' AND `Status` = 'on'");
                $status = $res ? 'on' : 'wait';
                break;
            case 2: // всегда
                $status = 'wait';
                break;
        }

        return $status;
    }

    public function log($mailer_id, $user_id, $action) {
        $user_id = intval($user_id);
        $mailer_id = intval($mailer_id);
        $action = $this->db->escape($action);
        $nc_s = nc_subscriber::get_object();
        $in_stat = $nc_s->get($mailer_id, 'InStat');
        if ($mailer_id && $user_id && $action && $in_stat) {
            $this->db->query("INSERT INTO `Subscriber_Log`(`Mailer_ID`, `User_ID`, `ActionType`)
                      VALUES('".$mailer_id."','".$user_id."','".$action."')");
        }
    }

    public function db_error($cl, $fn, $cron = 0) {
        global $perm;
        if (is_object($perm) && $perm->isSupervisor() || $cron) {
            return "Ошибка <br/><b>".$this->db->last_error."</b><br/> в запросе <br/><b>".$this->db->last_query."</b><br/>".
            "<b>".$cl."</b>::<b>".$fn."</b>";
        } else {
            return "Ошибка в запросе<br/>";
        }
    }

    public function get_settings($item ='', $reset = 0) {
        static $storage = array();
        if (empty($storage) || $reset) {
            $res = $this->db->get_results("SELECT `Key`, `Value` FROM `Subscriber_Settings`", ARRAY_A);
            if (!empty($res)) {
                foreach ($res as $v) {
                    $storage[$v['Key']] = $v['Value'];
                }
            }
        }
        if ($item) return $storage[$item];

        return $storage;
    }

    public function get_subscribe_sub($item = '', $catalogue = 0) {
        static $storage = array();
        $host = str_replace(array('http://', ''), '', $_SERVER['HTTP_HOST']);
        if (!isset($storage[$host])) {
            //выборка сайтов в которых есть раздел c компонентом "список подписок"
            $res = $this->db->get_results("SELECT c.`Catalogue_ID`, s.`Subdivision_ID`, s.`Hidden_URL`, c.`Domain`, REPLACE(CONCAT('|', c.`Domain`, '\\n', c.`Mirrors`, '|') , '\\n', '|') AS `Domains`
                               FROM `Catalogue` AS `c`, `Subdivision` AS `s`, `Sub_Class` as `sc`
                               WHERE sc.`Class_ID` = '".$this->core->modules->get_vars('subscriber', 'SUBSCRIBER_CLASS_ID')."'
                               AND sc.`Subdivision_ID` = s.`Subdivision_ID`
                               AND s.`Catalogue_ID` = c.`Catalogue_ID`", ARRAY_A);
            if (!$this->db->num_rows) return ($storage[$host] = array());

            foreach ($res as $v) { 
                //если сайт на раздел которого сделана подписка имеет раздел для управления
                if ($catalogue && $catalogue == $v['Catalogue_ID']) {
                    $storage[$host] = $v;
                    break;
                }
                //если текущий сайт имеет раздел для управления
                if (strpos(str_replace(' ', '', $v['Domains']), $host) !== false) {
                    $storage[$host] = $v;
                    break;
                }
            }
            //если так и не выбран сайт, берем первый в котором можно управлять рассылками
            if (!isset($storage[$host])) { 
                $storage[$host] = $res[0];
            }
        }
        if ($item) return $storage[$host][$item];
        return $storage[$host];
    }

}