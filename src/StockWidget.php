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

use Ajax;
use CommonDBTM;
use DBConnection;
use DbUtils;
use Dropdown;
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

        $input = parent::prepareInputForAdd($input);

        if (!$input["itemtype"]) {
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

        $rand = mt_rand();

        // showFormHeader()/showFormButtons() emit the surrounding <form> and <table>
        ob_start();
        $this->showFormHeader($options);
        $form_header_html = ob_get_clean();

        $itemtype_label = null;
        $itemtype_hidden_html = '';
        $itemtype_dropdown_html = '';
        if ($ID > 0) {
            $itemtype = $this->fields["itemtype"];
            $item = new $itemtype();
            $itemtype_label = $item->getTypeName();
            $itemtype_hidden_html = \Html::hidden('itemtype', ['value' => $itemtype]);
        } else {
            $params = ['itemtype' => '__VALUE__', 'fieldname' => 'types'];
            $itemtype_dropdown_html = Dropdown::showItemTypes(
                'itemtype',
                $CFG_GLPI['state_types'],
                ['value' => $this->fields["itemtype"], 'rand' => $rand, 'display' => false],
            )
            . Ajax::updateItemOnSelectEvent(
                "dropdown_itemtype$rand",
                "show_types$rand",
                "../ajax/dropdownType.php",
                $params,
                false,
            )
            . Ajax::updateItemOnSelectEvent(
                "dropdown_itemtype$rand",
                "show_statuses$rand",
                "../ajax/dropdownStatus.php",
                $params,
                false,
            );
        }

        $types_dropdown_html = '';
        if ($options['item']) {
            $itemtypeclass = $options['item'] . "Type";
            if ($item = getItemForItemtype($itemtypeclass)) {
                $types = [];
                foreach ($item->find() as $v) {
                    $types[$v['id']] = $v['name'];
                }
                $types_dropdown_html = Dropdown::showFromArray('types', $types, [
                    'multiple' => true,
                    'values' => self::getSelectedKeys($ID > 0 ? $this->fields['types'] : null),
                    'display' => false,
                ]);
            }
        }

        $states_dropdown_html = '';
        if ($options['item']) {
            $state = new State();
            $dbu = new DbUtils();
            $field = 'is_visible_' . strtolower($options['item']);
            $condition = [$field => 1]
                + $dbu->getEntitiesRestrictCriteria('glpi_states', 'entities_id', $this->fields['entities_id'], true);
            $states = [];
            foreach ($state->find($condition) as $v) {
                $states[$v['id']] = $v['name'];
            }
            $states_dropdown_html = Dropdown::showFromArray('states', $states, [
                'multiple' => true,
                'values' => self::getSelectedKeys($ID > 0 ? $this->fields['states'] : null),
                'display' => false,
            ]);
        }

        $icon_selector_id = 'icon_' . mt_rand();
        $icon_select_html = \Html::select(
            'icon',
            [$this->fields['icon'] => $this->fields['icon']],
            [
                'id' => $icon_selector_id,
                'selected' => $this->fields['icon'],
                'style' => 'width:175px;',
            ],
        )
        . \Html::script('js/modules/Form/WebIconSelector.js')
        . \Html::scriptBlock("$(
            function() {
            import('/js/modules/Form/WebIconSelector.js').then((m) => {
               var icon_selector = new m.default(document.getElementById('{$icon_selector_id}'));
               icon_selector.init();
               });
            }
         );");

        ob_start();
        $this->showFormButtons($options);
        $form_buttons_html = ob_get_clean();

        echo TemplateRenderer::getInstance()->render('@mydashboard/stockwidget_form.html.twig', [
            'form_header_html' => $form_header_html,
            'form_buttons_html' => $form_buttons_html,
            'rand' => $rand,
            'is_new' => $ID <= 0,
            'name_input_html' => \Html::input('name', ['value' => $this->fields['name'], 'size' => 40]),
            'itemtype_label' => $itemtype_label,
            'itemtype_hidden_html' => $itemtype_hidden_html,
            'itemtype_dropdown_html' => $itemtype_dropdown_html,
            'types_dropdown_html' => $types_dropdown_html,
            'states_dropdown_html' => $states_dropdown_html,
            'icon_select_html' => $icon_select_html,
            'threshold_dropdown_html' => Dropdown::showNumber('alarm_threshold', [
                'value' => $this->fields["alarm_threshold"],
                'min' => 1,
                'max' => 100,
                'step' => 1,
                'display' => false,
            ]),
        ]);
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
