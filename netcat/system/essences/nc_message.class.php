<?php

/* $Id: nc_message.class.php 6441 2012-03-19 09:42:35Z alive $ */
if (!class_exists("nc_System")) die("Unable to load file.");

class nc_Message extends nc_Essence {

    protected $db;

    /**
     * Constructor function
     */
    public function __construct() {
        // load parent constructor
        parent::__construct();

        // system superior object
        $nc_core = nc_Core::get_object();
        // system db object
        if (is_object($nc_core->db)) $this->db = $nc_core->db;
    }

    public function get_current($item = "") {
        // dummy
        return false;
    }

    public function set_current_by_id($id, $reset = false) {
        // dummy
        return false;
    }

    public function get_by_id($class_id, $id, $item = "", $reset = false) {
        // call as static
        static $storage = array();

        // validate parameters
        $class_id = intval($class_id);
        $id = intval($id);

        // check inited object
        if (empty($storage[$class_id][$id]) || $reset) {
            $storage[$class_id][$id] = $this->db->get_row("SELECT * FROM `".$this->essence.$class_id."`
      WHERE `".$this->essence."_ID` = '".$id."'", ARRAY_A);
        }

        // if item requested return item value
        if ($item && is_array($storage[$class_id][$id])) {
            return array_key_exists($item, $storage[$class_id][$id]) ? $storage[$class_id][$id][$item] : "";
        }

        return $storage[$class_id][$id];
    }

    /**
     * Функция для удаления объектов компонентов
     * @param mixed `identificators` - идентификаторы объектов подлежащих удалению в виде массива, или цифры
     * @param int `class_id` номер компонента, в котором производится удаление
     * @param bool `trash` параметр, определяющий, перед будет ли объект помещен в корзину перед удалением. 1 - объект будет помещен в корзину, затем удален, 0 - объект будет удален.
     * @return int `affected` - затронутые объекты or false
     */
    public function delete_by_id($identificators, $class_id, $trash = false) {
        $nc_core = nc_Core::get_object();
        $MODULE_FOLDER = $nc_core->MODULE_FOLDER;

        // validate parameters
        $affected = 0;
        $classID = intval($class_id);

        // Приводим первый параметр к массиву
        if (!is_array($identificators)) {
            $messages_to_delete = array(intval($identificators));
        } else {
            $messages_to_delete = array_map("intval", $identificators);
        }

        if (empty($messages_to_delete)) return 0;

        // Выясняем данные по всем объектам для удаления - номера разделов,
        // компонентов в разделе и номера сайтов, а тажке формируем массив номеров удаляемых объектов

        $temp_array = $this->db->get_results("SELECT m.* , m.`Message_ID` AS `m_id`, m.`Subdivision_ID` as `sub_id`, m.`Sub_Class_ID` as `cc_id`, s.`Catalogue_ID` as `cat_id`
    FROM `Message".$classID."` as m, `Subdivision` as s
    WHERE m.`Subdivision_ID` = s.`Subdivision_ID` AND  m.`Message_ID` IN (".join(',', $messages_to_delete).")", ARRAY_A);

        $message_to_delete = array();
        $message_to_delete_ids = array();
        $message_to_delete_data = array();

        if (!empty($temp_array)) {
            foreach ($temp_array as $v) {
                // группируем объекты по сайты/разделу/сс
                $message_to_delete[$v['cat_id']][$v['sub_id']][$v['cc_id']][] = intval($v['m_id']);
                $message_to_delete_data[$v['cat_id']][$v['sub_id']][$v['cc_id']][intval($v['m_id'])] = $v;
                $message_to_delete_ids[] = $v['m_id'];
            }
        } else {
            return 0;
        }

        $nc_core->event->execute("dropMessagePrep", $messages_to_delete);

        try {
            if ($trash) {
                $trashing_result_arr = $nc_core->trash->add($messages_to_delete, $classID);
            }
        } catch (nc_Exception_Trash_Full $e) {
            $trash = 0;
        } catch (nc_Exception_Trash_Folder_Fail $e) {
            $trash = 0;
        }

        // Удаляем комментарии
        if (nc_module_check_by_keyword("comments")) {
            include_once ($MODULE_FOLDER."comments/function.inc.php");
            // get need ids
            $comments_temp = $this->db->get_results("SELECT `Message_ID`, `Sub_Class_ID` FROM `Message".$classID."`
      WHERE `Message_ID` IN (".join(',', $message_to_delete_ids).") OR `Parent_Message_ID` IN (".join(',', $message_to_delete_ids).")", ARRAY_A);

            // compile arrays
            $temp_messages = array();
            $temp_ccs = array();
            foreach ((array) $comments_temp AS $comments_temp_value) {
                if (!in_array($comments_temp_value['Message_ID'], $temp_messages))
                        $temp_messages[] = $comments_temp_value['Message_ID'];
                if (!in_array($comments_temp_value['Sub_Class_ID'], $temp_ccs))
                        $temp_ccs[] = $comments_temp_value['Sub_Class_ID'];
            }

            // delete comments
            nc_comments::dropComments($this->db, $temp_ccs, "Sub_Class", $temp_messages);

            // delete comment rules
            nc_comments::dropRuleMessage($this->db, $temp_messages, $temp_ccs);
            // clear
            unset($comments_temp, $temp_ccs, $temp_messages);
        }

        // delete related files
        // поочередно удаляем файлы у всех перечисленных к удалению объектов
        // если они не отправляются в корзину, иначе файлам ставим метку deleted
        if (!$trash) {
            require_once $this->core->INCLUDE_FOLDER."s_files.inc.php";
            foreach ($message_to_delete_ids as $id) {
                DeleteMessageFiles($classID, $id);
            }
        } else {
            $nc_core->trash->TrashMessageFiles($classID, $message_to_delete_ids);
        }

        // Удаляем сами объекты
        $this->db->query("DELETE FROM `Message".$classID."` WHERE `Message_ID` IN (".join(',', $message_to_delete_ids).")");
        if ($this->db->is_error) {
            throw new nc_Exception_DB_Error($this->db->last_query, $this->db->last_error);
        } else {
            $affected = $this->db->rows_affected;
        }

        // execute core action
        if ($message_to_delete) {
            foreach ($message_to_delete as $site_id => $site) {
                foreach ($site as $sub_id => $sub) {
                    foreach ($sub as $cc_id => $messages_in_cc) {
                        $nc_core->event->execute("dropMessage", $site_id, $sub_id, $cc_id,
                                $classID, $messages_in_cc, $message_to_delete_data[$site_id][$sub_id][$cc_id]);
                    }
                }
            }
        }

        $children_ids = $this->db->get_col("SELECT `Message_ID` FROM `Message".$classID."` WHERE `Parent_Message_ID` IN  (".join(',', $message_to_delete_ids).")");

        if (!empty($children_ids)) {
            // delete related files
            if ($this->db->is_error) {
                throw new nc_Exception_DB_Error($this->db->last_query, $this->db->last_error);
            }

            $affected += $this->db->rows_affected;

            // execute core action
            $affected += $this->delete_by_id($children_ids, $classID, $trash);
        }

        return $affected;
    }

}
?>