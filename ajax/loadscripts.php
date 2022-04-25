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

Html::header_nocache();
Session::checkLoginUser();
header("Content-Type: text/html; charset=UTF-8");

if (isset($_POST['action'])) {
   switch ($_POST['action']) {
      case "load" :

         $name              = "showMyDashboardLateralMenu";
         $param['title']    = __('My Dashboard', 'mydashboard');
         $param['position'] = 'right';
         $param['url']      = PLUGIN_MYDASHBOARD_WEBDIR. '/ajax/lateralmenu.php';

         $style = "#000";
         $title = __("Go to mydashboard actions", "mydashboard");

         $edit = PluginMydashboardPreference::checkEditMode(Session::getLoginUserID());
         if ($edit > 0) {
            $style = "red";
            $title = __("Edit mode is enabled, please close it for better performance", "mydashboard");
         }
         $out = "<script type='text/javascript'>\n";
         $out .= "if ($('#showMyDashboardLateralMenu').length === 0) {
            $('.ms-md-4').before(\"<a class='ti ti-dashboard' title='$title' href='#' style='color:$style;' id='showMyDashboardLateralMenuLink'></a>\");
         };";
         
         $out .= "</script>";
         echo $out;

         PluginMydashboardMenu::createSlidePanel(
            'showMyDashboardLateralMenu',
            [
               'title'     => __('My Dashboard', 'mydashboard'),
               'url'       => PLUGIN_MYDASHBOARD_WEBDIR. '/ajax/lateralmenu.php'
            ]
         );

         break;
   }
} else {
   exit;
}
