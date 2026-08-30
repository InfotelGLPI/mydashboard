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

use Glpi\Application\View\TemplateRenderer;
use DbUtils;
use GlpiPlugin\Mydashboard\Reports\Change;
use GlpiPlugin\Mydashboard\Reports\Contract;
use GlpiPlugin\Mydashboard\Reports\Event;
use GlpiPlugin\Mydashboard\Reports\KnowbaseItem;
use GlpiPlugin\Mydashboard\Reports\Planning;
use GlpiPlugin\Mydashboard\Reports\Problem;
use GlpiPlugin\Mydashboard\Reports\Project;
use GlpiPlugin\Mydashboard\Reports\ProjectTask;
use GlpiPlugin\Mydashboard\Reports\Reminder;
use GlpiPlugin\Mydashboard\Reports\Reports_Bar;
use GlpiPlugin\Mydashboard\Reports\Reports_Custom;
use GlpiPlugin\Mydashboard\Reports\Reports_Funnel;
use GlpiPlugin\Mydashboard\Reports\Reports_Line;
use GlpiPlugin\Mydashboard\Reports\Reports_Map;
use GlpiPlugin\Mydashboard\Reports\Reports_Pie;
use GlpiPlugin\Mydashboard\Reports\Reports_Table;
use GlpiPlugin\Mydashboard\Reports\RSSFeed;
use GlpiPlugin\Mydashboard\Reports\Ticket;
use Session;
use Toolbox;

/**
 * Class Widgetlist
 */
class Widgetlist
{
    public static $TICKET_REQUESTERVIEW = 98;
    public static $TICKET_TECHVIEW      = 99;
    public static $GROUP_VIEW           = 100;
    public static $HELPDESK             = 101;
    public static $INVENTORY            = 102;
    public static $TOOLS                = 103;
    public static $USERS                = 104;
    public static $MANAGEMENT           = 105;
    public static $SYSTEM               = 106;
    public static $OTHERS               = 107;


    public static function getList($filtered = true, $active_profile = -1, $profile_interface = "central", $preload = false)
    {
        global $PLUGIN_HOOKS;
        $widgets = [];

        //        We get hooked plugin widgets
        if (isset($PLUGIN_HOOKS['mydashboard']) && $preload != 1) {
            $widgets = ($PLUGIN_HOOKS['mydashboard'] ?? []);
        }

        //We add classes from mydashboard
        $widgets['mydashboard'] = [Alert::class,
            Reports_Custom::class,
            Reports_Bar::class,
            Reports_Pie::class,
            Reports_Funnel::class,
            Reports_Line::class,
            Reports_Map::class,
            Reports_Table::class,];

        //We add classes for GLPI core widgets
        $widgets['GLPI'] = [
            Reminder::class,
            Planning::class,
            Event::class,
            Problem::class,
            Change::class,
            Ticket::class,
            RSSFeed::class,
            Project::class,
            ProjectTask::class,
            Contract::class,
            KnowbaseItem::class,
        ];
        $dbu             = new DbUtils();

        $config = new Config();
        $config->getConfig();

        //We run through the hook to get all widget IDs and Titles declared in all classes
        foreach ($widgets as $plugin => $pluginclasses) {
            $widgets[$plugin] = [];

            foreach ($pluginclasses as $pluginclass) {
                if (!class_exists($pluginclass)) {
                    Toolbox::loginfo($pluginclass);
                    continue;
                }

                $item = $dbu->getItemForItemtype($pluginclass);

                //            if ($item->canview) {
                $widgets[$plugin][$pluginclass] = [];
                //We try get the list of widgets for this class
                if ($item && is_callable([$item, 'getWidgetsForItem'])) {
                    if (isset($item->interfaces)) {
                        if (is_array($item->interfaces)
                            && in_array($profile_interface, $item->interfaces)) {
                            $widgets[$plugin][$pluginclass] = $item->getWidgetsForItem();
                        } else {
                            unset($widgets[$plugin]);
                        }
                    } elseif (!isset($item->interfaces)) {
                        $widgets[$plugin][$pluginclass] = $item->getWidgetsForItem();
                    }
                }
                //            }
            }
        }

        if ($filtered) {
            //Plugin filtered by user (blacklist)
            //Blacklist
            //Used when user doesn't want to display widgets of a plugin
            $ublacklist           = new PreferenceUserBlacklist();
            $filters['blacklist'] = $ublacklist->getBlacklistForUser(Session::getLoginUserID());

            foreach ($widgets as $plugin => $widgetclasses) {
                if (isset($filters['blacklist'][$plugin])) {
                    unset($widgets[$plugin]);
                    continue;
                }
            }

            //Widget filtered by profile (authorized list)
            $pauthlist = new ProfileAuthorizedWidget();
            $profile   = ($active_profile != -1) ? $active_profile : $_SESSION['glpiactiveprofile']['id'];

            if (($filters['authorized'] = $pauthlist->getAuthorizedListForProfile($profile)) !== false) {
                //getAuthorizedListForProfile() return false when the profile can see all the widgets

                //If nothing is authorized
                if (count($filters['authorized']) < 0) {
                    $widgets = [];
                } else {
                    foreach ($widgets as $plugin => &$widgetclasses) {
                        foreach ($widgetclasses as $widgetclass => &$widgetlist) {
                            $widgetlist = self::cleanList($filters['authorized'], $widgetlist);
                        }
                    }
                }
            }
        }

        return $widgets;
    }

