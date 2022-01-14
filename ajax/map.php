<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

include('../../../inc/includes.php');
header("Content-Type: application/json; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

$result = [];
if (!isset($_POST['itemtype']) || !isset($_POST['params'])) {
   http_response_code(500);
   $result = [
      'success' => false,
      'message' => __('Required argument missing!')
   ];
} else {
   $itemtype = $_POST['itemtype'];
   $params   = $_POST['params'];

   $data = Search::prepareDatasForSearch('Ticket', $params);
   Search::constructSQL($data);
   Search::constructData($data);

   $lat_field = $itemtype . '_998';
   $lng_field = $itemtype . '_999';
   $name_field = $itemtype . '_3';

   $rows   = $data['data']['rows'];
   $points = [];
   foreach ($rows as $row) {
      $idx = $row['raw']["ITEM_$lat_field"] . ',' . $row['raw']["ITEM_$lng_field"];

      if (isset($points[$idx])) {
         $points[$idx]['count'] += 1;
      } else {
         $points[$idx] = [
            'lat'    => $row['raw']["ITEM_$lat_field"],
            'lng'    => $row['raw']["ITEM_$lng_field"],
            'title'  => $row['raw']["ITEM_$name_field"],
            'loc_id' => $row['raw']['loc_id'],
            'count'  => 1
         ];
      }
   }
   $result['points'] = $points;
}

echo json_encode($result);
