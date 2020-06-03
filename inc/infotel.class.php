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
 * Class PluginMydashboardInfotel
 */
class PluginMydashboardInfotel extends CommonGLPI {

   private $options;
   private $pref;

   /**
    * PluginMydashboardInfotel constructor.
    *
    * @param array $_options
    */
   public function __construct($_options = []) {
      $this->options = $_options;

      $preference = new PluginMydashboardPreference();
      if (Session::getLoginUserID() !== false
          && !$preference->getFromDB(Session::getLoginUserID())) {
         $preference->initPreferences(Session::getLoginUserID());
      }
      $preference->getFromDB(Session::getLoginUserID());
      $this->preferences = $preference->fields;
   }

   /**
    * @return array
    */
   public function getWidgetsForItem() {

      $isDebug = $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE;

      $widgets = [
         __('Public')                => [$this->getType() . "3"  => (($isDebug) ? "3 " : "") . __("Internal annuary", "mydashboard") . "&nbsp;<i class='fa fa-table'></i>",
                                         //                                         $this->getType() . "4"  => __("Mails collector", "mydashboard") . "&nbsp;<i class='fa fa-table'></i>",
                                         $this->getType() . "5"  => (($isDebug) ? "5 " : "") . __("Fields unicity") . "&nbsp;<i class='fa fa-table'></i>",
                                         //                                         $this->getType() . "9"  => __('Automatic actions in error', 'mydashboard') . "&nbsp;<i class='fa fa-table'></i>",
                                         //                                         $this->getType() . "10" => __("User ticket alerts", "mydashboard") . "&nbsp;<i class='fa fa-table'></i>",
                                         //                                         $this->getType() . "11" => __("GLPI Status", "mydashboard") . "&nbsp;<i class='fa fa-info-circle'></i>",
                                         $this->getType() . "14" => (($isDebug) ? "14 " : "") . __("All unpublished articles") . "&nbsp;<i class='fa fa-table'></i>",
                                         //                                              $this->getType() . "19" => __("Tickets alerts", "mydashboard") . "&nbsp;<i class='fa fa-info-circle'></i>",
         ],
         __('Charts', "mydashboard") => [
            $this->getType() . "1"  => (($isDebug) ? "1 " : "") . __("Opened tickets backlog", "mydashboard") . "&nbsp;<i class='fas fa-chart-bar'></i>",
            $this->getType() . "2"  => (($isDebug) ? "2 " : "") . __("Number of opened tickets by priority", "mydashboard") . "&nbsp;<i class='fa fa-chart-pie'></i>",
            $this->getType() . "6"  => (($isDebug) ? "6 " : "") . __("Tickets stock by month", "mydashboard") . "&nbsp;<i class='fas fa-chart-line'></i>",
            $this->getType() . "7"  => (($isDebug) ? "7 " : "") . __("Top ten ticket requesters by month", "mydashboard") . "&nbsp;<i class='fa fa-chart-pie'></i>",
            $this->getType() . "8"  => (($isDebug) ? "8 " : "") . __("Process time by technicians by month", "mydashboard") . "&nbsp;<i class='fas fa-chart-bar'></i>",
            $this->getType() . "12" => (($isDebug) ? "12 " : "") . __("TTR Compliance", "mydashboard") . "&nbsp;<i class='fa fa-chart-pie'></i>",
            $this->getType() . "13" => (($isDebug) ? "13 " : "") . __("TTO Compliance", "mydashboard") . "&nbsp;<i class='fa fa-chart-pie'></i>",
            $this->getType() . "15" => (($isDebug) ? "15 " : "") . __("Top ten ticket categories by type of ticket", "mydashboard") . "&nbsp;<i class='fa fa-chart-pie'></i>",
            $this->getType() . "16" => (($isDebug) ? "16 " : "") . __("Number of opened incidents by category", "mydashboard") . "&nbsp;<i class='fa fa-chart-pie'></i>",
            $this->getType() . "17" => (($isDebug) ? "17 " : "") . __("Number of opened requests by category", "mydashboard") . "&nbsp;<i class='fa fa-chart-pie'></i>",
            $this->getType() . "18" => (($isDebug) ? "18 " : "") . __("Number of opened and closed tickets by month", "mydashboard") . "&nbsp;<i class='fa fa-chart-pie'></i>",
            $this->getType() . "20" => (($isDebug) ? "20 " : "") . __("Percent of use of solution types", "mydashboard") . "&nbsp;<i class='fa fa-chart-pie'></i>",
            $this->getType() . "21" => (($isDebug) ? "21 " : "") . __("Number of tickets affected by technicians by month", "mydashboard") . "&nbsp;<i class='fas fa-chart-bar'></i>",
            $this->getType() . "22" => (($isDebug) ? "22 " : "") . __("Number of opened and closed tickets by month", "mydashboard") . "&nbsp;<i class='fas fa-chart-line'></i>",
            $this->getType() . "23" => (($isDebug) ? "23 " : "") . __("Average real duration of treatment of the ticket", "mydashboard") . "&nbsp;<i class='fas fa-chart-bar'></i>",
            $this->getType() . "24" => (($isDebug) ? "24 " : "") . __("Top ten technicians (by tickets number)", "mydashboard") . "&nbsp;<i class='fas fa-chart-bar'></i>",
            $this->getType() . "25" => (($isDebug) ? "25 " : "") . __("Top ten of opened tickets by requester groups", "mydashboard") . "&nbsp;<i class='fa fa-chart-pie'></i>",
            $this->getType() . "26" => (($isDebug) ? "26 " : "") . __("Global satisfaction level", "mydashboard") . "&nbsp;<i class='fa fa-chart-pie'></i>",
            $this->getType() . "27" => (($isDebug) ? "27 " : "") . __("Top ten of opened tickets by location", "mydashboard") . "&nbsp;<i class='fa fa-chart-pie'></i>",
            $this->getType() . "29" => (($isDebug) ? "29 " : "") . __("OpenStreetMap - Opened tickets by location", "mydashboard") . "&nbsp;<i class='fa fa-map'></i>",
            $this->getType() . "30" => (($isDebug) ? "30 " : "") . __("Number of use of request sources", "mydashboard") . "&nbsp;<i class='fa fa-chart-pie'></i>",
            $this->getType() . "31" => (($isDebug) ? "31 " : "") . __("Tickets request sources evolution", "mydashboard") . "&nbsp;<i class='fas fa-chart-line'></i>",
            $this->getType() . "32" => (($isDebug) ? "32 " : "") . __("Number of opened tickets by technician and by status", "mydashboard") . "&nbsp;<i class='fa fa-table'></i>",
            $this->getType() . "33" => (($isDebug) ? "33 " : "") . __("Number of opened tickets by group and by status", "mydashboard") . "&nbsp;<i class='fa fa-table'></i>",
            $this->getType() . "34" => (($isDebug) ? "34 " : "") . __("Number of opened and resolved / closed tickets by month", "mydashboard") . "&nbsp;<i class='fas fa-chart-line'></i>",
            $this->getType() . "35" => (($isDebug) ? "35 " : "") . __("Age of tickets", "mydashboard") . "&nbsp;<i class='fas fa-chart-bar'></i>",
            $this->getType() . "36" => (($isDebug) ? "36 " : "") . __("Number of opened tickets by priority", "mydashboard") . "&nbsp;<i class='fas fa-chart-bar'></i>",
            $this->getType() . "37" => (($isDebug) ? "37 " : "") . __("Stock of tickets by status", "mydashboard") . "&nbsp;<i class='fas fa-chart-bar'></i>",
            $this->getType() . "38" => (($isDebug) ? "38 " : "") . __("Number of opened ticket and average satisfaction per trimester","mydashboard") . "&nbsp;<i class='fas fa-chart-bar'></i>",
         ]
      ];

      $customsWidgets = PluginMydashboardCustomswidget::listCustomsWidgets();
      if (!empty($customsWidgets)) {

         foreach ($customsWidgets as $customWidget) {
            $widgets[__('Custom Widgets', 'mydashboard')][$this->getType() . "cw" . $customWidget['id']] = $customWidget['name'];
         }
      }
      return $widgets;
   }

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
      $data    = $DB->fetch_array($results);
      if ($data["count"] > 0) {
         die("stock tickets of $year-$month is already filled");
      }
      echo "fill table <glpi_plugin_mydashboard_stocktickets> with datas of $year-$month";
      $nbdays     = date("t", mktime(0, 0, 0, $month, 1, $year));
      $is_deleted = "`glpi_tickets`.`is_deleted` = 0";
      //      $query   = "SELECT COUNT(*) as count,`glpi_tickets`.`entities_id` FROM `glpi_tickets`
      //                  WHERE $is_deleted AND (((`glpi_tickets`.`date` <= '$year-$month-$nbdays 23:59:59')
      //                  AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . "))) GROUP BY `glpi_tickets`.`entities_id`";
      //      $results = $DB->query($query);
      //      while ($data = $DB->fetch_array($results)) {
      //         $query = "INSERT INTO `glpi_plugin_mydashboard_stocktickets` (`id`,`date`,`nbstocktickets`,`entities_id`)
      //                  VALUES (NULL,'$year-$month-$nbdays'," . $data['count'] . "," . $data['entities_id'] . ")";
      //         $DB->query($query);
      //      }
      $query   = "SELECT COUNT(*) as count,`glpi_tickets`.`entities_id`,`glpi_groups_tickets`.`groups_id` FROM `glpi_tickets`
                 LEFT JOIN `glpi_groups_tickets` ON `glpi_groups_tickets`.`tickets_id`=`glpi_tickets`.`id`
                  WHERE $is_deleted AND (((`glpi_tickets`.`date` <= '$year-$month-$nbdays 23:59:59') 
                  AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . "))) GROUP BY `glpi_tickets`.`entities_id`";
      $results = $DB->query($query);
      while ($data = $DB->fetch_array($results)) {
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

   /**
    * @param $widgetId
    *
    * @return PluginMydashboardDatatable|PluginMydashboardHBarChart|PluginMydashboardHtml|PluginMydashboardLineChart|PluginMydashboardPieChart|PluginMydashboardVBarChart
    */
   public function getWidgetContentForItem($widgetId, $opt = []) {
      global $DB, $CFG_GLPI;
      $isDebug = $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE;
      $dbu     = new DbUtils();
      switch ($widgetId) {

         case $this->getType() . "1":
            $name    = 'BacklogBarChart';
            $onclick = 0;
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['entities_id',
                             'is_recursive',
                             'technicians_groups_id',
                             'group_is_recursive',
                             'requesters_groups_id',
                             'type',
                             'locations_id'];
               $onclick   = 1;
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = ['type',
                             'locations_id',
                             'requesters_groups_id'];
            }

            $params  = ["preferences" => $this->preferences,
                        "criterias"   => $criterias,
                        "opt"         => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);

            $opt                        = $options['opt'];
            $crit                       = $options['crit'];
            $type                       = $opt['type'];
            $type_criteria              = $crit['type'];
            $entities_criteria          = $crit['entities_id'];
            $entities_id_criteria       = $crit['entity'];
            $sons_criteria              = $crit['sons'];
            $requester_groups           = $opt['requesters_groups_id'];
            $requester_groups_criteria  = $crit['requesters_groups_id'];
            $technician_group           = $opt['technicians_groups_id'];
            $technician_groups_criteria = $crit['technicians_groups_id'];
            $location                   = $opt['locations_id'];
            $locations_criteria         = $crit['locations_id'];
            $is_deleted                 = "`glpi_tickets`.`is_deleted` = 0";


            $query = "SELECT DISTINCT
                           DATE_FORMAT(`date`, '%b %Y') AS period_name,
                           COUNT(`glpi_tickets`.`id`) AS nb,
                           DATE_FORMAT(`date`, '%Y-%m') AS period
                        FROM `glpi_tickets` ";
            $query .= " WHERE $is_deleted $type_criteria $locations_criteria $technician_groups_criteria $requester_groups_criteria";
            $query .= " $entities_criteria AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")
                        GROUP BY period_name ORDER BY period ASC";

            $result   = $DB->query($query);
            $nb       = $DB->numrows($result);
            $tabdata  = [];
            $tabnames = [];
            $tabdates = [];
            if ($nb) {
               while ($data = $DB->fetch_assoc($result)) {
                  $tabdata[]  = $data['nb'];
                  $tabnames[] = $data['period_name'];
                  $tabdates[] = $data['period'];
               }
            }

            $widget = new PluginMydashboardHtml();
            $widget->setWidgetTitle((($isDebug) ? "1 " : "") . __("Opened tickets backlog", "mydashboard"));
            $widget->setWidgetComment(__("Display of opened tickets by month", "mydashboard"));
            $databacklogset = json_encode($tabdata);
            $labelsback     = json_encode($tabnames);
            $tabdatesset    = json_encode($tabdates);

            $nbtickets       = __('Tickets number', 'mydashboard');
            $js_ancestors    = $crit['ancestors'];
            $colors          = '#1f77b4';
            $backgroundColor = json_encode($colors);

            $graph_datas     = ['name'            => $name,
                                'ids'             => $tabdatesset,
                                'data'            => $databacklogset,
                                'labels'          => $labelsback,
                                'label'           => $nbtickets,
                                'backgroundColor' => $backgroundColor];
            $graph_criterias = [];
            if ($onclick == 1) {
               $graph_criterias = ['entities_id'        => $entities_id_criteria,
                                   'sons'               => $sons_criteria,
                                   'requester_groups'   => $requester_groups,
                                   'technician_group'   => $technician_group,
                                   'group_is_recursive' => $js_ancestors,
                                   'type'               => $type,
                                   'locations_id'       => $location,
                                   'widget'             => $widgetId];
            }

            $graph = PluginMydashboardBarChart::launchGraph($graph_datas, $graph_criterias);

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => true,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => 1];

            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));
            $widget->toggleWidgetRefresh();
            $widget->setWidgetHtmlContent($graph);

            return $widget;
            break;

