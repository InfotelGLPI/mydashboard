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
use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Mydashboard\Datatable;
use GlpiPlugin\Mydashboard\Html as MydashboardHtml;
use GlpiPlugin\Mydashboard\Menu;
use GlpiPlugin\Mydashboard\Widget;
use Html;
use Session;
use Toolbox;

/**
 * This class extends GLPI class project to add the functions to display a widget on Dashboard
 */
class ProjectTask extends CommonGLPI
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
        $showprojecttask = Session::haveRight('projecttask', \ProjectTask::READMY);

        if ($showprojecttask) {
            $widgets = [
                Menu::$TOOLS
                => [
                    "projecttaskprocesswidget" => [
                        "title" => __('Projects tasks to be processed', 'mydashboard'),
                        "type" => Widget::$TABLE,
                        "comment" => "",
                    ],
                ],
                Menu::$GROUP_VIEW
                => [
                    "projecttaskprocesswidgetgroup" => [
                        "title" => __('Projects tasks to be processed', 'mydashboard'),
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
     * @return false|MydashboardHtml
     */
    public function getWidgetContentForItem($widgetId)
    {
        $showprojecttask = Session::haveRightsOr('projecttask', [\ProjectTask::READMY]);

        if ($showprojecttask) {
            switch ($widgetId) {
                case "projecttaskprocesswidget":
                    return self::showCentralList($widgetId, 0, "process", false);
                    break;
                case "projecttaskprocesswidgetgroup":
                    return self::showCentralList($widgetId, 0, "process", true);
                    break;
            }
        }
    }

    /**
     * @param        $start
     * @param string $status
     * @param bool $showgroupprojecttasks
     *
     * @return MydashboardHtml
     */
    public static function showCentralList($widgetId, $start, $status = "process", $showgroupprojecttasks = true)
    {
        global $DB, $CFG_GLPI;

        $output = [];

        if (!Session::haveRightsOr('projecttask', [\ProjectTask::READMY])) {
            return false;
        }


        $search_assign = [
            'OR' => [
                ['glpi_projecttasks.users_id' => Session::getLoginUserID()],
                [
                    'glpi_projecttaskteams.items_id' => Session::getLoginUserID(),
                    'glpi_projecttaskteams.itemtype' => 'User'
                ],
            ],
        ];

        if ($showgroupprojecttasks) {
            if (count($_SESSION['glpigroups'])) {
                $search_assign = [
                    'glpi_projecttaskteams.items_id' => $_SESSION['glpigroups'],
                    'glpi_projecttaskteams.itemtype' => 'Group'
                ];
            }
        }
        $criteria = [
            'SELECT' => 'glpi_projecttasks.id',
            'DISTINCT' => true,
            'FROM' => 'glpi_projecttasks',
            'LEFT JOIN' => [
                'glpi_projecttaskteams' => [
                    'ON' => [
                        'glpi_projecttasks' => 'id',
                        'glpi_projecttaskteams' => 'projecttasks_id',
                    ],
                ],
                'glpi_projectstates' => [
                    'ON' => [
                        'glpi_projecttasks' => 'projectstates_id',
                        'glpi_projectstates' => 'id',
                    ],
                ],
            ],
            'WHERE' => [],
            'ORDERBY' => 'glpi_projecttasks.date_mod DESC',
        ];

        switch ($status) {
            case "process": // on affiche les projets assignés au user

                $criteria['WHERE'] = $criteria['WHERE'] + $search_assign;

                $criteria['WHERE'] = $criteria['WHERE'] + [
                        'OR' => [
                            ['glpi_projectstates.is_finished' => 0],
                            ['glpi_projecttasks.projectstates_id' => 0],
                        ],
                    ];

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                        'glpi_projecttasks'
                    );
                break;
        }

        $iterator = $DB->request($criteria);
        $numrows = count($iterator);

        $widget = new MydashboardHtml();
        $widget->setWidgetId($widgetId);
        $entries = [];

        if ($numrows > 0) {
            $output['title'] = "";
            $options['reset'] = 'reset';
            $forcetab = '';
            $num = 0;
            if ($showgroupprojecttasks) {
                switch ($status) {
                    case "process":
                        $options = Toolbox::append_params([
                            'reset' => 'reset',
                            'criteria' => [
                                0 => [
                                    'value' => $_SESSION['glpigroups'],
                                    'searchtype' => 'equals',
                                    'field' => 8,
                                    'link' => 'AND',
                                ],
                                1 => [
                                    'value' => 'process',
                                    'searchtype' => 'equals',
                                    'field' => 12,
                                ],
                            ],
                        ]);
//                        $output['title'] .= "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/projecttask.php?"
//                            . $options . "\">"
//                            . Html::makeTitle(
//                                __('Projects tasks to be processed', 'mydashboard'),
//                                $numrows,
//                                $numrows
//                            ) . "</a>";

                        $icon = "<i class='" . \ProjectTask::getIcon() . "'></i>";
                        $widgetTitle = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/projecttask.php?"
                            . $options . "\">"
                            . Html::makeTitle(
                                __('Projects tasks to be processed', 'mydashboard'),
                                $numrows,
                                $numrows
                            ) . "</a>";


                        break;
                }
            } else {
                switch ($status) {
                    case "process":
                        $options = Toolbox::append_params([
                            'reset' => 'reset',
                            'criteria' => [
                                0 => [
                                    'value' => Session::getLoginUserID(),
                                    'searchtype' => 'equals',
                                    'field' => 5,
                                    'link' => 'AND',
                                ],
                                1 => [
                                    'value' => 'process',
                                    'searchtype' => 'equals',
                                    'field' => 12,
                                ],
                            ],
                        ]);

//                        $output['title'] .= "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/projecttask.php?"
//                            . $options . "\">"
//                            . Html::makeTitle(
//                                __('Projects tasks to be processed', 'mydashboard'),
//                                $numrows,
//                                $numrows
//                            ) . "</a>";

                        $icon = "<i class='" . \ProjectTask::getIcon() . "'></i>";
                        $widgetTitle = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/projecttask.php?"
                            . $options . "\">"
                            . Html::makeTitle(
                                __('Projects tasks to be processed', 'mydashboard'),
                                $numrows,
                                $numrows
                            ) . "</a>";


                        break;
                }
            }

            $widget->setWidgetTitle(
                $icon . " " . $widgetTitle
            );

            if ($numrows) {

                foreach ($iterator as $data) {
                    $ID = $data["id"];
//                    $values = self::showVeryShort($ID, $forcetab);

                    $projecttask = new \ProjectTask();

                    if ($projecttask->getFromDB($ID)) {

                        $status = $data['id'];
                        if (!empty($projecttask->fields["projects_id"])) {
                            $project = new \Project();
                            $project->getFromDB($projecttask->fields["projects_id"]);
                            $bgcolor = $_SESSION["glpipriority_" . $project->fields["priority"]];

                            $status_badge_style = "background-color:{$bgcolor};;";
                            $status = '<span class="badge" style="' . htmlescape($status_badge_style) . '">' . htmlescape($data['id']) . '</span>';
                        }

                        $name = $projecttask->fields[\ProjectTask::getNameField()];
                        if (
                            $_SESSION["glpiis_ids_visible"]
                            || empty($projecttask->fields[\ProjectTask::getNameField()])
                        ) {
                            $name = sprintf(__('%1$s (%2$s)'), $name, $data["id"]);
                        }
                        $link     = $projecttask::getFormURLWithID($data['id']);
                        $namelink = "<a href=\"" . htmlescape($link) . "\">" . htmlescape($name) . "</a>";

                        $requesters = "";
                        if (isset($projecttask->fields["users_id"])) {
                            if ($projecttask->fields["users_id"] > 0) {
                                $requesters .= getUserName($projecttask->fields["users_id"]);
                            }
                        }

                        if (isset($projecttask->fields["groups_id"])
                            && $projecttask->fields["groups_id"] != 0
                        ) {
                            $requesters .= Dropdown::getDropdownName("glpi_groups", $projecttask->fields["groups_id"]);
                        }

                        $entries[] = [
                            'itemtype' => \ProjectTask::class,
                            'id' => $status,
                            'requester' =>$requesters,
                            'name' => $namelink,
                        ];
                    }
                }
            }
        }


        $add_link = '';

        $columns = [
            'id' => __('ID'),
        ];
        $columns += [
            'requester' => __('Requester'),
            'name' => __('Name'),
        ];
        $formatters = [
            'id' => 'raw_html',
            'name' => 'raw_html',
        ];
        $footers = [];

        $output = TemplateRenderer::getInstance()->render('@mydashboard/table.html.twig', [
            'title' => __('Description'),
            'add_link' => $add_link,
            'datatable_params' => [
                'is_tab' => true,
                'nofilter' => true,
                'nosort' => true,
                'columns' => $columns,
                'formatters' => $formatters,
                'entries' => $entries,
                'footers' => $footers,
                'total_number' => count($entries),
                'filtered_number' => count($entries),
                'showmassiveactions' => false,
            ],
        ]);

        $widget->toggleWidgetRefresh();
        $widget->setWidgetHtmlContent($output);

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

        $projecttask = new \ProjectTask();
        $rand = mt_rand();

        if ($projecttask->getFromDB($ID)) {
            $project = new \Project();
            $project->getFromDB($projecttask->fields["projects_id"]);
            $bgcolor = $_SESSION["glpipriority_" . $project->fields["priority"]];
            //      $rand    = mt_rand();
            $output[$colnum] = "<div class='center' style='background-color:$bgcolor; padding: 10px;'>" . sprintf(
                    __('%1$s: %2$s'),
                    __('ID'),
                    $projecttask->fields["id"]
                ) . "</div>";
            $colnum++;

            $output[$colnum] = '';
            $projecttasksFields = $projecttask->fields;
            if (isset($projecttasksFields["users_id"])) {
                if ($projecttasksFields["users_id"] > 0) {
                    $userdata = getUserName($projecttasksFields["users_id"]);
                    $name = "<div class='b center'>" . $userdata;
                    $output[$colnum] .= $name . "</div>";
                }
            }

            if (isset($projecttasksFields["groups_id"])
                && $projecttasksFields["groups_id"] != 0
            ) {
                $output[$colnum] .= Dropdown::getDropdownName("glpi_groups", $projecttasksFields["groups_id"]);
            }

            $colnum++;

            $link = "<a id='projecttask" . $projecttask->fields["id"] . $rand . "' href='" . $CFG_GLPI["root_doc"]
                . "/front/projecttask.form.php?id=" . $projecttask->fields["id"];
            if ($forcetab != '') {
                $link .= "&amp;forcetab=" . $forcetab;
            }

            $link .= "'>";
            $link .= "<span class='b'>" . $projecttask->fields["name"] . "</span></a>";

            $link = sprintf(
                __('%1$s %2$s'),
                $link,
                Html::showToolTip(
                    $projecttask->fields['content'],
                    [
                        'applyto' => 'projecttask' . $projecttask->fields["id"] . $rand,
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
