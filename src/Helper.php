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

namespace GlpiPlugin\Mydashboard;

use CommonDBTM;
use CommonITILActor;
use CommonITILObject;
use DbUtils;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use Glpi\RichText\RichText;
use GlpiPlugin\Mydashboard\Charts\HBarChart;
use GlpiPlugin\Mydashboard\Charts\LineChart;
use GlpiPlugin\Mydashboard\Charts\PieChart;
use GlpiPlugin\Mydashboard\Charts\VBarChart;
use GlpiPlugin\Mydashboard\Html as MyDashboardHtml;
use Html;
use Session;

/**
 * This helper class provides some static functions that are useful for widget class
 */
class Helper
{
    /**
     * Typed cell of an item ID (ITIL object, project), coloured with the priority colour of the user.
     *
     * @param CommonDBTM $item
     * @param ?string    $url
     *
     * @return array
     */
    public static function getPriorityIdCell(CommonDBTM $item, $url = null)
    {
        return [
            'kind' => 'badge',
            'label' => sprintf(__('%1$s: %2$s'), __('ID'), $item->fields['id']),
            'color' => $_SESSION['glpipriority_' . $item->fields['priority']] ?? null,
            'url' => $url,
        ];
    }

    /**
     * Typed cell listing the requesters (users, anonymous emails, groups) of an ITIL object.
     *
     * @param CommonITILObject $item
     *
     * @return array
     */
    public static function getItilRequestersCell(CommonITILObject $item)
    {
        $lines = [];
        foreach ($item->getUsers(CommonITILActor::REQUESTER) as $d) {
            if ($d['users_id'] > 0) {
                $lines[] = ['kind' => 'text', 'value' => (string) getUserName($d['users_id']), 'bold' => true];
            } else {
                $lines[] = ['kind' => 'text', 'value' => (string) $d['alternative_email']];
            }
        }
        foreach ($item->getGroups(CommonITILActor::REQUESTER) as $d) {
            $lines[] = ['kind' => 'text', 'value' => (string) Dropdown::getDropdownName('glpi_groups', $d['groups_id'])];
        }
        return ['kind' => 'lines', 'items' => $lines];
    }

    /**
     * Typed cell linking to an item, its rich text content being shown as a plain text tooltip.
     *
     * @param string $url
     * @param string $label
     * @param string $content rich text
     * @param string $suffix
     *
     * @return array
     */
    public static function getItemLinkCell($url, $label, $content = '', $suffix = '')
    {
        return [
            'kind' => 'link',
            'url' => $url,
            'label' => (string) $label,
            'bold' => true,
            'tooltip' => RichText::getTextFromHtml((string) $content, false, true),
            'suffix' => $suffix,
        ];
    }

    public static function getGraphHeader($params)
    {

        $criteria_html = '';
        if (count($params["criterias"]) > 0) {
            $criteria_html = Criteria::getForm(
                $params["widgetId"],
                $params["default"] ?? [],
                $params["opt"],
                $params["criterias"],
                $params["onsubmit"],
            );
        }

        return TemplateRenderer::getInstance()->render('@mydashboard/graph_header.html.twig', [
            'name' => $params['name'],
            'export' => $params["export"] == true,
            'canvas' => $params["canvas"] == true,
            'no_results' => $params["nb"] < 1,
            'criteria_html' => $criteria_html,
        ]);
    }


    /**
     * @param $params
     *
     * @return string
     */
    public static function getGraphFooter($params)
    {
        $setup_url = null;
        if (isset($params["setup"]) && Session::haveRightsOr(StockWidget::$rightname, [CREATE, UPDATE])) {
            $setup_url = $params["setup"];
        }

        return TemplateRenderer::getInstance()->render('@mydashboard/graph_footer.html.twig', [
            'setup_url' => $setup_url,
        ]);
    }


    /**
     * Get an array of scripts found in a string
     *
     * @param string $stringToEval , a HTML string with potentially script tags
     *
     * @return array of string
     */
    public static function extractScriptsFromString($stringToEval)
    {
        $scripts = [];
        if (gettype($stringToEval) == "string") {
            if (preg_match_all("/<script[^>]*>([\s\S]+?)<\/script>/i", $stringToEval, $matches)) {
                foreach ($matches[1] as $match) {
                    $scripts[] = $match;
                }
            }
        }
        return $scripts;
    }

    /**
     * Get a string without scripts from stringToEval,
     * it strips script tags
     *
     * @param string $stringToEval , the string that you want without scripts
     *
     * @return string with no scripts
     */
    public static function removeScriptsFromString($stringToEval)
    {
        if (gettype($stringToEval) === "string") {
            return preg_replace('#<script(.*?)>(.*?)</script>#is', '', $stringToEval);
        }
        return $stringToEval;
    }