         case $this->getType() . "2":
            $name = 'TicketsByPriorityPieChart';
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['entities_id',
                             'is_recursive',
                             'type',
                             'technicians_groups_id',
                             'group_is_recursive'];
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = ['type'];
            }

            $params  = ["preferences" => $this->preferences,
                        "criterias"   => $criterias,
                        "opt"         => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);

            $opt                        = $options['opt'];
            $crit                       = $options['crit'];
            $type                       = $opt['type'];
            $type_criteria              = $crit['type'];
            $entities_criteria          = $crit['entities_id'];
            $entities_id_criteria       = $crit['entity'];
            $sons_criteria              = $crit['sons'];
            $is_deleted                 = "`glpi_tickets`.`is_deleted` = 0";
            $technician_group           = $opt['technicians_groups_id'];
            $technician_groups_criteria = $crit['technicians_groups_id'];

            $query = "SELECT DISTINCT
                           `priority`,
                           COUNT(`id`) AS nb
                        FROM `glpi_tickets`
                        WHERE $is_deleted $type_criteria $entities_criteria $technician_groups_criteria";
            $query .= " AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ") ";
            $query .= " GROUP BY `priority` ORDER BY `priority` ASC";

            $colors = [];
            $result = $DB->query($query);
            $nb     = $DB->numrows($result);

            $name_priority = [];
            $datas         = [];
            $tabpriority   = [];
            if ($nb) {
               while ($data = $DB->fetch_array($result)) {
                  $name_priority[] = CommonITILObject::getPriorityName($data['priority']);
                  $colors[]        = $_SESSION["glpipriority_" . $data['priority']];
                  $datas[]         = $data['nb'];
                  $tabpriority[]   = $data['priority'];
               }
            }

            $widget = new PluginMydashboardHtml();
            $title  = __("Number of opened tickets by priority", "mydashboard");
            $widget->setWidgetTitle((($isDebug) ? "2 " : "") . $title);

            $dataPieset         = json_encode($datas);
            $backgroundPieColor = json_encode($colors);
            $labelsPie          = json_encode($name_priority);
            $tabpriorityset     = json_encode($tabpriority);
            $js_ancestors       = $crit['ancestors'];

            $graph_datas = ['name'            => $name,
                            'ids'             => $tabpriorityset,
                            'data'            => $dataPieset,
                            'labels'          => $labelsPie,
                            'label'           => $title,
                            'backgroundColor' => $backgroundPieColor];

            //            if ($onclick == 1) {
            $graph_criterias = ['entities_id'        => $entities_id_criteria,
                                'sons'               => $sons_criteria,
                                'technician_group'   => $technician_group,
                                'group_is_recursive' => $js_ancestors,
                                'type'               => $type,
                                'widget'             => $widgetId];
            //            }

            $graph = PluginMydashboardPieChart::launchPieGraph($graph_datas, $graph_criterias);

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => true,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => 1];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));

            $widget->setWidgetHtmlContent($graph);

            return $widget;
            break;

         case $this->getType() . "3":

            $profile_user = new Profile_User();
            $condition    = $dbu->getEntitiesRestrictCriteria('glpi_profiles_users', 'entities_id', '', true);
            $users        = $profile_user->find($condition);
            $filtredUsers = [];
            foreach ($users as $user) {
               $filtredUsers[$user['users_id']] = $user['users_id'];
            }
            $query = "SELECT `firstname`, `realname`, `name`, `phone`, `phone2`, `mobile`
                        FROM `glpi_users`
                        WHERE `glpi_users`.`is_deleted` = '0'
                        AND `id` IN ('" . implode("','", $filtredUsers) . "')
                        AND `glpi_users`.`is_active`
                        AND NOT `glpi_users`.`firstname` = ''
                        AND `glpi_users`.`firstname` IS NOT NULL
                        AND NOT `glpi_users`.`realname` = ''
                        AND `glpi_users`.`realname` IS NOT NULL
                        AND ((NOT `glpi_users`.`phone` = ''
                        AND `glpi_users`.`phone` IS NOT NULL)
                        OR (NOT `glpi_users`.`phone2` = ''
                        AND `glpi_users`.`phone2` IS NOT NULL)
                        OR (NOT `glpi_users`.`mobile` = ''
                        AND `glpi_users`.`mobile` IS NOT NULL))
                        ORDER BY `realname`, `firstname` ASC";

            $widget  = PluginMydashboardHelper::getWidgetsFromDBQuery('table', $query);
            $headers = [__('First name'), __('Name'), __('Login'), __('Phone'), __('Phone 2'), __('Mobile phone')];

            $widget->setTabNames($headers);
            $hidden[] = ["targets" => 2, "visible" => false];
            $widget->setOption("bDef", $hidden);
            $widget->toggleWidgetRefresh();
            $widget->setWidgetTitle((($isDebug) ? "3 " : "") . __("Internal annuary", "mydashboard"));
            $widget->setWidgetComment(__("Search users of your organisation", "mydashboard"));

            return $widget;
            break;

         case $this->getType() . "4":

            $alert = new PluginMydashboardAlert();
            return $alert->getWidgetContentForItem("PluginMydashboardAlert9");
            break;

         case $this->getType() . "5":

            $query = "SELECT id
                FROM `glpi_fieldunicities`
                WHERE `is_active` = '1' " .
                     $dbu->getEntitiesRestrictRequest("AND", 'glpi_fieldunicities', "", $_SESSION['glpiactive_entity'],
                                                      true);
            $query .= "ORDER BY `entities_id` DESC";

            $result = $DB->query($query);
            $nb     = $DB->numrows($result);

            $widget  = PluginMydashboardHelper::getWidgetsFromDBQuery('table', $query);
            $headers = [__('Name'), __('Duplicates')];
            $widget->setTabNames($headers);

            $datas = [];
            $i     = 0;
            if ($nb) {
               while ($data = $DB->fetch_assoc($result)) {

                  $unicity = new FieldUnicity();
                  $unicity->getFromDB($data["id"]);

                  if (!$item = getItemForItemtype($unicity->fields['itemtype'])) {
                     continue;
                  }
                  $datas[$i]["name"] = $unicity->fields["name"];

                  $fields       = [];
                  $where_fields = [];

                  foreach (explode(',', $unicity->fields['fields']) as $field) {
                     $fields[]       = $field;
                     $where_fields[] = $field;
                  }

                  if (!empty($fields)) {

                     $entities = [$unicity->fields['entities_id']];
                     if ($unicity->fields['is_recursive']) {
                        $entities = getSonsOf('glpi_entities', $unicity->fields['entities_id']);
                     }
                     $fields_string = implode(',', $fields);

                     if ($item->maybeTemplate()) {
                        $where_template = " AND `" . $item->getTable() . "`.`is_template` = '0'";
                     } else {
                        $where_template = "";
                     }

                     $where_fields_string = "";
                     foreach ($where_fields as $where_field) {
                        if (getTableNameForForeignKeyField($where_field)) {
                           $where_fields_string .= " AND `$where_field` IS NOT NULL AND `$where_field` <> '0'";
                        } else {
                           $where_fields_string .= " AND `$where_field` IS NOT NULL AND `$where_field` <> ''";
                        }
                     }
                     $query_field             = "SELECT COUNT(*) AS cpt
                               FROM `" . $item->getTable() . "`
                               WHERE `" . $item->getTable() . "`.`entities_id` IN (" . implode(',', $entities) . ")
                                     $where_template
                                     $where_fields_string
                               GROUP BY $fields_string
                               ORDER BY cpt DESC";
                     $count                   = 0;
                     $datas[$i]["duplicates"] = 0;
                     foreach ($DB->request($query_field) as $uniq) {
                        if ($uniq['cpt'] > 1) {
                           $count++;
                        }
                     }
                     $datas[$i]["duplicates"] = $count;
                  } else {
                     $datas[$i]["duplicates"] = __('No item found');
                  }
                  $i++;
               }
            }

            $widget->setTabDatas($datas);
            $widget->setWidgetTitle((($isDebug) ? "5 " : "") . __('Fields unicity'));
            $widget->setWidgetComment(__("Display if you have duplicates into inventory", "mydashboard"));

            return $widget;
            break;

         case $this->getType() . "6":
            $name = 'TicketStockLineChart';
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['technicians_groups_id',
                             'entities_id',
                             'is_recursive',
                             'year'];
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = [];
            }

            $params  = ["preferences" => $this->preferences,
                        "criterias"   => $criterias,
                        "opt"         => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);

            $opt  = $options['opt'];
            $crit = $options['crit'];

            $entities_criteria     = $crit['entities_id'];
            $tech_groups_crit      = "";
            $technician_groups_ids = is_array($opt['technicians_groups_id']) ? $opt['technicians_groups_id'] : [$opt['technicians_groups_id']];
            if (count($opt['technicians_groups_id']) > 0) {
               $tech_groups_crit = " AND `groups_id` IN (" . implode(",", $technician_groups_ids) . ")";
            }
            $mdentities = self::getSpecificEntityRestrict("glpi_plugin_mydashboard_stocktickets", $opt);

            $currentmonth = date("m");
            $currentyear = date("Y");

            if (isset($opt["year"]) && $opt["year"] > 0) {
               $currentyear = $opt["year"];
            }
            $previousyear = $currentyear - 1;
            $query_2      = "SELECT DATE_FORMAT(`glpi_plugin_mydashboard_stocktickets`.`date`, '%Y-%m') as month,
                                    DATE_FORMAT(`glpi_plugin_mydashboard_stocktickets`.`date`, '%b %Y') as monthname,
                                    SUM(nbStockTickets) as nbStockTickets
                                    FROM `glpi_plugin_mydashboard_stocktickets`
                                    WHERE  (`glpi_plugin_mydashboard_stocktickets`.`date` >= '$previousyear-$currentmonth-01 00:00:00')
                                    AND (`glpi_plugin_mydashboard_stocktickets`.`date` <= '$currentyear-$currentmonth-01 00:00:00')
                                    " . $mdentities . $tech_groups_crit . "
                                    AND `glpi_plugin_mydashboard_stocktickets`.`groups_id` >= 0
                                    GROUP BY DATE_FORMAT(`glpi_plugin_mydashboard_stocktickets`.`date`, '%Y-%m')";

            $tabdata    = [];
            $tabnames   = [];
            $results2   = $DB->query($query_2);
            $maxcount   = 0;
            $i          = 0;
            $is_deleted = "`glpi_tickets`.`is_deleted` = 0";
            while ($data = $DB->fetch_array($results2)) {
               $tabdata[$i] = $data["nbStockTickets"];
               $tabnames[]  = $data['monthname'];
               if ($data["nbStockTickets"] > $maxcount) {
                  $maxcount = $data["nbStockTickets"];
               }
               $i++;
            }

            $query = "SELECT DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m') AS month, 
                        DATE_FORMAT(`glpi_tickets`.`date`, '%b %Y') AS monthname, 
                        DATE_FORMAT(`glpi_tickets`.`date`, '%Y%m') AS monthnum, count(MONTH(`glpi_tickets`.`date`))
                        FROM `glpi_tickets`
                        WHERE $is_deleted ";
            $query .= $entities_criteria . " 
                     AND MONTH(`glpi_tickets`.`date`)='" . date("m") . "' 
                     AND(YEAR(`glpi_tickets`.`date`) = '" . date("Y") . "') 
                     GROUP BY DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m')";

            $results = $DB->query($query);
            while ($data = $DB->fetch_array($results)) {

               list($year, $month) = explode('-', $data['month']);

               $nbdays  = date("t", mktime(0, 0, 0, $month, 1, $year));
               $query_1 = "SELECT COUNT(*) as count FROM `glpi_tickets`
                     WHERE $is_deleted " . $entities_criteria . "
                     AND (((`glpi_tickets`.`date` <= '$year-$month-$nbdays 23:59:59') 
                     AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")) 
                     OR ((`glpi_tickets`.`date` <= '$year-$month-$nbdays 23:59:59') 
                     AND (`glpi_tickets`.`solvedate` > ADDDATE('$year-$month-$nbdays 00:00:00' , INTERVAL 1 DAY))))";

               $results_1 = $DB->query($query_1);
               $data_1    = $DB->fetch_array($results_1);

               $tabdata[$i] = $data_1['count'];

               $tabnames[] = $data['monthname'];
               $i++;
            }

            $widget = new PluginMydashboardHtml();
            $title  = __("Tickets stock", "mydashboard");
            $widget->setWidgetComment(__("Sum of not solved tickets by month", "mydashboard"));
            $widget->setWidgetTitle((($isDebug) ? "6 " : "") . $title);
            $widget->toggleWidgetRefresh();

            $dataLineset = json_encode($tabdata);
            $labelsLine  = json_encode($tabnames);

            $month     = _n('month', 'months', 2);
            $nbtickets = __('Tickets number', 'mydashboard');

            $graph_datas = ['name'   => $name,
                            'ids'    => json_encode([]),
                            'data'   => $dataLineset,
                            'labels' => $labelsLine,
                            'label'  => $title];


            $graph = PluginMydashboardLineChart::launchGraph($graph_datas, []);

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => true,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => 1];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));

            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;

         case $this->getType() . "7":
            $name = 'TopTenTicketAuthorsPieChart';
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['entities_id',
                             'is_recursive',
                             'type',
                             'year',
                             'month'];
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = ['type',
                             'year',
                             'month'];
            }

            $params  = ["preferences" => $this->preferences,
                        "criterias"   => $criterias,
                        "opt"         => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);

            $opt  = $options['opt'];
            $crit = $options['crit'];

            $type_criteria     = $crit['type'];
            $entities_criteria = $crit['entities_id'];
            $date_criteria     = $crit['date'];
            $is_deleted        = "`glpi_tickets`.`is_deleted` = 0";

            $query    = "SELECT IFNULL(`glpi_tickets_users`.`users_id`,-1) as users_id, COUNT(`glpi_tickets`.`id`) as count
                     FROM `glpi_tickets`
                     LEFT JOIN `glpi_tickets_users`
                        ON (`glpi_tickets_users`.`tickets_id` = `glpi_tickets`.`id` AND `glpi_tickets_users`.`type` = 1)
                     WHERE $date_criteria
                     $entities_criteria $type_criteria
                     AND $is_deleted
                     GROUP BY `glpi_tickets_users`.`users_id`
                     ORDER BY count DESC
                     LIMIT 10";
            $widget   = PluginMydashboardHelper::getWidgetsFromDBQuery('piechart', $query);
            $datas    = $widget->getTabDatas();
            $dataspie = [];
            $namespie = [];
            $nb       = count($datas);
            if ($nb > 0) {
               foreach ($datas as $k => $v) {
                  if ($k == 0) {
                     $name_user = __('Email');
                  } else if ($k == -1) {
                     $name_user = __('None');
                  } else if ($k > 0) {
                     $name_user = getUserName($k);
                  }
                  $dataspie[] = $v;
                  $namespie[] = $name_user;
                  unset($datas[$k]);
               }
            }

            $widget = new PluginMydashboardHtml();
            $title  = __("Top ten ticket requesters by month", "mydashboard");
            $widget->setWidgetTitle((($isDebug) ? "7 " : "") . $title);

            $palette = PluginMydashboardColor::getColors($nb);

            $dataPieset         = json_encode($dataspie);
            $backgroundPieColor = json_encode($palette);
            $labelsPie          = json_encode($namespie);

            $graph_datas = ['name'            => $name,
                            'ids'             => json_encode([]),
                            'data'            => $dataPieset,
                            'labels'          => $labelsPie,
                            'label'           => $title,
                            'backgroundColor' => $backgroundPieColor];


            $graph = PluginMydashboardPieChart::launchPolarAreaGraph($graph_datas, []);

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => false,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => $nb];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));

            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;

         case $this->getType() . "8":
            $name = 'TimeByTechChart';
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['entities_id',
                             'is_recursive',
                             'technicians_groups_id',
                             'type',
                             'year'];
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = ['type',
                             'year'];
            }

            $params  = ["preferences" => $this->preferences,
                        "criterias"   => $criterias,
                        "opt"         => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);
            $opt     = $options['opt'];

            $time_per_tech = self::getTimePerTech($options);

            $months_t = Toolbox::getMonthsOfYearArray();
            $months   = [];
            foreach ($months_t as $key => $month) {
               $months[] = $month;
            }

            $nb_bar = 0;
            foreach ($time_per_tech as $tech_id => $tickets) {
               $nb_bar++;
            }
            $palette = PluginMydashboardColor::getColors($nb_bar);

            $i       = 0;
            $dataset = [];
            foreach ($time_per_tech as $tech_id => $times) {
               unset($time_per_tech[$tech_id]);
               $username = getUserName($tech_id);
               $i++;
               $dataset[] = [
                  "label"           => $username,
                  "data"            => array_values($times),
                  "backgroundColor" => $palette[$i]];
            }

            $widget = new PluginMydashboardHtml();
            $title  = __("Process time by technicians by month", "mydashboard");
            $widget->setWidgetTitle((($isDebug) ? "8 " : "") . $title);
            $widget->setWidgetComment(__("Sum of ticket tasks duration by technicians", "mydashboard"));

            $dataLineset = json_encode($dataset);
            $labelsLine  = json_encode($months);

            $graph_datas = ['name'   => $name,
                            'ids'    => json_encode([]),
                            'data'   => $dataLineset,
                            'labels' => $labelsLine];

            $graph = PluginMydashboardBarChart::launchStackedGraph($graph_datas, []);

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => true,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => count($dataset)];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));

            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;

         case $this->getType() . "12":
            $name      = 'TTRCompliance';
            $criterias = ['type'];
            $params    = ["preferences" => $this->preferences,
                          "criterias"   => $criterias,
                          "opt"         => $opt];
            $options   = PluginMydashboardHelper::manageCriterias($params);

            $opt  = $options['opt'];
            $crit = $options['crit'];

            $type_criteria = $crit['type'];
            $is_deleted    = "`glpi_tickets`.`is_deleted` = 0";
            $all           = "SELECT DISTINCT COUNT(`glpi_tickets`.`id`) AS nb
                        FROM `glpi_tickets`
                        WHERE $is_deleted $type_criteria
                        AND `glpi_tickets`.`solvedate` IS NOT NULL
                        AND `glpi_tickets`.`time_to_resolve` IS NOT NULL ";
            $all           .= $dbu->getEntitiesRestrictRequest("AND", Ticket::getTable())
                              . " AND `status` IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ") ";

            $result = $DB->query($all);
            $total  = $DB->fetch_assoc($result);

            $query = "SELECT COUNT(`glpi_tickets`.`id`) AS nb
                        FROM `glpi_tickets`
                        WHERE $is_deleted $type_criteria
                        AND `glpi_tickets`.`solvedate` IS NOT NULL
                        AND `glpi_tickets`.`time_to_resolve` IS NOT NULL
                                            AND (`glpi_tickets`.`solvedate` > `glpi_tickets`.`time_to_resolve`
                                                 OR (`glpi_tickets`.`solvedate` IS NULL
                                                      AND `glpi_tickets`.`time_to_resolve` < NOW()))";
            $query .= $dbu->getEntitiesRestrictRequest("AND", Ticket::getTable())
                      . " AND `status` IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")";

            $result       = $DB->query($query);
            $sum          = $DB->fetch_assoc($result);
            $nb           = $DB->numrows($result);
            $notrespected = 0;
            $respected    = 0;
            if ($nb > 0 && $sum['nb'] > 0) {
               $notrespected = round(($sum['nb']) * 100 / ($total['nb']), 2);
               $respected    = round(($total['nb'] - $sum['nb']) * 100 / ($total['nb']), 2);
            }
            $widget = new PluginMydashboardHtml();
            $title  = __("TTR Compliance", "mydashboard");
            $widget->setWidgetTitle((($isDebug) ? "12 " : "") . $title);
            $widget->setWidgetComment(__("Display tickets where time to resolve is respected (percent)", "mydashboard"));

            $dataPieset         = json_encode([$respected, $notrespected]);
            $palette            = PluginMydashboardColor::getColors(2);
            $backgroundPieColor = json_encode($palette);
            $labelsPie          = json_encode([__("Respected TTR", "mydashboard"), __("Not respected TTR", "mydashboard")]);

            $graph_datas = ['name'            => $name,
                            'ids'             => json_encode([]),
                            'data'            => $dataPieset,
                            'labels'          => $labelsPie,
                            'label'           => $title,
                            'backgroundColor' => $backgroundPieColor];

            $graph = PluginMydashboardPieChart::launchPieGraph($graph_datas, []);

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => false,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => $nb];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));

            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;

         case $this->getType() . "13":
            $name      = 'TTOCompliance';
            $criterias = ['type'];
            $params    = ["preferences" => $this->preferences,
                          "criterias"   => $criterias,
                          "opt"         => $opt];
            $options   = PluginMydashboardHelper::manageCriterias($params);

            $opt  = $options['opt'];
            $crit = $options['crit'];

            $type_criteria = $crit['type'];
            $is_deleted    = "`glpi_tickets`.`is_deleted` = 0";

            $all = "SELECT DISTINCT COUNT(`glpi_tickets`.`id`) AS nb
                        FROM `glpi_tickets`
                        WHERE $is_deleted $type_criteria
                        AND `glpi_tickets`.`takeintoaccount_delay_stat` IS NOT NULL
                        AND `glpi_tickets`.`time_to_own` IS NOT NULL ";// AND ".getDateRequest("`$table`.`solvedate`", $begin, $end)."
            $all .= $dbu->getEntitiesRestrictRequest("AND", Ticket::getTable())
                    . " AND `status` IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ") ";

            $result = $DB->query($all);
            $total  = $DB->fetch_assoc($result);

            $query = "SELECT COUNT(`glpi_tickets`.`id`) AS nb
                        FROM `glpi_tickets`
                        WHERE $is_deleted $type_criteria
                        AND `glpi_tickets`.`takeintoaccount_delay_stat` IS NOT NULL
                        AND `glpi_tickets`.`time_to_own` IS NOT NULL
                        AND (`glpi_tickets`.`takeintoaccount_delay_stat`
                                                        > TIME_TO_SEC(TIMEDIFF(`glpi_tickets`.`time_to_own`,
                                                                               `glpi_tickets`.`date`))
                                                 OR (`glpi_tickets`.`takeintoaccount_delay_stat` = 0
                                                      AND `glpi_tickets`.`time_to_own` < NOW()))";
            $query .= $dbu->getEntitiesRestrictRequest("AND", Ticket::getTable())
                      . " AND `status` IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")";

            $result       = $DB->query($query);
            $sum          = $DB->fetch_assoc($result);
            $nb           = $DB->numrows($result);
            $notrespected = 0;
            $respected    = 0;
            if ($nb > 0 && $sum['nb'] > 0) {
               $notrespected = round(($sum['nb']) * 100 / ($total['nb']), 2);
               $respected    = round(($total['nb'] - $sum['nb']) * 100 / ($total['nb']), 2);
            }
            $widget = new PluginMydashboardHtml();
            $title  = __("TTO Compliance", "mydashboard");
            $widget->setWidgetTitle((($isDebug) ? "13 " : "") . $title);
            $widget->setWidgetComment(__("Display tickets where time to own is respected (percent)", "mydashboard"));

            $dataPieset         = json_encode([$respected, $notrespected]);
            $palette            = PluginMydashboardColor::getColors(2);
            $backgroundPieColor = json_encode($palette);
            $labelsPie          = json_encode([__("Respected TTO", "mydashboard"), __("Not respected TTO", "mydashboard")]);

            $graph_datas = ['name'            => $name,
                            'ids'             => json_encode([]),
                            'data'            => $dataPieset,
                            'labels'          => $labelsPie,
                            'label'           => $title,
                            'backgroundColor' => $backgroundPieColor];

            $graph = PluginMydashboardPieChart::launchPieGraph($graph_datas, []);

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => false,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => $nb];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));
            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;

         case $this->getType() . "14":

            $query = "SELECT DISTINCT `glpi_knowbaseitems`.*, `glpi_knowbaseitemcategories`.`completename` AS category 
                     FROM `glpi_knowbaseitems` 
                     LEFT JOIN `glpi_knowbaseitems_users` ON (`glpi_knowbaseitems_users`.`knowbaseitems_id` = `glpi_knowbaseitems`.`id`) 
                     LEFT JOIN `glpi_groups_knowbaseitems` ON (`glpi_groups_knowbaseitems`.`knowbaseitems_id` = `glpi_knowbaseitems`.`id`) 
                     LEFT JOIN `glpi_knowbaseitems_profiles` ON (`glpi_knowbaseitems_profiles`.`knowbaseitems_id` = `glpi_knowbaseitems`.`id`) 
                     LEFT JOIN `glpi_entities_knowbaseitems` ON (`glpi_entities_knowbaseitems`.`knowbaseitems_id` = `glpi_knowbaseitems`.`id`) 
                     LEFT JOIN `glpi_knowbaseitemcategories` ON (`glpi_knowbaseitemcategories`.`id` = `glpi_knowbaseitems`.`knowbaseitemcategories_id`) 
                     WHERE (`glpi_entities_knowbaseitems`.`entities_id` IS NULL 
                     AND `glpi_knowbaseitems_profiles`.`profiles_id` IS NULL 
                     AND `glpi_groups_knowbaseitems`.`groups_id` IS NULL 
                     AND `glpi_knowbaseitems_users`.`users_id` IS NULL)";

            $widget = PluginMydashboardHelper::getWidgetsFromDBQuery('table', $query);
            $widget->getTabDatas();

            $headers = [__('Subject'), __('Writer'), __('Category')];
            $widget->setTabNames($headers);

            $result = $DB->query($query);
            $nb     = $DB->numrows($result);

            $datas = [];
            $i     = 0;

            $knowbaseitem = new KnowbaseItem();
            if ($nb) {
               while ($data = $DB->fetch_assoc($result)) {
                  $knowbaseitem->getFromDB($data['id']);

                  $datas[$i]["name"] = $knowbaseitem->getLink();
                  $showuserlink      = 0;
                  if (Session::haveRight('user', READ)) {
                     $showuserlink = 1;
                  }
                  $datas[$i]["users"]    = getUserName($data["users_id"], $showuserlink);
                  $datas[$i]["category"] = $data["category"];

                  $i++;
               }
            }

            $widget->setTabDatas($datas);

            $widget->toggleWidgetRefresh();
            $widget->setWidgetTitle((($isDebug) ? "14 " : "") . __('All unpublished articles'));

            return $widget;
            break;

         case $this->getType() . "15":
            $name = 'TopTenTicketCategoriesPieChart';
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['requesters_groups_id',
                             'entities_id',
                             'is_recursive',
                             'type',
                             'year'];
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = ['requesters_groups_id',
                             'type',
                             'year'];
            }

            $params  = ["preferences" => $this->preferences,
                        "criterias"   => $criterias,
                        "opt"         => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);

            $opt  = $options['opt'];
            $crit = $options['crit'];

            $type_criteria             = $crit['type'];
            $entities_criteria         = $crit['entities_id'];
            $requester_groups_criteria = $crit['requesters_groups_id'];
            $date_criteria             = $crit['date'];
            $is_deleted                = "`glpi_tickets`.`is_deleted` = 0";

            $query = "SELECT `glpi_itilcategories`.`completename` as itilcategories_id, COUNT(`glpi_tickets`.`id`) as count
                     FROM `glpi_tickets`
                     LEFT JOIN `glpi_itilcategories`
                        ON (`glpi_itilcategories`.`id` = `glpi_tickets`.`itilcategories_id`)
                     WHERE $date_criteria
                     $entities_criteria $type_criteria $requester_groups_criteria
                     AND $is_deleted
                     GROUP BY `glpi_itilcategories`.`id`
                     ORDER BY count DESC
                     LIMIT 10";

            $result   = $DB->query($query);
            $nb       = $DB->numrows($result);
            $tabdata  = [];
            $tabnames = [];
            if ($nb) {
               while ($data = $DB->fetch_assoc($result)) {
                  $tabdata[]  = $data['count'];
                  $tabnames[] = $data['itilcategories_id'];
               }
            }

            $widget = new PluginMydashboardHtml();
            $title  = __("Top ten ticket categories by type of ticket", "mydashboard");
            $widget->setWidgetTitle((($isDebug) ? "15 " : "") . $title);
            $widget->setWidgetComment(__("Display of Top ten ticket categories by type of ticket"
               , "mydashboard"));
            $databacklogset = json_encode($tabdata);
            $labelsback     = json_encode($tabnames);

            $nbtickets = __('Tickets number', 'mydashboard');

            $palette         = '#1f77b4';
            $backgroundColor = json_encode($palette);

            $graph_datas = ['name'            => $name,
                            'ids'             => $labelsback,
                            'data'            => $databacklogset,
                            'labels'          => $labelsback,
                            'label'           => $nbtickets,
                            'backgroundColor' => $backgroundColor];

            $graph = PluginMydashboardBarChart::launchHorizontalGraph($graph_datas, []);

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => true,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => $nb];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));
            $widget->setWidgetHtmlContent($graph);

            return $widget;
            break;

         case $this->getType() . "16":
            $name = 'IncidentsByCategoryPieChart';
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['entities_id',
                             'is_recursive',
                             'technicians_groups_id',
                             'group_is_recursive',
                             'requesters_groups_id'];
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = ['requesters_groups_id'];
            }

            $params  = ["preferences" => $this->preferences,
                        "criterias"   => $criterias,
                        "opt"         => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);

            $opt  = $options['opt'];
            $crit = $options['crit'];

            $entities_criteria          = $crit['entities_id'];
            $entities_id_criteria       = $crit['entity'];
            $sons_criteria              = $crit['sons'];
            $requester_groups_criteria  = $crit['requesters_groups_id'];
            $requester_groups           = $opt['requesters_groups_id'];
            $technician_group           = $opt['technicians_groups_id'];
            $technician_groups_criteria = $crit['technicians_groups_id'];
            $is_deleted                 = "`glpi_tickets`.`is_deleted` = 0";

            $query = "SELECT DISTINCT
                           `glpi_itilcategories`.`name` AS name,
                           `glpi_itilcategories`.`id` AS itilcategories_id,
                           COUNT(`glpi_tickets`.`id`) AS nb
                        FROM `glpi_tickets` ";
            $query .= "LEFT JOIN `glpi_itilcategories`
                        ON (`glpi_itilcategories`.`id` = `glpi_tickets`.`itilcategories_id`)
                        WHERE $is_deleted AND  `glpi_tickets`.`type` = '" . Ticket::INCIDENT_TYPE . "'";
            $query .= $entities_criteria . " " . $technician_groups_criteria . " " . $requester_groups_criteria
                      . " AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")
                        GROUP BY `glpi_itilcategories`.`id`";


            $result = $DB->query($query);
            $nb     = $DB->numrows($result);

            $name_category       = [];
            $datas               = [];
            $tabincidentcategory = [];
            if ($nb) {
               while ($data = $DB->fetch_array($result)) {
                  if ($data['name'] == NULL) {
                     $name_category[] = __('None');
                  } else {
                     $name_category[] = $data['name'];
                  }
                  $datas[]               = $data['nb'];
                  $tabincidentcategory[] = $data['itilcategories_id'];
               }
            }

            $widget = new PluginMydashboardHtml();
            $title  = __("Number of opened incidents by category", "mydashboard");
            $widget->setWidgetTitle((($isDebug) ? "16 " : "") . $title);

            $dataPieset             = json_encode($datas);
            $palette                = PluginMydashboardColor::getColors($nb);
            $backgroundPieColor     = json_encode($palette);
            $labelsPie              = json_encode($name_category);
            $tabincidentcategoryset = json_encode($tabincidentcategory);
            $js_ancestors           = $crit['ancestors'];

            $graph_datas = ['name'            => $name,
                            'ids'             => $tabincidentcategoryset,
                            'data'            => $dataPieset,
                            'labels'          => $labelsPie,
                            'label'           => $title,
                            'backgroundColor' => $backgroundPieColor];

            $graph_criterias = ['entities_id'        => $entities_id_criteria,
                                'sons'               => $sons_criteria,
                                'technician_group'   => $technician_group,
                                'group_is_recursive' => $js_ancestors,
                                'requester_groups'   => $requester_groups,
                                'widget'             => $widgetId];

            $graph = PluginMydashboardPieChart::launchPieGraph($graph_datas, $graph_criterias);

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => true,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => $nb];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));

            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;

         case $this->getType() . "17":
            $name = 'RequestsByCategoryPieChart';
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['entities_id',
                             'is_recursive',
                             'technicians_groups_id',
                             'group_is_recursive',
                             'requesters_groups_id'];
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = ['requesters_groups_id'];
            }

            $params  = ["preferences" => $this->preferences,
                        "criterias"   => $criterias,
                        "opt"         => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);

            $opt  = $options['opt'];
            $crit = $options['crit'];

            $entities_criteria          = $crit['entities_id'];
            $entities_id_criteria       = $crit['entity'];
            $sons_criteria              = $crit['sons'];
            $requester_groups_criteria  = $crit['requesters_groups_id'];
            $requester_groups           = $opt['requesters_groups_id'];
            $technician_group           = $opt['technicians_groups_id'];
            $technician_groups_criteria = $crit['technicians_groups_id'];
            $is_deleted                 = "`glpi_tickets`.`is_deleted` = 0";

            $query = "SELECT DISTINCT
                           `glpi_itilcategories`.`name` AS name,
                           `glpi_itilcategories`.`id` AS itilcategories_id,
                           COUNT(`glpi_tickets`.`id`) AS nb
                        FROM `glpi_tickets` ";
            $query .= " LEFT JOIN `glpi_itilcategories`
                        ON (`glpi_itilcategories`.`id` = `glpi_tickets`.`itilcategories_id`)
                        WHERE $is_deleted AND  `glpi_tickets`.`type` = '" . Ticket::DEMAND_TYPE . "'";
            $query .= $entities_criteria . " " . $technician_groups_criteria . " " . $requester_groups_criteria
                      . " AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")
                        GROUP BY `glpi_itilcategories`.`id`";

            $result = $DB->query($query);
            $nb     = $DB->numrows($result);

            $name_categories = [];
            $datas           = [];
            $tabcategory     = [];
            if ($nb) {
               while ($data = $DB->fetch_array($result)) {
                  if ($data['name'] == NULL) {
                     $name_categories[] = __('None');
                  } else {
                     $name_categories[] = $data['name'];
                  }
                  $datas[]       = $data['nb'];
                  $tabcategory[] = $data['itilcategories_id'];
               }
            }

            $widget = new PluginMydashboardHtml();
            $title  = __("Number of opened requests by category", "mydashboard");
            $widget->setWidgetTitle((($isDebug) ? "17 " : "") . $title);

            $dataPieset         = json_encode($datas);
            $palette            = PluginMydashboardColor::getColors($nb);
            $backgroundPieColor = json_encode($palette);
            $labelsPie          = json_encode($name_categories);
            $tabcategoryset     = json_encode($tabcategory);
            $js_ancestors       = $crit['ancestors'];

            $graph_datas = ['name'            => $name,
                            'ids'             => $tabcategoryset,
                            'data'            => $dataPieset,
                            'labels'          => $labelsPie,
                            'label'           => $title,
                            'backgroundColor' => $backgroundPieColor];

            $graph_criterias = ['entities_id'        => $entities_id_criteria,
                                'sons'               => $sons_criteria,
                                'technician_group'   => $technician_group,
                                'group_is_recursive' => $js_ancestors,
                                'requester_groups'   => $requester_groups,
                                'widget'             => $widgetId];

            $graph = PluginMydashboardPieChart::launchPieGraph($graph_datas, $graph_criterias);

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => true,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => $nb];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));

            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;

         case $this->getType() . "18":
            $name = 'TicketTypePieChart';
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['entities_id',
                             'technicians_groups_id',
                             'group_is_recursive',
                             'requesters_groups_id',
                             'is_recursive',
                             'type',
                             'year',
                             'month'];
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = ['requesters_groups_id',
                             'type',
                             'year',
                             'month'];
            }

            $params  = ["preferences" => $this->preferences,
                        "criterias"   => $criterias,
                        "opt"         => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);

            $opt  = $options['opt'];
            $crit = $options['crit'];

            $type_criteria              = $crit['type'];
            $entities_criteria          = $crit['entities_id'];
            $requester_groups_criteria  = $crit['requesters_groups_id'];
            $technician_groups_criteria = $crit['technicians_groups_id'];
            $date_criteria              = $crit['date'];
            $closedate_criteria         = $crit['closedate'];
            $is_deleted                 = "`glpi_tickets`.`is_deleted` = 0";


            $query = "SELECT COUNT(`glpi_tickets`.`id`)  AS nb
                     FROM `glpi_tickets`
                     WHERE $date_criteria
                     $entities_criteria $type_criteria $requester_groups_criteria $technician_groups_criteria
                     AND $is_deleted";

            $result   = $DB->query($query);
            $nb       = $DB->numrows($result);
            $dataspie = [];
            $namespie = [];
            if ($nb) {
               while ($data = $DB->fetch_assoc($result)) {
                  $dataspie[] = $data['nb'];
                  $namespie[] = __("Opened tickets", "mydashboard");
               }
            }

            $query = "SELECT COUNT(`glpi_tickets`.`id`)  AS nb
                     FROM `glpi_tickets`

                     WHERE $closedate_criteria
                     $entities_criteria $type_criteria $requester_groups_criteria $technician_groups_criteria 
                     AND $is_deleted";

            $result = $DB->query($query);
            $nb     = $DB->numrows($result);

            if ($nb) {
               while ($data = $DB->fetch_assoc($result)) {
                  $dataspie[] = $data['nb'];
                  $namespie[] = __("Closed tickets", "mydashboard");
               }
            }

            $widget = new PluginMydashboardHtml();
            $title  = __("Number of opened and closed tickets by month", "mydashboard");
            $widget->setWidgetTitle((($isDebug) ? "18 " : "") . $title);

            $dataPieset         = json_encode($dataspie);
            $palette            = PluginMydashboardColor::getColors($nb);
            $backgroundPieColor = json_encode($palette);
            $labelsPie          = json_encode($namespie);

            $graph_datas = ['name'            => $name,
                            'ids'             => json_encode([]),
                            'data'            => $dataPieset,
                            'labels'          => $labelsPie,
                            'label'           => $title,
                            'backgroundColor' => $backgroundPieColor];

            $graph = PluginMydashboardPieChart::launchPieGraph($graph_datas, []);

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => true,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => $nb];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));
            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;

         case $this->getType() . "19":

            break;

         case $this->getType() . "20":
            $name = 'SolutionTypePieChart';
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['entities_id',
                             'is_recursive',
                             'type'];
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = ['type'];
            }

            $params  = ["preferences" => $this->preferences,
                        "criterias"   => $criterias,
                        "opt"         => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);

            $opt  = $options['opt'];
            $crit = $options['crit'];

            $type_criteria     = $crit['type'];
            $entities_criteria = $crit['entities_id'];
            $is_deleted        = "`glpi_tickets`.`is_deleted` = 0";
            $is_ticket         = " AND `glpi_itilsolutions`.`itemtype` = 'Ticket'";

            $query = "SELECT DISTINCT
                           `glpi_solutiontypes`.`name` AS name,
                           `glpi_solutiontypes`.`id` AS solutiontypes_id,
                           COUNT(`glpi_tickets`.`id`) AS nb
                        FROM `glpi_tickets`
                        LEFT JOIN `glpi_itilsolutions`
                        ON (`glpi_itilsolutions`.`items_id` = `glpi_tickets`.`id`)
                        LEFT JOIN `glpi_solutiontypes`
                        ON (`glpi_solutiontypes`.`id` = `glpi_itilsolutions`.`solutiontypes_id`)
                        WHERE $is_deleted $is_ticket $type_criteria ";
            $query .= $entities_criteria
                      . " AND `glpi_tickets`.`status` IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")
                      AND `glpi_itilsolutions`.`solutiontypes_id` > 0
                      GROUP BY `glpi_solutiontypes`.`id`";

            $result = $DB->query($query);
            $nb     = $DB->numrows($result);

            $name_solution = [];
            $datas         = [];
            $tabsolution   = [];

            if ($nb) {
               while ($data = $DB->fetch_array($result)) {
                  $name_solution[] = $data['name'];
                  //                  $datas[]       = Html::formatNumber(($data['nb']*100)/$total);
                  $datas[]       = intval($data['nb']);
                  $tabsolution[] = $data['solutiontypes_id'];
               }
            }
            $widget = new PluginMydashboardHtml();
            $title  = __("Percent of use of solution types", "mydashboard");
            $widget->setWidgetComment(__("Display percent of solution types for tickets", "mydashboard"));
            $widget->setWidgetTitle((($isDebug) ? "20 " : "") . $title);

            $dataPieset         = json_encode($datas);
            $palette            = PluginMydashboardColor::getColors($nb);
            $backgroundPieColor = json_encode($palette);
            $labelsPie          = json_encode($name_solution);
            $tabsolutionset     = json_encode($tabsolution);

            $graph_datas = ['name'            => $name,
                            'ids'             => $tabsolutionset,
                            'data'            => $dataPieset,
                            'labels'          => $labelsPie,
                            'label'           => $title,
                            'backgroundColor' => $backgroundPieColor];

            $graph = PluginMydashboardPieChart::launchDonutGraph($graph_datas, []);

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => false,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => $nb];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));

            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;

         case $this->getType() . "21":
            $name = 'TicketsByTechChart';
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['entities_id',
                             'is_recursive',
                             'technicians_groups_id',
                             'group_is_recursive',
                             'type',
                             'year'];
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = ['type',
                             'year'];
            }

            $params  = ["preferences" => $this->preferences,
                        "criterias"   => $criterias,
                        "opt"         => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);
            $opt     = $options['opt'];

            $tickets_per_tech = self::getTicketsPerTech($opt);

            $months_t = Toolbox::getMonthsOfYearArray();
            $months   = [];
            foreach ($months_t as $key => $month) {
               $months[] = $month;
            }

            $nb_bar = 0;
            foreach ($tickets_per_tech as $tech_id => $tickets) {
               $nb_bar++;
            }
            $palette = PluginMydashboardColor::getColors($nb_bar);
            $i       = 0;
            $dataset = [];
            foreach ($tickets_per_tech as $tech_id => $tickets) {
               unset($tickets_per_tech[$tech_id]);
               $username = getUserName($tech_id);
               $i++;

               $dataset[] = [
                  "label"           => $username,
                  "data"            => array_values($tickets),
                  "backgroundColor" => $palette[$i]];
            }

            $widget = new PluginMydashboardHtml();
            $widget->setWidgetTitle((($isDebug) ? "21 " : "") . __("Number of tickets affected by technicians by month", "mydashboard"));
            $widget->setWidgetComment(__("Sum of ticket affected by technicians", "mydashboard"));

            $dataLineset = json_encode($dataset);
            $labelsLine  = json_encode($months);

            $graph_datas = ['name'   => $name,
                            'ids'    => json_encode([]),
                            'data'   => $dataLineset,
                            'labels' => $labelsLine];

            $graph = PluginMydashboardBarChart::launchStackedGraph($graph_datas, []);

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => true,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => count($dataset)];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));
            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;

         case $this->getType() . "22":
            $name = 'TicketStatusBarLineChart';
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['entities_id',
                             'technicians_groups_id',
                             'group_is_recursive',
                             'requesters_groups_id',
                             'is_recursive',
                             'technicians_id',
                             'year',
                             'type',
                             'locations_id'];
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = ['requesters_groups_id',
                             'year',
                             'locations_id'];
            }

            $params  = ["preferences" => $this->preferences,
                        "criterias"   => $criterias,
                        "opt"         => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);

            $opt  = $options['opt'];
            $crit = $options['crit'];

            $type_criteria              = $crit['type'];
            $entities_criteria          = $crit['entities_id'];
            $requester_groups_criteria  = $crit['requesters_groups_id'];
            $tech_groups_crit           = "";
            $technician_groups_criteria = $crit['technicians_groups_id'];
            $technician_groups_ids      = is_array($opt['technicians_groups_id']) ? $opt['technicians_groups_id'] : [$opt['technicians_groups_id']];
            if (count($opt['technicians_groups_id']) > 0) {
               $tech_groups_crit = " AND `groups_id` IN (" . implode(",", $technician_groups_ids) . ")";
            }
            $mdentities = self::getSpecificEntityRestrict("glpi_plugin_mydashboard_stocktickets", $opt);

            $ticket_users_join   = "";
            $technician_criteria = "";

            if (isset($opt['technicians_id']) && $opt['technicians_id'] != 0) {
               $ticket_users_join   = "INNER JOIN glpi_tickets_users ON glpi_tickets_users.tickets_id = glpi_tickets.id";
               $technician_criteria = "AND glpi_tickets_users.type = " . CommonITILObject::ASSIGNED;
               $technician_criteria .= " AND glpi_tickets_users.users_id = " . $opt['technicians_id'];
            }

            $location           = $opt['locations_id'];
            $locations_criteria = $crit['locations_id'];

            $currentyear = date("Y");

            if (isset($opt["year"]) && $opt["year"] > 0) {
               $currentyear = $opt["year"];
            }
            $currentmonth = date("m");

            $query_stockTickets =
               "SELECT DATE_FORMAT(`glpi_plugin_mydashboard_stocktickets`.`date`, '%Y-%m') as month," .
               " DATE_FORMAT(`glpi_plugin_mydashboard_stocktickets`.`date`, '%b %Y') as monthname," .
               " SUM(nbStockTickets) as nbStockTickets" .
               " FROM `glpi_plugin_mydashboard_stocktickets`" .
               " WHERE `glpi_plugin_mydashboard_stocktickets`.`date` between '$currentyear-01-01' AND ADDDATE('$currentyear-01-01', INTERVAL 1 YEAR)" .
               " " . $mdentities . $tech_groups_crit .
               " AND `glpi_plugin_mydashboard_stocktickets`.`groups_id` >= 0 GROUP BY DATE_FORMAT(`glpi_plugin_mydashboard_stocktickets`.`date`, '%Y-%m')";

            $resultsStockTickets = $DB->query($query_stockTickets);
            $nbStockTickets      = $DB->numrows($resultsStockTickets);
            $maxcount            = 0;
            $i                   = 0;
            $tabopened           = [];
            $tabclosed           = [];
            $tabprogress         = [];
            $tabnames            = [];
            if ($nbStockTickets) {
               while ($data = $DB->fetch_array($resultsStockTickets)) {
                  $tabprogress[] = $data["nbStockTickets"];
                  if ($data["nbStockTickets"] > $maxcount) {
                     $maxcount = $data["nbStockTickets"];
                  }
                  $i++;
               }
            }

            $is_deleted = "`glpi_tickets`.`is_deleted` = 0";

            $query_tickets =
               "SELECT DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m') as month," .
               " DATE_FORMAT(`glpi_tickets`.`date`, '%b %Y') as monthname," .
               " DATE_FORMAT(`glpi_tickets`.`date`, '%Y%m') AS monthnum, count(MONTH(`glpi_tickets`.`date`))" .
               " FROM `glpi_tickets`" .
               " WHERE $is_deleted" .
               " AND `glpi_tickets`.`date` between '$currentyear-01-01' AND ADDDATE('$currentyear-01-01', INTERVAL 1 YEAR)" .
               " $entities_criteria" .
               " $requester_groups_criteria" .
               " $technician_groups_criteria" .
               " $locations_criteria" .
               " $type_criteria" .
               " GROUP BY DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m')";

            $results   = $DB->query($query_tickets);
            $nbResults = $DB->numrows($results);
            $i         = 0;
            if ($nbResults) {
               while ($data = $DB->fetch_array($results)) {

                  $tabnames[] = $data['monthname'];

                  list($year, $month) = explode('-', $data['month']);

                  $nbdays = date("t", mktime(0, 0, 0, $month, 1, $year));

                  $date_criteria = " `glpi_tickets`.`date` between '$year-$month-01' AND ADDDATE('$year-$month-01', INTERVAL 1 MONTH)";

                  $query_1 =
                     "SELECT COUNT(*) as count FROM `glpi_tickets`" .
                     " $ticket_users_join" .
                     " WHERE $date_criteria" .
                     " $technician_criteria" .
                     " $entities_criteria" .
                     " $requester_groups_criteria" .
                     " $technician_groups_criteria" .
                     " $locations_criteria" .
                     " $type_criteria" .
                     " AND $is_deleted";

                  $results_1 = $DB->query($query_1);

                  if ($DB->numrows($results_1)) {
                     $data_1      = $DB->fetch_array($results_1);
                     $tabopened[] = $data_1['count'];
                  } else {
                     $tabopened[] = 0;
                  }

                  $closedate_criteria = " `glpi_tickets`.`closedate` between '$year-$month-01' AND ADDDATE('$year-$month-01', INTERVAL 1 MONTH)";

                  $query_2 =
                     "SELECT COUNT(*) as count FROM `glpi_tickets`" .
                     " $ticket_users_join" .
                     " WHERE $closedate_criteria" .
                     " $technician_criteria" .
                     " $entities_criteria" .
                     " $requester_groups_criteria" .
                     " $technician_groups_criteria" .
                     " $locations_criteria" .
                     " $type_criteria" .
                     " AND $is_deleted";

                  $results_2 = $DB->query($query_2);

                  if ($DB->numrows($results_2)) {
                     $data_2      = $DB->fetch_array($results_2);
                     $tabclosed[] = $data_2['count'];
                  } else {
                     $tabclosed[] = 0;
                  }

                  if ($month == date("m") && $year == date("Y")) {

                     $nbdays = date("t", mktime(0, 0, 0, $month, 1, $year));
                     //nbstock : cannot use tech or group criteria

                     $query_3 =
                        "SELECT COUNT(*) as count FROM `glpi_tickets`" .
                        //                        " $ticket_users_join".
                        " WHERE $is_deleted" .
                        " $technician_groups_criteria" .
                        " $entities_criteria" .
                        //                        " $requester_groups_criteria".
                        //                        " $locations_criteria" .
                        // Tickets open in the month
                        " AND (((`glpi_tickets`.`date` <= '$year-$month-$nbdays 23:59:59') 
                           AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")) " .
                        // Tickets solved in the month
                        "OR ((`glpi_tickets`.`date` <= '$year-$month-$nbdays 23:59:59') 
                           AND (`glpi_tickets`.`solvedate` > ADDDATE('$year-$month-$nbdays 00:00:00' , INTERVAL 1 DAY))))";

                     $results_3 = $DB->query($query_3);

                     if ($DB->numrows($results_3)) {
                        $data_3        = $DB->fetch_array($results_3);
                        $tabprogress[] = $data_3['count'];
                     } else {
                        $tabprogress[] = 0;
                     }
                  }

                  $i++;
               }
            }

            $widget = new PluginMydashboardHtml();
            $title  = __("Number of opened and closed tickets by month", "mydashboard");
            $widget->setWidgetTitle((($isDebug) ? "22 " : "") . $title);
            $widget->toggleWidgetRefresh();

            $titleopened   = __("Opened tickets", "mydashboard");
            $titlesolved   = __("Closed tickets", "mydashboard");
            $titleprogress = __("Opened tickets backlog", "mydashboard");
            $labels        = json_encode($tabnames);

            $datasets[] =
               ['type'        => 'line',
                'data'        => $tabprogress,
                'label'       => $titleprogress,
                'borderColor' => '#ff7f0e',
                'fill'        => false,
                'lineTension' => '0.1',
               ];

            $datasets[] =
               ["type"            => "bar",
                "data"            => $tabopened,
                "label"           => $titleopened,
                'backgroundColor' => '#1f77b4',
               ];

            $datasets[] =
               ['type'            => 'bar',
                'data'            => $tabclosed,
                'label'           => $titlesolved,
                'backgroundColor' => '#aec7e8',
               ];

            $graph_datas = ['name'   => $name,
                            'ids'    => json_encode([]),
                            'data'   => json_encode($datasets),
                            'labels' => $labels,
                            'label'  => $title];

            $graph = PluginMydashboardBarChart::launchMultipleGraph($graph_datas, []);

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => true,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => 1];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));
            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;

         case $this->getType() . "23":
            $name = 'AverageBarChart';
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['entities_id',
                             'is_recursive',
                             'year',
                             'type'];
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = ['year',
                             'type'];
            }

            $params  = ["preferences" => $this->preferences,
                        "criterias"   => $criterias,
                        "opt"         => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);

            $opt  = $options['opt'];
            $crit = $options['crit'];

            $type_criteria     = $crit['type'];
            $entities_criteria = $crit['entities_id'];

            $currentyear  = $opt["year"];
            $currentmonth = date("m");

            $previousyear = $currentyear - 1;
            $nextmonth    = $currentmonth + 1;
            $is_deleted   = "`glpi_tickets`.`is_deleted` = 0";

            $query = "SELECT 
                              DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m') as month,
                              DATE_FORMAT(`glpi_tickets`.`date`, '%b %Y') as monthname,
                              DATE_FORMAT(`glpi_tickets`.`date`, '%Y%m') AS monthnum
                              FROM `glpi_tickets`
                              WHERE $is_deleted AND (`glpi_tickets`.`date` >= '$previousyear-$currentmonth-01 00:00:00')
                              AND (`glpi_tickets`.`date` <= '$currentyear-$nextmonth-01 00:00:00')
                              " . $entities_criteria . $type_criteria . "
                              GROUP BY DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m')";

            $results = $DB->query($query);
            $i       = 0;

            $tabduration = [];
            $tabdates    = [];
            $tabnames    = [];
            while ($data = $DB->fetch_array($results)) {

               list($year, $month) = explode('-', $data['month']);

               $nbdays  = date("t", mktime(0, 0, 0, $month, 1, $year));
               $query_1 = "SELECT COUNT(DISTINCT `glpi_tickets`.`id`) AS nb_tickets, SUM(`glpi_tickettasks`.`actiontime`) AS count 
                          FROM `glpi_tickettasks`
                          LEFT JOIN `glpi_tickets` ON (`glpi_tickets`.`id` = `glpi_tickettasks`.`tickets_id`)
                          WHERE $is_deleted " . $entities_criteria . $type_criteria . "
                           AND (`glpi_tickettasks`.`date` >= '$year-$month-01 00:00:01' 
                           AND `glpi_tickettasks`.`date` <= ADDDATE('$year-$month-$nbdays 00:00:00' , INTERVAL 1 DAY) )";

               $results_1         = $DB->query($query_1);
               $data_1            = $DB->fetch_array($results_1);
               $average_by_ticket = 0;

               if ($data_1['nb_tickets'] > 0
                   && $data_1['count'] > 0) {
                  $average_by_ticket = ($data_1['count'] / $data_1['nb_tickets']) / 60;
               }
               $tabduration[] = round($average_by_ticket, 2);
               $tabnames[]    = $data['monthname'];
               $tabdates[]    = $data['monthnum'];
               $i++;
            }

            $widget = new PluginMydashboardHtml();
            $widget->setWidgetTitle((($isDebug) ? "23 " : "") . __("Average real duration of treatment of the ticket", "mydashboard"));
            $widget->setWidgetComment(__("Display of average real duration of treatment of tickets (actiontime of tasks)", "mydashboard"));
            $dataLineset = json_encode($tabduration);
            $labelsLine  = json_encode($tabnames);
            $tabdatesset = json_encode($tabdates);

            $taskduration = __('Tasks duration (minutes)', 'mydashboard');

            $colors          = '#1f77b4';
            $backgroundColor = json_encode($colors);

            $graph_datas = ['name'            => $name,
                            'ids'             => $tabdatesset,
                            'data'            => $dataLineset,
                            'labels'          => $labelsLine,
                            'label'           => $taskduration,
                            'backgroundColor' => $backgroundColor];

            $graph_criterias = [];

            $graph = PluginMydashboardBarChart::launchGraph($graph_datas, []);

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => false,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => 1];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));
            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;

         case $this->getType() . "24":
            $name = 'TicketByTechsBarChart';
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['entities_id',
                             'is_recursive',
                             'year',
                             'type'];
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = ['year',
                             'type'];
            }

            $params  = ["preferences" => $this->preferences,
                        "criterias"   => $criterias,
                        "opt"         => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);

            $opt  = $options['opt'];
            $crit = $options['crit'];

            $type                 = $opt['type'];
            $type_criteria        = $crit['type'];
            $entities_criteria    = $crit['entities_id'];
            $entities_id_criteria = $crit['entity'];
            $sons_criteria        = $crit['sons'];
            $date_criteria        = $crit['date'];
            $year_criteria        = $crit['year'];
            $is_deleted           = "`glpi_tickets`.`is_deleted` = 0";

            $query   = "SELECT IFNULL(`glpi_tickets_users`.`users_id`,-1) as users_id, COUNT(`glpi_tickets`.`id`) as count
                     FROM `glpi_tickets`
                     LEFT JOIN `glpi_tickets_users`
                        ON (`glpi_tickets_users`.`tickets_id` = `glpi_tickets`.`id` AND `glpi_tickets_users`.`type` = 2)
                     WHERE $date_criteria
                     $entities_criteria $type_criteria
                     AND $is_deleted
                     GROUP BY `glpi_tickets_users`.`users_id`
                     ORDER BY count DESC
                     LIMIT 10";
            $results = $DB->query($query);

            $tabtickets  = [];
            $tabtech     = [];
            $tabtechName = [];
            $tabtechid   = [];
            while ($data = $DB->fetch_array($results)) {
               $tabtickets[] = $data['count'];
               $tabtech[]    = $data['users_id'];
               $users_id     = getUserName($data['users_id']);
               if ($data['users_id'] == -1) {
                  $users_id = __('None');
               }
               if ($data['users_id'] == 0) {
                  $users_id = __('Email');
               }
               $tabtechName[] = $users_id;
               $tabtechid[]   = $data['users_id'];
            }

            $widget = new PluginMydashboardHtml();
            $widget->setWidgetTitle((($isDebug) ? "24 " : "") . __("Top ten technicians (by tickets number)", "mydashboard"));
            $widget->setWidgetComment(__("Display of number of tickets by technicians", "mydashboard"));

            $dataticketset   = json_encode($tabtickets);
            $tabNamesset     = json_encode($tabtechName);
            $tabIdTechset    = json_encode($tabtechid);
            $ticketsnumber   = __('Tickets number', 'mydashboard');
            $palette         = PluginMydashboardColor::getColors(10);
            $backgroundColor = json_encode($palette);

            $graph_datas = ['name'            => $name,
                            'ids'             => $tabIdTechset,
                            'data'            => $dataticketset,
                            'labels'          => $tabNamesset,
                            'label'           => $ticketsnumber,
                            'backgroundColor' => $backgroundColor];

            $graph_criterias = ['entities_id' => $entities_id_criteria,
                                'sons'        => $sons_criteria,
                                'type'        => $type,
                                'year'        => $year_criteria,
                                'widget'      => $widgetId];

            $graph = PluginMydashboardBarChart::launchHorizontalGraph($graph_datas, $graph_criterias);

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => false,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => count($tabtickets)];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));
            $widget->toggleWidgetRefresh();
            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;

         case $this->getType() . "25":
            $name      = 'TicketsByRequesterGroupPieChart';
            $criterias = ['type'];
            $params    = ["preferences" => $this->preferences,
                          "criterias"   => $criterias,
                          "opt"         => $opt];
            $options   = PluginMydashboardHelper::manageCriterias($params);

            $opt           = $options['opt'];
            $crit          = $options['crit'];
            $type          = $opt['type'];
            $type_criteria = $crit['type'];
            $is_deleted    = "`glpi_tickets`.`is_deleted` = 0";

            $query = "SELECT DISTINCT
                           `groups_id` AS `requesters_groups_id`,
                           COUNT(`glpi_tickets`.`id`) AS nb
                        FROM `glpi_tickets`
                        LEFT JOIN `glpi_groups_tickets` 
                        ON (`glpi_groups_tickets`.`tickets_id` = `glpi_tickets`.`id` 
                        AND `glpi_groups_tickets`.`type` = '" . CommonITILActor::REQUESTER . "')
                        WHERE $is_deleted $type_criteria ";
            $query .= $dbu->getEntitiesRestrictRequest("AND", Ticket::getTable());
            $query .= " AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ") ";
            $query .= " GROUP BY `groups_id` LIMIT 10";

            $result = $DB->query($query);
            $nb     = $DB->numrows($result);

            $name_groups = [];
            $datas       = [];
            $tabgroup    = [];
            if ($nb) {
               while ($data = $DB->fetch_array($result)) {
                  if (!empty($data['requesters_groups_id'])) {
                     $name_groups[] = Dropdown::getDropdownName("glpi_groups", $data['requesters_groups_id']);
                  } else {
                     $name_groups[] = __('None');
                  }
                  $datas[] = $data['nb'];
                  if (!empty($data['requesters_groups_id'])) {
                     $tabgroup[] = $data['requesters_groups_id'];
                  } else {
                     $tabgroup[] = 0;
                  }
               }
            }

            $widget = new PluginMydashboardHtml();
            $title  = __("Top ten of opened tickets by requester groups", "mydashboard");
            $widget->setWidgetTitle((($isDebug) ? "25 " : "") . $title);

            $dataPieset         = json_encode($datas);
            $palette            = PluginMydashboardColor::getColors($nb);
            $backgroundPieColor = json_encode($palette);
            $labelsPie          = json_encode($name_groups);
            $tabgroupset        = json_encode($tabgroup);

            $graph_datas = ['name'            => $name,
                            'ids'             => $tabgroupset,
                            'data'            => $dataPieset,
                            'labels'          => $labelsPie,
                            'label'           => $title,
                            'backgroundColor' => $backgroundPieColor];

            //            if ($onclick == 1) {
            $graph_criterias = ['type'   => $type,
                                'widget' => $widgetId];
            //            }

            $graph = PluginMydashboardPieChart::launchPieGraph($graph_datas, []);

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => false,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => $nb];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));
            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;

         case $this->getType() . "26":
            $name = 'SatisfactionPercent';
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['entities_id',
                             'is_recursive',
                             'year'];
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = ['year'];
            }

            $params  = ["preferences" => $this->preferences,
                        "criterias"   => $criterias,
                        "opt"         => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);

            $opt  = $options['opt'];
            $crit = $options['crit'];

            $entities_criteria  = $crit['entities_id'];
            $closedate_criteria = $crit['closedate'];
            $is_deleted         = "`glpi_tickets`.`is_deleted` = 0";

            $query = "SELECT AVG(`glpi_ticketsatisfactions`.`satisfaction`) AS satisfaction
                       FROM `glpi_tickets`
                       INNER JOIN `glpi_ticketsatisfactions`
                           ON (`glpi_tickets`.`id` = `glpi_ticketsatisfactions`.`tickets_id`)";

            $query .= " WHERE $closedate_criteria
                      $entities_criteria 
                        AND $is_deleted
                        AND `glpi_tickets`.`status` IN (" . CommonITILObject::CLOSED . ")
                        AND `glpi_tickets`.`closedate` IS NOT NULL
                        AND `glpi_ticketsatisfactions`.`date_answered` IS NOT NULL ";

            $result = $DB->query($query);
            $sum    = $DB->fetch_assoc($result);
            $nb     = $DB->numrows($result);

            $notsatisfy = 0;
            $satisfy    = 0;
            if ($nb > 0 && $sum['satisfaction'] > 0) {
               $satisfy    = round(($sum['satisfaction']) * 100 / (5), 2);
               $notsatisfy = round(100 - $satisfy, 2);
            }

            $widget = new PluginMydashboardHtml();
            $title  = __("Global satisfaction level", "mydashboard");
            $widget->setWidgetTitle((($isDebug) ? "26 " : "") . $title);
            $dataPieset         = json_encode([$satisfy, $notsatisfy]);
            $palette            = PluginMydashboardColor::getColors(2);
            $backgroundPieColor = json_encode($palette);
            $labelsPie          = json_encode([__("Satisfy percent", "mydashboard"), __("Not satisfy percent", "mydashboard")]);

            $graph_datas = ['name'            => $name,
                            'ids'             => json_encode([]),
                            'data'            => $dataPieset,
                            'labels'          => $labelsPie,
                            'label'           => $title,
                            'backgroundColor' => $backgroundPieColor];

            $graph = PluginMydashboardPieChart::launchPolarAreaGraph($graph_datas, []);

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => false,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => $nb];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));
            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;

         case $this->getType() . "27":
            $name    = 'TicketsByLocationPieChart';
            $onclick = 0;
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['entities_id',
                             'is_recursive',
                             'type',
                             'technicians_groups_id',
                             'group_is_recursive'];
               $onclick   = 1;
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = ['type'];
            }

            $params  = ["preferences" => $this->preferences,
                        "criterias"   => $criterias,
                        "opt"         => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);

            $opt                        = $options['opt'];
            $crit                       = $options['crit'];
            $type                       = $opt['type'];
            $type_criteria              = $crit['type'];
            $entities_criteria          = $crit['entities_id'];
            $entities_id_criteria       = $crit['entity'];
            $sons_criteria              = $crit['sons'];
            $technician_group           = $opt['technicians_groups_id'];
            $technician_groups_criteria = $crit['technicians_groups_id'];
            $is_deleted                 = "`glpi_tickets`.`is_deleted` = 0";

            $query  = "SELECT COUNT(`glpi_tickets`.`id`) AS count, 
                           glpi_locations.id as locations_id
                        FROM `glpi_tickets` 
                        LEFT JOIN `glpi_locations` ON (`glpi_locations`.`id` = `glpi_tickets`.`locations_id`)";
            $query  .= " WHERE $is_deleted $type_criteria $entities_criteria $technician_groups_criteria ";
            $query  .= " AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ") ";
            $query  .= " GROUP BY `glpi_locations`.`id` ORDER BY count DESC";
            $query  .= " LIMIT 0, 10";
            $result = $DB->query($query);
            $nb     = $DB->numrows($result);

            $name_location = [];
            $datas         = [];
            $tablocation   = [];
            if ($nb) {
               while ($data = $DB->fetch_array($result)) {
                  if (!empty($data['locations_id'])) {
                     $name_location[] = Dropdown::getDropdownName("glpi_locations", $data['locations_id']);
                  } else {
                     $name_location[] = __('None');
                  }
                  $datas[] = $data['count'];
                  if (!empty($data['locations_id'])) {
                     $tablocation[] = $data['locations_id'];
                  } else {
                     $tablocation[] = 0;
                  }
               }
            }

            $widget = new PluginMydashboardHtml();
            $title  = __("Top ten of opened tickets by location", "mydashboard");
            $widget->setWidgetTitle((($isDebug) ? "27 " : "") . $title);

            $dataPieset         = json_encode($datas);
            $palette            = PluginMydashboardColor::getColors($nb);
            $backgroundPieColor = json_encode($palette);
            $labelsPie          = json_encode($name_location);
            $tablocationset     = json_encode($tablocation);
            $js_ancestors       = $crit['ancestors'];

            $graph_datas = ['name'            => $name,
                            'ids'             => $tablocationset,
                            'data'            => $dataPieset,
                            'labels'          => $labelsPie,
                            'label'           => $title,
                            'backgroundColor' => $backgroundPieColor];

            $graph_criterias = ['entities_id'        => $entities_id_criteria,
                                'sons'               => $sons_criteria,
                                'technician_group'   => $technician_group,
                                'group_is_recursive' => $js_ancestors,
                                'type'               => $type,
                                'widget'             => $widgetId];

            $graph = PluginMydashboardPieChart::launchPolarAreaGraph($graph_datas, $graph_criterias);

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => true,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => $nb];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));
            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;

         case $this->getType() . "29":

            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['entities_id',
                             'is_recursive',
                             'type',
                             'technicians_groups_id',
                             'group_is_recursive'];
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = ['type'];
            }

            $paramsc = ["preferences" => $this->preferences,
                        "criterias"   => $criterias,
                        "opt"         => $opt];
            $options = PluginMydashboardHelper::manageCriterias($paramsc);

            $opt  = $options['opt'];
            $crit = $options['crit'];

            $type                 = $opt['type'];
            $entities_id_criteria = $crit['entity'];
            $sons_criteria        = $crit['sons'];
            $groups_criteria      = $crit['technicians_groups_id'];

            $widget = new PluginMydashboardHtml();
            $title  = __("OpenStreetMap - Opened tickets by location", "mydashboard");
            $widget->setWidgetComment(__("Display Tickets by location (Latitude / Longitude)", "mydashboard"));
            $widget->setWidgetTitle((($isDebug) ? "29 " : "") . $title);

            $params['as_map']     = 1;
            $params['is_deleted'] = 0;
            $params['order']      = 'DESC';
            $params['sort']       = 19;
            $params['start']      = 0;
            $params['list_limit'] = 999999;
            $itemtype             = 'Ticket';

            if (isset($sons_criteria) && $sons_criteria > 0) {
               $params['criteria'][] = [
                  'field'      => 80,
                  'searchtype' => 'under',
                  'value'      => $entities_id_criteria
               ];
            } else {
               $params['criteria'][] = [
                  'field'      => 80,
                  'searchtype' => 'equals',
                  'value'      => $entities_id_criteria
               ];
            }
            $params['criteria'][] = [
               'link'       => 'AND',
               'field'      => 12,
               'searchtype' => 'equals',
               'value'      => 'notold'
            ];
            $params['criteria'][] = [
               'link'       => 'AND NOT',
               'field'      => 998,
               'searchtype' => 'contains',
               'value'      => 'NULL'
            ];
            $params['criteria'][] = [
               'link'       => 'AND NOT',
               'field'      => 999,
               'searchtype' => 'contains',
               'value'      => 'NULL'
            ];

            if ($type > 0) {
               $params['criteria'][] = [
                  'link'       => 'AND',
                  'field'      => 14,
                  'searchtype' => 'equals',
                  'value'      => $type
               ];
            }

            if ($groups_criteria > 0) {
               $options['criteria'][7]['link'] = 'AND';
               $nb                             = 0;
               foreach ($groups_criteria as $group) {
                  if ($nb == 0) {
                     $options['criteria'][7]['criteria'][$nb]['link'] = 'AND';
                  } else {
                     $options['criteria'][7]['criteria'][$nb]['link'] = 'OR';
                  }
                  $options['criteria'][7]['criteria'][$nb]['field']      = 8;
                  $options['criteria'][7]['criteria'][$nb]['searchtype'] = 'equals';
                  $options['criteria'][7]['criteria'][$nb]['value']      = $group;
                  $nb++;
               }
            }

            if ($groups_criteria > 0) {
               $params['criteria'][] = [
                  'link'       => 'AND',
                  'field'      => 8,
                  'searchtype' => 'equals',
                  'value'      => $groups_criteria
               ];
            }
            $data = Search::prepareDatasForSearch('Ticket', $params);
            Search::constructSQL($data);
            Search::constructData($data);

            $paramsh = ["widgetId"  => $widgetId,
                        "name"      => 'TicketsByLocationOpenStreetMap',
                        "onsubmit"  => false,
                        "opt"       => $opt,
                        "criterias" => $criterias,
                        "export"    => false,
                        "canvas"    => false,
                        "nb"        => 1];
            $graph   = PluginMydashboardHelper::getGraphHeader($paramsh);

            if ($data['data']['totalcount'] > 0) {

               $target   = $data['search']['target'];
               $criteria = $data['search']['criteria'];

               $criteria[]   = [
                  'link'       => 'AND',
                  'field'      => 83,
                  'searchtype' => 'equals',
                  'value'      => 'CURLOCATION'
               ];
               $globallinkto = Toolbox::append_params(
                  [
                     'criteria'     => Toolbox::stripslashes_deep($criteria),
                     'metacriteria' => Toolbox::stripslashes_deep($data['search']['metacriteria'])
                  ],
                  '&amp;'
               );
               $parameters   = "as_map=0&amp;sort=" . $data['search']['sort'] . "&amp;order=" . $data['search']['order'] . '&amp;' .
                               $globallinkto;

               $typename = $itemtype::getTypeName(2);

               if (strpos($target, '?') == false) {
                  $fulltarget = $target . "?" . $parameters;
               } else {
                  $fulltarget = $target . "&" . $parameters;
               }

               $graph .= "<script>                    
                var _loadMap = function(map_elt, itemtype) {
                  L.AwesomeMarkers.Icon.prototype.options.prefix = 'fa';
                  var _micon = 'circle';
      
                  var stdMarker = L.AwesomeMarkers.icon({
                     icon: _micon,
                     markerColor: 'blue'
                  });
      
                  var aMarker = L.AwesomeMarkers.icon({
                     icon: _micon,
                     markerColor: 'cadetblue'
                  });
      
                  var bMarker = L.AwesomeMarkers.icon({
                     icon: _micon,
                     markerColor: 'purple'
                  });
      
                  var cMarker = L.AwesomeMarkers.icon({
                     icon: _micon,
                     markerColor: 'darkpurple'
                  });
      
                  var dMarker = L.AwesomeMarkers.icon({
                     icon: _micon,
                     markerColor: 'red'
                  });
      
                  var eMarker = L.AwesomeMarkers.icon({
                     icon: _micon,
                     markerColor: 'darkred'
                  });
      
      
                  //retrieve geojson data
                  map_elt.spin(true);
                  $.ajax({
                     dataType: 'json',
                     method: 'POST',
                     url: '{$CFG_GLPI['root_doc']}/plugins/mydashboard/ajax/map.php',
                     data: {
                        itemtype: itemtype,
                        params: " . json_encode($params) . "
                     }
                  }).done(function(data) {
                     var _points = data.points;
                     var _markers = L.markerClusterGroup({
                        iconCreateFunction: function(cluster) {
                           var childCount = cluster.getChildCount();
      
                           var markers = cluster.getAllChildMarkers();
                           var n = 0;
                           for (var i = 0; i < markers.length; i++) {
                              n += markers[i].count;
                           }
      
                           var c = ' marker-cluster-';
                           if (n < 10) {
                              c += 'small';
                           } else if (n < 100) {
                              c += 'medium';
                           } else {
                              c += 'large';
                           }
      
                           return new L.DivIcon({ html: '<div><span>' + n + '</span></div>', className: 'marker-cluster' + c, iconSize: new L.Point(40, 40) });
                        }
                     });
      
                     $.each(_points, function(index, point) {
                        var _title = '<strong>' + point.title + '</strong><br/><a target=\'_blank\' href=\''+'$fulltarget'.replace(/CURLOCATION/, point.loc_id)+'\'>" . sprintf(__('%1$s %2$s'), 'COUNT', $typename) . "'.replace(/COUNT/, point.count)+'</a>';
                        if (point.types) {
                           $.each(point.types, function(tindex, type) {
                              _title += '<br/>" . sprintf(__('%1$s %2$s'), 'COUNT', 'TYPE') . "'.replace(/COUNT/, type.count).replace(/TYPE/, type.name);
                           });
                        }
                        var _icon = stdMarker;
                        if (point.count < 10) {
                           _icon = stdMarker;
                        } else if (point.count < 100) {
                           _icon = aMarker;
                        } else if (point.count < 1000) {
                           _icon = bMarker;
                        } else if (point.count < 5000) {
                           _icon = cMarker;
                        } else if (point.count < 10000) {
                           _icon = dMarker;
                        } else {
                           _icon = eMarker;
                        }
                        var _marker = L.marker([point.lat, point.lng], { icon: _icon, title: point.title });
                        _marker.count = point.count;
                        _marker.bindPopup(_title);
                        _markers.addLayer(_marker);
                     });
      
                     map_elt.addLayer(_markers);
                     map_elt.fitBounds(
                        _markers.getBounds(), {
                           padding: [50, 50],
                           maxZoom: 12
                        }
                     );
                  }).fail(function (response) {
                     var _data = response.responseJSON;
                     var _message = '" . __s('An error occured loading data :(') . "';
                     if (_data.message) {
                        _message = _data.message;
                     }
                     var fail_info = L.control();
                     fail_info.onAdd = function (map) {
                        this._div = L.DomUtil.create('div', 'fail_info');
                        this._div.innerHTML = _message + '<br/><span id=\'reload_data\'><i class=\'fa fa-refresh\'></i> " . __s('Reload') . "</span>';
                        return this._div;
                     };
                     fail_info.addTo(map_elt);
                     $('#reload_data').on('click', function() {
                        $('.fail_info').remove();
                        _loadMap(map_elt);
                     });
                  }).always(function() {
                     //hide spinner
                     map_elt.spin(false);
                  });
               };
               $(function() {
                       var map = initMap($('#TicketsByLocationOpenStreetMap'), 'map', '500px');
                         _loadMap(map, 'Ticket');
                   });
               ";
               $graph .= "</script>";
            }
            $graph .= "<div id=\"TicketsByLocationOpenStreetMap\" class=\"mapping\"></div>";
            $widget->toggleWidgetRefresh();
            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;

         case $this->getType() . "30":
            $name = 'RequestTypePieChart';
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['entities_id',
                             'is_recursive',
                             'type'];
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = ['type'];
            }

            $params  = ["preferences" => $this->preferences,
                        "criterias"   => $criterias,
                        "opt"         => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);

            $opt  = $options['opt'];
            $crit = $options['crit'];

            $type_criteria     = $crit['type'];
            $entities_criteria = $crit['entities_id'];
            $is_deleted        = "`glpi_tickets`.`is_deleted` = 0";

            $query = "SELECT DISTINCT
                           `glpi_requesttypes`.`name` AS name,
                           `glpi_requesttypes`.`id` AS requesttypes_id,
                           COUNT(`glpi_tickets`.`id`) AS nb
                        FROM `glpi_tickets`
                        LEFT JOIN `glpi_requesttypes`
                        ON (`glpi_requesttypes`.`id` = `glpi_tickets`.`requesttypes_id`)
                        WHERE $is_deleted $type_criteria ";
            $query .= $entities_criteria
                      . " AND `status` IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")
                      AND `glpi_tickets`.`requesttypes_id` > 0
                      GROUP BY `glpi_requesttypes`.`id`";

            $result = $DB->query($query);
            $nb     = $DB->numrows($result);

            $name_requesttypes = [];
            $datas             = [];
            $tabrequest        = [];

            if ($nb) {
               while ($data = $DB->fetch_array($result)) {
                  $name_requesttypes[] = $data['name'];
                  //                  $datas[]       = Html::formatNumber(($data['nb']*100)/$total);
                  $datas[]      = intval($data['nb']);
                  $tabrequest[] = $data['requesttypes_id'];
               }
            }
            $widget = new PluginMydashboardHtml();
            $title  = __("Number of use of request sources", "mydashboard");
            $widget->setWidgetComment(__("Display percent of request sources for closed tickets", "mydashboard"));
            $widget->setWidgetTitle((($isDebug) ? "30 " : "") . $title);

            $dataPieset         = json_encode($datas);
            $palette            = PluginMydashboardColor::getColors($nb);
            $backgroundPieColor = json_encode($palette);
            $labelsPie          = json_encode($name_requesttypes);
            $tabrequestset      = json_encode($tabrequest);

            $graph_datas = ['name'            => $name,
                            'ids'             => $tabrequestset,
                            'data'            => $dataPieset,
                            'labels'          => $labelsPie,
                            'label'           => $title,
                            'backgroundColor' => $backgroundPieColor];

            $graph = PluginMydashboardPieChart::launchDonutGraph($graph_datas, []);

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => false,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => $nb];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));

            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;

         case $this->getType() . "31":
            $name = 'RequestTypeEvolutionLineChart';
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['entities_id',
                             'is_recursive'];
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = [];
            }

            $params  = ["preferences" => $this->preferences,
                        "criterias"   => $criterias,
                        "opt"         => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);

            $opt  = $options['opt'];
            $crit = $options['crit'];

            $entities_criteria = $crit['entities_id'];
            $is_deleted        = "`glpi_tickets`.`is_deleted` = 0";

            $tabdata  = [];
            $tabnames = [];
            $tabyears = [];
            $i        = 0;

            $total = 0;


            $query = "SELECT DATE_FORMAT(`glpi_tickets`.`date`, '%Y') AS year, 
                        DATE_FORMAT(`glpi_tickets`.`date`, '%Y') AS yearname
                        FROM `glpi_tickets`
                        WHERE $is_deleted ";
            $query .= $entities_criteria . " 
                     GROUP BY DATE_FORMAT(`glpi_tickets`.`date`, '%Y')";

            $results = $DB->query($query);

            while ($data = $DB->fetch_array($results)) {

               $year = $data['year'];

               $query_0 = "SELECT COUNT(`requesttypes_id`) as count
                     FROM `glpi_tickets`
                     WHERE $is_deleted " . $entities_criteria . "
                     AND (`glpi_tickets`.`date` <= '$year-12-31 23:59:59') 
                     AND (`glpi_tickets`.`date` > ADDDATE('$year-01-01 00:00:00' , INTERVAL 1 DAY))";

               $results_0 = $DB->query($query_0);

               while ($data_0 = $DB->fetch_array($results_0)) {
                  $total = $data_0['count'];
               }

               $query_1 = "SELECT COUNT(`requesttypes_id`) as count,
                                 `glpi_requesttypes`.`name`as namerequest,
                                 `glpi_tickets`.`requesttypes_id`
                     FROM `glpi_tickets`
                     LEFT JOIN `glpi_requesttypes` ON (`glpi_tickets`.`requesttypes_id` = `glpi_requesttypes`.`id`)
                     WHERE $is_deleted " . $entities_criteria . "
                     AND (`glpi_tickets`.`date` <= '$year-12-31 23:59:59') 
                     AND (`glpi_tickets`.`date` > ADDDATE('$year-01-01 00:00:00' , INTERVAL 1 DAY))
                     GROUP BY `requesttypes_id`";

               $results_1 = $DB->query($query_1);

               while ($data_1 = $DB->fetch_array($results_1)) {
                  $percent                                    = round(($data_1['count'] * 100) / $total, 2);
                  $tabdata[$data_1['requesttypes_id']][$year] = $data_1['count'];
                  $tabnames[$data_1['requesttypes_id']]       = $data_1['namerequest'];
               }

               $tabyears[] = $data['yearname'];

               $i++;
            }

            if (isset($tabdata)) {
               foreach ($tabdata as $key => $val) {
                  foreach ($tabyears as $year) {
                     if (!isset($val[$year])) {
                        $tabdata[$key][$year] = 0;
                     }
                  }
                  ksort($tabdata[$key]);
               }
            }

            $labelsLine = json_encode($tabyears);
            $palette    = PluginMydashboardColor::getColors($i);
            $datasets   = [];

            foreach ($tabdata as $k => $v) {
               $datasets[] =
                  ['data'            => array_values($v),
                   'label'           => ($tabnames[$k] == NULL) ? __('None') : $tabnames[$k],
                   'backgroundColor' => $palette[$k],
                  ];
            }

            $widget = new PluginMydashboardHtml();
            $title  = __("Tickets request sources evolution", "mydashboard");
            $widget->setWidgetComment(__("Evolution of tickets request sources types by year", "mydashboard"));
            $widget->setWidgetTitle((($isDebug) ? "31 " : "") . $title);
            $widget->toggleWidgetRefresh();

            $years      = __('Year', 'mydashboard');
            $nbrequests = _n('Request source', 'Request sources', 2);

            $jsonsets = json_encode($datasets);

            $graph_datas = ['name'   => $name,
                            'ids'    => json_encode([]),
                            'data'   => $jsonsets,
                            'labels' => $labelsLine];

            $graph = PluginMydashboardBarChart::launchStackedGraph($graph_datas, []);

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => false,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => 1];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));

            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;

         case $this->getType() . "32":
            $name = 'NumberOfTicketsByTechnicianAndStatus';
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['entities_id',
                             'is_recursive',
                             'technicians_groups_id',
                             'group_is_recursive',
                             'users_id'];
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = [];
            }

            $params = ["preferences" => $this->preferences,
                       "criterias"   => $criterias,
                       "opt"         => $opt];

            $options = PluginMydashboardHelper::manageCriterias($params);
            $crit    = $options['crit'];
            $opt     = $options['opt'];

            $groups_sql_criteria = "";
            $entities_criteria   = $crit['entities_id'];
            $users_criteria      = "";
            $technician_group    = $opt['technicians_groups_id'];

            // GROUP
            if (isset($technician_group) && $technician_group != 0 && !empty($technician_group)) {
               $groups_sql_criteria = " AND `glpi_groups_users`.`groups_id`";
               if (is_array($technician_group)) {
                  $groups_sql_criteria .= " IN (" . implode(",", $technician_group) . ")";
               } else {
                  $groups_sql_criteria .= " = " . $technician_group;
               }
            }

            // USER
            if (isset($crit['users_id']) && $crit['users_id'] != 0 && !empty($crit['users_id'])) {
               $users_criteria = " AND `glpi_groups_users`.`users_id` = " . $crit['users_id'];
            }

            // Allowed status
            $statusList = [
               CommonITILObject::ASSIGNED,
               CommonITILObject::PLANNED,
               CommonITILObject::WAITING,
               CommonITILObject::SOLVED
            ];

            // List of technicians active and not deleted
            $query_technicians = "SELECT `glpi_groups_users`.`users_id`"
                                 . " FROM `glpi_groups_users`"
                                 . " LEFT JOIN `glpi_groups` ON (`glpi_groups_users`.`groups_id` = `glpi_groups`.`id`)"
                                 . " INNER JOIN `glpi_users` ON (`glpi_users`.`id` = `glpi_groups_users`.`users_id`)"
                                 . " WHERE `glpi_groups`.`is_assign` = 1"
                                 . " AND `glpi_users`.`is_active` = 1"
                                 . " AND `glpi_users`.`is_deleted` = 0"
                                 . $groups_sql_criteria
                                 . $users_criteria
                                 . " GROUP BY `glpi_groups_users`.`users_id`";
            // Number of tickets by technician and by status more ticket
            $plugin         = new Plugin();
            $moreTicketType = [];
            if ($plugin->isActivated('moreticket')) {
               $query_moretickets_by_technician_by_status = "SELECT count(*) as nb, `glpi_tickets_users`.`users_id` as userid,  `glpi_plugin_moreticket_waitingtickets`.`tickets_id` AS ticketid,"
                                                            . " `glpi_plugin_moreticket_waitingtypes`.`completename` AS statusname,"
                                                            . " `glpi_plugin_moreticket_waitingtickets`.`plugin_moreticket_waitingtypes_id` AS type"
                                                            . " FROM `glpi_plugin_moreticket_waitingtickets`"
                                                            . " INNER JOIN `glpi_tickets` ON `glpi_tickets`.`id` = `glpi_plugin_moreticket_waitingtickets`.`tickets_id`"
                                                            . " INNER JOIN `glpi_plugin_moreticket_waitingtypes`"
                                                            . " ON `glpi_plugin_moreticket_waitingtickets`.`plugin_moreticket_waitingtypes_id`=`glpi_plugin_moreticket_waitingtypes`.`id`"
                                                            . " INNER JOIN `glpi_tickets_users` ON (`glpi_tickets`.`id` = `glpi_tickets_users`.`tickets_id` AND `glpi_tickets_users`.`type` = 2 AND `glpi_tickets`.`is_deleted` = 0)"
                                                            . " LEFT JOIN `glpi_entities` ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`)"
                                                            . " GROUP BY userid,statusname"
                                                            . " ORDER BY statusname";
               $query_moreticket_type                     = "SELECT DISTINCT `glpi_plugin_moreticket_waitingtypes`.`completename` AS typename,"
                                                            . " `glpi_plugin_moreticket_waitingtypes`.`id` AS typeid FROM `glpi_plugin_moreticket_waitingtypes` ORDER BY typename";
               $result                                    = $DB->query($query_moreticket_type);
               $i                                         = 0;
               $moreTicketTypeName                        = [];
               while ($data = $DB->fetch_array($result)) {
                  $moreTicketType[$i]['name'] = $data['typename'];
                  $moreTicketType[$i]['id']   = $data['typeid'];
                  array_push($moreTicketTypeName, $data['typename']);
                  $i++;
               }
            }
            // Number of tickets by technician and by status
            // Tickets are not deleted
            // User Type is 2
            $query_tickets_by_technician_by_status = "SELECT COUNT(DISTINCT `glpi_tickets`.`id`) AS nbtickets"
                                                     . " FROM `glpi_tickets`"
                                                     . " INNER JOIN `glpi_tickets_users`"
                                                     . " ON (`glpi_tickets`.`id` = `glpi_tickets_users`.`tickets_id` AND `glpi_tickets_users`.`type` = 2 AND `glpi_tickets`.`is_deleted` = 0)"
                                                     . " LEFT JOIN `glpi_entities` ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`)"
                                                     . " WHERE `glpi_tickets`.`status` = %s"
                                                     . " AND `glpi_tickets_users`.`users_id` = '%s'"
                                                     . $entities_criteria;
            // Lists of tickets by technician by status
            $result = $DB->query($query_technicians);
            $nb     = $DB->numrows($result);
            $temp   = [];

            $typesTicketStatus = [__('Technician'),
                                  _x('status', 'Processing (assigned)'),
                                  _x('status', 'Processing (planned)'),
                                  __('Pending'),
                                  _x('status', 'Solved')];
            if ($nb) {
               $i = 0;
               while ($data = $DB->fetch_array($result)) {
                  $nbWaitingTickets = "";
                  $hasMoreTicket    = 0;
                  $userId           = $data['users_id'];
                  $username         = getUserName($userId);
                  $temp[$i]         = [0 => $username];
                  $j                = 1;
                  foreach ($statusList as $status) {
                     $query        = sprintf($query_tickets_by_technician_by_status, $status, $userId);
                     $temp[$i][$j] = 0;
                     $result2      = $DB->query($query);
                     $nb2          = $DB->numrows($result2);
                     if ($nb2) {
                        while ($data = $DB->fetch_assoc($result2)) {
                           $value            = "";
                           $nbWaitingTickets = $data['nbtickets'];
                           if ($data['nbtickets'] != "0") {
                              $value .= "<a href='#' onclick='" . $widgetId . "_search($userId, $status, $hasMoreTicket)'>";
                           }
                           $value .= $data['nbtickets'];
                           if ($data['nbtickets'] != "0") {
                              $value .= "</a>";
                           }
                           $temp[$i][$j] = $value;
                        }
                     }
                     $j++;
                  }
                  if ($plugin->isActivated('moreticket')) {
                     $result3       = $DB->query($query_moretickets_by_technician_by_status);
                     $hasMoreTicket = 1;
                     if ($DB->numrows($result3) > 0) {
                        while ($dataMoreTicket = $DB->fetch_assoc($result3)) {
                           $array[$dataMoreTicket['statusname']][$dataMoreTicket['userid']] = $dataMoreTicket['nb'];
                        }

                        foreach ($moreTicketType as $key => $value) {
                           $status   = $value['name'];
                           $statusId = $value['id'];
                           if (isset($array[$status][$userId])) {
                              $value        = '';
                              $value        .= "<a href='#' onclick='" . $widgetId . "_search($userId, $statusId , $hasMoreTicket)'>";
                              $value        .= $array[$status][$userId];
                              $value        .= "</a>";
                              $temp[$i][$j] = $value;
                              $newNbTickets = $nbWaitingTickets - $array[$status][$userId];
                              $temp[$i][3]  = str_replace('>' . $nbWaitingTickets . '<', '>' . $newNbTickets . '<', $temp[$i][3]);
                           } else {
                              $temp[$i][$j] = 0;
                           }
                           $j++;
                        }
                     }
                  }
                  $i++;
               }
               if ($plugin->isActivated('moreticket')) {
                  if (isset($array) && count($array) > 0) {
                     $typesTicketStatus = array_merge($typesTicketStatus, $moreTicketTypeName);
                  }
               }
            }

            $widget = new PluginMydashboardDatatable();
            $title  = __("Number of tickets open by technician and by status", "mydashboard");
            if ($nb > 1 || $nb == 0) {
               // String technicians never translated in glpi
               $title .= " : $nb " . __('Technicians', 'mydashboard');
            } else {
               $title .= " : $nb " . __('Technician');
            }
            $widget->setWidgetTitle((($isDebug) ? "32 " : "") . $title);

            $widget->setTabNames($typesTicketStatus);
            $widget->setTabDatas($temp);
            $widget->toggleWidgetRefresh();
            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => true,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => false,
                       "canvas"    => false,
                       "nb"        => $nb];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params) . "<br>");
            $linkURL   = $CFG_GLPI['root_doc'] . "/plugins/mydashboard/ajax/launchURL.php";
            $js_group  = json_encode($technician_group);
            $js_entity = $crit['entity'];
            $js_sons   = $crit['sons'];

            $js = "
               var " . $widgetId . "_search = function(_technician, _status, _hasMoreTicket){
                  $.ajax({
                     url: '" . $linkURL . "',
                     type: 'POST',
                     data:{
                        technician_group:$js_group,
                        entities_id:$js_entity, 
                        sons:$js_sons,
                        technician: _technician,
                        status: _status,
                        moreticket: _hasMoreTicket,
                        widget:'$widgetId'},
                     success:function(response) {
                        window.open(response);
                        console.log('SUCCESS');
                     },
                     error:function(response){
                        console.log('FAILED');
                     }
                  });
               }";
            echo Html::scriptBlock($js);
            return $widget;
            break;
         case $this->getType() . "33":
            $name = 'NumberOfTicketsByGroupAndStatus';
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['entities_id',
                             'is_recursive',
                             'technicians_groups_id',
                             'group_is_recursive'];
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = [];
            }

            $params = ["preferences" => $this->preferences,
                       "criterias"   => $criterias,
                       "opt"         => $opt];

            $options = PluginMydashboardHelper::manageCriterias($params);
            $crit    = $options['crit'];
            $opt     = $options['opt'];

            $groups_sql_criteria = "";
            $entities_criteria   = $crit['entities_id'];
            $technician_group    = $opt['technicians_groups_id'];

            // Allowed status
            $statusList = [
               CommonITILObject::ASSIGNED,
               CommonITILObject::PLANNED,
               CommonITILObject::WAITING,
               CommonITILObject::SOLVED
            ];

            // List of group active
            $condition        = "1=1";
            $technician_group = (is_array($technician_group) ? $technician_group : [$technician_group]);
            if (count($technician_group) > 0) {
               if (isset($opt['ancestors']) && $opt['ancestors'] != 0) {
                  $childs = [];

                  foreach ($technician_group as $k => $v) {
                     $childs = $dbu->getSonsAndAncestorsOf('glpi_groups', $v);
                  }
                  $condition .= " AND `id` IN ('" . implode("','", $childs) . "')";
               } else {

                  $condition .= " AND `id` IN ('" . implode("','", $technician_group) . "')";
               }
            }
            $iterator = $DB->request([
                                        'SELECT' => ['id', 'name'],
                                        'FROM'   => 'glpi_groups',
                                        'WHERE'  => [
                                           'is_assign' => 1,
                                           $condition
                                        ]
                                     ]);

            $plugin         = new Plugin();
            $moreTicketType = [];
            if ($plugin->isActivated('moreticket')) {
               $query_moretickets_by_group_by_status = "SELECT count(*) as nb, `glpi_groups_tickets`.`groups_id` as groups_id,  `glpi_plugin_moreticket_waitingtickets`.`tickets_id` AS ticketid,"
                                                       . " `glpi_plugin_moreticket_waitingtypes`.`completename` AS statusname,"
                                                       . " `glpi_plugin_moreticket_waitingtickets`.`plugin_moreticket_waitingtypes_id` AS type"
                                                       . " FROM `glpi_plugin_moreticket_waitingtickets`"
                                                       . " INNER JOIN `glpi_tickets` ON `glpi_tickets`.`id` = `glpi_plugin_moreticket_waitingtickets`.`tickets_id`"
                                                       . " INNER JOIN `glpi_plugin_moreticket_waitingtypes`"
                                                       . " ON `glpi_plugin_moreticket_waitingtickets`.`plugin_moreticket_waitingtypes_id`=`glpi_plugin_moreticket_waitingtypes`.`id`"
                                                       . " INNER JOIN `glpi_groups_tickets` ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id` AND `glpi_groups_tickets`.`type` = 2 
                                                            AND `glpi_tickets`.`is_deleted` = 0)"
                                                       . " LEFT JOIN `glpi_entities` ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`)"
                                                       . " GROUP BY groups_id,statusname"
                                                       . " ORDER BY statusname";

               $query_moreticket_type = "SELECT DISTINCT `glpi_plugin_moreticket_waitingtypes`.`completename` AS typename,"
                                        . " `glpi_plugin_moreticket_waitingtypes`.`id` AS typeid 
                                        FROM `glpi_plugin_moreticket_waitingtypes` ORDER BY typename";
               $result                = $DB->query($query_moreticket_type);
               $i                     = 0;
               $moreTicketTypeName    = [];
               while ($data = $DB->fetch_array($result)) {
                  $moreTicketType[$i]['name'] = $data['typename'];
                  $moreTicketType[$i]['id']   = $data['typeid'];
                  array_push($moreTicketTypeName, $data['typename']);
                  $i++;
               }
            }

            // Number of tickets by group and by status
            // Tickets are not deleted
            // group Type is 2
            $query_tickets_by_groups_by_status = "SELECT COUNT(DISTINCT `glpi_tickets`.`id`) AS nbtickets"
                                                 . " FROM `glpi_tickets`"
                                                 . " LEFT JOIN `glpi_groups_tickets`"
                                                 . " ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id` AND `glpi_groups_tickets`.`type` = '" . CommonITILActor::ASSIGN . "' 
                                                  AND `glpi_tickets`.`is_deleted` = 0)"
                                                 . " LEFT JOIN `glpi_entities` ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`)"
                                                 . " WHERE `glpi_tickets`.`status` = %s"
                                                 . " AND `glpi_groups_tickets`.`groups_id` = '%s'"
                                                 . $entities_criteria;

            // Lists of tickets by group by status
            $nb = count($iterator);

            $temp = [];

            if ($nb) {
               $i = 0;

               while ($data = $iterator->next()) {
                  $nbWaitingTickets = "";
                  $hasMoreTicket    = 0;
                  $groupId          = $data['id'];
                  $groupname        = $data['name'];

                  $temp[$i] = [0 => $groupname];

                  $j = 1;
                  foreach ($statusList as $status) {

                     $query = sprintf($query_tickets_by_groups_by_status, $status, $groupId);

                     $temp[$i][$j] = 0;

                     $result2 = $DB->query($query);
                     $nb2     = $DB->numrows($result2);

                     if ($nb2) {

                        while ($data = $DB->fetch_assoc($result2)) {

                           $value            = "";
                           $nbWaitingTickets = $data['nbtickets'];
                           if ($data['nbtickets'] != "0") {
                              $value .= "<a href='#' onclick='" . $widgetId . "_search($groupId, $status, $hasMoreTicket)'>";
                           }
                           $value .= $data['nbtickets'];
                           if ($data['nbtickets'] != "0") {
                              $value .= "</a>";
                           }
                           $temp[$i][$j] = $value;
                        }
                     }
                     $j++;
                  }
                  if ($plugin->isActivated('moreticket')) {
                     $result3       = $DB->query($query_moretickets_by_group_by_status);
                     $hasMoreTicket = 1;
                     if ($DB->numrows($result3) > 0) {
                        while ($dataMoreTicket = $DB->fetch_assoc($result3)) {
                           $array[$dataMoreTicket['statusname']][$dataMoreTicket['groups_id']] = $dataMoreTicket['nb'];
                        }
                        foreach ($moreTicketType as $key => $value) {
                           $status   = $value['name'];
                           $statusId = $value['id'];
                           if (isset($array[$status][$groupId])) {
                              $value        = '';
                              $value        .= "<a href='#' onclick='" . $widgetId . "_search($groupId, $statusId , $hasMoreTicket)'>";
                              $value        .= $array[$status][$groupId];
                              $value        .= "</a>";
                              $temp[$i][$j] = $value;
                              $newNbTickets = $nbWaitingTickets - $array[$status][$groupId];
                              $temp[$i][3]  = str_replace('>' . $nbWaitingTickets . '<', '>' . $newNbTickets . '<', $temp[$i][3]);
                           } else {
                              $temp[$i][$j] = 0;
                           }
                           $j++;
                        }
                     }
                  }
                  $i++;
               }
            }

            $widget = new PluginMydashboardDatatable();

            $title = __("Number of opened tickets by group and by status", "mydashboard");

            if ($nb > 1 || $nb == 0) {
               // String technicians never translated in glpi
               $title .= " : $nb " . _n('Group', 'Groups', $nb);
            } else {
               $title .= " : $nb " . __('Group');
            }

            $widget->setWidgetTitle((($isDebug) ? "33 " : "") . $title);

            $typesTicketStatus = [__('Group'),
                                  _x('status', 'Processing (assigned)'),
                                  _x('status', 'Processing (planned)'),
                                  __('Pending'),
                                  _x('status', 'Solved')];
            if (count($moreTicketType) > 0) {
               $typesTicketStatus = array_merge($typesTicketStatus, $moreTicketTypeName);
            }
            $widget->setTabNames($typesTicketStatus);
            $widget->setTabDatas($temp);
            $widget->toggleWidgetRefresh();

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => true,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => false,
                       "canvas"    => false,
                       "nb"        => $nb];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params) . "<br>");

            $linkURL = $CFG_GLPI['root_doc'] . "/plugins/mydashboard/ajax/launchURL.php";

            $js_entity = $crit['entity'];
            $js_sons   = $crit['sons'];

            $js = "
               var " . $widgetId . "_search = function(_group, _status, _hasMoreTicket){
                  $.ajax({
                     url: '" . $linkURL . "',
                     type: 'POST',
                     data:{
                        entities_id:$js_entity, 
                        sons:$js_sons,
                        technician_group: _group,
                        moreticket: _hasMoreTicket,
                        status: _status,
                        widget:'$widgetId'},
                     success:function(response) {
                        window.open(response);
                        console.log('SUCCESS');
                     },
                     error:function(response){
                        console.log('FAILED');
                     }
                  });
               }";

            echo Html::scriptBlock($js);

            return $widget;
            break;

         case $this->getType() . "34":
            $name = 'TicketStatusResolvedBarLineChart';
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['entities_id',
                             'technicians_groups_id',
                             'group_is_recursive',
                             'requesters_groups_id',
                             'is_recursive',
                             'technicians_id',
                             'year',
                             'type',
                             'locations_id'];
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = ['requesters_groups_id',
                             'year',
                             'locations_id'];
            }

            $params  = ["preferences" => $this->preferences,
                        "criterias"   => $criterias,
                        "opt"         => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);

            $opt  = $options['opt'];
            $crit = $options['crit'];

            $type_criteria              = $crit['type'];
            $entities_criteria          = $crit['entities_id'];
            $requester_groups_criteria  = $crit['requesters_groups_id'];
            $technician_groups_criteria = $crit['technicians_groups_id'];
            $mdentities                 = self::getSpecificEntityRestrict("glpi_plugin_mydashboard_stocktickets", $opt);

            $ticket_users_join   = "";
            $technician_criteria = "";

            if (isset($opt['technicians_id']) && $opt['technicians_id'] != 0) {
               $ticket_users_join   = "INNER JOIN glpi_tickets_users ON glpi_tickets_users.tickets_id = glpi_tickets.id";
               $technician_criteria = "AND glpi_tickets_users.type = " . CommonITILObject::ASSIGNED;
               $technician_criteria .= " AND glpi_tickets_users.users_id = " . $opt['technicians_id'];
            }

            $location           = $opt['locations_id'];
            $locations_criteria = $crit['locations_id'];

            $currentyear = date("Y");

            if (isset($opt["year"]) && $opt["year"] > 0) {
               $currentyear = $opt["year"];
            }
            $currentmonth          = date("m");
            $tech_groups_crit      = "";
            $technician_groups_ids = is_array($opt['technicians_groups_id']) ? $opt['technicians_groups_id'] : [$opt['technicians_groups_id']];
            if (count($opt['technicians_groups_id']) > 0) {
               $tech_groups_crit = " AND `groups_id` IN (" . implode(",", $technician_groups_ids) . ")";
            }

            $query_stockTickets =
               "SELECT DATE_FORMAT(`glpi_plugin_mydashboard_stocktickets`.`date`, '%Y-%m') as month," .
               " DATE_FORMAT(`glpi_plugin_mydashboard_stocktickets`.`date`, '%b %Y') as monthname," .
               " SUM(nbStockTickets) as nbStockTickets" .
               " FROM `glpi_plugin_mydashboard_stocktickets`" .
               " WHERE `glpi_plugin_mydashboard_stocktickets`.`date` between '$currentyear-01-01' AND ADDDATE('$currentyear-01-01', INTERVAL 1 YEAR)" .
               " " . $mdentities . $tech_groups_crit .
               " AND `glpi_plugin_mydashboard_stocktickets`.`groups_id` >= 0 GROUP BY DATE_FORMAT(`glpi_plugin_mydashboard_stocktickets`.`date`, '%Y-%m')";

            $resultsStockTickets = $DB->query($query_stockTickets);
            $nbStockTickets      = $DB->numrows($resultsStockTickets);
            $maxcount            = 0;
            $i                   = 0;
            $tabopened           = [];
            $tabresolved         = [];
            $tabprogress         = [];
            $tabnames            = [];
            if ($nbStockTickets) {
               while ($data = $DB->fetch_array($resultsStockTickets)) {
                  $tabprogress[] = $data["nbStockTickets"];
                  if ($data["nbStockTickets"] > $maxcount) {
                     $maxcount = $data["nbStockTickets"];
                  }
                  $i++;
               }
            }

            $is_deleted = "`glpi_tickets`.`is_deleted` = 0";

            $query_tickets =
               "SELECT DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m') as month," .
               " DATE_FORMAT(`glpi_tickets`.`date`, '%b %Y') as monthname," .
               " DATE_FORMAT(`glpi_tickets`.`date`, '%Y%m') AS monthnum, count(MONTH(`glpi_tickets`.`date`))" .
               " FROM `glpi_tickets`" .
               " WHERE $is_deleted" .
               " AND `glpi_tickets`.`date` between '$currentyear-01-01' AND ADDDATE('$currentyear-01-01', INTERVAL 1 YEAR)" .
               " $entities_criteria" .
               " $requester_groups_criteria" .
               " $technician_groups_criteria" .
               " $locations_criteria" .
               " $type_criteria" .
               " GROUP BY DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m')";

            $results   = $DB->query($query_tickets);
            $nbResults = $DB->numrows($results);
            $i         = 0;
            if ($nbResults) {
               while ($data = $DB->fetch_array($results)) {

                  $tabnames[] = $data['monthname'];

                  list($year, $month) = explode('-', $data['month']);

                  $nbdays = date("t", mktime(0, 0, 0, $month, 1, $year));

                  $date_criteria = " `glpi_tickets`.`date` between '$year-$month-01' AND ADDDATE('$year-$month-01', INTERVAL 1 MONTH)";

                  $query_1 =
                     "SELECT COUNT(*) as count FROM `glpi_tickets`" .
                     " $ticket_users_join" .
                     " WHERE $date_criteria" .
                     " $technician_criteria" .
                     " $entities_criteria" .
                     " $requester_groups_criteria" .
                     " $technician_groups_criteria" .
                     " $locations_criteria" .
                     " $type_criteria" .
                     " AND $is_deleted";

                  $results_1 = $DB->query($query_1);

                  if ($DB->numrows($results_1)) {
                     $data_1      = $DB->fetch_array($results_1);
                     $tabopened[] = $data_1['count'];
                  } else {
                     $tabopened[] = 0;
                  }

                  $solvedate_criteria = " (`glpi_tickets`.`solvedate` between '$year-$month-01' AND ADDDATE('$year-$month-01', INTERVAL 1 MONTH) 
                  OR `glpi_tickets`.`closedate` between '$year-$month-01' AND ADDDATE('$year-$month-01', INTERVAL 1 MONTH))";

                  $query_2 =
                     "SELECT COUNT(*) as count FROM `glpi_tickets`" .
                     " $ticket_users_join" .
                     " WHERE $solvedate_criteria" .
                     " $technician_criteria" .
                     " $entities_criteria" .
                     " $requester_groups_criteria" .
                     " $technician_groups_criteria" .
                     " $locations_criteria" .
                     " $type_criteria" .
                     " AND $is_deleted";

                  $results_2 = $DB->query($query_2);

                  if ($DB->numrows($results_2)) {
                     $data_2        = $DB->fetch_array($results_2);
                     $tabresolved[] = $data_2['count'];
                  } else {
                     $tabresolved[] = 0;
                  }

                  if ($month == date("m") && $year == date("Y")) {

                     $nbdays = date("t", mktime(0, 0, 0, $month, 1, $year));
                     //nbstock : cannot use tech or group criteria

                     $query_3 =
                        "SELECT COUNT(*) as count FROM `glpi_tickets`" .
                        //                        " $ticket_users_join".
                        " WHERE $is_deleted" .
                        " $technician_groups_criteria" .
                        " $entities_criteria" .
                        //                        " $requester_groups_criteria".
                        //                        " $locations_criteria" .
                        // Tickets open in the month
                        " AND (((`glpi_tickets`.`date` <= '$year-$month-$nbdays 23:59:59') 
                           AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")) " .
                        // Tickets solved in the month
                        "OR ((`glpi_tickets`.`date` <= '$year-$month-$nbdays 23:59:59') 
                           AND (`glpi_tickets`.`solvedate` > ADDDATE('$year-$month-$nbdays 00:00:00' , INTERVAL 1 DAY))))";

                     $results_3 = $DB->query($query_3);

                     if ($DB->numrows($results_3)) {
                        $data_3        = $DB->fetch_array($results_3);
                        $tabprogress[] = $data_3['count'];
                     } else {
                        $tabprogress[] = 0;
                     }
                  }

                  $i++;
               }
            }

            $widget = new PluginMydashboardHtml();
            $title  = __("Number of opened and resolved / closed tickets by month", "mydashboard");
            $widget->setWidgetTitle((($isDebug) ? "34 " : "") . $title);
            $widget->toggleWidgetRefresh();

            $titleopened   = __("Opened tickets", "mydashboard");
            $titlesolved   = __("Closed tickets", "mydashboard");
            $titleprogress = __("Opened tickets backlog", "mydashboard");
            $labels        = json_encode($tabnames);

            $datasets[] =
               ['type'        => 'line',
                'data'        => $tabprogress,
                'label'       => $titleprogress,
                'borderColor' => '#ff7f0e',
                'fill'        => false,
                'lineTension' => '0.1',
               ];

            $datasets[] =
               ["type"            => "bar",
                "data"            => $tabopened,
                "label"           => $titleopened,
                'backgroundColor' => '#1f77b4',
               ];

            $datasets[] =
               ['type'            => 'bar',
                'data'            => $tabresolved,
                'label'           => $titlesolved,
                'backgroundColor' => '#aec7e8',
               ];

            $graph_datas = ['name'   => $name,
                            'ids'    => json_encode([]),
                            'data'   => json_encode($datasets),
                            'labels' => $labels,
                            'label'  => $title];

            $graph = PluginMydashboardBarChart::launchMultipleGraph($graph_datas, []);

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => true,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => 1];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));
            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;

            break;

         case $this->getType() . "35":
            $name = 'AgeBarChart';
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['entities_id',
                             'is_recursive',
                             'type',
                             'technicians_groups_id',
                             'group_is_recursive',
                             'group_is_recursive'];
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = ['type'];
            }

            $params  = ["preferences" => $this->preferences,
                        "criterias"   => $criterias,
                        "opt"         => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);

            $opt  = $options['opt'];
            $crit = $options['crit'];

            $type_criteria              = $crit['type'];
            $entities_criteria          = $crit['entities_id'];
            $technician_group           = $opt['technicians_groups_id'];
            $technician_groups_criteria = $crit['technicians_groups_id'];

            $is_deleted = "`glpi_tickets`.`is_deleted` = 0";

            $query = "SELECT  CONCAT ('< 1 Semaine') Age, COUNT(*) Total, COUNT(*) * 100 / 
                (SELECT COUNT(*) FROM glpi_tickets WHERE $is_deleted $type_criteria $technician_groups_criteria $entities_criteria AND `glpi_tickets`.`status` NOT IN ('" . Ticket::CLOSED . "', '" . Ticket::SOLVED . "')) Percent
                FROM glpi_tickets  WHERE glpi_tickets.date > CURRENT_TIMESTAMP - INTERVAL 1 WEEK
                AND $is_deleted
                 $type_criteria
                 $technician_groups_criteria
                 $entities_criteria
                AND `glpi_tickets`.`status` NOT IN ('" . Ticket::CLOSED . "', '" . Ticket::SOLVED . "')
                UNION
                SELECT CONCAT ('> 1 Semaine') Age, COUNT(*) Total, COUNT(*) * 100 / 
                (SELECT COUNT(*) FROM glpi_tickets WHERE $is_deleted $type_criteria $technician_groups_criteria $entities_criteria AND `glpi_tickets`.`status` NOT IN ('" . Ticket::CLOSED . "', '" . Ticket::SOLVED . "')) Percent
                FROM glpi_tickets  WHERE glpi_tickets.date <= CURRENT_TIMESTAMP - INTERVAL 1 WEEK
                AND  glpi_tickets.date > CURRENT_TIMESTAMP - INTERVAL 1 MONTH
                AND $is_deleted
                 $type_criteria
                 $technician_groups_criteria
                 $entities_criteria
                AND `glpi_tickets`.`status` NOT IN ('" . Ticket::CLOSED . "', '" . Ticket::SOLVED . "')
                UNION
                SELECT CONCAT ('> 1 Mois') Age, COUNT(*) Total, COUNT(*) * 100 / 
                (SELECT COUNT(*) FROM glpi_tickets WHERE $is_deleted $type_criteria $technician_groups_criteria $entities_criteria AND `glpi_tickets`.`status` NOT IN ('" . Ticket::CLOSED . "', '" . Ticket::SOLVED . "')) Percent
                FROM glpi_tickets  WHERE glpi_tickets.date <= CURRENT_TIMESTAMP - INTERVAL 1 MONTH
                AND  glpi_tickets.date > CURRENT_TIMESTAMP - INTERVAL 3 MONTH
                AND $is_deleted
                 $type_criteria
                 $technician_groups_criteria
                 $entities_criteria
                AND `glpi_tickets`.`status` NOT IN ('" . Ticket::CLOSED . "', '" . Ticket::SOLVED . "')
                UNION
                SELECT CONCAT ('> 3 Mois') Age, COUNT(*) Total, COUNT(*) * 100 / 
                (SELECT COUNT(*) FROM glpi_tickets WHERE $is_deleted $type_criteria $technician_groups_criteria $entities_criteria AND `glpi_tickets`.`status` NOT IN ('" . Ticket::CLOSED . "', '" . Ticket::SOLVED . "')) Percent
                FROM glpi_tickets  WHERE glpi_tickets.date <= CURRENT_TIMESTAMP - INTERVAL 3 MONTH
                AND  glpi_tickets.date > CURRENT_TIMESTAMP - INTERVAL 6 MONTH
                AND $is_deleted
                 $type_criteria
                 $technician_groups_criteria
                 $entities_criteria
                AND `glpi_tickets`.`status` NOT IN ('" . Ticket::CLOSED . "', '" . Ticket::SOLVED . "')
                UNION
                SELECT CONCAT ('> 6 Mois') Age, COUNT(*) Total, COUNT(*) * 100 / 
                (SELECT COUNT(*) FROM glpi_tickets WHERE $is_deleted $type_criteria $technician_groups_criteria $entities_criteria AND `glpi_tickets`.`status` NOT IN ('" . Ticket::CLOSED . "', '" . Ticket::SOLVED . "')) Percent
                FROM glpi_tickets  WHERE glpi_tickets.date <= CURRENT_TIMESTAMP - INTERVAL 6 MONTH
                AND $is_deleted
                 $type_criteria
                 $technician_groups_criteria
                 $entities_criteria
                AND `glpi_tickets`.`status` NOT IN ('" . Ticket::CLOSED . "', '" . Ticket::SOLVED . "')";

            $results  = $DB->query($query);
            $tabage   = [];
            $tabnames = [];
            while ($data = $DB->fetch_array($results)) {
               $percent    = round($data['Percent'], 2);
               $tabnames[] = $data['Age']; //" (".$percent."%)";
               $tabage[]   = $data['Total'];
            }

            $widget = new PluginMydashboardHtml();
            $widget->setWidgetTitle((($isDebug) ? "35 " : "") . __("Age of tickets", "mydashboard"));
            $dataLineset = json_encode($tabage);
            $labelsLine  = json_encode($tabnames);

            $title = __("Age of tickets", "mydashboard");
            $widget->setWidgetTitle((($isDebug) ? "35 " : "") . $title);

            $colors          = ['rgb(32, 142, 61)', 'rgb(255, 247, 69)', 'rgb(255, 165, 0)', 'rgb(237, 89, 83)', 'rgb(237, 35, 28)'];
            $backgroundColor = json_encode($colors);

            $graph_datas = ['name'            => $name,
                            'ids'             => json_encode([]),
                            'data'            => $dataLineset,
                            'labels'          => $labelsLine,
                            'label'           => $title,
                            'backgroundColor' => $backgroundColor];

            $graph = PluginMydashboardBarChart::launchGraph($graph_datas, []);
            $widget->setWidgetHtmlContent($graph);

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => true,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => 1];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));
            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;

         case $this->getType() . "36":
            $name = 'TicketsByPriorityBarChart';
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['entities_id',
                             'is_recursive',
                             'type',
                             'technicians_groups_id',
                             'group_is_recursive'];
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = ['type'];
            }

            $params  = ["preferences" => $this->preferences,
                        "criterias"   => $criterias,
                        "opt"         => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);

            $opt                        = $options['opt'];
            $crit                       = $options['crit'];
            $type                       = $opt['type'];
            $type_criteria              = $crit['type'];
            $entities_criteria          = $crit['entities_id'];
            $entities_id_criteria       = $crit['entity'];
            $sons_criteria              = $crit['sons'];
            $is_deleted                 = "`glpi_tickets`.`is_deleted` = 0";
            $technician_group           = $opt['technicians_groups_id'];
            $technician_groups_criteria = $crit['technicians_groups_id'];

            $query = "SELECT DISTINCT
                           `priority`,
                           COUNT(`id`) AS nb
                        FROM `glpi_tickets`
                        WHERE $is_deleted $type_criteria $entities_criteria $technician_groups_criteria";
            $query .= " AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ") ";
            $query .= " GROUP BY `priority` ORDER BY `priority` ASC";

            $colors = [];
            $result = $DB->query($query);
            $nb     = $DB->numrows($result);

            $name_priority = [];
            $datas         = [];
            $tabpriority   = [];
            if ($nb) {
               while ($data = $DB->fetch_array($result)) {
                  $name_priority[] = CommonITILObject::getPriorityName($data['priority']);
                  $colors[]        = $_SESSION["glpipriority_" . $data['priority']];
                  $datas[]         = $data['nb'];
                  $tabpriority[]   = $data['priority'];
               }
            }

            $widget = new PluginMydashboardHtml();
            $title  = __("Number of opened tickets by priority", "mydashboard");
            $widget->setWidgetTitle((($isDebug) ? "36 " : "") . $title);


            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => true,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => 1];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));

            $dataset         = json_encode($datas);
            $backgroundColor = json_encode($colors);
            $labels          = json_encode($name_priority);
            $tabpriorityset  = json_encode($tabpriority);
            $js_ancestors    = $crit['ancestors'];

            $graph_datas = ['name'            => $name,
                            'ids'             => $tabpriorityset,
                            'data'            => $dataset,
                            'labels'          => $labels,
                            'label'           => $title,
                            'backgroundColor' => $backgroundColor];

            $graph_criterias = ['entities_id'        => $entities_id_criteria,
                                'sons'               => $sons_criteria,
                                'technician_group'   => $technician_group,
                                'group_is_recursive' => $js_ancestors,
                                'type'               => $type,
                                'widget'             => $widgetId];

            $graph = PluginMydashboardBarChart::launchGraph($graph_datas, $graph_criterias);
            $widget->setWidgetHtmlContent($graph);

            return $widget;
            break;

         case $this->getType() . "37":
            $name = 'TicketsByStatusBarChart';
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['entities_id',
                             'is_recursive',
                             'type',
                             'technicians_groups_id',
                             'group_is_recursive'];
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = ['type'];
            }

            $params  = ["preferences" => $this->preferences,
                        "criterias"   => $criterias,
                        "opt"         => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);

            $opt                        = $options['opt'];
            $crit                       = $options['crit'];
            $type                       = $opt['type'];
            $type_criteria              = $crit['type'];
            $entities_criteria          = $crit['entities_id'];
            $entities_id_criteria       = $crit['entity'];
            $sons_criteria              = $crit['sons'];
            $is_deleted                 = "`glpi_tickets`.`is_deleted` = 0";
            $technician_group           = $opt['technicians_groups_id'];
            $technician_groups_criteria = $crit['technicians_groups_id'];

            $query = "SELECT `glpi_tickets`.`status` AS status, COUNT(`glpi_tickets`.`id`) AS Total
                FROM glpi_tickets
                WHERE $is_deleted
                $type_criteria
                $technician_groups_criteria
                $entities_criteria
                AND `glpi_tickets`.`status` IN (" . implode(",", Ticket::getNotSolvedStatusArray()) . ")
                GROUP BY `glpi_tickets`.`status`";

            //            $colors = [];

            $result = $DB->query($query);
            $nb     = $DB->numrows($result);

            $name_status = [];
            $datas       = [];
            $tabstatus   = [];
            $colors      = PluginMydashboardColor::getColors(10);
            if ($nb) {
               while ($data = $DB->fetch_array($result)) {
                  foreach (Ticket::getAllStatusArray() as $value => $names) {
                     if ($data['status'] == $value) {
                        $datas[]       = $data['Total'];
                        $name_status[] = $names;
                        $tabstatus[]   = $data['status'];
                     }
                  }
               }
            }
            $plugin = new Plugin();
            if ($plugin->isActivated('moreticket')) {
               $moreTicketToShow = [];
               foreach (self::getAllMoreTicketStatus() as $id => $names) {
                  $moreTicketToShow[] = $id;
               }
               $moreTicketToShow = ' AND `glpi_plugin_moreticket_waitingtickets`.`plugin_moreticket_waitingtypes_id`  IN (' . implode(',', $moreTicketToShow) . ')';
               $query_moreticket = "SELECT COUNT(*) AS Total,   
                         `glpi_plugin_moreticket_waitingtickets`.`plugin_moreticket_waitingtypes_id` AS status
                          FROM `glpi_plugin_moreticket_waitingtickets`
                          INNER JOIN `glpi_tickets` ON `glpi_tickets`.`id` = `glpi_plugin_moreticket_waitingtickets`.`tickets_id`
                          INNER JOIN `glpi_plugin_moreticket_waitingtypes` ON `glpi_plugin_moreticket_waitingtickets`.`plugin_moreticket_waitingtypes_id`=`glpi_plugin_moreticket_waitingtypes`.`id`
                          WHERE $is_deleted
                          $type_criteria
                          $moreTicketToShow
                          $entities_criteria
                          GROUP BY status";

               $result_more_ticket = $DB->query($query_moreticket);
               $rows               = $DB->numrows($result_more_ticket);
               if ($rows) {
                  while ($ticket = $DB->fetch_assoc($result_more_ticket)) {
                     foreach (self::getAllMoreTicketStatus() as $value => $names) {
                        if ($ticket['status'] == $value) {
                           $datas[]       = $ticket['Total'];
                           $name_status[] = $names;
                           $tabstatus[]   = 'moreticket_' . $ticket['status'];
                        }
                     }
                  }
               }
            }

            $widget = new PluginMydashboardHtml();
            $title  = __("Stock of tickets by status", "mydashboard");
            $widget->setWidgetTitle((($isDebug) ? "37 " : "") . $title);


            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => true,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => 1];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));

            $dataset         = json_encode($datas);
            $backgroundColor = json_encode($colors);
            $labels          = json_encode($name_status);
            $tabstatusset    = json_encode($tabstatus);
            $js_ancestors    = $crit['ancestors'];

            $graph_datas = ['name'            => $name,
                            'ids'             => $tabstatusset,
                            'data'            => $dataset,
                            'labels'          => $labels,
                            'label'           => $title,
                            'backgroundColor' => $backgroundColor];

            $graph_criterias = ['entities_id'        => $entities_id_criteria,
                                'sons'               => $sons_criteria,
                                'technician_group'   => $technician_group,
                                'group_is_recursive' => $js_ancestors,
                                'type'               => $type,
                                'widget'             => $widgetId];

            $graph = PluginMydashboardBarChart::launchGraph($graph_datas, $graph_criterias);
            $widget->setWidgetHtmlContent($graph);

            return $widget;
            break;
            
         case $this->getType() . "38":
            $name = 'NumberOfOpenedTicketAndAverageSatisfactionPerTrimester';
            $criterias = [];
            $params  = ["preferences" => $this->preferences,
               "criterias"   => $criterias,
               "opt"         => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);

            $opt                        = $options['opt'];
            $crit                       = $options['crit'];
            $is_deleted                 = "`glpi_tickets`.`is_deleted` = 0";
            $opened_tickets_data = [];
            $satisfaction_data = [];
            $tabnames = [];
            $starting_year = 2018;
            $ending_year = date('Y');
            for($starting_year; $starting_year <= $ending_year; $starting_year++){
               // Checking T1
               array_push($tabnames, 'Trimestre 1 '.$starting_year);
               // Number of tickets opened
               $query_openedTicketT1 = "SELECT count(MONTH(`glpi_tickets`.`date`)) FROM `glpi_tickets` 
                                        WHERE $is_deleted
                                        AND `glpi_tickets`.`date` between '$starting_year-01-01' AND '$starting_year-03-31'";
               $result = $DB->query($query_openedTicketT1);
               $dataT1 = $DB->fetch_array($result);
               array_push($opened_tickets_data, $dataT1[0]);
               // Average Satisfaction
               $query_satisfatcionT1 = "SELECT AVG(satisfaction)
                                        FROM `glpi_tickets` INNER JOIN `glpi_ticketsatisfactions` ON `glpi_tickets`.`id` = `glpi_ticketsatisfactions`.`tickets_id` 
                                        WHERE `glpi_tickets`.`is_deleted` = 0 
                                        AND `glpi_tickets`.`date` between '$starting_year-01-01' AND '$starting_year-03-31'";
               $result = $DB->query($query_satisfatcionT1);
               $data_satisfactionT1 = $DB->fetch_array($result);
               array_push($satisfaction_data, round($data_satisfactionT1[0],2));
               // Checking T2

               array_push($tabnames, 'Trimestre 2 '.$starting_year);
               $query_openedTicketT2 = "SELECT count(MONTH(`glpi_tickets`.`date`)) FROM `glpi_tickets` 
                                        WHERE $is_deleted
                                        AND `glpi_tickets`.`date` between '$starting_year-04-01' AND '$starting_year-06-30'";
               $result = $DB->query($query_openedTicketT2);
               $dataT2 = $DB->fetch_array($result);
               array_push($opened_tickets_data, $dataT2[0]);
               // Average Satisfaction
               $query_satisfatcionT2 = "SELECT AVG(satisfaction)
                                        FROM `glpi_tickets` INNER JOIN `glpi_ticketsatisfactions` ON `glpi_tickets`.`id` = `glpi_ticketsatisfactions`.`tickets_id` 
                                        WHERE `glpi_tickets`.`is_deleted` = 0 
                                        AND `glpi_tickets`.`date` between '$starting_year-04-01' AND '$starting_year-06-30'";
               $result = $DB->query($query_satisfatcionT2);
               $data_satisfactionT2 = $DB->fetch_array($result);
               array_push($satisfaction_data, round($data_satisfactionT2[0],2));
               // Checking T3
               array_push($tabnames, 'Trimestre 3 '.$starting_year);
               $query_openedTicketT3 = "SELECT count(MONTH(`glpi_tickets`.`date`)) FROM `glpi_tickets` 
                                        WHERE $is_deleted
                                        AND `glpi_tickets`.`date` between '$starting_year-06-01' AND '$starting_year-09-30'";
               $result = $DB->query($query_openedTicketT3);
               $dataT3 = $DB->fetch_array($result);
               array_push($opened_tickets_data, $dataT3[0]);
               // Average Satisfaction
               $query_satisfatcionT3 = "SELECT AVG(satisfaction)
                                        FROM `glpi_tickets` INNER JOIN `glpi_ticketsatisfactions` ON `glpi_tickets`.`id` = `glpi_ticketsatisfactions`.`tickets_id` 
                                        WHERE `glpi_tickets`.`is_deleted` = 0 
                                        AND `glpi_tickets`.`date` between '$starting_year-06-01' AND '$starting_year-09-30'";
               $result = $DB->query($query_satisfatcionT3);
               $data_satisfactionT3 = $DB->fetch_array($result);
               array_push($satisfaction_data, round($data_satisfactionT3[0],2));
               // Checking T4
               array_push($tabnames, 'Trimestre 4 '.$starting_year);
               $query_openedTicketT4 = "SELECT count(MONTH(`glpi_tickets`.`date`)) FROM `glpi_tickets` 
                                        WHERE $is_deleted
                                        AND `glpi_tickets`.`date` between '$starting_year-09-01' AND '$starting_year-12-31'";
               $result = $DB->query($query_openedTicketT4);
               $dataT4 = $DB->fetch_array($result);
               array_push($opened_tickets_data, $dataT4[0]);
               // Average Satisfaction
               $query_satisfatcionT4 = "SELECT AVG(satisfaction)
                                        FROM `glpi_tickets` INNER JOIN `glpi_ticketsatisfactions` ON `glpi_tickets`.`id` = `glpi_ticketsatisfactions`.`tickets_id` 
                                        WHERE `glpi_tickets`.`is_deleted` = 0 
                                        AND `glpi_tickets`.`date` between '$starting_year-09-01' AND '$starting_year-12-31'";
               $result = $DB->query($query_satisfatcionT4);
               $data_satisfactionT4 = $DB->fetch_array($result);
               array_push($satisfaction_data, round($data_satisfactionT4[0],2));
            }

            $widget = new PluginMydashboardHtml();
            $title  = __("Number of opened ticket and average satisfaction per trimester", "mydashboard");
            $widget->setWidgetTitle((($isDebug) ? "37 " : "") . $title);
            $widget->toggleWidgetRefresh();

            $params = ["widgetId"  => $widgetId,
               "name"      => $name,
               "onsubmit"  => true,
               "opt"       => $opt,
               "criterias" => $criterias,
               "export"    => true,
               "canvas"    => true,
               "nb"        => 1];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));

            $titleOpenedTicket = __("Opened Ticket","mydashboard");
            $titleSatisfactionTicket = __("Average Satisfaction","mydashboad");
            $labels = json_encode($tabnames);

            $datasets[] =
               ['type'        => 'bar',
                  'data'        => $opened_tickets_data,
                  'label'       => $titleOpenedTicket,
                  'borderColor' => '#ff7f0e',
               ];

            $datasets[] =
               ['type'        => 'line',
                  'data'        => $satisfaction_data,
                  'label'       => $titleSatisfactionTicket,
                  'borderColor' => '#ff7f0e',
                  'fill'        => false,
                  'lineTension' => '0.1',
               ];

            $graph_datas = ['name'            => $name,
               'ids'             => json_encode([]),
               'data'            => json_encode($datasets),
               'labels'          => $labels,
               'label'           => $title,
               'backgroundColor' => "#1f77b4"];

            $graph = PluginMydashboardBarChart::launchMultipleGraph($graph_datas, []);
            $widget->setWidgetHtmlContent($graph);
            return $widget;
            break;

         default:
         {
            // It's a custom widget
            if (strpos($widgetId, "cw")) {

               // Last letter of widgetId is customWidget index in database
               $id = intval(substr($widgetId, -1));

               $content = PluginMydashboardCustomswidget::getCustomWidget($id);

               $widget = new PluginMydashboardHtml(false);

               $widget->setWidgetTitle("");

               $htmlContent = html_entity_decode($content['content']);

               // Edit style to avoid padding, margin, and limited width

               $htmlContent .= "<script>
                $( document ).ready(function() {
                    let $widgetId = document.getElementById('$widgetId');
                    " . $widgetId . ".children[0].style.marginTop = '-5px';
                    " . $widgetId . ".children[0].children[0].classList.remove('bt-col-md-11');
                    " . $widgetId . ".children[0].children[0].classList.add('bt-col-md-12');
                    " . $widgetId . ".children[0].children[0].children[0].style = 'padding-left : 0% !important; margin-right : 28px;margin-bottom: -10px;';
                });
                </script>";

               $widget->setWidgetHtmlContent($htmlContent);

               return $widget;
            }
         }
      }
   }


   static function getAllMoreTicketStatus() {
      global $DB;

      $tabs   = [];
      $plugin = new Plugin();
      if ($plugin->isActivated('moreticket')) {
         $query  = "SELECT `glpi_plugin_moreticket_waitingtypes`.`completename` as name, 
                   `glpi_plugin_moreticket_waitingtypes`.`id` as id  FROM `glpi_plugin_moreticket_waitingtypes` ORDER BY id";
         $result = $DB->query($query);
         while ($type = $DB->fetch_assoc($result)) {
            $tabs[$type['id']] = $type['name'];
         }
      }
      return $tabs;
   }

   /**
    * @param $table
    * @param $params
    *
    * @return string
    */
   private static function getSpecificEntityRestrict($table, $params) {

      if (isset($params['entities_id']) && $params['entities_id'] == "") {
         $params['entities_id'] = $_SESSION['glpiactive_entity'];
      }
      if (isset($params['entities_id']) && ($params['entities_id'] != -1)) {
         if (isset($params['sons']) && ($params['sons'] != "") && ($params['sons'] != 0)) {
            $entities = " AND `$table`.`entities_id` IN  (" . implode(",", getSonsOf("glpi_entities", $params['entities_id'])) . ") ";
         } else {
            $entities = " AND `$table`.`entities_id` = " . $params['entities_id'] . " ";
         }
      } else {
         if (isset($params['sons']) && ($params['sons'] != "") && ($params['sons'] != 0)) {
            $entities = " AND `$table`.`entities_id` IN  (" . implode(",", getSonsOf("glpi_entities", $_SESSION['glpiactive_entity'])) . ") ";
         } else {
            $entities = " AND `$table`.`entities_id` = " . $_SESSION['glpiactive_entity'] . " ";
         }
      }
      return $entities;
   }

   /**
    * @param $params
    *
    * @return array
    */
   private static function getTimePerTech($params) {
      global $DB;

      $time_per_tech = [];
      $months        = Toolbox::getMonthsOfYearArray();

      $opt               = $params['opt'];
      $crit              = $params['crit'];
      $type_criteria     = $crit['type'];
      $entities_criteria = $crit['entities_id'];
      $year              = $opt["year"];

      $selected_group = [];
      if (isset($opt["technicians_groups_id"])
          && count($opt["technicians_groups_id"]) > 0) {
         $selected_group = $opt['technicians_groups_id'];
      } else if (count($_SESSION['glpigroups']) > 0) {
         $selected_group = $_SESSION['glpigroups'];
      }

      $techlist = [];
      if (count($selected_group) > 0) {
         $groups             = implode(",", $selected_group);
         $query_group_member = "SELECT `glpi_groups_users`.`users_id`"
                               . "FROM `glpi_groups_users` "
                               . "LEFT JOIN `glpi_groups` ON (`glpi_groups_users`.`groups_id` = `glpi_groups`.`id`) "
                               . "WHERE `glpi_groups_users`.`groups_id` IN (" . $groups . ") AND `glpi_groups`.`is_assign` = 1 "
                               . " GROUP BY `glpi_groups_users`.`users_id`";

         $result_gu = $DB->query($query_group_member);

         while ($data = $DB->fetch_assoc($result_gu)) {
            $techlist[] = $data['users_id'];
         }
      }
      $current_month = date("m");
      foreach ($months as $key => $month) {

         if ($key > $current_month && $year == date("Y")) {
            break;
         }

         $next = $key + 1;

         $month_tmp = $key;
         $nb_jours  = date("t", mktime(0, 0, 0, $key, 1, $year));

         if (strlen($key) == 1) {
            $month_tmp = "0" . $month_tmp;
         }
         if (strlen($next) == 1) {
            $next = "0" . $next;
         }

         if ($key == 0) {
            $year      = $year - 1;
            $month_tmp = "12";
            $nb_jours  = date("t", mktime(0, 0, 0, 12, 1, $year));
         }

         $month_deb_date     = "$year-$month_tmp-01";
         $month_deb_datetime = $month_deb_date . " 00:00:00";
         $month_end_date     = "$year-$month_tmp-$nb_jours";
         $month_end_datetime = $month_end_date . " 23:59:59";
         $is_deleted         = "`glpi_tickets`.`is_deleted` = 0";

         foreach ($techlist as $techid) {
            $time_per_tech[$techid][$key] = 0;

            $querym_ai   = "SELECT  DATE(`glpi_tickettasks`.`date`), SUM(`glpi_tickettasks`.`actiontime`) AS actiontime_date
                        FROM `glpi_tickettasks` 
                        INNER JOIN `glpi_tickets` ON (`glpi_tickets`.`id` = `glpi_tickettasks`.`tickets_id` AND $is_deleted) 
                        LEFT JOIN `glpi_entities` ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`) ";
            $querym_ai   .= "WHERE ";
            $querym_ai   .= "(
                           `glpi_tickettasks`.`begin` >= '$month_deb_datetime' 
                           AND `glpi_tickettasks`.`end` <= '$month_end_datetime'
                           AND `glpi_tickettasks`.`users_id_tech` = (" . $techid . ") "
                            . $entities_criteria
                            . ") 
                        OR (
                           `glpi_tickettasks`.`date` >= '$month_deb_datetime' 
                           AND `glpi_tickettasks`.`date` <= '$month_end_datetime' 
                           AND `glpi_tickettasks`.`users_id`  = (" . $techid . ") 
                           AND `glpi_tickettasks`.`begin` IS NULL "
                            . $entities_criteria
                            . ")
                           AND `glpi_tickettasks`.`actiontime` != 0 $type_criteria ";
            $querym_ai   .= "GROUP BY DATE(`glpi_tickettasks`.`date`);
                        ";
            $result_ai_q = $DB->query($querym_ai);
            while ($data = $DB->fetch_assoc($result_ai_q)) {
               //               $time_per_tech[$techid][$key] += (self::TotalTpsPassesArrondis($data['actiontime_date'] / 3600 / 8));
               $time_per_tech[$techid][$key] += round(($data['actiontime_date'] / 3600 / 8), 2);
            }
         }

         if ($key == 0) {
            $year++;
         }
      }
      return $time_per_tech;
   }


   /**
    * @param $params
    *
    * @return array
    */
   private static function getTicketsPerTech($params) {
      global $DB;

      $tickets_per_tech = [];
      $months           = Toolbox::getMonthsOfYearArray();

      $mois = intval(strftime("%m") - 1);
      $year = intval(strftime("%Y") - 1);

      if ($mois > 0) {
         $year = date("Y");
      }

      if (isset($params["year"])
          && $params["year"] > 0) {
         $year = $params["year"];
      }

      $type_criteria = "AND 1 = 1";
      if (isset($params["type"])
          && $params["type"] > 0) {
         $type_criteria = " AND `glpi_tickets`.`type` = '" . $params["type"] . "' ";
      }

      $selected_group = [];
      if (isset($params["technicians_groups_id"])
          && count($params["technicians_groups_id"]) > 0) {
         $selected_group = $params['technicians_groups_id'];
      } else if (count($_SESSION['glpigroups']) > 0) {
         $selected_group = $_SESSION['glpigroups'];
      }

      $techlist = [];
      if (count($selected_group) > 0) {
         $groups             = implode(",", $selected_group);
         $query_group_member = "SELECT `glpi_groups_users`.`users_id`"
                               . "FROM `glpi_groups_users` "
                               . "LEFT JOIN `glpi_groups` ON (`glpi_groups_users`.`groups_id` = `glpi_groups`.`id`) "
                               . "WHERE `glpi_groups_users`.`groups_id` IN (" . $groups . ") AND `glpi_groups`.`is_assign` = 1 "
                               . " GROUP BY `glpi_groups_users`.`users_id`";

         $result_gu = $DB->query($query_group_member);

         while ($data = $DB->fetch_assoc($result_gu)) {
            $techlist[] = $data['users_id'];
         }
      }
      //      else {
      //         $query = "SELECT `glpi_tickets_users`.`users_id`"
      //                  . "FROM `glpi_tickets_users` "
      //                  . "WHERE  `glpi_tickets_users`.`type` = ".CommonITILActor::ASSIGN."
      //         GROUP BY `glpi_tickets_users`.`users_id`";
      //
      //         $result_gu = $DB->query($query);
      //
      //         while ($data = $DB->fetch_assoc($result_gu)) {
      //            $techlist[] = $data['users_id'];
      //         }
      //      }
      $current_month = date("m");
      foreach ($months as $key => $month) {

         if ($key > $current_month && $year == date("Y")) {
            break;
         }

         $next = $key + 1;

         $month_tmp = $key;
         $nb_jours  = date("t", mktime(0, 0, 0, $key, 1, $year));

         if (strlen($key) == 1) {
            $month_tmp = "0" . $month_tmp;
         }
         if (strlen($next) == 1) {
            $next = "0" . $next;
         }

         if ($key == 0) {
            $year      = $year - 1;
            $month_tmp = "12";
            $nb_jours  = date("t", mktime(0, 0, 0, 12, 1, $year));
         }

         $month_deb_date     = "$year-$month_tmp-01";
         $month_deb_datetime = $month_deb_date . " 00:00:00";
         $month_end_date     = "$year-$month_tmp-$nb_jours";
         $month_end_datetime = $month_end_date . " 23:59:59";
         $is_deleted         = "`glpi_tickets`.`is_deleted` = 0";

         foreach ($techlist as $techid) {
            $tickets_per_tech[$techid][$key] = 0;

            $querym_ai   = "SELECT COUNT(`glpi_tickets`.`id`) AS nbtickets
                        FROM `glpi_tickets` 
                        INNER JOIN `glpi_tickets_users` 
                        ON (`glpi_tickets`.`id` = `glpi_tickets_users`.`tickets_id` AND `glpi_tickets_users`.`type` = 2 AND $is_deleted) 
                        LEFT JOIN `glpi_entities` ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`) ";
            $querym_ai   .= "WHERE ";
            $querym_ai   .= "(
                           `glpi_tickets`.`date` >= '$month_deb_datetime' 
                           AND `glpi_tickets`.`date` <= '$month_end_datetime'
                           AND `glpi_tickets_users`.`users_id` = (" . $techid . ") "
                            . self::getSpecificEntityRestrict("glpi_tickets", $params)
                            . " $type_criteria ) ";
            $querym_ai   .= "GROUP BY DATE(`glpi_tickets`.`date`);
                        ";
            $result_ai_q = $DB->query($querym_ai);
            while ($data = $DB->fetch_assoc($result_ai_q)) {
               $tickets_per_tech[$techid][$key] += $data['nbtickets'];
            }
         }

         if ($key == 0) {
            $year++;
         }
      }
      return $tickets_per_tech;
   }

   /**
    * @param $a_arrondir
    *
    * @return float|int
    */
   static function TotalTpsPassesArrondis($a_arrondir) {

      $tranches_seuil   = 0.002;
      $tranches_arrondi = [0, 0.25, 0.5, 0.75, 1];

      $partie_entiere = floor($a_arrondir);
      $reste          = $a_arrondir - $partie_entiere + 10; // Le + 10 permet de pallier é un probléme de comparaison (??) par la suite.
      /* Initialisation des tranches majorées du seuil supplémentaire. */
      $tranches_majorees = [];
      for ($i = 0; $i < count($tranches_arrondi); $i++) {
         // Le + 10 qui suit permet de pallier é un probléme de comparaison (??) par la suite.
         $tranches_majorees[] = $tranches_arrondi[$i] + $tranches_seuil + 10;
      }
      if ($reste < $tranches_majorees[0]) {
         $result = $partie_entiere;

      } else if ($reste >= $tranches_majorees[0] && $reste < $tranches_majorees[1]) {
         $result = $partie_entiere + $tranches_arrondi[1];

      } else if ($reste >= $tranches_majorees[1] && $reste < $tranches_majorees[2]) {
         $result = $partie_entiere + $tranches_arrondi[2];

      } else if ($reste >= $tranches_majorees[2] && $reste < $tranches_majorees[3]) {
         $result = $partie_entiere + $tranches_arrondi[3];

      } else {
         $result = $partie_entiere + $tranches_arrondi[4];
      }

      return $result;
   }
}
