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

use GlpiPlugin\Mydashboard\Dashboard;

Session::checkRightsOr("plugin_mydashboard", [READ, CREATE + UPDATE]);

$dashboard = new Dashboard();

$profile   = (int) ($_POST['profiles_id'] ?? ($_SESSION['glpiactiveprofile']['id'] ?? -1));
$edit_mode = (int) ($_POST['edit_mode'] ?? 0);

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



