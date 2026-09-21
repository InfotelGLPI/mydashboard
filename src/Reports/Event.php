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

use GlpiPlugin\Mydashboard\Datatable;
use GlpiPlugin\Mydashboard\Menu;
use GlpiPlugin\Mydashboard\Widget;
use Session;

/**
 * This class extends GLPI class event to add the functions to display widgets on Dashboard
 */
class Event extends \Glpi\Event
{
    /**
     * @param int $nb
     *
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return _n('Log', 'Logs', $nb);
    }

    /**
     * Event constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct();
    }


    /**
     * @return array
     */
    public function getWidgetsForItem()
    {
        $widgets = [];
        // The widget renders glpi_events, whose rightname is 'system_logs' (see the parent
        // \Glpi\Event). It was gated on 'logs', the right of glpi_logs — a different bit of
        // a different profile field, granted to many more profiles: the system journal was
        // readable by anyone holding the history right. self::$rightname is inherited from
        // the very class being displayed, so the two can no longer drift apart.
        if (Session::haveRight(self::$rightname, READ)) {
            $widgets = [
                Menu::$SYSTEM => [
                    "eventwidgetglobal" => [
                        "title" => sprintf(__('Last %d events'), $_SESSION['glpilist_limit']),
                        "type" => Widget::$TABLE,
                        "comment" => "",
                    ],
                ],
            ];
        }
        return $widgets;
    }


    /**
     * @param $widgetId
     *
     * @return Datatable
     */
    public function getWidgetContentForItem($widgetId)
    {
        if (Session::haveRight(self::$rightname, READ)) {
            switch ($widgetId) {
                //                case "eventwidgetpersonal":
                //                    return Event::showForUser($_SESSION['glpiname']);
                //                    break;
                case "eventwidgetglobal":
                    return Event::showForUser();
                    break;
            }
        }
    }

    /**
     * @param $type
     * @param $items_id
     *
     * @return string|void
     */
    public static function displayItemLogID($type, $items_id)
    {
        global $CFG_GLPI;
        $out = "";
        if (($items_id == "-1") || ($items_id == "0")) {
            $out .= "&nbsp;";//$item;
        } else {
            switch ($type) {
                case "rules":
                    $out .= "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/rule.generic.form.php?id=" .
                        $items_id . "\">" . $items_id . "</a>";
                    break;

                case "infocom":
                    $out .= "<a href='#' onClick=\"window.open('" . $CFG_GLPI["root_doc"] .
                        "/front/infocom.form.php?id=" . $items_id . "','infocoms','location=infocoms,width=" .
                        "1000,height=400,scrollbars=no')\">" . $items_id . "</a>";
                    break;

                case "devices":
                    $out .= $items_id;
                    break;

                case "reservationitem":
                    $out .= "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/reservation.php?reservationitems_id=" .
                        $items_id . "\">" . $items_id . "</a>";
                    break;

                default:
                    $type = getSingular($type);
                    $url = '';
                    if ($item = getItemForItemtype($type)) {
                        $url = $item->getFormURL();
                    }
                    if (!empty($url)) {
                        $out .= "<a href=\"" . $url . "?id=" . $items_id . "\">" . $items_id . "</a>";
                    } else {
                        $out .= $items_id;
                    }
                    break;
            }
        }
        return $out;
    }


    /**
     * Print a nice tab for last event from inventory section
     *
     * Print a great tab to present lasts events occured on glpi
     *
     * @param $user   string  name user to search on message (default '')
     *
     * @return Datatable|void
     */
    public static function showForUser(string $user = "", bool $display = true)
    {
        global $DB, $CFG_GLPI;

        // Show events from $result in table form
        list($logItemtype, $logService) = self::logArray();

        // define default sorting
        $usersearch = "";
        if (!empty($user)) {
            $usersearch = $user . " ";
        }

        // Query Database
        $iterator = $DB->request([
            'SELECT'    => '*',
            'FROM'      => 'glpi_events',
            'WHERE'     => [
                'message'   => ['LIKE', $usersearch . '%'],
            ],
            'ORDERBY'   => 'date DESC',
            'LIMIT'    => intval($_SESSION['glpilist_limit']),
        ]);

        // Number of results
        $number = count($iterator);
        // No Events in database
        if ($number < 1) {
            $output['title'] = "<br><div class='spaced'><table class='tab_cadre_fixe'>";
            $output['title'] .= "<tr><th>" . __('No Event') . "</th></tr>";
            $output['title'] .= "</table></div>";
        }
        $logService["Impersonate"] = "Impersonate";
        // Output events
        $i = 0;

        //TRANS: %d is the number of item to display
        $output['title'] = "<a href=\"" . $CFG_GLPI["root_doc"] . "/front/event.php\">" .
            sprintf(__('Last %d events'), $_SESSION['glpilist_limit']) . "</a>";

        $output['header'][] = __('Source');
        $output['header'][] = __('id');
        $output['header'][] = __('Date');
        $output['header'][] = __('Service');
        $output['header'][] = __('Message');

        $output['body'] = [];

        foreach ($iterator as $data) {

            $itemtype = "&nbsp;";
            if (isset($logItemtype[$data['type']])) {
                $itemtype = $logItemtype[$data['type']];
            } else {
                $type = getSingular($data['type']);
                if ($item = getItemForItemtype($type)) {
                    // Escaped here rather than with the cell below, which would turn the
                    // "&nbsp;" default above into a literal entity: getTypeName() is the only
                    // branch carrying data, and a custom asset definition names itself.
                    $itemtype = htmlspecialchars((string) $item->getTypeName(1), ENT_QUOTES, 'UTF-8');
                }
            }

            $output['body'][$i][0] = $itemtype;
            $output['body'][$i][1] = self::displayItemLogID($data['type'], $data['items_id']);
            $output['body'][$i][2] = \Html::convDateTime($data['date']);
            $output['body'][$i][3] = $logService[$data['service']] ?? "";
            // Datatable writes every cell as HTML (see Reports_Table::getWidgetContentForItem()),
            // so this column had to be escaped here: glpi_events.message is filled by the core
            // with the login name posted on the sign-in form (Auth::addToLogin()), which makes
            // it anonymously injectable, and it lands in a session holding system_logs. The
            // core escapes the same column in its own listing (Glpi\Event::showList()).
            $output['body'][$i][4] = htmlspecialchars((string) $data['message'], ENT_QUOTES, 'UTF-8');

            $i++;
        }
        $widget = new Datatable();
        $widget->setWidgetTitle($output['title']);
        $personnal = ($user == "") ? "global" : "personnal";
        $widget->setWidgetId("eventwidget" . $personnal);
        //We set the datas of the widget (which will be later automatically formatted by the method getJSonData of Datatable)
        $widget->setTabNames($output['header']);

        if (!empty($output)) {
            $widget->setTabDatas($output['body']);
            //Here we set few otions concerning the jquery library Datatable, bPaginate for paginating ...
            $widget->setOption("bPaginate", false);
            $widget->setOption("bFilter", false);
            $widget->setOption("bInfo", false);
            //            if (count($output['body']) > 0) {
            //                $widget->setOption("bSort", false);
            //            }
        }

        $widget->toggleWidgetRefresh();
        return $widget;
    }
}
