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

namespace GlpiPlugin\Mydashboard;

use CommonDBTM;
use CommonITILObject;
use DBConnection;
use Glpi\DBAL\QueryExpression;
use Migration;

class StockTicket extends CommonDBTM
{
    public function cronMydashboardInfotelUpdateStockTicket()
    {
        global $DB;
        $year  = date("Y");
        $month = date("m") - 1;
        if ($month == 0) {
            $month = 12;
            $year  = $year - 1;
        }
        $nbdays  = date("t", mktime(0, 0, 0, $month, 1, $year));

        $criteria = [
            'SELECT' => [
                'COUNT' => 'id AS count',
            ],
            'FROM' => 'glpi_plugin_mydashboard_stocktickets',
            'WHERE' => [
                'date' => "$year-$month-$nbdays",
            ],
        ];
        $iterator = $DB->request($criteria);
        foreach ($iterator as $data) {
            if ($data["count"] > 0) {
                die("stock tickets of $year-$month is already filled");
            }
        }
        echo "fill table <glpi_plugin_mydashboard_stocktickets> with datas of $year-$month";
        $nbdays     = date("t", mktime(0, 0, 0, $month, 1, $year));

        $is_deleted = ['glpi_tickets.is_deleted' => 0];
//        $query      = "SELECT COUNT(*) as count,`glpi_tickets`.`entities_id` FROM `glpi_tickets`
//                        WHERE $is_deleted AND (((`glpi_tickets`.`date` <= '$year-$month-$nbdays 23:59:59')
//                        AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")))
//                        GROUP BY `glpi_tickets`.`entities_id`";

        $criteria = [
            'SELECT' => [
                'COUNT' => 'glpi_tickets.id AS count',
                'glpi_tickets.entities_id',
            ],
            'FROM' => 'glpi_tickets',
            'WHERE' => [
                $is_deleted,
                'glpi_tickets.status' => \Ticket::getNotSolvedStatusArray(),
                'date' => ['<=', "$year-$month-$nbdays 23:59:59"]
            ],
            'GROUPBY' => 'glpi_tickets.entities_id'
        ];
        $iterator = $DB->request($criteria);
        foreach ($iterator as $data) {
            $DB->insert(
                'glpi_plugin_mydashboard_stocktickets',
                ['id' => NULL,
                    'date' => "$year-$month-$nbdays",
                    'nbstocktickets' => $data['count'],
                    'entities_id' => $data['entities_id']]
            );
        }

//        $query   = "SELECT COUNT(*) as count,`glpi_tickets`.`entities_id`,`glpi_groups_tickets`.`groups_id` FROM `glpi_tickets`
//                 LEFT JOIN `glpi_groups_tickets` ON `glpi_groups_tickets`.`tickets_id`=`glpi_tickets`.`id`
//                  WHERE $is_deleted AND (((`glpi_tickets`.`date` <= '$year-$month-$nbdays 23:59:59')
//                  AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . "))) GROUP BY `glpi_groups_tickets`.`groups_id`,`glpi_tickets`.`entities_id`";

        $criteria = [
            'SELECT' => [
                'COUNT' => 'glpi_tickets.id AS count',
                'glpi_tickets.entities_id',
                'glpi_groups_tickets.groups_id'
            ],
            'FROM' => 'glpi_tickets',
            'LEFT JOIN'       => [
                'glpi_groups_tickets' => [
                    'ON' => [
                        'glpi_groups_tickets' => 'tickets_id',
                        'glpi_tickets'          => 'id'
                    ]
                ]
            ],
            'WHERE' => [
                $is_deleted,
                'glpi_tickets.status' => \Ticket::getNotSolvedStatusArray(),
                'date' => ['<=', "$year-$month-$nbdays 23:59:59"]
            ],
            'GROUPBY' => 'glpi_groups_tickets.groups_id, glpi_tickets.entities_id'
        ];

        $iterator = $DB->request($criteria);
        foreach ($iterator as $data) {
            $groups_id = $data["groups_id"];
            if (!empty($groups_id)) {
                $DB->insert(
                    'glpi_plugin_mydashboard_stocktickets',
                    ['id' => NULL,
                        'date' => "$year-$month-$nbdays",
                        'nbstocktickets' => $data['count'],
                        'entities_id' => $data['entities_id'],
                        'groups_id' => $data['groups_id']
                    ]
                );

            } else {
                $DB->insert(
                    'glpi_plugin_mydashboard_stocktickets',
                    ['id' => NULL,
                        'date' => "$year-$month-$nbdays",
                        'nbstocktickets' => $data['count'],
                        'entities_id' => $data['entities_id'],
                        'groups_id' => 0
                    ]
                );
            }
        }
    }


