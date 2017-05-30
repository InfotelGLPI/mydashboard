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
 * Class PluginMydashboardAlert
 */
class PluginMydashboardAlert extends CommonDBTM
{


   /**
    * @param CommonGLPI $item
    * @param int $withtemplate
    * @return string|translated
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
   {
      if ($item->getType() == 'Reminder') {
         return _n('Alert', 'Alerts', 2, 'mydashboard');
      }
      return '';
   }

   /**
    * @param CommonGLPI $item
    * @param int $tabnum
    * @param int $withtemplate
    * @return bool
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
   {
      $alert = new self();
      if ($item->getType() == 'Reminder') {
         $alert->showForm($item);
      }
      return true;
   }

   /**
    * @return array
    */
   function getWidgetsForItem()
   {
      return array(
         _n('Alert', 'Alerts', 2, 'mydashboard') => array(
         $this->getType() . "1" => _n('Network alert', 'Network alerts', 2, 'mydashboard'),
         $this->getType() . "2" => _n('Scheduled maintenance', 'Scheduled maintenances', 2, 'mydashboard'),
         $this->getType() . "3" => _n('Information', 'Informations', 2, 'mydashboard'),
         )
      );
   }
   
   static function countForAlerts($public, $type)
   {
      global $DB;
      
      $now = date('Y-m-d H:i:s');
      $nb = 0;
      $restrict_visibility = "AND (`glpi_reminders`.`begin_view_date` IS NULL
                                    OR `glpi_reminders`.`begin_view_date` < '$now')
                              AND (`glpi_reminders`.`end_view_date` IS NULL
                                   OR `glpi_reminders`.`end_view_date` > '$now') ";

      $query = "SELECT COUNT(`glpi_reminders`.`id`) as cpt
                   FROM `glpi_reminders` "
         . Reminder::addVisibilityJoins()
         . "LEFT JOIN `glpi_plugin_mydashboard_alerts`"
         . "ON `glpi_reminders`.`id` = `glpi_plugin_mydashboard_alerts`.`reminders_id`"
         . "WHERE `glpi_plugin_mydashboard_alerts`.`type` = $type
                         $restrict_visibility ";
      
      if ($public == 0) {
         $query .= "AND " . Reminder::addVisibilityRestrict() . "";
      } else {
         $query .= "AND `glpi_plugin_mydashboard_alerts`.`is_public`";
      }


      $result = $DB->query($query);
      $ligne  = $DB->fetch_assoc($result);
      $nb = $ligne['cpt'];
      
