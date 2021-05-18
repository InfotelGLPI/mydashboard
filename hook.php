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
   include_once(GLPI_ROOT . "/plugins/mydashboard/inc/widget.class.php");
   //First install 1.0.0 (0.84)
   if (!$DB->tableExists("glpi_plugin_mydashboard_widgets")) {
      //Creates all tables
      $DB->runFile(GLPI_ROOT . "/plugins/mydashboard/install/sql/empty-1.7.5.sql");

      PluginMydashboardMenu::installWidgets();
      insertDefaultTitles();
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
      $mig             = new Migration("1.0.3");
      $dbu             = new DbUtils();
      $configs         = $dbu->getAllDataFromTable("glpi_plugin_mydashboard_configs");
      $replace_central = 1;
      //Basically there is only one config for Dashboard (this foreach may be useless)
      foreach ($configs as $config) {
         $replace_central = $config['replace_central'];
      }
      $mig->addField("glpi_plugin_mydashboard_preferences",
                     "replace_central",
                     "bool",
                     [
                        "update" => $replace_central,
                        "value"  => 1
                     ]);

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
                     [
                        "update" => 1
                     ]);
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

   if (!$DB->tableExists("glpi_plugin_mydashboard_dashboards")) {
      $mig = new Migration("1.5.0");
      //new table to fix bug about stock tickets
      $DB->runFile(GLPI_ROOT . "/plugins/mydashboard/install/sql/update-1.5.0.sql");
      $mig->executeMigration();
      include_once(GLPI_ROOT . "/plugins/mydashboard/install/update_133_150.php");
      update133to150();
   }

   if (!$DB->fieldExists("glpi_plugin_mydashboard_configs", "google_api_key")) {
      $mig = new Migration("1.5.1");
      $DB->runFile(GLPI_ROOT . "/plugins/mydashboard/install/sql/update-1.5.1.sql");
      $mig->executeMigration();
   }

   if (!$DB->fieldExists("glpi_plugin_mydashboard_configs", "impact_1")) {
      $mig = new Migration("1.6.2");
      $DB->runFile(GLPI_ROOT . "/plugins/mydashboard/install/sql/update-1.6.2.sql");
      $mig->executeMigration();
   }

   if (!$DB->fieldExists("glpi_plugin_mydashboard_dashboards", "grid_statesave")) {
      $mig = new Migration("1.6.3");
      $DB->runFile(GLPI_ROOT . "/plugins/mydashboard/install/sql/update-1.6.3.sql");
      $mig->executeMigration();
   }

   if (!$DB->tableExists("glpi_plugin_mydashboard_stockwidgets")) {
      $mig = new Migration("1.7.0");
      $DB->runFile(GLPI_ROOT . "/plugins/mydashboard/install/sql/update-1.7.0.sql");
      $mig->executeMigration();
   }

   if (!$DB->tableExists("glpi_plugin_mydashboard_customswidgets")) {
      $mig = new Migration("1.7.2");
      $DB->runFile(GLPI_ROOT . "/plugins/mydashboard/install/sql/update-1.7.2.sql");
      $mig->executeMigration();
      insertDefaultTitles();
   }

   $query  = "SELECT DATA_TYPE 
               FROM INFORMATION_SCHEMA.COLUMNS
               WHERE TABLE_SCHEMA = '$DB->dbdefault' AND
                    TABLE_NAME = 'glpi_plugin_mydashboard_preferences' AND 
                    COLUMN_NAME = 'prefered_group'";
   $result = $DB->query($query);
   while ($data = $DB->fetchAssoc($result)) {
      $type = $data["DATA_TYPE"];

   }

   if ($type != "varchar") {
      $mig = new Migration("1.7.5");
      $DB->runFile(GLPI_ROOT . "/plugins/mydashboard/install/sql/update-1.7.5.sql");
      $mig->executeMigration();
      transform_prefered_group_to_prefered_groups();
      fillTableMydashboardStockticketsGroup();
   }

   if (!$DB->tableExists("glpi_plugin_mydashboard_itilalerts")) {
      $mig = new Migration("1.7.7");
      $DB->runFile(GLPI_ROOT . "/plugins/mydashboard/install/sql/update-1.7.7.sql");
      $mig->executeMigration();
      $widget = new PluginMydashboardWidget();
      $widget->migrateWidgets();
   }

   if (!$DB->fieldExists("glpi_plugin_mydashboard_configs", "title_informations_widget")) {
      $mig = new Migration("1.7.8");
      $DB->runFile(GLPI_ROOT . "/plugins/mydashboard/install/sql/update-1.7.8.sql");
      $queryTruncate = "TRUNCATE TABLE `glpi_plugin_mydashboard_stocktickets`";
      $DB->query($queryTruncate);
      $mig->executeMigration();
      fillTableMydashboardStocktickets();
      fillTableMydashboardStockticketsGroup();

      $config                             = new PluginMydashboardConfig();
      $input['id']                        = "1";
      $input['title_alerts_widget']       = _n("Network alert", "Network alerts", 2, 'mydashboard');
      $input['title_maintenances_widget'] = _n("Scheduled maintenance", "Scheduled maintenances", 2, 'mydashboard');
      $input['title_informations_widget'] = _n("Information", "Informations", 2, 'mydashboard');
      $config->update($input);

   }

   //fix bug about widget
   if (!$DB->tableExists("glpi_plugin_mydashboard_stockticketindicators")) {
      $mig = new Migration("1.7.9");

      //new table to fix bug about stock tickets
      $DB->runFile(GLPI_ROOT . "/plugins/mydashboard/install/sql/update-1.7.9.sql");
      

      $mig->executeMigration();
   }

   //If default configuration is not loaded
   $config = new PluginMydashboardConfig();
   if (!$config->getFromDB("1")) {
      $config->initConfig();
   }
   PluginMydashboardProfile::initProfile();
   PluginMydashboardProfile::createFirstAccess($_SESSION['glpiactiveprofile']['id']);
   return true;
}

