<?php

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
