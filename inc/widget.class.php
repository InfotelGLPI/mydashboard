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
 * Class PluginMydashboardWidget
 */
class PluginMydashboardWidget extends CommonDBTM {

   static $rightname = "plugin_mydashboard";

   /**
    * @param int $nb
    *
    * @return translated
    */
   static function getTypeName($nb = 0) {

      return __('Widget management', 'mydashboard');
   }

   /**
    * Get the widget name with his id
    *
    * @param type  $widgetId
    *
    * @return string, the widget 'name'
    * @global type $DB
    *
    */
   function getWidgetNameById($widgetId) {

      if ($this->getFromDBByCrit(['id' => $widgetId]) === false) {
         return null;
      } else {
         return isset($this->fields['name']) ? $this->fields['name'] : null;
      }
   }

   /**
    * Get the widgets_id by its 'name'
    *
    * @param string $widgetName
    *
    * @return the widgets_id if found, NULL otherwise
    * @global type  $DB
    *
    */
   function getWidgetIdByName($widgetName) {

      unset($this->fields);
      if ($this->getFromDBByCrit(['name' => $widgetName]) === false) {
         return null;
      } else {
         return isset($this->fields['id']) ? $this->fields['id'] : null;
      }
   }

   /**
    * Save a new widget Name
    *
    * @param string $widgetName
    *
    * @return TRUE if the new widget name has been added, FALSE otherwise
    * @global type  $DB
    *
    */
   function saveWidget($widgetName) {

      if (isset($widgetName) && $widgetName !== "") {
         //            $widgettmp = preg_replace( '/[^[:alnum:]_]+/', '', $widgetName );
         //Not really good regex
         //         $widgettmp = preg_match('#[^.0-9a-z]+#i', $widgetName, $matches);
         //
         //         if ($widgettmp == 1) {
         //            Toolbox::logDebug("'$widgetName' can't be used as a widget Name, '$matches[0]' is not a valid character ");
         //            return false;
         //         }

         $this->fields["id"] = null;
         $id                 = $this->getWidgetIdByName($widgetName);

         if (!isset($id)) {
            $this->fields = [];
            $this->add(["name" => $widgetName]);
         }
         return true;
      } else {
         return false;
      }
   }


   /**
    *
    */
   function migrateWidgets() {

      $dbu     = new DbUtils();
      $reports = $dbu->getAllDataFromTable($this->getTable());
      foreach ($reports as $report) {
         $name = $report['name'];
         if (strpos($report['name'], "PluginMydashboardInfotel") !== false && strpos($report['name'], "PluginMydashboardInfotelcw") === false) {
            $widgettmp = preg_match_all('!\d+!', $name, $matches);
            if ($widgettmp == 1) {
               $widgetName = "";
               foreach ($matches[0] as $k => $v) {
                  if (in_array($v, PluginMydashboardReports_Bar::$reports)) {
                     $widgetName = "PluginMydashboardReports_Bar" . $v;
                  }
                  if (in_array($v, PluginMydashboardReports_Pie::$reports)) {
                     $widgetName = "PluginMydashboardReports_Pie" . $v;
                  }
                  if (in_array($v, PluginMydashboardReports_Table::$reports)) {
                     $widgetName = "PluginMydashboardReports_Table" . $v;
                  }
                  if (in_array($v, PluginMydashboardReports_Line::$reports)) {
                     $widgetName = "PluginMydashboardReports_Line" . $v;
                  }
                  if (in_array($v, PluginMydashboardReports_Map::$reports)) {
                     $widgetName = "PluginMydashboardReports_Map" . $v;
                  }
                  if ($widgetName != "") {
                     $this->update(["id" => $report['id'], "name" => $widgetName]);
                  }
               }
            }
         }
         if (strpos($report['name'], "PluginMydashboardInfotelcw") !== false) {
            $widgettmp = preg_match_all('!\d+!', $name, $matches);
            if ($widgettmp == 1) {
               foreach ($matches[0] as $k => $v) {
                  $widgetName = "PluginMydashboardReports_Custom" . $v;
                  if ($widgetName != "") {
                     $this->update(["id" => $report['id'], "name" => $widgetName]);
                  }
               }
            }
         }
      }
   }

