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
 * This widget class is meant to display data as a table
 */
class PluginMydashboardDatatable extends PluginMydashboardModule
{

   private $tabNames = [];
   private $tabNamesHidden = [];
   private $tabDatas = [];
   private $tabDatasSet = false;
   private $options = [];
   static $rightname = "plugin_mydashboard";

   /**
    * PluginMydashboardDatatable constructor.
    */
   function __construct() {
      $this->setWidgetType("table");
   }

   /**
    * @param int $nb
    * @return translated
    */
   static function getTypeName($nb = 0) {

      return __('Dashboard', 'mydashboard');
   }

   /**
    * get an array representing the header of the datatable
    * @return array header,
    */
   function getTabNames() {
      return $this->tabNames;
   }

   /**
    * @return array
    */
   function getTabDatas() {
      return $this->tabDatas;
   }

   /**
    * @return array
    */
   function getTabNamesHidden() {
      return $this->tabNamesHidden;
   }

   /**
    * set an array representing the header
    * @param array $_tabNames , array of header (corresponding to an html 'th' tag)<br>
    * for example array('header1','header2',...,'headern')
    */
   function setTabNames($_tabNames) {
      if (is_array($_tabNames)) {
         $this->tabNames = $_tabNames;

      }
      return $this->tabNames;
   }

   /**
    * @param $_tabNamesHidden
    */
   function setTabNamesHidden($_tabNamesHidden) {
      $this->tabNamesHidden = array_flip($_tabNamesHidden);
   }

   /**
    * set an array of data
    * @param array $_tabDatas
    * should and must look like array( array(1,'foo','bar'), array(2,'bar',bar')) for a datatable of two lines
    */
   function setTabDatas($_tabDatas) {
      if (is_array($_tabDatas)) {
         $this->tabNamesSet = !empty($this->tabNames);
         $this->tabDatasSet = true;
         //for a datatable we ignore all custom keys (they are useless in this case)
         foreach ($_tabDatas as &$line) {
            //For future, maybe use those custom keys to match with order of the header and not use order of the array
            //Could be done like this :
//            if ($this->tabNamesSet) {
//               $line = $this->sortArrayByArray($line, $this->tabNames);
//            }
            $line = array_values($line);

         }
         $this->tabDatas = array_values($_tabDatas);
         $this->setWidgetScripts(PluginMydashboardHelper::extractScriptsFromArray($this->tabDatas));
      } else {
         $this->debugError(__("Not an array", 'mydashboard'));
      }
   }

   /**
    * @param array $array
    * @param array $orderArray
    * @return array
    */
   function sortArrayByArray(Array $array, Array $orderArray) {
      $ordered = [];
      ksort($orderArray);
      foreach ($orderArray as $index => $key) {
         if (isset($array[$key])) {
            $ordered[$index] = $array[$key];
         } else {
            $array = array_values($array);
            $ordered[$index] = $array[$index];
         }
      }

      return $ordered;
   }

   /**
    *
    * @return an array of all options
    */
   function getOptions() {
      return $this->options;
   }

   /**
    * Set a specific option for the datatable
    * (see jquery datatable documentation for options available http://legacy.datatables.net/usage/features)
    * @param type $optionName , the name of the option
    * @param type $optionValue , the value of the option (can be a php array)
    * @param bool $force
    * @return bool
    */
   function setOption($optionName, $optionValue, $force = false) {
      if (isset($this->options[$optionName])) {
         if (is_array($optionValue)) {
            $this->options[$optionName] = array_merge($this->options[$optionName], $optionValue);
            return true;
         }
      }
      $this->options[$optionName] = $optionValue;
      return true;
   }

   /**
    * Return a json encoded string ready to make a Datatable widget
    * @return string
    */
   function getJSonDatas() {
      //Use template coloration for datatables widget
      $this->useTemplatesScript();

      //Headers
      $jsonData['aoColumns'] = $this->getTabNames();
      //If no headers is defined we must match number of element in a line and number of columns
      if (empty($jsonData['aoColumns'])) {
         $jsonData['aoColumns'] = $this->getDefaultColumns();
      }
      //Important attribute of the aoColumns is 'sTitle', the title of the column
      foreach ($jsonData['aoColumns'] as &$value) {
         $tmp = $value;
         $tmp = ['sTitle' => str_replace(["\r\n", "\r", "\n"/*, "'"*/], ["", "", ""/*, '"'*/], $tmp)];
         if (isset($this->tabNamesHidden[$value])) {
            $tmp['bVisible'] = false;
         }
         $value = $tmp;
      }
      //Warning when setTabDatas haven't been called (it could have been called with an empty array, but that's not a problem)
      if (!$this->tabDatasSet) {
         $this->debugWarning(__("No data is given to the widget", 'mydashboard'));
      }

      //Setting datas
      $jsonData['aaData'] = $this->getTabDatas();

      //Setting options
      //number of lines displayed
      //nmbers available to select
      //$jsonData['LengthMenu'] = [[10, 25, 50, -1], [10, 25, 50, __("All")]];
      $jsonData['bAutoWidth'] = false;

      //Setting specific options
      foreach ($this->getOptions() as $optionName => $optionValue) {
         $jsonData[$optionName] = $optionValue;
      }

      return json_encode($jsonData);
   }

   /**
    * Get default header (when tabNames is null or empty)
    * Number of header is determined by the maximum number of element in lines
    * @return array of N empty strings, Nbeing the maximum number of element in lines
    */
   function getDefaultColumns() {
      $this->debugNotice(__("No column defined", 'mydashboard'));
      if (!empty($this->tabDatas)) {
         $count = count($this->tabDatas[0]);
         foreach ($this->tabDatas as $line) {
            $tmp = count($line);
            $count = ($tmp > $line) ? $tmp : $count;
         }
      } else {
         $count = 1;
      }
      $columns = [];
      for ($i = 0; $i < $count; $i++) {
         array_push($columns, "");
      }
      return $columns;
   }

   function useTemplatesScript() {
      $scripts = [];
      $scripts[] = "var liwidget = $('#" . $this->getWidgetId() . "');";
      $scripts[] = "liwidget.find('table').removeClass('sDashboardTableView');";
      $scripts[] = "liwidget.find('table').addClass('tab_cadrehov');";
      $scripts[] = "liwidget.find('table').css('width','100%');";
      $this->appendWidgetScripts($scripts);
   }
}
