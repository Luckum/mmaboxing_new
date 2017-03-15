<?php

/* $Id: sub.php 8366 2012-11-07 16:30:14Z aix $ */

/**
 * 
 */
class nc_search_area_sub extends nc_search_area_part {

    protected $sites = array();
    protected $path = "";
    static protected $date_regexp = "#/(?:19|20)\d{2}(?:/(?:0\d|11|12))?(?:/(?:[012]\d|3[01]))/?#";

    /**
     *
     * @return boolean
     */
    public function has_site() {
        // the path is unambiguous if:
        //  (a) subdivision ID is set
        //  (b) $this->url contains full url, i.e. scheme + domain name + path
        return (bool) ($this->id || strpos($this->url, "://"));
    }

    /**
     *
     * @param array $sites
     */
    public function set_sites(array $sites) {
        $this->sites = $sites;
    }

    /**
     * Заложена возможность использовать неполные пути на нескольких сайтах.
     * Например, правило "allsites /news/" будет соответствовать множеству разделов
     * "http://site1.com/news/", "http://site2.com/news/" и т.д.
     * @return array
     */
    public function get_sites() {
        if (!$this->sites) {
            if ($this->id) { // get site by subdivision ID
                $db = nc_Core::get_object()->db;
                $site_id = $db->get_var("SELECT `Catalogue_ID` FROM `Subdivision` WHERE `Subdivision_ID`=".intval($this->id));
                $this->sites[] = new nc_search_area_site(array("id" => $site_id));
            } elseif (strpos($this->url, "://")) { // probably a string with "http://"
                $this->sites[] = new nc_search_area_site(array("url" => parse_url($this->url, PHP_URL_HOST)));
            } else {
                $this->sites[] = new nc_search_area_allsites(array());
            }
        }
        return $this->sites;
    }

    /**
     *
     * @return integer
     */
    protected function get_id() {
        if ($this->id === null) {
            $site_id = null;

            if (strpos($this->url, "://")) { // hostname, huh?!
                $host = parse_url($this->url, PHP_URL_HOST);
                try {
                    $site_settings = nc_Core::get_object()->catalogue->get_by_host_name($host);
                    $site_id = $site_settings['Catalogue_ID'];
                } catch (Exception $e) {
                }
            }

            $resolved_path = nc_resolve_url($this->url, 'GET', $site_id);

            if (isset($resolved_path['folder_id'])) {
                $this->id = $resolved_path['folder_id'];
            }
            else {
                $this->id = false;
            }
        }

        return $this->id;
    }

    /**
     *
     * @throws nc_search_exception
     * @return string
     */
    protected function get_path() {
        if (!$this->path) {
            if ($this->url) {
                if (strpos($this->url, "://")) {
                    $this->path = parse_url($this->url, PHP_URL_PATH);
                } else {
                    $this->path = $this->url;
                }
            } elseif ($this->id) {
                $this->path = nc_folder_path($this->id);
            } else {
                throw new nc_search_exception("Wrong subdivision area: neither ID nor URL specified");
            }
        }
        return $this->path;
    }

    /**
     * Получить полный URL (с http://, именем домена)
     */
    public function get_urls() {
        $path = ltrim($this->get_path(), '/');
        $urls = array();
        foreach ($this->get_sites() as $site) {
            foreach ($site->get_urls() as $site_url) {
                $urls[] = $site_url.$path;
            }
        }
        return $urls;
    }

    /**
     * ВНИМАНИЕ! Пути регистрозависимы!
     * @param string $url
     * @return boolean
     */
    public function matches($url) {
        $domain_matched = false;
        foreach ($this->get_sites() as $site) {
            if ($site->matches($url) && !$site->is_excluded()) {
                $domain_matched = true;
                break;
            }
        }
        if (!$domain_matched) {
            return false;
        }

        $area_path = $this->get_path();
        $checked_path = parse_url($url, PHP_URL_PATH);

        // убрать фрагменты дат из проверяемого пути в случае, если правило задано
        // в виде идентификатора раздела ("sub123")
        if (!$this->url) {
            $checked_path = preg_replace(self::$date_regexp, "/", $checked_path);
        }

        // нелатинские пути
        $checked_path = urldecode($checked_path);

        // Возможно три варианта:
        // (а) Только эта страница
        if (!$this->include_children && !$this->include_descendants) {
            return ($checked_path == $area_path);
        }
        // (б) Этот раздел и прямые потомки (объекты в разделе)
        if ($this->include_children && !$this->include_descendants) {
            if (strpos($checked_path, $area_path) !== 0) {
                return false;
            }
            $remainder = substr($checked_path, strlen($area_path));
            return (strpos($remainder, "/") === false);
        }
        // (в) Этот раздел и все потомки
        return (strpos($checked_path, $area_path) === 0);
    }

