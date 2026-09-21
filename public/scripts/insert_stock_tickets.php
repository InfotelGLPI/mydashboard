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

// Backward compatibility forwarder.
//
// Up to 2.3.8 this CLI script lived here, under public/. The GLPI 11 router both
// serves and executes a plugin's public/ tree -- public/scripts/x.php answers on
// /plugins/mydashboard/scripts/x.php -- so maintenance scripts have been moved one
// level up, to <plugin>/scripts/, out of the web root. System crons configured
// against the old path keep working through this forwarder.
//
// It is inert over HTTP: the CLI check below runs before anything is required.
// Update the crontab to <plugin>/scripts/insert_stock_tickets.php and this file can go away.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

fwrite(
    STDERR,
    'mydashboard: scripts/' . basename(__FILE__) . ' has moved out of public/.'
    . ' Point the cron at ' . realpath(__DIR__ . '/../../scripts') . DIRECTORY_SEPARATOR
    . basename(__FILE__) . ' instead.' . PHP_EOL,
);

require __DIR__ . '/../../scripts/insert_stock_tickets.php';
