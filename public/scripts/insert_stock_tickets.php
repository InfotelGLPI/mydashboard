<?php

/**
 * -------------------------------------------------------------------------
 * mydashboard plugin for GLPI
 * Copyright (C) 2016-2026 by the mydashboard Development Team.
 *
 * https://github.com/InfotelGLPI/mydashboard
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of mydashboard.
 *
 * mydashboard is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * mydashboard is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with mydashboard. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

use GlpiPlugin\Mydashboard\StockTicket;

// This maintenance script performs an unbounded scan of glpi_tickets and forges a
// cron session; it is designed to be run from the command line (see run.php).
// Because it lives under public/ (web-routed by GLPI 11), refuse any web request
// to close a denial-of-service vector reachable without authentication.
if (PHP_SAPI !== 'cli') {
    die('This script can only be run from the command line.');
}

ini_set("memory_limit", "-1");
ini_set("max_execution_time", "0");

// Can't run on MySQL replicate
$USEDBREPLICATE = 0;
$DBCONNECTION_REQUIRED = 1;

chdir(dirname($_SERVER["SCRIPT_FILENAME"]));

// __DIR__ is the script's absolute directory regardless of the cwd or of how the
// script path was passed (relative or absolute), unlike $_SERVER['SCRIPT_FILENAME']
// which stays relative after the chdir() above and would make realpath() return false.
define("GLPI_DIR_ROOT", realpath(__DIR__ . "/../../../.."));
require_once GLPI_DIR_ROOT . '/vendor/autoload.php';
$kernel = new \Glpi\Kernel\Kernel($options['env'] ?? null);
// Boot the kernel so the DB connection ($DB), $CFG_GLPI and the GLPI_* constants
// are initialized; without it $DB stays null and any query fatals.
$kernel->boot();

$_SESSION["glpicronuserrunning"] = $_SESSION["glpiname"] = 'mydashboard';

// Chech Memory_limit - sometine cli limit (php-cli.ini) != module limit (php.ini)
$mem = Toolbox::getMemoryLimit();
if (($mem > 0) && ($mem < (64 * 1024 * 1024))) {
    die("PHP memory_limit = " . $mem . " - " . "A minimum of 64Mio is commonly required for GLPI.'\n\n");
}

//Check if plugin is installed
if (Plugin::isPluginActive("mydashboard")) {
    $record = new StockTicket();
    $record->cronMydashboardInfotelUpdateStockTicket();
} else {
    echo __('Plugin disabled', 'mydashboard');
    exit(1);
}
