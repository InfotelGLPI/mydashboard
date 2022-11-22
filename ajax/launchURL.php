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

include("../../../inc/includes.php");

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

if ($_POST["selected_id"] == "") {
    $link = '';
}

if (isset($_POST["params"]["widget"])
    && $_POST["params"]["widget"] == "PluginOcsinventoryngDashboard1") {
    //inventory
    $link = PluginOcsinventoryngDashboard::pluginOcsinventoryngDashboard1link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == "PluginMydashboardReports_Bar1") {
    $link = PluginMydashboardReports_Bar::pluginMydashboardReports_Bar1link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == "PluginMydashboardReports_Bar15") {
    $link = PluginMydashboardReports_Bar::pluginMydashboardReports_Bar15link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == "PluginMydashboardReports_Bar24") {
    $link = PluginMydashboardReports_Bar::pluginMydashboardReports_Bar24link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == "PluginMydashboardReports_Bar35") {
    $link = PluginMydashboardReports_Bar::pluginMydashboardReports_Bar35link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == "PluginMydashboardReports_Bar36") {
    $link = PluginMydashboardReports_Bar::pluginMydashboardReports_Bar36link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == "PluginMydashboardReports_Bar37") {
    $link = PluginMydashboardReports_Bar::pluginMydashboardReports_Bar37link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == "PluginMydashboardReports_Bar43") {
    $link = PluginMydashboardReports_Bar::pluginMydashboardReports_Bar43link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == "PluginMydashboardReports_Bar44") {
    //inventory
    $link = PluginMydashboardReports_Bar::pluginMydashboardReports_Bar44link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && ($_POST["params"]["widget"] == "PluginMydashboardReports_Pie2")) {
    $link = PluginMydashboardReports_Pie::pluginMydashboardReports_Pie2link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && ($_POST["params"]["widget"] == "PluginMydashboardReports_Pie16"
              || $_POST["params"]["widget"] == "PluginMydashboardReports_Pie17")) {
    $link = PluginMydashboardReports_Pie::pluginMydashboardReports_Pie16link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == "PluginMydashboardReports_Pie25") {
    $link = PluginMydashboardReports_Pie::pluginMydashboardReports_Pie25link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == "PluginMydashboardReports_Pie27") {
    $link = PluginMydashboardReports_Pie::pluginMydashboardReports_Pie27link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == "PluginMydashboardReports_Table32") {
    $link = PluginMydashboardReports_Table::pluginMydashboardReports_Table32link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == "PluginMydashboardReports_Table33") {
    $link = PluginMydashboardReports_Table::pluginMydashboardReports_Table33link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == "PluginMydashboardReports_Line22") {
    $link = PluginMydashboardReports_Line::pluginMydashboardReports_Line22link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == "PluginMydashboardReports_Line34") {
    $link = PluginMydashboardReports_Line::pluginMydashboardReports_Line34link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == "PluginMydashboardReports_Line35") {
    $link = PluginMydashboardReports_Line::pluginMydashboardReports_Line35link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == "PluginMydashboardReports_Line43") {
    $link = PluginMydashboardReports_Line::pluginMydashboardReports_Line43link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == "PluginMydashboardReports_Line44") {
    $link = PluginMydashboardReports_Line::pluginMydashboardReports_Line44link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == "PluginMydashboardReports_Line45") {
    $link = PluginMydashboardReports_Line::pluginMydashboardReports_Line45link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == "PluginMydashboardReports_Line46") {
    $link = PluginMydashboardReports_Line::pluginMydashboardReports_Line46link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == "PluginMydashboardReports_Line48") {
    $link = PluginMydashboardReports_Line::pluginMydashboardReports_Line48link($_POST);
} elseif (isset($_POST["params"]["widget"])
          && $_POST["params"]["widget"] == "PluginMydashboardReports_Funnel1") {
    //inventory
    $link = PluginMydashboardReports_Funnel::pluginMydashboardReports_Funnel1link($_POST);
}

echo $link;
