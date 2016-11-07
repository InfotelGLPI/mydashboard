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
 * This helper class provides some static functions that are useful for widget class
 */
class PluginMydashboardHelper
{

   /**
    * get the delay between two automatic refreshing
    * @return int
    */
   static function getAutomaticRefreshDelay()
   {
      return self::getPreferenceField('automatic_refresh_delay');
   }

   /**
    * Check if automatic refreshing is enable or not
    * @return boolean, TRUE if automatic refresh is enabled, FALSE otherwise
    */
   static function getAutomaticRefresh()
   {
      return (self::getPreferenceField('automatic_refresh') == 1) ? true : false;
   }

   /**
    * Get the number of widgets in width in the configuration
    * @return int
    */
   static function getNumberOfWidgetsInWidth()
   {

      return self::getPreferenceField('nb_widgets_width');
   }

   /**
    * Check if user wants dashboard to replace central interface
    * @return boolean, TRUE if dashboard must replace, FALSE otherwise
    */
   static function getReplaceCentral()
   {
      return self::getPreferenceField("replace_central");
   }

   /**
    * @return mixed
    */
   static function getDisplayPlugins()
   {
      return self::getConfigField("display_plugin_widget");
   }

   /**
    * @return mixed
    */
   static function getDisplayMenu()
   {
      return self::getConfigField("display_menu");
   }

   /**
    * Get a specific field of the config
    * @param string $fieldname
    * @return mixed
    */
   private static function getConfigField($fieldname)
   {
      $config = new PluginMydashboardConfig();
      if (!$config->getFromDB(Session::getLoginUserID())) $config->initConfig();
      $config->getFromDB("1");

      return (isset($config->fields[$fieldname])) ? $config->fields[$fieldname] : 0;
   }

   /**
    * Get a specific field of the config
    * @param string $fieldname
    * @return mixed
    */
   private static function getPreferenceField($fieldname)
   {
      $preference = new PluginMydashboardPreference();
      if (!$preference->getFromDB(Session::getLoginUserID())) $preference->initPreferences(Session::getLoginUserID());
      $preference->getFromDB(Session::getLoginUserID());

      return (isset($preference->fields[$fieldname])) ? $preference->fields[$fieldname] : 0;
   }

   /**
    * Get a form header, this form header permit to update data of the widget
    * with parameters of this form
    * @param int $widgetId
    * @param bool $onsubmit
    * @return string , like '<form id=...>'
    */
   static function getFormHeader($widgetId, $onsubmit = false)
   {
      $formId = uniqid('form');
      if ($onsubmit) {
         $form = "<form id='" . $formId . "' action='' "
            . "onsubmit=\"mydashboard.updateOption('" . $widgetId . "','" . $formId . "'); return false;\">";
      } else {
         $form = "<form id='" . $formId . "' action='' onsubmit='return false;' ";
         $form .= "onchange=\"mydashboard.updateOption('" . $widgetId . "','" . $formId . "');\">";
      }
      return $form;
   }

   /**
    * Get a link to be used as a widget title
    * @param $pathfromrootdoc
    * @param $text
    * @param string $title
    * @return string
    */
   static function getATag($pathfromrootdoc, $text, $title = "")
   {
      global $CFG_GLPI;
      $title = ($title !== "") ? "title=$title" : "";
      return "<a href='" . $CFG_GLPI['root_doc'] . "/" . $pathfromrootdoc . "' $title target='_blank'>" . $text . "</a>";
   }

   /**
    * Return an unique id for a widget,
    * (can only be used on temporary plugins,
    *  because the id must represent the widget
    *  and every once this function is called it generates a new id)
    *
    * @return string
    */
   static function getUniqueWidgetId()
   {
      return uniqid("id_");
   }

   /**
    * Extract the content of the HTML script tag in an array 2D (line, column),
    * Useful for datatables
    * @param array 2D $arrayToEval
    * @return array of string (each string is a script line)
    */
   static function extractScriptsFromArray($arrayToEval)
   {
      $scripts = array();
      if (is_array($arrayToEval)) {
         if (!is_array($arrayToEval)) return $scripts;
         foreach ($arrayToEval as $array) {
            if (!is_array($array)) break;
            foreach ($array as $arrayLine) {
               $scripts = array_merge($scripts, self::extractScriptsFromString($arrayLine));
            }
         }
      }
      return $scripts;
   }

   /**
    * Get an array of scripts found in a string
    * @param string $stringToEval , a HTML string with potentially script tags
    * @return array of string
    */
   static function extractScriptsFromString($stringToEval)
   {
      $scripts = array();
      if (gettype($stringToEval) == "string") {
         $stringToEval = str_replace(array("'", "//<![CDATA[", "//]]>"), array('"', "", ""), $stringToEval);
//             $stringToEval = preg_replace('/\s+/', ' ', $stringToEval);

         if (preg_match_all("/<script[^>]*>([\s\S]+?)<\/script>/i", $stringToEval, $matches)) {
            foreach ($matches[1] as $match) {
//                     $match = preg_replace('/(\/\/[[:alnum:]_ ]+)/', '', $match);
//                     $match = preg_replace('#^\s*//.+$#m', "", $match);
               $scripts[] = $match;
            }
         }
      }
      return $scripts;
   }

