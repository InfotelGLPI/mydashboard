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

namespace GlpiPlugin\Mydashboard\Reports;

use CommonGLPI;
use CommonITILActor;
use CommonITILObject;
use DbUtils;
use FieldUnicity;
use GlpiPlugin\Mydashboard\Criteria;
use GlpiPlugin\Mydashboard\Criterias\Entity;
use GlpiPlugin\Mydashboard\Criterias\ITILCategory;
use GlpiPlugin\Mydashboard\Criterias\Technician;
use GlpiPlugin\Mydashboard\Criterias\TechnicianGroup;
use GlpiPlugin\Mydashboard\Datatable;
use GlpiPlugin\Mydashboard\Helper;
use GlpiPlugin\Mydashboard\Menu;
use GlpiPlugin\Mydashboard\Preference as MydashboardPreference;
use GlpiPlugin\Mydashboard\Widget;
use Plugin;
use Session;
use Toolbox;

/**
 * Class Reports_Table
 */
class Reports_Table extends CommonGLPI
{
    private $options;
    private $pref;
    public static $reports = [3, 5, 14, 32, 33];

    /**
     * Reports_Table constructor.
     *
     * @param array $_options
     */
    public function __construct($_options = [])
    {
        $this->options = $_options;
    }

    /**
     * @return array
     */
    public function getWidgetsForItem()
    {
        $widgets = [
            Menu::$HELPDESK => [

                $this->getType() . "32" => [
                    "title" => __("Number of opened tickets by technician and by status", "mydashboard"),
                    "type" => Widget::$TABLE,
                    "comment" => "",
                ],
                $this->getType() . "33" => [
                    "title" => __("Number of opened tickets by group and by status", "mydashboard"),
                    "type" => Widget::$TABLE,
                    "comment" => "",
                ],
            ],
            Menu::$INVENTORY => [

                $this->getType() . "5" => [
                    "title" => __("Fields unicity"),
                    "type" => Widget::$TABLE,
                    "comment" => __("Display if you have duplicates into inventory", "mydashboard"),
                ],
            ],
            Menu::$TOOLS => [

                $this->getType() . "14" => [
                    "title" => __("All unpublished articles", "mydashboard"),
                    "type" => Widget::$TABLE,
                    "comment" => __("Display unpublished articles of Knowbase", "mydashboard"),
                ],

            ],
            Menu::$USERS => [

                $this->getType() . "3" => [
                    "title" => __("Internal annuary", "mydashboard"),
                    "type" => Widget::$TABLE,
                    "comment" => __("Search users of your organisation", "mydashboard"),
                ],
            ],
        ];

        return $widgets;
    }


    /**
     * @param $widgetID
     *
     * @return false|mixed
     */
    public function getTitleForWidget($widgetID)
    {
        $widgets = $this->getWidgetsForItem();
        foreach ($widgets as $type => $list) {
            foreach ($list as $name => $widget) {
                if ($widgetID == $name) {
                    return $widget['title'];
                }
            }
        }
        return false;
    }

    /**
     * @param $widgetID
     *
     * @return false|mixed
     */
    public function getCommentForWidget($widgetID)
    {
        $widgets = $this->getWidgetsForItem();
        foreach ($widgets as $type => $list) {
            foreach ($list as $name => $widget) {
                if ($widgetID == $name) {
                    return $widget['comment'];
                }
            }
        }
        return false;
    }

