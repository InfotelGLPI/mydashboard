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
 * Class PluginMydashboardAlert
 */
class PluginMydashboardAlert extends CommonDBTM {


   public function __construct($_options = []) {
      $this->options = $_options;

      $preference = new PluginMydashboardPreference();
      $preference->getFromDB(Session::getLoginUserID());
      $this->preferences = $preference->fields;
   }

   /**
    * @param CommonGLPI $item
    * @param int        $withtemplate
    *
    * @return string|translated
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if ($item->getType() == 'Reminder'
          || $item->getType() == 'Problem'
          || $item->getType() == 'PluginEventsmanagerEvent') {
         return _n('Alert Dashboard', 'Alerts Dashboard', 2, 'mydashboard');
      }
      return '';
   }

   /**
    * @param CommonGLPI $item
    * @param int        $tabnum
    * @param int        $withtemplate
    *
    * @return bool
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      $alert  = new self();
      $palert = new PluginMydashboardProblemAlert();
      switch ($item->getType()) {
         case "Reminder":
            $alert->showForm($item);
            break;
         case "Problem":
            $palert->showForItem($item);
            break;
         default :
            $alert->showForItem($item);
            break;
      }
      return true;
   }

   /**
    * @return array
    */
   function getWidgetsForItem() {
      return array(
         _n('Alert', 'Alerts', 2, 'mydashboard') => array(
            $this->getType() . "1" => _n('Network alert', 'Network alerts', 2, 'mydashboard') . "&nbsp;<i class='fa fa-info-circle'></i>",
            $this->getType() . "2" => _n('Scheduled maintenance', 'Scheduled maintenances', 2, 'mydashboard') . "&nbsp;<i class='fa fa-info-circle'></i>",
            $this->getType() . "3" => _n('Information', 'Informations', 2, 'mydashboard') . "&nbsp;<i class='fa fa-info-circle'></i>",
            $this->getType() . "4" => __("Incidents alerts", "mydashboard") . "&nbsp;<i class='fa fa-info-circle'></i>",
            $this->getType() . "5" => __("SLA Incidents alerts", "mydashboard") . "&nbsp;<i class='fa fa-info-circle'></i>",
            $this->getType() . "6" => __("GLPI Status", "mydashboard") . "&nbsp;<i class='fa fa-info-circle'></i>",
         )
      );
   }

   static function countForAlerts($public, $type) {
      global $DB;

      $now                 = date('Y-m-d H:i:s');
      $nb                  = 0;
      $restrict_visibility = "AND (`glpi_reminders`.`begin_view_date` IS NULL
                                    OR `glpi_reminders`.`begin_view_date` < '$now')
                              AND (`glpi_reminders`.`end_view_date` IS NULL
                                   OR `glpi_reminders`.`end_view_date` > '$now') ";

      $query = "SELECT COUNT(`glpi_reminders`.`id`) as cpt
                   FROM `glpi_reminders` "
               . Reminder::addVisibilityJoins()
               . "LEFT JOIN `glpi_plugin_mydashboard_alerts`"
               . "ON `glpi_reminders`.`id` = `glpi_plugin_mydashboard_alerts`.`reminders_id`"
               . "WHERE `glpi_plugin_mydashboard_alerts`.`type` = $type
                         $restrict_visibility ";

      if ($public == 0) {
         $query .= "AND " . Reminder::addVisibilityRestrict() . "";
      } else {
         $query .= "AND `glpi_plugin_mydashboard_alerts`.`is_public`";
      }


      $result = $DB->query($query);
      $ligne  = $DB->fetch_assoc($result);
      $nb     = $ligne['cpt'];

