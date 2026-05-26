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

use GlpiPlugin\Mydashboard\Alert;

Session::checkRight("plugin_mydashboard", UPDATE);

$alert = new Alert();

if (isset($_POST['update'])) {
   if (isset($_POST['id'])) {
      if ($_POST['id'] == -1) {
         unset($_POST['id']);
         $alert->add($_POST);
      } else {
         $alert->update($_POST);
      }
   }
} else if (isset($_POST['delete'])) {
   if (isset($_POST['id'])) {
      $alert->delete($_POST, true);
   }
}
Html::back();

