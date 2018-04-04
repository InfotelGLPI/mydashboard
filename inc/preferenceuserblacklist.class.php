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
 * Preference_User_Blacklist is the class that handles plugins blacklist for a User
 * An user can disable the display of widgets of some plugins (because it doesn't need widgets of this plugin for example)
 */
class PluginMydashboardPreferenceUserBlacklist extends CommonDBTM
{

   /**
    * Show a form to blacklist some plugins for Dashboard
    * @param int $user_id , id of the user concerned
    * @return bool
    */
   public function showForm($user_id)
   {
      global $CFG_GLPI, $PLUGIN_HOOKS;
      $options['candel'] = false;
      $options['colspan'] = 1;

      //We don't display this form in helpdesk interface
      if ($_SESSION['glpiactiveprofile']['interface'] != 'central') {
         return false;
      }

      //If there is plugin hooked to dashboard
      if (isset($PLUGIN_HOOKS['mydashboard'])) {
         $blacklist = $this->getBlacklistForUser($user_id);

         $pluginObject = new Plugin();
         echo "<form method='post' action='" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/front/preferenceuserblacklist.form.php' onsubmit='return true;'>";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='headerRow'><th class='center' colspan='2'>";
         echo __("From which plugins you want to display the widgets?", 'mydashboard');
         echo "</th></tr>";
         //Every plugins can be blacklisted by user, by default every plugins
         foreach ($PLUGIN_HOOKS['mydashboard'] as $pluginname => $x) {
            if ($pluginObject->isActivated($pluginname)) {
               echo "<tr class='tab_bg_1'><td>" . $this->getLocalName($pluginname) . "</td>";
               echo "<td>";
               $yesno = 1;
               if (isset($blacklist[$pluginname])) $yesno = 0;
               Dropdown::showYesNo("pn" . $pluginname, $yesno);
               echo "</td>";
               echo "</tr>";
            }
         }

         echo "<tr class='tab_bg_2'><td class='center' colspan='2'>";
         echo "<input type='submit' name='update' value=\"" . _sx('button', 'Save') . "\" class='submit'>";
         echo "<input type='hidden' name='id' value=" . Session::getLoginUserID() . ">";
         Html::closeForm();

         echo "</td></tr></table>";
      }
   }

   /**
    * Save a black list from the form
    * @param array $post , values
    */
   public function save($post)
   {

      if (isset($post) && isset($post['id'])) {
         $user_id = $post['id'];
         $currentblacklist = $this->getBlacklistForUser($user_id);
         $newblacklist = array();
         foreach ($post as $key => $value) {
            if ($key == "id") {
               continue;
            }
            if (substr($key, 0, 2) == "pn" && $value == 0) {
               $newblacklist[] = substr($key, 2);
            }
         }
         //to_delete contains names of the plugins that will no longer be blacklisted
         $to_delete = self::arrayDiffEmulation($currentblacklist, $newblacklist);
         //newblacklist contains names of the plugins newly blacklisted
         $newblacklist = self::arrayDiffEmulation($newblacklist, $currentblacklist);
         //We store new blacklist
         foreach ($newblacklist as $plugin_name) {
            $this->saveItem($user_id, $plugin_name);
         }
         //We remove no longer blacklisted
         foreach ($to_delete as $delete_blacklist_item) {
            $this->deleteItem($user_id, $delete_blacklist_item);
         }
      }
   }

   /**
    * Save an item correponding to a blacklisting of a plugin for an user
    * @param int $user_id , id of user
    * @param string $plugin_name , name of the plugin
    */
   private function saveItem($user_id, $plugin_name)
   {
      global $DB;
      $query = "SELECT * "
         . "FROM `glpi_plugin_mydashboard_preferenceuserblacklists`"
         . "WHERE `users_id` = " . $user_id . " "
         . "AND `plugin_name` = '$plugin_name';";

      $result = $DB->query($query);
      if ($result && $DB->numrows($result) == 0) {
         $query = "INSERT IGNORE INTO `glpi_plugin_mydashboard_preferenceuserblacklists` "
            . "VALUES (NULL,$user_id,'$plugin_name');";
         $DB->query($query);
      }
   }

   /**
    * Delete $plugin_name from $user_id 's black list
    * @param type $user_id
    * @param type $plugin_name
    */
   private function deleteItem($user_id, $plugin_name)
   {
      global $DB;
      $query = "DELETE FROM `glpi_plugin_mydashboard_preferenceuserblacklists` "
         . "WHERE (`users_id` = " . $user_id . " && `plugin_name` = '" . $plugin_name . "')";
      $DB->query($query);
   }

   /**
    * Get an array of plugin names that are blacklisted by user
    * @param int $user_id
    * @return array of string
    */
   function getBlacklistForUser($user_id)
   {
      global $DB;

      $query = "SELECT `plugin_name` "
         . "FROM `glpi_plugin_mydashboard_preferenceuserblacklists` "
         . "WHERE `users_id` = $user_id;";
      $result = $DB->query($query);

      $tab = array();
      while ($row = $DB->fetch_array($result)) {
         $tab[$row['plugin_name']] = $row['plugin_name'];
      }
      return $tab;
   }

   /**
    * Get the localized name for a plugin
    * @param string $plugin_name
    * @return string
    */
   private function getLocalName($plugin_name)
   {
      $infos = Plugin::getInfo($plugin_name);

      return isset($infos['name']) ? $infos['name'] : $plugin_name;
   }

   /**
    * Replacement of array_diff, normally faster
    * @param array $arrayFrom
    * @param array $arrayAgainst
    * @return array, $arrayFrom - $arrayAgainst
    */
   public static function arrayDiffEmulation($arrayFrom, $arrayAgainst)
   {
      $arrayAgainst = array_flip($arrayAgainst);

      foreach ($arrayFrom as $key => $value) {
         if (isset($arrayAgainst[$value])) {
            unset($arrayFrom[$key]);
         }
      }

      return $arrayFrom;
   }
}
