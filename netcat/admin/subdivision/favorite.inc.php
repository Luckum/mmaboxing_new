<?php
/* $Id */
# покажем список избранных разделов

function ShowFavorites() {
    global $db, $UI_CONFIG, $nc_core;
    global $EDIT_DOMAIN, $HTTP_ROOT_PATH, $DOMAIN_NAME;
    global $loc, $perm, $ADMIN_PATH, $ADMIN_TEMPLATE, $SUB_FOLDER;

    $favorites = GetFavorites('OBJECT');

    if ($favorites) {

        $totrows = $db->num_rows;
?>
        <form method='post' action='favorites.php'>
            <table border='0' cellpadding='0' cellspacing='0' width='100%'>
                <tr>
                    <td>
                        <table class='admin_table' width='100%'>
                            <tr>
                                <th>ID</th>
								<th width='100%'><?=CONTROL_CONTENT_SUBDIVISION_FUNCS_SECTION ?></th>
								<th><?=CONTROL_CONTENT_SUBDIVISION_FUNCS_SUBSECTIONS ?></th>
								<th class='align-center'><?=CONTROL_CONTENT_SUBDIVISION_FUNCS_GOTO ?></th>
								<td class='align-center'><div class='icons icon_delete' title='<?=CONTROL_CONTENT_SUBDIVISION_FUNCS_DELETE ?>'></div></td>
							</tr>

		<?php
		$temp_group = "";

		foreach ($favorites as $Array) {
			if ($temp_group != $Array->Catalogue_ID)
					print "<tr><td'><br></td><td colspan=5><a href=".$ADMIN_PATH."subdivision/full.php?CatalogueID=".$Array->Catalogue_ID." title='".CONTROL_CONTENT_CATALOUGE_ONESITE."'>".(!$Array->CatalogueChecked ? "" : "").$Array->Catalogue_Name.(!$Array->CatalogueChecked ? "" : "")."</a></td></tr>";
			print "<tr>
                     <td>".$Array->Subdivision_ID."</td>
                     <td><a href=\"index.php?phase=4&SubdivisionID=".$Array->Subdivision_ID."\">".(!$Array->SubChecked ? "" : "").$Array->Subdivision_Name."</a></td>
                     <td><a href=index.php?phase=1&ParentSubID=".$Array->Subdivision_ID.">".(!$Array->SubChecked ? "" : "").(!ChildrenNumber($Array->Subdivision_ID) ? CONTROL_CONTENT_SUBDIVISION_FUNCS_NONE : CONTROL_CONTENT_SUBDIVISION_FUNCS_LIST." (".ChildrenNumber($Array->Subdivision_ID).")")."</a></td>
                     <td align=center nowrap><a href=index.php?phase=5&SubdivisionID=".$Array->Subdivision_ID."><div class='icons icon_settings' title='".CONTROL_CONTENT_SUBDIVISION_FUNCS_TOOPTIONS."'></div></a>".((!GetSubClassCount($Array->Subdivision_ID)) ? "<img src=".$ADMIN_TEMPLATE."img/px.gif width=16 height=16 style='margin:0px 2px 0px 2px;'>" : "<a target=_blank href=http://".$EDIT_DOMAIN.$HTTP_ROOT_PATH."?catalogue=".$Array->Catalogue_ID."&sub=".$Array->Subdivision_ID.(session_id() ? "?".session_name()."=".session_id() : "")."><div class='icons icon_pencil' title='".CONTROL_CONTENT_SUBDIVISION_FUNCS_TOEDIT."'></div></a>")."<a href=".nc_subdivision_preview_link($Array)." target=_blank><div class='icons icon_preview' title='".CONTROL_CONTENT_SUBDIVISION_FUNCS_TOVIEW."'></div></a></td>
                     <td align=center>".nc_admin_checkbox_simple("Delete[".$Array->Subdivision_ID."]", $Array->Subdivision_ID)."</td>\n
                   </tr>\n";

                                                                            $temp_group = $Array->Catalogue_ID;
                                                                        }
                                                                        ?>
                                                                        </table>
                                                                        </td>
                                                                        </tr>
                                                                        </table>
                                                                        <input type='hidden' name='phase' value='6'>
                                                                        <input type='submit' class='hidden'>
                                                                        <?php echo $nc_core->token->get_input(); ?>
                                                                        </form>
                                                                        <?php
                                                                        $UI_CONFIG->actionButtons[] = array(
                                                                            "id" => "delete",
                                                                            "caption" => NETCAT_ADMIN_DELETE_SELECTED,
                                                                            "action" => "mainView.submitIframeForm()",
                                                                            "align" => "left",
                                                                            "red_border" => true,
                                                                        );
                                                                    } else {
                                                                        nc_print_status(CONTROL_CONTENT_SUBDIVISION_FUNCS_NOONEFAVORITES, 'info');
                                                                    }
                                                                    $UI_CONFIG->actionButtons[] = array("id" => "submit",
                                                                            "caption" => CONTROL_CONTENT_SUBDIVISION_FUNCS_ADDSECTION,
                                                                            "action" => "window.open('".$ADMIN_PATH."subdivision/favorites.php?phase=4','LIST','top=50, left=100,directories=no,height=600,location=no,menubar=no,resizable=no,scrollbars=yes,status=yes,toolbar=no,width=400')");
                                                                }

                                                                function GetSubsForFavorites($section=0, $mode="plain", $catid=0) {
                                                                    global $db, $_structure_level, $perm;

                                                                    $catid+=0;

                                                                    if (!$catid) { // Если не задан сайт, возьмем первый доступный
                                                                        $array_id = $perm->GetAllowSite(MASK_ADMIN | MASK_MODERATE, false);
                                                                        $where = is_array($array_id) ? " WHERE `Catalogue_ID` IN(".join(',', $array_id).") " : " ";
                                                                        $sql = "SELECT Catalogue_ID FROM Catalogue ".$where." ORDER BY Priority LIMIT 1";
                                                                        $catid = $db->get_var($sql);
                                                                    }

                                                                    // Определение доступных сайтов
                                                                    $allow_id = $perm->GetAllowSub($catid, MASK_ADMIN, true, true, false);
                                                                    $qry_where = is_array($allow_id) ? " AND Subdivision_ID IN(".join(',', (array) $allow_id).") " : " ";

                                                                    $ret = array();
                                                                    $select = "SELECT * FROM Subdivision as a
              WHERE a.Catalogue_ID=".$catid."
              AND a.Parent_Sub_ID=".$section.$qry_where."
              ORDER BY a.Priority";

                                                                    if ($Result = $db->get_results($select, ARRAY_A)) {
                                                                        foreach ($Result as $row) {
                                                                            $row["level"] = (int) $_structure_level;
                                                                            $ret[$row["Subdivision_ID"]] = $row;
                                                                            $_structure_level++;
                                                                            $children = GetSubsForFavorites($row["Subdivision_ID"], 'plain', $catid);
                                                                            $_structure_level--;

                                                                            foreach ($children as $idx => $row2) {
                                                                                $ret[$idx] = $row2;
                                                                            }
                                                                        }

                                                                        if ($mode == "get_children") {
                                                                            foreach ($ret as $idx => $row) {
                                                                                while ($row["Parent_Sub_ID"] != $section) {
                                                                                    $ret[$row["Parent_Sub_ID"]]["Children"][] = $row["Subdivision_ID"];
                                                                                    $row = $ret[$row["Parent_Sub_ID"]];
                                                                                }
                                                                            }
                                                                        }
                                                                    }

                                                                    return $ret;
                                                                }

