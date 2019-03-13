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
   private $widgets;

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

      $icons = PluginMydashboardHelper::icons();

      $isDebug = $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE;

      return [
          __('Public') => [
              $this->getType() . "3"  => (($isDebug)?"3 ":"").$icons["table"]. "&nbsp;" . __("Internal annuary", "mydashboard"),
              $this->getType() . "5"  => (($isDebug)?"5 ":"").$icons["table"]. "&nbsp;" . __("Fields unicity"),
              $this->getType() . "14" => (($isDebug)?"14 ":"").$icons["table"]. "&nbsp;" . __("All unpublished articles"),
          ],
          __('Charts', "mydashboard") => [
              $this->getType() . "1"  => (($isDebug)?"1 ":"").$icons["bar"] . "&nbsp;" . __("Opened tickets backlog", "mydashboard"),
              $this->getType() . "2"  => (($isDebug)?"2 ":"").$icons["pie"] . "&nbsp;" . __("Number of opened tickets by priority", "mydashboard"),
              $this->getType() . "6"  => (($isDebug)?"6 ":"").$icons["line"] . "&nbsp;" . __("Tickets stock by month", "mydashboard"),
              $this->getType() . "7"  => (($isDebug)?"7 ":"").$icons["pie"] . "&nbsp;" . __("Top ten ticket requesters by month", "mydashboard"),
              $this->getType() . "8"  => (($isDebug)?"8 ":"").$icons["bar"] . "&nbsp;" . __("Process time by technicians by month", "mydashboard"),
              $this->getType() . "12" => (($isDebug)?"12 ":"").$icons["pie"] . "&nbsp;" . __("TTR Compliance", "mydashboard"),
              $this->getType() . "13" => (($isDebug)?"13 ":"").$icons["pie"] . "&nbsp;" . __("TTO Compliance", "mydashboard"),
              $this->getType() . "15" => (($isDebug)?"15 ":"").$icons["pie"] . "&nbsp;" . __("Top ten ticket categories by type of ticket", "mydashboard"),
              $this->getType() . "16" => (($isDebug)?"16 ":"").$icons["pie"] . "&nbsp;" . __("Number of opened incidents by category", "mydashboard"),
              $this->getType() . "17" => (($isDebug)?"17 ":"").$icons["pie"] . "&nbsp;" . __("Number of opened requests by category", "mydashboard"),
              $this->getType() . "18" => (($isDebug)?"18 ":"").$icons["pie"] . "&nbsp;" . __("Number of opened and closed tickets by month", "mydashboard"),
              $this->getType() . "20" => (($isDebug)?"20 ":"").$icons["pie"] . "&nbsp;" . __("Percent of use of solution types", "mydashboard"),
              $this->getType() . "21" => (($isDebug)?"21 ":"").$icons["bar"] . "&nbsp;" . __("Number of tickets affected by technicians by month", "mydashboard"),
              $this->getType() . "22" => (($isDebug)?"22 ":"").$icons["line"] . "&nbsp;" . __("Number of opened and solved tickets by month", "mydashboard"),
              $this->getType() . "23" => (($isDebug)?"23 ":"").$icons["bar"] . "&nbsp;" . __("Average real duration of treatment of the ticket", "mydashboard"),
              $this->getType() . "24" => (($isDebug)?"24 ":"").$icons["bar"] . "&nbsp;" . __("Top ten technicians (by tickets number)", "mydashboard"),
              $this->getType() . "25" => (($isDebug)?"25 ":"").$icons["pie"] . "&nbsp;" . __("Number of opened tickets by requester groups", "mydashboard"),
              $this->getType() . "26" => (($isDebug)?"26 ":"").$icons["pie"] . "&nbsp;" . __("Global satisfaction level", "mydashboard"),
              $this->getType() . "27" => (($isDebug)?"27 ":"").$icons["pie"] . "&nbsp;" . __("Top 10 of opened tickets by location", "mydashboard"),
              $this->getType() . "28" => (($isDebug)?"28 ":"").$icons["map"] . "&nbsp;" . __("Map - Opened tickets by location", "mydashboard"),
              $this->getType() . "29" => (($isDebug)?"29 ":"").$icons["map"] . "&nbsp;" . __("OpenStreetMap - Opened tickets by location", "mydashboard"),
              $this->getType() . "30" => (($isDebug)?"30 ":"").$icons["pie"] . "&nbsp;" . __("Number of use of request sources", "mydashboard"),
              $this->getType() . "31" => (($isDebug)?"31 ":"").$icons["line"] . "&nbsp;" . __("Tickets request sources evolution", "mydashboard"),
          ]
      ];

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

      $query   = "SELECT COUNT(*) as count,`glpi_tickets`.`entities_id` FROM `glpi_tickets`
                  WHERE $is_deleted AND (((`glpi_tickets`.`date` <= '$year-$month-$nbdays 23:59:59') 
                  AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . "))) GROUP BY `glpi_tickets`.`entities_id`";
      $results = $DB->query($query);
      while ($data = $DB->fetch_array($results)) {
         $query = "INSERT INTO `glpi_plugin_mydashboard_stocktickets` (`id`,`date`,`nbstocktickets`,`entities_id`) 
                  VALUES (NULL,'$year-$month-$nbdays'," . $data['count'] . "," . $data['entities_id'] . ")";
         $DB->query($query);
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
      $dbu        = new DbUtils();
      switch ($widgetId) {

         case $this->getType() . "1":
            $criterias = ['entities_id', 'is_recursive', 'groups_id', 'type'];
            $params    = ["preferences" => $this->preferences,
                          "criterias"   => $criterias,
                          "opt"         => $opt];
            $options   = PluginMydashboardHelper::manageCriterias($params);

            $opt                  = $options['opt'];
            $crit                 = $options['crit'];
            $type                 = $opt['type'];
            $type_criteria        = $crit['type'];
            $entities_criteria    = $crit['entities_id'];
            $groups_criteria      = $crit['groups_id'];
            $is_deleted           = "`glpi_tickets`.`is_deleted` = 0";
            $query                = "SELECT DISTINCT
                           DATE_FORMAT(`date`, '%b %Y') AS period_name,
                           COUNT(`glpi_tickets`.`id`) AS nb,
                           DATE_FORMAT(`date`, '%Y-%m') AS period
                        FROM `glpi_tickets` ";
            if (isset($groups_criteria) && ($groups_criteria != 0)) {
               $query .= " LEFT JOIN `glpi_groups_tickets` 
                        ON (`glpi_groups_tickets`.`tickets_id` = `glpi_tickets`.`id` 
                        AND `glpi_groups_tickets`.`type` = '" . CommonITILActor::ASSIGN . "')";
            }
            $query .= " WHERE $is_deleted $type_criteria ";
            if (isset($groups_criteria) && ($groups_criteria != 0)) {
               $query .= " AND `glpi_groups_tickets`.`groups_id` = " . $groups_criteria;
            }
            $query .= " $entities_criteria AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")
                        GROUP BY period_name ORDER BY period ASC";

            $result   = $DB->query($query);
            $nb       = $DB->numrows($result);

            $results = [
                "name" => [],
                "colors" => [],
                "datas" => [],
                "tab" => [],
            ];

            if ($nb) {
               while ($data = $DB->fetch_assoc($result)) {
                  $results['name'][] = $data['period_name'];
                  $results['datas'][] = $data['nb'];
                  $results['tab'][] = $data['period'];
               }
            }

            $results['colors'] = PluginMydashboardColor::getColors($nb);

            $events = [
                "onclick" => true,
                "hover" => true,
                "tooltip" => true,
                "animation" => true
            ];

            $params = ["widgetId"  => $widgetId,
                "name" => $widgetId . 'BarChart',
                "onsubmit"  => false,
                "opt"       => $opt,
                "criterias" => $criterias,
                "export"    => true,
                "canvas"    => true,
                "nb"        => 1];

            $infos = [
                "type" => "TICKET",
                "filter" => "DATE",
                "state" => "INPROGRESS",
                "values" => []];

            $infos['values']['dates'] = json_encode($results['tab']);
            $infos['values']['label'] = __('Tickets number', 'mydashboard');

            if(!PluginMydashboardHelper::graph(
                $graph2, 'bar', $widgetId, $results, $events, $infos, $crit)){
               Toolbox::userErrorHandlerDebug(
                   E_ERROR,
                   "You passed wrong parameters to generate graph please verify",
                   "infotel.class.php",
                   "");
            }

            $graph2  .= PluginMydashboardHelper::getGraphHeader($params);

            $widget = new PluginMydashboardHtml();
            $widget->setWidgetTitle((($isDebug)?"-1- ":"").__("Opened tickets backlog", "mydashboard"));
            $widget->setWidgetComment(__("Display of opened tickets by month", "mydashboard"));
            $widget->toggleWidgetRefresh();
            $widget->setWidgetHtmlContent($graph2);

            return $widget;

            break;

         case $this->getType() . "2":

            $criterias = ['entities_id', 'is_recursive', 'type'];
            $params    = ["preferences" => $this->preferences,
                          "criterias"   => $criterias,
                          "opt"         => $opt];
            $options   = PluginMydashboardHelper::manageCriterias($params);

            $opt           = $options['opt'];
            $crit          = $options['crit'];
            $type_criteria = $crit['type'];
            $entities_criteria    = $crit['entities_id'];
            $is_deleted           = "`glpi_tickets`.`is_deleted` = 0";
            $query = "SELECT DISTINCT
                           `priority`,
                           COUNT(`id`) AS nb
                        FROM `glpi_tickets`
                        WHERE $is_deleted $type_criteria $entities_criteria";
            $query .= " AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ") ";
            $query .= " GROUP BY `priority` ORDER BY `priority` ASC";

            $result = $DB->query($query);
            $nb     = $DB->numrows($result);

            $results = [
                "name" => [],
                "colors" => [],
                "datas" => [],
                "tab" => [],
            ];

            $events = [
                "onclick" => true,
                "hover" => true,
                "tooltip" => false
            ];

            if ($nb) {
               while ($data = $DB->fetch_array($result)) {
                  $results['name'][]        = CommonITILObject::getPriorityName($data['priority']);
                  $results['colors'][]      = $_SESSION["glpipriority_" . $data['priority']];
                  $results['datas'][]       = $data['nb'];
                  $results['tab'][] = $data['priority'];
               }
            }

            $params = ["widgetId"  => $widgetId,
                "name"      => $widgetId.'PieChart',
                "onsubmit"  => false,
                "opt"       => $opt,
                "criterias" => $criterias,
                "export"    => true,
                "canvas"    => true,
                "nb"        => 1];

            $infos = [
                "type" => "TICKET",
                "filter" => "PRIORITY",
                "state" => "INPROGRESS",
                "values" => null
            ];
            $infos['values']['priority_id'] = json_encode($results['tab']);

            if(!PluginMydashboardHelper::graph(
                $graph2, 'pie', $widgetId, $results, $events, $infos, $crit)){
               Toolbox::userErrorHandlerDebug(
                   E_ERROR,
                   "You passed wrong parameters to generate graph please verify",
                   "infotel.class.php",
                   "");
            }

            $graph2  .= PluginMydashboardHelper::getGraphHeader($params);

            $widget = new PluginMydashboardHtml();
            $widget->setWidgetTitle((($isDebug)?"-2- ":"").__("Number of opened tickets by priority", "mydashboard"));
            $widget->setWidgetHtmlContent($graph2);

            return $widget;
            break;

         case $this->getType() . "3":
            $profile_user = new Profile_User();
            $users        = $profile_user->find($dbu->getEntitiesRestrictRequest("", "glpi_profiles_users", "entities_id", '', true));
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
            //            $hidden  = array(__('Login'));
            $widget->setTabNames($headers);
            //            $widget->setTabNamesHidden($hidden);
            $hidden[] = ["targets" => 2, "visible" => false];
            $widget->setOption("bDef", $hidden);
            $widget->toggleWidgetRefresh();
            $widget->setWidgetTitle((($isDebug)?"-3- ":"").__("Internal annuary", "mydashboard"));
            $widget->setWidgetComment(__("Search users of your organisation", "mydashboard"));

            return $widget;
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
            $widget->setWidgetTitle((($isDebug)?"-5- ":"").__('Fields unicity'));
            $widget->setWidgetComment(__("Display if you have duplicates into inventory", "mydashboard"));
            return $widget;
            break;

         case $this->getType() . "6":

            $criterias = ['entities_id', 'is_recursive'];
            $params    = ["preferences" => $this->preferences,
                          "criterias"   => $criterias,
                          "opt"         => $opt];
            $options   = PluginMydashboardHelper::manageCriterias($params);

            $opt  = $options['opt'];
            $crit = $options['crit'];

            $entities_criteria = $crit['entities_id'];
            $mdentities        = self::getSpecificEntityRestrict("glpi_plugin_mydashboard_stocktickets", $opt);

            $currentmonth = date("m");
            $currentyear  = date("Y");
            $previousyear = $currentyear - 1;
            $query_2      = "SELECT DATE_FORMAT(`glpi_plugin_mydashboard_stocktickets`.`date`, '%Y-%m') as month,
                                    DATE_FORMAT(`glpi_plugin_mydashboard_stocktickets`.`date`, '%b %Y') as monthname,
                                    SUM(nbStockTickets) as nbStockTickets
                                    FROM `glpi_plugin_mydashboard_stocktickets`
                                    WHERE  (`glpi_plugin_mydashboard_stocktickets`.`date` >= '$previousyear-$currentmonth-01 00:00:00')
                                    AND (`glpi_plugin_mydashboard_stocktickets`.`date` <= '$currentyear-$currentmonth-01 00:00:00')
                                    " . $mdentities . "
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

               //               if ($data_1['count'] > $maxcount) {
               //                  $maxcount = $data_1['count'];
               //               }
               $tabnames[] = $data['monthname'];
               $i++;
            }

            $widget = new PluginMydashboardHtml();
            $title  = __("Tickets stock", "mydashboard");
            $widget->setWidgetComment(__("Sum of not solved tickets by month", "mydashboard"));
            $widget->setWidgetTitle((($isDebug)?"-6- ":"").$title);
            $widget->toggleWidgetRefresh();

            $dataLineset = json_encode($tabdata);
            $labelsLine  = json_encode($tabnames);

            $month     = _n('month', 'months', 2);
            $nbtickets = __('Tickets number', 'mydashboard');

            $graph = "<script type='text/javascript'>
      

            var dataStockLine = {
                    datasets: [{
                      data: $dataLineset,
                      label: '$title',
                      borderColor: '#1f77b4',
            //          backgroundColor: '#FFF',
                            fill: false,
                            lineTension: '0.1',
                    }],
                  labels:
                  $labelsLine
                  };
            
//            $(document).ready(
//               function () {
                 var isChartRendered = false;
                  var canvas = document . getElementById('TicketStockLineChart');
                   var ctx = canvas . getContext('2d');
                   ctx.canvas.width = 700;
                   ctx.canvas.height = 400;
                   var TicketStockLineChart = new Chart(ctx, {
                  type:
                  'line',
                     data: dataStockLine,
                     options: {
                     responsive: true,
                     maintainAspectRatio: true,
                      title:{
                          display: false,
                          text:'Line Chart'
                      },
                      tooltips: {
                     mode:
                     'index',
                          intersect: false,
                      },
                      hover: {
                     mode:
                     'nearest',
                          intersect: true
                      },
                      scales: {
                     xAxes:
                     [{
                        display:
                        true,
                              scaleLabel: {
                           display:
                           true,
                                  labelString: '$month'
                              }
                          }],
                          yAxes: [{
                        display:
                        true,
                              scaleLabel: {
                           display:
                           true,
                                  labelString: '$nbtickets'
                              }
                          }]
                      },
                       animation: {
                        onComplete: function() {
                          isChartRendered = true
                        }
                      }
                   }
                   });

             </script>";

            $params = ["widgetId"  => $widgetId,
                       "name"      => 'TicketStockLineChart',
                       "onsubmit"  => false,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => 1];
            $graph  .= PluginMydashboardHelper::getGraphHeader($params);

            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;

            break;
         case $this->getType() . "7":

            $criterias = ['entities_id', 'is_recursive', 'type', 'year', 'month'];
            $params    = ["preferences" => $this->preferences,
                          "criterias"   => $criterias,
                          "opt"         => $opt];
            $options   = PluginMydashboardHelper::manageCriterias($params);

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
                     $name = __('Email');
                  } else if ($k == -1) {
                     $name = __('None');
                  } else if ($k > 0) {
                     $name = getUserName($k);
                  }
                  $dataspie[] = $v;
                  $namespie[] = $name;
                  unset($datas[$k]);
               }
            }

            $widget = new PluginMydashboardHtml();
            $title  = __("Top ten ticket requesters by month", "mydashboard");
            $widget->setWidgetTitle((($isDebug)?"-7- ":"").$title);

            $palette = PluginMydashboardColor::getColors($nb);

            $dataPieset         = json_encode($dataspie);
            $backgroundPieColor = json_encode($palette);
            $labelsPie          = json_encode($namespie);

            $graph = "<script type='text/javascript'>
         
            var dataTopTenPie = {
              datasets: [{
                data: $dataPieset,
                backgroundColor: $backgroundPieColor
              }],
              labels: $labelsPie
            };
            
             var isChartRendered = false;
             var canvas = document.getElementById('TopTenTicketAuthorsPieChart');
             var ctx = canvas.getContext('2d');
             ctx.canvas.width = 700;
             ctx.canvas.height = 400;
             var TopTenTicketAuthorsPieChart = new Chart(ctx, {
               type: 'polarArea',
               data: dataTopTenPie,
               options: {
                 responsive: true,
                 maintainAspectRatio: true,
                 animation: {
                  onComplete: function() {
                    isChartRendered = true
                  }
                }
             }
             });

             </script>";

            $params = ["widgetId"  => $widgetId,
                       "name"      => 'TopTenTicketAuthorsPieChart',
                       "onsubmit"  => false,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => $nb];
            $graph  .= PluginMydashboardHelper::getGraphHeader($params);

            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;

         case $this->getType() . "8":

            $criterias = ['entities_id', 'is_recursive', 'groups_id', 'type', 'year'];
            $params    = ["preferences" => $this->preferences,
                          "criterias"   => $criterias,
                          "opt"         => $opt];
            $options   = PluginMydashboardHelper::manageCriterias($params);
            $opt       = $options['opt'];

            $time_per_tech = self::getTimePerTech($options);

            $months_t = Toolbox::getMonthsOfYearArray();
            // Reindex array to start with 0
            $months = array_splice($months_t, 0, count($months_t));

            $results = [
                "name" => [],
                "colors" => [],
                "datas" => [],
                "tab" => [],
            ];

            foreach ($time_per_tech as $tech_id => $times) {
               unset($time_per_tech[$tech_id]);
               $username = getUserName($tech_id);
               $results['name'][] = $username;
               $results['datas'][] = array_values($times);
               $infos['values']['label'][] = $username;
            }

            $results['colors'] = PluginMydashboardColor::getColors(count($results['name']));

            $events = [
                "onclick" => false,
                "hover" => false,
                "tooltip" => true,
                "animation" => false
            ];

            $infos = [
                "type" => "TECHNICIAN",
                "filter" => "MONTH",
                "state" => null,
                "values" => []];

            $infos['values']['label'] = json_encode($months);

            if(!PluginMydashboardHelper::graph(
                $graph2, 'bar', $widgetId, $results, $events, $infos, $options['crit'])){
               Toolbox::userErrorHandlerDebug(
                   E_ERROR,
                   "You passed wrong parameters to generate graph please verify",
                   "infotel.class.php",
                   "");
            }

            $params2 = ["widgetId"  => $widgetId,
                "name" => $widgetId . 'BarChart',
                "onsubmit"  => false,
                "opt"       => $opt,
                "criterias" => $criterias,
                "export"    => true,
                "canvas"    => true,
                "nb"        => count($results['name'])];

            $graph2  .= PluginMydashboardHelper::getGraphHeader($params2);

            $widget = new PluginMydashboardHtml();
            $widget->setWidgetTitle((($isDebug)?"-8- ":"")
                .__("Process time by technicians by month", "mydashboard"));
            $widget->setWidgetComment(__("Sum of ticket tasks duration by technicians", "mydashboard"));
            $widget->setWidgetHtmlContent($graph2);

            return $widget;

            break;

         case $this->getType() . "12":

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

            $results = [
                "name" => [],
                "colors" => [],
                "datas" => [],
                "tab" => [],
            ];

            $results['datas'] = [$respected, $notrespected];
            $results['colors'] = PluginMydashboardColor::getColors(2);
            $results['name'] = [
                __("Respected TTR", "mydashboard"),
                __("Not respected TTR", "mydashboard")];

            $events = [
                "onclick" => false,
                "hover" => false,
                "tooltip" => true
            ];

            $infos = [
                "type" => "TICKET",
                "filter" => null,
                "state" => null,
                "values" => []];

            $params = ["widgetId" => $widgetId,
                "name" => $widgetId.'PieChart',
                "onsubmit" => false,
                "opt" => $opt,
                "criterias" => $criterias,
                "export" => true,
                "canvas" => true,
                "nb" => $nb];

            if(!PluginMydashboardHelper::graph(
                $graph2, 'pie', $widgetId, $results, $events, $infos, $crit)){
               Toolbox::userErrorHandlerDebug(
                   E_ERROR,
                   "You passed wrong parameters to generate graph please verify",
                   "infotel.class.php",
                   "");
            }
            $graph2  .= PluginMydashboardHelper::getGraphHeader($params);

            $widget = new PluginMydashboardHtml();
            $widget->setWidgetTitle((($isDebug)?"-12- ":"").__("TTR Compliance", "mydashboard"));
            $widget->setWidgetComment(__("Display tickets where time to resolve is respected", "mydashboard"));
            $widget->setWidgetHtmlContent($graph2);

            return $widget;

            break;
         case $this->getType() . "13":

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

            $results = [
                "name" => [],
                "colors" => [],
                "datas" => [],
                "tab" => [],
            ];

            $results['datas'] = [$respected, $notrespected];
            $results['colors'] = PluginMydashboardColor::getColors(2);
            $results['name'] = [
                __("Respected TTO", "mydashboard"),
                __("Not respected TTO", "mydashboard")];

            $events = [
                "onclick" => false,
                "hover" => false,
                "tooltip" => true
            ];

            $infos = [
                "type" => "TICKET",
                "filter" => null,
                "state" => null,
                "values" => []];

            if(!PluginMydashboardHelper::graph(
                $graph2, 'pie', $widgetId, $results, $events, $infos, $crit)){
               Toolbox::userErrorHandlerDebug(
                   E_ERROR,
                   "You passed wrong parameters to generate graph please verify",
                   "infotel.class.php",
                   "");
            }

            $params = ["widgetId" => $widgetId,
                "name" => $widgetId . 'PieChart',
                "onsubmit" => false,
                "opt" => $opt,
                "criterias" => $criterias,
                "export" => true,
                "canvas" => true,
                "nb" => $nb];

            $graph2  .= PluginMydashboardHelper::getGraphHeader($params);

            $widget = new PluginMydashboardHtml();
            $widget->setWidgetTitle((($isDebug)?"-13- ":"").__("TTO Compliance", "mydashboard"));
            $widget->setWidgetComment(__("Display tickets where time to own is respected", "mydashboard"));
            $widget->setWidgetHtmlContent($graph2);

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
            $widget->setWidgetTitle((($isDebug)?"-14- ":"").__('All unpublished articles'));
            return $widget;

            break;
         case $this->getType() . "15":

            $criterias = ['entities_id', 'is_recursive', 'type', 'year'];
            $params    = ["preferences" => $this->preferences,
                          "criterias"   => $criterias,
                          "opt"         => $opt];
            $options   = PluginMydashboardHelper::manageCriterias($params);

            $opt  = $options['opt'];
            $crit = $options['crit'];

            $type_criteria     = $crit['type'];
            $entities_criteria = $crit['entities_id'];
            $date_criteria     = $crit['date'];
            $is_deleted        = "`glpi_tickets`.`is_deleted` = 0";

            $query    = "SELECT `glpi_itilcategories`.`completename` as itilcategories_id, COUNT(`glpi_tickets`.`id`) as count
                     FROM `glpi_tickets`
                     LEFT JOIN `glpi_itilcategories`
                        ON (`glpi_itilcategories`.`id` = `glpi_tickets`.`itilcategories_id`)
                     WHERE $date_criteria
                     $entities_criteria $type_criteria
                     AND $is_deleted
                     GROUP BY `glpi_itilcategories`.`id`
                     ORDER BY count DESC
                     LIMIT 10";
            $widget   = PluginMydashboardHelper::getWidgetsFromDBQuery('piechart', $query);
            $datas    = $widget->getTabDatas();
            $dataspie = [];
            $namespie = [];
            $nb       = count($datas);
            if ($nb > 0) {
               foreach ($datas as $k => $v) {

                  if (!empty($k)) {
                     $name = $k;
                  } else {
                     $name = __('None');
                  }
                  $dataspie[] = $v;
                  $namespie[] = $name;
                  unset($datas[$k]);
               }
            }
            //            $widget->setTabDatas($datas);
            //            $widget->appendWidgetHtmlContent($dropdown);
            //            $widget->toggleWidgetRefresh();
            $widget = new PluginMydashboardHtml();
            $title  = __("Top ten ticket categories by type of ticket", "mydashboard");
            $widget->setWidgetTitle((($isDebug)?"-15- ":"").$title);

            $dataPieset = json_encode($dataspie);

            $palette            = PluginMydashboardColor::getColors($nb);
            $backgroundPieColor = json_encode($palette);
            $labelsPie          = json_encode($namespie);

            $graph = "<script type='text/javascript'>
         
            var dataTopTenCatPie = {
              datasets: [{
                data: $dataPieset,
                backgroundColor: $backgroundPieColor
              }],
              labels: $labelsPie
            };
            
