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
 * Class PluginMydashboardProfileAuthorizedWidget
 */
class PluginMydashboardProfileAuthorizedWidget extends CommonDBTM {

   private $authorized;

   /**
    * @param $profiles_id
    *
    * @return array|bool
    */
   public function getAuthorizedListForProfile($profiles_id) {
      $profileright = new ProfileRight();
      $profileright->getFromDBByCrit(['name'        => 'plugin_mydashboard',
                                      'profiles_id' => $profiles_id]);

      //If profile has right CREATE+UPDATE it means it can see every widgets
      if (isset($profileright->fields['rights'])
          && $profileright->fields['rights'] == (CREATE + UPDATE)) {
         return false;
      }

      //If profile has right READ it means it can see only authorized widgets
      if (isset($profileright->fields['rights']) && $profileright->fields['rights'] == READ) {
         $dbu    = new DbUtils();
         $table  = $dbu->getAllDataFromTable($this->getTable(), ["profiles_id" => $profiles_id]);
         $widget = new PluginMydashboardWidget();

         $ret = [];
         foreach ($table as $key => $line) {
            $widgetId       = $widget->getWidgetNameById($line['widgets_id']);
            $ret[$widgetId] = $line['id'];
         }
         return $ret;
      } else {
         return [];
      }
   }

   /**
    * @param       $ID
    * @param array $options
    */
   public function showForm($ID, $options = []) {

      $this->authorized = $this->getAuthorizedListForProfile($ID);
      $list             = new PluginMydashboardWidgetlist();
      $widgetlist       = $list->getList(false, -1, $options['interface']);

      echo "<form method='post' action='../plugins/mydashboard/front/profileauthorizedwidget.form.php' onsubmit='return true;'>";
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_2'>";
      echo "<th colspan='2' class='center b'>" . __('Authorized widgets', 'mydashboard') . "</th>";
      echo "</tr>";
      foreach ($widgetlist as $plugin => $widgetclasses) {
         echo "<tr class='tab_bg_2'>";
         $fct = 'plugin_version_' . strtolower($plugin);
         if (function_exists($fct)) {
            echo "<td>" . ucfirst($this->getLocalName($plugin)) . "</td>";
         } else {
            echo "<td>" . ucfirst($plugin) . "</td>";
         }
         echo "<td class='plugin_mydashboard_authorize_all' onclick=\""
              . "$('.from_$plugin').find('select').val(($('.from_$plugin').find('select').val() === '0')?'1':'0');
                       $('.from_$plugin').find('select').trigger('change');\" >" . __('Authorize/Unauthorize all', 'mydashboard') . "&nbsp;"
              //                     ."<input type='checkbox' value='all' />"

              . "</td>";
         echo "</tr>";
         foreach ($widgetclasses as $widgetclass => $widgetlist) {
            $this->displayList($widgetlist, '', 'from_' . $plugin);
         }
      }
      echo "<tr class='tab_bg_2'><td class='center' colspan='2'>";
      echo Html::submit(_sx('button', 'Save'), ['name' => 'update']);
      echo Html::hidden("id", ['value' => $ID]);
      echo "</tr>";
      echo "</table>";

      Html::closeForm();
   }

   /**
    * @param        $widgetlist
    * @param string $category
    * @param        $pluginname
    */
   private function displayList($widgetlist, $category, $pluginname) {

      $menu  = new PluginMydashboardMenu();
      $viewNames = $menu->getViewNames();
      foreach ($widgetlist as $widgetId => $widgetTitle) {

         if (!is_array($widgetTitle)) {
            echo "<tr class='tab_bg_1 $pluginname'>";
            $yesno = 0;
            if (isset($this->authorized[$widgetId])) {
               echo "<td class='plugin_mydashboard_authorized' >";
               $yesno = 1;
            } else {
               echo "<td class='plugin_mydashboard_unauthorized'>";
            }

            echo $widgetTitle;
            if ($category != '') {
               echo "<span class='plugin_mydashboard_discret' style='color:gray'>&nbsp;&nbsp;$category</span>";
            }
            echo "</td>";
            echo "<td>";

            Dropdown::showYesNo($widgetId, $yesno);
            echo "</td>";
            echo "</tr>";
         } else {
            $newcategory = "";
            if ($category != '') {
               $newcategory .= $category . ' > ';
            }

            if (is_numeric($widgetId)) {
               $widgetId = isset($viewNames[$widgetId])?$viewNames[$widgetId]:0;
            }
            $newcategory .= $widgetId;
            $this->displayList($widgetTitle, $newcategory, $pluginname);
         }
      }
   }

   /**
    * @param $post
    */
   public function save($post) {

      if (isset($post['id']) && isset($post['update'])) {
         $profiles_id = $post['id'];
         unset($post['id']);
         unset($post['update']);
      } else {
         return;
      }
      $this->authorized = $this->getAuthorizedListForProfile($profiles_id);
      $widget           = new PluginMydashboardWidget();

      //Newly authorized
      foreach ($post as $widgetName => $authorized) {
         if ($authorized == 1) {
            $widgetId = $widget->getWidgetIdByName($widgetName);
            unset($this->fields['id']);
            $this->getFromDBByCrit(['widgets_id' => $widgetId, 'profiles_id' => $profiles_id]);
            if (!isset($this->fields['id'])
                && $widgetId != null
                && !empty($widgetId)) {
               $this->add([
                             'profiles_id' => $profiles_id,
                             'widgets_id'  => $widgetId
                          ]);
            }
         } else {
            if (isset($this->authorized[$widgetName])) {
               $this->getFromDB($this->authorized[$widgetName]);
               $this->deleteFromDB();
            }
         }
      }

   }

   /**
    * Get the localized name for a plugin
    *
    * @param string $plugin_name
    *
    * @return string
    */
   private function getLocalName($plugin_name) {
      $infos = Plugin::getInfo($plugin_name);

      return isset($infos['name']) ? $infos['name'] : $plugin_name;
   }
}
