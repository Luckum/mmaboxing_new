$(function(){

    var scroll_nav = function(nav_selector, fn, offset){
        var scroll_points = {};
        var a_pointer = 'data-nc-ls-display-pointer',
            a_link = 'data-nc-ls-display-link';

        var is_on_screen = function(ob){
            var win = $(window);
            var bounds = ob.offset();
            var viewport = {}
            viewport.top = win.scrollTop() + offset;
            viewport.bottom = viewport.top + win.height();
            bounds.bottom = bounds.top + ob.outerHeight();
            return (!(viewport.bottom < bounds.top || viewport.top > bounds.bottom));
        }

        // Находим все контентные блоки
        $('div[' + a_pointer + ']').each(function(){
            var id = $.parseJSON($(this).attr(a_pointer)).subdivisionId;
            scroll_points[id] = {
                'obj': $(this),
                'links': []
            };
        });

        // Находим ссылки на контентные блоки
        $(nav_selector + ' a[' + a_link + ']').each(function(){
            var $this = $(this);
            var id = $.parseJSON($this.attr(a_link)).subdivisionId;
            if (typeof scroll_points[id] !== 'undefined') {
                scroll_points[id].links.push($this);
            }
        });

        // Удаляем блоки за которыми не нужно следить
        for (var k in scroll_points) {
            if (!scroll_points[k].links.length) {
                delete(scroll_points[k]);
            }
        }

        // scroll event
        $(window).scroll(function(){
            for (var k in scroll_points) {
                if (is_on_screen(scroll_points[k].obj)) {
                    for (var i in scroll_points[k].links) {
                        fn(scroll_points[k].links[i]);
                    }
                    break;
                }
            }
        });
    }

    scroll_nav('header', function($link){
        $('header li.active').removeClass('active');
        $link.parents('LI').addClass('active');
    }, 0);

    $(window).scroll(function(){
        var $header = $('HEADER');
        var $contactHolder = $('.page .contact-holder');
        if ($contactHolder.is(':visible')) {
            var scrollTop = $(window).scrollTop();
            if (scrollTop >= $contactHolder.height() + $contactHolder.offset().top) {
                $header.removeClass('relative');
            } else {
                $('NAV LI .contacts-button').parent().addClass('active').siblings().removeClass('active');
                $header.addClass('relative');
            }
        } else {
            $header.removeClass('relative');
        }
    });

    if ($('.nc-navbar').length) {
        $('HEADER').addClass('admin');
    }


    $('HEADER .contacts-button').click(function(){
        var $this = $(this);
        var $header = $('HEADER');

        var $contactHolder = $('.contact-holder');
        var scrollTop = $(window).scrollTop();

        var triggerScrollHandler = function(){
            setTimeout(function(){
                $(window).triggerHandler('scroll');
            }, 100);
        };

        if ($contactHolder.is(':visible')) {
            if (scrollTop >= ($contactHolder.height() + $contactHolder.offset().top)) {
                $('HTML,BODY').animate({
                    scrollTop: $contactHolder.offset().top
                });
            } else {
                $contactHolder.slideUp(function(){
                    $header.removeClass('active');
                    triggerScrollHandler();
                });
            }
        } else {
            var callback = function(){
                $contactHolder.slideDown(function(){
                    triggerScrollHandler();
                });
                $header.addClass('active');
            };
            if (scrollTop) {
                $('HTML,BODY').animate({
                    scrollTop: 0
                }, callback);
            } else {
                callback();
            }

        }

        triggerScrollHandler();

        return false;
    });

    $('.main-section .gallery .switcher A').on('click', function(){
        var $this = $(this);
        var $li = $this.closest('LI');

        if (!$li.hasClass('active')) {
            var index = $li.index('.main-section .gallery .switcher LI');
            $li.addClass('active').siblings().removeClass('active');

            var $slides = $('.main-section .gallery .slide');
            $slides.filter(':visible').stop().fadeOut();
            $slides.eq(index).fadeIn();
        }

        return false;
    });

    $('.main-section .gallery .btn-prev, .main-section .gallery .btn-next').on('click', function(){
        var $this = $(this);
        var $dots = $('.main-section .gallery .switcher LI');
        var index = $dots.filter('.active').index('.main-section .gallery .switcher LI');

        if ($this.hasClass('btn-prev')) {
            index--;
            if (index < 0) {
                index = $dots.length - 1;
            }
        } else {
            index++;
            if (index >= $dots.length) {
                index = 0;
            }
        }
        $dots.eq(index).find('A').triggerHandler('click');

        return false;
    });


    var change = function(index){
        if (!index) return false;

        var $faces = $('.company .collective LI');
        $faces.filter('.active').removeClass('active');

        if (index < 0) {
            $faces.eq(1).addClass('active');
            $faces.last().hide(200, function(){
                $(this).insertBefore($faces.first()).show(200, function(){
                    change(index + 1)
                });
            });
        } else if (index > 0) {
            $faces.eq(3).addClass('active');
            $faces.first().hide(200, function(){
                $(this).insertAfter($faces.last()).show(200, function(){
                    change(index - 1)
                });
            });
        }
        return false;
    }

    $('.company .collective li').click(function(){
        var index = $(this).index();
        change(index - 2);
    });

    $('.company .btn-prev, .company .btn-next').on('click', function(){
        return change($(this).hasClass('btn-prev') ? -1 : +1);
    });

    $('.write-us FORM').on('submit', function(){
        var $this = $(this);

        var $email = $(this).find('[name=f_Email]');
        var email = $email.val();

        var $text = $(this).find('[name=f_Text]');
        var text = $text.val();

        var emailPreg = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

        if (!email || !emailPreg.test(email)) {
            $email.focus();
            return false;
        }

        if (!text) {
            $text.focus();
            return false;
        }

        $.ajax({
            url: $this.attr('action'),
            type: 'post',
            data: $this.serializeArray(),
            dataType: 'json',
            success: function(data){
                if (typeof(data.result) != 'undefined' && data.result) {
                    alert('Ваше сообщение успешно отправлено!');
                    $email.val('');
                    $text.val('');
                } else {
                    alert('Произошла ошибка, перезагрузите форму');
                }
            },
            error: function(){
                alert('Произошла ошибка, перезагрузите форму');
            }
        });

        return false;
    });
});