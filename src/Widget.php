<?php

/**
 * -------------------------------------------------------------------------
 * mydashboard plugin for GLPI
 * Copyright (C) 2016-2026 by the mydashboard Development Team.
 *
 * https://github.com/InfotelGLPI/mydashboard
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of mydashboard.
 *
 * mydashboard is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * mydashboard is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with mydashboard. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Mydashboard;

use CommonDBTM;
use CommonGLPI;
use DBConnection;
use DbUtils;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QueryExpression;
use Glpi\RichText\RichText;
use GlpiPlugin\Badges\Badge;
use GlpiPlugin\Mydashboard\Reports\Reports_Bar;
use GlpiPlugin\Mydashboard\Reports\Reports_Custom;
use GlpiPlugin\Mydashboard\Reports\Reports_Line;
use GlpiPlugin\Mydashboard\Reports\Reports_Map;
use GlpiPlugin\Mydashboard\Reports\Reports_Pie;
use GlpiPlugin\Mydashboard\Reports\Reports_Table;
use GlpiPlugin\Servicecatalog\Config as ServiceCatalogConfig;
use Migration;
use Session;

/**
 * Class Widget
 */
class Widget extends CommonDBTM
{
    public static $rightname = "plugin_mydashboard_config";
    public $dohistory = true;

    public static $KPI      = 0;
    public static $TABLE    = 1;
    public static $PIE      = 2;
    public static $BAR      = 3;
    public static $LINE     = 4;
    public static $MAP      = 5;
    public static $PLANNING = 6;
    public static $OTHERS   = 7;
    /**
     * @param int $nb
     *
     * @return string
     */
    public static function getTypeName($nb = 0)
    {
        return __('Widget', 'mydashboard');
    }

    public static function getIcon()
    {
        return Menu::getIcon();
    }

    public static function canCreate(): bool
    {
        return Session::haveRightsOr('plugin_mydashboard_config', [CREATE, UPDATE]);
    }

    /**
     * @return bool
     */
    public static function canView(): bool
    {
        return Session::haveRight('plugin_mydashboard_config', UPDATE);
    }

    public static function canUpdate(): bool
    {
        return Session::haveRight('plugin_mydashboard_config', UPDATE);
    }


    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addDefaultFormTab($ong)
            ->addStandardTab(self::class, $ong, $options)
            ->addStandardTab('Log', $ong, $options);

