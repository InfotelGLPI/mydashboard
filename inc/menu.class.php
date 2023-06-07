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
 * Class PluginMydashboardMenu
 */
class PluginMydashboardMenu extends CommonGLPI
{
    const DASHBOARD_NAME = "myDashboard";
    /**
     * Will contain an array indexed with classnames, each element of this array<br>
     * will be an array containing widgetId s
     * @var array of array of string
     */
    private $widgets    = [];
    public $widgetlist = [];
    /**
     * Will contain an array of strings with js function needed to add a widget
     * @var array of string
     */
    private $addfunction = [];
    /**
     * User id, most of the time it will correspond to currently connected user id,
     * but sometimes it will correspond to the DEFAULD_ID, for the default dashboard
     * @var int
     */
    private $users_id;
    /**
     * An array of string, each string is a widgetId of a widget that must be added on the mydashboard
     * @var array of string
     */
    private $dashboard = [];
    /**
     * An array of string indexed by classnames, each string is a statistic (time /mem)
     * @var array of string
     */
    private $stats = [];
    /**
     * A string to store infos, those infos are displayed in the top right corner of the mydashboard
     * @var string
     */

    public static $_PLUGIN_MYDASHBOARD_CFG = [];

    //Maintened for compatibility
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

    public static $rightname = "plugin_mydashboard";

    /**
     * @param int $nb
     *
     * @return translated
     */
    public static function getTypeName($nb = 0)
    {
        return __('My Dashboard', 'mydashboard');
    }


    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addStandardTab(__CLASS__, $ong, $options);

