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
use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Mydashboard\Alert;
use Migration;
use Reminder;
use Session;

/**
 * Class ItilAlert
 */
class ItilAlert extends CommonDBTM
{
    /**
     * @param $item
     */
    public function showForItem($item)
    {
        // Same boundary as Alert::displayTabContentForItem(), replayed here because this
        // method emits the configuration form and its creation button on its own.
        Session::checkRight(Alert::$rightname, UPDATE);

        $items_id = $item->getID();
        $item->getFromDB($items_id);
        $itemtype = $item->getType();
        $this->getFromDBByCrit(['items_id' => $items_id,
            'itemtype' => $itemtype]);

        $reminder = new Reminder();
        $reminders_id = $this->fields['reminders_id'] ?? 0;

        $create_button = null;
        if ($reminders_id == 0) {
            // Handled by public/scripts/alert-item.js from the data-* attributes.
            $create_button = [
                'menu_name' => Menu::getTypeName(2),
                'url' => PLUGIN_MYDASHBOARD_WEBDIR . '/ajax/createalert.php',
                'itemtype' => $itemtype,
                'items_id' => (int) $items_id,
                'script_url' => PLUGIN_MYDASHBOARD_WEBDIR . '/scripts/alert-item.js',
            ];
        }

        $reminder_data = null;
        $alert_form = null;
        if ($reminders_id > 0) {
            $reminder->getFromDB($reminders_id);
            // The reminder text is stored raw (rich text) in GLPI 11: the template runs it
            // through |safe_html, which keeps the allowed formatting but strips scripts and
            // event handlers (stored XSS for any user opening this tab otherwise).
            $reminder_data = [
                'name' => $reminder->getNameID(),
                'url' => $reminder->getLinkURL(),
                'text' => $reminder->fields['text'],
            ];

            $alert = new Alert();
            $alert->getFromDBByCrit(['reminders_id' => $reminders_id]);
            $alert_form = $alert->getAlertFormParams(
                $reminders_id,
                _n('Network alert', 'Network alerts', 1, 'mydashboard'),
                [],
                true,
            );
        }

        TemplateRenderer::getInstance()->display('@mydashboard/alert_item.html.twig', [
            'create_button' => $create_button,
            'reminder' => $reminder_data,
            'alert_form' => $alert_form,
        ]);

        if ($reminders_id > 0) {
            $reminder->showVisibility();
        }
    }

    public static function purgeAlerts(Reminder $reminder)
    {

        $alert = new Alert();
        $alert->deleteByCriteria(['reminders_id' => $reminder->getField("id")]);

        $itilalert = new self();
        $itilalert->deleteByCriteria(['reminders_id' => $reminder->getField("id")]);
    }

    public static function install(Migration $migration)
    {
        global $DB;

        $default_charset   = DBConnection::getDefaultCharset();
        $default_collation = DBConnection::getDefaultCollation();
        $default_key_sign  = DBConnection::getDefaultPrimaryKeySignOption();
        $table  = self::getTable();

        if ($DB->tableExists("glpi_plugin_mydashboard_problemalerts")) {
            $migration->renameTable("glpi_plugin_mydashboard_problemalerts", $table);
        }

        if (!$DB->tableExists($table)) {
            $query = "CREATE TABLE `$table` (
                        `id` int {$default_key_sign} NOT NULL auto_increment,
                        `reminders_id` int {$default_key_sign}                            NOT NULL DEFAULT '0',
                        `items_id`     int {$default_key_sign}                            NOT NULL DEFAULT '0',
                        `itemtype`     varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'see .class.php file',
                        PRIMARY KEY (`id`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);

        }

        if (!$DB->fieldExists($table, "itemtype")) {
            $migration->addField($table, "itemtype", "varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'see .class.php file'");
            $migration->migrationOneTable($table);
        }
        if (!$DB->fieldExists($table, "items_id")) {

            $migration->changeField($table, "problems_id", "items_id", "int {$default_key_sign} NOT NULL DEFAULT '0'");
            $migration->migrationOneTable($table);

            $DB->update(
                $table,
                [
                    'itemtype' => 'Problem',
                ],
                [
                    1 => 1,
                ],
            );
        }
    }

    public static function uninstall()
    {
        global $DB;

        $DB->dropTable("glpi_plugin_mydashboard_problemalerts", true);

        $DB->dropTable(self::getTable(), true);

    }
}
