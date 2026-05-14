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

namespace GlpiPlugin\Mydashboard\Reports;

use CommonDBTM;
use CommonITILActor;
use CommonITILObject;
use DateInterval;
use DatePeriod;
use DateTime;
use DbUtils;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QuerySubQuery;
use Glpi\DBAL\QueryUnion;
use GlpiPlugin\Mydashboard\Charts\BarChart;
use GlpiPlugin\Mydashboard\Criteria;
use GlpiPlugin\Mydashboard\Criterias\Entity;
use GlpiPlugin\Mydashboard\Criterias\FilterDate;
use GlpiPlugin\Mydashboard\Criterias\ITILCategory;
use GlpiPlugin\Mydashboard\Criterias\Limit;
use GlpiPlugin\Mydashboard\Criterias\Location;
use GlpiPlugin\Mydashboard\Criterias\MultipleLocation;
use GlpiPlugin\Mydashboard\Criterias\RequesterGroup;
use GlpiPlugin\Mydashboard\Criterias\Technician;
use GlpiPlugin\Mydashboard\Criterias\Year;
use GlpiPlugin\Mydashboard\Helper;
use GlpiPlugin\Mydashboard\Html;
use GlpiPlugin\Mydashboard\Menu;
use GlpiPlugin\Mydashboard\Preference as MydashboardPreference;
use GlpiPlugin\Mydashboard\Widget;
use Plugin;
use Session;
use Toolbox;

/**
 * Class Reports_Bar
 */
class Reports_Bar extends CommonDBTM
{
    private $options;
    private $pref;
    public static $reports = [1, 8, 15, 21, 23, 24, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44];