function insertDefaultTitles() {

   global $DB;
   $startTitle = '<p style="background-color: lightgrey; padding: 5px; font-weight: bold; border: solid 1px black;">';
   $endTitle   = ' </p>';

   // Insert default title in table customwidgets
   $DB->insert("glpi_plugin_mydashboard_customswidgets",
               [
                  'name'    => __('Incidents', 'mydashboard'),
                  'content' => $startTitle . __("Incidents", 'mydashboard') . $endTitle,
                  'comment' => ''
               ]);

   $DB->insert("glpi_plugin_mydashboard_customswidgets",
               [
                  'name'    => __('Requests', 'mydashboard'),
                  'content' => $startTitle . __("Requests", 'mydashboard') . $endTitle,
                  'comment' => ''
               ]);

   $DB->insert("glpi_plugin_mydashboard_customswidgets",
               [
                  'name'    => __('Problems'),
                  'content' => $startTitle . __("Problems") . $endTitle,
                  'comment' => ''
               ]);
}

function fillTableMydashboardStocktickets() {
   global $DB;

   ini_set("memory_limit", "-1");
   ini_set("max_execution_time", "0");
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
   while ($data = $DB->fetchArray($results)) {
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
      $data2       = $DB->fetchArray($results2);
      $countTicket = $data2['count'];
      if ($countTicket > 0) {
         $query = "INSERT INTO `glpi_plugin_mydashboard_stocktickets` (`id`,`date`,`nbstocktickets`,`entities_id`) 
                              VALUES (NULL,'$year-$month-$nbdays'," . $countTicket . "," . $entities_id . ")";
         $DB->query($query);
      }
   }
}

function fillTableMydashboardStockticketsGroup() {
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
   $results = $DB->query($query);
   while ($data = $DB->fetchArray($results)) {
      list($year, $month) = explode('-', $data['month']);
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
         $results2    = $DB->query($query);
         $data2       = $DB->fetchArray($results2);
         $countTicket = $data2['count'];
         if ($countTicket > 0) {
            $query = "INSERT INTO `glpi_plugin_mydashboard_stocktickets` (`id`,`groups_id`,`date`,`nbstocktickets`,`entities_id`) 
                              VALUES (NULL,$groups_id,'$year-$month-$nbdays'," . $countTicket . "," . $entities_id . ")";
            $DB->query($query);
         }
      } else {
         $query       = "SELECT COUNT(*) as count FROM `glpi_tickets`
                  LEFT JOIN  `glpi_groups_tickets` ON `glpi_groups_tickets`.`tickets_id`=`glpi_tickets`.`id`
                  WHERE `glpi_tickets`.`is_deleted` = '0' AND `glpi_tickets`.`entities_id` = $entities_id AND `glpi_tickets`.`id` NOT IN  (SELECT tickets_id FROM `glpi_groups_tickets`)
                  AND (((`glpi_tickets`.`date` <= '$year-$month-$nbdays 23:59:59') 
                  AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . "))
                  OR ((`glpi_tickets`.`date` <= '$year-$month-$nbdays 23:59:59') 
                  AND (`glpi_tickets`.`solvedate` > ADDDATE('$year-$month-$nbdays 00:00:00' , INTERVAL 1 DAY))))";
         $results2    = $DB->query($query);
         $data2       = $DB->fetchArray($results2);
         $countTicket = $data2['count'];
         if ($countTicket > 0) {
            $query = "INSERT INTO `glpi_plugin_mydashboard_stocktickets` (`id`,`groups_id`,`date`,`nbstocktickets`,`entities_id`) 
                              VALUES (NULL,0,'$year-$month-$nbdays'," . $countTicket . "," . $entities_id . ")";
            $DB->query($query);
         }
      }
   }
}

