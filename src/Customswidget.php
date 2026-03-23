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

use CommonDropdown;
use DBConnection;
use Migration;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class Customswidget
 */
class Customswidget extends CommonDropdown
{
    /**
     * @param int $nb
     *
     * @return string
     * @override
     */
    public static function getTypeName($nb = 0)
    {
        return __('Custom Widgets', 'mydashboard');
    }

    public static function getIcon()
    {
        return Menu::getIcon();
    }

    /**
     * Display tab for each customwidget
     * @override
     */
    public function defineTabs($options = [])
    {
        $ong = [];

        $this->addDefaultFormTab($ong);
        $this->addStandardTab(HTMLEditor::class, $ong, $options);
        return $ong;
    }

    /**
     * @return array
     * @throws \GlpitestSQLError
     */
    public static function listCustomsWidgets()
    {
        global $DB;

        $customsWidgets = [];

        $iterator = $DB->request([
            'SELECT'    => '*',
            'FROM'      => Customswidget::getTable()
        ]);

        foreach ($iterator as $data) {
            $customsWidgets[] = $data;
        }

        return $customsWidgets;
    }


    /**
     * @param $id
     *
     * @return string[]|null
     * @throws \GlpitestSQLError
     */
    private static function getCustomWidgetById($id)
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT'    => '*',
            'FROM'      => Customswidget::getTable(),
            'WHERE'     => [
                'id'  => $id
            ],
        ]);

        foreach ($iterator as $data) {
            return $data;
        }

        return null;
    }

    /**
     * @param $id
     *
     * @return string[]|null
     * @throws \GlpitestSQLError
     */
    public static function getCustomWidget($id)
    {
        $temp = self::getCustomWidgetById($id);

        return $temp;
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
                        `name`    varchar(255) NOT NULL,
                        `comment` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                        `content` text         NOT NULL,
                        PRIMARY KEY (`id`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);

            $startTitle = '<p style="background-color: lightgrey; padding: 5px; font-weight: bold; border: solid 1px black;">';
            $endTitle   = ' </p>';

            // Insert default title in table customwidgets
            $DB->insert(
                "glpi_plugin_mydashboard_customswidgets",
                [
                    'name'    => __('Incidents', 'mydashboard'),
                    'content' => $startTitle . __("Incidents", 'mydashboard') . $endTitle,
                    'comment' => '',
                ]
            );

            $DB->insert(
                "glpi_plugin_mydashboard_customswidgets",
                [
                    'name'    => __('Requests', 'mydashboard'),
                    'content' => $startTitle . __("Requests", 'mydashboard') . $endTitle,
                    'comment' => '',
                ]
            );

            $DB->insert(
                "glpi_plugin_mydashboard_customswidgets",
                [
                    'name'    => __('Problems'),
                    'content' => $startTitle . __("Problems") . $endTitle,
                    'comment' => '',
                ]
            );
        }
    }

    public static function uninstall()
    {
        global $DB;

        $DB->dropTable(self::getTable(), true);

    }
}
