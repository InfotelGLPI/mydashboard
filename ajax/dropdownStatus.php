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

if (strpos($_SERVER['PHP_SELF'], "dropdownStatus.php")) {
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

Session::checkRightsOr("plugin_mydashboard", [READ, CREATE + UPDATE]);

Global $DB;
// Make a select box
if (isset($_POST["itemtype"])) {

    $criteria = [
        'SELECT' => [\State::getTable().'.id', \State::getTable().'.name'],
        'FROM' => \State::getTable(),
        'LEFT JOIN' => [
            DropdownVisibility::getTable() => [
                'ON' => [
                    DropdownVisibility::getTable() => 'items_id',
                    \State::getTable() => 'id', [
                        'AND' => [
                            DropdownVisibility::getTable() . '.itemtype' => \State::getType(),
                        ],
                    ],
                ],
            ],
        ],
        'WHERE' => [
            DropdownVisibility::getTable() . '.itemtype' => \State::getType(),
            DropdownVisibility::getTable() . '.visible_itemtype' => strtolower($_POST["itemtype"]),
            DropdownVisibility::getTable() . '.is_visible' => 1,
        ],
    ];
    $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
            \State::getTable()
        );


   $states     = [];
    $iterator = $DB->request($criteria);
    foreach ($iterator as $data) {
      $states[$data['id']] = $data['name'];
   }
   Dropdown::showFromArray('states', $states, ['multiple' => true]);
}