        return $ong;
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (self::canView()) {
            switch (get_class($item)) {
                case self::class:
                    return self::createTabEntry(__s("Filters", "mydashboard"), 0, $item::getType(), "ti ti-filter");
            }
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        /** @var Widget $item */
        switch ($item->getType()) {
            case self::class:
                $item->showFilters();
                return true;
        }
        return false;
    }

    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $options);
        $rand = mt_rand();

        // showFormHeader() emits the surrounding <form> and <table>
        ob_start();
        $this->showFormHeader($options);
        $form_header_html = ob_get_clean();

        echo TemplateRenderer::getInstance()->render('@mydashboard/widget_form.html.twig', [
            'form_header_html' => $form_header_html,
            'rand' => $rand,
            'name_input_html' => \Html::input('name', [
                'value' => $this->fields["name"],
                'id' => "textfield_name$rand",
                'readonly' => 'readonly',
            ]),
            'class_input_html' => \Html::input('class', [
                'value' => $this->fields["class"],
                'id' => "textfield_class$rand",
                'readonly' => 'readonly',
            ]),
        ]);

        return true;
    }


    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'   => 'common',
            'name' => self::getTypeName(2),
        ];

        $tab[] = [
            'id'         => '1',
            'table'      => $this->getTable(),
            'field'      => 'name',
            'name'       => __s('Internal name', 'mydashboard'),
            'datatype' => 'itemlink',
            'itemlink_type' => $this->getType(),
        ];

        $tab[] = [
            'id'         => '2',
            'table'      => $this->getTable(),
            'field'      => 'class',
            'name'       => __s('Class', 'mydashboard'),
            'searchtype' => 'equals',
            'datatype'   => 'text',
        ];

        $tab[] = [
            'id' => '30',
            'table' => $this->getTable(),
            'field' => 'id',
            'name' => __('ID'),
            'datatype' => 'number',
        ];

        return $tab;
    }

    public function showFilters() {}

    /**
     * @param $type
     *
     * @return mixed
     */
    public static function getIconByType($type)
    {
        switch ($type) {
            case self::$KPI:
                return 'ti ti-info-circle';
            case self::$TABLE:
                return 'ti ti-table';
            case self::$PIE:
                return 'ti ti-chart-pie';
            case self::$BAR:
                return 'ti ti-chart-bar';
            case self::$LINE:
                return 'ti ti-chart-area-line';
            case self::$MAP:
                return 'ti ti-map';
            case self::$PLANNING:
                return 'ti ti-calendar';
        }
        return 'ti ti-dashboard';
    }


    /**
     * @param $type
     *
     * @return mixed
     */
    public static function getNameByType($type)
    {
        switch ($type) {
            case self::$KPI:
                return __('Indicators', 'mydashboard');
            case self::$TABLE:
                return __('Tables', 'mydashboard');
            case self::$PIE:
                return __('Pie charts', 'mydashboard');
            case self::$BAR:
                return __('Bar charts', 'mydashboard');
            case self::$LINE:
                return __('Line charts', 'mydashboard');
            case self::$MAP:
                return __('Map', 'mydashboard');
            case self::$PLANNING:
                return __('Planning');
        }
        return __('Others');
    }

    /**
     * Get the widget name with his id
     *
     * @param type  $widgetId
     *
     * @return string, the widget 'name'
     * @global type $DB
     *
     */
    public function getWidgetNameById($widgetId)
    {
        if ($this->getFromDBByCrit(['id' => $widgetId]) === false) {
            return null;
        } else {
            return $this->fields['name'] ?? null;
        }
    }

    /**
     * Get the widgets_id by its 'name'
     *
     * @param string $widgetName
     *
     * @return the widgets_id if found, NULL otherwise
     * @global type  $DB
     *
     */
    public function getWidgetIdByName($widgetName)
    {
        unset($this->fields);
        if ($this->getFromDBByCrit(['name' => $widgetName]) === false) {
            return null;
        } else {
            return $this->fields['id'] ?? null;
        }
    }

    /**
     * Save a new widget Name
     *
     * @param string $widgetName
     *
     * @return true if the new widget name has been added, FALSE otherwise
     * @global type  $DB
     *
     */
    public function saveWidget($widgetName, $widgetClass)
    {
        if (isset($widgetName) && $widgetName !== "") {
            $this->fields["id"] = null;
            $id                 = $this->getWidgetIdByName($widgetName);

            if (!isset($id)) {
                $this->fields = [];
                $this->add(["name" => $widgetName, "class" => $widgetClass]);
            }
            return true;
        } else {
            return false;
        }
    }


    /**
     *
     */
    public function migrateWidgets()
    {
        $dbu     = new DbUtils();
        $reports = $dbu->getAllDataFromTable($this->getTable());
        foreach ($reports as $report) {
            $name = $report['name'];
            if (strpos($report['name'], "GlpiPlugin\Mydashboard\Infotel") !== false && strpos($report['name'], "GlpiPlugin\Mydashboard\Infotelcw") === false) {
                $widgettmp = preg_match_all('!\d+!', $name, $matches);
                if ($widgettmp == 1) {
                    $widgetName = "";
                    foreach ($matches[0] as $k => $v) {
                        if (in_array($v, Reports_Bar::$reports)) {
                            $widgetName = Reports_Bar::class . $v;
                        }
                        if (in_array($v, Reports_Pie::$reports)) {
                            $widgetName =  Reports_Pie::class . $v;
                        }
                        if (in_array($v, Reports_Table::$reports)) {
                            $widgetName = Reports_Table::class . $v;
                        }
                        if (in_array($v, Reports_Line::$reports)) {
                            $widgetName = Reports_Line::class . $v;
                        }
                        if (in_array($v, Reports_Map::$reports)) {
                            $widgetName = Reports_Map::class . $v;
                        }
                        if ($widgetName != "") {
                            $this->update(["id" => $report['id'], "name" => $widgetName]);
                        }
                    }
                }
            }
            if (strpos($report['name'], "GlpiPlugin\Mydashboard\Infotelcw") !== false) {
                $widgettmp = preg_match_all('!\d+!', $name, $matches);
                if ($widgettmp == 1) {
                    foreach ($matches[0] as $k => $v) {
                        $widgetName = Reports_Custom::class . $v;
                        if ($widgetName != "") {
                            $this->update(["id" => $report['id'], "name" => $widgetName]);
                        }
                    }
                }
            }
        }
    }

    public static function removeBackslashes($classname)
    {
        if ($classname != null) {
            $replace = str_replace('\\', '', $classname);
            $replace = str_replace('_', '', $replace);
            return $replace;
        }
    }

    public static function getCompleteWidgetList($preload = false, $withslashes = false)
    {

        //Load widgets
        $widgetlist = Widgetlist::getList(true, -1, "central", $preload);
        $i          = 1;
        $self       = new self();
        $widgets    = [];
        foreach ($widgetlist as $plugin => $widgetclasses) {
            foreach ($widgetclasses as $widgetclass => $list) {
                if (is_array($list)) {
                    foreach ($list as $k => $namelist) {
                        if (is_array($namelist)) {
                            foreach ($namelist as $idl => $val) {
                                $id                  = $self->getWidgetIdByName($idl);

                                if ($withslashes == true) {
                                    $widgets['gs' . $id] = ["class" => self::removeBackslashes($widgetclass), "id" => self::removeBackslashes($idl), "parent" => $k];
                                } else {
                                    $widgets['gs' . $id] = ["class" => $widgetclass, "id" => $idl, "parent" => $k];
                                }

                                $i++;
                            }
                        } else {
                            $id                  = $self->getWidgetIdByName($k);
                            if ($withslashes == true) {
                                $widgets['gs' . $id] = ["class" => self::removeBackslashes($widgetclass), "id" => self::removeBackslashes($k), "parent" => $widgetclass];
                            } else {
                                $widgets['gs' . $id] = ["class" => $widgetclass, "id" => $k, "parent" => $widgetclass];
                            }
                        }
                    }
                } else {
                    $id                  = $self->getWidgetIdByName($widgetclass);
                    if ($withslashes == true) {
                        $widgets['gs' . $id] = ["class" => self::removeBackslashes($widgetclasses), "id" =>  self::removeBackslashes($widgetclass)];
                    } else {
                        $widgets['gs' . $id] = ["class" => $widgetclasses, "id" => $widgetclass];
                    }
                }
            }
        }
        return $widgets;
    }


    /**
     * Returns the widget with the ID
     *
     * @param       $id
     * @param array $opt
     *
     * @return string
     */
    public static function getWidget($id, $widgets, $opt = [])
    {
        $class = "bt-col-md-11";
        if (isset($widgets[$id])) {
            return self::loadWidget($widgets[$id]["class"], $widgets[$id]["id"], $class, $opt);
        }

        $message = __("This widget doesn't exist anymore", 'mydashboard');
        if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
            $message .= " - " . $id;
        }
        $msg = "<div class='center alert alert-warning ' role='alert'><br><br>";
        $msg .= "<i style='font-size:3em;' class='ti ti-alert-triangle'></i>";
        $msg .= "<br><br><span class='b'>$message</span></div>";

        return $msg;
    }


    /**
     * @param $id
     *
     * @return bool
     */
    public static function getGsID($id)
    {
        $widgets = self::getCompleteWidgetList(false, true);

        foreach ($widgets as $gs => $widgetclasses) {
            $gslist[$widgetclasses['id']] = $gs;
        }

        if (isset($gslist[self::removeBackslashes($id)])) {
            return $gslist[self::removeBackslashes($id)];
        }
        return false;
    }


    /**
     * Normalize the client-controlled widget parameters that end up interpolated
     * into raw SQL by the report classes (date filters). Numeric year/month are
     * cast to integers and free-form begin/end dates are reformatted to a canonical
     * datetime, neutralizing any SQL injection payload while preserving behaviour.
     * Array-valued year/month (multi-value criteria) are left untouched.
     *
     * @param array $opt
     *
     * @return array
     */
    public static function sanitizeWidgetParams($opt)
    {
        if (!is_array($opt)) {
            return $opt;
        }

        foreach (['year', 'month', 'start_year', 'start_month', 'end_year', 'end_month'] as $key) {
            if (isset($opt[$key]) && !is_array($opt[$key]) && $opt[$key] !== '') {
                $opt[$key] = (int) $opt[$key];
            }
        }

        foreach (['begin', 'end'] as $key) {
            if (isset($opt[$key]) && is_string($opt[$key]) && $opt[$key] !== '') {
                $timestamp = strtotime($opt[$key]);
                $opt[$key] = ($timestamp !== false) ? date('Y-m-d H:i:s', $timestamp) : null;
            }
        }

        return $opt;
    }

    /**
     * @param       $classname
     * @param       $widgetindex
     * @param       $parent
     * @param       $class
     * @param array $opt
     *
     * @return string
     */
    public static function loadWidget($classname, $widgetindex, $class, $opt = [])
    {
        // Central sanitization of the fully client-controlled widget parameters
        // ($opt originates from $_POST['params'] in ajax/refreshWidget.php and from the
        // stored grid on initial render). Several report classes interpolate these date
        // filters into raw SQL (QueryExpression / raw WHERE strings), so they must be
        // normalized here to prevent SQL injection with only the plugin READ right.
        $opt = self::sanitizeWidgetParams($opt);

        if (isset($classname) && isset($widgetindex)) {
            $classobject = getItemForItemtype($classname);
            if ($classobject && method_exists($classobject, "getWidgetContentForItem")) {
                if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
                    $TIMER = new Timer();
                    $TIMER->start();
                }
                $widget = $classobject->getWidgetContentForItem($widgetindex, $opt);
                if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
                    $loadwidget        = $TIMER->getTime();
                    $displayloadwidget = "";
                }

                $widgetindex = self::removeBackslashes($widgetindex);

                if (isset($widget) && ($widget instanceof Module)) {

                    $widget->setWidgetId($widgetindex);
                    //Then its Html content
                    $htmlContent = $widget->getWidgetHtmlContent();

                    if ($widget->getWidgetIsOnlyHTML()) {
                        $htmlContent = "";
                    }

                    //when we get jsondata some checkings and modification can be done by the widget class
                    //For example Datatable add some scripts to adapt the table to the template
                    $jsondata = $widget->getJSonDatas();

                    //Then its scripts (non evaluated, have to be evaluated client-side)
                    $scripts = $widget->getWidgetScripts();

                    //We prepare a "JSon object" compatible with sDashboard
                    $widgetTitle = $widget->getWidgetTitle();
                    $json
                        = [
                            "widgetTitle"     => $widgetTitle,
                            "widgetComment"   => $widget->getWidgetComment(),
                            "widgetId"        => self::removeBackslashes($widget->getWidgetId()),
                            "widgetType"      => $widget->getWidgetType(),
                            "widgetContent"   => "%widgetContent%",
                            "enableRefresh"   => json_decode($widget->getWidgetEnableRefresh()),
                            "refreshCallBack" => "function(){return mydashboard.getWidgetData('" . Menu::DASHBOARD_NAME . "','" . $classname . "', '" . $widget->getWidgetId() . "');}",
                            "html"            => $htmlContent,
                            "scripts"         => $scripts,
                            //                        "_glpi_csrf_token" => Session::getNewCSRFToken()
                        ];
                    $_SESSION["glpi_plugin_mydashboard_widgets"][$widget->getWidgetId()] = json_decode($widget->getWidgetEnableRefresh());
                    //safeJson because refreshCallBack must be a javascript function not a string,
                    // not a string, but a function in a json object is not valid
                    $widgetlistclass = new Widgetlist();
                    $menu = new Menu();
                    $views = $widgetlistclass->getViewNames();

                    $type  = $json['widgetType'];
                    $title = $json['widgetTitle'];

                    $comment = $json['widgetComment'];
                    //                    if (isset($view) && $view != -1) {
                    //                        $title .= "<span class='plugin_mydashboard_discret'>&nbsp;-&nbsp;" . $view . "</span>";
                    //                    }

                    //               $json  = Helper::safeJson($json);
                    $datas = json_decode($jsondata, true);

                    if ($type == "table") {
                        $opt = $widget->getOptions();

                        //                        $order = json_encode([[0, 'asc']]);

                        $order = json_encode([]);
                        if (isset($opt['bSort'])) {
                            $order = json_encode([$opt['bSort']]);
                        }
                        $defs = json_encode([]);
                        if (isset($opt['bDef'])) {
                            $defs = json_encode($opt['bDef']);
                        }

                        $dateformat = "D";
                        $mask       = 'MM-DD-YYYY';
                        if (isset($opt['bDate'])) {
                            $dateformat = $opt['bDate'][0];
                        }

                        if ($dateformat == "DHS") {
                            if (!isset($_SESSION["glpidate_format"])) {
                                $_SESSION["glpidate_format"] = 0;
                            }
                            $format = $_SESSION["glpidate_format"];
                            switch ($format) {
                                case 1: // DD-MM-YYYY
                                    $mask = 'DD-MM-YYYY HH:mm:SS';
                                    break;
                                case 2: // MM-DD-YYYY
                                    $mask = 'MM-DD-YYYY HH:mm:SS';
                                    break;
                            }
                        } elseif ($dateformat == "DH") {
                            if (!isset($_SESSION["glpidate_format"])) {
                                $_SESSION["glpidate_format"] = 0;
                            }
                            $format = $_SESSION["glpidate_format"];
                            switch ($format) {
                                case 1: // DD-MM-YYYY
                                    $mask = 'DD-MM-YYYY HH:mm';
                                    break;
                                case 2: // MM-DD-YYYY
                                    $mask = 'MM-DD-YYYY HH:mm';
                                    break;
                            }
                        } elseif ($dateformat == "D") {
                            if (!isset($_SESSION["glpidate_format"])) {
                                $_SESSION["glpidate_format"] = 0;
                            }
                            $format = $_SESSION["glpidate_format"];
                            switch ($format) {
                                case 1: // DD-MM-YYYY
                                    $mask = 'DD-MM-YYYY';
                                    break;
                                case 2: // MM-DD-YYYY
                                    $mask = 'MM-DD-YYYY';
                                    break;
                            }
                        }
                        $rand      = mt_rand();
                        $languages = json_encode($menu->getJsLanguages("datatables"));
                        //                  $display_count_on_home = intval($_SESSION['glpidisplay_count_on_home']);

                        $lengthMenulangs = [__('5 rows', 'mydashboard'),
                            __('10 rows', 'mydashboard'),
                            __('25 rows', 'mydashboard'),
                            __('50 rows', 'mydashboard'),
                            __('Show all', 'mydashboard'),
                        ];
                        $lengthMenulangs = json_encode($lengthMenulangs);
                        $root_doc        = PLUGIN_MYDASHBOARD_WEBDIR;

                        $widgetdisplay   = "<script type='text/javascript'>
               //         setTimeout(function () {
//                           $.fn.dataTable.moment('$mask');
                           $('#$widgetindex$rand').dataTable(
                               {
                                stateSave: true,
                                'stateSaveParams': function (settings, data) {
                                  data.gsId = '$widgetindex';
                                  if (typeof document.getElementsByName('profiles_id')[0] !== 'undefined') {
                                   data.profiles_id = document.getElementsByName('profiles_id')[0].value;
                                 }
                                },
                                'stateSaveCallback': function (settings, data) {
                                    // Send an Ajax request to the server with the state object

                                    $.ajax({
                                       'url': '$root_doc/ajax/state_save.php',
                                       'data': data,
                                       'dataType': 'json',
                                       'type': 'POST',
                                       'success': function(response) {},
                                       'error': function(response) {}
                                    });
                               },
                               'stateLoadCallback': function (settings, callback) {
                                 profiles_id='';
                                 if (typeof document.getElementsByName('profiles_id')[0] !== 'undefined') {
                                   profiles_id = document.getElementsByName('profiles_id')[0].value;
                                 }
                                $.ajax({
                                    url: '$root_doc/ajax/state_load.php?gsId={$widgetindex}&profiles_id='+profiles_id,
                                    dataType: 'json',
                                    success: function (json) {
                                      // state_load.php returns null when no grid state has been
                                      // saved yet (fresh widget/profile); guard before touching it.
                                      if (json === null) {
                                        callback(null);
                                        return;
                                      }
                                      //JSON parse the saved filter and set the time equal to now.
                                      json.time = +new Date();
                                      callback(json);
                                    },
                                    error: function () {
                                        callback(null);
                                    }
                                })
                               },
                               'order': $order,
                               'colReorder': true,
                               'columnDefs' :$defs,
                               rowReorder: {
                                 selector: 'td:nth-child(2)'
                               },
                               responsive: true,
                              'language': $languages,
                              dom: 'Bfrtip',
                              select: true,
                              lengthMenu: [
                                   [ 5, 10, 25, 50, -1 ],
                                   $lengthMenulangs
                               ],
                              buttons: [
                                 'colvis',
                                 'pageLength',
                                 {
                                  extend: 'collection',
                                  text: 'Export',
                                  buttons: [
                                      'copy',
                                      'excel',
                                      'csv',
                                      'pdf',
                                      'print',
                                  ]
                              }
                          ]
                       }
                       );

                       </script>";
                    } else {
                        $widgetdisplay = "";
                    }
                    $tooltip_html = '';
                    if ($widget->getTitleVisibility() && $comment != "") {
                        $tooltip_html = \Html::showToolTip($comment, [
                            'awesome-class' => 'fa-info-circle',
                            'display' => false,
                        ]);
                    }

                    // Any markup-bearing header or cell goes through the sanitizer the plugin
                    // already uses for custom content: it preserves safe formatting
                    // (class/style/links) while stripping <script> and event handlers, closing
                    // the stored-XSS path opened by raw values such as a ticket title.
                    // Plain values are emitted untouched to avoid <p> wrapping.
                    $sanitize = static function ($value) {
                        $value = (string) $value;
                        return str_contains($value, '<')
                            ? RichText::getSafeHtml($value)
                            : $value;
                    };

                    $table = null;
                    $html_content = '';
                    if ($type == "table") {
                        $data = $datas['aaData'];
                        $nb = 0;
                        if (($nb_data = reset($data)) == !false) {
                            $nb = count($nb_data);
                        }

                        $columns = [];
                        foreach ($datas['aoColumns'] as $th) {
                            $columns[] = $sanitize($th['sTitle']);
                        }

                        $rows = [];
                        foreach ($data as $v) {
                            $row = [];
                            for ($i = 0; $i < $nb; $i++) {
                                $row[] = $sanitize($v[$i]);
                            }
                            $rows[] = $row;
                        }

                        $table = [
                            'id' => $widgetindex . $rand,
                            'columns' => $columns,
                            'rows' => $rows,
                            'content_html' => $widget->getWidgetHtmlContent(),
                        ];
                    } elseif ($type == "html") {
                        $html_content = $datas;
                    }

                    $scripts_html = '';
                    foreach ($scripts as $script) {
                        $scripts_html .= \Html::scriptBlock($script);
                    }

                    $debug_html = '';
                    if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
                        $debug_html = "Load widget " . $widgetindex . " : " . $loadwidget . "<br>";
                    }

                    return TemplateRenderer::getInstance()->render('@mydashboard/widget_frame.html.twig', [
                        'prelude_html' => $widgetdisplay,
                        'widget_id' => $widgetindex,
                        'feature_class' => $class,
                        'show_title' => $widget->getTitleVisibility(),
                        'title_html' => $title,
                        'tooltip_html' => $tooltip_html,
                        'header_html' => $widget->getWidgetHeader(),
                        'table' => $table,
                        'html_content' => $html_content,
                        'scripts_html' => $scripts_html,
                        'debug_html' => $debug_html,
                    ]);
                } else {
                    $widgetdisplay = $widgetindex . " : " . __('No data available', 'mydashboard');
                    return $widgetdisplay;
                }
            }
        }
    }

    /**
     * @param $class
     *
     * @return string
     */
    /**
     * Render one of the alert / maintenance / information widgets.
     *
     * The three used to carry near-identical copies of this frame; only the grid id, the
     * bootstrap alert flavour, the Config title field, the list builder and the empty
     * wording ever differed.
     *
     * @param string $feature_class      css class of the widget body
     * @param bool   $hidewidget         wrap in a card and use smaller headings
     * @param array  $itilcategories_id  categories the widget is restricted to
     * @param string $style              inline style of the row
     * @param string $gs_id              grid id (gs4, gs5, gs6)
     * @param string $alert_class        bootstrap alert flavour
     * @param string $title_field        Config field holding the widget title
     * @param string $list_html          rendered ticker, empty when there is nothing
     * @param string $empty_label        message shown when the list is empty
     *
     * @return string
     */
    private static function getAlertWidgetHtml(
        $feature_class,
        $hidewidget,
        $itilcategories_id,
        $style,
        $gs_id,
        $alert_class,
        $title_field,
        $list_html,
        $empty_label
    ) {
        $config = new Config();
        $config->getFromDB(1);

        return TemplateRenderer::getInstance()->render('@mydashboard/widget_alert_block.html.twig', [
            'hidewidget' => (bool) $hidewidget,
            'heading' => $hidewidget ? 'h5' : 'h3',
            'gs_id' => $gs_id,
            'addclass' => count($itilcategories_id) > 0 ? 'details' : '',
            'style' => $style,
            'feature_class' => $feature_class,
            'alert_class' => $alert_class,
            'title_html' => Config::displayField($config, $title_field),
            'list_html' => $list_html,
            'empty_label' => $empty_label,
        ]);
    }

    public static function getWidgetMydashboardAlert($class, $hidewidget = false, $itilcategories_id = [], $style = "")
    {
        $nb = Alert::countForAlerts(0, 0, $itilcategories_id);
        if ($hidewidget == true && $nb < 1) {
            return false;
        }

        $list_html = '';
        if ($nb > 0) {
            $alerts = new Alert();
            $list_html = $alerts->getAlertList(0, $itilcategories_id);
        }

        return self::getAlertWidgetHtml(
            $class,
            $hidewidget,
            $itilcategories_id,
            $style,
            'gs4',
            'alert-danger',
            'title_alerts_widget',
            $list_html,
            __("No problem detected", "mydashboard"),
        );
    }

    /**
     * @param $class
     *
     * @return string
     */
    public static function getWidgetMydashboardMaintenance($class, $hidewidget = false, $itilcategories_id = [], $style = "")
    {
        $nb = Alert::countForAlerts(0, 1, $itilcategories_id);
        if ($hidewidget == true && $nb < 1) {
            return false;
        }

        $list_html = '';
        if ($nb > 0) {
            $alerts = new Alert();
            $list_html = $alerts->getMaintenanceList($itilcategories_id);
        }

        return self::getAlertWidgetHtml(
            $class,
            $hidewidget,
            $itilcategories_id,
            $style,
            'gs5',
            'alert-warning',
            'title_maintenances_widget',
            $list_html,
            __("No scheduled maintenance", "mydashboard"),
        );
    }

    /**
     * @param $class
     *
     * @return string
     * @throws \GlpitestSQLError
     */
    public static function getWidgetMydashboardInformation($class, $hidewidget = false, $itilcategories_id = [], $style = "")
    {
        $nb = Alert::countForAlerts(0, 2, $itilcategories_id);
        if ($hidewidget == true && $nb < 1) {
            return false;
        }

        $list_html = '';
        if ($nb > 0) {
            $alerts = new Alert();
            $list_html = $alerts->getInformationList($itilcategories_id);
        }

        return self::getAlertWidgetHtml(
            $class,
            $hidewidget,
            $itilcategories_id,
            $style,
            'gs6',
            'alert-info',
            'title_informations_widget',
            $list_html,
            __("No informations founded", "mydashboard"),
        );
    }
    /**
     * @param $class
     *
     * @return string
     */
    public static function getWidgetMydashboardEquipments($class, $fromsc)
    {
        $item_class = '';
        if ($fromsc == true) {
            $config = new ServiceCatalogConfig();
            if ($config->getLayout() == ServiceCatalogConfig::THUMBNAIL) {
                $item_class = "visitedchildbg widgetrow";
            }
        }

        $can_link = isset($_SESSION['glpiactiveprofile']['interface'])
            && Session::getCurrentInterface() == 'central';

        $groups = [];
        foreach (self::getAllUsedItemsForUser() as $itemtype => $used_items) {
            $item = getItemForItemtype($itemtype);
            $items = [];
            foreach ($used_items as $item_datas) {
                $items[] = [
                    'url' => ($can_link && $item->canView())
                        ? $item::getFormURL() . "?id=" . $item_datas['id']
                        : null,
                    'icon' => $item->getIcon(),
                    'name' => $item_datas['name'],
                    'typename' => $item->getTypeName(),
                ];
            }
            $groups[] = ['class' => $item_class, 'items' => $items];
        }

        return TemplateRenderer::getInstance()->render('@mydashboard/widget_equipments.html.twig', [
            'fromsc' => $fromsc == true,
            'feature_class' => $class,
            'groups' => $groups,
        ]);
    }

    /**
     * Get all used items for user
     *
     * @param ID of user
     *
     * @return array
     */
    public static function getAllUsedItemsForUser()
    {
        $items = [];

        $types = ['Computer',
            'Monitor',
            'Peripheral',
            'Phone',
            'Printer',
            'SoftwareLicense',
            Badge::class];

        $users_id = Session::getLoginUserID();
        foreach ($types as $itemtype) {
            if (!($item = getItemForItemtype($itemtype))) {
                continue;
            }
            $condition = ['users_id' => $users_id];
            if ($item->maybeTemplate()) {
                $condition['is_template'] = 0;
            }
            if ($item->maybeDeleted()) {
                $condition['is_deleted'] = 0;
            }
            $dbu       = new DbUtils();
            $condition += $dbu->getEntitiesRestrictCriteria(getTableForItemType($itemtype), '', '', true);

            $objects = $item->find($condition);

            $nb = count($objects);
            if ($nb > 0) {
                foreach ($objects as $object) {
                    $items[$itemtype][] = $object;
                }
            }
        }
        return $items;
    }

    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        $forbidden[] = 'purge';
        return $forbidden;
    }

    public static function install(Migration $migration)
    {
        global $DB;

        $default_charset   = DBConnection::getDefaultCharset();
        $default_collation = DBConnection::getDefaultCollation();
        $default_key_sign  = DBConnection::getDefaultPrimaryKeySignOption();
        $table  = self::getTable();

        if (!$DB->tableExists($table)) {
            $query = "CREATE TABLE `$table` (
                        `id` int {$default_key_sign} NOT NULL auto_increment,
                        `name` varchar(255) NOT NULL,
                        `class` varchar(255) NOT NULL,
                        PRIMARY KEY (`id`),
                        UNIQUE (`name`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);

        }

        //widgetname Migration
        $classes = ['GlpiPluginActivityDashboard' => 'GlpiPlugin\\\Activity\\\Dashboard',
            'GlpiPluginManageentitiesDashboard' => 'GlpiPlugin\\\Manageentities\\\Dashboard',
            'GlpiPluginEventsmanagerDashboard' => 'GlpiPlugin\\\Eventsmanager\\\Dashboard',
            'GlpiPluginOcsinventoryngDashboard' => 'GlpiPlugin\\\Ocsinventoryng\\\Dashboard',
            'GlpiPluginResourcesDashboard' => 'GlpiPlugin\\\Resources\\\Dashboard',
            'GlpiPluginSatisfactionDashboard' => 'GlpiPlugin\\\Satisfaction\\\Dashboard',
            'GlpiPluginServicecatalogIndicator' => 'GlpiPlugin\\\Servicecatalog\\\Indicator',
            'GlpiPluginTasklistsDashboard' => 'GlpiPlugin\\\Tasklists\\\Dashboard',
            'GlpiPluginVipDashboard' => 'GlpiPlugin\\\Vip\\\Dashboard'];

        foreach ($classes as $old => $new) {
            $iterator = $DB->request([
                'SELECT' => [
                    'id',
                    'name',
                ],
                'FROM' => 'glpi_plugin_mydashboard_widgets',
                'WHERE' => [
                    'name'   => ['LIKE', $old . '%'],
                ],
            ]);

            if (count($iterator) > 0) {
                foreach ($iterator as $data) {
                    $DB->update(
                        $table,
                        [
                            'name' => new QueryExpression(
                                'REPLACE(' . $DB->quoteName('name') . ', "' . $old . '", "' . $new . '")',
                            ),
                        ],
                        [
                            'id' => $data['id'],
                        ],
                    );
                }
            }
        }

        $DB->update(
            $table,
            [
                'name' => new QueryExpression(
                    'REPLACE(' . $DB->quoteName('name') . ', "PluginMydashboardReports", "GlpiPlugin\\\Mydashboard\\\Reports\\\Reports")',
                ),
            ],
            [
                1 => 1,
            ],
        );

        $DB->update(
            $table,
            [
                'name' => new QueryExpression(
                    'REPLACE(' . $DB->quoteName('name') . ', "PluginMydashboardAlert", "GlpiPlugin\\\Mydashboard\\\Reports\\\lert")',
                ),
            ],
            [
                1 => 1,
            ],
        );

        if (!$DB->fieldExists($table, "class")) {
            $migration->addField($table, "class", "varchar(255) NOT NULL");
            $migration->migrationOneTable($table);

            $widgetlist = Widgetlist::getList(false);
            foreach ($widgetlist as $widgetclasses) {
                foreach ($widgetclasses as $widgetclass => $widgets) {
                    foreach ($widgets as $widgetview => $widgetlist) {
                        if (is_array($widgetlist)) {
                            foreach ($widgetlist as $widgetId => $widgetTitle) {
                                if (is_numeric($widgetId)) {
                                    $widgetId = $widgetTitle;
                                }
                                $widget_origin = new Widget();
                                $widget = new Widget();
                                if ($widget_origin->getFromDBByCrit(['name' => $widgetId])) {
                                    $widget->update(['class' => $widgetclass, 'id' => $widget_origin->fields['id']]);
                                }
                            }
                        } else {
                            if (is_numeric($widgetview)) {
                                $widgetview = $widgetlist;
                            }
                            $widget_origin = new Widget();
                            $widget = new Widget();
                            if ($widget_origin->getFromDBByCrit(['name' => $widgetview])) {
                                $widget->update(['class' => $widgetclass, 'id' => $widget_origin->fields['id']]);
                            }
                        }
                    }
                }
            }
        }
    }

    public static function uninstall()
    {
        global $DB;

        $DB->dropTable(self::getTable(), true);

    }
}
