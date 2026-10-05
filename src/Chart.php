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

namespace GlpiPlugin\Mydashboard;

use Glpi\Application\View\TemplateRenderer;
use Session;

/**
 * Every chart classes of the mydashboard plugin inherit from this class
 * It sets basical parameters to display a chart with ECharts
 */
class Chart extends Module
{
    protected $tabDatas;
    private $tabDatasSet;
    private $options = [];

    public static string $rightname = "plugin_mydashboard";
    /**
     * Chart constructor.
     */
    public function __construct()
    {
        $this->initOptions();
        $this->setWidgetType("chart");
        $this->tabDatas = [];
        $this->tabDatasSet = false;
    }

    /**
     * @param int $nb
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return __('Dashboard', 'mydashboard');
    }

    /**
     * This method is here to init options of every chart (pie, bar ...)
     */
    public function initOptions()
    {
        $this->options['HtmlText'] = false;
    }

    /**
     *
     * @return array array of all options
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param $optionName
     * @return mixed|string
     */
    public function getOption($optionName)
    {
        return (isset($this->options[$optionName])) ? $this->options[$optionName] : '';
    }

    /**
     * Render the configuration of an ECharts chart, drawn by public/scripts/charts.js in
     * the element whose id is the chart name (rendered by graph_header.html.twig).
     *
     * The markup carries no script: it can be concatenated to any widget content, as the
     * reports and the other plugins do with the value returned by the launch*Graph()
     * methods.
     *
     * @param string $type            chart type, one of the types handled by charts.js
     * @param array  $graph_datas     name, data, ids, labels, label, title, legends, yaxis
     * @param array  $graph_criterias parameters posted back on click (url of the endpoint
     *                                included), no click when empty
     *
     * @return string
     */
    protected static function renderChart(string $type, array $graph_datas, array $graph_criterias): string
    {
        $click = null;
        if (count($graph_criterias) > 0) {
            $click = [
                'url'    => $graph_criterias['url'] ?? PLUGIN_MYDASHBOARD_WEBDIR . "/ajax/launchURL.php",
                'params' => $graph_criterias,
            ];
        }

        $chart = [
            'type'    => $type,
            'name'    => self::sanitizeCanvasName($graph_datas['name'] ?? ''),
            'data'    => self::decodeChartJson($graph_datas['data'] ?? []),
            'ids'     => self::decodeChartJson($graph_datas['ids'] ?? []),
            'labels'  => self::decodeChartJson($graph_datas['labels'] ?? []),
            'legends' => self::decodeChartJson($graph_datas['legends'] ?? []),
            'yaxis'   => self::decodeChartJson($graph_datas['yaxis'] ?? []),
            'label'   => (string) ($graph_datas['label'] ?? ''),
            'title'   => (string) ($graph_datas['title'] ?? ''),
            'theme'   => Preference::getPalette(Session::getLoginUserID()),
            'click'   => $click,
        ];

        return TemplateRenderer::getInstance()->render('@mydashboard/chart_config.html.twig', [
            'chart' => $chart,
        ]);
    }

    /**
     * Chart data are given either as arrays or as JSON strings: anything else, or a string
     * that does not decode, gives an empty list.
     *
     * @param mixed $value
     *
     * @return array
     */
    private static function decodeChartJson($value): array
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        return is_array($value) ? $value : [];
    }

    /**
     * Validate a chart canvas identifier, looked up by id in charts.js.
     * Only word characters are allowed.
     *
     * @param mixed $name
     * @return string
     */
    protected static function sanitizeCanvasName($name): string
    {
        $name = preg_replace('/[^A-Za-z0-9_]/', '', (string) $name);

        return $name !== '' ? $name : 'mydashboard_chart';
    }

    /**
     * @param $optionName
     * @param $optionValue
     * @param bool $force
     * @return bool
     */
    public function setOption($optionName, $optionValue, $force = false)
    {
        if (isset($this->options[$optionName]) && !$force) {
            if (is_array($optionValue)) {
                $this->options[$optionName] = array_merge($this->options[$optionName], $optionValue);
                return true;
            }
        }
        $this->options[$optionName] = $optionValue;
        return true;
    }

    /**
     * @return array array representing the horizontal bar chart
     */
    public function getTabDatas()
    {
        if (empty($this->tabDatas) && !$this->tabDatasSet) {
            $this->debugWarning(__("No data is given to the widget", 'mydashboard'));
        }
        return $this->tabDatas;
    }

    /**
     * This method is used to set an array of value representing the horizontal bar chart
     * @param array $_tabDatas
     * $_tabDatas must be formatted as :
     *  Array(
     *      label1 => value1,
     *      label2 => value2
     *  )
     * Example : array("2012" => 10, "2013" => 14,"2014" => 25)
     */
    public function setTabDatas($_tabDatas)
    {
        if (empty($_tabDatas)) {
            $this->debugNotice(__("No data available", 'mydashboard'));
        }
        $this->tabDatasSet = true;
        if (is_array($_tabDatas)) {
            $this->tabDatas = $_tabDatas;
        } else {
            $this->debugError(__("Not an array", 'mydashboard'));
        }
    }
}
