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

namespace GlpiPlugin\Mydashboard\Criterias;

use Glpi\Application\View\TemplateRenderer;
use Ajax;
use Dropdown;
use GlpiPlugin\Mydashboard\Preference;
use Session;

/**
 * Class DisplayData
 */
class DisplayData
{
    public static $criteria_name = 'display_data';

    public static function getDefaultValue()
    {

        $year = intval(date('Y', time()));

        $preference = new Preference();
        if (!$preference->getFromDB(Session::getLoginUserID())) {
            $preference->initPreferences(Session::getLoginUserID());
        }
        $preference->getFromDB(Session::getLoginUserID());
        $preferences = $preference->fields;
        if (isset($preferences['prefered_year'])) {
            if ($preferences['prefered_year'] > 0) {
                $year = intval(date('Y', time()) - 1);
            }
        }
        return $year;

    }

    public static function getDisplayValue($opt)
    {


        $form = "";
        if ($opt[self::$criteria_name] && preg_match('/^\d{4}$/', $opt[self::$criteria_name])) {
            $form .= "&nbsp;/&nbsp;" . __('Year', 'mydashboard') . "&nbsp;:&nbsp;" . $opt[self::$criteria_name];
        }
        if (isset($opt['start_year'])
            && isset($opt['start_month'])
            && isset($opt['end_year'])
                    && isset($opt['end_month'])) {
            $start_month = sprintf('%02d', $opt['start_month']);
            $end_month = sprintf('%02d', $opt['end_month']);
            $form .= "&nbsp;/&nbsp;" . __('Period', 'mydashboard')
                . "&nbsp;:&nbsp;" . $opt['start_year'] . "-" . $start_month . " / " . $opt['end_year'] . "-" . $end_month;
        }
        return $form;
    }

    /**
     * Start/end month and year fields.
     *
     * Shared with ajax/dropdownUpdateDisplaydata.php, which refreshes this very block.
     *
     * @param int  $rand
     * @param array $opt          current values
     * @param int  $first_breaks  leading <br> count of the first field (the AJAX block
     *                            starts flush, the inline criterion is spaced out)
     *
     * @return array
     */
    public static function getPeriodFields($rand, $opt = [], $first_breaks = 2)
    {
        $years = [];
        $year = date("Y") - 3;
        for ($i = 0; $i <= 3; $i++) {
            $years[$year] = $year;
            $year++;
        }

        $fields = [];
        foreach ([['start', __('Start month', 'mydashboard'), __('Start year', 'mydashboard')],
            ['end', __('End month', 'mydashboard'), __('End year', 'mydashboard')],
        ] as [$prefix, $month_label, $year_label]) {
            $fields[] = [
                'label' => $month_label,
                'breaks' => $fields === [] ? $first_breaks : 2,
                'input_html' => Dropdown::showNumber($prefix . '_month', [
                    'value' => $opt[$prefix . '_month'] ?? date('m'),
                    'rand' => $rand,
                    'min' => 1,
                    'max' => 12,
                    'display' => false,
                    'width' => '200px',
                ]),
            ];
            $fields[] = [
                'label' => $year_label,
                'breaks' => 1,
                'input_html' => Dropdown::showFromArray($prefix . '_year', $years, [
                    'value' => $opt[$prefix . '_year'] ?? date('Y'),
                    'rand' => $rand,
                    'display' => false,
                ]),
            ];
        }

        return $fields;
    }

    public static function getDisplayForm($default, $opt, $count)
    {
        global $CFG_GLPI;

        $temp = [
            "YEAR" => __("year", 'mydashboard'),
            "START_END" => __("Start end", 'mydashboard'),
        ];

        $rand = mt_rand();
        $params = [
            "name" => 'display_data',
            "display" => false,
            "multiple" => false,
            "width" => '200px',
            "rand" => $rand,
            'value' => $opt['display_data'] ?? 'YEAR',
            'display_emptychoice' => false,
        ];

        $start_end = isset($opt['display_data']) && $opt['display_data'] == 'START_END';

        $fields = [];
        $year_html = '';
        if ($start_end) {
            $fields = self::getPeriodFields($rand, $opt);
        } else {
            $annee_courante = date('Y', time());
            if (isset($opt["year"])
                && $opt["year"] > 0) {
                $annee_courante = $opt["year"];
            }
            $year_html = Year::YearDropdown($annee_courante);
        }

        $root = $CFG_GLPI['root_doc'] . '/plugins/mydashboard';

        return TemplateRenderer::getInstance()->render('@mydashboard/criteria_display_data.html.twig', [
            'rand' => $rand,
            'count' => $count,
            'start_end' => $start_end,
            'mode_html' => Dropdown::showFromArray("display_data", $temp, $params),
            'fields' => $fields,
            'year_html' => $year_html,
            'ajax_html' => Ajax::updateItemOnSelectEvent(
                'dropdown_display_data' . $rand,
                "display_data_crit$rand",
                $root . "/ajax/dropdownUpdateDisplaydata.php",
                ['value' => '__VALUE__'],
                false,
            ),
        ]);
    }

}
