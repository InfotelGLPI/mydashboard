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

function usage() {

   echo "Usage:\n";
   echo "\t" . $_SERVER["argv"][0] . " [--args]\n";
   echo "\n\tArguments:\n";
}

if (!isset($_SERVER["argv"][0])) {
   header("HTTP/1.0 403 Forbidden");
   die("403 Forbidden");
}
ini_set("memory_limit", "-1");
ini_set("max_execution_time", "0");

chdir(dirname($_SERVER["argv"][0]));
define("GLPI_ROOT", realpath(dirname($_SERVER["argv"][0]) . "/../../.."));
require GLPI_ROOT . "/inc/based_config.php";

$logfilename = GLPI_LOG_DIR . "/insert_stock_tickets.log";

if (!is_writable(GLPI_LOCK_DIR)) {
   echo "\tERROR : " . GLPI_LOCK_DIR . " not writable\n";
   echo "\trun script as 'apache' user\n";
   exit(1);
}
$log = fopen($logfilename, "at");

//Only available with PHP5 or later
fwrite($log, date("r") . " " . $_SERVER["argv"][0] . " started\n");

if (function_exists("pcntl_fork")) {
   // Unix/Linux
   $pids = [];

   $i++;
   $pid = pcntl_fork();
   if ($pid == -1) {
      fwrite($log, "Could not fork\n");
   } else if ($pid) {
      fwrite($log, "$pid Started\n");
      file_put_contents($pidfile, ";" . $i . '$$$' . $pid, FILE_APPEND);
      $pids[$pid] = 1;
   } else {
      $cmd = "php -q -d -f insert_stock_tickets.php";

      $out = [];
      exec($cmd, $out, $ret);
      foreach ($out as $line) {
         fwrite($log, $line . "\n");
      }
      exit($ret);
   }

   $status = 0;
   while (count($pids)) {
      $pid = pcntl_wait($status);
      if ($pid < 0) {
         fwrite($log, "Cound not wait\n");
         exit(1);
      } else {
         unset($pids[$pid]);
         fwrite($log, "$pid ended, waiting for " . count($pids) . " running son process\n");
      }
   }
} else {
   // Windows - No fork, so Only one process :(
   $cmd = "php -q -d -f insert_stock_tickets.php";
   $out = [];
   $test = exec($cmd, $out, $ret);
   foreach ($out as $line) {
      fwrite($log, $line . "\n");
   }
}

fwrite($log, date("r") . " " . $_SERVER["argv"][0] . " ended\n\n");
