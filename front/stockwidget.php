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

use Glpi\Exception\Http\AccessDeniedHttpException;
use GlpiPlugin\Mydashboard\Menu;
use GlpiPlugin\Mydashboard\StockWidget;

// Every refusal below is raised before a single byte of the page is emitted: an
// AccessDeniedHttpException thrown after Html::header() would land in the middle of
// an already-started document, leaving the menu and the page frame of the plugin
// visible behind the error to a visitor who has no right on it.
if (!Plugin::isPluginActive("mydashboard")) {
    throw new AccessDeniedHttpException();
}

$config = new StockWidget();
$config->checkGlobal(READ);

if (!$config->canView()) {
    throw new AccessDeniedHttpException();
}

Html::header(Menu::getTypeName(2), '', "tools", Menu::class, 'pluginmydashboardstockwidget');

Search::show(StockWidget::class);

Html::footer();
