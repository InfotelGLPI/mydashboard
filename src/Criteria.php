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

use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Mydashboard\Criterias\ComputerType;
use GlpiPlugin\Mydashboard\Criterias\DisplayData;
use GlpiPlugin\Mydashboard\Criterias\Entity;
use GlpiPlugin\Mydashboard\Criterias\FilterDate;
use GlpiPlugin\Mydashboard\Criterias\ITILCategory;
use GlpiPlugin\Mydashboard\Criterias\Limit;
use GlpiPlugin\Mydashboard\Criterias\Location;
use GlpiPlugin\Mydashboard\Criterias\Month;
use GlpiPlugin\Mydashboard\Criterias\MultipleLocation;
use GlpiPlugin\Mydashboard\Criterias\Priority;
use GlpiPlugin\Mydashboard\Criterias\RequesterGroup;
use GlpiPlugin\Mydashboard\Criterias\Status;
use GlpiPlugin\Mydashboard\Criterias\Technician;
use GlpiPlugin\Mydashboard\Criterias\TechnicianGroup;
use GlpiPlugin\Mydashboard\Criterias\Type;
use GlpiPlugin\Mydashboard\Criterias\Week;
use GlpiPlugin\Mydashboard\Criterias\Year;
use Session;
use Toolbox;

class Criteria
{
    public static $criterias_list = [
        'entities_id',
        'is_recursive_entities',
        'type',
        'locations_id',
        'status',
        'priority',
        'technicians_groups_id',
        'is_recursive_technicians',
        'requesters_groups_id',
        'is_recursive_requesters',
        'technicians_id',
        'itilcategories_id',
        'computertypes_id',
        'year',
        'month',
        'week',
        'limit',
        'multiple_time',
        'display_data',
        'filter_date',
        'multiple_locations_id',
        'is_recursive_locations',
    ];

    public const PRIORITY = 3;
    public const STATUS = 12;
    public const OPEN_DATE = 15;
    public const CLOSE_DATE = 16;
    public const SOLVE_DATE = 17;
    public const TASK_ACTIONTIME = 96;
    public const VALIDATION_STATS = 55;
    public const VALIDATION_REFUSED = 4;
    public const NUMBER_OF_PROBLEMS = 200;
    public const SATISFACTION_DATE = 61;
    public const SATISFACTION_VALUE = 62;
    public const BUY_DATE = 37;

    public const INVENTORY_DATE = 9;

    public const MORETICKET_WAITINGTYPE = 3452;

