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
 * Translations tab of the configuration (ConfigTranslation::showTranslations()). The
 * "Add a new translation" button and a click on a row load the translation form in the
 * container, whose data-* attributes carry the parent item: no PHP value reaches a script.
 */

const getCsrfToken = () => {
    const meta = document.querySelector('meta[property="glpi:csrf_token"]');
    return meta !== null ? meta.getAttribute('content') : '';
};

/**
 * The form carries the scripts of its dropdowns: a range fragment keeps them runnable
 * once inserted, unlike innerHTML.
 */
const replaceContent = (container, html) => {
    const range = document.createRange();
    range.selectNodeContents(container);
    range.deleteContents();
    container.append(range.createContextualFragment(html));
};

/**
 * @param {HTMLElement} container element carrying the data-md-translation-view attribute
 * @param {string}      id        translation to edit, -1 to add one
 */
const loadForm = (container, id) => {
    const body = new URLSearchParams({
        type: container.dataset.type,
        parenttype: container.dataset.parenttype,
        [container.dataset.parentFk]: container.dataset.parentId,
        id,
    });

    fetch(container.dataset.mdTranslationView, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'X-Glpi-Csrf-Token': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body,
    })
        .then((response) => response.text())
        .then((html) => replaceContent(container, html));
};

document.addEventListener('click', (event) => {
    const button = event.target.closest('button[data-md-translation-add]');
    if (button !== null) {
        const container = document.getElementById(button.dataset.mdTranslationAdd);
        if (container !== null) {
            loadForm(container, '-1');
        }
        return;
    }

    const row = event.target.closest('tr.cursor-pointer[data-id]');
    // The massive action checkbox of the row keeps its own behaviour
    if (row === null || event.target.closest('input, a, button, label') !== null) {
        return;
    }
    const table = row.closest('table[id]');
    if (table === null) {
        return;
    }
    const container = document.querySelector(
        `[data-md-translation-datatable="${CSS.escape(table.id)}"]`,
    );
    if (container !== null) {
        loadForm(container, row.dataset.id);
    }
});
