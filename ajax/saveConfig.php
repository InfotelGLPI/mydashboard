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

include('../../../inc/includes.php');


Session::checkLoginUser();

//Initialization
if (isset($_POST["id"])) $userId = $_POST["id"];
if (isset($_POST["interface"])) $interface = $_POST["interface"];
if (isset($_POST["widgetId"])) $widgetName = $_POST["widgetId"];
if (isset($_POST["order"])) $order = $_POST["order"];
if (isset($_POST["sortedList"])) $sortedList = $_POST["sortedList"];

//I use validate rather than check because i want to handle errors 
//if(Session::validateCSRF($_POST)) {
$widget = new PluginMydashboardWidget();
$userwidget = new PluginMydashboardUserWidget($userId, $interface);

//We check if user has a personnal Dashboard in DB ( $canbeempty = true because we want the real dashboard )
$userdashboard = $userwidget->getWidgets(true);
//If he hasn't a personnal Dashboard, it means that he is working with the default Dashboard
if (empty($userdashboard)) {
   //We must had the default widgets on his dashboard (in DB) before the first modification
   //default dashboard widgets that must'nt be added to users dashboard are stored in a SESSION
   $userwidget->setDefault(/*$_SESSION['not_to_be_added']*/);
//        unset($_SESSION['not_to_be_added']);
}

if (isset($userId) && isset($widgetName) && isset($order)) {
   //Checking existence of widgetId in `glpi_plugin_mydashboard_widgets`
   $widgetId = $widget->getWidgetIdByName($widgetName);

   //If it doesnt exist, we add it
   if (!isset($widgetId)) {
      if ($widget->saveWidget($widgetName)) {
         //Here we have an entry coresponding to the widget, no we want its id
         $widgetId = $widget->getWidgetIdByName($widgetName);
      } else {
         //If an error occured
         finnish(true, __("Can't add this widget in Database", "mydashboard"));
      }
   }
   //We never know when it's a new widget on the mydashboard user, we have to check it and insert or update if needed
   if ($userwidget->saveWidgetIdPlace($widgetId, $order)) {
      finnish(false, "(" . __("Added", "mydashboard")/*." : '".$widgetName."'"*/ . ")");
   } else {
      finnish(true, __("Can't add this widget on users Dashboard", "mydashboard"));
   }
} else {
   //Deletion case
   if (isset($userId) && isset($widgetName)) {
      if ($userwidget->removeWidgetByWidgetName($widgetName)) {

         finnish(false, "(" . __("Deleted", "mydashboard")/*." : '".$widgetName."'"*/ . ")");
      }
   } else {
      //Updating order case
      if (isset($userId) && isset($sortedList)) {
         $result = true;
         foreach ($sortedList as $order => $widgetname) {
            //We get the widget id
            $widgetId = $widget->getWidgetIdByName($widgetname);
            if (!isset($widgetId)) {
               //If this widgetname don't exist, there may be something weird here
               break;
            }
            $result = $result && $userwidget->saveWidgetIdPlace($widgetId, $order);
         }
         if ($result) {
            finnish(false, "(" . __("Updated", "mydashboard") . ")");
         } else {
            finnish(true, __("Can't save new order", "mydashboard"));
         }
      } else { //any other case
         echo "Sorry. You can't access directly to this file";
      }
   }
}
//}
//else //When CSRF is not valid 
//{
//    finnish(true);
//}

/**
 * @param bool $error
 * @param string $msg
 */
function finnish($error = false, $msg = "")
{
   //Two statuses are possible, whether there is an error or not
   $response_array['status'] = $error ? __("Dashboard not saved", "mydashboard") : __("Dashboard saved", "mydashboard");
   $response_array['message'] = $msg;

   //TODO Maybe not necessary to create a new one every updates of the mydashboard
//    $response_array['_glpi_csrf_token'] = Session::getNewCSRFToken();
//    Session::cleanCSRFTokens();
   header('Content-type: application/json');
   echo json_encode($response_array);
}