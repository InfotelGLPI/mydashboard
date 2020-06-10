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

define('PLUGIN_MYDASHBOARD_VERSION', '1.8.0');

// Init the hooks of the plugins -Needed
function plugin_init_mydashboard() {
   global $PLUGIN_HOOKS, $CFG_GLPI;

   // manage autoload of plugin custom classes
   include_once(GLPI_ROOT . "/plugins/mydashboard/inc/autoload.php");
   $autoloader = new PluginMydasboardAutoloader();
   $autoloader->register();

   $PLUGIN_HOOKS['display_login']['mydashboard'] = "plugin_mydashboard_display_login";

   $PLUGIN_HOOKS['add_css']['mydashboard'] = [
      "lib/sdashboard/sDashboard.css",
      "css/mydashboard.css",
   ];

   $PLUGIN_HOOKS["add_javascript"]['mydashboard'] = [
      "lib/jquery-fullscreen-plugin/jquery.fullscreen-min.js",
      "lib/fileSaver.min.js",
      //"lib/sdashboard/lib/datatables/jquery.dataTables.min.js",
      //"lib/sdashboard/lib/flotr2/flotr2.min.js",
      "scripts/mydashboard.js",
      "lib/vticker/vticker.js"
   ];

   $PLUGIN_HOOKS["javascript"]['mydashboard'] = [
      "/plugins/mydashboard/scripts/mydashboard.js",
   ];

   $PLUGIN_HOOKS['csrf_compliant']['mydashboard'] = true;
   $PLUGIN_HOOKS['change_profile']['mydashboard'] = ['PluginMydashboardProfile', 'initProfile'];

   if (Session::getLoginUserID()) {
      Plugin::registerClass('PluginMydashboardProfile', ['addtabon' => 'Profile']);

      $plugin = new Plugin();

      if ($plugin->isActivated("mydashboard")) {
         //If user has right to see configuration
         if (Session::haveRightsOr("plugin_mydashboard_config", [CREATE, UPDATE])) {
            $PLUGIN_HOOKS['config_page']['mydashboard'] = 'front/config.php';
            //            $PLUGIN_HOOKS['menu_toadd']['mydashboard']['links']['config'] = 'front/config.form.php';
         }

         if ($plugin->isActivated('servicecatalog')) {
            $PLUGIN_HOOKS['servicecatalog']['mydashboard'] = ['PluginMydashboardServicecatalog'];
         }

         if (Session::haveRightsOr("plugin_mydashboard", [CREATE, READ])) {

            $PLUGIN_HOOKS['menu_toadd']['mydashboard']          = ['tools' => 'PluginMydashboardMenu'];
            $PLUGIN_HOOKS['helpdesk_menu_entry']['mydashboard'] = '/front/menu.php';

//            $CFG_GLPI['javascript']['tools']['pluginmydashboardmenu']['PluginMydashboardConfig'] = ['colorpicker'];

            if ($plugin->isActivated('servicecatalog')
                && Session::haveRight("plugin_servicecatalog", READ)) {
               unset($PLUGIN_HOOKS['helpdesk_menu_entry']['mydashboard']);
            }
            if (isset($_SERVER['HTTP_REFERER'])
                && strpos($_SERVER['HTTP_REFERER'], 'redirect') !== false
                && strpos($_SERVER['REQUEST_URI'], 'apirest.php') === false) {
               $_SESSION["glpi_plugin_mydashboard_loaded"] = 1;
            }
            if (isset($_SESSION["glpi_plugin_mydashboard_loaded"])
                && $_SESSION["glpi_plugin_mydashboard_loaded"] == 0) {

               if (strpos($_SERVER['REQUEST_URI'], 'central.php?redirect') === false
                   && strpos($_SERVER['REQUEST_URI'], 'apirest.php') === false) {
                  if(Session::getCurrentInterface() == 'central'){
                     if (!$_SESSION['glpiactiveprofile']['create_ticket_on_login']) {
                        $_SESSION["glpi_plugin_mydashboard_loaded"] = 1;
                        Html::redirect($CFG_GLPI['root_doc'] . "/plugins/mydashboard/front/menu.php");

                     } else if (!$plugin->isActivated("servicecatalog")
                                || Session::haveRight("plugin_servicecatalog", 1)) {
                        $_SESSION["glpi_plugin_mydashboard_loaded"] = 1;
                        Html::redirect($CFG_GLPI['root_doc'] . "/plugins/mydashboard/front/menu.php");

                     }
                  }
               }
            }

            if(Session::getCurrentInterface() == 'central'){
               if (PluginMydashboardHelper::getReplaceCentral()
                   && Session::haveRightsOr("plugin_mydashboard", [CREATE, READ])){
                  $PLUGIN_HOOKS["add_javascript"]['mydashboard'][] = 'scripts/replace_central.js';
               } else if (PluginMydashboardHelper::getReplaceCentralConf()
                          && PluginMydashboardHelper::getReplaceCentral()
                          && Session::haveRightsOr("plugin_mydashboard", [CREATE, READ])){
                  $PLUGIN_HOOKS["add_javascript"]['mydashboard'][] = 'scripts/replace_central.js';
               }
            }

            Plugin::registerClass('PluginMydashboardPreference',
                                  ['addtabon' => 'Preference']);

            Plugin::registerClass('PluginMydashboardAlert',
                                  ['addtabon' => ['Reminder', 'Problem', 'Change']]);

            Plugin::registerClass('PluginMydashboardHTMLEditor', ['addtabon' => 'PluginMydashboardCustomswidget']);

         }

      }
      $PLUGIN_HOOKS['post_init']['mydashboard'] = 'plugin_mydashboard_postinit';
   }
}

// Get the name and the version of the plugin - Needed
/**
 * @return array
 */
function plugin_version_mydashboard() {

   return [
      'name'           => __('My Dashboard', 'mydashboard'),
      'version'        => PLUGIN_MYDASHBOARD_VERSION,
      'author'         => "<a href='http://blogglpi.infotel.com'>Infotel</a>",
      'license'        => 'GPLv2+',
      'homepage'       => 'https://github.com/InfotelGLPI/mydashboard',
      'requirements'   => [
         'glpi' => [
            'min' => '9.5',
            'dev' => false
         ]
      ]];
}

/**
 * Optional : check prerequisites before install : may print errors or add to message after redirect
 *
 * @return bool
 */
function plugin_mydashboard_check_prerequisites() {
   if (version_compare(GLPI_VERSION, '9.5', 'lt')
         || version_compare(GLPI_VERSION, '9.6', 'ge')) {
      if (method_exists('Plugin', 'messageIncompatible')) {
         echo Plugin::messageIncompatible('core', '9.6');
      }
      return false;
   }

   return true;
}


/**
 * Uninstall process for plugin : need to return true if succeeded : may display messages or add to message after redirect
 *
 * @return bool
 */
function plugin_mydashboard_check_config() {
   //To prevent redirecting when activating Dashboard
   $_SESSION['glpi_plugin_mydashboard_activating'] = 1;
   return true;
}
