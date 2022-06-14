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
 * This widget class is meant to display data as a linechart
 */
class PluginMydashboardLineChart extends PluginMydashboardChart {

   /**
    * PluginMydashboardLineChart constructor.
    */
   function __construct() {
      parent::__construct();
      $this->setOption('grid', ['verticalLines' => true, 'horizontalLines' => true]);
      $this->setOption('xaxis', ['showLabels' => true]);
      $this->setOption('yaxis', ['showLabels' => true]);
      $this->setOption('mouse', ['track' => true, 'relative' => true]);
      $this->setOption('legend', ['position' => 'se', 'backgroundColor' => '#D2E8FF']);
      $this->setOption('lines', ['show' => true, 'fillOpacity' => PluginMydashboardColor::getOpacity()]);
   }

   /**
    * Get an array formatted for Flotr2 linechart
    * @param array $dataArray looks like [x1=>y1,x2=>y2, ...]
    * @return array coordinates looks like [[x1,y1],[x2,y2], ...]
    */
   private function getData($dataArray) {
      $data = [];
      foreach ($dataArray as $dataX => $dataY) {
         $data[] = [$dataX, $dataY];
      }
      return $data;
   }

   /**
    * Get data formatted in (pseudo) JSon representing the linechart and options for Flotr2
    * @return string A (pseudo) JSon formatted string for Flotr2
    */
   function getJSonDatas() {
      $data = [];
      $alone = false;
      foreach ($this->getTabDatas() as $legend => $dataY) {
         if (is_array($dataY)) {
            $data[] = ['data' => $this->getData($dataY), 'label' => $legend];
         } else {
            $alone = true;
            break;
         }

      }

      if ($alone) {
         $data[] = ['data' => $this->getData($this->getTabDatas())];
      }

      return PluginMydashboardHelper::safeJsonData($data, $this->getOptions());
   }

   /**
    * Get a specific TickFormatter function for Flotr2
    * @param int $id ,
    *      (default) : <value>
    * @return string, A JS function
    */
   static function getTickFormatter($id = 0) {
      switch ($id) {
         default :
            $funct = 'function(value){ console.log(value); return value; }';
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
   static function launchGraph($graph_datas = [], $graph_criterias = []) {
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
      $color          = (isset($graph_datas['backgroundColor']))?$graph_datas['backgroundColor']:'#1f77b4';
      $json_criterias = json_encode($graph_criterias);

      $linkURL = isset($graph_criterias['url']) ? $graph_criterias['url'] : PLUGIN_MYDASHBOARD_WEBDIR . "/ajax/launchURL.php";

      $graph = "<script type='text/javascript'>
            var dataLine$name = {
              datasets: [{
                data: $datas,
                label: \"$label\",
                borderColor: '$color',
//                            fill: false,
                            lineTension: '0.1',
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
               type: 'line',
               data: dataLine$name,
               options: {
                 plugins: {
                    datalabels: {
                     display:false,
                     color: '#000',
                   },
                   labels: {
                     render: 'value',
//                     fontSize: 14,
//                     fontStyle: 'bold',
                     fontColor: '#000',
//                     fontFamily: 'Lucida Console, Monaco, monospace'
                   }
                },
                 responsive: true,
                 maintainAspectRatio: true,
                 title:{
                     display:false,
                     text:'$name'
                 },
//                 tooltips: {
//                     enabled: false,
//                 },
                 tooltips: {
                     mode: 'index',
                     intersect: false
                 },
                 scales: {
                     xAxes: [{
                         stacked: true,
                     }],
                     yAxes: [{
                         ticks: {
                            beginAtZero: true,
                            min: 0,
                        },
                         stacked: true
                     }]
                 },
                 animation: {
                     onComplete: function() {
                       var chartInstance = this.chart,
                        ctx = chartInstance.ctx;
                        ctx.font = Chart.helpers.fontString(Chart.defaults.global.defaultFontSize, 
                        Chart.defaults.global.defaultFontStyle, Chart.defaults.global.defaultFontFamily);
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'bottom';
                        ctx.fillStyle = chartInstance.chart.config.options.defaultFontColor;
                        this.data.datasets.forEach(function (dataset, i) {
                            var meta = chartInstance.controller.getDatasetMeta(i);
                            meta.data.forEach(function (bar, index) {
                                var data = dataset.data[index];
                                ctx.fillText(data, bar._model.x, bar._model.y - 5);
                            });
                        });
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
                 var selected_id = idx;
                 //console.log(selected_id)
                 $.ajax({
                    url: '$linkURL',
                    type: 'POST',
                    data:
                    {
                        selected_id:selected_id,
                        label:label,
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

}
