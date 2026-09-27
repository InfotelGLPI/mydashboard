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
 * Criteria forms of the widgets (criteria_form.html.twig, built by Criteria::getForm()).
 *
 * - a click on the summary line ([data-md-criteria-toggle]) shows or hides the panel;
 * - the form ([data-md-criteria-form]) refreshes its widget, either when sent
 *   (data-refresh-on="submit") or as soon as a criterion changes.
 *
 * Widgets are injected after page load and the forms are also rendered by other plugins
 * (servicecatalog), so the listeners are delegated on the document.
 */

const refresh = (form) => {
    if (typeof window.refreshWidgetByForm !== 'function') {
        return;
    }
    window.refreshWidgetByForm(form.dataset.widgetId, form.dataset.gsid, form.id);
};

document.addEventListener('click', (event) => {
    const toggle = event.target.closest?.('[data-md-criteria-toggle]');
    if (toggle === null || toggle === undefined) {
        return;
    }
    const panel = document.getElementById(toggle.dataset.mdCriteriaToggle);
    if (panel === null) {
        return;
    }
    panel.style.width = '300px';
    panel.style.display = getComputedStyle(panel).display === 'none' ? 'block' : 'none';
});

document.addEventListener('submit', (event) => {
    const form = event.target.closest?.('[data-md-criteria-form]');
    if (form === null || form === undefined) {
        return;
    }
    event.preventDefault();
    if (form.dataset.refreshOn === 'submit') {
        refresh(form);
    }
});

const onChange = (target) => {
    const form = target.closest?.('[data-md-criteria-form]');
    if (form === null || form === undefined || form.dataset.refreshOn === 'submit') {
        return;
    }
    refresh(form);
};

// select2 reports its changes through jQuery only: a jQuery delegated handler sees them
// as well as the native ones.
if (window.jQuery !== undefined) {
    window.jQuery(document).on('change', '[data-md-criteria-form]', (event) => onChange(event.target));
} else {
    document.addEventListener('change', (event) => onChange(event.target));
}
