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
use GlpiPlugin\Mydashboard\Preference;
use Html;
use Session;

/**
 * Class FilterDate
 */
class FilterDate
{
    public static $criteria_name = 'filter_date';

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

    /**
     * @param array $opt
     *
     * @return array<int, array{label: string, values: array<int, string>}>
     */
    public static function getDisplayValue($opt): array
    {
        $summary = [];
        if ($opt[self::$criteria_name] && preg_match('/^\d{4}$/', $opt[self::$criteria_name])) {
            $summary[] = [
                'label'  => __('Year', 'mydashboard'),
                'values' => [(string) $opt[self::$criteria_name]],
            ];
        }
        if (isset($opt['begin']) && isset($opt['end'])) {
            $summary[] = [
                'label'  => __('Period', 'mydashboard'),
                'values' => [
                    Html::convDateTime($opt['begin']) . " / " . Html::convDateTime($opt['end']),
                ],
            ];
        }

        return $summary;
    }

    public static function getDisplayForm($default, $opt, $count)
    {

        global $CFG_GLPI;

        $temp = [
            "YEAR" => __("year", 'mydashboard'),
            "BEGIN_END" => __("begin and end date", 'mydashboard'),
        ];

        $rand = mt_rand();
        $begin_end = isset($opt['filter_date']) && $opt['filter_date'] == 'BEGIN_END';

        $annee_courante = date('Y', time());
        if (isset($opt["year"])
            && $opt["year"] > 0) {
            $annee_courante = $opt["year"];
        }

        return TemplateRenderer::getInstance()->render('@mydashboard/criteria_filter_date.html.twig', [
            'rand' => $rand,
            'count' => $count,
            'begin_end' => $begin_end,
            'modes' => $temp,
            'mode' => $opt['filter_date'] ?? 'YEAR',
            'begin' => $opt['begin'] ?? null,
            'end' => $opt['end'] ?? null,
            'year_name' => Year::$criteria_name,
            'years' => Year::getYearChoices(),
            'year' => $annee_courante,
            'ajax_url' => $CFG_GLPI['root_doc'] . '/plugins/mydashboard/ajax/dropdownUpdateDisplaydata.php',
        ]);
    }

}
