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

use GlpiPlugin\Mydashboard\Criteria;
use GlpiPlugin\Mydashboard\Preference;
use Session;
use Ticket;

/**
 * Class ComputerType
 */
class ComputerType
{
    public static $criteria_name = 'computertypes_id';
    public static $criteria_number = 4;

    public static function getDefaultValue() {

        $computertypes_id = 0;

        return $computertypes_id;
    }

    public static function getDisplayValue($opt) {

        $form = "";
        if ($opt[self::$criteria_name] != 0) {
            $type = new \ComputerType();
            $type->getFromDB($opt[self::$criteria_name]);
            $form = "&nbsp;/&nbsp;" . __('Type') . "&nbsp;:&nbsp;" . $type->getName();
        }
        return $form;
    }

    public static function getDisplayForm($default, $opt, $count) {

        $form = "<span class='md-widgetcrit'>";

        $form .= __('Type');
        $form .= "&nbsp;";
        $gparams = [
            'name' => self::$criteria_name,
            'display' => false,
            'value' => $opt[self::$criteria_name] ?? 0,
            'entity' => $_SESSION['glpiactiveentities'],
        ];
        $form .= \ComputerType::Dropdown($gparams);
        $form .= "</span>";
        if ($count > 1) {
            $form .= "</br></br>";
        }

        return $form;
    }


    public static function getQueryCriteria($params, $table) {

        return $params['query']['WHERE'] + [$table.".".self::$criteria_name => $params[self::$criteria_name]];
    }

    public static function getSearchCriteria($params, $value = 0) {

        if ($value > 0) {
            return Criteria::addUrlCriteria(
                self::$criteria_number,
                'equals',
                $value,
                'AND'
            );
        }
        return Criteria::addUrlCriteria(self::$criteria_number, 'equals', $params["params"][self::$criteria_name], 'AND');
    }
}
