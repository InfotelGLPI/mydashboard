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
use DateTime;
use DBConnection;
use Migration;

class StockTicketIndicator extends CommonDBTM
{
    public const NEWT              = 1;
    public const LATET             = 2;
    public const PENDINGT          = 3;
    public const INCIDENTPROGRESST = 4;
    public const REQUESTPROGRESST  = 5;
    public const SOLVEDT           = 6;
    public const CLOSEDT           = 7;

    public function cronMydashboardInfotelUpdateStockTicketIndicator($type = "week")
    {
        global $DB;

        $year = date("Y");
        if ($type == "week") {
            $week = date("W") - 1;

            if ($week == 0) {
                $year = $year - 1;
                $dt   = new DateTime('December 28th, ' . $year);
                $week = $dt->format('W');
            }

//            $query   = "SELECT COUNT(*) as count FROM glpi_plugin_mydashboard_stockticketindicators
//                  WHERE glpi_plugin_mydashboard_stockticketindicators.year = '$year'
//                      AND glpi_plugin_mydashboard_stockticketindicators.week = '$week'";

            $criteria = [
                'SELECT' => [
                    'COUNT' => 'id AS count',
                ],
                'FROM' => 'glpi_plugin_mydashboard_stockticketindicators',
                'WHERE' => [
                    'year' => $year,
                    'week' => $week,
                ],
            ];
            $iterator = $DB->request($criteria);
            foreach ($iterator as $data) {
                if ($data["count"] > 0) {
                    die("stock tickets of $year week $week is already filled");
                }
            }
            echo "fill table with datas of $year week $week";
        }
        if ($type == "all") {
            for ($i = 1; $i <= 52; $i++) {
                self::queryNewTickets($year, $i);
                self::queryDueTickets($year, $i);
                self::queryPendingTickets($year, $i);
                self::queryIncidentTickets($year, $i);
                self::queryRequestTickets($year, $i);
                self::queryResolvedTickets($year, $i);
                self::queryClosedTickets($year, $i);

            }
        } else {
            self::queryNewTickets($year, $week);
            self::queryDueTickets($year, $week);
            self::queryPendingTickets($year, $week);
            self::queryIncidentTickets($year, $week);
            self::queryRequestTickets($year, $week);
            self::queryResolvedTickets($year, $week);
            self::queryClosedTickets($year, $week);
        }
    }


    /**
     * @param $year
     * @param $week
     *
     * @return bool
     */
    public static function queryNewTickets($year, $week)
    {
        global $DB;

        //New tickets
        $sql_new = "SELECT COUNT(DISTINCT glpi_tickets.id) as total,
                    `glpi_tickets`.`entities_id`
                  FROM glpi_tickets
                  LEFT JOIN glpi_entities
                  ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`)
                  WHERE `glpi_tickets`.`is_deleted` = 0
                        AND WEEK(`glpi_tickets`.`date`) = '$week'
                        AND YEAR(`glpi_tickets`.`date`) = '$year'
                        AND `glpi_tickets`.`status` = " . \Ticket::INCOMING . "
                        GROUP BY `glpi_tickets`.`entities_id`";
        $results = $DB->doQuery($sql_new);
        while ($data = $DB->fetchArray($results)) {

            $DB->insert(
                'glpi_plugin_mydashboard_stockticketindicators',
                ['id' => NULL,
                    'year' => $year,
                    'week' => $week,
                    'nbTickets' => $data['total'],
                    'indicator_id' => self::NEWT,
                    'groups_id' => 0,
                    'entities_id' => $data['entities_id'],
                ]
            );
        }


        return true;
    }

    /**
     * @param $year
     * @param $week
     *
     * @return bool
     */

    public static function queryDueTickets($year, $week)
    {
        global $DB;

        $sql_due = "SELECT COUNT(DISTINCT glpi_tickets.id) AS due,
                    `glpi_tickets`.`entities_id`
                  FROM glpi_tickets
                  LEFT JOIN glpi_entities
                  ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`)
                  WHERE `glpi_tickets`.`is_deleted` = 0
                        AND WEEK(`glpi_tickets`.`date`) = '$week'
                        AND YEAR(`glpi_tickets`.`date`) = '$year'
                        AND `glpi_tickets`.`status` NOT IN (" . \Ticket::INCOMING . "," . \Ticket::WAITING . "," . \Ticket::SOLVED . ", " . \Ticket::CLOSED . ")
                        AND `glpi_tickets`.`time_to_resolve` IS NOT NULL
                        AND `glpi_tickets`.`time_to_resolve` < NOW()
                  GROUP BY `glpi_tickets`.`entities_id`";

