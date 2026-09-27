<?php

/**
 * -------------------------------------------------------------------------
 * mydashboard plugin for GLPI
 * Copyright (C) 2016-2026 by the mydashboard Development Team.
 *
 * https://github.com/InfotelGLPI/mydashboard
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of mydashboard.
 *
 * mydashboard is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * mydashboard is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with mydashboard. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
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
use Glpi\DBAL\QueryExpression;
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
        $widgets = [];

        // Ticket statistics, offered to the profiles the widgets are authorized for (see
        // Criteria::addCriteriasForQuery()).
        $widgets[Menu::$HELPDESK] = [

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
        ];

        // The directory lists the login, the name, both phone numbers and the mobile of
        // every user of the visible entities — a ready-made inventory of valid logins. That
        // is a User read, and this widget was the one left ungated next to "5" and "14".
        if (Session::haveRight(\User::$rightname, READ)) {
            $widgets[Menu::$USERS] = [

                $this->getType() . "3" => [
                    "title" => __("Internal annuary", "mydashboard"),
                    "type" => Widget::$TABLE,
                    "comment" => __("Search users of your organisation", "mydashboard"),
                ],
            ];
        }

        // Field unicity rules are configuration objects — FieldUnicity::$rightname is
        // 'config' — yet the widget listed every active rule with its itemtype and its
        // watched fields to any profile that added it to its dashboard.
        if (Session::haveRight(FieldUnicity::$rightname, READ)) {
            $widgets[Menu::$INVENTORY] = [

                $this->getType() . "5" => [
                    "title" => __("Fields unicity"),
                    "type" => Widget::$TABLE,
                    "comment" => __("Display if you have duplicates into inventory", "mydashboard"),
                ],
            ];
        }

        // An unpublished article is precisely one that is bound to no entity, no profile,
        // no group and no user, so KnowbaseItem::getVisibilityCriteria() hides it from
        // everybody and this widget was the only path that exposed its subject, its author
        // and its category. Declare it for the profiles that hold a right on the knowledge
        // base; getWidgetContentForItem() narrows the rows themselves.
        if (Session::haveRight(\KnowbaseItem::$rightname, READ)) {
            $widgets[Menu::$TOOLS] = [

                $this->getType() . "14" => [
                    "title" => __("All unpublished articles", "mydashboard"),
                    "type" => Widget::$TABLE,
                    "comment" => __("Display unpublished articles of Knowbase", "mydashboard"),
                ],

            ];
        }

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
     * @return Datatable|false
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

                // The widget list is cached in $_SESSION, so an entry declared under a
                // previous profile survives a profile switch and can still be refreshed
                // through ajax/refreshWidget.php: check the right again at the content.
                if (!Session::haveRight(\User::$rightname, READ)) {
                    return false;
                }

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
                    'glpi_profiles_users',
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
                            // Datatable writes each cell as HTML (the report widgets need it
                            // for the getLink() anchors they carry), so a plain text column
                            // has to be escaped here. Every field below is free text the user
                            // sets on its own account, which made the directory a stored XSS
                            // reaching all of its colleagues.
                            $rows[$i]['firstname'] = htmlspecialchars((string) $data['firstname'], ENT_QUOTES, 'UTF-8');
                            $rows[$i]['realname'] = htmlspecialchars((string) $data['realname'], ENT_QUOTES, 'UTF-8');
                            $rows[$i]['name'] = htmlspecialchars((string) $data['name'], ENT_QUOTES, 'UTF-8');
                            $rows[$i]['phone'] = htmlspecialchars((string) $data['phone'], ENT_QUOTES, 'UTF-8');
                            $rows[$i]['phone2'] = htmlspecialchars((string) $data['phone2'], ENT_QUOTES, 'UTF-8');
                            $rows[$i]['mobile'] = htmlspecialchars((string) $data['mobile'], ENT_QUOTES, 'UTF-8');
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

                // The widget list is cached in $_SESSION, so an entry declared under a
                // previous profile survives a profile switch and can still be refreshed
                // through ajax/refreshWidget.php: check the right again at the content.
                if (!Session::haveRight(FieldUnicity::$rightname, READ)) {
                    return false;
                }

                $criteria = [
                    'SELECT' => 'id',
                    'FROM' => 'glpi_fieldunicities',
                    'WHERE' => [
                        'is_active' => 1,
                    ],
                    'ORDERBY' => 'entities_id DESC',
                ];
                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                    'glpi_fieldunicities',
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
                        $datas[$i]["name"] = htmlspecialchars((string) $unicity->fields["name"], ENT_QUOTES, 'UTF-8');

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

                // Same reason as widget 5: re-check at the content, the declaration alone
                // does not survive a profile change.
                if (!Session::haveRight(\KnowbaseItem::$rightname, READ)) {
                    return false;
                }

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

                // Nobody may see these articles through the knowledge base itself, so the
                // widget has to stand in for the visibility criteria it cannot apply: a
                // profile that does not administer the base only gets its own drafts back.
                if (!\KnowbaseItem::canUpdate()) {
                    $criteria['WHERE']['glpi_knowbaseitems.users_id'] = Session::getLoginUserID();
                }

                $iterator = $DB->request($criteria);

                $headers = [__('Subject'), __('Writer'), __('Category')];

                $datas = [];
                $i = 0;

                $knowbaseitem = new \KnowbaseItem();
                if (count($iterator) > 0) {
                    foreach ($iterator as $data) {
                        $knowbaseitem->getFromDB($data['id']);

                        $datas[$i]["name"] = $knowbaseitem->getLink();
                        // Plain text cells of an HTML rendered table: the author name is free
                        // text of the user account and the category is the completename of a
                        // dropdown, both stored raw.
                        $datas[$i]["users"] = htmlspecialchars((string) getUserName($data["users_id"]), ENT_QUOTES, 'UTF-8');
                        $datas[$i]["category"] = htmlspecialchars((string) $data["category"], ENT_QUOTES, 'UTF-8');

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

                // The roster was built with no entity boundary at all — the scoping call was
                // left commented out — so the widget named every technician of every entity,
                // and the ticket counts of the entities the session may not read leaked with
                // them. addCriteriasForQuery() cannot help here: it scopes glpi_tickets, a
                // table this query does not join. Scope the assignable group instead, the
                // idiom Helper::getGroupsForUser() already uses. array_merge(), not "+": the
                // WHERE below opens on an integer key and getEntitiesRestrictCriteria() may
                // answer with an integer-keyed deny clause.
                $query_technicians['WHERE'] = array_merge(
                    $query_technicians['WHERE'],
                    getEntitiesRestrictCriteria('glpi_groups', '', '', true),
                );
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
                        'FROM' => 'glpi_plugin_moreticket_waitingtickets',
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
                        'GROUPBY' => ['userid', 'statusname'],
                        'ORDERBY' => 'statusname',
                    ];
                    // The counts of this subquery are merged into the very rows the main count
                    // query builds, and that one is scoped (see $query_counts below), so the
                    // "pending" columns used to aggregate the whole instance while the columns
                    // next to them only showed the entities of the session. array_merge(), not
                    // "+": the WHERE opens on an integer key and getEntitiesRestrictCriteria()
                    // may answer with an integer-keyed QueryExpression('false') deny clause,
                    // which the union operator would silently drop on the key collision.
                    $query_moretickets_by_technician_by_status['WHERE'] = array_merge(
                        $query_moretickets_by_technician_by_status['WHERE'],
                        getEntitiesRestrictCriteria('glpi_tickets'),
                    );

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
                    // Single query returning all (tech, status) counts at once
                    $is_deleted = ['glpi_tickets.is_deleted' => 0];
                    $query_counts = [
                        'SELECT' => [
                            'glpi_tickets_users.users_id',
                            'glpi_tickets.status',
                            new QueryExpression('COUNT(DISTINCT ' . $DB->quoteName('glpi_tickets.id') . ') AS nbtickets'),
                        ],
                        'FROM' => 'glpi_tickets',
                        'INNER JOIN' => [
                            'glpi_tickets_users' => [
                                'ON' => [
                                    'glpi_tickets_users' => 'tickets_id',
                                    'glpi_tickets' => 'id',
                                    ['AND' => ['glpi_tickets_users.type' => CommonITILActor::ASSIGN]],
                                ],
                            ],
                        ],
                        'WHERE' => [
                            $is_deleted,
                            'glpi_tickets.status' => $statusList,
                        ],
                        'GROUPBY' => ['glpi_tickets_users.users_id', 'glpi_tickets.status'],
                    ];
                    $query_counts['WHERE'] += getEntitiesRestrictCriteria('glpi_tickets');

                    $counts_cache = [];
                    foreach ($DB->request($query_counts) as $row) {
                        $counts_cache[$row['users_id']][$row['status']] = (int) $row['nbtickets'];
                    }

                    // Pre-fetch moreticket data once outside the tech loop
                    $array = [];
                    if (Plugin::isPluginActive('moreticket')) {
                        foreach ($DB->request($query_moretickets_by_technician_by_status) as $dataMoreTicket) {
                            $array[$dataMoreTicket['statusname']][$dataMoreTicket['userid']] = $dataMoreTicket['nb'];
                        }
                    }

                    $link_params = [
                        'widget' => self::class . "32",
                        'entities_id' => $opt['entities_id'] ?? $default['entities_id'],
                        'is_recursive_entities' => $opt['is_recursive_entities'] ?? $default['is_recursive_entities'],
                    ];
                    $has_moreticket = Plugin::isPluginActive('moreticket') && count($array) > 0;

                    foreach ($iterator_technicians as $data) {
                        $userId = $data['users_id'];
                        $row_params = $link_params + ['technicians_id' => $userId];

                        $counts = [];
                        foreach ($statusList as $status) {
                            $counts[$status] = $counts_cache[$userId][$status] ?? 0;
                        }
                        $moreticket_counts = [];
                        if ($has_moreticket) {
                            foreach ($moreTicketType as $type) {
                                $moreticket_counts[$type['id']] = (int) ($array[$type['name']][$userId] ?? 0);
                            }
                            // Tickets waiting for a moreticket type are shown in their own column
                            $counts[CommonITILObject::WAITING] = max(0, $counts[CommonITILObject::WAITING] - array_sum($moreticket_counts));
                        }

                        $row = [['kind' => 'text', 'value' => (string) getUserName($userId)]];
                        foreach ($counts as $status => $count) {
                            $row[] = self::getStatusCountCell($count, $row_params + ['status' => $status, 'moreticket' => 0]);
                        }
                        foreach ($moreticket_counts as $type_id => $count) {
                            $row[] = self::getStatusCountCell($count, $row_params + ['status' => $type_id, 'moreticket' => 1]);
                        }
                        $temp[] = $row;
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
                // Same hole as the technician roster: every assignable group of the instance
                // was listed, with its ticket counts, whatever the entities of the session.
                $criteria['WHERE'] = array_merge(
                    $criteria['WHERE'],
                    getEntitiesRestrictCriteria('glpi_groups', '', '', true),
                );

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
                        'FROM' => 'glpi_plugin_moreticket_waitingtickets',
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
                        'GROUPBY' => ['groups_id', 'statusname'],
                        'ORDERBY' => 'statusname',
                    ];
                    // Same asymmetry as the technician widget: the main count query of this
                    // widget goes through Criteria::addCriteriasForQuery() (see below) while
                    // this subquery queried every entity, and both results end up in the same
                    // displayed row. array_merge() for the same reason as there.
                    $query_moretickets_by_group_by_status['WHERE'] = array_merge(
                        $query_moretickets_by_group_by_status['WHERE'],
                        getEntitiesRestrictCriteria('glpi_tickets'),
                    );

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
                    // Single query returning all (group, status) counts at once
                    $is_deleted_g = ['glpi_tickets.is_deleted' => 0];
                    $query_group_counts = [
                        'SELECT' => [
                            'glpi_groups_tickets.groups_id',
                            'glpi_tickets.status',
                            new QueryExpression('COUNT(DISTINCT ' . $DB->quoteName('glpi_tickets.id') . ') AS nbtickets'),
                        ],
                        'FROM' => 'glpi_tickets',
                        'LEFT JOIN' => [
                            'glpi_groups_tickets' => [
                                'ON' => [
                                    'glpi_groups_tickets' => 'tickets_id',
                                    'glpi_tickets' => 'id',
                                    ['AND' => ['glpi_groups_tickets.type' => CommonITILActor::ASSIGN]],
                                ],
                            ],
                        ],
                        'WHERE' => [
                            $is_deleted_g,
                            'glpi_tickets.status' => $statusList,
                        ],
                        'GROUPBY' => ['glpi_groups_tickets.groups_id', 'glpi_tickets.status'],
                    ];
                    $query_group_counts = Criteria::addCriteriasForQuery($query_group_counts, $params);

                    $group_counts_cache = [];
                    foreach ($DB->request($query_group_counts) as $row) {
                        $group_counts_cache[$row['groups_id']][$row['status']] = (int) $row['nbtickets'];
                    }

                    // Pre-fetch moreticket data once outside the group loop
                    $array = [];
                    if (Plugin::isPluginActive('moreticket')) {
                        foreach ($DB->request($query_moretickets_by_group_by_status) as $dataMoreTicket) {
                            $array[$dataMoreTicket['statusname']][$dataMoreTicket['groups_id']] = $dataMoreTicket['nb'];
                        }
                    }

                    $link_params = [
                        'widget' => self::class . "33",
                        'entities_id' => $opt['entities_id'] ?? $default['entities_id'],
                        'is_recursive_entities' => $opt['is_recursive_entities'] ?? $default['is_recursive_entities'],
                    ];
                    $has_moreticket = Plugin::isPluginActive('moreticket') && count($moreTicketType) > 0;

                    foreach ($iterator_group as $data) {
                        $groupId = $data['id'];
                        $row_params = $link_params + ['technicians_groups_id' => [$groupId]];

                        $counts = [];
                        foreach ($statusList as $status) {
                            $counts[$status] = $group_counts_cache[$groupId][$status] ?? 0;
                        }
                        $moreticket_counts = [];
                        if ($has_moreticket) {
                            foreach ($moreTicketType as $type) {
                                $moreticket_counts[$type['id']] = (int) ($array[$type['name']][$groupId] ?? 0);
                            }
                            // Tickets waiting for a moreticket type are shown in their own column
                            $counts[CommonITILObject::WAITING] = max(0, $counts[CommonITILObject::WAITING] - array_sum($moreticket_counts));
                        }

                        $row = [['kind' => 'text', 'value' => (string) $data['name']]];
                        foreach ($counts as $status => $count) {
                            $row[] = self::getStatusCountCell($count, $row_params + ['status' => $status, 'moreticket' => 0]);
                        }
                        foreach ($moreticket_counts as $type_id => $count) {
                            $row[] = self::getStatusCountCell($count, $row_params + ['status' => $type_id, 'moreticket' => 1]);
                        }
                        $temp[] = $row;
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

                return $widget;

            default:
                break;
        }
        return false;
    }

    /**
     * Ticket count of widgets 32 and 33: opens the matching ticket search through ajax/launchURL.php.
     *
     * @param int   $count
     * @param array $params posted to ajax/launchURL.php
     *
     * @return array typed cell of Widget::getDisplayCell()
     */
    private static function getStatusCountCell(int $count, array $params): array
    {
        if ($count <= 0) {
            return ['kind' => 'text', 'value' => '0'];
        }

        return [
            'kind' => 'action',
            'label' => (string) $count,
            'url' => PLUGIN_MYDASHBOARD_WEBDIR . "/ajax/launchURL.php",
            'params' => $params,
        ];
    }

    public static function getLinkForWidget(string $widget, array $options): ?string
    {
        return match (str_replace(self::class, '', $widget)) {
            '32' => self::pluginMydashboardReports_Table32link($options),
            '33' => self::pluginMydashboardReports_Table33link($options),
            default => null,
        };
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
