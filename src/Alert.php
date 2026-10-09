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

namespace GlpiPlugin\Mydashboard;

use CommonDBTM;
use CommonGLPI;
use CommonITILActor;
use CommonITILObject;
use CronTask;
use DateTime;
use DBConnection;
use DbUtils;
use Document;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;
use Glpi\DBAL\QuerySubQuery;
use Glpi\RichText\RichText;
use Glpi\System\Status\StatusChecker;
use GlpiPlugin\Eventsmanager\Event;
use GlpiPlugin\Mydashboard\Html as MydashboardHtml;
use GlpiPlugin\Mydashboard\Reports\Reminder;
use GlpiPlugin\Releases\Release;
use ITILCategory;
use ITILFollowup;
use MailCollector;
use Migration;
use NotImportedEmail;
use Plugin;
use ReminderTranslation;
use Session;
use Toolbox;

/**
 * Class Alert
 */
class Alert extends CommonDBTM
{
    // Managing an alert is a plugin-configuration action — the ticker it feeds is shown to
    // every user, including on the login page — so the class is bound to the right its entry
    // points already require. Declaring it is what makes can()/check() usable at all:
    // CommonGLPI's can* methods all answer false while $rightname is empty. The profile
    // checkbox grants CREATE + UPDATE + PURGE (Profile.php), the three levels needed, and
    // not READ, so canView() keeps answering false exactly as before.
    public static string $rightname = 'plugin_mydashboard_config';

    public static $types = [
        'Reminder',
        'Problem',
        'Change',
        Event::class,
        Release::class,
    ];

    /**
     * @param CommonGLPI $item
     * @param int $withtemplate
     *
     * @return string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        //      if ($item->getType() == 'Reminder'
        //          || $item->getType() == 'Problem'
        //          || $item->getType() == 'Change'
        //          || $item->getType() == Event::class
        //          || $item->getType() == Release::class) {
        //         return _n('Alert Dashboard', 'Alerts Dashboard', 2, 'mydashboard');
        //      }*
        // The tab renders the alert configuration form of the item, and both of its write
        // paths — front/alert.form.php:35 and ajax/createalert.php:33 — require UPDATE on this
        // right. The type filter only replays the canView() of the parent (Reminder, Problem,
        // Change), never the one of the alert, so the tab used to be announced to every user
        // of the central interface. UPDATE and not READ: the profile checkbox grants
        // CREATE + UPDATE + PURGE (Profile.php:143) and never READ, so a READ test would hide
        // the tab from the administrators it is meant for.
        if (Session::getCurrentInterface() == 'central'
            && Session::haveRight(self::$rightname, UPDATE)
            && in_array($item->getType(), self::getTypes())) {
            return self::createTabEntry(_n('Alert Dashboard', 'Alerts Dashboard', 2, 'mydashboard'));
        }
        return '';
    }

    /**
     * The reminder an alert broadcasts is a posted id, and a public alert shows its title to
     * anonymous visitors of the login page: check() on the alert row vets the plugin right,
     * not that value. Alerting is spreading the reminder, so it takes UPDATE on it, as the
     * reminder form of the core requires to change its visibility.
     */
    public function prepareInputForAdd($input)
    {
        $reminders_id = (int) ($input['reminders_id'] ?? 0);
        $reminder     = new \Reminder();
        if ($reminders_id <= 0 || !$reminder->can($reminders_id, UPDATE)) {
            Session::addMessageAfterRedirect(__('You are not allowed to do this action'), false, ERROR);
            return false;
        }

        return parent::prepareInputForAdd($input);
    }

    public function prepareInputForUpdate($input)
    {
        // An alert stays bound to the reminder it was created for
        unset($input['reminders_id']);

        $reminder = new \Reminder();
        if (!$reminder->can((int) ($this->fields['reminders_id'] ?? 0), UPDATE)) {
            Session::addMessageAfterRedirect(__('You are not allowed to do this action'), false, ERROR);
            return false;
        }

        return parent::prepareInputForUpdate($input);
    }

    /**
     * @return string
     */
    public static function getIcon()
    {
        return "ti ti-dashboard";
    }


