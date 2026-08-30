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

use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Mydashboard\Criterias\DisplayData;
use GlpiPlugin\Mydashboard\Criterias\Year;

if (strpos($_SERVER['PHP_SELF'], "dropdownUpdateDisplaydata.php")) {
    header("Content-Type: text/html; charset=UTF-8");
    Html::header_nocache();
}

Session::checkRightsOr("plugin_mydashboard", [READ, CREATE + UPDATE]);

// Refresh the period block of a criteria bar after the mode dropdown changed.
// The current values are not posted, so every field falls back to its default -- this
// was already the case before, $opt was never defined in this endpoint.
if (isset($_POST["value"])) {
    $rand = mt_rand();
    $form = "";

    if ($_POST['value'] == "START_END") {
        // Same block as the inline criterion, rendered from the shared template
        $form = TemplateRenderer::getInstance()->render('@mydashboard/criteria_period_fields.html.twig', [
            'rand' => $rand,
            'fields' => DisplayData::getPeriodFields($rand, [], 0),
        ]);
    } elseif ($_POST['value'] == 'YEAR') {
        $form = TemplateRenderer::getInstance()->render('@mydashboard/criteria_period_year.html.twig', [
            'year_html' => Year::YearDropdown(date('Y', time())),
        ]);
    } elseif ($_POST['value'] == 'BEGIN_END') {
        $form = TemplateRenderer::getInstance()->render('@mydashboard/criteria_period_range.html.twig', [
            'begin_html' => Html::showDateTimeField(
                "begin",
                ['value' => null, 'maybeempty' => false, 'display' => false],
            ),
            'end_html' => Html::showDateTimeField(
                "end",
                ['value' => null, 'maybeempty' => false, 'display' => false],
            ),
        ]);
    }

    echo $form;
}
