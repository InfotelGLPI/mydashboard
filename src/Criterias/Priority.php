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

use CommonITILObject;
use Dropdown;
use GlpiPlugin\Mydashboard\Criteria;

/**
 * Class Priority
 */
class Priority
{
    public static $criteria_name = 'priority';
    public static $criteria_number = 87;

    public static function getDefaultValue()
    {

        $priority = 0;

        if (isset($params['opt']["priority"])) {
            $priority = $params['opt']["priority"];
        }

        return $priority;
    }

    public static function getDisplayValue($opt) {

        $form = "";
        if ($opt[self::$criteria_name] != 0) {
            $form = "&nbsp;/&nbsp;" . __('Priority') . "&nbsp;:&nbsp;" . CommonITILObject::getPriorityName($opt[self::$criteria_name]);
        }
        return $form;
    }

    public static function getDisplayForm($default, $opt, $count) {

        $form = "<span class='md-widgetcrit'>";
        $form .= __('Priority'). "&nbsp;";

        $current_priority = 0;

        if (isset($opt[self::$criteria_name])
            && $opt[self::$criteria_name] > 0) {
            $current_priority = $opt[self::$criteria_name];
        }

        $form .= self::priorityDropdown($current_priority);

        $form .= "</span>";
        if ($count > 1) {
            $form .= "</br></br>";
        }

        return $form;
    }

    public static function getQueryCriteria($params) {

        return $params['query']['WHERE'] + ['glpi_tickets.'.self::$criteria_name => $params[self::$criteria_name]];
    }

    public static function getSearchCriteria($params) {

        return Criteria::addUrlCriteria(self::$criteria_number, 'equals', $params["params"][self::$criteria_name], 'AND');
    }

    private static function priorityDropdown(mixed $selected = null)
    {
        $criteria_name = self::$criteria_name;

        $opt = [
            'value' => $selected,
            'display' => false,
        ];

        $values = [];
        $values[5] = CommonITILObject::getPriorityName(5);
        $values[4] = CommonITILObject::getPriorityName(4);
        $values[3] = CommonITILObject::getPriorityName(3);
        $values[2] = CommonITILObject::getPriorityName(2);
        $values[1] = CommonITILObject::getPriorityName(1);


        return Dropdown::showFromArray($criteria_name, $values, $opt);
    }
}
