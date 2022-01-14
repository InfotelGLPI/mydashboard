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

if ($plugin->isActivated("mydashboard")) {

   $config = new PluginMydashboardStockWidget();

   if (isset($_POST["add"])) {

      $config->check(-1, CREATE, $_POST);
      $newID = $config->add($_POST);
      if ($_SESSION['glpibackcreated']) {
         Html::redirect($config->getFormURL() . "?id=" . $newID);
      }
      Html::back();

   } else if (isset($_POST["purge"])) {

      $config->check($_POST['id'], PURGE);
      $config->delete($_POST, 1);
      $config->redirectToList();

   } else if (isset($_POST["update"])) {

      $config->check($_POST['id'], UPDATE);
      $config->update($_POST);
      Html::back();

   } else {

      $config->checkGlobal(READ);

      Html::header(PluginMydashboardMenu::getTypeName(2), '', "tools", "pluginmydashboardmenu",'pluginmydashboardstockwidget');

      $config->display($_GET);

      Html::footer();
   }
} else {
   Html::displayRightError();
}

Html::footer();
