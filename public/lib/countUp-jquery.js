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

(function($) {
 
    $.fn.countup = function(params) {
        // make sure dependency is present
        if (typeof CountUp !== 'function') {
        console.error('countUp.js is a required dependency of countUp-jquery.js.');
        return;
        }

        var defaults = {
         startVal: 0,
         decimals: 0,
         duration: 2,
        };

        if (typeof params === 'number') {
         defaults.endVal = params;
        }
        else if (typeof params === 'object') {
         $.extend(defaults, params);
        }
        else {
         console.error('countUp-jquery requires its argument to be either an object or number');
         return;
        } 

        this.each(function(i, elem) {
         var countUp = new CountUp(elem, defaults.startVal, defaults.endVal, defaults.decimals, defaults.duration, defaults.options);

         countUp.start();
        });



        return this;
 
    };
 
}(jQuery));