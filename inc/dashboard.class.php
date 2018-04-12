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

class PluginMydashboardDashboard extends CommonDBTM {

   //   static $rightname = 'plugin_servicecatalog_defaultview';

   static function getTypeName($nb = 0) {
      return __('Dashboard', 'mydashboard');
   }

   public static function checkIfPreferenceExists($options) {
      return self::checkPreferenceValue('id', $options);
   }

   public static function checkPreferenceValue($field, $options) {
      $data = getAllDatasFromTable(getTableForItemType(__CLASS__), "`users_id`='".$options["users_id"]."' AND `profiles_id`='".$options["profiles_id"]."'");
      if (!empty($data)) {
         $first = array_pop($data);
         return $first[$field];
      } else {
         return 0;
      }
   }
}
