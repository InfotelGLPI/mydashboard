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
abstract class PluginMydashboardBarChart extends PluginMydashboardChart
{

   //private $tabDatas;
   private $orientation = "v";

   /**
    * PluginMydashboardBarChart constructor.
    */
   function __construct()
   {
      parent::__construct();
      $this->setOption('grid', array('verticalLines' => true, 'horizontalLines' => true));
      $this->setOption('xaxis', array('showLabels' => true));
      $this->setOption('yaxis', array('showLabels' => true));
      $this->setOption('mouse', array('track' => true, 'relative' => true));
      $this->setOption('legend', array('position' => 'se', 'backgroundColor' => '#D2E8FF'));
      $this->setOption('bars', array('show' => true));
      $this->setOption('bars', array('fillOpacity' => PluginMydashboardColor::getOpacity()));
   }


   /**
    * Sets the orientation of the barchart
    * @param string $_o , orientation
    *        $_o is only accepted within "h" or "v"
    */
   function setOrientation($_o)
   {
      $possibleOrientations = array("h", "v");
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
   private function getCouple($dataValue, $dataLabel)
   {
      switch ($this->orientation) {
         case "h" :
            return array($dataValue * 1, $dataLabel);
         case "v" :
            return array($dataLabel, $dataValue * 1);
      }
   }

   /**
    * If there is more than one serie the format of the data for Flotr2 isn't the same
    * This method return a formatted array for few series
    * @return array
    */
   private function getStackedFormat()
   {
      //If we need stacked format we can guess that we want a stacked barchart
      $options = $this->getOptions();
      if (!isset($options['bar']['stacked'])) $this->setOption('bars', array('stacked' => true));
      $jsonDatasLabels = array();

      //every item of tabDatas should be a serie for the stacked bar chart, it's labelled by its key
      foreach ($this->getTabDatas() as $serieLabel => $serie) {
         $data = array();
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
         if (is_numeric($serieLabel)) $serieLabel = __("Serie") . " " . $serieLabel;

         //We ad this serie to the widget data
         $jsonDatasLabels[] = array("data" => $data, "label" => $serieLabel);
      }
      return $jsonDatasLabels;
   }

   /**
    * Get a json formatted string representing data and options for the barchart Flotr2
    * @return string
    */
   function getJSonDatas()
   {
      $stacked = false;
      $data = "";
      $count = 0;
      $jsonDatasLabels = array();

      foreach ($this->getTabDatas() as $dataLabel => $dataValue) {
         //If dataValue is an array it means there are few Y-values for one X-values, by default we understand it as a stacked bar chart
         if (is_array($dataValue)) {
            $stacked = true;
            break;
         } else {
            //We have to check if dataLabel is numeric because to place a point on a chart it must have numerical coordinate
            if (is_numeric($dataLabel)) {
               $jsonDatasLabels[] = array("data" => array($this->getCouple($dataValue, $dataLabel)), "label" => $dataLabel);
            } else {   //If it's not numeric we have a counter to place values on every 1 step
               $jsonDatasLabels[] = array("data" => array($this->getCouple($dataValue, $count)), "label" => $dataLabel);
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
   static function getLabelFormatter($id = 0)
   {
      $funct = "";
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
   static function getTickFormatter($id = 0)
   {
      $funct = "";
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
   static function getTicksFromLabels($datas)
   {
      $cumul = array();
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
               $cumul[] = array($key, $key);
            } else {   //If X is not numeric then its corresponding key is count (numeric)
               $cumul[] = array($count, $key);
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

}