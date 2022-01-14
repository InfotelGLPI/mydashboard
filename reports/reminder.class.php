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
 * This class extends GLPI class reminder to add the functions to display widgets on Dashboard
 */
class PluginMydashboardReminder extends CommonGLPI {

   static function getTypeName($nb = 0) {
      return __('Reminder');
   }

   /**
    * @return array
    */
   function getWidgetsForItem() {

      $widgets = [];
      if (Session::getCurrentInterface() != 'helpdesk') {
         $widgets[PluginMydashboardMenu::$MY_VIEW]["reminderpersonalwidget"] = ["title"   => _n('Personal reminder', 'Personal reminders', 2),
                                                                                "icon"    => "ti ti-table",
                                                                                "comment" => ""];
      }
      if (Session::haveRight("reminder_public", READ)) {

         $widgets[PluginMydashboardMenu::$MY_VIEW]["reminderpublicwidget"] = ["title"   => _n('Public reminder', 'Public reminders', 2),
                                                                                                         "icon"    => "ti ti-table",
                                                                                                         "comment" => ""];
      }

      return $widgets;
   }

   /**
    * @param $widgetId
    *
    * @return Nothing
    */
   function getWidgetContentForItem($widgetId) {
      switch ($widgetId) {
         case "reminderpersonalwidget":
            return self::showListForCentral();
            break;
         case "reminderpublicwidget":
            if (Session::haveRight("reminder_public", READ)) {
               return self::showListForCentral(false);
            }
            break;
      }
   }

   /**
    * Return visibility SQL restriction to add
    *
    * @return string restrict to add
    **/
   static function addVisibilityRestrict() {
      //not deprecated because used in Search

      //get and clean criteria
      $criteria = self::getVisibilityCriteria();
      unset($criteria['LEFT JOIN']);
      $criteria['FROM'] = Reminder::getTable();

      $it = new \DBmysqlIterator(null);
      $it->buildQuery($criteria);
      $sql = $it->getSql();
      $sql = preg_replace('/.*WHERE /', '', $sql);

      return $sql;
   }


   /**
    * Return visibility joins to add to DBIterator parameters
    *
    * @param boolean $forceall force all joins (false by default)
    *
    * @return array
    * @since 9.4
    *
    */
   static public function getVisibilityCriteria(bool $forceall = false): array {
      if (!Session::haveRight(Reminder::$rightname, READ)) {
         return [
            'WHERE' => ['glpi_reminders.users_id' => Session::getLoginUserID()],
         ];
      }

      $join  = [];
      $where = [];

      // Users
      $join['glpi_reminders_users'] = [
         'FKEY' => [
            'glpi_reminders_users' => 'reminders_id',
            'glpi_reminders'       => 'id'
         ]
      ];
      //disabled for plugin

      //      if (Session::getLoginUserID()) {
      //         $where['OR'] = [
      //               'glpi_reminders.users_id'        => Session::getLoginUserID(),
      //               'glpi_reminders_users.users_id'  => Session::getLoginUserID(),
      //         ];
      //      } else {
      //         $where = [
      //            0
      //         ];
      //      }

      // Groups
      if ($forceall
          || (isset($_SESSION["glpigroups"]) && count($_SESSION["glpigroups"]))) {
         $join['glpi_groups_reminders'] = [
            'FKEY' => [
               'glpi_groups_reminders' => 'reminders_id',
               'glpi_reminders'        => 'id'
            ]
         ];

         $or       = ['glpi_groups_reminders.entities_id' => ['<', 0]];
         $restrict = getEntitiesRestrictCriteria('glpi_groups_reminders', '', '', true);
         if (count($restrict)) {
            $or = $or + $restrict;
         }
         $where['OR'][] = [
            'glpi_groups_reminders.groups_id' => count($_SESSION["glpigroups"])
               ? $_SESSION["glpigroups"]
               : [-1],
            'OR'                              => $or
         ];
      }

      // Profiles
      if ($forceall
          || (isset($_SESSION["glpiactiveprofile"])
              && isset($_SESSION["glpiactiveprofile"]['id']))) {
         $join['glpi_profiles_reminders'] = [
            'FKEY' => [
               'glpi_profiles_reminders' => 'reminders_id',
               'glpi_reminders'          => 'id'
            ]
         ];

         $or       = ['glpi_profiles_reminders.entities_id' => ['<', 0]];
         $restrict = getEntitiesRestrictCriteria('glpi_profiles_reminders', '', '', true);
         if (count($restrict)) {
            $or = $or + $restrict;
         }
         $where['OR'][] = [
            'glpi_profiles_reminders.profiles_id' => $_SESSION["glpiactiveprofile"]['id'],
            'OR'                                  => $or
         ];
      }

      // Entities
      if ($forceall
          || (isset($_SESSION["glpiactiveentities"]) && count($_SESSION["glpiactiveentities"]))) {
         $join['glpi_entities_reminders'] = [
            'FKEY' => [
               'glpi_entities_reminders' => 'reminders_id',
               'glpi_reminders'          => 'id'
            ]
         ];
      }
      if (isset($_SESSION["glpiactiveentities"]) && count($_SESSION["glpiactiveentities"])) {
         $restrict = getEntitiesRestrictCriteria('glpi_entities_reminders', '', '', true, true);
         if (count($restrict)) {
            $where['OR'] = $where['OR'] + $restrict;
         }
      }

      $criteria = [
         'LEFT JOIN' => $join,
         'WHERE'     => $where
      ];

      return $criteria;
   }

