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

/**
 * Every chart classes of the mydashboard plugin inherit from this class
 * It sets basical parameters to display a chart with Flotr2
 */
class Chart extends Module
{
    protected $tabDatas;
    private $tabDatasSet;
    private $options = [];

    public static $rightname = "plugin_mydashboard";
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
     * JSON encoding flags that make a value safe to inline inside a <script>
     * block: they neutralise the sequences used to break out of the script
     * context (</script>, quotes and ampersands) by emitting \u escapes.
     */
    protected const SCRIPT_JSON_FLAGS = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;

    /**
     * Encode a raw PHP value for safe interpolation inside a <script> block.
     *
     * @param mixed $value
     * @return string
     */
    protected static function encodeForScript($value): string
    {
        return json_encode($value, self::SCRIPT_JSON_FLAGS);
    }

    /**
     * Re-encode chart data that may already be a JSON string so that it is safe
     * to inline inside a <script> block. Falls back to an empty JSON array when
     * the input is not decodable, so raw text is never emitted into the script.
     *
     * @param mixed $value already-encoded JSON string or raw PHP value
     * @return string
     */
    protected static function hardenJson($value): string
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return '[]';
            }
        } else {
            $decoded = $value;
        }

        return json_encode($decoded, self::SCRIPT_JSON_FLAGS);
    }

    /**
     * Validate a chart canvas identifier before it is interpolated into JS.
     * Only word characters are allowed so untrusted input can never be
     * reintroduced as a raw JS identifier.
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
