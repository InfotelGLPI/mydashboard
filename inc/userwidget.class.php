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
class PluginMydashboardUserWidget extends CommonDBTM {

   private $user_id;
   private $interface;
   static  $rightname = "plugin_mydashboard";

   /**
    * PluginMydashboardUserWidget constructor.
    *
    * @param int $user_id
    * @param int $interface
    */
   public function __construct($user_id = 0, $interface = -1) {
      parent::__construct();
      //1 for central
      //0 for interface
      if ($interface == -1) {
         $this->interface = (Session::getCurrentInterface() == 'central') ? 1 : 0;
      } else {
         $this->interface = $interface;
      }
      $this->profile_id = $_SESSION['glpiactiveprofile']['id'];
      $this->user_id = $user_id;
   }

   /**
    * @param int $nb
    *
    * @return translated
    */
   static function getTypeName($nb = 0) {

      return __('user_widget management', 'mydashboard');
   }


   /**
    * Get the id of the triplet (users_id, widgets_id, place)
    *
    * @param int   $widgetId , id of the widget
    *
    * @return int if there is a triplet as (users_id, widgets_id, X), esle NULL
    * @global type $DB
    * @internal param int $userId , id of the user
    */
   function getIdByUserIdWidgetId($widgetId) {

      if (!$this->checkWidgetId($widgetId)) {
         return null;
      }
      
      if ($this->getFromDBByCrit(['users_id' => $this->user_id,
                                  'widgets_id' => $widgetId,
                                  'profiles_id' => $this->profile_id,
                                  'interface' => $this->interface]) === false) {
         return null;
      } else {
         return isset($this->fields['id']) ? $this->fields['id'] : null;
      }
   }

   /**
    * Get an array of widget 'name's for a user ($userId)
    *
    * @param bool $canbeempty TRUE the mydashboard can be empty, FALSE it can't,<br>
                                    Used when the default MyDashboard is empty
    * @param null $user_id
    *
    * @return array of widget 'name's
    */
   function getWidgets($canbeempty = false, $user_id = null) {
      global $DB;

      if (!isset($user_id)) {
         $user_id = $this->user_id;
      }
      $query  = "SELECT `name` FROM `" . $this->getTable() . "` "
                . "LEFT JOIN `glpi_plugin_mydashboard_widgets` "
                . "ON `" . $this->getTable() . "`.`widgets_id` = `glpi_plugin_mydashboard_widgets`.`id` "
                . "WHERE `" . $this->getTable() . "`.`users_id` = '" . $user_id . "' "
                . "AND `profiles_id` = '$this->profile_id' ";
      $result = $DB->query($query);

      $tab = [];
      while ($row = $DB->fetchArray($result)) {
         array_push($tab, $row['name']);
      }
      if (!$canbeempty && count($tab) == 0) {
         return $this->getWidgets(true, 0);
      }
      return $tab;
   }

   /**
    * Check the validity of a widgetId
    *
    * @param int $widgetId
    *
    * @return boolean, TRUE if valid, FALSE otherwise
    */
   private function checkWidgetId($widgetId) {
      return is_numeric($widgetId) && $widgetId >= 0;
   }

}
