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
 * Class PluginMydashboardReports_Table
 */
class PluginMydashboardReports_Table extends CommonGLPI {

   private       $options;
   private       $pref;
   public static $reports = [3, 5, 14, 32, 33];

   /**
    * PluginMydashboardReports_Table constructor.
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
         __('Tables', "mydashboard") => [
            $this->getType() . "3"  => ["title"   => __("Internal annuary", "mydashboard"),
                                        "icon"    => "ti ti-table",
                                        "comment" => __("Search users of your organisation", "mydashboard")],
            $this->getType() . "5"  => ["title"   => __("Fields unicity"),
                                        "icon"    => "ti ti-table",
                                        "comment" => __("Display if you have duplicates into inventory", "mydashboard")],
            $this->getType() . "14" => ["title"   => __("All unpublished articles"),
                                        "icon"    => "ti ti-table",
                                        "comment" => __("Display unpublished articles of Knowbase", "mydashboard")],
            $this->getType() . "32" => ["title"   => __("Number of opened tickets by technician and by status", "mydashboard"),
                                        "icon"    => "ti ti-table",
                                        "comment" => ""],
            $this->getType() . "33" => ["title"   => __("Number of opened tickets by group and by status", "mydashboard"),
                                        "icon"    => "ti ti-table",
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
         case $this->getType() . "3":

            $profile_user = new Profile_User();
            $condition    = $dbu->getEntitiesRestrictCriteria('glpi_profiles_users', 'entities_id', '', true);
            $users        = $profile_user->find($condition);
            $filtredUsers = [];
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
            $headers = [__('First name'), __('Name'), __('Login'), __('Phone'), __('Phone 2'), __('Mobile phone')];

            $widget->setTabNames($headers);
            $hidden[] = ["targets" => 2, "visible" => false];
            $widget->setOption("bDef", $hidden);
            $widget->toggleWidgetRefresh();
            $widget->setWidgetTitle((($isDebug) ? "3 " : "") . __("Internal annuary", "mydashboard"));
            $widget->setWidgetComment(__("Search users of your organisation", "mydashboard"));

            return $widget;
            break;

         case $this->getType() . "5":

            $query = "SELECT id
                FROM `glpi_fieldunicities`
                WHERE `is_active` = '1' " .
                     $dbu->getEntitiesRestrictRequest("AND", 'glpi_fieldunicities', "", $_SESSION['glpiactive_entity'],
                                                      true);
            $query .= "ORDER BY `entities_id` DESC";

            $result = $DB->query($query);
            $nb     = $DB->numrows($result);

            $widget  = PluginMydashboardHelper::getWidgetsFromDBQuery('table', $query);
            $headers = [__('Name'), __('Duplicates')];
            $widget->setTabNames($headers);

            $datas = [];
            $i     = 0;
            if ($nb) {
               while ($data = $DB->fetchAssoc($result)) {

                  $unicity = new FieldUnicity();
                  $unicity->getFromDB($data["id"]);

                  if (!$item = getItemForItemtype($unicity->fields['itemtype'])) {
                     continue;
                  }
                  $datas[$i]["name"] = $unicity->fields["name"];

                  $fields       = [];
                  $where_fields = [];

                  foreach (explode(',', $unicity->fields['fields']) as $field) {
                     $fields[]       = $field;
                     $where_fields[] = $field;
                  }

                  if (!empty($fields)) {

                     $entities = [$unicity->fields['entities_id']];
                     if ($unicity->fields['is_recursive']) {
                        $entities = getSonsOf('glpi_entities', $unicity->fields['entities_id']);
                     }
                     $fields_string = implode(',', $fields);

                     if ($item->maybeTemplate()) {
                        $where_template = " AND `" . $item->getTable() . "`.`is_template` = '0'";
                     } else {
                        $where_template = "";
                     }

                     $where_fields_string = "";
                     foreach ($where_fields as $where_field) {
                        if (getTableNameForForeignKeyField($where_field)) {
                           $where_fields_string .= " AND `$where_field` IS NOT NULL AND `$where_field` <> '0'";
                        } else {
                           $where_fields_string .= " AND `$where_field` IS NOT NULL AND `$where_field` <> ''";
                        }
                     }
                     $query_field             = "SELECT COUNT(*) AS cpt
                               FROM `" . $item->getTable() . "`
                               WHERE `" . $item->getTable() . "`.`entities_id` IN (" . implode(',', $entities) . ")
                                     $where_template
                                     $where_fields_string
                               GROUP BY $fields_string
                               ORDER BY cpt DESC";
                     $count                   = 0;
                     $datas[$i]["duplicates"] = 0;
                     foreach ($DB->request($query_field) as $uniq) {
                        if ($uniq['cpt'] > 1) {
                           $count++;
                        }
                     }
                     $datas[$i]["duplicates"] = $count;
                  } else {
                     $datas[$i]["duplicates"] = __('No item found');
                  }
                  $i++;
               }
            }

            $widget->setTabDatas($datas);
            $widget->setWidgetTitle((($isDebug) ? "5 " : "") . __('Fields unicity'));
            $widget->setWidgetComment(__("Display if you have duplicates into inventory", "mydashboard"));

            return $widget;
            break;

         case $this->getType() . "14":

            $query = "SELECT DISTINCT `glpi_knowbaseitems`.*, `glpi_knowbaseitemcategories`.`completename` AS category 
                     FROM `glpi_knowbaseitems` 
                     LEFT JOIN `glpi_knowbaseitems_users` ON (`glpi_knowbaseitems_users`.`knowbaseitems_id` = `glpi_knowbaseitems`.`id`) 
                     LEFT JOIN `glpi_groups_knowbaseitems` ON (`glpi_groups_knowbaseitems`.`knowbaseitems_id` = `glpi_knowbaseitems`.`id`) 
                     LEFT JOIN `glpi_knowbaseitems_profiles` ON (`glpi_knowbaseitems_profiles`.`knowbaseitems_id` = `glpi_knowbaseitems`.`id`) 
                     LEFT JOIN `glpi_entities_knowbaseitems` ON (`glpi_entities_knowbaseitems`.`knowbaseitems_id` = `glpi_knowbaseitems`.`id`) 
                     LEFT JOIN `glpi_knowbaseitems_knowbaseitemcategories` ON (`glpi_knowbaseitems_knowbaseitemcategories`.`knowbaseitems_id` = `glpi_knowbaseitems`.`id`) 
                     LEFT JOIN `glpi_knowbaseitemcategories` ON (`glpi_knowbaseitems_knowbaseitemcategories`.`knowbaseitemcategories_id` = `glpi_knowbaseitemcategories`.`id`) 
                     WHERE (`glpi_entities_knowbaseitems`.`entities_id` IS NULL 
                     AND `glpi_knowbaseitems_profiles`.`profiles_id` IS NULL 
                     AND `glpi_groups_knowbaseitems`.`groups_id` IS NULL 
                     AND `glpi_knowbaseitems_users`.`users_id` IS NULL)";

            $widget = PluginMydashboardHelper::getWidgetsFromDBQuery('table', $query);
            $widget->getTabDatas();

            $headers = [__('Subject'), __('Writer'), __('Category')];
            $widget->setTabNames($headers);

            $result = $DB->query($query);
            $nb     = $DB->numrows($result);

            $datas = [];
            $i     = 0;

            $knowbaseitem = new KnowbaseItem();
            if ($nb) {
               while ($data = $DB->fetchAssoc($result)) {
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
            $widget->setWidgetTitle((($isDebug) ? "14 " : "") . __('All unpublished articles'));

            return $widget;
            break;

         case $this->getType() . "32":
            $name = 'NumberOfTicketsByTechnicianAndStatus';
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['entities_id',
                             'is_recursive',
                             'technicians_groups_id',
                             'group_is_recursive',
                             'users_id'];
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = [];
            }

            $params = ["preferences" => $this->preferences,
                       "criterias"   => $criterias,
                       "opt"         => $opt];

            $options = PluginMydashboardHelper::manageCriterias($params);
            $crit    = $options['crit'];
            $opt     = $options['opt'];

            $groups_sql_criteria = "";
            $entities_criteria   = $crit['entities_id'];
            $users_criteria      = "";
            $technician_group    = $opt['technicians_groups_id'];

            // GROUP
            if (isset($technician_group) && $technician_group != 0 && !empty($technician_group)) {
               $groups_sql_criteria = " AND `glpi_groups_users`.`groups_id`";
               if (is_array($technician_group)) {
                  $groups_sql_criteria .= " IN (" . implode(",", $technician_group) . ")";
               } else {
                  $groups_sql_criteria .= " = " . $technician_group;
               }
            }

            // USER
            if (isset($crit['users_id']) && $crit['users_id'] != 0 && !empty($crit['users_id'])) {
               $users_criteria = " AND `glpi_groups_users`.`users_id` = " . $crit['users_id'];
            }

            // Allowed status
            $statusList = [
               CommonITILObject::ASSIGNED,
               CommonITILObject::PLANNED,
               CommonITILObject::WAITING,
               CommonITILObject::SOLVED
            ];

            // List of technicians active and not deleted
            $query_technicians = "SELECT `glpi_groups_users`.`users_id`"
                                 . " FROM `glpi_groups_users`"
                                 . " LEFT JOIN `glpi_groups` ON (`glpi_groups_users`.`groups_id` = `glpi_groups`.`id`)"
                                 . " INNER JOIN `glpi_users` ON (`glpi_users`.`id` = `glpi_groups_users`.`users_id`)"
                                 . " WHERE `glpi_groups`.`is_assign` = 1"
                                 . " AND `glpi_users`.`is_active` = 1"
                                 . " AND `glpi_users`.`is_deleted` = 0"
                                 . $groups_sql_criteria
                                 . $users_criteria
                                 . " GROUP BY `glpi_groups_users`.`users_id`";
            // Number of tickets by technician and by status more ticket
            $plugin         = new Plugin();
            $moreTicketType = [];
            if ($plugin->isActivated('moreticket')) {
               $query_moretickets_by_technician_by_status = "SELECT count(*) as nb, `glpi_tickets_users`.`users_id` as userid,  `glpi_plugin_moreticket_waitingtickets`.`tickets_id` AS ticketid,"
                                                            . " `glpi_plugin_moreticket_waitingtypes`.`completename` AS statusname,"
                                                            . " `glpi_plugin_moreticket_waitingtickets`.`plugin_moreticket_waitingtypes_id` AS type"
                                                            . " FROM `glpi_plugin_moreticket_waitingtickets`"
                                                            . " INNER JOIN `glpi_tickets` ON `glpi_tickets`.`id` = `glpi_plugin_moreticket_waitingtickets`.`tickets_id`"
                                                            . " INNER JOIN `glpi_plugin_moreticket_waitingtypes`"
                                                            . " ON `glpi_plugin_moreticket_waitingtickets`.`plugin_moreticket_waitingtypes_id`=`glpi_plugin_moreticket_waitingtypes`.`id`"
                                                            . " INNER JOIN `glpi_tickets_users` ON (`glpi_tickets`.`id` = `glpi_tickets_users`.`tickets_id` AND `glpi_tickets_users`.`type` = 2 AND `glpi_tickets`.`is_deleted` = 0)"
                                                            . " LEFT JOIN `glpi_entities` ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`)"
                                                            . " GROUP BY userid,statusname"
                                                            . " ORDER BY statusname";
               $query_moreticket_type                     = "SELECT DISTINCT `glpi_plugin_moreticket_waitingtypes`.`completename` AS typename,"
                                                            . " `glpi_plugin_moreticket_waitingtypes`.`id` AS typeid FROM `glpi_plugin_moreticket_waitingtypes` ORDER BY typename";
               $result                                    = $DB->query($query_moreticket_type);
               $i                                         = 0;
               $moreTicketTypeName                        = [];
               while ($data = $DB->fetchArray($result)) {
                  $moreTicketType[$i]['name'] = $data['typename'];
                  $moreTicketType[$i]['id']   = $data['typeid'];
                  array_push($moreTicketTypeName, $data['typename']);
                  $i++;
               }
            }
            // Number of tickets by technician and by status
            // Tickets are not deleted
            // User Type is 2
            $query_tickets_by_technician_by_status = "SELECT COUNT(DISTINCT `glpi_tickets`.`id`) AS nbtickets"
                                                     . " FROM `glpi_tickets`"
                                                     . " INNER JOIN `glpi_tickets_users`"
                                                     . " ON (`glpi_tickets`.`id` = `glpi_tickets_users`.`tickets_id` AND `glpi_tickets_users`.`type` = 2 AND `glpi_tickets`.`is_deleted` = 0)"
                                                     . " LEFT JOIN `glpi_entities` ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`)"
                                                     . " WHERE `glpi_tickets`.`status` = %s"
                                                     . " AND `glpi_tickets_users`.`users_id` = '%s'"
                                                     . $entities_criteria;
            // Lists of tickets by technician by status
            $result = $DB->query($query_technicians);
            $nb     = $DB->numrows($result);
            $temp   = [];

            $typesTicketStatus = [__('Technician'),
                                  _x('status', 'Processing (assigned)'),
                                  _x('status', 'Processing (planned)'),
                                  __('Pending'),
                                  _x('status', 'Solved')];
            if ($nb) {
               $i = 0;
               while ($data = $DB->fetchArray($result)) {
                  $nbWaitingTickets = "";
                  $hasMoreTicket    = 0;
                  $userId           = $data['users_id'];
                  $username         = getUserName($userId);
                  $temp[$i]         = [0 => $username];
                  $j                = 1;
                  foreach ($statusList as $status) {
                     $query        = sprintf($query_tickets_by_technician_by_status, $status, $userId);
                     $temp[$i][$j] = 0;
                     $result2      = $DB->query($query);
                     $nb2          = $DB->numrows($result2);
                     if ($nb2) {
                        while ($data = $DB->fetchAssoc($result2)) {
                           $value            = "";
                           $nbWaitingTickets = $data['nbtickets'];
                           if ($data['nbtickets'] != "0") {
                              $value .= "<a href='#' onclick='" . $widgetId . "_search($userId, $status, $hasMoreTicket)'>";
                           }
                           $value .= $data['nbtickets'];
                           if ($data['nbtickets'] != "0") {
                              $value .= "</a>";
                           }
                           $temp[$i][$j] = $value;
                        }
                     }
                     $j++;
                  }
                  if ($plugin->isActivated('moreticket')) {
                     $result3       = $DB->query($query_moretickets_by_technician_by_status);
                     $hasMoreTicket = 1;
                     if ($DB->numrows($result3) > 0) {
                        while ($dataMoreTicket = $DB->fetchAssoc($result3)) {
                           $array[$dataMoreTicket['statusname']][$dataMoreTicket['userid']] = $dataMoreTicket['nb'];
                        }

                        foreach ($moreTicketType as $key => $value) {
                           $status   = $value['name'];
                           $statusId = $value['id'];
                           if (isset($array[$status][$userId])) {
                              $value        = '';
                              $value        .= "<a href='#' onclick='" . $widgetId . "_search($userId, $statusId , $hasMoreTicket)'>";
                              $value        .= $array[$status][$userId];
                              $value        .= "</a>";
                              $temp[$i][$j] = $value;
                              $newNbTickets = $nbWaitingTickets - $array[$status][$userId];
                              $temp[$i][3]  = str_replace('>' . $nbWaitingTickets . '<', '>' . $newNbTickets . '<', $temp[$i][3]);
                           } else {
                              $temp[$i][$j] = 0;
                           }
                           $j++;
                        }
                     }
                  }
                  $i++;
               }
               if ($plugin->isActivated('moreticket')) {
                  if (isset($array) && count($array) > 0) {
                     $typesTicketStatus = array_merge($typesTicketStatus, $moreTicketTypeName);
                  }
               }
            }

            $widget = new PluginMydashboardDatatable();
            $title  = __("Number of tickets open by technician and by status", "mydashboard");
            if ($nb > 1 || $nb == 0) {
               // String technicians never translated in glpi
               $title .= " : $nb " . __('Technicians', 'mydashboard');
            } else {
               $title .= " : $nb " . __('Technician');
            }
            $widget->setWidgetTitle((($isDebug) ? "32 " : "") . $title);

            $widget->setTabNames($typesTicketStatus);
            $widget->setTabDatas($temp);
            $widget->toggleWidgetRefresh();
            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => true,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => false,
                       "canvas"    => false,
                       "nb"        => $nb];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params) . "<br>");
            $linkURL   = PLUGIN_MYDASHBOARD_WEBDIR . "/ajax/launchURL.php";
            $js_group  = json_encode($technician_group);
            $js_entity = $crit['entity'];
            $js_sons   = $crit['sons'];

            $js = "
               var " . $widgetId . "_search = function(_technician, _status, _hasMoreTicket){
                  $.ajax({
                     url: '" . $linkURL . "',
                     type: 'POST',
                     data:{
                        technician_group:$js_group,
                        entities_id:$js_entity, 
                        sons:$js_sons,
                        technician: _technician,
                        status: _status,
                        moreticket: _hasMoreTicket,
                        widget:'$widgetId'},
                     success:function(response) {
                        window.open(response);
                        console.log('SUCCESS');
                     },
                     error:function(response){
                        console.log('FAILED');
                     }
                  });
               }";
            echo Html::scriptBlock($js);
            return $widget;
            break;
         case $this->getType() . "33":
            $name = 'NumberOfTicketsByGroupAndStatus';
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['entities_id',
                             'is_recursive',
                             'technicians_groups_id',
                             'group_is_recursive'];
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = [];
            }

            $params = ["preferences" => $this->preferences,
                       "criterias"   => $criterias,
                       "opt"         => $opt];

            $options = PluginMydashboardHelper::manageCriterias($params);
            $crit    = $options['crit'];
            $opt     = $options['opt'];

            $groups_sql_criteria = "";
            $entities_criteria   = $crit['entities_id'];
            $technician_group    = $opt['technicians_groups_id'];

            // Allowed status
            $statusList = [
               CommonITILObject::ASSIGNED,
               CommonITILObject::PLANNED,
               CommonITILObject::WAITING,
               CommonITILObject::SOLVED
            ];

            // List of group active
            $condition        = "1=1";
            $technician_group = (is_array($technician_group) ? $technician_group : [$technician_group]);
            if (count($technician_group) > 0) {
               if (isset($opt['ancestors']) && $opt['ancestors'] != 0) {
                  $childs = [];

                  foreach ($technician_group as $k => $v) {
                     $childs = $dbu->getSonsAndAncestorsOf('glpi_groups', $v);
                  }
                  $condition .= " AND `id` IN ('" . implode("','", $childs) . "')";
               } else {

                  $condition .= " AND `id` IN ('" . implode("','", $technician_group) . "')";
               }
            }
            $iterator = $DB->request([
                                        'SELECT' => ['id', 'name'],
                                        'FROM'   => 'glpi_groups',
                                        'WHERE'  => [
                                           'is_assign' => 1,
                                           $condition
                                        ]
                                     ]);

            $plugin         = new Plugin();
            $moreTicketType = [];
            if ($plugin->isActivated('moreticket')) {
               $query_moretickets_by_group_by_status = "SELECT count(*) as nb, `glpi_groups_tickets`.`groups_id` as groups_id,  `glpi_plugin_moreticket_waitingtickets`.`tickets_id` AS ticketid,"
                                                       . " `glpi_plugin_moreticket_waitingtypes`.`completename` AS statusname,"
                                                       . " `glpi_plugin_moreticket_waitingtickets`.`plugin_moreticket_waitingtypes_id` AS type"
                                                       . " FROM `glpi_plugin_moreticket_waitingtickets`"
                                                       . " INNER JOIN `glpi_tickets` ON `glpi_tickets`.`id` = `glpi_plugin_moreticket_waitingtickets`.`tickets_id`"
                                                       . " INNER JOIN `glpi_plugin_moreticket_waitingtypes`"
                                                       . " ON `glpi_plugin_moreticket_waitingtickets`.`plugin_moreticket_waitingtypes_id`=`glpi_plugin_moreticket_waitingtypes`.`id`"
                                                       . " INNER JOIN `glpi_groups_tickets` ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id` AND `glpi_groups_tickets`.`type` = 2 
                                                            AND `glpi_tickets`.`is_deleted` = 0)"
                                                       . " LEFT JOIN `glpi_entities` ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`)"
                                                       . " GROUP BY groups_id,statusname"
                                                       . " ORDER BY statusname";

               $query_moreticket_type = "SELECT DISTINCT `glpi_plugin_moreticket_waitingtypes`.`completename` AS typename,"
                                        . " `glpi_plugin_moreticket_waitingtypes`.`id` AS typeid 
                                        FROM `glpi_plugin_moreticket_waitingtypes` ORDER BY typename";
               $result                = $DB->query($query_moreticket_type);
               $i                     = 0;
               $moreTicketTypeName    = [];
               while ($data = $DB->fetchArray($result)) {
                  $moreTicketType[$i]['name'] = $data['typename'];
                  $moreTicketType[$i]['id']   = $data['typeid'];
                  array_push($moreTicketTypeName, $data['typename']);
                  $i++;
               }
            }

            // Number of tickets by group and by status
            // Tickets are not deleted
            // group Type is 2
            $query_tickets_by_groups_by_status = "SELECT COUNT(DISTINCT `glpi_tickets`.`id`) AS nbtickets"
                                                 . " FROM `glpi_tickets`"
                                                 . " LEFT JOIN `glpi_groups_tickets`"
                                                 . " ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id` AND `glpi_groups_tickets`.`type` = '" . CommonITILActor::ASSIGN . "' 
                                                  AND `glpi_tickets`.`is_deleted` = 0)"
                                                 . " LEFT JOIN `glpi_entities` ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`)"
                                                 . " WHERE `glpi_tickets`.`status` = %s"
                                                 . " AND `glpi_groups_tickets`.`groups_id` = '%s'"
                                                 . $entities_criteria;

            // Lists of tickets by group by status
            $nb = count($iterator);

            $temp = [];

            if ($nb) {
               $i = 0;

               foreach ($iterator as $data) {
                  $nbWaitingTickets = "";
                  $hasMoreTicket    = 0;
                  $groupId          = $data['id'];
                  $groupname        = $data['name'];

                  $temp[$i] = [0 => $groupname];

                  $j = 1;
                  foreach ($statusList as $status) {

                     $query = sprintf($query_tickets_by_groups_by_status, $status, $groupId);

                     $temp[$i][$j] = 0;

                     $result2 = $DB->query($query);
                     $nb2     = $DB->numrows($result2);

                     if ($nb2) {

                        while ($data = $DB->fetchAssoc($result2)) {

                           $value            = "";
                           $nbWaitingTickets = $data['nbtickets'];
                           if ($data['nbtickets'] != "0") {
                              $value .= "<a href='#' onclick='" . $widgetId . "_searchgroup($groupId, $status, $hasMoreTicket)'>";
                           }
                           $value .= $data['nbtickets'];
                           if ($data['nbtickets'] != "0") {
                              $value .= "</a>";
                           }
                           $temp[$i][$j] = $value;
                        }
                     }
                     $j++;
                  }
                  if ($plugin->isActivated('moreticket')) {
                     $result3       = $DB->query($query_moretickets_by_group_by_status);
                     $hasMoreTicket = 1;
                     if ($DB->numrows($result3) > 0) {
                        while ($dataMoreTicket = $DB->fetchAssoc($result3)) {
                           $array[$dataMoreTicket['statusname']][$dataMoreTicket['groups_id']] = $dataMoreTicket['nb'];
                        }
                        foreach ($moreTicketType as $key => $value) {
                           $status   = $value['name'];
                           $statusId = $value['id'];
                           if (isset($array[$status][$groupId])) {
                              $value        = '';
                              $value        .= "<a href='#' onclick='" . $widgetId . "_searchgroup($groupId, $statusId , $hasMoreTicket)'>";
                              $value        .= $array[$status][$groupId];
                              $value        .= "</a>";
                              $temp[$i][$j] = $value;
                              $newNbTickets = $nbWaitingTickets - $array[$status][$groupId];
                              $temp[$i][3]  = str_replace('>' . $nbWaitingTickets . '<', '>' . $newNbTickets . '<', $temp[$i][3]);
                           } else {
                              $temp[$i][$j] = 0;
                           }
                           $j++;
                        }
                     }
                  }
                  $i++;
               }
            }

            $widget = new PluginMydashboardDatatable();

            $title = __("Number of opened tickets by group and by status", "mydashboard");

            if ($nb > 1 || $nb == 0) {
               // String technicians never translated in glpi
               $title .= " : $nb " . _n('Group', 'Groups', $nb);
            } else {
               $title .= " : $nb " . __('Group');
            }

            $widget->setWidgetTitle((($isDebug) ? "33 " : "") . $title);

            $typesTicketStatus = [__('Group'),
                                  _x('status', 'Processing (assigned)'),
                                  _x('status', 'Processing (planned)'),
                                  __('Pending'),
                                  _x('status', 'Solved')];
            if (count($moreTicketType) > 0) {
               $typesTicketStatus = array_merge($typesTicketStatus, $moreTicketTypeName);
            }
            $widget->setTabNames($typesTicketStatus);
            $widget->setTabDatas($temp);
            $widget->toggleWidgetRefresh();

            $params = ["widgetId"  => $widgetId,
                       "name"      => $name,
                       "onsubmit"  => true,
                       "opt"       => $opt,
                       "criterias" => $criterias,
                       "export"    => false,
                       "canvas"    => false,
                       "nb"        => $nb];
            $widget->setWidgetHeader(PluginMydashboardHelper::getGraphHeader($params) . "<br>");

            $linkURL = PLUGIN_MYDASHBOARD_WEBDIR . "/ajax/launchURL.php";

            $js_entity = $crit['entity'];
            $js_sons   = $crit['sons'];

            $js = "
               var " . $widgetId . "_searchgroup = function(_group, _status, _hasMoreTicket){
                  $.ajax({
                     url: '" . $linkURL . "',
                     type: 'POST',
                     data:{
                        entities_id:$js_entity, 
                        sons:$js_sons,
                        technician_group: _group,
                        moreticket: _hasMoreTicket,
                        status: _status,
                        widget:'$widgetId'},
                     success:function(response) {
                        window.open(response);
                        console.log('SUCCESS');
                     },
                     error:function(response){
                        console.log('FAILED');
                     }
                  });
               }";

            echo Html::scriptBlock($js);

            return $widget;
            break;

         default:
            break;
      }
   }
}
