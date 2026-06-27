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

namespace GlpiPlugin\Mydashboard;

/**
 *  Timer class for debug and some other cases
 */
class Timer
{
    //! Timer value
    public $timer = 0;


    /**
     * Start the Timer
     *
     * @return true
     */
    public function start()
    {
        $this->timer = microtime(true);
        return true;
    }


    /**
     * Get the current time of the timer
     *
     * @param integer $decimals Number of decimal of the result (default 3)
     * @param boolean $raw Get raw time
     *
     * @return string time past from start
     **/
    public function getTime($decimals = 3, $raw = false)
    {
        $elapsed = microtime(true) - $this->timer;
        if ($raw === true) {
            return (string)($elapsed * 1000);
        } else {
            // $decimals will set the number of decimals you want for your milliseconds.
            return number_format($elapsed, $decimals, '.', ' ');
        }
    }
}
