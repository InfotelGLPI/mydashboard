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
 * Class PluginMydashboardDashboard
 */
class PluginMydashboardDashboard extends CommonDBTM {

   public static $GLPI_VIEW                = 1;
   public static $INVENTORY_VIEW           = 2;
   public static $HELPDESK_SUPERVISOR_VIEW = 3;
   public static $INCIDENT_SUPERVISOR_VIEW = 4;
   public static $REQUEST_SUPERVISOR_VIEW  = 5;
   public static $HELPDESK_TECHNICIAN_VIEW = 6;

   /**
    * Return the localized name of the current Type
    * Should be overloaded in each new class
    *
    * @param integer $nb Number of items
    *
    * @return string
    **/
   static function getTypeName($nb = 0) {
      return __('Dashboard', 'mydashboard');
   }

   /**
    * @param $options
    *
    * @return int
    */
   public static function checkIfPreferenceExists($options) {
      return self::checkPreferenceValue('id', $options);
   }

   /**
    * @param $field
    * @param $options
    *
    * @return int
    */
   public static function checkPreferenceValue($field, $options) {
      $data = getAllDatasFromTable(getTableForItemType(__CLASS__), "`users_id`='" . $options["users_id"] . "' AND `profiles_id`='" . $options["profiles_id"] . "'");
      if (!empty($data)) {
         $first = array_pop($data);
         return $first[$field];
      } else {
         return 0;
      }
   }

   /**
    * @param array|\datas $input
    *
    * @return array|\datas|\the
    */
   function prepareInputForAdd($input) {
      return self::prepareInputForUpdate($input);
   }

   /**
    * @param array|\datas $input
    *
    * @return array|\datas|\the
    */
   function prepareInputForUpdate($input) {

      //remove duplicate widgets
      $ID_check = [];

      $datagrid = json_decode($input['grid'], true);

      foreach ($datagrid as $key => $data) {
         //check if widget already present
         if (in_array($data['id'], $ID_check)) {
            //widget delete
            unset($datagrid[$key]);
         } else {
            $ID_check[$data['id']] = $data['id'];
         }
      }
      $input['grid'] = json_encode($datagrid);

      return $input;
   }

   static function getPredefinedDashboardName() {

      $elements = [self::$GLPI_VIEW                => __('GLPI admin grid', 'mydashboard'),
                   self::$INVENTORY_VIEW           => __('Inventory admin grid', 'mydashboard'),
                   self::$HELPDESK_SUPERVISOR_VIEW => __('Helpdesk supervisor grid', 'mydashboard'),
                   self::$INCIDENT_SUPERVISOR_VIEW => __('Incident supervisor grid', 'mydashboard'),
                   self::$REQUEST_SUPERVISOR_VIEW  => __('Request supervisor grid', 'mydashboard'),
                   self::$HELPDESK_TECHNICIAN_VIEW => __('Helpdesk technician grid', 'mydashboard')];

      return $elements;
   }


   static function loadPredefinedDashboard($id) {

      $data = '';
      if ($id == self::$GLPI_VIEW) {
         $data = '[{"id":"gs199","x":0,"y":0,"width":4,"height":8},
         {"id":"gs200","x":4,"y":0,"width":4,"height":8},
         {"id":"gs197","x":8,"y":0,"width":4,"height":8},
         {"id":"gs5","x":0,"y":8,"width":4,"height":8},
         {"id":"gs40","x":4,"y":8,"width":4,"height":8}]';
      }
      if ($id == self::$INVENTORY_VIEW) {
         $plugin   = new Plugin();
         $data_ocs = '';
         if ($plugin->isActivated("ocsinventoryng")) {

            $gs4 = PluginMydashboardWidget::getGsID("PluginOcsinventoryngDashboard1");
            $gs5 = PluginMydashboardWidget::getGsID("PluginOcsinventoryngDashboard2");
            $data_ocs = '{"id":"'.$gs4.'","x":0,"y":9,"width":5,"height":12},
                        {"id":"'.$gs5.'","x":5,"y":9,"width":5,"height":12}';
         }

         $gs1 = PluginMydashboardWidget::getGsID("PluginMydashboardInfotel5");
         $gs2 = PluginMydashboardWidget::getGsID("contractwidget");
         $gs3 = PluginMydashboardWidget::getGsID("PluginMydashboardInfotel3");
         $data = '[{"id":"'.$gs1.'","x":0,"y":0,"width":4,"height":9},
         {"id":"'.$gs2.'","x":4,"y":0,"width":4,"height":9},
         {"id":"'.$gs3.'","x":8,"y":0,"width":4,"height":8},';
         $data .= $data_ocs;
         $data .= ']';
      }
      if ($id == self::$HELPDESK_SUPERVISOR_VIEW) {
         $data = '[{"id":"gs195","x":0,"y":0,"width":4,"height":8},
         {"id":"gs194","x":0,"y":8,"width":4,"height":11},
         {"id":"gs38","x":0,"y":19,"width":4,"height":12},
         {"id":"gs192","x":0,"y":31,"width":5,"height":12},
         {"id":"gs196","x":4,"y":0,"width":5,"height":8},
         {"id":"gs110","x":4,"y":8,"width":4,"height":11},
         {"id":"gs203","x":4,"y":19,"width":4,"height":12},
         {"id":"gs150","x":5,"y":31,"width":3,"height":11},
         {"id":"gs183","x":9,"y":0,"width":3,"height":8},
         {"id":"gs182","x":8,"y":8,"width":4,"height":11},
         {"id":"gs138","x":8,"y":19,"width":4,"height":12},
         {"id":"gs169","x":8,"y":31,"width":4,"height":12}]';
      }
      if ($id == self::$INCIDENT_SUPERVISOR_VIEW) {
         $data = '[{"id":"gs154","x":0,"y":8,"width":4,"height":11},
         {"id":"gs196","x":8,"y":0,"width":4,"height":8},
         {"id":"gs183","x":9,"y":19,"width":3,"height":8},
         {"id":"gs182","x":9,"y":8,"width":3,"height":11},
         {"id":"gs195","x":0,"y":0,"width":4,"height":8},
         {"id":"gs38","x":4,"y":8,"width":5,"height":12},
         {"id":"gs198","x":4,"y":0,"width":4,"height":8}]';
      }
      if ($id == self::$REQUEST_SUPERVISOR_VIEW) {
         $data = '[{"id":"gs198","x":4,"y":0,"width":5,"height":11},
         {"id":"gs157","x":0,"y":0,"width":4,"height":12},
         {"id":"gs38","x":4,"y":11,"width":5,"height":12},
         {"id":"gs182","x":9,"y":0,"width":3,"height":11},
         {"id":"gs183","x":9,"y":11,"width":3,"height":8}]';
      }
      if ($id == self::$HELPDESK_TECHNICIAN_VIEW) {
         $data = '[{"id":"gs195","x":0,"y":0,"width":4,"height":8},
         {"id":"gs198","x":4,"y":0,"width":4,"height":8},
         {"id":"gs196","x":8,"y":0,"width":4,"height":8},
         {"id":"gs188","x":0,"y":8,"width":4,"height":9},
         {"id":"gs16","x":4,"y":17,"width":4,"height":9},
         {"id":"gs182","x":8,"y":8,"width":4,"height":11},
         {"id":"gs14","x":4,"y":8,"width":4,"height":9},
         {"id":"gs189","x":0,"y":17,"width":4,"height":9},
         {"id":"gs183","x":8,"y":19,"width":4,"height":8}]';
      }

      return $data;
   }
}
