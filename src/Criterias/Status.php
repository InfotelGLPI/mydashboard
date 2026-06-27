<?php

/*
 -------------------------------------------------------------------------
 mydashboard plugin for GLPI
 Copyright (C) 2016-2026 by the mydashboard Development Team.

 https://github.com/InfotelGLPI/mydashboard
 -------------------------------------------------------------------------

 LICENSE

 This file is part of mydashboard.

 mydashboard is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 3 of the License, or
 (at your option) any later version.

 mydashboard is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with mydashboard. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Mydashboard\Criterias;

use Dropdown;
use GlpiPlugin\Mydashboard\Criteria;
use Ticket;

/**
 * Class Status
 */
class Status
{
    public static $criteria_name = 'status';
    public static $criteria_number = 85;

    public static function getDefaultValue()
    {

        $status = 0;

        if (isset($params['opt']["status"])) {
            $status = $params['opt']["status"];
        }

        return $status;
    }

    public static function getDisplayValue($opt) {

        $form = "";
        if ($opt[self::$criteria_name] != 0) {
            $form = "&nbsp;/&nbsp;" . __('Status') . "&nbsp;:&nbsp;" . Ticket::getStatus($opt[self::$criteria_name]);
        }
        return $form;
    }

    public static function getDisplayForm($default, $opt, $count) {

        $form = "<span class='md-widgetcrit'>";
        $form .= __('Ticket')." ".__('Status') . "&nbsp;";

        $current_status = 0;

        if (isset($opt[self::$criteria_name])
            && $opt[self::$criteria_name] > 0) {
            $current_status = $opt[self::$criteria_name];
        }

        $form .= self::statusDropdown($current_status);
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

    private static function statusDropdown(mixed $selected = null)
    {
        $opt = [
            'value' => $selected,
            'display' => false,
        ];

        $all_status = Ticket::getAllStatusArray();
        $criteria_name = self::$criteria_name;

        return Dropdown::showFromArray($criteria_name, $all_status, $opt);
    }

}
