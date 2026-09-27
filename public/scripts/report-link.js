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

/*
 * Counters of the report tables (Reports_Table 32 and 33) are rendered by
 * widget_frame.html.twig as buttons carrying data-md-report-link: the endpoint and the
 * search parameters as JSON. A click posts them to ajax/launchURL.php, which answers
 * with the URL of the matching ticket search, opened in a new window.
 *
 * Widgets are injected after page load, so a single delegated listener is used.
 */

import {openLaunchUrl} from './launch-url.js';

const openReportLink = (button) => {
    let config;
    try {
        config = JSON.parse(button.dataset.mdReportLink);
    } catch {
        return;
    }
    openLaunchUrl(config.url, config.params ?? {});
};

document.addEventListener('click', (event) => {
    const button = event.target.closest?.('[data-md-report-link]');
    if (button === null || button === undefined) {
        return;
    }
    event.preventDefault();
    openReportLink(button);
});

/*
 * Report cells describe their content with Bootstrap tooltips (data-bs-toggle). The
 * core initialises them after jQuery requests only, while widgets are loaded with
 * fetch(): initialise the ones of every injected widget.
 */
new MutationObserver((mutations) => {
    if (typeof window.initTooltips !== 'function') {
        return;
    }
    mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
            if (node.nodeType === Node.ELEMENT_NODE && node.querySelector('[data-bs-toggle="tooltip"]') !== null) {
                window.initTooltips(node);
            }
        });
    });
}).observe(document.documentElement, {childList: true, subtree: true});
