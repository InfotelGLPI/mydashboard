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
function plugin_init_mydashboard()
{
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
      "scripts/mydashboard.js",
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

         if (Session::haveRightsOr("plugin_mydashboard", array(CREATE, READ))) {
            $PLUGIN_HOOKS['menu_toadd']['mydashboard'] = array('tools' => 'PluginMydashboardMenu');
            $PLUGIN_HOOKS['helpdesk_menu_entry']['mydashboard'] = '/front/menu.php';

            if (isset($_SESSION["glpi_plugin_mydashboard_loaded"])
               && $_SESSION["glpi_plugin_mydashboard_loaded"] == 0
            ) {
               $_SESSION["glpi_plugin_mydashboard_loaded"] = 1;
               Html::redirect($CFG_GLPI['root_doc'] . "/plugins/mydashboard/front/menu.php");
            }

            if (PluginMydashboardHelper::getReplaceCentral()
               && Session::haveRightsOr("plugin_mydashboard", array(CREATE, READ))
            ) {
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
function plugin_version_mydashboard()
{


   return array(
      'name' => __('My Dashboard', 'mydashboard'),
      'version' => '1.3.2',
      'author' => "<a href='http://infotel.com/services/expertise-technique/glpi/'>Infotel</a>",
      'license' => 'GPLv2+',
      'homepage' => 'https://github.com/InfotelGLPI/mydashboard',
      'minGlpiVersion' => '0.90');// For compatibility / no install in version < 0.90

}

// Optional : check prerequisites before install : may print errors or add to message after redirect
/**
 * @return bool
 */
function plugin_mydashboard_check_prerequisites()
{
   //85
   if (version_compare(GLPI_VERSION, '0.90', 'lt') || version_compare(GLPI_VERSION, '9.2', 'ge')) {
      _e('This plugin requires GLPI >= 0.90', 'mydashboard');
      return false;
   }
   return true;
}

// Uninstall process for plugin : need to return true if succeeded : may display messages or add to message after redirect
/**
 * @return bool
 */
function plugin_mydashboard_check_config()
{
   //To prevent redirecting when activating Dashboard
   $_SESSION['glpi_plugin_mydashboard_activating'] = 1;
   return true;
}
