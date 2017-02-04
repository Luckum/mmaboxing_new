<script src='<?=$self_folder ?>filemanager.js' type='text/javascript'></script>


<div class='block_manager' style="margin-right:15px">
	<? if(count($breadcrumbs)>1): ?>
	<div class="nc-padding-10">
		<? foreach ($breadcrumbs as $i => $row): ?>
			<? if($i+1 == count($breadcrumbs)): ?>
				<?=$row['title'] ?>
			<? else: ?>
				<a href="<?=$row['link'] ?>"><?=$row['title'] ?></a> /
			<? endif ?>
		<? endforeach ?>
		&nbsp;<a href="#" onclick="nc_filemanagerObj.show_link_panel('<?=trim($dir, '/') ?>', 0); return false;">
			<i class="nc-icon nc--hovered nc--mod-linkmanager"></i>
		</a>
	</div>
	<br>
	<? endif ?>
</div>
<br>
<br>

<? /* Modal: Copy link */ ?>
<div id='nc_filemanager_link_panel' style='display:none; padding-right:25px'>
	<div id='nc_filemanager_link_panel_body' class='nc-form'>
		<br>
		<input type="text" id='nc_filemanager_link_absolute' class="nc--blocked" onfocus='this.select()'>
		<input type="text" id='nc_filemanager_link_global' class="nc--blocked" onfocus='this.select()'>
		<input type="text" id='nc_filemanager_link_server' class="nc--blocked" onfocus='this.select()'>
		<br><br>
	</div>
	<div class='nc_admin_form_buttons'>
		<button type='button' id='nc_filemanager_panel_close' class='nc-btn nc--left' onclick='$nc.modal.close()'><?=NETCAT_MODULE_FILEMANAGER_ADMIN_PANEL_CLOSE ?></button>
	</div>
</div>

<script type='text/javascript'>
	nc_filemanagerObj = new nc_Filemanager({
		MODULE_PATH:'<?=$this->MODULE_PATH ?>',
		url_prefix: '<?=$this->url_prefix ?>',
		DOCUMENT_ROOT: '<?=$nc_core->DOCUMENT_ROOT ?>',
		HTTP_HOST: '<?=$nc_core->HTTP_HOST ?>'
	});
</script>

<div class='block_edit'>
	<form method='post' action='admin.php' id='FileManagerEditFile'>
		<div class='resize_block'>
			<a class='textarea_shrink' href='#filemanager_edit' > &#x25BC; </a><a class='textarea_grow' href='#filemanager_edit'> &#x25B2; </a>
		</div>
		<textarea id='filemanager_edit' name='file_data' rows='20'><?=htmlentities($content, ENT_COMPAT, MAIN_ENCODING) ?></textarea>

		<script>
		(function (){
			var links = document.getElementsByTagName("A");
			var onclicker_grow   = function (event) { ShrinkArea(event, 50);  }
			var onclicker_shrink = function (event) { ShrinkArea(event,-50);  }
			for (var i = 0 ; i < links.length ; i++){
				if (links[i].className.search('textarea_shrink') != -1 ) {
					bindEvent(links[i], 'click', onclicker_grow );
				}
				if (links[i].className.search('textarea_grow') != -1 ) {
					bindEvent(links[i], 'click', onclicker_shrink);
				}
			}
		})();
		</script>

	<input type='hidden' name='file' value='<?=$path ?>'>
	<input type='hidden' name='phase' value='31'>
	<?=$nc_core->token->get_input() ?>
	</form>
</div>