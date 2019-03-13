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

define("PRIORITY", 3);
define("TYPE", 14);
define("OPEN_DATE", 15);
define("ENTITIES", 80);
define("STATUS", 12);
define("CATEGORY", 7);
define("TECHNICIAN_GROUP", 8);

define("TELEPHONE_BATIMENTS", 859);
define("MOYEN_GENERAUX", 57);
define("SECURITE_DU_SI", 640);

define("HAS_SONS", isset($_POST["sons"]) && $_POST["sons"] > 0);

$urlRoot = $CFG_GLPI["root_doc"];
$ticketUrl = '/front/ticket.php?mydashboard&is_deleted=0&sort=15&order=ASC&';
$problemUrl = '/front/problem.php?mydashboard&is_deleted=0&sort=15&order=ASC&';

$link = "";

define("type", 0);
define("filter", 0);
define("state", 0);
define("values", 0);

$infos = $_POST['infos'];

if (!isset($_POST['widget'])) {
   exit;
}

resetCriterias();

$groupNumber = 0;
$groupIndex = 0;

if (!empty($_POST["groups_id"]) && !is_array($_POST["groups_id"])) {
   $groupNumber = 1;
} else if (!empty($_POST["groups_id"]) && is_array($_POST["groups_id"])) {
   $groupNumber = count($_POST["groups_id"]);
}

// Choose glpi page url
switch($infos['type']){
   case "TICKET":
   case "INCIDENT":
   case "DEMANDE":{
      $link .= $urlRoot . $ticketUrl;
      break;
   }
   case "PROBLEM":{
      $link .= $urlRoot . $problemUrl;
      break;
   }
   case "PROJECT":
   case "EVENT":
}

do{

   if($groupIndex == 0){
      if (!empty($_POST["groups_id"]) && !is_array($_POST["groups_id"]))
      {
         addCriteria(TECHNICIAN_GROUP, 'equals', $_POST["groups_id"], 'AND');
      }
      else if (!empty($_POST["groups_id"]) && is_array($_POST["groups_id"]))
      {
         addCriteria(TECHNICIAN_GROUP, 'equals', $_POST["groups_id"][$groupIndex], 'AND');
         $groupIndex++;
      }
   }

   switch ($infos['type']) {
      case "INCIDENT":
         {
            addCriteria(TYPE, 'equals', Ticket::INCIDENT_TYPE, 'AND');
            break;
         }
      case "DEMANDE":
         {
            addCriteria(TYPE, 'equals', Ticket::DEMAND_TYPE, 'AND');
            break;
         }
   }

// TYPE -> INCIDENT | DEMANDE
   if (isset($_POST["type"]) && $_POST["type"] > 0) {
      Toolbox::logDebug("HASTYPE");
      addCriteria(TYPE, 'equals', $_POST["type"], 'AND');
   }

   if (isset($infos['filter'])) {
      switch ($infos['filter']) {
         case "PRIORITY":{
               addCriteria(PRIORITY, 'equals', $_POST['selection'], 'AND');
               break;
            }
         case "CATEGORY":{
               if (!isset($_POST['selection']) || empty($_POST['selection'])) {
                  addCriteria(CATEGORY, 'contains', '^$', 'AND');
               } else {
                  addCriteria(CATEGORY, 'under', $_POST['selection'], 'AND');
               }
               break;
            }
         case "STATUS":{
               addCriteria(STATUS, 'equals', $_POST['selection'], 'AND');
               break;
            }
         case "DATE":{
               addCriteria(OPEN_DATE, 'contains', $_POST['selection'], 'AND');
               break;
            }
      }
   }


   if (isset($infos['state'])) {
      switch ($infos['state']) {
         case "NEW":{
               addCriteria(STATUS, 'equals', TICKET::INCOMING, 'AND');
               break;
            }
         case "INPROGRESS":{
               addCriteria(STATUS, 'equals', Ticket::SOLVED, 'AND NOT');
               addCriteria(STATUS, 'equals', Ticket::INCOMING, 'AND NOT');
               addCriteria(STATUS, 'equals', Ticket::CLOSED, 'AND NOT');
               break;
            }
         case "NOTCLOSED":{
               addCriteria(STATUS, 'equals', Ticket::CLOSED, 'AND NOT');
               break;
            }
      }
   }

// COMMONS

   addCriteria(ENTITIES, (HAS_SONS) ? 'under' : 'equals',
       $_POST["entities_id"], 'AND');

   if($groupNumber > 1 && $groupIndex < $groupNumber){
      $group = $_POST["groups_id"][$groupIndex];
      addCriteria(TECHNICIAN_GROUP, 'equals', $group, 'OR');
   }
   $groupIndex++;

}while($groupNumber > 1 && $groupIndex < $groupNumber +1 );

if (empty($link)) {
   exit;
}
$link .= Toolbox::append_params($options, "&");
echo $link;

function addCriteria($field, $searchType, $value, $link)
{
   global $options;

   $options['criteria'][] = [
       'field' => $field,
       'searchtype' => $searchType,
       'value' => $value,
       'link' => $link
   ];
}

function resetCriterias()
{
   $options['reset'] = 'reset';
   $options['criteria'] = [];
}
