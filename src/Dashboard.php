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

namespace GlpiPlugin\Mydashboard;

use CommonDBTM;
use DBConnection;
use DbUtils;
use GlpiPlugin\Mydashboard\Reports\Reports_Bar;
use GlpiPlugin\Mydashboard\Reports\Reports_Line;
use GlpiPlugin\Mydashboard\Reports\Reports_Pie;
use GlpiPlugin\Mydashboard\Reports\Reports_Table;
use GlpiPlugin\Ocsinventoryng\Dashboard as OCSDashboard;
use Migration;
use Plugin;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class Dashboard
 */
class Dashboard extends CommonDBTM
{
    public static $GLPI_VIEW                = 1;
    public static $INVENTORY_VIEW           = 2;
    public static $HELPDESK_SUPERVISOR_VIEW = 3;
    public static $INCIDENT_SUPERVISOR_VIEW = 4;
    public static $REQUEST_SUPERVISOR_VIEW  = 5;
    public static $HELPDESK_TECHNICIAN_VIEW = 6;

    /**
     * Return the localized name of the current Type
     * Should be overloaded in each new class
     *
     * @param integer $nb Number of items
     *
     * @return string
     **/
    public static function getTypeName($nb = 0)
    {
        return __('Dashboard', 'mydashboard');
    }


    /**
     * @param $options
     *
     * @return int
     */
    public static function checkIfPreferenceExists($options)
    {
        return self::checkPreferenceValue('id', $options);
    }

    /**
     * @param $field
     * @param $options
     *
     * @return int
     */
    public static function checkPreferenceValue($field, $options)
    {
        $dbu        = new DbUtils();
        $data = $dbu->getAllDataFromTable(getTableForItemType(__CLASS__), ["users_id" => $options["users_id"], "profiles_id" => $options["profiles_id"]]);
        if (!empty($data)) {
            $first = array_pop($data);
            return $first[$field];
        } else {
            return 0;
        }
    }

    /**
     * @param array $input
     *
     * @return array
     */
    public function prepareInputForAdd($input)
    {
        return self::prepareInputForUpdate($input);
    }

    /**
     * @param array$input
     *
     * @return array
     */
    public function prepareInputForUpdate($input)
    {

        //remove duplicate widgets
        $ID_check = [];

        $datagrid = json_decode($input['grid'], true);

        foreach ($datagrid as $key => $data) {
            //check if widget already present
            if (in_array($data['id'], $ID_check)) {
                //widget delete
                unset($datagrid[$key]);
            } else {
                $ID_check[$data['id']] = $data['id'];
            }
        }
        $input['grid'] = json_encode($datagrid);

        return $input;
    }

    public static function getPredefinedDashboardName()
    {

        $elements = [self::$GLPI_VIEW                => __('GLPI admin grid', 'mydashboard'),
            self::$INVENTORY_VIEW           => __('Inventory admin grid', 'mydashboard'),
            self::$HELPDESK_SUPERVISOR_VIEW => __('Helpdesk supervisor grid', 'mydashboard'),
            self::$INCIDENT_SUPERVISOR_VIEW => __('Incident supervisor grid', 'mydashboard'),
            self::$REQUEST_SUPERVISOR_VIEW  => __('Request supervisor grid', 'mydashboard'),
            self::$HELPDESK_TECHNICIAN_VIEW => __('Helpdesk technician grid', 'mydashboard')];

        return $elements;
    }


