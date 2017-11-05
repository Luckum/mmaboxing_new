<?php

/**
 * Коллекция объектов (на самом деле — не обязательно объектов nc_record; минимальное
 * требование к объекту коллекции — реализация интерфейса ArrayAccess).
 */
class nc_record_collection implements ArrayAccess, Countable, Iterator {

    protected $items_class = '';

    /** @var ArrayAccess[] */
    protected $items = array();

    /**
     * Total item count for partial collections
     * @var integer|null
     */
    protected $total_count;

    /**
     * присвоить ключам элементов коллекции значение свойства $index_property
     * @var string
     */
    protected $index_property;

    // -------------------------------------------------------------------------
    // Свойства, относящиеся к persistence
    // -------------------------------------------------------------------------

    // Костыли для PHP 5.2
    static protected $table_names = array();
    static protected $collection_classes = array();

    /**
     * @see self::load_all()
     * @var array
     */
    static protected $cache_all = array();

    /**
     *
     * @param array $items initial values
     * @param int|null $total_count
     * @param string|null $items_class
     */
    public function __construct(array $items = null, $total_count = null, $items_class = null) {
        if ($items_class) {
            $this->set_items_class($items_class);
        }
        if ($items) {
            $this->add_items($items);
        }
        $this->total_count = $total_count;
    }

    // -------------------------------------------------------------------------
    // Общая функциональность
    // (не обязательно используется вместе с сохранением в БД)
    // -------------------------------------------------------------------------

    /**
     *
     * @param string $items_class
     * @return $this
     */
    public function set_items_class($items_class) {
        $this->items_class = $items_class;
        return $this;
    }

    /**
     *
     * @throws nc_record_exception
     * @return string
     */
    public function get_items_class() {
        if ($this->items_class && !class_exists($this->items_class)) {
            throw new nc_record_exception(get_class($this).": wrong item class '$this->items_class'");
        }
        return $this->items_class;
    }

    /**
     * Устанавливает свойство, значение которого является ключом в массиве $this->items
     * (значения должны быть уникальными, иначе элементы коллекции будут потеряны!)
     *
     * @param string $property_name
     * @return $this
     */
    public function set_index_property($property_name) {
        if ($this->index_property != $property_name && sizeof($this->items)) { // need to reindex
            if ($property_name) {
                $reindexed = array();
                foreach ($this->items as $item) {
                    $reindexed[$item->offsetGet($property_name)] = $item;
                }
            } else { // $property_name == NULL?
                $reindexed = array_values($this->items);
            }
            $this->items = $reindexed;
        }
        $this->index_property = $property_name;
        return $this;
    }

    /**
     * Проверяет тип $item; в случае несовпадения выбрасывает исключение nc_record_exception
     * @param $item
     * @return bool
     * @throws nc_record_exception
     */
    protected function check_item_class($item) {
        if ($this->items_class && !is_a($item, $this->items_class)) {
            $msg = "This collection accepts only instances of '".
                $this->items_class."', '".(is_object($item) ? get_class($item) : gettype($item)).
                "' passed";
            throw new nc_record_exception($msg);
        }
        return true;
    }

    /**
     * Добавляет элемент в коллекцию
     * @param ArrayAccess $item
     * @param mixed|null $offset
     * @throws nc_record_exception
     * @return $this
     */
    public function add($item, $offset = null) {
        if ($this->check_item_class($item)) {
            if ($this->index_property) {
                $offset = $item->offsetGet($this->index_property);
            }

            if (is_null($offset)) {
                $this->items[] = $item;
            } else {
                $this->items[$offset] = $item;
            }
        }

        return $this;
    }

    /**
     * Добавляет несколько элементов в коллекцию
     * @param ArrayAccess[] $items
     * @return $this
     */
    public function add_items(array $items) {
        foreach ($items as $item) {
            $this->add($item);
        }
        return $this;
    }

    /**
     * Добавляет элемент в начало списка
     * @param ArrayAccess $item
     * @throws nc_record_exception
     * @return $this
     */
    public function unshift($item) {
        if ($this->check_item_class($item)) {
            if ($this->index_property) {
                $index = $item->offsetGet($this->index_property);
                $this->items = array_merge(array($index => $item), $this->items);
            }
            else {
                array_unshift($this->items, $item);
            }
        }
        return $this;
    }

