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

Session::checkLoginUser();

$plugin = new Plugin();

if (Session::getCurrentInterface() == 'central') {
   Html::header(PluginMydashboardMenu::getTypeName(1), '', "tools", "pluginmydashboardmenu");
} else {

   if ($plugin->isActivated('servicecatalog')) {
      PluginServicecatalogMain::showDefaultHeaderHelpdesk(PluginMydashboardMenu::getTypeName(1));
   } else {
      Html::helpHeader(PluginMydashboardMenu::getTypeName(1));
   }
}

if (isset($_POST["profiles_id"])) {
   $_SESSION['plugin_mydashboard_profiles_id'] = $_POST["profiles_id"];
}
if (isset($_POST["predefined_grid"])) {
   $_SESSION['plugin_mydashboard_predefined_grid'] = $_POST["predefined_grid"];
};

if (Session::haveRightsOr("plugin_mydashboard", [READ, UPDATE])) {
   if (isset($_POST["add_ticket"])) {

      Ticket::showFormHelpdesk(Session::getLoginUserID(), $_POST["tickettemplates_id"]);

   } else {

      $profile         = (isset($_SESSION['glpiactiveprofile']['id'])) ? $_SESSION['glpiactiveprofile']['id'] : -1;
      $predefined_grid = 0;

      if (isset($_POST["profiles_id"])) {
         $profile = $_POST["profiles_id"];
      }
      if (isset($_POST["predefined_grid"])) {
         $predefined_grid = $_POST["predefined_grid"];
      }
      $dashboard = new PluginMydashboardMenu();
      $dashboard->loadDashboard($profile, $predefined_grid);

   }
} else {
   Html::displayRightError();
}

if (Session::getCurrentInterface() != 'central'
    && $plugin->isActivated('servicecatalog')) {

   PluginServicecatalogMain::showNavBarFooter('mydashboard');
}

if (Session::getCurrentInterface() == 'central') {
   Html::footer();
} else {
   Html::helpFooter();
}
