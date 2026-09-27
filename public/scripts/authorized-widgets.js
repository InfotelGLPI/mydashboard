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

// "Authorize/Unauthorize all" of the authorized widgets form: toggles every yes/no
// select of the plugin rows (class given by data-md-toggle-all) at once.
document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-md-toggle-all]');
    if (toggle === null) {
        return;
    }

    const selects = [...document.querySelectorAll(`tr.${CSS.escape(toggle.dataset.mdToggleAll)} select`)];
    if (selects.length === 0) {
        return;
    }

    const next = selects[0].value === '0' ? '1' : '0';
    selects.forEach((select) => {
        select.value = next;
        // select2 listens to change (jQuery handlers also catch native events)
        select.dispatchEvent(new Event('change', { bubbles: true }));
    });
});
