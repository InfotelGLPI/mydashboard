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

class PluginMydashboardStockTicketIndicator extends CommonDBTM {

   const NEWT           =      1;
   const LATET          =   2;
   const PENDINGT        =   3;
   const INCIDENTPROGRESST        =   4;
   const REQUESTPROGRESST        =   5;
   const SOLVEDT        =   6;
   const CLOSEDT        =   7;

   public function cronMydashboardInfotelUpdateStockTicketIndicator() {
      global $DB;
      $year  = date("Y");
      $week = date("W") - 1;
      if ($week == 0) {
         $year  = $year - 1;
         $dt = new DateTime('December 28th, '.$year);
         $week = $dt->format('W');
      }
//      $nbdays  = date("t", mktime(0, 0, 0, $month, 1, $year));
      $query   = "SELECT COUNT(*) as count FROM glpi_plugin_mydashboard_stockticketindicators 
                  WHERE glpi_plugin_mydashboard_stockticketindicators.year = '$year' and glpi_plugin_mydashboard_stockticketindicators.week = '$week'";
      $results = $DB->query($query);
      $data    = $DB->fetchArray($results);
      if ($data["count"] > 0) {
         die("stock tickets of $year week $week is already filled");
      }
      echo "fill table <glpi_plugin_mydashboard_stockticketindactorss> with datas of $year week $week";

      self::queryNewTickets($year,$week);
      self::queryDueTickets($year,$week);
      self::queryPendingTickets($year,$week);
      self::queryIncidentTickets($year,$week);
      self::queryRequestTickets($year,$week);
      self::queryResolvedTickets($year,$week);
      self::queryClosedTickets($year,$week);
   }


   /**
    * @param $year
    * @param $week
    *
    * @return bool
    */
   static function queryNewTickets($year, $week) {
      global $DB;

      //New tickets
      $dbu     = new DbUtils();
      $sql_new = "SELECT COUNT(DISTINCT glpi_tickets.id) as total,
                    `glpi_tickets`.`entities_id`
                  FROM glpi_tickets
                  LEFT JOIN glpi_entities 
                  ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`)
                  WHERE `glpi_tickets`.`is_deleted` = 0 
                        AND `glpi_tickets`.`status` = " . Ticket::INCOMING . " GROUP BY `glpi_tickets`.`entities_id`";
      $results    = $DB->query($sql_new);
      while ($data = $DB->fetchArray($results)) {
         $query = "INSERT INTO `glpi_plugin_mydashboard_stockticketindicators` (`id`,`year`,`week`,`nbTickets`,`indicator_id`,`groups_id`,`entities_id`)
                        VALUES (NULL,$year, $week," . $data['total'] . ",".self::NEWT .",0,". $data['entities_id'] . ")";
         $DB->query($query);
      }



      return true;
   }

   /**
    * @param $year
    * @param $week
    *
    * @return bool
    */

   static function queryDueTickets($year, $week) {
      global $DB;


      $sql_due = "SELECT COUNT(DISTINCT glpi_tickets.id) AS due,
                    `glpi_tickets`.`entities_id`
                  FROM glpi_tickets
                  LEFT JOIN glpi_entities 
                  ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`)
                  LEFT JOIN `glpi_groups_tickets` 
                  ON `glpi_groups_tickets`.`tickets_id`=`glpi_tickets`.`id`
                  WHERE `glpi_tickets`.`is_deleted` = 0 
                        AND `glpi_tickets`.`status` NOT IN (" . Ticket::WAITING . "," . Ticket::SOLVED . ", " . Ticket::CLOSED . ")
                        AND `glpi_tickets`.`time_to_resolve` IS NOT NULL
                        AND `glpi_tickets`.`time_to_resolve` < NOW() 
                  GROUP BY `glpi_tickets`.`entities_id`";

