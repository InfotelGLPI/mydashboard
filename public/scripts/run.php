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

// CLI-only launcher: it boots the GLPI kernel with no memory/time limits and
// exec()s a heavy stock-ticket job. $_SERVER['argv'] is populated from the query
// string under the web SAPI (register_argc_argv), so the argv check below is NOT a
// CLI proof — gate on PHP_SAPI before anything runs, like the sibling scripts, so
// this cannot be abused as an unauthenticated DoS / side-effect trigger over HTTP.
if (PHP_SAPI !== 'cli') {
    die('This script can only be run from the command line.');
}

function usage()
{

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

// __DIR__ is the launcher's absolute directory regardless of the cwd or of how the
// script path was passed (relative or absolute), unlike $_SERVER['argv'][0] which
// stays relative after the chdir() above and would make realpath() return false.
define("GLPI_DIR_ROOT", realpath(__DIR__ . "/../../../.."));
require_once GLPI_DIR_ROOT . '/vendor/autoload.php';
$kernel = new \Glpi\Kernel\Kernel($options['env'] ?? null);
// Boot the kernel so the GLPI_* constants (GLPI_LOG_DIR, GLPI_LOCK_DIR) used below
// are defined.
$kernel->boot();

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
    # Unix/Linux
    $pids = [];
    $thread_nbr = 1;
    for ($i = 0; $i < $thread_nbr;) {
        $i++;
        $pid = pcntl_fork();
        if ($pid == -1) {
            fwrite($log, "Could not fork\n");
        } elseif ($pid) {
            fwrite($log, "$pid Started\n");
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
