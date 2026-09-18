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

use Glpi\Exception\Http\BadRequestHttpException;
use GlpiPlugin\Mydashboard\ConfigTranslation;

Session::checkRight("plugin_mydashboard_config", UPDATE);

$translation = new ConfigTranslation();

// The parent of a translation is polymorphic and comes from the request, so the posted itemtype
// is confronted with the domain the plugin really translates before anything is written. The
// check() calls below then guard the row itself: checkRight() above only guards the page, and
// the three branches used to pass $_POST straight to add()/update()/delete() without the object
// ever being loaded. check(-1, CREATE) needs the input to resolve that polymorphic parent.
if (isset($_POST['itemtype'])
    && !in_array($_POST['itemtype'], ConfigTranslation::getAllowedItemtypes(), true)) {
    throw new BadRequestHttpException();
}

if (isset($_POST['add'])) {
    $translation->check(-1, CREATE, $_POST);
    $translation->add($_POST);
} elseif (isset($_POST['update'])) {
    $translation->check($_POST['id'] ?? -1, UPDATE, $_POST);
    $translation->update($_POST);
} elseif (isset($_POST['purge'])) {
    $translation->check($_POST['id'] ?? -1, PURGE);
    $translation->delete($_POST, 1);
}
Html::back();
