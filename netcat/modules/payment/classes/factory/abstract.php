<?

/**
 * Абстрактная фабрика для создания объекта плтежной систмы
 *
 * @abstract
 *
 */
abstract class nc_payment_factory_abstract {

    /**
     * Cоздание объекта платежной системы
     *
     * @static
     * @param    string $class_name
     * @return   nc_payment_system
     */
    public static function create($class_name) {
        return new $class_name;
    }

    /**
     * Массив с названиями классов доступных платежных систем (по названию файлов
     * классов в папке classes/system)
     *
     * @static
     * @return    array
     */
    public static function get_available_payment_systems() {
        $result = array();
        $dir = nc_module_path('payment') . "classes/system";
        if (is_dir($dir)) {
            foreach (glob("$dir/*.php") as $file_name) {
                $result[] = "nc_payment_system_" . basename($file_name);
            }
        }
        return $result;
    }

}