   /**
    * Return visibility joins to add to SQL
    *
    * @param $forceall force all joins (false by default)
    *
    * @return string joins to add
    **/
   static function addVisibilityJoins($forceall = false) {

      if (!Session::haveRight(Reminder::$rightname, READ)) {
         return '';
      }
      // Users
      $join = " LEFT JOIN `glpi_reminders_users`
                     ON (`glpi_reminders_users`.`reminders_id` = `glpi_reminders`.`id`) ";

      // Groups
      if ($forceall
          || (isset($_SESSION["glpigroups"]) && count($_SESSION["glpigroups"]))) {
         $join .= " LEFT JOIN `glpi_groups_reminders`
                        ON (`glpi_groups_reminders`.`reminders_id` = `glpi_reminders`.`id`) ";
      }

      // Profiles
      if ($forceall
          || (isset($_SESSION["glpiactiveprofile"])
              && isset($_SESSION["glpiactiveprofile"]['id']))) {
         $join .= " LEFT JOIN `glpi_profiles_reminders`
                        ON (`glpi_profiles_reminders`.`reminders_id` = `glpi_reminders`.`id`) ";
      }

      // Entities
      if ($forceall
          || (isset($_SESSION["glpiactiveentities"]) && count($_SESSION["glpiactiveentities"]))) {
         $join .= " LEFT JOIN `glpi_entities_reminders`
                        ON (`glpi_entities_reminders`.`reminders_id` = `glpi_reminders`.`id`) ";
      }

      return $join;

   }

   /**
    * Show list for central view
    *
    * @param $personal boolean : display reminders created by me ? (true by default)
    *
    * @return Nothing (display function)
    **/
   static function showListForCentral($personal = true) {
      global $DB, $CFG_GLPI;

      $output = [];

      $users_id = Session::getLoginUserID();
      $today    = date('Y-m-d');
      $now      = date('Y-m-d H:i:s');

      $restrict_visibility = " AND (`glpi_reminders`.`begin_view_date` IS NULL
                                    OR `glpi_reminders`.`begin_view_date` < '$now')
                              AND (`glpi_reminders`.`end_view_date` IS NULL
                                   OR `glpi_reminders`.`end_view_date` > '$now') ";

