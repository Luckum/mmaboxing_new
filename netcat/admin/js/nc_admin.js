// Resize modal on window.resize
//
// Если у элемента, из которого был создан modal, в качестве data-свойства onResize
// установлена функция [.data('onResize', someFunction)], она будет выполнена при
// событии resize
function nc_register_modal_resize_handler() {
    if ( ! $nc._resize_modal_event) {
        $nc(window).resize(function(){
            var modal = $nc('#simplemodal-container').not(".simplemodal-container-fixed-size");
            if (modal.length !== 0) {
                var w = $nc(window).width() - 100 * 2;
                var h = $nc(window).height() - 100 * 2;
                w = w > 1200 ? 1200 : (w < 600 ? 600 : w);

                modal.css({width: w, height: h});

                var modalResizeHandler = modal.find(".simplemodal-data").data("onResize");
                if (modalResizeHandler && typeof modalResizeHandler == "function") {
                    modalResizeHandler(modal);
                }
            }
        });

        $nc._resize_modal_event = true;
    }
}


function nc_form(url, backurl, target, modalWindowSize, httpMethod, httpData) {
    if ( ! target && window.event) {
        target = window.event.target || window.event.srcElement;
    }

    if (!modalWindowSize) {
        modalWindowSize = null;
    }

    nc_register_modal_resize_handler();

    var $target = target ? $nc(target) : false;
    if ($target) {
        if ($target.hasClass('nc--disabled')) {
            return;
        }
        $target.addClass('nc--disabled');
    }

    if (!backurl) backurl = '';


    nc.process_start('nc_form()');



    if (!httpMethod) {
        httpMethod = 'GET';
    }

    if (!httpData) {
        httpData = {};
    }

    $nc.ajax({
        'type' : httpMethod,
        'url': url + '&isNaked=1',
        'data': httpData,
        'success' : function(response) {

            nc.process_stop('nc_form()');
            if ($target) $target.removeClass('nc--disabled');

            nc_remove_content_for_modal();
            $nc('body').append('<div style="display: none;" id="nc_form_result"></div>');
            $nc('#nc_form_result').html(response).modal({
                onShow: function (dialog) {
                    $nc('#nc_form_result').children().not('.nc_admin_form_menu, .nc_admin_form_body, .nc_admin_form_buttons').hide();

                    var container = dialog.container;

                    if (modalWindowSize) {
                          var currentLeft = parseInt(container.css('left'));
                          var currentWidth = container.width();

                          var currentTop = parseInt(container.css('top'));
                          var currentHeight = container.height();

                          container.css({
                                  width: modalWindowSize.width,
                                  height: modalWindowSize.height,
                                  left: currentLeft + (currentWidth - modalWindowSize.width) / 2,
                                  top: currentTop + (currentHeight - modalWindowSize.height) / 2
                          }).addClass('simplemodal-container-fixed-size');
                    }
                    else {
                        container.removeClass('simplemodal-container-fixed-size');
                        $nc(window).resize();
                    }

                    $nc('#nc_form_result #adminForm').append("<input type='hidden' name='nc_token' value='" + nc_token + "' />");
                },
                closeHTML: "<a class='modalCloseImg'></a>",
                onClose: function (e) {
                    if (typeof CKEDITOR != 'undefined' && CKEDITOR.instances) {
                        for (var instance_name in CKEDITOR.instances) {
                            if (!/_edit_inline$/i.test(instance_name)) {
                                CKEDITOR.instances[instance_name].destroy();
                            } else {
                                var $element = $nc('#' + instance_name);
                                var oldValue = $element.attr('data-oldvalue');
                                $element.html(oldValue);
                            }
                        }

                        if (typeof(CKEDITOR.nc_active_inline) != 'undefined') {
                        CKEDITOR.nc_active_inline = false;
                        }
                    }
                    $nc.modal.close();
                    $nc(document).unbind('keydown.simplemodal');
                    nc_remove_content_for_modal();
                }
            });

        $nc('#nc_form_result #adminForm').ajaxForm({
            beforeSerialize: function() {
                if (typeof CKEDITOR != 'undefined' && CKEDITOR.instances) {
                    for (var instance_name in CKEDITOR.instances) {
                        var $textarea = $nc('textarea[name=' + instance_name + ']');
                        if ($textarea.length) {
                            $textarea.val(CKEDITOR.instances[instance_name].getData());
                        }
                    }
                }
                if (window.FCKeditorAPI) {
                    for (fckeditorName in FCKeditorAPI.Instances) {
                        var editor = FCKeditorAPI.GetInstance(fckeditorName);
                        if ( editor.IsDirty() ) {
                            $nc('#' + fckeditorName).val( editor.GetHTML() );
                        }
                    }
                }
                //$nc('#nc_form_result #adminForm textarea.has_codemirror').each(function() {
                //      $nc(this).data('codemirror').save();
                //});
                CMSaveAll();
            },

            // modal layer button submit
            success: function(response, status, event, form) {

                nc.process_stop('nc_form()');
                var error = nc_check_error(response);
                if (error) {
                    var $form_buttons = $nc('.nc_admin_form_buttons');
                    $form_buttons.append(
                        "<div id='nc_modal_error' class='nc-alert nc--red' style='position:absolute; z-index:3000; width:"+($form_buttons.width()-55)+"px; bottom:70px; text-align:left; line-height:20px '>"
                        + "<div class='simplemodal_error_close'></div>"
                        + "<i class='nc-icon-l nc--status-error'></i>"
                        + error
                        + "</div>");
                    $nc('.simplemodal_error_close').click(function(){
                        $nc('#nc_modal_error').remove();
                    });
                    return false;
                }

                // if (response == 'OK') {
                //     window.location.reload(true);
                //     return false;
                // }

                var cc = form.find('input[name=cc]').val();

                var loc = window.location,
                    newUrlMatch = (/^NewHiddenURL=(.+?)$/m).exec(response), // в ответе есть строка "NewHiddenUrl=something"
                    newUrl = newUrlMatch ? $nc.trim(newUrlMatch[1]) : null; // новый HiddenURL страницы

                if ((/^ReloadPage=1$/m).test(response)) { // в ответе есть строка "ReloadPage=1"
                    // не режим "редактирование", изменился путь страницы
                    if (newUrl && !(/\.php/.test(window.location.pathname))) {
                    // сохранить имя страницы, если оно было (изменение свойств раздела со страницы объекта)
                    var pageNameMatch = /\/([^\/]+)$/.exec(loc.pathname);
                    if (pageNameMatch) { newUrl += pageNameMatch[1]; }
                        loc.pathname = newUrl;
                    }
                    else {
                        loc.reload(true);
                    }
                    return false;
                }
                else {
                    $nc.ajax({
                        'type' : 'GET',
                        'url': (backurl ? backurl : nc_page_url()) + '&isNaked=1&admin_modal=1&cc_only=' + cc,
                        success: function(response) {
                            nc_update_admin_mode_content(response, null, cc);
                            $nc.modal.close();
                        }
                    });
                }
        }
        });
    return false;
    }
});
}

