<?php



class nc_template_partials_controller extends nc_ui_controller {

    protected $is_naked    = false;
    protected $error       = false;
    protected $template_id = 0;
    protected $partial     = false;

    //-------------------------------------------------------------------------

    protected function init() {
        $this->bind('list',   array('TemplateID'));
        $this->bind('add',    array('TemplateID'));
        $this->bind('edit',   array('TemplateID', 'partial'));
        $this->bind('remove', array('TemplateID', 'partial'));
    }

    //-------------------------------------------------------------------------

    protected function init_view($view) {
        $view->with('error',       $this->error);
        $view->with('template_id', $this->template_id);
        $view->with('action_url',  $this->nc_core->SUB_FOLDER . $this->nc_core->HTTP_ROOT_PATH . 'action.php?ctrl=admin.template_partials&fs=1&TemplateID='.$this->template_id.'&action=');
    }

    //-------------------------------------------------------------------------

    protected function after_action($result) {
        // JSON
        if (is_array($result)) {
            return json_safe_encode($result);
        }
        // With template
        if (!$this->is_naked) {
            return BeginHtml() . $result . EndHtml();
        }

        return $result;
    }

    //-------------------------------------------------------------------------

    public function action_list($template_id) {
        $this->template_id = (int) $template_id;
        $data = array();

        $this->ui_config('list');
        $this->ui_config->actionButtons[] = array(
            'caption'  => CONTROL_TEMPLATE_PARTIALS_ADD,
            'align'    => 'left',
            'location' => "template_fs.partials_add({$this->template_id})",
        );

        if ($node = nc_core()->input->fetch_get('deleteNode')) {
            $this->ui_config->treeChanges['deleteNode'][] = $node;
        }

        $data['partials'] = $this->nc_core->template->get_template_partials($this->template_id);

        return $this->view('template_partials/list', $data);
    }

    //-------------------------------------------------------------------------

    public function action_add($template_id) {
        global $NETCAT_PATH, $DIRCHMOD;

        $this->template_id = (int) $template_id;
        $data = array(
            'action' => 'add',
        );

        $this->ui_config('add', CONTROL_TEMPLATE_PARTIALS_NEW);
        $this->ui_config->actionButtons[] = array(
            'caption' => NETCAT_CUSTOM_ONCE_SAVE,
            'action'  => "nc.view.main('form').submit(); return false",
        );

        if (isset($_POST['partial_name'])) {
            $partial_name = $this->nc_core->input->fetch_post('partial_name');
            if (!$partial_name) {
                $this->error = CONTROL_TEMPLATE_PARTIALS_NAME_FIELD_REQUIRED_ERROR;
            }
            elseif (preg_match('/^[a-z0-9_-]+$/ui', $partial_name)) {
                $partial_source = $this->nc_core->input->fetch_post('partial_source');

                $data['partial_source'] = $partial_source;
                $data['partial_name']   = $partial_name;

                $partial_file = $this->nc_core->template->get_partials_path($this->template_id, $partial_name);

                if (!file_exists($partial_file)) {
                    $partials_dir = $this->nc_core->template->get_partials_path($this->template_id);
                    if (!is_dir($partials_dir)) {
                        mkdir($partials_dir, $DIRCHMOD);
                    }
                    file_put_contents($partial_file, $partial_source);
                    header("Location: {$NETCAT_PATH}action.php?ctrl=admin.template_partials&action=edit&fs=1&TemplateID={$template_id}&partial={$partial_name}&addNode=1");
                } else {
                    $this->error = CONTROL_TEMPLATE_PARTIALS_EXISTS_ERROR;
                }
            } else {
                $this->error = CONTROL_TEMPLATE_PARTIALS_NAME_FIELD_ERROR;
            }

        }

        return $this->view('template_partials/edit', $data);
    }

    //-------------------------------------------------------------------------

    public function action_edit($template_id, $partial_name) {
        $this->partial     = $partial_name;
        $this->template_id = (int) $template_id;

        $data = array(
            'action' => 'edit',
        );

        $this->ui_config('edit', CONTROL_TEMPLATE_PARTIALS . ' <small>' . $partial_name . '</small>');
        $this->ui_config->locationHash = "template.partials_edit({$this->template_id}, {$partial_name})";

        if (nc_core()->input->fetch_get('addNode')) {
            $node_id   = $template_id;
            $partial   = $partial_name;
            $fs_suffix = '_fs';

            $this->ui_config->treeChanges['addNode'][] = array(
                "parentNodeId" => "template_partials-{$node_id}",
                "nodeId"       => "template_partial-{$node_id}-{$partial}",
                "name"         => $partial,
                "href"         => "#template.partials_edit({$node_id}, {$partial})",
                "sprite"       => 'dev-com-templates',
                "buttons"      => array(
                    nc_get_array_2json_button(
                        CONTROL_TEMPLATE_PARTIALS_REMOVE,
                        "template{$fs_suffix}.partials_remove({$node_id}, {$partial})",
                        "nc-icon nc--remove nc--hovered"
                    )
                )
            );
        }

        $partial_file = $this->nc_core->template->get_partials_path($this->template_id, $partial_name);

        if (file_exists($partial_file)) {
            if (isset($_POST['partial_source'])) {
                $partial_source = $this->nc_core->input->fetch_post('partial_source');
                file_put_contents($partial_file, $partial_source);
            }

            $partial_source = file_get_contents($partial_file);

            $data['partial_name']   = $partial_name;
            $data['partial_source'] = $partial_source;
        } else {
            nc_print_status('Template not found', 'error');
            return;
        }

        $this->ui_config->actionButtons[] = array(
            'caption' => NETCAT_CUSTOM_ONCE_SAVE,
            'action'  => "nc.view.main('form').submit(); return false",
        );

        return $this->view('template_partials/edit', $data);
    }

    //-------------------------------------------------------------------------

    public function action_remove($template_id, $partial_name) {
        global $NETCAT_PATH;

        $this->partial     = $partial_name;
        $this->template_id = (int) $template_id;

        $this->ui_config('list');
        $this->ui_config->locationHash = "template.partials_list({$this->template_id})";

        $partial_file = $this->nc_core->template->get_partials_path($this->template_id, $partial_name);

        if (file_exists($partial_file)) {
            unlink($partial_file);
        }

        $this->is_naked = true;
        header("Location: {$NETCAT_PATH}action.php?ctrl=admin.template_partials&action=list&fs=1&TemplateID={$template_id}&deleteNode=template_partial-{$this->template_id}-{$this->partial}");
    }

    //-------------------------------------------------------------------------

    protected function ui_config($mode, $title = CONTROL_TEMPLATE_PARTIALS) {
        $this->ui_config = new ui_config(array(
            'headerText'   => $title,
            'treeMode'     => 'template_fs',
        ));

        $this->ui_config->locationHash     = "template.partials_{$mode}({$this->template_id})";
        if ($this->partial) {
            $this->ui_config->treeSelectedNode = "template_partial-{$this->template_id}-{$this->partial}";
        } else {
            $this->ui_config->treeSelectedNode = "template_partials-{$this->template_id}";
        }
    }

    //-------------------------------------------------------------------------
}