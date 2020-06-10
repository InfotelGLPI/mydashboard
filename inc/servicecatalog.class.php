<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 resources plugin for GLPI
 Copyright (C) 2009-2016 by the resources Development Team.

 https://github.com/InfotelGLPI/resources
 -------------------------------------------------------------------------

 LICENSE

 This file is part of resources.

 resources is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 resources is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with resources. If not, see <http://www.gnu.org/licenses/>.
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

      return $CFG_GLPI['root_doc'] . "/plugins/mydashboard/front/menu.php";
   }

   static function getMenuLogo() {

      return "fas fa-tachometer-alt";

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