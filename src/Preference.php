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
use CommonGLPI;
use DBConnection;
use DbUtils;
use Dropdown;
use Entity;
use Glpi\Application\View\TemplateRenderer;
use Group;
use ITILCategory;
use Migration;
use Plugin;
use Session;
use Ticket;

/**
 * Class Preference
 */
class Preference extends CommonDBTM
{
    /**
     * @return bool
     */
    public static function canCreate(): bool
    {
        return Session::haveRightsOr('plugin_mydashboard', [CREATE, UPDATE, READ]);
    }

    /**
     * @return bool
     */
    public static function canView(): bool
    {
        return Session::haveRightsOr('plugin_mydashboard', [CREATE, UPDATE, READ]);
    }

    /**
     * @return bool|booleen
     */
    public static function canUpdate(): bool
    {
        return self::canCreate();
    }


    /**
     * @param CommonGLPI $item
     * @param int        $withtemplate
     *
     * @return string|translated
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item->getType() == 'Preference') {
            return self::createTabEntry(__('My Dashboard', 'mydashboard'));
        }
        return '';
    }

    /**
    * @return string
    */
    public static function getIcon()
    {
        return Menu::getIcon();
    }


    /**
     * @param CommonGLPI $item
     * @param int        $tabnum
     * @param int        $withtemplate
     *
     * @return bool
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        $pref = new Preference();
        $pref->showPreferencesForm(Session::getLoginUserID());
        return true;
    }

    /**
     * Get a specific field of the config
     *
     * @param string $fieldname
     *
     * @return mixed
     */
    public static function getPreferenceField($fieldname)
    {
        $preference = new Preference();
        if (!$preference->getFromDB(Session::getLoginUserID())) {
            $preference->initPreferences(Session::getLoginUserID());
        }
        $preference->getFromDB(Session::getLoginUserID());

        return (isset($preference->fields[$fieldname])) ? $preference->fields[$fieldname] : 0;
    }

    /**
     * Check if user wants dashboard to replace central interface
     * @return boolean, TRUE if dashboard must replace, FALSE otherwise
     */
    public static function getReplaceCentral()
    {
        return Preference::getPreferenceField("replace_central");
    }

    /**
     * @param $user_id
     */
    public function showPreferencesForm($user_id)
    {
        if (!$this->getFromDB($user_id)) {
            $this->initPreferences($user_id);
            $this->getFromDB($user_id);
        }

        $options    = ['candel' => false];
        $is_central = Session::getCurrentInterface() === 'central';

        $dbu = new DbUtils();

        $group_choices = [];
        if ($is_central) {
            foreach ($dbu->getAllDataFromTable(Group::getTable(), ['is_assign' => 1]) as $group) {
                $group_choices[$group['id']] = $group['name'];
            }
        }
        $requester_group_choices = [];
        foreach ($dbu->getAllDataFromTable(Group::getTable(), ['is_requester' => 1]) as $group) {
            $requester_group_choices[$group['id']] = $group['name'];
        }

        TemplateRenderer::getInstance()->display('@mydashboard/preferences.html.twig', [
            'item'                      => $this,
            'params'                    => $options,
            'is_central'                => $is_central,
            'group_choices'             => $group_choices,
            'prefered_groups'           => self::decodeGroupList($this->fields['prefered_group']),
            'requester_group_choices'   => $requester_group_choices,
            'requester_prefered_groups' => self::decodeGroupList($this->fields['requester_prefered_group']),
            'entity_itemtype'           => Entity::class,
            'category_itemtype'         => ITILCategory::class,
            'category_condition'        => [['OR' => ['is_request' => 1, 'is_incident' => 1]]],
            'empty_value'               => Dropdown::EMPTY_VALUE,
            'ticket_types'              => [0 => Dropdown::EMPTY_VALUE] + Ticket::getTypes(),
            'palettes'                  => $this->getPalettes(),
        ]);

        $blacklist = new PreferenceUserBlacklist();
        $blacklist->showUserForm(Session::getLoginUserID());
    }

    /**
     * Group ids stored as a JSON list.
     *
     * @param mixed $value
     *
     * @return array
     */
    private static function decodeGroupList($value): array
    {
        $groups = json_decode((string) $value, true);
        return is_array($groups) ? $groups : [];
    }

    public function prepareInputForAdd($input)
    {
        return $this->prepareInputForUpdate($input);
    }

    public function prepareInputForUpdate($input)
    {
        // The palette is read back by getPalette() and interpolated into the inline script that
        // initialises every ECharts instance, so only a theme that actually ships with the
        // plugin may be stored: getPalettes() is the source of truth the form itself uses, and
        // anything else falls back to the default (empty) theme instead of being persisted.
        if (isset($input['color_palette'])
            && !array_key_exists($input['color_palette'], $this->getPalettes())) {
            $input['color_palette'] = '';
        }

        return $input;
    }

