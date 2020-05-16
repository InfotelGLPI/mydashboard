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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * This class extends GLPI class event to add the functions to display widgets on Dashboard
 */
class PluginMydashboardEvent extends Glpi\Event {

   /**
    * @param int $nb
    * @return translated
    */
   static function getTypeName($nb = 0) {
      return _n('Log', 'Logs', $nb);
   }

   /**
    * PluginMydashboardEvent constructor.
    * @param array $options
    */
   function __construct($options = []) {
      parent::__construct();
   }


   /**
    * @return array
    */
   function getWidgetsForItem() {
      $array = [];
      if (Session::haveRight("logs", READ)) {
         $array = [
         //            PluginMydashboardMenu::$MY_VIEW =>
         //               array(
         //                  "eventwidgetpersonnal" => sprintf(__('Last %d events'), $_SESSION['glpilist_limit'])
         //               ),
            PluginMydashboardMenu::$GLOBAL_VIEW =>
               [
                  "eventwidgetglobal" => sprintf(__('Last %d events'), $_SESSION['glpilist_limit']) . "&nbsp;<i class='fas fa-table'></i>"
               ]
         ];
      }
      return $array;
   }

   /**
    * @param $widgetId
    * @return PluginMydashboardDatatable|void
    */
   function getWidgetContentForItem($widgetId) {
      if (Session::haveRight("logs", READ)) {
         switch ($widgetId) {
            case "eventwidgetpersonnal":
               return PluginMydashboardEvent::showForUser($_SESSION['glpiname']);
               break;
            case "eventwidgetglobal":
               return PluginMydashboardEvent::showForUser();
               break;
         }
      }
   }

   /**
    * @param $type
    * @param $items_id
    *
    * @return string|void
    */
   static function displayItemLogID($type, $items_id) {
      global $CFG_GLPI;
      $out = "";
      if (($items_id == "-1") || ($items_id == "0")) {
         $out .= "&nbsp;";//$item;
      } else {
         switch ($type) {
            case "rules" :
               $out .= "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/rule.generic.form.php?id=" .
                  $items_id . "\">" . $items_id . "</a>";
               break;

            case "infocom" :
               $out .= "<a href='#' onClick=\"window.open('" . $CFG_GLPI["root_doc"] .
                  "/front/infocom.form.php?id=" . $items_id . "','infocoms','location=infocoms,width=" .
                  "1000,height=400,scrollbars=no')\">" . $items_id . "</a>";
               break;

            case "devices" :
               $out .= $items_id;
               break;

            case "reservationitem" :
               $out .= "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/reservation.php?reservationitems_id=" .
                  $items_id . "\">" . $items_id . "</a>";
               break;

            default :
               $type = getSingular($type);
               $url = '';
               if ($item = getItemForItemtype($type)) {
                  $url = $item->getFormURL();
               }
               if (!empty($url)) {
                  $out .= "<a href=\"" . $url . "?id=" . $items_id . "\">" . $items_id . "</a>";
               } else {
                  $out .= $items_id;
               }
               break;
         }
      }
      return $out;
   }


   /**
    * Print a nice tab for last event from inventory section
    *
    * Print a great tab to present lasts events occured on glpi
    *
    * @param $user   string  name user to search on message (default '')
    *
    * @return PluginMydashboardDatatable|void
    */
   static function showForUser($user = "") {
      global $DB, $CFG_GLPI;

      // Show events from $result in table form
      list($logItemtype, $logService) = self::logArray();

      // define default sorting
      $usersearch = "";
      if (!empty($user)) {
         $usersearch = $user . " ";
      }

      // Query Database
      $query = "SELECT *
                FROM `glpi_events`
                WHERE `message` LIKE '" . $usersearch . "%'
                ORDER BY `date` DESC
                LIMIT 0," . intval($_SESSION['glpilist_limit']);

      // Get results
      $result = $DB->query($query);

      // Number of results
      $number = $DB->numrows($result);
      // No Events in database
      if ($number < 1) {
         $output['title'] = "<br><div class='spaced'><table class='tab_cadrehov'>";
         $output['title'] .= "<tr><th>" . __('No Event') . "</th></tr>";
         $output['title'] .= "</table></div>";
      }

      // Output events
      $i = 0;

      //TRANS: %d is the number of item to display
      $output['title'] = "<a style=\"font-size:14px;\" href=\"" . $CFG_GLPI["root_doc"] . "/front/event.php\">" .
         sprintf(__('Last %d events'), $_SESSION['glpilist_limit']) . "</a>";

      $output['header'][] = __('Source');
      $output['header'][] = __('id');
      $output['header'][] = __('Date');
      $output['header'][] = __('Service');
      $output['header'][] = __('Message');

      $output['body'] = [];

      while ($i < $number) {
         $DB->result($result, $i, "id");
         $items_id = $DB->result($result, $i, "items_id");
         $type = $DB->result($result, $i, "type");
         $date = $DB->result($result, $i, "date");
         $service = $DB->result($result, $i, "service");
         $message = $DB->result($result, $i, "message");

         $itemtype = "&nbsp;";
         if (isset($logItemtype[$type])) {
            $itemtype = $logItemtype[$type];
         } else {
            $type = getSingular($type);
            if ($item = getItemForItemtype($type)) {
               $itemtype = $item->getTypeName(1);
            }
         }

         $output['body'][$i][0] = $itemtype;
         $output['body'][$i][1] = self::displayItemLogID($type, $items_id);
         $output['body'][$i][2] = Html::convDateTime($date);
         $output['body'][$i][3] = $logService[$service];
         $output['body'][$i][4] = $message;

         $i++;
      }
      if (!empty($output)) {
         $personnal = ($user == "") ? "global" : "personnal";
         $widget = new PluginMydashboardDatatable();
         $widget->setWidgetTitle($output['title']);
         $widget->setWidgetId("eventwidget" . $personnal);
         //We set the datas of the widget (which will be later automatically formatted by the method getJSonData of PluginMydashboardDatatable)
         $widget->setTabNames($output['header']);
         $widget->setTabDatas($output['body']);
         //Here we set few otions concerning the jquery library Datatable, bPaginate for paginating ...
         $widget->setOption("bPaginate", false);
         $widget->setOption("bFilter", false);
         $widget->setOption("bInfo", false);
         if (count($output['body']) > 0){
            $widget->setOption("bSort", false);
         }


         $widget->toggleWidgetRefresh();
         return $widget;
      }
   }


}