    /**
     * @param $widgettype
     * @param $query
     *
     * @return Datatable|HBarChart|Html|LineChart|PieChart|VBarChart
     */
    public static function getWidgetsFromDBQuery(
        $widgettype,
        $query/*$widgettype,$table,$fields,$condition,$groupby,$orderby*/
    ) {
        global $DB;

        $widget = null;

        if (is_array($query)) {

            $tab = [];
            if ($iterator = $DB->request($query)) {
                foreach ($iterator as $row) {
                    $tab[] = $row;
                }

                $linechart = false;
                $chart = false;
                switch ($widgettype) {
                    case 'datatable':
                    case 'table':
                        $widget = new Datatable();
                        break;
                    case 'hbarchart':
                        $chart = true;
                        $widget = new HBarChart();
                        break;
                    case 'vbarchart':
                        $chart = true;
                        $widget = new VBarChart();
                        break;
                    case 'piechart':
                        $chart = true;
                        $widget = new PieChart();
                        break;
                    case 'linechart':
                        $linechart = true;
                        $widget = new LineChart();
                        break;
                }
                //            $widget = new HBarChart();
                //        $widget->setTabNames(array('Category','Count'));
                if ($chart) {
                    $newtab = [];
                    foreach ($tab as $key => $line) {
                        $line = array_values($line);
                        $newtab[$line[0]] = $line[1];
                        unset($tab[$key]);
                    }
                    $tab = $newtab;
                } elseif ($linechart) {
                    //TODO format for linechart
                } else {
                    //$widget->setTabNames(array('Category','Count'));
                }

                $widget->setTabDatas($tab);
            }
        } else {
            // Only structured query arrays are accepted here. Executing a raw SQL
            // string (formerly via $DB->doQuery()) is refused to avoid a raw-SQL
            // primitive: all queries must go through $DB->request() with the array
            // builder above.
            $widget = new MyDashboardHtml();
            $widget->debugError(__('Not a valid SQL SELECT query', 'mydashboard'));
        }

        return $widget;
    }


    /**
     * @param       $prefered_group
     * @param       $opt
     * @param false $params
     * @param       $entity
     * @param       $userid
     *
     * @return array|mixed
     */
    public static function getRequesterGroup($prefered_group, $opt, $entity, $userid, $params = false)
    {
        global $DB;

        $dbu = new DbUtils();

        $query = [
            'SELECT' => ['glpi_groups.id'],
            'FROM' => 'glpi_groups_users',
            'INNER JOIN' => [
                'glpi_groups' => [
                    'FKEY' => [
                        'glpi_groups' => 'id',
                        'glpi_groups_users' => 'groups_id',
                    ],
                ],
            ],
            'WHERE' => [
                'users_id' => $userid,
                $dbu->getEntitiesRestrictCriteria('glpi_groups', '', $entity, true),
                '`is_requester`',
            ],
        ];

        $rep = [];
        foreach ($DB->request($query) as $data) {
            $rep[] = $data['id'];
        }

        $res = [];
        if (!$params) {
            if (isset($prefered_group)
                && !empty($prefered_group)
                && count($opt) <= 1) {
                $res = json_decode($prefered_group, true);
            } elseif (isset($opt['requesters_groups_id'])) {
                $res = (is_array(
                    $opt['requesters_groups_id'],
                ) ? $opt['requesters_groups_id'] : []);
            } else {
                $res = $rep;
            }
        } else {
            if (isset($params['preferences']['requester_prefered_group'])
                && !empty($params['preferences']['requester_prefered_group'])
                && !isset($params['opt']['requesters_groups_id'])) {
                $res = json_decode($params['preferences']['requester_prefered_group'], true);
            } elseif (isset($params['opt']['requesters_groups_id'])
                && count($params['opt']['requesters_groups_id']) > 0) {
                $res = json_decode($params['opt']['requesters_groups_id'], true);
            }
        }
        return $res;
    }

    /**
     * @param      $prefered_group
     * @param      $opt
     * @param bool $params
     *
     * @return array|mixed
     */
    public static function getGroup($prefered_group, $opt, $params = [])
    {
        $groupprofiles = new Groupprofile();
        $res = [];
        if (!$params) {
            if (isset($prefered_group)
                && !empty($prefered_group)
                && count($opt) <= 1) {
                if ($group = $groupprofiles->getProfilGroup($_SESSION['glpiactiveprofile']['id'])) {
                    $res = json_decode($group, true);
                } else {
                    $res = json_decode($prefered_group, true);
                }
            } elseif ($group = $groupprofiles->getProfilGroup($_SESSION['glpiactiveprofile']['id'])
                && count($opt) < 1) {
                $res = json_decode($group, true);
            } elseif (isset($opt['technicians_groups_id'])) {
                $res = (is_array(
                    $opt['technicians_groups_id'],
                ) ? $opt['technicians_groups_id'] : []);
            } else {
                $res = [];
            }
        } else {
            if (isset($params['preferences']['prefered_group'])
                && !empty($params['preferences']['prefered_group'])
                && !isset($params['opt']['technicians_groups_id'])) {
                if ($group = $groupprofiles->getProfilGroup($_SESSION['glpiactiveprofile']['id'])) {
                    $res = json_decode($group, true);
                } else {
                    $res = json_decode($params['preferences']['prefered_group'], true);
                }
            } elseif (isset($params['opt']['technicians_groups_id'])
                && count($params['opt']['technicians_groups_id']) > 0) {
                $res = json_decode($params['opt']['technicians_groups_id'], true);
            } elseif (($group = $groupprofiles->getProfilGroup($_SESSION['glpiactiveprofile']['id']))
                && !isset($params['opt']['technicians_groups_id'])) {
                $res = json_decode($group, true);
            }
        }
        return $res;
    }

}
