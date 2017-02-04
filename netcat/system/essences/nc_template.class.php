<?php

/* $Id: nc_template.class.php 5960 2012-01-17 17:25:34Z denis $ */
if (!class_exists("nc_System")) die("Unable to load file.");

class nc_Template extends nc_Essence {

    const PARTIALS_DIR = 'partials';
    const TEMPLATE_EXT = 'html';

    protected $db;
    protected $table;

    /**
     * Constructor function
     */
    public function __construct() {
        parent::__construct();

        $this->db    = $this->core->db;
        $this->table = nc_db_table::make('Template');
    }

    public function convert_subvariables($template_env) {
        // load system table fields
        $table_fields = $this->core->get_system_table_fields($this->essence);
        // count
        $counted_fileds = count($table_fields);

        // %FIELD replace with inherited template field value
        for ($i = 0; $i < $counted_fileds; $i++) {
            $template_env["Header"] = str_replace("%".$table_fields[$i]['name'], $template_env[$table_fields[$i]['name']], $template_env["Header"]);
            $template_env["Footer"] = str_replace("%".$table_fields[$i]['name'], $template_env[$table_fields[$i]['name']], $template_env["Footer"]);
        }

        return $template_env;
    }

    protected function inherit($template_env) {
        global $perm, $AUTH_USER_ID, $templatePreview;

        // Блок для предпросмотра макетов дизайна
        $magic_gpc = get_magic_quotes_gpc();
        if ($template_env["Template_ID"] == $templatePreview && !empty($_SESSION["PreviewTemplate"][$templatePreview])) {
            foreach ($_SESSION["PreviewTemplate"][$templatePreview] as $key => $value) {
                $template_env[$key] = $magic_gpc ? stripslashes($value) : $value;
            }
        }

        $parent_template = $template_env["Parent_Template_ID"];

        if ($parent_template) {
            $parent_template_env = $this->get_by_id($parent_template);

            // Если мы вызываем предпросмотр для макета, а он используется в качестве родительского.
            if ($parent_template_env["Template_ID"] == $templatePreview && !empty($_SESSION["PreviewTemplate"][$templatePreview])) {
                foreach ($_SESSION["PreviewTemplate"][$templatePreview] as $key => $value) {
                    $parent_template_env[$key] = $magic_gpc ? stripslashes($value) : $value;
                }
            }

            $parent_template = $template_env["Parent_Template_ID"];

            if (!$template_env["Header"]) {
                $template_env["Header"] = $parent_template_env["Header"];
            } else {
                if ($parent_template_env["Header"]) {
                    $template_env["Header"] = str_replace("%Header", $parent_template_env["Header"], $template_env["Header"]);
                }
            }
            if (!$template_env["Footer"]) {
                $template_env["Footer"] = $parent_template_env["Footer"];
            } else {
                if ($parent_template_env["Footer"]) {
                    $template_env["Footer"] = str_replace("%Footer", $parent_template_env["Footer"], $template_env["Footer"]);
                }
            }
            $template_env["Settings"] = $parent_template_env["Settings"].$template_env["Settings"];

            $template_env = $this->inherit_system_fields($this->essence, $parent_template_env, $template_env);
            $parent_template = $parent_template_env["Parent_Template_ID"];
        }

        return $template_env;
    }

    public function update($template_id, $params = array()) {
        $db = $this->db;

        $template_id = intval($template_id);
        if (!$template_id || !is_array($params)) {
            return false;
        }

        $query = array();
        foreach ($params as $k => $v) {
            $query[] = "`".$k."` = '".(preg_match('/validate_regexp/', $v) ? $db->prepare($v) : $db->escape($v))."'";
        }

        if (!empty($query)) {
            $db->query("UPDATE `Template` SET ".join(', ', $query)." WHERE `Template_ID` = '".$template_id."' ");
            if ($db->is_error)
                    throw new nc_Exception_DB_Error($db->last_query, $db->last_error);
        }

        //unset($this->data[$template_id]);
        $this->data = array();
        return true;
    }

    public function get_parent($id, $all = 0) {
        $id = intval($id);
        $ret = array();
        $parent_id = $this->db->get_var("SELECT `Parent_Template_ID` FROM `Template` WHERE `Template_ID` = '".$id."' ");

        if (!$all) return intval($parent_id);

        if ($parent_id) {
            $ret[] = $parent_id;
            $ret = array_merge($ret, $this->get_parent($parent_id, 1));
        }

        return $ret;
    }

    public function get_childs($id) {
        $ret = array();
        $childs = $this->db->get_col("SELECT `Template_ID` FROM `Template` WHERE `Parent_Template_ID` = '".intval($id)."'");

        if (!empty($childs))
                foreach ($childs as $v) {
                $ret[] = $v;
                $ret = array_merge($ret, $this->get_childs($v));
            }


        return $ret;
    }

    /**
     * Возвращает абсолютный путь к папке с дополнительными шаблонами (partials)
     * @param  integer $template_id
     * @return string
     */
    public function get_partials_path($template_id, $partial = null) {
        if ($partial) {
            $partial .= '.' . self::TEMPLATE_EXT;
        }
        return $this->core->TEMPLATE_FOLDER . $template_id . '/' . self::PARTIALS_DIR . '/' . $partial;
    }

    /**
     * Возвращает true если макет имеет дополнительные шаблоны (partials) (только при File_Mode = 1)
     * @param  integer $template_id
     * @param  string  $name
     * @return boolean
     */
    public function has_partial($template_id, $name = null) {
        $partials = $this->get_template_partials($template_id);

        return $name ? isset($partials[$name]) : count($partials);
    }

    /**
     * Возвращает список дополнительных шаблонов для заданного макета дизайна
     * @param  integer $template_id
     * @return array
     */
    public function get_template_partials($template_id) {
        static $partials = array();

        if (!isset($partials[$template_id])) {
            $partials[$template_id] = array();
            $partials_folder = $this->get_partials_path($template_id);
            if (file_exists($partials_folder)) {
                $files = scandir($partials_folder);
                foreach ($files as $file) {
                    if (is_file($partials_folder . $file)) {
                        $info = pathinfo($partials_folder . $file);
                        if ($info['extension'] == self::TEMPLATE_EXT) {
                            $name = $info['filename'];
                            $partials[$template_id][$name] = $name;
                        }
                    }
                }
            }
        }

        return $partials[$template_id];
    }



}