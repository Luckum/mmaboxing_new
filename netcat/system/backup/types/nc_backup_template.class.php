<?php


class nc_backup_template extends nc_backup_base {

    //--------------------------------------------------------------------------

    // protected $group_name = SECTION_INDEX_MENU_DEVELOPMENT;
    // protected $name       = SECTION_INDEX_DEV_TEMPLATES;

    //-------------------------------------------------------------------------

    protected $new_paths = array();
    protected $template_table;

    //-------------------------------------------------------------------------

    protected function init() {
        $this->template_table = nc_db_table::make('Template');
    }

    //--------------------------------------------------------------------------

    protected function reset() {
        parent::reset();
        $this->new_paths = array();
    }

    //-------------------------------------------------------------------------

    protected function row_attributes($ids) {
        $titles = $this->template_table->select('Template_ID, Description, File_Mode')->where_in_id((array)$ids)->index_by_id()->get_result();
        $result = array();
        foreach ($titles as $id => $row) {
            $fs = $row['File_Mode'] ? 1 : 0;
            $result[$id] = array(
                'title'       => $row['Description'],
                'sprite'      => 'nc--dev-templates',
                'netcat_link' => $this->nc_core->ADMIN_PATH . "template/index.php?fs={$fs}&phase=4&TemplateID={$id}"
            );
        }
        return $result;
    }

    /**************************************************************************
        EXPORT PART
    **************************************************************************/

    protected function export_form() {
        $options = array();
        $result = $this->template_table
            ->select('Template_ID, Description, File_Mode')
            ->where('Parent_Template_ID', 0)
            ->order_by('File_Mode', 'DESC')->order_by('Template_ID')
            ->as_object()->get_result();

        foreach ($result as $row) {
            $group = $row->Template_ID . '. ' . $row->Description . ($row->File_Mode ? '' : ' (v4)');
            $options[$row->Template_ID] = $group;
        }

        return $this->nc_core->ui->form->add_row(NETCAT_QUICKBAR_BUTTON_TEMPLATE_SETTINGS)->select('id', $options);
    }

    //-------------------------------------------------------------------------

    protected function export_validation() {
        if (!$this->id) {
            $this->set_validation_error('Template not selected');
            return false;
        }
    }

    //-------------------------------------------------------------------------

    protected function export_process() {
        $id       = $this->id;
        $template = $this->template_table->where_id($id)->get_row();

        if (!$template) {
            return false;
        }

        // Export data: Template
        $data       = array($id => $template);
        $parent_ids = array($id);
        while ($parent_ids) {
            $result     = $this->template_table->where_in('Parent_Template_ID', $parent_ids)->index_by_id()->get_result();
            $parent_ids = array_keys($result);
            $data      += $result;
        }

        $this->dumper->export_data('Template', 'Template_ID', $data);

        // Export files
        if ($template['File_Mode']) {
            $this->dumper->export_files(nc_core('HTTP_TEMPLATE_PATH') . 'template', $template['File_Path']);
        }

        $this->dumper->set_dump_info('file_mode', $template['File_Mode']);
    }

    //--------------------------------------------------------------------------
    // IMPORT
    //--------------------------------------------------------------------------

    protected function import_process() {
        $file_mode = $this->dumper->get_dump_info('file_mode') ? '1' : '0';
        $this->dumper->import_data('Template');

        $this->new_id = $this->dumper->get_dict('Template_ID', $this->id);

        $this->dumper->import_files();

        $this->dumper->set_import_result('link', $this->nc_core->ADMIN_PATH . '#template'.($file_mode?'_fs':'').'.edit(' . $this->new_id . ')');
        $this->dumper->set_import_result('redirect', $this->nc_core->ADMIN_PATH . 'template/index.php?fs='.$file_mode.'&phase=4&TemplateID=' . $this->new_id);
    }

    //--------------------------------------------------------------------------

    protected function event_before_insert_template($row) {
        $row['Parent_Template_ID'] = $this->dumper->get_dict('Template_ID', $row['Parent_Template_ID']);
        return $row;
    }

    //--------------------------------------------------------------------------

    // Обновляем путь к директории с файлами шаблона
    protected function event_after_insert_template($row, $insert_id) {
        $update = array(
            'File_Path' => ($row['Parent_Template_ID'] ? $this->new_paths[$row['Parent_Template_ID']] : '/' ) . "{$insert_id}/",
        );
        $this->new_paths[$insert_id] = $update['File_Path'];
        $this->template_table->where_id($insert_id)->update($update);
    }

    //--------------------------------------------------------------------------

    // переименовываем основную папку
    protected function event_before_copy_file($path, $file) {
        return $path . $this->dumper->get_dict('Template_ID', $file);
    }

    //--------------------------------------------------------------------------

    // переименовываем папки шаблонов
    protected function event_after_copy_file($path) {
        $items = scandir($path);

        foreach ($items as $file) {
            if (is_numeric($file) && is_dir($path . '/' . $file)) {
                if ($new_file = $this->dumper->get_dict('Template_ID', $file)) {
                    rename($path . '/' . $file, $path . '/' . $new_file);
                    $this->event_after_copy_file($path . '/' . $new_file);
                }
            }
        }
    }

    //--------------------------------------------------------------------------
}