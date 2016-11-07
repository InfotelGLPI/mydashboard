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
 * This class extends GLPI class reminder to add the functions to display widgets on Dashboard
 */
class PluginMydashboardReminder
{

   /**
    * @return array
    */
   function getWidgetsForItem()
   {
      $array = array();
      if ($_SESSION['glpiactiveprofile']['interface'] != 'helpdesk') {
         $array = array(
            PluginMydashboardMenu::$MY_VIEW =>
               array(
                  "reminderpersonalwidget" => _n('Personal reminder', 'Personal reminders', 2)
               )
         );
      }
      if (Session::haveRight("reminder_public", READ)) {
         $array[PluginMydashboardMenu::$MY_VIEW]["reminderpublicwidget"] = _n('Public reminder', 'Public reminders', 2);
      }
      return $array;
   }

   /**
    * @param $widgetId
    * @return Nothing
    */
   function getWidgetContentForItem($widgetId)
   {
      switch ($widgetId) {
         case "reminderpersonalwidget":
            return PluginMydashboardReminder::showListForCentral();
            break;
         case "reminderpublicwidget":
            if (Session::haveRight("reminder_public", READ)) {
               return PluginMydashboardReminder::showListForCentral(false);
            }
            break;
      }
   }

   /**
    * Show list for central view
    *
    * @param $personal boolean : display reminders created by me ? (true by default)
    *
    * @return Nothing (display function)
    **/
   static function showListForCentral($personal = true)
   {
      global $DB, $CFG_GLPI;

      $output = array();

      $users_id = Session::getLoginUserID();
      $today = date('Y-m-d');
      $now = date('Y-m-d H:i:s');

      $restrict_visibility = " AND (`glpi_reminders`.`begin_view_date` IS NULL
                                    OR `glpi_reminders`.`begin_view_date` < '$now')
                              AND (`glpi_reminders`.`end_view_date` IS NULL
                                   OR `glpi_reminders`.`end_view_date` > '$now') ";

      if ($personal) {

         /// Personal notes only for central view
         if ($_SESSION['glpiactiveprofile']['interface'] == 'helpdesk') {
            return false;
         }

         $query = "SELECT `glpi_reminders`.*
                   FROM `glpi_reminders`
                   WHERE `glpi_reminders`.`users_id` = '$users_id'
                         AND (`glpi_reminders`.`end` >= '$today'
                              OR `glpi_reminders`.`is_planned` = '0')
                         $restrict_visibility
                   ORDER BY `glpi_reminders`.`name`";

         $titre = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/reminder.php\">" . _n('Personal reminder', 'Personal reminders', 2) . "</a>";

      } else {
         // Show public reminders / not mines : need to have access to public reminders
         if (!Session::haveRight('reminder_public', READ)) {
            return false;
         }

         $restrict_user = '1';
         // Only personal on central so do not keep it
         if ($_SESSION['glpiactiveprofile']['interface'] == 'central') {
            $restrict_user = "`glpi_reminders`.`users_id` <> '$users_id'";
         }

         $query = "SELECT `glpi_reminders`.*
                   FROM `glpi_reminders` " .
            Reminder::addVisibilityJoins() . "
                   WHERE $restrict_user
                         $restrict_visibility
                         AND " . Reminder::addVisibilityRestrict() . "
                   ORDER BY `glpi_reminders`.`name`";

         if ($_SESSION['glpiactiveprofile']['interface'] != 'helpdesk') {
            $titre = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/reminder.php\">" .
               _n('Public reminder', 'Public reminders', 2) . "</a>";
         } else {
            $titre = _n('Public reminder', 'Public reminders', 2);
         }
      }

      $result = $DB->query($query);
      $nb = $DB->numrows($result);

      $output['title'] = "<span>$titre</span>";

      if (Reminder::canCreate()) {
         $output['title'] .= "&nbsp;<span>";
         $output['title'] .= "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/reminder.form.php\">";
         $output['title'] .= "<img src=\"" . $CFG_GLPI["root_doc"] . "/pics/plus.png\" alt=\"" . __s('Add') . "\" title=\"" . __s('Add') . "\"></a></span>";
      }

      $output['title'] .= "";

