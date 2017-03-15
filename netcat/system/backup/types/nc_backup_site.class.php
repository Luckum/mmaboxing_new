<?php



class nc_backup_site extends nc_backup_base {

    //--------------------------------------------------------------------------

    protected $name = TOOLS_SYSTABLE_SITES;

    protected $site_table;
    protected $subdivision_table;
    protected $subclass_table;
    protected $template_table;
    protected $settings_table;
    protected $class_table;
    protected $field_table;

    protected $template_paths     = array();
    protected $new_components     = array();
    protected $file_fields        = array();
    protected $simple_file_fields = array();

    //--------------------------------------------------------------------------

    protected function init() {
        $this->site_table        = nc_db_table::make('Catalogue');
        $this->subdivision_table = nc_db_table::make('Subdivision');
        $this->subclass_table    = nc_db_table::make('Sub_Class');
        $this->template_table    = nc_db_table::make('Template');
        $this->settings_table    = nc_db_table::make('Settings');
        $this->class_table       = nc_db_table::make('Class');
        $this->field_table       = nc_db_table::make('Field');
    }

    //-------------------------------------------------------------------------

    protected function reset() {
        parent::reset();
        $this->template_paths     = array();
        $this->new_components     = array();
        $this->file_fields        = array();
        $this->simple_file_fields = array();
    }

    //-------------------------------------------------------------------------

