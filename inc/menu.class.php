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
   private $widgets    = array();
   private $widgetlist = array();
   /**
    * Will contain an array of strings with js function needed to add a widget
    * @var array of string
    */
   private $addfunction = array();
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
   private $dashboard = array();
   /**
    * An array of string indexed by classnames, each string is a statistic (time /mem)
    * @var array of string
    */
   private $stats = array();
   /**
    * A string to store infos, those infos are displayed in the top right corner of the mydashboard
    * @var string
    */
   //Unused
   //private $infos = "";
   public static  $ALL_VIEW                = -1;
   public static  $CHANGE_VIEW             = 3;
   public static  $PROBLEM_VIEW            = 2;
   public static  $TICKET_VIEW             = 1;
   public static  $RSS_VIEW                = 7;
   public static  $GLOBAL_VIEW             = 6;
   public static  $GROUP_VIEW              = 4;
   public static  $MY_VIEW                 = 5;
   private static $DEFAULT_ID              = 0;
   private static $_PLUGIN_MYDASHBOARD_CFG = array();

   static $rightname = "plugin_mydashboard";

   // Should return the localized name of the type

   /**
    * @param int $nb
    *
    * @return translated
    */
   static function getTypeName($nb = 0) {
      return __('My Dashboard', 'mydashboard');
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
      //Since 1.0.3 replace_central is now a preference

      unset($config);

      //Configuration set by User (via My Preferences -> Dashboard tab)
      //General Settings
      $preference = new PluginMydashboardPreference();
      if (!$preference->getFromDB(Session::getLoginUserID())) $preference->initPreferences(Session::getLoginUserID());
      $preference->getFromDB(Session::getLoginUserID());

      self::$_PLUGIN_MYDASHBOARD_CFG['automatic_refresh']       = $preference->fields['automatic_refresh'];  //Wether or not refreshable widget will be automatically refreshed by automaticRefreshDelay minutes
      self::$_PLUGIN_MYDASHBOARD_CFG['automatic_refresh_delay'] = $preference->fields['automatic_refresh_delay']; //In minutes
      self::$_PLUGIN_MYDASHBOARD_CFG['replace_central']         = $preference->fields['replace_central']; // Replace central interface
      //Blacklist
      //Used when user doesn't want to display widgets of a plugin
      //        $ublacklist = new PluginMydashboardPreferenceUserBlacklist();
      //        if(!$show_all) self::$_PLUGIN_MYDASHBOARD_CFG['blacklist'] = $ublacklist->getBlacklistForUser(Session::getLoginUserID());
      //        else {
      //            self::$_PLUGIN_MYDASHBOARD_CFG['blacklist'] = array();
      //        }
   }

   /**
    * @return array
    */
   static function getMenuContent() {
      $plugin_page = "/plugins/mydashboard/front/menu.php";
      $menu        = array();
      //Menu entry in tools
      $menu['title']           = self::getTypeName();
      $menu['page']            = $plugin_page;
      $menu['links']['search'] = $plugin_page;
      if (Session::haveRightsOr("plugin_mydashboard", array(CREATE, UPDATE))
          || Session::haveRight("config", UPDATE)
      ) {
         //Entry icon in breadcrumb
         $menu['links']['config'] = PluginMydashboardConfig::getFormURL(false);
      }

      return $menu;
   }

   /**
    * Show dashboard
    *
    * @param int $users_id
    * @param int $interface
    *
    * @return FALSE if the user haven't the right to see Dashboard
    * @internal param type $user_id
    */
   public function showMenu($users_id = -1, $active_profile = -1) {

      Html::requireJs('mydashboard');
      //We check the wanted interface (this param is later transmitted to PluginMydashboardUserWidget to get the dashboard for the user in this interface)
      $this->interface = ($_SESSION['glpiactiveprofile']['interface'] == 'central') ? 1 : 0;

      // validation des droits
      if (!Session::haveRightsOr("plugin_mydashboard", array(CREATE, READ))) {
         return false;
      }
      // checking if no users_id is specified
      $this->users_id = Session::getLoginUserID();
      if ($users_id != -1) $this->users_id = $users_id;

      //Now the mydashboard
      $this->showDashboard($active_profile);

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

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $p[$key] = $val;
         }
      }

      $query = "SELECT `glpi_profiles`.`name`, `glpi_profiles`.`id`
                FROM `glpi_profiles` 
                LEFT JOIN `glpi_profilerights` ON (`glpi_profilerights`.`profiles_id` = `glpi_profiles`.`id`)" .
               Profile::getUnderActiveProfileRestrictRequest("WHERE") . "
                AND `glpi_profilerights`.`name` = 'plugin_mydashboard'
                AND `glpi_profilerights`.`rights` > 0
                ORDER BY `glpi_profiles`.`name`";

      $res = $DB->query($query);

      //New rule -> get the next free ranking
      if ($DB->numrows($res)) {
         while ($data = $DB->fetch_assoc($res)) {
            $profiles[$data['id']] = $data['name'];
         }
      }
      Dropdown::showFromArray($p['name'], $profiles,
                              ['value'               => $p['value'],
                               'rand'                => $p['rand'],
                               'display_emptychoice' => true,
                               'on_change'           => 'this.form.submit()']);
   }

   /**
    * This method shows the widget list (in the left part) AND the mydashboard
    */
   private function showDashboard($selected_profile = -1) {
      global $CFG_GLPI;

      //If we want to display the widget list menu, we have to 'echo' it, else we also need to call it because it initialises $this->widgets (link between classnames and widgetId s)
      //      $_SESSION['plugin_mydashboard_editmode'] = false;
      $edit = PluginMydashboardPreference::checkIfPreferenceExists(Session::getLoginUserID());
      if ($edit > 0) {
         echo $this->getWidgetsList($selected_profile, $edit);
      }
//      $this->interface = ($_SESSION['glpiactiveprofile']['interface'] == 'central') ? 1 : 0;
      //      else {
      //         $this->getWidgetsList();
      //      }
      //Now we have a widget list menu, but, it does nothing, we have to bind
      //list item click with the adding on the mydashboard, and we need to display
      //the widgets that needs to be
      //      $this->initWidgets();

      //this div contains the header and the content (basically the ul used by sDashboard)

      echo "<div class='plugin_mydashboard_dashboard' >";//(div.plugin_mydashboard_dashboard)

      //This first div is the header of the mydashboard, basically it display a name, informations and a button to toggle full screen
      echo "<div class='plugin_mydashboard_header'>";//(div.plugin_mydashboard_header)

      $this->displayEditMode($edit, $selected_profile);
      //      echo "</span>";//end(span.plugin_mydashboard_header_title)
      echo "<span class='plugin_mydashboard_header_right'> ";//(span.plugin_mydashboard_header_right)
      //If administator enabled fullscreen we display the button to toggle fullscreen
      //(maybe we could also only add the js when needed, but jquery is loaded so would be only foolproof)
      if (self::$_PLUGIN_MYDASHBOARD_CFG['enable_fullscreen']
          && $edit < 1
      && $this->interface == 1) {
         //         echo "<img class='plugin_mydashboard_header_fullscreen plugin_mydashboard_discret' src='" . $CFG_GLPI["root_doc"] . "/plugins/mydashboard/pics/fullscreen.png' width='20px' alt='" . __("Fullscreen") . "'/>";
         echo "<i class=\"fa fa-arrows-alt plugin_mydashboard_header_fullscreen header_fullscreen plugin_mydashboard_discret \" alt='" . __("Fullscreen", "mydashboard") . "' title='" . __("Fullscreen", "mydashboard") . "'></i>";
      }
      //In debug mod we display a client side log display
      //      if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
      //         echo " <img class='plugin_mydashboard_header_info_img plugin_mydashboard_header_info plugin_mydashboard_discret' src='" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/pics/info.png' alt='info' title='' />";
      //         echo " <span class='plugin_mydashboard_header_info_logbox' style='display:none'><b>" . __("Logs :") . "</b></span>";
      //      }
      //place where infos are temporarly displayed (for exemple 'dashboard saved')
      //      echo " <span class='plugin_mydashboard_header_info plugin_mydashboard_discret'></span>";

      echo "</span>";//end(span.plugin_mydashboard_header_right)
      echo "</div>";//end(div.plugin_mydashboard_header)
      //Now the content
      echo "<div class='plugin_mydashboard_content'>";//(div.plugin_mydashboard_content)
      //Then we need to initialize the javascript/jquery concerning sDashboard
      //      $this->initDashboard();
      //        echo "</td></tr>";
      echo "</div>";//end(div.plugin_mydashboard_content)
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
      //We display informations (Once everything is initialized)
      //Unused
      //echo "$('.plugin_mydashboard_header_info').html('".$this->infos."');";
   }

   function displayEditMode($edit = 0, $selected_profile = -1) {

      if ($this->interface == 1) {
         if ($edit > 0) {

            echo "<form id=\"editmode\" class='plugin_mydashboard_header_title' method='post' 
                     action='" . $this->getSearchURL() . "' onsubmit='return true;'>";

            echo __('Edit mode', 'mydashboard');

            if (!Session::haveRight("plugin_mydashboard_config", CREATE) && $edit == 2) {
               $edit = 1;
            }
            if ($edit == 2) {
               echo "&nbsp;(" . __('Global', 'mydashboard') . ")&nbsp;";
            }
            echo "&nbsp;:&nbsp;";

            if (Session::haveRight("plugin_mydashboard_config", CREATE) && $edit == 2) {
               self::dropdownProfiles(['value' => $selected_profile]);
            } else {
               echo "<input type='hidden' name='profiles_id' value='" . $_SESSION['glpiactiveprofile']['id'] . "'>";
            }

            echo "&nbsp;<span class='plugin_mydashboard_add_button'><a id='add-widget' href='#'>" . __('Add a widget', 'mydashboard') . "</a></span>";//(span.plugin_mydashboard_header_title)
            //        if($this->users_id == self::$DEFAULT_ID) echo " ".__("By Default",'mydashboard');

            echo "&nbsp;<i class=\"fa fa-caret-down\"></i></span>";

            if ($edit == 1) {
               echo "&nbsp;";
               echo "<a id='save-grid' href='#' title=\"" . __('Save grid', 'mydashboard') . "\">";
               echo __('Save grid', 'mydashboard') . "</a>&nbsp;<i class='fa fa-floppy-o fa-1x'></i>";
               echo "<span class='sr-only'>" . __('Save grid', 'mydashboard') . "</span>";
               echo "&nbsp;";
            }
            if (Session::haveRight("plugin_mydashboard_config", CREATE) && $edit == 2) {
               echo "&nbsp;";
               echo "<a id='save-default-grid' href='#' title=\"" . __('Save default grid', 'mydashboard') . "\">";
               echo __('Save default grid', 'mydashboard') . "</a>&nbsp;<i class='fa fa-hdd-o fa-1x'></i>";
               echo "<span class='sr-only'>" . __('Save default grid', 'mydashboard') . "</span>";
               echo "&nbsp;";
            }

            echo "&nbsp;";
            echo "<a id='clear-grid' href='#' title=\"" . __('Clear grid', 'mydashboard') . "\">";
            echo __('Clear grid', 'mydashboard') . "</a>&nbsp;<i class='fa fa-window-restore  fa-1x'></i>";
            echo "<span class='sr-only'>" . __('Clear grid', 'mydashboard') . "</span>";
            echo "&nbsp;";

            echo "&nbsp;";
            echo "<a id='close-edit' href='#' title=\"" . __('Close edit mode', 'mydashboard') . "\">";
            echo __('Close edit mode', 'mydashboard') . "</a>&nbsp;<i class='fa fa-times-circle-o fa-1x'></i>";
            echo "<span class='sr-only'>" . __('Close edit mode', 'mydashboard') . "</span>";
            echo "&nbsp;";
            Html::closeForm();

            echo "<div class='bt-alert bt-alert-success' id='success-alert'>
                <strong>" . __('Success', 'mydashboard') . "</strong>
                " . __('The widget was added to dashboard. Save the dashboard to see it.', 'mydashboard') . "
            </div>";
            echo Html::scriptBlock('
               $("#success-alert").hide();
         ');

         } else {
            echo "<a id='edit-grid' href='#' title=\"" . __('Switch to edit mode', 'mydashboard') . "\">";
            echo "<i class='plugin_mydashboard_discret plugin_mydashboard_header_editmode fa fa-pencil-square-o fa-2x'></i>";
            echo "<span class='sr-only'>" . __('Switch to edit mode', 'mydashboard') . "</span>";
            echo "</a>";

            echo "<a id='save-grid' href='#' title=\"" . __('Save positions', 'mydashboard') . "\">";
            echo "<i class='plugin_mydashboard_discret plugin_mydashboard_header_editmode fa fa-floppy-o fa-2x'></i>";
            echo "<span class='sr-only'>" . __('Save positions', 'mydashboard') . "</span>";
            echo "</a>";

            if (Session::haveRight("plugin_mydashboard_config", CREATE)) {
               echo "<a id='edit-default-grid' href='#' title=\"" . __('Custom and save default grid', 'mydashboard') . "\">";
               echo "<i class='plugin_mydashboard_discret plugin_mydashboard_header_editmode fa fa-cogs fa-2x'></i>";
               echo "<span class='sr-only'>" . __('Custom and save default grid', 'mydashboard') . "</span>";
               echo "</a>";
            }
         }
      }
   }

   /**
    * This message shows a preventive message when needed (example : default password of glpi profile)
    * @global type $DB
    * @global type $CFG_GLPI
    */
   //   private function showPreventiveMessage() {
   //      global $DB, $CFG_GLPI;
   //      if (Session::haveRightsOr("config", array(CREATE, UPDATE))) {
   //         $logins = User::checkDefaultPasswords();
   //         $user   = new User();
   //         if (!empty($logins)) {
   //            $accounts = array();
   //            foreach ($logins as $login) {
   //               $user->getFromDBbyName($login);
   //               $accounts[] = $user->getLink();
   //            }
   //            $message = sprintf(__('For security reasons, please change the password for the default users: %s'), implode(" ", $accounts));
   //
   //            echo "<tr><th colspan='2'>";
   //            Html::displayTitle($CFG_GLPI['root_doc'] . "/pics/warning.png", $message, $message);
   //            echo "</th></tr>";
   //         }
   //         if (file_exists(GLPI_ROOT . "/install/install.php")) {
   //            echo "<tr><th colspan='2'>";
   //            $message = sprintf(__('For security reasons, please remove file: %s'), "install/install.php");
   //            Html::displayTitle($CFG_GLPI['root_doc'] . "/pics/warning.png", $message, $message);
   //            echo "</th></tr>";
   //         }
   //      }
   //      if ($DB->isSlave() && !$DB->first_connection) {
   //         echo "<tr><th colspan='2'>";
   //         Html::displayTitle($CFG_GLPI['root_doc'] . "/pics/warning.png", __('MySQL replica: read only'), __('MySQL replica: read only'));
   //         echo "</th></tr>";
   //      }
   //   }

   /**
    * Get the HTML view of the widget list, the lateral menu
    * @return string, HTML
    */
   private function getWidgetsList($profile, $edit) {

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

      if (!empty($grid) && ($datagrid = json_decode($grid, true)) == !NULL) {
         foreach ($datagrid as $k => $v) {
            $used[] = $v["id"];
         }
      }

      $wl = "";
      //menuMyDashboard is the non moving part (it's just it width that changes)
      $wl .= "<div class='plugin_mydashboard_menuDashboard' "
             . " data-dashboardid='" . self::DASHBOARD_NAME . "'"
             . ">";//(div.plugin_mydashboard_menuDashboard)
      //      menuSlider is the moving part (jQuery changing the css property margin-right)
      $wl .= "<div class='plugin_mydashboard_menuSlider' style='float:right;' >";  //(div.plugin_mydashboard_menuSlider)
      //        $wl .= "<div class='plugin_mydashboard_menuSliderHeader'>".$this->getTypeName()."</div>";
      //menuSliderContent contains the lists of widgets
      $wl .= "<div class='plugin_mydashboard_menuSliderContent'>"; //(div.plugin_mydashboard_menuSliderContent)

      //1) we 'display' GLPI core widgets in the list
      $wl .= $this->getWidgetsListFromGLPICore($used);
      //2) we 'display' Plugin widgets
      if (self::$_PLUGIN_MYDASHBOARD_CFG['display_plugin_widget']) {
         $wl .= $this->getWidgetsListFromPlugins($used);
      }
      //-------------------------------------------------------
      $wl .= "</div>"; //end(div.plugin_mydashboard_menuSliderContent)

      //This is the handle of the menu slider
      //        $wl .= "<a class='plugin_mydashboard_menuSliderButton' style='' >".__("Dashboard",'mydashboard')."</a>";

      $wl .= "</div>"; //end(div.plugin_mydashboard_menuSlider)
      $wl .= "</div>"; //end(div.plugin_mydashboard_menuDashboard)

      return $wl;
   }

   /**
    * Initialize :
    * - the bindings between the list and the adding on the mydashboard
    * - the functions that will be called to add widgets that are on the custom dashboard
    */
   //   private function initWidgets() {
   //      //We init Database widget names
   //
   //      $this->initDBWidgets();
   //      //Then we get the custom dashboard of the user, $dash is an array of widget names
   //      $this->dashboard = $this->getDashboardForUser($this->users_id);
   //      $classObjects    = array();
   //
   //      foreach ($this->widgets as $classname => $classwidgets) {
   //         //We start the timer for this class
   //         $start         = microtime(true);
   //         $memusagestart = memory_get_usage();
   //         $empty         = true;
   //         foreach ($classwidgets as $widgetId => $view) {
   //            if ($this->isOnDash($widgetId)) {
   //               if (!isset($classObjects[$classname])) $classObjects[$classname] = getItemForItemtype($classname);
   //               if (method_exists($classObjects[$classname], "getWidgetContentForItem")) {
   //                  $widget = $classObjects[$classname]->getWidgetContentForItem($widgetId);
   //                  if (method_exists($widget, "getWidgetId")) {
   //                     $widget->setWidgetId($widgetId);
   //                     $addFunction = "mydashboard.addWidget('" . self::DASHBOARD_NAME . "',";
   //                     //To add a widget we need its title
   //                     $addFunction .= "\"" . $widget->getWidgetTitle();
   //                     //This title maybe subtitled by a view name (example GLPI Core widgets precising the view)
   //
   //                     if (isset($view) && $view != -1) {
   //                        $addFunction .= "<span class='plugin_mydashboard_discret'>&nbsp;-&nbsp;" . $view . "</span>";
   //                     }
   //                     //We also need its id, its type ('table','chart' ...)
   //                     $addFunction .= "\",'" . $widgetId . "','" . $widget->getWidgetType() . "',";
   //                     //We need its datas, formatted in json
   //                     $addFunction .= $widget->getJsonDatas() . ",";         //        Toolbox::logDebug($widget->getJsonDatas());
   //                     //$addFunction .= "{},";
   //                     //We need to know if this widget will be refreshable
   //                     $addFunction .= $widget->getWidgetEnableRefresh() . ",";
   //                     //We need to know which class defines this widget
   //                     $addFunction .= "'" . $classname . "'";
   //                     $addFunction .= ");";
   //
   //                     //If this widget is not of PluginMydashboardHtml type, it means that its HTML is to be put after
   //                     //Else it means that it's only HTML to display, and this HTML is encoded in PluginMydashboardHtml::getJsonDatas() which is already called
   //                     if ($widget->getType() != "PluginMydashboardHtml") {
   //                        $htmlContent = $widget->getWidgetHtmlContent();
   //                        if (isset($htmlContent)) {
   //                           $addFunction .= "mydashboard.addWidgetHtmlContent('" . $widgetId . "'" . "," . json_encode($htmlContent) . " );";
   //                        }
   //                        $addFunction .= "";
   //                     }
   //                     //This widget may have inner scripts, we need to force evaluation of those ones
   //                     $scriptFunctions = $this->getEvalScriptArray($widget->getWidgetScripts());
   //
   //                     $this->addfunction[$widgetId] = $addFunction . "\n " . $scriptFunctions . "\n";
   //                     $empty                        = false;
   //                  }
   //               }
   //            } else {
   //               /* Useless now, getWidget is called on click on a listItem, widgetId, classname and view are stored as an html5 custom attribute (prefixed by data-)*/
   //               //                    $addFunction = "plugin_mydashboard_getWidget(";
   //               //                    $addFunction .= "'".$classname."',";
   //               //                    $addFunction .= "'".$widgetId."'";
   //               //                    //The widget title maybe subtitled by a view name (example GLPI Core widgets precising the view)
   //               //                    if(isset($view) && $view != -1) {
   //               //                        $addFunction .= ",\"<span class='plugin_mydashboard_discret'>&nbsp;-&nbsp;".$view."</span>\"";
   //               //    //                    $addFunction .= ",'".$view."'";
   //               //                    }
   //               //                    $addFunction .= ");";
   //            }
   //            //                echo "$('#btnAddWidget".$widgetId."' )
   //            //                        .click(
   //            //                            function( event ) {
   //            //                                $addFunction;
   //            //                                event.preventDefault();
   //            //                            }
   //            //                        );";
   //         }
   //         if (!$empty) {
   //            $end                     = microtime(true);
   //            $memusageend             = memory_get_usage();
   //            $this->stats[$classname] = round($end - $start, 2) . " / " . (round(($memusageend - $memusagestart) / 1048576, 2));
   //         }
   //      }
   //      //echo  "</script>";
   //      unset($this->widgets);
   //   }

   /**
    * Stores every widgets in Database (see PluginMydashboardWidget)
    */
   private function initDBWidgets() {
      $widgetDB     = new PluginMydashboardWidget();
      $widgetsinDB  = getAllDatasFromTable(PluginMydashboardWidget::getTable());
      $widgetsnames = array();
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
    * @return string, the HTML list
    */
   private function getWidgetsListFromGLPICore($used = []) {
      $wl = "<div class='plugin_mydashboard_menuDashboardListOfPlugin'>";
      $wl .= "<h3 class='plugin_mydashboard_menuDashboardListTitle1'>GLPI</h3>";
      $wl .= "<div class='plugin_mydashboard_menuDashboardListContainer'><ul class=''>";

      //GLPI core classes doesn't display the same thing in each view, we need to provide all views available
      $views = array(self::$TICKET_VIEW,
                     self::$PROBLEM_VIEW,
                     self::$CHANGE_VIEW,
                     self::$GROUP_VIEW,
                     self::$MY_VIEW,
                     self::$GLOBAL_VIEW,
                     self::$RSS_VIEW);
      //To ease navigation we display the name of the view
      $viewsNames = $this->getViewNames();

      $viewContent = array();

      foreach ($views as $view) {
         $viewContent[$view] = "";
      }

      if (!isset($this->widgetlist['GLPI'])) return '';
      $widgetclasses = $this->widgetlist['GLPI'];

      foreach ($widgetclasses as $widgetclass => $widgets) {
         foreach ($widgets as $widgetview => $widgetlist) {
            foreach ($widgetlist as $widgetId => $widgetTitle) {
               if (is_numeric($widgetId)) $widgetId = $widgetTitle;
               $this->widgets[$widgetclass][$widgetId] = $viewsNames[$widgetview];
               $gsid                                   = PluginMydashboardWidget::getGsID($widgetId);
               if (!in_array($gsid, $used)) {
                  $viewContent[$widgetview] .= "<li "/*."id='btnAddWidgete".$widgetId."'"*/
                                               . " class='plugin_mydashboard_menuDashboardListItem'"
                                               . " data-widgetid='" . $gsid . "'"
                                               . " data-classname='" . $widgetclass . "'"
                                               . " data-view='" . $viewsNames[$widgetview] . "'>";
                  $viewContent[$widgetview] .= $widgetTitle;
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
      if ($is_empty) return '';
      else return $wl;
   }

   /**
    * Get the HTML list of the plugin widgets available
    * @global type $PLUGIN_HOOKS , that's where you have to declare your classes that defines widgets, in
    *    $PLUGIN_HOOKS['mydashboard'][YourPluginName]
    * @return string|boolean
    */
   private function getWidgetsListFromPlugins($used = []) {
      $plugin_names = $this->getPluginsNames();
      $is_empty     = true;
      $wl           = "";
      foreach ($this->widgetlist as $plugin => $widgetclasses) {
         if ($plugin == "GLPI") continue;
         $is_empty = true;
         $tmp      = "<div class='plugin_mydashboard_menuDashboardListOfPlugin'>";
         //
         $tmp .= "<h6 class='plugin_mydashboard_menuDashboardListTitle1'>" . ucfirst($plugin_names[$plugin]) . "</h6>";
         //Every widgets of a plugin are in an accordion (handled by dashboard not the jquery one)
         $tmp .= "<div class='plugin_mydashboard_menuDashboardListContainer'>";
         $tmp .= "<ul>";
         foreach ($widgetclasses as $widgetclass => $widgetlist) {
            $res = $this->getWidgetsListFromWidgetsArray($widgetlist, $widgetclass, 2, $used);
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
            $wl .= $tmp;
         }
      }

      return $wl;
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
    * @param type $widgetsarray , an arry of widgets (or array of array ... of widgets)
    * @param type $classname , name of the class containing the widget
    * @param int  $depth
    *
    * @return string
    */
   private function getWidgetsListFromWidgetsArray($widgetsarray, $classname, $depth = 2, $used = []) {
      $wl = "";
      foreach ($widgetsarray as $widgetId => $widgetTitle) {
         //We check if this widget is a real widget
         if (!is_array($widgetTitle)) {
            //We stock our widget in this->widgets
            //               $this->widgets[$widget->getWidgetId()]['widget'] = $widget;
            //               $this->widgets[$widget->getWidgetId()]['classname'] = $classname;

            //               $this->widgets[$widget->getWidgetId()]['classname'] = $classname;
            //If no 'title' is specified it won't be 'widgetid' => 'widget Title' but 'widgetid' so
            if (is_numeric($widgetId)) $widgetId = $widgetTitle;
            $this->widgets[$classname][$widgetId] = -1;
            $gsid                                 = PluginMydashboardWidget::getGsID($widgetId);
            if (!in_array($gsid, $used)) {
               $wl .= "<li id='btnAddWidgete" . $widgetId . "'"
                      . " class='plugin_mydashboard_menuDashboardListItem' "
                      . " data-widgetid='" . $gsid . "'"
                      . " data-classname='" . $classname . "'>";
               $wl .= $widgetTitle/*->getWidgetListTitle()*/
               ;
               $wl .= "</li>";
            }
         } else { //If it's not a real widget
            //It may/must be an array of widget, in this case we need to go deeper (increase $depth)
            $tmp = "<li class='plugin_mydashboard_menuDashboardList'>";
            $tmp .= "<h6 class='plugin_mydashboard_menuDashboardListTitle$depth'>" . $widgetId . "</h6>";
            $tmp .= "<ul class='plugin_mydashboard_menuDashboardList$depth'>";
            $res = $this->getWidgetsListFromWidgetsArray($widgetTitle, $classname, $depth + 1, $used);
            if ($res != '') $tmp .= $res;
            $tmp .= "</ul></li>";
            if ($res != '') $wl .= $tmp;
         }
      }
      return $wl;
   }

   /**
    * Initialize all things that are needed for sDashboard
    */
   //   private function initDashboard() {
   //      global $CFG_GLPI;
   //
   //      //This is the container where widgets will be placed as dom li s
   //      echo "<ul id='" . self::DASHBOARD_NAME . "'></ul>";
   //      //Initialization of sDashboard
   //
   //      $script = "
   //          mydashboard.setLanguageData(" . json_encode($this->getJsLanguages("mydashboard")) . ");
   //          mydashboard.setRootDoc('" . $CFG_GLPI['root_doc'] . "');";
   //
   //      //CSRF
   //      //echo "plugin_mydashboard_csrf = '".Session::getNewCSRFToken()."';";
   //
   //      $script .= "
   //          $(function() {
   //            // Initialization
   //             $('#" . self::DASHBOARD_NAME . "').sDashboard({";
   //      //Because of a "bug" in Firefox we need to set disableSelection to false to enable form (select ...) selection
   //
   //      $script          .= "disableSelection : false,";
   //      $script          .= "dashboardLanguage : " . json_encode($this->getJsLanguages("sDashboard"))/*.","*/
   //      ;
   //      $script          .= "});\n";
   //      $this->interface = 1;
   //      //We show personnal widgets BEFORE binding the 'added' event to prevent useless operation
   //      if (!empty($this->dashboard))
   //         $script .= $this->showPersonalWidgets();
   //      //Once all personnal widgets are shown, client must know what is on the mydashboard
   //      $script .= "mydashboard.setOriginalDashboard(" . json_encode($this->dashboard) . ");";
   //      //Binding to update tab when MyDashboard is rearranged
   //      //Binding to update tab when a widget is added or deleted from dashboard
   //      $script .= "$('#" . self::DASHBOARD_NAME . "').bind('sdashboardstatechanged', function(e,data) {
   //                    switch(data.triggerAction) {
   //                        case 'orderChanged' :
   //                            var sorted = data.sortedDefinitions;
   //                            var tab = new Array(sorted.length);
   //                            for(var i=0;i<sorted.length;i++) {
   //                                tab[sorted.length-i] = data.sortedDefinitions[i]['widgetId'];
   //                            }
   //                            mydashboard.saveOrder(" . $this->users_id . "," . $this->interface . ",tab);
   //                            break;
   //                        case 'widgetAdded' :
   //                            mydashboard.saveAdding(" . $this->users_id . "," . $this->interface . ",data.affectedWidget.widgetId);
   //                            break;
   //                        case 'widgetDeleted' :
   //                            mydashboard.saveRemoval(" . $this->users_id . "," . $this->interface . ",data.affectedWidgetId);
   //                            break;
   //
   //                    }
   //                });";
   //      //Binding when a widget is Maximized or Minimized,
   //      //if you want a custom behavior you can set a function in :
   //      // -> onMaximize[widgetId] = your maximize function
   //      // -> onMinimize[widgetId] = your minimize function
   //      $script .= "$('#" . self::DASHBOARD_NAME . "').bind('sdashboardwidgetmaximized', function(e,data) {
   //                    var widget = document.getElementById(data.widgetDefinition.widgetId+'content');
   //                    if(widget) widget.setAttribute('class','unscaledContent');
   //                    if (typeof onMaximize[data.widgetDefinition.widgetId] !== 'undefined') {
   //                       setTimeout(function(){onMaximize[data.widgetDefinition.widgetId]();},1);
   //                    }
   //                    $('.plugin_mydashboard_menuDashboard').zIndex(9000);
   //                });";
   //
   //      $script .= "$('#" . self::DASHBOARD_NAME . "').bind('sdashboardwidgetminimized', function(e,data) {
   //                    var widget = document.getElementById(data.widgetDefinition.widgetId+'content');
   //                    if(widget) widget.setAttribute('class','scaledContent');
   //                    if (typeof onMinimize[data.widgetDefinition.widgetId] !== 'undefined') {
   //                       setTimeout(function(){onMinimize[data.widgetDefinition.widgetId]();},1);
   //                    }
   //                    $('.plugin_mydashboard_menuDashboard').zIndex(10000);
   //                });";
   //      $script .= "});";
   //
   //      echo Html::scriptBlock('$(document).ready(function() {' . $script . '});');
   //
   //   }

   /**
    * Get an array of widgetNames as ["id1","id2"] for a specifid users_id
    *
    * @param int $id user id
    *
    * @return array of string
    */
   private function getDashboardForUser($id) {
      $user_widget = new PluginMydashboardUserWidget($id, $this->interface);
      return $user_widget->getWidgets();
   }

   /**
    * This function echo all adding functions (js) of widgets on the users dashboard
    * previously stored in $this->addfunction
    */
   private function showPersonalWidgets() {
      $output       = array();
      $to_delete    = array();
      $countHidden  = 0;
      $count        = 0;
      $tmpdashboard = array(); //will store really added widgetIds
      //If there are widgets to add, $this->addfunction will contain javascript/jquery functions
      if (isset($this->addfunction)) {
         //For every widgets that is on dashboard
         foreach ($this->dashboard as &$widgetId) {
            //We get the index of this widget in $this->dashboard
            $index = $this->getIndexOnDash($widgetId);

            //We check if the function exists,
            // if not it probably means that the plugin is desactivated,
            // or that widgets from plugin are not displayed anymore
            if (isset($this->addfunction[$widgetId])) {
               $output[$index]       = $this->addfunction[$widgetId];
               $tmpdashboard[$index] = $widgetId;
               $count++;
            } else {
               $to_delete[] = $widgetId;
               $countHidden++;
            }
         }
      }
      //We need to add widgets in the same order than it is stored on base
      ksort($output);
      $script = '';
      //We echoes the adding functions in the order
      foreach ($output as $widget) {
         $script .= $widget;
      }

      //This two lines are here to adapt the canvas size to its container
      $script .= "canvas = $('canvas');";
      $script .= "$.each(canvas, function(index,value) { value.style.width = '100%'; });";

      //countHidden is the number of widgets that can't be displayed
      //Few reasons possible :
      //->This widget is from a blacklisted plugin
      //->This widget is from a desactivated plugin
      //->An error occured in the process
      if ($countHidden != 0) {
         //If a setDefault needs to be applied,
         //it must know which widgets from the default dashboard must'nt be displayed in personnal dashboard (btw mustn't be stored)
         //            unset($_SESSION['not_to_be_added']);
         //We delete from the users dashboard all obsolete widgets
         if (!empty($to_delete)) {
            $user_widget = new PluginMydashboardUserWidget($this->users_id);
            foreach ($to_delete as $widget_to_delete) {
               $user_widget->removeWidgetByWidgetName($widget_to_delete);
               //widgets not available for User are store in a SESSION variable, later usable in saveConfig
               //                    $_SESSION['not_to_be_added'][] = $widget_to_delete;
            }
         }
      }

      //dashboard now contains really added widgets, all non added (for any reason) are not in this var anymore
      $this->dashboard = array_values($tmpdashboard);
      return $script;
   }

   /**
    * Check if a widget is on the mydashboard stored in db by its widgetName
    *
    * @param string $name
    *
    * @return boolean, TRUE if $name is in the array self::dash, else FALSE
    */
   private function isOnDash($name) {
      return in_array($name, $this->dashboard);
   }

   /**
    * Get the widget index on dash, to add it in the correct order
    *
    * @param type $name
    *
    * @return int if $name is in self::dash, FALSE otherwise
    */
   private function getIndexOnDash($name) {
      return array_search($name, $this->dashboard);
   }

   /**
    * Get all plugin names of plugin hooked with mydashboard
    * @global type $PLUGIN_HOOKS
    * @return array of string
    */
   private function getPluginsNames() {
      global $PLUGIN_HOOKS;
      $plugins_hooked = $PLUGIN_HOOKS['mydashboard'];
      $tab            = array();
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

      $languages = array();
      switch ($libraryname) {
         case "datatables" :
            $languages['sEmptyTable']     = __('No data available in table', 'mydashboard');
            $languages['sInfo']           = __('Showing _START_ to _END_ of _TOTAL_ entries', 'mydashboard');
            $languages['sInfoEmpty']      = __('Showing 0 to 0 of 0 entries', 'mydashboard');
            $languages['sInfoFiltered']   = __('(filtered from _MAX_ total entries)', 'mydashboard');
            $languages['sInfoPostFix']    = __('');
            $languages['sInfoThousands']  = __(',');
            $languages['sLengthMenu']     = __('Show _MENU_ entries', 'mydashboard');
            $languages['sLoadingRecords'] = __('Loading') . "...";
            $languages['sProcessing']     = __('Processing') . "...";
            $languages['sSearch']         = __('Search') . ":";
            $languages['sZeroRecords']    = __('No matching records found', 'mydashboard');
            $languages['oPaginate']       = array(
               'sFirst'    => __('First'),
               'sLast'     => __('Last'),
               'sNext'     => " " . __('Next'),
               'sPrevious' => __('Previous')
            );
            $languages['oAria']           = array(
               'sSortAscending'  => __(': activate to sort column ascending', 'mydashboard'),
               'sSortDescending' => __(': activate to sort column descending', 'mydashboard')
            );
            $languages['select']          = array(
               "rows" => array(
                  "_" => "",// __('You have selected %d rows', 'mydashboard')
                  //                  "0" => "Click a row to select",
                  "1" => __('1 row selected', 'mydashboard')
               )
            );
            $languages['close']           = __("Close", "mydashboard");
            $languages['maximize']        = __("Maximize", "mydashboard");
            $languages['minimize']        = __("Minimize", "mydashboard");
            $languages['refresh']         = __("Refresh", "mydashboard");
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

      $names = array();

      $names[self::$TICKET_VIEW]  = _n('Ticket', 'Tickets', 2);
      $names[self::$PROBLEM_VIEW] = _n('Problem', 'Problems', 2);
      $names[self::$CHANGE_VIEW]  = _n('Change', 'Changes', 2);
      $names[self::$GROUP_VIEW]   = __('Group View');
      $names[self::$MY_VIEW]      = __('Personal View');
      $names[self::$GLOBAL_VIEW]  = __('Global View');
      $names[self::$RSS_VIEW]     = _n('RSS feed', 'RSS feeds', 2);

      return $names;
   }

   /**
    * get a javascript string that evals all scripts stored in an array
    *
    * @param array of string $scripts
    *
    * @return string, a javascript string
    */
   private function getEvalScriptArray($scripts) {
      $eval = "";
      if (!empty($scripts)) {
         //            $eval = 'eval(\' ';
         foreach ($scripts as $script) {
            $eval .= $script;
         }
         //          $eval .=  '\');';
         return $eval;
      }
   }

   /**
    * Log $msg only when DEBUG_MODE is set
    *
    * @param type $msg
    */
   //   private function debug($msg) {
   //      if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
   //         Toolbox::logDebug($msg);
   //      }
   //   }


   /***********************/

   function loadDashboard($active_profile = -1) {
      global $CFG_GLPI;

      $this->users_id = Session::getLoginUserID();
      $this->showMenu($this->users_id, $active_profile);

      $this->initDBWidgets();
      $grid = [];

      $list = $this->getDashboardForUser($this->users_id);
      if (count($list) > 0) {
         foreach ($list as $k => $v) {
            $id     = PluginMydashboardWidget::getGsID($v);
            if ($id) {
               $data[] = ["id" => $id, "x" => 6, "y" => 6, "width" => 4, "height" => 6];
            }
         }
         $grid = json_encode($data);
      }
      //LOAD WIDGETS
      $edit = PluginMydashboardPreference::checkIfPreferenceExists(Session::getLoginUserID());

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
      $datagrid = [];
      $datajson = [];
      $optjson  = [];

      if (!empty($grid) && ($datagrid = json_decode($grid, true)) == !NULL) {

         foreach ($datagrid as $k => $v) {
            if (isset($v["id"])) {
               $datajson[$v["id"]] = PluginMydashboardWidget::getWidget($v["id"]);
            }
         }

         foreach ($datagrid as $k => $v) {
            if (isset($v["id"])) {
               $optjson[$v["id"]] = PluginMydashboardWidget::getWidgetOptions($v["id"]);
            }
         }
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
         $widgets = PluginMydashboardWidget::getWidgetList();

         foreach ($widgets as $k => $val) {
            $allwidgetjson[$k] = [__('Save grid to see widget', 'mydashboard')];
            //NOT LOAD ALL WIDGETS FOR PERF
            //            $allwidgetjson[$k] = PluginMydashboardWidget::getWidget($k);
         }
      }
      $allwidgetjson = json_encode($allwidgetjson);
      $msg_delete    = __('Delete widget', 'mydashboard');
      $msg_error     = __('No data available', 'mydashboard');
      $msg_refresh   = __('Refresh widget', 'mydashboard');
      $disableResize = 'false';
      $disableDrag   = 'true';
      $delete_button = 'false';

      if ($this->interface == 1) {
         $disableResize = 'false';
         $disableDrag   = 'false';

         if ($edit > 0) {
            $delete_button = 'true';
         }
      }
      echo "<div id='mygrid' class='mygrid'>";
      echo "<div class='grid-stack md-grid-stack'>";
      echo "</div>";

      echo "<script type='text/javascript'>
        $(function () {
            var options = {
                cellHeight: 40,
                verticalMargin: 1,
                 disableResize: $disableResize,
                 disableDrag: $disableDrag,
                 resizable: {
                    handles: 'e, se, s, sw, w'
                }
            };
            $('.grid-stack').gridstack(options);  
            new function () {
                this.serializedData = $grid;
                this.grid = $('.grid-stack').data('gridstack');
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
                            var delbutton = '&nbsp;<button title=\"$msg_delete\" class=\"md-button pull-right\" onclick=\"deleteWidget(\'' + node.id + '\');\"><i class=\"fa fa-times\"></i></button>';
                         }
                         if (refreshopt == 1) {
                            var refreshbutton = '<button title=\"$msg_refresh\" class=\"md-button refresh-icon\" onclick=\"refreshWidget(\'' + node.id + '\');\"><i class=\"fa fa-refresh\"></i></button>';
                         }
                         if ( nodeid !== undefined ) {
                         var el = $('<div><div class=\"grid-stack-item-content md-grid-stack-item-content\">' + refreshbutton + delbutton + widget + '<div/><div/>');
                            this.grid.addWidget(el, node.x, node.y, node.width, node.height, true, null, null, null, null, node.id);
                            }
                    }, this);
                    return false;
                }.bind(this);
                this.saveGrid = function () {
                    this.serializedData = _.map($('.grid-stack > .grid-stack-item:visible'), function (el) {
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
                     $.ajax({
                       url: '" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/ajax/saveGrid.php',
                       type: 'POST',
                       data:{data:sData,profiles_id:$active_profile},
                       success:function(data) {
                              window.location.href = '" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/front/menu.php';
                           }
                       });
                    return false;
                }.bind(this);
                this.saveDefaultGrid = function () {
                    this.serializedData = _.map($('.grid-stack > .grid-stack-item:visible'), function (el) {
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
                     $.ajax({
                          url: '" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/ajax/saveGrid.php',
                          type: 'POST',
                          data:{data:sData,users_id:users_id,profiles_id:$active_profile},
                          success:function(data) {
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
                  $.ajax({
                    url: '" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/ajax/clearGrid.php',
                       type: 'POST',
                       success:function(data) {
                              window.location.href = '" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/front/menu.php';
                           }
                       });
                    return false;
                }.bind(this);
                this.editGrid = function () {
                  $.ajax({
                    url: '" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/ajax/editGrid.php',
                       type: 'POST',
                       data:{edit_mode:1},
                       success:function(data) {
                              window.location.href = '" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/front/menu.php';
                          }
                       });
                    return false;
                }.bind(this);
                this.editDefaultGrid = function () {
                  $.ajax({
                    url: '" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/ajax/editGrid.php',
                       type: 'POST',
                       data:{edit_mode:2},
                       success:function(data) {
                              window.location.href = '" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/front/menu.php';
                          }
                       });
                    return false;
                }.bind(this);
                this.closeEdit = function () {
                  $.ajax({
                    url: '" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/ajax/editGrid.php',
                       type: 'POST',
                       data:{edit_mode:0},
                       success:function(data) {
                              window.location.href = '" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/front/menu.php';
                          }
                       });
                    return false;
                }.bind(this);
                $('#save-grid').click(this.saveGrid);
                $('#edit-grid').click(this.editGrid);
                $('#edit-default-grid').click(this.editDefaultGrid);
                $('#close-edit').click(this.closeEdit);
                $('#save-default-grid').click(this.saveDefaultGrid);
                $('#remove-widget').click(this.removewidget);
                $('#clear-grid').click(this.clearGrid);
                this.loadGrid();
            };
        });
        
     
    </script>";
      echo "<script type='text/javascript'>
        $('.header_fullscreen').click(
        function () {
           $('#mygrid').toggleFullScreen();
           $('#mygrid').toggleClass('fullscreen_view');
        });
        function addNewWidget(value) {
             var id = value;
             if (id != 0){
                var widgetArray = $allwidgetjson; 
                widget = widgetArray['' + id + ''];
                var el = $('<div><div class=\"grid-stack-item-content md-grid-stack-item-content\">' +
                         '<button class=\"md-button pull-right\" onclick=\"deleteWidget(\'' + id + '\');\">' +
                          '<i class=\"fa fa-times\"></i></button>' + widget + '<div/><div/>');
                var grid = $('.grid-stack').data('gridstack');
                grid.addWidget(el, 0, 0, 6, 6, '', null, null, null, null, id);
             }
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
                   widgetOptionsObject[v.name] = v.value;
               });
            var widget = $('div[id='+ id + ']');
            $.ajax({
              url: '" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/ajax/refreshWidget.php',
              type: 'POST',
              data:{gsid:gsid, params:widgetOptionsObject,id:id},
              success:function(data) {
                  widget.replaceWith(data);
              }
           });
             return false;
           };
         function deleteWidget (id) {
           this.grid = $('.grid-stack').data('gridstack');
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
                     link.download = 'myChart.png';
                     link.click();
                 }
             })
         }
    </script>";

      echo "</div>";
      //      echo "</div>";
      //      echo "</div>";
   }
}

