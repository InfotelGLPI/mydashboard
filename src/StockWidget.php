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

namespace GlpiPlugin\Mydashboard;

use CommonDBTM;
use DBConnection;
use DbUtils;
use DropdownVisibility;
use Glpi\Application\View\TemplateRenderer;
use Migration;
use Session;
use State;

class StockWidget extends CommonDBTM
{
    public static $rightname = "plugin_mydashboard_stockwidget";

    /**
     * @param int $nb
     *
     * @return string
     */
    public static function getTypeName($nb = 0)
    {

        return _n('Stock widget', 'Stock widgets', $nb, 'mydashboard');
    }

    public function post_getEmpty()
    {
        $this->fields['alarm_threshold'] = 5;
    }

    public function prepareInputForAdd($input)
    {
        global $CFG_GLPI;

        $input = parent::prepareInputForAdd($input);

        if (!$input["itemtype"]) {
            Session::addMessageAfterRedirect(__("Cannot create alert without a type", "mydashboard"), false, ERROR);
            return false;
        }

        // The stored itemtype is later turned into a class name by showForm() and by the
        // stock computation, so keep it inside the very list the form offers.
        if (!in_array($input["itemtype"], $CFG_GLPI['state_types'], true)) {
            Session::addMessageAfterRedirect(__("Cannot create alert without a type", "mydashboard"), false, ERROR);
            return false;
        }

        if (isset($input["states"])) {
            $states = [];
            foreach ($input['states'] as $k => $v) {
                $states[$v] = $v;
            }
            $input['states'] = json_encode($states);
        }

        if (isset($input["types"])) {
            $types = [];
            foreach ($input['types'] as $k => $v) {
                $types[$v] = $v;
            }
            $input['types'] = json_encode($types);
        }

        return $input;
    }

    public function prepareInputForUpdate($input)
    {
        global $CFG_GLPI;

        // Same constraint as on creation: the form posts the itemtype back in a hidden
        // field, so never let an arbitrary value reach the stored column.
        if (isset($input["itemtype"]) && !in_array($input["itemtype"], $CFG_GLPI['state_types'], true)) {
            Session::addMessageAfterRedirect(__("Cannot create alert without a type", "mydashboard"), false, ERROR);
            return false;
        }

        if (isset($input["states"])) {
            $states = [];
            foreach ($input['states'] as $k => $v) {
                $states[$v] = $v;
            }
            $input['states'] = json_encode($states);
        }
        if (isset($input["types"])) {
            $types = [];
            foreach ($input['types'] as $k => $v) {
                $types[$v] = $v;
            }
            $input['types'] = json_encode($types);
        }
        return $input;
    }

    public function showForm($ID, $options = [])
    {
        global $CFG_GLPI;

        $this->initForm($ID, $options);

        if (!isset($options['item']) || empty($options['item'])) {
            $options['item'] = $this->fields["itemtype"];
        }

        $itemtype_label = null;
        if ($ID > 0) {
            // Rows created before the itemtype was constrained may hold anything: resolve
            // the class instead of instantiating the stored string blindly.
            if ($item = getItemForItemtype($this->fields["itemtype"])) {
                $itemtype_label = $item->getTypeName();
            }
        }

        $types = null;
        if ($options['item']) {
            $itemtypeclass = $options['item'] . "Type";
            if ($item = getItemForItemtype($itemtypeclass)) {
                $types = [];
                foreach ($item->find() as $v) {
                    $types[$v['id']] = $v['name'];
                }
            }
        }

        $states = null;
        if ($options['item']) {
            global $DB;

            // GLPI 11 moved the per-itemtype visibility of a status from the
            // glpi_states.is_visible_* columns to glpi_dropdownvisibilities.
            $dbu = new DbUtils();
            $criteria = [
                'SELECT' => [State::getTable() . '.id', State::getTable() . '.name'],
                'FROM' => State::getTable(),
                'INNER JOIN' => [
                    DropdownVisibility::getTable() => [
                        'ON' => [
                            DropdownVisibility::getTable() => 'items_id',
                            State::getTable() => 'id',
                            [
                                'AND' => [
                                    DropdownVisibility::getTable() . '.itemtype' => State::class,
                                ],
                            ],
                        ],
                    ],
                ],
                'WHERE' => [
                    DropdownVisibility::getTable() . '.visible_itemtype' => $options['item'],
                    DropdownVisibility::getTable() . '.is_visible' => 1,
                ] + $dbu->getEntitiesRestrictCriteria(State::getTable(), 'entities_id', $this->fields['entities_id'], true),
                'ORDER' => State::getTable() . '.name',
            ];
            $states = [];
            foreach ($DB->request($criteria) as $v) {
                $states[$v['id']] = $v['name'];
            }
        }

        TemplateRenderer::getInstance()->display('@mydashboard/stockwidget_form.html.twig', [
            'item' => $this,
            'params' => $options,
            'itemtype_label' => $itemtype_label,
            'itemtypes' => $CFG_GLPI['state_types'],
            'types' => $types,
            'selected_types' => self::getSelectedKeys($ID > 0 ? $this->fields['types'] : null),
            'states' => $states,
            'selected_states' => self::getSelectedKeys($ID > 0 ? $this->fields['states'] : null),
            'ajax_url' => PLUGIN_MYDASHBOARD_WEBDIR . '/ajax/',
        ]);

        return true;
    }

    /**
     * Keys of a JSON-encoded selection, as expected by a multiple dropdown.
     *
     * @param ?string $json
     *
     * @return array
     */
    private static function getSelectedKeys($json)
    {
        $values = $json !== null ? json_decode($json, true) : null;
        return is_array($values) ? array_keys($values) : [];
    }

    public static function install(Migration $migration)
    {
        global $DB;

        $default_charset   = DBConnection::getDefaultCharset();
        $default_collation = DBConnection::getDefaultCollation();
        $default_key_sign  = DBConnection::getDefaultPrimaryKeySignOption();
        $table  = self::getTable();

        if (!$DB->tableExists($table)) {
            $query = "CREATE TABLE `$table` (
                        `id` int {$default_key_sign}                              NOT NULL auto_increment,
                        `entities_id`     int {$default_key_sign}                 NOT NULL DEFAULT '0',
                        `is_recursive`    tinyint                                 NOT NULL DEFAULT '0',
                        `name`            varchar(255)                            NOT NULL,
                        `states`          longtext COLLATE utf8mb4_unicode_ci     DEFAULT NULL,
                        `itemtype`        varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'see .class.php file',
                        `icon`            varchar(255)                            NOT NULL,
                        `types`           longtext COLLATE utf8mb4_unicode_ci     DEFAULT NULL,
                        `alarm_threshold` int {$default_key_sign}                 NOT NULL DEFAULT '5',
                        PRIMARY KEY (`id`),
                        KEY `name` (`name`),
                        KEY `entities_id` (`entities_id`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);

        }

        $migration->changeField($table, "alarm_threshold", "alarm_threshold", "INT {$default_key_sign} NOT NULL DEFAULT '5'");
    }

    public static function uninstall()
    {
        global $DB;

        $DB->dropTable(self::getTable(), true);

    }
}
