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

use Dropdown;
use GlpiPlugin\Mydashboard\Criteria;
use Session;
use User;

/**
 * Class Location
 */
class Location
{
    public static $criteria_name = 'locations_id';
    public static $criteria_number = 83;

    public static function getDefaultValue() {

        $locations_id = 0;
        return $locations_id;
    }

    public static function getDisplayValue($opt) {

        $form = "";
        if ($opt[self::$criteria_name] != 0) {
            $form = "&nbsp;/&nbsp;" . __('Location') . "&nbsp;:&nbsp;" . Dropdown::getDropdownName(
                    'glpi_locations',
                    $opt[self::$criteria_name]
                );
        }
        return $form;
    }

    public static function getDisplayForm($default, $opt, $count) {

        $user = new User();
        $default_location = $default[self::$criteria_name];
        if (isset($_SESSION['glpiactiveprofile']['interface'])
            && Session::getCurrentInterface() != 'central'
            && $user->getFromDB(Session::getLoginUserID())) {
            $default_location = $user->fields[self::$criteria_name];
        }
        $gparams = [
            'name' => self::$criteria_name,
            'display' => false,
            'value' => $opt[self::$criteria_name] ?? $default_location,
            'entity' => $_SESSION['glpiactiveentities'],
        ];
        $form = "<span class='md-widgetcrit'>";
        $form .= __('Location');
        $form .= "&nbsp;";
        $form .= \Location::dropdown($gparams);
        $form .= "</span>";
        if ($count > 1) {
            $form .= "</br></br>";
        }

        return $form;
    }

    public static function getQueryCriteria($params) {

        return $params['query']['WHERE'] + ['glpi_tickets.'.self::$criteria_name => $params[self::$criteria_name]];
    }

    public static function getSearchCriteria($params, $value = 0) {

        return Criteria::addUrlCriteria(self::$criteria_number, 'equals', $params["params"][self::$criteria_name], 'AND');
    }
}
