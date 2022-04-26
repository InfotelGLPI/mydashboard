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
 * This class extends GLPI class ticket to add the functions to display widgets on Dashboard
 */
class PluginMydashboardTicket extends CommonGLPI {

   /**
    * @param int $nb
    *
    * @return string|\translated
    */
   static function getTypeName($nb = 0) {
      return __('Tickets');
   }

   /**
    * @return array
    */
   function getWidgetsForItem() {
      $showticket   = Session::haveRightsOr("ticket", [Ticket::READMY, Ticket::READALL, Ticket::READASSIGN]);
      $createticket = Session::haveRight("ticket", CREATE);

      $widgets = [
         PluginMydashboardMenu::$TICKET_REQUESTERVIEW => [
            "ticketlistrequestbyselfwidget" => ["title"   => __('Your tickets in progress'),
                                                "icon"    => "ti ti-table",
                                                "comment" => ""],
            "ticketlistobservedwidget"      => ["title"   => __('Your observed tickets'),
                                                "icon"    => "ti ti-table",
                                                "comment" => ""],
            "ticketlistrejectedwidget"      => ["title"   => __('Your rejected tickets', 'mydashboard'),
                                                "icon"    => "ti ti-table",
                                                "comment" => ""],
            "ticketlisttoapprovewidget"     => ["title"   => __('Your tickets to close'),
                                                "icon"    => "ti ti-table",
                                                "comment" => ""],
            "ticketlistsurveywidget"        => ["title"   => __('Satisfaction survey'),
                                                "icon"    => "ti ti-table",
                                                "comment" => ""],
         ]
      ];
      if (Session::haveRightsOr('ticketvalidation', TicketValidation::getValidateRights())) {
         $widgets[PluginMydashboardMenu::$TICKET_REQUESTERVIEW]["ticketlisttovalidatewidget"] = ["title"   => __('Your tickets to validate'),
                                                                                                 "icon"    => "ti ti-table",
                                                                                                 "comment" => ""];
      }
      if ($showticket) {

         $widgets[PluginMydashboardMenu::$TICKET_TECHVIEW]["ticketcountwidget2"]      = ["title"   => __('New tickets', 'mydashboard'),
                                                                                         "icon"    => "ti ti-table",
                                                                                         "comment" => ""];
         $widgets[PluginMydashboardMenu::$TICKET_TECHVIEW]["ticketlistprocesswidget"] = ["title"   => __('Tickets to be processed'),
                                                                                         "icon"    => "ti ti-table",
                                                                                         "comment" => ""];
         $widgets[PluginMydashboardMenu::$TICKET_TECHVIEW]["ticketlistwaitingwidget"] = ["title"   => __('Tickets on pending status'),
                                                                                         "icon"    => "ti ti-table",
                                                                                         "comment" => ""];
         $widgets[PluginMydashboardMenu::$TICKET_TECHVIEW]["tickettaskstodowidget"]   = ["title"   => __("Ticket tasks to do"),
                                                                                         "icon"    => "ti ti-table",
                                                                                         "comment" => ""];
      }
      if (Session::haveRight('ticket', Ticket::READGROUP)) {

         $widgets[PluginMydashboardMenu::$TICKET_TECHVIEW]["ticketlistwaitingwidgetgroup"]       = ["title"   => __('Tickets on pending status'),
                                                                                                    "icon"    => "ti ti-table",
                                                                                                    "comment" => ""];
         $widgets[PluginMydashboardMenu::$TICKET_TECHVIEW]["ticketlistwaitingwidgetgroup"]       = ["title"   => __('Your tickets to close'),
                                                                                                    "icon"    => "ti ti-table",
                                                                                                    "comment" => ""];
         $widgets[PluginMydashboardMenu::$TICKET_TECHVIEW]["ticketlistrequestbyselfwidgetgroup"] = ["title"   => __('Your tickets in progress'),
                                                                                                    "icon"    => "ti ti-table",
                                                                                                    "comment" => ""];
         $widgets[PluginMydashboardMenu::$TICKET_TECHVIEW]["ticketlistobservedwidgetgroup"]      = ["title"   => __('Your observed tickets'),
                                                                                                    "icon"    => "ti ti-table",
                                                                                                    "comment" => ""];
      }
      if ($showticket) {

         $widgets[PluginMydashboardMenu::$GROUP_VIEW]["ticketlistprocesswidgetgroup"] = ["title"   => __('Tickets to be processed'),
                                                                                         "icon"    => "ti ti-table",
                                                                                         "comment" => ""];
         $widgets[PluginMydashboardMenu::$GROUP_VIEW]["tickettaskstodowidgetgroup"]   = ["title"   => __("Ticket tasks to do"),
                                                                                         "icon"    => "ti ti-table",
                                                                                         "comment" => ""];
      }
      if ($showticket || $createticket) {

         $widgets[PluginMydashboardMenu::$GLOBAL_VIEW]["ticketcountwidget"] = ["title"   => __('Ticket followup', 'mydashboard'),
                                                                               "icon"    => "ti ti-table",
                                                                               "comment" => ""];
      }

      return $widgets;

   }

   /**
    * @param $widgetId
    *
    * @return bool|PluginMydashboardDatatable|string
    */
   function getWidgetContentForItem($widgetId) {

      $showticket   = Session::haveRightsOr("ticket", [Ticket::READMY, Ticket::READALL, Ticket::READASSIGN]);
      $createticket = Session::haveRight("ticket", CREATE);
      switch ($widgetId) {
         //Personnal
         case "ticketlisttovalidatewidget":
            if (Session::haveRightsOr('ticketvalidation', TicketValidation::getValidateRights())) {
               return self::showCentralList(0, "tovalidate", false);
            }
            break;
         case "ticketlisttoapprovewidget":
            return self::showCentralList(0, "toapprove", false);
            break;
         case "ticketlistrejectedwidget":
            return self::showCentralList(0, "rejected", false);
            break;
         case "ticketlistsurveywidget":
            return self::showCentralList(0, "survey", false);
            break;
         case "ticketlistrequestbyselfwidget":
            return self::showCentralList(0, "requestbyself", false);
            break;
         case "ticketlistobservedwidget":
            return self::showCentralList(0, "observed", false);
            break;
         case "ticketlistprocesswidget":
            if ($showticket) {
               return self::showCentralList(0, "process", false);
            };
            break;
         case "ticketlistwaitingwidget":
            if ($showticket) {
               return self::showCentralList(0, "waiting", false);
            };
            break;
         //Group
         case "ticketlistwaitingwidgetgroup":
            if (Session::haveRight('ticket', Ticket::READGROUP)) {
               return self::showCentralList(0, "waiting", true);
            };
            break;
         case "ticketlisttoapprovewidgetgroup":
            if (Session::haveRight('ticket', Ticket::READGROUP)) {
               return self::showCentralList(0, "toapprove", true);
            }
            break;
         case "ticketlistrequestbyselfwidgetgroup":
            if (Session::haveRight('ticket', Ticket::READGROUP)) {
               return self::showCentralList(0, "requestbyself", true);
            }
            break;
         case "ticketlistobservedwidgetgroup":
            if (Session::haveRight('ticket', Ticket::READGROUP)) {
               return self::showCentralList(0, "observed", true);
            }
            break;
         case "ticketlistprocesswidgetgroup":
            if ($showticket) {
               return self::showCentralList(0, "process", true);
            }
            break;
         //Global
         case "ticketcountwidget":
            if ($showticket || $createticket) {
               return self::showCentralCount($createticket && (Session::getCurrentInterface() == 'helpdesk'));
            }
            break;
         case "ticketcountwidget2":
            if ($showticket) {
               return self::showCentralNewList();
            }
            break;
         case "tickettaskstodowidget":
            if ($showticket) {
               return self::showCentralTaskList(0, "todo", false);
            }
         case "tickettaskstodowidgetgroup":
            if ($showticket) {
               return self::showCentralTaskList(0, "todo", true);
            }
            break;
      }
   }