    /**
     * Removes all $widgetlist members that are not in $authorized, recursively
     *
     * @param mixed $authorized , an array of authorized widgets IDs (names)
     * @param mixed $widgetlist , an array of widgets IDs or category
     *
     * @return array, widgetlist cleaned
     */
    private static function cleanList($authorized, $widgetlist)
    {
        foreach ($widgetlist as $widgetId => $widgetTitle) {
            if (is_array($widgetTitle) && !isset($widgetTitle['title'])) {
                $widgetlist[$widgetId] = self::cleanList($authorized, $widgetTitle);
            } else {
                if (!isset($authorized[$widgetId])) {
                    unset($widgetlist[$widgetId]);
                }
            }
        }
        return $widgetlist;
    }


    /**
     * Get the names of each view
     * @return array of string
     */
    public function getViewNames()
    {
        $names = [];

        $names[self::$TICKET_REQUESTERVIEW] = self::getFolderByType(self::$TICKET_REQUESTERVIEW);
        $names[self::$TICKET_TECHVIEW]      = self::getFolderByType(self::$TICKET_TECHVIEW);
        $names[self::$GROUP_VIEW]           = self::getFolderByType(self::$GROUP_VIEW);
        $names[self::$HELPDESK]             = self::getFolderByType(self::$HELPDESK);
        $names[self::$INVENTORY]            = self::getFolderByType(self::$INVENTORY);
        $names[self::$TOOLS]                = self::getFolderByType(self::$TOOLS);
        $names[self::$USERS]                = self::getFolderByType(self::$USERS);
        $names[self::$MANAGEMENT]           = self::getFolderByType(self::$MANAGEMENT);
        $names[self::$SYSTEM]               = self::getFolderByType(self::$SYSTEM);
        $names[self::$OTHERS]               = self::getFolderByType(self::$OTHERS);

        return $names;
    }


    /**
     * @param $type
     *
     * @return mixed
     */
    public static function getFolderByType($type)
    {
        switch ($type) {
            case self::$TICKET_REQUESTERVIEW:
                return _n('Ticket', 'Tickets', 2) . " (" . __("Requester") . ")";
            case self::$TICKET_TECHVIEW:
                return _n('Ticket', 'Tickets', 2) . " (" . __("Technician") . ")";
            case self::$GROUP_VIEW:
                return __('Group View');
            case self::$HELPDESK:
                return __('Helpdesk');
            case self::$INVENTORY:
                return __('Inventory');
            case self::$SYSTEM:
                return __('System');
            case self::$TOOLS:
                return __('Tools');
            case self::$USERS:
                return _n('User', 'Users', 2);
            case self::$MANAGEMENT:
                return __('Management');
        }
        return __('Others');
    }


    /**
     * @param $type
     *
     * @return mixed
     */
    public static function getIconByType($type)
    {
        switch ($type) {
            case self::$TICKET_REQUESTERVIEW:
            case self::$TICKET_TECHVIEW:
            case self::$GROUP_VIEW:
            case self::$HELPDESK:
                return "ti ti-headset";
            case self::$INVENTORY:
                return "ti ti-package";
            case self::$SYSTEM:
                return "ti ti-settings";
            case self::$TOOLS:
                return "ti ti-briefcase";
            case self::$USERS:
                return "ti ti-user";
            case self::$MANAGEMENT:
                return "ti ti-wallet";
        }
        return "ti ti-dashboard";
    }