    protected function row_attributes($ids) {
        $titles = $this->site_table->select('Catalogue_ID, Catalogue_Name, Domain')->where_in_id((array)$ids)->index_by_id()->get_result();

        $result = array();
        foreach ($titles as $id => $row) {
            $result[$id] = array(
                'title'       => $row['Catalogue_Name'] . ' (' . $row['Domain'] . ')',
                'sprite'      => 'nc--site',
                'netcat_link' => $this->nc_core->ADMIN_PATH . "subdivision/full.php?CatalogueID={$id}"
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

        $result = $this->site_table
            ->select('Catalogue_ID, Catalogue_Name, Domain')
            ->order_by('Priority')
            ->order_by('Catalogue_Name')
            ->order_by('Catalogue_ID')
            ->index_by_id()
            ->as_object()
            ->get_result();


        foreach ($result as $site_id => $row) {
            $options[$site_id] = $site_id . '. ' . $row->Catalogue_Name . ' (' . $row->Domain . ')';
        }

        return $this->nc_core->ui->form->add_row(SECTION_CONTROL_CLASS)->select('id', $options);
    }

    //-------------------------------------------------------------------------

    protected function export_validation() {
        if (!$this->id) {
            $this->set_validation_error('Site not selected');
            return false;
        }
    }

    //-------------------------------------------------------------------------

    protected function export_process() {
        global $SUB_FOLDER, $HTTP_FILES_PATH, $DOCUMENT_ROOT;

        $id   = $this->id;
        $site = $this->site_table->where_id($id)->get_row();

        if (!$site) {
            return false;
        }

        $this->dumper->register_dict_field('Catalogue_ID', 'Class_ID', 'Sub_Class_ID', 'Template_ID', 'Subdivision_ID');

        // Catalogue
        $data = array($id => $site);
        $this->dumper->export_data('Catalogue', 'Catalogue_ID', $data);


        // Settings
        $data = $this->settings_table->where('Catalogue_ID', $id)->index_by_id()->get_result();
        $this->dumper->export_data('Settings', 'Settings_ID', $data);


        // Subdivisions
        $data = $this->subdivision_table->where('Catalogue_ID', $id)->where('Parent_Sub_ID', 0)->index_by_id()->get_result();
        $parent_ids = array_keys($data);
        while ($parent_ids) {
            $result     = $this->subdivision_table->where_in('Parent_Sub_ID', $parent_ids)->index_by_id()->get_result();
            $parent_ids = array_keys($result);
            $data      += $result;
        }
        $this->dumper->export_data('Subdivision', 'Subdivision_ID', $data);


        // Sub_Class
        $sub_ids     = $this->dumper->get_dict('Subdivision_ID');
        $sub_classes = $this->subclass_table->where_in('Subdivision_ID', $sub_ids)->index_by_id()->get_result();
        $this->dumper->export_data('Sub_Class', 'Sub_Class_ID', $sub_classes);


        ##### TEMPLATES #####

        $template_ids = $this->dumper->get_dict('Template_ID');
        unset($template_ids[0]);
        if ($template_ids) {
            do {
                $template_ids = array_unique($this->template_table->where_in_id($template_ids)->get_list('Parent_Template_ID'));
                $template_ids[0] = 0;
            } while(call_user_func_array('max', $template_ids));
            unset($template_ids[0]);
            $template_ids = array_keys($template_ids);

            // Template
            $templates = $this->template_table->where_in_id($template_ids)->index_by_id()->get_result();
            $data = $templates;
            while ($template_ids) {
                $result       = $this->template_table->where_in('Parent_Template_ID', $template_ids)->index_by_id()->get_result();
                $template_ids = array_keys($result);
                $data        += $result;
            }
            $this->dumper->export_data('Template', 'Template_ID', $data);

            // Export files
            foreach ($templates as $tpl) {
                if ($tpl['File_Mode']) {
                    $this->dumper->export_files(nc_core('HTTP_TEMPLATE_PATH') . 'template', $tpl['File_Path']);
                }
            }
        }



        ##### COMPONENTS #####

        // Class
        if ($class_ids = $this->dumper->get_dict('Class_ID')) {
            $components = $this->class_table->where_in_id($class_ids)->where('System_Table_ID', 0)->index_by_id()->get_result();

            // Class templates
            $data = $components + $this->class_table->where_in('ClassTemplate', $class_ids)->index_by_id()->get_result();
            $this->dumper->export_data('Class', 'Class_ID', $data);

            // Field
            $data = $this->field_table->where_in('Class_ID', $class_ids)->index_by_id()->get_result();
            $this->dumper->export_data('Field', 'Field_ID', $data);

            foreach ($components as $class_id => $com) {
                // Message*
                $this->dumper->export_table('Message' . $class_id);

                // Export component files
                if ($com['File_Mode']) {
                    $this->dumper->export_files(nc_core('HTTP_TEMPLATE_PATH') . 'class', $com['File_Path']);
                }
            }
        }


        ##### DATA #####

        if ($sub_classes) {
            foreach ($sub_classes as $sub_class_id => $sub_class) {
                $class_id      = $sub_class['Class_ID'];
                $sub_id        = $sub_class['Subdivision_ID'];
                $message_table = nc_db_table::make('Message' . $class_id, 'Message_ID');

                // Data
                $data = $message_table->where('Sub_Class_ID', $sub_class_id)->index_by_id()->get_result();
                $this->dumper->export_data($message_table->get_table(), 'Message_ID', $data);

                // Files
                $dir = $SUB_FOLDER . $HTTP_FILES_PATH . "{$sub_id}/{$sub_class_id}/";
                if (file_exists($DOCUMENT_ROOT . $dir)) {
                    $this->dumper->export_files($dir);
                }
            }
        }
    }

    //-------------------------------------------------------------------------
    //-------------------------------------------------------------------------
    //-------------------------------------------------------------------------

    protected function import_process() {

        // Catalogue
        $this->dumper->register_dict_field(array(
            'Parent_Sub_ID'      => 'Subdivision_ID',
            'ClassTemplate'      => 'Class_ID',
            'Class_Template_ID'  => 'Class_ID',
            'Parent_Template_ID' => 'Template_ID',
        ));

        $this->dumper->import_data('Catalogue');
        $this->new_id = $this->dumper->get_dict('Catalogue_ID', $this->id);


        // Settings
        $this->dumper->import_data('Settings');

        // Template
        $this->dumper->import_data('Template');

        // Class
        $this->dumper->import_data('Class');

        // Field
        $this->dumper->import_data('Field');

        // Message*
        $class_ids = $this->dumper->get_dict('Class_ID');
        foreach ($class_ids as $old_id => $new_id) {
            if (isset($this->new_components[$new_id])) {
                $this->dumper->import_table('Message' . $old_id, 'Message' . $new_id);
            }
        }

        // Subdivision
        $this->dumper->import_data('Subdivision');

        // Update Catalogue
        $site = $this->site_table->where_id($this->new_id)->get_row();
        $this->site_table->where_id($this->new_id)->update(array(
            'E404_Sub_ID'  => $this->dumper->get_dict('Subdivision_ID', $site['E404_Sub_ID'], $site['E404_Sub_ID']),
            'Title_Sub_ID' => $this->dumper->get_dict('Subdivision_ID', $site['Title_Sub_ID'], $site['Title_Sub_ID']),
        ));

        // Sub_Class
        $this->dumper->import_data('Sub_Class');

        // DATA
        foreach ($class_ids as $old_id => $new_id) {
            if (isset($this->new_components[$new_id])) {
                $this->dumper->import_data('Message' . $old_id, 'Message' . $new_id);
            }
        }

        $this->dumper->import_files();
    }

    //-------------------------------------------------------------------------

    protected function event_before_insert_catalogue($row) {
        $domain_exists = (bool) $this->site_table->where('Domain', $row['Domain'])->count_all();
        if ($domain_exists) {
            $row['Domain'] = 'domain-' . uniqid();
        }
        return $row;
    }

    //-------------------------------------------------------------------------

    protected function event_before_insert_settings($row) {
        if (substr($row['Key'], 0, 13) == 'nc_shop_mode_') {
            $row['Key'] = 'nc_shop_mode_' . $row['Catalogue_ID'];
        }

        return $row;
    }

    //-------------------------------------------------------------------------

    protected function event_after_insert_template($row, $insert_id) {
        $update = array(
            'File_Path' => ($row['Parent_Template_ID'] ? $this->template_paths[$row['Parent_Template_ID']] : '/' ) . "{$insert_id}/",
        );
        $this->template_paths[$insert_id] = $update['File_Path'];
        $this->template_table->where_id($insert_id)->update($update);
    }

    //-------------------------------------------------------------------------

    protected function event_after_insert_class($row, $class_id) {
        if (!$row['ClassTemplate']) {
            $this->new_components[$class_id] = $class_id;
        }
        $update = array(
            'File_Path' => ($row['ClassTemplate'] ? "/" . $row['ClassTemplate'] : '' ) . "/{$class_id}/",
        );
        $this->class_table->where_id($class_id)->update($update);
    }

    //-------------------------------------------------------------------------

    protected function event_after_insert_field($row, $field_id) {
        // file fields
        if ($row['TypeOfData_ID'] == 6) {
            if (strpos($row['Format'], 'fs1')) {
                $this->simple_file_fields[$row['Class_ID']][$row['Field_Name']] = $row;
            } else {
                $this->file_fields[$row['Class_ID']][$row['Field_Name']] = $row;
            }
        }
    }

    //-------------------------------------------------------------------------

    protected function event_before_insert_message($message_id, $row) {
        if (isset($this->file_fields[$message_id])) {
            foreach ($this->file_fields[$message_id] as $key => $field) {
                $val = $row[$key];
                $val = explode(':', $val);
                if (isset($val[3])) {
                    $file = explode('/', $val[3]);
                    $file[0] = $this->dumper->get_dict('Subdivision_ID', $file[0], $file[0]);
                    $file[1] = $this->dumper->get_dict('Sub_Class_ID', $file[1], $file[1]);
                    $val[3] = implode('/', $file);
                }
                $row[$key] = implode(':', $val);
            }
        }
    }

    //-------------------------------------------------------------------------
    //-------------------------------------------------------------------------
    //-------------------------------------------------------------------------

    protected function detect_type_by_path($path) {
        global $HTTP_TEMPLATE_PATH, $HTTP_FILES_PATH;

        $types = array(
            'class'    => $HTTP_TEMPLATE_PATH . 'class/',
            'template' => $HTTP_TEMPLATE_PATH . 'template/',
            'files'    => $HTTP_FILES_PATH,
        );

        foreach ($types as $type => $type_path) {
            if (substr($path, 0, strlen($type_path)) == $type_path) {
                return $type;
            }
        }
    }

    //-------------------------------------------------------------------------

    protected function event_before_copy_file($path, $file) {
        switch ($this->detect_type_by_path($path)) {
            case 'class':
                return $path . $this->dumper->get_dict('Class_ID', $file, $file);

            case 'template':
                return $path . $this->dumper->get_dict('Template_ID', $file);

            case 'files':
                $full_path = $path . $file;

                if (preg_match('@/\d+/\d+$@', $full_path)) {
                    $full_path = explode('/', $full_path);

                    $i = count($full_path) - 1;
                    $full_path[$i] = $this->dumper->get_dict('Subdivision_ID', $full_path[$i], $full_path[$i]);
                    $i--;
                    $full_path[$i] = $this->dumper->get_dict('Sub_Class_ID', $full_path[$i], $full_path[$i]);

                    return implode('/', $full_path);
                }

            default:
                return $path . $file;
        }
    }

    //-------------------------------------------------------------------------

    protected function event_after_copy_file($path) {
        global $DOCUMENT_ROOT;

        $rel_path = substr($path, strlen($DOCUMENT_ROOT));

        switch ($this->detect_type_by_path($rel_path)) {
            case 'class':
                $items = scandir($path);
                foreach ($items as $file) {
                    if (is_numeric($file) && is_dir($path . '/' . $file)) {
                        if ($new_file = $this->dumper->get_dict('Class_ID', $file)) {
                            rename($path . '/' . $file, $path . '/' . $new_file);
                        }
                    }
                }
                break;

            case 'template':
                $items = scandir($path);
                foreach ($items as $file) {

                    if (is_numeric($file) && is_dir($path . '/' . $file)) {
                        if ($new_file = $this->dumper->get_dict('Template_ID', $file)) {
                            rename($path . '/' . $file, $path . '/' . $new_file);
                            $this->event_after_copy_file($path . '/' . $new_file);
                        }
                    }
                }
                break;
        }
    }

}