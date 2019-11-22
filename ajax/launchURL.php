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

define("PRIORITY", 3);
define("TYPE", 14);
define("ENTITIES_ID", 80);
define("STATUS", 12);
define("CATEGORY", 7);
define("OPEN_DATE", 15);
define("TECHNICIAN", 5);
define("REQUESTER_GROUP", 71);
define("TECHNICIAN_GROUP", 8);
define("LOCATIONS_ID", 83);


/**
 * @param $field
 * @param $searchType
 * @param $value
 * @param $link
 */
function addCriteria($field, $searchType, $value, $link) {
   global $options;

   $options['criteria'][] = [
      'field'      => $field,
      'searchtype' => $searchType,
      'value'      => $value,
      'link'       => $link
   ];
}

/**
 * @param $field
 * @param $searchType
 * @param $value
 */
function groupCriteria($field, $searchType, $value) {
   global $options;

   if (isset($value)
       && count($value) > 0) {
      $groups = $value;
      $nb     = 0;
      foreach ($groups as $group) {

         $criterias['criteria'][$nb] = [
            'field'      => $field,
            'searchtype' => $searchType,
            'value'      => $group,
            'link'       => (($nb == 0) ? 'AND' : 'OR'),
         ];
         $nb++;
      }
      $options['criteria'][] = $criterias;
   }
}

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

      addCriteria(10002, 'contains', $_POST["params"]["dateinv"], 'AND');

      $link = $CFG_GLPI["root_doc"] . '/front/computer.php?' .
              Toolbox::append_params($options, "&");
      echo $link;
   }
} else if (isset($_POST["params"]["widget"])
           && $_POST["params"]["widget"] == "PluginMydashboardInfotel1") {
   //$criterias = ['entities_id', 'is_recursive', 'technicians_groups_id', 'type'];
   if (isset($_POST["selected_id"])) {

      addCriteria(STATUS, 'equals', 'notold', 'AND');
      // open date
      addCriteria(OPEN_DATE, 'contains', $_POST["selected_id"], 'AND');

      groupCriteria(REQUESTER_GROUP, 'equals', $_POST["params"]["requester_groups"]);

      groupCriteria(TECHNICIAN_GROUP, ((isset($_POST["params"]["group_is_recursive"]) && !empty($_POST["params"]["group_is_recursive"])) ? 'under' : 'equals'), $_POST["params"]["technician_group"]);

      if ($_POST["params"]["type"] > 0) {
         addCriteria(TYPE, 'equals', $_POST["params"]["type"], 'AND');
      }

      addCriteria(ENTITIES_ID, (isset($_POST["params"]["sons"]) && $_POST["params"]["sons"] > 0) ? 'under' : 'equals', $_POST["params"]["entities_id"], 'AND');

      $link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
              Toolbox::append_params($options, "&");
      echo $link;
   }
} else if (isset($_POST["params"]["widget"])
           && ($_POST["params"]["widget"] == "PluginMydashboardInfotel2"
               || $_POST["params"]["widget"] == "PluginMydashboardInfotel36")) {


   if (isset($_POST["selected_id"])) {

      addCriteria(STATUS, 'equals', 'notold', 'AND');

      addCriteria(PRIORITY, 'equals', $_POST["selected_id"], 'AND');

      if ($_POST["params"]["type"] > 0) {
         addCriteria(TYPE, 'equals', $_POST["params"]["type"], 'AND');
      }

      addCriteria(ENTITIES_ID, (isset($_POST["params"]["sons"]) && $_POST["params"]["sons"] > 0) ? 'under' : 'equals', $_POST["params"]["entities_id"], 'AND');

      groupCriteria(TECHNICIAN_GROUP, ((isset($_POST["params"]["group_is_recursive"]) && !empty($_POST["params"]["group_is_recursive"])) ? 'under' : 'equals'), $_POST["params"]["technician_group"]);

      $link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
              Toolbox::append_params($options, "&");
      echo $link;
   }
} else if (isset($_POST["params"]["widget"])
           && $_POST["params"]["widget"] == "PluginMydashboardInfotel25") {
   //    $criterias = ['type'];
   //requester groups;
   if (isset($_POST["selected_id"])) {

      addCriteria(STATUS, 'equals', 'notold', 'AND');
      // requester_group
      addCriteria(71, ((empty($_POST["selected_id"])) ? 'contains' : 'equals'), ((empty($_POST["selected_id"])) ? '^$' : $_POST["selected_id"]), 'AND');

      if ($_POST["params"]["type"] > 0) {
         addCriteria(TYPE, 'equals', $_POST["params"]["type"], 'AND');
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

      addCriteria(STATUS, 'equals', 'notold', 'AND');

      addCriteria(TYPE, 'equals', (($_POST["params"]["widget"] == "PluginMydashboardInfotel16") ? Ticket::INCIDENT_TYPE : Ticket::DEMAND_TYPE), 'AND');

      addCriteria(CATEGORY, ((empty($_POST["selected_id"])) ? 'contains' : 'equals'), ((empty($_POST["selected_id"])) ? '^$' : $_POST["selected_id"]), 'AND');

      groupCriteria(REQUESTER_GROUP, 'equals', $_POST["params"]["requester_groups"]);

      groupCriteria(TECHNICIAN_GROUP, ((isset($_POST["params"]["group_is_recursive"]) && !empty($_POST["params"]["group_is_recursive"])) ? 'under' : 'equals'), $_POST["params"]["technician_group"]);

      addCriteria(ENTITIES_ID, (isset($_POST["params"]["sons"]) && $_POST["params"]["sons"] > 0) ? 'under' : 'equals', $_POST["params"]["entities_id"], 'AND');

      $link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
              Toolbox::append_params($options, "&");
      echo $link;
   }
} else if (isset($_POST["params"]["widget"])
           && $_POST["params"]["widget"] == "PluginMydashboardInfotel24") {
   //$criterias = ['entities_id', 'is_recursive', 'year', 'type'];
   if (isset($_POST["selected_id"])) {

      addCriteria(TECHNICIAN, (($_POST["selected_id"] == -1) ? 'contains' : 'equals'), (($_POST["selected_id"] == -1) ? '^$' : $_POST["selected_id"]), 'AND');

      addCriteria(OPEN_DATE, 'contains', $_POST["params"]["year"], 'AND');

      if ($_POST["params"]["type"] > 0) {
         addCriteria(TYPE, 'equals', $_POST["params"]["type"], 'AND');
      }

      addCriteria(ENTITIES_ID, (isset($_POST["params"]["sons"]) && $_POST["params"]["sons"] > 0) ? 'under' : 'equals', $_POST["params"]["entities_id"], 'AND');

      $link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
              Toolbox::append_params($options, "&");
      echo $link;
   }
} else if (isset($_POST["params"]["widget"])
           && $_POST["params"]["widget"] == "PluginMydashboardInfotel27") {
   //   $criterias = ['entities_id', 'is_recursive','type'];
   if (isset($_POST["selected_id"])) {

      addCriteria(STATUS, 'equals', 'notold', 'AND');

      addCriteria(LOCATIONS_ID, ((empty($_POST["selected_id"])) ? 'contains' : 'equals'), ((empty($_POST["selected_id"])) ? '^$' : $_POST["selected_id"]), 'AND');

      if ($_POST["params"]["type"] > 0) {
         addCriteria(TYPE, 'equals', $_POST["params"]["type"], 'AND');
      }

      addCriteria(ENTITIES_ID, (isset($_POST["params"]["sons"]) && $_POST["params"]["sons"] > 0) ? 'under' : 'equals', $_POST["params"]["entities_id"], 'AND');

      groupCriteria(TECHNICIAN_GROUP, ((isset($_POST["params"]["group_is_recursive"]) && !empty($_POST["params"]["group_is_recursive"])) ? 'under' : 'equals'), $_POST["params"]["technician_group"]);


      $link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
              Toolbox::append_params($options, "&");
      echo $link;
   }
} else if (isset($_POST["params"]["widget"])
           && $_POST["params"]["widget"] == "PluginMydashboardInfotel32") {

   // ENTITY | SONS
   addCriteria(ENTITIES_ID, (isset($_POST["params"]["sons"]) && $_POST["params"]["sons"] > 0) ? 'under' : 'equals', $_POST["params"]["entities_id"], 'AND');

   // USER
   if (isset($_POST["params"]["technician"])) {
      addCriteria(TECHNICIAN, 'equals', $_POST["params"]["technician"], 'AND');
   }

   // STATUS
   if ($_POST["params"]['moreticket'] == 1) {
      addCriteria(3452, 'equals', $_POST["params"]["status"], 'AND');
   } else {
      addCriteria(STATUS, 'equals', $_POST["params"]["status"], 'AND');
   }

   echo $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
        Toolbox::append_params($options, "&");
} else if (isset($_POST["params"]["widget"])
           && $_POST["params"]["widget"] == "PluginMydashboardInfotel33") {

   // ENTITY | SONS
   addCriteria(ENTITIES_ID, (isset($_POST["params"]["sons"]) && $_POST["params"]["sons"] > 0) ? 'under' : 'equals', $_POST["params"]["entities_id"], 'AND');

   // STATUS
   if ($_POST["params"]['moreticket'] == 1) {
      addCriteria(3452, 'equals', $_POST["params"]["status"], 'AND');
   } else {
      addCriteria(STATUS, 'equals', $_POST["params"]["status"], 'AND');
   }

   // Group
   groupCriteria(TECHNICIAN_GROUP, ((isset($_POST["params"]["group_is_recursive"]) && !empty($_POST["params"]["group_is_recursive"])) ? 'under' : 'equals'), $_POST["params"]["technician_group"]);


   echo $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
        Toolbox::append_params($options, "&");
} else if (isset($_POST["params"]["widget"])
           && $_POST["params"]["widget"] == "PluginMydashboardInfotel37") {

   // ENTITY | SONS
   addCriteria(ENTITIES_ID, (isset($_POST["params"]["sons"]) && $_POST["params"]["sons"] > 0) ? 'under' : 'equals', $_POST["params"]["entities_id"], 'AND');

   if ($_POST["params"]["type"] > 0) {
      addCriteria(TYPE, 'equals', $_POST["params"]["type"], 'AND');
   }

   // STATUS
   if (strpos($_POST["selected_id"], 'moreticket_') !== false) {
      $status = explode("_", $_POST["selected_id"]);

      addCriteria(STATUS, 'equals', Ticket::WAITING, 'AND');

      addCriteria(3452, 'equals', $status[1], 'AND');

   } else {

      addCriteria(STATUS, 'equals', $_POST["selected_id"], 'AND');

   }

   // Group
   groupCriteria(TECHNICIAN_GROUP, ((isset($_POST["params"]["group_is_recursive"]) && !empty($_POST["params"]["group_is_recursive"])) ? 'under' : 'equals'), $_POST["params"]["technician_group"]);


   echo $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
        Toolbox::append_params($options, "&");
}