    public function getPalettes()
    {
        $themes_files = scandir(Plugin::getPhpDir("mydashboard") . "/public/lib/echarts/theme");
        $themes = [];
        foreach ($themes_files as $file) {
            if (strpos($file, ".js") !== false) {
                $name     = substr($file, 0, -3);
                $themes[$name] = ucfirst($name);
            }
        }
        return $themes;
    }

    /**
     * @param $users_id
     */
    public function initPreferences($users_id)
    {
        $input                             = [];
        $input['id']                       = $users_id;
        $input['automatic_refresh']        = "0";
        $input['automatic_refresh_delay']  = "10";
        $input['nb_widgets_width']         = "3";
        $input['replace_central']          = "0";
        $input['requester_prefered_group'] = "[]";
        $input['prefered_group']           = "[]";
        $input['prefered_entity']          = "0";
        $input['color_palette']            = "";
        $input['edit_mode']                = "0";
        $input['drag_mode']                = "0";
        $this->add($input);
    }

    public static function checkEditMode($users_id)
    {
        return self::checkPreferenceValue('edit_mode', $users_id);
    }

    public static function checkDragMode($users_id)
    {
        return self::checkPreferenceValue('drag_mode', $users_id);
    }

    public static function checkPreferenceValue($field, $users_id = 0)
    {
        $dbu  = new DbUtils();
        $data = $dbu->getAllDataFromTable($dbu->getTableForItemType(__CLASS__), ["id" => $users_id]);
        if (!empty($data)) {
            $first = array_pop($data);
            return $first[$field];
        } else {
            return 0;
        }
    }

    /**
     * @return mixed
     */
    public static function getPalette($users_id)
    {
        $palette = self::checkPreferenceValue('color_palette', $users_id);

        // The value is interpolated into the inline script that initialises the ECharts
        // instances, so it is confronted with the themes really shipped by the plugin at read
        // time too, and not only in prepareInputForUpdate(): rows written before that validation
        // existed, or by any other write path, can then no longer leave the JS string literal.
        if (!array_key_exists($palette, (new self())->getPalettes())) {
            return '';
        }

        return $palette;
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
                        `id` int {$default_key_sign} NOT NULL auto_increment,
                        `automatic_refresh`        tinyint      NOT NULL DEFAULT '0',
                        `automatic_refresh_delay`  int {$default_key_sign} NOT NULL DEFAULT '10',
                        `replace_central`          tinyint      NOT NULL DEFAULT 0,
                        `nb_widgets_width`         int {$default_key_sign} NOT NULL DEFAULT '3',
                        `prefered_group`           varchar(255) NOT NULL DEFAULT '[]',
                        `requester_prefered_group` varchar(255) NOT NULL DEFAULT '[]',
                        `prefered_entity`          int {$default_key_sign} NOT NULL DEFAULT '0',
                        `edit_mode`                tinyint      NOT NULL DEFAULT '0',
                        `drag_mode`                tinyint      NOT NULL DEFAULT '0',
                        `color_palette`            varchar(50)  NOT NULL DEFAULT '',
                        `prefered_type`            int {$default_key_sign} NOT NULL DEFAULT '0',
                        `prefered_category`        int {$default_key_sign} NOT NULL DEFAULT '0',
                        `prefered_year`            tinyint      NOT NULL DEFAULT '0',
                        PRIMARY KEY (`id`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);

        }

        if ($DB->fieldExists("glpi_plugin_mydashboard_configs", "replace_central")
            && !$DB->fieldExists($table, "replace_central")) {
            //Adding the new field to preferences
            $mig             = new Migration("1.0.3");
            $configs         = getAllDataFromTable("glpi_plugin_mydashboard_configs");
            $replace_central = 0;
            //Basically there is only one config for Dashboard (this foreach may be useless)
            foreach ($configs as $config) {
                $replace_central = $config['replace_central'];
            }
            $mig->addField(
                "glpi_plugin_mydashboard_preferences",
                "replace_central",
                "bool",
                [
                    "update" => $replace_central,
                    "value"  => 0,
                ],
            );
            $mig->executeMigration();
        }

        if (!$DB->fieldExists($table, "prefered_group")) {
            $migration->addField($table, "prefered_group", "varchar(255) NOT NULL DEFAULT '[]'");
            $migration->migrationOneTable($table);
        }

        if (!$DB->fieldExists($table, "prefered_entity")) {
            $migration->addField($table, "prefered_entity", "int {$default_key_sign} NOT NULL DEFAULT '0'");
            $migration->migrationOneTable($table);
        }

