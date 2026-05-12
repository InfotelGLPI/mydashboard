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

namespace GlpiPlugin\Mydashboard;

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
        $is_empty      = true;

        $graphs = self::getAllWidgetsList($widgetlist);
        if (count($graphs) > 0) {
            $is_empty = false;
        }
        ksort($graphs);

        $accordion_id = 'md-wd-accordion';
        $tmp          = "<div class='accordion' id='{$accordion_id}'>";
        $cat_idx      = 0;

        foreach ($graphs as $globaltype => $widgetsplugin) {
            $cat_id  = 'md-wd-cat-' . $cat_idx++;
            $icon    = self::getIconByType($globaltype);
            $label   = self::getFolderByType($globaltype);

            $tmp .= "<div class='accordion-item'>";
            $tmp .= "<h2 class='accordion-header' id='hd-{$cat_id}'>";
            $tmp .= "<button class='accordion-button collapsed' type='button'"
                  . " data-bs-toggle='collapse' data-bs-target='#body-{$cat_id}'"
                  . " aria-expanded='false' aria-controls='body-{$cat_id}'>";
            $tmp .= "<i class='{$icon} me-2'></i>{$label}";
            $tmp .= "</button></h2>";
            $tmp .= "<div id='body-{$cat_id}' class='accordion-collapse collapse'"
                  . " aria-labelledby='hd-{$cat_id}' data-bs-parent='#{$accordion_id}'>";
            $tmp .= "<div class='accordion-body p-0'>";

            $graphbytype = [];
            foreach ($widgetsplugin as $widgets) {
                foreach ($widgets as $widgetsname => $widgetdetail) {
                    $typegraph                             = $widgetdetail['type'] ?? 0;
                    $graphbytype[$typegraph][$widgetsname] = $widgetdetail;
                }
            }

            $sub_idx = 0;
            foreach ($graphbytype as $typegraph => $widgetdetail) {
                $sub_id = $cat_id . '-sub-' . $sub_idx++;
                $items  = self::getWidgetsListFromWidgetsArray($widgetdetail, $typegraph, 2, $used, $gslist);
                if ($items === '') {
                    continue;
                }
                $icon_sub = Widget::getIconByType($typegraph);
                $res  = "<div class='accordion' id='{$sub_id}-acc'>";
                $res .= "<div class='accordion-item border-0 border-top'>";
                $res .= "<h2 class='accordion-header'>";
                $res .= "<button class='accordion-button collapsed ps-4 py-2' type='button'"
                      . " data-bs-toggle='collapse' data-bs-target='#{$sub_id}'"
                      . " aria-expanded='false'>";
                $res .= "<i class='{$icon_sub} me-2'></i>" . Widget::getNameByType($typegraph);
                $res .= "</button></h2>";
                $res .= "<div id='{$sub_id}' class='accordion-collapse collapse'>";
                $res .= "<div class='accordion-body p-0'>";
                $res .= "<div class='list-group list-group-flush'>{$items}</div>";
                $res .= "</div></div></div></div>";
                $tmp .= $res;
            }

            $tmp .= "</div></div></div>"; // accordion-body + accordion-collapse + accordion-item
        }
        $tmp .= "</div>"; // accordion

        if (!$is_empty) {
            $html .= $tmp;
            if ($list_is_empty) {
                $list_is_empty = __('No widgets available', 'mydashboard');
            }
        }

        return $list_is_empty;
    }


    /**
     *
     * @param type  $widgetsarray , an arry of widgets (or array of array ... of widgets)
     * @param type  $classname , name of the class containing the widget
     * @param int   $depth
     *
     * @param array $used
     *
     * @return string
     */
    private static function getWidgetsListFromWidgetsArray($widgetsarray, $classname, $depth = 2, $used = [], $gslist = [])
    {
        $wl = "";
        //        Toolbox::logInfo($widgetsarray);
        if (is_array($widgetsarray) && count($widgetsarray) > 0) {
            foreach ($widgetsarray as $widgetId => $widgetTitle) {
                //We check if this widget is a real widget
                if (!is_array($widgetTitle)) {
                    //If no 'title' is specified it won't be 'widgetid' => 'widget Title' but 'widgetid' so
                    if (is_numeric($widgetId)) {
                        $widgetId = $widgetTitle;
                    }
                    //                    $this->widgets[$classname][$widgetId] = -1;
                    if (isset($gslist[$widgetId])) {
                        $gsid = $gslist[$widgetId];
                        if (!in_array($gsid, $used)) {
                            $debug = ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) ? " ({$gsid})" : "";
                            $wl .= "<button type='button' id='btnAddWidgete" . $widgetId . "'"
                                 . " class='list-group-item list-group-item-action plugin_mydashboard_menuDashboardListItem'"
                                 . " data-widgetid='" . $gsid . "'"
                                 . " data-classname='" . $classname . "'>";
                            $wl .= $widgetTitle . $debug;
                            $wl .= "</button>";
                        }
                    }
                } else { //If it's not a real widget
                    //It may/must be an array of widget, in this case we need to go deeper (increase $depth)

                    if (isset($widgetTitle['title'])) {
                        //If no 'title' is specified it won't be 'widgetid' => 'widget Title' but 'widgetid' so
                        if (is_numeric($widgetId)) {
                            $widgetId = $widgetTitle['title'];
                        }
                        $classname = $widgetTitle['title'];
                        if (isset($gslist[$widgetId])) {
                            $gsid = $gslist[$widgetId];
                            if (!in_array($gsid, $used)) {
                                $icon = $widgetTitle['icon'] ?? "";
                                if (isset($widgetTitle['type'])) {
                                    $icon = Widget::getIconByType($widgetTitle['type']);
                                }
                                $debug   = ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) ? " ({$gsid})" : "";
                                $comment = $widgetTitle['comment'] ?? "";
                                $wl .= "<button type='button' id='btnAddWidgete" . $widgetId . "'"
                                     . " class='list-group-item list-group-item-action plugin_mydashboard_menuDashboardListItem'"
                                     . " data-widgetid='" . $gsid . "'"
                                     . " data-classname='" . $classname . "'>";
                                if (!empty($icon)) {
                                    $wl .= "<i class='{$icon} me-1'></i>";
                                }
                                $wl .= $widgetTitle['title'] . $debug;
                                if (!empty($comment)) {
                                    $wl .= "<br><small class='text-muted'>{$comment}</small>";
                                }
                                $wl .= "</button>";
                            }
                        }
                    } else {
                        $sub_label = self::getFolderByType($widgetId);
                        $res = self::getWidgetsListFromWidgetsArray($widgetTitle, $classname, $depth + 1, $used, $gslist);
                        if ($res !== '') {
                            $wl .= "<div class='ps-3 border-start ms-2 mt-1'>";
                            $wl .= "<div class='small text-muted py-1'>"
                                 . "<i class='ti ti-folder me-1'></i>{$sub_label}</div>";
                            $wl .= "<div class='list-group list-group-flush'>{$res}</div>";
                            $wl .= "</div>";
                        }
                    }
                }
            }
        }

        return $wl;
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
                return json_encode($graphs);
                break;
        }
    }
}
