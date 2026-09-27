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
 * Dashboard page rendered by menu_grid.html.twig: GridStack grid, toolbar actions,
 * widget offcanvas, global filters, automatic refresh and PDF export.
 *
 * The configuration travels as JSON in the data-md-grid-config attribute of the grid.
 * Toolbar and widget buttons carry a data-md-action attribute handled by one delegated
 * listener. A few functions stay exposed on window because markup rendered elsewhere
 * calls them: refreshWidgetByForm() (criteria forms), refreshAll() (mydashboard.js),
 * plus the md_used_widgets list read by md-fuzzysearch.js.
 *
 * The grid can also arrive through the "My view" tab, loaded after the page: a module is
 * only run once per URL, so grids are picked up by a MutationObserver as well, and the
 * document listeners below act on the grid displayed last.
 */

const GRID_SELECTOR = '[data-md-grid-config]';

/** Controller of the grid currently displayed */
let current = null;

const getCsrfToken = () => {
    const meta = document.querySelector('meta[property="glpi:csrf_token"]');
    return meta !== null ? meta.getAttribute('content') : '';
};

/**
 * Flatten a nested object the way jQuery.param() does (params[type]=1), which is
 * what the ajax endpoints read from $_POST.
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

const post = (url, data) => {
    const body = new URLSearchParams();
    Object.entries(data).forEach(([key, value]) => appendParams(body, key, value));

    return fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'X-Glpi-Csrf-Token': getCsrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body,
    }).then((response) => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        return response;
    });
};

/**
 * Widget payloads carry inline scripts (chart and counter initialisation). A range
 * fragment keeps them runnable once inserted, unlike innerHTML.
 */
const replaceWithWidget = (target, html) => {
    const range = document.createRange();
    range.selectNode(target);
    target.replaceWith(range.createContextualFragment(html));
};

/**
 * Collect the fields of a criteria form: "name[]" fields are merged into arrays.
 */
const readForm = (form) => {
    const values = {};
    new FormData(form).forEach((value, rawName) => {
        const name = rawName.endsWith('[]') ? rawName.slice(0, -2) : rawName;
        if (!(name in values)) {
            values[name] = value;
        } else if (Array.isArray(values[name])) {
            values[name].push(value);
        } else {
            values[name] = [values[name], value];
        }
    });
    return values;
};

const makeButton = (action, gsid, title, buttonClass, iconClass) => {
    const button = document.createElement('button');
    button.type = 'button';
    button.title = title;
    button.className = `md-button ${buttonClass}`;
    button.dataset.mdAction = action;
    button.dataset.mdGsid = gsid;
    const icon = document.createElement('i');
    icon.className = iconClass;
    button.append(icon);
    return button;
};

const makePlaceholder = (domId) => {
    const placeholder = document.createElement('div');
    placeholder.id = domId;
    placeholder.className = 'md-widget-loading text-center p-3';
    return placeholder;
};

const setWidgetButtonsVisible = (gsid, visible) => {
    document.querySelectorAll(`[data-widgetid="${CSS.escape(gsid)}"]`).forEach((el) => {
        el.style.display = visible ? '' : 'none';
    });
};

