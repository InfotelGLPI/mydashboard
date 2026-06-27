<?php

/*
 -------------------------------------------------------------------------
 mydashboard plugin for GLPI
 Copyright (C) 2016-2026 by the mydashboard Development Team.

 https://github.com/InfotelGLPI/mydashboard
 -------------------------------------------------------------------------

 LICENSE

 This file is part of mydashboard.

 mydashboard is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 3 of the License, or
 (at your option) any later version.

 mydashboard is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with mydashboard. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Mydashboard\Criterias;

use Dropdown;

/**
 * Class Week
 */
class Week
{
    public static $criteria_name = 'week';

    public static function getDefaultValue()
    {
        return intval(date('W', time())) - 1;
    }

    public static function getDisplayValue($opt)
    {
        $form = "";
        if (isset($opt[self::$criteria_name]) && $opt[self::$criteria_name] > 0) {
            $form .= "&nbsp;/&nbsp;" . __('Week', 'mydashboard') . "&nbsp;:&nbsp;" . $opt[self::$criteria_name];
        }
        return $form;
    }

    public static function getDisplayForm($default, $opt, $count)
    {
        $current_week = $default[self::$criteria_name];
        if (isset($opt[self::$criteria_name]) && $opt[self::$criteria_name] > 0) {
            $current_week = $opt[self::$criteria_name];
        }

        $form = "<span class='md-widgetcrit'>";
        $form .= __('Week', 'mydashboard');
        $form .= "&nbsp;";
        $form .= self::weekDropdown(self::$criteria_name, $current_week);
        $form .= "</span>";
        if ($count > 1) {
            $form .= "</br></br>";
        }

        return $form;
    }

    /**
     * @param string $name
     * @param int|null $selected
     *
     * @return int|string
     */
    public static function weekDropdown($name = 'week', $selected = null)
    {
        $opt = [
            'value'   => $selected,
            'min'     => 1,
            'max'     => 53,
            'display' => false,
        ];

        return Dropdown::showNumber($name, $opt);
    }
}
