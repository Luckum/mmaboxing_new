<?php


class nc_backup_component extends nc_backup_base {

    //--------------------------------------------------------------------------

    protected $name = SECTION_CONTROL_CLASS;

    protected $class_table;
    protected $field_table;

    //-------------------------------------------------------------------------

    protected function init() {
        $this->class_table = nc_db_table::make('Class');
        $this->field_table = nc_db_table::make('Field');
    }

    //-------------------------------------------------------------------------

    protected function row_attributes($ids) {
        $titles = $this->class_table->select('Class_ID, Class_Name, Class_Group, File_Mode')->where_in_id((array)$ids)->index_by_id()->get_result();

        $result = array();
        foreach ($titles as $id => $row) {
            $fs = $row['File_Mode'] ? 1 : 0;
            $result[$id] = array(
                'title'       => $row['Class_Group'] . ': ' . $row['Class_Name'],
                'sprite'      => 'nc--dev-components',
                'netcat_link' => $this->nc_core->ADMIN_PATH . "class/index.php?fs={$fs}&phase=4&ClassID={$id}"
            );
        }

        return $result;
    }

    //--------------------------------------------------------------------------
    // EXPORT
    //--------------------------------------------------------------------------

    protected function export_form() {
        $options    = array(''=>'');
        $options_v4 = array(''=>'');

        $result = $this->class_table
            ->select('Class_ID, Class_Name, Class_Group, File_Mode')
            ->where('System_Table_ID', 0)->where('ClassTemplate', 0)
            ->order_by('File_Mode', 'DESC')->order_by('Class_Group')->order_by('Class_Name')
            ->as_object()->get_result();

        foreach ($result as $row) {
            $group = $row->Class_Group . ($row->File_Mode ? '' : ' (v4)');
            $options[$group][$row->Class_ID] = $row->Class_ID . '. ' . $row->Class_Name;
        }

        return $this->nc_core->ui->form->add_row(SECTION_CONTROL_CLASS)->select('id', $options);
    }

    //-------------------------------------------------------------------------

    protected function export_validation() {
        if (!$this->id) {
            $this->set_validation_error('Component not selected');
            return false;
        }
    }

    //-------------------------------------------------------------------------

    protected function export_process() {
        $id        = $this->id;
        $component = $this->class_table->where_id($id)->get_row();

        if (!$component) {
            return false;
        }

        // Export data: Class
        $data  = array($id => $component);
        $data += $this->class_table->where('ClassTemplate', $id)->index_by_id()->get_result();
        $this->dumper->export_data('Class', 'Class_ID', $data);

        // Export data: Field
        $data = $this->field_table->where('Class_ID', $id)->get_result();
        $this->dumper->export_data('Field', 'Field_ID', $data);

        $this->dumper->export_table('Message' . $id);

        // Export files
        if ($component['File_Mode']) {
            $this->dumper->export_files(nc_core('HTTP_TEMPLATE_PATH') . 'class', $component['File_Path']);
        }

        $this->dumper->set_dump_info('file_mode_' . $id, $component['File_Mode']);
    }

    //--------------------------------------------------------------------------
    // IMPORT
    //--------------------------------------------------------------------------

    protected function import_process() {
        $file_mode = $this->dumper->get_dump_info('file_mode_' . $this->id) ? '1' : '0';

        $this->dumper->import_data('Class');

        $this->new_id = $this->dumper->get_dict('Class_ID', $this->id);

        $this->dumper->import_table('Message' . $this->id, 'Message' . $this->new_id);

        $this->dumper->import_data('Field');

        $this->dumper->import_files();

        $this->dumper->set_import_result('link', $this->nc_core->ADMIN_PATH . '#dataclass' . ($file_mode ? '_fs' : '') . '.edit(' . $this->new_id . ')');
        $this->dumper->set_import_result('redirect', $this->nc_core->ADMIN_PATH . "class/index.php?fs={$file_mode}&phase=4&ClassID=" . $this->new_id);
    }

    //--------------------------------------------------------------------------

    protected function event_before_insert_class($row) {
        if ($row['ClassTemplate']) {
            $row['ClassTemplate'] = $this->dumper->get_dict('Class_ID', $row['ClassTemplate']);
        }
        return $row;
    }

    //--------------------------------------------------------------------------

    // Обновляем путь к директории с файлами шаблона
    protected function event_after_insert_class($row, $insert_id) {
        $update = array(
            'File_Path' => ($row['ClassTemplate'] ? "/" . $row['ClassTemplate'] : '' ) . "/{$insert_id}/",
        );
        $this->class_table->where_id($insert_id)->update($update);
    }

    //--------------------------------------------------------------------------

    // Меняем Class_ID для полей компонента
    protected function event_before_insert_field($row) {
        $row['Class_ID'] = $this->dumper->get_dict('Class_ID', $row['Class_ID']);
        return $row;
    }

    //--------------------------------------------------------------------------

    // переименовываем основную папку
    protected function event_before_copy_file($path, $file) {
        return $path . $this->dumper->get_dict('Class_ID', $file);
    }

    //--------------------------------------------------------------------------

    // переименовываем папки шаблонов
    protected function event_after_copy_file($path) {
        $items = scandir($path);

        foreach ($items as $file) {
            if (is_numeric($file) && is_dir($path . '/' . $file)) {
                if ($new_file = $this->dumper->get_dict('Class_ID', $file)) {
                    rename($path . '/' . $file, $path . '/' . $new_file);
                }
            }
        }
    }

    //--------------------------------------------------------------------------
}