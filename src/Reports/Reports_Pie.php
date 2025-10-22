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

use Appliance;
use CommonGLPI;
use CommonITILActor;
use CommonITILObject;
use DbUtils;
use Dropdown;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;
use GlpiPlugin\Mydashboard\Chart;
use GlpiPlugin\Mydashboard\Charts\PieChart;
use GlpiPlugin\Mydashboard\Helper;
use GlpiPlugin\Mydashboard\Html as MydashboardHtml;
use GlpiPlugin\Mydashboard\Menu;
use GlpiPlugin\Mydashboard\Preference as MydashboardPreference;
use GlpiPlugin\Mydashboard\Widget;
use Session;
use Toolbox;

/**
 * Class Reports_Pie
 */
class Reports_Pie extends CommonGLPI
{
    private $options;
    private $pref;
    public static $reports = [2, 7, 12, 13, 16, 17, 18, 20, 25, 26, 27, 30, 31, 32];


    /**
     * Reports_Pie constructor.
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
                $this->getType() . "2"  => ["title"   => __("Number of opened tickets by priority", "mydashboard"),
                    "type"    => Widget::$PIE,
                    "comment" => ""],
                $this->getType() . "7"  => ["title"   => __("Top ten ticket requesters by month", "mydashboard"),
                    "type"    => Widget::$PIE,
                    "comment" => ""],
                $this->getType() . "12" => ["title"   => __("TTR Compliance", "mydashboard"),
                    "type"    => Widget::$PIE,
                    "comment" => __("Display tickets where time to resolve is respected (percent)", "mydashboard")],
                $this->getType() . "13" => ["title"   => __("TTO Compliance", "mydashboard"),
                    "type"    => Widget::$PIE,
                    "comment" => __("Display tickets where time to own is respected (percent)", "mydashboard")],
                $this->getType() . "16" => ["title"   => __("Number of opened incidents by category", "mydashboard"),
                    "type"    => Widget::$PIE,
                    "comment" => ""],
                $this->getType() . "17" => ["title"   => __("Number of opened requests by category", "mydashboard"),
                    "type"    => Widget::$PIE,
                    "comment" => ""],
                $this->getType() . "18" => ["title"   => __("Number of opened, closed and unplanned tickets by month", "mydashboard"),
                    "type"    => Widget::$PIE,
                    "comment" => ""],
                $this->getType() . "20" => ["title"   => __("Percent of use of solution types", "mydashboard"),
                    "type"    => Widget::$PIE,
                    "comment" => __("Display percent of solution types for tickets", "mydashboard")],
                $this->getType() . "25" => ["title"   => __("Top ten of opened tickets by requester groups", "mydashboard"),
                    "type"    => Widget::$PIE,
                    "comment" => ""],
                $this->getType() . "26" => ["title"   => __("Global satisfaction level", "mydashboard"),
                    "type"    => Widget::$PIE,
                    "comment" => __("Satisfaction average", "mydashboard")],
                $this->getType() . "27" => ["title"   => __("Top ten of opened tickets by location", "mydashboard"),
                    "type"    => Widget::$PIE,
                    "comment" => ""],
                $this->getType() . "30" => ["title"   => __("Number of use of request sources", "mydashboard"),
                    "type"    => Widget::$PIE,
                    "comment" => __("Display percent of request sources for closed tickets", "mydashboard")],
                $this->getType() . "31" => ["title"   => __("Number of tickets per location per period", "mydashboard"),
                    "type"    => Widget::$PIE,
                    "comment" => ""],
                $this->getType() . "32" => ["title"   => __("Top ten of opened tickets by appliance", "mydashboard"),
                    "type"    => Widget::$PIE,
                    "comment" => ""],

            ],
        ];
        return $widgets;
    }


    /**
     * @param       $widgetId
     * @param array $opt
     *
     * @return MydashboardHtml
     * @throws \GlpitestSQLError
     */
    public function getWidgetContentForItem($widgetId, $opt = [])
    {
        global $DB;

        $isDebug = $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE;
        $dbu     = new DbUtils();
        $preference = new MydashboardPreference();
        if (Session::getLoginUserID() !== false
            && !$preference->getFromDB(Session::getLoginUserID())) {
            $preference->initPreferences(Session::getLoginUserID());
        }
        $preference->getFromDB(Session::getLoginUserID());
        $preferences = $preference->fields;

        switch ($widgetId) {
            case $this->getType() . "2":
                $onclick = 0;
                $name    = 'TicketsByPriorityPieChart';
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

                $params  = ["preferences" => $preferences,
                    "criterias"   => $criterias,
                    "opt"         => $opt];
                $options = Helper::manageCriterias($params);


                $opt                        = $options['opt'];
                $crit                       = $options['crit'];

                $type                       = $opt['type'];
                $entities_id_criteria       = $crit['entity'];
                $sons_criteria              = $crit['sons'];
                $technician_group           = $opt['technicians_groups_id'];

                $name_priority = [];
                $datas         = [];
                $tabpriority   = [];

                $is_deleted = ['glpi_tickets.is_deleted' => 0];

                $criteria = [
                    'SELECT' => [
                        'glpi_tickets.priority',
                        'COUNT' => 'glpi_tickets.id AS nb',
                    ],
                    'DISTINCT'        => true,
                    'FROM' => 'glpi_tickets',
                    'WHERE' => [
                        $is_deleted,
                        'NOT'       => ['glpi_tickets.status' => [CommonITILObject::SOLVED, CommonITILObject::CLOSED]],
                    ],
                    'GROUPBY'   => 'glpi_tickets.priority',
                    'ORDERBY'   => 'glpi_tickets.priority ASC',
                ];

                if (is_array($technician_group) && count($technician_group) > 0) {
                    $criteria['LEFT JOIN'] =  ['glpi_groups_tickets' => [
                        'ON' => [
                            'glpi_tickets'   => 'id',
                            'glpi_groups_tickets'                  => 'tickets_id', [
                                'AND' => [
                                    'glpi_groups_tickets.type' => CommonITILActor::ASSIGN,
                                ],
                            ],
                        ],
                    ],
                ];

                    $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_groups_tickets.groups_id' => $technician_group];
                }

                if ($type > 0) {
                    $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_tickets.type' => $type];
                }

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                    'glpi_tickets'
                );

                $iterator = $DB->request($criteria);
                $nb = 0;
                if (count($iterator) > 0) {
                    $nb = count($iterator);
                    foreach ($iterator as $data) {
                        $name_priority[] = CommonITILObject::getPriorityName($data['priority']);
                        $datas[]         = ['value' => $data['nb'],
                            'name'  => CommonITILObject::getPriorityName($data['priority'])];

                        $tabpriority[] = $data['priority'];
                    }
                }

                $widget = new MydashboardHtml();
                $title   = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetComment($comment);
                $widget->setWidgetTitle((($isDebug) ? "2 " : "") . $title);
                $widget->toggleWidgetRefresh();

                $dataPieset     = json_encode($datas);
                $labelsPie      = json_encode($name_priority);
                $tabpriorityset = json_encode($tabpriority);
                $js_ancestors   = $crit['ancestors'];

                $graph_datas = ['title'   => $title,
                    'comment' => $comment,
                    'name'    => $name,
                    'ids'     => $tabpriorityset,
                    'data'    => $dataPieset,
                    'labels'  => $labelsPie,
                    'label'   => $title];

                if ($onclick == 1) {
                    $graph_criterias = ['entities_id'        => $entities_id_criteria,
                        'sons'               => $sons_criteria,
                        'technician_group'   => $technician_group,
                        'group_is_recursive' => $js_ancestors,
                        'type'               => $type,
                        'widget'             => $widgetId];
                }

                $graph = PieChart::launchPieGraph($graph_datas, $graph_criterias);

                $params = ["widgetId"  => $widgetId,
                    "name"      => $name,
                    "onsubmit"  => true,
                    "opt"       => $opt,
                    "criterias" => $criterias,
                    "export"    => true,
                    "canvas"    => true,
                    "nb"        => 1];
                $widget->setWidgetHeader(Helper::getGraphHeader($params));

                $widget->setWidgetHtmlContent($graph);

                return $widget;

            case $this->getType() . "7":
                $name = 'TopTenTicketAuthorsPieChart';
                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() == 'central') {
                    $criterias = ['entities_id',
                        'is_recursive',
                        'type',
                        'year',
                        'month',
                        'limit'];
                }
                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() != 'central') {
                    $criterias = ['type',
                        'year',
                        'month',
                        'limit'];
                }
                $opt['limit'] ??= 10;
                $params       = ["preferences" => $preferences,
                    "criterias"   => $criterias,
                    "opt"         => $opt];
                $options      = Helper::manageCriterias($params);

                $opt  = $options['opt'];
                $crit = $options['crit'];
                $type              = $opt['type'];
                $date_criteria     = $crit['date'];

                $limit             = $opt['limit'] ?? 10;

                $dataspie = [];
                $namespie = [];

                $is_deleted = ['glpi_tickets.is_deleted' => 0];

                $criteria = [
                    'SELECT' => [
                        new QueryExpression("IFNULL(" . $DB->quoteName('glpi_tickets_users.users_id') . ",-1) AS users_id"),
                        'COUNT' => 'glpi_tickets.id AS count',
                    ],
                    'FROM' => 'glpi_tickets',
                    'LEFT JOIN' => ['glpi_tickets_users' => [
                        'ON' => [
                            'glpi_tickets'   => 'id',
                            'glpi_tickets_users'                  => 'tickets_id', [
                                'AND' => [
                                    'glpi_tickets_users.type' => CommonITILActor::REQUESTER,
                                ],
                            ],
                        ],
                    ],
                    ],
                    'WHERE' => [
                        $is_deleted,
                        'NOT'       => ['glpi_tickets.status' => [CommonITILObject::SOLVED, CommonITILObject::CLOSED]],
                        new QueryExpression($date_criteria),
                    ],
                    'GROUPBY'   => 'glpi_tickets_users.users_id',
                    'ORDERBY'   => 'count DESC',
                    'LIMIT' => $limit,
                ];

                if ($type > 0) {
                    $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_tickets.type' => $type];
                }

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                    'glpi_tickets'
                );

                $iterator = $DB->request($criteria);
                $nb = 0;
                if (count($iterator) > 0) {
                    $nb = count($iterator);
                    foreach ($iterator as $data) {
                        if ($data['users_id'] == 0) {
                            $name_user = __('Email');
                        } elseif ($data['users_id'] == -1) {
                            $name_user = __('None');
                        } elseif ($data['users_id'] > 0) {
                            $name_user = getUserName($data['users_id']);
                        }
                        //                  $dataspie[] = $v;
                        $namespie[] = $name_user;
                        $dataspie[] = ['value' => $data['count'],
                            'name'  => $name_user];
                    }
                }

                $widget = new MydashboardHtml();
                $title   = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetComment($comment);
                $widget->setWidgetTitle((($isDebug) ? "7 " : "") . $title);
                $widget->toggleWidgetRefresh();

                $dataPieset = json_encode($dataspie);
                $labelsPie  = json_encode($namespie);

                $graph_datas = ['name'   => $name,
                    'ids'    => json_encode([]),
                    'data'   => $dataPieset,
                    'labels' => $labelsPie,
                    'label'  => $title];
                $graph = PieChart::launchPolarAreaGraph($graph_datas, []);
                $params = ['title'     => $title,
                    'comment'   => $comment,
                    "widgetId"  => $widgetId,
                    "name"      => $name,
                    "onsubmit"  => false,
                    "opt"       => $opt,
                    "criterias" => $criterias,
                    "export"    => true,
                    "canvas"    => true,
                    "nb"        => $nb];
                $widget->setWidgetHeader(Helper::getGraphHeader($params));

                $widget->setWidgetHtmlContent(
                    $graph
                );

                return $widget;

            case $this->getType() . "12":
                $name      = 'TTRCompliance';
                $criterias = ['entities_id',
                    'is_recursive',
                    'type'];
                $params    = ["preferences" => $preferences,
                    "criterias"   => $criterias,
                    "opt"         => $opt];
                $options   = Helper::manageCriterias($params);

                $opt  = $options['opt'];
                $crit = $options['crit'];

                $type = $opt['type'];

                $is_deleted = ['glpi_tickets.is_deleted' => 0];

                $criteria = [
                    'SELECT' => [
                        'COUNT' => 'glpi_tickets.id AS nb',
                    ],
                    'DISTINCT'        => true,
                    'FROM' => 'glpi_tickets',
                    'WHERE' => [
                        $is_deleted,
                        'NOT'       => ['glpi_tickets.status' => [CommonITILObject::SOLVED, CommonITILObject::CLOSED],
                            'solvedate' => null,
                            'time_to_resolve' => null],
                    ],
                ];

                if ($type > 0) {
                    $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_tickets.type' => $type];
                }

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                    'glpi_tickets'
                );

                $iterator = $DB->request($criteria);
                $nb = 0;
                $total = 0;
                if (count($iterator) > 0) {
                    $nb = count($iterator);
                    foreach ($iterator as $data) {
                        $total += $data['nb'];
                    }
                }

                $notrespected = 0;
                $respected    = 0;
                $datas = [];

                $criteria = [
                    'SELECT' => [
                        'COUNT' => 'glpi_tickets.id AS nb',
                    ],
                    'FROM' => 'glpi_tickets',
                    'WHERE' => [
                        $is_deleted,
                        'NOT' => ['glpi_tickets.status' => [CommonITILObject::SOLVED, CommonITILObject::CLOSED],
                            'solvedate' => null,
                            'time_to_resolve' => null],
                        ['solvedate' => ['>', new QueryExpression($DB::quoteName('time_to_resolve'))],
                            'OR' => [
                                'solvedate' => null,
                                'time_to_resolve' => ['<', QueryFunction::now()],
                            ],
                        ],
                    ],
                ];

                if ($type > 0) {
                    $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_tickets.type' => $type];
                }

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                    'glpi_tickets'
                );

                $iterator = $DB->request($criteria);
                $nb = 0;
                $sum = 0;
                if (count($iterator) > 0) {
                    $nb = count($iterator);
                    foreach ($iterator as $data) {
                        $sum += $data['nb'];
                    }
                }
                if ($nb > 0 && $sum > 0) {
                    $notrespectedvalue =  $sum * 100 / $total;
                    $respectedvalue =  ($total - $sum) * 100 / $total;
                    $notrespected = round($notrespectedvalue, 2, PHP_ROUND_HALF_UP);
                    $respected    = round($respectedvalue, 2, PHP_ROUND_HALF_UP);

                    $datas[] = ['value' => $notrespected,
                        'name'  => __("Not respected TTR", "mydashboard")];

                    $datas[] = ['value' => $respected,
                        'name'  => __("Respected TTR", "mydashboard")];
                }
                $widget = new MydashboardHtml();
                $title   = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetComment($comment);
                $widget->setWidgetTitle((($isDebug) ? "12 " : "") . $title);
                $widget->toggleWidgetRefresh();

                $dataPieset  = json_encode($datas);
                $labelsPie   = json_encode([__("Respected TTR", "mydashboard"),
                    __("Not respected TTR", "mydashboard")]);
                $graph_datas = ['title'   => $title,
                    'comment' => $comment,
                    'name'    => $name,
                    'ids'     => json_encode([]),
                    'data'    => $dataPieset,
                    'labels'  => $labelsPie,
                    'label'   => $title,
                ];

                $graph = PieChart::launchPieGraph($graph_datas, []);

                $params = ["widgetId"  => $widgetId,
                    "name"      => $name,
                    "onsubmit"  => false,
                    "opt"       => $opt,
                    "criterias" => $criterias,
                    "export"    => true,
                    "canvas"    => true,
                    "nb"        => $nb];
                $widget->setWidgetHeader(Helper::getGraphHeader($params));

                $widget->setWidgetHtmlContent(
                    $graph
                );

                return $widget;

            case $this->getType() . "13":
                $name      = 'TTOCompliance';
                $criterias = ['entities_id',
                    'is_recursive',
                    'type'];
                $params    = ["preferences" => $preferences,
                    "criterias"   => $criterias,
                    "opt"         => $opt];
                $options   = Helper::manageCriterias($params);

                $opt  = $options['opt'];
                $crit = $options['crit'];

                $type = $opt['type'];

                $is_deleted = ['glpi_tickets.is_deleted' => 0];

                $criteria = [
                    'SELECT' => [
                        'COUNT' => 'glpi_tickets.id AS nb',
                    ],
                    'DISTINCT'        => true,
                    'FROM' => 'glpi_tickets',
                    'WHERE' => [
                        $is_deleted,
                        'NOT'       => ['glpi_tickets.status' => [CommonITILObject::SOLVED, CommonITILObject::CLOSED],
                            'takeintoaccount_delay_stat' => null,
                            'time_to_own' => null],
                    ],
                ];

                if ($type > 0) {
                    $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_tickets.type' => $type];
                }

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                    'glpi_tickets'
                );

                $iterator = $DB->request($criteria);
                $nb = 0;
                $total = 0;
                if (count($iterator) > 0) {
                    $nb = count($iterator);
                    foreach ($iterator as $data) {
                        $total += $data['nb'];
                    }
                }

                $datas = [];

                $criteria = [
                    'SELECT' => [
                        'COUNT' => 'glpi_tickets.id AS nb',
                    ],
                    'FROM' => 'glpi_tickets',
                    'WHERE' => [
                        $is_deleted,
                        'NOT' => ['glpi_tickets.status' => [CommonITILObject::SOLVED, CommonITILObject::CLOSED],
                            'takeintoaccount_delay_stat' => null,
                            'time_to_own' => null],
                        ['takeintoaccount_delay_stat' => ['>', new QueryExpression("TIMESTAMPDIFF(SECOND," . $DB::quoteName('date') . ",
                                                                               " . $DB::quoteName('time_to_own') . ")")],
                            'OR' => [
                                'takeintoaccount_delay_stat' => null,
                                'time_to_own' => ['<', QueryFunction::now()],
                            ],
                        ],
                    ],
                ];

                if ($type > 0) {
                    $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_tickets.type' => $type];
                }

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                    'glpi_tickets'
                );

                $iterator = $DB->request($criteria);
                $nb = 0;
                $sum = 0;
                if (count($iterator) > 0) {
                    $nb = count($iterator);
                    foreach ($iterator as $data) {
                        $sum += $data['nb'];
                    }
                }

                $notrespected = 0;
                $respected    = 0;
                if ($nb > 0 && $sum > 0) {
                    $notrespected = round(($sum) * 100 / ($total), 2, PHP_ROUND_HALF_UP);
                    $respected    = round(($total - $sum) * 100 / ($total), 2, PHP_ROUND_HALF_UP);
                    $datas[]      = ['value' => $notrespected,
                        'name'  => __("Not respected TTO", "mydashboard")];

                    $datas[] = ['value' => $respected,
                        'name'  => __("Respected TTO", "mydashboard")];
                }
                $widget = new MydashboardHtml();
                $title   = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetComment($comment);
                $widget->setWidgetTitle((($isDebug) ? "13 " : "") . $title);
                $widget->toggleWidgetRefresh();

                $dataPieset  = json_encode($datas);
                $labelsPie   = json_encode([__("Respected TTO", "mydashboard"), __("Not respected TTO", "mydashboard")]);
                $graph_datas = ['title'   => $title,
                    'comment' => $comment,
                    'name'    => $name,
                    'ids'     => json_encode([]),
                    'data'    => $dataPieset,
                    'labels'  => $labelsPie,
                    'label'   => $title];

                $graph = PieChart::launchPieGraph($graph_datas, []);

                $params = ["widgetId"  => $widgetId,
                    "name"      => $name,
                    "onsubmit"  => false,
                    "opt"       => $opt,
                    "criterias" => $criterias,
                    "export"    => true,
                    "canvas"    => true,
                    "nb"        => $nb];
                $widget->setWidgetHeader(Helper::getGraphHeader($params));
                $widget->setWidgetHtmlContent(
                    $graph
                );

                return $widget;

            case $this->getType() . "16":
                $name = 'IncidentsByCategoryPieChart';
                $onclick = 0;
                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() == 'central') {
                    $criterias = ['entities_id',
                        'is_recursive',
                        'technicians_groups_id',
                        'group_is_recursive',
                        'requesters_groups_id'];
                    $onclick = 1;
                }
                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() != 'central') {
                    $criterias = ['requesters_groups_id'];
                }

                $params  = ["preferences" => $preferences,
                    "criterias"   => $criterias,
                    "opt"         => $opt];
                $options = Helper::manageCriterias($params);

                $opt  = $options['opt'];
                $crit = $options['crit'];

                $entities_id_criteria       = $crit['entity'];
                $sons_criteria              = $crit['sons'];
                $requester_groups           = $opt['requesters_groups_id'];
                $technician_group           = $opt['technicians_groups_id'];

                $names_ipie          = [];
                $datas               = [];
                $tabincidentcategory = [];

                $is_deleted = ['glpi_tickets.is_deleted' => 0];

                $criteria = [
                    'SELECT' => [
                        'glpi_itilcategories.name',
                        'glpi_itilcategories.id AS itilcategories_id',
                        'COUNT' => 'glpi_tickets.id AS nb',
                    ],
                    'DISTINCT'        => true,
                    'FROM' => 'glpi_tickets',
                    'LEFT JOIN'       => [
                        'glpi_itilcategories' => [
                            'ON' => [
                                'glpi_itilcategories'   => 'id',
                                'glpi_tickets'                  => 'itilcategories_id',
                            ],
                        ],
                    ],
                    'WHERE' => [
                        $is_deleted,
                        'glpi_tickets.type' => \Ticket::INCIDENT_TYPE,
                        'NOT'       => ['glpi_tickets.status' => [CommonITILObject::SOLVED, CommonITILObject::CLOSED]],
                    ],
                    'GROUPBY'   => 'glpi_itilcategories.id',
                ];

                if (is_array($technician_group) && count($technician_group) > 0) {
                    $criteria['LEFT JOIN'] = $criteria['LEFT JOIN'] + ['glpi_groups_tickets' => [
                        'ON' => [
                            'glpi_tickets'   => 'id',
                            'glpi_groups_tickets'                  => 'tickets_id', [
                                'AND' => [
                                    'glpi_groups_tickets.type' => CommonITILActor::ASSIGN,
                                ],
                            ],
                        ],
                    ],
                ];

                    $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_groups_tickets.groups_id' => $technician_group];
                }

                if (is_array($requester_groups) && count($requester_groups) > 0) {
                    $criteria['LEFT JOIN'] = $criteria['LEFT JOIN'] + ['glpi_groups_tickets as glpi_groups_requesters' => [
                        'ON' => [
                            'glpi_tickets'   => 'id',
                            'glpi_groups_tickets'                  => 'tickets_id', [
                                'AND' => [
                                    'glpi_groups_tickets.type' => CommonITILActor::REQUESTER,
                                ],
                            ],
                        ],
                    ],
                ];

                    $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_groups_requesters.groups_id' => $requester_groups];
                }

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                    'glpi_tickets'
                );

                $iterator = $DB->request($criteria);
                $nb = 0;
                if (count($iterator) > 0) {
                    $nb = count($iterator);
                    foreach ($iterator as $data) {
                        if ($data['name'] == null) {
                            $name_category = __('None');
                            $names_ipie[]  = __('None');
                        } else {
                            $name_category = $data['name'];
                            $names_ipie[]  = $data['name'];
                        }
                        //                  $datas[]               = $data['nb'];
                        $tabincidentcategory[] = $data['itilcategories_id'];

                        $datas[] = ['value' => $data['nb'],
                            'name'  => $name_category];
                    }
                }

                $widget = new MydashboardHtml();
                $title   = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetComment($comment);
                $widget->setWidgetTitle((($isDebug) ? "16 " : "") . $title);
                $widget->toggleWidgetRefresh();

                $dataPieset             = json_encode($datas);
                $labelsPie              = json_encode($names_ipie);
                $tabincidentcategoryset = json_encode($tabincidentcategory);
                $js_ancestors           = $crit['ancestors'];

                $graph_datas = ['title'   => $title,
                    'comment' => $comment,
                    'name'    => $name,
                    'ids'     => $tabincidentcategoryset,
                    'data'    => $dataPieset,
                    'labels'  => $labelsPie,
                    'label'   => $title,
                ];
                $graph_criterias = [];
                if ($onclick == 1) {
                    $graph_criterias = ['entities_id'        => $entities_id_criteria,
                        'sons'               => $sons_criteria,
                        'technician_group'   => $technician_group,
                        'group_is_recursive' => $js_ancestors,
                        'requester_groups'   => $requester_groups,
                        'widget'             => $widgetId];
                }
                $graph = PieChart::launchPieGraph($graph_datas, $graph_criterias);

                $params = ["widgetId"  => $widgetId,
                    "name"      => $name,
                    "onsubmit"  => true,
                    "opt"       => $opt,
                    "criterias" => $criterias,
                    "export"    => true,
                    "canvas"    => true,
                    "nb"        => $nb];
                $widget->setWidgetHeader(Helper::getGraphHeader($params));

                $widget->setWidgetHtmlContent(
                    $graph
                );

                return $widget;

            case $this->getType() . "17":
                $name = 'RequestsByCategoryPieChart';
                $onclick = 0;
                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() == 'central') {
                    $criterias = ['entities_id',
                        'is_recursive',
                        'technicians_groups_id',
                        'group_is_recursive',
                        'requesters_groups_id',
                        'limit'];
                    $onclick = 1;
                }
                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() != 'central') {
                    $criterias = ['requesters_groups_id'];
                }

                $params  = ["preferences" => $preferences,
                    "criterias"   => $criterias,
                    "opt"         => $opt];
                $options = Helper::manageCriterias($params);

                $opt  = $options['opt'];
                $crit = $options['crit'];

                $entities_id_criteria       = $crit['entity'];
                $sons_criteria              = $crit['sons'];
                $requester_groups           = $opt['requesters_groups_id'];
                $technician_group           = $opt['technicians_groups_id'];

                $names_pie     = [];
                $datas         = [];
                $tabcategory   = [];

                $is_deleted = ['glpi_tickets.is_deleted' => 0];

                $criteria = [
                    'SELECT' => [
                        'glpi_itilcategories.name',
                        'glpi_itilcategories.id AS itilcategories_id',
                        'COUNT' => 'glpi_tickets.id AS nb',
                    ],
                    'DISTINCT'        => true,
                    'FROM' => 'glpi_tickets',
                    'LEFT JOIN'       => [
                        'glpi_itilcategories' => [
                            'ON' => [
                                'glpi_itilcategories'   => 'id',
                                'glpi_tickets'                  => 'itilcategories_id',
                            ],
                        ],
                    ],
                    'WHERE' => [
                        $is_deleted,
                        'glpi_tickets.type' => \Ticket::DEMAND_TYPE,
                        'NOT'       => ['glpi_tickets.status' => [CommonITILObject::SOLVED, CommonITILObject::CLOSED]],
                    ],
                    'GROUPBY'   => 'glpi_itilcategories.id',
                ];

                if (is_array($technician_group) && count($technician_group) > 0) {
                    $criteria['LEFT JOIN'] = $criteria['LEFT JOIN'] + ['glpi_groups_tickets' => [
                        'ON' => [
                            'glpi_tickets'   => 'id',
                            'glpi_groups_tickets'                  => 'tickets_id', [
                                'AND' => [
                                    'glpi_groups_tickets.type' => CommonITILActor::ASSIGN,
                                ],
                            ],
                        ],
                    ],
                ];

                    $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_groups_tickets.groups_id' => $technician_group];
                }

                if (is_array($requester_groups) && count($requester_groups) > 0) {
                    $criteria['LEFT JOIN'] = $criteria['LEFT JOIN'] + ['glpi_groups_tickets as glpi_groups_requesters' => [
                        'ON' => [
                            'glpi_tickets'   => 'id',
                            'glpi_groups_tickets'                  => 'tickets_id', [
                                'AND' => [
                                    'glpi_groups_tickets.type' => CommonITILActor::REQUESTER,
                                ],
                            ],
                        ],
                    ],
                ];

                    $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_groups_requesters.groups_id' => $requester_groups];
                }

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                    'glpi_tickets'
                );

                $iterator = $DB->request($criteria);
                $nb = 0;
                if (count($iterator) > 0) {
                    $nb = count($iterator);
                    foreach ($iterator as $data) {
                        if ($data['name'] == null) {
                            $name_category = __('None');
                            $names_pie[]   = __('None');
                        } else {
                            $name_category = $data['name'];
                            $names_pie[]   = $data['name'];
                            ;
                        }
                        $datas[]       = ['value' => $data['nb'],
                            'name'  => $name_category];
                        $tabcategory[] = $data['itilcategories_id'];
                    }
                }
                $widget = new MydashboardHtml();
                $title   = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetComment($comment);
                $widget->setWidgetTitle((($isDebug) ? "17 " : "") . $title);
                $widget->toggleWidgetRefresh();

                $dataPieset     = json_encode($datas);
                $labelsPie      = json_encode($names_pie);
                $tabcategoryset = json_encode($tabcategory);
                $js_ancestors   = $crit['ancestors'];

                $graph_datas = ['title'   => $title,
                    'comment' => $comment,
                    'name'    => $name,
                    'ids'     => $tabcategoryset,
                    'data'    => $dataPieset,
                    'labels'  => $labelsPie,
                    'label'   => $title,
                ];
                $graph_criterias = [];
                if ($onclick == 1) {
                    $graph_criterias = ['entities_id'        => $entities_id_criteria,
                        'sons'               => $sons_criteria,
                        'technician_group'   => $technician_group,
                        'group_is_recursive' => $js_ancestors,
                        'requester_groups'   => $requester_groups,
                        'widget'             => $widgetId];
                }
                $graph = PieChart::launchPieGraph($graph_datas, $graph_criterias);

                $params = ["widgetId"  => $widgetId,
                    "name"      => $name,
                    "onsubmit"  => true,
                    "opt"       => $opt,
                    "criterias" => $criterias,
                    "export"    => true,
                    "canvas"    => true,
                    "nb"        => $nb];
                $widget->setWidgetHeader(Helper::getGraphHeader($params));

                $widget->setWidgetHtmlContent(
                    $graph
                );

                return $widget;

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

                $params  = ["preferences" => $preferences,
                    "criterias"   => $criterias,
                    "opt"         => $opt];
                $options = Helper::manageCriterias($params);

                $opt  = $options['opt'];
                $crit = $options['crit'];

                $type                       = $opt['type'];
                $requester_groups           = $opt['requesters_groups_id'];
                $technician_group           = $opt['technicians_groups_id'];

                $type_criteria              = $crit['type'];
                $entities_criteria          = $crit['entities_id'];
                $requester_groups_criteria  = $crit['requesters_groups_id'];
                $technician_groups_criteria = $crit['technicians_groups_id'];
                $date_criteria              = $crit['date'];
                $closedate_criteria         = $crit['closedate'];


                //                $query = "SELECT COUNT(`glpi_tickets`.`id`)  AS nb
                //                     FROM `glpi_tickets`
                //                     WHERE $date_criteria
                //                     $entities_criteria $type_criteria $requester_groups_criteria $technician_groups_criteria
                //                     AND $is_deleted";
                //
                //                $result   = $DB->doQuery($query);
                //                $nb       = $DB->numrows($result);
                $dataspie = [];
                $namespie = [];

                $is_deleted = ['glpi_tickets.is_deleted' => 0];

                $criteria = [
                    'SELECT' => [
                        'COUNT' => 'glpi_tickets.id AS nb',
                    ],
                    'FROM' => 'glpi_tickets',
                    'LEFT JOIN' => [],
                    'WHERE' => [
                        $is_deleted,
                        new QueryExpression($date_criteria),
                    ],
                ];

                if (is_array($technician_group) && count($technician_group) > 0) {
                    $criteria['LEFT JOIN'] = $criteria['LEFT JOIN'] + ['glpi_groups_tickets' => [
                        'ON' => [
                            'glpi_tickets'   => 'id',
                            'glpi_groups_tickets'                  => 'tickets_id', [
                                'AND' => [
                                    'glpi_groups_tickets.type' => CommonITILActor::ASSIGN,
                                ],
                            ],
                        ],
                    ],
                ];

                    $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_groups_tickets.groups_id' => $technician_group];
                }

                if (is_array($requester_groups) && count($requester_groups) > 0) {
                    $criteria['LEFT JOIN'] = $criteria['LEFT JOIN'] + ['glpi_groups_tickets as glpi_groups_requesters' => [
                            'ON' => [
                                'glpi_tickets'   => 'id',
                                'glpi_groups_tickets'                  => 'tickets_id', [
                                    'AND' => [
                                        'glpi_groups_tickets.type' => CommonITILActor::REQUESTER,
                                    ],
                                ],
                            ],
                        ],
                    ];

                    $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_groups_requesters.groups_id' => $requester_groups];
                }

                if ($type > 0) {
                    $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_tickets.type' => $type];
                }

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                    'glpi_tickets'
                );

                $iterator = $DB->request($criteria);
                $nb = 0;
                if (count($iterator) > 0) {
                    $nb = count($iterator);
                    foreach ($iterator as $data) {
                        $namespie[] = __("Opened tickets", "mydashboard");
                        $dataspie[] = ['value' => $data['nb'],
                            'name'  => __("Opened tickets", "mydashboard")];
                    }
                }
