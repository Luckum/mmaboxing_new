/**
 *
 * - Обработчики нажатий в формах внутри основного фрейма.
 * - messageInitDrag
 *
 */

/**
 * Обработка нажатия на элементы с атрибутом data-submit="1":
 * — если установлен атрибут data-confirm-message, будет создан запрос
 *   на подтверждение действия с указанным текстом;
 * — будет создана форма (метод POST) со значениями, перечисленными
 *   в параметре data-post в формате JSON
 */
$nc(function() {
    var buttons = $nc('[data-submit=1]');
    buttons.click(function(e) {
        var button = $nc(this),
            data = button.data('post'),
            message = button.data('confirmMessage');
        if (!message || confirm(message)) {
            var form = $nc('<form/>', { method: 'post', action: '?' }).hide().appendTo('body');
            for (var k in data) {
                form.append($nc('<input/>', { type: 'hidden', name: k, value: data[k] }));
            }
            form.submit();
        }
    });
    // prevent middle click on these "buttons":
    $nc(document).on('click', buttons, function(e) {  // other ways do not work (jQ 1.10)
        if ($nc(e.target).closest('a').data('submit')) { e.preventDefault(); }
    });
});


if (typeof formAsyncSaveEnabled == 'undefined') {
    formAsyncSaveEnabled = false;
}

bindEvent(window, 'load', function() {
    bindEvent(document.body, 'keyup', formKeyHandler);
});

/**
  * Form keyhandler (submits on enter, saves with XHR on Ctrl+Shift+S
  * @global {Boolean} formAsyncSaveEnabled
  */
function formKeyHandler(e) {
    if (!e && window.event) e = window.event;

    //var kEnter = (e.keyCode==13),  // Enter pressed

    // Ctrl + Shift + S
    var kSave  = (
		formAsyncSaveEnabled &&
		e.shiftKey &&
		e.ctrlKey &&
		e.keyCode == (nc_save_keycode ? Math.round(nc_save_keycode) : 83)
	);

    if (!kSave) return;// !kEnter &&

    var srcElement = (e.srcElement? e.srcElement : e.target);

    // SUBMIT on <ENTER>
    /*if (kEnter) {
		if (srcElement.tagName == 'INPUT' && srcElement.type=='text' && !srcElement.getAttribute('nosubmit')) {
		  srcElement.form.submit();
		  return;
		}
		else {
		  return;
		}
	  }*/

    // SAVE on <CTRL+SHIFT+S>
    if (kSave) {
        // update CodeMirror layers
        CMSaveAll();

		var iframe = false;
		$nc('iframe', parent.document).each(function() {
			if ( $nc(this).attr('id') == 'mainViewIframe' ) {
				iframe = true;
			}
		});

        var oForm = srcElement.form ? srcElement.form : "";
        formAsyncSave(oForm, 0, 'formSaveStatus(1);');

        // inside_admin
        if (iframe) {
			parent.mainView.chan = 0;
			parent.mainView.displayStar(0);
		}

        return;
    }

}


/**
  * Form ajax saver
  * @param,String or object
  */
function formAsyncSave(form, statusHandlers, posteval) {
    if (!formAsyncSaveEnabled) {
        return;
    }

    var oForm;

    // object
    if (typeof form == 'object' && form.tagName=='FORM') {
        oForm = form;
    }
    // get the form by ID
    if (typeof form == 'string') {
        oForm = document.getElementById(form);
    }
    // if it is not clear yet - save the FIRST form
    if (typeof oForm != 'object') {
        oForm = document.getElementsByTagName("FORM")[0];
    }
    // no form!
    if (typeof oForm != 'object') {
        return false;
    }

    if ( oForm.onsubmit ) oForm.onsubmit();

    // collect form values into array
    var values = [];
    for (var i=0; i < oForm.length; i++) {
        var el = oForm.elements[i];
        if (el.tagName=="SELECT") {
            values[el.name] = el.options[el.options.selectedIndex].value;
        }
        else if (el.tagName=="INPUT" && (el.type=="checkbox" || el.type=="radio")) {
            if (el.checked) values[el.name] = el.value;
        }
        else if (el.name && el.value != undefined) {
            values[el.name] = el.value;
        }
    }

    values["NC_HTTP_REQUEST"] = 1; // предупредить сервер, что данные переданы через Ajax в кодировке utf8

    if ( !statusHandlers ) statusHandlers = {
        '*': 'formSaveStatus(this.xhr);'
    };

    var xhr = new httpRequest(1); // Async request
    xhr.request('POST', oForm.action, values, statusHandlers);

    if ( posteval ) eval(posteval);
}