        $results = $DB->doQuery($sql_due);
        while ($data = $DB->fetchArray($results)) {

            $DB->insert(
                'glpi_plugin_mydashboard_stockticketindicators',
                ['id' => NULL,
                    'year' => $year,
                    'week' => $week,
                    'nbTickets' => $data['due'],
                    'indicator_id' => self::LATET,
                    'groups_id' => 0,
                    'entities_id' => $data['entities_id'],
                ]
            );

        }

        $sql_due = "SELECT COUNT(DISTINCT glpi_tickets.id) AS due,
                    `glpi_tickets`.`entities_id`,
                    `glpi_groups_tickets`.`groups_id`
                  FROM glpi_tickets
                  LEFT JOIN glpi_entities
                  ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`)
                  LEFT JOIN `glpi_groups_tickets`
                  ON `glpi_groups_tickets`.`tickets_id`=`glpi_tickets`.`id`
                  WHERE `glpi_tickets`.`is_deleted` = 0
                        AND WEEK(`glpi_tickets`.`date`) = '$week'
                        AND YEAR(`glpi_tickets`.`date`) = '$year'
                        AND `glpi_tickets`.`status` NOT IN (" . \Ticket::WAITING . "," . \Ticket::SOLVED . ", " . \Ticket::CLOSED . ")
                        AND `glpi_tickets`.`time_to_resolve` IS NOT NULL
                        AND `glpi_tickets`.`time_to_resolve` < NOW()
                  GROUP BY `glpi_groups_tickets`.`groups_id`,`glpi_tickets`.`entities_id`";


        $results = $DB->doQuery($sql_due);
        while ($data = $DB->fetchArray($results)) {
            if (isset($data['groups_id']) && $data['groups_id'] > 0) {

                $DB->insert(
                    'glpi_plugin_mydashboard_stockticketindicators',
                    ['id' => NULL,
                        'year' => $year,
                        'week' => $week,
                        'nbTickets' => $data['due'],
                        'indicator_id' => self::LATET,
                        'groups_id' => $data['groups_id'],
                        'entities_id' => $data['entities_id'],
                    ]
                );
            }
        }

        return true;
    }

    /**
     * @param $year
     * @param $week
     *
     * @return bool
     */
    public static function queryPendingTickets($year, $week)
    {
        global $DB;

        $sql_pend = "SELECT COUNT(DISTINCT glpi_tickets.id) as total,
                    `glpi_tickets`.`entities_id`
                  FROM glpi_tickets
                    LEFT JOIN glpi_entities
                  ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`)
                  WHERE `glpi_tickets`.`is_deleted` = 0
                        AND WEEK(`glpi_tickets`.`date`) = '$week'
                        AND YEAR(`glpi_tickets`.`date`) = '$year'
                        AND `glpi_tickets`.`status` = " . \Ticket::WAITING . "
                  GROUP BY `glpi_tickets`.`entities_id`";

        $results = $DB->doQuery($sql_pend);
        while ($data = $DB->fetchArray($results)) {

            $DB->insert(
                'glpi_plugin_mydashboard_stockticketindicators',
                ['id' => NULL,
                    'year' => $year,
                    'week' => $week,
                    'nbTickets' => $data['total'],
                    'indicator_id' => self::PENDINGT,
                    'groups_id' => 0,
                    'entities_id' => $data['entities_id'],
                ]
            );

        }

        $sql_pend = "SELECT COUNT(DISTINCT glpi_tickets.id) as total,
                    `glpi_tickets`.`entities_id`,
                    `glpi_groups_tickets`.`groups_id`
                  FROM glpi_tickets
                   LEFT JOIN glpi_entities
                  ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`)
                   LEFT JOIN `glpi_groups_tickets`
                  ON `glpi_groups_tickets`.`tickets_id`=`glpi_tickets`.`id`
                  WHERE `glpi_tickets`.`is_deleted` = 0
                        AND WEEK(`glpi_tickets`.`date`) = '$week'
                        AND YEAR(`glpi_tickets`.`date`) = '$year'
                        AND `glpi_tickets`.`status` = " . \Ticket::WAITING . "
                   GROUP BY `glpi_groups_tickets`.`groups_id`,`glpi_tickets`.`entities_id`";


        $results = $DB->doQuery($sql_pend);
        while ($data = $DB->fetchArray($results)) {
            if (isset($data['groups_id']) && $data['groups_id'] > 0) {

                $DB->insert(
                    'glpi_plugin_mydashboard_stockticketindicators',
                    ['id' => NULL,
                        'year' => $year,
                        'week' => $week,
                        'nbTickets' => $data['total'],
                        'indicator_id' => self::PENDINGT,
                        'groups_id' => $data['groups_id'],
                        'entities_id' => $data['entities_id'],
                    ]
                );

            }
        }

        return true;
    }

    /**
     * @param $year
     * @param $week
     *
     * @return bool
     */
    public static function queryIncidentTickets($year, $week)
    {
        global $DB;

        $statuses = [\Ticket::SOLVED, \Ticket::CLOSED, \Ticket::WAITING, \Ticket::INCOMING];


        $sql_incpro = "SELECT COUNT(DISTINCT glpi_tickets.id) as total,
                    `glpi_tickets`.`entities_id`
                  FROM glpi_tickets
                  LEFT JOIN glpi_entities
                  ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`)
                  WHERE `glpi_tickets`.`is_deleted` = 0
                        AND WEEK(`glpi_tickets`.`date`) = '$week'
                        AND YEAR(`glpi_tickets`.`date`) = '$year'
                        AND `glpi_tickets`.`type` = '" . \Ticket::INCIDENT_TYPE . "'
                        AND `glpi_tickets`.`status` NOT IN (" . implode(",", $statuses) . ")
                  GROUP BY `glpi_tickets`.`entities_id`";

        $results = $DB->doQuery($sql_incpro);
        while ($data = $DB->fetchArray($results)) {

            $DB->insert(
                'glpi_plugin_mydashboard_stockticketindicators',
                ['id' => NULL,
                    'year' => $year,
                    'week' => $week,
                    'nbTickets' => $data['total'],
                    'indicator_id' => self::INCIDENTPROGRESST,
                    'groups_id' => 0,
                    'entities_id' => $data['entities_id'],
                ]
            );
        }

        $sql_incpro = "SELECT COUNT(DISTINCT glpi_tickets.id) as total,
                    `glpi_tickets`.`entities_id`,
                    `glpi_groups_tickets`.`groups_id`
                  FROM glpi_tickets
                  LEFT JOIN glpi_entities
                  ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`)
                   LEFT JOIN `glpi_groups_tickets`
                  ON `glpi_groups_tickets`.`tickets_id`=`glpi_tickets`.`id`
                  WHERE `glpi_tickets`.`is_deleted` = 0
                        AND WEEK(`glpi_tickets`.`date`) = '$week'
                        AND YEAR(`glpi_tickets`.`date`) = '$year'
                        AND `glpi_tickets`.`type` = '" . \Ticket::INCIDENT_TYPE . "'
                        AND `glpi_tickets`.`status` NOT IN (" . implode(",", $statuses) . ")
                  GROUP BY `glpi_groups_tickets`.`groups_id`,`glpi_tickets`.`entities_id`";

        $results = $DB->doQuery($sql_incpro);
        while ($data = $DB->fetchArray($results)) {
            if (isset($data['groups_id']) && $data['groups_id'] > 0) {

                $DB->insert(
                    'glpi_plugin_mydashboard_stockticketindicators',
                    ['id' => NULL,
                        'year' => $year,
                        'week' => $week,
                        'nbTickets' => $data['total'],
                        'indicator_id' => self::INCIDENTPROGRESST,
                        'groups_id' => $data['groups_id'],
                        'entities_id' => $data['entities_id'],
                    ]
                );
            }
        }

        return true;
    }

    /**
     * @param $year
     * @param $week
     *
     * @return bool
     */
    public static function queryRequestTickets($year, $week)
    {
        global $DB;

        $statuses = [\Ticket::SOLVED, \Ticket::CLOSED, \Ticket::WAITING, \Ticket::INCOMING];

        $sql_dempro = "SELECT COUNT(DISTINCT glpi_tickets.id) as total,
                    `glpi_tickets`.`entities_id`
                  FROM glpi_tickets
                   LEFT JOIN glpi_entities
                  ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`)
                  WHERE `glpi_tickets`.`is_deleted` = 0
                        AND WEEK(`glpi_tickets`.`date`) = '$week'
                        AND YEAR(`glpi_tickets`.`date`) = '$year'
                        AND `glpi_tickets`.`type` = '" . \Ticket::DEMAND_TYPE . "'
                        AND `glpi_tickets`.`status` NOT IN (" . implode(",", $statuses) . ")
                  GROUP BY `glpi_tickets`.`entities_id`";

        $results = $DB->doQuery($sql_dempro);
        while ($data = $DB->fetchArray($results)) {

            $DB->insert(
                'glpi_plugin_mydashboard_stockticketindicators',
                ['id' => NULL,
                    'year' => $year,
                    'week' => $week,
                    'nbTickets' => $data['total'],
                    'indicator_id' => self::REQUESTPROGRESST,
                    'groups_id' => 0,
                    'entities_id' => $data['entities_id'],
                ]
            );
        }

        $sql_dempro = "SELECT COUNT(DISTINCT glpi_tickets.id) as total,
                    `glpi_tickets`.`entities_id`,
                    `glpi_groups_tickets`.`groups_id`
                  FROM glpi_tickets
                   LEFT JOIN glpi_entities
                  ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`)
                    LEFT JOIN `glpi_groups_tickets`
                  ON `glpi_groups_tickets`.`tickets_id`=`glpi_tickets`.`id`
                  WHERE `glpi_tickets`.`is_deleted` = 0
                        AND WEEK(`glpi_tickets`.`date`) = '$week'
                        AND YEAR(`glpi_tickets`.`date`) = '$year'
                        AND `glpi_tickets`.`type` = '" . \Ticket::DEMAND_TYPE . "'
                        AND `glpi_tickets`.`status` NOT IN (" . implode(",", $statuses) . ")
                  GROUP BY `glpi_groups_tickets`.`groups_id`,`glpi_tickets`.`entities_id`";

        $results = $DB->doQuery($sql_dempro);
        while ($data = $DB->fetchArray($results)) {
            if (isset($data['groups_id']) && $data['groups_id'] > 0) {
                $DB->insert(
                    'glpi_plugin_mydashboard_stockticketindicators',
                    ['id' => NULL,
                        'year' => $year,
                        'week' => $week,
                        'nbTickets' => $data['total'],
                        'indicator_id' => self::REQUESTPROGRESST,
                        'groups_id' => $data['groups_id'],
                        'entities_id' => $data['entities_id'],
                    ]
                );
            }

        }
        return true;
    }

    /**
     * @param $year
     * @param $week
     *
     * @return bool
     */
    public static function queryResolvedTickets($year, $week)
    {
        global $DB;

        $sql_res = "SELECT COUNT(DISTINCT glpi_tickets.id) as total,
                    `glpi_tickets`.`entities_id`
                  FROM glpi_tickets
                    LEFT JOIN glpi_entities
                  ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`)
                  WHERE `glpi_tickets`.`is_deleted` = 0
                        AND WEEK(`glpi_tickets`.`solvedate`) = '$week'
                        AND YEAR(`glpi_tickets`.`solvedate`) = '$year'
                        AND `glpi_tickets`.`status` = " . \Ticket::SOLVED . "
                  GROUP BY `glpi_tickets`.`entities_id`";

        $results = $DB->doQuery($sql_res);
        while ($data = $DB->fetchArray($results)) {

            $DB->insert(
                'glpi_plugin_mydashboard_stockticketindicators',
                ['id' => NULL,
                    'year' => $year,
                    'week' => $week,
                    'nbTickets' => $data['total'],
                    'indicator_id' => self::SOLVEDT,
                    'groups_id' => 0,
                    'entities_id' => $data['entities_id'],
                ]
            );
        }

        $sql_res = "SELECT COUNT(DISTINCT glpi_tickets.id) as total,
                    `glpi_tickets`.`entities_id`,
                    `glpi_groups_tickets`.`groups_id`
                  FROM glpi_tickets
                    LEFT JOIN glpi_entities
                  ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`)
                   LEFT JOIN `glpi_groups_tickets`
                  ON `glpi_groups_tickets`.`tickets_id`=`glpi_tickets`.`id`
                  WHERE `glpi_tickets`.`is_deleted` = 0
                        AND WEEK(`glpi_tickets`.`solvedate`) = '$week'
                        AND YEAR(`glpi_tickets`.`solvedate`) = '$year'
                        AND `glpi_tickets`.`status` = " . \Ticket::SOLVED . "
                  GROUP BY `glpi_groups_tickets`.`groups_id`,`glpi_tickets`.`entities_id`";

        $results = $DB->doQuery($sql_res);
        while ($data = $DB->fetchArray($results)) {
            if (isset($data['groups_id']) && $data['groups_id'] > 0) {

                $DB->insert(
                    'glpi_plugin_mydashboard_stockticketindicators',
                    ['id' => NULL,
                        'year' => $year,
                        'week' => $week,
                        'nbTickets' => $data['total'],
                        'indicator_id' => self::SOLVEDT,
                        'groups_id' => $data['groups_id'],
                        'entities_id' => $data['entities_id'],
                    ]
                );
            }
        }

        return true;
    }

    /**
     * @param $year
     * @param $week
     *
     * @return bool
     */
    public static function queryClosedTickets($year, $week)
    {
        global $DB;

        $sql_res = "SELECT COUNT(DISTINCT glpi_tickets.id) as total,
                    `glpi_tickets`.`entities_id`
                  FROM glpi_tickets
                    LEFT JOIN glpi_entities
                  ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`)
                  WHERE `glpi_tickets`.`is_deleted` = 0
                        AND WEEK(`glpi_tickets`.`closedate`) = '$week'
                        AND YEAR(`glpi_tickets`.`closedate`) = '$year'
                        AND `glpi_tickets`.`status` = " . \Ticket::CLOSED . "
                  GROUP BY `glpi_tickets`.`entities_id`";

        $results = $DB->doQuery($sql_res);
        while ($data = $DB->fetchArray($results)) {

            $DB->insert(
                'glpi_plugin_mydashboard_stockticketindicators',
                ['id' => NULL,
                    'year' => $year,
                    'week' => $week,
                    'nbTickets' => $data['total'],
                    'indicator_id' => self::CLOSEDT,
                    'groups_id' => 0,
                    'entities_id' => $data['entities_id'],
                ]
            );

        }

        $sql_res = "SELECT COUNT(DISTINCT glpi_tickets.id) as total,
                    `glpi_tickets`.`entities_id`,
                    `glpi_groups_tickets`.`groups_id`
                  FROM glpi_tickets
                    LEFT JOIN glpi_entities
                  ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`)
                   LEFT JOIN `glpi_groups_tickets`
                  ON `glpi_groups_tickets`.`tickets_id`=`glpi_tickets`.`id`
                  WHERE `glpi_tickets`.`is_deleted` = 0
                        AND WEEK(`glpi_tickets`.`closedate`) = '$week'
                        AND YEAR(`glpi_tickets`.`closedate`) = '$year'
                        AND `glpi_tickets`.`status` = " . \Ticket::CLOSED . "
                  GROUP BY `glpi_groups_tickets`.`groups_id`,`glpi_tickets`.`entities_id`";

        $results = $DB->doQuery($sql_res);
        while ($data = $DB->fetchArray($results)) {
            if (isset($data['groups_id']) && $data['groups_id'] > 0) {

                $DB->insert(
                    'glpi_plugin_mydashboard_stockticketindicators',
                    ['id' => NULL,
                        'year' => $year,
                        'week' => $week,
                        'nbTickets' => $data['total'],
                        'indicator_id' => self::CLOSEDT,
                        'groups_id' => $data['groups_id'],
                        'entities_id' => $data['entities_id'],
                    ]
                );
            }
        }

        return true;
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
                        `id`           int {$default_key_sign} AUTO_INCREMENT,
                        `year`         int {$default_key_sign} NOT NULL,
                        `week`         int {$default_key_sign} NOT NULL,
                        `nbTickets`    int {$default_key_sign} NOT NULL,
                        `indicator_id` int {$default_key_sign} NOT NULL,
                        `groups_id`    int {$default_key_sign} NOT NULL DEFAULT 0,
                        `entities_id`  int {$default_key_sign} NOT NULL,
                        PRIMARY KEY (`id`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);

        }

        $migration->changeField($table, "groups_id", "groups_id", "int {$default_key_sign} NOT NULL DEFAULT '0'");
        $migration->migrationOneTable($table);
    }

    public static function uninstall()
    {
        global $DB;

        $DB->dropTable(self::getTable(), true);

    }
}
