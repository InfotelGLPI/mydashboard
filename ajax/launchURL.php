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

include("../../../inc/includes.php");

Session::checkLoginUser();

global $CFG_GLPI;

// Reset criterias
$options['reset'][] = 'reset';

if (isset($_POST["widget"])
    && $_POST["widget"] == "PluginOcsinventoryngDashboard1") {
   if (isset($_POST["dateinv"])) {

      $options['criteria'][] = [
         'field'      => 10002,// last inv
         'searchtype' => 'contains',
         'value'      => $_POST["dateinv"],
         'link'       => 'AND'
      ];

      $link = $CFG_GLPI["root_doc"] . '/front/computer.php?' .
              Toolbox::append_params($options, "&");
      echo $link;
   }
} else if (isset($_POST["widget"])
           && $_POST["widget"] == "PluginMydashboardInfotel1") {
   //$criterias = ['entities_id', 'is_recursive', 'technicians_groups_id', 'type'];
   if (isset($_POST["datetik"])) {

      $options['criteria'][] = [
         'field'      => 12,// status inv
         'searchtype' => 'equals',
         'value'      => 'notold',
         'link'       => 'AND',
      ];

      $options['criteria'][] = [
         'field'      => 15, // open date
         'searchtype' => 'contains',
         'value'      => $_POST["datetik"],
         'link'       => 'AND',
      ];

      if (!empty($_POST["requester_groups"])) {
         $requester_groups = $_POST["requester_groups"];
         $nb               = 0;
         foreach ($requester_groups as $requester_group) {

            $criterias['criteria'][$nb] = [
               'field'      => 71, // requester_group
               'searchtype' => 'equals',
               'value'      => $requester_group,
               'link'       => (($nb == 0) ? 'AND' : 'OR'),
            ];
            $nb++;
         }
         $options['criteria'][] = $criterias;
      }

      if (!empty($_POST["technician_group"])) {
         $groups = $_POST["technician_group"];
         $nb     = 0;
         foreach ($groups as $group) {

            $criterias['criteria'][$nb] = [
               'field'      => 8, // groups_id_assign
               'searchtype' => ((isset($_POST["group_is_recursive"]) && !empty($_POST["group_is_recursive"])) ? 'under' : 'equals'),
               'value'      => $group,
               'link'       => (($nb == 0) ? 'AND' : 'OR'),
            ];
            $nb++;
         }
         $options['criteria'][] = $criterias;
      }

      if ($_POST["type"] > 0) {
         $options['criteria'][] = [
            'field'      => 14, // type
            'searchtype' => 'equals',
            'value'      => $_POST["type"],
            'link'       => 'AND',
         ];
      }

      $options['criteria'][] = [
         'field'      => 80, // entities
         'searchtype' => ((isset($_POST["sons"]) && $_POST["sons"] > 0) ? 'under' : 'equals'),
         'value'      => $_POST["entities_id"],
         'link'       => 'AND',
      ];

      $link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
              Toolbox::append_params($options, "&");
      echo $link;
   }
} else if (isset($_POST["widget"])
           && $_POST["widget"] == "PluginMydashboardInfotel2") {
   //   $criterias = ['type'];
   if (isset($_POST["priority_id"])) {
      $options['criteria'][] = [
         'field'      => 12, // status
         'searchtype' => 'equals',
         'value'      => 'notold',
         'link'       => 'AND',
      ];

      $options['criteria'][] = [
         'field'      => 3, // priority
         'searchtype' => 'equals',
         'value'      => $_POST["priority_id"],
         'link'       => 'AND',
      ];

      if ($_POST["type"] > 0) {
         $options['criteria'][] = [
            'field'      => 14, // type
            'searchtype' => 'equals',
            'value'      => $_POST["type"],
            'link'       => 'AND',
         ];
      }

      $options['criteria'][] = [
         'field'      => 80, // entities
         'searchtype' => ((isset($_POST["sons"]) && $_POST["sons"] > 0) ? 'under' : 'equals'),
         'value'      => $_POST["entities_id"],
         'link'       => 'AND',
      ];

      if (!empty($_POST["technician_group"])) {
         $groups = $_POST["technician_group"];
         $nb     = 0;
         foreach ($groups as $group) {

            $criterias['criteria'][$nb] = [
               'field'      => 8, //groups_id_assign
               'searchtype' => ((isset($_POST["group_is_recursive"]) && !empty($_POST["group_is_recursive"])) ? 'under' : 'equals'),
               'value'      => $group,
               'link'       => (($nb == 0) ? 'AND' : 'OR'),
            ];
            $nb++;
         }
         $options['criteria'][] = $criterias;
      }

      $link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
              Toolbox::append_params($options, "&");
      echo $link;
   }
} else if (isset($_POST["widget"])
           && $_POST["widget"] == "PluginMydashboardInfotel25") {
   //    $criterias = ['type'];
   //requester groups;
   if (isset($_POST["groups_id"])) {

      $options['criteria'][] = [
         'field'      => 12, // status
         'searchtype' => 'equals',
         'value'      => 'notold',
         'link'       => 'AND',
      ];

      $options['criteria'][] = [
         'field'      => 71, // requester_group
         'searchtype' => ((empty($_POST["groups_id"])) ? 'contains' : 'equals'),
         'value'      => ((empty($_POST["groups_id"])) ? '^$' : $_POST["groups_id"]),
         'link'       => 'AND',
      ];


      if ($_POST["type"] > 0) {
         $options['criteria'][] = [
            'field'      => 14, // type
            'searchtype' => 'equals',
            'value'      => $_POST["type"],
            'link'       => 'AND',
         ];
      }
      $link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
              Toolbox::append_params($options, "&");
      echo $link;
   }
} else if (isset($_POST["widget"])
           && ($_POST["widget"] == "PluginMydashboardInfotel16"
               || $_POST["widget"] == "PluginMydashboardInfotel17")) {
   //$criterias = ['entities_id', 'is_recursive', 'technicians_groups_id'];
   if (isset($_POST["category_id"])) {
      $options['criteria'][] = [
         'field'      => 12, // status
         'searchtype' => 'equals',
         'value'      => 'notold',
         'link'       => 'AND',
      ];

      $options['criteria'][] = [
         'field'      => 14, // type
         'searchtype' => 'equals',
         'value'      => (($_POST["widget"] == "PluginMydashboardInfotel16") ? Ticket::INCIDENT_TYPE : Ticket::DEMAND_TYPE),
         'link'       => 'AND',
      ];

      $options['criteria'][] = [
         'field'      => 7, // category
         'searchtype' => ((empty($_POST["category_id"])) ? 'contains' : 'equals'),
         'value'      => ((empty($_POST["category_id"])) ? '^$' : $_POST["category_id"]),
         'link'       => 'AND',
      ];

      if (!empty($_POST["technician_group"])) {
         $groups = $_POST["technician_group"];
         $nb     = 0;
         foreach ($groups as $group) {

            $criterias['criteria'][$nb] = [
               'field'      => 8, // technician_group
               'searchtype' => ((isset($_POST["group_is_recursive"]) && !empty($_POST["group_is_recursive"])) ? 'under' : 'equals'),
               'value'      => $group,
               'link'       => (($nb == 0) ? 'AND' : 'OR'),
            ];
            $nb++;
         }
         $options['criteria'][] = $criterias;
      }

      $options['criteria'][] = [
         'field'      => 80, // entities
         'searchtype' => ((isset($_POST["sons"]) && $_POST["sons"] > 0) ? 'under' : 'equals'),
         'value'      => $_POST["entities_id"],
         'link'       => 'AND',
      ];

      $link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
              Toolbox::append_params($options, "&");
      echo $link;
   }
} else if (isset($_POST["widget"])
           && $_POST["widget"] == "PluginMydashboardInfotel24") {
   //$criterias = ['entities_id', 'is_recursive', 'year', 'type'];
   if (isset($_POST["techtik"])) {

      $options['criteria'][] = [
         'field'      => 5, // tech
         'searchtype' => (($_POST["techtik"] == -1) ? 'contains' : 'equals'),
         'value'      => (($_POST["techtik"] == -1) ? '^$' : $_POST["techtik"]),
         'link'       => 'AND',
      ];

      $options['criteria'][] = [
         'field'      => 15, // open date
         'searchtype' => 'contains',
         'value'      => $_POST["year"],
         'link'       => 'AND',
      ];

      if ($_POST["type"] > 0) {
         $options['criteria'][] = [
            'field'      => 14, // type
            'searchtype' => 'equals',
            'value'      => $_POST["type"],
            'link'       => 'AND',
         ];
      }

      $options['criteria'][] = [
         'field'      => 80, // entities
         'searchtype' => ((isset($_POST["sons"]) && $_POST["sons"] > 0) ? 'under' : 'equals'),
         'value'      => $_POST["entities_id"],
         'link'       => 'AND',
      ];

      $link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
              Toolbox::append_params($options, "&");
      echo $link;
   }
} else if (isset($_POST["widget"])
           && $_POST["widget"] == "PluginMydashboardInfotel27") {
   //   $criterias = ['entities_id', 'is_recursive','type'];
   if (isset($_POST["locations_id"])) {

      $options['criteria'][] = [
         'field'      => 12, // status
         'searchtype' => 'equals',
         'value'      => 'notold',
         'link'       => 'AND',
      ];

      $options['criteria'][] = [
         'field'      => 83, // location
         'searchtype' => ((empty($_POST["locations_id"])) ? 'contains' : 'equals'),
         'value'      => ((empty($_POST["locations_id"])) ? '^$' : $_POST["locations_id"]),
         'link'       => 'AND',
      ];

      if ($_POST["type"] > 0) {
         $options['criteria'][] = [
            'field'      => 14, // type
            'searchtype' => 'equals',
            'value'      => $_POST["type"],
            'link'       => 'AND',
         ];
      }

      $options['criteria'][] = [
         'field'      => 80, // entities
         'searchtype' => ((isset($_POST["sons"]) && $_POST["sons"] > 0) ? 'under' : 'equals'),
         'value'      => $_POST["entities_id"],
         'link'       => 'AND',
      ];

      if (!empty($_POST["technician_group"])) {
         $groups = $_POST["technician_group"];
         $nb     = 0;
         foreach ($groups as $group) {

            $criterias['criteria'][$nb] = [
               'field'      => 8, // groups_id_assign
               'searchtype' => ((isset($_POST["group_is_recursive"]) && !empty($_POST["group_is_recursive"])) ? 'under' : 'equals'),
               'value'      => $group,
               'link'       => (($nb == 0) ? 'AND' : 'OR'),
            ];
            $nb++;
         }
         $options['criteria'][] = $criterias;
      }

      $link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
              Toolbox::append_params($options, "&");
      echo $link;
   }
} else if (isset($_POST["widget"])
           && $_POST["widget"] == "PluginMydashboardInfotel32") {

   // ENTITY | SONS
   $options['criteria'][] = [
      'field'      => 80,
      'searchtype' => (isset($_POST["sons"]) && $_POST["sons"] > 0) ? 'under' : 'equals',
      'value'      => $_POST["entities_id"],
      'link'       => 'AND'
   ];

   // USER
   if (isset($_POST["technician"])) {
      $options['criteria'][] = [
         'field'      => 5,
         'searchtype' => 'equals',
         'value'      => $_POST["technician"],
         'link'       => 'AND'
      ];
   }

   // STATUS
   if ($_POST['moreticket'] == 1) {
      $options['criteria'][] = [
         'field'      => 3452,
         'searchtype' => 'equals',
         'value'      => $_POST["status"],
         'link'       => 'AND'
      ];
   } else {
      $options['criteria'][] = [
         'field'      => 12,
         'searchtype' => 'equals',
         'value'      => $_POST["status"],
         'link'       => 'AND'
      ];
   }

   echo $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
        Toolbox::append_params($options, "&");
} else if (isset($_POST["widget"])
           && $_POST["widget"] == "PluginMydashboardInfotel33") {

   // ENTITY | SONS
   $options['criteria'][] = [
      'field'      => 80,
      'searchtype' => (isset($_POST["sons"]) && $_POST["sons"] > 0) ? 'under' : 'equals',
      'value'      => $_POST["entities_id"],
      'link'       => 'AND'
   ];

   // STATUS
   if ($_POST['moreticket'] == 1) {
      $options['criteria'][] = [
         'field'      => 3452,
         'searchtype' => 'equals',
         'value'      => $_POST["status"],
         'link'       => 'AND'
      ];
   } else {
      $options['criteria'][] = [
         'field'      => 12,
         'searchtype' => 'equals',
         'value'      => $_POST["status"],
         'link'       => 'AND'
      ];
   }

   // Group
   if (!empty($_POST["technician_group"])) {
      $groups = $_POST["technician_group"];
      $nb     = 0;
      foreach ($groups as $group) {

         $criterias['criteria'][$nb] = [
            'field'      => 8, // groups_id_assign
            'searchtype' => ((isset($_POST["group_is_recursive"]) && !empty($_POST["group_is_recursive"])) ? 'under' : 'equals'),
            'value'      => $group,
            'link'       => (($nb == 0) ? 'AND' : 'OR'),
         ];
         $nb++;
      }
      $options['criteria'][] = $criterias;
   }

   echo $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
        Toolbox::append_params($options, "&");
}
