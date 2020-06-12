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


   static $types = ['Reminder', 'Problem', 'Change', 'PluginEventsmanagerEvent','PluginReleasesRelease'];
   /**
    * PluginMydashboardAlert constructor.
    *
    * @param array $_options
    */
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
//      if ($item->getType() == 'Reminder'
//          || $item->getType() == 'Problem'
//          || $item->getType() == 'Change'
//          || $item->getType() == 'PluginEventsmanagerEvent'
//          || $item->getType() == 'PluginReleasesRelease') {
//         return _n('Alert Dashboard', 'Alerts Dashboard', 2, 'mydashboard');
//      }
      if(in_array( $item->getType(),self::getTypes())){
         return _n('Alert Dashboard', 'Alerts Dashboard', 2, 'mydashboard');
      }
      return '';
   }

   /**
    * @param bool        $withtemplate
    *
    * @return array of allowed type
    */
   static function getTypes($all = false) {
      if ($all) {
         return self::$types;
      }
      // Only allowed types
      $types = self::$types;
      foreach ($types as $key => $type) {
         if (!class_exists($type)) {
            continue;
         }
         $item = new $type();
         if (!$item->canView()) {
            unset($types[$key]);
         }
      }
      return $types;
   }

   /**
    * @param CommonGLPI $item
    * @param int        $tabnum
    * @param int        $withtemplate
    *
    * @return bool
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      $alert      = new self();
      $itil_alert = new PluginMydashboardItilAlert();
      switch ($item->getType()) {
         case "Reminder":
            $alert->showForm($item);
            break;
         case "Problem":
         case "Change":
         case "PluginReleasesRelease":
            $itil_alert->showForItem($item);
            break;
         default :
            $alert->showForItem($item);
            break;
      }
      return true;
   }

   /**
    * List widgets
    *
    * @return array
    */
   function getWidgetsForItem() {
      return [
         _n('Alert', 'Alerts', 2, 'mydashboard') => [
            $this->getType() . "1"  => _n('Network alert', 'Network alerts', 2, 'mydashboard') . "&nbsp;<i class='fas fa-info-circle'></i>",
            $this->getType() . "2"  => _n('Scheduled maintenance', 'Scheduled maintenances', 2, 'mydashboard') . "&nbsp;<i class='fas fa-info-circle'></i>",
            $this->getType() . "3"  => _n('Information', 'Informations', 2, 'mydashboard') . "&nbsp;<i class='fas fa-info-circle'></i>",
            $this->getType() . "4"  => __("Incidents alerts", "mydashboard") . "&nbsp;<i class='fas fa-info-circle'></i>",
            $this->getType() . "5"  => __("SLA Incidents alerts", "mydashboard") . "&nbsp;<i class='fas fa-info-circle'></i>",
            $this->getType() . "6"  => __("GLPI Status", "mydashboard") . "&nbsp;<i class='fas fa-info-circle'></i>",
            $this->getType() . "7"  => __("User ticket alerts", "mydashboard") . "&nbsp;<i class='fas fa-table'></i>",
            $this->getType() . "8"  => __('Automatic actions in error', 'mydashboard') . "&nbsp;<i class='fas fa-table'></i>",
            $this->getType() . "9"  => __("Not imported mails in collectors", "mydashboard") . "&nbsp;<i class='fas fa-table'></i>",
            $this->getType() . "10" => __("Inventory stock alerts", "mydashboard") . "&nbsp;<i class='fas fa-info-circle'></i>",
            $this->getType() . "11" => __('Your equipments', 'mydashboard') . "&nbsp;<i class='fas fa-info-circle'></i>",
            $this->getType() . "12" => __("SLA Requests alerts", "mydashboard") . "&nbsp;<i class='fas fa-info-circle'></i>",
            $this->getType() . "13" => __("Requests alerts", "mydashboard") . "&nbsp;<i class='fas fa-info-circle'></i>",
         ]
      ];
   }

   /**
    * Alert counter
    *
    * @param       $public
    * @param       $type
    *
    * @param array $itilcategories_id
    *
    * @return int
    * @throws \GlpitestSQLError
    */
   static function countForAlerts($public, $type, $itilcategories_id = []) {
      global $DB;

      $now                 = date('Y-m-d H:i:s');
      $restrict_visibility = "AND (`glpi_reminders`.`begin_view_date` IS NULL
                                    OR `glpi_reminders`.`begin_view_date` < '$now')
                              AND (`glpi_reminders`.`end_view_date` IS NULL
                                   OR `glpi_reminders`.`end_view_date` > '$now') ";
      $addwhere            = "";
      if (count($itilcategories_id) > 0) {
         $cats     = implode("','", $itilcategories_id);
         $addwhere = " AND `glpi_plugin_mydashboard_alerts`.`itilcategories_id` IN ('" . $cats . "')";
      }
      $query = "SELECT COUNT(`glpi_reminders`.`id`) as cpt
                   FROM `glpi_reminders` "
               . PluginMydashboardReminder::addVisibilityJoins()
               . " LEFT JOIN `glpi_plugin_mydashboard_alerts`"
               . " ON `glpi_reminders`.`id` = `glpi_plugin_mydashboard_alerts`.`reminders_id`"
               . " WHERE `glpi_plugin_mydashboard_alerts`.`type` = $type
                         $addwhere
                         $restrict_visibility ";

      if ($public == 0) {
         $query .= "AND " . Reminder::addVisibilityRestrict() . "";
      } else {
         $query .= "AND `glpi_plugin_mydashboard_alerts`.`is_public`";
      }

      $result = $DB->query($query);
      $ligne  = $DB->fetchAssoc($result);
      $nb     = $ligne['cpt'];

      return $nb;
   }

   /**
    * @param       $widgetId
    *
    * @param array $opt
    *
    * @return PluginMydashboardHtml
    * @throws \GlpitestSQLError
    */
   function getWidgetContentForItem($widgetId, $opt = []) {
      global $CFG_GLPI, $DB;
      $dbu    = new DbUtils();
      $config = new PluginMydashboardConfig();
      $config->getFromDB(1);
      switch ($widgetId) {
         case $this->getType() . "1":
            $widget = new PluginMydashboardHtml();
            $widget->setWidgetHtmlContent($this->getAlertList(0));
            $widget->setWidgetTitle(PluginMydashboardConfig::displayField($config, 'title_alerts_widget'));
            return $widget;
            break;

         case $this->getType() . "2":
            $widget = new PluginMydashboardHtml();
            $datas  = $this->getMaintenanceList();
            $widget->setWidgetHtmlContent(
               $datas
            );
            $widget->setWidgetTitle(PluginMydashboardConfig::displayField($config, 'title_maintenances_widget'));
            return $widget;
            break;

         case $this->getType() . "3":
            $widget = new PluginMydashboardHtml();
            $datas  = $this->getInformationList();
            $widget->setWidgetHtmlContent(
               $datas
            );
            $widget->setWidgetTitle(PluginMydashboardConfig::displayField($config, 'title_informations_widget'));
            return $widget;
            break;

         case $this->getType() . "4":
            $widget = $this->displayTicketsAlertsWidgets('PluginMydashboardAlert4', $widgetId, $opt, Ticket::INCIDENT_TYPE);
            return $widget;
            break;

         case $this->getType() . "5":
            $widget = $this->displaySLATicketsAlertsWidgets('PluginMydashboardAlert5', $widgetId, $opt, Ticket::INCIDENT_TYPE);
            return $widget;
            break;

         case $this->getType() . "6":

            $widget = new PluginMydashboardHtml();
            $url    = $CFG_GLPI['url_base'] . "/status.php";
            //            $url = "http://localhost/glpi/status.php";
            $options  = ["url" => $url];
            $table    = "";
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

         case $this->getType() . "7":

            $link_ticket = Toolbox::getItemTypeFormURL("Ticket");

            $mygroups = Group_User::getUserGroups(Session::getLoginUserID(), ['glpi_groups.is_assign' => 1]);
            $groups   = [];
            foreach ($mygroups as $mygroup) {
               $groups[] = $mygroup["id"];
            }
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
            $headers = [__('ID and priority', 'mydashboard'), _n('Requester', 'Requesters', 2), __('Status'),
                        __('Last update'), __('Assigned to'), __('Action'),
                        __('ID'), __('Priority'), __('Category')];
            $widget->setTabNames($headers);

            $result = $DB->query($query);
            $nb     = $DB->numrows($result);

            $datas = [];

            if ($nb) {
               $i = 0;
               while ($data = $DB->fetchAssoc($result)) {

                  $ticket = new Ticket();
                  $ticket->getFromDB($data['tickets_id']);

                  $users_requesters = [];
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

                     $itilfollowup = new ItilFollowup();
                     $followups    = $itilfollowup->find(['items_id' => $ticket->fields['id'],
                                                          'itemtype' => 'Ticket'], 'date DESC');

                     $ticketdocument = new Document();
                     $documents      = $ticketdocument->find(['tickets_id' => $ticket->fields['id']], ['date_mod DESC']);

                     if ((count($followups) > 0 && current($followups)['date'] >= $ticket->fields['date_mod'])
                         || (count($documents) > 0 && current($documents)['date_mod'] >= $ticket->fields['date_mod'])) {

                        $bgcolor   = $_SESSION["glpipriority_" . $ticket->fields["priority"]];
                        $textColor = "color:black!important;";
                        if ($bgcolor == '#000000') {
                           $textColor = "color:white!important;";
                        }
                        $name_ticket = "<div class='center' style='background-color:$bgcolor; padding: 10px;'>";
                        $name_ticket .= "<a style='$textColor' href='" . $link_ticket . "?id=" . $data['tickets_id'] . "' target='_blank'>";
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


                        $ticketId        = "<a href='" . $link_ticket . "?id=" . $data['tickets_id'] . "' target='_blank'>";
                        $ticketId        .= $data['tickets_id'];
                        $ticketId        .= "</a>";
                        $datas[$i]["id"] = $ticketId;

                        // Priorities
                        $priority              = "<div class='center' style='background-color:$bgcolor; padding: 10px;$textColor'>";
                        $priority              .= "<span class='b'>" . $ticket->fields["priority"] . " - " . Ticket::getPriorityName($ticket->fields["priority"]) . "</span>";
                        $priority              .= "</div>";
                        $datas[$i]["priority"] = $priority;

                        // Categories
                        $config = new PluginMydashboardConfig();
                        $config->getFromDB(1);
                        $itilCategory = new ITILCategory();
                        if ($itilCategory->getFromDB($ticket->fields["itilcategories_id"])) {
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
                           $datas[$i]["category"] = "<span class='b'>" . substr($haystack, 0, $pos) . "</span>";
                        } else {
                           $datas[$i]["category"] = "<span></span>";
                        }


                        $i++;
                     }
                  }
               }
            }

            $widget->setTabDatas($datas);
            $widget->setOption("bSort", [3, 'desc']);
            $widget->setOption("bDate", ["DH"]);
            $widget->toggleWidgetRefresh();

            $widget->setWidgetTitle("<span style='color:orange'><i class='fas fa-exclamation-triangle fa-1x'></i>&nbsp;" . __("User ticket alerts", "mydashboard") . "</span>");
            $widget->setWidgetComment(__("Display tickets where last modification is a user action", "mydashboard"));

            return $widget;
            break;

         case $this->getType() . "8":

            $query = "SELECT *
                FROM `glpi_crontasks`
                WHERE `state` = '" . CronTask::STATE_RUNNING . "'
                      AND ((unix_timestamp(`lastrun`) + 2 * `frequency` < unix_timestamp(now()))
                           OR (unix_timestamp(`lastrun`) + 2*" . HOUR_TIMESTAMP . " < unix_timestamp(now())))";

            $widget  = PluginMydashboardHelper::getWidgetsFromDBQuery('table', $query);
            $headers = [__('Last run'), __('Name'), __('Status')];
            $widget->setTabNames($headers);

            $result = $DB->query($query);
            $nb     = $DB->numrows($result);

            $datas = [];
            $i     = 0;
            if ($nb) {
               while ($data = $DB->fetchAssoc($result)) {

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
            $widget->setOption("bDate", ["DH"]);
            $widget->setOption("bSort", [1, 'desc']);
            $widget->toggleWidgetRefresh();
            $widget->setWidgetTitle("<span style='color:indianred'><i class='fas fa-exclamation-triangle fa-1x'></i>&nbsp;" . __('Automatic actions in error', 'mydashboard') . "</span>");

            return $widget;
            break;

         case $this->getType() . "9":

            $query = "SELECT `date`,`from`,`reason`,`mailcollectors_id`
                        FROM `glpi_notimportedemails`
                        ORDER BY `date` ASC";

            $widget  = PluginMydashboardHelper::getWidgetsFromDBQuery('table', $query);
            $headers = [__('Date'), __('From email header'), __('Reason of rejection'), __('Mails receiver')];
            $widget->setTabNames($headers);

            $result = $DB->query($query);
            $nb     = $DB->numrows($result);

            $datas = [];
            $i     = 0;
            if ($nb) {
               while ($data = $DB->fetchAssoc($result)) {

                  $datas[$i]["date"] = Html::convDateTime($data['date']);

                  $datas[$i]["from"] = $data['from'];

                  $datas[$i]["reason"] = NotImportedEmail::getReason($data['reason']);

                  $mail = new MailCollector();
                  $mail->getFromDB($data['mailcollectors_id']);
                  $datas[$i]["mailcollectors_id"] = $mail->getName();

                  $i++;
               }

            }

            $widget->setTabDatas($datas);
            $widget->setOption("bDate", ["DH"]);
            $widget->setOption("bSort", [0, 'desc']);
            $widget->toggleWidgetRefresh();
            $widget->setWidgetTitle("<span style='color:indianred'><i class='fas fa-exclamation-triangle fa-1x'></i>&nbsp;" . __("Not imported mails in collectors", "mydashboard") . "</span>");
            $widget->setWidgetComment(__("Display of mails which are not imported", "mydashboard"));

            return $widget;
            break;

         case $this->getType() . "10":

            $widget = new PluginMydashboardHtml();

            $setuplink = PluginMydashboardStockWidget::getSearchURL(true);
            $criterias = ["locations_id"];
            $params    = ["preferences" => $this->preferences,
                          "criterias"   => $criterias,
                          "opt"         => $opt];
            $options   = PluginMydashboardHelper::manageCriterias($params);

            $opt               = $options['opt'];
            $crit              = $options['crit'];
            $location_criteria = $crit['locations_id'];

            $params = ["widgetId"  => $widgetId,
                       "name"      => 'PluginMydashboardAlert10',
                       "onsubmit"  => false,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "setup"     => $setuplink,
                       "export"    => false,
                       "canvas"    => false,
                       "nb"        => 1];
            $table  = PluginMydashboardHelper::getGraphHeader($params);

            $table       .= "<div class=\"tickets-stats\">";
            $stockwidget = new PluginMydashboardStockWidget();
            $stocks      = $stockwidget->find();
            $script      = "";
            if (count($stocks) > 0) {
               $nb = 0;
               foreach ($stocks as $data) {
                  $nb++;
                  $alarm    = $data['alarm_threshold'];
                  $stock    = 0;
                  $color    = "olivedrab";
                  $itemtype = $data['itemtype'];
                  if ($item = getItemForItemtype($itemtype)) {
                     $itemtable = getTableForItemType($itemtype);
                     $typefield = $dbu->getForeignKeyFieldForTable($dbu->getTableForItemType($itemtype . "Type"));

                     $types  = json_decode($data["types"], true);
                     $states = json_decode($data["states"], true);
                     $q2     = "SELECT DISTINCT COUNT(`" . $itemtable . "`.`id`) AS nb
                        FROM `" . $itemtable . "`
                        WHERE `" . $itemtable . "`.`is_deleted` = '0' AND `" . $itemtable . "`.`is_template` = '0' ";
                     $q2     .= $dbu->getEntitiesRestrictRequest("AND", $itemtype::getTable());
                     if (is_array($states) && count($states) > 0) {
                        $q2 .= " AND `" . $itemtable . "`.`states_id` IN('" . implode("', '", $states) . "') ";
                     }
                     if (is_array($types) && count($types) > 0) {
                        $q2 .= "AND `" . $itemtable . "`.`" . $typefield . "` IN('" . implode("', '", $types) . "')";
                     }
                     if (isset($opt['locations_id']) && ($opt['locations_id'] != 0)) {
                        $q2 .= " AND `" . $itemtable . "`.`locations_id` = '" . $location_criteria . "' ";
                     }
                     $r2  = $DB->query($q2);
                     $nb2 = $DB->numrows($r2);
                     if ($nb2) {
                        foreach ($DB->request($q2) as $data2) {
                           $stock = $data2['nb'];
                        }
                     }
                     if ($stock < $alarm) {
                        $color = "indianred";
                     }

                     //////////////////////////////////////////
                     $search                              = [];
                     $search['reset']                     = 'reset';
                     $search['criteria'][0]['field']      = "view";
                     $search['criteria'][0]['searchtype'] = 'contains';
                     $search['criteria'][0]['value']      = "^";
                     $search['criteria'][0]['link']       = 'AND';
                     if (is_array($types) && count($types) > 0) {
                        $nbs = 1;
                        foreach ($types as $type) {
                           $nbs++;
                           if ($itemtype == 'Certificate') {
                              $search['criteria'][1]['criteria'][$nbs]['field'] = "7";
                           } else {
                              $search['criteria'][1]['criteria'][$nbs]['field'] = "4";
                           }
                           $search['criteria'][1]['criteria'][$nbs]['searchtype'] = 'equals';
                           $search['criteria'][1]['criteria'][$nbs]['value']      = $type;
                           $search['criteria'][1]['criteria'][$nbs]['link']       = 'OR';
                        }
                     }
                     if (is_array($states) && count($states) > 0) {
                        $nbs = 1;
                        foreach ($states as $state) {
                           $nbs++;
                           $search['criteria'][2]['criteria'][$nbs]['field']      = 31; // type
                           $search['criteria'][2]['criteria'][$nbs]['searchtype'] = 'equals';
                           $search['criteria'][2]['criteria'][$nbs]['value']      = $state;
                           $search['criteria'][2]['criteria'][$nbs]['link']       = 'OR';
                        }
                     }
                     if (isset($opt['locations_id']) && ($opt['locations_id'] != 0)) {
                        $search['criteria'][3]['field']      = "3";
                        $search['criteria'][3]['searchtype'] = 'equals';
                        $search['criteria'][3]['value']      = $opt['locations_id'];
                        $search['criteria'][3]['link']       = 'AND';
                     }
                     $form = $itemtype::getSearchURL(false);
                     $link = $CFG_GLPI["root_doc"] . $form . '?is_deleted=0&' .
                             Toolbox::append_params($search, "&");

                     $icon  = $data['icon'];
                     $table .= "<div class=\"nbstock\" style=\"color:$color\">";
                     $table .= "<a style='color:$color' target='_blank' href=\"" . $link . "\" title='" . $data['name'] . "'>";
                     $table .= "<i style='color:$color' class=\"$icon fa-3x fa-border\"></i>";
                     $table .= "<h3>";
                     $table .= "<span class=\"counter count-number\" id=\"stock_$nb\"></span>";
                     //                     $table .= " / <span class=\"counter count-number\" id=\"all_$nb\"></span>";
                     $table .= "</h3>";
                     $table .= "<p class=\"count-text \">" . $data['name'] . "</p>";
                     $table .= "</a>";
                     $table .= "</div>";

                     $script .= "$('#stock_$nb').countup($stock);";

                  }
               }
               $table .= "<script type='text/javascript'>
                         $(function(){
                            $script;
                         });
                  </script>";
            } else {

               $table .= "<i style='color:orange' class='fas fa-exclamation-triangle fa-3x'></i>";
               $table .= "<br><br><span class='b'>" . __("No alerts are setup", "mydashboard") . "</span>";
            }
            $table .= "</div>";
            $table .= PluginMydashboardHelper::getGraphFooter($params);
            $widget->setWidgetHtmlContent(
               $table
            );
            $widget->toggleWidgetRefresh();

            $widget->setWidgetTitle("<span style='color:indianred'>&nbsp;" . __("Inventory stock alerts", "mydashboard") . "</span>");
            $widget->setWidgetComment(__("Display alerts for inventory stocks", "mydashboard"));

            return $widget;
            break;

         case $this->getType() . "11":
            $widget  = new PluginMydashboardHtml();
            $class   = "bt-col-md-12";
            $display = PluginMydashboardWidget::getWidgetMydashboardEquipments($class, false);
            $widget->setWidgetHtmlContent($display);
            $widget->setWidgetTitle(__('Your equipments', 'mydashboard'));
            return $widget;
            break;

         case $this->getType() . "12":
            $widget = $this->displaySLATicketsAlertsWidgets('PluginMydashboardAlert12', $widgetId, $opt, Ticket::DEMAND_TYPE);
            return $widget;
            break;

         case $this->getType() . "13":

            $widget = $this->displayTicketsAlertsWidgets('PluginMydashboardAlert13', $widgetId, $opt, Ticket::DEMAND_TYPE);
            return $widget;
            break;
      }
   }


   /**
    * @param $widgetId
    * @param $opt
    * @param $type
    *
    * @return \PluginMydashboardHtml
    * @throws \GlpitestSQLError
    */
   function displayTicketsAlertsWidgets($name, $widgetId, $opt, $type) {
      global $CFG_GLPI, $DB;

      $widget = new PluginMydashboardHtml();
      $dbu    = new DbUtils();

      $colorstats1 = "#CCC";
      $colorstats2 = "#CCC";
      $colorstats3 = "#CCC";
      $colorstats4 = "#CCC";
      /*Stats1*/
      $search_assign = "1=1";
      $left          = "";

      $technicians_groups_id        = PluginMydashboardHelper::getGroup($this->preferences['prefered_group'], $opt);
      $opt['technicians_groups_id'] = $technicians_groups_id;
      if (count($technicians_groups_id) > 0) {

         $left          = "LEFT JOIN `glpi_groups_tickets`
                  ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id`) ";
         $search_assign = " (`glpi_groups_tickets`.`groups_id` IN (" . implode(",", $technicians_groups_id) . ")
                                    AND `glpi_groups_tickets`.`type` = '" . CommonITILActor::ASSIGN . "')";
      }

      $criterias = ['technicians_groups_id'];
      $params    = ["widgetId"  => $widgetId,
                    "name"      => $name,
                    "onsubmit"  => true,
                    "opt"       => $opt,
                    "criterias" => $criterias,
                    "export"    => false,
                    "canvas"    => false,
                    "nb"        => 1];
      $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));

      $q1 = "SELECT DISTINCT COUNT(`glpi_tickets`.`id`) AS nb
                        FROM `glpi_tickets`
                        $left
                        WHERE `glpi_tickets`.`is_deleted` = '0' ";
      $q1 .= $dbu->getEntitiesRestrictRequest("AND", Ticket::getTable())
             . " AND `glpi_tickets`.`status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ") 
            AND `glpi_tickets`.`priority` > 4 AND `glpi_tickets`.`type` = '" . $type . "' AND $search_assign";

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
      if ($type == Ticket::INCIDENT_TYPE) {
         $search_assign = "1=1";
         $left          = "";

         $q2 = "SELECT DISTINCT COUNT(`glpi_problems`.`id`) AS nb
                        FROM `glpi_problems`
                        $left
                        WHERE `glpi_problems`.`is_deleted` = '0' ";
         $q2 .= $dbu->getEntitiesRestrictRequest("AND", Problem::getTable())
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
      }
      /*Stats3*/
      $left = "";

      $q3 = "SELECT DISTINCT COUNT(`glpi_tickets`.`id`) AS nb
                        FROM `glpi_tickets`
                        $left
                        WHERE `glpi_tickets`.`is_deleted` = '0' ";
      $q3 .= $dbu->getEntitiesRestrictRequest("AND", Ticket::getTable())
             . " AND `glpi_tickets`.`status` IN (" . CommonITILObject::INCOMING . ") 
            AND `glpi_tickets`.`type` = '" . $type . "' ";

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
      $left          = "";
      $search_assign = "1=1";

      if (count($technicians_groups_id) > 0) {
         $left          = "LEFT JOIN `glpi_groups_tickets`
                  ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id`) ";
         $search_assign = " (`glpi_groups_tickets`.`groups_id` IN (" . implode(",", $technicians_groups_id) . ")
                                    AND `glpi_groups_tickets`.`type` = '" . CommonITILActor::ASSIGN . "')";
      }

      $search_assign .= " AND `glpi_tickets`.`id` NOT IN (SELECT `tickets_id` FROM `glpi_tickets_users` WHERE `glpi_tickets_users`.`type` = '" . CommonITILActor::ASSIGN . "') ";

      $q4 = "SELECT DISTINCT COUNT(`glpi_tickets`.`id`) AS nb
                        FROM `glpi_tickets`
                        $left
                        WHERE $search_assign  AND `glpi_tickets`.`type` = '" . $type . "' AND `glpi_tickets`.`is_deleted` = 0 ";
      $q4 .= $dbu->getEntitiesRestrictRequest("AND", Ticket::getTable())
             . " AND `glpi_tickets`.`status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ") ";

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
      //new tickets
      if ($stats_tickets3 > 0) {

         // Reset criterias
         $options3['reset'][] = 'reset';

         $options3['criteria'][] = [
            'field'      => 12,//status
            'searchtype' => 'equals',
            'value'      => 1,
            'link'       => 'AND'
         ];

         $options3['criteria'][] = [
            'field'      => 14, // type
            'searchtype' => 'equals',
            'value'      => $type,
            'link'       => 'AND',
         ];

         $stats3link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
                       Toolbox::append_params($options3, "&");
      }

      $table .= "<div class=\"nb\" style=\"color:$colorstats3\">";
      if ($stats_tickets3 > 0) {
         if ($type == Ticket::INCIDENT_TYPE) {
            $table .= "<a style='color:$colorstats3' target='_blank' href=\"" . $stats3link . "\" title='" . __('New incidents', 'mydashboard') . "'>";
         } else {
            $table .= "<a style='color:$colorstats3' target='_blank' href=\"" . $stats3link . "\" title='" . __('New requests', 'mydashboard') . "'>";
         }
      }
      $table .= "<i style='color:$colorstats3' class=\"fas fa-exclamation-circle fa-3x fa-border\"></i>
               <h3><span class=\"counter count-number\" id='stats_" . $type . "_tickets3'></span></h3>";

      if ($type == Ticket::INCIDENT_TYPE) {
         $table .= "<p class=\"count-text \">" . __('New incidents', 'mydashboard') . "</p>";
      } else {
         $table .= "<p class=\"count-text \">" . __('New requests', 'mydashboard') . "</p>";
      }

      if ($stats_tickets3 > 0) {
         $table .= "</a>";
      }
      $table .= "</div>";

      //////////////////////////////////////////
      //tickets without tech
      if ($stats_tickets4 > 0) {

         // Reset criterias
         $options4['reset'][] = 'reset';

         $options4['criteria'][] = [
            'field'      => 12,//status
            'searchtype' => 'equals',
            'value'      => 'notold',
            'link'       => 'AND'
         ];

         $options4['criteria'][] = [
            'field'      => 5, // tech
            'searchtype' => 'contains',
            'value'      => '^$',
            'link'       => 'AND',
         ];

         $options4['criteria'][] = [
            'field'      => 14, // type
            'searchtype' => 'equals',
            'value'      => $type,
            'link'       => 'AND',
         ];

         if (isset($opt['technicians_groups_id'])
             && count($opt['technicians_groups_id']) > 0) {
            $groups = $opt['technicians_groups_id'];
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
            $options4['criteria'][] = $criterias;
         }

         $stats4link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
                       Toolbox::append_params($options4, "&");
      }

      $table .= "<div class=\"nb\" style=\"color:$colorstats4\">";
      if ($stats_tickets4 > 0) {
         if ($type == Ticket::INCIDENT_TYPE) {
            $table .= "<a style='color:$colorstats4' target='_blank' href=\"" . $stats4link . "\" title='" . __('Opened incidents without assigned technicians', 'mydashboard') . "'>";
         } else {
            $table .= "<a style='color:$colorstats4' target='_blank' href=\"" . $stats4link . "\" title='" . __('Opened requests without assigned technicians', 'mydashboard') . "'>";
         }
      }

      $table .= "<i style='color:$colorstats4;font-size:34px' class=\"fas fa-user-times fa-3x fa-border\"></i>
               <h3><span class=\"counter count-number\" id='stats_" . $type . "_tickets4'></span></h3>";

      if ($type == Ticket::INCIDENT_TYPE) {
         $table .= "<p class=\"count-text \">" . __('Opened incidents without assigned technicians', 'mydashboard') . "</p>";
      } else {
         $table .= "<p class=\"count-text \">" . __('Opened requests without assigned technicians', 'mydashboard') . "</p>";
      }

      if ($stats_tickets4 > 0) {
         $table .= "</a>";
      }
      $table .= "</div>";

      //////////////////////////////////////////
      //Tickets with very high or major priority
      if ($stats_tickets1 > 0) {

         // Reset criterias
         $options1['reset'][] = 'reset';

         $options1['criteria'][] = [
            'field'      => 12,//status
            'searchtype' => 'equals',
            'value'      => 'notold',
            'link'       => 'AND'
         ];

         $options1['criteria'][] = [
            'field'      => 3, // priority
            'searchtype' => 'equals',
            'value'      => -5,
            'link'       => 'AND',
         ];

         $options1['criteria'][] = [
            'field'      => 14, // type
            'searchtype' => 'equals',
            'value'      => $type,
            'link'       => 'AND',
         ];

         if (isset($opt['technicians_groups_id'])
             && count($opt['technicians_groups_id']) > 0) {
            $groups = $opt['technicians_groups_id'];
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
            $options1['criteria'][] = $criterias;
         }

         $stats1link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
                       Toolbox::append_params($options1, "&");
      }

      $table .= "<div class=\"nb\" style=\"color:$colorstats1\">";
      if ($stats_tickets1 > 0) {

         if ($type == Ticket::INCIDENT_TYPE) {
            $table .= "<a style='color:$colorstats1' target='_blank' href=\"" . $stats1link . "\" title='" . __('Incidents with very high or major priority', 'mydashboard') . "'>";
         } else {
            $table .= "<a style='color:$colorstats1' target='_blank' href=\"" . $stats1link . "\" title='" . __('Requests with very high or major priority', 'mydashboard') . "'>";
         }
      }
      $table .= "<i style='color:$colorstats1' class=\"fas fa-exclamation-triangle fa-3x fa-border\"></i>
               <h3><span class=\"counter count-number\" id='stats_" . $type . "_tickets1'></span></h3>";
      if ($type == Ticket::INCIDENT_TYPE) {
         $table .= "<p class=\"count-text \">" . __('Incidents with very high or major priority', 'mydashboard') . "</p>";
      } else {
         $table .= "<p class=\"count-text \">" . __('Requests with very high or major priority', 'mydashboard') . "</p>";
      }

      if ($stats_tickets1 > 0) {
         $table .= "</a>";
      }
      $table .= "</div>";

      //////////////////////////////////////////
      //Problem with high priority
      if ($type == Ticket::INCIDENT_TYPE) {
         if ($stats_tickets2 > 0) {

            // Reset criterias
            $options2['reset'][] = 'reset';

            $options2['criteria'][] = [
               'field'      => 12,//status
               'searchtype' => 'equals',
               'value'      => 'notold',
               'link'       => 'AND'
            ];

            $options2['criteria'][] = [
               'field'      => 3, // priority
               'searchtype' => 'equals',
               'value'      => -5,
               'link'       => 'AND',
            ];

            $stats2link = $CFG_GLPI["root_doc"] . '/front/problem.php?is_deleted=0&' .
                          Toolbox::append_params($options2, "&");
         }

         $table .= "<div class=\"nb\" style=\"color:$colorstats2\">";
         if ($stats_tickets2 > 0) {
            $table .= "<a style='color:$colorstats2' target='_blank' href=\"" . $stats2link . "\" title='" . __('Problems with very high or major priority', 'mydashboard') . "'>";
         }
         $table .= "<i style='color:$colorstats2' class=\"fas fa-bug fa-3x fa-border\"></i>
                           <h3><span class=\"counter count-number\" id='stats_" . $type . "_tickets2'></span></h3>";
         $table .= "<p class=\"count-text \">" . __('Problems with very high or major priority', 'mydashboard') . "</p>";
         if ($stats_tickets2 > 0) {
            $table .= "</a>";
         }
         $table .= "</div>";
      }
      //////////////////////////////////////////

      if ($type == Ticket::INCIDENT_TYPE) {
         $table .= "<script type='text/javascript'>
                         $(function(){
                            $('#stats_" . $type . "_tickets1').countup($stats_tickets1);
                            $('#stats_" . $type . "_tickets2').countup($stats_tickets2);
                            $('#stats_" . $type . "_tickets3').countup($stats_tickets3);
                            $('#stats_" . $type . "_tickets4').countup($stats_tickets4);
                         });
                  </script>";
      } else {
         $table .= "<script type='text/javascript'>
                         $(function(){
                            $('#stats_" . $type . "_tickets1').countup($stats_tickets1);
                            $('#stats_" . $type . "_tickets3').countup($stats_tickets3);
                            $('#stats_" . $type . "_tickets4').countup($stats_tickets4);
                         });
                  </script>";
      }
      $table .= "</div>";

      $widget->setWidgetHtmlContent(
         $table
      );
      $widget->toggleWidgetRefresh();

      if ($type == Ticket::INCIDENT_TYPE) {
         $widget->setWidgetTitle("<span style='color:indianred'>&nbsp;" . __("Incidents alerts", "mydashboard") . "</span>");
         $widget->setWidgetComment(__("Display alerts for incidents and problems", "mydashboard"));
      } else {
         $widget->setWidgetTitle("<span style='color:indianred'>&nbsp;" . __("Requests alerts", "mydashboard") . "</span>");
         $widget->setWidgetComment(__("Display alerts for requests", "mydashboard"));
      }
      return $widget;
   }


   function displaySLATicketsAlertsWidgets($name, $widgetId, $opt, $type) {
      global $CFG_GLPI, $DB;

      $widget = new PluginMydashboardHtml();
      $dbu    = new DbUtils();

      $colorstats2 = "#CCC";
      $colorstats3 = "#CCC";
      $colorstats4 = "#CCC";
      $colorstats5 = "#CCC";

      /*Stats2*/
      $search_assign = "1=1";
      $left          = "";
      $stats2        = 0;

      $technicians_groups_id        = PluginMydashboardHelper::getGroup($this->preferences['prefered_group'], $opt);
      $opt['technicians_groups_id'] = $technicians_groups_id;
      if (count($technicians_groups_id) > 0) {

         $left          = "LEFT JOIN `glpi_groups_tickets`
                  ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id`) ";
         $search_assign = " (`glpi_groups_tickets`.`groups_id`IN (" . implode(",", $technicians_groups_id) . ")
                                    AND `glpi_groups_tickets`.`type` = '" . CommonITILActor::ASSIGN . "')";
      }

      $criterias = ['technicians_groups_id'];
      $params    = ["widgetId"  => $widgetId,
                    "name"      => $name,
                    "onsubmit"  => true,
                    "opt"       => $opt,
                    "criterias" => $criterias,
                    "export"    => false,
                    "canvas"    => false,
                    "nb"        => 1];
      $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params));

      $q2 = "SELECT DISTINCT COUNT(`glpi_tickets`.`id`) AS nb
                           FROM `glpi_tickets`
                           $left
                           WHERE `glpi_tickets`.`is_deleted` = '0' ";
      $q2 .= $dbu->getEntitiesRestrictRequest("AND", Ticket::getTable())
             . " AND $search_assign AND `glpi_tickets`.`status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ") 
                         AND `glpi_tickets`.`type` = '" . $type . "'
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
      if (count($technicians_groups_id) > 0) {

         $left          = "LEFT JOIN `glpi_groups_tickets`
                  ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id`) ";
         $search_assign = " (`glpi_groups_tickets`.`groups_id` IN (" . implode(",", $opt['technicians_groups_id']) . ")
                                    AND `glpi_groups_tickets`.`type` = '" . CommonITILActor::ASSIGN . "')";
      }
      $q3 = "SELECT DISTINCT COUNT(`glpi_tickets`.`id`) AS nb
                           FROM `glpi_tickets`
                           $left
                           WHERE `glpi_tickets`.`is_deleted` = '0' ";
      $q3 .= $dbu->getEntitiesRestrictRequest("AND", Ticket::getTable())
             . " AND $search_assign AND `glpi_tickets`.`status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ") 
                         AND `glpi_tickets`.`type` = '" . $type . "'
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

      if (count($technicians_groups_id) > 0) {

         $left          = "LEFT JOIN `glpi_groups_tickets`
                  ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id`) ";
         $search_assign = " (`glpi_groups_tickets`.`groups_id`IN (" . implode(",", $technicians_groups_id) . ")
                                    AND `glpi_groups_tickets`.`type` = '" . CommonITILActor::ASSIGN . "')";
      }

      $q4 = "SELECT DISTINCT COUNT(`glpi_tickets`.`id`) AS nb
                                       FROM `glpi_tickets`
                                       $left
                                       WHERE `glpi_tickets`.`is_deleted` = '0' ";
      $q4 .= $dbu->getEntitiesRestrictRequest("AND", Ticket::getTable())
             . " AND $search_assign AND `glpi_tickets`.`status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ") 
                         AND `glpi_tickets`.`type` = '" . $type . "'
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

      if (count($technicians_groups_id) > 0) {

         $left          = "LEFT JOIN `glpi_groups_tickets`
                  ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id`) ";
         $search_assign = " (`glpi_groups_tickets`.`groups_id`IN (" . implode(",", $technicians_groups_id) . ")
                                    AND `glpi_groups_tickets`.`type` = '" . CommonITILActor::ASSIGN . "')";
      }

      $q5 = "SELECT DISTINCT COUNT(`glpi_tickets`.`id`) AS nb
                                       FROM `glpi_tickets`
                                       $left
                                       WHERE `glpi_tickets`.`is_deleted` = '0' ";
      $q5 .= $dbu->getEntitiesRestrictRequest("AND", Ticket::getTable())
             . " AND $search_assign AND `glpi_tickets`.`status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ") 
                         AND `glpi_tickets`.`type` = '" . $type . "'
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

         // Reset criterias
         $options2['reset'][] = 'reset';

         $options2['criteria'][] = [
            'field'      => 12,//status
            'searchtype' => 'equals',
            'value'      => 'notold',
            'link'       => 'AND'
         ];

         $options2['criteria'][] = [
            'field'      => 14, // type
            'searchtype' => 'equals',
            'value'      => $type,
            'link'       => 'AND',
         ];

         $options2['criteria'][] = [
            'field'      => 155, // time_to_own
            'searchtype' => 'morethan',
            'value'      => 'NOW',
            'link'       => 'AND',
         ];

         if (isset($opt['technicians_groups_id'])
             && count($opt['technicians_groups_id']) > 0) {
            $groups = $opt['technicians_groups_id'];
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
            $options2['criteria'][] = $criterias;
         }

         $options2['criteria'][] = [
            'field'      => 150, // takeintoaccount_delay_stat
            'searchtype' => 'contains',
            'value'      => 0,
            'link'       => 'AND',
         ];

         $stats2link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
                       Toolbox::append_params($options2, "&");
      }

      $table .= "<div class=\"nb\" style=\"color:$colorstats2\">";
      if ($stats2 > 0) {
         $table .= "<a style='color:$colorstats2' target='_blank' href=\"" . $stats2link . "\">";
      }
      if ($type == Ticket::INCIDENT_TYPE) {
         $table .= "<i style='color:$colorstats2' class=\"fas fa-exclamation-circle fa-3x fa-border\"></i>
               <h3><span class=\"counter count-number\" id='stats_" . $type . "_sla2'></span></h3>
               <p class=\"count-text \">" . __('Incidents where time to own will be exceeded', 'mydashboard') . "</p>";
      } else {
         $table .= "<i style='color:$colorstats2' class=\"fas fa-exclamation-circle fa-3x fa-border\"></i>
               <h3><span class=\"counter count-number\" id='stats_" . $type . "_sla2'></span></h3>
               <p class=\"count-text \">" . __('Requests where time to own will be exceeded', 'mydashboard') . "</p>";
      }

      if ($stats2 > 0) {
         $table .= "</a>";
      }
      $table .= "</div>";
      if ($stats3 > 0) {

         // Reset criterias
         $options2['reset'][] = 'reset';

         $options3['criteria'][] = [
            'field'      => 12,//status
            'searchtype' => 'equals',
            'value'      => 'notold',
            'link'       => 'AND'
         ];

         $options3['criteria'][] = [
            'field'      => 14, // type
            'searchtype' => 'equals',
            'value'      => $type,
            'link'       => 'AND',
         ];

         $options3['criteria'][] = [
            'field'      => 18, // time_to_resolve
            'searchtype' => 'morethan',
            'value'      => 'NOW',
            'link'       => 'AND',
         ];

         if (isset($opt['technicians_groups_id'])
             && count($opt['technicians_groups_id']) > 0) {
            $groups = $opt['technicians_groups_id'];
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
            $options3['criteria'][] = $criterias;
         }

         $options3['criteria'][] = [
            'field'      => 154, // solve_delay_stat
            'searchtype' => 'contains',
            'value'      => 0,
            'link'       => 'AND',
         ];

         $stats3link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
                       Toolbox::append_params($options3, "&");
      }

      $table .= "<div class=\"nb\" style=\"color:$colorstats3\">";
      if ($stats3 > 0) {
         $table .= "<a style='color:$colorstats3' target='_blank' href=\"" . $stats3link . "\">";
      }
      if ($type == Ticket::INCIDENT_TYPE) {
         $table .= "<i style='color:$colorstats3' class=\"fas fa-times-circle fa-3x fa-border\"></i>
               <h3><span class=\"counter count-number\" id='stats_" . $type . "_sla3'></span></h3>
               <p class=\"count-text \">" . __('Incidents where time to resolve will be exceeded', 'mydashboard') . "</p>";
      } else {
         $table .= "<i style='color:$colorstats3' class=\"fas fa-times-circle fa-3x fa-border\"></i>
               <h3><span class=\"counter count-number\" id='stats_" . $type . "_sla3'></span></h3>
               <p class=\"count-text \">" . __('Requests where time to resolve will be exceeded', 'mydashboard') . "</p>";
      }
      if ($stats3 > 0) {
         $table .= "</a>";
      }
      $table .= "</div>";

      if ($stats4 > 0) {

         // Reset criterias
         $options4['reset'][] = 'reset';

         $options4['criteria'][] = [
            'field'      => 12,//status
            'searchtype' => 'equals',
            'value'      => 'notold',
            'link'       => 'AND'
         ];

         $options4['criteria'][] = [
            'field'      => 14, // type
            'searchtype' => 'equals',
            'value'      => $type,
            'link'       => 'AND',
         ];

         $options4['criteria'][] = [
            'field'      => 155, // time_to_own
            'searchtype' => 'lessthan',
            'value'      => 'NOW',
            'link'       => 'AND',
         ];

         if (isset($opt['technicians_groups_id'])
             && count($opt['technicians_groups_id']) > 0) {
            $groups = $opt['technicians_groups_id'];
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
            $options4['criteria'][] = $criterias;
         }

         $options4['criteria'][] = [
            'field'      => 150, // takeintoaccount_delay_stat
            'searchtype' => 'contains',
            'value'      => 0,
            'link'       => 'AND',
         ];

         $stats4link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
                       Toolbox::append_params($options4, "&");
      }

      $table .= "<div class=\"nb\" style=\"color:$colorstats4\">";
      if ($stats4 > 0) {
         $table .= "<a style='color:$colorstats4' target='_blank' href=\"" . $stats4link . "\">";
      }
      if ($type == Ticket::INCIDENT_TYPE) {
         $table .= "<i style='color:$colorstats4' class=\"fas fa-exclamation-circle fa-3x fa-border\"></i>
                           <h3><span class=\"counter count-number\" id='stats_" . $type . "_sla4'></span></h3>
                           <p class=\"count-text \">" . __('Incidents where time to own is exceeded', 'mydashboard') . "</p>";
      } else {
         $table .= "<i style='color:$colorstats4' class=\"fas fa-exclamation-circle fa-3x fa-border\"></i>
                           <h3><span class=\"counter count-number\" id='stats_" . $type . "_sla4'></span></h3>
                           <p class=\"count-text \">" . __('Requests where time to own is exceeded', 'mydashboard') . "</p>";
      }

      if ($stats4 > 0) {
         $table .= "</a>";
      }
      $table .= "</div>";

      if ($stats5 > 0) {

         // Reset criterias
         $options5['reset'][] = 'reset';

         $options5['criteria'][] = [
            'field'      => 12,//status
            'searchtype' => 'equals',
            'value'      => 'notold',
            'link'       => 'AND'
         ];

         $options5['criteria'][] = [
            'field'      => 14, // type
            'searchtype' => 'equals',
            'value'      => $type,
            'link'       => 'AND',
         ];

         $options5['criteria'][] = [
            'field'      => 18, // time_to_resolve
            'searchtype' => 'lessthan',
            'value'      => 'NOW',
            'link'       => 'AND',
         ];

         if (isset($opt['technicians_groups_id'])
             && count($opt['technicians_groups_id']) > 0) {
            $groups = $opt['technicians_groups_id'];
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
            $options5['criteria'][] = $criterias;
         }

         $options5['criteria'][] = [
            'field'      => 154, // solve_delay_stat
            'searchtype' => 'contains',
            'value'      => 0,
            'link'       => 'AND',
         ];

         $stats5link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&' .
                       Toolbox::append_params($options5, "&");
      }

      $table .= "<div class=\"nb\" style=\"color:$colorstats5\">";
      if ($stats5 > 0) {
         $table .= "<a style='color:$colorstats5' target='_blank' href=\"" . $stats5link . "\">";
      }
      if ($type == Ticket::INCIDENT_TYPE) {
         $table .= "<i style='color:$colorstats5' class=\"fas fa-times-circle fa-3x fa-border\"></i>
                           <h3><span class=\"counter count-number\" id='stats_" . $type . "_sla5'></span></h3>
                           <p class=\"count-text \">" . __('Incidents where time to resolve is exceeded', 'mydashboard') . "</p>";
      } else {
         $table .= "<i style='color:$colorstats5' class=\"fas fa-times-circle fa-3x fa-border\"></i>
                           <h3><span class=\"counter count-number\" id='stats_" . $type . "_sla5'></span></h3>
                           <p class=\"count-text \">" . __('Requests where time to resolve is exceeded', 'mydashboard') . "</p>";
      }

      if ($stats5 > 0) {
         $table .= "</a>";
      }
      $table .= "</div>";

      $table .= "<script type='text/javascript'>
                         $(function(){
                            $('#stats_" . $type . "_sla2').countup($stats2);
                            $('#stats_" . $type . "_sla3').countup($stats3);
                            $('#stats_" . $type . "_sla4').countup($stats4);
                            $('#stats_" . $type . "_sla5').countup($stats5);
                         });
                  </script>";

      $table .= "</div>";

      $widget->setWidgetHtmlContent(
         $table
      );
      $widget->toggleWidgetRefresh();

      if ($type == Ticket::INCIDENT_TYPE) {
         $widget->setWidgetTitle("<span style='color:indianred'>&nbsp;" . __("SLA Incidents alerts", "mydashboard") . "</span>");
         $widget->setWidgetComment(__("Display alerts for SLA of Incidents tickets", "mydashboard"));
      } else {
         $widget->setWidgetTitle("<span style='color:indianred'>&nbsp;" . __("SLA Requests alerts", "mydashboard") . "</span>");
         $widget->setWidgetComment(__("Display alerts for SLA of Requests tickets", "mydashboard"));
      }
      return $widget;
   }

   /**
    * @param bool $public
    *
    * @return string
    */
   static function getMaintenanceMessage($public = false) {
      if (self::countForAlerts($public, 1) > 0) {
         echo __('There is at least on planned scheduled maintenance. Please log on to see more', 'mydashboard');
      }
   }

   /**
    * @return string
    */
   function getMaintenanceList($itilcategories_id = []) {
      global $DB;

      $now = date('Y-m-d H:i:s');
      $wl  = "";

      $wl            .= "<div class='weather_block visitedchildbg widgetrow'>";
      $restrict_user = '1';
      // Only personal on central so do not keep it
      //      if (Session::getCurrentInterface() == 'central') {
      //         $restrict_user = "`glpi_reminders`.`users_id` <> '".Session::getLoginUserID()."'";
      //      }
      $addwhere = "";
      if (count($itilcategories_id) > 0) {
         $cats     = implode("','", $itilcategories_id);
         $addwhere = " AND `glpi_plugin_mydashboard_alerts`.`itilcategories_id` IN ('" . $cats . "')";
      }

      $restrict_visibility = "AND (`glpi_reminders`.`begin_view_date` IS NULL
                                    OR `glpi_reminders`.`begin_view_date` < '$now')
                              AND (`glpi_reminders`.`end_view_date` IS NULL
                                   OR `glpi_reminders`.`end_view_date` > '$now') ";

      $query = "SELECT `glpi_reminders`.`id`,
                       `glpi_reminders`.`name`
                   FROM `glpi_reminders` "
               . PluginMydashboardReminder::addVisibilityJoins()
               . "LEFT JOIN `" . $this->getTable() . "`"
               . "ON `glpi_reminders`.`id` = `" . $this->getTable() . "`.`reminders_id`"
               . "WHERE $restrict_user
                        $addwhere
                         $restrict_visibility ";

      $query .= "AND " . Reminder::addVisibilityRestrict() . "";

      $query .= "AND `" . $this->getTable() . "`.`type` = 1
                   ORDER BY `glpi_reminders`.`name`";

      $result = $DB->query($query);
      $nb     = $DB->numrows($result);
      if ($nb) {

         $wl .= "<div id='maint-div'>";
         $wl .= "<ul>";
         while ($row = $DB->fetchArray($result)) {
            $note = new Reminder();
            $note->getFromDB($row["id"]);
            $wl .= "<li>";
            $wl .= "<div class='bt-row'>";
            $wl .= "<div class=\"bt-col-xs-3 center alert-title-div \">";
            $wl .= "<i class='fas fa-exclamation-triangle fa-alert-5 fa-alert-orange' aria-hidden='true'></i>";
            $wl .= "</div>";
            $wl .= "<div class=\"bt-col-xs-8 alert-title-div \" style=\"margin-top: 30px;\">";
            $wl .= "<h3>";
            $wl .= ReminderTranslation::getTranslatedValue($note, 'name');
            $wl .= "</h3>";
            $wl .= "</div>";
            $wl .= "<div class=\"bt-col-xs-12 alert-content-div \">";
            $wl .= Toolbox::getHtmlToDisplay(ReminderTranslation::getTranslatedValue($note, 'text'));
            $wl .= "</div>";
            $wl .= "</div>";
            $wl .= "</li>";

         }
         $wl .= "</ul>";
         $wl .= "</div>";
         if ($nb) {
            $wl .= "<script type='text/javascript'>
                  $(function() {
                     $('#maint-div').vTicker({
                        speed: 500,
                        pause: 6000,
                        showItems: 1,
                        padding: 10,
                        animate: 'fade',
                        mousePause: true,
                        height: 0,
                        direction: 'up'
                     });
                  });
               </script>";
         }
      } else {

         $wl .= "<div align='center'><br><br><h3><span class ='maint-color'>";
         $wl .= __("No scheduled maintenance", "mydashboard");
         $wl .= "</span></h3></div>";
      }
      $wl .= "</div>";
      return $wl;
   }


   /**
    * @return string
    */
   function getInformationList($itilcategories_id = []) {
      global $DB;

      $now = date('Y-m-d H:i:s');
      $wl  = "";

      $wl            .= "<div class='weather_block visitedchildbg widgetrow'>";
      $restrict_user = '1';
      // Only personal on central so do not keep it
      //      if (Session::getCurrentInterface() == 'central') {
      //         $restrict_user = "`glpi_reminders`.`users_id` <> '".Session::getLoginUserID()."'";
      //      }
      $addwhere = "";
      if (count($itilcategories_id) > 0) {
         $cats     = implode("','", $itilcategories_id);
         $addwhere = " AND `glpi_plugin_mydashboard_alerts`.`itilcategories_id` IN ('" . $cats . "')";
      }

      $restrict_visibility = "AND (`glpi_reminders`.`begin_view_date` IS NULL
                                    OR `glpi_reminders`.`begin_view_date` < '$now')
                              AND (`glpi_reminders`.`end_view_date` IS NULL
                                   OR `glpi_reminders`.`end_view_date` > '$now') ";

      $query = "SELECT `glpi_reminders`.`id`,
                       `glpi_reminders`.`name`
                   FROM `glpi_reminders` "
               . PluginMydashboardReminder::addVisibilityJoins()
               . "LEFT JOIN `" . $this->getTable() . "`"
               . "ON `glpi_reminders`.`id` = `" . $this->getTable() . "`.`reminders_id`"
               . "WHERE $restrict_user
                        $addwhere
                         $restrict_visibility ";

      $query .= "AND " . Reminder::addVisibilityRestrict() . "";

      $query .= "AND `" . $this->getTable() . "`.`type` = 2
                   ORDER BY `glpi_reminders`.`name`";

      $result = $DB->query($query);
      $nb     = $DB->numrows($result);

      if ($nb) {

         $wl .= "<div id='info-div'>";
         $wl .= "<ul>";
         while ($row = $DB->fetchArray($result)) {
            $note = new Reminder();
            $note->getFromDB($row["id"]);
            $wl .= "<li>";
            $wl .= "<div class='bt-row'>";
            $wl .= "<div class=\"bt-col-xs-12 center \">";
            $wl .= "<h3>";
            $wl .= ReminderTranslation::getTranslatedValue($note, 'name');
            $wl .= "</h3>";
            $wl .= "</div>";
            $wl .= "<div class=\"bt-col-xs-12 center \">";
            $wl .= Toolbox::getHtmlToDisplay(ReminderTranslation::getTranslatedValue($note, 'text'));
            $wl .= "</div>";
            $wl .= "</div>";
            $wl .= "</li>";
         }
         $wl .= "</ul>";
         $wl .= "</div>";
         if ($nb) {
            $wl .= "<script type='text/javascript'>
                  $(function() {
                     $('#info-div').vTicker({
                        speed: 500,
                        pause: 6000,
                        showItems: 1,
                        padding: 10,
                        animate: false,
                        mousePause: true,
                        height: 0,
                        direction: 'right'
                     });
                  });
               </script>";
         }
      } else {

         $wl .= "<div align='center'><br><br><h3><span class ='maint-color'>";
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
   function getAlertList($public = 0, $itilcategories_id = []) {
      global $DB;

      $config = new PluginMydashboardConfig();
      $config->getFromDB(1);
      $now = date('Y-m-d H:i:s');

      $wl            = "";
      $wl            .= "<div class='weather_block visitedchildbg widgetrow'>";
      $restrict_user = '1';

      $addwhere = "";
      if (count($itilcategories_id) > 0) {
         $cats     = implode("','", $itilcategories_id);
         $addwhere = " AND `glpi_plugin_mydashboard_alerts`.`itilcategories_id` IN ('" . $cats . "')";
      }

      $restrict_visibility = "AND (`glpi_reminders`.`begin_view_date` IS NULL
                                    OR `glpi_reminders`.`begin_view_date` < '$now')
                              AND (`glpi_reminders`.`end_view_date` IS NULL
                                   OR `glpi_reminders`.`end_view_date` > '$now') ";

      $query = "SELECT `glpi_reminders`.`id`,
                       `glpi_reminders`.`name`,
                       `" . $this->getTable() . "`.`impact`
                   FROM `glpi_reminders` "
               . PluginMydashboardReminder::addVisibilityJoins()
               . "LEFT JOIN `" . $this->getTable() . "`"
               . "ON `glpi_reminders`.`id` = `" . $this->getTable() . "`.`reminders_id`"
               . "WHERE $restrict_user
                        $addwhere
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
         $wl .= "<ul class='alert-div'>";

         while ($row = $DB->fetchArray($result)) {

            $note = new Reminder();
            $note->getFromDB($row["id"]);

            $wl .= "<li>";

            $wl    .= "<div class='bt-row'>";
            $wl    .= "<div class=\"bt-col-xs-3 center alert-title-div\">";
            $class = "plugin_mydashboard_fa-thermometer-" . ($row['impact'] - 1);
            $style = "color:" . $config->getField('impact_' . $row['impact']);
            $wl    .= "<i style='$style' class='fa $class fa-alert-5'></i>";

            $wl .= "</div>";

            $wl .= "<div class=\"bt-col-xs-8 alert-title-div\" style=\"margin-top: 30px;\">";
            $wl .= "<h3>";

            $rand = mt_rand();
            $name = $row['name'];
            $wl   .= "<div id='alert$rand'>";
            $wl   .= "<span style='$style' class='left'>";
            $wl   .= ReminderTranslation::getTranslatedValue($note, 'name');
            $wl   .= "</span>";
            $wl   .= "</div>";
            $wl   .= "</h3>";

            $wl .= "</div>";
            $wl .= "</div>";

            $wl .= "<div class='bt-row'>";
            $wl .= "<div class=\"bt-col-xs-12 alert-content-div\">";
            $wl .= Toolbox::getHtmlToDisplay(ReminderTranslation::getTranslatedValue($note, 'text'));
            $wl .= "</div>";
            $wl .= "</div>";

            $wl .= "</li>";
         }
         $wl .= "</ul>";
         $wl .= "</div>";
         if ($nb) {
            $wl .= "<script type='text/javascript'>
                  $(function() {
                     $('#alert-div').vTicker({
                        speed: 500,
                        pause: 6000,
                        showItems: 1,
                        padding: 10,
                        animation: 'fade',
                        mousePause: true,
                        height: 0,
                        direction: 'up'
                     });
                  });
               </script>";
         }
      } else {

         $wl .= "<div align='center'><br><br><h3><span class ='alert-color'>";
         $wl .= __("No problem detected", "mydashboard");
         $wl .= "</span></h3></div>";
      }
      $wl .= "</div>";

      return $wl;
   }

   /**
    * @param int $public
    *
    * @param int $force
    *
    * @return string
    */
   function getAlertSummary($public = 0, $force = 0) {
      global $DB;

      echo Html::css("/public/lib/base.css");
      echo Html::css("/plugins/mydashboard/css/mydashboard.css");
      echo Html::css("/plugins/mydashboard/css/style_bootstrap_main.css");
      $now = date('Y-m-d H:i:s');

      $restrict_user = '1';
      // Only personal on central so do not keep it
      //      if (Session::getCurrentInterface() == 'central') {
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
               . PluginMydashboardReminder::addVisibilityJoins()
               . " LEFT JOIN `" . $this->getTable() . "`"
               . " ON `glpi_reminders`.`id` = `" . $this->getTable() . "`.`reminders_id`"
               . " WHERE $restrict_user
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
         while ($row = $DB->fetchArray($result)) {

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
            $wl .= $this->displayContent('5', $list, $public);
         } else if (!empty($f4)) {
            $wl .= $this->displayContent('4', $list, $public);
         } else if (!empty($f3)) {
            $wl .= $this->displayContent('3', $list, $public);
         } else if (!empty($f2)) {
            $wl .= $this->displayContent('2', $list, $public);
         } else if (!empty($f1)) {
            $wl .= $this->displayContent('1', $list, $public);
         }
      }
      if (!$nb && ($public == 0 || $force == 1)) {
         $wl .= $this->displayContent('1', [], 0);
      }

      return $wl;
   }

   /**
    * @param       $impact
    * @param array $list
    * @param int   $public
    *
    * @return string
    */
   private
   function displayContent($impact, $list = [], $public = 0) {

      $div    = "";
      $config = new PluginMydashboardConfig();
      $config->getFromDB(1);

      $class = "plugin_mydashboard_fa-thermometer-" . ($impact - 1);
      $style = "color:" . $config->getField('impact_' . $impact);

      $div .= "<div class='bt-row weather_public_block'>";
      $div .= "<div class='center'><h3>" . PluginMydashboardConfig::displayField($config, 'title_alerts_widget') . "</h3></div>";
      $div .= "<div class=\"bt-col-xs-3 right \">";
      $div .= "<i style='$style' class='fa $class fa-alert-4'></i>";
      $div .= "</div>";
      $div .= "<div class=\"bt-col-xs-8 alert-title-div\" style=\"margin-top: 30px;\">";
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
   private function getMessage($list, $public) {

      $l      = "";
      $config = new PluginMydashboardConfig();
      $config->getFromDB(1);
      if (!empty($list)) {
         foreach ($list as $listitem) {

            $configColor = $config->getField("impact_" . $listitem['impact']);
            //            $class     = (Html::convDate(date("Y-m-d")) == Html::convDate($listitem['date'])) ? 'alert_new' : '';
            //            $class     = ' alert_impact' . $listitem['impact'];
            $style = "background-color : " . $configColor;
            //            $classfont = ' alert_fontimpact' . $listitem['impact'];
            $styleFont = 'color : ' . $configColor;
            $rand      = mt_rand();
            $name      = (Session::haveRight("reminder_public", READ)) ?
               "<a  href='" . Reminder::getFormURL() . "?id=" . $listitem['id'] . "'>" . $listitem['name'] . "</a>"
               : $listitem['name'];

            $l .= "<div id='alert$rand'>";
            $l .= "<span style='$style' class='alert_impact'></span>";
            //            if (isset($listitem['begin_view_date'])
            //                && isset($listitem['end_view_date'])
            //            ) {
            //               $l .= "<span class='alert_date'>" . Html::convDateTime($listitem['begin_view_date']) . " - " . Html::convDateTime($listitem['end_view_date']) . "</span><br>";
            //            }
            $l .= "<span style='$styleFont'>" . $name . "</span>";
            $l .= "</div>";
         }
      } else {
         $l .= "<div align='center'><br><br><h3><span class ='alert-color'>";
         $l .= __("No problem detected", "mydashboard");
         $l .= "</span></h3></div>";
      }
      $l .= "<br>";

      return $l;
   }

   /**
    * @param Reminder $item
    */
   private function showForm(Reminder $item) {
      $reminders_id = $item->getID();

      $this->getFromDBByCrit(['reminders_id' => $reminders_id]);

      if (isset($this->fields['id'])) {
         $id                = $this->fields['id'];
         $impact            = $this->fields['impact'];
         $itilcategories_id = $this->fields['itilcategories_id'];
         $type              = $this->fields['type'];
         $is_public         = $this->fields['is_public'];
      } else {
         $id                = -1;
         $type              = 0;
         $impact            = 0;
         $itilcategories_id = 0;
         $is_public         = 0;
      }
      echo "<form action='" . $this->getFormURL() . "' method='post' >";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='2'>" . _n('Alert', 'Alerts', 2, 'mydashboard') . "</th></tr>";

      $types    = [];
      $types[0] = _n('Alert', 'Alerts', 1, 'mydashboard');
      $types[1] = _n('Scheduled maintenance', 'Scheduled maintenances', 1, 'mydashboard');
      $types[2] = _n('Information', 'Informations', 1, 'mydashboard');
      echo "<tr class='tab_bg_2'><td>" . __("Type") . "</td><td>";
      Dropdown::showFromArray('type', $types, [
                                       'value' => $type
                                    ]
      );
      echo "</td></tr>";

      $impacts    = [];
      $impacts[0] = __("No impact", "mydashboard");
      for ($i = 1; $i <= 5; $i++) {
         $impacts[$i] = CommonITILObject::getImpactName($i);
      }

      echo "<tr class='tab_bg_2'><td>" . __("Alert level", "mydashboard") . "</td><td>";
      Dropdown::showFromArray('impact', $impacts, [
                                         'value' => $impact
                                      ]
      );
      echo "</td></tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('Linked with a ticket category', 'mydashboard') . "</td>";
      echo "<td>";
      $opt = ['name'        => 'itilcategories_id',
              'value'       => $itilcategories_id,
              'entity'      => $_SESSION['glpiactiveentities'],
              'entity_sons' => true,
              'toadd'       => [-1 => __('All categories', 'mydashboard')]];
      ITILCategory::dropdown($opt);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'><td>" . __("Public") . "</td><td>";
      Dropdown::showYesNo('is_public', $is_public);
      echo "</td></tr>";

      if (Session::haveRight("reminder_public", UPDATE)) {
         echo "<tr class='tab_bg_1 center'><td colspan='2'>";
         echo Html::submit(_sx('button', 'Save'), ['name' => 'update']);
         echo Html::hidden("id", ['value' => $id]);
         echo Html::hidden("reminders_id", ['value' => $reminders_id]);
         echo "</td></tr>";
      }
      echo "</table>";
      Html::closeForm();
   }


   /**
    * @param $item
    */
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

         $this->getFromDBByCrit(['reminders_id' => $reminders_id]);

         if (isset($this->fields['id'])) {
            $id                = $this->fields['id'];
            $impact            = $this->fields['impact'];
            $itilcategories_id = $this->fields['itilcategories_id'];
            $type              = $this->fields['type'];
            $is_public         = $this->fields['is_public'];
         } else {
            $id                = -1;
            $type              = 0;
            $impact            = 0;
            $itilcategories_id = 0;
            $is_public         = 0;
         }
         echo "<form action='" . $this->getFormURL() . "' method='post' >";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='2'>" . _n('Alert', 'Alerts', 2, 'mydashboard') . "</th></tr>";

         $types    = [];
         $types[0] = _n('Network alert', 'Network alerts', 1, 'mydashboard');
         $types[1] = _n('Scheduled maintenance', 'Scheduled maintenances', 1, 'mydashboard');
         $types[2] = _n('Information', 'Informations', 1, 'mydashboard');

         echo "<tr class='tab_bg_2'><td>" . __("Type") . "</td><td>";
         Dropdown::showFromArray('type', $types, [
                                          'value' => $type
                                       ]
         );
         echo "</td></tr>";

         $impacts    = [];
         $impacts[0] = __("No impact", "mydashboard");
         for ($i = 1; $i <= 5; $i++) {
            $impacts[$i] = CommonITILObject::getImpactName($i);
         }

         echo "<tr class='tab_bg_2'><td>" . __("Alert level", "mydashboard") . "</td><td>";
         Dropdown::showFromArray('impact', $impacts, [
                                            'value' => $impact
                                         ]
         );
         echo "</td></tr>";

         echo "<tr class='tab_bg_2'>";
         echo "<td>" . __('Linked with a ticket category', 'mydashboard') . "</td>";
         echo "<td>";
         $opt = ['name'        => 'itilcategories_id',
                 'value'       => $itilcategories_id,
                 'entity'      => $_SESSION['glpiactiveentities'],
                 'entity_sons' => true];
         ITILCategory::dropdown($opt);
         echo "</td>";
         echo "</tr>";

         echo "<tr class='tab_bg_2'><td>" . __("Public") . "</td><td>";
         Dropdown::showYesNo('is_public', $is_public);
         echo "</td></tr>";

         if (Session::haveRight("reminder_public", UPDATE)) {
            echo "<tr class='tab_bg_1 center'><td colspan='2'>";
            echo Html::submit(_sx('button', 'Save'), ['name' => 'update']);
            echo Html::hidden("id", ['value' => $id]);
            echo Html::hidden("reminders_id", ['value' => $reminders_id]);
            echo "</td></tr>";
         }
         echo "</table>";
         Html::closeForm();

         $reminder->showVisibility();
      }
   }


   /**
    * @param $class
    *
    * @return bool|string
    */
   static function getWidgetMydashboardAlert($class) {

      if (PluginMydashboardAlert::countForAlerts(0, 0) > 0) {
         $display = "<div class=\"bt-feature $class \">";
         $display .= "<h3 class=\"bt-title-divider\">";
         $display .= "<span>";
         $config  = new PluginMydashboardConfig();
         $config->getFromDB(1);
         $display .= PluginMydashboardConfig::displayField($config, 'title_alerts_widget');
         $display .= "</span>";
         $display .= "</h3>";
         $display .= "<div align='left' style='margin: 5px;'><small style='font-size: 11px;'>";
         $display .= __('A network alert can impact you and will avoid creating a ticket', 'mydashboard') . "</small></div>";
         $display .= "<div id=\"display-sc\">";
         $alerts  = new self();
         $display .= $alerts->getAlertList(0);
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
   static function handleShellcommandResult(&$message, $url) {
      global $CFG_GLPI;

      $alert = "";
      if (isset($CFG_GLPI["maintenance_mode"]) && $CFG_GLPI["maintenance_mode"]) {
         $alert .= "<div class='center' style='color:darkred'><i class='fas fa-exclamation-circle fa-4x'></i><br><br>";
         $alert .= "<b>";
         $alert .= __('Service is down for maintenance. It will be back shortly.');
         $alert .= "</b></div>";
         if (isset($CFG_GLPI["maintenance_text"]) && !empty($CFG_GLPI["maintenance_text"])) {
            $alert .= "<div class='md-status'>";
            $alert .= "<p>" . nl2br($CFG_GLPI["maintenance_text"]) . "</p>";
            $alert .= "</div>";
         }
         $message = "";
      } else if (preg_match('/PROBLEM/is', $message)) {
         $alert .= "<div class='md-title-status' style='color:darkred'><i class='fas fa-exclamation-circle fa-4x'></i><br><br>";
         $alert .= "<b>";
         $alert .= __("Problem with GLPI", "mydashboard");
         $alert .= "</b></div>";
      } else if (preg_match('/OK/is', $message)) {
         $alert .= "<div class='md-title-status' style='color:forestgreen'><i class='far fa-check-circle fa-4x'></i><br><br>";
         $alert .= "<b>";
         $alert .= __("GLPI is OK", "mydashboard");
         $alert .= "</b></div>";
      } else {
         $alert .= "<div class='md-title-status' style='color:orange'><i class='fas fa-exclamation-triangle fa-4x'></i><br><br>";
         $alert .= "<b>";
         $alert .= __("Alert is not properly configured or is not reachable (or exceeded the timeout)", "mydashboard");
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
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      //      curl_setopt($ch, CURLOPT_HEADER, 1);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_COOKIEFILE, "cookiefile");
      curl_setopt($ch, CURLOPT_COOKIEJAR, "cookiefile"); // SAME cookiefile

      //Do we have post field to send?
      if (!empty($options["post"])) {
         //curl_setopt($ch, CURLOPT_POST,true);
         $post = '';
         foreach ($options['post'] as $key => $value) {
            $post .= $key . '=' . $value . '&';
         }
         rtrim($post, '&');
         curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type:application/x-www-form-urlencoded"]);
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

      if (//!$options["download"] &&
      !$data
      ) {
         curl_getinfo($ch, CURLINFO_HTTP_CODE);
         curl_close($ch); // make sure we closeany current curl sessions
         //die($http_code.' Unable to connect to server. Please come back later.');
      } else {
         curl_close($ch);
      }

      //if ($options["download"]) {
      //fclose($fp);
      //}
      if (//!$options["download"] &&
      $data
      ) {
         return $data;
      }
   }
}