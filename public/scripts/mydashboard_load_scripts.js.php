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
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 mydashboard is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with mydashboard. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

//use Glpi\Event;
//include('../../../../inc/includes.php');
//header('Content-Type: text/javascript');
//
//?>
//
//var root_mydashboard_doc = "<?php //echo PLUGIN_MYDASHBOARD_WEBDIR; ?>//";
//
//(function ($) {
//   $.fn.mydashboard_load_scripts = function () {
//
//      // Start the plugin
//      function init() {
//         //            $(document).ready(function () {
//         // Send data
//         $.ajax({
//            url: root_mydashboard_doc + '/ajax/loadscripts.php',
//            type: 'POST',
//            dataType: 'html',
//            data: 'action=load',
//            success: function (response, opts) {
//               var scripts, scriptsFinder = /<script[^>]*>([\s\S]+?)<\/script>/gi;
//               while (scripts = scriptsFinder.exec(response)) {
//                  eval(scripts[1]);
//               }
//            }
//         });
//      }
//
//      init();
//
//      return this;
//   };
//}(jQuery));
//
//$(document).mydashboard_load_scripts();
