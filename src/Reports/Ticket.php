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
use CommonITILValidation;
use DbUtils;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use Glpi\RichText\RichText;
use GlpiPlugin\Mydashboard\Config;
use GlpiPlugin\Mydashboard\Datatable;
use GlpiPlugin\Mydashboard\Html as MydashboardHtml;
use GlpiPlugin\Mydashboard\Menu;
use GlpiPlugin\Mydashboard\Widget;
use ITILCategory;
use ITILFollowup;
use Search;
use Session;
use TicketTask;
use TicketValidation;
use Toolbox;

/**
 * This class extends GLPI class ticket to add the functions to display widgets on Dashboard
 */
class Ticket extends CommonGLPI
{
    /**
     * @param int $nb
     *
     * @return string|\translated
     */
    public static function getTypeName($nb = 0)
    {
        return __('Tickets');
    }

    /**
     * @return array
     */
    public function getWidgetsForItem()
    {
        $showticket = Session::haveRightsOr("ticket", [\Ticket::READMY, \Ticket::READALL, \Ticket::READASSIGN]);
        $createticket = Session::haveRight("ticket", CREATE);

        $widgets = [
            Menu::$TICKET_REQUESTERVIEW => [
                "ticketlistrequestbyselfwidget" => [
                    "title" => __('Your tickets in progress'),
                    "type" => Widget::$TABLE,
                    "comment" => ""
                ],
                "ticketlistobservedwidget" => [
                    "title" => __('Your observed tickets'),
                    "type" => Widget::$TABLE,
                    "comment" => ""
                ],
                "ticketlistrejectedwidget" => [
                    "title" => __('Your rejected tickets', 'mydashboard'),
                    "type" => Widget::$TABLE,
                    "comment" => ""
                ],
                "ticketlisttoapprovewidget" => [
                    "title" => __('Your tickets to close'),
                    "type" => Widget::$TABLE,
                    "comment" => ""
                ],
                "ticketlistsurveywidget" => [
                    "title" => __('Your satisfaction surveys', 'mydashboard'),
                    "type" => Widget::$TABLE,
                    "comment" => ""
                ],
            ],
        ];
        if (Session::haveRightsOr('ticketvalidation', TicketValidation::getValidateRights())) {
            $widgets[Menu::$TICKET_REQUESTERVIEW]["ticketlisttovalidatewidget"] = [
                "title" => __('Your tickets to validate', "mydashboard"),
                "type" => Widget::$TABLE,
                "comment" => ""
            ];
        }
        if ($showticket) {
            $widgets[Menu::$TICKET_TECHVIEW]["ticketcountwidget2"] = [
                "title" => __('New tickets', 'mydashboard'),
                "type" => Widget::$TABLE,
                "comment" => ""
            ];
            $widgets[Menu::$TICKET_TECHVIEW]["ticketlistprocesswidget"] = [
                "title" => __('Tickets to be processed'),
                "type" => Widget::$TABLE,
                "comment" => ""
            ];
            $widgets[Menu::$TICKET_TECHVIEW]["ticketlistwaitingwidget"] = [
                "title" => __('Tickets on pending status'),
                "type" => Widget::$TABLE,
                "comment" => ""
            ];
            $widgets[Menu::$TICKET_TECHVIEW]["tickettaskstodowidget"] = [
                "title" => __("Ticket tasks to do"),
                "type" => Widget::$TABLE,
                "comment" => ""
            ];
        }
        if (Session::haveRight('ticket', \Ticket::READGROUP)) {
            $widgets[Menu::$TICKET_TECHVIEW]["ticketlistwaitingwidgetgroup"] = [
                "title" => __('Tickets on pending status'),
                "type" => Widget::$TABLE,
                "comment" => ""
            ];
            $widgets[Menu::$TICKET_TECHVIEW]["ticketlisttoapprovewidgetgroup"] = [
                "title" => __('Your tickets to close'),
                "type" => Widget::$TABLE,
                "comment" => ""
            ];
            $widgets[Menu::$TICKET_TECHVIEW]["ticketlistrequestbyselfwidgetgroup"] = [
                "title" => __('Your tickets in progress'),
                "type" => Widget::$TABLE,
                "comment" => ""
            ];
            $widgets[Menu::$TICKET_TECHVIEW]["ticketlistobservedwidgetgroup"] = [
                "title" => __('Your observed tickets'),
                "type" => Widget::$TABLE,
                "comment" => ""
            ];
        }
        if ($showticket) {
            $widgets[Menu::$GROUP_VIEW]["ticketlistprocesswidgetgroup"] = [
                "title" => __('Tickets to be processed'),
                "type" => Widget::$TABLE,
                "comment" => ""
            ];
            $widgets[Menu::$GROUP_VIEW]["tickettaskstodowidgetgroup"] = [
                "title" => __("Ticket tasks to do"),
                "type" => Widget::$TABLE,
                "comment" => ""
            ];
        }
        if ($showticket || $createticket) {
            $widgets[Menu::$HELPDESK]["ticketcountwidget"] = [
                "title" => __('Ticket followup', 'mydashboard'),
                "type" => Widget::$TABLE,
                "comment" => ""
            ];
        }

        return $widgets;
    }


