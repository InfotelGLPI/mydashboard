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

use Glpi\Exception\Http\AccessDeniedHttpException;
use GlpiPlugin\Mydashboard\Groupprofile;
use GlpiPlugin\Mydashboard\Config;
use GlpiPlugin\Mydashboard\Dashboard;

Session::checkRight(Config::$rightname, UPDATE);

$group = new Groupprofile();
if (isset($_POST["addGroup"])) {
    if (empty($_POST['groups_id'])) {
        Html::back();
    } else {
        $group->check(-1, CREATE, $_POST);

        // check() validates the creation of a Groupprofile row; it says nothing about the
        // profile that row points at. profiles_id was then used exactly as posted, both to
        // read and write the Groupprofile row and — the part that matters — to create or
        // update a core glpi_profilerights line. This is the only entry point of the plugin
        // that writes into a rights table of the core, and the global Config UPDATE right
        // gating it carries no notion of entity or profile hierarchy. Confront the posted id
        // with the profiles this session may actually administer, as ajax/saveGrid.php and
        // ajax/state_save.php already do.
        $profiles_id = (int) ($_POST['profiles_id'] ?? 0);
        if (!Dashboard::canManageProfile($profiles_id)) {
            throw new AccessDeniedHttpException();
        }

        if (isset($_POST["groups_id"]) && is_array($_POST["groups_id"])) {
            // The column stores a JSON list read back with json_decode() and used as group
            // ids: normalise it so nothing but ids can be persisted there.
            $_POST["groups_id"] = json_encode(array_values(array_map('intval', $_POST["groups_id"])));
        } else {
            $_POST["groups_id"] = "[]";
        }
        if ($group->getFromDBByCrit(['profiles_id' => $profiles_id])) {
            $group->update(['id'   => $group->fields['id'],
                'groups_id'   => $_POST['groups_id']]);
        } else {
            $group->add(['groups_id'   => $_POST['groups_id'],
                'profiles_id' => $profiles_id]);
        }

        if (isset($_POST["use_group_profile"])) {
            // The stored right is a boolean flag (consumed as == 1); constrain the posted value to {0,1}
            // so an arbitrary string / bitmask cannot land raw in glpi_profilerights.
            $use_group_profile = !empty($_POST["use_group_profile"]) ? 1 : 0;
            $profile = new ProfileRight();
            if ($profile->getFromDBByCrit(['profiles_id' => $profiles_id,
                'name'        => 'plugin_mydashboard_groupprofile'])) {
                $profile->update(['id'     => $profile->fields['id'],
                    'rights' => $use_group_profile]);
            } else {
                $profile->add(['profiles_id' => $profiles_id,
                    'name'        => 'plugin_mydashboard_groupprofile',
                    'rights'      => $use_group_profile]);
            }
        }
        Html::back();
    }
}
