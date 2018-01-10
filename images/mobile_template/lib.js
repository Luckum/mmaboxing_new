$(document).ready(function() {
$('table tr:odd').addClass('tr_odd');
	$('table tr:first th:first').addClass('green');	
	
	/* focus � blur �� ���� "�����" */
	searchFocus = function() {
		if ($(this).attr('value') == '�����')
		{
			$(this).attr('value', '');
			$(this).css({'color': '#000', 'font-style': 'normal'});
		}
	}
	
	searchBlur = function() {
		if ($(this).attr('value') == '')
		{
			$(this).attr('value', '�����');
			$(this).css({'color': '#b3b3b3', 'font-style': 'italic'});
		}
	}
	
	$('#search_form input[type="text"]').bind('focus', searchFocus);
	$('#search_form input[type="text"]').bind('blur', searchBlur);
});