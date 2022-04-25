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
 * Class PluginMydashboardPreference
 */
class PluginMydashboardPreference extends CommonDBTM {

   /**
    * @return bool
    */
   static function canCreate() {
      return Session::haveRightsOr('plugin_mydashboard', [CREATE, UPDATE, READ]);
   }

   /**
    * @return bool
    */
   static function canView() {
      return Session::haveRightsOr('plugin_mydashboard', [CREATE, UPDATE, READ]);
   }

   /**
    * @return bool|booleen
    */
   static function canUpdate() {
      return self::canCreate();
   }


   /**
    * @param CommonGLPI $item
    * @param int        $withtemplate
    *
    * @return string|translated
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if ($item->getType() == 'Preference') {
         return __('My Dashboard', 'mydashboard');
      }
      return '';
   }

    /**
    * @return string
    */
   static function getIcon() {
      return PluginMydashboardMenu::getIcon();
   }
   
   
   /**
    * @param CommonGLPI $item
    * @param int        $tabnum
    * @param int        $withtemplate
    *
    * @return bool
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      $pref = new PluginMydashboardPreference();
      $pref->showPreferencesForm(Session::getLoginUserID());
      return true;
   }

   /**
    * @param $user_id
    */
   function showPreferencesForm($user_id) {
      //If user has no preferences yet, we set default values
      if (!$this->getFromDB($user_id)) {
         $this->initPreferences($user_id);
         $this->getFromDB($user_id);
      }

      //Preferences are not deletable
      $options['candel']  = false;
      $options['colspan'] = 1;

      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'><td>" . __("Automatic refreshing of the widgets that can be refreshed", "mydashboard") . "</td>";
      echo "<td>";
      Dropdown::showYesNo("automatic_refresh", $this->fields['automatic_refresh']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'><td>" . __("Refresh every ", "mydashboard") . "</td>";
      echo "<td>";
      Dropdown::showFromArray("automatic_refresh_delay", [1 => 1, 2 => 2, 5 => 5, 10 => 10, 30 => 30, 60 => 60],
                              ["value" => $this->fields['automatic_refresh_delay']]);
      echo " " . __('minute(s)', "mydashboard");
      echo "</td>";
      echo "</tr>";
      //Since 1.0.3 replace_central is now a preference
      echo "<tr class='tab_bg_1'><td>" . __("Replace central interface", "mydashboard") . "</td>";
      echo "<td>";
      Dropdown::showYesNo("replace_central", $this->fields['replace_central']);
      echo "</td>";
      echo "</tr>";

      if (Session::getCurrentInterface()
          && Session::getCurrentInterface() == 'central') {
         echo "<tr class='tab_bg_1'><td>" . __("My prefered groups for widget", "mydashboard") . "</td>";
         echo "<td>";
         $params = ['name'      => 'prefered_group',
                    'value'     => $this->fields['prefered_group'],
                    'entity'    => $_SESSION['glpiactiveentities'],
                    'condition' => '`is_assign`'];

         $dbu    = new DbUtils();
         $result = $dbu->getAllDataFromTable(Group::getTable(), ['is_assign' => 1]);
         $pref   = json_decode($this->fields['prefered_group']);

         //      $opt['technicians_groups_id'] = is_array($opt['technicians_groups_id']) ? $opt['technicians_groups_id'] : [$opt['technicians_groups_id']];
         $temp = [];
         foreach ($result as $item) {
            $temp[$item['id']] = $item['name'];
         }

         $params = [
            "name"                => 'prefered_group',
            'entity'              => $_SESSION['glpiactiveentities'],
            "display"             => false,
            "multiple"            => true,
            "width"               => '200px',
            'values'              => isset($pref) ? $pref : [],
            'display_emptychoice' => true
         ];

         $dropdown = Dropdown::showFromArray("prefered_group", $temp, $params);

         echo $dropdown;
         //      Group::dropdown($params);
         echo "</td>";
         echo "</tr>";
      }
      echo "<tr class='tab_bg_1'><td>" . __("My requester prefered groups for widget", "mydashboard") . "</td>";
      echo "<td>";
      $params = ['name'      => 'requester_prefered_group',
                 'value'     => $this->fields['requester_prefered_group'],
                 'entity'    => $_SESSION['glpiactiveentities'],
                 'condition' => '`is_requester`'];

      $dbu    = new DbUtils();
      $result = $dbu->getAllDataFromTable(Group::getTable(), ['is_requester' => 1]);
      $pref   = json_decode($this->fields['requester_prefered_group']);

      //      $opt['technicians_groups_id'] = is_array($opt['technicians_groups_id']) ? $opt['technicians_groups_id'] : [$opt['technicians_groups_id']];
      $temp = [];
      foreach ($result as $item) {
         $temp[$item['id']] = $item['name'];
      }

      $params = [
         "name"                => 'requester_prefered_group',
         'entity'              => $_SESSION['glpiactiveentities'],
         "display"             => false,
         "multiple"            => true,
         "width"               => '200px',
         'values'              => isset($pref) ? $pref : [],
         'display_emptychoice' => true
      ];

      $dropdown = Dropdown::showFromArray("requester_prefered_group", $temp, $params);

      echo $dropdown;
      //      Group::dropdown($params);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'><td>" . __("My prefered entity for widget", "mydashboard") . "</td>";
      echo "<td>";
      $params = ['name'   => 'prefered_entity',
                 'value'  => $this->fields['prefered_entity'],
                 'entity' => $_SESSION['glpiactiveentities']];
      Entity::dropdown($params);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'><td>" . __("My prefered type for widget", "mydashboard") . "</td>";
      echo "<td>";
      $params = ['value'  => $this->fields['prefered_type'],'toadd' =>[0 => Dropdown::EMPTY_VALUE]];
      Ticket::dropdownType('prefered_type', $params);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'><td>" . __("Palette color", "mydashboard") . "</td>";
      echo "<td>";
      $palette  = [1 => __("Palette color", "mydashboard") . ' 1',
                   2 => __("Palette color", "mydashboard") . ' 2'];
      $selected = $this->fields['color_palette'];
      Dropdown::showFromArray('color_palette',
                              $palette,
                              [
                                 'id'    => 'color_palette',
                                 'value' => $selected
                              ]);

      echo "</td>";
      echo "</tr>";

      $this->showFormButtons($options);

      if (PluginMydashboardHelper::getDisplayPlugins()) {
         $blacklist = new PluginMydashboardPreferenceUserBlacklist();
         $blacklist->showUserForm(Session::getLoginUserID());
      }
   }

   /**
    * @param $users_id
    */
   public function initPreferences($users_id) {

      $input                             = [];
      $input['id']                       = $users_id;
      $input['automatic_refresh']        = "0";
      $input['automatic_refresh_delay']  = "10";
      $input['nb_widgets_width']         = "3";
      $input['replace_central']          = "0";
      $input['prefered_group']           = "[]";
      $input['requester_prefered_group'] = "[]";
      $input['prefered_entity']          = "0";
      $input['color_palette']            = "1";
      $input['edit_mode']                = "0";
      $input['drag_mode']                = "0";
      $this->add($input);

   }

   public static function checkEditMode($users_id) {
      return self::checkPreferenceValue('edit_mode', $users_id);
   }

   public static function checkDragMode($users_id) {
      return self::checkPreferenceValue('drag_mode', $users_id);
   }

   public static function checkPreferenceValue($field, $users_id = 0) {
      $dbu  = new DbUtils();
      $data = $dbu->getAllDataFromTable($dbu->getTableForItemType(__CLASS__), ["id" => $users_id]);
      if (!empty($data)) {
         $first = array_pop($data);
         return $first[$field];
      } else {
         return 0;
      }
   }

   /**
    * @return mixed
    */
   public static function getPalette($users_id) {
      return self::checkPreferenceValue('color_palette', $users_id);
   }
}
