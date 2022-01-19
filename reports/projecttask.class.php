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
 * This class extends GLPI class project to add the functions to display a widget on Dashboard
 */
class PluginMydashboardProjectTask extends CommonGLPI {

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
      $widgets         = [];
      $showprojecttask = Session::haveRight('projecttask', ProjectTask::READMY);

      if ($showprojecttask) {

         $widgets = [
            PluginMydashboardMenu::$PROJECT_VIEW =>
               [
                  "projecttaskprocesswidget" => ["title"   => __('projects tasks to be processed', 'mydashboard'),
                                                 "icon"    => "ti ti-table",
                                                 "comment" => ""],
               ],
            PluginMydashboardMenu::$GROUP_VIEW   =>
               [
                  "projectprocesswidgetgroup" => ["title"   => __('projects tasks to be processed', 'mydashboard'),
                                                  "icon"    => "ti ti-table",
                                                  "comment" => ""],
               ]
         ];
      }
      return $widgets;
   }

   /**
    * @param $widgetId
    *
    * @return PluginMydashboardDatatable
    */
   function getWidgetContentForItem($widgetId) {
      $showprojecttask = Session::haveRightsOr('projecttask', [ProjectTask::READMY]);

      if ($showprojecttask) {
         switch ($widgetId) {
            case "projecttaskprocesswidget":
               return self::showCentralList(0, "process", false);
               break;
            case "projecttaskprocesswidgetgroup":
               return self::showCentralList(0, "process", true);
               break;
         }
      }
   }

   /**
    * @param        $start
    * @param string $status
    * @param bool   $showgroupprojecttasks
    *
    * @return PluginMydashboardDatatable
    */
   static function showCentralList($start, $status = "process", $showgroupprojecttasks = true) {
      global $DB, $CFG_GLPI;

      $output = [];
      //We declare our new widget
      $widget = new PluginMydashboardDatatable();
      if ($status == "process") {
         $widget->setWidgetTitle(Html::makeTitle(__('projects tasks to be processed', 'mydashboard'), 0, 0));
      }
      $group = ($showgroupprojecttasks) ? "group" : "";
      $widget->setWidgetId("projecttask" . $status . "widget" . $group);
      //Here we set few otions concerning the jquery library Datatable, bPaginate for paginating ...
      $widget->setOption("bPaginate", false);
      $widget->setOption("bFilter", false);
      $widget->setOption("bInfo", false);

      if (!Session::haveRightsOr('projecttask', [ProjectTask::READMY])) {
         return false;
      }

      $search_assign = " (`glpi_projecttasks`.`users_id`= '" . Session::getLoginUserID() . "')";
      $search_assign .= " OR (`glpi_projecttaskteams`.`items_id` = '" . Session::getLoginUserID() . "'
                            AND `glpi_projecttaskteams`.`itemtype` = 'User')";

      if ($showgroupprojecttasks) {
         if (count($_SESSION['glpigroups'])) {
            $groups = implode("','", $_SESSION['glpigroups']);

            $search_assign = " (`glpi_projecttaskteams`.`items_id` IN ('$groups')
                                     AND `glpi_projecttaskteams`.`itemtype` = 'Group') ";
         }
      }
      $dbu   = new DbUtils();
      $query = "SELECT DISTINCT `glpi_projecttasks`.`id`
                FROM `glpi_projecttasks`
                LEFT JOIN `glpi_projecttaskteams`
                     ON (`glpi_projecttasks`.`id` = `glpi_projecttaskteams`.`projecttasks_id`)
                LEFT JOIN glpi_projectstates
                     ON glpi_projecttasks.projectstates_id = glpi_projectstates.id";

      switch ($status) {
         case "process" : // on affiche les projets assignés au user
            $query .= " WHERE ($search_assign) 
                      AND (glpi_projectstates.is_finished = 0  OR glpi_projecttasks.projectstates_id = 0)";
            $dbu->getEntitiesRestrictRequest("AND", "glpi_projects");
            break;
      }

      $query .= " ORDER BY glpi_projecttasks.date_mod DESC";

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
         if ($showgroupprojecttasks) {
            switch ($status) {

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
                  $output['title'] .= "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/projecttask.php?" .
                                      Toolbox::append_params($options, '&amp;') . "\">" .
                                      Html::makeTitle(__('projects tasks to be processed', 'mydashboard'), $number, $numrows) . "</a>";
                  break;

            }
         } else {
            switch ($status) {

               case "process" :
                  $options['field'][0]      = 5; // users_id_assign
                  $options['searchtype'][0] = 'equals';
                  $options['contains'][0]   = Session::getLoginUserID();
                  $options['link'][0]       = 'AND';

                  $options['field'][1]      = 12; // status
                  $options['searchtype'][1] = 'equals';
                  $options['contains'][1]   = 'process';
                  $options['link'][1]       = 'AND';

                  $output['title'] .= "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/projecttask.php?" .
                                      Toolbox::append_params($options, '&amp;') . "\">" .
                                      Html::makeTitle(__('projects tasks to be processed', 'mydashboard'), $number, $numrows) . "</a>";
                  break;
            }
         }

         if ($number) {
            $output['header'][] = __('ID');
            $output['header'][] = __('Requester');
            $output['header'][] = __('Description');
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

      $projecttask = new ProjectTask();
      $rand        = mt_rand();

      if ($projecttask->getFromDB($ID)) {
         $project = new Project();
         $project->getFromDB($projecttask->fields["projects_id"]);
         $bgcolor = $_SESSION["glpipriority_" . $project->fields["priority"]];
         //      $rand    = mt_rand();
         $output[$colnum] = "<div class='center' style='background-color:$bgcolor; padding: 10px;'>" . sprintf(__('%1$s: %2$s'), __('ID'),
                                                                                                               $projecttask->fields["id"]) . "</div>";
         $colnum++;

         $output[$colnum]    = '';
         $projecttasksFields = $projecttask->fields;
         if (isset($projecttasksFields["users_id"])) {
            if ($projecttasksFields["users_id"] > 0) {
               $userdata = getUserName($projecttasksFields["users_id"], 2);
               $name     = "<div class='b center'>" . $userdata['name'];
               if ($viewusers) {
                  $name = sprintf(__('%1$s %2$s'), $name,
                                  Html::showToolTip($userdata["comment"],
                                                    ['link'    => $userdata["link"],
                                                     'display' => false]));
               }
               $output[$colnum] .= $name . "</div>";
            }
         }

         if (isset($projecttasksFields["groups_id"])
             && $projecttasksFields["groups_id"] != 0
         ) {
            $output[$colnum] .= Dropdown::getDropdownName("glpi_groups", $projecttasksFields["groups_id"]);
         }

         $colnum++;

         $link = "<a id='projecttask" . $projecttask->fields["id"] . $rand . "' href='" . $CFG_GLPI["root_doc"] .
                 "/front/projecttask.form.php?id=" . $projecttask->fields["id"];
         if ($forcetab != '') {
            $link .= "&amp;forcetab=" . $forcetab;
         }

         $link .= "'>";
         $link .= "<span class='b'>" . $projecttask->fields["name"] . "</span></a>";

         $link = sprintf(__('%1$s %2$s'), $link,
                         Html::showToolTip($projecttask->fields['content'],
                                           ['applyto' => 'projecttask' . $projecttask->fields["id"] . $rand,
                                            'display' => false]));
         //echo $link;
         //$colnum++;
         $output[$colnum] = $link;
      }
      return $output;
   }

}
