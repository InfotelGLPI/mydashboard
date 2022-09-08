<?php
/*
 -------------------------------------------------------------------------
 MyDashboard plugin for GLPI
 Copyright (C) 2015-2022 by the MyDashboard Development Team.
 -------------------------------------------------------------------------

 LICENSE

 This file is part of MyDashboard.

 MyDashboard is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 MyDashboard is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with MyDashboard. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/**
 * Every Pie charts classes must inherit of this class
 * It sets basical parameters to display a pie chart with Flotr2
 * This widget class is meant to display data as a pie chart
 */
class PluginMydashboardPieChart extends PluginMydashboardChart {

    /**
     * @param array $graph_datas
     * @param array $graph_criterias
     *
     * @return string
     */
    static function launchPieGraph($graph_datas = [], $graph_criterias = []) {

        $onclick = 0;
        if (count($graph_criterias) > 0) {
            $onclick = 1;
        }
        $name           = $graph_datas['name'];
        $datas          = $graph_datas['data'];
        $ids            = $graph_datas['ids'];
        $label          = $graph_datas['label'] ?? "";
        $labels         = $graph_datas['labels'];
        $title          = $graph_datas['title'] ?? "";
        $comment        = $graph_datas['comment'] ?? "";
        $theme          = PluginMydashboardPreference::getPalette(Session::getLoginUserID());
        $json_criterias = json_encode($graph_criterias);

        $graph = "<script type='text/javascript'>
          var id$name = $ids;
          var canvas$name = echarts.init(document.getElementById('$name'), '$theme');
          window.onresize = function() {
            canvas$name.resize();
          };
          var option;

            option = {
//               title: {
//                text: '$title',
//                textStyle: {
//                  fontSize: '14',
//                  },
//                subtext: '$comment'
//              },
              tooltip: {
                trigger: 'item'
              },
              legend: {
                left: 'center',
                top: 'bottom',
                data: $labels
              },
              toolbox: {
                show: true,
                feature: {
                  dataView: { show: true, readOnly: false },
                  restore: { show: true },
                  saveAsImage: { show: true }
                }
              },
              calculable: true,
              grid: { left: 16, right: 32, top: 32, bottom: 32, containLabel: true },
              series: [
                        {
                          type: 'pie',
                          name: \"$label\",
                          radius: '50%',
                          data: $datas,
                          emphasis: {
                            itemStyle: {
                              shadowBlur: 10,
                              shadowOffsetX: 0,
                              shadowColor: 'rgba(0, 0, 0, 0.5)'
                            }
                          }
                        }
                      ]
            };
            
            option && canvas$name.setOption(option);
            //canvas$name.resize();
            canvas$name.on('click', function(params) {
              // Print name in console
            //  console.log(params);
              if ($onclick) {
                 var idx = params.dataIndex;
                 var tab = id$name;
                 var selected_id = tab[idx];
                 $.ajax({
                    url: '" . PLUGIN_MYDASHBOARD_WEBDIR . "/ajax/launchURL.php',
                    type: 'POST',
                    data:
                    {
                        selected_id:selected_id,
                        params: $json_criterias
                      },
                    success:function(response) {
                            window.open(response);
                          }
                 });
               }
});

          </script>";

        return $graph;
    }

