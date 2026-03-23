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
use CommonITILActor;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QueryFunction;
use GlpiPlugin\Mydashboard\Datatable;
use GlpiPlugin\Mydashboard\Html as MydashboardHtml;
use GlpiPlugin\Mydashboard\Menu;
use GlpiPlugin\Mydashboard\Widget;
use Html;
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
        $showproblem = Session::haveRightsOr('problem', [\Problem::READALL, \Problem::READMY]);

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
        $showproblem = Session::haveRightsOr('problem', [\Problem::READALL, \Problem::READMY]);

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
            $widget->setWidgetTitle(Html::makeTitle(__('Problems on pending status'), 0, 0));
        } else {
            $widget->setWidgetTitle(Html::makeTitle(__('Problems to be processed'), 0, 0));
        }
        $group = ($showgroupproblems) ? "group" : "";
        $widget->setWidgetId("problem" . $status . "widget" . $group);
        //Here we set few otions concerning the jquery library Datatable, bPaginate for paginating ...
        $widget->setOption("bPaginate", false);
        $widget->setOption("bFilter", false);
        $widget->setOption("bInfo", false);

        if (!Session::haveRightsOr('problem', [\Problem::READALL, \Problem::READMY])) {
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
                    'glpi_problems'
                );
                break;

            case "process": // on affiche les problemes planifiés ou assignés au user

                $criteria['WHERE'] = $criteria['WHERE'] + $search_assign;

                $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_problems.status' =>  [\Problem::PLANNED, \Problem::ASSIGNED]];

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                    'glpi_problems'
                );

                break;

            default:

                $criteria['WHERE'] = $criteria['WHERE'] + $search_users_id;

                $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_problems.status' =>  [\Problem::INCOMING, \Problem::ACCEPTED, \Problem::PLANNED,  \Problem::ASSIGNED,   \Problem::WAITING]];

                $criteria['WHERE'] = $criteria['WHERE'] + ['solvedate' => ['>', QueryFunction::dateSub(
                    date: QueryFunction::now(),
                    interval: '30',
                    interval_unit: 'DAY'
                )]];

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                    'glpi_problems'
                );
        }


        $iterator = $DB->request($criteria);
        $numrows = count($iterator);

        if ($numrows > 0) {
            $output['title'] = "";
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

                        $output['title'] .= "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/problem.php?"
                            . $options . "\">"
                            . Html::makeTitle(__('Problems on pending status'), $numrows, $numrows) . "</a>";
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

                        $output['title'] .= "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/problem.php?"
                            . $options . "\">"
                            . Html::makeTitle(__('Problems to be processed'), $numrows, $numrows) . "</a>";
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


                        $output['title'] .= "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/problem.php?"
                            . $options . "\">"
                            . Html::makeTitle(__('Your problems in progress'), $numrows, $numrows) . "</a>";
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

                        $output['title'] .= "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/problem.php?"
                            . $options . "\">"
                            . Html::makeTitle(__('Problems on pending status'), $numrows, $numrows) . "</a>";
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

                        $output['title'] .= "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/problem.php?"
                            . $options . "\">"
                            . Html::makeTitle(__('Problems to be processed'), $numrows, $numrows) . "</a>";
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


                        $output['title'] .= "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/problem.php?"
                            . $options . "\">"
                            . Html::makeTitle(__('Your problems in progress'), $numrows, $numrows) . "</a>";
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

        $colnum = 0;
        $output = [];

        // Prints a job in short form
        // Should be called in a <table>-segment
        // Print links or not in case of user view
        // Make new job object and fill it from database, if success, print it
        $viewusers = Session::haveRight("user", READ);

        $problem = new \Problem();
        $rand = mt_rand();

        if ($problem->getFromDBwithData($ID, 0)) {
            $bgcolor = $_SESSION["glpipriority_" . $problem->fields["priority"]];
            //      $rand    = mt_rand();
            $output[$colnum] = "<div class='center' style='background-color:$bgcolor; padding: 10px;'>" . sprintf(
                __('%1$s: %2$s'),
                __('ID'),
                $problem->fields["id"]
            ) . "</div>";
            $colnum++;

            $output[$colnum] = '';
            $userrequesters = $problem->getUsers(CommonITILActor::REQUESTER);
            if (isset($userrequesters)
                && count($userrequesters)
            ) {
                foreach ($userrequesters as $d) {
                    if ($d["users_id"] > 0) {
                        $userdata = getUserName($d["users_id"]);
                        $name = "<div class='b center'>" . $userdata;
                        $output[$colnum] .= $name . "</div>";
                    } else {
                        $output[$colnum] .= $d['alternative_email'] . "&nbsp;";
                    }
                    //$output[$colnum] .=  "<br>";
                }
            }
            $grouprequester = $problem->getGroups(CommonITILActor::REQUESTER);
            if (isset($grouprequester)
                && count($grouprequester)
            ) {
                foreach ($grouprequester as $d) {
                    $output[$colnum] .= Dropdown::getDropdownName("glpi_groups", $d["groups_id"]);
                }
            }

            $colnum++;
            //$output[$colnum] = '';
            $link = "<a id='problem" . $problem->fields["id"] . $rand . "' href='" . $CFG_GLPI["root_doc"]
                . "/front/problem.form.php?id=" . $problem->fields["id"];
            if ($forcetab != '') {
                $link .= "&amp;forcetab=" . $forcetab;
            }
            // echo "###########".$problem->fields["name"];
            $link .= "'>";
            $link .= "<span class='b'>" . $problem->fields["name"] . "</span></a>";

            $link = sprintf(
                __('%1$s %2$s'),
                $link,
                Html::showToolTip(
                    $problem->fields['content'],
                    [
                        'applyto' => 'problem' . $problem->fields["id"] . $rand,
                        'display' => false,
                    ]
                )
            );
            //echo $link;
            //$colnum++;
            $output[$colnum] = $link;
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
                'COUNT' => 'id AS COUNT',
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
            'glpi_problems'
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

        $icon = "<i class='".\Problem::getIcon()."'></i>";
        $widget->setWidgetTitle(
            $icon." <a href=\"" . $CFG_GLPI["root_doc"] . "/front/problem.php?reset=reset\">"
            .  __('Problem followup', 'mydashboard') . "</a>"
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
