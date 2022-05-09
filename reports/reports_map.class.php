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

/**
 * Class PluginMydashboardReports_Map
 */
class PluginMydashboardReports_Map extends CommonGLPI {

   private       $options;
   private       $pref;
   public static $reports = [29];

   /**
    * PluginMydashboardReports_Map constructor.
    *
    * @param array $_options
    */
   public function __construct($_options = []) {
      $this->options = $_options;

      $preference = new PluginMydashboardPreference();
      if (Session::getLoginUserID() !== false
          && !$preference->getFromDB(Session::getLoginUserID())) {
         $preference->initPreferences(Session::getLoginUserID());
      }
      $preference->getFromDB(Session::getLoginUserID());
      $this->preferences = $preference->fields;
   }

   /**
    * @return array
    */
   public function getWidgetsForItem() {

      $widgets = [
         __('Map', "mydashboard") => [
            $this->getType() . "29" => ["title"   => __("OpenStreetMap - Opened tickets by location", "mydashboard"),
                                        "icon"    => "ti ti-map",
                                        "comment" => __("Display Tickets by location (Latitude / Longitude)", "mydashboard")],
         ]
      ];
      return $widgets;
   }


   /**
    * @param       $widgetId
    * @param array $opt
    *
    * @return \PluginMydashboardHtml
    */
   public function getWidgetContentForItem($widgetId, $opt = []) {
      global $DB, $CFG_GLPI;
      $isDebug = $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE;
      $dbu     = new DbUtils();
      switch ($widgetId) {

         case $this->getType() . "29":

            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() == 'central') {
               $criterias = ['entities_id',
                             'is_recursive',
                             'type',
                             'technicians_groups_id',
                             'group_is_recursive'];
            }
            if (isset($_SESSION['glpiactiveprofile']['interface'])
                && Session::getCurrentInterface() != 'central') {
               $criterias = ['type'];
            }

            $paramsc = ["preferences" => $this->preferences,
                        "criterias"   => $criterias,
                        "opt"         => $opt];
            $options = PluginMydashboardHelper::manageCriterias($paramsc);

            $opt  = $options['opt'];
            $crit = $options['crit'];

            $type                 = $opt['type'];
            $entities_id_criteria = $crit['entity'];
            $sons_criteria        = $crit['sons'];
            $groups_criteria      = $crit['technicians_groups_id'];

            $widget = new PluginMydashboardHtml();
            $title  = __("OpenStreetMap - Opened tickets by location", "mydashboard");
            $widget->setWidgetComment(__("Display Tickets by location (Latitude / Longitude)", "mydashboard"));
            $widget->setWidgetTitle((($isDebug) ? "29 " : "") . $title);

            $params['as_map']     = 1;
            $params['is_deleted'] = 0;
            $params['order']      = 'DESC';
            $params['sort']       = 19;
            $params['start']      = 0;
            $params['list_limit'] = 999999;
            $itemtype             = 'Ticket';

            if (isset($sons_criteria) && $sons_criteria > 0) {
               $params['criteria'][] = [
                  'field'      => 80,
                  'searchtype' => 'under',
                  'value'      => $entities_id_criteria
               ];
            } else {
               $params['criteria'][] = [
                  'field'      => 80,
                  'searchtype' => 'equals',
                  'value'      => $entities_id_criteria
               ];
            }
            $params['criteria'][] = [
               'link'       => 'AND',
               'field'      => 12,
               'searchtype' => 'equals',
               'value'      => 'notold'
            ];
            $params['criteria'][] = [
               'link'       => 'AND NOT',
               'field'      => 998,
               'searchtype' => 'contains',
               'value'      => 'NULL'
            ];
            $params['criteria'][] = [
               'link'       => 'AND NOT',
               'field'      => 999,
               'searchtype' => 'contains',
               'value'      => 'NULL'
            ];

            if ($type > 0) {
               $params['criteria'][] = [
                  'link'       => 'AND',
                  'field'      => 14,
                  'searchtype' => 'equals',
                  'value'      => $type
               ];
            }
            $grp_criteria = is_array($groups_criteria) ? $groups_criteria : [$groups_criteria];
            if (is_array($grp_criteria) && count($grp_criteria) > 0) {
               $options['criteria'][7]['link'] = 'AND';
               $nb                             = 0;
               foreach ($grp_criteria as $group) {
                  if ($nb == 0) {
                     $options['criteria'][7]['criteria'][$nb]['link'] = 'AND';
                  } else {
                     $options['criteria'][7]['criteria'][$nb]['link'] = 'OR';
                  }
                  $options['criteria'][7]['criteria'][$nb]['field']      = 8;
                  $options['criteria'][7]['criteria'][$nb]['searchtype'] = 'equals';
                  $options['criteria'][7]['criteria'][$nb]['value']      = $group;
                  $nb++;
               }
            }

//            if ($groups_criteria > 0) {
//               $params['criteria'][] = [
//                  'link'       => 'AND',
//                  'field'      => 8,
//                  'searchtype' => 'equals',
//                  'value'      => $groups_criteria
//               ];
//            }
            $data = Search::prepareDatasForSearch('Ticket', $params);
            Search::constructSQL($data);
            Search::constructData($data);

            $paramsh = ["widgetId"  => $widgetId,
                        "name"      => 'TicketsByLocationOpenStreetMap',
                        "onsubmit"  => false,
                        "opt"       => $opt,
                        "criterias" => $criterias,
                        "export"    => false,
                        "canvas"    => false,
                        "nb"        => 1];
            $graph   = PluginMydashboardHelper::getGraphHeader($paramsh);

            if ($data['data']['totalcount'] > 0) {

               $target   = $data['search']['target'];
               $criteria = $data['search']['criteria'];

               $criteria[]   = [
                  'link'       => 'AND',
                  'field'      => 83,
                  'searchtype' => 'equals',
                  'value'      => 'CURLOCATION'
               ];
               $globallinkto = Toolbox::append_params(
                  [
                     'criteria'     => Toolbox::stripslashes_deep($criteria),
                     'metacriteria' => Toolbox::stripslashes_deep($data['search']['metacriteria'])
                  ],
                  '&amp;'
               );
               $parameters   = "as_map=0&amp;sort=" . $data['search']['sort'] . "&amp;order=" . $data['search']['order'] . '&amp;' .
                               $globallinkto;

               $typename = $itemtype::getTypeName(2);

               if (strpos($target, '?') == false) {
                  $fulltarget = $target . "?" . $parameters;
               } else {
                  $fulltarget = $target . "&" . $parameters;
               }
               $root_doc = PLUGIN_MYDASHBOARD_WEBDIR;
               $graph    .= "<script>
                var _loadMap = function(map_elt, itemtype) {
                  L.AwesomeMarkers.Icon.prototype.options.prefix = 'fas';
                  var _micon = 'circle';
      
                  var stdMarker = L.AwesomeMarkers.icon({
                     icon: _micon,
                     markerColor: 'blue'
                  });
      
                  var aMarker = L.AwesomeMarkers.icon({
                     icon: _micon,
                     markerColor: 'cadetblue'
                  });
      
                  var bMarker = L.AwesomeMarkers.icon({
                     icon: _micon,
                     markerColor: 'purple'
                  });
      
                  var cMarker = L.AwesomeMarkers.icon({
                     icon: _micon,
                     markerColor: 'darkpurple'
                  });
      
                  var dMarker = L.AwesomeMarkers.icon({
                     icon: _micon,
                     markerColor: 'red'
                  });
      
                  var eMarker = L.AwesomeMarkers.icon({
                     icon: _micon,
                     markerColor: 'darkred'
                  });
      
      
                  //retrieve geojson data
                  map_elt.spin(true);
                  $.ajax({
                     dataType: 'json',
                     method: 'POST',
                     url: '$root_doc/ajax/map.php',
                     data: {
                        itemtype: itemtype,
                        params: " . json_encode($params) . "
                     }
                  }).done(function(data) {
                     var _points = data.points;
                     var _markers = L.markerClusterGroup({
                        iconCreateFunction: function(cluster) {
                           var childCount = cluster.getChildCount();
      
                           var markers = cluster.getAllChildMarkers();
                           var n = 0;
                           for (var i = 0; i < markers.length; i++) {
                              n += markers[i].count;
                           }
      
                           var c = ' marker-cluster-';
                           if (n < 10) {
                              c += 'small';
                           } else if (n < 100) {
                              c += 'medium';
                           } else {
                              c += 'large';
                           }
      
                           return new L.DivIcon({ html: '<div><span>' + n + '</span></div>', className: 'marker-cluster' + c, iconSize: new L.Point(40, 40) });
                        }
                     });
      
                     $.each(_points, function(index, point) {
                        var _title = '<strong>' + point.title + '</strong><br/><a target=\'_blank\' href=\''+'$fulltarget'.replace(/CURLOCATION/, point.loc_id)+'\'>" . sprintf(__('%1$s %2$s'), 'COUNT', $typename) . "'.replace(/COUNT/, point.count)+'</a>';
                        if (point.types) {
                           $.each(point.types, function(tindex, type) {
                              _title += '<br/>" . sprintf(__('%1$s %2$s'), 'COUNT', 'TYPE') . "'.replace(/COUNT/, type.count).replace(/TYPE/, type.name);
                           });
                        }
                        var _icon = stdMarker;
                        if (point.count < 10) {
                           _icon = stdMarker;
                        } else if (point.count < 100) {
                           _icon = aMarker;
                        } else if (point.count < 1000) {
                           _icon = bMarker;
                        } else if (point.count < 5000) {
                           _icon = cMarker;
                        } else if (point.count < 10000) {
                           _icon = dMarker;
                        } else {
                           _icon = eMarker;
                        }
                        var _marker = L.marker([point.lat, point.lng], { icon: _icon, title: point.title });
                        _marker.count = point.count;
                        _marker.bindPopup(_title);
                        _markers.addLayer(_marker);
                     });
      
                     map_elt.addLayer(_markers);
                     map_elt.fitBounds(
                        _markers.getBounds(), {
                           padding: [50, 50],
                           maxZoom: 12
                        }
                     );
                  }).fail(function (response) {
                     var _data = response.responseJSON;
                     var _message = '" . __s('An error occured loading data :(') . "';
                     if (_data.message) {
                        _message = _data.message;
                     }
                     var fail_info = L.control();
                     fail_info.onAdd = function (map) {
                        this._div = L.DomUtil.create('div', 'fail_info');
                        this._div.innerHTML = _message + '<br/><span id=\'reload_data\'><i class=\'ti ti-refresh\'></i> " . __s('Reload') . "</span>';
                        return this._div;
                     };
                     fail_info.addTo(map_elt);
                     $('#reload_data').on('click', function() {
                        $('.fail_info').remove();
                        _loadMap(map_elt);
                     });
                  }).always(function() {
                     //hide spinner
                     map_elt.spin(false);
                  });
               };
               $(function() {
                       var map = initMap($('#TicketsByLocationOpenStreetMap'), 'map', '500px');
                         _loadMap(map, 'Ticket');
                   });
               ";
               $graph .= "</script>";
            }
            $graph .= "<div id=\"TicketsByLocationOpenStreetMap\" class=\"mapping\"></div>";
            $widget->toggleWidgetRefresh();
            $widget->setWidgetHtmlContent(
               $graph
            );

            return $widget;
            break;

         default:
            break;
      }
   }
}
