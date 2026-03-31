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

use CommonGLPI;
use CommonITILObject;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QuerySubQuery;
use GlpiPlugin\Mydashboard\Alert;
use GlpiPlugin\Mydashboard\Charts\BarChart;
use GlpiPlugin\Mydashboard\Criteria;
use GlpiPlugin\Mydashboard\Criterias\DisplayData;
use GlpiPlugin\Mydashboard\Criterias\Location;
use GlpiPlugin\Mydashboard\Criterias\RequesterGroup;
use GlpiPlugin\Mydashboard\Criterias\Technician;
use GlpiPlugin\Mydashboard\Criterias\Year;
use GlpiPlugin\Mydashboard\Helper;
use GlpiPlugin\Mydashboard\Html;
use GlpiPlugin\Mydashboard\Menu;
use GlpiPlugin\Mydashboard\Preference as MydashboardPreference;
use GlpiPlugin\Mydashboard\StockTicketIndicator;
use GlpiPlugin\Mydashboard\Widget;
use Session;
use Toolbox;

/**
 * Class Reports_Line
 */
class Reports_Line extends CommonGLPI
{
    private $options;
    private $pref;
    public static $reports = [6, 22, 34, 35, 43, 44, 45, 46, 47, 48, 49];

    /**
     * Reports_Line constructor.
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
            Menu::$HELPDESK => [
                $this->getType() . "6" => [
                    "title" => __("Tickets stock by month", "mydashboard"),
                    "type" => Widget::$LINE,
                    "comment" => __("Sum of not solved tickets by month", "mydashboard"),
                ],
                $this->getType() . "22" => [
                    "title" => __("Number of opened and closed tickets by month", "mydashboard"),
                    "type" => Widget::$LINE,
                    "comment" => "",
                ],
                $this->getType() . "34" => [
                    "title" => __("Number of opened and resolved / closed tickets by month", "mydashboard"),
                    "type" => Widget::$LINE,
                    "comment" => "",
                ],
                $this->getType() . "35" => [
                    "title" => __("Number of opened, closed, unplanned tickets by month", "mydashboard"),
                    "type" => Widget::$LINE,
                    "comment" => "",
                ],
                $this->getType() . "43" => [
                    "title" => __("Number of tickets created each months", "mydashboard"),
                    "type" => Widget::$LINE,
                    "comment" => "",
                ],
                $this->getType() . "44" => [
                    "title" => __("Number of tickets created each week", "mydashboard"),
                    "type" => Widget::$LINE,
                    "comment" => "",
                ],
                $this->getType() . "45" => [
                    "title" => __("Number of tickets with validation refusal", "mydashboard"),
                    "type" => Widget::$LINE,
                    "comment" => "",
                ],
                $this->getType() . "46" => [
                    "title" => __("Number of tickets linked with problems", "mydashboard"),
                    "type" => Widget::$LINE,
                    "comment" => "",
                ],
                $this->getType() . "47" => [
                    "title" => __("Backlog tickets by week", "mydashboard"),
                    "type" => Widget::$LINE,
                    "comment" => __("Number of in progress (not new and pending) tickets by week", "mydashboard"),
                ],
                $this->getType() . "48" => [
                    "title" => __("Monthly tickets in progress", "mydashboard"),
                    "type" => Widget::$LINE,
                    "comment" => __(
                        "Number of open tickets in the month still in progress for each month",
                        "mydashboard"
                    ),
                ],
                $this->getType() . "49" => [
                    "title" => __("Number of tickets with more than one solution", "mydashboard"),
                    "type" => Widget::$LINE,
                    "comment" => "",
                ],
            ],
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
     * @return Html
     * @throws \GlpitestSQLError
     */
    public function getWidgetContentForItem($widgetId, $opt = [])
    {
        global $DB;
        $isDebug = $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE;

        $preference = new MydashboardPreference();
        if (Session::getLoginUserID() !== false
            && !$preference->getFromDB(Session::getLoginUserID())) {
            $preference->initPreferences(Session::getLoginUserID());
        }
        $preference->getFromDB(Session::getLoginUserID());
        $preferences = $preference->fields;

        switch ($widgetId) {
            case $this->getType() . "6":
                $name = 'TicketStockLineChart';
                $criterias = Criteria::getDefaultCriterias();

                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() == 'central') {
                    $onclick = 1;
                }

                $params = [
                    "preferences" => $preferences,
                    "criterias" => $criterias,
                    "opt" => $opt,
                ];

                $default = Criteria::manageCriterias($params);

                $technicians_groups_id = $opt['technicians_groups_id'] ?? $default['technicians_groups_id'];

                $is_deleted = ['glpi_tickets.is_deleted' => 0];

                $currentmonth = date("m");
                $currentyear = $opt["year"] ?? $default["year"];
                $previousyear = $currentyear - 1;

                $criteria = [
                    'SELECT' => [
                        new QueryExpression(
                            "DATE_FORMAT(" . $DB->quoteName(
                                "glpi_plugin_mydashboard_stocktickets.date"
                            ) . ", '%Y-%m') AS month"
                        ),
                        new QueryExpression(
                            "DATE_FORMAT(" . $DB->quoteName(
                                "glpi_plugin_mydashboard_stocktickets.date"
                            ) . ", '%b %Y') AS monthname"
                        ),
                        'SUM' => 'nbStockTickets AS nbStockTickets',
                    ],
                    'FROM' => 'glpi_plugin_mydashboard_stocktickets',
                    'WHERE' => [
                        [
                            [
                                'glpi_plugin_mydashboard_stocktickets.date' => [
                                    '>=',
                                    "$previousyear-$currentmonth-01 00:00:00",
                                ],
                            ],
                            [
                                'glpi_plugin_mydashboard_stocktickets.date' => [
                                    '<=',
                                    "$currentyear-$currentmonth-01 00:00:00",
                                ],
                            ],
                        ],
                    ],
                    'GROUPBY' => 'month',
                ];

                if (is_array($technicians_groups_id)) {
                    $technicians_groups_id = array_filter($technicians_groups_id);
                    if (count($technicians_groups_id) > 0) {
                        $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_plugin_mydashboard_stocktickets.groups_id' => $technicians_groups_id];
                    }
                }

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                    'glpi_plugin_mydashboard_stocktickets'
                );

                $iterator = $DB->request($criteria);

                $tabdata = [];
                $tabnames = [];
                $maxcount = 0;
                $i = 0;

                foreach ($iterator as $data) {
                    $tabdata[$i] = $data["nbStockTickets"];
                    $tabnames[] = $data['monthname'];
                    if ($data["nbStockTickets"] > $maxcount) {
                        $maxcount = $data["nbStockTickets"];
                    }
                    $i++;
                }

                $criteria_1 = [
                    'SELECT' => [
                        new QueryExpression(
                            "DATE_FORMAT(" . $DB->quoteName("glpi_tickets.date") . ", '%Y-%m') AS month"
                        ),
                        new QueryExpression(
                            "DATE_FORMAT(" . $DB->quoteName("glpi_tickets.date") . ", '%b %Y') AS monthname"
                        ),
                        new QueryExpression(
                            "DATE_FORMAT(" . $DB->quoteName("glpi_tickets.date") . ", '%Y%m') AS monthnum"
                        ),
                        new QueryExpression("count(MONTH(" . $DB->quoteName("glpi_tickets.date") . "))"),
                    ],
                    'FROM' => 'glpi_tickets',
                    'WHERE' => [
                        $is_deleted,
                        new QueryExpression("MONTH(" . $DB->quoteName("glpi_tickets.date") . ") = " . date("m") . ""),
                        new QueryExpression("YEAR(" . $DB->quoteName("glpi_tickets.date") . ") = " . date("Y") . ""),
                    ],
                    'GROUPBY' => 'month',
                ];

                $criteria_1 = Criteria::addCriteriasForQuery($criteria_1, $params);

                $iterator_1 = $DB->request($criteria_1);

                $nbtickets = __('Tickets number', 'mydashboard');
                foreach ($iterator_1 as $data) {
                    [$year, $month] = explode('-', $data['month']);

                    $nbdays = date("t", mktime(0, 0, 0, $month, 1, $year));

                    $criteria_2 = [
                        'SELECT' => [
                            'COUNT' => 'glpi_tickets.id AS count',
                        ],
                        'FROM' => 'glpi_tickets',
                        'WHERE' => [
                            $is_deleted,
                            'glpi_tickets.date' => ['<=', "$year-$month-$nbdays 23:59:59"],
                            'glpi_tickets.status' => \Ticket::getNotSolvedStatusArray(),
                        ],
                    ];

                    $criteria_2 = Criteria::addCriteriasForQuery($criteria_2, $params);

                    $iterator_2 = $DB->request($criteria_2);

                    foreach ($iterator_2 as $data_1) {
                        $tabdata[$i] = $data_1['count'];
                    }
                    $tabnames[] = $data['monthname'];
                    $i++;
                }

                $widget = new Html();
                $title = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetComment($comment);
                $widget->setWidgetTitle((($isDebug) ? "6 " : "") . $title);
                $widget->toggleWidgetRefresh();

