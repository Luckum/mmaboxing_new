<?php
ob_start();
require_once 'old/header.inc.php';

$UI_CONFIG = new ui_config_module_netshop('admin', 'forms');
$UI_CONFIG->subheaderText    = NETCAT_MODULE_NETSHOP_FORMS;
$UI_CONFIG->treeSelectedNode = "netshop-forms";
$UI_CONFIG->toolbar          = false;
$UI_CONFIG->tabs             = false;


$nc_core    = nc_core::get_object();
$shop       = nc_netshop::get_instance();
$ui         = $nc_core->ui;
$form_types = $shop->forms->get_objects();


if (empty($phase)) {
    $phase = 'index';
}

switch ($phase) {

    //--------------------------------------------------------------------------

    case 'index':
        $table = $ui->table()->wide()->striped()->bordered();
        $table->thead()
            ->th(NETCAT_MODULE_NETSHOP_FORMS_TYPE)
            ->th();
        $tr          = $table->row();
        $tr->name    = $tr->td();
        $tr->actions = $tr->td()->text_right();

        foreach ($form_types as $key => $form) {
            $actions = $ui->html->a(NETCAT_MODULE_NETSHOP_SETTINGS)->icon('settings')->href('?phase=settings&type=' . $key);

            $tr->actions->text($actions);
            $tr->name->text($form->name());
            $table->add_row($tr);
        }

        echo $table;
        break;

    //--------------------------------------------------------------------------

    case 'settings':
        $type = $nc_core->input->fetch_get('type');

        if ( empty($form_types->$type) ) {
            echo $ui->alert->error("Form type '{$type}' not found");
            return;
        }

        $form_type = $form_types->$type;

        $data = $form_type->get_settings();

        $shop->forms->edit_mode = true;

        if (count($_POST)) {
            if ($form_type->set_settings($nc_core->input->fetch_post())) {
                echo $ui->alert->success(NETCAT_MODULE_NETSHOP_ADMIN_SAVE_OK);
                $data = $nc_core->input->fetch_post();
            }
        }

        $UI_CONFIG->actionButtons[] = array(
            "id"      => "submit",
            "caption" => NETCAT_MODULE_NETSHOP_SAVE,
            "action"  => "mainView.submitIframeForm()"
        );

        $tpl = $form_type->get_template();
        $ui_form = $nc_core->ui->form('?phase=settings&type='.$type)->horizontal();
        // foreach ($form_type->settings as $setting_key => $setting_name ) {
        //     $ui_form->add_row($setting_name)->string($setting_key,$data[$setting_key])->xlarge();
        // }

        $ui_form[] = $ui->html->div($tpl)->class_name('nc-padding-20')->style('min-width:800px');
        echo $ui_form;

        break;

    //--------------------------------------------------------------------------

    case 'print':
        $type                 = $nc_core->input->fetch_get('type');
        $order_id             = (int)$nc_core->input->fetch_get('order');
        $GLOBALS['catalogue'] = (int)$nc_core->input->fetch_get('catalogue');

        if ( empty($form_types->$type) ) {
            echo $ui->alert->error("Form type '{$type}' not found");
            return;
        }
        if ( ! $order_id ) {
            echo $ui->alert->error("Order not set");
            return;
        }
        echo $form_types->$type->get_template($order_id);


        break;
}


EndHtml();