    /**
     * @param $widgetId
     *
     * @return bool|Datatable|string
     */
    public function getWidgetContentForItem($widgetId)
    {
        $showticket = Session::haveRightsOr("ticket", [\Ticket::READMY, \Ticket::READALL, \Ticket::READASSIGN]);
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
                if (Session::haveRight('ticket', \Ticket::READGROUP)) {
                    return self::showCentralList(0, "waiting", true);
                };
                break;
            case "ticketlisttoapprovewidgetgroup":
                if (Session::haveRight('ticket', \Ticket::READGROUP)) {
                    return self::showCentralList(0, "toapprove", true);
                }
                break;
            case "ticketlistrequestbyselfwidgetgroup":
                if (Session::haveRight('ticket', \Ticket::READGROUP)) {
                    return self::showCentralList(0, "requestbyself", true);
                }
                break;
            case "ticketlistobservedwidgetgroup":
                if (Session::haveRight('ticket', \Ticket::READGROUP)) {
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
                break;
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
     * @return Datatable|string
     */
    public static function showCentralList($start, $status = "process", $showgrouptickets = true)
    {
        global $DB, $CFG_GLPI;

        $output = [];

        if (!Session::haveRightsOr(\Ticket::$rightname, [CREATE, \Ticket::READALL, \Ticket::READASSIGN])
            && !Session::haveRightsOr('ticketvalidation', TicketValidation::getValidateRights())) {
            return false;
        }

        $search_users_id = [
            'glpi_tickets_users.users_id' => Session::getLoginUserID(),
            'glpi_tickets_users.type' => CommonITILActor::REQUESTER
        ];

        $search_assign = [
            'glpi_tickets_users.users_id' => Session::getLoginUserID(),
            'glpi_tickets_users.type' => CommonITILActor::ASSIGN
        ];

        $search_observer = [
            'glpi_tickets_users.users_id' => Session::getLoginUserID(),
            'glpi_tickets_users.type' => CommonITILActor::OBSERVER
        ];


        if ($showgrouptickets) {
            $search_users_id = [];
            $search_assign = [];
            $search_observer = [];

            if (count($_SESSION['glpigroups'])) {
                $search_assign = [
                    'glpi_groups_tickets.groups_id' => $_SESSION['glpigroups'],
                    'glpi_groups_tickets.type' => CommonITILActor::ASSIGN
                ];

                if (Session::haveRight(\Ticket::$rightname, \Ticket::READGROUP)) {
                    $search_users_id = [
                        'glpi_groups_tickets.groups_id' => $_SESSION['glpigroups'],
                        'glpi_groups_tickets.type' => CommonITILActor::REQUESTER
                    ];
                }
                if (Session::haveRight(\Ticket::$rightname, \Ticket::READGROUP)) {
                    $search_observer = [
                        'glpi_groups_tickets.groups_id' => $_SESSION['glpigroups'],
                        'glpi_groups_tickets.type' => CommonITILActor::OBSERVER
                    ];
                }
            }
        }
        $criteria = [
            'SELECT' => 'glpi_tickets.id',
            'DISTINCT' => true,
            'FROM' => 'glpi_tickets',
            'LEFT JOIN' => [
                'glpi_tickets_users' => [
                    'ON' => [
                        'glpi_tickets' => 'id',
                        'glpi_tickets_users' => 'tickets_id',
                    ],
                ],
                'glpi_groups_tickets' => [
                    'ON' => [
                        'glpi_tickets' => 'id',
                        'glpi_groups_tickets' => 'tickets_id',
                    ],
                ],
            ],
            'WHERE' => ['glpi_tickets.is_deleted' => 0],
            'ORDERBY' => 'glpi_tickets.date_mod DESC',
        ];

        switch ($status) {
            case "waiting": // on affiche les tickets en attente

                $criteria['WHERE'] = $criteria['WHERE'] + $search_assign;

                $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_tickets.status' => \Ticket::WAITING];

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                        'glpi_tickets'
                    );

                break;

            case "process": // on affiche les tickets planifiés ou assignés au user

                $criteria['WHERE'] = $criteria['WHERE'] + $search_assign;

                $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_tickets.status' => \Ticket::getProcessStatusArray()];

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                        'glpi_tickets'
                    );

                break;

            case "toapprove": // on affiche les tickets planifiés ou assignés au user

                $criteria['WHERE'] = $criteria['WHERE'] + $search_users_id;

                $criteria['WHERE'] = $criteria['WHERE'] + ['glpi_tickets.status' => \Ticket::SOLVED];

                if (!$showgrouptickets) {
                    $criteria['WHERE'] = $criteria['WHERE'] + [
                            'OR' => [
                                'glpi_tickets.users_id_recipient' => Session::getLoginUserID(),
                            ]
                        ];
                }

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                        'glpi_tickets'
                    );

                break;

            case "tovalidate": // on affiche les tickets à valider

                $criteria['LEFT JOIN'] = $criteria['LEFT JOIN'] + [
                        'glpi_ticketvalidations' => [
                            'ON' => [
                                'glpi_ticketvalidations' => 'tickets_id',
                                'glpi_tickets' => 'id'
                            ]
                        ]
                    ];

                $criteria['WHERE'] = $criteria['WHERE'] + [
                        'glpi_ticketvalidations.users_id_validate' => Session::getLoginUserID(),
                        'glpi_ticketvalidations.status' => CommonITILValidation::WAITING,
                        'glpi_tickets.status' => \Ticket::getNotSolvedStatusArray(),
                    ];

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                        'glpi_tickets'
                    );

                break;

            case "rejected": // on affiche les tickets rejetés

                $criteria['WHERE'] = $criteria['WHERE'] + $search_assign;