   /**
    * Get a string without scripts from stringToEval,
    * it strips script tags
    * @param string $stringToEval , the string that you want without scripts
    * @return string with no scripts
    */
   static function removeScriptsFromString($stringToEval)
   {
      $stringWOScripts = "";
      if (gettype($stringToEval) == "string") {
         $stringWOScripts = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $stringToEval);
      }
      return $stringWOScripts;
   }


   /**
    * This method permit to avoid problems with function in JSon datas (example : tickFormatter)<br>
    * It's used to clean Json data needed to fill a widget<br>
    * Things like "function_to_call" => "function(){...}"
    * are replaced to look like "function_to_call" => function(){}<br>
    * This replacement cause the <b>return value</b> not being a valid Json object (<b>don't call json_decode on it</b>),
    * but it's necessary because some jquery plugins need functions and not string of function
    * @param type $datas , a formatted array of datas
    * @param type $options , a formatted array of options
    * @return a string formatted in JSon (most of the time, because in real JSon you can't have function)
    */
   static function safeJsonData($datas, $options)
   {
      $value_arr = array();
      $replace_keys = array();
      foreach ($options as & $option) {
         if (is_array($option)) {
            foreach ($option as $key => & $value) {
               // Look for values starting with 'function('

               if (is_string($value) && strpos($value, 'function(') === 0) {
                  // Store function string.
                  $value_arr[] = $value;
                  // Replace function string in $option with a 'unique' special key.
                  $value = '%' . $key . '%';
                  // Later on, we'll look for the value, and replace it.
                  $replace_keys[] = '"' . $value . '"';
               }
            }
         }
      }

      $json = str_replace($replace_keys,
         $value_arr,
         json_encode(array(
            'data' => $datas,
            'options' => $options
         )));

      return $json;
   }

   /**
    * Cleans and encodes in json an array
    * Things like "function_to_call" => "function(){...}"
    * are replaced to look like "function_to_call" => function(){}
    * This replacement cause the return not being a valid Json object (don't call json_decode on it),
    * but it's necessary because some jquery plugins need functions and not string of function
    * @param mixed $array , the array that needs to be cleaned and encoded in json
    * @return string a json encoded array
    */
   static function safeJson($array)
   {
      $value_arr = array();
      $replace_keys = array();
      foreach ($array as $key => & $value) {

         if (is_string($value) && strpos($value, 'function(') === 0) {
            // Store function string.
            $value_arr[] = $value;
            // Replace function string in $option with a 'unique' special key.
            $value = '%' . $key . '%';
            // Later on, we'll look for the value, and replace it.
            $replace_keys[] = '"' . $value . '"';
         }

      }

      $json = str_replace($replace_keys, $value_arr, json_encode($array));

      return $json;
   }

   /**
    * @param $widgettype
    * @param $query
    * @return PluginMydashboardDatatable|PluginMydashboardHBarChart|PluginMydashboardHtml|PluginMydashboardLineChart|PluginMydashboardPieChart|PluginMydashboardVBarChart
    */
   static function getWidgetsFromDBQuery($widgettype, $query/*$widgettype,$table,$fields,$condition,$groupby,$orderby*/)
   {
      global $DB;

      if (stripos(trim($query), "SELECT") === 0) {

         $result = $DB->query($query);
         $tab = array();
         if ($result) {
            while ($row = $DB->fetch_assoc($result)) {
               $tab[] = $row;
            }
            $linechart = false;
            $chart = false;
            switch ($widgettype) {
               case 'datatable':
               case 'table' :
                  $widget = new PluginMydashboardDatatable();
                  break;
               case 'hbarchart':
                  $chart = true;
                  $widget = new PluginMydashboardHBarChart();
                  break;
               case 'vbarchart':
                  $chart = true;
                  $widget = new PluginMydashboardVBarChart();
                  break;
               case 'piechart':
                  $chart = true;
                  $widget = new PluginMydashboardPieChart();
                  break;
               case 'linechart':
                  $linechart = true;
                  $widget = new PluginMydashboardLineChart();
                  break;
            }
            //            $widget = new PluginMydashboardHBarChart();
            //        $widget->setTabNames(array('Category','Count'));
            if ($chart) {
               $newtab = array();
               foreach ($tab as $key => $line) {
                  $line = array_values($line);
                  $newtab[$line[0]] = $line[1];
                  unset($tab[$key]);
               }
               $tab = $newtab;
            } elseif ($linechart) {
               //TODO format for linechart
            } else {
               //$widget->setTabNames(array('Category','Count'));
            }
            $widget->setTabDatas($tab);

         }
      } else {
         $widget = new PluginMydashboardHtml();
         $widget->debugError(__('Not a valid SQL SELECT query', 'mydashboard'));
         $widget->debugNotice($query);
      }

      return $widget;
   }
}