/**
  * Показать результат сохранения при помощи XHR
  * @param {Object} xhr   XHR object
  */
function formSaveStatus(xhr) {
    var dst = document.getElementById("formAsyncSaveStatus");
    if (!dst) {
        dst = createElement("DIV", {
            "id": "formAsyncSaveStatus"
        }, document.body);
    }

    dst.style.visibility = 'visible';
    dst.style.opacity = 1;
    dst.style.zIndex = 20000;

    dst.className = 'form_save_in_progress';
    dst.innerHTML = NETCAT_HTTP_REQUEST_SAVING;

    dst.style.top = Math.round(($nc('body').height() - $nc(dst).height()) / 2) + 'px';

    if (xhr.readyState && xhr.readyState > 3) {
        var errorMessage = "";

		var iframe = false;
		$nc('iframe', parent.document).each(function() {
			if ( $nc(this).attr('id') == 'mainViewIframe' ) {
				iframe = true;
			}
		});

		// modal layer update
        if (!iframe) {
			$nc.ajax({
				'type' : 'GET',
				'url': nc_page_url() + '&isNaked=1',
				success: function(response) {
					nc_update_admin_mode_content(response);
					$nc.modal.close();
				}
			});
		}

        if (xhr.status == "200") {
            var result = {};

            try {
                eval("var result = " + xhr.responseText);
            }
            catch (e) {
                if (xhr.responseText) errorMessage = xhr.responseText;
            }

            if (result.error) {
                alert(result.error);
                errorMessage = result.error;
            }
            else {
                if (typeof(result.ui_config) != 'undefined' && typeof(parent.mainView) != 'undefined') {
                    var newSettings = result.ui_config;
                    parent.mainView.setHeader(newSettings.headerText, newSettings.subheaderText);

                    var tree;
                    if (newSettings.treeChanges && (tree = parent.document.getElementById('treeIframe').contentWindow.tree)) {
                        for (var method in newSettings.treeChanges) {
                            if (typeof tree[method]=='function' && newSettings.treeChanges[method].length) {
                                for (var i=0; i < newSettings.treeChanges[method].length; i++) {
                                    // call method in the tree
                                    tree[method](newSettings.treeChanges[method][i]);
                                }
                            }
                        }
                    }
                }

                dst.className = 'form_save_ok';
                dst.innerHTML = NETCAT_HTTP_REQUEST_SAVED;
                setTimeout(function () {$nc(dst).remove();}, 2500);
            }
        } else {
            errorMessage = xhr.status + ". " + xhr.statusText;
        }

        if (errorMessage) {
            dst.className = 'form_save_error';
            dst.innerHTML = NETCAT_HTTP_REQUEST_ERROR;
            dst.error = errorMessage;
            setTimeout(function () {$nc(dst).remove();}, 5000);
        }
    }
}

function showFormSaveError() {
    alert(document.getElementById('formAsyncSaveStatus').error);
}

function loadCustomTplSettings(catalogueId, subdivisionId, templateId) {
    var is_parent_template = $nc('select[name=Template_ID] option:first').html() == $nc('select[name=Template_ID] option').filter(':selected').html();
    $nc('input[name=is_parent_template]').val(is_parent_template);
    $nc("#customTplSettings").html("");
    $nc("#loadTplWait").show();
    var xhr = new httpRequest;
    xhr.request('GET', top.ADMIN_PATH + 'template/custom_settings.php', {
        catalogue_id: catalogueId,
        sub_id: subdivisionId,
        template_id: templateId,
        is_parent_template: is_parent_template
    });
    // synchronous HTML-HTTP-request:
    document.getElementById('customTplSettings').innerHTML = xhr.getResponseText();
    if (templateId != 0) {
        document.getElementById('templateEditLink').onclick = function () {
            var suffix = File_Mode_IDs.indexOf('|' + templateId + '|') != -1 ? '_fs' : '';
            window.open(top.ADMIN_PATH + '#template' + suffix + '.edit(' + templateId + ')', 1)
        }
        $nc("#templateEditLink").removeAttr("disabled");
    }

    $nc("#loadTplWait").hide();
}


function loadClassDescription(classId) {
    var xhr = new httpRequest;
    xhr.request('GET', top.ADMIN_PATH + 'class/get_class_description.php', {
        class_id: classId
    });
    // synchronous HTML-HTTP-request:
    document.getElementById('loadClassDescription').innerHTML = xhr.getResponseText();
}


