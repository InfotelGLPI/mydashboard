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
 * This class extends GLPI class planning to add the functions to display a widget on Dashboard
 */
class PluginMydashboardPlanning
{

   // Should return the localized name of the type
   /**
    * @param int $nb
    * @return translated
    */
   static function getTypeName($nb = 0)
   {

      return __('Dashboard', 'mydashboard');
   }

   /**
    * @return bool
    */
   static function canCreate()
   {
      return Session::haveRightsOr('plugin_mydashboard', array(CREATE, UPDATE));
   }

   /**
    * @return bool
    */
   static function canView()
   {
      return Session::haveRight('plugin_mydashboard', READ);
   }


   /**
    * @return array
    */
   function getWidgetsForItem()
   {

      if (Session::haveRight(Planning::$rightname, Planning::READMY)) {
         return array(
            PluginMydashboardMenu::$MY_VIEW =>
               array(
                  "planningwidget" => __('Your planning')
               )
         );
      }
      return array();
   }

   /**
    * @param $widgetId
    * @return Nothing
    */
   function getWidgetContentForItem($widgetId)
   {
      switch ($widgetId) {
         case "planningwidget":
            if (Session::haveRight(Planning::$rightname, Planning::READMY)) {
               return PluginMydashboardPlanning::showCentral(Session::getLoginUserID());
            }
            break;
      }
   }

   /**
    * Show the planning for the central page of a user
    *
    * @param $who ID of the user
    *
    * @return Nothing (display function)
    **/
   static function showCentral($who)
   {
      global $CFG_GLPI;

      if (!Session::haveRight(Planning::$rightname, Planning::READMY)
         || ($who <= 0)
      ) {
         return false;
      }

      $output = array();

      $when = strftime("%Y-%m-%d");
      $debut = $when;

      // Get begin and duration
      $date = explode("-", $when);
      $time = mktime(0, 0, 0, $date[1], $date[2], $date[0]);
      $begin = $time;
      $end = $begin + DAY_TIMESTAMP;
      $begin = date("Y-m-d H:i:s", $begin);
      $end = date("Y-m-d H:i:s", $end);

      $params = array('who' => $who,
         'who_group' => 0,
         'whogroup' => 0,
         'begin' => $begin,
         'end' => $end);
      $interv = array();
      foreach ($CFG_GLPI['planning_types'] as $itemtype) {
         $interv = array_merge($interv, $itemtype::populatePlanning($params));
      }
      ksort($interv);

      $output['title'] = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/planning.php?uID=$who\">" . __('Your planning') . "</a>";
      $output['header'][] = '';
      if (count($interv) > 0) {
         foreach ($interv as $key => $val) {
            if ($val["begin"] < $begin) {
               $val["begin"] = $begin;
            }
            if ($val["end"] > $end) {
               $val["end"] = $end;
            }
            $output['body'][] = self::displayPlanningItem($val, $who, 'in');
         }
      }

      $output['name'] = "planningwidget";

      if (!empty($output)) {
         $widget = new PluginMydashboardDatatable();
         $widget->setWidgetTitle($output['title']);
         $widget->setWidgetId("planningwidget");
         //We set the datas of the widget (which will be later automatically formatted by the method getJSonData of PluginMydashboardDatatable)
         $widget->setTabNames($output['header']);
         if (isset($output['body'])) $widget->setTabDatas($output['body']);
         else $widget->setTabDatas(array());
         //Here we set few otions concerning the jquery library Datatable, bPaginate for paginating ...
         $widget->setOption("bPaginate", false);
         $widget->setOption("bFilter", false);
         $widget->setOption("bInfo", false);

         return $widget;
      }

      return $output;
   }

   /**
    * Display a Planning Item
    *
    * @param $val       Array of the item to display
    * @param $who             ID of the user (0 if all)
    * @param position|string $type position of the item in the time block (in, through, begin or end)
    *                         (default '')
    * @param complete|int $complete complete display (more details) (default 0)
    * @return Nothing
    */
   static function displayPlanningItem(array $val, $who, $type = "", $complete = 0)
   {

      $colnum = 0;
      $output = array();

      $color = "#e4e4e4";
      if (isset($val["state"])) {
         switch ($val["state"]) {
            case 0 :
               $color = "#efefe7"; // Information
               break;

            case 1 :
               $color = "#fbfbfb"; // To be done
               break;

            case 2 :
               $color = "#e7e7e2"; // Done
               break;
         }
      }
      $output[$colnum] = "<div style=' margin:auto; text-align:left; border:1px dashed #cccccc;
             background-color: $color; font-size:9px; width:98%;'>";


      // Plugins case
      if (isset($val['itemtype'])
         && !empty($val['itemtype'])
      ) {
         ob_start();
         echo $val['itemtype']::displayPlanningItem($val, $who, $type, $complete);
         $output[$colnum] .= ob_get_contents();
         ob_end_clean();
      }

      $output[$colnum] .= "</div>";

      return $output;
   }

}