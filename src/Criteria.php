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

use GlpiPlugin\Mydashboard\Criterias\ComputerType;
use GlpiPlugin\Mydashboard\Criterias\Entity;
use Glpi\DBAL\QueryExpression;
use GlpiPlugin\Mydashboard\Criterias\ITILCategory;
use GlpiPlugin\Mydashboard\Criterias\Limit;
use GlpiPlugin\Mydashboard\Criterias\Location;
use GlpiPlugin\Mydashboard\Criterias\Month;
use GlpiPlugin\Mydashboard\Criterias\RequesterGroup;
use GlpiPlugin\Mydashboard\Criterias\Technician;
use GlpiPlugin\Mydashboard\Criterias\TechnicianGroup;
use GlpiPlugin\Mydashboard\Criterias\Type;
use GlpiPlugin\Mydashboard\Criterias\Year;
use Session;

class Criteria
{

    public static $criterias_list = [
        'entities_id',
        'is_recursive_entities',
        'type',
        'locations_id',
        'technicians_groups_id',
        'is_recursive_technicians',
        'requesters_groups_id',
        'is_recursive_requesters',
        'technicians_id',
        'itilcategories_id',
        'computertypes_id',
        'year',
        'month',
        'limit',

        'multiple_technicians_id',
        'display_data',
        'filter_date',
        'is_recursive_locations',
        'multiple_locations_id',
        'week',
        'end',
        'begin',
        'date',
        'closedate',
        'satisfactiondate',
        'multiple_time',
        'multiple_year_time',
        'status',
        'itilcategorielvl1',
        'tag',

    ];

    public const PRIORITY           = 3;
//    public const TYPE               = 14;
//    public const ENTITIES_ID        = 80;
    public const STATUS             = 12;
//    public const CATEGORY           = 7;
    public const OPEN_DATE          = 15;
//    public const TECHNICIAN         = 5;
//    public const REQUESTER_GROUP    = 71;
//    public const TECHNICIAN_GROUP   = 8;
//    public const LOCATIONS_ID       = 83;
    public const CLOSE_DATE         = 16;
    public const SOLVE_DATE         = 17;
    public const TASK_ACTIONTIME    = 96;
    public const VALIDATION_STATS   = 55;
    public const VALIDATION_REFUSED = 4;
    public const NUMBER_OF_PROBLEMS = 200;
    public const SATISFACTION_DATE  = 61;
    public const SATISFACTION_VALUE = 62;
    public const BUY_DATE           = 37;
//    public const TYPE_COMPUTER      = 4;

//TODO : 'filter_date', 'multiple_locations_id', 'month' 'display_data',
    /**
     * @param $params
     *
     * @return mixed
     */
    public static function getDefaultCriterias()
    {
        $default_criterias_list = [
            Entity::$criteria_name,
            'is_recursive_entities',
            TechnicianGroup::$criteria_name,
            'is_recursive_technicians',
            Type::$criteria_name,
        ];

        if (isset($_SESSION['glpiactiveprofile']['interface'])
            && Session::getCurrentInterface() == 'central') {
            $criterias = $default_criterias_list;
        }
        if (isset($_SESSION['glpiactiveprofile']['interface'])
            && Session::getCurrentInterface() != 'central') {
            $criterias = [Type::$criteria_name];
        }

        return $criterias;
    }

    public static function getGraphCriterias($params)
    {

        $default = self::manageCriterias($params);
        $opt = $params['opt'];

        $criterias_values = [];

        $criterias_list = self::$criterias_list;
        foreach ($criterias_list as $criteria) {
            if (in_array($criteria, $params['criterias'])) {
                $criterias_values[$criteria] = $opt[$criteria] ?? $default[$criteria];
            }
        }

        return $criterias_values;
    }

    public static function addCriteriasForQuery($query, $params, $table = 'glpi_tickets')
    {
        $default = self::manageCriterias($params);
        $opt = $params['opt'];

        if (in_array(Entity::$criteria_name, $params['criterias'])) {
            $entities_id = $opt[Entity::$criteria_name] ?? $default[Entity::$criteria_name];
        }
        if (in_array("is_recursive_entities", $params['criterias'])) {
            $is_recursive_entities = $opt['is_recursive_entities'] ?? $default['is_recursive_entities'];
        }
        if (in_array(Type::$criteria_name, $params['criterias']) && $table == 'glpi_tickets') {
            $type = $opt[Type::$criteria_name] ?? $default[Type::$criteria_name];
        }
        if (in_array(TechnicianGroup::$criteria_name, $params['criterias'])  && $table == 'glpi_tickets') {
            $technicians_groups_id = $opt[TechnicianGroup::$criteria_name] ?? $default[TechnicianGroup::$criteria_name];
        }
        if (in_array("is_recursive_technicians", $params['criterias'])  && $table == 'glpi_tickets') {
            $is_recursive_technicians = $opt['is_recursive_technicians'] ?? $default['is_recursive_technicians'];
        }
        if (in_array(RequesterGroup::$criteria_name, $params['criterias'])) {
            $requesters_groups_id = $opt[RequesterGroup::$criteria_name] ?? $default[RequesterGroup::$criteria_name];
        }
        if (in_array("is_recursive_requesters", $params['criterias'])) {
            $is_recursive_requesters = $opt['is_recursive_requesters'] ?? $default['is_recursive_requesters'];
        }
        if (in_array(Location::$criteria_name, $params['criterias'])) {
            $locations_id = $opt[Location::$criteria_name] ?? $default[Location::$criteria_name];
        }
        if (in_array(ITILCategory::$criteria_name, $params['criterias'])) {
            $itilcategories_id = $opt[ITILCategory::$criteria_name] ?? $default[ITILCategory::$criteria_name];
        }
        if (in_array(Technician::$criteria_name, $params['criterias'])) {
            $technicians_id = $opt[Technician::$criteria_name] ?? $default[Technician::$criteria_name];
        }
        if (in_array(ComputerType::$criteria_name, $params['criterias'])) {
            $computertypes_id = $opt[ComputerType::$criteria_name] ?? $default[ComputerType::$criteria_name];
        }

        foreach ($params['criterias'] as $criterion) {

            if ($table == 'glpi_tickets' && $criterion == TechnicianGroup::$criteria_name && is_array($technicians_groups_id)) {
                $technicians_groups_id = array_filter($technicians_groups_id);

                if (count($technicians_groups_id) > 0) {
                    $params_query['criteria'] = $criterion;
                    $params_query['query'] = $query;
                    $params_query[$criterion] = $technicians_groups_id;
                    $params_query['recursive'] = $is_recursive_technicians ?? 0;
                    $query = self::defineLeftjoinByCriteria($params_query, $table);

                    $params_query['query'] = $query;
                    $query = self::defineWhereByCriteria($params_query, $table);
                }
            }

            if ($criterion == RequesterGroup::$criteria_name && is_array($requesters_groups_id)) {
                $requesters_groups_id = array_filter($requesters_groups_id);

                if (count($requesters_groups_id) > 0) {
                    $params_query['criteria'] = $criterion;
                    $params_query['query'] = $query;
                    $params_query[$criterion] = $requesters_groups_id;
                    $params_query['recursive'] = $is_recursive_requesters ?? 0;
                    $query = self::defineLeftjoinByCriteria($params_query, $table);

                    $params_query['query'] = $query;
                    $query = self::defineWhereByCriteria($params_query, $table);
                }
            }

            if ($criterion == Technician::$criteria_name) {

                if ($technicians_id > 0) {
                    $params_query['criteria'] = $criterion;
                    $params_query['query'] = $query;
                    $params_query[$criterion] = $technicians_id;
                    $query = self::defineLeftjoinByCriteria($params_query, $table);

                    $params_query['query'] = $query;
                    $query = self::defineWhereByCriteria($params_query, $table);
                }
            }

            if ($table == 'glpi_tickets' && $criterion == Type::$criteria_name && $type > 0) {
                $params_query['criteria'] = $criterion;
                $params_query['query'] = $query;
                $params_query[$criterion] = $type;
                $query = self::defineWhereByCriteria($params_query, $table);
            }

            if ($criterion == Location::$criteria_name && $locations_id > 0) {
                $params_query['criteria'] = $criterion;
                $params_query['query'] = $query;
                $params_query[$criterion] = $locations_id;
                $query = self::defineWhereByCriteria($params_query, $table);
            }

            if ($criterion == ITILCategory::$criteria_name && $itilcategories_id > 0) {
                $params_query['criteria'] = $criterion;
                $params_query['query'] = $query;
                $params_query[$criterion] = $itilcategories_id;
                $query = self::defineWhereByCriteria($params_query, $table);
            }

            if ($criterion == Entity::$criteria_name && !is_array($entities_id)) {
                $entities_id = [$entities_id];
            }
            if ($criterion ==  Entity::$criteria_name && is_array($entities_id)) {

                $params_query['criteria'] = $criterion;
                $params_query['query'] = $query;
                $params_query[$criterion] = $entities_id;
                $params_query['recursive'] = $is_recursive_entities;
                $query = self::defineWhereByCriteria($params_query, $table);
            }

            if ($table != 'glpi_tickets' && $criterion == ComputerType::$criteria_name && $computertypes_id > 0) {
                $params_query['criteria'] = $criterion;
                $params_query['query'] = $query;
                $params_query[$criterion] = $computertypes_id;
                $query = self::defineWhereByCriteria($params_query, $table);
            }

        }

        return $query;
    }

