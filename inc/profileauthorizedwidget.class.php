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
class PluginMydashboardProfileAuthorizedWidget extends CommonDBTM
{

   private $authorized;

   /**
    * @param $profiles_id
    * @return array|bool
    */
   public function getAuthorizedListForProfile($profiles_id)
   {
      $profileright = new ProfileRight();
      $profileright->getFromDBByQuery("WHERE `name`='plugin_mydashboard' AND `profiles_id` = '" . $profiles_id . "'");

      //If profile has right CREATE+UPDATE it means it can see every widgets
      if (isset($profileright->fields['rights']) && $profileright->fields['rights'] == (CREATE + UPDATE)) return false;

      //If profile has right READ it means it can see only authorized widgets
      if (isset($profileright->fields['rights']) && $profileright->fields['rights'] == READ) {
         $table = getAllDatasFromTable($this->getTable(), "`profiles_id` = '" . $profiles_id . "'");
         $widget = new PluginMydashboardWidget();

         $ret = array();
         foreach ($table as $key => $line) {
            $widgetId = $widget->getWidgetNameById($line['widgets_id']);
            $ret[$widgetId] = $line['id'];
         }
         return $ret;
      } else {
         return array();
      }
   }

   /**
    * @param $ID
    * @param array $options
    */
   public function showForm($ID, $options = array())
   {
      $this->authorized = $this->getAuthorizedListForProfile($ID);
      $test = new PluginMydashboardWidgetlist();
      $widgetlist = $test->getList(false);
      echo "<form method='post' action='../plugins/mydashboard/front/profileauthorizedwidget.form.php' onsubmit='return true;'>";
      echo "<table class='tab_cadre_fixe'>";
      foreach ($widgetlist as $plugin => $widgetclasses) {
         echo "<th>" . ucfirst($this->getLocalName($plugin)) . "</th>";
         echo "<th class='plugin_mydashboard_authorize_all' onclick=\""
            . "$('.from_$plugin').find('select').val(($('.from_$plugin').find('select').val() === '0')?'1':'0');
                       $('.from_$plugin').find('select').trigger('change');\" >" . __('Authorize/Unauthorize all', 'mydashboard') . "&nbsp;"
//                     ."<input type='checkbox' value='all' />"

            . "</th>";
         foreach ($widgetclasses as $widgetclass => $widgetlist) {
            $this->displayList($widgetlist, '', 'from_' . $plugin);
         }
      }
      echo "<tr class='tab_bg_2'><td class='center' colspan='2'>";
      echo "<input type='submit' name='update' value=\"" . _sx('button', 'Save') . "\" class='submit'>";
      echo "<input type='hidden' name='id' value=" . $ID . ">";
      echo "</tr>";
      echo "</table>";


      Html::closeForm();
   }

   /**
    * @param $widgetlist
    * @param string $category
    * @param $pluginname
    */
   private function displayList($widgetlist, $category = '', $pluginname)
   {
      $viewNames = $this->getViewNames();
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
               $widgetId = $viewNames[$widgetId];
            }
            $newcategory .= $widgetId;
            $this->displayList($widgetTitle, $newcategory, $pluginname);
         }
      }
   }

   /**
    * @param $post
    */
   public function save($post)
   {

      if (isset($post['id']) && isset($post['update'])) {
         $profiles_id = $post['id'];
         unset($post['id']);
         unset($post['update']);
      } else {
         return;
      }
      $this->authorized = $this->getAuthorizedListForProfile($profiles_id);
      $widget = new PluginMydashboardWidget();

      //Newly authorized
      foreach ($post as $widgetName => $authorized) {
         if ($authorized == 1) {
            $widgetId = $widget->getWidgetIdByName($widgetName);
            unset($this->fields['id']);
            $this->getFromDBByQuery("WHERE `profiles_id` = '$profiles_id' AND `widgets_id` ='$widgetId'");
            if (!isset($this->fields['id'])) {
               $this->add(array(
                  'profiles_id' => $profiles_id,
                  'widgets_id' => $widgetId
               ));
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
    * @param string $plugin_name
    * @return string
    */
   private function getLocalName($plugin_name)
   {
      $infos = Plugin::getInfo($plugin_name);

      return isset($infos['name']) ? $infos['name'] : $plugin_name;
   }

   /**
    * Get the names of each view
    * @return array of string
    */
   private function getViewNames()
   {
      $names = array();
      $names[1] = _n('Ticket', 'Tickets', 2);
      $names[2] = _n('Problem', 'Problems', 2);
      $names[3] = _n('Change', 'Changes', 2);
      $names[4] = __('Group View');
      $names[5] = __('Personal View');
      $names[6] = __('Global View');
      $names[7] = _n('RSS feed', 'RSS feeds', 2);

      return $names;
   }
}
