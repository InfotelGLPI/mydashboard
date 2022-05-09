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

/**
 * Class PluginMydashboardReports_Bar
 */
class PluginMydashboardReports_Bar extends CommonGLPI {

   private       $options;
   private       $pref;
   public static $reports = [1, 8, 15, 21, 23, 24, 35, 36, 37, 38, 39, 40, 41, 42];

   /**
    * PluginMydashboardReports_Bar constructor.
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

      $widgets = [
         __('Bar charts', "mydashboard") => [
            $this->getType() . "1"  => ["title"   => __("Opened tickets backlog", "mydashboard"),
                                        "icon"    => "ti ti-chart-bar",
                                        "comment" => __("Display of opened tickets by month", "mydashboard")],
            $this->getType() . "8"  => ["title"   => __("Process time by technicians by month", "mydashboard"),
                                        "icon"    => "ti ti-chart-bar",
                                        "comment" => __("Sum of ticket tasks duration by technicians", "mydashboard")],
            $this->getType() . "15" => ["title"   => __("Top ten ticket categories by type of ticket", "mydashboard"),
                                        "icon"    => "ti ti-chart-bar",
                                        "comment" => __("Display of Top ten ticket categories by type of ticket", "mydashboard")],
            $this->getType() . "21" => ["title"   => __("Number of tickets affected by technicians by month", "mydashboard"),
                                        "icon"    => "ti ti-chart-bar",
                                        "comment" => __("Sum of ticket affected by technicians", "mydashboard")],
            $this->getType() . "23" => ["title"   => __("Average real duration of treatment of the ticket", "mydashboard"),
                                        "icon"    => "ti ti-chart-bar",
                                        "comment" => __("Display of average real duration of treatment of tickets (actiontime of tasks)", "mydashboard")],
            $this->getType() . "24" => ["title"   => __("Top ten technicians (by tickets number)", "mydashboard"),
                                        "icon"    => "ti ti-chart-bar",
                                        "comment" => __("Display of number of tickets by technicians", "mydashboard")],
            $this->getType() . "35" => ["title"   => __("Age of tickets", "mydashboard"),
                                        "icon"    => "ti ti-chart-bar",
                                        "comment" => ""],
            $this->getType() . "36" => ["title"   => __("Number of opened tickets by priority", "mydashboard"),
                                        "icon"    => "ti ti-chart-bar",
                                        "comment" => ""],
            $this->getType() . "37" => ["title"   => __("Stock of tickets by status", "mydashboard"),
                                        "icon"    => "ti ti-chart-bar",
                                        "comment" => ""],
            $this->getType() . "38" => ["title"   => __("Number of opened ticket and average satisfaction per trimester", "mydashboard"),
                                        "icon"    => "ti ti-chart-bar",
                                        "comment" => ""],
            //            $this->getType() . "39" => ["title"   => __("Responsiveness over 12 rolling and pending by month", "mydashboard"),
            //                                        "icon"    => "ti ti-chart-bar",
            //                                        "comment" => ""],
            $this->getType() . "40" => ["title"   => __("Tickets request sources evolution", "mydashboard"),
                                        "icon"    => "ti ti-chart-bar",
                                        "comment" => __("Evolution of tickets request sources types by year", "mydashboard")],
            $this->getType() . "41" => ["title"   => __("Tickets solution types evolution", "mydashboard"),
                                        "icon"    => "ti ti-chart-bar",
                                        "comment" => __("Evolution of solution types by year", "mydashboard")],
            $this->getType() . "42" => ["title"   => __("Solve delay and take into account of tickets", "mydashboard"),
                                        "icon"    => "ti ti-chart-bar",
                                        "comment" => ""],
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
               while ($data = $DB->fetchAssoc($result)) {
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

            $nbtickets    = __('Tickets number', 'mydashboard');
            $js_ancestors = $crit['ancestors'];
            $colors       = PluginMydashboardColor::getColors(1, 0);;
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

         case $this->getType() . "8":
            $name = 'TimeByTechChart';
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['entities_id',
                             'is_recursive',
                             'technicians_groups_id',
                             'type',
                             'year',
                             'limit'];
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = ['type',
                             'year',
                             'limit'];
            }
            $opt['limit'] = isset($opt['limit']) ? $opt['limit'] : 10;
            $params       = ["preferences" => $this->preferences,
                             "criterias"   => $criterias,
                             "opt"         => $opt];
            $options      = PluginMydashboardHelper::manageCriterias($params);
            $opt          = $options['opt'];

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
               $username  = getUserName($tech_id);
               $dataset[] = [
                  "label"           => $username,
                  "data"            => array_values($times),
                  "backgroundColor" => $palette[$i]
               ];
               $i++;
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

         case $this->getType() . "15":
            $name = 'TopTenTicketCategoriesBarChart';
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['requesters_groups_id',
                             'technicians_groups_id',
                             'filter_date',
                             'entities_id',
                             'is_recursive',
                             'type',

                             'limit'];
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = ['requesters_groups_id',
                             'type',
                             'year',
                             'limit'];
            }
            $opt['limit'] = isset($opt['limit']) ? $opt['limit'] : 10;
            $params       = ["preferences" => $this->preferences,
                             "criterias"   => $criterias,
                             "opt"         => $opt];
            $options      = PluginMydashboardHelper::manageCriterias($params);

            $opt  = $options['opt'];
            $crit = $options['crit'];

            $type_criteria              = $crit['type'];
            $entities_criteria          = $crit['entities_id'];
            $requester_groups_criteria  = $crit['requesters_groups_id'];
            $technician_groups_criteria = $crit['technicians_groups_id'];
            $date_criteria              = $crit['date'];
            $is_deleted                 = "`glpi_tickets`.`is_deleted` = 0";
            $limit_query                = "";
            $limit                      = isset($opt['limit']) ? $opt['limit'] : 10;
            if ($limit > 0) {
               $limit_query = "LIMIT $limit";
            }

            $query = "SELECT `glpi_itilcategories`.`completename` as itilcategories_name, COUNT(`glpi_tickets`.`id`) as count,`glpi_itilcategories`.`id` as catID
                     FROM `glpi_tickets`
                     LEFT JOIN `glpi_itilcategories`
                        ON (`glpi_itilcategories`.`id` = `glpi_tickets`.`itilcategories_id`)
                     WHERE $date_criteria
                     $entities_criteria $type_criteria $requester_groups_criteria $technician_groups_criteria
                     AND $is_deleted
                     GROUP BY `glpi_itilcategories`.`id`
                     ORDER BY count DESC
                     $limit_query";

            $result   = $DB->query($query);
            $nb       = $DB->numrows($result);
            $tabdata  = [];
            $tabnames = [];
            $tabcat   = [];
            if ($nb) {
               while ($data = $DB->fetchAssoc($result)) {
                  $tabdata[]           = $data['count'];
                  $itilcategories_name = $data['itilcategories_name'];
                  if ($data['itilcategories_name'] == NULL) {
                     $itilcategories_name = __('None');
                  }
                  $tabnames[] = $itilcategories_name;
                  $tabcat[]   = $data["catID"];
               }
            }
            $backgroundColor = PluginMydashboardColor::getColors(1);
            $nbtickets       = __('Tickets number', 'mydashboard');
            $dataset[]       = ["type"            => 'horizontalBar',
                                "label"           => $nbtickets,
                                "data"            => $tabdata,
                                "backgroundColor" => $backgroundColor,
                                "datalabels"      => json_encode(["align"  => "end",
                                                                  "anchor" => "end"])
                                //                          'fill'            => false
            ];

            $widget = new PluginMydashboardHtml();
            $title  = __("Top ten ticket categories by type of ticket", "mydashboard");
            $widget->setWidgetTitle((($isDebug) ? "15 " : "") . $title);
            $widget->setWidgetComment(__("Display of Top ten ticket categories by type of ticket"
               ,                         "mydashboard"));
            $databacklogset = json_encode($dataset);
            $labelsback     = json_encode($tabnames);
            $idsback        = json_encode($tabcat);

            $graph_datas = ['name'   => $name,
                            'ids'    => $idsback,
                            'data'   => $databacklogset,
                            'labels' => $labelsback];

            $js_ancestors = $crit['ancestors'];


            $type                 = $opt['type'];
            $entities_id_criteria = $crit['entity'];
            $sons_criteria        = $crit['sons'];
            $year                 = $opt['year'] ?? '';
            $graph_criterias      = ['entities_id'        => $entities_id_criteria,
                                     'sons'               => $sons_criteria,
                                     'group_is_recursive' => $js_ancestors,
                                     'technician_groups'  => $opt['technicians_groups_id'] ?? [],
                                     'type'               => $type,
                                     'year'               => $year ?? '',
                                     'widget'             => $widgetId];

            $graph = PluginMydashboardBarChart::launchHorizontalGraph($graph_datas, $graph_criterias);

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

         case $this->getType() . "21":
            $name = 'TicketsByTechChart';
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['entities_id',
                             'is_recursive',
                             'technicians_groups_id',
                             'group_is_recursive',
                             'type',
                             'year',
                             'limit'];
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = ['type',
                             'year',
                             'limit'];
            }
            $opt['limit'] = isset($opt['limit']) ? $opt['limit'] : 10;
            $params       = ["preferences" => $this->preferences,
                             "criterias"   => $criterias,
                             "opt"         => $opt];
            $options      = PluginMydashboardHelper::manageCriterias($params);
            $opt          = $options['opt'];

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
               $username  = getUserName($tech_id);
               $dataset[] = [
                  "label"           => $username,
                  "data"            => array_values($tickets),
                  "backgroundColor" => $palette[$i]
               ];
               $i++;
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
            if (($currentmonth + 1) >= 12) {
               $nextmonth = "01";
            } else {
               $nextmonth = $currentmonth + 1;
            }

            $is_deleted = "`glpi_tickets`.`is_deleted` = 0";

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
            while ($data = $DB->fetchArray($results)) {

               list($year, $month) = explode('-', $data['month']);

               $nbdays  = date("t", mktime(0, 0, 0, $month, 1, $year));
               $query_1 = "SELECT COUNT(DISTINCT `glpi_tickets`.`id`) AS nb_tickets, SUM(`glpi_tickettasks`.`actiontime`) AS count 
                          FROM `glpi_tickettasks`
                          LEFT JOIN `glpi_tickets` ON (`glpi_tickets`.`id` = `glpi_tickettasks`.`tickets_id`)
                          WHERE $is_deleted " . $entities_criteria . $type_criteria . "
                           AND (`glpi_tickettasks`.`date` >= '$year-$month-01 00:00:01' 
                           AND `glpi_tickettasks`.`date` <= ADDDATE('$year-$month-$nbdays 00:00:00' , INTERVAL 1 DAY) )";

               $results_1         = $DB->query($query_1);
               $data_1            = $DB->fetchArray($results_1);
               $average_by_ticket = 0;

               if ($data_1['nb_tickets'] > 0
                   && $data_1['count'] > 0) {
                  $average_by_ticket = ($data_1['count'] / $data_1['nb_tickets']) / 60;
               }
               $tabduration[] = round($average_by_ticket ?? 0, 2);
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

            $colors          = PluginMydashboardColor::getColors(1, 0);
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
                             'type',
                             'begin',
                             'end',
                             'limit'];
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = ['year',
                             'type',
                             'limit'];
            }
            $opt['limit'] = isset($opt['limit']) ? $opt['limit'] : 10;
            $opt['begin'] = isset($opt['begin']) ? $opt['begin'] : date('Y-m-d H:i:s', strtotime('-1 year'));;
            $opt['end'] = isset($opt['end']) ? $opt['end'] : date('Y-m-d H:i:s');

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
            $date_criteria        = " (`glpi_tickets`.`date` >= '" . $opt['begin'] . "'
                                               AND `glpi_tickets`.`date` <= ADDDATE('" . $opt['end'] . "' , INTERVAL 1 DAY))";
            //            $year_criteria        = $crit['year'];
            $is_deleted  = "`glpi_tickets`.`is_deleted` = 0";
            $limit_query = "";
            $limit       = isset($opt['limit']) ? $opt['limit'] : 10;
            if ($limit > 0) {
               $limit_query = "LIMIT $limit";
            }

            $query   = "SELECT IFNULL(`glpi_tickets_users`.`users_id`,-1) as users_id, COUNT(`glpi_tickets`.`id`) as count
                     FROM `glpi_tickets`
                     LEFT JOIN `glpi_tickets_users`
                        ON (`glpi_tickets_users`.`tickets_id` = `glpi_tickets`.`id` AND `glpi_tickets_users`.`type` = 2)
                     WHERE $date_criteria
                     $entities_criteria $type_criteria
                     AND $is_deleted
                     GROUP BY `glpi_tickets_users`.`users_id`
                     ORDER BY count DESC
                     $limit_query";
            $results = $DB->query($query);

            $tabtickets  = [];
            $tabtech     = [];
            $tabtechName = [];
            $tabtechid   = [];
            while ($data = $DB->fetchArray($results)) {
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

            $ticketsnumber   = __('Tickets number', 'mydashboard');
            $palette         = PluginMydashboardColor::getColors(1);
            $backgroundColor = json_encode($palette);
            $dataset[]       = ["type"            => 'horizontalBar',
                                "label"           => $ticketsnumber,
                                "data"            => $tabtickets,
                                "backgroundColor" => $palette,
                                //                          'fill'            => false
            ];

            $dataticketset = json_encode($dataset);
            $tabNamesset   = json_encode($tabtechName);
            $tabIdTechset  = json_encode($tabtechid);


            $graph_datas = ['name'   => $name,
                            'ids'    => $tabIdTechset,
                            'data'   => $dataticketset,
                            'labels' => $tabNamesset,
                            //                            'label'           => $ticketsnumber,
                            //                            'backgroundColor' => $backgroundColor
            ];

            $graph_criterias = ['entities_id' => $entities_id_criteria,
                                'sons'        => $sons_criteria,
                                'type'        => $type,
                                //                                'year'        => $year_criteria,
                                'begin'       => $opt['begin'],
                                'end'         => $opt['end'],
                                'widget'      => $widgetId];

            $graph = PluginMydashboardBarChart::launchHorizontalGraph($graph_datas, $graph_criterias);

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => true,
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
            while ($data = $DB->fetchArray($results)) {
               $percent    = round($data['Percent'] ?? 0, 2);
               $tabnames[] = $data['Age']; //" (".$percent."%)";
               $tabage[]   = $data['Total'];
            }

            $widget = new PluginMydashboardHtml();
            $widget->setWidgetTitle((($isDebug) ? "35 " : "") . __("Age of tickets", "mydashboard"));
            $dataLineset = json_encode($tabage);
            $labelsLine  = json_encode($tabnames);

            $title = __("Age of tickets", "mydashboard");
            $widget->setWidgetTitle((($isDebug) ? "35 " : "") . $title);

            $colors = PluginMydashboardColor::getColors(5);
            //            $colors          = ['rgb(32, 142, 61)', 'rgb(255, 247, 69)', 'rgb(255, 165, 0)', 'rgb(237, 89, 83)', 'rgb(237, 35, 28)'];
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
               while ($data = $DB->fetchArray($result)) {
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
               while ($data = $DB->fetchArray($result)) {
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
               $moreTickets = [];
               foreach (self::getAllMoreTicketStatus() as $id => $names) {
                  $moreTickets[] = $id;
               }
               $moreTicketToShow = '';
               if (count($moreTickets) > 0) {
                  $moreTicketToShow = ' AND `glpi_plugin_moreticket_waitingtickets`.`plugin_moreticket_waitingtypes_id`  IN (' . implode(',', $moreTickets) . ')';
               }
               $query_moreticket = "SELECT COUNT(*) AS Total,   
                         `glpi_plugin_moreticket_waitingtickets`.`plugin_moreticket_waitingtypes_id` AS status,
                         `glpi_plugin_moreticket_waitingtypes`.`completename` AS name
                          FROM `glpi_plugin_moreticket_waitingtickets`
                          LEFT JOIN `glpi_tickets` ON `glpi_tickets`.`id` = `glpi_plugin_moreticket_waitingtickets`.`tickets_id`
                          LEFT JOIN `glpi_plugin_moreticket_waitingtypes` ON `glpi_plugin_moreticket_waitingtickets`.`plugin_moreticket_waitingtypes_id`=`glpi_plugin_moreticket_waitingtypes`.`id`
                          WHERE `glpi_tickets`.`status` = " . CommonITILObject::WAITING . " 
                          AND `glpi_plugin_moreticket_waitingtickets`.`date_end_suspension` IS NULL 
                          AND `glpi_plugin_moreticket_waitingtickets`.`id` = (SELECT MAX(`id`) FROM glpi_plugin_moreticket_waitingtickets WHERE `glpi_tickets`.`id` = `glpi_plugin_moreticket_waitingtickets`.`tickets_id`)
                          AND $is_deleted
                          $type_criteria
                          $technician_groups_criteria
                          $moreTicketToShow
                          $entities_criteria
                          GROUP BY status";

               $result_more_ticket = $DB->query($query_moreticket);
               $rows               = $DB->numrows($result_more_ticket);
               if ($rows) {
                  while ($ticket = $DB->fetchAssoc($result_more_ticket)) {
                     //foreach (self::getAllMoreTicketStatus() as $value => $names) {
                     //   if ($ticket['status'] == $value) {
                     $datas[]       = $ticket['Total'];
                     $name_status[] = $ticket['name'];
                     $tabstatus[]   = 'moreticket_' . $ticket['status'];
                     //}
                     //}
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
            $name      = 'NumberOfOpenedTicketAndAverageSatisfactionPerTrimester';
            $criterias = [];
            $params    = ["preferences" => $this->preferences,
                          "criterias"   => $criterias,
                          "opt"         => $opt];
            $options   = PluginMydashboardHelper::manageCriterias($params);

            $opt                 = $options['opt'];
            $crit                = $options['crit'];
            $is_deleted          = "`glpi_tickets`.`is_deleted` = 0";
            $opened_tickets_data = [];
            $satisfaction_data   = [];
            $tabnames            = [];
            $starting_year       = date('Y', strtotime('-2 year'));
            $ending_year         = date('Y');
            for ($starting_year; $starting_year <= $ending_year; $starting_year++) {
               // Checking T1
               array_push($tabnames, __('Trimester 1', 'mydashboard') . ' ' . $starting_year);
               // Number of tickets opened
               $query_openedTicketT1 = "SELECT count(MONTH(`glpi_tickets`.`date`)) FROM `glpi_tickets` 
                                        WHERE $is_deleted
                                        AND `glpi_tickets`.`date` between '$starting_year-01-01' AND '$starting_year-03-31'";
               $result               = $DB->query($query_openedTicketT1);
               $dataT1               = $DB->fetchArray($result);
               array_push($opened_tickets_data, $dataT1[0]);
               // Average Satisfaction
               $query_satisfactionT1 = "SELECT AVG(satisfaction)
                                        FROM `glpi_tickets` INNER JOIN `glpi_ticketsatisfactions` ON `glpi_tickets`.`id` = `glpi_ticketsatisfactions`.`tickets_id` 
                                        WHERE `glpi_tickets`.`is_deleted` = 0 
                                        AND `glpi_tickets`.`date` between '$starting_year-01-01' AND '$starting_year-03-31'";
               $result               = $DB->query($query_satisfactionT1);
               $data_satisfactionT1  = $DB->fetchArray($result);
               array_push($satisfaction_data, round($data_satisfactionT1[0] ?? 0, 2));
               // Checking T2

               array_push($tabnames, __('Trimester 2', 'mydashboard') . ' ' . $starting_year);
               $query_openedTicketT2 = "SELECT count(MONTH(`glpi_tickets`.`date`)) FROM `glpi_tickets` 
                                        WHERE $is_deleted
                                        AND `glpi_tickets`.`date` between '$starting_year-04-01' AND '$starting_year-06-30'";
               $result               = $DB->query($query_openedTicketT2);
               $dataT2               = $DB->fetchArray($result);
               array_push($opened_tickets_data, $dataT2[0]);
               // Average Satisfaction
               $query_satisfactionT2 = "SELECT AVG(satisfaction)
                                        FROM `glpi_tickets` INNER JOIN `glpi_ticketsatisfactions` ON `glpi_tickets`.`id` = `glpi_ticketsatisfactions`.`tickets_id` 
                                        WHERE `glpi_tickets`.`is_deleted` = 0 
                                        AND `glpi_tickets`.`date` between '$starting_year-04-01' AND '$starting_year-06-30'";
               $result               = $DB->query($query_satisfactionT2);
               $data_satisfactionT2  = $DB->fetchArray($result);
               array_push($satisfaction_data, round($data_satisfactionT2[0] ?? 0, 2));
               // Checking T3
               array_push($tabnames, __('Trimester 3', 'mydashboard') . ' ' . $starting_year);
               $query_openedTicketT3 = "SELECT count(MONTH(`glpi_tickets`.`date`)) FROM `glpi_tickets` 
                                        WHERE $is_deleted
                                        AND `glpi_tickets`.`date` between '$starting_year-06-01' AND '$starting_year-09-30'";
               $result               = $DB->query($query_openedTicketT3);
               $dataT3               = $DB->fetchArray($result);
               array_push($opened_tickets_data, $dataT3[0]);
               // Average Satisfaction
               $query_satisfactionT3 = "SELECT AVG(satisfaction)
                                        FROM `glpi_tickets` INNER JOIN `glpi_ticketsatisfactions` ON `glpi_tickets`.`id` = `glpi_ticketsatisfactions`.`tickets_id` 
                                        WHERE `glpi_tickets`.`is_deleted` = 0 
                                        AND `glpi_tickets`.`date` between '$starting_year-06-01' AND '$starting_year-09-30'";
               $result               = $DB->query($query_satisfactionT3);
               $data_satisfactionT3  = $DB->fetchArray($result);
               array_push($satisfaction_data, round($data_satisfactionT3[0] ?? 0, 2));
               // Checking T4
               array_push($tabnames, __('Trimester 4', 'mydashboard') . ' ' . $starting_year);
               $query_openedTicketT4 = "SELECT count(MONTH(`glpi_tickets`.`date`)) FROM `glpi_tickets` 
                                        WHERE $is_deleted
                                        AND `glpi_tickets`.`date` between '$starting_year-09-01' AND '$starting_year-12-31'";
               $result               = $DB->query($query_openedTicketT4);
               $dataT4               = $DB->fetchArray($result);
               array_push($opened_tickets_data, $dataT4[0]);
               // Average Satisfaction
               $query_satisfactionT4 = "SELECT AVG(satisfaction)
                                        FROM `glpi_tickets` INNER JOIN `glpi_ticketsatisfactions` ON `glpi_tickets`.`id` = `glpi_ticketsatisfactions`.`tickets_id` 
                                        WHERE `glpi_tickets`.`is_deleted` = 0 
                                        AND `glpi_tickets`.`date` between '$starting_year-09-01' AND '$starting_year-12-31'";
               $result               = $DB->query($query_satisfactionT4);
               $data_satisfactionT4  = $DB->fetchArray($result);
               array_push($satisfaction_data, round($data_satisfactionT4[0] ?? 0, 2));
            }

            $widget = new PluginMydashboardHtml();
            $title  = __("Number of opened ticket and average satisfaction per trimester", "mydashboard");
            $widget->setWidgetTitle((($isDebug) ? "38 " : "") . $title);
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

            $titleOpenedTicket       = __("Opened tickets", "mydashboard");
            $titleSatisfactionTicket = __("Average Satisfaction", "mydashboard");
            $labels                  = json_encode($tabnames);

            $colors = PluginMydashboardColor::getColors(1, 0);
            //            $backgroundColor = json_encode($colors);

            $datasets[] =
               ['type'            => 'bar',
                'data'            => $opened_tickets_data,
                'label'           => $titleOpenedTicket,
                'backgroundColor' => $colors,
                'yAxisID'         => 'right-y-axis',
                'order'           => 2
               ];

            $datasets[]  =
               ['type'        => 'line',
                'data'        => $satisfaction_data,
                'label'       => $titleSatisfactionTicket,
                'borderColor' => '#ff7f0e',
                'fill'        => false,
                'lineTension' => '0.1',
                'yAxisID'     => 'left-y-axis',
                'order'       => 1
               ];
            $max_left    = 10;
            $graph_datas = ['name'     => $name,
                            'ids'      => json_encode([]),
                            'data'     => json_encode($datasets),
                            'labels'   => $labels,
                            'label'    => $title,
                            'max_left' => $max_left];

            $graph = PluginMydashboardBarChart::launchMultipleGraphWithMultipleAxis($graph_datas, []);
            $widget->setWidgetHtmlContent($graph);
            return $widget;
            break;

            //         case $this->getType() . "39":
            //
            //            $name = 'ResponsivenessRollingPendingByYear';
            //            $criterias = ['requesters_groups_id', 'year', 'type'];
            //            $params    = ["preferences" => $this->preferences,
            //               "criterias"   => $criterias,
            //               "opt"         => $opt];
            //
            //            $options   = PluginMydashboardHelper::manageCriterias($params);
            //
            //            $crit = $options['crit'];
            //            $opt  = $options['opt'];
            //
            //            $type_criteria             = $crit['type'];
            //            $requester_groups_criteria = $crit['requesters_groups_id'];
            //            $is_deleted                = "`glpi_tickets`.`is_deleted` = 0";
            //            $status                    = " AND `glpi_tickets`.`status` IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")";
            //
            //            $datasets                           = [];
            //
            //
            //            $currentyear = date("Y");
            //            $currentmonth = date("m");
            //
            //            if (isset($opt["year"]) && $opt["year"] > 0) {
            //               $currentyear = $opt["year"];
            //            }
            //
            //            $previousyear = $currentyear - 1;
            //
            //
            //            $datesTab  = self::getAllMonthAndYear($currentyear,$currentmonth, $previousyear);
            //
            //
            //            $query_tickets = "SELECT t1.Total as Total, t1.monthname as Monthname, t1.month FROM
            //                              (SELECT  DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m') as month,
            //                               DATE_FORMAT(`glpi_tickets`.`date`, '%b %Y') as monthname ,
            //                               COUNT(*) Total FROM `glpi_tickets`  WHERE {$is_deleted} {$type_criteria}
            //                                 {$requester_groups_criteria}  {$status}
            //                                AND `glpi_tickets`.`date` >=  '$previousyear-$currentmonth-01 00:00:01'
            //                                  AND `glpi_tickets`.`date` <= '$currentyear-$currentmonth-01'
            //                                    AND `glpi_tickets`.`solve_delay_stat` <=  86400 GROUP BY month) t1
            //                                         ";
            //
            //            $results                            = $DB->query($query_tickets);
            //            $nbResults                          = $DB->numrows($results);
            //            $tabTicketsLessThanOneDay           = [];
            //            $tabTicketsLessThanOneDay['month_name'] = [];
            //            $tabTicketsLessThanOneDay['total'] = [];
            //
            //            $i = 0;
            //
            //            if ($nbResults) {
            //               while ($data = $DB->fetchArray($results)) {
            //                  $i++;
            //                  foreach ($datesTab as $datePeriod) {
            //                     if (!array_key_exists('month', $tabTicketsLessThanOneDay)) {
            //                        if (!in_array($data['Monthname'],$tabTicketsLessThanOneDay['month_name']) && !in_array($datePeriod, $tabTicketsLessThanOneDay['month_name'])) {
            //                           if ($data['Monthname'] !== $datePeriod)  {
            //                              $tabTicketsLessThanOneDay['month_name'][] = $datePeriod;
            //                              $tabTicketsLessThanOneDay['total'][] = 0;
            //
            //                           }
            //                           else {
            //                              $tabTicketsLessThanOneDay['month_name'][] = $data['Monthname'];
            //                              $tabTicketsLessThanOneDay['total'][] = $data['Total'];
            //                           }
            //                        }
            //                     }
            //                  }
            //                  if ( $i  == $nbResults) {
            //                     foreach ($datesTab as $datePeriod) {
            //                        if (!in_array($datePeriod, $tabTicketsLessThanOneDay['month_name'])) {
            //                           $tabTicketsLessThanOneDay['month_name'][] = $datePeriod;
            //                           $tabTicketsLessThanOneDay['total'][] = 0;
            //                        }
            //                     }
            //                  }
            //               }
            //            }
            //
            //            $query_tickets2 = "SELECT t1.Total as Total, t1.monthname as Monthname, t1.month FROM
            //                              (SELECT  DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m') as month,
            //                               DATE_FORMAT(`glpi_tickets`.`date`, '%b %Y') as monthname ,
            //                               COUNT(*) Total FROM `glpi_tickets`  WHERE {$is_deleted} {$type_criteria}
            //                                {$requester_groups_criteria}  {$status}
            //                                AND `glpi_tickets`.`date` >=  '$previousyear-$currentmonth-01 00:00:01'
            //                                  AND `glpi_tickets`.`date` <= '$currentyear-$currentmonth-01'
            //                                    AND `glpi_tickets`.`solve_delay_stat` >=  86400 AND  `glpi_tickets`.`solve_delay_stat` <=  604800 GROUP BY month) t1
            //                                         ";
            //
            //            $results = $DB->query($query_tickets2);
            //            $nbResults = $DB->numrows($results);
            //            $tabTicketsBetweenOneDayAndOneWeek = [];
            //            $tabTicketsBetweenOneDayAndOneWeek['month_name'] = [];
            //            $tabTicketsBetweenOneDayAndOneWeek['total'] = [];
            //            $i = 0;
            //
            //            if ($nbResults) {
            //               while ($data = $DB->fetchArray($results)) {
            //                  $i++;
            //                  foreach ($datesTab as $datePeriod) {
            //                     if (!array_key_exists('month', $tabTicketsBetweenOneDayAndOneWeek)) {
            //                        if (!in_array($data['Monthname'],$tabTicketsBetweenOneDayAndOneWeek['month_name']) && !in_array($datePeriod, $tabTicketsBetweenOneDayAndOneWeek['month_name'])) {
            //                           if ($data['Monthname'] !== $datePeriod)  {
            //                              $tabTicketsBetweenOneDayAndOneWeek['month_name'][] = $datePeriod;
            //                              $tabTicketsBetweenOneDayAndOneWeek['total'][] = 0;
            //
            //                           }
            //                           else {
            //                              $tabTicketsBetweenOneDayAndOneWeek['month_name'][] = $data['Monthname'];
            //                              $tabTicketsBetweenOneDayAndOneWeek['total'][] = $data['Total'];
            //                           }
            //                        }
            //                     }
            //                  }
            //                  if ( $i  == $nbResults) {
            //                     foreach ($datesTab as $datePeriod) {
            //                        if (!in_array($datePeriod, $tabTicketsBetweenOneDayAndOneWeek['month_name'])) {
            //                           $tabTicketsBetweenOneDayAndOneWeek['month_name'][] = $datePeriod;
            //                           $tabTicketsBetweenOneDayAndOneWeek['total'][] = 0;
            //                        }
            //                     }
            //                  }
            //               }
            //            }
            //
            //            $query_tickets3 = "SELECT t1.Total as Total, t1.monthname as Monthname, t1.month FROM
            //                              (SELECT  DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m') as month,
            //                               DATE_FORMAT(`glpi_tickets`.`date`, '%b %Y') as monthname ,
            //                               COUNT(*) Total FROM `glpi_tickets`  WHERE {$is_deleted} {$type_criteria}
            //                                 {$requester_groups_criteria}  {$status}
            //                                AND `glpi_tickets`.`date` >=  '$previousyear-$currentmonth-01 00:00:01'
            //                                  AND `glpi_tickets`.`date` <= '$currentyear-$currentmonth-01'
            //                                    AND `glpi_tickets`.`solve_delay_stat` >=  604800 GROUP BY month) t1
            //                                         ";
            //
            //            $results = $DB->query($query_tickets3);
            //            $nbResults = $DB->numrows($results);
            //            $tabTicketsMoreThanOneWeek= [];
            //            $tabTicketsMoreThanOneWeek['month_name'] = [];
            //            $tabTicketsMoreThanOneWeek['total'] = [];
            //            $i = 0;
            //
            //            if ($nbResults) {
            //               while ($data = $DB->fetchArray($results)) {
            //                  $i++;
            //                  foreach ($datesTab as $datePeriod) {
            //                     if (!array_key_exists('month', $tabTicketsMoreThanOneWeek)) {
            //                        if (!in_array($data['Monthname'],$tabTicketsMoreThanOneWeek['month_name']) && !in_array($datePeriod, $tabTicketsMoreThanOneWeek['month_name'])) {
            //                           if ($data['Monthname'] !== $datePeriod)  {
            //                              $tabTicketsMoreThanOneWeek['month_name'][] = $datePeriod;
            //                              $tabTicketsMoreThanOneWeek['total'][] = 0;
            //
            //                           }
            //                           else {
            //                              $tabTicketsMoreThanOneWeek['month_name'][] = $data['Monthname'];
            //                              $tabTicketsMoreThanOneWeek['total'][] = $data['Total'];
            //                           }
            //                        }
            //                     }
            //                  }
            //                  if ( $i  == $nbResults) {
            //                     foreach ($datesTab as $datePeriod) {
            //                        if (!in_array($datePeriod, $tabTicketsMoreThanOneWeek['month_name'])) {
            //                           $tabTicketsMoreThanOneWeek['month_name'][] = $datePeriod;
            //                           $tabTicketsMoreThanOneWeek['total'][] = 0;
            //                        }
            //                     }
            //                  }
            //               }
            //            }
            //
            //
            //            $datesTab  = self::getAllMonthAndYear($currentyear,$currentmonth, $previousyear, true);
            //
            //            foreach ($datesTab as $dateTab) {
            //               $year  = strstr($dateTab, '-', true);
            //               $month = strstr($dateTab, '-');
            //               $month = str_replace('-', '', $month);
            //               $monthDays[$dateTab] = self::getAllFirstDayOfWeeksInAMonth($year, $month);
            //            }
            //
            //            $parcCat = 163;
            //
            //            $tabTotalRunningByDayAndMonth               = [];
            //            $tabTotalRunningInfraByDayAndMonth          = [];
            //            $computerCat                                = self::getCategorySonsOf($parcCat);
            //
            //            foreach($monthDays as $key => $month ) {
            //               foreach($month as $day) {
            //                  $query_average_total = "SELECT COUNT(`glpi_tickets`.`id`) AS Total FROM `glpi_tickets`
            //                                      WHERE {$is_deleted} {$type_criteria}
            //                                         {$requester_groups_criteria}
            //                                        AND `glpi_tickets`.date<=date_add('{$day}', interval 3*7 DAY)
            //                                        AND (`glpi_tickets`.solvedate>=date_add('{$day}', interval 3*7 DAY) OR `glpi_tickets`.solvedate is null)
            //                                    ";
            //
            //
            //                  $results = $DB->query($query_average_total);
            //                  $nbResults = $DB->numrows($results);
            //
            //
            //                  if ($nbResults) {
            //                     while ($data = $DB->fetchArray($results)) {
            //                        if (strpos($day, $key) !== false) {
            //                           $tabTotalRunningByDayAndMonth[$key][$day]['total'] = $data['Total'];
            //                        }
            //                     }
            //                  }
            //
            //                  $query_average_total_infra = "SELECT COUNT(`glpi_tickets`.`id`) AS Total FROM `glpi_tickets`
            //                                WHERE {$is_deleted} {$type_criteria}
            //                                   {$requester_groups_criteria}
            //                                  AND `glpi_tickets`.date <= date_add('{$day}', interval 3*7 DAY)
            //                                  AND (`glpi_tickets`.solvedate >= date_add('{$day}', interval 3*7 DAY) OR `glpi_tickets`.solvedate is null)
            //                                  AND {$computerCat};
            //                              ";
            //
            //
            //                  $results = $DB->query($query_average_total_infra);
            //                  $nbResults = $DB->numrows($results);
            //
            //                  if ($nbResults) {
            //                     while ($data = $DB->fetchArray($results)) {
            //                        if (strpos($day, $key) !== false) {
            //                           $tabTotalRunningInfraByDayAndMonth[$key][$day]['total'] = $data['Total'];
            //                        }
            //                     }
            //                  }
            //               }
            //            }
            //
            //            $tabTotalRunningByMonth = [];
            //            //Reformat datas by calculate total by month
            //            foreach ($tabTotalRunningByDayAndMonth as $month => $runningMouth) {
            //               $i = 0;
            //               foreach ($runningMouth as $day => $value) {
            //                  $i++;
            //                  if(!isset($tabTotalRunningByMonth[$month]['total'])) {
            //                     $tabTotalRunningByMonth[$month]['total'] = $value['total'];
            //                     $tabTotalRunningByMonth[$month]['nbWeek'] = $i;
            //                  }else{
            //                     $tabTotalRunningByMonth[$month]['total'] += $value['total'];
            //                     $tabTotalRunningByMonth[$month]['nbWeek'] = $i;
            //                  }
            //               }
            //            }
            //            $tabAverageTotalRunningByMonth = [];
            //            foreach($tabTotalRunningByMonth as  $runningMouth) {
            //               $tabAverageTotalRunningByMonth[] = $runningMouth['total'] / $runningMouth['nbWeek'];
            //            }
            //
            //            $tabTotalInfraRunningByMonth = [];
            //            //Reformat datas by calculate total by month
            //            foreach ($tabTotalRunningInfraByDayAndMonth as $month => $runningMouth) {
            //               $i = 0;
            //               foreach ($runningMouth as $day => $value) {
            //                  $i++;
            //                  if(!isset($tabTotalInfraRunningByMonth[$month]['total'])){
            //                     $tabTotalInfraRunningByMonth[$month]['total'] = $value['total'];
            //                     $tabTotalInfraRunningByMonth[$month]['nbWeek'] = $i;
            //                  }else{
            //                     $tabTotalInfraRunningByMonth[$month]['total'] += $value['total'];
            //                     $tabTotalInfraRunningByMonth[$month]['nbWeek'] = $i;
            //                  }
            //               }
            //            }
            //
            //            $tabAverageTotalInfraRunningByMonth = [];
            //            foreach($tabTotalInfraRunningByMonth as  $runningMouth) {
            //               $tabAverageTotalInfraRunningByMonth[] = $runningMouth['total'] / $runningMouth['nbWeek'];
            //            }
            //
            //
            //            $widget = new PluginMydashboardHtml();
            //            $title = __("Responsiveness over 12 rolling and pending by month", "mydashboard");
            //            $widget->setWidgetTitle((($isDebug) ? "39 " : "") . $title);
            //            $widget->toggleWidgetRefresh();
            //
            //            $labels = json_encode($datesTab);
            //
            //            $max = '';
            //            $max2 = '';
            //            $max3 = '';
            //            $max_tab = [];
            //
            //            if(!empty($tabTicketsLessThanOneDay['total'])) {
            //               array_push($max_tab, $max = max($tabTicketsLessThanOneDay['total']) + 100);
            //            }
            //
            //            if(!empty($tabTicketsBetweenOneDayAndOneWeek['total'])) {
            //               array_push($max_tab, $max = max($tabTicketsBetweenOneDayAndOneWeek['total']) + 100);
            //            }
            //
            //            if(!empty($tabTicketsMoreThanOneWeek['total'])) {
            //               array_push($max_tab, $max = max($tabTicketsMoreThanOneWeek['total']) + 100);
            //            }
            //
            //            $maxFinal = max($max_tab);
            //
            //
            //            $datasets[] =
            //               ['type' => 'line',
            //                  'data' => $tabAverageTotalRunningByMonth,
            //                  'label' => __('Sum of pending by month', "mydashboard"),
            //                  'borderColor' => '#00BFFF',
            //                  'fill' => false,
            //                  'lineTension' => '0.1',
            //                  'yAxisID' => 'left-y-axis'
            //               ];
            //
            //
            //            $datasets[] =
            //               ['type' => 'line',
            //                  'data' => $tabAverageTotalInfraRunningByMonth,
            //                  'label' => __('Sum of pending_infra by month', "mydashboard"),
            //                  'borderColor' => '#787878',
            //                  'fill' => false,
            //                  'lineTension' => '0.1',
            //                  'yAxisID' => 'left-y-axis'
            //               ];
            //
            //
            //
            //            $datasets[] =
            //               [
            //                  "type" => "bar",
            //                  "data" => $tabTicketsLessThanOneDay['total'],
            //                  "label" => __('sum of less_24_h', "mydashboard"),
            //                  'backgroundColor' => '#BBD4F9',
            //                  'yAxisID' => 'bar-y-axis'
            //               ];
            //
            //            $datasets[] =
            //               [
            //                  "type" => "bar",
            //                  "data" => $tabTicketsBetweenOneDayAndOneWeek['total'],
            //                  "label" => __('sum of less_1_week', "mydashboard"),
            //                  'backgroundColor' => '#2B68C4',
            //                  'yAxisID' => 'bar-y-axis'
            //               ];
            //
            //            $datasets[] =
            //               [
            //                  "type" => "bar",
            //                  "data" => $tabTicketsMoreThanOneWeek['total'],
            //                  "label" => __('sum of more_1_week', "mydashboard"),
            //                  'backgroundColor' => '#033A5F',
            //                  'yAxisID' => 'bar-y-axis'
            //               ];
            //
            //            $graph_datas = ['name' => $name,
            //               'ids' => json_encode([]),
            //               'data' => json_encode($datasets),
            //               'labels' => $labels,
            //               'label' => $title,
            //            ];
            //
            //
            //            $graph = PluginMydashboardBarChart::launchMultipleAxisAndGroupableBar($graph_datas, [], $maxFinal);
            //
            //            $params = ["widgetId" => $widgetId,
            //               "name" => $name,
            //               "onsubmit" => true,
            //               "opt" => $opt,
            //               "criterias" => $criterias,
            //               "export" => true,
            //               "canvas" => true,
            //               "nb" => 1];
            //            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));
            //
            //            $widget->setWidgetHtmlContent(
            //               $graph
            //            );

            return $widget;
            break;

         case $this->getType() . "40":

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

            $query = "SELECT DATE_FORMAT(`glpi_tickets`.`date`, '%Y') AS year, 
                        DATE_FORMAT(`glpi_tickets`.`date`, '%Y') AS yearname
                        FROM `glpi_tickets`
                        WHERE $is_deleted ";
            $query .= $entities_criteria . " 
                     GROUP BY DATE_FORMAT(`glpi_tickets`.`date`, '%Y')";

            $results = $DB->query($query);

            while ($data = $DB->fetchArray($results)) {

               $year = $data['year'];

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

               while ($data_1 = $DB->fetchArray($results_1)) {
                  $tabdata[$data_1['requesttypes_id']][$year] = $data_1['count'];
                  $tabnames[$data_1['requesttypes_id']]       = $data_1['namerequest'];
                  $i++;
               }

               $tabyears[] = $data['yearname'];


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
                   'backgroundColor' => isset($palette[$k]) ? $palette[$k] : "#CCC",
                  ];
            }

            $widget = new PluginMydashboardHtml();
            $title  = __("Tickets request sources evolution", "mydashboard");
            $widget->setWidgetComment(__("Evolution of tickets request sources types by year", "mydashboard"));
            $widget->setWidgetTitle((($isDebug) ? "40 " : "") . $title);
            $widget->toggleWidgetRefresh();

            $name = 'RequestTypeEvolutionLineChart';

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


         case $this->getType() . "41":

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

            $query = "SELECT DATE_FORMAT(`glpi_tickets`.`date`, '%Y') AS year, 
                        DATE_FORMAT(`glpi_tickets`.`date`, '%Y') AS yearname
                        FROM `glpi_tickets`
                        WHERE $is_deleted ";
            $query .= $entities_criteria . " 
                     GROUP BY DATE_FORMAT(`glpi_tickets`.`date`, '%Y')";

            $results = $DB->query($query);

            while ($data = $DB->fetchArray($results)) {

               $year = $data['year'];

               $query_1 = "SELECT COUNT(`solutiontypes_id`) as count,
                                 `glpi_solutiontypes`.`name`as namesolution,
                                 `glpi_itilsolutions`.`solutiontypes_id`
                     FROM `glpi_itilsolutions`
                     LEFT JOIN `glpi_tickets` ON (`glpi_itilsolutions`.`items_id` = `glpi_tickets`.`id`
                         AND `glpi_itilsolutions`.`itemType` = 'Ticket')
                     LEFT JOIN `glpi_solutiontypes` ON (`glpi_itilsolutions`.`solutiontypes_id` = `glpi_solutiontypes`.`id`)
                     WHERE $is_deleted " . $entities_criteria . "
                     AND (`glpi_tickets`.`date` <= '$year-12-31 23:59:59') 
                     AND (`glpi_tickets`.`date` > ADDDATE('$year-01-01 00:00:00' , INTERVAL 1 DAY))
                     GROUP BY `solutiontypes_id`";

               $results_1 = $DB->query($query_1);

               while ($data_1 = $DB->fetchArray($results_1)) {
                  $tabdata[$data_1['solutiontypes_id']][$year] = $data_1['count'];
                  $tabnames[$data_1['solutiontypes_id']]       = $data_1['namesolution'];
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
            $palette    = PluginMydashboardColor::getColors($i, 0);
            $datasets   = [];

            foreach ($tabdata as $k => $v) {
               $datasets[] =
                  ['data'            => array_values($v),
                   'label'           => ($tabnames[$k] == NULL) ? __('None') : $tabnames[$k],
                   'backgroundColor' => isset($palette[$k]) ? $palette[$k] : "#CCC",
                  ];
            }

            $widget = new PluginMydashboardHtml();
            $title  = __("Tickets solution types evolution", "mydashboard");
            $widget->setWidgetComment(__("Evolution of solution types by year", "mydashboard"));
            $widget->setWidgetTitle((($isDebug) ? "41 " : "") . $title);
            $widget->toggleWidgetRefresh();

            $name = 'SolutionTypeEvolutionLineChart';

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

         case $this->getType() . "42" :
            $name = 'reportLineLifeTimeAndTakenAccountAverageByMonthHelpdesk';

            $lifetime           = __('Solve delay average (hour)', 'mydashboard');
            $taken_into_account = __('Take into account average (hour)', 'mydashboard');

            $criterias = ['entities_id',
                          'is_recursive',
                          'type',
                          'year',
                          'multiple_locations_id',
                          'technicians_groups_id'];

            $params = ["preferences" => $this->preferences,
                       "criterias"   => $criterias,
                       "opt"         => $opt];

            $options = PluginMydashboardHelper::manageCriterias($params);

            $opt  = $options['opt'];
            $crit = $options['crit'];

            $technician_group           = $opt['technicians_groups_id'];
            $technician_groups_criteria = $crit['technicians_groups_id'];

            $lifetime_avg_ticket = self::getLifetimeOrTakeIntoAccountTicketAverage($crit, $technician_groups_criteria);

            $months_t = Toolbox::getMonthsOfYearArray();
            $months   = [];
            foreach ($months_t as $key => $month) {
               $months[] = $month;
            }

            $dataset                         = [];
            $avg_lifetime_ticket_data        = [];
            $avg_takeintoaccount_ticket_data = [];

            foreach ($lifetime_avg_ticket as $avg_tickets_d) {
               if ($avg_tickets_d['nb'] > 0) {
                  $avg_lifetime_ticket_data []        = round(($avg_tickets_d['lifetime'] / $avg_tickets_d['nb']) ?? 0, 2);
                  $avg_takeintoaccount_ticket_data [] = round(($avg_tickets_d['takeintoaccount'] / $avg_tickets_d['nb']) ?? 0, 2);
               }
            }

            $avg_lifetime_ticket_data        = array_values($avg_lifetime_ticket_data);
            $avg_takeintoaccount_ticket_data = array_values($avg_takeintoaccount_ticket_data);

            $dataset[] = ["type"        => 'line',
                          "label"       => $lifetime,
                          "data"        => $avg_lifetime_ticket_data,
                          "borderColor" => PluginMydashboardColor::getColors(1, 0),
                          'fill'        => false,
                          'lineTension' => '0.1',
            ];

            $dataset[] = ["type"        => 'line',
                          "label"       => $taken_into_account,
                          "data"        => $avg_takeintoaccount_ticket_data,
                          "borderColor" => PluginMydashboardColor::getColors(1, 1),
                          'fill'        => false,
                          'lineTension' => '0.1',
            ];

            $widget = new PluginMydashboardHtml();
            $title  = __("Solve delay and take into account of tickets", "mydashboard");
            $widget->setWidgetTitle((($isDebug) ? "42 " : "") . $title);

            $widget->toggleWidgetRefresh();

            $dataLineset = json_encode($dataset);
            $labelsLine  = json_encode($months);
            $colors      = PluginMydashboardColor::getColors(2, 0);
            $graph_datas = ['name'            => $name,
                            'ids'             => json_encode([]),
                            'data'            => $dataLineset,
                            'labels'          => $labelsLine,
                            'label'           => $title,
                            'backgroundColor' => $colors];


            $graph = PluginMydashboardBarChart::launchMultipleGraph($graph_datas, []);

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => true,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => count($dataset)];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));
            $widget->setWidgetHtmlContent($graph);

            return $widget;

            break;

         default:
            break;
      }
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

      $techlist    = [];
      $limit_query = "";
      $limit       = isset($opt['limit']) ? $opt['limit'] : 10;
      if ($limit > 0) {
         $limit_query = "LIMIT $limit";
      }
      if (count($selected_group) > 0) {
         $groups             = implode(",", $selected_group);
         $query_group_member = "SELECT `glpi_groups_users`.`users_id`"
                               . "FROM `glpi_groups_users` "
                               . "LEFT JOIN `glpi_groups` ON (`glpi_groups_users`.`groups_id` = `glpi_groups`.`id`) "
                               . "WHERE `glpi_groups_users`.`groups_id` IN (" . $groups . ") AND `glpi_groups`.`is_assign` = 1 "
                               . " GROUP BY `glpi_groups_users`.`users_id`
                               $limit_query";

         $result_gu = $DB->query($query_group_member);

         while ($data = $DB->fetchAssoc($result_gu)) {
            $techlist[] = $data['users_id'];
         }
      } else {

         $query_group_member = "SELECT  `glpi_tickettasks`.`users_id_tech`"
                               . "FROM `glpi_tickettasks` "
                               . " GROUP BY `glpi_tickettasks`.`users_id_tech`
                               $limit_query";

         $result_gu = $DB->query($query_group_member);

         while ($data = $DB->fetchAssoc($result_gu)) {
            $techlist[] = $data['users_id_tech'];
         }
      }

      $current_month = date("m");
      foreach ($months as $key => $month) {

         if ($key > $current_month && $year == date("Y")) {
            break;
         }

         //         $next = $key + 1;

         $month_tmp = $key;
         $nb_jours  = date("t", mktime(0, 0, 0, $key, 1, $year));

         if (strlen($key) == 1) {
            $month_tmp = "0" . $month_tmp;
         }
         //         if (strlen($next) == 1) {
         //            $next = "0" . $next;
         //         }

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
            while ($data = $DB->fetchAssoc($result_ai_q)) {
               //               $time_per_tech[$techid][$key] += (self::TotalTpsPassesArrondis($data['actiontime_date'] / 3600 / 8));
               if ($data['actiontime_date'] > 0) {
                  if (isset($time_per_tech[$techid][$key])) {
                     $time_per_tech[$techid][$key] += round(($data['actiontime_date'] / 3600 / 8) ?? 0, 2);
                  } else {
                     $time_per_tech[$techid][$key] = round(($data['actiontime_date'] / 3600 / 8) ?? 0, 2);
                  }
               }
            }
            $time_per_tech[$techid][$key] = (self::TotalTpsPassesArrondis($time_per_tech[$techid][$key]));
         }

         if ($key == 0) {
            $year++;
         }
      }
      //drop 0 values
      foreach ($time_per_tech as $k => $v) {
         foreach ($v as $d => $n) {
            if ($n == 0) {
               $tickets_per_tech[$k][$d] = '';
            }
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

      $mois = intval(date('m', time()) - 1);
      $year = intval(date('Y', time()) - 1);

      if ($mois > 0) {
         $year = date("Y");
      }

      if (isset($params["year"])
          && $params["year"] > 0) {
         $year = $params["year"];
      }

      $type_criteria = "";
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

      $techlist    = [];
      $limit_query = "";
      $limit       = isset($params['limit']) ? $params['limit'] : 10;
      if ($limit > 0) {
         $limit_query = "LIMIT $limit";
      }
      if (count($selected_group) > 0) {
         $groups             = implode(",", $selected_group);
         $query_group_member = "SELECT `glpi_groups_users`.`users_id`"
                               . "FROM `glpi_groups_users` "
                               . "LEFT JOIN `glpi_groups` ON (`glpi_groups_users`.`groups_id` = `glpi_groups`.`id`) "
                               . "WHERE `glpi_groups_users`.`groups_id` IN (" . $groups . ") AND `glpi_groups`.`is_assign` = 1 "
                               . " GROUP BY `glpi_groups_users`.`users_id`
                               $limit_query";

         $result_gu = $DB->query($query_group_member);

         while ($data = $DB->fetchAssoc($result_gu)) {
            $techlist[] = $data['users_id'];
         }
      } else {

         $query_group_member = "SELECT  `glpi_tickettasks`.`users_id_tech`"
                               . "FROM `glpi_tickettasks` "
                               . " GROUP BY `glpi_tickettasks`.`users_id_tech`
                               $limit_query";

         $result_gu = $DB->query($query_group_member);

         while ($data = $DB->fetchAssoc($result_gu)) {
            $techlist[] = $data['users_id_tech'];
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
      //         while ($data = $DB->fetchAssoc($result_gu)) {
      //            $techlist[] = $data['users_id'];
      //         }
      //      }
      $current_month = date("m");
      foreach ($months as $key => $month) {

         if ($key > $current_month && $year == date("Y")) {
            break;
         }

         //         $next = $key + 1;

         $month_tmp = $key;
         $nb_jours  = date("t", mktime(0, 0, 0, $key, 1, $year));

         if (strlen($key) == 1) {
            $month_tmp = "0" . $month_tmp;
         }
         //         if (strlen($next) == 1) {
         //            $next = "0" . $next;
         //         }

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
                            . PluginMydashboardHelper::getSpecificEntityRestrict("glpi_tickets", $params)
                            . " $type_criteria ) ";
            $querym_ai   .= "GROUP BY DATE(`glpi_tickets`.`date`);
                        ";
            $result_ai_q = $DB->query($querym_ai);
            while ($data = $DB->fetchAssoc($result_ai_q)) {
               $tickets_per_tech[$techid][$key] += $data['nbtickets'];
            }
         }

         if ($key == 0) {
            $year++;
         }
      }
      //drop 0 values
      foreach ($tickets_per_tech as $k => $v) {
         foreach ($v as $d => $n) {
            if ($n == 0) {
               $tickets_per_tech[$k][$d] = '';
            }
         }
      }

      return $tickets_per_tech;
   }

   static function getAllMoreTicketStatus() {
      global $DB;

      $tabs   = [];
      $plugin = new Plugin();
      if ($plugin->isActivated('moreticket')) {
         $query  = "SELECT `glpi_plugin_moreticket_waitingtypes`.`completename` as name, 
                   `glpi_plugin_moreticket_waitingtypes`.`id` as id  FROM `glpi_plugin_moreticket_waitingtypes` ORDER BY id";
         $result = $DB->query($query);
         while ($type = $DB->fetchAssoc($result)) {
            $tabs[$type['id']] = $type['name'];
         }
      }
      return $tabs;
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

   private static function getCategorySonsOf($id) {
      $categories = getSonsOf("glpi_itilcategories", $id);

      if (count($categories) > 1) {
         $categories = " `glpi_tickets`.`itilcategories_id` IN  (" . implode(",", $categories) . ") ";
      } else {
         $categories = " `glpi_tickets`.`itilcategories_id` = " . implode(",", $categories);
      }
      return $categories;
   }

   function getAllMonthAndYear($currentYear, $currentMonth, $previousYear, $otherFormat = false) {

      $begin    = new DateTime($previousYear . '-' . $currentMonth . '-' . '01');
      $end      = new DateTime($currentYear . '-' . $currentMonth . '-' . '01');
      $period   = new DatePeriod($begin, new DateInterval('P1M'), $end);
      $datesTab = [];


      foreach ($period as $date) {
         if ($otherFormat) {
            array_push($datesTab, $date->format("Y-m"));
         } else {
            array_push($datesTab, $date->format("M Y"));
         }

      }
      return $datesTab;
   }

   function getAllFirstDayOfWeeksInAMonth($year, $month, $day = 'Monday', $daysError = 3) {
      $dateString = 'first ' . $day . ' of ' . $year . '-' . $month;

      $startDay    = new \DateTime($dateString);
      $datesString = [];

      if ($startDay->format('j') > $daysError) {
         $startDay->modify('- 7 days');
      }

      $days = array();

      while ($startDay->format('Y-m') <= $year . '-' . str_pad($month, 2, 0, STR_PAD_LEFT)) {
         $days[] = clone($startDay);
         $startDay->modify('+ 7 days');
      }

      foreach ($days as $day) {
         $datesString[] = $day->format('Y-m-d');
      }

      return $datesString;
   }

   /**
    * @param $params
    * @param $groups_id
    *
    * @return array
    * @throws \GlpitestSQLError
    */
   private static function getLifetimeOrTakeIntoAccountTicketAverage($params, $groups_id) {
      global $DB;

      $entities_criteria   = $params['entities_id'];
      $locations_criteria  = $params['multiple_locations_id'];
      $type_criteria       = "";
      $groups_sql_criteria = "";


      if (isset($params["type"]) && $params["type"] > 0) {
         $type_criteria = " AND `glpi_tickets`.`type` = '" . $params["type"] . "' ";
      }

      $tickets_helpdesk = [];
      $months           = Toolbox::getMonthsOfYearArray();

      $mois = intval(date('m', time()) - 1);
      $year = intval(date('Y', time()) - 1);

      if ($mois > 0) {
         $year = date("Y");
      }

      if (isset($params["year"]) && $params["year"] > 0) {
         $year = $params["year"];
      }

      if (!empty($params['multiple_locations_id'])) {
         $locations_criteria = " AND " . $params['multiple_locations_id'];
      }

      $current_month = date("m");
      foreach ($months as $key => $month) {
         $tickets_helpdesk[$key]['nb']              = 0;
         $tickets_helpdesk[$key]['lifetime']        = 0;
         $tickets_helpdesk[$key]['takeintoaccount'] = 0;

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
         $is_deleted         = " AND `glpi_tickets`.`is_deleted` = 0 ";
         $assign             = Group_Ticket::ASSIGN;
         $date               = "`glpi_tickets`.`date`";

         $queryavg = "SELECT COUNT(`glpi_tickets`.`id`) AS nbtickets, 
                             SUM(`glpi_tickets`.`solve_delay_stat` / 3600) as lifetime,
                             SUM(`glpi_tickets`.`takeintoaccount_delay_stat` / 3600) as takeintoaccount
                        FROM `glpi_tickets` 
                        INNER JOIN `glpi_groups_tickets` 
                        ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id`
                          AND `glpi_groups_tickets`.`type` = {$assign}) ";
         $queryavg .= "WHERE ";

         $queryavg .= "{$date} >= '{$month_deb_datetime}' 
                          AND {$date} <= '{$month_end_datetime}'
                          {$groups_sql_criteria} {$type_criteria} 
                          {$entities_criteria} {$locations_criteria} {$groups_id} {$is_deleted} ";

         $queryavg   .= "GROUP BY DATE(`glpi_tickets`.`date`);
                        ";
         $result_avg = $DB->query($queryavg);
         while ($data = $DB->fetchAssoc($result_avg)) {
            $tickets_helpdesk[$key]['takeintoaccount'] += $data['takeintoaccount'];
            $tickets_helpdesk[$key]['lifetime']        += $data['lifetime'];
            $tickets_helpdesk[$key]['nb']              += $data['nbtickets'];
         }
      }

      if ($key == 0) {
         $year++;
      }

      return $tickets_helpdesk;


   }
}