   /**
    * @return array
    */
   static function getWidgetList($preload = false) {

      $list = new PluginMydashboardWidgetlist();
      //Load widgets
      $widgetlist = $list->getList(true, -1, "central", $preload);
      $i          = 1;
      $self       = new self();
      $widgets    = [];
      foreach ($widgetlist as $plugin => $widgetclasses) {
         foreach ($widgetclasses as $widgetclass => $list) {
            if (is_array($list)) {
               foreach ($list as $k => $namelist) {
                  if (is_array($namelist)) {
                     foreach ($namelist as $idl => $val) {
                        $id                  = $self->getWidgetIdByName($idl);
                        $widgets['gs' . $id] = ["class" => $widgetclass, "id" => $idl, "parent" => $k];
                        $i++;
                     }
                  } else {
                     $id                  = $self->getWidgetIdByName($k);
                     $widgets['gs' . $id] = ["class" => $widgetclass, "id" => $k, "parent" => $widgetclass];
                     $i++;
                  }
               }
            } else {
               $id                  = $self->getWidgetIdByName($widgetclass);
               $widgets['gs' . $id] = ["class" => $widgetclasses, "id" => $widgetclass];
               $i++;
            }

         }
      }
      return $widgets;
   }

   /**
    * Returns the widget with the ID
    *
    * @param       $id
    * @param array $opt
    *
    * @return string
    */
   static function getWidget($id, $widgets, $opt = []) {
      $class = "bt-col-md-11";

      if (isset($widgets[$id])) {
         return self::loadWidget($widgets[$id]["class"], $widgets[$id]["id"], $widgets[$id]["parent"], $class, $opt);
      }
      $message = __('No data available', 'mydashboard');
      if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
         $message .= " - " . $id;
      }
      $msg = "<div class='center alert alert-warning' role='alert'><br><br>";
      $msg .= "<i style='color:orange' class='fas fa-exclamation-triangle fa-3x'></i>";
      $msg .= "<br><br><span class='b'>$message</span></div>";