    /**
     * @param bool $withtemplate
     *
     * @return array of allowed type
     */
    public static function getTypes($all = false)
    {
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
     * @param int $tabnum
     * @param int $withtemplate
     *
     * @return bool
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        // CommonGLPI::displayStandardTab() checks no right on a plugin tab, and
        // ajax/common.tabs.php reaches this method without ever calling getTabNameForItem():
        // the guard placed on the declaration is not replayed here.
        Session::checkRight(self::$rightname, UPDATE);

        $alert = new self();
        $itil_alert = new ItilAlert();
        switch ($item->getType()) {
            case "Reminder":
                $alert->showReminderForm($item);
                break;
            case "Problem":
            case "Change":
            case Release::class:
                $itil_alert->showForItem($item);
                break;
            default:
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
    public function getWidgetsForItem()
    {
        $widgets = [
            Menu::$SYSTEM => [
                $this->getType() . "1" => [
                    "title" => _n('Network alert', 'Network alerts', 2, 'mydashboard'),
                    "type" => Widget::$KPI,
                    "comment" => __("See network alert block", "mydashboard"),
                ],
                $this->getType() . "2" => [
                    "title" => _n('Scheduled maintenance', 'Scheduled maintenances', 2, 'mydashboard'),
                    "type" => Widget::$KPI,
                    "comment" => __("See scheduled maintenances information block", "mydashboard"),
                ],
                $this->getType() . "3" => [
                    "title" => _n('Information', 'Informations', 2, 'mydashboard'),
                    "type" => Widget::$KPI,
                    "comment" => __("See informations block", "mydashboard"),
                ],
                $this->getType() . "6" => [
                    "title" => __("GLPI Status", "mydashboard"),
                    "type" => Widget::$KPI,
                    "comment" => __("Check if GLPI have no problem", "mydashboard"),
                ],
                $this->getType() . "8" => [
                    "title" => __('Automatic actions in error', 'mydashboard'),
                    "type" => Widget::$KPI,
                    "comment" => __("Display automatic actions in error", "mydashboard"),
                ],
                $this->getType() . "9" => [
                    "title" => __("Not imported mails in collectors", "mydashboard"),
                    "type" => Widget::$TABLE,
                    "comment" => __("Display of mails which are not imported", "mydashboard"),
                ],
            ],
            Menu::$INVENTORY
            => [
                $this->getType() . "10" => [
                    "title" => __("Inventory stock alerts", "mydashboard"),
                    "type" => Widget::$KPI,
                    "comment" => __("Display alerts for inventory stocks", "mydashboard"),
                ],
                $this->getType() . "11" => [
                    "title" => __('Your equipments', 'mydashboard'),
                    "type" => Widget::$KPI,
                    "comment" => __("Display your equipments", "mydashboard"),
                ],
            ],
            Menu::$HELPDESK
            => [
                $this->getType() . "4" => [
                    "title" => __("Incidents alerts", "mydashboard"),
                    "type" => Widget::$KPI,
                    "comment" => __("Display alerts for incidents and problems", "mydashboard"),
                ],
                $this->getType() . "5" => [
                    "title" => __("SLA Incidents alerts", "mydashboard"),
                    "type" => Widget::$KPI,
                    "comment" => __("Display alerts for SLA of Incidents tickets", "mydashboard"),
                ],
                $this->getType() . "7" => [
                    "title" => __("User ticket alerts", "mydashboard"),
                    "type" => Widget::$TABLE,
                    "comment" => __("Display tickets where last modification is a user action", "mydashboard"),
                ],
                $this->getType() . "12" => [
                    "title" => __("SLA Requests alerts", "mydashboard"),
                    "type" => Widget::$KPI,
                    "comment" => __("Display alerts for SLA of Requests tickets", "mydashboard"),
                ],
                $this->getType() . "13" => [
                    "title" => __("Requests alerts", "mydashboard"),
                    "type" => Widget::$KPI,
                    "comment" => __("Display alerts for requests", "mydashboard"),
                ],
                //                $this->getType() . "33" => [
                //                    "title" => __("Number of opened tickets by group and by status", "mydashboard"),
                //                    "type" => Widget::$TABLE,
                //                    "comment" => ""
                //                ],
                $this->getType() . "SC32" => [
                    "title" => __("Global indicators", "mydashboard"),
                    "type" => Widget::$KPI,
                    "comment" => "",
                ],
                $this->getType() . "SC33" => [
                    "title" => __("Global indicators by week", "mydashboard"),
                    "type" => Widget::$KPI,
                    "comment" => "",
                ],
            ],
        ];

        // Widgets "6" (GLPI status), "8" (automatic actions in error) and "9" (mails the
        // collectors rejected) expose the internal state of the instance — services,
        // database replicas, LDAP, mail collectors, cron tasks, installed plugins and the
        // sender addresses of every rejected message — which the core binds to the "config"
        // right (CronTask::$rightname, NotImportedEmail::$rightname, status.php IP allow
        // list). Menu::$SYSTEM is only a display section and grants nothing, so the right is
        // checked here. Widget "9" was left out of this list while its two neighbours were
        // removed, so it kept being offered to any holder of plugin_mydashboard.
        if (!Session::haveRight(\Config::$rightname, READ)) {
            unset(
                $widgets[Menu::$SYSTEM][$this->getType() . "6"],
                $widgets[Menu::$SYSTEM][$this->getType() . "8"],
                $widgets[Menu::$SYSTEM][$this->getType() . "9"],
            );
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
    public static function countForAlerts($public, $type, $itilcategories_id = [])
    {
        global $DB;

        $now = date('Y-m-d H:i:s');


        //        $query = "SELECT COUNT(`glpi_reminders`.`id`) as cpt
        //                   FROM `glpi_reminders` "
        //            . Reminder::addVisibilityJoins()
        //            . " LEFT JOIN `glpi_plugin_mydashboard_alerts`"
        //            . " ON `glpi_reminders`.`id` = `glpi_plugin_mydashboard_alerts`.`reminders_id`"
        //            . " WHERE `glpi_plugin_mydashboard_alerts`.`type` = $type
        //                         $addwhere
        //                         $restrict_visibility ";
        //
        //        if ($public == 0) {
        //            $query .= "AND " . \Reminder::addVisibilityRestrict() . "";
        //        } else {
        //            $query .= "AND `glpi_plugin_mydashboard_alerts`.`is_public`";
        //        }

        $visibility_criteria = [
            [
                'OR' => [
                    ['glpi_reminders.begin_view_date' => null],
                    ['glpi_reminders.begin_view_date' => ['<', $now]],
                ],
            ],
            [
                'OR' => [
                    ['glpi_reminders.end_view_date' => null],
                    ['glpi_reminders.end_view_date' => ['>', $now]],
                ],
            ],
        ];

        $criteria = [
            'SELECT' => [
                // DISTINCT because the visibility joins applied below multiply the rows of a
                // reminder targeting several entities, groups or profiles; the four list
                // builders collapse them with a GROUPBY, which a bare COUNT cannot use
                // without returning one row per reminder instead of the total.
                'COUNT DISTINCT' => 'glpi_reminders.id AS cpt',
            ],
            'FROM' => 'glpi_reminders',
            'LEFT JOIN' => [
                'glpi_plugin_mydashboard_alerts' => [
                    'ON' => [
                        'glpi_plugin_mydashboard_alerts' => 'reminders_id',
                        'glpi_reminders' => 'id',
                    ],
                ],
            ],
            'WHERE' => [
                'glpi_plugin_mydashboard_alerts.type' => $type,
                $visibility_criteria,
            ],
        ];

        if ($public == 0) {
            // Both halves of the core visibility criteria, joins AND where: only the joins
            // used to be taken, and a LEFT JOIN on its own filters nothing, so the badge
            // counted every reminder of the instance instead of the ones targeting the
            // session. The $public == 1 branch below is the anonymous ticker and stays as
            // it is: it deliberately selects on is_public rather than on visibility.
            $criteria = Reminder::applyVisibilityCriteria($criteria);

            if (count($itilcategories_id) > 0) {
                $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_plugin_mydashboard_alerts.itilcategories_id' => $itilcategories_id];
            } else {
                $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_plugin_mydashboard_alerts.itilcategories_id' => 0];
            }
        } else {
            $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_plugin_mydashboard_alerts.is_public' => 1];
        }

        $nb = 0;
        $iterator = $DB->request($criteria);

        if (count($iterator) > 0) {
            foreach ($iterator as $ligne) {
                $nb = $ligne['cpt'];
            }
        }

        return $nb;
    }


    /**
     * @param       $widgetId
     *
     * @param array $opt
     *
     * @return MydashboardHtml|false
     * @throws \GlpitestSQLError
     */
    public function getWidgetContentForItem($widgetId, $opt = [])
    {
        global $CFG_GLPI, $DB;

        $isDebug = $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE;
        $dbu = new DbUtils();
        $preference = new Preference();
        if (Session::getLoginUserID() !== false
            && !$preference->getFromDB(Session::getLoginUserID())) {
            $preference->initPreferences(Session::getLoginUserID());
        }
        $preference->getFromDB(Session::getLoginUserID());
        $preferences = $preference->fields;

        $config = new Config();
        $config->getFromDB(1);

        switch ($widgetId) {
            case $this->getType() . "1":
                $widget = new MydashboardHtml();
                $widget->setWidgetHeaderType('danger');
                $widget->setWidgetHtmlContent($this->getAlertList(0));
                $widget->setWidgetTitle(Config::displayField($config, 'title_alerts_widget'));
                return $widget;
                break;

            case $this->getType() . "2":
                $widget = new MydashboardHtml();
                $datas = $this->getMaintenanceList();
                $widget->setWidgetHeaderType('warning');
                $widget->setWidgetHtmlContent(
                    $datas,
                );
                $widget->setWidgetTitle(Config::displayField($config, 'title_maintenances_widget'));
                return $widget;
                break;

            case $this->getType() . "3":
                $widget = new MydashboardHtml();
                $datas = $this->getInformationList();
                $widget->setWidgetHeaderType('info');
                $widget->setWidgetHtmlContent(
                    $datas,
                );
                $widget->setWidgetTitle(Config::displayField($config, 'title_informations_widget'));
                return $widget;
                break;

            case $this->getType() . "4":
                $widget = $this->displayTicketsAlertsWidgets(
                    'GlpiPlugin\Mydashboard\Alert4',
                    $widgetId,
                    $opt,
                    \Ticket::INCIDENT_TYPE,
                );
                return $widget;
                break;

            case $this->getType() . "5":
                $widget = $this->displaySLATicketsAlertsWidgets(
                    'GlpiPlugin\Mydashboard\Alert5',
                    $widgetId,
                    $opt,
                    \Ticket::INCIDENT_TYPE,
                );
                return $widget;
                break;

            case $this->getType() . "6":
                // The widget declaration is cached in the session and ajax/refreshWidget.php
                // serves any widget id that cache holds, so the "config" right is checked
                // again here and not only at the declaration.
                if (!Session::haveRight(\Config::$rightname, READ)) {
                    return false;
                }

                $widget = new MydashboardHtml();
                $url = $CFG_GLPI['url_base'] . "/status.php?format=json";
                // The widget always renders the global status table, which is read from the
                // "glpi" key below: a single service status does not carry that key. The
                // request parameter is therefore not forwarded to the checker at all, which
                // also removes an unvalidated user input from the service selection.
                $contents = StatusChecker::getServiceStatus(null, true);
                $table = self::handleShellcommandResult($contents['glpi']['status'], $url);


                if (is_array($contents) && count($contents) > 0) {
                    $modules = [];
                    foreach ($contents as $module => $content) {
                        if ($module != 'glpi') {
                            $modules[] = ['name' => $module, 'status' => $content['status']];
                        }
                    }
                    $table .= TemplateRenderer::getInstance()->render(
                        '@mydashboard/alert_modules_status.html.twig',
                        ['modules' => $modules],
                    );
                }
                $widget->setWidgetHtmlContent(
                    $table,
                );
                //            $widget->toggleWidgetRefresh();

                $widget->setWidgetTitle(__("GLPI Status", "mydashboard"));
                $widget->setWidgetComment(__("Check if GLPI have no problem", "mydashboard"));

                return $widget;
                break;

            case $this->getType() . "7":

                $link_ticket = Toolbox::getItemTypeFormURL("Ticket");

                $criteria = [
                    'SELECT' => [
                        'glpi_tickets.id AS tickets_id',
                        'glpi_tickets.status AS status',
                        'glpi_tickets.date_mod AS date_mod',
                    ],
                    'FROM' => 'glpi_tickets',
                    'WHERE' => [
                        'glpi_tickets.is_deleted' => 0,
                        'NOT' => ['glpi_tickets.status' => \Ticket::CLOSED],
                        'glpi_tickets.date_mod' => ['>', new QueryExpression($DB::quoteName("glpi_tickets.date"))],
                    ],
                    'ORDERBY' => 'glpi_tickets.date_mod DESC',
                ];
                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                    'glpi_tickets',
                );

                if (count($_SESSION['glpigroups'])) {
                    $criteria['LEFT JOIN'] = [
                        'glpi_groups_tickets' => [
                            'ON' => [
                                'glpi_tickets' => 'id',
                                'glpi_groups_tickets' => 'tickets_id',
                            ],
                        ],
                    ];
                    $search_assign = [
                        'glpi_groups_tickets.groups_id' => $_SESSION['glpigroups'],
                        'glpi_groups_tickets.type' => CommonITILActor::ASSIGN,
                    ];

                    $criteria['WHERE'] = $criteria['WHERE'] + $search_assign;
                }

                $iterator = $DB->request($criteria);

                $headers = [
                    __('ID and priority', 'mydashboard'),
                    _n('Requester', 'Requesters', 2),
                    __('Status'),
                    __('Last update'),
                    __('Assigned to'),
                    __('Action'),
                    __('ID'),
                    __('Priority'),
                    __('Category'),
                ];

                $datas = [];

                if (count($iterator) > 0) {
                    $i = 0;
                    foreach ($iterator as $data) {
                        $ticket = new \Ticket();
                        $ticket->getFromDB($data['tickets_id']);

                        $users_requesters = [];
                        $requester_names = [];
                        foreach ($ticket->getUsers(CommonITILActor::REQUESTER) as $u) {
                            $users_requesters[$u['users_id']] = $u['users_id'];
                            if ($u['users_id']) {
                                $requester_names[] = (string) getUserName($u['users_id']);
                            }
                        }
                        if (in_array($ticket->fields['users_id_lastupdater'], $users_requesters)) {
                            $itilfollowup = new ITILFollowup();
                            $followups = $itilfollowup->find([
                                'items_id' => $ticket->fields['id'],
                                'itemtype' => 'Ticket',
                            ], 'date DESC');

                            $ticketdocument = new Document();
                            $documents = $ticketdocument->find(
                                ['tickets_id' => $ticket->fields['id']],
                                ['date_mod DESC'],
                            );

                            if ((count($followups) > 0 && current($followups)['date'] >= $ticket->fields['date_mod'])
                                || (count($documents) > 0 && current(
                                    $documents,
                                )['date_mod'] >= $ticket->fields['date_mod'])) {
                                $bgcolor = $_SESSION["glpipriority_" . $ticket->fields["priority"]];
                                $textColor = "color:black!important;";
                                if ($bgcolor == '#000000') {
                                    $textColor = "color:white!important;";
                                }
                                $ticket_url = $link_ticket . "?id=" . (int) $data['tickets_id'];

                                $datas[$i]["tickets_id"] = self::getTicketCellHtml('ticket_name', [
                                    'bgcolor' => $bgcolor,
                                    'text_color' => $textColor,
                                    'url' => $ticket_url,
                                    'id' => $data['tickets_id'],
                                ]);

                                $datas[$i]["users_id"] = self::getTicketCellHtml('names', [
                                    'names' => $requester_names,
                                ]);

                                $datas[$i]["status"] = \Ticket::getStatus($data['status']);

                                $datas[$i]["date_mod"] = \Html::convDateTime($data['date_mod']);

                                $assigned_names = [];
                                foreach ($ticket->getUsers(CommonITILActor::ASSIGN) as $u) {
                                    if ($u['users_id']) {
                                        $assigned_names[] = (string) getUserName($u['users_id']);
                                    }
                                }
                                foreach ($ticket->getGroups(CommonITILActor::ASSIGN) as $u) {
                                    if ($u['groups_id']) {
                                        $assigned_names[] = (string) Dropdown::getDropdownName("glpi_groups", $u['groups_id']);
                                    }
                                }
                                $datas[$i]["techs_id"] = self::getTicketCellHtml('names', [
                                    'names' => $assigned_names,
                                ]);

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


                                $datas[$i]["id"] = self::getTicketCellHtml('ticket_id', [
                                    'url' => $ticket_url,
                                    'id' => $data['tickets_id'],
                                ]);

                                $datas[$i]["priority"] = self::getTicketCellHtml('priority', [
                                    'bgcolor' => $bgcolor,
                                    'text_color' => $textColor,
                                    'priority' => $ticket->fields["priority"],
                                    'priority_name' => \Ticket::getPriorityName($ticket->fields["priority"]),
                                ]);

                                // Categories
                                $config = new Config();
                                $config->getFromDB(1);
                                $itilCategory = new ITILCategory();
                                if ($itilCategory->getFromDB($ticket->fields["itilcategories_id"])) {
                                    $haystack = $itilCategory->getField('completename');
                                    $needle = '>';
                                    $offset = 0;
                                    $allpos = [];

                                    while (($pos = strpos($haystack, $needle, $offset)) !== false) {
                                        $offset = $pos + 1;
                                        $allpos[] = $pos;
                                    }

                                    if (isset($allpos[$config->getField('levelCat') - 1])) {
                                        $pos = $allpos[$config->getField('levelCat') - 1];
                                    } else {
                                        $pos = strlen($haystack);
                                    }
                                    // The completename of a category is free text, stored
                                    // unescaped: the cell template escapes it.
                                    $datas[$i]["category"] = self::getTicketCellHtml('category', [
                                        'category' => substr($haystack, 0, $pos),
                                    ]);
                                } else {
                                    $datas[$i]["category"] = "";
                                }


                                $i++;
                            }
                        }
                    }
                }
                $widget = new Datatable();
                $widget->setTabDatas($datas);
                if (count($iterator) > 0) {
                    $widget->setOption("bSort", [3, 'desc']);
                }
                $widget->setOption("bDate", ["DH"]);

                $widget->setWidgetHeaderType('warning');

                $title = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetComment($comment);
                $widget->setWidgetTitle((($isDebug) ? "7 " : "") . $title);

                $widget->toggleWidgetRefresh();

                return $widget;

            case $this->getType() . "8":
                // Same reason as case "6": the cached declaration is not an authorization,
                // and glpi_crontasks is bound to the "config" right in the core.
                if (!Session::haveRight(\Config::$rightname, READ)) {
                    return false;
                }

                $criteria = [
                    'SELECT' => '*',
                    'FROM' => 'glpi_crontasks',
                    'WHERE' => [
                        'state' => CronTask::STATE_RUNNING,
                        'OR' => [
                            [
                                new QueryExpression(
                                    "UNIX_TIMESTAMP(" . $DB->quoteName("lastrun") . ") + 2 * " . $DB->quoteName(
                                        "frequency",
                                    ) . " < UNIX_TIMESTAMP(NOW())",
                                ),
                            ],
                            [
                                new QueryExpression(
                                    "UNIX_TIMESTAMP(" . $DB->quoteName(
                                        "lastrun",
                                    ) . ") + 2 * " . HOUR_TIMESTAMP . " < UNIX_TIMESTAMP(NOW())",
                                ),
                            ],
                        ],
                    ],

                ];

                $iterator = $DB->request($criteria);

                $headers = [
                    __('Last run'),
                    __('Name'),
                    __('Status'),
                ];

                $datas = [];
                $i = 0;
                if (count($iterator)) {
                    foreach ($iterator as $data) {
                        $datas[$i]["lastrun"] = \Html::convDateTime($data['lastrun']);

                        // The cron task name lands in an HTML column of the datatable, and a
                        // plugin is free to register a task under any name: escape it here, as
                        // the other columns of this file already do.
                        $name = htmlspecialchars((string) $data["name"], ENT_QUOTES, 'UTF-8');
                        if ($isplug = isPluginItemType($data["itemtype"])) {
                            $name = sprintf(
                                __('%1$s - %2$s'),
                                htmlspecialchars((string) $isplug["plugin"], ENT_QUOTES, 'UTF-8'),
                                $name,
                            );
                        }

                        $datas[$i]["name"] = $name;

                        $datas[$i]["state"] = CronTask::getStateName($data["state"]);

                        $i++;
                    }
                }
                $widget = new Datatable();
                $widget->setWidgetHeaderType('danger');
                $widget->setTabDatas($datas);
                $widget->setOption("bDate", ["DH"]);
                if (count($iterator)) {
                    $widget->setOption("bSort", [1, 'desc']);
                }

                $widget = new Datatable();
                $title = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetTitle((($isDebug) ? "8 " : "") . $title);
                $widget->setWidgetComment($comment);

                $widget->setTabNames($headers);
                $widget->setTabDatas($datas);

                $widget->setOption("bPaginate", false);
                $widget->setOption("bFilter", false);
                $widget->setOption("bInfo", false);

                $widget->toggleWidgetRefresh();

                return $widget;

            case $this->getType() . "9":
                // Same reason as case "6" and "8": the cached declaration is not an
                // authorization, and glpi_notimportedemails is bound to the "config" right in
                // the core (NotImportedEmail::$rightname). Without this, holding
                // plugin_mydashboard READ was enough to ask ajax/refreshWidget.php for this
                // widget id and read every rejected mail of every entity.
                if (!Session::haveRight(\Config::$rightname, READ)) {
                    return false;
                }

                $criteria = [
                    'SELECT' => ['date', 'from', 'reason', 'mailcollectors_id'],
                    'FROM' => 'glpi_notimportedemails',
                    // No entity restriction: the table has no entities_id column (it is global
                    // in the core too, guarded by the "config" right checked above).
                    'ORDERBY' => 'date ASC',
                ];

                $iterator = $DB->request($criteria);

                $headers = [
                    __('Date'),
                    __('From email header'),
                    __('Reason of rejection'),
                    __('Mails receiver'),
                ];

                $datas = [];
                $i = 0;
                if (count($iterator) > 0) {
                    foreach ($iterator as $data) {
                        $datas[$i]["date"] = \Html::convDateTime($data['date']);

                        // Datatable writes every cell as HTML, and this column is the From
                        // header of the collected message: it is written by whoever sent the
                        // mail, typically someone outside the organisation, and read back by
                        // the high privilege profiles that follow the collectors.
                        $datas[$i]["from"] = htmlspecialchars((string) $data['from'], ENT_QUOTES, 'UTF-8');

                        $datas[$i]["reason"] = NotImportedEmail::getReason($data['reason']);

                        $mail = new MailCollector();
                        $mail->getFromDB($data['mailcollectors_id']);
                        // Same sink: getName() returns the stored name, unescaped since GLPI 10.
                        $datas[$i]["mailcollectors_id"] = htmlspecialchars(
                            (string) $mail->getName(),
                            ENT_QUOTES,
                            'UTF-8',
                        );

                        $i++;
                    }
                }

                $widget = new Datatable();

                $widget->setTabNames($headers);
                $widget->setTabDatas($datas);
                $widget->toggleWidgetRefresh();
                //                $widget->setOption("bDate", ["DH"]);
                //                if ($nb) {
                //                    $widget->setOption("bSort", [0, 'desc']);
                //                }
                //                $widget->setWidgetHeaderType('danger');
                $title = $this->getTitleForWidget($widgetId);
                //                $comment = $this->getCommentForWidget($widgetId);
                //                $widget->setWidgetComment($comment);
                $widget->setWidgetTitle((($isDebug) ? "9 " : "") . $title);


                return $widget;
                break;

            case $this->getType() . "10":

                $widget = new MydashboardHtml();

                $setuplink = StockWidget::getSearchURL(true);
                $criterias = ["locations_id"];
                $params = [
                    "preferences" => $preferences,
                    "criterias" => $criterias,
                    "opt" => $opt,
                ];

                $default = Criteria::manageCriterias($params);

                $location_criteria = $opt['locations_id'] ?? $default['locations_id'];

                $params = [
                    "widgetId" => $widgetId,
                    "name" => 'GlpiPlugin\Mydashboard\Alert10',
                    "onsubmit" => false,
                    "opt" => $opt,
                    "default" => $default,
                    "criterias" => $criterias,
                    "setup" => $setuplink,
                    "export" => false,
                    "canvas" => false,
                    "nb" => 1,
                ];
                $table = Helper::getGraphHeader($params);

                $stockwidget = new StockWidget();
                $stocks = $stockwidget->find();
                $stock_tiles = [];
                $types = [];
                $states = [];
                if (count($stocks) > 0) {
                    $nb = 0;
                    foreach ($stocks as $data) {
                        $nb++;
                        $alarm = $data['alarm_threshold'];
                        $stock = 0;
                        $color = "olivedrab";
                        $itemtype = $data['itemtype'];
                        if ($item = getItemForItemtype($itemtype)) {
                            $itemtable = getTableForItemType($itemtype);
                            $typefield = $dbu->getForeignKeyFieldForTable(
                                $dbu->getTableForItemType($itemtype . "Type"),
                            );
                            if ($data["types"] != null) {
                                $types = json_decode($data["types"], true);
                            }
                            if ($data["states"] != null) {
                                $states = json_decode($data["states"], true);
                            }

                            //                            $q2 = "SELECT DISTINCT COUNT(`" . $itemtable . "`.`id`) AS nb
                            //                        FROM `" . $itemtable . "`
                            //                        WHERE `" . $itemtable . "`.`is_deleted` = '0' AND `" . $itemtable . "`.`is_template` = '0' ";
                            //                            $q2 .= $dbu->getEntitiesRestrictRequest("AND", $itemtype::getTable());
                            //                            if (is_array($states) && count($states) > 0) {
                            //                                $q2 .= " AND `" . $itemtable . "`.`states_id` IN('" . implode("', '", $states) . "') ";
                            //                            }
                            //                            if (is_array($types) && count($types) > 0) {
                            //                                $q2 .= "AND `" . $itemtable . "`.`" . $typefield . "` IN('" . implode(
                            //                                        "', '",
                            //                                        $types
                            //                                    ) . "')";
                            //                            }
                            //                            if (isset($opt['locations_id']) && ($opt['locations_id'] != 0)) {
                            //                                $q2 .= " AND `" . $itemtable . "`.`locations_id` = '" . $location_criteria . "' ";
                            //                            }

                            $criteria2 = [
                                'SELECT' => [
                                    'COUNT' => $itemtable . '.id AS nb',
                                ],
                                'DISTINCT' => true,
                                'FROM' => $itemtable,
                                'WHERE' => [
                                    $itemtable . '.is_deleted' => 0,
                                    $itemtable . '.is_template' => 0,
                                ],
                            ];

                            if (is_array($states) && count($states) > 0) {
                                $criteria2['WHERE'] = $criteria2['WHERE'] + [$itemtable . '.states_id' => $states];
                            }

                            if (is_array($types) && count($types) > 0) {
                                $criteria2['WHERE'] = $criteria2['WHERE'] + [$itemtable . '.' . $typefield => $types];
                            }

                            if (isset($opt['locations_id']) && ($opt['locations_id'] != 0)) {
                                $criteria2['WHERE'] = $criteria2['WHERE'] + [$itemtable . '.locations_id' => $location_criteria];
                            }

                            $criteria2['WHERE'] = $criteria2['WHERE'] + getEntitiesRestrictCriteria(
                                $itemtype::getTable(),
                            );

                            $iterator2 = $DB->request($criteria2);
                            $nb2 = count($iterator2);
                            if ($nb2) {
                                foreach ($iterator2 as $data2) {
                                    $stock = $data2['nb'];
                                }
                            }
                            if ($stock < $alarm) {
                                $color = "indianred";
                            }

                            //////////////////////////////////////////
                            $search = [];
                            $search['reset'] = 'reset';
                            $search['criteria'][0]['field'] = "view";
                            $search['criteria'][0]['searchtype'] = 'contains';
                            $search['criteria'][0]['value'] = "^";
                            $search['criteria'][0]['link'] = 'AND';
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
                                    $search['criteria'][1]['criteria'][$nbs]['value'] = $type;
                                    $search['criteria'][1]['criteria'][$nbs]['link'] = 'OR';
                                }
                            }
                            if (is_array($states) && count($states) > 0) {
                                $nbs = 1;
                                foreach ($states as $state) {
                                    $nbs++;
                                    $search['criteria'][2]['criteria'][$nbs]['field'] = 31; // type
                                    $search['criteria'][2]['criteria'][$nbs]['searchtype'] = 'equals';
                                    $search['criteria'][2]['criteria'][$nbs]['value'] = $state;
                                    $search['criteria'][2]['criteria'][$nbs]['link'] = 'OR';
                                }
                            }
                            if (isset($opt['locations_id']) && ($opt['locations_id'] != 0)) {
                                $search['criteria'][3]['field'] = "3";
                                $search['criteria'][3]['searchtype'] = 'equals';
                                $search['criteria'][3]['value'] = $opt['locations_id'];
                                $search['criteria'][3]['link'] = 'AND';
                            }
                            $form = $itemtype::getSearchURL(false);
                            $link = $CFG_GLPI["root_doc"] . $form . '?is_deleted=0&'
                                . Toolbox::append_params($search, "&");

                            $stock_tiles[] = [
                                'color' => $color,
                                'url' => $link,
                                'icon' => $data['icon'],
                                'id' => 'stock_' . $nb,
                                // Stock-widget names are admin-set DB values; the template
                                // escapes them in both the title attribute and the text.
                                'name' => $data['name'],
                                // Counted up by public/scripts/alert-widgets.js
                                'value' => (int) $stock,
                            ];
                        }
                    }
                }

                $table .= TemplateRenderer::getInstance()->render('@mydashboard/alert_stock_tiles.html.twig', [
                    'tiles' => $stock_tiles,
                ]);
                $table .= Helper::getGraphFooter($params);
                $widget->setWidgetHtmlContent(
                    $table,
                );

                $widget->setWidgetHeaderType('danger');

                $title = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetComment($comment);
                $widget->setWidgetTitle((($isDebug) ? "10 " : "") . $title);
                $widget->toggleWidgetRefresh();

                return $widget;


            case $this->getType() . "11":
                $widget = new MydashboardHtml();
                $class = "bt-col-md-12";
                $display = Widget::getWidgetMydashboardEquipments($class, false);
                $widget->setWidgetHtmlContent($display);

                $title = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetComment($comment);
                $widget->setWidgetTitle((($isDebug) ? "11 " : "") . $title);
                $widget->toggleWidgetRefresh();
                return $widget;


            case $this->getType() . "12":
                $widget = $this->displaySLATicketsAlertsWidgets(
                    'GlpiPlugin\Mydashboard\Alert12',
                    $widgetId,
                    $opt,
                    \Ticket::DEMAND_TYPE,
                );
                return $widget;


            case $this->getType() . "13":

                $widget = $this->displayTicketsAlertsWidgets(
                    'GlpiPlugin\Mydashboard\Alert13',
                    $widgetId,
                    $opt,
                    \Ticket::DEMAND_TYPE,
                );
                return $widget;


            case $this->getType() . "SC32":
                $widget = new MydashboardHtml();
                $title = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetComment($comment);
                $widget->setWidgetTitle((($isDebug) ? "SC32 " : "") . $title);
                $widget->toggleWidgetRefresh();
                $graph = self::displayIndicator($widgetId, "all", $opt, true);
                $widget->setWidgetHtmlContent(
                    $graph,
                );
                return $widget;
            case $this->getType() . "SC33":
                $widget = new MydashboardHtml();

                $title = $this->getTitleForWidget($widgetId);
                $comment = $this->getCommentForWidget($widgetId);
                $widget->setWidgetComment($comment);
                $widget->setWidgetTitle((($isDebug) ? "SC33 " : "") . $title);
                $widget->toggleWidgetRefresh();

                $graph = self::displayIndicator($widgetId, "week", $opt, true);
                $widget->setWidgetHtmlContent($graph);
                return $widget;
        }
    }


    /**
     * @param $widgetId
     * @param $opt
     * @param $type
     *
     * @return PluginMydashboardHtml
     * @throws \GlpitestSQLError
     */
    public function displayTicketsAlertsWidgets($name, $widgetId, $opt, $type)
    {
        global $CFG_GLPI, $DB;

        $widget = new MydashboardHtml();
        $dbu = new DbUtils();
        $preference = new Preference();
        if (Session::getLoginUserID() !== false
            && !$preference->getFromDB(Session::getLoginUserID())) {
            $preference->initPreferences(Session::getLoginUserID());
        }
        $preference->getFromDB(Session::getLoginUserID());
        $preferences = $preference->fields;

        $colorstats1 = "#CCC";
        $colorstats2 = "#CCC";
        $colorstats3 = "#CCC";
        $colorstats4 = "#CCC";


        $criterias = ['technicians_groups_id'];

        $params = [
            "preferences" => $preferences,
            "criterias" => $criterias,
            "opt" => $opt,
        ];
        $default = Criteria::manageCriterias($params);

        if (!isset($opt['technicians_groups_id'])
            || (is_array($opt['technicians_groups_id']) && count($opt['technicians_groups_id']) == 0)) {
            $technicians_groups_id = Helper::getGroup($preferences['prefered_group'], $opt);
        } else {
            $technicians_groups_id = $opt['technicians_groups_id'];
        }

        $params = [
            "widgetId" => $widgetId,
            "name" => $name,
            "onsubmit" => true,
            "opt" => $opt,
            "default" => $default,
            "criterias" => $criterias,
            "export" => false,
            "canvas" => false,
            "nb" => 1,
        ];
        $widget->setWidgetHeader(Helper::getGraphHeader($params));

        $is_deleted = ['glpi_tickets.is_deleted' => 0];

        //        $q1 = "SELECT DISTINCT COUNT(`glpi_tickets`.`id`) AS nb
        //                        FROM `glpi_tickets`
        //                        $left
        //                        WHERE `glpi_tickets`.`is_deleted` = '0' ";
        //        $q1 .= $dbu->getEntitiesRestrictRequest("AND", \Ticket::getTable())
        //            . " AND `glpi_tickets`.`status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")
        //            AND `glpi_tickets`.`priority` > 4 AND `glpi_tickets`.`type` = '" . $type . "' AND $search_assign";
        //
        $criteria1 = [
            'SELECT' => [
                'COUNT' => 'glpi_tickets.id AS nb',
            ],
            'DISTINCT' => true,
            'FROM' => 'glpi_tickets',
            'WHERE' => [
                $is_deleted,
                'glpi_tickets.status' => \Ticket::getNotSolvedStatusArray(),
                'glpi_tickets.priority' => ['>', 4],
                'glpi_tickets.type' => $type,
            ],
        ];

        if (is_array($technicians_groups_id) > 0
            && count($technicians_groups_id) > 0) {

            if (!isset($criteria1['LEFT JOIN'])) {
                $criteria1['LEFT JOIN'] = [];
            }
            $criteria1['LEFT JOIN'] = $criteria1['LEFT JOIN'] + [
                'glpi_groups_tickets' => [
                    'ON' => [
                        'glpi_tickets' => 'id',
                        'glpi_groups_tickets' => 'tickets_id',
                        [
                            'AND' => [
                                'glpi_groups_tickets.type' => CommonITILActor::ASSIGN,
                            ],
                        ],
                    ],
                ],
            ];
            $criteria1['WHERE'] = $criteria1['WHERE'] + ['glpi_groups_tickets.groups_id' => $technicians_groups_id];
        }
        $criteria1['WHERE'] = $criteria1['WHERE'] + getEntitiesRestrictCriteria(
            'glpi_tickets',
        );

        $iterator1 = $DB->request($criteria1);
        $stats_tickets1 = 0;
        $nb1 = count($iterator1);
        if ($nb1) {
            foreach ($iterator1 as $data1) {
                $stats_tickets1 = $data1['nb'];
            }
        }
        if ($stats_tickets1 > 0) {
            $colorstats1 = "indianred";
        }

        /*Stats2*/
        if ($type == \Ticket::INCIDENT_TYPE) {
            $is_deleted = ['glpi_problems.is_deleted' => 0];

            //            $search_assign = "1=1";
            //            $left = "";
            //
            //            $q2 = "SELECT DISTINCT COUNT(`glpi_problems`.`id`) AS nb
            //                        FROM `glpi_problems`
            //                        $left
            //                        WHERE `glpi_problems`.`is_deleted` = '0' ";
            //            $q2 .= $dbu->getEntitiesRestrictRequest("AND", \Problem::getTable())
            //                . " AND `glpi_problems`.`status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")
            //            AND `glpi_problems`.`priority` > 4 AND $search_assign";


            $criteria2 = [
                'SELECT' => [
                    'COUNT' => 'glpi_problems.id AS nb',
                ],
                'DISTINCT' => true,
                'FROM' => 'glpi_problems',
                'WHERE' => [
                    $is_deleted,
                    'glpi_problems.status' => \Problem::getNotSolvedStatusArray(),
                    'glpi_problems.priority' => ['>', 4],
                ],
            ];

            $criteria2['WHERE'] = $criteria2['WHERE'] + getEntitiesRestrictCriteria(
                'glpi_problems',
            );

            $iterator2 = $DB->request($criteria2);
            $stats_tickets2 = 0;
            $nb2 = count($iterator2);
            if ($nb2) {
                foreach ($iterator2 as $data2) {
                    $stats_tickets2 = $data2['nb'];
                }
            }
            if ($stats_tickets2 > 0) {
                $colorstats2 = "indianred";
            }
        }
        /*Stats3*/

        $is_deleted = ['glpi_tickets.is_deleted' => 0];

        //        $q3 = "SELECT DISTINCT COUNT(`glpi_tickets`.`id`) AS nb
        //                        FROM `glpi_tickets`
        //                        $left
        //                        WHERE `glpi_tickets`.`is_deleted` = '0' ";
        //        $q3 .= $dbu->getEntitiesRestrictRequest("AND", \Ticket::getTable())
        //            . " AND `glpi_tickets`.`status` IN (" . CommonITILObject::INCOMING . ")
        //            AND `glpi_tickets`.`type` = '" . $type . "' ";

        $criteria3 = [
            'SELECT' => [
                'COUNT' => 'glpi_tickets.id AS nb',
            ],
            'DISTINCT' => true,
            'FROM' => 'glpi_tickets',
            'WHERE' => [
                $is_deleted,
                'glpi_tickets.status' => CommonITILObject::INCOMING,
                'glpi_tickets.type' => $type,
            ],
        ];

        $criteria3['WHERE'] = $criteria3['WHERE'] + getEntitiesRestrictCriteria(
            'glpi_tickets',
        );

        $iterator3 = $DB->request($criteria3);
        $stats_tickets3 = 0;
        $nb3 = count($iterator3);
        if ($nb3) {
            foreach ($iterator3 as $data3) {
                $stats_tickets3 = $data3['nb'];
            }
        }
        if ($stats_tickets3 > 0) {
            $colorstats3 = "indianred";
        }

        /*Stats4*/
        $is_deleted = ['glpi_tickets.is_deleted' => 0];

        //        if (is_array($technicians_groups_id) > 0
        //            && count($technicians_groups_id) > 0) {
        //            $left = "LEFT JOIN `glpi_groups_tickets`
        //                  ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id`) ";
        //            $search_assign = " (`glpi_groups_tickets`.`groups_id` IN (" . implode(",", $technicians_groups_id) . ")
        //                                    AND `glpi_groups_tickets`.`type` = '" . CommonITILActor::ASSIGN . "')";
        //        }
        //
        //        $search_assign .= " AND `glpi_tickets`.`id` NOT IN (SELECT `tickets_id` FROM `glpi_tickets_users` WHERE `glpi_tickets_users`.`type` = '" . CommonITILActor::ASSIGN . "') ";
        //
        //        $q4 = "SELECT DISTINCT COUNT(`glpi_tickets`.`id`) AS nb
        //                        FROM `glpi_tickets`
        //                        $left
        //                        WHERE $search_assign  AND `glpi_tickets`.`type` = '" . $type . "' AND `glpi_tickets`.`is_deleted` = 0 ";
        //        $q4 .= $dbu->getEntitiesRestrictRequest("AND", \Ticket::getTable())
        //            . " AND `glpi_tickets`.`status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ") ";

        $criteria_init = [
            'SELECT' => [
                'tickets_id',
            ],
            'FROM' => 'glpi_tickets_users',
            'WHERE' => [
                'type' =>  CommonITILActor::ASSIGN,
            ],
        ];

        $criteria4 = [
            'SELECT' => [
                'COUNT' => 'glpi_tickets.id AS nb',
            ],
            'DISTINCT' => true,
            'FROM' => 'glpi_tickets',
            'WHERE' => [
                $is_deleted,
                ['glpi_tickets.id' => ['NOT IN', new QuerySubQuery($criteria_init)]],
                'glpi_tickets.status' => \Ticket::getNotSolvedStatusArray(),
                'glpi_tickets.type' => $type,
            ],
        ];

        if (is_array($technicians_groups_id) > 0
            && count($technicians_groups_id) > 0) {
            if (!isset($criteria4['LEFT JOIN'])) {
                $criteria4['LEFT JOIN'] = [];
            }
            $criteria4['LEFT JOIN'] = $criteria4['LEFT JOIN'] + [
                'glpi_groups_tickets' => [
                    'ON' => [
                        'glpi_tickets' => 'id',
                        'glpi_groups_tickets' => 'tickets_id',
                        [
                            'AND' => [
                                'glpi_groups_tickets.type' => CommonITILActor::ASSIGN,
                            ],
                        ],
                    ],
                ],
            ];
            $criteria4['WHERE'] = $criteria4['WHERE'] + ['glpi_groups_tickets.groups_id' => $technicians_groups_id];
        }
        $criteria4['WHERE'] = $criteria4['WHERE'] + getEntitiesRestrictCriteria(
            'glpi_tickets',
        );

        $iterator4 = $DB->request($criteria4);
        $stats_tickets4 = 0;
        $nb4 = count($iterator4);
        if ($nb4) {
            foreach ($iterator4 as $data4) {
                $stats_tickets4 = $data4['nb'];
            }
        }
        if ($stats_tickets4 > 0) {
            $colorstats4 = "indianred";
        }

        $is_incident = $type == \Ticket::INCIDENT_TYPE;

        $tiles = [];

        //////////////////////////////////////////
        //new tickets
        $stats3link = null;
        if ($stats_tickets3 > 0) {
            // Reset criterias
            $options3['reset'][] = 'reset';

            $options3['criteria'][] = [
                'field' => 12,//status
                'searchtype' => 'equals',
                'value' => 1,
                'link' => 'AND',
            ];

            $options3['criteria'][] = [
                'field' => 14, // type
                'searchtype' => 'equals',
                'value' => $type,
                'link' => 'AND',
            ];

            $stats3link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&'
                . Toolbox::append_params($options3, "&");
        }

        $label3 = $is_incident
            ? __('New incidents', 'mydashboard')
            : __('New requests', 'mydashboard');
        $tiles[] = [
            'color' => $colorstats3,
            'url' => $stats3link,
            'title' => $label3,
            'icon_class' => 'ti ti-alert-circle fa-3x fa-border',
            'icon_style' => 'font-size:34px',
            'heading_style' => 'margin-top: 10px;',
            'id' => 'stats_' . $type . '_tickets3',
            'label' => $label3,
            'value' => $stats_tickets3,
        ];

        //////////////////////////////////////////
        //tickets without tech
        $stats4link = null;
        if ($stats_tickets4 > 0) {
            // Reset criterias
            $options4['reset'][] = 'reset';

            $options4['criteria'][] = [
                'field' => 12,//status
                'searchtype' => 'equals',
                'value' => 'notold',
                'link' => 'AND',
            ];

            $options4['criteria'][] = [
                'field' => 5, // tech
                'searchtype' => 'contains',
                'value' => '^$',
                'link' => 'AND',
            ];

            $options4['criteria'][] = [
                'field' => 14, // type
                'searchtype' => 'equals',
                'value' => $type,
                'link' => 'AND',
            ];

            if (is_array($technicians_groups_id)
                && count($technicians_groups_id) > 0) {
                $nb = 0;
                foreach ($technicians_groups_id as $group) {
                    $criterias['criteria'][$nb] = [
                        'field' => 8, // groups_id_assign
                        'searchtype' => 'equals',
                        'value' => $group,
                        'link' => (($nb == 0) ? 'AND' : 'OR'),
                    ];
                    $nb++;
                }
                $options4['criteria'][] = $criterias;
            }

            $stats4link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&'
                . Toolbox::append_params($options4, "&");
        }

        $label4 = $is_incident
            ? __('Opened incidents without assigned technicians', 'mydashboard')
            : __('Opened requests without assigned technicians', 'mydashboard');
        $tiles[] = [
            'color' => $colorstats4,
            'url' => $stats4link,
            'title' => $label4,
            'icon_class' => 'ti ti-user-x fa-3x fa-border',
            'icon_style' => 'font-size:34px',
            'heading_style' => 'margin-top: 10px;',
            'id' => 'stats_' . $type . '_tickets4',
            'label' => $label4,
            'value' => $stats_tickets4,
        ];

        //////////////////////////////////////////
        //Tickets with very high or major priority
        $stats1link = null;
        if ($stats_tickets1 > 0) {
            // Reset criterias
            $options1['reset'][] = 'reset';

            $options1['criteria'][] = [
                'field' => 12,//status
                'searchtype' => 'equals',
                'value' => 'notold',
                'link' => 'AND',
            ];

            $options1['criteria'][] = [
                'field' => 3, // priority
                'searchtype' => 'equals',
                'value' => -5,
                'link' => 'AND',
            ];

            $options1['criteria'][] = [
                'field' => 14, // type
                'searchtype' => 'equals',
                'value' => $type,
                'link' => 'AND',
            ];

            if (is_array($technicians_groups_id)
                && count($technicians_groups_id) > 0) {
                $nb = 0;
                foreach ($technicians_groups_id as $group) {
                    $criterias['criteria'][$nb] = [
                        'field' => 8, // groups_id_assign
                        'searchtype' => 'equals',
                        'value' => $group,
                        'link' => (($nb == 0) ? 'AND' : 'OR'),
                    ];
                    $nb++;
                }
                $options1['criteria'][] = $criterias;
            }

            $stats1link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&'
                . Toolbox::append_params($options1, "&");
        }

        $label1 = $is_incident
            ? __('Incidents with very high or major priority', 'mydashboard')
            : __('Requests with very high or major priority', 'mydashboard');
        $tiles[] = [
            'color' => $colorstats1,
            'url' => $stats1link,
            'title' => $label1,
            'icon_class' => 'ti ti-alert-triangle fa-border',
            'icon_style' => 'font-size:3em;',
            'heading_style' => 'font-size:34px;margin-top: 10px;',
            'id' => 'stats_' . $type . '_tickets1',
            'label' => $label1,
            'value' => $stats_tickets1,
        ];

        //////////////////////////////////////////
        //Problem with high priority
        if ($is_incident) {
            $stats2link = null;
            if ($stats_tickets2 > 0) {
                // Reset criterias
                $options2['reset'][] = 'reset';

                $options2['criteria'][] = [
                    'field' => 12,//status
                    'searchtype' => 'equals',
                    'value' => 'notold',
                    'link' => 'AND',
                ];

                $options2['criteria'][] = [
                    'field' => 3, // priority
                    'searchtype' => 'equals',
                    'value' => -5,
                    'link' => 'AND',
                ];

                $stats2link = $CFG_GLPI["root_doc"] . '/front/problem.php?is_deleted=0&'
                    . Toolbox::append_params($options2, "&");
            }

            $label2 = __('Problems with very high or major priority', 'mydashboard');
            $tiles[] = [
                'color' => $colorstats2,
                'url' => $stats2link,
                'title' => $label2,
                'icon_class' => 'ti ti-bug fa-3x fa-border',
                'icon_style' => 'font-size:34px',
                'heading_style' => 'margin-top: 10px;',
                'id' => 'stats_' . $type . '_tickets2',
                'label' => $label2,
                'value' => $stats_tickets2,
            ];
        }

        $widget->setWidgetHtmlContent(self::getStatsTilesHtml($tiles));
        $widget->toggleWidgetRefresh();
        $widget->setWidgetHeaderType('danger');
        if ($type == \Ticket::INCIDENT_TYPE) {
            $widget->setWidgetTitle(__("Incidents alerts", "mydashboard"));
            $widget->setWidgetComment(__("Display alerts for incidents and problems", "mydashboard"));
        } else {
            $widget->setWidgetTitle(__("Requests alerts", "mydashboard"));
            $widget->setWidgetComment(__("Display alerts for requests", "mydashboard"));
        }
        return $widget;
    }


    public function displaySLATicketsAlertsWidgets($name, $widgetId, $opt, $type)
    {
        global $CFG_GLPI, $DB;

        $widget = new MydashboardHtml();
        $dbu = new DbUtils();

        $colorstats2 = "#CCC";
        $colorstats3 = "#CCC";
        $colorstats4 = "#CCC";
        $colorstats5 = "#CCC";
        $preference = new Preference();
        if (Session::getLoginUserID() !== false
            && !$preference->getFromDB(Session::getLoginUserID())) {
            $preference->initPreferences(Session::getLoginUserID());
        }
        $preference->getFromDB(Session::getLoginUserID());
        $preferences = $preference->fields;

        /*Stats2*/

        $stats2 = 0;

        $criterias = ['technicians_groups_id'];

        $params = [
            "preferences" => $preferences,
            "criterias" => $criterias,
            "opt" => $opt,
        ];

        $default = Criteria::manageCriterias($params);

        if (!isset($opt['technicians_groups_id']) || (is_array($opt['technicians_groups_id']) && count(
            $opt['technicians_groups_id'],
        ) == 0)) {
            $technicians_groups_id = Helper::getGroup($preferences['prefered_group'], $opt);
        } else {
            $technicians_groups_id = $opt['technicians_groups_id'];
        }

        $params = [
            "widgetId" => $widgetId,
            "name" => $name,
            "onsubmit" => true,
            "opt" => $opt,
            "default" => $default,
            "criterias" => $criterias,
            "export" => false,
            "canvas" => false,
            "nb" => 1,
        ];
        $widget->setWidgetHeader(Helper::getGraphHeader($params));

        $is_deleted = ['glpi_tickets.is_deleted' => 0];

        //        $q2 = "SELECT DISTINCT COUNT(`glpi_tickets`.`id`) AS nb
        //                           FROM `glpi_tickets`
        //                           $left
        //                           WHERE `glpi_tickets`.`is_deleted` = '0' ";
        //        $q2 .= $dbu->getEntitiesRestrictRequest("AND", \Ticket::getTable())
        //            . " AND $search_assign AND `glpi_tickets`.`status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")
        //                         AND `glpi_tickets`.`type` = '" . $type . "'
        //                         AND (`glpi_tickets`.`takeintoaccount_delay_stat` = '0'
        //                         AND `glpi_tickets`.`time_to_own` > NOW())";

        $criteria2 = [
            'SELECT' => [
                'COUNT' => 'glpi_tickets.id AS nb',
            ],
            'DISTINCT' => true,
            'FROM' => 'glpi_tickets',
            'WHERE' => [
                $is_deleted,
                'glpi_tickets.status' => \Ticket::getNotSolvedStatusArray(),
                'glpi_tickets.takeintoaccount_delay_stat' => 0,
                'glpi_tickets.time_to_own' => ['>', QueryFunction::now()],
                'glpi_tickets.type' => $type,
            ],
        ];

        if (is_array($technicians_groups_id) > 0
            && count($technicians_groups_id) > 0) {
            if (!isset($criteria2['LEFT JOIN'])) {
                $criteria2['LEFT JOIN'] = [];
            }
            $criteria2['LEFT JOIN'] = $criteria2['LEFT JOIN'] + [
                'glpi_groups_tickets' => [
                    'ON' => [
                        'glpi_tickets' => 'id',
                        'glpi_groups_tickets' => 'tickets_id',
                        [
                            'AND' => [
                                'glpi_groups_tickets.type' => CommonITILActor::ASSIGN,
                            ],
                        ],
                    ],
                ],
            ];
            $criteria2['WHERE'] = $criteria2['WHERE'] + ['glpi_groups_tickets.groups_id' => $technicians_groups_id];
        }
        $criteria2['WHERE'] = $criteria2['WHERE'] + getEntitiesRestrictCriteria(
            'glpi_tickets',
        );

        $iterator2 = $DB->request($criteria2);
        $stats2 = 0;
        $nb2 = count($iterator2);
        if ($nb2) {
            foreach ($iterator2 as $data2) {
                $stats2 = $data2['nb'];
            }
        }
        if ($stats2 > 0) {
            $colorstats2 = "indianred";
        }
        /*Stats3*/

        //        if (is_array($technicians_groups_id) > 0
        //            && count($technicians_groups_id) > 0) {
        //            $left = "LEFT JOIN `glpi_groups_tickets`
        //                  ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id`) ";
        //            $search_assign = " (`glpi_groups_tickets`.`groups_id` IN (" . implode(",", $opt['technicians_groups_id']) . ")
        //                                    AND `glpi_groups_tickets`.`type` = '" . CommonITILActor::ASSIGN . "')";
        //        }
        //        $q3 = "SELECT DISTINCT COUNT(`glpi_tickets`.`id`) AS nb
        //                           FROM `glpi_tickets`
        //                           $left
        //                           WHERE `glpi_tickets`.`is_deleted` = '0' ";
        //        $q3 .= $dbu->getEntitiesRestrictRequest("AND", \Ticket::getTable())
        //            . " AND $search_assign AND `glpi_tickets`.`status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")
        //                         AND `glpi_tickets`.`type` = '" . $type . "'
        //                         AND (`glpi_tickets`.`solve_delay_stat` = '0'
        //                         AND `glpi_tickets`.`time_to_resolve` > NOW())";

        $criteria3 = [
            'SELECT' => [
                'COUNT' => 'glpi_tickets.id AS nb',
            ],
            'DISTINCT' => true,
            'FROM' => 'glpi_tickets',
            'WHERE' => [
                $is_deleted,
                'glpi_tickets.status' => \Ticket::getNotSolvedStatusArray(),
                'glpi_tickets.solve_delay_stat' => 0,
                'glpi_tickets.time_to_resolve' => ['>', QueryFunction::now()],
                'glpi_tickets.type' => $type,
            ],
        ];

        if (is_array($technicians_groups_id) > 0
            && count($technicians_groups_id) > 0) {
            if (!isset($criteria3['LEFT JOIN'])) {
                $criteria3['LEFT JOIN'] = [];
            }
            $criteria3['LEFT JOIN'] = $criteria3['LEFT JOIN'] + [
                'glpi_groups_tickets' => [
                    'ON' => [
                        'glpi_tickets' => 'id',
                        'glpi_groups_tickets' => 'tickets_id',
                        [
                            'AND' => [
                                'glpi_groups_tickets.type' => CommonITILActor::ASSIGN,
                            ],
                        ],
                    ],
                ],
            ];
            $criteria3['WHERE'] = $criteria3['WHERE'] + ['glpi_groups_tickets.groups_id' => $technicians_groups_id];
        }
        $criteria3['WHERE'] = $criteria3['WHERE'] + getEntitiesRestrictCriteria(
            'glpi_tickets',
        );

        $iterator3 = $DB->request($criteria3);
        $stats3 = 0;
        $nb3 = count($iterator3);
        if ($nb3) {
            foreach ($iterator3 as $data3) {
                $stats3 = $data3['nb'];
            }
        }
        if ($stats3 > 0) {
            $colorstats3 = "indianred";
        }

        /*Stats4*/


        //        if (is_array($technicians_groups_id) > 0
        //            && count($technicians_groups_id) > 0) {
        //            $left = "LEFT JOIN `glpi_groups_tickets`
        //                  ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id`) ";
        //            $search_assign = " (`glpi_groups_tickets`.`groups_id`IN (" . implode(",", $technicians_groups_id) . ")
        //                                    AND `glpi_groups_tickets`.`type` = '" . CommonITILActor::ASSIGN . "')";
        //        }

        //        $q4 = "SELECT DISTINCT COUNT(`glpi_tickets`.`id`) AS nb
        //                                       FROM `glpi_tickets`
        //                                       $left
        //                                       WHERE `glpi_tickets`.`is_deleted` = '0' ";
        //        $q4 .= $dbu->getEntitiesRestrictRequest("AND", \Ticket::getTable())
        //            . " AND $search_assign AND `glpi_tickets`.`status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")
        //                         AND `glpi_tickets`.`type` = '" . $type . "'
        //                         AND (`glpi_tickets`.`takeintoaccount_delay_stat` = '0'
        //                         AND `glpi_tickets`.`time_to_own` < NOW())";

        $criteria4 = [
            'SELECT' => [
                'COUNT' => 'glpi_tickets.id AS nb',
            ],
            'DISTINCT' => true,
            'FROM' => 'glpi_tickets',
            'WHERE' => [
                $is_deleted,
                'glpi_tickets.status' => \Ticket::getNotSolvedStatusArray(),
                'glpi_tickets.takeintoaccount_delay_stat' => 0,
                'glpi_tickets.time_to_own' => ['<', QueryFunction::now()],
                'glpi_tickets.type' => $type,
            ],
        ];

        if (is_array($technicians_groups_id) > 0
            && count($technicians_groups_id) > 0) {
            if (!isset($criteria4['LEFT JOIN'])) {
                $criteria4['LEFT JOIN'] = [];
            }
            $criteria4['LEFT JOIN'] = $criteria4['LEFT JOIN'] + [
                'glpi_groups_tickets' => [
                    'ON' => [
                        'glpi_tickets' => 'id',
                        'glpi_groups_tickets' => 'tickets_id',
                        [
                            'AND' => [
                                'glpi_groups_tickets.type' => CommonITILActor::ASSIGN,
                            ],
                        ],
                    ],
                ],
            ];
            $criteria4['WHERE'] = $criteria4['WHERE'] + ['glpi_groups_tickets.groups_id' => $technicians_groups_id];
        }
        $criteria4['WHERE'] = $criteria4['WHERE'] + getEntitiesRestrictCriteria(
            'glpi_tickets',
        );

        $iterator4 = $DB->request($criteria4);
        $stats4 = 0;
        $nb4 = count($iterator4);
        if ($nb4) {
            foreach ($iterator4 as $data4) {
                $stats4 = $data4['nb'];
            }
        }
        if ($stats4 > 0) {
            $colorstats4 = "indianred";
        }

        /*Stats5*/

        //        if (is_array($technicians_groups_id) > 0
        //            && count($technicians_groups_id) > 0) {
        //            $left = "LEFT JOIN `glpi_groups_tickets`
        //                  ON (`glpi_tickets`.`id` = `glpi_groups_tickets`.`tickets_id`) ";
        //            $search_assign = " (`glpi_groups_tickets`.`groups_id`IN (" . implode(",", $technicians_groups_id) . ")
        //                                    AND `glpi_groups_tickets`.`type` = '" . CommonITILActor::ASSIGN . "')";
        //        }
        //
        //        $q5 = "SELECT DISTINCT COUNT(`glpi_tickets`.`id`) AS nb
        //                                       FROM `glpi_tickets`
        //                                       $left
        //                                       WHERE `glpi_tickets`.`is_deleted` = '0' ";
        //        $q5 .= $dbu->getEntitiesRestrictRequest("AND", \Ticket::getTable())
        //            . " AND $search_assign AND `glpi_tickets`.`status` NOT IN (" . CommonITILObject::SOLVED . "," . CommonITILObject::CLOSED . ")
        //                         AND `glpi_tickets`.`type` = '" . $type . "'
        //                         AND (`glpi_tickets`.`solve_delay_stat` = '0'
        //                         AND `glpi_tickets`.`time_to_resolve` < NOW())";

        $criteria5 = [
            'SELECT' => [
                'COUNT' => 'glpi_tickets.id AS nb',
            ],
            'DISTINCT' => true,
            'FROM' => 'glpi_tickets',
            'WHERE' => [
                $is_deleted,
                'glpi_tickets.status' => \Ticket::getNotSolvedStatusArray(),
                'glpi_tickets.solve_delay_stat' => 0,
                'glpi_tickets.time_to_resolve' => ['<', QueryFunction::now()],
                'glpi_tickets.type' => $type,
            ],
        ];

        if (is_array($technicians_groups_id) > 0
            && count($technicians_groups_id) > 0) {
            if (!isset($criteria5['LEFT JOIN'])) {
                $criteria5['LEFT JOIN'] = [];
            }
            $criteria5['LEFT JOIN'] = $criteria5['LEFT JOIN'] + [
                'glpi_groups_tickets' => [
                    'ON' => [
                        'glpi_tickets' => 'id',
                        'glpi_groups_tickets' => 'tickets_id',
                        [
                            'AND' => [
                                'glpi_groups_tickets.type' => CommonITILActor::ASSIGN,
                            ],
                        ],
                    ],
                ],
            ];
            $criteria5['WHERE'] = $criteria5['WHERE'] + ['glpi_groups_tickets.groups_id' => $technicians_groups_id];
        }
        $criteria5['WHERE'] = $criteria5['WHERE'] + getEntitiesRestrictCriteria(
            'glpi_tickets',
        );

        $iterator5 = $DB->request($criteria5);
        $stats5 = 0;
        $nb5 = count($iterator5);
        if ($nb5) {
            foreach ($iterator5 as $data5) {
                $stats5 = $data5['nb'];
            }
        }
        if ($stats5 > 0) {
            $colorstats5 = "indianred";
        }

        $is_incident = $type == \Ticket::INCIDENT_TYPE;
        $tiles = [];
        $stats2link = null;
        $stats3link = null;
        $stats4link = null;
        $stats5link = null;

        if ($stats2 > 0) {
            // Reset criterias
            $options2['reset'][] = 'reset';

            $options2['criteria'][] = [
                'field' => 12,//status
                'searchtype' => 'equals',
                'value' => 'notold',
                'link' => 'AND',
            ];

            $options2['criteria'][] = [
                'field' => 14, // type
                'searchtype' => 'equals',
                'value' => $type,
                'link' => 'AND',
            ];

            $options2['criteria'][] = [
                'field' => 155, // time_to_own
                'searchtype' => 'morethan',
                'value' => 'NOW',
                'link' => 'AND',
            ];

            if (is_array($technicians_groups_id) > 0
                && count($technicians_groups_id) > 0) {
                $groups = $technicians_groups_id;
                $nb = 0;
                foreach ($groups as $group) {
                    $criterias['criteria'][$nb] = [
                        'field' => 8, // groups_id_assign
                        'searchtype' => 'equals',
                        'value' => $group,
                        'link' => (($nb == 0) ? 'AND' : 'OR'),
                    ];
                    $nb++;
                }
                $options2['criteria'][] = $criterias;
            }

            $options2['criteria'][] = [
                'field' => 150, // takeintoaccount_delay_stat
                'searchtype' => 'contains',
                'value' => 0,
                'link' => 'AND',
            ];

            $stats2link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&'
                . Toolbox::append_params($options2, "&");
        }

        $tiles[] = [
            'color' => $colorstats2,
            'url' => $stats2link,
            'title' => '',
            'icon_class' => 'ti ti-alert-circle fa-3x fa-border',
            'icon_style' => 'font-size:34px',
            'heading_style' => 'margin-top: 10px;',
            'id' => 'stats_' . $type . '_sla2',
            'label' => $is_incident
                ? __('Incidents where time to own will be exceeded', 'mydashboard')
                : __('Requests where time to own will be exceeded', 'mydashboard'),
            'value' => $stats2,
        ];
        if ($stats3 > 0) {
            // Reset criterias
            $options2['reset'][] = 'reset';

            $options3['criteria'][] = [
                'field' => 12,//status
                'searchtype' => 'equals',
                'value' => 'notold',
                'link' => 'AND',
            ];

            $options3['criteria'][] = [
                'field' => 14, // type
                'searchtype' => 'equals',
                'value' => $type,
                'link' => 'AND',
            ];

            $options3['criteria'][] = [
                'field' => 18, // time_to_resolve
                'searchtype' => 'morethan',
                'value' => 'NOW',
                'link' => 'AND',
            ];

            if (is_array($technicians_groups_id) > 0
                && count($technicians_groups_id) > 0) {
                $groups = $technicians_groups_id;
                $nb = 0;
                foreach ($groups as $group) {
                    $criterias['criteria'][$nb] = [
                        'field' => 8, // groups_id_assign
                        'searchtype' => 'equals',
                        'value' => $group,
                        'link' => (($nb == 0) ? 'AND' : 'OR'),
                    ];
                    $nb++;
                }
                $options3['criteria'][] = $criterias;
            }

            $options3['criteria'][] = [
                'field' => 154, // solve_delay_stat
                'searchtype' => 'contains',
                'value' => 0,
                'link' => 'AND',
            ];

            $stats3link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&'
                . Toolbox::append_params($options3, "&");
        }

        $tiles[] = [
            'color' => $colorstats3,
            'url' => $stats3link,
            'title' => '',
            'icon_class' => 'ti ti-circle-x fa-3x fa-border',
            'icon_style' => 'font-size:34px',
            'heading_style' => 'margin-top: 10px;',
            'id' => 'stats_' . $type . '_sla3',
            'label' => $is_incident
                ? __('Incidents where time to resolve will be exceeded', 'mydashboard')
                : __('Requests where time to resolve will be exceeded', 'mydashboard'),
            'value' => $stats3,
        ];

        if ($stats4 > 0) {
            // Reset criterias
            $options4['reset'][] = 'reset';

            $options4['criteria'][] = [
                'field' => 12,//status
                'searchtype' => 'equals',
                'value' => 'notold',
                'link' => 'AND',
            ];

            $options4['criteria'][] = [
                'field' => 14, // type
                'searchtype' => 'equals',
                'value' => $type,
                'link' => 'AND',
            ];

            $options4['criteria'][] = [
                'field' => 155, // time_to_own
                'searchtype' => 'lessthan',
                'value' => 'NOW',
                'link' => 'AND',
            ];

            if (is_array($technicians_groups_id) > 0
                && count($technicians_groups_id) > 0) {
                $groups = $technicians_groups_id;
                $nb = 0;
                foreach ($groups as $group) {
                    $criterias['criteria'][$nb] = [
                        'field' => 8, // groups_id_assign
                        'searchtype' => 'equals',
                        'value' => $group,
                        'link' => (($nb == 0) ? 'AND' : 'OR'),
                    ];
                    $nb++;
                }
                $options4['criteria'][] = $criterias;
            }

            $options4['criteria'][] = [
                'field' => 150, // takeintoaccount_delay_stat
                'searchtype' => 'contains',
                'value' => 0,
                'link' => 'AND',
            ];

            $stats4link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&'
                . Toolbox::append_params($options4, "&");
        }

        $tiles[] = [
            'color' => $colorstats4,
            'url' => $stats4link,
            'title' => '',
            'icon_class' => 'ti ti-alert-circle fa-3x fa-border',
            'icon_style' => 'font-size:34px',
            'heading_style' => 'margin-top: 10px;',
            'id' => 'stats_' . $type . '_sla4',
            'label' => $is_incident
                ? __('Incidents where time to own is exceeded', 'mydashboard')
                : __('Requests where time to own is exceeded', 'mydashboard'),
            'value' => $stats4,
        ];

        if ($stats5 > 0) {
            // Reset criterias
            $options5['reset'][] = 'reset';

            $options5['criteria'][] = [
                'field' => 12,//status
                'searchtype' => 'equals',
                'value' => 'notold',
                'link' => 'AND',
            ];

            $options5['criteria'][] = [
                'field' => 14, // type
                'searchtype' => 'equals',
                'value' => $type,
                'link' => 'AND',
            ];

            $options5['criteria'][] = [
                'field' => 18, // time_to_resolve
                'searchtype' => 'lessthan',
                'value' => 'NOW',
                'link' => 'AND',
            ];

            if (is_array($technicians_groups_id) > 0
                && count($technicians_groups_id) > 0) {
                $groups = $technicians_groups_id;
                $nb = 0;
                foreach ($groups as $group) {
                    $criterias['criteria'][$nb] = [
                        'field' => 8, // groups_id_assign
                        'searchtype' => 'equals',
                        'value' => $group,
                        'link' => (($nb == 0) ? 'AND' : 'OR'),
                    ];
                    $nb++;
                }
                $options5['criteria'][] = $criterias;
            }

            $options5['criteria'][] = [
                'field' => 154, // solve_delay_stat
                'searchtype' => 'contains',
                'value' => 0,
                'link' => 'AND',
            ];

            $stats5link = $CFG_GLPI["root_doc"] . '/front/ticket.php?is_deleted=0&'
                . Toolbox::append_params($options5, "&");
        }

        $tiles[] = [
            'color' => $colorstats5,
            'url' => $stats5link,
            'title' => '',
            'icon_class' => 'ti ti-circle-x fa-3x fa-border',
            'icon_style' => 'font-size:34px',
            'heading_style' => 'margin-top: 10px;',
            'id' => 'stats_' . $type . '_sla5',
            'label' => $is_incident
                ? __('Incidents where time to resolve is exceeded', 'mydashboard')
                : __('Requests where time to resolve is exceeded', 'mydashboard'),
            'value' => $stats5,
        ];

        $widget->setWidgetHtmlContent(self::getStatsTilesHtml($tiles));
        $widget->toggleWidgetRefresh();
        $widget->setWidgetHeaderType('danger');
        if ($type == \Ticket::INCIDENT_TYPE) {
            $widget->setWidgetTitle(__("SLA Incidents alerts", "mydashboard"));
            $widget->setWidgetComment(__("Display alerts for SLA of Incidents tickets", "mydashboard"));
        } else {
            $widget->setWidgetTitle(__("SLA Requests alerts", "mydashboard"));
            $widget->setWidgetComment(__("Display alerts for SLA of Requests tickets", "mydashboard"));
        }
        return $widget;
    }

    /**
     * Render the news-ticker shared by the alert, maintenance and information widgets.
     *
     * @param string $prefix            id prefix of the ticker (nt_alert, nt_maint, nt_info)
     * @param string $data_attr         suffix of the per-item data attribute
     * @param array  $items             [['id' => int, 'style' => string, 'name' => string, 'icon_class' => ?string]]
     * @param string $first_description sanitized text of the first item
     * @param string $title_field       Config field holding the widget title, for the empty state
     * @param string $empty_label       message shown when there is nothing to display
     * @param string $first_color       CSS color wrapping the first description, if any
     *
     * @return string
     */
    /**
     * Render one cell of the "tickets updated by requesters" Datatable.
     *
     * The Datatable API takes an HTML string per cell: the markup comes from
     * alert_ticket_cell.html.twig, which escapes every value.
     *
     * @param string $kind      ticket_name, ticket_id, names, priority or category
     * @param array  $variables values of the cell
     *
     * @return string
     */
    private static function getTicketCellHtml($kind, $variables)
    {
        return trim(TemplateRenderer::getInstance()->render(
            '@mydashboard/alert_ticket_cell.html.twig',
            ['kind' => $kind] + $variables,
        ));
    }

    /**
     * Render a row of counter tiles, shared by the ticket-alert and SLA-alert widgets.
     *
     * @param array $tiles each entry carries color, url (null when the counter is zero),
     *                     title, icon_class, icon_style, heading_style, id, label and the
     *                     counter value counted up by public/scripts/alert-widgets.js
     *
     * @return string
     */
    private static function getStatsTilesHtml($tiles)
    {
        return TemplateRenderer::getInstance()->render('@mydashboard/alert_stats_tiles.html.twig', [
            'tiles' => $tiles,
        ]);
    }

    private static function getTickerHtml($prefix, $data_attr, $items, $first_description, $title_field, $empty_label, $first_color = '')
    {
        $empty_title = '';
        if ($items === []) {
            $config = new Config();
            $config->getFromDB(1);
            $empty_title = Config::getTranslatedField($config, $title_field);
        }

        return TemplateRenderer::getInstance()->render('@mydashboard/alert_ticker.html.twig', [
            'prefix' => $prefix,
            'data_attr' => $data_attr,
            'items' => $items,
            'first_description' => $first_description,
            'first_color' => $first_color,
            'empty_title' => $empty_title,
            'empty_label' => $empty_label,
            // Loaded by public/scripts/alert-widgets.js when the ticker moves
            'description_url' => PLUGIN_MYDASHBOARD_WEBDIR . '/ajax/showalert.php',
        ]);
    }

    public function getMaintenanceList($itilcategories_id = [])
    {
        global $DB;

        $now = date('Y-m-d H:i:s');

        //        $restrict_user = '1';
        //        // Only personal on central so do not keep it
        //        //      if (Session::getCurrentInterface() == 'central') {
        //        //         $restrict_user = "`glpi_reminders`.`users_id` <> '".Session::getLoginUserID()."'";
        //        //      }
        //        $addwhere = "";
        //        if (count($itilcategories_id) > 0) {
        //            $cats = implode("','", $itilcategories_id);
        //            $addwhere = " AND `glpi_plugin_mydashboard_alerts`.`itilcategories_id` IN ('" . $cats . "')";
        //        }
        //
        //        $restrict_visibility = "AND (`glpi_reminders`.`begin_view_date` IS NULL
        //                                    OR `glpi_reminders`.`begin_view_date` < '$now')
        //                              AND (`glpi_reminders`.`end_view_date` IS NULL
        //                                   OR `glpi_reminders`.`end_view_date` > '$now') ";
        //
        //        $query = "SELECT `glpi_reminders`.`id`,
        //                       `glpi_reminders`.`name`
        //                   FROM `glpi_reminders` "
        //            . Reminder::addVisibilityJoins()
        //            . "LEFT JOIN `" . $this->getTable() . "`"
        //            . "ON `glpi_reminders`.`id` = `" . $this->getTable() . "`.`reminders_id`"
        //            . "WHERE $restrict_user
        //                        $addwhere
        //                         $restrict_visibility ";
        //
        //        $query .= "AND " . \Reminder::addVisibilityRestrict() . "";
        //
        //        $query .= "AND `" . $this->getTable() . "`.`type` = 1
        //                   ORDER BY `glpi_reminders`.`name`";


        $visibility_criteria = [
            [
                'OR' => [
                    ['glpi_reminders.begin_view_date' => null],
                    ['glpi_reminders.begin_view_date' => ['<', $now]],
                ],
            ],
            [
                'OR' => [
                    ['glpi_reminders.end_view_date' => null],
                    ['glpi_reminders.end_view_date' => ['>', $now]],
                ],
            ],
        ];

        $criteria = [
            'SELECT' => [
                'glpi_reminders.id',
                'glpi_reminders.name',
            ],
            'FROM' => 'glpi_reminders',
            'LEFT JOIN' => [
                $this->getTable() => [
                    'ON' => [
                        $this->getTable() => 'reminders_id',
                        'glpi_reminders' => 'id',
                    ],
                ],
            ],
            'WHERE' => [
                $this->getTable() . '.type' => 1,
                $visibility_criteria,
            ],
            // Collapse rows duplicated by the visibility joins (entities/groups/profiles/users)
            'GROUPBY' => 'glpi_reminders.id',
            'ORDERBY' => 'glpi_reminders.name',
        ];

        // Both halves of the core visibility criteria, joins AND where: only the joins used
        // to be taken, and a LEFT JOIN on its own filters nothing, so the maintenance ticker
        // listed every reminder of the instance — name, text and attached documents included
        // once the row id reached ajax/showalert.php.
        $criteria = Reminder::applyVisibilityCriteria($criteria);

        if (count($itilcategories_id) > 0) {
            $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_plugin_mydashboard_alerts.itilcategories_id' => $itilcategories_id];
        } else {
            $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_plugin_mydashboard_alerts.itilcategories_id' => 0];
        }

        $iterator = $DB->request($criteria);

        $items = [];
        $firstdescription = "";
        foreach ($iterator as $row) {
            $note = new \Reminder();
            $note->getFromDB($row["id"]);

            if ($items === []) {
                // Reminder text is rich HTML stored raw: sanitize at the source (strips
                // scripts / event handlers) while preserving allowed formatting.
                $firstdescription = RichText::getSafeHtml(
                    ReminderTranslation::getTranslatedValue($note, 'text'),
                );
            }

            $items[] = [
                'id' => $row["id"],
                'style' => 'text-align:center;color:orange',
                // Reminder names are stored raw: alert_ticker.html.twig escapes them.
                'name' => (string) ReminderTranslation::getTranslatedValue($note, 'name'),
            ];
        }

        return self::getTickerHtml(
            'nt_maint',
            'maint',
            $items,
            $firstdescription,
            'title_maintenances_widget',
            __("No scheduled maintenance", "mydashboard"),
        );
    }


    public static function displayTickerDescription($id)
    {
        global $DB;

        // Anti-IDOR: $id comes straight from the client ($_GET['id'] in
        // ajax/showalert.php), which enforces no right of its own. This gate is therefore
        // the only access control: without it any caller could enumerate id=1,2,3... and
        // read the text and attached documents of any reminder, including alerts targeting
        // another entity/profile.
        //
        // Core \Reminder::getVisibilityCriteria() supplies BOTH the joins and the matching
        // WHERE clause, and Reports\Reminder::applyVisibilityCriteria() now merges them so
        // they cannot be taken apart again. Only the joins used to be applied here, and a
        // LEFT JOIN on its own filters nothing, so the check passed for every reminder. The
        // core helper also degrades safely: with no session it restricts on a falsy users_id
        // and matches no row at all.
        $id = (int) $id;

        $config = new Config();
        $config->getFromDB(1);
        //
        $alert = new self();

        if ($alert->getFromDBByCrit(['reminders_id' => $id])) {
            $now = date('Y-m-d H:i:s');
            $visibility_check = Reminder::applyVisibilityCriteria([
                'SELECT'  => 'glpi_reminders.id',
                'FROM'    => 'glpi_reminders',
                'WHERE'   => [
                    'glpi_reminders.id' => $id,
                    [
                        'OR' => [
                            ['glpi_reminders.begin_view_date' => null],
                            ['glpi_reminders.begin_view_date' => ['<', $now]],
                        ],
                    ],
                    [
                        'OR' => [
                            ['glpi_reminders.end_view_date' => null],
                            ['glpi_reminders.end_view_date' => ['>', $now]],
                        ],
                    ],
                ],
                'GROUPBY' => 'glpi_reminders.id',
            ]);
            if (count($DB->request($visibility_check)) === 0) {
                return;
            }

            // Second, independent barrier: the visibility query above is the only other
            // control on this endpoint, and a regression in it would reopen every reminder.
            // haveVisibilityAccess() rather than can(READ): the ticker is shown to users
            // holding no reminder right at all, which canViewItem() requires.
            $note = new \Reminder();
            if (!$note->getFromDB($id) || !$note->haveVisibilityAccess()) {
                return;
            }

            $color = null;
            if ($alert->fields['type'] == 0 && $alert->fields['impact'] > 0) {
                // impact_* colors only ever hold a CSS hex value (validated in
                // Config::prepareInputForUpdate); the template escapes it anyway.
                $color = (string) $config->getField('impact_' . $alert->fields['impact']);
            }

            $document_links = [];
            $iterator = $DB->request([
                'SELECT' => 'documents_id',
                'FROM' => 'glpi_documents_items',
                'WHERE' => [
                    'items_id' => $id,
                    'itemtype' => 'Reminder',
                ],
            ]);
            foreach ($iterator as $docs) {
                $doc = new Document();
                $doc->getFromDB($docs["documents_id"]);
                $document_links[] = $doc->getDownloadLink();
            }

            TemplateRenderer::getInstance()->display('@mydashboard/alert_ticker_description.html.twig', [
                'color' => $color,
                // The reminder text is rich HTML: sanitize it the same way getAlertList()
                // does for the first item. htmlspecialchars() was used here instead, which
                // is why the AJAX-loaded description showed its markup as literal text.
                'text_html' => RichText::getSafeHtml(
                    ReminderTranslation::getTranslatedValue($note, 'text'),
                ),
                'document_links' => $document_links,
            ]);
        }
    }

    /**
     * @return string
     */
    public function getInformationList($itilcategories_id = [])
    {
        global $DB;

        $now = date('Y-m-d H:i:s');

        $visibility_criteria = [
            [
                'OR' => [
                    ['glpi_reminders.begin_view_date' => null],
                    ['glpi_reminders.begin_view_date' => ['<', $now]],
                ],
            ],
            [
                'OR' => [
                    ['glpi_reminders.end_view_date' => null],
                    ['glpi_reminders.end_view_date' => ['>', $now]],
                ],
            ],
        ];

        $criteria = [
            'SELECT' => [
                'glpi_reminders.id',
                'glpi_reminders.name',
            ],
            'FROM' => 'glpi_reminders',
            'LEFT JOIN' => [
                $this->getTable() => [
                    'ON' => [
                        $this->getTable() => 'reminders_id',
                        'glpi_reminders' => 'id',
                    ],
                ],
            ],
            'WHERE' => [
                $this->getTable() . '.type' => 2,
                $visibility_criteria,
            ],
            // Collapse rows duplicated by the visibility joins (entities/groups/profiles/users)
            'GROUPBY' => 'glpi_reminders.id',
            'ORDERBY' => 'glpi_reminders.name',
        ];

        // Both halves of the core visibility criteria, joins AND where: only the joins used
        // to be taken, and a LEFT JOIN on its own filters nothing, so the information ticker
        // listed every reminder of the instance.
        $criteria = Reminder::applyVisibilityCriteria($criteria);

        if (count($itilcategories_id) > 0) {
            $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_plugin_mydashboard_alerts.itilcategories_id' => $itilcategories_id];
        } else {
            $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_plugin_mydashboard_alerts.itilcategories_id' => 0];
        }

        $iterator = $DB->request($criteria);

        $nb = count($iterator);

        $items = [];
        $firstdescription = "";
        foreach ($iterator as $row) {
            $note = new \Reminder();
            $note->getFromDB($row["id"]);

            if ($items === []) {
                // Reminder text is rich HTML stored raw: sanitize at the source (strips
                // scripts / event handlers) while preserving allowed formatting.
                $firstdescription = RichText::getSafeHtml(
                    ReminderTranslation::getTranslatedValue($note, 'text'),
                );
            }

            $items[] = [
                'id' => $row["id"],
                'style' => 'text-align:center;',
                // Reminder names are stored raw: alert_ticker.html.twig escapes them.
                'name' => (string) ReminderTranslation::getTranslatedValue($note, 'name'),
            ];
        }

        return self::getTickerHtml(
            'nt_info',
            'info',
            $items,
            $firstdescription,
            'title_informations_widget',
            __("No informations founded", "mydashboard"),
        );
    }



    /**
     * @param int $public
     *
     * @return string
     */
    public function getAlertList($public = 0, $itilcategories_id = [])
    {
        global $DB;

        $config = new Config();
        $config->getFromDB(1);
        $now = date('Y-m-d H:i:s');

        //        $query = "SELECT `glpi_reminders`.`id`,
        //                       `glpi_reminders`.`name`,
        //                       `glpi_reminders`.`begin_view_date`,
        //                       `glpi_reminders`.`end_view_date`,
        //                       `" . $this->getTable() . "`.`impact`
        //                   FROM `glpi_reminders` "
        //            . Reminder::addVisibilityJoins()
        //            . "LEFT JOIN `" . $this->getTable() . "`"
        //            . "ON `glpi_reminders`.`id` = `" . $this->getTable() . "`.`reminders_id`"
        //            . "WHERE $restrict_user
        //                        $addwhere
        //                         $restrict_visibility ";
        //
        //        if ($public == 0) {
        //            $query .= "AND " . \Reminder::addVisibilityRestrict() . "";
        //        } else {
        //            $query .= "AND `" . $this->getTable() . "`.`is_public`";
        //        }
        //
        //        $query .= "AND `" . $this->getTable() . "`.`impact` IS NOT NULL
        //                 AND `" . $this->getTable() . "`.`type` = 0
        //                   ORDER BY `glpi_reminders`.`name`";
        //
        $visibility_criteria = [
            [
                'OR' => [
                    ['glpi_reminders.begin_view_date' => null],
                    ['glpi_reminders.begin_view_date' => ['<', $now]],
                ],
            ],
            [
                'OR' => [
                    ['glpi_reminders.end_view_date' => null],
                    ['glpi_reminders.end_view_date' => ['>', $now]],
                ],
            ],
        ];

        $criteria = [
            'SELECT' => [
                'glpi_reminders.id',
                'glpi_reminders.name',
                'glpi_reminders.begin_view_date',
                'glpi_reminders.end_view_date',
                $this->getTable() . '.impact',
            ],
            'FROM' => 'glpi_reminders',
            'LEFT JOIN' => [
                $this->getTable() => [
                    'ON' => [
                        $this->getTable() => 'reminders_id',
                        'glpi_reminders' => 'id',
                    ],
                ],
            ],
            'WHERE' => [
                'NOT'       => [ $this->getTable() . '.impact' => null],
                $this->getTable() . '.type' => 0,
                $visibility_criteria,
            ],
            // Collapse rows duplicated by the visibility joins (entities/groups/profiles/users)
            'GROUPBY' => 'glpi_reminders.id',
            'ORDERBY' => 'glpi_reminders.name',
        ];

        if ($public == 0) {
            // Both halves of the core visibility criteria, joins AND where: only the joins
            // used to be taken, and a LEFT JOIN on its own filters nothing, so the alert
            // ticker listed every reminder of the instance. The $public == 1 branch is the
            // anonymous ticker and keeps selecting on is_public instead.
            $criteria = Reminder::applyVisibilityCriteria($criteria);

            if (count($itilcategories_id) > 0) {
                $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_plugin_mydashboard_alerts.itilcategories_id' => $itilcategories_id];
            } else {
                $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_plugin_mydashboard_alerts.itilcategories_id' => 0];
            }
        } else {
            $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_plugin_mydashboard_alerts.is_public' => 1];
        }

        $iterator = $DB->request($criteria);

        $items = [];
        $firstdescription = "";
        $first_color = '';
        foreach ($iterator as $row) {
            $note = new \Reminder();
            $note->getFromDB($row["id"]);

            // impact_* colors are stored raw; they only ever hold a CSS hex value
            // (validated in Config::prepareInputForUpdate).
            $impact_color = $config->getField('impact_' . $row['impact']);

            if ($items === []) {
                // Reminder text is rich HTML stored raw: sanitize it, the template wraps it
                // in the span carrying the impact color.
                $firstdescription = RichText::getSafeHtml(
                    ReminderTranslation::getTranslatedValue($note, 'text'),
                );
                $first_color = $impact_color;
            }

            $items[] = [
                'id' => $row["id"],
                'style' => "text-align: center;color:" . $impact_color,
                'icon_class' => "fas plugin_mydashboard_fa-thermometer-" . ($row['impact'] - 1),
                // Reminder names are stored raw: alert_ticker.html.twig escapes them.
                'name' => (string) ReminderTranslation::getTranslatedValue($note, 'name'),
            ];
        }

        return self::getTickerHtml(
            'nt_alert',
            'alert',
            $items,
            $firstdescription,
            'title_alerts_widget',
            __("No problem detected", "mydashboard"),
            $first_color,
        );
    }

    /**
     * @param int $public
     *
     * @param int $force
     *
     * @return string
     */
    public function getAlertSummary($public = 0, $force = 0)
    {
        global $DB;

        $now = date('Y-m-d H:i:s');

        //        $query = "SELECT `glpi_reminders`.`id`,
        //                       `glpi_reminders`.`name`,
        //                       `glpi_reminders`.`text`,
        //                       `glpi_reminders`.`date`,
        //                       `glpi_reminders`.`begin_view_date`,
        //                       `glpi_reminders`.`end_view_date`,
        //                       `" . $this->getTable() . "`.`impact`,
        //                       `" . $this->getTable() . "`.`is_public`
        //                   FROM `glpi_reminders` "
        //            . Reminder::addVisibilityJoins()
        //            . " LEFT JOIN `" . $this->getTable() . "`"
        //            . " ON `glpi_reminders`.`id` = `" . $this->getTable() . "`.`reminders_id`"
        //            . " WHERE $restrict_user
        //                         $restrict_visibility ";
        //
        //        if ($public == 0) {
        //            $query .= "AND " . \Reminder::addVisibilityRestrict() . "";
        //        } else {
        //            $query .= "AND `" . $this->getTable() . "`.`is_public`";
        //        }
        //        $query .= "AND `" . $this->getTable() . "`.`impact` IS NOT NULL
        //                 AND `" . $this->getTable() . "`.`type` = 0
        //                   ORDER BY `glpi_reminders`.`name`";
        //
        //

        $visibility_criteria = [
            [
                'OR' => [
                    ['glpi_reminders.begin_view_date' => null],
                    ['glpi_reminders.begin_view_date' => ['<', $now]],
                ],
            ],
            [
                'OR' => [
                    ['glpi_reminders.end_view_date' => null],
                    ['glpi_reminders.end_view_date' => ['>', $now]],
                ],
            ],
        ];

        $criteria = [
            'SELECT' => [
                'glpi_reminders.id',
                'glpi_reminders.name',
                'glpi_reminders.text',
                'glpi_reminders.date',
                'glpi_reminders.begin_view_date',
                'glpi_reminders.end_view_date',
                $this->getTable() . '.impact',
                $this->getTable() . '.is_public',
            ],
            'FROM' => 'glpi_reminders',
            'LEFT JOIN' => [
                $this->getTable() => [
                    'ON' => [
                        $this->getTable() => 'reminders_id',
                        'glpi_reminders' => 'id',
                    ],
                ],
            ],
            'WHERE' => [
                'NOT'       => [ $this->getTable() . '.impact' => null],
                $this->getTable() . '.type' => 0,
                $visibility_criteria,
            ],
            // Collapse rows duplicated by the visibility joins (entities/groups/profiles/users)
            'GROUPBY' => 'glpi_reminders.id',
            'ORDERBY' => 'glpi_reminders.name',
        ];

        if ($public == 0) {
            // Both halves of the core visibility criteria, joins AND where: only the joins
            // used to be taken, and a LEFT JOIN on its own filters nothing, so the summary
            // exposed the name, text and dates of every reminder of the instance. The
            // $public == 1 branch is the anonymous ticker and keeps selecting on is_public.
            $criteria = Reminder::applyVisibilityCriteria($criteria);
        } else {
            $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_plugin_mydashboard_alerts.is_public' => 1];
        }

        $iterator = $DB->request($criteria);

        $nb = count($iterator);

        $nb_maintenance = self::countForAlerts($public, 1);

        // The weather icon follows the highest impact among the displayed alerts. A
        // row with an impact out of 1-5 is listed nowhere, as before.
        $list = [];
        $max_impact = 0;
        foreach ($iterator as $row) {
            if ($row['impact'] >= 1 && $row['impact'] <= 5) {
                $list[] = $row;
                $max_impact = max($max_impact, (int) $row['impact']);
            }
        }

        $weather = null;
        if ($max_impact > 0) {
            $weather = $this->getWeatherData($max_impact, $list);
        } elseif (!$nb && ($public == 0 || $force == 1)) {
            $weather = $this->getWeatherData(1, []);
        }

        $show_maintenance = $nb_maintenance > 0;
        if ($weather === null && !$show_maintenance) {
            return '';
        }

        return TemplateRenderer::getInstance()->render('@mydashboard/alert_summary.html.twig', [
            // The login page does not load the ADD_CSS hook of the plugin.
            'css_url' => ($nb || $show_maintenance)
                ? PLUGIN_MYDASHBOARD_WEBDIR . '/css/mydashboard.css?v=' . PLUGIN_MYDASHBOARD_VERSION
                : '',
            'weather' => $weather,
            'show_maintenance' => $show_maintenance,
        ]);
    }

    /**
     * Data of the weather block: title, icon matching the impact and list of alerts.
     *
     * @param int   $impact
     * @param array $list   reminder rows carrying id, name and impact
     *
     * @return array
     */
    private function getWeatherData($impact, $list)
    {
        $config = new Config();
        $config->getFromDB(1);

        $items = [];
        foreach ($list as $listitem) {
            $items[] = [
                // Reminder names are stored raw: the template escapes them. This block
                // is shown to anonymous visitors on the login page (DISPLAY_LOGIN hook).
                'name' => (string) $listitem['name'],
                'url' => Session::haveRight(\Reminder::$rightname, READ)
                    ? \Reminder::getFormURLWithID((int) $listitem['id'])
                    : null,
                // impact_* colors are stored raw; the template escapes them.
                'color' => (string) $config->getField("impact_" . $listitem['impact']),
            ];
        }

        return [
            'title' => Config::getTranslatedField($config, 'title_alerts_widget'),
            'icon_class' => "plugin_mydashboard_fa-thermometer-" . ($impact - 1),
            'color' => (string) $config->getField('impact_' . $impact),
            'items' => $items,
        ];
    }

    /**
     * @param Reminder $item
     */
    private function showReminderForm(\Reminder $item)
    {
        // Same rule as prepareInputForAdd(): only who may edit the reminder may alert on it
        if (!$item->can($item->getID(), UPDATE)) {
            return;
        }

        $reminders_id = $item->getID();
        $this->getFromDBByCrit(['reminders_id' => $reminders_id]);

        TemplateRenderer::getInstance()->display(
            '@mydashboard/alert_form.html.twig',
            $this->getAlertFormParams(
                $reminders_id,
                _n('Alert', 'Alerts', 1, 'mydashboard'),
                [-1 => __('All categories', 'mydashboard')],
            ),
        );
    }

    /**
     * Variables of the alert form attached to a reminder (alert_form.html.twig).
     *
     * Shared by showReminderForm(), showForItem() and ItilAlert::showForItem(), which
     * used to carry nearly identical copies of it.
     *
     * @param int    $reminders_id
     * @param string $first_type_name label of type 0, the only wording that differs
     * @param array  $category_toadd  extra entries prepended to the category dropdown
     * @param bool   $with_delete     show the purge button once the alert exists
     *
     * @return array
     */
    public function getAlertFormParams($reminders_id, $first_type_name, $category_toadd = [], $with_delete = false)
    {
        if (isset($this->fields['id'])) {
            $id = $this->fields['id'];
            $impact = $this->fields['impact'];
            $itilcategories_id = $this->fields['itilcategories_id'];
            $type = $this->fields['type'];
            $is_public = $this->fields['is_public'];
        } else {
            $id = -1;
            $type = 0;
            $impact = 0;
            $itilcategories_id = 0;
            $is_public = 0;
        }

        $types = [
            0 => $first_type_name,
            1 => _n('Scheduled maintenance', 'Scheduled maintenances', 1, 'mydashboard'),
            2 => _n('Information', 'Informations', 1, 'mydashboard'),
        ];

        $impacts = [0 => __("No impact", "mydashboard")];
        for ($i = 1; $i <= 5; $i++) {
            $impacts[$i] = CommonITILObject::getImpactName($i);
        }

        $category_options = [
            'entity' => $_SESSION['glpiactiveentities'],
        ];
        if ($category_toadd !== []) {
            $category_options['toadd'] = $category_toadd;
        }

        return [
            'form_action' => $this->getFormURL(),
            'id' => $id,
            'reminders_id' => $reminders_id,
            'type' => $type,
            'types' => $types,
            'impact' => $impact,
            'impacts' => $impacts,
            'itilcategories_id' => $itilcategories_id,
            'category_options' => $category_options,
            'is_public' => $is_public,
            'can_edit' => Session::haveRight(\Reminder::$rightname, UPDATE),
            'can_delete' => $with_delete && $id > 0,
        ];
    }


    /**
     * @param $item
     */
    private function showForItem($item)
    {
        $items_id = $item->getID();
        $item->getFromDB($items_id);
        $itemtype = $item->getType();
        $reminder = new \Reminder();
        $has_reminder = isset($item->fields['reminders_id']);

        $create_button = null;
        if (!$has_reminder) {
            // Handled by public/scripts/alert-item.js from the data-* attributes.
            $create_button = [
                'menu_name' => Menu::getTypeName(2),
                'url' => PLUGIN_MYDASHBOARD_WEBDIR . '/ajax/createalert.php',
                'itemtype' => $itemtype,
                'items_id' => (int) $items_id,
                'script_url' => PLUGIN_MYDASHBOARD_WEBDIR . '/scripts/alert-item.js',
            ];
        }

        $reminder_data = null;
        $alert_form = null;
        if ($has_reminder) {
            $reminders_id = $item->fields['reminders_id'];
            $reminder->getFromDB($reminders_id);
            // The reminder text is stored raw (rich text) in GLPI 11: the template runs it
            // through |safe_html, which keeps the allowed formatting but strips scripts and
            // event handlers (stored XSS for any user opening this tab otherwise).
            $reminder_data = [
                'name' => $reminder->getNameID(),
                'url' => $reminder->getLinkURL(),
                'text' => $reminder->fields['text'],
            ];

            $this->getFromDBByCrit(['reminders_id' => $reminders_id]);
            $alert_form = $this->getAlertFormParams(
                $reminders_id,
                _n('Network alert', 'Network alerts', 1, 'mydashboard'),
            );
        }

        TemplateRenderer::getInstance()->display('@mydashboard/alert_item.html.twig', [
            'create_button' => $create_button,
            'reminder' => $reminder_data,
            'alert_form' => $alert_form,
        ]);

        if ($has_reminder) {
            $reminder->showVisibility();
        }
    }


    /**
     * @param $message
     * @param $url
     *
     * @return string
     */
    public static function handleShellcommandResult(&$message, $url)
    {
        global $CFG_GLPI;
        //        Toolbox::logInfo($message);
        if ($message == null) {
            return "";
        }

        $params = [
            'wrapper_class' => 'md-title-status',
            'url' => null,
            'maintenance_text' => '',
        ];

        if (isset($CFG_GLPI["maintenance_mode"]) && $CFG_GLPI["maintenance_mode"]) {
            $params += [
                'color' => 'darkred',
                'icon' => 'ti ti-exclamation-circle',
                'label' => __('Service is down for maintenance. It will be back shortly.'),
            ];
            $params['wrapper_class'] = 'center';
            if (isset($CFG_GLPI["maintenance_text"]) && !empty($CFG_GLPI["maintenance_text"])) {
                // Raw text: the template applies Twig's nl2br filter, which escapes the value
                // before turning the newlines into <br />. Calling nl2br() here instead forced
                // the template to render the value with |raw, i.e. to open a full HTML sink on a
                // configuration field for the sole purpose of keeping its line breaks.
                $params['maintenance_text'] = $CFG_GLPI["maintenance_text"];
            }
            $message = "";
        } elseif (preg_match('/PROBLEM/is', $message)) {
            $params += [
                'color' => 'darkred',
                'icon' => 'ti ti-exclamation-circle',
                'label' => __("Problem with GLPI", "mydashboard"),
            ];
        } elseif (preg_match('/OK/is', $message)) {
            $params += [
                'color' => 'forestgreen',
                'icon' => 'ti ti-circle-check',
                'label' => __("GLPI is OK", "mydashboard"),
            ];
        } else {
            $params += [
                'color' => 'orange',
                'icon' => 'ti ti-alert-triangle',
                'label' => __(
                    "Alert is not properly configured or is not reachable (or exceeded the timeout)",
                    "mydashboard",
                ),
            ];
            $params['url'] = $url;
        }

        return TemplateRenderer::getInstance()->render('@mydashboard/alert_status_banner.html.twig', $params);
    }

    public static function displayIndicator($id, $type, $params = [], $iswidget = false)
    {
        global $CFG_GLPI;

        if (!Session::haveRightsOr(\Ticket::$rightname, [\Ticket::READMY, \Ticket::READALL, \Ticket::READGROUP])) {
            return false;
        }

        $seeown = false;

        if (isset($params['year']) && isset($params['week'])) {
            $dto = new DateTime();
            $dto->setISODate($params['year'], $params['week']);
            $dto->setTime(0, 0, 1);
            $week_start = $dto->format('Y-m-d H:i:s');
            $dto->modify('+6 days');
            $dto->setTime(23, 59, 59);
            $week_end = $dto->format('Y-m-d H:i:s');
        } else {
            $params['year'] = date("Y");
            $params['week'] = date("W");
            $dto = new DateTime();
            $dto->setISODate($params['year'], $params['week']);
            $dto->setTime(0, 0, 1);
            $week_start = $dto->format('Y-m-d H:i:s');
            $dto->modify('+6 days');
            $dto->setTime(23, 59, 59);
            $week_end = $dto->format('Y-m-d H:i:s');
        }

        $opt = [];
        if ($seeown == false) {
            if ($iswidget == true) {
                if (Plugin::isPluginActive("Mydashboard")) {
                    $preference = new Preference();
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
                            $opt['technicians_groups_id'] = $technicians_groups_id;
                        }
                    }

                    if (!isset($params['itilcategories_id'])) {
                        $params['itilcategories_id'] = "";
                        if (isset($preferences['prefered_category'])) {
                            if ($preferences['prefered_category'] != 0) {
                                $opt['itilcategories_id'] = $preferences['prefered_category'];
                            }
                        }
                    } else {
                        $opt['itilcategories_id'] = $params['itilcategories_id'];
                    }

                    if (!isset($params['itilcategorielvl1'])) {
                        $params['itilcategorielvl1'] = "";
                        if (isset($preferences['prefered_category'])) {
                            if ($preferences['prefered_category'] != 0) {
                                $opt['itilcategories_id'] = $preferences['prefered_category'];
                            }
                        }
                    } else {
                        $opt['itilcategories_id'] = $params['itilcategorielvl1'];
                    }
                }
            }
        }