    /**
     *
     * @return string
     */
    public function get_string() {
        return ($this->get_id() ? "sub{$this->get_id()}" : $this->path);
    }

    /**
     *
     * @return string
     */
    protected function get_suffix() {
        if (!$this->include_children) {
            return ".";
        }
        if ($this->include_descendants) {
            return "*";
        }
        return "";
    }

    /**
     *
     */
    public function get_sql_condition() {
        $table = "`{$this->document_table_name}`";
        // (а) Только эта страница
        if (!$this->include_children && !$this->include_descendants) {
            $q = $this->get_path_sql_condition();
        }
        // (б) Этот раздел и все потомки
        elseif ($this->include_descendants) {
            $q = $this->is_sub_root() ?
                    "FIND_IN_SET('sub{$this->get_id()}', $table.`Ancestors`)" :
                    $this->get_path_sql_condition('LIKE', '%');
        }
        // (в) Этот раздел и прямые потомки (объекты в разделе)
        else {
            $q = $this->is_sub_root() ?
                    "$table.`Subdivision_ID` = {$this->get_id()}" :
                     $this->get_path_sql_condition("RLIKE", "[^/]*$");
        }
        return $q;
    }

    /**
     * Является ли указанный в правиле путь «корнем» раздела?
     * Не являются «„корнем“ раздела»:
     *  — пути, не находящиеся под управлением Netcat
     *  — пути с указаниями фрагментов дат
     */
    protected function is_sub_root() {
        if (!$this->url) {
            return true;
        }        // it must be "subXXX" then
        if (!$this->get_id()) {
            return false;
        }  // not a Netcat-managed path
        // has ID, has URL; check for date fragments
        return!preg_match(self::$date_regexp, $this->get_path());
    }

    /**
     *
     */
    protected function get_path_sql_condition($operator = '=', $template = '') {
        $site_cond = array();
        foreach ($this->get_sites() as $site) {
            $site_cond[] = $site->get_sql_condition();
        }
        $q = ($site_cond ? "(".join(" OR ", $site_cond).")" : "1");
        $q .= " AND `{$this->document_table_name}`.`Path` $operator '" .
              nc_search_util::db_escape($this->get_path()) . $template . "'";
        return $q;
    }

    /**
     *
     */
    public function get_field_condition() {
        if ($this->include_descendants) { // Этот раздел и все потомки
            return "ancestor:sub".$this->get_id();
        }
        // Только эта страница или страница и прямые потомки
        return "sub_id:".$this->get_id();
    }

    /**
     *
     */
    public function get_description() {
        $sub_id = $this->get_id();
        $link = "$GLOBALS[ADMIN_PATH]#subdivision.info($sub_id)";

        if ($sub_id) {
            try {
                $name = nc_Core::get_object()->subdivision->get_by_id($sub_id, "Subdivision_Name");
            } catch (Exception $e) {
                $name = "sub$sub_id";
            }
        } else {
            $name = $link = $this->url;
        }

        // (а) Только эта страница
        if (!$this->include_children && !$this->include_descendants) {
            $str = NETCAT_MODULE_SEARCH_ADMIN_RULE_AREA_DESCRIPTION_SUB_ONLY;
        }
        // (б) Этот раздел и все потомки
        elseif ($this->include_descendants) {
            $str = NETCAT_MODULE_SEARCH_ADMIN_RULE_AREA_DESCRIPTION_SUB_DESCENDANTS;
        }
        // (в) Этот раздел и прямые потомки (объекты в разделе)
        else {
            $str = NETCAT_MODULE_SEARCH_ADMIN_RULE_AREA_DESCRIPTION_SUB_CHILDREN;
        }

        return sprintf($str, $link, $name);
    }

}
