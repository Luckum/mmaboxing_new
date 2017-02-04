/*$Id: url_routes.js 4356 2011-03-27 10:24:29Z denis $*/
urlDispatcher.addRoutes({
    'module.minishop': NETCAT_PATH + 'modules/minishop/admin.php?view=info'
})
.addPrefixRouter('module.minishop.', function(path, params) {
    var url = NETCAT_PATH + "modules/minishop/admin.php?view=" + path.substr(16);
    if (params) {
        url += "&id=" + params;
    }
    mainView.loadIframe(url);
});
