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
use GlpiPlugin\Mydashboard\Config;
use GlpiPlugin\Mydashboard\Menu;
use GlpiPlugin\Mydashboard\StockTicket;
use GlpiPlugin\Mydashboard\StockTicketIndicator;

Session::checkLoginUser();
// checkLoginUser() performs no authorization on GLPI 11. CommonDBTM::update() does
// not enforce $rightname either, so gate the whole controller (config write and the
// heavy reconstruct branches) on the plugin setup right, like front/config.php does.
Session::checkRight(Config::$rightname, UPDATE);

Html::header(Menu::getTypeName(2), '', "tools", Menu::class);

if (!isset($_GET["id"])) {
    $_GET["id"] = "1";
}
if (Plugin::isPluginActive("mydashboard")) {
    $config = new Config();

    if (isset($_POST["reconstructBacklog"])) {
        ini_set("max_execution_time", "0");
        ini_set("memory_limit", "-1");
        StockTicket::fillTableMydashboardStocktickets();
        StockTicket::fillTableMydashboardStockticketsGroup();
        Html::back();
    } elseif (isset($_POST["reconstructIndicators"])) {
        ini_set("max_execution_time", "0");
        ini_set("memory_limit", "-1");
        $record = new StockTicketIndicator();
        $record->cronMydashboardInfotelUpdateStockTicketIndicator("all");
        Html::back();
    } elseif (isset($_POST['update'])) {
        $config->update($_POST);
    }

    $config->display($_GET);
} else {
    throw new AccessDeniedHttpException();
}

Html::footer();
