<?php
/*
 -------------------------------------------------------------------------
 MyDashboard plugin for GLPI
 Copyright (C) 2015 by the MyDashboard Development Team.
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
class PluginMydashboardMenu extends CommonGLPI {
   const DASHBOARD_NAME = "myDashboard";
   /**
    * Will contain an array indexed with classnames, each element of this array<br>
    * will be an array containing widgetId s
    * @var array of array of string
    */
   private $widgets    = [];
   public  $widgetlist = [];
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
   //Unused
   //private $infos = "";
   public static  $ALL_VIEW                = -1;
   public static  $TICKET_REQUESTERVIEW    = 1;
   public static  $PROBLEM_VIEW            = 2;
   public static  $CHANGE_VIEW             = 3;
   public static  $GROUP_VIEW              = 4;
   public static  $MY_VIEW                 = 5;
   public static  $GLOBAL_VIEW             = 6;
   public static  $RSS_VIEW                = 7;
   public static  $PROJECT_VIEW            = 8;
   public static  $TICKET_TECHVIEW         = 9;
   private static $DEFAULT_ID              = 0;
   public static  $_PLUGIN_MYDASHBOARD_CFG = [];

   static $rightname = "plugin_mydashboard";

   /**
    * @param int $nb
    *
    * @return translated
    */
   static function getTypeName($nb = 0) {
      return __('My Dashboard', 'mydashboard');
   }


   function defineTabs($options = []) {

      $ong = [];
      $this->addStandardTab(__CLASS__, $ong, $options);

      return $ong;
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

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

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

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
            case 1 :
               $self->loadDashboard($profile, $predefined_grid);
               break;
            case 2 :
               $self->loadDashboard($profile, 1);
               break;
            default :
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
   function __construct($show_all = false) {
      $this->initConfig($show_all);
   }

   /**
    * Initialize the mydashboard config
    *
    * @param $show_all
    */
   private function initConfig($show_all) {

      //Configuration set by Administrator (via Configuration->Plugins ...)
      $config = new PluginMydashboardConfig();
      $config->getConfig();

      self::$_PLUGIN_MYDASHBOARD_CFG['enable_fullscreen']     = $config->fields['enable_fullscreen']; // 0 (FALSE) or 1 (TRUE), enable the possibility to display the mydashboard in fullscreen
      self::$_PLUGIN_MYDASHBOARD_CFG['display_menu']          = $config->fields['display_menu']; // Display the right menu slider
      self::$_PLUGIN_MYDASHBOARD_CFG['display_plugin_widget'] = $config->fields['display_plugin_widget']; // Display widgets of plugins
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
   static function getMenuContent() {
      $plugin_page = "/plugins/mydashboard/front/menu.php";
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

      $menu['icon']    = self::getIcon();

      return $menu;
   }

   /**
    * @return string
    */
   static function getIcon() {
      return "fas fa-tachometer-alt";
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
   public function showMenu($rand, $users_id = -1, $active_profile = -1, $predefined_grid = 0) {

      //We check the wanted interface (this param is later transmitted to PluginMydashboardUserWidget to get the dashboard for the user in this interface)
      $this->interface = (Session::getCurrentInterface() == 'central') ? 1 : 0;

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
   static function dropdownProfiles($options = []) {
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
         ]);

      //New rule -> get the next free ranking
      while ($data = $iterator->next()) {
         $profiles[$data['id']] = $data['name'];
      }

      Dropdown::showFromArray($p['name'], $profiles,
                              ['value'               => $p['value'],
                               'rand'                => $p['rand'],
                               'display_emptychoice' => true,
                               'on_change'           => 'this.form.submit()']);
   }

   /**
    * This method shows the widget list (in the left part) AND the mydashboard
    *
    * @param int $selected_profile
    */
   private function showDashboard($rand, $selected_profile = -1, $predefined_grid = 0) {

      //If we want to display the widget list menu, we have to 'echo' it, else we also need to call it because it initialises $this->widgets (link between classnames and widgetId s)
      //      $_SESSION['plugin_mydashboard_editmode'] = false;
      $edit = PluginMydashboardPreference::checkEditMode(Session::getLoginUserID());
      if ($edit > 0) {
         echo $this->getWidgetsList($selected_profile, $edit);
      }

      //Now we have a widget list menu, but, it does nothing, we have to bind
      //list item click with the adding on the mydashboard, and we need to display
      //this div contains the header and the content (basically the ul used by sDashboard)

      echo "<div class='plugin_mydashboard_dashboard' >";//(div.plugin_mydashboard_dashboard)

      //This first div is the header of the mydashboard, basically it display a name, informations and a button to toggle full screen
      echo "<div class='plugin_mydashboard_header'>";//(div.plugin_mydashboard_header)

      $this->displayEditMode($rand, $edit, $selected_profile, $predefined_grid);

      //      echo "</span>";//end(span.plugin_mydashboard_header_title)
      //(span.plugin_mydashboard_header_right)
      //If administator enabled fullscreen we display the button to toggle fullscreen
      //(maybe we could also only add the js when needed, but jquery is loaded so would be only foolproof)

      //end(span.plugin_mydashboard_header_right)
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
            function automaticRefreshAll(delay) {
                 setInterval(function () {
                     mydashboard.refreshAll();
                 }, delay);
             }
            function refreshAll() {
                 $(\'.refresh-icon\').trigger(\'click\');
             };');

         echo Html::scriptBlock('
               automaticRefreshAll(' . $refreshIntervalMs . ');
         ');

      }
   }

   function displayEditMode($rand, $edit = 0, $selected_profile = -1, $predefined_grid = 0) {

      $drag = PluginMydashboardPreference::checkDragMode(Session::getLoginUserID());

      echo "<script>";
      echo "$(document).ready(function() {
              $('#see-menu$rand').click(function () {
                 var zone2 = document.getElementById('zone2');
                 var zone1 = document.getElementById('zone1');
                 if (zone2.style.display === \"none\") {
                   zone2.style.display = \"block\";
                   zone1.style.display = \"none\";
                 }
             }); 
             $('#hide-menu$rand').click(function () {
                 var zone2 = document.getElementById('zone2');
                 var zone1 = document.getElementById('zone1');
                 if (zone1.style.display === \"none\") {
                   zone1.style.display = \"block\";
                   zone2.style.display = \"none\";
                 }
             });
             $('#see-menu-edit$rand').click(function () {
                 var zone3 = document.getElementById('zone3');
                 var zone4 = document.getElementById('zone4');
                 if (zone3.style.display === \"none\") {
                   zone3.style.display = \"block\";
                   zone4.style.display = \"none\";
                 }
             }); 
           });";
      echo "</script>";

      if ($edit > 0) {

         echo "<div id='menutop' align='right'>";
         echo "<div id=\"zone3\" style=\"display: none;\">";
         echo "<form id=\"editmode\" class='plugin_mydashboard_header_title' method='post' 
                     action='" . $this->getSearchURL() . "' onsubmit='return true;'>";

         echo "<table class='cadre_edit' width='100%'>";
         echo "<tr><th>";
         echo __('Edit mode', 'mydashboard');
         if ($edit == 2) {
            echo " (" . __('Global', 'mydashboard') . ")";
         }
         echo "</th>";
         if (!Session::haveRight("plugin_mydashboard_config", CREATE) && $edit == 2) {
            $edit = 1;
         }
         echo "<td>";
         echo "<a id='load-widgets$rand' class='cadre_edit_button' href='#' title=\"" . __('Load widgets', 'mydashboard') . "\">";
         //         echo __('Load widgets', 'mydashboard');
         echo "&nbsp;<i class='fas fa-spinner fa-lg'></i>";
         echo "<span class='sr-only'>" . __('Load widgets', 'mydashboard') . "</span>";
         echo "</a>";
         echo "</td>";

         if (Session::haveRight("plugin_mydashboard_config", CREATE) && $edit == 2) {
            echo "<td>";
            echo "<a class='cadre_edit_button' href='#' style='padding: 3px;'>";
            echo "<span class='editmode_test'>" . __('Profile') . "</span>&nbsp;";
            self::dropdownProfiles(['value' => $selected_profile]);
            echo "</a>";
            echo "</td>";
         } else {
            echo Html::hidden("profiles_id", ['value' => $_SESSION['glpiactiveprofile']['id']]);
         }

         echo "<td>";

         echo "<a id='add-widget' class='cadre_edit_button' href='#'>";
         echo "<span class='plugin_mydashboard_add_button'>" . __('Add a widget', 'mydashboard');
         echo "&nbsp;<i class=\"fas fa-caret-down\"></i>";
         echo "</span>";//(span.plugin_mydashboard_header_title)
         echo "</a>";
         echo "</td>";

         echo "<td>";
         //         echo "<i class='fas fa-tasks fa-lg'></i>";
         echo "<a class='cadre_edit_button' href='#' style='padding: 3px;'>";
         echo "<span class='editmode_test'>" . __('Load a predefined grid', 'mydashboard') . "</span>&nbsp;";
         echo "<span class='sr-only'>" . __('Load a predefined grid', 'mydashboard') . "</span>";
         //         echo "<br><br>";
         $elements = PluginMydashboardDashboard::getPredefinedDashboardName();
         Dropdown::showFromArray("predefined_grid", $elements, [
            'value'               => $predefined_grid,
            'width'               => '170px',
            'display_emptychoice' => true,
            'on_change'           => 'this.form.submit()']);
         echo "</a>";
         echo "</td>";

         if ($edit == 1) {
            echo "<td>";
            echo "<a id='save-grid$rand' class='cadre_edit_button' href='#' title=\"" . __('Save grid', 'mydashboard') . "\">";
            //            echo __('Save grid', 'mydashboard');
            echo "<span class='sr-only'>" . __('Save grid', 'mydashboard') . "</span>";
            echo "&nbsp;<i class='far fa-save fa-lg'></i>";
            echo "</a>";
            echo "</td>";
         }
         if (Session::haveRight("plugin_mydashboard_config", CREATE) && $edit == 2) {
            echo "<td>";
            echo "<a id='save-default-grid$rand' class='cadre_edit_button' href='#' title=\"" . __('Save default grid', 'mydashboard') . "\">";
            //            echo __('Save default grid', 'mydashboard');
            echo "<span class='sr-only'>" . __('Save default grid', 'mydashboard') . "</span>";
            echo "&nbsp;<i class='far fa-hdd fa-lg'></i>";
            echo "</a>";
            echo "</td>";
         }

         echo "<td>";
         echo "<a id='clear-grid$rand' href='#' class='cadre_edit_button' title=\"" . __('Clear grid', 'mydashboard') . "\">";
         //         echo __('Clear grid', 'mydashboard');
         echo "<span class='sr-only'>" . __('Clear grid', 'mydashboard') . "</span>";
         echo "&nbsp;<i class='far fa-window-restore  fa-lg'></i>";
         echo "</a>";
         echo "</td>";

         echo "<td>";
         if ($drag < 1 && Session::haveRight("plugin_mydashboard_edit", 6)) {
            echo "<a id='drag-grid$rand' href='#' class='cadre_edit_button' title=\"" . __('Permit drag / resize widgets', 'mydashboard') . "\">";
            //            echo __('Permit drag / resize widgets', 'mydashboard');
            echo "<span class='sr-only'>" . __('Permit drag / resize widgets', 'mydashboard') . "</span>";
            echo "&nbsp;<i class='fas fa-lock fa-lg'></i>";
            echo "</a>";

         }
         if ($drag > 0 && Session::haveRight("plugin_mydashboard_edit", 6)) {
            echo "<a id='undrag-grid$rand' href='#' class='cadre_edit_button' title=\"" . __('Block drag / resize widgets', 'mydashboard') . "\">";
            //            echo __('Block drag / resize widgets', 'mydashboard');
            echo "<span class='sr-only'>" . __('Block drag / resize widgets', 'mydashboard') . "</span>";
            echo "&nbsp;<i class='fas fa-unlock fa-lg'></i>";
         }
         echo "</a>";
         echo "</td>";

         echo "<td>";
         echo "<a id='close-edit$rand' href='#' class='cadre_edit_button' title=\"" . __('Close edit mode', 'mydashboard') . "\">";
         //         echo "<span class='red'>".__('Close edit mode', 'mydashboard') . "</span>";
         echo "<span class='sr-only'>" . __('Close edit mode', 'mydashboard') . "</span>";
         echo "&nbsp;<i class='far fa-times-circle fa-lg' style='color: red;'></i>";
         echo "</a>";
         echo "</td>";

         echo "</tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";

         echo "<div id=\"zone4\">";
         echo "<div id=\"btn_zone4\" class=\"btn_open_zone\">";
         echo "<table class='cadre_edit'>";
         echo "<tr>";
         echo "<td>";
         echo "<a id='see-menu-edit$rand' class='cadre_edit_button' href='#' title=\"" . __('See menu', 'mydashboard') . "\">";
         echo "<i class='fas fa-ellipsis-v fa-2x'></i>";
         echo "<span class='sr-only'>" . __('See menu', 'mydashboard') . "</span>";
         echo "</a>";
         echo "</td>";
         echo "</tr>";
         echo "</table>";
         echo "</div>";
         echo "</div>";
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

         echo "<div id='menutop' align='right'>";

         echo "<div id=\"zone2\" style=\"display: none;\">";
         echo "<div id=\"btn_zone2\" class=\"btn_open_zone\">";
         echo "<table class='cadre_edit'>";
         echo "<tr>";

         if (Session::haveRight("plugin_mydashboard_edit", 6)) {
            echo "<td>";
            echo "<a class='cadre_edit_button' id='edit-grid$rand' href='#' title=\"" . __('Switch to edit mode', 'mydashboard') . "\">";
            echo "<i class='fas fa-edit fa-lg'></i>";
            echo "<span class='sr-only'>" . __('Switch to edit mode', 'mydashboard') . "</span>";
            echo "</a>";
            echo "</td>";
         }

         if ($drag < 1 && Session::haveRight("plugin_mydashboard_edit", 6)) {
            echo "<td>";
            echo "<a class='cadre_edit_button' id='drag-grid$rand' href='#' title=\"" . __('Permit drag / resize widgets', 'mydashboard') . "\">";
            echo "<i class='fas fa-lock fa-lg'></i>";
            echo "<span class='sr-only'>" . __('Permit drag / resize widgets', 'mydashboard') . "</span>";
            echo "</a>";
            echo "</td>";
         }
         if ($drag > 0 && Session::haveRight("plugin_mydashboard_edit", 6)) {
            echo "<td>";
            echo "<a class='cadre_edit_button' id='undrag-grid$rand' href='#' title=\"" . __('Block drag / resize widgets', 'mydashboard') . "\">";
            echo "<i class='fas fa-unlock-alt fa-lg'></i>";
            echo "<span class='sr-only'>" . __('Block drag / resize widgets', 'mydashboard') . "</span>";
            echo "</a>";
            echo "</td>";

            echo "<td>";
            echo "<a class='cadre_edit_button' id='save-grid$rand' href='#' title=\"" . __('Save positions', 'mydashboard') . "\">";
            echo "<i class='fas fa-save fa-lg'></i>";
            echo "<span class='sr-only'>" . __('Save positions', 'mydashboard') . "</span>";
            echo "</a>";
            echo "</td>";
         }
         if (Session::haveRight("plugin_mydashboard_config", CREATE)) {
            echo "<td>";
            echo "<a class='cadre_edit_button' id='edit-default-grid$rand' href='#' title=\"" . __('Custom and save default grid', 'mydashboard') . "\">";
            echo "<i class='fas fa-cogs fa-lg'></i>";
            echo "<span class='sr-only'>" . __('Custom and save default grid', 'mydashboard') . "</span>";
            echo "</a>";
            echo "</td>";
         }

         if (self::$_PLUGIN_MYDASHBOARD_CFG['enable_fullscreen']
             && $edit < 1
             && $this->interface == 1) {
            echo "<td>";
            echo "<a class='cadre_edit_button' href='#' title=\"" . __("Fullscreen", "mydashboard") . "\">";
            echo "<i class=\"fas fa-arrows-alt fa-lg header_fullscreen\" alt='" . __("Fullscreen", "mydashboard") . "' title='" . __("Fullscreen", "mydashboard") . "'></i>";
            echo "</a>";
            echo "</td>";
         }

         echo "<td>";
         echo "<a id='hide-menu$rand' class='cadre_edit_button' href='#' title=\"" . __('Hide menu', 'mydashboard') . "\">";
         echo "<i class='fas fa-ellipsis-v fa-2x'></i>";
         echo "<span class='sr-only'>" . __('Hide menu', 'mydashboard') . "</span>";
         echo "</a>";
         echo "</td>";

         echo "</tr>";
         echo "</table>";

         echo "</div>";
         echo "</div>";

         echo "<div id=\"zone1\">";
         echo "<div id=\"btn_zone1\" class=\"btn_open_zone\">";
         echo "<table class='cadre_edit'>";
         echo "<tr>";
         echo "<td>";
         echo "<a id='see-menu$rand' class='cadre_edit_button' href='#' title=\"" . __('See menu', 'mydashboard') . "\">";
         echo "<i class='fas fa-ellipsis-v fa-2x'></i>";
         echo "<span class='sr-only'>" . __('See menu', 'mydashboard') . "</span>";
         echo "</a>";
         echo "</td>";
         echo "</tr>";
         echo "</table>";
         echo "</div>";
         echo "</div>";

         echo "</div>";
      }
      echo "<div id='ajax_loader' class=\"ajax_loader hidden\">";
      echo "</div>";
      //      }
   }

   /**
    * Get the HTML view of the widget list, the lateral menu
    *
    * @param     $profile
    * @param int $edit
    *
    * @return string, HTML
    */
   private function getWidgetsList($profile, $edit = 0) {

      $list             = new PluginMydashboardWidgetlist();
      $this->widgetlist = $list->getList(true, $profile);

      $grid = [];
      $used = [];

      $dashboard = new PluginMydashboardDashboard();

      if ($edit == 2) {
         $options = ["users_id" => 0, "profiles_id" => $profile];
         $id      = PluginMydashboardDashboard::checkIfPreferenceExists($options);
         if ($dashboard->getFromDB($id)) {
            $grid = stripslashes($dashboard->fields['grid']);
         }
      }
      if ($edit == 1) {
         $option_users = ["users_id" => Session::getLoginUserID(), "profiles_id" => $profile];
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
      $layout = $_SESSION['glpilayout'];
      $wl     = "<script>

            $(document).ready(function () {
                var layout = '$layout';
                //===================Start:Showing Menu=====================================
                //Showing the menu on click
                $('.plugin_mydashboard_add_button').on('click', function (e) {
                    //For tabs
                    if (layout == 'vsplit') {
                        $('.plugin_mydashboard_menuDashboard').css('top', $(this).offset().top - 100);
                        $('.plugin_mydashboard_menuDashboard').css('left', $(this).offset().left + 20);
                    } else {
                        $('.plugin_mydashboard_menuDashboard').css('top', $(this).offset().top + 25);
                        $('.plugin_mydashboard_menuDashboard').css('left', $(this).offset().left + 45);
                    }
                    $('.plugin_mydashboard_menuDashboard').width(400);
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
                      $('.plugin_mydashboard_menuDashboard').hide();
                  } else {
                      menu = false
                  }
                });
            
                //===================Stop:Showing Menu=====================================
                //===================Start:AccordionEffect=================================
                //Now the accordion effect w/o jQuery Accordion (wasn't really customizable, and css from other plugin can override dashboard one)
                //at the beginning every lists of widgets are folded
                $('.plugin_mydashboard_menuDashboardListContainer,.plugin_mydashboard_menuDashboardList2').slideUp('fast');
            
                //binding when user wants to unfold/fold a list of widget
                $('.plugin_mydashboard_menuDashboardListTitle1').click(function () {
                    var isOpened = $(this).hasClass('plugin_mydashboard_menuDashboardListTitle1Opened');
                    $('.plugin_mydashboard_menuDashboardListTitle1').removeClass(\"plugin_mydashboard_menuDashboardListTitle1Opened\");
                  if (!isOpened) {
                     $(this).addClass(\"plugin_mydashboard_menuDashboardListTitle1Opened\");
                  }
                    $('.plugin_mydashboard_menuDashboardListTitle1').not(this).next(\"div\").slideUp('fast');
                    plugin_mydashboard_slideToggle_title1(this);
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
                    $('.plugin_mydashboard_menuDashboardListTitle2').not(this).next(\"ul\").slideUp('fast');
                    $(this).next(\"ul\").slideToggle('fast');
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
                      $(\"#success-alert\").fadeTo(2000, 500).slideUp(500, function () {
                          $(\"#success-alert\").slideUp(500);
                      });
                      $('.plugin_mydashboard_menuDashboard').hide();
                  } else {
                      //error
                      $(\"#error-alert\").fadeTo(2000, 500).slideUp(500, function () {
                          $(\"#error-alert\").slideUp(500);
                      });
                  }
                });
                //===================Start:Fullscreen mode=================================
                //handling click on the 'fullscreen' button
                $('.plugin_mydashboard_header_fullscreen').click(
                     function () {
                        $('#plugin_mydashboard_container').toggleFullScreen();
                        var overlay = $('.sDashboard-overlay');
                        $('#plugin_mydashboard_container').append(overlay);
                        $('#plugin_mydashboard_container').toggleClass('plugin_mydashboard_fullscreen_view');
                     });
                //===================Stop:Fullscreen mode=================================
            });
             var plugin_mydashboard_slideToggle_title1 = function (element) {
                $(element).next(\"div\").slideToggle('fast');
            };
        </script>";
      //menuMyDashboard is the non moving part (it's just it width that changes)
      $wl .= "<div class='plugin_mydashboard_menuDashboard' "
             . " data-dashboardid='" . self::DASHBOARD_NAME . "'"
             . ">";//(div.plugin_mydashboard_menuDashboard)
      //      menuSlider is the moving part (jQuery changing the css property margin-right)
      $wl .= "<div class='plugin_mydashboard_menuSlider' style='float:right;' >";  //(div.plugin_mydashboard_menuSlider)
      //        $wl .= "<div class='plugin_mydashboard_menuSliderHeader'>".$this->getTypeName()."</div>";
      //menuSliderContent contains the lists of widgets
      $wl .= "<div class='plugin_mydashboard_menuSliderContent'>"; //(div.plugin_mydashboard_menuSliderContent)

      $empty       = false;
      $widgetslist = PluginMydashboardWidget::getWidgetList();
      $gslist = [];
      foreach ($widgetslist as $gs => $widgetclasses) {
         $gslist[$widgetclasses['id']] = $gs;
      }

      //1) we 'display' GLPI core widgets in the list
      if ($this->getWidgetsListFromGLPICore($used, $wl, $gslist)) {
         $empty = true;
      }
      //2) we 'display' Plugin widgets
      if (self::$_PLUGIN_MYDASHBOARD_CFG['display_plugin_widget']) {
         if ($this->getWidgetsListFromPlugins($used, $wl, $gslist)) {
            $empty = ($empty) ? $empty : false;
         } else {
            $empty = false;
         }
      }

      if ($empty) {
         $wl .= __('No data available', 'mydashboard');
      }
      //-------------------------------------------------------
      $wl .= "</div>"; //end(div.plugin_mydashboard_menuSliderContent)

      $wl .= "</div>"; //end(div.plugin_mydashboard_menuSlider)
      $wl .= "</div>"; //end(div.plugin_mydashboard_menuDashboard)

      return $wl;
   }

   /**
    * Initialization of widgets at installation
    */
   static function installWidgets() {

      $list       = new PluginMydashboardWidgetlist();
      $widgetlist = $list->getList(false);

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
   private function initDBWidgets() {
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
    * Get the HTML list of the GLPI core widgets available
    *
    * @param array  $used
    * @param string $html the HTML list
    *
    * @return bool|string is empty ?
    */
   public function getWidgetsListFromGLPICore($used = [], &$html = "", $gslist = []) {
      $wl = "<div class='plugin_mydashboard_menuDashboardListOfPlugin'>";
      $wl .= "<h3 class='plugin_mydashboard_menuDashboardListTitle1'>GLPI</h3>";
      $wl .= "<div class='plugin_mydashboard_menuDashboardListContainer'><ul class=''>";

      //GLPI core classes doesn't display the same thing in each view, we need to provide all views available
      $views = [self::$TICKET_REQUESTERVIEW,
                self::$TICKET_TECHVIEW,
                self::$PROBLEM_VIEW,
                self::$CHANGE_VIEW,
                self::$PROJECT_VIEW,
                self::$GROUP_VIEW,
                self::$MY_VIEW,
                self::$GLOBAL_VIEW,
                self::$RSS_VIEW];
      //To ease navigation we display the name of the view
      $viewsNames = $this->getViewNames();

      $viewContent = [];
      foreach ($views as $view) {
         $viewContent[$view] = "";
      }

      if (!isset($this->widgetlist['GLPI'])) {
         return '';
      }
      $widgetclasses = $this->widgetlist['GLPI'];

      foreach ($widgetclasses as $widgetclass => $widgets) {
         foreach ($widgets as $widgetview => $widgetlist) {
            foreach ($widgetlist as $widgetId => $widgetTitle) {
               if (is_numeric($widgetId)) {
                  $widgetId = $widgetTitle;
               }
               $this->widgets[$widgetclass][$widgetId] = $viewsNames[$widgetview];
               $gsid                                   = $gslist[$widgetId];
               if (!in_array($gsid, $used)) {
                  $viewContent[$widgetview] .= "<li "/*."id='btnAddWidgete".$widgetId."'"*/
                                               . " class='plugin_mydashboard_menuDashboardListItem'"
                                               . " data-widgetid='" . $gsid . "'"
                                               . " data-classname='" . $widgetclass . "'"
                                               . " data-view='" . $viewsNames[$widgetview] . "'>";
                  $viewContent[$widgetview] .= $widgetTitle;
                  if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
                     $viewContent[$widgetview] .= " (" . $gsid . ")";
                  }
                  $viewContent[$widgetview] .= "</li>\n";
               }
            }
         }
      }
      $is_empty = true;
      //Now we display each group (view) as a list
      foreach ($viewContent as $view => $vContent) {
         if ($vContent != '') {
            $wl .= "<li class='plugin_mydashboard_menuDashboardList'>";
            $wl .= "<h6 class='plugin_mydashboard_menuDashboardListTitle2'>" . $viewsNames[$view] . "</h6>";
            $wl .= "<ul class='plugin_mydashboard_menuDashboardList2'>";

            $wl       .= $vContent;
            $wl       .= "</ul></li>";
            $is_empty = false;
         }
      }

      $wl .= "</ul></div>";
      $wl .= "</div>";
      if ($is_empty) {
         return true;
      } else {
         $html .= $wl;
         return false;
      }
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
   public function getWidgetsListFromPlugins($used = [], &$html = "", $gslist = []) {
      $plugin_names                = $this->getPluginsNames();
      $plugin_names["mydashboard"] = __('My Dashboard', 'mydashboard');
      $plugins_is_empty            = true;
      foreach ($this->widgetlist as $plugin => $widgetclasses) {
         if ($plugin == "GLPI") {
            continue;
         }
         $is_empty = true;
         $tmp      = "<div class='plugin_mydashboard_menuDashboardListOfPlugin'>";
         //
         $tmp .= "<h6 class='plugin_mydashboard_menuDashboardListTitle1'>" . ucfirst($plugin_names[$plugin]) . "</h6>";
         //Every widgets of a plugin are in an accordion (handled by dashboard not the jquery one)
         $tmp .= "<div class='plugin_mydashboard_menuDashboardListContainer'>";
         $tmp .= "<ul>";
         foreach ($widgetclasses as $widgetclass => $widgetlist) {
            $res = $this->getWidgetsListFromWidgetsArray($widgetlist, $widgetclass, 2, $used, $gslist);
            if (!empty($widgetlist) && $res != '') {
               $tmp      .= $res;
               $is_empty = false;
            }
         }
         $tmp .= "</ul>";
         $tmp .= "</div>";
         $tmp .= "</div>";
         //If there is now widgets available from this plugins we don't display menu entry
         if (!$is_empty) {
            $html .= $tmp;
            if ($plugins_is_empty) {
               $plugins_is_empty = false;
            }
         }
      }
      return $plugins_is_empty;
   }


   /**
    * Get all listitems (<li> tags) for an array of widgets ($widgetsarray)
    * In case items of the array ($widgetsarray) is an array of widgets it's recursive
    * It can result as :
    * <li></li>
    * <li><ul>
    *   <li></li>
    *  </ul></li>
    * The class of each li, ul or h3 (title/category), is linked to the javascript for accordion purpose
    * Accordion is only available for level 2, (level 3 and more won't be folded (by default))
    * ATTENTION : it doesn't handle level 1 items (Plugin names, GLPI ...)
    *
    * @param type  $widgetsarray , an arry of widgets (or array of array ... of widgets)
    * @param type  $classname , name of the class containing the widget
    * @param int   $depth
    *
    * @param array $used
    *
    * @return string
    */
   private function getWidgetsListFromWidgetsArray($widgetsarray, $classname, $depth = 2, $used = [], $gslist = []) {
      $wl = "";
      if (is_array($widgetsarray) && count($widgetsarray) > 0) {
         foreach ($widgetsarray as $widgetId => $widgetTitle) {
            //We check if this widget is a real widget
            if (!is_array($widgetTitle)) {
               //If no 'title' is specified it won't be 'widgetid' => 'widget Title' but 'widgetid' so
               if (is_numeric($widgetId)) {
                  $widgetId = $widgetTitle;
               }
               $this->widgets[$classname][$widgetId] = -1;
               if (isset($gslist[$widgetId])) {
                  $gsid = $gslist[$widgetId];
                  if (!in_array($gsid, $used)) {
                     $wl .= "<li id='btnAddWidgete" . $widgetId . "'"
                            . " class='plugin_mydashboard_menuDashboardListItem' "
                            . " data-widgetid='" . $gsid . "'"
                            . " data-classname='" . $classname . "'>";
                     $wl .= $widgetTitle;
                     if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
                        $wl .= " (" . $gsid . ")";
                     }/*->getWidgetListTitle()*/
                     $wl .= "</li>";
                  }
               }
            } else { //If it's not a real widget
               //It may/must be an array of widget, in this case we need to go deeper (increase $depth)
               $tmp = "<li class='plugin_mydashboard_menuDashboardList'>";
               $tmp .= "<h6 class='plugin_mydashboard_menuDashboardListTitle$depth'>" . $widgetId . "</h6>";
               $tmp .= "<ul class='plugin_mydashboard_menuDashboardList$depth'>";
               $res = $this->getWidgetsListFromWidgetsArray($widgetTitle, $classname, $depth + 1, $used, $gslist);
               if ($res != '') {
                  $tmp .= $res;
               }
               $tmp .= "</ul></li>";
               if ($res != '') {
                  $wl .= $tmp;
               }
            }
         }
      }
      return $wl;
   }

   /**
    * Get an array of widgetNames as ["id1","id2"] for a specifid users_id
    *
    * @param int $id user id
    *
    * @return array of string
    */
   private function getDashboardForUser($id) {
      $this->interface = (Session::getCurrentInterface() == 'central') ? 1 : 0;
      $user_widget     = new PluginMydashboardUserWidget($id, $this->interface);
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
   private function getPluginsNames() {
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
   private function getLocalName($plugin_name) {
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
   public function getJsLanguages($libraryname) {

      $languages = [];
      switch ($libraryname) {
         case "datatables" :
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
         case "mydashboard" :
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
    * Get the names of each view
    * @return array of string
    */
   public function getViewNames() {

      $names = [];

      $names[self::$TICKET_REQUESTERVIEW] = _n('Ticket', 'Tickets', 2) . " (" . __("Requester") . ")";
      $names[self::$TICKET_TECHVIEW]      = _n('Ticket', 'Tickets', 2) . " (" . __("Technician") . ")";
      $names[self::$PROBLEM_VIEW]         = _n('Problem', 'Problems', 2);
      $names[self::$CHANGE_VIEW]          = _n('Change', 'Changes', 2);
      $names[self::$GROUP_VIEW]           = __('Group View');
      $names[self::$MY_VIEW]              = __('Personal View');
      $names[self::$GLOBAL_VIEW]          = __('Global View');
      $names[self::$RSS_VIEW]             = _n('RSS feed', 'RSS feeds', 2);
      $names[self::$PROJECT_VIEW]         = _n('Project', 'Projects', 2);

      return $names;
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
   function loadDashboard($active_profile = -1, $predefined_grid = 0) {
      global $CFG_GLPI, $GLPI_CACHE;

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
      $datagrid = [];
      $datajson = [];
      $optjson  = [];
      $widgets  = [];
      if (!empty($grid) && ($datagrid = json_decode($grid, true)) == !null) {

            $widgetclasse = new PluginMydashboardWidget();
//            $ckey         = 'md_cache_' . md5($widgetclasse->getTable()).Session::getLoginUserID();
//            $datas     = $GLPI_CACHE->get($ckey);
            //UNACTIVATE IT FOR DEBUG
//            if (is_array($datas) && count($datas) > 0 && $predefined_grid == 0) {
//               $datajson = $datas;
//            } else {
               $widgets = PluginMydashboardWidget::getWidgetList();

               foreach ($datagrid as $k => $v) {
                  if (isset($v["id"])) {
                     $datajson[$v["id"]] = PluginMydashboardWidget::getWidget($v["id"], $widgets, []);

                     if (isset($_SESSION["glpi_plugin_mydashboard_widgets"])) {
                        foreach ($_SESSION["glpi_plugin_mydashboard_widgets"] as $w => $r) {
                           if (isset($widgets[$v["id"]]["id"])
                               && $widgets[$v["id"]]["id"] == $w) {
                              $optjson[$v["id"]]["enableRefresh"] = $r;
                           }
                        }
                     }
                  }
               }
//               if ($predefined_grid == 0) {
//                  $GLPI_CACHE->set($ckey, $datajson);
//               }
//            }
      } else {
         echo "<div class='bt-alert bt-alert-warning' id='warning-alert'>
                <strong>" . __('Warning', 'mydashboard') . "!</strong>
                " . __('No widgets founded, please add widgets', 'mydashboard') . "
            </div>";
         echo Html::scriptBlock('$("#warning-alert").fadeTo(2000, 500).slideUp(500, function(){
            $("#success-alert").slideUp(500);
         });');

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

      //      if ($this->interface == 1) {
      if ($drag > 0) {
         $disableResize = 'false';
         $disableDrag   = 'false';
      }
      if ($edit > 0) {
         $delete_button = 'true';
      }
      //      }

      echo "<div id='mygrid$rand' class='mygrid'>";
      echo "<div class='grid-stack$rand grid-stack md-grid-stack'>";
      echo "</div>";

      echo "<script type='text/javascript'>
        $(function () {
            var options = {
                cellHeight: 41,
                verticalMargin: 2,
                 disableResize: $disableResize,
                 disableDrag: $disableDrag,
                 resizable: {
                    handles: 'e, se, s, sw, w'
                }
            };
            $('.grid-stack$rand').gridstack(options);  
            new function () {
                this.serializedData = $grid;
                this.grid = $('.grid-stack$rand').data('gridstack');
                this.loadGrid = function () {
                    this.grid.removeAll();
                    var items = GridStackUI.Utils.sort(this.serializedData);
//                    _.each(items, function (node) {
                     items.forEach(function(node)  {
                         var nodeid = node.id;
                         var optArray = $optjson;
                         var widgetArray = $datajson; 
                         var widget = widgetArray['' + nodeid + ''];
                         if ( widget !== undefined ) {
                            widget = widgetArray['' + nodeid + ''];
                         } else {
                             widget = '$msg_error';
                         }
                         var opt = optArray['' + nodeid + ''];
                         if ( opt !== undefined ) {
                            options = optArray['' + nodeid + ''];
                            if ( options != null ) {
                               refreshopt = optArray['' + nodeid + '']['enableRefresh'];
                            } else {
                                refreshopt = false;
                            }
                         } else {
                             refreshopt = false;
                         }
                         var delbutton = '';
                         var refreshbutton = '';
                         if ($delete_button == 1) {
                            var delbutton = '<button title=\"$msg_delete\" class=\"md-button pull-left\" onclick=\"deleteWidget(\'' + node.id + '\');\"><i class=\"fas fa-times\"></i></button>';
                         }
                         if (refreshopt == 1) {
                            var refreshbutton = '<button title=\"$msg_refresh\" class=\"md-button refresh-icon pull-right\" onclick=\"refreshWidget(\'' + node.id + '\');\"><i class=\"fas fa-sync-alt\"></i></button>';
                         } else {
                            var refreshbutton = '<button title=\"$msg_refresh\" class=\"md-button refresh-icon-disabled pull-right\"><i class=\"fas fa-sync-alt\"></i></button>';
                         }
                         if ( nodeid !== undefined ) {
                         var el = $('<div><div class=\"grid-stack-item-content md-grid-stack-item-content\">' + refreshbutton + delbutton + widget + '<div/><div/>');
                            this.grid.addWidget(el, node.x, node.y, node.width, node.height, true, null, null, null, null, node.id);
                            }
                    }, this);
                    return false;
                }.bind(this);
                this.saveGrid = function () {
                    this.serializedData = _.map($('.grid-stack$rand > .grid-stack-item:visible'), function (el) {
                        el = $(el);
                        var node = el.data('_gridstack_node');
                        if ( node.id !== undefined ) {
                           return {
                                id: node.id,
                               x: node.x,
                               y: node.y,
                               width: node.width,
                               height: node.height
                           };
                        }
                    }, this);
                    var sData = JSON.stringify(this.serializedData);
                    var profiles_id = -1;
                    $('#ajax_loader').show();
                     $.ajax({
                       url: '" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/ajax/saveGrid.php',
                       type: 'POST',
                       data:{data:sData,profiles_id:$active_profile},
                       success:function(data) {
                              $('#ajax_loader').hide();
                              window.location.href = '" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/front/menu.php';
                           }
                       });
                    return false;
                }.bind(this);
                this.saveDefaultGrid = function () {
                    this.serializedData = _.map($('.grid-stack$rand > .grid-stack-item:visible'), function (el) {
                        el = $(el);
                        var node = el.data('_gridstack_node');
                        return {
                             id: node.id,
                            x: node.x,
                            y: node.y,
                            width: node.width,
                            height: node.height
                        };
                    }, this);
                    var sData = JSON.stringify(this.serializedData);
                    var users_id = 0;
                    var profiles_id = -1;
                    $('#ajax_loader').show();
                     $.ajax({
                          url: '" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/ajax/saveGrid.php',
                          type: 'POST',
                          data:{data:sData,users_id:users_id,profiles_id:$active_profile},
                          success:function(data) {
                             $('#ajax_loader').hide();
                             var redirectUrl = '" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/front/menu.php';
                             var form = $('<form action=\"' + redirectUrl + '\" method=\"post\">' +
                             '<input type=\"hidden\" name=\"profiles_id\" value=\"$active_profile\"></input>' +
                             '<input type=\"hidden\" name=\"_glpi_csrf_token\" value=\"' + data +'\"></input>'+ 
                            '</form>');
                             $('body').append(form);
                             $(form).submit();
                          }
                       });
                    return false;
                }.bind(this);
                this.clearGrid = function () {
                  $('#ajax_loader').show();
                  $.ajax({
                    url: '" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/ajax/clearGrid.php',
                       type: 'POST',
                       success:function(data) {
                              $('#ajax_loader').hide();
                              window.location.href = '" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/front/menu.php';
                           }
                       });
                    return false;
                }.bind(this);
                this.dragGrid = function () {
                  $('#ajax_loader').show();
                  $.ajax({
                    url: '" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/ajax/dragGrid.php',
                       type: 'POST',
                       data:{drag_mode:1},
                       success:function(data) {
                              $('#ajax_loader').hide();
                              window.location.href = '" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/front/menu.php';
                          }
                       });
                    return false;
                }.bind(this);
                this.undragGrid = function () {
                  $('#ajax_loader').show();
                  $.ajax({
                    url: '" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/ajax/dragGrid.php',
                       type: 'POST',
                       data:{drag_mode:0},
                       success:function(data) {
                              $('#ajax_loader').hide();
                              window.location.href = '" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/front/menu.php';
                          }
                       });
                    return false;
                }.bind(this);
                this.editGrid = function () {
                  $('#ajax_loader').show();
                  $.ajax({
                    url: '" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/ajax/editGrid.php',
                       type: 'POST',
                       data:{edit_mode:1},
                       success:function(data) {
                              $('#ajax_loader').hide();
                              window.location.href = '" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/front/menu.php';
                          }
                       });
                    return false;
                }.bind(this);
                this.editDefaultGrid = function () {
                  $('#ajax_loader').show();
                  $.ajax({
                    url: '" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/ajax/editGrid.php',
                       type: 'POST',
                       data:{edit_mode:2},
                       success:function(data) {
                              $('#ajax_loader').hide();
                              window.location.href = '" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/front/menu.php';
                          }
                       });
                    return false;
                }.bind(this);
                this.closeEdit = function () {
                  $('#ajax_loader').show();
                  $.ajax({
                    url: '" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/ajax/editGrid.php',
                       type: 'POST',
                       data:{edit_mode:0},
                       success:function(data) {
                              $('#ajax_loader').hide();
                              window.location.href = '" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/front/menu.php';
                          }
                       });
                    return false;
                }.bind(this);
                this.loadWidgets = function () {
                  var modal = $('<div>').dialog({ modal: true });
                  modal.dialog('widget').hide();
                  $('#ajax_loader').show();
                  $.ajax({
                    url: '" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/ajax/loadWidgets.php',
                       type: 'POST',
                       complete: function () {
                                //back to normal!
                                $('#ajax_loader').hide();
                                modal.dialog('close');
                                window.location.href = '" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/front/menu.php';
                            }
                       });
                    return false;
                }.bind(this);
                $('#save-grid$rand').click(this.saveGrid);
                $('#edit-grid$rand').click(this.editGrid);
                $('#drag-grid$rand').click(this.dragGrid);
                $('#undrag-grid$rand').click(this.undragGrid);
                $('#edit-default-grid$rand').click(this.editDefaultGrid);
                $('#close-edit$rand').click(this.closeEdit);
                $('#save-default-grid$rand').click(this.saveDefaultGrid);
                $('#remove-widget$rand').click(this.removewidget);
                $('#clear-grid$rand').click(this.clearGrid);
                $('#load-widgets$rand').click(this.loadWidgets);
                this.loadGrid();
            };
        });
        
     
    </script>";
      echo "<script type='text/javascript'>
        $('.header_fullscreen').click(
        function () {
           $('#mygrid$rand').toggleFullScreen();
           $('#mygrid$rand').toggleClass('fullscreen_view');
        });
        function addNewWidget(value) {
             var id = value;
             if (id != 0){
                var widgetArray = $allwidgetjson; 
                widget = widgetArray['' + id + ''];
                var el = $('<div><div class=\"grid-stack-item-content md-grid-stack-item-content\">' +
                         '<button class=\"md-button pull-left\" onclick=\"deleteWidget(\'' + id + '\');\">' +
                          '<i class=\"fas fa-times\"></i></button>' + widget + '<div/><div/>');
                var grid = $('.grid-stack$rand').data('gridstack');
                grid.addWidget(el, 0, 0, 4, 12, '', null, null, null, null, id);
                return true;
             }
             return false;
         };
        function refreshWidget (id) {
            var widgetOptionsObject = [];
            $.ajax({
              url: '" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/ajax/refreshWidget.php',
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
              url: '" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/ajax/refreshWidget.php',
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
         function deleteWidget (id) {
           this.grid = $('.grid-stack$rand').data('gridstack');
           widget = $('div[data-gs-id='+ id + ']');
//             if (confirm('$msg_delete') == true)
//             { 
                 this.grid.removeWidget(widget);
//             }
             return false;
           };
          
         function downloadGraph(id) {
//             if (!isChartRendered) return; // return if chart not rendered
                html2canvas(document.getElementById(id), {
                 onrendered: function(canvas) {
                     var link = document.createElement('a');
                    link.href = canvas.toDataURL('image/png');
                    
                    if (!HTMLCanvasElement.prototype.toBlob) {
                     Object.defineProperty(HTMLCanvasElement.prototype, 'toBlob', {
                       value: function (callback, type, quality) {
                         var canvas = this;
                         setTimeout(function() {
                           var binStr = atob( canvas.toDataURL(type, quality).split(',')[1] ),
                           len = binStr.length,
                           arr = new Uint8Array(len);
                  
                           for (var i = 0; i < len; i++ ) {
                              arr[i] = binStr.charCodeAt(i);
                           }
                  
                           callback( new Blob( [arr], {type: type || 'image/png'} ) );
                         });
                       }
                    });
                  }
                       
                  canvas.toBlob(function(blob){
                   link.href = URL.createObjectURL(blob);
                   saveAs(blob, 'myChart.png');
                 },'image/png');                      
              }
            })
         }
    </script>";

      echo "</div>";
   }
}

