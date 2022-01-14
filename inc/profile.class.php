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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * This class handles the access rights on Dashboard by glpi_profiles
 * Administration->Profiles-> .. Dashboard
 */
class PluginMydashboardProfile extends CommonDBTM {

   static $rightname = "profile";

   /**
    * @param int $nb
    *
    * @return translated
    */
   static function getTypeName($nb = 0) {
      return __('Rights management', 'mydashboard');
   }

   /**
    * @param CommonGLPI $item
    * @param int        $withtemplate
    *
    * @return string|translated
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if ($item->getType() == 'Profile') {
         return PluginMydashboardMenu::getTypeName(2);
      }
      return '';
   }


   /**
    * @param CommonGLPI $item
    * @param int        $tabnum
    * @param int        $withtemplate
    *
    * @return bool
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if ($item->getType() == 'Profile') {
         $ID   = $item->getID();
         $prof = new self();
         //85
         self::addDefaultProfileInfos($ID,
                                      ['plugin_mydashboard'             => 0,
                                       'plugin_mydashboard_config'      => 0,
                                       'plugin_mydashboard_edit'        => 0,
                                       'plugin_mydashboard_stockwidget' => 0]);
         $prof->showForm($ID);
      }
      return true;
   }

   /**
    * @param      $profiles_id
    * @param      $rights
    * @param bool $drop_existing
    *
    * @internal param $profile 85* 85
    */
   static function addDefaultProfileInfos($profiles_id, $rights, $drop_existing = false) {

      $profileRight = new ProfileRight();
      $dbu          = new DbUtils();
      foreach ($rights as $right => $value) {
         if ($dbu->countElementsInTable('glpi_profilerights',
                                        ["profiles_id" => $profiles_id, "name" => $right]) && $drop_existing) {
            $profileRight->deleteByCriteria(['profiles_id' => $profiles_id, 'name' => $right]);
         }
         if (!$dbu->countElementsInTable('glpi_profilerights',
                                         ["profiles_id" => $profiles_id, "name" => $right])) {
            $myright['profiles_id'] = $profiles_id;
            $myright['name']        = $right;
            $myright['rights']      = $value;
            $profileRight->add($myright);
            //Add right to the current session
            //            $_SESSION['glpiactiveprofile'][$right] = $value;
         }
      }
   }


   /**
    * @param $ID
    */
   static function createFirstAccess($ID) {
      //85
      self::addDefaultProfileInfos($ID,
                                   ['plugin_mydashboard'             => 6,
                                    'plugin_mydashboard_config'      => CREATE + UPDATE + PURGE,
                                    'plugin_mydashboard_edit'        => 6,
                                    'plugin_mydashboard_stockwidget' => READ + CREATE + UPDATE + PURGE], true);
   }

   //profiles modification

