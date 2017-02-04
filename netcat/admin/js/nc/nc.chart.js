(function(nc){
    /*
        Обертка для класса генерации диаграмм: http://www.flotcharts.org/
    */

    // JSHint:
    /* global nc */

    //=== PRIVATE ==============================================================

    var flot_folder = nc.config('admin_path') + 'js/flot/';

    var required_js = [
        flot_folder + 'jquery.flot.min.js',
        flot_folder + 'jquery.flot.categories.min.js'
    ];

    var config = {};
    var $chart, $chart_elem, $table_elem, $legend, $tooltip;

    var default_config = {
        series: {
            lines: {
                show: false,
                steps: false
            },
            points: {show: false},
            bars: {
                show:      true,
                lineWidth: 0,
                barWidth:  0.9,
                align:     "center",
                fill:      true,
                fillColor: { colors: [ { opacity: 0.7 }, { opacity: 0.2 } ] }
            },
        },
        xaxis: {
            mode:       'categories',
            tickLength: 0
        },
        grid: {
            hoverable: true,
            color: "#CCC",
            // backgroundColor: "#FFF",
            borderWidth: 1, //number or object with "top", "right", "bottom" and "left" properties with different widths
            borderColor: '#DDD',
            margin: {top:20, left:20, right:20, bottom:10},
            // labelMargin: number
            axisMargin: 10,
        },
        shadowSize: 0,
        margin: 30,
        // highlightColor: '#F00',
        legend: {
            // show: true,
            labelFormatter: function(label, series) {
                var html = '<li><i style="background-color:'+series.color+'"></i>' + label + '</li> ';
                $legend.append(html);
            },
            // labelBoxBorderColor: '#F00',
            // noColumns: 1,
            // position: 'ne', //"ne" or "nw" or "se" or "sw"
            // margin: [-100, 0],
            // backgroundColor: null or color
            // backgroundOpacity: 0,
            // container: '#legend'
            // sorted: null/false, true, "ascending", "descending", "reverse", or a comparator
        }
    };

    var def = function(json, key, def) {
        if (nc.key_exists(key, json)) {
            return json[key];
        }
        return def;
    };

    var merge_json = function(a, b) {
        a = a || {};
        b = b || {};
        var result = {};

        // clone
        for (var k in a) {
            result[k] = a[k];
        }

        // merge
        for (k in b) {
            result[k] = b[k];
        }

        return result;
    };

    //=== PUBLIC ===============================================================

    var chart = function(obj, data, chart_config) {
        config = merge_json(default_config, chart_config);
        $chart = obj;

        $chart_elem = nc('<div class="nc-chart-canvas"></div>').appendTo($chart);
        $table_elem = nc('<div class="nc-chart-table" style="display:none"></div>').appendTo($chart);

        var $panel  = nc("<div class='nc-chart-legend'></div>").appendTo($chart);
        var $toggle = nc('<i class="nc-icon nc--dev-system-tables nc--right nc--hovered"></i>').appendTo($panel);
        $legend = nc("<ul></ul>").appendTo($panel);

        // Переключатель между таблицей и графиком
        $toggle.click(function(){
            var $chart = $(this).parents('div.nc-chart');
            $chart.find('div.nc-chart-table').slideToggle();
            $chart.find('div.nc-chart-canvas').slideToggle();
        });

        // Размеры графика
        if (nc.key_exists('width', config) || !$chart.width()) {
            $chart.width(def(config, 'width', 600));
        }
        if (nc.key_exists('height', config)) {
            var height = def(config, 'height', 300);
            $chart_elem.height(height);
            $table_elem.css({height:height, overflow:'auto'});
        }

        // Генерируем таблицу
        if ('data' in data[0]) {
            var table = '<table class="nc-table nc--small nc--striped nc--wide">';
                // thead
                table += '<tr>';
                    table += '<th></th>';
                    for (var i in data) {
                        table += '<th>' + data[i].label + '</th>';
                    }
                table += '</tr>';

                // tbody
                var label, value;
                for (i in data[0].data) {
                    label = (data[0].data[i][0] + '').replace('<br>', ' ');
                    table += '<tr>';
                        table += '<td>' + label + '</td>';
                        for (var j in data) {
                            value = data[j].data[i][1];
                            table += '<td>' + value + '</td>';
                        }
                    table += '</tr>';
                }
            table += '</table>';

            $table_elem.html(table);
        }

        // Инициализация графика
        $chart_elem.plot(data, config);
        chart.hoverable($chart_elem);
    };

    //--------------------------------------------------------------------------

    chart.__init = function() {
        for (var k in required_js) {
            nc('head').append('<script src="'+required_js[k]+'"></script>');
        }

        nc(function(){
            $tooltip = nc("<div id='nc_chart_tooltip' class='nc-tooltip'></div>")
                .css({position: 'absolute'})
                .appendTo('body');
        });
    };

    //-------------------------------------------------------------------------

    chart.set_defaults = function(defaults) {
        default_config  = merge_json(default_config, defaults);
    };

    //-------------------------------------------------------------------------

    chart.hoverable = function(selector) {
        nc(selector).bind("plothover", function (event, pos, item) {
            if (item) {
                var offset = $tooltip.height()/2 + 2;
                $tooltip.html(item.series.label + ": " + item.datapoint[1].toFixed(2))
                    .css({top: item.pageY - offset, left: item.pageX + offset})
                    .fadeIn(200);
            } else {
                $tooltip.hide();
            }
        });
    };

    //--------------------------------------------------------------------------

    nc.ext('chart', chart);

})(nc);