    /**
     * @param array $graph_datas
     * @param array $graph_criterias
     *
     * @return string
     */
    static function launchPolarAreaGraph($graph_datas = [], $graph_criterias = []) {

        $onclick = 0;
        if (count($graph_criterias) > 0) {
            $onclick = 1;
        }
        $name           = $graph_datas['name'];
        $datas          = $graph_datas['data'];
        $ids            = $graph_datas['ids'];
        $label          = $graph_datas['label'] ?? "";
        $labels         = $graph_datas['labels'];
        $title          = $graph_datas['title'] ?? "";
        $comment        = $graph_datas['comment'] ?? "";
        $theme          = PluginMydashboardPreference::getPalette(Session::getLoginUserID());
        $json_criterias = json_encode($graph_criterias);

        $graph = "<script type='text/javascript'>

          var id$name = $ids;
          var canvas$name = echarts.init(document.getElementById('$name'), '$theme');
          window.onresize = function() {
            canvas$name.resize();
          };
          var option;

            option = {
//               title: {
//                text: '$title',
//                textStyle: {
//                  fontSize: '14',
//                  },
//                subtext: '$comment'
//              },
              tooltip: {
                trigger: 'item',
                formatter: '{a} <br/>{b} : {c} ({d}%)'
              },
              legend: {
                left: 'center',
                top: 'bottom',
                data: $labels
              },
              toolbox: {
                show: true,
                feature: {
                  mark: { show: true },
                  dataView: { show: true, readOnly: false },
                  restore: { show: true },
                  saveAsImage: { show: true }
                }
              },
//              grid: { left: 16, right: 32, top: 32, bottom: 32, containLabel: true },
              series: [
                        {
                          type: 'pie',
                          name: \"$label\",
                          radius: [20, 140],
                          roseType: 'area',
                          itemStyle: {
                            borderRadius: 5
                          },
                          label: {
                            show: false
                          },
                          emphasis: {
                            label: {
                              show: true
                            }
                          },
                          data: $datas,
                        }
                      ]
            };
            
            option && canvas$name.setOption(option);
            //canvas$name.resize();
            canvas$name.on('click', function(params) {
              // Print name in console
            //  console.log(params);
              if ($onclick) {
                 var idx = params.dataIndex;
                 var tab = id$name;
                 var selected_id = tab[idx];
                 $.ajax({
                    url: '" . PLUGIN_MYDASHBOARD_WEBDIR . "/ajax/launchURL.php',
                    type: 'POST',
                    data:
                    {
                        selected_id:selected_id,
                        params: $json_criterias
                      },
                    success:function(response) {
                            window.open(response);
                          }
                 });
               }
            });
          </script>";

        return $graph;
    }

    /**
     * @param array $graph_datas
     * @param array $graph_criterias
     *
     * @return string
     */
    static function launchDonutGraph($graph_datas = [], $graph_criterias = []) {

        $onclick = 0;
        if (count($graph_criterias) > 0) {
            $onclick = 1;
        }
        $name           = $graph_datas['name'];
        $datas          = $graph_datas['data'];
        $ids            = $graph_datas['ids'];
        $label          = $graph_datas['label'] ?? "";
        $labels         = $graph_datas['labels'];
        $title          = $graph_datas['title'] ?? "";
        $comment        = $graph_datas['comment'] ?? "";
        $theme          = PluginMydashboardPreference::getPalette(Session::getLoginUserID());
        $json_criterias = json_encode($graph_criterias);

        $graph = "<script type='text/javascript'>
            var id$name = $ids;
          var canvas$name = echarts.init(document.getElementById('$name'), '$theme');
          window.onresize = function() {
            canvas$name.resize();
          };
          var option;

            option = {
//               title: {
//                text: '$title',
//                textStyle: {
//                  fontSize: '14',
//                  },
//                subtext: '$comment'
//              },
              tooltip: {
                trigger: 'item',
                formatter: '{a} <br/>{b} : {c} ({d}%)'
              },
              legend: {
                left: 'center',
                top: 'bottom',
                data: $labels
              },
              toolbox: {
                show: true,
                feature: {
                  dataView: { show: true, readOnly: false },
                  restore: { show: true },
                  saveAsImage: { show: true }
                }
              },
              calculable: true,
              grid: { left: 16, right: 32, top: 32, bottom: 32, containLabel: true },
              series: [
                        {
                          type: 'pie',
                          radius: ['40%', '70%'],
                          avoidLabelOverlap: false,
                          name: \"$label\",
                          label: {
                            show: false,
                            position: 'center'
                          },
                          emphasis: {
                            label: {
                              show: true,
                              fontSize: '40',
                              fontWeight: 'bold'
                            }
                          },
                          labelLine: {
                            show: false
                          },
                          data: $datas,
                        }
                      ]
            };
            
            option && canvas$name.setOption(option);
            //canvas$name.resize();
            canvas$name.on('click', function(params) {
              // Print name in console
            //  console.log(params);
              if ($onclick) {
                 var idx = params.dataIndex;
                 var tab = id$name;
                 var selected_id = tab[idx];
                 $.ajax({
                    url: '" . PLUGIN_MYDASHBOARD_WEBDIR . "/ajax/launchURL.php',
                    type: 'POST',
                    data:
                    {
                        selected_id:selected_id,
                        params: $json_criterias
                      },
                    success:function(response) {
                            window.open(response);
                          }
                 });
               }
            });
          </script>";

        return $graph;
    }
}
