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
 * This class extends GLPI class change to add the functions to display a widget on Dashboard
 */
class PluginMydashboardChange extends CommonGLPI {

   /**
    * @param int $nb
    *
    * @return translated
    */
   static function getTypeName($nb = 0) {
      return __('Dashboard', 'mydashboard');
   }

   /**
    * @return array
    */
   function getWidgetsForItem() {
      $array      = [];
      $showchange = Session::haveRightsOr('change', [Change::READALL, Change::READMY]);

      if ($showchange) {
         $array = [
            PluginMydashboardMenu::$CHANGE_VIEW =>
               [
                  "changeprocesswidget" => __('Changes to be processed', 'mydashboard') . "&nbsp;<i class='fas fa-table'></i>",
                  "changewaitingwidget" => __('Changes on pending status', 'mydashboard') . "&nbsp;<i class='fas fa-table'></i>",
                  "changeappliedwidget" => __('Applied changes', 'mydashboard') . "&nbsp;<i class='fas fa-table'></i>",
               ],
            PluginMydashboardMenu::$GROUP_VIEW  =>
               [
                  "changeprocesswidgetgroup" => __('Changes to be processed', 'mydashboard') . "&nbsp;<i class='fas fa-table'></i>",
                  "changewaitingwidgetgroup" => __('Changes on pending status', 'mydashboard') . "&nbsp;<i class='fas fa-table'></i>"

               ],
            PluginMydashboardMenu::$GLOBAL_VIEW =>
               [
                  "changecountwidget" => __('Change followup', 'mydashboard') . "&nbsp;<i class='fas fa-table'></i>"
               ]
         ];
      }
      return $array;
   }

   /**
    * @param $widgetId
    *
    * @return PluginMydashboardDatatable
    */
   function getWidgetContentForItem($widgetId) {
      $showchange = Session::haveRightsOr('change', [Change::READALL, Change::READMY]);

      if ($showchange) {
         switch ($widgetId) {
            case "changeprocesswidget":
               return self::showCentralList(0, "process", false);
               break;
            case "changeappliedwidget":
               return self::showCentralList(0, "applied", false);
               break;
            case "changeprocesswidgetgroup":
               return self::showCentralList(0, "process", true);
               break;
            case "changewaitingwidget":
               return self::showCentralList(0, "waiting", false);
               break;
            case "changewaitingwidgetgroup":
               return self::showCentralList(0, "waiting", true);
               break;
            case "changecountwidget":
               return self::showCentralCount();
               break;
         }
      }
   }

