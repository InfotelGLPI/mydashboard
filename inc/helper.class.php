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
 * This helper class provides some static functions that are useful for widget class
 */
class PluginMydashboardHelper {

   /**
    * get the delay between two automatic refreshing
    * @return int
    */
   static function getAutomaticRefreshDelay() {
      return self::getPreferenceField('automatic_refresh_delay');
   }

   /**
    * Check if automatic refreshing is enable or not
    * @return boolean, TRUE if automatic refresh is enabled, FALSE otherwise
    */
   static function getAutomaticRefresh() {
      return (self::getPreferenceField('automatic_refresh') == 1) ? true : false;
   }

   /**
    * Get the number of widgets in width in the configuration
    * @return int
    */
   static function getNumberOfWidgetsInWidth() {

      return self::getPreferenceField('nb_widgets_width');
   }

   /**
    * Check if user wants dashboard to replace central interface
    * @return boolean, TRUE if dashboard must replace, FALSE otherwise
    */
   static function getReplaceCentral() {
      return self::getPreferenceField("replace_central");
   }

   /**
    * @return mixed
    */
   static function getDisplayPlugins() {
      return self::getConfigField("display_plugin_widget");
   }

   /**
    * @return mixed
    */
   static function getDisplayMenu() {
      return self::getConfigField("display_menu");
   }

   /**
    * @return mixed
    */
   static function getReplaceCentralConf() {
      return self::getConfigField("replace_central");
   }

   /**
    * @return mixed
    */
   static function getGoogleApiKey() {
      return self::getConfigField("google_api_key");
   }

   /**
    * Get a specific field of the config
    *
    * @param string $fieldname
    *
    * @return mixed
    */
   private static function getConfigField($fieldname) {
      $config = new PluginMydashboardConfig();
      if (!$config->getFromDB(Session::getLoginUserID())) {
         $config->initConfig();
      }
      $config->getFromDB("1");

      return (isset($config->fields[$fieldname])) ? $config->fields[$fieldname] : 0;
   }

   /**
    * Get a specific field of the config
    *
    * @param string $fieldname
    *
    * @return mixed
    */
   private static function getPreferenceField($fieldname) {
      $preference = new PluginMydashboardPreference();
      if (!$preference->getFromDB(Session::getLoginUserID())) {
         $preference->initPreferences(Session::getLoginUserID());
      }
      $preference->getFromDB(Session::getLoginUserID());

      return (isset($preference->fields[$fieldname])) ? $preference->fields[$fieldname] : 0;
   }

