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

use Glpi\Exception\Http\NotFoundHttpException;
use GlpiPlugin\Mydashboard\Alert;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

// No plugin right is required here on purpose: this endpoint only returns the body of a
// reminder the caller is already allowed to see, and the ticker is displayed in contexts
// where the user has no plugin_mydashboard right. The previous check also tested the
// meaningless value CREATE + UPDATE (6), which is not a GLPI right.
// Access control is enforced in displayTickerDescription(), which resolves the reminder
// through Reminder::getVisibilityCriteria() -- that returns "no rows" when there is no
// session, so this endpoint exposes nothing anonymously.

if (!isset($_GET['id'])) {
    throw new NotFoundHttpException();
}

Alert::displayTickerDescription($_GET['id']);
