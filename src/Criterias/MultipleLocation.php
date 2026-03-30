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
 * Class MultipleLocation
 */
class MultipleLocation
{
    public static $criteria_name = 'multiple_locations_id';
//    public static $criteria_number = 83;

    public static function getDefaultValue() {

        $multiple_locations_id = [];
        return $multiple_locations_id;
    }

    public static function getDisplayValue($opt) {

        $form = "";
        if (isset($opt[self::$criteria_name])) {
            $multiple_locations_id = is_array(
                $opt[self::$criteria_name]
            ) ? $opt[self::$criteria_name] : [];

            $multiple_locations_id = array_filter($multiple_locations_id);

            if (count($multiple_locations_id) > 0) {
                $form .= "&nbsp;/&nbsp;" . _n(
                        'Location',
                        'Locations',
                        count($multiple_locations_id)
                    ) . "&nbsp;:&nbsp;";
                foreach ($multiple_locations_id as $k => $v) {
                    $form .= Dropdown::getDropdownName('glpi_locations', $v);
                    if (count($multiple_locations_id) > 1) {
                        $form .= "&nbsp;-&nbsp;";
                    }
                }
            }
        }
        return $form;
    }

    public static function getDisplayForm($default, $opt, $count) {

        $form = "<span class='md-widgetcrit'>";
        $result = getAllDataFromTable(\Location::getTable(), ['ORDER' => "completename"], false);

        if (isset($opt[self::$criteria_name])) {
            $multiple_locations_id = (is_array(
                $opt[self::$criteria_name]
            ) ? $opt[self::$criteria_name] : []);
        } else {
            $multiple_locations_id = [];
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
            'values' => $multiple_locations_id ?? $default[self::$criteria_name],
            'display_emptychoice' => true,
        ];

        $form .= _n('Location', 'Locations', 2);
        $form .= "&nbsp;";

        $dropdown = Dropdown::showFromArray(self::$criteria_name, $temp, $params);

        $form .= $dropdown;
        $form .= "</span>";
        if ($count > 1) {
            $form .= "</br></br>";
        }

        $form .= "<span class='md-widgetcrit'>";
        $form .= __('Child locations', 'mydashboard') . "&nbsp;";
        $paramsy = ['display' => false];
        $ancestors = $opt['is_recursive_locations'] ?? $default['is_recursive_locations'];
        $form .= Dropdown::showYesNo('is_recursive_locations', $ancestors, -1, $paramsy);
        $form .= "</span>";
        if ($count > 1) {
            $form .= "</br></br>";
        }

        return $form;
    }

    public static function getQueryCriteria($params) {

        return $params['query']['WHERE'] + ['glpi_tickets.locations_id' => $params[self::$criteria_name]];

    }

    public static function getSearchCriteria($params, $value = 0) {

//        return Criteria::addUrlCriteria(self::$criteria_number, 'equals', $params["params"][self::$criteria_name], 'AND');
    }
}
