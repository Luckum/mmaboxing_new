window.set_cart_count = function(count, append) {
    append = append || false;
    var $cart = $('#cart');
    if (append) {
        count = parseInt($cart.find('span.cart-count').html(), false) + count;
    }

    var attr = count ? {cart:['cart-empty', 'cart-full'], icon:['icon-cart-empty', 'icon-cart']} : {cart:['cart-full', 'cart-empty'], icon:['icon-cart', 'icon-cart-empty']};
    $cart.removeClass(attr.cart[0]).addClass(attr.cart[1]);
    $cart.find('i.'+attr.icon[0]).removeClass(attr.icon[0]).addClass(attr.icon[1]);
    $cart.find('span.cart-count').html(count);
};

window.cart_handler = function(data) {
    if (data && typeof data.cart_count !== 'undefined') {
        var $cart = $('#cart');
        window.set_cart_count(data.cart_count);

        var $src_img = $(this).parents('div.item, div.item_full').find('img');
        var $img     = $src_img.clone();
        var css      = $src_img.offset();
        css.position = 'absolute';
        css.width    = $src_img.width();
        css.height   = $src_img.height();
        $img.css(css);
        $('body').append($img);

        var animate = $cart.offset();
        animate.width   = $cart.width();
        animate.height  = $cart.height();
        animate.opacity = 0;
        $img.animate(animate, function(){
            $(this).remove();
        });
    }
    return false;
};

window.refresh_cart_handler = function() {
    history.go(-1);
    return true;
};

window.cart_remove_item = function() {
    var $item = $(this).parents('div.item');
    $item.fadeOut(200);
    $item.find('input.qty').val(0);
    $('#netshop_cart_contents').submit();
    window.set_cart_count(-1, true);
    return false;
};

window.clear_cart_handler = function() {
    window.set_cart_count(0);
    return true;
};


(function($, window){

    var default_title  = $('title').html();

    var resize_content = function(){
        var navbar_h = parseInt($('body').css('margin-top'), false);
        var height = Math.max($('#main').height(), $(window).height() - navbar_h);
        $('#sidebar').css({'min-height': height});
    };

    //--------------------------------------------------------------------------
    // SLIDER
    //--------------------------------------------------------------------------

    var init_slider = function(){
        $('#slider').each(function(){
            var $slider = $(this);
            if ( $slider.attr('data-slider-is-init') ) {
                return;
            }
            $slider.attr('data-slider-is-init', true);

            var $slider_nav   = $('div.slider-nav', $slider);
            var $slider_items = $('div.slider-items', $slider);
            var $slider_w     = $('div.slider-wrapper', $slider);
            var $slider_rows  = $('div.slider-row', $slider);

            var current_slide = 0;
            var slider_width  = $slider.width();
            var total_slides  = $slider_rows.length;

            var slide_to = function (i) {
                current_slide = i;
                var $links = $slider_nav.find('a').removeClass('active');
                $( $links[i] ).addClass('active');
                $slider_items.animate({scrollLeft: $slider.width() * i}, 500);
            };
            $slider_rows.each(function(i){
                var $link = $('<a/>').attr('href','#').attr('class', i?'':'active').html(i + 1)
                    .click(function(){
                        slide_to(i);
                        return false;
                    });
                $slider_nav.append($link);
            });

            $(window).resize(function(){
                slider_width = $slider.width();
                $slider_rows.width( slider_width );
                $slider_items.scrollLeft( slider_width * current_slide );
                $slider_w.width( slider_width * total_slides );
            });
        });

        $(window).resize();
    };

    //--------------------------------------------------------------------------

    $(document).ajaxComplete(function() {
        init_slider();
        resize_content();

        // title
        var h1 = $('h1').first().html();
        $('title').html(h1 ? h1 : default_title);

        // active menu items
        var path = window.location.pathname.replace(/^\/(.*?)\/?[^\/]*$/i,'$1');
        $('#sidebar a').each(function(){
            if (path.split('/')[0] !== 'netcat') {
                if (path && $(this).attr('href').search(path) >= 0) {
                    $('#sidebar li').removeClass('active');
                    $(this).parents('LI').addClass('active');
                }
            }
        });
    });

    //--------------------------------------------------------------------------

    $(window).load(function(){
        resize_content();
        $(window).resize(function(){
            resize_content();
        });
    });

    init_slider();


    //--------------------------------------------------------------------------

})(jQuery, window);