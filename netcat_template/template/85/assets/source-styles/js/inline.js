module.exports = function() {
    // windows: SET NODE_PATH = path\to\AppData\Roaming\npm\node_modules\
    var stylus = require('stylus');
    return function(style){
        style.define('inline', stylus.url());
    }
};