<?php
/*
 -------------------------------------------------------------------------
 MyDashboard plugin for GLPI
 Copyright (C) 2015 by the MyDashboard Development Team.
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

$widgets = PluginMydashboardWidget::getWidgetList();

if (isset($_POST['gsid']) && isset($_POST['id'])) {
   $gsid = $_POST['gsid'];
   $opt  = [];
   if (isset($_POST['params']) && is_array($_POST['params'])) {
      $opt = $_POST['params'];
   }

   $widget = PluginMydashboardWidget::getWidget($gsid, $opt, $widgets);
   echo $widget;
} else {
   $gsid    = $_POST['gsid'];
   $data = [];
   if (isset($widgets[$gsid])) {
      $opt    = [];
      $widget = PluginMydashboardWidget::getWidget($gsid, $opt, $widgets);
      $data = ["id" => $widgets[$gsid]["id"], "widget" => $widget];
   }

   echo json_encode($data);
}
