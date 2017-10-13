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
 * Class PluginMydashboardInfotel
 */
class PluginMydashboardInfotel extends CommonGLPI {

   private $options;

   /**
    * PluginMydashboardInfotel constructor.
    *
    * @param array $_options
    */
   public function __construct($_options = array()) {
      $this->options = $_options;
   }

   /**
    * @return array
    */
   public function getWidgetsForItem() {
      return array(
         __('Public') => array($this->getType() . "1"  => __("Opened tickets backlog", "mydashboard"),
                               $this->getType() . "2"  => __("Number of opened tickets by priority", "mydashboard"),
                               $this->getType() . "3"  => __("Internal annuary", "mydashboard"),
                               $this->getType() . "4"  => __("Mails collector", "mydashboard"),
                               //$this->getType()."5" => __("Logged users","mydashboard"),
                               $this->getType() . "6"  => __("Tickets stock by month", "mydashboard"),
                               $this->getType() . "7"  => __("Top ten ticket authors of the previous month", "mydashboard"),
                               $this->getType() . "8"  => __("Process time by tech by month", "mydashboard"),
                               $this->getType() . "9"  => __('Automatic actions in error', 'mydashboard'),
                               $this->getType() . "10" => __("User ticket alerts", "mydashboard"),
                               $this->getType() . "11" => __("GLPI Status", "mydashboard"),
                               $this->getType() . "12" => __("TTR Compliance", "mydashboard"),
                               $this->getType() . "13" => __("TTO Compliance", "mydashboard"),
                               $this->getType() . "14" => __("All unpublished articles"),
         )
      );
   }

