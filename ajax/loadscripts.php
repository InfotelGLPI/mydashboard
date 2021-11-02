<?php
/*
 -------------------------------------------------------------------------
 MyDashboard plugin for GLPI
 Copyright (C) 2015 by the MyDashboard Development Team.
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

include('../../../inc/includes.php');

Html::header_nocache();
Session::checkLoginUser();
header("Content-Type: text/html; charset=UTF-8");

if (isset($_POST['action'])) {
   switch ($_POST['action']) {
      case "load" :

         $name              = "showMyDashboardLateralMenu";
         $param['title']    = __('My Dashboard', 'mydashboard');
         $param['position'] = 'right';
         $param['url']      = $CFG_GLPI["root_doc"] . '/plugins/mydashboard/ajax/lateralmenu.php';

         $style = "";
         $title = __("Go to mydashboard actions", "mydashboard");

         $edit = PluginMydashboardPreference::checkEditMode(Session::getLoginUserID());
         if ($edit > 0) {
            $style = "red";
            $title = __("Edit mode is enabled, please close it for better performance", "mydashboard");
         }
         $out = "<script type='text/javascript'>\n";
//         $out .= "$(function() {";
//         $out .= "$( '.mygrid' ).append('<div id=\'$name\' class=\'slidepanel on{$param['position']}\'><div class=\"header\">" .
//                 "<button type=\'button\' class=\'close ui-button ui-widget ui-state-default ui-corner-all ui-button-icon-only ui-dialog-titlebar-close\'  title=\'" . __s('Close') . "\'><span class=\'ui-button-icon-primary ui-icon ui-icon-closethick\'></span><span class=\'ui-button-text\'>" . __('Close') . "</span></button>";
//
//         if ($param['title'] != '') {
//            $out .= "<h3>{$param['title']}</h3>";
//         }
//
//         $out .= "</div><div class=\'contents\'></div></div>');
//          $('#$name').hide();";
//
//         $out .= "$('#{$name} .close').on('click', function() {
//         $('#$name').hide(
//            'slow',
//            function () {
//               $(this).find('.contents').empty();
//            }
//         );
//       });\n";
//         $out .= "$('#{$name}Link').on('click', function() {
//         $('#$name').show(
//            'slow',
//            function() {
//               _load$name();
//            }
//         );
//      });\n";
//         $out .= "});";
//         if ($param['url'] != null) {
//            $out .= "var _load$name = function() {
//            $.ajax({
//               url: '{$param['url']}',
//               beforeSend: function() {
//                  var _loader = $('<div id=\'loadingslide\'><div class=\'loadingindicator\'>" . __s('Loading...') . "</div></div>');
//                  $('#$name .contents').html(_loader);
//               }
//            })
//            .always( function() {
//               $('#loadingslide').remove();
//            })
//            .done(function(res) {
//               $('#$name .contents').html(res);
//            });
//         };\n";
//         }

         $out .= "if ($('#showMyDashboardLateralMenu').length === 0) {
            $('.ms-md-4').before(\"<a class='fas fa-tachometer-alt fa-1x red' title='$title' href='#' style='color:$style;' id='showMyDashboardLateralMenuLink'></a>\");
         };";
         
         $out .= "</script>";
         echo $out;

         PluginMydashboardMenu::createSlidePanel(
            'showMyDashboardLateralMenu',
            [
               'title'     => __('My Dashboard', 'mydashboard'),
               'url'       => $CFG_GLPI["root_doc"] . '/plugins/mydashboard/ajax/lateralmenu.php'
            ]
         );
         
         break;
   }
} else {
   exit;
}
