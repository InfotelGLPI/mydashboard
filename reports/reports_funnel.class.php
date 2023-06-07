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
 * Class PluginMydashboardReports_Funnel
 */
class PluginMydashboardReports_Funnel extends CommonGLPI
{
    private $options;
    private $pref;
    public static $reports = [1];

    /**
     * PluginMydashboardReports_Line constructor.
     *
     * @param array $_options
     */
    public function __construct($_options = [])
    {
        $this->options = $_options;
    }


    /**
     * @return array
     */
    public function getWidgetsForItem()
    {
        $widgets = [
            PluginMydashboardMenu::$INVENTORY =>
                [
                    $this->getType() . "1"  => ["title"   => __("Age pyramid", "mydashboard"),
                                                "type"    => PluginMydashboardWidget::$OTHERS,
                                                "icon"    => "ti ti-triangle",
                                                "comment" => ""],
                ]
        ];

        return $widgets;
    }

    /**
     * @param $widgetID
     *
     * @return false|mixed
     */
    public function getTitleForWidget($widgetID)
    {
        $widgets = $this->getWidgetsForItem();
        foreach ($widgets as $type => $list) {
            foreach ($list as $name => $widget) {
                if ($widgetID == $name) {
                    return $widget['title'];
                }
            }
        }
        return false;
    }

    /**
     * @param $widgetID
     *
     * @return false|mixed
     */
    public function getCommentForWidget($widgetID)
    {
        $widgets = $this->getWidgetsForItem();
        foreach ($widgets as $type => $list) {
            foreach ($list as $name => $widget) {
                if ($widgetID == $name) {
                    return $widget['comment'];
                }
            }
        }
        return false;
    }

    /**
     * @param       $widgetId
     * @param array $opt
     *
     * @return \PluginMydashboardHtml
     * @throws \GlpitestSQLError
     */
    public function getWidgetContentForItem($widgetId, $opt = [])
    {
        global $DB;
        $isDebug = $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE;

        $preference = new PluginMydashboardPreference();
        if (Session::getLoginUserID() !== false
            && !$preference->getFromDB(Session::getLoginUserID())) {
            $preference->initPreferences(Session::getLoginUserID());
        }
        $preference->getFromDB(Session::getLoginUserID());
        $preferences = $preference->fields;

        switch ($widgetId) {
            case $this->getType() . "1":
                $name = 'AgePyramid';
                $onclick = 0;
                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() == 'central') {
                    $criterias = ['entities_id',
                                  'is_recursive',
                                  'type_computer'
                    ];
                    $onclick   = 1;
                }
                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() != 'central') {
                    $criterias = [
                        'type_computer'
                    ];
                }

                $params  = ["preferences" => $preferences,
                            "criterias"   => $criterias,
                            "opt"         => $opt];
                $options = PluginMydashboardHelper::manageCriterias($params);

                $opt  = $options['opt'];
                $crit = $options['crit'];

                $entities_criteria          = $crit['entities_id'];
                $entities_id_criteria       = $crit['entity'];
                $sons_criteria              = $crit['sons'];
                $type                       = $opt['type_computer'];
                $type_criteria              = $crit['type_computer'];


                $is_deleted = "`glpi_computers`.`is_deleted` = 0";
                $params['entities_id'] = $entities_id_criteria;
                $params['sons'] = $sons_criteria;
                $entities_criteria = PluginMydashboardHelper::getSpecificEntityRestrict("glpi_computers", $params);

                $query = "
                         SELECT 'Undefined' Age, 'other' AgeCrit, count(*) Total
                         FROM `glpi_computers`
                         LEFT JOIN `glpi_infocoms` as i ON (`glpi_computers`.`id` = i.`items_id` AND i.`itemtype` = 'Computer')
                         WHERE $is_deleted
                           AND `glpi_computers`.`is_template` = 0
                           AND i.`buy_date` IS NULL
                            $entities_criteria
                            $type_criteria
                         UNION
                         SELECT '> 6 years' Age, '6-6' AgeCrit, count(*) Total
                         FROM `glpi_computers`
                         LEFT JOIN `glpi_infocoms` as i ON (`glpi_computers`.`id` = i.`items_id` AND i.`itemtype` = 'Computer')
                         WHERE $is_deleted
                           AND `glpi_computers`.`is_template` = 0
                           AND i.`buy_date` < CURRENT_DATE - INTERVAL 6 YEAR
                           $entities_criteria
                         $type_criteria
                         UNION
                         SELECT '4-6 years' Age, '4-6' AgeCrit, count(*) Total
                         FROM `glpi_computers`
                         LEFT JOIN `glpi_infocoms` as i ON (`glpi_computers`.`id` = i.`items_id` AND i.`itemtype` = 'Computer')
                         WHERE $is_deleted
                           AND `glpi_computers`.`is_template` = 0
                           AND i.`buy_date` <= CURRENT_DATE - INTERVAL 4 YEAR
                           AND i.`buy_date` > CURRENT_DATE - INTERVAL 6 YEAR
                           $entities_criteria
                         $type_criteria
                         UNION
                         SELECT '2-4 years' Age, '2-4' AgeCrit, count(*) Total
                         FROM `glpi_computers`
                         LEFT JOIN `glpi_infocoms` as i ON (`glpi_computers`.`id` = i.`items_id` AND i.`itemtype` = 'Computer')
                         WHERE $is_deleted
                           AND `glpi_computers`.`is_template` = 0
                           AND i.`buy_date` <= CURRENT_DATE - INTERVAL 2 YEAR
                           AND i.`buy_date` > CURRENT_DATE - INTERVAL 4 YEAR
                           $entities_criteria
                         $type_criteria
                         UNION
                            SELECT '< 2 years' Age, '2-2' AgeCrit, count(*) Total
                         FROM `glpi_computers`
                         LEFT JOIN `glpi_infocoms` as i ON (`glpi_computers`.`id` = i.`items_id` AND i.`itemtype` = 'Computer')
                         WHERE $is_deleted
                           AND `glpi_computers`.`is_template` = 0
                           AND i.`buy_date` < CURRENT_DATE - INTERVAL 2 YEAR
                           $entities_criteria
                         $type_criteria";