const createController = (gridEl, config) => {
    const urls = config.urls;
    const labels = config.labels;
    // Widgets currently placed on the grid, refreshed together by the global filters
    const mounted = new Set();

    const grid = window.GridStack.init({
        cellHeight: 41,
        disableResize: !config.dragMode,
        disableDrag: !config.dragMode,
        margin: 2,
        sizeToContent: false,
        disableOneColumnMode: false,
        resizable: {
            handles: 'e, se, s, sw, w',
        },
    }, gridEl);

    const syncDisplayedIds = () => {
        window.mdDisplayedWidgetIds = Array.from(mounted);
    };

    /**
     * Place a widget frame on the grid: refresh and delete buttons, then the content
     * (a placeholder the widget HTML replaces once received).
     */
    const mountWidget = (node, content) => {
        const item = grid.addWidget({
            x: node.x,
            y: node.y,
            w: node.w,
            h: node.h,
            id: node.id,
        });

        const frame = document.createElement('div');
        frame.id = `gridcontent${node.id}`;
        // "refresh-icon" is what mydashboard.refreshAll() clicks
        frame.append(makeButton('refresh-widget', node.id, labels.refresh, 'refresh-icon pull-right', 'ti ti-refresh'));
        if (config.editMode > 0) {
            frame.append(makeButton('delete-widget', node.id, labels.delete, 'pull-left', 'ti ti-circle-x md-close'));
        }
        frame.append(content);
        item.querySelector('.grid-stack-item-content').append(frame);

        mounted.add(String(node.id));
        syncDisplayedIds();
    };

    const filters = () => Object.assign({}, window.mdGlobalFilters || {});

    const refreshWidget = (gsid) => {
        post(urls.refreshWidget, {gsid, params: filters()})
            .then((response) => response.json())
            .then((data) => {
                // An empty answer means the widget is not (or no longer) allowed
                const target = data && data.id ? document.getElementById(data.id) : null;
                if (target !== null) {
                    replaceWithWidget(target, data.widget);
                }
            })
            .catch(() => {
                // The previous content stays in place
            });
    };

    const refreshAll = () => {
        mounted.forEach((gsid) => refreshWidget(gsid));
    };

    const deleteWidget = (gsid) => {
        const item = gridEl.querySelector(`.grid-stack-item[gs-id="${CSS.escape(gsid)}"]`);
        if (item !== null) {
            grid.removeWidget(item);
        }
        mounted.delete(gsid);
        syncDisplayedIds();

        // The widget becomes available again in the offcanvas
        setWidgetButtonsVisible(gsid, true);
        const used = window.md_used_widgets;
        const index = Array.isArray(used) ? used.indexOf(gsid) : -1;
        if (index !== -1) {
            used.splice(index, 1);
        }
    };

    const addNewWidget = (gsid) => {
        if (!gsid || gsid === '0' || mounted.has(gsid)) {
            return false;
        }
        post(urls.refreshWidget, {gsid, params: filters()})
            .then((response) => response.json())
            .then((data) => {
                if (!data || !data.id) {
                    return;
                }
                const placeholder = makePlaceholder(data.id);
                mountWidget({x: 0, y: 0, w: 4, h: 12, id: gsid}, placeholder);
                replaceWithWidget(placeholder, data.widget);
            })
            .catch(() => {
                // Nothing was added
            });
        return true;
    };

    const refreshWidgetByForm = (id, gsid, formId) => {
        const form = document.getElementById(formId);
        const target = document.getElementById(id);
        if (form === null || target === null) {
            return;
        }
        const params = Object.assign(filters(), readForm(form));
        post(urls.refreshWidget, {gsid, params, id})
            .then((response) => response.text())
            .then((html) => replaceWithWidget(target, html))
            .catch(() => {
                // The previous content stays in place
            });
    };

    const reloadMenu = () => {
        window.location.href = urls.menu;
    };

    /**
     * Post back to the menu with the token the endpoint answered, so the selected
     * profile survives the reload.
     */
    const submitToMenu = (token) => {
        const form = document.createElement('form');
        form.action = urls.menu;
        form.method = 'post';
        [['profiles_id', config.activeProfile], ['_glpi_csrf_token', token.trim()]].forEach(([name, value]) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = String(value);
            form.append(input);
        });
        document.body.append(form);
        form.submit();
    };

    const saveGrid = (asDefault) => {
        const data = {
            data: JSON.stringify(grid.save(false)),
            profiles_id: config.activeProfile,
        };
        if (asDefault) {
            data.users_id = 0;
        }
        return post(urls.saveGrid, data)
            .then((response) => response.text())
            .then((token) => (asDefault ? submitToMenu(token) : reloadMenu()));
    };

    const toggleFullscreen = () => {
        if (document.fullscreenElement) {
            document.exitFullscreen();
        } else {
            gridEl.requestFullscreen();
        }
    };

    const exportPdf = async () => {
        const overlay = document.createElement('div');
        overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.6);display:flex;flex-direction:column;align-items:center;justify-content:center;z-index:9999;gap:12px;';
        const spinner = document.createElement('div');
        spinner.className = 'spinner-border text-light';
        spinner.style.cssText = 'width:3rem;height:3rem;';
        spinner.setAttribute('role', 'status');
        const label = document.createElement('div');
        label.className = 'text-white fw-semibold';
        label.textContent = labels.pdfGenerating;
        overlay.append(spinner, label);
        document.body.append(overlay);

        try {
            const dpr = window.devicePixelRatio || 1;
            const canvas = await window.html2canvas(gridEl, {
                scale: dpr,
                useCORS: true,
                logging: false,
                backgroundColor: '#ffffff',
                scrollX: 0,
                scrollY: 0,
            });

            const headerH = 28;
            const imgW = canvas.width / dpr;
            const imgH = canvas.height / dpr;

            const pdf = new window.jspdf.jsPDF({
                orientation: imgW >= imgH ? 'l' : 'p',
                unit: 'px',
                format: [imgW, imgH + headerH],
                hotfixes: ['px_scaling'],
            });

            // Header: centered title, date on the right
            pdf.setFontSize(11);
            pdf.setFont('helvetica', 'bold');
            pdf.setTextColor(40, 40, 40);
            pdf.text(labels.pdfTitle, imgW / 2, 18, {align: 'center'});
            pdf.setFontSize(8);
            pdf.setFont('helvetica', 'normal');
            pdf.setTextColor(130, 130, 130);
            pdf.text(new Date().toLocaleDateString(), imgW - 6, 18, {align: 'right'});
            pdf.setDrawColor(200, 200, 200);
            pdf.setLineWidth(0.5);
            pdf.line(6, headerH - 4, imgW - 6, headerH - 4);

            pdf.addImage(canvas.toDataURL('image/jpeg', 0.92), 'JPEG', 0, headerH, imgW, imgH);
            pdf.save(`dashboard_${new Date().toISOString().slice(0, 10)}.pdf`);
        } catch (err) {
            console.error('PDF export failed:', err);
            window.alert(labels.pdfError);
        } finally {
            overlay.remove();
        }
    };

    const actions = {
        'save-grid': () => saveGrid(false),
        'save-default-grid': () => saveGrid(true),
        'clear-grid': () => post(urls.clearGrid, {profiles_id: config.activeProfile, edit_mode: config.editMode})
            .then((response) => response.text())
            .then(submitToMenu),
        'edit-grid': () => post(urls.editGrid, {edit_mode: 1}).then(reloadMenu),
        'edit-default-grid': () => post(urls.editGrid, {edit_mode: 2}).then(reloadMenu),
        'close-edit': () => post(urls.editGrid, {edit_mode: 0}).then(reloadMenu),
        'drag-grid': () => post(urls.dragGrid, {drag_mode: 1}).then(reloadMenu),
        'undrag-grid': () => post(urls.dragGrid, {drag_mode: 0}).then(reloadMenu),
        'fullscreen': toggleFullscreen,
        'export-pdf': exportPdf,
        'refresh-widget': (button) => refreshWidget(button.dataset.mdGsid),
        'delete-widget': (button) => deleteWidget(button.dataset.mdGsid),
    };

    const start = () => {
        window.mdGlobalFilters = config.globalFilters;
        window.md_used_widgets = Array.isArray(config.usedWidgets) ? config.usedWidgets : [];

        config.grid.forEach((node) => {
            const domId = config.widgets[node.id];
            if (domId === undefined) {
                const missing = document.createElement('div');
                missing.textContent = labels.error;
                mountWidget(node, missing);
                return;
            }
            mountWidget(node, makePlaceholder(domId));
            refreshWidget(node.id);
        });
        syncDisplayedIds();

        if (config.autoRefreshMs > 0) {
            const timer = window.setInterval(() => {
                // Stops once the grid left the page (tab switched)
                if (!gridEl.isConnected) {
                    window.clearInterval(timer);
                    return;
                }
                refreshAll();
            }, config.autoRefreshMs);
        }
    };

    return {gridEl, actions, start, refreshAll, addNewWidget, refreshWidgetByForm};
};

