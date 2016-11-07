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
class PluginMydashboardConfig extends CommonDBTM
{
   /**
    * @param int $nb
    * @return translated
    */
   static function getTypeName($nb = 0)
   {
      return __('Dashboard - Configuration', 'mydashboard');
   }

   /**
    * @return bool
    */
   static function canUpdate()
   {
      return self::canCreate();
   }

   /**
    * @return bool
    */
   static function canCreate()
   {
      return Session::haveRightsOr("plugin_mydashboard_config", array(CREATE, UPDATE));
   }

   /**
    * @return bool
    */
   static function canView()
   {
      return Session::haveRight('plugin_mydashboard_config', READ);
   }


   /**
    * @param $ID
    * @param array $options
    */
   function showForm($ID, $options = array())
   {
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
      $options['candel'] = false;
      $options['colspan'] = 1;

      $this->showFormHeader($options);

      //canCreate means that user can update the configuration
//        $canCreate = self::canCreate();
      $canCreate = true;

      //This array is for those who can't update, it's to display the value of a boolean parameter
      $yesno = array(__("No"), __("Yes"));

      echo "<tr class='tab_bg_1'><td>" . __("Enable the possibility to display Dashboard in full screen", "mydashboard") . "</td>";
      echo "<td>";
      if ($canCreate) Dropdown::showYesNo("enable_fullscreen", $this->fields['enable_fullscreen']);
      else echo $yesno[$this->fields['enable_fullscreen']];
      echo "</td>";
      echo "</tr>";
      echo "<tr class='tab_bg_1'><td>" . __("Display the menu", "mydashboard") . "</td>";
      echo "<td>";
      if ($canCreate) Dropdown::showYesNo("display_menu", $this->fields['display_menu']);
      else echo $yesno[$this->fields['display_menu']];
      echo "</td>";
      echo "</tr>";

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
      if ($canCreate) {
         //interface will be by default 1 if the activeprofile interface is central, 0 if helpdesk
         //If user choosed to display for an other interface then there will be something in the post
         $interface = (isset($_POST['interface'])) ?
            $_POST['interface']
            : ($_SESSION['glpiactiveprofile']['interface'] == 'central') ? 1 : 0;
         echo "<div class='center plugin_mydashboard_interface_selector'>";
         echo "<form method='post' action='" . $this->getFormURL() . "' onsubmit='return true;'>";
         echo "<label>" . __('Dashboard', 'mydashboard') . " " . __("By Default for", "mydashboard") . " ";
         Dropdown::showFromArray('interface', array(
            0 => __('Simplified interface'),
            1 => __('Standard interface')
         ),
            array(
               'value' => $interface,
               'on_change' => 'this.form.submit()'
            ));
         echo "</label>";
         Html::closeForm();
         echo "</div>";
         $menu = new PluginMydashboardMenu();
         //0 is the default dashboard
         $menu->showMenu(0, $interface);
      }
   }

   /*
    * Initialize the original configuration
    */
   function initConfig()
   {
      global $DB;

      //We first check if there is no configuration
      $query = "SELECT * FROM `" . $this->getTable() . "` LIMIT 1";

      $result = $DB->query($query);
      if ($DB->numrows($result) == '0') {

         $input = array();
         $input['id'] = "1";
         $input['enable_fullscreen'] = "1";
         $input['display_menu'] = "1";
         $input['display_plugin_widget'] = "1";
         //Since 1.0.3 replace_central is now a preference
//         $input['replace_central'] = "0";

         $this->add($input);
      }
   }

   /*
    * Get the original config
    */
   function getConfig()
   {
      if ($this->getFromDB("1")) {

      } else {
         $this->initConfig();
         $this->getFromDB("1");
      }
   }

}