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

namespace GlpiPlugin\Mydashboard\Criterias;

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

    public static function getDefaultValue() {

        $year = intval(date('Y', time()));

        $preference = new Preference();
        if (!$preference->getFromDB(Session::getLoginUserID())) {
            $preference->initPreferences(Session::getLoginUserID());
        }
        $preference->getFromDB(Session::getLoginUserID());
        $preferences = $preference->fields;
        if (isset($preferences['prefered_year'])) {
            if ($preferences['prefered_year'] > 0) {
                $year = intval(date('Y', time()) -1);
            }
        }
        return $year;
    }

    public static function getDisplayValue($opt) {

        $form = "";
        if ($opt[self::$criteria_name] && preg_match('/^\d{4}$/', $opt[self::$criteria_name])) {
            $form .= "&nbsp;/&nbsp;" . __('Year', 'mydashboard') . "&nbsp;:&nbsp;" . $opt[self::$criteria_name];
        }
        if (isset($opt['begin']) && isset($opt['end'])) {
            $form .= "&nbsp;/&nbsp;" . __('Period', 'mydashboard') .
                "&nbsp;:&nbsp;" .Html::convDateTime($opt['begin'])." / ".Html::convDateTime($opt['end']);
        }
        return $form;
    }

    public static function getDisplayForm($default, $opt, $count) {

        global $CFG_GLPI;

        $form = "<span class='md-widgetcrit'>";

        $temp = [];
        $temp["YEAR"] = __("year", 'mydashboard');
        $temp["BEGIN_END"] = __("begin and end date", 'mydashboard');


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

        $form .= __('Filter date', 'mydashboard');
        $form .= "&nbsp;";

        $dropdown = Dropdown::showFromArray("filter_date", $temp, $params);

        $form .= $dropdown;


        $form .= "</span>";
        if (isset($opt['filter_date']) && $opt['filter_date'] == 'BEGIN_END') {
            $form .= "<span id='filter_date_crit$rand' name= 'filter_date_crit$rand' class='md-widgetcrit'>";
            $form .= "<span class='md-widgetcrit'>";

            $form .= __('Start');
            $form .= "&nbsp;";
            $form .= Html::showDateTimeField(
                "begin",
                ['value' => $opt['begin'] ?? null, 'maybeempty' => false, 'display' => false]
            );
            $form .= "</span>";
            $form .= "</br>";
            $form .= "<span class='md-widgetcrit'>";
            $form .= __('End');
            $form .= "&nbsp;";
            $form .= Html::showDateTimeField(
                "end",
                ['value' => $opt['end'] ?? null, 'maybeempty' => false, 'display' => false]
            );
            $form .= "</span>";
            $form .= "</span>";
        } else {
            $form .= "</br></br>";
            $form .= "<span id='filter_date_crit$rand' name= 'filter_date_crit$rand' class='md-widgetcrit'>";
            $annee_courante = date('Y', time());
            if (isset($opt["year"])
                && $opt["year"] > 0) {
                $annee_courante = $opt["year"];
            }
            $form .= __('Year', 'mydashboard');
            $form .= "&nbsp;";
            $form .= Year::YearDropdown($annee_courante);
            $form .= "</span>";
        }

        $params2 = [
            'value' => '__VALUE__',

        ];
        $root = $CFG_GLPI['root_doc'] . '/plugins/mydashboard';
        $form .= Ajax::updateItemOnSelectEvent(
            'dropdown_filter_date' . $rand,
            "filter_date_crit$rand",
            $root . "/ajax/dropdownUpdateDisplaydata.php",
            $params2,
            false
        );

        if ($count > 1) {
            $form .= "</br></br>";
        }

        return $form;
    }

}
