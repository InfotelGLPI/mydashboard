<?php

/*
 -------------------------------------------------------------------------
 MyDashboard plugin for GLPI
 Copyright (C) 2015-2022 by the MyDashboard Development Team.
 -------------------------------------------------------------------------

 LICENSE

 This file is part of MyDashboard.

 MyDashboard is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 MyDashboard is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with MyDashboard. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

use GlpiPlugin\Mydashboard\Reports\Reports_Bar;
use GlpiPlugin\Mydashboard\Reports\Reports_Funnel;
use GlpiPlugin\Mydashboard\Reports\Reports_Line;
use GlpiPlugin\Mydashboard\Reports\Reports_Pie;
use GlpiPlugin\Mydashboard\Reports\Reports_Table;
use GlpiPlugin\Ocsinventoryng\Dashboard;

Session::checkRightsOr("plugin_mydashboard", [READ, CREATE + UPDATE]);

//Case PluginMydashboardReports_Table32 / PluginMydashboardReports_Table33
if (isset($_POST['widget'])) {
    foreach ($_POST as $k => $v) {
        $_POST['params'][$k] = $v;
    }
}

$link = '';

if (isset($_POST["selected_id"]) && $_POST["selected_id"] == "") {
    $link = '';
}

$widget = $_POST["params"]["widget"] ?? '';

if ($widget === "PluginOcsinventoryngDashboard1") {
    $link = Dashboard::pluginOcsinventoryngDashboard1link($_POST);
} else {
    $classes = [Reports_Bar::class, Reports_Pie::class, Reports_Line::class, Reports_Table::class, Reports_Funnel::class];
    foreach ($classes as $class) {
        if (str_starts_with($widget, $class)) {
            $link = $class::getLinkForWidget($widget, $_POST) ?? '';
            break;
        }
    }
}

echo $link;
