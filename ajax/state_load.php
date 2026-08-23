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
use GlpiPlugin\Mydashboard\Widget;
use GlpiPlugin\Mydashboard\Preference;

Session::checkRightsOr("plugin_mydashboard", [READ, CREATE + UPDATE]);

$result  = null;
$gsId    = "";
$gsExist = false;
if (isset($_GET['gsId'])) {
    $gsIdName         = $_GET['gsId'];
    $dashboardWidgets = new Widget();
    $dashboardWidgets->getFromDBByCrit(['name' => $gsIdName]);
}

if (isset($dashboardWidgets->fields['id'])) {
    $gsId = "gs" . $dashboardWidgets->fields['id'];
    $idUser    = $_SESSION['glpiID'];
    $idProfile = $_SESSION['glpiactiveprofile']['id'];
    $dashboard = new Dashboard();

    $edit = Preference::checkEditMode(Session::getLoginUserID());
    if (Session::haveRight("plugin_mydashboard_config", CREATE) && $edit == 2) {
        $idUser    = 0;
        // Cast the incoming profile id to int (consistent with saveGrid/clearGrid).
        $idProfile = (int) ($_GET['profiles_id'] ?? 0);
    }
    if ($idProfile > 0) {
        if ($dashboard->getFromDBByCrit(['users_id' => $idUser, 'profiles_id' => $idProfile])) {
            if (!is_null($dashboard->fields['grid_statesave'])) {
                $grids_saved = json_decode($dashboard->fields['grid_statesave']);
                foreach ($grids_saved as $key => $grid_saved) {
                    if ($key == $gsId) {
                        $result = $grid_saved;
                        // Escape HTML-significant characters so the stored grid
                        // payload cannot be interpreted as HTML (stored-XSS defense,
                        // aligned with state_save.php).
                        $result = json_encode(
                            $result,
                            JSON_NUMERIC_CHECK | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
                        );
                        $result = str_replace(['"true"', '"false"'], ['true', 'false'], $result);
                    }
                }
            }
        } elseif ($dashboard->getFromDBByCrit(['users_id' => 0, 'profiles_id' => $idProfile])) {
            if (!is_null($dashboard->fields['grid_statesave'])) {
                $grids_saved = json_decode($dashboard->fields['grid_statesave']);
                foreach ($grids_saved as $key => $grid_saved) {
                    if ($key == $gsId) {
                        $result = $grid_saved;
                        // Escape HTML-significant characters so the stored grid
                        // payload cannot be interpreted as HTML (stored-XSS defense,
                        // aligned with state_save.php).
                        $result = json_encode(
                            $result,
                            JSON_NUMERIC_CHECK | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
                        );
                        $result = str_replace(['"true"', '"false"'], ['true', 'false'], $result);
                    }
                }
            }
        }
    }
}

header('Content-Type: application/json');
echo $result ?? json_encode(null);