        if (!$DB->fieldExists($table, "edit_mode")) {
            $migration->addField($table, "edit_mode", "tinyint NOT NULL DEFAULT '0'");
            $migration->migrationOneTable($table);
        }

        if (!$DB->fieldExists($table, "drag_mode")) {
            $migration->addField($table, "drag_mode", "tinyint NOT NULL DEFAULT '0'");
            $migration->migrationOneTable($table);
        }

        if (!$DB->fieldExists($table, "requester_prefered_group")) {
            $migration->addField($table, "requester_prefered_group", "varchar(255) NOT NULL DEFAULT '[]'");
            $migration->migrationOneTable($table);
        }

        $criteria = [
            'SELECT' => [
                'DATA_TYPE',
            ],
            'FROM'   => 'information_schema.columns',
            'WHERE'  => [
                'table_schema' => $DB->dbdefault,
                'table_name'   => 'glpi_plugin_mydashboard_preferences',
                'column_name'  => ['prefered_group'],
            ],
        ];
        $iterator = $DB->request($criteria);
        foreach ($iterator as $data) {
            $type = $data["DATA_TYPE"];
        }

        if ($type != "varchar") {

            $migration->changeField($table, "prefered_group", "prefered_group", "varchar(255) NOT NULL DEFAULT '[]'");
            $migration->migrationOneTable($table);

            $migration->changeField("glpi_plugin_mydashboard_groupprofiles", "prefered_group", "prefered_group", "varchar(255) NOT NULL DEFAULT '[]'");
            $migration->migrationOneTable($table);

            $pref  = new self();
            $prefs = $pref->find();
            foreach ($prefs as $p) {
                if ($p["prefered_group"] == "0") {
                    $p["prefered_group"] = "[]";
                } else {
                    $p["prefered_group"] = "[\"" . $p["prefered_group"] . "\"]";
                }
                $pref->update($p);
            }

            $prefgroup  = new Groupprofile();
            $prefgroups = $prefgroup->find();
            foreach ($prefgroups as $p) {
                if ($p["prefered_group"] == "0") {
                    $p["prefered_group"] = "[]";
                } else {
                    $p["prefered_group"] = "[\"" . $p["prefered_group"] . "\"]";
                }
                $prefgroup->update($p);
            }
        }

        if (!$DB->fieldExists($table, "color_palette")) {
            $migration->addField($table, "color_palette", "varchar(50)  NOT NULL DEFAULT ''");
            $migration->migrationOneTable($table);
        }
        if (!$DB->fieldExists($table, "prefered_type")) {
            $migration->addField($table, "prefered_type", "int {$default_key_sign} NOT NULL DEFAULT '0'");
            $migration->migrationOneTable($table);
        }

        if (!$DB->fieldExists($table, "prefered_category")) {
            $migration->addField($table, "prefered_category", "int {$default_key_sign} NOT NULL DEFAULT '0'");
            $migration->migrationOneTable($table);
        }

        if (!$DB->fieldExists($table, "automatic_refresh_delay")) {
            $migration->addField($table, "automatic_refresh_delay", "int {$default_key_sign} NOT NULL DEFAULT '10'");
            $migration->migrationOneTable($table);
        }

        $migration->changeField($table, "color_palette", "color_palette", "varchar(50)  NOT NULL DEFAULT ''");
        $migration->migrationOneTable($table);

        $migration->changeField($table, "id", "id", "int {$default_key_sign} NOT NULL AUTO_INCREMENT");
        $migration->migrationOneTable($table);

        $migration->changeField($table, "nb_widgets_width", "nb_widgets_width", "int {$default_key_sign} NOT NULL DEFAULT '3'");
        $migration->migrationOneTable($table);

        $migration->changeField($table, "prefered_entity", "prefered_entity", "int {$default_key_sign} NOT NULL DEFAULT '0'");
        $migration->migrationOneTable($table);

        $migration->changeField($table, "replace_central", "replace_central", "tinyint NOT NULL DEFAULT 0");
        $migration->migrationOneTable($table);

        $migration->changeField($table, "automatic_refresh_delay", "automatic_refresh_delay", "int {$default_key_sign} NOT NULL DEFAULT '10'");
        $migration->migrationOneTable($table);

        if (!$DB->fieldExists($table, "prefered_year")) {
            $migration->addField($table, "prefered_year", "tinyint NOT NULL DEFAULT '0'");
            $migration->migrationOneTable($table);
        }

    }

    public static function uninstall()
    {
        global $DB;

        $DB->dropTable(self::getTable(), true);

    }
}