   /**
    * @param        $start
    * @param string $status
    * @param bool   $showgroupchanges
    *
    * @return PluginMydashboardDatatable
    */
   static function showCentralList($start, $status = "process", $showgroupchanges = true) {
      global $DB, $CFG_GLPI;

      $output = [];
      //We declare our new widget
      $widget = new PluginMydashboardDatatable();
      if ($status == "waiting") {
         $widget->setWidgetTitle(Html::makeTitle(__('Changes on pending status', 'mydashboard'), 0, 0));
      } else {
         $widget->setWidgetTitle(Html::makeTitle(__('Changes to be processed', 'mydashboard'), 0, 0));
      }
      $group = ($showgroupchanges) ? "group" : "";
      $widget->setWidgetId("change" . $status . "widget" . $group);
      //Here we set few otions concerning the jquery library Datatable, bPaginate for paginating ...
      $widget->setOption("bPaginate", false);
      $widget->setOption("bFilter", false);
      $widget->setOption("bInfo", false);

      if (!Session::haveRightsOr('change', [Change::READALL, Change::READMY])) {
         return false;
      }

      $search_users_id = " (`glpi_changes_users`.`users_id` = '" . Session::getLoginUserID() . "'
                            AND `glpi_changes_users`.`type` = '" . CommonITILActor::REQUESTER . "') ";
      $search_assign   = " (`glpi_changes_users`.`users_id` = '" . Session::getLoginUserID() . "'
                            AND `glpi_changes_users`.`type` = '" . CommonITILActor::ASSIGN . "')";
      $is_deleted      = " `glpi_changes`.`is_deleted` = 0 ";

      if ($showgroupchanges) {
         $search_users_id = " 0 = 1 ";
         $search_assign   = " 0 = 1 ";

         if (count($_SESSION['glpigroups'])) {
            $groups        = implode("','", $_SESSION['glpigroups']);
            $search_assign = " (`glpi_changes_groups`.`groups_id` IN ('$groups')
                                  AND `glpi_changes_groups`.`type`
                                        = '" . CommonITILActor::ASSIGN . "')";

            $search_users_id = " (`glpi_changes_groups`.`groups_id` IN ('$groups')
                                  AND `glpi_changes_groups`.`type`
                                        = '" . CommonITILActor::REQUESTER . "') ";
         }
      }
      $dbu   = new DbUtils();
      $query = "SELECT DISTINCT `glpi_changes`.`id`
                FROM `glpi_changes`
                LEFT JOIN `glpi_changes_users`
                     ON (`glpi_changes`.`id` = `glpi_changes_users`.`changes_id`)
                LEFT JOIN `glpi_changes_groups`
                     ON (`glpi_changes`.`id` = `glpi_changes_groups`.`changes_id`)";

      switch ($status) {
         case "waiting" : // on affiche les changements en attente
            $query .= "WHERE $is_deleted
                             AND ($search_assign)
                             AND `status` = '" . Change::WAITING . "' " .
                      $dbu->getEntitiesRestrictRequest("AND", "glpi_changes");
            break;

         case "process" : // on affiche les changements planifiés ou assignés au user
            $query .= "WHERE $is_deleted
                             AND ($search_assign)
                             AND (`status` IN ('" . implode("','", Change::getProcessStatusArray()) . "')) " .
                      $dbu->getEntitiesRestrictRequest("AND", "glpi_changes");
            break;

         case "applied" : // on affiche les changements qui vont être mis en production
            $query .= "WHERE $is_deleted
                             AND (`status` IN ('" . implode("','", Change::getSolvedStatusArray()) . "')) 
                             AND solvedate > DATE_SUB(CURDATE(), interval 30 DAY) " .
                      $dbu->getEntitiesRestrictRequest("AND", "glpi_changes");
            break;

         default :
            $query .= "WHERE $is_deleted
                             AND ($search_users_id)
                             AND (`status` IN ('" . implode("','", Change::getNewStatusArray()) . "','" . implode("','", Change::getProcessStatusArray()) . "',
                                               '" . Change::WAITING . "'))
                             AND NOT ($search_assign) " .
                      $dbu->getEntitiesRestrictRequest("AND", "glpi_changes");
      }

      $query   .= " ORDER BY date_mod DESC";
      $result  = $DB->query($query);
      $numrows = $DB->numrows($result);

      //      if ($_SESSION['glpidisplay_count_on_home'] > 0) {
      //         $query .= " LIMIT " . intval($start) . ',' . intval($_SESSION['glpidisplay_count_on_home']);
      $result = $DB->query($query);
      $number = $DB->numrows($result);
      //      } else {
      //         $number = 0;
      //      }

      if ($numrows > 0) {
         $output['title']  = "";
         $options['reset'] = 'reset';
         $forcetab         = '';
         $num              = 0;
         if ($showgroupchanges) {
            switch ($status) {

               case "waiting" :
                  foreach ($_SESSION['glpigroups'] as $gID) {
                     $options['field'][$num]      = 8; // groups_id_assign
                     $options['searchtype'][$num] = 'equals';
                     $options['contains'][$num]   = $gID;
                     $options['link'][$num]       = (($num == 0) ? 'AND' : 'OR');
                     $num++;
                     $options['field'][$num]      = 12; // status
                     $options['searchtype'][$num] = 'equals';
                     $options['contains'][$num]   = Change::WAITING;
                     $options['link'][$num]       = 'AND';
                     $num++;
                  }
                  $output['title'] .= "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/change.php?" .
                                      Toolbox::append_params($options, '&amp;') . "\">" .
                                      Html::makeTitle(__('Changes on pending status', 'mydashboard'), $number, $numrows) . "</a>";
                  break;

               case "process" :
                  foreach ($_SESSION['glpigroups'] as $gID) {
                     $options['field'][$num]      = 8; // groups_id_assign
                     $options['searchtype'][$num] = 'equals';
                     $options['contains'][$num]   = $gID;
                     $options['link'][$num]       = (($num == 0) ? 'AND' : 'OR');
                     $num++;
                     $options['field'][$num]      = 12; // status
                     $options['searchtype'][$num] = 'equals';
                     $options['contains'][$num]   = 'process';
                     $options['link'][$num]       = 'AND';
                     $num++;
                  }
                  $output['title'] .= "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/change.php?" .
                                      Toolbox::append_params($options, '&amp;') . "\">" .
                                      Html::makeTitle(__('Changes to be processed', 'mydashboard'), $number, $numrows) . "</a>";
                  break;

               default :
                  foreach ($_SESSION['glpigroups'] as $gID) {
                     $options['field'][$num]      = 71; // groups_id
                     $options['searchtype'][$num] = 'equals';
                     $options['contains'][$num]   = $gID;
                     $options['link'][$num]       = (($num == 0) ? 'AND' : 'OR');
                     $num++;
                     $options['field'][$num]      = 12; // status
                     $options['searchtype'][$num] = 'equals';
                     $options['contains'][$num]   = 'process';
                     $options['link'][$num]       = 'AND';
                     $num++;
                  }
                  $output['title'] .= "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/change.php?" .
                                      Toolbox::append_params($options, '&amp;') . "\">" .
                                      Html::makeTitle(__('Your changes in progress'), $number, $numrows) . "</a>";
            }
         } else {
            switch ($status) {
               case "waiting" :
                  $options['field'][0]      = 12; // status
                  $options['searchtype'][0] = 'equals';
                  $options['contains'][0]   = Change::WAITING;
                  $options['link'][0]       = 'AND';

                  $options['field'][1]      = 5; // users_id_assign
                  $options['searchtype'][1] = 'equals';
                  $options['contains'][1]   = Session::getLoginUserID();
                  $options['link'][1]       = 'AND';

                  $output['title'] .= "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/change.php?" .
                                      Toolbox::append_params($options, '&amp;') . "\">" .
                                      Html::makeTitle(__('Changes on pending status', 'mydashboard'), $number, $numrows) . "</a>";
                  break;

               case "process" :
                  $options['field'][0]      = 5; // users_id_assign
                  $options['searchtype'][0] = 'equals';
                  $options['contains'][0]   = Session::getLoginUserID();
                  $options['link'][0]       = 'AND';

                  $options['field'][1]      = 12; // status
                  $options['searchtype'][1] = 'equals';
                  $options['contains'][1]   = 'process';
                  $options['link'][1]       = 'AND';

                  $output['title'] .= "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/change.php?" .
                                      Toolbox::append_params($options, '&amp;') . "\">" .
                                      Html::makeTitle(__('Changes to be processed', 'mydashboard'), $number, $numrows) . "</a>";
                  break;

               case "applied" :
                  $options['field'][$num]      = 12; // status
                  $options['searchtype'][$num] = 'equals';
                  $options['contains'][$num]   = 'solved';
                  $options['link'][$num]       = 'AND';
                  $num++;
                  $output['title'] .= "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/change.php?" .
                                      Toolbox::append_params($options, '&amp;') . "\">" .
                                      Html::makeTitle(__('Applied changes', 'mydashboard'), $number, $numrows) . "</a>";
                  break;

               default :
                  $options['field'][0]      = 4; // users_id
                  $options['searchtype'][0] = 'equals';
                  $options['contains'][0]   = Session::getLoginUserID();
                  $options['link'][0]       = 'AND';

                  $options['field'][1]      = 12; // status
                  $options['searchtype'][1] = 'equals';
                  $options['contains'][1]   = 'notold';
                  $options['link'][1]       = 'AND';

                  $output['title'] .= "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/change.php?" .
                                      Toolbox::append_params($options, '&amp;') . "\">" .
                                      Html::makeTitle(__('Your changes in progress'), $number, $numrows) . "</a>";
            }
         }

         if ($number) {
            $output['header'][] = __('ID');
            $output['header'][] = __('Requester');
            $output['header'][] = __('Description');
            $output['header'][] = __('Date of solving');
            for ($i = 0; $i < $number; $i++) {
               $ID               = $DB->result($result, $i, "id");
               $output['body'][] = self::showVeryShort($ID, $forcetab);
            }
         }

      }

      //We set the datas of the widget (which will be later automatically formatted by the method getJSonData of PluginMydashboardDatatable)
      if (isset($output['title'])) {
         $widget->setWidgetTitle($output['title']);
      }
      if (isset($output['header'])) {
         $widget->setTabNames($output['header']);
      }
      if (isset($output['body'])) {
         $widget->setTabDatas($output['body']);
      } else {
         $widget->setTabDatas([]);
      }

      return $widget;

   }

   /**
    * @param        $ID
    * @param string $forcetab
    *
    * @return array
    */
   static function showVeryShort($ID, $forcetab = '') {
      global $CFG_GLPI;

      $colnum = 0;
      $output = [];

      // Prints a job in short form
      // Should be called in a <table>-segment
      // Print links or not in case of user view
      // Make new job object and fill it from database, if success, print it
      $viewusers = Session::haveRight("user", READ);

      $change = new Change();
      $rand   = mt_rand();

      if ($change->getFromDBwithData($ID, 0)) {
         $bgcolor = $_SESSION["glpipriority_" . $change->fields["priority"]];
         //      $rand    = mt_rand();
         $output[$colnum] = "<div class='center' style='background-color:$bgcolor; padding: 10px;'>" . sprintf(__('%1$s: %2$s'), __('ID'),
                                                                                                               $change->fields["id"]) . "</div>";
         $colnum++;

         $output[$colnum] = '';
         $userrequesters  = $change->getUsers(CommonITILActor::REQUESTER);
         if (isset($userrequesters)
             && count($userrequesters)
         ) {
            foreach ($userrequesters as $d) {
               if ($d["users_id"] > 0) {
                  $userdata = getUserName($d["users_id"], 2);
                  $name     = "<div class='b center'>" . $userdata['name'];
                  if ($viewusers) {
                     $name = sprintf(__('%1$s %2$s'), $name,
                                     Html::showToolTip($userdata["comment"],
                                                       ['link'    => $userdata["link"],
                                                        'display' => false]));
                  }
                  $output[$colnum] .= $name . "</div>";
               } else {
                  $output[$colnum] .= $d['alternative_email'] . "&nbsp;";
               }
               //$output[$colnum] .=  "<br>";
            }
         }
         $grouprequester = $change->getGroups(CommonITILActor::REQUESTER);
         if (isset($grouprequester)
             && count($grouprequester)
         ) {
            foreach ($grouprequester as $d) {
               $output[$colnum] .= Dropdown::getDropdownName("glpi_groups", $d["groups_id"]);
            }
         }

         $colnum++;
         //$output[$colnum] = '';
         $link = "<a id='change" . $change->fields["id"] . $rand . "' href='" . $CFG_GLPI["root_doc"] .
                 "/front/change.form.php?id=" . $change->fields["id"];
         if ($forcetab != '') {
            $link .= "&amp;forcetab=" . $forcetab;
         }
         // echo "###########".$change->fields["name"];
         $link .= "'>";
         $link .= "<span class='b'>" . $change->fields["name"] . "</span></a>";

         $link = sprintf(__('%1$s %2$s'), $link,
                         Html::showToolTip($change->fields['content'],
                                           ['applyto' => 'change' . $change->fields["id"] . $rand,
                                            'display' => false]));
         //echo $link;
         $output[$colnum] = $link;
         $colnum++;
         $output[$colnum] = Html::convDateTime($change->fields['solvedate']);
      }
      return $output;
   }

   /**
    * @param bool $foruser
    *
    * @return PluginMydashboardDatatable
    */
   static function showCentralCount($foruser = false) {
      global $DB, $CFG_GLPI;

      // show a tab with count of jobs in the central and give link
      if (!Change::canView()) {
         return false;
      }
      if (!Session::haveRight(Change::$rightname, Change::READALL)) {
         $foruser = true;
      }

      $output = [];

      $query = "SELECT `status`,
                       COUNT(*) AS COUNT
                FROM `glpi_changes` ";

      if ($foruser) {
         $query .= " LEFT JOIN `glpi_changes_users`
                        ON (`glpi_changes`.`id` = `glpi_changes_users`.`changes_id`
                            AND `glpi_changes_users`.`type` = '" . CommonITILActor::REQUESTER . "')";

         if (isset($_SESSION["glpigroups"])
             && count($_SESSION["glpigroups"])
         ) {
            $query .= " LEFT JOIN `glpi_changes_groups`
                           ON (`glpi_changes`.`id` = `glpi_changes_groups`.`changes_id`
                               AND `glpi_changes_groups`.`type` = '" . CommonITILActor::REQUESTER . "')";
         }
      }
      $dbu   = new DbUtils();
      $query .= $dbu->getEntitiesRestrictRequest("WHERE", "glpi_changes");

      if ($foruser) {
         $query .= " AND (`glpi_changes_users`.`users_id` = '" . Session::getLoginUserID() . "' ";

         if (isset($_SESSION["glpigroups"])
             && count($_SESSION["glpigroups"])
         ) {
            $groups = implode("','", $_SESSION['glpigroups']);
            $query  .= " OR `glpi_changes_groups`.`groups_id` IN ('$groups') ";
         }
         $query .= ")";
      }
      $query_deleted = $query;

      $query         .= " AND NOT `glpi_changes`.`is_deleted`
                         GROUP BY `status`";
      $query_deleted .= " AND `glpi_changes`.`is_deleted`
                         GROUP BY `status`";

      $result         = $DB->query($query);
      $result_deleted = $DB->query($query_deleted);

      $status = [];
      foreach (Change::getAllStatusArray() as $key => $val) {
         $status[$key] = 0;
      }

      if ($DB->numrows($result) > 0) {
         while ($data = $DB->fetchAssoc($result)) {
            $status[$data["status"]] = $data["COUNT"];
         }
      }

      $number_deleted = 0;
      if ($DB->numrows($result_deleted) > 0) {
         while ($data = $DB->fetchAssoc($result_deleted)) {
            $number_deleted += $data["COUNT"];
         }
      }
      $options['field'][0]      = 12;
      $options['searchtype'][0] = 'equals';
      $options['contains'][0]   = 'process';
      $options['link'][0]       = 'AND';
      $options['reset']         = 'reset';

      $output['title'] = "<a style=\"font-size:14px;\" href=\"" . $CFG_GLPI["root_doc"] . "/front/change.php?" .
                         Toolbox::append_params($options, '&amp;') . "\">" . __('Change followup', 'mydashboard') . "</a>";

      $output['header'][] = _n('Change', 'Changes', 2);
      $output['header'][] = _x('quantity', 'Number');

      $count = 0;
      foreach ($status as $key => $val) {
         $options['contains'][0]    = $key;
         $output['body'][$count][0] = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/change.php?" .
                                      Toolbox::append_params($options, '&amp;') . "\">" . Change::getStatus($key) . "</a>";
         $output['body'][$count][1] = $val;
         $count++;
      }

      $options['contains'][0]    = 'all';
      $options['is_deleted']     = 1;
      $output['body'][$count][0] = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/change.php?" .
                                   Toolbox::append_params($options, '&amp;') . "\">" . __('Deleted') . "</a>";
      $output['body'][$count][1] = $number_deleted;

      $widget = new PluginMydashboardDatatable();
      $widget->setWidgetTitle($output['title']);
      $widget->setWidgetId("changecountwidget");
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
