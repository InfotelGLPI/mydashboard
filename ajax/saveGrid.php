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

Session::checkRightsOr("plugin_mydashboard", [READ, CREATE + UPDATE]);

// Normalize the incoming grid payload before persisting it: decode the client
// JSON and keep only the geometry keys the dashboard loader reads (id/x/y/w/h),
// dropping anything else. This is defense in depth for the [S1] sink in
// Menu.php (the `grid` column is later interpolated into an inline <script>):
// a crafted payload can no longer be stored verbatim.
$raw_data  = stripslashes($_POST['data'] ?? '[]');
$raw_nodes = json_decode($raw_data, true);
$safe_grid = [];
if (is_array($raw_nodes)) {
    foreach ($raw_nodes as $node) {
        if (!is_array($node) || !isset($node['id'])) {
            continue;
        }
        $safe_grid[] = [
            'id' => (string) $node['id'],
            'x'  => (int) ($node['x'] ?? 0),
            'y'  => (int) ($node['y'] ?? 0),
            'w'  => (int) ($node['w'] ?? 0),
            'h'  => (int) ($node['h'] ?? 0),
        ];
    }
}
$data      = json_encode($safe_grid);
$dashboard = new Dashboard();

// Cast the incoming profile id to an integer (consistent with clearGrid.php)
// to avoid storing non-numeric identifiers in the dashboard preference rows.
$profile = (int) ($_POST['profiles_id'] ?? 0);
$options = ["users_id" => Session::getLoginUserID(), "profiles_id" => $profile];
$id      = Dashboard::checkIfPreferenceExists($options);

if (isset($_POST['users_id'])
    && $_POST['users_id'] == 0) {
    $options              = ["users_id" => 0,
        "profiles_id" => $profile];
    $id                   = Dashboard::checkIfPreferenceExists($options);
    $input['profiles_id'] = $profile;
    if (Session::haveRightsOr("plugin_mydashboard_config", [CREATE, UPDATE])) {
        if ($id) {
            $input['id']   = $id;
            $input["grid"] = $data;
            $dashboard->update($input);
        } else {
            $input['users_id'] = 0;
            $input["grid"]     = $data;
            $dashboard->add($input);
        }
    }
} else {
    $input['profiles_id'] = $profile;
    if ($id) {
        $input['id']   = $id;
        $input["grid"] = $data;
        $dashboard->update($input);
    } else {
        $input['users_id'] = Session::getLoginUserID();
        $input["grid"]     = $data;
        $dashboard->add($input);
    }
}

//$widgets      = Widget::getWidgetList();
//$widgetclasse = new Widget();
//
//if (isset($data)) {
//   $widgetdata = json_decode($data, true);
//   if (isset($widgetdata)
//       && is_array($widgetdata)
//       && count($widgetdata) > 0) {
//      $datajson = [];
//      foreach ($widgetdata as $k => $v) {
//         if (isset($v["id"])) {
//            $datajson[$v["id"]] = Widget::getWidget($v["id"], $widgets, []);
//
//            //         if (isset($_SESSION["glpi_plugin_mydashboard_widgets"])) {
//            //            foreach ($_SESSION["glpi_plugin_mydashboard_widgets"] as $w => $r) {
//            //               if (isset($widgets[$v["id"]]["id"])
//            //                   && $widgets[$v["id"]]["id"] == $w) {
//            //                  $optjson[$v["id"]]["enableRefresh"] = $r;
//            //               }
//            //            }
//            //         }
//         }
//      }
//      //      $ckey = 'md_cache_' . md5($widgetclasse->getTable()).Session::getLoginUserID();
//      //      $GLPI_CACHE->delete($ckey);
//      //      $GLPI_CACHE->set($ckey, $datajson);
//   }
//}

echo Session::getNewCSRFToken();
