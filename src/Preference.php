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

namespace GlpiPlugin\Mydashboard;

use CommonDBTM;
use CommonGLPI;
use DBConnection;
use DbUtils;
use Dropdown;
use Entity;
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
        //If user has no preferences yet, we set default values
        if (!$this->getFromDB($user_id)) {
            $this->initPreferences($user_id);
            $this->getFromDB($user_id);
        }

        //Preferences are not deletable
        $options['candel']  = false;
        $options['colspan'] = 1;

        $this->showFormHeader($options);

        echo "<tr class='tab_bg_1'><td>" . __("Automatic refreshing of the widgets that can be refreshed", "mydashboard") . "</td>";
        echo "<td>";
        Dropdown::showYesNo("automatic_refresh", $this->fields['automatic_refresh']);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'><td>" . __("Refresh every ", "mydashboard") . "</td>";
        echo "<td>";
        Dropdown::showFromArray(
            "automatic_refresh_delay",
            [1 => 1, 2 => 2, 5 => 5, 10 => 10, 30 => 30, 60 => 60],
            ["value" => $this->fields['automatic_refresh_delay']]
        );
        echo " " . __('minute(s)', "mydashboard");
        echo "</td>";
        echo "</tr>";
        //Since 1.0.3 replace_central is now a preference
        echo "<tr class='tab_bg_1'><td>" . __("Replace central interface", "mydashboard") . "</td>";
        echo "<td>";
        Dropdown::showYesNo("replace_central", $this->fields['replace_central']);
        echo "</td>";
        echo "</tr>";

        if (Session::getCurrentInterface()
            && Session::getCurrentInterface() == 'central') {
            echo "<tr class='tab_bg_1'><td>" . __("My prefered groups for widget", "mydashboard") . "</td>";
            echo "<td>";
            $params = ['name'      => 'prefered_group',
                'value'     => $this->fields['prefered_group'],
                'entity'    => $_SESSION['glpiactiveentities'],
                'condition' => '`is_assign`'];

            $dbu    = new DbUtils();
            $result = $dbu->getAllDataFromTable(Group::getTable(), ['is_assign' => 1]);
            $pref   = json_decode($this->fields['prefered_group'], true);

            if (!is_array($pref)) {
                $pref = [];
            }
            //      $opt['technicians_groups_id'] = is_array($opt['technicians_groups_id']) ? $opt['technicians_groups_id'] : [$opt['technicians_groups_id']];
            $temp = [];
            foreach ($result as $item) {
                $temp[$item['id']] = $item['name'];
            }

            $params = [
                "name"                => 'prefered_group',
                'entity'              => $_SESSION['glpiactiveentities'],
                "display"             => false,
                "multiple"            => true,
                "width"               => '200px',
                'values'              => $pref ?? [],
                'display_emptychoice' => true,
            ];

            $dropdown = Dropdown::showFromArray("prefered_group", $temp, $params);

            echo $dropdown;
            //      Group::dropdown($params);
            echo "</td>";
            echo "</tr>";
        }
        echo "<tr class='tab_bg_1'><td>" . __("My requester prefered groups for widget", "mydashboard") . "</td>";
        echo "<td>";
        $params = ['name'      => 'requester_prefered_group',
            'value'     => $this->fields['requester_prefered_group'],
            'entity'    => $_SESSION['glpiactiveentities'],
            'condition' => '`is_requester`'];

        $dbu    = new DbUtils();
        $result = $dbu->getAllDataFromTable(Group::getTable(), ['is_requester' => 1]);
        $pref   = json_decode($this->fields['requester_prefered_group'], true);

        if (!is_array($pref)) {
            $pref = [];
        }
        //      $opt['technicians_groups_id'] = is_array($opt['technicians_groups_id']) ? $opt['technicians_groups_id'] : [$opt['technicians_groups_id']];
        $temp = [];
        foreach ($result as $item) {
            $temp[$item['id']] = $item['name'];
        }

        $params = [
            "name"                => 'requester_prefered_group',
            'entity'              => $_SESSION['glpiactiveentities'],
            "display"             => false,
            "multiple"            => true,
            "width"               => '200px',
            'values'              => $pref ?? [],
            'display_emptychoice' => true,
        ];

        echo Dropdown::showFromArray("requester_prefered_group", $temp, $params);

        //      Group::dropdown($params);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'><td>" . __("My prefered entity for widget", "mydashboard") . "</td>";
        echo "<td>";
        $params = ['name'   => 'prefered_entity',
            'value'  => $this->fields['prefered_entity'],
            'entity' => $_SESSION['glpiactiveentities']];
        Entity::dropdown($params);
        echo "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td>" . __('My favorite category for widgets', 'mydashboard') . "</td>";
        echo "<td>";

        $params = [
            'name' => 'prefered_category',
            'value' => $this->fields['prefered_category'],
            'multiple' => false,
            'display' => false,
            'width' => '200px',
            'entity' => $_SESSION['glpiactiveentities'],
            'display_emptychoice' => true,
            'condition' => [['OR' => ['is_request' => 1, 'is_incident' => 1]]],
        ];

        $dropdownCategory = ITILCategory::dropdown($params);
        echo $dropdownCategory;
        echo "</td>";
        echo "</tr>";
        echo "<tr class='tab_bg_1'><td>" . __("My prefered type for widget", "mydashboard") . "</td>";
        echo "<td>";
        $params = ['value'  => $this->fields['prefered_type'],'toadd' => [0 => Dropdown::EMPTY_VALUE]];
        Ticket::dropdownType('prefered_type', $params);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'><td>" . __("Palette color", "mydashboard") . "</td>";
        echo "<td>";
        echo \Html::select(
            'color_palette',
            $this->getPalettes(),
            [
                'id'        => 'theme-selector',
                'selected'  => $this->fields['color_palette'],
            ]
        );
        echo "</td>";
        echo "</tr>";


        $this->showFormButtons($options);

        $blacklist = new PreferenceUserBlacklist();
        $blacklist->showUserForm(Session::getLoginUserID());
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
        return self::checkPreferenceValue('color_palette', $users_id);
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
                ]
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

            $migration->changeField($table, "prefered_group" , "prefered_group", "varchar(255) NOT NULL DEFAULT '[]'");
            $migration->migrationOneTable($table);

            $migration->changeField("glpi_plugin_mydashboard_groupprofiles", "prefered_group" , "prefered_group", "varchar(255) NOT NULL DEFAULT '[]'");
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

    }

    public static function uninstall()
    {
        global $DB;

        $DB->dropTable(self::getTable(), true);

    }
}
