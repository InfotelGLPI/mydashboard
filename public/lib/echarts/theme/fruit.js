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
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 */

(function(root, factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['exports', 'echarts'], factory);
    } else if (
        typeof exports === 'object' &&
        typeof exports.nodeName !== 'string'
    ) {
        // CommonJS
        factory(exports, require('echarts/lib/echarts'));
    } else {
        // Browser globals
        factory({}, root.echarts);
    }
})(this, function(exports, echarts) {
    var log = function(msg) {
        if (typeof console !== 'undefined') {
            console && console.error && console.error(msg);
        }
    };
    if (!echarts) {
        log('ECharts is not Loaded');
        return;
    }

    var colorPalette = [
        '#ffcb6a',
        '#ffa850',
        '#ffe2c4',
        '#e5834e',
        '#ffb081',
        '#f7826e',
        '#faac9e',
        '#fcd5cf'
    ];

    var theme = {
        color: colorPalette,

        title: {
            textStyle: {
                fontWeight: 'normal',
                color: '#ffcb6a'
            }
        },

        visualMap: {
            color: ['#ffcb6a', '#ffa850']
        },

        toolbox: {
            color: ['#ffcb6a', '#ffcb6a', '#ffcb6a', '#ffcb6a']
        },

        tooltip: {
            backgroundColor: 'rgba(0,0,0,0.5)',
            axisPointer: {
                // Axis indicator, coordinate trigger effective
                type: 'line', // The default is a straight line： 'line' | 'shadow'
                lineStyle: {
                    // Straight line indicator style settings
                    color: '#ffcb6a',
                    type: 'dashed'
                },
                crossStyle: {
                    color: '#ffcb6a'
                },
                shadowStyle: {
                    // Shadow indicator style settings
                    color: 'rgba(200,200,200,0.3)'
                }
            }
        },

        // Area scaling controller
        dataZoom: {
            dataBackgroundColor: '#eee', // Data background color
            fillerColor: 'rgba(200,200,200,0.2)', // Fill the color
            handleColor: '#ffcb6a' // Handle color
        },

        timeline: {
            lineStyle: {
                color: '#ffcb6a'
            },
            controlStyle: {
                color: '#ffcb6a',
                borderColor: '#ffcb6a'
            }
        },

        candlestick: {
            itemStyle: {
                color: '#ffa850',
                color0: '#ffe2c4'
            },
            lineStyle: {
                width: 1,
                color: '#ffb081',
                color0: '#e5834e'
            },
            areaStyle: {
                color: '#e5834e',
                color0: '#fcd5cf'
            }
        },

        chord: {
            padding: 4,
            itemStyle: {
                color: '#fcd5cf',
                borderWidth: 1,
                borderColor: 'rgba(128, 128, 128, 0.5)'
            },
            lineStyle: {
                color: 'rgba(128, 128, 128, 0.5)'
            },
            areaStyle: {
                color: '#e5834e'
            }
        },

        map: {
            itemStyle: {
                color: '#ffe2c4'
            },
            areaStyle: {
                color: '#ddd'
            },
            label: {
                color: '#c12e34'
            }
        },

        graph: {
            itemStyle: {
                color: '#f2385a'
            },
            linkStyle: {
                color: '#ffcb6a'
            }
        },

        gauge: {
            axisLine: {
                lineStyle: {
                    color: [
                        [0.2, '#ffa850'],
                        [0.8, '#ffcb6a'],
                        [1, '#ffb081']
                    ],
                    width: 8
                }
            }
        }
    };

    echarts.registerTheme('fruit', theme);
});
