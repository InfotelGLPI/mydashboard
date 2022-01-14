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

class PluginMydashboardStockTicket extends CommonDBTM {

   public function cronMydashboardInfotelUpdateStockTicket() {
      global $DB;
      $year  = date("Y");
      $month = date("m") - 1;
      if ($month == 0) {
         $month = 12;
         $year  = $year - 1;
      }
      $nbdays  = date("t", mktime(0, 0, 0, $month, 1, $year));
      $query   = "SELECT COUNT(*) as count FROM glpi_plugin_mydashboard_stocktickets 
                  WHERE glpi_plugin_mydashboard_stocktickets.date = '$year-$month-$nbdays'";
      $results = $DB->query($query);
      $data    = $DB->fetchArray($results);
      if ($data["count"] > 0) {
         die("stock tickets of $year-$month is already filled");
      }
      echo "fill table <glpi_plugin_mydashboard_stocktickets> with datas of $year-$month";
      $nbdays     = date("t", mktime(0, 0, 0, $month, 1, $year));
      $is_deleted = "`glpi_tickets`.`is_deleted` = 0";
      $query      = "SELECT COUNT(*) as count,`glpi_tickets`.`entities_id` FROM `glpi_tickets`
                        WHERE $is_deleted AND (((`glpi_tickets`.`date` <= '$year-$month-$nbdays 23:59:59')
                        AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . "))) GROUP BY `glpi_tickets`.`entities_id`";
      $results    = $DB->query($query);
      while ($data = $DB->fetchArray($results)) {
         $query = "INSERT INTO `glpi_plugin_mydashboard_stocktickets` (`id`,`date`,`nbstocktickets`,`entities_id`)
                        VALUES (NULL,'$year-$month-$nbdays'," . $data['count'] . "," . $data['entities_id'] . ")";
         $DB->query($query);
      }
      $query   = "SELECT COUNT(*) as count,`glpi_tickets`.`entities_id`,`glpi_groups_tickets`.`groups_id` FROM `glpi_tickets`
                 LEFT JOIN `glpi_groups_tickets` ON `glpi_groups_tickets`.`tickets_id`=`glpi_tickets`.`id`
                  WHERE $is_deleted AND (((`glpi_tickets`.`date` <= '$year-$month-$nbdays 23:59:59') 
                  AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . "))) GROUP BY `glpi_groups_tickets`.`groups_id`,`glpi_tickets`.`entities_id`";
      $results = $DB->query($query);
      while ($data = $DB->fetchArray($results)) {
         $groups_id = $data["groups_id"];
         if (!empty($groups_id)) {
            $query = "INSERT INTO `glpi_plugin_mydashboard_stocktickets` (`id`,`date`,`nbstocktickets`,`entities_id`,`groups_id`) 
                     VALUES (NULL,'$year-$month-$nbdays'," . $data['count'] . "," . $data['entities_id'] . "," . $data['groups_id'] . ")";
            $DB->query($query);
         } else {
            $query = "INSERT INTO `glpi_plugin_mydashboard_stocktickets` (`id`,`date`,`nbstocktickets`,`entities_id`,`groups_id`) 
                     VALUES (NULL,'$year-$month-$nbdays'," . $data['count'] . "," . $data['entities_id'] . ",0)";
            $DB->query($query);
         }
      }
   }
}
