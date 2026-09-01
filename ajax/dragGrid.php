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

use GlpiPlugin\Mydashboard\Preference;

Session::checkRightsOr("plugin_mydashboard", [READ, CREATE + UPDATE]);

if (isset($_POST['drag_mode'])) {
    $pref = new Preference();
    // [S2] Constrain the mode to its known domain (0/1/2) instead of persisting
    // an arbitrary POST value into the user's own preference row.
    $mode = in_array((int) $_POST['drag_mode'], [0, 1, 2], true)
       ? (int) $_POST['drag_mode']
       : 0;
    // [S2] Mode 2 is the profile-wide "global" mode, which every privileged endpoint
    // gates on plugin_mydashboard_config (see clearGrid.php, saveGrid.php,
    // state_save.php). Those checks already reject the writes, but storing the mode
    // without the right leaves the preference claiming a capability the session does
    // not have: fall back to the per-user mode so the stored state stays truthful.
    if ($mode === 2 && !Session::haveRight("plugin_mydashboard_config", CREATE)) {
        $mode = 1;
    }
    $input['drag_mode'] = $mode;
    $input['id'] = Session::getLoginUserID();
    $pref->update($input);
}
