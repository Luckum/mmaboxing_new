(function($){
$(document).ready(function(){

$('.enter_button').click(function(){
	$('#header_login').show();
	$('#header_top, #header_search').hide();
});

$('.search').click(function(){
	$('#search_expand').show();
	$('#search_turn').hide();
});

$('#search_turn_fixed').click(function(){
    $('#menu_fixed').show();
    $('#menu_2_fixed, #menu_3_fixed').hide();
});

$('.search_close').click(function(){
	$('#search_expand').hide();
    $('#search_turn').show();
});

$('.search_close_fixed').click(function(){
    $('#menu_fixed').hide();
    $('#menu_2_fixed, #menu_3_fixed').show();
});

$('#loginlink').click(function(){
	$('#login').arcticmodal();
});

$( "#dleprofilepopup" ).dialog({
    autoOpen: false,
    resizable: false,
    width: 500,
    modal: true,
    buttons: {
        "Просмотр профиля": function() {
            $( this ).dialog( "close" );
        }
    }
});


// Табы
$('.tab-title').click(function(){
	el = $(this);
	el.parent('.tabs-title').children('.tab-title').removeClass('active');
	el.addClass('active');

	el.parents('.tabs').find('.tab-content').removeClass('active');
	$('#c-'+el.attr('id')).addClass('active');
});


// Подписатьна на новости
	$('#subscribe').click(function(){
		var el = $(this);
		if (el.hasClass('checked')) {
			el.removeClass('checked').text('Подписаться на новости');
		}
		else {
			el.addClass('checked').text('Отписаться от новостей');
		}
	});


// Слайдшоу
$('.slider').flexslider({
	selector: '.slideshow > li',
	animation: 'fade',
	slideshow: false,
	slideshowSpeed: 10000,
	animationSpeed: 600,
	initDelay: 0,
	randomize: false,
    directionNav: false,
    controlNav: false,
});

// Выпадающее меню
$('#topmenu > li').hover(
	function(){
		$(this).children('ul').stop(true,false).slideDown(200);
	},
	function(){
		$(this).children('ul').stop(true,false).slideUp(200);
	}
);

});
})(jQuery)