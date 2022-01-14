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
 * Every chart classes of the mydashboard plugin inherit from this class
 * It sets basical parameters to display a chart with Flotr2
 */
abstract class PluginMydashboardChart extends PluginMydashboardModule
{

   protected $tabDatas;
   private $tabDatasSet;
   private $options = [];

   /**
    * PluginMydashboardChart constructor.
    */
   function __construct() {
      $this->initOptions();
      $this->setWidgetType("chart");
      $this->setOption('colors', PluginMydashboardColor::getColors());
      $this->tabDatas = [];
      $this->tabDatasSet = false;
   }

   /**
    * This method is here to init options of every chart (pie, bar ...)
    */
   function initOptions() {
      $this->options['HtmlText'] = false;
   }

   /**
    *
    * @return array array of all options
    */
   function getOptions() {
      return $this->options;
   }

   /**
    * @param $optionName
    * @return mixed|string
    */
   function getOption($optionName) {
      return (isset($this->options[$optionName])) ? $this->options[$optionName] : '';
   }

   /**
    * @param $optionName
    * @param $optionValue
    * @param bool $force
    * @return bool
    */
   function setOption($optionName, $optionValue, $force = false) {
      if (isset($this->options[$optionName]) && !$force) {
         if (is_array($optionValue)) {
            $this->options[$optionName] = array_merge($this->options[$optionName], $optionValue);
            return true;
         }
      }
      $this->options[$optionName] = $optionValue;
      return true;
   }

   /**
    * @return an array representing the horizontal bar chart
    */
   function getTabDatas() {
      if (empty($this->tabDatas) && !$this->tabDatasSet) {
         $this->debugWarning(__("No data is given to the widget", 'mydashboard'));
      }
      return $this->tabDatas;
   }

   /**
    * This method is used to set an array of value representing the horizontal bar chart
    * @param array $_tabDatas
    * $_tabDatas must be formatted as :
    *  Array(
    *      label1 => value1,
    *      label2 => value2
    *  )
    * Example : array("2012" => 10, "2013" => 14,"2014" => 25)
    */
   function setTabDatas($_tabDatas) {
      if (empty($_tabDatas)) {
         $this->debugNotice(__("No data available", 'mydashboard'));
      }
      $this->tabDatasSet = true;
      if (is_array($_tabDatas)) {
         $this->tabDatas = $_tabDatas;
      } else {
         $this->debugError(__("Not an array", 'mydashboard'));
      }
   }

   /**
    * set the opacity of the chart, from 0 (hidden) to 1 (completly visible)
    * Note : this is just a helper, you can use setOption to do the same, it will only be longer to write
    * @param int $_opacity in [0,1]
    */
   function setFillOpacity($_opacity) {
      //a dictionnary associating Dashboard class to Flotr2 types
      $dic = [
         "PluginMydashboardHBarChart" => "bars",
         "PluginMydashboardVBarChart" => "bars",
         "PluginMydashboardPieChart" => "pie",
         "PluginMydashboardLineChart" => "line"
      ];
      //Opacity is only set when reasonable
      if (is_numeric($_opacity) && $_opacity >= 0 && $_opacity <= 1) {
         $classname = get_class($this);
         if (isset($dic[$classname])) {
            //We set the option,
            $this->setOption($dic[$classname], ['fillOpacity' => $_opacity]);
         }
      }
   }

   /**
    * Get a custom track formatter (when mouse overs a plot)
    * @param int $id , the id of the trackformatter within :
    *      1: <x_value>
    *      2: <y_value>
    *      3: <label>
    *      4: (<x_value>,<y_value>)
    *      5: <label> (<x_value>)
    *      x:(default) <label> (<y_value>)
    * @return string
    */
   static function getTrackFormatter($id = 0) {
      switch ($id) {
         case 1 : //Display track as just its x axis value
            $funct = 'function(obj){ return obj.series.data[0][0];  }';
            break;
         case 2 : //Display track as just its y axis value
            $funct = 'function(obj){ return obj.series.data[0][1];  }';
            break;
         case 3 : //Display label on track
            $funct = 'function(obj){ return obj.series.label;}';
            break;
         case 4 : //Display (x,y)
            $funct = 'function(obj){ return ("+obj.series.data[0][0]+","+obj.series.data[0][1]+") ";}';
            break;
         case 5 : //Display label (x)
            $funct = 'function(obj){ return obj.series.label+" ("+obj.series.data[0][0]+") ";}';
            break;
         default ://Display label (y)
            $funct = 'function(obj){ return obj.series.label+" ("+obj.series.data[0][1]+") ";}';
            break;
      }

      return $funct;
   }
}
