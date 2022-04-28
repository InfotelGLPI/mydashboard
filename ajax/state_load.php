<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

include('../../../inc/includes.php');
Session::checkLoginUser();
$result  = null;
$gsId    = "";
$gsExist = false;
if (isset($_GET['gsId'])) {
   $gsIdName         = $_GET['gsId'];
   $dashboardWidgets = new PluginMydashboardWidget();
   $dashboardWidgets->getFromDBByCrit(['name' => $gsIdName]);
}

if (isset($dashboardWidgets->fields['id'])) {
   $gsId = "gs" . $dashboardWidgets->fields['id'];
   $idUser    = $_SESSION['glpiID'];
   $idProfile = $_SESSION['glpiactiveprofile']['id'];
   $dashboard = new  PluginMydashboardDashboard();

   $edit = PluginMydashboardPreference::checkEditMode(Session::getLoginUserID());
   if (Session::haveRight("plugin_mydashboard_config", CREATE) && $edit == 2) {
      $idUser    = 0;
      $idProfile = $_GET['profiles_id'];
   }
   if ($idProfile > 0) {
      if ($dashboard->getFromDBByCrit(['users_id' => $idUser, 'profiles_id' => $idProfile])) {
         if (!is_null($dashboard->fields['grid_statesave'])) {
            $grids_saved = json_decode($dashboard->fields['grid_statesave']);
            foreach ($grids_saved as $key => $grid_saved) {
               if ($key == $gsId) {
                  $result = $grid_saved;
                  $result = json_encode($result, JSON_NUMERIC_CHECK);
                  $result = str_replace(['"true"', '"false"'], ['true', 'false'], $result);
               }
            }
         }
      } else if ($dashboard->getFromDBByCrit(['users_id' => 0, 'profiles_id' => $idProfile])) {
         if (!is_null($dashboard->fields['grid_statesave'])) {
            $grids_saved = json_decode($dashboard->fields['grid_statesave']);
            foreach ($grids_saved as $key => $grid_saved) {
               if ($key == $gsId) {
                  $result = $grid_saved;
                  $result = json_encode($result, JSON_NUMERIC_CHECK);
                  $result = str_replace(['"true"', '"false"'], ['true', 'false'], $result);
               }
            }
         }
      }
   }
}

echo $result;