   /**
    * @param       $ID
    * @param array $options
    */
   function showForm($profiles_id = 0, $openform = TRUE, $closeform = TRUE) {
      //85
      $profile = new Profile();
      $profile->getFromDB($profiles_id);
      if ($canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE])) {
         echo "<form method='post' action='" . $profile->getFormURL() . "'>";
      }

      $effective_rights = ProfileRight::getProfileRights($profiles_id, ['plugin_mydashboard_stockwidget', 'plugin_mydashboard', 'plugin_mydashboard_config', 'plugin_mydashboard_edit']);

      //      Toolbox::logDebug($effective_rights);
      echo "<table class='tab_cadre_fixehov'>";
      echo "<tr class='tab_bg_2'>";
      echo "<th colspan='4' class='center b'>" . sprintf(__('%1$s - %2$s'), self::getTypeName(1), $profile->fields["name"]) . "</th>";
      echo "</tr>";
      echo "<tr class='tab_bg_1'><td></td><td>" . __("Full", "mydashboard") . "</td><td>" . __("Custom", "mydashboard") . "</td></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __("Dashboard Access", "mydashboard") . "</td><td>";
      $checked = ($effective_rights["plugin_mydashboard"] > 1) ? 1 : 0;
      Html::showCheckbox(['name'    => '_plugin_mydashboard[6_0]',
                          'checked' => $checked]);
      echo "</td>";
      echo "<td>";
      $checked = ($effective_rights["plugin_mydashboard"] == 1) ? 1 : 0;
      Html::showCheckbox(['name'    => '_plugin_mydashboard[1_0]',
                          'checked' => $checked]);
      echo "</td>";
      echo "</tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __("Configuration Access", "mydashboard") . "</td><td>";
      //      Profile::dropdownNoneReadWrite("_plugin_mydashboard_config",$effective_rights["plugin_mydashboard_config"],1,1,1);
      Html::showCheckbox(['name'    => '_plugin_mydashboard_config[22_0]',
                          'checked' => $effective_rights["plugin_mydashboard_config"]]);
      echo "</td>";
      echo "<td></td>";
      echo "</tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __("See edit mode", "mydashboard") . "</td><td>";
      //      Profile::dropdownNoneReadWrite("_plugin_mydashboard_config",$effective_rights["plugin_mydashboard_config"],1,1,1);
      Html::showCheckbox(['name'    => '_plugin_mydashboard_edit[6_0]',
                          'checked' => $effective_rights["plugin_mydashboard_edit"]]);
      echo "</td>";
      echo "<td></td>";
      echo "</tr>";

      $rights = $this->getAllRights();
      $profile->displayRightsChoiceMatrix($rights, ['canedit'       => $canedit,
                                                    'default_class' => 'tab_bg_2',
                                                    'title'         => __('Setup stock widget', 'mydashboard')]);

      if ($canedit
          && $closeform) {
         echo "<div class='center'>";
         echo Html::hidden('id', ['value' => $profiles_id]);
         echo Html::submit(_sx('button', 'Save'), ['name' => 'update', 'class' => 'btn btn-primary']);
         echo "</div>\n";
         Html::closeForm();
      }

      PluginMydashboardGroupprofile::addGroup($profiles_id, $canedit);

      PluginMydashboardMenu::installWidgets();

      if ($effective_rights["plugin_mydashboard"] == READ) {
         $authorizedform = new PluginMydashboardProfileAuthorizedWidget();
         $authorizedform->showForm($profiles_id, ['interface' => $profile->fields["interface"]]);
      }
   }


   /**
    * Initialize profiles
    */
   static function initProfile() {
      global $DB, $GLPI_CACHE;
      $profile = new self();
      $dbu     = new DbUtils();
      //Add new rights in glpi_profilerights table
      foreach ($profile->getAllRights(true) as $data) {
         if ($dbu->countElementsInTable("glpi_profilerights",
                                        ["name" => $data['field']]) == 0) {
            ProfileRight::addProfileRights([$data['field']]);
         }
      }

      foreach ($DB->request("SELECT *
                           FROM `glpi_profilerights` 
                           WHERE `profiles_id`='" . $_SESSION['glpiactiveprofile']['id'] . "' 
                           AND `name` LIKE '%plugin_mydashboard%'") as $prof) {
         $_SESSION['glpiactiveprofile'][$prof['name']] = $prof['rights'];
      }

      // When user connects or change profile he goes (when Mydashboard is configured) to the menu
      $pref = PluginMydashboardHelper::getReplaceCentral();
      if ($pref
          && Session::haveRightsOr("plugin_mydashboard", [CREATE, READ])
          && !isset($_SESSION["glpi_plugin_mydashboard_activating"])) {
         $_SESSION["glpi_plugin_mydashboard_loaded"] = 0;
      } else {
         unset($_SESSION["glpi_plugin_mydashboard_loaded"]);
         unset($_SESSION["glpi_plugin_mydashboard_activating"]);
      }
      unset($_SESSION["glpi_plugin_mydashboard_allwidgets"]);
//      $widgetclasse = new PluginMydashboardWidget();
//      $ckey         = 'md_cache_' . md5($widgetclasse->getTable()).Session::getLoginUserID();
//      $GLPI_CACHE->delete($ckey);
   }

   /**
    * @param bool $all
    *
    * @return array
    */
   static function getAllRights($all = false) {

      $rights = [];

      $rights[] = ['itemtype' => 'PluginMydashboardStockWidget',
                   'label'    => __('Setup stock widget', 'mydashboard'),
                   'field'    => 'plugin_mydashboard_stockwidget'];
      if ($all) {

         $rights[] = ['itemtype' => 'PluginMydashboardMenu',
                      'label'    => __('See the dashboard', 'mydashboard'),
                      'field'    => 'plugin_mydashboard'];

         $rights[] = ['itemtype' => 'PluginMydashboardConfig',
                      'label'    => __('See the configuration', 'mydashboard'),
                      'field'    => 'plugin_mydashboard_config'];

         $rights[] = ['itemtype' => 'PluginMydashboardMenu',
                      'label'    => __('See edit mode', 'mydashboard'),
                      'field'    => 'plugin_mydashboard_edit'];

      }

      return $rights;
   }

   static function removeRightsFromSession() {
      foreach (self::getAllRights(true) as $right) {
         if (isset($_SESSION['glpiactiveprofile'][$right['field']])) {
            unset($_SESSION['glpiactiveprofile'][$right['field']]);
         }
      }
   }

   /**
    * Init profiles
    *
    * @param $old_right
    *
    * @return int
    */

   static function translateARight($old_right) {
      switch ($old_right) {
         case '':
            return 0;
         case 'r' :
            return READ;
         case 'w':
            return CREATE + UPDATE;
         case '0':
         case '1':
            return $old_right;

         default :
            return 0;
      }
   }

   /**
    * @param $profiles_id the profile ID
    *
    * @return bool
    * @since 0.85
    * Migration rights from old system to the new one for one profile
    *
    */
   private static function migrateOneProfile($profiles_id) {
      global $DB;
      //Cannot launch migration if there's nothing to migrate...
      if (!$DB->tableExists('glpi_plugin_mydashboard_profiles')) {
         return true;
      }

      foreach ($DB->request('glpi_plugin_mydashboard_profiles',
                            "`profiles_id`='$profiles_id'") as $profile_data) {

         $matching       = ['mydashboard' => 'plugin_mydashboard',
                            'config'      => 'plugin_mydashboard_config'];
         $current_rights = ProfileRight::getProfileRights($profiles_id, array_values($matching));
         foreach ($matching as $old => $new) {
            if (!isset($current_rights[$old])) {
               $query = "UPDATE `glpi_profilerights` 
                         SET `rights`='" . self::translateARight($profile_data[$old]) . "' 
                         WHERE `name`='$new' AND `profiles_id`='$profiles_id'";
               $DB->query($query);
            }
         }
      }
   }

   /**
    * @since 0.85
    * Migration rights from old system to new one for all profiles
    */
   public static function migrateRightsFrom84To85() {
      global $DB;
      //Migration old rights in new ones
      foreach ($DB->request("SELECT `id` FROM `glpi_profiles`") as $prof) {
         self::migrateOneProfile($prof['id']);
      }
   }
}
