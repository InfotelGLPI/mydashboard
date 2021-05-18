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
//$config = PluginPrintercountersConfig::getInstance();

if ($plugin->isActivated("mydashboard")) {
   $record = new PluginMydashboardStockTicketIndicator();
   $record->cronMydashboardInfotelUpdateStockTicketIndicator();
} else {
   echo __('Plugin disabled', 'mydashboard');
   exit(1);
}
