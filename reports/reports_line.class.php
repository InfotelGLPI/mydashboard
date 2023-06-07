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
 * Class PluginMydashboardReports_Line
 */
class PluginMydashboardReports_Line extends CommonGLPI
{
    private $options;
    private $pref;
    public static $reports = [6, 22, 34, 35, 43, 44, 45, 46, 47, 48];

    /**
     * PluginMydashboardReports_Line constructor.
     *
     * @param array $_options
     */
    public function __construct($_options = [])
    {
        $this->options = $_options;

    }

    /**
     * @return array
     */
    public function getWidgetsForItem()
    {
        $widgets = [
            PluginMydashboardMenu::$HELPDESK => [
            $this->getType() . "6"  => ["title"   => __("Tickets stock by month", "mydashboard"),
                                        "type"    => PluginMydashboardWidget::$LINE,
                                        "comment" => __("Sum of not solved tickets by month", "mydashboard")],
            $this->getType() . "22" => ["title"   => __("Number of opened and closed tickets by month", "mydashboard"),
                                        "type"    => PluginMydashboardWidget::$LINE,
                                        "comment" => ""],
            $this->getType() . "34" => ["title"   => __("Number of opened and resolved / closed tickets by month", "mydashboard"),
                                        "type"    => PluginMydashboardWidget::$LINE,
                                        "comment" => ""],
            $this->getType() . "35" => ["title"   => __("Number of opened, closed, unplanned tickets by month", "mydashboard"),
                                        "type"    => PluginMydashboardWidget::$LINE,
                                        "comment" => ""],
            $this->getType() . "43" => ["title"   => __("Number of tickets created each months", "mydashboard"),
                                        "type"    => PluginMydashboardWidget::$LINE,
                                        "comment" => ""],
            $this->getType() . "44" => ["title"   => __("Number of tickets created each week", "mydashboard"),
                                        "type"    => PluginMydashboardWidget::$LINE,
                                        "comment" => ""],
            $this->getType() . "45" => ["title"   => __("Number of tickets with validation refusal", "mydashboard"),
                                        "type"    => PluginMydashboardWidget::$LINE,
                                        "comment" => ""],
            $this->getType() . "46" => ["title"   => __("Number of tickets linked with problems", "mydashboard"),
                                        "type"    => PluginMydashboardWidget::$LINE,
                                        "comment" => ""],
            $this->getType() . "47" => ["title"   => __("Backlog tickets by week", "mydashboard"),
                                        "type"    => PluginMydashboardWidget::$LINE,
                                        "comment" => __("Number of in progress (not new and pending) tickets by week", "mydashboard")],
            $this->getType() . "48" => ["title"   => __("Monthly tickets in progress", "mydashboard"),
                                        "type"    => PluginMydashboardWidget::$LINE,
                                        "comment" => __("Number of open tickets in the month still in progress for each month", "mydashboard")],
            ]
        ];
        return $widgets;
    }


    /**
     * @param $widgetID
     *
     * @return false|mixed
     */
    public function getTitleForWidget($widgetID)
    {
        $widgets = $this->getWidgetsForItem();
        foreach ($widgets as $type => $list) {
            foreach ($list as $name => $widget) {
                if ($widgetID == $name) {
                    return $widget['title'];
                }
            }
        }
        return false;
    }

    /**
     * @param $widgetID
     *
     * @return false|mixed
     */
    public function getCommentForWidget($widgetID)
    {
        $widgets = $this->getWidgetsForItem();
        foreach ($widgets as $type => $list) {
            foreach ($list as $name => $widget) {
                if ($widgetID == $name) {
                    return $widget['comment'];
                }
            }
        }
        return false;
    }

    /**
     * @param       $widgetId
     * @param array $opt
     *
     * @return \PluginMydashboardHtml
     * @throws \GlpitestSQLError
     */
    public function getWidgetContentForItem($widgetId, $opt = [])
    {
        global $DB;
        $isDebug = $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE;

        $preference = new PluginMydashboardPreference();
        if (Session::getLoginUserID() !== false
            && !$preference->getFromDB(Session::getLoginUserID())) {
            $preference->initPreferences(Session::getLoginUserID());
        }
        $preference->getFromDB(Session::getLoginUserID());
        $preferences = $preference->fields;

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

                $params  = ["preferences" => $preferences,
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
                }
                //                else {
                //                    $tech_groups_crit = " AND `glpi_plugin_mydashboard_stocktickets`.`groups_id` = -1";
                //                }
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

                $nbtickets = __('Tickets number', 'mydashboard');
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
                    $tabnames[]  = $data['monthname'];
                    $i++;
                }

                $widget  = new PluginMydashboardHtml();
                $title   = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetComment($comment);
                $widget->setWidgetTitle((($isDebug) ? "6 " : "") . $title);
                $widget->toggleWidgetRefresh();

                $datasets[]  =
                    ['type'   => 'line',
                     'data'   => $tabdata,
                     'name'   => $nbtickets,
                     'smooth' => false
                    ];
                $dataLineset = json_encode($datasets);
                $labelsLine  = json_encode($tabnames);
                $graph_datas = ['title'   => $title,
                                'comment' => $comment,
                                'name'    => $name,
                                'ids'     => json_encode([]),
                                'data'    => $dataLineset,
                                'labels'  => $labelsLine];