function ShowSubsForFavorites($structure=0, $parent_section=0, $catid=0, $phase=0) {
    global $db, $perm, $ADMIN_TEMPLATE;
    static $count, $init, $sub_admin;

    if (!$init) {
        // разделы, для к-ых пол-тель является админом
        $allow_id = $perm->GetAllowSub($catid, MASK_ADMIN, false, true, false);

        $qry_where = is_array($allow_id) ? " `Subdivision_ID` IN(".join(',', (array) $allow_id).") " : " 1";
        $sub_admin = (array) $db->get_col("SELECT `Subdivision_ID`
                                      FROM `Subdivision`
                                      WHERE ".$qry_where);
        $init = true;
    }


    echo "<ul".(!$parent_section ? " id='siteTree'" : "")." style='margin-left: 5px; padding-left: 0;'>\n";
    foreach ($structure as $id => $row) {
        if ($row["Parent_Sub_ID"] == $parent_section) {
            $count++;
            if (!$row["Parent_Sub_ID"])
                $count = 0;
            if (in_array($row['Subdivision_ID'], $sub_admin)) {
                echo "<li class='menu_left_sub'>\n
            <i class='nc-icon nc--folder ".(!$row['Checked'] ? "nc--disabled" : "")."'></i>

            <span class='node_id'>".$row['Subdivision_ID']."</span> ".($row["Favorite"] ? "&nbsp;" : "<a href=# onclick=\"add_to_favorites($row[Subdivision_ID], ".($phase + 1)."); return false;\" style='".(!$row['Checked'] ? "color:#cccccc;" : "")."'>")."".$row['Subdivision_Name']."</a>\n";
            } else { //нет доступа - просто показываем
                echo "<li class='menu_left_sub'><i class='nc-icon nc--folder ".(!$row['Checked'] ? "nc--disabled" : "")."'></i>
        <span class='node_id'>".$row['Subdivision_ID']."</span> ".$row['Subdivision_Name']."\n";
            }

            ShowSubsForFavorites($structure, $id, 0, $phase);
            $count--;
        }
    }
    echo "</ul>\n";
    unset($structure);
}

                                                                function AddFavorites($subid) {
                                                                    global $nc_core, $db;
                                                                    $subid+=0;

                                                                    $catalogue = $nc_core->subdivision->get_by_id($subid, "Catalogue_ID");

                                                                    // execute core action
                                                                    $nc_core->event->execute("updateSubdivisionPrep", $catalogue, $subid);

                                                                    $db->query("UPDATE `Subdivision` SET `Favorite` = 1 WHERE `Subdivision_ID` = '".$subid."'");


                                                                    // execute core action
                                                                    $nc_core->event->execute("updateSubdivision", $catalogue, $subid);
                                                                }

                                                                function ShowCataloguesForFavorites($catid=0, $phase=0) {
                                                                    global $DOC_DOMAIN, $ADMIN_PATH;
                                                                    global $db, $perm;

                                                                    $CatalogueID = intval($catid);

                                                                    // получим id всех каталогов, к которому пользователь имеет доступ админа
                                                                    // или иммет доступ к его разделам, тоже админ
                                                                    // т.к. в избранный раздел польз-ль может долбавить, если он
                                                                    // админ этого раздела
                                                                    // если ф-ция вернет не массив, то значит есть достп ко всем
                                                                    $array_id = $perm->GetAllowSite(MASK_ADMIN, false);

                                                                    $select = "SELECT DISTINCT catalogue.Catalogue_ID,
                               catalogue.Catalogue_Name,
                               catalogue.Domain,
                               catalogue.Title_Sub_ID,
                               catalogue.Checked
                          FROM Catalogue as catalogue
                          WHERE ".( is_array($array_id) ? "catalogue.Catalogue_ID IN(".join(',', (array) $array_id).")" : "1" )."
                          ORDER BY catalogue.Priority";

                                                                    $Result = $db->get_results($select, ARRAY_N);
                                                                    $sitenum = $db->num_rows;
                                                                    echo "<nobr>".CONTROL_USER_SELECTSITE.": <select onchange=\"document.location.href='".$ADMIN_PATH."subdivision/favorites.php?phase=".$phase."&catid='+this.value;\" style='width:250;'>\n";
                                                                    $f = false;

                                                                    foreach ($Result as $Array) {
                                                                        if ($sitenum > 1) {
                                                                            if ($CatalogueID == false && ($Array[2] == $HTTP_HOST || "www.".$Array[2] == $HTTP_HOST)) {
                                                                                $CatalogueID = $Array[0];
                                                                            }
                                                                        } else {
                                                                            $CatalogueID = $Array[0];
                                                                        }

                                                                        if (!$temp_catid)
                                                                                $temp_catid = $Array[0];
                                                                        $s_id = "";
                                                                        if ($AUTHORIZATION_TYPE == 'session') {
                                                                            $s_id = "&".session_name()."=".session_id();
                                                                        }

                                                                        echo "\t<option ".($CatalogueID == $Array[0] ? "selected " : "")."value=".$Array[0].$s_id.">".$Array[0].":".$Array[1]."\n";
                                                                    }

                                                                    if (!$CatalogueID)
                                                                            $CatalogueID = $temp_catid;

                                                                    echo "</select>\n</nobr>\n<hr>\n<!--<div align=right>\n\t<a href='http://".$DOC_DOMAIN."/management/favorites/add/' target=help onclick=\"PopupWindow(this.href);return false;\"><img border=0 src=".$ADMIN_PATH."images/help.gif width=16 height=16 align=absmiddle hspace=4 alt='".BEGINHTML_HELPNOTE."'>".BEGINHTML_HELPNOTE."</a>\n</div>-->";
                                                                }

                                                                /**
                                                                 * Delete subs from favorites
                                                                 *
                                                                 * @param array with sub id
                                                                 */
                                                                function nc_delete_from_favorite($favorites) {
                                                                    global $nc_core, $db, $perm;

                                                                    foreach ($favorites as $favorite) {
                                                                        $favorite = intval($favorite);
                                                                        if ($perm->isSubdivisionAdmin($favorite)) {
                                                                            $catalogue = $nc_core->subdivision->get_by_id($favorite, "Catalogue_ID");

                                                                            // execute core action
                                                                            $nc_core->event->execute("updateSubdivisionPrep", $catalogue, $favorite);

                                                                            $db->query("UPDATE Subdivision SET Favorite = 0 WHERE Subdivision_ID = '".$favorite."'");

                                                                            // execute core action
                                                                            $nc_core->event->execute("updateSubdivision", $catalogue, $favorite);
                                                                        }
                                                                    }
                                                                    return;
                                                                }
