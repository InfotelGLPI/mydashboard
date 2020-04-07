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

class PluginMydashboardStockWidget extends CommonDBTM {

   static $rightname = "plugin_mydashboard_stockwidget";

   /**
    * @param int $nb
    *
    * @return translated
    */
   static function getTypeName($nb = 0) {

      return _n('Stock widget', 'Stock widgets', $nb, 'mydashboard');
   }

   function post_getEmpty() {
      $this->fields['alarm_threshold'] = 5;
   }

   function prepareInputForAdd($input) {

      $input = parent::prepareInputForAdd($input);

      if (!$input["itemtype"]) {
         Session::addMessageAfterRedirect(__("Cannot create alert without a type", "mydashboard"), false, ERROR);
         return false;
      }

      if (isset($input["states"])) {
         $states = [];
         foreach ($input['states'] as $k => $v) {
            $states[$v] = $v;
         }
         $input['states'] = json_encode($states);
      }

      if (isset($input["types"])) {
         $types = [];
         foreach ($input['types'] as $k => $v) {
            $types[$v] = $v;
         }
         $input['types'] = json_encode($types);
      }

      return $input;
   }

   function prepareInputForUpdate($input) {

      if (isset($input["states"])) {
         $states = [];
         foreach ($input['states'] as $k => $v) {
            $states[$v] = $v;
         }
         $input['states'] = json_encode($states);
      }
      if (isset($input["types"])) {
         $types = [];
         foreach ($input['types'] as $k => $v) {
            $types[$v] = $v;
         }
         $input['types'] = json_encode($types);
      }
      return $input;
   }

   function showForm($ID, $options = []) {
      global $CFG_GLPI;

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      if (!isset($options['item']) || empty($options['item'])) {
         $options['item'] = $this->fields["itemtype"];
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Name') . "</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";

      echo "<td>" . __("Item type");
      // Mandatory dropdown :
      if ($ID <= 0) {
         echo " <span class='red'>*</span>";
      }
      echo "</td>";
      $rand = mt_rand();
      echo "<td>";
      if ($ID > 0) {
         $itemtype = $this->fields["itemtype"];
         $item     = new $itemtype();
         echo $item->getTypeName();
         echo Html::hidden('itemtype', ['value' => $itemtype]);
      } else {

         Dropdown::showItemTypes('itemtype', $CFG_GLPI['state_types'], ['value' => $this->fields["itemtype"],
                                                                        'rand'  => $rand]);
         $params = [
            'itemtype'  => '__VALUE__',
            'fieldname' => 'types',
         ];
         Ajax::updateItemOnSelectEvent("dropdown_itemtype$rand", "show_types$rand",
                                       "../ajax/dropdownType.php",
                                       $params);

         Ajax::updateItemOnSelectEvent("dropdown_itemtype$rand", "show_statuses$rand",
                                       "../ajax/dropdownStatus.php",
                                       $params);
      }
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";

      echo "<td>" . __("Type") . "</td>";
      echo "<td>";
      echo "<span id='show_types$rand'>";
      if ($options['item']) {
         $itemtypeclass = $options['item'] . "Type";
         if ($item = getItemForItemtype($itemtypeclass)) {
            $types    = [];
            $alltypes = $item->find();
            foreach ($alltypes as $k => $v) {
               $types[$v['id']] = $v['name'];
            }
            $values = [];
            $stypes = [];
            if ($ID > 0) {
               $values = json_decode($this->fields['types'], true);
               if (is_array($values) && count($values) > 0) {
                  foreach ($values as $k => $v) {
                     $stypes[] = $k;
                  }
               }
            }
            Dropdown::showFromArray('types', $types, ['multiple' => true, 'values' => $stypes]);
         }
      }
      echo "</span>";
      echo "</td>";
      echo "<td>" . _n('Status', 'Statuses', 2) . "</td>";
      echo "<td>";
      echo "<span id='show_statuses$rand'>";
      if ($options['item']) {
         $state     = new State();
         $dbu       = new DbUtils();
         $states    = [];
         $field     = 'is_visible_' . strtolower($options['item']);
         $condition = [$field => 1]
                      + $dbu->getEntitiesRestrictCriteria('glpi_states', 'entities_id', $this->fields['entities_id'], true);
         $allstates = $state->find($condition);
         foreach ($allstates as $k => $v) {
            $states[$v['id']] = $v['name'];
         }
         $values  = [];
         $svalues = [];
         if ($ID > 0) {
            $values = json_decode($this->fields['states'], true);
            if (is_array($values) && count($values) > 0) {
               foreach ($values as $k => $v) {
                  $svalues[] = $k;
               }
            }
         }
         Dropdown::showFromArray('states', $states, ['multiple' => true, 'values' => $svalues]);
      }
      echo "</span>";
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Icon') . "</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "icon");
      if (isset($this->fields['icon'])
          && !empty($this->fields['icon'])) {
         $icon = $this->fields['icon'];
         echo "<br><br><i class='fas-sc sc-fa-color $icon fa-3x' ></i>";
      }
      echo "</td>";

      echo "<td>" . __('Alert threshold') . "</td>";
      echo "<td>";
      Dropdown::showNumber('alarm_threshold', ['value' => $this->fields["alarm_threshold"],
                                               'min'   => 1,
                                               'max'   => 100,
                                               'step'  => 1]);
      echo "</td>";
      echo "</tr>";

      $this->showFormButtons($options);
   }
}
