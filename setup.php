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

// Init the hooks of the plugins -Needed
function plugin_init_mydashboard() {
   global $PLUGIN_HOOKS, $CFG_GLPI;

   $PLUGIN_HOOKS['display_login']['mydashboard'] = "plugin_mydashboard_display_login";

   $PLUGIN_HOOKS['add_css']['mydashboard'] = array(
      "lib/sdashboard/sDashboard.css",
      "css/mydashboard.css",
   );

   $PLUGIN_HOOKS["add_javascript"]['mydashboard'] = array(
      "lib/jquery-fullscreen-plugin/jquery.fullscreen-min.js",
      "lib/sdashboard/lib/datatables/jquery.dataTables.js",
      "lib/sdashboard/lib/flotr2/flotr2.js",
      "lib/sdashboard/jquery-sDashboard.js",
      "lib/vticker/vticker.js"
   );

   $PLUGIN_HOOKS["javascript"]['mydashboard'] = array(
      "/plugins/mydashboard/scripts/mydashboard.js",
   );

   $PLUGIN_HOOKS['csrf_compliant']['mydashboard'] = true;
   $PLUGIN_HOOKS['change_profile']['mydashboard'] = array('PluginMydashboardProfile', 'initProfile');

   if (Session::getLoginUserID()) {
      Plugin::registerClass('PluginMydashboardProfile', array('addtabon' => 'Profile'));
      //Probably useless if MyDashboard is configured to replace central
      //Plugin::registerClass('PluginMydashboardCentral', array('addtabon' => array('Central')));

      $plugin = new Plugin();

      if ($plugin->isActivated("mydashboard")) {
         //If user has right to see configuration 
         if (Session::haveRightsOr("plugin_mydashboard_config", array(CREATE, UPDATE))) {
            $PLUGIN_HOOKS['config_page']['mydashboard'] = 'front/config.form.php';
            //            $PLUGIN_HOOKS['menu_toadd']['mydashboard']['links']['config'] = 'front/config.form.php';
         }

         if (class_exists('PluginServicecatalogMain')) {
            $PLUGIN_HOOKS['servicecatalog']['mydashboard'] = array('PluginMydashboardServicecatalog');
         }

         if (Session::haveRightsOr("plugin_mydashboard", array(CREATE, READ))) {

            $PLUGIN_HOOKS['menu_toadd']['mydashboard']          = array('tools' => 'PluginMydashboardMenu');
            $PLUGIN_HOOKS['helpdesk_menu_entry']['mydashboard'] = '/front/menu.php';

            if (class_exists('PluginServicecatalogMain') && Session::haveRight("plugin_servicecatalog", READ)) {
               unset($PLUGIN_HOOKS['helpdesk_menu_entry']['mydashboard']);
            }
            if (strpos($_SERVER['REQUEST_URI'], 'redirect') !== false) {
               $_SESSION["glpi_plugin_mydashboard_loaded"] = 1;
            }
            if (isset($_SESSION["glpi_plugin_mydashboard_loaded"])
                && $_SESSION["glpi_plugin_mydashboard_loaded"] == 0) {

               if (strpos($_SERVER['REQUEST_URI'], 'central.php?redirect') === false) {
                  if ($_SESSION['glpiactiveprofile']['interface'] == 'central'
                      && (!$_SESSION['glpiactiveprofile']['create_ticket_on_login'])) {
                     $_SESSION["glpi_plugin_mydashboard_loaded"] = 1;
                     Html::redirect($CFG_GLPI['root_doc'] . "/plugins/mydashboard/front/menu.php");

                  } else if (!$plugin->isActivated("servicecatalog")
                             || Session::haveRight("plugin_servicecatalog", 1)) {
                     $_SESSION["glpi_plugin_mydashboard_loaded"] = 1;
                     Html::redirect($CFG_GLPI['root_doc'] . "/plugins/mydashboard/front/menu.php");

                  } else if (!$_SESSION['glpiactiveprofile']['create_ticket_on_login']) {
                     $_SESSION["glpi_plugin_mydashboard_loaded"] = 1;
                     Html::redirect($CFG_GLPI['root_doc'] . "/plugins/mydashboard/front/menu.php");
                  }
               }
            }

            if (PluginMydashboardHelper::getReplaceCentral()
                && Session::haveRightsOr("plugin_mydashboard", array(CREATE, READ))) {
               $PLUGIN_HOOKS["add_javascript"]['mydashboard'][] = 'scripts/replace_central.js';
            }
            Plugin::registerClass('PluginMydashboardPreference',
                                  array('addtabon' => 'Preference'));

            Plugin::registerClass('PluginMydashboardAlert',
                                  array('addtabon' => 'Reminder'));

            $PLUGIN_HOOKS['mydashboard']['mydashboard'] = array('PluginMydashboardInfotel', 'PluginMydashboardAlert');
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


   return array(
      'name'           => __('My Dashboard', 'mydashboard'),
      'version'        => '1.4.0',
      'author'         => "<a href='http://infotel.com/services/expertise-technique/glpi/'>Infotel</a>",
      'license'        => 'GPLv2+',
      'homepage'       => 'https://github.com/InfotelGLPI/mydashboard',
      'minGlpiVersion' => '9.2');// For compatibility / no install in version < 0.90

}

/**
 * Optional : check prerequisites before install : may print errors or add to message after redirect
 *
 * @return bool
 */
function plugin_mydashboard_check_prerequisites() {
   if (version_compare(GLPI_VERSION, '9.2', 'lt') || version_compare(GLPI_VERSION, '9.3', 'ge')) {
      echo __('This plugin requires GLPI >= 9.2');
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
