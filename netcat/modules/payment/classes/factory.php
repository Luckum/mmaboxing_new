<?

/**
 * Фабрика создания объекта платежной системы с использованием данных NetCat
 *
 * @original_author vitaliy
 *
 */
class nc_payment_factory extends nc_payment_factory_abstract {

    /**
     * Cоздание объекта платежной системы по названию класса или ID записи в
     * списке (классификаторе) PaymentSystem
     *
     * @param    string|int $system
     * @return   nc_payment_system|null
     */
    public static function create($system) {
        $db = nc_db();
        if (is_numeric($system)) {
            $system_id = (int)$system;

            $sql = "SELECT `Value`
                      FROM `" . NC_PAYMENT_SYSTEM_CLASSIFIER_TABLE . "`
                     WHERE `PaymentSystem_ID` = {$system_id}";

            $system_class = $db->get_var($sql);
        }
        else {
            $system_class = $system;
            $system_id = (int)$db->get_var(
                "SELECT `PaymentSystem_ID`
                   FROM `" . NC_PAYMENT_SYSTEM_CLASSIFIER_TABLE . "`
                  WHERE `Value` = '" . $db->escape($system_class) . "'"
            );
        }

        if (!$system_id || !$system_class) {
            return null;
        }

        $payment_system = parent::create($system_class);
        $payment_system->set_id($system_id);
        self::set_system_params($payment_system);

        return $payment_system;
    }

    /**
     * Загрузка параметров платежной системы для текущего сайта
     *
     * @param    nc_payment_system $payment_system
     * @global   ID сайта
     */
    protected static function set_system_params(nc_payment_system $payment_system) {
        /** @var nc_catalogue $catalogue */
        $catalogue = nc_core('catalogue');

        $current_catalogue = (int)$catalogue->get_current("Catalogue_ID");
        $system_id = (int)$payment_system->get_id();

        $results = (array)nc_db()->get_results("SELECT `Param_Name`, `Param_Value`
                                                  FROM `" . NC_PAYMENT_SYSTEM_PARAM_TABLE . "`
                                                 WHERE `System_ID`='$system_id'
                                                   AND `Catalogue_ID` = $current_catalogue", ARRAY_A);

        $params = array();
        foreach($results as $row) {
            $params[$row['Param_Name']] = $row['Param_Value'];
        }

        $payment_system->set_settings($params);
    }

    /**
     * Функция возвращает массив с названиями классов включенных платежных систем
     * на сайте $catalogue, или, если $catalogue не указан, список классов платежных
     * систем из списка (классификатора) PaymentSystem.
     *
     * @param    integer $catalogue
     * @return   array
     */
    public static function get_available_payment_systems($catalogue = NULL) {
        /** @var nc_db $db */
        $db = nc_Core::get_object()->db;
        $catalogue = (int)$catalogue;

        $query = "SELECT b.*
                    FROM `" . NC_PAYMENT_SYSTEM_CATALOGUE_TABLE . "` AS a
                    LEFT JOIN `" . NC_PAYMENT_SYSTEM_CLASSIFIER_TABLE . "` AS b
                         ON (a.`PaymentSystem_ID` = b.`PaymentSystem_ID`)";

        if ($catalogue) {
            $query .= "WHERE a.`Catalogue_ID` = $catalogue AND a.`Checked`=1";
        }

        return (array)$db->get_results($query, ARRAY_A);
    }

}
