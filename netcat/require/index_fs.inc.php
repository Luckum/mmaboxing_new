<?php

if (!$isNaked || $admin_modal) {
    $template_view = new nc_template_view($nc_core->TEMPLATE_FOLDER, $nc_core->db);

    if (!$templatePreview) {
        $template_view->load_template($template, $template_env['File_Path']);
    } else {
        $template_view->load_template($templatePreview, $template_env['File_Path'], $is_preview = true);
    }

    if ($nc_core->template->get_current("CustomSettings") && $nc_core->subdivision->get_current('Template_ID') == $template) {
        require_once($nc_core->ADMIN_FOLDER . "array_to_form.inc.php");
        $nc_a2f = new nc_a2f($nc_core->template->get_current("CustomSettings"));
        $nc_a2f->set_value($nc_core->subdivision->get_current("TemplateSettings"));
        $template_settings = $nc_a2f->get_values_as_array();
    }

    if ($nc_core->template->get_current("CustomSettings") && $nc_core->subdivision->get_current('Template_ID') != $template) {
        require_once($nc_core->ADMIN_FOLDER . "array_to_form.inc.php");
        $nc_a2f = new nc_a2f($nc_core->template->get_current("CustomSettings"), array());
        $template_settings = $nc_a2f->get_values_as_array();
    }

    if (!$templatePreview) {
        $array_settings_path = $template_view->get_all_settings_path_in_array();
        foreach ($array_settings_path as $path) {
            include $path;
        }
    }

    $template_view->fill_fields();

    if ($templatePreview) {
        eval('?>' . $template_view->get_settings());
    }

    $template_env['Header'] = $template_view->get_header();
    $template_env['Footer'] = $template_view->get_footer();

    // %FIELD replace with inherited template field value
    $template_env = $nc_core->template->convert_subvariables($template_env);
}

$template_header = "";
$template_footer = "";

if (!$isNaked && $nc_core->get_page_type() != 'rss' && $nc_core->get_page_type() != 'xml') {
    $template_header = $template_env['Header'];
    $template_footer = $template_env['Footer'];

    if ($nc_core->subdivision->get_current("UseMultiSubClass") == 2 && !$nc_core->get_variable("inside_admin")) {
        $template_header_code = '';
        $array_headers_path = $template_view->get_all_header_path_in_array();
        foreach ($array_headers_path as $path) {
            $template_header_code .= file_get_contents($path);
        }
        if (strpos($template_header_code, '_browse_cc') === false) {
            $template_header .= s_browse_cc(array(
                    'prefix' => "<div id='browse_cc'>",
                    'suffix' => "</div><br />",
                    'active' => "<span>%NAME</span>",
                    'active_link' => "<span>%NAME</span>",
                    'unactive' => "<span> <a href=%URL>%NAME</a></span>",
                    'divider' => " &nbsp; "
            ));
        }
    }

    eval($nc_core->template->get_current("Settings"));
    // add system CSS styles in admin mode

    if ($nc_core->get_variable("admin_mode") || $nc_core->get_variable("inside_admin")) {
        // reversive direction!
        $template_header = nc_insert_in_head($template_header, "<script type='text/javascript' src='".$nc_core->get_variable("ADMIN_PATH")."js/package.js'></script>\r\n");
        $template_header = nc_insert_in_head($template_header, nc_js(), true);
    }

    if (!$nc_core->catalogue->get_current('ncMobile') && $nc_core->catalogue->get_current('ncMobileSrc')) {
        $template_mobile_js = "<script type='text/javascript' src='" . $nc_core->get_variable("ADMIN_PATH") . "js/mobile.js'></script>\r\n";
        $template_header = nc_insert_in_head($template_header, $template_mobile_js, true);
        $mobile_data = $nc_core->catalogue->get_mobile(0, true);
        $_SESSION['no_mobile_redirect'] = 0;
        if ($_SERVER['HTTP_REFERER'] && @strpos($_SERVER['HTTP_REFERER'], $mobile_data['Domain']) !== false) {
            $_SESSION['no_mobile_redirect'] = 1;
        }

        if ($mobile_data['Catalogue_ID'] && $mobile_data['ncMobileRedirect'] && !$_SESSION['no_mobile_redirect']) {
            $_SESSION['no_mobile_redirect'] = 1;
            $device = $nc_core->return_device();
            $mobile_href = 'http://' . $nc_core->subdivision->get_alternative_link();

            if ($mobile_data['ncMobileIdentity'] == 1) {
                if ($device != 'desktop') {
                    header('Location: ' . $mobile_href);
                    exit;
                }
            } elseif ($mobile_data['ncMobileIdentity'] == 2) {
                if ($nc_core->mobile_screen()) {
                    header('Location: ' . $mobile_href);
                    exit;
                }
            } else {
                if ($device != 'desktop' && $nc_core->mobile_screen()) {
                    header('Location: ' . $mobile_href);
                    exit;
                }
            }
        }
    }

    // metatag noindex
    if ($nc_core->subdivision->get_current("DisallowIndexing") == 1) {
        $template_header = nc_insert_in_head($template_header, "<meta name='robots' content='noindex' />");
    }

    // $_GET['rand'] - подставляется в админке при просмотре страницы внутри фрейма, это нужно для снятия кэширования фрейма
    // используем этот параметр для определения отображения QuickBar
    // Если нет AUTH_USER_ID, то нужно произвести авторизацию
    $quick_mode = false;
    if ($nc_core->get_variable("AUTHORIZATION_TYPE") != 'http') {
        if ($nc_core->get_settings('QuickBar') && !isset($_GET['rand'])) {
            $cookie_domain = ($nc_core->modules->get_vars('auth', 'COOKIES_WITH_SUBDOMAIN') ? str_replace("www.", "", $nc_core->get_variable("HTTP_HOST")) : NULL);
            if ($nc_core->input->fetch_cookie('QUICK_BAR_CLOSED') == 0 || $nc_core->input->fetch_cookie('QUICK_BAR_CLOSED') == -1) {
                require_once ($nc_core->get_variable("INCLUDE_FOLDER") . "quickbar.inc.php");
                $template_header = nc_quickbar_in_template_header($template_header, $File_Mode);
                $quick_mode = true;
                setcookie("QUICK_BAR_CLOSED", -1, time() + $nc_core->get_variable("ADMIN_AUTHTIME"), "/", $cookie_domain);
            } else {
                setcookie("QUICK_BAR_CLOSED", 1, time() + $nc_core->get_variable("ADMIN_AUTHTIME"), "/", $cookie_domain);
            }
        }
    }
}

