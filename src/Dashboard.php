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
use DbUtils;
use GlpiPlugin\Mydashboard\Reports\Reports_Bar;
use GlpiPlugin\Mydashboard\Reports\Reports_Line;
use GlpiPlugin\Mydashboard\Reports\Reports_Pie;
use GlpiPlugin\Mydashboard\Reports\Reports_Table;
use GlpiPlugin\Ocsinventoryng\Dashboard as OCSDashboard;
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
}
