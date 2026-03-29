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

/**
 * Class Year
 */
class Year
{
    public static $criteria_name = 'year';


    public static function getDefaultValue() {
        return intval(date('Y', time()));
    }

    public static function getDisplayValue($year) {

        $form = "";
        if ($year) {
            $form .= "&nbsp;/&nbsp;" . __('Year', 'mydashboard') . "&nbsp;:&nbsp;" . $year;
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
        $year = date("Y") - 3;
        for ($i = 0; $i <= 3; $i++) {
            $elements[$year] = $year;

            $year++;
        }
        $opt = [
            'value' => $selected,
            'display' => false,
        ];

        return Dropdown::showFromArray(self::$criteria_name, $elements, $opt);
    }
}
