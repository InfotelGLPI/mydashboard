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
 * ECharts charts of the widgets. Chart::renderChart() renders the configuration as JSON
 * in a div[data-md-chart]; the chart is drawn in the element whose id is the chart name
 * (rendered by graph_header.html.twig, or by the plugins that contribute widgets).
 *
 * Widgets are injected after page load (grid loading, refresh, new widget), so the
 * configurations are picked up both on start and through a MutationObserver. Registered
 * through ADD_JAVASCRIPT_MODULE: other plugins draw these charts on their own pages too.
 */

import {openLaunchUrl} from './launch-url.js';

const SELECTOR = '[data-md-chart]';

const TOOLTIP_BACKGROUND = 'rgba(255,255,255)';
const PERCENT_FORMATTER = '{a} <br/>{b} : {c} ({d}%)';

const toolbox = (features) => ({
    show: true,
    feature: {
        ...features,
        dataView: {show: true, readOnly: false},
        restore: {show: true},
        saveAsImage: {show: true},
    },
});

const barOption = (chart) => ({
    tooltip: {
        backgroundColor: TOOLTIP_BACKGROUND,
        trigger: 'axis',
        axisPointer: {type: 'shadow'},
    },
    legend: {show: true, bottom: 0, itemHeight: 10, itemWidth: 8},
    toolbox: toolbox({magicType: {show: true, type: ['line', 'bar']}}),
    calculable: true,
    xAxis: [{type: 'category', data: chart.labels, axisPointer: {type: 'shadow'}}],
    yAxis: [{type: 'value'}, {type: 'value'}],
    series: chart.data,
});

const byIndex = (ids, params) => ids[params.dataIndex];
const bySeriesThenIndex = (ids, params) => ids[params.seriesIndex]?.[params.dataIndex];

/**
 * ECharts option of each chart type, and the id posted when an element is clicked.
 */
const TYPES = {
    bar: {
        option: barOption,
        selectedId: byIndex,
        sendComponent: true,
    },
    bar_multiple: {
        option: barOption,
        selectedId: bySeriesThenIndex,
    },
    bar_multiple_yaxis: {
        option: (chart) => {
            const option = barOption(chart);
            option.legend.data = chart.legends;
            option.yAxis = chart.yaxis;
            return option;
        },
        selectedId: bySeriesThenIndex,
    },
    bar_horizontal: {
        option: (chart) => {
            const option = barOption(chart);
            option.grid = {left: 16, right: 32, top: 32, bottom: 32, containLabel: true};
            option.yAxis = [{type: 'category', data: chart.labels}];
            option.xAxis = [{type: 'value'}];
            return option;
        },
        selectedId: byIndex,
    },
    pie: {
        option: (chart) => ({
            tooltip: {backgroundColor: TOOLTIP_BACKGROUND, trigger: 'item'},
            legend: {left: 'center', top: 'bottom', data: chart.labels},
            toolbox: toolbox({}),
            calculable: true,
            series: [{
                type: 'pie',
                name: chart.label,
                radius: '50%',
                data: chart.data,
                emphasis: {
                    itemStyle: {shadowBlur: 10, shadowOffsetX: 0, shadowColor: 'rgba(0, 0, 0, 0.5)'},
                },
                top: '-20%',
            }],
        }),
        selectedId: byIndex,
    },
    polar: {
        option: (chart) => ({
            tooltip: {backgroundColor: TOOLTIP_BACKGROUND, trigger: 'item', formatter: PERCENT_FORMATTER},
            legend: {top: 'bottom', data: chart.labels},
            toolbox: toolbox({mark: {show: true}}),
            series: [{
                type: 'pie',
                name: chart.label,
                radius: [20, 140],
                center: ['50%', '50%'],
                roseType: 'area',
                itemStyle: {borderRadius: 8},
                data: chart.data,
                top: '-20%',
            }],
        }),
        selectedId: byIndex,
    },
    donut: {
        option: (chart) => ({
            tooltip: {backgroundColor: TOOLTIP_BACKGROUND, trigger: 'item', formatter: PERCENT_FORMATTER},
            legend: {left: 'center', top: 'bottom', data: chart.labels},
            toolbox: toolbox({}),
            calculable: true,
            series: [{
                type: 'pie',
                radius: ['40%', '70%'],
                avoidLabelOverlap: false,
                name: chart.label,
                label: {show: false, position: 'center'},
                emphasis: {label: {show: true, fontSize: '40', fontWeight: 'bold'}},
                labelLine: {show: false},
                data: chart.data,
            }],
        }),
        selectedId: byIndex,
    },
    funnel: {
        option: (chart) => ({
            tooltip: {backgroundColor: TOOLTIP_BACKGROUND, trigger: 'item', formatter: PERCENT_FORMATTER},
            legend: {data: chart.labels},
            toolbox: toolbox({}),
            series: [{
                type: 'funnel',
                name: chart.title,
                left: '10%',
                top: 60,
                bottom: 60,
                width: '80%',
                min: 0,
                max: 100,
                minSize: '0%',
                maxSize: '100%',
                sort: 'descending',
                gap: 2,
                label: {show: true, position: 'inside', formatter: '{b} : {c} - {d}%'},
                labelLine: {length: 10, lineStyle: {width: 1, type: 'solid'}},
                itemStyle: {borderColor: '#fff', borderWidth: 1},
                emphasis: {label: {fontSize: 20}},
                data: chart.data,
            }],
        }),
        selectedId: byIndex,
    },
};

