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

if (strpos($_SERVER['PHP_SELF'], "dropdownUpdateDisplaydata.php")) {
   include("../../../inc/includes.php");
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

Session::checkCentralAccess();

// Make a select box
if (isset($_POST["value"])) {
   $form = "";
   if ($_POST['value'] == "START_END") {
      $rand = mt_rand();

      $form .= "<span id='display_data_crit$rand' name= 'display_data_crit$rand' class='md-widgetcrit'>";
      $form .= "<span class='md-widgetcrit'>";
      $form .= "</br></br>";
      $form .= __('Start month', 'mydashboard');
      $form .= "&nbsp;";
      $options = [];
      $options['value'] = $opt['start_month'] ?? date('m');
      $options['rand'] = $rand;
      $options['min'] = 1;
      $options['max'] = 12;
      $options['display'] = false;
      $options['width'] = '200px';
      $form .= Dropdown::showNumber('start_month',$options);
      $form .= "</span>";

      $form .= "<span class='md-widgetcrit'>";
      $form .= "</br>";
      $form .= __('Start year', 'mydashboard');
      $form .= "&nbsp;";
      $options = [];
      $options['value'] = $opt['start_year'] ?? date('Y');
      $options['rand'] = $rand;
      $options['display'] = false;
      $year = date("Y") - 3;
      for ($i = 0; $i <= 3; $i++) {
         $elements[$year] = $year;

         $year++;
      }

      $form .= Dropdown::showFromArray("start_year", $elements, $options);
      $form .= "</span>";

      $form .= "<span class='md-widgetcrit'>";
      $form .= "</br></br>";
      $form .= __('End month', 'mydashboard');
      $form .= "&nbsp;";
      $options = [];
      $options['value'] = $opt['end_month'] ?? date('m');
      $options['rand'] = $rand;
      $options['min'] = 1;
      $options['max'] = 12;
      $options['display'] = false;
      $options['width'] = '200px';
      $form .= Dropdown::showNumber('end_month',$options);
      $form .= "</span>";

      $form .= "<span class='md-widgetcrit'>";
      $form .= "</br>";
      $form .= __('End year', 'mydashboard');
      $form .= "&nbsp;";
      $options = [];
      $options['value'] = $opt['end_year'] ?? date('Y');
      $options['rand'] = $rand;
      $options['display'] = false;
      $year = date("Y") - 3;
      for ($i = 0; $i <= 3; $i++) {
         $elements[$year] = $year;

         $year++;
      }

      $form .= Dropdown::showFromArray("end_year", $elements, $options);
      //            $form .= Dropdown::showNumber('end_year',$options);
      $form .= "</span>";
      $form .= "</span>";

   } else if ($_POST['value'] == 'YEAR') {


      $annee_courante = date('Y', time());
      if (isset($opt["year"])
          && $opt["year"] > 0) {
         $annee_courante = $opt["year"];
      }
      $form .= __('Year', 'mydashboard');
      $form .= "&nbsp;";
      $form .= PluginMydashboardHelper::YearDropdown($annee_courante);


   } else if ($_POST['value'] == 'BEGIN_END') {
      $form .= "<span class='md-widgetcrit'>";
      $form .= __('Start');
      $form .= "&nbsp;";
      $form .= Html::showDateTimeField("begin", ['value' => isset($opt['begin']) ? $opt['begin'] : null, 'maybeempty' => false, 'display' => false]);
      $form .= "</span>";
      $form .= "</br>";
      $form .= "<span class='md-widgetcrit'>";
      $form .= __('End');
      $form .= "&nbsp;";
      $form .= Html::showDateTimeField("end", ['value' => isset($opt['end']) ? $opt['end'] : null, 'maybeempty' => false, 'display' => false]);
      $form .= "</span>";
   }
//     $form .= "</span>";

     echo  $form;


}
