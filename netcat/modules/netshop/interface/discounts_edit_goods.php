<?
# register_globals - в топку!!!
$selected = $_GET['selected'];
$goods_types = $_GET['goods_types'];
$subdivisions = $_GET['subdivisions'];
$shop_id = $_GET['shop_id'];
$catalogue_id = $_GET['catalogue_id'];

if (!$shop_id) die("NO SHOP ID.");
if (!$catalogue_id && !$subdivisions)
        die("Neither CATALOGUE_ID nor SUBDIVISIONS supplied.");

// список товаров магазина $shop_id [или указан $cc] типа $goods_types в разделах $subdivisions
// и выбрать $selected (type_id:goods_id) товары

error_reporting(E_ALL ^ E_NOTICE);

$NETCAT_FOLDER = join(strstr(__FILE__, "/") ? "/" : "\\", array_slice(preg_split("/[\/\\\]+/", __FILE__), 0, -5)).( strstr(__FILE__, "/") ? "/" : "\\" );
include_once ($NETCAT_FOLDER."vars.inc.php");
require_once ($ADMIN_FOLDER."function.inc.php");
require_once ($ADMIN_FOLDER."classificator.inc.php");
if (!($perm->isSupervisor() || $perm->isGuest())) {
    die("NO RIGHTS");
}
//LoadModuleEnv();
$MODULE_VARS = $nc_core->modules->get_module_vars();
?>
<html>
    <head>
        <title></title>
        <script>
            function save()
            {
                var val = [], src = document.form.goods;

                for (j=0; j < src.options.length; j++)
                {
                    if (src.options[j].selected) val.push(src.options[j].value);
                }
                parent.document.adminForm.f_Goods.value = val.join(",");
            }
        </script>
    </head>

    <body leftmargin=0 topmargin=0>


        <form name=form style="margin:0">
            <select multiple name=goods style="width:100%; height:100%" onchange="save()">
                <?
                if (!$goods_types) {
                    /* $goods_types = assoc_array("SELECT DISTINCT c.Class_ID
                      FROM Class as c, Field as f
                      WHERE c.Class_ID=f.Class_ID
                      AND f.Field_Name LIKE 'Price%'
                      AND (c.Class_Group='Netshop' OR c.Class_Group='Интернет-магазин')
                      ORDER BY c.Priority, c.Class_ID
                      "); */
                    $goods_types = NetShop::get_goods_table();
                } else {
                    $goods_types = explode(",", $goods_types);
                }


                $struct = GetStructure($shop_id, "Checked=1", ($subdivisions ? "get_children" : "plain"));

                $subdiv_ids = array();

                if ($subdivisions) {
                    $subdivisions_t = explode(",", $subdivisions);
                    if (count($subdivisions_t) > 1) {
                        foreach ($subdivisions_t as $subdivision) {
                            if (isset($struct[$subdivision]["Children"])) {
                                $subdiv_ids = array_merge(array($subdivision), $subdiv_ids, $struct[$subdivision]["Children"]);
                            } else {
                                $subdiv_ids = array_merge(array($subdivision), $subdiv_ids);
                            }
                        }
                        $subdiv_ids = join(",", $subdiv_ids);
                    } else {
                        $subdiv_ids = $subdivisions;
                    }
                }

                $goods = array();
                foreach ($goods_types as $goods_type) {
                    if ($subdivisions) {
                        $res = assoc_array("SELECT m.Message_ID, m.Name, m.Parent_Message_ID,
                                 IF (m.Parent_Message_ID=0, m.Message_ID, m.Parent_Message_ID) as item_parent,
                                 (m.Parent_Message_ID = 0) as is_parent
                          FROM Message$goods_type as m, Sub_Class as sc
                          WHERE m.Checked=1
                            AND m.Sub_Class_ID=sc.Sub_Class_ID
                            AND sc.Subdivision_ID IN ($subdiv_ids)
                          ORDER BY item_parent, is_parent DESC");
                    } else {
                        $res = assoc_array("SELECT m.Message_ID, m.Name, m.Parent_Message_ID,
                                 IF (m.Parent_Message_ID=0, m.Message_ID, m.Parent_Message_ID) as item_parent,
                                 (m.Parent_Message_ID = 0) as is_parent
                          FROM Message$goods_type as m, Sub_Class as sc
                          WHERE m.Checked=1
                            AND m.Sub_Class_ID=sc.Sub_Class_ID
                            AND sc.Catalogue_ID=$catalogue_id
                          ORDER BY item_parent, is_parent DESC");
                    }

                    $tp = ",$selected,";
                    foreach ($res as $row) {
                        print "<option value='$goods_type:$row[Message_ID]'";
                        if ($selected && strpos($tp, ",$goods_type:$row[Message_ID],") !== false)
                                print " selected";
                        print ">";
                        if ($row["Parent_Message_ID"]) {
                            print "&nbsp; &nbsp; &nbsp; ";
                        }
                        print "[$row[Message_ID]] $row[Name]\n";
                    }
                }
                ?>
            </select>
        </form>
    </body>
</html>
