<?php
/*
 -------------------------------------------------------------------------
 MyDashboard plugin for GLPI
 Copyright (C) 2015 by the MyDashboard Development Team.
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
    * PluginMydashboardPieChart constructor.
    */
   function __construct() {
      parent::__construct();
      $this->setOption('grid', ['verticalLines' => false, 'horizontalLines' => false]);
      $this->setOption('xaxis', ['showLabels' => false]);
      $this->setOption('yaxis', ['showLabels' => false]);
      $this->setOption('mouse', ['track' => true, 'trackFormatter' => self::getTrackFormatter()]);
      $this->setOption('legend', ['position' => 'ne', 'backgroundColor' => '#D2E8FF']);
      $this->setOption('pie', ['show'        => true, 'explode' => 0,
                               'fillOpacity' => PluginMydashboardColor::getOpacity()]);
   }


   /**
    * @return a JSon formatted string that can be used to add a widget in a dashboard (with sDashboard)
    */
   function getJSonDatas() {
      $jsonDatasLabels = [];

      foreach ($this->getTabDatas() as $sliceLabel => $sliceValue) {
         if (is_array($sliceValue)) {
            $this->debugError(__("You can't have more than one serie for a pie chart", 'mydashboard'));
            break;
         }
         $jsonDatasLabels[] = ["data" => [[0, round($sliceValue, 2)]], "label" => $sliceLabel];
      }

      return PluginMydashboardHelper::safeJsonData($jsonDatasLabels, $this->getOptions());
   }


   /**
    * Get a custom label format
    *
    * @param int    $id , the id of the format within {1,2,3,x}
    *      1: $prefix+<percentage>+% (+<value>+)+$suffix
    *      2: $prefix+<value>+$suffix
    *      3: empty
    *      x:(default) $prefix+<percentage>+%+$suffix
    * @param string $prefix , a custom $prefix
    * @param string $suffix , a custom $suffix
    * @param int    $minvalue
    *
    * @return string
    */
   static function getLabelFormatter($id = 0, $prefix = "", $suffix = "", $minvalue = 0) {
      $cond = "";
      if ($minvalue != 0) {
         $cond = "if(parseInt(value) < $minvalue) { return ''; }";
      }

      switch ($id) {
         case 1 :
            $funct = 'function(total, value){ ' . $cond . ' return "' . $prefix . ' "+(100 * value / total).toFixed(2)+"% (" +(value)+ ")"+" ' . $suffix . '"; }';
            break;
         case 2 :
            $funct = 'function(total, value){ ' . $cond . ' return "' . $prefix . ' "+value+" ' . $suffix . '"; }';
            break;
         case 3 :
            $funct = 'function(total, value){ return ""; }';
            break;
         default :
            $funct = 'function(total, value){ ' . $cond . ' return "' . $prefix . ' "+(100 * value / total).toFixed(2)+"%"+" ' . $suffix . '"; }';
            break;
      }
      return $funct;
   }

   /**
    * @param array $graph_datas
    * @param array $graph_criterias
    *
    * @return string
    */
   static function launchPieGraph($graph_datas = [], $graph_criterias = []) {
      global $CFG_GLPI;

      $onclick = 0;
      if (count($graph_criterias) > 0) {
         $onclick = 1;
      }
      $name            = $graph_datas['name'];
      $datas           = $graph_datas['data'];
      $ids             = $graph_datas['ids'];
      $label           = $graph_datas['label'];
      $labels          = $graph_datas['labels'];
      $backgroundColor = $graph_datas['backgroundColor'];
      $format          = isset($graph_datas['format']) ? $graph_datas['format'] : json_encode("");
      $json_criterias = json_encode($graph_criterias);

      $formatter = "formatter: function(value) {
                           let piformat = $format;
                           let percentage = value + piformat;
                           return  percentage;
                         },";
      if(isset($graph_datas["percentage"]) && $graph_datas["percentage"] == true ){
         $formatter = "formatter: (value, ctx) => {
                            let sum = 0;
                            let dataArr = ctx.chart.data.datasets[0].data;
                            dataArr.map(data => {
                                sum += data;
                            });
                            let percentage = (value*100 / sum).toFixed(1)+\"%\";
                            return percentage;
                        },";
      }
      $title = "";
      $disp = true;
      if(isset($graph_datas['title']) && !empty($graph_datas['title'])){
         $title =" title:{
            display:true,
                     text:'".$graph_datas['title']."'
                 },";

      }

      $graph = "<script type='text/javascript'>
            var dataPie$name = {
              datasets: [{
                data: $datas,
                label: \"$label\",
                backgroundColor: $backgroundColor,
              }],
              labels: $labels,
            };
             var id$name = $ids;
             var isChartRendered = false;
             var canvas$name = document.getElementById('$name');
             var ctx = canvas$name.getContext('2d');
             ctx.canvas.width = 700;
             ctx.canvas.height = 400;
             var $name = new Chart(ctx, {
               type: 'pie',
               data: dataPie$name,
               options: {
                 plugins: {
                    datalabels: {
                        $formatter
                     color: 'black',
                   },
                   labels: {
                     render: 'value',
//                     fontSize: 14,
//                     fontStyle: 'bold',
                     fontColor: '#FFF',
//                     fontFamily: 'Lucida Console, Monaco, monospace'
                   }
                },
                $title
                 responsive: true,
                 maintainAspectRatio: true,
//                  tooltips: {
//                      mode: 'label',
//                      callbacks: {
//                          label: function(tooltipItem, data) {
//                              return data['datasets'][0]['data'][tooltipItem['index']] + ' %';
//                          }
//                      }
//                  },
                 animation: {
                     onComplete: function() {
                       isChartRendered = true;
                     }
                   },
                   hover: {
                      onHover: function(event,elements) {
                         if ($onclick) {
                            $('#$name').css('cursor', elements[0] ? 'pointer' : 'default');
                         }
                       }
                    }
                }
             });
             canvas$name.onclick = function(evt) {
               var activePoints = $name.getElementsAtEvent(evt);
               if (activePoints[0] && $onclick) {
                 var chartData = activePoints[0]['_chart'].config.data;
                 var idx = activePoints[0]['_index'];
                 var label = chartData.labels[idx];
                 var value = chartData.datasets[0].data[idx];
                 var tab = id$name;
                 var selected_id = tab[idx];
                 $.ajax({
                    url: '" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/ajax/launchURL.php',
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
             };
             
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
      global $CFG_GLPI;

      $onclick = 0;
      if (count($graph_criterias) > 0) {
         $onclick = 1;
      }
      $name            = $graph_datas['name'];
      $datas           = $graph_datas['data'];
      $ids             = $graph_datas['ids'];
      $label           = $graph_datas['label'];
      $labels          = $graph_datas['labels'];
      $backgroundColor = $graph_datas['backgroundColor'];
      $format          = isset($graph_datas['format']) ? $graph_datas['format'] : json_encode("");
      $json_criterias = json_encode($graph_criterias);

      $graph = "<script type='text/javascript'>
            var dataPie$name = {
              datasets: [{
                data: $datas,
                label: \"$label\",
                backgroundColor: $backgroundColor,
              }],
              labels: $labels,
            };
             var id$name = $ids;
             var isChartRendered = false;
             var canvas$name = document.getElementById('$name');
             var ctx = canvas$name.getContext('2d');
             ctx.canvas.width = 700;
             ctx.canvas.height = 400;
             var $name = new Chart(ctx, {
               type: 'polarArea',
               data: dataPie$name,
               options: {
                 plugins: {
                    datalabels: {
                       formatter: function(value) {
                           let piformat = $format;
                           let percentage = value + piformat;
                           return  percentage;
                         },
                     color: 'white',
                   },
                   labels: {
                     render: 'value',
//                     fontSize: 14,
//                     fontStyle: 'bold',
                     fontColor: '#fff',
//                     fontFamily: 'Lucida Console, Monaco, monospace'
                   }
                },
                 responsive: true,
                 maintainAspectRatio: true,
                 animation: {
                     onComplete: function() {
                       isChartRendered = true;
                     }
                   },
                 hover: {
                      onHover: function(event,elements) {
                         if ($onclick) {
                            $('#$name').css('cursor', elements[0] ? 'pointer' : 'default');
                         }
                       }
                    }
                }
             });
             canvas$name.onclick = function(evt) {
               var activePoints = $name.getElementsAtEvent(evt);
               if (activePoints[0] && $onclick) {
                 var chartData = activePoints[0]['_chart'].config.data;
                 var idx = activePoints[0]['_index'];
                 var label = chartData.labels[idx];
                 var value = chartData.datasets[0].data[idx];
                 var tab = id$name;
                 var selected_id = tab[idx];
                 $.ajax({
                    url: '" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/ajax/launchURL.php',
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
             };
             
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
      global $CFG_GLPI;

      $onclick = 0;
      if (count($graph_criterias) > 0) {
         $onclick = 1;
      }
      $name            = $graph_datas['name'];
      $datas           = $graph_datas['data'];
      $ids             = $graph_datas['ids'];
      $label           = $graph_datas['label'];
      $labels          = $graph_datas['labels'];
      $backgroundColor = $graph_datas['backgroundColor'];
      $format          = isset($graph_datas['format']) ? $graph_datas['format'] : json_encode("");
      $json_criterias  = json_encode($graph_criterias);

      $graph = "<script type='text/javascript'>
            var dataPie$name = {
              datasets: [{
                data: $datas,
                label: \"$label\",
                backgroundColor: $backgroundColor,
              }],
              labels: $labels,
            };
             var id$name = $ids;
             var format = $format;
             var isChartRendered = false;
             var canvas$name = document.getElementById('$name');
             var ctx = canvas$name.getContext('2d');
             ctx.canvas.width = 700;
             ctx.canvas.height = 400;
             var $name = new Chart(ctx, {
               type: 'doughnut',
               data: dataPie$name,
               options: {
                 plugins: {
                    datalabels: {
                        formatter: function(value) {
                           let piformat = $format;
                           let percentage = value + piformat;
                           return  percentage;
                         },
                        color: 'white',
                        labels: {
                          color: 'white'
                      }
                   },
                },
                 responsive: true,
                 maintainAspectRatio: true,
                 animation: {
                     onComplete: function() {
                       isChartRendered = true;
                     }
                   },
//                 tooltips: {
//                     callbacks: {
//                       label: function(tooltipItem, data) {
//                        var dataset = data.datasets[tooltipItem.datasetIndex];
//                         var total = dataset.data.reduce(function(previousValue, currentValue, currentIndex, array) {
//                           return previousValue + currentValue;
//                         });
//                         var currentValue = dataset.data[tooltipItem.index];
//                         var percentage = Math.floor(((currentValue/total) * 100)+0.5);
//                         return percentage + \"%\";
//                       }
//                     }
//                   }
                }
             });
//             canvas$name.onclick = function(evt) {
//               var activePoints = $name.getElementsAtEvent(evt);
//               if (activePoints[0] && $onclick) {
//                 var chartData = activePoints[0]['_chart'].config.data;
//                 var idx = activePoints[0]['_index'];
//                 var label = chartData.labels[idx];
//                 var value = chartData.datasets[0].data[idx];
//                 var tab = id$name;
//                 var selected_id = tab[idx];
//                 $.ajax({
//                    url: '" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/ajax/launchURL.php',
//                    type: 'POST',
//                    data:
//                    {
//                        selected_id:selected_id,
//                        params: $json_criterias
//                      },
//                    success:function(response) {
//                            window.open(response);
//                          }
//                 });
//               }
//             };
             
          </script>";

      return $graph;
   }
}
