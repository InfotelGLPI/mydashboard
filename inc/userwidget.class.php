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
 * This class handles the storage of the mydashboard every Users
 * It associates the users_id to a widgets_id with a place
 * users_id : id of the user
 * widgets_id : id of the widget, refers to glpi_plugin_mydashboard_widgets.id
 */
class PluginMydashboardUserWidget extends CommonDBTM
{

   private $user_id;
   private $interface;
   static $rightname = "plugin_mydashboard";

   /**
    * PluginMydashboardUserWidget constructor.
    * @param int $user_id
    * @param int $interface
    */
   public function __construct($user_id = 0, $interface = -1)
   {
      parent::__construct();
      //1 for central
      //0 for interface
      if ($interface == -1) {
         $this->interface = ($_SESSION['glpiactiveprofile']['interface'] == 'central') ? 1 : 0;
      } else {
         $this->interface = $interface;
      }
      $this->user_id = $user_id;
   }

   /**
    * @param int $nb
    * @return translated
    */
   static function getTypeName($nb = 0)
   {

      return __('user_widget management', 'mydashboard');
   }


   /**
    * Get the id of the triplet (users_id, widgets_id, place)
    * @param int $widgetId , id of the widget
    * @return int if there is a triplet as (users_id, widgets_id, X), esle NULL
    * @global type $DB
    * @internal param int $userId , id of the user
    */
   function getIdByUserIdWidgetId($widgetId)
   {
//        global $DB;
//        $query = "SELECT `id` FROM `glpi_plugin_mydashboard_users_widgets` WHERE (`users_id` = '".$userId."' && `widgets_id` = '".$widgetId."')";
//        
//        $result = $DB->query($query);
//        if($result && $DB->numrows($result)>0 )
//        {
//            return $DB->result($result,0,'id');
//        }
//        else return NULL;
//        
      if (!$this->checkWidgetId($widgetId)) return null;
      $query = "WHERE (`users_id` = '" . $this->user_id . "' AND `widgets_id` = '" . $widgetId . "' AND `interface` = $this->interface)";
      $this->getFromDBByQuery($query);
      return (isset($this->fields['id'])) ? $this->fields['id'] : null;
   }

   /**
    * Get an array of widget 'name's for a user ($userId)
    * @param boolean $canbeempty , TRUE the mydashboard can be empty, FALSE it can't,<br>
    *                             Used when the default MyDashboard is empty
    * @global type $DB
    * @return array of widget 'name's
    * @global type $DB
    * @global type $DB
    */
   function getWidgets($canbeempty = false, $user_id = null)
   {
      global $DB;
      if (!isset($user_id)) {
         $user_id = $this->user_id;
      }
      $query = "SELECT `name` FROM `" . $this->getTable() . "` "
         . "LEFT JOIN `glpi_plugin_mydashboard_widgets` "
         . "ON `" . $this->getTable() . "`.`widgets_id` = `glpi_plugin_mydashboard_widgets`.`id` "
         . "WHERE `" . $this->getTable() . "`.`users_id` = '" . $user_id . "' "
         . "AND `interface` = $this->interface "
         . "ORDER BY `place` ASC ";
      $result = $DB->query($query);

      $tab = array();
      while ($row = $DB->fetch_array($result)) {
         array_push($tab, $row['name']);
      }
      if (!$canbeempty && count($tab) == 0) {
         //Two choices :
         // either SET a default Dashboard for the user
         // $this->setDefault();
         // or GET the default Dashboard

         //in the case user_id = 0
         $this->initDefault();
         return $this->getWidgets(true, 0);
      }
      return $tab;
   }

   /**
    * Get the place of a widget by its id
    * @param int $widgetId
    * @return int place of the widget, null otherwise
    */
   public function getWidgetPlace($widgetId)
   {
      unset($this->fields['place']);
      $query = "WHERE (`" . $this->getTable() . "`.`users_id` = '" . $this->user_id . "' "
         . "AND `" . $this->getTable() . "`.`widgets_id` = '" . $widgetId . "' "
         . "AND `interface` = $this->interface)";
      $this->getFromDBByQuery($query);
      $result = isset($this->fields['place']) ? $this->fields['place'] : null;
      return $result;
   }

