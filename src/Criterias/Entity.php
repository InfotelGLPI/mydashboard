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

use Dropdown;
use GlpiPlugin\Mydashboard\Criteria;
use GlpiPlugin\Mydashboard\Preference;
use Session;

/**
 * Class Entity
 */
class Entity
{
    public static $criteria_name = "entities_id";
    public static $criteria_number = 80;

    public static function getDefaultValue()
    {
        $entities_id = $_SESSION['glpiactive_entity'];

        $preference = new Preference();
        if (!$preference->getFromDB(Session::getLoginUserID())) {
            $preference->initPreferences(Session::getLoginUserID());
            $preference->getFromDB(Session::getLoginUserID());
        }
        $preferences = $preference->fields;
        if (isset($preferences['prefered_entity'])
            && $preferences['prefered_entity'] > 0) {
            $entities_id = $preferences['prefered_entity'];
        }

        return $entities_id;
    }

    public static function getDisplayValue($opt)
    {
        $form = "";
        $entity = new \Entity();
        if (isset($opt[self::$criteria_name]) && $opt[self::$criteria_name] > -1) {
            if ($entity->getFromDB($opt[self::$criteria_name])) {
                $form = "&nbsp;" . __('Entity') . "&nbsp;:&nbsp;" . $entity->getField('name');
            }
        }

        return $form;
    }

    public static function getDisplayForm($default, $opt, $count)
    {
        $form = '';
        if (Session::isMultiEntitiesMode()) {
            $params = [
                'name' => self::$criteria_name,
                'display' => false,
                'width' => '100px',
                'value' => $opt[self::$criteria_name] ?? $default[self::$criteria_name],
                'display_emptychoice' => true,

            ];
            $form = Criteria::getFieldHtml(__('Entity'), \Entity::dropdown($params), $count);

            $sons = $opt['is_recursive_entities'] ?? $default['is_recursive_entities'];
            if ($sons > 0) {
                $form .= Criteria::getFieldHtml(
                    __('Recursive'),
                    Dropdown::showYesNo('is_recursive_entities', $sons, -1, ['display' => false]),
                    $count,
                );
            }
        }

        return $form;
    }

    public static function getQueryLeftJoin($params, $table)
    {

        return $params['query']['LEFT JOIN'] + [
            'glpi_entities' => [
                'ON' => [
                    $table => self::$criteria_name,
                    'glpi_entities' => 'id',
                ],
            ],
        ];

    }

    /**
     * Build the entity restriction of a widget query.
     *
     * $params is filled from $_POST['params'] by ajax/refreshWidget.php, so both
     * entities_id and is_recursive_entities are attacker controlled. The resulting
     * entity list is therefore intersected with $_SESSION['glpiactiveentities']:
     * a forged criterion may only narrow the scope, never widen it.
     *
     * @param array  $params
     * @param string $table
     *
     * @return array
     */
    public static function getQueryCriteria($params, $table = 'glpi_tickets')
    {
        $field = $table . "." . self::$criteria_name;

        $requested = $params[self::$criteria_name] ?? '';
        if (is_array($requested)) {
            $requested = reset($requested);
        }
        if ($requested === '' || $requested === null || (int) $requested === -1) {
            $requested = $_SESSION['glpiactive_entity'] ?? 0;
        }

        $recursive = isset($params['is_recursive_entities'])
                     && (int) $params['is_recursive_entities'] === 1;

        $entities = $recursive
            ? getSonsOf('glpi_entities', (int) $requested)
            : [(int) $requested];

        // Entities the session may actually read. Fails closed: an empty
        // intersection yields an impossible id rather than no criterion at all,
        // which would expose every entity.
        $allowed  = $_SESSION['glpiactiveentities'] ?? [];
        $entities = array_values(array_intersect(
            array_map('intval', $entities),
            array_map('intval', $allowed),
        ));

        return $params['query']['WHERE'] + [$field => ($entities === [] ? [-1] : $entities)];
    }

    public static function getSearchCriteria($params, $value = 0)
    {
        return Criteria::addUrlCriteria(
            self::$criteria_number,
            (isset($params["params"]["is_recursive_entities"])
                && $params["params"]["is_recursive_entities"] > 0) ? 'under' : 'equals',
            $params["params"][self::$criteria_name],
            'AND',
        );
    }
}
