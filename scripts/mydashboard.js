/*
 -------------------------------------------------------------------------
 MyDashboard plugin for GLPI
 Copyright (C) 2006-2014 by the mydashboard Development Team.

 https://forge.indepnet.net/projects/mydashboard
 -------------------------------------------------------------------------

 LICENSE

 This file is part of mydashboard.

 MyDashboard is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 MyDashboard is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with mydashboard. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

// onMaximize = new Array();
// onMinimize = new Array();
// onInit = new Array();

//this object contains all methods to manage the dashboard
var mydashboard = {

    //Refresh all widgets that can be refreshed
    refreshAll: function () {
        // this.log(this.language.refreshAll);
        $('.refresh-icon').trigger('click');
    },
    //Launch the automatic refresh with a specified delay
    automaticRefreshAll: function (delay) {
        setInterval(function () {
            refreshAll();
        }, delay);
    },
};
