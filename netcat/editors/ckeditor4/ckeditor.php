<?php

class CKEditor {

    static $toolbarNames = array(
        'clipboard' => NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_CLIPBOARD,
        'undo' => NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_UNDO,
        'find' => NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_FIND,
        'selection' => NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_SELECTION,
        'forms' => NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_FORMS,
        'basicstyles' => NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_BASICSTYLES,
        'cleanup' => NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_CLEANUP,
        'list' => NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_LIST,
        'indent' => NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_INDENT,
        'blocks' => NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_BLOCKS,
        'align' => NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_ALIGN,
        'links' => NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_LINKS,
        'insert' => NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_INSERT,
        'styles' => NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_STYLES,
        'colors' => NETCAT_WYSIWYG_CKEDITOR_SETTINGS_FIELD_TOOLBARS_NAME_COLORS,
    );

    static $defaultSkin = 'moono';

    private $name;

    private $value;

    private $panel;

    private $basePath;
    private $language;

    public function __construct($name = null, $value = null, $panel = 0) {
        $this->name = $name;
        $this->value = $value;
        $this->panel = $panel;

        $nc_core = nc_Core::get_object();
        $this->basePath = $nc_core->SUB_FOLDER . $nc_core->HTTP_ROOT_PATH . 'editors/ckeditor4/';

        $language = $nc_core->lang->detect_lang(1);
        $this->language = $language == 'ru' ? 'ru' : 'en';
    }

    protected function getScriptLoader() {
        return "
            if (typeof CKEDITOR === 'undefined') {
                var CKEDITOR_BASEPATH = '{$this->getBasePath()}';
                nc.load_script('{$this->getScriptPath()}');
            }\n";

    }

    public function CreateHtml() {
        $html = "";

        static $initComplete;

        if (!$initComplete) {
            $initComplete = true;

            if (nc_core()->admin_mode) {
                $html .= "<script type='text/javascript'>" .
                            $this->getScriptLoader() .
                            $this->getInstanceReadyHandler() .
                         "</script>\n";
            }
            else {
                $html .= "<script type='text/javascript'>var CKEDITOR_BASEPATH = '" . $this->getBasePath() . "';</script>";
                $html .= "<script type='text/javascript' src='" . $this->getScriptPath() . "'></script>";
                $html .= "<script type='text/javascript'>{$this->getInstanceReadyHandler()}</script>";
            }
        }

        $toolbars = $this->loadToolbarsConfig('CkeditorPanelFull');
        $defaultConfig = $this->getDefaultConfig();

        $html .= "<textarea class='no_cm' name=\"{$this->name}\" id=\"{$this->name}\">" . htmlspecialchars($this->value) . "</textarea>\n";
        $html .= "<script type=\"text/javascript\">try {CKEDITOR.replace('{$this->name}', {{$toolbars}{$defaultConfig}});} catch (exception) {}</script>\n";

        return $html;
    }

    public function getBasePath() {
        return $this->basePath;
    }

    public function getScriptPath() {
        return $this->basePath . 'ckeditor.js';
    }

    public function getFileManagerPath() {
        global $perm;
        $path = $this->basePath . 'filemanager/index.php';

        $split_users = nc_Core::get_object()->get_settings('CKEditorFileSystem');

        if ($split_users && $perm && $perm->isSupervisor()) {
            $path .= '?expandedFolder=' . $perm->GetUserID() . '/';
        }

        return $path;
    }

    public function getLanguage() {
        return $this->language;
    }

    public function getWindowFormScript() {
        $toolbars = $this->loadToolbarsConfig('CkeditorPanelFull');
        $defaultConfig = $this->getDefaultConfig();

        return "try {CKEDITOR.replace('nc_editor', {{$toolbars}{$defaultConfig}});} catch (exception) {}\n";
    }

    public function getInlineScript($fieldName, $fieldValue, $messageId, $subClassId = null) {
        $nc_core = nc_Core::get_object();

        $subClass = $subClassId ? $nc_core->sub_class->get_by_id($subClassId) : $nc_core->sub_class->get_current();

        $html = '';

        static $initComplete;

        if (!$initComplete) {
            $initComplete = true;

            $html .= "<script type='text/javascript'>" .
                        $this->getScriptLoader() .
                     "\nCKEDITOR.disableAutoInline = true;\n" .
                        $this->getInstanceReadyHandler() .
                     "</script>\n";
        }

        $toolbars = $this->loadToolbarsConfig('CkeditorPanelInline');
        $defaultConfig = $this->getDefaultConfig();

        $html .= "<div id='{$fieldName}_{$messageId}_{$subClassId}_edit_inline' style='display: inline-block;' contenteditable='true' data-oldvalue='" . str_replace("'", "&#39;", $fieldValue) . "'>";
        $html .= $fieldValue;
        $html .= "</div>";
        $html .= "<script type='text/javascript'>
    \$nc('#{$fieldName}_{$messageId}_{$subClassId}_edit_inline').closest('A').on('click', function(){
        return false;
    });
    try {
        if (typeof(CKEDITOR.nc_active_inline) == 'undefined') {
            CKEDITOR.nc_active_inline = false;
        }
        CKEDITOR.inline('{$fieldName}_{$messageId}_{$subClassId}_edit_inline', {
            {$toolbars}
            {$defaultConfig},
            on: {
                blur: function(){
                    var \$element = \$nc('#{$fieldName}_{$messageId}_{$subClassId}_edit_inline');
                    var newValue = CKEDITOR.instances.{$fieldName}_{$messageId}_{$subClassId}_edit_inline.getData();
                    var oldValue = \$element.attr('data-oldvalue');
                    if (CKEDITOR.nc_active_inline) {
                        //\$element.html(oldValue);
                        return true;
                    } else {
                        //CKEDITOR.nc_active_inline = true;
                    }

                    if (\$nc.trim(newValue) != \$nc.trim(oldValue)) {
                    ";
        if ($nc_core->get_settings('InlineEditConfirmation')) {
            $html .= "parent.nc_form('{$nc_core->SUB_FOLDER}{$nc_core->HTTP_ROOT_PATH}editors/nc_edit_in_place.php?dummy=0', '', '', {
                            width: 300,
                            height: 100
                        }, 'post', {
                            messageId: {$messageId},
                            subClassId: {$subClass['Sub_Class_ID']},
                            fieldName: '{$fieldName}',
                            newValue: newValue
                        });";
        } else {
            $html .= "parent.nc_action_message('{$nc_core->SUB_FOLDER}{$nc_core->HTTP_ROOT_PATH}editors/nc_edit_in_place.php?dummy=0', 'post', {
                            messageId: {$messageId},
                            subClassId: {$subClass['Sub_Class_ID']},
                            fieldName: '{$fieldName}',
                            newValue: newValue
                        });
                        CKEDITOR.nc_active_inline = false;
                        ";
        }

        $html .= "}
                    return true;
                }
            }
        });
    } catch (exception) {
    }
