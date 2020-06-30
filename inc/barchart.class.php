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
 * Class PluginMydashboardBarChart
 */
abstract class PluginMydashboardBarChart extends PluginMydashboardChart {

   private $orientation = "v";

   /**
    * PluginMydashboardBarChart constructor.
    */
   function __construct() {
      parent::__construct();
      $this->setOption('grid', ['verticalLines' => true, 'horizontalLines' => true]);
      $this->setOption('xaxis', ['showLabels' => true]);
      $this->setOption('yaxis', ['showLabels' => true]);
      $this->setOption('mouse', ['track' => true, 'relative' => true]);
      $this->setOption('legend', ['position' => 'se', 'backgroundColor' => '#D2E8FF']);
      $this->setOption('bars', ['show' => true]);
      $this->setOption('bars', ['fillOpacity' => PluginMydashboardColor::getOpacity()]);
   }


   /**
    * Sets the orientation of the barchart
    * @param string $_o , orientation
    *        $_o is only accepted within "h" or "v"
    */
   function setOrientation($_o) {
      $possibleOrientations = ["h", "v"];
      if (in_array($_o, $possibleOrientations)) {
         $this->orientation = $_o;
      }
   }

   /**
    * Return an array of two item ordered according to orientation
    * @param type $dataValue , the Y value
    * @param type $dataLabel , the X value
    * @return array like [X,Y] or [Y,X]
    */
   private function getCouple($dataValue, $dataLabel) {
      switch ($this->orientation) {
         case "h" :
            return [$dataValue * 1, $dataLabel];
         case "v" :
            return [$dataLabel, $dataValue * 1];
      }
   }

   /**
    * If there is more than one serie the format of the data for Flotr2 isn't the same
    * This method return a formatted array for few series
    * @return array
    */
   private function getStackedFormat() {
      //If we need stacked format we can guess that we want a stacked barchart
      $options = $this->getOptions();
      if (!isset($options['bar']['stacked'])) {
         $this->setOption('bars', ['stacked' => true]);
      }
      $jsonDatasLabels = [];

      //every item of tabDatas should be a serie for the stacked bar chart, it's labelled by its key
      foreach ($this->getTabDatas() as $serieLabel => $serie) {
         $data = [];
         //each item of the serie is a couple X => Y
         //TODO handle when X is not numeric
         $count = 0;
         foreach ($serie as $label => $item) {
            if (is_numeric($label)) {
               array_push($data, $this->getCouple($item, $label));
            } else {
               array_push($data, $this->getCouple($item, $count));
            }
            $count++;
         }

         //By default, if not specifically labelled, in the legend the serie name will look like "Serie 1"
         if (is_numeric($serieLabel)) {
            $serieLabel = __("Serie") . " " . $serieLabel;
         }

         //We ad this serie to the widget data
         $jsonDatasLabels[] = ["data" => $data, "label" => $serieLabel];
      }
      return $jsonDatasLabels;
   }

   /**
    * Get a json formatted string representing data and options for the barchart Flotr2
    * @return string
    */
   function getJSonDatas() {
      $stacked = false;
      $count = 0;
      $jsonDatasLabels = [];

      foreach ($this->getTabDatas() as $dataLabel => $dataValue) {
         //If dataValue is an array it means there are few Y-values for one X-values, by default we understand it as a stacked bar chart
         if (is_array($dataValue)) {
            $stacked = true;
            break;
         } else {
            //We have to check if dataLabel is numeric because to place a point on a chart it must have numerical coordinate
            if (is_numeric($dataLabel)) {
               $jsonDatasLabels[] = ["data" => [$this->getCouple($dataValue, $dataLabel)], "label" => $dataLabel];
            } else {   //If it's not numeric we have a counter to place values on every 1 step
               $jsonDatasLabels[] = ["data" => [$this->getCouple($dataValue, $count)], "label" => $dataLabel];
            }
            //Here is our step
            $count += 1;
         }
      }

      //If the barchart is considered as a stacked bar chart, we need a different format of data
      if ($stacked) {
         //getStackedFormat gives an array formatted to display a stacked bar chart
         $jsonDatasLabels = $this->getStackedFormat();
      }

      return PluginMydashboardHelper::safeJsonData($jsonDatasLabels, $this->getOptions());
   }

