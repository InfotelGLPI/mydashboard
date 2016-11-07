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
 * Class PluginMydashboardColor
 */
class PluginMydashboardColor
{
   private $red, $green, $blue;

   //Colors for 5 series, if there is more series Flotr generates missing colors
   //Those colors are differents from the default colors of Flotr
   private static $colors = array(
      '#28BEBD',//blue
      '#8ED8DB',//green
      '#F1AE29',//yellow
      '#F79637',//red
      '#EF5344'//magenta
   );
   private static $opacity = '0.7';

   /**
    * Get a color string "rgb(x,y,z)" randomly generated
    * @return string
    */
   public static function getRandomColor()
   {
      return "rgb(" . rand(0, 255) . "," . rand(0, 255) . "," . rand(0, 255) . ")";
   }

   /**
    * Get the array of colors,
    * @return array
    */
   public static function getColors()
   {
      return self::$colors;
   }

   /**
    * Get the fixed opacity
    * @return string
    */
   public static function getOpacity()
   {
      return self::$opacity;
   }

}
