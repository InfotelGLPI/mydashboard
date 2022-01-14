<?php
/*
 -------------------------------------------------------------------------
 MyDashboard plugin for GLPI
 Copyright (C) 2015-2022 by the MyDashboard Development Team.
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

if (strpos($_SERVER['PHP_SELF'], "createalert.php")) {
   $AJAX_INCLUDE = 1;
   include('../../../inc/includes.php');
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

if (isset($_POST['itemtype'])) {
   $class = $_POST['itemtype'];
   $item  = new $class();
   if ($class == 'PluginEventsmanagerEvent') {
      if (isset($_POST['items_id'])) {
         if ($item->getFromDB($_POST['items_id'])) {
            $reminder     = new Reminder();
            $reminders_id = $reminder->add(['name'     => addslashes($item->fields['name']),
                                                 'text'     => addslashes($item->fields['comment']),
                                                 'users_id' => $_SESSION['glpiID']]);

            $item->update(['id'           => $_POST['items_id'],
                                'reminders_id' => $reminders_id]);
         }
      }
   } else if ($class == 'Problem' || $class == 'Change') {
      if (isset($_POST['items_id'])) {
         if ($item->getFromDB($_POST['items_id'])) {
            $reminder = new Reminder();
            $reminders_id = $reminder->add(['name' => addslashes($item->fields['name']),
               'text' => addslashes($item->fields['content']),
               'users_id' => $_SESSION['glpiID']]);
            $alert = new PluginMydashboardItilAlert();
            $alert->add(['items_id' => $_POST['items_id'],
               'itemtype' => $_POST['itemtype'],
               'reminders_id' => $reminders_id]);
         }
      }
   } else if (in_array($class,PluginMydashboardAlert::getTypes())){
      if (isset($_POST['items_id'])) {
         if ($item->getFromDB($_POST['items_id'])) {
            $name = method_exists($item,"getNameAlert")?$item->getNameAlert():$item->fields["name"];
            $content = method_exists($item,"getContentAlert")?$item->getContentAlert():$item->fields["content"];
            $reminder     = new Reminder();
            $reminders_id = $reminder->add(['name'     => addslashes($name),
               'text'     => addslashes($content),
               'users_id' => $_SESSION['glpiID']]);
            $alert        = new PluginMydashboardItilAlert();
            $alert->add(['items_id'  => $_POST['items_id'],
               'itemtype'  => $_POST['itemtype'],
               'reminders_id' => $reminders_id]);
         }
      }
   }
}
