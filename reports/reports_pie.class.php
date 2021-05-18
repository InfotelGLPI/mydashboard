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
 * Class PluginMydashboardReports_Pie
 */
class PluginMydashboardReports_Pie extends CommonGLPI {

   private       $options;
   private       $pref;
   public static $reports = [2, 7, 12, 13, 16, 17, 18, 20, 25, 26, 27, 30, 31];



   /**
    * PluginMydashboardReports_Pie constructor.
    *
    * @param array $_options
    */
   public function __construct($_options = []) {
      $this->options = $_options;

      $preference = new PluginMydashboardPreference();
      if (Session::getLoginUserID() !== false
          && !$preference->getFromDB(Session::getLoginUserID())) {
         $preference->initPreferences(Session::getLoginUserID());
      }
      $preference->getFromDB(Session::getLoginUserID());
      $this->preferences = $preference->fields;
   }

   /**
    * @return array
    */
   public function getWidgetsForItem() {

      $isDebug = $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE;

      $widgets =
         [
            __('Pie charts', "mydashboard") => [
               $this->getType() . "2"  => (($isDebug) ? "2 " : "") . __("Number of opened tickets by priority", "mydashboard") . "&nbsp;<i class='fas fa-chart-pie'></i>",
               $this->getType() . "7"  => (($isDebug) ? "7 " : "") . __("Top ten ticket requesters by month", "mydashboard") . "&nbsp;<i class='fas fa-chart-pie'></i>",
               $this->getType() . "12" => (($isDebug) ? "12 " : "") . __("TTR Compliance", "mydashboard") . "&nbsp;<i class='fas fa-chart-pie'></i>",
               $this->getType() . "13" => (($isDebug) ? "13 " : "") . __("TTO Compliance", "mydashboard") . "&nbsp;<i class='fas fa-chart-pie'></i>",
               $this->getType() . "16" => (($isDebug) ? "16 " : "") . __("Number of opened incidents by category", "mydashboard") . "&nbsp;<i class='fas fa-chart-pie'></i>",
               $this->getType() . "17" => (($isDebug) ? "17 " : "") . __("Number of opened requests by category", "mydashboard") . "&nbsp;<i class='fas fa-chart-pie'></i>",
               $this->getType() . "18" => (($isDebug) ? "18 " : "") . __("Number of opened, closed and unplanned tickets by month", "mydashboard") . "&nbsp;<i class='fas fa-chart-pie'></i>",
               $this->getType() . "20" => (($isDebug) ? "20 " : "") . __("Percent of use of solution types", "mydashboard") . "&nbsp;<i class='fas fa-chart-pie'></i>",
               $this->getType() . "25" => (($isDebug) ? "25 " : "") . __("Top ten of opened tickets by requester groups", "mydashboard") . "&nbsp;<i class='fas fa-chart-pie'></i>",
               $this->getType() . "26" => (($isDebug) ? "26 " : "") . __("Global satisfaction level", "mydashboard") . "&nbsp;<i class='fas fa-chart-pie'></i>",
               $this->getType() . "27" => (($isDebug) ? "27 " : "") . __("Top ten of opened tickets by location", "mydashboard") . "&nbsp;<i class='fas fa-chart-pie'></i>",
               $this->getType() . "30" => (($isDebug) ? "30 " : "") . __("Number of use of request sources", "mydashboard") . "&nbsp;<i class='fas fa-chart-pie'></i>",
               $this->getType() . "31" => (($isDebug) ? "31 " : "") . __("Number of tickets per location per period", "mydashboard") . "&nbsp;<i class='fas fa-chart-pie'></i>",
               $this->getType() . "SC32" => (($isDebug) ? "31 " : "") . __("Global indicators by week", "mydashboard") . "&nbsp;<i class='fas fa-info-circle'></i>",

               ]
         ];
      return $widgets;
   }


   /**
    * @param       $widgetId
    * @param array $opt
    *
    * @return \PluginMydashboardDatatable|\PluginMydashboardHBarChart|\PluginMydashboardHtml|\PluginMydashboardLineChart|\PluginMydashboardPieChart|\PluginMydashboardVBarChart
    * @throws \GlpitestSQLError
    */
   public function getWidgetContentForItem($widgetId, $opt = []) {
      global $DB, $CFG_GLPI;
      $isDebug = $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE;
      $dbu     = new DbUtils();
      switch ($widgetId) {

         case $this->getType() . "2":
            $name = 'TicketsByPriorityPieChart';
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['entities_id',
                             'is_recursive',
                             'type',
                             'technicians_groups_id',
                             'group_is_recursive'];
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = ['type'];
            }

            $params  = ["preferences" => $this->preferences,
                        "criterias"   => $criterias,
                        "opt"         => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);

            $opt                        = $options['opt'];
            $crit                       = $options['crit'];
            $type                       = $opt['type'];
            $type_criteria              = $crit['type'];
            $entities_criteria          = $crit['entities_id'];
            $entities_id_criteria       = $crit['entity'];
            $sons_criteria              = $crit['sons'];
            $is_deleted                 = "`glpi_tickets`.`is_deleted` = 0";
            $technician_group           = $opt['technicians_groups_id'];
            $technician_groups_criteria = $crit['technicians_groups_id'];

            $query = "SELECT DISTINCT
                           `priority`,
                           COUNT(`id`) AS nb
                        FROM `glpi_tickets`
                        WHERE $is_deleted $type_criteria $entities_criteria $technician_groups_criteria";
            $query .= " AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ") ";
            $query .= " GROUP BY `priority` ORDER BY `priority` ASC";

            $colors = [];
            $result = $DB->query($query);
            $nb     = $DB->numrows($result);

            $name_priority = [];
            $datas         = [];
            $tabpriority   = [];
            if ($nb) {
               while ($data = $DB->fetchArray($result)) {
                  $name_priority[] = CommonITILObject::getPriorityName($data['priority']);
                  $colors[]        = $_SESSION["glpipriority_" . $data['priority']];
                  $datas[]         = $data['nb'];
                  $tabpriority[]   = $data['priority'];
               }
            }

            $widget = new PluginMydashboardHtml();
            $title  = __("Number of opened tickets by priority", "mydashboard");
            $widget->setWidgetTitle((($isDebug) ? "2 " : "") . $title);

            $dataPieset         = json_encode($datas);
            $backgroundPieColor = json_encode($colors);
            $labelsPie          = json_encode($name_priority);
            $tabpriorityset     = json_encode($tabpriority);
            $js_ancestors       = $crit['ancestors'];

            $graph_datas = ['name'            => $name,
                            'ids'             => $tabpriorityset,
                            'data'            => $dataPieset,
                            'labels'          => $labelsPie,
                            'label'           => $title,
                            'backgroundColor' => $backgroundPieColor];

            //            if ($onclick == 1) {
            $graph_criterias = ['entities_id'        => $entities_id_criteria,
                                'sons'               => $sons_criteria,
                                'technician_group'   => $technician_group,
                                'group_is_recursive' => $js_ancestors,
                                'type'               => $type,
                                'widget'             => $widgetId];
            //            }

