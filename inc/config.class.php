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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/**
 * This class handles the general configuration of mydashboard
 *
 */
class PluginMydashboardConfig extends CommonDBTM {
   /**
    * @param int $nb
    *
    * @return translated
    */
   static $rightname         = "plugin_mydashboard_config";
   var    $can_be_translated = true;

   static function getTypeName($nb = 0) {
      return __('Plugin setup', 'mydashboard');
   }

   /**
    * PluginMydashboardConfig constructor.
    */
   public function __construct() {
      global $DB;

      if ($DB->tableExists($this->getTable())) {
         $this->getFromDB(1);
      }
   }

   /**
    * Have I the global right to "view" the Object
    *
    * Default is true and check entity if the objet is entity assign
    *
    * May be overloaded if needed
    *
    * @return booleen
    **/
   static function canView() {

      return (Session::haveRight(self::$rightname, UPDATE));
   }

   /**
    * Have I the global right to "create" the Object
    * May be overloaded if needed (ex KnowbaseItem)
    *
    * @return booleen
    **/
   static function canCreate() {

      return (Session::haveRight(self::$rightname, CREATE));
   }

   /**
    * @param string $interface
    *
    * @return array
    */
   function getRights($interface = 'central') {

      $values = parent::getRights();

      unset($values[READ], $values[DELETE]);
      return $values;
   }


   /**
    * @param CommonGLPI $item
    * @param int        $tabnum
    * @param int        $withtemplate
    *
    * @return bool
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if ($item->getType() == __CLASS__) {
         switch ($tabnum) {

            case 1 :
               $item->showForm($item->getID());
               break;
         }
      }
      return true;
   }

   /**
    * @param CommonGLPI $item
    * @param int        $withtemplate
    *
    * @return string
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if (!$withtemplate) {
         switch ($item->getType()) {
            case __CLASS__ :
               $ong[1] = self::getTypeName();
               return $ong;
         }

      }
      return '';
   }

   /**
    * @see CommonGLPI::defineTabs()
    */
   function defineTabs($options = []) {

      $ong = [];
      $this->addStandardTab(__CLASS__, $ong, $options);
      $this->addStandardTab('PluginMydashboardConfigTranslation', $ong, $options);
      return $ong;
   }

   /**
    * Get the Search options for the given Type
    *
    * This should be overloaded in Class
    *
    * @return array an *indexed* array of search options
    *
    * @see https://glpi-developer-documentation.rtfd.io/en/master/devapi/search.html
    **/
   function rawSearchOptions() {

      $tab = [];

      $tab[] = [
         'id'   => 'common',
         'name' => self::getTypeName(2)
      ];

      $tab[] = [
         'id'         => '2',
         'table'      => $this->getTable(),
         'field'      => 'title_alerts_widget',
         'name'       => __("Title of alerts widget", "mydashboard"),
         'searchtype' => 'equals',
         'datatype'   => 'text'
      ];

      $tab[] = [
         'id'         => '3',
         'table'      => $this->getTable(),
         'field'      => 'title_maintenances_widget',
         'name'       => __("Title of scheduled maintenances widget", "mydashboard"),
         'searchtype' => 'equals',
         'datatype'   => 'text'
      ];

      $tab[] = [
         'id'         => '4',
         'table'      => $this->getTable(),
         'field'      => 'title_informations_widget',
         'name'       => __("Title of informations widget", "mydashboard"),
         'searchtype' => 'equals',
         'datatype'   => 'text'
      ];

      return $tab;
   }

