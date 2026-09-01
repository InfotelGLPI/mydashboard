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

use GlpiPlugin\Mydashboard\Reports\Reports_Bar;
use GlpiPlugin\Mydashboard\Reports\Reports_Funnel;
use GlpiPlugin\Mydashboard\Reports\Reports_Line;
use GlpiPlugin\Mydashboard\Reports\Reports_Pie;
use GlpiPlugin\Mydashboard\Reports\Reports_Table;
use GlpiPlugin\Ocsinventoryng\Dashboard;

global $CFG_GLPI;

// The response is a bare URL consumed by window.open() on the JS side, never HTML.
// Serving it as text/plain (like the sibling endpoints serve application/json) keeps
// it out of any HTML parsing context, whatever a future link builder returns.
header('Content-Type: text/plain; charset=UTF-8');
Html::header_nocache();

Session::checkRightsOr("plugin_mydashboard", [READ, CREATE + UPDATE]);

//Case PluginMydashboardReports_Table32 / PluginMydashboardReports_Table33
if (isset($_POST['widget'])) {
    foreach ($_POST as $k => $v) {
        $_POST['params'][$k] = $v;
    }
}

$link = '';

$widget = $_POST["params"]["widget"] ?? '';

if ($widget === "PluginOcsinventoryngDashboard1") {
    $link = Dashboard::pluginOcsinventoryngDashboard1link($_POST);
} else {
    $classes = [Reports_Bar::class, Reports_Pie::class, Reports_Line::class, Reports_Table::class, Reports_Funnel::class];

    //Add custom classes. Only accept report classes shipped by a GLPI plugin
    // (GlpiPlugin\ namespace) that actually expose the fixed getLinkForWidget()
    // method: the widget name is fully attacker-controlled, so this prevents
    // triggering a static call (and a fatal error usable for class enumeration)
    // on an arbitrary existing class.
    $result = preg_replace('/\d+$/', '', $widget);
    if (is_string($result)
        && str_starts_with($result, 'GlpiPlugin\\')
        && class_exists($result)
        && method_exists($result, 'getLinkForWidget')
        && !in_array($result, $classes, true)) {
        $classes[] = $result;
    }

    foreach ($classes as $class) {
        if (str_starts_with($widget, $class)) {
            $link = $class::getLinkForWidget($widget, $_POST) ?? '';
            break;
        }
    }
}

// Every consumer feeds this response straight to window.open() (see the Charts and
// Reports_Table script blocks), so the URL itself is the sink: a "javascript:" or
// "data:" link would execute, and a protocol-relative "//host" one would be an open
// redirect. Link builders all return $CFG_GLPI['root_doc'] . '/front/...' URLs, so
// anything that is not a path under the GLPI root is dropped -- including the links
// produced by the third-party OCS Inventory NG branch above.
$root = $CFG_GLPI['root_doc'] ?? '';
if ($link !== ''
    && (str_starts_with($link, '//') || !str_starts_with($link, $root . '/'))) {
    $link = '';
}

echo $link;
