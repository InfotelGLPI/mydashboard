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
class PluginMydashboardPlanning {

   // Should return the localized name of the type
   /**
    * @param int $nb
    *
    * @return translated
    */
   static function getTypeName($nb = 0) {

      return __('Dashboard', 'mydashboard');
   }

   /**
    * @return bool
    */
   static function canCreate() {
      return Session::haveRightsOr('plugin_mydashboard', [CREATE, UPDATE]);
   }

   /**
    * @return bool
    */
   static function canView() {
      return Session::haveRight('plugin_mydashboard', READ);
   }


   /**
    * @return array
    */
   function getWidgetsForItem() {

      if (Session::haveRight(Planning::$rightname, Planning::READMY)) {
         return [
            PluginMydashboardMenu::$MY_VIEW =>
               [
                  "planningwidget" => __('Your planning') . "&nbsp;<i class='fa fa-calendar'></i>",
               ]
         ];
      }
      return [];
   }

   /**
    * @param $widgetId
    *
    * @return Nothing
    */
   function getWidgetContentForItem($widgetId) {
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
    * @return \PluginMydashboardDatatable (display function)
    */
   static function showCentral($who) {
      global $CFG_GLPI;

      if (!Session::haveRight(Planning::$rightname, Planning::READMY)
          || ($who <= 0)
      ) {
         return false;
      }

      $when  = strftime("%Y-%m-%d");

      // Get begin and duration
      $date  = explode("-", $when);
      $time  = mktime(0, 0, 0, $date[1], $date[2], $date[0]);
      $begin = $time;
      $end   = $begin + 5*DAY_TIMESTAMP;
      $begin = date("Y-m-d H:i:s", $begin);
      $end   = date("Y-m-d H:i:s", $end);
      $params = ['who'       => $who,
                      'who_group' => 0,
                      'whogroup'  => 0,
                      'begin'     => $begin,
                      'end'       => $end];
      $interv = [];
      foreach ($CFG_GLPI['planning_types'] as $itemtype) {
         $interv = array_merge($interv, $itemtype::populatePlanning($params));
      }
      ksort($interv);

      $title    = "<a style=\"font-size:14px;\" href=\"" . $CFG_GLPI["root_doc"] . "/front/planning.php?uID=$who\">" . __('Your planning') . "</a>";
      $header = [__('Type'), __('Name'),__('Begin date'),__('End date'), __('Content')];
      $content= [];
      $i     = 0;
      if (count($interv) > 0) {
         foreach ($interv as $key => $val) {
            if ($val["begin"] < $begin) {
               $val["begin"] = $begin;
            }
            if ($val["end"] > $end) {
               $val["end"] = $end;
            }
            $item = new $val['itemtype'];
            $content[$i]["type"] = $item->getTypeName();
            $content[$i]["name"] = Html::resume_text($val['name'], 50);
            $content[$i]["begin"] = Html::convDateTime($val["begin"]);
            $content[$i]["end"] = Html::convDateTime($val["end"]);
            $content[$i]["text"] = Html::resume_text($val['text'], 100);
            $i++;
         }
      }

      $widget = new PluginMydashboardDatatable();
      $widget->setWidgetTitle($title);
      $widget->setWidgetId("planningwidget");
      $widget->setTabNames($header);
      $widget->setTabDatas($content);

      return $widget;
   }
}
