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

include('../../../inc/includes.php');

$plugin = new Plugin();

global $CFG_GLPI;

if ($plugin->isActivated("mydashboard")) {
   if (Session::haveRight("plugin_mydashboard_config", UPDATE)) {

      Html::redirect($CFG_GLPI['root_doc'] . "/plugins/mydashboard/front/config.form.php");

   } else {
      Html::displayRightError();
   }

} else {
   Html::header(__('Setup'), '', "config", "plugins");
   echo "<div align='center'><br><br>";
   echo "<i class='fas fa-exclamation-triangle fa-4x' style='color:orange'></i><br><br>";
   echo "<b>" . __('Please activate the plugin', 'mydashboard') . "</b></div>";
   Html::footer();

}