        if ($seeown == false) {
            if (!isset($params['year']) && !isset($params['week'])) {
                $params['year'] = date('Y');
                $params['week'] = date('W');
            }

            if ($type == "all") {
                $crits = [
                    "entities_id",
                    "is_recursive_entities",
                    "technicians_groups_id",
                    "itilcategories_id",
                ];
                $params_query["criterias"] = [
                    "entities_id",
                    "is_recursive_entities",
                    "technicians_groups_id",
                    "itilcategories_id",
                ];

                $default = Criteria::manageCriterias($params_query);

                $opt['entities_id'] = $params['entities_id'] ?? $default['entities_id'];
                $opt['is_recursive_entities'] = $params['is_recursive_entities'] ?? $default['is_recursive_entities'];

                if (isset($params['itilcategories_id'])
                    && $params['itilcategories_id'] > 0) {
                    $opt['itilcategories_id'] = $params['itilcategories_id'] ?? $default['itilcategories_id'];
                }

                if (isset($params['technicians_groups_id'])
                    && is_array($params['technicians_groups_id'])
                    && count($params['technicians_groups_id']) > 0) {
                    $opt['technicians_groups_id'] = $params['technicians_groups_id'] ?? $default['technicians_groups_id'];
                }

                $params_query["opt"] = $opt;

                //New tickets
                $total_new = self::queryNewTickets($params_query);
                //Late tickets
                $total_due = self::queryDueTickets($params_query);
                //Waiting tickets
                $total_pend = self::queryPendingTickets($params_query);
                //Processing incidents
                $total_incpro = self::queryIncidentTickets($params_query);
                //Processing requests
                $total_dempro = self::queryRequestTickets($params_query);
                //Resolved tickets
                $total_resolved = self::queryResolvedTickets($params_query);
                //Resolved tickets
                $total_closed = 0;

            } elseif ($type == "week") {
                $crits = [
                    "entities_id",
                    "is_recursive_entities",
                    "technicians_groups_id",
                    "itilcategories_id",
                    "year",
                    "week",
                ];

                $params_query["criterias"] = [
                    "entities_id",
                    "is_recursive_entities",
                    "technicians_groups_id",
                    "itilcategories_id",
                    "year",
                    "week",
                ];

                $default = Criteria::manageCriterias($params_query);

                $opt['year'] = $params['year'] ?? $default['year'];

                $opt['week'] = $params['week'] ?? $default['week'];

                //                $params['week'] = '44';
                $total_new = self::commonQueryWeek($params['year'], $params['week'], StockTicketIndicator::NEWT, []);
                //Late tickets
                $total_due = self::commonQueryWeek(
                    $params['year'],
                    $params['week'],
                    StockTicketIndicator::LATET,
                    $technicians_groups_id,
                );
                //Waiting tickets
                $total_pend = self::commonQueryWeek(
                    $params['year'],
                    $params['week'],
                    StockTicketIndicator::PENDINGT,
                    $technicians_groups_id,
                );
                //Processing incidents
                $total_incpro = self::commonQueryWeek(
                    $params['year'],
                    $params['week'],
                    StockTicketIndicator::INCIDENTPROGRESST,
                    $technicians_groups_id,
                );
                //Processing requests
                $total_dempro = self::commonQueryWeek(
                    $params['year'],
                    $params['week'],
                    StockTicketIndicator::REQUESTPROGRESST,
                    $technicians_groups_id,
                );
                //Validate tickets
                //            $total_validate = self::queryValidateTickets($left, $search_assign);
                //Resolved tickets
                $total_resolved = self::commonQueryWeek(
                    $params['year'],
                    $params['week'],
                    StockTicketIndicator::SOLVEDT,
                    $technicians_groups_id,
                );
                //Resolved tickets
                $total_closed = self::commonQueryWeek(
                    $params['year'],
                    $params['week'],
                    StockTicketIndicator::CLOSEDT,
                    $technicians_groups_id,
                );
            }
        }


