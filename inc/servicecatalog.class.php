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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}


class PluginMydashboardServicecatalog extends CommonGLPI
{

   static $rightname = 'plugin_mydashboard';

   var $dohistory = false;

   static function canUse() {
      return Session::haveRightsOr("plugin_mydashboard", [CREATE, READ]);
   }

   /**
    * @return string
    */
   static function getMenuLink() {
      global $CFG_GLPI;

      return PLUGIN_MYDASHBOARD_WEBDIR . "/front/menu.php";
   }

   /**
    * @return string
    */
   static function getNavBarLink() {
      global $CFG_GLPI;

      return PLUGIN_MYDASHBOARD_NOTFULL_DIR . "/front/menu.php";
   }

   static function getMenuLogo() {

      return PluginMydashboardMenu::getIcon();

   }

   /**
    * @return string
    * @throws \GlpitestSQLError
    */
   static function getMenuLogoCss() {

      $addstyle = "font-size: 4.5em;";
      return $addstyle;

   }

   static function getMenuTitle() {

      return __('Dashboard access', 'mydashboard');

   }


   static function getMenuComment() {

      return __('Dashboard access', 'mydashboard');
   }

   static function getLinkList() {
      return "";
   }

   static function getList() {
      return "";
   }
}
