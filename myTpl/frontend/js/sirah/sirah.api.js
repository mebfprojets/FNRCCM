window.SIRAH ||(window.SIRAH={});
SIRAH.basePath || (SIRAH.basePath="/projectbase/templates/sirah/js/");

requirejs.config({
    baseUrl: SIRAH.basePath,
    paths: {
    	plugin     : "../plugins",
    	sirah      : "../sirah",
    	validators : "../validators",
        jquery     : "jquery-1.10.2.min",
        jq-ui-min  : "jquery-ui-1.10.3.min",
        jqueryui   : "../development/ui"
    }
});