                $datasets[]
                    = [
                        'type' => 'line',
                        'data' => $tabdata,
                        'name' => $nbtickets,
                        'smooth' => false,
                    ];
                $dataLineset = json_encode($datasets);
                $labelsLine = json_encode($tabnames);
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
                    "nb" => 1,
                ];
                $widget->setWidgetHeader(Helper::getGraphHeader($params));

                $widget->setWidgetHtmlContent(
                    $graph
                );

                return $widget;


            case $this->getType() . "22":
                $name = 'TicketStatusBarLineChart';
                $onclick = 0;

                $criterias = Criteria::getDefaultCriterias();
                $criterias = array_filter($criterias, fn($v) => $v !== Year::$criteria_name);

                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() == 'central') {
                    $onclick = 1;
                    $specific_criterias = [
                        DisplayData::$criteria_name,
                        Location::$criteria_name,
                        Technician::$criteria_name,
                        RequesterGroup::$criteria_name,
                    ];
                    $criterias = array_merge($criterias, $specific_criterias);
                }
                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() != 'central') {
                    $specific_criterias = [
                        DisplayData::$criteria_name,
                        Location::$criteria_name,
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

                $technicians_groups_id = $opt['technicians_groups_id'] ?? $default['technicians_groups_id'];

                if (isset($opt["display_data"]) && $opt['display_data'] == "YEAR") {
                    if (isset($opt["year"]) && $opt["year"] > 0) {
                        $currentyear = $opt["year"];
                    }

                    $date_crit_ticket = [
                        ['glpi_tickets.date' => ['<=', $currentyear . '-12-31 23:59:59']],
                        ['glpi_tickets.date' => ['>', new QueryExpression("ADDDATE('$currentyear-01-01 00:00:00' , INTERVAL 1 DAY)")]],
                    ];
                    $date_crit_stockticket = [
                        ['glpi_plugin_mydashboard_stocktickets.date' => ['<=', $currentyear . '-12-31 23:59:59']],
                        ['glpi_plugin_mydashboard_stocktickets.date' => ['>', new QueryExpression("ADDDATE('$currentyear-01-01 00:00:00' , INTERVAL 1 DAY)")]],
                    ];

                } else {
                    $end_year = $opt['end_year'] ?? date("Y");
                    $end_month = $opt['end_month'] ?? date("m");
                    $start_month = $opt['start_month'] ?? date('m');
                    $start_year = $opt['start_year'] ?? date("Y");

                    if (strlen($start_month) == 1) {
                        $start_month = "0" . $start_month;
                    }
                    $nbdays = date("t", mktime(0, 0, 0, $end_month, 1, $end_year));

                    $date_crit_ticket = [
                        ['glpi_tickets.date' => ['>=', "$start_year-$start_month-01 00:00:00"]],
                        ['glpi_tickets.date' => ['<=',  "$end_year-$end_month-$nbdays 00:00:00"]],
                    ];
                    $date_crit_stockticket = [
                        ['glpi_plugin_mydashboard_stocktickets.date' => ['>=', "$start_year-$start_month-01 00:00:00"]],
                        ['glpi_plugin_mydashboard_stocktickets.date' => ['<=',  "$end_year-$end_month-$nbdays 00:00:00"]],
                    ];
                }

                $criteria = [
                    'SELECT' => [
                        new QueryExpression("DATE_FORMAT(" . $DB->quoteName("glpi_plugin_mydashboard_stocktickets.date") . ", '%Y-%m') AS month"),
                        new QueryExpression("DATE_FORMAT(" . $DB->quoteName("glpi_plugin_mydashboard_stocktickets.date") . ", '%b %Y') AS monthname"),
                        'SUM' => 'nbStockTickets AS nbStockTickets',
                    ],
                    'FROM' => 'glpi_plugin_mydashboard_stocktickets',
                    'WHERE' => [
                        $date_crit_stockticket,
                    ],
                    'GROUPBY' => 'month',
                ];

                if (is_array($technicians_groups_id)) {

                    $technicians_groups_id = array_filter($technicians_groups_id);
                    if (count($technicians_groups_id) > 0) {
                        $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_plugin_mydashboard_stocktickets.groups_id' => $technicians_groups_id];
                    }
                }

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                    'glpi_plugin_mydashboard_stocktickets'
                );

                $iterator = $DB->request($criteria);

                $nbStockTickets = count($iterator);
                $maxcount = 0;
                $tabdates = [];
                $tabopened = [];
                $tabclosed = [];
                $tabprogress = [];
                $tabnames = [];

                if ($nbStockTickets) {
                    foreach ($iterator as $data) {

                        $tabprogress[] = $data["nbStockTickets"];
                        if ($data["nbStockTickets"] > $maxcount) {
                            $maxcount = $data["nbStockTickets"];
                        }
                        $tabdates[0][] = $data['month'] . '_progress';
                    }
                }

                $is_deleted = ['glpi_tickets.is_deleted' => 0];

                $query_tickets = [
                    'SELECT' => [
                        new QueryExpression(
                            "DATE_FORMAT(" . $DB->quoteName("glpi_tickets.date") . ", '%Y-%m') AS month"
                        ),
                        new QueryExpression(
                            "DATE_FORMAT(" . $DB->quoteName("glpi_tickets.date") . ", '%b %Y') AS monthname"
                        ),
                        new QueryExpression(
                            "DATE_FORMAT(" . $DB->quoteName("glpi_tickets.date") . ", '%Y%m') AS monthnum"
                        ),
                        new QueryExpression("count(MONTH(" . $DB->quoteName("glpi_tickets.date") . "))"),
                    ],
                    'FROM' => 'glpi_tickets',
                    'WHERE' => [
                        $is_deleted,
                        $date_crit_ticket,
                    ],
                    'GROUPBY' => 'month',
                ];

                $query_tickets = Criteria::addCriteriasForQuery($query_tickets, $params);

                $iterator_tickets = $DB->request($query_tickets);

                if (count($iterator_tickets) > 0) {

                    foreach ($iterator_tickets as $data) {

                        $tabnames[] = $data['monthname'];

                        [$year, $month] = explode('-', $data['month']);
                        $nbdays = date("t", mktime(0, 0, 0, $month, 1, $year));
                        $query_1 = [
                            'SELECT' => [
                                'COUNT' => 'glpi_tickets.id AS count',
                            ],
                            'FROM' => 'glpi_tickets',
                            'WHERE' => [
                                $is_deleted,
                                [
                                    ['glpi_tickets.date' => ['>', "$year-$month-01 00:00:01"]],
                                    ['glpi_tickets.date' =>  ['<=', new QueryExpression("ADDDATE('$year-$month-$nbdays 00:00:00' , INTERVAL 1 DAY)")]],
                                ],
                            ],
                        ];


                        $query_1 = Criteria::addCriteriasForQuery($query_1, $params);

                        $iterator_1 = $DB->request($query_1);

                        if (count($iterator_1) > 0) {
                            foreach ($iterator_1 as $data_1) {
                                $tabopened[] = $data_1['count'];
                            }
                        } else {
                            $tabopened[] = 0;
                        }
                        $tabdates[1][] = $data['month'] . '_opened';

                        $query_2 = [
                            'SELECT' => [
                                'COUNT' => 'glpi_tickets.id AS count',
                            ],
                            'FROM' => 'glpi_tickets',
                            'WHERE' => [
                                $is_deleted,
                                [
                                    ['glpi_tickets.closedate' => ['>', "$year-$month-01 00:00:01"]],
                                    ['glpi_tickets.closedate' =>  ['<=', new QueryExpression("ADDDATE('$year-$month-$nbdays 00:00:00' , INTERVAL 1 DAY)")]],
                                ],
                            ],
                        ];

                        $query_2 = Criteria::addCriteriasForQuery($query_2, $params);

                        $iterator_2 = $DB->request($query_2);

                        if (count($iterator_2) > 0) {
                            foreach ($iterator_2 as $data_2) {
                                $tabclosed[] = $data_2['count'];
                            }
                        } else {
                            $tabclosed[] = 0;
                        }
                        $tabdates[2][] = $data['month'] . '_closed';


                        if ($month == date("m") && $year == date("Y")) {
                            $nbdays = date("t", mktime(0, 0, 0, $month, 1, $year));

                            $query_3 = [
                                'SELECT' => [
                                    'COUNT' => 'glpi_tickets.id AS count',
                                ],
                                'FROM' => 'glpi_tickets',
                                'WHERE' => [
                                    $is_deleted,
                                    'glpi_tickets.date' => ['<=', "$year-$month-$nbdays 23:59:59"],
                                    'glpi_tickets.status' => \Ticket::getNotSolvedStatusArray(),
                                ],
                            ];

                            $query_3 = Criteria::addCriteriasForQuery($query_3, $params);

                            $iterator_3 = $DB->request($query_3);

                            if (count($iterator_3) > 0) {

                                foreach ($iterator_3 as $data_3) {

                                    $tabprogress[] = $data_3['count'];
                                }
                            } else {
                                $tabprogress[] = 0;
                            }
                        }
                    }
                }

                $widget = new Html();
                $title = __("Number of opened and closed tickets by month", "mydashboard");
                $comment = "";
                $widget->setWidgetTitle((($isDebug) ? "22 " : "") . $title);
                $widget->toggleWidgetRefresh();

                $titleopened = __("Opened tickets", "mydashboard");
                $titlesolved = __("Closed tickets", "mydashboard");
                $titleprogress = __("Opened tickets backlog", "mydashboard");
                $labels = json_encode($tabnames);

                $datasets[]
                    = [
                        'type' => 'line',
                        'data' => $tabprogress,
                        'name' => $titleprogress,
                        'smooth' => false,
                    ];

                $datasets[]
                    = [
                        "type" => "bar",
                        "data" => $tabopened,
                        "name" => $titleopened,
                    ];

                $datasets[]
                    = [
                        'type' => 'bar',
                        'data' => $tabclosed,
                        'name' => $titlesolved,
                    ];


                $tabdatesset = json_encode($tabdates);

                $graph_datas = [
                    'title' => $title,
                    'comment' => $comment,
                    'name' => $name,
                    'ids' => $tabdatesset,
                    'data' => json_encode($datasets),
                    'labels' => $labels,
                    //                            'label'  => $title
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


            case $this->getType() . "34":
                $name = 'TicketStatusResolvedBarLineChart';
                $onclick = 0;

                $criterias = Criteria::getDefaultCriterias();

                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() == 'central') {
                    $onclick = 1;
                    $specific_criterias = [
                        Location::$criteria_name,
                        Technician::$criteria_name,
                        RequesterGroup::$criteria_name,
                    ];
                    $criterias = array_merge($criterias, $specific_criterias);
                }
                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() != 'central') {
                    $specific_criterias = [
                        Location::$criteria_name,
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

                $technicians_groups_id = $opt['technicians_groups_id'] ?? $default['technicians_groups_id'];

                $currentyear = $opt["year"] ?? $default["year"];

                $criteria = [
                    'SELECT' => [
                        new QueryExpression(
                            "DATE_FORMAT(" . $DB->quoteName(
                                "glpi_plugin_mydashboard_stocktickets.date"
                            ) . ", '%Y-%m') AS month"
                        ),
                        new QueryExpression(
                            "DATE_FORMAT(" . $DB->quoteName(
                                "glpi_plugin_mydashboard_stocktickets.date"
                            ) . ", '%b %Y') AS monthname"
                        ),
                        'SUM' => 'nbStockTickets AS nbStockTickets',
                    ],
                    'FROM' => 'glpi_plugin_mydashboard_stocktickets',
                    'WHERE' => [
                        [
                            [
                                'glpi_plugin_mydashboard_stocktickets.date' => [
                                    '>=',
                                    "$currentyear-01-01 00:00:00",
                                ],
                            ],
                            ['glpi_plugin_mydashboard_stocktickets.date' => ['<=', new QueryExpression("ADDDATE('$currentyear-01-01 00:00:00' , INTERVAL 1 YEAR)")]],
                        ],
                    ],
                    'GROUPBY' => 'month',
                ];

                if (is_array($technicians_groups_id)) {
                    $technicians_groups_id = array_filter($technicians_groups_id);
                    if (count($technicians_groups_id) > 0) {
                        $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_plugin_mydashboard_stocktickets.groups_id' => $technicians_groups_id];
                    }
                }

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                    'glpi_plugin_mydashboard_stocktickets'
                );

                $iterator = $DB->request($criteria);

                $nbStockTickets = count($iterator);
                $maxcount = 0;
                $i = 0;
                $tabdates = [];
                $tabopened = [];
                $tabresolved = [];
                $tabprogress = [];
                $tabnames = [];
                if ($nbStockTickets) {
                    foreach ($iterator as $data) {
                        $tabprogress[] = $data["nbStockTickets"];
                        if ($data["nbStockTickets"] > $maxcount) {
                            $maxcount = $data["nbStockTickets"];
                        }
                        $tabdates[0][] = $data['month'] . '_progress';
                    }
                }

                $is_deleted = ['glpi_tickets.is_deleted' => 0];

                $criteria_1 = [
                    'SELECT' => [
                        new QueryExpression(
                            "DATE_FORMAT(" . $DB->quoteName("glpi_tickets.date") . ", '%Y-%m') AS month"
                        ),
                        new QueryExpression(
                            "DATE_FORMAT(" . $DB->quoteName("glpi_tickets.date") . ", '%b %Y') AS monthname"
                        ),
                        new QueryExpression(
                            "DATE_FORMAT(" . $DB->quoteName("glpi_tickets.date") . ", '%Y%m') AS monthnum"
                        ),
                        new QueryExpression("count(MONTH(" . $DB->quoteName("glpi_tickets.date") . "))"),
                    ],
                    'FROM' => 'glpi_tickets',
                    'WHERE' => [
                        $is_deleted,
                        [
                            [
                                'glpi_tickets.date' => [
                                    '>=',
                                    "$currentyear-01-01 00:00:00",
                                ],
                            ],
                            ['glpi_tickets.date' => ['<=', new QueryExpression("ADDDATE('$currentyear-01-01 00:00:00' , INTERVAL 1 YEAR)")]],
                        ],
                    ],
                    'GROUPBY' => 'month',
                ];

                $criteria_1 = Criteria::addCriteriasForQuery($criteria_1, $params);

                $iterator_1 = $DB->request($criteria_1);

                $nbResults = count($iterator_1);

                if ($nbResults) {
                    foreach ($iterator_1 as $data_1) {

                        $tabnames[] = $data_1['monthname'];

                        [$year, $month] = explode('-', $data_1['month']);
                        $nbdays = date("t", mktime(0, 0, 0, $month, 1, $year));

                        $criteria_2 = [
                            'SELECT' => [
                                'COUNT' => 'glpi_tickets.id AS count',
                            ],
                            'FROM' => 'glpi_tickets',
                            'WHERE' => [
                                $is_deleted,
                                [
                                    ['glpi_tickets.date' => ['>', "$year-$month-01 00:00:01"]],
                                    ['glpi_tickets.date' =>  ['<=', new QueryExpression("ADDDATE('$year-$month-$nbdays 00:00:00' , INTERVAL 1 DAY)")]],
                                ],
                            ],
                        ];

                        $criteria_2 = Criteria::addCriteriasForQuery($criteria_2, $params);

                        $iterator_2 = $DB->request($criteria_2);

                        if (count($iterator_2) > 0) {
                            foreach ($iterator_2 as $data_2) {
                                $tabopened[] = $data_2['count'];
                            }
                        } else {
                            $tabopened[] = 0;
                        }
                        $tabdates[1][] = $data_1['month'] . '_opened';

                        $criteria_3 = [
                            'SELECT' => [
                                'COUNT' => 'glpi_tickets.id AS count',
                            ],
                            'FROM' => 'glpi_tickets',
                            'WHERE' => [
                                $is_deleted,
                                'glpi_tickets.status' => [CommonITILObject::SOLVED, CommonITILObject::CLOSED],
                                [
                                    [
                                        'glpi_tickets.solvedate' => [
                                            '>=',
                                            "$year-$month-01 00:00:00",
                                        ],
                                    ],
                                    ['glpi_tickets.solvedate' => ['<=', new QueryExpression("ADDDATE('$year-$month-$nbdays 00:00:00' , INTERVAL 1 DAY)")]],
                                ],
                            ],
                        ];

                        $criteria_3 = Criteria::addCriteriasForQuery($criteria_3, $params);

                        $iterator_3 = $DB->request($criteria_3);

                        if (count($iterator_3) > 0) {
                            foreach ($iterator_3 as $data_3) {
                                $tabresolved[] = $data_3['count'];
                            }
                        } else {
                            $tabresolved[] = 0;
                        }
                        $tabdates[2][] = $data_1['month'] . '_resolved';

                        if ($month == date("m") && $year == date("Y")) {
                            $nbdays = date("t", mktime(0, 0, 0, $month, 1, $year));
                            //nbstock : cannot use tech or group criteria

                            $criteria_4 = [
                                'SELECT' => [
                                    'COUNT' => 'glpi_tickets.id AS count',
                                ],
                                'FROM' => 'glpi_tickets',
                                'WHERE' => [
                                    $is_deleted,
                                    'glpi_tickets.status' => \Ticket::getNotSolvedStatusArray(),
                                    'glpi_tickets.date' => [
                                        '<=',
                                        "$year-$month-$nbdays 23:59:59",
                                    ],
                                ],
                            ];

                            $criteria_4 = Criteria::addCriteriasForQuery($criteria_4, $params);

                            $iterator_4 = $DB->request($criteria_4);

                            if (count($iterator_4) > 0) {
                                foreach ($iterator_4 as $data_4) {
                                    $tabprogress[] = $data_4['count'];
                                }
                            } else {
                                $tabprogress[] = 0;
                            }
                            $tabdates[0][] = 0;
                        }

                    }
                }

                $widget = new Html();
                $title = __("Number of opened and resolved / closed tickets by month", "mydashboard");
                $comment = "";
                $widget->setWidgetTitle((($isDebug) ? "34 " : "") . $title);
                $widget->toggleWidgetRefresh();

                $titleopened = __("Opened tickets", "mydashboard");
                $titlesolved = __("Resolved / closed tickets", "mydashboard");
                $titleprogress = __("Opened tickets backlog", "mydashboard");
                $labels = json_encode($tabnames);

                $datasets[]
                    = [
                        'type' => 'line',
                        'data' => $tabprogress,
                        'name' => $titleprogress,
                        'smooth' => false,
                    ];

                $datasets[]
                    = [
                        "type" => "bar",
                        "data" => $tabopened,
                        "name" => $titleopened,
                    ];

                $datasets[]
                    = [
                        'type' => 'bar',
                        'data' => $tabresolved,
                        'name' => $titlesolved,
                    ];

                $tabdatesset = json_encode($tabdates);

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
                break;

            case $this->getType() . "35":
                $name = 'TicketStatusUnplannedBarLineChart';
                $onclick = 0;

                $criterias = Criteria::getDefaultCriterias();

                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() == 'central') {
                    $onclick = 1;
                    $specific_criterias = [
                        Location::$criteria_name,
                        Technician::$criteria_name,
                        RequesterGroup::$criteria_name,
                    ];
                    $criterias = array_merge($criterias, $specific_criterias);
                }
                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() != 'central') {
                    $specific_criterias = [
                        Location::$criteria_name,
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

                $technicians_groups_id = $opt['technicians_groups_id'] ?? $default['technicians_groups_id'];

                $currentyear = $opt["year"] ?? $default["year"];

                if (isset($opt["year"]) && $opt["year"] > 0) {
                    $currentyear = $opt["year"];
                }

                //                $query_stockTickets
                //                    = "SELECT DATE_FORMAT(`glpi_plugin_mydashboard_stocktickets`.`date`, '%Y-%m') as month,"
                //                    . " DATE_FORMAT(`glpi_plugin_mydashboard_stocktickets`.`date`, '%b %Y') as monthname,"
                //                    . " SUM(nbStockTickets) as nbStockTickets"
                //                    . " FROM `glpi_plugin_mydashboard_stocktickets`"
                //                    . " WHERE `glpi_plugin_mydashboard_stocktickets`.`date` between '$currentyear-01-01' AND ADDDATE('$currentyear-01-01', INTERVAL 1 YEAR)"
                //                    . " " . $mdentities . $tech_groups_crit
                //                    . " GROUP BY DATE_FORMAT(`glpi_plugin_mydashboard_stocktickets`.`date`, '%Y-%m')";


                $criteria = [
                    'SELECT' => [
                        new QueryExpression(
                            "DATE_FORMAT(" . $DB->quoteName(
                                "glpi_plugin_mydashboard_stocktickets.date"
                            ) . ", '%Y-%m') AS month"
                        ),
                        new QueryExpression(
                            "DATE_FORMAT(" . $DB->quoteName(
                                "glpi_plugin_mydashboard_stocktickets.date"
                            ) . ", '%b %Y') AS monthname"
                        ),
                        'SUM' => 'nbStockTickets AS nbStockTickets',
                    ],
                    'FROM' => 'glpi_plugin_mydashboard_stocktickets',
                    'WHERE' => [
                        [
                            ['glpi_plugin_mydashboard_stocktickets.date' => ['<=', $currentyear . '-12-31 23:59:59']],
                            ['glpi_plugin_mydashboard_stocktickets.date' => ['>', new QueryExpression("ADDDATE('$currentyear-01-01 00:00:00' , INTERVAL 1 DAY)")]],
                        ],

                    ],
                    'GROUPBY' => 'month',
                ];

                if (is_array($technicians_groups_id)) {
                    $technicians_groups_id = array_filter($technicians_groups_id);
                    if (count($technicians_groups_id) > 0) {
                        $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_plugin_mydashboard_stocktickets.groups_id' => $technicians_groups_id];
                    }
                }

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                    'glpi_plugin_mydashboard_stocktickets'
                );

                $iterator = $DB->request($criteria);

                $nbStockTickets = count($iterator);
                $maxcount = 0;
                $i = 0;
                $tabdates = [];
                $tabopened = [];
                $tabclosed = [];
                $tabprogress = [];
                $tabunplanned = [];
                $tabnames = [];
                if ($nbStockTickets) {
                    foreach ($iterator as $data) {
                        $tabprogress[] = $data["nbStockTickets"];
                        if ($data["nbStockTickets"] > $maxcount) {
                            $maxcount = $data["nbStockTickets"];
                        }
                        [$year, $month] = explode('-', $data['month']);
                        $tabdates[0][] = $data['month'] . '_progress';
                        $i++;
                    }
                }

                $is_deleted = ['glpi_tickets.is_deleted' => 0];

                $criteria_1 = [
                    'SELECT' => [
                        new QueryExpression(
                            "DATE_FORMAT(" . $DB->quoteName("glpi_tickets.date") . ", '%Y-%m') AS month"
                        ),
                        new QueryExpression(
                            "DATE_FORMAT(" . $DB->quoteName("glpi_tickets.date") . ", '%b %Y') AS monthname"
                        ),
                        new QueryExpression(
                            "DATE_FORMAT(" . $DB->quoteName("glpi_tickets.date") . ", '%Y%m') AS monthnum"
                        ),
                        new QueryExpression("count(MONTH(" . $DB->quoteName("glpi_tickets.date") . "))"),
                    ],
                    'FROM' => 'glpi_tickets',
                    'WHERE' => [
                        $is_deleted,
                        [
                            [
                                'glpi_tickets.date' => [
                                    '>=',
                                    "$currentyear-01-01 00:00:00",
                                ],
                            ],
                            ['glpi_tickets.date' => ['<=', new QueryExpression("ADDDATE('$currentyear-01-01 00:00:00' , INTERVAL 1 YEAR)")]],
                        ],
                    ],
                    'GROUPBY' => 'month',
                ];

                $criteria_1 = Criteria::addCriteriasForQuery($criteria_1, $params);

                $iterator_1 = $DB->request($criteria_1);

                $nbResults = count($iterator_1);
                $i = 0;
                if ($nbResults) {
                    foreach ($iterator_1 as $data) {
                        $tabnames[] = $data['monthname'];

                        [$year, $month] = explode('-', $data['month']);

                        $nbdays = date("t", mktime(0, 0, 0, $month, 1, $year));

                        $query_1 = [
                            'SELECT' => [
                                'COUNT' => 'glpi_tickets.id AS count',
                            ],
                            'FROM' => 'glpi_tickets',
                            'WHERE' => [
                                $is_deleted,
                                [
                                    ['glpi_tickets.date' => ['>', "$year-$month-01 00:00:01"]],
                                    ['glpi_tickets.date' => ['<=', new QueryExpression("ADDDATE('$year-$month-$nbdays 00:00:00' , INTERVAL 1 DAY)")]],
                                ],
                            ],
                        ];

                        $query_1 = Criteria::addCriteriasForQuery($query_1, $params);

                        $iterator_1 = $DB->request($query_1);

                        if (count($iterator_1) > 0) {

                            foreach ($iterator_1 as $data_1) {
                                $tabopened[] = $data_1['count'];
                            }
                        } else {
                            $tabopened[] = 0;
                        }
                        $tabdates[1][] = $data['month'] . '_opened';

                        $query_2 = [
                            'SELECT' => [
                                'COUNT' => 'glpi_tickets.id AS count',
                            ],
                            'FROM' => 'glpi_tickets',
                            'WHERE' => [
                                $is_deleted,
                                [
                                    ['glpi_tickets.closedate' => ['>', "$year-$month-01 00:00:01"]],
                                    ['glpi_tickets.closedate' => ['<=', new QueryExpression("ADDDATE('$year-$month-$nbdays 00:00:00' , INTERVAL 1 DAY)")]],
                                ],
                            ],
                        ];

                        $query_2 = Criteria::addCriteriasForQuery($query_2, $params);

                        $iterator_2 = $DB->request($query_2);

                        if (count($iterator_2) > 0) {
                            foreach ($iterator_2 as $data_2) {
                                $tabclosed[] = $data_2['count'];
                            }
                        } else {
                            $tabclosed[] = 0;
                        }
                        $tabdates[2][] = $data['month'] . '_closed';

                        //                        $whereUnplanned = " AND `glpi_tickettasks`.`actiontime` IS NULL ";
                        $whereUnplanned = ['glpi_tickettasks.actiontime' => 0];
                        //                        $query_3
                        //                            = "SELECT COUNT(*) as count FROM `glpi_tickets`"
                        //                            . " $ticket_users_join"
                        //                            . " LEFT JOIN `glpi_tickettasks` ON `glpi_tickets`.`id` = `glpi_tickettasks`.`tickets_id`"
                        //                            . " WHERE $closedate_criteria"
                        //                            . " $technician_criteria"
                        //                            . " $entities_criteria"
                        //                            . " $requester_groups_criteria"
                        //                            . " $technician_groups_criteria"
                        //                            . " $locations_criteria"
                        //                            . " $type_criteria"
                        //                            . " AND $is_deleted $whereUnplanned";

                        $query_3 = [
                            'SELECT' => [
                                'COUNT' => 'glpi_tickets.id AS count',
                            ],
                            'FROM' => 'glpi_tickets',
                            'LEFT JOIN'       => [
                                'glpi_tickettasks' => [
                                    'ON' => [
                                        'glpi_tickettasks' => 'tickets_id',
                                        'glpi_tickets'          => 'id',
                                    ],
                                ],
                            ],
                            'WHERE' => [
                                $is_deleted,
                                $whereUnplanned,
                                [
                                    ['glpi_tickets.closedate' => ['>', "$year-$month-01 00:00:01"]],
                                    ['glpi_tickets.closedate' => ['<=', new QueryExpression("ADDDATE('$year-$month-$nbdays 00:00:00' , INTERVAL 1 DAY)")]],
                                ],
                            ],
                        ];

                        $query_3 = Criteria::addCriteriasForQuery($query_3, $params);

                        $iterator_3 = $DB->request($query_3);

                        if (count($iterator_3) > 0) {

                            foreach ($iterator_3 as $data_3) {
                                $tabunplanned[] = $data_3['count'];
                            }
                        } else {
                            $tabunplanned[] = 0;
                        }
                        $tabdates[3][] = $data['month'] . '_unplanned';

                        if ($month == date("m") && $year == date("Y")) {
                            $nbdays = date("t", mktime(0, 0, 0, $month, 1, $year));

                            $criteria_4 = [
                                'SELECT' => [
                                    'COUNT' => 'glpi_tickets.id AS count',
                                ],
                                'FROM' => 'glpi_tickets',
                                'WHERE' => [
                                    $is_deleted,
                                    'glpi_tickets.status' => \Ticket::getNotSolvedStatusArray(),
                                    'glpi_tickets.date' => [
                                        '<=',
                                        "$year-$month-$nbdays 23:59:59",
                                    ],
                                ],
                            ];

                            $criteria_4 = Criteria::addCriteriasForQuery($criteria_4, $params);

                            $iterator_4 = $DB->request($criteria_4);

                            if (count($iterator_4) > 0) {
                                foreach ($iterator_4 as $data_4) {
                                    $tabprogress[] = $data_4['count'];
                                }
                            } else {
                                $tabprogress[] = 0;
                            }
                            $tabdates[0][] = 0;
                        }
                    }
                }

                $widget = new Html();
                $title = __("Number of opened, closed  and unplanned tickets by month", "mydashboard");
                $comment = "";
                $widget->setWidgetTitle((($isDebug) ? "35 " : "") . $title);
                $widget->toggleWidgetRefresh();

                $titleopened = __("Opened tickets", "mydashboard");
                $titlesolved = __("Closed tickets", "mydashboard");
                $titleunplanned = __("Not planned", "mydashboard");
                $titleprogress = __("Opened tickets backlog", "mydashboard");
                $labels = json_encode($tabnames);

                $datasets[]
                    = [
                        'type' => 'line',
                        'data' => $tabprogress,
                        'name' => $titleprogress,
                        'smooth' => false,
                    ];

                $datasets[]
                    = [
                        "type" => "bar",
                        "data" => $tabopened,
                        "name" => $titleopened,
                    ];

                $datasets[]
                    = [
                        'type' => 'bar',
                        'data' => $tabclosed,
                        'name' => $titlesolved,
                    ];

                $datasets[]
                    = [
                        'type' => 'bar',
                        'data' => $tabunplanned,
                        'name' => $titleunplanned,
                    ];

                $tabdatesset = json_encode($tabdates);

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


            case $this->getType() . "43":
                $name = 'reportLineChartNbCreatedTicketByMonths';

                $criterias = Criteria::getDefaultCriterias();

                $params = [
                    "preferences" => $preferences,
                    "criterias" => $criterias,
                    "opt" => $opt,
                ];

                $default = Criteria::manageCriterias($params);

                $is_deleted = ['glpi_tickets.is_deleted' => 0];

                $currentmonth = date("m");
                $currentyear = $opt["year"] ?? $default["year"];
                $now = date("Y-m-d");

                $previousyear = $currentyear - 1;
                $tabdates = [];
                //                $queryOpenedTicket = "SELECT DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m') as period,
                //                                         DATE_FORMAT(`glpi_tickets`.`date`, '%b %Y') as monthname,
                //                                         count(*) as count
                //                                  FROM `glpi_tickets`
                //                                  WHERE  (`glpi_tickets`.`date` >= '$previousyear-$currentmonth-01 00:00:00')
                //                                  AND (`glpi_tickets`.`date` <= '$now 23:59:59')
                //                                  " . $entities_criteria . $is_deleted . $type_criteria . "
                //                                  GROUP BY DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m')";

                $criteria_1 = [
                    'SELECT' => [
                        new QueryExpression(
                            "DATE_FORMAT(" . $DB->quoteName("glpi_tickets.date") . ", '%Y-%m') AS period"
                        ),
                        new QueryExpression(
                            "DATE_FORMAT(" . $DB->quoteName("glpi_tickets.date") . ", '%b %Y') AS monthname"
                        ),
                        'COUNT' => 'glpi_tickets.id AS count',
                    ],
                    'FROM' => 'glpi_tickets',
                    'WHERE' => [
                        $is_deleted,
                        [
                            [
                                'glpi_tickets.date' => [
                                    '>=',
                                    "$previousyear-$currentmonth-01 00:00:00",
                                ],
                            ],
                            ['glpi_tickets.date' => ['<=', new QueryExpression("ADDDATE('$now 23:59:59' , INTERVAL 1 DAY)")]],
                        ],
                    ],
                    'GROUPBY' => 'period',
                ];

                $criteria_1 = Criteria::addCriteriasForQuery($criteria_1, $params);

                $iterator_1 = $DB->request($criteria_1);

                $tabdata = [];
                $tabnames = [];

                foreach ($iterator_1 as $data) {
                    $tabdata[] = $data['count'];
                    $tabnames[] = $data['monthname'];
                    $tabdates[] = $data['period'];
                }


                $widget = new Html();

                $title = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetTitle((($isDebug) ? "43 " : "") . $title);
                $widget->setWidgetComment($comment);

                $widget->toggleWidgetRefresh();

                $nbtickets = __('Tickets number', 'mydashboard');
                $datasets[]
                    = [
                        'type' => 'line',
                        'data' => $tabdata,
                        'name' => $nbtickets,
                        'smooth' => false,
                    ];

                $dataLineset = json_encode($datasets);
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

                $criterias_values = Criteria::getGraphCriterias($params);
                $graph_criterias = array_merge(['widget' => $widgetId], $criterias_values);

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

                $widget->setWidgetHtmlContent($graph);

                return $widget;
                break;

            case $this->getType() . "44":
                $name = 'reportLineChartNbCreatedTicketByWeek';

                $criterias = Criteria::getDefaultCriterias();

                $params = [
                    "preferences" => $preferences,
                    "criterias" => $criterias,
                    "opt" => $opt,
                ];

                $default = Criteria::manageCriterias($params);

                $result = self::getTicketsCreatedPerWeek($params);

                $tabdata = [];
                $tabnames = [];
                $maxcount = 0;
                foreach ($result as $weeknum => $nbticket) {
                    $tabdata[] = $nbticket;
                    $tabnames[] = $weeknum;
                    if ($nbticket > $maxcount) {
                        $maxcount = $nbticket;
                    }
                }

                $widget = new Html();
                $title = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetTitle((($isDebug) ? "44 " : "") . $title);
                $widget->setWidgetComment($comment);
                $widget->toggleWidgetRefresh();

                $nbtickets = __('Tickets number', 'mydashboard');

                $datasets[]
                    = [
                        'type' => 'line',
                        'data' => $tabdata,
                        'name' => $nbtickets,
                        'smooth' => false,
                    ];

                $dataLineset = json_encode($datasets);
                $labelsLine = json_encode($tabnames);
                $tabdatesset = json_encode([]);

                $graph_datas = [
                    'title' => $title,
                    'comment' => $comment,
                    'name' => $name,
                    'ids' => $tabdatesset,
                    'data' => $dataLineset,
                    'labels' => $labelsLine,
                ];
                $onclick = 0;
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

                $widget->setWidgetHtmlContent($graph);

                return $widget;
                break;
            case $this->getType() . "45":
                $name = 'reportLineChartRefusedTicketsByMonths';

                $criterias = Criteria::getDefaultCriterias();

                $params = [
                    "preferences" => $preferences,
                    "criterias" => $criterias,
                    "opt" => $opt,
                ];
                $default = Criteria::manageCriterias($params);

                $is_deleted = ['glpi_tickets.is_deleted' => 0];

                $currentmonth = date("m");
                $currentyear = $opt["year"] ?? $default["year"];
                $now = date("Y-m-d");

                $previousyear = $currentyear - 1;
                $tabdates = [];
                //                $queryOpenedTicket = "SELECT DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m') as period,
                //                                         DATE_FORMAT(`glpi_tickets`.`date`, '%b %Y') as monthname,
                //                                         count(*) as count
                //                                  FROM `glpi_tickets`
                //                                  INNER JOIN glpi_ticketvalidations ON `glpi_tickets`.`id` = `glpi_ticketvalidations`.`tickets_id`
                //                                  WHERE  (`glpi_tickets`.`date` >= '$previousyear-$currentmonth-01 00:00:00')
                //                                  AND `glpi_ticketvalidations`.`status` = 4
                //                                  AND (`glpi_tickets`.`date` <= '$now 23:59:59')
                //                                  " . $entities_criteria . $is_deleted . $type_criteria . "
                //                                  GROUP BY DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m')";

                $criteria_1 = [
                    'SELECT' => [
                        new QueryExpression(
                            "DATE_FORMAT(" . $DB->quoteName("glpi_tickets.date") . ", '%Y-%m') AS period"
                        ),
                        new QueryExpression(
                            "DATE_FORMAT(" . $DB->quoteName("glpi_tickets.date") . ", '%b %Y') AS monthname"
                        ),
                        'COUNT' => 'glpi_tickets.id AS count',
                    ],
                    'FROM' => 'glpi_tickets',
                    'INNER JOIN'       => [
                        'glpi_ticketvalidations' => [
                            'ON' => [
                                'glpi_ticketvalidations' => 'tickets_id',
                                'glpi_tickets'          => 'id',
                            ],
                        ],
                    ],
                    'WHERE' => [
                        $is_deleted,
                        'glpi_ticketvalidations.status' => \CommonITILValidation::REFUSED,
                        [
                            [
                                'glpi_tickets.date' => [
                                    '>=',
                                    "$previousyear-$currentmonth-01 00:00:00",
                                ],
                            ],
                            ['glpi_tickets.date' => ['<=', new QueryExpression("ADDDATE('$now 23:59:59' , INTERVAL 1 DAY)")]],
                        ],
                    ],
                    'GROUPBY' => 'period',
                ];

                $criteria_1 = Criteria::addCriteriasForQuery($criteria_1, $params);

                $iterator_1 = $DB->request($criteria_1);

                $tabdata = [];
                $tabnames = [];

                foreach ($iterator_1 as $data) {
                    $tabdata[] = $data['count'];
                    $tabnames[] = $data['monthname'];
                    $tabdates[] = $data['period'];
                }


                $widget = new Html();
                $title = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetTitle((($isDebug) ? "45 " : "") . $title);
                $widget->setWidgetComment($comment);
                $widget->toggleWidgetRefresh();

                $nbtickets = __('Tickets number', 'mydashboard');

                $datasets[]
                    = [
                        'type' => 'line',
                        'data' => $tabdata,
                        'name' => $nbtickets,
                        'smooth' => false,
                    ];

                $dataLineset = json_encode($datasets);
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

                $criterias_values = Criteria::getGraphCriterias($params);
                $graph_criterias = array_merge(['widget' => $widgetId], $criterias_values);

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

                $widget->setWidgetHtmlContent($graph);

                return $widget;
                break;
            case $this->getType() . "46":
                $name = 'reportLineTicketsProblemsByMonths';

                $criterias = Criteria::getDefaultCriterias();

                $params = [
                    "preferences" => $preferences,
                    "criterias" => $criterias,
                    "opt" => $opt,
                ];

                $default = Criteria::manageCriterias($params);

                $is_deleted = ['glpi_tickets.is_deleted' => 0];

                $currentmonth = date("m");
                $currentyear = $default["year"];
                $now = date("Y-m-d");
                if (isset($opt["year"]) && $opt["year"] > 0) {
                    $currentyear = $opt["year"];
                }
                $previousyear = $currentyear - 1;
                $tabdates = [];
                //                $queryOpenedTicket = "SELECT DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m') as period,
                //                                         DATE_FORMAT(`glpi_tickets`.`date`, '%b %Y') as monthname,
                //                                         count(*) as count
                //                                  FROM `glpi_tickets`
                //                                  INNER JOIN glpi_problems_tickets ON `glpi_tickets`.`id` = `glpi_problems_tickets`.`tickets_id`
                //                                  WHERE  (`glpi_tickets`.`date` >= '$previousyear-$currentmonth-01 00:00:00')
                //                                  AND (`glpi_tickets`.`date` <= '$now 23:59:59')
                //                                  " . $entities_criteria . $is_deleted . $type_criteria . "
                //                                  GROUP BY DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m')";

                $criteria_1 = [
                    'SELECT' => [
                        new QueryExpression(
                            "DATE_FORMAT(" . $DB->quoteName("glpi_tickets.date") . ", '%Y-%m') AS period"
                        ),
                        new QueryExpression(
                            "DATE_FORMAT(" . $DB->quoteName("glpi_tickets.date") . ", '%b %Y') AS monthname"
                        ),
                        'COUNT' => 'glpi_tickets.id AS count',
                    ],
                    'FROM' => 'glpi_tickets',
                    'INNER JOIN'       => [
                        'glpi_problems_tickets' => [
                            'ON' => [
                                'glpi_problems_tickets' => 'tickets_id',
                                'glpi_tickets'          => 'id',
                            ],
                        ],
                    ],
                    'WHERE' => [
                        $is_deleted,
                        [
                            [
                                'glpi_tickets.date' => [
                                    '>=',
                                    "$previousyear-$currentmonth-01 00:00:00",
                                ],
                            ],
                            ['glpi_tickets.date' => ['<=', new QueryExpression("ADDDATE('$now 23:59:59' , INTERVAL 1 DAY)")]],
                        ],
                    ],
                    'GROUPBY' => 'period',
                ];

                $criteria_1 = Criteria::addCriteriasForQuery($criteria_1, $params);

                $iterator_1 = $DB->request($criteria_1);

                $tabdata = [];
                $tabnames = [];

                foreach ($iterator_1 as $data) {
                    $tabdata[] = $data['count'];
                    $tabnames[] = $data['monthname'];
                    $tabdates[] = $data['period'];
                }


                $widget = new Html();
                $title = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetTitle((($isDebug) ? "46 " : "") . $title);
                $widget->setWidgetComment($comment);
                $widget->toggleWidgetRefresh();

                $nbtickets = __('Tickets number', 'mydashboard');

                $datasets[]
                    = [
                        'type' => 'line',
                        'data' => $tabdata,
                        'name' => $nbtickets,
                        'smooth' => false,
                    ];

                $dataLineset = json_encode($datasets);
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

                $criterias_values = Criteria::getGraphCriterias($params);
                $graph_criterias = array_merge(['widget' => $widgetId], $criterias_values);

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

                $widget->setWidgetHtmlContent($graph);

                return $widget;
                break;
            case $this->getType() . "47":
                $name = 'reportLineChartBacklogTicketByWeek';

                $criterias = Criteria::getDefaultCriterias();

                $params = [
                    "preferences" => $preferences,
                    "criterias" => $criterias,
                    "opt" => $opt,
                ];

                $default = Criteria::manageCriterias($params);

                $technician_groups_ids = $opt['technicians_groups_id'] ?? $default['technicians_groups_id'];
                $type = $opt['type'] ?? $default['type'];
                $currentyear = $opt["year"] ?? $default["year"];

                $year = intval(date('Y', time()) - 1);

                if (isset($opt["year"]) && $opt["year"] > 0) {
                    $year = $currentyear;
                }
                if ($year < intval($currentyear)) {
                    $week = date("W", strtotime("$currentyear-12-31"));
                } else {
                    $week = intval(date('W'));
                }

                $tabdata = [];
                $tabnames = [];
                $maxcount = 0;

                for ($i = 1; $i <= intval($week); $i++) {
                    if (!isset($datas[$i])) {
                        $nbticket = 0;
                        if ($type == \Ticket::DEMAND_TYPE) {
                            $nbticket += Alert::commonQueryWeek($currentyear, $i, StockTicketIndicator::REQUESTPROGRESST, $technician_groups_ids);
                        } elseif ($type == \Ticket::INCIDENT_TYPE) {
                            $nbticket += Alert::commonQueryWeek($currentyear, $i, StockTicketIndicator::INCIDENTPROGRESST, $technician_groups_ids);
                        } else {
                            $nbticket += Alert::commonQueryWeek($currentyear, $i, StockTicketIndicator::REQUESTPROGRESST, $technician_groups_ids);
                            $nbticket += Alert::commonQueryWeek($currentyear, $i, StockTicketIndicator::INCIDENTPROGRESST, $technician_groups_ids);
                        }
                        $tabdata[] = $nbticket;
                        $tabnames[] = $i;
                        if ($nbticket > $maxcount) {
                            $maxcount = $nbticket;
                        }
                    }
                }

                $widget = new Html();
                $title = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetTitle((($isDebug) ? "47 " : "") . $title);
                $widget->setWidgetComment($comment);
                $widget->toggleWidgetRefresh();

                $nbtickets = __('Tickets number', 'mydashboard');

                $datasets[]
                    = [
                        'type' => 'line',
                        'data' => $tabdata,
                        'name' => $nbtickets,
                        'smooth' => false,
                    ];

                $dataLineset = json_encode($datasets);
                $labelsLine = json_encode($tabnames);
                $tabdatesset = json_encode([]);

                $graph_datas = [
                    'title' => $title,
                    'comment' => $comment,
                    'name' => $name,
                    'ids' => $tabdatesset,
                    'data' => $dataLineset,
                    'labels' => $labelsLine,
                ];
                $onclick = 0;
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

                $widget->setWidgetHtmlContent($graph);

                return $widget;

                break;

            case $this->getType() . "48":
                $name = 'reportLineWeekBacklog';
                $onclick = 0;

                $criterias = Criteria::getDefaultCriterias();

                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() == 'central') {
                    $onclick = 1;
                    $specific_criterias = [
                        Location::$criteria_name,
                        RequesterGroup::$criteria_name,
                    ];
                    $criterias = array_merge($criterias, $specific_criterias);
                }
                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() != 'central') {
                    $specific_criterias = [
                        Location::$criteria_name,
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

                //
                //                $query = "SELECT DISTINCT
                //                           DATE_FORMAT(`date`, '%b %Y') AS period_name,
                //                           COUNT(`glpi_tickets`.`id`) AS nb,
                //                           DATE_FORMAT(`date`, '%Y-%m') AS period
                //                        FROM `glpi_tickets` ";
                //                $query .= " WHERE $is_deleted $type_criteria $locations_criteria $technician_groups_criteria
                //                 $requester_groups_criteria";
                //                $query .= " $entities_criteria
                //                AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")
                //                        GROUP BY period_name ORDER BY period ASC";
                //

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

                $nb = count($iterator);
                $tabdata = [];
                $tabnames = [];
                $tabdates = [];
                if ($nb) {
                    foreach ($iterator as $data) {
                        $tabdata[] = $data['nb'];
                        $tabnames[] = $data['period_name'];
                        $tabdates[] = $data['period'];
                    }
                }

                $widget = new Html();
                $title = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetTitle((($isDebug) ? "48 " : "") . $title);
                $widget->setWidgetComment($comment);
                $widget->toggleWidgetRefresh();

                $nbtickets = __('Tickets number', 'mydashboard');

                $datasets[]
                    = [
                        'type' => 'line',
                        'data' => $tabdata,
                        'name' => $nbtickets,
                        'smooth' => false,
                    ];

                $databacklogset = json_encode($datasets);
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

            case $this->getType() . "49":
                $name = 'reportLineChartRefusedSolutionTicketsByMonths';

                $criterias = Criteria::getDefaultCriterias();

                $params = [
                    "preferences" => $preferences,
                    "criterias" => $criterias,
                    "opt" => $opt,
                ];
                $default = Criteria::manageCriterias($params);

                $is_deleted = ['glpi_tickets.is_deleted' => 0];

                $currentmonth = date("m");
                $currentyear = $opt["year"] ?? $default["year"];
                $now = date("Y-m-d");

                $previousyear = $currentyear - 1;
                $tabdates = [];
                //                $queryOpenedTicket = "SELECT DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m') as period,
                //                                         DATE_FORMAT(`glpi_tickets`.`date`, '%b %Y') as monthname,
                //                                         count(*) as count
                //                                  FROM `glpi_tickets`
                //                                  INNER JOIN glpi_itilsolutions ON (`glpi_tickets`.`id` = `glpi_itilsolutions`.`items_id`
                //                                                                        AND `glpi_itilsolutions`.`itemtype` = 'Ticket')
                //
                //                                  WHERE  (`glpi_tickets`.`date` >= '$previousyear-$currentmonth-01 00:00:00')
                //                                  AND `glpi_itilsolutions`.`items_id` IN (SELECT items_id FROM `glpi_itilsolutions`
                //                                                                                          WHERE `glpi_itilsolutions`.`itemtype` = 'Ticket'
                //                                                                                          GROUP BY items_id HAVING (COUNT(items_id) > 1))
                //                                  AND (`glpi_tickets`.`date` <= '$now 23:59:59')
                //                                  " . $entities_criteria . $is_deleted . $type_criteria . "
                //                                  GROUP BY DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m')";

                $criteria_init = [
                    'SELECT' => [
                        'items_id',
                    ],
                    'FROM' => 'glpi_itilsolutions',
                    'WHERE' => [
                        'itemtype' => 'Ticket',
                    ],
                    'GROUPBY' => 'items_id',
                    'HAVING' => [new QueryExpression("COUNT(items_id) > 1")],
                ];

                $criteria = [
                    'SELECT' => [
                        new QueryExpression("DATE_FORMAT(" . $DB->quoteName("date") . ", '%Y-%m') AS period"),
                        new QueryExpression("DATE_FORMAT(" . $DB->quoteName("date") . ", '%b %Y') AS monthname"),
                        'COUNT' => 'glpi_tickets.id AS count',
                    ],
                    'FROM' => 'glpi_tickets',
                    'INNER JOIN'       => [
                        'glpi_itilsolutions' => [
                            'ON' => [
                                'glpi_itilsolutions'   => 'items_id',
                                'glpi_tickets'                  => 'id', [
                                    'AND' => [
                                        'glpi_itilsolutions.itemtype' => 'Ticket',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'WHERE' => [
                        $is_deleted,
                        'glpi_itilsolutions.items_id' => new QuerySubQuery($criteria_init),
                        [
                            ['glpi_tickets.date' => ['>=', "$previousyear-$currentmonth-01 00:00:00"]],
                            ['glpi_tickets.date' => ['<=', "$now 00:00:00"]],
                        ],
                    ],
                    'GROUPBY' => 'period',
                ];

                $criteria = Criteria::addCriteriasForQuery($criteria, $params);

                $tabdata = [];
                $tabnames = [];

                $iterator = $DB->request($criteria);

                foreach ($iterator as $data) {


                    $tabdata[] = $data['count'];
                    $tabnames[] = $data['monthname'];
                    $tabdates[] = $data['period'];
                }


                $widget = new Html();
                $title = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetTitle((($isDebug) ? "45 " : "") . $title);
                $widget->setWidgetComment($comment);
                $widget->toggleWidgetRefresh();

                $nbtickets = __('Tickets number', 'mydashboard');

                $datasets[]
                    = [
                        'type' => 'line',
                        'data' => $tabdata,
                        'name' => $nbtickets,
                        'smooth' => false,
                    ];

                $dataLineset = json_encode($datasets);
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
                $onclick = 0;
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

                $widget->setWidgetHtmlContent($graph);

                return $widget;
            default:
                break;
        }
        return false;
    }

    /**
     * @param $params
     * @param $specific
     *
     * @return array
     */
    private static function getTicketsCreatedPerWeek($params)
    {
        global $DB;

        $default = Criteria::manageCriterias($params);

        //        $year = intval(date('Y', time()) - 1);

        $year = $opt["year"] ?? $default["year"];
        $currentyear = date("Y");

        if ($year < intval($currentyear)) {
            $week = date("W", strtotime("$currentyear-12-31"));
        } else {
            $week = intval(date('W'));
        }

        $is_deleted = ['glpi_tickets.is_deleted' => 0];


        //        $querym_ai = "SELECT COUNT(`glpi_tickets`.`id`) AS nbtickets,
        //                                   week(`glpi_tickets`.`date` ) AS numweek
        //                        FROM `glpi_tickets` ";
        //        $querym_ai .= "WHERE ";
        //        $querym_ai .= "(
        //                           `glpi_tickets`.`date` >= '$year-01-01 00:00:00'
        //                           AND `glpi_tickets`.`date` <= '$year-12-31 23:59:59'
        //                           AND  $is_deleted
        //                           $type_criteria )
        //                           $entities_criteria";
        //        $querym_ai .= "GROUP BY week(`glpi_tickets`.`date`);";


        $criteria = [
            'SELECT' => [
                'COUNT' => 'glpi_tickets.id AS nbtickets',
                new QueryExpression("week(" . $DB->quoteName("glpi_tickets.date") . ") AS numweek"),
            ],
            'DISTINCT'        => true,
            'FROM' => 'glpi_tickets',
            'WHERE' => [
                $is_deleted,
                'NOT'       => [['glpi_tickets.solvedate' => 'NULL'],
                    ['glpi_tickets.time_to_resolve' => 'NULL']],
                'glpi_tickets.status' => [CommonITILObject::SOLVED, CommonITILObject::CLOSED],
                [
                    ['glpi_tickets.date' => ['>=', "$year-01-01 00:00:00"]],
                    ['glpi_tickets.date' => ['<=', "$year-12-31 23:59:59"]],
                ],
            ],
            'GROUPBY' => 'numweek',
        ];

        $criteria = Criteria::addCriteriasForQuery($criteria, $params);

        $iterator = $DB->request($criteria);

        $datas = [];
        foreach ($iterator as $data) {
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
    public static function pluginMydashboardReports_Line22link($options)
    {
        global $CFG_GLPI;

        if (isset($options['selected_id']) && strpos($options['selected_id'], '_') !== false) {
            $eventParts = explode('_', $options['selected_id']);
            $date = $eventParts[0];
            $ticket_state = $eventParts[1];
            if (isset($date) && strpos($date, '-') !== false) {
                $dateParts = explode('-', $date);
                $year = $dateParts[0];
                $month = $dateParts[1];
            }
        }

        if (isset($year) && isset($month) && isset($ticket_state)) {
            if ($ticket_state == "opened") {
                $crit = Criteria::OPEN_DATE;
            } elseif ($ticket_state == "closed") {
                $crit = Criteria::CLOSE_DATE;
            } elseif ($ticket_state == "progress") {
                $crit = Criteria::OPEN_DATE;
            }
            if ($ticket_state == "progress") {
                $nbdays = date("t", mktime(0, 0, 0, $month, 1, $year));
                $date = "$year-$month-$nbdays 23:59";
                $options_selected = Criteria::addUrlCriteria($crit, 'lessthan', $date, 'AND');
                $options_selected = Criteria::addUrlCriteria(Criteria::STATUS, 'equals', 'notold', 'AND');
            } else {
                $date = "$year-$month-01 00:00";
                $nbdays = date("t", mktime(0, 0, 0, $month, 1, $year));
                $options_selected = Criteria::addUrlCriteria($crit, 'morethan', $date, 'AND');
                $date = "$year-$month-$nbdays 23:59";
                $options_selected = Criteria::addUrlCriteria($crit, 'lessthan', $date, 'AND');
            }
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
    public static function pluginMydashboardReports_Line34link($options)
    {
        global $CFG_GLPI;

        if (isset($options['selected_id']) && strpos($options['selected_id'], '_') !== false) {
            $eventParts = explode('_', $options['selected_id']);
            $date = $eventParts[0];
            $ticket_state = $eventParts[1];
            if (isset($date) && strpos($date, '-') !== false) {
                $dateParts = explode('-', $date);
                $year = $dateParts[0];
                $month = $dateParts[1];
            }
        }
        if (isset($year) && isset($month) && isset($ticket_state)) {
            if ($ticket_state == "opened") {
                $crit = Criteria::OPEN_DATE;
            } elseif ($ticket_state == "resolved") {
                $crit = Criteria::SOLVE_DATE;
            } elseif ($ticket_state == "progress") {
                $crit = Criteria::OPEN_DATE;
            }
            if ($ticket_state == "progress") {
                $nbdays = date("t", mktime(0, 0, 0, $month, 1, $year));
                $date = "$year-$month-$nbdays 23:59";
                $options_selected = Criteria::addUrlCriteria($crit, 'lessthan', $date, 'AND');
                $options_selected = Criteria::addUrlCriteria(Criteria::STATUS, 'equals', 'notold', 'AND');
            } else {
                $date = "$year-$month-01 00:00";
                $nbdays = date("t", mktime(0, 0, 0, $month, 1, $year));
                $options_selected = Criteria::addUrlCriteria($crit, 'morethan', $date, 'AND');
                $date = "$year-$month-$nbdays 23:59";
                $options_selected = Criteria::addUrlCriteria($crit, 'lessthan', $date, 'AND');
            }
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
    public static function pluginMydashboardReports_Line35link($options)
    {
        global $CFG_GLPI;

        if (isset($options['selected_id']) && strpos($options['selected_id'], '_') !== false) {
            $eventParts = explode('_', $options['selected_id']);
            $date = $eventParts[0];
            $ticket_state = $eventParts[1];
            if (isset($date) && strpos($date, '-') !== false) {
                $dateParts = explode('-', $date);
                $year = $dateParts[0];
                $month = $dateParts[1];
            }
        }

        $add_actiontime_crit = 0;
        if (isset($year) && isset($month) && isset($ticket_state)) {
            if ($ticket_state == "opened") {
                $crit = Criteria::OPEN_DATE;
            } elseif ($ticket_state == "closed") {
                $crit = Criteria::CLOSE_DATE;
            } elseif ($ticket_state == "progress") {
                $crit = Criteria::OPEN_DATE;
            } elseif ($ticket_state == "unplanned") {
                $crit = Criteria::CLOSE_DATE;
                $add_actiontime_crit = 1;
            }
            if ($ticket_state == "progress") {
                $nbdays = date("t", mktime(0, 0, 0, $month, 1, $year));
                $date = "$year-$month-$nbdays 23:59";
                $options_selected = Criteria::addUrlCriteria($crit, 'lessthan', $date, 'AND');
                $options_selected = Criteria::addUrlCriteria(Criteria::STATUS, 'equals', 'notold', 'AND');
            } else {
                $date = "$year-$month-01 00:00";
                $nbdays = date("t", mktime(0, 0, 0, $month, 1, $year));
                $options_selected = Criteria::addUrlCriteria($crit, 'morethan', $date, 'AND');
                $date = "$year-$month-$nbdays 23:59";
                $options_selected = Criteria::addUrlCriteria($crit, 'lessthan', $date, 'AND');
            }
        }

        if ($add_actiontime_crit == 1) {
            $options_selected = Criteria::addUrlCriteria(Criteria::TASK_ACTIONTIME, 'contains', '0', 'AND');
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
    public static function pluginMydashboardReports_Line43link($options)
    {
        global $CFG_GLPI;

        if (isset($options['selected_id']) && strpos($options['selected_id'], '-') !== false) {
            $dateParts = explode('-', $options['selected_id']);
            $year = $dateParts[0];
            $month = $dateParts[1];
        }
        if (isset($month)) {
            $date = "$year-$month-01 00:00";
            $nbdays = date("t", mktime(0, 0, 0, $month, 1, $year));
            $options_selected = Criteria::addUrlCriteria(Criteria::OPEN_DATE, 'morethan', $date, 'AND');
            $date = "$year-$month-$nbdays 23:59";
            $options_selected = Criteria::addUrlCriteria(Criteria::OPEN_DATE, 'lessthan', $date, 'AND');
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
    public static function pluginMydashboardReports_Line44link($options)
    {
        global $CFG_GLPI;

        if (isset($options['selected_id']) && strpos($options['selected_id'], '_') !== false) {
            $eventParts = explode('_', $options['selected_id']);
            $date = $eventParts[0];
            $ticket_state = $eventParts[1];
            if (isset($date) && strpos($date, '-') !== false) {
                $dateParts = explode('-', $date);
                $year = $dateParts[0];
                $month = $dateParts[1];
            }
        }

        $week_number = $options["selected_id"];

        $firstMonday = date("d", strtotime("first monday of january $year"));
        $start = date(
            "Y-m-d 00:00:00",
            strtotime("$firstMonday Jan " . $year . " 00:00:00 GMT + " . $week_number . " weeks")
        );
        $end = date("Y-m-d 23:59:59", strtotime($start . " + 1 week"));
        $end = date("Y-m-d 23:59:59", strtotime($end . " - 1 day"));

        $options_selected = Criteria::addUrlCriteria(Criteria::OPEN_DATE, 'morethan', $start, 'AND');
        $options_selected = Criteria::addUrlCriteria(Criteria::OPEN_DATE, 'lessthan', $end, 'AND');

        $options['criteria'] = array_merge($options['params']['criteria'], $options_selected['criteria']);

        return $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&'
            . Toolbox::append_params($options, "&");

    }


    /**
     * @param $selected_id
     *
     * @return string
     */
    public static function pluginMydashboardReports_Line45link($options)
    {
        global $CFG_GLPI;

        if (isset($options['selected_id']) && strpos($options['selected_id'], '-') !== false) {
            $dateParts = explode('-', $options['selected_id']);
            $year = $dateParts[0];
            $month = $dateParts[1];
        }
        if (isset($month)) {
            $date = "$year-$month-01 00:00";
            $nbdays = date("t", mktime(0, 0, 0, $month, 1, $year));
            $options_selected = Criteria::addUrlCriteria(Criteria::OPEN_DATE, 'morethan', $date, 'AND');
            $date = "$year-$month-$nbdays 23:59";
            $options_selected = Criteria::addUrlCriteria(Criteria::OPEN_DATE, 'lessthan', $date, 'AND');
        }
        $options_selected = Criteria::addUrlCriteria(Criteria::VALIDATION_STATS, 'equals', Criteria::VALIDATION_REFUSED, 'AND');

        $options['criteria'] = array_merge($options['params']['criteria'], $options_selected['criteria']);

        return $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&'
            . Toolbox::append_params($options, "&");
    }


    /**
     * @param $selected_id
     *
     * @return string
     */
    public static function pluginMydashboardReports_Line46link($options)
    {
        global $CFG_GLPI;

        if (isset($options['selected_id']) && strpos($options['selected_id'], '-') !== false) {
            $dateParts = explode('-', $options['selected_id']);
            $year = $dateParts[0];
            $month = $dateParts[1];
        }
        if (isset($month)) {
            $date = "$year-$month-01 00:00";
            $nbdays = date("t", mktime(0, 0, 0, $month, 1, $year));
            $options_selected = Criteria::addUrlCriteria(Criteria::OPEN_DATE, 'morethan', $date, 'AND');
            $date = "$year-$month-$nbdays 23:59";
            $options_selected = Criteria::addUrlCriteria(Criteria::OPEN_DATE, 'lessthan', $date, 'AND');
        }
        $options_selected = Criteria::addUrlCriteria(Criteria::NUMBER_OF_PROBLEMS, 'equals', '>0', 'AND');

        $options['criteria'] = array_merge($options['params']['criteria'], $options_selected['criteria']);

        return $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&'
            . Toolbox::append_params($options, "&");
    }


    /**
     * @param $selected_id
     *
     * @return string
     */
    public static function pluginMydashboardReports_Line48link($options)
    {
        global $CFG_GLPI;

        if (isset($options['selected_id']) && strpos($options['selected_id'], '-') !== false) {
            $dateParts = explode('-', $options['selected_id']);
            $year = $dateParts[0];
            $month = $dateParts[1];
        }
        if (isset($month)) {
            $date = "$year-$month-01 00:00";
            $nbdays = date("t", mktime(0, 0, 0, $month, 1, $year));
            $options_selected = Criteria::addUrlCriteria(Criteria::OPEN_DATE, 'morethan', $date, 'AND');
            $date = "$year-$month-$nbdays 23:59";
            $options_selected = Criteria::addUrlCriteria(Criteria::OPEN_DATE, 'lessthan', $date, 'AND');
        }
        $options_selected = Criteria::addUrlCriteria(Criteria::STATUS, 'equals', 'notold', 'AND');

        $options['criteria'] = array_merge($options['params']['criteria'], $options_selected['criteria']);

        return $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&'
            . Toolbox::append_params($options, "&");
    }
}
