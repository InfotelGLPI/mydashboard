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
 * Class PluginMydashboardWidget
 */
class PluginMydashboardWidget extends CommonDBTM
{

   static $rightname = "plugin_mydashboard";

   /**
    * @param int $nb
    * @return translated
    */
   static function getTypeName($nb = 0)
   {

      return __('Widget management', 'mydashboard');
   }

   /**
    * Get the widget name with his id
    * @global type $DB
    * @param type $widgetId
    * @return string, the widget 'name'
    */
   function getWidgetNameById($widgetId)
   {
//        global $DB;
//        $query = "SELECT name FROM `glpi_plugin_mydashboard_widgets` WHERE id = '".$widgetId."'";
//        $result = $DB->query($query);
//        if($result && $DB->numrows($result)>0 )
//        {
//            return $DB->result($result,0,'name');
//        }
//        else return NULL;
//        
      $query = "WHERE id = '" . $widgetId . "'";
      $this->getFromDBByQuery($query);

      return isset($this->fields['name']) ? $this->fields['name'] : null;
   }

   /**
    * Get the widgets_id by its 'name'
    * @global type $DB
    * @param string $widgetName
    * @return the widgets_id if found, NULL otherwise
    */
   function getWidgetIdByName($widgetName)
   {
//        global $DB;
//        $query = "SELECT `id` FROM `glpi_plugin_mydashboard_widgets` WHERE name = '".$widgetName."'";
//        
//        $result = $DB->query($query);
//        if($result && $DB->numrows($result)>0 )
//        {
//            return $DB->result($result,0,'id');
//        }
//        else return NULL;
      unset($this->fields);
      $query = "WHERE name = '" . $widgetName . "'";
      $this->getFromDBByQuery($query);
      return isset($this->fields['id']) ? $this->fields['id'] : null;
   }

   /**
    * Useful if you want to check if a widgetname is available
    * @global type $DB
    * @param string $widgetName , the name you want to check
    * @return boolean, TRUE if it's available, FALSE otherwise
    */
   static function isWidgetNameAvailable($widgetName)
   {
      global $DB;
      $query = "SELECT `id` FROM `glpi_plugin_mydashboard_widgets` WHERE name = '" . $widgetName . "'";

      $result = $DB->query($query);
      if ($result && $DB->numrows($result) > 0) {
         return false;
      } else return true;
   }

   /**
    * Save a new widget Name
    * @global type $DB
    * @param string $widgetName
    * @return TRUE if the new widget name has been added, FALSE otherwise
    */
   function saveWidget($widgetName)
   {
//        if(isset($widgetName) && $widgetName !== "")
//        {
////            $widgetId = $this->getWidgetIdByName($widgetName);
////            if(isset($widgetId)) return true;
//            global $DB;
//            $query = "INSERT IGNORE INTO `glpi_plugin_mydashboard_widgets` (`id`, `name`) VALUES (NULL, '".$widgetName."')";
//            $result = $DB->query($query);
//            if($result && $DB->affected_rows()>0 )
//            {
//                return true;
//            }
//            else return false;
//        }
//        return false;
      if (isset($widgetName) && $widgetName !== "") {
//            $widgettmp = preg_replace( '/[^[:alnum:]_]+/', '', $widgetName );
         //Not really good regex
         $widgettmp = preg_match('#[^.0-9a-z]+#i', $widgetName, $matches);

         if ($widgettmp == 1) {
            Toolbox::logDebug("'$widgetName' can't be used as a widget Name, '$matches[0]' is not a valid character ");
            return false;
         }

         $this->fields["id"] = null;
         $id = $this->getWidgetIdByName($widgetName);

         if (!isset($id)) {
            $this->fields = [];
            $this->add(array("name" => $widgetName));
         }
         return true;
      } else return false;
   }

   /**
    * @param $widgetName
    * @return true
    */
   function removeWidgetByName($widgetName)
   {
      //$widgetName = preg_replace( '/[^[:alnum:]_]+/', '', $widgetName );
      $this->getFromDBByQuery("WHERE `glpi_plugin_mydashboard_widgets`.`name` = '" . $widgetName . "'");
      return $this->deleteFromDB();
   }


}