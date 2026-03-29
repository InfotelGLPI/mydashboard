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
use GlpiPlugin\Mydashboard\Preference;
use Session;
use User;

/**
 * Class ITILCategory
 */
class ITILCategory
{
    public static $criteria_name = 'itilcategories_id';
    public static $criteria_number = 7;

    public static function getDefaultValue() {

        $itilcategories_id = 0;

        $preference = new Preference();
        if (!$preference->getFromDB(Session::getLoginUserID())) {
            $preference->initPreferences(Session::getLoginUserID());
        }
        $preference->getFromDB(Session::getLoginUserID());
        $preferences = $preference->fields;
        if (isset($preferences['prefered_category'])) {
            if ($preferences['prefered_category'] > 0) {
                $itilcategories_id = $preferences['prefered_category'];
            }
        }

        return $itilcategories_id;
    }

    public static function getDisplayValue($itilcategories_id) {

        $form = "";
        if ($itilcategories_id != 0) {
            $form = "&nbsp;/&nbsp;" . __("Category", 'mydashboard') . "&nbsp;:&nbsp;" . Dropdown::getDropdownName(
                    'glpi_itilcategories',
                    $itilcategories_id
                );
        }
        return $form;
    }

    public static function getDisplayForm($default, $opt, $count) {

        $form = "<span class='md-widgetcrit'>";

        $form .= __('Category', 'mydashboard');
        $form .= "&nbsp;";
        if (isset($opt['entities_id'])) {
            $restrict = getEntitiesRestrictCriteria(
                'glpi_entities',
                '',
                $opt['entities_id'],
                $opt['is_recursive_entities']
            );
        } else {
            $restrict = [];
        }

        $dropdown = \ITILCategory::dropdown(
            [
                'name' => 'itilcategories_id',
                'value' => $opt['itilcategories_id'] ?? $default['itilcategories_id'],
                'display' => false,
                'condition' => ['OR' => ['is_request' => 1, 'is_incident' => 1]],
            ] + $restrict
        );

        $form .= $dropdown;

        $form .= "</span>";


        if ($count > 1) {
            $form .= "</br></br>";
        }
        return $form;
    }

    public static function getQueryCriteria($params) {

        return $params['query']['WHERE'] + ['glpi_tickets.'.self::$criteria_name => $params[self::$criteria_name]];
    }

    public static function getSearchCriteria($params, $value = 0) {

        return Criteria::addUrlCriteria(self::$criteria_number, 'equals', $params["params"][self::$criteria_name], 'AND');
    }
}
