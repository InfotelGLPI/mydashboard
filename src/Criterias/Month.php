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
use Glpi\DBAL\QueryExpression;
use Toolbox;

/**
 * Class Month
 */
class Month
{
    public static $criteria_name = 'month';


    public static function getDefaultValue() {
        return intval(date('m', time()) - 1);
    }

    public static function getDisplayValue($opt) {

        $form = "";
        if ($opt[self::$criteria_name]) {
            $monthsarray = Toolbox::getMonthsOfYearArray();
            $form .= "&nbsp;/&nbsp;" . __('Month', 'mydashboard') . "&nbsp;:&nbsp;" . $monthsarray[$opt[self::$criteria_name]];
        }

        return $form;
    }

    public static function getDisplayForm($default, $opt, $count) {

        $mois_courant = $default[self::$criteria_name];
        if (isset($opt[self::$criteria_name])
            && $opt[self::$criteria_name] > 0) {
            $mois_courant = $opt[self::$criteria_name];
        }
        $form = "<span class='md-widgetcrit'>";
        $form .= __('Month', 'mydashboard');
        $form .= "&nbsp;";
        $form .= self::monthDropdown(self::$criteria_name, $mois_courant);
        $form .= "</span>";
        if ($count > 1) {
            $form .= "</br></br>";
        }

        return $form;
    }

    /**
     * @param null $selected
     *
     * @return int|string
     */
    public static function monthDropdown($name = "month", $selected = null)
    {
        $monthsarray = Toolbox::getMonthsOfYearArray();

        $opt = [
            'value' => $selected,
            'display' => false,
        ];

        return Dropdown::showFromArray($name, $monthsarray, $opt);
    }

    public static function getQueryCriteria($params) {

        $year = $params['year'];

        if (isset($params['month'])) {
            $month = $params['month'];
            $month = sprintf('%02d', $month);
            $date_criteria = [
                ['glpi_tickets.date' => ['>=', "$year-$month-01 00:00:00"]],
                ['glpi_tickets.date' => ['<', new QueryExpression("DATE_ADD('$year-$month-01', INTERVAL 1 MONTH)")]]
            ];
        } else {
            $date_criteria = [
                ['glpi_tickets.date' => ['>=', "$year-01-01 00:00:00"]],
                ['glpi_tickets.date' => ['<', new QueryExpression("DATE_ADD('$year-01-01', INTERVAL 1 YEAR)")]]
            ];
        }

        return array_merge($params['query']['WHERE'],$date_criteria);
    }
}
