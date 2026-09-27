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
     * Cell of the id of the item a log line is about.
     *
     * @param $type
     * @param $items_id
     *
     * @return array typed cell, see Widget::getDisplayCell()
     */
    public static function displayItemLogID($type, $items_id)
    {
        global $CFG_GLPI;

        if (($items_id == "-1") || ($items_id == "0")) {
            return ['kind' => 'text', 'value' => ''];
        }
        $items_id = (int) $items_id;

        $url = '';
        switch ($type) {
            case "rules":
                $url = $CFG_GLPI["root_doc"] . "/front/rule.generic.form.php?id=" . $items_id;
                break;

            case "infocom":
                $url = $CFG_GLPI["root_doc"] . "/front/infocom.form.php?id=" . $items_id;
                break;

            case "devices":
                break;

            case "reservationitem":
                $url = $CFG_GLPI["root_doc"] . "/front/reservation.php?reservationitems_id=" . $items_id;
                break;

            default:
                $type = getSingular($type);
                if ($item = getItemForItemtype($type)) {
                    $url = $item->getFormURLWithID($items_id);
                }
                break;
        }

        return ['kind' => 'link', 'url' => $url, 'label' => $items_id];
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

        $logService["Impersonate"] = "Impersonate";
        // Output events
        $i = 0;

        //TRANS: %d is the number of item to display
        $output['title'] = sprintf(__('Last %d events'), $_SESSION['glpilist_limit']);

        $output['header'][] = __('Source');
        $output['header'][] = __('id');
        $output['header'][] = __('Date');
        $output['header'][] = __('Service');
        $output['header'][] = __('Message');

        $output['body'] = [];

        foreach ($iterator as $data) {

            $itemtype = "";
            if (isset($logItemtype[$data['type']])) {
                $itemtype = $logItemtype[$data['type']];
            } else {
                $type = getSingular($data['type']);
                if ($item = getItemForItemtype($type)) {
                    $itemtype = $item->getTypeName(1);
                }
            }

            // Typed cells, escaped by widget_frame.html.twig: glpi_events.message is filled
            // by the core with the login name posted on the sign-in form (Auth::addToLogin()),
            // so it is anonymously injectable, and a custom asset definition names itself.
            $output['body'][$i][0] = ['kind' => 'text', 'value' => $itemtype];
            $output['body'][$i][1] = self::displayItemLogID($data['type'], $data['items_id']);
            $output['body'][$i][2] = ['kind' => 'text', 'value' => \Html::convDateTime($data['date'])];
            $output['body'][$i][3] = ['kind' => 'text', 'value' => $logService[$data['service']] ?? ""];
            $output['body'][$i][4] = ['kind' => 'text', 'value' => $data['message']];

            $i++;
        }
        $widget = new Datatable();
        $widget->setWidgetTitle($output['title']);
        $widget->setWidgetTitleLink($CFG_GLPI["root_doc"] . "/front/event.php");
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
