<?php
/*
 -------------------------------------------------------------------------
 MyDashboard plugin for GLPI
 Copyright (C) 2015-2022 by the MyDashboard Development Team.
 -------------------------------------------------------------------------

 LICENSE

 This file is part of MyDashboard.

 MyDashboard is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 MyDashboard is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with MyDashboard. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/**
 * Every chart classes of the mydashboard plugin inherit from this class
 * It sets basical parameters to display a chart with Flotr2
 */
abstract class PluginMydashboardChart extends PluginMydashboardModule
{
    protected $tabDatas;
    private $tabDatasSet;
    private $options = [];

    const PRIORITY           = 3;
    const TYPE               = 14;
    const ENTITIES_ID        = 80;
    const STATUS             = 12;
    const CATEGORY           = 7;
    const OPEN_DATE          = 15;
    const TECHNICIAN         = 5;
    const REQUESTER_GROUP    = 71;
    const TECHNICIAN_GROUP   = 8;
    const LOCATIONS_ID       = 83;
    const CLOSE_DATE         = 16;
    const SOLVE_DATE         = 17;
    const TASK_ACTIONTIME    = 96;
    const VALIDATION_STATS   = 55;
    const VALIDATION_REFUSED = 4;
    const NUMBER_OF_PROBLEMS = 200;
    const SATISFACTION_DATE  = 61;
    const SATISFACTION_VALUE = 62;
    const BUY_DATE           = 37;
    const TYPE_COMPUTER      = 4;

    /**
     * PluginMydashboardChart constructor.
     */
    public function __construct()
    {
        $this->initOptions();
        $this->setWidgetType("chart");
        $this->tabDatas = [];
        $this->tabDatasSet = false;
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
     * @return an array representing the horizontal bar chart
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


     /**
      * @param $field
      * @param $searchType
      * @param $value
      * @param $link
      */
    public static function addCriteria($field, $searchType, $value, $link)
    {
        global $options;

        $options['criteria'][] = [
            'field'      => $field,
            'searchtype' => $searchType,
            'value'      => $value,
            'link'       => $link
        ];
        return $options;
    }

     /**
      * @param $field
      * @param $searchType
      * @param $value
      */
    public static function groupCriteria($field, $searchType, $value)
    {
        global $options;

        if (isset($value)
            && count($value) > 0) {
            $groups = $value;
            $nb     = 0;
            foreach ($groups as $group) {
                $criterias['criteria'][$nb] = [
                    'field'      => $field,
                    'searchtype' => $searchType,
                    'value'      => $group,
                    'link'       => (($nb == 0) ? 'AND' : 'OR'),
                ];
                $nb++;
            }
            $options['criteria'][] = $criterias;
        }

        return $options;
    }
}
