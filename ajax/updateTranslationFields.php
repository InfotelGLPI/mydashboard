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

$AJAX_INCLUDE = 1;
include("../../../inc/includes.php");

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkRight("plugin_mydashboard_config", UPDATE);

if (isset($_POST['itemtype']) && isset($_POST['language'])) {
   $item = new $_POST['itemtype'];
   $item->getFromDB($_POST['items_id']);
   if ($item->getType() == "PluginMydashboardConfig") {
      PluginMydashboardConfigTranslation::dropdownFields($item, $_POST['language']);
   } else {
      PluginMydashboardConfigTranslation::dropdownFields($item, $_POST['language']);
   }
}