                $graph = PluginMydashboardBarChart::launchGraph($graph_datas, []);

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
                $name      = 'TicketStatusBarLineChart';
                $onclick   = 0;
                $criterias = [];
                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() == 'central'
                ) {
                    $criterias = ['entities_id',
                                  'technicians_groups_id',
                                  'group_is_recursive',
                                  'requesters_groups_id',
                                  'is_recursive',
                                  'display_data',
                                  'technicians_id',
                                  'type',
                                  'locations_id'];
                    $onclick   = 1;
                } else {
                    $criterias = ['entities_id',
                                  'requesters_groups_id',
                                  'display_data',
                                  'type',
                                  'locations_id'];
                }

                $params  = ["preferences" => $preferences,
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
                $technicians_ids_crit = $opt['technicians_id'];

                $requester_groups_criteria = $crit['requesters_groups_id'];
                $requester_groups          = $opt['requesters_groups_id'];

                $technician_groups_criteria = $crit['technicians_groups_id'];
                $technician_group           = $opt['technicians_groups_id'];
                $technician_groups_ids      = is_array($opt['technicians_groups_id']) ? $opt['technicians_groups_id'] : [$opt['technicians_groups_id']];
                $tech_groups_crit           = "";
                if (count($opt['technicians_groups_id']) > 0) {
                    $tech_groups_crit = " AND `groups_id` IN (" . implode(",", $technician_groups_ids) . ")";
                }
                //                else {
                //                    $tech_groups_crit = " AND `glpi_plugin_mydashboard_stocktickets`.`groups_id` = -1";
                //                }

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

                if (isset($opt["display_data"]) && $opt['display_data'] == "YEAR") {
                    if (isset($opt["year"]) && $opt["year"] > 0) {
                        $currentyear = $opt["year"];
                    }
                    $date_crit        = "`glpi_plugin_mydashboard_stocktickets`.`date` between '$currentyear-01-01' AND ADDDATE('$currentyear-01-01', INTERVAL 1 YEAR)";
                    $date_crit_ticket = "`glpi_tickets`.`date` between '$currentyear-01-01' AND ADDDATE('$currentyear-01-01', INTERVAL 1 YEAR)";
                } else {
                    $end_year    = $opt['end_year'] ?? date("Y");
                    $end_month   = $opt['end_month'] ?? date("m");
                    $start_month = $opt['start_month'] ?? date('m');
                    $start_year  = $opt['start_year'] ?? date("Y");
                    //               if($start_month <= 0) {
                    //                  $start_month = 12 + $start_month;
                    //                  $start_year = $start_year -1 ;
                    //               }
                    if (strlen($start_month) == 1) {
                        $start_month = "0" . $start_month;
                    }
                    $nbdays           = date("t", mktime(0, 0, 0, $end_month, 1, $end_year));
                    $date_crit        = "`glpi_plugin_mydashboard_stocktickets`.`date` between '$start_year-$start_month-01' AND '$end_year-$end_month-$nbdays'";
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
                $tabdates            = [];
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
//                        list($year, $month) = explode('-', $data['month']);
                        $tabdates[0][] = $data['month']. '_progress';
                        $i++;
                    }
                }

                $is_deleted    = "`glpi_tickets`.`is_deleted` = 0";
                $q             = "SET lc_time_names = '" . $_SESSION['glpilanguage'] . "';";
                $r             = $DB->query($q);
                $query_tickets =
                    "SELECT DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m') as month," .
                    " DATE_FORMAT(`glpi_tickets`.`date`, '%b %Y') as monthname," .
                    " DATE_FORMAT(`glpi_tickets`.`date`, '%Y%m') AS monthnum, 
               count(MONTH(`glpi_tickets`.`date`))" .
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
                $q         = "SET lc_time_names = 'en_GB';";
                $r         = $DB->query($q);
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
                        $tabdates[1][]      = $data['month'] . '_opened';
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
                        $tabdates[2][] = $data['month'] . '_closed';


                        if ($month == date("m") && $year == date("Y")) {
                            $nbdays = date("t", mktime(0, 0, 0, $month, 1, $year));
                            //nbstock : cannot use tech or group criteria

                            $query_3 =
                                "SELECT COUNT(*) as count FROM `glpi_tickets`" .
                                //                        " $ticket_users_join".
                                " WHERE $is_deleted" .
                                " $technician_groups_criteria" .
                                " $entities_criteria" .
                                " $type_criteria" .
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
//                            $tabdates[0][] = 0;
                        }
                        $i++;
                    }
                }

                $widget  = new PluginMydashboardHtml();
                $title   = __("Number of opened and closed tickets by month", "mydashboard");
                $comment = "";
                $widget->setWidgetTitle((($isDebug) ? "22 " : "") . $title);
                $widget->toggleWidgetRefresh();

                $titleopened   = __("Opened tickets", "mydashboard");
                $titlesolved   = __("Closed tickets", "mydashboard");
                $titleprogress = __("Opened tickets backlog", "mydashboard");
                $labels        = json_encode($tabnames);

                $datasets[] =
                    ['type'   => 'line',
                     'data'   => $tabprogress,
                     'name'   => $titleprogress,
                     'smooth' => false
                    ];

                $datasets[] =
                    ["type" => "bar",
                     "data" => $tabopened,
                     "name" => $titleopened,
                    ];

                $datasets[] =
                    ['type' => 'bar',
                     'data' => $tabclosed,
                     'name' => $titlesolved,
                    ];


                $tabdatesset = json_encode($tabdates);

                $graph_datas = ['title'   => $title,
                                'comment' => $comment,
                                'name'    => $name,
                                'ids'     => $tabdatesset,
                                'data'    => json_encode($datasets),
                                'labels'  => $labels,
                                //                            'label'  => $title
                ];

                $graph_criterias = [];
                $js_ancestors    = $crit['ancestors'];
                if ($onclick == 1) {
                    $graph_criterias = [
                        'entities_id'        => $entities_id_criteria,
                        'sons'               => $sons_criteria,
                        'requester_groups'   => $requester_groups,
                        'technician_group'   => $technician_group,
                        'technician_id'      => $technicians_ids_crit,
                        'group_is_recursive' => $js_ancestors,
                        'type'               => $type,
                        'locations_id'       => $location,
                        'widget'             => $widgetId];
                }

                $graph = PluginMydashboardBarChart::launchMultipleGraph($graph_datas, $graph_criterias);

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
                $name    = 'TicketStatusResolvedBarLineChart';
                $onclick = 0;
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
                    $onclick   = 1;
                }
                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() != 'central') {
                    $criterias = ['requesters_groups_id',
                                  'year',
                                  'locations_id'];
                }

                $params  = ["preferences" => $preferences,
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
                $technicians_ids_crit = $opt['technicians_id'];

                $requester_groups_criteria = $crit['requesters_groups_id'];
                $requester_groups          = $opt['requesters_groups_id'];

                $technician_groups_criteria = $crit['technicians_groups_id'];
                $technician_group           = $opt['technicians_groups_id'];
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
                }
                //                else {
                //                    $tech_groups_crit = " AND `glpi_plugin_mydashboard_stocktickets`.`groups_id` = -1";
                //                }

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
                $tabdates            = [];
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
                        list($year, $month) = explode('-', $data['month']);
                        $tabdates[0][] = $data['month']. '_progress';
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
                        $tabdates[1][] = $data['month'] . '_opened';

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
                        $tabdates[2][] = $data['month'] . '_resolved';
                        if ($month == date("m") && $year == date("Y")) {
                            $nbdays = date("t", mktime(0, 0, 0, $month, 1, $year));
                            //nbstock : cannot use tech or group criteria

                            $query_3 =
                                "SELECT COUNT(*) as count FROM `glpi_tickets`" .
                                //                        " $ticket_users_join".
                                " WHERE $is_deleted" .
                                " $technician_groups_criteria" .
                                " $entities_criteria" .
                                " $type_criteria" .
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
                            $tabdates[0][] = 0;
                        }

                        $i++;
                    }
                }

                $widget  = new PluginMydashboardHtml();
                $title   = __("Number of opened and resolved / closed tickets by month", "mydashboard");
                $comment = "";
                $widget->setWidgetTitle((($isDebug) ? "34 " : "") . $title);
                $widget->toggleWidgetRefresh();

                $titleopened   = __("Opened tickets", "mydashboard");
                $titlesolved   = __("Resolved / closed tickets", "mydashboard");
                $titleprogress = __("Opened tickets backlog", "mydashboard");
                $labels        = json_encode($tabnames);

                $datasets[] =
                    ['type'   => 'line',
                     'data'   => $tabprogress,
                     'name'   => $titleprogress,
                     'smooth' => false
                    ];

                $datasets[] =
                    ["type" => "bar",
                     "data" => $tabopened,
                     "name" => $titleopened,
                    ];

                $datasets[] =
                    ['type' => 'bar',
                     'data' => $tabresolved,
                     'name' => $titlesolved,
                    ];

                $tabdatesset = json_encode($tabdates);

                $graph_datas = ['title'   => $title,
                                'comment' => $comment,
                                'name'    => $name,
                                'ids'     => $tabdatesset,
                                'data'    => json_encode($datasets),
                                'labels'  => $labels,
                                //                            'label'  => $title
                ];

                $graph_criterias = [];
                $js_ancestors    = $crit['ancestors'];
                if ($onclick == 1) {
                    $graph_criterias = [
                        'entities_id'        => $entities_id_criteria,
                        'sons'               => $sons_criteria,
                        'requester_groups'   => $requester_groups,
                        'technician_group'   => $technician_group,
                        'technician_id'      => $technicians_ids_crit,
                        'group_is_recursive' => $js_ancestors,
                        'type'               => $type,
                        'locations_id'       => $location,
                        'widget'             => $widgetId];
                }

                $graph = PluginMydashboardBarChart::launchMultipleGraph($graph_datas, $graph_criterias);

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
                $name    = 'TicketStatusUnplannedBarLineChart';
                $onclick = 0;
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
                    $onclick   = 1;
                }
                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() != 'central') {
                    $criterias = ['requesters_groups_id',
                                  'year',
                                  'locations_id'];
                }

                $params  = ["preferences" => $preferences,
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
                $technicians_ids_crit = $opt['technicians_id'];

                $requester_groups_criteria = $crit['requesters_groups_id'];
                $requester_groups          = $opt['requesters_groups_id'];

                $tech_groups_crit           = "";
                $technician_groups_criteria = $crit['technicians_groups_id'];
                $technician_group           = $opt['technicians_groups_id'];

                $technician_groups_ids = is_array($opt['technicians_groups_id']) ? $opt['technicians_groups_id'] : [$opt['technicians_groups_id']];
                if (count($opt['technicians_groups_id']) > 0) {
                    $tech_groups_crit = " AND `groups_id` IN (" . implode(",", $technician_groups_ids) . ")";
                }
                //                else {
                //                    $tech_groups_crit = " AND `glpi_plugin_mydashboard_stocktickets`.`groups_id` = -1";
                //                }
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
                $tabdates            = [];
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
                        list($year, $month) = explode('-', $data['month']);
                        $tabdates[0][] = $data['month']. '_progress';
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
                        $tabdates[1][] = $data['month'] . '_opened';

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
                        $tabdates[2][] = $data['month'] . '_closed';

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
                        $tabdates[3][] = $data['month'] . '_unplanned';

                        if ($month == date("m") && $year == date("Y")) {
                            $nbdays = date("t", mktime(0, 0, 0, $month, 1, $year));
                            //nbstock : cannot use tech or group criteria

                            $query_3 =
                                "SELECT COUNT(*) as count FROM `glpi_tickets`" .
                                //                        " $ticket_users_join".
                                " WHERE $is_deleted" .
                                " $technician_groups_criteria" .
                                " $entities_criteria" .
                                " $type_criteria" .
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
                            $tabdates[0][] = 0;
                        }

                        $i++;
                    }
                }

                $widget  = new PluginMydashboardHtml();
                $title   = __("Number of opened, closed  and unplanned tickets by month", "mydashboard");
                $comment = "";
                $widget->setWidgetTitle((($isDebug) ? "35 " : "") . $title);
                $widget->toggleWidgetRefresh();

                $titleopened    = __("Opened tickets", "mydashboard");
                $titlesolved    = __("Closed tickets", "mydashboard");
                $titleunplanned = __("Not planned", "mydashboard");
                $titleprogress  = __("Opened tickets backlog", "mydashboard");
                $labels         = json_encode($tabnames);

                $datasets[] =
                    ['type'   => 'line',
                     'data'   => $tabprogress,
                     'name'   => $titleprogress,
                     'smooth' => false
                    ];

                $datasets[] =
                    ["type" => "bar",
                     "data" => $tabopened,
                     "name" => $titleopened,
                    ];

                $datasets[] =
                    ['type' => 'bar',
                     'data' => $tabclosed,
                     'name' => $titlesolved,
                    ];

                $datasets[] =
                    ['type' => 'bar',
                     'data' => $tabunplanned,
                     'name' => $titleunplanned,
                    ];

                $tabdatesset = json_encode($tabdates);

                $graph_datas = ['title'   => $title,
                                'comment' => $comment,
                                'name'    => $name,
                                'ids'     => $tabdatesset,
                                'data'    => json_encode($datasets),
                                'labels'  => $labels,
                                //                            'label'  => $title
                ];

                $graph_criterias = [];
                $js_ancestors    = $crit['ancestors'];
                if ($onclick == 1) {
                    $graph_criterias = [
                        'entities_id'        => $entities_id_criteria,
                        'sons'               => $sons_criteria,
                        'requester_groups'   => $requester_groups,
                        'technician_group'   => $technician_group,
                        'technician_id'      => $technicians_ids_crit,
                        'group_is_recursive' => $js_ancestors,
                        'type'               => $type,
                        'locations_id'       => $location,
                        'widget'             => $widgetId];
                }

                $graph = PluginMydashboardBarChart::launchMultipleGraph($graph_datas, $graph_criterias);

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

            case $this->getType() . "43":
                $name = 'reportLineChartNbCreatedTicketByMonths';

                $criterias = ['year',
                              'type',
                              'entities_id',
                              'is_recursive'];
                $params    = ["preferences" => $preferences,
                              "criterias"   => $criterias,
                              "opt"         => $opt];

                $options = PluginMydashboardHelper::manageCriterias($params);

                $opt  = $options['opt'];
                $crit = $options['crit'];

                $isDeleted         = " AND `glpi_tickets`.`is_deleted` = 0 ";
                $type_criteria     = $crit['type'];
                $entities_criteria = $crit['entities_id'];

                $currentmonth = date("m");
                $currentyear  = date("Y");
                $now          = date("Y-m-d");
                if (isset($opt["year"]) && $opt["year"] > 0) {
                    $currentyear = $opt["year"];
                }
                $previousyear      = $currentyear - 1;
                $tabdates          = [];
                $queryOpenedTicket = "SELECT DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m') as period,
                                         DATE_FORMAT(`glpi_tickets`.`date`, '%b %Y') as monthname,
                                         count(*) as count
                                  FROM `glpi_tickets`
                                  WHERE  (`glpi_tickets`.`date` >= '$previousyear-$currentmonth-01 00:00:00')
                                  AND (`glpi_tickets`.`date` <= '$now 23:59:59')
                                  " . $entities_criteria . $isDeleted . $type_criteria . "
                                  GROUP BY DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m')";
                $tabdata           = [];
                $tabnames          = [];
                $results           = $DB->query($queryOpenedTicket);
                while ($data = $DB->fetchArray($results)) {
                    $tabdata[]  = $data['count'];
                    $tabnames[] = $data['monthname'];
                    $tabdates[] = $data['period'];
                }


                $widget  = new PluginMydashboardHtml();

                $title   = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetTitle((($isDebug) ? "43 " : "") . $title);
                $widget->setWidgetComment($comment);

                $widget->toggleWidgetRefresh();

                $nbtickets  = __('Tickets number', 'mydashboard');
                $datasets[] =
                    ['type'   => 'line',
                     'data'   => $tabdata,
                     'name'   => $nbtickets,
                     'smooth' => false
                    ];

                $dataLineset = json_encode($datasets);
                $labelsLine  = json_encode($tabnames);
                $tabdatesset = json_encode($tabdates);

                $graph_datas = ['title'   => $title,
                                'comment' => $comment,
                                'name'    => $name,
                                'ids'     => $tabdatesset,
                                'data'    => $dataLineset,
                                'labels'  => $labelsLine,
                ];

                $graph_criterias = ['type'   => $options['crit']["type"],
                                    'year'   => $options['crit']['year'],
                                    'widget' => $widgetId];

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

                $widget->setWidgetHtmlContent($graph);

                return $widget;
                break;

            case $this->getType() . "44":
                $name = 'reportLineChartNbCreatedTicketByWeek';

                $criterias = ['entities_id',
                              'is_recursive',
                              'year',
                              'type'];
                $params    = ["preferences" => $preferences,
                              "criterias"   => $criterias,
                              "opt"         => $opt];
                $options   = PluginMydashboardHelper::manageCriterias($params);

                $opt      = $options['opt'];
                $result   = self::getTicketsCreatedPerWeek($options);
                $tabdata  = [];
                $tabnames = [];
                $maxcount = 0;
                foreach ($result as $weeknum => $nbticket) {
                    $tabdata[]  = $nbticket;
                    $tabnames[] = $weeknum;
                    if ($nbticket > $maxcount) {
                        $maxcount = $nbticket;
                    }
                }

                $widget  = new PluginMydashboardHtml();
                $title   = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetTitle((($isDebug) ? "44 " : "") . $title);
                $widget->setWidgetComment($comment);
                $widget->toggleWidgetRefresh();

                $nbtickets = __('Tickets number', 'mydashboard');

                $datasets[] =
                    ['type'   => 'line',
                     'data'   => $tabdata,
                     'name'   => $nbtickets,
                     'smooth' => false
                    ];

                $dataLineset = json_encode($datasets);
                $labelsLine  = json_encode($tabnames);
                $tabdatesset = json_encode([]);

                $graph_datas     = ['title'   => $title,
                                    'comment' => $comment,
                                    'name'    => $name,
                                    'ids'     => $tabdatesset,
                                    'data'    => $dataLineset,
                                    'labels'  => $labelsLine];
                $onclick         = 0;
                $graph_criterias = [];
                if ($onclick == 1) {
                    $graph_criterias = ['type'   => $options['crit']["type"],
                                        'year'   => $options['crit']['year'],
                                        'widget' => $widgetId];
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

                $widget->setWidgetHtmlContent($graph);

                return $widget;
                break;
            case $this->getType() . "45":
                $name      = 'reportLineChartRefusedTicketsByMonths';
                $criterias = ['year',
                              'type',
                              'entities_id',
                              'is_recursive'];
                $params    = ["preferences" => $preferences,
                              "criterias"   => $criterias,
                              "opt"         => $opt];
                $options   = PluginMydashboardHelper::manageCriterias($params);

                $opt  = $options['opt'];
                $crit = $options['crit'];

                $isDeleted         = " AND `glpi_tickets`.`is_deleted` = 0 ";
                $type_criteria     = $crit['type'];
                $entities_criteria = $crit['entities_id'];

                $currentmonth = date("m");
                $currentyear  = date("Y");
                $now          = date("Y-m-d");
                if (isset($opt["year"]) && $opt["year"] > 0) {
                    $currentyear = $opt["year"];
                }
                $previousyear      = $currentyear - 1;
                $tabdates          = [];
                $queryOpenedTicket = "SELECT DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m') as period,
                                         DATE_FORMAT(`glpi_tickets`.`date`, '%b %Y') as monthname,
                                         count(*) as count
                                  FROM `glpi_tickets`
                                  INNER JOIN glpi_ticketvalidations ON `glpi_tickets`.`id` = `glpi_ticketvalidations`.`tickets_id`
                                  WHERE  (`glpi_tickets`.`date` >= '$previousyear-$currentmonth-01 00:00:00')
                                  AND `glpi_ticketvalidations`.`status` = 4 
                                  AND (`glpi_tickets`.`date` <= '$now 23:59:59')
                                  " . $entities_criteria . $isDeleted . $type_criteria . "
                                  GROUP BY DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m')";
                $tabdata           = [];
                $tabnames          = [];
                $results           = $DB->query($queryOpenedTicket);
                while ($data = $DB->fetchArray($results)) {
                    $tabdata[]  = $data['count'];
                    $tabnames[] = $data['monthname'];
                    $tabdates[] = $data['period'];
                }


                $widget  = new PluginMydashboardHtml();
                $title   = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetTitle((($isDebug) ? "45 " : "") . $title);
                $widget->setWidgetComment($comment);
                $widget->toggleWidgetRefresh();

                $nbtickets = __('Tickets number', 'mydashboard');

                $datasets[] =
                    ['type'   => 'line',
                     'data'   => $tabdata,
                     'name'   => $nbtickets,
                     'smooth' => false
                    ];

                $dataLineset = json_encode($datasets);
                $labelsLine  = json_encode($tabnames);
                $tabdatesset = json_encode($tabdates);

                $graph_datas = ['title'   => $title,
                                'comment' => $comment,
                                'name'    => $name,
                                'ids'     => $tabdatesset,
                                'data'    => $dataLineset,
                                'labels'  => $labelsLine];

                $graph_criterias = ['type'   => $options['crit']["type"],
                                    'year'   => $options['crit']['year'],
                                    'widget' => $widgetId];


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

                $widget->setWidgetHtmlContent($graph);

                return $widget;
                break;
            case $this->getType() . "46":
                $name      = 'reportLineTicketsProblemsByMonths';
                $criterias = ['year',
                              'type',
                              'entities_id',
                              'is_recursive'];
                $params    = ["preferences" => $preferences,
                              "criterias"   => $criterias,
                              "opt"         => $opt];
                $options   = PluginMydashboardHelper::manageCriterias($params);

                $opt  = $options['opt'];
                $crit = $options['crit'];

                $isDeleted         = " AND `glpi_tickets`.`is_deleted` = 0 ";
                $type_criteria     = $crit['type'];
                $entities_criteria = $crit['entities_id'];

                $currentmonth = date("m");
                $currentyear  = date("Y");
                $now          = date("Y-m-d");
                if (isset($opt["year"]) && $opt["year"] > 0) {
                    $currentyear = $opt["year"];
                }
                $previousyear      = $currentyear - 1;
                $tabdates          = [];
                $queryOpenedTicket = "SELECT DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m') as period,
                                         DATE_FORMAT(`glpi_tickets`.`date`, '%b %Y') as monthname,
                                         count(*) as count
                                  FROM `glpi_tickets`
                                  INNER JOIN glpi_problems_tickets ON `glpi_tickets`.`id` = `glpi_problems_tickets`.`tickets_id`
                                  WHERE  (`glpi_tickets`.`date` >= '$previousyear-$currentmonth-01 00:00:00')
                                  AND (`glpi_tickets`.`date` <= '$now 23:59:59')
                                  " . $entities_criteria . $isDeleted . $type_criteria . "
                                  GROUP BY DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m')";
                $tabdata           = [];
                $tabnames          = [];
                $results           = $DB->query($queryOpenedTicket);
                while ($data = $DB->fetchArray($results)) {
                    $tabdata[]  = $data['count'];
                    $tabnames[] = $data['monthname'];
                    $tabdates[] = $data['period'];
                }


                $widget  = new PluginMydashboardHtml();
                $title   = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetTitle((($isDebug) ? "46 " : "") . $title);
                $widget->setWidgetComment($comment);
                $widget->toggleWidgetRefresh();

                $nbtickets = __('Tickets number', 'mydashboard');

                $datasets[] =
                    ['type'   => 'line',
                     'data'   => $tabdata,
                     'name'   => $nbtickets,
                     'smooth' => false
                    ];

                $dataLineset = json_encode($datasets);
                $labelsLine  = json_encode($tabnames);
                $tabdatesset = json_encode($tabdates);

                $graph_datas = ['title'   => $title,
                                'comment' => $comment,
                                'name'    => $name,
                                'ids'     => $tabdatesset,
                                'data'    => $dataLineset,
                                'labels'  => $labelsLine];

                $graph_criterias = ['type'   => $options['crit']["type"],
                                    'year'   => $options['crit']['year'],
                                    'widget' => $widgetId];

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

                $widget->setWidgetHtmlContent($graph);

                return $widget;
                break;
            case $this->getType() . "47":
                $name = 'reportLineChartBacklogTicketByWeek';

                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() == 'central') {
                    $criterias = ['technicians_groups_id',
                                  'entities_id',
                                  'is_recursive',
                                  'year',
                                  'type'];
                }
                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() != 'central') {
                    $criterias = [];
                }

                $params    = ["preferences" => $preferences,
                              "criterias"   => $criterias,
                              "opt"         => $opt];
                $options   = PluginMydashboardHelper::manageCriterias($params);

                $opt                        = $options['opt'];
                $crit                       = $options['crit'];
                $technician_groups_ids      = $opt['technicians_groups_id'];
                $currentyear = date("Y");
                $year        = intval(date('Y', time()) - 1);

                if (isset($opt["year"]) && $opt["year"] > 0) {
                    $year        = $opt["year"];
                    $currentyear = $opt["year"];
                }
                if ($year < intval($currentyear)) {
                    $week = date("W", strtotime("$currentyear-12-31"));
                } else {
                    $week = intval(date('W'));
                }

                $tabdata  = [];
                $tabnames = [];
                $maxcount = 0;

                for ($i = 1; $i <= intval($week); $i++) {
                    if (!isset($datas[$i])) {
                        $nbticket = 0;
                        if ($opt['type'] == Ticket::DEMAND_TYPE) {
                            $nbticket += PluginMydashboardAlert::queryRequestTicketsWeek($currentyear, $i, $technician_groups_ids);
                        } elseif ($opt['type'] == Ticket::INCIDENT_TYPE) {
                            $nbticket += PluginMydashboardAlert::queryIncidentTicketsWeek($currentyear, $i, $technician_groups_ids);
                        } else {
                            $nbticket += PluginMydashboardAlert::queryIncidentTicketsWeek($currentyear, $i, $technician_groups_ids);
                            $nbticket += PluginMydashboardAlert::queryRequestTicketsWeek($currentyear, $i, $technician_groups_ids);
                        }
                        $tabdata[]  = $nbticket;
                        $tabnames[] = $i;
                        if ($nbticket > $maxcount) {
                            $maxcount = $nbticket;
                        }
                    }
                }

                $widget  = new PluginMydashboardHtml();
                $title   = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetTitle((($isDebug) ? "47 " : "") . $title);
                $widget->setWidgetComment($comment);
                $widget->toggleWidgetRefresh();

                $nbtickets = __('Tickets number', 'mydashboard');

                $datasets[] =
                    ['type'   => 'line',
                     'data'   => $tabdata,
                     'name'   => $nbtickets,
                     'smooth' => false
                    ];

                $dataLineset = json_encode($datasets);
                $labelsLine  = json_encode($tabnames);
                $tabdatesset = json_encode([]);

                $graph_datas     = ['title'   => $title,
                                    'comment' => $comment,
                                    'name'    => $name,
                                    'ids'     => $tabdatesset,
                                    'data'    => $dataLineset,
                                    'labels'  => $labelsLine];
                $onclick         = 0;
                $graph_criterias = [];
                if ($onclick == 1) {
                    $graph_criterias = ['type'   => $options['crit']["type"],
                                        'year'   => $options['crit']['year'],
                                        'widget' => $widgetId];
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

                $widget->setWidgetHtmlContent($graph);

                return $widget;

                break;

            case $this->getType() . "48":
                $name    = 'reportLineWeekBacklog';
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

                $params  = ["preferences" => $preferences,
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
                $query .= " WHERE $is_deleted $type_criteria $locations_criteria $technician_groups_criteria
                 $requester_groups_criteria";
                $query .= " $entities_criteria 
                AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")
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

                $widget  = new PluginMydashboardHtml();
                $title   = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetTitle((($isDebug) ? "48 " : "") . $title);
                $widget->setWidgetComment($comment);
                $widget->toggleWidgetRefresh();

                $nbtickets = __('Tickets number', 'mydashboard');

                $datasets[] =
                    ['type'   => 'line',
                     'data'   => $tabdata,
                     'name'   => $nbtickets,
                     'smooth' => false
                    ];

                $databacklogset = json_encode($datasets);
                $labelsback     = json_encode($tabnames);
                $tabdatesset    = json_encode($tabdates);

                $js_ancestors = $crit['ancestors'];

                $graph_datas = ['title'   => $title,
                                'comment' => $comment,
                                'name'    => $name,
                                'ids'     => $tabdatesset,
                                'data'    => $databacklogset,
                                'labels'  => $labelsback];
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
            default:
                break;
        }
    }

    /**
     * @param $params
     * @param $specific
     *
     * @return array
     */
    private static function getTicketsCreatedPerWeek($params, $specific = [])
    {
        global $DB;

        $year        = intval(date('Y', time()) - 1);
        $currentyear = date("Y");

        if (isset($params['opt']["year"]) && $params['opt']["year"] > 0) {
            $year = $params['opt']["year"];
        }
        if ($year < intval($currentyear)) {
            $week = date("W", strtotime("$currentyear-12-31"));
        } else {
            $week = intval(date('W'));
        }
        $type_criteria = $params['crit']["type"];

        $entities_criteria = $params['crit']['entities_id'];
        $is_deleted        = "`glpi_tickets`.`is_deleted` = 0";
        $whereStr          = "";
        if (!empty($specific)) {
            $whereStr = " " . implode('', $specific);
        }

        $querym_ai   = "SELECT COUNT(`glpi_tickets`.`id`) AS nbtickets,
                                   week(`glpi_tickets`.`date` ) AS numweek
                        FROM `glpi_tickets` ";
        $querym_ai   .= "WHERE ";
        $querym_ai   .= "(
                           `glpi_tickets`.`date` >= '$year-01-01 00:00:00' 
                           AND `glpi_tickets`.`date` <= '$year-12-31 23:59:59'
                           AND  $is_deleted 
                           $type_criteria ) 
                           $entities_criteria
                           $whereStr";
        $querym_ai   .= "GROUP BY week(`glpi_tickets`.`date`);
                        ";
        $result_ai_q = $DB->query($querym_ai);
        $datas       = [];
        while ($data = $DB->fetchAssoc($result_ai_q)) {
            $datas[$data["numweek"]] = $data["nbtickets"];
        }


        for ($i = 1; $i <= intval($week); $i++) {
            if (!isset($datas[$i])) {
                $datas[$i] = 0;
            }
        }
        ksort($datas);


        return $datas;
    }


    /**
     * @param $selected_id
     *
     * @return string
     */
    public static function pluginMydashboardReports_Line22link($params)
    {
        global $CFG_GLPI;

        $options['reset'][] = 'reset';

        if (isset($params['selected_id']) && strpos($params['selected_id'], '_') !== false) {
            $eventParts   = explode('_', $params['selected_id']);
            $date         = $eventParts[0];
            $ticket_state = $eventParts[1];
            if (isset($date) && strpos($date, '-') !== false) {
                $dateParts = explode('-', $date);
                $year      = $dateParts[0];
                $month     = $dateParts[1];
            }

            $params['id'] = $eventParts[1];
        }
        if (isset($year) && isset($month) && isset($ticket_state)) {
            if ($ticket_state == "opened") {
                $crit = PluginMydashboardChart::OPEN_DATE;
            } elseif ($ticket_state == "closed") {
                $crit = PluginMydashboardChart::CLOSE_DATE;
            } elseif ($ticket_state == "progress") {
                $crit = PluginMydashboardChart::OPEN_DATE;
            }
            if ($ticket_state == "progress") {
                $nbdays = date("t", mktime(0, 0, 0, $month, 1, $year));
                $date   = "$year-$month-$nbdays 23:59";
                $options = PluginMydashboardChart::addCriteria($crit, 'lessthan', $date, 'AND');
                $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::STATUS, 'equals', 'notold', 'AND');
            } else {
                $date   = "$year-$month-01 00:00";
                $nbdays = date("t", mktime(0, 0, 0, $month, 1, $year));
                $options = PluginMydashboardChart::addCriteria($crit, 'morethan', $date, 'AND');
                $date = "$year-$month-$nbdays 23:59";
                $options = PluginMydashboardChart::addCriteria($crit, 'lessthan', $date, 'AND');
            }
        }

        if ($params["params"]["locations_id"] > 0) {
            $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::LOCATIONS_ID, 'equals', $params["params"]["locations_id"], 'AND');
        }
        if ($params["params"]["technician_id"] > 0) {
            $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::TECHNICIAN, 'equals', $params["params"]["technician_id"], 'AND');
        }
        if ($params["params"]["type"] > 0) {
            $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::TYPE, 'equals', $params["params"]["type"], 'AND');
        }

        $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::ENTITIES_ID, (isset($params["params"]["sons"])
                                  && $params["params"]["sons"] > 0) ? 'under' : 'equals', $params["params"]["entities_id"], 'AND');

        $options = PluginMydashboardChart::groupCriteria(PluginMydashboardChart::REQUESTER_GROUP, 'equals', $params["params"]["requester_groups"]);

        $options = PluginMydashboardChart::groupCriteria(PluginMydashboardChart::TECHNICIAN_GROUP, ((isset($params["params"]["group_is_recursive"])
                                          && !empty($params["params"]["group_is_recursive"])) ? 'under' : 'equals'), $params["params"]["technician_group"]);

        return  $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
                Toolbox::append_params($options, "&");

    }


    /**
     * @param $selected_id
     *
     * @return string
     */
    public static function pluginMydashboardReports_Line34link($params)
    {
        global $CFG_GLPI;

        $options['reset'][] = 'reset';

        if (isset($params['selected_id']) && strpos($params['selected_id'], '_') !== false) {
            $eventParts   = explode('_', $params['selected_id']);
            $date         = $eventParts[0];
            $ticket_state = $eventParts[1];
            if (isset($date) && strpos($date, '-') !== false) {
                $dateParts = explode('-', $date);
                $year      = $dateParts[0];
                $month     = $dateParts[1];
            }

            $params['id'] = $eventParts[1];
        }
        if (isset($year) && isset($month) && isset($ticket_state)) {
            if ($ticket_state == "opened") {
                $crit = PluginMydashboardChart::OPEN_DATE;
            } elseif ($ticket_state == "resolved") {
                $crit = PluginMydashboardChart::SOLVE_DATE;
            } elseif ($ticket_state == "progress") {
                $crit = PluginMydashboardChart::OPEN_DATE;
            }
            if ($ticket_state == "progress") {
                $nbdays = date("t", mktime(0, 0, 0, $month, 1, $year));
                $date   = "$year-$month-$nbdays 23:59";
                $options = PluginMydashboardChart::addCriteria($crit, 'lessthan', $date, 'AND');
                $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::STATUS, 'equals', 'notold', 'AND');
            } else {
                $date   = "$year-$month-01 00:00";
                $nbdays = date("t", mktime(0, 0, 0, $month, 1, $year));
                $options = PluginMydashboardChart::addCriteria($crit, 'morethan', $date, 'AND');
                $date = "$year-$month-$nbdays 23:59";
                $options = PluginMydashboardChart::addCriteria($crit, 'lessthan', $date, 'AND');
            }
        }

        if ($params["params"]["locations_id"] > 0) {
            $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::LOCATIONS_ID, 'equals', $params["params"]["locations_id"], 'AND');
        }
        if ($params["params"]["technician_id"] > 0) {
            $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::TECHNICIAN, 'equals', $params["params"]["technician_id"], 'AND');
        }
        if ($params["params"]["type"] > 0) {
            $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::TYPE, 'equals', $params["params"]["type"], 'AND');
        }

        $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::ENTITIES_ID, (isset($params["params"]["sons"])
                                  && $params["params"]["sons"] > 0) ? 'under' : 'equals', $params["params"]["entities_id"], 'AND');

        $options = PluginMydashboardChart::groupCriteria(PluginMydashboardChart::REQUESTER_GROUP, 'equals', $params["params"]["requester_groups"]);

        $options = PluginMydashboardChart::groupCriteria(PluginMydashboardChart::TECHNICIAN_GROUP, ((isset($params["params"]["group_is_recursive"])
                                          && !empty($params["params"]["group_is_recursive"])) ? 'under' : 'equals'), $params["params"]["technician_group"]);


        return  $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
                Toolbox::append_params($options, "&");

    }


    /**
     * @param $selected_id
     *
     * @return string
     */
    public static function pluginMydashboardReports_Line35link($params)
    {
        global $CFG_GLPI;

        $options['reset'][] = 'reset';

        if (isset($params['selected_id']) && strpos($params['selected_id'], '_') !== false) {
            $eventParts   = explode('_', $params['selected_id']);
            $date         = $eventParts[0];
            $ticket_state = $eventParts[1];
            if (isset($date) && strpos($date, '-') !== false) {
                $dateParts = explode('-', $date);
                $year      = $dateParts[0];
                $month     = $dateParts[1];
            }

            $params['id'] = $eventParts[1];
        }
        $add_actiontime_crit = 0;
        if (isset($year) && isset($month) && isset($ticket_state)) {
            if ($ticket_state == "opened") {
                $crit = PluginMydashboardChart::OPEN_DATE;
            } elseif ($ticket_state == "closed") {
                $crit = PluginMydashboardChart::CLOSE_DATE;
            } elseif ($ticket_state == "progress") {
                $crit = PluginMydashboardChart::OPEN_DATE;
            } elseif ($ticket_state == "unplanned") {
                $crit                = PluginMydashboardChart::CLOSE_DATE;
                $add_actiontime_crit = 1;
            }
            if ($ticket_state == "progress") {
                $nbdays = date("t", mktime(0, 0, 0, $month, 1, $year));
                $date   = "$year-$month-$nbdays 23:59";
                $options = PluginMydashboardChart::addCriteria($crit, 'lessthan', $date, 'AND');
                $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::STATUS, 'equals', 'notold', 'AND');
            } else {
                $date   = "$year-$month-01 00:00";
                $nbdays = date("t", mktime(0, 0, 0, $month, 1, $year));
                $options = PluginMydashboardChart::addCriteria($crit, 'morethan', $date, 'AND');
                $date = "$year-$month-$nbdays 23:59";
                $options = PluginMydashboardChart::addCriteria($crit, 'lessthan', $date, 'AND');
            }
        }

        if ($params["params"]["locations_id"] > 0) {
            $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::LOCATIONS_ID, 'equals', $params["params"]["locations_id"], 'AND');
        }
        if ($params["params"]["technician_id"] > 0) {
            $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::TECHNICIAN, 'equals', $params["params"]["technician_id"], 'AND');
        }
        if ($params["params"]["type"] > 0) {
            $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::TYPE, 'equals', $params["params"]["type"], 'AND');
        }

        $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::ENTITIES_ID, (isset($params["params"]["sons"])
                                  && $params["params"]["sons"] > 0) ? 'under' : 'equals', $params["params"]["entities_id"], 'AND');

        $options = PluginMydashboardChart::groupCriteria(PluginMydashboardChart::REQUESTER_GROUP, 'equals', $params["params"]["requester_groups"]);

        $options = PluginMydashboardChart::groupCriteria(PluginMydashboardChart::TECHNICIAN_GROUP, ((isset($params["params"]["group_is_recursive"])
                                          && !empty($params["params"]["group_is_recursive"])) ? 'under' : 'equals'), $params["params"]["technician_group"]);

        if ($add_actiontime_crit == 1) {
            $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::TASK_ACTIONTIME, 'contains', 'NULL', 'AND');
        }

        return  $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
                Toolbox::append_params($options, "&");

    }

    /**
     * @param $selected_id
     *
     * @return string
     */
    public static function pluginMydashboardReports_Line43link($params)
    {
        global $CFG_GLPI;

        $options['reset'][] = 'reset';
        
        if (isset($params['selected_id']) && strpos($params['selected_id'], '-') !== false) {
            $dateParts = explode('-', $params['selected_id']);
            $year      = $dateParts[0];
            $month     = $dateParts[1];
        }
        if (isset($month)) {
            $date   = "$year-$month-01 00:00";
            $nbdays = date("t", mktime(0, 0, 0, $month, 1, $year));
            $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::OPEN_DATE, 'morethan', $date, 'AND');
            $date = "$year-$month-$nbdays 23:59";
            $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::OPEN_DATE, 'lessthan', $date, 'AND');
        }
        if ($params["params"]["type"] > 0) {
            $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::TYPE, 'equals', $params["params"]["type"], 'AND');
        }
        return  $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
                Toolbox::append_params($options, "&");

    }

    /**
     * @param $selected_id
     *
     * @return string
     */
    public static function pluginMydashboardReports_Line44link($params)
    {
        global $CFG_GLPI;

        $options['reset'][] = 'reset';

        if (isset($params['selected_id']) && strpos($params['selected_id'], '_') !== false) {
            $eventParts   = explode('_', $params['selected_id']);
            $date         = $eventParts[0];
            $ticket_state = $eventParts[1];
            if (isset($date) && strpos($date, '-') !== false) {
                $dateParts = explode('-', $date);
                $year      = $dateParts[0];
                $month     = $dateParts[1];
            }

            $params['id'] = $eventParts[1];
        }
        $week_number = $params["selected_id"];

        $firstMonday = date("d", strtotime("first monday of january $year"));
        $start       = date("Y-m-d 00:00:00", strtotime("$firstMonday Jan " . $year . " 00:00:00 GMT + " . $week_number . " weeks"));
        $end         = date("Y-m-d 23:59:59", strtotime($start . " + 1 week"));
        $end         = date("Y-m-d 23:59:59", strtotime($end . " - 1 day"));

        $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::OPEN_DATE, 'morethan', $start, 'AND');
        $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::OPEN_DATE, 'lessthan', $end, 'AND');

        if ($params["params"]["type"] > 0) {
            $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::TYPE, 'equals', $params["params"]["type"], 'AND');
        }
        return  $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
                Toolbox::append_params($options, "&");

    }


    /**
     * @param $selected_id
     *
     * @return string
     */
    public static function pluginMydashboardReports_Line45link($params)
    {
        global $CFG_GLPI;

        $options['reset'][] = 'reset';

        if (isset($params['selected_id']) && strpos($params['selected_id'], '-') !== false) {
            $dateParts = explode('-', $params['selected_id']);
            $year      = $dateParts[0];
            $month     = $dateParts[1];
        }
        if (isset($month)) {
            $date   = "$year-$month-01 00:00";
            $nbdays = date("t", mktime(0, 0, 0, $month, 1, $year));
            $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::OPEN_DATE, 'morethan', $date, 'AND');
            $date = "$year-$month-$nbdays 23:59";
            $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::OPEN_DATE, 'lessthan', $date, 'AND');
        }
        $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::VALIDATION_STATS, 'equals', VALIDATION_REFUSED, 'AND');

        if ($params["params"]["type"] > 0) {
            $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::TYPE, 'equals', $params["params"]["type"], 'AND');
        }
        return  $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
                Toolbox::append_params($options, "&");

    }


    /**
     * @param $selected_id
     *
     * @return string
     */
    public static function pluginMydashboardReports_Line46link($params)
    {
        global $CFG_GLPI;

        $options['reset'][] = 'reset';

        if (isset($params['selected_id']) && strpos($params['selected_id'], '-') !== false) {
            $dateParts = explode('-', $params['selected_id']);
            $year      = $dateParts[0];
            $month     = $dateParts[1];
        }
        if (isset($month)) {
            $date   = "$year-$month-01 00:00";
            $nbdays = date("t", mktime(0, 0, 0, $month, 1, $year));
            $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::OPEN_DATE, 'morethan', $date, 'AND');
            $date = "$year-$month-$nbdays 23:59";
            $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::OPEN_DATE, 'lessthan', $date, 'AND');
        }
        $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::NUMBER_OF_PROBLEMS, 'equals', '>0', 'AND');

        if ($params["params"]["type"] > 0) {
            $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::TYPE, 'equals', $params["params"]["type"], 'AND');
        }
        return  $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
                Toolbox::append_params($options, "&");

    }


    /**
     * @param $selected_id
     *
     * @return string
     */
    public static function pluginMydashboardReports_Line48link($params)
    {
        global $CFG_GLPI;

        $options['reset'][] = 'reset';

        if (isset($params['selected_id']) && strpos($params['selected_id'], '-') !== false) {
            $dateParts = explode('-', $params['selected_id']);
            $year      = $dateParts[0];
            $month     = $dateParts[1];
        }
        if (isset($month)) {
            $date   = "$year-$month-01 00:00";
            $nbdays = date("t", mktime(0, 0, 0, $month, 1, $year));
            $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::OPEN_DATE, 'morethan', $date, 'AND');
            $date = "$year-$month-$nbdays 23:59";
            $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::OPEN_DATE, 'lessthan', $date, 'AND');
        }
        $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::STATUS, 'equals', 'notold', 'AND');

        $options = PluginMydashboardChart::groupCriteria(PluginMydashboardChart::REQUESTER_GROUP, 'equals', $params["params"]["requester_groups"]);

        $options = PluginMydashboardChart::groupCriteria(PluginMydashboardChart::TECHNICIAN_GROUP, ((isset($params["params"]["group_is_recursive"])
                                          && !empty($params["params"]["group_is_recursive"])) ? 'under' : 'equals'), $params["params"]["technician_group"]);

        if ($params["params"]["type"] > 0) {
            $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::TYPE, 'equals', $params["params"]["type"], 'AND');
        }
        return  $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
                Toolbox::append_params($options, "&");

    }
}