    static function fillTableMydashboardStocktickets()
    {
        global $DB;

        ini_set("memory_limit", "-1");
        ini_set("max_execution_time", "0");
        $currentmonth = date("m");
        $currentyear  = date("Y");
        $previousyear = $currentyear - 1;
//        $query        = "SELECT DISTINCT DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m') as month,
//                     DATE_FORMAT(`glpi_tickets`.`date`, '%b %Y') as monthname, `glpi_tickets`.`entities_id` "
//            . "FROM `glpi_tickets` "
//            . "WHERE `glpi_tickets`.`is_deleted`= 0 "
//            . "AND (`glpi_tickets`.`date` >= '$previousyear-$currentmonth-01 00:00:00') "
//            . "AND (`glpi_tickets`.`date` < '$currentyear-$currentmonth-01 00:00:00') "
//            . "GROUP BY DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m'), `glpi_tickets`.`entities_id`";
//        $results      = $DB->doQuery($query);

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
            'GROUPBY' => 'month, entities_id',
        ];

        $iterator = $DB->request($criteria);

        foreach ($iterator as $data) {

            [$year, $month] = explode('-', $data['month']);
            $nbdays      = date("t", mktime(0, 0, 0, $month, 1, $year));
            $entities_id = $data["entities_id"];

//            $query       = "SELECT COUNT(*) as count FROM `glpi_tickets`
//                  WHERE `glpi_tickets`.`is_deleted` = '0' AND `glpi_tickets`.`entities_id` = $entities_id
//                  AND (
//                      (
//                      (`glpi_tickets`.`date` <= '$year-$month-$nbdays 23:59:59')
//                  AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")
//                  )
//                  OR ((`glpi_tickets`.`date` <= '$year-$month-$nbdays 23:59:59')
//                  AND (`glpi_tickets`.`solvedate` > ADDDATE('$year-$month-$nbdays 00:00:00' , INTERVAL 1 DAY))))";

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
                        'entities_id' => $data['entities_id'],
                    ]
                );
            }
        }
    }

    static function fillTableMydashboardStockticketsGroup()
    {
        global $DB;

        ini_set("memory_limit", "-1");
        ini_set("max_execution_time", "0");
        $query   = "SELECT DISTINCT DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m') as month,
                     DATE_FORMAT(`glpi_tickets`.`date`, '%b %Y') as monthname, `glpi_tickets`.`entities_id`,
                      `glpi_groups_tickets`.`groups_id` as groups_id
      FROM `glpi_tickets`
      LEFT JOIN  `glpi_groups_tickets` ON `glpi_groups_tickets`.`tickets_id`=`glpi_tickets`.`id`
      WHERE `glpi_tickets`.`is_deleted`= 0
      GROUP BY DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m'), `glpi_tickets`.`entities_id`, `glpi_groups_tickets`.`groups_id`";
        $results = $DB->doQuery($query);
        while ($data = $DB->fetchArray($results)) {
            [$year, $month] = explode('-', $data['month']);
            $nbdays      = date("t", mktime(0, 0, 0, $month, 1, $year));
            $entities_id = $data["entities_id"];
            $groups_id   = $data["groups_id"];
            if (!empty($groups_id)) {
                $query       = "SELECT COUNT(*) as count FROM `glpi_tickets`
                  LEFT JOIN  `glpi_groups_tickets` ON `glpi_groups_tickets`.`tickets_id`=`glpi_tickets`.`id`
                  WHERE `glpi_tickets`.`is_deleted` = '0' AND `glpi_tickets`.`entities_id` = $entities_id AND `glpi_groups_tickets`.`groups_id` = $groups_id
                  AND (((`glpi_tickets`.`date` <= '$year-$month-$nbdays 23:59:59')
                  AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . "))
                  OR ((`glpi_tickets`.`date` <= '$year-$month-$nbdays 23:59:59')
                  AND (`glpi_tickets`.`solvedate` > ADDDATE('$year-$month-$nbdays 00:00:00' , INTERVAL 1 DAY))))";
                $results2    = $DB->doQuery($query);
                $data2       = $DB->fetchArray($results2);
                $countTicket = $data2['count'];
                if ($countTicket > 0) {

                    $DB->insert(
                        'glpi_plugin_mydashboard_stocktickets',
                        ['id' => NULL,
                            'date' => "$year-$month-$nbdays",
                            'nbstocktickets' => $countTicket,
                            'entities_id' => $entities_id,
                            'groups_id' => $groups_id,
                        ]
                    );
                }
            } else {
                $query       = "SELECT COUNT(*) as count FROM `glpi_tickets`
                  LEFT JOIN  `glpi_groups_tickets` ON `glpi_groups_tickets`.`tickets_id`=`glpi_tickets`.`id`
                  WHERE `glpi_tickets`.`is_deleted` = '0' AND `glpi_tickets`.`entities_id` = $entities_id AND `glpi_tickets`.`id` NOT IN  (SELECT tickets_id FROM `glpi_groups_tickets`)
                  AND (((`glpi_tickets`.`date` <= '$year-$month-$nbdays 23:59:59')
                  AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . "))
                  OR ((`glpi_tickets`.`date` <= '$year-$month-$nbdays 23:59:59')
                  AND (`glpi_tickets`.`solvedate` > ADDDATE('$year-$month-$nbdays 00:00:00' , INTERVAL 1 DAY))))";
                $results2    = $DB->doQuery($query);
                $data2       = $DB->fetchArray($results2);
                $countTicket = $data2['count'];
                if ($countTicket > 0) {

                    $DB->insert(
                        'glpi_plugin_mydashboard_stocktickets',
                        ['id' => NULL,
                            'date' => "$year-$month-$nbdays",
                            'nbstocktickets' => $countTicket,
                            'entities_id' => $entities_id,
                            'groups_id' => 0,
                        ]
                    );

                }
            }
        }
    }


    public static function install(Migration $migration)
    {
        global $DB;

        $default_charset   = DBConnection::getDefaultCharset();
        $default_collation = DBConnection::getDefaultCollation();
        $default_key_sign  = DBConnection::getDefaultPrimaryKeySignOption();
        $table  = self::getTable();

        if (!$DB->tableExists($table)) {
            $query = "CREATE TABLE `$table` (
                        `id` int {$default_key_sign} NOT NULL auto_increment,
                        `date`           DATE         NOT NULL,
                        `nbstocktickets` int {$default_key_sign} NOT NULL,
                        `entities_id`    int {$default_key_sign} NOT NULL,
                        `groups_id`      int {$default_key_sign} NOT NULL DEFAULT '0',
                        PRIMARY KEY (`id`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);

        }

        if (!$DB->fieldExists($table, "groups_id")) {
            $migration->addField($table, "groups_id", "int {$default_key_sign} NOT NULL DEFAULT '0'");
            $migration->migrationOneTable($table);
        }

        $migration->changeField($table, "groups_id", "groups_id", "int {$default_key_sign} NOT NULL DEFAULT '0'");
        $migration->migrationOneTable($table);

        $DB->update(
            $table,
            ['groups_id' => 0],
            ['groups_id' => -1],
        );
    }

    public static function uninstall()
    {
        global $DB;

        $DB->dropTable(self::getTable(), true);

    }
}