    /**
     * @param       $widgetId
     * @param array $opt
     *
     * @return Datatable
     * @throws \GlpitestSQLError
     */
    public function getWidgetContentForItem($widgetId, $opt = [])
    {
        global $DB;
        $isDebug = $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE;
        $dbu = new DbUtils();
        $preference = new MydashboardPreference();
        if (Session::getLoginUserID() !== false
            && !$preference->getFromDB(Session::getLoginUserID())) {
            $preference->initPreferences(Session::getLoginUserID());
        }
        $preference->getFromDB(Session::getLoginUserID());
        $preferences = $preference->fields;

        switch ($widgetId) {
            case $this->getType() . "3":

                $criteria = [
                    'SELECT' => ['firstname',
                        'realname',
                        'name',
                        'phone',
                        'phone2',
                        'mobile'],
                    'FROM' => 'glpi_users',
                    'LEFT JOIN'       => [
                        'glpi_profiles_users' => [
                            'ON' => [
                                'glpi_users' => 'id',
                                'glpi_profiles_users'          => 'users_id',
                            ],
                        ],
                    ],
                    'WHERE' => [
                        'glpi_users.is_deleted' => 0,
                        'glpi_users.is_active' => 1,
                    ],
                    'GROUPBY' => 'name',
                    'ORDERBY' => 'realname,firstname ASC',
                ];
                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                    'glpi_profiles_users'
                );

                $iterator = $DB->request($criteria);

                $headers = [
                    __('First name'),
                    __('Name'),
                    __('Login'),
                    __('Phone'),
                    __('Phone 2'),
                    __('Mobile phone'),
                ];

                $rows = [];
                if (count($iterator) > 0) {
                    $i = 0;
                    foreach ($iterator as $data) {
                        if (!empty($data['firstname'])
                            && !empty($data['realname'])
                            && (!empty($data['phone'])
                            || !empty($data['phone2'])
                                || !empty($data['mobile']))) {
                            $rows[$i]['firstname'] = $data['firstname'];
                            $rows[$i]['realname'] = $data['realname'];
                            $rows[$i]['name'] = $data['name'];
                            $rows[$i]['phone'] = $data['phone'];
                            $rows[$i]['phone2'] = $data['phone2'];
                            $rows[$i]['mobile'] = $data['mobile'];
                            $i++;
                        }
                    }
                }

                $widget = new Datatable();
                $title = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetTitle((($isDebug) ? "3 " : "") . $title);
                $widget->setWidgetComment($comment);

                $widget->setTabNames($headers);
                $widget->setTabDatas($rows);

                $widget->setOption("bPaginate", false);
                $widget->setOption("bFilter", false);
                $widget->setOption("bInfo", false);

                $widget->toggleWidgetRefresh();

                return $widget;

            case $this->getType() . "5":

                $criteria = [
                    'SELECT' => 'id',
                    'FROM' => 'glpi_fieldunicities',
                    'WHERE' => [
                        'is_active' => 1,
                    ],
                    'ORDERBY' => 'entities_id DESC',
                ];
                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                    'glpi_fieldunicities'
                );

                $iterator = $DB->request($criteria);

                $headers = [__('Name'), __('Duplicates')];

                $datas = [];
                $i = 0;
                if (count($iterator) > 0) {
                    foreach ($iterator as $data) {
                        $unicity = new FieldUnicity();
                        $unicity->getFromDB($data["id"]);

                        if (!$item = getItemForItemtype($unicity->fields['itemtype'])) {
                            continue;
                        }
                        $datas[$i]["name"] = $unicity->fields["name"];

                        $fields = [];
                        $where_fields = [];

                        foreach (explode(',', $unicity->fields['fields']) as $field) {
                            $fields[] = $field;
                            $where_fields[] = $field;
                        }

                        if (!empty($fields)) {
                            $entities = [$unicity->fields['entities_id']];
                            if ($unicity->fields['is_recursive']) {
                                $entities = getSonsOf('glpi_entities', $unicity->fields['entities_id']);
                            }

                            $where_fields_string = [];


                            $query_field = [
                                'SELECT' => [
                                    'COUNT' => '* AS cpt',
                                ],
                                'FROM' => $item->getTable(),
                                'WHERE' => [
                                    'entities_id' => $entities,
                                ],
                                'GROUPBY' => $fields,
                                'ORDERBY' => ['cpt DESC'],
                            ];

                            if ($item->maybeTemplate()) {
                                $query_field['WHERE'] = $query_field['WHERE'] + ['is_template' => 0];
                            }
                            $query_field['WHERE'] = $query_field['WHERE'] + $where_fields_string;
                            $count = 0;
                            foreach ($DB->request($query_field) as $uniq) {
                                if ($uniq['cpt'] > 1) {
                                    $count++;
                                }
                            }
                            $datas[$i]["duplicates"] = $count;
                        } else {
                            $datas[$i]["duplicates"] = __('No results found');
                        }
                        $i++;
                    }
                }

                $widget = new Datatable();
                $title = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetTitle((($isDebug) ? "5 " : "") . $title);
                $widget->setWidgetComment($comment);

                $widget->setTabNames($headers);
                $widget->setTabDatas($datas);

                $widget->setOption("bPaginate", false);
                $widget->setOption("bFilter", false);
                $widget->setOption("bInfo", false);

                $widget->toggleWidgetRefresh();

                return $widget;


            case $this->getType() . "14":

