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
 * Class PluginMydashboardGroupprofile
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginMydashboardGroupprofile extends CommonDBTM {

   static $rightname = 'plugin_mydashboard';
   var    $dohistory = true;

   /**
    * Add a category to profile
    * @global type $CFG_GLPI
    *
    * @param type  $profiles_id
    * @param type  $canedit
    */
   static function addGroup($profiles_id, $canedit) {
      global $CFG_GLPI;
      if ($canedit) {

         echo "<form method='post' action='" . $CFG_GLPI["root_doc"] . "/plugins/mydashboard/front/groupprofile.form.php" . "'>";
         echo "<input type='hidden' name='profiles_id' value='".$profiles_id."' >";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_1'><th colspan='4'>";
         echo __('Default dashboard group', 'mydashboard');
         echo "</th></tr>";
         $checked = '';
         $profilerights = new ProfileRight();
         if($profilerights->getFromDBByCrit(['profiles_id' => $profiles_id,
                                             'name'        => 'plugin_mydashboard_groupprofile'])){
            $checked = $profilerights->fields['rights'] ? 'checked' : '';
         }
         echo "<tr class='tab_bg_1'><td>";
         Html::showCheckbox(['name' => 'use_group_profile','checked' => $checked]);
         echo " " . __('Use profile group','mydashboard');
         echo "</td><td>";
         echo __('Default groups', 'mydashboard');
         echo "</td><td>";
         $groupprofile = new PluginMydashboardGroupprofile();
         $groups_id = [];
         if($groupprofile->getFromDBByCrit(['profiles_id' => $profiles_id])){
            $groups_id =  json_decode($groupprofile->fields['groups_id']);
         }
         //         Group::dropdown(['entity' => $_SESSION['glpiactive_entity'],
         //                          'name'   => 'groups_id',
         //                          'value'  => $groups_id]);

         $dbu    = new DbUtils();
         $result = $dbu->getAllDataFromTable(Group::getTable(), ['is_assign'=>1]);
         //         $pref = json_decode($groupprofile->fields['prefered_group']);

         $temp                         = [];
         foreach ($result as $item) {
            $temp[$item['id']] = $item['name'];
         }

         $params = [
            "name"                => 'groups_id',
            'entity'    => $_SESSION['glpiactive_entity'],
            "display"             => false,
            "multiple"            => true,
            "width"               => '200px',
            'values'              => isset($groups_id) ? $groups_id : [],
            'display_emptychoice' => true
         ];



         $dropdown = Dropdown::showFromArray("groups_id", $temp, $params);

         echo $dropdown;

         echo "</td></tr>";

         echo "<tr class='tab_bg_2'><td colspan='4' style='text-align:center'>";
         echo Html::submit(_sx('button', 'Save'), ['name' => 'addGroup']);
         echo "</td></tr>";

         echo "</table></div>";
         Html::closeForm();
      }
   }

   function getProfilGroup($profiles_id){
      $group = 0;
      $profilerights = new ProfileRight();
      if($profilerights->getFromDBByCrit(['profiles_id' => $profiles_id,
                                          'name'        => 'plugin_mydashboard_groupprofile'])){
         if($profilerights->fields['rights'] == 1){
            if($this->getFromDBByCrit(['profiles_id' => $profiles_id])){
               $group = $this->fields['groups_id'];
            }
            return $group;
         }
      }
      return false;
   }
}