      return $nb;
   }
   /**
    * @param $widgetId
    * @return PluginMydashboardHtml
    */
   function getWidgetContentForItem($widgetId)
   {
      switch ($widgetId) {
         case $this->getType() . "1":
            $widget = new PluginMydashboardHtml();
            $widget->setWidgetHtmlContent($this->getAlertList(0,0,true));
            $widget->setWidgetTitle(_n('Network alert', 'Network alerts', 2, 'mydashboard'));
            $widget->toggleWidgetRefresh();
            //$widget->toggleWidgetMaximize();
            return $widget;
            break;
         
         case $this->getType() . "2":
            $widget = new PluginMydashboardHtml();
            $datas = $this->getMaintenanceList(true);
            $widget->setWidgetHtmlContent(
               $datas
            );
            $widget->setWidgetTitle(_n('Scheduled maintenance', 'Scheduled maintenances', 2, 'mydashboard'));
            $widget->toggleWidgetRefresh();
            //$widget->toggleWidgetMaximize();
            return $widget;
            break;
         
         case $this->getType() . "3":
            $widget = new PluginMydashboardHtml();
            $datas = $this->getInformationList(true);
            $widget->setWidgetHtmlContent(
               $datas
            );
            $widget->setWidgetTitle(_n('Information', 'Informations', 2, 'mydashboard'));
            $widget->toggleWidgetRefresh();
            //$widget->toggleWidgetMaximize();
            return $widget;
            break;
      }
   }
   
   
   /**
    * @param int $public
    * @return string
    */
   static function getMaintenanceMessage($public = false)
   {
      if (self::countForAlerts($public, 1) > 0) {
         echo __('There is at least on planned scheduled maintenance. Please log on to see more', 'mydashboard');
      }
   }
   /**
    * @param int $public
    * @return string
    */
   function getMaintenanceList($widget = false)
   {
      global $DB, $CFG_GLPI;

      $now = date('Y-m-d H:i:s');
      $wl = "";
      $wl = $this->getPublicCSS();
      
      if (!$widget) {
         $wl .= "<div class='weather_block'>";
      }
      $restrict_user = '1';
      // Only personal on central so do not keep it
//      if ($_SESSION['glpiactiveprofile']['interface'] == 'central') {
//         $restrict_user = "`glpi_reminders`.`users_id` <> '".Session::getLoginUserID()."'";
//      }

      $restrict_visibility = "AND (`glpi_reminders`.`begin_view_date` IS NULL
                                    OR `glpi_reminders`.`begin_view_date` < '$now')
                              AND (`glpi_reminders`.`end_view_date` IS NULL
                                   OR `glpi_reminders`.`end_view_date` > '$now') ";

      $query = "SELECT `glpi_reminders`.`id`,
                       `glpi_reminders`.`name`,
                       `glpi_reminders`.`text`,
                       `glpi_reminders`.`date`,
                       `glpi_reminders`.`begin_view_date`,
                       `glpi_reminders`.`end_view_date`
                   FROM `glpi_reminders` "
         . Reminder::addVisibilityJoins()
         . "LEFT JOIN `" . $this->getTable() . "`"
         . "ON `glpi_reminders`.`id` = `" . $this->getTable() . "`.`reminders_id`"
         . "WHERE $restrict_user
                         $restrict_visibility ";

      $query .= "AND " . Reminder::addVisibilityRestrict() . "";
  
      $query .= "AND `" . $this->getTable() . "`.`type` = 1
                   ORDER BY `glpi_reminders`.`name`";

      
      $result = $DB->query($query);
      $nb = $DB->numrows($result);
      $list = array();
      $width ="";
      if ($nb) {
         
         if (!$widget) {
            $wl .= "<div id='maint-div'>";
            $wl .= "<ul>";
         }
         while ($row = $DB->fetch_array($result)) {
            
            if (!$widget) {
               $wl .= "<li>";
            }
            if ($widget) {
               $width = " width=75px";
            }
            $wl .= "<table width='100%'><tr>";
            $wl .= "<td rowspan='2' class='center' width='20%'>";
            $wl .= "<img src='" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/pics/travaux.png' $width />";
            $wl .= "</td>";
            $wl .= "<td valign='top'>";
            if (!$widget) {
               $wl .= "<h3>";
            } else {
               $wl .= "<h2>";
            }
            $wl .= $row['name'];
            
            if (!$widget) {
               $wl .= "</h3>";
            } else {
               $wl .= "</h2>";
            }
            
            $wl .= "</td></tr>";
            $wl .= "<tr><td valign='top'>";
            $wl .= Toolbox::unclean_html_cross_side_scripting_deep($row["text"]);
            $wl .= "</td></tr></table>";
            if (!$widget) {
               $wl .= "</li>";
            }
            if ($widget) {
               $wl .= "<hr>";
            }
         }
         if (!$widget) {
            $wl .= "</ul>";
            $wl .= "</div>";

            $wl .= "<script type='text/javascript'>
                  $(function() {
                     $('#maint-div').vTicker({
                        speed: 500,
                        pause: 3000,
                        showItems: 1,
                        animate: 'fade',
                        mousePause: true,
                        height: 0,
                        direction: 'up'
                     });
                  });
               </script>";
         }
      } else {
      
         $wl .= "<div align='center'><h3><span class ='maint-color'>";
         $wl .=  __("No scheduled maintenance", "mydashboard");
         $wl .= "</span></h3></div>";
      }
      if (!$widget) {
         $wl .= "</div>";
      }
      return $wl;
   }
   
   
   /**
    * @param int $public
    * @return string
    */
   function getInformationList($widget = false)
   {
      global $DB, $CFG_GLPI;

      $now = date('Y-m-d H:i:s');
      $wl = "";
      $wl = $this->getPublicCSS();
      
      if (!$widget) {
         $wl .= "<div class='weather_block'>";
      }
      $restrict_user = '1';
      // Only personal on central so do not keep it
//      if ($_SESSION['glpiactiveprofile']['interface'] == 'central') {
//         $restrict_user = "`glpi_reminders`.`users_id` <> '".Session::getLoginUserID()."'";
//      }

      $restrict_visibility = "AND (`glpi_reminders`.`begin_view_date` IS NULL
                                    OR `glpi_reminders`.`begin_view_date` < '$now')
                              AND (`glpi_reminders`.`end_view_date` IS NULL
                                   OR `glpi_reminders`.`end_view_date` > '$now') ";

      $query = "SELECT `glpi_reminders`.`id`,
                       `glpi_reminders`.`name`,
                       `glpi_reminders`.`text`,
                       `glpi_reminders`.`date`,
                       `glpi_reminders`.`begin_view_date`,
                       `glpi_reminders`.`end_view_date`
                   FROM `glpi_reminders` "
         . Reminder::addVisibilityJoins()
         . "LEFT JOIN `" . $this->getTable() . "`"
         . "ON `glpi_reminders`.`id` = `" . $this->getTable() . "`.`reminders_id`"
         . "WHERE $restrict_user
                         $restrict_visibility ";

      $query .= "AND " . Reminder::addVisibilityRestrict() . "";
  
      $query .= "AND `" . $this->getTable() . "`.`type` = 2
                   ORDER BY `glpi_reminders`.`name`";

      
      $result = $DB->query($query);
      $nb = $DB->numrows($result);
      $list = array();
      $width ="";
      if ($nb) {
         
         if (!$widget) {
            $wl .= "<div id='info-div'>";
            $wl .= "<ul>";
         }
         while ($row = $DB->fetch_array($result)) {
            
            if (!$widget) {
               $wl .= "<li>";
            }
            if ($widget) {
               $width = " width=55px";
            }
            $wl .= "<table width='100%'><tr>";
            //$wl .= "<td rowspan='2' class='center' width='20%'>";
            //$wl .= "<img src='" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/pics/informations.png' $width />";
            //$wl .= "</td>";
            $wl .= "<td valign='top'>";
            if (!$widget) {
               $wl .= "<h3>";
            } else {
               $wl .= "<h2>";
            }
            $wl .= $row['name'];
            
            if (!$widget) {
               $wl .= "</h3>";
            } else {
               $wl .= "</h2>";
            }
            
            $wl .= "</td></tr>";
            $wl .= "<tr><td valign='top'>";
            $wl .= Toolbox::unclean_html_cross_side_scripting_deep($row["text"]);
            $wl .= "</td></tr></table>";
            if (!$widget) {
               $wl .= "</li>";
            }
            if ($widget) {
               $wl .= "<hr>";
            }
         }
         if (!$widget) {
            $wl .= "</ul>";
            $wl .= "</div>";

            $wl .= "<script type='text/javascript'>
                  $(function() {
                     $('#info-div').vTicker({
                        speed: 300,
                        pause: 5000,
                        showItems: 1,
                        animate: false,
                        mousePause: true,
                        height: 0,
                        direction: 'right'
                     });
                  });
               </script>";
         }
      } else {
      
         $wl .= "<div align='center'><h3><span class ='maint-color'>";
         $wl .=  __("No informations founded", "mydashboard");
         $wl .= "</span></h3></div>";
      }
      if (!$widget) {
         $wl .= "</div>";
      }
      return $wl;
   }
   
   
   /**
    * @param int $public
    * @return string
    */
   function getAlertList($public = 0, $force = 0, $widget = false)
   {
      global $DB, $CFG_GLPI;

      $now = date('Y-m-d H:i:s');

      $wl = $this->getPublicCSS();
      
      if (!$widget) {
         $wl .= "<div class='weather_block'>";
      }

      $restrict_user = '1';
      // Only personal on central so do not keep it
//      if ($_SESSION['glpiactiveprofile']['interface'] == 'central') {
//         $restrict_user = "`glpi_reminders`.`users_id` <> '".Session::getLoginUserID()."'";
//      }

      $restrict_visibility = "AND (`glpi_reminders`.`begin_view_date` IS NULL
                                    OR `glpi_reminders`.`begin_view_date` < '$now')
                              AND (`glpi_reminders`.`end_view_date` IS NULL
                                   OR `glpi_reminders`.`end_view_date` > '$now') ";

      $query = "SELECT `glpi_reminders`.`id`,
                       `glpi_reminders`.`name`,
                       `glpi_reminders`.`text`,
                       `glpi_reminders`.`date`,
                       `glpi_reminders`.`begin_view_date`,
                       `glpi_reminders`.`end_view_date`,
                       `" . $this->getTable() . "`.`impact`,
                       `" . $this->getTable() . "`.`is_public`
                   FROM `glpi_reminders` "
         . Reminder::addVisibilityJoins()
         . "LEFT JOIN `" . $this->getTable() . "`"
         . "ON `glpi_reminders`.`id` = `" . $this->getTable() . "`.`reminders_id`"
         . "WHERE $restrict_user
                         $restrict_visibility ";

      if ($public == 0) {
         $query .= "AND " . Reminder::addVisibilityRestrict() . "";
      } else {
         $query .= "AND `" . $this->getTable() . "`.`is_public`";
      }
      $query .= "AND `" . $this->getTable() . "`.`impact` IS NOT NULL 
                 AND `" . $this->getTable() . "`.`type` = 0
                   ORDER BY `glpi_reminders`.`name`";

      $cloud = array();
      $cloudy = array();
      $storm = array();

      $result = $DB->query($query);
      $nb = $DB->numrows($result);

      $list = array();
      $width ="";
      if ($nb) {
         
         if (!$widget) {
            $wl .= "<div id='alert-div'>";
            $wl .= "<ul>";
         }
         while ($row = $DB->fetch_array($result)) {
            
            if ($nb < 2 && $row['impact'] < 3) {
               $cloudy[] = $row;
            }
            if ($row['impact'] <= 3) {
               $cloud[] = $row;
            } else {
               $storm[] = $row;
            }
            
            if (!$widget) {
               $wl .= "<li>";
            }
            //if ($widget) {
            //   $width = " width=55px";
            //}
            $wl .= "<table width='100%'><tr>";
            $wl .= "<td rowspan='2' class='center' width='30%'>";
            
            if (!empty($storm)) {
               //display storm
               $type = "storm";
            } elseif (!empty($cloudy)) {
               //display cloudy
               $type = "cloudy";
            } elseif (!empty($cloud)) {
               //display cloud
               $type = "cloud";
            } else {
               //display sun
               $type = "sun";
            }
         
            $wl .= "<img src='" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/pics/{$type}.png' width='95%'/>";
            $wl .= "</td>";
            $wl .= "<td valign='top'>";
            $wl .= "<h3>";

            $class = (Html::convDate(date("Y-m-d")) == Html::convDate($row['date'])) ? 'alert_new' : '';
            $class .= ' alert_impact' . $row['impact'];
            $classfont = ' alert_fontimpact' . $row['impact'];
            
            $rand = mt_rand();
            $name = (Session::haveRight("reminder_public", READ)) ?
               "<a  href='" . Reminder::getFormURL() . "?id=" . $row['id'] . "'>" . $row['name'] . "</a>"
               : $row['name'];
            
            $wl .= "<div id='alert$rand' class='alert_alert'>";
            
            
            $wl .= "<span class='$classfont left'>" . $name . "</span>";
            $wl .= "</div>";
            //$wl .= $row['name'];
            $wl .= "</h3>";
            
            $wl .= "</td></tr>";
            $wl .= "<tr><td valign='top'>";
            $wl .= "<span class='alert_impact $class'></span>";
            if (isset($row['begin_view_date'])
               && isset($row['end_view_date'])
            ) {
               $wl .= "<span class='alert_date'>" . Html::convDateTime($row['begin_view_date']) . " - " . Html::convDateTime($row['end_view_date']) . "</span><br>";
            }
            $wl .= Toolbox::unclean_html_cross_side_scripting_deep($row["text"]);
            $wl .= "</td></tr></table>";
            if (!$widget) {
               $wl .= "</li>";
            }
            if ($widget) {
               $wl .= "<hr>";
            }
         }
         if (!$widget) {
            $wl .= "</ul>";
            $wl .= "</div>";

            $wl .= "<script type='text/javascript'>
                  $(function() {
                     $('#alert-div').vTicker({
                        speed: 500,
                        pause: 3000,
                        showItems: 1,
                        animation: 'fade',
                        mousePause: true,
                        height: 0,
                        direction: 'up'
                     });
                  });
               </script>";
         }
      } else {
      
         $wl .= "<div align='center'><h3><span class ='alert-color'>";
         $wl .=  __("No problem", "mydashboard");
         $wl .= "</span></h3></div>";
      }
      if (!$widget) {
         $wl .= "</div>";
      }
      return $wl;
   }
   
   /**
    * @param int $public
    * @return string
    */
   function getAlertSummary($public = 0, $force = 0)
   {
      global $DB;

      $now = date('Y-m-d H:i:s');

      $restrict_user = '1';
      // Only personal on central so do not keep it
//      if ($_SESSION['glpiactiveprofile']['interface'] == 'central') {
//         $restrict_user = "`glpi_reminders`.`users_id` <> '".Session::getLoginUserID()."'";
//      }

      $restrict_visibility = "AND (`glpi_reminders`.`begin_view_date` IS NULL
                                    OR `glpi_reminders`.`begin_view_date` < '$now')
                              AND (`glpi_reminders`.`end_view_date` IS NULL
                                   OR `glpi_reminders`.`end_view_date` > '$now') ";

      $query = "SELECT `glpi_reminders`.`id`,
                       `glpi_reminders`.`name`,
                       `glpi_reminders`.`text`,
                       `glpi_reminders`.`date`,
                       `glpi_reminders`.`begin_view_date`,
                       `glpi_reminders`.`end_view_date`,
                       `" . $this->getTable() . "`.`impact`,
                       `" . $this->getTable() . "`.`is_public`
                   FROM `glpi_reminders` "
         . Reminder::addVisibilityJoins()
         . "LEFT JOIN `" . $this->getTable() . "`"
         . "ON `glpi_reminders`.`id` = `" . $this->getTable() . "`.`reminders_id`"
         . "WHERE $restrict_user
                         $restrict_visibility ";

      if ($public == 0) {
         $query .= "AND " . Reminder::addVisibilityRestrict() . "";
      } else {
         $query .= "AND `" . $this->getTable() . "`.`is_public`";
      }
      $query .= "AND `" . $this->getTable() . "`.`impact` IS NOT NULL 
                 AND `" . $this->getTable() . "`.`type` = 0
                   ORDER BY `glpi_reminders`.`name`";

      $cloud = array();
      $cloudy = array();
      $storm = array();
      $wl = "";
      $result = $DB->query($query);
      $nb = $DB->numrows($result);

      if ($nb) {
         while ($row = $DB->fetch_array($result)) {

            if ($nb < 2 && $row['impact'] < 3) {
               $cloudy[] = $row;
            }
            if ($row['impact'] <= 3) {
               $cloud[] = $row;
            } else {
               $storm[] = $row;
            }
         }

         if (!empty($storm)) {
            //display storm
            $wl .= $this->displayContent('storm', array_merge($storm, $cloud), $public, $force);
         } elseif (!empty($cloudy)) {
            //display cloudy
            $wl .= $this->displayContent('cloudy', $cloudy, $public, $force);
         } elseif (!empty($cloud)) {
            //display cloud
            $wl .= $this->displayContent('cloud', $cloud, $public, $force);
         } else {
            //display sun
            $wl .= $this->displayContent('sun', array(), 0, $force);
         }
      }
      if (!$nb && ($public == 0 || $force == 1)) {
         $wl .= $this->displayContent('sun', array(), 0, $force);
      }


