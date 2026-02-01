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

Session::checkLoginUser();

//Case PluginMydashboardReports_Table32 / PluginMydashboardReports_Table33
if (isset($_POST['widget'])) {
    foreach ($_POST as $k => $v) {
        $_POST['params'][$k] = $v;
    }
}

if (isset($_POST["params"]["technician_group"])) {
    $_POST["params"]["technician_group"] = is_array($_POST["params"]["technician_group"]) ? $_POST["params"]["technician_group"] : [$_POST["params"]["technician_group"]];
} else {
    $_POST["params"]["technician_group"] = [];
}

if (isset($_POST["params"]["requester_groups"])) {
    $_POST["params"]["requester_groups"] = is_array($_POST["params"]["requester_groups"]) ? $_POST["params"]["requester_groups"] : [$_POST["params"]["requester_groups"]];
} else {
    $_POST["params"]["requester_groups"] = [];
}

$link = '';

if ($_POST["selected_id"] == "") {
    $link = '';
}

if (isset($_POST["params"]["widget"])
    && $_POST["params"]["widget"] == "PluginOcsinventoryngDashboard1") {
    //inventory
    $link = Dashboard::pluginOcsinventoryngDashboard1link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == Reports_Bar::class."1") {
    $link = Reports_Bar::pluginMydashboardReports_Bar1link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == Reports_Bar::class."15") {
    $link = Reports_Bar::pluginMydashboardReports_Bar15link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == Reports_Bar::class."24") {
    $link = Reports_Bar::pluginMydashboardReports_Bar24link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == Reports_Bar::class."35") {
    $link = Reports_Bar::pluginMydashboardReports_Bar35link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == Reports_Bar::class."36") {
    $link = Reports_Bar::pluginMydashboardReports_Bar36link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == Reports_Bar::class."37") {
    $link = Reports_Bar::pluginMydashboardReports_Bar37link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == Reports_Bar::class."43") {
    $link = Reports_Bar::pluginMydashboardReports_Bar43link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == Reports_Bar::class."44") {
    //inventory
    $link = Reports_Bar::pluginMydashboardReports_Bar44link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && ($_POST["params"]["widget"] == Reports_Pie::class."2")) {
    $link = Reports_Pie::pluginMydashboardReports_Pie2link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && ($_POST["params"]["widget"] == Reports_Pie::class."16"
              || $_POST["params"]["widget"] == Reports_Pie::class."17")) {
    $link = Reports_Pie::pluginMydashboardReports_Pie16link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == Reports_Pie::class."25") {
    $link = Reports_Pie::pluginMydashboardReports_Pie25link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == Reports_Pie::class."27") {
    $link = Reports_Pie::pluginMydashboardReports_Pie27link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == Reports_Table::class."32") {
    $link = Reports_Table::pluginMydashboardReports_Table32link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == Reports_Table::class."33") {
    $link = Reports_Table::pluginMydashboardReports_Table33link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == Reports_Line::class."22") {
    $link = Reports_Line::pluginMydashboardReports_Line22link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == Reports_Line::class."34") {
    $link = Reports_Line::pluginMydashboardReports_Line34link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == Reports_Line::class."35") {
    $link = Reports_Line::pluginMydashboardReports_Line35link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == Reports_Line::class."43") {
    $link = Reports_Line::pluginMydashboardReports_Line43link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == Reports_Line::class."44") {
    $link = Reports_Line::pluginMydashboardReports_Line44link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == Reports_Line::class."45") {
    $link = Reports_Line::pluginMydashboardReports_Line45link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == Reports_Line::class."46") {
    $link = Reports_Line::pluginMydashboardReports_Line46link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == Reports_Line::class."48") {
    $link = Reports_Line::pluginMydashboardReports_Line48link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == Reports_Funnel::class."1") {
    //inventory
    $link = Reports_Funnel::pluginMydashboardReports_Funnel1link($_POST);
}

echo $link;
