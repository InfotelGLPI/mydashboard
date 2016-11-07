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

/**
 * Class PluginMydashboardKnowbaseItem
 */
class PluginMydashboardKnowbaseItem extends CommonGLPI
{
   static $rightname = 'knowbase';

   /**
    * @return array
    */
   public function getWidgetsForItem()
   {
      return array(
         PluginMydashboardMenu::$GLOBAL_VIEW => array(
            "knowbaseitempopular" => __('FAQ') . " - " . __('Most popular questions'),
            "knowbaseitemrecent" => __('FAQ') . " - " . __('Recent entries'),
            "knowbaseitemlastupdate" => __('FAQ') . " - " . __('Last updated entries')
         )

      );
   }

   /**
    * @param $widgetId
    * @return PluginMydashboardDatatable
    */
   public function getWidgetContentForItem($widgetId)
   {
      global $DB, $CFG_GLPI;

      $faq = !Session::haveRight(self::$rightname, READ);

      if ($widgetId == "knowbaseitemrecent") {
         $orderby = "ORDER BY `date` DESC";
         $title = __('FAQ') . " - " . __('Recent entries');
      } else if ($widgetId == 'knowbaseitemlastupdate') {
         $orderby = "ORDER BY `date_mod` DESC";
         $title = __('FAQ') . " - " . __('Last updated entries');
      } else {
         $orderby = "ORDER BY `view` DESC";
         $title = __('FAQ') . " - " . __('Most popular questions');
      }

      $faq_limit = "";
      // Force all joins for not published to verify no visibility set
      $join = KnowbaseItem::addVisibilityJoins(true);

      if (Session::getLoginUserID()) {
         $faq_limit .= "WHERE " . KnowbaseItem::addVisibilityRestrict();
      } else {
         // Anonymous access
         if (Session::isMultiEntitiesMode()) {
            $faq_limit .= " WHERE (`glpi_entities_knowbaseitems`.`entities_id` = '0'
                                   AND `glpi_entities_knowbaseitems`.`is_recursive` = '1')";
         } else {
            $faq_limit .= " WHERE 1";
         }
      }


      // Only published
      $faq_limit .= " AND (`glpi_entities_knowbaseitems`.`entities_id` IS NOT NULL
                           OR `glpi_knowbaseitems_profiles`.`profiles_id` IS NOT NULL
                           OR `glpi_groups_knowbaseitems`.`groups_id` IS NOT NULL
                           OR `glpi_knowbaseitems_users`.`users_id` IS NOT NULL)";

      if ($faq) { // FAQ
         $faq_limit .= " AND (`glpi_knowbaseitems`.`is_faq` = '1')";
      }

      $query = "SELECT DISTINCT `glpi_knowbaseitems`.`id`, `glpi_knowbaseitems`.`name`,`glpi_knowbaseitems`.`is_faq`, `glpi_knowbaseitems`.`date`, `glpi_knowbaseitems`.`date_mod`
                FROM `glpi_knowbaseitems`
                $join
                $faq_limit
                $orderby
                LIMIT 10";

      $result = $DB->query($query);
      $tab = array();
      while ($row = $DB->fetch_assoc($result)) {
         $date = "";
         if ($widgetId == "knowbaseitemrecent") {
            $date = $row["date"];
         } else {
            $date = $row["date_mod"];
         }
         $tab[] = array(
            "<a " . ($row['is_faq'] ? " class='pubfaq' " : " class='knowbase' ") . " href=\"" .
            $CFG_GLPI["root_doc"] . "/front/knowbaseitem.form.php?id=" . $row["id"] . "\">" .
            Html::resume_text($row["name"], 80) . "</a>", Html::convDateTime($date)
         );
      }
      if ($widgetId == "knowbaseitemrecent") {
         $headers = array(__('Name'), __('Publication date', 'mydashboard'));
      } else {
         $headers = array(__('Name'), __('Modification date', 'mydashboard'));
      }

      $widget = new PluginMydashboardDatatable();
      $widget->setTabNames($headers);
      $widget->setTabDatas($tab);
      $widget->setWidgetTitle($title);
      return $widget;
   }
}
