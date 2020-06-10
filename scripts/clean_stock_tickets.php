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

ini_set("memory_limit", "-1");
ini_set("max_execution_time", "0");

// Can't run on MySQL replicate
$USEDBREPLICATE = 0;
$DBCONNECTION_REQUIRED = 1;

chdir(dirname($_SERVER["SCRIPT_FILENAME"]));

include('../../../inc/includes.php');


$_SESSION["glpicronuserrunning"] = $_SESSION["glpiname"] = 'mydashboard';

// Chech Memory_limit - sometine cli limit (php-cli.ini) != module limit (php.ini)
$mem = Toolbox::getMemoryLimit();
if (($mem > 0) && ($mem < (64 * 1024 * 1024))) {
   die("PHP memory_limit = " . $mem . " - " . "A minimum of 64Mio is commonly required for GLPI.'\n\n");
}

//Check if plugin is installed
$plugin = new Plugin();

if ($plugin->isActivated("mydashboard")) {

   $currentmonth = date("m");
   $currentyear  = date("Y");
   $previousyear = $currentyear - 1;

   $query        = "SELECT DISTINCT DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m') as month,
                     DATE_FORMAT(`glpi_tickets`.`date`, '%b %Y') as monthname, `glpi_tickets`.`entities_id` "
                   . "FROM `glpi_tickets` "
                   . "WHERE `glpi_tickets`.`is_deleted`= 0 "
                   . "AND (`glpi_tickets`.`date` >= '$previousyear-$currentmonth-01 00:00:00') "
                   . "AND (`glpi_tickets`.`date` < '$currentyear-$currentmonth-01 00:00:00') "
                   . "GROUP BY DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m'), `glpi_tickets`.`entities_id`";

   $results      = $DB->query($query);
   while ($data = $DB->fetchArray($results)) {
      list($year, $month) = explode('-', $data['month']);
      $nbdays      = date("t", mktime(0, 0, 0, $month, 1, $year));
      $entities_id = $data["entities_id"];
      $query       = "SELECT COUNT(*) as count FROM `glpi_tickets`
                  WHERE `glpi_tickets`.`is_deleted` = '0' AND `glpi_tickets`.`entities_id` = $entities_id
                  AND (((`glpi_tickets`.`date` <= '$year-$month-$nbdays 23:59:59') 
                  AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")) 
                  OR ((`glpi_tickets`.`date` <= '$year-$month-$nbdays 23:59:59') 
                  AND (`glpi_tickets`.`solvedate` > ADDDATE('$year-$month-$nbdays 00:00:00' , INTERVAL 1 DAY))))";
      $results2    = $DB->query($query);
      $data2       = $DB->fetchArray($results2);
      $countTicket = $data2['count'];
      if ($countTicket > 0) {
         $query = "INSERT INTO `glpi_plugin_mydashboard_stocktickets` (`id`,`date`,`nbstocktickets`,`entities_id`) 
                              VALUES (NULL,'$year-$month-$nbdays'," . $countTicket . "," . $entities_id . ")";
         $DB->query($query);
      }
   }
} else {
   echo __('Plugin disabled', 'mydashboard');
   exit(1);
}