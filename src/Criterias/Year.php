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
use GlpiPlugin\Mydashboard\Criteria;
use GlpiPlugin\Mydashboard\Preference;
use Session;

/**
 * Class Year
 */
class Year
{
    public static $criteria_name = 'year';


    public static function getDefaultValue() {

        $year = intval(date('Y', time()));

        $preference = new Preference();
        if (!$preference->getFromDB(Session::getLoginUserID())) {
            $preference->initPreferences(Session::getLoginUserID());
        }
        $preference->getFromDB(Session::getLoginUserID());
        $preferences = $preference->fields;
        if (isset($preferences['prefered_year'])) {
            if ($preferences['prefered_year'] > 0) {
                $year = intval(date('Y', time()) -1);
            }
        }
        return $year;
    }

    public static function getDisplayValue($opt) {

        $form = "";
        if ($opt[self::$criteria_name]) {
            $form .= "&nbsp;/&nbsp;" . __('Year', 'mydashboard') . "&nbsp;:&nbsp;" . $opt[self::$criteria_name];
        }

        return $form;
    }

    public static function getDisplayForm($default, $opt, $count) {

        $form = "<span class='md-widgetcrit'>";
        //            $annee_courante = date('Y', time());
        $annee_courante = $default[self::$criteria_name];
        if (isset($opt[self::$criteria_name])
            && $opt[self::$criteria_name] > 0) {
            $annee_courante = $opt[self::$criteria_name];
        }
        $form .= __('Year', 'mydashboard');
        $form .= "&nbsp;";
        $form .= self::YearDropdown($annee_courante);
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
    public static function YearDropdown($selected = null)
    {
        $year = date("Y") - 10;
        for ($i = 0; $i <= 10; $i++) {
            $elements[$year] = $year;

            $year++;
        }
        $opt = [
            'value' => $selected,
            'display' => false,
        ];

        return Dropdown::showFromArray(self::$criteria_name, $elements, $opt);
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

    public static function getSearchCriteria($params) {

        if (isset($params["params"]["year"])) {
            $params["params"]["begin"] = $params["params"]["year"] . "-01-01 00:00:01";
            $params["params"]["end"] = $params["params"]["year"] . "-12-31 23:59:00";
        }

        if (isset($params["params"]["begin"])) {
            $options = Criteria::addUrlCriteria(Criteria::OPEN_DATE, 'morethan', $params["params"]["begin"], 'AND');
        }
        if (isset($params["params"]["end"])) {
            $options = Criteria::addUrlCriteria(Criteria::OPEN_DATE, 'lessthan', $params["params"]["end"], 'AND');
        }

        return $options;
    }
}
