<?php

/*
 -------------------------------------------------------------------------
 mydashboard plugin for GLPI
 Copyright (C) 2016-2026 by the mydashboard Development Team.

 https://github.com/InfotelGLPI/mydashboard
 -------------------------------------------------------------------------

 LICENSE

 This file is part of mydashboard.

 mydashboard is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 mydashboard is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with mydashboard. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

use GlpiPlugin\Mydashboard\Preference;

Session::checkLoginUser();

//Save user preferences
if (isset ($_POST['update'])) {
   $pref = new Preference();
   $pref->check(-1, UPDATE, $_POST);
   if(isset($_POST["prefered_group"])){
      $_POST["prefered_group"] = json_encode($_POST["prefered_group"]);
   }else{
      $_POST["prefered_group"] = "[]";
   }

   if(isset($_POST["requester_prefered_group"])){
      $_POST["requester_prefered_group"] = json_encode($_POST["requester_prefered_group"]);
   }else{
      $_POST["requester_prefered_group"] = "[]";
   }

   $pref->update($_POST);
   Html::back();
}
