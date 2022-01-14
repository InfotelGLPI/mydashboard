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
 * Update from 1.3.3 to 1.5.0
 *
 * @return bool for success (will die for most error)
 * */
function update133to150() {
   global $DB;

   $migration = new Migration(150);

   //Assigning the right profile
   //   $query_userwidgets = "SELECT DISTINCT `users_id` FROM `glpi_plugin_mydashboard_userwidgets` WHERE `profiles_id` = 0 AND `users_id` != 0;";

   //   if ($result_userwidgets = $DB->query($query_userwidgets)) {
   //      if ($DB->numrows($result_userwidgets) > 0) {
   //         while ($data_userwidgets = $DB->fetchAssoc($result_userwidgets)) {
   //
   //            $user_id        = $data_userwidgets['users_id'];
   //
   //            //Default profile search
   //            $query       = "SELECT *
   //                           FROM `glpi_users`
   //                           WHERE `id` = '" . $user_id."';";
   //            $result      = $DB->query($query);
   //            $profiles_id = $DB->result($result, 0, 'profiles_id');
   //
   //            //Check if profile has rights on the plugin
   //            $query       = "SELECT *
   //                           FROM `glpi_profilerights`
   //                           WHERE `profiles_id` = '" . $profiles_id."'
   //                           AND `name` LIKE 'plugin_mydashboard'
   //                           AND `rights` > 0;";
   //            $result      = $DB->query($query);
   //
   //            if ($DB->numrows($result) > 0) {
   //
   //               // update default profiles_id
   //               $query = "UPDATE `glpi_plugin_mydashboard_userwidgets` SET `profiles_id` = '$profiles_id' WHERE `glpi_plugin_mydashboard_userwidgets`.`users_id` = $user_id;";
   //               $DB->query($query);
   //            }
   //
   //         }
   //      }
   //   }

   //No default profile
   $query_userwidgets = "SELECT DISTINCT `users_id` FROM `glpi_plugin_mydashboard_userwidgets` 
                        WHERE `profiles_id` = 0 AND `users_id` != 0 AND `interface` != 0;";

   if ($result_userwidgets = $DB->query($query_userwidgets)) {
      if ($DB->numrows($result_userwidgets) > 0) {
         while ($data_userwidgets = $DB->fetchAssoc($result_userwidgets)) {

            $user_id        = $data_userwidgets['users_id'];

            //Search for user profiles
            $query_profiles_users = "SELECT *
                                    FROM `glpi_profiles_users` 
                                    WHERE `users_id` = " . $user_id . "
                                    ORDER BY `id`";

            if ($result_profiles_users = $DB->query($query_profiles_users)) {
               if ($DB->numrows($result_profiles_users) > 0) {
                  while ($data_profiles_users = $DB->fetchAssoc($result_profiles_users)) {
                      $profiles_id = $data_profiles_users['profiles_id'];

                     //Check if profile has rights on the plugin
                     $query  = "SELECT *
                           FROM `glpi_profilerights` 
                           WHERE `profiles_id` = '" . $profiles_id . "'
                           AND `name` LIKE 'plugin_mydashboard'
                           AND `rights` > 0;";
                     $result = $DB->query($query);

                     if ($DB->numrows($result) > 0) {

                        // update default profiles_id
                        $query = "UPDATE `glpi_plugin_mydashboard_userwidgets` SET `profiles_id` = '$profiles_id' 
                                  WHERE `glpi_plugin_mydashboard_userwidgets`.`users_id` = $user_id AND `interface` != 0;";
                        $DB->query($query);
                        break;
                     }
                  }
               }
            }
         }
      }
   }

   $query = "ALTER TABLE `glpi_plugin_mydashboard_userwidgets` DROP `place`;";
   $DB->queryOrDie($query, "DROP Place field");

   $query = "ALTER TABLE `glpi_plugin_mydashboard_userwidgets` DROP `interface`;";
   $DB->queryOrDie($query, "DROP interface field");

   return true;
}
