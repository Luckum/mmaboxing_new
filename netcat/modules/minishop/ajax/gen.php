<?php

/* $Id: gen.php 4945 2011-06-28 14:25:47Z gaika $ */
$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -5)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require_once ($ROOT_FOLDER."connect_io.php");

$nc_core->modules->load_env();

Authorize();
if (!is_object($perm) || !$perm->isSupervisor()) die("");


$result_arr = array();
$radio+= 0;

switch ($tname) {
    case 'put_button_alternate' :
        $res = true;
        $ttext = $nc_minishop->TMPL->templates[1]["put"][$radio];
        break;
    case 'mass_put_alternate' :
        $res = true;
        $ttext = $nc_minishop->TMPL->templates[1]["massput"]["template"];
        break;
    case 'already_in_cart_alternate' :
        $res = true;
        $ttext = $nc_minishop->TMPL->templates[1]["incart"][$radio];
        break;
    case 'cart_full' :
        $res = true;
        $ttext = $nc_minishop->TMPL->templates[1]["cart"]["nonempty"];
        break;
    case 'cart_empty' :
        $res = true;
        $ttext = $nc_minishop->TMPL->templates[1]["cart"]["empty"];
        break;
    default :
        $res = false;
        break;
};

$result_arr["res"] = $res;

if ($res) {
    $result_arr["ident"] = $tname;
    //$result_arr["ttext"] = preg_replace('/(\$)/m','\\\$',$ttext);
    $result_arr["ttext"] = $ttext;
}
if (!$nc_core->NC_UNICODE) {
    $result_arr["ttext"] = $nc_core->utf8->win2utf($result_arr["ttext"]);
}
$result = json_encode($result_arr);

ob_end_clean();
echo $result;