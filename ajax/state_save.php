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

use GlpiPlugin\Mydashboard\Dashboard;
use GlpiPlugin\Mydashboard\Preference;
use GlpiPlugin\Mydashboard\Widget;

Session::checkRightsOr("plugin_mydashboard", [READ, CREATE + UPDATE]);

$result      = [];
$grids_saved = [];
$arrayFinal  = [];
if (!isset($_POST)) {
    $result = [
        'success' => false,
        'message' => __('Required argument missing!'),
    ];
} elseif (!empty($_POST)) {
    $gsIdName = $_POST['gsId'];
    unset($_POST['gsId']);

    // Whitelist the DataTables state before persisting it: keep only the keys the
    // table state is expected to carry, coerced to strict types, and drop anything
    // else a client might inject. This mirrors the grid normalization already applied
    // in saveGrid.php so the grid_statesave column cannot store an arbitrary client
    // payload verbatim (defense in depth against a future consumer that would render
    // this state as HTML). profiles_id, a client-side routing hint, is intentionally
    // not part of the persisted state.
    $raw_state   = $_POST;
    $to_bool     = static function ($value): bool {
        return $value === true || $value === 1 || $value === '1' || $value === 'true';
    };
    $safe_search = static function ($search) use ($to_bool): array {
        $search = is_array($search) ? $search : [];
        return [
            'search'          => (string) ($search['search'] ?? ''),
            'smart'           => $to_bool($search['smart'] ?? true),
            'regex'           => $to_bool($search['regex'] ?? false),
            'caseInsensitive' => $to_bool($search['caseInsensitive'] ?? true),
        ];
    };
    $safe_state = [];
    if (isset($raw_state['time'])) {
        $safe_state['time'] = (int) $raw_state['time'];
    }
    if (isset($raw_state['start'])) {
        $safe_state['start'] = (int) $raw_state['start'];
    }
    if (isset($raw_state['length'])) {
        $safe_state['length'] = (int) $raw_state['length'];
    }
    if (isset($raw_state['order']) && is_array($raw_state['order'])) {
        $safe_state['order'] = [];
        foreach ($raw_state['order'] as $rule) {
            if (!is_array($rule) || !isset($rule[0], $rule[1])) {
                continue;
            }
            $safe_state['order'][] = [
                (int) $rule[0],
                strtolower((string) $rule[1]) === 'desc' ? 'desc' : 'asc',
            ];
        }
    }
    if (isset($raw_state['search'])) {
        $safe_state['search'] = $safe_search($raw_state['search']);
    }
    if (isset($raw_state['columns']) && is_array($raw_state['columns'])) {
        $safe_state['columns'] = [];
        foreach ($raw_state['columns'] as $column) {
            $column = is_array($column) ? $column : [];
            $safe_state['columns'][] = [
                'visible' => $to_bool($column['visible'] ?? true),
                'search'  => $safe_search($column['search'] ?? []),
            ];
        }
    }

    $dashboardWidgets = new Widget();
    $dashboardWidgets->getFromDBByCrit(['name' => $gsIdName]);

    if (isset($dashboardWidgets->fields['id'])) {
        $gsId    = "gs" . $dashboardWidgets->fields['id'];
        $gsExist = false;
        $result  = [
            'success' => true,
            'message' => $dashboardWidgets->fields['id'],
        ];

        $idUser    = $_SESSION['glpiID'];
        $idProfile = $_SESSION['glpiactiveprofile']['id'];

        $dashboard = new Dashboard();

        $edit = Preference::checkEditMode(Session::getLoginUserID());
        if (Session::haveRight("plugin_mydashboard_config", CREATE) && $edit == 2) {
            $idUser    = 0;
            $idProfile = $_SESSION['plugin_mydashboard_profiles_id'] ?? $idProfile;
        }

        if ($dashboard->getFromDBByCrit(['users_id'    => $idUser,
            'profiles_id' => $idProfile])) {
            if (!is_null($dashboard->fields['grid_statesave'])) {
                $grids_saved = json_decode($dashboard->fields['grid_statesave']);
                foreach ($grids_saved as $key => $grid_saved) {
                    $arrayFinal[$key] = $grid_saved;
                    if ($key == $gsId) {
                        $gsExist           = true;
                        $arrayFinal[$gsId] = $safe_state;
                    }
                }
                if (!$gsExist) {
                    $arrayFinal[$gsId] = $safe_state;
                }
            } else {
                $arrayFinal[$gsId] = $safe_state;
            }
            $res = json_encode($arrayFinal, JSON_NUMERIC_CHECK);
            $res = str_replace(['"true"', '"false"'], ['true', 'false'], $res);
            $dashboard->update(['id'             => $dashboard->fields['id'],
                'grid'           => $dashboard->fields['grid'],
                'grid_statesave' => $res]);
            $result = [
                'success' => true,
                'message' => $safe_state,
            ];
        }
    }
}

// Return a proper JSON content type and escape HTML-significant characters so the
// reflected request payload cannot be interpreted as HTML (reflected XSS defense).
header('Content-Type: application/json');
echo json_encode($result, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
