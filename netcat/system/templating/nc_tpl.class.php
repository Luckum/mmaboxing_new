<?php

class nc_tpl {

    /** @var nc_fields  */
    public $fields = null;
    /** @var int */
    public $catalogue_id = null;
    /** @var string  */
    public $relative_path = null;
    /** @var string  */
    public $absolute_path = null;
    /** @var int */
    public $id = null;
    /** @var int */
    public $count_parent = null;
    /** @var string  */
    public $extension = '.html';
    /** @var nc_tpl|null */
    public $child = null;
    /** @var string  */
    public $type = null;
    /** @var nc_db */
    public $db = null;
    /** @var string  */
    public $path_to_root_folder = null;
    /** @var bool */
    protected $use_catalogue = false;

    /**
     * @param string $path
     * @param nc_Db $db
     * @param string $type
     */
    public function __construct($path, nc_Db $db, $type = null) {
        $this->path_to_root_folder = $path;
        $this->db = $db;
        $this->type = $type;

        $this->catalogue_id = nc_core::get_object()->catalogue->id();
    }

    /**
     * @param int $id
     * @param string $type
     * @param string $relative_path
     */
    public function load($id, $type, $relative_path = null) {
        // check path
        #if (!$id) {
        #   nc_print_status(NETCAT_TEMPLATE_FILE_NOT_FOUND, 'error');
        #   exit;
        #}
        $this->id = (int)$id;
        $this->type = $type;
        $this->relative_path = $relative_path ? $relative_path : $this->select_relative_path();
        // check path
        #if (!$this->relative_path) {
        #   nc_print_status(NETCAT_TEMPLATE_FILE_NOT_FOUND, 'error');
        #   exit;
        #}
        $this->absolute_path = $this->get_absolute_path_to_template();
        $this->count_parent = $this->count_parent_template();

        $this->fields = new nc_fields($this);
    }

    /**
     * Использовать шаблоны "привязанные" к сайту
     *
     * @param int|boolean $catalogue_id Идентификатор сайта | true - текущий сайт
     * @return null
     */
    public function use_catalogue($catalogue_id = true) {
        if ($catalogue_id) {
            if ($catalogue_id !== true) {
                $this->catalogue_id = $catalogue_id;
            }
            $this->absolute_path = $this->get_absolute_path_to_template(true);
        }
    }

    /**
     * @return bool|string|null
     */
    private function select_relative_path() {
        if (!($this->type && $this->id)) {
            return false;
        }
        $SQL = "SELECT File_Path
                  FROM {$this->type}
                 WHERE {$this->type}_ID = {$this->id}";
        return $this->db->get_var($SQL);
    }

    /**
     * @param bool $make_catalogue_dir
     * @return string
     */
    private function get_absolute_path_to_template($make_catalogue_dir = false) {
        $abs_path = nc_standardize_path_to_folder($this->path_to_root_folder . '/' . $this->relative_path);

        $module_editor = new nc_module_editor();
        if ($this->catalogue_id && array_key_exists($this->type, $module_editor->get_module_types())) {
            if (!file_exists($abs_path . $this->catalogue_id)) {
                if (!$make_catalogue_dir) {
                    return $abs_path;
                }
                mkdir($abs_path . $this->catalogue_id);
            }

            $abs_path .= $this->catalogue_id . '/';
        }

        return $abs_path;
    }

    /**
     * @return int
     */
    private function count_parent_template() {
        return count(array_diff(explode('/', trim($this->relative_path, '/')), array('')));
    }

    /**
     * @param int $id
     */
    public function load_child($id) {
        $this->child = new nc_tpl(nc_standardize_path_to_folder($this->path_to_root_folder), $this->db);
        $this->child->load($id, $this->type, nc_standardize_path_to_folder($this->relative_path . '/' . $id));
    }

    /**
     * @return bool
     */
    public function update_file_path_and_mode() {
        if (!($this->type && $this->id && $this->relative_path)) {
            return false;
        }
        $SQL = "UPDATE {$this->type}
                   SET File_Path = '{$this->relative_path}',
                       File_Mode = 1
                 WHERE {$this->type}_ID = {$this->id}";
        return $this->db->query($SQL);
    }

    /**
     * @return int
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * @param string $path
     * @return bool
     */
    public function delete_template_file_and_folder($path = '') {
        // path
        $path = ($path ? $path : $this->absolute_path);
        // check path
        if (!is_dir($path)) {
            return false;
        }

        // error when trying to delete template folder
        if (nc_standardize_path_to_folder($this->path_to_root_folder) == $path) {
            // warning message
            nc_print_status(sprintf(NETCAT_TEMPLATE_DIR_DELETE_ERROR, $path), 'error');
            return false;
        }

        // variables
        $files = nc_double_array_shift(scandir($path));
        $directory = nc_standardize_path_to_folder($path);

        foreach ($files as $file) {
            $full_path = $directory . $file;
            // check file existance
            if (!file_exists($full_path)) {
                continue;
            }
            // file / dir
            if (is_dir($full_path)) {
                $this->delete_template_file_and_folder($full_path);
            }
            else {
                // delete file
                unlink($full_path);
            }
        }
        // delete dir
        if (is_dir($directory)) {
            rmdir($directory);
        }

        return true;
    }
}