   /**
    * Get a label formatter javascript function for a barchart Flotr2
    * @param int $id
    *      0 : Y value (default)
    *      1 : X value
    * @return string
    */
   static function getLabelFormatter($id = 0) {
      switch ($id) {
         case 1 :
            $funct = 'function(o){ return o.x; }';
            break;
         default :
            $funct = 'function(o){ return o.y; }';
            break;
      }

      return $funct;
   }


   /**
    * Get a tick formatter javascript function for a barchart Flotr2
    * @param int $id
    *      0 : value (default)
    * @return string, a tick formatter function
    */
   static function getTickFormatter($id = 0) {
      switch ($id) {
         default :
            $funct = 'function(value){ return value; }';
            break;
      }

      return $funct;
   }

   /**
    * Get ticks form an array of datas, useful for non-numeric labels
    * Warning : for stacked bar it doesn't work for the moment (TODO)
    * @param array $datas
    * @return an array of ticks as wanted by Flotr2
    */
   static function getTicksFromLabels($datas) {
      $cumul = [];
      $count = 0;
      $stacked = false;
      if (!empty($datas)) {
         //each data must be as X => Y
         foreach ($datas as $key => $data) {
            if (is_array($data)) {
               $stacked = true;
               break;
            }
            //If X is numeric then its corresponding key is itself
            if (is_numeric($key)) {
               $cumul[] = [$key, $key];
            } else {   //If X is not numeric then its corresponding key is count (numeric)
               $cumul[] = [$count, $key];
            }
            $count++;
         }
         if ($stacked) {
            $values = array_values($datas);
            $cumul = self::getTicksFromLabels($values[0]);
         }
      }
      //Finally $cumul looks like [[0,"label1"],[1,"label2"],...]
      return $cumul;
   }

   static function launchMultipleAxisAndGroupableBar($graph_datas = [], $graph_criterias = []) {
      global $CFG_GLPI;

      $onclick = 0;
      if (count($graph_criterias) > 0) {
         $onclick = 1;
      }

      $name            = $graph_datas['name'];
      $datas           = $graph_datas['data'];
      $labels          = $graph_datas['labels'];
      $max             = isset($graph_datas['max']) ? "max:".$graph_datas['max'] . ',' : "";

      $graph = "<script type='text/javascript'>
            
            var dataBar$name = {
              datasets: $datas,
              labels: $labels
            };
        
             var isChartRendered = false;
             var canvas$name = document.getElementById('$name');
             var ctx = canvas$name.getContext('2d');
             ctx.canvas.width = 700;
             ctx.canvas.height = 400;
             var $name = new Chart(ctx, {
               type: 'bar',
               data: dataBar$name,
               plugins: [{
                      beforeInit: function(ctx, options) {
                      ctx.legend.afterFit = function() {
                      this.height = this.height + 10;
                  };
                }
              },
             ],
               options: {
                 responsive: false,
                 scaleShowVerticalLines: false,
                 title:{
                     display:false,
                     text:'$name'
                 },
                 tooltips: {
                     mode:'label',
                     enabled: true,
                 },
                 scales: {
                        xAxes: [{
                        stacked: true,
                        ticks: {
                        beginAtZero: true
                    }
                     }],
                     yAxes: [
                          {
                         id: 'left-y-axis',
                         type: 'linear',
                         position: 'right',
                         stacked : false,
                         ticks: {
                             beginAtZero: true
                         }
                        }, 
                        {
                         id: 'bar-y-axis',
                         type: 'linear',
                         position: 'left',
                         stacked: true, 
                         ticks: { 
                             beginAtZero: true
                         }
                      }],
                 },
                 animation: {
                  onComplete: function() {
                    var ctx = this.chart.ctx;
                   ctx.font = Chart.helpers.fontString(Chart.defaults.global.defaultFontSize, 'normal', Chart.defaults.global.defaultFontFamily);
                   ctx.fillStyle = '#595959';
                   ctx.textAlign = 'center';
                   ctx.textBaseline = 'bottom';
/*                   this.data.datasets.forEach(function (dataset) {
                       for (var i = 0; i < dataset.data.length; i++) {
                           if (dataset.type == 'bar') {
                           var model = dataset._meta[Object.keys(dataset._meta)[0]].data[i]._model;
                           ctx.fillText(dataset.data[i], model.x, model.y - 5);
                       }
                     }      
                   });*/
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
          </script>";

      return $graph;
   }

}