                $criteria['WHERE'] = $criteria['WHERE'] + [
                        'glpi_tickets.status' => ['<>', \Ticket::CLOSED],
                        'glpi_tickets.global_validation' => CommonITILValidation::REFUSED
                    ];

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                        'glpi_tickets'
                    );
                break;

            case "observed":

                $criteria['WHERE'] = $criteria['WHERE'] + $search_observer;

                $criteria['WHERE'] = $criteria['WHERE'] + [
                        'glpi_tickets.status' => [
                            \Ticket::INCOMING,
                            \Ticket::PLANNED,
                            \Ticket::ASSIGNED,
                            \Ticket::WAITING
                        ]
                    ];

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                        'glpi_tickets'
                    );

                break;

            case "survey": // on affiche les tickets dont l'enquête de satisfaction n'est pas remplie

                $criteria['INNER JOIN'] = [
                    'glpi_ticketsatisfactions' => [
                        'ON' => [
                            'glpi_ticketsatisfactions' => 'tickets_id',
                            'glpi_tickets' => 'id'
                        ]
                    ]
                ];

                $criteria['WHERE'] = $criteria['WHERE'] + $search_users_id + [
                        'OR' => [
                            'glpi_tickets.users_id_recipient' => Session::getLoginUserID(),
                        ]
                    ];

                $criteria['WHERE'] = $criteria['WHERE'] + [
                        'glpi_ticketsatisfactions.date_answered' => null,
                        'glpi_tickets.status' => \Ticket::CLOSED
                    ];

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                        'glpi_tickets'
                    );

                break;

            case "requestbyself": // on affiche les tickets demandés le user qui sont planifiés ou assignés
                // à quelqu'un d'autre (exclut les self-tickets)

            default:

                $criteria['WHERE'] = $criteria['WHERE'] + $search_users_id;

                $criteria['WHERE'] = $criteria['WHERE'] + [
                        'glpi_tickets.status' => [
                            \Ticket::INCOMING,
                            \Ticket::PLANNED,
                            \Ticket::ASSIGNED,
                            \Ticket::WAITING
                        ]
                    ];

                $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                        'glpi_tickets'
                    );
        }


        $iterator = $DB->request($criteria);
        $numrows = count($iterator);


        $output['header'][] = __('ID and priority', 'mydashboard');
        $output['header'][] = __('Requester');
        $output['header'][] = __('Associated element');
        $output['header'][] = __('Description');
        $output['header'][] = __('ID');
        $output['header'][] = __('Priority');
        $output['header'][] = __('Category');
        $output['header'][] = __('Status');
        $output['body'] = [];
        $output['title'] = "default";

        //if ($numrows > 0) {
        $options['reset'] = 'reset';
        $forcetab = '';
        $num = 0;
        if ($showgrouptickets) {
            switch ($status) {
                case "toapprove":
                    $options = Toolbox::append_params([
                        'reset' => 'reset',
                        'criteria' => [
                            0 => [
                                'value' => $_SESSION['glpigroups'],
                                'searchtype' => 'equals',
                                'field' => 71,
                                'link' => 'AND',
                            ],
                            1 => [
                                'value' => 'process',
                                'searchtype' => 'equals',
                                'field' => 12,
                                'link' => 'AND',
                            ],
                        ],
                    ]);

                    $forcetab = 'Ticket$2';

                    $output['title'] = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.php?"
                        . $options . "\">"
                        . \Html::makeTitle(__('Your tickets to close'), $numrows, $numrows) . "</a>";
                    break;

                case "waiting":
                    $options = Toolbox::append_params([
                        'reset' => 'reset',
                        'criteria' => [
                            0 => [
                                'value' => $_SESSION['glpigroups'],
                                'searchtype' => 'equals',
                                'field' => 8,
                                'link' => 'AND',
                            ],
                            1 => [
                                'value' => \Ticket::WAITING,
                                'searchtype' => 'equals',
                                'field' => 12,
                                'link' => 'AND',
                            ],
                        ],
                    ]);


                    $output['title'] = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.php?"
                        . $options . "\">"
                        . \Html::makeTitle(__('Tickets on pending status'), $numrows, $numrows) . "</a>";
                    break;

                case "process":
                    $options = Toolbox::append_params([
                        'reset' => 'reset',
                        'criteria' => [
                            0 => [
                                'value' => $_SESSION['glpigroups'],
                                'searchtype' => 'equals',
                                'field' => 8,
                                'link' => 'AND',
                            ],
                            1 => [
                                'value' => 'process',
                                'searchtype' => 'equals',
                                'field' => 12,
                                'link' => 'AND',
                            ],
                        ],
                    ]);

                    $output['title'] = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.php?"
                        . $options . "\">"
                        . \Html::makeTitle(__('Tickets to be processed'), $numrows, $numrows) . "</a>";
                    break;

                case "observed":
                    $options = Toolbox::append_params([
                        'reset' => 'reset',
                        'criteria' => [
                            0 => [
                                'value' => $_SESSION['glpigroups'],
                                'searchtype' => 'equals',
                                'field' => 65,
                                'link' => 'AND',
                            ],
                            1 => [
                                'value' => 'notold',
                                'searchtype' => 'equals',
                                'field' => 12,
                                'link' => 'AND',
                            ],
                        ],
                    ]);

                    $output['title'] = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.php?"
                        . $options . "\">"
                        . \Html::makeTitle(__('Your observed tickets'), $numrows, $numrows) . "</a>";
                    break;

                case "requestbyself":
                default:

                    $options = Toolbox::append_params([
                        'reset' => 'reset',
                        'criteria' => [
                            0 => [
                                'value' => $_SESSION['glpigroups'],
                                'searchtype' => 'equals',
                                'field' => 71,
                                'link' => 'AND',
                            ],
                            1 => [
                                'value' => 'notold',
                                'searchtype' => 'equals',
                                'field' => 12,
                                'link' => 'AND',
                            ],
                        ],
                    ]);


                    $output['title'] = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.php?"
                        . $options . "\">"
                        . \Html::makeTitle(__('Your tickets in progress'), $numrows, $numrows) . "</a>";
            }
        } else {
            switch ($status) {
                case "waiting":
                    $options = Toolbox::append_params([
                        'reset'      => 'reset',
                        'criteria'   => [
                            0 => [
                                'value'      => Session::getLoginUserID(),
                                'searchtype' => 'equals',
                                'field'      => 5,
                                'link'       => 'AND',
                            ],
                            1 => [
                                'value'      => \Ticket::WAITING,
                                'searchtype' => 'equals',
                                'field'      => 12,
                            ],
                        ],
                    ]);

                    $output['title'] = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.php?"
                        . $options . "\">"
                        . \Html::makeTitle(__('Tickets on pending status'), $numrows, $numrows) . "</a>";
                    break;

                case "process":
                    $options = Toolbox::append_params([
                        'reset'      => 'reset',
                        'criteria'   => [
                            0 => [
                                'value'      => Session::getLoginUserID(),
                                'searchtype' => 'equals',
                                'field'      => 5,
                                'link'       => 'AND',
                            ],
                            1 => [
                                'value'      => 'process',
                                'searchtype' => 'equals',
                                'field'      => 12,
                            ],
                        ],
                    ]);

                    $output['title'] = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.php?"
                        . $options . "\">"
                        . \Html::makeTitle(__('Tickets to be processed'), $numrows, $numrows) . "</a>";
                    break;

                case "tovalidate":

                    $options = Toolbox::append_params([
                        'reset'      => 'reset',
                        'criteria'   => [
                            0 => [
                                'value'      => Session::getLoginUserID(),
                                'searchtype' => 'equals',
                                'field'      => 59,
                                'link'       => 'AND',
                            ],
                            1 => [
                                'value'      => 'old',
                                'searchtype' => 'equals',
                                'field'      => 12,
                            ],
                            2 => [
                                'value'      => CommonITILValidation::WAITING,
                                'searchtype' => 'equals',
                                'field'      => 55,
                                'link'       => 'AND NOT',
                            ],
                        ],
                    ]);

                    $forcetab = 'TicketValidation$1';

                    $output['title'] = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.php?"
                        . $options . "\">"
                        . \Html::makeTitle(__('Your tickets to validate', "mydashboard"), $numrows, $numrows) . "</a>";

                    break;

                case "rejected":

                    $options = Toolbox::append_params([
                        'reset'      => 'reset',
                        'criteria'   => [
                            0 => [
                                'value'      => Session::getLoginUserID(),
                                'searchtype' => 'equals',
                                'field'      => 5,
                                'link'       => 'AND',
                            ],
                            1 => [
                                'value'      => CommonITILValidation::REFUSED,
                                'searchtype' => 'equals',
                                'field'      => 52,
                            ],
                        ],
                    ]);

                    $output['title'] = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.php?"
                        . $options . "\">"
                        . \Html::makeTitle(__('Your rejected tickets'), $numrows, $numrows) . "</a>";

                    break;

                case "toapprove":

                    $options = Toolbox::append_params([
                        'reset'      => 'reset',
                        'criteria'   => [
                            0 => [
                                'value'      => \Ticket::SOLVED,
                                'searchtype' => 'equals',
                                'field'      => 12,
                                'link'       => 'AND',
                            ],
                            1 => [
                                'value'      => Session::getLoginUserID(),
                                'searchtype' => 'equals',
                                'field'      => 4,
                                'link'       => 'AND',
                            ],
                            2 => [
                                'value'      => Session::getLoginUserID(),
                                'searchtype' => 'equals',
                                'field'      => 22,
                                'link'       => 'OR',
                            ],
                            2 => [
                                'value'      => Session::getLoginUserID(),
                                'searchtype' => 'equals',
                                'field'      => 22,
                                'link'       => 'OR',
                            ],
                        ],
                    ]);

                    $forcetab = 'Ticket$2';

                    $output['title'] = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.php?"
                        . $options . "\">"
                        . \Html::makeTitle(__('Your tickets to close'), $numrows, $numrows) . "</a>";
                    break;

                case "observed":

                    $options = Toolbox::append_params([
                        'reset'      => 'reset',
                        'criteria'   => [
                            0 => [
                                'value'      => Session::getLoginUserID(),
                                'searchtype' => 'equals',
                                'field'      => 66,
                                'link'       => 'AND',
                            ],
                            1 => [
                                'value'      => 'notold',
                                'searchtype' => 'equals',
                                'field'      => 12,
                            ],
                        ],
                    ]);

                    $output['title'] = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.php?"
                        . $options . "\">"
                        . \Html::makeTitle(__('Your observed tickets'), $numrows, $numrows) . "</a>";
                    break;

                case "survey":

                    $options = Toolbox::append_params([
                        'reset'      => 'reset',
                        'criteria'   => [
                            0 => [
                                'value'      => \Ticket::CLOSED,
                                'searchtype' => 'equals',
                                'field'      => 12,
                                'link'       => 'AND',
                            ],
                            1 => [
                                'value'      => '^',
                                'searchtype' => 'contains',
                                'field'      => 60,
                                'link'       => 'AND',
                            ],
                            2 => [
                                'value'      => '^',
                                'searchtype' => 'contains',
                                'field'      => 61,
                                'link'       => 'AND',
                            ],
                            2 => [
                                'value'      => Session::getLoginUserID(),
                                'searchtype' => 'equals',
                                'field'      => 22,
                                'link'       => 'OR',
                            ],
                        ],
                    ]);

                    $forcetab = 'Ticket$3';

                    $output['title'] = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.php?"
                        . $options . "\">"
                        . \Html::makeTitle(__('Satisfaction survey'), $numrows, $numrows) . "</a>";
                    break;

                case "requestbyself":
                default:

                    $options = Toolbox::append_params([
                        'reset' => 'reset',
                        'criteria' => [
                            0 => [
                                'value' => Session::getLoginUserID(),
                                'searchtype' => 'equals',
                                'field' => 4,
                                'link' => 'AND',
                            ],
                            1 => [
                                'value' => 'notold',
                                'searchtype' => 'equals',
                                'field' => 12,
                            ],
                        ],
                    ]);

                    $output['title'] = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.php?"
                        . $options . "\">"
                        . \Html::makeTitle(__('Your tickets in progress'), $numrows, $numrows) . "</a>";
            }
        }

        foreach ($iterator as $data) {
            $ID = $data["id"];
            $output['body'][] = self::showVeryShort($ID, $forcetab);
        }

        if (!empty($output)) {
            $widget = new Datatable();

            $group = ($showgrouptickets) ? "group" : "";

            $widget->setWidgetTitle($output['title']);

            $widget->setWidgetId("ticketlist" . $status . "widget" . $group);
            //We set the datas of the widget (which will be later automatically formatted by the method getJSonData of Datatable)
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
     * @return Datatable|string
     */
    public static function showCentralTaskList($start, $status = "todo", $showgrouptickets = true)
    {
        global $CFG_GLPI;

        $req = TicketTask::getTaskList($status, $showgrouptickets);
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
            $req = TicketTask::getTaskList($status, $showgrouptickets, $start, $limit);
            $number = $req->numrows();
        }

        $itemtype = "TicketTask";
        $type = "";
        if ($itemtype == "TicketTask") {
            $type = \Ticket::getTypeName();
        } elseif ($itemtype == "ProblemTask") {
            $type = \Problem::getTypeName();
        }

        $output['header'][] = __('ID and priority', 'mydashboard');
        $output['header'][] = __('Title') . " (" . strtolower($type) . ")";
        $output['header'][] = __('Description');
        $output['header'][] = __('ID');
        $output['header'][] = __('Priority');
        $output['header'][] = __('Category');
        $output['body'] = [];
        $output['title'] = "default";

        //if ($numrows > 0) {
        $options['reset'] = 'reset';
        $forcetab = '';
        $num = 0;

        switch ($status) {
            case "todo":
                $options['criteria'][0]['field'] = 12; // status
                $options['criteria'][0]['searchtype'] = 'equals';
                $options['criteria'][0]['value'] = "notold";
                $options['criteria'][0]['link'] = 'AND';
                if ($showgrouptickets) {
                    $options['criteria'][1]['field'] = 112; // tech in charge of task
                    $options['criteria'][1]['searchtype'] = 'equals';
                    $options['criteria'][1]['value'] = 'mygroups';
                    $options['criteria'][1]['link'] = 'AND';
                } else {
                    $options['criteria'][1]['field'] = 95; // tech in charge of task
                    $options['criteria'][1]['searchtype'] = 'equals';
                    $options['criteria'][1]['value'] = $_SESSION['glpiID'];
                    $options['criteria'][1]['link'] = 'AND';
                }
                $options['criteria'][2]['field'] = 33; // task status
                $options['criteria'][2]['searchtype'] = 'equals';
                $options['criteria'][2]['value'] = \Planning::TODO;
                $options['criteria'][2]['link'] = 'AND';

                if ($itemtype == "TicketTask") {
                    $title = __("Ticket tasks to do");
                } elseif ($itemtype == "ProblemTask") {
                    $title = __("Problem tasks to do");
                }
                $output['title'] = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.php?"
                    . Toolbox::append_params($options, '&amp;') . "\">"
                    . \Html::makeTitle($title, $number, $numrows) . "</a>";
                break;
        }
        if ($req !== false) {
            foreach ($req as $id => $row) {
                $output['body'][] = self::showVeryShortTask($id, $itemtype);
            }
        }
        if (!empty($output)) {
            $widget = new Datatable();

            $group = ($showgrouptickets) ? "group" : "";

            $widget->setWidgetTitle($output['title']);

            $widget->setWidgetId("tickettasks" . $status . "widget" . $group);
            //We set the datas of the widget (which will be later automatically formatted by the method getJSonData of Datatable)
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
    public static function showVeryShort($ID, $forcetab)
    {
        global $CFG_GLPI;

        $colnum = 0;
        $output = [];

        // Prints a job in short form
        // Should be called in a <table>-segment
        // Print links or not in case of user view
        // Make new job object and fill it from database, if success, print it
        $showprivate = Session::haveRight("show_full_ticket", 1);

        $job = new \Ticket();
        $rand = mt_rand();
        if ($job->getFromDBwithData($ID, 0)) {
            $bgcolor = $_SESSION["glpipriority_" . $job->fields["priority"]];
            $textColor = "color:black!important;";
            if ($bgcolor == '#000000') {
                $textColor = "color:white!important;";
            }

            $link = "<a id='ticket" . $job->fields["id"] . $rand . "' href='" . $CFG_GLPI["root_doc"]
                . "/front/ticket.form.php?id=" . $job->fields["id"];
            if ($forcetab != '') {
                $link .= "&amp;forcetab=" . $forcetab;
            }
            $link .= "'>";


            $output[$colnum] = "<div class='center' style='background-color:$bgcolor; padding: 10px;'>"
                . $link
                . sprintf(__('%1$s: %2$s'), __('ID'), $job->fields["id"]) . "</a></div>";

            $colnum++;
            $output[$colnum] = '';
            $userrequesters = $job->getUsers(CommonITILActor::REQUESTER);
            if (isset($userrequesters)
                && count($userrequesters)
            ) {
                foreach ($userrequesters as $d) {
                    if ($d["users_id"] > 0) {
                        $userdata = getUserName($d["users_id"]);
                        $name = "<div class='b center'>" . $userdata;
                        //                        $name     = sprintf(
                        //                            __('%1$s %2$s'),
                        //                            $name,
                        //                            Html::showToolTip(
                        //                                $userdata["comment"],
                        //                                ['link'    => $userdata["link"],
                        //                                 'display' => false]
                        //                            )
                        //                        );

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
                    } elseif ($hardwaredatas) {
                        $output[$colnum] .= $hardwaredatas->getTypeName() . " - ";
                        $output[$colnum] .= "<span class='b'>" . $hardwaredatas->getNameID() . "</span><br/>";
                    }
                }
            } else {
                $output[$colnum] .= __('General');
            }

            $colnum++;

            $link .= "<span class='b'>" . $job->getNameID() . "</span></a>";
            $link = sprintf(
                __('%1$s (%2$s)'),
                $link,
                sprintf(
                    __('%1$s - %2$s'),
                    $job->numberOfFollowups($showprivate),
                    $job->numberOfTasks($showprivate)
                )
            );
            $link = sprintf(
                __('%1$s %2$s'),
                $link,
                \Html::showToolTip(
                    nl2br(RichText::getSafeHtml($job->fields['content'])),
                    [
                        'applyto' => 'ticket' . $job->fields["id"] . $rand,
                        'display' => false
                    ]
                )
            );
            $output[$colnum] = $link;

            //Ticket ID
            $colnum++;
            $link = "<a id='ticket" . $job->fields["id"] . $rand . "' href='" . $CFG_GLPI["root_doc"]
                . "/front/ticket.form.php?id=" . $job->fields["id"];
            if ($forcetab != '') {
                $link .= "&amp;forcetab=" . $forcetab;
            }
            $link .= "'>";
            $output[$colnum] = $link . "<span class='b'>" . $job->fields["id"] . "</span></a>";

            //Priority
            $colnum++;
            $bgcolor = $_SESSION["glpipriority_" . $job->fields["priority"]];

            $output[$colnum] = "<div class='center' style='background-color:$bgcolor; padding: 10px;$textColor'>
                                <span class='b'>" . $job->fields["priority"] . " - " . \Ticket::getPriorityName(
                    $job->fields["priority"]
                ) . "</span>
                             </div>";
            //Categories
            $colnum++;
            $config = new Config();
            $config->getFromDB(1);
            $itilCategory = new ITILCategory();
            if ($itilCategory->getFromDB($job->fields['itilcategories_id'])) {
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
                $output[$colnum] = "<span class='b'>" . substr($haystack, 0, $pos) . "</span>";
            } else {
                $output[$colnum] = "<span></span>";
            }

            //status
            $colnum++;
            $statusId = $job->fields["status"];
            $statusArray = \Ticket::getAllowedStatusArray($statusId);
            $output[$colnum] = $statusArray[$statusId];
        }
        return $output;
    }


    /**
     * Very short table to display the task
     *
     * @param integer $ID The ID of the task
     * @param string $itemtype The itemtype (TicketTask, ProblemTask)
     *
     * @return array
     * @since 9.2
     *
     */
    public static function showVeryShortTask($ID, $itemtype)
    {
        global $DB, $CFG_GLPI;

        $colnum = 0;
        $output = [];

        $job = new $itemtype();
        $rand = mt_rand();
        if ($job->getFromDB($ID)) {
            if ($DB->fieldExists($job->getTable(), 'tickets_id')) {
                $item_link = new \Ticket();
                $item_link->getFromDB($job->fields['tickets_id']);
                $tab_name = "Ticket";
            } elseif ($DB->fieldExists($job->getTable(), 'problems_id')) {
                $item_link = new \Problem();
                $item_link->getFromDB($job->fields['problems_id']);
                $tab_name = "ProblemTask";
            }

            $bgcolor = $_SESSION["glpipriority_" . $item_link->fields["priority"]];

            $output[$colnum] = "<div class='center' style='background-color:$bgcolor; padding: 10px;'>"
                . sprintf(__('%1$s: %2$s'), __('ID'), $job->fields["id"]) . "</div>";

            $colnum++;
            $output[$colnum] = $item_link->fields['name'];
            $colnum++;
            //echo "<td>";
            $link = "<a id='" . strtolower(
                    $item_link->getType()
                ) . "ticket" . $item_link->fields["id"] . $rand . "' href='" . $CFG_GLPI["root_doc"]
                . "/front/" . strtolower($item_link->getType()) . ".form.php?id=" . $item_link->fields["id"];
            $link .= "&amp;forcetab=" . $tab_name . "$1";
            $link .= "'>";

            $colnum++;

            $content = $job->fields['content'];
            $link .= "<span class='b'>" . $content . "</span></a>";

            $output[$colnum] = $link;

            //Ticket ID
            $colnum++;
            $link = "<a id='ticket" . $item_link->fields["id"] . $rand . "' href='" . $CFG_GLPI["root_doc"]
                . "/front/ticket.form.php?id=" . $item_link->fields["id"];

            $link .= "'>";
            $output[$colnum] = $link . "<span class='b'>" . $item_link->fields["id"] . "</span></a>";

            //Priority
            $colnum++;
            $bgcolor = $_SESSION["glpipriority_" . $item_link->fields["priority"]];

            $output[$colnum] = "<div class='center' style='background-color:$bgcolor; padding: 10px;color:white'>
                                <span>" . \Ticket::getPriorityName($item_link->fields["priority"]) . "</span>
                             </div>";

            //Categories
            $colnum++;
            $config = new Config();
            $config->getFromDB(1);
            $itilCategory = new ITILCategory();
            $itilCategory->getFromDB($item_link->fields['itilcategories_id']);

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
            $output[$colnum] = "<span class='b'>" . substr($haystack, 0, $pos) . "</span>";
        }
        return $output;
    }

    /**
     * Get tickets count
     *
     * @param $foruser boolean : only for current login user as requester (false by default)
     *
     * @return Datatable
     */
    public static function showCentralCount($foruser = false)
    {
        global $DB, $CFG_GLPI;

        // show a tab with count of jobs in the central and give link
        if (!Session::haveRight(\Ticket::$rightname, \Ticket::READALL) && !\Ticket::canCreate()) {
            return false;
        }
        if (!Session::haveRight(\Ticket::$rightname, \Ticket::READALL)) {
            $foruser = true;
        }

        $criteria = [
            'SELECT' => [
                'status',
                'COUNT' => 'glpi_tickets.id AS COUNT',
            ],
            'FROM' => 'glpi_tickets',
            'LEFT JOIN' => [],
            'WHERE' => [],
            'GROUPBY' => 'glpi_tickets.status',
        ];

        if ($foruser) {
            $criteria['LEFT JOIN'] = $criteria['LEFT JOIN'] + [
                    'glpi_tickets_users' => [
                        'ON' => [
                            'glpi_tickets' => 'id',
                            'glpi_tickets_users' => 'tickets_id',
                        ],
                    ],
                ];

            if (isset($_SESSION["glpigroups"])
                && count($_SESSION["glpigroups"])
            ) {
                $criteria['LEFT JOIN'] = $criteria['LEFT JOIN'] + [
                        'glpi_groups_tickets' => [
                            'ON' => [
                                'glpi_tickets' => 'id',
                                'glpi_groups_tickets' => 'tickets_id',
                            ],
                        ],
                    ];
            }
        }

        $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                'glpi_tickets'
            );

        if ($foruser) {
            $criteria['WHERE'] = $criteria['WHERE'] + [
                    'glpi_tickets_users.users_id' => Session::getLoginUserID(),
                    'glpi_tickets_users.type' => CommonITILActor::REQUESTER
                ];

            if (isset($_SESSION["glpigroups"])
                && count($_SESSION["glpigroups"])
            ) {
                $criteria['WHERE'] = $criteria['WHERE'] + [
                        'glpi_groups_tickets.groups_id' => $_SESSION['glpigroups'],
                        'glpi_groups_tickets.type' => CommonITILActor::REQUESTER
                    ];
            }
        }
        $criteria_deleted = $criteria;

        $iterator = $DB->request($criteria);
        $iterator_deleted = $DB->request($criteria_deleted);

        $status = [];
        foreach (\Ticket::getAllStatusArray() as $key => $val) {
            $status[$key] = 0;
        }


        if (count($iterator) > 0) {
            foreach ($iterator as $data) {
                $status[$data["status"]] = $data["COUNT"];
            }
        }

        $number_deleted = 0;
        if (count($iterator_deleted) > 0) {
            foreach ($iterator_deleted as $data) {
                $number_deleted += $data["COUNT"];
            }
        }

        $options = Toolbox::append_params([
            'reset' => 'reset',
            'criteria' => [
                0 => [
                    'value' => 'process',
                    'searchtype' => 'equals',
                    'field' => 12,
                ],
            ],
        ]);

        $widget = new MydashboardHtml();
        $widget->setWidgetId("ticketcountwidget");

        $title = __('Ticket followup', 'mydashboard');
        if (Session::getCurrentInterface() != "central") {
            $icon = "<i class='" . \Ticket::getIcon() . "'></i>";
            $widgetTitle = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.php?reset=reset\">"
                . $title . "</a>";
            if (\Ticket::canCreate()) {
                $widgetTitle .= "&nbsp;<span>";
                $widgetTitle .= "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/helpdesk.public.php?create_ticket=1\">";
                $widgetTitle .= "<i class='ti ti-plus'></i><span class='sr-only'>" . __s('Add') . "</span></a>";
            }
        } else {
            $icon = "<i class='" . \Ticket::getIcon() . "'></i>";
            $widgetTitle = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.php?reset=reset\">"
                . $title . "</a>";
            if (\Ticket::canCreate()) {
                $widgetTitle .= "&nbsp;<span>";
                $widgetTitle .= "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.form.php\">";
                $widgetTitle .= "<i class='ti ti-plus'></i><span class='sr-only'>" . __s('Add') . "</span></a>";
            }
        }

        $twig_params = [
            'title' => [
                'link' => $CFG_GLPI["root_doc"] . "/front/ticket.php?reset=reset",
                'text' => __('Ticket followup', 'mydashboard'),
                'icon' => \Ticket::getIcon(),
            ],
            'items' => [],
        ];

        $widget->setWidgetTitle(
            $icon . " " . $widgetTitle
        );


        foreach ($status as $key => $val) {
            $options = Toolbox::append_params([
                'reset' => 'reset',
                'criteria' => [
                    0 => [
                        'value' => $key,
                        'searchtype' => 'equals',
                        'field' => 12,
                    ],
                ],
            ]);
            $twig_params['items'][] = [
                'link' => $CFG_GLPI["root_doc"] . "/front/ticket.php?" . $options,
                'text' => \Ticket::getStatus($key),
                'count' => $val,
            ];
        }


        $options = Toolbox::append_params([
            'reset' => 'reset',
            'is_deleted' => 1,
            'criteria' => [
                0 => [
                    'value' => 'all',
                    'searchtype' => 'equals',
                    'field' => 12,
                ],
            ],
        ]);
        $twig_params['items'][] = [
            'link' => $CFG_GLPI["root_doc"] . "/front/ticket.php?" . $options,
            'text' => __('Deleted'),
            'count' => $number_deleted,
        ];

        $output = TemplateRenderer::getInstance()->render('@mydashboard/itemtype_count.html.twig', $twig_params);

        $widget->toggleWidgetRefresh();
        $widget->setWidgetHtmlContent($output);

        return $widget;
    }


    public static function getCommonSelect()
    {
        $SELECT = [];
        if (count($_SESSION["glpiactiveentities"]) > 1) {
            $SELECT = [
                'glpi_entities.completename AS entityname',
                'glpi_tickets.entities_id AS entityID'
            ];
        }

        return [
                'glpi_tickets.*',
                'glpi_itilcategories.completename AS catname'
            ] + $SELECT;
    }


    public static function getCommonLeftJoin()
    {
        $FROM = [];
        if (count($_SESSION["glpiactiveentities"]) > 1) {
            $FROM = [
                'glpi_entities' => [
                    'ON' => [
                        'glpi_tickets' => 'entities_id',
                        'glpi_entities' => 'id'
                    ]
                ]
            ];
        }

        return [
                'glpi_groups_tickets' => [
                    'ON' => [
                        'glpi_groups_tickets' => 'tickets_id',
                        'glpi_tickets' => 'id'
                    ]
                ],
                'glpi_tickets_users' => [
                    'ON' => [
                        'glpi_tickets_users' => 'tickets_id',
                        'glpi_tickets' => 'id'
                    ]
                ],
                'glpi_suppliers_tickets' => [
                    'ON' => [
                        'glpi_suppliers_tickets' => 'tickets_id',
                        'glpi_tickets' => 'id'
                    ]
                ],
                'glpi_itilcategories' => [
                    'ON' => [
                        'glpi_tickets' => 'itilcategories_id',
                        'glpi_itilcategories' => 'id'
                    ]
                ],
                'glpi_tickettasks' => [
                    'ON' => [
                        'glpi_tickettasks' => 'tickets_id',
                        'glpi_tickets' => 'id'
                    ]
                ],
                'glpi_items_tickets' => [
                    'ON' => [
                        'glpi_items_tickets' => 'tickets_id',
                        'glpi_tickets' => 'id'
                    ]
                ]
            ] + $FROM;
    }

    /**
     * @return bool|Datatable
     */
    public static function showCentralNewList()
    {
        global $DB, $CFG_GLPI;

        if (!Session::haveRight(\Ticket::$rightname, \Ticket::READALL)) {
            return false;
        }

        $output = [];
        $criteria = [
            'SELECT' => self::getCommonSelect(),
            'DISTINCT' => true,
            'FROM' => 'glpi_tickets',
            'LEFT JOIN' => self::getCommonLeftJoin(),
            'WHERE' => [
                'is_deleted' => 0,
                'status' => \Ticket::INCOMING,
            ],
            'ORDERBY' => 'glpi_tickets.date_mod DESC',
            'LIMIT' => intval($_SESSION['glpilist_limit'])
        ];

        $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria(
                'glpi_tickets'
            );

        $iterator = $DB->request($criteria);

        $number = count($iterator);

        if ($number > 0) {
            Session::initNavigateListItems('Ticket');

            $options = Toolbox::append_params([
                'reset' => 'reset',
                'criteria' => [
                    0 => [
                        'value' => \Ticket::INCOMING,
                        'searchtype' => 'equals',
                        'field' => 12,
                        'link' => 'AND',
                    ],
                ],
            ]);

            //TRANS: %d is the number of new tickets
            $output['title'] = sprintf(_n('%d new ticket', '%d new tickets', $number), $number);
            $output['title'] .= "&nbsp;(<a href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.php?" . $options . "\">" . __(
                    'Show all'
                ) . "</a>)";

            $output['header'] = self::commonListHeader();

            foreach ($iterator as $data) {
                Session::addToNavigateListItems('Ticket', $data["id"]);
                $output['body'][] = self::showShort($data["id"], 0);
            }
        } else {
            $output['title'] = __('New tickets', 'mydashboard');
            $output['header'] = self::commonListHeader();
            $output['body'] = [];
        }

        $widget = new Datatable();
        $widget->setWidgetTitle($output['title']);
        $widget->setWidgetId("ticketcountwidget2");
        //We set the datas of the widget (which will be later automatically formatted by the method getJSonData of Datatable)
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
    public static function commonListHeader()
    {
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
    public static function showShort($id, $followups, $output_type = Search::HTML_OUTPUT)
    {
        global $CFG_GLPI;

        $output = [];
        $colnum = 0;

        $rand = mt_rand();

        /// TODO to be cleaned. Get datas and clean display links

        // Prints a job in short form
        // Should be called in a <table>-segment
        // Print links or not in case of user view
        // Make new job object and fill it from database, if success, print it
        $job = new \Ticket();

        $showprivate = Session::haveRight('followup', ITILFollowup::SEEPRIVATE);

        if ($job->getFromDB($id)) {
            $bgcolor = $_SESSION["glpipriority_" . $job->fields["priority"]];

            // ID
            $first_col = sprintf(__('%1$s: %2$s'), __('ID'), $job->fields["id"]);
            if ($output_type == Search::HTML_OUTPUT) {
                $class = CommonITILObject::getStatusClass($job->fields["status"]);
                $label = CommonITILObject::getStatus($job->fields["status"]);
                $first_col .= "<br><i class='" . $class . "'
                                alt=\"" . $label . "\">";
            } else {
                $first_col = sprintf(
                    __('%1$s - %2$s'),
                    $first_col,
                    \Ticket::getStatus($job->fields["status"])
                );
            }

            $colnum++;
            $output[$colnum] = $first_col;

            // Date
            $colnum++;
            if ($job->fields['status'] == \Ticket::CLOSED) {
                $output[$colnum] = sprintf(
                    __('Closed on %s'),
                    ($output_type == Search::HTML_OUTPUT ? '<br>' : '')
                    . \Html::convDateTime($job->fields['closedate'])
                );
            } elseif ($job->fields['status'] == \Ticket::SOLVED) {
                $output[$colnum] = sprintf(
                    __('Solved on %s'),
                    ($output_type == Search::HTML_OUTPUT ? '<br>' : '')
                    . \Html::convDateTime($job->fields['solvedate'])
                );
            } elseif ($job->fields['begin_waiting_date']) {
                $output[$colnum] = sprintf(
                    __('Put on hold on %s'),
                    ($output_type == Search::HTML_OUTPUT ? '<br>' : '')
                    . \Html::convDateTime($job->fields['begin_waiting_date'])
                );
            } elseif ($job->fields['time_to_resolve']) {
                $output[$colnum] = sprintf(
                    __('%1$s: %2$s'),
                    __('Time to resolve'),
                    ($output_type == Search::HTML_OUTPUT ? '<br>' : '')
                    . \Html::convDateTime($job->fields['time_to_resolve'])
                );
            } else {
                $output[$colnum] = sprintf(
                    __('Opened on %s'),
                    ($output_type == Search::HTML_OUTPUT ? '<br>' : '')
                    . \Html::convDateTime($job->fields['date'])
                );
            }

            // Entity
            if (count($_SESSION["glpiactiveentities"]) > 1) {
                $colnum++;
                $output[$colnum] = Dropdown::getDropdownName('glpi_entities', $job->fields['entities_id']);
            }

            // Priority
            $colnum++;
            $output[$colnum] = "<span class='b'><div class='center' style='background-color:$bgcolor; padding: 10px;'>"
                . \Ticket::getPriorityName($job->fields["priority"]) . "</div></span>";

            // Requester
            $fourth_col = "";
            $userrequesters = $job->getUsers(CommonITILActor::REQUESTER);
            if (isset($userrequesters)
                && count($userrequesters)
            ) {
                foreach ($userrequesters as $d) {
                    $userdata = getUserName($d["users_id"]);
                    $fourth_col .= "<span class='b'>" . $userdata . "</span>";
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
            $sixth_col = "";
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
            } elseif (empty($job->fields["itemtype"])) {
                $sixth_col = __('General');
            }

            $colnum++;
            $output[$colnum] = $sixth_col;

            // Name ticket
            $eigth_column = "<span class='b'>" . $job->fields["name"] . "</span>&nbsp;";

            // Add link
            if ($job->canViewItem()) {
                $eigth_column = "<a id='ticket" . $job->fields["id"] . "$rand' href=\"" . $CFG_GLPI["root_doc"]
                    . "/front/ticket.form.php?id=" . $job->fields["id"] . "\">$eigth_column</a>";

                if ($followups
                    && ($output_type == Search::HTML_OUTPUT)
                ) {
                    $eigth_column = sprintf(
                        __('%1$s (%2$s)'),
                        $eigth_column,
                        sprintf(
                            __('%1$s - %2$s'),
                            $job->numberOfFollowups($showprivate),
                            $job->numberOfTasks($showprivate)
                        )
                    );
                } else {
                    $eigth_column = sprintf(
                        __('%1$s (%2$s)'),
                        $eigth_column,
                        sprintf(
                            __('%1$s - %2$s'),
                            $job->numberOfFollowups($showprivate),
                            $job->numberOfTasks($showprivate)
                        )
                    );
                }
            }

            if ($output_type == Search::HTML_OUTPUT) {
                $eigth_column = sprintf(
                    __('%1$s %2$s'),
                    $eigth_column,
                    \Html::showToolTip(
                        $job->fields['content'],
                        [
                            'display' => false,
                            'applyto' => "ticket" . $job->fields["id"]
                                . $rand
                        ]
                    )
                );
            }

            $colnum++;
            $output[$colnum] = $eigth_column;
        }

        return $output;
    }
}