function loadClassTemplates(classId, selectedId, catalogueId, is_mirror) {
    var xhr = new httpRequest;
    xhr.request('GET', top.ADMIN_PATH + 'class/get_class_templates.php', {
        class_id: classId,
        selected_id: selectedId,
        catalogue_id: catalogueId,
        is_mirror : is_mirror
    });
    // synchronous HTML-HTTP-request:
    document.getElementById('loadClassTemplates').innerHTML = xhr.getResponseText();
}


function loadClassCustomSettings(classId) {
    var xhr = new httpRequest;
    xhr.request('GET', top.ADMIN_PATH + 'class/get_class_custom_settings.php', {
        class_id: classId
    });
    // synchronous HTML-HTTP-request:
    document.getElementById('loadClassCustomSettings').innerHTML = xhr.getResponseText();
}


function loadSubdivisionAddForm(catalogueId, subId) {
    var oFormDiv;
    if (subId) {
        oFormDiv = document.getElementById('sub-' + subId);
    } else {
        oFormDiv = document.getElementById('site-' + catalogueId);
    }

    if (oFormDiv.innerHTML) {
        oFormDiv.innerHTML = '';
    } else {
        var xhr = new httpRequest;
        xhr.request('GET', top.ADMIN_PATH + 'wizard/subdivision_add_form.php', {
            catalogue_id: catalogueId,
            sub_id: subId
        });
        // synchronous HTML-HTTP-request:
        var oForm = document.createElement("form");
        oForm.id = 'ajaxSubdivisionAdd';
        oForm.name = 'ajaxSubdivisionAdd';
        oForm.innerHTML = xhr.getResponseText();
        oFormDiv.appendChild(oForm);
    }
}

//Subdivision_Name, EnglishName, TemplateID, ClassID
function saveSubdivisionAddForm() {
    var oSubdivisionForm = document.getElementById('ajaxSubdivisionAdd');

    var subdivisionName = oSubdivisionForm.Subdivision_Name.value,
        englishName = oSubdivisionForm.EnglishName.value,
        templateId = oSubdivisionForm.TemplateID.value,
        classId = oSubdivisionForm.ClassID.value,
        catalogueId = oSubdivisionForm.CatalogueID.value,
        subId = oSubdivisionForm.SubdivisionID.value,
        token = oSubdivisionForm.nc_token.value;

    var xhr = new httpRequest;
    xhr.request('GET', top.ADMIN_PATH + 'wizard/subdivision_add.php', {
        subdivision_name: subdivisionName,
        english_name: englishName,
        template_id: templateId,
        class_id: classId,
        catalogue_id: catalogueId,
        sub_id: subId,
        nc_token: token
    });
    // synchronous HTML-HTTP-request:

    var result = xhr.getResponseText();
    if (isNaN(result)) {
        var dst = document.getElementById("formAsyncSaveStatus");
        if (!dst) {
            dst = createElement("DIV", {
                "id": "formAsyncSaveStatus"
            }, document.body);
        }
        dst.style.visibility = 'visible';
        dst.style.opacity = 1;
        dst.className = 'form_save_error';
        dst.innerHTML = result;
        setTimeout("fadeOut('formAsyncSaveStatus')", 5000);
        return;
    }

    var oFormDiv, oInsertBeforeTr;

    if (subId != 0) {
        oFormDiv = document.getElementById('sub-' + subId);
        oInsertBeforeTr = document.getElementById('tr-' + subId);
    } else {
        oFormDiv = document.getElementById('site-' + catalogueId);
        oInsertBeforeTr = document.getElementById('site_tr-' + catalogueId);
    }

    var oTr1 = document.createElement('tr');
    oTr1.id = 'tr-' + result;
    oTr1.setAttribute('parentsub', subId);

    var oTr2 = document.createElement('tr');

    var oTd1 = document.createElement('td');
    oTd1.className = 'name active';

    var oTd2 = document.createElement('td');
    oTd2.className = 'button';

    var oTd3 = document.createElement('td');
    oTd3.colSpan = 2;
    oTd3.style.backgroundColor = '#FFFFFF';

    if (isNaN(parseInt(oInsertBeforeTr.firstChild.style.paddingLeft))) {
        oTd1.style.paddingLeft = 16;
        oTd3.style.padding = '0 0 0 16';
    } else {
        oTd1.style.paddingLeft = parseInt(oInsertBeforeTr.firstChild.style.paddingLeft) + 20;
        oTd3.style.paddingLeft = parseInt(oInsertBeforeTr.firstChild.style.paddingLeft) + 20;
        oTd3.style.paddingRight = 0;
        oTd3.style.paddingTop = 0;
        oTd3.style.paddingBottom = 0;
    }

    var oA1 = document.createElement('a');
    oA1.href = 'index.php?phase=4&SubdivisionID=' + result;
    oA1.innerHTML = subdivisionName;

    var oA2 = document.createElement('a');
    oA2.href = '#';
    oA2.onclick = function() {
        loadSubdivisionAddForm(catalogueId, result);
    };

    var oImg1 = document.createElement('img');
    oImg1.src = ADMIN_PATH + 'images/arrow_sec.gif';
    oImg1.width = '14';
    oImg1.height = '10';
    oImg1.alt = '';
    oImg1.title = '';

    var oImg2 = document.createElement('img');
    oImg2.src = ICON_PATH + 'i_folder_add.gif';
    oImg2.alt = ncLang.addSubsection;
    oImg2.title = ncLang.addSubsection;

    var oSpan = document.createElement('span');
    oSpan.innerHTML = result + '. ';

    oTd1.appendChild(oImg1);
    oTd1.appendChild(oSpan);
    oTd1.appendChild(oA1);

    oA2.appendChild(oImg2);

    oTd2.appendChild(oA2);

    oTr1.appendChild(oTd1);
    oTr1.appendChild(oTd2);

    var oDiv = document.createElement('div');
    oDiv.id = 'sub-' + result;

    oTr2.appendChild(oTd3);
    oTd3.appendChild(oDiv);

    bindEvent(oTr1, 'mouseover', siteMapMouseOver);
    bindEvent(oTr1, 'mouseout', siteMapMouseOut);

    bindEvent(oTr2, 'mouseover', siteMapMouseOver);
    bindEvent(oTr2, 'mouseout', siteMapMouseOut);

    oInsertBeforeTr.parentNode.insertBefore(oTr2, oInsertBeforeTr.nextSibling.nextSibling);
    oInsertBeforeTr.parentNode.insertBefore(oTr1, oInsertBeforeTr.nextSibling.nextSibling);
    oForm.parentNode.removeChild(oForm);
}


