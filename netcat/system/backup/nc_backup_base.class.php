<?php



class nc_backup_base {

    //-------------------------------------------------------------------------

    // protected $group_name = '';

    protected $name    = '';
    protected $type    = '';
    protected $id      = 0;
    protected $new_id  = 0;
    protected $file    = '';
    protected $version = 1;

    protected $validation_error = '';

    protected $nc_core = null;
    protected $backup  = null;
    protected $dumper  = null;

    //-------------------------------------------------------------------------

    public function __construct(nc_backup $backup) {
        $class = get_class($this);

        if ($class == 'nc_backup_base') {
            throw new Exception('Dont use nc_backup_base as object (extending only)', 1);
        }

        if (!$this->type) {
            $this->type = substr($class, strlen('nc_backup_'));
        }

        if (!$this->name) {
            $this->name = ucfirst($this->type);
        }

        $this->backup  = $backup;
        $this->nc_core = nc_core();

        $this->init();
    }

    //-------------------------------------------------------------------------

    public function get_row_attributes($ids) {
        static $attributes;

        if ($attributes === null) {
            $attributes = $this->row_attributes($ids);
        }

        return $attributes;
    }

    //-------------------------------------------------------------------------

    protected function reset() {
        $this->id               = 0;
        $this->new_id           = 0;
        $this->validation_error = '';

        // $this->export = null;
        // $this->import = null;
        $this->dumper = null;
    }

    //-------------------------------------------------------------------------

    public function get_name() {
        return $this->name;
    }

    //-------------------------------------------------------------------------

    public function get_type() {
        return $this->type;
    }

    //-------------------------------------------------------------------------

    public function get_version() {
        return $this->version;
    }

    //-------------------------------------------------------------------------

    public function get_group_name() {
        return $this->group_name;
    }

    //-------------------------------------------------------------------------

    public function get_id() {
        return $this->id;
    }

    //-------------------------------------------------------------------------

    public function get_new_id() {
        return $this->new_id;
    }

    //-------------------------------------------------------------------------

    public function get_export_form() {
        return $this->export_form();
    }

    //-------------------------------------------------------------------------

    public function set_validation_error($message) {
        $this->validation_error = $message;
    }

    //-------------------------------------------------------------------------

    public function get_validation_error() {
        return $this->validation_error;
    }

    //-------------------------------------------------------------------------

    public function call_event($event, $attr) {
        $method = 'event_' . $event;

        if (method_exists($this, $method)) {
            switch (count($attr)) {
                case 0: return $this->$method();
                case 1: return $this->$method($attr[0]);
                case 2: return $this->$method($attr[0], $attr[1]);
                case 3: return $this->$method($attr[0], $attr[1], $attr[2]);
            }
        }
    }

    //-------------------------------------------------------------------------

    public function export($id, $settings = array()) {
        $this->reset();
        $this->id     = $id;
        $this->dumper = $this->backup->get_dumper();

        $this->dumper->set_current_object($this);
        $this->dumper->export_init($this->type, $id, $settings);

        $this->export_init();

        $valid = $this->export_validation();
        $error = $this->get_validation_error();
        if ($valid === false || $error) {
            throw new Exception($error ? $error : 'Export validation error', 1);
        }

        $this->export_process();

        $this->dumper->export_finish();

        $this->dumper->forgot_current_object();

        return $this->dumper->get_export_file();
    }

    //-------------------------------------------------------------------------

    public function export_download($id) {
        $file = $this->export($id);

        ob_get_level() && ob_clean();
        header("Content-type: application/x-compressed");
        header("Content-Disposition: attachment; filename=" . basename($file));
        header("Expires: 0");
        header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
        header("Pragma: public");

        echo file_get_contents(nc_core('DOCUMENT_ROOT') . $file);
        exit;
    }

    //-------------------------------------------------------------------------

    public function get_import_form() {
        return $this->import_form();
    }

    //-------------------------------------------------------------------------

    public function import($file, $settings = array()) {
        try {
            $this->reset();
            $this->dumper = $this->backup->get_dumper();

            $this->dumper->set_current_object($this);
            $this->dumper->import_init($file, $settings);

            $multiple_mode = false;
            if ($this->dumper->get_dump_info('multiple_mode') && is_numeric($file)) {
                $multiple_mode = true;
                $this->id = $file;
            } else {
                $this->id = $this->dumper->get_dump_info('id');
                $type = $this->dumper->get_dump_info('type');
                // Check type
                if ($this->type != $type) {
                    throw new Exception("Type not match: '{$type}' != '{$this->type}'");
                }
            }

            $this->dumper->import_validation();

            $valid = $this->import_validation();
            $error = $this->get_validation_error();
            if ($valid === false || $error) {
                throw new Exception($error ? $error : 'Export validation error', 1);
            }

            $this->import_process();

            if (!$multiple_mode) {
                $this->dumper->import_finish();

                $this->dumper->set_import_result('new_id', $this->new_id);
            }

            $this->dumper->forgot_current_object();

            return $this->dumper->get_import_result();

        } catch (Exception $e) {
            $this->dumper->import_finish();
            throw new Exception($e->getMessage(), 1);
        }
    }

    //-------------------------------------------------------------------------

    /**************************************************************************
        EXTENDING METHODS
    **************************************************************************/

    protected function init() {}
    protected function row_attributes() {}

    protected function export_form() {}
    protected function export_init() {}
    protected function export_validation() {}
    // protected function export_process() {}

    protected function import_form() {}
    protected function import_init() {}
    protected function import_validation() {}
    // protected function import_process() {}
}