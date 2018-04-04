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
 * This widget class is meant to display data as a linechart
 */
class PluginMydashboardLineChart extends PluginMydashboardChart
{

//    private $options;
   //   private $tabDatas;

   /**
    * PluginMydashboardLineChart constructor.
    */
   function __construct()
   {
      parent::__construct();
      $this->setOption('grid', array('verticalLines' => true, 'horizontalLines' => true));
      $this->setOption('xaxis', array('showLabels' => true));
      $this->setOption('yaxis', array('showLabels' => true));
      $this->setOption('mouse', array('track' => true, 'relative' => true));
      $this->setOption('legend', array('position' => 'se', 'backgroundColor' => '#D2E8FF'));
      $this->setOption('lines', array('show' => true, 'fillOpacity' => PluginMydashboardColor::getOpacity()));
   }

   /**
    * Get an array formatted for Flotr2 linechart
    * @param array $dataArray looks like [x1=>y1,x2=>y2, ...]
    * @return array coordinates looks like [[x1,y1],[x2,y2], ...]
    */
   private function getData($dataArray)
   {
      $data = array();
      foreach ($dataArray as $dataX => $dataY) {
         $data[] = array($dataX, $dataY);
      }
      return $data;
   }

   /**
    * Get data formatted in (pseudo) JSon representing the linechart and options for Flotr2
    * @return string A (pseudo) JSon formatted string for Flotr2
    */
   function getJSonDatas()
   {
      $data = array();
      $alone = false;
      foreach ($this->getTabDatas() as $legend => $dataY) {
         if (is_array($dataY)) {
            $data[] = array('data' => $this->getData($dataY), 'label' => $legend);
         } else {
            $alone = true;
            $break;
         }

      }

      if ($alone) $data[] = array('data' => $this->getData($this->getTabDatas()));

      return PluginMydashboardHelper::safeJsonData($data, $this->getOptions());
   }

   /**
    * Get a specific TickFormatter function for Flotr2
    * @param int $id ,
    *      (default) : <value>
    * @return string, A JS function
    */
   static function getTickFormatter($id = 0)
   {
      $funct = "";
      switch ($id) {
         default :
            $funct = 'function(value){ console.log(value); return value; }';
            break;
      }

      return $funct;
   }

//    /**
//     * 
//     * @param type $datas
//     * @return type
//     */
//    static function getTicksFromLabels($datas)
//    {
//        $cumul;
//        $count = 0;
//        foreach($datas as $key => $data)
//        {
//            if(is_numeric($key))
//            {
//                $cumul[] = [$key,$key];
//            }
//            else
//            {
//                $cumul[] = [$count,$key];
//            }
//            $count++;
//        }
//        Toolbox::logDebug($cumul);
//        return $cumul;
//    }

}