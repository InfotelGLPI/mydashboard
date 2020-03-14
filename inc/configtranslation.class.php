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
 * PluginMydashboardConfigTranslation Class
 *
 **/
class PluginMydashboardConfigTranslation extends CommonDBChild {

   static public $itemtype  = 'itemtype';
   static public $items_id  = 'items_id';
   public        $dohistory = true;

   static $rightname = 'plugin_mydashboard_config';


   /**
    * Return the localized name of the current Type
    * Should be overloaded in each new class
    *
    * @param integer $nb Number of items
    *
    * @return string
    **/
   static function getTypeName($nb = 0) {
      return _n('Translation', 'Translations', $nb);
   }


   /**
    * Get the standard massive actions which are forbidden
    *
    * @since version 0.84
    *
    * This should be overloaded in Class
    *
    * @return array an array of massive actions
    **/
   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }


   /**
    * @see CommonGLPI::getTabNameForItem()
    **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      $nb = self::getNumberOfTranslationsForItem($item);
      return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
   }

   /**
    * @param $item            CommonGLPI object
    * @param $tabnum (default 1)
    * @param $withtemplate (default 0)
    **
    *
    * @return bool
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if (self::canBeTranslated($item)) {
         self::showTranslations($item);
      }
      return true;
   }


   /**
    * Display all translated field for a dropdown
    *
    * @param $item a Dropdown item
    *
    * @return true;
    **/
   static function showTranslations($item) {
      global $DB, $CFG_GLPI;

      $rand    = mt_rand();
      $canedit = $item->can($item->getID(), UPDATE);

      if ($canedit) {
         echo "<div id='viewtranslation" . $item->getType() . $item->getID() . "$rand'></div>\n";

         echo "<script type='text/javascript' >\n";
         echo "function addTranslation" . $item->getType() . $item->getID() . "$rand() {\n";
         $params = ['type'                      => __CLASS__,
                         'parenttype'                => get_class($item),
                         $item->getForeignKeyField() => $item->getID(),
                         'id'                        => -1];
         Ajax::updateItemJsCode("viewtranslation" . $item->getType() . $item->getID() . "$rand",
                                $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php",
                                $params);
         echo "};";
         echo "</script>\n";
         echo "<div class='center'>" .
              "<a class='vsubmit' href='javascript:addTranslation" .
              $item->getType() . $item->getID() . "$rand();'>" . __('Add a new translation') .
              "</a></div><br>";
      }
      $iterator = $DB->request([
                                  'FROM'   => getTableForItemType(__CLASS__),
                                  'WHERE'  => [
                                     'itemtype'  => $item->getType(),
                                     'items_id'  => $item->getID(),
                                     'field'     => ['<>', 'completename']
                                  ],
                                  'ORDER'  => ['language ASC']
                               ]);
      if (count($iterator)) {
         if ($canedit) {
            Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
            $massiveactionparams = ['container' => 'mass' . __CLASS__ . $rand];
            Html::showMassiveActions($massiveactionparams);
         }
         echo "<div class='center'>";
         echo "<table class='tab_cadre_fixehov'><tr class='tab_bg_2'>";
         echo "<th colspan='4'>" . __("List of translations") . "</th></tr><tr>";
         if ($canedit) {
            echo "<th width='10'>";
            Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
            echo "</th>";
         }
         echo "<th>" . __("Language") . "</th>";
         echo "<th>" . __("Field") . "</th>";
         echo "<th>" . __("Value") . "</th></tr>";
         while ($data = $iterator->next()) {
            $onhover = '';
            if ($canedit) {
               $onhover = "style='cursor:pointer'
                           onClick=\"viewEditTranslation" . $data['itemtype'] . $data['id'] . "$rand();\"";
            }
            echo "<tr class='tab_bg_1'>";
            if ($canedit) {
               echo "<td class='center'>";
               Html::showMassiveActionCheckBox(__CLASS__, $data["id"]);
               echo "</td>";
            }

            echo "<td $onhover>";
            if ($canedit) {
               echo "\n<script type='text/javascript' >\n";
               echo "function viewEditTranslation" . $data['itemtype'] . $data['id'] . "$rand() {\n";
               $params = ['type'                      => __CLASS__,
                               'parenttype'                => get_class($item),
                               $item->getForeignKeyField() => $item->getID(),
                               'id'                        => $data["id"]];
               Ajax::updateItemJsCode("viewtranslation" . $item->getType() . $item->getID() . "$rand",
                                      $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php",
                                      $params);
               echo "};";
               echo "</script>\n";
            }
            echo Dropdown::getLanguageName($data['language']);
            echo "</td><td $onhover>";
            $searchOption = $item->getSearchOptionByField('field', $data['field']);
            echo $searchOption['name'] . "</td>";
            echo "<td $onhover>" . $data['value'] . "</td>";
            echo "</tr>";
         }
         echo "</table>";
         if ($canedit) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
         }
      } else {
         echo "<table class='tab_cadre_fixe'><tr class='tab_bg_2'>";
         echo "<th class='b'>" . __("No translation found") . "</th></tr></table>";
      }
      return true;
   }


   /**
    * Display translation form
    *
    * @param $ID               field (default -1)
    * @param $options   array
    *
    * @return bool
    */
   function showForm($ID = -1, $options = []) {
      global $CFG_GLPI;

      if (isset($options['parent']) && !empty($options['parent'])) {
         $item = $options['parent'];
      }
      if ($ID > 0) {
         $this->check($ID, UPDATE);
      } else {
         $options['itemtype'] = get_class($item);
         $options['items_id'] = $item->getID();

         // Create item
         $this->check(-1, CREATE, $options);
      }
      $rand = mt_rand();
      $this->showFormHeader($options);
      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Language') . "</td>";
      echo "<td>";
      echo Html::hidden('items_id', ['value' => $item->getID()]);
      echo Html::hidden('itemtype', ['value' => get_class($item)]);
      if ($ID > 0) {
         echo Html::hidden('language', ['value' => $this->fields['language']]);
         echo Dropdown::getLanguageName($this->fields['language']);
      } else {
         $rand   = Dropdown::showLanguages("language",
                                           ['display_none' => false,
                                                 'value'        => $_SESSION['glpilanguage']]);
         $params = ['language' => '__VALUE__',
                         'itemtype' => get_class($item),
                         'items_id' => $item->getID()];
         Ajax::updateItemOnSelectEvent("dropdown_language$rand",
                                       "span_fields",
                                       $CFG_GLPI["root_doc"] . "/plugins/mydashboard/ajax/updateTranslationFields.php",
                                       $params);
      }
      echo "</td><td colspan='2'>&nbsp;</td></tr>";

      echo "<tr class='tab_bg_1'><td>" . __('Field') . "</td>";
      echo "<td>";
      if ($ID > 0) {
         echo Html::hidden('field', ['value' => $this->fields['field']]);
         $searchOption = $item->getSearchOptionByField('field', $this->fields['field']);
         echo $searchOption['name'];
      } else {
         echo "<span id='span_fields' name='span_fields'>";
         self::dropdownFields($item, $_SESSION['glpilanguage']);
         echo "</span>";
      }
      echo "</td>";
      echo "<td>" . __('Value') . "</td>";
      echo "<td><textarea cols='80' rows='3' name='value'>" . $this->fields['value'] . "</textarea>";
      echo "</td>";
      echo "</tr>\n";
      $this->showFormButtons($options);
      return true;
   }

   /**
    * Display a dropdown with fields that can be translated for an itemtype
    *
    * @param $item       a Dropdown item
    * @param $language   language to look for translations (default '')
    * @param $value      field which must be selected by default (default '')
    *
    * @return the dropdown's random identifier
    **/
   static function dropdownFields(CommonDBTM $item, $language = '', $value = '') {
      global $DB;
      $options = [];
      foreach ($item->rawSearchOptions() as $id => $field) {
         //Can only translate name, and fields whose datatype is text or string
         $dbu        = new DbUtils();
         if (isset ($field['field'])
             && ($field['field'] == 'name')
             && ($field['table'] == $dbu->getTableForItemType(get_class($item)))
             || (isset($field['datatype'])
                 && in_array($field['datatype'], ['text', 'string']))) {
            $options[$field['field']] = $field['name'];
         }
      }
      $used = [];
      if (!empty($options)) {
         $iterator = $DB->request([
                                     'SELECT' => 'field',
                                     'FROM'   => self::getTable(),
                                     'WHERE'  => [
                                        'itemtype'  => $item->getType(),
                                        'items_id'  => $item->getID(),
                                        'language'  => $language
                                     ]
                                  ]);
         if (count($iterator) > 0) {
            while ($data = $iterator->next()) {
               $used[$data['field']] = $data['field'];
            }
         }
      }
      //$used = array();
      return Dropdown::showFromArray('field', $options, ['value' => $value,
                                                              'used'  => $used]);
   }

   /**
    * Check if an item can be translated
    * It be translated if translation if globally on and item is an instance of CommonDropdown
    * or CommonTreeDropdown and if translation is enabled for this class
    *
    * @param item the item to check
    *
    * @return true if item can be translated, false otherwise
    **/
   static function canBeTranslated(CommonGLPI $item) {

      return ($item instanceof PluginMydashboardConfig);
   }


   /**
    * Return the number of translations for an item
    *
    * @param item
    *
    * @return the number of translations for this item
    **/
   static function getNumberOfTranslationsForItem($item) {
      $dbu = new DbUtils();
      return $dbu->countElementsInTable($dbu->getTableForItemType(__CLASS__),
                                        ["items_id" => $item->getID()]);
   }

}