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

$group = new PluginMydashboardGroupprofile();
if (isset($_POST["addGroup"])) {
   if (empty($_POST['groups_id'])) {
      Html::back();
   } else {
      $group->check(-1, CREATE, $_POST);
      if(isset($_POST["groups_id"])){
         $_POST["groups_id"] = json_encode($_POST["groups_id"]);
      }else{
         $_POST["groups_id"] = "[]";
      }
      if($group->getFromDBByCrit(['profiles_id' => $_POST['profiles_id']])){
         $group->update(['id'   => $group->fields['id'],
                         'groups_id'   => $_POST['groups_id']]);
      } else{
         $group->add(['groups_id'   => $_POST['groups_id'],
                      'profiles_id' => $_POST['profiles_id']]);
      }

      if (isset($_POST["use_group_profile"])) {
         $profile = new ProfileRight();
         if($profile->getFromDBByCrit(['profiles_id' => $_POST['profiles_id'],
                                       'name'        => 'plugin_mydashboard_groupprofile'])){
            $profile->update(['id'     => $profile->fields['id'],
                              'rights' => $_POST["use_group_profile"]]);
         } else{
            $profile->add(['profiles_id' => $_POST['profiles_id'],
                           'name'        => 'plugin_mydashboard_groupprofile',
                           'rights'      => $_POST["use_group_profile"]]);
         }
      }
      Html::back();
   }
}