function nc_action_message(url, httpMethod, httpData) {
    var ajax_url = url + '&isNaked=1&posting=1' + '&nc_token=' + nc_token;

    if (!httpMethod) {
        httpMethod = 'GET';
    }

    if (!httpData) {
        httpData = {};
    }

    $nc.ajax({
        'type' : httpMethod,
        'data': httpData,
        'url': ajax_url,
        'success' : function(response) {
            response = $nc.trim(response);
            if (response == 'deleted') {
                $nc('body', nc_get_current_document()).append("<div id='formAsyncSaveStatus'>Объект помещен в корзину</div>");
                $nc('div#formAsyncSaveStatus', nc_get_current_document()).css({
                    backgroundColor: '#39B54A'
                });
                setTimeout(function () {
                    $nc('div#formAsyncSaveStatus', nc_get_current_document()).remove();
                },
                1000);
            }

            if (response == 'trashbin_disabled') {

                nc_print_custom_modal();

                $nc('div#nc_cart_confirm_footer button.nc_admin_metro_button').click(function() {
                    $nc.modal.close();
                    nc_action_message(url + '&force_delete=1')
                });

                return null;
            }

            var $status_message = $nc('<div />').html(response).find('#statusMessage');

            $nc.ajax({
                'type': 'GET',
                'url' : nc_page_url() + '&isNaked=1',
                'success' : function(response) {
                    response ? nc_update_admin_mode_content(response, $status_message)
                    : nc_page_url(nc_get_back_page_url());
                }
            });
    }
    });
}

function nc_is_frame() {
    return typeof mainView != "undefined";
}

function nc_get_back_page_url() {
    return NETCAT_PATH + '?' + nc_page_url().match(/sub=[0-9]+/) + (nc_is_frame() ? '&inside_admin=1' : '');
}

function nc_page_url(url) {
    return nc_correct_page_url(url ? nc_get_location().href = url : nc_get_location().href);
}

