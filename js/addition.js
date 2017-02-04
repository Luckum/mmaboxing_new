
function showArResults()
{
	$(".vote .answers").hide();
	$(".vote .answRes").show();
}

function loadUserInfo(user_id,user_name)
{
	if($('BODY').data('dlg'+user_id) == user_id)
	{
		$("#dleprofilepopup"+user_id).dialog( "open" );
	}
	else
	{
			$('BODY').data('dlg'+user_id,user_id);
			$("BODY").append("<div id='dleprofilepopup"+user_id+"' title='Пользователь: "+user_name+"'></div>");
			$.post( "/comments/?user_id="+user_id).done(function( data ) {
		   	$("#dleprofilepopup"+user_id).html(data).dialog().dialog( "open" );
		  });
	}
}