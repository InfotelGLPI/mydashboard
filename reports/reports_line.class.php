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
 * Class PluginMydashboardReports_Line
 */
class PluginMydashboardReports_Line extends CommonGLPI {

   private       $options;
   private       $pref;
   public static $reports = [6, 22, 34, 35];

   /**
    * PluginMydashboardReports_Line constructor.
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
         __('Line charts', "mydashboard") => [
            $this->getType() . "6"  => (($isDebug) ? "6 " : "") . __("Tickets stock by month", "mydashboard") . "&nbsp;<i class='fas fa-chart-line'></i>",
            $this->getType() . "22" => (($isDebug) ? "22 " : "") . __("Number of opened and closed tickets by month", "mydashboard") . "&nbsp;<i class='fas fa-chart-line'></i>",
            $this->getType() . "34" => (($isDebug) ? "34 " : "") . __("Number of opened and resolved / closed tickets by month", "mydashboard") . "&nbsp;<i class='fas fa-chart-line'></i>",
            $this->getType() . "35" => (($isDebug) ? "35 " : "") . __("Number of opened, closed, unplanned tickets by month", "mydashboard") . "&nbsp;<i class='fas fa-chart-line'></i>",
         ]
      ];
      return $widgets;
   }


   /**
    * @param       $widgetId
    * @param array $opt
    *
    * @return \PluginMydashboardHtml
    * @throws \GlpitestSQLError
    */
   public function getWidgetContentForItem($widgetId, $opt = []) {
      global $DB, $CFG_GLPI;
      $isDebug = $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE;
      $dbu     = new DbUtils();
      switch ($widgetId) {

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

            $entities_criteria          = $crit['entities_id'];
            $tech_groups_crit           = "";
            $technician_groups_criteria = $crit['technicians_groups_id'];
            $technician_groups_ids      = is_array($opt['technicians_groups_id']) ? $opt['technicians_groups_id'] : [$opt['technicians_groups_id']];
            if (count($opt['technicians_groups_id']) > 0) {
               $tech_groups_crit = " AND `groups_id` IN (" . implode(",", $technician_groups_ids) . ")";
            } else {
               $tech_groups_crit = " AND `glpi_plugin_mydashboard_stocktickets`.`groups_id` = -1";
            }
            $mdentities = PluginMydashboardHelper::getSpecificEntityRestrict("glpi_plugin_mydashboard_stocktickets", $opt);

            $currentmonth = date("m");
            $currentyear  = date("Y");

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
                                    GROUP BY DATE_FORMAT(`glpi_plugin_mydashboard_stocktickets`.`date`, '%Y-%m')";

            $tabdata    = [];
            $tabnames   = [];
            $results2   = $DB->query($query_2);
            $maxcount   = 0;
            $i          = 0;
            $is_deleted = "`glpi_tickets`.`is_deleted` = 0";
            while ($data = $DB->fetchArray($results2)) {
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
            while ($data = $DB->fetchArray($results)) {

               list($year, $month) = explode('-', $data['month']);

               $nbdays  = date("t", mktime(0, 0, 0, $month, 1, $year));
               $query_1 = "SELECT COUNT(*) as count FROM `glpi_tickets`
                  WHERE $is_deleted " . $entities_criteria . $technician_groups_criteria . "
                  AND ((`glpi_tickets`.`date` <= '$year-$month-$nbdays 23:59:59') 
                  AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . "))";

               $results_1 = $DB->query($query_1);
               $data_1    = $DB->fetchArray($results_1);

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
            $colors      = PluginMydashboardColor::getColors(1, 0);

            $month     = _n('month', 'months', 2);
            $nbtickets = __('Tickets number', 'mydashboard');

            $graph_datas = ['name'            => $name,
                            'ids'             => json_encode([]),
                            'data'            => $dataLineset,
                            'labels'          => $labelsLine,
                            'label'           => $title,
                            'backgroundColor' => $colors];


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

         case $this->getType() . "22":
            $name = 'TicketStatusBarLineChart';
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['entities_id',
                             'technicians_groups_id',
                             'group_is_recursive',
                             'requesters_groups_id',
                             'is_recursive',
                             'display_data',
                             'technicians_id',
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
            } else {
               $tech_groups_crit = " AND `glpi_plugin_mydashboard_stocktickets`.`groups_id` = -1";
            }
            $mdentities = PluginMydashboardHelper::getSpecificEntityRestrict("glpi_plugin_mydashboard_stocktickets", $opt);

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

            if(isset($opt["display_data"]) && $opt['display_data'] == "YEAR") {
               if (isset($opt["year"]) && $opt["year"] > 0) {
                  $currentyear = $opt["year"];
               }
               $date_crit = "`glpi_plugin_mydashboard_stocktickets`.`date` between '$currentyear-01-01' AND ADDDATE('$currentyear-01-01', INTERVAL 1 YEAR)";
               $date_crit_ticket = "`glpi_tickets`.`date` between '$currentyear-01-01' AND ADDDATE('$currentyear-01-01', INTERVAL 1 YEAR)";
            } else {
               $end_year = $opt['end_year'] ?? date("Y");
               $end_month = $opt['end_month'] ?? date("m");
               $start_month = $opt['start_month'] ?? date('m');
               $start_year = $opt['start_year'] ?? date("Y");
//               if($start_month <= 0) {
//                  $start_month = 12 + $start_month;
//                  $start_year = $start_year -1 ;
//               }
               if(strlen($start_month) == 1) {
                  $start_month = "0".$start_month;
               }
               $nbdays = date("t", mktime(0, 0, 0, $end_month, 1, $end_year));
               $date_crit = "`glpi_plugin_mydashboard_stocktickets`.`date` between '$start_year-$start_month-01' AND '$end_year-$end_month-$nbdays'";
               $date_crit_ticket = "`glpi_tickets`.`date` between '$start_year-$start_month-01' AND '$end_year-$end_month-$nbdays'";
            }
               $currentmonth = date("m");

               $query_stockTickets =
                  "SELECT DATE_FORMAT(`glpi_plugin_mydashboard_stocktickets`.`date`, '%Y-%m') as month," .
                  " DATE_FORMAT(`glpi_plugin_mydashboard_stocktickets`.`date`, '%b %Y') as monthname," .
                  " SUM(nbStockTickets) as nbStockTickets" .
                  " FROM `glpi_plugin_mydashboard_stocktickets`" .
                  " WHERE $date_crit " .
                  " " . $mdentities . $tech_groups_crit .
                  " GROUP BY DATE_FORMAT(`glpi_plugin_mydashboard_stocktickets`.`date`, '%Y-%m')";

               $resultsStockTickets = $DB->query($query_stockTickets);
               $nbStockTickets      = $DB->numrows($resultsStockTickets);
               $maxcount            = 0;
               $i                   = 0;
               $tabopened           = [];
               $tabclosed           = [];
               $tabprogress         = [];
               $tabnames            = [];
               if ($nbStockTickets) {
                  while ($data = $DB->fetchArray($resultsStockTickets)) {
                     $tabprogress[] = $data["nbStockTickets"];
                     if ($data["nbStockTickets"] > $maxcount) {
                        $maxcount = $data["nbStockTickets"];
                     }
                     $i++;
                  }
               }

               $is_deleted = "`glpi_tickets`.`is_deleted` = 0";
			   $q = "SET lc_time_names = '".$_SESSION['glpilanguage']."';";
               $r  = $DB->query($q);
               $query_tickets =
                  "SELECT DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m') as month," .
                  " DATE_FORMAT(`glpi_tickets`.`date`, '%b %Y') as monthname," .
                  " DATE_FORMAT(`glpi_tickets`.`date`, '%Y%m') AS monthnum, count(MONTH(`glpi_tickets`.`date`))" .
                  " FROM `glpi_tickets`" .
                  " WHERE $is_deleted" .
                  " AND $date_crit_ticket " .
                  " $entities_criteria" .
                  " $requester_groups_criteria" .
                  " $technician_groups_criteria" .
                  " $locations_criteria" .
                  " $type_criteria" .
                  " GROUP BY DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m')";

               $results   = $DB->query($query_tickets);
               $nbResults = $DB->numrows($results);
               $i         = 0;
			   $q = "SET lc_time_names = 'en_GB';";
               $r   = $DB->query($q);
               if ($nbResults) {
                  while ($data = $DB->fetchArray($results)) {

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
                        $data_1      = $DB->fetchArray($results_1);
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
                        $data_2      = $DB->fetchArray($results_2);
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
                           " AND ((`glpi_tickets`.`date` <= '$year-$month-$nbdays 23:59:59') 
                           AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")) ";

                        $results_3 = $DB->query($query_3);

                        if ($DB->numrows($results_3)) {
                           $data_3        = $DB->fetchArray($results_3);
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
                'borderColor' => PluginMydashboardColor::getColors(1, 0),
                'fill'        => false,
                'lineTension' => '0.1',
               ];

            $datasets[] =
               ["type"            => "bar",
                "data"            => $tabopened,
                "label"           => $titleopened,
                'backgroundColor' => PluginMydashboardColor::getColors(1, 1),
               ];

            $datasets[] =
               ['type'            => 'bar',
                'data'            => $tabclosed,
                'label'           => $titlesolved,
                'backgroundColor' => PluginMydashboardColor::getColors(1, 2),
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
            $mdentities                 = PluginMydashboardHelper::getSpecificEntityRestrict("glpi_plugin_mydashboard_stocktickets", $opt);

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
            } else {
               $tech_groups_crit = " AND `glpi_plugin_mydashboard_stocktickets`.`groups_id` = -1";
            }

            $query_stockTickets =
               "SELECT DATE_FORMAT(`glpi_plugin_mydashboard_stocktickets`.`date`, '%Y-%m') as month," .
               " DATE_FORMAT(`glpi_plugin_mydashboard_stocktickets`.`date`, '%b %Y') as monthname," .
               " SUM(nbStockTickets) as nbStockTickets" .
               " FROM `glpi_plugin_mydashboard_stocktickets`" .
               " WHERE `glpi_plugin_mydashboard_stocktickets`.`date` between '$currentyear-01-01' AND ADDDATE('$currentyear-01-01', INTERVAL 1 YEAR)" .
               " " . $mdentities . $tech_groups_crit .
               "  GROUP BY DATE_FORMAT(`glpi_plugin_mydashboard_stocktickets`.`date`, '%Y-%m')";

            $resultsStockTickets = $DB->query($query_stockTickets);
            $nbStockTickets      = $DB->numrows($resultsStockTickets);
            $maxcount            = 0;
            $i                   = 0;
            $tabopened           = [];
            $tabresolved         = [];
            $tabprogress         = [];
            $tabnames            = [];
            if ($nbStockTickets) {
               while ($data = $DB->fetchArray($resultsStockTickets)) {
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
               while ($data = $DB->fetchArray($results)) {

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
                     $data_1      = $DB->fetchArray($results_1);
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
                     $data_2        = $DB->fetchArray($results_2);
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
                        " AND ((`glpi_tickets`.`date` <= '$year-$month-$nbdays 23:59:59') 
                           AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")) ";

                     $results_3 = $DB->query($query_3);

                     if ($DB->numrows($results_3)) {
                        $data_3        = $DB->fetchArray($results_3);
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
                'borderColor' => PluginMydashboardColor::getColors(1, 0),
                'fill'        => false,
                'lineTension' => '0.1',
               ];

            $datasets[] =
               ["type"            => "bar",
                "data"            => $tabopened,
                "label"           => $titleopened,
                'backgroundColor' => PluginMydashboardColor::getColors(1, 1),
               ];

            $datasets[] =
               ['type'            => 'bar',
                'data'            => $tabresolved,
                'label'           => $titlesolved,
                'backgroundColor' => PluginMydashboardColor::getColors(1, 2),
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
            $name = 'TicketStatusUnplannedBarLineChart';
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
            } else {
               $tech_groups_crit = " AND `glpi_plugin_mydashboard_stocktickets`.`groups_id` = -1";
            }
            $mdentities = PluginMydashboardHelper::getSpecificEntityRestrict("glpi_plugin_mydashboard_stocktickets", $opt);

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
               " GROUP BY DATE_FORMAT(`glpi_plugin_mydashboard_stocktickets`.`date`, '%Y-%m')";

            $resultsStockTickets = $DB->query($query_stockTickets);
            $nbStockTickets      = $DB->numrows($resultsStockTickets);
            $maxcount            = 0;
            $i                   = 0;
            $tabopened           = [];
            $tabclosed           = [];
            $tabprogress         = [];
            $tabunplanned        = [];
            $tabnames            = [];
            if ($nbStockTickets) {
               while ($data = $DB->fetchArray($resultsStockTickets)) {
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
               while ($data = $DB->fetchArray($results)) {

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
                     $data_1      = $DB->fetchArray($results_1);
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
                     $data_2      = $DB->fetchArray($results_2);
                     $tabclosed[] = $data_2['count'];
                  } else {
                     $tabclosed[] = 0;
                  }

                  $whereUnplanned = " AND `glpi_tickettasks`.`actiontime` IS NULL ";

                  $query_3 =
                     "SELECT COUNT(*) as count FROM `glpi_tickets`" .
                     " $ticket_users_join" .
                     " LEFT JOIN `glpi_tickettasks` ON `glpi_tickets`.`id` = `glpi_tickettasks`.`tickets_id`" .
                     " WHERE $closedate_criteria" .
                     " $technician_criteria" .
                     " $entities_criteria" .
                     " $requester_groups_criteria" .
                     " $technician_groups_criteria" .
                     " $locations_criteria" .
                     " $type_criteria" .
                     " AND $is_deleted $whereUnplanned";

                  $results_3 = $DB->query($query_3);

                  if ($DB->numrows($results_3)) {
                     $data_3         = $DB->fetchArray($results_3);
                     $tabunplanned[] = $data_3['count'];
                  } else {
                     $tabunplanned[] = 0;
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
                        " AND ((`glpi_tickets`.`date` <= '$year-$month-$nbdays 23:59:59') 
                           AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")) ";

                     $results_3 = $DB->query($query_3);

                     if ($DB->numrows($results_3)) {
                        $data_3        = $DB->fetchArray($results_3);
                        $tabprogress[] = $data_3['count'];
                     } else {
                        $tabprogress[] = 0;
                     }
                  }

                  $i++;
               }
            }

            $widget = new PluginMydashboardHtml();
            $title  = __("Number of opened, closed  and unplanned tickets by month", "mydashboard");
            $widget->setWidgetTitle((($isDebug) ? "22 " : "") . $title);
            $widget->toggleWidgetRefresh();

            $titleopened    = __("Opened tickets", "mydashboard");
            $titlesolved    = __("Closed tickets", "mydashboard");
            $titleunplanned = __("Not planned", "mydashboard");
            $titleprogress  = __("Opened tickets backlog", "mydashboard");
            $labels         = json_encode($tabnames);

            $datasets[] =
               ['type'        => 'line',
                'data'        => $tabprogress,
                'label'       => $titleprogress,
                'borderColor' => PluginMydashboardColor::getColors(1, 0),
                'fill'        => false,
                'lineTension' => '0.1',
               ];

            $datasets[] =
               ["type"            => "bar",
                "data"            => $tabopened,
                "label"           => $titleopened,
                'backgroundColor' => PluginMydashboardColor::getColors(1, 1),
               ];

            $datasets[] =
               ['type'            => 'bar',
                'data'            => $tabclosed,
                'label'           => $titlesolved,
                'backgroundColor' => PluginMydashboardColor::getColors(1, 2),
               ];

            $datasets[] =
               ['type'            => 'bar',
                'data'            => $tabunplanned,
                'label'           => $titleunplanned,
                'backgroundColor' => PluginMydashboardColor::getColors(1, 3),
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

         default:
            break;
      }
   }
}
