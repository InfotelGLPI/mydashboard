<?php

/*
 -------------------------------------------------------------------------
 mydashboard plugin for GLPI
 Copyright (C) 2016-2026 by the mydashboard Development Team.

 https://github.com/InfotelGLPI/mydashboard
 -------------------------------------------------------------------------

 LICENSE

 This file is part of mydashboard.

 mydashboard is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 mydashboard is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with mydashboard. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

if (strpos($_SERVER['PHP_SELF'], "dropdownType.php")) {
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

Session::checkRightsOr("plugin_mydashboard", [READ, CREATE + UPDATE]);

// Make a select box
$allowed_itemtypes = ['Ticket', 'Change', 'Problem'];
if (isset($_POST["itemtype"]) && in_array($_POST["itemtype"], $allowed_itemtypes, true)) {

   $itemtypeclass = $_POST["itemtype"]."Type";
   if ($item = getItemForItemtype($itemtypeclass)) {
      $types     = [];
      $alltypes      = $item->find();
      foreach ($alltypes as $k => $v) {
         $types[$v['id']] = $v['name'];
      }
      Dropdown::showFromArray('types', $types, ['multiple' => true]);
   }
}
