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
