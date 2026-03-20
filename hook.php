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

use GlpiPlugin\Mydashboard\Alert;
use GlpiPlugin\Mydashboard\Config;
use GlpiPlugin\Mydashboard\ConfigTranslation;
use GlpiPlugin\Mydashboard\Customswidget;
use GlpiPlugin\Mydashboard\Groupprofile;
use GlpiPlugin\Mydashboard\ItilAlert;
use GlpiPlugin\Mydashboard\Menu;
use GlpiPlugin\Mydashboard\Preference as MydashboardPreference;
use GlpiPlugin\Mydashboard\PreferenceUserBlacklist;
use GlpiPlugin\Mydashboard\Profile;
use GlpiPlugin\Mydashboard\StockTicket;
use GlpiPlugin\Mydashboard\StockTicketIndicator;
use GlpiPlugin\Mydashboard\StockWidget;
use GlpiPlugin\Mydashboard\Widget;
use GlpiPlugin\Mydashboard\ProfileAuthorizedWidget;
use GlpiPlugin\Mydashboard\UserWidget;
use GlpiPlugin\Mydashboard\Dashboard;

/**
 * @return bool
 */
function plugin_mydashboard_install()
{

    $migration = new Migration(PLUGIN_MYDASHBOARD_VERSION);

    Widget::install($migration);
    UserWidget::install($migration);
    Config::install($migration);
    MydashboardPreference::install($migration);
    PreferenceUserBlacklist::install($migration);
    StockWidget::install($migration);
    Customswidget::install($migration);
    ProfileAuthorizedWidget::install($migration);
    Alert::install($migration);
    StockTicket::install($migration);
    Dashboard::install($migration);
    ItilAlert::install($migration);
    Groupprofile::install($migration);
    ConfigTranslation::install($migration);
    StockTicketIndicator::install($migration);

    Menu::installWidgets();

    Profile::initProfile();

    Profile::createFirstAccess($_SESSION['glpiactiveprofile']['id']);
    return true;
}



// Uninstall process for plugin : need to return true if succeeded
/**
 * @return bool
 */
function plugin_mydashboard_uninstall()
{

    Widget::uninstall();
    UserWidget::uninstall();
    Config::uninstall();
    MydashboardPreference::uninstall();
    PreferenceUserBlacklist::uninstall();
    StockWidget::uninstall();
    Customswidget::uninstall();
    ProfileAuthorizedWidget::uninstall();
    Alert::uninstall();
    StockTicket::uninstall();
    Dashboard::uninstall();
    ItilAlert::uninstall();
    Groupprofile::uninstall();
    ConfigTranslation::uninstall();
    StockTicketIndicator::uninstall();

    //Delete rights associated with the plugin
    $profileRight = new ProfileRight();

    foreach (Profile::getAllRights() as $right) {
        $profileRight->deleteByCriteria(['name' => $right['field']]);
    }
    Profile::removeRightsFromSession();

    return true;
}

function plugin_mydashboard_postinit()
{
    global $PLUGIN_HOOKS;

    $plugin = 'mydashboard';
    foreach (['add_css', 'add_javascript'] as $type) {
        foreach ($PLUGIN_HOOKS[$type][$plugin] as $data) {
            if (!empty($PLUGIN_HOOKS[$type])) {
                foreach ($PLUGIN_HOOKS[$type] as $key => $plugins_data) {
                    if (is_array($plugins_data) && $key != $plugin) {
                        foreach ($plugins_data as $key2 => $values) {
                            if ($values == $data) {
                                unset($PLUGIN_HOOKS[$type][$key][$key2]);
                            }
                        }
                    }
                }
            }
        }
    }
}

function plugin_mydashboard_display_login()
{
    $alerts = new Alert();
    echo $alerts->getAlertSummary(1);
}

// Define dropdown relations
/**
 * @return array
 */
function plugin_mydashboard_getDatabaseRelations()
{
    return ["glpi_groups" => [
        'glpi_plugin_mydashboard_stocktickets' => "groups_id",
    ],
        "glpi_reminders" => [
            'glpi_plugin_mydashboard_itilalerts' => "reminders_id",
            'glpi_plugin_mydashboard_alerts' => "reminders_id",
        ],
    ];
}

// Define Dropdown tables to be manage in GLPI
function plugin_mydashboard_getDropdown()
{
    if (Plugin::isPluginActive("mydashboard")) {
        return [
            Customswidget::class => Customswidget::getTypeName(2),];
    } else {
        return [];
    }
}