/** Drawn charts by name: a refreshed widget renders a new element with the same id. */
const drawn = new Map();

/** Configurations whose element or chart engine is not there yet. */
const pending = new Set();

const dispose = (name) => {
    const previous = drawn.get(name);
    if (previous === undefined) {
        return;
    }
    previous.observer.disconnect();
    if (!previous.instance.isDisposed()) {
        previous.instance.dispose();
    }
    drawn.delete(name);
};

const bindClick = (instance, chart, type) => {
    const click = chart.click;
    if (click === null || typeof click !== 'object') {
        return;
    }
    instance.on('click', (params) => {
        const fields = {params: click.params ?? {}};
        const selectedId = type.selectedId(chart.ids ?? [], params);
        if (selectedId !== undefined) {
            fields.selected_id = selectedId;
        }
        if (type.sendComponent === true) {
            fields.selectedComponent_id = params.componentIndex;
        }
        openLaunchUrl(click.url, fields);
    });
};

/**
 * @returns {boolean} false when the chart has to wait for its element or for ECharts
 */
const draw = (config) => {
    let chart;
    try {
        chart = JSON.parse(config.dataset.mdChart);
    } catch {
        return true;
    }
    const type = TYPES[chart.type];
    if (type === undefined) {
        return true;
    }

    const element = document.getElementById(chart.name);
    if (element === null || window.echarts === undefined) {
        return false;
    }

    dispose(chart.name);
    window.echarts.getInstanceByDom(element)?.dispose();

    const instance = window.echarts.init(element, chart.theme !== '' ? chart.theme : null);
    instance.setOption(type.option(chart));
    bindClick(instance, chart, type);

    const observer = new ResizeObserver(() => {
        if (!instance.isDisposed()) {
            instance.resize();
        }
    });
    observer.observe(element);
    drawn.set(chart.name, {instance, observer});

    return true;
};

const drawPending = () => {
    pending.forEach((config) => {
        if (!config.isConnected || draw(config)) {
            pending.delete(config);
        }
    });
};

const collect = (root) => {
    if (root.matches?.(SELECTOR)) {
        pending.add(root);
    }
    root.querySelectorAll?.(SELECTOR).forEach((config) => pending.add(config));
};

collect(document);
drawPending();

new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
            if (node.nodeType === Node.ELEMENT_NODE) {
                collect(node);
            }
        });
    });
    // Also retries the configurations rendered before their element
    if (pending.size > 0) {
        drawPending();
    }
}).observe(document.documentElement, {childList: true, subtree: true});
