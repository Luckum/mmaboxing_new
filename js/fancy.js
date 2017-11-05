$(document).ready(function() {
    if ($(window).width() > 810) {
        $(".send_material").fancybox({
            'width'         : 730,
            'height'        : 465,
            'autoScale'     : false,
            'transitionIn'  : 'none',
            'transitionOut' : 'none',
            'type'          : 'iframe',
            'overlayColor'  : '#333333'
        });
    }
    
    if ($(window).width() > 450) {
        $(".auth_user").fancybox({
            'width'         : 370,
            'height'        : 485,
            'autoScale'     : false,
            'transitionIn'  : 'none',
            'transitionOut' : 'none',
            'type'          : 'iframe',
            'overlayColor'  : '#333333'
        });
    }
});