        return $ong;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item->getType() == __CLASS__) {
            $tabs[1] = __('My view', 'mydashboard');
            $tabs[2] = __('GLPI admin grid', 'mydashboard');
            //         $tabs[3] = __('Inventory admin grid', 'mydashboard');
            //         $tabs[4] = __('Helpdesk supervisor grid', 'mydashboard');
            //         $tabs[5] = __('Incident supervisor grid', 'mydashboard');
            //         $tabs[6] = __('Request supervisor grid', 'mydashboard');
            //         $tabs[7] = __('Helpdesk technician grid', 'mydashboard');
            return $tabs;
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        $profile         = (isset($_SESSION['glpiactiveprofile']['id'])) ? $_SESSION['glpiactiveprofile']['id'] : -1;
        $predefined_grid = 0;

        if (isset($_POST["profiles_id"])) {
            $profile = $_POST["profiles_id"];
        }
        if (isset($_POST["predefined_grid"])) {
            $predefined_grid = $_POST["predefined_grid"];
        }
        $self = new self();
        $self->initDBWidgets();

        if ($item->getType() == __CLASS__) {
            switch ($tabnum) {
                case 1:
                    $self->loadDashboard($profile, $predefined_grid);
                    break;
                case 2:
                    $self->loadDashboard($profile, 1);
                    break;
                default:
                    break;
            }
        }
        return true;
    }

    /**
     * PluginMydashboardMenu constructor.
     *
     * @param bool $show_all
     */
    public function __construct($show_all = false)
    {
        $this->initConfig($show_all);
    }

    /**
     * Initialize the mydashboard config
     *
     * @param $show_all
     */
    private function initConfig($show_all)
    {
        //Configuration set by Administrator (via Configuration->Plugins ...)
        $config = new PluginMydashboardConfig();
        $config->getConfig();

        self::$_PLUGIN_MYDASHBOARD_CFG['enable_fullscreen']     = $config->fields['enable_fullscreen']; // 0 (FALSE) or 1 (TRUE), enable the possibility to display the mydashboard in fullscreen
        self::$_PLUGIN_MYDASHBOARD_CFG['display_menu']          = $config->fields['display_menu']; // Display the right menu slider
        self::$_PLUGIN_MYDASHBOARD_CFG['replace_central']       = $config->fields['replace_central']; // Replace central interface

        unset($config);

        //Configuration set by User (via My Preferences -> Dashboard tab)
        //General Settings
        $preference = new PluginMydashboardPreference();
        if (!$preference->getFromDB(Session::getLoginUserID())) {
            $preference->initPreferences(Session::getLoginUserID());
        }
        $preference->getFromDB(Session::getLoginUserID());

        self::$_PLUGIN_MYDASHBOARD_CFG['automatic_refresh']       = $preference->fields['automatic_refresh'];  //Wether or not refreshable widget will be automatically refreshed by automaticRefreshDelay minutes
        self::$_PLUGIN_MYDASHBOARD_CFG['automatic_refresh_delay'] = $preference->fields['automatic_refresh_delay']; //In minutes
        self::$_PLUGIN_MYDASHBOARD_CFG['replace_central']         = $preference->fields['replace_central']; // Replace central interface
    }

    /**
     * @return array
     */
    public static function getMenuContent()
    {
        $plugin_page = PluginMydashboardMenu::getSearchURL(false);
        $menu        = [];
        //Menu entry in tools
        $menu['title']           = self::getTypeName();
        $menu['page']            = $plugin_page;
        $menu['links']['search'] = $plugin_page;
        if (Session::haveRightsOr("plugin_mydashboard_config", [CREATE, UPDATE])
            || Session::haveRight("config", UPDATE)) {
            //Entry icon in breadcrumb
            $menu['links']['config'] = PluginMydashboardConfig::getFormURL(false);
        }

        $menu['options']['pluginmydashboardstockwidget'] = [
            'title' => PluginMydashboardStockWidget::getTypeName(2),
            'page'  => PluginMydashboardStockWidget::getSearchURL(false),
            'links' => [
                'search' => PluginMydashboardStockWidget::getSearchURL(false),
                'add'    => PluginMydashboardStockWidget::getFormURL(false)
            ]
        ];

        $menu['icon'] = self::getIcon();

        return $menu;
    }

    /**
     * @return string
     */
    public static function getIcon()
    {
        return "ti ti-dashboard";
    }

    /**
     * Show dashboard
     *
     * @param int $users_id
     * @param int $active_profile
     *
     * @return FALSE if the user haven't the right to see Dashboard
     * @internal param type $user_id
     */
    public function showMenu($rand, $users_id = -1, $active_profile = -1, $predefined_grid = 0)
    {
        //We check the wanted interface (this param is later transmitted to PluginMydashboardUserWidget to get the dashboard for the user in this interface)
        $interface = (Session::getCurrentInterface() == 'central') ? 1 : 0;

        // validation des droits
        if (!Session::haveRightsOr("plugin_mydashboard", [CREATE, READ])) {
            return false;
        }
        // checking if no users_id is specified
        $this->users_id = Session::getLoginUserID();
        if ($users_id != -1) {
            $this->users_id = $users_id;
        }

        //Now the mydashboard
        $this->showDashboard($rand, $active_profile, $predefined_grid);
    }


    /**
     * Dropdown profiles which have rights under the active one
     *
     * @param $options array of possible options:
     *    - name : string / name of the select (default is profiles_id)
     *    - value : integer / preselected value (default 0)
     *
     **/
    public static function dropdownProfiles($options = [])
    {
        global $DB;

        $p['name']  = 'profiles_id';
        $p['value'] = '';
        $p['rand']  = mt_rand();
        $profiles   = [];
        if (is_array($options) && count($options)) {
            foreach ($options as $key => $val) {
                $p[$key] = $val;
            }
        }
        $iterator = $DB->request(
            ['SELECT'    => [
                'glpi_profiles.name',
                'glpi_profiles.id'
            ],
             'FROM'      => Profile::getTable(),
             'LEFT JOIN' => [
                 'glpi_profilerights' => [
                     'FKEY' => [
                         'glpi_profilerights' => 'profiles_id',
                         'glpi_profiles'      => 'id'
                     ]
                 ]
             ],
             'WHERE'     => [Profile::getUnderActiveProfileRestrictCriteria(),
                             'glpi_profilerights.name'   => 'plugin_mydashboard',
                             'glpi_profilerights.rights' => ['>', 0],
             ],
             'ORDER'     => 'glpi_profilerights.name'
            ]
        );

        //New rule -> get the next free ranking
        foreach ($iterator as $data) {
            $profiles[$data['id']] = $data['name'];
        }
        echo "<select name='" . $p['name'] . "' onChange='this.form.submit()'>";
        echo "<option>" . Dropdown::EMPTY_VALUE . "</option>";
        foreach ($profiles as $id => $name) {
            $selected = '';
            if ($id == $p['value']) {
                $selected = 'selected';
            }
            echo "<option $selected value='$id'>$name</option>";
        }
        echo "</select>";
        //
        //      Dropdown::showFromArray($p['name'], $profiles,
        //                              ['value'               => $p['value'],
        //                               'rand'                => $p['rand'],
        //                               'display_emptychoice' => true,
        //                               'on_change'           => 'this.form.submit()']);
    }

    /**
     * This method shows the widget list (in the left part) AND the mydashboard
     *
     * @param int $selected_profile
     */
    private function showDashboard($rand, $selected_profile = -1, $predefined_grid = 0)
    {
        //If we want to display the widget list menu, we have to 'echo' it, else we also need to call it because it initialises $this->widgets (link between classnames and widgetId s)
        //      $_SESSION['plugin_mydashboard_editmode'] = false;
        //      $edit = PluginMydashboardPreference::checkEditMode(Session::getLoginUserID());
        //      if ($edit > 0) {
        //         echo $this->getWidgetsList($selected_profile, $edit);
        //      }

        //Now we have a widget list menu, but, it does nothing, we have to bind
        //list item click with the adding on the mydashboard, and we need to display
        //this div contains the header and the content (basically the ul used by sDashboard)

        echo "<div class='plugin_mydashboard_dashboard' >";//(div.plugin_mydashboard_dashboard)

        //This first div is the header of the mydashboard, basically it display a name, informations and a button to toggle full screen
        echo "<div class='plugin_mydashboard_header'>";//(div.plugin_mydashboard_header)
        echo "</div>";//end(div.plugin_mydashboard_header)
        //Now the content
        //      echo "<div class='plugin_mydashboard_content'>";//(div.plugin_mydashboard_content)
        //
        //      echo "</div>";//end(div.plugin_mydashboard_content)
        echo "</div>";//end(div.plugin_mydashboard_dashboard)

        //      //Automatic refreshing of the widgets (that wants to be refreshed -> see PluginMydashboardModule::toggleRefresh() )
        if (self::$_PLUGIN_MYDASHBOARD_CFG['automatic_refresh']) {
            //We need some javascript, here are scripts (script which have to be dynamically called)
            $refreshIntervalMs = 60000 * self::$_PLUGIN_MYDASHBOARD_CFG['automatic_refresh_delay'];
            //this js function call itself every $refreshIntervalMs ms, each execution result in the refreshing of all refreshable widgets

            echo Html::scriptBlock('
            function refreshAll() {
                 $(\'.refresh-icon\').trigger(\'click\');
             };
            function automaticRefreshAll(delay) {
                 setInterval(function () {
                     refreshAll();
                 }, delay);
             }
            ');

            echo Html::scriptBlock('
               automaticRefreshAll(' . $refreshIntervalMs . ');
         ');
        }
    }

    public function displayEditMode($rand, $edit = 0, $selected_profile = -1, $predefined_grid = 0)
    {
        $drag = PluginMydashboardPreference::checkDragMode(Session::getLoginUserID());

        echo $this->getscripts();

        echo Html::css(PLUGIN_MYDASHBOARD_NOTFULL_DIR . "/css/style_bootstrap_new.css");
        if ($edit > 0) {
            //force loading new widgets
            self::installWidgets();
        }

        if ($edit > 0) {
            echo "<div class='left'>";

            echo "<form method='post' 
                     action='" . $this->getSearchURL() . "' onsubmit='return true;'>";

            echo "<table class='tab_cadre_fixe' width='100%'>";


            echo "<tr><th style='background-color: #e3e3e3;padding: 10px;'>";
            echo "&nbsp;" . __('Availables widgets', 'mydashboard');
            echo "</th>";
            echo "</tr>";

            /**** Loading widgets****/
            $widgetslist = PluginMydashboardWidget::getWidgetList();
            $gslist      = [];
            foreach ($widgetslist as $gs => $widgetclasses) {
                $gslist[$widgetclasses['id']] = $gs;
            }
            $grid = [];
            $used = [];

            $dashboard = new PluginMydashboardDashboard();

            if ($edit == 2) {
                $options = ["users_id"    => 0,
                            "profiles_id" => $selected_profile];
                $id      = PluginMydashboardDashboard::checkIfPreferenceExists($options);
                if ($dashboard->getFromDB($id)) {
                    $grid = stripslashes($dashboard->fields['grid']);
                }
            }
            if ($edit == 1) {
                $option_users = ["users_id"    => Session::getLoginUserID(),
                                 "profiles_id" => $selected_profile];
                $id           = PluginMydashboardDashboard::checkIfPreferenceExists($option_users);
                if ($dashboard->getFromDB($id)) {
                    $grid = stripslashes($dashboard->fields['grid']);
                }
            }

            if (!empty($grid) && ($datagrid = json_decode($grid, true)) == !null) {
                foreach ($datagrid as $k => $v) {
                    $used[] = $v["id"];
                }
            }

            $widgetlist = PluginMydashboardWidgetlist::getList(true, $selected_profile);
            /**** End Loading widgets****/

            echo "<div id='searchwidgets'>";
            echo "</div>";
            echo PluginMydashboardWidgetlist::fuzzySearch('getHtml');

            echo "<tr>";
            echo "<td class='left' style='padding: 0px;'>";
            echo $this->getWidgetsList($widgetlist, $gslist, $used);
            echo "</th>";
            echo "</tr>";

            echo "<tr><th style='background-color: #e3e3e3;padding: 10px;'>";
            echo __('Edit mode', 'mydashboard');
            if ($edit == 2) {
                echo " (" . __('Global', 'mydashboard') . ")";
            }
            echo "</th>";
            echo "</tr>";

            if (Session::haveRight("plugin_mydashboard_config", CREATE) && $edit == 2) {
                echo "<tr>";
                echo "<td class='center'>";
                echo "<span class='editmode_test'>" . __('Profile') . "</span>&nbsp;";
                echo "<br><br>";
                self::dropdownProfiles(['value' => $selected_profile]);
                echo "</td>";
                echo "<tr>";
            } else {
                echo Html::hidden("profiles_id", ['value' => $_SESSION['glpiactiveprofile']['id']]);
            }


            echo "<tr class='plugin_mydashboard_trWidget'>";
            echo "<td class='center' style='border: 0;'>";

            echo "<br><span class='editmode_test'>" . __('Load a predefined grid', 'mydashboard') . "</span>&nbsp;";
            echo "<br><br>";
            $elements = PluginMydashboardDashboard::getPredefinedDashboardName();
            echo "<select name='predefined_grid' onChange='this.form.submit()'>";
            echo "<option>" . Dropdown::EMPTY_VALUE . "</option>";
            foreach ($elements as $id => $name) {
                echo "<option value='$id'>$name</option>";
            }
            echo "</select><br>";
            //         Dropdown::showFromArray("predefined_grid", $elements, [
            //            'value'               => $predefined_grid,
            //            'width'               => '170px',
            //            'display_emptychoice' => true,
            //            'on_change'           => 'this.form.submit()']);
            //
            echo "<br>";

            if (!Session::haveRight("plugin_mydashboard_config", CREATE) && $edit == 2) {
                $edit = 1;
            }

            echo "<a id='load-widgets' class='submit btn btn-info'>";
            echo "<i class='ti ti-loader pointer btn-mydashboard' title='" . __('Load widgets', 'mydashboard') . "'
                           data-hasqtip='0' aria-hidden='true'></i>";
            echo "&nbsp;" . __('Load widgets', 'mydashboard');
            echo "</a>";
            echo "<br><br>";

            if ($edit == 1) {
                echo "<a id='save-grid' class='submit btn btn-success'>";
                echo "<i class='ti ti-device-floppy pointer btn-mydashboard' title='" . __('Save grid', 'mydashboard') . "'
                           data-hasqtip='0' aria-hidden='true'></i>";
                echo "&nbsp;" . __('Save grid', 'mydashboard');
                echo "</a>";
                echo "<br><br>";
            }
            if (Session::haveRight("plugin_mydashboard_config", CREATE) && $edit == 2) {
                echo "<a id='save-default-grid' class='submit btn btn-success'>";
                echo "<i class='ti ti-layout-grid pointer btn-mydashboard' title='" . __('Save default grid', 'mydashboard') . "'
                           data-hasqtip='0' aria-hidden='true'></i>";
                echo "&nbsp;" . __('Save default grid', 'mydashboard');
                echo "</a>";
                echo "<br><br>";
            }

            echo "<a id='clear-grid' class='submit btn btn-danger'>";
            echo "<i class='ti ti-brand-windows pointer btn-mydashboard' title='" . __('Clear grid', 'mydashboard') . "'
                           data-hasqtip='0' aria-hidden='true'></i>";
            echo "&nbsp;" . __('Clear grid', 'mydashboard');
            echo "</a>";
            echo "<br><br>";

            if ($drag < 1 && Session::haveRight("plugin_mydashboard_edit", 6)) {
                echo "<a id='drag-grid' class='submit btn btn-danger'>";
                echo "<i class='ti ti-lock pointer btn-mydashboard' title='" . __('Permit drag / resize widgets', 'mydashboard') . "'
                           data-hasqtip='0' aria-hidden='true'></i>";
                echo "&nbsp;" . __('Permit drag / resize widgets', 'mydashboard');
                echo "</a>";
                echo "<br><br>";
            }
            if ($drag > 0 && Session::haveRight("plugin_mydashboard_edit", 6)) {
                echo "<a id='undrag-grid' class='submit btn btn-success'>";
                echo "<i class='ti ti-lock-open pointer btn-mydashboard' title='" . __('Block drag / resize widgets', 'mydashboard') . "'
                           data-hasqtip='0' aria-hidden='true'></i>";
                echo "&nbsp;" . __('Block drag / resize widgets', 'mydashboard');
                echo "</a>";
                echo "<br><br>";
            }

            $interface = (Session::getCurrentInterface() == 'central') ? 1 : 0;
            if (self::$_PLUGIN_MYDASHBOARD_CFG['enable_fullscreen']
                && $edit < 1
                && $interface == 1) {
                echo "<a id='header_fullscreen' class='submit btn btn-info'>";
                echo "<i class='ti ti-maximize pointer btn-mydashboard' title='" . __("Fullscreen", "mydashboard") . "'
                           data-hasqtip='0' aria-hidden='true'></i>";
                echo "&nbsp;" . __("Fullscreen", "mydashboard");
                echo "</a>";
                echo "<br><br>";
            }
            echo "<a id='close-edit' class='submit btn btn-success'>";
            echo "<i class='ti ti-circle-x pointer btn-mydashboard' title='" . __("Close edit mode", "mydashboard") . "'
                           data-hasqtip='0' aria-hidden='true'></i>";
            echo "&nbsp;" . __("Close edit mode", "mydashboard");
            echo "</a>";

            echo "</td>";
            echo "</tr>";

            echo "</table>";
            Html::closeForm();
            echo "</div>";


            echo "<div class='alert alert-success' id='success-alert'>
                <strong>" . __('Success', 'mydashboard') . "</strong> - 
                " . __('The widget was added to dashboard. Save the dashboard.', 'mydashboard') . "
            </div>";
            echo Html::scriptBlock('
               $("#success-alert").hide();
         ');

            echo "<div class='bt-alert bt-alert-error' id='error-alert'>
                <strong>" . __('Error', 'mydashboard') . "</strong>
                " . __('Please reload your page.', 'mydashboard') . "
            </div>";
            echo Html::scriptBlock('
               $("#error-alert").hide();
         ');
        } else {
            echo "<div class='center'>";
            echo "<br>";

            if ($drag > 0 && Session::haveRight("plugin_mydashboard_edit", 6)) {
                echo "<a id='save-grid' class='submit btn btn-success'>";
                echo "<i class='ti ti-device-floppy pointer btn-mydashboard' title='" . __('Save grid', 'mydashboard') . "'
                           data-hasqtip='0' aria-hidden='true'></i>";
                echo "&nbsp;" . __('Save grid', 'mydashboard');
                echo "</a>";
                echo "<br><br>";

                echo "<a id='undrag-grid' class='submit btn btn-success'>";
                echo "<i class='ti ti-lock-open pointer btn-mydashboard' title='" . __('Block drag / resize widgets', 'mydashboard') . "'
                           data-hasqtip='0' aria-hidden='true'></i>";
                echo "&nbsp;" . __('Block drag / resize widgets', 'mydashboard');
                echo "</a>";
                echo "<br><br>";
            }

            if (Session::haveRight("plugin_mydashboard_edit", 6)) {
                echo "<a id='edit-grid' class='submit btn btn-danger'>";
                echo "<i class='ti ti-edit pointer btn-mydashboard' title='" . __('Switch to edit mode', 'mydashboard') . "'
                           data-hasqtip='0' aria-hidden='true'></i>";
                echo "&nbsp;" . __('Switch to edit mode', 'mydashboard');
                echo "</a>";
                echo "<br><br>";
            }

            if ($drag < 1 && Session::haveRight("plugin_mydashboard_edit", 6)) {
                echo "<a id='drag-grid' class='submit btn btn-danger'>";
                echo "<i class='ti ti-lock pointer btn-mydashboard' title='" . __('Permit drag / resize widgets', 'mydashboard') . "'
                           data-hasqtip='0' aria-hidden='true'></i>";
                echo "&nbsp;" . __('Permit drag / resize widgets', 'mydashboard');
                echo "</a>";
                echo "<br><br>";
            }

            if (Session::haveRight("plugin_mydashboard_config", CREATE)) {
                echo "<a id='edit-default-grid' class='submit btn btn-danger'>";
                echo "<i class='ti ti-adjustments pointer btn-mydashboard' title='" . __('Custom and save default grid', 'mydashboard') . "'
                           data-hasqtip='0' aria-hidden='true'></i>";
                echo "&nbsp;" . __('Custom and save default grid', 'mydashboard');
                echo "</a>";
                echo "<br><br>";
            }

            $interface = (Session::getCurrentInterface() == 'central') ? 1 : 0;
            if (self::$_PLUGIN_MYDASHBOARD_CFG['enable_fullscreen']
                && $edit < 1
                && $interface == 1) {
                echo "<a id='header_fullscreen' class='submit btn btn-info'>";
                echo "<i class='ti ti-maximize pointer btn-mydashboard' title='" . __("Fullscreen", "mydashboard") . "'
                           data-hasqtip='0' aria-hidden='true'></i>";
                echo "&nbsp;" . __("Fullscreen", "mydashboard");
                echo "</a>";
            }
        }
        echo "<div id='ajax_loader' class=\"ajax_loader hidden\">";
        echo "</div>";
    }

    /**
     * Get the HTML view of the widget list, the lateral menu
     *
     * @param     $profile
     * @param int $edit
     *
     * @return string, HTML
     */
    public function getWidgetsList($widgetlist, $gslist, $used)
    {
        $wl = "<script>

            $(document).ready(function () {

                //===================Start:Showing Menu=====================================
                //Showing the menu on click
                $('.plugin_mydashboard_add_button').on('click', function (e) {
//                    $('.plugin_mydashboard_menuDashboard').width(400);
                    $('.plugin_mydashboard_menuDashboard').show();
                });
                //Hiding the menu when clicking outside the menu
                var menu = false;
                $(\"#success-alert\").hide();
                $('.plugin_mydashboard_add_button,.plugin_mydashboard_menuDashboard').click(function (e) {
                    menu = true;
                });
                $(document).click(function () {
                  if (!menu) {
//                      $('.plugin_mydashboard_menuDashboard').hide();
                  } else {
                      menu = false;
                  }
                });
            
                //===================Stop:Showing Menu=====================================
                //===================Start:AccordionEffect=================================
                //Now the accordion effect w/o jQuery Accordion (wasn't really customizable, and css from other plugin can override dashboard one)
                //at the beginning every lists of widgets are folded
//                $('.plugin_mydashboard_menuDashboardListContainer,.plugin_mydashboard_menuDashboardListWidget').slideUp('fast');
                $('.plugin_mydashboard_menuDashboardListContainer,.plugin_mydashboard_menuDashboardList2').slideUp('fast');
                $('.plugin_mydashboard_menuDashboardListContainer,.plugin_mydashboard_menuDashboardList1').slideUp('fast');
                //binding when user wants to unfold/fold a list of widget
                $('.plugin_mydashboard_menuDashboardListTitle1').click(function () {
                    var isOpened = $(this).hasClass('plugin_mydashboard_menuDashboardListTitle1Opened');
                    $('.plugin_mydashboard_menuDashboardListTitle1').removeClass(\"plugin_mydashboard_menuDashboardListTitle1Opened\");
                  if (!isOpened) {
                     $(this).addClass(\"plugin_mydashboard_menuDashboardListTitle1Opened\");
                  }
                    $('.plugin_mydashboard_menuDashboardListTitle1').not(this).next(\"div\").slideUp('fast');
                    $(this).next(\"div\").slideToggle('fast');
                });
                //This part is about lists of lists of widgets (when there are much widgets)
                //Every list of list are closed at the beginning
               //   $('.plugin_mydashboard_menuDashboardList2').slideUp('fast');
                //Binding when user want to unfold/fold a list of widget
                $('.plugin_mydashboard_menuDashboardListTitle2').click(function () {
                    var isOpened = $(this).hasClass('plugin_mydashboard_menuDashboardListTitle1Opened');
                    $('.plugin_mydashboard_menuDashboardListTitle2').removeClass(\"plugin_mydashboard_menuDashboardListTitle1Opened\");
                  if (!isOpened) {
                     $(this).addClass(\"plugin_mydashboard_menuDashboardListTitle1Opened\");
                  }
                    $('.plugin_mydashboard_menuDashboardListTitle2').not(this).next(\"div\").slideUp('fast');
                    $(this).next(\"div\").slideToggle('fast');
                });
                //===================Stop:AccordionEffect=================================
                //===================Start:ListItem click=================================
                //handling click on all listitem (button to add a specific widget), -> getWidget with data stored in a custom attribute (html5 prefixed as data-*)
                //XACA
                $('.plugin_mydashboard_menuDashboardListItem').click(function () {
                    var dashboardId = $(this).parents('.plugin_mydashboard_menuDashboard').attr('data-dashboardid');
                    var widgetId = $(this).attr('data-widgetid');
                    var classname = $(this).attr('data-classname');
                    var attrview = $(this).attr('data-view');
                    var view = \"\";
                  if (typeof attrview != \"undefined\") {
                     view = \"<span class='plugin_mydashboard_discret'>&nbsp;-&nbsp;\" + attrview + \"</span>\";
                  }
                  if (addNewWidget(widgetId) === true) {
//                      $(\"#success-alert\").fadeTo(2000, 500).slideUp(500, function () {
//                          $(\"#success-alert\").slideUp(500);
//                      });
                      $('.plugin_mydashboard_menuDashboard').hide();
                  } else {
                      //error
//                      $(\"#error-alert\").fadeTo(2000, 500).slideUp(500, function () {
//                          $(\"#error-alert\").slideUp(500);
//                      });
                  }
                });
            });

        </script>";

        PluginMydashboardWidgetlist::loadWidgetsListForMenu($widgetlist, $used, $wl, $gslist);

        return $wl;
    }


    /**
     * @return string
     */
    public function getscripts()
    {
        $wl = "<script>

            $(document).ready(function () {

                 $('#load-widgets').click(function () {
                    launchloadWidgets();
                });
                 $('#clear-grid').click(function () {
                    launchClearGrid();
                });
                 $('#header_fullscreen').click(function () {
                    launchFullscreen();
                });
                 $('#edit-grid').click(function () {
                    launchEditMode();
                });
                 $('#edit-default-grid').click(function () {
                    launchEditDefaultMode();
                });
                 $('#close-edit').click(function () {
                    launchCloseEditMode();
                });
                 $('#save-grid').click(function () {
                    launchSaveGrid();
                });
                 $('#save-default-grid').click(function () {
                    launchSaveDefaultGrid();
                });
                 $('#drag-grid').click(function () {
                    launchDragGrid();
                });
                 $('#undrag-grid').click(function () {
                    launchUndragGrid();
                });
            });

        </script>";

        return $wl;
    }

    /**
     * Initialization of widgets at installation
     */
    public static function installWidgets()
    {
        $widgetlist = PluginMydashboardWidgetlist::getList(false);

        $widgetDB = new PluginMydashboardWidget();

        foreach ($widgetlist as $widgetclasses) {
            foreach ($widgetclasses as $widgetclass => $widgets) {
                foreach ($widgets as $widgetview => $widgetlist) {
                    if (is_array($widgetlist)) {
                        foreach ($widgetlist as $widgetId => $widgetTitle) {
                            if (is_numeric($widgetId)) {
                                $widgetId = $widgetTitle;
                            }
                            $widgetDB->saveWidget($widgetId);
                        }
                    } else {
                        if (is_numeric($widgetview)) {
                            $widgetview = $widgetlist;
                        }
                        $widgetDB->saveWidget($widgetview);
                    }
                }
            }
        }
    }

    /**
     * Stores every widgets in Database (see PluginMydashboardWidget)
     */
    private function initDBWidgets()
    {
        $widgetDB    = new PluginMydashboardWidget();
        $dbu         = new DbUtils();
        $widgetsinDB = $dbu->getAllDataFromTable(PluginMydashboardWidget::getTable());

        $widgetsnames = [];
        foreach ($widgetsinDB as $widget) {
            $widgetsnames[$widget['name']] = $widget['id'];
        }

        foreach ($this->widgets as $classname => $classwidgets) {
            foreach ($classwidgets as $widgetId => $view) {
                if (!isset($widgetsnames[$widgetId])) {
                    $widgetDB->saveWidget($widgetId);
                }
            }
        }
    }


    /**
     * Get an array of widgetNames as ["id1","id2"] for a specifid users_id
     *
     * @param int $id user id
     *
     * @return array of string
     */
    private function getDashboardForUser($id)
    {
        $interface = (Session::getCurrentInterface() == 'central') ? 1 : 0;
        $user_widget     = new PluginMydashboardUserWidget($id, $interface);
        return $user_widget->getWidgets();
    }

    //   /**
    //    * Get the widget index on dash, to add it in the correct order
    //    *
    //    * @param type $name
    //    *
    //    * @return int if $name is in self::dash, FALSE otherwise
    //    */
    //   private function getIndexOnDash($name) {
    //      return array_search($name, $this->dashboard);
    //   }

    /**
     * Get all plugin names of plugin hooked with mydashboard
     * @return array of string
     * @global type $PLUGIN_HOOKS
     */
    private function getPluginsNames()
    {
        global $PLUGIN_HOOKS;
        $plugins_hooked = (isset($PLUGIN_HOOKS['mydashboard']) ? $PLUGIN_HOOKS['mydashboard'] : []);
        $tab            = [];
        foreach ($plugins_hooked as $plugin_name => $x) {
            $tab[$plugin_name] = $this->getLocalName($plugin_name);
        }
        return $tab;
    }

    /**
     * Get the translated name of the plugin $plugin_name
     *
     * @param string $plugin_name
     *
     * @return string
     */
    private function getLocalName($plugin_name)
    {
        $infos = Plugin::getInfo($plugin_name);
        return isset($infos['name']) ? $infos['name'] : $plugin_name;
    }

    /**
     * Display an information in the top left corner of the mydashboard
     *
     * @param type $text
     */
    //    private function displayInfo($text) {
    //        if(is_string($text)) {
    //            $this->infos .= $text;
    //        }
    //    }

    /**
     * Get all languages for a specific library
     *
     * @param $libraryname
     *
     * @return array $languages
     * @internal param string $name name of the library :
     *    Currently available :
     *        sDashboard (for Datatable),
     *        mydashboard (for our own)
     */
    public function getJsLanguages($libraryname)
    {
        $languages = [];
        switch ($libraryname) {
            case "datatables":
                $languages['sEmptyTable']    = __('No data available in table', 'mydashboard');
                $languages['sInfo']          = __('Showing _START_ to _END_ of _TOTAL_ entries', 'mydashboard');
                $languages['sInfoEmpty']     = __('Showing 0 to 0 of 0 entries', 'mydashboard');
                $languages['sInfoFiltered']  = __('(filtered from _MAX_ total entries)', 'mydashboard');
                $languages['sInfoPostFix']   = __('');
                $languages['sInfoThousands'] = __(',');
                //$languages['aLengthMenu']     = __('Show _MENU_ entries', 'mydashboard');
                $languages['sLoadingRecords'] = __('Loading') . "...";
                $languages['sProcessing']     = __('Processing') . "...";
                $languages['sSearch']         = __('Search') . ":";
                $languages['sZeroRecords']    = __('No matching records found', 'mydashboard');
                $languages['oPaginate']       = [
                    'sFirst'    => __('First'),
                    'sLast'     => __('Last'),
                    'sNext'     => " " . __('Next'),
                    'sPrevious' => __('Previous')
                ];
                $languages['oAria']           = [
                    'sSortAscending'  => __(': activate to sort column ascending', 'mydashboard'),
                    'sSortDescending' => __(': activate to sort column descending', 'mydashboard')
                ];
                $languages['select']          = [
                    "rows" => [
                        "_" => "",// __('You have selected %d rows', 'mydashboard')
                        //                  "0" => "Click a row to select",
                        "1" => __('1 row selected', 'mydashboard')
                    ]
                ];

                $languages['close']    = __("Close", "mydashboard");
                $languages['maximize'] = __("Maximize", "mydashboard");
                $languages['minimize'] = __("Minimize", "mydashboard");
                $languages['refresh']  = __("Refresh", "mydashboard");
                $languages['buttons']  = [
                    'colvis'     => __('Column visibility', 'mydashboard'),
                    "pageLength" => [
                        "_"  => __('Show %d elements', 'mydashboard'),
                        "-1" => __('Show all', 'mydashboard'),
                    ],
                ];
                break;
            case "mydashboard":
                $languages["dashboardsliderClose"]   = __("Close", "mydashboard");
                $languages["dashboardsliderOpen"]    = __("Dashboard", 'mydashboard');
                $languages["dashboardSaved"]         = __("Dashboard saved", 'mydashboard');
                $languages["dashboardNotSaved"]      = __("Dashboard not saved", 'mydashboard');
                $languages["dataReceived"]           = __("Data received for", 'mydashboard');
                $languages["noDataReceived"]         = __("No data received for", 'mydashboard');
                $languages["refreshAll"]             = __("Updating all widgets", 'mydashboard');
                $languages["widgetAddedOnDashboard"] = __("Widget added on Dashboard", "mydashboard");
                break;
        }
        return $languages;
    }



    /**
     * Log $msg only when DEBUG_MODE is set
     *
     * @param int $active_profile
     */
    //   private function debug($msg) {
    //      if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
    //         Toolbox::logDebug($msg);
    //      }
    //   }


    /***********************/

    /**
     * @param int $active_profile
     */
    public function loadDashboard($active_profile = -1, $predefined_grid = 0)
    {
        echo Html::css(PLUGIN_MYDASHBOARD_NOTFULL_DIR . "/css/style_bootstrap_new.css");
        echo Html::script(PLUGIN_MYDASHBOARD_NOTFULL_DIR . "/lib/jquery-ui/jquery-ui.min.js");
        echo Html::css(PLUGIN_MYDASHBOARD_NOTFULL_DIR . "/lib/jquery-ui/jquery-ui.min.css");
        //
        Html::requireJs('gridstack');
        echo Html::css("/public/lib/gridstack.css");
        echo Html::css("/css/standalone/gridstack-grids.scss");
        echo Html::css(PLUGIN_MYDASHBOARD_NOTFULL_DIR . "/css/mydashboard.css.php");
        //       echo Html::script(PLUGIN_MYDASHBOARD_NOTFULL_DIR."/lib/gridstack/src/gridstack.jQueryUI.js");
        echo Html::script(PLUGIN_MYDASHBOARD_NOTFULL_DIR . "/lib/jquery-fullscreen-plugin/jquery.fullscreen-min.js");
        echo Html::script(PLUGIN_MYDASHBOARD_NOTFULL_DIR . "/lib/fuze.js");
        echo Html::script(PLUGIN_MYDASHBOARD_NOTFULL_DIR . "/lib/fuzzysearch.js.php");

        echo Html::css(PLUGIN_MYDASHBOARD_NOTFULL_DIR . "/lib/datatables/Buttons-2.2.3/css/buttons.dataTables.min.css");
        echo Html::css(PLUGIN_MYDASHBOARD_NOTFULL_DIR . "/lib/datatables/ColReorder-1.5.6/css/colReorder.dataTables.min.css");
        echo Html::css(PLUGIN_MYDASHBOARD_NOTFULL_DIR . "/lib/datatables/datatables.min.css");
        echo Html::css(PLUGIN_MYDASHBOARD_NOTFULL_DIR . "/lib/datatables/Responsive-2.3.0/css/responsive.dataTables.min.css");
        echo Html::css(PLUGIN_MYDASHBOARD_NOTFULL_DIR . "/lib/datatables/Select-1.4.0/css/select.dataTables.min.css");

        echo Html::script(PLUGIN_MYDASHBOARD_NOTFULL_DIR . "/lib/datatables/datatables.min.js");
        echo Html::script(PLUGIN_MYDASHBOARD_NOTFULL_DIR . "/lib/datatables/Responsive-2.3.0/js/dataTables.responsive.min.js");
        echo Html::script(PLUGIN_MYDASHBOARD_NOTFULL_DIR . "/lib/datatables/Select-1.4.0/js/dataTables.select.min.js");
        echo Html::script(PLUGIN_MYDASHBOARD_NOTFULL_DIR . "/lib/datatables/Buttons-2.2.3/js/dataTables.buttons.min.js");
        echo Html::script(PLUGIN_MYDASHBOARD_NOTFULL_DIR . "/lib/datatables/Buttons-2.2.3/js/buttons.html5.min.js");
        echo Html::script(PLUGIN_MYDASHBOARD_NOTFULL_DIR . "/lib/datatables/Buttons-2.2.3/js/buttons.print.min.js");
        echo Html::script(PLUGIN_MYDASHBOARD_NOTFULL_DIR . "/lib/datatables/Buttons-2.2.3/js/buttons.colVis.min.js");
        echo Html::script(PLUGIN_MYDASHBOARD_NOTFULL_DIR . "/lib/datatables/ColReorder-1.5.6/js/dataTables.colReorder.min.js");
        echo Html::script(PLUGIN_MYDASHBOARD_NOTFULL_DIR . "/lib/datatables/JSZip-2.5.0/jszip.min.js");
        echo Html::script(PLUGIN_MYDASHBOARD_NOTFULL_DIR . "/lib/datatables/pdfmake-0.1.36/pdfmake.min.js");
        echo Html::script(PLUGIN_MYDASHBOARD_NOTFULL_DIR . "/lib/datatables/pdfmake-0.1.36/vfs_fonts.js");

        echo Html::script(PLUGIN_MYDASHBOARD_NOTFULL_DIR . "/lib/echarts/echarts.js");
        $theme = PluginMydashboardPreference::getPalette(Session::getLoginUserID());
        echo Html::script(PLUGIN_MYDASHBOARD_NOTFULL_DIR . "/lib/echarts/theme/$theme.js");
        echo Html::script(PLUGIN_MYDASHBOARD_NOTFULL_DIR . "/lib/html2canvas.min.js");
        echo Html::script(PLUGIN_MYDASHBOARD_NOTFULL_DIR . "/lib/jspdf.umd.js");

        echo Html::script(PLUGIN_MYDASHBOARD_NOTFULL_DIR . "/lib/jquery-advanced-news-ticker/jquery.newsTicker.min.js");
        //        echo Html::script(PLUGIN_MYDASHBOARD_NOTFULL_DIR . "/lib/fileSaver.min.js");
        echo Html::script(PLUGIN_MYDASHBOARD_NOTFULL_DIR . "/lib/circles/circles.min.js");
        echo Html::script(PLUGIN_MYDASHBOARD_NOTFULL_DIR . "/lib/countUp.min.js");
        echo Html::script(PLUGIN_MYDASHBOARD_NOTFULL_DIR . "/lib/countUp-jquery.js");

        $rand           = mt_rand();
        $this->users_id = Session::getLoginUserID();
        $this->showMenu($rand, $this->users_id, $active_profile, $predefined_grid);

        $this->initDBWidgets();
        $grid = [];

        $list = $this->getDashboardForUser($this->users_id);
        $data = [];
        if (count($list) > 0) {
            foreach ($list as $k => $v) {
                $id = PluginMydashboardWidget::getGsID($v);
                if ($id) {
                    $data[] = ["id" => $id, "x" => 6, "y" => 6, "width" => 4, "height" => 6];
                }
            }
            $grid = json_encode($data);
        }
        //LOAD WIDGETS
        $edit = PluginMydashboardPreference::checkEditMode(Session::getLoginUserID());
        $drag = PluginMydashboardPreference::checkDragMode(Session::getLoginUserID());
        //WITHOUTH PREFS
        $dashboard     = new PluginMydashboardDashboard();
        $options_users = ["users_id" => Session::getLoginUserID(), "profiles_id" => $active_profile];
        $id_user       = PluginMydashboardDashboard::checkIfPreferenceExists($options_users);

        if ($id_user == 0 || $edit == 2) {
            $options = ["users_id" => 0, "profiles_id" => $active_profile];
            $id      = PluginMydashboardDashboard::checkIfPreferenceExists($options);
            if ($dashboard->getFromDB($id)) {
                $grid = stripslashes($dashboard->fields['grid']);
            }
        }
        //WITH PREFS
        if ($edit != 2) {
            if ($dashboard->getFromDB($id_user)) {
                $grid = stripslashes($dashboard->fields['grid']);
            }
        }
        //LOAD PREDEFINED GRID
        if ($predefined_grid > 0) {
            $grid = PluginMydashboardDashboard::loadPredefinedDashboard($predefined_grid);
        }
        $datagrid             = [];
        $datajson             = [];
        $optjson              = [];
        $widgets              = [];
        $displayed_widgets    = [];
        $displayed_widgets_id = [];

        if (!empty($grid) && ($datagrid = json_decode($grid, true)) == !null) {
            $widgets = PluginMydashboardWidget::getWidgetList();

            foreach ($datagrid as $k => $v) {
                if (isset($v["id"])) {
                    $datajson[$v["id"]] = PluginMydashboardWidget::getWidget($v["id"], $widgets, []);

                    $obj = new PluginMydashboardWidget();

                    $id          = substr($v["id"], 2);
                    $widget_name = $obj->getWidgetNameById($id);
                    if (strpos($widget_name, "PluginMydashboardReports_Bar") === 0
                        || strpos($widget_name, "PluginMydashboardReports_Line") === 0
                        || strpos($widget_name, "PluginMydashboardReports_Pie") === 0
                        || strpos($widget_name, "PluginMydashboardReports_Funnel") === 0
                        || strpos($widget_name, "PluginMydashboardReports_Custom") === 0) {
                        $displayed_widgets[]    = $widget_name;
                        $displayed_widgets_id[] = $v["id"];
                    }
                }
            }
        } else {
            echo "<div class='alert alert-warning alert-important' id='warning-alert'>
                <strong>" . __('Warning', 'mydashboard') . "!</strong>
                " . __('No widgets founded, please add widgets', 'mydashboard') . "
            </div>";
            //         echo Html::scriptBlock('$("#warning-alert").fadeTo(2000, 500).slideUp(500, function(){
            //            $("#success-alert").slideUp(500);
            //         });');

            $grid = json_encode($grid);
        }

        $datajson = json_encode($datajson);
        $optjson  = json_encode($optjson);

        //FOR ADD NEW WIDGET
        $allwidgetjson = [];

        if ($edit > 0) {
            if (isset($_SESSION["glpi_plugin_mydashboard_allwidgets"])
                && count($_SESSION["glpi_plugin_mydashboard_allwidgets"]) > 0) {
                $allwidgetjson = $_SESSION["glpi_plugin_mydashboard_allwidgets"];
            } else {
                //            if (empty($grid) && count($widgets) < 1) {
                $widgets = PluginMydashboardWidget::getWidgetList();
                //            }
                foreach ($widgets as $k => $val) {
                    $allwidgetjson[$k] = ["<div class='alert alert-success' id='success-alert'>
                <strong>" . __('Success', 'mydashboard') . "</strong> - 
                " . __('Save grid to see widget', 'mydashboard') . "
            </div>"];
                    //NOT LOAD ALL WIDGETS FOR PERF
                    //               $allwidgetjson[$k] = PluginMydashboardWidget::getWidget($k, [], $widgets);
                }
            }
        }
        $allwidgetjson = json_encode($allwidgetjson);
        $msg_delete    = __('Delete widget', 'mydashboard');
        $msg_error     = __('No data available', 'mydashboard');
        $msg_refresh   = __('Refresh widget', 'mydashboard');
        $disableResize = 'true';
        $disableDrag   = 'true';
        $delete_button = 'false';

        if ($drag > 0) {
            $disableResize = 'false';
            $disableDrag   = 'false';
        }
        if ($edit > 0) {
            $delete_button = 'true';
        }

        $all_displayed_widgets    = json_encode($displayed_widgets);
        $all_displayed_widgets_id = json_encode($displayed_widgets_id);

        echo "<div id='mygrid$rand' class='mygrid'>";
        echo "<div class='grid-stack$rand grid-stack md-grid-stack'>";
        echo "</div>";

        echo "<script type='text/javascript'>
        $(function () {
            var options = {
                 cellHeight: 41,
                 disableResize: $disableResize,
                 disableDrag: $disableDrag,
                 margin: 2,
                 resizable: {
                    handles: 'e, se, s, sw, w'
                }
            };
            let grid = GridStack.init(options);
            new function () {
                this.loadGrid = function () {
                    grid.removeAll();
                    var items = $grid;
                     items.forEach(function(node)  {
                         var nodeid = node.id;
//                         var optArray = $optjson;
                         var widgetArray = $datajson; 
                         var widget = widgetArray['' + nodeid + ''];
                         if ( widget !== undefined ) {
                            widget = widgetArray['' + nodeid + ''];
                         } else {
                             widget = '$msg_error';
                         }
//                         var opt = optArray['' + nodeid + ''];
//                         if ( opt !== undefined ) {
//                            options = optArray['' + nodeid + ''];
//                            if ( options != null ) {
//                               refreshopt = optArray['' + nodeid + '']['enableRefresh'];
//                            } else {
//                                refreshopt = false;
//                            }
//                         } else {
//                             refreshopt = false;
//                         }
                         var delbutton = '';
                         var refreshbutton = '';
                         if ($delete_button == 1) {
                            var delbutton = '<button title=\"$msg_delete\" class=\"md-button pull-left\" onclick=\"deleteWidget(\'' + node.id + '\');\"><i class=\"ti ti-circle-x md-close\"></i></button>';
                         }
//                         if (refreshopt == 1) {
                            var refreshbutton = '<button title=\"$msg_refresh\" class=\"md-button refresh-icon pull-right\" onclick=\"refreshWidget(\'' + node.id + '\');\"><i class=\"ti ti-refresh\"></i></button>';
//                         } else {
//                            var refreshbutton = '<button title=\"$msg_refresh\" class=\"md-button refresh-icon-disabled pull-right\"><i class=\"ti ti-refresh\"></i></button>';
//                         }
                         if ( nodeid !== undefined ) {
                         var el = '<div class=\"grid-stack-item\"><div class=\"grid-stack-item-content md-grid-stack-item-content\" id=\"gridcontent' + nodeid + '\">' + refreshbutton + delbutton + widget + '</div></div>';

                         grid.addWidget(el,
                                            {
                                               x: node.x,
                                               y: node.y,
                                               w: node.w,
                                               h: node.h,
                                               id: node.id,
//                                               autoPosition: true,
                                            }
                                         );
                         refreshWidget(node.id);
                            }
                    }, this);
                    return false;
                }.bind(this);
                
                this.loadGrid();
                
            };
            deleteWidget = function(value) {
                widget = 'div[gs-id='+ value + ']';
                grid.removeWidget(widget);
            }
            
            addNewWidget = function(value) {
                if (value != 0){
                        var widgetArray = $allwidgetjson; 
                        widget = widgetArray['' + value + ''];
                        var el = '<div class=\"grid-stack-item\"><div class=\"grid-stack-item-content md-grid-stack-item-content\">' +
                                 '<button class=\"md-button pull-left\" onclick=\"deleteWidget(\'' + value + '\');\">' +
                                  '<i class=\"ti ti-circle-x md-close\"></i></button>' + widget + '</div></div>';
        //                grid = $('.grid-stack').data('gridstack');
                        grid.addWidget(
                                                    el,
                                                    {
                                                       x: 0,
                                                       y: 0,
                                                       w: 4,
                                                       h: 12,
                                                       id: value
                                                    }
                                                 );
                        refreshWidget(value);
                     }
            }
            // 3.1 full method saving the grid options + children (which is recursive for nested grids)
            launchSaveGrid = function() {
                delete serializedFull;
                serializedData = grid.save(false);
                var sData = JSON.stringify(serializedData);
                var profiles_id = -1;
                $('#ajax_loader').show();
                $.ajax({
                   url: '" . PLUGIN_MYDASHBOARD_WEBDIR . "/ajax/saveGrid.php',
                   type: 'POST',
                   data:{data:sData,profiles_id:$active_profile},
                   success:function(data) {
                          $('#ajax_loader').hide();
                          window.location.href = '" . PLUGIN_MYDASHBOARD_WEBDIR . "/front/menu.php';
                       }
                   });
            }
            
            launchSaveDefaultGrid = function() {
                delete serializedFull;
                serializedData = grid.save(false);
                var sData = JSON.stringify(serializedData);
                var users_id = 0;
                var profiles_id = -1;
                $('#ajax_loader').show();
                $.ajax({
                      url: '" . PLUGIN_MYDASHBOARD_WEBDIR . "/ajax/saveGrid.php',
                      type: 'POST',
                      data:{data:sData,users_id:users_id,profiles_id:$active_profile},
                      success:function(data) {
                         $('#ajax_loader').hide();
                         var redirectUrl = '" . PLUGIN_MYDASHBOARD_WEBDIR . "/front/menu.php';
                         var form = $('<form action=\"' + redirectUrl + '\" method=\"post\">' +
                         '<input type=\"hidden\" name=\"profiles_id\" value=\"$active_profile\"></input>' +
                         '<input type=\"hidden\" name=\"_glpi_csrf_token\" value=\"' + data +'\"></input>'+ 
                        '</form>');
                         $('body').append(form);
                         $(form).submit();
                      }
                   });
            }
        });
        
     
    </script>";
        echo "<script type='text/javascript'>
        function launchloadWidgets() {
           var modal = $('<div>').dialog({ modal: true });
            modal.dialog('widget').hide();
            $('#ajax_loader').show();
            $.ajax({
              url: '" . PLUGIN_MYDASHBOARD_WEBDIR . "/ajax/loadWidgets.php',
                 type: 'POST',
                 complete: function () {
                          //back to normal!
                          $('#ajax_loader').hide();
                          modal.dialog('close');
                          window.location.href = '" . PLUGIN_MYDASHBOARD_WEBDIR . "/front/menu.php';
                      }
                 });
        }
        function launchClearGrid() {
           $('#ajax_loader').show();
            $.ajax({
              url: '" . PLUGIN_MYDASHBOARD_WEBDIR . "/ajax/clearGrid.php',
                 type: 'POST',
                 success:function(data) {
                        $('#ajax_loader').hide();
                        window.location.href = '" . PLUGIN_MYDASHBOARD_WEBDIR . "/front/menu.php';
                     }
                 });
        }
        function launchFullscreen() {
           $('#mygrid$rand').toggleFullScreen();
           $('#mygrid$rand').toggleClass('fullscreen_view');
        }
        function launchEditMode() {
          $('#ajax_loader').show();
            $.ajax({
              url: '" . PLUGIN_MYDASHBOARD_WEBDIR . "/ajax/editGrid.php',
                 type: 'POST',
                 data:{edit_mode:1},
                 success:function(data) {
                        $('#ajax_loader').hide();
                        window.location.href = '" . PLUGIN_MYDASHBOARD_WEBDIR . "/front/menu.php';
                    }
                 });
        }
        function launchEditDefaultMode() {
          $('#ajax_loader').show();
                  $.ajax({
                    url: '" . PLUGIN_MYDASHBOARD_WEBDIR . "/ajax/editGrid.php',
                       type: 'POST',
                       data:{edit_mode:2},
                       success:function(data) {
                              $('#ajax_loader').hide();
                              window.location.href = '" . PLUGIN_MYDASHBOARD_WEBDIR . "/front/menu.php';
                          }
                       });
        }
        function launchCloseEditMode() {
           $('#ajax_loader').show();
            $.ajax({
              url: '" . PLUGIN_MYDASHBOARD_WEBDIR . "/ajax/editGrid.php',
                 type: 'POST',
                 data:{edit_mode:0},
                 success:function(data) {
                        $('#ajax_loader').hide();
                        window.location.href = '" . PLUGIN_MYDASHBOARD_WEBDIR . "/front/menu.php';
                    }
                 });
        }
        function launchDragGrid() {
           $('#ajax_loader').show();
            $.ajax({
              url: '" . PLUGIN_MYDASHBOARD_WEBDIR . "/ajax/dragGrid.php',
                 type: 'POST',
                 data:{drag_mode:1},
                 success:function(data) {
                        $('#ajax_loader').hide();
                        window.location.href = '" . PLUGIN_MYDASHBOARD_WEBDIR . "/front/menu.php';
                    }
                 });
        }
        function launchUndragGrid() {
           $('#ajax_loader').show();
            $.ajax({
              url: '" . PLUGIN_MYDASHBOARD_WEBDIR . "/ajax/dragGrid.php',
                 type: 'POST',
                 data:{drag_mode:0},
                 success:function(data) {
                        $('#ajax_loader').hide();
                        window.location.href = '" . PLUGIN_MYDASHBOARD_WEBDIR . "/front/menu.php';
                    }
                 });
        }

        function refreshWidget (id) {
            var widgetOptionsObject = [];
            $.ajax({
              url: '" . PLUGIN_MYDASHBOARD_WEBDIR . "/ajax/refreshWidget.php',
              type: 'POST',
              data:{gsid:id, params:widgetOptionsObject},
              dataType: 'json',
              success:function(data) {
                  var wid = data.id;
                  var wdata = data.widget;
                  var widget = $('div[id='+ wid + ']');
                  widget.replaceWith(wdata);
              }
           });
             return false;
        };
        function refreshWidgetByForm (id, gsid, formId) {
           var widgetOptions = $('#' + formId).serializeArray();
           var widgetOptionsObject = {};
           $.each(widgetOptions,
              function (i, v) {
                 var name = v.name;
                 // Remove [] in the name do issue with ajax
                 var index = v.name.indexOf('[]');
                 if( index != -1 ){
                    name = v.name.substring(0, index);
                 }
                 // Key already exist
                 if(name in widgetOptionsObject){
                    if(widgetOptionsObject[name] instanceof Array){
                       widgetOptionsObject[name].push(v.value);
                    }else{
                       var tempArray = [];
                       tempArray.push(widgetOptionsObject[name]);
                       tempArray.push(v.value);
                       widgetOptionsObject[name] = tempArray;
                    }
                 }else{
                    widgetOptionsObject[name] = v.value;
                 }
              }
           );           
           var widget = $('div[id='+ id + ']');
           $.ajax({
              url: '" . PLUGIN_MYDASHBOARD_WEBDIR . "/ajax/refreshWidget.php',
              type: 'POST',
              data:{
                  gsid:gsid,
                  params:widgetOptionsObject,
                  id:id
              },
              success:function(data) {
                  widget.replaceWith(data);
              }
           });
           return false;
        };
         
          
//         function downloadGraph(id) {
////             if (!isChartRendered) return; // return if chart not rendered
//                html2canvas(document.getElementById(id), {
//                 onrendered: function(canvas) {
//                     var link = document.createElement('a');
//                    link.href = canvas.toDataURL('image/png');
//                    
//                    if (!HTMLCanvasElement.prototype.toBlob) {
//                     Object.defineProperty(HTMLCanvasElement.prototype, 'toBlob', {
//                       value: function (callback, type, quality) {
//                         var canvas = this;
//                         setTimeout(function() {
//                           var binStr = atob( canvas.toDataURL(type, quality).split(',')[1] ),
//                           len = binStr.length,
//                           arr = new Uint8Array(len);
//                  
//                           for (var i = 0; i < len; i++ ) {
//                              arr[i] = binStr.charCodeAt(i);
//                           }
//                  
//                           callback( new Blob( [arr], {type: type || 'image/png'} ) );
//                         });
//                       }
//                    });
//                  }
//                       
//                  canvas.toBlob(function(blob){
//                   link.href = URL.createObjectURL(blob);
//                   saveAs(blob, 'myChart.png');
//                 },'image/png');                      
//              }
//            })
//         }
    </script>";

        echo "</div>";

        if (!empty($grid) && ($datagrid = json_decode($grid, true)) == !null) {
            echo "<button class='btn btn-info' id='exportByHTML' style='float: right;margin-bottom: 25px;'>";
            echo __("Export to PDF", "mydashboard") . "</button>";

            echo "<script type='text/javascript'>

            function loadImage(src) {
              return new Promise((resolve, reject) => {
                const img = new Image();
                img.onload = () => resolve(img);
                img.onerror = reject;
                img.src = src;
              });
            }
            
            function getChartImage(chart) {
              return loadImage(chart.getDataURL());
            }
            
            const btnExportHTML = document.getElementById('exportByHTML');
            widgets = $all_displayed_widgets;
            widgetsid = $all_displayed_widgets_id;
            btnExportHTML.addEventListener('click', async () => {
//            console.log('exporting...');
            try {
                const doc = new jspdf.jsPDF({
                      unit: 'px',
                      orientation: 'l',
                      hotfixes: ['px_scaling']
                    });
                    // another way:
                    
                    for (var i = 0; i < widgets.length; i++) {
                        var widgetname ='#'+widgets[i];

                        const canvas = await html2canvas(document.querySelector(widgetname));
                        const img = await loadImage(canvas.toDataURL());
                        chart1 = echarts.init(document.getElementById(widgets[i]));
                        dpr = chart1.getDevicePixelRatio();
                        
                        const pageWidth = doc.internal.pageSize.getWidth();
                        const pageHeight = doc.internal.pageSize.getHeight();
                    
                        const widthRatio = pageWidth / canvas.width;
                        const heightRatio = pageHeight / canvas.height;

                        const canvasWidth = img.width / dpr;
                        const canvasHeight = img.height / dpr;
                        
                        const marginX = (pageWidth - canvasWidth) / 2;
                        const marginY = (pageHeight - canvasHeight) / 2;
                    
                        doc.addImage(img.src, 'PNG', marginX, marginY, canvasWidth, canvasHeight);
                        if (i < widgets.length-1) {
                            doc.addPage();
                        }
                     }
                    for (var i = 0; i < widgetsid.length; i++) {
                       refreshWidget(widgetsid[i]);
                     }
                     await doc.save('dashboard_report.pdf');
                  } catch (e) {
                console.error('failed to export', e);
            }
//            console.log('exported');
        });

          </script>";
        }
    }

    /**
     * Create a side slide panel
     *
     * @param string $name name of the js object
     * @param array  $options Possible options:
     *          - title       Title to display
     *          - position    position (either left or right - defaults to right)
     *          - display     display or get string? (default true)
     *          - icon        Path to aditional icon
     *          - icon_url    Link for aditional icon
     *          - icon_txt    Alternative text and title for aditional icon_
     *
     * @return void|string (see $options['display'])
     */
    public static function createSlidePanel($name, $options = [])
    {
        global $CFG_GLPI;

        $param = [
            'title'    => '',
            'position' => 'right',
            'url'      => '',
            'display'  => true,
            'icon'     => false,
            'icon_url' => false,
            'icon_txt' => false
        ];

        if (count($options)) {
            foreach ($options as $key => $val) {
                if (isset($param[$key])) {
                    $param[$key] = $val;
                }
            }
        }

        $out = "<script type='text/javascript'>\n";
        $out .= "$(function() {";
        $out .= "$('<div id=\'$name\' class=\'slidepanel on{$param['position']}\'><div class=\"header\">" .
                "<button type=\'button\' class=\'close ui-button ui-widget ui-state-default ui-corner-all ui-button-icon-only ui-dialog-titlebar-close\' title=\'" . __s('Close') . "\'><span class=\'ui-button-icon-primary ui-icon ui-icon-closethick\'></span><span class=\'ui-button-text\'>X</span></button>";

        if ($param['icon']) {
            $icon = "<img class=\'icon\' src=\'{$CFG_GLPI['root_doc']}{$param['icon']}\' alt=\'{$param['icon_txt']}\' title=\'{$param['icon_txt']}\'/>";
            if ($param['icon_url']) {
                $out .= "<a href=\'{$param['icon_url']}\'>$icon</a>";
            } else {
                $out .= $icon;
            }
        }

        if ($param['title'] != '') {
            $out .= "<h3>{$param['title']}</h3>";
        }

        $out .= "</div><div class=\'contents\'></div></div>')
         .hide()
         .appendTo('body');\n";
        $out .= "$('#{$name} .close').on('click', function() {
         $('#$name').hide(
            'slow',
            function () {
               $(this).find('.contents').empty();
            }
         );
       });\n";
        $out .= "$('#{$name}Link').on('click', function() {
         $('#$name').show(
            'slow',
            function() {
               _load$name();
            }
         );
      });\n";
        $out .= "});";
        if ($param['url'] != null) {
            $out .= "var _load$name = function() {
            $.ajax({
               url: '{$param['url']}',
               beforeSend: function() {
                  var _loader = $('<div id=\'loadingslide\'><div class=\'loadingindicator\'>" . __s('Loading...') . "</div></div>');
                  $('#$name .contents').html(_loader);
               }
            })
            .always( function() {
               $('#loadingslide').remove();
            })
            .done(function(res) {
               $('#$name .contents').html(res);
            });
         };\n";
        }
        $out .= "</script>";

        if ($param['display']) {
            echo $out;
        } else {
            return $out;
        }
    }
}
