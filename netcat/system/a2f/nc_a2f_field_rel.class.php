<?php

/**
 * Класс для реализации поля типа "Связь с другой сущностью"
 *
 * (Не является абстрактным из-за того, что экземпляр создаётся в function nc_customsettings_show_once())
 */
class nc_a2f_field_rel extends nc_a2f_field {

    protected $select_link, $width;
    protected $has_default = 0;

    public function __construct(array $field_settings = null, nc_a2f $parent = null) {
        parent::__construct($field_settings, $parent);
        $nc_core = nc_Core::get_object();
        require_once ($nc_core->ADMIN_FOLDER."related/format.inc.php");
    }

    public function get_subtypes() {
        return array('sub', 'cc', 'user', 'class');
    }

    public function render_value_field($html = true) {
        $nc_core = nc_Core::get_object();

        $change_template = "&nbsp;&nbsp;&nbsp;<a href=\"#\" onclick=\"%s\">".NETCAT_MODERATION_CHANGE_RELATED."</a>\n";
        $remove_template = "&nbsp;&nbsp;&nbsp;<a href=\"#\" onclick=\"%s\">".NETCAT_MODERATION_REMOVE_RELATED."</a>\n";

        $change_link = "window.open('".$nc_core->ADMIN_PATH.$this->select_link."', ".
                "'nc_popup_".$this->name."', ".
                "'width=".$this->width.",height=500,menubar=no,resizable=no,scrollbars=yes,toolbar=no,resizable=yes'); ".
                "return false;";
        $remove_link = "document.getElementById('cs_".$this->name."_value').value='';".
                "document.getElementById('cs_".$this->name."_caption').innerHTML = '".NETCAT_MODERATION_NO_RELATED."';".
                "return false;";

        $ret = "<span id='cs_".$this->name."_caption' style='font-weight:bold;'>";
        if (!$this->value) {
            $ret .= NETCAT_MODERATION_NO_RELATED;
        } else {
            $field_data = $this->get_relation_object();
            $related_caption = listQuery($field_data->get_object_query($this->value),
                            $field_data->get_full_admin_template("%ID. <a href='%LINK' target='_blank'>%CAPTION</a>"));
            $ret .= ( $related_caption ? $related_caption : sprintf(NETCAT_MODERATION_RELATED_INEXISTENT, $this->value));
        }
        $ret .= "</span>";
        $ret .= "<input id='cs_".$this->name."_value' name='".$this->get_field_name()."' type='hidden' value='".intval($this->value)."' />";

        // ссылки изменить, удалить
        $ret .= sprintf($change_template, $change_link);
        $ret .= sprintf($remove_template, $remove_link);

        return $ret;
    }

    /**
     * @return field_relation
     */
    protected function get_relation_object() {
    }

}

/**
 * Класс для реализации поля типа "Связь с компонентов в разделе"
 */
class nc_a2f_field_rel_cc extends nc_a2f_field_rel {

    public function __construct(array $field_settings = null, nc_a2f $parent = null) {
        parent::__construct($field_settings, $parent);
        $this->select_link = "related/select_subclass.php?cs_type=rel_cc&cs_field_name=".$this->name;
        $this->width = 800;
    }

    public function get_relation_object() {
        return new field_relation_subclass();
    }

}

/**
 * Класс для реализации поля типа "Связь с компонентом"
 */
class nc_a2f_field_rel_class extends nc_a2f_field_rel {

    public function __construct(array $field_settings = null, nc_a2f $parent = null) {
        parent::__construct($field_settings, $parent);
        $this->select_link = "related/select_class.php?cs_type=rel_class&cs_field_name=".$this->name;
        $this->width = 350;
    }

    public function get_relation_object() {
        return new field_relation_class();
    }

}

/**
 * Класс для реализации поля типа "Связь с разделом"
 */
class nc_a2f_field_rel_sub extends nc_a2f_field_rel {

    public function __construct(array $field_settings = null, nc_a2f $parent = null) {
        parent::__construct($field_settings, $parent);
        $this->select_link = "related/select_subdivision.php?cs_type=rel_sub&cs_field_name=".$this->name;
        $this->width = 350;
    }

    public function get_relation_object() {
        return new field_relation_subdivision();
    }

}

/**
 * Класс для реализации поля типа "Связь с сайтом"
 */
class nc_a2f_field_rel_catalogue extends nc_a2f_field_rel {

    public function __construct(array $field_settings = null, nc_a2f $parent = null) {
        parent::__construct($field_settings, $parent);
        $this->select_link = "related/select_catalogue.php?cs_type=rel_catalogue&cs_field_name=".$this->name;
        $this->width = 250;
    }

    public function get_relation_object() {
        return new field_relation_catalogue();
    }

}

/**
 * Класс для реализации поля типа "Связь с другой пользователем"
 */
class nc_a2f_field_rel_user extends nc_a2f_field_rel {

    public function __construct(array $field_settings = null, nc_a2f $parent = null) {
        parent::__construct($field_settings, $parent);
        $this->select_link = "related/select_user.php?cs_type=rel_user&cs_field_name=".$this->name;
        $this->width = 350;
    }

    public function get_relation_object() {
        return new field_relation_user();
    }

}