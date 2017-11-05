<?php

/* $Id: drag_manager.php 5946 2012-01-17 10:44:36Z denis $ */

ob_start("ob_gzhandler");

define("NC_ADMIN_ASK_PASSWORD", false);

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -4)) . (strstr(__FILE__, "/") ? "/" : "\\");
include_once($NETCAT_FOLDER . "vars.inc.php");
require($ADMIN_FOLDER . "function.inc.php");

$dragged_id = (int)$dragged_id;
$target_id = (int)$target_id;
if (!$dragged_id || !$target_id || $dragged_id == $target_id) die("0 /* Wrong parameters */");

// INPUT: $dragged_type, $dragged_id, $target_type, $target_id, $position [inside|below]

if ($dragged_type == 'template' && $target_type == 'template') {
    if ($perm->isAccess(NC_PERM_TEMPLATE, 0, 0, 1)) {
        if ($position == 'inside') {
            $new_parent_id = $target_id;
        } else {
            $sql = "SELECT `Parent_Template_ID` FROM `Template` WHERE `Template_ID` = {$target_id}";
            $new_parent_id = (int)$db->get_var($sql);
        }

        $sql = "SELECT `Parent_Template_ID` FROM `Template` WHERE `Template_ID` = {$dragged_id}";
        $old_parent_id = (int)$db->get_var($sql);

        if ($old_parent_id != $new_parent_id) {
            $sql = "SELECT `File_Path` FROM `Template` WHERE `Template_ID` = {$dragged_id}";
            $old_file_path = $db->get_var($sql);
            $old_file_path = substr($old_file_path, 0, strlen($old_file_path)-1);

            if ($new_parent_id) {
                $id_tree = array($dragged_id, $target_id);

                $current_template_id = $target_id;
                do {
                    $sql = "SELECT `Parent_Template_ID` FROM `Template` WHERE `Template_ID` = {$current_template_id}";
                    $current_template_id = (int)$db->get_var($sql);
                    if ($current_template_id) {
                        $id_tree[] = $current_template_id;
                    }
                } while ($current_template_id != 0);
            } else {
                $id_tree = array($dragged_id);
            }

            $new_file_path = '/' . implode('/', array_reverse($id_tree));
            $new_file_path = $db->escape($new_file_path);

            $sql = "UPDATE `Template` SET `Parent_Template_ID` = {$new_parent_id} WHERE `Template_ID` = {$dragged_id}";
            $db->query($sql);

            nc_update_template_file_path_recursively($dragged_id);
            $template_folder = rtrim(nc_Core::get_object()->TEMPLATE_FOLDER, '\/');
            nc_move_directory($template_folder . $old_file_path, $template_folder . $new_file_path);
        }

        //change priorities
        $priority = 0;

        if ($position == 'inside') {
            $sql = "UPDATE `Template` SET `Priority` = {$priority} WHERE `Template_ID` = {$dragged_id}";
            $db->query($sql);
            $priority++;
        }

        $sql = "SELECT `Template_ID` FROM `Template` "  .
            "WHERE `Template_ID` <> {$dragged_id} " .
            "AND `Parent_Template_ID` = {$new_parent_id} " .
            "ORDER BY `Priority`, `Template_ID`";

        foreach((array)$db->get_col($sql) AS $template_id) {
            $sql = "UPDATE `Template` SET `Priority` = {$priority} WHERE `Template_ID` = {$template_id}";
            $db->query($sql);

            $priority++;

            if ($position =='below' && $target_id == $template_id) {
                $sql = "UPDATE `Template` SET `Priority` = {$priority} WHERE `Template_ID` = {$dragged_id}";
                $db->query($sql);
                $priority++;
            }
        }
    }

    die("1 /* OK */");
} else {
    die("0 /* Wrong request ['$dragged_type $dragged_id' $position '$target_type $target_id'] */");
}

function nc_update_template_file_path_recursively($template_id) {
    $db = nc_Core::get_object()->db;

    $id_tree = array($template_id);

    $current_template_id = $template_id;
    do {
        $sql = "SELECT `Parent_Template_ID` FROM `Template` WHERE `Template_ID` = {$current_template_id}";
        $current_template_id = (int)$db->get_var($sql);
        if ($current_template_id) {
            $id_tree[] = $current_template_id;
        }
    } while ($current_template_id != 0);

    $new_file_path = '/' . implode('/', array_reverse($id_tree)) . '/';
    $new_file_path = $db->escape($new_file_path);

    $sql = "UPDATE `Template` SET `File_Path` = '{$new_file_path}' WHERE `Template_ID` = {$template_id}";
    $db->query($sql);

    $sql = "SELECT `Template_ID` FROM `Template` WHERE `Parent_Template_ID` = {$template_id}";
    $children_templates = $db->get_col($sql);

    foreach ($children_templates as $template_id) {
        nc_update_template_file_path_recursively($template_id);
    }
}