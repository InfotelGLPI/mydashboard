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
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use Group;
use Html;
use Migration;
use ProfileRight;

/**
 * Class Groupprofile
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

class Groupprofile extends CommonDBTM
{
    public static $rightname = 'plugin_mydashboard';
    public $dohistory = true;

    /**
     * Add a category to profile
     * @global type $CFG_GLPI
     *
     * @param type  $profiles_id
     * @param type  $canedit
     */
    public static function addGroup($profiles_id, $canedit)
    {
        if (!$canedit) {
            return;
        }

        $checked = false;
        $profilerights = new ProfileRight();
        if ($profilerights->getFromDBByCrit(['profiles_id' => $profiles_id,
            'name'        => 'plugin_mydashboard_groupprofile'])) {
            $checked = (bool) $profilerights->fields['rights'];
        }

        $groupprofile = new Groupprofile();
        $groups_id = [];
        if ($groupprofile->getFromDBByCrit(['profiles_id' => $profiles_id])) {
            $groups_id = json_decode($groupprofile->fields['groups_id']);
        }

        $dbu = new DbUtils();
        $groups = [];
        foreach ($dbu->getAllDataFromTable(Group::getTable(), ['is_assign' => 1]) as $group) {
            $groups[$group['id']] = $group['name'];
        }

        echo TemplateRenderer::getInstance()->render('@mydashboard/groupprofile_form.html.twig', [
            'form_action' => PLUGIN_MYDASHBOARD_WEBDIR . '/front/groupprofile.form.php',
            'hidden_html' => Html::hidden('profiles_id', ['value' => $profiles_id]),
            'checkbox_html' => Html::getCheckbox(['name' => 'use_group_profile', 'checked' => $checked]),
            'groups_dropdown_html' => Dropdown::showFromArray('groups_id', $groups, [
                'name' => 'groups_id',
                'entity' => $_SESSION['glpiactive_entity'],
                'display' => false,
                'multiple' => true,
                'width' => '200px',
                'values' => $groups_id ?: [],
                'display_emptychoice' => true,
            ]),
            'submit_html' => Html::submit(_sx('button', 'Save'), ['name' => 'addGroup',
                'class' => 'btn btn-primary',
            ]),
            'close_form_html' => Html::closeForm(false),
        ]);
    }

    public function getProfilGroup($profiles_id)
    {
        $group = 0;
        $profilerights = new ProfileRight();
        if ($profilerights->getFromDBByCrit(['profiles_id' => $profiles_id,
            'name'        => 'plugin_mydashboard_groupprofile'])) {
            if ($profilerights->fields['rights'] == 1) {
                if ($this->getFromDBByCrit(['profiles_id' => $profiles_id])) {
                    $group = $this->fields['groups_id'];
                }
                return $group;
            }
        }
        return false;
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
                        `groups_id`  varchar(255) NOT NULL DEFAULT '[]',
                        `profiles_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                        PRIMARY KEY (`id`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);

        }
    }

    public static function uninstall()
    {
        global $DB;

        $DB->dropTable(self::getTable(), true);

    }
}