    public static function defineLeftjoinByCriteria($params, $table = 'glpi_tickets')
    {

        $query = $params['query'];

        if ($params['criteria']  == TechnicianGroup::$criteria_name) {
            $technician_group = array_filter($params[TechnicianGroup::$criteria_name]);
            if (count($technician_group) > 0) {
                if (!isset($params['query']['LEFT JOIN'])) {
                    $params['query']['LEFT JOIN'] = [];
                }
                $query['LEFT JOIN'] = TechnicianGroup::getQueryLeftJoin($params, $table);
            }
        } elseif ($params['criteria']  == RequesterGroup::$criteria_name) {
            $requester_groups = array_filter($params[RequesterGroup::$criteria_name]);
            if (count($requester_groups) > 0) {
                if (!isset($params['query']['LEFT JOIN'])) {
                    $params['query']['LEFT JOIN'] = [];
                }
                $query['LEFT JOIN'] = RequesterGroup::getQueryLeftJoin($params, $table);

            }
        } elseif ($params['criteria']  == Technician::$criteria_name) {
            if (!isset($params['query']['LEFT JOIN'])) {
                $params['query']['LEFT JOIN'] = [];
            }
            $query['LEFT JOIN'] = Technician::getQueryLeftJoin($params, $table);
        }

        return $query;
    }

    public static function defineWhereByCriteria($params, $table = 'glpi_tickets')
    {

        $query = $params['query'];

        if ($params['criteria'] == "entities_id") {

            $query['WHERE'] = Entity::getQueryCriteria($params, $table);

        } elseif ($params['criteria'] == TechnicianGroup::$criteria_name) {

            $technician_group = array_filter($params[TechnicianGroup::$criteria_name]);
            if (count($technician_group) > 0) {
                $query['WHERE'] = TechnicianGroup::getQueryCriteria($params);
            }

        } elseif ($params['criteria'] == Type::$criteria_name) {

            $query['WHERE'] = Type::getQueryCriteria($params);

        } elseif ($params['criteria'] == ComputerType::$criteria_name) {

            $query['WHERE'] = ComputerType::getQueryCriteria($params, $table);

        } elseif ($params['criteria'] == RequesterGroup::$criteria_name) {

            $requester_groups = array_filter($params[RequesterGroup::$criteria_name]);
            if (count($requester_groups) > 0) {
                $query['WHERE'] = RequesterGroup::getQueryCriteria($params);
            }

        } elseif ($params['criteria'] == Technician::$criteria_name) {

            $query['WHERE'] = Technician::getQueryCriteria($params);

        } elseif ($params['criteria'] == ITILCategory::$criteria_name) {

            $query['WHERE'] = ITILCategory::getQueryCriteria($params);

        } elseif ($params['criteria'] == Location::$criteria_name) {

            $query['WHERE'] = Location::getQueryCriteria($params);

        } elseif ($params['criteria'] == "multiple_locations_id") {

            $query['WHERE'] = $params['query']['WHERE'] + ['glpi_tickets.locations_id' => $params['multiple_locations_id']];

        }

        return $query;
    }

    /**
     * @param $field
     * @param $searchType
     * @param $value
     * @param $link
     */
    public static function addUrlCriteria($field, $searchType, $value, $link)
    {
        global $options;

        $options['criteria'][] = [
            'field'      => $field,
            'searchtype' => $searchType,
            'value'      => $value,
            'link'       => $link,
        ];
        return $options;
    }

    /**
     * @param $field
     * @param $searchType
     * @param $value
     */
    public static function addUrlGroupCriteria($field, $searchType, $value)
    {
        global $options;

        if (isset($value)
            && count($value) > 0) {
            $groups = $value;
            $nb     = 0;
            foreach ($groups as $group) {
                $criterias['criteria'][$nb] = [
                    'field'      => $field,
                    'searchtype' => $searchType,
                    'value'      => $group,
                    'link'       => (($nb == 0) ? 'AND' : 'OR'),
                ];
                $nb++;
            }
            $options['criteria'][] = $criterias;
        }

        return $options;
    }

