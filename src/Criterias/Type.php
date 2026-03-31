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
 * Class Type
 */
class Type
{
    public static $criteria_name = 'type';
    public static $criteria_number = 14;

    public static function getDefaultValue() {

        $type = 0;

        $preference = new Preference();
        if (!$preference->getFromDB(Session::getLoginUserID())) {
            $preference->initPreferences(Session::getLoginUserID());
        }
        $preference->getFromDB(Session::getLoginUserID());
        $preferences = $preference->fields;
        if (isset($preferences['prefered_type'])) {
            if ($preferences['prefered_type'] > 0) {
                $type = $preferences['prefered_type'];
            }
        }

        return $type;
    }

    public static function getDisplayValue($opt) {

        $form = "";
        if ($opt[self::$criteria_name] != 0) {
            $form = "&nbsp;/&nbsp;" . __('Type') . "&nbsp;:&nbsp;" . Ticket::getTicketTypeName($opt[self::$criteria_name]);
        }
        return $form;
    }

    public static function getDisplayForm($default, $opt, $count) {

        $form = "<span class='md-widgetcrit'>";
        $type = $default[self::$criteria_name];
        if (isset($opt[self::$criteria_name])
            && $opt[self::$criteria_name] > 0) {
            $type = $opt[self::$criteria_name];
        }
        $form .= __('Type');
        $form .= "&nbsp;";
        $form .= Ticket::dropdownType(self::$criteria_name, [
            'value' => $type,
            'display' => false,
            'display_emptychoice' => true,
        ]);
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
}
