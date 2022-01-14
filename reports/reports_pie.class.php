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

      $widgets = [
         __('Pie charts', "mydashboard") => [
            $this->getType() . "2"  => ["title"   => __("Number of opened tickets by priority", "mydashboard"),
                                        "icon"    => "ti ti-chart-pie",
                                        "comment" => ""],
            $this->getType() . "7" => ["title"   => __("Top ten ticket requesters by month", "mydashboard"),
                                        "icon"    => "ti ti-chart-pie",
                                        "comment" => ""],
            $this->getType() . "12" => ["title"   => __("TTR Compliance", "mydashboard"),
                                        "icon"    => "ti ti-chart-pie",
                                        "comment" => __("Display tickets where time to resolve is respected (percent)", "mydashboard")],
            $this->getType() . "13" => ["title"   => __("TTO Compliance", "mydashboard"),
                                        "icon"    => "ti ti-chart-pie",
                                        "comment" => __("Display tickets where time to own is respected (percent)", "mydashboard")],
            $this->getType() . "16" => ["title"   => __("Number of opened incidents by category", "mydashboard"),
                                        "icon"    => "ti ti-chart-pie",
                                        "comment" => ""],
            $this->getType() . "17" => ["title"   =>  __("Number of opened requests by category", "mydashboard"),
                                        "icon"    => "ti ti-chart-pie",
                                        "comment" => ""],
            $this->getType() . "18" => ["title"   => __("Number of opened, closed and unplanned tickets by month", "mydashboard"),
                                        "icon"    => "ti ti-chart-pie",
                                        "comment" => ""],
            $this->getType() . "20" => ["title"   => __("Percent of use of solution types", "mydashboard"),
                                        "icon"    => "ti ti-chart-pie",
                                        "comment" => __("Display percent of solution types for tickets", "mydashboard")],
            $this->getType() . "25" => ["title"   => __("Top ten of opened tickets by requester groups", "mydashboard"),
                                        "icon"    => "ti ti-chart-pie",
                                        "comment" => ""],
            $this->getType() . "26" => ["title"   => __("Global satisfaction level", "mydashboard"),
                                        "icon"    => "ti ti-chart-pie",
                                        "comment" => ""],
            $this->getType() . "27" => ["title"   => __("Top ten of opened tickets by location", "mydashboard"),
                                        "icon"    => "ti ti-chart-pie",
                                        "comment" => ""],
            $this->getType() . "30" => ["title"   => __("Number of use of request sources", "mydashboard"),
                                        "icon"    => "ti ti-chart-pie",
                                        "comment" => __("Display percent of request sources for closed tickets", "mydashboard")],
            $this->getType() . "31" => ["title"   => __("Number of tickets per location per period", "mydashboard"),
                                        "icon"    => "ti ti-chart-pie",
                                        "comment" => ""],

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
                  $datas[] = round(($data['nb'] * 100) / $total, 2);
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
                            'format'          => $format
            ];

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
                  $datas[] = round(($data['nb'] * 100) / $total, 2);
                  $tabrequest[] = $data['requesttypes_id'];
               }
            }
            $widget = new PluginMydashboardHtml();
            $title  = __("Percent of use of request sources", "mydashboard");
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

         default:
            break;
      }
   }
}
