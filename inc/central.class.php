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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * This class is used to display a dashboard in a tab in the central
 */
class PluginMydashboardCentral extends CommonGLPI
{

   static $rightname = "plugin_mydashboard";

   /**
    * Get name of this type
    *
    * @param int $nb
    * @return text name of this type by language of the user connected
    *
    */
   static function getTypeName($nb = 0)
   {
      return __('Central', 'mydashboard');
   }


   /**
    * @param array $options
    * @return array
    */
   function defineTabs($options = array())
   {

      $ong = array();
      $this->addStandardTab('PluginMydashboardMenu', $ong, $options);

      return $ong;
   }


   /**
    * @param CommonGLPI $item
    * @param int $withtemplate
    * @return array
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
   {

      $array_ret = array();

      if (Session::haveRight("plugin_mydashboard", UPDATE)) {
         $array_ret[12] = self::createTabEntry(
            __('Dashboard', 'mydashboard'));
      }
      return $array_ret;
   }


   /**
    * @param CommonGLPI $item
    * @param int $tabnum
    * @param int $withtemplate
    * @return bool
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
   {

      if ($tabnum == 12) {
         $menu = new PluginMydashboardMenu();
         $menu->showMenu();
      }

      return true;
   }
}