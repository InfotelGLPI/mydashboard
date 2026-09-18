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

use GlpiPlugin\Mydashboard\Alert;

// Alerts feed the global ticker shown to every user (including on the login page),
// so managing them is a plugin-configuration action, not personal dashboard editing.
// Require the config right, consistent with ajax/createalert.php.
Session::checkRight("plugin_mydashboard_config", UPDATE);

$alert = new Alert();

// The page guard above carries the global profile bitmask only. check() re-reads the row
// being written — it raises NotFoundHttpException on an id matching nothing, where update()
// and delete() used to answer false silently — and confronts the exact level each branch
// needs, so the entry point can no longer perform a purge with a right that only covers
// updates. This is the control ajax/createalert.php already receives.
if (isset($_POST['update'])) {
    if (isset($_POST['id'])) {
        if ($_POST['id'] == -1) {
            unset($_POST['id']);
            $alert->check(-1, CREATE, $_POST);
            $alert->add($_POST);
        } else {
            $alert->check($_POST['id'], UPDATE, $_POST);
            $alert->update($_POST);
        }
    }
} elseif (isset($_POST['delete'])) {
    if (isset($_POST['id'])) {
        $alert->check($_POST['id'], PURGE, $_POST);
        $alert->delete($_POST, true);
    }
}
Html::back();