                $criteria = [
                    'SELECT' => ['glpi_knowbaseitems.*',
                        'glpi_knowbaseitemcategories.completename AS category'],
                    'DISTINCT'        => true,
                    'FROM' => 'glpi_knowbaseitems',
                    'LEFT JOIN'       => [
                        'glpi_knowbaseitems_users' => [
                            'ON' => [
                                'glpi_knowbaseitems_users' => 'knowbaseitems_id',
                                'glpi_knowbaseitems'          => 'id',
                            ],
                        ],
                        'glpi_groups_knowbaseitems' => [
                            'ON' => [
                                'glpi_groups_knowbaseitems' => 'knowbaseitems_id',
                                'glpi_knowbaseitems'          => 'id',
                            ],
                        ],
                        'glpi_knowbaseitems_profiles' => [
                            'ON' => [
                                'glpi_knowbaseitems_profiles' => 'knowbaseitems_id',
                                'glpi_knowbaseitems'          => 'id',
                            ],
                        ],
                        'glpi_entities_knowbaseitems' => [
                            'ON' => [
                                'glpi_entities_knowbaseitems' => 'knowbaseitems_id',
                                'glpi_knowbaseitems'          => 'id',
                            ],
                        ],
                        'glpi_knowbaseitems_knowbaseitemcategories' => [
                            'ON' => [
                                'glpi_knowbaseitems_knowbaseitemcategories' => 'knowbaseitems_id',
                                'glpi_knowbaseitems'          => 'id',
                            ],
                        ],
                        'glpi_knowbaseitemcategories' => [
                            'ON' => [
                                'glpi_knowbaseitems_knowbaseitemcategories' => 'knowbaseitemcategories_id',
                                'glpi_knowbaseitemcategories'          => 'id',
                            ],
                        ],
                    ],
                    'WHERE' => [
                        'glpi_entities_knowbaseitems.entities_id' => null,
                        'glpi_knowbaseitems_profiles.profiles_id' => null,
                        'glpi_groups_knowbaseitems.groups_id' => null,
                        'glpi_knowbaseitems_users.users_id' => null,
                    ],

                ];


                $iterator = $DB->request($criteria);

                $headers = [__('Subject'), __('Writer'), __('Category')];

                $datas = [];
                $i = 0;

                $knowbaseitem = new \KnowbaseItem();
                if (count($iterator) > 0) {
                    foreach ($iterator as $data) {
                        $knowbaseitem->getFromDB($data['id']);

                        $datas[$i]["name"] = $knowbaseitem->getLink();
                        $datas[$i]["users"] = getUserName($data["users_id"]);
                        $datas[$i]["category"] = $data["category"];

                        $i++;
                    }
                }


                $widget = new Datatable();
                $title = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetTitle((($isDebug) ? "14 " : "") . $title);
                $widget->setWidgetComment($comment);

                $widget->setTabNames($headers);
                $widget->setTabDatas($datas);

                $widget->setOption("bPaginate", false);
                $widget->setOption("bFilter", false);
                $widget->setOption("bInfo", false);

                $widget->toggleWidgetRefresh();

                return $widget;


            case $this->getType() . "32":
                $name = 'NumberOfTicketsByTechnicianAndStatus';

                $criterias = Criteria::getDefaultCriterias();

                $params = [
                    "preferences" => $preferences,
                    "criterias" => $criterias,
                    "opt" => $opt,
                ];

                $default = Criteria::manageCriterias($params);

                $technician_group = $opt['technicians_groups_id'] ?? $default['technicians_groups_id'];
                // Allowed status
                $statusList = [
                    CommonITILObject::ASSIGNED,
                    CommonITILObject::PLANNED,
                    CommonITILObject::WAITING,
                    CommonITILObject::SOLVED,
                ];

                // List of technicians active and not deleted
                //                $query_technicians = "SELECT `glpi_groups_users`.`users_id`"
                //                    . " FROM `glpi_groups_users`"
                //                    . " LEFT JOIN `glpi_groups` ON (`glpi_groups_users`.`groups_id` = `glpi_groups`.`id`)"
                //                    . " INNER JOIN `glpi_users` ON (`glpi_users`.`id` = `glpi_groups_users`.`users_id`)"
                //                    . " WHERE `glpi_groups`.`is_assign` = 1"
                //                    . " AND `glpi_users`.`is_active` = 1"
                //                    . " AND `glpi_users`.`is_deleted` = 0"
                //                    . $groups_sql_criteria
                //                    . $users_criteria
                //                    . " GROUP BY `glpi_groups_users`.`users_id`";

                $is_deleted = ['glpi_users.is_deleted' => 0];
                $query_technicians = [
                    'SELECT' => [
                        'glpi_groups_users.users_id',
                    ],
                    'FROM' => 'glpi_groups_users',
                    'LEFT JOIN'       => [
                        'glpi_groups' => [
                            'ON' => [
                                'glpi_groups_users' => 'groups_id',
                                'glpi_groups'          => 'id',
                            ],
                        ],
                    ],
                    'INNER JOIN'       => [
                        'glpi_users' => [
                            'ON' => [
                                'glpi_groups_users' => 'users_id',
                                'glpi_users'          => 'id',
                            ],
                        ],
                    ],
                    'WHERE' => [
                        $is_deleted,
                        'glpi_users.is_active' =>  1,
                        'glpi_groups.is_assign' => 1,
                    ],
                    'GROUPBY' => ['glpi_groups_users.users_id'],
                ];

