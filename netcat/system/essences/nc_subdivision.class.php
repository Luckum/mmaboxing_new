<?php

class nc_Subdivision extends nc_Essence {

    protected $db;
    protected $real_value;
    protected $_level_count;
    protected $_parent_tree;

    /**
     * Constructor function
     */
    public function __construct() {
        // load parent constructor
        parent::__construct();

        // system superior object
        $nc_core = nc_Core::get_object();
        // system db object
        if (is_object($nc_core->db)) {
            $this->db = $nc_core->db;
        }

        $page = nc_Core::get_object()->page;
        $lm_type = $page->get_field_name('last_modified_type');
        $sm_field = $page->get_field_name('sitemap_include');
        $sm_change_field = $page->get_field_name('sitemap_changefreq');
        $sm_priority_field = $page->get_field_name('sitemap_priority');
        $lang_field = $page->get_field_name('language');
        $this->real_value = array('Template_ID', 'DisallowIndexing', $lm_type, $sm_field, $sm_change_field, $sm_priority_field,
            $lang_field, 'Read_Access_ID', 'Write_Access_ID', 'Edit_Access_ID', 'Delete_Access_ID', 'Checked_Access_ID', 'Moderation_ID',
            'Cache_Access_ID', 'Cache_Lifetime');
    }

    /**
     * @param int $id
     * @param string $item
     * @param bool $reset
     * @return null|string
     * @throws Exception
     */
    public function get_by_id($id, $item = "", $reset = false) {
        $res = array();
        if (isset($this->data[$id])) {
            $res = $this->data[$id];
        }

        if (empty($res)) {
            $res = $this->db->get_row("SELECT * FROM `Subdivision` WHERE `Subdivision_ID` = '" . intval($id) . "'", ARRAY_A);

            if (empty($res)) {
                //return false;
                throw new Exception("Subdivision with id  " . $id . " does not exist");
            }

            $this->data[$id] = $res;

            foreach ($this->real_value as $v) {
                $this->data[$id]['_db_' . $v] = $this->data[$id][$v];
            }
            $this->data[$id]['_nc_final'] = 0;
        }

        if (!$this->data[$id]['_nc_final']) {
            $this->data[$id] = $this->convert_system_vars($this->data[$id]);
            $this->data[$id] = $this->inherit($this->data[$id]);
            $this->data[$id]['_nc_final'] = 1;
        }

        if ($item) {
            return array_key_exists($item, $this->data[$id]) ? $this->data[$id][$item] : "";
        }

        return $this->data[$id];
    }

    /**
     * @param int $id
     * @param bool $reset
     * @return bool|null|string
     */
    public function set_current_by_id($id, $reset = false) {
        $nc_core = nc_Core::get_object();
        try {
            $this->current = $this->get_by_id($id, "", $reset);
        } catch (Exception $e) {
            $this->current = $this->get_by_id($nc_core->catalogue->get_current("Title_Sub_ID"));
        }

        // return result
        return $this->current;
    }

