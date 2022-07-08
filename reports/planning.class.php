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
 * This class extends GLPI class planning to add the functions to display a widget on Dashboard
 */
class PluginMydashboardPlanning extends CommonGLPI {

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

      $widgets = [];
      if (Session::haveRight(Planning::$rightname, Planning::READMY)) {
         $widgets = [
            PluginMydashboardMenu::$TICKET_TECHVIEW => [
               "planningwidget"    => ["title"   => __('Your planning'),
                                            "icon"    => "ti ti-calendar",
                                            "comment" => ""],
            ]
         ];
      }
      return $widgets;
   }

   /**
    * @param $widgetId
    *
    * @return Nothing
    */
   function getWidgetContentForItem($widgetId) {
      switch ($widgetId) {
         case "planningwidget":
            $who_group = "";
            $who       = 0;
            if (Session::haveRight(Planning::$rightname, Planning::READMY)) {
               $who = Session::getLoginUserID();
            }
            if (Session::haveRightsOr(Planning::$rightname, [Planning::READGROUP,
                                                             Planning::READALL])) {
               $who_group = "mine";
            }
            return self::showCentral($who, $who_group);
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
   static function showCentral($who, $who_group = "") {

      if (!Session::haveRight(Planning::$rightname, Planning::READMY)
          || ($who <= 0 && $who_group == "")
      ) {
         return false;
      }
      Html::requireJs('fullcalendar');
      Html::requireJs('planning');
      echo Html::css("/public/lib/fullcalendar.css");

      $widget = new PluginMydashboardHtml();
      $title  = __("Your planning");
      $widget->setWidgetTitle($title);

      $rand    = rand();
      $options = [
         'full_view'    => false,
         'default_view' => 'listFull',
         'header'       => false,
         'height'       => 'auto',
         'rand'         => $rand,
         'now'          => date("Y-m-d H:i:s"),
      ];
      $graph     = "<div id='planning$rand' class='flex-fill'></div>";
      $graph     .= "</div>";

      $graph     .= Html::scriptBlock("$(function() {
         GLPIPlanning.display(" . json_encode($options) . ");
         GLPIPlanning.planningFilters();
      });");

      $widget->toggleWidgetRefresh();
      $widget->setWidgetHtmlContent(
         $graph
      );

      return $widget;
   }
}