function nc_correct_page_url(url) {
    url = url.replace(/#.?$/, '');
    return url.indexOf('?') == -1 ? url + '?' : url ;
}

function nc_update_admin_mode_content(content, $status_message, cc) {
    cc = cc || '';
    var $nc_admin_mode_content = $nc('#nc_admin_mode_content' + cc, nc_get_current_document());

    if ( ! $nc_admin_mode_content.length) {
        $nc_admin_mode_content = $nc('div.nc_admin_mode_content', nc_get_current_document());
    }

    $nc_admin_mode_content.prev('#statusMessage').remove();
    $nc_admin_mode_content.html(content);

    if (typeof($status_message) != 'undefined' && $status_message) {
        $nc_admin_mode_content.before($status_message);
    }
}

function nc_get_current_document() {
    return nc_is_frame() ? mainView.oIframe.contentDocument : document;
}

function nc_get_location() {
    return nc_is_frame() ? mainView.oIframe.contentWindow.location : location;
}

function nc_remove_content_for_modal() {
    $nc('#nc_form_result').remove();
    if (typeof(resize_layout) != 'undefined') {
        resize_layout();
    }
}

function nc_add_inside_admin_parameter() {
    var re = new RegExp(NETCAT_PATH + '[^/]+$'),
        add_params = function(href) {
            if (href && href.indexOf("inside_admin=") == -1 && re.test(href)) {
                var location_parts = href.split("#"),
                    path_and_query = location_parts[0].split("?");
                return path_and_query[0] +
                       "?" + "inside_admin=1" +
                       (path_and_query[1] ? '&' + path_and_query[1] : '') +
                       (location_parts[1] ? '#' + location_parts[1] : '');
            }
            else {
                return href;
            }
        },
        iframe_doc = mainView.oIframe.contentDocument,
        admin_form = $('#adminForm, #nc_form_result form, .nc-modal-dialog form');

    $nc('a', iframe_doc).add('a', admin_form)
        .each(function() { this.href = add_params(this.href); });

    $nc('form', iframe_doc).add(admin_form)
        .each(function() { var f = $nc(this); f.attr('action', add_params(f.attr('action'))); });
}

function nc_password_change() {
    var $password_change = $nc('#nc_password_change');
    $password_change.modal({
        closeHTML: "",
        containerId: 'nc_small_modal_container',
        onShow: function () {
            $nc('div.simplemodal-wrap').css({padding:0, overflow:'inherit'});
            var $form = $password_change.find('form');
            $nc('#nc_small_modal_container').addClass('nc-shadow-large').css({width:$form.width(), height:$form.height()});
            $nc(window).resize();
        }
    });

    // $nc('.password_change_simplemodal_container').css({
    //       backgroundColor: 'white',
    // });

    //FIXME: проверка формы изменения пароля перед отправкой
    if (false) {
        var $submit = $password_change.find('button[type=submit]');
        // var button = $nc('div#nc_password_change_footer button.nc_admin_metro_button');
        $submit.unbind();
        $submit.click(function() {
            if ($nc('input[name=Password1]').val() != $nc('input[name=Password2]').val()) {
                $nc('div#nc_password_change_footer').append(
                    "<div id='nc_modal_error' style='position: absolute; z-index: 3000; width: 200px; border: 2px solid red;background-color: white; bottom: 190px; text-align: left; padding: 10px;'>"
                    + "<div class='simplemodal_error_close'></div>"
                    + ncLang.UserPasswordsMismatch
                    + "</div>");
                return false;
            }
            $nc('div#nc_password_change_body form').submit();
        });
    }

    $nc('div#nc_password_change form').ajaxForm({
        success : function() {
            $nc.modal.close();
        }
    });
}

$nc('button.nc_admin_metro_button_cancel').click(function() {
    $nc.modal.close();
});

function nc_check_error(response) {
    var div = document.createElement('div');
    div.innerHTML = response;
    return $nc(div).find('#nc_error').html();
}

$nc('.simplemodal_error_close').click(function() {
    $nc('#nc_modal_error').remove();
});

function CMSaveAll() {
    /* // pre method
    var editors = null;

    if ( nc_is_frame() ) {
        editors = mainView.oIframe.contentWindow.CMEditors;
    }
    else {
        editors = window.CMEditors;
    }
    if ( typeof(editors) != 'undefined' ) {
        for(var key in editors) {
            editors[key].save();
        }
    }*/

    $nc('textarea.has_codemirror').each(function() {
        $nc(this).data('codemirror').save();
    });
}

function nc_print_custom_modal() {
    $nc('body').append("<div id='nc_cart_confirm' style='display: none;'></div>");

    var cart_confirm = $nc('#nc_cart_confirm');

    cart_confirm.append("<div id='nc_cart_confirm_header'></div>");
    cart_confirm.append("<div id='nc_cart_confirm_body'></div>");
    cart_confirm.append("<div id='nc_cart_confirm_footer'></div>");

    $nc('#nc_cart_confirm_header').append("<div><h2 style='padding: 0px;'>" + ncLang.DropHard + "</h2></div>");
    $nc('#nc_cart_confirm_footer').append("<button type='button' class='nc_admin_metro_button nc-btn nc--blue'>" + ncLang.Drop + "</button>");
    $nc('#nc_cart_confirm_footer').append("<button type='button' class='nc_admin_metro_button_cancel nc-btn nc--red nc--bordered nc--right'>" + ncLang.Cancel + "</button>");

    cart_confirm.modal({
        closeHTML: "",
        containerId: 'cart_confirm_simplemodal_container',
        onShow: function () {
            $nc('.simplemodal-wrap').css({
                backgroundColor: 'white'
            });
        },
        onClose : function () {
            $nc.modal.close();
            $nc('#nc_cart_confirm').remove();
        }
    });

    $nc('div#nc_cart_confirm_footer button.nc_admin_metro_button_cancel').click(function() {
        $nc.modal.close();
    });

    $nc('div#nc_cart_confirm_footer button.nc_admin_metro_button').click(function() {
        if (typeof callback_on_confirm == 'function'){
            callback_on_confirm();
            $nc.modal.close();
        }
    });

}


function nc_print_custom_modal_callback(callback){
    $nc('body').append("<div id='nc_cart_confirm' style='display: none;'></div>");

    var cart_confirm = $nc('#nc_cart_confirm');

    cart_confirm.append("<div id='nc_cart_confirm_header'></div>");
    cart_confirm.append("<div id='nc_cart_confirm_body'></div>");
    cart_confirm.append("<div id='nc_cart_confirm_footer'></div>");

    $nc('#nc_cart_confirm_header').append("<div><h2 style='padding: 0px;'>" + ncLang.DropHard + "</h2></div>");
    $nc('#nc_cart_confirm_footer').append("<button type='button' class='nc_admin_metro_button_cancel nc-btn nc--bordered nc--blue'>" + ncLang.Cancel + "</button>");
    $nc('#nc_cart_confirm_footer').append("<button type='button' class='nc_admin_metro_button nc-btn nc--red nc--bordered nc--right'>" + ncLang.Drop + "</button>");

    cart_confirm.modal({
        closeHTML: "",
        containerId: 'cart_confirm_simplemodal_container',
        onShow: function () {
            $nc('.simplemodal-wrap').css({
                backgroundColor: 'white'
            });
        },
        onClose : function () {
            $nc.modal.close();
            $nc('#nc_cart_confirm').remove();
        }
    });

    $nc('div#nc_cart_confirm_footer button.nc_admin_metro_button_cancel').click(function() {
        $nc.modal.close();
    });

    $nc('div#nc_cart_confirm_footer button.nc_admin_metro_button').click(function() {
        if (typeof callback == 'function'){
            callback();
            $nc.modal.close();
        }
    });
}

function prepare_message_form() {
    $nc(function() {
        $nc('#adminForm').wrapInner('<div class="nc_admin_form_main">');
        $nc('#adminForm').append($nc('#nc_seo_append').html());
        $nc('#adminForm').append('<input type="hidden" name="isNaked" value="1" />');
        $nc('#nc_seo_append').remove();
    });

    //var nc_admin_form_values = $nc('#adminForm').serialize();

    $nc('#nc_show_main').click(function() {
        $nc('.nc_admin_form_main').show();
        $nc('.nc_admin_form_seo').hide();
    });

    $nc('#nc_show_seo').click(function() {
        $nc('.nc_admin_form_main').hide();
        $nc('.nc_admin_form_seo').show();
    });

    $nc('#nc_object_slider_menu li').click(function(){
        $nc('#nc_object_slider_menu li').removeClass('button_on');
        $nc(this).addClass('button_on');
    });

    $nc('.nc_admin_metro_button_cancel').click(function() {
        $nc.modal.close();
    });

    $nc('.nc_admin_metro_button').click(function() {
        if ( $nc(this).hasClass('nc--loading') ) return;
        nc.process_start('nc_form()', this);
        $nc('#adminForm').submit();
    });
    InitTransliterate();
}

function nc_typo_field(field) {
    var string;
    if (typeof CKEDITOR != 'undefined' && CKEDITOR.instances && typeof(CKEDITOR.instances[field]) != 'undefined') {
        string = CKEDITOR.instances[field].getData();
        string = Typographus_Lite_UTF8.process(string);
        CKEDITOR.instances[field].setData(string);
    } else if (typeof FCKeditorAPI != 'undefined' && FCKeditorAPI.Instances && typeof(FCKeditorAPI.Instances[field]) != 'undefined') {
        var editor = FCKeditorAPI.GetInstance(field);
        string = editor.GetHTML();
        string = Typographus_Lite_UTF8.process(string);
        editor.SetHTML(string);
    } else {
        var $textarea = $nc('TEXTAREA[name=' + field + ']');
        string = $textarea.val();
        string = Typographus_Lite_UTF8.process(string);
        $textarea.val(string);
    }
}