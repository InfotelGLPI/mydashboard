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

   /**
    * @param $params
    *
    * @return string
    */
   static function getGraphHeader($params) {

      $name  = $params['name'];
      $graph = "<div class='bt-row'>";
      if ($params["export"] == true) {
         $graph .= "<div class='bt-col-md-8 left'>";
      } else {
         $graph .= "<div class='bt-col-md-12 left'>";
      }
      if (count($params["criterias"]) > 0) {
         $graph .= self::getForm($params["widgetId"], $params["opt"], $params["criterias"], $params["onsubmit"]);
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


   /**
    * @param $params
    *
    * @return string
    */
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
         } else if ($_SERVER["REQUEST_URI"] == $CFG_GLPI['root_doc'] . "/plugins/mydashboard/front/menu.php") {
            $groups_id                   = self::getRequesterGroup($params['preferences']['requester_prefered_group'], $params, $_SESSION['glpiactive_entity'], Session::getLoginUserID(), $opt);
            $opt['requesters_groups_id'] = $groups_id;
         } else {
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
            $opt['technicians_groups_id'] = is_array($params['opt']['technicians_groups_id']) ? $params['opt']['technicians_groups_id'] : [$params['opt']['technicians_groups_id']];
         } else if ($_SERVER["REQUEST_URI"] == $CFG_GLPI['root_doc'] . "/plugins/mydashboard/front/menu.php") {
            $groups_id                    = self::getGroup($params['preferences']['prefered_group'], $opt, $params);
            $opt['technicians_groups_id'] = $groups_id;
         } else {
            $opt['technicians_groups_id'] = [];
         }
         $params['opt']['technicians_groups_id'] = $opt['technicians_groups_id'];
         if (isset($params['opt']['technicians_groups_id'])
             && is_array($params['opt']['technicians_groups_id'])
             && count($params['opt']['technicians_groups_id']) > 0) {
            $none = false;
            if ($params['opt']['technicians_groups_id'][0] == "0") {
               $none = true;
            }
            if (in_array("group_is_recursive", $criterias) && isset($params['opt']['ancestors']) && $params['opt']['ancestors'] != 0) {
               $dbu    = new DbUtils();
               $childs = [];
               foreach ($opt['technicians_groups_id'] as $k => $v) {
                  $childs = $dbu->getSonsAndAncestorsOf('glpi_groups', $v);
               }
               if ($none) {
                  $crit['crit']['technicians_groups_id'] = " AND ( `glpi_tickets`.`id` NOT IN (SELECT `tickets_id` AS id FROM `glpi_groups_tickets`) ";
                  $crit['crit']['technicians_groups_id'] .= " OR `glpi_tickets`.`id` IN (SELECT `tickets_id` AS id FROM `glpi_groups_tickets`
            WHERE `type` = " . CommonITILActor::ASSIGN . " AND `groups_id` IN (" . implode(",", $childs) . ")))";
               } else {
                  $crit['crit']['technicians_groups_id'] .= " AND `glpi_tickets`.`id` IN (SELECT `tickets_id` AS id FROM `glpi_groups_tickets`
            WHERE `type` = " . CommonITILActor::ASSIGN . " AND `groups_id` IN (" . implode(",", $childs) . "))";
               }
               $opt['ancestors']          = $params['opt']['ancestors'];
               $crit['crit']['ancestors'] = $opt['ancestors'];
            } else {
               if ($none) {
                  $crit['crit']['technicians_groups_id'] = " AND ( `glpi_tickets`.`id` NOT IN (SELECT `tickets_id` AS id FROM `glpi_groups_tickets`) ";
                  $crit['crit']['technicians_groups_id'] .= " OR `glpi_tickets`.`id` IN (SELECT `tickets_id` AS id FROM `glpi_groups_tickets`
            WHERE `type` = " . CommonITILActor::ASSIGN . " AND `groups_id` IN (" . implode(",", $params['opt']['technicians_groups_id']) . ")))";
               } else {
                  $crit['crit']['technicians_groups_id'] .= " AND `glpi_tickets`.`id` IN (SELECT `tickets_id` AS id FROM `glpi_groups_tickets`
            WHERE `type` = " . CommonITILActor::ASSIGN . " AND `groups_id` IN (" . implode(",", $params['opt']['technicians_groups_id']) . "))";
               }
               $opt['ancestors']          = 0;
               $crit['crit']['ancestors'] = 0;
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

      // LOCATIONS
      $opt['multiple_locations_id']          = [];
      $crit['crit']['multiple_locations_id'] = " AND 1 = 1 ";
      $opt['loc_ancestors']                  = 0;
      $crit['crit']['loc_ancestors']         = 0;
      if (in_array("multiple_locations_id", $criterias)) {

         if (isset($params['opt']['multiple_locations_id'])) {
            $opt['multiple_locations_id'] = is_array($params['opt']['multiple_locations_id']) ? $params['opt']['multiple_locations_id'] : [$params['opt']['multiple_locations_id']];
            //            $crit['crit']['multiple_locations_id'] = " AND `glpi_tickets`.`locations_id` IN  (" . implode(",", $opt['multiple_locations_id']) . ") ";

         } else {
            $crit['crit']['multiple_locations_id'] = "";
         }
         $params['opt']['multiple_locations_id'] = $opt['multiple_locations_id'];

         if (isset($params['opt']['multiple_locations_id'])
             && is_array($params['opt']['multiple_locations_id'])
             && count($params['opt']['multiple_locations_id']) > 0) {
            if (isset($params['opt']['loc_ancestors']) && $params['opt']['loc_ancestors'] != 0) {
               $dbu    = new DbUtils();
               $childs = [];
               foreach ($opt['multiple_locations_id'] as $k => $v) {
                  $childs = $dbu->getSonsAndAncestorsOf('glpi_locations', $v);
               }
               $crit['crit']['multiple_locations_id'] .= " AND `locations_id` IN (" . implode(",", $childs) . ")";
               $opt['loc_ancestors']                  = $params['opt']['loc_ancestors'];
               $crit['crit']['loc_ancestors']         = $opt['loc_ancestors'];
            } else {
               $crit['crit']['multiple_locations_id'] .= " AND `locations_id` IN (" . implode(",", $params['opt']['multiple_locations_id']) . ")";
               $opt['loc_ancestors']                  = 0;
               $crit['crit']['loc_ancestors']         = 0;
            }
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

      // DISPLAY DATA

      if (in_array("display_data", $criterias)) {
         if (isset($params['opt']['display_data'])) {
            $opt["display_data"]          = $params['opt']['display_data'];
            $crit['crit']['display_data'] = $params['opt']['display_data'];
         } else {
            $opt["display_data"]          = "YEAR";
            $crit['crit']['display_data'] = "YEAR";
         }

         if($opt["display_data"] == "YEAR"){
            $year                 = intval(strftime("%Y"));
            if (isset($params['opt']["year"])
                && $params['opt']["year"] > 0) {
               $year        = $params['opt']["year"];
               $opt['year'] = $params['opt']['year'];
            } else {
               $opt["year"] = $year;
            }
            $crit['crit']['year'] = $opt['year'];
         } else if ($opt["display_data"] == "START_END") {
            if (isset($params['opt']["start_month"])
                && $params['opt']["start_month"] > 0) {
               $opt['start_month'] = $params['opt']['start_month'];
               $crit['crit']['start_month'] = $params['opt']['start_month'];
            } else {
               $opt["start_month"] = date("m");
               $crit['crit']['start_month'] = date("m");
            }

            if (isset($params['opt']["start_year"])
                && $params['opt']["start_year"] > 0) {
               $opt['start_year'] = $params['opt']['start_year'];
               $crit['crit']['start_year'] = $params['opt']['start_year'];
            } else {
               $opt["start_year"] = date("Y");
               $crit['crit']['start_year'] = date("Y");
            }

            if (isset($params['opt']["end_month"])
                && $params['opt']["end_month"] > 0) {
               $opt['end_month'] = $params['opt']['end_month'];
               $crit['crit']['end_month'] = $params['opt']['end_month'];
            } else {
               $opt["end_month"] = date("m");
               $crit['crit']['end_month'] = date("m");
            }

            if (isset($params['opt']["end_year"])
                && $params['opt']["end_year"] > 0) {
               $opt['end_year'] = $params['opt']['end_year'];
               $crit['crit']['end_year'] = $params['opt']['end_year'];
            } else {
               $opt["end_year"] = date("Y");
               $crit['crit']['end_year'] = date("Y");
            }
         }

      }

      if (in_array("filter_date", $criterias)) {
         if (isset($params['opt']['filter_date'])) {
            $opt["filter_date"]          = $params['opt']['filter_date'];
            $crit['crit']['filter_date'] = $params['opt']['filter_date'];
         } else {
            $opt["filter_date"]          = "YEAR";
            $crit['crit']['filter_date'] = "YEAR";
         }

         if($opt["filter_date"] == "YEAR"){
            $year                 = intval(strftime("%Y"));
            if (isset($params['opt']["year"])
                && $params['opt']["year"] > 0) {
               $year        = $params['opt']["year"];
               $opt['year'] = $params['opt']['year'];
            } else {
               $opt["year"] = $year;
            }
            $crit['crit']['year'] = $opt['year'];

            $crit['crit']['date']      = "(`glpi_tickets`.`date` >= '$year-01-01 00:00:01' 
                              AND `glpi_tickets`.`date` <= ADDDATE('$year-12-31 00:00:00' , INTERVAL 1 DAY) )";
            $crit['crit']['closedate'] = "(`glpi_tickets`.`closedate` >= '$year-01-01 00:00:01' 
                              AND `glpi_tickets`.`closedate` <= ADDDATE('$year-12-31 00:00:00' , INTERVAL 1 DAY) )";
         } else if ($opt["filter_date"] == "BEGIN_END") {

            if (isset($params['opt']['begin'])
                && $params['opt']["begin"] > 0) {
               $opt["begin"]          = $params['opt']['begin'];
               $crit['crit']['begin'] = $params['opt']['begin'];
            } else {
               $opt["begin"] = date("Y-m-d H:i:s");
            }

            if (isset($params['opt']['end'])
                && $params['opt']["end"] > 0) {
               $opt["end"]          = $params['opt']['end'];
               $crit['crit']['end'] = $params['opt']['end'];
            } else {
               $opt["end"] = date("Y-m-d H:i:s");
            }
            $end =  $opt["end"];
            $start = $opt["begin"];

            $crit['crit']['date']      = "(`glpi_tickets`.`date` >= '$start' 
                              AND `glpi_tickets`.`date` <= '$end' )";
            $crit['crit']['closedate'] = "(`glpi_tickets`.`closedate` >= '$start' 
                              AND `glpi_tickets`.`closedate` <= '$end' )";
         }

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

      if(!in_array('filter_date',$criterias)) {


         $nbdays                    = date("t", mktime(0, 0, 0, $month, 1, $year));
         $crit['crit']['date']      = "(`glpi_tickets`.`date` >= '$year-$month-01 00:00:01' 
                              AND `glpi_tickets`.`date` <= ADDDATE('$year-$month-$nbdays 00:00:00' , INTERVAL 1 DAY) )";
         $crit['crit']['closedate'] = "(`glpi_tickets`.`closedate` >= '$year-$month-01 00:00:01' 
                              AND `glpi_tickets`.`closedate` <= ADDDATE('$year-$month-$nbdays 00:00:00' , INTERVAL 1 DAY) )";
      }

      if (!in_array("month", $criterias) && !in_array('filter_date',$criterias)) {
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

      // TECHNICIAN MULTIPLE
      $opt['multiple_technicians_id']          = [];
      $crit['crit']['multiple_technicians_id'] = " AND 1 = 1 ";
      if (in_array("multiple_technicians_id", $criterias)) {

         if (isset($params['opt']['multiple_technicians_id'])) {
            $opt['multiple_technicians_id'] = is_array($params['opt']['multiple_technicians_id']) ? $params['opt']['multiple_technicians_id'] : [$params['opt']['multiple_technicians_id']];
         } else {
            $crit['crit']['multiple_technicians_id'] = [];
         }
         $params['opt']['multiple_technicians_id'] = $opt['multiple_technicians_id'];

         if (isset($params['opt']['multiple_technicians_id'])
             && is_array($params['opt']['multiple_technicians_id'])
             && count($params['opt']['multiple_technicians_id']) > 0) {
            $crit['crit']['multiple_technicians_id'] = $params['opt']['multiple_technicians_id'];
         }
      }

      // LIMIT
      if (in_array("limit", $criterias)) {
         if (isset($params['opt']['limit'])) {
            $opt["limit"]          = $params['opt']['limit'];
            $crit['crit']['limit'] = $params['opt']['limit'];
         }
      }

      // MULTIPLE TIME
      if (in_array("multiple_time", $criterias)) {
         if (isset($params['opt']['multiple_time'])) {
            $opt["multiple_time"]          = $params['opt']['multiple_time'];
            $crit['crit']['multiple_time'] = $params['opt']['multiple_time'];
         } else {
            $opt["multiple_time"]          = "MONTH";
            $crit['crit']['multiple_time'] = "MONTH";
         }
      }

      // MULTIPLE YEAR TIME
      if (in_array("multiple_year_time", $criterias)) {
         if (isset($params['opt']['multiple_year_time'])) {
            $opt["multiple_year_time"]          = $params['opt']['multiple_year_time'];
            $crit['crit']['multiple_year_time'] = $params['opt']['multiple_year_time'];
         } else {
            $opt["multiple_year_time"]          = "LASTMONTH";
            $crit['crit']['multiple_year_time'] = "LASTMONTH";
         }
         if (isset($params['opt']['month_year'])) {
            $opt["month_year"]          = $params['opt']['month_year'];
            $crit['crit']['month_year'] = $params['opt']['month_year'];
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
      //TYPE
      $opt['itilcategorielvl1']          = 0;
      $crit['crit']['itilcategorielvl1'] = "AND 1 = 1";
      if (in_array("itilcategorielvl1", $criterias)) {
         if (isset($params['opt']["itilcategorielvl1"])
             && $params['opt']["itilcategorielvl1"] > 0) {
            $opt['itilcategorielvl1']          = $params['opt']['itilcategorielvl1'];
            $categorie = new ITILCategory();
            $catlvl2 = $categorie->find(['itilcategories_id'=>$opt['itilcategorielvl1']]);
            $i = 0;
            $listcat = "";
            foreach ($catlvl2 as $cat){
               if($i !=0){
                  $listcat .= ",".$cat['id'];
               }else{
                  $listcat .= $cat['id'];
               }
               $i++;
            }
            if(empty($listcat)){
               $listcat = "0";
            }
            $crit['crit']['itilcategorielvl1'] = " AND `glpi_tickets`.`itilcategories_id` IN ( " . $listcat . ") ";
         }
      }

      //TAG
      $opt['tag']          = 0;
      $crit['crit']['tag'] = "AND 1 = 1";
      if (in_array("tag", $criterias)) {
         if (isset($params['opt']["tag"])
             && $params['opt']["tag"] > 0) {
            $opt['tag']          = $params['opt']['tag'];

            $crit['crit']['tag'] = " AND `glpi_plugin_tag_tagitems`.`plugin_tag_tags_id` = " . $opt['tag'] . " ";
         }
      }

      $crit['opt'] = $opt;

      return $crit;
   }


   /**
    * Get a form header, this form header permit to update data of the widget
    * with parameters of this form
    *
    * @param int   $widgetId
    * @param       $gsid
    * @param bool  $onsubmit
    *
    * @param array $opt
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

      if (isset($opt['multiple_locations_id'])) {
         $opt['multiple_locations_id'] = is_array($opt['multiple_locations_id']) ? $opt['multiple_locations_id'] : [$opt['multiple_locations_id']];
         if (count($opt['multiple_locations_id']) > 0) {
            $form .= "&nbsp;/&nbsp;" . _n('Location', 'Locations', count($opt['multiple_locations_id'])) . "&nbsp;:&nbsp;";
            foreach ($opt['multiple_locations_id'] as $k => $v) {
               $form .= Dropdown::getDropdownName('glpi_locations', $v);
               if (count($opt['multiple_locations_id']) > 1) {
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

      if (isset($opt['year'])) {
         $form .= "&nbsp;/&nbsp;" . __('Year', 'mydashboard') . "&nbsp;:&nbsp;" . $opt['year'];
      }

      // TECHNICIAN MULTIPLE
      if (isset($opt['multiple_technicians_id'])) {
         $opt['multiple_technicians_id'] = is_array($opt['multiple_technicians_id']) ? $opt['multiple_technicians_id'] : [$opt['multiple_technicians_id']];
         if (count($opt['multiple_technicians_id']) > 0) {
            $form .= "&nbsp;/&nbsp;" . _n('Technician', 'Technicians', count($opt['multiple_technicians_id']), 'mydashboard') . "&nbsp;:&nbsp;";
            foreach ($opt['multiple_technicians_id'] as $k => $v) {
               $form .= getUserName($v);
               if (count($opt['multiple_technicians_id']) > 1) {
                  $form .= "&nbsp;-&nbsp;";
               }
            }
         }
      }
      // TECHNICIAN
      if (isset($opt['technicians_id']) && $opt['technicians_id'] > 0) {
         $form .= "&nbsp;/&nbsp;" . __('Technician') . "&nbsp;:&nbsp;" . getUserName($opt['technicians_id']);
      }

      if (isset($opt['tag']) && $opt['tag'] > 0) {
         $form .= "&nbsp;/&nbsp;" .PluginTagTag::getTypeName() . "&nbsp;:&nbsp;" . Dropdown::getDropdownName('glpi_plugin_tag_tags', $opt['tag']);
      }
      if (isset($opt['multiple_year_time'])) {
         switch ($opt['multiple_year_time']){
            case "LASTMONTH":
               $form .= "&nbsp;/&nbsp;".__('Time display', 'mydashboard')."&nbsp;/&nbsp;" .__("Last month",'mydashboard');
               break;
            case "LASTYEAR":
               $form .= "&nbsp;/&nbsp;".__('Time display', 'mydashboard')."&nbsp;/&nbsp;" .__("Last year",'mydashboard');
               break;
            case "YEARTODATE":
               $form .= "&nbsp;/&nbsp;".__('Time display', 'mydashboard')."&nbsp;/&nbsp;" .__("Year to date",'mydashboard');
               break;
            case "MONTH":
               $form .= "&nbsp;/&nbsp;".__('Time display', 'mydashboard')."&nbsp;/&nbsp;" .__("Month",'mydashboard');
               break;
         }

      }

      if (isset($opt['display_data']) && $opt['display_data'] == "SLIDING") {
               $form .= "&nbsp;/&nbsp;".sprintf(__('sliding %s-month period', 'mydashboard'),$opt['period_time']);
      }
      if (isset($opt['itilcategorielvl1']) && $opt['itilcategorielvl1'] > 0) {
         $form .= "&nbsp;/&nbsp;" . __("Category",'mydashobard') . "&nbsp;:&nbsp;" . Dropdown::getDropdownName('glpi_itilcategories', $opt['itilcategorielvl1']);

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

   /**
    * @param       $widgetId
    * @param false $onsubmit
    * @param       $opt
    * @param       $criterias
    *
    * @return string
    */
   static function getForm($widgetId, $opt, $criterias, $onsubmit = false) {

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

      // MULTIPLE LOCATIONS
      if (in_array("multiple_locations_id", $criterias)) {
         $form .= "<span class='md-widgetcrit'>";

         $dbu    = new DbUtils();
         $result = $dbu->getAllDataFromTable(Location::getTable(), ['ORDER' => "completename"], false);

         if (isset($opt['multiple_locations_id'])) {
            $multiple_locations_id = (is_array($opt['multiple_locations_id']) ? $opt['multiple_locations_id'] : [$opt['multiple_locations_id']]);
         } else {
            $multiple_locations_id = [];
         }

         $temp = [];
         foreach ($result as $item) {
            $temp[$item['id']] = $item['completename'];
         }

         $params = [
            "name"                => 'multiple_locations_id',
            "display"             => false,
            "multiple"            => true,
            "width"               => '200px',
            'values'              => $multiple_locations_id,
            'display_emptychoice' => true
         ];

         $form .= _n('Location', 'Locations', 2);
         $form .= "&nbsp;";

         $dropdown = Dropdown::showFromArray("multiple_locations_id", $temp, $params);

         $form .= $dropdown;

         $form .= "</span>";
         if ($count > 1) {
            $form .= "</br></br>";
         }

         $form      .= "<span class='md-widgetcrit'>";
         $form      .= __('Child locations', 'mydashboard') . "&nbsp;";
         $paramsy   = ['display' => false];
         $ancestors = isset($opt['loc_ancestors']) ? $opt['loc_ancestors'] : 0;
         $form      .= Dropdown::showYesNo('loc_ancestors', $ancestors, -1, $paramsy);
         $form      .= "</span>";
         if ($count > 1) {
            $form .= "</br></br>";
         }
      }
      // REQUESTER GROUPS
      if (in_array("requesters_groups_id", $criterias)) {
         $form .= "<span class='md-widgetcrit'>";

         $dbu    = new DbUtils();
         $result = $dbu->getAllDataFromTable(Group::getTable(), ['is_requester' => 1, 'ORDER' => "completename"], false);

         if (isset($opt['requesters_groups_id'])) {
            $requesters_groups_id = (is_array($opt['requesters_groups_id']) ? $opt['requesters_groups_id'] : [$opt['requesters_groups_id']]);
         } else {
            $requesters_groups_id = [];
         }

         $temp = [];
         foreach ($result as $item) {
            $temp[$item['id']] = $item['completename'];
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
         $result = $dbu->getAllDataFromTable(Group::getTable(), ['is_assign' => 1, 'ORDER' => "completename"], false);

         if (isset($opt['technicians_groups_id'])) {
            $technicians_groups_id = (is_array($opt['technicians_groups_id']) ? $opt['technicians_groups_id'] : [$opt['technicians_groups_id']]);
         } else {
            $technicians_groups_id = [];
         }

         $temp = [];
         foreach ($result as $item) {
            $temp[$item['id']] = $item['completename'];
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

      if (in_array("week", $criterias)) {
         $form           .= "<span class='md-widgetcrit'>";
         $semaine_courante = strftime("%W");
         if (isset($opt["week"])
             && $opt["week"] > 0) {
            $semaine_courante = $opt["week"];
         }
         $form .= __('Week', 'mydashboard');
         $form .= "&nbsp;";
         $form .= self::WeekDropdown($semaine_courante);
         $form .= "</span>";
         if ($count > 1) {
            $form .= "</br></br>";
         }
      }
      // MONTH
      if (in_array("month", $criterias)) {
         $form .= "<span class='md-widgetcrit'>";
         $form .= __('Month', 'mydashboard');
         $form .= "&nbsp;";
         $form .= self::monthDropdown("month", (isset($opt['month']) ? $opt['month'] : 0));
         $form .= "</span>";
         if ($count > 1) {
            $form .= "</br></br>";
         }
      }
      // START DATE
      if (in_array("begin", $criterias)) {
         $form .= "<span class='md-widgetcrit'>";
         $form .= __('Start');
         $form .= "&nbsp;";
         $form .= Html::showDateTimeField("begin", ['value' => isset($opt['begin']) ? $opt['begin'] : null, 'maybeempty' => false, 'display' => false]);
         $form .= "</span>";
         if ($count > 1 && !in_array("end", $criterias)) {
            $form .= "</br></br>";
         } elseif ($count > 1 && in_array("end", $criterias)) {
            $form .= "</br>";
         }
      }
      // END DATE
      if (in_array("end", $criterias)) {
         $form .= "<span class='md-widgetcrit'>";
         $form .= __('End');
         $form .= "&nbsp;";
         $form .= Html::showDateTimeField("end", ['value' => isset($opt['end']) ? $opt['end'] : null, 'maybeempty' => false, 'display' => false]);
         $form .= "</span>";
         if ($count > 1) {
            $form .= "</br></br>";
         }
      }

      // USER
      if (in_array("users_id", $criterias)) {
         $form   .= "<span class='md-widgetcrit'>";
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
         $form   .= "</span>";
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
         $form   .= "<span class='md-widgetcrit'>";
         $form   .= __('Technician');
         $form   .= "&nbsp;";
         $form   .= User::dropdown($params);
         $form   .= "</span>";
         if ($count > 1) {
            $form .= "</br></br>";
         }
      }

      // TECHNICIAN MULTIPLE
      if (in_array("multiple_technicians_id", $criterias)) {
         $form .= "<span class='md-widgetcrit'>";

         $params['entity']    = $_SESSION['glpiactive_entity'];
         $params['right']     = ['groups'];
         $data_users          = [];
         $users               = [];
         $param['values']     = [];
         $params['groups_id'] = 0;
         if (isset($opt['technicians_groups_id'])) {
            $technicians_groups_id = (is_array($opt['technicians_groups_id']) ? $opt['technicians_groups_id'] : [$opt['technicians_groups_id']]);
         } else {
            $technicians_groups_id = [];
         }
         $technicians_groups_id = 1;
         $list                  = [];
         $restrict              = [];
         $res                   = User::getSqlSearchResult(false, $params['right'], $params['entity']);
         while ($data = $res->next()) {
            $list[] = $data['id'];
         }
         if (count($list) > 0) {
            $restrict = ['glpi_users.id' => $list];
         }
         $restrict["glpi_users.is_deleted"] = 0;
         $restrict["glpi_users.is_active"] = 1;

         $data_users = Group_user::getGroupUsers($technicians_groups_id, $restrict);

         foreach ($data_users as $data) {
            $users[$data['id']] = formatUserName($data['id'], $data['name'], $data['realname'],
                                                 $data['firstname']);
            $params['values'][]  = $data['id'];
         }
         $users             = Toolbox::stripslashes_deep($users);
         $params['multiple'] = true;
         $params['display']  = false;
         $params['size']     = count($users);

         $form .= _n('Technician', 'Technicians', 2, 'mydashboard');
         $form .= "&nbsp;";

         $dropdownusers = Dropdown::showFromArray("multiple_technicians_id", $users, $params);

         $form .= $dropdownusers;

         $form .= "</span>";
         if ($count > 1) {
            $form .= "</br></br>";
         }
      }

      // LIMIT
      if (in_array("limit", $criterias)) {
         $params = ['value'   => isset($opt['limit']) ? $opt['limit'] : 0,
                    'min'     => 0,
                    'max'     => 200,
                    'step'    => 1,
                    'display' => false,
                    'toadd'   => [0 => __('All')]];
         $form   .= "<span class='md-widgetcrit'>";
         $form   .= __('Number of results');
         $form   .= "&nbsp;";
         $form   .= Dropdown::showNumber("limit", $params);
         $form   .= "</span>";
         if ($count > 1) {
            $form .= "</br></br>";
         }
      }
      //STATUS
      if (in_array("status", $criterias)) {
         $form    .= "<span class='md-widgetcrit'>";
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
         $form .= "</span>";
         if ($count > 1) {
            $form .= "</br></br>";
         }
      }

      if (in_array("multiple_time", $criterias)) {
         $form .= "<span class='md-widgetcrit'>";



         $temp = [];
         $temp["DAY"] = __("Day",'mydashboard');
         $temp["WEEK"] = __("Week",'mydashboard');
         $temp["MONTH"] = __("Month",'mydashboard');

         $params = [
            "name"                => 'multiple_time',
            "display"             => false,
            "multiple"            => false,
            "width"               => '200px',
            'value'              => isset($opt['multiple_time'])?$opt['multiple_time']:null,
            'display_emptychoice' => false
         ];

         $form .= __('Time display', 'mydashboard');
         $form .= "&nbsp;";

         $dropdown = Dropdown::showFromArray("multiple_time", $temp, $params);

         $form .= $dropdown;

         $form .= "</span>";


         if ($count > 1) {
            $form .= "</br></br>";
         }

      }

      if (in_array("multiple_year_time", $criterias)) {
         $form .= "<span class='md-widgetcrit'>";



         $temp = [];
         $temp["YEARTODATE"] = __("Year to date",'mydashboard');
         $temp["LASTYEAR"] = __("year",'mydashboard');
         $temp["LASTMONTH"] = __("Last month",'mydashboard');
         $temp["MONTH"] = __("Month",'mydashboard');

         $rand = mt_rand();
         $params = [
            "name"                => 'multiple_year_time',
            "display"             => false,
            "multiple"            => false,
            "width"               => '200px',
            "rand"               => $rand,
            'value'              => isset($opt['multiple_year_time'])?$opt['multiple_year_time']:null,
            'display_emptychoice' => false
         ];

         $form .= __('Time display', 'mydashboard');
         $form .= "&nbsp;";

         $dropdown = Dropdown::showFromArray("multiple_year_time", $temp, $params);

         $form .= $dropdown;


         $form .= "</span>";
         if(isset($opt['multiple_year_time']) && $opt['multiple_year_time'] == 'MONTH'){

            $form .= "<span id='month_crit$rand' name= 'month_crit$rand' class='md-widgetcrit'>";
            $form .= "</br></br>";
            $form .= __('Month', 'mydashboard');
            $form .= "&nbsp;";
            $form .= PluginMydashboardHelper::monthDropdown("month_year", (isset($opt['month_year']) ? $opt['month_year'] : 0));
            $form .= "</span>";
         }else{
            $form .= "<span id='month_crit$rand' name= 'month_crit$rand' class='md-widgetcrit'></span>";
         }

         $params2=['value'=>'__VALUE__',

         ];
         $form .= Ajax::updateItemOnSelectEvent('dropdown_multiple_year_time'.$rand,
                                                "month_crit$rand",
                                                Plugin::getWebDir('mydashboard')."/ajax/dropdownMonth.php",
                                                $params2,
                                                false);

         if ($count > 1) {
            $form .= "</br></br>";
         }

      }

      if (in_array("display_data", $criterias)) {
         $form .= "<span class='md-widgetcrit'>";



         $temp = [];
         $temp["YEAR"] = __("year",'mydashboard');
         $temp["START_END"] = __("Start end",'mydashboard');


         $rand = mt_rand();
         $params = [
            "name"                => 'display_data',
            "display"             => false,
            "multiple"            => false,
            "width"               => '200px',
            "rand"               => $rand,
            'value'              => isset($opt['display_data'])?$opt['display_data']:'YEAR',
            'display_emptychoice' => false
         ];

         $form .= __('Display', 'mydashboard');
         $form .= "&nbsp;";

         $dropdown = Dropdown::showFromArray("display_data", $temp, $params);

         $form .= $dropdown;


         $form .= "</span>";
         if(isset($opt['display_data']) && $opt['display_data'] == 'START_END'){

            $form .= "<span id='display_data_crit$rand' name= 'display_data_crit$rand' class='md-widgetcrit'>";
            $form .= "<span class='md-widgetcrit'>";
            $form .= "</br></br>";
            $form .= __('Start month', 'mydashboard');
            $form .= "&nbsp;";
            $options = [];
            $options['value'] = $opt['start_month'] ?? date('m');
            $options['rand'] = $rand;
            $options['min'] = 1;
            $options['max'] = 12;
            $options['display'] = false;
            $options['width'] = '200px';
            $form .= Dropdown::showNumber('start_month',$options);
            $form .= "</span>";

            $form .= "<span class='md-widgetcrit'>";
            $form .= "</br>";
            $form .= __('Start year', 'mydashboard');
            $form .= "&nbsp;";
            $options = [];
            $options['value'] = $opt['start_year'] ?? date('Y');
            $options['rand'] = $rand;
            $options['display'] = false;
            $year = date("Y") - 3;
            for ($i = 0; $i <= 3; $i++) {
               $elements[$year] = $year;

               $year++;
            }

            $form .= Dropdown::showFromArray("start_year", $elements, $options);
            $form .= "</span>";

            $form .= "<span class='md-widgetcrit'>";
            $form .= "</br></br>";
            $form .= __('End month', 'mydashboard');
            $form .= "&nbsp;";
            $options = [];
            $options['value'] = $opt['end_month'] ?? date('m');
            $options['rand'] = $rand;
            $options['min'] = 1;
            $options['max'] = 12;
            $options['display'] = false;
            $options['width'] = '200px';
            $form .= Dropdown::showNumber('end_month',$options);
            $form .= "</span>";

            $form .= "<span class='md-widgetcrit'>";
            $form .= "</br>";
            $form .= __('End year', 'mydashboard');
            $form .= "&nbsp;";
            $options = [];
            $options['value'] = $opt['end_year'] ?? date('Y');
            $options['rand'] = $rand;
            $options['display'] = false;
            $year = date("Y") - 3;
            for ($i = 0; $i <= 3; $i++) {
               $elements[$year] = $year;

               $year++;
            }

            $form .= Dropdown::showFromArray("end_year", $elements, $options);
//            $form .= Dropdown::showNumber('end_year',$options);
            $form .= "</span>";
            $form .= "</span>";
         }else{
            $form .= "</br></br>";
            $form           .= "<span id='display_data_crit$rand' name= 'display_data_crit$rand' class='md-widgetcrit'>";
            $annee_courante = strftime("%Y");
            if (isset($opt["year"])
                && $opt["year"] > 0) {
               $annee_courante = $opt["year"];
            }
            $form .= __('Year', 'mydashboard');
            $form .= "&nbsp;";
            $form .= self::YearDropdown($annee_courante);
            $form .= "</span>";

         }

         $params2=['value'=>'__VALUE__',

         ];
         $form .= Ajax::updateItemOnSelectEvent('dropdown_display_data'.$rand,
                                                "display_data_crit$rand",
                                                Plugin::getWebDir('mydashboard')."/ajax/dropdownUpdateDisplaydata.php",
                                                $params2,
                                                false);

         if ($count > 1) {
            $form .= "</br></br>";
         }

      }

      if (in_array("filter_date", $criterias)) {
         $form .= "<span class='md-widgetcrit'>";



         $temp = [];
         $temp["YEAR"] = __("year",'mydashboard');
         $temp["BEGIN_END"] = __("begin and end date",'mydashboard');


         $rand = mt_rand();
         $params = [
            "name"                => 'filter_date',
            "display"             => false,
            "multiple"            => false,
            "width"               => '200px',
            "rand"               => $rand,
            'value'              => isset($opt['filter_date'])?$opt['filter_date']:'YEAR',
            'display_emptychoice' => false
         ];

         $form .= __('Filter date', 'mydashboard');
         $form .= "&nbsp;";

         $dropdown = Dropdown::showFromArray("filter_date", $temp, $params);

         $form .= $dropdown;


         $form .= "</span>";
         if(isset($opt['filter_date']) && $opt['filter_date'] == 'BEGIN_END'){

            $form .= "<span id='filter_date_crit$rand' name= 'filter_date_crit$rand' class='md-widgetcrit'>";
            $form .= "<span class='md-widgetcrit'>";

            $form .= __('Start');
            $form .= "&nbsp;";
            $form .= Html::showDateTimeField("begin", ['value' => isset($opt['begin']) ? $opt['begin'] : null, 'maybeempty' => false, 'display' => false]);
            $form .= "</span>";
            $form .= "</br>";
            $form .= "<span class='md-widgetcrit'>";
            $form .= __('End');
            $form .= "&nbsp;";
            $form .= Html::showDateTimeField("end", ['value' => isset($opt['end']) ? $opt['end'] : null, 'maybeempty' => false, 'display' => false]);
            $form .= "</span>";
            $form .= "</span>";
         }else{
            $form .= "</br></br>";
            $form           .= "<span id='filter_date_crit$rand' name= 'filter_date_crit$rand' class='md-widgetcrit'>";
            $annee_courante = strftime("%Y");
            if (isset($opt["year"])
                && $opt["year"] > 0) {
               $annee_courante = $opt["year"];
            }
            $form .= __('Year', 'mydashboard');
            $form .= "&nbsp;";
            $form .= self::YearDropdown($annee_courante);
            $form .= "</span>";

         }

         $params2=['value'=>'__VALUE__',

         ];
         $form .= Ajax::updateItemOnSelectEvent('dropdown_filter_date'.$rand,
                                                "filter_date_crit$rand",
                                                Plugin::getWebDir('mydashboard')."/ajax/dropdownUpdateDisplaydata.php",
                                                $params2,
                                                false);

         if ($count > 1) {
            $form .= "</br></br>";
         }

      }

      if (in_array("itilcategorielvl1", $criterias)) {
         $form .= "<span class='md-widgetcrit'>";



         //

         $form .= __('Category', 'mydashboard');
         $form .= "&nbsp;";
         $dbu = new DbUtils();
         if(isset($_POST["params"]['entities_id'])){
            $restrict = $dbu->getEntitiesRestrictCriteria('glpi_entities', '', $_POST["params"]['entities_id'], $_POST["params"]['sons']);
         }else{
            $restrict = $dbu->getEntitiesRestrictCriteria('glpi_entities', '', $opt['entities_id'], $opt['sons']);
         }

         $dropdown = ITILCategory::dropdown(['name'=>'itilcategorielvl1','value'=>$opt['itilcategorielvl1'],'display'=>false,'condition'=>['level'=>1,['OR'=>['is_request'=>1,'is_incident'=>1]]]]+$restrict);

         $form .= $dropdown;

         $form .= "</span>";


         if ($count > 1) {
            $form .= "</br></br>";
         }

      }

      if (in_array("tag", $criterias)) {
         $form .= "<span class='md-widgetcrit'>";



         //

         $form .= __('Tag', 'mydashboard');
         $form .= "&nbsp;";
         $dbu = new DbUtils();
         if(isset($_POST["params"]['entities_id'])){
            $restrict = $dbu->getEntitiesRestrictCriteria('glpi_plugin_tag_tags', '', $_POST["params"]['entities_id'], $_POST["params"]['sons']);
         }else{
            $restrict = $dbu->getEntitiesRestrictCriteria('glpi_plugin_tag_tags', '', $opt['entities_id'], $opt['sons']);
         }
         $tag = new PluginTagTag();
         $data_tags = $tag->find([$restrict]);
         foreach ($data_tags as $data) {
            $types = json_decode($data['type_menu']);
            if(in_array('Ticket',$types)){
               $tags[$data['id']] = $data['name'];
            }
         }
         $params['multiple'] = false;
         $params['display']  = false;
         $params['value']  = isset($opt['tag'])?$opt['tag']:null;
         $params['size']     = count($tags);


         $dropdown = Dropdown::showFromArray("tag", $tags, $params);



         $form .= $dropdown;

         $form .= "</span>";


         if ($count > 1) {
            $form .= "</br></br>";
         }

      }

      if ($onsubmit) {
         $form .= "<input type='submit' class='submit' value='" . _x('button', 'Send') . "'>";
      }

      return $form . self::getFormFooter();
   }

   /**
    * @return string
    */
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
   /**
    * @param null $selected
    *
    * @return int|string
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

   /**
    * @param null $selected
    *
    * @return int|string
    */
   static function WeekDropdown($selected = null) {


      $opt = [
         'value'   => $selected,
         'min'   => 1,
         'max'   => 53,
              'display' => false];

      return Dropdown::showNumber("week", $opt);
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
   /**
    * @param string $name
    * @param null   $selected
    *
    * @return int|string
    */
   static function monthDropdown($name = "month", $selected = null) {

      $monthsarray = Toolbox::getMonthsOfYearArray();

      $opt = ['value'   => $selected,
              'display' => false];

      return Dropdown::showFromArray($name, $monthsarray, $opt);
   }


   /**
    * @param       $prefered_group
    * @param       $opt
    * @param false $params
    * @param       $entity
    * @param       $userid
    *
    * @return array|mixed
    */
   static public function getRequesterGroup($prefered_group, $opt, $entity, $userid, $params = false) {
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
