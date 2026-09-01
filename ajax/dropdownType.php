<?php

/**
 * -------------------------------------------------------------------------
 * mydashboard plugin for GLPI
 * Copyright (C) 2016-2026 by the mydashboard Development Team.
 *
 * https://github.com/InfotelGLPI/mydashboard
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of mydashboard.
 *
 * mydashboard is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * mydashboard is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with mydashboard. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

if (strpos($_SERVER['PHP_SELF'], "dropdownType.php")) {
    header("Content-Type: text/html; charset=UTF-8");
    Html::header_nocache();
}

Session::checkRightsOr("plugin_mydashboard", [READ, CREATE + UPDATE]);

global $CFG_GLPI;
// Make a select box
// The itemtype drives a dynamic class name ($itemtype . "Type"), so it must be
// validated against the list the caller offers: StockWidget::showForm() builds the
// itemtype dropdown from $CFG_GLPI['state_types'] (asset itemtypes). The previous
// ['Ticket', 'Change', 'Problem'] allow-list was copied over from ajax/map.php, whose
// caller is a different one: since TicketType, ChangeType and ProblemType do not exist,
// it made this endpoint return an empty dropdown for every legitimate itemtype.
if (isset($_POST["itemtype"]) && in_array($_POST["itemtype"], $CFG_GLPI['state_types'], true)) {

    $itemtypeclass = $_POST["itemtype"] . "Type";
    if ($item = getItemForItemtype($itemtypeclass)) {
        $types     = [];
        $alltypes      = $item->find();
        foreach ($alltypes as $k => $v) {
            $types[$v['id']] = $v['name'];
        }
        Dropdown::showFromArray('types', $types, ['multiple' => true]);
    }
}
