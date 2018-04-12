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


if ($_SESSION['glpiactiveprofile']['interface'] == 'central') {
   Html::header(PluginMydashboardMenu::getTypeName(1), '', "tools", "pluginmydashboardmenu");
} else {
   Html::helpHeader(PluginMydashboardMenu::getTypeName(1));
}

if (Session::haveRightsOr("plugin_mydashboard", array(READ, UPDATE))) {
   if (isset($_POST["add_ticket"])) {

      Ticket::showFormHelpdesk(Session::getLoginUserID(), $_POST["tickettemplates_id"]);

   } else {

      ?>
       <!--<!DOCTYPE html>-->
       <html>
       <head>
           <link type="text/css" href="../css/style_bootstrap_main.css" rel="stylesheet">
           <link type="text/css" href="../css/style_bootstrap_ticket.css" rel="stylesheet">
           <link type="text/css" href="../../../lib/font-awesome-4.7.0/css/font-awesome.min.css" rel="stylesheet">
           <link type="text/css" href="../lib/datatables/css/jquery.dataTables.min.css" rel="stylesheet">
           <link type="text/css" href="../lib/datatables/css/responsive.dataTables.min.css" rel="stylesheet">
           <link type="text/css" href="../lib/datatables/css/select.dataTables.min.css" rel="stylesheet">
           <link type="text/css" href="../lib/datatables/css/buttons.dataTables.min.css" rel="stylesheet">
           <link type="text/css" href="../lib/gridstack/src/gridstack.css" rel="stylesheet">
           <link type="text/css" href="../lib/gridstack/src/gridstack-extra.css" rel="stylesheet">

           <script src="../lib/lodash.min.js"></script>
           <script src="../lib/gridstack/src/gridstack.js"></script>
           <script src="../lib/gridstack/src/gridstack.jQueryUI.js"></script>
           <script src="../lib/datatables/js/jquery.dataTables.min.js"></script>
           <script src="../lib/datatables/js/dataTables.responsive.min.js"></script>
           <script src="../lib/moment.min.js"></script>
           <script src="../lib/datetime-moment.js"></script>
           <script src="../lib/chartjs/Chart.min.js"></script>
           <script src="../lib/circles/circles.min.js"></script>
           <script src="../lib/html2canvas.min.js"></script>
           <script src="../lib/datatables/js/dataTables.select.min.js"></script>
           <script src="../lib/datatables/js/dataTables.buttons.min.js"></script>
           <script src="../lib/jszip/js/jszip.min.js"></script>
           <script src="../lib/pdfmake/js/pdfmake.min.js"></script>
           <script src="../lib/pdfmake/js/vfs_fonts.js"></script>
           <script src="../lib/datatables/js/buttons.html5.min.js"></script>
           <script src="../lib/datatables/js/buttons.print.min.js"></script>
           <script src="../lib/countUp.min.js"></script>
           <script src="../lib/countUp-jquery.js"></script>

       </head>
       <body>

       <?php

       $profile = (isset($_SESSION['glpiactiveprofile']['id'])) ? $_SESSION['glpiactiveprofile']['id'] : -1;

       if (isset($_POST["profiles_id"])) {
          $profile = $_POST["profiles_id"];
       }
       $dashboard = new PluginMydashboardMenu();
       $dashboard->loadDashboard($profile);
       ?>

       </body>
       </html>

      <?php

   }
} else {
   Html::displayRightError();
}
if ($_SESSION['glpiactiveprofile']['interface'] == 'central') {
   Html::footer();
} else {
   Html::helpFooter();
}