   /**
    * @param       $ID
    * @param array $options
    */
   function showForm($ID, $options = []) {
      $this->getFromDB("1");

      //If user have no access
      //        if(!plugin_dashboard_haveRight('config', READ)){
      //            return false;
      //        }

      //The configuration is not deletable
      $options['candel']  = false;
      $options['colspan'] = 1;

      $this->showFormHeader($options);

      //canCreate means that user can update the configuration
      //        $canCreate = self::canCreate();
      $canCreate = true;
      $rand      = mt_rand();

      //This array is for those who can't update, it's to display the value of a boolean parameter
      $yesno = [__("No"), __("Yes")];

      echo "<tr class='tab_bg_1'><td>" . __("Enable the possibility to display Dashboard in full screen", "mydashboard") . "</td>";
      echo "<td>";
      if ($canCreate) {
         Dropdown::showYesNo("enable_fullscreen", $this->fields['enable_fullscreen']);
      } else {
         echo $yesno[$this->fields['enable_fullscreen']];
      }
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'><td>" . __("Display widgets from plugins", "mydashboard") . "</td>";
      echo "<td>";
      Dropdown::showYesNo("display_plugin_widget", $this->fields['display_plugin_widget']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'><td>" . __("Replace central interface", "mydashboard") . "</td>";
      echo "<td>";
      Dropdown::showYesNo("replace_central", $this->fields['replace_central']);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'><td>" . __("Google API Key", "mydashboard") . "</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "google_api_key");
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('Impact colors for alerts', 'mydashboard') . "</td>";
      echo "<td colspan='3'>";

      echo "<table><tr>";
      echo "<td><label for='dropdown_priority_1$rand'>1</label>&nbsp;";
      Html::showColorField('impact_1', ['value' => $this->fields["impact_1"], 'rand' => $rand]);
      echo "</td>";
      echo "<td><label for='dropdown_priority_2$rand'>2</label>&nbsp;";
      Html::showColorField('impact_2', ['value' => $this->fields["impact_2"], 'rand' => $rand]);
      echo "</td>";
      echo "<td><label for='dropdown_priority_3$rand'>3</label>&nbsp;";
      Html::showColorField('impact_3', ['value' => $this->fields["impact_3"], 'rand' => $rand]);
      echo "</td>";
      echo "<td><label for='dropdown_priority_4$rand'>4</label>&nbsp;";
      Html::showColorField('impact_4', ['value' => $this->fields["impact_4"], 'rand' => $rand]);
      echo "</td>";
      echo "<td><label for='dropdown_priority_5$rand'>5</label>&nbsp;";
      Html::showColorField('impact_5', ['value' => $this->fields["impact_5"], 'rand' => $rand]);
      echo "</td>";
      echo "</tr></table>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Level of categories to show', 'mydashboard') . "</td>";
      echo "<td>";
      $itilCat        = new ITILCategory();
      $itilCategories = $itilCat->find();
      $levelsCat      = [];
      foreach ($itilCategories as $categorie) {
         $levelsCat[$categorie['level']] = $categorie['level'];
      }
      ksort($levelsCat);
      Dropdown::showFromArray('levelCat', $levelsCat, ['value' => $this->fields["levelCat"]]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'><td>" . __("Title of alerts widget", "mydashboard") . "</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "title_alerts_widget", ['size' => 100]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'><td>" . __("Title of scheduled maintenances widget", "mydashboard") . "</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "title_maintenances_widget", ['size' => 100]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'><td>" . __("Title of informations widget", "mydashboard") . "</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "title_informations_widget", ['size' => 100]);
      echo "</td>";
      echo "</tr>";

      $this->showFormButtons($options);
   }

   /*
    * Initialize the original configuration
    */
   function initConfig() {
      global $DB;

      //We first check if there is no configuration
      $query = "SELECT * FROM `" . $this->getTable() . "` LIMIT 1";

      $result = $DB->query($query);
      if ($DB->numrows($result) == '0') {

         $input                              = [];
         $input['id']                        = "1";
         $input['enable_fullscreen']         = "1";
         $input['display_menu']              = "1";
         $input['display_plugin_widget']     = "1";
         $input['replace_central']           = "1";
         $input['google_api_key']            = "";
         $input['title_alerts_widget']       = _n("Network alert", "Network alerts", 2, 'mydashboard');
         $input['title_maintenances_widget'] = _n("Scheduled maintenance", "Scheduled maintenances", 2, 'mydashboard');
         $input['title_informations_widget'] = _n("Information", "Informations", 2, 'mydashboard');
         $this->add($input);
      }
   }

   /*
    * Get the original config
    */
   function getConfig() {
      if (!$this->getFromDB("1")) {
         $this->initConfig();
         $this->getFromDB("1");
      }
   }

   /**
    * Returns the translation of the field
    *
    * @param type  $item
    * @param type  $field
    *
    * @return type
    * @global type $DB
    *
    */
   static function displayField($item, $field) {
      global $DB;

      // Make new database object and fill variables
      $iterator = $DB->request([
                                  'FROM'  => 'glpi_plugin_mydashboard_configtranslations',
                                  'WHERE' => [
                                     'itemtype' => 'PluginMydashboardConfig',
                                     'items_id' => '1',
                                     'field'    => $field,
                                     'language' => $_SESSION['glpilanguage']
                                  ]]);

      if (count($iterator)) {
         while ($data = $iterator->next()) {
            return $data['value'];
         }
      }
      return $item->fields[$field];
   }
}