    /**
     * Создаёт коллекцию с того же типа, что и текущая
     * @return static
     */
    protected function make_new_collection() {
        $class_name = get_class($this); // PHP 5.3 is SO MUCH cooler... :(
        /** @var nc_record_collection $result */
        $result = new $class_name;
        $result->set_items_class($this->get_items_class());
        return $result;
    }

    /**
     * Возвращает элементы коллекции в виде массива
     * @return array
     */
    public function to_array() {
        return $this->items;
    }

    /**
     * @param $value1
     * @param $value2
     * @param $operator
     * @return bool
     */
    protected function compare($value1, $value2, $operator) {
        switch ($operator) {
            case '==':  return $value1 == $value2;
            case '=':   return $value1 == $value2;
            case '!=':  return $value1 != $value2;
            case '===': return $value1 === $value2;
            case '!==': return $value1 !== $value2;
            case '>':   return $value1 > $value2;
            case '>=':  return $value1 >= $value2;
            case '<':   return $value1 < $value2;
            case '<=':  return $value1 <= $value2;
            case 'IN':  return in_array($value1, $value2);
            case 'in':  return in_array($value1, $value2);
        }
        return false;
    }

    /**
     * @param ArrayAccess $item
     * @param string $property_or_method
     * @param mixed $property_value
     * @param string $comparison_operator
     * @param array $method_arguments
     * @return bool
     */
    protected function item_matches_condition(ArrayAccess $item, $property_or_method, $property_value, $comparison_operator, array $method_arguments = null) {
        if ($item->offsetExists($property_or_method)) {
            if ($this->compare($item->offsetGet($property_or_method), $property_value, $comparison_operator)) {
                return true;
            }
        }
        else if (method_exists($item, $property_or_method)) {
            $method_result = $method_arguments ? call_user_func_array(array($item, $property_or_method), $method_arguments)
                                               : $item->$property_or_method();

            if ($this->compare($method_result, $property_value, $comparison_operator)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param ArrayAccess $item
     * @param array $conditions
     * @return bool
     */
    protected function item_matches_all(ArrayAccess $item, array $conditions) {
        foreach ($conditions as $condition) {
            $matched = $this->item_matches_condition($item,
                $condition[0],
                $condition[1],
                isset($condition[2]) ? $condition[2] : '==',
                isset($condition[3]) ? $condition[3] : null);
            if (!$matched) { return false; }
        }
        return true;
    }

    /**
     * Возвращает новую коллекцию с элементами, имеющими указанное значение.
     *
     *   $collection->where('site_id', 1)
     *      возвращает коллекцию с элементами, у которых offsetGet('site_id') равен 1
     *
     *   $collection->where('site_id', 1, '>')
     *      возвращает коллекцию с элементами, у которых offsetGet('site_id') больше 1
     *
     *   $collection->where('site_id', array(1, 2), 'IN')
     *      возвращает коллекцию с элементами, у которых offsetGet('site_id') равно 1 или 2
     *
     *   $collection->where('method_name', 'value', '==', array('arg1', 'arg2')
     *      возвращает коллекцию с элементами, у которых результат выполнения метода
     *      method_name('arg1', 'arg2') равен 'value'
     *
     * @param string $property_or_method
     * @param mixed $value
     * @param string $comparison_operator
     * @param array $method_arguments
     * @return static
     */
    public function where($property_or_method, $value, $comparison_operator = '==', array $method_arguments = null) {
        $result = $this->make_new_collection();
        foreach ($this->items as $item) {
            if ($this->item_matches_condition($item, $property_or_method, $value, $comparison_operator, $method_arguments)) {
                $result->add($item);
            }
        }
        return $result;
    }

    /**
     * Выборка элементов по нескольким условиям, каждое из которых должно быть
     * выполнено.
     *
     * Пример:
     *
     * — вернёт коллекцию с элементами, у которых (site_id==1 AND checked==1 AND comment_cont >= 1):
     *   $collection->where_all(array(
     *      array('site_id', 1),
     *      array('enabled', 1),
     *      array('comment_count', 1, '>=')
     *   );
     *
     * @param array $conditions     условия (каждый элемент массива — массив с условиями
     *                              в том же порядке, что и для метода where())
     * @return static
     */
    public function where_all(array $conditions) {
        $result = $this->make_new_collection();
        foreach ($this->items as $item) {
            if ($this->item_matches_all($item, $conditions)) {
                $result->add($item);
            }
        }
        return $result;
    }

    /**
     * Возвращает первый подходящий элемент коллекции
     * (или первый элемент, если параметры не заданы).
     * Если нет первого [подходящего] элемента, возвращает NULL.
     *
     * @param string $property_or_method
     * @param mixed $value
     * @param string $comparison_operator
     * @param array $method_arguments
     * @return ArrayAccess|null
     */
    public function first($property_or_method = null, $value = null, $comparison_operator = '==', array $method_arguments = null) {
        foreach ($this->items as $item) {
            if ($property_or_method == null || $this->item_matches_condition($item, $property_or_method, $value, $comparison_operator, $method_arguments)) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Возвращает первый подходящий под все условия элемент коллекции.
     *
     * @param array $conditions
     * @return ArrayAccess|null
     */
    public function first_where_all(array $conditions) {
        foreach ($this->items as $item) {
            if ($this->item_matches_all($item, $conditions)) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Возвращает TRUE, если хотя бы у одного элемента коллекции offset или
     * результат выполнения метода совпадает с заданным значением
     *
     * @param string $property_or_method
     * @param mixed $value
     * @param string $comparison_operator
     * @param array $method_arguments
     * @return bool
     */
    public function any($property_or_method, $value, $comparison_operator = '==', array $method_arguments = null) {
        foreach ($this->items as $item) {
            if ($this->item_matches_condition($item, $property_or_method, $value, $comparison_operator, $method_arguments)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Возвращает TRUE, если у всех элементов коллекции offset или
     * результат выполнения метода совпадает с заданным значением
     *
     * @param string $property_or_method
     * @param mixed $value
     * @param string $comparison_operator
     * @param array $method_arguments
     * @return bool
     */
    public function all($property_or_method, $value, $comparison_operator = '==', array $method_arguments = null) {
        foreach ($this->items as $item) {
            if ($this->item_matches_condition($item, $property_or_method, $value, $comparison_operator, $method_arguments)) {
                return false;
            }
        }
        return true;
    }

    /**
     *
     * @param integer|string $key
     * @return bool
     */
    public function has_key($key) {
        return isset($this->items[$key]);
    }

    /**
     * Выполнить действие с каждым элементом коллекции, вернуть результат в виде массива,
     * в котором каждый элемент соответствует результату применения метода к элементу
     * @internal param string $method_name method name
     * @internal param mixed $argument1 ,     (variable number of arguments)
     * @example  $all_values = $collection->each('get', 'some_property');
     * @return array
     */
    public function each() {
        $args = func_get_args();
        $method = array_shift($args);
        $num_args = count($args);

        $result = array();
        foreach ($this->items as $item) {
            // оптимизация для наиболее частых случаев: 1 аргумент, без аргументов, 2 аргумента
            if ($num_args === 1) {
                $result[] = $item->$method($args[0]);
            }
            elseif ($num_args === 0) {
                $result[] = $item->$method();
            }
            elseif ($num_args === 2) {
                $result[] = $item->$method($args[1], $args[2]);
            }
            else {
                $result[] = call_user_func_array(array($item, $method), $args);
            }
        }

        return $result;
    }

    /**
     * Общее количество элементов.
     * Некоторые коллекции могут иметь свойство total_count — общее количество записей,
     * которое может быть не равно загруженному в коллекцию числу записей.
     *
     * @return integer
     */
    public function get_total_count() {
        return ($this->total_count === null ? $this->count() : $this->total_count);
    }

    /**
     * @param $offset
     * @return number
     */
    public function sum($offset) {
        return array_sum($this->each('offsetGet', $offset));
    }

    /**
     * @param $offset
     * @return mixed
     */
    public function min($offset) {
        return min($this->each('offsetGet', $offset));
    }

    /**
     * @param $offset
     * @return mixed
     */
    public function max($offset) {
        return max($this->each('offsetGet', $offset));
    }

    /**
     * Возвращает список уникальных значений по свойству $offset записей.
     * @param $offset
     * @return array
     */
    public function distinct($offset) {
        return array_unique($this->each('offsetGet', $offset));
    }

    // -------------------------------------------------------------------------
    // Методы, относящиеся к загрузке из БД
    // -------------------------------------------------------------------------
    /**
     *
     * @return string
     */
    public function get_table_name() {
        $class = $this->items_class;
        if (!isset(self::$table_names[$class])) {
            // PHP 5.3 will make such things beautiful; I miss the 'static::' et cetera
            /** @var nc_record $dummy_item */
            $dummy_item = new $class;
            self::$table_names[$class] = $dummy_item->get_table_name();
        }
        return self::$table_names[$class];
    }

    /**
     *
     * @param string $query  SQL query
     *   Вместо имени таблицы можно использовать '%t%'
     * @throws nc_record_exception
     * @return $this
     */
    public function select_from_database($query) {
        $item_class = $this->get_items_class();
        $db = nc_db();

        if (strpos($query, "%t")) {
            $query = str_replace("%t%", $this->get_table_name(), $query);
        }

        $result = $db->get_results($query, ARRAY_A);

        if ($db->is_error) {
            throw new nc_record_exception("Cannot load items from the database: '$db->last_error'");
        }

        if (sizeof($result)) {
            foreach ($result as $row) {
                /** @var nc_record $item */
                $item = new $item_class();
                $item->set_values_from_database_result($row);
                $this->add($item);
            }
        }
        return $this;
    }


    /**
     * Костыли для PHP 5.2 (нет LSB)
     * Подбирает класс коллекции для указанного $items_class:
     * для каждого из родительских классов $item_class ищет соответствующий класс
     * коллекции с суффиксом "_collection".
     * Если подходящего класса не будет найдено, вернёт "nc_record_collection".
     *
     * Переделать после отказа от поддержки PHP 5.2 (текущий способ неэффективен)
     *
     * @param string $items_class
     * @return string
     */
    static protected function get_collection_class($items_class) {
        if (!isset(self::$collection_classes[$items_class])) {
            $collection_class = "nc_record_collection";
            do {
                if (class_exists("{$items_class}_collection")) {
                    $collection_class = "{$items_class}_collection";
                    break;
                }
            } while ($items_class = get_parent_class($items_class));

            self::$collection_classes[$items_class] = $collection_class;
        }
        return self::$collection_classes[$items_class];
    }

    /**
     *
     * @param string $items_class
     * @param boolean $force_reload
     * @param string $index_by присвоить ключам элементов коллекции значение свойства $index_property
     * @return static
     * @throws nc_record_exception
     */
    static public function load_all($items_class, $force_reload = false, $index_by = null) {
        if (!class_exists($items_class) || !is_subclass_of($items_class, "nc_record")) {
            throw new nc_record_exception("Wrong item class '$items_class' passed to nc_record_collection::load_all()");
        }

        if ($force_reload || !isset(self::$cache_all[$items_class])) {
            /** @var nc_record_collection $collection */
            $collection_class = self::get_collection_class($items_class);
            self::$cache_all[$items_class] = $collection = new $collection_class;
            $collection->set_items_class($items_class)
                       ->set_index_property($index_by)
                       ->select_from_database("SELECT * FROM `%t%`");
        }

        return self::$cache_all[$items_class];
    }

    /**
     *
     * @param string $items_class
     * @param string $query
     * @param string $index_by присвоить ключам элементов коллекции значение свойства $index_property
     * @return static
     * @throws nc_record_exception
     */
    static public function load($items_class, $query, $index_by = null) {
        if (!class_exists($items_class) || !is_subclass_of($items_class, "nc_record")) {
            throw new nc_record_exception("Wrong item class '$items_class' passed to nc_record_collection::load()");
        }

        /** @var nc_record_collection $collection */
        $collection_class = self::get_collection_class($items_class);
        $collection = new $collection_class;

        $collection->set_items_class($items_class)
                   ->set_index_property($index_by)
                   ->select_from_database($query);

        return $collection;
    }

    //////////////////////////// ArrayAccess Interface ///////////////////////////
    public function offsetGet($offset) {
        return (isset($this->items[$offset]) ? $this->items[$offset] : null);
    }

    public function offsetSet($offset, $item) {
        $this->add($offset, $item);
    }

    public function offsetExists($offset) {
        return isset($this->items[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->items[$offset]);
    }

    ////////////////////////////// Iterator Interface ////////////////////////////
    public function rewind() {
        reset($this->items);
    }

    public function current() {
        return current($this->items);
    }

    public function key() {
        return key($this->items);
    }

    public function next() {
        return next($this->items);
    }

    public function valid() {
        return (key($this->items) !== null);
    }

    ///////////////////////////// Countable Interface ////////////////////////////
    public function count() {
        return count($this->items);
    }

}