//                $is_deleted                 = "`glpi_tickets`.`is_deleted` = 0";
//                $query = "SELECT COUNT(`glpi_tickets`.`id`)  AS nb
//                     FROM `glpi_tickets`
//
//                     WHERE $closedate_criteria
//                     $entities_criteria $type_criteria $requester_groups_criteria $technician_groups_criteria
//                     AND $is_deleted";
//
//                $result = $DB->doQuery($query);
//                $nb     = $DB->numrows($result);

                $is_deleted = ['glpi_tickets.is_deleted' => 0];

                $criteria = [
                    'SELECT' => [
                        'COUNT' => 'glpi_tickets.id AS nb',
                    ],
                    'FROM' => 'glpi_tickets',
                    'LEFT JOIN' => [],
                    'WHERE' => [
                        $is_deleted,
                        new QueryExpression($closedate_criteria),
                    ],
                ];

                if (is_array($technician_group) && count($technician_group) > 0) {
                    $criteria['LEFT JOIN'] = $criteria['LEFT JOIN'] + ['glpi_groups_tickets' => [
                            'ON' => [
                                'glpi_tickets'   => 'id',
                                'glpi_groups_tickets'                  => 'tickets_id', [
                                    'AND' => [
                                        'glpi_groups_tickets.type' => CommonITILActor::ASSIGN,
                                    ],
                                ],
                            ],
                        ],
                    ];

                    $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_groups_tickets.groups_id' => $technician_group];
                }

                if (is_array($requester_groups) && count($requester_groups) > 0) {
                    $criteria['LEFT JOIN'] = $criteria['LEFT JOIN'] + ['glpi_groups_tickets as glpi_groups_requesters' => [
                            'ON' => [
                                'glpi_tickets'   => 'id',
                                'glpi_groups_tickets'                  => 'tickets_id', [
                                    'AND' => [
                                        'glpi_groups_tickets.type' => CommonITILActor::REQUESTER,
                                    ],
                                ],
                            ],
                        ],
                    ];

                    $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_groups_requesters.groups_id' => $requester_groups];
                }

                if ($type > 0) {
                    $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_tickets.type' => $type];
                }

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                        'glpi_tickets'
                    );

                $iterator = $DB->request($criteria);
                $nb = 0;
                if (count($iterator) > 0) {
                    $nb = count($iterator);
                    foreach ($iterator as $data) {
                        $namespie[] = __("Closed tickets", "mydashboard");
                        $dataspie[] = ['value' => $data['nb'],
                            'name'  => __("Closed tickets", "mydashboard")];
                    }
                }

                $is_deleted = ['glpi_tickets.is_deleted' => 0];

                $criteria = [
                    'SELECT' => [
                        'COUNT' => 'glpi_tickets.id AS nb',
                    ],
                    'FROM' => 'glpi_tickets',
                    'LEFT JOIN'       => [
                        'glpi_tickettasks' => [
                            'ON' => [
                                'glpi_tickets' => 'id',
                                'glpi_tickettasks'          => 'tickets_id'
                            ]
                        ]
                    ],
                    'WHERE' => [
                        $is_deleted,
                        'glpi_tickettasks.actiontime' => null,
                    ],
                ];

                if (is_array($technician_group) && count($technician_group) > 0) {
                    $criteria['LEFT JOIN'] = $criteria['LEFT JOIN'] + ['glpi_groups_tickets' => [
                            'ON' => [
                                'glpi_tickets'   => 'id',
                                'glpi_groups_tickets'                  => 'tickets_id', [
                                    'AND' => [
                                        'glpi_groups_tickets.type' => CommonITILActor::ASSIGN,
                                    ],
                                ],
                            ],
                        ],
                    ];

                    $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_groups_tickets.groups_id' => $technician_group];
                }

                if (is_array($requester_groups) && count($requester_groups) > 0) {
                    $criteria['LEFT JOIN'] = $criteria['LEFT JOIN'] + ['glpi_groups_tickets as glpi_groups_requesters' => [
                            'ON' => [
                                'glpi_tickets'   => 'id',
                                'glpi_groups_tickets'                  => 'tickets_id', [
                                    'AND' => [
                                        'glpi_groups_tickets.type' => CommonITILActor::REQUESTER,
                                    ],
                                ],
                            ],
                        ],
                    ];

                    $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_groups_requesters.groups_id' => $requester_groups];
                }

                if ($type > 0) {
                    $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_tickets.type' => $type];
                }

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                        'glpi_tickets'
                    );

                $iterator = $DB->request($criteria);
                $nb = 0;
                if (count($iterator) > 0) {
                    $nb = count($iterator);
                    foreach ($iterator as $data) {
                        $namespie[] = __("Not planned", "mydashboard");
                        $dataspie[] = ['value' => $data['nb'],
                            'name'  => __("Not planned", "mydashboard")];
                    }
                }

                $widget = new MydashboardHtml();
                $title   = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetComment($comment);
                $widget->setWidgetTitle((($isDebug) ? "18 " : "") . $title);
                $widget->toggleWidgetRefresh();

                $dataPieset = json_encode($dataspie);
                $labelsPie  = json_encode($namespie);

                $graph_datas = ['title'   => $title,
                    'comment' => $comment,
                    'name'    => $name,
                    'ids'     => json_encode([]),
                    'data'    => $dataPieset,
                    'labels'  => $labelsPie,
                    'label'   => $title];

                $graph = PieChart::launchPieGraph($graph_datas, []);

                $params = ["widgetId"  => $widgetId,
                    "name"      => $name,
                    "onsubmit"  => true,
                    "opt"       => $opt,
                    "criterias" => $criterias,
                    "export"    => true,
                    "canvas"    => true,
                    "nb"        => $nb];
                $widget->setWidgetHeader(Helper::getGraphHeader($params));
                $widget->setWidgetHtmlContent(
                    $graph
                );

                return $widget;

            case $this->getType() . "20":
                $name = 'SolutionTypePieChart';
                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() == 'central') {
                    $criterias = ['entities_id',
                        'is_recursive',
                        'type',
                        'technicians_groups_id',];
                }
                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() != 'central') {
                    $criterias = ['type'];
                }

                $params  = ["preferences" => $preferences,
                    "criterias"   => $criterias,
                    "opt"         => $opt];
                $options = Helper::manageCriterias($params);

                $opt  = $options['opt'];
                $crit = $options['crit'];

                $type                       = $opt['type'];
                $technician_group           = $opt['technicians_groups_id'];
                $name_solution = [];
                $datas         = [];
                $tabsolution   = [];

                $is_deleted = ['glpi_tickets.is_deleted' => 0];

                $criteria = [
                    'SELECT' => [
                        new QueryExpression("IFNULL(" . $DB->quoteName('glpi_solutiontypes.name') . ",-1) AS name"),
                        'glpi_solutiontypes.id AS solutiontypes_id',
                        'COUNT' => 'glpi_tickets.id AS nb',
                    ],
                    'DISTINCT'        => true,
                    'FROM' => 'glpi_tickets',
                    'LEFT JOIN'       => [
                        'glpi_itilsolutions' => [
                            'ON' => [
                                'glpi_itilsolutions' => 'items_id',
                                'glpi_tickets'                  => 'id', [
                                    'AND' => [
                                        'glpi_itilsolutions.itemtype' => Ticket::class,
                                    ],
                                ],
                            ],
                        ],
                        'glpi_solutiontypes' => [
                            'ON' => [
                                'glpi_solutiontypes' => 'id',
                                'glpi_itilsolutions'          => 'solutiontypes_id',
                            ],
                        ],
                    ],
                    'WHERE' => [
                        $is_deleted,
                        'NOT'       => ['glpi_tickets.status' => [CommonITILObject::SOLVED, CommonITILObject::CLOSED]],
                    ],
                    'GROUPBY'   => 'glpi_solutiontypes.id',
                ];

                if (is_array($technician_group) && count($technician_group) > 0) {
                    $criteria['LEFT JOIN'] = $criteria['LEFT JOIN'] + ['glpi_groups_tickets' => [
                        'ON' => [
                            'glpi_tickets'   => 'id',
                            'glpi_groups_tickets'                  => 'tickets_id', [
                                'AND' => [
                                    'glpi_groups_tickets.type' => CommonITILActor::ASSIGN,
                                ],
                            ],
                        ],
                    ],
                ];

                    $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_groups_tickets.groups_id' => $technician_group];
                }

                if ($type > 0) {
                    $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_tickets.type' => $type];
                }

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                    'glpi_tickets'
                );

                $iterator = $DB->request($criteria);
                $nb = 0;
                if (count($iterator) > 0) {
                    $nb = count($iterator);
                    foreach ($iterator as $data) {
                        if ($data['name'] == -1) {
                            $name_solution[] = $data['name'] = __('None');
                        } else {
                            $name_solution[] = $data['name'];
                        }
                        $datas[] = ['value' => $data['nb'],
                            'name'  => $data['name']];

                        $tabsolution[] = $data['solutiontypes_id'];
                    }
                }

                $widget  = new MydashboardHtml();
                $title   = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetComment($comment);
                $widget->setWidgetTitle((($isDebug) ? "20 " : "") . $title);
                $widget->toggleWidgetRefresh();

                $dataPieset     = json_encode($datas);
                $labelsPie      = json_encode($name_solution);
                $tabsolutionset = json_encode($tabsolution);

                $graph_datas = ['title'   => $title,
                    'comment' => $comment,
                    'name'    => $name,
                    'ids'     => $tabsolutionset,
                    'data'    => $dataPieset,
                    'labels'  => $labelsPie,
                    'label'   => $title,
                ];

                $graph = PieChart::launchDonutGraph($graph_datas, []);

                $params = ["widgetId"  => $widgetId,
                    "name"      => $name,
                    "onsubmit"  => false,
                    "opt"       => $opt,
                    "criterias" => $criterias,
                    "export"    => true,
                    "canvas"    => true,
                    "nb"        => $nb];
                $widget->setWidgetHeader(Helper::getGraphHeader($params));
                $widget->setWidgetHtmlContent(
                    $graph
                );

                return $widget;

            case $this->getType() . "25":
                $name         = 'TicketsByRequesterGroupPieChart';

                $criterias = ['entities_id',
                    'is_recursive',
                    'type', 'limit'];

                $opt['limit'] ??= 10;
                $params       = ["preferences" => $preferences,
                    "criterias"   => $criterias,
                    "opt"         => $opt];

                $options      = Helper::manageCriterias($params);

                $opt               = $options['opt'];
                $type              = $opt['type'];
                $limit             = $opt['limit'] ?? 10;

                $name_groups = [];
                $datas       = [];
                $tabgroup    = [];

                $is_deleted = ['glpi_tickets.is_deleted' => 0];

                $criteria = [
                    'SELECT' => [
                        'groups_id AS requesters_groups_id',
                        'COUNT' => 'glpi_tickets.id AS nb',
                    ],
                    'DISTINCT'        => true,
                    'FROM' => 'glpi_tickets',
                    'LEFT JOIN'       => [
                        'glpi_groups_tickets' => [
                            'ON' => [
                                'glpi_tickets'   => 'id',
                                'glpi_groups_tickets'                  => 'tickets_id', [
                                    'AND' => [
                                        'glpi_groups_tickets.type' => CommonITILActor::REQUESTER,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'WHERE' => [
                        $is_deleted,
                        'NOT'       => ['glpi_tickets.status' => [CommonITILObject::SOLVED, CommonITILObject::CLOSED]],
                    ],
                    'GROUPBY'   => 'groups_id',
                    'LIMIT' => $limit,
                ];

                if ($type > 0) {
                    $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_tickets.type' => $type];
                }

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                    'glpi_tickets'
                );

                $iterator = $DB->request($criteria);
                $nb = 0;
                if (count($iterator) > 0) {
                    $nb = count($iterator);
                    foreach ($iterator as $data) {
                        if (!empty($data['requesters_groups_id'])) {
                            $name_grp      = Dropdown::getDropdownName("glpi_groups", $data['requesters_groups_id']);
                            $name_grp = html_entity_decode($name_grp);
                            $name_groups[] = Dropdown::getDropdownName("glpi_groups", $data['requesters_groups_id']);
                        } else {
                            $name_groups[] = __('None');
                            $name_grp      = __('None');
                        }
                        //                  $datas[] = $data['nb'];
                        $datas[] = ['value' => $data['nb'],
                            'name'  => $name_grp];
                        if (!empty($data['requesters_groups_id'])) {
                            $tabgroup[] = $data['requesters_groups_id'];
                        } else {
                            $tabgroup[] = 0;
                        }
                    }
                }

                $widget = new MydashboardHtml();
                $title   = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetComment($comment);
                $widget->setWidgetTitle((($isDebug) ? "25 " : "") . $title);
                $widget->toggleWidgetRefresh();

                $dataPieset  = json_encode($datas);
                $labelsPie   = json_encode($name_groups);
                $tabgroupset = json_encode($tabgroup);

                $graph_datas = ['title'   => $title,
                    'comment' => $comment,
                    'name'    => $name,
                    'ids'     => $tabgroupset,
                    'data'    => $dataPieset,
                    'labels'  => $labelsPie,
                    'label'   => $title];

                $graph = PieChart::launchPieGraph($graph_datas, []);

                $params = ["widgetId"  => $widgetId,
                    "name"      => $name,
                    "onsubmit"  => false,
                    "opt"       => $opt,
                    "criterias" => $criterias,
                    "export"    => true,
                    "canvas"    => true,
                    "nb"        => $nb];
                $widget->setWidgetHeader(Helper::getGraphHeader($params));
                $widget->setWidgetHtmlContent(
                    $graph
                );

                return $widget;

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

                $params  = ["preferences" => $preferences,
                    "criterias"   => $criterias,
                    "opt"         => $opt];
                $options = Helper::manageCriterias($params);

                $opt  = $options['opt'];
                $crit = $options['crit'];

                $closedate_criteria = $crit['closedate'];

                $notsatisfy = 0;
                $satisfy    = 0;
                $datas = [];

                $is_deleted = ['glpi_tickets.is_deleted' => 0];

                $criteria = [
                    'SELECT' => [
                        'AVG' => [
                            'glpi_ticketsatisfactions.satisfaction AS satisfaction',
                        ],
                    ],
                    'FROM' => 'glpi_tickets',
                    'INNER JOIN'       => [
                        'glpi_ticketsatisfactions' => [
                            'ON' => [
                                'glpi_tickets'   => 'id',
                                'glpi_ticketsatisfactions' => 'tickets_id',
                            ],
                        ],
                    ],
                    'WHERE' => [
                        $is_deleted,
                        'status' => [CommonITILObject::CLOSED],
                        'NOT'       => ['glpi_tickets.closedate' => null, 'glpi_ticketsatisfactions.date_answered' => null],
                        new QueryExpression($closedate_criteria),
                    ],
                ];

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                    'glpi_tickets'
                );

                $iterator = $DB->request($criteria);
                $nb = 0;
                if (count($iterator) > 0) {
                    $nb = count($iterator);
                    foreach ($iterator as $data) {
                        //                if ($nb > 0 && $sum['satisfaction'] > 0) {
                        $satisfy = round(($data['satisfaction']) * 100 / (5), 2, PHP_ROUND_HALF_UP);
                        $notsatisfy = round(100 - $satisfy, 2, PHP_ROUND_HALF_UP);

                        $datas[] = [
                            'value' => $satisfy,
                            'name' => __("Satisfy percent", "mydashboard"),
                        ];

                        $datas[] = [
                            'value' => $notsatisfy,
                            'name' => __("Not satisfy percent", "mydashboard"),
                        ];
                    }
                }

                $widget = new MydashboardHtml();
                $title   = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetComment($comment);
                $widget->setWidgetTitle((($isDebug) ? "26 " : "") . $title);
                $widget->toggleWidgetRefresh();

                $dataPieset = json_encode($datas);
                $labelsPie  = json_encode([__("Satisfy percent", "mydashboard"), __("Not satisfy percent", "mydashboard")]);

                $graph_datas = ['title'   => $title,
                    'comment' => $comment,
                    'name'    => $name,
                    'ids'     => json_encode([]),
                    'data'    => $dataPieset,
                    'labels'  => $labelsPie,
                    'label'   => $title];

                $graph = PieChart::launchPieGraph($graph_datas, []);

                $params = ["widgetId"  => $widgetId,
                    "name"      => $name,
                    "onsubmit"  => false,
                    "opt"       => $opt,
                    "criterias" => $criterias,
                    "export"    => true,
                    "canvas"    => true,
                    "nb"        => $nb];
                $widget->setWidgetHeader(Helper::getGraphHeader($params));
                $widget->setWidgetHtmlContent(
                    $graph
                );

                return $widget;

            case $this->getType() . "27":
                $name    = 'TicketsByLocationPieChart';
                $onclick = 0;
                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() == 'central') {
                    $criterias = ['entities_id',
                        'is_recursive',
                        'type',
                        'technicians_groups_id',
                        'group_is_recursive',
                        'limit'];
                    $onclick   = 1;
                }
                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() != 'central') {
                    $criterias = ['type', 'limit'];
                }
                $opt['limit'] ??= 10;
                $params       = ["preferences" => $preferences,
                    "criterias"   => $criterias,
                    "opt"         => $opt];

                $options = Helper::manageCriterias($params);

                $opt                        = $options['opt'];
                $crit                       = $options['crit'];
                $type                       = $opt['type'];
                $entities_id_criteria       = $crit['entity'];
                $sons_criteria              = $crit['sons'];
                $technician_group           = $opt['technicians_groups_id'];
                $limit                      = $opt['limit'] ?? 10;


                $name_location = [];
                $datas         = [];
                $tablocation   = [];

                $is_deleted = ['glpi_tickets.is_deleted' => 0];

                $criteria = [
                    'SELECT' => [
                        'glpi_locations.id AS locations_id',
                        'COUNT' => 'glpi_tickets.id AS count',
                    ],
                    'FROM' => 'glpi_tickets',
                    'LEFT JOIN' => ['glpi_locations' => [
                        'ON' => [
                            'glpi_locations'   => 'id',
                            'glpi_tickets'                  => 'locations_id',
                        ],
                    ],
                    ],
                    'WHERE' => [
                        $is_deleted,
                        'NOT'       => ['glpi_tickets.status' => [CommonITILObject::SOLVED, CommonITILObject::CLOSED]],
                    ],
                    'GROUPBY'   => 'glpi_locations.id',
                    'ORDERBY'   => 'count DESC',
                    'LIMIT' => $limit,
                ];

                if (is_array($technician_group) && count($technician_group) > 0) {
                    $criteria['LEFT JOIN'] = $criteria['LEFT JOIN'] + ['glpi_groups_tickets' => [
                        'ON' => [
                            'glpi_tickets'   => 'id',
                            'glpi_groups_tickets'                  => 'tickets_id', [
                                'AND' => [
                                    'glpi_groups_tickets.type' => CommonITILActor::ASSIGN,
                                ],
                            ],
                        ],
                    ],
                ];

                    $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_groups_tickets.groups_id' => $technician_group];
                }

                if ($type > 0) {
                    $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_tickets.type' => $type];
                }

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                    'glpi_tickets'
                );

                $iterator = $DB->request($criteria);
                $nb = 0;
                if (count($iterator) > 0) {
                    $nb = count($iterator);
                    foreach ($iterator as $data) {
                        if (!empty($data['locations_id'])) {
                            $name_location[] = Dropdown::getDropdownName("glpi_locations", $data['locations_id']);
                            $name_loc        = Dropdown::getDropdownName("glpi_locations", $data['locations_id']);
                            $name_loc = html_entity_decode($name_loc);
                        } else {
                            $name_loc        = __('None');
                            $name_location[] = __('None');
                        }
                        $datas[] = ['value' => $data['count'],
                            'name'  => $name_loc];
                        if (!empty($data['locations_id'])) {
                            $tablocation[] = $data['locations_id'];
                        } else {
                            $tablocation[] = 0;
                        }
                    }
                }

                $widget = new MydashboardHtml();
                $title   = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetComment($comment);
                $widget->setWidgetTitle((($isDebug) ? "27 " : "") . $title);
                $widget->toggleWidgetRefresh();

                $dataPieset     = json_encode($datas);
                $labelsPie      = json_encode($name_location);
                $tablocationset = json_encode($tablocation);
                $js_ancestors   = $crit['ancestors'];

                $graph_datas     = ['title'   => $title,
                    'comment' => $comment,
                    'name'    => $name,
                    'ids'     => $tablocationset,
                    'data'    => $dataPieset,
                    'labels'  => $labelsPie,
                    'label'   => $title];
                $graph_criterias = [];
                if ($onclick == 1) {
                    $graph_criterias = ['entities_id'        => $entities_id_criteria,
                        'sons'               => $sons_criteria,
                        'technician_group'   => $technician_group,
                        'group_is_recursive' => $js_ancestors,
                        'type'               => $type,
                        'widget'             => $widgetId];
                }
                $graph = PieChart::launchPolarAreaGraph($graph_datas, $graph_criterias);

                $params = ["widgetId"  => $widgetId,
                    "name"      => $name,
                    "onsubmit"  => true,
                    "opt"       => $opt,
                    "criterias" => $criterias,
                    "export"    => true,
                    "canvas"    => true,
                    "nb"        => $nb];

                $widget->setWidgetHeader(Helper::getGraphHeader($params));
                $widget->setWidgetHtmlContent(
                    $graph
                );
                return $widget;

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

                $params  = ["preferences" => $preferences,
                    "criterias"   => $criterias,
                    "opt"         => $opt];
                $options = Helper::manageCriterias($params);

                $opt  = $options['opt'];
                $crit = $options['crit'];

                $type                       = $opt['type'];
                $technician_group           = $opt['technicians_groups_id'];

                $name_requesttypes = [];
                $datas             = [];
                $tabrequest        = [];

                $is_deleted = ['glpi_tickets.is_deleted' => 0];

                $criteria = [
                    'SELECT' => [
                        new QueryExpression("IFNULL(" . $DB->quoteName('glpi_requesttypes.name') . ",-1) AS name"),
                        'glpi_requesttypes.id AS requesttypes_id',
                        'COUNT' => 'glpi_tickets.id AS nb',
                    ],
                    'DISTINCT'        => true,
                    'FROM' => 'glpi_tickets',
                    'LEFT JOIN' => ['glpi_requesttypes' => [
                        'ON' => [
                            'glpi_requesttypes'   => 'id',
                            'glpi_tickets'                  => 'requesttypes_id',
                        ],
                    ],
                    ],
                    'WHERE' => [
                        $is_deleted,
                        'NOT'       => ['glpi_tickets.status' => [CommonITILObject::SOLVED, CommonITILObject::CLOSED]],
                    ],
                    'GROUPBY'   => 'glpi_requesttypes.id',
                ];

                if (is_array($technician_group) && count($technician_group) > 0) {
                    $criteria['LEFT JOIN'] = $criteria['LEFT JOIN'] + ['glpi_groups_tickets' => [
                        'ON' => [
                            'glpi_tickets'   => 'id',
                            'glpi_groups_tickets'                  => 'tickets_id', [
                                'AND' => [
                                    'glpi_groups_tickets.type' => CommonITILActor::ASSIGN,
                                ],
                            ],
                        ],
                    ],
                ];

                    $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_groups_tickets.groups_id' => $technician_group];
                }

                if ($type > 0) {
                    $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_tickets.type' => $type];
                }

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                    'glpi_tickets'
                );

                $iterator = $DB->request($criteria);
                $nb = 0;
                if (count($iterator) > 0) {
                    $nb = count($iterator);
                    foreach ($iterator as $data) {
                        if ($data['name'] == -1) {
                            $name_requesttypes[] = $data['name'] = __('None');
                        } else {
                            $name_requesttypes[] = $data['name'];
                        }
                        $datas[]      = ['value' => $data['nb'],
                            'name'  => $data['name']];
                        $tabrequest[] = $data['requesttypes_id'];
                    }
                }

                $widget  = new MydashboardHtml();
                $title   = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetComment($comment);
                $widget->setWidgetTitle((($isDebug) ? "30 " : "") . $title);
                $widget->toggleWidgetRefresh();

                $dataPieset    = json_encode($datas);
                $labelsPie     = json_encode($name_requesttypes);
                $tabrequestset = json_encode($tabrequest);

                $graph_datas = ['title'   => $title,
                    'comment' => $comment,
                    'name'    => $name,
                    'ids'     => $tabrequestset,
                    'data'    => $dataPieset,
                    'labels'  => $labelsPie,
                    'label'   => $title];

                $graph = PieChart::launchDonutGraph($graph_datas, []);

                $params = ["widgetId"  => $widgetId,
                    "name"      => $name,
                    "onsubmit"  => false,
                    "opt"       => $opt,
                    "criterias" => $criterias,
                    "export"    => true,
                    "canvas"    => true,
                    "nb"        => $nb];
                $widget->setWidgetHeader(Helper::getGraphHeader($params));

                $widget->setWidgetHtmlContent(
                    $graph
                );

                return $widget;

            case $this->getType() . "31":
                $name    = 'TicketsByLocationPolarChart';
                $onclick = 0;
                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() == 'central') {
                    $criterias = ['entities_id',
                        'is_recursive',
                        'type',
                        'year',
                        'month',
                        'technicians_groups_id'];
                    $onclick   = 1;
                }
                $params  = ["preferences" => $preferences,
                    "criterias"   => $criterias,
                    "opt"         => $opt];
                $options = Helper::manageCriterias($params);

                $opt                        = $options['opt'];
                $crit                       = $options['crit'];
                $type                       = $opt['type'];
                $entities_id_criteria       = $crit['entity'];
                $sons_criteria              = $crit['sons'];
                $technician_group           = $opt['technicians_groups_id'];

                $date_criteria              = $crit['date'];

                $name_location1 = [];
                $datas          = [];
                $tablocation    = [];

                $is_deleted = ['glpi_tickets.is_deleted' => 0];

                $criteria = [
                    'SELECT' => [
                        'glpi_tickets.locations_id',
                        'COUNT' => 'glpi_tickets.id AS nb',
                    ],
                    'FROM' => 'glpi_tickets',
                    'LEFT JOIN' => ['glpi_locations' => [
                        'ON' => [
                            'glpi_locations'   => 'id',
                            'glpi_tickets'                  => 'locations_id',
                        ],
                    ],
                    ],
                    'WHERE' => [
                        $is_deleted,
                        'NOT'       => ['glpi_tickets.status' => [CommonITILObject::SOLVED, CommonITILObject::CLOSED]],
                        new QueryExpression($date_criteria),
                    ],
                    'GROUPBY'   => 'locations_id',
                ];

                if (is_array($technician_group) && count($technician_group) > 0) {
                    $criteria['LEFT JOIN'] = $criteria['LEFT JOIN'] + ['glpi_groups_tickets' => [
                        'ON' => [
                            'glpi_tickets'   => 'id',
                            'glpi_groups_tickets'                  => 'tickets_id', [
                                'AND' => [
                                    'glpi_groups_tickets.type' => CommonITILActor::ASSIGN,
                                ],
                            ],
                        ],
                    ],
                ];

                    $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_groups_tickets.groups_id' => $technician_group];
                }

                if ($type > 0) {
                    $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_tickets.type' => $type];
                }

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                    'glpi_tickets'
                );

                $iterator = $DB->request($criteria);
                $nb = 0;
                if (count($iterator) > 0) {
                    $nb = count($iterator);
                    foreach ($iterator as $data) {
                        if (!empty($data['locations_id'])) {
                            $name_location1[] = Dropdown::getDropdownName("glpi_locations", $data['locations_id']);
                            $name_loc1        = Dropdown::getDropdownName("glpi_locations", $data['locations_id']);
                            $name_loc1 = html_entity_decode($name_loc1);
                        } else {
                            $name_location1[] = __('None');
                            $name_loc1        = __('None');
                        }
                        $datas[] = ['value' => $data['nb'],
                            'name'  => $name_loc1];

                        if (!empty($data['locations_id'])) {
                            $tablocation[] = $data['locations_id'];
                        } else {
                            $tablocation[] = 0;
                        }
                    }
                }

                $widget = new MydashboardHtml();
                $title   = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetComment($comment);
                $widget->setWidgetTitle((($isDebug) ? "40 " : "") . $title);
                $widget->toggleWidgetRefresh();

                $dataPolarset   = json_encode($datas);
                $labelsPolar    = json_encode($name_location1);
                $tablocationset = json_encode($tablocation);
                $graph_datas    = ['title'   => $title,
                    'comment' => $comment,
                    'name'    => $name,
                    'ids'     => $tablocationset,
                    'data'    => $dataPolarset,
                    'labels'  => $labelsPolar,
                    'label'   => $title];

                $graph_criterias = [];
                if ($onclick == 1) {
                    $graph_criterias = ['entities_id'      => $entities_id_criteria,
                        'sons'             => $sons_criteria,
                        'technician_group' => $technician_group,
                        'type'             => $type,
                        'widget'           => $widgetId];
                }
                $graph = PieChart::launchPolarAreaGraph($graph_datas, $graph_criterias);

                $params = ["widgetId"  => $widgetId,
                    "name"      => $name,
                    "onsubmit"  => false,
                    "opt"       => $opt,
                    "criterias" => $criterias,
                    "export"    => true,
                    "canvas"    => true,
                    "nb"        => $nb];
                $widget->setWidgetHeader(Helper::getGraphHeader($params));
                $widget->setWidgetHtmlContent(
                    $graph
                );

                return $widget;

            case $this->getType() . "32":
                $name    = 'TicketsByAppliancePieChart';
                $onclick = 0;
                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() == 'central') {
                    $criterias = ['entities_id',
                        'is_recursive',
                        'type',
                        'technicians_groups_id',
                        'group_is_recursive',
                        'limit'];
                    //                    $onclick   = 1;
                }
                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() != 'central') {
                    $criterias = ['type', 'limit'];
                }
                $opt['limit'] ??= 10;
                $params       = ["preferences" => $preferences,
                    "criterias"   => $criterias,
                    "opt"         => $opt];

                $options = Helper::manageCriterias($params);

                $opt                        = $options['opt'];
                $crit                       = $options['crit'];
                $type                       = $opt['type'];
                $technician_group           = $opt['technicians_groups_id'];
                $limit                      = $opt['limit'] ?? 10;

                $name_appliance = [];
                $datas         = [];
                $tabapp   = [];

                $is_deleted = ['glpi_tickets.is_deleted' => 0];

                $criteria = [
                    'SELECT' => [
                        'glpi_items_tickets.items_id AS appliances_id',
                        'COUNT' => 'glpi_tickets.id AS count',
                    ],
                    'FROM' => 'glpi_tickets',
                    'INNER JOIN' => ['glpi_items_tickets' => [
                        'ON' => [
                            'glpi_items_tickets'   => 'tickets_id',
                            'glpi_tickets'                  => 'id', [
                                'AND' => [
                                    'glpi_items_tickets.itemtype' => Appliance::class,
                                ],
                            ],
                        ],
                    ],
                    ],
                    'WHERE' => [
                        $is_deleted,
                    ],
                    'GROUPBY'   => 'glpi_items_tickets.items_id',
                    'ORDERBY'   => 'count DESC',
                    'LIMIT' => $limit,
                ];

                if (is_array($technician_group) && count($technician_group) > 0) {
                    $criteria['LEFT JOIN'] = ['glpi_groups_tickets' => [
                        'ON' => [
                            'glpi_tickets'   => 'id',
                            'glpi_groups_tickets'                  => 'tickets_id', [
                                'AND' => [
                                    'glpi_groups_tickets.type' => CommonITILActor::ASSIGN,
                                ],
                            ],
                        ],
                    ],
                ];

                    $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_groups_tickets.groups_id' => $technician_group];
                }

                if ($type > 0) {
                    $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_tickets.type' => $type];
                }

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                    'glpi_tickets'
                );

                $iterator = $DB->request($criteria);
                $nb = 0;
                if (count($iterator) > 0) {
                    $nb = count($iterator);
                    foreach ($iterator as $data) {
                        if (!empty($data['appliances_id'])) {
                            $name_appliance[] = Dropdown::getDropdownName("glpi_appliances", $data['appliances_id']);
                            $name_app        = Dropdown::getDropdownName("glpi_appliances", $data['appliances_id']);
                        } else {
                            $name_app        = __('None');
                            $name_appliance[] = __('None');
                        }
                        //                  $datas[] = $data['count'];
                        $datas[] = ['value' => $data['count'],
                            'name'  => $name_app];
                        if (!empty($data['appliances_id'])) {
                            $tabapp[] = $data['appliances_id'];
                        } else {
                            $tabapp[] = 0;
                        }
                    }
                }

                $widget = new MydashboardHtml();
                $title   = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetComment($comment);
                $widget->setWidgetTitle((($isDebug) ? "32 " : "") . $title);
                $widget->toggleWidgetRefresh();

                $dataPieset     = json_encode($datas);
                $labelsPie      = json_encode($name_appliance);
                $tabappset = json_encode($tabapp);
                $js_ancestors   = $crit['ancestors'];

                $graph_datas     = ['title'   => $title,
                    'comment' => $comment,
                    'name'    => $name,
                    'ids'     => $tabappset,
                    'data'    => $dataPieset,
                    'labels'  => $labelsPie,
                    'label'   => $title];
                $graph_criterias = [];
                //                if ($onclick == 1) {
                //                    $graph_criterias = [];
                //                }
                $graph = PieChart::launchPolarAreaGraph($graph_datas, $graph_criterias);

                $params = ["widgetId"  => $widgetId,
                    "name"      => $name,
                    "onsubmit"  => true,
                    "opt"       => $opt,
                    "criterias" => $criterias,
                    "export"    => true,
                    "canvas"    => true,
                    "nb"        => $nb];
                $widget->setWidgetHeader(Helper::getGraphHeader($params));
                $widget->setWidgetHtmlContent(
                    $graph
                );

                return $widget;
            default:
                break;
        }
        return false;
    }

    /**
     * @param $selected_id
     *
     * @return string
     */
    public static function pluginMydashboardReports_Pie2link($params)
    {
        global $CFG_GLPI;

        $options['reset'][] = 'reset';

        $options = Chart::addCriteria(Chart::STATUS, 'equals', 'notold', 'AND');

        $options = Chart::addCriteria(Chart::PRIORITY, 'equals', $params["selected_id"], 'AND');

        if ($params["params"]["type"] > 0) {
            $options = Chart::addCriteria(Chart::TYPE, 'equals', $params["params"]["type"], 'AND');
        }

        $options = Chart::addCriteria(Chart::ENTITIES_ID, (isset($params["params"]["sons"])
                                                                                              && $params["params"]["sons"] > 0) ? 'under' : 'equals', $params["params"]["entities_id"], 'AND');

        $options = Chart::groupCriteria(Chart::TECHNICIAN_GROUP, ((isset($params["params"]["group_is_recursive"])
                                                                                                     && !empty($params["params"]["group_is_recursive"])) ? 'under' : 'equals'), $params["params"]["technician_group"]);


        return  $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&'
                . Toolbox::append_params($options, "&");
    }


    /**
     * @param $selected_id
     *
     * @return string
     */
    public static function pluginMydashboardReports_Pie16link($params)
    {
        global $CFG_GLPI;

        $options['reset'][] = 'reset';

        $options = Chart::addCriteria(Chart::STATUS, 'equals', 'notold', 'AND');

        $options = Chart::addCriteria(Chart::TYPE, 'equals', (($params["params"]["widget"] == PieChart::class . "16") ? \Ticket::INCIDENT_TYPE : \Ticket::DEMAND_TYPE), 'AND');

        $options = Chart::addCriteria(Chart::CATEGORY, ((empty($params["selected_id"])) ? 'contains' : 'equals'), ((empty($params["selected_id"])) ? '^$' : $params["selected_id"]), 'AND');

        $options = Chart::groupCriteria(Chart::REQUESTER_GROUP, 'equals', $params["params"]["requester_groups"]);

        $options = Chart::groupCriteria(Chart::TECHNICIAN_GROUP, ((isset($params["params"]["group_is_recursive"])
                                          && !empty($params["params"]["group_is_recursive"])) ? 'under' : 'equals'), $params["params"]["technician_group"]);

        $options = Chart::addCriteria(Chart::ENTITIES_ID, (isset($params["params"]["sons"])
                                  && $params["params"]["sons"] > 0) ? 'under' : 'equals', $params["params"]["entities_id"], 'AND');

        return  $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&'
                . Toolbox::append_params($options, "&");
    }


    /**
     * @param $selected_id
     *
     * @return string
     */
    public static function pluginMydashboardReports_Pie25link($params)
    {
        global $CFG_GLPI;

        $options['reset'][] = 'reset';

        $options = Chart::addCriteria(Chart::STATUS, 'equals', 'notold', 'AND');
        // requester_group
        $options = Chart::addCriteria(71, ((empty($params["selected_id"])) ? 'contains' : 'equals'), ((empty($params["selected_id"])) ? '^$' : $params["selected_id"]), 'AND');

        if ($params["params"]["type"] > 0) {
            $options = Chart::addCriteria(Chart::TYPE, 'equals', $params["params"]["type"], 'AND');
        }
        return  $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&'
                . Toolbox::append_params($options, "&");
    }


    /**
     * @param $selected_id
     *
     * @return string
     */
    public static function pluginMydashboardReports_Pie27link($params)
    {
        global $CFG_GLPI;

        $options['reset'][] = 'reset';

        $options = Chart::addCriteria(Chart::STATUS, 'equals', 'notold', 'AND');

        $options = Chart::addCriteria(Chart::LOCATIONS_ID, ((empty($params["selected_id"])) ? 'contains' : 'equals'), ((empty($params["selected_id"])) ? '^$' : $params["selected_id"]), 'AND');

        if ($params["params"]["type"] > 0) {
            $options = Chart::addCriteria(Chart::TYPE, 'equals', $params["params"]["type"], 'AND');
        }

        $options = Chart::addCriteria(Chart::ENTITIES_ID, (isset($params["params"]["sons"])
                                  && $params["params"]["sons"] > 0) ? 'under' : 'equals', $params["params"]["entities_id"], 'AND');

        $options = Chart::groupCriteria(Chart::TECHNICIAN_GROUP, ((isset($params["params"]["group_is_recursive"])
                                          && !empty($params["params"]["group_is_recursive"])) ? 'under' : 'equals'), $params["params"]["technician_group"]);


        return  $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&'
                . Toolbox::append_params($options, "&");
    }
}