      return $nb;
   }

   /**
    * @param $widgetId
    *
    * @return PluginMydashboardHtml
    */
   function getWidgetContentForItem($widgetId, $opt = []) {
      global $CFG_GLPI, $DB;

      switch ($widgetId) {
         case $this->getType() . "1":
            $widget = new PluginMydashboardHtml();
            $widget->setWidgetHtmlContent($this->getAlertList(0, 0, true));
            $widget->setWidgetTitle(__('Network Monitoring', 'mydashboard'));
            //            $widget->toggleWidgetRefresh();
            return $widget;
            break;

         case $this->getType() . "2":
            $widget = new PluginMydashboardHtml();
            $datas  = $this->getMaintenanceList();
            $widget->setWidgetHtmlContent(
               $datas
            );
            $widget->setWidgetTitle(_n('Scheduled maintenance', 'Scheduled maintenances', 2, 'mydashboard'));
            //            $widget->toggleWidgetRefresh();
            return $widget;
            break;

         case $this->getType() . "3":
            $widget = new PluginMydashboardHtml();
            $datas  = $this->getInformationList();
            $widget->setWidgetHtmlContent(
               $datas
            );
            $widget->setWidgetTitle(_n('Information', 'Informations', 2, 'mydashboard'));
            //            $widget->toggleWidgetRefresh();
            return $widget;
            break;
         case $this->getType() . "4":

            $widget = new PluginMydashboardHtml();

            $colorstats1 = "#CCC";
            $colorstats2 = "#CCC";
            $colorstats3 = "#CCC";
            $colorstats4 = "#CCC";
            /*Stats1*/
            $search_assign = "1=1";
            $left          = "";
            if (isset($opt)) {
               if (isset($this->preferences['prefered_group'])
                   && $this->preferences['prefered_group'] > 0
                   && count($opt) < 1) {
                  $left             = "LEFT JOIN `glpi_groups_tickets`
                  ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id`) ";
                  $opt['groups_id'] = $this->preferences['prefered_group'];
               }
               if (isset($opt['groups_id']) && ($opt['groups_id'] != 0)) {
                  $left          = "LEFT JOIN `glpi_groups_tickets`
                  ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id`) ";
                  $search_assign = " (`glpi_groups_tickets`.`groups_id` = " . $opt['groups_id'] . "
                                    AND `glpi_groups_tickets`.`type` = '" . CommonITILActor::ASSIGN . "')";
               }
            }
            $q1 = "SELECT DISTINCT COUNT(`glpi_tickets`.`id`) AS nb
                        FROM `glpi_tickets`
                        $left
                        WHERE `glpi_tickets`.`is_deleted` = '0' ";
            $q1 .= getEntitiesRestrictRequest("AND", Ticket::getTable())
                   . " AND `glpi_tickets`.`status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ") 
            AND `glpi_tickets`.`priority` > 4 AND `glpi_tickets`.`type` = '" . Ticket::INCIDENT_TYPE . "' AND $search_assign";

            $r1             = $DB->query($q1);
            $stats_tickets1 = 0;
            $nb1            = $DB->numrows($r1);
            if ($nb1) {
               foreach ($DB->request($q1) as $data1) {
                  $stats_tickets1 = $data1['nb'];
               }
            }
            if ($stats_tickets1 > 0) {
               $colorstats1 = "indianred";
            }

            /*Stats2*/
            $search_assign = "1=1";
            $left          = "";

            $q2 = "SELECT DISTINCT COUNT(`glpi_problems`.`id`) AS nb
                        FROM `glpi_problems`
                        $left
                        WHERE `glpi_problems`.`is_deleted` = '0' ";
            $q2 .= getEntitiesRestrictRequest("AND", Problem::getTable())
                   . " AND `glpi_problems`.`status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ") 
            AND `glpi_problems`.`priority` > 4 AND $search_assign";

            $r2             = $DB->query($q2);
            $stats_tickets2 = 0;
            $nb2            = $DB->numrows($r2);
            if ($nb2) {
               foreach ($DB->request($q2) as $data6) {
                  $stats_tickets2 = $data6['nb'];
               }
            }
            if ($stats_tickets2 > 0) {
               $colorstats2 = "indianred";
            }

            /*Stats3*/
            $left = "";

            $q3 = "SELECT DISTINCT COUNT(`glpi_tickets`.`id`) AS nb
                        FROM `glpi_tickets`
                        $left
                        WHERE `glpi_tickets`.`is_deleted` = '0' ";
            $q3 .= getEntitiesRestrictRequest("AND", Ticket::getTable())
                   . " AND `glpi_tickets`.`status` IN (" . CommonITILObject::INCOMING . ") 
            AND `glpi_tickets`.`type` = '" . Ticket::INCIDENT_TYPE . "' ";

            $r3             = $DB->query($q3);
            $stats_tickets3 = 0;
            $nb3            = $DB->numrows($r3);
            if ($nb3) {
               foreach ($DB->request($q3) as $data3) {
                  $stats_tickets3 = $data3['nb'];
               }
            }
            if ($stats_tickets3 > 0) {
               $colorstats3 = "indianred";
            }

            /*Stats4*/
            $left = "";
            $search_assign = "1=1";
            if (isset($opt)) {
               if (isset($this->preferences['prefered_group'])
                   && $this->preferences['prefered_group'] > 0
                   && count($opt) < 1) {
                  $left             = "LEFT JOIN `glpi_groups_tickets`
                  ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id`) ";
                  $opt['groups_id'] = $this->preferences['prefered_group'];
               }
               if (isset($opt['groups_id']) && ($opt['groups_id'] != 0)) {
                  $left          = "LEFT JOIN `glpi_groups_tickets`
                  ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id`) ";
                  $search_assign = " (`glpi_groups_tickets`.`groups_id` = " . $opt['groups_id'] . "
                                    AND `glpi_groups_tickets`.`type` = '" . CommonITILActor::ASSIGN . "')";
               }
            }

            $search_assign .= " AND `glpi_tickets`.`id` NOT IN (SELECT `tickets_id` FROM `glpi_tickets_users` WHERE `glpi_tickets_users`.`type` = '" . CommonITILActor::ASSIGN . "') ";
            
            $q4 = "SELECT DISTINCT COUNT(`glpi_tickets`.`id`) AS nb
                        FROM `glpi_tickets`
                        $left
                        WHERE $search_assign AND NOT `glpi_tickets`.`is_deleted` ";
            $q4 .= getEntitiesRestrictRequest("AND", Ticket::getTable())
                   . " AND `glpi_tickets`.`status` NOT IN (" . CommonITILObject::CLOSED . ") ";

            $r4             = $DB->query($q4);
            $stats_tickets4 = 0;
            $nb4            = $DB->numrows($r4);
            if ($nb4) {
               foreach ($DB->request($q4) as $data4) {
                  $stats_tickets4 = $data4['nb'];
               }
            }
            if ($stats_tickets4 > 0) {
               $colorstats4 = "indianred";
            }

            $table = "<div class=\"tickets-stats\">";

            //////////////////////////////////////////

            if ($stats_tickets3 > 0) {
               $options3['reset']                     = 'reset';
               $options3['criteria'][0]['field']      = 12; // status
               $options3['criteria'][0]['searchtype'] = 'equals';
               $options3['criteria'][0]['value']      = "1";
               $options3['criteria'][0]['link']       = 'AND';

               $options3['criteria'][1]['field']      = 14; // type
               $options3['criteria'][1]['searchtype'] = 'equals';
               $options3['criteria'][1]['value']      = Ticket::INCIDENT_TYPE;
               $options3['criteria'][1]['link']       = 'AND';

               $stats3link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
                             Toolbox::append_params($options3, "&");
            }

            $table .= "<div class=\"nb\" style=\"color:$colorstats3\">";
            if ($stats_tickets3 > 0) {
               $table .= "<a style='color:$colorstats3' target='_blank' href=\"" . $stats3link . "\" title='".__('New incidents', 'mydashboard')."'>";
            }
            $table .= "<i style='color:$colorstats3' class=\"fa fa-exclamation-circle fa-3x fa-border\"></i>
               <h3><span class=\"counter count-number\" id=\"stats_tickets3\"></span></h3>";
            $table .= "<p class=\"count-text \">" . __('New incidents', 'mydashboard') . "</p>";
            if ($stats_tickets3 > 0) {
               $table .= "</a>";
            }
            $table .= "</div>";
            
            //////////////////////////////////////////

            if ($stats_tickets4 > 0) {
               $options4['reset']                     = 'reset';
               $options4['criteria'][0]['field']      = 12; // status
               $options4['criteria'][0]['searchtype'] = 'equals';
               $options4['criteria'][0]['value']      = "notclosed";
               $options4['criteria'][0]['link']       = 'AND';

               $options4['criteria'][1]['field']      = 5; // tech
               $options4['criteria'][1]['searchtype'] = 'contains';
               $options4['criteria'][1]['value']      = '^$';
               $options4['criteria'][1]['link']       = 'AND';

               $stats4link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
                             Toolbox::append_params($options4, "&");
            }

            $table .= "<div class=\"nb\" style=\"color:$colorstats4\">";
            if ($stats_tickets4 > 0) {
               $table .= "<a style='color:$colorstats4' target='_blank' href=\"" . $stats4link . "\" title='".__('Opened tickets without assigned technicians', 'mydashboard')."'>";
            }
            $table .= "<i style='color:$colorstats4;font-size:36px' class=\"fa fa-user-times fa-3x fa-border\"></i>
               <h3><span class=\"counter count-number\" id=\"stats_tickets4\"></span></h3>";
            $table .= "<p class=\"count-text \">" . __('Opened tickets without assigned technicians', 'mydashboard') . "</p>";
            if ($stats_tickets4 > 0) {
               $table .= "</a>";
            }
            $table .= "</div>";

            //////////////////////////////////////////

            if ($stats_tickets1 > 0) {
               $options1['reset']                     = 'reset';
               $options1['criteria'][0]['field']      = 12; // status
               $options1['criteria'][0]['searchtype'] = 'equals';
               $options1['criteria'][0]['value']      = "notold";
               $options1['criteria'][0]['link']       = 'AND';

               $options1['criteria'][1]['field']      = 3; // priority
               $options1['criteria'][1]['searchtype'] = 'equals';
               $options1['criteria'][1]['value']      = -5;
               $options1['criteria'][1]['link']       = 'AND';

               $options1['criteria'][2]['field']      = 14; // type
               $options1['criteria'][2]['searchtype'] = 'equals';
               $options1['criteria'][2]['value']      = Ticket::INCIDENT_TYPE;
               $options1['criteria'][2]['link']       = 'AND';

               $stats1link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
                             Toolbox::append_params($options1, "&");
            }

            $table .= "<div class=\"nb\" style=\"color:$colorstats1\">";
            if ($stats_tickets1 > 0) {
               $table .= "<a style='color:$colorstats1' target='_blank' href=\"" . $stats1link . "\" title='".__('Incidents with very high or major priority', 'mydashboard')."'>";
            }
            $table .= "<i style='color:$colorstats1' class=\"fa fa-exclamation-triangle fa-3x fa-border\"></i>
               <h3><span class=\"counter count-number\" id=\"stats_tickets1\"></span></h3>";
            $table .= "<p class=\"count-text \">" . __('Incidents with very high or major priority', 'mydashboard') . "</p>";
            if ($stats_tickets1 > 0) {
               $table .= "</a>";
            }
            $table .= "</div>";

            //////////////////////////////////////////

            if ($stats_tickets2 > 0) {
               $options2['reset']                     = 'reset';
               $options2['criteria'][0]['field']      = 12; // status
               $options2['criteria'][0]['searchtype'] = 'equals';
               $options2['criteria'][0]['value']      = "notold";
               $options2['criteria'][0]['link']       = 'AND';

               $options2['criteria'][1]['field']      = 3; // priority
               $options2['criteria'][1]['searchtype'] = 'equals';
               $options2['criteria'][1]['value']      = -5;
               $options2['criteria'][1]['link']       = 'AND';

               $stats2link = $CFG_GLPI["root_doc"] . '/front/problem.php?is_deleted=0&' .
                             Toolbox::append_params($options2, "&");
            }

            $table .= "<div class=\"nb\" style=\"color:$colorstats2\">";
            if ($stats_tickets2 > 0) {
               $table .= "<a style='color:$colorstats2' target='_blank' href=\"" . $stats2link . "\" title='".__('Problems with very high or major priority', 'mydashboard')."'>";
            }
            $table .= "<i style='color:$colorstats2' class=\"fa fa-bug fa-3x fa-border\"></i>
                           <h3><span class=\"counter count-number\" id=\"stats_tickets2\"></span></h3>";
            $table .= "<p class=\"count-text \">" . __('Problems with very high or major priority', 'mydashboard') . "</p>";
            if ($stats_tickets2 > 0) {
               $table .= "</a>";
            }
            $table .= "</div>";

            //////////////////////////////////////////

            $table .= "<script type='text/javascript'>
                         $(function(){
                            $('#stats_tickets1').countup($stats_tickets1);
                            $('#stats_tickets2').countup($stats_tickets2);
                            $('#stats_tickets3').countup($stats_tickets3);
                            $('#stats_tickets4').countup($stats_tickets4);
                         });
                  </script>";

            $table .= "</div>";

            if (isset($this->preferences['prefered_group'])
                && $this->preferences['prefered_group'] > 0
                && count($opt) < 1) {
               $opt['groups_id'] = $this->preferences['prefered_group'];
            }
            $gsid   = PluginMydashboardWidget::getGsID($widgetId);
            $table  .= PluginMydashboardHelper::getFormHeader($widgetId, $gsid, false);
            $params = ['name'      => 'groups_id',
                       'display'   => false,
                       'value'     => isset($opt['groups_id']) ? $opt['groups_id'] : 0,
                       'entity'    => $_SESSION['glpiactiveentities'],
                       'condition' => '`is_assign`'
            ];
            //            $params['on_change'] = "refreshWidget('$widgetId', '$gsid', this.value);";
            $table .= __('Group');
            $table .= "&nbsp;";
            $table .= Group::dropdown($params)
                      . "</form>";

            $widget->setWidgetHtmlContent(
               $table
            );
            $widget->toggleWidgetRefresh();

            $widget->setWidgetTitle(__("Incidents alerts", "mydashboard"));
            $widget->setWidgetComment(__("Display alerts for tickets and problems", "mydashboard"));

            return $widget;
            break;

         case $this->getType() . "5":

            $widget = new PluginMydashboardHtml();

            $colorstats2 = "#CCC";
            $colorstats3 = "#CCC";
            $colorstats4 = "#CCC";
            $colorstats5 = "#CCC";

            /*Stats2*/
            $search_assign = "1=1";
            $left          = "";
            $stats2        = 0;
            if (isset($opt)) {
               if (isset($this->preferences['prefered_group'])
                   && $this->preferences['prefered_group'] > 0
                   && count($opt) < 1) {
                  $left             = "LEFT JOIN `glpi_groups_tickets`
                  ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id`) ";
                  $opt['groups_id'] = $this->preferences['prefered_group'];
               }
               if (isset($opt['groups_id']) && ($opt['groups_id'] != 0)) {
                  $left          = "LEFT JOIN `glpi_groups_tickets`
                  ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id`) ";
                  $search_assign = " (`glpi_groups_tickets`.`groups_id` = " . $opt['groups_id'] . "
                                    AND `glpi_groups_tickets`.`type` = '" . CommonITILActor::ASSIGN . "')";
               }
            }
            $q2 = "SELECT DISTINCT COUNT(`glpi_tickets`.`id`) AS nb
                           FROM `glpi_tickets`
                           $left
                           WHERE `glpi_tickets`.`is_deleted` = '0' ";
            $q2 .= getEntitiesRestrictRequest("AND", Ticket::getTable())
                   . " AND $search_assign AND `glpi_tickets`.`status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ") 
                         AND `glpi_tickets`.`type` = '" . Ticket::INCIDENT_TYPE . "'
                         AND (`glpi_tickets`.`takeintoaccount_delay_stat` = '0'
                         AND `glpi_tickets`.`time_to_own` > NOW())";

            $r2  = $DB->query($q2);
            $nb2 = $DB->numrows($r2);
            if ($nb2) {
               foreach ($DB->request($q2) as $data2) {
                  $stats2 = $data2['nb'];
               }
            }
            if ($stats2 > 0) {
               $colorstats2 = "indianred";
            }
            /*Stats3*/
            $search_assign = "1=1";
            $left          = "";
            $stats3        = 0;
            if (isset($opt)) {
               if (isset($this->preferences['prefered_group'])
                   && $this->preferences['prefered_group'] > 0
                   && count($opt) < 1) {
                  $left             = "LEFT JOIN `glpi_groups_tickets`
                  ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id`) ";
                  $opt['groups_id'] = $this->preferences['prefered_group'];
               }
               if (isset($opt['groups_id']) && ($opt['groups_id'] != 0)) {
                  $left          = "LEFT JOIN `glpi_groups_tickets`
                  ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id`) ";
                  $search_assign = " (`glpi_groups_tickets`.`groups_id` = " . $opt['groups_id'] . "
                                    AND `glpi_groups_tickets`.`type` = '" . CommonITILActor::ASSIGN . "')";
               }
            }
            $q3 = "SELECT DISTINCT COUNT(`glpi_tickets`.`id`) AS nb
                           FROM `glpi_tickets`
                           $left
                           WHERE `glpi_tickets`.`is_deleted` = '0' ";
            $q3 .= getEntitiesRestrictRequest("AND", Ticket::getTable())
                   . " AND $search_assign AND `glpi_tickets`.`status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ") 
                         AND `glpi_tickets`.`type` = '" . Ticket::INCIDENT_TYPE . "'
                         AND (`glpi_tickets`.`solve_delay_stat` = '0'
                         AND `glpi_tickets`.`time_to_resolve` > NOW())";

            $r3  = $DB->query($q3);
            $nb3 = $DB->numrows($r3);

            if ($nb3) {
               foreach ($DB->request($q3) as $data3) {
                  $stats3 = $data3['nb'];
               }
            }
            if ($stats3 > 0) {
               $colorstats3 = "indianred";
            }

            /*Stats4*/
            $search_assign = "1=1";
            $left          = "";
            $stats4        = 0;
            if (isset($opt)) {
               if (isset($this->preferences['prefered_group'])
                   && $this->preferences['prefered_group'] > 0
                   && count($opt) < 1) {
                  $left             = "LEFT JOIN `glpi_groups_tickets`
                  ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id`) ";
                  $opt['groups_id'] = $this->preferences['prefered_group'];
               }
               if (isset($opt['groups_id']) && ($opt['groups_id'] != 0)) {
                  $left          = "LEFT JOIN `glpi_groups_tickets`
                  ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id`) ";
                  $search_assign = " (`glpi_groups_tickets`.`groups_id` = " . $opt['groups_id'] . "
                                    AND `glpi_groups_tickets`.`type` = '" . CommonITILActor::ASSIGN . "')";
               }
            }
            $q4 = "SELECT DISTINCT COUNT(`glpi_tickets`.`id`) AS nb
                                       FROM `glpi_tickets`
                                       $left
                                       WHERE `glpi_tickets`.`is_deleted` = '0' ";
            $q4 .= getEntitiesRestrictRequest("AND", Ticket::getTable())
                   . " AND $search_assign AND `glpi_tickets`.`status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ") 
                         AND `glpi_tickets`.`type` = '" . Ticket::INCIDENT_TYPE . "'
                         AND (`glpi_tickets`.`takeintoaccount_delay_stat` = '0'
                         AND `glpi_tickets`.`time_to_own` < NOW())";

            $r4  = $DB->query($q4);
            $nb4 = $DB->numrows($r4);
            if ($nb4) {
               foreach ($DB->request($q4) as $data4) {
                  $stats4 = $data4['nb'];
               }
            }
            if ($stats4 > 0) {
               $colorstats4 = "indianred";
            }

            /*Stats5*/
            $search_assign = "1=1";
            $left          = "";
            $stats5        = 0;
            if (isset($opt)) {
               if (isset($this->preferences['prefered_group'])
                   && $this->preferences['prefered_group'] > 0
                   && count($opt) < 1) {
                  $left             = "LEFT JOIN `glpi_groups_tickets`
                  ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id`) ";
                  $opt['groups_id'] = $this->preferences['prefered_group'];
               }
               if (isset($opt['groups_id']) && ($opt['groups_id'] != 0)) {
                  $left          = "LEFT JOIN `glpi_groups_tickets`
                  ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id`) ";
                  $search_assign = " (`glpi_groups_tickets`.`groups_id` = " . $opt['groups_id'] . "
                                    AND `glpi_groups_tickets`.`type` = '" . CommonITILActor::ASSIGN . "')";
               }
            }
            $q5 = "SELECT DISTINCT COUNT(`glpi_tickets`.`id`) AS nb
                                       FROM `glpi_tickets`
                                       $left
                                       WHERE `glpi_tickets`.`is_deleted` = '0' ";
            $q5 .= getEntitiesRestrictRequest("AND", Ticket::getTable())
                   . " AND $search_assign AND `glpi_tickets`.`status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ") 
                         AND `glpi_tickets`.`type` = '" . Ticket::INCIDENT_TYPE . "'
                         AND (`glpi_tickets`.`solve_delay_stat` = '0'
                         AND `glpi_tickets`.`time_to_resolve` < NOW())";
            //print_r($opt);
            $r5  = $DB->query($q5);
            $nb5 = $DB->numrows($r5);
            if ($nb5) {
               foreach ($DB->request($q5) as $data5) {
                  $stats5 = $data5['nb'];
               }
            }
            if ($stats5 > 0) {
               $colorstats5 = "indianred";
            }

            $table = "<div class=\"tickets-stats\">";
            if ($stats2 > 0) {
               $options2['reset']                     = 'reset';
               $options2['criteria'][0]['field']      = 12; // status
               $options2['criteria'][0]['searchtype'] = 'equals';
               $options2['criteria'][0]['value']      = "notold";
               $options2['criteria'][0]['link']       = 'AND';

               $options2['criteria'][1]['field']      = 14; // type
               $options2['criteria'][1]['searchtype'] = 'equals';
               $options2['criteria'][1]['value']      = Ticket::INCIDENT_TYPE;
               $options2['criteria'][1]['link']       = 'AND';

               $options2['criteria'][2]['field']      = 155; // time_to_own
               $options2['criteria'][2]['searchtype'] = 'morethan';
               $options2['criteria'][2]['value']      = 'NOW';
               $options2['criteria'][2]['link']       = 'AND';

               if (isset($opt['groups_id']) && $opt['groups_id'] > 0) {
                  $group = $opt['groups_id'];

                  $options2['criteria'][3]['field']      = 8; // groups_id_assign
                  $options2['criteria'][3]['searchtype'] = 'equals';
                  $options2['criteria'][3]['value']      = $group;
                  $options2['criteria'][3]['link']       = 'AND';

               }
               $options2['criteria'][4]['field']      = 150; // takeintoaccount_delay_stat
               $options2['criteria'][4]['searchtype'] = 'contains';
               $options2['criteria'][4]['value']      = 0;
               $options2['criteria'][4]['link']       = 'AND';

               $stats2link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
                             Toolbox::append_params($options2, "&");
            }

            $table .= "<div class=\"nb\" style=\"color:$colorstats2\">";
            if ($stats2 > 0) {
               $table .= "<a style='color:$colorstats2' target='_blank' href=\"" . $stats2link . "\">";
            }
            $table .= "<i style='color:$colorstats2' class=\"fa fa-exclamation-circle fa-3x fa-border\"></i>
               <h3><span class=\"counter count-number\" id=\"stats2\"></span></h3>
               <p class=\"count-text \">" . __('Incidents where time to own will be exceeded', 'mydashboard') . "</p>";
            if ($stats2 > 0) {
               $table .= "</a>";
            }
            $table .= "</div>";
            if ($stats3 > 0) {
               $options3['reset']                     = 'reset';
               $options3['criteria'][0]['field']      = 12; // status
               $options3['criteria'][0]['searchtype'] = 'equals';
               $options3['criteria'][0]['value']      = "notold";
               $options3['criteria'][0]['link']       = 'AND';

               $options3['criteria'][1]['field']      = 14; // type
               $options3['criteria'][1]['searchtype'] = 'equals';
               $options3['criteria'][1]['value']      = Ticket::INCIDENT_TYPE;
               $options3['criteria'][1]['link']       = 'AND';

               $options3['criteria'][2]['field']      = 18; // time_to_resolve
               $options3['criteria'][2]['searchtype'] = 'morethan';
               $options3['criteria'][2]['value']      = 'NOW';
               $options3['criteria'][2]['link']       = 'AND';

               if (isset($opt['groups_id']) && $opt['groups_id'] > 0) {
                  $group = $opt['groups_id'];

                  $options3['criteria'][3]['field']      = 8; // groups_id_assign
                  $options3['criteria'][3]['searchtype'] = 'equals';
                  $options3['criteria'][3]['value']      = $group;
                  $options3['criteria'][3]['link']       = 'AND';

               }
               $options3['criteria'][4]['field']      = 154; // solve_delay_stat
               $options3['criteria'][4]['searchtype'] = 'contains';
               $options3['criteria'][4]['value']      = 0;
               $options3['criteria'][4]['link']       = 'AND';

               $stats3link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
                             Toolbox::append_params($options3, "&");
            }

            $table .= "<div class=\"nb\" style=\"color:$colorstats3\">";
            if ($stats3 > 0) {
               $table .= "<a style='color:$colorstats3' target='_blank' href=\"" . $stats3link . "\">";
            }
            $table .= "<i style='color:$colorstats3' class=\"fa fa-times-circle fa-3x fa-border\"></i>
               <h3><span class=\"counter count-number\" id=\"stats3\"></span></h3>
               <p class=\"count-text \">" . __('Incidents where time to resolve will be exceeded', 'mydashboard') . "</p>";
            if ($stats3 > 0) {
               $table .= "</a>";
            }
            $table .= "</div>";


            if ($stats4 > 0) {
               $options4['reset']                     = 'reset';
               $options4['criteria'][0]['field']      = 12; // status
               $options4['criteria'][0]['searchtype'] = 'equals';
               $options4['criteria'][0]['value']      = "notold";
               $options4['criteria'][0]['link']       = 'AND';

               $options4['criteria'][1]['field']      = 14; // type
               $options4['criteria'][1]['searchtype'] = 'equals';
               $options4['criteria'][1]['value']      = Ticket::INCIDENT_TYPE;
               $options4['criteria'][1]['link']       = 'AND';

               $options4['criteria'][2]['field']      = 155; // time_to_own
               $options4['criteria'][2]['searchtype'] = 'lessthan';
               $options4['criteria'][2]['value']      = 'NOW';
               $options4['criteria'][2]['link']       = 'AND';

               if (isset($opt['groups_id']) && $opt['groups_id'] > 0) {
                  $group = $opt['groups_id'];

                  $options4['criteria'][3]['field']      = 8; // groups_id_assign
                  $options4['criteria'][3]['searchtype'] = 'equals';
                  $options4['criteria'][3]['value']      = $group;
                  $options4['criteria'][3]['link']       = 'AND';

               }
               $options4['criteria'][4]['field']      = 150; // takeintoaccount_delay_stat
               $options4['criteria'][4]['searchtype'] = 'contains';
               $options4['criteria'][4]['value']      = 0;
               $options4['criteria'][4]['link']       = 'AND';

               $stats4link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
                             Toolbox::append_params($options4, "&");
            }

            $table .= "<div class=\"nb\" style=\"color:$colorstats4\">";
            if ($stats4 > 0) {
               $table .= "<a style='color:$colorstats4' target='_blank' href=\"" . $stats4link . "\">";
            }
            $table .= "<i style='color:$colorstats4' class=\"fa fa-exclamation-circle fa-3x fa-border\"></i>
                           <h3><span class=\"counter count-number\" id=\"stats4\"></span></h3>
                           <p class=\"count-text \">" . __('Incidents where time to own is exceeded', 'mydashboard') . "</p>";
            if ($stats4 > 0) {
               $table .= "</a>";
            }
            $table .= "</div>";

            if ($stats5 > 0) {
               $options5['reset']                     = 'reset';
               $options5['criteria'][0]['field']      = 12; // status
               $options5['criteria'][0]['searchtype'] = 'equals';
               $options5['criteria'][0]['value']      = "notold";
               $options5['criteria'][0]['link']       = 'AND';

               $options5['criteria'][1]['field']      = 14; // type
               $options5['criteria'][1]['searchtype'] = 'equals';
               $options5['criteria'][1]['value']      = Ticket::INCIDENT_TYPE;
               $options5['criteria'][1]['link']       = 'AND';

               $options5['criteria'][2]['field']      = 18; // time_to_resolve
               $options5['criteria'][2]['searchtype'] = 'lessthan';
               $options5['criteria'][2]['value']      = 'NOW';
               $options5['criteria'][2]['link']       = 'AND';

               if (isset($opt['groups_id']) && $opt['groups_id'] > 0) {
                  $group = $opt['groups_id'];

                  $options5['criteria'][3]['field']      = 8; // groups_id_assign
                  $options5['criteria'][3]['searchtype'] = 'equals';
                  $options5['criteria'][3]['value']      = $group;
                  $options5['criteria'][3]['link']       = 'AND';

               }
               $options5['criteria'][4]['field']      = 154; // solve_delay_stat
               $options5['criteria'][4]['searchtype'] = 'contains';
               $options5['criteria'][4]['value']      = 0;
               $options5['criteria'][4]['link']       = 'AND';

               $stats5link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
                             Toolbox::append_params($options5, "&");
            }

            $table .= "<div class=\"nb\" style=\"color:$colorstats5\">";
            if ($stats5 > 0) {
               $table .= "<a style='color:$colorstats5' target='_blank' href=\"" . $stats5link . "\">";
            }
            $table .= "<i style='color:$colorstats5' class=\"fa fa-times-circle fa-3x fa-border\"></i>
                           <h3><span class=\"counter count-number\" id=\"stats5\"></span></h3>
                           <p class=\"count-text \">" . __('Incidents where time to resolve is exceeded', 'mydashboard') . "</p>";
            if ($stats5 > 0) {
               $table .= "</a>";
            }
            $table .= "</div>";

            $table .= "<script type='text/javascript'>
                         $(function(){
                            $('#stats2').countup($stats2);
                            $('#stats3').countup($stats3);
                            $('#stats4').countup($stats4);
                            $('#stats5').countup($stats5);
                         });
                  </script>";

            $table .= "</div>";

            if (isset($this->preferences['prefered_group'])
                && $this->preferences['prefered_group'] > 0
                && count($opt) < 1) {
               $opt['groups_id'] = $this->preferences['prefered_group'];
            }
            $gsid   = PluginMydashboardWidget::getGsID($widgetId);
            $table  .= PluginMydashboardHelper::getFormHeader($widgetId, $gsid, false);
            $params = ['name'      => 'groups_id',
                       'display'   => false,
                       'value'     => isset($opt['groups_id']) ? $opt['groups_id'] : 0,
                       'entity'    => $_SESSION['glpiactiveentities'],
                       'condition' => '`is_assign`'
            ];
            //            $params['on_change'] = "refreshWidget('$widgetId', '$gsid', this.value);";
            $table .= __('Group');
            $table .= "&nbsp;";
            $table .= Group::dropdown($params)
                      . "</form>";

            $widget->setWidgetHtmlContent(
               $table
            );
            $widget->toggleWidgetRefresh();

            $widget->setWidgetTitle(__("SLA Incidents alerts", "mydashboard"));
            $widget->setWidgetComment(__("Display alerts for SLA of tickets", "mydashboard"));

            return $widget;
            break;

         case $this->getType() . "6":

            $widget = new PluginMydashboardHtml();
            $url    = $CFG_GLPI['url_base'] . "/status.php";
            //            $url = "http://localhost/glpi/status.php";
            $options = ["url" => $url];

            $contents = self::cURLData($options);
            $contents = nl2br($contents);

            $table = self::handleShellcommandResult($contents, $url);
            if (!empty($contents)) {
               $table .= "<div class='md-status'>";
               $table .= $contents;
               $table .= "</div>";
            }
            $widget->setWidgetHtmlContent(
               $table
            );
            //            $widget->toggleWidgetRefresh();

            $widget->setWidgetTitle(__("GLPI Status", "mydashboard"));
            $widget->setWidgetComment(__("Check if GLPI have no problem", "mydashboard"));

            return $widget;
            break;
      }
   }


   /**
    * @param int $public
    *
    * @return string
    */
   static function getMaintenanceMessage($public = false) {
      if (self::countForAlerts($public, 1) > 0) {
         echo __('There is at least on planned scheduled maintenance. Please log on to see more', 'mydashboard');
      }
   }

   /**
    * @param int $public
    *
    * @return string
    */
   function getMaintenanceList() {
      global $DB, $CFG_GLPI;

      $now = date('Y-m-d H:i:s');
      $wl  = "";

      $wl            .= "<div class='weather_block'>";
      $restrict_user = '1';
      // Only personal on central so do not keep it
      //      if ($_SESSION['glpiactiveprofile']['interface'] == 'central') {
      //         $restrict_user = "`glpi_reminders`.`users_id` <> '".Session::getLoginUserID()."'";
      //      }

      $restrict_visibility = "AND (`glpi_reminders`.`begin_view_date` IS NULL
                                    OR `glpi_reminders`.`begin_view_date` < '$now')
                              AND (`glpi_reminders`.`end_view_date` IS NULL
                                   OR `glpi_reminders`.`end_view_date` > '$now') ";

      $query = "SELECT `glpi_reminders`.`id`,
                       `glpi_reminders`.`name`,
                       `glpi_reminders`.`text`,
                       `glpi_reminders`.`date`,
                       `glpi_reminders`.`begin_view_date`,
                       `glpi_reminders`.`end_view_date`
                   FROM `glpi_reminders` "
               . Reminder::addVisibilityJoins()
               . "LEFT JOIN `" . $this->getTable() . "`"
               . "ON `glpi_reminders`.`id` = `" . $this->getTable() . "`.`reminders_id`"
               . "WHERE $restrict_user
                         $restrict_visibility ";

      $query .= "AND " . Reminder::addVisibilityRestrict() . "";

      $query .= "AND `" . $this->getTable() . "`.`type` = 1
                   ORDER BY `glpi_reminders`.`name`";


      $result = $DB->query($query);
      $nb     = $DB->numrows($result);
      if ($nb) {

         $wl .= "<div id='maint-div'>";
         $wl .= "<ul>";
         while ($row = $DB->fetch_array($result)) {
            $wl .= "<li>";
            $wl .= "<div class='bt-row'>";
            $wl .= "<div class=\"bt-col-xs-4 center alert-title-div \">";
            $wl .= "<i class='fa fa-exclamation-triangle fa-alert-7 fa-alert-orange' aria-hidden='true'></i>";
            $wl .= "</div>";
            $wl .= "<div class=\"bt-col-xs-8 left \">";
            $wl .= "<h3>";
            $wl .= $row['name'];
            $wl .= "</h3>";
            $wl .= "</div>";
            $wl .= "<div class=\"bt-col-xs-12 alert-content-div \">";
            $wl .= Toolbox::unclean_html_cross_side_scripting_deep($row["text"]);
            $wl .= "</div>";
            $wl .= "</div>";
            $wl .= "</li>";

         }
         $wl .= "</ul>";
         $wl .= "</div>";
         $wl .= "<script type='text/javascript'>
                  $(function() {
                     $('#maint-div').vTicker({
                        speed: 500,
                        pause: 6000,
                        showItems: 1,
                        animate: 'fade',
                        mousePause: true,
                        height: 0,
                        direction: 'up'
                     });
                  });
               </script>";
      } else {

         $wl .= "<div align='center'><h3><span class ='maint-color'>";
         $wl .= __("No scheduled maintenance", "mydashboard");
         $wl .= "</span></h3></div>";
      }
      $wl .= "</div>";
      return $wl;
   }


   /**
    * @param int $public
    *
    * @return string
    */
   function getInformationList() {
      global $DB, $CFG_GLPI;

      $now = date('Y-m-d H:i:s');
      $wl  = "";

      $wl            .= "<div class='weather_block'>";
      $restrict_user = '1';
      // Only personal on central so do not keep it
      //      if ($_SESSION['glpiactiveprofile']['interface'] == 'central') {
      //         $restrict_user = "`glpi_reminders`.`users_id` <> '".Session::getLoginUserID()."'";
      //      }

      $restrict_visibility = "AND (`glpi_reminders`.`begin_view_date` IS NULL
                                    OR `glpi_reminders`.`begin_view_date` < '$now')
                              AND (`glpi_reminders`.`end_view_date` IS NULL
                                   OR `glpi_reminders`.`end_view_date` > '$now') ";

      $query = "SELECT `glpi_reminders`.`id`,
                       `glpi_reminders`.`name`,
                       `glpi_reminders`.`text`,
                       `glpi_reminders`.`date`,
                       `glpi_reminders`.`begin_view_date`,
                       `glpi_reminders`.`end_view_date`
                   FROM `glpi_reminders` "
               . Reminder::addVisibilityJoins()
               . "LEFT JOIN `" . $this->getTable() . "`"
               . "ON `glpi_reminders`.`id` = `" . $this->getTable() . "`.`reminders_id`"
               . "WHERE $restrict_user
                         $restrict_visibility ";

      $query .= "AND " . Reminder::addVisibilityRestrict() . "";

      $query .= "AND `" . $this->getTable() . "`.`type` = 2
                   ORDER BY `glpi_reminders`.`name`";


      $result = $DB->query($query);
      $nb     = $DB->numrows($result);

      if ($nb) {

         $wl .= "<div id='info-div'>";
         $wl .= "<ul>";
         while ($row = $DB->fetch_array($result)) {
            $wl .= "<li>";
            $wl .= "<div class='bt-row'>";
            $wl .= "<div class=\"bt-col-xs-12 center \">";
            $wl .= "<h3>";
            $wl .= $row['name'];
            $wl .= "</h3>";
            $wl .= "</div>";
            $wl .= "<div class=\"bt-col-xs-12 center \">";
            $wl .= Toolbox::unclean_html_cross_side_scripting_deep($row["text"]);
            $wl .= "</div>";
            $wl .= "</div>";
            $wl .= "</li>";
         }
         $wl .= "</ul>";
         $wl .= "</div>";
         $wl .= "<script type='text/javascript'>
                  $(function() {
                     $('#info-div').vTicker({
                        speed: 500,
                        pause: 6000,
                        showItems: 1,
                        animate: false,
                        mousePause: true,
                        height: 0,
                        direction: 'right'
                     });
                  });
               </script>";

      } else {

         $wl .= "<div align='center'><h3><span class ='maint-color'>";
         $wl .= __("No informations founded", "mydashboard");
         $wl .= "</span></h3></div>";
      }
      $wl .= "</div>";

      return $wl;
   }


   /**
    * @param int $public
    *
    * @return string
    */
   function getAlertList($public = 0, $force = 0) {
      global $DB;

      $now = date('Y-m-d H:i:s');

      $wl            = "";
      $wl            .= "<div class='weather_block'>";
      $restrict_user = '1';
      // Only personal on central so do not keep it
      //      if ($_SESSION['glpiactiveprofile']['interface'] == 'central') {
      //         $restrict_user = "`glpi_reminders`.`users_id` <> '".Session::getLoginUserID()."'";
      //      }

      $restrict_visibility = "AND (`glpi_reminders`.`begin_view_date` IS NULL
                                    OR `glpi_reminders`.`begin_view_date` < '$now')
                              AND (`glpi_reminders`.`end_view_date` IS NULL
                                   OR `glpi_reminders`.`end_view_date` > '$now') ";

      $query = "SELECT `glpi_reminders`.`id`,
                       `glpi_reminders`.`name`,
                       `glpi_reminders`.`text`,
                       `glpi_reminders`.`date`,
                       `glpi_reminders`.`begin_view_date`,
                       `glpi_reminders`.`end_view_date`,
                       `" . $this->getTable() . "`.`impact`,
                       `" . $this->getTable() . "`.`is_public`
                   FROM `glpi_reminders` "
               . Reminder::addVisibilityJoins()
               . "LEFT JOIN `" . $this->getTable() . "`"
               . "ON `glpi_reminders`.`id` = `" . $this->getTable() . "`.`reminders_id`"
               . "WHERE $restrict_user
                         $restrict_visibility ";

      if ($public == 0) {
         $query .= "AND " . Reminder::addVisibilityRestrict() . "";
      } else {
         $query .= "AND `" . $this->getTable() . "`.`is_public`";
      }
      $query .= "AND `" . $this->getTable() . "`.`impact` IS NOT NULL 
                 AND `" . $this->getTable() . "`.`type` = 0
                   ORDER BY `glpi_reminders`.`name`";

      $result = $DB->query($query);
      $nb     = $DB->numrows($result);

      if ($nb) {

         $wl .= "<div id='alert-div'>";
         $wl .= "<ul>";

         while ($row = $DB->fetch_array($result)) {

            $wl .= "<li>";

            $wl .= "<div class='bt-row'>";
            $wl .= "<div class=\"bt-col-xs-4 center \">";

            if ($row['impact'] == 5) {
               $class = "fa-thermometer-4 fa-alert-red";
            } else if ($row['impact'] == 4) {
               $class = "fa-thermometer-3 fa-alert-lightred";
            } else if ($row['impact'] == 3) {
               $class = "fa-thermometer-2 fa-alert-orange";
            } else if ($row['impact'] == 2) {
               $class = "fa-thermometer-1 fa-alert-yellow";
            } else if ($row['impact'] == 1) {
               $class = "fa-thermometer-0 fa-alert-green";
            }

            $wl .= "<i class='fa $class fa-alert-7'></i>";

            $wl .= "</div>";

            $wl .= "<div class=\"bt-col-xs-8 alert-title-div\">";
            $wl .= "<h3>";

            $classfont = ' alert_fontimpact' . $row['impact'];
            $rand      = mt_rand();
            //            $name = (Session::haveRight("reminder_public", READ)) ?
            //               "<a  href='" . Reminder::getFormURL() . "?id=" . $row['id'] . "'>" . $row['name'] . "</a>"
            //               : $row['name'];
            $name = $row['name'];
            $wl   .= "<div id='alert$rand'>";
            $wl   .= "<span class='$classfont left'>" . $name . "</span>";
            $wl   .= "</div>";
            $wl   .= "</h3>";
            //            if (isset($row['begin_view_date'])
            //                && isset($row['end_view_date'])
            //            ) {
            //               $wl .= "<span class='alert_date'>" . Html::convDateTime($row['begin_view_date']) . "<br> " . Html::convDateTime($row['end_view_date']) . "</span><br>";
            //            }

            $wl .= "</div>";
            $wl .= "</div>";

            $wl .= "<div class='bt-row'>";
            $wl .= "<div class=\"bt-col-xs-12 alert-content-div\">";
            $wl .= Toolbox::unclean_html_cross_side_scripting_deep($row["text"]);
            $wl .= "</div>";
            $wl .= "</div>";

            $wl .= "</li>";
         }
         $wl .= "</ul>";
         $wl .= "</div>";

         $wl .= "<script type='text/javascript'>
                  $(function() {
                     $('#alert-div').vTicker({
                        speed: 500,
                        pause: 6000,
                        showItems: 1,
                        animation: 'fade',
                        mousePause: true,
                        height: 0,
                        direction: 'up'
                     });
                  });
               </script>";
      } else {

         $wl .= "<div align='center'><h3><span class ='alert-color'>";
         $wl .= __("No problem detected", "mydashboard");
         $wl .= "</span></h3></div>";
      }
      $wl .= "</div>";

      return $wl;
   }

   /**
    * @param int $public
    *
    * @return string
    */
   function getAlertSummary($public = 0, $force = 0) {
      global $DB;

      echo Html::css("/lib/font-awesome-4.7.0/css/font-awesome.min.css");
      echo Html::css("/plugins/mydashboard/css/mydashboard.css");
      echo Html::css("/plugins/mydashboard/css/style_bootstrap_main.css");
      $now = date('Y-m-d H:i:s');

      $restrict_user = '1';
      // Only personal on central so do not keep it
      //      if ($_SESSION['glpiactiveprofile']['interface'] == 'central') {
      //         $restrict_user = "`glpi_reminders`.`users_id` <> '".Session::getLoginUserID()."'";
      //      }

      $restrict_visibility = "AND (`glpi_reminders`.`begin_view_date` IS NULL
                                    OR `glpi_reminders`.`begin_view_date` < '$now')
                              AND (`glpi_reminders`.`end_view_date` IS NULL
                                   OR `glpi_reminders`.`end_view_date` > '$now') ";

      $query = "SELECT `glpi_reminders`.`id`,
                       `glpi_reminders`.`name`,
                       `glpi_reminders`.`text`,
                       `glpi_reminders`.`date`,
                       `glpi_reminders`.`begin_view_date`,
                       `glpi_reminders`.`end_view_date`,
                       `" . $this->getTable() . "`.`impact`,
                       `" . $this->getTable() . "`.`is_public`
                   FROM `glpi_reminders` "
               . Reminder::addVisibilityJoins()
               . "LEFT JOIN `" . $this->getTable() . "`"
               . "ON `glpi_reminders`.`id` = `" . $this->getTable() . "`.`reminders_id`"
               . "WHERE $restrict_user
                         $restrict_visibility ";

      if ($public == 0) {
         $query .= "AND " . Reminder::addVisibilityRestrict() . "";
      } else {
         $query .= "AND `" . $this->getTable() . "`.`is_public`";
      }
      $query .= "AND `" . $this->getTable() . "`.`impact` IS NOT NULL 
                 AND `" . $this->getTable() . "`.`type` = 0
                   ORDER BY `glpi_reminders`.`name`";

      $wl     = "";
      $result = $DB->query($query);
      $nb     = $DB->numrows($result);

      if ($nb) {
         while ($row = $DB->fetch_array($result)) {

            if ($row['impact'] == 1) {
               $f1[]   = $row;
               $list[] = $row;
            } else if ($row['impact'] == 2) {
               $f2[]   = $row;
               $list[] = $row;
            } else if ($row['impact'] == 3) {
               $f3[]   = $row;
               $list[] = $row;
            } else if ($row['impact'] == 4) {
               $f4[]   = $row;
               $list[] = $row;
            } else if ($row['impact'] == 5) {
               $f5[]   = $row;
               $list[] = $row;
            }
         }

         if (!empty($f5)) {
            $wl .= $this->displayContent('5', $list, $public, $force);
         } elseif (!empty($f4)) {
            $wl .= $this->displayContent('4', $list, $public, $force);
         } elseif (!empty($f3)) {
            $wl .= $this->displayContent('3', $list, $public, $force);
         } elseif (!empty($f2)) {
            $wl .= $this->displayContent('2', $list, $public, $force);
         } elseif (!empty($f1)) {
            $wl .= $this->displayContent('1', $list, $public, $force);
         }
      }
      if (!$nb && ($public == 0 || $force == 1)) {
         $wl .= $this->displayContent('1', array(), 0, $force);
      }

      return $wl;
   }

   /**
    * @param       $type
    * @param array $list
    * @param int   $public
    *
    * @return string
    */
   private
   function displayContent($impact, $list = array(), $public = 0, $force = 0) {

      $div = "";

      if ($impact == '5') {
         $class = "fa-thermometer-4 fa-alert-red";
      } else if ($impact == '4') {
         $class = "fa-thermometer-3 fa-alert-lightred";
      } else if ($impact == '3') {
         $class = "fa-thermometer-2 fa-alert-orange";
      } else if ($impact == '2') {
         $class = "fa-thermometer-1 fa-alert-yellow";
      } else if ($impact == '1') {
         $class = "fa-thermometer-0 fa-alert-green";
      }

      $div .= "<div class='bt-row weather_public_block'>";
      $div .= "<div class='center'><h3>" . __("Monitoring", "mydashboard") . "</h3></div>";
      $div .= "<div class=\"bt-col-xs-4 right \">";
      $div .= "<i class='fa $class fa-alert-4'></i>";
      $div .= "</div>";
      $div .= "<div class=\"bt-col-xs-8 alert-title-div\">";
      $div .= "<div class='weather_msg'>";
      $div .= $this->getMessage($list, $public);
      $div .= "</div>";
      $div .= "</div>";
      $div .= "</div>";
      return $div;
   }

   /**
    * @param $list
    * @param $public
    *
    * @return string
    */
   private
   function getMessage($list, $public) {

      $l = "";
      if (!empty($list)) {
         foreach ($list as $listitem) {

            //            $class     = (Html::convDate(date("Y-m-d")) == Html::convDate($listitem['date'])) ? 'alert_new' : '';
            $class     = ' alert_impact' . $listitem['impact'];
            $classfont = ' alert_fontimpact' . $listitem['impact'];
            $rand      = mt_rand();
            $name      = (Session::haveRight("reminder_public", READ)) ?
               "<a  href='" . Reminder::getFormURL() . "?id=" . $listitem['id'] . "'>" . $listitem['name'] . "</a>"
               : $listitem['name'];

            $l .= "<div id='alert$rand'>";
            $l .= "<span class='alert_impact $class'></span>";
            //            if (isset($listitem['begin_view_date'])
            //                && isset($listitem['end_view_date'])
            //            ) {
            //               $l .= "<span class='alert_date'>" . Html::convDateTime($listitem['begin_view_date']) . " - " . Html::convDateTime($listitem['end_view_date']) . "</span><br>";
            //            }
            $l .= "<span class='$classfont'>" . $name . "</span>";
            $l .= "</div>";
         }
      } else {
         $l .= "<div align='center'><h3><span class ='alert-color'>";
         $l .= __("No problem detected", "mydashboard");
         $l .= "</span></h3></div>";
      }
      $l .= "<br>";

      return $l;
   }

   /**
    * @param Reminder $item
    */
   private
   function showForm(Reminder $item) {
      $reminders_id = $item->getID();

      $this->getFromDBByQuery("WHERE `reminders_id` = '" . $reminders_id . "'");

      if (isset($this->fields['id'])) {
         $id        = $this->fields['id'];
         $impact    = $this->fields['impact'];
         $type      = $this->fields['type'];
         $is_public = $this->fields['is_public'];
      } else {
         $id        = -1;
         $type      = 0;
         $impact    = 0;
         $is_public = 0;
      }
      echo "<form action='" . $this->getFormURL() . "' method='post' >";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='2'>" . _n('Alert', 'Alerts', 2, 'mydashboard') . "</th></tr>";

      $types    = array();
      $types[0] = _n('Alert', 'Alerts', 1, 'mydashboard');
      $types[1] = _n('Scheduled maintenance', 'Scheduled maintenances', 1, 'mydashboard');
      $types[2] = _n('Information', 'Informations', 1, 'mydashboard');
      echo "<tr class='tab_bg_2'><td>" . __("Type") . "</td><td>";
      Dropdown::showFromArray('type', $types, array(
                                       'value' => $type
                                    )
      );
      echo "</td></tr>";

      $impacts    = array();
      $impacts[0] = __("No impact", "mydashboard");
      for ($i = 1; $i <= 5; $i++) {
         $impacts[$i] = CommonITILObject::getImpactName($i);
      }

      echo "<tr class='tab_bg_2'><td>" . __("Alert level", "mydashboard") . "</td><td>";
      Dropdown::showFromArray('impact', $impacts, array(
                                         'value' => $impact
                                      )
      );
      echo "</td></tr>";
      echo "<tr class='tab_bg_2'><td>" . __("Public") . "</td><td>";
      Dropdown::showYesNo('is_public', $is_public);

      echo "</td></tr>";
      if (Session::haveRight("reminder_public", UPDATE)) {
         echo "<tr class='tab_bg_1 center'><td colspan='2'>";
         echo "<input type='submit' name='update' value=\"" . _sx('button', 'Save') . "\" class='submit'>";
         echo "<input type='hidden' name='id' value=" . $id . ">";
         echo "<input type='hidden' name='reminders_id' value=" . $reminders_id . ">";
         echo "</td></tr>";
      }
      echo "</table>";
      Html::closeForm();
   }


   private
   function showForItem($item) {
      global $CFG_GLPI;

      $items_id = $item->getID();
      $item->getFromDB($items_id);
      $itemtype = $item->getType();
      $reminder = new Reminder();

      if (!isset($item->fields['reminders_id'])) {

         echo "<table class='tab_cadre_fixe'>";
         echo "<th>" . PluginMydashboardMenu::getTypeName(2) . "</th>";
         echo "<tr class='tab_bg_1'><td class='center'>";
         echo "<button type='submit' onclick=\"createAlert('$itemtype', $items_id)\">" . __("Create a new alert", "mydashboard") . "</button>";
         echo '<script>
            function createAlert(itemtype, items_id) {
              $conf = confirm("' . __('Create a new alert', 'mydashboard') . '");
              if($conf){
                  $.ajax({
                      url: "' . $CFG_GLPI['root_doc'] . '/plugins/mydashboard/ajax/createalert.php",
                      type: "POST",
                      data: { "itemtype": itemtype, "items_id": items_id},
                      success: function()
                          {
                              window.location.reload()
                          }
                  });
                }
              }

            </script>';
         echo "</td></tr>";
         echo "</table>";
      } else {
         $reminders_id = $item->fields['reminders_id'];
      }


      if (isset($item->fields['reminders_id'])) {
         $reminder->getFromDB($reminders_id);
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr>";
         echo "<th colspan='2'>" . __('Linked reminder', 'mydashboard') . "</a></th>";
         echo "</tr>";
         echo "<tr class='tab_bg_2'>";
         echo "<td>" . __("Name") . "</td>";
         echo "<td>";
         echo nl2br($reminder->getLink());
         echo "</td>";
         echo "</tr>";
         echo "<tr class='tab_bg_2'>";
         echo "<td>" . __("Comment") . "</td>";
         echo "<td>";
         echo nl2br($reminder->fields['text']);
         echo "</td>";
         echo "</tr>";
         echo "</table>";

         $this->getFromDBByQuery("WHERE `reminders_id` = '" . $reminders_id . "'");

         if (isset($this->fields['id'])) {
            $id        = $this->fields['id'];
            $impact    = $this->fields['impact'];
            $type      = $this->fields['type'];
            $is_public = $this->fields['is_public'];
         } else {
            $id        = -1;
            $type      = 0;
            $impact    = 0;
            $is_public = 0;
         }
         echo "<form action='" . $this->getFormURL() . "' method='post' >";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='2'>" . _n('Alert', 'Alerts', 2, 'mydashboard') . "</th></tr>";

         $types    = array();
         $types[0] = _n('Alert', 'Alerts', 1, 'mydashboard');
         $types[1] = _n('Scheduled maintenance', 'Scheduled maintenances', 1, 'mydashboard');
         $types[2] = _n('Information', 'Informations', 1, 'mydashboard');

         echo "<tr class='tab_bg_2'><td>" . __("Type") . "</td><td>";
         Dropdown::showFromArray('type', $types, array(
                                          'value' => $type
                                       )
         );
         echo "</td></tr>";

         $impacts    = array();
         $impacts[0] = __("No impact", "mydashboard");
         for ($i = 1; $i <= 5; $i++) {
            $impacts[$i] = CommonITILObject::getImpactName($i);
         }

         echo "<tr class='tab_bg_2'><td>" . __("Alert level", "mydashboard") . "</td><td>";
         Dropdown::showFromArray('impact', $impacts, array(
                                            'value' => $impact
                                         )
         );
         echo "</td></tr>";
         echo "<tr class='tab_bg_2'><td>" . __("Public") . "</td><td>";
         Dropdown::showYesNo('is_public', $is_public);

         echo "</td></tr>";
         if (Session::haveRight("reminder_public", UPDATE)) {
            echo "<tr class='tab_bg_1 center'><td colspan='2'>";
            echo "<input type='submit' name='update' value=\"" . _sx('button', 'Save') . "\" class='submit'>";
            echo "<input type='hidden' name='id' value=" . $id . ">";
            echo "<input type='hidden' name='reminders_id' value=" . $reminders_id . ">";
            echo "</td></tr>";
         }
         echo "</table>";
         Html::closeForm();

         $reminder->showVisibility();
      }
   }


   static function getWidgetMydashboardAlert($class) {

      if (PluginMydashboardAlert::countForAlerts(0, 0) > 0) {
         $display = "<div class=\"bt-feature $class \">";
         $display .= "<h3 class=\"bt-title-divider\">";
         $display .= "<span>";
         $display .= __('Network Monitoring', 'mydashboard');
         $display .= "</span>";
         $display .= "<small>" . __('A network alert can impact you and will avoid creating a ticket', 'mydashboard') . "</small>";
         $display .= "</h3>";
         $display .= "<div id=\"display-sc\">";
         $alerts  = new self();
         $display .= $alerts->getAlertList(0, 1);
         $display .= "</div>";
         $display .= "</div>";

         return $display;
      } else {
         return false;
      }
   }

   /**
    * @param $message
    * @param $url
    *
    * @return string
    */
   static function handleShellcommandResult($message, $url) {
      global $CFG_GLPI;

      $alert = "";
      if (preg_match('/PROBLEM/is', $message)) {
         $alert .= "<div class='md-title-status' style='color:darkred'><i class='fa fa-exclamation-circle fa-4x'></i><br><br";
         $alert .= "<b>";
         $alert .= __("Problem with GLPI", "mydashboard");
         $alert .= "</b></div>";
      } elseif (preg_match('/OK/is', $message)) {
         $alert .= "<div class='md-title-status' style='color:forestgreen'><i class='fa fa-check-circle-o fa-4x'></i><br><br>";
         $alert .= "<b>";
         $alert .= __("GLPI is OK", "mydashboard");
         $alert .= "</b></div>";
      } else {
         $alert .= "<div class='md-title-status' style='color:orange'><i class='fa fa-warning fa-4x'></i><br><br>";
         $alert .= "<b>";
         $alert .= __("Alert is not properly configured or is not reachable", "mydashboard");
         $alert .= "</b>";
         $alert .= "<br><br><a href='$url' target='_blank'>" . $url . "</a></div>";
      }

      return $alert;
   }

   /**
    * @param $options
    *
    * @return mixed|string
    */
   static function cURLData($options) {
      global $CFG_GLPI;

      if (!function_exists('curl_init')) {
         return __('Curl PHP package not installed', 'mydashboard') . "\n";
      }
      $data        = '';
      $timeout     = 15;
      $proxy_host  = $CFG_GLPI["proxy_name"] . ":" . $CFG_GLPI["proxy_port"]; // host:port
      $proxy_ident = $CFG_GLPI["proxy_user"] . ":" .
                     Toolbox::decrypt($CFG_GLPI["proxy_passwd"], GLPIKEY); // username:password

      $url = $options["url"];

      $ch = curl_init();

      curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
      curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

      if (preg_match('`^https://`i', $options["url"])) {
         curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
         curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
      }
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_COOKIEFILE, "cookiefile");
      curl_setopt($ch, CURLOPT_COOKIEJAR, "cookiefile"); # SAME cookiefile

      //Do we have post field to send?
      if (!empty($options["post"])) {
         //curl_setopt($ch, CURLOPT_POST,true);
         $post = '';
         foreach ($options['post'] as $key => $value) {
            $post .= $key . '=' . $value . '&';
         }
         rtrim($post, '&');
         curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type:application/x-www-form-urlencoded"));
         curl_setopt($ch, CURLOPT_POST, true);
         curl_setopt($ch, CURLOPT_POSTREDIR, 2);
         curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
      }

      //if (!$options["download"]) {
      //curl_setopt($ch, CURLOPT_HEADER, 1);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
      //}

      // Activation de l'utilisation d'un serveur proxy
      if (!empty($CFG_GLPI["proxy_name"])) {
         //curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, true);

         // Définition de l'adresse du proxy
         curl_setopt($ch, CURLOPT_PROXY, $proxy_host);

         // Définition des identifiants si le proxy requiert une identification
         if ($proxy_ident) {
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_ident);
         }
      }
      //if ($options["download"]) {
      //   $fp = fopen($options["file"], "w");
      //   curl_setopt($ch, CURLOPT_FILE, $fp);
      //   curl_exec($ch);
      //} else {
      $data = curl_exec($ch);
      //}

      if (
         //!$options["download"] &&
      !$data
      ) {
         $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
         curl_close($ch); // make sure we closeany current curl sessions
         //die($http_code.' Unable to connect to server. Please come back later.');
      } else {
         curl_close($ch);
      }

      //if ($options["download"]) {
      //fclose($fp);
      //}
      if (
         //!$options["download"] &&
      $data
      ) {
         return $data;
      }
   }
}
