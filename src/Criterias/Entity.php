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

/**
 * Class Entity
 */
class Entity
{
    public static $criteria_name = 'entities_id';
    public static $criteria_number = 80;

    public static function getDefaultValue() {

        $entities_id = $_SESSION['glpiactive_entity'];

        $preference = new Preference();
        if (!$preference->getFromDB(Session::getLoginUserID())) {
            $preference->initPreferences(Session::getLoginUserID());
        }
        if (isset($preferences['prefered_entity'])
            && $preferences['prefered_entity'] > 0) {
            $entities_id = $preferences['prefered_entity'];
        }

        return $entities_id;
    }

    public static function getDisplayValue($entities_id) {

        $form = "";
        $entity = new \Entity();
        if (isset($entities_id) && $entities_id > -1) {
            if ($entity->getFromDB($entities_id)) {
                $form = "&nbsp;" . __('Entity') . "&nbsp;:&nbsp;" . $entity->getField('name');
            }
        }

        return $form;
    }

    public static function getDisplayForm($default, $opt, $count) {

        $form = '';
        if (Session::isMultiEntitiesMode()) {

                $form = "<span class='md-widgetcrit'>";
                $params = [
                    'name' => self::$criteria_name,
                    'display' => false,
                    'width' => '100px',
                    'value' => $opt[self::$criteria_name] ?? $default[self::$criteria_name],
                    'display_emptychoice' => true,

                ];
                $form .= __('Entity');
                $form .= "&nbsp;";
                $form .= \Entity::dropdown($params);
                $form .= "</span>";
                if ($count > 1) {
                    $form .= "</br></br>";
                }

            //            if (in_array("is_recursive_entities", $criterias)) {
            $form .= "<span class='md-widgetcrit'>";
            $form .= __('Recursive') . "&nbsp;";
            $paramsy = [
                'display' => false,
            ];
            $sons = $opt['is_recursive_entities'] ?? $default['is_recursive_entities'];
            $form .= Dropdown::showYesNo('is_recursive_entities', $sons, -1, $paramsy);
            $form .= "</span>";
            if ($count > 1) {
                $form .= "</br></br>";
            }
//            }
            }

        return $form;
    }

    public static function getQueryCriteria($params, $table = 'glpi_tickets') {

        return $params['query']['WHERE'] + getEntitiesRestrictCriteria(
                $table,
                self::$criteria_name,
                $params[self::$criteria_name],
                $params['recursive']
            );;
    }

    public static function getSearchCriteria($params, $value = 0) {

        return Criteria::addUrlCriteria(
            self::$criteria_number,
            (isset($params["params"]["sons"])
                && $params["params"]["sons"] > 0) ? 'under' : 'equals',
            $params["params"][self::$criteria_name],
            'AND'
        );
    }
}
