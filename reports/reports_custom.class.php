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

/**
 * Class PluginMydashboardReports_Custom
 */
class PluginMydashboardReports_Custom extends CommonGLPI {

   private       $options;
   private       $pref;
   public static $reports = [];

   /**
    * PluginMydashboardReports_Custom constructor.
    *
    * @param array $_options
    */
   public function __construct($_options = []) {
      $this->options = $_options;

      $preference = new PluginMydashboardPreference();
      if (Session::getLoginUserID() !== false
          && !$preference->getFromDB(Session::getLoginUserID())) {
         $preference->initPreferences(Session::getLoginUserID());
      }
      $preference->getFromDB(Session::getLoginUserID());
      $this->preferences = $preference->fields;
   }

   /**
    * @return array
    */
   public function getWidgetsForItem() {

      //      $isDebug = $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE;
      $widgets        = [];
      $customsWidgets = PluginMydashboardCustomswidget::listCustomsWidgets();
      if (!empty($customsWidgets)) {
         foreach ($customsWidgets as $customWidget) {
            $widgets[__('Custom Widgets', 'mydashboard')][$this->getType() . "cw" . $customWidget['id']] = ["title"   => $customWidget['name'],
                                                                                                            "icon"    => "ti ti-edit",
                                                                                                            "comment" => ""];
         }
      }
      return $widgets;
   }


   /**
    * @param       $widgetId
    * @param array $opt
    *
    * @return \PluginMydashboardHtml
    */
   public function getWidgetContentForItem($widgetId, $opt = []) {

      switch ($widgetId) {

         default:
         {
            // It's a custom widget
            if (strpos($widgetId, "cw")) {

               // Last letter of widgetId is customWidget index in database
               $id = intval(substr($widgetId, -1));

               $content = PluginMydashboardCustomswidget::getCustomWidget($id);

               $widget = new PluginMydashboardHtml(true);

               $widget->setWidgetTitle($content['name']);

               $htmlContent = html_entity_decode($content['content']);

               // Edit style to avoid padding, margin, and limited width

               //               $htmlContent .= "<script>
               //                $( document ).ready(function() {
               //                    let $widgetId = document.getElementById('$widgetId');
               //                    " . $widgetId . ".children[0].style.marginTop = '-5px';
               //                    " . $widgetId . ".children[0].children[0].classList.remove('bt-col-md-11');
               //                    " . $widgetId . ".children[0].children[0].classList.add('bt-col-md-12');
               //                    " . $widgetId . ".children[0].children[0].children[0].style = 'padding-left : 0% !important; margin-right : 28px;margin-bottom: -10px;';
               //                });
               //                </script>";

               if (isset($opt["is_widget"]) && $opt["is_widget"] == false) {
                  return $htmlContent;
               }
               $widget->setWidgetHtmlContent($htmlContent);
               return $widget;
            }
         }
      }
   }
}