            $graph = PluginMydashboardPieChart::launchPieGraph($graph_datas, $graph_criterias);

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => true,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => 1];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));

            $widget->setWidgetHtmlContent($graph);

            return $widget;
            break;

         case $this->getType() . "7":
            $name = 'TopTenTicketAuthorsPieChart';
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['entities_id',
                             'is_recursive',
                             'type',
                             'year',
                             'month',
                             'limit'];
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = ['type',
                             'year',
                             'month',
                             'limit'];
            }
            $opt['limit'] = isset($opt['limit']) ? $opt['limit'] : 10;
            $params       = ["preferences" => $this->preferences,
                             "criterias"   => $criterias,
                             "opt"         => $opt];
            $options      = PluginMydashboardHelper::manageCriterias($params);

            $opt  = $options['opt'];
            $crit = $options['crit'];

            $type_criteria     = $crit['type'];
            $entities_criteria = $crit['entities_id'];
            $date_criteria     = $crit['date'];
            $is_deleted        = "`glpi_tickets`.`is_deleted` = 0";
            $limit_query       = "";
            $limit             = isset($opt['limit']) ? $opt['limit'] : 10;
            if ($limit > 0) {
               $limit_query = "LIMIT $limit";
            }
            $query    = "SELECT IFNULL(`glpi_tickets_users`.`users_id`,-1) as users_id, COUNT(`glpi_tickets`.`id`) as count
                     FROM `glpi_tickets`
                     LEFT JOIN `glpi_tickets_users`
                        ON (`glpi_tickets_users`.`tickets_id` = `glpi_tickets`.`id` AND `glpi_tickets_users`.`type` = 1)
                     WHERE $date_criteria
                     $entities_criteria $type_criteria
                     AND $is_deleted
                     GROUP BY `glpi_tickets_users`.`users_id`
                     ORDER BY count DESC
                     $limit_query";
            $widget   = PluginMydashboardHelper::getWidgetsFromDBQuery('piechart', $query);
            $datas    = $widget->getTabDatas();
            $dataspie = [];
            $namespie = [];
            $nb       = count($datas);
            if ($nb > 0) {
               foreach ($datas as $k => $v) {
                  if ($k == 0) {
                     $name_user = __('Email');
                  } else if ($k == -1) {
                     $name_user = __('None');
                  } else if ($k > 0) {
                     $name_user = getUserName($k);
                  }
                  $dataspie[] = $v;
                  $namespie[] = $name_user;
                  unset($datas[$k]);
               }
            }

            $widget = new PluginMydashboardHtml();
            $title  = __("Top ten ticket requesters by month", "mydashboard");
            $widget->setWidgetTitle((($isDebug) ? "7 " : "") . $title);

            $palette = PluginMydashboardColor::getColors($nb);

            $dataPieset         = json_encode($dataspie);
            $backgroundPieColor = json_encode($palette);
            $labelsPie          = json_encode($namespie);

            $graph_datas = ['name'            => $name,
                            'ids'             => json_encode([]),
                            'data'            => $dataPieset,
                            'labels'          => $labelsPie,
                            'label'           => $title,
                            'backgroundColor' => $backgroundPieColor];


            $graph = PluginMydashboardPieChart::launchPolarAreaGraph($graph_datas, []);

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => false,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => $nb];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));

            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;

         case $this->getType() . "12":
            $name      = 'TTRCompliance';
            $criterias = ['type'];
            $params    = ["preferences" => $this->preferences,
                          "criterias"   => $criterias,
                          "opt"         => $opt];
            $options   = PluginMydashboardHelper::manageCriterias($params);

            $opt  = $options['opt'];
            $crit = $options['crit'];

            $type_criteria = $crit['type'];
            $is_deleted    = "`glpi_tickets`.`is_deleted` = 0";
            $all           = "SELECT DISTINCT COUNT(`glpi_tickets`.`id`) AS nb
                        FROM `glpi_tickets`
                        WHERE $is_deleted $type_criteria
                        AND `glpi_tickets`.`solvedate` IS NOT NULL
                        AND `glpi_tickets`.`time_to_resolve` IS NOT NULL ";
            $all           .= $dbu->getEntitiesRestrictRequest("AND", Ticket::getTable())
                              . " AND `status` IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ") ";

            $result = $DB->query($all);
            $total  = $DB->fetchAssoc($result);

            $query = "SELECT COUNT(`glpi_tickets`.`id`) AS nb
                        FROM `glpi_tickets`
                        WHERE $is_deleted $type_criteria
                        AND `glpi_tickets`.`solvedate` IS NOT NULL
                        AND `glpi_tickets`.`time_to_resolve` IS NOT NULL
                                            AND (`glpi_tickets`.`solvedate` > `glpi_tickets`.`time_to_resolve`
                                                 OR (`glpi_tickets`.`solvedate` IS NULL
                                                      AND `glpi_tickets`.`time_to_resolve` < NOW()))";
            $query .= $dbu->getEntitiesRestrictRequest("AND", Ticket::getTable())
                      . " AND `status` IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")";

            $result       = $DB->query($query);
            $sum          = $DB->fetchAssoc($result);
            $nb           = $DB->numrows($result);
            $notrespected = 0;
            $respected    = 0;
            if ($nb > 0 && $sum['nb'] > 0) {
               $notrespected = round(($sum['nb']) * 100 / ($total['nb']), 2);
               $respected    = round(($total['nb'] - $sum['nb']) * 100 / ($total['nb']), 2);
            }
            $widget = new PluginMydashboardHtml();
            $title  = __("TTR Compliance", "mydashboard");
            $widget->setWidgetTitle((($isDebug) ? "12 " : "") . $title);
            $widget->setWidgetComment(__("Display tickets where time to resolve is respected (percent)", "mydashboard"));

            $dataPieset         = json_encode([$respected, $notrespected]);
            $palette            = PluginMydashboardColor::getColors(2);
            $backgroundPieColor = json_encode($palette);
            $labelsPie          = json_encode([__("Respected TTR", "mydashboard"), __("Not respected TTR", "mydashboard")]);
            $format             = json_encode('%');
            $graph_datas = ['name'            => $name,
                            'ids'             => json_encode([]),
                            'data'            => $dataPieset,
                            'labels'          => $labelsPie,
                            'label'           => $title,
                            'backgroundColor' => $backgroundPieColor,
                            'format'          => $format];

            $graph = PluginMydashboardPieChart::launchPieGraph($graph_datas, []);

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => false,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => $nb];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));

            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;

         case $this->getType() . "13":
            $name      = 'TTOCompliance';
            $criterias = ['type'];
            $params    = ["preferences" => $this->preferences,
                          "criterias"   => $criterias,
                          "opt"         => $opt];
            $options   = PluginMydashboardHelper::manageCriterias($params);

            $opt  = $options['opt'];
            $crit = $options['crit'];

            $type_criteria = $crit['type'];
            $is_deleted    = "`glpi_tickets`.`is_deleted` = 0";

            $all = "SELECT DISTINCT COUNT(`glpi_tickets`.`id`) AS nb
                        FROM `glpi_tickets`
                        WHERE $is_deleted $type_criteria
                        AND `glpi_tickets`.`takeintoaccount_delay_stat` IS NOT NULL
                        AND `glpi_tickets`.`time_to_own` IS NOT NULL ";// AND ".getDateRequest("`$table`.`solvedate`", $begin, $end)."
            $all .= $dbu->getEntitiesRestrictRequest("AND", Ticket::getTable())
                    . " AND `status` IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ") ";

            $result = $DB->query($all);
            $total  = $DB->fetchAssoc($result);

            $query = "SELECT COUNT(`glpi_tickets`.`id`) AS nb
                        FROM `glpi_tickets`
                        WHERE $is_deleted $type_criteria
                        AND `glpi_tickets`.`takeintoaccount_delay_stat` IS NOT NULL
                        AND `glpi_tickets`.`time_to_own` IS NOT NULL
                        AND (`glpi_tickets`.`takeintoaccount_delay_stat`
                                                        > TIME_TO_SEC(TIMEDIFF(`glpi_tickets`.`time_to_own`,
                                                                               `glpi_tickets`.`date`))
                                                 OR (`glpi_tickets`.`takeintoaccount_delay_stat` = 0
                                                      AND `glpi_tickets`.`time_to_own` < NOW()))";
            $query .= $dbu->getEntitiesRestrictRequest("AND", Ticket::getTable())
                      . " AND `status` IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")";

            $result       = $DB->query($query);
            $sum          = $DB->fetchAssoc($result);
            $nb           = $DB->numrows($result);
            $notrespected = 0;
            $respected    = 0;
            if ($nb > 0 && $sum['nb'] > 0) {
               $notrespected = round(($sum['nb']) * 100 / ($total['nb']), 2);
               $respected    = round(($total['nb'] - $sum['nb']) * 100 / ($total['nb']), 2);
            }
            $widget = new PluginMydashboardHtml();
            $title  = __("TTO Compliance", "mydashboard");
            $widget->setWidgetTitle((($isDebug) ? "13 " : "") . $title);
            $widget->setWidgetComment(__("Display tickets where time to own is respected (percent)", "mydashboard"));

            $dataPieset         = json_encode([$respected, $notrespected]);
            $palette            = PluginMydashboardColor::getColors(2);
            $backgroundPieColor = json_encode($palette);
            $labelsPie          = json_encode([__("Respected TTO", "mydashboard"), __("Not respected TTO", "mydashboard")]);
            $format             = json_encode('%');
            $graph_datas = ['name'            => $name,
                            'ids'             => json_encode([]),
                            'data'            => $dataPieset,
                            'labels'          => $labelsPie,
                            'label'           => $title,
                            'backgroundColor' => $backgroundPieColor,
                            'format'          => $format];

            $graph = PluginMydashboardPieChart::launchPieGraph($graph_datas, []);

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => false,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => $nb];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));
            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;

         case $this->getType() . "16":
            $name = 'IncidentsByCategoryPieChart';
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['entities_id',
                             'is_recursive',
                             'technicians_groups_id',
                             'group_is_recursive',
                             'requesters_groups_id'];
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = ['requesters_groups_id'];
            }

            $params  = ["preferences" => $this->preferences,
                        "criterias"   => $criterias,
                        "opt"         => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);

            $opt  = $options['opt'];
            $crit = $options['crit'];

            $entities_criteria          = $crit['entities_id'];
            $entities_id_criteria       = $crit['entity'];
            $sons_criteria              = $crit['sons'];
            $requester_groups_criteria  = $crit['requesters_groups_id'];
            $requester_groups           = $opt['requesters_groups_id'];
            $technician_group           = $opt['technicians_groups_id'];
            $technician_groups_criteria = $crit['technicians_groups_id'];
            $is_deleted                 = "`glpi_tickets`.`is_deleted` = 0";

            $query = "SELECT DISTINCT
                           `glpi_itilcategories`.`name` AS name,
                           `glpi_itilcategories`.`id` AS itilcategories_id,
                           COUNT(`glpi_tickets`.`id`) AS nb
                        FROM `glpi_tickets` ";
            $query .= "LEFT JOIN `glpi_itilcategories`
                        ON (`glpi_itilcategories`.`id` = `glpi_tickets`.`itilcategories_id`)
                        WHERE $is_deleted AND  `glpi_tickets`.`type` = '" . Ticket::INCIDENT_TYPE . "'";
            $query .= $entities_criteria . " " . $technician_groups_criteria . " " . $requester_groups_criteria
                      . " AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")
                        GROUP BY `glpi_itilcategories`.`id`";


            $result = $DB->query($query);
            $nb     = $DB->numrows($result);

            $name_category       = [];
            $datas               = [];
            $tabincidentcategory = [];
            if ($nb) {
               while ($data = $DB->fetchArray($result)) {
                  if ($data['name'] == NULL) {
                     $name_category[] = __('None');
                  } else {
                     $name_category[] = $data['name'];
                  }
                  $datas[]               = $data['nb'];
                  $tabincidentcategory[] = $data['itilcategories_id'];
               }
            }

            $widget = new PluginMydashboardHtml();
            $title  = __("Number of opened incidents by category", "mydashboard");
            $widget->setWidgetTitle((($isDebug) ? "16 " : "") . $title);

            $dataPieset             = json_encode($datas);
            $palette                = PluginMydashboardColor::getColors($nb);
            $backgroundPieColor     = json_encode($palette);
            $labelsPie              = json_encode($name_category);
            $tabincidentcategoryset = json_encode($tabincidentcategory);
            $js_ancestors           = $crit['ancestors'];

            $graph_datas = ['name'            => $name,
                            'ids'             => $tabincidentcategoryset,
                            'data'            => $dataPieset,
                            'labels'          => $labelsPie,
                            'label'           => $title,
                            'backgroundColor' => $backgroundPieColor];

            $graph_criterias = ['entities_id'        => $entities_id_criteria,
                                'sons'               => $sons_criteria,
                                'technician_group'   => $technician_group,
                                'group_is_recursive' => $js_ancestors,
                                'requester_groups'   => $requester_groups,
                                'widget'             => $widgetId];

            $graph = PluginMydashboardPieChart::launchPieGraph($graph_datas, $graph_criterias);

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => true,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => $nb];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));

            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;

         case $this->getType() . "17":
            $name = 'RequestsByCategoryPieChart';
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['entities_id',
                             'is_recursive',
                             'technicians_groups_id',
                             'group_is_recursive',
                             'requesters_groups_id',
                             'limit'];
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = ['requesters_groups_id'];
            }

            $params  = ["preferences" => $this->preferences,
                        "criterias"   => $criterias,
                        "opt"         => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);

            $opt  = $options['opt'];
            $crit = $options['crit'];

            $entities_criteria          = $crit['entities_id'];
            $entities_id_criteria       = $crit['entity'];
            $sons_criteria              = $crit['sons'];
            $requester_groups_criteria  = $crit['requesters_groups_id'];
            $requester_groups           = $opt['requesters_groups_id'];
            $technician_group           = $opt['technicians_groups_id'];
            $technician_groups_criteria = $crit['technicians_groups_id'];
            $is_deleted                 = "`glpi_tickets`.`is_deleted` = 0";
            $limit_query                = "";
            $limit                      = isset($opt['limit']) ? $opt['limit'] : 0;
            if ($limit > 0) {
               $limit_query = "LIMIT $limit";
            }
            $query = "SELECT DISTINCT
                           `glpi_itilcategories`.`name` AS name,
                           `glpi_itilcategories`.`id` AS itilcategories_id,
                           COUNT(`glpi_tickets`.`id`) AS nb
                        FROM `glpi_tickets` ";
            $query .= " LEFT JOIN `glpi_itilcategories`
                        ON (`glpi_itilcategories`.`id` = `glpi_tickets`.`itilcategories_id`)
                        WHERE $is_deleted AND  `glpi_tickets`.`type` = '" . Ticket::DEMAND_TYPE . "'";
            $query .= $entities_criteria . " " . $technician_groups_criteria . " " . $requester_groups_criteria
                      . " AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")
                        GROUP BY `glpi_itilcategories`.`id` $limit_query";

            $result = $DB->query($query);
            $nb     = $DB->numrows($result);

            $name_categories = [];
            $datas           = [];
            $tabcategory     = [];
            if ($nb) {
               while ($data = $DB->fetchArray($result)) {
                  if ($data['name'] == NULL) {
                     $name_categories[] = __('None');
                  } else {
                     $name_categories[] = $data['name'];
                  }
                  $datas[]       = $data['nb'];
                  $tabcategory[] = $data['itilcategories_id'];
               }
            }
            $widget = new PluginMydashboardHtml();
            $title  = __("Number of opened requests by category", "mydashboard");
            $widget->setWidgetTitle((($isDebug) ? "17 " : "") . $title);

            $dataPieset         = json_encode($datas);
            $palette            = PluginMydashboardColor::getColors($nb);
            $backgroundPieColor = json_encode($palette);
            $labelsPie          = json_encode($name_categories);
            $tabcategoryset     = json_encode($tabcategory);
            $js_ancestors       = $crit['ancestors'];

            $graph_datas = ['name'            => $name,
                            'ids'             => $tabcategoryset,
                            'data'            => $dataPieset,
                            'labels'          => $labelsPie,
                            'label'           => $title,
                            'backgroundColor' => $backgroundPieColor];

            $graph_criterias = ['entities_id'        => $entities_id_criteria,
                                'sons'               => $sons_criteria,
                                'technician_group'   => $technician_group,
                                'group_is_recursive' => $js_ancestors,
                                'requester_groups'   => $requester_groups,
                                'widget'             => $widgetId];

            $graph = PluginMydashboardPieChart::launchPieGraph($graph_datas, $graph_criterias);

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => true,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => $nb];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));

            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;

         case $this->getType() . "18":
            $name = 'TicketTypePieChart';
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['entities_id',
                             'technicians_groups_id',
                             'group_is_recursive',
                             'requesters_groups_id',
                             'is_recursive',
                             'type',
                             'year',
                             'month'];
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = ['requesters_groups_id',
                             'type',
                             'year',
                             'month'];
            }

            $params  = ["preferences" => $this->preferences,
                        "criterias"   => $criterias,
                        "opt"         => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);

            $opt  = $options['opt'];
            $crit = $options['crit'];

            $type_criteria              = $crit['type'];
            $entities_criteria          = $crit['entities_id'];
            $requester_groups_criteria  = $crit['requesters_groups_id'];
            $technician_groups_criteria = $crit['technicians_groups_id'];
            $date_criteria              = $crit['date'];
            $closedate_criteria         = $crit['closedate'];
            $is_deleted                 = "`glpi_tickets`.`is_deleted` = 0";

            $query = "SELECT COUNT(`glpi_tickets`.`id`)  AS nb
                     FROM `glpi_tickets`
                     WHERE $date_criteria
                     $entities_criteria $type_criteria $requester_groups_criteria $technician_groups_criteria
                     AND $is_deleted";

            $result   = $DB->query($query);
            $nb       = $DB->numrows($result);
            $dataspie = [];
            $namespie = [];
            if ($nb) {
               while ($data = $DB->fetchAssoc($result)) {
                  $dataspie[] = $data['nb'];
                  $namespie[] = __("Opened tickets", "mydashboard");
               }
            }

            $query = "SELECT COUNT(`glpi_tickets`.`id`)  AS nb
                     FROM `glpi_tickets`

                     WHERE $closedate_criteria
                     $entities_criteria $type_criteria $requester_groups_criteria $technician_groups_criteria 
                     AND $is_deleted";

            $result = $DB->query($query);
            $nb     = $DB->numrows($result);

            if ($nb) {
               while ($data = $DB->fetchAssoc($result)) {
                  $dataspie[] = $data['nb'];
                  $namespie[] = __("Closed tickets", "mydashboard");
               }
            }

            $whereUnplanned = " AND `glpi_tickettasks`.`actiontime` IS NULL ";

            $query = "SELECT COUNT(`glpi_tickets`.`id`)  AS nb
                     FROM `glpi_tickets`
                     LEFT JOIN `glpi_tickettasks` ON `glpi_tickets`.`id` = `glpi_tickettasks`.`tickets_id`
                     WHERE $date_criteria
                     $entities_criteria $type_criteria $requester_groups_criteria
                     AND $is_deleted $whereUnplanned";

            $result = $DB->query($query);
            $nb     = $DB->numrows($result);

            if ($nb) {
               while ($data = $DB->fetchAssoc($result)) {
                  $dataspie[] = $data['nb'];
                  $namespie[] = __("Not planned", "mydashboard");
               }
            }

            $widget = new PluginMydashboardHtml();
            $title  = __("Number of opened, closed and unplanned tickets by month", "mydashboard");
            $widget->setWidgetTitle((($isDebug) ? "18 " : "") . $title);

            $dataPieset         = json_encode($dataspie);
            $palette            = PluginMydashboardColor::getColors(2);
            $backgroundPieColor = json_encode($palette);
            $labelsPie          = json_encode($namespie);

            $graph_datas = ['name'            => $name,
                            'ids'             => json_encode([]),
                            'data'            => $dataPieset,
                            'labels'          => $labelsPie,
                            'label'           => $title,
                            'backgroundColor' => $backgroundPieColor];

            $graph = PluginMydashboardPieChart::launchPieGraph($graph_datas, []);

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => true,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => $nb];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));
            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;

         case $this->getType() . "20":
            $name = 'SolutionTypePieChart';
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['entities_id',
                             'is_recursive',
                             'type'];
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = ['type'];
            }

            $params  = ["preferences" => $this->preferences,
                        "criterias"   => $criterias,
                        "opt"         => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);

            $opt  = $options['opt'];
            $crit = $options['crit'];

            $type_criteria     = $crit['type'];
            $entities_criteria = $crit['entities_id'];
            $is_deleted        = "`glpi_tickets`.`is_deleted` = 0";
            $is_ticket         = " AND `glpi_itilsolutions`.`itemtype` = 'Ticket'";

            $total             = 0;
            $query_tot = "SELECT COUNT(`glpi_tickets`.`id`) AS nb
                        FROM `glpi_tickets`
                        LEFT JOIN `glpi_itilsolutions`
                        ON (`glpi_itilsolutions`.`items_id` = `glpi_tickets`.`id`)
                        LEFT JOIN `glpi_solutiontypes`
                        ON (`glpi_solutiontypes`.`id` = `glpi_itilsolutions`.`solutiontypes_id`)
                        WHERE $is_deleted $is_ticket $type_criteria ";
            $query_tot .= $entities_criteria
                          . " AND `glpi_tickets`.`status` IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")
                      AND `glpi_itilsolutions`.`solutiontypes_id` > 0
                      GROUP BY `glpi_solutiontypes`.`id`";

            $result_tot = $DB->query($query_tot);
            $nb_tot     = $DB->numrows($result_tot);
            if ($nb_tot) {
               while ($tot = $DB->fetchArray($result_tot)) {
                  $total += $tot['nb'];
               }
            }

            $query = "SELECT DISTINCT
                           `glpi_solutiontypes`.`name` AS name,
                           `glpi_solutiontypes`.`id` AS solutiontypes_id,
                           COUNT(`glpi_tickets`.`id`) AS nb
                        FROM `glpi_tickets`
                        LEFT JOIN `glpi_itilsolutions`
                        ON (`glpi_itilsolutions`.`items_id` = `glpi_tickets`.`id`)
                        LEFT JOIN `glpi_solutiontypes`
                        ON (`glpi_solutiontypes`.`id` = `glpi_itilsolutions`.`solutiontypes_id`)
                        WHERE $is_deleted $is_ticket $type_criteria ";
            $query .= $entities_criteria
                      . " AND `glpi_tickets`.`status` IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")
                      AND `glpi_itilsolutions`.`solutiontypes_id` > 0
                      GROUP BY `glpi_solutiontypes`.`id`";

            $result = $DB->query($query);
            $nb     = $DB->numrows($result);

            $name_solution = [];
            $datas         = [];
            $tabsolution   = [];

            if ($nb) {
               while ($data = $DB->fetchArray($result)) {
                  $name_solution[] = $data['name'];
                  $datas[]         = Html::formatNumber(($data['nb'] * 100) / $total);
                  $tabsolution[]   = $data['solutiontypes_id'];
               }
            }
            $widget = new PluginMydashboardHtml();
            $title  = __("Percent of use of solution types", "mydashboard");
            $widget->setWidgetComment(__("Display percent of solution types for tickets", "mydashboard"));
            $widget->setWidgetTitle((($isDebug) ? "20 " : "") . $title);

            $dataPieset         = json_encode($datas);
            $palette            = PluginMydashboardColor::getColors($nb);
            $backgroundPieColor = json_encode($palette);
            $labelsPie          = json_encode($name_solution);
            $tabsolutionset     = json_encode($tabsolution);
            $format             = json_encode('%');

            $graph_datas = ['name'            => $name,
                            'ids'             => $tabsolutionset,
                            'data'            => $dataPieset,
                            'labels'          => $labelsPie,
                            'label'           => $title,
                            'backgroundColor' => $backgroundPieColor,
                            'format'          => $format];

            $graph = PluginMydashboardPieChart::launchDonutGraph($graph_datas, []);

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => false,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => $nb];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));

            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;

         case $this->getType() . "25":
            $name         = 'TicketsByRequesterGroupPieChart';
            $criterias    = ['type', 'limit'];
            $params       = ["preferences" => $this->preferences,
                             "criterias"   => $criterias,
                             "opt"         => $opt];
            $opt['limit'] = isset($opt['limit']) ? $opt['limit'] : 10;
            $options      = PluginMydashboardHelper::manageCriterias($params);

            $opt           = $options['opt'];
            $crit          = $options['crit'];
            $type          = $opt['type'];
            $type_criteria = $crit['type'];
            $is_deleted    = "`glpi_tickets`.`is_deleted` = 0";
            $limit_query   = "";
            $limit         = isset($opt['limit']) ? $opt['limit'] : 10;
            if ($limit > 0) {
               $limit_query = "LIMIT $limit";
            }

            $query = "SELECT DISTINCT
                           `groups_id` AS `requesters_groups_id`,
                           COUNT(`glpi_tickets`.`id`) AS nb
                        FROM `glpi_tickets`
                        LEFT JOIN `glpi_groups_tickets` 
                        ON (`glpi_groups_tickets`.`tickets_id` = `glpi_tickets`.`id` 
                        AND `glpi_groups_tickets`.`type` = '" . CommonITILActor::REQUESTER . "')
                        WHERE $is_deleted $type_criteria ";
            $query .= $dbu->getEntitiesRestrictRequest("AND", Ticket::getTable());
            $query .= " AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ") ";
            $query .= " GROUP BY `groups_id` $limit_query";

            $result = $DB->query($query);
            $nb     = $DB->numrows($result);

            $name_groups = [];
            $datas       = [];
            $tabgroup    = [];
            if ($nb) {
               while ($data = $DB->fetchArray($result)) {
                  if (!empty($data['requesters_groups_id'])) {
                     $name_groups[] = Dropdown::getDropdownName("glpi_groups", $data['requesters_groups_id']);
                  } else {
                     $name_groups[] = __('None');
                  }
                  $datas[] = $data['nb'];
                  if (!empty($data['requesters_groups_id'])) {
                     $tabgroup[] = $data['requesters_groups_id'];
                  } else {
                     $tabgroup[] = 0;
                  }
               }
            }

            $widget = new PluginMydashboardHtml();
            $title  = __("Top ten of opened tickets by requester groups", "mydashboard");
            $widget->setWidgetTitle((($isDebug) ? "25 " : "") . $title);

            $dataPieset         = json_encode($datas);
            $palette            = PluginMydashboardColor::getColors($nb);
            $backgroundPieColor = json_encode($palette);
            $labelsPie          = json_encode($name_groups);
            $tabgroupset        = json_encode($tabgroup);

            $graph_datas = ['name'            => $name,
                            'ids'             => $tabgroupset,
                            'data'            => $dataPieset,
                            'labels'          => $labelsPie,
                            'label'           => $title,
                            'backgroundColor' => $backgroundPieColor];

            //            if ($onclick == 1) {
            $graph_criterias = ['type'   => $type,
                                'widget' => $widgetId];
            //            }

            $graph = PluginMydashboardPieChart::launchPieGraph($graph_datas, []);

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => false,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => $nb];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));
            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;

         case $this->getType() . "26":
            $name = 'SatisfactionPercent';
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['entities_id',
                             'is_recursive',
                             'year'];
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = ['year'];
            }

            $params  = ["preferences" => $this->preferences,
                        "criterias"   => $criterias,
                        "opt"         => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);

            $opt  = $options['opt'];
            $crit = $options['crit'];

            $entities_criteria  = $crit['entities_id'];
            $closedate_criteria = $crit['closedate'];
            $is_deleted         = "`glpi_tickets`.`is_deleted` = 0";

            $query = "SELECT AVG(`glpi_ticketsatisfactions`.`satisfaction`) AS satisfaction
                       FROM `glpi_tickets`
                       INNER JOIN `glpi_ticketsatisfactions`
                           ON (`glpi_tickets`.`id` = `glpi_ticketsatisfactions`.`tickets_id`)";

            $query .= " WHERE $closedate_criteria
                      $entities_criteria 
                        AND $is_deleted
                        AND `glpi_tickets`.`status` IN (" . CommonITILObject::CLOSED . ")
                        AND `glpi_tickets`.`closedate` IS NOT NULL
                        AND `glpi_ticketsatisfactions`.`date_answered` IS NOT NULL ";

            $result = $DB->query($query);
            $sum    = $DB->fetchAssoc($result);
            $nb     = $DB->numrows($result);

            $notsatisfy = 0;
            $satisfy    = 0;
            if ($nb > 0 && $sum['satisfaction'] > 0) {
               $satisfy    = round(($sum['satisfaction']) * 100 / (5), 2);
               $notsatisfy = round(100 - $satisfy, 2);
            }

            $widget = new PluginMydashboardHtml();
            $title  = __("Global satisfaction level", "mydashboard");
            $widget->setWidgetTitle((($isDebug) ? "26 " : "") . $title);
            $dataPieset         = json_encode([$satisfy, $notsatisfy]);
            $palette            = PluginMydashboardColor::getColors(2);
            $backgroundPieColor = json_encode($palette);
            $labelsPie          = json_encode([__("Satisfy percent", "mydashboard"), __("Not satisfy percent", "mydashboard")]);
            $format             = json_encode('%');

            $graph_datas = ['name'            => $name,
                            'ids'             => json_encode([]),
                            'data'            => $dataPieset,
                            'labels'          => $labelsPie,
                            'label'           => $title,
                            'backgroundColor' => $backgroundPieColor,
                            'format'          => $format];

            $graph = PluginMydashboardPieChart::launchPolarAreaGraph($graph_datas, []);

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => false,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => $nb];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));
            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;

         case $this->getType() . "27":
            $name    = 'TicketsByLocationPieChart';
            $onclick = 0;
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['entities_id',
                             'is_recursive',
                             'type',
                             'technicians_groups_id',
                             'group_is_recursive',
                             'limit'];
               $onclick   = 1;
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = ['type', 'limit'];
            }
            $opt['limit'] = isset($opt['limit']) ? $opt['limit'] : 10;
            $params       = ["preferences" => $this->preferences,
                             "criterias"   => $criterias,
                             "opt"         => $opt];

            $options = PluginMydashboardHelper::manageCriterias($params);

            $opt                        = $options['opt'];
            $crit                       = $options['crit'];
            $type                       = $opt['type'];
            $type_criteria              = $crit['type'];
            $entities_criteria          = $crit['entities_id'];
            $entities_id_criteria       = $crit['entity'];
            $sons_criteria              = $crit['sons'];
            $technician_group           = $opt['technicians_groups_id'];
            $technician_groups_criteria = $crit['technicians_groups_id'];
            $is_deleted                 = "`glpi_tickets`.`is_deleted` = 0";
            $limit_query                = "";
            $limit                      = isset($opt['limit']) ? $opt['limit'] : 10;
            if ($limit > 0) {
               $limit_query = "LIMIT $limit";
            }

            $query  = "SELECT COUNT(`glpi_tickets`.`id`) AS count, 
                           glpi_locations.id as locations_id
                        FROM `glpi_tickets` 
                        LEFT JOIN `glpi_locations` ON (`glpi_locations`.`id` = `glpi_tickets`.`locations_id`)";
            $query  .= " WHERE $is_deleted $type_criteria $entities_criteria $technician_groups_criteria ";
            $query  .= " AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ") ";
            $query  .= " GROUP BY `glpi_locations`.`id` ORDER BY count DESC";
            $query  .= " $limit_query";
            $result = $DB->query($query);
            $nb     = $DB->numrows($result);

            $name_location = [];
            $datas         = [];
            $tablocation   = [];
            if ($nb) {
               while ($data = $DB->fetchArray($result)) {
                  if (!empty($data['locations_id'])) {
                     $name_location[] = Dropdown::getDropdownName("glpi_locations", $data['locations_id']);
                  } else {
                     $name_location[] = __('None');
                  }
                  $datas[] = $data['count'];
                  if (!empty($data['locations_id'])) {
                     $tablocation[] = $data['locations_id'];
                  } else {
                     $tablocation[] = 0;
                  }
               }
            }

            $widget = new PluginMydashboardHtml();
            $title  = __("Top ten of opened tickets by location", "mydashboard");
            $widget->setWidgetTitle((($isDebug) ? "27 " : "") . $title);

            $dataPieset         = json_encode($datas);
            $palette            = PluginMydashboardColor::getColors($nb);
            $backgroundPieColor = json_encode($palette);
            $labelsPie          = json_encode($name_location);
            $tablocationset     = json_encode($tablocation);
            $js_ancestors       = $crit['ancestors'];

            $graph_datas = ['name'            => $name,
                            'ids'             => $tablocationset,
                            'data'            => $dataPieset,
                            'labels'          => $labelsPie,
                            'label'           => $title,
                            'backgroundColor' => $backgroundPieColor];

            $graph_criterias = ['entities_id'        => $entities_id_criteria,
                                'sons'               => $sons_criteria,
                                'technician_group'   => $technician_group,
                                'group_is_recursive' => $js_ancestors,
                                'type'               => $type,
                                'widget'             => $widgetId];

            $graph = PluginMydashboardPieChart::launchPolarAreaGraph($graph_datas, $graph_criterias);

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => true,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => $nb];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));
            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;

         case $this->getType() . "30":
            $name = 'RequestTypePieChart';
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['entities_id',
                             'is_recursive',
                             'type'];
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = ['type'];
            }

            $params  = ["preferences" => $this->preferences,
                        "criterias"   => $criterias,
                        "opt"         => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);

            $opt  = $options['opt'];
            $crit = $options['crit'];

            $type_criteria     = $crit['type'];
            $entities_criteria = $crit['entities_id'];
            $is_deleted        = "`glpi_tickets`.`is_deleted` = 0";
            $total             = 0;
            $query_tot = "SELECT COUNT(`glpi_tickets`.`id`) AS nb
                        FROM `glpi_tickets`
                        LEFT JOIN `glpi_requesttypes`
                        ON (`glpi_requesttypes`.`id` = `glpi_tickets`.`requesttypes_id`)
                        WHERE $is_deleted $type_criteria ";
            $query_tot .= $entities_criteria
                          . " AND `status` IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")
                      AND `glpi_tickets`.`requesttypes_id` > 0
                      GROUP BY `glpi_requesttypes`.`id`";

            $result_tot = $DB->query($query_tot);
            $nb_tot     = $DB->numrows($result_tot);
            if ($nb_tot) {
               while ($tot = $DB->fetchArray($result_tot)) {
                  $total += $tot['nb'];
               }
            }

            $query = "SELECT DISTINCT
                           `glpi_requesttypes`.`name` AS name,
                           `glpi_requesttypes`.`id` AS requesttypes_id,
                           COUNT(`glpi_tickets`.`id`) AS nb
                        FROM `glpi_tickets`
                        LEFT JOIN `glpi_requesttypes`
                        ON (`glpi_requesttypes`.`id` = `glpi_tickets`.`requesttypes_id`)
                        WHERE $is_deleted $type_criteria ";
            $query .= $entities_criteria
                      . " AND `status` IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")
                      AND `glpi_tickets`.`requesttypes_id` > 0
                      GROUP BY `glpi_requesttypes`.`id`";

            $result = $DB->query($query);
            $nb     = $DB->numrows($result);

            $name_requesttypes = [];
            $datas             = [];
            $tabrequest        = [];

            if ($nb) {
               while ($data = $DB->fetchArray($result)) {
                  if ($data['name'] == NULL) {
                     $name_requesttypes[] = __('None');
                  } else {
                     $name_requesttypes[] = $data['name'];
                  }
                  $datas[]      = Html::formatNumber(($data['nb'] * 100) / $total);
                  $tabrequest[] = $data['requesttypes_id'];
               }
            }
            $widget = new PluginMydashboardHtml();
            $title  = __("Number of use of request sources", "mydashboard");
            $widget->setWidgetComment(__("Display percent of request sources for closed tickets", "mydashboard"));
            $widget->setWidgetTitle((($isDebug) ? "30 " : "") . $title);

            $dataPieset         = json_encode($datas);
            $palette            = PluginMydashboardColor::getColors($nb);
            $backgroundPieColor = json_encode($palette);
            $labelsPie          = json_encode($name_requesttypes);
            $tabrequestset      = json_encode($tabrequest);
            $format             = json_encode('%');
            $graph_datas = ['name'            => $name,
                            'ids'             => $tabrequestset,
                            'data'            => $dataPieset,
                            'labels'          => $labelsPie,
                            'label'           => $title,
                            'backgroundColor' => $backgroundPieColor,
                            'format'          => $format];

            $graph = PluginMydashboardPieChart::launchDonutGraph($graph_datas, []);

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => false,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => $nb];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));

            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;

         case $this->getType() . "31":
            $name = 'TicketsByLocationPolarChart';
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['entities_id',
                             'is_recursive',
                             'type',
                             'year',
                             'month',
                             'technicians_groups_id'];
            }
            $params  = ["preferences" => $this->preferences,
                        "criterias"   => $criterias,
                        "opt"         => $opt];
            $options = PluginMydashboardHelper::manageCriterias($params);

            $opt                        = $options['opt'];
            $crit                       = $options['crit'];
            $type                       = $opt['type'];
            $type_criteria              = $crit['type'];
            $entities_criteria          = $crit['entities_id'];
            $entities_id_criteria       = $crit['entity'];
            $sons_criteria              = $crit['sons'];
            $technician_group           = $opt['technicians_groups_id'];
            $technician_groups_criteria = $crit['technicians_groups_id'];
            $is_deleted                 = " AND `glpi_tickets`.`is_deleted` = 0";
            $date_criteria              = $crit['date'];

            $query = "SELECT DISTINCT
                           `glpi_tickets`.`locations_id`,
                           COUNT(`glpi_tickets`.`id`) AS nb
                        FROM `glpi_tickets` ";
            $query .= " WHERE $date_criteria $is_deleted  $type_criteria $entities_criteria $technician_groups_criteria ";
            $query .= " AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ") ";
            $query .= " GROUP BY `locations_id`";

            $result = $DB->query($query);
            $nb     = $DB->numrows($result);

            $name_location = [];
            $datas         = [];
            $tablocation   = [];
            if ($nb) {
               while ($data = $DB->fetchArray($result)) {
                  if (!empty($data['locations_id'])) {
                     $name_location[] = Dropdown::getDropdownName("glpi_locations", $data['locations_id']);
                  } else {
                     $name_location[] = __('None');
                  }
                  $datas[] = $data['nb'];
                  if (!empty($data['locations_id'])) {
                     $tablocation[] = $data['locations_id'];
                  } else {
                     $tablocation[] = 0;
                  }
               }
            }

            $widget = new PluginMydashboardHtml();
            $title  = __("Number of tickets per location per period", "mydashboard");
            $widget->setWidgetTitle((($isDebug) ? "40 " : "") . $title);

            $dataPolarset         = json_encode($datas);
            $palette              = PluginMydashboardColor::getColors($nb);
            $backgroundPolarColor = json_encode($palette);
            $labelsPolar          = json_encode($name_location);
            $tablocationset       = json_encode($tablocation);
            $graph_datas          = ['name'            => $name,
                                     'ids'             => $tablocationset,
                                     'data'            => $dataPolarset,
                                     'labels'          => $labelsPolar,
                                     'label'           => $title,
                                     'backgroundColor' => $backgroundPolarColor];

            $graph_criterias = ['entities_id'      => $entities_id_criteria,
                                'sons'             => $sons_criteria,
                                'technician_group' => $technician_group,
                                'type'             => $type,
                                'widget'           => $widgetId];

            $graph = PluginMydashboardPieChart::launchPolarAreaGraph($graph_datas, $graph_criterias);

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => false,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => true,
                       "canvas"    => true,
                       "nb"        => $nb];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));
            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;
         case $this->getType() . "SC32":
            $widget = new PluginMydashboardHtml();
            $widget->setWidgetTitle(__("Global indicators by week", "mydashboard"));
            $graph = self::displayIndicator($widgetId, $opt, true);
            $widget->setWidgetHtmlContent(
               $graph
            );
            $widget->toggleWidgetRefresh();
            return $widget;
            break;
         default:
            break;
      }
   }


   static function displayIndicator($id, $params = [], $iswidget = false) {
      global $CFG_GLPI;

      if (!Session::haveRightsOr("ticket", [Ticket::READMY, Ticket::READALL, Ticket::READGROUP])) {
         return false;
      }

      $seeown = false;


      if ($seeown == false) {
         if ($iswidget == true) {
            $plugin = new Plugin();
            if($plugin->isActivated("Mydashboard")){
               $preference = new PluginMydashboardPreference();
               if (!$preference->getFromDB(Session::getLoginUserID())) {
                  $preference->initPreferences(Session::getLoginUserID());
               }
               $preference->getFromDB(Session::getLoginUserID());
               $preferences = $preference->fields;
               if (isset($preferences['prefered_group'])) {
                  $technicians_groups_id = json_decode($preferences['prefered_group'], true);
                  if (is_array($technicians_groups_id)
                      && count($technicians_groups_id) > 0
                      && count($params) < 1) {
                     $params['technicians_groups_id'] = $technicians_groups_id;
                  }
               }
            }

            if (isset($params['technicians_groups_id'])) {
               $params['technicians_groups_id'] = (is_array($params['technicians_groups_id']) ? $params['technicians_groups_id'] : [$params['technicians_groups_id']]);
            }
         }
      }

      $search_assign = "1=1";
      $left          = "LEFT JOIN glpi_entities 
                  ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`) ";
      $is_deleted    = " `glpi_tickets`.`is_deleted` = 0 ";
      //if (Session::haveRight("ticket", Ticket::READMY)) {
      //   $left          .= "LEFT JOIN `glpi_tickets_users`
      //            ON (`glpi_tickets`.`id` = `glpi_tickets_users`.`tickets_id`) ";
      //   $search_assign .= " AND (`glpi_tickets_users`.`users_id` = '" . Session::getLoginUserID() . "'
      //                            AND `glpi_tickets_users`.`type` = '" . CommonITILActor::ASSIGN . "')";
      //}

      if ($seeown == false) {
         if(!isset($params['year']) && !isset($params['week'])){
            $params['year'] = date('Y');
            $params['week'] = date('W');
         }
         if($params['year'] == date('Y') && $params['week'] == date('W')){
            if (isset($params['technicians_groups_id']) && count($params['technicians_groups_id']) > 0) {
               $left                  .= "LEFT JOIN `glpi_groups_tickets`
                  ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id`) ";
               $technicians_groups_id = $params['technicians_groups_id'];
               $search_assign         .= " AND (`glpi_groups_tickets`.`groups_id` IN (" . implode(",", $technicians_groups_id) . ")
                                AND `glpi_groups_tickets`.`type` = '" . CommonITILActor::ASSIGN . "')";
            }
            //New tickets
            $total_new = self::queryNewTickets($left, $is_deleted);
            //Late tickets
            $total_due = self::queryDueTickets($left, $is_deleted, $search_assign);
            //Waiting tickets
            $total_pend = self::queryPendingTickets($left, $is_deleted, $search_assign);
            //Processing incidents
            $total_incpro = self::queryIncidentTickets($left, $is_deleted, $search_assign);
            //Processing requests
            $total_dempro = self::queryRequestTickets($left, $is_deleted, $search_assign);
            //Validate tickets
