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
      $this->getFromDB("1");

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
      $rand      = mt_rand();

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

      echo "<tr class='tab_bg_1'><td>" . __("Display widgets from plugins", "mydashboard") . "</td>";
      echo "<td>";
      Dropdown::showYesNo("display_plugin_widget", $this->fields['display_plugin_widget']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'><td>" . __("Replace central interface", "mydashboard") . "</td>";
      echo "<td>";
      Dropdown::showYesNo("replace_central", $this->fields['replace_central']);
      echo "</td>";
      echo "</tr>";
      
      echo "<tr class='tab_bg_1'><td>" . __("Google API Key", "mydashboard") . "</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "google_api_key");
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('Impact colors for alerts','mydashboard') . "</td>";
      echo "<td colspan='3'>";

      echo "<table><tr>";
      echo "<td><label for='dropdown_priority_1$rand'>1</label>&nbsp;";
      Html::showColorField('impact_1', ['value' => $this->fields["impact_1"], 'rand' => $rand]);
      echo "</td>";
      echo "<td><label for='dropdown_priority_2$rand'>2</label>&nbsp;";
      Html::showColorField('impact_2', ['value' => $this->fields["impact_2"], 'rand' => $rand]);
      echo "</td>";
      echo "<td><label for='dropdown_priority_3$rand'>3</label>&nbsp;";
      Html::showColorField('impact_3', ['value' => $this->fields["impact_3"], 'rand' => $rand]);
      echo "</td>";
      echo "<td><label for='dropdown_priority_4$rand'>4</label>&nbsp;";
      Html::showColorField('impact_4', ['value' => $this->fields["impact_4"], 'rand' => $rand]);
      echo "</td>";
      echo "<td><label for='dropdown_priority_5$rand'>5</label>&nbsp;";
      Html::showColorField('impact_5', ['value' => $this->fields["impact_5"], 'rand' => $rand]);
      echo "</td>";
      echo "</tr></table>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Level of categories to show', 'mydashboard') . "</td>";
      echo "<td>";
      $itilCat = new ITILCategory();
      $itilCategories = $itilCat->find();
      $levelsCat = [];
      foreach ($itilCategories as $categorie){
         $levelsCat[$categorie['level']] = $categorie['level'];
      }
      ksort($levelsCat);
      Dropdown::showFromArray('levelCat', $levelsCat, ['value' => $this->fields["levelCat"]]);
      echo "</td>";
      echo "</tr>";

      echo "</td></tr>";

      $this->showFormButtons($options);
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
         $input['replace_central']       = "1";
         $input['google_api_key']        = "";
         $this->add($input);
      }
   }

   /*
    * Get the original config
    */
   function getConfig() {
      if (!$this->getFromDB("1")) {
         $this->initConfig();
         $this->getFromDB("1");
      }
   }

}
