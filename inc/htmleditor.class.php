<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Metademands plugin for GLPI
 Copyright (C) 2018-2019 by the Metademands Development Team.

 https://github.com/InfotelGLPI/metademands
 -------------------------------------------------------------------------

 LICENSE

 This file is part of Metademands.

 Metademands is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Metademands is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Metademands. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginMydashboardHTMLEditor extends CommonDBTM {
   public $itemtype = 'PluginMydashboardCustomswidget';
   public $items_id = 'id';

   static $types = ['PluginMydashboardCustomswidget'];

   static $rightname = 'plugin_mydashboard';

   function rawSearchOptions() {
      $tab = [];

      $tab[] = [
         'id'            => '66',
         'table'         => $this->getTable(),
         'field'         => 'content',
         'name'          => __('Content'),
         'datatype'      => 'text',
         'itemlink_type' => $this->getType()
      ];
   }

   /**
    * Display tab for each users
    *
    * @param CommonGLPI $item
    * @param int        $withtemplate
    *
    * @return array|string
    */
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      $dbu = new DbUtils();
      if (!$withtemplate) {
         if ($item->getType() == 'PluginMydashboardCustomswidget') {
            if ($_SESSION['glpishow_count_on_tabs']) {
               return PluginMydashboardCustomswidget::createTabEntry(PluginMydashboardCustomswidget::getTypeName(),
                                                                     $dbu->countElementsInTable(PluginMydashboardCustomswidget::getTable(),
                                                                                                ["`id`" => $item->getID()]));
            }
            return PluginMydashboardCustomswidget::getTypeName();
         }
      }
      return '';
   }

   /**
    * Display content for each users
    *
    * @static
    *
    * @param CommonGLPI $item
    * @param int        $tabnum
    * @param int        $withtemplate
    *
    * @return bool|true
    */
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      $field = new self();

      $field->showForm($item);

      return true;
   }

   function showForm($item, $openform = true, $closeform = true) {

      // Codemirror lib
      echo Html::css('public/lib/codemirror.css');
      echo Html::script("public/lib/codemirror.js");

      echo "<div class='firstbloc'>";
      if ($openform) {
         echo "<form method='post' action='" . Toolbox::getItemTypeFormURL('PluginMydashboardHTMLEditor') . "'>";
      }

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_1'>";
      echo "<th>" . $item->fields['name'] . "</th></tr>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>";

      $rand = mt_rand();

      echo '<textarea id="custom_css_code_' . $rand . '" name="content" ';
      echo '>';
      echo Html::entities_deep($item->fields['content']);
      echo '</textarea>';

      $editor_options = [
         'mode'         => 'text/css',
         'lineNumbers'  => true,
         'lineWrapping' => true,
         // Autocomplete with CTRL+SPACE
         'extraKeys'    => [
            'Ctrl-Space' => 'autocomplete',
         ],

         // Code folding configuration
         'foldGutter'   => true,
         'gutters'      => [
            'CodeMirror-linenumbers',
            'CodeMirror-foldgutter'
         ],
      ];

      echo Html::scriptBlock('
      $(function() {
         var textarea = document.getElementById("custom_css_code_' . $rand . '");
         var editor = CodeMirror.fromTextArea(textarea, ' . json_encode($editor_options) . ');

         // Fix bad display of gutter (see https://github.com/codemirror/CodeMirror/issues/3098 )
         setTimeout(function () {editor.refresh();}, 10);
      });
   ');

      echo "</td></tr>\n";

      if ($closeform) {
         echo "<tr class='tab_bg_1 center'>";
         echo "<td>";
         echo Html::hidden('id', ['value' => $item->getID()]);
         echo Html::submit(_sx('button', 'Save'), ['name' => 'update']);
         echo "</td></tr>\n";
         echo "</table>";
         Html::closeForm();
      } else {
         echo "</table>";
      }
   }
}
