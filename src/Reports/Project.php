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
use Dropdown;
use GlpiPlugin\Mydashboard\Datatable;
use GlpiPlugin\Mydashboard\Helper;
use GlpiPlugin\Mydashboard\Menu;
use GlpiPlugin\Mydashboard\Widget;
use Session;
use Toolbox;

/**
 * This class extends GLPI class project to add the functions to display a widget on Dashboard
 */
class Project extends CommonGLPI
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
        $showproject = Session::haveRightsOr('project', [\Project::READALL, \Project::READMY]);

        if ($showproject) {
            $widgets = [
                Menu::$TOOLS
                    => [
                        "projectprocesswidget" => [
                            "title" => __('Projects to be processed', 'mydashboard'),
                            "type" => Widget::$TABLE,
                            "comment" => "",
                        ],
                    ],
                Menu::$GROUP_VIEW
                    => [
                        "projectprocesswidgetgroup" => [
                            "title" => __('Projects to be processed', 'mydashboard'),
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
        $showproject = Session::haveRightsOr('project', [\Project::READALL, \Project::READMY]);

        if ($showproject) {
            switch ($widgetId) {
                case "projectprocesswidget":
                    return self::showCentralList(0, "process", false);

                case "projectprocesswidgetgroup":
                    return self::showCentralList(0, "process", true);

            }
        }
    }

    /**
     * @param        $start
     * @param string $status
     * @param bool $showgroupprojects
     *
     * @return Datatable
     */
    public static function showCentralList($start, $status = "process", $showgroupprojects = true)
    {
        global $DB, $CFG_GLPI;

        $output = [];
        //We declare our new widget
        $widget = new Datatable();
        if ($status == "process") {
            $widget->setWidgetTitle(__('Projects to be processed', 'mydashboard'));
        }

        $group = ($showgroupprojects) ? "group" : "";
        $widget->setWidgetId("project" . $status . "widget" . $group);
        //Here we set few otions concerning the jquery library Datatable, bPaginate for paginating ...
        $widget->setOption("bPaginate", false);
        $widget->setOption("bFilter", false);
        $widget->setOption("bInfo", false);

        if (!Session::haveRightsOr('project', [\Project::READALL, \Project::READMY])) {
            return false;
        }

        $search_assign = [ 'OR' => [
            ['glpi_projects.users_id' => Session::getLoginUserID()],
            ['glpi_projectteams.items_id' => Session::getLoginUserID(), 'glpi_projectteams.itemtype' => 'User'],
        ],
        ];


        if ($showgroupprojects) {
            $search_assign = [];

            if (count($_SESSION['glpigroups'])) {

                $search_assign = [ 'OR' => [
                    ['glpi_projects.groups_id' => $_SESSION['glpigroups']],
                    ['glpi_projectteams.items_id' => $_SESSION['glpigroups'], 'glpi_projectteams.itemtype' => 'Group'],
                ],
                ];
            }
        }
        $criteria = [
            'SELECT' => 'glpi_projects.id',
            'DISTINCT'        => true,
            'FROM' => 'glpi_projects',
            'LEFT JOIN'       => [
                'glpi_projectteams' => [
                    'ON' => [
                        'glpi_projects' => 'id',
                        'glpi_projectteams'          => 'projects_id',
                    ],
                ],
                'glpi_projectstates' => [
                    'ON' => [
                        'glpi_projects' => 'projectstates_id',
                        'glpi_projectstates'          => 'id',
                    ],
                ],
            ],
            'WHERE' => ['glpi_projects.is_deleted' =>  0],
            'ORDERBY' => 'glpi_projects.date_mod DESC',
        ];

        switch ($status) {
            case "process": // on affiche les projets assignés au user

                $criteria['WHERE'] = $criteria['WHERE'] + $search_assign;

                $criteria['WHERE'] = $criteria['WHERE'] + [ 'OR' => [
                    ['glpi_projectstates.is_finished' => 0],
                    ['glpi_projects.projectstates_id' => 0],
                ],
                ];

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                    'glpi_projects',
                );
                break;
        }

        $iterator = $DB->request($criteria);
        $numrows = count($iterator);

        if ($numrows > 0) {
            $options['reset'] = 'reset';
            $forcetab = '';
            $num = 0;
            if ($showgroupprojects) {
                switch ($status) {
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
                                ],
                            ],
                        ]);

                        $output['title'] = __('Projects to be processed', 'mydashboard');
                        $output['title_url'] = $CFG_GLPI["root_doc"] . "/front/project.php?" . $options;
                        break;
                }
            } else {
                switch ($status) {
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

                        $output['title'] = __('Projects to be processed', 'mydashboard');
                        $output['title_url'] = $CFG_GLPI["root_doc"] . "/front/project.php?" . $options;
                        break;
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

        $project = new \Project();
        if ($project->getFromDB($ID)) {
            $url = $CFG_GLPI["root_doc"] . "/front/project.form.php?id=" . $project->fields["id"];
            if ($forcetab != '') {
                $url .= "&forcetab=" . $forcetab;
            }

            $managers = [];
            if (($project->fields["users_id"] ?? 0) > 0) {
                $managers[] = ['kind' => 'text', 'value' => (string) getUserName($project->fields["users_id"]), 'bold' => true];
            }
            if (($project->fields["groups_id"] ?? 0) != 0) {
                $managers[] = ['kind' => 'text', 'value' => (string) Dropdown::getDropdownName("glpi_groups", $project->fields["groups_id"])];
            }

            $output[] = Helper::getPriorityIdCell($project);
            $output[] = ['kind' => 'lines', 'items' => $managers];
            $output[] = Helper::getItemLinkCell($url, $project->fields["name"], $project->fields['content']);
        }
        return $output;
    }
}