      $results    = $DB->query($sql_due);
      while ($data = $DB->fetchArray($results)) {
         $query = "INSERT INTO `glpi_plugin_mydashboard_stockticketindicators` (`id`,`year`,`week`,`nbTickets`,`indicator_id`,`groups_id`,`entities_id`)
                        VALUES (NULL,$year, $week," . $data['due'] . ",".self::LATET .",0,". $data['entities_id'] . ")";
         $DB->query($query);
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
                        AND `glpi_tickets`.`status` NOT IN (" . Ticket::WAITING . "," . Ticket::SOLVED . ", " . Ticket::CLOSED . ")
                        AND `glpi_tickets`.`time_to_resolve` IS NOT NULL
                        AND `glpi_tickets`.`time_to_resolve` < NOW() 
                  GROUP BY `glpi_groups_tickets`.`groups_id`,`glpi_tickets`.`entities_id`";


         $results    = $DB->query($sql_due);
         while ($data = $DB->fetchArray($results)) {
         $query = "INSERT INTO `glpi_plugin_mydashboard_stockticketindicators` (`id`,`year`,`week`,`nbTickets`,`indicator_id`,`groups_id`,`entities_id`)
                        VALUES (NULL,$year, $week," . $data['due'] . ",".self::LATET .",".$data['groups_id'].",". $data['entities_id'] . ")";
         $DB->query($query);
      }

      return true;
   }

   /**
    * @param $year
    * @param $week
    *
    * @return bool
    */
   static function queryPendingTickets($year, $week) {
      global $DB;

      $dbu      = new DbUtils();
      $sql_pend = "SELECT COUNT(DISTINCT glpi_tickets.id) as total,
                    `glpi_tickets`.`entities_id`
                  FROM glpi_tickets
                    LEFT JOIN glpi_entities 
                  ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`)
                  WHERE `glpi_tickets`.`is_deleted` = 0 
                        AND `glpi_tickets`.`status` = " . Ticket::WAITING . "  
                  GROUP BY `glpi_tickets`.`entities_id`";

      $results    = $DB->query($sql_pend);
      while ($data = $DB->fetchArray($results)) {
         $query = "INSERT INTO `glpi_plugin_mydashboard_stockticketindicators` (`id`,`year`,`week`,`nbTickets`,`indicator_id`,`groups_id`,`entities_id`)
                        VALUES (NULL,$year, $week," . $data['total'] . ",".self::PENDINGT .",0,". $data['entities_id'] . ")";
         $DB->query($query);
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
                        AND `glpi_tickets`.`status` = " . Ticket::WAITING . "
                   GROUP BY `glpi_groups_tickets`.`groups_id`,`glpi_tickets`.`entities_id`";


      $results    = $DB->query($sql_pend);
      while ($data = $DB->fetchArray($results)) {
         $query = "INSERT INTO `glpi_plugin_mydashboard_stockticketindicators` (`id`,`year`,`week`,`nbTickets`,`indicator_id`,`groups_id`,`entities_id`)
                        VALUES (NULL,$year, $week," . $data['total'] . ",".self::PENDINGT .",".$data['groups_id'].",". $data['entities_id'] . ")";
         $DB->query($query);
      }

      return true;
   }

   /**
    * @param $year
    * @param $week
    *
    * @return bool
    */
   static function queryIncidentTickets($year, $week) {
      global $DB;

      $dbu      = new DbUtils();
      $statuses = [Ticket::SOLVED, Ticket::CLOSED, Ticket::WAITING, Ticket::INCOMING];


      $sql_incpro    = "SELECT COUNT(DISTINCT glpi_tickets.id) as total,
                    `glpi_tickets`.`entities_id`
                  FROM glpi_tickets
                  LEFT JOIN glpi_entities 
                  ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`)
                  WHERE `glpi_tickets`.`is_deleted` = 0 
                        AND `glpi_tickets`.`type` = '" . Ticket::INCIDENT_TYPE . "'
                        AND `glpi_tickets`.`status` NOT IN (" . implode(",", $statuses) . ") 
                  GROUP BY `glpi_tickets`.`entities_id`";

      $results    = $DB->query($sql_incpro);
      while ($data = $DB->fetchArray($results)) {
         $query = "INSERT INTO `glpi_plugin_mydashboard_stockticketindicators` (`id`,`year`,`week`,`nbTickets`,`indicator_id`,`groups_id`,`entities_id`)
                        VALUES (NULL,$year, $week," . $data['total'] . ",".self::INCIDENTPROGRESST .",0,". $data['entities_id'] . ")";
         $DB->query($query);
      }

