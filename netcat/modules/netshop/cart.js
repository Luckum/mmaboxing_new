var ns_track_x, ns_track_y; // coordinates of the mouse
var ns_discount_shown = false;

function netshop_show_discount(discount_names_array, full_price, final_price)
{
    var div = document.getElementById('netshop_discount_div'),
    txt = NETCAT_MODULE_NETSHOP_APPLIED_DISCOUNTS+'<p>';

    for (var i in discount_names_array)
    {
        txt += '&mdash; '+discount_names_array[i] + '<br>';
    }

    txt += '</p>' + NETCAT_MODULE_NETSHOP_PRICE_WITHOUT_DISCOUNT + ': '+full_price
    + '<br>' + NETCAT_MODULE_NETSHOP_PRICE_WITH_DISCOUNT + ': '+final_price;

    // snap to cursor pos
    div.style.top = ns_track_y + 5 + 'px';
    div.style.left= ns_track_x + 5 + 'px';
    ns_discount_shown = true;

    div.innerHTML = txt;
    div.style.display = '';
}

function netshop_hide_discount()
{
    document.getElementById('netshop_discount_div').style.display = 'none';
    ns_discount_shown = false;
}

function netshop_track_mouse(event)
{
    if (!event) event = window.event; // MSIE
    ns_track_x = (event.x != undefined) ? event.x  + document.body.scrollLeft : event.pageX;
    ns_track_y = (event.y != undefined) ? event.y  + document.body.scrollTop  : event.pageY;

    if (ns_discount_shown)
    {
        var div = document.getElementById('netshop_discount_div');
        div.style.top = ns_track_y + 5 + 'px';
        div.style.left= ns_track_x + 5 + 'px';
    }
}
