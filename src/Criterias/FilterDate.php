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

    public static function getDisplayValue($opt)
    {

        $form = "";
        if ($opt[self::$criteria_name] && preg_match('/^\d{4}$/', $opt[self::$criteria_name])) {
            $form .= "&nbsp;/&nbsp;" . __('Year', 'mydashboard') . "&nbsp;:&nbsp;" . $opt[self::$criteria_name];
        }
        if (isset($opt['begin']) && isset($opt['end'])) {
            $form .= "&nbsp;/&nbsp;" . __('Period', 'mydashboard') .
                "&nbsp;:&nbsp;" . Html::convDateTime($opt['begin']) . " / " . Html::convDateTime($opt['end']);
        }
        return $form;
    }

    public static function getDisplayForm($default, $opt, $count)
    {

        global $CFG_GLPI;

        $temp = [
            "YEAR" => __("year", 'mydashboard'),
            "BEGIN_END" => __("begin and end date", 'mydashboard'),
        ];

        $rand = mt_rand();
        $params = [
            "name" => 'filter_date',
            "display" => false,
            "multiple" => false,
            "width" => '200px',
            "rand" => $rand,
            'value' => $opt['filter_date'] ?? 'YEAR',
            'display_emptychoice' => false,
        ];

        $begin_end = isset($opt['filter_date']) && $opt['filter_date'] == 'BEGIN_END';

        $begin_html = '';
        $end_html = '';
        $year_html = '';
        if ($begin_end) {
            $begin_html = Html::showDateTimeField(
                "begin",
                ['value' => $opt['begin'] ?? null, 'maybeempty' => false, 'display' => false],
            );
            $end_html = Html::showDateTimeField(
                "end",
                ['value' => $opt['end'] ?? null, 'maybeempty' => false, 'display' => false],
            );
        } else {
            $annee_courante = date('Y', time());
            if (isset($opt["year"])
                && $opt["year"] > 0) {
                $annee_courante = $opt["year"];
            }
            $year_html = Year::YearDropdown($annee_courante);
        }

        $root = $CFG_GLPI['root_doc'] . '/plugins/mydashboard';

        return TemplateRenderer::getInstance()->render('@mydashboard/criteria_filter_date.html.twig', [
            'rand' => $rand,
            'count' => $count,
            'begin_end' => $begin_end,
            'mode_html' => Dropdown::showFromArray("filter_date", $temp, $params),
            'begin_html' => $begin_html,
            'end_html' => $end_html,
            'year_html' => $year_html,
            'ajax_html' => Ajax::updateItemOnSelectEvent(
                'dropdown_filter_date' . $rand,
                "filter_date_crit$rand",
                $root . "/ajax/dropdownUpdateDisplaydata.php",
                ['value' => '__VALUE__'],
                false,
            ),
        ]);
    }

}