   /**
    * @param $start
    * @param $status (default ''process)
    * @param $showgrouptickets (true by default)
    *
    * @return PluginMydashboardDatatable|string
    */
   static function showCentralList($start, $status = "process", $showgrouptickets = true) {
      global $DB, $CFG_GLPI;

      $output = [];

      if (!Session::haveRightsOr(Ticket::$rightname, [CREATE, Ticket::READALL, Ticket::READASSIGN])
          && !Session::haveRightsOr('ticketvalidation', TicketValidation::getValidateRights())) {

         return false;
      }

      $search_users_id = " (`glpi_tickets_users`.`users_id` = '" . Session::getLoginUserID() . "'
                            AND `glpi_tickets_users`.`type` = '" . CommonITILActor::REQUESTER . "') ";
      $search_assign   = " (`glpi_tickets_users`.`users_id` = '" . Session::getLoginUserID() . "'
                            AND `glpi_tickets_users`.`type` = '" . CommonITILActor::ASSIGN . "')";
      $search_observer = " (`glpi_tickets_users`.`users_id` = '" . Session::getLoginUserID() . "'
                            AND `glpi_tickets_users`.`type` = '" . CommonITILActor::OBSERVER . "')";
      $is_deleted      = " `glpi_tickets`.`is_deleted` = 0 ";

      if ($showgrouptickets) {
         $search_users_id = " 0 = 1 ";
         $search_assign   = " 0 = 1 ";

         if (count($_SESSION['glpigroups'])) {
            $groups        = implode("','", $_SESSION['glpigroups']);
            $search_assign = " (`glpi_groups_tickets`.`groups_id` IN ('$groups')
                                AND `glpi_groups_tickets`.`type` = '" . CommonITILActor::ASSIGN . "')";

            if (Session::haveRight(Ticket::$rightname, Ticket::READGROUP)) {
               $search_users_id = " (`glpi_groups_tickets`.`groups_id` IN ('$groups')
                                     AND `glpi_groups_tickets`.`type`
                                           = '" . CommonITILActor::REQUESTER . "') ";
            }
            if (Session::haveRight(Ticket::$rightname, Ticket::READGROUP)) {
               $search_observer = " (`glpi_groups_tickets`.`groups_id` IN ('$groups')
                                     AND `glpi_groups_tickets`.`type`
                                           = '" . CommonITILActor::OBSERVER . "') ";
            }
         }
      }
      $dbu   = new DbUtils();
      $query = "SELECT DISTINCT `glpi_tickets`.`id`
                FROM `glpi_tickets`
                LEFT JOIN `glpi_tickets_users`
                     ON (`glpi_tickets`.`id` = `glpi_tickets_users`.`tickets_id`)
                LEFT JOIN `glpi_groups_tickets`
                     ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id`)";

      switch ($status) {
         case "waiting" : // on affiche les tickets en attente
            $query .= "WHERE $is_deleted
                             AND ($search_assign)
                             AND `status` = '" . Ticket::WAITING . "' " .
                      $dbu->getEntitiesRestrictRequest("AND", "glpi_tickets");
            break;

         case "process" : // on affiche les tickets planifiés ou assignés au user
            $query .= "WHERE $is_deleted
                             AND ( $search_assign )
                             AND (`status` IN ('" . implode("','", Ticket::getProcessStatusArray()) . "')) " .
                      $dbu->getEntitiesRestrictRequest("AND", "glpi_tickets");
            break;

         case "toapprove" : // on affiche les tickets planifiés ou assignés au user
            $query .= "WHERE $is_deleted
                             AND (`status` = '" . Ticket::SOLVED . "')
                             AND ($search_users_id";
            if (!$showgrouptickets) {
               $query .= " OR `glpi_tickets`.users_id_recipient = '" . Session::getLoginUserID() . "' ";
            }
            $query .= ")" .
                      $dbu->getEntitiesRestrictRequest("AND", "glpi_tickets");
            break;

         case "tovalidate" : // on affiche les tickets à valider
            $query .= " LEFT JOIN `glpi_ticketvalidations`
                           ON (`glpi_tickets`.`id` = `glpi_ticketvalidations`.`tickets_id`)
                        WHERE $is_deleted AND `users_id_validate` = '" . Session::getLoginUserID() . "'
                              AND `glpi_ticketvalidations`.`status` = '" . CommonITILValidation::WAITING . "'
                              AND (`glpi_tickets`.`status` NOT IN ('" . Ticket::CLOSED . "',
                                                                   '" . Ticket::SOLVED . "')) " .
                      $dbu->getEntitiesRestrictRequest("AND", "glpi_tickets");
            break;

         case "rejected" : // on affiche les tickets rejetés
            $query .= "WHERE $is_deleted
                             AND ($search_assign)
                             AND `status` <> '" . Ticket::CLOSED . "'
                             AND `global_validation` = '" . CommonITILValidation::REFUSED . "' " .
                      $dbu->getEntitiesRestrictRequest("AND", "glpi_tickets");
            break;

         case "observed" :
            $query .= "WHERE $is_deleted
                             AND ($search_observer)
                             AND (`status` IN ('" . Ticket::INCOMING . "',
                                               '" . Ticket::PLANNED . "',
                                               '" . Ticket::ASSIGNED . "',
                                               '" . Ticket::WAITING . "'))
                             AND NOT ( $search_assign )
                             AND NOT ( $search_users_id ) " .
                      $dbu->getEntitiesRestrictRequest("AND", "glpi_tickets");
            break;

         case "survey" : // on affiche les tickets dont l'enquête de satisfaction n'est pas remplie
            $query .= " INNER JOIN `glpi_ticketsatisfactions`
                           ON (`glpi_tickets`.`id` = `glpi_ticketsatisfactions`.`tickets_id`)
                        WHERE $is_deleted
                              AND ($search_users_id
                                   OR `glpi_tickets`.`users_id_recipient` = '" . Session::getLoginUserID() . "')
                              AND `glpi_tickets`.`status` = '" . Ticket::CLOSED . "'
                              AND `glpi_ticketsatisfactions`.`date_answered` IS NULL " .
                      $dbu->getEntitiesRestrictRequest("AND", "glpi_tickets");
            break;

         case "requestbyself" : // on affiche les tickets demandés le user qui sont planifiés ou assignés
            // à quelqu'un d'autre (exclut les self-tickets)

         default :
            $query .= "WHERE $is_deleted
                             AND ($search_users_id)
                             AND (`status` IN ('" . Ticket::INCOMING . "',
                                               '" . Ticket::PLANNED . "',
                                               '" . Ticket::ASSIGNED . "',
                                               '" . Ticket::WAITING . "'))
                             AND NOT ( $search_assign ) " .
                      $dbu->getEntitiesRestrictRequest("AND", "glpi_tickets");
      }

      $query   .= " ORDER BY date_mod DESC";
      $result  = $DB->query($query);
      $numrows = $DB->numrows($result);

      //      if ($_SESSION['glpidisplay_count_on_home'] > 0) {
      //         $query  .= " LIMIT " . intval($start) . ',' . intval($_SESSION['glpidisplay_count_on_home']);
      $result = $DB->query($query);
      $number = $DB->numrows($result);
      //      } else {
      //         $number = 0;
      //      }

      $output['header'][] = __('ID and priority', 'mydashboard');
      $output['header'][] = __('Requester');
      $output['header'][] = __('Associated element');
      $output['header'][] = __('Description');
      $output['header'][] = __('ID');
      $output['header'][] = __('Priority');
      $output['header'][] = __('Category');
      $output['header'][] = __('Status');
      $output['body']     = [];
      $output['title']    = "default";

      //if ($numrows > 0) {
      $options['reset'] = 'reset';
      $forcetab         = '';
      $num              = 0;
      if ($showgrouptickets) {
         switch ($status) {
            case "toapprove" :
               $options['criteria'][0]['field']      = 12; // status
               $options['criteria'][0]['searchtype'] = 'equals';
               $options['criteria'][0]['value']      = Ticket::SOLVED;
               $options['criteria'][0]['link']       = 'AND';

               $options['criteria'][1]['field']      = 71; // groups_id
               $options['criteria'][1]['searchtype'] = 'equals';
               $options['criteria'][1]['value']      = 'mygroups';
               $options['criteria'][1]['link']       = 'AND';
               $forcetab                             = 'Ticket$2';

               $output['title'] = "<a style=\"font-size:14px;\" href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.php?" .
                                  Toolbox::append_params($options, '&amp;') . "\">" .
                                  Html::makeTitle(__('Your tickets to close'), $number, $numrows) . "</a>";
               break;

            case "waiting" :
               $options['criteria'][0]['field']      = 12; // status
               $options['criteria'][0]['searchtype'] = 'equals';
               $options['criteria'][0]['value']      = Ticket::WAITING;
               $options['criteria'][0]['link']       = 'AND';

               $options['criteria'][1]['field']      = 8; // groups_id_assign
               $options['criteria'][1]['searchtype'] = 'equals';
               $options['criteria'][1]['value']      = 'mygroups';
               $options['criteria'][1]['link']       = 'AND';

               $output['title'] = "<a style=\"font-size:14px;\" href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.php?" .
                                  Toolbox::append_params($options, '&amp;') . "\">" .
                                  Html::makeTitle(__('Tickets on pending status'), $number, $numrows) . "</a>";
               break;

            case "process" :
               $options['criteria'][0]['field']      = 12; // status
               $options['criteria'][0]['searchtype'] = 'equals';
               $options['criteria'][0]['value']      = 'process';
               $options['criteria'][0]['link']       = 'AND';

               $options['criteria'][1]['field']      = 8; // groups_id_assign
               $options['criteria'][1]['searchtype'] = 'equals';
               $options['criteria'][1]['value']      = 'mygroups';
               $options['criteria'][1]['link']       = 'AND';

               $output['title'] = "<a style=\"font-size:14px;\" href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.php?" .
                                  Toolbox::append_params($options, '&amp;') . "\">" .
                                  Html::makeTitle(__('Tickets to be processed'), $number, $numrows) . "</a>";
               break;

            case "observed":
               $options['criteria'][0]['field']      = 12; // status
               $options['criteria'][0]['searchtype'] = 'equals';
               $options['criteria'][0]['value']      = 'notold';
               $options['criteria'][0]['link']       = 'AND';

               $options['criteria'][1]['field']      = 65; // groups_id
               $options['criteria'][1]['searchtype'] = 'equals';
               $options['criteria'][1]['value']      = 'mygroups';
               $options['criteria'][1]['link']       = 'AND';

               $output['title'] = "<a style=\"font-size:14px;\" href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.php?" .
                                  Toolbox::append_params($options, '&amp;') . "\">" .
                                  Html::makeTitle(__('Your observed tickets'), $number, $numrows) . "</a>";
               break;

            case "requestbyself" :
            default :
               $options['criteria'][0]['field']      = 12; // status
               $options['criteria'][0]['searchtype'] = 'equals';
               $options['criteria'][0]['value']      = 'notold';
               $options['criteria'][0]['link']       = 'AND';

               $options['criteria'][1]['field']      = 71; // groups_id
               $options['criteria'][1]['searchtype'] = 'equals';
               $options['criteria'][1]['value']      = 'mygroups';
               $options['criteria'][1]['link']       = 'AND';

               $output['title'] = "<a style=\"font-size:14px;\" href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.php?" .
                                  Toolbox::append_params($options, '&amp;') . "\">" .
                                  Html::makeTitle(__('Your tickets in progress'), $number, $numrows) . "</a>";
         }

      } else {
         switch ($status) {
            case "waiting" :
               $options['criteria'][0]['field']      = 12; // status
               $options['criteria'][0]['searchtype'] = 'equals';
               $options['criteria'][0]['value']      = Ticket::WAITING;
               $options['criteria'][0]['link']       = 'AND';

               $options['criteria'][1]['field']      = 5; // users_id_assign
               $options['criteria'][1]['searchtype'] = 'equals';
               $options['criteria'][1]['value']      = Session::getLoginUserID();
               $options['criteria'][1]['link']       = 'AND';

               $output['title'] = "<a style=\"font-size:14px;\" href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.php?" .
                                  Toolbox::append_params($options, '&amp;') . "\">" .
                                  Html::makeTitle(__('Tickets on pending status'), $number, $numrows) . "</a>";
               break;

            case "process" :
               $options['criteria'][0]['field']      = 5; // users_id_assign
               $options['criteria'][0]['searchtype'] = 'equals';
               $options['criteria'][0]['value']      = Session::getLoginUserID();
               $options['criteria'][0]['link']       = 'AND';

               $options['criteria'][1]['field']      = 12; // status
               $options['criteria'][1]['searchtype'] = 'equals';
               $options['criteria'][1]['value']      = 'process';
               $options['criteria'][1]['link']       = 'AND';

               $output['title'] = "<a style=\"font-size:14px;\" href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.php?" .
                                  Toolbox::append_params($options, '&amp;') . "\">" .
                                  Html::makeTitle(__('Tickets to be processed'), $number, $numrows) . "</a>";
               break;

            case "tovalidate" :
               $options['criteria'][0]['field']      = 55; // validation status
               $options['criteria'][0]['searchtype'] = 'equals';
               $options['criteria'][0]['value']      = CommonITILValidation::WAITING;
               $options['criteria'][0]['link']       = 'AND';

               $options['criteria'][1]['field']      = 59; // validation aprobator
               $options['criteria'][1]['searchtype'] = 'equals';
               $options['criteria'][1]['value']      = Session::getLoginUserID();
               $options['criteria'][1]['link']       = 'AND';

               $options['criteria'][2]['field']      = 12; // validation aprobator
               $options['criteria'][2]['searchtype'] = 'equals';
               $options['criteria'][2]['value']      = 'old';
               $options['criteria'][2]['link']       = 'AND NOT';
               $forcetab                             = 'TicketValidation$1';

               $output['title'] = "<a style=\"font-size:14px;\" href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.php?" .
                                  Toolbox::append_params($options, '&amp;') . "\">" .
                                  Html::makeTitle(__('Your tickets to validate'), $number, $numrows) . "</a>";

               break;

            case "rejected" :
               $options['criteria'][0]['field']      = 52; // validation status
               $options['criteria'][0]['searchtype'] = 'equals';
               $options['criteria'][0]['value']      = CommonITILValidation::REFUSED;
               $options['criteria'][0]['link']       = 'AND';

               $options['criteria'][1]['field']      = 5; // assign user
               $options['criteria'][1]['searchtype'] = 'equals';
               $options['criteria'][1]['value']      = Session::getLoginUserID();
               $options['criteria'][1]['link']       = 'AND';

               $output['title'] = "<a style=\"font-size:14px;\" href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.php?" .
                                  Toolbox::append_params($options, '&amp;') . "\">" .
                                  Html::makeTitle(__('Your rejected tickets'), $number, $numrows) . "</a>";

               break;

            case "toapprove" :
               $options['criteria'][0]['field']      = 12; // status
               $options['criteria'][0]['searchtype'] = 'equals';
               $options['criteria'][0]['value']      = Ticket::SOLVED;
               $options['criteria'][0]['link']       = 'AND';

               $options['criteria'][1]['field']      = 4; // users_id_assign
               $options['criteria'][1]['searchtype'] = 'equals';
               $options['criteria'][1]['value']      = Session::getLoginUserID();
               $options['criteria'][1]['link']       = 'AND';

               $options['criteria'][2]['field']      = 22; // users_id_recipient
               $options['criteria'][2]['searchtype'] = 'equals';
               $options['criteria'][2]['value']      = Session::getLoginUserID();
               $options['criteria'][2]['link']       = 'OR';

               $options['criteria'][3]['field']      = 12; // status
               $options['criteria'][3]['searchtype'] = 'equals';
               $options['criteria'][3]['value']      = Ticket::SOLVED;
               $options['criteria'][3]['link']       = 'AND';
               $forcetab                             = 'Ticket$2';

               $output['title'] = "<a style=\"font-size:14px;\" href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.php?" .
                                  Toolbox::append_params($options, '&amp;') . "\">" .
                                  Html::makeTitle(__('Your tickets to close'), $number, $numrows) . "</a>";
               break;

            case "observed" :
               $options['criteria'][0]['field']      = 66; // users_id
               $options['criteria'][0]['searchtype'] = 'equals';
               $options['criteria'][0]['value']      = Session::getLoginUserID();
               $options['criteria'][0]['link']       = 'AND';

               $options['criteria'][1]['field']      = 12; // status
               $options['criteria'][1]['searchtype'] = 'equals';
               $options['criteria'][1]['value']      = 'notold';
               $options['criteria'][1]['link']       = 'AND';

               $output['title'] = "<a style=\"font-size:14px;\" href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.php?" .
                                  Toolbox::append_params($options, '&amp;') . "\">" .
                                  Html::makeTitle(__('Your observed tickets'), $number, $numrows) . "</a>";
               break;

            case "survey" :
               $options['criteria'][0]['field']      = 12; // status
               $options['criteria'][0]['searchtype'] = 'equals';
               $options['criteria'][0]['value']      = Ticket::CLOSED;
               $options['criteria'][0]['link']       = 'AND';

               $options['criteria'][1]['field']      = 60; // enquete generee
               $options['criteria'][1]['searchtype'] = 'contains';
               $options['criteria'][1]['value']      = '^';
               $options['criteria'][1]['link']       = 'AND';

               $options['criteria'][2]['field']      = 61; // date_answered
               $options['criteria'][2]['searchtype'] = 'contains';
               $options['criteria'][2]['value']      = 'NULL';
               $options['criteria'][2]['link']       = 'AND';

               $options['criteria'][3]['field']      = 22; // auteur
               $options['criteria'][3]['searchtype'] = 'equals';
               $options['criteria'][3]['value']      = Session::getLoginUserID();
               $options['criteria'][3]['link']       = 'AND';
               $forcetab                             = 'Ticket$3';

               $output['title'] = "<a style=\"font-size:14px;\" href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.php?" .
                                  Toolbox::append_params($options, '&amp;') . "\">" .
                                  Html::makeTitle(__('Satisfaction survey'), $number, $numrows) . "</a>";
               break;

            case "requestbyself" :
            default :
               $options['criteria'][0]['field']      = 4; // users_id
               $options['criteria'][0]['searchtype'] = 'equals';
               $options['criteria'][0]['value']      = Session::getLoginUserID();
               $options['criteria'][0]['link']       = 'AND';

               $options['criteria'][1]['field']      = 12; // status
               $options['criteria'][1]['searchtype'] = 'equals';
               $options['criteria'][1]['value']      = 'notold';
               $options['criteria'][1]['link']       = 'AND';

               $output['title'] = "<a style=\"font-size:14px;\" href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.php?" .
                                  Toolbox::append_params($options, '&amp;') . "\">" .
                                  Html::makeTitle(__('Your tickets in progress'), $number, $numrows) . "</a>";
         }

      }

      for ($i = 0; $i < $number; $i++) {
         $ID               = $DB->result($result, $i, "id");
         $output['body'][] = self::showVeryShort($ID, $forcetab);
      }

      if (!empty($output)) {
         $widget = new PluginMydashboardDatatable();

         $group = ($showgrouptickets) ? "group" : "";

         $widget->setWidgetTitle($output['title']);

         $widget->setWidgetId("ticketlist" . $status . "widget" . $group);
         //We set the datas of the widget (which will be later automatically formatted by the method getJSonData of PluginMydashboardDatatable)
         $widget->setTabNames($output['header']);

         $widget->setTabDatas($output['body']);

         //We sort by descending ticket ID
         $widget->setOption("aaSorting", [[0, "desc"]]);
         $widget->toggleWidgetRefresh();
         return $widget;
      }

      return "";
   }


   /**
    * @param $start
    * @param $status (default ''process)
    * @param $showgrouptickets (true by default)
    *
    * @return PluginMydashboardDatatable|string
    */
   static function showCentralTaskList($start, $status = "todo", $showgrouptickets = true) {
      global $CFG_GLPI;

      $req     = TicketTask::getTaskList($status, $showgrouptickets);
      $numrows = 0;
      if ($req !== false) {
         $numrows = $req->numrows();
      }

      $number = 0;
      //      $_SESSION['glpidisplay_count_on_home'] > 0 &&
      if ($req !== false) {
         $start = (int)$start;
         $limit = "";
         //         $limit  = (int)$_SESSION['glpidisplay_count_on_home'];
         $req    = TicketTask::getTaskList($status, $showgrouptickets, $start, $limit);
         $number = $req->numrows();
      }

      $itemtype = "TicketTask";
      $type     = "";
      if ($itemtype == "TicketTask") {
         $type = Ticket::getTypeName();
      } else if ($itemtype == "ProblemTask") {
         $type = Problem::getTypeName();
      }

      $output['header'][] = __('ID and priority', 'mydashboard');
      $output['header'][] = __('Title') . " (" . strtolower($type) . ")";
      $output['header'][] = __('Description');
      $output['header'][] = __('ID');
      $output['header'][] = __('Priority');
      $output['header'][] = __('Category');
      $output['body']     = [];
      $output['title']    = "default";

      //if ($numrows > 0) {
      $options['reset'] = 'reset';
      $forcetab         = '';
      $num              = 0;

      switch ($status) {

         case "todo" :
            $options['criteria'][0]['field']      = 12; // status
            $options['criteria'][0]['searchtype'] = 'equals';
            $options['criteria'][0]['value']      = "notold";
            $options['criteria'][0]['link']       = 'AND';
            if ($showgrouptickets) {
               $options['criteria'][1]['field']      = 112; // tech in charge of task
               $options['criteria'][1]['searchtype'] = 'equals';
               $options['criteria'][1]['value']      = 'mygroups';
               $options['criteria'][1]['link']       = 'AND';
            } else {
               $options['criteria'][1]['field']      = 95; // tech in charge of task
               $options['criteria'][1]['searchtype'] = 'equals';
               $options['criteria'][1]['value']      = $_SESSION['glpiID'];
               $options['criteria'][1]['link']       = 'AND';
            }
            $options['criteria'][2]['field']      = 33; // task status
            $options['criteria'][2]['searchtype'] = 'equals';
            $options['criteria'][2]['value']      = Planning::TODO;
            $options['criteria'][2]['link']       = 'AND';

            if ($itemtype == "TicketTask") {
               $title = __("Ticket tasks to do");
            } else if ($itemtype == "ProblemTask") {
               $title = __("Problem tasks to do");
            }
            $output['title'] = "<a style=\"font-size:14px;\" href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.php?" .
                               Toolbox::append_params($options, '&amp;') . "\">" .
                               Html::makeTitle($title, $number, $numrows) . "</a>";
            break;
      }
      if ($req !== false) {
         foreach ($req as $id => $row) {
            $output['body'][] = self::showVeryShortTask($id, $itemtype);
         }
      }
      if (!empty($output)) {
         $widget = new PluginMydashboardDatatable();

         $group = ($showgrouptickets) ? "group" : "";

         $widget->setWidgetTitle($output['title']);

         $widget->setWidgetId("tickettasks" . $status . "widget" . $group);
         //We set the datas of the widget (which will be later automatically formatted by the method getJSonData of PluginMydashboardDatatable)
         $widget->setTabNames($output['header']);

         $widget->setTabDatas($output['body']);

         //We sort by descending ticket ID
         $widget->setOption("aaSorting", [[0, "desc"]]);
         $widget->toggleWidgetRefresh();
         return $widget;
      }

      return "";
   }

   /**
    * @param $ID
    * @param $forcetab  string   name of the tab to force at the display (default '')
    *
    * @return array
    */
   static function showVeryShort($ID, $forcetab) {
      global $CFG_GLPI;

      $colnum = 0;
      $output = [];

      // Prints a job in short form
      // Should be called in a <table>-segment
      // Print links or not in case of user view
      // Make new job object and fill it from database, if success, print it
      $showprivate = Session::haveRight("show_full_ticket", 1);

      $job  = new Ticket();
      $rand = mt_rand();
      if ($job->getFromDBwithData($ID, 0)) {
         $bgcolor   = $_SESSION["glpipriority_" . $job->fields["priority"]];
         $textColor = "color:black!important;";
         if ($bgcolor == '#000000') {
            $textColor = "color:white!important;";
         }

         $link = "<a style='$textColor' id='ticket" . $job->fields["id"] . $rand . "' href='" . $CFG_GLPI["root_doc"] .
                 "/front/ticket.form.php?id=" . $job->fields["id"];
         if ($forcetab != '') {
            $link .= "&amp;forcetab=" . $forcetab;
         }
         $link .= "'>";


         $output[$colnum] = "<div class='center' style='background-color:$bgcolor; padding: 10px;'>" .
                            $link .
                            sprintf(__('%1$s: %2$s'), __('ID'), $job->fields["id"]) . "</a></div>";

         $colnum++;
         $output[$colnum] = '';
         $userrequesters  = $job->getUsers(CommonITILActor::REQUESTER);
         if (isset($userrequesters)
             && count($userrequesters)
         ) {
            foreach ($userrequesters as $d) {
               if ($d["users_id"] > 0) {
                  $userdata = getUserName($d["users_id"], 2);
                  $name     = "<div class='b center'>" . $userdata['name'];
                  $name     = sprintf(__('%1$s %2$s'), $name,
                                      Html::showToolTip($userdata["comment"],
                                                        ['link'    => $userdata["link"],
                                                         'display' => false]));

                  $output[$colnum] .= $name . "</div>";
               } else {

                  $output[$colnum] .= $d['alternative_email'] . "&nbsp;";
               }

               $output[$colnum] .= "<br>";
            }
         }
         $grouprequester = $job->getGroups(CommonITILActor::REQUESTER);
         if (isset($grouprequester)
             && count($grouprequester)
         ) {
            foreach ($grouprequester as $d) {
               $output[$colnum] .= Dropdown::getDropdownName("glpi_groups", $d["groups_id"]) . "<br>";
            }
         }

         $colnum++;
         $output[$colnum] = '';
         if (!empty($job->hardwaredatas)) {
            foreach ($job->hardwaredatas as $hardwaredatas) {
               if ($hardwaredatas->canView()) {
                  $output[$colnum] .= $hardwaredatas->getTypeName() . " - ";
                  $output[$colnum] .= "<span class='b'>" . $hardwaredatas->getLink() . "</span><br/>";
               } else if ($hardwaredatas) {
                  $output[$colnum] .= $hardwaredatas->getTypeName() . " - ";
                  $output[$colnum] .= "<span class='b'>" . $hardwaredatas->getNameID() . "</span><br/>";
               }
            }
         } else {
            $output[$colnum] .= __('General');
         }

         $colnum++;

         $link            .= "<span class='b'>" . $job->getNameID() . "</span></a>";
         $link            = sprintf(__('%1$s (%2$s)'), $link,
                                    sprintf(__('%1$s - %2$s'), $job->numberOfFollowups($showprivate),
                                            $job->numberOfTasks($showprivate)));
         $link            = sprintf(__('%1$s %2$s'), $link,
                                    Html::showToolTip(nl2br(Glpi\RichText\RichText::getSafeHtml($job->fields['content'])),
                                                      ['applyto' => 'ticket' . $job->fields["id"] . $rand,
                                                       'display' => false]));
         $output[$colnum] = $link;

         //Ticket ID
         $colnum++;
         $link = "<a id='ticket" . $job->fields["id"] . $rand . "' href='" . $CFG_GLPI["root_doc"] .
                 "/front/ticket.form.php?id=" . $job->fields["id"];
         if ($forcetab != '') {
            $link .= "&amp;forcetab=" . $forcetab;
         }
         $link            .= "'>";
         $output[$colnum] = $link . "<span class='b'>" . $job->fields["id"] . "</span></a>";

         //Priority
         $colnum++;
         $bgcolor = $_SESSION["glpipriority_" . $job->fields["priority"]];

         $output[$colnum] = "<div class='center' style='background-color:$bgcolor; padding: 10px;$textColor'>
                                <span class='b'>" . $job->fields["priority"] . " - " . Ticket::getPriorityName($job->fields["priority"]) . "</span>
                             </div>";
         //Categories
         $colnum++;
         $config = new PluginMydashboardConfig();
         $config->getFromDB(1);
         $itilCategory = new ITILCategory();
         if ($itilCategory->getFromDB($job->fields['itilcategories_id'])) {
            $haystack = $itilCategory->getField('completename');
            $needle   = '>';
            $offset   = 0;
            $allpos   = [];

            while (($pos = strpos($haystack, $needle, $offset)) !== FALSE) {
               $offset   = $pos + 1;
               $allpos[] = $pos;
            }

            if (isset($allpos[$config->getField('levelCat') - 1])) {
               $pos = $allpos[$config->getField('levelCat') - 1];
            } else {
               $pos = strlen($haystack);
            }
            $output[$colnum] = "<span class='b'>" . substr($haystack, 0, $pos) . "</span>";
         } else {
            $output[$colnum] = "<span></span>";
         }

         //status
         $colnum++;
         $statusId        = $job->fields["status"];
         $statusArray     = Ticket::getAllowedStatusArray($statusId);
         $output[$colnum] = $statusArray[$statusId];
      }
      return $output;
   }


   /**
    * Very short table to display the task
    *
    * @param integer $ID The ID of the task
    * @param string  $itemtype The itemtype (TicketTask, ProblemTask)
    *
    * @return void
    * @since 9.2
    *
    */
   static function showVeryShortTask($ID, $itemtype) {
      global $DB, $CFG_GLPI;

      $colnum = 0;
      $output = [];

      $job  = new $itemtype();
      $rand = mt_rand();
      if ($job->getFromDB($ID)) {

         if ($DB->fieldExists($job->getTable(), 'tickets_id')) {
            $item_link = new Ticket();
            $item_link->getFromDB($job->fields['tickets_id']);
            $tab_name = "Ticket";
         } else if ($DB->fieldExists($job->getTable(), 'problems_id')) {
            $item_link = new Problem();
            $item_link->getFromDB($job->fields['problems_id']);
            $tab_name = "ProblemTask";
         }

         $bgcolor = $_SESSION["glpipriority_" . $item_link->fields["priority"]];

         $output[$colnum] = "<div class='center' style='background-color:$bgcolor; padding: 10px;'>" .
                            sprintf(__('%1$s: %2$s'), __('ID'), $job->fields["id"]) . "</div>";

         $colnum++;
         $output[$colnum] = $item_link->fields['name'];
         $colnum++;
         //echo "<td>";
         $link = "<a id='" . strtolower($item_link->getType()) . "ticket" . $item_link->fields["id"] . $rand . "' href='" . $CFG_GLPI["root_doc"] .
                 "/front/" . strtolower($item_link->getType()) . ".form.php?id=" . $item_link->fields["id"];
         $link .= "&amp;forcetab=" . $tab_name . "$1";
         $link .= "'>";

         $colnum++;

         $content = Glpi\Toolbox\Sanitizer::unsanitize($job->fields['content']);
         $link    .= "<span class='b'>" . $content . "</span></a>";

         $output[$colnum] = $link;

         //Ticket ID
         $colnum++;
         $link = "<a id='ticket" . $item_link->fields["id"] . $rand . "' href='" . $CFG_GLPI["root_doc"] .
                 "/front/ticket.form.php?id=" . $item_link->fields["id"];

         $link            .= "'>";
         $output[$colnum] = $link . "<span class='b'>" . $item_link->fields["id"] . "</span></a>";

         //Priority
         $colnum++;
         $bgcolor = $_SESSION["glpipriority_" . $item_link->fields["priority"]];

         $output[$colnum] = "<div class='center' style='background-color:$bgcolor; padding: 10px;color:white'>
                                <span>" . Ticket::getPriorityName($item_link->fields["priority"]) . "</span>
                             </div>";

         //Categories
         $colnum++;
         $config = new PluginMydashboardConfig();
         $config->getFromDB(1);
         $itilCategory = new ITILCategory();
         $itilCategory->getFromDB($item_link->fields['itilcategories_id']);

         $haystack = $itilCategory->getField('completename');
         $needle   = '>';
         $offset   = 0;
         $allpos   = [];

         while (($pos = strpos($haystack, $needle, $offset)) !== FALSE) {
            $offset   = $pos + 1;
            $allpos[] = $pos;
         }

         if (isset($allpos[$config->getField('levelCat') - 1])) {
            $pos = $allpos[$config->getField('levelCat') - 1];
         } else {
            $pos = strlen($haystack);
         }
         $output[$colnum] = "<span class='b'>" . substr($haystack, 0, $pos) . "</span>";

      }
      return $output;
   }

   /**
    * Get tickets count
    *
    * @param $foruser boolean : only for current login user as requester (false by default)
    *
    * @return PluginMydashboardDatatable
    */
   static function showCentralCount($foruser = false) {
      global $DB, $CFG_GLPI;

      // show a tab with count of jobs in the central and give link
      if (!Session::haveRight(Ticket::$rightname, Ticket::READALL) && !Ticket::canCreate()) {
         return false;
      }
      if (!Session::haveRight(Ticket::$rightname, Ticket::READALL)) {
         $foruser = true;
      }

      $output = [];

      $query = "SELECT `status`,
                       COUNT(*) AS COUNT
                FROM `glpi_tickets` ";

      if ($foruser) {
         $query .= " LEFT JOIN `glpi_tickets_users`
                        ON (`glpi_tickets`.`id` = `glpi_tickets_users`.`tickets_id`
                            AND `glpi_tickets_users`.`type` = '" . CommonITILActor::REQUESTER . "')";

         if (Session::haveRight(Ticket::$rightname, Ticket::READGROUP)
             && isset($_SESSION["glpigroups"])
             && count($_SESSION["glpigroups"])
         ) {
            $query .= " LEFT JOIN `glpi_groups_tickets`
                           ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id`
                               AND `glpi_groups_tickets`.`type` = '" . CommonITILActor::REQUESTER . "')";
         }
      }
      $dbu   = new DbUtils();
      $query .= $dbu->getEntitiesRestrictRequest("WHERE", "glpi_tickets");

      if ($foruser) {
         $query .= " AND (`glpi_tickets_users`.`users_id` = '" . Session::getLoginUserID() . "' ";

         if (Session::haveRight(Ticket::$rightname, Ticket::READGROUP)
             && isset($_SESSION["glpigroups"])
             && count($_SESSION["glpigroups"])
         ) {
            $groups = implode("','", $_SESSION['glpigroups']);
            $query  .= " OR `glpi_groups_tickets`.`groups_id` IN ('$groups') ";
         }
         $query .= ")";
      }
      $query_deleted = $query;

      $query         .= " AND `glpi_tickets`.`is_deleted` = 0
                         GROUP BY `status`";
      $query_deleted .= " AND `glpi_tickets`.`is_deleted` = 1
                         GROUP BY `status`";

      $result         = $DB->query($query);
      $result_deleted = $DB->query($query_deleted);

      $status = [];
      foreach (Ticket::getAllStatusArray() as $key => $val) {
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

      $options['criteria'][0]['field']      = 12;
      $options['criteria'][0]['searchtype'] = 'equals';
      $options['criteria'][0]['value']      = 'process';
      $options['criteria'][0]['link']       = 'AND';
      $options['reset']                     = 'reset';

      if (Session::getCurrentInterface() != "central") {
         $output['title'] = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/helpdesk.public.php?create_ticket=1\" class='pointer'>" .
                            __('Create a ticket') . "&nbsp;<i class='ti ti-plus'></i><span class='sr-only'>" . __s('Add') . "</span></a>";
      } else {
         $output['title'] = "<a style=\"font-size:14px;\" href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.php?" .
                            Toolbox::append_params($options, '&amp;') . "\">" . __('Ticket followup', 'mydashboard') . "</a>";
      }

      $output['header'][] = _n('Ticket', 'Tickets', 2);
      $output['header'][] = _x('quantity', 'Number');

      $count = 0;
      foreach ($status as $key => $val) {
         $options['criteria'][0]['value'] = $key;
         $output['body'][$count][0]       = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.php?" .
                                            Toolbox::append_params($options, '&amp;') . "\">" . Ticket::getStatus($key) . "</a>";
         $output['body'][$count][1]       = $val;
         $count++;
      }

      $options['criteria'][0]['value'] = 'all';
      $options['is_deleted']           = 1;
      $output['body'][$count][0]       = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.php?" .
                                         Toolbox::append_params($options, '&amp;') . "\">" . __('Deleted') . "</a>";
      $output['body'][$count][1]       = $number_deleted;

      $widget = new PluginMydashboardDatatable();
      $widget->setWidgetTitle($output['title']);
      $widget->setWidgetId("ticketcountwidget");
      //We set the datas of the widget (which will be later automatically formatted by the method getJSonData of PluginMydashboardDatatable)
      $widget->setTabNames($output['header']);
      $widget->setTabDatas($output['body']);

      //Here we set few otions concerning the jquery library Datatable, bSort for sorting, bPaginate for paginating ...
      if (count($output['body']) > 0) {
         $widget->setOption("bSort", false);
      }
      $widget->setOption("bPaginate", false);
      $widget->setOption("bFilter", false);
      $widget->setOption("bInfo", false);

      return $widget;
   }

   /**
    * @deprecated 9.5.0
    */
   static function getCommonSelect() {

      $SELECT = "";
      if (count($_SESSION["glpiactiveentities"])>1) {
         $SELECT .= ", `glpi_entities`.`completename` AS entityname,
                       `glpi_tickets`.`entities_id` AS entityID ";
      }

      return " DISTINCT `glpi_tickets`.*,
                        `glpi_itilcategories`.`completename` AS catname
                        $SELECT";
   }


   /**
    * @deprecated 9.5.0
    */
   static function getCommonLeftJoin() {

      $FROM = "";
      if (count($_SESSION["glpiactiveentities"])>1) {
         $FROM .= " LEFT JOIN `glpi_entities`
                        ON (`glpi_entities`.`id` = `glpi_tickets`.`entities_id`) ";
      }

      return " LEFT JOIN `glpi_groups_tickets`
                  ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id`)
               LEFT JOIN `glpi_tickets_users`
                  ON (`glpi_tickets`.`id` = `glpi_tickets_users`.`tickets_id`)
               LEFT JOIN `glpi_suppliers_tickets`
                  ON (`glpi_tickets`.`id` = `glpi_suppliers_tickets`.`tickets_id`)
               LEFT JOIN `glpi_itilcategories`
                  ON (`glpi_tickets`.`itilcategories_id` = `glpi_itilcategories`.`id`)
               LEFT JOIN `glpi_tickettasks`
                  ON (`glpi_tickets`.`id` = `glpi_tickettasks`.`tickets_id`)
               LEFT JOIN `glpi_items_tickets`
                  ON (`glpi_tickets`.`id` = `glpi_items_tickets`.`tickets_id`)
               $FROM";

   }

   /**
    * @return bool|PluginMydashboardDatatable
    */
   static function showCentralNewList() {
      global $DB, $CFG_GLPI;

      if (!Session::haveRight(Ticket::$rightname, Ticket::READALL)) {
         return false;
      }

      $output = [];
      $dbu    = new DbUtils();
      $query  = "SELECT " . self::getCommonSelect() . "
                FROM `glpi_tickets` " . self::getCommonLeftJoin() . "
                WHERE `status` = '" . Ticket::INCOMING . "' " .
                $dbu->getEntitiesRestrictRequest("AND", "glpi_tickets") . "
                      AND NOT `is_deleted`
                ORDER BY `glpi_tickets`.`date_mod` DESC
                LIMIT " . intval($_SESSION['glpilist_limit']);
      $result = $DB->query($query);

      $number = $DB->numrows($result);

      if ($number > 0) {
         Session::initNavigateListItems('Ticket');

         $options['criteria'][0]['field']      = 12;
         $options['criteria'][0]['searchtype'] = 'equals';
         $options['criteria'][0]['value']      = Ticket::INCOMING;
         $options['criteria'][0]['link']       = 'AND';
         $options['reset']                     = 'reset';

         //TRANS: %d is the number of new tickets
         $output['title'] = sprintf(_n('%d new ticket', '%d new tickets', $number), $number);
         $output['title'] .= "&nbsp;(<a style=\"font-size:14px;\" href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.php?" . Toolbox::append_params($options, '&amp;') . "\">" . __('Show all') . "</a>)";

         $output['header'] = self::commonListHeader();

         while ($data = $DB->fetchAssoc($result)) {
            Session::addToNavigateListItems('Ticket', $data["id"]);
            $output['body'][] = self::showShort($data["id"], 0);
         }
      } else {
         $output['title']  = __('New tickets', 'mydashboard');
         $output['header'] = self::commonListHeader();
         $output['body']   = [];
      }

      $widget = new PluginMydashboardDatatable();
      $widget->setWidgetTitle($output['title']);
      $widget->setWidgetId("ticketcountwidget2");
      //We set the datas of the widget (which will be later automatically formatted by the method getJSonData of PluginMydashboardDatatable)
      $widget->setTabNames($output['header']);
      $widget->setTabDatas($output['body']);

      //Here we set few otions concerning the jquery library Datatable, bSort for sorting, bPaginate for paginating ...
      $widget->setOption("bPaginate", false);
      $widget->setOption("bFilter", false);
      $widget->setOption("bInfo", false);
      $widget->toggleWidgetRefresh();
      return $widget;
   }


   /**
    * @return array
    */
   static function commonListHeader() {
      $items[] = __('ID');
      $items[] = __('Date');
      if (count($_SESSION["glpiactiveentities"]) > 1) {
         $items[] = __('Entity');
      }
      $items[] = __('Priority');
      $items[] = __('Requester');
      $items[] = __('Associated element');
      $items[] = __('Title');

      return $items;
   }

   /**
    * Display a line for a ticket
    *
    * @param $id                 Integer  ID of the ticket
    * @param $followups          Boolean  show followup columns
    * @param $output_type        Integer  type of output (default Search::HTML_OUTPUT)
    * @param $row_num            Integer  row number (default 0)
    * @param $id_for_massaction  Integer  default 0 means no massive action (default 0)
    *
    * @return array
    */
   static function showShort($id, $followups, $output_type = Search::HTML_OUTPUT) {
      global $CFG_GLPI;

      $output = [];
      $colnum = 0;

      $rand = mt_rand();

      /// TODO to be cleaned. Get datas and clean display links

      // Prints a job in short form
      // Should be called in a <table>-segment
      // Print links or not in case of user view
      // Make new job object and fill it from database, if success, print it
      $job = new Ticket();

      $showprivate = Session::haveRight('followup', ITILFollowup::SEEPRIVATE);

      if ($job->getFromDB($id)) {
         $bgcolor = $_SESSION["glpipriority_" . $job->fields["priority"]];

         // ID
         $first_col = sprintf(__('%1$s: %2$s'), __('ID'), $job->fields["id"]);
         if ($output_type == Search::HTML_OUTPUT) {

            $class     = CommonITILObject::getStatusClass($job->fields["status"]);
            $label     = CommonITILObject::getStatus($job->fields["status"]);
            $first_col .= "<br><i class='" . $class . "'
                                alt=\"" . $label . "\">";
         } else {
            $first_col = sprintf(__('%1$s - %2$s'), $first_col,
                                 Ticket::getStatus($job->fields["status"]));
         }

         $colnum++;
         $output[$colnum] = $first_col;

         // Date
         $colnum++;
         if ($job->fields['status'] == Ticket::CLOSED) {
            $output[$colnum] = sprintf(__('Closed on %s'),
                                       ($output_type == Search::HTML_OUTPUT ? '<br>' : '') .
                                       Html::convDateTime($job->fields['closedate']));
         } else if ($job->fields['status'] == Ticket::SOLVED) {
            $output[$colnum] = sprintf(__('Solved on %s'),
                                       ($output_type == Search::HTML_OUTPUT ? '<br>' : '') .
                                       Html::convDateTime($job->fields['solvedate']));
         } else if ($job->fields['begin_waiting_date']) {
            $output[$colnum] = sprintf(__('Put on hold on %s'),
                                       ($output_type == Search::HTML_OUTPUT ? '<br>' : '') .
                                       Html::convDateTime($job->fields['begin_waiting_date']));
         } else if ($job->fields['time_to_resolve']) {
            $output[$colnum] = sprintf(__('%1$s: %2$s'), __('Time to resolve'),
                                       ($output_type == Search::HTML_OUTPUT ? '<br>' : '') .
                                       Html::convDateTime($job->fields['time_to_resolve']));
         } else {
            $output[$colnum] = sprintf(__('Opened on %s'),
                                       ($output_type == Search::HTML_OUTPUT ? '<br>' : '') .
                                       Html::convDateTime($job->fields['date']));
         }

         // Entity
         if (count($_SESSION["glpiactiveentities"]) > 1) {
            $colnum++;
            $output[$colnum] = Dropdown::getDropdownName('glpi_entities', $job->fields['entities_id']);
         }

         // Priority
         $colnum++;
         $output[$colnum] = "<span class='b'><div class='center' style='background-color:$bgcolor; padding: 10px;'>"
                            . Ticket::getPriorityName($job->fields["priority"]) . "</div></span>";

         // Requester
         $fourth_col     = "";
         $userrequesters = $job->getUsers(CommonITILActor::REQUESTER);
         if (isset($userrequesters)
             && count($userrequesters)
         ) {
            foreach ($userrequesters as $d) {
               $userdata   = getUserName($d["users_id"], 2);
               $fourth_col .= sprintf(__('%1$s %2$s'),
                                      "<span class='b'>" . $userdata['name'] . "</span>",
                                      Html::showToolTip($userdata["comment"],
                                                        ['link'    => $userdata["link"],
                                                         'display' => false]));
               $fourth_col .= "<br>";
            }
         }
         $grouprequester = $job->getGroups(CommonITILActor::REQUESTER);
         if (isset($grouprequester)
             && count($grouprequester)
         ) {
            foreach ($grouprequester as $d) {
               $fourth_col .= Dropdown::getDropdownName("glpi_groups", $d["groups_id"]);
               $fourth_col .= "<br>";
            }
         }

         $colnum++;
         $output[$colnum] = $fourth_col;

         // Sixth Colum
         $sixth_col  = "";
         $is_deleted = false;
         if (!empty($job->fields["itemtype"])
             && ($job->fields["items_id"] > 0)
         ) {
            if ($item = getItemForItemtype($job->fields["itemtype"])) {
               if ($item->getFromDB($job->fields["items_id"])) {
                  $is_deleted = $item->isDeleted();

                  $sixth_col .= $item->getTypeName();
                  $sixth_col .= "<br><span class='b'>";
                  if ($item->canView()) {
                     $sixth_col .= $item->getLink(['linkoption' => $output_type == Search::HTML_OUTPUT]);
                  } else {
                     $sixth_col .= $item->getNameID();
                  }
                  $sixth_col .= "</span>";
               }
            }

         } else if (empty($job->fields["itemtype"])) {
            $sixth_col = __('General');
         }

         $colnum++;
         $output[$colnum] = $sixth_col;

         // Name ticket
         $eigth_column = "<span class='b'>" . $job->fields["name"] . "</span>&nbsp;";

         // Add link
         if ($job->canViewItem()) {
            $eigth_column = "<a id='ticket" . $job->fields["id"] . "$rand' href=\"" . $CFG_GLPI["root_doc"] .
                            "/front/ticket.form.php?id=" . $job->fields["id"] . "\">$eigth_column</a>";

            if ($followups
                && ($output_type == Search::HTML_OUTPUT)
            ) {
               $eigth_column .= ITILFollowup::showShortForTicket($job->fields["id"]);
            } else {
               $eigth_column = sprintf(__('%1$s (%2$s)'), $eigth_column,
                                       sprintf(__('%1$s - %2$s'),
                                               $job->numberOfFollowups($showprivate),
                                               $job->numberOfTasks($showprivate)));
            }
         }

         if ($output_type == Search::HTML_OUTPUT) {
            $eigth_column = sprintf(__('%1$s %2$s'), $eigth_column,
                                    Html::showToolTip($job->fields['content'],
                                                      ['display' => false,
                                                       'applyto' => "ticket" . $job->fields["id"] .
                                                                    $rand]));
         }

         $colnum++;
         $output[$colnum] = $eigth_column;
      }

      return $output;
   }

}
