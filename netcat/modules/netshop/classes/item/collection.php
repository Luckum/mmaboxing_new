<?php

class nc_netshop_item_collection extends nc_record_collection {

    protected $index_field = '';
    protected $items_class = 'nc_netshop_item';
//    protected $index_property = '_ItemKey';

    /**
     * Сумма по полю (с учётом количества из поля Qty)
     */
    public function get_field_sum($field_name) {
        return array_sum($this->each('get_field_total', $field_name));
    }

    /**
     * @param int $component_id
     * @param int $item_id
     * @return mixed
     */
    public function get_item_by_id($component_id, $item_id) {
        $key = "$component_id:$item_id";
        if ($this->index_property == '_ItemKey') {
            return $this->offsetGet($key);
        }
        else {
            return $this->first('_ItemKey', $key);
        }
    }

    /**
     * @param int $component_id
     * @param int $item_id
     */
    public function remove_item_by_id($component_id, $item_id) {
        $key = "$component_id:$item_id";
        if ($this->index_property == '_ItemKey') {
            unset($this->items[$key]);
        }
        else {
            foreach ($this->items as $index => $item) {
                if ($item['_ItemKey'] == $key) {
                    unset($this->items[$index]);
                    break;
                }
            }
        }
    }

    /**
     * SHA1-хэш, идентифицирующий содержимое корзины
     * @return string
     */
    public function get_hash() {
        $result = array();
        foreach ($this->items as $item) {
            $result[] = "$item[Class_ID]:$item[Message_ID]:$item[Qty]";
        }
        sort($result);
        return sha1(join(";", $result));
    }

}