//            $(document).ready(
//              function() {
                var isChartRendered = false;
                var canvas = document.getElementById('TopTenTicketCategoriesPieChart');
                var ctx = canvas.getContext('2d');
                ctx.canvas.width = 700;
                ctx.canvas.height = 400;
                var TopTenTicketCategoriesPieChart = new Chart(ctx, {
                  type: 'polarArea',
                  data: dataTopTenCatPie,
                  options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    animation: {
                     onComplete: function() {
                       isChartRendered = true
                     }
                   }
                }
                });
            
      //          canvas.onclick = function(evt) {
      //            var activePoints = TopTenTicketCategoriesPieChart.getElementsAtEvent(evt);
      //            if (activePoints[0]) {
      //              var chartData = activePoints[0]['_chart'].config.data;
      //              var idx = activePoints[0]['_index'];
      //      
      //              var label = chartData.labels[idx];
      //              var value = chartData.datasets[0].data[idx];
      //      
      //              var url = \"http://example.com/?label=\" + label + \"&value=\" + value;
      //              console.log(url);
      //              alert(url);
      //            }
      //          };
//              }
//            );
                
             </script>";

            $params = ["widgetId"  => $widgetId,
                       "name"      => 'TopTenTicketCategoriesPieChart',
                       "onsubmit"  => false,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => $nb];
            $graph  .= PluginMydashboardHelper::getGraphHeader($params);
            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;
         case $this->getType() . "16":

            $criterias = ['entities_id', 'is_recursive', 'groups_id'];
            $params    = ["preferences" => $this->preferences,
                          "criterias"   => $criterias,
                          "opt"         => $opt];
            $options   = PluginMydashboardHelper::manageCriterias($params);

            $opt  = $options['opt'];
            $crit = $options['crit'];

            $entities_criteria    = $crit['entities_id'];
            $groups_criteria      = $crit['groups_id'];
            $is_deleted           = "`glpi_tickets`.`is_deleted` = 0";

            $query = "SELECT DISTINCT
                           `glpi_itilcategories`.`name` AS name,
                           `glpi_itilcategories`.`id` AS itilcategories_id,
                           COUNT(`glpi_tickets`.`id`) AS nb
                        FROM `glpi_tickets` ";
            if (isset($opt['groups_id']) && ($opt['groups_id'] != 0)) {
               $query .= " LEFT JOIN `glpi_groups_tickets` 
                        ON (`glpi_groups_tickets`.`tickets_id` = `glpi_tickets`.`id`
                            AND `glpi_groups_tickets`.`type` = '" . CommonITILActor::ASSIGN . "')";
            }
            $query .= "LEFT JOIN `glpi_itilcategories`
                        ON (`glpi_itilcategories`.`id` = `glpi_tickets`.`itilcategories_id`)
                        WHERE $is_deleted AND  `glpi_tickets`.`type` = '" . Ticket::INCIDENT_TYPE . "'";
            if (isset($opt['groups_id']) && ($opt['groups_id'] != 0)) {
               $query .= " AND `glpi_groups_tickets`.`groups_id` = " . $groups_criteria;
            }
            $query .= $entities_criteria
                      . " AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")
                        GROUP BY `glpi_itilcategories`.`id`";

            $result = $DB->query($query);
            $nb     = $DB->numrows($result);

            $results = [
                "name" => [],
                "colors" => [],
                "datas" => [],
                "tab" => [],
            ];

            if ($nb) {
               while ($data = $DB->fetch_array($result)) {
                  if ($data['name'] == NULL) {
                     $results['name'][] = __('None');
                  } else {
                     $results['name'][] = $data['name'];
                  }
                  $results['datas'][] = $data['nb'];
                  $results['tab'][] = $data['itilcategories_id'];
               }
            }

            $results['colors'] = PluginMydashboardColor::getColors($nb);

            $events = [
                "onclick" => true,
                "hover" => true,
                "tooltip" => false
            ];

            $params = ["widgetId" => $widgetId,
                "name" => $widgetId . 'PieChart',
                "onsubmit" => false,
                "opt" => $opt,
                "criterias" => $criterias,
                "export" => true,
                "canvas" => true,
                "nb" => $nb];

            $infos = [
               "type" => "INCIDENT",
               "filter" => "CATEGORY",
               "state" => "INPROGRESS",
               "values" => []];
            $infos['values']['category_id'] = json_encode($results['tab']);

            if(!PluginMydashboardHelper::graph(
                $graph2, 'pie', $widgetId, $results, $events, $infos, $crit)){
               Toolbox::userErrorHandlerDebug(
                   E_ERROR,
                   "You passed wrong parameters to generate graph please verify",
                   "infotel.class.php",
                   "");
            }

            $graph2  .= PluginMydashboardHelper::getGraphHeader($params);

            $widget = new PluginMydashboardHtml();
            $widget->setWidgetTitle((($isDebug)?"-16- ":""). __("Number of opened incidents by category", "mydashboard"));
            $widget->setWidgetHtmlContent($graph2);

            return $widget;
            break;
         case $this->getType() . "17":

            $criterias = ['entities_id', 'is_recursive', 'groups_id'];
            $params    = ["preferences" => $this->preferences,
                          "criterias"   => $criterias,
                          "opt"         => $opt];
            $options   = PluginMydashboardHelper::manageCriterias($params);

            $opt  = $options['opt'];
            $crit = $options['crit'];

            $entities_criteria    = $crit['entities_id'];
            $groups_criteria      = $crit['groups_id'];
            $is_deleted           = "`glpi_tickets`.`is_deleted` = 0";

            $query = "SELECT DISTINCT
                           `glpi_itilcategories`.`name` AS name,
                           `glpi_itilcategories`.`id` AS itilcategories_id,
                           COUNT(`glpi_tickets`.`id`) AS nb
                        FROM `glpi_tickets` ";
            if (isset($opt['groups_id']) && ($opt['groups_id'] != 0)) {
               $query .= " LEFT JOIN `glpi_groups_tickets` 
                        ON (`glpi_groups_tickets`.`tickets_id` = `glpi_tickets`.`id`
                            AND `glpi_groups_tickets`.`type` = '" . CommonITILActor::ASSIGN . "') ";
            }
            $query .= " LEFT JOIN `glpi_itilcategories`
                        ON (`glpi_itilcategories`.`id` = `glpi_tickets`.`itilcategories_id`)
                        WHERE $is_deleted AND  `glpi_tickets`.`type` = '" . Ticket::DEMAND_TYPE . "'";
            if (isset($opt['groups_id']) && ($opt['groups_id'] != 0)) {
               $query .= " AND `glpi_groups_tickets`.`groups_id` = " . $groups_criteria;
            }
            $query .= $entities_criteria
                      . " AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")
                        GROUP BY `glpi_itilcategories`.`id`";

            $result = $DB->query($query);
            $nb     = $DB->numrows($result);

            $results = [
                "name" => [],
                "colors" => [],
                "datas" => [],
                "tab" => [],
            ];

            if ($nb) {
               while ($data = $DB->fetch_array($result)) {
                  if ($data['name'] == NULL) {
                     $results['name'][] = __('None');
                  } else {
                     $results['name'][] = $data['name'];
                  }
                  $results['datas'][] = $data['nb'];
                  $results['tab'][] = $data['itilcategories_id'];
               }
            }

            $results['colors'] = PluginMydashboardColor::getColors($nb);

            $events = [
                "onclick" => true,
                "hover" => true,
                "tooltip" => false
            ];

            $params = ["widgetId" => $widgetId,
               //"name"      => 'RequestsByCategoryPieChart',
                "name" => $widgetId . 'PieChart',
                "onsubmit" => false,
                "opt" => $opt,
                "criterias" => $criterias,
                "export" => true,
                "canvas" => true,
                "nb" => $nb];

            $infos = [
                "type" => "DEMANDE",
                "filter" => "CATEGORY",
                "state" => "INPROGRESS",
                "values" => []];
            $infos['values']['category_id'] = json_encode($results['tab']);

            if(!PluginMydashboardHelper::graph(
                $graph2, 'pie', $widgetId, $results, $events, $infos, $crit)){
               Toolbox::userErrorHandlerDebug(
                   E_ERROR,
                   "You passed wrong parameters to generate graph please verify",
                   "infotel.class.php",
                   "");
            }

            $graph2     .= PluginMydashboardHelper::getGraphHeader($params);

            $widget = new PluginMydashboardHtml();
            $widget->setWidgetTitle((($isDebug)?"-17- ":"").
                __("Number of opened requests by category", "mydashboard"));
            $widget->setWidgetHtmlContent($graph2);

            return $widget;
            break;

         case $this->getType() . "18":

            $criterias = ['entities_id', 'is_recursive', 'type', 'year', 'month'];
            $params    = ["preferences" => $this->preferences,
                          "criterias"   => $criterias,
                          "opt"         => $opt];
            $options   = PluginMydashboardHelper::manageCriterias($params);

            $opt  = $options['opt'];
            $crit = $options['crit'];

            $type_criteria      = $crit['type'];
            $entities_criteria  = $crit['entities_id'];
            $date_criteria      = $crit['date'];
            $closedate_criteria = $crit['closedate'];
            $is_deleted         = "`glpi_tickets`.`is_deleted` = 0";

            $query = "SELECT COUNT(`glpi_tickets`.`id`)  AS nb
                     FROM `glpi_tickets`
                     WHERE $date_criteria
                     $entities_criteria $type_criteria
                     AND $is_deleted";

            $result   = $DB->query($query);
            $nb       = $DB->numrows($result);

            $results = [
                "name" => [],
                "colors" => [],
                "datas" => [],
                "tab" => [],
            ];

            if ($nb) {
               while ($data = $DB->fetch_assoc($result)) {
                  $results['datas'][] = $data['nb'];
                  $results['name'][] = __("Opened tickets", "mydashboard");
               }
            }

            $query = "SELECT COUNT(`glpi_tickets`.`id`)  AS nb
                     FROM `glpi_tickets`
                     WHERE $closedate_criteria
                     $entities_criteria $type_criteria
                     AND $is_deleted";

            $result = $DB->query($query);
            $nb     = $DB->numrows($result);

            if ($nb) {
               while ($data = $DB->fetch_assoc($result)) {
                  $results['datas'][] = $data['nb'];
                  $results['name'][] = __("Solved tickets", "mydashboard");
               }
            }

            $results['colors'] = PluginMydashboardColor::getColors($nb);

            $events = [
                "onclick" => false,
                "hover" => false,
                "tooltip" => false
            ];

            $params = ["widgetId"  => $widgetId,
                "name" => $widgetId . 'PieChart',
                "onsubmit"  => false,
                "opt"       => $opt,
                "criterias" => $criterias,
                "export"    => true,
                "canvas"    => true,
                "nb"        => $nb];

            $infos = [
                "type" => "TICKET",
                "filter" => "MONTH",
                "state" => "INPROGRESS",
                "values" => []];

            if(!PluginMydashboardHelper::graph(
                $graph2, 'pie', $widgetId, $results, $events, $infos, $crit)){
               Toolbox::userErrorHandlerDebug(
                   E_ERROR,
                   "You passed wrong parameters to generate graph please verify",
                   "infotel.class.php",
                   "");
            }
            $graph2     .= PluginMydashboardHelper::getGraphHeader($params);

            $widget = new PluginMydashboardHtml();
            $widget->setWidgetTitle((($isDebug)?"-18- ":"")
                .__("Number of opened and solved tickets by month", "mydashboard"));
            $widget->setWidgetHtmlContent($graph2);

            return $widget;
            break;

         case $this->getType() . "19":

            break;

         case $this->getType() . "20":

            $criterias = ['entities_id', 'is_recursive', 'type'];
            $params    = ["preferences" => $this->preferences,
                          "criterias"   => $criterias,
                          "opt"         => $opt];
            $options   = PluginMydashboardHelper::manageCriterias($params);

            $opt  = $options['opt'];
            $crit = $options['crit'];

            $type_criteria     = $crit['type'];
            $entities_criteria = $crit['entities_id'];
            $is_deleted        = "`glpi_tickets`.`is_deleted` = 0";
            $is_ticket        = " AND `glpi_itilsolutions`.`itemtype` = 'Ticket'";

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

            $name        = [];
            $datas       = [];
            $tabsolution = [];

            if ($nb) {
               while ($data = $DB->fetch_array($result)) {
                  $name[] = $data['name'];
                  //                  $datas[]       = Html::formatNumber(($data['nb']*100)/$total);
                  $datas[]       = intval($data['nb']);
                  $tabsolution[] = $data['solutiontypes_id'];
               }
            }
            $widget = new PluginMydashboardHtml();
            $title  = __("Percent of use of solution types", "mydashboard");
            $widget->setWidgetComment(__("Display percent of solution types for tickets", "mydashboard"));
            $widget->setWidgetTitle((($isDebug)?"-19- ":"").$title);

            $dataPieset         = json_encode($datas);
            $palette            = PluginMydashboardColor::getColors($nb);
            $backgroundPieColor = json_encode($palette);
            $labelsPie          = json_encode($name);
            $tabsolutionset     = json_encode($tabsolution);
            $graph              = "<script type='text/javascript'>
         
            var dataSolutionTypePie = {
              datasets: [{
                data: $dataPieset,
                backgroundColor: $backgroundPieColor
              }],
              labels: $labelsPie
            };
            var solutionset = $tabsolutionset;
            $(document).ready(
              function() {
                var isChartRendered = false;
                var canvas = document.getElementById('SolutionTypePieChart');
                var ctx = canvas.getContext('2d');
                ctx.canvas.width = 700;
                ctx.canvas.height = 400;
                var SolutionTypePieChart = new Chart(ctx, {
                  type: 'doughnut',
                  data: dataSolutionTypePie,
                  options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    animation: {
                        onComplete: function() {
                          isChartRendered = true
                        }
                      },
                      tooltips: {
                        callbacks: {
                          label: function(tooltipItem, data) {
                           var dataset = data.datasets[tooltipItem.datasetIndex];
                            var total = dataset.data.reduce(function(previousValue, currentValue, currentIndex, array) {
                              return previousValue + currentValue;
                            });
                            var currentValue = dataset.data[tooltipItem.index];
                            var percentage = Math.floor(((currentValue/total) * 100)+0.5);         
                            return percentage + \"%\";
                          }
                        }
                      }
                   }
                });
              }
            );
                
             </script>";

            $params = ["widgetId"  => $widgetId,
                       "name"      => 'SolutionTypePieChart',
                       "onsubmit"  => false,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => $nb];
            $graph  .= PluginMydashboardHelper::getGraphHeader($params);

            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;
         case $this->getType() . "21":

            $criterias = ['entities_id', 'is_recursive', 'groups_id', 'year', 'type'];

            if (isset($this->preferences['prefered_entity'])
                && $this->preferences['prefered_entity'] > 0
                && count($opt) < 1) {
               $opt['entities_id'] = $this->preferences['prefered_entity'];
            }
            if (!isset($opt['sons'])) {
               $opt['sons'] = $_SESSION['glpiactive_entity_recursive'];
            }
            $opt['groups_id'] = PluginMydashboardHelper::getGroup($this->preferences['prefered_group'],$opt);

            $tickets_per_tech = self::getTicketsPerTech($opt);

            $months_t = Toolbox::getMonthsOfYearArray();
            // Reindex array to start with 0
            $months = array_splice($months_t, 0, count($months_t));

            $results = [
                "name" => [],
                "colors" => [],
                "datas" => [],
                "tab" => [],
            ];

            foreach ($tickets_per_tech as $tech_id => $tickets) {
               unset($tickets_per_tech[$tech_id]);
               $username = getUserName($tech_id);

               $results['name'][] = $username;
               $results['datas'][] = array_values($tickets);
               $infos['values']['label'][] = $username;
            }

            $results['colors'] = PluginMydashboardColor::getColors(count($results['name']));

            $events = [
                "onclick" => false,
                "hover" => false,
                "tooltip" => true,
                "animation" => false
            ];

            $infos = [
                "type" => "TICKET",
                "filter" => "TECHNICIAN",
                "state" => null,
                "values" => []];

            $infos['values']['label'] = json_encode($months);

            if(!PluginMydashboardHelper::graph(
                $graph2, 'bar', $widgetId, $results, $events, $infos, [])){
               Toolbox::userErrorHandlerDebug(
                   E_ERROR,
                   "You passed wrong parameters to generate graph please verify",
                   "infotel.class.php",
                   "");
            }

            $params2    = ["widgetId"  => $widgetId,
                "name"      => $widgetId . 'BarChart',
                "onsubmit"  => false,
                "opt"       => $opt,
                "criterias" => $criterias,
                "export"    => true,
                "canvas"    => true,
                "nb"        => count($results['name'])];

            $graph2  .= PluginMydashboardHelper::getGraphHeader($params2);

            $widget = new PluginMydashboardHtml();
            $widget->setWidgetTitle((($isDebug)?"-21- ":"")
                .__("Number of tickets affected by technicians by month", "mydashboard"));
            $widget->setWidgetComment(__("Sum of ticket affected by technicians", "mydashboard"));
            $widget->setWidgetHtmlContent($graph2);

            return $widget;

            break;

         case $this->getType() . "22":

            $criterias = ['entities_id', 'is_recursive', 'year'];
            $params    = ["preferences" => $this->preferences,
                          "criterias"   => $criterias,
                          "opt"         => $opt];
            $options   = PluginMydashboardHelper::manageCriterias($params);

            $opt  = $options['opt'];
            $crit = $options['crit'];

            $entities_criteria = $crit['entities_id'];
            $mdentities        = self::getSpecificEntityRestrict("glpi_plugin_mydashboard_stocktickets", $opt);

            $currentyear = date("Y");

            if (isset($opt["year"])
                && $opt["year"] > 0) {
               $currentyear = $opt["year"];
            }
            $currentmonth = date("m");

            $previousyear = $currentyear - 1;
            $tabopened    = [];
            $tabsolved    = [];
            $tabprogress  = [];
            $tabnames     = [];

            $query_2 = "SELECT DATE_FORMAT(`glpi_plugin_mydashboard_stocktickets`.`date`, '%Y-%m') as month,
                                    DATE_FORMAT(`glpi_plugin_mydashboard_stocktickets`.`date`, '%b %Y') as monthname,
                                    SUM(nbStockTickets) as nbStockTickets
                                    FROM `glpi_plugin_mydashboard_stocktickets`
                                    WHERE  (`glpi_plugin_mydashboard_stocktickets`.`date` >= '$previousyear-$currentmonth-01 00:00:00')
                                    AND (`glpi_plugin_mydashboard_stocktickets`.`date` <= '$currentyear-$currentmonth-01 00:00:00')
                                    " . $mdentities . "
                                    GROUP BY DATE_FORMAT(`glpi_plugin_mydashboard_stocktickets`.`date`, '%Y-%m')";

            $results2 = $DB->query($query_2);
            $maxcount = 0;
            $i        = 0;

            while ($data = $DB->fetch_array($results2)) {
               $tabprogress[] = $data["nbStockTickets"];
               $tabnames[]    = $data['monthname'];
               if ($data["nbStockTickets"] > $maxcount) {
                  $maxcount = $data["nbStockTickets"];
               }
               $i++;
            }
            $is_deleted = "`glpi_tickets`.`is_deleted` = 0";

            $query = "SELECT DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m') as month,
                                    DATE_FORMAT(`glpi_tickets`.`date`, '%b %Y') as monthname,
                                    DATE_FORMAT(`glpi_tickets`.`date`, '%Y%m') AS monthnum, count(MONTH(`glpi_tickets`.`date`))
                                    FROM `glpi_tickets`
                                    WHERE $is_deleted AND (`glpi_tickets`.`date` >= '$previousyear-$currentmonth-01 00:00:00')
                                    AND (`glpi_tickets`.`date` <= '$currentyear-$currentmonth-01 00:00:00')
                                    " . $entities_criteria . "
                                    GROUP BY DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m')";

            $results = $DB->query($query);
            $i       = 0;
            while ($data = $DB->fetch_array($results)) {

               list($year, $month) = explode('-', $data['month']);

               $nbdays        = date("t", mktime(0, 0, 0, $month, 1, $year));
               $date_criteria = "(`glpi_tickets`.`date` >= '$year-$month-01 00:00:01' AND `glpi_tickets`.`date` <= ADDDATE('$year-$month-$nbdays 00:00:00' , INTERVAL 1 DAY) )";

               $query_1 = "SELECT COUNT(*) as count FROM `glpi_tickets`
                     WHERE $date_criteria
                     $entities_criteria
                     AND $is_deleted";

               $results_1 = $DB->query($query_1);
               $data_1    = $DB->fetch_array($results_1);

               $tabopened[] = $data_1['count'];

               $closedate_criteria = "(`glpi_tickets`.`closedate` >= '$year-$month-01 00:00:01' AND `glpi_tickets`.`closedate` <= ADDDATE('$year-$month-$nbdays 00:00:00' , INTERVAL 1 DAY) )";
               $query_2            = "SELECT COUNT(*) as count FROM `glpi_tickets`
                     WHERE $closedate_criteria
                     $entities_criteria
                     AND $is_deleted";

               $results_2 = $DB->query($query_2);
               $data_2    = $DB->fetch_array($results_2);

               $tabsolved[] = $data_2['count'];

               if ($month == date("m") && $year == date("Y")) {
                  $query_3 = "SELECT COUNT(*) as count FROM `glpi_tickets`
                     WHERE $is_deleted " . $entities_criteria . "
                     AND (((`glpi_tickets`.`date` <= '$year-$month-$nbdays 23:59:59')
                     AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . "))
                     OR ((`glpi_tickets`.`date` <= '$year-$month-$nbdays 23:59:59')
                     AND (`glpi_tickets`.`solvedate` > ADDDATE('$year-$month-$nbdays 00:00:00' , INTERVAL 1 DAY))))";

                  $results_3 = $DB->query($query_3);
                  $data_3    = $DB->fetch_array($results_3);

                  $tabprogress[] = $data_3['count'];
                  $tabnames[]    = $data['monthname'];
               }

               $i++;
            }

            $widget = new PluginMydashboardHtml();
            $title  = __("Number of opened and closed tickets by month", "mydashboard");
            $widget->setWidgetTitle((($isDebug)?"-22- ":"").$title);
            $widget->toggleWidgetRefresh();

            $titleopened         = __("Opened tickets", "mydashboard");
            $titlesolved         = __("Closed tickets", "mydashboard");
            $titleprogress       = __("Number of opened tickets", "mydashboard");
            $dataopenedBarset    = json_encode($tabopened);
            $datasolvedBarset    = json_encode($tabsolved);
            $dataprogressLineset = json_encode($tabprogress);
            $labels              = json_encode($tabnames);

            $graph = "<script type='text/javascript'>
            var dataTicketStatusBar = {
                    datasets: [
                    {
                      type: 'line',
                      data: $dataprogressLineset,
                      label: '$titleprogress',
                      borderColor: '#ff7f0e',
                            fill: false,
                            lineTension: '0.1',
                    }, {
                      type: 'bar',
                      data: $dataopenedBarset,
                      label: '$titleopened',
                      backgroundColor: '#1f77b4',
                    }, {
                      type: 'bar',
                      data: $datasolvedBarset,
                      label: '$titlesolved',
                      backgroundColor: '#aec7e8',
                    }],
                  labels:
                  $labels
                  };
            
            $(document).ready(
               function () {
                   var isChartRendered = false;
                   var canvas = document . getElementById('TicketStatusBarLineChart');
                   var ctx = canvas . getContext('2d');
                   ctx.canvas.width = 700;
                   ctx.canvas.height = 400;
                   var TicketStatusBarLineChart = new Chart(ctx, {
                         type: 'bar',
                         data: dataTicketStatusBar,
                         options: {
                             responsive:true,
                             maintainAspectRatio: true,
                             title:{
                                 display:false,
                                 text:'TicketStatusBarLineChart'
                             },
                             tooltips: {
                                 enabled: false,
//                                          mode: 'index',
//                                          intersect: false
                             },
//                             scales: {
//                                 xAxes: [{
//                                     stacked: true,
//                                 }],
//                                 yAxes: [{
//                                     stacked: true
//                                 }]
//                             },
                             animation: {
                              onComplete: function() {
                                var ctx = this.chart.ctx;
                               ctx.font = Chart.helpers.fontString(Chart.defaults.global.defaultFontSize, 'normal', Chart.defaults.global.defaultFontFamily);
                               ctx.fillStyle = '#595959';
                               ctx.textAlign = 'center';
                               ctx.textBaseline = 'bottom';
                               this.data.datasets.forEach(function (dataset) {
                                   for (var i = 0; i < dataset.data.length; i++) {
                                       var model = dataset._meta[Object.keys(dataset._meta)[0]].data[i]._model;
                                       ctx.fillText(dataset.data[i], model.x, model.y - 5);
                                   }
                               });
                                 
                                isChartRendered = true;
                              }
                            },
                         }
                     });
                  }
             );
             </script>";

            $params = ["widgetId"  => $widgetId,
                       "name"      => 'TicketStatusBarLineChart',
                       "onsubmit"  => false,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => 1];
            $graph  .= PluginMydashboardHelper::getGraphHeader($params);
            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;

            break;

         case $this->getType() . "23":

            $criterias = ['entities_id', 'is_recursive', 'year', 'type'];
            $params    = ["preferences" => $this->preferences,
                          "criterias"   => $criterias,
                          "opt"         => $opt];
            $options   = PluginMydashboardHelper::manageCriterias($params);

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
            $widget->setWidgetTitle((($isDebug)?"-23- ":"").__("Average real duration of treatment of the ticket", "mydashboard"));
            $widget->setWidgetComment(__("Display of average real duration of treatment of tickets (actiontime of tasks)", "mydashboard"));
            $dataLineset = json_encode($tabduration);
            $labelsLine  = json_encode($tabnames);
            $tabdatesset = json_encode($tabdates);

            $taskduration = __('Tasks duration (minutes)', 'mydashboard');

            $graph = "<script type='text/javascript'>
                     var AverageData = {
                             datasets: [{
                               data: $dataLineset,
                               label: '$taskduration',
                               backgroundColor: '#1f77b4',
                             }],
                           labels:
                           $labelsLine
                           };
                     var dateset = $tabdatesset;
                     $(document).ready(
                        function () {
                            var isChartRendered = false;
                            var canvas = document . getElementById('AverageBarChart');
                            var ctx = canvas . getContext('2d');
                            ctx.canvas.width = 700;
                            ctx.canvas.height = 400;
                            var AverageBarChart = new Chart(ctx, {
                                  type: 'bar',
                                  data: AverageData,
                                  options: {
                                      responsive:true,
                                      maintainAspectRatio: true,
                                      title:{
                                          display:false,
                                          text:'AverageBarChart'
                                      },
                                      tooltips: {
                                          enabled: false,
//                                          mode: 'index',
//                                          intersect: false
                                      },
                                      scales: {
                                          xAxes: [{
                                              stacked: true,
                                          }],
                                          yAxes: [{
                                              stacked: true
                                          }]
                                      },
                                      animation: {
                                       onComplete: function() {
                                         var chartInstance = this.chart,
                                          ctx = chartInstance.ctx;
                                          ctx.font = Chart.helpers.fontString(Chart.defaults.global.defaultFontSize, Chart.defaults.global.defaultFontStyle, Chart.defaults.global.defaultFontFamily);
                                          ctx.textAlign = 'center';
                                          ctx.textBaseline = 'bottom';
                              
                                          this.data.datasets.forEach(function (dataset, i) {
                                              var meta = chartInstance.controller.getDatasetMeta(i);
                                              meta.data.forEach(function (bar, index) {
                                                  var data = dataset.data[index];                            
                                                  ctx.fillText(data, bar._model.x, bar._model.y - 5);
                                              });
                                          });
                                         isChartRendered = true;
                                       }
                                     },
                                  }
                              });
                           }
                      );
                     
                      </script>";

            $params = ["widgetId"  => $widgetId,
                       "name"      => 'AverageBarChart',
                       "onsubmit"  => false,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => 1];
            $graph  .= PluginMydashboardHelper::getGraphHeader($params);
            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;

            break;

         case $this->getType() . "24":

            $criterias = ['entities_id', 'is_recursive', 'year', 'type'];
            $params    = ["preferences" => $this->preferences,
                          "criterias"   => $criterias,
                          "opt"         => $opt];
            $options   = PluginMydashboardHelper::manageCriterias($params);

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

            $palette = PluginMydashboardColor::getColors(10);

            $widget = new PluginMydashboardHtml();
            $widget->setWidgetTitle((($isDebug)?"-24- ":"").__("Top ten technicians (by tickets number)", "mydashboard"));
            $widget->setWidgetComment(__("Display of number of tickets by technicians", "mydashboard"));
            $dataticketset = json_encode($tabtickets);

            $backgroundColor = json_encode($palette);
            $tabNamesset     = json_encode($tabtechName);
            $tabIdTechset    = json_encode($tabtechid);
            $ticketsnumber   = __('Tickets number', 'mydashboard');

            $graph = "<script type='text/javascript'>
                     var TicketByTechsData = {
                             datasets: [{
                               data: $dataticketset,
                               label: '$ticketsnumber',
                               backgroundColor: $backgroundColor,
                             }],
                           labels: $tabNamesset
                           };
                     var techidset = $tabIdTechset;
                     $(document).ready(
                        function () {
                            var isChartRendered = false;
                            var canvas = document . getElementById('TicketByTechsBarChart');
                            var ctx = canvas . getContext('2d');
                            ctx.canvas.width = 700;
                            ctx.canvas.height = 400;
                            var TicketByTechsBarChart = new Chart(ctx, {
                                  type: 'horizontalBar',
                                  data: TicketByTechsData,
                                  options: {
                                      responsive:true,
                                      maintainAspectRatio: true,
                                      title:{
                                          display:false,
                                          text:'TicketByTechsBarChart'
                                      },
                                      legend: {
                                          display:false,
                                          position: 'right',
                                      },
                                      tooltips: {
                                          enabled: true,
//                                          mode: 'index',
//                                          intersect: false
                                      },
//                                      scales: {
//                                          xAxes: [{
//                                              stacked: true,
//                                          }],
//                                          yAxes: [{
//                                              stacked: true
//                                          }]
//                                      },
                                      animation: {
                                       onComplete: function() {
//                                         var chartInstance = this.chart,
//                                          ctx = chartInstance.ctx;
//                                          ctx.font = Chart.helpers.fontString(Chart.defaults.global.defaultFontSize, Chart.defaults.global.defaultFontStyle, Chart.defaults.global.defaultFontFamily);
//                                          ctx.textAlign = 'center';
//                                          ctx.textBaseline = 'bottom';
//                              
//                                          this.data.datasets.forEach(function (dataset, i) {
//                                              var meta = chartInstance.controller.getDatasetMeta(i);
//                                              meta.data.forEach(function (bar, index) {
//                                                  var data = dataset.data[index];
//                                                  ctx.fillText(data, bar._model.x, bar._model.y - 5);
//                                              });
//                                          });
                                         isChartRendered = true;
                                       }
                                     },
                                     hover: {
                                        onHover: function(event,elements) {
                                           $('#TicketByTechsBarChart').css('cursor', elements[0] ? 'pointer' : 'default');
                                         }
                                      }
                                  }
                              });
                                canvas.onclick = function(evt) {
                                 var activePoints = TicketByTechsBarChart.getElementsAtEvent(evt);
                                 if (activePoints[0]) {
                                   var chartData = activePoints[0]['_chart'].config.data;
                                   var idx = activePoints[0]['_index'];
                                   var label = chartData.labels[idx];
                                   var value = chartData.datasets[0].data[idx];
                                   var techtik = techidset[idx];
                                   $.ajax({
                                      url: '" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/ajax/launchURL.php',
                                      type: 'POST',
                                      data:{techtik:techtik,
                                           year:$year_criteria,
                                           type:$type, 
                                           entities_id:$entities_id_criteria, 
                                           sons:$sons_criteria, 
                                           widget:'$widgetId'},
                                      success:function(response) {
                                              window.open(response);
                                            }
                                   });
                                 }
                               };
                           }
                      );
                     
                      </script>";

            $params = ["widgetId"  => $widgetId,
                       "name"      => 'TicketByTechsBarChart',
                       "onsubmit"  => false,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => count($tabtickets)];
            $graph  .= PluginMydashboardHelper::getGraphHeader($params);
            $widget->toggleWidgetRefresh();
            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;

            break;

         case $this->getType() . "25":

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
                           `groups_id`,
                           COUNT(`glpi_tickets`.`id`) AS nb
                        FROM `glpi_tickets`
                        LEFT JOIN `glpi_groups_tickets` 
                        ON (`glpi_groups_tickets`.`tickets_id` = `glpi_tickets`.`id` 
                        AND `glpi_groups_tickets`.`type` = '" . CommonITILActor::REQUESTER . "')
                        WHERE $is_deleted $type_criteria ";
            $query .= $dbu->getEntitiesRestrictRequest("AND", Ticket::getTable());
            $query .= " AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ") ";
            $query .= " GROUP BY `groups_id`";

            $result = $DB->query($query);
            $nb     = $DB->numrows($result);

            $name     = [];
            $datas    = [];
            $tabgroup = [];
            if ($nb) {
               while ($data = $DB->fetch_array($result)) {
                  if (!empty($data['groups_id'])) {
                     $name[] = Dropdown::getDropdownName("glpi_groups", $data['groups_id']);
                  } else {
                     $name[] = __('None');
                  }
                  $datas[] = $data['nb'];
                  if (!empty($data['groups_id'])) {
                     $tabgroup[] = $data['groups_id'];
                  } else {
                     $tabgroup[] = 0;
                  }
               }
            }

            $widget = new PluginMydashboardHtml();
            $title  = __("Number of opened tickets by requester groups", "mydashboard");
            $widget->setWidgetTitle((($isDebug)?"-25- ":"").$title);

            $dataPieset         = json_encode($datas);
            $palette            = PluginMydashboardColor::getColors($nb);
            $backgroundPieColor = json_encode($palette);
            $labelsPie          = json_encode($name);
            $tabgroupset        = json_encode($tabgroup);
            $graph              = "<script type='text/javascript'>
         
            var dataGroupPie = {
              datasets: [{
                data: $dataPieset,
                backgroundColor: $backgroundPieColor
              }],
              labels: $labelsPie
            };
            var groupset = $tabgroupset;
            $(document).ready(
              function() {
                var isChartRendered = false;
                var canvas = document.getElementById('TicketsByRequesterGroupPieChart');
                var ctx = canvas.getContext('2d');
                ctx.canvas.width = 700;
                ctx.canvas.height = 400;
                var TicketsByRequesterGroupPieChart = new Chart(ctx, {
                  type: 'polarArea',
                  data: dataGroupPie,
                  options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    animation: {
                        onComplete: function() {
                          isChartRendered = true
                        }
                      },
                      hover: {
                         onHover: function(event,elements) {
                            $('#TicketsByRequesterGroupPieChart').css('cursor', elements[0] ? 'pointer' : 'default');
                          }
                       }
                   }
                });
            
                canvas.onclick = function(evt) {
                     var activePoints = TicketsByRequesterGroupPieChart.getElementsAtEvent(evt);
                     if (activePoints[0]) {
                       var chartData = activePoints[0]['_chart'].config.data;
                       var idx = activePoints[0]['_index'];
                       var label = chartData.labels[idx];
                       var value = chartData.datasets[0].data[idx];
                       var groups_id = groupset[idx];
         //              var url = \"http://example.com/?label=\" + label + \"&value=\" + value;
                       $.ajax({
                          url: '" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/ajax/launchURL.php',
                          type: 'POST',
                          data:{groups_id:groups_id, type:$type, widget:'$widgetId'},
                          success:function(response) {
                                  window.open(response);
                                }
                       });
                     }
                   };
              }
            );
                
             </script>";

            $params = ["widgetId"  => $widgetId,
                       "name"      => 'TicketsByRequesterGroupPieChart',
                       "onsubmit"  => false,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => $nb];
            $graph  .= PluginMydashboardHelper::getGraphHeader($params);
            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;

         case $this->getType() . "26":

            $criterias = ['entities_id', 'is_recursive', 'year'];
            $params    = ["preferences" => $this->preferences,
                          "criterias"   => $criterias,
                          "opt"         => $opt];
            $options   = PluginMydashboardHelper::manageCriterias($params);

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

            $results = [
                "name" => [],
                "colors" => [],
                "datas" => [],
                "tab" => [],
            ];

            $notsatisfy = 0;
            $satisfy    = 0;
            if ($nb > 0 && $sum['satisfaction'] > 0) {
               $satisfy    = round(($sum['satisfaction']) * 100 / (5), 2);
               $notsatisfy = round(100 - $satisfy, 2);
            }

            $results['datas'][] = $satisfy;
            $results['datas'][] = $notsatisfy;
            $results['colors'] = PluginMydashboardColor::getColors(2);
            $results['name'][] = __("Satisfy percent", "mydashboard");
            $results['name'][] = __("Not satisfy percent", "mydashboard");

            $events = [
                "onclick" => false,
                "hover" => false,
                "tooltip" => true
            ];

            $params = ["widgetId"  => $widgetId,
                "name" => $widgetId . 'PieChart',
                "onsubmit"  => false,
                "opt"       => $opt,
                "criterias" => $criterias,
                "export"    => true,
                "canvas"    => true,
                "nb"        => $nb];

            $infos = [
                "type" => "TICKET",
                "filter" => "STATISFACTION",
                "state" => null,
                "values" => []];

            if(!PluginMydashboardHelper::graph(
                $graph2, 'pie', $widgetId, $results, $events, $infos, $crit)){
               Toolbox::userErrorHandlerDebug(
                   E_ERROR,
                   "You passed wrong parameters to generate graph please verify",
                   "infotel.class.php",
                   "");
            }
            $graph2     .= PluginMydashboardHelper::getGraphHeader($params);

            $widget = new PluginMydashboardHtml();
            $widget->setWidgetTitle((($isDebug)?"-26- ":"").__("Global satisfaction level", "mydashboard"));
            $widget->setWidgetHtmlContent($graph2);

            return $widget;
            break;

         case $this->getType() . "27":

            $criterias = ['entities_id', 'is_recursive', 'type', 'groups_id'];
            $params    = ["preferences" => $this->preferences,
                          "criterias"   => $criterias,
                          "opt"         => $opt];
            $options   = PluginMydashboardHelper::manageCriterias($params);

            $opt                  = $options['opt'];
            $crit                 = $options['crit'];
            $type                 = $opt['type'];
            $type_criteria        = $crit['type'];
            $entities_criteria    = $crit['entities_id'];
            $entities_id_criteria = $crit['entity'];
            $sons_criteria        = $crit['sons'];
            $groups_criteria      = $crit['groups_id'];
            $is_deleted           = "`glpi_tickets`.`is_deleted` = 0";

            $query = "SELECT DISTINCT
                           `glpi_tickets`.`locations_id`,
                           COUNT(`glpi_tickets`.`id`) AS nb
                        FROM `glpi_tickets` ";
            if (isset($opt['groups_id']) && ($opt['groups_id'] != 0)) {
               $query .= " LEFT JOIN `glpi_groups_tickets` 
                        ON (`glpi_groups_tickets`.`tickets_id` = `glpi_tickets`.`id`
                            AND `glpi_groups_tickets`.`type` = '" . CommonITILActor::ASSIGN . "') ";
            }
            $query .= " WHERE $is_deleted $type_criteria $entities_criteria ";
            if (isset($opt['groups_id']) && ($opt['groups_id'] != 0)) {
               $query .= " AND `glpi_groups_tickets`.`groups_id` = " . $groups_criteria;
            }
            $query .= " AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ") ";
            $query .= " GROUP BY `locations_id` LIMIT 10";

            $result = $DB->query($query);
            $nb     = $DB->numrows($result);

            $name        = [];
            $datas       = [];
            $tablocation = [];
            if ($nb) {
               while ($data = $DB->fetch_array($result)) {
                  if (!empty($data['locations_id'])) {
                     $name[] = Dropdown::getDropdownName("glpi_locations", $data['locations_id']);
                  } else {
                     $name[] = __('None');
                  }
                  $datas[] = $data['nb'];
                  if (!empty($data['locations_id'])) {
                     $tablocation[] = $data['locations_id'];
                  } else {
                     $tablocation[] = 0;
                  }
               }
            }

            $widget = new PluginMydashboardHtml();
            $title  = __("Top 10 of opened tickets by location", "mydashboard");
            $widget->setWidgetTitle((($isDebug)?"-27- ":"").$title);

            $dataPieset         = json_encode($datas);
            $palette            = PluginMydashboardColor::getColors($nb);
            $backgroundPieColor = json_encode($palette);
            $labelsPie          = json_encode($name);
            $tablocationset     = json_encode($tablocation);
            $graph              = "<script type='text/javascript'>
         
            var dataLocationPie = {
              datasets: [{
                data: $dataPieset,
                backgroundColor: $backgroundPieColor
              }],
              labels: $labelsPie
            };
            var locationset = $tablocationset;
            $(document).ready(
              function() {
                var isChartRendered = false;
                var canvas = document.getElementById('TicketsByLocationPieChart');
                var ctx = canvas.getContext('2d');
                ctx.canvas.width = 700;
                ctx.canvas.height = 400;
                var TicketsByLocationPieChart = new Chart(ctx, {
                  type: 'polarArea',
                  data: dataLocationPie,
                  options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    animation: {
                        onComplete: function() {
                          isChartRendered = true
                        }
                      },
                      hover: {
                         onHover: function(event,elements) {
                            $('#TicketsByLocationPieChart').css('cursor', elements[0] ? 'pointer' : 'default');
                          }
                       }
                   }
                });
            
                canvas.onclick = function(evt) {
                     var activePoints = TicketsByLocationPieChart.getElementsAtEvent(evt);
                     if (activePoints[0]) {
                       var chartData = activePoints[0]['_chart'].config.data;
                       var idx = activePoints[0]['_index'];
                       var label = chartData.labels[idx];
                       var value = chartData.datasets[0].data[idx];
                       var locations_id = locationset[idx];
         //              var url = \"http://example.com/?label=\" + label + \"&value=\" + value;
                       $.ajax({
                          url: '" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/ajax/launchURL.php',
                          type: 'POST',
                          data:{locations_id:locations_id, 
                                entities_id:$entities_id_criteria, 
                                sons:$sons_criteria, 
                                type:$type, 
                                groups_id:$groups_criteria, 
                                widget:'$widgetId'},
                          success:function(response) {
                                  window.open(response);
                                }
                       });
                     }
                   };
              }
            );
                
             </script>";

            $params = ["widgetId"  => $widgetId,
                       "name"      => 'TicketsByLocationPieChart',
                       "onsubmit"  => false,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => $nb];
            $graph  .= PluginMydashboardHelper::getGraphHeader($params);
            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;

         case $this->getType() . "28":

            $criterias = ['entities_id', 'is_recursive', 'type', 'groups_id'];
            $params    = ["preferences" => $this->preferences,
                          "criterias"   => $criterias,
                          "opt"         => $opt];
            $options   = PluginMydashboardHelper::manageCriterias($params);

            $opt  = $options['opt'];
            $crit = $options['crit'];

            $type          = $opt['type'];
            $type_criteria = $crit['type'];
            //$status_criteria      = $crit['status'];
            $entities_criteria    = $crit['entities_id'];
            $entities_id_criteria = $crit['entity'];
            $sons_criteria        = $crit['sons'];
            $groups_criteria      = $crit['groups_id'];
            $is_deleted           = "`glpi_tickets`.`is_deleted` = 0";

            $widget = new PluginMydashboardHtml();
            $title  = __("Map - Opened tickets by location", "mydashboard");
            $widget->setWidgetComment(__("Display Tickets by location (Latitude / Longitude). You must define a Google API Key and add it into setup", "mydashboard"));
            $widget->setWidgetTitle((($isDebug)?"-28- ":"").$title);
            $query = "SELECT DISTINCT
                           `glpi_locations`.`completename` AS `name`,
                           `glpi_locations`.`latitude`, 
                           `glpi_locations`.`longitude`,
                           `glpi_locations`.`comment`,
                            `glpi_locations`.`id`,
                           COUNT(`glpi_tickets`.`id`) AS `nb`
                        FROM `glpi_tickets` ";
            if (isset($opt['groups_id']) && ($opt['groups_id'] != 0)) {
               $query .= " LEFT JOIN `glpi_groups_tickets` 
                        ON (`glpi_groups_tickets`.`tickets_id` = `glpi_tickets`.`id`
                            AND `glpi_groups_tickets`.`type` = '" . CommonITILActor::ASSIGN . "') 
                            LEFT JOIN `glpi_groups`  ON (`glpi_groups_tickets`.`groups_id` = `glpi_groups`.`id` ) ";
            }
            $query .= " LEFT JOIN `glpi_locations` ON (`glpi_tickets`.`locations_id` = `glpi_locations`.`id`)
                        LEFT JOIN `glpi_entities` ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`)
                        WHERE $is_deleted $type_criteria $entities_criteria ";
            if (isset($opt['groups_id']) && ($opt['groups_id'] != 0)) {
               $query .= " AND `glpi_groups_tickets`.`groups_id` = " . $groups_criteria;
            }
            //            $query .= " AND `status` IN('" . implode("', '", $status_criteria) . "')";
            $query .= " AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ") ";
            $query .= " GROUP BY `glpi_tickets`.`locations_id`";

            $result = $DB->query($query);
            $nb     = $DB->numrows($result);

            $locations = "[";
            $infos     = "[";

            if ($nb) {
               while ($data = $DB->fetch_array($result)) {
                  if (!empty($data['latitude']) && !empty($data['longitude'])) {
                     $name      = addslashes($data['name']);
                     $locations .= "['" . $name . "'," . $data['latitude'] . "," . $data['longitude'] . ",'" . $data['nb'] . "'],";
                     $comment   = addslashes(str_replace("\r\n", "<br>", $data['comment']));

                     $options['reset']                     = 'reset';
                     $options['criteria'][0]['field']      = 12; // status
                     $options['criteria'][0]['searchtype'] = 'equals';
                     $options['criteria'][0]['value']      = "notold";
                     $options['criteria'][0]['link']       = 'AND';

                     $options['criteria'][1]['field']      = 83; // location
                     $options['criteria'][1]['searchtype'] = 'equals';
                     $options['criteria'][1]['value']      = $data['id'];
                     $options['criteria'][1]['link']       = 'AND';

                     if ($type > 0) {
                        $options['criteria'][2]['field']      = 14; // type
                        $options['criteria'][2]['searchtype'] = 'equals';
                        $options['criteria'][2]['value']      = $type;
                        $options['criteria'][2]['link']       = 'AND';
                     }

                     $options['criteria'][3]['field']      = 80; // entities
                     $options['criteria'][3]['searchtype'] = 'equals';
                     if (isset($sons_criteria) && $sons_criteria > 0) {
                        $options['criteria'][3]['searchtype'] = 'under';
                     }
                     $options['criteria'][3]['value'] = $entities_id_criteria;
                     $options['criteria'][3]['link']  = 'AND';

                     if (!empty($groups_criteria)) {
                        $options['criteria'][4]['field']      = 8; // technician group
                        $options['criteria'][4]['searchtype'] = 'equals';
                        $options['criteria'][4]['value']      = $groups_criteria;
                        $options['criteria'][4]['link']       = 'AND';
                     }
                     $link_ticket = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
                                    Toolbox::append_params($options, "&");
                     $nb          = "<a href=\"" . $link_ticket . "\" target=\"_blank\">" . $data['nb'] . " " . _n('Ticket', 'Tickets', $data['nb']) . "</a>";

                     $infos .= "['<div class=\"info_content\">' + '<h5>$name</h5>'+ '<p>$comment</p>'+ '<p>$nb</p>'+'</div>'],";
                  }
               }
            }
            $locations .= "]";
            $infos     .= "]";

            $params = ["widgetId"  => $widgetId,
                       "name"      => 'TicketsByLocationMap',
                       "onsubmit"  => false,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => false,
                       "canvas"    => false,
                       "nb"        => $nb];
            $graph  = PluginMydashboardHelper::getGraphHeader($params);

            $graph .= "<script>
               function initialize() {
                   var map;
                   var bounds = new google.maps.LatLngBounds();
               //    var centre = { lat: 46.3333300, lng: 2.6000000 };
                   var mapOptions = {
                       //mapTypeId: roadmap,
                       zoom: 4,
                       streetViewControl: false,
               //        center: centre 
                   };
                   //http://chrisltd.com/blog/2013/08/google-map-random-color-pins/
                   //https://wrightshq.com/playground/placing-multiple-markers-on-a-google-map-using-api-3/
                   // Display a map on the page
                   map = new google.maps.Map(document.getElementById(\"TicketsByLocationMap\"), mapOptions);
                   map.setTilt(45);
                       
                   // Multiple Markers
                   var markers = $locations;
                   
                   //var icon = {
                         //url: '../pics/marker.png',
               //          scaledSize: new google.maps.Size(72, 40),
               //
                     //};
//                     var iconURLPrefix = 'https://maps.gstatic.com/mapfiles/api-3/images/';
                     var iconURLPrefix = '../pics/';
                     var icons = [
                        iconURLPrefix + 'spotlight-poi-dotless2_hdpi.png',
                        //iconURLPrefix + 'green.png',
                        //iconURLPrefix + 'orange.png',
                      ]
                   // Info Window Content
                   var infoWindowContent = $infos;
                       
                   // Display multiple markers on a map
                   var infoWindow = new google.maps.InfoWindow(), marker, i;

                   // Loop through our array of markers & place each one on the map  
                   for( i = 0; i < markers.length; i++ ) {
                       var position = new google.maps.LatLng(markers[i][1], markers[i][2]);
                       bounds.extend(position);
                       var fontSize = '14px';
                       if (markers[i][3] >= 100) {
                         fontSize = '10px';
                       }
                       marker = new google.maps.Marker({
                           position: position,
                           icon: {
                                  url:icons[0], 
                                  scaledSize: new google.maps.Size(27, 43), 
                                  labelOrigin: new google.maps.Point(14, 14),
                                  fillColor: '#FFF'
                                  },
                           map: map,
                           label: {
                              text: markers[i][3],
                              color: '#FFF',
                              fontSize: fontSize,
                              //fontWeight: 'bold',
                            },
                           title: markers[i][0]
                       });
                       
                       // Allow each marker to have an info window    
                       google.maps.event.addListener(marker, 'click', (function(marker, i) {
                           return function() {
                               infoWindow.setContent(infoWindowContent[i][0]);
                               infoWindow.open(map, marker);
                           }
                       })(marker, i));
               
                       // Automatically center the map fitting all markers on the screen
                       map.fitBounds(bounds);
                   }
               
                   // Override our map zoom level once our fitBounds function runs (Make sure it only runs once)
                   var boundsListener = google.maps.event.addListener((map), 'bounds_changed', function(event) {
                       this.setZoom(6);
                       google.maps.event.removeListener(boundsListener);
                   });
                   
               }
               $(document).ready( function () {
                       initialize();
                   });
                   </script>";
            $graph .= "<div id=\"map_wrapper\">";
            $graph .= "<div id=\"TicketsByLocationMap\" class=\"mapping\"></div>";
            $graph .= "</div>";

            $widget->toggleWidgetRefresh();
            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;

            break;

         case $this->getType() . "29":

            $criterias = ['entities_id', 'is_recursive', 'type', 'groups_id'];
            $paramsc   = ["preferences" => $this->preferences,
                          "criterias"   => $criterias,
                          "opt"         => $opt];
            $options   = PluginMydashboardHelper::manageCriterias($paramsc);

            $opt  = $options['opt'];
            $crit = $options['crit'];

            $type                 = $opt['type'];
            $entities_id_criteria = $crit['entity'];
            $sons_criteria        = $crit['sons'];
            $groups_criteria      = $crit['groups_id'];

            $widget = new PluginMydashboardHtml();
            $title  = __("OpenStreetMap - Opened tickets by location", "mydashboard");
            $widget->setWidgetComment(__("Display Tickets by location (Latitude / Longitude)", "mydashboard"));
            $widget->setWidgetTitle((($isDebug)?"-29- ":"").$title);

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
               }
               
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

            $criterias = ['entities_id', 'is_recursive', 'type'];
            $params    = ["preferences" => $this->preferences,
                          "criterias"   => $criterias,
                          "opt"         => $opt];
            $options   = PluginMydashboardHelper::manageCriterias($params);

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

            $name       = [];
            $datas      = [];
            $tabrequest = [];

            if ($nb) {
               while ($data = $DB->fetch_array($result)) {
                  $name[] = $data['name'];
                  //                  $datas[]       = Html::formatNumber(($data['nb']*100)/$total);
                  $datas[]      = intval($data['nb']);
                  $tabrequest[] = $data['requesttypes_id'];
               }
            }
            $widget = new PluginMydashboardHtml();
            $title  = __("Number of use of request sources", "mydashboard");
            $widget->setWidgetComment(__("Display number of request sources for closed tickets", "mydashboard"));
            $widget->setWidgetTitle((($isDebug)?"-30- ":"").$title);

            $dataPieset         = json_encode($datas);
            $palette            = PluginMydashboardColor::getColors($nb);
            $backgroundPieColor = json_encode($palette);
            $labelsPie          = json_encode($name);
            $tabrequestset      = json_encode($tabrequest);
            $graph              = "<script type='text/javascript'>
         
            var dataRequestTypePie = {
              datasets: [{
                data: $dataPieset,
                backgroundColor: $backgroundPieColor
              }],
              labels: $labelsPie
            };
            var requestset = $tabrequestset;
            $(document).ready(
              function() {
                var isChartRendered = false;
                var canvas = document.getElementById('RequestTypePieChart');
                var ctx = canvas.getContext('2d');
                ctx.canvas.width = 700;
                ctx.canvas.height = 400;
                var RequestTypePieChart = new Chart(ctx, {
                  type: 'doughnut',
                  data: dataRequestTypePie,
                  options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    animation: {
                        onComplete: function() {
                          isChartRendered = true
                        }
                      },
//                      tooltips: {
//                        callbacks: {
//                          label: function(tooltipItem, data) {
//                           var dataset = data.datasets[tooltipItem.datasetIndex];
//                            var total = dataset.data.reduce(function(previousValue, currentValue, currentIndex, array) {
//                              return previousValue + currentValue;
//                            });
//                            var currentValue = dataset.data[tooltipItem.index];
//                            var percentage = Math.floor(((currentValue/total) * 100)+0.5);         
//                            return percentage + \"%\";
//                          }
//                        }
//                      }
                   }
                });
              }
            );
                
             </script>";

            $params = ["widgetId"  => $widgetId,
                       "name"      => 'RequestTypePieChart',
                       "onsubmit"  => false,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => $nb];
            $graph  .= PluginMydashboardHelper::getGraphHeader($params);

            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;

         case $this->getType() . "31":

            $criterias = ['entities_id', 'is_recursive'];
            $params    = ["preferences" => $this->preferences,
                          "criterias"   => $criterias,
                          "opt"         => $opt];
            $options   = PluginMydashboardHelper::manageCriterias($params);

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
                  $percent = round(($data_1['count']*100)/$total, 2);
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
                  ['data'        => array_values($v),
                   'label'       => ($tabnames[$k] == NULL) ? __('None') : $tabnames[$k],
                   'backgroundColor' => $palette[$k],
                  ];
            }

            $widget = new PluginMydashboardHtml();
            $title  = __("Tickets request sources evolution", "mydashboard");
            $widget->setWidgetComment(__("Evolution of tickets request sources types by year", "mydashboard"));
            $widget->setWidgetTitle((($isDebug)?"-31- ":"").$title);
            $widget->toggleWidgetRefresh();

            $years      = __('Year', 'mydashboard');
            $nbrequests = _n('Request source', 'Request sources', 2);

            $jsonsets  = json_encode($datasets);
            $graph     = "<script type='text/javascript'>
      

            var RequestTypeEvolutionLine = {
                    datasets: $jsonsets,
                  labels:
                  $labelsLine
                  };
            
//            $(document).ready(
//               function () {
                 var isChartRendered = false;
                  var canvas = document . getElementById('RequestTypeEvolutionLineChart');
                   var ctx = canvas . getContext('2d');
                   ctx.canvas.width = 700;
                   ctx.canvas.height = 400;
                   var RequestTypeEvolutionLineChart = new Chart(ctx, {
                     type:
                     'bar',
                     data: RequestTypeEvolutionLine,
                     options: {
                     responsive: true,
                     maintainAspectRatio: true,
                      title:{
                          display: false,
                          text:'RequestTypeEvolutionLineChart'
                      },
                      tooltips: {
                     mode:
                     'index',
                          intersect: false,
                      },
                      hover: {
                     mode:
                     'nearest',
                          intersect: true
                      },
                      scales: {
                           xAxes: [{
                               stacked: true,
                               scaleLabel: {
                                  display: true,
                                  labelString: '$years'
                                 }
                           }],
                           yAxes: [{
                               stacked: true,
                               scaleLabel: {
                                  display: true,
                                  labelString: '$nbrequests'
                                 }
                           }]
                       },
                       animation: {
                        onComplete: function() {
                          isChartRendered = true
                        }
                      }
                   }
                   });

             </script>";

            $params = ["widgetId"  => $widgetId,
                       "name"      => 'RequestTypeEvolutionLineChart',
                       "onsubmit"  => false,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => 1];
            $graph  .= PluginMydashboardHelper::getGraphHeader($params);

            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;

            break;
      }
   }

   /**
    * @param $id
    *
    * @return string
    */
   //   private function getSeeProfilebutton class='btn btn-primary btn-sm'($id) {
   //      global $CFG_GLPI;
   //      return "<a target='blank' href='" . $CFG_GLPI['root_doc'] . "/front/user.form.php?id=" . $id . "'>"
   //             . "<input type='button class='btn btn-primary btn-sm'' class='submit' value=' " . __("Show Profile", "mydashboard") . " '/>"
   //             . "</a>";
   //   }

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
      if (isset($opt["groups_id"])
          && $opt["groups_id"] > 0) {
         $groups_id = $opt['groups_id'];
      }

      if (isset($groups_id) && $groups_id > 0) {
         $selected_group[] = $groups_id;
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
      if (isset($params["groups_id"])
          && $params["groups_id"] > 0) {
         $groups_id = $params['groups_id'];
      }

      if (isset($groups_id) && $groups_id > 0) {
         $selected_group[] = $groups_id;
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

         $month_tmp = $key;
         $nb_jours  = date("t", mktime(0, 0, 0, $key, 1, $year));

         if (strlen($key) == 1) {
            $month_tmp = "0" . $month_tmp;
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
