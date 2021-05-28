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

include('../../../inc/includes.php');

Session::checkLoginUser();

$plugin = new Plugin();

if (Session::getCurrentInterface() == 'central') {
   Html::header(PluginMydashboardMenu::getTypeName(1), '', "tools", "pluginmydashboardmenu");
} else {

   if ($plugin->isActivated('servicecatalog')) {
      PluginServicecatalogMain::showDefaultHeaderHelpdesk(PluginMydashboardMenu::getTypeName(1));
   } else {
      Html::helpHeader(PluginMydashboardMenu::getTypeName(1));
   }
}

if (Session::haveRightsOr("plugin_mydashboard", [READ, UPDATE])) {
   if (isset($_POST["add_ticket"])) {

      Ticket::showFormHelpdesk(Session::getLoginUserID(), $_POST["tickettemplates_id"]);

   } else {

      ?>
       <!--<!DOCTYPE html>-->
       <html>
       <head>
           <link type="text/css" href="../css/bootstrap4.css" rel="stylesheet">
           <link type="text/css" href="../css/style_bootstrap_main.css" rel="stylesheet">
           <link type="text/css" href="../css/style_bootstrap_ticket.css" rel="stylesheet">
           <!--DATATABLES CSS-->
           <link type="text/css" href="../lib/datatables/datatables.min.css" rel="stylesheet">
           <link type="text/css" href="../lib/datatables/Responsive-2.2.3/css/responsive.dataTables.min.css"
                 rel="stylesheet">
           <link type="text/css" href="../lib/datatables/Select-1.3.1/css/select.dataTables.min.css" rel="stylesheet">
           <link type="text/css" href="../lib/datatables/Buttons-1.6.1/css/buttons.dataTables.min.css" rel="stylesheet">
           <link type="text/css" href="../lib/datatables/ColReorder-1.5.2/css/colReorder.dataTables.min.css"
                 rel="stylesheet">

           <!--GLPI-->
           <link type="text/css" href="../../../public/lib/base.css" rel="stylesheet">
           <link type="text/css" href="../../../public/lib/gridstack.css" rel="stylesheet">
           <script src="../../../public/lib/gridstack.js"></script>


           <!--DATATABLES-->
           <script src="../lib/datatables/datatables.min.js"></script>
           <script src="../lib/datatables/Responsive-2.2.3/js/dataTables.responsive.min.js"></script>
           <script src="../lib/datatables/Select-1.3.1/js/dataTables.select.min.js"></script>
           <script src="../lib/datatables/Buttons-1.6.1/js/dataTables.buttons.min.js"></script>
           <script src="../lib/datatables/Buttons-1.6.1/js/buttons.html5.min.js"></script>
           <script src="../lib/datatables/Buttons-1.6.1/js/buttons.print.min.js"></script>
           <script src="../lib/datatables/Buttons-1.6.1/js/buttons.colVis.min.js"></script>
           <script src="../lib/datatables/ColReorder-1.5.2/js/dataTables.colReorder.min.js"></script>
           <script src="../lib/datatables/JSZip-2.5.0/jszip.min.js"></script>
           <script src="../lib/datatables/pdfmake-0.1.36/pdfmake.min.js"></script>
           <script src="../lib/datatables/pdfmake-0.1.36/vfs_fonts.js"></script>
           <!--MOMENT FOR DATATABLES-->
           <script src="../lib/fullcalendar/lib/moment.min.js"></script>
           <script src="../lib/datetime-moment.js"></script>
           <!--CHARTJS-->
           <script src="../lib/chartjs/Chart.bundle.min.js"></script>
           <script src="../lib/chartjs/chartjs-plugin-datalabels.js"></script>
           <!--EXPORT CHARTJS-->
           <script src="../lib/html2canvas.min.js"></script>
           <script src="../lib/fileSaver.min.js"></script>
           <!--CIRCLES STATS-->
           <script src="../lib/circles/circles.min.js"></script>
           <!--COUNTS-->
           <script src="../lib/countUp.min.js"></script>
           <script src="../lib/countUp-jquery.js"></script>

           <!--FULLCALENDAR-->
           <link type="text/css" href="../lib/fullcalendar/fullcalendar.min.css" rel="stylesheet">
           <script src="../lib/fullcalendar/fullcalendar.min.js"></script>

          <?php
          if (isset($_SESSION['glpilanguage'])) {
             foreach ([2, 3] as $loc) {
                $filename = "../lib/fullcalendar/locale/".
                            strtolower($CFG_GLPI["languages"][$_SESSION['glpilanguage']][$loc]).".js";
//                if (file_exists('../lib/fullcalendar/locale/' . $filename)) {
                   echo " <script src='$filename'></script>" ;
                   break;
//                }
             }
          }
          //$apikey = PluginMydashboardHelper::getGoogleApiKey();
          //if (!empty($apikey)) {
          //   echo "<script src='https://maps.googleapis.com/maps/api/js?key=$apikey'></script>";
          //}
          ?>

       </head>
       <body>

       <?php

       $profile         = (isset($_SESSION['glpiactiveprofile']['id'])) ? $_SESSION['glpiactiveprofile']['id'] : -1;
       $predefined_grid = 0;

       if (isset($_POST["profiles_id"])) {
          $profile = $_POST["profiles_id"];
       }
       if (isset($_POST["predefined_grid"])) {
          $predefined_grid = $_POST["predefined_grid"];
       }
       $dashboard = new PluginMydashboardMenu();
       $dashboard->loadDashboard($profile, $predefined_grid);
       //       $options=[];
       //       $dashboard->display($options);

       ?>

       </body>
       </html>

      <?php

   }
} else {
   Html::displayRightError();
}

if (Session::getCurrentInterface() != 'central'
    && $plugin->isActivated('servicecatalog')) {

   PluginServicecatalogMain::showNavBarFooter('mydashboard');
}

if (Session::getCurrentInterface() == 'central') {
   Html::footer();
} else {
   Html::helpFooter();
}