const initGrid = (gridEl) => {
    if (gridEl.dataset.mdGridReady === '1' || typeof window.GridStack !== 'function') {
        return;
    }
    let config;
    try {
        config = JSON.parse(gridEl.dataset.mdGridConfig);
    } catch {
        return;
    }
    gridEl.dataset.mdGridReady = '1';

    current = createController(gridEl, config);
    current.start();
};

const initGridsIn = (root) => {
    if (root.matches?.(GRID_SELECTOR)) {
        initGrid(root);
    }
    root.querySelectorAll?.(GRID_SELECTOR).forEach(initGrid);
};

document.addEventListener('click', (event) => {
    if (current === null) {
        return;
    }
    const button = event.target.closest('[data-md-action]');
    if (button !== null && button.dataset.mdAction in current.actions) {
        event.preventDefault();
        Promise.resolve(current.actions[button.dataset.mdAction](button)).catch(() => {
            // The page stays as it is; the next reload shows the stored state
        });
        return;
    }

    // Offcanvas entries, rendered both by Widgetlist and by md-fuzzysearch.js
    const entry = event.target.closest('.plugin_mydashboard_menuDashboardListItem');
    if (entry !== null && current.addNewWidget(entry.dataset.widgetid)) {
        const gsid = entry.dataset.widgetid;
        setWidgetButtonsVisible(gsid, false);
        if (!window.md_used_widgets.includes(gsid)) {
            window.md_used_widgets.push(gsid);
        }
        const offcanvas = document.getElementById('md-widget-offcanvas');
        if (offcanvas !== null) {
            window.bootstrap.Offcanvas.getOrCreateInstance(offcanvas).hide();
        }
    }
});