    public const OCSINVENTORYNG_IMPORTDATE = 10002;

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
            Year::$criteria_name,
        ];

        // Most restrictive default: with no interface in session, only expose the
        // criterion every interface shares.
        $criterias = [Type::$criteria_name];

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

    public static function getGraphCriterias($params, $table = 'glpi_tickets')
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
        //For Filter_Date criteria
        if (isset($opt['year'])) {
            $criterias_values['year'] = $opt['year'];
        }
        if (isset($opt['begin'])) {
            $criterias_values['begin'] = $opt['begin'];
        }
        if (isset($opt['end'])) {
            $criterias_values['end'] = $opt['end'];
        }

        $options['reset'][] = 'reset';

        if ($table == 'glpi_tickets' && isset($criterias_values[Type::$criteria_name])
            && $criterias_values[Type::$criteria_name] > 0) {
            $values['params'][Type::$criteria_name] = $criterias_values[Type::$criteria_name];
            $options = Type::getSearchCriteria($values);
        }
        if (isset($criterias_values[Year::$criteria_name])
            && isset($criterias_values[Year::$criteria_name])) {
            $values['params'][Year::$criteria_name] = $criterias_values[Year::$criteria_name];
            $options = Year::getSearchCriteria($values);
        }

        if (isset($criterias_values[RequesterGroup::$criteria_name])
            && is_array($criterias_values[RequesterGroup::$criteria_name])
            && count($criterias_values[RequesterGroup::$criteria_name]) > 0) {
            $values['params'][RequesterGroup::$criteria_name] = $criterias_values[RequesterGroup::$criteria_name];
            $options = RequesterGroup::getSearchCriteria($values);
        }
        if (isset($criterias_values[TechnicianGroup::$criteria_name])
            && is_array($criterias_values[TechnicianGroup::$criteria_name])
            && count($criterias_values[TechnicianGroup::$criteria_name]) > 0) {
            $values['params'][TechnicianGroup::$criteria_name] = $criterias_values[TechnicianGroup::$criteria_name];
            $options = TechnicianGroup::getSearchCriteria($values);
        }

        $values['params'][Entity::$criteria_name] = $criterias_values[Entity::$criteria_name] ?? 0;

        $values['params']['is_recursive_entities'] = $criterias_values['is_recursive_entities'] ?? 0;
        $values['params']['is_recursive_technicians'] = $criterias_values['is_recursive_technicians'] ?? 0;

        $options = Entity::getSearchCriteria($values);

        return $options;
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
        if (in_array(TechnicianGroup::$criteria_name, $params['criterias']) && $table == 'glpi_tickets') {
            $technicians_groups_id = $opt[TechnicianGroup::$criteria_name] ?? $default[TechnicianGroup::$criteria_name];
        }
        if (in_array("is_recursive_technicians", $params['criterias']) && $table == 'glpi_tickets') {
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
        if (in_array(Status::$criteria_name, $params['criterias'])) {
            $status = $opt[Status::$criteria_name] ?? $default[Status::$criteria_name];
        }
        if (in_array(Priority::$criteria_name, $params['criterias'])) {
            $priority = $opt[Priority::$criteria_name] ?? $default[Priority::$criteria_name];
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
        if (in_array(MultipleLocation::$criteria_name, $params['criterias']) && $table == 'glpi_tickets') {
            $multiple_locations_id = $opt[MultipleLocation::$criteria_name] ?? $default[MultipleLocation::$criteria_name];
        }
        if (in_array("is_recursive_locations", $params['criterias']) && $table == 'glpi_tickets') {
            $is_recursive_locations = $opt['is_recursive_locations'] ?? $default['is_recursive_locations'];
        }
        if (in_array(Year::$criteria_name, $params['criterias']) && $table == 'glpi_tickets') {
            $year = $opt[Year::$criteria_name] ?? $default[Year::$criteria_name];
        }
        if (in_array(Month::$criteria_name, $params['criterias']) && $table == 'glpi_tickets') {
            $month = $opt[Month::$criteria_name] ?? $default[Month::$criteria_name];
        }

        foreach ($params['criterias'] as $criterion) {
            if ($criterion == Entity::$criteria_name && !is_array($entities_id)) {
                $entities_id = [$entities_id];
            }
            if ($criterion == Entity::$criteria_name && is_array($entities_id)) {
                $params_query['criteria'] = $criterion;
                $params_query['query'] = $query;
                $params_query[$criterion] = $entities_id;
                $params_query['is_recursive_entities'] = $is_recursive_entities;
                $query = self::defineLeftjoinByCriteria($params_query, $table);

                $params_query['query'] = $query;
                $query = self::defineWhereByCriteria($params_query, $table);
            }

            if ($table == 'glpi_tickets' && $criterion == TechnicianGroup::$criteria_name && is_array(
                $technicians_groups_id,
            )) {
                $technicians_groups_id = array_filter($technicians_groups_id);

                if (count($technicians_groups_id) > 0) {
                    $params_query['criteria'] = $criterion;
                    $params_query['query'] = $query;
                    $params_query[$criterion] = $technicians_groups_id;
                    $params_query['is_recursive_technicians'] = $is_recursive_technicians ?? 0;
                    $query = self::defineLeftjoinByCriteria($params_query, $table);

                    $params_query['query'] = $query;
                    $query = self::defineWhereByCriteria($params_query, $table);
                }
            }

            if ($table == 'glpi_tickets' && $criterion == RequesterGroup::$criteria_name && is_array(
                $requesters_groups_id,
            )) {
                $requesters_groups_id = array_filter($requesters_groups_id);

                if (count($requesters_groups_id) > 0) {
                    $params_query['criteria'] = $criterion;
                    $params_query['query'] = $query;
                    $params_query[$criterion] = $requesters_groups_id;
                    $params_query['is_recursive_requesters'] = $is_recursive_requesters ?? 0;
                    $query = self::defineLeftjoinByCriteria($params_query, $table);

                    $params_query['query'] = $query;
                    $query = self::defineWhereByCriteria($params_query, $table);
                }
            }

            if ($table == 'glpi_tickets' && $criterion == Technician::$criteria_name) {
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

            if ($table == 'glpi_tickets' && $criterion == Status::$criteria_name && $status > 0) {
                $params_query['criteria'] = $criterion;
                $params_query['query'] = $query;
                $params_query[$criterion] = $status;
                $query = self::defineWhereByCriteria($params_query, $table);
            }

            if ($table == 'glpi_tickets' && $criterion == Priority::$criteria_name && $priority > 0) {
                $params_query['criteria'] = $criterion;
                $params_query['query'] = $query;
                $params_query[$criterion] = $priority;
                $query = self::defineWhereByCriteria($params_query, $table);
            }

            if ($table == 'glpi_tickets' && $criterion == ITILCategory::$criteria_name && $itilcategories_id > 0) {
                $params_query['criteria'] = $criterion;
                $params_query['query'] = $query;
                $params_query[$criterion] = $itilcategories_id;
                $query = self::defineWhereByCriteria($params_query, $table);
            }

            if ($table == 'glpi_tickets' && $criterion == Year::$criteria_name && $year > 0 && (!isset($month) || $month == 0)) {
                $params_query['criteria'] = $criterion;
                $params_query['query'] = $query;
                $params_query[$criterion] = $year;
                $query = self::defineWhereByCriteria($params_query, $table);
            }

            if ($table == 'glpi_tickets' && $criterion == Month::$criteria_name && $month > 0 && $year > 0) {
                $params_query['criteria'] = $criterion;
                $params_query['query'] = $query;
                $params_query[$criterion] = $month;
                $params_query[Year::$criteria_name] = $year;
                $query = self::defineWhereByCriteria($params_query, $table);
            }

            if ($table != 'glpi_tickets' && $criterion == ComputerType::$criteria_name && $computertypes_id > 0) {
                $params_query['criteria'] = $criterion;
                $params_query['query'] = $query;
                $params_query[$criterion] = $computertypes_id;
                $query = self::defineWhereByCriteria($params_query, $table);
            }

            if ($criterion == MultipleLocation::$criteria_name && is_array($multiple_locations_id)) {
                $multiple_locations_id = array_filter($multiple_locations_id);

                if (count($multiple_locations_id) > 0) {
                    $params_query['criteria'] = $criterion;
                    $params_query['query'] = $query;
                    $params_query[$criterion] = $multiple_locations_id;
                    $params_query['is_recursive_locations'] = $is_recursive_locations;
                    $query = self::defineWhereByCriteria($params_query, $table);
                }
            }
        }

        // Fail closed on the entity scope.
        //
        // The restriction above only runs when Entity::$criteria_name belongs to
        // $params['criterias'], but that same list also drives the filters rendered by
        // getForm(). Outside the central interface, getDefaultCriterias() therefore
        // drops entities_id to hide the entity selector from self-service, which used
        // to drop the restriction along with it: a helpdesk user could aggregate the
        // tickets of every entity. Any widget family that simply forgets to declare
        // the criterion was exposed the same way.
        //
        // So whenever no entity clause reached the WHERE, apply the session
        // restriction here.
        if (self::queryUsesTable($query, $table)
            && !self::hasEntityRestriction($query['WHERE'] ?? [], $table)) {
            $query['WHERE'] = ($query['WHERE'] ?? []) + getEntitiesRestrictCriteria($table);
        }

        return $query;
    }

    /**
     * Is the table holding entities_id actually queried?
     *
     * addCriteriasForQuery() defaults to 'glpi_tickets', yet a few widgets start from
     * another table without joining the tickets (see Urgo and the timelineticket plugin
     * table). Adding a glpi_tickets.entities_id condition there would build invalid SQL,
     * so the fallback restriction is only applied when the table really appears in the
     * FROM or in a join.
     *
     * @param array  $query
     * @param string $table
     *
     * @return bool
     */
    private static function queryUsesTable($query, $table)
    {
        if (isset($query['FROM'])) {
            if (is_string($query['FROM']) && $query['FROM'] === $table) {
                return true;
            }
            if (is_array($query['FROM']) && in_array($table, $query['FROM'], true)) {
                return true;
            }
        }

        foreach (['JOIN', 'LEFT JOIN', 'INNER JOIN', 'RIGHT JOIN'] as $join_type) {
            if (isset($query[$join_type])
                && is_array($query[$join_type])
                && array_key_exists($table, $query[$join_type])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Does the WHERE already carry an entity condition on that table?
     *
     * Two shapes coexist. Entity::getQueryCriteria() writes a flat
     * "<table>.entities_id" key, while getEntitiesRestrictCriteria() -- which several
     * widgets call on their own -- nests the very same key under a random integer key
     * to avoid collisions. Both must be recognised, otherwise the fallback would stack
     * a redundant second restriction on top of an existing one.
     *
     * @param array  $where
     * @param string $table
     *
     * @return bool
     */
    private static function hasEntityRestriction($where, $table)
    {
        $field = $table . '.' . Entity::$criteria_name;

        if (!is_array($where)) {
            return false;
        }
        if (array_key_exists($field, $where)) {
            return true;
        }

        foreach ($where as $value) {
            if (is_array($value) && self::hasEntityRestriction($value, $table)) {
                return true;
            }
        }

        return false;
    }

    public static function defineLeftjoinByCriteria($params, $table = 'glpi_tickets')
    {
        $query = $params['query'];

        if ($params['criteria'] == Entity::$criteria_name) {
            if (!isset($params['query']['LEFT JOIN'])) {
                $params['query']['LEFT JOIN'] = [];
            }
            $query['LEFT JOIN'] = Entity::getQueryLeftJoin($params, $table);
        } elseif ($params['criteria'] == TechnicianGroup::$criteria_name) {
            $technician_group = array_filter($params[TechnicianGroup::$criteria_name]);
            if (count($technician_group) > 0) {
                if (!isset($params['query']['LEFT JOIN'])) {
                    $params['query']['LEFT JOIN'] = [];
                }
                $query['LEFT JOIN'] = TechnicianGroup::getQueryLeftJoin($params, $table);
            }
        } elseif ($params['criteria'] == RequesterGroup::$criteria_name) {
            $requester_groups = array_filter($params[RequesterGroup::$criteria_name]);
            if (count($requester_groups) > 0) {
                if (!isset($params['query']['LEFT JOIN'])) {
                    $params['query']['LEFT JOIN'] = [];
                }
                $query['LEFT JOIN'] = RequesterGroup::getQueryLeftJoin($params, $table);
            }
        } elseif ($params['criteria'] == Technician::$criteria_name) {
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
        } elseif ($params['criteria'] == Status::$criteria_name) {
            $query['WHERE'] = Status::getQueryCriteria($params);
        } elseif ($params['criteria'] == Priority::$criteria_name) {
            $query['WHERE'] = Priority::getQueryCriteria($params);
        } elseif ($params['criteria'] == MultipleLocation::$criteria_name) {
            $query['WHERE'] = MultipleLocation::getQueryCriteria($params);
        } elseif ($params['criteria'] == Year::$criteria_name) {
            $query['WHERE'] = Year::getQueryCriteria($params);
        } elseif ($params['criteria'] == Month::$criteria_name) {
            $query['WHERE'] = Month::getQueryCriteria($params);
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
            'field' => $field,
            'searchtype' => $searchType,
            'value' => $value,
            'link' => $link,
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
            && is_array($value)
            && count($value) > 0) {
            $groups = $value;
            $nb = 0;
            foreach ($groups as $group) {
                $criterias['criteria'][$nb] = [
                    'field' => $field,
                    'searchtype' => $searchType,
                    'value' => $group,
                    'link' => (($nb == 0) ? 'AND' : 'OR'),
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
            Entity::$criteria_name => Entity::class,
            Type::$criteria_name => Type::class,
            TechnicianGroup::$criteria_name => TechnicianGroup::class,
            RequesterGroup::$criteria_name => RequesterGroup::class,
            Limit::$criteria_name => Limit::class,
            Location::$criteria_name => Location::class,
            Status::$criteria_name => Status::class,
            Priority::$criteria_name => Priority::class,
            Technician::$criteria_name => Technician::class,
            ITILCategory::$criteria_name => ITILCategory::class,
            Year::$criteria_name => Year::class,
            Month::$criteria_name => Month::class,
            Week::$criteria_name => Week::class,
            ComputerType::$criteria_name => ComputerType::class,
            MultipleLocation::$criteria_name => MultipleLocation::class,
            DisplayData::$criteria_name => DisplayData::class,
            FilterDate::$criteria_name => FilterDate::class,
        ];

        foreach ($used_criterias as $criteria => $class) {
            if (in_array($criteria, $criterias)) {
                $critClass = new $class();
                $default[$criteria] = $critClass::getDefaultValue();
            }
        }
        $default['is_recursive_entities'] = true;
        $default['is_recursive_technicians'] = false;
        $default['is_recursive_requesters'] = false;
        $default['is_recursive_locations'] = false;
        if (count($_SESSION['glpiactiveentities']) > 1) {
            $default['is_recursive_entities'] = true;
            $default['is_recursive_technicians'] = true;
            $default['is_recursive_requesters'] = true;
            $default['is_recursive_locations'] = true;
        }



        return $default;
    }

    /**
     * Get a form header, this form header permit to update data of the widget
     * with parameters of this form
     *
     * @param int $widgetId
     * @param       $gsid
     * @param bool $onsubmit
     *
     * @param array $opt
     *
     * @return string , like '<form id=...>'
     */
    /**
     * Render one labelled widget of the criteria filter bar.
     *
     * Every Criterias\*::getDisplayForm() used to inline this same span/spacing markup.
     *
     * @param string $label      criterion label, plain text
     * @param string $input_html markup of a GLPI dropdown called with 'display' => false
     * @param int    $count      number of criteria shown; above one they are stacked
     *
     * @return string
     */
    public static function getFieldHtml($label, $input_html, $count)
    {
        return TemplateRenderer::getInstance()->render('@mydashboard/criteria_field.html.twig', [
            'label' => $label,
            'input_html' => $input_html,
            'count' => $count,
        ]);
    }

    /**
     * The criteria classes, in display order.
     *
     * @return array criteria name => class
     */
    private static function getUsedCriterias()
    {
        return [
            Entity::$criteria_name => Entity::class,
            Type::$criteria_name => Type::class,
            TechnicianGroup::$criteria_name => TechnicianGroup::class,
            RequesterGroup::$criteria_name => RequesterGroup::class,
            Limit::$criteria_name => Limit::class,
            Location::$criteria_name => Location::class,
            Status::$criteria_name => Status::class,
            Priority::$criteria_name => Priority::class,
            Technician::$criteria_name => Technician::class,
            ITILCategory::$criteria_name => ITILCategory::class,
            Year::$criteria_name => Year::class,
            Month::$criteria_name => Month::class,
            Week::$criteria_name => Week::class,
            ComputerType::$criteria_name => ComputerType::class,
            MultipleLocation::$criteria_name => MultipleLocation::class,
            DisplayData::$criteria_name => DisplayData::class,
            FilterDate::$criteria_name => FilterDate::class,
        ];
    }

    public static function getForm($widgetId, $default, $opt, $criterias, $onsubmit = false)
    {
        $gsid = Widget::getGsID($widgetId);
        $rand = mt_rand();
        if (count($opt) == 0) {
            $opt = $default;
        }

        $formId = uniqid('form');
        $count = count($criterias);

        $summary_html = '';
        $fields_html = '';
        foreach (self::getUsedCriterias() as $criteria => $class) {
            if (isset($opt[$criteria])) {
                $summary_html .= $class::getDisplayValue($opt);
            }
            if (in_array($criteria, $criterias)) {
                $fields_html .= $class::getDisplayForm($default, $opt, $count);
            }
        }

        $refresh_js = sprintf(
            "refreshWidgetByForm('%s','%s','%s');",
            Widget::removeBackslashes($widgetId),
            $gsid,
            $formId,
        );

        return TemplateRenderer::getInstance()->render('@mydashboard/criteria_form.html.twig', [
            'rand' => $rand,
            'form_id' => $formId,
            'on_submit' => (bool) $onsubmit,
            'refresh_js' => $refresh_js,
            'summary_html' => $summary_html,
            'fields_html' => $fields_html,
            'submit_html' => \Html::submit(_x('button', 'Send'), [
                'name' => 'submit',
                'class' => 'btn btn-primary',
            ]),
            'toggle_script_html' => \Html::scriptBlock("
                $(document).ready(function () {
                    $('#plugin_mydashboard_add_criteria{$rand}').on('click', function (e) {
                        $('#plugin_mydashboard_see_criteria{$rand}').width(300);
                        $('#plugin_mydashboard_see_criteria{$rand}').toggle();
                    });
                });"),
        ]);
    }

    /**
     * Construit une URL ticket.php avec les critères de base (issus du widget) et des critères spécifiques.
     * Les champs listés dans $strip sont exclus des critères de base avant la fusion.
     *
     * @param array    $options  Tableau $_POST reçu par la fonction link (contient params.criteria)
     * @param array    $specific Critères spécifiques à la barre/section cliquée
     * @param int[]    $strip    IDs de champs à exclure des critères de base (ex. OPEN_DATE)
     */
    public static function buildTicketUrl(array $options, array $specific, array $strip = []): string
    {
        global $CFG_GLPI;
        $base = array_values(array_filter(
            $options['params']['criteria'] ?? [],
            fn($c) => !in_array($c['field'] ?? null, $strip),
        ));
        $options['criteria'] = array_merge($base, $specific);
        return $CFG_GLPI['root_doc'] . '/front/ticket.php?is_deleted=0&' . Toolbox::append_params($options, '&');
    }

    /**
     * Construit une URL computer.php avec les critères de base et des critères spécifiques.
     *
     * @param array $options  Tableau $_POST reçu par la fonction link
     * @param array $specific Critères spécifiques à la section cliquée
     */
    public static function buildComputerUrl(array $options, array $specific): string
    {
        global $CFG_GLPI;
        $options['criteria'] = array_merge($options['params']['criteria'] ?? [], $specific);
        return $CFG_GLPI['root_doc'] . '/front/computer.php?is_deleted=0&' . Toolbox::append_params($options, '&');
    }
}
