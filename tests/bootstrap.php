<?php

$loader = require dirname(__DIR__, 3) . '/vendor/autoload.php';

$loader->addPsr4('GlpiPlugin\\Mydashboard\\', dirname(__DIR__) . '/src/');
$loader->addPsr4('GlpiPlugin\\Mydashboard\\Tests\\', dirname(__DIR__) . '/tests/');

// Stubs des fonctions de traduction GLPI (non disponibles sans le bootstrap complet)
if (!function_exists('__')) {
    function __(string $str, string $domain = ''): string
    {
        return $str;
    }
}

if (!function_exists('_n')) {
    function _n(string $singular, string $plural, int $nb, string $domain = ''): string
    {
        return $nb > 1 ? $plural : $singular;
    }
}

if (!function_exists('__s')) {
    function __s(string $str, string $domain = ''): string
    {
        return $str;
    }
}