   public function cronMydashboardInfotelUpdateStockTicket() {
      global $DB;

      $year  = date("Y");
      $month = date("m") - 1;

      if ($month == 0) {
         $month = 12;
         $year  = $year - 1;
      }
      $nbdays  = date("t", mktime(0, 0, 0, $month, 1, $year));
      $query   = "SELECT COUNT(*) as count FROM glpi_plugin_mydashboard_stocktickets WHERE glpi_plugin_mydashboard_stocktickets.date = '$year-$month-$nbdays'";
      $results = $DB->query($query);
      $data    = $DB->fetch_array($results);
      if ($data["count"] > 0) {
         die("stock tickets of $year-$month is already filled");
      }
      echo "fill table <glpi_plugin_mydashboard_stocktickets> with datas of $year-$month";
      $nbdays  = date("t", mktime(0, 0, 0, $month, 1, $year));
      $query   = "SELECT COUNT(*) as count,`glpi_tickets`.`entities_id` FROM `glpi_tickets`
                  WHERE `glpi_tickets`.`is_deleted` = '0' AND (((`glpi_tickets`.`date` <= '$year-$month-$nbdays 23:59:59') AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . "))) GROUP BY `glpi_tickets`.`entities_id`";
      $results = $DB->query($query);
      while ($data = $DB->fetch_array($results)) {
         $query = "INSERT INTO `glpi_plugin_mydashboard_stocktickets` (`id`,`date`,`nbstocktickets`,`entities_id`) VALUES (NULL,'$year-$month-$nbdays'," . $data['count'] . "," . $data['entities_id'] . ")";
         $DB->query($query);
      }
   }

   /**
    * @param $widgetId
    *
    * @return PluginMydashboardDatatable|PluginMydashboardHBarChart|PluginMydashboardHtml|PluginMydashboardLineChart|PluginMydashboardPieChart|PluginMydashboardVBarChart
    */
   public function getWidgetContentForItem($widgetId) {
      global $DB, $CFG_GLPI;

      switch ($widgetId) {
         case $this->getType() . "1":
            $query = "SELECT DISTINCT
                           DATE_FORMAT(`date`, '%b %Y') AS period_name,
                           COUNT(`glpi_tickets`.`id`) AS nb,
                           DATE_FORMAT(`date`, '%y%m') AS period
                        FROM `glpi_tickets`
                        LEFT JOIN `glpi_groups_tickets` 
                        ON (`glpi_groups_tickets`.`tickets_id` = `glpi_tickets`.`id` AND `glpi_groups_tickets`.`type` = '" . CommonITILActor::ASSIGN . "')
                        WHERE glpi_tickets.is_deleted = '0'";
            if (isset($this->options['groups_id']) && ($this->options['groups_id'] != 0)) {
               $query .= " AND `glpi_groups_tickets`.`groups_id` = " . $this->options['groups_id'] . " ";
            }
            $query .= getEntitiesRestrictRequest("AND", Ticket::getTable())
                      . " AND `status` NOT IN (" . CommonITILObject::WAITING . "," . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")
                        GROUP BY period_name ORDER BY period ASC";

            $widget = PluginMydashboardHelper::getWidgetsFromDBQuery('vbarchart', $query);

            $dropdown = PluginMydashboardHelper::getFormHeader($widgetId) . Group::dropdown(array('name'      => 'groups_id',
                                                                                                  'display'   => false,
                                                                                                  'value'     => isset($this->options['groups_id']) ? $this->options['groups_id'] : 0,
                                                                                                  'entity'    => $_SESSION['glpiactiveentities'],
                                                                                                  'condition' => '`is_assign`'))
                        . "</form>";
            $widget->setWidgetTitle(__("Opened tickets backlog", "mydashboard"));
            $widget->setOption("xaxis", array("ticks" => PluginMydashboardBarChart::getTicksFromLabels($widget->getTabDatas())));
            $widget->setOption("markers", array("show" => true, "position" => "ct", "labelFormatter" => PluginMydashboardBarChart::getLabelFormatter(2)));
            $widget->setOption('legend', array('show' => false));
            $widget->appendWidgetHtmlContent($dropdown);
            $widget->toggleWidgetRefresh();
            return $widget;
            break;

         case $this->getType() . "2":
            $query = "SELECT DISTINCT
                           `priority`,
                           COUNT(`id`) AS nb
                        FROM `glpi_tickets`
                        WHERE `glpi_tickets`.`is_deleted` = '0'";
            $query .= getEntitiesRestrictRequest("AND", Ticket::getTable())
                      . " AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")
                        GROUP BY `priority` ORDER BY `priority` ASC";

            $widget = PluginMydashboardHelper::getWidgetsFromDBQuery('piechart', $query);
            $datas  = $widget->getTabDatas();

            $colors = array();
            foreach ($datas as $k => $v) {
               $name         = CommonITILObject::getPriorityName($k);
               $colors[]     = $_SESSION["glpipriority_" . $k];
               $datas[$name] = $v;
               unset($datas[$k]);
            }
            $widget->setOption('colors', $colors, true);
            $widget->setTabDatas($datas);
            $widget->toggleWidgetRefresh();
            $widget->setWidgetTitle(__("Number of opened tickets by priority", "mydashboard"));

            return $widget;
            break;

         case $this->getType() . "3":
            $profile_user = new Profile_User();
            $users        = $profile_user->find(getEntitiesRestrictRequest("", "glpi_profiles_users", "entities_id", '', true));
            $filtredUsers = array();
            foreach ($users as $user) {
               $filtredUsers[$user['users_id']] = $user['users_id'];
            }
            $query = "SELECT `firstname`, `realname`, `name`, `phone`, `phone2`, `mobile`
                        FROM `glpi_users`
                        WHERE `glpi_users`.`is_deleted` = '0'
                        AND `id` IN ('" . implode("','", $filtredUsers) . "')
                        AND `glpi_users`.`is_active`
                        AND NOT `glpi_users`.`firstname` = ''
                        AND `glpi_users`.`firstname` IS NOT NULL
                        AND NOT `glpi_users`.`realname` = ''
                        AND `glpi_users`.`realname` IS NOT NULL
                        AND ((NOT `glpi_users`.`phone` = ''
                        AND `glpi_users`.`phone` IS NOT NULL)
                        OR (NOT `glpi_users`.`phone2` = ''
                        AND `glpi_users`.`phone2` IS NOT NULL)
                        OR (NOT `glpi_users`.`mobile` = ''
                        AND `glpi_users`.`mobile` IS NOT NULL))
                        ORDER BY `realname`, `firstname` ASC";

            $widget  = PluginMydashboardHelper::getWidgetsFromDBQuery('table', $query);
            $headers = array(__('First name'), __('Name'), __('Login'), __('Phone'), __('Phone 2'), __('Mobile phone'));
            $hidden  = array(__('Login'));
            $widget->setTabNames($headers);
            $widget->setTabNamesHidden($hidden);
            $widget->toggleWidgetRefresh();
            $widget->setWidgetTitle(__("Internal annuary", "mydashboard"));

            return $widget;
            break;


         case $this->getType() . "4":

            $query = "SELECT `glpi_notimportedemails`.`date`,`glpi_notimportedemails`.`from`,`glpi_notimportedemails`.`reason`
                        FROM `glpi_notimportedemails`
                        ORDER BY `glpi_notimportedemails`.`date` ASC";

            $widget  = PluginMydashboardHelper::getWidgetsFromDBQuery('table', $query);
            $headers = array(__('Date'), __('From email header'), __('Reason of rejection'));
            $widget->setTabNames($headers);

            $result = $DB->query($query);
            $nb     = $DB->numrows($result);

            $datas = array();
            $i     = 0;
            if ($nb) {
               while ($data = $DB->fetch_assoc($result)) {


                  $datas[$i]["date"] = Html::convDateTime($data['date']);

                  $datas[$i]["from"] = $data['from'];

                  $datas[$i]["reason"] = NotImportedEmail::getReason($data['reason']);

                  $i++;
               }

            }

            $widget->setTabDatas($datas);

            $widget->toggleWidgetRefresh();
            $widget->setWidgetTitle(__("Mails collector", "mydashboard"));

            return $widget;
            break;
            /*case $this->getType()."5":
               $users = array();
               $inactiveTime = 60; //seconds of inactivity
               foreach (glob(GLPI_SESSION_DIR."/sess_*") as $filename) {
                  if ((filemtime($filename) + $inactiveTime) > time()) {
                     $dh = fopen($filename,'r');
                     if($dh) {
                        //We save current session, session_decode put values directly in $_SESSION
                        $currentSess = $_SESSION;
                        session_decode(file_get_contents($filename));
                        $search = $_SESSION;
                        //We restore current session
                        $_SESSION = $currentSess;
                        if (count($search) > 0) {
                           if (isset($search["glpiID"])) {
                              $users[$search["glpiID"]]= array($search["glpifirstname"],
                                                               $search["glpirealname"],
                                                               $search["glpiname"],
                                                               $this->getSeeProfileButton($search["glpiID"])) ;
                           }
                        }
                        fclose($dh);
                     }   
                  }
               }*/

            $widget  = new PluginMydashboardDatatable();
            $headers = array(__('First name'), __('Name'), __('Login'), '');
            $widget->setTabDatas($users);
            $widget->setTabNames($headers);
            $widget->toggleWidgetRefresh();
            $widget->setWidgetTitle(__("Logged users", "mydashboard"));

            return $widget;
            break;
         case $this->getType() . "6":

            if (isset($this->options['entities_id']) && ($this->options['entities_id'] != 0)) {
               if (isset($this->options['sons']) && ($this->options['sons'] != 0)) {
                  $entities  = " AND `glpi_tickets`.`entities_id` IN  (" . implode(",", getSonsOf("glpi_entities", $this->options['entities_id'])) . ") ";
                  $entities2 = " `glpi_plugin_mydashboard_stocktickets`.`entities_id` IN  (" . implode(",", getSonsOf("glpi_entities", $this->options['entities_id'])) . ") ";
               } else {
                  $entities  = " AND `glpi_tickets`.`entities_id` = " . $this->options['entities_id'] . " ";
                  $entities2 = " `glpi_plugin_mydashboard_stocktickets`.`entities_id` = " . $this->options['entities_id'] . " ";
               }
            } else {
               //$entities =getEntitiesRestrictRequest();
               $entities  = " AND `glpi_tickets`.`entities_id` = " . $_SESSION['glpiactive_entity'] . " ";
               $entities2 = " `glpi_plugin_mydashboard_stocktickets`.`entities_id` = " . $_SESSION['glpiactive_entity'] . " ";
            }


            $currentmonth = date("m");
            $currentyear  = date("Y");
            $previousyear = $currentyear - 1;
            $query_2      = "SELECT DATE_FORMAT(`glpi_plugin_mydashboard_stocktickets`.`date`, '%Y-%m') as month, DATE_FORMAT(`glpi_plugin_mydashboard_stocktickets`.`date`, '%b %Y') as monthname, SUM(nbStockTickets) as nbStockTickets FROM `glpi_plugin_mydashboard_stocktickets` WHERE " . $entities2 . " AND (`glpi_plugin_mydashboard_stocktickets`.`date` >= '$previousyear-$currentmonth-01 00:00:00') AND (`glpi_plugin_mydashboard_stocktickets`.`date` <= '$currentyear-$currentmonth-01 00:00:00')  GROUP BY DATE_FORMAT(`glpi_plugin_mydashboard_stocktickets`.`date`, '%Y-%m')";

            $tabdata  = array();
            $tabnames = array();
            $results2 = $DB->query($query_2);
            $maxcount = 0;
            $i        = 0;


            while ($data = $DB->fetch_array($results2)) {
               $tabdata[$i] = $data["nbStockTickets"];
               $tabnames[]  = array($i, $data['monthname']);
               if ($data["nbStockTickets"] > $maxcount) {
                  $maxcount = $data["nbStockTickets"];
               }
               $i++;
            }


            $query = "SELECT DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m') AS month, 
                        DATE_FORMAT(`glpi_tickets`.`date`, '%b %Y') AS monthname, 
                        DATE_FORMAT(`glpi_tickets`.`date`, '%Y%m') AS monthnum, count(MONTH(`glpi_tickets`.`date`))
                        FROM `glpi_tickets`
                        WHERE `glpi_tickets`.`is_deleted` = 0 "
                     . $entities . " 
                     AND MONTH(`glpi_tickets`.`date`)='" . date("m") . "' 
                     AND(YEAR(`glpi_tickets`.`date`) = '" . date("Y") . "') 
                     GROUP BY DATE_FORMAT(`glpi_tickets`.`date`, '%Y-%m')";


            $results = $DB->query($query);
            while ($data = $DB->fetch_array($results)) {

               list($year, $month) = explode('-', $data['month']);

               $nbdays  = date("t", mktime(0, 0, 0, $month, 1, $year));
               $query_1 = "SELECT COUNT(*) as count FROM `glpi_tickets`
                     WHERE `glpi_tickets`.`is_deleted` = '0' " . $entities . "
                     AND (((`glpi_tickets`.`date` <= '$year-$month-$nbdays 23:59:59') 
                     AND `status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")) 
                     OR ((`glpi_tickets`.`date` <= '$year-$month-$nbdays 23:59:59') 
                     AND (`glpi_tickets`.`solvedate` > ADDDATE('$year-$month-$nbdays 00:00:00' , INTERVAL 1 DAY))))";

               $results_1 = $DB->query($query_1);
               $data_1    = $DB->fetch_array($results_1);

               $tabdata[$i] = $data_1['count'];

               if ($data_1['count'] > $maxcount) {
                  $maxcount = $data_1['count'];
               }
               $tabnames[] = array($i, $data['monthname']);
               $i++;
            }

            $widget = new PluginMydashboardLineChart();

            $dropdown = PluginMydashboardHelper::getFormHeader($widgetId) . Entity::dropdown(array('name'    => 'entities_id',
                                                                                                   'display' => false,
                                                                                                   'value'   => isset($this->options['entities_id']) ? $this->options['entities_id'] : $_SESSION['glpiactive_entity']))
                        . "&nbsp;" . __('Recursive') . "&nbsp;<input type='checkbox' name='sons' value=1 " . (isset($this->options['sons']) ? "checked" : "") . "></form>";

            $widget->setWidgetTitle(__("Tickets stock", "mydashboard"));
            $widget->setTabDatas(array(__("Tickets stock by month", "mydashboard") => $tabdata));
            $widget->setOption("xaxis", array("ticks" => $tabnames));
            $widget->setOption("yaxis", array("max" => $maxcount + 20, "min" => 0));

            $widget->setOption("markers", array("show" => true, "position" => "lt", "labelFormatter" => PluginMydashboardBarChart::getLabelFormatter(2)));
            $widget->setOption("lines", array("fillColor" => 'lightblue', "fill" => true));
            $widget->appendWidgetHtmlContent($dropdown);
            $widget->toggleWidgetRefresh();
            //$widget->setOption("series", array("curvedLines" => array("active" => true)));
            //               $widget->setOption("mouse", array("track" => true, "lineColor" => 'purple',
            //                                    "relative" => true,
            //                                    "position" =>'ne',
            //                                    "sensibility" => 1,
            //                                    "trackDecimals"  => 2,
            //                                    "trackFormatter"  => "function (o) { return 'x = ' + o.x +', y = ' + o.y; }"));
            //               $widget->setOption("crosshair", array("mode" => "xy"));

            return $widget;


            break;
         case $this->getType() . "7":

            if (isset($this->options['entities_id']) && ($this->options['entities_id'] != 0)) {
               if (isset($this->options['sons']) && ($this->options['sons'] != 0)) {
                  $entities = " AND `glpi_tickets`.`entities_id` IN  (" . implode(",", getSonsOf("glpi_entities", $this->options['entities_id'])) . ") ";
               } else {
                  $entities = " AND `glpi_tickets`.`entities_id` = " . $this->options['entities_id'] . " ";
               }
            } else {
               //$entities =getEntitiesRestrictRequest();
               $entities = " AND `glpi_tickets`.`entities_id` = " . $_SESSION['glpiactive_entity'] . " ";
            }

            $mois  = intval(strftime("%m") - 1);
            $annee = intval(strftime("%Y") - 1);

            if ($mois > 0) {
               $annee = strftime("%Y");
            } else {
               $mois = 12;
            }
            $nbjours  = date("t", mktime(0, 0, 0, $mois, 1, $annee));
            $query    = "SELECT `glpi_tickets_users`.`users_id` as users_id, COUNT(`glpi_tickets`.`id`) as count
                     FROM `glpi_tickets`
                     LEFT JOIN `glpi_tickets_users`
                        ON (`glpi_tickets_users`.`tickets_id` = `glpi_tickets`.`id` AND `glpi_tickets_users`.`type` = 1)
                     WHERE (`glpi_tickets`.`date` >= '$annee-$mois-01 00:00:01' AND `glpi_tickets`.`date` <= ADDDATE('$annee-$mois-$nbjours 00:00:00' , INTERVAL 1 DAY) )
                     " . $entities . "
                     AND `glpi_tickets`.`is_deleted` = '0'
                     GROUP BY `glpi_tickets_users`.`users_id`
                     ORDER BY count DESC
                     LIMIT 10";
            $widget   = PluginMydashboardHelper::getWidgetsFromDBQuery('piechart', $query);
            $dropdown = PluginMydashboardHelper::getFormHeader($widgetId) . Entity::dropdown(array('name'    => 'entities_id',
                                                                                                   'display' => false,
                                                                                                   'value'   => isset($this->options['entities_id']) ? $this->options['entities_id'] : $_SESSION['glpiactive_entity']))
                        . "&nbsp;" . __('Recursive') . "&nbsp;<input type='checkbox' name='sons' value=1 " . (isset($this->options['sons']) ? "checked" : "") . "></form>";

            $datas = $widget->getTabDatas();

            foreach ($datas as $k => $v) {

               if (!empty($k)) {
                  $name = getUserName($k);
               } else {
                  $name = __('None');
               }
               $datas[$name] = $v;
               unset($datas[$k]);
            }
            $widget->setTabDatas($datas);
            $widget->appendWidgetHtmlContent($dropdown);
            $widget->toggleWidgetRefresh();
            $widget->setWidgetTitle(__("Top ten ticket authors of the previous month", "mydashboard"));

            return $widget;
            break;

         case $this->getType() . "8":

            $time_per_tech = self::getTimePerTech($this->options);
            $months_t      = Toolbox::getMonthsOfYearArray();
            $months        = array();
            foreach ($months_t as $key => $month) {
               $months[] = array($key, $month);
            }

            foreach ($time_per_tech as $tech_id => $times) {
               unset($time_per_tech[$tech_id]);
               $username                 = getUserName($tech_id);
               $time_per_tech[$username] = $times;
            }
            $widget   = new PluginMydashboardVBarChart();
            $dropdown = PluginMydashboardHelper::getFormHeader($widgetId, true) . Entity::dropdown(array('name'    => 'entities_id',
                                                                                                         'display' => false,
                                                                                                         'value'   => isset($this->options['entities_id']) ? $this->options['entities_id'] : $_SESSION['glpiactive_entity']))
                        . "&nbsp;" . __('Recursive') . "&nbsp;<input type='checkbox' name='sons' value=1 " . (isset($this->options['sons']) ? "checked" : "") . ">"
                        . "<input type='submit' value='" . __("Show", "mydashboard") . "' class='submit' />"
                        . "</form>";

            $widget->setOption("bars", array("stacked" => true, "fillOpacity" => 0.8, "shadowSize" => 0));
            $colors = array(
               "#1f77b4", "#aec7e8", "#ff7f0e", "#ffbb78", "#2ca02c",
               "#98df8a", "#d62728", "#ff9896", "#9467bd", "#c5b0d5",
               "#8c564b", "#c49c94", "#e377c2", "#f7b6d2", "#7f7f7f",
               "#c7c7c7", "#bcbd22", "#dbdb8d", "#17becf", "#9edae5"
            );
            $widget->setOption('colors', $colors, true);
            $widget->setTabDatas($time_per_tech);
            $widget->setOption("xaxis", array("ticks" => $months));
            $widget->setOption("xaxis", array("max" => 13, "min" => 0.5));
            //$widget->setOption("markers", array("show" => true,"position" => "cb","stacked" => true, "stackingType" => 'a'));
            $widget->setOption('legend', array(
               'position' => 'no'
               //'show' => false
            ));
            $widget->appendWidgetHtmlContent($dropdown);
            $widget->toggleWidgetRefresh();
            $widget->setWidgetTitle(__("Process time by tech by month", "mydashboard"));

            return $widget;

            break;
         case $this->getType() . "9":

            $query = "SELECT *
                FROM `glpi_crontasks`
                WHERE `state` = '" . CronTask::STATE_RUNNING . "'
                      AND ((unix_timestamp(`lastrun`) + 2 * `frequency` < unix_timestamp(now()))
                           OR (unix_timestamp(`lastrun`) + 2*" . HOUR_TIMESTAMP . " < unix_timestamp(now())))";

            $widget  = PluginMydashboardHelper::getWidgetsFromDBQuery('table', $query);
            $headers = array(__('Last run'), __('Name'), __('Status'));
            $widget->setTabNames($headers);

            $result = $DB->query($query);
            $nb     = $DB->numrows($result);

            $datas = array();
            $i     = 0;
            if ($nb) {
               while ($data = $DB->fetch_assoc($result)) {


                  $datas[$i]["lastrun"] = Html::convDateTime($data['lastrun']);

                  $name = $data["name"];
                  if ($isplug = isPluginItemType($data["itemtype"])) {
                     $name = sprintf(__('%1$s - %2$s'), $isplug["plugin"], $name);
                  }

                  $datas[$i]["name"] = $name;

                  $datas[$i]["state"] = CronTask::getStateName($data["state"]);

                  $i++;
               }

            }

            $widget->setTabDatas($datas);

            $widget->toggleWidgetRefresh();
            $widget->setWidgetTitle(__('Automatic actions in error', 'mydashboard'));

            return $widget;
            break;
         case $this->getType() . "10":
            $widget = new PluginMydashboardHtml();

            $link_ticket = Toolbox::getItemTypeFormURL("Ticket");

            $mygroups = Group_User::getUserGroups(Session::getLoginUserID(), "`is_assign`");
            $groups   = array();
            foreach ($mygroups as $mygroup) {
               $groups[] = $mygroup["id"];
            }
            //$entities = " AND `glpi_tickets`.`entities_id` = ".$_SESSION['glpiactive_entity']." ";
            $entities = " AND `glpi_tickets`.`entities_id` IN  (" . implode(",", $_SESSION['glpiactiveentities']) . ") ";
            $query    = "SELECT  `glpi_tickets`.`id` as tickets_id, 
                                 `glpi_tickets`.`status` as status, 
                                 `glpi_tickets`.`date_mod` as date_mod
                        FROM `glpi_tickets`
                        LEFT JOIN `glpi_entities` ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`)
                        WHERE `glpi_tickets`.`is_deleted` = '0'
                        AND `glpi_tickets`.`status` != '" . CommonITILObject::CLOSED . "'
                        AND `glpi_tickets`.`date_mod` != `glpi_tickets`.`date` $entities";

            $query .= "ORDER BY `glpi_tickets`.`date_mod` DESC";//

            $widget  = PluginMydashboardHelper::getWidgetsFromDBQuery('table', $query);
            $headers = array(__('ID'), _n('Requester', 'Requesters', 2), __('Status'), __('Last update'), __('Assigned to'), __('Action'));
            $widget->setTabNames($headers);

            $result = $DB->query($query);
            $nb     = $DB->numrows($result);

            $datas = array();

            if ($nb) {
               $i = 0;
               while ($data = $DB->fetch_assoc($result)) {

                  $ticket = new Ticket();
                  $ticket->getFromDB($data['tickets_id']);

                  $users_requesters = array();
                  $userdata         = '';
                  if ($ticket->countUsers(CommonITILActor::REQUESTER)) {

                     foreach ($ticket->getUsers(CommonITILActor::REQUESTER) as $u) {
                        $k                                = $u['users_id'];
                        $users_requesters[$u['users_id']] = $u['users_id'];

                        if ($k) {
                           $userdata .= getUserName($k);
                        }


                        if ($ticket->countUsers(CommonITILActor::REQUESTER) > 1) {
                           $userdata .= "<br>";
                        }
                     }
                  }
                  if (in_array($ticket->fields['users_id_lastupdater'], $users_requesters)) {

                     $ticketfollowup = new TicketFollowup();
                     $followups      = $ticketfollowup->find("`tickets_id` = " . $ticket->fields['id'], 'date DESC');

                     $ticketdocument = new Document();
                     $documents      = $ticketdocument->find("`tickets_id` = " . $ticket->fields['id'], 'date_mod DESC');

                     if ((count($followups) > 0 && current($followups)['date'] >= $ticket->fields['date_mod'])
                         || (count($documents) > 0 && current($documents)['date_mod'] >= $ticket->fields['date_mod'])) {

                        $bgcolor = $_SESSION["glpipriority_" . $ticket->fields["priority"]];

                        $name_ticket = "<div class='center' style='background-color:$bgcolor; padding: 10px;'>";
                        $name_ticket .= "<a href='" . $link_ticket . "?id=" . $data['tickets_id'] . "' target='_blank'>";
                        $name_ticket .= sprintf(__('%1$s: %2$s'), __('ID'), $data['tickets_id']);
                        $name_ticket .= "</a>";
                        $name_ticket .= "</div>";


                        $datas[$i]["tickets_id"] = $name_ticket;


                        $datas[$i]["users_id"] = $userdata;

                        $datas[$i]["status"] = Ticket::getStatus($data['status']);

                        $datas[$i]["date_mod"] = Html::convDateTime($data['date_mod']);

                        $techdata = '';
                        if ($ticket->countUsers(CommonITILActor::ASSIGN)) {

                           foreach ($ticket->getUsers(CommonITILActor::ASSIGN) as $u) {
                              $k = $u['users_id'];
                              if ($k) {
                                 $techdata .= getUserName($k);
                              }


                              if ($ticket->countUsers(CommonITILActor::ASSIGN) > 1) {
                                 $techdata .= "<br>";
                              }
                           }
                           $techdata .= "<br>";
                        }

                        if ($ticket->countGroups(CommonITILActor::ASSIGN)) {

                           foreach ($ticket->getGroups(CommonITILActor::ASSIGN) as $u) {
                              $k = $u['groups_id'];
                              if ($k) {
                                 $techdata .= Dropdown::getDropdownName("glpi_groups", $k);
                              }


                              if ($ticket->countGroups(CommonITILActor::ASSIGN) > 1) {
                                 $techdata .= "<br>";
                              }
                           }
                        }
                        $datas[$i]["techs_id"] = $techdata;

                        $action = "";

                        if (count($followups) > 0) {
                           reset($followups);
                           if (current($followups)['date'] >= $ticket->fields['date_mod']) {
                              $action .= __('New followup');
                           }
                        }
                        if (count($documents) > 0) {
                           if (current($documents)['date_mod'] >= $ticket->fields['date_mod']) {
                              $action .= __('New document', "mydashboard");
                           }
                        }
                        $datas[$i]["action"] = $action;

                        $i++;
                     }
                  }
               }
            }

            $widget->setTabDatas($datas);
            $widget->setOption("bSort", false);
            $widget->toggleWidgetRefresh();


            $widget->setWidgetTitle(__("User ticket alerts", "mydashboard"));

            return $widget;
            break;
         case $this->getType() . "11":

            $widget = new PluginMydashboardHtml();
            $url    = $CFG_GLPI['url_base'] . "/status.php";
            //$url = "http://localhost/glpi/status.php";
            $options = array("url" => $url);

            $contents = self::cURLData($options);
            $contents = nl2br($contents);

            $table = "<table class='tab_cadre' width='100%'><tr><th>";
            $table .= self::handleShellcommandResult($contents, $url);
            $table .= "</th></tr>";
            $table .= "<tr class='tab_bg_1'><td>";
            $table .= $contents;
            $table .= "</td></tr></table>";
            $widget->setWidgetHtmlContent(
               $table
            );
            $widget->toggleWidgetRefresh();

            $widget->setWidgetTitle(__("GLPI Status", "mydashboard"));

            return $widget;
            break;

         case $this->getType() . "12":

            $all = "SELECT DISTINCT COUNT(`glpi_tickets`.`id`) AS nb
                        FROM `glpi_tickets`
                        WHERE `glpi_tickets`.`is_deleted` = '0'
                        AND `glpi_tickets`.`solvedate` IS NOT NULL
                        AND `glpi_tickets`.`due_date` IS NOT NULL ";// AND ".getDateRequest("`$table`.`solvedate`", $begin, $end)."
            $all .= getEntitiesRestrictRequest("AND", Ticket::getTable())
                    . " AND `status` IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ") ";

            $result = $DB->query($all);
            $total  = $DB->fetch_assoc($result);

            //toolbox::logdebug($total);

            $query = "SELECT DISTINCT `glpi_tickets`.`slts_ttr_id`, 
                        COUNT(`glpi_tickets`.`id`) AS nb
                        FROM `glpi_tickets`
                        WHERE `glpi_tickets`.`is_deleted` = '0'
                        AND `glpi_tickets`.`solvedate` IS NOT NULL
                        AND `glpi_tickets`.`due_date` IS NOT NULL
                        AND `glpi_tickets`.`slts_ttr_id` > 0
                        AND `glpi_tickets`.`solvedate` > `glpi_tickets`.`due_date`";// AND ".getDateRequest("`$table`.`solvedate`", $begin, $end)."

            $query .= getEntitiesRestrictRequest("AND", Ticket::getTable())
                      . " AND `status` IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ") 
                        GROUP BY slts_ttr_id";
            //toolbox::logdebug($query);
            $widget = PluginMydashboardHelper::getWidgetsFromDBQuery('piechart', $query);
            $datas  = $widget->getTabDatas();
            $sum    = 0;

            foreach ($datas as $k => $v) {
               $sum += $v;
            }
            $data                                         = array();
            $notrespected                                 = $sum;
            $respected                                    = $total['nb'] - $sum;
            $data[__("Respected TTR", "mydashboard")]     = $respected;
            $data[__("Not respected TTR", "mydashboard")] = $notrespected;
            $widget->setTabDatas($data);
            $widget->toggleWidgetRefresh();
            $widget->setWidgetTitle(__("TTR Compliance", "mydashboard"));

            return $widget;
            break;
         case $this->getType() . "13":

            $all = "SELECT DISTINCT COUNT(`glpi_tickets`.`id`) AS nb
                        FROM `glpi_tickets`
                        WHERE `glpi_tickets`.`is_deleted` = '0'
                        AND `glpi_tickets`.`takeintoaccount_delay_stat` IS NOT NULL
                        AND `glpi_tickets`.`time_to_own` IS NOT NULL ";// AND ".getDateRequest("`$table`.`solvedate`", $begin, $end)."
            $all .= getEntitiesRestrictRequest("AND", Ticket::getTable());
            //." AND `status` IN (".CommonITILObject::SOLVED.",".CommonITILObject::CLOSED.") ";

            $result = $DB->query($all);
            $total  = $DB->fetch_assoc($result);

            $query = "SELECT DISTINCT `glpi_tickets`.`slts_tto_id`, 
                        COUNT(`glpi_tickets`.`id`) AS nb
                        FROM `glpi_tickets`
                        WHERE `glpi_tickets`.`is_deleted` = '0'
                        AND `glpi_tickets`.`takeintoaccount_delay_stat` IS NOT NULL
                        AND `glpi_tickets`.`time_to_own` IS NOT NULL
                        AND `glpi_tickets`.`slts_tto_id` > 0
                        AND ((UNIX_TIMESTAMP(`glpi_tickets`.`time_to_own`) - `glpi_tickets`.`date`) < `glpi_tickets`.`takeintoaccount_delay_stat`) ";// AND ".getDateRequest("`$table`.`takeintoaccount_delay_stat`", $begin, $end)."

            $query .= getEntitiesRestrictRequest("AND", Ticket::getTable())
                      //." AND `status` IN (".CommonITILObject::SOLVED.",".CommonITILObject::CLOSED.")
                      . "  GROUP BY slts_tto_id";

            $widget = PluginMydashboardHelper::getWidgetsFromDBQuery('piechart', $query);
            $datas  = $widget->getTabDatas();
            $sum    = 0;

            foreach ($datas as $k => $v) {
               $sum += $v;
            }
            $data                                         = array();
            $notrespected                                 = $sum;
            $respected                                    = $total['nb'] - $sum;
            $data[__("Respected TTO", "mydashboard")]     = $respected;
            $data[__("Not respected TTO", "mydashboard")] = $notrespected;
            $widget->setTabDatas($data);
            $widget->toggleWidgetRefresh();
            $widget->setWidgetTitle(__("TTO Compliance", "mydashboard"));

            return $widget;
            break;

         case $this->getType() . "14":
            $query = "SELECT DISTINCT `glpi_knowbaseitems`.*, `glpi_knowbaseitemcategories`.`completename` AS category 
                     FROM `glpi_knowbaseitems` 
                     LEFT JOIN `glpi_knowbaseitems_users` ON (`glpi_knowbaseitems_users`.`knowbaseitems_id` = `glpi_knowbaseitems`.`id`) 
                     LEFT JOIN `glpi_groups_knowbaseitems` ON (`glpi_groups_knowbaseitems`.`knowbaseitems_id` = `glpi_knowbaseitems`.`id`) 
                     LEFT JOIN `glpi_knowbaseitems_profiles` ON (`glpi_knowbaseitems_profiles`.`knowbaseitems_id` = `glpi_knowbaseitems`.`id`) 
                     LEFT JOIN `glpi_entities_knowbaseitems` ON (`glpi_entities_knowbaseitems`.`knowbaseitems_id` = `glpi_knowbaseitems`.`id`) 
                     LEFT JOIN `glpi_knowbaseitemcategories` ON (`glpi_knowbaseitemcategories`.`id` = `glpi_knowbaseitems`.`knowbaseitemcategories_id`) 
                     WHERE (`glpi_entities_knowbaseitems`.`entities_id` IS NULL 
                     AND `glpi_knowbaseitems_profiles`.`profiles_id` IS NULL 
                     AND `glpi_groups_knowbaseitems`.`groups_id` IS NULL 
                     AND `glpi_knowbaseitems_users`.`users_id` IS NULL)";

            $widget = PluginMydashboardHelper::getWidgetsFromDBQuery('table', $query);
            $datas  = $widget->getTabDatas();

            $headers = array(__('Subject'), __('Writer'), __('Category'));
            $widget->setTabNames($headers);

            $result = $DB->query($query);
            $nb     = $DB->numrows($result);

            $datas = array();
            $i     = 0;

            $knowbaseitem = new KnowbaseItem();
            if ($nb) {
               while ($data = $DB->fetch_assoc($result)) {
                  $knowbaseitem->getFromDB($data['id']);

                  $datas[$i]["name"] = $knowbaseitem->getLink();
                  $showuserlink      = 0;
                  if (Session::haveRight('user', READ)) {
                     $showuserlink = 1;
                  }
                  $datas[$i]["users"]    = getUserName($data["users_id"], $showuserlink);
                  $datas[$i]["category"] = $data["category"];

                  $i++;
               }
            }

            $widget->setTabDatas($datas);

            $widget->toggleWidgetRefresh();
            $widget->setWidgetTitle(__('All unpublished articles'));
            return $widget;

            break;
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
         $alert .= "<style>img.middle {vertical-align:middle;}></style>";
         $alert .= "<img class='middle' src='" . $CFG_GLPI["root_doc"] . "/plugins/mydashboard/pics/warning.png'></style>&nbsp;";
         $alert .= __("Problem with GLPI", "mydashboard");
         $alert .= "&nbsp;<img class='middle' src='" . $CFG_GLPI["root_doc"] . "/plugins/mydashboard/pics/warning.png'>";
      } elseif (preg_match('/OK/is', $message)) {
         $alert .= "<style>img.middle {vertical-align:middle;}></style>";
         $alert .= "<img class='middle' src='" . $CFG_GLPI["root_doc"] . "/plugins/mydashboard/pics/ok.png'>&nbsp;";
         $alert .= __("GLPI is OK", "mydashboard");
      } else {
         $alert .= __("Alert is not properly configured", "mydashboard");
         $alert .= "<br>" . $url;
      }

      return $alert;
   }

   /**
    * @param $id
    *
    * @return string
    */
   private function getSeeProfileButton($id) {
      global $CFG_GLPI;
      return "<a target='blank' href='" . $CFG_GLPI['root_doc'] . "/front/user.form.php?id=" . $id . "'>"
             . "<input type='button' class='submit' value=' " . __("Show Profile", "mydashboard") . " '/>"
             . "</a>";
   }

   /**
    * @param $table
    * @param $params
    *
    * @return string
    */
   private static function getSpecificEntityRestrict($table, $params) {
      if (isset($params['entities_id']) /*&& ($params['entities_id'] != 0)*/) {
         if (isset($params['sons']) && ($params['sons'] != 0)) {
            $entities = " AND `$table`.`entities_id` IN  (" . implode(",", getSonsOf("glpi_entities", $params['entities_id'])) . ") ";
         } else {
            $entities = " AND `$table`.`entities_id` = " . $params['entities_id'] . " ";
         }
      } else {
         $entities = getEntitiesRestrictRequest("AND", $table/*,'','',isset($params['sons']) && ($params['sons'] != 0),true*/);
      }
      return $entities;
   }

   /**
    * @param $params
    *
    * @return array
    */
   private static function getTimePerTech($params) {
      global $DB;

      $time_per_tech = array();
      $months        = Toolbox::getMonthsOfYearArray();

      $mois = intval(strftime("%m") - 1);
      $year = intval(strftime("%Y") - 1);
      if ($mois > 0) {
         $year = date("Y");
      }

      $groups             = implode(",", $_SESSION['glpigroups']);
      $techlist           = array();
      $query_group_member = "SELECT `glpi_groups_users`.`users_id`"
                            . "FROM `glpi_groups_users` "
                            . "LEFT JOIN `glpi_groups` ON (`glpi_groups_users`.`groups_id` = `glpi_groups`.`id`) "
                            . "WHERE `glpi_groups_users`.`groups_id` IN (" . $groups . ") AND `glpi_groups`.`is_assign` = 1 "
                            . " GROUP BY `glpi_groups_users`.`users_id`";

      $result_gu = $DB->query($query_group_member);

      while ($data = $DB->fetch_assoc($result_gu)) {
         $techlist[] = $data['users_id'];
      }

      $current_month = date("m");
      foreach ($months as $key => $month) {

         if ($key > $current_month && $year == date("Y")) break;

         $next = $key + 1;

         $month_tmp = $key;
         $nb_jours  = date("t", mktime(0, 0, 0, $key, 1, $year));

         if (strlen($key) == 1) $month_tmp = "0" . $month_tmp;
         if (strlen($next) == 1) $next = "0" . $next;

         if ($key == 0) {
            $year      = $year - 1;
            $month_tmp = "12";
            $nb_jours  = date("t", mktime(0, 0, 0, 12, 1, $year));
         }

         $month_deb_date     = "$year-$month_tmp-01";
         $month_deb_datetime = $month_deb_date . " 00:00:00";
         $month_end_date     = "$year-$month_tmp-$nb_jours";
         $month_end_datetime = $month_end_date . " 23:59:59";

         foreach ($techlist as $techid) {
            $time_per_tech[$techid][$key] = 0;

            $querym_ai   = "SELECT  DATE(`glpi_tickettasks`.`date`), SUM(`glpi_tickettasks`.`actiontime`) AS actiontime_date
                        FROM `glpi_tickettasks` 
                        INNER JOIN `glpi_tickets` ON (`glpi_tickets`.`id` = `glpi_tickettasks`.`tickets_id` AND `glpi_tickets`.`is_deleted` = 0) 
                        LEFT JOIN `glpi_entities` ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`) ";
            $querym_ai   .= "WHERE ";
            $querym_ai   .= "(
                           `glpi_tickettasks`.`begin` >= '$month_deb_datetime' 
                           AND `glpi_tickettasks`.`end` <= '$month_end_datetime'
                           AND `glpi_tickettasks`.`users_id_tech` = (" . $techid . ") "
                            . self::getSpecificEntityRestrict("glpi_tickets", $params)
                            . ") 
                        OR (
                           `glpi_tickettasks`.`date` >= '$month_deb_datetime' 
                           AND `glpi_tickettasks`.`date` <= '$month_end_datetime' 
                           AND `glpi_tickettasks`.`users_id`  = (" . $techid . ") 
                           AND `glpi_tickettasks`.`begin` IS NULL "
                            . self::getSpecificEntityRestrict("glpi_tickets", $params)
                            . ")
                           AND `glpi_tickettasks`.`actiontime` != 0 ";
            $querym_ai   .= "GROUP BY DATE(`glpi_tickettasks`.`date`);
                        ";
            $result_ai_q = $DB->query($querym_ai);
            while ($data = $DB->fetch_assoc($result_ai_q)) {
               $time_per_tech[$techid][$key] += (self::TotalTpsPassesArrondis($data['actiontime_date'] / 3600 / 8));
            }
         }

         if ($key == 0) {
            $year++;
         }
      }
      return $time_per_tech;
   }

   /**
    * @param $a_arrondir
    *
    * @return float|int
    */
   static function TotalTpsPassesArrondis($a_arrondir) {

      $tranches_seuil   = 0.002;
      $tranches_arrondi = array(0, 0.25, 0.5, 0.75, 1);

      $result = 0;

      $partie_entiere = floor($a_arrondir);
      $reste          = $a_arrondir - $partie_entiere + 10; // Le + 10 permet de pallier é un probléme de comparaison (??) par la suite.
      /* Initialisation des tranches majorées du seuil supplémentaire. */
      $tranches_majorees = array();
      for ($i = 0; $i < count($tranches_arrondi); $i++) {
         // Le + 10 qui suit permet de pallier é un probléme de comparaison (??) par la suite.
         $tranches_majorees[] = $tranches_arrondi[$i] + $tranches_seuil + 10;
      }
      if ($reste < $tranches_majorees[0]) {
         $result = $partie_entiere;

      } else if ($reste >= $tranches_majorees[0] && $reste < $tranches_majorees[1]) {
         $result = $partie_entiere + $tranches_arrondi[1];

      } else if ($reste >= $tranches_majorees[1] && $reste < $tranches_majorees[2]) {
         $result = $partie_entiere + $tranches_arrondi[2];

      } else if ($reste >= $tranches_majorees[2] && $reste < $tranches_majorees[3]) {
         $result = $partie_entiere + $tranches_arrondi[3];

      } else {
         $result = $partie_entiere + $tranches_arrondi[4];
      }

      return $result;
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
      $timeout     = 10;
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
