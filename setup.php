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

define('PLUGIN_MYDASHBOARD_VERSION', '2.2.6');

global $CFG_GLPI;

use Glpi\Plugin\Hooks;
use GlpiPlugin\Mydashboard\Alert;
use GlpiPlugin\Mydashboard\Config;
use GlpiPlugin\Mydashboard\Customswidget;
use GlpiPlugin\Mydashboard\HTMLEditor;
use GlpiPlugin\Mydashboard\Menu;
use GlpiPlugin\Mydashboard\Preference;
use GlpiPlugin\Mydashboard\Profile;
use GlpiPlugin\Mydashboard\Servicecatalog;

if (!defined("PLUGIN_MYDASHBOARD_DIR")) {
    define("PLUGIN_MYDASHBOARD_DIR", Plugin::getPhpDir("mydashboard"));
    $root = $CFG_GLPI['root_doc'] . '/plugins/mydashboard';
    define("PLUGIN_MYDASHBOARD_WEBDIR", $root);
}

// Init the hooks of the plugins -Needed
function plugin_init_mydashboard()
{
    global $PLUGIN_HOOKS;


    $PLUGIN_HOOKS[Hooks::DISPLAY_LOGIN]['mydashboard'] = "plugin_mydashboard_display_login";

    $PLUGIN_HOOKS[Hooks::ADD_CSS]['mydashboard'] = [
        "css/mydashboard.css",
        "css/jquery.newsTicker.css",
    ];
    if (Session::getCurrentInterface() == 'central') {
        $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['mydashboard'][] = 'lib/fuse.js';
        $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['mydashboard'][] = 'lib/md-fuzzysearch.js.php';
        $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['mydashboard'][] = 'lib/jquery-fullscreen-plugin/jquery.fullscreen-min.js';
        $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['mydashboard'][] = 'scripts/mydashboard.js';
    }

    if (Session::getCurrentInterface() == 'central'
        && isset($_SERVER['REQUEST_URI'])
        && strpos($_SERVER['REQUEST_URI'], 'mydashboard') == true) {
        $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['mydashboard'] = ["scripts/mydashboard_load_scripts.js.php"];
    }
    $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['mydashboard'][] = 'lib/jquery-advanced-news-ticker/jquery.newsTicker.min.js';
    //    $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['mydashboard'] = [
    //       "lib/jquery-fullscreen-plugin/jquery.fullscreen-min.js",
    //       "lib/fileSaver.min.js",
    //       "lib/fuze.js",
    //       "lib/fuzzysearch.js.php",
    //       "scripts/mydashboard.js",
    //       "lib/jquery-advanced-news-ticker/jquery.newsTicker.min.js"
    //    ];
    //

    //        if (Session::getCurrentInterface() == 'central') {
    //            $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['mydashboard'] = [
    //               "scripts/mydashboard_load_scripts.js.php",
    //            ];
    //        }
    //        $PLUGIN_HOOKS["javascript"]['mydashboard'] = [
    //           PLUGIN_MYDASHBOARD_WEBDIR . "/lib/fileSaver.min.js",
    //           PLUGIN_MYDASHBOARD_WEBDIR . "/lib/jquery-advanced-news-ticker/jquery.newsTicker.min.js",
    //           PLUGIN_MYDASHBOARD_WEBDIR . "/scripts/mydashboard.js",
    //           PLUGIN_MYDASHBOARD_WEBDIR . "/scripts/mydashboard_load_scripts.js.php",
    //        ];
    //        if (Session::getCurrentInterface() == 'central') {
    //            $PLUGIN_HOOKS["javascript"]['mydashboard'] = [
    //               PLUGIN_MYDASHBOARD_WEBDIR . "/scripts/mydashboard_load_scripts.js.php",
    //                PLUGIN_MYDASHBOARD_WEBDIR . "/lib/fuze.js",
    //                 PLUGIN_MYDASHBOARD_WEBDIR . "lib/fuzzysearch.js.php"
    //            ];
    //        }
    //    }

    $PLUGIN_HOOKS[Hooks::CHANGE_PROFILE]['mydashboard'] = [Profile::class, 'initProfile'];

    if (Session::getLoginUserID()) {
        Plugin::registerClass(Profile::class, ['addtabon' => 'Profile']);

        if (Plugin::isPluginActive("mydashboard")) {
            //If user has right to see configuration
            if (Session::haveRightsOr("plugin_mydashboard_config", [CREATE, UPDATE])) {
                $PLUGIN_HOOKS[Hooks::CONFIG_PAGE]['mydashboard'] = 'front/config.form.php';
                //            $PLUGIN_HOOKS['menu_toadd']['mydashboard']['links']['config'] = 'front/config.form.php';
            }

            if (Plugin::isPluginActive('servicecatalog')) {
                $PLUGIN_HOOKS['servicecatalog']['mydashboard'] = [Servicecatalog::class];
            }

            if (Session::haveRightsOr("plugin_mydashboard", [CREATE, READ])) {
                $PLUGIN_HOOKS[Hooks::MENU_TOADD]['mydashboard']               = ['tools' => Menu::class];
                $PLUGIN_HOOKS[Hooks::HELPDESK_MENU_ENTRY]['mydashboard']      = PLUGIN_MYDASHBOARD_WEBDIR . '/front/menu.php';
                $PLUGIN_HOOKS[Hooks::HELPDESK_MENU_ENTRY_ICON]['mydashboard'] = Menu::getIcon();

                //            $CFG_GLPI['javascript']['tools']['pluginmydashboardmenu'][Config::class] = ['colorpicker'];

                if (Plugin::isPluginActive('servicecatalog')
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
                        if (Session::getCurrentInterface() == 'central') {
                            if (!$_SESSION['glpiactiveprofile']['create_ticket_on_login']) {
                                $_SESSION["glpi_plugin_mydashboard_loaded"] = 1;
//                                Html::redirect(PLUGIN_MYDASHBOARD_WEBDIR . "/front/menu.php");
                                $dest = PLUGIN_MYDASHBOARD_WEBDIR . "/front/menu.php";
                                $toadd = '';
                                $dest = addslashes($dest);

                                echo "<script type='text/javascript'>
                            NomNav = navigator.appName;
                            if (NomNav=='Konqueror') {
                               window.location='" . $dest . $toadd . "';
                            } else {
                               window.location='" . $dest . "';
                            }
                         </script>";
                                exit();
                            } elseif (!Plugin::isPluginActive("servicecatalog")
                                       || Session::haveRight("plugin_servicecatalog", 1)) {
                                $_SESSION["glpi_plugin_mydashboard_loaded"] = 1;
//                                Html::redirect(PLUGIN_MYDASHBOARD_WEBDIR . "/front/menu.php");
                                $dest = PLUGIN_MYDASHBOARD_WEBDIR . "/front/menu.php";
                                $toadd = '';
                                $dest = addslashes($dest);

                                echo "<script type='text/javascript'>
                            NomNav = navigator.appName;
                            if (NomNav=='Konqueror') {
                               window.location='" . $dest . $toadd . "';
                            } else {
                               window.location='" . $dest . "';
                            }
                         </script>";
                            }
                        }
                    }
                }

                if (Session::getCurrentInterface() == 'central') {
                    if (Preference::getReplaceCentral()
                        && Session::haveRightsOr("plugin_mydashboard", [CREATE, READ])) {
                        $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['mydashboard'][] = 'scripts/replace_central.js.php';
                    } elseif (Config::getReplaceCentralConf()
                               && Preference::getReplaceCentral()
                               && Session::haveRightsOr("plugin_mydashboard", [CREATE, READ])) {
                        $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['mydashboard'][] = 'scripts/replace_central.js.php';
                    }
                }

                Plugin::registerClass(
                    Preference::class,
                    ['addtabon' => 'Preference']
                );

                Plugin::registerClass(
                    Alert::class,
                    ['addtabon' => ['Reminder', 'Problem', 'Change']]
                );

                Plugin::registerClass(HTMLEditor::class, ['addtabon' => Customswidget::class]);
            }
            $PLUGIN_HOOKS[Hooks::PRE_ITEM_PURGE]['mydashboard'] = ['Reminder' => [Alert::class,
                'purgeAlerts']];
        }
        $PLUGIN_HOOKS[Hooks::POST_INIT]['mydashboard'] = 'plugin_mydashboard_postinit';
    }
}

// Get the name and the version of the plugin - Needed
/**
 * @return array
 */
function plugin_version_mydashboard()
{
    return [
        'name'         => __('My Dashboard', 'mydashboard'),
        'version'      => PLUGIN_MYDASHBOARD_VERSION,
        'author'       => "<a href='https//blogglpi.infotel.com'>Infotel</a>, Xavier CAILLAUD",
        'license'      => 'GPLv2+',
        'homepage'     => 'https://github.com/InfotelGLPI/mydashboard',
        'requirements' => [
            'glpi' => [
                'min' => '11.0',
                'max' => '12.0',
                'dev' => false,
            ],
        ]];
}
