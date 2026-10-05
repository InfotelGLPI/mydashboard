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
 * Report links of the widgets (table counters, chart elements): the search parameters
 * are posted to ajax/launchURL.php, which answers with the URL of the matching search,
 * opened in a new window.
 */

/**
 * Flatten a nested value the way jQuery.param() does (params[key][0]=1), which is what
 * the endpoint reads from $_POST.
 */
const appendParams = (body, prefix, value) => {
    if (Array.isArray(value) || (value !== null && typeof value === 'object')) {
        Object.entries(value).forEach(([key, item]) => {
            appendParams(body, `${prefix}[${key}]`, item);
        });
        return;
    }
    body.append(prefix, value === null || value === undefined ? '' : String(value));
};

/**
 * @param {string} url    endpoint answering with a path under the GLPI root
 * @param {Object} fields posted fields, nested values included
 */
export const openLaunchUrl = (url, fields) => {
    if (typeof url !== 'string' || url === '') {
        return;
    }

    const body = new URLSearchParams();
    Object.entries(fields).forEach(([key, value]) => appendParams(body, key, value));

    // Opened before the request so that the browser does not treat it as a popup.
    const target = window.open('', '_blank');

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body,
    })
        .then((response) => (response.ok ? response.text() : ''))
        .then((link) => {
            link = link.trim();
            // launchURL.php only answers paths under the GLPI root
            if (link.startsWith('/') && !link.startsWith('//') && target !== null) {
                target.opener = null;
                target.location.href = link;
            } else if (target !== null) {
                target.close();
            }
        })
        .catch(() => {
            if (target !== null) {
                target.close();
            }
        });
};
