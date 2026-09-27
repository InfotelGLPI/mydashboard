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

use CommonGLPI;
use DbUtils;
use Dropdown;
use Entity;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Group;
use GlpiPlugin\Mydashboard\Criterias\Year;
use Plugin;
use Profile;
use Session;
use Ticket;
use GlpiPlugin\Mydashboard\Preference as MydashboardPreference;

/**
 * Class Menu
 */
class Menu extends CommonGLPI
{
    public const DASHBOARD_NAME = "myDashboard";
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
     * @return string
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
        $profile         = (isset($_SESSION['glpiactiveprofile']['id'])) ? (int) $_SESSION['glpiactiveprofile']['id'] : -1;
        $predefined_grid = 0;

        // This tab is reached through ajax/common.tabs.php, which replays neither the cast
        // nor the ownership rule front/menu.php applies before storing the value: the raw
        // string used to travel to loadDashboard(), and from there to the dashboard row
        // lookup, so another profile's layout was served from its id alone.
        if (isset($_POST["profiles_id"]) && Dashboard::canManageProfile((int) $_POST["profiles_id"])) {
            $profile = (int) $_POST["profiles_id"];
        }
        if (isset($_POST["predefined_grid"])) {
            $predefined_grid = (int) $_POST["predefined_grid"];
        }
        $self = new self();

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
     * Menu constructor.
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
        $config = new Config();
        $config->getConfig();

        self::$_PLUGIN_MYDASHBOARD_CFG['enable_fullscreen']     = $config->fields['enable_fullscreen']; // 0 (FALSE) or 1 (TRUE), enable the possibility to display the mydashboard in fullscreen
        self::$_PLUGIN_MYDASHBOARD_CFG['display_menu']          = $config->fields['display_menu']; // Display the right menu slider
        self::$_PLUGIN_MYDASHBOARD_CFG['replace_central']       = $config->fields['replace_central']; // Replace central interface

        unset($config);

        //Configuration set by User (via My Preferences -> Dashboard tab)
        //General Settings
        $preference = new MydashboardPreference();
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
        $plugin_page = Menu::getSearchURL(false);
        $menu        = [];
        //Menu entry in tools
        $menu['title']           = self::getTypeName();
        $menu['page']            = $plugin_page;
        $menu['links']['search'] = $plugin_page;
        if (Session::haveRightsOr("plugin_mydashboard_config", [CREATE, UPDATE])
            || Session::haveRight("config", UPDATE)) {
            //Entry icon in breadcrumb
            $menu['links']['config'] = Config::getFormURL(false);
        }

