<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Metademands plugin for GLPI
 Copyright (C) 2018-2019 by the Metademands Development Team.

 https://github.com/InfotelGLPI/metademands
 -------------------------------------------------------------------------

 LICENSE

 This file is part of Metademands.

 Metademands is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Metademands is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Metademands. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginMydashboardCustomswidgets
 */
class PluginMydashboardCustomswidget extends CommonDropdown {

   /**
    * @param int $nb
    *
    * @return translated
    * @override
    */
   static function getTypeName($nb = 0) {

      return __('Custom Widgets', 'mydashboard');
   }

   /**
    * Display tab for each customwidget
    * @override
    */
   function defineTabs($options = []) {
      $ong = [];

      $this->addDefaultFormTab($ong);
      $this->addStandardTab('PluginMydashboardHTMLEditor', $ong, $options);
      return $ong;
   }

   /**
    * @return array
    * @throws \GlpitestSQLError
    */
   static function listCustomsWidgets() {

      $customsWidgets = [];

      global $DB;

      $query = "SELECT * from " . PluginMydashboardCustomswidget::getTable();

      $result = $DB->query($query);

      while ($data = $DB->fetchAssoc($result)) {
         $customsWidgets[] = $data;
      }

      return $customsWidgets;
   }

   /**
    * @param $id
    *
    * @return bool
    * @throws \GlpitestSQLError
    */
   static function checkCustomWidgetExist($id) {
      global $DB;

      $query = "SELECT count(*) as count from " . PluginMydashboardCustomswidget::getTable();
      $query .= " WHERE id=" . $id;

      $result = $DB->query($query);

      $data2 = $DB->fetchArray($result);
      return $data2['count'] > 0;
   }

   /**
    * @param $id
    *
    * @return string[]|null
    * @throws \GlpitestSQLError
    */
   static private function getCustomWidgetById($id) {
      global $DB;

      $query = "SELECT * from " . PluginMydashboardCustomswidget::getTable();
      $query .= " WHERE id=" . $id;

      $result = $DB->query($query);

      while ($data = $DB->fetchAssoc($result)) {
         return $data;
      }
      return null;
   }

   /**
    * @param $id
    *
    * @return string[]|null
    * @throws \GlpitestSQLError
    */
   static function getCustomWidget($id) {
      $temp = self::getCustomWidgetById($id);

      return $temp;
   }
}
