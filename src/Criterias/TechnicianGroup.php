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
 * Class TechnicianGroup
 */
class TechnicianGroup
{
    public static $criteria_name = 'technicians_groups_id';
    public static $criteria_number = 8;

    public static function getDefaultValue()
    {

        $technicians_groups_id = [];

        $preference = new Preference();
        if (!$preference->getFromDB(Session::getLoginUserID())) {
            $preference->initPreferences(Session::getLoginUserID());
        }
        if (isset($preferences['prefered_group'])) {
            $preferences['prefered_group'] = \Safe\json_decode($preferences['prefered_group'], true);
            if (is_array($preferences['prefered_group'])
                && count($preferences['prefered_group']) > 0) {
                $technicians_groups_id = $preferences['prefered_group'];
            }
        }
        return $technicians_groups_id;

    }

    public static function getDisplayValue($opt)
    {

        $technicians_groups_id = is_array(
            $opt[self::$criteria_name]
        ) ? $opt[self::$criteria_name] : [];

        $technicians_groups_id = array_filter($technicians_groups_id);

        $form = "";

        if (is_array($technicians_groups_id)
            && count($technicians_groups_id) > 0) {

            $form = "&nbsp;/&nbsp;" . __('Technician group') . "&nbsp;:&nbsp;";
            foreach ($technicians_groups_id as $k => $v) {
                $form .= Dropdown::getDropdownName('glpi_groups', $v);
                if (count($technicians_groups_id) > 1) {
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
            ['is_assign' => 1, 'ORDER' => "completename"],
            false
        );

        if (isset($opt[self::$criteria_name])) {
            $technicians_groups_id = (is_array(
                $opt[self::$criteria_name]
            ) ? $opt[self::$criteria_name] : []);
        } else {
            $technicians_groups_id = [];
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
            'values' => $technicians_groups_id ?? $default[self::$criteria_name],
            'display_emptychoice' => true,
        ];


        $form .= __('Technician group');
        $form .= "&nbsp;";

        $dropdown = Dropdown::showFromArray(self::$criteria_name, $temp, $params);

        $form .= $dropdown;

        $form .= "</span>";
        if ($count > 1) {
            $form .= "</br></br>";
        }

        $sons = $opt['is_recursive_technicians'] ?? $default['is_recursive_technicians'];
        if ($sons > 0) {
            $form .= "<span class='md-widgetcrit'>";
            $form .= __('Child groups') . "&nbsp;";
            $paramsy = ['display' => false];
            $ancestors = $opt['is_recursive_technicians'] ?? $default['is_recursive_technicians'];
            $form .= Dropdown::showYesNo('is_recursive_technicians', $ancestors, -1, $paramsy);
            $form .= "</span>";
            if ($count > 1) {
                $form .= "</br></br>";
            }
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
                            'glpi_groups_tickets.type' => CommonITILActor::ASSIGN,
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

        return Criteria::addUrlGroupCriteria(
            self::$criteria_number,
            ((isset($params["params"]["is_recursive_technicians"])
                && !empty($params["params"]["is_recursive_technicians"])) ? 'under' : 'equals'),
            $params["params"][self::$criteria_name]
        );

    }
}
