<?php

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

namespace GlpiPlugin\Mydashboard\Charts;

use GlpiPlugin\Mydashboard\Chart;

/**
 * Class BarChart
 *
 * The charts are drawn client-side by public/scripts/charts.js.
 */
abstract class BarChart extends Chart
{
    /**
     * Vertical bars, ids indexed by bar.
     *
     * @param array $graph_datas
     * @param array $graph_criterias
     *
     * @return string
     */
    public static function launchGraph($graph_datas = [], $graph_criterias = [])
    {
        return self::renderChart('bar', $graph_datas, $graph_criterias);
    }

    /**
     * Vertical bars with a Y axis per series, ids indexed by series then bar.
     *
     * @param array $graph_datas
     * @param array $graph_criterias
     *
     * @return string
     */
    public static function launchMultipleYaxisGraph($graph_datas = [], $graph_criterias = [])
    {
        return self::renderChart('bar_multiple_yaxis', $graph_datas, $graph_criterias);
    }

    /**
     * Vertical bars, ids indexed by series then bar.
     *
     * @param array $graph_datas
     * @param array $graph_criterias
     *
     * @return string
     */
    public static function launchMultipleGraph($graph_datas = [], $graph_criterias = [])
    {
        return self::renderChart('bar_multiple', $graph_datas, $graph_criterias);
    }

    /**
     * Horizontal bars, ids indexed by bar.
     *
     * @param array $graph_datas
     * @param array $graph_criterias
     *
     * @return string
     */
    public static function launchHorizontalGraph($graph_datas = [], $graph_criterias = [])
    {
        return self::renderChart('bar_horizontal', $graph_datas, $graph_criterias);
    }
}
