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
use Glpi\Exception\Http\NotFoundHttpException;
use GlpiPlugin\Mydashboard\ConfigTranslation;

$AJAX_INCLUDE = 1;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkRight("plugin_mydashboard_config", UPDATE);

if (isset($_POST['itemtype']) && isset($_POST['language'])) {
    // The allow list and the instantiation are both kept here, at the sink: the list says which
    // itemtypes the plugin translates, getItemForItemtype() refuses anything that is not a
    // loadable GLPI class instead of instantiating a dynamic string.
    if (!in_array($_POST['itemtype'], ConfigTranslation::getAllowedItemtypes(), true)
        || !$item = getItemForItemtype($_POST['itemtype'])) {
        throw new BadRequestHttpException();
    }
    // The row identifier is client supplied: refuse when it does not exist rather than
    // building the dropdown of an empty object. Both allowed itemtypes (core Config and the
    // plugin translations) are global, so the plugin_mydashboard_config UPDATE right checked
    // above is the whole perimeter here.
    if (!$item->getFromDB((int) ($_POST['items_id'] ?? 0))) {
        throw new NotFoundHttpException();
    }
    ConfigTranslation::dropdownFields($item, $_POST['language']);
}
