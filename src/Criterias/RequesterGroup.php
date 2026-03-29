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

namespace GlpiPlugin\Mydashboard\Criterias;

use CommonITILActor;
use Dropdown;
use GlpiPlugin\Mydashboard\Criteria;
use GlpiPlugin\Mydashboard\Preference;
use Group;
use Session;

/**
 * Class RequesterGroup
 */
class RequesterGroup
{
    public static $criteria_name = 'requesters_groups_id';
    public static $criteria_number = 71;

    public static function getDefaultValue()
    {

        $requesters_groups_id = [];

        $preference = new Preference();
        if (!$preference->getFromDB(Session::getLoginUserID())) {
            $preference->initPreferences(Session::getLoginUserID());
        }
        if (isset($preferences['requester_prefered_group'])) {
            $preferences['requester_prefered_group'] = \Safe\json_decode($preferences['requester_prefered_group'], true);
            if (is_array($preferences['requester_prefered_group'])
                && count($preferences['requester_prefered_group']) > 0) {
                $requesters_groups_id = $preferences['requester_prefered_group'];
            }
        }
        return $requesters_groups_id;

    }

    public static function getDisplayValue($requesters_groups_id)
    {

        $requesters_groups_id = is_array(
            $requesters_groups_id
        ) ? $requesters_groups_id : [];

        $requesters_groups_id = array_filter($requesters_groups_id);

        $form = "";

        if (is_array($requesters_groups_id)
            && count($requesters_groups_id) > 0) {

            $form = "&nbsp;/&nbsp;" . __('Requester group') . "&nbsp;:&nbsp;";
            foreach ($requesters_groups_id as $k => $v) {
                $form .= Dropdown::getDropdownName('glpi_groups', $v);
                if (count($requesters_groups_id) > 1) {
                    $form .= "&nbsp;-&nbsp;";
                }
            }
        }

        return $form;
    }

    public static function getDisplayForm($default, $opt, $count)
    {

        $form = "<span class='md-widgetcrit'>";

        $result = getAllDataFromTable(
            Group::getTable(),
            ['is_requester' => 1, 'ORDER' => "completename"],
            false
        );

        if (isset($opt[self::$criteria_name])) {
            $requesters_groups_id = (is_array(
                $opt[self::$criteria_name]
            ) ? $opt[self::$criteria_name] : []);
        } else {
            $requesters_groups_id = [];
        }

        $temp = [];
        foreach ($result as $item) {
            $temp[$item['id']] = $item['completename'];
        }

        $params = [
            "name" => self::$criteria_name,
            "display" => false,
            "multiple" => true,
            "width" => '200px',
            'values' => $requesters_groups_id ?? $default[self::$criteria_name],
            'display_emptychoice' => true,
        ];


        $form .= __('Requester group');
        $form .= "&nbsp;";

        $dropdown = Dropdown::showFromArray("requesters_groups_id", $temp, $params);

        $form .= $dropdown;

        $form .= "</span>";
        if ($count > 1) {
            $form .= "</br></br>";
        }

        return $form;
    }

    public static function getQueryLeftJoin($params, $table)
    {

        return $params['query']['LEFT JOIN'] + [
            'glpi_groups_tickets' => [
                'ON' => [
                    $table => 'id',
                    'glpi_groups_tickets' => 'tickets_id',
                    [
                        'AND' => [
                            'glpi_groups_tickets.type' => CommonITILActor::REQUESTER,
                        ],
                    ],
                ],
            ],
        ];

    }

    public static function getQueryCriteria($params)
    {

        return $params['query']['WHERE'] + ['glpi_groups_tickets.groups_id' => $params[self::$criteria_name]];
    }

    public static function getSearchCriteria($params, $value = 0)
    {

        return Criteria::addUrlGroupCriteria(self::$criteria_number, 'equals', $params["params"][self::$criteria_name]);

    }
}
