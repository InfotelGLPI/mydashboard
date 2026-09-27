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
use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Mydashboard\Criteria;
use GlpiPlugin\Mydashboard\Helper;
use GlpiPlugin\Mydashboard\Menu;
use GlpiPlugin\Mydashboard\Widget;
use GlpiPlugin\Mydashboard\Preference as MydashboardPreference;
use GlpiPlugin\Mydashboard\Html;
use Search;
use Session;
use Toolbox;

/**
 * Class Reports_Map
 */
class Reports_Map extends CommonGLPI
{
    private $options;
    private $pref;
    public static $reports = [29];

    /**
     * Reports_Map constructor.
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
        $widgets[Menu::$HELPDESK] = [
            $this->getType() . "29" => [
                "title" => __("OpenStreetMap - Opened tickets by location", "mydashboard"),
                "type" => Widget::$MAP,
                "comment" => __("Display Tickets by location (Latitude / Longitude)", "mydashboard"),
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
     * @return Html|false
     */
    public function getWidgetContentForItem($widgetId, $opt = [])
    {
        $isDebug = $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE;

        $preference = new MydashboardPreference();
        if (Session::getLoginUserID() !== false
            && !$preference->getFromDB(Session::getLoginUserID())) {
            $preference->initPreferences(Session::getLoginUserID());
        }
        $preference->getFromDB(Session::getLoginUserID());
        $preferences = $preference->fields;

        switch ($widgetId) {
            case $this->getType() . "29":

                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() == 'central') {
                    $criterias = [
                        'entities_id',
                        'is_recursive_entities',
                        'type',
                        'technicians_groups_id',
                        'is_recursive_technicians',
                    ];
                }
                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() != 'central') {
                    $criterias = ['type'];
                }

                $paramsc = [
                    "preferences" => $preferences,
                    "criterias" => $criterias,
                    "opt" => $opt,
                ];
                $default = Criteria::manageCriterias($paramsc);

                $type = $opt['type'] ?? $default['type'];
                $entities_id_criteria = $opt['entities_id'] ?? $default['entities_id'];
                $sons_criteria = $opt['is_recursive_entities'] ?? $default['is_recursive_entities'];
                $groups_criteria = $opt['technicians_groups_id'] ?? $default['technicians_groups_id'];

                $widget = new Html();
                $title = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetTitle((($isDebug) ? "29 " : "") . $title);
                $widget->setWidgetComment($comment);
                $widget->toggleWidgetRefresh();

                $params['as_map'] = 1;
                $params['is_deleted'] = 0;
                $params['order'] = 'DESC';
                $params['sort'] = 19;
                $params['start'] = 0;
                $params['list_limit'] = 5000;
                $itemtype = 'Ticket';

                if (isset($sons_criteria) && $sons_criteria > 0) {
                    $params['criteria'][] = [
                        'field' => 80,
                        'searchtype' => 'under',
                        'value' => $entities_id_criteria,
                    ];
                } else {
                    $params['criteria'][] = [
                        'field' => 80,
                        'searchtype' => 'equals',
                        'value' => $entities_id_criteria,
                    ];
                }
                $params['criteria'][] = [
                    'link' => 'AND',
                    'field' => 12,
                    'searchtype' => 'equals',
                    'value' => 'notold',
                ];
                $params['criteria'][] = [
                    'link' => 'AND NOT',
                    'field' => 998,
                    'searchtype' => 'contains',
                    'value' => 'NULL',
                ];
                $params['criteria'][] = [
                    'link' => 'AND NOT',
                    'field' => 999,
                    'searchtype' => 'contains',
                    'value' => 'NULL',
                ];

                if ($type > 0) {
                    $params['criteria'][] = [
                        'link' => 'AND',
                        'field' => 14,
                        'searchtype' => 'equals',
                        'value' => $type,
                    ];
                }
                $grp_criteria = is_array($groups_criteria) ? $groups_criteria : [$groups_criteria];
                if (is_array($grp_criteria) && count($grp_criteria) > 0) {
                    $options['criteria'][7]['link'] = 'AND';
                    $nb = 0;
                    foreach ($grp_criteria as $group) {
                        if ($nb == 0) {
                            $options['criteria'][7]['criteria'][$nb]['link'] = 'AND';
                        } else {
                            $options['criteria'][7]['criteria'][$nb]['link'] = 'OR';
                        }
                        $options['criteria'][7]['criteria'][$nb]['field'] = 8;
                        $options['criteria'][7]['criteria'][$nb]['searchtype'] = 'equals';
                        $options['criteria'][7]['criteria'][$nb]['value'] = $group;
                        $nb++;
                    }
                }

                //            if ($groups_criteria > 0) {
                //               $params['criteria'][] = [
                //                  'link'       => 'AND',
                //                  'field'      => 8,
                //                  'searchtype' => 'equals',
                //                  'value'      => $groups_criteria
                //               ];
                //            }
                $data = Search::prepareDatasForSearch('Ticket', $params);
                Search::constructSQL($data);
                Search::constructData($data);

                $paramsh = [
                    "widgetId" => $widgetId,
                    "name" => 'TicketsByLocationOpenStreetMap',
                    "onsubmit" => false,
                    "opt" => $opt,
                    "default" => $default,
                    "criterias" => $criterias,
                    "export" => false,
                    "canvas" => false,
                    "nb" => 1,
                ];
                $graph = Helper::getGraphHeader($paramsh);
                $map_config = null;

                if ($data['data']['totalcount'] > 0) {
                    $target = $data['search']['target'];
                    $criteria = $data['search']['criteria'];

                    $criteria[] = [
                        'link' => 'AND',
                        'field' => 83,
                        'searchtype' => 'equals',
                        'value' => 'CURLOCATION',
                    ];
                    $globallinkto = Toolbox::append_params(
                        [
                            'criteria' => $criteria,
                            'metacriteria' => $data['search']['metacriteria'],
                        ],
                    );
                    $parameters = "as_map=0&" . $globallinkto;

                    $typename = $itemtype::getTypeName(2);

                    if (strpos($target, '?') == false) {
                        $fulltarget = $target . "?" . $parameters;
                    } else {
                        $fulltarget = $target . "&" . $parameters;
                    }
                    // Drawn by public/scripts/tickets-map.js; the labels are inserted as text
                    $map_config = [
                        'url' => PLUGIN_MYDASHBOARD_WEBDIR . '/ajax/map.php',
                        'itemtype' => $itemtype,
                        'params' => $params,
                        'target' => $fulltarget,
                        'count_label' => sprintf(__('%1$s %2$s'), 'COUNT', $typename),
                        'type_label' => sprintf(__('%1$s %2$s'), 'COUNT', 'TYPE'),
                        'error_label' => __('An error occured loading data :('),
                        'reload_label' => __('Reload'),
                    ];
                }
                $graph .= TemplateRenderer::getInstance()->render('@mydashboard/report_map.html.twig', [
                    'id' => 'TicketsByLocationOpenStreetMap',
                    'config' => $map_config,
                ]);
                $widget->toggleWidgetRefresh();
                $widget->setWidgetHtmlContent(
                    $graph,
                );

                return $widget;

            default:
                break;
        }
    }
}
