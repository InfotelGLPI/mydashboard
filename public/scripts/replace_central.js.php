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

header('Content-Type: text/javascript');
?>
var root_mydasboard_doc = "<?php echo PLUGIN_MYDASHBOARD_WEBDIR; ?>";
/**
 *  Replace central by dashboard
 */
(function ($) {
    $.fn.mydashboard_replace_central = function () {

        init();
        var object = this;
        var root_mydashboard_doc = root_mydasboard_doc;
        var check_path = root_mydashboard_doc.split('/');
       // console.log(check_path[2]);

        // Start the plugin
        function init() {
            $(document).ready(function () {

                var hrefs = $("a[href$='/front/central.php']");
                hrefs.each(function (href, value) {
                    $("a[href='" + value['pathname'] + "']").attr('href', root_mydasboard_doc + '/front/menu.php');
                });
            });
        }

        return this;
    }
}(jQuery));

$(document).mydashboard_replace_central();