      $sql_incpro    = "SELECT COUNT(DISTINCT glpi_tickets.id) as total,
                    `glpi_tickets`.`entities_id`,
                    `glpi_groups_tickets`.`groups_id`
                  FROM glpi_tickets
                  LEFT JOIN glpi_entities 
                  ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`)
                   LEFT JOIN `glpi_groups_tickets` 
                  ON `glpi_groups_tickets`.`tickets_id`=`glpi_tickets`.`id`
                  WHERE `glpi_tickets`.`is_deleted` = 0 
                        AND `glpi_tickets`.`type` = '" . Ticket::INCIDENT_TYPE . "'
                        AND `glpi_tickets`.`status` NOT IN (" . implode(",", $statuses) . ") 
                  GROUP BY `glpi_groups_tickets`.`groups_id`,`glpi_tickets`.`entities_id`";

      $results    = $DB->query($sql_incpro);
      while ($data = $DB->fetchArray($results)) {
         $query = "INSERT INTO `glpi_plugin_mydashboard_stockticketindicators` (`id`,`year`,`week`,`nbTickets`,`indicator_id`,`groups_id`,`entities_id`)
                        VALUES (NULL,$year, $week," . $data['total'] . ",".self::INCIDENTPROGRESST .",".$data['groups_id'].",". $data['entities_id'] . ")";
         $DB->query($query);
      }

      return true;
   }

   /**
    * @param $year
    * @param $week
    *
    * @return bool
    */
   static function queryRequestTickets($year, $week ) {
      global $DB;


      $statuses = [Ticket::SOLVED, Ticket::CLOSED, Ticket::WAITING, Ticket::INCOMING];


      $sql_dempro    = "SELECT COUNT(DISTINCT glpi_tickets.id) as total,
                    `glpi_tickets`.`entities_id`
                  FROM glpi_tickets
                   LEFT JOIN glpi_entities 
                  ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`)
                  WHERE `glpi_tickets`.`is_deleted` = 0 
                        AND `glpi_tickets`.`type` = '" . Ticket::DEMAND_TYPE . "'
                        AND `glpi_tickets`.`status` NOT IN (" . implode(",", $statuses) . ") 
                  GROUP BY `glpi_tickets`.`entities_id`";

      $results    = $DB->query($sql_dempro);
      while ($data = $DB->fetchArray($results)) {
         $query = "INSERT INTO `glpi_plugin_mydashboard_stockticketindicators` (`id`,`year`,`week`,`nbTickets`,`indicator_id`,`groups_id`,`entities_id`)
                        VALUES (NULL,$year, $week," . $data['total'] . ",".self::REQUESTPROGRESST .",0,". $data['entities_id'] . ")";
         $DB->query($query);
      }

      $sql_dempro    = "SELECT COUNT(DISTINCT glpi_tickets.id) as total,
                    `glpi_tickets`.`entities_id`,
                    `glpi_groups_tickets`.`groups_id`
                  FROM glpi_tickets
                   LEFT JOIN glpi_entities 
                  ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`)
                    LEFT JOIN `glpi_groups_tickets` 
                  ON `glpi_groups_tickets`.`tickets_id`=`glpi_tickets`.`id`
                  WHERE `glpi_tickets`.`is_deleted` = 0 
                        AND `glpi_tickets`.`type` = '" . Ticket::DEMAND_TYPE . "'
                        AND `glpi_tickets`.`status` NOT IN (" . implode(",", $statuses) . ") 
                  GROUP BY `glpi_groups_tickets`.`groups_id`,`glpi_tickets`.`entities_id`";

      $results    = $DB->query($sql_dempro);
      while ($data = $DB->fetchArray($results)) {
         $query = "INSERT INTO `glpi_plugin_mydashboard_stockticketindicators` (`id`,`year`,`week`,`nbTickets`,`indicator_id`,`groups_id`,`entities_id`)
                        VALUES (NULL,$year, $week," . $data['total'] . ",".self::REQUESTPROGRESST .",".$data['groups_id'].",". $data['entities_id'] . ")";
         $DB->query($query);
      }
      return true;
   }

   /**
    * @param $year
    * @param $week
    *
    * @return bool
    */
   static function queryResolvedTickets($year, $week) {
      global $DB;


      $sql_res = "SELECT COUNT(DISTINCT glpi_tickets.id) as total,
                    `glpi_tickets`.`entities_id`
                  FROM glpi_tickets
                    LEFT JOIN glpi_entities 
                  ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`)
                  WHERE `glpi_tickets`.`is_deleted` = 0 
                        AND WEEK(`glpi_tickets`.`solvedate`) = '$week'
                        AND YEAR(`glpi_tickets`.`solvedate`) = '$year'
                        AND `glpi_tickets`.`status` = " . Ticket::SOLVED . " 
                  GROUP BY `glpi_tickets`.`entities_id`";

      $results    = $DB->query($sql_res);
      while ($data = $DB->fetchArray($results)) {
         $query = "INSERT INTO `glpi_plugin_mydashboard_stockticketindicators` (`id`,`year`,`week`,`nbTickets`,`indicator_id`,`groups_id`,`entities_id`)
                        VALUES (NULL,$year, $week," . $data['total'] . ",".self::SOLVEDT .",0,". $data['entities_id'] . ")";
         $DB->query($query);
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
                        AND `glpi_tickets`.`status` = " . Ticket::SOLVED . " 
                  GROUP BY `glpi_groups_tickets`.`groups_id`,`glpi_tickets`.`entities_id`";

      $results    = $DB->query($sql_res);
      while ($data = $DB->fetchArray($results)) {
         $query = "INSERT INTO `glpi_plugin_mydashboard_stockticketindicators` (`id`,`year`,`week`,`nbTickets`,`indicator_id`,`groups_id`,`entities_id`)
                        VALUES (NULL,$year, $week," . $data['total'] . ",".self::SOLVEDT .",".$data['groups_id'].",". $data['entities_id'] . ")";
         $DB->query($query);
      }

      return true;
   }

   /**
    * @param $year
    * @param $week
    *
    * @return bool
    */
   static function queryClosedTickets($year, $week) {
      global $DB;


      $sql_res = "SELECT COUNT(DISTINCT glpi_tickets.id) as total,
                    `glpi_tickets`.`entities_id`
                  FROM glpi_tickets
                    LEFT JOIN glpi_entities 
                  ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`)
                  WHERE `glpi_tickets`.`is_deleted` = 0 
                        AND WEEK(`glpi_tickets`.`closedate`) = '$week'
                        AND YEAR(`glpi_tickets`.`closedate`) = '$year'
                        AND `glpi_tickets`.`status` = " . Ticket::CLOSED . " 
                  GROUP BY `glpi_tickets`.`entities_id`";

      $results    = $DB->query($sql_res);
      while ($data = $DB->fetchArray($results)) {
         $query = "INSERT INTO `glpi_plugin_mydashboard_stockticketindicators` (`id`,`year`,`week`,`nbTickets`,`indicator_id`,`groups_id`,`entities_id`)
                        VALUES (NULL,$year, $week," . $data['total'] . ",".self::CLOSEDT .",0,". $data['entities_id'] . ")";
         $DB->query($query);
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
                        AND `glpi_tickets`.`status` = " . Ticket::CLOSED . " 
                  GROUP BY `glpi_groups_tickets`.`groups_id`,`glpi_tickets`.`entities_id`";

      $results    = $DB->query($sql_res);
      while ($data = $DB->fetchArray($results)) {
         $query = "INSERT INTO `glpi_plugin_mydashboard_stockticketindicators` (`id`,`year`,`week`,`nbTickets`,`indicator_id`,`groups_id`,`entities_id`)
                        VALUES (NULL,$year, $week," . $data['total'] . ",".self::CLOSEDT .",".$data['groups_id'].",". $data['entities_id'] . ")";
         $DB->query($query);
      }

      return true;
   }
}