        $menu['options']['pluginmydashboardstockwidget'] = [
            'title' => StockWidget::getTypeName(2),
            'page'  => StockWidget::getSearchURL(false),
            'links' => [
                'search' => StockWidget::getSearchURL(false),
                'add'    => StockWidget::getFormURL(false),
            ],
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
     * Data of the offcanvas listing the widgets that can be added to the grid.
     *
     * The click on an entry is handled by public/scripts/mydashboard-grid.js.
     *
     * @param array    $used gsids already placed on the grid
     *
     * @return array{categories: array}
     */
    private function getWidgetsOffcanvasData(int $active_profile, array $used): array
    {
        $gslist = [];
        foreach (Widget::getCachedWidgetList() as $gs => $widgetclasses) {
            $gslist[$widgetclasses['id']] = $gs;
        }
        $widgetlist = Widgetlist::getList(true, $active_profile);

        return [
            'categories' => Widgetlist::getWidgetsCategoriesForMenu($widgetlist, $used, $gslist),
        ];
    }

    /**
     * Data of the toolbar displayed above the grid in edit mode.
     *
     * @return array<string, mixed>
     */
    private function getEditToolbarData(int $edit, int $selected_profile, int $drag): array
    {
        global $DB;

        $badge = __('Edit mode', 'mydashboard');
        if ($edit == 2) {
            $badge .= ' ' . __('Global', 'mydashboard');
        }

        $actions = [];
        if ($edit == 1) {
            $actions[] = ['id' => 'save-grid', 'class' => 'btn-success',
                'icon' => 'ti ti-device-floppy', 'label' => __('Save grid', 'mydashboard'),
            ];
        }
        if (Session::haveRight("plugin_mydashboard_config", CREATE) && $edit == 2) {
            $actions[] = ['id' => 'save-default-grid', 'class' => 'btn-success',
                'icon' => 'ti ti-layout-grid', 'label' => __('Save grid', 'mydashboard'),
            ];
        }
        $actions[] = ['id' => 'clear-grid', 'class' => 'btn-danger',
            'icon' => 'ti ti-trash', 'label' => __('Clear grid', 'mydashboard'),
        ];
        if ($drag < 1 && Session::haveRight("plugin_mydashboard_edit", 6)) {
            $actions[] = ['id' => 'drag-grid', 'class' => 'btn-outline-warning',
                'icon' => 'ti ti-lock', 'label' => __('Permit drag / resize widgets', 'mydashboard'),
            ];
        }
        if ($drag > 0 && Session::haveRight("plugin_mydashboard_edit", 6)) {
            $actions[] = ['id' => 'undrag-grid', 'class' => 'btn-outline-success',
                'icon' => 'ti ti-lock-open', 'label' => __('Block drag / resize widgets', 'mydashboard'),
            ];
        }

        // Profile selector, in global admin mode only
        $profiles = null;
        if (Session::haveRight("plugin_mydashboard_config", CREATE) && $edit == 2) {
            $iterator = $DB->request([
                'SELECT'    => ['glpi_profiles.name', 'glpi_profiles.id'],
                'FROM'      => Profile::getTable(),
                'LEFT JOIN' => [
                    'glpi_profilerights' => [
                        'FKEY' => [
                            'glpi_profilerights' => 'profiles_id',
                            'glpi_profiles'      => 'id',
                        ],
                    ],
                ],
                'WHERE'     => [
                    Profile::getUnderActiveProfileRestrictCriteria(),
                    'glpi_profilerights.name'   => 'plugin_mydashboard',
                    'glpi_profilerights.rights' => ['>', 0],
                ],
                'ORDER'     => 'glpi_profiles.name',
            ]);
            $profiles = [];
            foreach ($iterator as $data) {
                $profiles[] = [
                    'id' => (int) $data['id'],
                    'name' => $data['name'],
                    'selected' => (int) $data['id'] === (int) $selected_profile,
                ];
            }
        }

        $predefined_grids = Dashboard::getPredefinedDashboardName();

        return [
            'form_action' => $this->getSearchURL(),
            'badge' => $badge,
            'actions' => $actions,
            'predefined_grids' => is_array($predefined_grids) ? $predefined_grids : [],
            'profiles' => $profiles,
            'empty_value' => Dropdown::EMPTY_VALUE,
            'current_profile_id' => (int) $_SESSION['glpiactiveprofile']['id'],
        ];
    }

    /**
     * Actions of the toolbar displayed above the grid out of edit mode.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getViewToolbarActions(int $drag): array
    {
        $interface = (Session::getCurrentInterface() == 'central') ? 1 : 0;
        $can_edit = Session::haveRight("plugin_mydashboard_edit", 6);

        $actions = [];
        if ($drag > 0 && $can_edit) {
            $actions[] = ['id' => 'save-grid', 'class' => 'btn-success',
                'icon' => 'ti ti-device-floppy', 'label' => __('Save grid', 'mydashboard'),
            ];
            $actions[] = ['id' => 'undrag-grid', 'class' => 'btn-outline-success',
                'icon' => 'ti ti-lock-open', 'label' => __('Block drag / resize widgets', 'mydashboard'),
            ];
        }
        if ($can_edit) {
            $actions[] = ['id' => 'edit-grid', 'class' => 'btn-primary',
                'icon' => 'ti ti-edit', 'label' => __('Switch to edit mode', 'mydashboard'),
            ];
        }
        if ($drag < 1 && $can_edit) {
            $actions[] = ['id' => 'drag-grid', 'class' => 'btn-outline-warning',
                'icon' => 'ti ti-lock', 'label' => __('Permit drag / resize widgets', 'mydashboard'),
            ];
        }
        if (Session::haveRight("plugin_mydashboard_config", CREATE)) {
            $actions[] = ['id' => 'edit-default-grid', 'class' => 'btn-outline-secondary',
                'icon' => 'ti ti-adjustments', 'label' => __('Custom and save profile grid', 'mydashboard'),
            ];
        }
        $actions[] = ['id' => 'export-pdf', 'class' => 'btn-outline-secondary', 'ms_auto' => true,
            'icon' => 'ti ti-file-type-pdf', 'label' => __("Export to PDF", "mydashboard"),
        ];
        if (self::$_PLUGIN_MYDASHBOARD_CFG['enable_fullscreen'] && $interface === 1) {
            $actions[] = ['id' => 'fullscreen', 'class' => 'btn-info',
                'icon' => 'ti ti-maximize', 'label' => __("Fullscreen", "mydashboard"),
            ];
        }

        return $actions;
    }

    /**
     * Global filters displayed under the toolbar, preset from the user preferences.
     * Any change refreshes every widget of the grid (see mydashboard-grid.js).
     *
     * @return array{filters: array<int, array{label: string, widget: array{0: string, 1: string}, arguments: array<int, mixed>}>, initial: array<string, mixed>}
     */
    private function getGlobalFilterBarData(): array
    {
        $pref = new MydashboardPreference();
        if (!$pref->getFromDB(Session::getLoginUserID())) {
            $pref->initPreferences(Session::getLoginUserID());
            $pref->getFromDB(Session::getLoginUserID());
        }
        $fields = $pref->fields;

        $year_default   = Year::getDefaultValue();
        $type_default   = (int) ($fields['prefered_type'] ?? 0);
        $entity_default = (int) ($fields['prefered_entity'] ?? 0) ?: (int) $_SESSION['glpiactive_entity'];
        $group_default  = json_decode($fields['prefered_group'] ?? '[]', true) ?: [];

        // The dropdown helpers of the core are called by menu_global_filter_bar.html.twig
        $entity_args = [[
            'name'    => 'md_gf_entities_id',
            'value'   => $entity_default,
            'entity'  => $_SESSION['glpiactiveentities'],
        ]];

        $dbu         = new DbUtils();
        $groups_data = $dbu->getAllDataFromTable(Group::getTable(), ['is_assign' => 1]);
        $groups_list = [];
        foreach ($groups_data as $g) {
            $groups_list[$g['id']] = $g['name'];
        }
        $techgroup_args = ['md_gf_technicians_groups_id', $groups_list, [
            'values'              => $group_default,
            'multiple'            => true,
            'width'               => '200px',
            'display_emptychoice' => true,
        ]];

        $type_args = ['md_gf_type', [
            'value'   => $type_default,
            'toadd'   => [0 => Dropdown::EMPTY_VALUE],
        ]];

        $year_range = [];
        $start_year = (int) date('Y') - 10;
        for ($i = 0; $i <= 10; $i++) {
            $year_range[$start_year + $i] = $start_year + $i;
        }
        $year_args = ['md_gf_year', $year_range, [
            'value'   => $year_default,
        ]];

        $filters = [
            ['label' => __('Entity'), 'widget' => [Entity::class, 'dropdown'], 'arguments' => $entity_args],
        ];
        if (!empty($groups_list)) {
            $filters[] = ['label' => __('Technician group'), 'widget' => [Dropdown::class, 'showFromArray'], 'arguments' => $techgroup_args];
        }
        $filters[] = ['label' => __('Type'), 'widget' => [Ticket::class, 'dropdownType'], 'arguments' => $type_args];
        $filters[] = ['label' => __('Year', 'mydashboard'), 'widget' => [Dropdown::class, 'showFromArray'], 'arguments' => $year_args];

        return [
            'filters' => $filters,
            'initial' => [
                'entities_id'           => $entity_default,
                'technicians_groups_id' => $group_default,
                'type'                  => $type_default ?: null,
                'year'                  => $year_default,
            ],
        ];
    }

    /**
     * Initialization of widgets at installation
     */
    public static function installWidgets()
    {
        $widgetlist = Widgetlist::getList(false);

        $widgetDB = new Widget();

        foreach ($widgetlist as $widgetclasses) {
            foreach ($widgetclasses as $widgetclass => $widgets) {
                foreach ($widgets as $widgetview => $widgetlist) {
                    if (is_array($widgetlist)) {
                        foreach ($widgetlist as $widgetId => $widgetTitle) {
                            if (is_numeric($widgetId)) {
                                $widgetId = $widgetTitle;
                            }
                            $widgetDB->saveWidget($widgetId, $widgetclass);
                        }
                    } else {
                        if (is_numeric($widgetview)) {
                            $widgetview = $widgetlist;
                        }
                        $widgetDB->saveWidget($widgetview, $widgetclass);
                    }
                }
            }
        }
    }

    /**
     * Get all plugin names of plugin hooked with mydashboard
     * @return array of string
     * @global $PLUGIN_HOOKS
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
                    'sPrevious' => __('Previous'),
                ];
                $languages['oAria']           = [
                    'sSortAscending'  => __(': activate to sort column ascending', 'mydashboard'),
                    'sSortDescending' => __(': activate to sort column descending', 'mydashboard'),
                ];
                $languages['select']          = [
                    "rows" => [
                        "_" => "",// __('You have selected %d rows', 'mydashboard')
                        //                  "0" => "Click a row to select",
                        "1" => __('1 row selected', 'mydashboard'),
                    ],
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
     * Display the dashboard: toolbar, widget offcanvas, global filters and grid.
     *
     * Everything is rendered by menu_grid.html.twig; the grid itself is driven by
     * public/scripts/mydashboard-grid.js from the configuration passed in a data attribute.
     *
     * @param int $active_profile
     * @param int $predefined_grid
     *
     * @return void
     */
    public function loadDashboard($active_profile = -1, $predefined_grid = 0)
    {
        // Union of the rule of front/menu.php (READ, UPDATE) and of the former showMenu()
        // (CREATE, READ): the "My view" tab reaches this method through
        // ajax/common.tabs.php, which does not replay the page guard.
        if (!Session::haveRightsOr("plugin_mydashboard", [READ, UPDATE, CREATE])) {
            throw new AccessDeniedHttpException();
        }
        $this->users_id = Session::getLoginUserID();
        $active_profile = (int) $active_profile;

        $edit = MydashboardPreference::checkEditMode($this->users_id);
        $drag = MydashboardPreference::checkDragMode($this->users_id);

        $grid          = '';
        $dashboard     = new Dashboard();
        $id_user       = Dashboard::checkIfPreferenceExists(["users_id" => $this->users_id, "profiles_id" => $active_profile]);

        // Default grid of the profile when the user has none, or when it is being edited
        if ($id_user == 0 || $edit == 2) {
            $id = Dashboard::checkIfPreferenceExists(["users_id" => 0, "profiles_id" => $active_profile]);
            if ($dashboard->getFromDB($id)) {
                $grid = stripslashes($dashboard->fields['grid']);
            }
        }
        if ($edit != 2 && $dashboard->getFromDB($id_user)) {
            $grid = stripslashes($dashboard->fields['grid']);
        }
        if ($predefined_grid > 0) {
            $grid = Dashboard::loadPredefinedDashboard($predefined_grid);
        }

        // The `grid` column is persisted from client input by ajax/saveGrid.php: keep only
        // the geometry keys the grid reads, cast, so nothing else reaches the page.
        $grid_nodes = json_decode((string) $grid, true);
        $grid_safe  = [];
        if (is_array($grid_nodes)) {
            foreach ($grid_nodes as $node) {
                if (!is_array($node) || !isset($node['id'])) {
                    continue;
                }
                $grid_safe[] = [
                    'id' => (string) $node['id'],
                    'x'  => (int) ($node['x'] ?? 0),
                    'y'  => (int) ($node['y'] ?? 0),
                    'w'  => (int) ($node['w'] ?? 0),
                    'h'  => (int) ($node['h'] ?? 0),
                ];
            }
        }

        // gsid => id of the element the widget HTML replaces
        $widget_dom_ids = [];
        if (count($grid_safe) > 0) {
            $widgets = Widget::getCachedWidgetList();
            foreach ($grid_safe as $node) {
                if (isset($widgets[$node['id']])) {
                    $widget_dom_ids[$node['id']] = Widget::removeBackslashes($widgets[$node['id']]['id']);
                }
            }
        }
        $used_widgets = array_keys($widget_dom_ids);

        $filter_bar = $this->getGlobalFilterBarData();
        $theme      = MydashboardPreference::getPalette($this->users_id);
        $menu_url   = PLUGIN_MYDASHBOARD_WEBDIR . '/front/menu.php';
        $ajax_url   = PLUGIN_MYDASHBOARD_WEBDIR . '/ajax/';

        $grid_config = [
            'grid'          => $grid_safe,
            'widgets'       => (object) $widget_dom_ids,
            'dragMode'      => $drag > 0,
            'editMode'      => (int) $edit,
            'activeProfile' => $active_profile,
            'urls'          => [
                'refreshWidget' => $ajax_url . 'refreshWidget.php',
                'saveGrid'      => $ajax_url . 'saveGrid.php',
                'clearGrid'     => $ajax_url . 'clearGrid.php',
                'editGrid'      => $ajax_url . 'editGrid.php',
                'dragGrid'      => $ajax_url . 'dragGrid.php',
                'menu'          => $menu_url,
            ],
            'labels'        => [
                'refresh'       => __('Refresh widget', 'mydashboard'),
                'delete'        => __('Delete widget', 'mydashboard'),
                'error'         => __('No data available', 'mydashboard'),
                'pdfGenerating' => __('Generating PDF...', 'mydashboard'),
                'pdfTitle'      => __('My Dashboard', 'mydashboard'),
                'pdfError'      => __('PDF export failed. Please try again.', 'mydashboard'),
            ],
            'usedWidgets'   => $used_widgets,
            'globalFilters' => $filter_bar['initial'],
            'autoRefreshMs' => self::$_PLUGIN_MYDASHBOARD_CFG['automatic_refresh']
                ? 60000 * (int) self::$_PLUGIN_MYDASHBOARD_CFG['automatic_refresh_delay']
                : 0,
        ];

        TemplateRenderer::getInstance()->display('@mydashboard/menu_grid.html.twig', [
            'rand'           => mt_rand(),
            'plugin_version' => PLUGIN_MYDASHBOARD_VERSION,
            // setup.php already registers them in the central interface
            'load_search_libs' => Session::getCurrentInterface() !== 'central',
            'theme'          => $theme,
            'edit_mode'      => (int) $edit,
            'offcanvas'      => $edit > 0 ? $this->getWidgetsOffcanvasData($active_profile, $used_widgets) : null,
            'edit_toolbar'   => $edit > 0 ? $this->getEditToolbarData((int) $edit, $active_profile, (int) $drag) : null,
            'view_actions'   => $edit > 0 ? [] : $this->getViewToolbarActions((int) $drag),
            'filters'        => $filter_bar['filters'],
            'show_warning'   => count($grid_safe) === 0,
            'grid_config'    => $grid_config,
        ]);
    }
}