    public static function loadPredefinedDashboard($id)
    {

        $data = '';
        if ($id == self::$GLPI_VIEW) {
            $gs1  = Widget::getGsID(Alert::class . "8");
            $gs2  = Widget::getGsID(Alert::class . "9");
            $gs3  = Widget::getGsID(Alert::class . "6");
            $gs4  = Widget::getGsID("eventwidgetglobal");
            $gs5  = Widget::getGsID(Reports_Table::class . "3");
            $data = '[{"id":"' . $gs1 . '","x":0,"y":0,"w":4,"h":8},
         {"id":"' . $gs2 . '","x":4,"y":0,"w":4,"h":8},
         {"id":"' . $gs3 . '","x":8,"y":0,"w":4,"h":8},
         {"id":"' . $gs4 . '","x":0,"y":8,"w":4,"h":8},
         {"id":"' . $gs5 . '","x":4,"y":8,"w":4,"h":8}]';
        }
        if ($id == self::$INVENTORY_VIEW) {
            $data_ocs = '';
            if (Plugin::isPluginActive("ocsinventoryng")) {
                $gs4      = Widget::getGsID(OCSDashboard::class . "1");
                $gs5      = Widget::getGsID(OCSDashboard::class . "2");
                $data_ocs = ',{"id":"' . $gs4 . '","x":0,"y":9,"w":5,"h":12},
                        {"id":"' . $gs5 . '","x":5,"y":9,"w":5,"h":12}';
            }

            $gs1  = Widget::getGsID(Reports_Table::class . "5");
            $gs2  = Widget::getGsID("contractwidget");
            $gs3  = Widget::getGsID(Reports_Table::class . "3");
            $data = '[{"id":"' . $gs1 . '","x":0,"y":0,"w":4,"h":9},
         {"id":"' . $gs2 . '","x":4,"y":0,"w":4,"h":9},
         {"id":"' . $gs3 . '","x":8,"y":0,"w":4,"h":8}';
            $data .= $data_ocs;
            $data .= ']';
        }
        if ($id == self::$HELPDESK_SUPERVISOR_VIEW) {
            $gs1  = Widget::getGsID(Alert::class . "4");
            $gs2  = Widget::getGsID(Reports_Bar::class . "24");
            $gs3  = Widget::getGsID(Reports_Bar::class . "1");
            $gs4  = Widget::getGsID(Reports_Line::class . "22");
            $gs5  = Widget::getGsID(Alert::class . "5");
            $gs6  = Widget::getGsID(Reports_Line::class . "6");
            $gs7  = Widget::getGsID(Reports_Pie::class . "25");
            $gs8  = Widget::getGsID(Reports_Pie::class . "12");
            $gs9  = Widget::getGsID(Alert::class . "2");
            $gs10 = Widget::getGsID(Alert::class . "1");
            $gs11 = Widget::getGsID(Reports_Pie::class . "7");
            $gs12 = Widget::getGsID(Reports_Pie::class . "18");

            $data = '[{"id":"' . $gs1 . '","x":0,"y":0,"w":4,"h":8},
         {"id":"' . $gs2 . '","x":0,"y":8,"w":4,"h":11},
         {"id":"' . $gs3 . '","x":0,"y":19,"w":4,"h":12},
         {"id":"' . $gs4 . '","x":0,"y":31,"w":5,"h":12},
         {"id":"' . $gs5 . '","x":4,"y":0,"w":5,"h":8},
         {"id":"' . $gs6 . '","x":4,"y":8,"w":4,"h":11},
         {"id":"' . $gs7 . '","x":4,"y":19,"w":4,"h":12},
         {"id":"' . $gs8 . '","x":5,"y":31,"w":3,"h":11},
         {"id":"' . $gs9 . '","x":9,"y":0,"w":3,"h":8},
         {"id":"' . $gs10 . '","x":8,"y":8,"w":4,"h":11},
         {"id":"' . $gs11 . '","x":8,"y":19,"w":4,"h":12},
         {"id":"' . $gs12 . '","x":8,"y":31,"w":4,"h":12}]';
        }
        if ($id == self::$INCIDENT_SUPERVISOR_VIEW) {
            $gs1 = Widget::getGsID(Reports_Pie::class . "16");
            $gs2 = Widget::getGsID(Alert::class . "5");
            $gs3 = Widget::getGsID(Alert::class . "2");
            $gs4 = Widget::getGsID(Alert::class . "1");
            $gs5 = Widget::getGsID(Alert::class . "4");
            $gs6 = Widget::getGsID(Reports_Line::class . "6");
            $gs7 = Widget::getGsID(Alert::class . "7");

            $data = '[{"id":"' . $gs1 . '","x":0,"y":8,"w":4,"h":11},
         {"id":"' . $gs2 . '","x":8,"y":0,"w":4,"h":8},
         {"id":"' . $gs3 . '","x":9,"y":19,"w":3,"h":8},
         {"id":"' . $gs4 . '","x":9,"y":8,"w":3,"h":11},
         {"id":"' . $gs5 . '","x":0,"y":0,"w":4,"h":8},
         {"id":"' . $gs6 . '","x":4,"y":8,"w":5,"h":12},
         {"id":"' . $gs7 . '","x":4,"y":0,"w":4,"h":8}]';
        }
        if ($id == self::$REQUEST_SUPERVISOR_VIEW) {
            $gs1 = Widget::getGsID(Alert::class . "7");
            $gs2 = Widget::getGsID(Reports_Pie::class . "17");
            $gs3 = Widget::getGsID(Reports_Bar::class . "1");
            $gs4 = Widget::getGsID(Alert::class . "1");
            $gs5 = Widget::getGsID(Alert::class . "2");

            $data = '[{"id":"' . $gs1 . '","x":4,"y":0,"w":5,"h":11},
         {"id":"' . $gs2 . '","x":0,"y":0,"w":4,"h":12},
         {"id":"' . $gs3 . '","x":4,"y":11,"w":5,"h":12},
         {"id":"' . $gs4 . '","x":9,"y":0,"w":3,"h":11},
         {"id":"' . $gs5 . '","x":9,"y":11,"w":3,"h":8}]';
        }
        if ($id == self::$HELPDESK_TECHNICIAN_VIEW) {
            $gs1  = Widget::getGsID(Alert::class . "4");
            $gs2  = Widget::getGsID(Alert::class . "7");
            $gs3  = Widget::getGsID(Alert::class . "5");
            $gs4  = Widget::getGsID("tickettaskstodowidget");
            $gs5  = Widget::getGsID("ticketlistprocesswidget");
            $gs6  = Widget::getGsID(Alert::class . "1");
            $gs7  = Widget::getGsID("tickettaskstodowidgetgroup");
            $gs8  = Widget::getGsID("ticketlistprocesswidgetgroup");
            $gs9  = Widget::getGsID(Alert::class . "2");
            $data = '[{"id":"' . $gs1 . '","x":0,"y":0,"w":4,"h":8},
         {"id":"' . $gs2 . '","x":4,"y":0,"w":4,"h":8},
         {"id":"' . $gs3 . '","x":8,"y":0,"w":4,"h":8},
         {"id":"' . $gs4 . '","x":0,"y":8,"w":4,"h":9},
         {"id":"' . $gs5 . '","x":4,"y":17,"w":4,"h":9},
         {"id":"' . $gs6 . '","x":8,"y":8,"w":4,"h":11},
         {"id":"' . $gs7 . '","x":4,"y":8,"w":4,"h":9},
         {"id":"' . $gs8 . '","x":0,"y":17,"w":4,"h":9},
         {"id":"' . $gs9 . '","x":8,"y":19,"w":4,"h":8}]';
        }

        return $data;
    }

    public static function install(Migration $migration)
    {
        global $DB;

        $default_charset   = DBConnection::getDefaultCharset();
        $default_collation = DBConnection::getDefaultCollation();
        $default_key_sign  = DBConnection::getDefaultPrimaryKeySignOption();
        $table  = self::getTable();

        if (!$DB->tableExists($table)) {
            $query = "CREATE TABLE `$table` (
                        `id` int {$default_key_sign} NOT NULL auto_increment,
                        `users_id`       int {$default_key_sign} NOT NULL               DEFAULT '0',
                        `grid`           longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                        `profiles_id`    int {$default_key_sign} NOT NULL               DEFAULT '0',
                        `grid_statesave` longtext  NULL  DEFAULT NULL,
                        PRIMARY KEY (`id`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);

        }

        if (!$DB->fieldExists($table, "grid_statesave")) {
            $migration->addField($table, "grid_statesave", "longtext NULL DEFAULT NULL");
            $migration->migrationOneTable($table);
        }

        $query      = "SELECT `id`, `grid` FROM `glpi_plugin_mydashboard_dashboards`";
        $result     = $DB->doQuery($query);

        while ($data = $DB->fetchArray($result)) {
            $id    = $data['id'];
            $grids = json_decode($data['grid'], true);
            $newwidgets = [];
            $newwidget  = [];
            foreach ($grids as $k => $widgets) {
                $newwidget['id'] = $widgets['id'];
                $newwidget['x']  = $widgets['x'];
                $newwidget['y']  = $widgets['y'];
                $newwidget['w']  = $widgets['width'] ?? $widgets['w'];
                $newwidget['h']  = $widgets['height'] ?? $widgets['h'];

                $newwidgets[] = $newwidget;
            }
            $newgrid      = json_encode($newwidgets);

            $DB->update(
                $table,
                ['grid' => $newgrid],
                ['id' => $id],
            );
        }

        $grid = new self();
        $grids = $grid->find();
        $allmapping = [];
        $grid_widgets = [];
        $grid_statesave_widgets = [];
        foreach ($grids as $grid_user) {
            if (!empty($grid_user['grid'])) {
                $grid_widgets = json_decode($grid_user['grid'], true);
            }
            if (!empty($grid_user['grid_statesave'])) {
                $grid_statesave_widgets = json_decode($grid_user['grid_statesave'], true);
            }

            $classes = [
                'PluginActivityDashboard' => 'GlpiPlugin\\\Activity\\\Dashboard',
                'PluginManageentitiesDashboard' => 'GlpiPlugin\\\Manageentities\\\Dashboard',
                'PluginEventsmanagerDashboard' => 'GlpiPlugin\\\Eventsmanager\\\Dashboard',
                'PluginOcsinventoryngDashboard' => 'GlpiPlugin\\\Ocsinventoryng\\\Dashboard',
                'PluginResourcesDashboard' => 'GlpiPlugin\\\Resources\\\Dashboard',
                'PluginSatisfactionDashboard' => 'GlpiPlugin\\\Satisfaction\\\Dashboard',
                'PluginServicecatalogIndicator' => 'GlpiPlugin\\\Servicecatalog\\\Indicator',
                'PluginTasklistsDashboard' => 'GlpiPlugin\\\Tasklists\\\Dashboard',
                'PluginVipDashboard' => 'GlpiPlugin\\\Vip\\\Dashboard'
            ];

            $grid_dest_widgets = $grid_widgets;
            $grid_statesave_dest_widgets = $grid_statesave_widgets;

            foreach ($grid_widgets as $grid_widget) {
                $id_origin = substr($grid_widget['id'], 2);
                $widget = new Widget();
                if ($widget->getFromDB($id_origin)) {

                    $result = preg_replace('/\d+$/', '', $widget->fields['name']);

                    if (preg_match('/^(.*?)(\d+)$/', $widget->fields['name'], $matches)) {
                        $baseName = $matches[1]; // sans les chiffres
                        $number = $matches[2]; // chiffres supprimés

                        if (isset($classes[$baseName])) {
                            $widget2 = new Widget();
                            $name_dest = stripslashes($classes[$baseName] . $number);

                            if ($widget2->getFromDBByCrit(['name' => $name_dest])) {
                                $id_dest = $widget2->fields['id'];

                                $mapping = [
                                    $id_origin => $id_dest,
                                ];
                                $allmapping[$id_origin] = $id_dest;

                                foreach ($grid_dest_widgets as &$grid_dest_widget) {
                                    if (preg_match('/^([a-zA-Z]+)(\d+)$/', $grid_dest_widget['id'], $m)) {
                                        $prefix = $m[1];           // gs
                                        $number = (int) $m[2];     // 142

                                        if (isset($mapping[$number])) {
                                            $grid_dest_widget['id'] = $prefix . $mapping[$number];

                                        }
                                    }
                                }
                                unset($grid_dest_widget);
                            }
                        }
                    }
                }
            }

            foreach ($grid_statesave_widgets as $k => $grid_statesave_widget) {
                $id_origin = substr($k, 2);
                $widget = new Widget();
                if ($widget->getFromDB($id_origin)) {

                    $result = preg_replace('/\d+$/', '', $widget->fields['name']);

                    if (preg_match('/^(.*?)(\d+)$/', $widget->fields['name'], $matches)) {
                        $baseName2 = $matches[1]; // sans les chiffres
                        $number2 = $matches[2]; // chiffres supprimés

                        if (isset($classes[$baseName2])) {
                            $widget2 = new Widget();
                            $name_dest = stripslashes($classes[$baseName2] . $number2);

                            if ($widget2->getFromDBByCrit(['name' => $name_dest])) {
                                $id_dest = $widget2->fields['id'];

                                $mapping2 = [
                                    $id_origin => $id_dest,
                                ];

                                foreach ($grid_statesave_dest_widgets as $key => $value) {

                                    // $key = gs177
                                    if (preg_match('/^([a-zA-Z]+)(\d+)$/', $key, $m)) {

                                        $prefix = $m[1];          // gs
                                        $number3 = (int) $m[2];    // 177

                                        // mapping : id_origin => id_dest
                                        if (isset($mapping2[$number3])) {

                                            $newKey = $prefix . $mapping2[$number3];

                                            // créer la nouvelle clé
                                            $grid_statesave_dest_widgets[$newKey] = $value;

                                            // supprimer l'ancienne
                                            unset($grid_statesave_dest_widgets[$key]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $ifg = $grid_user['id'];

            $grid->update(['id' => $ifg,
                'grid' => json_encode($grid_dest_widgets),
                'grid_statesave' => json_encode($grid_statesave_dest_widgets)]);

        }

        $widgetuser = new UserWidget();
        if ($ids = $widgetuser->find()) {
            foreach ($ids as $values) {
                if (isset($allmapping[$values['widgets_id']])) {
                    $widgetuser->update(['id' => $values['id'], 'widgets_id' => $allmapping[$values['widgets_id']]]);
                }
            }
        }

        $widgetprofile = new ProfileAuthorizedWidget();
        if ($ids = $widgetprofile->find()) {
            foreach ($ids as $values) {
                if (isset($allmapping[$values['widgets_id']])) {
                    $widgetprofile->update(['id' => $values['id'], 'widgets_id' => $allmapping[$values['widgets_id']]]);
                }
            }
        }
    }

    public static function uninstall()
    {
        global $DB;

        $DB->dropTable(self::getTable(), true);

    }
}
