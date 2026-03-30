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

namespace GlpiPlugin\Mydashboard;

use Ajax;
use CommonITILActor;
use CommonITILObject;
use DbUtils;
use Dropdown;
use Glpi\DBAL\QueryExpression;
use GlpiPlugin\Mydashboard\Charts\HBarChart;
use GlpiPlugin\Mydashboard\Charts\LineChart;
use GlpiPlugin\Mydashboard\Charts\PieChart;
use GlpiPlugin\Mydashboard\Charts\VBarChart;
use GlpiPlugin\Mydashboard\Criterias\ComputerType;
use GlpiPlugin\Mydashboard\Criterias\DisplayData;
use GlpiPlugin\Mydashboard\Criterias\Entity;
use GlpiPlugin\Mydashboard\Criterias\FilterDate;
use GlpiPlugin\Mydashboard\Criterias\ITILCategory;
use GlpiPlugin\Mydashboard\Criterias\Limit;
use GlpiPlugin\Mydashboard\Criterias\Location;
use GlpiPlugin\Mydashboard\Criterias\Month;
use GlpiPlugin\Mydashboard\Criterias\MultipleLocation;
use GlpiPlugin\Mydashboard\Criterias\RequesterGroup;
use GlpiPlugin\Mydashboard\Criterias\Technician;
use GlpiPlugin\Mydashboard\Criterias\TechnicianGroup;
use GlpiPlugin\Mydashboard\Criterias\Type;
use GlpiPlugin\Mydashboard\Criterias\Year;
use GlpiPlugin\Mydashboard\Html as MyDashboardHtml;
use Group;
use Group_User;
use Html;
use PluginTagTag;
use Session;
use Ticket;
use Toolbox;
use User;

/**
 * This helper class provides some static functions that are useful for widget class
 */
class Helper
{
    /**
     * @param $params
     *
     * @return string
     */
    public static function getGraphHeader($params)
    {

        $name = $params['name'];
        $graph = "<div class='bt-row'>";
        if ($params["export"] == true) {
            $graph .= "<div class='bt-col-md-8 left'>";
        } else {
            $graph .= "<div class='bt-col-md-12 left'>";
        }
        if (count($params["criterias"]) > 0) {
            $graph .= Criteria::getForm($params["widgetId"], $params["default"] ?? [], $params["opt"], $params["criterias"], $params["onsubmit"]);
        }
        $graph .= "</div>";
        //        if ($params["export"] == true) {
        //            $graph .= "<div class='bt-col-md-2 center'>";
        //            $graph .= "<button class='submit btn btn-primary btn-sm' onclick='downloadGraph(\"$name\");'>PNG</button>";
        //            $graph .= "<button class='submit btn btn-primary btn-sm' style=\"margin-left: 1px;\" id=\"downloadCSV$name\">CSV</button>";
        //            $graph .= "<script>
        //         $(document).ready(
        //               function () {
        //                document.getElementById(\"downloadCSV$name\").addEventListener(\"click\", function(){
        //                    downloadCSV({ filename: \"chart-data.csv\", chart: $name })
        //                  });
        //
        //                   function convertChartDataToCSV(args,labels, nbIterations) {
        //
        //                       var result, ctr, keys, columnDelimiter, lineDelimiter, data;
        //
        //                       data = args.data.data || null;
        //                       if (data == null || !data.length) {
        //                         return null;
        //                       }
        //
        //                       columnDelimiter = args.columnDelimiter || \";\";
        //                       lineDelimiter = args.lineDelimiter || '\\n';
        //                       result = '';
        //                       if(nbIterations == 0){
        //
        //                          labels.forEach(function(label) {
        //                            result += columnDelimiter;
        //                            result += label;
        //                          });
        //                       }
        //                       keys = Object.keys(data);
        //                       result += lineDelimiter;
        //                       result += args.data.label;
        //                       result += columnDelimiter;
        //                       data.forEach(function(item) {
        //                          if (typeof item != 'undefined') {
        //                                 result += item;
        //                          }
        //                           ctr++;
        //                         result += columnDelimiter;
        //                       });
        //                       return result;
        //                     }
        //
        //                     function downloadCSV(args) {
        //                      console.log(args);
        //                       var data, filename, link;
        //                       var csv = \"\";
        //
        //                       for(var i = 0; i < args.chart.data.datasets.length; i++){
        //                         csv += convertChartDataToCSV({
        //                           data: args.chart.data.datasets[i]
        //                         }, args.chart.data.labels, i);
        //                       }
        //                       if (csv == null) return;
        //
        //                       filename = args.filename || 'chart-data.csv';
        //
        //                       if (!csv.match(/^data:text\/csv/i)) {
        //                         var universalBOM = '\uFEFF';
        //                         csv = 'data:text/csv;charset=utf-8,' + encodeURIComponent(universalBOM+csv);
        //                       }
        //                       link = document.createElement('a');
        //                       link.setAttribute('href', csv);
        //                       link.setAttribute('download', filename);
        //                       document.body.appendChild(link); // Required for FF
        //                       link.click();
        //                       document.body.removeChild(link);
        //                     }
        //         });</script>";
        //            $graph .= "<a href='#' id='download'></a>";
        //            $graph .= "</div>";
        //        }
        //        $graph .= "</div>";
        if ($params["canvas"] == true) {
            if ($params["nb"] < 1) {
                $graph .= "<div class='center'><br><br><h3><span class ='maint-color'>";
                $graph .= __("No results found");
                $graph .= "</span></h3></div>";
            }
            $graph .= "<div id=\"chart-container\" class=\"chart-container\">"; // style="position: relative; height:45vh; width:45vw"

            $graph .= "<div id=\"$name\" style='width: 100%; height: 400px;'></div>";
            $graph .= "</div>";
        }
        $graph .= "</div>";


        return $graph;
    }


