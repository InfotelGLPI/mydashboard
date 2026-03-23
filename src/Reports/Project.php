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
use Dropdown;
use GlpiPlugin\Mydashboard\Datatable;
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
            $widget->setWidgetTitle(\Html::makeTitle(__('Projects to be processed', 'mydashboard'), 0, 0));
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
                    'glpi_projects'
                );
                break;
        }

        $iterator = $DB->request($criteria);
        $numrows = count($iterator);

        if ($numrows > 0) {
            $output['title'] = "";
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

                        $output['title'] .= "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/project.php?"
                           . $options . "\">"
                            . \Html::makeTitle(__('Projects to be processed', 'mydashboard'), $numrows, $numrows) . "</a>";
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

                        $output['title'] .= "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/project.php?"
                           . $options . "\">"
                            . \Html::makeTitle(__('Projects to be processed', 'mydashboard'), $numrows, $numrows) . "</a>";
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

        $project = new \Project();
        $rand = mt_rand();
        if ($project->getFromDB($ID)) {
            $bgcolor = $_SESSION["glpipriority_" . $project->fields["priority"]];
            //      $rand    = mt_rand();
            $output[$colnum] = "<div class='center' style='background-color:$bgcolor; padding: 10px;'>" . sprintf(
                __('%1$s: %2$s'),
                __('ID'),
                $project->fields["id"]
            ) . "</div>";
            $colnum++;

            $output[$colnum] = '';
            $projectsFields = $project->fields;
            if (isset($projectsFields["users_id"])) {
                if ($projectsFields["users_id"] > 0) {
                    $userdata = getUserName($projectsFields["users_id"]);
                    $name = "<div class='b center'>" . $userdata;
                    $output[$colnum] .= $name . "</div>";
                }
            }

            if (isset($projectsFields["groups_id"])
                && $projectsFields["groups_id"] != 0
            ) {
                $output[$colnum] .= Dropdown::getDropdownName("glpi_groups", $projectsFields["groups_id"]);
            }

            $colnum++;

            $link = "<a id='project" . $project->fields["id"] . $rand . "' href='" . $CFG_GLPI["root_doc"]
                . "/front/project.form.php?id=" . $project->fields["id"];
            if ($forcetab != '') {
                $link .= "&amp;forcetab=" . $forcetab;
            }

            $link .= "'>";
            $link .= "<span class='b'>" . $project->fields["name"] . "</span></a>";

            $link = sprintf(
                __('%1$s %2$s'),
                $link,
                \Html::showToolTip(
                    $project->fields['content'],
                    [
                        'applyto' => 'project' . $project->fields["id"] . $rand,
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
}
