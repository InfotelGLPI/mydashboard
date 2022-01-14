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
 * This class extends GLPI class rssfeed to add the functions to display a widget on Dashboard
 */
class PluginMydashboardRSSFeed extends CommonGLPI {

   /**
    * @param int $nb
    *
    * @return string|\translated
    */
   static function getTypeName($nb = 0) {
      return __('RSS');
   }
   /**
    * @return array
    */
   function getWidgetsForItem() {

      $widgets = [];
      if (Session::getCurrentInterface() != 'helpdesk') {
         $widgets[PluginMydashboardMenu::$RSS_VIEW]["rssfeedpersonalwidget"] = ["title"   =>  _n('Personal RSS feed', 'Personal RSS feeds', 2),
                                                                                "icon"    => "ti ti-table",
                                                                                "comment" => ""];
      }
      if (Session::haveRight("rssfeed_public", READ)) {

         $widgets[PluginMydashboardMenu::$RSS_VIEW]["rssfeedpublicwidget"] = ["title"   => _n('Public RSS feed', 'Public RSS feeds', 2),
                                                                              "icon"    => "ti ti-table",
                                                                              "comment" => ""];
      }

      return $widgets;
   }

   /**
    * @param $widgetId
    * @return Nothing
    */
   function getWidgetContentForItem($widgetId) {
      switch ($widgetId) {
         case "rssfeedpersonalwidget":
            return PluginMydashboardRSSFeed::showListForCentral();
            break;
         case "rssfeedpublicwidget":
            if (Session::haveRight("rssfeed_public", READ)) {
               return PluginMydashboardRSSFeed::showListForCentral(false);
            }
            break;
      }
   }

   /**
    * Show list for central view
    *
    * @param $personal boolean   display rssfeeds created by me ? (true by default)
    *
    * @return \PluginMydashboardDatatable (display function)
    */
   static function showListForCentral($personal = true) {
      global $DB, $CFG_GLPI;

      $output = [];

      $users_id = Session::getLoginUserID();

      if ($personal) {

         /// Personal notes only for central view
         if (Session::getCurrentInterface() == 'helpdesk') {
            return false;
         }

         $query = "SELECT `glpi_rssfeeds`.*
                   FROM `glpi_rssfeeds`
                   WHERE `glpi_rssfeeds`.`users_id` = '$users_id'
                         AND `glpi_rssfeeds`.`is_active` = '1'
                   ORDER BY `glpi_rssfeeds`.`name`";

         $titre = "<a style=\"font-size:14px;\" href=\"" . $CFG_GLPI["root_doc"] . "/front/rssfeed.php\">" . _n('Personal RSS feed', 'Personal RSS feeds', 2) . "</a>";

      } else {
         // Show public rssfeeds / not mines : need to have access to public rssfeeds
         if (!Session::haveRight('rssfeed_public', READ)) {
            return false;
         }

         $restrict_user = '1';
         // Only personal on central so do not keep it
         if (Session::getCurrentInterface() == 'central') {
            $restrict_user = "`glpi_rssfeeds`.`users_id` <> '$users_id'";
         }

         $query = "SELECT `glpi_rssfeeds`.*
                   FROM `glpi_rssfeeds` " .
            RSSFeed::addVisibilityJoins() . "
                   WHERE $restrict_user
                         AND " . RSSFeed::addVisibilityRestrict() . "
                   ORDER BY `glpi_rssfeeds`.`name`";

         if (Session::getCurrentInterface() != 'helpdesk') {
            $titre = "<a style=\"font-size:14px;\" href=\"" . $CFG_GLPI["root_doc"] . "/front/rssfeed.php\">" . _n('Public RSS feed', 'Public RSS feeds', 2) . "</a>";
         } else {
            $titre = _n('Public RSS feed', 'Public RSS feeds', 2);
         }
      }

      $result = $DB->query($query);
      $items = [];
      $rssfeed = new RSSFeed();
      if ($nb = $DB->numrows($result)) {
         while ($data = $DB->fetchAssoc($result)) {
            if ($rssfeed->getFromDB($data['id'])) {
               // Force fetching feeds
               if ($feed = RSSFeed::getRSSFeed($data['url'], $data['refresh_rate'])) {
                  // Store feeds in array of feeds
                  $items = array_merge($items, $feed->get_items(0, $data['max_items']));
                  $rssfeed->setError(false);
               } else {
                  $rssfeed->setError(true);
               }
            }
         }
      }

      $output['title'] = "<span>$titre</span>";

      if (RSSFeed::canCreate()) {
         $output['title'] .= "<span class=\"rssfeed_right\">";
         $output['title'] .= "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/rssfeed.form.php\">";
         $output['title'] .= "<i class='ti ti-plus'></i><span class='sr-only'>". __s('Add')."</span></a></span>";
      }

      $count = 0;
      $output['header'][0] = __('Date');
      $output['header'][1] = __('Title');

      $output['body'] = [];

      if ($nb) {
         usort($items, ['SimplePie', 'sort_items']);
         foreach ($items as $item) {
            $output['body'][$count][0] = Html::convDateTime($item->get_date('Y-m-d H:i:s'));
            $link = $item->feed->get_permalink();
            if (empty($link)) {
               $output['body'][$count][1] = $item->feed->get_title();
            } else {
               $output['body'][$count][1] = "<a target=\"_blank'\" href=\"$link\">" . $item->feed->get_title() . '</a>';
            }
            $link = $item->get_permalink();
            $rand = mt_rand();
            $output['body'][$count][1] .= "<div id=\"rssitem$rand\" class=\"pointer rss\">";
            if (!is_null($link)) {
               $output['body'][$count][1] .= "<a target=\"_blank\" href=\"$link\">";
            }
            $output['body'][$count][1] .= $item->get_title();
            if (!is_null($link)) {
               $output['body'][$count][1] .= "</a>";
            }
            $output['body'][$count][1] .= "</div>";
            $output['body'][$count][1] .= Html::showToolTip( Glpi\RichText\RichText::getSafeHtml($item->get_content()),
               ['applyto' => "rssitem$rand",
                  'display' => false]);
            $count++;
         }
      }

      $publique = $personal ? "personal" : "public";

      //First we create a new Widget of Datatable kind
      $widget = new PluginMydashboardDatatable();
      //We set the widget title and the id
      $widget->setWidgetTitle($output['title']);
      $widget->setWidgetId("rssfeed" . $publique . "widget");
      //We set the datas of the widget (which will be later automatically formatted by the method getJSonData of PluginMydashboardDatatable)
      $widget->setTabNames($output['header']);
      $widget->setTabDatas($output['body']);

      //Here we set few otions concerning the jquery library Datatable, bSort for sorting, bPaginate for paginating ...
      if (count($output['body']) > 0){
         $widget->setOption("bSort", false);
      }
      $widget->setOption("bPaginate", false);
      $widget->setOption("bFilter", false);
      $widget->setOption("bInfo", false);

      return $widget;
   }

}