//            $total_validate = self::queryValidateTickets($left, $is_deleted, $search_assign);
            //Resolved tickets
            $total_resolved = self::queryResolvedTickets($left, $is_deleted, $search_assign);
            //Resolved tickets
            $total_closed = self::queryClosedTickets($left, $is_deleted, $search_assign);

         } else {

            if (isset($params['technicians_groups_id']) && count($params['technicians_groups_id']) > 0) {
               $technicians_groups_id = $params['technicians_groups_id'];
            } else {
               $technicians_groups_id = [0];
            }

            $total_new = self::queryNewTicketsWeek($params['year'], $params['week'], $technicians_groups_id);
            //Late tickets
            $total_due = self::queryDueTicketsWeek($params['year'], $params['week'], $technicians_groups_id);
            //Waiting tickets
            $total_pend = self::queryPendingTicketsWeek($params['year'], $params['week'], $technicians_groups_id);
            //Processing incidents
            $total_incpro = self::queryIncidentTicketsWeek($params['year'], $params['week'], $technicians_groups_id);
            //Processing requests
            $total_dempro = self::queryRequestTicketsWeek($params['year'], $params['week'], $technicians_groups_id);
            //Validate tickets
//            $total_validate = self::queryValidateTickets($left, $is_deleted, $search_assign);
            //Resolved tickets
            $total_resolved = self::queryResolvedTicketsWeek($params['year'], $params['week'], $technicians_groups_id);
            //Resolved tickets
            $total_closed = self::queryClosedTicketsWeek($params['year'], $params['week'], $technicians_groups_id);
         }

      }



      $size = "";
      $span = "";
      if ($iswidget == true) {
         $size = "font-size:18px";
         $span = "ind-link";
      }

      $target = "";
      if ($iswidget == true) {
         $target = "target = '_blank'";
      }

      // Reset criterias
      $options_new['reset'][] = 'reset';

      $options_new['criteria'][] = [
         'field'      => 12,//status
         'searchtype' => 'equals',
         'value'      => Ticket::INCOMING,
         'link'       => 'AND'
      ];


      $href_new = "<a $target style='color:#D9534F !important;$size' title='" . __('New tickets', 'mydashboard') . "' \
href='" . $CFG_GLPI["root_doc"] . '/front/ticket.php?' .
                  Toolbox::append_params($options_new, '&amp;') . "' ><span class='$span'>" .
                  $total_new . "</span></a>";

      //$href_due
      // Reset criterias
      $options_due['reset'][] = 'reset';

      $options_due['criteria'][] = [
         'field'      => 12,//status
         'searchtype' => 'equals',
         'value'      => 'notold',
         'link'       => 'AND'
      ];

      if (isset($params['technicians_groups_id'])
          && count($params['technicians_groups_id']) > 0) {
         $groups = $params['technicians_groups_id'];
         $nb     = 0;
         foreach ($groups as $group) {

            $criterias['criteria'][$nb] = [
               'field'      => 8, // groups_id_assign
               'searchtype' => 'equals',
               'value'      => $group,
               'link'       => (($nb == 0) ? 'AND' : 'OR'),
            ];
            $nb++;
         }
         $options_due['criteria'][] = $criterias;
      }

      $options_due['criteria'][] = [
         'field'      => 82,//due date
         'searchtype' => 'equals',
         'value'      => 1,
         'link'       => 'AND'
      ];


      $href_due = "<a $target style='$size' title='" . __('Tickets late', 'mydashboard') . "' \
href='" . $CFG_GLPI["root_doc"] . '/front/ticket.php?' .
                  Toolbox::append_params($options_due, '&amp;') . "' ><span class='$span'>" .
                  $total_due . "</span></a>";

      //$href_pend
      // Reset criterias
      $options_pend['reset'][] = 'reset';

      $options_pend['criteria'][] = [
         'field'      => 12,//status
         'searchtype' => 'equals',
         'value'      => Ticket::WAITING,
         'link'       => 'AND'
      ];

      if (isset($params['technicians_groups_id'])
          && count($params['technicians_groups_id']) > 0) {
         $groups = $params['technicians_groups_id'];
         $nb     = 0;
         foreach ($groups as $group) {

            $criterias['criteria'][$nb] = [
               'field'      => 8, // groups_id_assign
               'searchtype' => 'equals',
               'value'      => $group,
               'link'       => (($nb == 0) ? 'AND' : 'OR'),
            ];
            $nb++;
         }
         $options_pend['criteria'][] = $criterias;
      }

      $href_pend = "<a $target style='$size' title='" . __('Pending tickets', 'mydashboard') . "' \
href='" . $CFG_GLPI["root_doc"] . '/front/ticket.php?' .
                   Toolbox::append_params($options_pend, '&amp;') . "' ><span class='$span'>" .
                   $total_pend . "</span></a>";

      //$href_incpro
      // Reset criterias
      $options_incpro['reset'][] = 'reset';

      if ($seeown == false) {
         $options_incpro['criteria'][] = [
            'field'      => 12,//status
            'searchtype' => 'equals',
            'value'      => 'process',
            'link'       => 'AND'
         ];
      } else {
         $options_incpro['criteria'][] = [
            'field'      => 12,//status
            'searchtype' => 'equals',
            'value'      => 'notold',
            'link'       => 'AND'
         ];
      }

      $options_incpro['criteria'][] = [
         'field'      => 14, // type
         'searchtype' => 'equals',
         'value'      => Ticket::INCIDENT_TYPE,
         'link'       => 'AND',
      ];

      if ($seeown == false) {
         if (isset($params['technicians_groups_id'])
             && count($params['technicians_groups_id']) > 0) {
            $groups = $params['technicians_groups_id'];
            $nb     = 0;
            foreach ($groups as $group) {

               $criterias['criteria'][$nb] = [
                  'field'      => 8, // groups_id_assign
                  'searchtype' => 'equals',
                  'value'      => $group,
                  'link'       => (($nb == 0) ? 'AND' : 'OR'),
               ];
               $nb++;
            }
            $options_incpro['criteria'][] = $criterias;
         }
      }

         $href_incpro = "<a $target style='$size' title='" . __('Incidents in progress', 'mydashboard') . "' \
href='" . $CFG_GLPI["root_doc"] . '/front/ticket.php?' .
                        Toolbox::append_params($options_incpro, '&amp;') . "' ><span class='$span'>" .
                        $total_incpro . "</span></a>";

      //$href_dempro
      // Reset criterias
      $options_dempro['reset'][] = 'reset';

      if ($seeown == false) {
         $options_dempro['criteria'][] = [
            'field'      => 12,//status
            'searchtype' => 'equals',
            'value'      => 'process',
            'link'       => 'AND'
         ];
      } else {
         $options_dempro['criteria'][] = [
            'field'      => 12,//status
            'searchtype' => 'equals',
            'value'      => 'notold',
            'link'       => 'AND'
         ];
      }

      $options_dempro['criteria'][] = [
         'field'      => 14, // type
         'searchtype' => 'equals',
         'value'      => Ticket::DEMAND_TYPE,
         'link'       => 'AND',
      ];

      if ($seeown == false) {
         if (isset($params['technicians_groups_id'])
             && count($params['technicians_groups_id']) > 0) {
            $groups = $params['technicians_groups_id'];
            $nb     = 0;
            foreach ($groups as $group) {

               $criterias['criteria'][$nb] = [
                  'field'      => 8, // groups_id_assign
                  'searchtype' => 'equals',
                  'value'      => $group,
                  'link'       => (($nb == 0) ? 'AND' : 'OR'),
               ];
               $nb++;
            }
            $options_dempro['criteria'][] = $criterias;
         }
      }

         $href_dempro = "<a $target style='$size' title='" . __('Requests in progress', 'mydashboard') . "' \
href='" . $CFG_GLPI["root_doc"] . '/front/ticket.php?' .
                        Toolbox::append_params($options_dempro, '&amp;') . "' ><span class='$span'>" .
                        $total_dempro . "</span></a>";


      $options_closed['reset'][] = 'reset';

      if ($seeown == false) {
         $options_closed['criteria'][] = [
            'field'      => 12,//status
            'searchtype' => 'equals',
            'value'      => 'process',
            'link'       => 'AND'
         ];
      } else {
         $options_closed['criteria'][] = [
            'field'      => 12,//status
            'searchtype' => 'equals',
            'value'      => Ticket::CLOSED,
            'link'       => 'AND'
         ];
      }


      if ($seeown == false) {
         if (isset($params['technicians_groups_id'])
             && count($params['technicians_groups_id']) > 0) {
            $groups = $params['technicians_groups_id'];
            $nb     = 0;
            foreach ($groups as $group) {

               $criterias['criteria'][$nb] = [
                  'field'      => 8, // groups_id_assign
                  'searchtype' => 'equals',
                  'value'      => $group,
                  'link'       => (($nb == 0) ? 'AND' : 'OR'),
               ];
               $nb++;
            }
            $options_closed['criteria'][] = $criterias;
         }
      }

      $href_closed = "<a $target style='$size' title='" . __('Ticket closed', 'mydashboard') . "' \
href='" . $CFG_GLPI["root_doc"] . '/front/ticket.php?' .
                     Toolbox::append_params($options_closed, '&amp;') . "' ><span class='$span'>" .
                     $total_closed . "</span></a>";




      if ($iswidget == false) {
         //         echo "<li>";
         echo "<table id='indicators' class='indicators'><tr>";

         echo "<td class='ind-new'>";
         echo $href_new;
         echo "</td>";

         echo "<td class='ind-late'>";
         echo $href_due;
         echo "</td>";

         echo "<td class='ind-pending'>";
         echo $href_pend;
         echo "</td>";

         echo "<td class='ind-process'>";
         echo $href_incpro;
         echo "</td>";

         echo "<td class='dem-process'>";
         echo $href_dempro;
         echo "</td>";

         echo "</tr></table>";
         //         echo "</li>";

      } else {

         //         $graph = "<table id='indicators' class='indicators'><tr>";

         $stats = "";
         if ($iswidget == true
             && Session::haveRightsOr("ticket", [Ticket::READALL, Ticket::READGROUP])) {
            $criterias     = ['technicians_groups_id','week','year'];
            $params_header = ["widgetId"  => "PluginMydashboardReports_PieSC32",
                              "name"      => 'PluginMydashboardReports_PieSC32',
                              "onsubmit"  => true,
                              "opt"       => $params,
                              "criterias" => $criterias,
                              "export"    => false,
                              "canvas"    => false,
                              "nb"        => 1];
            if($plugin->isActivated("Mydashboard")) {
               $stats .= PluginMydashboardHelper::getGraphHeader($params_header);
            }

         }

         if ($seeown == true) {
            $delclass = "";
            $class    = "bt-col-md-12";
            if (Session::haveRight("plugin_servicecatalog_view", CREATE)
                || Session::haveRight("plugin_servicecatalog_defaultview", CREATE)) {
               $delclass = "delclass";
            }
            $stats .= "<div id='gs20' class=\"bt-row $delclass\">";
            $stats .= "<div class=\"bt-feature $class \">";
            $stats .= "<h3 class=\"bt-title-divider\">";
            $stats .= "<span>";
            $stats .= __("Global indicators by week", "mydashboard");
            $stats .= "</span>";
            $stats .= "</h3>";
         }

         $stats .= "<div id='indicators' class='tickets-ind' style='text-align: center;'>";
         //         $stats .= "<div class='circle'>";

         if ($seeown == false) {
            $stats .= "<div class='nb ind-widget-new'>";
            $stats .= $href_new;
            $stats .= "<br><br>";
            $stats .= __('New tickets', 'mydashboard');
            $stats .= "</div>";

//            $stats .= "<div class='nb ind-widget-late'>";
//            $stats .= $href_due;
//            $stats .= "<br><br>";
//            $stats .= __('Tickets late', 'mydashboard');
//            $stats .= "</div>";

            $stats .= "<div class='nb ind-widget-pending'>";
            $stats .= $href_pend;
            $stats .= "<br><br>";
            $stats .= __('Pending tickets', 'mydashboard');
            $stats .= "</div>";

            $stats .= "<div class='nb ind-widget-process'>";
            $stats .= $href_incpro;
            $stats .= "<br><br>";
            $stats .= __('Incidents in progress', 'mydashboard');
            $stats .= "</div>";

            $stats .= "<div class='nb dem-widget-process'>";
            $stats .= $href_dempro;
            $stats .= "<br><br>";
            $stats .= __('Requests in progress', 'mydashboard');
            $stats .= "</div>";

            $stats .= "<div class='nb ind-widget-late'>";
            $stats .= $href_closed;
            $stats .= "<br><br>";
            $stats .= __('Tickets closed', 'mydashboard');
            $stats .= "</div>";

         }
         //         $stats .= "</div>";
         $stats .= "</div>";
         //         $stats .= "</tr></table>";

         if ($seeown == true) {
            if ($iswidget == false) {
               $stats .= "</div>";
               $stats .= "</div>";
            }
         }

         return $stats;
      }
   }

   static function queryAllTickets($left, $criteria) {
      global $DB;

      //all tickets
      $dbu     = new DbUtils();
      $sql_all = "SELECT COUNT(DISTINCT glpi_tickets.id) as total
                  FROM glpi_tickets
                  $left
                  WHERE $criteria
                        AND `glpi_tickets`.`status` NOT IN (" . Ticket::SOLVED . ", " . Ticket::CLOSED . ") ";
      $sql_all .= $dbu->getEntitiesRestrictRequest("AND", "glpi_tickets");

      $result_all = $DB->query($sql_all);
      $total_all  = $DB->result($result_all, 0, 'total');

      return $total_all;
   }


   /**
    * @param $left
    * @param $criteria
    *
    * @return mixed|\Value
    * @throws \GlpitestSQLError
    */
   static function queryNewTickets($left, $criteria) {
      global $DB;

      //New tickets
      $dbu     = new DbUtils();
      $sql_new = "SELECT COUNT(DISTINCT glpi_tickets.id) as total
                  FROM glpi_tickets
                  $left
                  WHERE $criteria
                        AND `glpi_tickets`.`status` = " . Ticket::INCOMING . " " .
                 $dbu->getEntitiesRestrictRequest("AND", "glpi_tickets");

      $result_new = $DB->query($sql_new);
      if($result_new == false) {
         return 0;
      }

      $total_new  = $DB->result($result_new, 0, 'total');

      if($total_new == null) {
         $total_new = 0;
      }
      return $total_new;
   }

   static function queryNewTicketsWeek($year, $week) {
      global $DB;

      //New tickets
      $dbu     = new DbUtils();
      $sql_new = "SELECT  SUM(glpi_plugin_mydashboard_stockticketindicators.nbTickets) as total
                  FROM glpi_plugin_mydashboard_stockticketindicators
                  WHERE  `glpi_plugin_mydashboard_stockticketindicators`.`indicator_id` = " . PluginMydashboardStockTicketIndicator::NEWT . " 
                    AND `glpi_plugin_mydashboard_stockticketindicators`.`week` = $week
                    AND `glpi_plugin_mydashboard_stockticketindicators`.`year` = $year " .
                 $dbu->getEntitiesRestrictRequest("AND", "glpi_plugin_mydashboard_stockticketindicators");

      $result_new = $DB->query($sql_new);
      if($result_new == false) {
         return 0;
      }
      $total_new  = $DB->result($result_new, 0, 'total');

      if($total_new == null) {
         $total_new = 0;
      }
      return $total_new;
   }

   /**
    * @param $left
    * @param $criteria
    * @param $search_assign
    *
    * @return mixed|\Value
    * @throws \GlpitestSQLError
    */
   static function queryDueTickets($left, $criteria, $search_assign) {
      global $DB;

      $dbu     = new DbUtils();
      $sql_due = "SELECT COUNT(DISTINCT glpi_tickets.id) AS due
                  FROM glpi_tickets
                  $left
                  WHERE $criteria
                        AND ($search_assign)
                        AND `glpi_tickets`.`status` NOT IN (" . Ticket::WAITING . "," . Ticket::SOLVED . ", " . Ticket::CLOSED . ")
                        AND `glpi_tickets`.`time_to_resolve` IS NOT NULL
                        AND `glpi_tickets`.`time_to_resolve` < NOW() ";
      $sql_due .= $dbu->getEntitiesRestrictRequest("AND", "glpi_tickets");

      $result_due = $DB->query($sql_due);
      $total_due  = $DB->result($result_due, 0, 'due');

      return $total_due;
   }

   static function queryDueTicketsWeek($year, $week, $groups_id) {
      global $DB;

      //New tickets
      $dbu     = new DbUtils();
      $sql_new = "SELECT  SUM(glpi_plugin_mydashboard_stockticketindicators.nbTickets) as total
                  FROM glpi_plugin_mydashboard_stockticketindicators
                  WHERE  `glpi_plugin_mydashboard_stockticketindicators`.`indicator_id` = " . PluginMydashboardStockTicketIndicator::LATET . " 
                  AND glpi_plugin_mydashboard_stockticketindicators.groups_id  IN (" . implode(",", $groups_id) . ") 
                   AND `glpi_plugin_mydashboard_stockticketindicators`.`week` = $week
                    AND `glpi_plugin_mydashboard_stockticketindicators`.`year` = $year " .
                 $dbu->getEntitiesRestrictRequest("AND", "glpi_plugin_mydashboard_stockticketindicators");

      $result_new = $DB->query($sql_new);
      if($result_new == false) {
         return 0;
      }
      $total_new  = $DB->result($result_new, 0, 'total');

      if($total_new == null) {
         $total_new = 0;
      }

      return $total_new;
   }

   /**
    * @param $left
    * @param $criteria
    * @param $search_assign
    *
    * @return mixed|\Value
    * @throws \GlpitestSQLError
    */
   static function queryPendingTickets($left, $criteria, $search_assign) {
      global $DB;

      $dbu      = new DbUtils();
      $sql_pend = "SELECT COUNT(DISTINCT glpi_tickets.id) as total
                  FROM glpi_tickets
                  $left
                  WHERE $criteria
                        AND ($search_assign)
                        AND `glpi_tickets`.`status` = " . Ticket::WAITING . " " .
                  $dbu->getEntitiesRestrictRequest("AND", "glpi_tickets");

      $result_pend = $DB->query($sql_pend);
      $total_pend  = $DB->result($result_pend, 0, 'total');

      return $total_pend;
   }

   static function queryPendingTicketsWeek($year, $week, $groups_id) {
      global $DB;

      //New tickets
      $dbu     = new DbUtils();
      $sql_new = "SELECT  SUM(glpi_plugin_mydashboard_stockticketindicators.nbTickets) as total
                  FROM glpi_plugin_mydashboard_stockticketindicators
                  WHERE  `glpi_plugin_mydashboard_stockticketindicators`.`indicator_id` = " . PluginMydashboardStockTicketIndicator::PENDINGT . " 
                  AND glpi_plugin_mydashboard_stockticketindicators.groups_id  IN (" . implode(",", $groups_id) . ") 
                   AND `glpi_plugin_mydashboard_stockticketindicators`.`week` = $week
                    AND `glpi_plugin_mydashboard_stockticketindicators`.`year` = $year " .
                 $dbu->getEntitiesRestrictRequest("AND", "glpi_plugin_mydashboard_stockticketindicators");

      $result_new = $DB->query($sql_new);

      if($result_new == false) {
         return 0;
      }

      $total_new  = $DB->result($result_new, 0, 'total');
      if($total_new == null) {
         $total_new = 0;
      }
      return $total_new;
   }


   /**
    * @param $left
    * @param $criteria
    * @param $search_assign
    *
    * @return mixed|\Value
    * @throws \GlpitestSQLError
    */
   static function queryIncidentTickets($left, $criteria, $search_assign) {
      global $DB;

      $dbu      = new DbUtils();
      $statuses = [Ticket::SOLVED, Ticket::CLOSED, Ticket::WAITING, Ticket::INCOMING];
      if (Session::getCurrentInterface() == 'helpdesk') {
         $statuses = [Ticket::SOLVED, Ticket::CLOSED];
      }

      $sql_incpro    = "SELECT COUNT(DISTINCT glpi_tickets.id) as total
                  FROM glpi_tickets
                  $left
                  WHERE $criteria
                        AND ($search_assign)
                        AND `glpi_tickets`.`type` = '" . Ticket::INCIDENT_TYPE . "'
                        AND `glpi_tickets`.`status` NOT IN (" . implode(",", $statuses) . ") ";
      $sql_incpro    .= $dbu->getEntitiesRestrictRequest("AND", "glpi_tickets");
      $result_incpro = $DB->query($sql_incpro);
      $total_incpro  = $DB->result($result_incpro, 0, 'total');
      return $total_incpro;
   }

   static function queryIncidentTicketsWeek($year, $week, $groups_id) {
      global $DB;

      //New tickets
      $dbu     = new DbUtils();
      $sql_new = "SELECT  SUM(glpi_plugin_mydashboard_stockticketindicators.nbTickets) as total
                  FROM glpi_plugin_mydashboard_stockticketindicators
                  WHERE  `glpi_plugin_mydashboard_stockticketindicators`.`indicator_id` = " . PluginMydashboardStockTicketIndicator::INCIDENTPROGRESST . " 
                  AND glpi_plugin_mydashboard_stockticketindicators.groups_id  IN (" . implode(",", $groups_id) . ") 
                   AND `glpi_plugin_mydashboard_stockticketindicators`.`week` = $week
                    AND `glpi_plugin_mydashboard_stockticketindicators`.`year` = $year " .
                 $dbu->getEntitiesRestrictRequest("AND", "glpi_plugin_mydashboard_stockticketindicators");

      $result_new = $DB->query($sql_new);

      if($result_new == false) {
         return 0;
      }

      $total_new  = $DB->result($result_new, 0, 'total');

      if($total_new == null) {
         $total_new = 0;
      }

      return $total_new;
   }



   /**
    * @param $left
    * @param $criteria
    * @param $search_assign
    *
    * @return mixed|\Value
    * @throws \GlpitestSQLError
    */
   static function queryRequestTickets($left, $criteria, $search_assign) {
      global $DB;

      $dbu = new DbUtils();

      $statuses = [Ticket::SOLVED, Ticket::CLOSED, Ticket::WAITING, Ticket::INCOMING];
      if (Session::getCurrentInterface() == 'helpdesk') {
         $statuses = [Ticket::SOLVED, Ticket::CLOSED];
      }

      $sql_dempro    = "SELECT COUNT(DISTINCT glpi_tickets.id) as total
                  FROM glpi_tickets
                  $left
                  WHERE $criteria
                        AND ($search_assign)
                        AND `glpi_tickets`.`type` = '" . Ticket::DEMAND_TYPE . "'
                        AND `glpi_tickets`.`status` NOT IN (" . implode(",", $statuses) . ") ";
      $sql_dempro    .= $dbu->getEntitiesRestrictRequest("AND", "glpi_tickets");
      $result_dempro = $DB->query($sql_dempro);
      $total_dempro  = $DB->result($result_dempro, 0, 'total');

      return $total_dempro;
   }

   static function queryRequestTicketsWeek($year, $week, $groups_id) {
      global $DB;

      //New tickets
      $dbu     = new DbUtils();
      $sql_new = "SELECT  SUM(glpi_plugin_mydashboard_stockticketindicators.nbTickets) as total
                  FROM glpi_plugin_mydashboard_stockticketindicators
                  WHERE `glpi_plugin_mydashboard_stockticketindicators`.`indicator_id` = " . PluginMydashboardStockTicketIndicator::REQUESTPROGRESST . " 
                  AND glpi_plugin_mydashboard_stockticketindicators.groups_id  IN (" . implode(",", $groups_id) . ") 
                   AND `glpi_plugin_mydashboard_stockticketindicators`.`week` = $week
                    AND `glpi_plugin_mydashboard_stockticketindicators`.`year` = $year " .
                 $dbu->getEntitiesRestrictRequest("AND", "glpi_plugin_mydashboard_stockticketindicators");

      $result_new = $DB->query($sql_new);
      if($result_new == false) {
         return 0;
      }
      $total_new  = $DB->result($result_new, 0, 'total');


      if($total_new == null) {
         $total_new = 0;
      }

      return $total_new;
   }



   /**
    * @param $left
    * @param $criteria
    * @param $search_assign
    *
    * @return mixed|\Value
    * @throws \GlpitestSQLError
    */
   static function queryResolvedTickets($left, $criteria, $search_assign) {
      global $DB;

      $dbu     = new DbUtils();
      $week = date('W');
      $year = date('Y');
      $sql_res    = "SELECT COUNT(DISTINCT glpi_tickets.id) as total
                  FROM glpi_tickets
                  $left
                  WHERE $criteria
                        AND ($search_assign)
                      AND WEEK(`glpi_tickets`.`solvedate`) = '$week'
                        AND YEAR(`glpi_tickets`.`solvedate`) = '$year'
                        AND `glpi_tickets`.`status` = " . Ticket::SOLVED . " ";
      $sql_res    .= $dbu->getEntitiesRestrictRequest("AND", "glpi_tickets");


      $result_res = $DB->query($sql_res);
      $total_res  = $DB->result($result_res, 0, 'total');

      return $total_res;
   }

   static function queryResolvedTicketsWeek($year, $week, $groups_id) {
      global $DB;

      //New tickets
      $dbu     = new DbUtils();
      $sql_new = "SELECT  SUM(glpi_plugin_mydashboard_stockticketindicators.nbTickets) as total
                  FROM glpi_plugin_mydashboard_stockticketindicators
                  WHERE  `glpi_plugin_mydashboard_stockticketindicators`.`indicator_id` = " . PluginMydashboardStockTicketIndicator::SOLVEDT . " 
                  AND glpi_plugin_mydashboard_stockticketindicators.groups_id  IN (" . implode(",", $groups_id) . ") 
                   AND `glpi_plugin_mydashboard_stockticketindicators`.`week` = $week
                    AND `glpi_plugin_mydashboard_stockticketindicators`.`year` = $year " .
                 $dbu->getEntitiesRestrictRequest("AND", "glpi_plugin_mydashboard_stockticketindicators");

      $result_new = $DB->query($sql_new);

      if($result_new == false) {
         return 0;
      }

      $total_new  = $DB->result($result_new, 0, 'total');

      if($total_new == null) {
         $total_new = 0;
      }

      return $total_new;
   }

   static function queryClosedTickets($left, $criteria, $search_assign) {
      global $DB;

      //New tickets
      $dbu     = new DbUtils();
      $sql_close = "SELECT COUNT(DISTINCT glpi_tickets.id) as total,

                  FROM glpi_tickets
                    LEFT JOIN glpi_entities 
                  ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`)
                  WHERE `glpi_tickets`.`is_deleted` = 0 
                        
                  GROUP BY `glpi_tickets`.`entities_id`";
      $week = date('W');
      $year = date('Y');
      $sql_close    = "SELECT COUNT(DISTINCT glpi_tickets.id) as total
                  FROM glpi_tickets
                  $left
                  WHERE $criteria
                        AND ($search_assign)
                      AND WEEK(`glpi_tickets`.`closedate`) = '$week'
                        AND YEAR(`glpi_tickets`.`closedate`) = '$year'
                        AND `glpi_tickets`.`status` = " . Ticket::CLOSED ."  ";
      $sql_close    .= $dbu->getEntitiesRestrictRequest("AND", "glpi_tickets");


      $result_close = $DB->query($sql_close);

      if($result_close == false) {
         return 0;
      }

      $total_close  = $DB->result($result_close, 0, 'total');

      if($total_close == null) {
         $total_close = 0;
      }

      return $total_close;
   }

   static function queryClosedTicketsWeek($year, $week, $groups_id) {
      global $DB;

      //New tickets
      $dbu     = new DbUtils();
      $sql_new = "SELECT  SUM(glpi_plugin_mydashboard_stockticketindicators.nbTickets) as total
                  FROM glpi_plugin_mydashboard_stockticketindicators
                  WHERE  `glpi_plugin_mydashboard_stockticketindicators`.`indicator_id` = " . PluginMydashboardStockTicketIndicator::CLOSEDT . " 
                  AND glpi_plugin_mydashboard_stockticketindicators.groups_id  IN (" . implode(",", $groups_id) . ") 
                   AND `glpi_plugin_mydashboard_stockticketindicators`.`week` = $week
                    AND `glpi_plugin_mydashboard_stockticketindicators`.`year` = $year " .
                 $dbu->getEntitiesRestrictRequest("AND", "glpi_plugin_mydashboard_stockticketindicators");

      $result_new = $DB->query($sql_new);

      if($result_new == false) {
         return 0;
      }

      $total_new  = $DB->result($result_new, 0, 'total');

      if($total_new == null) {
         $total_new = 0;
      }

      return $total_new;
   }
}
