$(document).ready(function() {

	$('#middle table tr:odd, #middle_shop table tr:odd').addClass('tr_odd')
	$('#middle table tr:first th:first, #middle_shop table tr:first th:first').addClass('green')

	/* Наведение мыши на меню */
	$('#menu_top > li').mouseover(function() {
		$(this).addClass('hover');
	});

	$('#menu_top > li').mouseleave(function() {
		$(this).removeClass('hover');
	});

	/* Задаем размер стрелок в верхнем меню для IE 7 и ниже */
	/*if ($.browser.msie && $.browser.version <= 7.0)
	{
		$('#menu_top > li').each(function() {
			$(this).find('img').css('width', ($(this).width() + 20) + 'px');
		});
	}*/


	/* focus и blur по полю "Поиск" */
	searchFocus = function() {
		if ($(this).attr('value') == 'Поиск')
		{
			$(this).attr('value', '');
			$(this).css({'color': '#000', 'font-style': 'normal'});
		}
	}

	searchBlur = function() {
		if ($(this).attr('value') == '')
		{
			$(this).attr('value', 'Поиск');
			$(this).css({'color': '#b3b3b3', 'font-style': 'italic'});
		}
	}

	$('#search_form input[type="text"]').bind('focus', searchFocus);
	$('#search_form input[type="text"]').bind('blur', searchBlur);


	/* focus и blur по полю "Подписаться" */
	subscriptionFocus = function() {
		if ($(this).attr('value') == 'Введите ваш email...')
		{
			$(this).attr('value', '');
		}
	}

	subscriptionBlur = function() {
		if ($(this).attr('value') == '')
		{
			$(this).attr('value', 'Введите ваш email...');
		}
	}

	$('#subscription_field').bind('focus', subscriptionFocus);
	$('#subscription_field').bind('blur', subscriptionBlur);


	/* Открыть и закрыть форму авторизации */
	$('.login_text').click(function() {
		$('#login_form').show();
		return false;
	});

	$('#login_form .close').click(function() {
		$('#login_form').hide();
		return false;
	});



	/* Левое меню в магазине */
	$('#menu_left > li > a').each(function() {
		if ($(this).height() > 30)
			$(this).css({'padding-top': '0px', 'top': '-2px'});
	});



	/* Ровняем элементы в каталоге по заголовку */
	var catHeight = 0,
		i = 1;

	$('.cat_4 .block').each(function() {
		if ($(this).find('.name').height() >= catHeight)
			catHeight = $(this).find('.name').height();

		if (i % 4 == 0)
		{
			$('.cat_4 .block:eq(' + (i - 1) + ') .name, .cat_4 .block:eq(' + (i - 2) + ') .name, .cat_4 .block:eq(' + (i - 3) + ') .name, .cat_4 .block:eq(' + (i - 4) + ') .name').css('height', catHeight + 'px');
			catHeight = 0;
		}
		else if (i % 3 == 0 && (i + 1) % 4 == 0)
		{
			$('.cat_4 .block:eq(' + (i - 1) + ') .name, .cat_4 .block:eq(' + (i - 2) + ') .name, .cat_4 .block:eq(' + (i - 3) + ') .name').css('height', catHeight + 'px');
		}
		else if (i % 2 == 0 && (i + 2) % 4 == 0)
		{
			$('.cat_4 .block:eq(' + (i - 1) + ') .name, .cat_4 .block:eq(' + (i - 2) + ') .name').css('height', catHeight + 'px');
		}

		i++;
	});



	/* Листалка на главной */
	var positionPartners = 0,
		speedPartners = 400,
		elementsNumberOfPartners = $('#slider td').size(),
		elementsOnWindowPartners = 5,
		stepPartners = 126;

	clickRightPartners = function() {
		$('#slider .left').unbind('click');
		$('#slider .right').unbind('click');
		positionPartners++;

		$('#slider .animate').animate({
			'left': '-=' + stepPartners + 'px'
		}, speedPartners, function() {
			if (positionPartners > 0 && positionPartners < (elementsNumberOfPartners - elementsOnWindowPartners))
			{
				$('#slider .left').bind('click', clickLeftPartners);
				$('#slider .right').bind('click', clickRightPartners);
				$('#slider .left').css('background-position', '-12px 0px');
				$('#slider .right').css('background-position', '0px 0px');
			}
			else if (positionPartners == (elementsNumberOfPartners - elementsOnWindowPartners))
			{
				$('#slider .left').bind('click', clickLeftPartners);
				$('#slider .left').css('background-position', '-12px 0px');
				$('#slider .right').css('background-position', '0px -18px');
			}
		});
		return false;
	}

	clickLeftPartners = function() {
		$('#slider .left').unbind('click');
		$('#slider .right').unbind('click');
		positionPartners--;

		$('#slider .animate').animate({
			'left': '+=' + stepPartners + 'px'
		}, speedPartners, function() {
			if (positionPartners > 0 && positionPartners < (elementsNumberOfPartners - elementsOnWindowPartners))
			{
				$('#slider .left').bind('click', clickLeftPartners);
				$('#slider .right').bind('click', clickRightPartners);
				$('#slider .left').css('background-position', '-12px 0px');
				$('#slider .right').css('background-position', '0px 0px');
			}
			else if (positionPartners == 0)
			{
				$('#slider .right').bind('click', clickRightPartners);
				$('#slider .left').css('background-position', '-12px -18px');
				$('#slider .right').css('background-position', '0px 0px');
			}
		});
		return false;
	}

	$('#slider .right').bind('click', clickRightPartners);



	/* Листалка в карточке товара */
	var positionProduct = 0,
		speedProduct = 200,
		elementsNumberOfProduct = $('.catalogue_full_animate .block').size(),
		elementsOnWindowProduct = 5,
		stepProduct = 73;

	clickBottomProduct = function() {
		$('#cat_full_slider_block .top').unbind('click');
		$('#cat_full_slider_block .bottom').unbind('click');
		positionProduct++;

		$('.catalogue_full_animate').animate({
			'top': '-=' + stepProduct + 'px'
		}, speedProduct, function() {
			if (positionProduct > 0 && positionProduct < (elementsNumberOfProduct - elementsOnWindowProduct))
			{
				$('#cat_full_slider_block .top').bind('click', clickTopProduct);
				$('#cat_full_slider_block .bottom').bind('click', clickBottomProduct);
				$('#cat_full_slider_block .top').css('background-position', '-53px -10px');
				$('#cat_full_slider_block .bottom').css('background-position', '0px -10px');
			}
			else if (positionProduct == (elementsNumberOfProduct - elementsOnWindowProduct))
			{
				$('#cat_full_slider_block .top').bind('click', clickTopProduct);
				$('#cat_full_slider_block .top').css('background-position', '-53px -10px');
				$('#cat_full_slider_block .bottom').css('background-position', '-53px 0px');
			}
		});
		return false;
	}

	clickTopProduct = function() {
		$('#cat_full_slider_block .top').unbind('click');
		$('#cat_full_slider_block .bottom').unbind('click');
		positionProduct--;

		$('.catalogue_full_animate').animate({
			'top': '+=' + stepProduct + 'px'
		}, speedProduct, function() {
			if (positionProduct > 0 && positionProduct < (elementsNumberOfProduct - elementsOnWindowProduct))
			{
				$('#cat_full_slider_block .top').bind('click', clickTopProduct);
				$('#cat_full_slider_block .bottom').bind('click', clickBottomProduct);
				$('#cat_full_slider_block .top').css('background-position', '-53px -10px');
				$('#cat_full_slider_block .bottom').css('background-position', '0px -10px');
			}
			else if (positionProduct == 0)
			{
				$('#cat_full_slider_block .bottom').bind('click', clickBottomProduct);
				$('#cat_full_slider_block .top').css('background-position', '0px 0px');
				$('#cat_full_slider_block .bottom').css('background-position', '0px -10px');
			}
		});
		return false;
	}

	if ((elementsNumberOfProduct - elementsOnWindowProduct) > 0)
	{
		$('#cat_full_slider_block .bottom').bind('click', clickBottomProduct);
		$('#cat_full_slider_block .top, #cat_full_slider_block .bottom').show();
	}

	/* Клик по фоткам в листалке в карточке товара */
	$('.catalogue_full_animate .block').click(function() {
		$('.catalogue_full_animate .block').removeClass('active');
		$(this).addClass('active');
		$('#cat_full_slider_block .left .left img').attr('src', $(this).find('a').attr('href'));
		return false;
	});



	/* Листалка на гавной странице каталога */
	slider_shop = function() {
		var newHref = $(this).attr('href'),
			newSrc = $(this).attr('rel');

		$('#slider_shop').find('.img a').attr('href', newHref).find('img').attr('src', newSrc);
		$('#slider_shop .button a').removeClass('active');
		$(this).addClass('active');
		return false;
	}

	$('#slider_shop .button a').bind('click', slider_shop);





	/**
	*
	* Создание особых форм
	*
	*/

	$('div.no_special_style').find('input, textarea, select, button').addClass('no_special_style');

	/* Перебираем все input="text" и создаем особые стили */
	$('input[type="text"], input:not(#nc_password_change input)[type="password"]').each(function() {
            var input = $(this);
            
            if (input.hasClass('no_special_style')) {
                return null;
            }
                
            input.wrap('<div class="input_text">').after('<div class="right"></div>').after('<div class="left"></div>');
            if (input.hasClass('nc_inline_block')) {
                input.parent().css({display: 'inline-block'});
            }
	});


	/* Перебираем все textarea и создаем особые стили */
	$('textarea').each(function() {
		$(this).wrap('<div class="textarea">').after('<div class="textarea_tl"></div><div class="textarea_t"></div><div class="textarea_tr"></div><div class="textarea_l"></div><div class="textarea_r"></div><div class="textarea_bl"></div><div class="textarea_b"></div><div class="textarea_br"></div>');
		$(this).parent().before('<br />');
	});


	/* Перебираем все input="radio" и создаем особые стили */
	//$('input[type="radio"]').each(function() {
	//	$(this).wrap('<div class="radio">');
	//});

	/* Действия с radio */
	//$('.radio').click(function() {
	//	$(this).children('input').attr('checked', 'checked');
	//	$(this).parents('form').find('.radio').css('background-position', '0px 0px');
	//	$(this).css('background-position', '0px -14px');
	//});

	//Если выбрать пункт при помощи label
	//$('.radio input').change(function() {
	//	$(this).attr('checked', 'checked');
	//	$(this).parents('form').find('.radio').css('background-position', '0px 0px');
	//	$(this).parent().css('background-position', '0px -14px');
	//});

	//Проверяем все нажатые пункты при загрузке страницы
	//$('.radio input').each(function() {
	//	if ($(this).attr('checked'))
	//	{
	//		$(this).parent().css('background-position', '0px -14px');
	//	}
	//});


	/* Перебираем все input="checkbox" и создаем особые стили */
	$('input[type="checkbox"]').each(function() {
		//$(this).wrap('<div class="checkbox">');
	});
	
	/* устанавливает родителю-обертке стили в зависимости от состояния чекбокса */ 
	function handle_checkbox() {
		inp = $(this);
		inp.parent().css('background-position', '0px '+(inp.is(':checked') ? '-12px' : '0px'));
	}

	/* Действия с checkbox */
	$('div.checkbox').click(function(e) {
		// клик по лейблу генерит событие на инпуте, которое потом бабблится до обертки
		if (e.target != this) {
			return;
		}
		var inp = $('input:checkbox', $(this));
		var set_checked = !inp.is(":checked");
		inp.get(0).checked = set_checked;
		set_checked ? inp.attr('checked', 'checked') : inp.removeAttr('checked');
		inp.trigger('change');
	});

	//Если выбрать пункт при помощи label
	$('.checkbox input').change(handle_checkbox);

	//Проверяем все нажатые пункты при загрузке страницы
	$('.checkbox input').each(handle_checkbox);


	/* Действия с select */
	$('select').each(function() {
            
                if ($(this).hasClass('no_special_style')) {
                    return null;
                }
                
		$(this).wrap('<div class="select">').after('<div class="select_title"></div><div class="select_left"></div><div class="select_right"></div><ol class="select_list"></ol>');
		$(this).parent().before('<br />');

		var option = '',
			valueOption = '',
			printLi = '',
			name = $(this).attr('name');

		$(this).children('option').each(function() {
			option += $(this).html() + '::';
			valueOption += $(this).attr('value') + '::';
		});

		option = option.substr(0, option.length - 2);
		valueOption = valueOption.substr(0, valueOption.length - 2);

		option = option.split('::');
		valueOption = valueOption.split('::');

		for (var i in option)
		{
			printLi += '<li class="select_' + valueOption[i] + '">' + option[i] + '</li>';
		}

		var value = $(this).val();

		$(this).parent().find('ol').html(printLi);
		$(this).parent().parent().find('.select_title').html($(this).find('option[value=\''+value+'\']').html());
		$(this).parent().append('<input type="hidden" name="' + name + '" value="'+value+'" />');
		$(this).remove();
	});

	closeSelect = function(e)
	{
		var className = e.target.parentNode.className;
		if (className != 'select')
		{
			$('.select ol').hide();
			$('body').unbind('click');
		}
	}

	$('.select_title, .select_right').click(function() {
		$('.select ol').hide();
		$(this).parent().find('ol').show();
		$('body').bind('click', closeSelect);
	});

	$('.select ol li').click(function() {
		$(this).parent().parent().find('.select_title').html($(this).html());
		$(this).parent().parent().find('input[type="hidden"]').attr('value', $(this).attr('class').substr(7));
		$(this).parent().hide();
	});

});