// Plain selects of the edit toolbar (predefined grid, profile)
document.addEventListener('change', (event) => {
    const select = event.target.closest('select[data-md-submit-on-change]');
    if (select !== null && select.form !== null) {
        select.form.submit();
    }
});

document.addEventListener('fullscreenchange', () => {
    if (current !== null) {
        current.gridEl.classList.toggle('fullscreen_view', document.fullscreenElement === current.gridEl);
    }
});

// The global filters are core select2 dropdowns: select2 fires its change event through
// jQuery only, which never reaches a native listener, so this one has to be bound there.
window.jQuery(document).on('change', '#md-global-filter-bar select', () => {
    if (current === null) {
        return;
    }
    const values = {};
    document.querySelectorAll('#md-global-filter-bar select').forEach((select) => {
        if (!select.name) {
            return;
        }
        const key = select.name.replace(/^md_gf_/, '').replace(/\[\]$/, '');
        const value = select.multiple
            ? Array.from(select.selectedOptions, (option) => option.value).filter((v) => v !== '')
            : select.value;
        if (value === '' || (Array.isArray(value) && value.length === 0)) {
            return;
        }
        if (key === 'type' && parseInt(value, 10) === 0) {
            return;
        }
        values[key] = value;
    });
    window.mdGlobalFilters = values;
    current.refreshAll();
});

// Called by the criteria forms of the widgets (Criteria::getForm()) and by
// mydashboard.automaticRefreshAll()
window.refreshWidgetByForm = (id, gsid, formId) => {
    current?.refreshWidgetByForm(id, gsid, formId);
    return false;
};
window.refreshAll = () => current?.refreshAll();

initGridsIn(document);

new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
            if (node.nodeType === Node.ELEMENT_NODE) {
                initGridsIn(node);
            }
        });
    });
}).observe(document.documentElement, {childList: true, subtree: true});
