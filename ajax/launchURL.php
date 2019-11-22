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

//Toolbox::logWarning($_POST);
// Reset criterias
$options['reset'][] = 'reset';
if (isset($_POST["params"]["technician_group"])) {
   $_POST["params"]["technician_group"] = is_array($_POST["params"]["technician_group"]) ? $_POST["params"]["technician_group"] : [$_POST["params"]["technician_group"]];
}

if (isset($_POST["params"]["requester_groups"])) {
   $_POST["params"]["requester_groups"] = is_array($_POST["params"]["requester_groups"]) ? $_POST["params"]["requester_groups"] : [$_POST["params"]["requester_groups"]];
}

if (isset($_POST["params"]["widget"])
    && $_POST["params"]["widget"] == "PluginOcsinventoryngDashboard1") {
   if (isset($_POST["params"]["dateinv"])) {

      $options['criteria'][] = [
         'field'      => 10002,// last inv
         'searchtype' => 'contains',
         'value'      => $_POST["params"]["dateinv"],
         'link'       => 'AND'
      ];

      $link = $CFG_GLPI["root_doc"] . '/front/computer.php?' .
              Toolbox::append_params($options, "&");
      echo $link;
   }
} else if (isset($_POST["params"]["widget"])
           && $_POST["params"]["widget"] == "PluginMydashboardInfotel1") {
   //$criterias = ['entities_id', 'is_recursive', 'technicians_groups_id', 'type'];
   if (isset($_POST["selected_id"])) {

      $options['criteria'][] = [
         'field'      => 12,// status inv
         'searchtype' => 'equals',
         'value'      => 'notold',
         'link'       => 'AND',
      ];

      $options['criteria'][] = [
         'field'      => 15, // open date
         'searchtype' => 'contains',
         'value'      => $_POST["selected_id"],
         'link'       => 'AND',
      ];

      if (isset($_POST["params"]["requester_groups"])
          && count($_POST["params"]["requester_groups"]) > 0) {
         $requester_groups = $_POST["params"]["requester_groups"];
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

      if (isset($_POST["params"]["technician_group"])
          && count($_POST["params"]["technician_group"]) > 0) {
         $groups    = $_POST["params"]["technician_group"];
         $nb        = 0;
         $criterias = [];
         foreach ($groups as $group) {

            $criterias['criteria'][$nb] = [
               'field'      => 8, // groups_id_assign
               'searchtype' => ((isset($_POST["params"]["group_is_recursive"]) && !empty($_POST["params"]["group_is_recursive"])) ? 'under' : 'equals'),
               'value'      => $group,
               'link'       => (($nb == 0) ? 'AND' : 'OR'),
            ];
            $nb++;
         }
         $options['criteria'][] = $criterias;
      }

      if ($_POST["params"]["type"] > 0) {
         $options['criteria'][] = [
            'field'      => 14, // type
            'searchtype' => 'equals',
            'value'      => $_POST["params"]["type"],
            'link'       => 'AND',
         ];
      }

      $options['criteria'][] = [
         'field'      => 80, // entities
         'searchtype' => ((isset($_POST["params"]["sons"]) && $_POST["params"]["sons"] > 0) ? 'under' : 'equals'),
         'value'      => $_POST["params"]["entities_id"],
         'link'       => 'AND',
      ];

      $link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
              Toolbox::append_params($options, "&");
      echo $link;
   }
} else if (isset($_POST["params"]["widget"])
           && ($_POST["params"]["widget"] == "PluginMydashboardInfotel2"
               || $_POST["params"]["widget"] == "PluginMydashboardInfotel36")) {


   if (isset($_POST["selected_id"])) {
      $options['criteria'][] = [
         'field'      => 12, // status
         'searchtype' => 'equals',
         'value'      => 'notold',
         'link'       => 'AND',
      ];

      $options['criteria'][] = [
         'field'      => 3, // priority
         'searchtype' => 'equals',
         'value'      => $_POST["selected_id"],
         'link'       => 'AND',
      ];

      if ($_POST["params"]["type"] > 0) {
         $options['criteria'][] = [
            'field'      => 14, // type
            'searchtype' => 'equals',
            'value'      => $_POST["params"]["type"],
            'link'       => 'AND',
         ];
      }

      $options['criteria'][] = [
         'field'      => 80, // entities
         'searchtype' => ((isset($_POST["params"]["sons"]) && $_POST["params"]["sons"] > 0) ? 'under' : 'equals'),
         'value'      => $_POST["params"]["entities_id"],
         'link'       => 'AND',
      ];

      if (isset($_POST["params"]["technician_group"])
          && count($_POST["params"]["technician_group"]) > 0) {
         $groups = $_POST["params"]["technician_group"];
         $nb     = 0;
         foreach ($groups as $group) {

            $criterias['criteria'][$nb] = [
               'field'      => 8, //groups_id_assign
               'searchtype' => ((isset($_POST["params"]["group_is_recursive"]) && !empty($_POST["params"]["group_is_recursive"])) ? 'under' : 'equals'),
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
} else if (isset($_POST["params"]["widget"])
           && $_POST["params"]["widget"] == "PluginMydashboardInfotel25") {
   //    $criterias = ['type'];
   //requester groups;
   if (isset($_POST["selected_id"])) {

      $options['criteria'][] = [
         'field'      => 12, // status
         'searchtype' => 'equals',
         'value'      => 'notold',
         'link'       => 'AND',
      ];

      $options['criteria'][] = [
         'field'      => 71, // requester_group
         'searchtype' => ((empty($_POST["selected_id"])) ? 'contains' : 'equals'),
         'value'      => ((empty($_POST["selected_id"])) ? '^$' : $_POST["selected_id"]),
         'link'       => 'AND',
      ];


      if ($_POST["params"]["type"] > 0) {
         $options['criteria'][] = [
            'field'      => 14, // type
            'searchtype' => 'equals',
            'value'      => $_POST["params"]["type"],
            'link'       => 'AND',
         ];
      }
      $link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
              Toolbox::append_params($options, "&");
      echo $link;
   }
} else if (isset($_POST["params"]["widget"])
           && ($_POST["params"]["widget"] == "PluginMydashboardInfotel16"
               || $_POST["params"]["widget"] == "PluginMydashboardInfotel17")) {
   //$criterias = ['entities_id', 'is_recursive', 'technicians_groups_id'];
   if (isset($_POST["selected_id"])) {
      $options['criteria'][] = [
         'field'      => 12, // status
         'searchtype' => 'equals',
         'value'      => 'notold',
         'link'       => 'AND',
      ];

      $options['criteria'][] = [
         'field'      => 14, // type
         'searchtype' => 'equals',
         'value'      => (($_POST["params"]["widget"] == "PluginMydashboardInfotel16") ? Ticket::INCIDENT_TYPE : Ticket::DEMAND_TYPE),
         'link'       => 'AND',
      ];

      $options['criteria'][] = [
         'field'      => 7, // category
         'searchtype' => ((empty($_POST["selected_id"])) ? 'contains' : 'equals'),
         'value'      => ((empty($_POST["selected_id"])) ? '^$' : $_POST["selected_id"]),
         'link'       => 'AND',
      ];

      if (isset($_POST["params"]["technician_group"])
          && count($_POST["params"]["technician_group"]) > 0) {
         $groups = $_POST["params"]["technician_group"];
         $nb     = 0;
         foreach ($groups as $group) {

            $criterias['criteria'][$nb] = [
               'field'      => 8, // technician_group
               'searchtype' => ((isset($_POST["params"]["group_is_recursive"]) && !empty($_POST["params"]["group_is_recursive"])) ? 'under' : 'equals'),
               'value'      => $group,
               'link'       => (($nb == 0) ? 'AND' : 'OR'),
            ];
            $nb++;
         }
         $options['criteria'][] = $criterias;
      }

      if (isset($_POST["params"]["requester_groups"])
          && count($_POST["params"]["requester_groups"]) > 0) {
         $requester_groups = $_POST["params"]["requester_groups"];
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

      $options['criteria'][] = [
         'field'      => 80, // entities
         'searchtype' => ((isset($_POST["params"]["sons"]) && $_POST["params"]["sons"] > 0) ? 'under' : 'equals'),
         'value'      => $_POST["params"]["entities_id"],
         'link'       => 'AND',
      ];

      $link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
              Toolbox::append_params($options, "&");
      echo $link;
   }
} else if (isset($_POST["params"]["widget"])
           && $_POST["params"]["widget"] == "PluginMydashboardInfotel24") {
   //$criterias = ['entities_id', 'is_recursive', 'year', 'type'];
   if (isset($_POST["selected_id"])) {

      $options['criteria'][] = [
         'field'      => 5, // tech
         'searchtype' => (($_POST["selected_id"] == -1) ? 'contains' : 'equals'),
         'value'      => (($_POST["selected_id"] == -1) ? '^$' : $_POST["selected_id"]),
         'link'       => 'AND',
      ];

      $options['criteria'][] = [
         'field'      => 15, // open date
         'searchtype' => 'contains',
         'value'      => $_POST["params"]["year"],
         'link'       => 'AND',
      ];

      if ($_POST["params"]["type"] > 0) {
         $options['criteria'][] = [
            'field'      => 14, // type
            'searchtype' => 'equals',
            'value'      => $_POST["params"]["type"],
            'link'       => 'AND',
         ];
      }

      $options['criteria'][] = [
         'field'      => 80, // entities
         'searchtype' => ((isset($_POST["params"]["sons"]) && $_POST["params"]["sons"] > 0) ? 'under' : 'equals'),
         'value'      => $_POST["params"]["entities_id"],
         'link'       => 'AND',
      ];

      $link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
              Toolbox::append_params($options, "&");
      echo $link;
   }
} else if (isset($_POST["params"]["widget"])
           && $_POST["params"]["widget"] == "PluginMydashboardInfotel27") {
   //   $criterias = ['entities_id', 'is_recursive','type'];
   if (isset($_POST["selected_id"])) {

      $options['criteria'][] = [
         'field'      => 12, // status
         'searchtype' => 'equals',
         'value'      => 'notold',
         'link'       => 'AND',
      ];

      $options['criteria'][] = [
         'field'      => 83, // location
         'searchtype' => ((empty($_POST["selected_id"])) ? 'contains' : 'equals'),
         'value'      => ((empty($_POST["selected_id"])) ? '^$' : $_POST["selected_id"]),
         'link'       => 'AND',
      ];

      if ($_POST["params"]["type"] > 0) {
         $options['criteria'][] = [
            'field'      => 14, // type
            'searchtype' => 'equals',
            'value'      => $_POST["params"]["type"],
            'link'       => 'AND',
         ];
      }

      $options['criteria'][] = [
         'field'      => 80, // entities
         'searchtype' => ((isset($_POST["params"]["sons"]) && $_POST["params"]["sons"] > 0) ? 'under' : 'equals'),
         'value'      => $_POST["params"]["entities_id"],
         'link'       => 'AND',
      ];

      if (isset($_POST["params"]["technician_group"])
          && count($_POST["params"]["technician_group"]) > 0) {
         $groups = $_POST["params"]["technician_group"];
         $nb     = 0;
         foreach ($groups as $group) {

            $criterias['criteria'][$nb] = [
               'field'      => 8, // groups_id_assign
               'searchtype' => ((isset($_POST["params"]["group_is_recursive"]) && !empty($_POST["params"]["group_is_recursive"])) ? 'under' : 'equals'),
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
} else if (isset($_POST["params"]["widget"])
           && $_POST["params"]["widget"] == "PluginMydashboardInfotel32") {

   // ENTITY | SONS
   $options['criteria'][] = [
      'field'      => 80,
      'searchtype' => (isset($_POST["params"]["sons"]) && $_POST["params"]["sons"] > 0) ? 'under' : 'equals',
      'value'      => $_POST["params"]["entities_id"],
      'link'       => 'AND'
   ];

   // USER
   if (isset($_POST["params"]["technician"])) {
      $options['criteria'][] = [
         'field'      => 5,
         'searchtype' => 'equals',
         'value'      => $_POST["params"]["technician"],
         'link'       => 'AND'
      ];
   }

   // STATUS
   if ($_POST["params"]['moreticket'] == 1) {
      $options['criteria'][] = [
         'field'      => 3452,
         'searchtype' => 'equals',
         'value'      => $_POST["params"]["status"],
         'link'       => 'AND'
      ];
   } else {
      $options['criteria'][] = [
         'field'      => 12,
         'searchtype' => 'equals',
         'value'      => $_POST["params"]["status"],
         'link'       => 'AND'
      ];
   }

   echo $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
        Toolbox::append_params($options, "&");
} else if (isset($_POST["params"]["widget"])
           && $_POST["params"]["widget"] == "PluginMydashboardInfotel33") {

   // ENTITY | SONS
   $options['criteria'][] = [
      'field'      => 80,
      'searchtype' => (isset($_POST["params"]["sons"]) && $_POST["params"]["sons"] > 0) ? 'under' : 'equals',
      'value'      => $_POST["params"]["entities_id"],
      'link'       => 'AND'
   ];

   // STATUS
   if ($_POST["params"]['moreticket'] == 1) {
      $options['criteria'][] = [
         'field'      => 3452,
         'searchtype' => 'equals',
         'value'      => $_POST["params"]["status"],
         'link'       => 'AND'
      ];
   } else {
      $options['criteria'][] = [
         'field'      => 12,
         'searchtype' => 'equals',
         'value'      => $_POST["params"]["status"],
         'link'       => 'AND'
      ];
   }

   // Group
   if (isset($_POST["params"]["technician_group"])
       && count($_POST["params"]["technician_group"]) > 0) {
      $groups = $_POST["params"]["technician_group"];
      $nb     = 0;
      foreach ($groups as $group) {

         $criterias['criteria'][$nb] = [
            'field'      => 8, // groups_id_assign
            'searchtype' => ((isset($_POST["params"]["group_is_recursive"]) && !empty($_POST["params"]["group_is_recursive"])) ? 'under' : 'equals'),
            'value'      => $group,
            'link'       => (($nb == 0) ? 'AND' : 'OR'),
         ];
         $nb++;
      }
      $options['criteria'][] = $criterias;
   }

   echo $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
        Toolbox::append_params($options, "&");
} else if (isset($_POST["params"]["widget"])
           && $_POST["params"]["widget"] == "PluginMydashboardInfotel37") {

   // ENTITY | SONS
   $options['criteria'][] = [
      'field'      => 80,
      'searchtype' => (isset($_POST["params"]["sons"]) && $_POST["params"]["sons"] > 0) ? 'under' : 'equals',
      'value'      => $_POST["params"]["entities_id"],
      'link'       => 'AND'
   ];

   if ($_POST["params"]["type"] > 0) {
      $options['criteria'][] = [
         'field'      => 14, // type
         'searchtype' => 'equals',
         'value'      => $_POST["params"]["type"],
         'link'       => 'AND',
      ];
   }

   // STATUS
   if (strpos($_POST["selected_id"], 'moreticket_') !== false) {
      $status = explode("_", $_POST["selected_id"]);
      $options['criteria'][] = [
         'field'      => 12,
         'searchtype' => 'equals',
         'value'      => Ticket::WAITING,
         'link'       => 'AND'
      ];

      $options['criteria'][] = [
         'field'      => 3452,
         'searchtype' => 'equals',
         'value'      => $status[1],
         'link'       => 'AND'
      ];
   } else {
      $options['criteria'][] = [
         'field'      => 12,
         'searchtype' => 'equals',
         'value'      => $_POST["selected_id"],
         'link'       => 'AND'
      ];
   }

   // Group
   if (isset($_POST["params"]["technician_group"])
       && count($_POST["params"]["technician_group"]) > 0) {
      $groups = $_POST["params"]["technician_group"];
      $nb     = 0;
      foreach ($groups as $group) {

         $criterias['criteria'][$nb] = [
            'field'      => 8, // groups_id_assign
            'searchtype' => ((isset($_POST["params"]["group_is_recursive"]) && !empty($_POST["params"]["group_is_recursive"])) ? 'under' : 'equals'),
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
