<?php

/* $Id: part.php 6209 2012-02-10 10:28:29Z denis $ */

/**
 * 
 */
abstract class nc_search_area_part {

    protected $id;
        // ID сайта/раздела; может быть пустым, если указана страница (не раздел)
    protected $url = "";
  // путь, соответствующий области или доменное имя сайта, в том виде, как задан пользователем
    protected $is_excluded = false;
         // область исключена из поиска (начинается на "-")
    protected $include_children = true;
     // прямые потомки
    protected $include_descendants = false; // прямые и отдалённые потомки ("subX*")

    protected $document_table_name = "Search_Document";

    /**
     * @param array $values
     */
    public function __construct(array $values) {
        foreach ($values as $k => $v) {
            $this->$k = $v;
        }
    }

    /**
     * Сравнение URL с областью. Вернёт истину, если $url входит в область
     * ВНИМАНИЕ!!! НЕ УЧИТЫВАЕТСЯ СВОЙСТВО $this->is_excluded!
     * @param string $url
     * @return boolean
     */
    abstract public function matches($url);

    /**
     * Возвращает полный URL, соответствующий области. allsites или sub, который задан
     * через указание пути без имени сайта, может вернуть несколько элементов в массиве
     * @return array
     */
    abstract public function get_urls();

    /**
     * Возвращает SQL-условие
     * @return string
     */
    abstract public function get_sql_condition();

    /**
     * Возвращает условие для запроса в терминах языка запросов (i.e., lucene query)
     * @return string
     */
    abstract public function get_field_condition();

    /**
     * Возвращает «человекопонятное» описание области
     */
    abstract public function get_description();

    /**
     * Возвращает соответствующее области текстовое описание; для разделов и сайтов
     * в виде subXX/siteXX
     * @return string
     */
    public function to_string() {
        return $this->get_prefix().$this->get_string().$this->get_suffix();
    }

    abstract public function get_string();

    protected function get_prefix() {
        return ($this->is_excluded ? "-" : "");
    }

    protected function get_suffix() {
        return "";
    }

    public function is_excluded() {
        return $this->is_excluded;
    }

}