   /**
    * Get an array of widgets_id for a user ($userId)
    * @global type $DB
    * @return array of widgets_id
    */
   private function getWidgetsIdPlace()
   {
//        global $DB;
//        
//        $query = "SELECT `".$this->getTable()."`.`widgets_id`, `".$this->getTable()."`.`place` FROM `".$this->getTable()."` "
//                ."WHERE `".$this->getTable()."`.`users_id` = '".$this->user_id."' "
//                ."ORDER BY `place` ASC ";
//        $result = $DB->query($query);

      $widgets = getAllDatasFromTable($this->getTable(), "`" . $this->getTable() . "`.`users_id` = '" . $this->user_id . "' "
         . "AND `interface` = $this->interface ORDER BY `place` ASC ");

      $tab = array();

      foreach ($widgets as $widget) {
         $tab[] = array('widgets_id' => $widget['widgets_id'], 'place' => $widget['place']);
      }
//        while ($row = $DB->fetch_array($result)) {
//            $tab[] =array('widgets_id' => $row['widgets_id'], 'place' => $row['place']);
//        }
      return $tab;
   }

   /**
    * Saves a triplet (user_id, widget_id, place) representing User,Widget and the Place of the Widget on the User's dashboard
    * @global type $DB
    * @param int $widgetId , id of the widget
    * @param int $place , place of the widget
    * @return boolean
    */
   public function saveWidgetIdPlace($widgetId, $place)
   {
      global $DB;
      if ($this->checkWidgetId($widgetId)
         && $this->checkPlace($place)
      ) {
//            //We check if the place is not already taken
//            $query = "SELECT `glpi_plugin_mydashboard_users_widgets`.`widgets_id` FROM `glpi_plugin_mydashboard_users_widgets` "
//                      ."WHERE (`glpi_plugin_mydashboard_users_widgets`.`users_id` = '".$this->user_id."' AND `glpi_plugin_mydashboard_users_widgets`.`place` = '".$place."')"
//                ."ORDER BY `place` ASC ";
//            
//            $result = $DB->query($query);
//            //Only if no widgets are here
//            if($DB->num_rows($result) == 0)
//            {

         $nLine = array(
            "users_id" => $this->user_id,
            "widgets_id" => $widgetId,
            "place" => $place,
            "interface" => $this->interface
         );

         //Reset of the id
         unset($this->fields["id"]);
         $id = $this->getIdByUserIdWidgetId($widgetId);

         if (isset($id)) {
            $nLine["id"] = $id;
            $this->update($nLine);
         } else {
            $this->add($nLine);
         }
         // var_dump($nLine);
         return true;
//            }
      }
      return false;
   }

   /**
    * Removes a widget ($widgetName) from the $this->user_id's Dashboard
    * @global type $DB
    * @param string $widgetName , name of the widget
    * @return boolean, true in normal case, false when the query went wrong
    */
   public function removeWidgetByWidgetName($widgetName)
   {
      global $DB;
      $widget = new PluginMydashboardWidget();
      $widgetId = $widget->getWidgetIdByName($widgetName);
      return $this->removeWidgetByWidgetId($widgetId);
   }

   /**
    * Removes a widget ($widgetId) from the $this->user_id 's Dashboard
    * @global type $DB
    * @param int $widgetId , id of the widget
    * @return boolean, true in normal case, false when the query went wrong
    */
   public function removeWidgetByWidgetId($widgetId)
   {
      global $DB;
      if ($this->checkWidgetId($widgetId)) {
         $this->getFromDBByQuery("  WHERE (`users_id` = '" . $this->user_id . "' "
            . "AND `widgets_id` = '" . $widgetId . "' "
            . "AND `interface` = $this->interface)");

         $this->deleteFromDB();
         //All later added widgets MUST be updated, their places are not the same anymore
         $this->updateOthersPlaces($this->fields['place']);
//            $query = "DELETE FROM `".$this->getTable()."` WHERE (`users_id` = '".$this->user_id."' && `widgets_id` = '".$widgetId."')";
//            $result = $DB->query($query);
//            
//            if($result) {
//                return true;
//            }
//            else return false;
      }
      return true;
   }

   /**
    * Remove all widgets from the user's dashboard
    * @global type $DB
    */
   public function removeWidgets()
   {
      global $DB;
      $query = "DELETE FROM `" . $this->getTable() . "` WHERE (`users_id` = '" . $this->user_id . "' AND `interface` = $this->interface)";
      $DB->query($query);
   }

