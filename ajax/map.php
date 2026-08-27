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

header("Content-Type: application/json; charset=UTF-8");
Html::header_nocache();

Session::checkRightsOr("plugin_mydashboard", [READ, CREATE + UPDATE]);

$result = [];
if (!isset($_POST['itemtype']) || !isset($_POST['params'])) {
    http_response_code(500);
    $result = [
        'success' => false,
        'message' => __('Required argument missing!'),
    ];
} else {
    $itemtype = $_POST['itemtype'];
    $params   = $_POST['params'];

    // Validate the client-supplied itemtype against an allow-list (consistent with dropdownType.php)
    // so it cannot flow unchecked into field-name construction or a future dynamic instantiation.
    $allowed_itemtypes = ['Ticket', 'Change', 'Problem'];
    if (!in_array($itemtype, $allowed_itemtypes, true)) {
        // Route the error through $result so it flows to the single json_encode() sink below
        // (a separate echo would be an extra tainted-HTML sink outside the shared response path).
        http_response_code(400);
        $result = ['success' => false, 'message' => __('Invalid itemtype')];
    } else {
        $data = Search::prepareDatasForSearch('Ticket', $params);
        Search::constructSQL($data);
        Search::constructData($data);

        $lat_field = $itemtype . '_998';
        $lng_field = $itemtype . '_999';
        $name_field = $itemtype . '_3';

        $rows   = $data['data']['rows'];
        $points = [];
        foreach ($rows as $row) {
            $idx = $row['raw']["ITEM_$lat_field"] . ',' . $row['raw']["ITEM_$lng_field"];

            if (isset($points[$idx])) {
                $points[$idx]['count'] += 1;
            } else {
                $points[$idx] = [
                    'lat'    => $row['raw']["ITEM_$lat_field"],
                    'lng'    => $row['raw']["ITEM_$lng_field"],
                    'title'  => $row['raw']["ITEM_$name_field"],
                    'loc_id' => $row['raw']['loc_id'],
                    'count'  => 1,
                ];
            }
        }
        $result['points'] = $points;
    }
}

// Escape HTML-significant characters so the DB-derived labels cannot be
// interpreted as HTML (XSS defense). Content-Type is already set to JSON above.
echo json_encode($result, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
