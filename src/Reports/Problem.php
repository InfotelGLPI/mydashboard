<?php

/**
 * -------------------------------------------------------------------------
 * mydashboard plugin for GLPI
 * Copyright (C) 2016-2026 by the mydashboard Development Team.
 *
 * https://github.com/InfotelGLPI/mydashboard
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of mydashboard.
 *
 * mydashboard is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * mydashboard is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with mydashboard. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Mydashboard\Reports;

use CommonGLPI;
use CommonITILActor;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QueryFunction;
use Glpi\RichText\RichText;
use GlpiPlugin\Mydashboard\Datatable;
use GlpiPlugin\Mydashboard\Helper;
use GlpiPlugin\Mydashboard\Html as MydashboardHtml;
use GlpiPlugin\Mydashboard\Menu;
use GlpiPlugin\Mydashboard\Widget;
use Session;
use Toolbox;

/**
 * This class extends GLPI class problem to add the functions to display a widget on Dashboard
 */
class Problem extends CommonGLPI
{
    /**
     * @param int $nb
     *
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return __('Dashboard', 'mydashboard');
    }

    /**
     * @return array
     */
    public function getWidgetsForItem()
    {
        $widgets = [];
        $showproblem = Session::haveRightsOr(\Problem::$rightname, [\Problem::READALL, \Problem::READMY]);

        if ($showproblem) {
            $widgets = [
                Menu::$HELPDESK
                    => [
                        "problemprocesswidget" => [
                            "title" => __('Problems to be processed'),
                            "type" => Widget::$TABLE,
                            "comment" => "",
                        ],
                        "problemwaitingwidget" => [
                            "title" => __('Problems on pending status'),
                            "type" => Widget::$TABLE,
                            "comment" => "",
                        ],
                        "problemcountwidget" => [
                            "title" => __('Problem followup', 'mydashboard'),
                            "type" => Widget::$TABLE,
                            "comment" => "",
                        ],
                    ],
                Menu::$GROUP_VIEW
                    => [
                        "problemprocesswidgetgroup" => [
                            "title" => __('Problems to be processed'),
                            "type" => Widget::$TABLE,
                            "comment" => "",
                        ],
                        "problemwaitingwidgetgroup" => [
                            "title" => __('Problems on pending status'),
                            "type" => Widget::$TABLE,
                            "comment" => "",
                        ],
                    ],
            ];
        }
        return $widgets;
    }


    /**
     * @param $widgetId
     *
     * @return Datatable
     */
    public function getWidgetContentForItem($widgetId)
    {
        $showproblem = Session::haveRightsOr(\Problem::$rightname, [\Problem::READALL, \Problem::READMY]);

        if ($showproblem) {
            switch ($widgetId) {
                case "problemprocesswidget":
                    return self::showCentralList(0, "process", false);
                    break;
                case "problemprocesswidgetgroup":
                    return self::showCentralList(0, "process", true);
                    break;
                case "problemwaitingwidget":
                    return self::showCentralList(0, "waiting", false);
                    break;
                case "problemwaitingwidgetgroup":
                    return self::showCentralList(0, "waiting", true);
                    break;
                case "problemcountwidget":
                    return self::showCentralCount();
                    break;
            }
        }
    }