function transform_prefered_group_to_prefered_groups() {

   $pref  = new PluginMydashboardPreference();
   $prefs = $pref->find();
   foreach ($prefs as $p) {
      if ($p["prefered_group"] == "0") {
         $p["prefered_group"] = "[]";
      } else {
         $p["prefered_group"] = "[\"" . $p["prefered_group"] . "\"]";
      }
      $pref->update($p);
   }

   $prefgroup  = new PluginMydashboardGroupprofile();
   $prefgroups = $prefgroup->find();
   foreach ($prefgroups as $p) {
      if ($p["prefered_group"] == "0") {
         $p["prefered_group"] = "[]";
      } else {
         $p["prefered_group"] = "[\"" . $p["prefered_group"] . "\"]";
      }
      $prefgroup->update($p);
   }

}

// Uninstall process for plugin : need to return true if succeeded
/**
 * @return bool
 */
function plugin_mydashboard_uninstall() {
   global $DB;

   // Plugin tables deletion
   $tables = [
      "glpi_plugin_mydashboard_profileauthorizedwidgets",
      "glpi_plugin_mydashboard_widgets",
      "glpi_plugin_mydashboard_userwidgets",
      "glpi_plugin_mydashboard_configs",
      "glpi_plugin_mydashboard_preferences",
      "glpi_plugin_mydashboard_preferenceuserblacklists",
      "glpi_plugin_mydashboard_alerts",
      "glpi_plugin_mydashboard_stockwidgets",
      "glpi_plugin_mydashboard_stocktickets",
      "glpi_plugin_mydashboard_itilalerts",
      "glpi_plugin_mydashboard_changealerts",
      "glpi_plugin_mydashboard_dashboards",
      "glpi_plugin_mydashboard_groupprofiles",
      "glpi_plugin_mydashboard_customswidgets",
      "glpi_plugin_mydashboard_configtranslations"];

   foreach ($tables as $table) {
      $DB->query("DROP TABLE IF EXISTS `$table`;");
   }

   include_once(GLPI_ROOT . "/plugins/mydashboard/inc/profile.class.php");

   //Delete rights associated with the plugin
   $profileRight = new ProfileRight();

   foreach (PluginMydashboardProfile::getAllRights() as $right) {
      $profileRight->deleteByCriteria(['name' => $right['field']]);
   }
   PluginMydashboardProfile::removeRightsFromSession();

   return true;
}

function plugin_mydashboard_postinit() {
   global $PLUGIN_HOOKS;

   $plugin = 'mydashboard';
   foreach (['add_css', 'add_javascript'] as $type) {
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
}

// Define dropdown relations
/**
 * @return array
 */
function plugin_mydashboard_getDatabaseRelations() {

   return ["glpi_groups" => [
      'glpi_plugin_mydashboard_stocktickets' => "groups_id",
   ],
           "glpi_reminders" => [
              'glpi_plugin_mydashboard_itilalerts' => "reminders_id",
              'glpi_plugin_mydashboard_alerts' => "reminders_id",
           ],
   ];
}

// Define Dropdown tables to be manage in GLPI
function plugin_mydashboard_getDropdown() {

   $plugin = new Plugin();

   if ($plugin->isActivated("mydashboard")) {
      return [
         'PluginMydashboardCustomswidget' => PluginMydashboardCustomswidget::getTypeName(2),];
   } else {
      return [];
   }
}
