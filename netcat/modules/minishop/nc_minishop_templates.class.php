<?php

/* $Id: nc_minishop_templates.class.php 4356 2011-03-27 10:24:29Z denis $ */

class nc_minishop_templates {

    protected $templates;
    protected $MODULE_PATH;

    public function __construct($module_path = '') {
        if ($module_path !== '') $this->MODULE_PATH = $module_path;
        
        $nc_core = nc_Core::get_object();
        $this->init_templates();
        $this->init_templates_fs();
    }

    public function __get($name) {
        if ($name == 'templates') {
            return $this->templates;
        }
    }

    private function init_templates() {
        // вспомогательные переменные
        $href = " href='".$this->MODULE_PATH."index.php?good[\$id][name]=\$name&amp;good[\$id][price]=\$price&amp;good[\$id][hash]=\$hash&good[\$id][uri]=\$uri' \".(\$this->settings['ajax'] ? \"onclick='jQuery.get(this.href,{},function(response){nc_minishop_response(response)}, \\\"json\\\");return false;'\" : \"\").\" ";
        $img = "<img src='".$this->MODULE_PATH."img/cartput.gif' title='".NETCAT_MODULE_MINISHOP_PUT_GOOD_IN_CART."' alt='".NETCAT_MODULE_MINISHOP_PUT_GOOD_IN_CART."'/>";
        $atmp = "<div id='nc_mscont_\$hash' class='nc_msput'>\n%el\n</div>";
        $formtmp = "<div id='nc_mscont_\$hash' class='nc_msput'><form id='mscontform_\$hash' name='form_\$hash' method='post' action='".$this->MODULE_PATH."index.php'>\n".
                "<input class='nc_msvalues' type='hidden' name='good[\$id][name]' value='\$name'>\n".
                "<input class='nc_msvalues' type='hidden' name='good[\$id][price]' value='\$price'>\n".
                "<input class='nc_msvalues' type='hidden' name='good[\$id][hash]' value='\$hash'>\n".
                "<input class='nc_msvalues' type='hidden' name='good[\$id][uri]' value='\$uri'>\n".
                "%input".
                "<input \".(\$this->settings['ajax'] ? \"onclick='nc_minishop_send_form(this.form.id, this.form.action);return false;'\" : \"\").\" type='submit' title='".NETCAT_MODULE_MINISHOP_PUT_GOOD_IN_CART."' value='".NETCAT_MODULE_MINISHOP_PUT_GOOD_IN_CART."'>\n".
                "</form></div>";
        
        // шаблоны "Положить в корзину"
        $this->templates[0]['put'][nc_minishop::PUT_TEXT] = str_replace('%el', "<a ".$href." >".NETCAT_MODULE_MINISHOP_PUT_GOOD_IN_CART."</a>", $atmp);
        $this->templates[0]['put'][nc_minishop::PUT_IMG] = str_replace('%el', "<a ".$href." >".$img."</a>", $atmp);
        $this->templates[0]['put'][nc_minishop::PUT_TEXTIMG] = str_replace('%el', "<a ".$href." >".$img."</a><a ".$href.">".NETCAT_MODULE_MINISHOP_PUT_GOOD_IN_CART."</a>", $atmp);

        $this->templates[0]['put'][nc_minishop::PUT_BUTTON] = str_replace('%input', "<input class='nc_msvalues' type='hidden' name='good[\$id][quantity]' value='1'>\n", $formtmp);
        $this->templates[0]['put'][nc_minishop::PUT_FORM] = str_replace('%input', "<input class='nc_msvalues' type='text'   name='good[\$id][quantity]' size='2'  value='1'>\n", $formtmp);

        // шаблон массового добавления в корзину
        $this->templates[0]['massput']['header'] = "<form id='mscontmassform' name='mscontmassform' method='post' action='".$this->MODULE_PATH."index.php'>\n<input class='nc_msvalues' type='hidden' name='massput' value='1' />\n";
        $this->templates[0]['massput']['template'] = "<div id='nc_mscont_\$hash' class='nc_msput'>".
                "<input class='nc_msvalues' type='hidden' name='good[\$id][name]' value='\$name'>\n".
                "<input class='nc_msvalues' type='hidden' name='good[\$id][price]' value='\$price'>\n".
                "<input class='nc_msvalues' type='hidden' name='good[\$id][hash]' value='\$hash'>\n".
                "<input class='nc_msvalues' type='hidden' name='good[\$id][uri]' value='\$uri'>\n".
                NETCAT_MODULE_MINISHOP_PUT_GOOD_IN_CART.": ".
                "<input class='nc_msvalues' type='text' name='good[\$id][quantity]' size='2'  value='0'>\n".
                NETCAT_MODULE_MINISHOP_PIECES.". ".
                "</div>";
        $this->templates[0]['massput']['button'] = "<input \".(\$this->settings['ajax'] ? \"onclick='nc_minishop_send_form(this.form.id, this.form.action);return false;'\" : \"\").\" type='submit' value='".NETCAT_MODULE_MINISHOP_MASSPUT_GOOD_IN_CART."'></form>";
        $this->templates[0]['massput']['footer'] = "";

        // шаблон "Уже в корзине"
        $this->templates[0]['incart'][nc_minishop::PUT_TEXT] = NETCAT_MODULE_MINISHOP_GOOD_ALREADY_IN_CART;
        $this->templates[0]['incart'][nc_minishop::PUT_IMG] = "<img src='".$this->MODULE_PATH."img/already.gif' alt='".NETCAT_MODULE_MINISHOP_GOOD_ALREADY_IN_CART."' title='".NETCAT_MODULE_MINISHOP_GOOD_ALREADY_IN_CART."' />";

        $this->templates[0]['cart']['empty'] = NETCAT_MODULE_MINISHOP_CART_EMPTY_TEXT;
        $this->templates[0]['cart']['nonempty'] = NETCAT_MODULE_MINISHOP_CART_ATYOURS." <a href='\$carturl' title='".NETCAT_MODULE_MINISHOP_GO_TO_CART."'>".NETCAT_MODULE_MINISHOP_CART_TITLE2."</a> \$cartcount \".nc_numeral_inclination(\$cartcount,  array('".NETCAT_MODULE_MINISHOP_CART_GOODS_COUNT1."', '".NETCAT_MODULE_MINISHOP_CART_GOODS_COUNT2."', '".NETCAT_MODULE_MINISHOP_CART_GOODS_COUNT3."') ).\" ".NETCAT_MODULE_MINISHOP_CART_GOODS_SUM." \$cartsum \$cartcurrency \n<br/>\n<a href='\$orderurl'>".NETCAT_MODULE_MINISHOP_CART_ORDER."</a>";

        $this->templates[0]['notify'][nc_minishop::NOTIFY_ALERT] = NETCAT_MODULE_MINISHOP_GOOD_PUT_IN_CART_OK;
        $this->templates[0]['notify'][nc_minishop::NOTIFY_DIV] = NETCAT_MODULE_MINISHOP_GOOD_PUT_IN_CART_OK."\n<div>\n\t<a href='\$carturl'>".NETCAT_MODULE_MINISHOP_GO_TO_CART."</a>\n\t<a href='\$orderurl'>".NETCAT_MODULE_MINISHOP_CART_ORDER."</a>\n</div>";
    }
    
