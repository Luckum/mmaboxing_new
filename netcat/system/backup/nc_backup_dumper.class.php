<?php


class nc_backup_dumper {

    //-------------------------------------------------------------------------

    protected $dump_info   = array();
    protected $dump_path   = '';

    protected $backup;
    protected $current_object;
    protected $current_object_stack = array();

    protected $export_file;
    protected $export_settings = array(
        'compress' => true,
    );

    protected $import_result = array();
    protected $import_settings = array(
        'save_ids' => true,
    );

    //-------------------------------------------------------------------------

    public function __construct() {
        @set_time_limit(0);
        $memory_limit = (int) ini_get('memory_limit');
        $size         = strtoupper(substr($memory_limit, -1));

        if ($size == 'M' && $memory_limit < 512) {
            @ini_set('memory_limit', 512 . 'M');
        }

        $this->backup = nc_core('backup');
    }

    //-------------------------------------------------------------------------

    public function call_event($event, $args, &$replace_result = null) {
        if ($this->current_object) {
            $result = $this->current_object->call_event($event, $args);

            if ($replace_result && $result !== null) {
                $replace_result = $result;
            }
        }
    }

    //-------------------------------------------------------------------------

    public function set_current_object($object) {
        $this->current_object_stack[] = $object;
        $this->current_object = $object;
    }

    //-------------------------------------------------------------------------

    public function forgot_current_object() {
        $this->current_object = array_pop($this->current_object_stack);
    }

    //-------------------------------------------------------------------------

    public function set_dump_path($path) {
        $this->dump_path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        return $this->get_dump_path();
    }

    //-------------------------------------------------------------------------

    public function get_dump_path($filename = '') {
        return $this->dump_path . $filename;
    }

    //-------------------------------------------------------------------------

    public function set_dump_info($key, $value) {
        $this->dump_info[$key] = $value;
    }

    //-------------------------------------------------------------------------

    public function get_dump_info($key = null, $key2 = null) {
        if (!$key) {
            return $this->dump_info;
        }

        $result = isset($this->dump_info[$key]) ? $this->dump_info[$key] : null;

        if ($key2 && $result) {
            $result = isset($result[$key2]) ? $result[$key2] : null;
        }

        return $result;
    }

    //-------------------------------------------------------------------------

    public function load_dump_info() {
        $info_file = $this->get_dump_path('info.php');
        if (file_exists($info_file)) {
            $this->dump_info = include $info_file;
        }
        return $this->dump_info;
    }

    //-------------------------------------------------------------------------

    public function save_dump_info() {
        if (!$this->dump_info) {
            return;
        }

        $file    = $this->get_dump_path('info.php');
        $content = '<?php return ' . var_export($this->dump_info, true) . ';';

        return file_put_contents($file, $content);
    }

    //-------------------------------------------------------------------------

    public function set_dict($field, $key, $value = null) {
        if ($value === null) {
            $value = $key;
        }
        if (is_numeric($key)) $key = (int)$key;
        if (is_numeric($value)) $value = (int)$value;

        $this->dump_info['dict'][$field][$key] = $value;
    }

    //-------------------------------------------------------------------------

    public function get_dict($field = null, $key = null, $default = null) {
        static $dict;

        if ($dict === null) {
            $dict =& $this->dump_info['dict'];
        }

        if ($field === null) {
            return $dict;
        }

        if ($key === null) {
            return isset($dict[$field]) ? $dict[$field] : array();
        }

        return isset($dict[$field]) && isset($dict[$field][$key]) ? $dict[$field][$key] : $default;
    }

    //-------------------------------------------------------------------------

    public function register_dict_field($fields) {
        if (func_num_args() > 1) {
            $fields = func_get_args();
        } else {
            $fields = (array) $fields;
        }

        foreach ($fields as $alias => $field) {
            if (is_numeric($alias)) {
                $alias = $field;
            }
            $this->dump_info['dict_fields'][$alias] = $field;
        }
    }

    //-------------------------------------------------------------------------

    public function search_dict_fields(&$row) {
        foreach ($this->dump_info['dict_fields'] as $field => $true) {

            if (isset($row[$field])) {
                $this->set_dict($field, $row[$field]);
            }
        }
    }

    /**************************************************************************
        FILE OPERATIONS
    **************************************************************************/