    /**
     * @param $widgetlist
     * @param $from
     *
     * @return void
     */
    public static function getAllWidgetsList($widgetlist)
    {
        $graphs        = [];
        foreach ($widgetlist as $plugin => $pluginclasses) {
            foreach ($pluginclasses as $widgetclasses => $types) {
                foreach ($types as $type => $list) {
                    $graphs[$type][] = $list;
                }
            }
        }
        return $graphs;
    }

    /**
     * Get the HTML list of the plugin widgets available
     *
     * @param array $used
     *
     * @return array
     * @global type $PLUGIN_HOOKS , that's where you have to declare your classes that defines widgets, in
     *    $PLUGIN_HOOKS['mydashboard'][YourPluginName]
     */
    public static function loadWidgetsListForFuzzy($widgetlist)
    {
        if (!isset($_SESSION['glpi_plugin_mydashboard_widget_list'])) {
            $_SESSION['glpi_plugin_mydashboard_widget_list'] = Widget::getCompleteWidgetList();
        }
        $widgetslist = $_SESSION['glpi_plugin_mydashboard_widget_list'];
        $gslist      = [];
        foreach ($widgetslist as $gs => $widgetclasses) {
            $gslist[$widgetclasses['id']] = $gs;
        }

        $list = [];
        $graphs = self::getAllWidgetsList($widgetlist);

        ksort($graphs);

        foreach ($graphs as $globaltype => $widgetsplugin) {
            $graphbytype = [];
            foreach ($widgetsplugin as $widgets) {
                foreach ($widgets as $widgetsname => $widgetdetail) {
                    $typegraph                             = $widgetdetail['type'] ?? 0;
                    $graphbytype[$typegraph][$widgetsname] = $widgetdetail;
                }
            }

            foreach ($graphbytype as $typegraph => $widgetdetail) {
                foreach ($widgetdetail as $widgetId => $widgetTitle) {
                    if (isset($gslist[$widgetId])) {
                        $gsid   = $gslist[$widgetId];
                        $list[] = [
                            'icon'     => isset($widgetTitle['type']) ? Widget::getIconByType($widgetTitle['type']) : 'ti ti-dashboard',
                            'title'    => $widgetTitle['title'] ?? '',
                            'widgetid' => $gsid,
                        ];
                    }
                }
            }
        }

        return $list;
    }


    /**
     * Get the HTML list of the plugin widgets available
     *
     * @param array $used
     *
     * @return string|bool
     * @global type $PLUGIN_HOOKS , that's where you have to declare your classes that defines widgets, in
     *    $PLUGIN_HOOKS['mydashboard'][YourPluginName]
     */
    public static function loadWidgetsListForMenu($widgetlist, $used = [], &$html = "", $gslist = [])
    {
        $list_is_empty = true;

        $graphs = self::getAllWidgetsList($widgetlist);
        $is_empty = count($graphs) === 0;
        ksort($graphs);

        $accordion_id = 'md-wd-accordion';
        $categories = [];
        $cat_idx = 0;

        foreach ($graphs as $globaltype => $widgetsplugin) {
            $cat_id = 'md-wd-cat-' . $cat_idx++;

            $graphbytype = [];
            foreach ($widgetsplugin as $widgets) {
                foreach ($widgets as $widgetsname => $widgetdetail) {
                    $typegraph = $widgetdetail['type'] ?? 0;
                    $graphbytype[$typegraph][$widgetsname] = $widgetdetail;
                }
            }

            $groups = [];
            $sub_idx = 0;
            foreach ($graphbytype as $typegraph => $widgetdetail) {
                $items = self::getWidgetsItems($widgetdetail, $typegraph, $used, $gslist);
                if ($items === []) {
                    continue;
                }
                $groups[] = [
                    'id' => $cat_id . '-sub-' . $sub_idx++,
                    'icon' => Widget::getIconByType($typegraph),
                    'label' => Widget::getNameByType($typegraph),
                    'items' => $items,
                ];
            }

            $categories[] = [
                'id' => $cat_id,
                'icon' => self::getIconByType($globaltype),
                'label' => self::getFolderByType($globaltype),
                'groups' => $groups,
            ];
        }

        if (!$is_empty) {
            $html .= TemplateRenderer::getInstance()->render('@mydashboard/widgetlist_accordion.html.twig', [
                'accordion_id' => $accordion_id,
                'categories' => $categories,
            ]);
            if ($list_is_empty) {
                $list_is_empty = __('No widgets available', 'mydashboard');
            }
        }

        return $list_is_empty;
    }