      $output['header'][] = '';

      $output['body'] = array();

      $count = 0;

      if ($nb) {
         $rand = mt_rand();

         while ($data = $DB->fetch_assoc($result)) {
            $output['body'][$count] = array();
            $output['body'][$count][0] = '';
            $output['body'][$count][0] .= "<div class=\"relative reminder_list\">";
            $link = "<a id=\"content_reminder_" . $data["id"] . $rand . "\"  href=\"" . $CFG_GLPI["root_doc"] . "/front/reminder.form.php?id=" . $data["id"] . "\">" . $data["name"] . "</a>";

            $tooltip = Html::showToolTip(Toolbox::unclean_html_cross_side_scripting_deep($data["text"]),
               array('applyto' => "content_reminder_" . $data["id"] . $rand,
                  'display' => false));

            $output['body'][$count][0] .= $link . ' ' . $tooltip;

            if ($data["is_planned"]) {
               $tab = explode(" ", $data["begin"]);
               $date_url = $tab[0];
               $output['body'][$count][0] .= "<span class=\"reminder_right\">";
               $output['body'][$count][0] .= "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/planning.php?date=" . $date_url . "&amp;type=day\">";
               $output['body'][$count][0] .= "<img src=\"" . $CFG_GLPI["root_doc"] . "/pics/rdv.png\" alt=\"" . __s('Planning') . "\" title=\"" . sprintf(__s('From %1$s to %2$s'),
                     Html::convDateTime($data["begin"]),
                     Html::convDateTime($data["end"])) . "\">";
               $output['body'][$count][0] .= "</a></span>";
            }

            $output['body'][$count][0] .= "</div>";
            $count++;
         }
      } else {
         $output['body'][$count][0] = '';
      }

      $publique = ($personal) ? "personal" : "public";

      $widget = new PluginMydashboardDatatable();
      $widget->setWidgetTitle($output['title']);
      $widget->setWidgetId("reminder" . $publique . "widget");

      $widget->setTabNames($output['header']);
      $widget->setTabDatas($output['body']);

      return $widget;
   }

   /**
    * Show list for central view
    * @return Nothing
    * @internal param bool $personal : display reminders created by me ?
    *
    */
   static function showNewsList()
   {
      global $DB;

      $now = date('Y-m-d H:i:s');

      $restrict_visibility = " AND (`glpi_reminders`.`begin_view_date` IS NULL
                                    OR `glpi_reminders`.`begin_view_date` < '$now')
                               AND (`glpi_reminders`.`end_view_date` IS NULL
                                    OR `glpi_reminders`.`end_view_date` > '$now') ";


      // Show public reminders / not mines : need to have access to public reminders
      if (!Session::haveRight('reminder_public', READ)) {
         return false;
      }

      $restrict_user = '1';

      $query = "SELECT `glpi_reminders`.*
                FROM `glpi_reminders`
                " . Reminder::addVisibilityJoins() . "
                WHERE $restrict_user
                      $restrict_visibility
                     AND " . Reminder::addVisibilityRestrict() . "
                     ORDER BY `glpi_reminders`.`name`";

      $titre = _n('Public reminder', 'Public reminders', 2);

      $result = $DB->query($query);
      $nb = $DB->numrows($result);

      if ($nb) {
         echo "<table class='treetable'>";
         echo "<thead><tr><th class='title'>" . $titre . "</th></tr></thead>";
         echo "</table>";
         echo "<div id='wrap-treetable3'>";
         echo "<div id='fibnews-div'>";
         echo "<ul>";
         while ($data = $DB->fetch_array($result)) {
            echo "<li>";
            echo '<h1>' . $data["name"] . '</h1>';
            echo Toolbox::unclean_html_cross_side_scripting_deep($data["text"]);
            echo "</li>";
         }
         echo "</ul>";
         echo "</div></div>";

         echo "<script type='text/javascript'>
                  $(function() {
                     $('#fibnews-div').vTicker({
                        speed: 500,
                        pause: 3000,
                        showItems: 3,
                        animation: 'fade',
                        mousePause: true,
                        height: 0,
                        direction: 'up'
                     });
                  });
               </script>";
      }
   }

}