    /**
     * Reports_Bar constructor.
     *
     * @param array $_options
     */
    public function __construct($_options = [])
    {
        $this->options = $_options;
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
     * @return array
     */
    public function getWidgetsForItem()
    {
        $widgets = [
            Menu::$HELPDESK => [
                $this->getType() . "1" => [
                    "title" => __("Opened tickets backlog", "mydashboard"),
                    "type" => Widget::$BAR,
                    "comment" => __("Display of opened tickets by month", "mydashboard"),
                ],
                $this->getType() . "8" => [
                    "title" => __("Process time by technicians by month", "mydashboard"),
                    "type" => Widget::$BAR,
                    "comment" => __("Sum of ticket tasks duration by technicians", "mydashboard"),
                ],
                $this->getType() . "15" => [
                    "title" => __("Top ten ticket categories by type of ticket", "mydashboard"),
                    "type" => Widget::$BAR,
                    "comment" => __("Display of Top ten ticket categories by type of ticket", "mydashboard"),
                ],
                $this->getType() . "21" => [
                    "title" => __("Number of tickets affected by technicians by month", "mydashboard"),
                    "type" => Widget::$BAR,
                    "comment" => __("Sum of ticket affected by technicians", "mydashboard"),
                ],
                $this->getType() . "23" => [
                    "title" => __("Average real duration of treatment of the ticket", "mydashboard"),
                    "type" => Widget::$BAR,
                    "comment" => __(
                        "Display of average real duration of treatment of tickets (actiontime of tasks)",
                        "mydashboard"
                    ),
                ],
                $this->getType() . "24" => [
                    "title" => __("Top ten technicians (by tickets number)", "mydashboard"),
                    "type" => Widget::$BAR,
                    "comment" => __("Display of number of tickets by technicians", "mydashboard"),
                ],
                $this->getType() . "35" => [
                    "title" => __("Age of tickets", "mydashboard"),
                    "type" => Widget::$BAR,
                    "comment" => "",
                ],
                $this->getType() . "36" => [
                    "title" => __("Number of opened tickets by priority", "mydashboard"),
                    "type" => Widget::$BAR,
                    "comment" => "",
                ],
                $this->getType() . "37" => [
                    "title" => __("Stock of tickets by status", "mydashboard"),
                    "type" => Widget::$BAR,
                    "comment" => "",
                ],
                $this->getType() . "38" => [
                    "title" => __("Number of opened ticket and average satisfaction per trimester", "mydashboard"),
                    "type" => Widget::$BAR,
                    "comment" => "",
                ],
                $this->getType() . "39" => [
                    "title" => __("Responsiveness over 12 rolling months", "mydashboard"),
                    "type" => Widget::$BAR,
                    "comment" => "",
                ],
                $this->getType() . "40" => [
                    "title" => __("Tickets request sources evolution", "mydashboard"),
                    "type" => Widget::$BAR,
                    "comment" => __("Evolution of tickets request sources types by year", "mydashboard"),
                ],
                $this->getType() . "41" => [
                    "title" => __("Tickets solution types evolution", "mydashboard"),
                    "type" => Widget::$BAR,
                    "comment" => __("Evolution of solution types by year", "mydashboard"),
                ],
                $this->getType() . "42" => [
                    "title" => __("Solve delay and take into account of tickets", "mydashboard"),
                    "type" => Widget::$BAR,
                    "comment" => "",
                ],
                $this->getType() . "43" => [
                    "title" => __("Evolution of ticket satisfaction by year", "mydashboard"),
                    "type" => Widget::$BAR,
                    "comment" => "",
                ],
                $this->getType() . "45" => [
                    "title" => __("Evolution of TTO respect", "mydashboard"),
                    "type" => Widget::$BAR,
                    "comment" => "",
                ],
                $this->getType() . "46" => [
                    "title" => __("Evolution of TTR respect", "mydashboard"),
                    "type" => Widget::$BAR,
                    "comment" => "",
                ],
            ],
            Menu::$INVENTORY => [
                $this->getType() . "44" => [
                    "title" => __("Last synchronization of computers by month", "mydashboard"),
                    "type" => Widget::$BAR,
                    "comment" => "",
                ],
            ],
        ];
        return $widgets;
    }

    /**
     * @param       $widgetId
     * @param array $opt
     *
     * @return Html
     * @throws \GlpitestSQLError
     */
    public function getWidgetContentForItem($widgetId, $opt = [])
    {
        global $DB, $CFG_GLPI;
        $isDebug = $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE;
        $dbu = new DbUtils();

        $preference = new MydashboardPreference();
        if (Session::getLoginUserID() !== false
            && !$preference->getFromDB(Session::getLoginUserID())) {
            $preference->initPreferences(Session::getLoginUserID());
        }
        $preference->getFromDB(Session::getLoginUserID());
        $preferences = $preference->fields;


        switch ($widgetId) {
            case $this->getType() . "1":
                $name = 'BacklogBarChart';
                $onclick = 0;

                $criterias = Criteria::getDefaultCriterias();

                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() == 'central') {
                    $onclick = 1;
                    $specific_criterias = [
                        RequesterGroup::$criteria_name,
                        Location::$criteria_name,
                    ];
                    $criterias = array_merge($criterias, $specific_criterias);
                }
                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() != 'central') {
                    $specific_criterias = [
                        RequesterGroup::$criteria_name,
                        Location::$criteria_name,
                    ];
                    $criterias = array_merge($criterias, $specific_criterias);
                }

                $params = [
                    "preferences" => $preferences,
                    "criterias" => $criterias,
                    "opt" => $opt,
                ];

                $default = Criteria::manageCriterias($params);

                $is_deleted = ['glpi_tickets.is_deleted' => 0];

                $criteria = [
                    'SELECT' => [
                        new QueryExpression("DATE_FORMAT(" . $DB->quoteName("date") . ", '%b %Y') AS period_name"),
                        'COUNT' => 'glpi_tickets.id AS nb',
                        new QueryExpression("DATE_FORMAT(" . $DB->quoteName("date") . ", '%Y-%m') AS period"),
                    ],
                    'DISTINCT' => true,
                    'FROM' => 'glpi_tickets',
                    'LEFT JOIN' => [],
                    'WHERE' => [
                        $is_deleted,
                        'glpi_tickets.status' => \Ticket::getNotSolvedStatusArray(),
                    ],
                    'GROUPBY' => 'period_name',
                    'ORDERBY' => 'period ASC',
                ];

                $criteria = Criteria::addCriteriasForQuery($criteria, $params);

                $iterator = $DB->request($criteria);

                $tabdata = [];
                $tabnames = [];
                $tabdates = [];
                $nbtickets = __('Tickets number', 'mydashboard');
                if (count($iterator) > 0) {
                    foreach ($iterator as $data) {
                        $tabdata['data'][] = $data['nb'];
                        $tabdata['type'] = 'bar';
                        $tabdata['name'] = $nbtickets;
                        $tabnames[] = $data['period_name'];
                        $tabdates[] = $data['period'];
                    }
                }

                $widget = new Html();
                $title = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetTitle((($isDebug) ? "1 " : "") . $title);
                $widget->setWidgetComment($comment);
                $widget->toggleWidgetRefresh();

                $databacklogset = json_encode($tabdata);
                $labelsback = json_encode($tabnames);
                $tabdatesset = json_encode($tabdates);

                $graph_datas = [
                    'title' => $title,
                    'comment' => $comment,
                    'name' => $name,
                    'ids' => $tabdatesset,
                    'data' => $databacklogset,
                    'labels' => $labelsback,
                ];
                $graph_criterias = [];
                if ($onclick == 1) {
                    $criterias_values = Criteria::getGraphCriterias($params);
                    $graph_criterias = array_merge(['widget' => $widgetId], $criterias_values);
                }

                $graph = BarChart::launchGraph($graph_datas, $graph_criterias);

                $params = [
                    "widgetId" => $widgetId,
                    "name" => $name,
                    "onsubmit" => true,
                    "opt" => $opt,
                    "default" => $default,
                    "criterias" => $criterias,
                    "export" => true,
                    "canvas" => true,
                    "nb" => 1,
                ];

                $widget->setWidgetHeader(Helper::getGraphHeader($params));
                $widget->toggleWidgetRefresh();
                $widget->setWidgetHtmlContent($graph);

                return $widget;


            case $this->getType() . "8":
                $name = 'TimeByTechChart';

                $criterias = Criteria::getDefaultCriterias();

                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() == 'central') {
                    $onclick = 1;
                    $specific_criterias = [
                        Limit::$criteria_name,
                    ];
                    $criterias = array_merge($criterias, $specific_criterias);
                }
                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() != 'central') {
                    $specific_criterias = [
                        Limit::$criteria_name,
                    ];
                    $criterias = array_merge($criterias, $specific_criterias);
                }

                $params = [
                    "preferences" => $preferences,
                    "criterias" => $criterias,
                    "opt" => $opt,
                ];

                $default = Criteria::manageCriterias($params);

                $time_per_tech = self::getTimePerTech($params);

                $months_t = Toolbox::getMonthsOfYearArray();
                $months = [];
                foreach ($months_t as $key => $month) {
                    $months[] = $month;
                }

                $dataset = [];
                foreach ($time_per_tech as $tech_id => $times) {
                    unset($time_per_tech[$tech_id]);
                    $username = getUserName($tech_id);

                    $dataset[] = [
                        "name" => $username,
                        "data" => array_values($times),
                        "type" => "bar",
                        "stack" => "Ad",
                        "emphasis" => [
                            'focus' => 'series',
                        ],
                    ];
                }

                $widget = new Html();
                $title = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetTitle((($isDebug) ? "8 " : "") . $title);
                $widget->setWidgetComment($comment);
                $widget->toggleWidgetRefresh();

                $dataLineset = json_encode($dataset);
                $labelsLine = json_encode($months);

                $graph_datas = [
                    'title' => $title,
                    'comment' => $comment,
                    'name' => $name,
                    'ids' => json_encode([]),
                    'data' => $dataLineset,
                    'labels' => $labelsLine,
                ];

                $graph = BarChart::launchGraph($graph_datas, []);

                $params = [
                    "widgetId" => $widgetId,
                    "name" => $name,
                    "onsubmit" => true,
                    "opt" => $opt,
                    "default" => $default,
                    "criterias" => $criterias,
                    "export" => true,
                    "canvas" => true,
                    "nb" => count($dataset),
                ];
                $widget->setWidgetHeader(Helper::getGraphHeader($params));

                $widget->setWidgetHtmlContent(
                    $graph
                );

                return $widget;

            case $this->getType() . "15":
                $name = 'TopTenTicketCategoriesBarChart';
                $onclick = 0;

                $criterias = Criteria::getDefaultCriterias();
                $criterias = array_filter($criterias, fn($v) => $v !== Year::$criteria_name);

                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() == 'central') {
                    $onclick = 1;
                    $specific_criterias = [
                        FilterDate::$criteria_name,
                        RequesterGroup::$criteria_name,
                        Limit::$criteria_name,
                    ];
                    $criterias = array_merge($criterias, $specific_criterias);
                }
                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() != 'central') {
                    $specific_criterias = [
                        FilterDate::$criteria_name,
                        RequesterGroup::$criteria_name,
                        Limit::$criteria_name,
                    ];
                    $criterias = array_merge($criterias, $specific_criterias);
                }


                $params = [
                    "preferences" => $preferences,
                    "criterias" => $criterias,
                    "opt" => $opt,
                ];

                $default = Criteria::manageCriterias($params);

                $limit = $opt['limit'] ?? $default['limit'];

                $is_deleted = ['glpi_tickets.is_deleted' => 0];

                $date_criteria = [];
                $year = $opt['year'] ?? $default['filter_date'];

                if ($year) {
                    $date_criteria = [
                        ['glpi_tickets.date' => ['>=', "$year-01-01 00:00:00"]],
                        ['glpi_tickets.date' => ['<', new QueryExpression("DATE_ADD('$year-01-01', INTERVAL 1 YEAR)")]],
                    ];
                }
                if (isset($opt['filter_date'])
                    && $opt['filter_date'] == 'BEGIN_END'
                    && isset($opt['begin'])
                    && isset($opt['end'])) {
                    $begin = $opt['begin'];
                    $end = $opt['end'];
                    $date_criteria = [
                        ['glpi_tickets.date' => ['>=', "$begin"]],
                        ['glpi_tickets.date' => ['<', new QueryExpression("DATE_ADD('$end', INTERVAL 1 DAY)")]],
                    ];
                }

                $criteria = [
                    'SELECT' => ['glpi_itilcategories.completename AS itilcategories_name',
                        'COUNT' => 'glpi_tickets.id AS count',
                        'glpi_itilcategories.id AS catID',
                    ],
                    'FROM' => 'glpi_tickets',
                    'LEFT JOIN'       => [
                        'glpi_itilcategories' => [
                            'ON' => [
                                'glpi_tickets' => 'itilcategories_id',
                                'glpi_itilcategories'          => 'id',
                            ],
                        ],
                    ],
                    'WHERE' => [
                        $is_deleted,
                    ],
                    'GROUPBY' => 'glpi_itilcategories.id',
                    'ORDERBY' => 'count DESC',
                    'LIMIT' => $limit,
                ];

                $criteria = Criteria::addCriteriasForQuery($criteria, $params);
                if ($year
                    || isset($opt['filter_date']) && $opt['filter_date'] == 'BEGIN_END') {
                    $criteria['WHERE'] = array_merge($criteria['WHERE'], $date_criteria);

                }
                $iterator = $DB->request($criteria);

                $tabdata = [];
                $tabnames = [];
                $tabcat = [];
                if (count($iterator) > 0) {
                    foreach ($iterator as $data) {
                        $tabdata[] = $data['count'];
                        $itilcategories_name = $data['itilcategories_name'];
                        if ($data['itilcategories_name'] == null) {
                            $itilcategories_name = __('None');
                        }
                        $tabnames[] = $itilcategories_name;
                        $tabcat[] = $data["catID"];
                    }
                }

                $nbtickets = __('Tickets number', 'mydashboard');
                $dataset[] = [
                    "type" => 'bar',
                    "name" => $nbtickets,
                    "data" => $tabdata,
                ];

                $widget = new Html();
                $title = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetTitle((($isDebug) ? "15 " : "") . $title);
                $widget->setWidgetComment($comment);
                $widget->toggleWidgetRefresh();

                $databacklogset = json_encode($dataset);
                $labelsback = json_encode($tabnames);
                $idsback = json_encode($tabcat);

                $graph_datas = [
                    'title' => $title,
                    'comment' => $comment,
                    'name' => $name,
                    'ids' => $idsback,
                    'data' => $databacklogset,
                    //                                'label'           => $title,
                    'labels' => $labelsback,
                ];

                $graph_criterias = [];
                if ($onclick == 1) {
                    $criterias_values = Criteria::getGraphCriterias($params);
                    $graph_criterias = array_merge(['widget' => $widgetId], $criterias_values);
                }

                $graph = BarChart::launchHorizontalGraph($graph_datas, $graph_criterias);

                $params = [
                    "widgetId" => $widgetId,
                    "name" => $name,
                    "onsubmit" => true,
                    "opt" => $opt,
                    "default" => $default,
                    "criterias" => $criterias,
                    "export" => true,
                    "canvas" => true,
                    "nb" => count($iterator),
                ];
                $widget->setWidgetHeader(Helper::getGraphHeader($params));
                $widget->setWidgetHtmlContent($graph);

                return $widget;
                break;

            case $this->getType() . "21":
                $name = 'TicketsByTechChart';

                $criterias = Criteria::getDefaultCriterias();

                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() == 'central') {
                    $onclick = 1;
                    $specific_criterias = [
                        Limit::$criteria_name,
                    ];
                    $criterias = array_merge($criterias, $specific_criterias);
                }
                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() != 'central') {
                    $specific_criterias = [
                        Limit::$criteria_name,
                    ];
                    $criterias = array_merge($criterias, $specific_criterias);
                }

                $params = [
                    "preferences" => $preferences,
                    "criterias" => $criterias,
                    "opt" => $opt,
                ];

                $default = Criteria::manageCriterias($params);

                $tickets_per_tech = self::getTicketsPerTech($params);

                $months_t = Toolbox::getMonthsOfYearArray();
                $months = [];
                foreach ($months_t as $key => $month) {
                    $months[] = $month;
                }

                $dataset = [];
                foreach ($tickets_per_tech as $tech_id => $tickets) {
                    unset($tickets_per_tech[$tech_id]);
                    $username = getUserName($tech_id);
                    $dataset[] = [
                        "name" => $username,
                        "data" => array_values($tickets),
                        "type" => "bar",
                        "stack" => "Ad",
                        "emphasis" => [
                            'focus' => 'series',
                        ],
                    ];
                }

                $widget = new Html();
                $title = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetTitle((($isDebug) ? "21 " : "") . $title);
                $widget->setWidgetComment($comment);
                $widget->toggleWidgetRefresh();

                $dataLineset = json_encode($dataset);
                $labelsLine = json_encode($months);

                $graph_datas = [
                    'title' => $title,
                    'comment' => $comment,
                    'name' => $name,
                    'ids' => json_encode([]),
                    'data' => $dataLineset,
                    'labels' => $labelsLine,
                ];

                $graph = BarChart::launchGraph($graph_datas, []);

                $params = [
                    "widgetId" => $widgetId,
                    "name" => $name,
                    "onsubmit" => true,
                    "opt" => $opt,
                    "default" => $default,
                    "criterias" => $criterias,
                    "export" => true,
                    "canvas" => true,
                    "nb" => count($dataset),
                ];
                $widget->setWidgetHeader(Helper::getGraphHeader($params));
                $widget->setWidgetHtmlContent(
                    $graph
                );

                return $widget;

            case $this->getType() . "23":
                $name = 'AverageBarChart';

                $criterias = Criteria::getDefaultCriterias();

                $params = [
                    "preferences" => $preferences,
                    "criterias" => $criterias,
                    "opt" => $opt,
                ];
                $default = Criteria::manageCriterias($params);

                $currentyear = $opt["year"] ?? $default["year"];
                $currentmonth = date("m");

                $previousyear = $currentyear - 1;
                if (($currentmonth + 1) >= 12) {
                    $nextmonth = "01";
                } else {
                    $nextmonth = $currentmonth + 1;
                }

                $is_deleted = ['glpi_tickets.is_deleted' => 0];

                $criteria = [
                    'SELECT' => [
                        new QueryExpression("DATE_FORMAT(" . $DB->quoteName("glpi_tickets.date") . ", '%Y-%m') AS month"),
                        new QueryExpression("DATE_FORMAT(" . $DB->quoteName("glpi_tickets.date") . ", '%b %Y') AS monthname"),
                        new QueryExpression("DATE_FORMAT(" . $DB->quoteName("glpi_tickets.date") . ", '%Y%m') AS monthnum"),
                    ],
                    'FROM' => 'glpi_tickets',
                    'WHERE' => [
                        $is_deleted,
                        [
                            ['glpi_tickets.date' => ['>=', "$previousyear-$currentmonth-01 00:00:00"]],
                            ['glpi_tickets.date' => ['<=', "$currentyear-$nextmonth-01 00:00:00"]],
                        ],
                    ],
                    'GROUPBY' => 'month',
                ];

                $criteria = Criteria::addCriteriasForQuery($criteria, $params);

                //                $query = "SELECT
                //                              DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m') as month,
                //                              DATE_FORMAT(`glpi_tickets`.`date`, '%b %Y') as monthname,
                //                              DATE_FORMAT(`glpi_tickets`.`date`, '%Y%m') AS monthnum
                //                              FROM `glpi_tickets`
                //                              WHERE $is_deleted
                //                                AND (`glpi_tickets`.`date` >= '$previousyear-$currentmonth-01 00:00:00')
                //                              AND (`glpi_tickets`.`date` <= '$currentyear-$nextmonth-01 00:00:00')
                //                              " . $entities_criteria . $type_criteria . "
                //                              GROUP BY DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m')";

                // Single aggregated query replacing the outer loop + N inner queries
                $criteria_tasks_agg = [
                    'SELECT' => [
                        new QueryExpression("DATE_FORMAT(" . $DB->quoteName("glpi_tickets.date") . ", '%Y-%m') AS month"),
                        new QueryExpression("DATE_FORMAT(" . $DB->quoteName("glpi_tickets.date") . ", '%b %Y') AS monthname"),
                        new QueryExpression("DATE_FORMAT(" . $DB->quoteName("glpi_tickets.date") . ", '%Y%m') AS monthnum"),
                        new QueryExpression("COUNT(DISTINCT " . $DB->quoteName("glpi_tickets.id") . ") AS nb_tickets"),
                        new QueryExpression("SUM(" . $DB->quoteName("glpi_tickettasks.actiontime") . ") AS total_actiontime"),
                    ],
                    'FROM' => 'glpi_tickettasks',
                    'LEFT JOIN' => [
                        'glpi_tickets' => [
                            'ON' => [
                                'glpi_tickettasks' => 'tickets_id',
                                'glpi_tickets' => 'id',
                            ],
                        ],
                    ],
                    'WHERE' => [
                        $is_deleted,
                        [
                            ['glpi_tickets.date' => ['>=', "$previousyear-$currentmonth-01 00:00:00"]],
                            ['glpi_tickets.date' => ['<=', "$currentyear-$nextmonth-01 00:00:00"]],
                        ],
                    ],
                    'GROUPBY' => 'month',
                    'ORDERBY' => 'monthnum ASC',
                ];
                $criteria_tasks_agg = Criteria::addCriteriasForQuery($criteria_tasks_agg, $params);

                $tabduration = [];
                $tabdates = [];
                $tabnames = [];
                foreach ($DB->request($criteria_tasks_agg) as $data) {
                    $average_by_ticket = 0;
                    if ($data['nb_tickets'] > 0 && $data['total_actiontime'] > 0) {
                        $average_by_ticket = ($data['total_actiontime'] / $data['nb_tickets']) / 60;
                    }
                    $tabduration['data'][] = round($average_by_ticket ?? 0, 2, PHP_ROUND_HALF_UP);
                    $tabduration['type'] = 'bar';
                    $tabduration['name'] = __('Tasks duration (minutes)', 'mydashboard');
                    $tabnames[] = $data['monthname'];
                    $tabdates[] = $data['monthnum'];
                }

                $widget = new Html();
                $title = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetTitle((($isDebug) ? "23 " : "") . $title);
                $widget->setWidgetComment($comment);
                $widget->toggleWidgetRefresh();

                $dataLineset = json_encode($tabduration);
                $labelsLine = json_encode($tabnames);
                $tabdatesset = json_encode($tabdates);


                $graph_datas = [
                    'title' => $title,
                    'comment' => $comment,
                    'name' => $name,
                    'ids' => $tabdatesset,
                    'data' => $dataLineset,
                    'labels' => $labelsLine,
                ];

                $graph_criterias = [];

                $graph = BarChart::launchGraph($graph_datas, []);

                $params = [
                    "widgetId" => $widgetId,
                    "name" => $name,
                    "onsubmit" => false,
                    "opt" => $opt,
                    "default" => $default,
                    "criterias" => $criterias,
                    "export" => true,
                    "canvas" => true,
                    "nb" => 1,
                ];
                $widget->setWidgetHeader(Helper::getGraphHeader($params));
                $widget->setWidgetHtmlContent(
                    $graph
                );

                return $widget;


            case $this->getType() . "24":
                $name = 'TicketByTechsBarChart';
                $onclick = 0;

                $criterias = Criteria::getDefaultCriterias();
                $criterias = array_filter($criterias, fn($v) => $v !== Year::$criteria_name);

                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() == 'central') {
                    $onclick = 1;
                    $specific_criterias = [
                        FilterDate::$criteria_name,
                        Limit::$criteria_name,
                    ];

                    $criterias = array_merge($criterias, $specific_criterias);
                }
                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() != 'central') {
                    $specific_criterias = [
                        FilterDate::$criteria_name,
                        Limit::$criteria_name,
                    ];
                    $criterias = array_merge($criterias, $specific_criterias);
                }

                $params = [
                    "preferences" => $preferences,
                    "criterias" => $criterias,
                    "opt" => $opt,
                ];

                $default = Criteria::manageCriterias($params);

                $is_deleted = ['glpi_tickets.is_deleted' => 0];

                $limit = $opt['limit'] ?? $default['limit'];

                $date_criteria = [];
                $year = $opt['year'] ?? $default['filter_date'];

                if ($year) {
                    $date_criteria = [
                        ['glpi_tickets.date' => ['>=', "$year-01-01 00:00:00"]],
                        ['glpi_tickets.date' => ['<', new QueryExpression("DATE_ADD('$year-01-01', INTERVAL 1 YEAR)")]],
                    ];
                }
                if (isset($opt['filter_date'])
                    && $opt['filter_date'] == 'BEGIN_END'
                    && isset($opt['begin'])
                    && isset($opt['end'])) {
                    $begin = $opt['begin'];
                    $end = $opt['end'];
                    $date_criteria = [
                        ['glpi_tickets.date' => ['>=', "$begin"]],
                        ['glpi_tickets.date' => ['<', new QueryExpression("DATE_ADD('$end', INTERVAL 1 DAY)")]],
                    ];
                }

                $users_id_select = new QueryExpression('IFNULL(' . $DB::quoteName("glpi_tickets_users.users_id") . ',-1) AS users_id');
                $criteria = [
                    'SELECT' => [$users_id_select,
                        'COUNT' => 'glpi_tickets.id AS count',
                    ],
                    'FROM' => 'glpi_tickets',
                    'LEFT JOIN'       => [
                        'glpi_tickets_users' => [
                            'ON' => [
                                'glpi_tickets_users' => 'tickets_id',
                                'glpi_tickets'          => 'id',
                            ],
                        ],
                    ],
                    'WHERE' => [
                        $is_deleted,
                        'glpi_tickets_users.type' => CommonITILActor::ASSIGN,
                    ],
                    'GROUPBY' => 'glpi_tickets_users.users_id',
                    'ORDERBY' => 'count DESC',
                    'LIMIT' => $limit,
                ];

                $criteria = Criteria::addCriteriasForQuery($criteria, $params);

                if ($year
                    || isset($opt['filter_date']) && $opt['filter_date'] == 'BEGIN_END') {
                    $criteria['WHERE'] = array_merge($criteria['WHERE'], $date_criteria);

                }

                $iterator = $DB->request($criteria);

                $tabtickets = [];
                $tabtech = [];
                $tabtechName = [];
                $tabtechid = [];
                foreach ($iterator as $data) {
                    //                    $tabtickets[] = $data['count'];

                    $tabtickets['data'][] = $data['count'];
                    $tabtickets['type'] = 'bar';
                    $tabtickets['name'] = __('Tickets number', 'mydashboard');

                    $users_id = getUserName($data['users_id']);
                    if ($data['users_id'] == -1) {
                        $users_id = __('None');
                    }
                    if ($data['users_id'] == 0) {
                        $users_id = __('Email');
                    }
                    $tabtechName[] = $users_id;
                    $tabtechid[] = $data['users_id'];
                }

                $widget = new Html();
                $title = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetTitle((($isDebug) ? "24 " : "") . $title);
                $widget->setWidgetComment($comment);
                $widget->toggleWidgetRefresh();

                $dataticketset = json_encode($tabtickets);
                $tabNamesset = json_encode($tabtechName);
                $tabIdTechset = json_encode($tabtechid);


                $graph_datas = [
                    'title' => $title,
                    'comment' => $comment,
                    'name' => $name,
                    'ids' => $tabIdTechset,
                    'data' => $dataticketset,
                    'labels' => $tabNamesset,
                ];

                $graph_criterias = [];
                if ($onclick == 1) {
                    $criterias_values = Criteria::getGraphCriterias($params);
                    $graph_criterias = array_merge(['widget' => $widgetId], $criterias_values);
                }
                $graph = BarChart::launchHorizontalGraph($graph_datas, $graph_criterias);

                $params = [
                    "widgetId" => $widgetId,
                    "name" => $name,
                    "onsubmit" => true,
                    "opt" => $opt,
                    "default" => $default,
                    "criterias" => $criterias,
                    "export" => true,
                    "canvas" => true,
                    "nb" => count($tabtickets),
                ];
                $widget->setWidgetHeader(Helper::getGraphHeader($params));
                $widget->toggleWidgetRefresh();
                $widget->setWidgetHtmlContent(
                    $graph
                );

                return $widget;
                break;

            case $this->getType() . "35":
                $name = 'AgeBarChart';
                $onclick = 0;

                $criterias = Criteria::getDefaultCriterias();

                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() == 'central') {
                    $onclick = 1;
                    $specific_criterias = [
                        ITILCategory::$criteria_name,
                    ];
                    $criterias = array_merge($criterias, $specific_criterias);
                }

                // Le rapport d'âge définit ses propres tranches de dates — le filtre Year
                // entrerait en conflit avec les buckets (ex. "> 6 Mois" + filtre Jan 2026 → 0)
                $criterias = array_values(array_filter($criterias, fn($c) => $c !== Year::$criteria_name));

                $params = [
                    "preferences" => $preferences,
                    "criterias" => $criterias,
                    "opt" => $opt,
                ];
                $default = Criteria::manageCriterias($params);


                $is_deleted = ['glpi_tickets.is_deleted' => 0];
                //                $criteria_init
                $criteria_init = [
                    'SELECT' => [
                        'COUNT' => 'glpi_tickets.id AS Total',
                    ],
                    'FROM' => 'glpi_tickets',
                    'WHERE' => [
                        $is_deleted,
                        'status' => \Ticket::getNotSolvedStatusArray(),
                    ],
                ];

                $criteria_init = Criteria::addCriteriasForQuery($criteria_init, $params);


                //                $query = "SELECT  CONCAT ('< 1 Semaine') Age, COUNT(*) Total, COUNT(*) * 100 /
                //                (SELECT COUNT(*) FROM glpi_tickets
                //                                 WHERE $is_deleted $type_criteria $technician_groups_criteria $entities_criteria $categories_criteria
                //                                 AND `glpi_tickets`.`status` NOT IN ('" . \Ticket::CLOSED . "', '" . \Ticket::SOLVED . "')) Percent,
                //                CURRENT_TIMESTAMP - INTERVAL 1 WEEK as period_begin,
                //                CURRENT_TIMESTAMP - INTERVAL 1 WEEK as period_end
                //                FROM glpi_tickets
                //                WHERE glpi_tickets.date > CURRENT_TIMESTAMP - INTERVAL 1 WEEK
                //                AND $is_deleted
                //                 $type_criteria
                //                 $technician_groups_criteria
                //                 $entities_criteria
                //                 $categories_criteria
                //                AND `glpi_tickets`.`status` NOT IN ('" . \Ticket::CLOSED . "', '" . \Ticket::SOLVED . "')

                $criteria = [
                    'SELECT' => [
                        new QueryExpression("CONCAT('< 1 Semaine') Age"),
                        'COUNT' => 'glpi_tickets.id AS Total',
                        new QueryExpression("COUNT(*) * 100 / " . new QuerySubQuery($criteria_init, 'Percent')),
                        new QueryExpression("CURRENT_TIMESTAMP - INTERVAL 1 WEEK as period_begin"),
                        new QueryExpression("CURRENT_TIMESTAMP - INTERVAL 1 WEEK as period_end"),
                    ],
                    'FROM' => 'glpi_tickets',
                    'WHERE' => [
                        $is_deleted,
                        'status' => \Ticket::getNotSolvedStatusArray(),
                        'glpi_tickets.date' => ['>', new QueryExpression("CURRENT_TIMESTAMP - INTERVAL 1 WEEK")],
                    ],
                ];

                $criteria = Criteria::addCriteriasForQuery($criteria, $params);

                $queries[] = $criteria;

                //                SELECT CONCAT ('> 1 Semaine') Age, COUNT(*) Total, COUNT(*) * 100 /
                //            (SELECT COUNT(*) FROM glpi_tickets
                //                WHERE $is_deleted $type_criteria $technician_groups_criteria $entities_criteria $categories_criteria
                //            AND `glpi_tickets`.`status` NOT IN ('" . \Ticket::CLOSED . "', '" . \Ticket::SOLVED . "')) Percent,
                //                CURRENT_TIMESTAMP - INTERVAL 1 WEEK as period_begin,
                //                CURRENT_TIMESTAMP - INTERVAL 1 MONTH as period_end
                //                FROM glpi_tickets  WHERE glpi_tickets.date <= CURRENT_TIMESTAMP - INTERVAL 1 WEEK
                //            AND  glpi_tickets.date > CURRENT_TIMESTAMP - INTERVAL 1 MONTH
                //            AND $is_deleted
                //                 $type_criteria
                //                 $technician_groups_criteria
                //                 $entities_criteria
                //                 $categories_criteria
                //                 AND `glpi_tickets`.`status` NOT IN ('" . \Ticket::CLOSED . "', '" . \Ticket::SOLVED . "')
                //
                $criteria1 = [
                    'SELECT' => [
                        new QueryExpression("CONCAT('> 1 Semaine') Age"),
                        'COUNT' => 'glpi_tickets.id AS Total',
                        new QueryExpression("COUNT(*) * 100 / " . new QuerySubQuery($criteria_init, 'Percent')),
                        new QueryExpression("CURRENT_TIMESTAMP - INTERVAL 1 WEEK as period_begin"),
                        new QueryExpression("CURRENT_TIMESTAMP - INTERVAL 1 MONTH as period_end"),
                    ],
                    'FROM' => 'glpi_tickets',
                    'WHERE' => [
                        $is_deleted,
                        'status' => \Ticket::getNotSolvedStatusArray(),
                        [
                            ['glpi_tickets.date' => ['<=', new QueryExpression("CURRENT_TIMESTAMP - INTERVAL 1 WEEK")]],
                            ['glpi_tickets.date' => ['>', new QueryExpression("CURRENT_TIMESTAMP - INTERVAL 1 MONTH")]],
                        ],
                    ],
                ];

                $criteria1 = Criteria::addCriteriasForQuery($criteria1, $params);

                $queries[] = $criteria1;

                //                SELECT CONCAT ('> 1 Mois') Age, COUNT(*) Total, COUNT(*) * 100 /
                //                (SELECT COUNT(*) FROM glpi_tickets
                //                WHERE $is_deleted $type_criteria $technician_groups_criteria $entities_criteria $categories_criteria
                //                AND `glpi_tickets`.`status` NOT IN ('" . \Ticket::CLOSED . "', '" . \Ticket::SOLVED . "')) Percent,
                //                CURRENT_TIMESTAMP - INTERVAL 1 MONTH as period_begin,
                //                CURRENT_TIMESTAMP - INTERVAL 3 MONTH as period_end
                //                FROM glpi_tickets  WHERE glpi_tickets.date <= CURRENT_TIMESTAMP - INTERVAL 1 MONTH
                //                AND  glpi_tickets.date > CURRENT_TIMESTAMP - INTERVAL 3 MONTH
                //                AND $is_deleted
                //                 $type_criteria
                //                 $technician_groups_criteria
                //                 $entities_criteria
                //                 $categories_criteria
                //                AND `glpi_tickets`.`status` NOT IN ('" . \Ticket::CLOSED . "', '" . \Ticket::SOLVED . "')


                $criteria2 = [
                    'SELECT' => [
                        new QueryExpression("CONCAT('> 1 Mois') Age"),
                        'COUNT' => 'glpi_tickets.id AS Total',
                        new QueryExpression("COUNT(*) * 100 / " . new QuerySubQuery($criteria_init, 'Percent')),
                        new QueryExpression("CURRENT_TIMESTAMP - INTERVAL 1 MONTH as period_begin"),
                        new QueryExpression("CURRENT_TIMESTAMP - INTERVAL 3 MONTH as period_end"),
                    ],
                    'FROM' => 'glpi_tickets',
                    'WHERE' => [
                        $is_deleted,
                        'status' => \Ticket::getNotSolvedStatusArray(),
                        [
                            ['glpi_tickets.date' => ['<=', new QueryExpression("CURRENT_TIMESTAMP - INTERVAL 1 MONTH")]],
                            ['glpi_tickets.date' => ['>', new QueryExpression("CURRENT_TIMESTAMP - INTERVAL 3 MONTH")]],
                        ],
                    ],
                ];

                $criteria2 = Criteria::addCriteriasForQuery($criteria2, $params);

                $queries[] = $criteria2;

                //                SELECT CONCAT ('> 3 Mois') Age, COUNT(*) Total, COUNT(*) * 100 /
                //                (SELECT COUNT(*) FROM glpi_tickets
                //                WHERE $is_deleted $type_criteria $technician_groups_criteria $entities_criteria $categories_criteria
                //                AND `glpi_tickets`.`status` NOT IN ('" . \Ticket::CLOSED . "', '" . \Ticket::SOLVED . "')) Percent,
                //                CURRENT_TIMESTAMP - INTERVAL 3 MONTH as period_begin,
                //                CURRENT_TIMESTAMP - INTERVAL 6 MONTH as period_end
                //                FROM glpi_tickets  WHERE glpi_tickets.date <= CURRENT_TIMESTAMP - INTERVAL 3 MONTH
                //                AND  glpi_tickets.date > CURRENT_TIMESTAMP - INTERVAL 6 MONTH
                //                AND $is_deleted
                //                 $type_criteria
                //                 $technician_groups_criteria
                //                 $entities_criteria
                //                 $categories_criteria
                //                AND `glpi_tickets`.`status` NOT IN ('" . \Ticket::CLOSED . "', '" . \Ticket::SOLVED . "')

                $criteria3 = [
                    'SELECT' => [
                        new QueryExpression("CONCAT('> 3 Mois') Age"),
                        'COUNT' => 'glpi_tickets.id AS Total',
                        new QueryExpression("COUNT(*) * 100 / " . new QuerySubQuery($criteria_init, 'Percent')),
                        new QueryExpression("CURRENT_TIMESTAMP - INTERVAL 3 MONTH as period_begin"),
                        new QueryExpression("CURRENT_TIMESTAMP - INTERVAL 6 MONTH as period_end"),
                    ],
                    'FROM' => 'glpi_tickets',
                    'WHERE' => [
                        $is_deleted,
                        'status' => \Ticket::getNotSolvedStatusArray(),
                        [
                            ['glpi_tickets.date' => ['<=', new QueryExpression("CURRENT_TIMESTAMP - INTERVAL 3 MONTH")]],
                            ['glpi_tickets.date' => ['>', new QueryExpression("CURRENT_TIMESTAMP - INTERVAL 6 MONTH")]],
                        ],
                    ],
                ];

                $criteria3 = Criteria::addCriteriasForQuery($criteria3, $params);

                $queries[] = $criteria3;

                //                SELECT CONCAT ('> 6 Mois') Age, COUNT(*) Total, COUNT(*) * 100 /
                //                (SELECT COUNT(*) FROM glpi_tickets
                //                WHERE $is_deleted $type_criteria $technician_groups_criteria $entities_criteria $categories_criteria
                //                AND `glpi_tickets`.`status` NOT IN ('" . \Ticket::CLOSED . "', '" . \Ticket::SOLVED . "')) Percent,
                //                CURRENT_TIMESTAMP - INTERVAL 6 MONTH as period_begin,
                //                CURRENT_TIMESTAMP - INTERVAL 6 MONTH as period_end
                //                FROM glpi_tickets  WHERE glpi_tickets.date <= CURRENT_TIMESTAMP - INTERVAL 6 MONTH
                //                AND $is_deleted
                //                 $type_criteria
                //                 $technician_groups_criteria
                //                 $entities_criteria
                //                 $categories_criteria
                //                AND `glpi_tickets`.`status` NOT IN ('" . \Ticket::CLOSED . "', '" . \Ticket::SOLVED . "')";

                $criteria4 = [
                    'SELECT' => [
                        new QueryExpression("CONCAT('> 6 Mois') Age"),
                        'COUNT' => 'glpi_tickets.id AS Total',
                        new QueryExpression("COUNT(*) * 100 / " . new QuerySubQuery($criteria_init, 'Percent')),
                        new QueryExpression("CURRENT_TIMESTAMP - INTERVAL 6 MONTH as period_begin"),
                        new QueryExpression("CURRENT_TIMESTAMP - INTERVAL 6 MONTH as period_end"),
                    ],
                    'FROM' => 'glpi_tickets',
                    'WHERE' => [
                        $is_deleted,
                        'status' => \Ticket::getNotSolvedStatusArray(),
                        'glpi_tickets.date' => ['<=', new QueryExpression("CURRENT_TIMESTAMP - INTERVAL 6 MONTH")],
                    ],
                ];

                $criteria4 = Criteria::addCriteriasForQuery($criteria4, $params);

                $queries[] = $criteria4;

                $union = new QueryUnion($queries, true);
                $criteria_final = [
                    'SELECT' => [],
                    'FROM'   => $union,
                ];

                $iterator = $DB->request($criteria_final);

                $tabage = [];
                $tabnames = [];
                foreach ($iterator as $data) {
                    $tabnames[] = $data['Age'];
                    if (isset($data['period_end'])) {
                        $tabdate[] = $data['period_begin'] . "_" . $data['period_end'];
                    } else {
                        $tabdate[] = $data['period_begin'];
                    }

                    $tabage['data'][] = $data['Total'];
                    $tabage['type'] = 'bar';
                    $tabage['name'] = __("Not resolved tickets", "mydashboard");
                }

                $widget = new Html();
                $dataLineset = json_encode($tabage);
                $dataDateset = json_encode($tabdate);
                $labelsLine = json_encode($tabnames);

                $title = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetTitle((($isDebug) ? "35 " : "") . $title);
                $widget->setWidgetComment($comment);
                $widget->toggleWidgetRefresh();

                $graph_datas = [
                    'title' => $title,
                    'comment' => $comment,
                    'name' => $name,
                    'ids' => $dataDateset,
                    'data' => $dataLineset,
                    'labels' => $labelsLine,
                ];

                $graph_criterias = [];
                if ($onclick == 1) {
                    $criterias_values = Criteria::getGraphCriterias($params);
                    $graph_criterias = array_merge(['widget' => $widgetId], $criterias_values);
                }
                $graph = BarChart::launchGraph($graph_datas, $graph_criterias);

                $params = [
                    "widgetId" => $widgetId,
                    "name" => $name,
                    "onsubmit" => true,
                    "opt" => $opt,
                    "default" => $default,
                    "criterias" => $criterias,
                    "export" => true,
                    "canvas" => true,
                    "nb" => 1,
                ];
                $widget->setWidgetHeader(Helper::getGraphHeader($params));
                $widget->setWidgetHtmlContent(
                    $graph
                );

                return $widget;
                break;

            case $this->getType() . "36":
                $name = 'TicketsByPriorityBarChart';
                $onclick = 0;

                $criterias = Criteria::getDefaultCriterias();

                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() == 'central') {
                    $onclick = 1;
                    $specific_criterias = [
                        ITILCategory::$criteria_name,
                    ];
                    $criterias = array_merge($criterias, $specific_criterias);
                }

                $params = [
                    "preferences" => $preferences,
                    "criterias" => $criterias,
                    "opt" => $opt,
                ];
                $default = Criteria::manageCriterias($params);

                $is_deleted = ['glpi_tickets.is_deleted' => 0];

                $criteria = [
                    'SELECT' => ['glpi_tickets.priority',
                        'COUNT' => 'glpi_tickets.id AS nb',
                    ],
                    'DISTINCT'        => true,
                    'FROM' => 'glpi_tickets',
                    'LEFT JOIN'       => [
                        'glpi_itilcategories' => [
                            'ON' => [
                                'glpi_tickets' => 'itilcategories_id',
                                'glpi_itilcategories'          => 'id',
                            ],
                        ],
                    ],
                    'WHERE' => [
                        $is_deleted,
                        'status' => \Ticket::getNotSolvedStatusArray(),
                    ],
                    'GROUPBY' => 'priority',
                    'ORDERBY' => 'priority ASC',
                ];

                $criteria = Criteria::addCriteriasForQuery($criteria, $params);

                $iterator = $DB->request($criteria);
                $nb = count($iterator);

                $name_priority = [];
                $datas = [];
                $tabpriority = [];
                if ($nb) {
                    foreach ($iterator as $data) {
                        $name_priority[] = CommonITILObject::getPriorityName($data['priority']);
                        $datas['data'][] = $data['nb'];
                        $datas['type'] = 'bar';
                        $datas['name'] = __('Tickets number', 'mydashboard');

                        $tabpriority[] = $data['priority'];
                    }
                }

                $widget = new Html();
                $title = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetTitle((($isDebug) ? "36 " : "") . $title);
                $widget->setWidgetComment($comment);
                $widget->toggleWidgetRefresh();

                $params = [
                    "widgetId" => $widgetId,
                    "name" => $name,
                    "onsubmit" => true,
                    "opt" => $opt,
                    "default" => $default,
                    "criterias" => $criterias,
                    "export" => true,
                    "canvas" => true,
                    "nb" => 1,
                ];
                $widget->setWidgetHeader(Helper::getGraphHeader($params));

                $dataset = json_encode($datas);
                $labels = json_encode($name_priority);
                $tabpriorityset = json_encode($tabpriority);

                $graph_datas = [
                    'title' => $title,
                    'comment' => $comment,
                    'name' => $name,
                    'ids' => $tabpriorityset,
                    'data' => $dataset,
                    'labels' => $labels,
                ];
                $graph_criterias = [];
                if ($onclick == 1) {
                    $criterias_values = Criteria::getGraphCriterias($params);
                    $graph_criterias = array_merge(['widget' => $widgetId], $criterias_values);
                }
                $graph = BarChart::launchGraph($graph_datas, $graph_criterias);
                $widget->setWidgetHtmlContent($graph);

                return $widget;
                break;

            case $this->getType() . "37":
                $name = 'TicketsByStatusBarChart';
                $onclick = 0;

                $criterias = Criteria::getDefaultCriterias();

                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() == 'central') {
                    $onclick = 1;
                    $specific_criterias = [
                        ITILCategory::$criteria_name,
                    ];
                    $criterias = array_merge($criterias, $specific_criterias);
                }

                $params = [
                    "preferences" => $preferences,
                    "criterias" => $criterias,
                    "opt" => $opt,
                ];
                $default = Criteria::manageCriterias($params);

                $is_deleted = ['glpi_tickets.is_deleted' => 0];


                //                $query = "SELECT `glpi_tickets`.`status` AS status, COUNT(`glpi_tickets`.`id`) AS Total
                //                FROM glpi_tickets
                //                WHERE $is_deleted
                //                $type_criteria
                //                $technician_groups_criteria
                //                $entities_criteria
                //                $categories_criteria
                //                AND `glpi_tickets`.`status` IN (" . implode(",", \Ticket::getNotSolvedStatusArray()) . ")
                //                GROUP BY `glpi_tickets`.`status`";

                $criteria = [
                    'SELECT' => ['glpi_tickets.status AS status',
                        'COUNT' => 'glpi_tickets.id AS Total',
                    ],
                    'FROM' => 'glpi_tickets',
                    'LEFT JOIN'       => [
                        'glpi_itilcategories' => [
                            'ON' => [
                                'glpi_tickets' => 'itilcategories_id',
                                'glpi_itilcategories'          => 'id',
                            ],
                        ],
                    ],
                    'WHERE' => [
                        $is_deleted,
                        'status' => \Ticket::getNotSolvedStatusArray(),
                    ],
                    'GROUPBY' => 'glpi_tickets.status',
                ];

                $criteria = Criteria::addCriteriasForQuery($criteria, $params);

                $iterator = $DB->request($criteria);

                $nb = count($iterator);

                $name_status = [];
                $datas = [];
                $tabstatus = [];

                //TODO Add waiting types
                if ($nb) {
                    foreach ($iterator as $data) {
                        foreach (\Ticket::getAllStatusArray() as $value => $names) {
                            if ($data['status'] == $value) {
                                //                        $datas[]       = $data['Total'];
                                $datas['data'][] = $data['Total'];
                                $datas['type'] = 'bar';

                                $name_status[] = $names;
                                $tabstatus[] = $data['status'];
                            }
                        }
                    }
                }

                $widget = new Html();
                $title = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetTitle((($isDebug) ? "37 " : "") . $title);
                $widget->setWidgetComment($comment);
                $widget->toggleWidgetRefresh();

                $params = [
                    "widgetId" => $widgetId,
                    "name" => $name,
                    "onsubmit" => true,
                    "opt" => $opt,
                    "default" => $default,
                    "criterias" => $criterias,
                    "export" => true,
                    "canvas" => true,
                    "nb" => 1,
                ];
                $widget->setWidgetHeader(Helper::getGraphHeader($params));

                $dataset = json_encode($datas);
                $labels = json_encode($name_status);
                $tabstatusset = json_encode($tabstatus);

                $graph_datas = [
                    'title' => $title,
                    'comment' => $comment,
                    'name' => $name,
                    'ids' => $tabstatusset,
                    'data' => $dataset,
                    'labels' => $labels,
                ];
                $graph_criterias = [];
                if ($onclick == 1) {
                    $criterias_values = Criteria::getGraphCriterias($params);
                    $graph_criterias = array_merge(['widget' => $widgetId], $criterias_values);
                }
                $graph = BarChart::launchGraph($graph_datas, $graph_criterias);
                $widget->setWidgetHtmlContent($graph);

                return $widget;
                break;

            case $this->getType() . "38":
                $name = 'NumberOfOpenedTicketAndAverageSatisfactionPerTrimester';
                $criterias = [];
                $params = [
                    "preferences" => $preferences,
                    "criterias" => $criterias,
                    "opt" => $opt,
                ];
                $default = Criteria::manageCriterias($params);

                $is_deleted = ['glpi_tickets.is_deleted' => 0];

                $opened_tickets_data = [];
                $satisfaction_data = [];

                $tabnames = [];
                $starting_year = date('Y', strtotime('-2 year'));
                $ending_year = date('Y');
                for ($starting_year; $starting_year <= $ending_year; $starting_year++) {
                    // Checking T1
                    array_push($tabnames, __('Trimester 1', 'mydashboard') . ' ' . $starting_year);
                    // Number of tickets opened

                    $query_openedTicketT1 = [
                        'SELECT' => [
                            new QueryExpression("count(MONTH(" . $DB->quoteName("glpi_tickets.date") . ")) AS opened_tickets"),
                        ],
                        'FROM' => 'glpi_tickets',
                        'WHERE' => [
                            $is_deleted,
                            [
                                ['glpi_tickets.date' => ['>=', $starting_year . '-01-01 00:00:00']],
                                ['glpi_tickets.date' => ['<=', $starting_year . '-03-31 00:00:00']],
                            ],
                        ],
                    ];
                    $iteratorT1 = $DB->request($query_openedTicketT1);
                    foreach ($iteratorT1 as $dataT1) {
                        $opened_tickets_data['data'][] = round($dataT1['opened_tickets'] ?? 0, 2, PHP_ROUND_HALF_UP);
                    }
                    //                    $query_openedTicketT1 = "SELECT count(MONTH(`glpi_tickets`.`date`)) FROM `glpi_tickets`
                    //                                        WHERE $is_deleted
                    //                                        AND `glpi_tickets`.`date` between '$starting_year-01-01' AND '$starting_year-03-31'";

                    // Average Satisfaction
                    $query_satisfactionT1 = [
                        'SELECT' => [
                            new QueryExpression("AVG(" . $DB->quoteName("satisfaction") . ") AS satisfaction"),
                        ],
                        'FROM' => 'glpi_tickets',
                        'INNER JOIN'       => [
                            'glpi_ticketsatisfactions' => [
                                'ON' => [
                                    'glpi_tickets'   => 'id',
                                    'glpi_ticketsatisfactions'                  => 'tickets_id',
                                ],
                            ],
                        ],
                        'WHERE' => [
                            $is_deleted,
                            [
                                ['glpi_tickets.date' => ['>=', $starting_year . '-01-01 00:00:00']],
                                ['glpi_tickets.date' => ['<=', $starting_year . '-03-31 00:00:00']],
                            ],
                        ],
                    ];
                    $iteratorsatisfactionT1 = $DB->request($query_satisfactionT1);
                    foreach ($iteratorsatisfactionT1 as $data_satisfactionT1) {

                        $satisfaction_data['data'][] = round($data_satisfactionT1['satisfaction'] ?? 0, 2, PHP_ROUND_HALF_UP);
                    }

                    //                    $query_satisfactionT1 = "SELECT AVG(satisfaction)
                    //                                        FROM `glpi_tickets` INNER JOIN `glpi_ticketsatisfactions` ON `glpi_tickets`.`id` = `glpi_ticketsatisfactions`.`tickets_id`
                    //                                        WHERE `glpi_tickets`.`is_deleted` = 0
                    //                                        AND `glpi_tickets`.`date` between '$starting_year-01-01' AND '$starting_year-03-31'";


                    // Checking T2

                    array_push($tabnames, __('Trimester 2', 'mydashboard') . ' ' . $starting_year);

                    $query_openedTicketT2 = [
                        'SELECT' => [
                            new QueryExpression("count(MONTH(" . $DB->quoteName("glpi_tickets.date") . ")) AS opened_tickets"),
                        ],
                        'FROM' => 'glpi_tickets',
                        'WHERE' => [
                            $is_deleted,
                            [
                                ['glpi_tickets.date' => ['>=', $starting_year . '-04-01 00:00:00']],
                                ['glpi_tickets.date' => ['<=', $starting_year . '-06-30 00:00:00']],
                            ],
                        ],
                    ];
                    $iteratorT2 = $DB->request($query_openedTicketT2);
                    foreach ($iteratorT2 as $dataT2) {
                        $opened_tickets_data['data'][] = round($dataT2['opened_tickets'] ?? 0, 2, PHP_ROUND_HALF_UP);
                    }

                    //                    $query_openedTicketT2 = "SELECT count(MONTH(`glpi_tickets`.`date`)) FROM `glpi_tickets`
                    //                                        WHERE $is_deleted
                    //                                        AND `glpi_tickets`.`date` between '$starting_year-04-01' AND '$starting_year-06-30'";
                    //
                    //

                    // Average Satisfaction
                    $query_satisfactionT2 = [
                        'SELECT' => [
                            new QueryExpression("AVG(" . $DB->quoteName("satisfaction") . ") AS satisfaction"),
                        ],
                        'FROM' => 'glpi_tickets',
                        'INNER JOIN'       => [
                            'glpi_ticketsatisfactions' => [
                                'ON' => [
                                    'glpi_tickets'   => 'id',
                                    'glpi_ticketsatisfactions'                  => 'tickets_id',
                                ],
                            ],
                        ],
                        'WHERE' => [
                            $is_deleted,
                            [
                                ['glpi_tickets.date' => ['>=', $starting_year . '-04-01 00:00:00']],
                                ['glpi_tickets.date' => ['<=', $starting_year . '-06-30 00:00:00']],
                            ],
                        ],
                    ];
                    $iteratorsatisfactionT2 = $DB->request($query_satisfactionT2);
                    foreach ($iteratorsatisfactionT2 as $data_satisfactionT2) {
                        $satisfaction_data['data'][] = round($data_satisfactionT2['satisfaction'] ?? 0, 2, PHP_ROUND_HALF_UP);
                    }

                    //                    $query_satisfactionT2 = "SELECT AVG(satisfaction)
                    //                                        FROM `glpi_tickets` INNER JOIN `glpi_ticketsatisfactions` ON `glpi_tickets`.`id` = `glpi_ticketsatisfactions`.`tickets_id`
                    //                                        WHERE `glpi_tickets`.`is_deleted` = 0
                    //                                        AND `glpi_tickets`.`date` between '$starting_year-04-01' AND '$starting_year-06-30'";


                    // Checking T3
                    array_push($tabnames, __('Trimester 3', 'mydashboard') . ' ' . $starting_year);

                    $query_openedTicketT3 = [
                        'SELECT' => [
                            new QueryExpression("count(MONTH(" . $DB->quoteName("glpi_tickets.date") . ")) AS opened_tickets"),
                        ],
                        'FROM' => 'glpi_tickets',
                        'WHERE' => [
                            $is_deleted,
                            [
                                ['glpi_tickets.date' => ['>=', $starting_year . '-07-01 00:00:00']],
                                ['glpi_tickets.date' => ['<=', $starting_year . '-09-30 00:00:00']],
                            ],
                        ],
                    ];
                    $iteratorT3 = $DB->request($query_openedTicketT3);
                    foreach ($iteratorT3 as $dataT3) {
                        $opened_tickets_data['data'][] = round($dataT3['opened_tickets'] ?? 0, 2, PHP_ROUND_HALF_UP);
                    }

                    //                    $query_openedTicketT3 = "SELECT count(MONTH(`glpi_tickets`.`date`)) FROM `glpi_tickets`
                    //                                        WHERE $is_deleted
                    //                                        AND `glpi_tickets`.`date` between '$starting_year-06-01' AND '$starting_year-09-30'";


                    // Average Satisfaction

                    // Average Satisfaction
                    $query_satisfactionT3 = [
                        'SELECT' => [
                            new QueryExpression("AVG(" . $DB->quoteName("satisfaction") . ") AS satisfaction"),
                        ],
                        'FROM' => 'glpi_tickets',
                        'INNER JOIN'       => [
                            'glpi_ticketsatisfactions' => [
                                'ON' => [
                                    'glpi_tickets'   => 'id',
                                    'glpi_ticketsatisfactions'                  => 'tickets_id',
                                ],
                            ],
                        ],
                        'WHERE' => [
                            $is_deleted,
                            [
                                ['glpi_tickets.date' => ['>=', $starting_year . '-07-01 00:00:00']],
                                ['glpi_tickets.date' => ['<=', $starting_year . '-09-30 00:00:00']],
                            ],
                        ],
                    ];
                    $iteratorsatisfactionT3 = $DB->request($query_satisfactionT3);
                    foreach ($iteratorsatisfactionT3 as $data_satisfactionT3) {
                        $satisfaction_data['data'][] = round($data_satisfactionT3['satisfaction'] ?? 0, 2, PHP_ROUND_HALF_UP);
                    }

                    //                    $query_satisfactionT3 = "SELECT AVG(satisfaction)
                    //                                        FROM `glpi_tickets` INNER JOIN `glpi_ticketsatisfactions` ON `glpi_tickets`.`id` = `glpi_ticketsatisfactions`.`tickets_id`
                    //                                        WHERE `glpi_tickets`.`is_deleted` = 0
                    //                                        AND `glpi_tickets`.`date` between '$starting_year-06-01' AND '$starting_year-09-30'";
                    //

                    // Checking T4
                    array_push($tabnames, __('Trimester 4', 'mydashboard') . ' ' . $starting_year);

                    $query_openedTicketT4 = [
                        'SELECT' => [
                            new QueryExpression("count(MONTH(" . $DB->quoteName("glpi_tickets.date") . ")) AS opened_tickets"),
                        ],
                        'FROM' => 'glpi_tickets',
                        'WHERE' => [
                            $is_deleted,
                            [
                                ['glpi_tickets.date' => ['>=', $starting_year . '-10-01 00:00:00']],
                                ['glpi_tickets.date' => ['<=', $starting_year . '-12-31 00:00:00']],
                            ],
                        ],
                    ];
                    $iteratorT4 = $DB->request($query_openedTicketT4);
                    foreach ($iteratorT4 as $dataT4) {
                        $opened_tickets_data['data'][] = round($dataT4['opened_tickets'] ?? 0, 2, PHP_ROUND_HALF_UP);
                    }


                    // Average Satisfaction
                    $query_satisfactionT4 = [
                        'SELECT' => [
                            new QueryExpression("AVG(" . $DB->quoteName("satisfaction") . ") AS satisfaction"),
                        ],
                        'FROM' => 'glpi_tickets',
                        'INNER JOIN'       => [
                            'glpi_ticketsatisfactions' => [
                                'ON' => [
                                    'glpi_tickets'   => 'id',
                                    'glpi_ticketsatisfactions'                  => 'tickets_id',
                                ],
                            ],
                        ],
                        'WHERE' => [
                            $is_deleted,
                            [
                                ['glpi_tickets.date' => ['>=', $starting_year . '-10-01 00:00:00']],
                                ['glpi_tickets.date' => ['<=', $starting_year . '-12-31 00:00:00']],
                            ],
                        ],
                    ];
                    $iteratorsatisfactionT4 = $DB->request($query_satisfactionT4);
                    foreach ($iteratorsatisfactionT4 as $data_satisfactionT4) {
                        $satisfaction_data['data'][] = round($data_satisfactionT4['satisfaction'] ?? 0, 2, PHP_ROUND_HALF_UP);
                    }

                    //                    $query_satisfactionT4 = "SELECT AVG(satisfaction)
                    //                                        FROM `glpi_tickets` INNER JOIN `glpi_ticketsatisfactions` ON `glpi_tickets`.`id` = `glpi_ticketsatisfactions`.`tickets_id`
                    //                                        WHERE `glpi_tickets`.`is_deleted` = 0
                    //                                        AND `glpi_tickets`.`date` between '$starting_year-09-01' AND '$starting_year-12-31'";


                }

                $widget = new Html();
                $title = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetTitle((($isDebug) ? "38 " : "") . $title);
                $widget->setWidgetComment($comment);
                $widget->toggleWidgetRefresh();

                $titleOpenedTicket = __("Opened tickets", "mydashboard");
                $titleSatisfactionTicket = __("Average Satisfaction", "mydashboard");
                $labels = json_encode($tabnames);
                $datasets[]
                    = [
                        'type' => 'bar',
                        'data' => $opened_tickets_data['data'],
                        'name' => $titleOpenedTicket,
                    ];

                $datasets[]
                    = [
                        'type' => 'line',
                        'data' => $satisfaction_data['data'],
                        'name' => $titleSatisfactionTicket,
                        'smooth' => false,
                        'yAxisIndex' => 1,
                    ];

                $graph_datas = [
                    'title' => $title,
                    'comment' => $comment,
                    'name' => $name,
                    'ids' => json_encode([]),
                    'data' => json_encode($datasets),
                    'labels' => $labels,
                ];

                $graph = BarChart::launchGraph($graph_datas, []);

                $params = [
                    "widgetId" => $widgetId,
                    "name" => $name,
                    "onsubmit" => true,
                    "opt" => $opt,
                    "default" => $default,
                    "criterias" => $criterias,
                    "export" => true,
                    "canvas" => true,
                    "nb" => 1,
                ];
                $widget->setWidgetHeader(Helper::getGraphHeader($params));


                $widget->setWidgetHtmlContent($graph);
                return $widget;
                break;

            case $this->getType() . "39":
                $name = 'ResponsivenessRollingPendingByYear';

                $criterias = Criteria::getDefaultCriterias();

                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() == 'central') {
                    $onclick = 1;
                    $specific_criterias = [
                        RequesterGroup::$criteria_name,
                    ];
                    $criterias = array_merge($criterias, $specific_criterias);
                }
                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() != 'central') {
                    $specific_criterias = [
                        RequesterGroup::$criteria_name,
                    ];
                    $criterias = array_merge($criterias, $specific_criterias);
                }

                $params = [
                    "preferences" => $preferences,
                    "criterias" => $criterias,
                    "opt" => $opt,
                ];

                $default = Criteria::manageCriterias($params);

                $is_deleted = ['glpi_tickets.is_deleted' => 0];

                $datasets = [];

                $currentyear = date("Y");
                $currentmonth = date("m");

                if (isset($opt["year"]) && $opt["year"] > 0) {
                    $currentyear = $opt["year"];
                }

                $previousyear = $currentyear - 1;


                $datesTab = self::getAllMonthAndYear($currentyear, $currentmonth, $previousyear);

                $criteria_init = [
                    'SELECT' => [
                        new QueryExpression("DATE_FORMAT(" . $DB->quoteName("glpi_tickets.date") . ", '%Y-%m') AS month"),
                        new QueryExpression("DATE_FORMAT(" . $DB->quoteName("glpi_tickets.date") . ", '%b %Y') AS monthname"),
                        'COUNT' => 'glpi_tickets.id AS Total',
                    ],
                    'FROM' => 'glpi_tickets',
                    'WHERE' => [
                        $is_deleted,
                        'glpi_tickets.solve_delay_stat' => ['<=', 86400],
                        [
                            ['glpi_tickets.date' => ['>=',  "$previousyear-$currentmonth-01 00:00:00"]],
                            ['glpi_tickets.date' => ['<=', "$currentyear-$currentmonth-01 00:00:00"]],
                        ],
                        'glpi_tickets.status' => [CommonITILObject::SOLVED, CommonITILObject::CLOSED],
                    ],
                    'GROUPBY' => 'month',
                ];

                $criteria_init = Criteria::addCriteriasForQuery($criteria_init, $params);

                $criteria = [
                    'SELECT' => [
                        't1.Total as Total',
                        't1.monthname as Monthname',
                        't1.month',
                    ],
                    'FROM' => new QuerySubQuery($criteria_init, 't1'),
                ];

                $iterator = $DB->request($criteria);

                $tabTicketsLessThanOneDay = [];
                $tabTicketsLessThanOneDay['month_name'] = [];
                $tabTicketsLessThanOneDay['total'] = [];

                $i = 0;

                if (count($iterator) > 0) {
                    foreach ($iterator as $data) {
                        $i++;
                        foreach ($datesTab as $datePeriod) {
                            if (!array_key_exists('month', $tabTicketsLessThanOneDay)) {
                                if (!in_array($data['Monthname'], $tabTicketsLessThanOneDay['month_name'])
                                    && !in_array($datePeriod, $tabTicketsLessThanOneDay['month_name'])) {
                                    if ($data['Monthname'] !== $datePeriod) {
                                        $tabTicketsLessThanOneDay['month_name'][] = $datePeriod;
                                        $tabTicketsLessThanOneDay['total'][] = 0;
                                    } else {
                                        $tabTicketsLessThanOneDay['month_name'][] = $data['Monthname'];
                                        $tabTicketsLessThanOneDay['total'][] = $data['Total'];
                                    }
                                }
                            }
                        }
                        if ($i == count($iterator)) {
                            foreach ($datesTab as $datePeriod) {
                                if (!in_array($datePeriod, $tabTicketsLessThanOneDay['month_name'])) {
                                    $tabTicketsLessThanOneDay['month_name'][] = $datePeriod;
                                    $tabTicketsLessThanOneDay['total'][] = 0;
                                }
                            }
                        }
                    }
                }

                //                $query_tickets2 = "SELECT t1.Total as Total, t1.monthname as Monthname, t1.month FROM
                //                                              (SELECT  DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m') as month,
                //                                               DATE_FORMAT(`glpi_tickets`.`date`, '%b %Y') as monthname ,
                //                                               COUNT(*) Total FROM `glpi_tickets`  WHERE {$is_deleted} {$type_criteria}
                //                                                {$requester_groups_criteria}  {$status}
                //                                                AND `glpi_tickets`.`date` >=  '$previousyear-$currentmonth-01 00:00:01'
                //                                                AND `glpi_tickets`.`date` <= '$currentyear-$currentmonth-01'
                //                                                AND `glpi_tickets`.`solve_delay_stat` >=  86400
                //                                                AND  `glpi_tickets`.`solve_delay_stat` <=  604800 GROUP BY month) t1
                //                                                         ";

                $criteria_init_2 = [
                    'SELECT' => [
                        new QueryExpression("DATE_FORMAT(" . $DB->quoteName("glpi_tickets.date") . ", '%Y-%m') AS month"),
                        new QueryExpression("DATE_FORMAT(" . $DB->quoteName("glpi_tickets.date") . ", '%b %Y') AS monthname"),
                        'COUNT' => 'glpi_tickets.id AS Total',
                    ],
                    'FROM' => 'glpi_tickets',
                    'WHERE' => [
                        $is_deleted,
                        [
                            ['glpi_tickets.solve_delay_stat' => ['>=', 86400]],
                            ['glpi_tickets.solve_delay_stat' => ['<=', 604800]],
                        ],
                        [
                            ['glpi_tickets.date' => ['>=',  "$previousyear-$currentmonth-01 00:00:00"]],
                            ['glpi_tickets.date' => ['<=', "$currentyear-$currentmonth-01 00:00:00"]],
                        ],
                        'glpi_tickets.status' => [CommonITILObject::SOLVED, CommonITILObject::CLOSED],
                    ],
                    'GROUPBY' => 'month',
                ];

                $criteria_init_2 = Criteria::addCriteriasForQuery($criteria_init_2, $params);

                $criteria_2 = [
                    'SELECT' => [
                        't1.Total as Total',
                        't1.monthname as Monthname',
                        't1.month',
                    ],
                    'FROM' => new QuerySubQuery($criteria_init_2, 't1'),
                ];

                $iterator_2 = $DB->request($criteria_2);

                $nbResults = count($iterator_2);
                $tabTicketsBetweenOneDayAndOneWeek = [];
                $tabTicketsBetweenOneDayAndOneWeek['month_name'] = [];
                $tabTicketsBetweenOneDayAndOneWeek['total'] = [];
                $i = 0;

                if ($nbResults) {
                    foreach ($iterator_2 as $data) {
                        $i++;
                        foreach ($datesTab as $datePeriod) {
                            if (!array_key_exists('month', $tabTicketsBetweenOneDayAndOneWeek)) {
                                if (!in_array($data['Monthname'], $tabTicketsBetweenOneDayAndOneWeek['month_name'])
                                    && !in_array($datePeriod, $tabTicketsBetweenOneDayAndOneWeek['month_name'])) {
                                    if ($data['Monthname'] !== $datePeriod) {
                                        $tabTicketsBetweenOneDayAndOneWeek['month_name'][] = $datePeriod;
                                        $tabTicketsBetweenOneDayAndOneWeek['total'][] = 0;
                                    } else {
                                        $tabTicketsBetweenOneDayAndOneWeek['month_name'][] = $data['Monthname'];
                                        $tabTicketsBetweenOneDayAndOneWeek['total'][] = $data['Total'];
                                    }
                                }
                            }
                        }
                        if ($i == $nbResults) {
                            foreach ($datesTab as $datePeriod) {
                                if (!in_array($datePeriod, $tabTicketsBetweenOneDayAndOneWeek['month_name'])) {
                                    $tabTicketsBetweenOneDayAndOneWeek['month_name'][] = $datePeriod;
                                    $tabTicketsBetweenOneDayAndOneWeek['total'][] = 0;
                                }
                            }
                        }
                    }
                }

                //                $query_tickets3 = "SELECT t1.Total as Total, t1.monthname as Monthname, t1.month FROM
                //                                              (SELECT  DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m') as month,
                //                                               DATE_FORMAT(`glpi_tickets`.`date`, '%b %Y') as monthname ,
                //                                               COUNT(*) Total FROM `glpi_tickets`  WHERE {$is_deleted} {$type_criteria}
                //                                                 {$requester_groups_criteria}  {$status}
                //                                                AND `glpi_tickets`.`date` >=  '$previousyear-$currentmonth-01 00:00:01'
                //                                                  AND `glpi_tickets`.`date` <= '$currentyear-$currentmonth-01'
                //                                                    AND `glpi_tickets`.`solve_delay_stat` >=  604800 GROUP BY month) t1
                //                                                         ";


                $criteria_init_3 = [
                    'SELECT' => [
                        new QueryExpression("DATE_FORMAT(" . $DB->quoteName("glpi_tickets.date") . ", '%Y-%m') AS month"),
                        new QueryExpression("DATE_FORMAT(" . $DB->quoteName("glpi_tickets.date") . ", '%b %Y') AS monthname"),
                        'COUNT' => 'glpi_tickets.id AS Total',
                    ],
                    'FROM' => 'glpi_tickets',
                    'WHERE' => [
                        $is_deleted,
                        'glpi_tickets.solve_delay_stat' => ['>=', 604800],
                        [
                            ['glpi_tickets.date' => ['>=',  "$previousyear-$currentmonth-01 00:00:00"]],
                            ['glpi_tickets.date' => ['<=', "$currentyear-$currentmonth-01 00:00:00"]],
                        ],
                        'glpi_tickets.status' => [CommonITILObject::SOLVED, CommonITILObject::CLOSED],
                    ],
                    'GROUPBY' => 'month',
                ];

                $criteria_init_3 = Criteria::addCriteriasForQuery($criteria_init_3, $params);

                $criteria_3 = [
                    'SELECT' => [
                        't1.Total as Total',
                        't1.monthname as Monthname',
                        't1.month',
                    ],
                    'FROM' => new QuerySubQuery($criteria_init_3, 't1'),
                ];

                $iterator_3 = $DB->request($criteria_3);

                $nbResults = count($iterator_3);
                $tabTicketsMoreThanOneWeek = [];
                $tabTicketsMoreThanOneWeek['month_name'] = [];
                $tabTicketsMoreThanOneWeek['total'] = [];
                $i = 0;

                if ($nbResults) {
                    foreach ($iterator as $data) {
                        $i++;
                        foreach ($datesTab as $datePeriod) {
                            if (!array_key_exists('month', $tabTicketsMoreThanOneWeek)) {
                                if (!in_array(
                                    $data['Monthname'],
                                    $tabTicketsMoreThanOneWeek['month_name']
                                ) && !in_array($datePeriod, $tabTicketsMoreThanOneWeek['month_name'])) {
                                    if ($data['Monthname'] !== $datePeriod) {
                                        $tabTicketsMoreThanOneWeek['month_name'][] = $datePeriod;
                                        $tabTicketsMoreThanOneWeek['total'][] = 0;
                                    } else {
                                        $tabTicketsMoreThanOneWeek['month_name'][] = $data['Monthname'];
                                        $tabTicketsMoreThanOneWeek['total'][] = $data['Total'];
                                    }
                                }
                            }
                        }
                        if ($i == $nbResults) {
                            foreach ($datesTab as $datePeriod) {
                                if (!in_array($datePeriod, $tabTicketsMoreThanOneWeek['month_name'])) {
                                    $tabTicketsMoreThanOneWeek['month_name'][] = $datePeriod;
                                    $tabTicketsMoreThanOneWeek['total'][] = 0;
                                }
                            }
                        }
                    }
                }

                $widget = new Html();
                $title = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetTitle((($isDebug) ? "39 " : "") . $title);
                $widget->setWidgetComment($comment);
                $widget->toggleWidgetRefresh();

                $labels = json_encode($datesTab);

                $max = '';
                $max2 = '';
                $max3 = '';
                $max_tab = [];

                if (!empty($tabTicketsLessThanOneDay['total'])) {
                    array_push($max_tab, $max = max($tabTicketsLessThanOneDay['total']) + 100);
                }

                if (!empty($tabTicketsBetweenOneDayAndOneWeek['total'])) {
                    array_push($max_tab, $max = max($tabTicketsBetweenOneDayAndOneWeek['total']) + 100);
                }

                if (!empty($tabTicketsMoreThanOneWeek['total'])) {
                    array_push($max_tab, $max = max($tabTicketsMoreThanOneWeek['total']) + 100);
                }

                //                $maxFinal = max($max_tab);

                $datasets[]
                    = [
                        "type" => "bar",
                        "data" => $tabTicketsLessThanOneDay['total'],
                        "name" => __('Sum of tickets solved in less than 24 hours', "mydashboard"),
                        //                                  'backgroundColor' => '#BBD4F9',
                        //                        'yAxisID' => 'bar-y-axis'
                    ];

                $datasets[]
                    = [
                        "type" => "bar",
                        "data" => $tabTicketsBetweenOneDayAndOneWeek['total'],
                        "name" => __('Sum of tickets solved in less than a week', "mydashboard"),
                        //                                  'backgroundColor' => '#2B68C4',
                        //                        'yAxisID' => 'bar-y-axis'
                    ];

                $datasets[]
                    = [
                        "type" => "bar",
                        "data" => $tabTicketsMoreThanOneWeek['total'],
                        "name" => __('Sum of tickets solved in more than a week', "mydashboard"),
                        //                                  'backgroundColor' => '#033A5F',
                        //                        'yAxisID' => 'bar-y-axis'
                    ];

                $graph_datas = [
                    'title' => $title,
                    'comment' => $comment,
                    'name' => $name,
                    'ids' => json_encode([]),
                    'data' => json_encode($datasets),
                    'labels' => $labels,
                    'label' => $title,
                ];


                $graph = BarChart::launchGraph($graph_datas, []);

                $params = [
                    "widgetId" => $widgetId,
                    "name" => $name,
                    "onsubmit" => true,
                    "opt" => $opt,
                    "default" => $default,
                    "criterias" => $criterias,
                    "export" => true,
                    "canvas" => true,
                    "nb" => 1,
                ];
                $widget->setWidgetHeader(Helper::getGraphHeader($params));

                $widget->setWidgetHtmlContent(
                    $graph
                );

                return $widget;
                break;

            case $this->getType() . "40":

                $criterias = Criteria::getDefaultCriterias();

                $params = [
                    "preferences" => $preferences,
                    "criterias" => $criterias,
                    "opt" => $opt,
                ];
                $default = Criteria::manageCriterias($params);

                $is_deleted = ['glpi_tickets.is_deleted' => 0];

                $tabdata = [];
                $tabnames = [];
                $tabyears = [];
                $i = 0;

                $criteria = [
                    'SELECT' => [
                        new QueryExpression("DATE_FORMAT(" . $DB->quoteName("date") . ", '%Y') AS year"),
                        new QueryExpression("DATE_FORMAT(" . $DB->quoteName("date") . ", '%Y') AS yearname"),
                    ],
                    'FROM' => 'glpi_tickets',
                    'WHERE' => [
                        $is_deleted,
                    ],
                    'GROUPBY' => new QueryExpression("DATE_FORMAT(" . $DB->quoteName("date") . ", '%Y')"),
                ];

                $criteria = Criteria::addCriteriasForQuery($criteria, $params);

                $iterator = $DB->request($criteria);

                foreach ($iterator as $data) {
                    $year = $data['year'];

                    $criteria_1 = [
                        'SELECT' => [
                            'COUNT' => 'glpi_tickets.requesttypes_id AS count',
                            'glpi_requesttypes.name AS namerequest',
                            'glpi_tickets.requesttypes_id',
                        ],
                        'FROM' => 'glpi_tickets',
                        'LEFT JOIN'       => [
                            'glpi_requesttypes' => [
                                'ON' => [
                                    'glpi_tickets' => 'requesttypes_id',
                                    'glpi_requesttypes'          => 'id',
                                ],
                            ],
                        ],
                        'WHERE' => [
                            $is_deleted,
                            [
                                ['glpi_tickets.date' => ['<=', $year . '-12-31 23:59:59']],
                                ['glpi_tickets.date' => ['>', new QueryExpression("ADDDATE('$year-01-01 00:00:00' , INTERVAL 1 DAY)")]],
                            ],
                        ],
                        'GROUPBY' => 'glpi_tickets.requesttypes_id',
                    ];

                    $criteria_1 = Criteria::addCriteriasForQuery($criteria_1, $params);

                    $iterator_1 = $DB->request($criteria_1);

                    foreach ($iterator_1 as $data_1) {
                        $tabdata[$data_1['requesttypes_id']][$year] = $data_1['count'];
                        $tabnames[$data_1['requesttypes_id']] = $data_1['namerequest'];
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
                $datasets = [];

                foreach ($tabdata as $k => $v) {
                    $datasets[] = [
                        "name" => ($tabnames[$k] == null) ? __('None') : $tabnames[$k],
                        "data" => array_values($v),
                        "type" => "bar",
                        "stack" => "Ad",
                        "emphasis" => [
                            'focus' => 'series',
                        ],
                    ];
                }

                $widget = new Html();
                $title = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetTitle((($isDebug) ? "40 " : "") . $title);
                $widget->setWidgetComment($comment);
                $widget->toggleWidgetRefresh();

                $name = 'RequestTypeEvolutionLineChart';

                $jsonsets = json_encode($datasets);

                $graph_datas = [
                    'title' => $title,
                    'comment' => $comment,
                    'name' => $name,
                    'ids' => json_encode([]),
                    'data' => $jsonsets,
                    'labels' => $labelsLine,
                ];

                $graph = BarChart::launchGraph($graph_datas, []);


                $params = [
                    "widgetId" => $widgetId,
                    "name" => $name,
                    "onsubmit" => false,
                    "opt" => $opt,
                    "default" => $default,
                    "criterias" => $criterias,
                    "export" => true,
                    "canvas" => true,
                    "nb" => 1,
                ];
                $widget->setWidgetHeader(Helper::getGraphHeader($params));

                $widget->setWidgetHtmlContent(
                    $graph
                );

                return $widget;

            case $this->getType() . "41":

                $criterias = Criteria::getDefaultCriterias();

                $params = [
                    "preferences" => $preferences,
                    "criterias" => $criterias,
                    "opt" => $opt,
                ];
                $default = Criteria::manageCriterias($params);

                $is_deleted = ['glpi_tickets.is_deleted' => 0];

                $tabdata = [];
                $tabnames = [];
                $tabyears = [];
                $i = 0;

                $criteria = [
                    'SELECT' => [
                        new QueryExpression("DATE_FORMAT(" . $DB->quoteName("date") . ", '%Y') AS year"),
                        new QueryExpression("DATE_FORMAT(" . $DB->quoteName("date") . ", '%Y') AS yearname"),
                    ],
                    'FROM' => 'glpi_tickets',
                    'WHERE' => [
                        $is_deleted,
                    ],
                    'GROUPBY' => new QueryExpression("DATE_FORMAT(" . $DB->quoteName("date") . ", '%Y')"),
                ];

                $criteria = Criteria::addCriteriasForQuery($criteria, $params);

                $iterator = $DB->request($criteria);

                foreach ($iterator as $data) {
                    $year = $data['year'];

                    $criteria_1 = [
                        'SELECT' => [
                            'COUNT' => 'glpi_itilsolutions.solutiontypes_id AS count',
                            'glpi_solutiontypes.name AS namesolution',
                            'glpi_itilsolutions.solutiontypes_id',
                        ],
                        'FROM' => 'glpi_itilsolutions',
                        'LEFT JOIN'       => [
                            'glpi_tickets' => [
                                'ON' => [
                                    'glpi_itilsolutions'   => 'items_id',
                                    'glpi_tickets'                  => 'id', [
                                        'AND' => [
                                            'glpi_itilsolutions.itemtype' => 'Ticket',
                                        ],
                                    ],
                                ],
                            ],
                            'glpi_solutiontypes' => [
                                'ON' => [
                                    'glpi_itilsolutions' => 'solutiontypes_id',
                                    'glpi_solutiontypes'          => 'id',
                                ],
                            ],
                        ],
                        'WHERE' => [
                            $is_deleted,
                            [
                                ['glpi_tickets.date' => ['<=', $year . '-12-31 23:59:59']],
                                ['glpi_tickets.date' => ['>', new QueryExpression("ADDDATE('$year-01-01 00:00:00' , INTERVAL 1 DAY)")]],
                            ],
                        ],
                        'GROUPBY' => 'glpi_itilsolutions.solutiontypes_id',
                    ];

                    $criteria_1 = Criteria::addCriteriasForQuery($criteria_1, $params);

                    $iterator_1 = $DB->request($criteria_1);

                    foreach ($iterator_1 as $data_1) {
                        $tabdata[$data_1['solutiontypes_id']][$year] = $data_1['count'];
                        $tabnames[$data_1['solutiontypes_id']] = $data_1['namesolution'];
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
                $datasets = [];

                foreach ($tabdata as $k => $v) {
                    $datasets[]
                        = [
                            'data' => array_values($v),
                            'name' => ($tabnames[$k] == null) ? __('None') : $tabnames[$k],
                            'type' => 'bar',
                            'stack' => 'Ad',
                            'emphasis' => [
                                'focus' => 'series',
                            ],
                        ];
                }

                $widget = new Html();
                $title = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetTitle((($isDebug) ? "41 " : "") . $title);
                $widget->setWidgetComment($comment);
                $widget->toggleWidgetRefresh();

                $name = 'SolutionTypeEvolutionLineChart';

                $jsonsets = json_encode($datasets);

                $graph_datas = [
                    'title' => $title,
                    'comment' => $comment,
                    'name' => $name,
                    'ids' => json_encode([]),
                    'data' => $jsonsets,
                    'labels' => $labelsLine,
                ];

                $graph = BarChart::launchGraph($graph_datas, []);


                $params = [
                    "widgetId" => $widgetId,
                    "name" => $name,
                    "onsubmit" => false,
                    "opt" => $opt,
                    "default" => $default,
                    "criterias" => $criterias,
                    "export" => true,
                    "canvas" => true,
                    "nb" => 1,
                ];
                $widget->setWidgetHeader(Helper::getGraphHeader($params));

                $widget->setWidgetHtmlContent(
                    $graph
                );

                return $widget;


            case $this->getType() . "42":

                $name = 'reportLineLifeTimeAndTakenAccountAverageByMonthHelpdesk';

                $lifetime = __('Solve delay average (hour)', 'mydashboard');
                $taken_into_account = __('Take into account average (hour)', 'mydashboard');

                $criterias = Criteria::getDefaultCriterias();

                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() == 'central') {
                    $onclick = 1;
                    $specific_criterias = [
                        MultipleLocation::$criteria_name,
                        'is_recursive_locations',
                    ];
                    $criterias = array_merge($criterias, $specific_criterias);
                }
                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() != 'central') {
                    $specific_criterias = [
                        MultipleLocation::$criteria_name,
                        'is_recursive_locations',
                    ];
                    $criterias = array_merge($criterias, $specific_criterias);
                }

                $params = [
                    "preferences" => $preferences,
                    "criterias" => $criterias,
                    "opt" => $opt,
                ];

                $default = Criteria::manageCriterias($params);


                $lifetime_avg_ticket = self::getLifetimeOrTakeIntoAccountTicketAverage(
                    $params,
                );

                $months_t = Toolbox::getMonthsOfYearArray();
                $months = [];
                foreach ($months_t as $key => $month) {
                    $months[] = $month;
                }

                $dataset = [];
                $avg_lifetime_ticket_data = [];
                $avg_takeintoaccount_ticket_data = [];

                foreach ($lifetime_avg_ticket as $avg_tickets_d) {
                    if ($avg_tickets_d['nb'] > 0) {
                        $avg_lifetime_ticket_data [] = round(
                            ($avg_tickets_d['lifetime'] / $avg_tickets_d['nb']) ?? 0,
                            2,
                            PHP_ROUND_HALF_UP
                        );
                        $avg_takeintoaccount_ticket_data [] = round(
                            ($avg_tickets_d['takeintoaccount'] / $avg_tickets_d['nb']) ?? 0,
                            2,
                            PHP_ROUND_HALF_UP
                        );
                    } else {
                        $avg_lifetime_ticket_data [] = 0;
                        $avg_takeintoaccount_ticket_data [] = 0;
                    }
                }

                $avg_lifetime_ticket_data = array_values($avg_lifetime_ticket_data);
                $avg_takeintoaccount_ticket_data = array_values($avg_takeintoaccount_ticket_data);

                $dataset[] = [
                    "name" => $lifetime,
                    "data" => $avg_lifetime_ticket_data,
                    "type" => "bar",
                ];

                $dataset[] = [
                    "name" => $taken_into_account,
                    "data" => $avg_takeintoaccount_ticket_data,
                    "type" => "bar",
                ];

                $widget = new Html();
                $title = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetTitle((($isDebug) ? "42 " : "") . $title);
                $widget->setWidgetComment($comment);
                $widget->toggleWidgetRefresh();

                $dataLineset = json_encode($dataset);
                $labelsLine = json_encode($months);

                $graph_datas = [
                    'title' => $title,
                    'comment' => $comment,
                    'name' => $name,
                    'ids' => json_encode([]),
                    'data' => $dataLineset,
                    'labels' => $labelsLine,
                ];


                $graph = BarChart::launchGraph($graph_datas, []);

                $params = [
                    "widgetId" => $widgetId,
                    "name" => $name,
                    "onsubmit" => true,
                    "opt" => $opt,
                    "default" => $default,
                    "criterias" => $criterias,
                    "export" => true,
                    "canvas" => true,
                    "nb" => count($dataset),
                ];
                $widget->setWidgetHeader(Helper::getGraphHeader($params));
                $widget->setWidgetHtmlContent($graph);

                return $widget;

            case $this->getType() . "43":
                $name = 'SatisfactionByYear';
                $onclick = 0;

                $criterias = Criteria::getDefaultCriterias();

                $params = [
                    "preferences" => $preferences,
                    "criterias" => $criterias,
                    "opt" => $opt,
                ];
                $default = Criteria::manageCriterias($params);

                $is_deleted = ['glpi_tickets.is_deleted' => 0];

                $tabnames = [];
                $tabdates = [];

                $criteria = [
                    'SELECT' => [
                        new QueryExpression("DATE_FORMAT(" . $DB->quoteName("glpi_ticketsatisfactions.date_answered") . ", '%b %Y') AS period_name"),
                        'COUNT' => 'glpi_ticketsatisfactions.satisfaction AS satisfy',
                        new QueryExpression("DATE_FORMAT(" . $DB->quoteName("glpi_ticketsatisfactions.date_answered") . ", '%Y-%m') AS period"),
                    ],
                    'FROM' => 'glpi_ticketsatisfactions',
                    'INNER JOIN'       => [
                        'glpi_tickets' => [
                            'ON' => [
                                'glpi_ticketsatisfactions' => 'tickets_id',
                                'glpi_tickets'          => 'id',
                            ],
                        ],
                    ],
                    'WHERE' => [
                        $is_deleted,
                        'glpi_tickets.status' => CommonITILObject::CLOSED,
                        'NOT'       => ['glpi_tickets.closedate' => null],
                        'glpi_ticketsatisfactions.satisfaction' => ['>=', 3],
                    ],
                    'GROUPBY' => 'period_name',
                ];

                $criteria = Criteria::addCriteriasForQuery($criteria, $params);

                $iterator = $DB->request($criteria);
                $nb = count($iterator);

                $satisfydatasset = [];
                $notsatisfydatasset = [];
                $datasets = [];
                if ($nb) {
                    foreach ($iterator as $data) {

                        [$year, $month] = explode('-', $data['period']);

                        $nbdays = date("t", mktime(0, 0, 0, $month, 1, $year));
                        $count = 0;

                        $criteria_1 = [
                            'SELECT' => [
                                'COUNT' => 'glpi_ticketsatisfactions.id AS nb',
                            ],
                            'FROM' => 'glpi_ticketsatisfactions',
                            'INNER JOIN'       => [
                                'glpi_tickets' => [
                                    'ON' => [
                                        'glpi_ticketsatisfactions' => 'tickets_id',
                                        'glpi_tickets'          => 'id',
                                    ],
                                ],
                            ],
                            'WHERE' => [
                                $is_deleted,
                                [
                                    ['glpi_ticketsatisfactions.date_answered' => ['>=', $data['period'] . '-01 00:00:01']],
                                    ['glpi_ticketsatisfactions.date_answered' => ['<=', new QueryExpression("ADDDATE('$year-$month-$nbdays 00:00:00' , INTERVAL 1 DAY)")]],
                                ],
                                'glpi_tickets.status' => CommonITILObject::CLOSED,
                                'NOT'       => ['glpi_tickets.closedate' => null],
                            ],
                        ];
                        $criteria_1 = Criteria::addCriteriasForQuery($criteria_1, $params);

                        $iterator_1 = $DB->request($criteria_1);

                        foreach ($iterator_1 as $datacount) {
                            $count = $datacount['nb'];
                        }

                        $satisfydatasset[] = $data['satisfy'];
                        $numberanswered[] = $count;
                        $notsatisfydatasset[] = $count - $data['satisfy'];
                        $tabnames[] = $data['period_name'];
                        $tabdates[0][] = $data['period'] . '_answered';
                        $tabdates[1][] = $data['period'] . '_satisfy';
                        $tabdates[2][] = $data['period'] . '_notsatisfy';
                    }
                    $datasets[]
                        = [
                            "type" => "line",
                            "data" => $numberanswered,
                            "name" => __('Number of surveys answered', "mydashboard"),
                            'smooth' => false,
                            'yAxisIndex' => 1,
                        ];
                    $datasets[]
                        = [
                            "type" => "bar",
                            "data" => $satisfydatasset,
                            "name" => __("Satisfy number", "mydashboard"),
                        ];
                    $datasets[]
                        = [
                            "type" => "bar",
                            "data" => $notsatisfydatasset,
                            "name" => __("Not satisfy number", "mydashboard"),
                        ];
                }

                $labels = json_encode($tabnames);
                $tabdatesset = json_encode($tabdates);

                $widget = new Html();
                $title = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetComment($comment);
                $widget->setWidgetTitle((($isDebug) ? "42 " : "") . $title);
                $widget->toggleWidgetRefresh();

                $graph_datas = [
                    'title' => $title,
                    'comment' => $comment,
                    'name' => $name,
                    'ids' => $tabdatesset,
                    'data' => json_encode($datasets),
                    'labels' => $labels,
                ];

                $graph_criterias = [];
                if ($onclick == 1) {
                    $criterias_values = Criteria::getGraphCriterias($params);
                    $graph_criterias = array_merge(['widget' => $widgetId], $criterias_values);
                }

                $graph = BarChart::launchMultipleGraph($graph_datas, $graph_criterias);

                $params = [
                    "widgetId" => $widgetId,
                    "name" => $name,
                    "onsubmit" => true,
                    "opt" => $opt,
                    "default" => $default,
                    "criterias" => $criterias,
                    "export" => true,
                    "canvas" => true,
                    "nb" => 1,
                ];
                $widget->setWidgetHeader(Helper::getGraphHeader($params));

                $widget->setWidgetHtmlContent(
                    $graph
                );

                return $widget;

            case $this->getType() . "44":
                $name = 'LastSynchroInventoryChart';

                $onclick = 0;

                $criterias = [ Entity::$criteria_name,
                            'is_recursive_entities'];

                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() == 'central') {
                    $onclick = 1;
                }

                $params = [
                    "preferences" => [],
                    "criterias" => $criterias,
                    "opt" => $opt,
                ];
                $default = Criteria::manageCriterias($params);

                $is_deleted = ['glpi_computers.is_deleted' => 0];

                $criteria = [
                    'SELECT' => [
                        new QueryExpression("DATE_FORMAT(" . $DB->quoteName("glpi_computers.last_inventory_update") . ", '%b %Y') AS periodsync_name"),
                        'COUNT' => 'glpi_computers.id AS nb',
                        new QueryExpression("DATE_FORMAT(" . $DB->quoteName("glpi_computers.last_inventory_update") . ", '%Y-%m') AS periodsync"),
                    ],
                    'DISTINCT'        => true,
                    'FROM' => 'glpi_computers',
                    'WHERE' => [
                        $is_deleted,
                        'glpi_computers.is_template' => 0,
                    ],
                    'GROUPBY' => 'periodsync_name',
                    'ORDERBY' => 'periodsync ASC',
                ];

                $criteria = Criteria::addCriteriasForQuery($criteria, $params, 'glpi_computers');

                $iterator = $DB->request($criteria);

                $nbcomputers = __('Computers number', 'mydashboard');

                $tabdata = [];
                $tabnames = [];
                $tabsyncdates = [];
                if (count($iterator)) {
                    foreach ($iterator as $data) {
                        $tabdata['data'][] = $data['nb'];
                        $tabdata['type'] = 'bar';
                        $tabdata['name'] = $nbcomputers;
                        $tabnames[] = $data['periodsync_name'];
                        $tabsyncdates[] = $data['periodsync'];
                    }
                }

                $widget = new Html();
                $title = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetComment($comment);
                $widget->setWidgetTitle((($isDebug) ? "44 " : "") . $title);


                $dataBarset = json_encode($tabdata);
                $labelsBar = json_encode($tabnames);
                $tabsyncset = json_encode($tabsyncdates);

                $graph_datas = [
                    'title' => $title,
                    'comment' => $comment,
                    'name' => $name,
                    'ids' => $tabsyncset,
                    'data' => $dataBarset,
                    'labels' => $labelsBar,
                ];

                $graph_criterias = [];
                if ($onclick == 1) {
                    $criterias_values = Criteria::getGraphCriterias($params,  \Computer::getTable());
                    $graph_criterias = array_merge(['widget' => $widgetId], $criterias_values);
                }

                $graph = BarChart::launchGraph($graph_datas, $graph_criterias);

                $params = [
                    "widgetId" => $widgetId,
                    "name" => $name,
                    "onsubmit" => false,
                    "opt" => $opt,
                    "default" => $default,
                    "criterias" => $criterias,
                    "export" => true,
                    "canvas" => true,
                    "nb" => count($iterator),
                ];
                $widget->setWidgetHeader(Helper::getGraphHeader($params));
                $widget->setWidgetHtmlContent(
                    $graph
                );

                return $widget;
                break;
            case $this->getType() . "45":
                $name = 'reportBarTTORespectByMonth';

                $criterias = Criteria::getDefaultCriterias();

                $params = [
                    "preferences" => $preferences,
                    "criterias" => $criterias,
                    "opt" => $opt,
                ];

                $default = Criteria::manageCriterias($params);


                $tto_tickets = self::getTicketsforTTOTTR($params, "TTO");

                $currentYear = date("Y");
                $currentMonth = date("m");

                if (isset($opt["year"]) && $opt["year"] > 0) {
                    $currentYear = $opt["year"];
                }

                $previousYear = $currentYear - 1;

                $begin = new DateTime($previousYear . '-' . $currentMonth . '-' . '01');
                $end = new DateTime($currentYear . '-' . $currentMonth . '-' . '01');
                $period = new DatePeriod($begin, new DateInterval('P1M'), $end);
                $months = [];
                foreach ($period as $date) {
                    $months[] = $date->format("M Y");
                }

                $dataset = [];
                $notrespected = [];
                $respected = [];
                $respected_percent = [];
                foreach ($tto_tickets as $tto_ticket) {
                    $notrespected [] = $tto_ticket['notrespected'];
                    $respected [] = $tto_ticket['respected'];
                    $respected_percent [] = $tto_ticket['respected_percent'];
                }

                $notrespected = array_values($notrespected);
                $respected = array_values($respected);
                $respected_percent = array_values($respected_percent);
                $dataset[] = [
                    "name" => __("Not respected TTO", "mydashboard"),
                    "data" => $notrespected,
                    "type" => "bar",
                ];

                $dataset[] = [
                    "name" => __("Respected TTO", "mydashboard"),
                    "data" => $respected,
                    "type" => "bar",
                ];

                $dataset[] = [
                    "name" => __("Percent - Respected TTO", "mydashboard"),
                    "data" => $respected_percent,
                    "type" => "line",
                    'smooth' => false,
                    'yAxisIndex' => 1,
                ];

                $widget = new Html();
                $title = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetTitle((($isDebug) ? "46 " : "") . $title);
                $widget->setWidgetComment($comment);
                $widget->toggleWidgetRefresh();

                $dataLineset = json_encode($dataset);
                $labelsLine = json_encode($months);

                $graph_datas = [
                    'title' => $title,
                    'comment' => $comment,
                    'name' => $name,
                    'ids' => json_encode([]),
                    'data' => $dataLineset,
                    'labels' => $labelsLine,
                ];


                $graph = BarChart::launchGraph($graph_datas, []);

                $params = [
                    "widgetId" => $widgetId,
                    "name" => $name,
                    "onsubmit" => true,
                    "opt" => $opt,
                    "default" => $default,
                    "criterias" => $criterias,
                    "export" => true,
                    "canvas" => true,
                    "nb" => count($dataset),
                ];
                $widget->setWidgetHeader(Helper::getGraphHeader($params));
                $widget->setWidgetHtmlContent($graph);

                return $widget;

                break;
            case $this->getType() . "46":
                $name = 'reportBarTTRRespectByMonth';

                $criterias = Criteria::getDefaultCriterias();

                $params = [
                    "preferences" => $preferences,
                    "criterias" => $criterias,
                    "opt" => $opt,
                ];

                $default = Criteria::manageCriterias($params);

                $ttr_tickets = self::getTicketsforTTOTTR($params, "TTR");

                $currentYear = date("Y");
                $currentMonth = date("m");

                if (isset($opt["year"]) && $opt["year"] > 0) {
                    $currentYear = $opt["year"];
                }

                $previousYear = $currentYear - 1;

                $begin = new DateTime($previousYear . '-' . $currentMonth . '-' . '01');
                $end = new DateTime($currentYear . '-' . $currentMonth . '-' . '01');
                $period = new DatePeriod($begin, new DateInterval('P1M'), $end);
                $months = [];
                foreach ($period as $date) {
                    $months[] = $date->format("M Y");
                }

                $dataset = [];
                $notrespected = [];
                $respected = [];
                $respected_percent = [];
                foreach ($ttr_tickets as $ttr_ticket) {
                    $notrespected [] = $ttr_ticket['notrespected'];
                    $respected [] = $ttr_ticket['respected'];
                    $respected_percent [] = $ttr_ticket['respected_percent'];
                }

                $notrespected = array_values($notrespected);
                $respected = array_values($respected);
                $respected_percent = array_values($respected_percent);
                $dataset[] = [
                    "name" => __("Not respected TTR", "mydashboard"),
                    "data" => $notrespected,
                    "type" => "bar",
                ];

                $dataset[] = [
                    "name" => __("Respected TTR", "mydashboard"),
                    "data" => $respected,
                    "type" => "bar",
                ];

                $dataset[] = [
                    "name" => __("Percent - Respected TTR", "mydashboard"),
                    "data" => $respected_percent,
                    "type" => "line",
                    'smooth' => false,
                    'yAxisIndex' => 1,
                ];

                $widget = new Html();
                $title = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetTitle((($isDebug) ? "46 " : "") . $title);
                $widget->setWidgetComment($comment);
                $widget->toggleWidgetRefresh();

                $dataLineset = json_encode($dataset);
                $labelsLine = json_encode($months);

                $graph_datas = [
                    'title' => $title,
                    'comment' => $comment,
                    'name' => $name,
                    'ids' => json_encode([]),
                    'data' => $dataLineset,
                    'labels' => $labelsLine,
                ];


                $graph = BarChart::launchGraph($graph_datas, []);

                $params = [
                    "widgetId" => $widgetId,
                    "name" => $name,
                    "onsubmit" => true,
                    "opt" => $opt,
                    "default" => $default,
                    "criterias" => $criterias,
                    "export" => true,
                    "canvas" => true,
                    "nb" => count($dataset),
                ];
                $widget->setWidgetHeader(Helper::getGraphHeader($params));
                $widget->setWidgetHtmlContent($graph);

                return $widget;

                break;
            default:
                break;
        }
        return false;
    }

    /**
     * @param $params
     *
     * @return array
     */
    private static function getTimePerTech($params)
    {
        global $DB;

        $time_per_tech = [];
        $months = Toolbox::getMonthsOfYearArray();

        $default = Criteria::manageCriterias($params);

        $limit = $params['opt']['limit'] ?? $default['limit'];

        $year = $params['opt']['year'] ?? $default["year"];

        $selected_group = $params['opt']['technicians_groups_id'] ?? $default["technicians_groups_id"];
        if (is_array($selected_group)) {
            $selected_group = array_filter($selected_group);
        }

        $techlist = [];

        if (is_array($selected_group)
            && count($selected_group) > 0) {

            $criteria = [
                'SELECT' => 'glpi_groups_users.users_id',
                'FROM' => 'glpi_groups_users',
                'LEFT JOIN'       => [
                    'glpi_groups' => [
                        'ON' => [
                            'glpi_groups_users' => 'groups_id',
                            'glpi_groups'          => 'id',
                        ],
                    ],
                ],
                'WHERE' => [
                    'glpi_groups_users.groups_id' => $selected_group,
                    'glpi_groups.is_assign' => 1,
                ],
                'GROUPBY' => 'glpi_groups_users.users_id',
                'LIMIT' => $limit,
            ];

            $iterator = $DB->request($criteria);

            foreach ($iterator as $data) {
                $techlist[] = $data['users_id'];
            }
        } else {

            $criteria = [
                'SELECT' => 'glpi_tickettasks.users_id_tech',
                'FROM' => 'glpi_tickettasks',
                'GROUPBY' => 'glpi_tickettasks.users_id_tech',
                'LIMIT' => $limit,
            ];

            $iterator = $DB->request($criteria);

            foreach ($iterator as $data) {
                $techlist[] = $data['users_id_tech'];
            }
        }

        $current_month = date("m");
        foreach ($months as $key => $month) {
            if ($key > $current_month && $year == date("Y")) {
                break;
            }

            $month_tmp = $key;
            $nb_jours = date("t", mktime(0, 0, 0, $key, 1, $year));

            if (strlen($key) == 1) {
                $month_tmp = "0" . $month_tmp;
            }

            if ($key == 0) {
                $year = $year - 1;
                $month_tmp = "12";
                $nb_jours = date("t", mktime(0, 0, 0, 12, 1, $year));
            }

            $month_deb_date = "$year-$month_tmp-01";
            $month_deb_datetime = $month_deb_date . " 00:00:00";
            $month_end_date = "$year-$month_tmp-$nb_jours";
            $month_end_datetime = $month_end_date . " 23:59:59";
            $is_deleted = ['glpi_tickets.is_deleted' => 0];

            foreach ($techlist as $techid) {
                $time_per_tech[$techid][$key] = 0;

                $criteria = [
                    'SELECT' => [
                        new QueryExpression("DATE(" . $DB->quoteName("glpi_tickettasks.date") . ")"),
                        new QueryExpression("SUM(" . $DB->quoteName("glpi_tickettasks.actiontime") . ") AS actiontime_date"),
                    ],
                    'FROM' => 'glpi_tickettasks',
                    'INNER JOIN'       => [
                        'glpi_tickets' => [
                            'ON' => [
                                'glpi_tickettasks' => 'tickets_id',
                                'glpi_tickets'          => 'id',
                            ],
                        ],
                    ],
                    'WHERE' => [
                        $is_deleted,
                        'glpi_tickettasks.actiontime' => ['<>', 0],
                        'OR' => [
                            [
                                'glpi_tickettasks.users_id' => $techid,
                                ['glpi_tickettasks.begin' => ['>=', $month_deb_datetime]],
                                ['glpi_tickettasks.end' => ['<=', $month_end_datetime]],
                            ],
                            [
                                'glpi_tickettasks.users_id' => $techid,
                                'glpi_tickettasks.begin' => null,
                                ['glpi_tickettasks.date' => ['>=', $month_deb_datetime]],
                                ['glpi_tickettasks.date' => ['<=', $month_end_datetime]],
                            ],
                        ],
                    ],
                    'GROUPBY' => new QueryExpression("DATE(" . $DB->quoteName("glpi_tickettasks.date") . ")"),
                ];

                $criteria = Criteria::addCriteriasForQuery($criteria, $params);

                $iterator = $DB->request($criteria);

                foreach ($iterator as $data) {

                    if ($data['actiontime_date'] > 0) {
                        if (isset($time_per_tech[$techid][$key])) {
                            $time_per_tech[$techid][$key] += round(
                                ($data['actiontime_date'] / 3600 / 8) ?? 0,
                                2,
                                PHP_ROUND_HALF_UP
                            );
                        } else {
                            $time_per_tech[$techid][$key] = round(
                                ($data['actiontime_date'] / 3600 / 8) ?? 0,
                                2,
                                PHP_ROUND_HALF_UP
                            );
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
    private static function getTicketsPerTech($params)
    {
        global $DB;

        $tickets_per_tech = [];
        $months = Toolbox::getMonthsOfYearArray();

        $default = Criteria::manageCriterias($params);

        $limit = $params['opt']['limit'] ?? $default['limit'];

        $year = $params['opt']['year'] ?? $default["year"];

        $selected_group = $params['opt']['technicians_groups_id'] ?? $default["technicians_groups_id"];
        if (is_array($selected_group)) {
            $selected_group = array_filter($selected_group);
        }

        $techlist = [];

        if (is_array($selected_group)
            && count($selected_group) == 0) {
            $selected_group = $_SESSION['glpigroups'];
        }
        if (is_array($selected_group)
            && count($selected_group) > 0) {

            $criteria = [
                'SELECT' => 'glpi_groups_users.users_id',
                'FROM' => 'glpi_groups_users',
                'LEFT JOIN'       => [
                    'glpi_groups' => [
                        'ON' => [
                            'glpi_groups_users' => 'groups_id',
                            'glpi_groups'          => 'id',
                        ],
                    ],
                ],
                'WHERE' => [
                    'glpi_groups_users.groups_id' => $selected_group,
                    'glpi_groups.is_assign' => 1,
                ],
                'GROUPBY' => 'glpi_groups_users.users_id',
            ];

            $iterator = $DB->request($criteria);

            foreach ($iterator as $data) {
                $techlist[] = $data['users_id'];
            }
        }

        $techlist = array_filter($techlist);

        $current_month = date("m");
        foreach ($months as $key => $month) {
            if ($key > $current_month && $year == date("Y")) {
                break;
            }

            $month_tmp = $key;
            $nb_jours = date("t", mktime(0, 0, 0, $key, 1, $year));

            if (strlen($key) == 1) {
                $month_tmp = "0" . $month_tmp;
            }

            if ($key == 0) {
                $year = $year - 1;
                $month_tmp = "12";
                $nb_jours = date("t", mktime(0, 0, 0, 12, 1, $year));
            }

            $month_deb_date = "$year-$month_tmp-01";
            $month_deb_datetime = $month_deb_date . " 00:00:00";
            $month_end_date = "$year-$month_tmp-$nb_jours";
            $month_end_datetime = $month_end_date . " 23:59:59";
            $is_deleted = ['glpi_tickets.is_deleted' => 0];

            foreach ($techlist as $techid) {

                $tickets_per_tech[$techid][$key] = 0;

                $criteria_1 = [
                    'SELECT' => [
                        'COUNT' => 'glpi_tickets.id AS nb_tickets',
                    ],
                    'FROM' => 'glpi_tickets',
                    'INNER JOIN'       => [
                        'glpi_tickets_users' => [
                            'ON' => [
                                'glpi_tickets_users'   => 'tickets_id',
                                'glpi_tickets'                  => 'id', [
                                    'AND' => [
                                        'glpi_tickets_users.type' => CommonITILActor::ASSIGN,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'LEFT JOIN'       => [
                        'glpi_entities' => [
                            'ON' => [
                                'glpi_tickets' => 'entities_id',
                                'glpi_entities'          => 'id',
                            ],
                        ],
                    ],
                    'WHERE' => [
                        $is_deleted,
                        'glpi_tickets_users.users_id' => $techid,
                        [
                            ['glpi_tickets.date' => ['>=', $month_deb_datetime]],
                            ['glpi_tickets.date' => ['<=', $month_end_datetime]],
                        ],
                    ],
                    'GROUPBY' => new QueryExpression("DATE(" . $DB->quoteName("glpi_tickets.date") . ")"),
                ];

                $criteria_1 = Criteria::addCriteriasForQuery($criteria_1, $params);

                $iterator_1 = $DB->request($criteria_1);

                //                $querym_ai = "SELECT COUNT(`glpi_tickets`.`id`) AS nbtickets
                //                        FROM `glpi_tickets`
                //                        INNER JOIN `glpi_tickets_users`
                //                        ON (`glpi_tickets`.`id` = `glpi_tickets_users`.`tickets_id` AND `glpi_tickets_users`.`type` = 2 AND $is_deleted)
                //                        LEFT JOIN `glpi_entities` ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`) ";
                //                $querym_ai .= "WHERE ";
                //                $querym_ai .= "(
                //                           `glpi_tickets`.`date` >= '$month_deb_datetime'
                //                           AND `glpi_tickets`.`date` <= '$month_end_datetime'
                //                           AND `glpi_tickets_users`.`users_id` = (" . $techid . ") "
                //                    . Helper::getSpecificEntityRestrict("glpi_tickets", $params)
                //                    . " $type_criteria ) ";
                //                $querym_ai .= "GROUP BY DATE(`glpi_tickets`.`date`);
                //                        ";
                foreach ($iterator_1 as $data1) {
                    $tickets_per_tech[$techid][$key] += $data1['nb_tickets'];
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

    public static function getAllMoreTicketStatus()
    {
        global $DB;

        $tabs = [];
        if (Plugin::isPluginActive('moreticket')) {

            $iterator = $DB->request([
                'SELECT'    => [
                    'completename AS name',
                    'id',
                ],
                'FROM'      => 'glpi_plugin_moreticket_waitingtypes',
                'ORDERBY'   => 'name',
            ]);

            foreach ($iterator as $type) {
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
    public static function TotalTpsPassesArrondis($a_arrondir)
    {
        $tranches_seuil = 0.002;
        $tranches_arrondi = [0, 0.25, 0.5, 0.75, 1];

        $partie_entiere = floor($a_arrondir);
        $reste = $a_arrondir - $partie_entiere + 10; // Le + 10 permet de pallier é un probléme de comparaison (??) par la suite.
        /* Initialisation des tranches majorées du seuil supplémentaire. */
        $tranches_majorees = [];
        for ($i = 0; $i < count($tranches_arrondi); $i++) {
            // Le + 10 qui suit permet de pallier é un probléme de comparaison (??) par la suite.
            $tranches_majorees[] = $tranches_arrondi[$i] + $tranches_seuil + 10;
        }
        if ($reste < $tranches_majorees[0]) {
            $result = $partie_entiere;
        } elseif ($reste >= $tranches_majorees[0] && $reste < $tranches_majorees[1]) {
            $result = $partie_entiere + $tranches_arrondi[1];
        } elseif ($reste >= $tranches_majorees[1] && $reste < $tranches_majorees[2]) {
            $result = $partie_entiere + $tranches_arrondi[2];
        } elseif ($reste >= $tranches_majorees[2] && $reste < $tranches_majorees[3]) {
            $result = $partie_entiere + $tranches_arrondi[3];
        } else {
            $result = $partie_entiere + $tranches_arrondi[4];
        }

        return $result;
    }

    private static function getCategorySonsOf($id)
    {
        $categories = getSonsOf("glpi_itilcategories", $id);

        if (count($categories) > 1) {
            $categories = " `glpi_tickets`.`itilcategories_id` IN  (" . implode(",", $categories) . ") ";
        } else {
            $categories = " `glpi_tickets`.`itilcategories_id` = " . implode(",", $categories);
        }
        return $categories;
    }

    public static function getAllMonthAndYear($currentYear, $currentMonth, $previousYear, $otherFormat = false)
    {
        $begin = new DateTime($previousYear . '-' . $currentMonth . '-' . '01');
        $end = new DateTime($currentYear . '-' . $currentMonth . '-' . '01');
        $period = new DatePeriod($begin, new DateInterval('P1M'), $end);
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

    public function getAllFirstDayOfWeeksInAMonth($year, $month, $day = 'Monday', $daysError = 3)
    {
        $dateString = 'first ' . $day . ' of ' . $year . '-' . $month;

        $startDay = new DateTime($dateString);
        $datesString = [];

        if ($startDay->format('j') > $daysError) {
            $startDay->modify('- 7 days');
        }

        $days = [];

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
    private static function getTicketsforTTOTTR($params, $sla)
    {
        global $DB;

        $default = Criteria::manageCriterias($params);

        $type = $params['type'] ?? $default['type'];

        $tickets_helpdesk = [];

        $currentYear = date("Y");
        $currentMonth = date("m");

        if (isset($params["year"]) && $params["year"] > 0) {
            $currentYear = $params["year"];
        }

        $previousYear = $currentYear - 1;

        $begin = new DateTime($previousYear . '-' . $currentMonth . '-' . '01');
        $end = new DateTime($currentYear . '-' . $currentMonth . '-' . '01');

        $period = new DatePeriod($begin, new DateInterval('P1M'), $end);
        $datesTab = [];
        foreach ($period as $date) {
            $datesTab[] = $date->format("Y-m");
        }

        foreach ($datesTab as $key => $dateTab) {

            $tickets_helpdesk[$key]['nb'] = 0;
            $tickets_helpdesk[$key]['respected'] = 0;
            $tickets_helpdesk[$key]['notrespected'] = 0;
            $tickets_helpdesk[$key]['respected_percent'] = 0;
            $month_deb_date = "$dateTab-01";
            $month_deb_datetime = $month_deb_date . " 00:00:00";
            $datecheck = explode("-", $dateTab);
            $nbdays = cal_days_in_month(CAL_GREGORIAN, $datecheck[1], $datecheck[0]);
            $month_end_date = "$dateTab-$nbdays";
            $month_end_datetime = $month_end_date . " 23:59:59";

            $is_deleted = ['glpi_tickets.is_deleted' => 0];

            if ($sla == "TTO") {

                $criteria = [
                    'SELECT' => [
                        'COUNT' => 'glpi_tickets.id AS nb',
                    ],
                    'DISTINCT'        => true,
                    'FROM' => 'glpi_tickets',
                    'WHERE' => [
                        $is_deleted,
                        'glpi_tickets.takeintoaccount_delay_stat' => ['<>', 'NULL'],
                        'glpi_tickets.time_to_own' => ['<>', 'NULL'],
                        'glpi_tickets.status' => [CommonITILObject::SOLVED, CommonITILObject::CLOSED],
                        [
                            ['glpi_tickets.closedate' => ['>=', $month_deb_datetime]],
                            ['glpi_tickets.closedate' => ['<=', $month_end_datetime]],
                        ],
                    ],
                ];

                $criteria = Criteria::addCriteriasForQuery($criteria, $params);
                //
                //                $all = "SELECT DISTINCT COUNT(`glpi_tickets`.`id`) AS nb
                //                        FROM `glpi_tickets`
                //                        WHERE {$date} >= '{$month_deb_datetime}'
                //                          AND {$date} <= '{$month_end_datetime}' $is_deleted $entities_criteria $type_criteria
                //                        AND `glpi_tickets`.`takeintoaccount_delay_stat` IS NOT NULL
                //                        AND `glpi_tickets`.`time_to_own` IS NOT NULL ";
                //                $all .= " AND `status` IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ") ";
                //
            } else {
                //                $all = "SELECT DISTINCT COUNT(`glpi_tickets`.`id`) AS nb
                //                        FROM `glpi_tickets`
                //                        WHERE {$date} >= '{$month_deb_datetime}'
                //                          AND {$date} <= '{$month_end_datetime}' $is_deleted $entities_criteria $type_criteria
                //                        AND `glpi_tickets`.`solvedate` IS NOT NULL
                //                        AND `glpi_tickets`.`time_to_resolve` IS NOT NULL ";
                //                $all .= " AND `status` IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ") ";

                $criteria = [
                    'SELECT' => [
                        'COUNT' => 'glpi_tickets.id AS nb',
                    ],
                    'DISTINCT'        => true,
                    'FROM' => 'glpi_tickets',
                    'WHERE' => [
                        $is_deleted,
                        'NOT'       => [['glpi_tickets.solvedate' => 'NULL'],
                            ['glpi_tickets.time_to_resolve' => 'NULL']],
                        'glpi_tickets.status' => [CommonITILObject::SOLVED, CommonITILObject::CLOSED],
                        [
                            ['glpi_tickets.closedate' => ['>=', $month_deb_datetime]],
                            ['glpi_tickets.closedate' => ['<=', $month_end_datetime]],
                        ],
                    ],
                ];

                $criteria = Criteria::addCriteriasForQuery($criteria, $params);
            }

            $iterator = $DB->request($criteria);
            foreach ($iterator as $data) {
                $total['nb'] = $data['nb'];
            }


            if ($sla == "TTO") {
                //                $query = "SELECT COUNT(`glpi_tickets`.`id`) AS nb
                //                        FROM `glpi_tickets`
                //                        WHERE {$date} >= '{$month_deb_datetime}'
                //                          AND {$date} <= '{$month_end_datetime}' $is_deleted $entities_criteria $type_criteria
                //                        AND `glpi_tickets`.`takeintoaccount_delay_stat` IS NOT NULL
                //                        AND `glpi_tickets`.`time_to_own` IS NOT NULL
                //                        AND (`glpi_tickets`.`takeintoaccount_delay_stat`  > TIME_TO_SEC(TIMEDIFF(`glpi_tickets`.`time_to_own`, `glpi_tickets`.`date`))
                //                                                 OR (`glpi_tickets`.`takeintoaccount_delay_stat` = 0 AND `glpi_tickets`.`time_to_own` < NOW()))";
                //                $query .= " AND `status` IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")";

                $criteria = [
                    'SELECT' => [
                        'COUNT' => 'glpi_tickets.id AS nb',
                    ],
                    'FROM' => 'glpi_tickets',
                    'WHERE' => [
                        $is_deleted,
                        'glpi_tickets.time_to_own' => ['<>', 'NULL'],
                        'glpi_tickets.status' => [CommonITILObject::SOLVED, CommonITILObject::CLOSED],
                        [
                            ['glpi_tickets.closedate' => ['>=', $month_deb_datetime]],
                            ['glpi_tickets.closedate' => ['<=', $month_end_datetime]],
                        ],
                        'OR' => [
                            [
                                'glpi_tickets.takeintoaccount_delay_stat' => [
                                    '>',
                                    new QueryExpression(
                                        "TIME_TO_SEC(TIMEDIFF(" . $DB->quoteName(
                                            "glpi_tickets.time_to_own"
                                        ) . ", " . $DB->quoteName("glpi_tickets.date") . "))"
                                    ),
                                ],
                            ],
                            [
                                'AND' => [
                                    'glpi_tickets.takeintoaccount_delay_stat' => '0',
                                    'glpi_tickets.time_to_own' => ['>', new QueryExpression("NOW()")],
                                ],
                            ],
                        ],

                    ],
                ];

                $criteria = Criteria::addCriteriasForQuery($criteria, $params);

            } else {
                //                $query = "SELECT COUNT(`glpi_tickets`.`id`) AS nb
                //                        FROM `glpi_tickets`
                //                        WHERE {$date} >= '{$month_deb_datetime}'
                //                          AND {$date} <= '{$month_end_datetime}' $is_deleted $entities_criteria $type_criteria
                //                        AND `glpi_tickets`.`solvedate` IS NOT NULL
                //                        AND `glpi_tickets`.`time_to_resolve` IS NOT NULL
                //                                            AND (`glpi_tickets`.`solvedate` > `glpi_tickets`.`time_to_resolve`
                //                                                 OR (`glpi_tickets`.`solvedate` IS NULL
                //                                                      AND `glpi_tickets`.`time_to_resolve` < NOW()))";
                //                $query .= " AND `status` IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")";

                $criteria = [
                    'SELECT' => [
                        'COUNT' => 'glpi_tickets.id AS nb',
                    ],
                    'FROM' => 'glpi_tickets',
                    'WHERE' => [
                        $is_deleted,
                        'NOT'       => ['glpi_tickets.solvedate' => 'NULL'],
                        'glpi_tickets.status' => [CommonITILObject::SOLVED, CommonITILObject::CLOSED],
                        [
                            ['glpi_tickets.closedate' => ['>=', $month_deb_datetime]],
                            ['glpi_tickets.closedate' => ['<=', $month_end_datetime]],
                        ],
                        'OR' => [
                            [
                                'glpi_tickets.solvedate' => [
                                    '>',
                                    new QueryExpression(
                                        $DB->quoteName(
                                            "glpi_tickets.time_to_resolve"
                                        )
                                    ),
                                ],
                            ],
                            [
                                'AND' => [
                                    'NOT'       => ['glpi_tickets.solvedate' => 'NULL'],
                                    'glpi_tickets.time_to_resolve' => ['>', new QueryExpression("NOW()")],
                                ],
                            ],
                        ],

                    ],
                ];

                $criteria = Criteria::addCriteriasForQuery($criteria, $params);

            }
            $iterator = $DB->request($criteria);
            $nb = count($iterator);

            foreach ($iterator as $sum) {
                if ($nb > 0 && $sum['nb'] > 0) {
                    $notrespected = $sum['nb'];
                    $respected = $total['nb'] - $sum['nb'];

                    $respected_percent = round(
                        ($total['nb'] - $sum['nb']) * 100 / ($total['nb']),
                        2,
                        PHP_ROUND_HALF_UP
                    );

                    $tickets_helpdesk[$key]['notrespected'] = $notrespected;
                    $tickets_helpdesk[$key]['respected'] = $respected;
                    $tickets_helpdesk[$key]['respected_percent'] = $respected_percent;
                }
            }
        }

        return $tickets_helpdesk;
    }

    /**
     * @param $params
     * @param $groups_id
     *
     * @return array
     * @throws \GlpitestSQLError
     */
    private static function getLifetimeOrTakeIntoAccountTicketAverage($params)
    {
        global $DB;

        $default = Criteria::manageCriterias($params);

        $tickets_helpdesk = [];
        $months = Toolbox::getMonthsOfYearArray();

        $year = $params['opt']['year'] ?? $default['year'];

        $current_month = date("m");
        foreach ($months as $key => $month) {
            $tickets_helpdesk[$key]['nb'] = 0;
            $tickets_helpdesk[$key]['lifetime'] = 0;
            $tickets_helpdesk[$key]['takeintoaccount'] = 0;

            if ($key > $current_month && $year == date("Y")) {
                break;
            }

            //            $next = $key + 1;

            $month_tmp = $key;
            $nb_jours = date("t", mktime(0, 0, 0, $key, 1, $year));

            if (strlen($key) == 1) {
                $month_tmp = "0" . $month_tmp;
            }
            //            if (strlen($next) == 1) {
            //                $next = "0" . $next;
            //            }

            if ($key == 0) {
                $year = $year - 1;
                $month_tmp = "12";
                $nb_jours = date("t", mktime(0, 0, 0, 12, 1, $year));
            }

            $month_deb_date = "$year-$month_tmp-01";
            $month_deb_datetime = $month_deb_date . " 00:00:00";
            $month_end_date = "$year-$month_tmp-$nb_jours";
            $month_end_datetime = $month_end_date . " 23:59:59";

            $is_deleted = ['glpi_tickets.is_deleted' => 0];
            //            $assign = Group_Ticket::ASSIGN;
            //            $date = "`glpi_tickets`.`solvedate`";


            //
            //            $queryavg = "SELECT COUNT(`glpi_tickets`.`id`) AS nbtickets,
            //                             SUM(`glpi_tickets`.`solve_delay_stat` / 3600) as lifetime,
            //                             SUM(`glpi_tickets`.`takeintoaccount_delay_stat` / 3600) as takeintoaccount
            //                        FROM `glpi_tickets`
            //                        LEFT JOIN `glpi_groups_tickets`
            //                        ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id`
            //                          AND `glpi_groups_tickets`.`type` = {$assign}) ";
            //            $queryavg .= "WHERE ";
            //
            //            $queryavg .= "{$date} >= '{$month_deb_datetime}'
            //                          AND {$date} <= '{$month_end_datetime}'
            //                          {$type_criteria}
            //                          {$entities_criteria} {$locations_criteria} {$groups_id} {$is_deleted}
            //                          AND `glpi_tickets`.`status` IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ") ";
            //
            //            $queryavg .= "GROUP BY DATE(`glpi_tickets`.`solvedate`);
            //                        ";
            //

            $criteria = [
                'SELECT' => [
                    'COUNT' => 'glpi_tickets.id AS nbtickets',
                    new QueryExpression("SUM(" . $DB->quoteName("glpi_tickets.solve_delay_stat") . " / 3600) AS lifetime"),
                    new QueryExpression("SUM(" . $DB->quoteName("glpi_tickets.takeintoaccount_delay_stat") . " / 3600) AS takeintoaccount"),
                ],
                'FROM' => 'glpi_tickets',
                'WHERE' => [
                    $is_deleted,
                    'glpi_tickets.status' => [CommonITILObject::SOLVED, CommonITILObject::CLOSED],
                    [
                        ['glpi_tickets.solvedate' => ['>=', $month_deb_datetime]],
                        ['glpi_tickets.solvedate' => ['<=', $month_end_datetime]],
                    ],
                ],
                'GROUPBY' => new QueryExpression("DATE(" . $DB->quoteName("glpi_tickets.solvedate") . ")"),
            ];

            $criteria = Criteria::addCriteriasForQuery($criteria, $params);

            $iterator = $DB->request($criteria);
            foreach ($iterator as $data) {
                $tickets_helpdesk[$key]['takeintoaccount'] += $data['takeintoaccount'];
                $tickets_helpdesk[$key]['lifetime'] += $data['lifetime'];
                $tickets_helpdesk[$key]['nb'] += $data['nbtickets'];
            }
        }

        //        if ($key == 0) {
        //            $year++;
        //        }

        return $tickets_helpdesk;
    }


    /**
     * @param $selected_id
     *
     * @return string
     */
    public static function pluginMydashboardReports_Bar1link($options)
    {
        global $CFG_GLPI;

        $options_selected = Criteria::addUrlCriteria(Criteria::STATUS, 'equals', 'notold', 'AND');
        // open date
        $options_selected = Criteria::addUrlCriteria(Criteria::OPEN_DATE, 'contains', $options["selected_id"], 'AND');

        // Strip date criteria from base params — the bar's period defines its own date range
        $base_criteria = array_values(array_filter(
            $options['params']['criteria'] ?? [],
            fn($c) => ($c['field'] ?? null) != Criteria::OPEN_DATE
        ));
        $options['criteria'] = array_merge($base_criteria, $options_selected['criteria']);

        return $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&'
            . Toolbox::append_params($options, "&");
    }


    /**
     * @param $selected_id
     *
     * @return string
     */
    public static function pluginMydashboardReports_Bar15link($options)
    {
        global $CFG_GLPI;


        if (isset($options["params"]["year"]) && !isset($options["params"]["begin"])) {
            $options["params"]["begin"] = $options["params"]["year"] . "-01-01 00:00:01";
            $options["params"]["end"] = $options["params"]["year"] . "-12-31 23:59:00";
            $options_selected = Criteria::addUrlCriteria(Criteria::OPEN_DATE, 'contains', $options["params"]["year"], 'AND');
        } elseif (isset($options["params"]["begin"]) && isset($options["params"]["end"])) {
            $options_selected = Criteria::addUrlCriteria(Criteria::OPEN_DATE, 'morethan', $options["params"]["begin"], 'AND');

            $options_selected = Criteria::addUrlCriteria(Criteria::OPEN_DATE, 'lessthan', $options["params"]["end"], 'AND');
        } elseif (isset($options["params"]["filter_date"])) {
            $options["params"]["begin"] = $options["params"]["filter_date"] . "-01-01 00:00:01";
            $options["params"]["end"] = $options["params"]["filter_date"] . "-12-31 23:59:00";
            $options_selected = Criteria::addUrlCriteria(Criteria::OPEN_DATE, 'contains', $options["params"]["filter_date"], 'AND');

        }

        $params["params"][ITILCategory::$criteria_name] = $options["selected_id"];
        $options_selected = ITILCategory::getSearchCriteria($params);

        $options['criteria'] = array_merge($options['params']['criteria'], $options_selected['criteria']);

        return $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&'
            . Toolbox::append_params($options, "&");
    }

    /**
     * @param $selected_id
     *
     * @return string
     */
    public static function pluginMydashboardReports_Bar24link($options)
    {
        global $CFG_GLPI;


        if (isset($options["params"]["year"]) && !isset($options["params"]["begin"])) {
            $options["params"]["begin"] = $options["params"]["year"] . "-01-01 00:00:01";
            $options["params"]["end"] = $options["params"]["year"] . "-12-31 23:59:00";
        }

        if (isset($options["params"]["begin"])) {
            $options_selected = Criteria::addUrlCriteria(Criteria::OPEN_DATE, 'morethan', $options["params"]["begin"], 'AND');
        }
        if (isset($options["params"]["end"])) {
            $options_selected = Criteria::addUrlCriteria(Criteria::OPEN_DATE, 'lessthan', $options["params"]["end"], 'AND');
        }

        $params["params"][Technician::$criteria_name] = $options["selected_id"];
        $options_selected = Technician::getSearchCriteria($params);

        $options['criteria'] = array_merge($options['params']['criteria'], $options_selected['criteria']);

        return $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&'
            . Toolbox::append_params($options, "&");
    }


    /**
     * @param $selected_id
     *
     * @return string
     */
    public static function pluginMydashboardReports_Bar35link($options)
    {
        global $CFG_GLPI;

        $begin = null;
        $end = null;
        if (isset($options['selected_id']) && strpos($options['selected_id'], '_') !== false) {
            $eventParts = explode('_', $options['selected_id']);
            $begin = $eventParts[0];
            $end = $eventParts[1];
        }

        $options_selected = Criteria::addUrlCriteria(Criteria::STATUS, 'equals', 'notold', 'AND');

        if ($begin == $end) {
            $today = strtotime(date("Y-m-d H:i:s"));
            $datecheck = date('Y-m-d H:i:s', strtotime('-1 month', $today));
            if (strtotime($begin) < strtotime($datecheck)) {
                $options_selected = Criteria::addUrlCriteria(Criteria::OPEN_DATE, 'lessthan', $begin, 'AND');
            } else {
                $options_selected = Criteria::addUrlCriteria(Criteria::OPEN_DATE, 'morethan', $begin, 'AND');
            }
        } else {
            $options_selected = Criteria::addUrlCriteria(Criteria::OPEN_DATE, 'lessthan', $begin, 'AND');
            $options_selected = Criteria::addUrlCriteria(Criteria::OPEN_DATE, 'morethan', $end, 'AND');
        }

        // Strip date criteria from base params — the bar's period defines its own date range
        $base_criteria = array_values(array_filter(
            $options['params']['criteria'] ?? [],
            fn($c) => ($c['field'] ?? null) != Criteria::OPEN_DATE
        ));
        $options['criteria'] = array_merge($base_criteria, $options_selected['criteria']);


        return $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&'
            . Toolbox::append_params($options, "&");
    }


    /**
     * @param $selected_id
     *
     * @return string
     */
    public static function pluginMydashboardReports_Bar36link($options)
    {
        global $CFG_GLPI;


        $options_selected = Criteria::addUrlCriteria(Criteria::STATUS, 'equals', 'notold', 'AND');

        $options_selected = Criteria::addUrlCriteria(Criteria::PRIORITY, 'equals', $options["selected_id"], 'AND');

        $options['criteria'] = array_merge($options['params']['criteria'], $options_selected['criteria']);


        return $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&'
            . Toolbox::append_params($options, "&");
    }


    /**
     * @param $selected_id
     *
     * @return string
     */
    public static function pluginMydashboardReports_Bar37link($options)
    {
        global $CFG_GLPI;


        // STATUS
        if (strpos($options["selected_id"], 'moreticket_') !== false) {
            $status = explode("_", $options["selected_id"]);

            $options_selected = Criteria::addUrlCriteria(Criteria::STATUS, 'equals', \Ticket::WAITING, 'AND');

            $options_selected = Criteria::addUrlCriteria(Criteria::MORETICKET_WAITINGTYPE, 'equals', $status[1], 'AND');
        } else {
            $options_selected = Criteria::addUrlCriteria(Criteria::STATUS, 'equals', $options["selected_id"], 'AND');
        }

        $options['criteria'] = array_merge($options['params']['criteria'], $options_selected['criteria']);

        return $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&'
            . Toolbox::append_params($options, "&");
    }


    /**
     * @param $selected_id
     *
     * @return string
     */
    public static function pluginMydashboardReports_Bar43link($options)
    {
        global $CFG_GLPI;

        if (isset($options['selected_id']) && strpos($options['selected_id'], '_') !== false) {
            $eventParts = explode('_', $options['selected_id']);
            $date = $eventParts[0];
            $ticket_satisfaction = $eventParts[1];
            if (isset($date) && strpos($date, '-') !== false) {
                $dateParts = explode('-', $date);
                $year = $dateParts[0];
                $month = $dateParts[1];
            }
        }

        if (isset($year) && isset($month) && isset($ticket_satisfaction)) {
            if ($ticket_satisfaction == "answered") {
            } elseif ($ticket_satisfaction == "satisfy") {
                $options_selected = Criteria::addUrlCriteria(Criteria::SATISFACTION_VALUE, 'equals', '>= 3', 'AND');
            } elseif ($ticket_satisfaction == "notsatisfy") {
                $options_selected = Criteria::addUrlCriteria(Criteria::SATISFACTION_VALUE, 'equals', '< 3', 'AND');
            }

            $date = "$year-$month-01 00:00";
            $nbdays = date("t", mktime(0, 0, 0, $month, 1, $year));
            $options_selected = Criteria::addUrlCriteria(Criteria::SATISFACTION_DATE, 'morethan', $date, 'AND');
            $date = "$year-$month-$nbdays 23:59";
            $options_selected = Criteria::addUrlCriteria(Criteria::SATISFACTION_DATE, 'lessthan', $date, 'AND');
        }

        $options['criteria'] = array_merge($options['params']['criteria'], $options_selected['criteria']);

        return $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&'
            . Toolbox::append_params($options, "&");
    }

    /**
     * @param $selected_id
     *
     * @return string
     */
    public static function pluginMydashboardReports_Bar44link($options)
    {
        global $CFG_GLPI;

        if (isset($options['selected_id']) && strpos($options['selected_id'], '-') !== false) {
            $dateParts = explode('-', $options['selected_id']);
            $year = $dateParts[0];
            $month = $dateParts[1];
        }

        if (isset($year) && isset($month)) {
            $date = "$year-$month";
            $options_selected = Criteria::addUrlCriteria(Criteria::INVENTORY_DATE, 'contains', $date, 'AND');
        } else {
            $options_selected = Criteria::addUrlCriteria(Criteria::INVENTORY_DATE, 'contains', 'NULL', 'AND');
        }

        $options['criteria'] = array_merge($options['params']['criteria'], $options_selected['criteria']);

        return $CFG_GLPI["root_doc"] . '/front/computer.php?is_deleted=0&'
            . Toolbox::append_params($options, "&");
    }
}