/**
  * привязать драг-дроп к s_list_class
  */
function messageInitDrag(messageList, allowChangePriority) {
    if (!messageList) return;

    var current_document = nc_get_current_document();

    for (var classId in messageList) {
        for (var i=0; i < messageList[classId].length; i++) {
            var messageId = messageList[classId][i];
            var container = current_document.getElementById('message'+classId+'-'+messageId),
            handler = current_document.getElementById('message'+classId+'-'+messageId+'_handler');

            if (!container || !handler || !top.dragManager) continue;

            top.dragManager.addDraggable(handler, container);

            if (allowChangePriority) {
                top.dragManager.addDroppable(container, messageAcceptDrop, messageOnDrop, {
                    name: 'arrowRight',
                    bottom: -10,
                    left: 0
                });
            }

            // убрать selectstart с плашки с ID и кнопками (IE)
            handler.parentNode.onselectstart = top.dragManager.cancelEvent;
        }
    }
}

/**
  *
  */
function messageAcceptDrop(e) {
    var //dragged = top.dragManager.draggedInstance,
    target  = top.dragManager.droppedInstance;

    // объект можно бросить на другой объект (если это не родительский) - сменить проритет
    // перемещать только в пределах того же родителя
    if (target.type == 'message' && this.getAttribute('messageParent')==top.dragManager.draggedObject.getAttribute('messageParent')) {
        return true;
    }

    return false;
}

function messageOnDrop(e) {
    var dragged = top.dragManager.draggedInstance,
    target  = top.dragManager.droppedInstance,
    xhr = new httpRequest();

    var res = xhr.getJson(top.ADMIN_PATH + 'subdivision/drag_manager_message.php',
    {
        'dragged_type': dragged.type,
        'dragged_class': dragged.typeNum,
        'dragged_id': dragged.id,
        'target_type': target.type,
        'target_class': target.typeNum,
        'target_id': target.id
    });

    // (смена проритета)
    if (res && target.type == 'message') {
        var oParent = top.dragManager.draggedObject.parentNode;

        oParent.removeChild(top.dragManager.draggedObject);
        // если this.nextSibling не определен, то insertBefore вставляет в конец родительского элемента
        oParent.insertBefore(top.dragManager.draggedObject, this.nextSibling);
    }
}

