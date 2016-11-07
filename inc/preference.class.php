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
 * Class PluginMydashboardPreference
 */
class PluginMydashboardPreference extends CommonDBTM
{

   /**
    * @return bool
    */
   static function canCreate()
   {
      return Session::haveRightsOr('plugin_mydashboard', array(CREATE, UPDATE, READ));
   }

   /**
    * @return bool
    */
   static function canView()
   {
      return Session::haveRightsOr('plugin_mydashboard', array(CREATE, UPDATE, READ));
   }

   /**
    * @return bool|booleen
    */
   static function canUpdate()
   {
      return self::canCreate();
   }


   /**
    * @param CommonGLPI $item
    * @param int $withtemplate
    * @return string|translated
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
   {
      if ($item->getType() == 'Preference') {
         return __('My Dashboard', 'mydashboard');
      }
      return '';
   }

   /**
    * @param CommonGLPI $item
    * @param int $tabnum
    * @param int $withtemplate
    * @return bool
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
   {
      $pref = new PluginMydashboardPreference();
      $pref->showForm(Session::getLoginUserID());
      return true;
   }

   /**
    * @param $user_id
    */
   function showForm($user_id)
   {
      //If user has no preferences yet, we set default values
      if (!$this->getFromDB($user_id)) {
         $this->initPreferences($user_id);
         $this->getFromDB($user_id);
      }

      //Preferences are not deletable
      $options['candel'] = false;
      $options['colspan'] = 1;

      $this->showFormHeader($options);


      //This array is for those who can't update, it's to display the value of a boolean parameter
      $yesno = array(__("No"), __("Yes"));


      echo "<tr class='tab_bg_1'><td>" . __("Automatic refreshing of the widgets that can be refreshed", "mydashboard") . "</td>";
      echo "<td>";
      Dropdown::showYesNo("automatic_refresh", $this->fields['automatic_refresh']);
      echo "</td>";
      echo "</tr>";
      echo "<tr class='tab_bg_1'><td>" . __("Refresh every ", "mydashboard") . "</td>";
      echo "<td>";
      Dropdown::showFromArray("automatic_refresh_delay", array(1 => 1, 10 => 10, 30 => 30, 60 => 60), array("value" => $this->fields['automatic_refresh_delay']));
      echo " " . __('minute(s)', "mydashboard");
      echo "</td>";
      echo "<tr class='tab_bg_1'><td>" . __("Number of widget in width", "mydashboard") . "</td>";
      echo "<td>";
      Dropdown::showFromArray("nb_widgets_width", array(1 => 1, 2 => 2, 3 => 3, 4 => 4), array("value" => $this->fields['nb_widgets_width']));
      echo "</td>";
      echo "</tr>";
      //Since 1.0.3 replace_central is now a preference
      echo "<tr class='tab_bg_1'><td>" . __("Replace central interface", "mydashboard") . "</td>";
      echo "<td>";
      Dropdown::showYesNo("replace_central", $this->fields['replace_central']);
      echo "</td>";
      echo "</tr>";
      echo "</tr>";

      //echo "</table>";
      $this->showFormButtons($options);

      if (PluginMydashboardHelper::getDisplayPlugins()) {
         $blacklist = new PluginMydashboardPreferenceUserBlacklist();
         $blacklist->showForm(Session::getLoginUserID());
      }
   }

   /**
    * @param $users_id
    */
   public function initPreferences($users_id)
   {

      $input = array();
      $input['id'] = $users_id;
      $input['automatic_refresh'] = "0";
      $input['automatic_refresh_delay'] = "10";
      $input['nb_widgets_width'] = "3";
      $input['replace_central'] = "0";

      $this->add($input);

   }
}