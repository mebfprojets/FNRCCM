new WOW().init();

jQuery(document).ready(function($) {
    $(function(){$('.sticky-nav').hcSticky({top: 50});});
    $(function(){$('#responsive-menu').click(function(){$('.main-header ul.main-nav').toggle();});});
});