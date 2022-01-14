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

/**
 * This class extends GLPI class contract to add the functions to display a widget on Dashboard
 */
class PluginMydashboardContract extends CommonGLPI {

   /**
    * @param int $nb
    *
    * @return string|\translated
    */
   static function getTypeName($nb = 0) {
      return __('Contract');
   }

   /**
    * @return array
    */
   function getWidgetsForItem() {

      $widgets = [];
      if (Session::haveRight("contract", READ)) {
         $widgets = [
            PluginMydashboardMenu::$GLOBAL_VIEW => [
               "contractwidget" => ["title"   => Contract::getTypeName(1),
                                    "icon"    => "ti ti-table",
                                    "comment" => ""],
            ]
         ];
      }
      return $widgets;
   }

   /**
    * @param $widgetId
    *
    * @return Nothing
    */
   function getWidgetContentForItem($widgetId) {
      switch ($widgetId) {
         case "contractwidget":
            return self::showCentral();
            break;
      }
   }

   /**
    * Show central contract resume
    * HTML array
    *
    * @return \PluginMydashboardDatatable (display)
    */
   static function showCentral() {
      global $DB, $CFG_GLPI;

      $dbu = new DbUtils();
      if (!Session::haveRight("contract", READ)) {
         return false;
      }

      // No recursive contract, not in local management
      // contrats echus depuis moins de 30j
      $query     = "SELECT COUNT(*)
                FROM `glpi_contracts`
                WHERE `glpi_contracts`.`is_deleted`='0' " .
                   $dbu->getEntitiesRestrictRequest("AND", "glpi_contracts") . "
                      AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                           `glpi_contracts`.`duration` MONTH),CURDATE() )>-30
                      AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                           `glpi_contracts`.`duration` MONTH),CURDATE() )<'0'";
      $result    = $DB->query($query);
      $contract0 = $DB->result($result, 0, 0);
      $dbu       = new DbUtils();
      // contrats  echeance j-7
      $query     = "SELECT COUNT(*)
                FROM `glpi_contracts`
                WHERE `glpi_contracts`.`is_deleted`='0' " .
                   $dbu->getEntitiesRestrictRequest("AND", "glpi_contracts") . "
                      AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                           `glpi_contracts`.`duration` MONTH),CURDATE() )>'0'
                      AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                           `glpi_contracts`.`duration` MONTH),CURDATE() )<='7'";
      $result    = $DB->query($query);
      $contract7 = $DB->result($result, 0, 0);

      // contrats echeance j -30
      $query      = "SELECT COUNT(*)
                FROM `glpi_contracts`
                WHERE `glpi_contracts`.`is_deleted`='0' " .
                    $dbu->getEntitiesRestrictRequest("AND", "glpi_contracts") . "
                      AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                           `glpi_contracts`.`duration` MONTH),CURDATE() )>'7'
                      AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                           `glpi_contracts`.`duration` MONTH),CURDATE() )<'30'";
      $result     = $DB->query($query);
      $contract30 = $DB->result($result, 0, 0);

      // contrats avec préavis echeance j-7
      $query        = "SELECT COUNT(*)
                FROM `glpi_contracts`
                WHERE `glpi_contracts`.`is_deleted`='0' " .
                      $dbu->getEntitiesRestrictRequest("AND", "glpi_contracts") . "
                      AND `glpi_contracts`.`notice`<>'0'
                      AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                           (`glpi_contracts`.`duration`-`glpi_contracts`.`notice`)
                                           MONTH),CURDATE() )>'0'
                      AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                           (`glpi_contracts`.`duration`-`glpi_contracts`.`notice`)
                                           MONTH),CURDATE() )<='7'";
      $result       = $DB->query($query);
      $contractpre7 = $DB->result($result, 0, 0);

      // contrats avec préavis echeance j -30
      $query         = "SELECT COUNT(*)
                FROM `glpi_contracts`
                WHERE `glpi_contracts`.`is_deleted`='0'" .
                       $dbu->getEntitiesRestrictRequest("AND", "glpi_contracts") . "
                      AND `glpi_contracts`.`notice`<>'0'
                      AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                           (`glpi_contracts`.`duration`-`glpi_contracts`.`notice`)
                                           MONTH),CURDATE() )>'7'
                      AND DATEDIFF(ADDDATE(`glpi_contracts`.`begin_date`, INTERVAL
                                           (`glpi_contracts`.`duration`-`glpi_contracts`.`notice`)
                                           MONTH),CURDATE() )<'30'";
      $result        = $DB->query($query);
      $contractpre30 = $DB->result($result, 0, 0);

      $widget = new PluginMydashboardDatatable();
      $widget->setWidgetId("contractwidget");
      $widget->setWidgetTitle("<a style=\"font-size:14px;\" href=\"" . $CFG_GLPI["root_doc"] . "/front/contract.php?reset=reset\">" .
                              Contract::getTypeName(2) . "</a>");

      $body = [];

      $options['reset'] = 'reset';
      $options['sort']  = 12;
      $options['order'] = 'DESC';
      $options['start'] = 0;

      $options['criteria'][0] = ['field'      => 12,
                                 'value'      => '<0',
                                 'searchtype' => 'contains'];
      $options['criteria'][1] = ['field'      => 12,
                                 'link'       => 'AND',
                                 'value'      => '>-30',
                                 'searchtype' => 'contains'];

      $body[]                          = ["<a href=\"" . $CFG_GLPI["root_doc"] . "/front/contract.php?" .
                                          Toolbox::append_params($options, '&amp;') . "\">" .
                                          __('Contracts expired in the last 30 days') . "</a>",
                                          $contract0
      ];
      $options['criteria'][0]['value'] = '>0';
      $options['criteria'][1]['value'] = '<7';
      $body[]                          = ["<a href=\"" . $CFG_GLPI["root_doc"] . "/front/contract.php?" .
                                          Toolbox::append_params($options, '&amp;') . "\">" .
                                          __('Contracts expiring in less than 7 days') . "</a>",
                                          $contract7
      ];

      $options['criteria'][0]['value'] = '>6';
      $options['criteria'][1]['value'] = '<30';
      $body[]                          = ["<a href=\"" . $CFG_GLPI["root_doc"] . "/front/contract.php?" .
                                          Toolbox::append_params($options, '&amp;') . "\">" .
                                          __('Contracts expiring in less than 30 days') . "</a>",
                                          $contract30
      ];
      $options['criteria'][0]['field'] = 13;
      $options['criteria'][0]['value'] = '>0';
      $options['criteria'][1]['field'] = 13;
      $options['criteria'][1]['value'] = '<7';
      $body[]                          = ["<a href=\"" . $CFG_GLPI["root_doc"] . "/front/contract.php?" .
                                          Toolbox::append_params($options, '&amp;') . "\">" .
                                          __('Contracts where notice begins in less than 7 days') . "</a>",
                                          $contractpre7
      ];

      $options['criteria'][0]['value'] = '>6';
      $options['criteria'][1]['value'] = '<30';
      $body[]                          = ["<a href=\"" . $CFG_GLPI["root_doc"] . "/front/contract.php?" .
                                          Toolbox::append_params($options, '&amp;') . "\">" .
                                          __('Contracts where notice begins in less than 30 days') . "</a>",
                                          $contractpre30
      ];

      $widget->setTabDatas($body);

      return $widget;
   }

}
