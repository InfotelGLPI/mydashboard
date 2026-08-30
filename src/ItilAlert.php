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
use CommonITILObject;
use DBConnection;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Mydashboard\Alert;
use ITILCategory;
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
        $items_id = $item->getID();
        $item->getFromDB($items_id);
        $itemtype = $item->getType();
        $this->getFromDBByCrit(['items_id' => $items_id,
            'itemtype' => $itemtype]);

        $reminder = new Reminder();
        $reminders_id = $this->fields['reminders_id'] ?? 0;

        $create_button = null;
        if ($reminders_id == 0) {
            $button_id = 'mydashboard_create_alert_' . mt_rand();
            // The handler is bound by id rather than through an inline onclick, so the
            // itemtype and the confirmation label never reach an HTML attribute.
            $create_button = [
                'id' => $button_id,
                'menu_name' => Menu::getTypeName(2),
                'script_html' => \Html::scriptBlock(
                    '$(function () {'
                    . '$("#' . $button_id . '").on("click", function () {'
                    . 'if (!confirm(' . json_encode(__('Create a new alert', 'mydashboard')) . ')) { return; }'
                    . '$.ajax({'
                    . 'url: ' . json_encode(PLUGIN_MYDASHBOARD_WEBDIR . '/ajax/createalert.php') . ','
                    . 'type: "POST",'
                    . 'data: { itemtype: ' . json_encode($itemtype) . ', items_id: ' . (int) $items_id . ' },'
                    . 'success: function () { window.location.reload(); }'
                    . '});'
                    . '});'
                    . '});',
                ),
            ];
        }

        $reminder_data = null;
        $alert_data = null;
        if ($reminders_id > 0) {
            $reminder->getFromDB($reminders_id);
            $reminder_data = [
                'link_html' => nl2br($reminder->getLink()),
                // The reminder text is stored raw (rich text) in GLPI 11 and must be
                // sanitized at render time. getSafeHtml keeps the allowed formatting but
                // strips scripts/event handlers, closing a stored-XSS path where a
                // reminder author's payload would execute for any user opening this tab.
                'text_html' => \Glpi\RichText\RichText::getSafeHtml($reminder->fields['text']),
            ];

            $alert = new Alert();
            $alert->getFromDBByCrit(['reminders_id' => $reminders_id]);

            if (isset($alert->fields['id'])) {
                $id                = $alert->fields['id'];
                $impact            = $alert->fields['impact'];
                $itilcategories_id = $alert->fields['itilcategories_id'];
                $type              = $alert->fields['type'];
                $is_public         = $alert->fields['is_public'];
            } else {
                $id                = -1;
                $type              = 0;
                $impact            = 0;
                $itilcategories_id = 0;
                $is_public         = 0;
            }

            $types = [
                0 => _n('Network alert', 'Network alerts', 1, 'mydashboard'),
                1 => _n('Scheduled maintenance', 'Scheduled maintenances', 1, 'mydashboard'),
                2 => _n('Information', 'Informations', 1, 'mydashboard'),
            ];

            $impacts = [0 => __("No impact", "mydashboard")];
            for ($i = 1; $i <= 5; $i++) {
                $impacts[$i] = CommonITILObject::getImpactName($i);
            }

            $can_edit = Session::haveRight("reminder_public", UPDATE);
            $alert_data = [
                'form_action' => $alert->getFormURL(),
                'type_html' => Dropdown::showFromArray('type', $types, ['value' => $type,
                    'display' => false,
                ]),
                'impact_html' => Dropdown::showFromArray('impact', $impacts, ['value' => $impact,
                    'display' => false,
                ]),
                'category_html' => ITILCategory::dropdown([
                    'name' => 'itilcategories_id',
                    'value' => $itilcategories_id,
                    'entity' => $_SESSION['glpiactiveentities'],
                    'display' => false,
                ]),
                'public_html' => Dropdown::showYesNo('is_public', $is_public, -1, ['display' => false]),
                'can_edit' => $can_edit,
                'hidden_html' => \Html::hidden("id", ['value' => $id])
                    . \Html::hidden("reminders_id", ['value' => $reminders_id]),
                'submit_html' => \Html::submit(
                    $id > 0 ? _sx('button', 'Update') : _sx('button', 'Add'),
                    ['name' => 'update', 'class' => 'btn btn-primary'],
                ),
                'delete_html' => $id > 0
                    ? \Html::submit(_sx('button', 'Delete permanently'), ['name' => 'delete',
                        'class' => 'btn btn-primary',
                    ])
                    : '',
                'close_form_html' => \Html::closeForm(false),
            ];
        }

        echo TemplateRenderer::getInstance()->render('@mydashboard/itilalert_item.html.twig', [
            'create_button' => $create_button,
            'reminder' => $reminder_data,
            'alert' => $alert_data,
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
