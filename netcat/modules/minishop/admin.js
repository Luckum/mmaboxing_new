/* $Id$ */

function gen( ident, radioval, prefix ) {
    if (!confirm( ncLang.WarnReplace ) ) {
        return false;
    }
    
    if (prefix == undefined){
        prefix = '';
    }
    var params = {
        'tname' : ident
        ,
        'radio' : radioval
    };
	
    jQuery.get( 'ajax/gen.php',
        params,
        function(data){
            if (data.res) {
                selectedTextarea = jQuery('#'+prefix+data.ident);
                selectedTextarea.val(data.ttext);
                if (typeof selectedTextarea.codemirror == 'function') {
                    selectedTextarea.codemirror('setValue'); 
                }
            }
        },
        "json");
    return false;
}
