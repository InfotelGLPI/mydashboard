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
 * Class PluginMydashboardWidgetlist
 */
class PluginMydashboardWidgetlist
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
            $widgets = (isset($PLUGIN_HOOKS['mydashboard'])?$PLUGIN_HOOKS['mydashboard']:[]);
        }

        //We add classes from mydashboard
        $widgets['mydashboard'] = [];

        $autoloader = new PluginMydasboardAutoloader();
        $classes = $autoloader->listReports();
        foreach ($classes as $class) {
            $widgets['mydashboard'][] = $class;
        }

        //We add classes for GLPI core widgets
        $widgets['GLPI'] = [
                            "PluginMydashboardReminder",
                            "PluginMydashboardPlanning",
                            "PluginMydashboardEvent",
                            "PluginMydashboardProblem",
                            "PluginMydashboardChange",
                            "PluginMydashboardTicket",
                            "PluginMydashboardRSSFeed",
                            "PluginMydashboardProject",
                            "PluginMydashboardProjectTask",
                            "PluginMydashboardContract",
                            "PluginMydashboardKnowbaseItem"
        ];
        $dbu             = new DbUtils();

        $config = new PluginMydashboardConfig();
        $config->getConfig();

        //We run through the hook to get all widget IDs and Titles declared in all classes
        foreach ($widgets as $plugin => $pluginclasses) {
            $widgets[$plugin] = [];

            foreach ($pluginclasses as $pluginclass) {
                if (!class_exists($pluginclass)) {
                    Toolbox::logWarning($pluginclass);
                    continue;
                }

                $item = $dbu->getItemForItemtype($pluginclass);

            //            if ($item->canview) {
                $widgets[$plugin][$pluginclass] = [];
                //We try get the list of widgets for this class
                if ($item && is_callable([$item, 'getWidgetsForItem'])) {
                    if (isset($item->interfaces)) {
                        if (is_array($item->interfaces) && in_array($profile_interface, $item->interfaces)) {
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
            $ublacklist           = new PluginMydashboardPreferenceUserBlacklist();
            $filters['blacklist'] = $ublacklist->getBlacklistForUser(Session::getLoginUserID());

            foreach ($widgets as $plugin => $widgetclasses) {
                if (isset($filters['blacklist'][$plugin])) {
                    unset($widgets[$plugin]);
                    continue;
                }
            }

            //Widget filtered by profile (authorized list)
            $pauthlist = new PluginMydashboardProfileAuthorizedWidget();
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
     * @return string|boolean
     * @global type $PLUGIN_HOOKS , that's where you have to declare your classes that defines widgets, in
     *    $PLUGIN_HOOKS['mydashboard'][YourPluginName]
     */
    public static function loadWidgetsListForFuzzy($widgetlist)
    {
        $widgetslist = PluginMydashboardWidget::getWidgetList();
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
                            'title'    => $widgetTitle['title'],
                            'icon'     => PluginMydashboardWidget::getIconByType($widgetTitle['type']),
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
     * @return string|boolean
     * @global type $PLUGIN_HOOKS , that's where you have to declare your classes that defines widgets, in
     *    $PLUGIN_HOOKS['mydashboard'][YourPluginName]
     */
    public static function loadWidgetsListForMenu($widgetlist, $used = [], &$html = "", $gslist = [])
    {
        $list_is_empty = true;

        $is_empty      = true;
        $tmp           = "<div class='plugin_mydashboard_menuDashboard'>";

        $graphs = self::getAllWidgetsList($widgetlist);
        if (count($graphs) > 0) {
            $is_empty        = false;
        }
        ksort($graphs);

        foreach ($graphs as $globaltype => $widgetsplugin) {
            $tmp .= "<div class='plugin_mydashboard_menuDashboardList'>";
            $tmp .= "<h5 class='media-body plugin_mydashboard_menuDashboardListTitle1'>";
            $tmp .= "<span class='media-left'>";
            $icon = self::getIconByType($globaltype);
            $tmp .= "<i class='".$icon."'></i>";
            $tmp .= "</span>&nbsp;";
            $tmp .= self::getFolderByType($globaltype);
            $tmp .= "</h5>";
            //Every widgets of a plugin are in an accordion (handled by dashboard not the jquery one)
            $tmp .= "<div style='width: 100%;' class='plugin_mydashboard_menuDashboardList1'>";

            $graphbytype = [];
            foreach ($widgetsplugin as $widgets) {
                foreach ($widgets as $widgetsname => $widgetdetail) {
                    $typegraph                             = $widgetdetail['type'] ?? 0;
                    $graphbytype[$typegraph][$widgetsname] = $widgetdetail;
                }
            }

            foreach ($graphbytype as $typegraph => $widgetdetail) {
                $res = "<div class='plugin_mydashboard_menuDashboardList'>";
                $res .= "<h5 class='media-body plugin_mydashboard_menuDashboardListTitle2'>";
                $res .= "<span class='media-left'>";
                $icon = PluginMydashboardWidget::getIconByType($typegraph);
                $res .= "<i class='$icon'></i>";
                $res .= "</span>&nbsp;";
                $res .= "&nbsp;";
                $res .= PluginMydashboardWidget::getNameByType($typegraph);
                $res .= "</h5>";
                //Every widgets of a plugin are in an accordion (handled by dashboard not the jquery one)
                $res .= "<div style='width: 100%;' class='plugin_mydashboard_menuDashboardList2'>";
                $res .= self::getWidgetsListFromWidgetsArray($widgetdetail, $typegraph, 2, $used, $gslist);
                $res .= "</div>";
                $res .= "</div>";
                if ($res != '') {
                    $tmp .= $res;
                }
            }
            $tmp .= "<div style='padding-bottom: 10px;'>";
            $tmp .= "</div>";

            $tmp .= "</div>";
            $tmp .= "</div>";
        }
        $tmp .= "</div>";
        $tmp .= "<div style='padding-bottom: 10px;'>";
        $tmp .= "</div>";
        //If there is now widgets available from this plugins we don't display menu entry
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
                            $wl .= "<span id='btnAddWidgete" . $widgetId . "'"
                                   . " class='plugin_mydashboard_menuDashboardListItem' "
                                   . " data-widgetid='" . $gsid . "'"
                                   . " data-classname='" . $classname . "'>";
                            $wl .= $widgetTitle;

                            if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
                                $wl .= " (" . $gsid . ")";
                            }/*->getWidgetListTitle()*/
                            $wl .= "</span>";
                        }
                    }
                } else { //If it's not a real widget
                    //It may/must be an array of widget, in this case we need to go deeper (increase $depth)

                    if (isset($widgetTitle['title'])) {
                        //If no 'title' is specified it won't be 'widgetid' => 'widget Title' but 'widgetid' so
                        if (is_numeric($widgetId)) {
                            $widgetId = $widgetTitle['title'];
                        }
                        //                        $this->widgets[$classname][$widgetId] = -1;
                        $classname = $widgetTitle['title'];
                        if (isset($gslist[$widgetId])) {
                            $gsid = $gslist[$widgetId];
                            if (!in_array($gsid, $used)) {
                                $wl .= "<span id='btnAddWidgete" . $widgetId . "'"
                                       . " class='media plugin_mydashboard_menuDashboardListItem' "
                                       . " data-widgetid='" . $gsid . "'"
                                       . " data-classname='" . $classname . "'>";

                                $icon = $widgetTitle['icon'] ?? "";
                                if (isset($widgetTitle['type'])) {
                                    $icon = PluginMydashboardWidget::getIconByType($widgetTitle['type']);
                                }
                                if (!empty($icon)) {
                                    $wl .= "<div class='media-left'><i class='$icon'></i>&nbsp;";
                                }
                                //                        $wl .= "<div class='media-body' style='margin: 10px;'>";
                                $wl .= $widgetTitle['title'];
                                if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
                                    $wl .= " (" . $gsid . ")";
                                }
                                $comment = $widgetTitle['comment'] ?? "";
                                if (!empty($comment)) {
                                    $wl .= "<br><span class='widget-comment'>$comment</span>";
                                }
                                $wl .= "</div></span>";
                            }
                        }
                    } else {
                        $tmp = "<div class='plugin_mydashboard_menuDashboardList'>";
                        $tmp .= "<h5 class='media-body plugin_mydashboard_menuDashboardListTitle$depth'>";
                        $tmp .= "<span class='media-left'>";
                        $tmp .= "<i class='ti ti-folder'></i>";
                        $tmp .= "</span>&nbsp;";
                        $tmp .= self::getFolderByType($widgetId);
                        $tmp .= "</h5>";
                        $tmp .= "<div style='width: 100%;' class='plugin_mydashboard_menuDashboardList$depth'>";
                        $res = self::getWidgetsListFromWidgetsArray($widgetTitle, $classname, $depth + 1, $used, $gslist);
                        if ($res != '') {
                            $tmp .= $res;
                        }
                        $tmp .= "</div></div>";
                        if ($res != '') {
                            $wl .= $tmp;
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
                $modal_header = __('Search');
                $placeholder        = $title;
                $alert              = "";
                //            $alert        = sprintf(
                //               __("Tip: You can call this modal with %s keys combination"),
                //               "<kbd><kbd>Ctrl</kbd> + <kbd>Alt</kbd> + <kbd>G</kbd></kbd>"
                //            );
                $html = <<<HTML
               <div class="" tabindex="-1" id="md-fuzzysearch">
                  <div class="">
                     <div class="modal-content">
                        <div class="modal-body" style="padding: 10px;">
                           <input type="text" class="md-home-trigger-fuzzy form-control" placeholder="{$placeholder}">
                           <ul class="results list-group mt-2" style="background: #FFF;"></ul>
                        </div>
                     </div>
                  </div>
               </div>

HTML;
                return $html;
                break;

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