      if ($personal) {

         /// Personal notes only for central view
         if (Session::getCurrentInterface() == 'helpdesk') {
            return false;
         }

         $query = "SELECT `glpi_reminders`.*
                   FROM `glpi_reminders`
                   WHERE `glpi_reminders`.`users_id` = '$users_id'
                         AND (`glpi_reminders`.`end` >= '$today'
                              OR `glpi_reminders`.`is_planned` = '0')
                         $restrict_visibility
                   ORDER BY `glpi_reminders`.`name`";

         $titre = "<a style=\"font-size:14px;\" href=\"" . $CFG_GLPI["root_doc"] . "/front/reminder.php\">" . _n('Personal reminder', 'Personal reminders', 2) . "</a>";

      } else {
         // Show public reminders / not mines : need to have access to public reminders
         if (!Session::haveRight('reminder_public', READ)) {
            return false;
         }

         $restrict_user = '1';
         // Only personal on central so do not keep it
         if (Session::getCurrentInterface() == 'central') {
            $restrict_user = "`glpi_reminders`.`users_id` <> '$users_id'";
         }

         $query = "SELECT `glpi_reminders`.*
                   FROM `glpi_reminders` " .
                  self::addVisibilityJoins() . "
                   WHERE $restrict_user
                         $restrict_visibility
                         AND " . Reminder::addVisibilityRestrict() . "
                   ORDER BY `glpi_reminders`.`name`";

         if (Session::getCurrentInterface() != 'helpdesk') {
            $titre = "<a style=\"font-size:14px;\" href=\"" . $CFG_GLPI["root_doc"] . "/front/reminder.php\">" .
                     _n('Public reminder', 'Public reminders', 2) . "</a>";
         } else {
            $titre = _n('Public reminder', 'Public reminders', 2);
         }
      }

      $result = $DB->query($query);
      $nb     = $DB->numrows($result);

      $output['title'] = "<span>$titre</span>";

      if (Reminder::canCreate()) {
         $output['title'] .= "&nbsp;<span>";
         $output['title'] .= "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/reminder.form.php\">";
         $output['title'] .= "<i class='ti ti-plus'></i><span class='sr-only'>" . __s('Add') . "</span></a>";
      }

      $output['title'] .= "";

      $output['header'][] = '';

      $output['body'] = [];

      $count = 0;

      if ($nb) {
         $rand = mt_rand();

         while ($data = $DB->fetchAssoc($result)) {
            $output['body'][$count]    = [];
            $output['body'][$count][0] = '';
            $output['body'][$count][0] .= "<div class=\"relative reminder_list\">";
            $link                      = "<a id=\"content_reminder_" . $data["id"] . $rand . "\"  href=\"" . $CFG_GLPI["root_doc"] . "/front/reminder.form.php?id=" . $data["id"] . "\">" . $data["name"] . "</a>";

            $tooltip = Html::showToolTip( Glpi\RichText\RichText::getSafeHtml($data["text"]),
                                         ['applyto' => "content_reminder_" . $data["id"] . $rand,
                                          'display' => false]);

            $output['body'][$count][0] .= $link . ' ' . $tooltip;

            if ($data["is_planned"]) {
               $tab                       = explode(" ", $data["begin"]);
               $date_url                  = $tab[0];
               $output['body'][$count][0] .= "<span class=\"reminder_right\">";
               $output['body'][$count][0] .= "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/planning.php?date=" . $date_url . "&amp;type=day\">";
               $output['body'][$count][0] .= "<i class='ti ti-clock' title=\"" . sprintf(__s('From %1$s to %2$s'),
                                                                                            Html::convDateTime($data["begin"]),
                                                                                            Html::convDateTime($data["end"])) . "\"></i><span class='sr-only'></span>";
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
   static function showNewsList() {
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
                " . self::addVisibilityJoins() . "
                WHERE $restrict_user
                      $restrict_visibility
                     AND " . Reminder::addVisibilityRestrict() . "
                     ORDER BY `glpi_reminders`.`name`";

      $titre = _n('Public reminder', 'Public reminders', 2);

      $result = $DB->query($query);
      $nb     = $DB->numrows($result);

      if ($nb) {
         echo "<table class='treetable'>";
         echo "<thead><tr><th class='title'>" . $titre . "</th></tr></thead>";
         echo "</table>";
         echo "<div id='wrap-treetable3'>";
         echo "<div id='fibnews-div'>";
         echo "<ul>";
         while ($data = $DB->fetchArray($result)) {
            echo "<li>";
            echo '<h1>' . $data["name"] . '</h1>';
            echo  Glpi\RichText\RichText::getSafeHtml($data["text"]);
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