    /**
     * @param $params
     *
     * @return string
     */
    public static function getGraphFooter($params)
    {
        $graph = "<div class='bt-row'>";
        $graph .= "<div class='bt-col-md-12 left'>";
        if (isset($params["setup"]) && Session::haveRightsOr("plugin_mydashboard_stockwidget", [CREATE, UPDATE])) {
            $graph .= "<a target='_blank' href='" . $params["setup"] . "'><i class=\"ti ti-tool\"></i></a>";
        }
        $graph .= "</div>";
        $graph .= "</div>";


        return $graph;
    }


    /**
     * Extract the content of the HTML script tag in an array 2D (line, column),
     * Useful for datatables
     *
     * @param array 2D $arrayToEval
     *
     * @return array of string (each string is a script line)
     */
    public static function extractScriptsFromArray($arrayToEval)
    {
        $scripts = [];
        if (is_array($arrayToEval)) {
            if (!is_array($arrayToEval)) {
                return $scripts;
            }
            foreach ($arrayToEval as $array) {
                if (!is_array($array)) {
                    break;
                }
                foreach ($array as $arrayLine) {
                    $scripts = array_merge($scripts, self::extractScriptsFromString($arrayLine));
                }
            }
        }
        return $scripts;
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
            $stringToEval = str_replace(["'", "//<![CDATA[", "//]]>"], ['"', "", ""], $stringToEval);
            //             $stringToEval = preg_replace('/\s+/', ' ', $stringToEval);

            if (preg_match_all("/<script[^>]*>([\s\S]+?)<\/script>/i", $stringToEval, $matches)) {
                foreach ($matches[1] as $match) {
                    //                     $match = preg_replace('/(\/\/[[:alnum:]_ ]+)/', '', $match);
                    //                     $match = preg_replace('#^\s*//.+$#m', "", $match);
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
        //      $stringWOScripts = "";
        //      if (gettype($stringToEval) == "string") {
        //         $stringWOScripts = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $stringToEval);
        //      }
        //      return $stringWOScripts;
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
        } elseif (!is_array($query) && stripos(trim($query), "SELECT") === 0) {
            $result = $DB->doQuery($query);
            $tab = [];
            if ($result) {
                while ($row = $DB->fetchAssoc($result)) {
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
            $widget = new MyDashboardHtml();
            $widget->debugError(__('Not a valid SQL SELECT query', 'mydashboard'));
            $widget->debugNotice($query);
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
                    $opt['requesters_groups_id']
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
                    $opt['technicians_groups_id']
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
