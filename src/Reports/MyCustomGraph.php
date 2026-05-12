<?php

/*
 -------------------------------------------------------------------------
 MyDashboard plugin for GLPI
 Copyright (C) 2015-2026 by the MyDashboard Development Team.
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
use GlpiPlugin\Mydashboard\Charts\BarChart;
use GlpiPlugin\Mydashboard\Criteria;
use GlpiPlugin\Mydashboard\Criterias\Type;
use GlpiPlugin\Mydashboard\Helper;
use GlpiPlugin\Mydashboard\Html as MydashboardHtml;
use GlpiPlugin\Mydashboard\Menu;
use GlpiPlugin\Mydashboard\Preference as MydashboardPreference;
use GlpiPlugin\Mydashboard\Widget;
use Session;
use Ticket;
use Toolbox;

class MyCustomGraph extends CommonGLPI {

    public static $reports = [1000];

    public $widget = [];

    public function __construct()
    {
        parent::__construct();
        self::includePropLocales('mycustomgraph');
    }

    private static function includePropLocales(string $report_name): void
    {
        global $LANG;

        $prefix = PLUGIN_MYDASHBOARD_DIR . "/locales/" . $report_name . "/" . $report_name;

        if (isset($_SESSION["glpilanguage"])
            && file_exists($prefix . "." . $_SESSION["glpilanguage"] . ".php")) {
            include_once($prefix . "." . $_SESSION["glpilanguage"] . ".php");
        } elseif (file_exists($prefix . ".en_GB.php")) {
            include_once($prefix . ".en_GB.php");
        }
    }

    public function getWidgetsForItem()
    {
        global $LANG;

        $widgets = [
            Menu::$HELPDESK => [
                $this->getType() . "1000" => [
                    "title"   => $LANG['plugin_mydashboard']['mycustomgraph']['title_new_request_vs_closed'] ?? '',
                    "type"    => Widget::$BAR,
                    "comment" => $LANG['plugin_mydashboard']['mycustomgraph']['comment_new_request_vs_closed'] ?? '',
                ],
            ],
        ];

        return $widgets;
    }

    public function getTitleForWidget(string $widgetID): string|false
    {
        foreach ($this->getWidgetsForItem() as $list) {
            foreach ($list as $name => $widget) {
                if ($widgetID == $name) {
                    return $widget['title'];
                }
            }
        }
        return false;
    }

    public function getCommentForWidget(string $widgetID): string|false
    {
        foreach ($this->getWidgetsForItem() as $list) {
            foreach ($list as $name => $widget) {
                if ($widgetID == $name) {
                    return $widget['comment'];
                }
            }
        }
        return false;
    }

    public function getWidgetContentForItem($widgetId, $opt = []): MydashboardHtml|false
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

        if ($widgetId === $this->getType() . "1000") {
            $name = "infosTicketsNewRequestVsClosed";

            $criterias = Criteria::getDefaultCriterias();

            $onclick = 0;

            if (Session::getCurrentInterface() == 'central') {
                $onclick = 0;
                $criterias = array_merge($criterias, [Type::$criteria_name]);
            }

            $params = [
                "preferences" => $preferences,
                "criterias"   => $criterias,
                "opt"         => $opt,
            ];

            $default = Criteria::manageCriterias($params);

            $conditions = [
                'glpi_tickets.is_deleted' => 0,
                'glpi_tickets.status'     => Ticket::getNotSolvedStatusArray(),
            ];

                $criteria = [
                    'SELECT' => [
                        'COUNT' => 'glpi_tickets.id AS count_nb_tickets',
                        'glpi_tickets.status as status',
                    ],
                    'FROM' => 'glpi_tickets',
                    'WHERE' => $conditions,
                    'GROUPBY' => [
                        'glpi_tickets.status',
                    ],
                    'ORDERBY' => [
                        'glpi_tickets.status'
                    ]
                ];

                $criteria = Criteria::addCriteriasForQuery($criteria, $params);

                $iterator = $DB->request($criteria);

                $values = [];
                $labelsLine = [];
                if (count($iterator) > 0) {
                    foreach ($iterator as $item) {
                        $labelsLine[] = Ticket::getStatus($item['status']);
                        $values[] = (int) $item['count_nb_tickets'];
                    }
                }

                $dataset = [
                    [
                        "name"     => __("Tickets"),
                        "data"     => $values,
                        "type"     => "bar",
                        "label"    => ['show' => true],
                        "emphasis" => ['focus' => 'series'],
                    ],
                ];

                $widget = new MydashboardHtml();
                $title = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetTitle((($isDebug) ? "1000 " : "") . $title);
                $widget->setWidgetComment($comment);
                $widget->toggleWidgetRefresh();

                $dataLineset = json_encode($dataset);

                $graph_datas = [
                    'title' => $title,
                    'comment' => $comment,
                    'name' => $name,
                    'ids' => json_encode([]),
                    'data' => $dataLineset,
                    'labels' => json_encode($labelsLine),
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
                    "nb" => count($dataset)
                ];

                $widget->setWidgetHeader(Helper::getGraphHeader($params));
                $widget->setWidgetHtmlContent($graph);

                return $widget;
        }

        return false;
    }

    public static function pluginMydashboardReports_Bar1000link(array $options): string
    {
        global $CFG_GLPI;

        $options_selected = Criteria::addUrlCriteria(Criteria::STATUS, 'equals', 'notold', 'AND');
        $options['criteria'] = array_merge($options['params']['criteria'], $options_selected['criteria']);

        return $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&'
            . Toolbox::append_params($options);
    }
}