function FormAsyncDebug(oForm) {

    if (typeof oForm != 'object') {
        oForm = document.getElementsByTagName("FORM")[0];
    }

    var values = [];
    for (var i=0; i < oForm.length; i++) {
        var el = oForm.elements[i];
        if (el.tagName=="SELECT") {
            values[el.name] = el.options[el.options.selectedIndex].value;
        }
        else if (el.tagName=="INPUT" && (el.type=="checkbox" || el.type=="radio")) {
            if (el.checked) values[el.name] = el.value;
        }
        else if (el.name && el.value != undefined) {
            values[el.name] = el.value;
        }
    }

    values["NC_HTTP_REQUEST"] = 1; // предупредить сервер, что данные переданы через Ajax в кодировке utf8

    var statusHandlers = {
        '*': 'formDebugStatus(this.xhr);'
    };

    var xhr = new httpRequest(1); // Async request
    xhr.request('POST', "../debug/debug.php", values, statusHandlers);
    formDebugStatus(1);

}

/**
  * Показать результат evala при помощи XHR
  * @param {Object} XHR httpRequest
  */

function formDebugStatus(xhr) {

    var dst = document.getElementById("formAsyncDebugStatus");
    if (!dst) {
        dst = createElement("DIV", {
            "id": "formAsyncDebugStatus"
        }, document.body);
    }

    dst.style.visibility = 'visible';

    if (!xhr.readyState || xhr.readyState < 4) {
        dst.className = 'formdebug_save_in_progress';
        dst.innerHTML="<div style='float:right;cursor:pointer;' onclick='this.parentNode.style.visibility=\"hidden\"'>X</div><p>" + ncLang.DebugCheckData + "</p>";
        dst.style.height="50px";
    }
    else {
        var errorMessage = "";
        if (xhr.status=="200") { // OK
            var result = {};
            try {
                eval(" var result="+xhr.responseText);
            }
            catch (e) {
                if (xhr.responseText) errorMessage = xhr.responseText;
            }
            if (result.status=="ok") {
                dst.className = 'formdebug_save_ok';
                dst.innerHTML = "<div style='float:right;cursor:pointer;' onclick='this.parentNode.style.visibility=\"hidden\"'>X</div><div class='formdebug_ok_sign'>"+result.content+"</div>";
                var inntable = document.getElementById("debuginfo");
                dst.style.height = inntable.clientHeight+"px";
            }else if (result.status=="err"){
                dst.className = 'formdebug_save_warn';
                dst.innerHTML = "<div style='float:right;cursor:pointer;' onclick='this.parentNode.style.visibility=\"hidden\"'>X</div><div class='formdebug_err_sign'>"+result.content+"</div>";
                var inntable = document.getElementById("debuginfo");
                dst.style.height = inntable.clientHeight+"px";
            }
        }
        else {
            errorMessage = xhr.status + ". " + xhr.statusText;
        }

        if (errorMessage) {
            dst.className = 'formdebug_save_error';
            dst.innerHTML = "<div style='float:right;cursor:pointer;' onclick='this.parentNode.style.visibility=\"hidden\"'>X</div><p>" + ncLang.MessageError + "</p><p>"+errorMessage+"</p>";
            dst.error = errorMessage;
            dst.style.height="50px";
        }
    }
}

function SendClassPreview (form,oTarget) {
    var oForm;
    // object
    if (typeof form == 'object' && form.tagName=='FORM') {
        oForm = form;
    }
    // get the form by ID
    if (typeof form == 'string') {
        oForm = document.getElementById(form);
    }
    // if it is not clear yet - save the FIRST form
    if (typeof oForm != 'object' || oForm == null) {
        oForm = document.getElementsByTagName("FORM")[0];
    }
    // no form!
    if (typeof oForm != 'object') {
        return false;
    }

    if (typeof oTarget == 'undefined' || oTarget == null) {
        oTarget='';
    }
    if (typeof oTarget != 'string') {
        oTarget = oTarget.toString();
    }

    if (isFinite(oForm.ClassID.value)) {
        var old_action = oForm.getAttribute("action");
        var old_target = oForm.getAttribute("target");
        oForm.setAttribute("action",oTarget+"?classPreview="+oForm.ClassID.value);
        oForm.setAttribute("target","_blank");
        oForm.submit();
        oForm.setAttribute("action",old_action);
        oForm.setAttribute("target",old_target);
    }
}

