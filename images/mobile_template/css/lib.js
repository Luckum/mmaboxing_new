$(document).ready(function() {
	$('#middle table tr:even, #middle_shop table tr:even').addClass('tr_odd');
        //$('#middle #cart table tr:odd, #middle_shop table tr:odd').removeClass('tr_odd');
	$('#middle table tr:first th:first, #middle_shop table tr:first th:first').addClass('green');
        $('#search_form .advancedlink').css('display', 'none');
        $(".captcha_block input:text").css('width', '10%');
        
        /* Клик по фоткам в листалке в карточке товара */
	$('.slider .block').click(function() {
		$('.slider .block').removeClass('active');
		$(this).addClass('active');
		$('#item_full .img img').attr('src', $(this).find('a').attr('href'));
		return false;
	});
});