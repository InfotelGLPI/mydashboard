<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Metademands plugin for GLPI
 Copyright (C) 2018-2019 by the Metademands Development Team.

 https://github.com/InfotelGLPI/metademands
 -------------------------------------------------------------------------

 LICENSE

 This file is part of Metademands.

 Metademands is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Metademands is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Metademands. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * Class PluginMydashboardCustomswidgets
 */
class PluginMydashboardCustomswidget extends CommonDropdown{

   /**
    * @param int $nb
    *
    * @return translated
    * @override
    */
   static function getTypeName($nb = 0){

      return __('Customs Widgets', 'mydashboard');
   }

   /**
    * Display tab for each customwidget
    * */
   function defineTabs($options = []) {
      $ong = [];

      $this->addDefaultFormTab($ong);
      $this->addStandardTab('PluginMydashboardHTMLEditor', $ong, $options);
      return $ong;
   }


}