    /**
     * @param        $start
     * @param string $status
     * @param bool $showgroupproblems
     *
     * @return Datatable
     */
    public static function showCentralList($start, $status = "process", $showgroupproblems = true)
    {
        global $DB, $CFG_GLPI;

        $output = [];
        //We declare our new widget
        $widget = new Datatable();
        if ($status == "waiting") {
            $widget->setWidgetTitle(__('Problems on pending status'));
        } else {
            $widget->setWidgetTitle(__('Problems to be processed'));
        }
        $group = ($showgroupproblems) ? "group" : "";
        $widget->setWidgetId("problem" . $status . "widget" . $group);
        //Here we set few otions concerning the jquery library Datatable, bPaginate for paginating ...
        $widget->setOption("bPaginate", false);
        $widget->setOption("bFilter", false);
        $widget->setOption("bInfo", false);

        if (!Session::haveRightsOr(\Problem::$rightname, [\Problem::READALL, \Problem::READMY])) {
            return false;
        }

        $search_users_id = ['glpi_problems_users.users_id' =>  Session::getLoginUserID(),
            'glpi_problems_users.type' => CommonITILActor::REQUESTER];

        $search_assign = ['glpi_problems_users.users_id' =>  Session::getLoginUserID(),
            'glpi_problems_users.type' => CommonITILActor::ASSIGN];


        if ($showgroupproblems) {
            $search_users_id = [];
            $search_assign = [];

            if (count($_SESSION['glpigroups'])) {

                $search_assign = ['glpi_groups_problems.groups_id' =>  $_SESSION['glpigroups'],
                    'glpi_groups_problems.type' => CommonITILActor::ASSIGN];

                $search_users_id = ['glpi_groups_problems.groups_id' =>  $_SESSION['glpigroups'],
                    'glpi_groups_problems.type' => CommonITILActor::REQUESTER];
            }
        }
        $criteria = [
            'SELECT' => 'glpi_problems.id',
            'DISTINCT'        => true,
            'FROM' => 'glpi_problems',
            'LEFT JOIN'       => [
                'glpi_problems_users' => [
                    'ON' => [
                        'glpi_problems' => 'id',
                        'glpi_problems_users'          => 'problems_id',
                    ],
                ],
                'glpi_groups_problems' => [
                    'ON' => [
                        'glpi_problems' => 'id',
                        'glpi_groups_problems'          => 'problems_id',
                    ],
                ],
            ],
            'WHERE' => ['glpi_problems.is_deleted' =>  0],
            'ORDERBY' => 'glpi_problems.date_mod DESC',
        ];

        switch ($status) {
            case "waiting": // on affiche les problemes en attente
                $criteria['WHERE'] = $criteria['WHERE'] + $search_assign;

                $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_problems.status' =>  \Problem::WAITING];

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                    'glpi_problems',
                );
                break;

            case "process": // on affiche les problemes planifiés ou assignés au user

                $criteria['WHERE'] = $criteria['WHERE'] + $search_assign;

                $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_problems.status' =>  [\Problem::PLANNED, \Problem::ASSIGNED]];

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                    'glpi_problems',
                );

                break;

            default:

                $criteria['WHERE'] = $criteria['WHERE'] + $search_users_id;

                $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_problems.status' =>  [\Problem::INCOMING, \Problem::ACCEPTED, \Problem::PLANNED,  \Problem::ASSIGNED,   \Problem::WAITING]];

