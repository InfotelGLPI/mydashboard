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
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 mydashboard is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with mydashboard. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

use GlpiPlugin\Mydashboard\Criterias\Month;

if (strpos($_SERVER['PHP_SELF'], "dropdownMonth.php")) {
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

Session::checkRightsOr("plugin_mydashboard", [READ, CREATE + UPDATE]);

// Make a select box
if (isset($_POST["value"])) {
  if($_POST['value']== "MONTH"){
     $form = "";
     $form .= "</br></br>";
     $form .= __('Month', 'mydashboard');
     $form .= "&nbsp;";
     $form .= Month::monthDropdown("month_year", (isset($opt['month_year']) ? $opt['month_year'] : 0));

     echo  $form;
  }

}