</script>";

        return $html;
    }

    public function loadToolbarsConfig($setting_name) {
        $nc_core = nc_Core::get_object();
        $db = $nc_core->db;

        $panel_id = $this->panel ? $this->panel : (int)$nc_core->get_settings($setting_name);

        $sql = "SELECT `Toolbars` FROM `Wysiwyg_Panel` WHERE `Wysiwyg_Panel_ID` = {$panel_id}";
        $toolbars = $db->get_var($sql);

        $toolbars = @unserialize($toolbars);

        $toolbarNames = self::$toolbarNames;

        if (is_array($toolbars)) {
            $jsonArray = array('mode', 'tools');
            foreach ($toolbarNames as $toolbar => $title) {
                if ($toolbars[$toolbar]) {
                    $jsonArray[] = array(
                        'name' => $toolbar,
                    );
                }
            }

            $config = 'toolbarGroups: ' . json_encode($jsonArray) . ',';
            return $config;
        }

        return '';
    }

    public function loadSkinConfig() {
        $nc_core = nc_Core::get_object();
        $skin = $nc_core->get_settings('CKEditorSkin');

        $dir = $nc_core->ROOT_FOLDER . "editors/ckeditor4/skins/";

        if (!$skin || !file_exists($dir . $skin)) {
            $skin = self::$defaultSkin;
        }

        return $skin;
    }

    public function getPreviewFunctions() {
        ?>
        <script type="text/javascript">
            function nc_update_ckeditor_toolbars_preview(instance_name, toolbars) {
                for(var i in toolbars) {
                    alert(i);
                }
            }
        </script>
    <?php
    }

    public function CreatePanelPreviewHtml() {
        $html = "";

        static $initComplete;

        if (!$initComplete) {
            $initComplete = true;

            $html .= "<script type='text/javascript'>var CKEDITOR_BASEPATH = '" . $this->getBasePath() . "';</script>";
            $html .= "<script type='text/javascript' src='" . $this->getScriptPath() . "'></script>";
        }

        $defaultConfig = $this->getDefaultConfig();

        $html .= "<textarea class='no_cm' name=\"\" id=\"preview\"></textarea>\n";
        $html .= "<script type=\"text/javascript\">
    var nc_default_ckeditor_toolbars = " . json_encode(array_keys(self::$toolbarNames)) . ";
    function nc_update_ckeditor_toolbars_preview() {
        var toolbars = ['mode'];

        for (var i in nc_default_ckeditor_toolbars) {
            var toolbar = nc_default_ckeditor_toolbars[i];
            if (\$nc('INPUT[name=\"Toolbars[' + toolbar + ']\"]').is(':checked')) {
                toolbars[toolbars.length] = toolbar;
            }
        }

        try {
            if (typeof(CKEDITOR.instances['preview']) != 'undefined') {
                CKEDITOR.instances['preview'].destroy();
            }
            CKEDITOR.replace('preview', {
                toolbarGroups: toolbars,
                {$defaultConfig}
            });
        } catch (exception) {
        }
    }

    \$nc(function(){
        nc_update_ckeditor_toolbars_preview();

        \$nc('INPUT[name^=Toolbars]').on('change', function(){
            nc_update_ckeditor_toolbars_preview();
        });
    });
</script>\n";

        return $html;
    }

    public function getDefaultConfig() {
        $nc_core = nc_Core::get_object();

        $skin = $this->loadSkinConfig();
        $enterMode = (int)$nc_core->get_settings('CKEditorEnterMode');
        $enterMode = ($enterMode) ? $enterMode : 1;

        $result = "skin: '{$skin}',
language: '" . $this->getLanguage() . "',
filebrowserBrowseUrl:  '" . $this->getFileManagerPath() . "',\n" .
($nc_core->get_settings('CKEditorEnableContentFilter') ? "" : "allowedContent: true,\n") .
"entities: true,
autoParagraph: true,
enterMode: {$enterMode}";

        return $result;
    }

    public function getInstanceReadyHandler() {
        $html = "CKEDITOR.on('instanceReady', function(ev) {
    for(var tag in CKEDITOR.dtd.\$block) {
        ev.editor.dataProcessor.writer.setRules(tag, {
            indent: false,
            breakBeforeOpen: true,
            breakAfterOpen: false,
            breakBeforeClose: false,
            breakAfterClose: false
        });
    }
});";

        return $html;
    }

}