   /**
    * Set a default dashboard for $this->user_id,
    * @param array $to_be_deleted
    * @internal param int $userId ,
    */
   public function setDefault($to_be_deleted = array())
   {

      //We make sure that there is a default configuration
      $this->initDefault();

      //We get this default configuration userId = 0
      $defaultDashboard = new self(0);
      $defaultTab = $defaultDashboard->getWidgetsIdPlace();
//        $widget = new PluginMydashboardWidget();
      //For each widget of the default dashboard we add it to userId 's dashboard
      //We add only the widgets that can be used by user (by its profile)
      $pauthwidget = new PluginMydashboardProfileAuthorizedWidget();
      $authwidgets = $pauthwidget->getAuthorizedListForProfile($_SESSION['glpiactiveprofile']['id']);
      //$authwidgets each items of authwidgets is "widgetid" => <widgets_i> and we want it as <widget_id> => "widgetid"
      if (is_array($authwidgets)) array_flip($authwidgets);
      foreach ($defaultTab as $defaultWidget) {
//            if(!in_array($widget->getWidgetNameById($defaultWidget['widgets_id']), $to_be_deleted)) {
         if ((!empty($authwidgets)
               && !isset($authwidgets[$defaultWidget['widgets_id']]))
            || !$authwidgets
         ) {
            $this->saveWidgetIdPlace($defaultWidget['widgets_id'], $defaultWidget['place']);
         }
//            }   
      }
   }

   /**
    * Initialize the default dashboard
    * @global type $DB
    */
   private function initDefault()
   {
      global $DB;

      //Query to check if there is a default dashboard
      $query = "SELECT * FROM `" . $this->getTable() . "` WHERE (`users_id` = '0' AND `interface` = '1')";
      $result = $DB->query($query);
      //Init default central (1) :
      //If there is no default dashboard
      if ($result && ($DB->numrows($result) == 0)) {
         //DONE : Those are widgets_id s, maybe a better way would be to get those id's from names, but by default no names is known
         //Widgets are initialized before user_widgets is used, by this way we know widgets names
         $default = array();
         $default[] = "planningwidget";
         $default[] = "reminderpersonalwidget";
         $default[] = "reminderpublicwidget";
         $default[] = "ticketlistprocesswidget";
         $default[] = "ticketlistrequestbyselfwidget";
         $default[] = "problemprocesswidget";

         //We replace names by ids
         foreach ($default as $key => $d) {
            $widget = new PluginMydashboardWidget();
            $default[$key] = $widget->getWidgetIdByName($d);
         }
         foreach ($default as $key => $d) {
            if (isset($d) && !empty($d)) {
               $query = "INSERT INTO `" . $this->getTable() . "` (`id`,`users_id`,`widgets_id`,`place`,`interface`) VALUES (NULL,0,$d,$key,1)";
               $DB->query($query);
            }
         }
      }
      //Init default helpdesk (0):
      $query = "SELECT * FROM `" . $this->getTable() . "` WHERE (`users_id` = '0' AND `interface` = '0')";
      $result = $DB->query($query);

      //If there is no default dashboard
      if ($result && ($DB->numrows($result) == 0)) {
         $default = array();
         $default[] = "rssfeedpublicwidget";
         $default[] = "knowbaseitemrecent";
         $default[] = "knowbaseitemlastupdate";
         $default[] = "knowbaseitempopular";
         $default[] = "ticketlistrequestbyselfwidget";
         $default[] = "ticketcountwidget";
         //We replace names by ids
         foreach ($default as $key => $d) {
            $widget = new PluginMydashboardWidget();
            $default[$key] = $widget->getWidgetIdByName($d);
         }
         foreach ($default as $key => $d) {
            if (isset($d) && !empty($d)) {
               $query = "INSERT INTO `" . $this->getTable() . "` (`id`,`users_id`,`widgets_id`,`place`,`interface`) VALUES (NULL,0,$d,$key,0)";
               $DB->query($query);
            }
         }
      }
   }

   /**
    * Check the validity of a widgetId
    * @param int $widgetId
    * @return boolean, TRUE if valid, FALSE otherwise
    */
   private function checkWidgetId($widgetId)
   {
      //$this->checkSpecificValues($datatype, $value);
      return is_numeric($widgetId) && $widgetId >= 0;
   }

   /**
    * Check the validity of a place
    * @param int $place
    * @return boolean, TRUE if valid, FALSE otherwise
    */
   private function checkPlace($place)
   {
      return is_numeric($place) && $place >= 0;
   }

//    private function checktSpecificValues($datatype, $value) {
//       switch($datatype)
//       {
//          case "tinyint":
//             break;
//          case "int":
//             break;
//          case "char":
//             break;
//          case "varchar":
//             break;
//       }
//    }

   /**
    * Update all later added widgets for a user (when a widget is deleted)
    * @global type $DB
    * @param int $place
    * @return type
    */
   private function updateOthersPlaces($place)
   {
      global $DB;
      if (!$this->checkPlace($place)) return false;
      //We must update places of other widgets (those which were added after the one that just have been deleted
      $query = "UPDATE `" . $this->getTable() . "` SET `place` = `place`-1 ";
      $query .= "WHERE (`users_id` = '" . $this->user_id . "' && `place` > " . $place . ") ;";
      return $DB->query($query);

   }

}