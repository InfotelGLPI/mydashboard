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

use GlpiPlugin\Mydashboard\Widget;

Session::checkRightsOr("plugin_mydashboard", [READ, CREATE + UPDATE]);


//charger uniquement les graph chargés plutot ?
$widgets = Widget::getCompleteWidgetList();
$wid = new Widget();

if (isset($_POST['gsid']) && isset($_POST['id'])) {
    $gsid = $_POST['gsid'];
    $opt  = [];
    if (isset($_POST['params']) && is_array($_POST['params'])) {
        $opt = $_POST['params'];
    }
    if (isset($widgets[$gsid])) {
        $class    = $widgets[$gsid]["class"];
        $id_class = $widgets[$gsid]["id"];
        $widget   = Widget::loadWidget($class, $id_class, "bt-col-md-11", $opt);
        echo $widget;
    }
} else {
    $gsid = $_POST['gsid'];
    $data = [];
    if (isset($widgets[$gsid])) {
        $opt      = [];
        $class    = $widgets[$gsid]["class"];
        $id_class = $widgets[$gsid]["id"];
        $widget   = Widget::loadWidget($class, $id_class, "bt-col-md-11", $opt);
        $data     = ["id" => Widget::removeBackslashes($id_class), "widget" => $widget];
    }
    echo json_encode($data);
}
