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
class PluginMydashboardPieChart extends PluginMydashboardChart
{


   /**
    * PluginMydashboardPieChart constructor.
    */
   function __construct()
   {
      parent::__construct();
      $this->setOption('grid', array('verticalLines' => false, 'horizontalLines' => false));
      $this->setOption('xaxis', array('showLabels' => false));
      $this->setOption('yaxis', array('showLabels' => false));
      $this->setOption('mouse', array('track' => true, 'trackFormatter' => self::getTrackFormatter()));
      $this->setOption('legend', array('position' => 'ne', 'backgroundColor' => '#D2E8FF'));
      $this->setOption('pie', array('show' => true, 'explode' => 0,
         'fillOpacity' => PluginMydashboardColor::getOpacity()));
   }


   /**
    * @return a JSon formatted string that can be used to add a widget in a dashboard (with sDashboard)
    */
   function getJSonDatas()
   {
      $jsonDatasLabels = array();

      foreach ($this->getTabDatas() as $sliceLabel => $sliceValue) {
         if (is_array($sliceValue)) {
            $this->debugError(__("You can't have more than one serie for a pie chart", 'mydashboard'));
            break;
         }
         $jsonDatasLabels[] = array("data" => array(array(0, round($sliceValue, 2))), "label" => $sliceLabel);
      }

      return PluginMydashboardHelper::safeJsonData($jsonDatasLabels, $this->getOptions());
   }


   /**
    * Get a custom label format
    * @param int $id , the id of the format within {1,2,3,x}
    *      1: $prefix+<percentage>+% (+<value>+)+$suffix
    *      2: $prefix+<value>+$suffix
    *      3: empty
    *      x:(default) $prefix+<percentage>+%+$suffix
    * @param string $prefix , a custom $prefix
    * @param string $suffix , a custom $suffix
    * @param int $minvalue
    * @return string
    */
   static function getLabelFormatter($id = 0, $prefix = "", $suffix = "", $minvalue = 0)
   {
      $funct = "";
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


}