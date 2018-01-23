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

/**
 * @return bool
 */
function plugin_mydashboard_install() {
   global $DB;

   include_once(GLPI_ROOT . "/plugins/mydashboard/inc/profile.class.php");
   include_once(GLPI_ROOT . "/plugins/mydashboard/inc/helper.class.php");
   include_once(GLPI_ROOT . "/plugins/mydashboard/inc/preference.class.php");
   include_once(GLPI_ROOT . "/plugins/mydashboard/inc/config.class.php");
   include_once(GLPI_ROOT . "/plugins/mydashboard/inc/menu.class.php");
   //First install 1.0.0 (0.84)
   if (!$DB->tableExists("glpi_plugin_mydashboard_widgets")) {
      //Creates all tables
      $DB->runFile(GLPI_ROOT . "/plugins/mydashboard/install/sql/empty-1.0.0.sql");
   }
   //end---------------------------------------------------------------------
   //From 1.0.0 (0.84) to 1.0.1 (0.84)------------------------------------
   if (!$DB->tableExists("glpi_plugin_mydashboard_profileauthorizedwidgets")) {
      //A new table to manage widgets by profile
      $DB->runFile(GLPI_ROOT . "/plugins/mydashboard/install/sql/update-1.0.1.sql");
   }
   //end---------------------------------------------------------------------
   //From 1.0.1 (0.84) to 1.0.2 (0.84)------------------------------------
   if (!$DB->tableExists("glpi_plugin_mydashboard_alerts")) {
      $DB->runFile(GLPI_ROOT . "/plugins/mydashboard/install/sql/update-1.0.2.sql");
   }
   //end---------------------------------------------------------------------
   //From 1.0.2 (0.84) to 1.0.3 (0.84)------------------------------------
   if ($DB->fieldExists("glpi_plugin_mydashboard_configs", "replace_central")
       && !$DB->fieldExists("glpi_plugin_mydashboard_preferences", "replace_central")) {
      //Adding the new field to preferences
      $mig = new Migration("1.0.3");

      $configs         = getAllDatasFromTable("glpi_plugin_mydashboard_configs");
      $replace_central = 0;
      //Basically there is only one config for Dashboard (this foreach may be useless)
      foreach ($configs as $config) {
         $replace_central = $config['replace_central'];
      }
      $mig->addField("glpi_plugin_mydashboard_preferences",
                     "replace_central",
                     "bool",
                     array(
                        "update" => $replace_central,
                        "value"  => 0
                     ));

      $mig->dropField("glpi_plugin_mydashboard_configs", "replace_central");
      $mig->executeMigration();
   }
   //From 1.0.3 (0.84) to 1.0.4 (0.84)------------------------------------
   if (!$DB->fieldExists("glpi_plugin_mydashboard_userwidgets", "interface")) {
      //Adding the new field to userwidgets to precise of which interface this dashboard is
      $mig = new Migration("1.0.4");

      $mig->addField("glpi_plugin_mydashboard_userwidgets",
                     "interface",
                     "bool",
                     array(
                        "update" => 1
                     ));
      $mig->executeMigration();
   }
   //fix bug about widget
   if (!$DB->tableExists("glpi_plugin_mydashboard_stocktickets")) {
      $mig = new Migration("1.0.5");

      //new table to fix bug about stock tickets
      $DB->runFile(GLPI_ROOT . "/plugins/mydashboard/install/sql/update-1.0.5.sql");

      //fill the new table with the data of previous month of this year
      fillTableMydashboardStocktickets();


      $mig->executeMigration();
   }
   //From 1.0.4 (0.84) to 1.1.0 (0.85)------------------------------------
   //Profile migration
   if ($DB->tableExists("glpi_plugin_mydashboard_profiles")) {
      PluginMydashboardProfile::migrateRightsFrom84To85();
      $DB->query("DROP TABLE `glpi_plugin_mydashboard_profiles`;");
   }
   //end---------------------------------------------------------------------
   if (!$DB->fieldExists("glpi_plugin_mydashboard_alerts", "is_public")) {
      $mig = new Migration("1.2.1");

      $DB->runFile(GLPI_ROOT . "/plugins/mydashboard/install/sql/update-1.2.1.sql");

      $mig->executeMigration();
   }

   if (!$DB->fieldExists("glpi_plugin_mydashboard_alerts", "type")) {
      $mig = new Migration("1.3.3");

      $DB->runFile(GLPI_ROOT . "/plugins/mydashboard/install/sql/update-1.3.3.sql");

      $mig->executeMigration();
   }
   PluginMydashboardProfile::initProfile();
   PluginMydashboardProfile::createFirstAccess($_SESSION['glpiactiveprofile']['id']);
   return true;
}