                //                $query_technicians = Criteria::addCriteriasForQuery($query_technicians, $params);
                // GROUP
                if (isset($technician_group)
                    && $technician_group != 0
                    && !empty($technician_group)) {
                    $query_technicians['WHERE'] = $query_technicians['WHERE'] + ['glpi_groups_users.groups_id' => $technician_group];
                }

                // Number of tickets by technician and by status more ticket
                $moreTicketType = [];
                if (Plugin::isPluginActive('moreticket')) {
                    //                    $query_moretickets_by_technician_by_status = "SELECT count(*) as nb,
                    //                    `glpi_tickets_users`.`users_id` as userid,  `glpi_plugin_moreticket_waitingtickets`.`tickets_id` AS ticketid,"
                    //                        . " `glpi_plugin_moreticket_waitingtypes`.`completename` AS statusname,"
                    //                        . " `glpi_plugin_moreticket_waitingtickets`.`plugin_moreticket_waitingtypes_id` AS type"
                    //                        . " FROM `glpi_plugin_moreticket_waitingtickets`"
                    //                        . " INNER JOIN `glpi_tickets` ON `glpi_tickets`.`id` = `glpi_plugin_moreticket_waitingtickets`.`tickets_id`"
                    //                        . " INNER JOIN `glpi_plugin_moreticket_waitingtypes`"
                    //                        . " ON `glpi_plugin_moreticket_waitingtickets`.`plugin_moreticket_waitingtypes_id`=`glpi_plugin_moreticket_waitingtypes`.`id`"
                    //                        . " INNER JOIN `glpi_tickets_users` ON (`glpi_tickets`.`id` = `glpi_tickets_users`.`tickets_id` AND `glpi_tickets_users`.`type` = 2 AND `glpi_tickets`.`is_deleted` = 0)"
                    //                        . " LEFT JOIN `glpi_entities` ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`)"
                    //                        . " GROUP BY userid,statusname"
                    //                        . " ORDER BY statusname";

                    $is_deleted = ['glpi_tickets.is_deleted' => 0];
                    $query_moretickets_by_technician_by_status = [
                        'SELECT' => [
                            'COUNT' => 'glpi_plugin_moreticket_waitingtickets.id AS nb',
                            'glpi_tickets_users.users_id AS userid',
                            'glpi_plugin_moreticket_waitingtickets.tickets_id AS ticketid',
                            'glpi_plugin_moreticket_waitingtypes.completename AS statusname',
                            'glpi_plugin_moreticket_waitingtickets.plugin_moreticket_waitingtypes_id AS type',
                        ],
                        'DISTINCT' => true,
                        'FROM' => 'glpi_tickets',
                        'INNER JOIN'       => [
                            'glpi_tickets' => [
                                'ON' => [
                                    'glpi_plugin_moreticket_waitingtickets'   => 'tickets_id',
                                    'glpi_tickets'         => 'id',
                                ],
                            ],
                            'glpi_plugin_moreticket_waitingtypes' => [
                                'ON' => [
                                    'glpi_plugin_moreticket_waitingtickets'   => 'plugin_moreticket_waitingtypes_id',
                                    'glpi_plugin_moreticket_waitingtypes'         => 'id',
                                ],
                            ],
                            'glpi_tickets_users' => [
                                'ON' => [
                                    'glpi_tickets_users'   => 'tickets_id',
                                    'glpi_tickets'         => 'id', [
                                        'AND' => [
                                            'glpi_tickets_users.type' => CommonITILActor::ASSIGN,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'WHERE' => [
                            $is_deleted,
                        ],
                        'GROUPBY' => 'userid, statusname',
                        'ORDERBY' => 'statusname',
                    ];

                    $query_moreticket_type = [
                        'SELECT' => [
                            'completename AS typename',
                            'id AS typeid',
                        ],
                        'DISTINCT' => true,
                        'FROM' => 'glpi_plugin_moreticket_waitingtypes',
                        'ORDERBY' => 'typename',
                    ];

                    $iterator_moreticket_type = $DB->request($query_moreticket_type);

                    $i = 0;
                    $moreTicketTypeName = [];
                    foreach ($iterator_moreticket_type as $data) {
                        $moreTicketType[$i]['name'] = $data['typename'];
                        $moreTicketType[$i]['id'] = $data['typeid'];
                        array_push($moreTicketTypeName, $data['typename']);
                        $i++;
                    }
                }
                // Number of tickets by technician and by status
                // Tickets are not deleted
                // User Type is 2
                //                $query_tickets_by_technician_by_status = "SELECT COUNT(DISTINCT `glpi_tickets`.`id`) AS nbtickets"
                //                    . " FROM `glpi_tickets`"
                //                    . " INNER JOIN `glpi_tickets_users`"
                //                    . " ON (`glpi_tickets`.`id` = `glpi_tickets_users`.`tickets_id` AND `glpi_tickets_users`.`type` = 2 AND `glpi_tickets`.`is_deleted` = 0)"
                //                    . " LEFT JOIN `glpi_entities` ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`)"
                //                    . " WHERE `glpi_tickets`.`status` = %s"
                //                    . " AND `glpi_tickets_users`.`users_id` = '%s'"
                //                    . $entities_criteria;

                $iterator_technicians = $DB->request($query_technicians);
                $nb = count($iterator_technicians);
                $temp = [];

                $typesTicketStatus = [
                    __('Technician'),
                    _x('status', 'Processing (assigned)'),
                    _x('status', 'Processing (planned)'),
                    __('Pending'),
                    _x('status', 'Solved'),
                ];
                if (count($iterator_technicians) > 0) {
                    $i = 0;
                    foreach ($iterator_technicians as $data) {
                        $nbWaitingTickets = "";
                        $hasMoreTicket = 0;
                        $userId = $data['users_id'];
                        $username = getUserName($userId);
                        $temp[$i] = [0 => $username];
                        $j = 1;
                        foreach ($statusList as $status) {

                            // Lists of tickets by technician by status
                            $is_deleted = ['glpi_tickets.is_deleted' => 0];
                            $query = [
                                'SELECT' => [
                                    'COUNT' => 'glpi_tickets.id AS nbtickets',
                                ],
                                'DISTINCT' => true,
                                'FROM' => 'glpi_tickets',
                                'INNER JOIN'       => [
                                    'glpi_tickets_users' => [
                                        'ON' => [
                                            'glpi_tickets_users'   => 'tickets_id',
                                            'glpi_tickets'         => 'id', [
                                                'AND' => [
                                                    'glpi_tickets_users.type' => CommonITILActor::ASSIGN,
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                                'WHERE' => [
                                    $is_deleted,
                                    'glpi_tickets.status' => $status,
                                    'glpi_tickets_users.users_id' => $userId,
                                ],
                            ];

                            $query['WHERE'] = $query['WHERE'] + getEntitiesRestrictCriteria(
                                'glpi_tickets'
                            );

                            $temp[$i][$j] = 0;
                            $iterator = $DB->request($query);
                            $nb2 = count($iterator);
                            if ($nb2) {
                                foreach ($iterator as $data) {
                                    $value = "";
                                    $nbWaitingTickets = $data['nbtickets'];
                                    if ($data['nbtickets'] != "0") {
                                        $value .= "<a href='#' onclick='" . Widget::removeBackslashes(
                                            $widgetId
                                        ) . "_search($userId, $status, $hasMoreTicket)'>";
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
                        if (Plugin::isPluginActive('moreticket')) {

                            $iterator3 = $DB->request($query_moretickets_by_technician_by_status);
                            $hasMoreTicket = 1;
                            if (count($iterator3) > 0) {
                                foreach ($iterator3 as $dataMoreTicket) {
                                    $array[$dataMoreTicket['statusname']][$dataMoreTicket['userid']] = $dataMoreTicket['nb'];
                                }

                                foreach ($moreTicketType as $key => $value) {
                                    $status = $value['name'];
                                    $statusId = $value['id'];
                                    if (isset($array[$status][$userId])) {
                                        $value = '';
                                        $value .= "<a href='#' onclick='" . Widget::removeBackslashes(
                                            $widgetId
                                        ) . "_search($userId, $statusId , $hasMoreTicket)'>";
                                        $value .= $array[$status][$userId];
                                        $value .= "</a>";
                                        $temp[$i][$j] = $value;
                                        $newNbTickets = $nbWaitingTickets - $array[$status][$userId];
                                        $temp[$i][3] = str_replace(
                                            '>' . $nbWaitingTickets . '<',
                                            '>' . $newNbTickets . '<',
                                            $temp[$i][3]
                                        );
                                    } else {
                                        $temp[$i][$j] = 0;
                                    }
                                    $j++;
                                }
                            }
                        }
                        $i++;
                    }
                    if (Plugin::isPluginActive('moreticket')) {
                        if (isset($array) && count($array) > 0) {
                            $typesTicketStatus = array_merge($typesTicketStatus, $moreTicketTypeName);
                        }
                    }
                }

                $widget = new Datatable();
                $title = __("Number of tickets open by technician and by status", "mydashboard");
                if ($nb > 1 || $nb == 0) {
                    // String technicians never translated in glpi
                    $title .= " : $nb " . __('Technicians', 'mydashboard');
                } else {
                    $title .= " : $nb " . __('Technician');
                }
                //                $title   = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetTitle((($isDebug) ? "32 " : "") . $title);
                $widget->setWidgetComment($comment);

                $widget->setTabNames($typesTicketStatus);
                $hidden[] = ["targets" => 2, "visible" => false];
                $widget->setOption("bDef", $hidden);
                $widget->setTabDatas($temp);
                $widget->toggleWidgetRefresh();

                $params = [
                    "widgetId" => $widgetId,
                    "name" => $name,
                    "onsubmit" => true,
                    "opt" => $opt,
                    "default" => $default,
                    "criterias" => $criterias,
                    "export" => false,
                    "canvas" => false,
                    "nb" => $nb,
                ];
                $widget->setWidgetHeader(Helper::getGraphHeader($params) . "<br>");
                $linkURL = PLUGIN_MYDASHBOARD_WEBDIR . "/ajax/launchURL.php";

                $technician_group = $opt['technicians_groups_id'] ?? $default['technicians_groups_id'];
                $entities_id = $opt['entities_id'] ?? $default['entities_id'];
                $is_recursive_entities = $opt['is_recursive_entities'] ?? $default['is_recursive_entities'];

                $js_group = json_encode($technician_group);
                $js_entity = $entities_id;
                $js_sons = $is_recursive_entities;

                $widgetId = Widget::removeBackslashes($widgetId);
                $js = "var " . $widgetId . "_search = function(_technician, _status, _hasMoreTicket){
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
                $widget->appendWidgetScriptContent(\Html::scriptBlock($js));

                return $widget;
                break;

            case $this->getType() . "33":
                $name = 'NumberOfTicketsByGroupAndStatus';

                $criterias = Criteria::getDefaultCriterias();

                if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() == 'central') {
                    $specific_criterias = [
                        ITILCategory::$criteria_name,
                    ];
                    $criterias = array_merge($criterias, $specific_criterias);
                }

                $params = [
                    "preferences" => $preferences,
                    "criterias" => $criterias,
                    "opt" => $opt,
                ];

                $default = Criteria::manageCriterias($params);

                // Allowed status
                $statusList = [
                    CommonITILObject::ASSIGNED,
                    CommonITILObject::PLANNED,
                    CommonITILObject::WAITING,
                    CommonITILObject::SOLVED,
                ];

                // List of group active
                $technician_group = $opt['technicians_groups_id'] ?? $default['technicians_groups_id'];
                $technician_group = array_filter($technician_group);


                $criteria = [
                    'SELECT' => ['id', 'name'],
                    'FROM' => 'glpi_groups',
                    'WHERE' => [
                        'is_assign' => 1,
                    ],
                ];

                if (count($technician_group) > 0) {

                    if (isset($opt['is_recursive_requesters']) && $opt['is_recursive_requesters'] != 0) {
                        $childs = [];
                        foreach ($technician_group as $k => $v) {
                            $childs = $dbu->getSonsAndAncestorsOf('glpi_groups', $v);
                        }
                        $criteria['WHERE'] = $criteria['WHERE'] + ['id' => $childs];
                    } else {
                        $criteria['WHERE'] = $criteria['WHERE'] + ['id' => $technician_group];
                    }
                }

                $iterator_group = $DB->request($criteria);

                $moreTicketType = [];
                if (Plugin::isPluginActive('moreticket')) {
                    //                    $query_moretickets_by_group_by_status = "SELECT count(*) as nb, `glpi_groups_tickets`.`groups_id` as groups_id,
                    //                     `glpi_plugin_moreticket_waitingtickets`.`tickets_id` AS ticketid,"
                    //                        . " `glpi_plugin_moreticket_waitingtypes`.`completename` AS statusname,"
                    //                        . " `glpi_plugin_moreticket_waitingtickets`.`plugin_moreticket_waitingtypes_id` AS type"
                    //                        . " FROM `glpi_plugin_moreticket_waitingtickets`"
                    //                        . " INNER JOIN `glpi_tickets` ON `glpi_tickets`.`id` = `glpi_plugin_moreticket_waitingtickets`.`tickets_id`"
                    //                        . " INNER JOIN `glpi_plugin_moreticket_waitingtypes`"
                    //                        . " ON `glpi_plugin_moreticket_waitingtickets`.`plugin_moreticket_waitingtypes_id`=`glpi_plugin_moreticket_waitingtypes`.`id`"
                    //                        . " INNER JOIN `glpi_groups_tickets` ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id` AND `glpi_groups_tickets`.`type` = 2
                    //                                                            AND `glpi_tickets`.`is_deleted` = 0)"
                    //                        . " LEFT JOIN `glpi_entities` ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`)"
                    //                        . " GROUP BY groups_id,statusname"
                    //                        . " ORDER BY statusname";

                    $is_deleted = ['glpi_tickets.is_deleted' => 0];
                    $query_moretickets_by_group_by_status = [
                        'SELECT' => [
                            'COUNT' => 'glpi_plugin_moreticket_waitingtickets.id AS nb',
                            'glpi_groups_tickets.groups_id AS groups_id',
                            'glpi_plugin_moreticket_waitingtickets.tickets_id AS ticketid',
                            'glpi_plugin_moreticket_waitingtypes.completename AS statusname',
                            'glpi_plugin_moreticket_waitingtickets.plugin_moreticket_waitingtypes_id AS type',
                        ],
                        'DISTINCT' => true,
                        'FROM' => 'glpi_tickets',
                        'INNER JOIN'       => [
                            'glpi_tickets' => [
                                'ON' => [
                                    'glpi_plugin_moreticket_waitingtickets'   => 'tickets_id',
                                    'glpi_tickets'         => 'id',
                                ],
                            ],
                            'glpi_plugin_moreticket_waitingtypes' => [
                                'ON' => [
                                    'glpi_plugin_moreticket_waitingtickets'   => 'plugin_moreticket_waitingtypes_id',
                                    'glpi_plugin_moreticket_waitingtypes'         => 'id',
                                ],
                            ],
                            'glpi_groups_tickets' => [
                                'ON' => [
                                    'glpi_groups_tickets'   => 'tickets_id',
                                    'glpi_tickets'         => 'id', [
                                        'AND' => [
                                            'glpi_groups_tickets.type' => CommonITILActor::ASSIGN,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'WHERE' => [
                            $is_deleted,
                        ],
                        'GROUPBY' => 'groups_id, statusname',
                        'ORDERBY' => 'statusname',
                    ];

                    $query_moreticket_type = [
                        'SELECT' => [
                            'completename AS typename',
                            'id AS typeid',
                        ],
                        'DISTINCT' => true,
                        'FROM' => 'glpi_plugin_moreticket_waitingtypes',
                        'ORDERBY' => 'typename',
                    ];

                    $iterator_moreticket_type = $DB->request($query_moreticket_type);

                    $i = 0;
                    $moreTicketTypeName = [];
                    foreach ($iterator_moreticket_type as $data) {
                        $moreTicketType[$i]['name'] = $data['typename'];
                        $moreTicketType[$i]['id'] = $data['typeid'];
                        array_push($moreTicketTypeName, $data['typename']);
                        $i++;
                    }
                }

                // Number of tickets by group and by status
                // Tickets are not deleted
                // group Type is 2
                //                $query_tickets_by_groups_by_status = "SELECT COUNT(DISTINCT `glpi_tickets`.`id`) AS nbtickets"
                //                    . " FROM `glpi_tickets`"
                //                    . " LEFT JOIN `glpi_groups_tickets`"
                //                    . " ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id` AND `glpi_groups_tickets`.`type` = '" . CommonITILActor::ASSIGN . "'
                //                                                  AND `glpi_tickets`.`is_deleted` = 0)"
                //                    . " LEFT JOIN `glpi_entities` ON (`glpi_tickets`.`entities_id` = `glpi_entities`.`id`)"
                //                    . " WHERE `glpi_tickets`.`status` = %s"
                //                    . " AND `glpi_groups_tickets`.`groups_id` = '%s'"
                //                    . $entities_criteria
                //                    . $category_criteria;


                // Lists of tickets by group by status
                $nb = count($iterator_group);

                $temp = [];

                if ($nb) {
                    $i = 0;

                    foreach ($iterator_group as $data) {
                        $nbWaitingTickets = "";
                        $hasMoreTicket = 0;
                        $groupId = $data['id'];
                        $groupname = $data['name'];

                        $temp[$i] = [0 => $groupname];

                        $j = 1;
                        foreach ($statusList as $status) {

                            $is_deleted = ['glpi_tickets.is_deleted' => 0];
                            $query = [
                                'SELECT' => [
                                    'COUNT' => 'glpi_tickets.id AS nbtickets',
                                ],
                                'DISTINCT' => true,
                                'FROM' => 'glpi_tickets',
                                'LEFT JOIN'       => [
                                    'glpi_groups_tickets' => [
                                        'ON' => [
                                            'glpi_groups_tickets'   => 'tickets_id',
                                            'glpi_tickets'         => 'id', [
                                                'AND' => [
                                                    'glpi_groups_tickets.type' => CommonITILActor::ASSIGN,
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                                'WHERE' => [
                                    $is_deleted,
                                    'glpi_tickets.status' => $status,
                                    'glpi_groups_tickets.groups_id' => $groupId,
                                ],
                            ];

                            $query = Criteria::addCriteriasForQuery($query, $params);

                            $temp[$i][$j] = 0;

                            $iterator2 = $DB->request($query);

                            if (count($iterator2) > 0) {
                                foreach ($iterator2 as $data) {
                                    $value = "";
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
                        if (Plugin::isPluginActive('moreticket')) {

                            $iterator3 = $DB->request($query_moretickets_by_group_by_status);
                            $hasMoreTicket = 1;
                            if (count($iterator3) > 0) {
                                foreach ($iterator3 as $dataMoreTicket) {
                                    $array[$dataMoreTicket['statusname']][$dataMoreTicket['groups_id']] = $dataMoreTicket['nb'];
                                }
                                foreach ($moreTicketType as $key => $value) {
                                    $status = $value['name'];
                                    $statusId = $value['id'];
                                    if (isset($array[$status][$groupId])) {
                                        $value = '';
                                        $value .= "<a href='#' onclick='" . $widgetId . "_searchgroup($groupId, $statusId , $hasMoreTicket)'>";
                                        $value .= $array[$status][$groupId];
                                        $value .= "</a>";
                                        $temp[$i][$j] = $value;
                                        $newNbTickets = $nbWaitingTickets - $array[$status][$groupId];
                                        $temp[$i][3] = str_replace(
                                            '>' . $nbWaitingTickets . '<',
                                            '>' . $newNbTickets . '<',
                                            $temp[$i][3]
                                        );
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

                $widget = new Datatable();

                $title = __("Number of opened tickets by group and by status", "mydashboard");

                if ($nb > 1 || $nb == 0) {
                    // String technicians never translated in glpi
                    $title .= " : $nb " . _n('Group', 'Groups', $nb);
                } else {
                    $title .= " : $nb " . __('Group');
                }

                //                $title   = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetTitle((($isDebug) ? "33 " : "") . $title);
                $widget->setWidgetComment($comment);

                $typesTicketStatus = [
                    __('Group'),
                    _x('status', 'Processing (assigned)'),
                    _x('status', 'Processing (planned)'),
                    __('Pending'),
                    _x('status', 'Solved'),
                ];
                if (count($moreTicketType) > 0) {
                    $typesTicketStatus = array_merge($typesTicketStatus, $moreTicketTypeName);
                }
                $widget->setTabNames($typesTicketStatus);
                $hidden[] = ["targets" => 2, "visible" => false];
                $widget->setOption("bDef", $hidden);
                $widget->setTabDatas($temp);
                $widget->toggleWidgetRefresh();

                $params = [
                    "widgetId" => $widgetId,
                    "name" => $name,
                    "onsubmit" => true,
                    "opt" => $opt,
                    "default" => $default,
                    "criterias" => $criterias,
                    "export" => false,
                    "canvas" => false,
                    "nb" => $nb,
                ];
                $widget->setWidgetHeader(Helper::getGraphHeader($params) . "<br>");

                $linkURL = PLUGIN_MYDASHBOARD_WEBDIR . "/ajax/launchURL.php";

                $entities_id = $opt['entities_id'] ?? $default['entities_id'];
                $is_recursive_entities = $opt['is_recursive_entities'] ?? $default['is_recursive_entities'];

                $js_entity = $entities_id;
                $js_sons = $is_recursive_entities;
                $widgetId = Widget::removeBackslashes($widgetId);
                $js = "var " . $widgetId . "_searchgroup = function(_group, _status, _hasMoreTicket){
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

                $widget->appendWidgetScriptContent(\Html::scriptBlock($js));

                return $widget;

            default:
                break;
        }
        return false;
    }

    /**
     * @param $selected_id
     *
     * @return string
     */
    public static function pluginMydashboardReports_Table32link($params)
    {
        global $CFG_GLPI;

        $options['reset'][] = 'reset';

        // ENTITY | SONS
        $options = Entity::getSearchCriteria($params);

        // USER
        if ($params["params"][Technician::$criteria_name] > 0) {
            $options = Technician::getSearchCriteria($params);
        }

        // STATUS
        if ($params["params"]['moreticket'] == 1) {
            $options = Criteria::addUrlCriteria(Criteria::MORETICKET_WAITINGTYPE, 'equals', $params["params"]["status"], 'AND');
        } else {
            $options = Criteria::addUrlCriteria(Criteria::STATUS, 'equals', $params["params"]["status"], 'AND');
        }

        return $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&'
            . Toolbox::append_params($options, "&");
    }


    /**
     * @param $selected_id
     *
     * @return string
     */
    public static function pluginMydashboardReports_Table33link($params)
    {
        global $CFG_GLPI;

        $options['reset'][] = 'reset';

        $options = Entity::getSearchCriteria($params);

        // STATUS
        if ($params["params"]['moreticket'] == 1) {
            $options = Criteria::addUrlCriteria(Criteria::MORETICKET_WAITINGTYPE, 'equals', $params["params"]["status"], 'AND');
        } else {
            $options = Criteria::addUrlCriteria(Criteria::STATUS, 'equals', $params["params"]["status"], 'AND');
        }

        // Group
        if ($params["params"][TechnicianGroup::$criteria_name] > 0) {
            $options = TechnicianGroup::getSearchCriteria($params);
        }


        return $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&'
            . Toolbox::append_params($options, "&");
    }
}
