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
 the Free Software Foundation; either version 3 of the License, or
 (at your option) any later version.

 mydashboard is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with mydashboard. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

use Glpi\Exception\Http\AccessDeniedHttpException;
use GlpiPlugin\Mydashboard\Menu;
use GlpiPlugin\Servicecatalog\Main;

Session::checkLoginUser();

if (Session::getCurrentInterface() == 'central') {
   Html::header(Menu::getTypeName(1), '', "tools", Menu::class);
} else {

   if (Plugin::isPluginActive('servicecatalog')) {
      Main::showDefaultHeaderHelpdesk(Menu::getTypeName(1));
   } else {
      Html::helpHeader(Menu::getTypeName(1));
   }
}

if (isset($_POST["profiles_id"])) {
   // Anti-IDOR: only accept a profile actually granted to the user (present in
   // $_SESSION['glpiprofiles']). Config-right holders may switch to any profile
   // for global dashboard editing (consistent with saveGrid/state_save). A forged
   // profiles_id is ignored so it cannot load another profile's dashboard layout.
   $requested_profile = (int) $_POST["profiles_id"];
   if (isset($_SESSION['glpiprofiles'][$requested_profile])
       || Session::haveRight("plugin_mydashboard_config", CREATE)) {
      $_SESSION['plugin_mydashboard_profiles_id'] = $requested_profile;
   }
}
if (isset($_POST["predefined_grid"])) {
   $_SESSION['plugin_mydashboard_predefined_grid'] = $_POST["predefined_grid"];
};

if (Session::haveRightsOr("plugin_mydashboard", [READ, UPDATE])) {
    $profile         = (isset($_SESSION['glpiactiveprofile']['id'])) ? $_SESSION['glpiactiveprofile']['id'] : -1;
    $predefined_grid = 0;

    // Use the validated profile stored above (never the raw POST value).
    if (isset($_POST["profiles_id"]) && isset($_SESSION['plugin_mydashboard_profiles_id'])) {
        $profile = (int) $_SESSION['plugin_mydashboard_profiles_id'];
    }
    if (isset($_POST["predefined_grid"])) {
        $predefined_grid = $_POST["predefined_grid"];
    }
    $dashboard = new Menu();
    $dashboard->loadDashboard($profile, $predefined_grid);
} else {
    throw new AccessDeniedHttpException();
}

if (Session::getCurrentInterface() != 'central'
    && Plugin::isPluginActive('servicecatalog')) {

   Main::showNavBarFooter('mydashboard');
}

if (Session::getCurrentInterface() == 'central') {
   Html::footer();
} else {
   Html::helpFooter();
}
