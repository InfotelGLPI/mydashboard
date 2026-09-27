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

use GlpiPlugin\Mydashboard\Criteria;
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

    /**
     * @param array $opt
     *
     * @return array<int, array{label: string, values: array<int, string>}>
     */
    public static function getDisplayValue($opt): array
    {
        if (isset($opt[self::$criteria_name]) && $opt[self::$criteria_name] > 0) {
            return [[
                'label'  => __('Week', 'mydashboard'),
                'values' => [(string) $opt[self::$criteria_name]],
            ]];
        }

        return [];
    }

    public static function getDisplayForm($default, $opt, $count)
    {
        $current_week = $default[self::$criteria_name];
        if (isset($opt[self::$criteria_name]) && $opt[self::$criteria_name] > 0) {
            $current_week = $opt[self::$criteria_name];
        }

        return Criteria::getFieldHtml(
            __('Week', 'mydashboard'),
            $count,
            [Dropdown::class, 'showNumber'],
            [self::$criteria_name, ['value' => $current_week, 'min' => 1, 'max' => 53]],
        );
    }
}