    /**
     * @param $params
     *
     * @return mixed
     */
    public static function manageCriterias($params)
    {

        $criterias = $params['criterias'];

        $used_criterias = [
            Entity::$criteria_name => Entity::Class,
            Type::$criteria_name => Type::Class,
            TechnicianGroup::$criteria_name => TechnicianGroup::Class,
            RequesterGroup::$criteria_name => RequesterGroup::Class,
            Limit::$criteria_name => Limit::Class,
            Location::$criteria_name => Location::Class,
            Technician::$criteria_name => Technician::Class,
            ITILCategory::$criteria_name => ITILCategory::Class,
            Year::$criteria_name => Year::Class,
            Month::$criteria_name => Month::Class,
            ComputerType::$criteria_name => ComputerType::Class,
        ];

        foreach ($used_criterias as $criteria => $class) {
            if (in_array($criteria, $criterias)) {
                $critClass= new $class();
                $default[$criteria] = $critClass::getDefaultValue();

            }
        }
        $default['is_recursive_entities'] = false;
        $default['is_recursive_technicians'] = false;

        //        $opt['multiple_locations_id'] = [];
        //        $crit['crit']['multiple_locations_id'] = "";
        //        $opt['loc_ancestors'] = 0;
        //        $crit['crit']['loc_ancestors'] = 0;

        //        if (in_array("multiple_locations_id", $criterias)) {
        //            if (isset($params['opt']['multiple_locations_id'])) {
        //                $opt['multiple_locations_id'] = is_array(
        //                    $params['opt']['multiple_locations_id']
        //                ) ? $params['opt']['multiple_locations_id'] : [];
        //            } else {
        //                $crit['crit']['multiple_locations_id'] =  [];
        //            }
        //            $params['opt']['multiple_locations_id'] = $opt['multiple_locations_id'];
        //
        //            if (isset($params['opt']['multiple_locations_id'])
        //                && is_array($params['opt']['multiple_locations_id'])
        //                && count($params['opt']['multiple_locations_id']) > 0) {
        //                if (isset($params['opt']['loc_ancestors']) && $params['opt']['loc_ancestors'] != 0) {
        //                    $dbu = new DbUtils();
        //                    $childs = [];
        //                    foreach ($opt['multiple_locations_id'] as $k => $v) {
        //                        $childs = $dbu->getSonsAndAncestorsOf('glpi_locations', $v);
        //                    }
        //                    $crit['crit']['multiple_locations_id'] = ['locations_id' => $childs];
        //                    $opt['loc_ancestors'] = $params['opt']['loc_ancestors'];
        //                    $crit['crit']['loc_ancestors'] = $opt['loc_ancestors'];
        //                } else {
        //                    $crit['crit']['multiple_locations_id'] = ['locations_id' => $params['opt']['multiple_locations_id']];
        //                    $opt['loc_ancestors'] = 0;
        //                    $crit['crit']['loc_ancestors'] = 0;
        //                }
        //            }
        //        }

        //        $year = intval(date('Y', time()));
        //        $month = intval(date('m', time()) - 1);
        //        $crit['crit']['year'] = $year;
        //        if (in_array("month", $criterias)) {
        //            if ($month > 0) {
        //                $year = date('Y', time());
        //                $opt["year"] = $year;
        //            } else {
        //                $month = 12;
        //            }
        //            if (isset($params['opt']["month"])
        //                && $params['opt']["month"] > 0) {
        //                $month = $params['opt']["month"];
        //                $opt['month'] = $params['opt']['month'];
        //            } else {
        //                $opt["month"] = $month;
        //            }
        //        }
        //

        if (in_array("week", $criterias)) {
            $default['week'] = intval(date('W', time()) - 1);
        }

        // DISPLAY DATA
        if (in_array("display_data", $criterias)) {
            $default['display_data'] = "YEAR";
        }
        //        if (in_array("display_data", $criterias)) {
        //            if (isset($params['opt']['display_data'])) {
        //                $opt["display_data"] = $params['opt']['display_data'];
        //                $crit['crit']['display_data'] = $params['opt']['display_data'];
        //            } else {
        //                $opt["display_data"] = "YEAR";
        //                $crit['crit']['display_data'] = "YEAR";
        //            }
        //
        //            if ($opt["display_data"] == "YEAR") {
        //                $year = intval(date('Y', time()));
        //                if (isset($params['opt']["year"])
        //                    && $params['opt']["year"] > 0) {
        //                    $year = $params['opt']["year"];
        //                    $opt['year'] = $params['opt']['year'];
        //                } else {
        //                    $opt["year"] = $year;
        //                }
        //                $crit['crit']['year'] = $opt['year'];
        //            } elseif ($opt["display_data"] == "START_END") {
        //                if (isset($params['opt']["start_month"])
        //                    && $params['opt']["start_month"] > 0) {
        //                    $opt['start_month'] = $params['opt']['start_month'];
        //                    $crit['crit']['start_month'] = $params['opt']['start_month'];
        //                } else {
        //                    $opt["start_month"] = date("m");
        //                    $crit['crit']['start_month'] = date("m");
        //                }
        //
        //                if (isset($params['opt']["start_year"])
        //                    && $params['opt']["start_year"] > 0) {
        //                    $opt['start_year'] = $params['opt']['start_year'];
        //                    $crit['crit']['start_year'] = $params['opt']['start_year'];
        //                } else {
        //                    $opt["start_year"] = date("Y");
        //                    $crit['crit']['start_year'] = date("Y");
        //                }
        //
        //                if (isset($params['opt']["end_month"])
        //                    && $params['opt']["end_month"] > 0) {
        //                    $opt['end_month'] = $params['opt']['end_month'];
        //                    $crit['crit']['end_month'] = $params['opt']['end_month'];
        //                } else {
        //                    $opt["end_month"] = date("m");
        //                    $crit['crit']['end_month'] = date("m");
        //                }
        //
        //                if (isset($params['opt']["end_year"])
        //                    && $params['opt']["end_year"] > 0) {
        //                    $opt['end_year'] = $params['opt']['end_year'];
        //                    $crit['crit']['end_year'] = $params['opt']['end_year'];
        //                } else {
        //                    $opt["end_year"] = date("Y");
        //                    $crit['crit']['end_year'] = date("Y");
        //                }
        //            }
        //        }

        if (in_array("filter_date", $criterias)) {
            $default['filter_date'] = "YEAR";
        }

        //        if (in_array("filter_date", $criterias)) {
        //            if (isset($params['opt']['filter_date'])) {
        //                $opt["filter_date"] = $params['opt']['filter_date'];
        //                $crit['crit']['filter_date'] = $params['opt']['filter_date'];
        //            } else {
        //                $opt["filter_date"] = "YEAR";
        //                $crit['crit']['filter_date'] = "YEAR";
        //            }
        //
        //            if ($opt["filter_date"] == "YEAR") {
        //                $year = intval(date('Y', time()));
        //                if (isset($params['opt']["year"])
        //                    && $params['opt']["year"] > 0) {
        //                    $year = $params['opt']["year"];
        //                    $opt['year'] = $params['opt']['year'];
        //                } else {
        //                    $opt["year"] = $year;
        //                }
        //                $crit['crit']['year'] = $opt['year'];
        //
        //                $crit['crit']['date'] = [['glpi_tickets.date' => ['>=', $year . '-01-01 00:00:01']],
        //                    ['glpi_tickets.date' => ['<=', new QueryExpression("ADDDATE('" . $year . "-12-31 00:00:00' , INTERVAL 1 DAY)")]]];
        //
        //                $crit['crit']['closedate'] = [['glpi_tickets.closedate' => ['>=', $year . '-01-01 00:00:01']],
        //                    ['glpi_tickets.closedate' => ['<=', new QueryExpression("ADDDATE('" . $year . "-12-31 00:00:00' , INTERVAL 1 DAY)")]]];
        //
        //                $crit['crit']['satisfactiondate'] = [['glpi_ticketsatisfactions.date_answered' => ['>=', $year . '-01-01 00:00:01']],
        //                    ['glpi_ticketsatisfactions.date_answered' => ['<=', new QueryExpression("ADDDATE('" . $year . "-12-31 00:00:00' , INTERVAL 1 DAY)")]]];
        //
        //
        //
        //            } elseif ($opt["filter_date"] == "BEGIN_END") {
        //                if (isset($params['opt']['begin'])
        //                    && $params['opt']["begin"] > 0) {
        //                    $opt["begin"] = $params['opt']['begin'];
        //                    $crit['crit']['begin'] = $params['opt']['begin'];
        //                } else {
        //                    $opt["begin"] = date("Y-m-d H:i:s");
        //                }
        //
        //                if (isset($params['opt']['end'])
        //                    && $params['opt']["end"] > 0) {
        //                    $opt["end"] = $params['opt']['end'];
        //                    $crit['crit']['end'] = $params['opt']['end'];
        //                } else {
        //                    $opt["end"] = date("Y-m-d H:i:s");
        //                }
        //                $end = $opt["end"];
        //                $start = $opt["begin"];
        //
        //                $crit['crit']['date'] = [['glpi_tickets.date' => ['>=', $start]],
        //                    ['glpi_tickets.date' => ['<=', $end]]];
        //
        //                $crit['crit']['closedate'] = [['glpi_tickets.closedate' => ['>=', $start]],
        //                    ['glpi_tickets.closedate' => ['<=', $end]]];
        //
        //                $crit['crit']['satisfactiondate'] = [['glpi_ticketsatisfactions.date_answered' => ['>=', $start]],
        //                    ['glpi_ticketsatisfactions.date_answered' => ['<=', $end]]];
        //
        //            }
        //        }

        // BEGIN DATE
        if (in_array("begin", $criterias)) {
            $default['begin'] = date("Y-m-d H:i:s");
        }
        //        if (in_array("begin", $criterias)) {
        //            if (isset($params['opt']['begin'])
        //                && $params['opt']["begin"] > 0) {
        //                $opt["begin"] = $params['opt']['begin'];
        //                $crit['crit']['begin'] = $params['opt']['begin'];
        //            } else {
        //                $opt["begin"] = date("Y-m-d H:i:s");
        //            }
        //        }

        // END DATE
        if (in_array("end", $criterias)) {
            $default['end'] = date("Y-m-d H:i:s");
        }
        //        if (in_array("end", $criterias)) {
        //            if (isset($params['opt']['end'])
        //                && $params['opt']["end"] > 0) {
        //                $opt["end"] = $params['opt']['end'];
        //                $crit['crit']['end'] = $params['opt']['end'];
        //            } else {
        //                $opt["end"] = date("Y-m-d H:i:s");
        //            }
        //        }

        //        if (!in_array('filter_date', $criterias)) {
        //            $nbdays = date("t", mktime(0, 0, 0, $month, 1, $year));
        //
        //            $crit['crit']['date'] = [['glpi_tickets.date' => ['>=', $year - $month . '-01 00:00:01']],
        //                ['glpi_tickets.date' => ['<=', new QueryExpression("ADDDATE('$year-$month-$nbdays 00:00:00' , INTERVAL 1 DAY)")]]];
        //
        //            $crit['crit']['closedate'] = [['glpi_tickets.closedate' => ['>=', $year - $month . '-01 00:00:01']],
        //                ['glpi_tickets.closedate' => ['<=', new QueryExpression("ADDDATE('$year-$month-$nbdays 00:00:00' , INTERVAL 1 DAY)")]]];
        //
        //            $crit['crit']['satisfactiondate'] = [['glpi_ticketsatisfactions.date_answered' => ['>=', $year - $month . '-01 00:00:01']],
        //                ['glpi_ticketsatisfactions.date_answered' => ['<=', new QueryExpression("ADDDATE('$year-$month-$nbdays 00:00:00' , INTERVAL 1 DAY)")]]];
        //
        //        }
        //
        //        if (!in_array("month", $criterias) && !in_array('filter_date', $criterias)) {
        //
        //            $crit['crit']['date'] = [['glpi_tickets.date' => ['>=', $year . '-01-01 00:00:01']],
        //                ['glpi_tickets.date' => ['<=', new QueryExpression("ADDDATE('$year-12-31 00:00:00' , INTERVAL 1 DAY)")]]];
        //
        //            $crit['crit']['closedate'] = [['glpi_tickets.closedate' => ['>=', $year . '-01-01 00:00:01']],
        //                ['glpi_tickets.closedate' => ['<=', new QueryExpression("ADDDATE('$year-12-31 00:00:00' , INTERVAL 1 DAY)")]]];
        //
        //            $crit['crit']['satisfactiondate'] = [['glpi_ticketsatisfactions.date_answered' => ['>=', $year . '-01-01 00:00:01']],
        //                ['glpi_ticketsatisfactions.date_answered' => ['<=', new QueryExpression("ADDDATE('$year-12-31 00:00:00' , INTERVAL 1 DAY)")]]];
        //
        //        }


        // TECHNICIAN MULTIPLE
        //        $opt['multiple_technicians_id'] = [];
        //        //        $crit['crit']['multiple_technicians_id'] = " AND 1 = 1 ";
        //        if (in_array("multiple_technicians_id", $criterias)) {
        //            if (isset($params['opt']['multiple_technicians_id'])) {
        //                $opt['multiple_technicians_id'] = is_array(
        //                    $params['opt']['multiple_technicians_id']
        //                ) ? $params['opt']['multiple_technicians_id'] : [];
        //            } else {
        //                $crit['crit']['multiple_technicians_id'] = [];
        //            }
        //            $params['opt']['multiple_technicians_id'] = $opt['multiple_technicians_id'];
        //
        //            if (isset($params['opt']['multiple_technicians_id'])
        //                && is_array($params['opt']['multiple_technicians_id'])
        //                && count($params['opt']['multiple_technicians_id']) > 0) {
        //                $crit['crit']['multiple_technicians_id'] = $params['opt']['multiple_technicians_id'];
        //            }
        //        }


        // MULTIPLE TIME
        //        if (in_array("multiple_time", $criterias)) {
        //            if (isset($params['opt']['multiple_time'])) {
        //                $opt["multiple_time"] = $params['opt']['multiple_time'];
        //                $crit['crit']['multiple_time'] = $params['opt']['multiple_time'];
        //            } else {
        //                $opt["multiple_time"] = "MONTH";
        //                $crit['crit']['multiple_time'] = "MONTH";
        //            }
        //        }

        // MULTIPLE YEAR TIME
        //        if (in_array("multiple_year_time", $criterias)) {
        //            if (isset($params['opt']['multiple_year_time'])) {
        //                $opt["multiple_year_time"] = $params['opt']['multiple_year_time'];
        //                $crit['crit']['multiple_year_time'] = $params['opt']['multiple_year_time'];
        //            } else {
        //                $opt["multiple_year_time"] = "LASTMONTH";
        //                $crit['crit']['multiple_year_time'] = "LASTMONTH";
        //            }
        //            if (isset($params['opt']['month_year'])) {
        //                $opt["month_year"] = $params['opt']['month_year'];
        //                $crit['crit']['month_year'] = $params['opt']['month_year'];
        //            }
        //        }


        // STATUS
        //        $default = [
        //            CommonITILObject::INCOMING,
        //            CommonITILObject::ASSIGNED,
        //            CommonITILObject::PLANNED,
        //            CommonITILObject::WAITING,
        //        ];
        //        $crit['crit']['status'] = $default;
        //        $opt['status'] = $default;
        //        if (in_array("status", $criterias)) {
        //            $status = [];
        //
        //            if (isset($params['opt']["status_1"])
        //                && $params['opt']["status_1"] > 0) {
        //                $status[] = CommonITILObject::INCOMING;
        //            }
        //            if (isset($params['opt']["status_2"])
        //                && $params['opt']["status_2"] > 0) {
        //                $status[] = CommonITILObject::ASSIGNED;
        //            }
        //            if (isset($params['opt']["status_3"])
        //                && $params['opt']["status_3"] > 0) {
        //                $status[] = CommonITILObject::PLANNED;
        //            }
        //            if (isset($params['opt']["status_4"])
        //                && $params['opt']["status_4"] > 0) {
        //                $status[] = CommonITILObject::WAITING;
        //            }
        //            if (isset($params['opt']["status_5"])
        //                && $params['opt']["status_5"] > 0) {
        //                $status[] = CommonITILObject::SOLVED;
        //            }
        //            if (isset($params['opt']["status_6"])
        //                && $params['opt']["status_6"] > 0) {
        //                $status[] = CommonITILObject::CLOSED;
        //            }
        //
        //            if (count($status) > 0) {
        //                $opt['status'] = $status;
        //                $crit['crit']['status'] = $status;
        //            }
        //        }
        //ITILCATEGORY_LVL1
        //        $opt['itilcategorielvl1'] = 0;
        //        //        $crit['crit']['itilcategorielvl1'] = " AND 1 = 1 ";
        //        if (in_array("itilcategorielvl1", $criterias)) {
        //            if (isset($params['preferences']['prefered_category'])
        //                && $params['preferences']['prefered_category'] > 0 && !isset($params['opt']['itilcategorielvl1'])) {
        //                $opt['itilcategorielvl1'] = $params['preferences']['prefered_category'];
        //            } elseif (isset($params['opt']["itilcategorielvl1"])
        //                && $params['opt']["itilcategorielvl1"] > 0) {
        //                $opt['itilcategorielvl1'] = $params['opt']['itilcategorielvl1'];
        //            }
        //            $category = new ITILCategory();
        //            $catlvl2 = $category->find(
        //                ['itilcategories_id' => $opt['itilcategorielvl1'], 'is_request' => 1, 'is_incident' => 1]
        //            );
        //            $listcat = [];
        //            $listcat[] = $opt['itilcategorielvl1'];
        //            foreach ($catlvl2 as $cat) {
        //                $listcat[] = $cat['id'];
        //            }
        //            $categories = implode(",", $listcat);
        //            if (empty($listcat)) {
        //                $listcat = "0";
        //            }
        //            $crit['crit']['itilcategorielvl1'] = ['glpi_tickets.itilcategories_id' => $categories];
        //        }


        //TAG
        //        $opt['tag'] = 0;
        //        //        $crit['crit']['tag'] = "AND 1 = 1";
        //        if (in_array("tag", $criterias)) {
        //            if (isset($params['opt']["tag"])
        //                && $params['opt']["tag"] > 0) {
        //                $opt['tag'] = $params['opt']['tag'];
        //
        //                $crit['crit']['tag'] = ['glpi_plugin_tag_tagitems.plugin_tag_tags_id' => $opt['tag']];
        //            }
        //        }
        //
        //        $crit['opt'] = $opt;

        $year = intval(date('Y', time()));

        $default['date'] = [['glpi_tickets.date' => ['>=', $year . '-01-01 00:00:01']],
            ['glpi_tickets.date' => ['<=', new QueryExpression("ADDDATE('$year-12-31 00:00:00' , INTERVAL 1 DAY)")]]];

        $default['closedate'] = [['glpi_tickets.closedate' => ['>=', $year . '-01-01 00:00:01']],
            ['glpi_tickets.closedate' => ['<=', new QueryExpression("ADDDATE('$year-12-31 00:00:00' , INTERVAL 1 DAY)")]]];

        $default['satisfactiondate'] = [['glpi_ticketsatisfactions.date_answered' => ['>=', $year . '-01-01 00:00:01']],
            ['glpi_ticketsatisfactions.date_answered' => ['<=', new QueryExpression("ADDDATE('$year-12-31 00:00:00' , INTERVAL 1 DAY)")]]];


        return $default;
    }