        // The <a> of each counter is built by alert_indicators_table.html.twig /
        // alert_indicators_widget.html.twig from the url, title, count and style below.
        $size = "";
        if ($iswidget == true) {
            $size = "font-size:18px";
        }

        // Reset criterias
        $options_new['reset'][] = 'reset';

        $options_new['criteria'][] = [
            'field' => 12,//status
            'searchtype' => 'equals',
            'value' => \Ticket::INCOMING,
            'link' => 'AND',
        ];
        if ($type == "week") {
            $options_new['criteria'][] = [
                'field' => 15,//date
                'searchtype' => 'lessthan',
                'value' => $week_end,
                'link' => 'AND',
            ];
            $options_new['criteria'][] = [
                'field' => 15,//date
                'searchtype' => 'morethan',
                'value' => $week_start,
                'link' => 'AND',
            ];
        }


        $href_new = [
            'title' => __('New tickets', 'mydashboard'),
            'url' => $CFG_GLPI["root_doc"] . '/front/ticket.php?' . Toolbox::append_params($options_new, '&'),
            'count' => $total_new,
            'style' => 'color:#D9534F !important;' . $size,
        ];

        //$href_due
        // Reset criterias
        $options_due['reset'][] = 'reset';

        $options_due['criteria'][] = [
            'field' => 12,//status
            'searchtype' => 'equals',
            'value' => 'notold',
            'link' => 'AND',
        ];

