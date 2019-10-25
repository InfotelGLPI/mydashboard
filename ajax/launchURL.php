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

if (isset($_POST["widget"])
    && $_POST["widget"] == "PluginOcsinventoryngDashboard1") {
   if (isset($_POST["dateinv"])) {
      $options['reset']                     = 'reset';
      $options['criteria'][0]['field']      = 10002; // last inv
      $options['criteria'][0]['searchtype'] = 'contains';
      $options['criteria'][0]['value']      = $_POST["dateinv"];
      $options['criteria'][0]['link']       = 'AND';

      $link = $CFG_GLPI["root_doc"] . '/front/computer.php?' .
              Toolbox::append_params($options, "&");
      echo $link;
   }
} else if (isset($_POST["widget"])
           && $_POST["widget"] == "PluginMydashboardInfotel1") {
   //$criterias = ['entities_id', 'is_recursive', 'technicians_groups_id', 'type'];
   if (isset($_POST["datetik"])) {
      $options['reset']                     = 'reset';
      $options['criteria'][0]['field']      = 12; // status
      $options['criteria'][0]['searchtype'] = 'equals';
      $options['criteria'][0]['value']      = "notold";
      $options['criteria'][0]['link']       = 'AND';

      $options['criteria'][1]['field']      = 15; // open date
      $options['criteria'][1]['searchtype'] = 'contains';
      $options['criteria'][1]['value']      = $_POST["datetik"];
      $options['criteria'][1]['link']       = 'AND';

      if (!empty($_POST["technician_group"])) {
         $groups = $_POST["technician_group"];
         $options['criteria'][2]['link']       = 'AND';
         $nb = 0;
         foreach($groups as $group) {
            if ($nb == 0) {
               $options['criteria'][2]['criteria'][$nb]['link']       = 'AND';
            } else {
               $options['criteria'][2]['criteria'][$nb]['link']       = 'OR';
            }
            $options['criteria'][2]['criteria'][$nb]['field']       = 8;
            $options['criteria'][2]['criteria'][$nb]['searchtype'] = 'equals';
            $options['criteria'][2]['criteria'][$nb]['value']      = $group;
            $nb++;
         }
      }
      if ($_POST["type"] > 0) {
         $options['criteria'][3]['field']      = 14; // type
         $options['criteria'][3]['searchtype'] = 'equals';
         $options['criteria'][3]['value']      = $_POST["type"];
         $options['criteria'][3]['link']       = 'AND';
      }
      $options['criteria'][4]['field']      = 80; // entities
      $options['criteria'][4]['searchtype'] = 'equals';
      if (isset($_POST["sons"]) && $_POST["sons"] > 0) {
         $options['criteria'][4]['searchtype'] = 'under';
      }
      $options['criteria'][4]['value'] = $_POST["entities_id"];
      $options['criteria'][4]['link']  = 'AND';

      $link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
              Toolbox::append_params($options, "&");
      echo $link;
   }
} else if (isset($_POST["widget"])
           && $_POST["widget"] == "PluginMydashboardInfotel2") {
   //   $criterias = ['type'];
   if (isset($_POST["priority_id"])) {
      $options['reset']                     = 'reset';
      $options['criteria'][0]['field']      = 12; // status
      $options['criteria'][0]['searchtype'] = 'equals';
      $options['criteria'][0]['value']      = "notold";
      $options['criteria'][0]['link']       = 'AND';

      $options['criteria'][1]['field']      = 3; // priority
      $options['criteria'][1]['searchtype'] = 'equals';
      $options['criteria'][1]['value']      = $_POST["priority_id"];
      $options['criteria'][1]['link']       = 'AND';

      if ($_POST["type"] > 0) {
         $options['criteria'][2]['field']      = 14; // type
         $options['criteria'][2]['searchtype'] = 'equals';
         $options['criteria'][2]['value']      = $_POST["type"];
         $options['criteria'][2]['link']       = 'AND';
      }

      $options['criteria'][3]['field']      = 80; // entities
      $options['criteria'][3]['searchtype'] = 'equals';
      if (isset($_POST["sons"]) && $_POST["sons"] > 0) {
         $options['criteria'][3]['searchtype'] = 'under';
      }
      $options['criteria'][3]['value'] = $_POST["entities_id"];
      $options['criteria'][3]['link']  = 'AND';


      $link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
              Toolbox::append_params($options, "&");
      echo $link;
   }
} else if (isset($_POST["widget"])
           && $_POST["widget"] == "PluginMydashboardInfotel25") {
   //    $criterias = ['type'];
   //requester groups;
   if (isset($_POST["groups_id"])) {
      $options['reset']                     = 'reset';
      $options['criteria'][0]['field']      = 12; // status
      $options['criteria'][0]['searchtype'] = 'equals';
      $options['criteria'][0]['value']      = "notold";
      $options['criteria'][0]['link']       = 'AND';

      if (!empty($_POST["groups_id"])) {
         $options['criteria'][1]['field']      = 71; // requester group
         $options['criteria'][1]['searchtype'] = 'equals';
         $options['criteria'][1]['value']      = $_POST["groups_id"];
         $options['criteria'][1]['link']       = 'AND';
      } else {
         $options['criteria'][1]['field']      = 71; // requester group
         $options['criteria'][1]['searchtype'] = 'contains';
         $options['criteria'][1]['value']      = '^$';
         $options['criteria'][1]['link']       = 'AND';
      }
      if ($_POST["type"] > 0) {
         $options['criteria'][2]['field']      = 14; // type
         $options['criteria'][2]['searchtype'] = 'equals';
         $options['criteria'][2]['value']      = $_POST["type"];
         $options['criteria'][2]['link']       = 'AND';
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
      $options['reset']                     = 'reset';
      $options['criteria'][0]['field']      = 12; // status
      $options['criteria'][0]['searchtype'] = 'equals';
      $options['criteria'][0]['value']      = "notold";
      $options['criteria'][0]['link']       = 'AND';

      if ($_POST["widget"] == "PluginMydashboardInfotel16") {
         $options['reset']                     = 'reset';
         $options['criteria'][1]['field']      = 14; // type
         $options['criteria'][1]['searchtype'] = 'equals';
         $options['criteria'][1]['value']      = Ticket::INCIDENT_TYPE;
         $options['criteria'][1]['link']       = 'AND';
      } else {
         $options['reset']                     = 'reset';
         $options['criteria'][1]['field']      = 14; // type
         $options['criteria'][1]['searchtype'] = 'equals';
         $options['criteria'][1]['value']      = Ticket::DEMAND_TYPE;
         $options['criteria'][1]['link']       = 'AND';
      }

      if (empty($_POST["category_id"])) {
         $options['criteria'][2]['field']      = 7; // category
         $options['criteria'][2]['searchtype'] = 'contains';
         $options['criteria'][2]['value']      = '^$';
         $options['criteria'][2]['link']       = 'AND';
      } else {
         $options['criteria'][2]['field']      = 7; // category
         $options['criteria'][2]['searchtype'] = 'equals';
         $options['criteria'][2]['value']      = $_POST["category_id"];
         $options['criteria'][2]['link']       = 'AND';
      }

      if (!empty($_POST["technician_group"])) {
         $groups = $_POST["technician_group"];
         $options['criteria'][3]['link']       = 'AND';
         $nb = 0;
         foreach($groups as $group) {
            if ($nb == 0) {
               $options['criteria'][3]['criteria'][$nb]['link']       = 'AND';
            } else {
               $options['criteria'][3]['criteria'][$nb]['link']       = 'OR';
            }
            $options['criteria'][3]['criteria'][$nb]['field']       = 8;
            $options['criteria'][3]['criteria'][$nb]['searchtype'] = 'equals';
            $options['criteria'][3]['criteria'][$nb]['value']      = $group;
            $nb++;
         }
      }

      $options['criteria'][4]['field']      = 80; // entities
      $options['criteria'][4]['searchtype'] = 'equals';
      if (isset($_POST["sons"]) && $_POST["sons"] > 0) {
         $options['criteria'][4]['searchtype'] = 'under';
      }
      $options['criteria'][4]['value'] = $_POST["entities_id"];
      $options['criteria'][4]['link']  = 'AND';

      $link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
              Toolbox::append_params($options, "&");
      echo $link;
   }
} else if (isset($_POST["widget"])
           && $_POST["widget"] == "PluginMydashboardInfotel24") {
   //$criterias = ['entities_id', 'is_recursive', 'year', 'type'];
   if (isset($_POST["techtik"])) {
      $options['reset'] = 'reset';
      if ($_POST["techtik"] == -1) {
         $options['criteria'][0]['field']      = 5; // tech
         $options['criteria'][0]['searchtype'] = 'contains';
         $options['criteria'][0]['value']      = '^$';
         $options['criteria'][0]['link']       = 'AND';
      } else {
         $options['criteria'][0]['field']      = 5; // tech
         $options['criteria'][0]['searchtype'] = 'equals';
         $options['criteria'][0]['value']      = $_POST["techtik"];
         $options['criteria'][0]['link']       = 'AND';
      }

      $options['criteria'][1]['field']      = 15; // open date
      $options['criteria'][1]['searchtype'] = 'contains';
      $options['criteria'][1]['value']      = $_POST["year"];
      $options['criteria'][1]['link']       = 'AND';

      if ($_POST["type"] > 0) {
         $options['criteria'][2]['field']      = 14; // type
         $options['criteria'][2]['searchtype'] = 'equals';
         $options['criteria'][2]['value']      = $_POST["type"];
         $options['criteria'][2]['link']       = 'AND';
      }
      $options['criteria'][3]['field']      = 80; // entities
      $options['criteria'][3]['searchtype'] = 'equals';
      if (isset($_POST["sons"]) && $_POST["sons"] > 0) {
         $options['criteria'][3]['searchtype'] = 'under';
      }
      $options['criteria'][3]['value'] = $_POST["entities_id"];
      $options['criteria'][3]['link']  = 'AND';

      $link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
              Toolbox::append_params($options, "&");
      echo $link;
   }
} else if (isset($_POST["widget"])
           && $_POST["widget"] == "PluginMydashboardInfotel27") {
   //   $criterias = ['entities_id', 'is_recursive','type'];
   if (isset($_POST["locations_id"])) {
      $options['reset']                     = 'reset';
      $options['criteria'][0]['field']      = 12; // status
      $options['criteria'][0]['searchtype'] = 'equals';
      $options['criteria'][0]['value']      = "notold";
      $options['criteria'][0]['link']       = 'AND';

      if (empty($_POST["locations_id"])) {
         $options['criteria'][1]['field']      = 83; // location
         $options['criteria'][1]['searchtype'] = 'contains';
         $options['criteria'][1]['value']      = '^$';
         $options['criteria'][1]['link']       = 'AND';
      } else {
         $options['criteria'][1]['field']      = 83; // location
         $options['criteria'][1]['searchtype'] = 'equals';
         $options['criteria'][1]['value']      = $_POST["locations_id"];
         $options['criteria'][1]['link']       = 'AND';
      }

      if ($_POST["type"] > 0) {
         $options['criteria'][2]['field']      = 14; // type
         $options['criteria'][2]['searchtype'] = 'equals';
         $options['criteria'][2]['value']      = $_POST["type"];
         $options['criteria'][2]['link']       = 'AND';
      }

      $options['criteria'][3]['field']      = 80; // entities
      $options['criteria'][3]['searchtype'] = 'equals';
      if (isset($_POST["sons"]) && $_POST["sons"] > 0) {
         $options['criteria'][3]['searchtype'] = 'under';
      }
      $options['criteria'][3]['value'] = $_POST["entities_id"];
      $options['criteria'][3]['link']  = 'AND';

      if (!empty($_POST["technician_group"])) {
         $groups = $_POST["technician_group"];
         $options['criteria'][4]['link']       = 'AND';
         $nb = 0;
         foreach($groups as $group) {
            if ($nb == 0) {
               $options['criteria'][4]['criteria'][$nb]['link']       = 'AND';
            } else {
               $options['criteria'][4]['criteria'][$nb]['link']       = 'OR';
            }
            $options['criteria'][4]['criteria'][$nb]['field']       = 8;
            $options['criteria'][4]['criteria'][$nb]['searchtype'] = 'equals';
            $options['criteria'][4]['criteria'][$nb]['value']      = $group;
            $nb++;
         }
      }

      $link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
              Toolbox::append_params($options, "&");
      echo $link;
   }
} else if (isset($_POST["widget"])
   && $_POST["widget"] == "PluginMydashboardInfotel32"){

   // Reset criterias
   $options['reset'] = 'reset';

   // ENTITY | SONS
   $options['criteria'][] = [
      'field' => 80,
      'searchtype' => (isset($_POST["sons"]) && $_POST["sons"] > 0) ? 'under' : 'equals',
      'value' => $_POST["entities_id"],
      'link' => 'AND'
   ];

   // USER
   if(isset($_POST["technician"])){
      $options['criteria'][] = [
         'field' => 5,
         'searchtype' => 'equals',
         'value' => $_POST["technician"],
         'link' => 'AND'
      ];
   }

   // STATUS
   $options['criteria'][] = [
      'field' => 12,
      'searchtype' => 'equals',
      'value' => $_POST["status"],
      'link' => 'AND'
   ];

   echo $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
      Toolbox::append_params($options, "&");
} else if (isset($_POST["widget"])
   && $_POST["widget"] == "PluginMydashboardInfotel33"){

   // Reset criterias
   $options['reset'] = 'reset';

   // ENTITY | SONS
   $options['criteria'][] = [
      'field' => 80,
      'searchtype' => (isset($_POST["sons"]) && $_POST["sons"] > 0) ? 'under' : 'equals',
      'value' => $_POST["entities_id"],
      'link' => 'AND'
   ];

   // Group
   if (!empty($_POST["technician_group"])) {
      $groups = [$_POST["technician_group"]];
      $options['criteria'][1]['link']       = 'AND';
      $nb = 0;
      foreach($groups as $group) {
         if ($nb == 0) {
            $options['criteria'][1]['criteria'][$nb]['link']       = 'AND';
         } else {
            $options['criteria'][1]['criteria'][$nb]['link']       = 'OR';
         }
         $options['criteria'][1]['criteria'][$nb]['field']       = 8;
         $options['criteria'][1]['criteria'][$nb]['searchtype'] = 'equals';
         $options['criteria'][1]['criteria'][$nb]['value']      = $group;
         $nb++;
      }
   }


   // STATUS
   $options['criteria'][] = [
      'field' => 12,
      'searchtype' => 'equals',
      'value' => $_POST["status"],
      'link' => 'AND'
   ];

   echo $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
      Toolbox::append_params($options, "&");
}
