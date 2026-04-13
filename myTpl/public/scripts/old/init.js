new WOW().init();
jQuery(document).ready(function($) {$(function() {$('.sticky-nav').hcSticky({top: 50});});
    $(function(){$('.main-nav').superfish({animation:{opacity:'show'},animationOut:{opacity:'hide'},});});
    $(function(){$('#responsive-menu').click(function(){$('.main-header ul.main-nav').toggle();});});
});