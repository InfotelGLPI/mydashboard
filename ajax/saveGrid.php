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


$data      = stripslashes($_POST['data']);
$dashboard = new PluginMydashboardDashboard();

$profile = $_POST['profiles_id'];
$options = ["users_id" => Session::getLoginUserID(), "profiles_id" => $profile];
$id = PluginMydashboardDashboard::checkIfPreferenceExists($options);

if (isset($_POST['users_id']) && $_POST['users_id'] == 0) {
   $options = ["users_id" => 0, "profiles_id" => $profile];
   $id = PluginMydashboardDashboard::checkIfPreferenceExists($options);
   $input['profiles_id'] = $profile;
   if (Session::haveRightsOr("plugin_mydashboard_config", array(CREATE, UPDATE))) {
      if ($id) {
         $input['id']   = $id;
         $input["grid"] = $data;
         $dashboard->update($input);
      } else {
         $input['users_id'] = 0;
         $input["grid"]     = $data;
         $dashboard->add($input);
      }
   }
} else {
   $input['profiles_id'] = $profile;
   if ($id) {
      $input['id']   = $id;
      $input["grid"] = $data;
      $dashboard->update($input);
   } else {
      $input['users_id'] = Session::getLoginUserID();
      $input["grid"]     = $data;
      $dashboard->add($input);
   }
}

echo Session::getNewCSRFToken();