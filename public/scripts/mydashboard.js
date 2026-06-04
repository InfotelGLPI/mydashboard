/*
 -------------------------------------------------------------------------
 MyDashboard plugin for GLPI
 Copyright (C) 2006-2014 by the mydashboard Development Team.

 https://forge.indepnet.net/projects/mydashboard
 -------------------------------------------------------------------------

 LICENSE

 This file is part of mydashboard.

 MyDashboard is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 MyDashboard is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with mydashboard. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

// onMaximize = new Array();
// onMinimize = new Array();
// onInit = new Array();

//this object contains all methods to manage the dashboard
var mydashboard = {

    //Refresh all widgets that can be refreshed
    refreshAll: function () {
        // this.log(this.language.refreshAll);
        $('.refresh-icon').trigger('click');
    },
    //Launch the automatic refresh with a specified delay
    automaticRefreshAll: function (delay) {
        setInterval(function () {
            refreshAll();
        }, delay);
    },
};

const observer = new MutationObserver(() => {

    const charts = [];

    document.querySelectorAll('*').forEach(el => {
        const inst = echarts.getInstanceByDom(el);
        if (inst) charts.push(inst);
    });

    console.log(charts)

    charts.forEach(chart => {
        const opt = chart.getOption();

        if (opt.toolbox?.[0]?.feature?.dataView) {
            opt.toolbox[0].feature.dataView.optionToContent = function (opt) {

                var axisData = opt.xAxis[0].data;
                var series = opt.series;

                var table =
                    '<table class="table table-hover" style="width:100%;text-align:center"><tbody><tr>'
                    + '<td>  </td>';
                for (var i = 0, l = series.length; i < l; i++) {
                    table += '<td>' + series[i].name + '</td>'
                }
                table += '</tr>';
                for (var j = 0, l = axisData.length; j < l; j++) {
                    table += '<tr>' + '<td>' + axisData[j] + '</td>';
                    for (var i = 0, m = series.length; i < m; i++) {
                        table += '<td>' + series[i].data[j] + '</td>'
                    }
                    table += '</tr>';
                }

                table += '</tbody></table>';
                return table;
            };

            chart.setOption(opt);
        }
    });
});


observer.observe(document.body, { childList: true, subtree: true });


/**
 *  Load plugin scripts on page start
 */
//(function ($) {
//    $.fn.mydashboard_load_scripts = function () {

//        init();

// Start the plugin
//        function init() {
//            $(document).ready(function () {
//            var path = 'plugins/mydashboard/';
//            var url = window.location.href.replace(/front\/.*/, path);
//            if (window.location.href.indexOf('plugins') > 0) {
//                url = window.location.href.replace(/plugins\/.*/, path);
//            }

// Send data
//            $.ajax({
//                url: url+'ajax/loadscripts.php',
//                type: "POST",
//                dataType: "html",
//                data: 'action=load',
//                success: function (response, opts) {
//                    var scripts, scriptsFinder = /<script[^>]*>([\s\S]+?)<\/script>/gi;
//                    while (scripts = scriptsFinder.exec(response)) {
//                        eval(scripts[1]);
//                    }
//                }
//            });
//            if($("#mydashboard_link").length == 0) {
//                $("#c_preference ul #bookmark_link").before("\
//                <li id='mydashboard_link'>\
//                    <a href='#' id='showMydashboardMenuLink'>\
//                        <i id='mydashboard_icon' class='fas fa-tachometer-alt fa-2x' title='' class='button-icon'></i>\
//                    </a>\
//                </li>");
//            }
//            });
//        }

//        return this;
//    }
//}(jQuery));

//$(document).mydashboard_load_scripts();
