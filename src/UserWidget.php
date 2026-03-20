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

namespace GlpiPlugin\Mydashboard;

use CommonDBTM;
use DBConnection;
use Migration;
use Session;

/**
 * This class handles the storage of the mydashboard every Users
 * It associates the users_id to a widgets_id with a place
 * users_id : id of the user
 * widgets_id : id of the widget, refers to glpi_plugin_mydashboard_widgets.id
 */
class UserWidget extends CommonDBTM {

   private $user_id;
   private $interface;
   static  $rightname = "plugin_mydashboard";

   /**
    * UserWidget constructor.
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
      $this->user_id = $user_id;
   }

   /**
    * @param int $nb
    *
    * @return string
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
                                  'profiles_id' => $_SESSION['glpiactiveprofile']['id'],
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
                . "AND `profiles_id` = '".$_SESSION['glpiactiveprofile']['id']."' ";
      $result = $DB->doQuery($query);

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

    public static function install(Migration $migration)
    {
        global $DB;

        $default_charset   = DBConnection::getDefaultCharset();
        $default_collation = DBConnection::getDefaultCollation();
        $default_key_sign  = DBConnection::getDefaultPrimaryKeySignOption();
        $table  = self::getTable();

        if (!$DB->tableExists($table)) {
            $query = "CREATE TABLE `$table` (
                        `id` int {$default_key_sign} NOT NULL auto_increment,
                        `users_id`    int {$default_key_sign} NOT NULL COMMENT 'RELATION to glpi_users(id)',
                        `profiles_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                        `widgets_id`  int {$default_key_sign} NOT NULL,
                        PRIMARY KEY (`id`),
                        KEY `users_id` (`users_id`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);

        }

        if (!$DB->fieldExists($table, "profiles_id")) {
            $migration->addField($table, "profiles_id", "int {$default_key_sign} NOT NULL DEFAULT '0'");
            $migration->migrationOneTable($table);
        }

        if ($DB->fieldExists($table, "place")) {
            $migration->dropField($table, "place");
            $migration->migrationOneTable($table);
        }

        if ($DB->fieldExists($table, "interface")) {

            //No default profile
            $query_userwidgets = "SELECT DISTINCT `users_id`
                                    FROM `glpi_plugin_mydashboard_userwidgets`
                                    WHERE `profiles_id` = 0
                                      AND `users_id` != 0
                                      AND `interface` != 0;";

            if ($result_userwidgets = $DB->doQuery($query_userwidgets)) {
                if ($DB->numrows($result_userwidgets) > 0) {
                    while ($data_userwidgets = $DB->fetchAssoc($result_userwidgets)) {

                        $user_id        = $data_userwidgets['users_id'];
                        //Search for user profiles
                        $query_profiles_users = "SELECT *
                                                FROM `glpi_profiles_users`
                                                WHERE `users_id` = " . $user_id . "
                                                ORDER BY `id`";

                        if ($result_profiles_users = $DB->doQuery($query_profiles_users)) {
                            if ($DB->numrows($result_profiles_users) > 0) {
                                while ($data_profiles_users = $DB->fetchAssoc($result_profiles_users)) {
                                    $profiles_id = $data_profiles_users['profiles_id'];

                                    //Check if profile has rights on the plugin
                                    $query  = "SELECT *
                                               FROM `glpi_profilerights`
                                               WHERE `profiles_id` = '" . $profiles_id . "'
                                               AND `name` LIKE 'plugin_mydashboard'
                                               AND `rights` > 0;";
                                    $result = $DB->doQuery($query);

                                    if ($DB->numrows($result) > 0) {

                                        // update default profiles_id
                                        $query = "UPDATE `glpi_plugin_mydashboard_userwidgets`
                                                SET `profiles_id` = '$profiles_id'
                                                WHERE `glpi_plugin_mydashboard_userwidgets`.`users_id` = $user_id
                                                  AND `interface` != 0;";
                                        $DB->doQuery($query);
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $migration->dropField($table, "interface");
            $migration->migrationOneTable($table);
        }
    }

    public static function uninstall()
    {
        global $DB;

        $DB->dropTable(self::getTable(), true);

    }
}
