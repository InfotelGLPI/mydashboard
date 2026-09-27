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

use GlpiPlugin\Mydashboard\Helper;
use CommonGLPI;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use Glpi\RichText\RichText;
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
     * @return false|Datatable
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
        return false;
    }

    /**
     * @param        $start
     * @param string $status
     * @param bool $showgroupprojecttasks
     *
     * @return false|Datatable
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
                    'glpi_projecttaskteams.itemtype' => 'User',
                ],
            ],
        ];

        if ($showgroupprojecttasks) {
            if (count($_SESSION['glpigroups'])) {
                $search_assign = [
                    'glpi_projecttaskteams.items_id' => $_SESSION['glpigroups'],
                    'glpi_projecttaskteams.itemtype' => 'Group',
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
                    'glpi_projecttasks',
                );
                break;
        }

        $iterator = $DB->request($criteria);
        $numrows = count($iterator);

        $widget = new Datatable();
        $widget->setWidgetId($widgetId);
        $widget->setWidgetTitle(__('Projects tasks to be processed', 'mydashboard'));

        if ($status === "process") {
            $criterion = $showgroupprojecttasks
                ? ['value' => $_SESSION['glpigroups'], 'field' => 8]
                : ['value' => Session::getLoginUserID(), 'field' => 5];
            $options = Toolbox::append_params([
                'reset' => 'reset',
                'criteria' => [
                    0 => [
                        'value' => $criterion['value'],
                        'searchtype' => 'equals',
                        'field' => $criterion['field'],
                        'link' => 'AND',
                    ],
                    1 => [
                        'value' => 'process',
                        'searchtype' => 'equals',
                        'field' => 12,
                    ],
                ],
            ]);
            $widget->setWidgetTitleLink(
                $CFG_GLPI["root_doc"] . "/front/projecttask.php?" . $options,
                $numrows,
                $numrows,
                \ProjectTask::getIcon(),
            );
        }

        $rows = [];
        foreach ($iterator as $data) {
            $projecttask = new \ProjectTask();
            if (!$projecttask->getFromDB($data['id'])) {
                continue;
            }

            $id_cell = ['kind' => 'text', 'value' => (string) $data['id']];
            $project = new \Project();
            if (
                !empty($projecttask->fields["projects_id"])
                && $project->getFromDB($projecttask->fields["projects_id"])
            ) {
                $id_cell = [
                    'kind' => 'badge',
                    'label' => (string) $data['id'],
                    'color' => $_SESSION["glpipriority_" . $project->fields["priority"]] ?? null,
                ];
            }

            $name = $projecttask->fields[\ProjectTask::getNameField()];
            if (
                $_SESSION["glpiis_ids_visible"]
                || empty($name)
            ) {
                $name = sprintf(__('%1$s (%2$s)'), $name, $data["id"]);
            }

            $requesters = [];
            if (($projecttask->fields["users_id"] ?? 0) > 0) {
                $requesters[] = ['kind' => 'text', 'value' => getUserName($projecttask->fields["users_id"])];
            }
            if (($projecttask->fields["groups_id"] ?? 0) > 0) {
                $requesters[] = [
                    'kind' => 'text',
                    'value' => Dropdown::getDropdownName("glpi_groups", $projecttask->fields["groups_id"]),
                ];
            }

            $rows[] = [
                $id_cell,
                ['kind' => 'lines', 'items' => $requesters],
                [
                    'kind' => 'link',
                    'url' => \ProjectTask::getFormURLWithID($data['id']),
                    'label' => (string) $name,
                ],
            ];
        }

        $widget->setTabNames([__('ID'), __('Requester'), __('Name')]);
        $widget->setTabDatas($rows);
        $widget->toggleWidgetRefresh();

        return $widget;
    }
}