    /**
     * @param $table
     * @param $params
     *
     * @return array
     */
//    public static function getSpecificEntityRestrict($table, $params)
//    {
//        if (isset($params['entities_id']) && $params['entities_id'] == "") {
//            $params['entities_id'] = $_SESSION['glpiactive_entity'];
//        }
//        if (isset($params['entities_id']) && ($params['entities_id'] != -1)) {
//            if (isset($params['sons']) && ($params['sons'] != "") && ($params['sons'] != 0)) {
//                $entities = [$table . '.entities_id' => getSonsOf("glpi_entities", $params['entities_id'])];
//            } else {
//                $entities = [$table . '.entities_id' => $params['entities_id']];
//            }
//        } else {
//            if (isset($params['sons']) && ($params['sons'] != "") && ($params['sons'] != 0)) {
//                $entities = [$table . '.entities_id' => getSonsOf("glpi_entities", $_SESSION['glpiactive_entity'])];
//            } else {
//                $entities = [$table . '.entities_id' => $_SESSION['glpiactive_entity']];
//            }
//        }
//        return $entities;
//    }
    /**
     * @param $params
     *
     * @return mixed
     */
//    public static function manageCriteriasOld($params)
//    {
//
//        $criterias = $params['criterias'];
//
//
//        // ENTITY | SONS
//        if (Session::isMultiEntitiesMode()) {
//
//            if (in_array("entities_id", $criterias)
//                && isset($params['opt']['entities_id'])
//                && $params['opt']['entities_id'] > 0) {
//                $opt['entities_id'] = $params['opt']['entities_id'];
//            } elseif (isset($params['preferences']['prefered_entity'])
//                && $params['preferences']['prefered_entity'] > 0) {
//                $opt['entities_id'] = $params['preferences']['prefered_entity'];
//            } else {
//                $opt['entities_id'] = $_SESSION['glpiactive_entity'];
//            }
//            $opt['sons'] = 0;
//            $crit['crit']['sons'] = 0;
//            if (in_array("is_recursive_entities", $criterias)) {
//                if (!isset($params['opt']['sons'])) {
//                    //TODO : Add conf for recursivity
//                    if (isset($_SESSION['glpiactive_entity_recursive']) && $_SESSION['glpiactive_entity_recursive'] != false) {
//                        $opt['sons'] = $_SESSION['glpiactive_entity_recursive'];
//                    } else {
//                        $opt['sons'] = 0;
//                    }
//                } else {
//                    $opt['sons'] = $params['opt']['sons'];
//                }
//                $crit['crit']['sons'] = $opt['sons'];
//            }
//
//            if (isset($opt)) {
//                $crit['crit']['entities_id'] = self::getSpecificEntityRestrict("glpi_tickets", $opt);
//                $crit['crit']['entity'] = $opt['entities_id'];
//            }
//        } else {
//            $crit['crit']['entities_id'] = '';
//            $crit['crit']['entity'] = 0;
//            $crit['crit']['sons'] = 0;
//        }
//
//        // REQUESTER GROUP
//        $opt['requesters_groups_id'] = [];
//        //        $crit['crit']['requesters_groups_id'] = "AND 1 = 1";
//        //      $opt['ancestors']                     = 0;
//        //      $crit['crit']['ancestors']            = 0;
//        if (in_array("requesters_groups_id", $criterias)) {
//            if (isset($params['opt']['requesters_groups_id'])) {
//                $opt['requesters_groups_id'] = $params['opt']['requesters_groups_id'];
//            } elseif ($_SERVER["REQUEST_URI"] == PLUGIN_MYDASHBOARD_WEBDIR . "/front/menu.php") {
//                $groups_id = self::getRequesterGroup(
//                    $params['preferences']['requester_prefered_group'],
//                    $params,
//                    $_SESSION['glpiactive_entity'],
//                    Session::getLoginUserID(),
//                    $opt
//                );
//                $opt['requesters_groups_id'] = $groups_id;
//            } else {
//                $opt['requesters_groups_id'] = [];
//            }
//
//            $params['opt']['requesters_groups_id'] = $opt['requesters_groups_id'];
//
//            $params['opt']['requesters_groups_id'] = is_array(
//                $params['opt']['requesters_groups_id']
//            ) ? $params['opt']['requesters_groups_id'] : [];
//
//            $params['opt']['requesters_groups_id'] = array_filter($params['opt']['requesters_groups_id']);
//
//            if (isset($params['opt']['requesters_groups_id'])
//                && is_array($params['opt']['requesters_groups_id'])
//                && count($params['opt']['requesters_groups_id']) > 0) {
//
//                $crit['crit']['requesters_groups_id'] = ['glpi_tickets.id' => new QueryExpression("(SELECT tickets_id AS id FROM glpi_groups_tickets
//                WHERE type = " . CommonITILActor::REQUESTER . " AND groups_id IN (" . implode(
//                    ",",
//                    $params['opt']['requesters_groups_id']
//                ) . ")))")];
//            }
//        }
//
//        // TECH GROUP
//        $opt['technicians_groups_id'] = [];
//        //        $crit['crit']['technicians_groups_id'] = "AND 1 = 1";
//        $opt['ancestors'] = 0;
//        $crit['crit']['ancestors'] = 0;
//        if (in_array("technicians_groups_id", $criterias)) {
//            if (isset($params['opt']['technicians_groups_id'])) {
//
//                $opt['technicians_groups_id'] = is_array(
//                    $params['opt']['technicians_groups_id']
//                ) ? $params['opt']['technicians_groups_id'] : [];
//
//            } else { //if ($_SERVER["REQUEST_URI"] == PLUGIN_MYDASHBOARD_WEBDIR . "/front/menu.php")
//                $groups_id = self::getGroup($params['preferences']['prefered_group'], $opt, $params);
//                $opt['technicians_groups_id'] = $groups_id;
//                //            } else {
//                //                $opt['technicians_groups_id'] = [];
//            }
//            $params['opt']['technicians_groups_id'] = $opt['technicians_groups_id'];
//            if (is_array($params['opt']['technicians_groups_id'])) {
//                $params['opt']['technicians_groups_id'] = array_filter($params['opt']['technicians_groups_id']);
//            }
//
//            if (isset($params['opt']['technicians_groups_id'])
//                && is_array($params['opt']['technicians_groups_id'])
//                && count($params['opt']['technicians_groups_id']) > 0) {
//                $none = false;
//                if (isset($params['opt']['technicians_groups_id'][0])
//                    && $params['opt']['technicians_groups_id'][0] == "0") {
//                    $none = true;
//                }
//                if (in_array(
//                    "is_recursive_technicians",
//                    $criterias
//                ) && isset($params['opt']['ancestors']) && $params['opt']['ancestors'] != 0) {
//                    $dbu = new DbUtils();
//                    $childs = [];
//                    foreach ($opt['technicians_groups_id'] as $k => $v) {
//                        $childs = $dbu->getSonsAndAncestorsOf('glpi_groups', $v);
//                    }
//                    $childs = array_filter($childs);
//                    $params['opt']['technicians_groups_id'] = array_filter($params['opt']['technicians_groups_id']);
//                    if ($none) {
//
//                        $crit['crit']['technicians_groups_id'] = [['NOT' => ['glpi_tickets.id' => new QueryExpression("(SELECT tickets_id AS id FROM glpi_groups_tickets")]],
//                            ['glpi_tickets.id' => new QueryExpression("(SELECT tickets_id AS id FROM glpi_groups_tickets
//                WHERE type = " . CommonITILActor::ASSIGN . " AND groups_id IN (" . implode(
//                                ",",
//                                $childs
//                            ) . ")))")]];
//                        //
//                        //
//                        //                        $crit['crit']['technicians_groups_id'] = " AND ( `glpi_tickets`.`id` NOT IN (SELECT `tickets_id` AS id FROM `glpi_groups_tickets`) ";
//                        //                        $crit['crit']['technicians_groups_id'] .= " OR `glpi_tickets`.`id` IN (SELECT `tickets_id` AS id FROM `glpi_groups_tickets`
//                        //            WHERE `type` = " . CommonITILActor::ASSIGN . " AND `groups_id` IN (" . implode(",", $childs) . ")))";
//
//                    } else {
//
//                        $crit['crit']['technicians_groups_id'] = ['glpi_tickets.id' => new QueryExpression("(SELECT tickets_id AS id FROM glpi_groups_tickets
//                WHERE type = " . CommonITILActor::ASSIGN . " AND groups_id IN (" . implode(
//                            ",",
//                            $childs
//                        ) . ")))")];
//
//                        //                        $crit['crit']['technicians_groups_id'] .= " AND `glpi_tickets`.`id` IN (SELECT `tickets_id` AS id FROM `glpi_groups_tickets`
//                        //            WHERE `type` = " . CommonITILActor::ASSIGN . " AND `groups_id` IN (" . implode(",", $childs) . "))";
//
//                    }
//                    $opt['ancestors'] = $params['opt']['ancestors'];
//                    $crit['crit']['ancestors'] = $opt['ancestors'];
//                } else {
//                    $params['opt']['technicians_groups_id'] = array_filter($params['opt']['technicians_groups_id']);
//                    if ($none) {
//
//                        //                        $crit['crit']['technicians_groups_id'] = " AND ( `glpi_tickets`.`id` NOT IN (SELECT `tickets_id` AS id FROM `glpi_groups_tickets`) ";
//                        //                        $crit['crit']['technicians_groups_id'] .= " OR `glpi_tickets`.`id` IN (SELECT `tickets_id` AS id FROM `glpi_groups_tickets`
//                        //            WHERE `type` = " . CommonITILActor::ASSIGN . " AND `groups_id` IN (" . implode(
//                        //                            ",",
//                        //                            $params['opt']['technicians_groups_id']
//                        //                        ) . ")))";
//
//                        $crit['crit']['technicians_groups_id'] = [['NOT' => ['glpi_tickets.id' => new QueryExpression("(SELECT tickets_id AS id FROM glpi_groups_tickets")]],
//                            ['glpi_tickets.id' => new QueryExpression("(SELECT tickets_id AS id FROM glpi_groups_tickets
//                WHERE type = " . CommonITILActor::ASSIGN . " AND groups_id IN (" . implode(
//                                ",",
//                                $params['opt']['technicians_groups_id']
//                            ) . ")))")]];
//
//                    } else {
//
//                        $crit['crit']['technicians_groups_id'] = ['glpi_tickets.id' => new QueryExpression("(SELECT tickets_id AS id FROM glpi_groups_tickets
//                WHERE type = " . CommonITILActor::ASSIGN . " AND groups_id IN (" . implode(
//                            ",",
//                            $params['opt']['technicians_groups_id']
//                        ) . ")))")];
//
//                        //                        $crit['crit']['technicians_groups_id'] .= " AND `glpi_tickets`.`id` IN (SELECT `tickets_id` AS id FROM `glpi_groups_tickets`
//                        //            WHERE `type` = " . CommonITILActor::ASSIGN . " AND `groups_id` IN (" . implode(
//                        //                            ",",
//                        //                            $params['opt']['technicians_groups_id']
//                        //                        ) . "))";
//
//                    }
//                    $opt['ancestors'] = 0;
//                    $crit['crit']['ancestors'] = 0;
//                }
//            }
//        }
//
//        //LOCATION
//        $opt['locations_id'] = 0;
//        $crit['crit']['locations_id'] = "";
//        $user = new User();
//        if (in_array("locations_id", $criterias)) {
//            if (isset($params['opt']["locations_id"])
//                && $params['opt']["locations_id"] > 0) {
//                $opt['locations_id'] = $params['opt']['locations_id'];
//                $crit['crit']['locations_id'] = ['glpi_tickets.locations_id' => $params['opt']["locations_id"]];
//            } elseif (isset($_SESSION['glpiactiveprofile']['interface'])
//                && Session::getCurrentInterface() != 'central' && $user->getFromDB(Session::getLoginUserID())) {
//                $opt['locations_id'] = $user->fields['locations_id'];
//                $crit['crit']['locations_id'] = ['glpi_tickets.locations_id' => $opt["locations_id"]];
//            }
//        }
//
//        // LOCATIONS
//        $opt['multiple_locations_id'] = [];
//        $crit['crit']['multiple_locations_id'] = "";
//        $opt['loc_ancestors'] = 0;
//        $crit['crit']['loc_ancestors'] = 0;
//
//        if (in_array("multiple_locations_id", $criterias)) {
//            if (isset($params['opt']['multiple_locations_id'])) {
//                $opt['multiple_locations_id'] = is_array(
//                    $params['opt']['multiple_locations_id']
//                ) ? $params['opt']['multiple_locations_id'] : [];
//            } else {
//                $crit['crit']['multiple_locations_id'] =  [];
//            }
//            $params['opt']['multiple_locations_id'] = $opt['multiple_locations_id'];
//
//            if (isset($params['opt']['multiple_locations_id'])
//                && is_array($params['opt']['multiple_locations_id'])
//                && count($params['opt']['multiple_locations_id']) > 0) {
//                if (isset($params['opt']['loc_ancestors']) && $params['opt']['loc_ancestors'] != 0) {
//                    $dbu = new DbUtils();
//                    $childs = [];
//                    foreach ($opt['multiple_locations_id'] as $k => $v) {
//                        $childs = $dbu->getSonsAndAncestorsOf('glpi_locations', $v);
//                    }
//                    $crit['crit']['multiple_locations_id'] = ['locations_id' => $childs];
//                    $opt['loc_ancestors'] = $params['opt']['loc_ancestors'];
//                    $crit['crit']['loc_ancestors'] = $opt['loc_ancestors'];
//                } else {
//                    $crit['crit']['multiple_locations_id'] = ['locations_id' => $params['opt']['multiple_locations_id']];
//                    $opt['loc_ancestors'] = 0;
//                    $crit['crit']['loc_ancestors'] = 0;
//                }
//            }
//        }
//
//        //TYPE
//        $opt['type'] = 0;
//        $crit['crit']['type'] = "";
//        if (in_array("type", $criterias)) {
//            if (isset($params['preferences']['prefered_type'])
//                && $params['preferences']['prefered_type'] > 0
//                && !isset($params['opt']['type'])) {
//                $opt['type'] = $params['preferences']['prefered_type'];
//                $crit['crit']['type'] = ['glpi_tickets.type' => $opt["type"]];
//            } elseif (isset($params['opt']['type'])
//                && $params['opt']['type'] > 0) {
//                $opt['type'] = $params['opt']['type'];
//                $crit['crit']['type'] = ['glpi_tickets.type' => $params['opt']["type"]];
//            }
//        }
//
//        //TYPE COMPUTER
//        $opt['type_computer'] = 0;
//        $crit['crit']['type_computer'] = "";
//        if (in_array("type_computer", $criterias)) {
//            if (isset($params['opt']['type_computer'])
//                && $params['opt']['type_computer'] > 0) {
//                $opt['type_computer'] = $params['opt']['type_computer'];
//                $crit['crit']['type_computer'] = ['glpi_computers.computertypes_id' => $params['opt']["type_computer"]];
//            }
//        }
//
//        // DATE
//        // MONTH
//        $year = intval(date('Y', time()));
//        $month = intval(date('m', time()) - 1);
//        $crit['crit']['year'] = $year;
//        if (in_array("month", $criterias)) {
//            if ($month > 0) {
//                $year = date('Y', time());
//                $opt["year"] = $year;
//            } else {
//                $month = 12;
//            }
//            if (isset($params['opt']["month"])
//                && $params['opt']["month"] > 0) {
//                $month = $params['opt']["month"];
//                $opt['month'] = $params['opt']['month'];
//            } else {
//                $opt["month"] = $month;
//            }
//        }
//
//        // YEAR
//        if (in_array("year", $criterias)) {
//            if (isset($params['opt']["year"])
//                && $params['opt']["year"] > 0) {
//                $year = $params['opt']["year"];
//                $opt['year'] = $params['opt']['year'];
//            } else {
//                $opt["year"] = $year;
//            }
//            $crit['crit']['year'] = $opt['year'];
//        }
//
//        // DISPLAY DATA
//
//        if (in_array("display_data", $criterias)) {
//            if (isset($params['opt']['display_data'])) {
//                $opt["display_data"] = $params['opt']['display_data'];
//                $crit['crit']['display_data'] = $params['opt']['display_data'];
//            } else {
//                $opt["display_data"] = "YEAR";
//                $crit['crit']['display_data'] = "YEAR";
//            }
//
//            if ($opt["display_data"] == "YEAR") {
//                $year = intval(date('Y', time()));
//                if (isset($params['opt']["year"])
//                    && $params['opt']["year"] > 0) {
//                    $year = $params['opt']["year"];
//                    $opt['year'] = $params['opt']['year'];
//                } else {
//                    $opt["year"] = $year;
//                }
//                $crit['crit']['year'] = $opt['year'];
//            } elseif ($opt["display_data"] == "START_END") {
//                if (isset($params['opt']["start_month"])
//                    && $params['opt']["start_month"] > 0) {
//                    $opt['start_month'] = $params['opt']['start_month'];
//                    $crit['crit']['start_month'] = $params['opt']['start_month'];
//                } else {
//                    $opt["start_month"] = date("m");
//                    $crit['crit']['start_month'] = date("m");
//                }
//
//                if (isset($params['opt']["start_year"])
//                    && $params['opt']["start_year"] > 0) {
//                    $opt['start_year'] = $params['opt']['start_year'];
//                    $crit['crit']['start_year'] = $params['opt']['start_year'];
//                } else {
//                    $opt["start_year"] = date("Y");
//                    $crit['crit']['start_year'] = date("Y");
//                }
//
//                if (isset($params['opt']["end_month"])
//                    && $params['opt']["end_month"] > 0) {
//                    $opt['end_month'] = $params['opt']['end_month'];
//                    $crit['crit']['end_month'] = $params['opt']['end_month'];
//                } else {
//                    $opt["end_month"] = date("m");
//                    $crit['crit']['end_month'] = date("m");
//                }
//
//                if (isset($params['opt']["end_year"])
//                    && $params['opt']["end_year"] > 0) {
//                    $opt['end_year'] = $params['opt']['end_year'];
//                    $crit['crit']['end_year'] = $params['opt']['end_year'];
//                } else {
//                    $opt["end_year"] = date("Y");
//                    $crit['crit']['end_year'] = date("Y");
//                }
//            }
//        }
//
//        if (in_array("filter_date", $criterias)) {
//            if (isset($params['opt']['filter_date'])) {
//                $opt["filter_date"] = $params['opt']['filter_date'];
//                $crit['crit']['filter_date'] = $params['opt']['filter_date'];
//            } else {
//                $opt["filter_date"] = "YEAR";
//                $crit['crit']['filter_date'] = "YEAR";
//            }
//
//            if ($opt["filter_date"] == "YEAR") {
//                $year = intval(date('Y', time()));
//                if (isset($params['opt']["year"])
//                    && $params['opt']["year"] > 0) {
//                    $year = $params['opt']["year"];
//                    $opt['year'] = $params['opt']['year'];
//                } else {
//                    $opt["year"] = $year;
//                }
//                $crit['crit']['year'] = $opt['year'];
//
//                $crit['crit']['date'] = [['glpi_tickets.date' => ['>=', $year . '-01-01 00:00:01']],
//                    ['glpi_tickets.date' => ['<=', new QueryExpression("ADDDATE('" . $year . "-12-31 00:00:00' , INTERVAL 1 DAY)")]]];
//
//                $crit['crit']['closedate'] = [['glpi_tickets.closedate' => ['>=', $year . '-01-01 00:00:01']],
//                    ['glpi_tickets.closedate' => ['<=', new QueryExpression("ADDDATE('" . $year . "-12-31 00:00:00' , INTERVAL 1 DAY)")]]];
//
//                $crit['crit']['satisfactiondate'] = [['glpi_ticketsatisfactions.date_answered' => ['>=', $year . '-01-01 00:00:01']],
//                    ['glpi_ticketsatisfactions.date_answered' => ['<=', new QueryExpression("ADDDATE('" . $year . "-12-31 00:00:00' , INTERVAL 1 DAY)")]]];
//
//
//
//            } elseif ($opt["filter_date"] == "BEGIN_END") {
//                if (isset($params['opt']['begin'])
//                    && $params['opt']["begin"] > 0) {
//                    $opt["begin"] = $params['opt']['begin'];
//                    $crit['crit']['begin'] = $params['opt']['begin'];
//                } else {
//                    $opt["begin"] = date("Y-m-d H:i:s");
//                }
//
//                if (isset($params['opt']['end'])
//                    && $params['opt']["end"] > 0) {
//                    $opt["end"] = $params['opt']['end'];
//                    $crit['crit']['end'] = $params['opt']['end'];
//                } else {
//                    $opt["end"] = date("Y-m-d H:i:s");
//                }
//                $end = $opt["end"];
//                $start = $opt["begin"];
//
//                $crit['crit']['date'] = [['glpi_tickets.date' => ['>=', $start]],
//                    ['glpi_tickets.date' => ['<=', $end]]];
//
//                $crit['crit']['closedate'] = [['glpi_tickets.closedate' => ['>=', $start]],
//                    ['glpi_tickets.closedate' => ['<=', $end]]];
//
//                $crit['crit']['satisfactiondate'] = [['glpi_ticketsatisfactions.date_answered' => ['>=', $start]],
//                    ['glpi_ticketsatisfactions.date_answered' => ['<=', $end]]];
//
//            }
//        }
//
//        // BEGIN DATE
//        if (in_array("begin", $criterias)) {
//            if (isset($params['opt']['begin'])
//                && $params['opt']["begin"] > 0) {
//                $opt["begin"] = $params['opt']['begin'];
//                $crit['crit']['begin'] = $params['opt']['begin'];
//            } else {
//                $opt["begin"] = date("Y-m-d H:i:s");
//            }
//        }
//
//        // END DATE
//        if (in_array("end", $criterias)) {
//            if (isset($params['opt']['end'])
//                && $params['opt']["end"] > 0) {
//                $opt["end"] = $params['opt']['end'];
//                $crit['crit']['end'] = $params['opt']['end'];
//            } else {
//                $opt["end"] = date("Y-m-d H:i:s");
//            }
//        }
//
//        if (!in_array('filter_date', $criterias)) {
//            $nbdays = date("t", mktime(0, 0, 0, $month, 1, $year));
//
//            $crit['crit']['date'] = [['glpi_tickets.date' => ['>=', $year - $month . '-01 00:00:01']],
//                ['glpi_tickets.date' => ['<=', new QueryExpression("ADDDATE('$year-$month-$nbdays 00:00:00' , INTERVAL 1 DAY)")]]];
//
//            $crit['crit']['closedate'] = [['glpi_tickets.closedate' => ['>=', $year - $month . '-01 00:00:01']],
//                ['glpi_tickets.closedate' => ['<=', new QueryExpression("ADDDATE('$year-$month-$nbdays 00:00:00' , INTERVAL 1 DAY)")]]];
//
//            $crit['crit']['satisfactiondate'] = [['glpi_ticketsatisfactions.date_answered' => ['>=', $year - $month . '-01 00:00:01']],
//                ['glpi_ticketsatisfactions.date_answered' => ['<=', new QueryExpression("ADDDATE('$year-$month-$nbdays 00:00:00' , INTERVAL 1 DAY)")]]];
//
//        }
//
//        if (!in_array("month", $criterias) && !in_array('filter_date', $criterias)) {
//
//            $crit['crit']['date'] = [['glpi_tickets.date' => ['>=', $year . '-01-01 00:00:01']],
//                ['glpi_tickets.date' => ['<=', new QueryExpression("ADDDATE('$year-12-31 00:00:00' , INTERVAL 1 DAY)")]]];
//
//            $crit['crit']['closedate'] = [['glpi_tickets.closedate' => ['>=', $year . '-01-01 00:00:01']],
//                ['glpi_tickets.closedate' => ['<=', new QueryExpression("ADDDATE('$year-12-31 00:00:00' , INTERVAL 1 DAY)")]]];
//
//            $crit['crit']['satisfactiondate'] = [['glpi_ticketsatisfactions.date_answered' => ['>=', $year . '-01-01 00:00:01']],
//                ['glpi_ticketsatisfactions.date_answered' => ['<=', new QueryExpression("ADDDATE('$year-12-31 00:00:00' , INTERVAL 1 DAY)")]]];
//
//        }

//
//        // TECHNICIAN
//        $opt["technicians_id"] = 0;
//        if (in_array("technicians_id", $criterias)) {
//            if (isset($params['opt']['technicians_id'])) {
//                $opt["technicians_id"] = $params['opt']['technicians_id'];
//                $crit['crit']['technicians_id'] = $params['opt']['technicians_id'];
//            }
//        }
//
//        // TECHNICIAN MULTIPLE
//        $opt['multiple_technicians_id'] = [];
//        //        $crit['crit']['multiple_technicians_id'] = " AND 1 = 1 ";
//        if (in_array("multiple_technicians_id", $criterias)) {
//            if (isset($params['opt']['multiple_technicians_id'])) {
//                $opt['multiple_technicians_id'] = is_array(
//                    $params['opt']['multiple_technicians_id']
//                ) ? $params['opt']['multiple_technicians_id'] : [];
//            } else {
//                $crit['crit']['multiple_technicians_id'] = [];
//            }
//            $params['opt']['multiple_technicians_id'] = $opt['multiple_technicians_id'];
//
//            if (isset($params['opt']['multiple_technicians_id'])
//                && is_array($params['opt']['multiple_technicians_id'])
//                && count($params['opt']['multiple_technicians_id']) > 0) {
//                $crit['crit']['multiple_technicians_id'] = $params['opt']['multiple_technicians_id'];
//            }
//        }
//
//        // LIMIT
//        if (in_array("limit", $criterias)) {
//            if (isset($params['opt']['limit'])) {
//                $opt["limit"] = $params['opt']['limit'];
//                $crit['crit']['limit'] = $params['opt']['limit'];
//            }
//        }
//
//        // MULTIPLE TIME
//        if (in_array("multiple_time", $criterias)) {
//            if (isset($params['opt']['multiple_time'])) {
//                $opt["multiple_time"] = $params['opt']['multiple_time'];
//                $crit['crit']['multiple_time'] = $params['opt']['multiple_time'];
//            } else {
//                $opt["multiple_time"] = "MONTH";
//                $crit['crit']['multiple_time'] = "MONTH";
//            }
//        }
//
//        // MULTIPLE YEAR TIME
//        if (in_array("multiple_year_time", $criterias)) {
//            if (isset($params['opt']['multiple_year_time'])) {
//                $opt["multiple_year_time"] = $params['opt']['multiple_year_time'];
//                $crit['crit']['multiple_year_time'] = $params['opt']['multiple_year_time'];
//            } else {
//                $opt["multiple_year_time"] = "LASTMONTH";
//                $crit['crit']['multiple_year_time'] = "LASTMONTH";
//            }
//            if (isset($params['opt']['month_year'])) {
//                $opt["month_year"] = $params['opt']['month_year'];
//                $crit['crit']['month_year'] = $params['opt']['month_year'];
//            }
//        }
//
//
//        // STATUS
//        $default = [
//            CommonITILObject::INCOMING,
//            CommonITILObject::ASSIGNED,
//            CommonITILObject::PLANNED,
//            CommonITILObject::WAITING,
//        ];
//        $crit['crit']['status'] = $default;
//        $opt['status'] = $default;
//        if (in_array("status", $criterias)) {
//            $status = [];
//
//            if (isset($params['opt']["status_1"])
//                && $params['opt']["status_1"] > 0) {
//                $status[] = CommonITILObject::INCOMING;
//            }
//            if (isset($params['opt']["status_2"])
//                && $params['opt']["status_2"] > 0) {
//                $status[] = CommonITILObject::ASSIGNED;
//            }
//            if (isset($params['opt']["status_3"])
//                && $params['opt']["status_3"] > 0) {
//                $status[] = CommonITILObject::PLANNED;
//            }
//            if (isset($params['opt']["status_4"])
//                && $params['opt']["status_4"] > 0) {
//                $status[] = CommonITILObject::WAITING;
//            }
//            if (isset($params['opt']["status_5"])
//                && $params['opt']["status_5"] > 0) {
//                $status[] = CommonITILObject::SOLVED;
//            }
//            if (isset($params['opt']["status_6"])
//                && $params['opt']["status_6"] > 0) {
//                $status[] = CommonITILObject::CLOSED;
//            }
//
//            if (count($status) > 0) {
//                $opt['status'] = $status;
//                $crit['crit']['status'] = $status;
//            }
//        }
//        //ITILCATEGORY_LVL1
//        $opt['itilcategorielvl1'] = 0;
//        //        $crit['crit']['itilcategorielvl1'] = " AND 1 = 1 ";
//        if (in_array("itilcategorielvl1", $criterias)) {
//            if (isset($params['preferences']['prefered_category'])
//                && $params['preferences']['prefered_category'] > 0 && !isset($params['opt']['itilcategorielvl1'])) {
//                $opt['itilcategorielvl1'] = $params['preferences']['prefered_category'];
//            } elseif (isset($params['opt']["itilcategorielvl1"])
//                && $params['opt']["itilcategorielvl1"] > 0) {
//                $opt['itilcategorielvl1'] = $params['opt']['itilcategorielvl1'];
//            }
//            $category = new ITILCategory();
//            $catlvl2 = $category->find(
//                ['itilcategories_id' => $opt['itilcategorielvl1'], 'is_request' => 1, 'is_incident' => 1]
//            );
//            $listcat = [];
//            $listcat[] = $opt['itilcategorielvl1'];
//            foreach ($catlvl2 as $cat) {
//                $listcat[] = $cat['id'];
//            }
//            $categories = implode(",", $listcat);
//            if (empty($listcat)) {
//                $listcat = "0";
//            }
//            $crit['crit']['itilcategorielvl1'] = ['glpi_tickets.itilcategories_id' => $categories];
//        }
//
//        //ITILCATEGORY
//        $opt['itilcategories_id'] = 0;
//        //        $crit['crit']['itilcategories_id'] = " AND 1 = 1";
//        if (in_array("itilcategories_id", $criterias)) {
//            if (isset($params['preferences']['prefered_category'])
//                && $params['preferences']['prefered_category'] > 0 && !isset($params['opt']['itilcategories_id'])) {
//                $opt['itilcategories_id'] = $params['preferences']['prefered_category'];
//            } elseif (isset($params['opt']["itilcategories_id"])
//                && $params['opt']["itilcategories_id"] > 0) {
//                $opt['itilcategories_id'] = $params['opt']['itilcategories_id'];
//            }
//            if ($opt['itilcategories_id'] > 0) {
//                $category = new ITILCategory();
//                if ($category->getFromDB($opt['itilcategories_id'])) {
//                    $crit['crit']['itilcategories_id'] = ['glpi_tickets.itilcategories_id' => $opt['itilcategories_id']];
//                }
//            } else {
//                //                $crit['crit']['itilcategories_id'] = " AND 1 = 1 ";
//            }
//        }
//
//        //TAG
//        $opt['tag'] = 0;
//        //        $crit['crit']['tag'] = "AND 1 = 1";
//        if (in_array("tag", $criterias)) {
//            if (isset($params['opt']["tag"])
//                && $params['opt']["tag"] > 0) {
//                $opt['tag'] = $params['opt']['tag'];
//
//                $crit['crit']['tag'] = ['glpi_plugin_tag_tagitems.plugin_tag_tags_id' => $opt['tag']];
//            }
//        }
//
//        $crit['opt'] = $opt;
//
//        return $crit;
//    }

}
