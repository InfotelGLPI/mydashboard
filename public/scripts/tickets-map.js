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
 * OpenStreetMap of the tickets by location (report_map.html.twig, Reports_Map). The
 * configuration is read from data-md-map; the points come from ajax/map.php and are
 * grouped in clusters whose size is the sum of the tickets of their markers.
 *
 * Leaflet and its plugins (markercluster, awesome-markers, spin) and initMap() are
 * provided by the core on every page. Widgets are injected after page load (grid
 * loading, refresh), so the maps are picked up both on start and through a
 * MutationObserver. The popups are built as DOM nodes: no value is parsed as HTML.
 */

const SELECTOR = '[data-md-map]';

const MAP_HEIGHT = '500px';

// Marker colour by number of tickets at the location: [upper bound, colour]
const MARKER_COLORS = [
    [10, 'blue'],
    [100, 'cadetblue'],
    [1000, 'purple'],
    [5000, 'darkpurple'],
    [10000, 'red'],
    [Infinity, 'darkred'],
];

/**
 * Serializes the search parameters the way jQuery.param() did (bracket notation),
 * which is what ajax/map.php reads from $_POST['params'].
 */
const appendParams = (body, value, prefix) => {
    if (value !== null && typeof value === 'object') {
        Object.entries(value).forEach(([key, child]) => appendParams(body, child, `${prefix}[${key}]`));
    } else {
        body.append(prefix, value ?? '');
    }
};

const markerIcons = () => {
    L.AwesomeMarkers.Icon.prototype.options.prefix = 'fas';
    return MARKER_COLORS.map(([limit, color]) => [
        limit,
        L.AwesomeMarkers.icon({icon: 'circle', markerColor: color}),
    ]);
};

const clusterIcon = (cluster) => {
    const count = cluster.getAllChildMarkers().reduce((total, marker) => total + marker.count, 0);
    let size = 'large';
    if (count < 10) {
        size = 'small';
    } else if (count < 100) {
        size = 'medium';
    }

    const inner = document.createElement('div');
    const span = document.createElement('span');
    span.textContent = String(count);
    inner.append(span);

    return new L.DivIcon({
        html: inner,
        className: `marker-cluster marker-cluster-${size}`,
        iconSize: new L.Point(40, 40),
    });
};

const popupContent = (point, config) => {
    const content = document.createElement('div');

    const title = document.createElement('strong');
    title.textContent = point.title;
    content.append(title, document.createElement('br'));

    const link = document.createElement('a');
    link.target = '_blank';
    link.href = config.target.replace('CURLOCATION', encodeURIComponent(point.loc_id));
    link.textContent = config.count_label.replace('COUNT', point.count);
    content.append(link);

    Object.values(point.types ?? {}).forEach((type) => {
        content.append(
            document.createElement('br'),
            config.type_label.replace('COUNT', type.count).replace('TYPE', type.name),
        );
    });

    return content;
};

const showFailure = (map, config, message, reload) => {
    const control = L.control();
    control.onAdd = () => {
        const box = L.DomUtil.create('div', 'fail_info');
        box.append(message || config.error_label, document.createElement('br'));

        const button = document.createElement('span');
        button.setAttribute('role', 'button');
        const icon = document.createElement('i');
        icon.className = 'ti ti-refresh';
        button.append(icon, ` ${config.reload_label}`);
        button.addEventListener('click', () => {
            control.remove();
            reload();
        });
        box.append(button);
        return box;
    };
    control.addTo(map);
};

const loadPoints = (map, config) => {
    const body = new URLSearchParams({itemtype: config.itemtype});
    appendParams(body, config.params, 'params');

    map.spin(true);
    fetch(config.url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body,
    })
        .then((response) => response.json().then((data) => {
            if (!response.ok || data.success === false) {
                return Promise.reject(data);
            }
            return data;
        }))
        .then((data) => {
            const icons = markerIcons();
            const markers = L.markerClusterGroup({iconCreateFunction: clusterIcon});

            Object.values(data.points ?? {}).forEach((point) => {
                const icon = icons.find(([limit]) => point.count < limit)[1];
                const marker = L.marker([point.lat, point.lng], {icon, title: point.title});
                marker.count = point.count;
                marker.bindPopup(() => popupContent(point, config));
                markers.addLayer(marker);
            });

            map.addLayer(markers);
            map.fitBounds(markers.getBounds(), {padding: [50, 50], maxZoom: 12});
        })
        .catch((error) => {
            showFailure(map, config, error?.message, () => loadPoints(map, config));
        })
        .finally(() => map.spin(false));
};

const draw = (element) => {
    const config = JSON.parse(element.dataset.mdMap);
    delete element.dataset.mdMap;
    // Provided by the core (js/common.js) together with Leaflet
    if (typeof window.initMap !== 'function' || typeof window.L === 'undefined') {
        return;
    }
    const map = window.initMap(window.jQuery(element), `${element.id}-map`, MAP_HEIGHT);
    loadPoints(map, config);
};

const collect = (root) => {
    if (root.matches?.(SELECTOR)) {
        draw(root);
    }
    root.querySelectorAll?.(SELECTOR).forEach(draw);
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
