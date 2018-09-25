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
    * @global type $DB
    *
    * @param type  $widgetId
    *
    * @return string, the widget 'name'
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
    * @global type  $DB
    *
    * @param string $widgetName
    *
    * @return the widgets_id if found, NULL otherwise
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
    * @global type  $DB
    *
    * @param string $widgetName
    *
    * @return TRUE if the new widget name has been added, FALSE otherwise
    */
   function saveWidget($widgetName) {

      if (isset($widgetName) && $widgetName !== "") {
         //            $widgettmp = preg_replace( '/[^[:alnum:]_]+/', '', $widgetName );
         //Not really good regex
         $widgettmp = preg_match('#[^.0-9a-z]+#i', $widgetName, $matches);

         if ($widgettmp == 1) {
            Toolbox::logDebug("'$widgetName' can't be used as a widget Name, '$matches[0]' is not a valid character ");
            return false;
         }

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
    * @return array
    */
   static function getWidgetList() {

      $list       = new PluginMydashboardWidgetlist();
      $widgetlist = $list->getList();
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
   static function getWidget($id, $opt = []) {
      global $CFG_GLPI;
      $class   = "bt-col-md-11";
      $widgets = self::getWidgetList();

      if (isset($widgets[$id])) {
         return self::loadWidget($widgets[$id]["class"], $widgets[$id]["id"], $widgets[$id]["parent"], $class, $opt);
      }
      $message = __('No data available', 'mydashboard');
      if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
         $message .= " - " . $id;
      }
      $msg = "<div class='center'><br><br>";
      $msg .= Html::image($CFG_GLPI["root_doc"] . "/pics/warning.png", ['alt' => __('Warning')]);
      $msg .= "<br><br><span class='b'>$message</span></div>";

      return $msg;
   }

   /**
    * @param $id
    *
    * @return array|string
    */
   static function getWidgetOptions($id) {
      global $CFG_GLPI;
      $widgets = self::getWidgetList();

      if (isset($widgets[$id])) {
         return self::getAllOptions($widgets[$id]["class"], $widgets[$id]["id"], []);
      }
      $message = __('No data available', 'mydashboard');
      if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
         $message .= " - " . $id;
      }
      $msg = "<div class='center'><br><br>";
      $msg .= Html::image($CFG_GLPI["root_doc"] . "/pics/warning.png", ['alt' => __('Warning')]);
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

         $classname   = $classname;
         $classobject = getItemForItemtype($classname);
         if ($classobject && method_exists($classobject, "getWidgetContentForItem")) {
            $widget = $classobject->getWidgetContentForItem($widgetindex, $opt);
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
               $scripts = implode($scripts, "");

               //            $jsondatas = $widget->getJSonDatas();
               //            $widgetContent = json_decode($jsondatas);
               //            if(!isset($widgetContent)) $widgetContent = $jsondatas;
               //We prepare a "JSon object" compatible with sDashboard
               $widgetTitle = $widget->getWidgetTitle();
               //               if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
               //                  $widgetTitle .= " (" . $widget->getWidgetId() . ")";
               //               }
               $json =
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
               //safeJson because refreshCallBack must be a javascript function not a string,
               // not a string, but a function in a json object is not valid
               //               Toolbox::logDebug($json);
               $menu  = new PluginMydashboardMenu();
               $views = $menu->getViewNames();
               $view  = -1;
               if (is_numeric($parent)) {
                  $view = $views[$parent];
               }

               $type    = $json['widgetType'];
               $title   = $json['widgetTitle'];
               $comment = $json['widgetComment'];
               if (isset($view) && $view != -1) {
                  $title .= "<span class='plugin_mydashboard_discret'>&nbsp;-&nbsp;" . $view . "</span>";
               }

               $json  = PluginMydashboardHelper::safeJson($json);
               $datas = json_decode($jsondata, true);

               if ($type == "table") {
                  $opt = $widget->getOptions();
                  //                  Toolbox::logDebug($opt);
                  $order = json_encode([0, 'asc']);
                  if (isset($opt['bSort'])) {
                     $order = json_encode($opt['bSort']);
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
                  $rand                  = mt_rand();
                  $languages             = json_encode($menu->getJsLanguages("datatables"));
                  $display_count_on_home = $CFG_GLPI['display_count_on_home'];
                  $user                  = new User();
                  if ($user->getFromDB(Session::getLoginUserID())) {
                     $user->computePreferences();
                     $display_count_on_home = $user->fields['display_count_on_home'];
                  }
                  $widgetdisplay = "<script type='text/javascript'>
               //         setTimeout(function () {
                           $.fn.dataTable.moment('$mask');
                           $('#$widgetindex$rand').DataTable(
                               {
                               'iDisplayLength' : $display_count_on_home,
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
                          buttons: [
                              'colvis',
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
               //               if (Session::haveRight("plugin_servicecatalog_view", CREATE)
               //                   || Session::haveRight("plugin_servicecatalog_defaultview", CREATE)) {
               //                  $delclass = "delclass";
               //               }

               $widgetdisplay .= "<div id='$widgetindex'>";
               $widgetdisplay .= "<div class=\"bt-row $delclass\">";
               $widgetdisplay .= "<div class=\"bt-feature $class \">";
               $widgetdisplay .= "<h5 class=\"bt-title-divider\">";
               $widgetdisplay .= "<span>";
               $widgetdisplay .= $title;
               if ($comment != "") {
                  $widgetdisplay .= "&nbsp;";
                  $opt           = ['awesome-class' => 'fa-info-circle',
                                    'display'       => false];
                  $widgetdisplay .= Html::showToolTip($comment, $opt);
               }
               $widgetdisplay .= "</span>";
               //         $widget .= "<small>" . __('A comment') . "</small>";
               $widgetdisplay .= "</h5>";
               $widgetdisplay .= "<div id=\"display-sc\">";

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
               return $widgetdisplay;
            } else {
               $widgetdisplay = $widgetindex . " : " . __('No data available', 'mydashboard');
               return $widgetdisplay;
            }
         }
      }

   }


   /**
    * @param       $classname
    * @param       $widgetindex
    * @param array $opt
    *
    * @return array
    */
   static function getAllOptions($classname, $widgetindex, $opt = []) {

      if (isset($classname) && isset($widgetindex)) {

         $classname   = $classname;
         $classobject = getItemForItemtype($classname);
         if ($classobject && method_exists($classobject, "getWidgetContentForItem")) {
            $widget = $classobject->getWidgetContentForItem($widgetindex, $opt);
            if (isset($widget) && ($widget instanceof PluginMydashboardModule)) {
               $json =
                  [
                     "enableRefresh" => json_decode($widget->getWidgetEnableRefresh()),
                  ];
               return $json;
            }
         }
      }
   }

   /**
    * @param $class
    *
    * @return string
    */
   static function getWidgetMydashboardAlert($class, $hidewidget = false) {

      if ($hidewidget == true && PluginMydashboardAlert::countForAlerts(0, 0) < 1) {
         $display = false;
         return $display;
      }
      $delclass = "";
      //      if (Session::haveRight("plugin_servicecatalog_view", CREATE)
      //          || Session::haveRight("plugin_servicecatalog_defaultview", CREATE)) {
      //         $delclass = "delclass";
      //      }
      $display = "<div id='gs4' class=\"bt-row $delclass\">";
      $display .= "<div class=\"bt-feature $class \">";
      $display .= "<h3 class=\"bt-title-divider\">";
      $display .= "<span>";
      $display .= __('Network Monitoring', 'mydashboard');
      $display .= "</span>";
      $display .= "<small>" . __('A network alert can impact you and will avoid creating a ticket', 'mydashboard') . "</small>";
      $display .= "</h3>";
      $display .= "<div id=\"display-sc\">";
      if (PluginMydashboardAlert::countForAlerts(0, 0) > 0) {
         $alerts  = new PluginMydashboardAlert();
         $display .= $alerts->getAlertList(0);
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
   static function getWidgetMydashboardMaintenance($class, $hidewidget = false) {

      if ($hidewidget == true && PluginMydashboardAlert::countForAlerts(0, 1) < 1) {
         $display = false;
         return $display;
      }

      $delclass = "";
      //      if (Session::haveRight("plugin_servicecatalog_view", CREATE)
      //          || Session::haveRight("plugin_servicecatalog_defaultview", CREATE)) {
      //         $delclass = "delclass";
      //      }
      $display = "<div id='gs5' class=\"bt-row $delclass\">";
      $display .= "<div class=\"bt-feature $class \">";
      $display .= "<h3 class=\"bt-title-divider\">";
      $display .= "<span>";
      $display .= _n('Scheduled maintenance', 'Scheduled maintenances', 2, 'mydashboard');
      $display .= "</span>";
      //      $display .= "<small>" . __('A network maintenance can impact you and will avoid creating a ticket', 'mydashboard') . "</small>";
      $display .= "</h3>";
      $display .= "<div id=\"display-sc\">";
      if (PluginMydashboardAlert::countForAlerts(0, 1) > 0) {
         $alerts  = new PluginMydashboardAlert();
         $display .= $alerts->getMaintenanceList();
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
    */
   static function getWidgetMydashboardInformation($class, $hidewidget = false) {

      if ($hidewidget == true && PluginMydashboardAlert::countForAlerts(0, 2) < 1) {
         $display = false;
         return $display;
      }

      $delclass = "";
      //      if (Session::haveRight("plugin_servicecatalog_view", CREATE)
      //          || Session::haveRight("plugin_servicecatalog_defaultview", CREATE)) {
      //         $delclass = "delclass";
      //      }
      $display = "<div id='gs6' class=\"bt-row $delclass\">";
      $display .= "<div class=\"bt-feature $class \">";
      $display .= "<h3 class=\"bt-title-divider\">";
      $display .= "<span>";
      $display .= _n('Information', 'Informations', 2, 'mydashboard');
      $display .= "</span>";
      $display .= "</h3>";
      $display .= "<div id='display-sc'>";
      if (PluginMydashboardAlert::countForAlerts(0, 2) > 0) {

         $alerts  = new PluginMydashboardAlert();
         $display .= $alerts->getInformationList();

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
}
