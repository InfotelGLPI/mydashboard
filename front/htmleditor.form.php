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

use Glpi\RichText\RichText;
use GlpiPlugin\Mydashboard\Customswidget;

Session::checkRight("plugin_mydashboard_config", UPDATE);

$customsWidget = new Customswidget();

if (isset($_POST['update'])) {
    // Decode the HTML marks then sanitize the free-HTML widget content before
    // storing it. This keeps the allowed formatting but strips scripts and event
    // handlers, closing the stored-XSS path where a config admin could persist a
    // payload executed in every user's browser that displays this shared widget.
    $_POST["content"] = RichText::getSafeHtml(
        html_entity_decode($_POST["content"]),
    );
    $customsWidget->update($_POST);

    Html::back();
}
