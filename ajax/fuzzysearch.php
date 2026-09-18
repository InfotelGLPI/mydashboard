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

use GlpiPlugin\Mydashboard\Widgetlist;

$AJAX_INCLUDE = 1;
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkRightsOr("plugin_mydashboard", [READ, CREATE + UPDATE]);

// Read the action from $_POST and not from $_REQUEST: the GET form of this endpoint is never
// used by the plugin and is not covered by the CSRF validation, which GLPI only applies to
// non-GET methods. The default also removes the "Undefined array key" notice raised on a call
// without parameter, which polluted the JSON response in debug mode.
$action = (string) ($_POST['action'] ?? '');

echo Widgetlist::fuzzySearch($action);
