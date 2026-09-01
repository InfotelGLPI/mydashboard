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

use Glpixception\Http\AccessDeniedHttpException;
use Glpi\Search\SearchOption;

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
        // The plugin right alone says nothing about the right to read the searched
        // itemtype. The core search engine already fails closed, this closes the gap
        // one step earlier.
        if (!$itemtype::canView()) {
            throw new AccessDeniedHttpException();
        }

        $data = Search::prepareDatasForSearch($itemtype, $params);
        Search::constructSQL($data);
        Search::constructData($data);

        $lat_field = $itemtype . '_998';
        $lng_field = $itemtype . '_999';
        // Search option 3 is `priority` on a CommonITILObject, not the label the popup
        // expects, and it is not even part of the map-mode selection: the popup title was
        // always empty. Resolve the location option the same way the core search engine
        // does when it adds it to `toview` (SearchOption::getDefaultToView(), as_map case),
        // so the alias always matches a selected column.
        $loc_option = SearchOption::getOptionNumber($itemtype, 'completename', 'Location');
        $name_field = $itemtype . '_' . $loc_option;

        $rows   = $data['data']['rows'];
        $points = [];
        foreach ($rows as $row) {
            // Defensive ?? '': a missing alias must not raise a warning that would be
            // emitted before the JSON body (it would corrupt the response).
            $lat = $row['raw']["ITEM_$lat_field"] ?? '';
            $lng = $row['raw']["ITEM_$lng_field"] ?? '';
            $idx = $lat . ',' . $lng;

            if (isset($points[$idx])) {
                $points[$idx]['count'] += 1;
            } else {
                $points[$idx] = [
                    'lat'    => $lat,
                    'lng'    => $lng,
                    'title'  => $row['raw']["ITEM_$name_field"] ?? '',
                    'loc_id' => $row['raw']['loc_id'] ?? 0,
                    'count'  => 1,
                ];
            }
        }
        $result['points'] = $points;
    }
}

// The HEX flags keep this payload safe to inline in an HTML context. They do NOT
// protect the consumer: JSON.parse() restores the original characters, so the
// caller must escape `title` before building HTML out of it (see Reports_Map).
echo json_encode($result, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