//      foreach($datas as $data){
//         $wl .= "<div class='bubble' style='display:inline; background-color:".$status[$data['type']]."'>".$data['title']."</div>";
//      }
      return $wl;
   }

   /**
    * @param $type
    * @param array $list
    * @param int $public
    * @return string
    */
   private function displayContent($type, $list = array(), $public = 0, $force = 0)
   {
      global $CFG_GLPI;

      $div = $this->getCSS();
      if ($force == 1) {
         $div .= $this->getPublicCSS();
      }
      $div .= "<div class='weather_block'>";
      $div .= "<div class='center'><h3>" . __("Monitoring", "mydashboard") . "</h3></div>";
      $div .= "<div class='weather_img center'><img src='" . $CFG_GLPI['root_doc'] . "/plugins/mydashboard/pics/{$type}.png' width='85%'/></div>";
      $div .= "<div class='weather_msg'>"
         . $this->getMessage($list, $public)
         . "</div>";
      $div .= "</div>";
      return $div;
   }

   /**
    * @param $list
    * @param $public
    * @return string
    */
   private function getMessage($list, $public)
   {
      $l = "";
      if (!empty($list)) {
         foreach ($list as $listitem) {

            $class = (Html::convDate(date("Y-m-d")) == Html::convDate($listitem['date'])) ? 'alert_new' : '';
            $class .= ' alert_impact' . $listitem['impact'];
            $classfont = ' alert_fontimpact' . $listitem['impact'];
            $rand = mt_rand();
            $name = (Session::haveRight("reminder_public", READ)) ?
               "<a  href='" . Reminder::getFormURL() . "?id=" . $listitem['id'] . "'>" . $listitem['name'] . "</a>"
               : $listitem['name'];

            $l .= "<div id='alert$rand' class='alert_alert'>";
            $l .= "<span class='alert_impact $class'></span>";
            if (isset($listitem['begin_view_date'])
               && isset($listitem['end_view_date'])
            ) {
               $l .= "<span class='alert_date'>" . Html::convDateTime($listitem['begin_view_date']) . " - " . Html::convDateTime($listitem['end_view_date']) . "</span><br>";
            }


            $l .= "<span class='$classfont center'>" . $name . "</span>&nbsp;";

            //if ($public == 0) {
            $l .= Html::showToolTip(
               nl2br(Html::Clean($listitem['text'])),
               array('display' => false,
                  //'applyto' => 'alert' . $rand
                  )
            );
            //}
            $l .= "</div>";
         }
      } else {
         $l .= "<div>" . __("No problem", "mydashboard") . "</div>";
      }
      $l .= "<br>";

      return $l;
   }

   /**
    * @param Reminder $item
    */
   private function showForm(Reminder $item)
   {
      $reminders_id = $item->getID();

      $this->getFromDBByQuery("WHERE `reminders_id` = '" . $reminders_id . "'");

      if (isset($this->fields['id'])) {
         $id = $this->fields['id'];
         $impact = $this->fields['impact'];
         $type = $this->fields['type'];
         $is_public = $this->fields['is_public'];
      } else {
         $id = -1;
         $type = 0;
         $impact = 0;
         $is_public = 0;
      }
      echo "<form action='" . $this->getFormURL() . "' method='post' >";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='2'>" . _n('Alert', 'Alerts', 2, 'mydashboard') . "</th></tr>";
      
      $types = array();
      $types[0] = _n('Alert', 'Alerts', 1, 'mydashboard');
      $types[1] = _n('Scheduled maintenance', 'Scheduled maintenances', 1, 'mydashboard');
      $types[2] = _n('Information', 'Informations', 1, 'mydashboard');
      echo "<tr class='tab_bg_2'><td>" . __("Type") . "</td><td>";
      Dropdown::showFromArray('type', $types, array(
            'value' => $type
         )
      );
      echo "</td></tr>";
      
      $impacts = array();
      $impacts[0] = __("No impact", "mydashboard");
      for ($i = 1; $i <= 5; $i++) {
         $impacts[$i] = CommonITILObject::getImpactName($i);
      }

      echo "<tr class='tab_bg_2'><td>" . __("Alert level", "mydashboard") . "</td><td>";
      Dropdown::showFromArray('impact', $impacts, array(
            'value' => $impact
         )
      );
      echo "</td></tr>";
      echo "<tr class='tab_bg_2'><td>" . __("Public") . "</td><td>";
      Dropdown::showYesNo('is_public', $is_public);

      echo "</td></tr>";
      if (Session::haveRight("reminder_public", UPDATE)) {
         echo "<tr class='tab_bg_1 center'><td colspan='2'>";
         echo "<input type='submit' name='update' value=\"" . _sx('button', 'Save') . "\" class='submit'>";
         echo "<input type='hidden' name='id' value=" . $id . ">";
         echo "<input type='hidden' name='reminders_id' value=" . $reminders_id . ">";
         echo "</td></tr>";
      }
      echo "</table>";
      Html::closeForm();
   }

   /**
    * @return string
    */
   private function getCSS()
   {
      $css = "<style  type='text/css' media='screen'>
               #display-login {
                  width: 40%;
                  /*background-color: #006573;*/
                  text-align:center;
                  padding: 1px 1%;
               }
               #display-sc {
                  width: 100%;
                  /*background-color: #006573;*/
                  text-align:center;
                  padding: 1px 1%;
               }
              .alert_alert {
                  /*margin: 0 30%;*/
                  
                  margin: 0 auto;
                  /*border:1px solid #DDD;*/
                  color:#000;
              }
              .alert_date {
                  text-align:center;
              }
              //.alert_alert:hover {
              //    background-color: #EEE;
              //}

              .alert_impact {
                  width: 14px;
                  height: 14px;
                  border-radius:7px;
                  display: inline-block;
                  float:top;
                  margin:1px 5px;
              }

              .alert_impact1 {
                  background-color: #DFEC4B;
              }
              .alert_fontimpact1 {
                  color: #DFEC4B;
              }
              .alert_impact2 {
                  background-color: #EED655;
              }
              .alert_fontimpact2 {
                  color: #EED655;
              }
              .alert_impact3 {
                  background-color: #DBBD5D;
              }
              .alert_fontimpact3 {
                  color: #DBBD5D;
              }
              .alert_impact4 {
                  background-color: #CE9C5C;
              }
              .alert_fontimpact4 {
                  color: #CE9C5C;
              }
              .alert_impact5 {
                  background-color: #B55;
                  -webkit-animation: blink 0.5s linear infinite;
                  -moz-animation: blink 0.5s linear infinite;
                  animation: blink 0.5s linear infinite;
              }
              .alert_fontimpact5 {
                  color: #B55;
              }

              @keyframes blink {  
                  0% { opacity:0 }
                  50% { opacity:1 }
                  100% { opacity:0 }
              }
              @-webkit-keyframes blink {
                  0% { opacity:0 }
                  50% { opacity:1 }
                  100% { opacity:0 }
              }
               .weather_block {
                  text-align: center;
                  margin:0 auto;
                  color:#000;
                  font-size: 12px;
                  border-radius: 5px 10px 0 5px;
                  border-color: #CCC;
                  border-style: dashed;
                  background-color: #FFF;
                  width: 80%;
               }
              .weather_img {
                  /*background-color: deepskyblue;*/
                  width: 128px;
                  margin: 20px auto 20px auto;
                  border-radius: 5px;
                  /*box-shadow: deepskyblue 0px 0px 10px 10px;*/
              }

              .weather_msg {
                  text-align: center;
              }
              
              </style>";
      return $css;
   }
   
   /**
    * @return string
    */
   private function getPublicCSS()
   {
      $css = "<style  type='text/css' media='screen'>
               .weather_block {
                  text-align: center;
                  margin:0 auto;
                  color:#000;
                  font-size: 12px;
                  border-radius: 5px 10px 0 5px;
                  border-color: #CCC;
                  border-style: dashed;
                  background-color: #FFF;
                  width: 100%;
               }
              </style>";
      return $css;
   }
}
