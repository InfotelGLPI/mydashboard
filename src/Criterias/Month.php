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

namespace GlpiPlugin\Mydashboard\Criterias;

use GlpiPlugin\Mydashboard\Criteria;
use Dropdown;
use Glpi\DBAL\QueryExpression;
use Toolbox;

/**
 * Class Month
 */
class Month
{
    public static $criteria_name = 'month';

    public static function getDefaultValue()
    {
        return intval(date('m', time()) - 1);
    }

    public static function getDisplayValue($opt)
    {

        $form = "";
        if ($opt[self::$criteria_name]) {
            $monthsarray = Toolbox::getMonthsOfYearArray();
            $form .= "&nbsp;/&nbsp;" . __('Month', 'mydashboard') . "&nbsp;:&nbsp;" . $monthsarray[$opt[self::$criteria_name]];
        }

        return $form;
    }

    public static function getDisplayForm($default, $opt, $count)
    {

        $mois_courant = $default[self::$criteria_name] ?? date('m');
        if (isset($opt[self::$criteria_name])
            && $opt[self::$criteria_name] > 0) {
            $mois_courant = $opt[self::$criteria_name];
        }
        return Criteria::getFieldHtml(
            __('Month', 'mydashboard'),
            self::monthDropdown(self::$criteria_name, $mois_courant),
            $count,
        );
    }

    /**
     * @param string     $name
     * @param int|string $selected selected month, 0 or null for none
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

    public static function getQueryCriteria($params)
    {

        $year = $params['year'];

        if (is_array($year)) {
            return $params['query']['WHERE'];
        }
        // $year is interpolated into a raw QueryExpression (DATE_ADD) below, so it
        // must be forced to an integer to prevent SQL injection from the fully
        // client-controlled widget "year" parameter.
        $year = (int) $year;

        if (isset($params['month'])) {
            $month = (int) $params['month'];
            $month = sprintf('%02d', $month);
            $date_criteria = [
                ['glpi_tickets.date' => ['>=', "$year-$month-01 00:00:00"]],
                ['glpi_tickets.date' => ['<', new QueryExpression("DATE_ADD('$year-$month-01', INTERVAL 1 MONTH)")]],
            ];
        } else {
            $date_criteria = [
                ['glpi_tickets.date' => ['>=', "$year-01-01 00:00:00"]],
                ['glpi_tickets.date' => ['<', new QueryExpression("DATE_ADD('$year-01-01', INTERVAL 1 YEAR)")]],
            ];
        }

        return array_merge($params['query']['WHERE'], $date_criteria);
    }
}
