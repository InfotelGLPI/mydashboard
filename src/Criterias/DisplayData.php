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

/**
 * Class DisplayData
 */
class DisplayData
{
    public static $criteria_name = 'display_data';

    public static function getDefaultValue() {

        return intval(date('Y', time()));

    }

    public static function getDisplayValue($display_data) {

        $form = "";
//        if (isset($display_data) && $opt['display_data'] == "SLIDING") {
//            $form .= "&nbsp;/&nbsp;" . sprintf(__('sliding %s-month period', 'mydashboard'), $opt['period_time']);
//        }
        return $form;
    }

    public static function getDisplayForm($default, $opt, $count) {
        global $CFG_GLPI;

        $form = "<span class='md-widgetcrit'>";

        $temp = [];
        $temp["YEAR"] = __("year", 'mydashboard');
        $temp["START_END"] = __("Start end", 'mydashboard');


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

        $form .= __('Display', 'mydashboard');
        $form .= "&nbsp;";

        $dropdown = Dropdown::showFromArray("display_data", $temp, $params);

        $form .= $dropdown;

        $form .= "</span>";
        if (isset($opt['display_data']) && $opt['display_data'] == 'START_END') {
            $form .= "<span id='display_data_crit$rand' name= 'display_data_crit$rand' class='md-widgetcrit'>";
            $form .= "<span class='md-widgetcrit'>";
            $form .= "</br></br>";
            $form .= __('Start month', 'mydashboard');
            $form .= "&nbsp;";
            $options = [];
            $options['value'] = $opt['start_month'] ?? date('m');
            $options['rand'] = $rand;
            $options['min'] = 1;
            $options['max'] = 12;
            $options['display'] = false;
            $options['width'] = '200px';
            $form .= Dropdown::showNumber('start_month', $options);
            $form .= "</span>";

            $form .= "<span class='md-widgetcrit'>";
            $form .= "</br>";
            $form .= __('Start year', 'mydashboard');
            $form .= "&nbsp;";
            $options = [];
            $options['value'] = $opt['start_year'] ?? date('Y');
            $options['rand'] = $rand;
            $options['display'] = false;
            $year = date("Y") - 3;
            for ($i = 0; $i <= 3; $i++) {
                $elements[$year] = $year;

                $year++;
            }

            $form .= Dropdown::showFromArray("start_year", $elements, $options);
            $form .= "</span>";

            $form .= "<span class='md-widgetcrit'>";
            $form .= "</br></br>";
            $form .= __('End month', 'mydashboard');
            $form .= "&nbsp;";
            $options = [];
            $options['value'] = $opt['end_month'] ?? date('m');
            $options['rand'] = $rand;
            $options['min'] = 1;
            $options['max'] = 12;
            $options['display'] = false;
            $options['width'] = '200px';
            $form .= Dropdown::showNumber('end_month', $options);
            $form .= "</span>";

            $form .= "<span class='md-widgetcrit'>";
            $form .= "</br>";
            $form .= __('End year', 'mydashboard');
            $form .= "&nbsp;";
            $options = [];
            $options['value'] = $opt['end_year'] ?? date('Y');
            $options['rand'] = $rand;
            $options['display'] = false;
            $year = date("Y") - 3;
            for ($i = 0; $i <= 3; $i++) {
                $elements[$year] = $year;

                $year++;
            }

            $form .= Dropdown::showFromArray("end_year", $elements, $options);
            //            $form .= Dropdown::showNumber('end_year',$options);
            $form .= "</span>";
            $form .= "</span>";
        } else {
            $form .= "</br></br>";
            $form .= "<span id='display_data_crit$rand' name= 'display_data_crit$rand' class='md-widgetcrit'>";
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
            'dropdown_display_data' . $rand,
            "display_data_crit$rand",
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
