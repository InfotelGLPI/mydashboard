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
 the Free Software Foundation; either version 3 of the License, or
 (at your option) any later version.

 mydashboard is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with mydashboard. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

use GlpiPlugin\Mydashboard\Config;
use GlpiPlugin\Mydashboard\ProfileAuthorizedWidget;

Session::checkLoginUser();
// This admin screen rewrites the per-profile widget-authorization matrix from an
// arbitrary POST profiles_id. checkLoginUser() is not authorization on GLPI 11 and
// ProfileAuthorizedWidget declares no $rightname, so gate on the plugin setup right.
Session::checkRight(Config::$rightname, UPDATE);

$paw = new ProfileAuthorizedWidget();

$paw->save($_POST);

Html::back();
