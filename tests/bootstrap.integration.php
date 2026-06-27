<?php

/*
 -------------------------------------------------------------------------
 mydashboard plugin for GLPI
 Copyright (C) 2016-2026 by the mydashboard Development Team.

 https://github.com/InfotelGLPI/mydashboard
 -------------------------------------------------------------------------

 LICENSE

 This file is part of mydashboard.

 mydashboard is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 3 of the License, or
 (at your option) any later version.

 mydashboard is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with mydashboard. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

// Bootstrap GLPI (connexion DB, session, autoloader GLPI core)
require_once dirname(__DIR__, 3) . '/tests/bootstrap.php';

// Enregistrement des namespaces du plugin dans l'autoloader Composer déjà chargé
$loader = require dirname(__DIR__, 3) . '/vendor/autoload.php';
$loader->addPsr4('GlpiPlugin\\Mydashboard\\', dirname(__DIR__) . '/src/');
$loader->addPsr4('GlpiPlugin\\Mydashboard\\Tests\\', dirname(__DIR__) . '/tests/');

// Initialisation du plugin si nécessaire (filet de sécurité : normalement géré par glpi:plugin:install)
if (!defined('PLUGIN_MYDASHBOARD_VERSION')) {
    require_once dirname(__DIR__) . '/setup.php';
}
global $DB;
if (!$DB->tableExists('glpi_plugin_mydashboard_widgets')) {
    require_once dirname(__DIR__) . '/hook.php';
    plugin_mydashboard_install();
}