        if (isset($params['technicians_groups_id'])
            && is_array($params['technicians_groups_id'])
            && count($params['technicians_groups_id']) > 0) {
            $groups = $params['technicians_groups_id'];
            $nb = 0;
            foreach ($groups as $group) {
                $criterias['criteria'][$nb] = [
                    'field' => 8, // groups_id_assign
                    'searchtype' => 'equals',
                    'value' => $group,
                    'link' => (($nb == 0) ? 'AND' : 'OR'),
                ];
                $nb++;
            }
            $options_due['criteria'][] = $criterias;
        }
        if ($type == "week") {
            $options_due['criteria'][] = [
                'field' => 15,//date
                'searchtype' => 'lessthan',
                'value' => $week_end,
                'link' => 'AND',
            ];
            $options_due['criteria'][] = [
                'field' => 15,//date
                'searchtype' => 'morethan',
                'value' => $week_start,
                'link' => 'AND',
            ];
        }

        $options_due['criteria'][] = [
            'field' => 82,//due date
            'searchtype' => 'equals',
            'value' => 1,
            'link' => 'AND',
        ];

        $href_due = [
            'title' => __('Tickets late', 'mydashboard'),
            'url' => $CFG_GLPI["root_doc"] . '/front/ticket.php?' . Toolbox::append_params($options_due, '&'),
            'count' => $total_due,
            'style' => $size,
        ];