      return $msg;
   }


   /**
    * @param $id
    *
    * @return bool
    */
   static function getGsID($id) {

      $widgets = self::getWidgetList();

      foreach ($widgets as $gs => $widgetclasses) {
         $gslist[$widgetclasses['id']] = $gs;
      }

      if (isset($gslist[$id])) {
         return $gslist[$id];
      }
      return false;
   }


   /**
    * @param       $classname
    * @param       $widgetindex
    * @param       $parent
    * @param       $class
    * @param array $opt
    *
    * @return string
    */
   static function loadWidget($classname, $widgetindex, $parent, $class, $opt = []) {
      global $CFG_GLPI;

      if (isset($classname) && isset($widgetindex)) {
         $classobject = getItemForItemtype($classname);
         if ($classobject && method_exists($classobject, "getWidgetContentForItem")) {
            if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
               $TIMER = new Timer();
               $TIMER->start();
            }
            $widget = $classobject->getWidgetContentForItem($widgetindex, $opt);
            if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
               $loadwidget        = $TIMER->getTime();
               $displayloadwidget = "";
            }

            if (isset($widget) && ($widget instanceof PluginMydashboardModule)) {

               $widget->setWidgetId($widgetindex);
               //Then its Html content
               $htmlContent = $widget->getWidgetHtmlContent();

               if ($widget->getWidgetIsOnlyHTML()) {
                  $htmlContent = "";
               }

               //when we get jsondata some checkings and modification can be done by the widget class
               //For example Datatable add some scripts to adapt the table to the template
               $jsondata = $widget->getJSonDatas();

               //Then its scripts (non evaluated, have to be evaluated client-side)
               $scripts = $widget->getWidgetScripts();

               //We prepare a "JSon object" compatible with sDashboard
               $widgetTitle                                                         = $widget->getWidgetTitle();
               $json                                                                =
                  [
                     "widgetTitle"     => $widgetTitle,
                     "widgetComment"   => $widget->getWidgetComment(),
                     "widgetId"        => $widget->getWidgetId(),
                     "widgetType"      => $widget->getWidgetType(),
                     "widgetContent"   => "%widgetContent%",
                     "enableRefresh"   => json_decode($widget->getWidgetEnableRefresh()),
                     "refreshCallBack" => "function(){return mydashboard.getWidgetData('" . PluginMydashboardMenu::DASHBOARD_NAME . "','$classname', '" . $widget->getWidgetId() . "');}",
                     "html"            => $htmlContent,
                     "scripts"         => $scripts,
                     //                        "_glpi_csrf_token" => Session::getNewCSRFToken()
                  ];
               $_SESSION["glpi_plugin_mydashboard_widgets"][$widget->getWidgetId()] = json_decode($widget->getWidgetEnableRefresh());
               //safeJson because refreshCallBack must be a javascript function not a string,
               // not a string, but a function in a json object is not valid
               $menu  = new PluginMydashboardMenu();
               $views = $menu->getViewNames();
               $view  = -1;
               if (is_numeric($parent)) {
                  $view = $views[$parent];
               }

               $type  = $json['widgetType'];
               $title = $json['widgetTitle'];

               $comment = $json['widgetComment'];
               if (isset($view) && $view != -1) {
                  $title .= "<span class='plugin_mydashboard_discret'>&nbsp;-&nbsp;" . $view . "</span>";
               }

               //               $json  = PluginMydashboardHelper::safeJson($json);
               $datas = json_decode($jsondata, true);

               if ($type == "table") {
                  $opt = $widget->getOptions();
                  //                  Toolbox::logDebug($opt);
                  $order = json_encode([[0, 'asc']]);
                  if (isset($opt['bSort'])) {
                     $order = json_encode([$opt['bSort']]);
                  }
                  $defs = json_encode([]);
                  if (isset($opt['bDef'])) {
                     $defs = json_encode($opt['bDef']);
                  }

                  $dateformat = "D";
                  $mask       = 'MM-DD-YYYY';
                  if (isset($opt['bDate'])) {
                     $dateformat = $opt['bDate'][0];
                  }

                  if ($dateformat == "DHS") {
                     if (!isset($_SESSION["glpidate_format"])) {
                        $_SESSION["glpidate_format"] = 0;
                     }
                     $format = $_SESSION["glpidate_format"];
                     switch ($format) {
                        case 1 : // DD-MM-YYYY
                           $mask = 'DD-MM-YYYY HH:mm:SS';
                           break;
                        case 2 : // MM-DD-YYYY
                           $mask = 'MM-DD-YYYY HH:mm:SS';
                           break;
                     }

                  } else if ($dateformat == "DH") {
                     if (!isset($_SESSION["glpidate_format"])) {
                        $_SESSION["glpidate_format"] = 0;
                     }
                     $format = $_SESSION["glpidate_format"];
                     switch ($format) {
                        case 1 : // DD-MM-YYYY
                           $mask = 'DD-MM-YYYY HH:mm';
                           break;
                        case 2 : // MM-DD-YYYY
                           $mask = 'MM-DD-YYYY HH:mm';
                           break;
                     }

                  } else if ($dateformat == "D") {
                     if (!isset($_SESSION["glpidate_format"])) {
                        $_SESSION["glpidate_format"] = 0;
                     }
                     $format = $_SESSION["glpidate_format"];
                     switch ($format) {
                        case 1 : // DD-MM-YYYY
                           $mask = 'DD-MM-YYYY';
                           break;
                        case 2 : // MM-DD-YYYY
                           $mask = 'MM-DD-YYYY';
                           break;
                     }
                  }
                  $rand      = mt_rand();
                  $languages = json_encode($menu->getJsLanguages("datatables"));
                  //                  $display_count_on_home = intval($_SESSION['glpidisplay_count_on_home']);

                  $lengthMenulangs = [__('5 rows', 'mydashboard'),
                                      __('10 rows', 'mydashboard'),
                                      __('25 rows', 'mydashboard'),
                                      __('50 rows', 'mydashboard'),
                                      __('Show all', 'mydashboard'),
                  ];
                  $lengthMenulangs = json_encode($lengthMenulangs);
                  $widgetdisplay   = "<script type='text/javascript'>
               //         setTimeout(function () {
                           $.fn.dataTable.moment('$mask');
                           $('#$widgetindex$rand').dataTable(
                               {
                                stateSave: true,
                                'stateSaveParams': function (settings, data) {
                                  data.gsId = '$widgetindex';
                                  if (typeof document.getElementsByName('profiles_id')[0] !== 'undefined') {
                                   data.profiles_id = document.getElementsByName('profiles_id')[0].value;
                                 }
                                }, 
                                'stateSaveCallback': function (settings, data) {
                                    // Send an Ajax request to the server with the state object
                                    
                                    $.ajax({
                                       'url': '{$CFG_GLPI['root_doc']}/plugins/mydashboard/ajax/state_save.php',
                                       'data': data,
                                       'dataType': 'json',
                                       'type': 'POST',
                                       'success': function(response) {},
                                       'error': function(response) {}
                                    });
                               },       
                               'stateLoadCallback': function (settings, callback) {
                                 profiles_id='';
                                 if (typeof document.getElementsByName('profiles_id')[0] !== 'undefined') {
                                   profiles_id = document.getElementsByName('profiles_id')[0].value;
                                 }
                                $.ajax({
                                    url: '{$CFG_GLPI['root_doc']}/plugins/mydashboard/ajax/state_load.php?gsId={$widgetindex}&profiles_id='+profiles_id,
                                    dataType: 'json',
                                    success: function (json) {                               
                                      //JSON parse the saved filter and set the time equal to now.          
                                      json.time = +new Date();                                         
                                      callback(json);
                                    },
                                    error: function () {
                                        callback(null);
                                    }
                                })
                               },
                               'order': $order,
                               'colReorder': true,
                               'columnDefs' :$defs,
                               rowReorder: {
                                 selector: 'td:nth-child(2)'
                               },
                               responsive: true,
                              'language': $languages,
                              dom: 'Bfrtip',
                              select: true,
                              lengthMenu: [
                                   [ 5, 10, 25, 50, -1 ],
                                   $lengthMenulangs
                               ],
                              buttons: [
                                 'colvis',
                                 'pageLength',
                                 {
                                  extend: 'collection',
                                  text: 'Export',
                                  buttons: [
                                      'copy',
                                      'excel',
                                      'csv',
                                      'pdf',
                                      'print',
                                  ]
                              }
                          ]
                       }
                       );

                       </script>";
               } else {
                  $widgetdisplay = "";
               }
               $delclass = "";

               $widgetdisplay .= "<div id='$widgetindex'>";
               $widgetdisplay .= "<div class=\"bt-row $delclass\">";
               $widgetdisplay .= "<div class=\"bt-feature $class \" style='width: 96%'>";

               if ($widget->getTitleVisibility()) {
                  $widgetdisplay .= "<h5>";
                  $titletype = $widget->getWidgetHeaderType();
                  if (!empty($titletype)) {
                     $titletype = $widget->getWidgetHeaderType();
                  } else {
                     $titletype = 'info';
                  }
                  $widgetdisplay .= "<div class='alert alert-$titletype' role='alert'>";
                  $widgetdisplay .= $title;
                  if ($comment != "") {
                     $widgetdisplay .= "&nbsp;";
                     $opt           = ['awesome-class' => 'fa-info-circle',
                                       'display'       => false];
                     $widgetdisplay .= Html::showToolTip($comment, $opt);
                  }
                  $widgetdisplay .= "</div>";
                  //         $widget .= "<small>" . __('A comment') . "</small>";
                  $widgetdisplay .= "</h5>";
               }

               $widgetdisplay .= "<div id=\"display-sc\">";

               // HEADER
               $widgetdisplay .= $widget->getWidgetHeader();

               if ($type == "table") {
                  $head = $datas['aoColumns'];
                  $data = $datas['aaData'];
                  $nb   = 0;
                  if (($nb_data = reset($data)) == !false) {
                     $nb = count($nb_data);
                  }

                  $widgetdisplay .= '<table id="' . $widgetindex . $rand . '" class="display" cellspacing="0" width="100%">';
                  $widgetdisplay .= '<thead>';
                  $widgetdisplay .= '<tr>';
                  foreach ($head as $k => $th) {
                     $widgetdisplay .= '<th>' . $th['sTitle'] . '</th>';
                  }
                  $widgetdisplay .= '</tr>';
                  $widgetdisplay .= '</thead><tfoot><tr>';
                  foreach ($head as $k => $th) {
                     $widgetdisplay .= '<th>' . $th['sTitle'] . '</th>';
                  }
                  $widgetdisplay .= '</tr></tfoot>';
                  $widgetdisplay .= ' <tbody>';

                  foreach ($data as $k => $v) {
                     $widgetdisplay .= '<tr>';
                     for ($i = 0; $i < $nb; $i++) {
                        $widgetdisplay .= '<td>' . $v[$i] . '</td>';
                     }
                     $widgetdisplay .= '</tr>';
                  }
                  $widgetdisplay .= '</tbody></table>';

                  $widgetdisplay .= $widget->getWidgetHtmlContent();
               } else if ($type == "html") {
                  $widgetdisplay .= $datas;
               }

               $widgetdisplay .= "</div>";
               $widgetdisplay .= "</div>";
               $widgetdisplay .= "</div>";
               $widgetdisplay .= "</div>";

               if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
                  $displayloadwidget = "Load widget : " . $loadwidget . "<br>";
                  $widgetdisplay     .= $displayloadwidget;
               }
               return $widgetdisplay;
            } else {
               $widgetdisplay = $widgetindex . " : " . __('No data available', 'mydashboard');
               return $widgetdisplay;
            }
         }
      }
   }

   /**
    * @param $class
    *
    * @return string
    */
   static function getWidgetMydashboardAlert($class, $hidewidget = false, $itilcategories_id = []) {

      if ($hidewidget == true && PluginMydashboardAlert::countForAlerts(0, 0, $itilcategories_id) < 1) {
         $display = false;
         return $display;
      }
      $delclass = "";
      $addclass = "";
      if (count($itilcategories_id) > 0) {
         $addclass = "details";
      }
      $display  = "<div id='gs4' class=\"bt-row $delclass $addclass\">";
      $display  .= "<div class=\"bt-feature $class \">";
      $display  .= "<h3>";
      $display  .= "<div class='alert alert-danger' role='alert'>";
      $config   = new PluginMydashboardConfig();
      $config->getFromDB(1);
      $display .= PluginMydashboardConfig::displayField($config, 'title_alerts_widget');
      $display .= "</div>";
      $display .= "</h3>";
      //      $display  .= "<div align='left' style='margin: 5px;'><small style='font-size: 11px;'>";
      //      $display  .= __('A network alert can impact you and will avoid creating a ticket', 'mydashboard') . "</small></div>";
      $display .= "<div id=\"display-sc\">";
      if (PluginMydashboardAlert::countForAlerts(0, 0, $itilcategories_id) > 0) {
         $alerts  = new PluginMydashboardAlert();
         $display .= $alerts->getAlertList(0, $itilcategories_id);
      } else {
         $display .= "<div align='center'><h3><span class ='alert-color'>";
         $display .= __("No problem detected", "mydashboard");
         $display .= "</span></h3></div>";
      }
      $display .= "</div>";
      $display .= "</div>";
      $display .= "</div>";

      return $display;
   }

   /**
    * @param $class
    *
    * @return string
    */
   static function getWidgetMydashboardMaintenance($class, $hidewidget = false, $itilcategories_id = []) {

      if ($hidewidget == true && PluginMydashboardAlert::countForAlerts(0, 1, $itilcategories_id) < 1) {
         $display = false;
         return $display;
      }

      $delclass = "";
      $addclass = "";
      if (count($itilcategories_id) > 0) {
         $addclass = "details";
      }
      $display  = "<div id='gs5' class=\"bt-row $delclass $addclass\">";
      $display  .= "<div class=\"bt-feature $class \">";
      $display  .= "<h3>";
      $display  .= "<div class='alert alert-warning' role='alert'>";
      $config   = new PluginMydashboardConfig();
      $config->getFromDB(1);
      $display .= PluginMydashboardConfig::displayField($config, 'title_maintenances_widget');
      $display .= "</span>";
      //      $display .= "<small>" . __('A network maintenance can impact you and will avoid creating a ticket', 'mydashboard') . "</small>";
      $display .= "</div>";
      $display .= "<div id=\"display-sc\">";
      if (PluginMydashboardAlert::countForAlerts(0, 1, $itilcategories_id) > 0) {
         $alerts  = new PluginMydashboardAlert();
         $display .= $alerts->getMaintenanceList($itilcategories_id);
      } else {
         $display .= "<div align='center'><h3><span class ='alert-color'>";
         $display .= __("No scheduled maintenance", "mydashboard");
         $display .= "</span></h3></div>";
      }
      $display .= "</div>";
      $display .= "</div>";
      $display .= "</div>";

      return $display;
   }

   /**
    * @param $class
    *
    * @return string
    * @throws \GlpitestSQLError
    */
   static function getWidgetMydashboardInformation($class, $hidewidget = false, $itilcategories_id = []) {

      if ($hidewidget == true && PluginMydashboardAlert::countForAlerts(0, 2, $itilcategories_id) < 1) {
         $display = false;
         return $display;
      }

      $delclass = "";
      $addclass = "";
      if (count($itilcategories_id) > 0) {
         $addclass = "details";
      }
      $display  = "<div id='gs6' class=\"bt-row $delclass $addclass\">";
      $display  .= "<div class=\"bt-feature $class \">";
      $display  .= "<h3>";
      $display  .= "<div class='alert alert-info' role='alert'>";
      $config   = new PluginMydashboardConfig();
      $config->getFromDB(1);
      $display .= PluginMydashboardConfig::displayField($config, 'title_informations_widget');
      $display .= "</div>";
      $display .= "</h3>";
      $display .= "<div id='display-sc'>";
      if (PluginMydashboardAlert::countForAlerts(0, 2, $itilcategories_id) > 0) {

         $alerts  = new PluginMydashboardAlert();
         $display .= $alerts->getInformationList($itilcategories_id);

      } else {
         $display .= "<div align='center'><h3><span class ='alert-color'>";
         $display .= __("No informations founded", "mydashboard");
         $display .= "</span></h3></div>";
      }
      $display .= "</div>";
      $display .= "</div>";
      $display .= "</div>";

      return $display;
   }

   /**
    * @param $class
    *
    * @return string
    */
   static function getWidgetMydashboardEquipments($class, $fromsc) {
      global $CFG_GLPI;

      $delclass = "";
      //      $display  = "<div id='gs17' class=\"bt-row $delclass\">";
      $display = "";
      if ($fromsc == true) {
         $display .= "<div class=\"bt-feature $class\">";
         $display .= "<h3>";
         $display .= "<div class='alert alert-light' role='alert'>";
         $display .= __('Your equipments', 'mydashboard');
         $display .= "</div>";
         $display .= "</h3>";
         $display .= "</div>";
      }
      $allUsedItemsForUser = self::getAllUsedItemsForUser();

      if (count($allUsedItemsForUser) > 0) {
         if ($fromsc == true) {
            $class ="";
            $config = new PluginServicecatalogConfig();
            if ($config->getLayout() == PluginServicecatalogConfig::THUMBNAIL) {
               $class = "visitedchildbg widgetrow";
            }
            $display .= "<div class=\"bt-feature bt-col-md-12 count-title\">";
         }
         foreach ($allUsedItemsForUser as $itemtype => $used_items) {

            $item = getItemForItemtype($itemtype);


            //            if ($i % 2 == 0 && $nb > 1) {
            $class  = "";
            if ($fromsc == true) {
               $config = new PluginServicecatalogConfig();
               if ($config->getLayout() == PluginServicecatalogConfig::THUMBNAIL) {
                  $class = "visitedchildbg widgetrow";
               }
            }
            $display .= "<div class=\"bt-feature bt-col-md-3 center equip-text $class\">";
            //            }
            //            if ($nb == 1) {
            //               $display .= "<div class=\"bt-feature bt-col-md-6 center equip-text\">";
            //            }
            $i  = 0;
            $nb = count($used_items);
            foreach ($used_items as $item_datas) {

               //               if ($i % 2 == 0 && $nb > 1) {
               //                  $display .= "<div class=\"bt-col-md-6 center\">";
               //               }
               if ($nb == 1) {
                  $display .= "<div class=\"equip-item\">";
               } else {
                  $display .= "<div class=\"equip-item\">";
               }

               //               $display .= "<div class=\"nbstock\" style=\"color:$color\">";
               //               $display .= "<a style='color:$color' target='_blank' href=\"" . $link . "\" title='" .$item_datas['name']  . "'>";

               //               $display .= "<h4>";
               //               $display .= "<span class=\"counter count-number\" id=\"stock_$itemtype\"></span>";
               //                     $table .= " / <span class=\"counter count-number\" id=\"all_$nb\"></span>";
               //               $display .= "<p class=\"count-text \">";

               $display .= "</br>";

               //               $types = ['Computer', 'Monitor','Peripheral','Phone','Printer','SoftwareLicense','PluginBadgesBadge'];
               if ($itemtype == 'Computer') {
                  $icon = 'fas fa-laptop';
               } else if ($itemtype == 'Monitor') {
                  $icon = 'fas fa-desktop';
               } else if ($itemtype == 'Peripheral') {
                  $icon = 'fas fa-hdd';
               } else if ($itemtype == 'Phone') {
                  $icon = 'fas fa-mobile-alt';
               } else if ($itemtype == 'Printer') {
                  $icon = 'fas fa-print';
               } else if ($itemtype == 'SoftwareLicense') {
                  $icon = 'fas fa-award';
               } else if ($itemtype == 'PluginBadgesBadge') {
                  $icon = 'far fa-id-badge';
               }

               if ($item->canView() && isset($_SESSION['glpiactiveprofile']['interface'])
                   && Session::getCurrentInterface() == 'central') {
                  $display .= "<a href='" . $item::getFormURL() . "?id=" . $item_datas['id'] . "' target='_blank'>";
               }


               $display .= "<i class=\"$icon md-fa-2x fa-border\"></i>";
               $display .= "</br>";
               $display .= $item_datas['name'];
               if ($item->canView() && isset($_SESSION['glpiactiveprofile']['interface'])
                   && Session::getCurrentInterface() == 'central') {
                  $display .= "</a>";
               }
               $display .= "</br>";
               $display .= $item->getTypeName();
               $display .= "</br>";
               //               $script .= "$('#stock_$itemtype').countup($nb);";

               $i++;
               //               if (($i == $nb) && (($nb % 2) != 0) && ($nb > 1)) {

               //               if ($item_datas['id']
               //                   && Ticket::isPossibleToAssignType($itemtype)
               //                   && Ticket::canCreate()
               //                   && (!isset($item->fields['is_template']) || ($item->fields['is_template'] == 0))) {
               //                  $link = Html::showSimpleForm(Ticket::getFormURL(),
               //                                       '_add_fromitem',
               //                                       __('New ticket for this item...'),
               //                                       ['itemtype' => $itemtype,
               //                                        'items_id' => $item_datas['id']],
               //                     'fa-plus-circle');
               //                  $display .= $link;
               //               }

               $display .= "</div>";
               //               }
            }

            $display .= "</div>";
         }
         //         $display .= "<script type='text/javascript'>
         //                         $(function(){
         //                            $script;
         //                         });
         //                  </script>";
         //                  $display .= "</div>";
      } else {
         $display .= "<div class=\"bt-feature bt-col-md-11 equip-item center\">";
         $display .= "<h3><span class ='alert-color'>";
         $display .= __("No equipments founded", "mydashboard");
         $display .= "</span></h3></div>";
      }
      if ($fromsc == true) {
         $display .= "</div>";
      }
      return $display;
   }

   /**
    * Get all used items for user
    *
    * @param ID of user
    *
    * @return array
    */
   static function getAllUsedItemsForUser() {
      $items = [];

      $types = ['Computer',
                'Monitor',
                'Peripheral',
                'Phone',
                'Printer',
                'SoftwareLicense',
                'PluginBadgesBadge'];

      $users_id = Session::getLoginUserID();
      foreach ($types as $itemtype) {
         if (!($item = getItemForItemtype($itemtype))) {
            continue;
         }
         $condition = ['users_id' => $users_id];
         if ($item->maybeTemplate()) {
            $condition['is_template'] = 0;
         }
         if ($item->maybeDeleted()) {
            $condition['is_deleted'] = 0;
         }
         $dbu       = new DbUtils();
         $condition += $dbu->getEntitiesRestrictCriteria(getTableForItemType($itemtype), '', '', true);

         $objects = $item->find($condition);

         $nb = count($objects);
         if ($nb > 0) {
            foreach ($objects as $object) {
               $items[$itemtype][] = $object;
            }
         }
      }
      return $items;
   }
}
