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
 * Behaviour of the alert widgets (Alert.php), driven by data-* attributes:
 * - span[data-md-countup]: counters of the stats and stock tiles, counted up from zero;
 * - [data-md-ticker]: news tickers of the alert / maintenance / information widgets,
 *   rolled by the jQuery newsTicker plugin; the description of the shown item is
 *   loaded from data-md-ticker-url.
 *
 * Widgets are injected after page load (grid loading, refresh, new widget), so they are
 * picked up both on start and through a MutationObserver. Registered through
 * ADD_JAVASCRIPT_MODULE: servicecatalog renders these widgets on its own pages too.
 */

const COUNTUP_SELECTOR = '[data-md-countup]';
const TICKER_SELECTOR = '[data-md-ticker]';

const COUNTUP_DURATION = 2000;
const FADE_DURATION = 200;

/**
 * Same easing as countUp.js (easeOutExpo), which drew these counters before.
 */
const easeOutExpo = (progress) => (progress >= 1 ? 1 : 1 - Math.pow(2, -10 * progress));

const countUp = (element) => {
    const end = Number.parseInt(element.dataset.mdCountup, 10);
    delete element.dataset.mdCountup;
    if (!Number.isFinite(end)) {
        return;
    }

    let start = null;
    const step = (timestamp) => {
        if (start === null) {
            start = timestamp;
        }
        const progress = Math.min((timestamp - start) / COUNTUP_DURATION, 1);
        element.textContent = Math.round(end * easeOutExpo(progress)).toLocaleString();
        if (progress < 1) {
            window.requestAnimationFrame(step);
        }
    };
    window.requestAnimationFrame(step);
};

const loadDescription = (container, list, infos) => {
    const first = list.querySelector('li');
    const text = infos?.querySelector('.infos-text');
    if (first === null || text === null || text === undefined) {
        return;
    }
    const item_id = first.getAttribute(`data-${container.dataset.mdTickerAttr}`) ?? '';
    const url = `${container.dataset.mdTickerUrl}?id=${encodeURIComponent(item_id)}`;
    const infos_container = document.getElementById(`${container.dataset.mdTicker}-infos-container`);

    if (infos_container !== null) {
        infos_container.style.transition = `opacity ${FADE_DURATION}ms`;
        infos_container.style.opacity = '0';
    }
    fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
        .then((response) => (response.ok ? response.text() : Promise.reject(response)))
        // Rendered by alert_ticker_description.html.twig, which escapes or sanitizes it
        .then((html) => {
            text.innerHTML = html;
        })
        .catch(() => {})
        .finally(() => {
            if (infos_container !== null) {
                infos_container.style.opacity = '1';
            }
        });
};

const startTicker = (container) => {
    const prefix = container.dataset.mdTicker;
    const $ = window.jQuery;
    // The ticker plugin only exists for jQuery (loaded through ADD_JAVASCRIPT)
    if (typeof $ !== 'function' || typeof $.fn.newsTicker !== 'function') {
        return;
    }
    const list = document.getElementById(prefix);
    if (list === null || container.dataset.mdTickerStarted !== undefined) {
        return;
    }
    container.dataset.mdTickerStarted = '1';

    const infos = document.getElementById(`${prefix}-infos`);
    const ticker = $(list).newsTicker({
        row_height: 60,
        max_rows: 1,
        speed: 300,
        duration: 6000,
        prevButton: $(document.getElementById(`${prefix}-prev`)),
        nextButton: $(document.getElementById(`${prefix}-next`)),
        hasMoved: () => loadDescription(container, list, infos),
    });

    if (infos !== null) {
        infos.addEventListener('mouseenter', () => ticker.newsTicker('pause'));
        infos.addEventListener('mouseleave', () => ticker.newsTicker('unpause'));
    }
};

const collect = (root) => {
    if (root.matches?.(COUNTUP_SELECTOR)) {
        countUp(root);
    }
    root.querySelectorAll?.(COUNTUP_SELECTOR).forEach(countUp);

    if (root.matches?.(TICKER_SELECTOR)) {
        startTicker(root);
    }
    root.querySelectorAll?.(TICKER_SELECTOR).forEach(startTicker);
};

collect(document);

new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
            if (node.nodeType === Node.ELEMENT_NODE) {
                collect(node);
            }
        });
    });
}).observe(document.documentElement, {childList: true, subtree: true});
