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

use Glpi\DBAL\QueryExpression;

ini_set("memory_limit", "-1");
ini_set("max_execution_time", "0");

// Can't run on MySQL replicate
$USEDBREPLICATE = 0;
$DBCONNECTION_REQUIRED = 1;

chdir(dirname($_SERVER["SCRIPT_FILENAME"]));

define("GLPI_DIR_ROOT", realpath(dirname($_SERVER["SCRIPT_FILENAME"]) . "/../../../.."));
require_once GLPI_DIR_ROOT . '/vendor/autoload.php';
$kernel = new \Glpi\Kernel\Kernel($options['env'] ?? null);

global $DB;

$_SESSION["glpicronuserrunning"] = $_SESSION["glpiname"] = 'mydashboard';

// Chech Memory_limit - sometine cli limit (php-cli.ini) != module limit (php.ini)
$mem = Toolbox::getMemoryLimit();
if (($mem > 0) && ($mem < (64 * 1024 * 1024))) {
   die("PHP memory_limit = " . $mem . " - " . "A minimum of 64Mio is commonly required for GLPI.'\n\n");
}

//Check if plugin is installed
if (Plugin::isPluginActive("mydashboard")) {

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


    $is_deleted = ['glpi_tickets.is_deleted' => 0];
    $criteria = [
        'SELECT' => [
            new QueryExpression(
                "DATE_FORMAT(" . $DB->quoteName("date") . ", '%Y-%m') AS month"
            ),
            new QueryExpression(
                "DATE_FORMAT(" . $DB->quoteName("date") . ", '%b %Y') AS monthname"
            ),
            'glpi_tickets.entities_id',
        ],
        'DISTINCT'        => true,
        'FROM' => 'glpi_tickets',
        'WHERE' => [
            $is_deleted,
            [
                ['date' => ['>=', "$previousyear-$currentmonth-01 00:00:00"]],
                ['date' => ['<', "$currentyear-$currentmonth-01 00:00:00"]],
            ]
        ],
        'GROUPBY' => ['month', 'entities_id'],
    ];

    $iterator = $DB->request($criteria);

    foreach ($iterator as $data) {

      list($year, $month) = explode('-', $data['month']);
      $nbdays      = date("t", mktime(0, 0, 0, $month, 1, $year));
      $entities_id = $data["entities_id"];

        $criteria = [
            'SELECT' => [
                'COUNT' => 'id AS count',
            ],
            'FROM' => 'glpi_tickets',
            'WHERE' => [
                $is_deleted,
                'entities_id' => $entities_id,
                ['OR'         =>
                    [
                        ['date' => ['<=', "$year-$month-$nbdays 23:59:59"]],
                        ['status' => \Ticket::getNotSolvedStatusArray()],
                    ],
                    [
                        ['date' => ['<=', "$year-$month-$nbdays 23:59:59"]],
                        ['solvedate' => ['>', new QueryExpression("ADDDATE('$year-$month-$nbdays 00:00:00' , INTERVAL 1 DAY)")]],
                    ],
                ],
            ],
        ];
        $iterator = $DB->request($criteria);
        foreach ($iterator as $data2) {
            $countTicket = $data2['count'];
        }
        if ($countTicket > 0) {

          $DB->insert(
              'glpi_plugin_mydashboard_stocktickets',
              ['id' => NULL,
                  'date' => "$year-$month-$nbdays",
                  'nbstocktickets' => $countTicket,
                  'entities_id' => $entities_id,
              ]
          );
      }
   }
} else {
   echo __('Plugin disabled', 'mydashboard');
   exit(1);
}
