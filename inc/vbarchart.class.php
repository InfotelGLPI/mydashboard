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

/**
 * Every Vertical Bars charts classes must inherit of this class
 * It sets basical parameters to display an vertical bar chart with Flotr2
 */
class PluginMydashboardVBarChart extends PluginMydashboardBarChart
{

   /**
    * PluginMydashboardVBarChart constructor.
    */
   function __construct() {
      parent::__construct();
      $this->setOption('bars', ['show' => true, 'horizontal' => false]);
      $this->setOrientation("v");
   }

}