                $results = $DB->query($query);
                $tabage   = [];
                $tabnames = [];

                $ages = [__('Without buy date', 'mydashboard'),
                         __('> 6 years', 'mydashboard'),
                         __('4-6 years', 'mydashboard'),
                         __('2-4 years', 'mydashboard'),
                         __('< 2 years', 'mydashboard'),
                         ];
                $i = 0;
                while ($data = $DB->fetchArray($results)) {
                    $tabnames[] = $ages[$i];
                    $tabdate[] = $data['AgeCrit'];

                    if ($i == 0) {
                        $tabage[] = ['value' => $data['Total'],
                                     'name' => $ages[$i],
                                     'itemStyle' => ['color' => '#CCC']];
                    } elseif ($i == 1) {
                        $tabage[] = ['value' => $data['Total'],
                                     'name' => $ages[$i],
                                     'itemStyle' => ['color' => '#E19494FF']];
                    } elseif ($i == 2) {
                        $tabage[] = ['value' => $data['Total'],
                                     'name' => $ages[$i],
                                     'itemStyle' => ['color' => '#EAAC4EFF']];
                    } elseif ($i == 3) {
                        $tabage[] = ['value' => $data['Total'],
                                     'name' => $ages[$i],
                                     'itemStyle' => ['color' => '#599CD0FF']];
                    } elseif ($i == 4) {

                        $tabage[] = ['value' => $data['Total'],
                                     'name' => $ages[$i],
                                     'itemStyle' => ['color' => '#9EB778FF']];
                    }
                    $i++;
                }

                $widget      = new PluginMydashboardHtml();
                $dataLineset = json_encode($tabage);
                $dataDateset = json_encode($tabdate);
                $labelsLine  = json_encode($tabnames);

                $title   = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetTitle((($isDebug) ? "35 " : "") . $title);
                $widget->setWidgetComment($comment);
                $widget->toggleWidgetRefresh();

                $graph_datas = ['title'   => $title,
                                'comment' => $comment,
                                'name'    => $name,
                                'ids'     => $dataDateset,
                                'data'    => $dataLineset,
                                'labels'  => $labelsLine];

                $graph_criterias = [];
                if ($onclick == 1) {
                    $graph_criterias = ['entities_id'        => $entities_id_criteria,
                                        'sons'               => $sons_criteria,
                                        'type_computer'      => $type,
                                        'widget'             => $widgetId];
                }
                $graph = PluginMydashboardFunnelChart::launchFunnelGraph($graph_datas, $graph_criterias);
                $widget->setWidgetHtmlContent($graph);

                $params = ["widgetId"  => $widgetId,
                           "name"      => $name,
                           "onsubmit"  => true,
                           "opt"       => $opt,
                           "criterias" => $criterias,
                           "export"    => true,
                           "canvas"    => true,
                           "nb"        => 1];
                $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));
                $widget->setWidgetHtmlContent(
                    $graph
                );

                return $widget;
                break;

            default:
                break;
        }
    }

    /**
     * @param $params['selected_id']
     *
     * @return string
     */
    public static function pluginMydashboardReports_Funnel1link($params)
    {
        global $CFG_GLPI;
//        Toolbox::logInfo($params);
        $options['reset'][] = 'reset';

        if (isset($params['selected_id'])) {
            if ($params['selected_id'] == "2-2") {
                $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::BUY_DATE, 'lessthan', '-2YEAR', 'AND');
            } elseif ($params['selected_id'] == "2-4") {
                $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::BUY_DATE, 'lessthan', '-2YEAR', 'AND');
                $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::BUY_DATE, 'morethan', '-4YEAR', 'AND');
            } elseif ($params['selected_id'] == "4-6") {
                $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::BUY_DATE, 'lessthan', '-4YEAR', 'AND');
                $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::BUY_DATE, 'morethan', '-6YEAR', 'AND');
            } elseif ($params['selected_id'] == "6-6") {
                $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::BUY_DATE, 'lessthan', '-6YEAR', 'AND');
            } elseif ($params['selected_id'] == "other") {
                $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::BUY_DATE, 'contains', 'NULL', 'AND');
            }
        }

        if ($params["params"]["type_computer"] > 0) {
            $options = PluginMydashboardChart::addCriteria(PluginMydashboardChart::TYPE_COMPUTER, 'equals', $params["params"]["type_computer"], 'AND');
        }

        return $CFG_GLPI["root_doc"] . '/front/computer.php?is_deleted=0&' .
                Toolbox::append_params($options, "&");
    }
}