                $criteria['WHERE'] = $criteria['WHERE'] + ['solvedate' => ['>', QueryFunction::dateSub(
                    date: QueryFunction::now(),
                    interval: '30',
                    interval_unit: 'DAY',
                )]];

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                    'glpi_problems',
                );
        }


        $iterator = $DB->request($criteria);
        $numrows = count($iterator);

        if ($numrows > 0) {
            $options['reset'] = 'reset';
            $forcetab = '';
            $num = 0;
            if ($showgroupproblems) {
                switch ($status) {
                    case "waiting":
                        $options = Toolbox::append_params([
                            'reset'      => 'reset',
                            'criteria'   => [
                                0 => [
                                    'value'      => $_SESSION['glpigroups'],
                                    'searchtype' => 'equals',
                                    'field'      => 8,
                                    'link'       => 'AND',
                                ],
                                1 => [
                                    'value'      => \Problem::WAITING,
                                    'searchtype' => 'equals',
                                    'field'      => 12,
                                    'link'       => 'AND',
                                ],
                            ],
                        ]);

                        $output['title'] = __('Problems on pending status');
                        $output['title_url'] = $CFG_GLPI["root_doc"] . "/front/problem.php?" . $options;
                        break;

                    case "process":

                        $options = Toolbox::append_params([
                            'reset'      => 'reset',
                            'criteria'   => [
                                0 => [
                                    'value'      => $_SESSION['glpigroups'],
                                    'searchtype' => 'equals',
                                    'field'      => 8,
                                    'link'       => 'AND',
                                ],
                                1 => [
                                    'value'      => 'process',
                                    'searchtype' => 'equals',
                                    'field'      => 12,
                                    'link'       => 'AND',
                                ],
                            ],
                        ]);

                        $output['title'] = __('Problems to be processed');
                        $output['title_url'] = $CFG_GLPI["root_doc"] . "/front/problem.php?" . $options;
                        break;

                    default:

                        $options = Toolbox::append_params([
                            'reset'      => 'reset',
                            'criteria'   => [
                                0 => [
                                    'value'      => $_SESSION['glpigroups'],
                                    'searchtype' => 'equals',
                                    'field'      => 71,
                                    'link'       => 'AND',
                                ],
                                1 => [
                                    'value'      => 'process',
                                    'searchtype' => 'equals',
                                    'field'      => 12,
                                    'link'       => 'AND',
                                ],
                            ],
                        ]);


                        $output['title'] = __('Your problems in progress');
                        $output['title_url'] = $CFG_GLPI["root_doc"] . "/front/problem.php?" . $options;
                }
            } else {
                switch ($status) {
                    case "waiting":

                        $options = Toolbox::append_params([
                            'reset'      => 'reset',
                            'criteria'   => [
                                0 => [
                                    'value'      => Session::getLoginUserID(),
                                    'searchtype' => 'equals',
                                    'field'      => 5,
                                    'link'       => 'AND',
                                ],
                                1 => [
                                    'value'      => \Problem::WAITING,
                                    'searchtype' => 'equals',
                                    'field'      => 12,
                                ],
                            ],
                        ]);

                        $output['title'] = __('Problems on pending status');
                        $output['title_url'] = $CFG_GLPI["root_doc"] . "/front/problem.php?" . $options;
                        break;

                    case "process":

                        $options = Toolbox::append_params([
                            'reset'      => 'reset',
                            'criteria'   => [
                                0 => [
                                    'value'      => Session::getLoginUserID(),
                                    'searchtype' => 'equals',
                                    'field'      => 5,
                                    'link'       => 'AND',
                                ],
                                1 => [
                                    'value'      => 'process',
                                    'searchtype' => 'equals',
                                    'field'      => 12,
                                ],
                            ],
                        ]);

                        $output['title'] = __('Problems to be processed');
                        $output['title_url'] = $CFG_GLPI["root_doc"] . "/front/problem.php?" . $options;
                        break;

                    default:

                        $options = Toolbox::append_params([
                            'reset'      => 'reset',
                            'criteria'   => [
                                0 => [
                                    'value'      => Session::getLoginUserID(),
                                    'searchtype' => 'equals',
                                    'field'      => 4,
                                    'link'       => 'AND',
                                ],
                                1 => [
                                    'value'      => 'notold',
                                    'searchtype' => 'equals',
                                    'field'      => 12,
                                ],
                            ],
                        ]);


                        $output['title'] = __('Your problems in progress');
                        $output['title_url'] = $CFG_GLPI["root_doc"] . "/front/problem.php?" . $options;
                }
            }

            if ($numrows) {
                $output['header'][] = __('');
                $output['header'][] = __('Requester');
                $output['header'][] = __('Description');
                foreach ($iterator as $data) {
                    $ID = $data["id"];
                    $output['body'][] = self::showVeryShort($ID, $forcetab);
                }
            }
        }

        //We set the datas of the widget (which will be later automatically formatted by the method getJSonData of Datatable)
        if (isset($output['title'])) {
            $widget->setWidgetTitle($output['title']);
            $widget->setWidgetTitleLink($output['title_url'], $numrows, $numrows);
        }
        if (isset($output['header'])) {
            $widget->setTabNames($output['header']);
        }
        if (isset($output['body'])) {
            $widget->setTabDatas($output['body']);
        } else {
            $widget->setTabDatas([]);
        }

        return $widget;
    }

    /**
     * @param        $ID
     * @param string $forcetab
     *
     * @return array
     */
    public static function showVeryShort($ID, $forcetab = '')
    {
        global $CFG_GLPI;

        $output = [];

        $problem = new \Problem();
        if ($problem->getFromDBwithData($ID, 0)) {
            $url = $CFG_GLPI["root_doc"] . "/front/problem.form.php?id=" . $problem->fields["id"];
            if ($forcetab != '') {
                $url .= "&forcetab=" . $forcetab;
            }
            $output[] = Helper::getPriorityIdCell($problem);
            $output[] = Helper::getItilRequestersCell($problem);
            $output[] = Helper::getItemLinkCell($url, $problem->fields["name"], $problem->fields['content']);
        }
        return $output;
    }
    /**
     * @param bool $foruser
     *
     * @return MydashboardHtml
     */
    public static function showCentralCount($foruser = false)
    {
        global $DB, $CFG_GLPI;

        // show a tab with count of jobs in the central and give link
        if (!\Problem::canView()) {
            return false;
        }
        if (!Session::haveRight(\Problem::$rightname, \Problem::READALL)) {
            $foruser = true;
        }

        $criteria = [
            'SELECT' => [
                'status',
                'COUNT' => 'glpi_problems.id AS COUNT',
            ],
            'FROM' => 'glpi_problems',
            'LEFT JOIN' => [],
            'WHERE' => [],
            'GROUPBY' => 'glpi_problems.status',
        ];

        if ($foruser) {
            $criteria['LEFT JOIN'] = $criteria['LEFT JOIN'] + [
                'glpi_problems_users' => [
                    'ON' => [
                        'glpi_problems' => 'id',
                        'glpi_problems_users'          => 'problems_id',
                    ],
                ],
            ];

            if (isset($_SESSION["glpigroups"])
                && count($_SESSION["glpigroups"])
            ) {
                $criteria['LEFT JOIN'] = $criteria['LEFT JOIN'] + [
                    'glpi_groups_problems' => [
                        'ON' => [
                            'glpi_problems' => 'id',
                            'glpi_groups_problems'          => 'problems_id',
                        ],
                    ],
                ];
            }
        }

        $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
            'glpi_problems',
        );

        if ($foruser) {

            $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_problems_users.users_id' =>  Session::getLoginUserID(),
                'glpi_problems_users.type' => CommonITILActor::REQUESTER];

            if (isset($_SESSION["glpigroups"])
                && count($_SESSION["glpigroups"])
            ) {
                $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_groups_problems.groups_id' =>  $_SESSION['glpigroups'],
                    'glpi_groups_problems.type' => CommonITILActor::REQUESTER];

            }
        }
        $criteria_deleted = $criteria;

        $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_problems.is_deleted' =>  0];

        $criteria_deleted['WHERE'] = $criteria_deleted['WHERE'] + ['glpi_problems.is_deleted' =>  1];

        $iterator = $DB->request($criteria);
        $iterator_deleted = $DB->request($criteria_deleted);

        $status = [];
        foreach (\Problem::getAllStatusArray() as $key => $val) {
            $status[$key] = 0;
        }

        if (count($iterator) > 0) {
            foreach ($iterator as $data) {
                $status[$data["status"]] = $data["COUNT"];
            }
        }

        $number_deleted = 0;
        if (count($iterator_deleted) > 0) {
            foreach ($iterator_deleted as $data) {
                $number_deleted += $data["COUNT"];
            }
        }

        $widget = new MydashboardHtml();
        $widget->setWidgetId("problemcountwidget");


        $options = Toolbox::append_params([
            'reset'      => 'reset',
            'criteria'   => [
                0 => [
                    'value'      => 'process',
                    'searchtype' => 'equals',
                    'field'      => 12,
                ],
            ],
        ]);

        $widget->setWidgetTitle(__('Problem followup', 'mydashboard'));
        $widget->setWidgetTitleLink(
            $CFG_GLPI["root_doc"] . "/front/problem.php?reset=reset",
            null,
            null,
            \Problem::getIcon(),
        );

        $twig_params = [
            'title'     => [
                'link'   => $CFG_GLPI["root_doc"] . "/front/problem.php?reset=reset",
                'text'   =>  __('Problem followup', 'mydashboard'),
                'icon'   => \Problem::getIcon(),
            ],
            'items'     => [],
        ];

        foreach ($status as $key => $val) {
            $options = Toolbox::append_params([
                'reset'      => 'reset',
                'criteria'   => [
                    0 => [
                        'value'      => $key,
                        'searchtype' => 'equals',
                        'field'      => 12,
                    ],
                ],
            ]);
            $twig_params['items'][] = [
                'link'   => $CFG_GLPI["root_doc"] . "/front/problem.php?" . $options,
                'text'   => \Problem::getStatus($key),
                'count'  => $val,
            ];
        }

        $options = Toolbox::append_params([
            'reset'      => 'reset',
            'is_deleted' => 1,
            'criteria'   => [
                0 => [
                    'value'      => 'all',
                    'searchtype' => 'equals',
                    'field'      => 12,
                ],
            ],
        ]);
        $twig_params['items'][] = [
            'link'   => $CFG_GLPI["root_doc"] . "/front/problem.php?" . $options,
            'text'   => __('Deleted'),
            'count'  => $number_deleted,
        ];

        $output = TemplateRenderer::getInstance()->render('@mydashboard/itemtype_count.html.twig', $twig_params);

        $widget->toggleWidgetRefresh();
        $widget->setWidgetHtmlContent($output);

        return $widget;

    }
}
