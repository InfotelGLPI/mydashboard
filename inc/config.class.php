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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * This class handles the general configuration of mydashboard
 *
 */
class PluginMydashboardConfig extends CommonDBTM {
   /**
    * @param int $nb
    *
    * @return translated
    */
   static function getTypeName($nb = 0) {
      return __('Dashboard - Configuration', 'mydashboard');
   }

   /**
    * @return bool
    */
   static function canUpdate() {
      return self::canCreate();
   }

   /**
    * @return bool
    */
   static function canCreate() {
      return Session::haveRightsOr("plugin_mydashboard_config", [CREATE, UPDATE]);
   }

   /**
    * @return bool
    */
   static function canView() {
      return Session::haveRight('plugin_mydashboard_config', READ);
   }


   /**
    * @param       $ID
    * @param array $options
    */
   function showForm($ID, $options = []) {
      global $CFG_GLPI;
      //If default configuration is not loaded
      if (!$this->getFromDB("1")) {
         $this->initConfig();
         //           $this->getFromDB("1");
      }

      //If user have no access
      //        if(!plugin_dashboard_haveRight('config', READ)){
      //            return false;
      //        }

      //The configuration is not deletable
      $options['candel']  = false;
      $options['colspan'] = 1;

      $this->showFormHeader($options);

      //canCreate means that user can update the configuration
      //        $canCreate = self::canCreate();
      $canCreate = true;

      //This array is for those who can't update, it's to display the value of a boolean parameter
      $yesno = [__("No"), __("Yes")];

      echo "<tr class='tab_bg_1'><td>" . __("Enable the possibility to display Dashboard in full screen", "mydashboard") . "</td>";
      echo "<td>";
      if ($canCreate) {
         Dropdown::showYesNo("enable_fullscreen", $this->fields['enable_fullscreen']);
      } else {
         echo $yesno[$this->fields['enable_fullscreen']];
      }
      echo "</td>";
      echo "</tr>";
      //      echo "<tr class='tab_bg_1'><td>" . __("Display the menu", "mydashboard") . "</td>";
      //      echo "<td>";
      //      if ($canCreate) Dropdown::showYesNo("display_menu", $this->fields['display_menu']);
      //      else echo $yesno[$this->fields['display_menu']];
      //      echo "</td>";
      //      echo "</tr>";

      echo "<tr class='tab_bg_1'><td>" . __("Display widgets from plugins", "mydashboard") . "</td>";
      echo "<td>";
      Dropdown::showYesNo("display_plugin_widget", $this->fields['display_plugin_widget']);
      echo "</td>";
      echo "</tr>";
      echo "</tr>";
      //Since 1.0.3 replace_central is now a preference
      //        echo "<tr class='tab_bg_1'><td>".__("Replace central interface","mydashboard")."</td>";
      //        echo "<td>";
      //        Dropdown::showYesNo("replace_central",$this->fields['replace_central']);
      //        echo "</td>";
      //        echo "</tr>";

      //echo "</table>";
      $this->showFormButtons($options);

      //Now updating the default dashboard
      //      echo "<br><div align='center'>";
      //      echo "<table class='tab_cadre_fixehov'>";
      //      echo "<tr class='tab_bg_2'>";
      //      echo "<td class='center'>";
      //      echo "<a href='" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/front/menu.php'>";
      //      echo __('Custom and save default grid', 'mydashboard');
      //      echo "</a>";
      //      echo "</td>";
      //      echo "<tr>";
      //      echo "</table></div>";
   }

   /*
    * Initialize the original configuration
    */
   function initConfig() {
      global $DB;

      //We first check if there is no configuration
      $query = "SELECT * FROM `" . $this->getTable() . "` LIMIT 1";

      $result = $DB->query($query);
      if ($DB->numrows($result) == '0') {

         $input                          = [];
         $input['id']                    = "1";
         $input['enable_fullscreen']     = "1";
         $input['display_menu']          = "1";
         $input['display_plugin_widget'] = "1";
         //Since 1.0.3 replace_central is now a preference
         //         $input['replace_central'] = "0";

         $this->add($input);
      }
   }

   /*
    * Get the original config
    */
   function getConfig() {
      if ($this->getFromDB("1")) {

      } else {
         $this->initConfig();
         $this->getFromDB("1");
      }
   }

}
