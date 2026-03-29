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
use GlpiPlugin\Mydashboard\Criteria;
use User;

/**
 * Class Technician
 */
class Technician
{
    public static $criteria_name = 'technicians_id';
    public static $criteria_number = 5;

    public static function getDefaultValue() {

        $technicians_id = 0;
        return $technicians_id;
    }

    public static function getDisplayValue($technicians_id) {

        $form = "";
        if ($technicians_id != 0) {
            $form = "&nbsp;/&nbsp;" . __('Technician') . "&nbsp;:&nbsp;" . getUserName($technicians_id);
        }

        return $form;
    }

    public static function getDisplayForm($default, $opt, $count) {

        $params = [
            'name' => "technicians_id",
            'value' => $opt['technicians_id'] ?? $default['technicians_id'],
            'right' => "interface",
            'comments' => 1,
            'entity' => $_SESSION["glpiactiveentities"],
            'width' => '50%',
            'display' => false,
        ];
        $form = "<span class='md-widgetcrit'>";
        $form .= __('Technician');
        $form .= "&nbsp;";
        $form .= User::dropdown($params);
        $form .= "</span>";
        if ($count > 1) {
            $form .= "</br></br>";
        }

        return $form;
    }

    public static function getQueryLeftJoin($params, $table) {

        return $params['query']['LEFT JOIN'] + [
                'glpi_tickets_users' => [
                    'ON' => [
                        $table => 'id',
                        'glpi_tickets_users' => 'tickets_id',
                        [
                            'AND' => [
                                'glpi_tickets_users.type' => CommonITILActor::ASSIGN,
                            ],
                        ],
                    ],
                ],
            ];

    }

    public static function getQueryCriteria($params) {

        return $params['query']['WHERE'] + ['glpi_tickets_users.users_id' => $params[self::$criteria_name]];
    }

    public static function getSearchCriteria($params, $value = 0) {

        return Criteria::addUrlCriteria(self::$criteria_number, 'equals', $params["params"][self::$criteria_name], 'AND');
    }
}