        //$href_pend
        // Reset criterias
        $options_pend['reset'][] = 'reset';

        $options_pend['criteria'][] = [
            'field' => 12,//status
            'searchtype' => 'equals',
            'value' => \Ticket::WAITING,
            'link' => 'AND',
        ];

        if (isset($params['technicians_groups_id'])
            && is_array($params['technicians_groups_id'])
            && count($params['technicians_groups_id']) > 0) {
            $groups = $params['technicians_groups_id'];
            $nb = 0;
            foreach ($groups as $group) {
                $criterias['criteria'][$nb] = [
                    'field' => 8, // groups_id_assign
                    'searchtype' => 'equals',
                    'value' => $group,
                    'link' => (($nb == 0) ? 'AND' : 'OR'),
                ];
                $nb++;
            }
            $options_pend['criteria'][] = $criterias;
        }
        if ($type == "week") {
            $options_pend['criteria'][] = [
                'field' => 15,//date
                'searchtype' => 'lessthan',
                'value' => $week_end,
                'link' => 'AND',
            ];
            $options_pend['criteria'][] = [
                'field' => 15,//date
                'searchtype' => 'morethan',
                'value' => $week_start,
                'link' => 'AND',
            ];
        }

        $href_pend = [
            'title' => __('Pending tickets', 'mydashboard'),
            'url' => $CFG_GLPI["root_doc"] . '/front/ticket.php?' . Toolbox::append_params($options_pend, '&'),
            'count' => $total_pend,
            'style' => $size,
        ];