    /**
     * Получить информацию о разделе по url
     *
     * @param string $uri
     * @param int $catalogue номер сайта, по умолчанию определяется по домену
     * @param string $item требуемый параметр, если не задан - функция возврщает массив
     * @param bool $remove_date
     * @param bool $return_null_when_not_found если false и раздел не найден, возвращает информацию для раздела 404, иначе - null
     * @return mixed
     */
    public function get_by_uri($uri, $catalogue = 0, $item = '', $remove_date = true, $return_null_when_not_found = false) {
        $nc_core = nc_Core::get_object();
        // определение сайта
        if (!$catalogue) {
            $catalogue = $nc_core->catalogue->get_by_host_name($_SERVER['HTTP_HOST']);
            $catalogue = $catalogue['Catalogue_ID'];
        }

        $uri = rtrim(nc_substr($uri, 0, strrpos($uri, '/')), '/') . '/';

        if ($remove_date) {
            // find date in url
            $uri = preg_replace('|/\d{4}/(?:\d{2}/)?(?:\d{2}/)?$|', '/', $uri);
        }

        // титульная страница
        if ($uri == "/" || $uri == "") {
            $res = $this->get_by_id($nc_core->catalogue->get_by_id($catalogue, "Title_Sub_ID"));
        }

        // поиск в кэше
        if (empty($res) && !empty($this->data)) {
            foreach ($this->data as $id => $values) {
                if ($values['Catalogue_ID'] == $catalogue && $values['Hidden_URL'] == $uri) {
                    $res = $this->data[$id];
                }
            }
        }

        // из базы
        if (empty($res)) {
            $res = $this->db->get_row("SELECT * FROM `Subdivision`
                                        WHERE `Catalogue_ID` = '" . intval($catalogue) . "'
                                          AND `Hidden_URL` = '" . $this->db->escape($uri) . "'", ARRAY_A);
            if ($res) {
                $res['_nc_final'] = 0;
                $this->data[$res['Subdivision_ID']] = $res;
            }
            else if (!$return_null_when_not_found) {
                $res = $this->get_by_id($nc_core->catalogue->get_current("E404_Sub_ID"));
            }
            else {
                return null;
            }
        }

        // processing system fields, inherit
        if (!$res['_nc_final']) {
            $res = $this->get_by_id($res['Subdivision_ID']);
        }

        if ($item) {
            return array_key_exists($item, $res) ? $res[$item] : "";
        }

        return $res;
    }

    /**
     * Get subdivision data by the URI parameter
     *
     * @param URI string
     * @param bool use returned value as current catalogue data
     * @param bool reset stored data in the static variable
     *
     * @return subdivision data associative array
     */
    public function set_current_by_uri($reset = false) {
        // system superior object
        $nc_core = nc_Core::get_object();
        $catalogue = $nc_core->catalogue->get_current("Catalogue_ID");

        // URI
        $uri = $nc_core->url->get_parsed_url('path');

        $this->current = $this->get_by_uri($uri, $catalogue);
        return $this->current;
    }

    public function validate_hidden_url($url) {
        // validate Hidden_URL
        return nc_preg_match("/^[\/_a-z" . NETCAT_RUALPHABET . "0-9-]+$/i", $url);
    }

    protected function inherit($sub_env) {
        // system superior object
        $nc_core = nc_Core::get_object();
        $sub = $sub_env["Subdivision_ID"];

        $lm = $nc_core->page->get_field_name('last_modified_type');

        $inherited_params = array('Template_ID', 'TemplateSettings', 'Read_Access_ID', 'Write_Access_ID', 'Edit_Access_ID', 'Checked_Access_ID',
            'Delete_Access_ID', 'Subscribe_Access_ID', 'Moderation_ID', $lm,
            $nc_core->page->get_field_name('sitemap_priority'), $nc_core->page->get_field_name('language'));
        $inherited_params_minus = array($nc_core->page->get_field_name('sitemap_include'), $nc_core->page->get_field_name('sitemap_changefreq'),
            'DisallowIndexing');

        if ($nc_core->modules->get_by_keyword("cache")) {
            $inherited_params[] = 'Cache_Access_ID';
            $inherited_params[] = 'Cache_Lifetime';
        }

        $parent_sub = $sub_env["Parent_Sub_ID"];

        if ($parent_sub) {
            $parent_sub_env = $this->get_by_id($parent_sub);
        }
        else {
            $parent_sub_env = $nc_core->catalogue->get_by_id($sub_env["Catalogue_ID"]);
            $sub_env = $this->inherit_system_fields("Catalogue", $parent_sub_env, $sub_env);
            $parent_sub_env["Subdivision_Name"] = $parent_sub_env["Catalogue_Name"];
            $parent_sub_env["Hidden_URL"] = "/";
        }

        foreach ($inherited_params as $v) {
            if ($parent_sub_env[$v] && !isset($sub_env['_db_inherit_' . $v])) {
                $sub_env['_db_inherit_' . $v] = $parent_sub_env[$v];
            }
            if (!$sub_env[$v]) {
                $sub_env[$v] = $parent_sub_env[$v];
            }
        }
        foreach ($inherited_params_minus as $v) {
            if ($parent_sub_env[$v] != -1 && !isset($sub_env['_db_inherit_' . $v])) {
                $sub_env['_db_inherit_' . $v] = $parent_sub_env[$v];
            }
            if ($sub_env[$v] == -1) {
                $sub_env[$v] = $parent_sub_env[$v];
            }
        }

        $sub_env = $this->inherit_system_fields($this->essence, $parent_sub_env, $sub_env);


        $this->_parent_tree[$sub] = $parent_sub ? $this->_parent_tree[$parent_sub] : array($parent_sub_env);
        $this->_level_count[$sub] = $this->_level_count[$parent_sub] + 1;

        array_unshift($this->_parent_tree[$sub], $sub_env);

        return $sub_env;
    }

    public function get_parent_tree($sub) {
        // get data
        $this->get_by_id($sub);
        // check
        if (is_array($this->_parent_tree[$sub]) && !empty($this->_parent_tree[$sub])) {
            return $this->_parent_tree[$sub];
        }
        // parent sub array not found
        return false;
    }

    public function get_level_count($sub) {
        // get data
        $this->get_by_id($sub);
        // check
        if (isset($this->_level_count[$sub])) {
            return $this->_level_count[$sub];
        }
        // level count array not found
        return false;
    }

    public function validate_english_name($str) {
        // Check string length: database scheme stores up to 64 characters
        if (mb_strlen($str) > 64) {
            return 0;
        }
        // validate Hidden_URL
        return nc_preg_match("/^[a-z" . NETCAT_RUALPHABET . "0-9-]+$/i", $str);
    }

    public function get_lang($id) {
        return $this->get_by_id($id, nc_Core::get_object()->page->get_language_field());
    }

    public function update($id, $params = array()) {
        $db = $this->db;

        $id = intval($id);
        if (!$id || !is_array($params)) {
            return false;
        }

        $query = array();
        foreach ($params as $k => $v) {
            $query[] = "`" . $db->escape($k) . "` = '" . $db->escape($v) . "'";
        }

        if (!empty($query)) {
            $this->core->event->execute("updateSubdivisionPrep", $this->get_by_id($id, 'Catalogue_ID'), $id);

            $db->query("UPDATE `Subdivision` SET " . join(', ', $query) . " WHERE `Subdivision_ID` = '" . $id . "' ");
            if ($db->is_error) {
                throw new nc_Exception_DB_Error($db->last_query, $db->last_error);
            }

            $this->core->event->execute("updateSubdivision", $this->get_by_id($id, 'Catalogue_ID'), $id);
        }

        $this->data = array();
        return true;
    }

}
