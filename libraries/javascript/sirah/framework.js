window.SIRAH ||(window.SIRAH={});

SIRAH.insert_modules = function(modules) {
    for (var modname in modules) {
        YUI_config.modules[modname] = modules[modname];
    }
};