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
 * "Create a new alert" button of the alert tab (Alert::showForItem()). The item to copy
 * travels in data-* attributes of the button, so no PHP value reaches a script.
 */

document.addEventListener('click', (event) => {
    const button = event.target.closest('button[data-md-create-alert]');
    if (button === null) {
        return;
    }
    event.preventDefault();

    if (!window.confirm(button.dataset.confirm)) {
        return;
    }

    const body = new URLSearchParams({
        itemtype: button.dataset.itemtype,
        items_id: button.dataset.itemsId,
    });

    fetch(button.dataset.url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body,
    }).then(() => window.location.reload());
});
