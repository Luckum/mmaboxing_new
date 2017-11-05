/* $Id$ */

function nc_minishop_response (response) {
    jQuery('#nc_minishop_cart').html(response.cart);
    for ( i = 0; i < response.hash.length; i++ ) {
        jQuery('#nc_mscont_'+response.hash[i]).html(response.incart);
    }
    //notify
    if ( response.notify.type == 1 ) alert( response.notify.text );
    if ( response.notify.type == 2 ) jQuery("body").prepend( response.notify.text );
	
}


function nc_minishop_send_form (ident, url) {
    var params = {};
    jQuery('#'+ident).find(".nc_msvalues").each(function(index){
        var elem = jQuery(this);
        var aname = elem.attr("name");
        var avalue = elem.val();
        params[aname] = avalue;
    });
    
    jQuery.post( url,params,function(response){
        nc_minishop_response(response)
	},'json');
}