        //$href_incpro
        // Reset criterias
        $options_incpro['reset'][] = 'reset';

        if ($seeown == false) {
            $options_incpro['criteria'][] = [
                'field' => 12,//status
                'searchtype' => 'equals',
                'value' => 'process',
                'link' => 'AND',
            ];
        } else {
            $options_incpro['criteria'][] = [
                'field' => 12,//status
                'searchtype' => 'equals',
                'value' => 'notold',
                'link' => 'AND',
            ];
        }

        $options_incpro['criteria'][] = [
            'field' => 14, // type
            'searchtype' => 'equals',
            'value' => \Ticket::INCIDENT_TYPE,
            'link' => 'AND',
        ];

        if ($seeown == false) {
            if (isset($params['technicians_groups_id'])
                && is_array($params['technicians_groups_id'])
                && count($params['technicians_groups_id']) > 0) {
                $groups = $params['technicians_groups_id'];
                $nb = 0;
                foreach ($groups as $group) {
                    $criterias['criteria'][$nb] = [
                        'field' => 8, // groups_id_assign
                        'searchtype' => 'equals',
                        'value' => $group,
                        'link' => (($nb == 0) ? 'AND' : 'OR'),
                    ];
                    $nb++;
                }
                $options_incpro['criteria'][] = $criterias;
            }
            if ($type == "week") {
                $options_incpro['criteria'][] = [
                    'field' => 15,//date
                    'searchtype' => 'lessthan',
                    'value' => $week_end,
                    'link' => 'AND',
                ];
                $options_incpro['criteria'][] = [
                    'field' => 15,//date
                    'searchtype' => 'morethan',
                    'value' => $week_start,
                    'link' => 'AND',
                ];
            }
        }

        $href_incpro = [
            'title' => __('Incidents in progress', 'mydashboard'),
            'url' => $CFG_GLPI["root_doc"] . '/front/ticket.php?' . Toolbox::append_params($options_incpro, '&'),
            'count' => $total_incpro,
            'style' => $size,
        ];

        //$href_dempro
        // Reset criterias
        $options_dempro['reset'][] = 'reset';

        if ($seeown == false) {
            $options_dempro['criteria'][] = [
                'field' => 12,//status
                'searchtype' => 'equals',
                'value' => 'process',
                'link' => 'AND',
            ];
        } else {
            $options_dempro['criteria'][] = [
                'field' => 12,//status
                'searchtype' => 'equals',
                'value' => 'notold',
                'link' => 'AND',
            ];
        }

        $options_dempro['criteria'][] = [
            'field' => 14, // type
            'searchtype' => 'equals',
            'value' => \Ticket::DEMAND_TYPE,
            'link' => 'AND',
        ];

        if ($seeown == false) {
            if (isset($params['technicians_groups_id'])
                && is_array($params['technicians_groups_id'])
                && count($params['technicians_groups_id']) > 0) {
                $groups = $params['technicians_groups_id'];
                $nb = 0;
                foreach ($groups as $group) {
                    $criterias['criteria'][$nb] = [
                        'field' => 8, // groups_id_assign
                        'searchtype' => 'equals',
                        'value' => $group,
                        'link' => (($nb == 0) ? 'AND' : 'OR'),
                    ];
                    $nb++;
                }
                $options_dempro['criteria'][] = $criterias;
            }
            if ($type == "week") {
                $options_dempro['criteria'][] = [
                    'field' => 15,//date
                    'searchtype' => 'lessthan',
                    'value' => $week_end,
                    'link' => 'AND',
                ];
                $options_dempro['criteria'][] = [
                    'field' => 15,//date
                    'searchtype' => 'morethan',
                    'value' => $week_start,
                    'link' => 'AND',
                ];
            }
        }

