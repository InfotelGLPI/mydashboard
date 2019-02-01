<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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

$result = [];
$grids_saved = [];
$arrayFinal = [];
if (!isset($_POST)) {
   $result = [
      'success'   => false,
      'message'   => __('Required argument missing!')
   ];
} else if(!empty($_POST)){
   $gsIdName = $_POST['gsId'];
   unset($_POST['gsId']);
   $dashboardWidgets = new PluginMydashboardWidget();
   $dashboardWidgets->getFromDBByCrit(['name' => $gsIdName]);
   $gsId = "gs".$dashboardWidgets->fields['id'];
   $gsExist = false;
   $result = [
      'success'   => true,
      'message'   =>$dashboardWidgets->fields['id']
   ];

   $idUser = $_SESSION['glpiID'];
   $idProfile = $_SESSION['glpiactiveprofile']['id'];

   $dashboard = new  PluginMydashboardDashboard();

   $edit = PluginMydashboardPreference::checkEditMode(Session::getLoginUserID());
   if (Session::haveRight("plugin_mydashboard_config", CREATE) && $edit == 2) {
      $idUser = 0;
      $idProfile = $_POST['profiles_id'];
   }

   if($dashboard->getFromDBByCrit(['users_id' => $idUser,'profiles_id'=> $idProfile])){
      if(!is_null($dashboard->fields['grid_statesave'])){
         $grids_saved = json_decode($dashboard->fields['grid_statesave']);
         foreach ($grids_saved as $key => $grid_saved) {
            $arrayFinal[$key] = $grid_saved;
            if($key == $gsId){
               $gsExist = true;
               $arrayFinal[$gsId] = $_POST;
            }
         }
         if(!$gsExist){
            $arrayFinal[$gsId] = $_POST;
         }
      } else{
         $arrayFinal[$gsId] = $_POST;
      }
      $res = json_encode($arrayFinal,JSON_NUMERIC_CHECK);
      $res = str_replace( ['"true"', '"false"'], ['true', 'false'], $res );
      $dashboard->update(['id'=> $dashboard->fields['id'],
                          'grid'=> $dashboard->fields['grid'],
                          'grid_statesave' => $res]);
      $result = [
         'success'   => true,
         'message'   => $_POST
      ];
   }
}

echo json_encode($result);