   static function getGraphHeader($params) {

      $name  = $params['name'];
      $graph = "<div class='bt-row'>";
      if ($params["export"] == true) {
         $graph .= "<div class='bt-col-md-8 left'>";
      } else {
         $graph .= "<div class='bt-col-md-12 left'>";
      }
      if (count($params["criterias"]) > 0) {
         $graph .= self::getForm($params["widgetId"], $params["onsubmit"], $params["opt"], $params["criterias"]);
      }
      $graph .= "</div>";
      if ($params["export"] == true) {
         $graph .= "<div class='bt-col-md-2 center'>";
         $graph .= "<button class='btn btn-primary btn-sm' onclick='downloadGraph(\"$name\");'>PNG</button>";
         $graph .= "<button class='btn btn-primary btn-sm' style=\"margin-left: 1px;\" id=\"downloadCSV$name\">CSV</button>";
         $graph .= "<script>
         $(document).ready(
               function () {
                document.getElementById(\"downloadCSV$name\").addEventListener(\"click\", function(){
                    downloadCSV({ filename: \"chart-data.csv\", chart: $name })
                  });
                   
                   function convertChartDataToCSV(args,labels, nbIterations) {  
                       
                       var result, ctr, keys, columnDelimiter, lineDelimiter, data;
                     
                       data = args.data.data || null;
                       if (data == null || !data.length) {
                         return null;
                       }

                       columnDelimiter = args.columnDelimiter || \";\";
                       lineDelimiter = args.lineDelimiter || '\\n';
                       result = '';     
                       if(nbIterations == 0){
                           
                          labels.forEach(function(label) {
                            result += columnDelimiter;
                            result += label;
                          });
                       }
                       keys = Object.keys(data);
                       result += lineDelimiter;
                       result += args.data.label;
                       result += columnDelimiter;
                       data.forEach(function(item) {
                          if (typeof item != 'undefined') {
                                 result += item;
                          }
                           ctr++;
                         result += columnDelimiter;
                       });
                       return result;
                     }
                     
                     function downloadCSV(args) {
                       var data, filename, link;
                       var csv = \"\";
                       
                       for(var i = 0; i < args.chart.chart.data.datasets.length; i++){
                         csv += convertChartDataToCSV({
                           data: args.chart.chart.data.datasets[i]
                         }, args.chart.chart.data.labels, i);
                       }
                       if (csv == null) return;
                     
                       filename = args.filename || 'chart-data.csv';
                     
                       if (!csv.match(/^data:text\/csv/i)) {
                         var universalBOM = '\uFEFF';
                         csv = 'data:text/csv;charset=utf-8,' + encodeURIComponent(universalBOM+csv);
                       }
                       link = document.createElement('a');
                       link.setAttribute('href', csv);
                       link.setAttribute('download', filename);
                       document.body.appendChild(link); // Required for FF
                       link.click(); 
                       document.body.removeChild(link);
                     }
         });</script>";
         $graph .= "<a href='#' id='download'></a>";
         $graph .= "</div>";
      }
      $graph .= "</div>";
      if ($params["canvas"] == true) {
         if ($params["nb"] < 1) {
            $graph .= "<div align='center'><br><br><h3><span class ='maint-color'>";
            $graph .= __("No item found");
            $graph .= "</span></h3></div>";
         }
         $graph .= "<div id=\"chart-container\" class=\"chart-container\">"; // style="position: relative; height:45vh; width:45vw"
         $graph .= "<canvas id=\"$name\"></canvas>";
         $graph .= "</div>";
      }


      return $graph;
   }


   static function getGraphFooter($params) {

      $graph = "<div class='bt-row'>";
      $graph .= "<div class='bt-col-md-12 left'>";
      if (isset($params["setup"]) && Session::haveRightsOr("plugin_mydashboard_stockwidget", [CREATE, UPDATE])) {
         $graph .= "<a target='_blank' href='" . $params["setup"] . "'><i class=\"fas fa-wrench fa-1x\"></i></a>";
      }
      $graph .= "</div>";
      $graph .= "</div>";


      return $graph;
   }

   /**
    * @param $table
    * @param $params
    *
    * @return string
    */
   static function getSpecificEntityRestrict($table, $params) {

      if (isset($params['entities_id']) && $params['entities_id'] == "") {
         $params['entities_id'] = $_SESSION['glpiactive_entity'];
      }
      if (isset($params['entities_id']) && ($params['entities_id'] != -1)) {
         if (isset($params['sons']) && ($params['sons'] != "") && ($params['sons'] != 0)) {
            $entities = " AND `$table`.`entities_id` IN  (" . implode(",", getSonsOf("glpi_entities", $params['entities_id'])) . ") ";
         } else {
            $entities = " AND `$table`.`entities_id` = " . $params['entities_id'] . " ";
         }
      } else {
         if (isset($params['sons']) && ($params['sons'] != "") && ($params['sons'] != 0)) {
            $entities = " AND `$table`.`entities_id` IN  (" . implode(",", getSonsOf("glpi_entities", $_SESSION['glpiactive_entity'])) . ") ";
         } else {
            $entities = " AND `$table`.`entities_id` = " . $_SESSION['glpiactive_entity'] . " ";
         }
      }
      return $entities;
   }

   /**
    * @param $params
    *
    * @return mixed
    */
   static function manageCriterias($params) {
      global $CFG_GLPI;
      $criterias = $params['criterias'];


      // ENTITY | SONS
      if (Session::isMultiEntitiesMode()) {

         $opt['entities_id'] = $_SESSION['glpiactive_entity'];
         if (in_array("entities_id", $criterias)) {
            if (isset($params['preferences']['prefered_entity'])
                && $params['preferences']['prefered_entity'] > 0
                && count($params['opt']) < 1) {
               $opt['entities_id'] = $params['preferences']['prefered_entity'];
            } elseif (isset($params['opt']['entities_id'])
                      && $params['opt']['entities_id'] > 0) {
               $opt['entities_id'] = $params['opt']['entities_id'];
            } else {
               $opt['entities_id'] = $_SESSION['glpiactive_entity'];
            }
         }
         $opt['sons']          = 0;
         $crit['crit']['sons'] = 0;
         if (in_array("is_recursive", $criterias)) {
            if (!isset($params['opt']['sons'])) {
               //TODO : Add conf for recursivity
               if (isset($_SESSION['glpiactive_entity_recursive']) && $_SESSION['glpiactive_entity_recursive'] != false) {
                  $opt['sons'] = $_SESSION['glpiactive_entity_recursive'];
               } else {
                  $opt['sons'] = 0;
               }
            } else {
               $opt['sons'] = $params['opt']['sons'];
            }
            $crit['crit']['sons'] = $opt['sons'];
         }

         if (isset($opt)) {
            $crit['crit']['entities_id'] = self::getSpecificEntityRestrict("glpi_tickets", $opt);
            $crit['crit']['entity']      = $opt['entities_id'];
         }
      } else {
         $crit['crit']['entities_id'] = '';
         $crit['crit']['entity']      = 0;
         $crit['crit']['sons']        = 0;
      }

      // REQUESTER GROUP
      $opt['requesters_groups_id']          = [];
      $crit['crit']['requesters_groups_id'] = "AND 1 = 1";
      //      $opt['ancestors']                     = 0;
      //      $crit['crit']['ancestors']            = 0;
      if (in_array("requesters_groups_id", $criterias)) {
         if (isset($params['opt']['requesters_groups_id'])) {
            $opt['requesters_groups_id'] = $params['opt']['requesters_groups_id'];
         } else if($_SERVER["REQUEST_URI"]== $CFG_GLPI['root_doc']."/plugins/mydashboard/front/menu.php"){
            $groups_id                   = self::getRequesterGroup($params['preferences']['requester_prefered_group'], $opt, $params, $_SESSION['glpiactive_entity'], Session::getLoginUserID());
            $opt['requesters_groups_id'] = $groups_id;
         }else{
            $opt['requesters_groups_id'] = [];
         }

         $params['opt']['requesters_groups_id'] = $opt['requesters_groups_id'];

         $params['opt']['requesters_groups_id'] = is_array($params['opt']['requesters_groups_id']) ? $params['opt']['requesters_groups_id'] : [$params['opt']['requesters_groups_id']];

         if (isset($params['opt']['requesters_groups_id'])
             && is_array($params['opt']['requesters_groups_id'])
             && count($params['opt']['requesters_groups_id']) > 0) {
            //            if (in_array("group_is_recursive", $criterias) && isset($params['opt']['ancestors']) && $params['opt']['ancestors'] != 0) {
            //               $dbu    = new DbUtils();
            //               $childs = [];
            //
            //               foreach ($opt['requesters_groups_id'] as $k => $v) {
            //                  $childs = $dbu->getSonsAndAncestorsOf('glpi_groups', $v);
            //               }
            //
            //               $crit['crit']['requesters_groups_id'] = " AND `glpi_tickets`.`id` IN (SELECT `tickets_id` AS id FROM `glpi_groups_tickets`
            //            WHERE `type` = " . CommonITILActor::REQUESTER . " AND `groups_id` IN (" . implode(",", $childs) . "))";
            //               $opt['ancestors']                     = $params['opt']['ancestors'];
            //               $crit['crit']['ancestors']            = $opt['ancestors'];
            //            } else {
            $crit['crit']['requesters_groups_id'] = " AND `glpi_tickets`.`id` IN (SELECT `tickets_id` AS id FROM `glpi_groups_tickets`
            WHERE `type` = " . CommonITILActor::REQUESTER . " AND `groups_id` IN (" . implode(",", $params['opt']['requesters_groups_id']) . "))";
            //            $opt['ancestors']                     = 0;
            //            $crit['crit']['ancestors']            = 0;
            //            }
         }
      }

      // TECH GROUP
      $opt['technicians_groups_id']          = [];
      $crit['crit']['technicians_groups_id'] = "AND 1 = 1";
      $opt['ancestors']                      = 0;
      $crit['crit']['ancestors']             = 0;
      if (in_array("technicians_groups_id", $criterias)) {
         if (isset($params['opt']['technicians_groups_id'])) {
            $opt['technicians_groups_id'] = is_array($params['opt']['technicians_groups_id'])?$params['opt']['technicians_groups_id']:[$params['opt']['technicians_groups_id']];
         } else if($_SERVER["REQUEST_URI"] == $CFG_GLPI['root_doc']."/plugins/mydashboard/front/menu.php"){
            $groups_id                    = self::getGroup($params['preferences']['prefered_group'], $opt, $params);
            $opt['technicians_groups_id'] = $groups_id;
         }else {
            $opt['technicians_groups_id'] = [];
         }
         $params['opt']['technicians_groups_id'] = $opt['technicians_groups_id'];
         if (isset($params['opt']['technicians_groups_id'])
             && is_array($params['opt']['technicians_groups_id'])
             && count($params['opt']['technicians_groups_id']) > 0) {
            $none = false;
            if($params['opt']['technicians_groups_id'][0]=="0"){
               $none = true;
            }
            if (in_array("group_is_recursive", $criterias) && isset($params['opt']['ancestors']) && $params['opt']['ancestors'] != 0) {
               $dbu    = new DbUtils();
               $childs = [];
               foreach ($opt['technicians_groups_id'] as $k => $v) {
                  $childs = $dbu->getSonsAndAncestorsOf('glpi_groups', $v);
               }
               if($none){
                  $crit['crit']['technicians_groups_id'] = " AND ( `glpi_tickets`.`id` NOT IN (SELECT `tickets_id` AS id FROM `glpi_groups_tickets`) ";
                  $crit['crit']['technicians_groups_id'] .= " OR `glpi_tickets`.`id` IN (SELECT `tickets_id` AS id FROM `glpi_groups_tickets`
            WHERE `type` = " . CommonITILActor::ASSIGN . " AND `groups_id` IN (" . implode(",", $childs) . ")))";
               }else{
                  $crit['crit']['technicians_groups_id'] .= " AND `glpi_tickets`.`id` IN (SELECT `tickets_id` AS id FROM `glpi_groups_tickets`
            WHERE `type` = " . CommonITILActor::ASSIGN . " AND `groups_id` IN (" . implode(",", $childs) . "))";
               }
               $opt['ancestors']                      = $params['opt']['ancestors'];
               $crit['crit']['ancestors']             = $opt['ancestors'];
            } else {
               if($none){
                  $crit['crit']['technicians_groups_id'] = " AND ( `glpi_tickets`.`id` NOT IN (SELECT `tickets_id` AS id FROM `glpi_groups_tickets`) ";
                  $crit['crit']['technicians_groups_id'] .= " OR `glpi_tickets`.`id` IN (SELECT `tickets_id` AS id FROM `glpi_groups_tickets`
            WHERE `type` = " . CommonITILActor::ASSIGN . " AND `groups_id` IN (" . implode(",", $params['opt']['technicians_groups_id']) . ")))";
               }else{
                  $crit['crit']['technicians_groups_id'] .= " AND `glpi_tickets`.`id` IN (SELECT `tickets_id` AS id FROM `glpi_groups_tickets`
            WHERE `type` = " . CommonITILActor::ASSIGN . " AND `groups_id` IN (" . implode(",", $params['opt']['technicians_groups_id']) . "))";
               }
               $opt['ancestors']                      = 0;
               $crit['crit']['ancestors']             = 0;
            }
         }
      }

      //LOCATION
      $opt['locations_id']          = 0;
      $crit['crit']['locations_id'] = "AND 1 = 1";
      $user                         = new User();
      if (in_array("locations_id", $criterias)) {
         if (isset($params['opt']["locations_id"])
             && $params['opt']["locations_id"] > 0) {
            $opt['locations_id']          = $params['opt']['locations_id'];
            $crit['crit']['locations_id'] = " AND `glpi_tickets`.`locations_id` = '" . $params['opt']["locations_id"] . "' ";
         } else if (isset($_SESSION['glpiactiveprofile']['interface'])
                    && Session::getCurrentInterface() != 'central' && $user->getFromDB(Session::getLoginUserID())) {
            $opt['locations_id']          = $user->fields['locations_id'];
            $crit['crit']['locations_id'] = " AND `glpi_tickets`.`locations_id` = '" . $opt["locations_id"] . "' ";
         }
      }

      //TYPE
      $opt['type']          = 0;
      $crit['crit']['type'] = "AND 1 = 1";
      if (in_array("type", $criterias)) {
         if (isset($params['opt']["type"])
             && $params['opt']["type"] > 0) {
            $opt['type']          = $params['opt']['type'];
            $crit['crit']['type'] = " AND `glpi_tickets`.`type` = '" . $params['opt']["type"] . "' ";
         }
      }

      // DATE
      // MONTH
      $year                 = intval(strftime("%Y"));
      $month                = intval(strftime("%m") - 1);
      $crit['crit']['year'] = $year;
      if (in_array("month", $criterias)) {
         if ($month > 0) {
            $year        = strftime("%Y");
            $opt["year"] = $year;
         } else {
            $month = 12;
         }
         if (isset($params['opt']["month"])
             && $params['opt']["month"] > 0) {
            $month        = $params['opt']["month"];
            $opt['month'] = $params['opt']['month'];
         } else {
            $opt["month"] = $month;
         }
      }

      // YEAR
      if (in_array("year", $criterias)) {
         if (isset($params['opt']["year"])
             && $params['opt']["year"] > 0) {
            $year        = $params['opt']["year"];
            $opt['year'] = $params['opt']['year'];
         } else {
            $opt["year"] = $year;
         }
         $crit['crit']['year'] = $opt['year'];
      }

      // BEGIN DATE
      if (in_array("begin", $criterias)) {
         if (isset($params['opt']['begin'])
             && $params['opt']["begin"] > 0) {
            $opt["begin"]          = $params['opt']['begin'];
            $crit['crit']['begin'] = $params['opt']['begin'];
         } else {
            $opt["begin"] = date("Y-m-d H:i:s");
         }
      }

      // END DATE
      if (in_array("end", $criterias)) {
         if (isset($params['opt']['end'])
             && $params['opt']["end"] > 0) {
            $opt["end"]          = $params['opt']['end'];
            $crit['crit']['end'] = $params['opt']['end'];
         } else {
            $opt["end"] = date("Y-m-d H:i:s");
         }
      }

      $nbdays                    = date("t", mktime(0, 0, 0, $month, 1, $year));
      $crit['crit']['date']      = "(`glpi_tickets`.`date` >= '$year-$month-01 00:00:01' 
                              AND `glpi_tickets`.`date` <= ADDDATE('$year-$month-$nbdays 00:00:00' , INTERVAL 1 DAY) )";
      $crit['crit']['closedate'] = "(`glpi_tickets`.`closedate` >= '$year-$month-01 00:00:01' 
                              AND `glpi_tickets`.`closedate` <= ADDDATE('$year-$month-$nbdays 00:00:00' , INTERVAL 1 DAY) )";

      if (!in_array("month", $criterias)) {
         $crit['crit']['date']      = "(`glpi_tickets`.`date` >= '$year-01-01 00:00:01' 
                              AND `glpi_tickets`.`date` <= ADDDATE('$year-12-31 00:00:00' , INTERVAL 1 DAY) )";
         $crit['crit']['closedate'] = "(`glpi_tickets`.`closedate` >= '$year-01-01 00:00:01' 
                              AND `glpi_tickets`.`closedate` <= ADDDATE('$year-12-31 00:00:00' , INTERVAL 1 DAY) )";
      }
      // USER
      //      $opt["users_id"] = $_SESSION['glpiID'];
      if (in_array("users_id", $criterias)) {
         if (isset($params['opt']['users_id'])) {
            $opt["users_id"]          = $params['opt']['users_id'];
            $crit['crit']['users_id'] = $params['opt']['users_id'];
         }
      }

      // TECHNICIAN
      $opt["technicians_id"] = 0;
      if (in_array("technicians_id", $criterias)) {
         if (isset($params['opt']['technicians_id'])) {
            $opt["technicians_id"]          = $params['opt']['technicians_id'];
            $crit['crit']['technicians_id'] = $params['opt']['technicians_id'];
         }
      }

      // LIMIT
      if (in_array("limit", $criterias)) {
         if (isset($params['opt']['limit'])) {
            $opt["limit"]          = $params['opt']['limit'];
            $crit['crit']['limit'] = $params['opt']['limit'];
         }
      }

      // STATUS
      $default                = [CommonITILObject::INCOMING,
                                      CommonITILObject::ASSIGNED,
                                      CommonITILObject::PLANNED,
                                      CommonITILObject::WAITING];
      $crit['crit']['status'] = $default;
      $opt['status']          = $default;
      if (in_array("status", $criterias)) {
         $status = [];

         if (isset($params['opt']["status_1"])
             && $params['opt']["status_1"] > 0) {
            $status[] = CommonITILObject::INCOMING;
         }
         if (isset($params['opt']["status_2"])
             && $params['opt']["status_2"] > 0) {
            $status[] = CommonITILObject::ASSIGNED;
         }
         if (isset($params['opt']["status_3"])
             && $params['opt']["status_3"] > 0) {
            $status[] = CommonITILObject::PLANNED;
         }
         if (isset($params['opt']["status_4"])
             && $params['opt']["status_4"] > 0) {
            $status[] = CommonITILObject::WAITING;
         }
         if (isset($params['opt']["status_5"])
             && $params['opt']["status_5"] > 0) {
            $status[] = CommonITILObject::SOLVED;
         }
         if (isset($params['opt']["status_6"])
             && $params['opt']["status_6"] > 0) {
            $status[] = CommonITILObject::CLOSED;
         }

         if (count($status) > 0) {
            $opt['status']          = $status;
            $crit['crit']['status'] = $status;
         }
      }
      $crit['opt'] = $opt;

      return $crit;
   }


   /**
    * Get a form header, this form header permit to update data of the widget
    * with parameters of this form
    *
    * @param int  $widgetId
    * @param      $gsid
    * @param bool $onsubmit
    *
    * @return string , like '<form id=...>'
    */
   static function getFormHeader($widgetId, $gsid, $onsubmit = false, $opt = []) {
      $formId = uniqid('form');
      $rand   = mt_rand();
      $form   = "<script type='text/javascript'>
               $(document).ready(function () {
                   $('#plugin_mydashboard_add_criteria$rand').on('click', function (e) {
                       $('#plugin_mydashboard_see_criteria$rand').width(300);
                       $('#plugin_mydashboard_see_criteria$rand').toggle();
                   });
                 });
                </script>";

      $form   .= "<div id='plugin_mydashboard_add_criteria$rand'><i class=\"fas fa-bars md-fa-2x\"></i>";
      $form   .= "<span style='font-size: 12px;font-family: verdana;color: #CCC;font-weight: bold;'>";
      $entity = new Entity();
      if (isset($opt['entities_id']) && $opt['entities_id'] > -1) {
         if ($entity->getFromDB($opt['entities_id'])) {
            $form .= "&nbsp;" . __('Entity') . "&nbsp;:&nbsp;" . $entity->getField('name');
         }
      } else {
         if ($entity->getFromDB($_SESSION["glpiactive_entity"])) {
            $form .= "&nbsp;" . __('Entity') . "&nbsp;:&nbsp;" . $entity->getField('name');
         }
      }

      if (isset($opt['locations_id']) && $opt['locations_id'] > 0) {
         $form .= "&nbsp;/&nbsp;" . __('Location') . "&nbsp;:&nbsp;" . Dropdown::getDropdownName('glpi_locations', $opt['locations_id']);
      }


      if (isset($opt['technicians_groups_id'])) {
         $opt['technicians_groups_id'] = is_array($opt['technicians_groups_id']) ? $opt['technicians_groups_id'] : [$opt['technicians_groups_id']];
         if (count($opt['technicians_groups_id']) > 0) {
            $form .= "&nbsp;/&nbsp;" . __('Technician group') . "&nbsp;:&nbsp;";
            foreach ($opt['technicians_groups_id'] as $k => $v) {
               $form .= Dropdown::getDropdownName('glpi_groups', $v);
               if (count($opt['technicians_groups_id']) > 1) {
                  $form .= "&nbsp;-&nbsp;";
               }
            }
         }
      }

      if (isset($opt['requesters_groups_id'])) {
         $opt['requesters_groups_id'] = is_array($opt['requesters_groups_id']) ? $opt['requesters_groups_id'] : [$opt['requesters_groups_id']];
         if (count($opt['requesters_groups_id']) > 0) {
            $form .= "&nbsp;/&nbsp;" . _n('Requester group', 'Requester groups', count($opt['requesters_groups_id'])) . "&nbsp;:&nbsp;";
            foreach ($opt['requesters_groups_id'] as $k => $v) {
               $form .= Dropdown::getDropdownName('glpi_groups', $v);
               if (count($opt['requesters_groups_id']) > 1) {
                  $form .= "&nbsp;-&nbsp;";
               }
            }
         }
      }

      if (isset($opt['type']) && $opt['type'] > 0) {
         $form .= "&nbsp;/&nbsp;" . __('Type') . "&nbsp;:&nbsp;" . Ticket::getTicketTypeName($opt['type']);
      }

      if (isset($opt['year']) && isset($opt['month'])) {
         $monthsarray = Toolbox::getMonthsOfYearArray();
         $form        .= "&nbsp;/&nbsp;" . __('Date') . "&nbsp;:&nbsp;" . sprintf(__('%1$s %2$s'), $monthsarray[$opt['month']], $opt['year']);
      }
      $form .= "</span>";
      $form .= "</div>";
      $form .= "<div class='plugin_mydashboard_menuWidget' id='plugin_mydashboard_see_criteria$rand'>";
      if ($onsubmit) {
         $form .= "<form id='" . $formId . "' action='' "
                  . "onsubmit=\"refreshWidgetByForm('" . $widgetId . "','" . $gsid . "','" . $formId . "'); return false;\">";
      } else {
         $form .= "<form id='" . $formId . "' action='' onsubmit='return false;' ";
         $form .= "onchange=\"refreshWidgetByForm('" . $widgetId . "','" . $gsid . "','" . $formId . "');\">";
      }
      return $form;
   }

   static function getForm($widgetId, $onsubmit = false, $opt, $criterias) {

      $gsid = PluginMydashboardWidget::getGsID($widgetId);

      $form = self::getFormHeader($widgetId, $gsid, $onsubmit, $opt);

      $count = count($criterias);

      // ENTITY | SONS
      if (Session::isMultiEntitiesMode()) {
         if (in_array("entities_id", $criterias)) {
            $form   .= "<span class='md-widgetcrit'>";
            $params = ['name'                => 'entities_id',
                       'display'             => false,
                       'width'               => '100px',
                       'value'               => isset($opt['entities_id']) ? $opt['entities_id'] : $_SESSION['glpiactive_entity'],
                       'display_emptychoice' => true

            ];
            $form   .= __('Entity');
            $form   .= "&nbsp;";
            $form   .= Entity::dropdown($params);
            $form   .= "</span>";
            if ($count > 1) {
               $form .= "</br></br>";
            }
         }
         if (in_array("is_recursive", $criterias)) {
            $form    .= "<span class='md-widgetcrit'>";
            $form    .= __('Recursive') . "&nbsp;";
            $paramsy = [
               'display' => false];
            $sons    = isset($opt['sons']) ? $opt['sons'] : 0;
            $form    .= Dropdown::showYesNo('sons', $sons, -1, $paramsy);
            $form    .= "</span>";
            if ($count > 1) {
               $form .= "</br></br>";
            }

         }
      }
      // LOCATION
      if (in_array("locations_id", $criterias)) {
         $user             = new User();
         $default_location = 0;
         if (isset($_SESSION['glpiactiveprofile']['interface'])
             && Session::getCurrentInterface() != 'central' && $user->getFromDB(Session::getLoginUserID())) {
            $default_location = $user->fields['locations_id'];
         }
         $gparams = ['name'    => 'locations_id',
                     'display' => false,
                     'value'   => isset($opt['locations_id']) ? $opt['locations_id'] : $default_location,
                     'entity'  => $_SESSION['glpiactiveentities'],
         ];
         $form    .= "<span class='md-widgetcrit'>";
         $form    .= __('Location');
         $form    .= "&nbsp;";
         $form    .= Location::dropdown($gparams);
         $form    .= "</span>";
         if ($count > 1) {
            $form .= "</br></br>";
         }
      }
      // REQUESTER GROUPS
      if (in_array("requesters_groups_id", $criterias)) {
         $form .= "<span class='md-widgetcrit'>";

         $dbu    = new DbUtils();
         $result = $dbu->getAllDataFromTable(Group::getTable(), ['is_requester' => 1]);

         if (isset($opt['requesters_groups_id'])) {
            $requesters_groups_id = (is_array($opt['requesters_groups_id']) ? $opt['requesters_groups_id'] : [$opt['requesters_groups_id']]);
         } else {
            $requesters_groups_id = [];
         }

         $temp = [];
         foreach ($result as $item) {
            $temp[$item['id']] = $item['name'];
         }

         $params = [
            "name"                => 'requesters_groups_id',
            "display"             => false,
            "multiple"            => true,
            "width"               => '200px',
            'values'              => $requesters_groups_id,
            'display_emptychoice' => true
         ];

         $form .= __('Requester group');
         $form .= "&nbsp;";

         $dropdown = Dropdown::showFromArray("requesters_groups_id", $temp, $params);

         $form .= $dropdown;

         $form .= "</span>";
         if ($count > 1) {
            $form .= "</br></br>";
         }
      }

      // TECHNICIAN GROUPS
      if (in_array("technicians_groups_id", $criterias)) {
         $form .= "<span class='md-widgetcrit'>";

         $dbu    = new DbUtils();
         $result = $dbu->getAllDataFromTable(Group::getTable(), ['is_assign' => 1]);

         if (isset($opt['technicians_groups_id'])) {
            $technicians_groups_id = (is_array($opt['technicians_groups_id']) ? $opt['technicians_groups_id'] : [$opt['technicians_groups_id']]);
         } else {
            $technicians_groups_id = [];
         }

         $temp = [];
         foreach ($result as $item) {
            $temp[$item['id']] = $item['name'];
         }

         $params = [
            "name"                => 'technicians_groups_id',
            "display"             => false,
            "multiple"            => true,
            "width"               => '200px',
            'values'              => $technicians_groups_id,
            'display_emptychoice' => true
         ];

         $form .= __('Technician group');
         $form .= "&nbsp;";

         $dropdown = Dropdown::showFromArray("technicians_groups_id", $temp, $params);

         $form .= $dropdown;

         $form .= "</span>";
         if ($count > 1) {
            $form .= "</br></br>";
         }
      }

      if (in_array("group_is_recursive", $criterias)) {
         $form      .= "<span class='md-widgetcrit'>";
         $form      .= __('Child groups') . "&nbsp;";
         $paramsy   = ['display' => false];
         $ancestors = isset($opt['ancestors']) ? $opt['ancestors'] : 0;
         $form      .= Dropdown::showYesNo('ancestors', $ancestors, -1, $paramsy);
         $form      .= "</span>";
         if ($count > 1) {
            $form .= "</br></br>";
         }
      }

      // TYPE
      if (in_array("type", $criterias)) {
         $form .= "<span class='md-widgetcrit'>";
         $type = 0;
         if (isset($opt["type"])
             && $opt["type"] > 0) {
            $type = $opt["type"];
         }
         $form .= __('Type');
         $form .= "&nbsp;";
         $form .= Ticket::dropdownType('type', ['value'               => $type,
                                                'display'             => false,
                                                'display_emptychoice' => true]);
         $form .= "</span>";
         if ($count > 1) {
            $form .= "</br></br>";
         }
      }
      // DATE
      // YEAR
      if (in_array("year", $criterias)) {
         $form           .= "<span class='md-widgetcrit'>";
         $annee_courante = strftime("%Y");
         if (isset($opt["year"])
             && $opt["year"] > 0) {
            $annee_courante = $opt["year"];
         }
         $form .= __('Year', 'mydashboard');
         $form .= "&nbsp;";
         $form .= self::YearDropdown($annee_courante);
         $form .= "</span>";
         if ($count > 1) {
            $form .= "</br></br>";
         }
      }
      // MONTH
      if (in_array("month", $criterias)) {
         $form .= __('Month', 'mydashboard');
         $form .= "&nbsp;";
         $form .= self::monthDropdown("month", (isset($opt['month']) ? $opt['month'] : 0));
         $form .= "&nbsp;";
         if ($count > 1) {
            $form .= "</br></br>";
         }
      }
      // START DATE
      if (in_array("begin", $criterias)) {
         $form .= __('Start');
         $form .= "&nbsp;";
         $form .= Html::showDateTimeField("begin", ['value' => isset($opt['begin']) ? $opt['begin'] : null, 'maybeempty' => false, 'display' => false]);
         $form .= "&nbsp;";
         if ($count > 1 && !in_array("end", $criterias)) {
            $form .= "</br></br>";
         } elseif ($count > 1 && in_array("end", $criterias)) {
            $form .= "</br>";
         }
      }
      // END DATE
      if (in_array("end", $criterias)) {
         $form .= __('End');
         $form .= "&nbsp;";
         $form .= Html::showDateTimeField("end", ['value' => isset($opt['end']) ? $opt['end'] : null, 'maybeempty' => false, 'display' => false]);
         $form .= "&nbsp;";
         if ($count > 1) {
            $form .= "</br></br>";
         }
      }

      // USER
      if (in_array("users_id", $criterias)) {
         $params = ['name'     => "users_id",
                         'value'    => isset($opt['users_id']) ? $opt['users_id'] : null,
                         'right'    => "interface",
                         'comments' => 1,
                         'entity'   => $_SESSION["glpiactiveentities"],
                         'width'    => '50%',
                         'display'  => false
         ];
         $form   .= __('User');
         $form   .= "&nbsp;";
         $form   .= User::dropdown($params);
         if ($count > 1) {
            $form .= "</br></br>";
         }
      }
      // TECHNICIAN
      if (in_array("technicians_id", $criterias)) {
         $params = ['name'     => "technicians_id",
                         'value'    => isset($opt['technicians_id']) ? $opt['technicians_id'] : null,
                         'right'    => "interface",
                         'comments' => 1,
                         'entity'   => $_SESSION["glpiactiveentities"],
                         'width'    => '50%',
                         'display'  => false
      ];
         $form   .= __('Technician');
         $form   .= "&nbsp;";
         $form   .= User::dropdown($params);
         if ($count > 1) {
            $form .= "</br></br>";
         }
      }

      // LIMIT
      if (in_array("limit", $criterias)) {
         $params = ['value' => isset($opt['limit']) ? $opt['limit'] : 0,
                    'min'   => 0,
                    'max'   => 200,
                    'step'  => 1,
                    'display'  => false,
                    'toadd' => [0 => __('All')]];
         $form   .= __('Number of results');
         $form   .= "&nbsp;";
         $form   .= Dropdown::showNumber("limit", $params);
         if ($count > 1) {
            $form .= "</br></br>";
         }
      }
      //STATUS
      if (in_array("status", $criterias)) {
         $form    .= _n('Status', 'Statuses', 2) . "&nbsp;";
         $default = [CommonITILObject::INCOMING,
                          CommonITILObject::ASSIGNED,
                          CommonITILObject::PLANNED,
                          CommonITILObject::WAITING];

         $i = 1;
         foreach (Ticket::getAllStatusArray() as $svalue => $sname) {
            $form .= '<input type="hidden" name="status_' . $svalue . '" value="0" /> ';
            $form .= '<input type="checkbox" name="status_' . $svalue . '" value="1"';

            if (in_array($svalue, $opt['status'])) {
               $form .= ' checked="checked"';
            }
            if (count($opt['status']) < 1 && in_array($svalue, $default)) {
               $form .= ' checked="checked"';
            }

            $form .= ' /> ';
            $form .= $sname;
            if ($i % 2 == 0) {
               $form .= "<br>";
            } else {
               $form .= "&nbsp;";
            }
            $i++;
         }
         if ($count > 1) {
            $form .= "</br></br>";
         }
      }

      if ($onsubmit) {
         $form .= "<input type='submit' class='submit' value='" . _x('button', 'Send') . "'>";
      }

      return $form . self::getFormFooter();
   }

   static function getFormFooter() {

      $form = "</form>";
      $form .= "</div>";

      return $form;
   }

   /**
    * Get a link to be used as a widget title
    *
    * @param        $pathfromrootdoc
    * @param        $text
    * @param string $title
    *
    * @return string
    */
   static function getATag($pathfromrootdoc, $text, $title = "") {
      global $CFG_GLPI;
      $title = ($title !== "") ? "title=$title" : "";
      return "<a href='" . $CFG_GLPI['root_doc'] . "/" . $pathfromrootdoc . "' $title target='_blank'>" . $text . "</a>";
   }

   /**
    * Return an unique id for a widget,
    * (can only be used on temporary plugins,
    *  because the id must represent the widget
    *  and every once this function is called it generates a new id)
    *
    * @return string
    */
   static function getUniqueWidgetId() {
      return uniqid("id_");
   }

   /**
    * Extract the content of the HTML script tag in an array 2D (line, column),
    * Useful for datatables
    *
    * @param array 2D $arrayToEval
    *
    * @return array of string (each string is a script line)
    */
   static function extractScriptsFromArray($arrayToEval) {
      $scripts = [];
      if (is_array($arrayToEval)) {
         if (!is_array($arrayToEval)) {
            return $scripts;
         }
         foreach ($arrayToEval as $array) {
            if (!is_array($array)) {
               break;
            }
            foreach ($array as $arrayLine) {
               $scripts = array_merge($scripts, self::extractScriptsFromString($arrayLine));
            }
         }
      }
      return $scripts;
   }

   /**
    * Get an array of scripts found in a string
    *
    * @param string $stringToEval , a HTML string with potentially script tags
    *
    * @return array of string
    */
   static function extractScriptsFromString($stringToEval) {
      $scripts = [];
      if (gettype($stringToEval) == "string") {
         $stringToEval = str_replace(["'", "//<![CDATA[", "//]]>"], ['"', "", ""], $stringToEval);
         //             $stringToEval = preg_replace('/\s+/', ' ', $stringToEval);

         if (preg_match_all("/<script[^>]*>([\s\S]+?)<\/script>/i", $stringToEval, $matches)) {
            foreach ($matches[1] as $match) {
               //                     $match = preg_replace('/(\/\/[[:alnum:]_ ]+)/', '', $match);
               //                     $match = preg_replace('#^\s*//.+$#m', "", $match);
               $scripts[] = $match;
            }
         }
      }
      return $scripts;
   }

   /**
    * Get a string without scripts from stringToEval,
    * it strips script tags
    *
    * @param string $stringToEval , the string that you want without scripts
    *
    * @return string with no scripts
    */
   static function removeScriptsFromString($stringToEval) {
      //      $stringWOScripts = "";
      //      if (gettype($stringToEval) == "string") {
      //         $stringWOScripts = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $stringToEval);
      //      }
      //      return $stringWOScripts;
      return $stringToEval;
   }


   /**
    * This method permit to avoid problems with function in JSon datas (example : tickFormatter)<br>
    * It's used to clean Json data needed to fill a widget<br>
    * Things like "function_to_call" => "function(){...}"
    * are replaced to look like "function_to_call" => function(){}<br>
    * This replacement cause the <b>return value</b> not being a valid Json object (<b>don't call json_decode on
    * it</b>), but it's necessary because some jquery plugins need functions and not string of function
    *
    * @param type $datas , a formatted array of datas
    * @param type $options , a formatted array of options
    *
    * @return a string formatted in JSon (most of the time, because in real JSon you can't have function)
    */
   static function safeJsonData($datas, $options) {
      $value_arr    = [];
      $replace_keys = [];
      foreach ($options as & $option) {
         if (is_array($option)) {
            foreach ($option as $key => & $value) {
               // Look for values starting with 'function('

               if (is_string($value) && strpos($value, 'function(') === 0) {
                  // Store function string.
                  $value_arr[] = $value;
                  // Replace function string in $option with a 'unique' special key.
                  $value = '%' . $key . '%';
                  // Later on, we'll look for the value, and replace it.
                  $replace_keys[] = '"' . $value . '"';
               }
            }
         }
      }

      $json = str_replace($replace_keys,
                          $value_arr,
                          json_encode([
                                         'data'    => $datas,
                                         'options' => $options
                                      ]));

      return $json;
   }

   /**
    * Cleans and encodes in json an array
    * Things like "function_to_call" => "function(){...}"
    * are replaced to look like "function_to_call" => function(){}
    * This replacement cause the return not being a valid Json object (don't call json_decode on it),
    * but it's necessary because some jquery plugins need functions and not string of function
    *
    * @param mixed $array , the array that needs to be cleaned and encoded in json
    *
    * @return string a json encoded array
    */
   static function safeJson($array) {
      $value_arr    = [];
      $replace_keys = [];
      foreach ($array as $key => & $value) {

         if (is_string($value) && strpos($value, 'function(') === 0) {
            // Store function string.
            $value_arr[] = $value;
            // Replace function string in $option with a 'unique' special key.
            $value = '%' . $key . '%';
            // Later on, we'll look for the value, and replace it.
            $replace_keys[] = '"' . $value . '"';
         }

      }

      $json = str_replace($replace_keys, $value_arr, json_encode($array));

      return $json;
   }

   /**
    * @param $widgettype
    * @param $query
    *
    * @return PluginMydashboardDatatable|PluginMydashboardHBarChart|PluginMydashboardHtml|PluginMydashboardLineChart|PluginMydashboardPieChart|PluginMydashboardVBarChart
    */
   static function getWidgetsFromDBQuery($widgettype, $query/*$widgettype,$table,$fields,$condition,$groupby,$orderby*/) {
      global $DB;

      if (stripos(trim($query), "SELECT") === 0) {

         $result = $DB->query($query);
         $tab    = [];
         if ($result) {
            while ($row = $DB->fetchAssoc($result)) {
               $tab[] = $row;
            }
            $linechart = false;
            $chart     = false;
            switch ($widgettype) {
               case 'datatable':
               case 'table' :
                  $widget = new PluginMydashboardDatatable();
                  break;
               case 'hbarchart':
                  $chart  = true;
                  $widget = new PluginMydashboardHBarChart();
                  break;
               case 'vbarchart':
                  $chart  = true;
                  $widget = new PluginMydashboardVBarChart();
                  break;
               case 'piechart':
                  $chart  = true;
                  $widget = new PluginMydashboardPieChart();
                  break;
               case 'linechart':
                  $linechart = true;
                  $widget    = new PluginMydashboardLineChart();
                  break;
            }
            //            $widget = new PluginMydashboardHBarChart();
            //        $widget->setTabNames(array('Category','Count'));
            if ($chart) {
               $newtab = [];
               foreach ($tab as $key => $line) {
                  $line             = array_values($line);
                  $newtab[$line[0]] = $line[1];
                  unset($tab[$key]);
               }
               $tab = $newtab;
            } else if ($linechart) {
               //TODO format for linechart
            } else {
               //$widget->setTabNames(array('Category','Count'));
            }
            $widget->setTabDatas($tab);

         }
      } else {
         $widget = new PluginMydashboardHtml();
         $widget->debugError(__('Not a valid SQL SELECT query', 'mydashboard'));
         $widget->debugNotice($query);
      }

      return $widget;
   }

   /*
    * @Create an HTML drop down menu
    *
    * @param string $name The element name and ID
    *
    * @param int $selected The month to be selected
    *
    * @return string
    *
    */
   static function YearDropdown($selected = null) {

      $year = date("Y") - 3;
      for ($i = 0; $i <= 3; $i++) {
         $elements[$year] = $year;

         $year++;
      }
      $opt = ['value'   => $selected,
              'display' => false];

      return Dropdown::showFromArray("year", $elements, $opt);
   }

   /*
    *
    * @Create an HTML drop down menu
    *
    * @param string $name The element name and ID
    *
    * @param int $selected The month to be selected
    *
    * @return string
    *
    */
   static function monthDropdown($name = "month", $selected = null) {

      $monthsarray = Toolbox::getMonthsOfYearArray();

      $opt = ['value'   => $selected,
              'display' => false];

      return Dropdown::showFromArray($name, $monthsarray, $opt);
   }


   static public function getRequesterGroup($prefered_group, $opt, $params = false, $entity, $userid) {
      global $DB;

      $dbu = new DbUtils();

      $query = ['FIELDS'     => ['glpi_groups' => ['id']],
                'FROM'       => 'glpi_groups_users',
                'INNER JOIN' => ['glpi_groups' => ['FKEY' => ['glpi_groups'       => 'id',
                                                              'glpi_groups_users' => 'groups_id']]],
                'WHERE'      => ['users_id' => $userid,
                                 $dbu->getEntitiesRestrictCriteria('glpi_groups', '', $entity, true),
                                 '`is_requester`']];

      $rep = [];
      foreach ($DB->request($query) as $data) {
         $rep[] = $data['id'];
      }

      $res = [];
      if (!$params) {
         if (isset($prefered_group)
             && !empty($prefered_group)
             && count($opt) <= 1) {
            $res = json_decode($prefered_group, true);
         } else if (isset($opt['requesters_groups_id'])) {
            $res = (is_array($opt['requesters_groups_id']) ? $opt['requesters_groups_id'] : [$opt['requesters_groups_id']]);
         } else {
            $res = $rep;
         }
      } else {
         if (isset($params['preferences']['requester_prefered_group'])
             && !empty($params['preferences']['requester_prefered_group'])
             && !isset($params['opt']['requesters_groups_id'])) {
            $res = json_decode($params['preferences']['requester_prefered_group'], true);
         } else if (isset($params['opt']['requesters_groups_id'])
                    && count($params['opt']['requesters_groups_id']) > 0) {
            $res = json_decode($params['opt']['requesters_groups_id'], true);
         }
      }
      return $res;
   }

   /**
    * @param      $prefered_group
    * @param      $opt
    * @param bool $params
    *
    * @return array|mixed
    */
   static function getGroup($prefered_group, $opt, $params = false) {
      $groupprofiles = new PluginMydashboardGroupprofile();
      $res           = [];
      if (!$params) {
         if (isset($prefered_group)
             && !empty($prefered_group)
             && count($opt) <= 1) {
            if ($group = $groupprofiles->getProfilGroup($_SESSION['glpiactiveprofile']['id'])) {
               $res = json_decode($group, true);
            } else {
               $res = json_decode($prefered_group, true);
            }
         } else if ($group = $groupprofiles->getProfilGroup($_SESSION['glpiactiveprofile']['id'])
                             && count($opt) < 1) {
            $res = json_decode($group, true);
         } else if (isset($opt['technicians_groups_id'])) {
            $res = (is_array($opt['technicians_groups_id']) ? $opt['technicians_groups_id'] : [$opt['technicians_groups_id']]);
         } else {
            $res = [];
         }
      } else {
         if (isset($params['preferences']['prefered_group'])
             && !empty($params['preferences']['prefered_group'])
             && !isset($params['opt']['technicians_groups_id'])) {
            if ($group = $groupprofiles->getProfilGroup($_SESSION['glpiactiveprofile']['id'])) {
               $res = json_decode($group, true);
            } else {
               $res = json_decode($params['preferences']['prefered_group'], true);
            }
         } else if (isset($params['opt']['technicians_groups_id'])
                    && count($params['opt']['technicians_groups_id']) > 0) {
            $res = json_decode($params['opt']['technicians_groups_id'], true);
         } else if (($group = $groupprofiles->getProfilGroup($_SESSION['glpiactiveprofile']['id']))
                    && !isset($params['opt']['technicians_groups_id'])) {
            $res = json_decode($group, true);
         }
      }
      return $res;
   }
}
