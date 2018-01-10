jQuery(function($) {
    $.datepicker.regional['ru'] = {
        monthNames: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
        dayNamesMin: ['Вс','Пн','Вт','Ср','Чт','Пт','Сб'],
        firstDay: 1,
        closeText: 'X',
        currentText: 'Сегодня',
		prevText: '<',
		nextText: '>'
    };
    $.datepicker.setDefaults($.datepicker.regional['ru']);
});