    /**
     * Flatten a widget tree into renderable items: either an "add widget" button, or a
     * folder carrying nested items.
     *
     * @param array  $widgetsarray
     * @param string $classname    family the widgets belong to
     * @param array  $used         widgets already placed on the grid
     * @param array  $gslist       widget id => grid id
     *
     * @return array
     */
    private static function getWidgetsItems($widgetsarray, $classname, $used = [], $gslist = [])
    {
        if (!is_array($widgetsarray) || count($widgetsarray) === 0) {
            return [];
        }

        $is_debug = $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE;
        $items = [];

        foreach ($widgetsarray as $widgetId => $widgetTitle) {
            //We check if this widget is a real widget
            if (!is_array($widgetTitle)) {
                //If no 'title' is specified it won't be 'widgetid' => 'widget Title' but 'widgetid' so
                if (is_numeric($widgetId)) {
                    $widgetId = $widgetTitle;
                }
                if (!isset($gslist[$widgetId])) {
                    continue;
                }
                $gsid = $gslist[$widgetId];
                if (in_array($gsid, $used)) {
                    continue;
                }
                $items[] = [
                    'kind' => 'button',
                    'widget_id' => $widgetId,
                    'gsid' => $gsid,
                    'classname' => $classname,
                    'icon' => '',
                    'label' => $widgetTitle,
                    'debug' => $is_debug ? " ({$gsid})" : '',
                    'comment' => '',
                ];
                continue;
            }

            if (isset($widgetTitle['title'])) {
                //If no 'title' is specified it won't be 'widgetid' => 'widget Title' but 'widgetid' so
                if (is_numeric($widgetId)) {
                    $widgetId = $widgetTitle['title'];
                }
                // Kept as is: the family name is rebound for the remaining iterations too
                $classname = $widgetTitle['title'];
                if (!isset($gslist[$widgetId])) {
                    continue;
                }
                $gsid = $gslist[$widgetId];
                if (in_array($gsid, $used)) {
                    continue;
                }
                $icon = $widgetTitle['icon'] ?? "";
                if (isset($widgetTitle['type'])) {
                    $icon = Widget::getIconByType($widgetTitle['type']);
                }
                $items[] = [
                    'kind' => 'button',
                    'widget_id' => $widgetId,
                    'gsid' => $gsid,
                    'classname' => $classname,
                    'icon' => $icon,
                    'label' => $widgetTitle['title'],
                    'debug' => $is_debug ? " ({$gsid})" : '',
                    'comment' => $widgetTitle['comment'] ?? "",
                ];
                continue;
            }

            $sub_items = self::getWidgetsItems($widgetTitle, $classname, $used, $gslist);
            if ($sub_items !== []) {
                $items[] = [
                    'kind' => 'folder',
                    'label' => self::getFolderByType($widgetId),
                    'items' => $sub_items,
                ];
            }
        }

        return $items;
    }

    /**
     * Manage events from js/fuzzysearch.js
     *
     * @param string $action action to switch (should be actually 'getHtml' or 'getList')
     *
     * @return string
     * @since 9.2
     *
     */
    public static function fuzzySearch($action = '')
    {
        $title = __("Find a widget", "mydashboard");

        switch ($action) {
            case 'getHtml':
                $placeholder = $title;
                $html = <<<HTML
               <div id="md-fuzzysearch">
                  <input type="text" class="md-home-trigger-fuzzy form-control" placeholder="{$placeholder}">
                  <ul class="results list-group mt-2"></ul>
               </div>

HTML;
                return $html;

            default:

                $selected_profile = (isset($_SESSION['glpiactiveprofile']['id'])) ? $_SESSION['glpiactiveprofile']['id'] : -1;
                $widgetlist = self::getList(true, $selected_profile);

                $graphs = self::loadWidgetsListForFuzzy($widgetlist);

                // return the entries to ajax call
                // Widget labels can include a Customswidget name entered by a config admin and stored raw;
                // hex-escape HTML-significant characters so the payload stays inert even if the client
                // inserts it via innerHTML, matching the JSON hardening of the sibling endpoints.
                return json_encode($graphs, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                break;
        }
    }
}