function fillTableMydashboardStocktickets() {
   global $DB;
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
   $results      = $DB->query($query);
   while ($data = $DB->fetch_array($results)) {
      list($year, $month) = explode('-', $data['month']);
      $nbdays      = date("t", mktime(0, 0, 0, $month, 1, $year));
      $entities_id = $data["entities_id"];
      $query       = "SELECT COUNT(*) as count FROM `glpi_tickets`
                  WHERE `glpi_tickets`.`is_deleted` = '0' AND `glpi_tickets`.`entities_id` = $entities_id
                  AND (((`glpi_tickets`.`date` <= '$year-$month-$nbdays 23:59:59') 
                  AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")) 
                  OR ((`glpi_tickets`.`date` <= '$year-$month-$nbdays 23:59:59') 
                  AND (`glpi_tickets`.`solvedate` > ADDDATE('$year-$month-$nbdays 00:00:00' , INTERVAL 1 DAY))))";
      $results2    = $DB->query($query);
      $data2       = $DB->fetch_array($results2);
      $countTicket = $data2['count'];
      if ($countTicket > 0) {
         $query = "INSERT INTO `glpi_plugin_mydashboard_stocktickets` (`id`,`date`,`nbstocktickets`,`entities_id`) 
                              VALUES (NULL,'$year-$month-$nbdays'," . $countTicket . "," . $entities_id . ")";
         $DB->query($query);
      }
   }
}

// Uninstall process for plugin : need to return true if succeeded
/**
 * @return bool
 */
function plugin_mydashboard_uninstall() {
   global $DB;

   // Plugin tables deletion
   $tables = array(/*"glpi_plugin_mydashboard_profiles",*/
                   "glpi_plugin_mydashboard_profileauthorizedwidgets",
                   "glpi_plugin_mydashboard_widgets",
                   "glpi_plugin_mydashboard_userwidgets",
                   "glpi_plugin_mydashboard_configs",
                   "glpi_plugin_mydashboard_preferences",
                   "glpi_plugin_mydashboard_preferenceuserblacklists",
                   "glpi_plugin_mydashboard_alerts",
                   "glpi_plugin_mydashboard_stocktickets");

   foreach ($tables as $table)
      $DB->query("DROP TABLE IF EXISTS `$table`;");

   include_once(GLPI_ROOT . "/plugins/mydashboard/inc/profile.class.php");


   //Delete rights associated with the plugin
   $profileRight = new ProfileRight();

   foreach (PluginMydashboardProfile::getAllRights() as $right) {
      $profileRight->deleteByCriteria(array('name' => $right['field']));
   }
   PluginMydashboardProfile::removeRightsFromSession();

   return true;
}

function plugin_mydashboard_postinit() {
   global $PLUGIN_HOOKS;

   $plugin = 'mydashboard';
   foreach (array('add_css', 'add_javascript') as $type) {
      foreach ($PLUGIN_HOOKS[$type][$plugin] as $data) {
         if (!empty($PLUGIN_HOOKS[$type])) {
            foreach ($PLUGIN_HOOKS[$type] as $key => $plugins_data) {
               if (is_array($plugins_data) && $key != $plugin) {
                  foreach ($plugins_data as $key2 => $values) {
                     if ($values == $data) {
                        unset($PLUGIN_HOOKS[$type][$key][$key2]);
                     }
                  }
               }
            }
         }
      }
   }

}

function plugin_mydashboard_display_login() {
   $alerts = new PluginMydashboardAlert();
   echo $alerts->getAlertSummary(1);
   echo "<br>";
   echo "<div class='red'>";
   echo $alerts->getMaintenanceMessage(1);
   echo "</div>";
}

// Define dropdown relations
/**
 * @return array
 */
function plugin_mydashboard_getDatabaseRelations() {

   return array();
}