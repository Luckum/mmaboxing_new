<?php

/**
 * Класс для реализации поля типа "Файл"
 */
class nc_a2f_field_file extends nc_a2f_field {

    protected $upload, $filename, $filesize, $filepath, $filetype;

    public function get_subtypes() {
        return array('any', 'image');
    }

    public function render_value_field($html = true) {
        global $DOMAIN_NAME;
        $nc_core = nc_Core::get_object();
        $ret = "<input name='" . $this->get_field_name() . "' type='file' style='width:100%;'/>";
        // старый файл
        if ($this->value) {
            $filepath = $nc_core->SUB_FOLDER . $nc_core->HTTP_FILES_PATH . $this->value['path'];
            $ret .= "<input type='hidden' name='" . $this->get_field_name('old') . "' value='" . ($this->value['all']) . "' /><br/>\r\n";
            $ret .= NETCAT_MODERATION_FILES_UPLOADED . ": ";
            $ret .= "<a target='_blank' href='http://" . $DOMAIN_NAME . $this->value['path'] . "'>" . $this->value['name'] . "</a> (" . nc_bytes2size($this->value['size']) . ")";
            $ret .= " <input id='kill" . $this->name . "' type='checkbox' name='" . $this->get_field_name('kill') . "' value='1' />
               <label for='kill" . $this->name . "'>" . NETCAT_MODERATION_FILES_DELETE . "</label>\r\n";
        }
        if ($html) {
            $ret = "<div class='ncf_value'>" . $ret . "</div>\n";
        }

        return $ret;
    }

    public function set_value($value) {
        $nc_core = nc_Core::get_object();
        $this->value = false;
        if ($value) {
            list ($filename, $filetype, $filesize, $filepath) = explode(':', $value);
            $this->value['path'] = $nc_core->SUB_FOLDER . $nc_core->HTTP_FILES_PATH . $filepath;
            $this->value['type'] = $filetype;
            $this->value['size'] = $filesize;
            $this->value['name'] = $filename;
            $this->value['all'] = $value;
        }
        return 0;
    }

    public function save($value) {
        $nc_core = nc_Core::get_object();

        $array_name = $this->parent->get_array_name();

        if (!empty($value['old']) && !empty($value['kill'])) {
            list ($filename, $filetype, $filesize, $filepath) = explode(':', $value['old']);
            unlink($nc_core->FILES_FOLDER . $filepath);
            $this->value = $value['old'] = '';
        }

        if ($_FILES[$array_name]['error'][$this->name]) {
            if ($value['old']) {
                $this->value = $value['old'];
            }
            return 0;
        }

        $tmp_name = $_FILES[$array_name]['tmp_name'][$this->name];
        $filetype = $_FILES[$array_name]['type'][$this->name];
        $filename = $_FILES[$array_name]['name'][$this->name];

        // nothing was changed
        if (!empty($value['old']) && empty($value['kill']) && !$filetype) {
            if ($value['old']) {
                $this->value = $value['old'];
            }
            return 0;
        }

        $folder = $nc_core->FILES_FOLDER . 'cs/';
        $put_file_name = nc_transliterate($filename);
        $put_file_name = nc_get_filename_for_original_fs($put_file_name, $folder, array());

        $nc_core->files->create_dir($folder);
        move_uploaded_file($tmp_name, $folder . $put_file_name);
        $filesize = filesize($folder . $put_file_name);
        if ($filesize) {
            $this->value = $filename . ':' . $filetype . ':' . $filesize . ':cs/' . $put_file_name;
        }
        else {
            $this->value = '';
        }

        $this->upload = true;
        $this->filename = $filename;
        $this->filetype = $filetype;
        $this->filesize = $filesize;
        $this->filepath = $folder . $put_file_name;
    }
}

/**
 * Класс для реализации поля типа "Произвольный Файл"
 */
class nc_a2f_field_file_any extends nc_a2f_field_file {

}

/**
 * Класс для реализации поля типа "Файл - Изображение"
 */
class nc_a2f_field_file_image extends nc_a2f_field_file {

    protected $resize_w;
    protected $resize_h;

    public function get_extend_parameters() {
        return array('resize_w' => array('type' => 'int', 'caption' => NETCAT_CUSTOM_ONCE_EXTEND_RESIZE_W),
            'resize_h' => array('type' => 'int', 'caption' => NETCAT_CUSTOM_ONCE_EXTEND_RESIZE_H));
    }

    public function save($value) {
        $nc_core = nc_Core::get_object();

        parent::save($value);

        if ($this->upload && $this->resize_w && $this->resize_h) {
            require_once($nc_core->INCLUDE_FOLDER . "classes/nc_imagetransform.class.php");
            nc_ImageTransform::imgResize($this->filepath, $this->filepath, $this->resize_w, $this->resize_h);
            clearstatcache(true, $this->filepath);
            $this->filesize = filesize($this->filepath);
            if ($this->filesize) {
                $paths = explode('/', $this->filepath);
                $put_file_name = $paths[count($paths) - 1];
                $this->value = $put_file_name . ':' . $this->filetype . ':' . $this->filesize . ':cs/' . $put_file_name;
                $this->filename = $put_file_name;
            }
            else {
                $this->value = '';
            }
        }
    }

    public function render_value_field($html = true) {
        $ret = "<input name='" . $this->get_field_name() . "' type='file' style='width:100%;'/>";
        // старый файл
        if ($this->value) {
            $ret = parent::render_value_field(FALSE);
            $ret .= "<input type='hidden' name='subtype' value='image' /><br/>\r\n";
        }
        if ($html) {
            $ret = "<div class='ncf_value'>" . $ret . "</div>\n";
        }

        return $ret;
    }
}