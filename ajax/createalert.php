<?php

/*
 -------------------------------------------------------------------------
 mydashboard plugin for GLPI
 Copyright (C) 2016-2026 by the mydashboard Development Team.

 https://github.com/InfotelGLPI/mydashboard
 -------------------------------------------------------------------------

 LICENSE

 This file is part of mydashboard.

 mydashboard is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 3 of the License, or
 (at your option) any later version.

 mydashboard is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with mydashboard. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

use GlpiPlugin\Mydashboard\Alert;
use GlpiPlugin\Mydashboard\ItilAlert;

Session::checkRight("plugin_mydashboard_config", UPDATE);

if (strpos($_SERVER['PHP_SELF'], "createalert.php")) {
   $AJAX_INCLUDE = 1;
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

if (isset($_POST['itemtype'])) {
   $class = $_POST['itemtype'];
   $allowed_types = array_merge([\GlpiPlugin\Eventsmanager\Event::class, 'Problem', 'Change'], Alert::getTypes());
   if (!in_array($class, $allowed_types, true)) {
      http_response_code(400);
      exit;
   }
   $item = new $class();
   if ($class == \GlpiPlugin\Eventsmanager\Event::class) {
      if (isset($_POST['items_id'])) {
         // Enforce access control on the source item (global right + entity) before
         // copying its data and updating it: this branch updates the event, so
         // require UPDATE. can() also loads the record into $item->fields.
         if ($item->can($_POST['items_id'], UPDATE)) {
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
         // Enforce access control on the source item (global right + entity) before
         // copying its name/content into a reminder. can() also loads $item->fields.
         if ($item->can($_POST['items_id'], READ)) {
            $reminder = new Reminder();
            $reminders_id = $reminder->add(['name' => addslashes($item->fields['name']),
               'text' => addslashes($item->fields['content']),
               'users_id' => $_SESSION['glpiID']]);
            $alert = new ItilAlert();
            $alert->add(['items_id' => $_POST['items_id'],
               'itemtype' => $_POST['itemtype'],
               'reminders_id' => $reminders_id]);
         }
      }
   } else if (in_array($class,Alert::getTypes())){
      if (isset($_POST['items_id'])) {
         // Enforce access control on the source item (global right + entity) before
         // copying its name/content into a reminder. can() also loads $item->fields.
         if ($item->can($_POST['items_id'], READ)) {
            $name = method_exists($item,"getNameAlert")?$item->getNameAlert():$item->fields["name"];
            $content = method_exists($item,"getContentAlert")?$item->getContentAlert():$item->fields["content"];
            $reminder     = new Reminder();
            $reminders_id = $reminder->add(['name'     => addslashes($name),
               'text'     => addslashes($content),
               'users_id' => $_SESSION['glpiID']]);
            $alert        = new ItilAlert();
            $alert->add(['items_id'  => $_POST['items_id'],
               'itemtype'  => $_POST['itemtype'],
               'reminders_id' => $reminders_id]);
         }
      }
   }
}
