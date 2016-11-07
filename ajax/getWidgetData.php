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
if (isset($_POST['dashboard_plugin_classname']) && isset($_POST['dashboard_plugin_widget_index'])) {
   //We need the name of the class that contains the widget
   $classname = $_POST['dashboard_plugin_classname'];
   unset($_POST['dashboard_plugin_classname']);

   //We need the index of it's widget in the array of widgets
   $widgetIndex = $_POST['dashboard_plugin_widget_index'];
   unset($_POST['dashboard_plugin_widget_index']);

   //We may need the view number, especially in GLPI Core widgets
   if (isset($_POST['dashboard_plugin_widget_view'])) {
      $view = $_POST['dashboard_plugin_widget_view'];
      unset($_POST['dashboard_plugin_widget_view']);
   } else $view = NULL;

   //To create the widget classname may need some options
   $options = array();
   if (isset($_POST['options'])) $options = $_POST['options'];

   //This is the function name of the method that return the array of widgets
   $getWidgetContentForItem = "getWidgetContentForItem";
//    $getWidgets = "getWidgets";
   //Those three functions will be useful for gettin Datas,HtmlContent,Scripts of the widget
   $getJsonDatas = "getJsonDatas";
   $getHtmlContent = "getWidgetHtmlContent";
   $getWidgetScripts = "getWidgetScripts";

   //A bit of debug help
//    $infoDebug = "getWidgetData.php :\n";
//    $infoDebug .= "Class :".$classname."\nWidget :".$widgetIndex."\n";

   //We instanciate the widget container with options found
   $instance = new $classname($options);
   $infoDebug = "";
   //Now we have to get the widget, two ways :
   //Old : with method 'getWidgets' which gives an array of widgets, then get the desired widget in this array
   //New : with method 'getWidgetContentForItem' which gives only the wanted widget widgetIndex
   if (method_exists($instance, $getWidgetContentForItem)) {
      $widget = $instance->getWidgetContentForItem($widgetIndex);
   } else //We display a warning because it's 'deprecated' but for GLPI Core class getwidgets is still used (TODO)
   {
      $infoDebug .= "Warning : no method $getWidgetContentForItem found for the widget $widgetIndex in class " . $classname . "\n";
//        $infoDebug .= "Trying with the method $getWidgets\n";
   }

   //Compatibility (for GLPI Core classes)
   //For container classes which haven't a 'getWidgetContentForItem'
//    if(!isset($widget) && method_exists($instance,"getWidgets"))
//    {
////                We get its widgets
////STA: Idea 1.0.1 to not instanciate all widgets of the class
//        if(isset($view)) //It's a GLPI core widget
//        {
//            $widgets = call_user_func_array( array( $instance, $getWidgets ),array($view,$widgetIndex));
//            $widgets = $widgets[$view];
//        }
//        else //It's a tiers plugin
//        {
//            //We indicates which widget must be instanctiated, (we don't need others)
//            $widgets = call_user_func_array( array( $instance, $getWidgets ),array($widgetIndex));
//        }
////END : Idea 1.0.1
////        $widgets = call_user_func( array( $instance, $getWidgets ),$view);
////        if(isset($view)) 
////        {
////            $widgets = $widgets[$view];
////        }
////        
//        $widget = NULL;
//        //We find the widget we need by its id
//        foreach($widgets as $key => $w)
//        {
//            if(method_exists($w,"getWidgetId") && $w->getWidgetId() == $widgetIndex) $widget = $w;
//        }
//        $widget = find_widget_in_widgets_array($widgets, $widgetIndex);
//    }
//    else
//    {
//        if(!isset($widget))
//        {
//            $infoDebug .= "Warning : no method $getWidgets found for the widget $widgetIndex in class ".$classname."\n";
//        }
//    }


   //We check if it's a widget Object
   if (method_exists($widget, $getJsonDatas)) {
      //We assume that if getJsonData is callable on $widget then it's something from a known widget type
      //A bit of debug
//        $infoDebug .= "WidgetType :".$widget->getType()."\n";
//        $infoDebug .= "WidgetOptions :".json_encode($options)."\n";
//        $infoDebug .= "Success\n";
      $widget->setWidgetId($widgetIndex);
      //We first get its JSON data
      $data = call_user_func(array($widget, $getJsonDatas));

      //Then its Html content
      $htmlContent = "";
      $htmlContent = call_user_func(array($widget, $getHtmlContent));

      //Then its scripts (non evaluated, have to be evaluated client-side)
      $scripts = call_user_func(array($widget, $getWidgetScripts));
      //getWidgetScripts gives an array of script lines, we implode this array to have only one string
      $scripts = implode($scripts, "");
      //Then we send what we found
      //$data is alreaddy json_encoded, no need to re encode it
      $json = json_encode(
         array(
            "data" => "%widgetdata%",
            "html" => $htmlContent,
            "scripts" => $scripts,
//                 "_glpi_csrf_token" => Session::getNewCSRFToken()
         )
      );

      echo str_replace('"%widgetdata%"', $data, $json);
      //$infoDebug = "";
   } else {
      if (isset($widget)) $infoDebug .= "Failure : no method $getJsonDatas found for the widget $widgetIndex of class " . $widget->getType() . "\n";
      else $infoDebug .= "Failure : no method $getJsonDatas found for the widget $widgetIndex in class " . $classname . "\n";
   }


   //All infoDebug are displayed only in GLPI debug_mode
   if (!empty($infoDebug) && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
      Toolbox::logDebug($infoDebug);
   }
} else {
   echo "Sorry. You can't access directly to this file";
   Toolbox::logDebug($_SERVER['REMOTE_ADDR'] . " tried to access getWidgetData.php directly, it isn't normal. No one should directly access this file.");
}

/**
 * @param $haystack
 * @param $needle
 * @return mixed
 */
function find_widget_in_widgets_array($haystack, $needle)
{
   foreach ($haystack as $key => $w) {
      if (is_array($w)) return find_widget_in_widgets_array($w, $needle);
      if (method_exists($w, "getWidgetId") && $w->getWidgetId() == $needle) return $w;
   }
}