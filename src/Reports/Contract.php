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

namespace GlpiPlugin\Mydashboard\Reports;

use CommonGLPI;
use DbUtils;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;
use GlpiPlugin\Mydashboard\Datatable;
use GlpiPlugin\Mydashboard\Html;
use GlpiPlugin\Mydashboard\Menu;
use GlpiPlugin\Mydashboard\Widget;
use Session;
use Toolbox;

/**
 * This class extends GLPI class contract to add the functions to display a widget on Dashboard
 */
class Contract extends CommonGLPI
{
    /**
     * @param int $nb
     *
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return __('Contract');
    }

    /**
     * @return array
     */
    public function getWidgetsForItem()
    {
        $widgets = [];
        if (Session::haveRight("contract", READ)) {
            $widgets = [
                Menu::$MANAGEMENT => [
                    "contractwidget" => [
                        "title" => __('Contracts status', 'mydashboard'),
                        "type" => Widget::$TABLE,
                        "comment" => "",
                    ],
                ],
            ];
        }
        return $widgets;
    }


    /**
     * @param $widgetId
     *
     * @return Datatable|false
     */
    public function getWidgetContentForItem($widgetId)
    {
        switch ($widgetId) {
            case "contractwidget":
                return self::showCentral();
                break;
        }
    }

    /**
     * Show central contract resume
     * HTML array
     *
     * @return Html (display)
     */
    public static function showCentral()
    {
        global $DB, $CFG_GLPI;

        $dbu = new DbUtils();
        if (!Session::haveRight("contract", READ)) {
            return false;
        }

        $end_date = QueryFunction::dateAdd(
            date: 'begin_date',
            interval: new QueryExpression($DB::quoteName('duration')),
            interval_unit: 'MONTH'
        );

        $end_date_diff_now = QueryFunction::dateDiff(
            expression1: $end_date,
            expression2: QueryFunction::curDate()
        );

        // All contract counts in a single query using conditional aggregation
        $table = \Contract::getTable();

        $notice_date = QueryFunction::dateAdd(
            date: 'begin_date',
            interval: new QueryExpression($DB::quoteName('duration') . ' - ' . $DB::quoteName('notice')),
            interval_unit: 'MONTH'
        );

        $end_date_diff_notice = QueryFunction::dateDiff(
            expression1: $notice_date,
            expression2: QueryFunction::curDate()
        );

        $notice_col = $DB::quoteName('notice');
        $result = $DB->request([
            'SELECT' => [
                new QueryExpression("SUM(CASE WHEN $end_date_diff_now > -30 AND $end_date_diff_now < 0 THEN 1 ELSE 0 END) AS contract0"),
                new QueryExpression("SUM(CASE WHEN $end_date_diff_now > 0 AND $end_date_diff_now <= 7 THEN 1 ELSE 0 END) AS contract7"),
                new QueryExpression("SUM(CASE WHEN $end_date_diff_now > 7 AND $end_date_diff_now < 30 THEN 1 ELSE 0 END) AS contract30"),
                new QueryExpression("SUM(CASE WHEN $notice_col <> 0 AND $end_date_diff_notice > 0 AND $end_date_diff_notice <= 7 THEN 1 ELSE 0 END) AS contractpre7"),
                new QueryExpression("SUM(CASE WHEN $notice_col <> 0 AND $end_date_diff_notice > 7 AND $end_date_diff_notice < 30 THEN 1 ELSE 0 END) AS contractpre30"),
            ],
            'FROM'  => $table,
            'WHERE' => ['is_deleted' => 0] + getEntitiesRestrictCriteria($table),
        ])->current();

        $contract0    = (int) ($result['contract0']    ?? 0);
        $contract7    = (int) ($result['contract7']    ?? 0);
        $contract30   = (int) ($result['contract30']   ?? 0);
        $contractpre7 = (int) ($result['contractpre7'] ?? 0);
        $contractpre30 = (int) ($result['contractpre30'] ?? 0);

        $widget = new Html();
        $widget->setWidgetId("contractwidget");

        $icon = "<i class='".\Contract::getIcon()."'></i>";
        $widget->setWidgetTitle(
            $icon." <a href=\"" . $CFG_GLPI["root_doc"] . "/front/contract.php?reset=reset\">"
            .  __('Contract followup', 'mydashboard') . "</a>"
        );

        $twig_params = [
            'title'     => [
                'link'   => $CFG_GLPI["root_doc"] . "/front/contract.php?reset=reset",
                'text'   =>  \Contract::getTypeName(2),
                'icon'   => \Contract::getIcon(),
            ],
            'items'     => [],
        ];

        $options = [
            'reset' => 'reset',
            'sort'  => 20,
            'order' => 'DESC',
            'start' => 0,
            'criteria' => [
                [
                    'field'      => 20,
                    'value'      => 'NOW',
                    'searchtype' => 'lessthan',
                ],
                [
                    'field'      => 20,
                    'link'       => 'AND',
                    'searchtype' => 'morethan',
                    'value'      => '-1MONTH',
                ],
            ],
        ];

        $twig_params['items'][] = [
            'link'   => $CFG_GLPI["root_doc"] . "/front/contract.php?" . Toolbox::append_params($options),
            'text'   => __('Contracts expired in the last 30 days'),
            'count'  => $contract0,
        ];

        $options['criteria'][0]['searchtype'] = 'morethan';
        $options['criteria'][0]['value']      = 'NOW';
        $options['criteria'][1]['searchtype'] = 'lessthan';
        $options['criteria'][1]['value']      = '7DAY';

        $twig_params['items'][] = [
            'link'   => $CFG_GLPI["root_doc"] . "/front/contract.php?" . Toolbox::append_params($options),
            'text'   => __('Contracts expiring in less than 7 days'),
            'count'  => $contract7,
        ];

        $options['criteria'][0]['searchtype'] = 'morethan';
        $options['criteria'][0]['value']      = '6DAY';
        $options['criteria'][1]['searchtype'] = 'lessthan';
        $options['criteria'][1]['value']      = '1MONTH';

        $twig_params['items'][] = [
            'link'   => $CFG_GLPI["root_doc"] . "/front/contract.php?" . Toolbox::append_params($options),
            'text'   => __('Contracts expiring in less than 30 days'),
            'count'  => $contract30,
        ];

        $options['criteria'][0]['field'] = 13;
        $options['criteria'][0]['searchtype'] = 'morethan';
        $options['criteria'][0]['value'] = 'NOW';
        $options['criteria'][1]['field'] = 13;
        $options['criteria'][1]['searchtype'] = 'lessthan';
        $options['criteria'][1]['value'] = '7DAY';

        $twig_params['items'][] = [
            'link'   => $CFG_GLPI["root_doc"] . "/front/contract.php?" . Toolbox::append_params($options),
            'text'   => __('Contracts where notice begins in less than 7 days'),
            'count'  => $contractpre7,
        ];

        $options['criteria'][0]['value'] = '6DAY';
        $options['criteria'][1]['value'] = '1MONTH';


        $twig_params['items'][] = [
            'link'   => $CFG_GLPI["root_doc"] . "/front/contract.php?" . Toolbox::append_params($options),
            'text'   => __('Contracts where notice begins in less than 30 days'),
            'count'  => $contractpre30,
        ];

        $output = TemplateRenderer::getInstance()->render('@mydashboard/itemtype_count.html.twig', $twig_params);

        $widget->toggleWidgetRefresh();
        $widget->setWidgetHtmlContent($output);

        return $widget;
    }
}