        $href_dempro = [
            'title' => __('Requests in progress', 'mydashboard'),
            'url' => $CFG_GLPI["root_doc"] . '/front/ticket.php?' . Toolbox::append_params($options_dempro, '&'),
            'count' => $total_dempro,
            'style' => $size,
        ];

        ///resolved
        $options_resolved['reset'][] = 'reset';

        $options_resolved['criteria'][] = [
            'field' => 12,//status
            'searchtype' => 'equals',
            'value' => \Ticket::SOLVED,
            'link' => 'AND',
        ];


        if ($seeown == false) {
            if (isset($params['technicians_groups_id'])
                && is_array($params['technicians_groups_id'])
                && count($params['technicians_groups_id']) > 0) {
                $groups = $params['technicians_groups_id'];
                $nb = 0;
                foreach ($groups as $group) {
                    $criterias['criteria'][$nb] = [
                        'field' => 8, // groups_id_assign
                        'searchtype' => 'equals',
                        'value' => $group,
                        'link' => (($nb == 0) ? 'AND' : 'OR'),
                    ];
                    $nb++;
                }
                $options_resolved['criteria'][] = $criterias;
            }
            if ($type == "week") {
                $options_resolved['criteria'][] = [
                    'field' => 16,//solvedate
                    'searchtype' => 'lessthan',
                    'value' => $week_end,
                    'link' => 'AND',
                ];
                $options_resolved['criteria'][] = [
                    'field' => 16,//solvedate
                    'searchtype' => 'morethan',
                    'value' => $week_start,
                    'link' => 'AND',
                ];
            }
        }

        $href_resolved = [
            'title' => __('Tickets solved', 'mydashboard'),
            'url' => $CFG_GLPI["root_doc"] . '/front/ticket.php?' . Toolbox::append_params($options_resolved, '&'),
            'count' => $total_resolved,
            'style' => $size,
        ];

        ///closed
        $options_closed['reset'][] = 'reset';

        $options_closed['criteria'][] = [
            'field' => 12,//status
            'searchtype' => 'equals',
            'value' => \Ticket::CLOSED,
            'link' => 'AND',
        ];


        if ($seeown == false) {
            if (isset($params['technicians_groups_id'])
                && is_array($params['technicians_groups_id'])
                && count($params['technicians_groups_id']) > 0) {
                $groups = $params['technicians_groups_id'];
                $nb = 0;
                foreach ($groups as $group) {
                    $criterias['criteria'][$nb] = [
                        'field' => 8, // groups_id_assign
                        'searchtype' => 'equals',
                        'value' => $group,
                        'link' => (($nb == 0) ? 'AND' : 'OR'),
                    ];
                    $nb++;
                }
                $options_closed['criteria'][] = $criterias;
            }
            if ($type == "week") {
                $options_closed['criteria'][] = [
                    'field' => 17,//closedate
                    'searchtype' => 'lessthan',
                    'value' => $week_end,
                    'link' => 'AND',
                ];
                $options_closed['criteria'][] = [
                    'field' => 17,//closedate
                    'searchtype' => 'morethan',
                    'value' => $week_start,
                    'link' => 'AND',
                ];
            }
        }

        $href_closed = [
            'title' => __('Ticket closed', 'mydashboard'),
            'url' => $CFG_GLPI["root_doc"] . '/front/ticket.php?' . Toolbox::append_params($options_closed, '&'),
            'count' => $total_closed,
            'style' => $size,
        ];


        if ($iswidget == false) {
            TemplateRenderer::getInstance()->display('@mydashboard/alert_indicators_table.html.twig', [
                'cells' => [
                    ['class' => 'ind-new', 'link' => $href_new],
                    ['class' => 'ind-late', 'link' => $href_due],
                    ['class' => 'ind-pending', 'link' => $href_pend],
                    ['class' => 'ind-process', 'link' => $href_incpro],
                    ['class' => 'dem-process', 'link' => $href_dempro],
                ],
            ]);
        } else {
            //         $graph = "<table id='indicators' class='indicators'><tr>";

            $stats = "";
            if ($iswidget == true
                && Session::haveRightsOr(\Ticket::$rightname, [\Ticket::READALL, \Ticket::READGROUP])) {
                $params_header = [
                    "widgetId" => $id,
                    "name" => ($type == "all") ? __("Global indicators", "mydashboard") : __(
                        "Global indicators by week",
                        "mydashboard",
                    ),
                    "onsubmit" => true,
                    "opt" => $opt,
                    "default" => $default,
                    "criterias" => $crits,
                    "export" => false,
                    "canvas" => false,
                    "nb" => 1,
                ];

                $stats .= Helper::getGraphHeader($params_header);
            }

            // The former "$seeown == true" branches (title block and its wrappers) are gone:
            // $seeown is initialised to false at the top of this method and never
            // reassigned, so they could not be reached.
            $cells = [
                ['class' => 'nb ind-widget-new', 'link' => $href_new,
                    'label' => __('New tickets', 'mydashboard'),
                ],
                ['class' => 'nb ind-widget-late', 'link' => $href_due,
                    'label' => __('Tickets late', 'mydashboard'),
                ],
                ['class' => 'nb ind-widget-pending', 'link' => $href_pend,
                    'label' => __('Pending tickets', 'mydashboard'),
                ],
                ['class' => 'nb ind-widget-process', 'link' => $href_incpro,
                    'label' => __('Incidents in progress', 'mydashboard'),
                ],
                ['class' => 'nb dem-widget-process', 'link' => $href_dempro,
                    'label' => __('Requests in progress', 'mydashboard'),
                ],
                ['class' => 'nb ind-widget-solved', 'link' => $href_resolved,
                    'label' => __('Tickets solved', 'mydashboard'),
                ],
            ];
            if ($type == "week") {
                $cells[] = ['class' => 'nb ind-widget-closed', 'link' => $href_closed,
                    'label' => __('Tickets closed', 'mydashboard'),
                ];
            }

            return TemplateRenderer::getInstance()->render('@mydashboard/alert_indicators_widget.html.twig', [
                'header_html' => $stats,
                'cells' => $cells,
            ]);
        }
    }


    /**
     * @param $left
     * @param $criteria
     *
     * @return mixed|\Value
     * @throws \GlpitestSQLError
     */
    public static function queryNewTickets($params)
    {
        global $DB;

        //New tickets
        $is_deleted = ['glpi_tickets.is_deleted' => 0];

        $criteria = [
            'SELECT' => [
                'COUNT' => 'glpi_tickets.id AS total',
            ],
            'DISTINCT' => true,
            'FROM' => 'glpi_tickets',
            'WHERE' => [
                $is_deleted,
                'glpi_tickets.status' => \Ticket::INCOMING,
            ],
        ];

        $criteria = Criteria::addCriteriasForQuery($criteria, $params);

        $iterator = $DB->request($criteria);

        if (count($iterator) == 0) {
            return 0;
        }

        foreach ($iterator as $data) {
            $total_new = $data['total'];
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
    public static function queryDueTickets($params)
    {
        global $DB;

        $is_deleted = ['glpi_tickets.is_deleted' => 0];

        $criteria = [
            'SELECT' => [
                'COUNT' => 'glpi_tickets.id AS due',
            ],
            'DISTINCT' => true,
            'FROM' => 'glpi_tickets',
            'WHERE' => [
                $is_deleted,
                'NOT' => ['glpi_tickets.status' => [\Ticket::WAITING, \Ticket::SOLVED, \Ticket::CLOSED]],
                'time_to_resolve' => ['<', QueryFunction::now()],
            ],
        ];

        $criteria = Criteria::addCriteriasForQuery($criteria, $params);

        $iterator = $DB->request($criteria);

        if (count($iterator) == 0) {
            return 0;
        }

        foreach ($iterator as $data) {
            $total_due = $data['due'];
        }

        return $total_due;
    }

    public static function commonQueryWeek($year, $week, $indicator_id, $technicians_groups_id)
    {
        global $DB;

        $criteria = [
            'SELECT' => [
                'SUM' => 'nbTickets AS total',
            ],
            'FROM' => 'glpi_plugin_mydashboard_stockticketindicators',
            'WHERE' => [
                'indicator_id' => $indicator_id,
                [
                    ['week' => $week],
                    ['year' => $year],
                ],

            ],
        ];

        if (is_array($technicians_groups_id)) {
            $technicians_groups_id = array_filter($technicians_groups_id);
            if (count($technicians_groups_id) > 0) {
                $criteria['WHERE'] = $criteria['WHERE'] + ['groups_id' => $technicians_groups_id];
            }
        }

        $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
            'glpi_plugin_mydashboard_stockticketindicators',
        );

        $iterator = $DB->request($criteria);

        if (count($iterator) == 0) {
            return 0;
        }
        $total = 0;
        foreach ($iterator as $data) {
            if ($data['total']) {
                $total = $data['total'];
            }

        }

        return $total;
    }


    /**
     * @param $left
     * @param $criteria
     * @param $search_assign
     *
     * @return mixed|\Value
     * @throws \GlpitestSQLError
     */
    public static function queryPendingTickets($params)
    {
        global $DB;

        //        $dbu = new DbUtils();
        //        $sql_pend = "SELECT COUNT(DISTINCT glpi_tickets.id) as total
        //                  FROM glpi_tickets
        //                  $left
        //                  WHERE $criteria
        //                        AND ($search_assign)
        //                        $category_criteria
        //                        AND `glpi_tickets`.`status` = " . \Ticket::WAITING . " "
        //            . $dbu->getEntitiesRestrictRequest("AND", "glpi_tickets");

        $is_deleted = ['glpi_tickets.is_deleted' => 0];

        $criteria = [
            'SELECT' => [
                'COUNT' => 'glpi_tickets.id AS total',
            ],
            'DISTINCT' => true,
            'FROM' => 'glpi_tickets',
            'WHERE' => [
                $is_deleted,
                'glpi_tickets.status' => \Ticket::WAITING,
            ],
        ];

        $criteria = Criteria::addCriteriasForQuery($criteria, $params);

        $iterator = $DB->request($criteria);

        if (count($iterator) == 0) {
            return 0;
        }

        foreach ($iterator as $data) {
            $total_pend = $data['total'];
        }

        return $total_pend;
    }


    /**
     * @param $left
     * @param $criteria
     * @param $search_assign
     *
     * @return mixed|\Value
     * @throws \GlpitestSQLError
     */
    public static function queryIncidentTickets($params)
    {
        global $DB;

        $dbu = new DbUtils();
        $statuses = [\Ticket::SOLVED, \Ticket::CLOSED, \Ticket::WAITING, \Ticket::INCOMING];
        if (Session::getCurrentInterface() == 'helpdesk') {
            $statuses = [\Ticket::SOLVED, \Ticket::CLOSED];
        }

        //        $sql_incpro = "SELECT COUNT(DISTINCT glpi_tickets.id) as total
        //                  FROM glpi_tickets
        //                  $left
        //                  WHERE $criteria
        //                        AND ($search_assign)
        //                        $category_criteria
        //                        AND `glpi_tickets`.`type` = '" . \Ticket::INCIDENT_TYPE . "'
        //                        AND `glpi_tickets`.`status` NOT IN (" . implode(",", $statuses) . ") ";
        //        $sql_incpro .= $dbu->getEntitiesRestrictRequest("AND", "glpi_tickets");

        $is_deleted = ['glpi_tickets.is_deleted' => 0];

        $criteria = [
            'SELECT' => [
                'COUNT' => 'glpi_tickets.id AS total',
            ],
            'DISTINCT' => true,
            'FROM' => 'glpi_tickets',
            'WHERE' => [
                $is_deleted,
                'glpi_tickets.type' => \Ticket::INCIDENT_TYPE,
                ['glpi_tickets.status' => ['NOT IN', $statuses]],
            ],
        ];

        $criteria = Criteria::addCriteriasForQuery($criteria, $params);

        $iterator = $DB->request($criteria);

        if (count($iterator) == 0) {
            return 0;
        }

        foreach ($iterator as $data) {
            $total_incpro = $data['total'];
        }

        return $total_incpro;
    }


    /**
     * @param $left
     * @param $criteria
     * @param $search_assign
     *
     * @return mixed|\Value
     * @throws \GlpitestSQLError
     */
    public static function queryRequestTickets($params)
    {
        global $DB;

        //        $dbu = new DbUtils();

        $statuses = [\Ticket::SOLVED, \Ticket::CLOSED, \Ticket::WAITING, \Ticket::INCOMING];
        if (Session::getCurrentInterface() == 'helpdesk') {
            $statuses = [\Ticket::SOLVED, \Ticket::CLOSED];
        }

        //        $sql_dempro = "SELECT COUNT(DISTINCT glpi_tickets.id) as total
        //                  FROM glpi_tickets
        //                  $left
        //                  WHERE $criteria
        //                        AND ($search_assign)
        //                        $category_criteria
        //                        AND `glpi_tickets`.`type` = '" . \Ticket::DEMAND_TYPE . "'
        //                        AND `glpi_tickets`.`status` NOT IN (" . implode(",", $statuses) . ") ";
        //        $sql_dempro .= $dbu->getEntitiesRestrictRequest("AND", "glpi_tickets");

        $is_deleted = ['glpi_tickets.is_deleted' => 0];

        $criteria = [
            'SELECT' => [
                'COUNT' => 'glpi_tickets.id AS total',
            ],
            'DISTINCT' => true,
            'FROM' => 'glpi_tickets',
            'WHERE' => [
                $is_deleted,
                'glpi_tickets.type' => \Ticket::DEMAND_TYPE,
                ['glpi_tickets.status' => ['NOT IN', $statuses]],
            ],
        ];

        $criteria = Criteria::addCriteriasForQuery($criteria, $params);

        $iterator = $DB->request($criteria);

        if (count($iterator) == 0) {
            return 0;
        }

        foreach ($iterator as $data) {
            $total_dempro = $data['total'];
        }

        return $total_dempro;
    }

    /**
     * @param $left
     * @param $criteria
     * @param $search_assign
     *
     * @return mixed|\Value
     * @throws \GlpitestSQLError
     */
    public static function queryResolvedTickets($params)
    {
        global $DB;

        //        $dbu = new DbUtils();
        //        $week = date('W');
        //        $year = date('Y');
        //        $sql_res = "SELECT COUNT(DISTINCT glpi_tickets.id) as total
        //                  FROM glpi_tickets
        //                  $left
        //                  WHERE $criteria
        //                        AND ($search_assign)
        //                        $category_criteria
        //                        AND `glpi_tickets`.`status` = " . \Ticket::SOLVED . " ";
        //        $sql_res .= $dbu->getEntitiesRestrictRequest("AND", "glpi_tickets");

        $is_deleted = ['glpi_tickets.is_deleted' => 0];

        $criteria = [
            'SELECT' => [
                'COUNT' => 'glpi_tickets.id AS total',
            ],
            'DISTINCT' => true,
            'FROM' => 'glpi_tickets',
            'WHERE' => [
                $is_deleted,
                'glpi_tickets.status' => \Ticket::SOLVED,
            ],
        ];

        $criteria = Criteria::addCriteriasForQuery($criteria, $params);

        $iterator = $DB->request($criteria);

        if (count($iterator) == 0) {
            return 0;
        }

        foreach ($iterator as $data) {
            $total_res = $data['total'];
        }

        return $total_res;
    }

    public static function install(Migration $migration)
    {
        global $DB;

        $default_charset = DBConnection::getDefaultCharset();
        $default_collation = DBConnection::getDefaultCollation();
        $default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();
        $table = self::getTable();

        if (!$DB->tableExists($table)) {
            $query = "CREATE TABLE `$table` (
                        `id` int {$default_key_sign} NOT NULL auto_increment,
                        `reminders_id`      int {$default_key_sign} NOT NULL,
                        `impact`            tinyint      NOT NULL,
                        `type`              tinyint      NOT NULL,
                        `is_public`         tinyint      NOT NULL,
                        `itilcategories_id` int {$default_key_sign} NOT NULL DEFAULT '0',
                        PRIMARY KEY (`id`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);
        }

        if (!$DB->fieldExists($table, "is_public")) {
            $migration->addField($table, "is_public", "tinyint NOT NULL");
            $migration->migrationOneTable($table);
        }

        if (!$DB->fieldExists($table, "type")) {
            $migration->addField($table, "type", "tinyint NOT NULL");
            $migration->migrationOneTable($table);
        }

        if (!$DB->fieldExists($table, "itilcategories_id")) {
            $migration->addField($table, "itilcategories_id", "int {$default_key_sign} NOT NULL DEFAULT '0'");
            $migration->migrationOneTable($table);
        }

        $DB->update(
            $table,
            ['itilcategories_id' => 0],
            ['itilcategories_id' => -1],
        );
    }

    public static function uninstall()
    {
        global $DB;

        $DB->dropTable(self::getTable(), true);
    }
}
