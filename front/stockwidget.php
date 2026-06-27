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

use Glpi\Exception\Http\AccessDeniedHttpException;
use GlpiPlugin\Mydashboard\Menu;
use GlpiPlugin\Mydashboard\StockWidget;

Session::checkLoginUser();

Html::header(Menu::getTypeName(2), '', "tools", Menu::class,'pluginmydashboardstockwidget');

if (Plugin::isPluginActive("mydashboard")) {

   $config = new StockWidget();
   $config->checkGlobal(READ);

   if ($config->canView()) {

      Search::show(StockWidget::class);

   } else {
       throw new AccessDeniedHttpException();
   }
} else {
    throw new AccessDeniedHttpException();
}

Html::footer();
