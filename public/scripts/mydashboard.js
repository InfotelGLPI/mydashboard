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

// onMaximize = new Array();
// onMinimize = new Array();
// onInit = new Array();

//this object contains all methods to manage the dashboard
var mydashboard = {

    //Refresh all widgets that can be refreshed
    refreshAll: function () {
        // this.log(this.language.refreshAll);
        $('.refresh-icon').trigger('click');
    },
    //Launch the automatic refresh with a specified delay
    automaticRefreshAll: function (delay) {
        setInterval(function () {
            refreshAll();
        }, delay);
    },
};

const observer = new MutationObserver(() => {

    const charts = [];

    document.querySelectorAll('*').forEach(el => {
        const inst = echarts.getInstanceByDom(el);
        if (inst) charts.push(inst);
    });

    charts.forEach(chart => {
        const opt = chart.getOption();

        const dataView = opt.toolbox?.[0]?.feature?.dataView;

        // Déjà traité lors d'une précédente mutation : inutile de refaire un setOption
        if (!dataView || typeof dataView.optionToContent === 'function') {
            return;
        }

        dataView.optionToContent = function (opt) {

            const escapeHtml = function (value) {
                return String(value === null || value === undefined ? '' : value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            };

            const toArray = function (value) {
                if (Array.isArray(value)) {
                    return value;
                }
                return (value === null || value === undefined) ? [] : [value];
            };

            // Un point peut valoir 12, {value: 12, name: 'Libellé'} ou ['Libellé', 12]
            const itemValue = function (item) {
                if (item === null || item === undefined) {
                    return '';
                }
                if (Array.isArray(item)) {
                    return item.length ? item[item.length - 1] : '';
                }
                if (typeof item === 'object') {
                    return item.value === undefined ? '' : item.value;
                }
                return item;
            };

            const itemName = function (item) {
                if (item && typeof item === 'object' && !Array.isArray(item) && item.name !== undefined) {
                    return item.name;
                }
                return null;
            };

            // Une catégorie d'axe peut être 'Libellé' ou {value: 'Libellé'}
            const categoryLabel = function (category) {
                if (category && typeof category === 'object' && !Array.isArray(category)) {
                    return category.value === undefined ? '' : category.value;
                }
                return category;
            };

            const series = toArray(opt.series);

            // Axe des catégories : xAxis (barres/courbes verticales) ou yAxis (barres horizontales)
            let axisData = null;
            toArray(opt.xAxis).concat(toArray(opt.yAxis)).some(function (axis) {
                if (axis && Array.isArray(axis.data) && axis.data.length) {
                    axisData = axis.data;
                    return true;
                }
                return false;
            });

            // Sans axe de catégories (camembert, entonnoir, jauge...), les libellés
            // sont portés par les points de données eux-mêmes.
            if (axisData === null) {
                axisData = [];
                series.forEach(function (serie) {
                    toArray(serie.data).forEach(function (item, index) {
                        const name = itemName(item);
                        const label = (name === null) ? index : name;
                        if (axisData.indexOf(label) === -1) {
                            axisData.push(label);
                        }
                    });
                });
            }

            // Le point correspondant à une ligne : par position, sinon par libellé
            const findItem = function (serie, rowIndex, label) {
                const data = toArray(serie.data);
                const direct = data[rowIndex];
                const directName = itemName(direct);
                if (directName === null || String(directName) === String(label)) {
                    return direct;
                }
                return data.find(function (item) {
                    return String(itemName(item)) === String(label);
                });
            };

            let table = '<table class="table table-hover" style="width:100%;text-align:center"><tbody><tr>'
                + '<td>  </td>';
            series.forEach(function (serie) {
                table += '<td>' + escapeHtml(serie.name) + '</td>';
            });
            table += '</tr>';

            axisData.forEach(function (category, rowIndex) {
                const label = categoryLabel(category);
                table += '<tr>' + '<td>' + escapeHtml(label) + '</td>';
                series.forEach(function (serie) {
                    table += '<td>' + escapeHtml(itemValue(findItem(serie, rowIndex, label))) + '</td>';
                });
                table += '</tr>';
            });

            table += '</tbody></table>';
            return table;
        };

        chart.setOption(opt);
    });
});

observer.observe(document.body, { childList: true, subtree: true });
