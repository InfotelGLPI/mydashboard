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

include('../../../inc/includes.php');
//header('Content-Type: application/json; charset=UTF-8');
Html::header_nocache();

Session::checkLoginUser();

header("Content-Type: text/html; charset=UTF-8");

$menu = new PluginMydashboardMenu();
$rand = mt_rand();

$selected_profile = (isset($_SESSION['glpiactiveprofile']['id'])) ? $_SESSION['glpiactiveprofile']['id'] : -1;
$predefined_grid  = 0;

if (isset($_SESSION['plugin_mydashboard_profiles_id'])) {
   $selected_profile = $_SESSION['plugin_mydashboard_profiles_id'];
}
if (isset($_SESSION['plugin_mydashboard_predefined_grid'])) {
   $predefined_grid = $_SESSION['plugin_mydashboard_predefined_grid'];
}

$edit = PluginMydashboardPreference::checkEditMode(Session::getLoginUserID());

$menu->displayEditMode($rand, $edit, $selected_profile, $predefined_grid);