// openstat
if (NC_OPENSTAT_COUNTER) {
    if (!$admin_mode && !$inside_admin) {
        $pos = nc_strpos($template_header, NC_OPENSTAT_COUNTER);
        if ($pos !== FALSE) {
            $template_header = nc_substr($template_header, 0, $pos) . nc_openstat_get_code(1) . nc_substr($template_header, $pos + nc_strlen(NC_OPENSTAT_COUNTER));
            $template_header = str_replace(NC_OPENSTAT_COUNTER, "", $template_header);
            $template_footer = str_replace(NC_OPENSTAT_COUNTER, "", $template_footer);
        } else {
            $pos = nc_strpos($template_footer, NC_OPENSTAT_COUNTER);
            if ($pos !== FALSE) {
                $template_footer = nc_substr($template_footer, 0, $pos) . nc_openstat_get_code(1) . nc_substr($template_footer, $pos + nc_strlen(NC_OPENSTAT_COUNTER));
                $template_footer = str_replace(NC_OPENSTAT_COUNTER, "", $template_footer);
            }
        }
    }
}

if (!$check_auth && NC_AUTH_IN_PROGRESS !== 1) {

    if (!$templatePreview) {
        echo $template_header;
    } else {
        eval('?>' . $template_header);
    }

    if ($AUTH_USER_ID || (!$AUTH_USER_ID && !$nc_core->modules->get_vars('auth') )) {
        if ($nc_core->inside_admin) {
            nc_print_status(NETCAT_MODERATION_ERROR_NORIGHTS, 'error');
        } else {
            print NETCAT_MODERATION_ERROR_NORIGHTS;
        }
    } elseif (!$AUTH_USER_ID && $nc_core->modules->get_vars('auth')) {
        $nc_auth->login_form();
    }

    if (!$templatePreview) {
        echo $template_footer;
    } else {
        eval('?>' . $template_footer);
    }

    exit;
}

if (!$message && $action == "full")
    exit;

if ($AUTH_USER_ID) {
    $current_user = $nc_core->user->get_by_id($AUTH_USER_ID);
}
?>