    public function copy_files($src, $dst, $replace = false, &$result = array(), $move = false) {

        if (!$result) {
            $result = array(
                'copied'   => 0,
                'skipped'  => 0,
                'replaced' => 0,
            );
        }

        if ($replace && file_exists($dst)) {
            $result['replaced'] ++;
            remove_dir($dst);
        }

        if (is_dir($src)) {
            if (!file_exists($dst)) {
                mkdir($dst);
            }
            $files = scandir($src);
            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    $this->copy_files($src . DIRECTORY_SEPARATOR . $file, $dst  . DIRECTORY_SEPARATOR . $file, $replace, $result, $move);
                }
            }
        }
        elseif (file_exists($src)) {
            if ($replace && file_exists($dst)) {
                $result['replaced'] ++;
                remove_dir($dst);
            }

            if (copy($src, $dst)) {
                $result['copied'] ++;
            } else {
                $result['skipped'] ++;
            }
        }

        return $result['copied'];
    }

    //-------------------------------------------------------------------------

    public function move_files($src, $dst) {
        $result = $this->copy_files($src, $dst);
        remove_dir($src);
        return $result;
    }

    //-------------------------------------------------------------------------

    public function tar_create($dir, $archive_name = null) {
        require_once nc_core('ADMIN_FOLDER') . 'tar.inc.php';

        $additional_path = dirname(str_replace(nc_core('DOCUMENT_ROOT'), '', $dir));
        $file_name        = basename($dir);

        if (!$archive_name) {
            $archive_name = $file_name . '.tgz';
        } else {
            $archive_name = rtrim($archive_name, '.tgz') . '.tgz';
        }

        $result = nc_tgz_create($archive_name, $file_name, $additional_path);

        if ($result) {
            remove_dir($dir);
        }

        return $additional_path . '/' . $archive_name;
    }

    //-------------------------------------------------------------------------

    public function tar_extract($file) {
        require_once nc_core('ADMIN_FOLDER') . 'tar.inc.php';

        $archive_path = trim(str_replace(nc_core('DOCUMENT_ROOT'), '', $file), '/');
        $tmp_path     = dirname($archive_path) . '/' . uniqid();
        $abs_tmp_path = nc_core('DOCUMENT_ROOT') . '/' . $tmp_path;

        mkdir($abs_tmp_path);

        nc_tgz_extract($archive_path, $tmp_path);

        $files = scandir($abs_tmp_path);

        if (count($files) == 3) {
            $result_path = nc_core('DOCUMENT_ROOT') . '/' . dirname($archive_path) . '/' . $files[2] . '-' . uniqid();
            $this->move_files($abs_tmp_path . '/' . $files[2], $result_path);
            remove_dir($abs_tmp_path);
        } else {
            $result_path = $abs_tmp_path;
        }

        unlink($file);

        return $result_path;
    }

    /**************************************************************************
        EXPORT METODS
    **************************************************************************/

    public function get_export_file() {
        return $this->export_file;
    }

    //-------------------------------------------------------------------------

    public function set_export_settings($settings) {
        foreach ($settings as $key => $val) {
            $this->export_settings[$key] = $val;
        }
    }

    //-------------------------------------------------------------------------

    public function get_export_settings($key = null, $default = null) {
        if ($key) {
            return isset($this->export_settings[$key]) ? $this->export_settings[$key] : $default;
        }

        return $this->export_settings;
    }


    //-------------------------------------------------------------------------

    public function export_init($type, $id, $settings = array()) {
        if (count($this->current_object_stack) > 1) {
            $this->set_dump_info('multiple_mode', 1);
            $this->dump_info['sub_export'][$type][$id] = $id;
            return;
        }

        $suffix = date('Ymd');
        // $suffix = false;

        $this->set_export_settings($settings);

        $folder = $this->backup->make_filename($type, $id, $suffix);

        $path = $this->backup->get_export_path($folder);
        $this->set_dump_path($path);

        if (!file_exists($path)) {
            // make dir if need: netcat_files/export/
            $export_path = $this->backup->get_export_path();
            if (!file_exists($export_path)) {
                mkdir($export_path);
            }

            // netcat_files/export/{type}-{id}/
            mkdir($path);
        }

        $this->set_dump_info('version', $this->current_object ? $this->current_object->get_version() : 0);
        $this->set_dump_info('type',    $type);
        $this->set_dump_info('id',      $id);
        $this->set_dump_info('time',    time());
        $this->set_dump_info('user',    (int) $GLOBALS[ 'AUTH_USER_ID']);
        $this->set_dump_info('host',    nc_core()->HTTP_HOST);

        $this->set_dump_info('data',  array());
        $this->set_dump_info('files', array());

        $this->set_dump_info('dict_fields', array());
        $this->set_dump_info('dict',        array());
    }

    //-------------------------------------------------------------------------

    public function export_finish() {
        $this->save_dump_info();

        if (count($this->current_object_stack) > 1) {
            return;
        }

        $this->export_file = $this->get_dump_path();

        if ($this->get_export_settings('compress')) {
            $this->export_file = $this->tar_create($this->export_file);
        }
    }

    //-------------------------------------------------------------------------

    public function export_data($table, $primary_key, &$data, $filename = false) {
        // if data is row
        if (isset($data[$primary_key])) {
            $data = array($data);
        }

        $this->register_dict_field($primary_key);

        if (!$data) {
            return false;
        }

        if ($filename) {
            $filename = $this->get_filename($filename);
        } else {
            $first_row = current($data);
            $first_id  = $first_row[$primary_key];
            $filename  = $this->get_filename($table, $first_id);
        }

        $this->dump_info['data'][$table]['pk']      = $primary_key;
        $this->dump_info['data'][$table]['fields']  = array_keys(current($data));
        $this->dump_info['data'][$table]['files'][] = $filename;

        $this->write_data($filename, $data);
    }

    //-------------------------------------------------------------------------

    public function export_table($table) {
        $this->dump_info['table'][$table] = $this->sql_make_create($table);
    }

    //-------------------------------------------------------------------------

    public function export_files($path, $file = null) {
        if ($file === null) {
            $file = basename($path);
            $path = dirname($path);
        }
        $path     = rtrim($path, DIRECTORY_SEPARATOR);
        $file     = trim($file, DIRECTORY_SEPARATOR);
        $src_path = nc_core()->DOCUMENT_ROOT . $path . DIRECTORY_SEPARATOR . $file;
        $tmp_dir  = str_replace('/', '.', trim($path . '.' . $file, '/'));

        $dest_path = $this->get_dump_path($tmp_dir);
        $this->copy_files($src_path, $dest_path);

        $this->dump_info['files'][$path][$file] = $tmp_dir;
    }

    /**************************************************************************
        IMPORT METHODS
    **************************************************************************/

    public function import_init($file, $settings = array()) {
        if (!$file) {
            throw new Exception("Import file not set", 1);
        }


        if (count($this->current_object_stack) > 1) {
            return;
        }

        if (!is_dir($file)) {
            $tmp_archive = nc_core()->TMP_FOLDER . uniqid() . '.tgz';
            copy($file, $tmp_archive);
            $file = $this->tar_extract($tmp_archive);
        }

        $this->set_import_settings($settings);

        if (!file_exists($file)) {
            throw new Exception("Import file not found: {$file}", 1);
        }

        $this->import_result = new nc_backup_result;
        $this->set_dump_path($file);

        return $this->load_dump_info();
    }

    //-------------------------------------------------------------------------

    public function import_validation() {

        $version = $this->current_object ? $this->current_object->get_version() : 0;

        if ($this->get_dump_info('version') != $version) {
            throw new Exception(TOOLS_DATA_BACKUP_INCOMPATIBLE_VERSION, 1);
        }

        if ($this->get_import_settings('save_ids')) {
            $data = $this->get_dump_info('data');

            foreach ($data as $table => $settings) {
                $pk       = $settings['pk'];
                $db_table = nc_db_table::make($table, $pk);
                $dict     = $this->get_dict($pk);

                if ($dict) {
                    if ($result = $db_table->where_in_id($dict)->get_list($pk)) {
                        $ids = implode(', ', $result);
                        throw new Exception(TOOLS_DATA_BACKUP_IMPORT_DUPLICATE_KEY_ERROR . "<br>Table: {$table}<br>IDs: {$ids}", 1);
                    }
                }
            }
        }
    }

    //-------------------------------------------------------------------------

    public function set_import_result($key, $value = null, $append = false) {
        if (is_array($key)) {
            $this->import_result += $key;
        }

        if ($append && isset($this->import_result[$key])) {
            $this->import_result[$key] += $value;
        } else {
            $this->import_result[$key] = $value;
        }
    }

    //-------------------------------------------------------------------------

    public function get_import_result($key = null, $default = null) {
        if ($key) {
            return isset($this->import_result[$key]) ? $this->import_result[$key] : $default;
        }

        return $this->import_result;
    }

    //-------------------------------------------------------------------------

    public function set_import_settings($settings) {
        foreach ($settings as $key => $val) {
            $this->import_settings[$key] = $val;
        }
    }

    //-------------------------------------------------------------------------

    public function get_import_settings($key = null, $default = null) {
        if ($key) {
            return isset($this->import_settings[$key]) ? $this->import_settings[$key] : $default;
        }

        return $this->import_settings;
    }

    //-------------------------------------------------------------------------

    public function import_data($table, $new_table = null) {
        $save_ids    = $this->get_import_settings('save_ids');
        $data        = $this->get_dump_info('data', $table);
        $dict_fields = $this->get_dump_info('dict_fields');
        $new_table   = $new_table ? $new_table : $table;

        $pk     = $data['pk'];
        $fields = $data['fields'];
        $xmls   = $data['files'];

        if (!$xmls) {
            return false;
        }
        $db_table = nc_db_table::make($new_table, $pk);

        $message_table = substr($new_table, 0, 7) == 'Message' ? substr($new_table, 7) : false;

        $lower_table         = $message_table ? 'message' : strtolower($new_table);
        $event_before_insert = 'before_insert_' . $lower_table;
        $event_after_insert  = 'after_insert_' . $lower_table;

        foreach ($xmls as $xml) {
            $xml_file = $this->get_dump_path($xml);

            if (!file_exists($xml_file)) {
                throw new Exception("XML file not found: {$xml}", 1);
            }

            $data = $this->read_data($xml_file);

            foreach ($data as $row) {
                $row = array_combine($fields, unserialize(base64_decode($row)));

                foreach ($dict_fields as $alias => $field) {
                    if (isset($row[$alias])) {
                        if ($field === true) {
                            $field = $alias;
                        }
                        $row[$alias] = $this->get_dict($field, $row[$alias], $row[$alias]);
                    }
                }

                $event_args = $message_table ? array($message_table, $row) : array($row);
                $this->call_event($event_before_insert, $event_args, $row);

                if (!$save_ids) {
                    $id = $row[$pk];
                    unset($row[$pk]);
                }
                if ($new_id = $db_table->set($row)->insert()) {
                    $this->set_import_result('total_insert_rows', +1, true);
                } elseif ($error = $db_table->get_last_error()) {
                    $error = 'TABLE: ' . $db_table->get_table() . PHP_EOL . 'ERROR: ' . $error;
                    throw new Exception($error, 1);
                }

                $this->set_dict($pk, $id, $new_id);

                $event_args[] = $new_id;
                $this->call_event($event_after_insert, $event_args);
            }
        }

    }

    //-------------------------------------------------------------------------

    public function import_table($table, $new_table = null) {
        $sql = $this->get_dump_info('table', $table);

        if ($new_table) {
            $sql = str_replace("`{$table}`", "`{$new_table}`", $sql);
        }

        nc_db()->query($sql);
        $this->set_import_result('total_create_tables', +1, true);
    }

    //-------------------------------------------------------------------------

    public function import_files() {
        $save_ids     = $this->get_import_settings('save_ids');
        $import_files = $this->get_dump_info('files');

        $doc_root = nc_core('DOCUMENT_ROOT');

        foreach ($import_files as $path => $files) {
            foreach ($files as $file => $src) {
                $path      = rtrim($path, '/') . '/';
                $file      = trim($file, '/');
                $file_path = $path . $file;

                $this->call_event('before_copy_file', array($path, $file), $file_path);

                $dest = $doc_root . $file_path;
                $src  = $this->get_dump_path($src);

                // $replace = false;
                // if ($this->copy_files($src, $dest, $replace, $result)) {
                //     $this->set_import_result('total_copied_files',   $result['copied'], true);
                //     $this->set_import_result('total_skipped_files',  $result['skipped'], true);
                //     $this->set_import_result('total_replaced_files', $result['replaced'], true);
                // }

                if (file_exists($dest) && is_dir($dest)) {
                    remove_dir($dest);
                }

                $parent_dir = dirname($dest);
                if (!file_exists($parent_dir)) {
                    mkdir($parent_dir);
                }

                if (@rename($src, $dest)) {
                    $this->set_import_result('total_copied_files', +1, true);
                } else {
                    $this->set_import_result('total_skipped_files', +1, true);
                }

                $this->call_event('after_copy_file', array($dest));
            }
        }
    }

    //-------------------------------------------------------------------------

    public function import_finish() {
        remove_dir($this->get_dump_path());
        // $this->save_dump_info();
        // $this->tar_create($this->get_dump_path());
    }

    /**************************************************************************
        XML DRIVER PART
    **************************************************************************/

    public function get_filename($parts) {
        $args = func_get_args();
        return implode(nc_backup::FILENAME_DIVIDER, $args) . '.xml';
    }

    //-------------------------------------------------------------------------

    protected function make_xml_reader() {
        return new XMLReader();
    }

    //-------------------------------------------------------------------------

    protected function make_domdocument() {
        $xml = new DOMDocument();
        $xml->preserveWhiteSpace = false;
        $xml->formatOutput       = true;
        $xml->encoding           = 'utf-8';
        return $xml;
    }

    //-------------------------------------------------------------------------

    protected function write_data($filename, &$data) {
        $xml_filepath = $this->get_dump_path($filename);

        if (file_exists($xml_filepath)) {
            unlink($xml_filepath);
        }

        $xml = $this->make_domdocument();

        $xml_root = $xml->createElement('root');
        $xml->appendChild($xml_root);

        foreach ($data as $row) {
            $this->search_dict_fields($row);
            $row = base64_encode(serialize(array_values($row)));

            $xml_row = $xml->createElement('row');
            $xml_row->appendChild($xml->createCDATASection($row));

            $xml_root->appendChild($xml_row);
        }

        file_put_contents($xml_filepath, $xml->saveXML());
    }

    //-------------------------------------------------------------------------

    protected function read_data($xmlfile) {
        $data = array();

        $xml = $this->make_domdocument();
        $xml->load(realpath($xmlfile));
        $rows = $xml->getElementsByTagName('row');

        foreach ($rows as $row) {
            $data[] = $row->nodeValue;
        }

        return $data;
    }

    //-------------------------------------------------------------------------

    // protected function xml2assoc($xml, array &$target = array()) {
    //     while ($xml->read()) {
    //         switch ($xml->nodeType) {
    //             case XMLReader::END_ELEMENT:
    //                 return $target;

    //             case XMLReader::ELEMENT:
    //                 $name = $xml->name;
    //                 $target[$name] = $xml->hasAttributes ? array() : '';
    //                 if (!$xml->isEmptyElement) {
    //                     $target[$name] = array();
    //                     $this->xml2assoc($xml, $target[$name]);
    //                 }

    //                 if ($xml->hasAttributes)
    //                     while($xml->moveToNextAttribute())
    //                         $target[$name]['@'.$xml->name] = $xml->value;
    //                 break;

    //             case XMLReader::TEXT:
    //             case XMLReader::CDATA:
    //                 $target = $xml->value;
    //         }
    //     }
    //     return $target;
    // }

    // //-------------------------------------------------------------------------

    // protected function xml_read($xml, $element, $read_content = true) {
    //     while ($xml->read()) {
    //         if ($xml->nodeType == XMLReader::ELEMENT) {
    //             if ($xml->name == $element) {
    //                 return $read_content ? $this->xml2assoc($xml) : true;
    //             }
    //         }
    //     }

    //     return false;
    // }

    //-------------------------------------------------------------------------

    protected function sql_make_create($table) {
        $db = nc_db();
        $db->query("SET SQL_QUOTE_SHOW_CREATE = 1;");

        $result = $db->get_row("SHOW CREATE TABLE `{$table}`", ARRAY_N);

        if (!$result) {
            return false;
        }

        return preg_replace('/ AUTO_INCREMENT=\d+/', '', $result[1]);
    }

    //-------------------------------------------------------------------------
    //
}