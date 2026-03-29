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
 * Class Limit
 */
class Limit
{
    public static $criteria_name = 'limit';

    public static function getDefaultValue() {

        $limit = 10;

        return $limit;
    }

    public static function getDisplayValue($limit) {

        $form = "";
        if ($limit != 0) {
            $form = "&nbsp;/&nbsp;" . __('Number of results') . "&nbsp;:&nbsp;" . $limit;
        }
        return $form;
    }

    public static function getDisplayForm($default, $opt, $count) {

        $params = [
            'value' => $opt[self::$criteria_name] ?? $default[self::$criteria_name],
            'min' => 0,
            'max' => 200,
            'step' => 1,
            'display' => false,
            'toadd' => [0 => __('All')],
        ];
        $form = "<span class='md-widgetcrit'>";
        $form .= __('Number of results');
        $form .= "&nbsp;";
        $form .= Dropdown::showNumber(self::$criteria_name, $params);
        $form .= "</span>";
        if ($count > 1) {
            $form .= "</br></br>";
        }

        return $form;
    }

}
