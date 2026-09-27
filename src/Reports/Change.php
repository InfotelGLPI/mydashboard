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
use GlpiPlugin\Mydashboard\Menu;
use GlpiPlugin\Mydashboard\Widget;
use Html;
use GlpiPlugin\Mydashboard\Html as MydashboardHtml;
use Session;
use Toolbox;

/**
 * This class extends GLPI class change to add the functions to display a widget on Dashboard
 */
class Change extends CommonGLPI
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
        $showchange = Session::haveRightsOr('change', [\Change::READALL, \Change::READMY]);

        if ($showchange) {
            $widgets = [
                Menu::$HELPDESK
                    => [
                        "changeprocesswidget" => [
                            "title" => __('Changes to be processed', 'mydashboard'),
                            "type" => Widget::$TABLE,
                            "comment" => "",
                        ],
                        "changewaitingwidget" => [
                            "title" => __('Changes on pending status', 'mydashboard'),
                            "type" => Widget::$TABLE,
                            "comment" => "",
                        ],
                        "changeappliedwidget" => [
                            "title" => __('Applied changes', 'mydashboard'),
                            "type" => Widget::$TABLE,
                            "comment" => "",
                        ],
                        "changecountwidget" => [
                            "title" => __('Change followup', 'mydashboard'),
                            "type" => Widget::$TABLE,
                            "comment" => "",
                        ],
                    ],
                Menu::$GROUP_VIEW
                    => [
                        "changeprocesswidgetgroup" => [
                            "title" => __('Changes to be processed', 'mydashboard'),
                            "type" => Widget::$TABLE,
                            "comment" => "",
                        ],
                        "changewaitingwidgetgroup" => [
                            "title" => __('Changes on pending status', 'mydashboard'),
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
        $showchange = Session::haveRightsOr('change', [\Change::READALL, \Change::READMY]);

        if ($showchange) {
            switch ($widgetId) {
                case "changeprocesswidget":
                    return self::showCentralList(0, "process", false);
                    break;
                case "changeappliedwidget":
                    return self::showCentralList(0, "applied", false);
                    break;
                case "changeprocesswidgetgroup":
                    return self::showCentralList(0, "process", true);
                    break;
                case "changewaitingwidget":
                    return self::showCentralList(0, "waiting", false);
                    break;
                case "changewaitingwidgetgroup":
                    return self::showCentralList(0, "waiting", true);
                    break;
                case "changecountwidget":
                    return self::showCentralCount();
                    break;
            }
        }
    }

    /**
     * @param        $start
     * @param string $status
     * @param bool $showgroupchanges
     *
     * @return Datatable
     */
    public static function showCentralList($start, $status = "process", $showgroupchanges = true)
    {
        global $DB, $CFG_GLPI;

        $output = [];
        //We declare our new widget
        $widget = new Datatable();
        if ($status == "waiting") {
            $widget->setWidgetTitle(__('Changes on pending status', 'mydashboard'));
        } else {
            $widget->setWidgetTitle(__('Changes to be processed', 'mydashboard'));
        }
        $group = ($showgroupchanges) ? "group" : "";
        $widget->setWidgetId("change" . $status . "widget" . $group);
        //Here we set few otions concerning the jquery library Datatable, bPaginate for paginating ...
        $widget->setOption("bPaginate", false);
        $widget->setOption("bFilter", false);
        $widget->setOption("bInfo", false);

        if (!Session::haveRightsOr('change', [\Change::READALL, \Change::READMY])) {
            return false;
        }

        $search_users_id = ['glpi_changes_users.users_id' =>  Session::getLoginUserID(),
            'glpi_changes_users.type' => CommonITILActor::REQUESTER];

        $search_assign = ['glpi_changes_users.users_id' =>  Session::getLoginUserID(),
            'glpi_changes_users.type' => CommonITILActor::ASSIGN];

        if ($showgroupchanges) {
            $search_users_id = [];
            $search_assign = [];

            if (count($_SESSION['glpigroups'])) {

                $search_assign = ['glpi_changes_groups.groups_id' =>  $_SESSION['glpigroups'],
                    'glpi_changes_groups.type' => CommonITILActor::ASSIGN];

                $search_users_id = ['glpi_changes_groups.groups_id' =>  $_SESSION['glpigroups'],
                    'glpi_changes_groups.type' => CommonITILActor::REQUESTER];
            }
        }

        $criteria = [
            'SELECT' => 'glpi_changes.id',
            'DISTINCT'        => true,
            'FROM' => 'glpi_changes',
            'LEFT JOIN'       => [
                'glpi_changes_users' => [
                    'ON' => [
                        'glpi_changes' => 'id',
                        'glpi_changes_users'          => 'changes_id',
                    ],
                ],
                'glpi_changes_groups' => [
                    'ON' => [
                        'glpi_changes' => 'id',
                        'glpi_changes_groups'          => 'changes_id',
                    ],
                ],
            ],
            'WHERE' => ['glpi_changes.is_deleted' =>  0],
            'ORDERBY' => 'glpi_changes.date_mod DESC',
        ];

        switch ($status) {
            case "waiting": // on affiche les changements en attente

                $criteria['WHERE'] = $criteria['WHERE'] + $search_assign;

                $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_changes.status' =>  \Change::WAITING];

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                    'glpi_changes',
                );
                break;

            case "process": // on affiche les changements planifiés ou assignés au user

                $criteria['WHERE'] = $criteria['WHERE'] + $search_assign;

                $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_changes.status' =>  \Change::getProcessStatusArray()];

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                    'glpi_changes',
                );
                break;

            case "applied": // on affiche les changements qui vont être mis en production

                $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_changes.status' =>  \Change::getSolvedStatusArray()];

                $criteria['WHERE'] = $criteria['WHERE'] + ['solvedate' => ['>', QueryFunction::dateSub(
                    date: QueryFunction::now(),
                    interval: '30',
                    interval_unit: 'DAY',
                )]];

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                    'glpi_changes',
                );
                break;

            default:

                $criteria['WHERE'] = $criteria['WHERE'] + $search_users_id;

                $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_changes.status' =>  [\Change::getProcessStatusArray(), \Change::getNewStatusArray(), \Change::WAITING]];

                $criteria['WHERE'] = $criteria['WHERE'] + ['solvedate' => ['>', QueryFunction::dateSub(
                    date: QueryFunction::now(),
                    interval: '30',
                    interval_unit: 'DAY',
                )]];

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                    'glpi_changes',
                );
        }

        $iterator = $DB->request($criteria);
        $numrows = count($iterator);


        if ($numrows > 0) {
            $options['reset'] = 'reset';
            $forcetab = '';
            $num = 0;
            if ($showgroupchanges) {
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
                                    'value'      => \Change::WAITING,
                                    'searchtype' => 'equals',
                                    'field'      => 12,
                                    'link'       => 'AND',
                                ],
                            ],
                        ]);
                        $output['title'] = __('Changes on pending status', 'mydashboard');
                        $output['title_url'] = $CFG_GLPI["root_doc"] . "/front/change.php?" . $options;
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
                        $output['title'] = __('Changes to be processed', 'mydashboard');
                        $output['title_url'] = $CFG_GLPI["root_doc"] . "/front/change.php?" . $options;
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
                        $output['title'] = __('Your changes in progress');
                        $output['title_url'] = $CFG_GLPI["root_doc"] . "/front/change.php?" . $options;
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
                                    'value'      => \Change::WAITING,
                                    'searchtype' => 'equals',
                                    'field'      => 12,
                                ],
                            ],
                        ]);

                        $output['title'] = __('Changes on pending status', 'mydashboard');
                        $output['title_url'] = $CFG_GLPI["root_doc"] . "/front/change.php?" . $options;
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

                        $output['title'] = __('Changes to be processed', 'mydashboard');
                        $output['title_url'] = $CFG_GLPI["root_doc"] . "/front/change.php?" . $options;
                        break;

                    case "applied":
                        $options = Toolbox::append_params([
                            'reset'      => 'reset',
                            'criteria'   => [
                                0 => [
                                    'value'      => 'solved',
                                    'searchtype' => 'equals',
                                    'field'      => 12,
                                ],
                            ],
                        ]);

                        $output['title'] = __('Applied changes', 'mydashboard');
                        $output['title_url'] = $CFG_GLPI["root_doc"] . "/front/change.php?" . $options;
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

                        $output['title'] = __('Your changes in progress');
                        $output['title_url'] = $CFG_GLPI["root_doc"] . "/front/change.php?" . $options;
                }
            }

            if ($numrows) {
                $output['header'][] = __('ID');
                $output['header'][] = __('Requester');
                $output['header'][] = __('Description');
                $output['header'][] = __('Date of solving');
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

        $change = new \Change();
        if ($change->getFromDBwithData($ID, 0)) {
            $url = $CFG_GLPI["root_doc"] . "/front/change.form.php?id=" . $change->fields["id"];
            if ($forcetab != '') {
                $url .= "&forcetab=" . $forcetab;
            }
            $output[] = Helper::getPriorityIdCell($change);
            $output[] = Helper::getItilRequestersCell($change);
            $output[] = Helper::getItemLinkCell($url, $change->fields["name"], $change->fields['content']);
            $output[] = ['kind' => 'text', 'value' => (string) Html::convDateTime($change->fields['solvedate'])];
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
        if (!\Change::canView()) {
            return false;
        }
        if (!Session::haveRight(\Change::$rightname, \Change::READALL)) {
            $foruser = true;
        }

        $criteria = [
            'SELECT' => [
                'status',
                'COUNT' => 'glpi_changes.id AS COUNT',
            ],
            'FROM' => 'glpi_changes',
            'LEFT JOIN' => [],
            'WHERE' => [],
            'GROUPBY' => 'glpi_changes.status',
        ];

        if ($foruser) {
            $criteria['LEFT JOIN'] = $criteria['LEFT JOIN'] + [
                'glpi_changes_users' => [
                    'ON' => [
                        'glpi_changes' => 'id',
                        'glpi_changes_users'          => 'changes_id',
                    ],
                ],
            ];

            if (isset($_SESSION["glpigroups"])
                && count($_SESSION["glpigroups"])
            ) {
                $criteria['LEFT JOIN'] = $criteria['LEFT JOIN'] + [
                    'glpi_changes_groups' => [
                        'ON' => [
                            'glpi_changes' => 'id',
                            'glpi_changes_groups'          => 'changes_id',
                        ],
                    ],
                ];
            }
        }

        $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
            'glpi_changes',
        );

        if ($foruser) {

            $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_changes_users.users_id' =>  Session::getLoginUserID(),
                'glpi_changes_users.type' => CommonITILActor::REQUESTER];

            if (isset($_SESSION["glpigroups"])
                && count($_SESSION["glpigroups"])
            ) {
                $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_changes_groups.groups_id' =>  $_SESSION['glpigroups'],
                    'glpi_changes_groups.type' => CommonITILActor::REQUESTER];

            }
        }
        $criteria_deleted = $criteria;

        $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_changes.is_deleted' =>  0];

        $criteria_deleted['WHERE'] = $criteria_deleted['WHERE'] + ['glpi_changes.is_deleted' =>  1];

        $iterator = $DB->request($criteria);
        $iterator_deleted = $DB->request($criteria_deleted);


        $status = [];
        foreach (\Change::getAllStatusArray() as $key => $val) {
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
        $widget->setWidgetId("changecountwidget");
        $widget->setWidgetTitle(__('Change followup', 'mydashboard'));
        $widget->setWidgetTitleLink(
            $CFG_GLPI["root_doc"] . "/front/change.php?reset=reset",
            null,
            null,
            \Change::getIcon(),
        );

        $twig_params = [
            'title'     => [
                'link'   => $CFG_GLPI["root_doc"] . "/front/change.php?reset=reset",
                'text'   =>  __('Change followup', 'mydashboard'),
                'icon'   => \Change::getIcon(),
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
                'link'   => $CFG_GLPI["root_doc"] . "/front/change.php?" . $options,
                'text'   => \Change::getStatus($key),
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
            'link'   => $CFG_GLPI["root_doc"] . "/front/change.php?" . $options,
            'text'   => __('Deleted'),
            'count'  => $number_deleted,
        ];

        $output = TemplateRenderer::getInstance()->render('@mydashboard/itemtype_count.html.twig', $twig_params);

        $widget->toggleWidgetRefresh();
        $widget->setWidgetHtmlContent($output);

        return $widget;
    }
}
