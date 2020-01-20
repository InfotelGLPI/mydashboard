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
 * Class PluginMydashboardReports_Bar
 */
class PluginMydashboardReports_Bar extends CommonGLPI {

   private $options;
   private $pref;
   public static $reports = [1, 8, 15, 21, 23, 24, 35, 36, 37];

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

      $isDebug = $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE;

      $widgets = [
         __('Bar charts', "mydashboard") => [
            $this->getType() . "1"  => (($isDebug) ? "1 " : "") . __("Opened tickets backlog", "mydashboard") . "&nbsp;<i class='fas fa-chart-bar'></i>",
            $this->getType() . "8"  => (($isDebug) ? "8 " : "") . __("Process time by technicians by month", "mydashboard") . "&nbsp;<i class='fas fa-chart-bar'></i>",
            $this->getType() . "15" => (($isDebug) ? "15 " : "") . __("Top ten ticket categories by type of ticket", "mydashboard") . "&nbsp;<i class='fa fa-chart-bar'></i>",
            $this->getType() . "21" => (($isDebug) ? "21 " : "") . __("Number of tickets affected by technicians by month", "mydashboard") . "&nbsp;<i class='fas fa-chart-bar'></i>",
            $this->getType() . "23" => (($isDebug) ? "23 " : "") . __("Average real duration of treatment of the ticket", "mydashboard") . "&nbsp;<i class='fas fa-chart-bar'></i>",
            $this->getType() . "24" => (($isDebug) ? "24 " : "") . __("Top ten technicians (by tickets number)", "mydashboard") . "&nbsp;<i class='fas fa-chart-bar'></i>",
            $this->getType() . "35" => (($isDebug) ? "35 " : "") . __("Age of tickets", "mydashboard") . "&nbsp;<i class='fas fa-chart-bar'></i>",
            $this->getType() . "36" => (($isDebug) ? "36 " : "") . __("Number of opened tickets by priority", "mydashboard") . "&nbsp;<i class='fas fa-chart-bar'></i>",
            $this->getType() . "37" => (($isDebug) ? "37 " : "") . __("Stock of tickets by status", "mydashboard") . "&nbsp;<i class='fas fa-chart-bar'></i>",
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
                            . PluginMydashboardHelper::getSpecificEntityRestrict("glpi_tickets", $params)
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
