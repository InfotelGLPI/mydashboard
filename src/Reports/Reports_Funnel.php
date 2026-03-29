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
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryUnion;
use GlpiPlugin\Mydashboard\Chart;
use GlpiPlugin\Mydashboard\Charts\FunnelChart;
use GlpiPlugin\Mydashboard\Criteria;
use GlpiPlugin\Mydashboard\Criterias\ComputerType;
use GlpiPlugin\Mydashboard\Criterias\Entity;
use GlpiPlugin\Mydashboard\Helper;
use GlpiPlugin\Mydashboard\Html;
use GlpiPlugin\Mydashboard\Menu;
use GlpiPlugin\Mydashboard\Preference as MydashboardPreference;
use GlpiPlugin\Mydashboard\Widget;
use Session;
use Toolbox;

/**
 * Class Reports_Funnel
 */
class Reports_Funnel extends CommonGLPI
{
    private $options;
    private $pref;
    public static $reports = [1];

    /**
     * Reports_Funnel constructor.
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
            Menu::$INVENTORY
                => [
                    $this->getType() . "1" => [
                        "title" => __("Age pyramid", "mydashboard"),
                        "type" => Widget::$OTHERS,
                        "icon" => "ti ti-triangle",
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
            case $this->getType() . "1":
                $name = 'AgePyramid';
                $onclick = 0;
                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() == 'central') {
                    $criterias = [
                        Entity::$criteria_name,
                        'is_recursive_entities',
                        ComputerType::$criteria_name,
                    ];
                    $onclick = 1;
                }
                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() != 'central') {
                    $criterias = [
                        ComputerType::$criteria_name,
                    ];
                }

                $params = [
                    "preferences" => $preferences,
                    "criterias" => $criterias,
                    "opt" => $opt,
                ];

                $default = Criteria::manageCriterias($params);

                $is_deleted = ['glpi_computers.is_deleted' => 0];

                $params['entities_id'] = $opt['entities_id'] ?? $default['entities_id'];
                $params['sons'] = $opt['is_recursive_entities'] ?? $default['is_recursive_entities'];

                $type = $opt['computertypes_id'] ?? $default['computertypes_id'];

                $criteria1 = [
                    'SELECT' => [
                        new QueryExpression("CONCAT('Undefined') Age"),
                        new QueryExpression("CONCAT('other') AgeCrit"),
                        'COUNT' => 'glpi_computers.id AS Total',
                    ],
                    'FROM' => 'glpi_computers',
                    'LEFT JOIN'       => [
                        'glpi_infocoms' => [
                            'ON' => [
                                'glpi_computers' => 'id',
                                'glpi_infocoms'  => 'items_id', [
                                    'AND' => [
                                        'glpi_infocoms.itemtype' => 'Computer',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'WHERE' => [
                        $is_deleted,
                        'glpi_computers.is_template' => 0,
                        'glpi_infocoms.buy_date' => 'NULL',
                    ],
                ];
                $criteria1 = Criteria::addCriteriasForQuery($criteria1, $params, \Computer::getTable());

                $queries[] = $criteria1;

                $criteria2 = [
                    'SELECT' => [
                        new QueryExpression("CONCAT('> 6 years') Age"),
                        new QueryExpression("CONCAT('6-6') AgeCrit"),
                        'COUNT' => 'glpi_computers.id AS Total',
                    ],
                    'FROM' => 'glpi_computers',
                    'LEFT JOIN'       => [
                        'glpi_infocoms' => [
                            'ON' => [
                                'glpi_computers' => 'id',
                                'glpi_infocoms'  => 'items_id', [
                                    'AND' => [
                                        'glpi_infocoms.itemtype' => 'Computer',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'WHERE' => [
                        $is_deleted,
                        'glpi_computers.is_template' => 0,
                        'glpi_infocoms.buy_date' => ['<', new QueryExpression("CURRENT_DATE - INTERVAL 6 YEAR")],
                    ],
                ];
                $criteria2 = Criteria::addCriteriasForQuery($criteria2, $params, \Computer::getTable());

                $queries[] = $criteria2;

                $criteria3 = [
                    'SELECT' => [
                        new QueryExpression("CONCAT('4-6 years') Age"),
                        new QueryExpression("CONCAT('4-6') AgeCrit"),
                        'COUNT' => 'glpi_computers.id AS Total',
                    ],
                    'FROM' => 'glpi_computers',
                    'LEFT JOIN'       => [
                        'glpi_infocoms' => [
                            'ON' => [
                                'glpi_computers' => 'id',
                                'glpi_infocoms'  => 'items_id', [
                                    'AND' => [
                                        'glpi_infocoms.itemtype' => 'Computer',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'WHERE' => [
                        $is_deleted,
                        'glpi_computers.is_template' => 0,
                        [
                            ['glpi_infocoms.buy_date' => ['<=', new QueryExpression("CURRENT_DATE - INTERVAL 4 YEAR")]],
                            ['glpi_infocoms.buy_date' => ['>', new QueryExpression("CURRENT_DATE - INTERVAL 6 YEAR")]],
                        ],
                    ],
                ];

                $criteria3 = Criteria::addCriteriasForQuery($criteria3, $params, \Computer::getTable());

                $queries[] = $criteria3;

                $criteria4 = [
                    'SELECT' => [
                        new QueryExpression("CONCAT('2-4 years') Age"),
                        new QueryExpression("CONCAT('2-4') AgeCrit"),
                        'COUNT' => 'glpi_computers.id AS Total',
                    ],
                    'FROM' => 'glpi_computers',
                    'LEFT JOIN'       => [
                        'glpi_infocoms' => [
                            'ON' => [
                                'glpi_computers' => 'id',
                                'glpi_infocoms'  => 'items_id', [
                                    'AND' => [
                                        'glpi_infocoms.itemtype' => 'Computer',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'WHERE' => [
                        $is_deleted,
                        'glpi_computers.is_template' => 0,
                        [
                            ['glpi_infocoms.buy_date' => ['<=', new QueryExpression("CURRENT_DATE - INTERVAL 2 YEAR")]],
                            ['glpi_infocoms.buy_date' => ['>', new QueryExpression("CURRENT_DATE - INTERVAL 4 YEAR")]],
                        ],
                    ],
                ];

                $criteria4 = Criteria::addCriteriasForQuery($criteria4, $params, \Computer::getTable());

                $queries[] = $criteria4;

                $criteria5 = [
                    'SELECT' => [
                        new QueryExpression("CONCAT('< 2 years') Age"),
                        new QueryExpression("CONCAT('2-2') AgeCrit"),
                        'COUNT' => 'glpi_computers.id AS Total',
                    ],
                    'FROM' => 'glpi_computers',
                    'LEFT JOIN'       => [
                        'glpi_infocoms' => [
                            'ON' => [
                                'glpi_computers' => 'id',
                                'glpi_infocoms'  => 'items_id', [
                                    'AND' => [
                                        'glpi_infocoms.itemtype' => 'Computer',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'WHERE' => [
                        $is_deleted,
                        'glpi_computers.is_template' => 0,
                        'glpi_infocoms.buy_date' => ['<', new QueryExpression("CURRENT_DATE - INTERVAL 2 YEAR")],
                    ],
                ];

                $criteria5 = Criteria::addCriteriasForQuery($criteria5, $params, \Computer::getTable());

                $queries[] = $criteria5;

                $union = new QueryUnion($queries, true);
                $criteria_final = [
                    'SELECT' => [],
                    'FROM'   => $union,
                ];
                $iterator = $DB->request($criteria_final);
                $tabage = [];
                $tabnames = [];

                $ages = [
                    __('Without buy date', 'mydashboard'),
                    __('> 6 years', 'mydashboard'),
                    __('4-6 years', 'mydashboard'),
                    __('2-4 years', 'mydashboard'),
                    __('< 2 years', 'mydashboard'),
                ];
                $i = 0;
                foreach ($iterator as $data) {
                    $tabnames[] = $ages[$i];
                    $tabdate[] = $data['AgeCrit'];

                    if ($i == 0) {
                        $tabage[] = [
                            'value' => $data['Total'],
                            'name' => $ages[$i],
                            'itemStyle' => ['color' => '#CCC'],
                        ];
                    } elseif ($i == 1) {
                        $tabage[] = [
                            'value' => $data['Total'],
                            'name' => $ages[$i],
                            'itemStyle' => ['color' => '#E19494FF'],
                        ];
                    } elseif ($i == 2) {
                        $tabage[] = [
                            'value' => $data['Total'],
                            'name' => $ages[$i],
                            'itemStyle' => ['color' => '#EAAC4EFF'],
                        ];
                    } elseif ($i == 3) {
                        $tabage[] = [
                            'value' => $data['Total'],
                            'name' => $ages[$i],
                            'itemStyle' => ['color' => '#599CD0FF'],
                        ];
                    } elseif ($i == 4) {
                        $tabage[] = [
                            'value' => $data['Total'],
                            'name' => $ages[$i],
                            'itemStyle' => ['color' => '#9EB778FF'],
                        ];
                    }
                    $i++;
                }

                $widget = new Html();
                $dataLineset = json_encode($tabage);
                $dataDateset = json_encode($tabdate);
                $labelsLine = json_encode($tabnames);

                $title = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetTitle((($isDebug) ? "35 " : "") . $title);
                $widget->setWidgetComment($comment);
                $widget->toggleWidgetRefresh();

                $graph_datas = [
                    'title' => $title,
                    'comment' => $comment,
                    'name' => $name,
                    'ids' => $dataDateset,
                    'data' => $dataLineset,
                    'labels' => $labelsLine,
                ];

                $graph_criterias = [];
                if ($onclick == 1) {
                    $criterias_values = Criteria::getGraphCriterias($params);
                    $graph_criterias = array_merge(['widget' => $widgetId], $criterias_values);
                }
                $graph = FunnelChart::launchFunnelGraph($graph_datas, $graph_criterias);
                $widget->setWidgetHtmlContent($graph);

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


            default:
                break;
        }
    }

    /**
     * @param $params ['selected_id']
     *
     * @return string
     */
    public static function pluginMydashboardReports_Funnel1link($params)
    {
        global $CFG_GLPI;

        $options['reset'][] = 'reset';

        if (isset($params['selected_id'])) {
            if ($params['selected_id'] == "2-2") {
                $options = Criteria::addUrlCriteria(Criteria::BUY_DATE, 'lessthan', '-2YEAR', 'AND');
            } elseif ($params['selected_id'] == "2-4") {
                $options = Criteria::addUrlCriteria(Criteria::BUY_DATE, 'lessthan', '-2YEAR', 'AND');
                $options = Criteria::addUrlCriteria(Criteria::BUY_DATE, 'morethan', '-4YEAR', 'AND');
            } elseif ($params['selected_id'] == "4-6") {
                $options = Criteria::addUrlCriteria(Criteria::BUY_DATE, 'lessthan', '-4YEAR', 'AND');
                $options = Criteria::addUrlCriteria(Criteria::BUY_DATE, 'morethan', '-6YEAR', 'AND');
            } elseif ($params['selected_id'] == "6-6") {
                $options = Criteria::addUrlCriteria(Criteria::BUY_DATE, 'lessthan', '-6YEAR', 'AND');
            } elseif ($params['selected_id'] == "other") {
                $options = Criteria::addUrlCriteria(Criteria::BUY_DATE, 'contains', 'NULL', 'AND');
            }
        }
        if ($params["params"][ComputerType::$criteria_name] > 0) {
            $options = ComputerType::getSearchCriteria($params);
        }

        return $CFG_GLPI["root_doc"] . '/front/computer.php?is_deleted=0&'
            . Toolbox::append_params($options, "&");
    }
}