        private function init_templates_fs() {
        // вспомогательные переменные
        $href = " href='".$this->MODULE_PATH."index.php?good[<?= \$id ?>][name]=<?= \$name ?>&amp;good[<?= \$id ?>][price]=<?= \$price ?>&amp;good[<?= \$id ?>][hash]=<?= \$hash ?>&good[<?= \$id ?>][uri]=<?= \$uri ?>' <?= ( \$this->settings['ajax'] ? \"onclick='jQuery.get(this.href,{},function(response){nc_minishop_response(response)}, \\\"json\\\");return false;'\" : \"\") ?> ";
        $img = "<img src='".$this->MODULE_PATH."img/cartput.gif' title='".NETCAT_MODULE_MINISHOP_PUT_GOOD_IN_CART."' alt='".NETCAT_MODULE_MINISHOP_PUT_GOOD_IN_CART."'/>";
        $atmp = "<div id='nc_mscont_<?= \$hash ?>' class='nc_msput'>\n%el\n</div>";
        $formtmp = "<div id='nc_mscont_<?= \$hash ?>' class='nc_msput'><form id='mscontform_<?= \$hash ?>' name='form_<?= \$hash ?>' method='post' action='".$this->MODULE_PATH."index.php'>\n".
                "<input class='nc_msvalues' type='hidden' name='good[<?= \$id ?>][name]' value='<?= \$name ?>'>\n".
                "<input class='nc_msvalues' type='hidden' name='good[<?= \$id ?>][price]' value='<?= \$price ?>'>\n".
                "<input class='nc_msvalues' type='hidden' name='good[<?= \$id ?>][hash]' value='<?= \$hash ?>'>\n".
                "<input class='nc_msvalues' type='hidden' name='good[<?= \$id ?>][uri]' value='<?= \$uri ?>'>\n".
                "%input".
                "<input \<?= (\$this->settings['ajax'] ? \"onclick='nc_minishop_send_form(this.form.id, this.form.action);return false;'\" : \"\") ?> type='submit' title='".NETCAT_MODULE_MINISHOP_PUT_GOOD_IN_CART."' value='".NETCAT_MODULE_MINISHOP_PUT_GOOD_IN_CART."'>\n".
                "</form></div>";
        
        // шаблоны "Положить в корзину"
        $this->templates[1]['put'][nc_minishop::PUT_TEXT] = str_replace('%el', "<a ".$href." >".NETCAT_MODULE_MINISHOP_PUT_GOOD_IN_CART."</a>", $atmp);
        $this->templates[1]['put'][nc_minishop::PUT_IMG] = str_replace('%el', "<a ".$href." >".$img."</a>", $atmp);
        $this->templates[1]['put'][nc_minishop::PUT_TEXTIMG] = str_replace('%el', "<a ".$href." >".$img."</a><a ".$href.">".NETCAT_MODULE_MINISHOP_PUT_GOOD_IN_CART."</a>", $atmp);

        $this->templates[1]['put'][nc_minishop::PUT_BUTTON] = str_replace('%input', "<input class='nc_msvalues' type='hidden' name='good[<?= \$id ?>][quantity]' value='1'>\n", $formtmp);
        $this->templates[1]['put'][nc_minishop::PUT_FORM] = str_replace('%input', "<input class='nc_msvalues' type='text'   name='good[<?= \$id ?>][quantity]' size='2'  value='1'>\n", $formtmp);

        // шаблон массового добавления в корзину
        $this->templates[1]['massput']['header'] = "<form id='mscontmassform' name='mscontmassform' method='post' action='".$this->MODULE_PATH."index.php'>\n<input class='nc_msvalues' type='hidden' name='massput' value='1' />\n";
        $this->templates[1]['massput']['template'] = "<div id='nc_mscont_<?= \$hash ?>' class='nc_msput'>".
                "<input class='nc_msvalues' type='hidden' name='good[<?= \$id ?>][name]' value='<?= \$name ?>'>\n".
                "<input class='nc_msvalues' type='hidden' name='good[<?= \$id ?>][price]' value='<?= \$price ?>'>\n".
                "<input class='nc_msvalues' type='hidden' name='good[<?= \$id ?>][hash]' value='<?= \$hash ?>'>\n".
                "<input class='nc_msvalues' type='hidden' name='good[<?= \$id ?>][uri]' value='<?= \$uri ?>'>\n".
                NETCAT_MODULE_MINISHOP_PUT_GOOD_IN_CART.": ".
                "<input class='nc_msvalues' type='text' name='good[<?= \$id ?>][quantity]' size='2'  value='0'>\n".
                NETCAT_MODULE_MINISHOP_PIECES.". ".
                "</div>";
        $this->templates[1]['massput']['button'] = "<input \".(\$this->settings['ajax'] ? \"onclick='nc_minishop_send_form(this.form.id, this.form.action);return false;'\" : \"\").\" type='submit' value='".NETCAT_MODULE_MINISHOP_MASSPUT_GOOD_IN_CART."'></form>";
        $this->templates[1]['massput']['footer'] = "";

        // шаблон "Уже в корзине"
        $this->templates[1]['incart'][nc_minishop::PUT_TEXT] = NETCAT_MODULE_MINISHOP_GOOD_ALREADY_IN_CART;
        $this->templates[1]['incart'][nc_minishop::PUT_IMG] = "<img src='".$this->MODULE_PATH."img/already.gif' alt='".NETCAT_MODULE_MINISHOP_GOOD_ALREADY_IN_CART."' title='".NETCAT_MODULE_MINISHOP_GOOD_ALREADY_IN_CART."' />";

        $this->templates[1]['cart']['empty'] = NETCAT_MODULE_MINISHOP_CART_EMPTY_TEXT;
        $this->templates[1]['cart']['nonempty'] = NETCAT_MODULE_MINISHOP_CART_ATYOURS." <a href='<?= \$carturl ?>' title='".NETCAT_MODULE_MINISHOP_GO_TO_CART."'>".NETCAT_MODULE_MINISHOP_CART_TITLE2."</a> <?= \$cartcount ?> <?= nc_numeral_inclination(\$cartcount,  array('".NETCAT_MODULE_MINISHOP_CART_GOODS_COUNT1."', '".NETCAT_MODULE_MINISHOP_CART_GOODS_COUNT2."', '".NETCAT_MODULE_MINISHOP_CART_GOODS_COUNT3."') ) ?> ".NETCAT_MODULE_MINISHOP_CART_GOODS_SUM." <?= \$cartsum ?> <?= \$cartcurrency ?> \n<br/>\n<a href='<?= \$orderurl ?>'>".NETCAT_MODULE_MINISHOP_CART_ORDER."</a>";

        $this->templates[1]['notify'][nc_minishop::NOTIFY_ALERT] = NETCAT_MODULE_MINISHOP_GOOD_PUT_IN_CART_OK;
        $this->templates[1]['notify'][nc_minishop::NOTIFY_DIV] = NETCAT_MODULE_MINISHOP_GOOD_PUT_IN_CART_OK."\n<div>\n\t<a href='<?= \$carturl ?>'>".NETCAT_MODULE_MINISHOP_GO_TO_CART."</a>\n\t<a href='<?= \$orderurl ?>'>".NETCAT_MODULE_MINISHOP_CART_ORDER."</a>\n</div>";
    }
}