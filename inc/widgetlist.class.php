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
 * Class PluginMydashboardWidgetlist
 */
class PluginMydashboardWidgetlist {

   /**
    * Get the list of widget as an array :
    * Array(
    *  'plugin1' => Array(
    *                  'classname1' => Array(
    *                                      'widget1' => 'Widget Title 1'
    *                                      ...
    *                                      'Category level 1' => Array(
    *                                                              'widgetX' => 'Widget Title X'
    *                                                              ...
    *                                                            )
    *                                  )
    *                  ...
    *                  'classnameN' => ...
    *               )
    *  ...
    *  'pluginN' => ...
    * )
    * By default it return a filtered array, filtered in two levels, profile and user preference.
    *
    * @param boolean $filtered default true, if set to false all widgets found will be in the list
    *
    * @param int     $active_profile
    * @param string  $profile_interface
    *
    * @return mixed array
    */
   public function getList($filtered = true, $active_profile = -1, $profile_interface = "central", $preload = false) {
      global $PLUGIN_HOOKS;
      $widgets = [];

      //        We get hooked plugin widgets
      if (isset($PLUGIN_HOOKS['mydashboard']) && $preload != 1) {
         $widgets = (isset($PLUGIN_HOOKS['mydashboard'])?$PLUGIN_HOOKS['mydashboard']:[]);
      }

      //We add classes from mydashboard
      $widgets['mydashboard'] = [];

      $autoloader = new PluginMydasboardAutoloader();
      $classes = $autoloader->listReports();
      foreach ($classes as $class) {
         $widgets['mydashboard'][] = $class;
      }

      //We add classes for GLPI core widgets
      $widgets['GLPI'] = [
                          "PluginMydashboardReminder",
                          "PluginMydashboardPlanning",
                          "PluginMydashboardEvent",
                          "PluginMydashboardProblem",
                          "PluginMydashboardChange",
                          "PluginMydashboardTicket",
                          "PluginMydashboardRSSFeed",
                          "PluginMydashboardProject",
                          "PluginMydashboardProjecttask",
                          "PluginMydashboardContract",
                          "PluginMydashboardKnowbaseItem"
      ];
      $dbu             = new DbUtils();

      //We run through the hook to get all widget IDs and Titles declared in all classes
      foreach ($widgets as $plugin => $pluginclasses) {
         $widgets[$plugin] = [];

         foreach ($pluginclasses as $pluginclass) {
            if (!class_exists($pluginclass)) {
               Toolbox::logWarning($pluginclass);
               continue;
            }
            $item = $dbu->getItemForItemtype($pluginclass);

            //            if ($item->canview) {
            $widgets[$plugin][$pluginclass] = [];
            //We try get the list of widgets for this class
            if ($item && is_callable([$item, 'getWidgetsForItem'])) {
               if (isset($item->interfaces)) {
                  if (is_array($item->interfaces) && in_array($profile_interface, $item->interfaces)) {
                     $widgets[$plugin][$pluginclass] = $item->getWidgetsForItem();
                  } else {
                     unset($widgets[$plugin]);
                  }
               } else if (!isset($item->interfaces)) {
                  $widgets[$plugin][$pluginclass] = $item->getWidgetsForItem();
               }
            }
            //            }
         }
      }

      if ($filtered) {
         //Plugin filtered by user (blacklist)
         //Blacklist
         //Used when user doesn't want to display widgets of a plugin
         $ublacklist           = new PluginMydashboardPreferenceUserBlacklist();
         $filters['blacklist'] = $ublacklist->getBlacklistForUser(Session::getLoginUserID());

         foreach ($widgets as $plugin => $widgetclasses) {
            if (isset($filters['blacklist'][$plugin])) {
               unset($widgets[$plugin]);
               continue;
            }
         }

         //Widget filtered by profile (authorized list)
         $pauthlist = new PluginMydashboardProfileAuthorizedWidget();
         $profile   = ($active_profile != -1) ? $active_profile : $_SESSION['glpiactiveprofile']['id'];

         if (($filters['authorized'] = $pauthlist->getAuthorizedListForProfile($profile)) !== false) {
            //getAuthorizedListForProfile() return false when the profile can see all the widgets

            //If nothing is authorized
            if (count($filters['authorized']) < 0) {
               $widgets = [];
            } else {
               foreach ($widgets as $plugin => &$widgetclasses) {
                  foreach ($widgetclasses as $widgetclass => &$widgetlist) {
                     $widgetlist = $this->cleanList($filters['authorized'], $widgetlist);
                  }
               }
            }
         }
      }

      return $widgets;
   }

   /**
    * Removes all $widgetlist members that are not in $authorized, recursively
    *
    * @param mixed $authorized , an array of authorized widgets IDs (names)
    * @param mixed $widgetlist , an array of widgets IDs or category
    *
    * @return array, widgetlist cleaned
    */
   private function cleanList($authorized, $widgetlist) {

      foreach ($widgetlist as $widgetId => $widgetTitle) {
         if (is_array($widgetTitle)) {
            $widgetlist[$widgetId] = $this->cleanList($authorized, $widgetTitle);
         } else {
            if (!isset($authorized[$widgetId])) {
               unset($widgetlist[$widgetId]);
            }
         }
      }
      return $widgetlist;
   }
}