function SendTemplatePreview (form,oTarget) {
    var oForm;
    // object
    if (typeof form == 'object' && form.tagName=='FORM') {
        oForm = form;
    }
    // get the form by ID
    if (typeof form == 'string') {
        oForm = document.getElementById(form);
    }
    // if it is not clear yet - save the FIRST form
    if (typeof oForm != 'object' || oForm == null) {
        oForm = document.getElementsByTagName("FORM")[0];
    }
    // no form!
    if (typeof oForm != 'object') {
        return false;
    }

    if (typeof oTarget == 'undefined' || oTarget == null) {
        oTarget='';
    }
    if (typeof oTarget != 'string') {
        oTarget = oTarget.toString();
    }

    if (isFinite(oForm.TemplateID.value)) {
        var old_action = oForm.getAttribute("action");
        var old_target = oForm.getAttribute("target");
        oForm.setAttribute("action",oTarget+"?templatePreview="+oForm.TemplateID.value);
        oForm.setAttribute("target","_blank");
        oForm.submit();
        oForm.setAttribute("action",old_action);
        oForm.setAttribute("target",old_target);
    }
}



function generateForm(classID, sysTable, act, confirmation) {

    if(!classID || !act) return false;

    var values = [];
    var res, confirmText;
    var url = NETCAT_PATH + 'alter_form.php';
    var needTextArea = document.getElementById(act);

    // выгружаем данные из редактора
    if (typeof $nc(needTextArea).codemirror == 'function') {
        $nc(needTextArea).codemirror('save');
    }

    // если поле не пустое - вызываем диалог
    if(needTextArea.value && !confirmation) {
        var dlgValue = confirm(ncLang["Warn" + act]);

        if(dlgValue) {
            generateForm(classID, sysTable, act, 1);
        }
        return false;
    }

    // предупредить сервер, что данные переданы через Ajax в кодировке utf8
    values["NC_HTTP_REQUEST"] = 1;

    // инициализируем
    var xhr = new httpRequest();

    xhr.request('POST', url, {
        'classID':classID,
        'act':act,
        'systemTableID':sysTable,
        'fs': $nc('input[name=fs]', nc_get_current_document()).val()
    });

    res = xhr.getResponseText();

    needTextArea.value = res;
    if (typeof $nc(needTextArea).codemirror == 'function') {
    	$nc(needTextArea).codemirror('setValue');
    }

    return false;
}

function generate_widget_form(widgetclass_id, action, confirm) {
    var textarea = document.getElementById(action);
    var url = NETCAT_PATH + 'admin/widget/index.php?phase=90';

    var xhr = new httpRequest(false);
    xhr.request('POST', url, {
        'Widget_Class_ID':widgetclass_id,
        'action':action
    });
    textarea.value = xhr.getResponseText();
    if (typeof $nc(textarea).codemirror == 'function') {
    	$nc(textarea).codemirror('setValue');
    }

    return false;
}

/**
 * Привязать к textarea кнопки изменения размера
 */
function bindTextareaResizeButtons() {
    $nc('TEXTAREA').each(function(){
        var $this = $nc(this);
        if (!$this.prev().is('.resize_block')) {
            $nc('<div class="resize_block"><a class="textarea_shrink nc-label nc--lighten" href="#" >&#x25B2;</a> <a class="textarea_grow nc-label nc--lighten" href="#">&#x25BC;</a></div>').insertBefore($this);
        }
        return true;
    });

    $nc('.resize_block A.textarea_shrink, .resize_block A.textarea_grow').bind('click', function(){
        var $this = $nc(this);
        var $textarea = $this.closest('.resize_block').next();
        var height;
        if (!$textarea.is('TEXTAREA')) {
            $textarea = $textarea.find('TEXTAREA');
        }

        if ($textarea.is('TEXTAREA')) {
            if ($textarea.hasClass('has_codemirror')) {
                var cmEditor = $textarea.data('codemirror');
                if (cmEditor) {
                    var $scrollElement = $nc(cmEditor.getScrollerElement());
                    height = $scrollElement.height() + ($this.hasClass('textarea_shrink') ? (-50) : 50);
                    if (height >= 100) {
                        $scrollElement.height(height);
                        cmEditor.refresh();
                    }
                }
            } else {
                height = $textarea.height() + ($this.hasClass('textarea_shrink') ? (-50) : 50);
                if (height >= 100) {
                    $textarea.height(height);
                }
            }
        }
        return false;
    });
}
