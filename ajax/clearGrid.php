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

Session::checkRightsOr("plugin_mydashboard", [READ, CREATE + UPDATE]);

$dashboard = new Dashboard();

$profile   = (int) ($_POST['profiles_id'] ?? ($_SESSION['glpiactiveprofile']['id'] ?? -1));
$edit_mode = (int) ($_POST['edit_mode'] ?? 0);

// The posted profile decides which grid row is deleted, so it is confronted with the profiles
// this session may actually manage, exactly as saveGrid.php, state_save.php, state_load.php and
// front/menu.php already do. The "plugin_mydashboard_config" right below is global: it answers
// "may administer dashboards", not "may administer THIS profile". The check is placed before
// $options so it also covers the personal branch, where a foreign profiles_id could be used to
// probe which preference rows exist. The call comes from a UI button, hence the silent fallback.
if (!Dashboard::canManageProfile($profile)) {
    $profile = (int) ($_SESSION['glpiactiveprofile']['id'] ?? 0);
}

if ($edit_mode == 2 && Session::haveRight("plugin_mydashboard_config", CREATE)) {
    // Global edit mode: clear the profile-wide grid (users_id = 0)
    $options = ["users_id" => 0, "profiles_id" => $profile];
} else {
    // Personal edit mode: clear the current user's grid
    $options = ["users_id" => Session::getLoginUserID(), "profiles_id" => $profile];
}

$id = Dashboard::checkIfPreferenceExists($options);
if ($id) {
    $input['id'] = $id;
    $dashboard->delete($input);
}

echo Session::getNewCSRFToken();
