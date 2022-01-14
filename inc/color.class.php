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

/**
 * Class PluginMydashboardColor
 */
class PluginMydashboardColor {
   private $red, $green, $blue;

   //Colors for 5 series, if there is more series Flotr generates missing colors
   //Those colors are differents from the default colors of Flotr
   private static $colors  = [
      '#28BEBD',//blue
      '#8ED8DB',//green
      '#F1AE29',//yellow
      '#F79637',//red
      '#EF5344'//magenta
   ];
   private static $opacity = '0.7';

   /**
    * Get a color string "rgb(x,y,z)" randomly generated
    * @return string
    */
   //   public static function getRandomColor() {
   //      return "rgb(" . rand(0, 255) . "," . rand(0, 255) . "," . rand(0, 255) . ")";
   //   }

   /**
    * Get the fixed opacity
    * @return string
    */
   public static function getOpacity() {
      return self::$opacity;
   }

   /**
    * @param     $nb_value
    *
    * @param int $pref
    *
    * @return array
    */
   static function getPalette($nb_value, $pref = 1) {

      if ($pref == 2) {
         $colors = ['#208e3d', '#fff745', '#ffa500', '#ed5953', '#ed231c',
                    "#8c564b", "#c49c94", "#e377c2", "#f7b6d2", "#7f7f7f",
                    "#c7c7c7", "#bcbd22", "#dbdb8d", "#17becf", "#9edae5",
                    "#98df8a", "#d62728", "#ff9896", "#9467bd", "#c5b0d5",
                    "#1f77b4", "#aec7e8", "#ff7f0e", "#ffbb78", "#2ca02c",
                    "#98df8a", "#d62728", "#ff9896", "#9467bd", "#c5b0d5",
                    "#8c564b", "#c49c94", "#e377c2", "#f7b6d2", "#7f7f7f",
                    "#c7c7c7", "#bcbd22", "#dbdb8d", "#17becf", "#9edae5"];
         if ($nb_value > 45) {
            $colors2 = [
               "#1f77b4", "#aec7e8", "#ff7f0e", "#ffbb78", "#2ca02c",
               "#98df8a", "#d62728", "#ff9896", "#9467bd", "#c5b0d5",
               "#8c564b", "#c49c94", "#e377c2", "#f7b6d2", "#7f7f7f",
               "#c7c7c7", "#bcbd22", "#dbdb8d", "#17becf", "#9edae5",
               '#208e3d', '#fff745', '#ffa500', '#ed5953', '#ed231c',
               "#8c564b", "#c49c94", "#e377c2", "#f7b6d2", "#7f7f7f",
               "#c7c7c7", "#bcbd22", "#dbdb8d", "#17becf", "#9edae5",
               "#98df8a", "#d62728", "#ff9896", "#9467bd", "#c5b0d5",
            ];
            $colors  = array_merge($colors, $colors2);
         }
      } else {
         $colors = [
            "#1f77b4", "#aec7e8", "#ff7f0e", "#ffbb78", "#2ca02c",
            "#98df8a", "#d62728", "#ff9896", "#9467bd", "#c5b0d5",
            "#8c564b", "#c49c94", "#e377c2", "#f7b6d2", "#7f7f7f",
            "#c7c7c7", "#bcbd22", "#dbdb8d", "#17becf", "#9edae5",
            '#208e3d', '#fff745', '#ffa500', '#ed5953', '#ed231c',
            "#8c564b", "#c49c94", "#e377c2", "#f7b6d2", "#7f7f7f",
            "#c7c7c7", "#bcbd22", "#dbdb8d", "#17becf", "#9edae5",
            "#98df8a", "#d62728", "#ff9896", "#9467bd", "#c5b0d5",
         ];

         if ($nb_value > 45) {
            $colors2 = ['#208e3d', '#fff745', '#ffa500', '#ed5953', '#ed231c',
                        "#8c564b", "#c49c94", "#e377c2", "#f7b6d2", "#7f7f7f",
                        "#c7c7c7", "#bcbd22", "#dbdb8d", "#17becf", "#9edae5",
                        "#98df8a", "#d62728", "#ff9896", "#9467bd", "#c5b0d5",
                        "#1f77b4", "#aec7e8", "#ff7f0e", "#ffbb78", "#2ca02c",
                        "#98df8a", "#d62728", "#ff9896", "#9467bd", "#c5b0d5",
                        "#8c564b", "#c49c94", "#e377c2", "#f7b6d2", "#7f7f7f",
                        "#c7c7c7", "#bcbd22", "#dbdb8d", "#17becf", "#9edae5"];
            $colors  = array_merge($colors, $colors2);
         }
      }
      return $colors;
   }

   /**
    * @param int $nb_value
    * @param int $index
    *
    * @return array
    */
   static function getColors($nb_value = 20, $index = 1) {

      $pref   = PluginMydashboardPreference::getPalette(Session::getLoginUserID());
      $colors = self::getPalette($nb_value, $pref);
      //fill colors on size index
      $palette = [];
      $i       = 0;
      if ($nb_value > 1) {
         while ($i < $nb_value) {
            if (isset($colors[$i])) {
               $palette[] = $colors[$i];
            }
            $i++;
         }
      } else {
         $palette = $colors[$index];
      }
      return $palette;
   }

}
