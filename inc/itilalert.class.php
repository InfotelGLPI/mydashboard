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
 * Class PluginMydashboardItilAlert
 */
class PluginMydashboardItilAlert extends CommonDBTM {

   /**
    * @param $item
    */
   function showForItem($item) {
      global $CFG_GLPI;

      $items_id = $item->getID();
      $item->getFromDB($items_id);
      $itemtype = $item->getType();
      $this->getFromDBByCrit(['items_id' => $items_id,
                              'itemtype' => $itemtype]);

      $reminder = new Reminder();

      if (!isset($this->fields['reminders_id']) || $this->fields['reminders_id']== 0) {

         echo "<table class='tab_cadre_fixe'>";
         echo "<th>" . PluginMydashboardMenu::getTypeName(2) . "</th>";
         echo "<tr class='tab_bg_1'><td class='center'>";
         echo "<button type='submit' onclick=\"createAlert('$itemtype', $items_id)\">" . __("Create a new alert", "mydashboard") . "</button>";
         echo '<script>
            function createAlert(itemtype, items_id) {
              $conf = confirm("' . __('Create a new alert', 'mydashboard') . '");
              if($conf){
                  $.ajax({
                      url: "' . $CFG_GLPI['root_doc'] . '/plugins/mydashboard/ajax/createalert.php",
                      type: "POST",
                      data: { "itemtype": itemtype, "items_id": items_id},
                      success: function()
                          {
                              window.location.reload()
                          }
                  });
                }
              }

            </script>';
         echo "</td></tr>";
         echo "</table>";
      } else {
         $reminders_id = $this->fields['reminders_id'];
      }

      if (isset($this->fields['reminders_id'])
          && $this->fields['reminders_id'] > 0) {
         $reminder->getFromDB($reminders_id);
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr>";
         echo "<th colspan='2'>" . __('Linked reminder', 'mydashboard') . "</a></th>";
         echo "</tr>";
         echo "<tr class='tab_bg_2'>";
         echo "<td>" . __("Name") . "</td>";
         echo "<td>";
         echo nl2br($reminder->getLink());
         echo "</td>";
         echo "</tr>";
         echo "<tr class='tab_bg_2'>";
         echo "<td>" . __("Comment") . "</td>";
         echo "<td>";
         $content = Toolbox::unclean_cross_side_scripting_deep(html_entity_decode($reminder->fields['text'],
                                                                                  ENT_QUOTES,
                                                                                  "UTF-8"));
         echo $content;
         echo "</td>";
         echo "</tr>";
         echo "</table>";

         $alert = new PluginMydashboardAlert();
         $alert->getFromDBByCrit(['reminders_id' => $reminders_id]);

         if (isset($alert->fields['id'])) {
            $id                = $alert->fields['id'];
            $impact            = $alert->fields['impact'];
            $itilcategories_id = $alert->fields['itilcategories_id'];
            $type              = $alert->fields['type'];
            $is_public         = $alert->fields['is_public'];
         } else {
            $id                = -1;
            $type              = 0;
            $impact            = 0;
            $itilcategories_id = 0;
            $is_public         = 0;
         }
         echo "<form action='" . $alert->getFormURL() . "' method='post' >";
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th colspan='2'>" . _n('Alert', 'Alerts', 2, 'mydashboard') . "</th></tr>";

         $types    = [];
         $types[0] = _n('Network alert', 'Network alerts', 1, 'mydashboard');
         $types[1] = _n('Scheduled maintenance', 'Scheduled maintenances', 1, 'mydashboard');
         $types[2] = _n('Information', 'Informations', 1, 'mydashboard');

         echo "<tr class='tab_bg_2'><td>" . __("Type") . "</td><td>";
         Dropdown::showFromArray('type', $types, [
                                          'value' => $type
                                       ]
         );
         echo "</td></tr>";

         $impacts    = [];
         $impacts[0] = __("No impact", "mydashboard");
         for ($i = 1; $i <= 5; $i++) {
            $impacts[$i] = CommonITILObject::getImpactName($i);
         }

         echo "<tr class='tab_bg_2'><td>" . __("Alert level", "mydashboard") . "</td><td>";
         Dropdown::showFromArray('impact', $impacts, [
                                            'value' => $impact
                                         ]
         );
         echo "</td></tr>";

         echo "<tr class='tab_bg_2'>";
         echo "<td>" . __('Linked with a ticket category', 'mydashboard') . "</td>";
         echo "<td>";
         $opt = ['name'        => 'itilcategories_id',
                 'value'       => $itilcategories_id,
                 'entity'      => $_SESSION['glpiactiveentities'],
                 'entity_sons' => true];
         ITILCategory::dropdown($opt);
         echo "</td>";
         echo "</tr>";

         echo "<tr class='tab_bg_2'><td>" . __("Public") . "</td><td>";
         Dropdown::showYesNo('is_public', $is_public);
         echo "</td></tr>";

         if (Session::haveRight("reminder_public", UPDATE)) {
            echo Html::hidden("id", ['value' => $id]);
            echo Html::hidden("reminders_id", ['value' => $reminders_id]);
            echo "<tr class='tab_bg_1 center'><td>";
            if ($id > 0) {
               echo Html::submit(_sx('button', 'Update'), ['name' => 'update']);
            } else {
               echo Html::submit(_sx('button', 'Add'), ['name' => 'update']);
            }
            echo "</td><td>";
            if ($id > 0) {
               echo Html::submit(_sx('button', 'Delete permanently'), ['name' => 'delete']);
            }
            echo "</td></tr>";
         }
         echo "</table>";
         Html::closeForm();

         $reminder->showVisibility();
      }
   }

   static function purgeAlerts(Reminder $reminder) {

      $alert = new PluginMydashboardAlert();
      $alert->deleteByCriteria(['reminders_id' => $reminder->getField("id")]);

      $itilalert = new self();
      $itilalert->deleteByCriteria(['reminders_id' => $reminder->getField("id")]);
   }
}
