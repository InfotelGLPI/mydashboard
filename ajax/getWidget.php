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

include("../../../inc/includes.php");
header('Content-type: text/plain');
Session::checkLoginUser();

if (/*Session::validateCSRF($_POST) && */
   isset($_POST['dashboard_plugin_classname']) && isset($_POST['dashboard_plugin_widget_index'])
) {
   $classname = $_POST['dashboard_plugin_classname'];
   $classobject = getItemForItemtype($classname);
   if ($classobject && method_exists($classobject, "getWidgetContentForItem")) {
      $widget = $classobject->getWidgetContentForItem($_POST['dashboard_plugin_widget_index']);
      if (isset($widget) && ($widget instanceof PluginMydashboardModule)) {
         $widget->setWidgetId($_POST['dashboard_plugin_widget_index']);
         //Then its Html content
         $htmlContent = "";
         $htmlContent = $widget->getWidgetHtmlContent();

         if ($widget->getWidgetIsOnlyHTML()) $htmlContent = "";

         //when we get jsondata some checkings and modification can be done by the widget class
         //For example Datatable add some scripts to adapt the table to the template
         $jsondata = $widget->getJSonDatas();

         //Then its scripts (non evaluated, have to be evaluated client-side)
         $scripts = $widget->getWidgetScripts();
         $scripts = implode($scripts, "");

//            $jsondatas = $widget->getJSonDatas();
//            $widgetContent = json_decode($jsondatas);
//            if(!isset($widgetContent)) $widgetContent = $jsondatas;
         //We prepare a "JSon object" compatible with sDashboard
         $json =
            array(
               "widgetTitle" => $widget->getWidgetTitle(),
               "widgetId" => $widget->getWidgetId(),
               "widgetType" => $widget->getWidgetType(),
               "widgetContent" => "%widgetContent%",
               "enableRefresh" => json_decode($widget->getWidgetEnableRefresh()),
               "refreshCallBack" => "function(){return mydashboard.getWidgetData('" . PluginMydashboardMenu::DASHBOARD_NAME . "','$classname', '" . $widget->getWidgetId() . "');}",
               "html" => $htmlContent,
               "scripts" => $scripts,
//                        "_glpi_csrf_token" => Session::getNewCSRFToken()
         );
         //safeJson because refreshCallBack must be a javascript function not a string,
         // not a string, but a function in a json object is not valid
         $json = PluginMydashboardHelper::safeJson($json);

         //getJSonDatas already gives a json_encoded string that's why we put it after the global encoding
         echo str_replace('"%widgetContent%"', $jsondata, $json);
      }
   }
}
