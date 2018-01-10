
$(document).ready(function(){
	var counter=0;
	function img_resize() {
		var win_w = $(window).width();
		if (win_w<1260 && counter==0) {
			$('.content img').each(function(){
				var img_w = $(this).width();
				var img_wn = (img_w/100)*80;
				$(this).css({width: img_wn});
			})
			counter = counter+1;
		}
		if (win_w>1260 || win_w<742) {
			$('.content img').each(function(){
				$(this).css({width: 'auto'});
			})
			counter = 0;
		}
	}
	$(window).load(function(){
		img_resize();
	})
	$(window).resize(function(){
		var win_w = $(window).width();
		img_resize();
	})
})