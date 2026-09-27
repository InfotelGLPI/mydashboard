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
 * Turns every table[data-md-datatable] rendered by widget_frame.html.twig into a
 * DataTable. The configuration travels as JSON in the attribute; the grid state is
 * persisted per widget through ajax/state_save.php and ajax/state_load.php.
 *
 * Widgets are injected after page load (grid loading, refresh, new widget), so the
 * tables are picked up both on start and through a MutationObserver.
 */

const SELECTOR = 'table[data-md-datatable]';

const getCsrfToken = () => {
    const meta = document.querySelector('meta[property="glpi:csrf_token"]');
    return meta !== null ? meta.getAttribute('content') : '';
};

const getProfileId = () => {
    const field = document.getElementsByName('profiles_id')[0];
    return field !== undefined ? field.value : '';
};

/**
 * Flatten a nested object the way jQuery.param() does (order[0][0]=1), which is
 * what ajax/state_save.php reads from $_POST.
 */
const appendParams = (params, prefix, value) => {
    if (value === null || value === undefined) {
        return;
    }
    if (Array.isArray(value) || typeof value === 'object') {
        Object.entries(value).forEach(([key, item]) => {
            appendParams(params, `${prefix}[${key}]`, item);
        });
        return;
    }
    params.append(prefix, String(value));
};

const saveState = (config, state) => {
    const body = new URLSearchParams();
    Object.entries(state).forEach(([key, value]) => appendParams(body, key, value));
    body.append('gsId', config.gsId);
    body.append('profiles_id', getProfileId());

    fetch(config.saveUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'X-Glpi-Csrf-Token': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body,
    }).catch(() => {
        // A lost state save only means the table layout is not remembered.
    });
};

const loadState = (config, callback) => {
    const url = new URL(config.loadUrl, window.location.href);
    url.searchParams.set('gsId', config.gsId);
    url.searchParams.set('profiles_id', getProfileId());

    fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
        .then((response) => (response.ok ? response.json() : null))
        .then((state) => {
            // null when no state has been saved yet for this widget and profile
            if (state === null || typeof state !== 'object') {
                callback(null);
                return;
            }
            state.time = Date.now();
            callback(state);
        })
        .catch(() => callback(null));
};

const initTable = (table) => {
    if (table.dataset.mdDatatableReady === '1' || typeof window.DataTable !== 'function') {
        return;
    }

    let config;
    try {
        config = JSON.parse(table.dataset.mdDatatable);
    } catch {
        return;
    }
    table.dataset.mdDatatableReady = '1';

    new window.DataTable(table, {
        stateSave: true,
        stateSaveCallback: (settings, state) => saveState(config, state),
        stateLoadCallback: (settings, callback) => loadState(config, callback),
        order: config.order,
        colReorder: true,
        columnDefs: config.columnDefs,
        rowReorder: {
            selector: 'td:nth-child(2)',
        },
        responsive: true,
        language: config.language,
        dom: 'Bfrtip',
        select: true,
        lengthMenu: [
            [5, 10, 25, 50, -1],
            config.lengthMenuLabels,
        ],
        buttons: [
            'colvis',
            'pageLength',
            {
                extend: 'collection',
                text: config.exportLabel,
                buttons: ['copy', 'excel', 'csv', 'pdf', 'print'],
            },
        ],
    });
};

const initTablesIn = (root) => {
    if (root.matches?.(SELECTOR)) {
        initTable(root);
    }
    root.querySelectorAll?.(SELECTOR).forEach(initTable);
};

initTablesIn(document);

new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
            if (node.nodeType === Node.ELEMENT_NODE) {
                initTablesIn(node);
            }
        });
    });
}).observe(document.documentElement, {childList: true, subtree: true});
