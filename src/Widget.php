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
use Glpi\Toolbox\URL;
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

        // Both fields are informative (filled by the widget declaration): the form keeps no
        // Save nor Delete button, as before.
        TemplateRenderer::getInstance()->display('@mydashboard/widget_form.html.twig', [
            'item' => $this,
            'params' => $options + ['canedit' => false, 'candel' => false],
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

    /**
     * The widget list of the active profile, memoized in the session.
     *
     * The list depends on the profile — getList() prunes it with ProfileAuthorizedWidget —
     * and on the interface, but it used to be cached under a single flat session key that
     * four call sites read and repopulated only when it was absent. Keying it removes the
     * whole class of leak rather than relying on every invalidation point being remembered.
     *
     * @return array
     */
    public static function getCachedWidgetList(): array
    {
        $profiles_id = (int) ($_SESSION['glpiactiveprofile']['id'] ?? 0);
        $interface   = Session::getCurrentInterface() ?: 'central';
        $key         = $profiles_id . '_' . $interface;

        if (!isset($_SESSION['glpi_plugin_mydashboard_widget_list'][$key])) {
            $_SESSION['glpi_plugin_mydashboard_widget_list'][$key] = self::getCompleteWidgetList();
        }

        return $_SESSION['glpi_plugin_mydashboard_widget_list'][$key];
    }

    public static function getCompleteWidgetList($preload = false, $withslashes = false)
    {

        //Load widgets
        // Widgetlist::getList() filters on the interface a widget class declares in
        // $interfaces; the constant "central" made that filter meaningless for a session in
        // the simplified interface, which this plugin does feed through
        // Hooks::HELPDESK_MENU_ENTRY.
        $widgetlist = Widgetlist::getList(true, -1, Session::getCurrentInterface() ?: 'central', $preload);
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
     * Parameter names a widget may receive, mapped to the shape they are coerced to.
     *
     * The list is the union of GlpiPlugin\Mydashboard\Criteria::$criterias_list -- the
     * criteria bar is the only thing that posts these -- of the date-range fields the
     * FilterDate and DisplayData criteria add, and of the few flags the widgets carry
     * alongside. Widgets shipped by other plugins extend it through getAllowedParams().
     *
     * Shapes:
     *  - id       : integer, or a list of integers (multi-valued dropdown)
     *  - bool     : 0 or 1
     *  - datetime : canonical 'Y-m-d H:i:s', or null when unparseable
     *  - word     : [A-Za-z0-9_-] only, 64 characters at most
     *  - wordlist : a list of words
     */
    private const ALLOWED_PARAMS = [
        // Criteria::$criterias_list
        'entities_id'              => 'id',
        'is_recursive_entities'    => 'bool',
        'type'                     => 'id',
        'locations_id'             => 'id',
        'multiple_locations_id'    => 'id',
        'is_recursive_locations'   => 'bool',
        'status'                   => 'id',
        'priority'                 => 'id',
        'technicians_groups_id'    => 'id',
        'is_recursive_technicians' => 'bool',
        'requesters_groups_id'     => 'id',
        'is_recursive_requesters'  => 'bool',
        'technicians_id'           => 'id',
        'multiple_technicians_id'  => 'id',
        'itilcategories_id'        => 'id',
        'itilcategorielvl1'        => 'id',
        'computertypes_id'         => 'id',
        'users_id'                 => 'id',
        'year'                     => 'id',
        'month'                    => 'id',
        'week'                     => 'id',
        'limit'                    => 'id',
        'multiple_time'            => 'bool',
        'multiple_year_time'       => 'bool',
        'display_data'             => 'word',
        'filter_date'              => 'word',
        // Date range of the FilterDate and DisplayData criteria
        'begin'       => 'datetime',
        'end'         => 'datetime',
        'start_year'  => 'id',
        'start_month' => 'id',
        'end_year'    => 'id',
        'end_month'   => 'id',
        'month_year'  => 'word',
        // Flags the widgets carry next to their criteria
        'criterias'    => 'wordlist',
        'is_widget'    => 'bool',
        'is_usedbycra' => 'bool',
        'export'       => 'bool',
        'tag'          => 'word',
    ];

    /**
     * Close the client-controlled widget parameters over an explicit allow-list.
     *
     * $opt reaches the report classes untouched and several of them interpolate it into
     * raw SQL -- Criterias\Year and Criterias\Month build a QueryExpression out of the
     * year and month, Reports_Bar and Reports_Line compute boundaries from the period
     * fields -- so the set of keys, and the type of each of them, is decided here rather
     * than left to whatever the caller posted. Every key outside the allow-list is
     * dropped: the output of this method is closed, and a widget needing more must say
     * so through getAllowedParams() instead of relying on the parameters flowing through.
     *
     * @param array       $opt       parameters as posted
     * @param string|null $classname widget class about to receive them, for its own extras
     *
     * @return array
     */
    public static function sanitizeWidgetParams($opt, $classname = null)
    {
        if (!is_array($opt)) {
            return [];
        }

        $allowed = self::ALLOWED_PARAMS;

        // Extension point for widgets declared by other plugins: getAllowedParams()
        // returns the same ['name' => shape] map, and may only add to the list.
        if (
            is_string($classname)
            && class_exists($classname)
            && method_exists($classname, 'getAllowedParams')
        ) {
            $extra = $classname::getAllowedParams();
            if (is_array($extra)) {
                foreach ($extra as $name => $shape) {
                    if (is_string($name) && is_string($shape)) {
                        $allowed[$name] = $shape;
                    }
                }
            }
        }

        $sanitized = [];
        foreach ($allowed as $name => $shape) {
            if (!array_key_exists($name, $opt)) {
                continue;
            }
            $sanitized[$name] = self::castWidgetParam($opt[$name], $shape);
        }

        return $sanitized;
    }

    /**
     * Coerce a single widget parameter to one of the shapes of ALLOWED_PARAMS.
     *
     * @param mixed  $value
     * @param string $shape
     *
     * @return mixed
     */
    private static function castWidgetParam($value, string $shape)
    {
        if (is_array($value)) {
            if ($shape === 'wordlist') {
                $shape = 'word';
            }
            $list = [];
            foreach ($value as $item) {
                if (is_scalar($item)) {
                    $list[] = self::castWidgetParam($item, $shape);
                }
            }
            return $list;
        }

        if (!is_scalar($value)) {
            return null;
        }

        switch ($shape) {
            case 'bool':
                return (int) (bool) $value;

            case 'datetime':
                $value = (string) $value;
                if ($value === '') {
                    return null;
                }
                $timestamp = strtotime($value);
                return ($timestamp !== false) ? date('Y-m-d H:i:s', $timestamp) : null;

            case 'word':
            case 'wordlist':
                return substr(preg_replace('/[^A-Za-z0-9_-]/', '', (string) $value), 0, 64);

            case 'id':
            default:
                // An empty selection must stay empty rather than become 0, which several
                // criteria read as a real identifier.
                return ($value === '') ? '' : (int) $value;
        }
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
        // The widget class is handed over so it can declare parameters of its own.
        $opt = self::sanitizeWidgetParams($opt, $classname);

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
                    //               $json  = Helper::safeJson($json);
                    $datas = json_decode($jsondata, true);

                    // Rendered by Html::showToolTip() from widget_frame.html.twig
                    $tooltip = '';
                    if ($widget->getTitleVisibility() && $comment != "") {
                        $tooltip = $comment;
                    }

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
                            $columns[] = self::getDisplayFragment($th['sTitle']);
                        }

                        $rows = [];
                        foreach ($data as $v) {
                            $row = [];
                            for ($i = 0; $i < $nb; $i++) {
                                $row[] = self::getDisplayCell($v[$i]);
                            }
                            $rows[] = $row;
                        }

                        $table = [
                            'id' => $widgetindex . mt_rand(),
                            'columns' => $columns,
                            'rows' => $rows,
                            'config' => self::getDatatableConfig($widget, $widgetindex, $menu),
                        ];
                        $html_content = $widget->getWidgetHtmlContent();
                    } elseif ($type == "html") {
                        $html_content = $datas;
                    }

                    $scripts_html = '';
                    foreach ($scripts as $script) {
                        $scripts_html .= \Html::scriptBlock($script);
                    }

                    $load_time = null;
                    if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
                        $load_time = $loadwidget;
                    }

                    return TemplateRenderer::getInstance()->render('@mydashboard/widget_frame.html.twig', [
                        'widget_id' => $widgetindex,
                        'feature_class' => $class,
                        'show_title' => $widget->getTitleVisibility(),
                        'title' => self::getDisplayFragment($title),
                        'title_link' => self::getTitleLink($widget),
                        'tooltip' => $tooltip,
                        'header_html' => $widget->getWidgetHeader(),
                        'table' => $table,
                        'html_content' => $html_content,
                        'scripts_html' => $scripts_html,
                        'load_time' => $load_time,
                    ]);
                } else {
                    $widgetdisplay = $widgetindex . " : " . __('No data available', 'mydashboard');
                    return $widgetdisplay;
                }
            }
        }
    }

    /**
     * Type a title or a table cell for widget_frame.html.twig.
     *
     * Plain values are handed over as text and escaped by Twig. Values carrying markup
     * (report links, status badges) are sanitized: formatting and links are kept,
     * scripts and event handlers are stripped. The template only prints the sanitized
     * branch raw, so a value can no longer reach the page unescaped by mistake.
     *
     * @param mixed $value
     *
     * @return array{html: bool, value: string}
     */
    private static function getDisplayFragment($value): array
    {
        $value = (string) $value;
        if (str_contains($value, '<')) {
            return ['html' => true, 'value' => RichText::getSafeHtml($value)];
        }
        // Several producers pre-escape their text (Config::displayField(), custom widget
        // names), decode it once so Twig does not escape it a second time.
        return ['html' => false, 'value' => html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8')];
    }

    /**
     * Type a table cell for widget_frame.html.twig.
     *
     * Reports describe their cells as arrays carrying a 'kind' (text, link, badge, status,
     * lines, action) and only scalar values: the template composes the markup and escapes
     * every value. Strings are the legacy format and go through getDisplayFragment().
     *
     * @param mixed $value
     *
     * @return array
     */
    private static function getDisplayCell($value): array
    {
        if (!is_array($value) || !isset($value['kind'])) {
            return self::getDisplayFragment($value);
        }

        $text = static fn($v): string => is_scalar($v) ? (string) $v : '';

        switch ($value['kind']) {
            case 'link':
                return [
                    'kind' => 'link',
                    // Report links point to GLPI pages, RSS items to the feed site: both are
                    // restricted to http(s) or root relative URLs.
                    'url' => URL::sanitizeURL($text($value['url'] ?? '')),
                    'label' => $text($value['label'] ?? ''),
                    'bold' => !empty($value['bold']),
                    'external' => !empty($value['external']),
                    'tooltip' => $text($value['tooltip'] ?? ''),
                    'prefix' => $text($value['prefix'] ?? ''),
                    'icon' => $text($value['icon'] ?? ''),
                    'suffix' => $text($value['suffix'] ?? ''),
                ];

            case 'badge':
                return [
                    'kind' => 'badge',
                    'label' => $text($value['label'] ?? ''),
                    'color' => self::getCellColor($value['color'] ?? null),
                    'text_color' => self::getCellColor($value['text_color'] ?? null),
                    'url' => URL::sanitizeURL($text($value['url'] ?? '')),
                ];

            case 'status':
                return [
                    'kind' => 'status',
                    'label' => $text($value['label'] ?? ''),
                    'icon' => $text($value['icon'] ?? ''),
                    'suffix' => $text($value['suffix'] ?? ''),
                ];

            case 'lines':
                $items = [];
                foreach ((array) ($value['items'] ?? []) as $item) {
                    $items[] = self::getDisplayCell($item);
                }
                return ['kind' => 'lines', 'items' => $items];

            case 'action':
                $params = [];
                foreach ((array) ($value['params'] ?? []) as $key => $param) {
                    if (is_scalar($param)) {
                        $params[$key] = $param;
                    } elseif (is_array($param)) {
                        $params[$key] = array_values(array_filter($param, 'is_scalar'));
                    }
                }
                return [
                    'kind' => 'action',
                    'label' => $text($value['label'] ?? ''),
                    'url' => URL::sanitizeURL($text($value['url'] ?? '')),
                    'params' => $params,
                ];

            default:
                return [
                    'kind' => 'text',
                    'value' => $text($value['value'] ?? ''),
                    'bold' => !empty($value['bold']),
                ];
        }
    }

    /**
     * A css colour of a cell, only accepted as an hexadecimal notation.
     *
     * @param mixed $color
     *
     * @return string|null
     */
    private static function getCellColor($color): ?string
    {
        if (is_string($color) && preg_match('/^#[0-9a-f]{3,8}$/i', $color) === 1) {
            return $color;
        }
        return null;
    }

    /**
     * Link decorations of the widget title, see Module::setWidgetTitleLink().
     *
     * @param Module $widget
     *
     * @return array|null
     */
    private static function getTitleLink($widget): ?array
    {
        $link = $widget->getWidgetTitleLink();
        if ($link === null) {
            return null;
        }
        $count = $link['count'];
        $total = $link['total'];
        $count_label = null;
        if ($count !== null) {
            $count_label = ($total !== null && $count > 0 && $count < $total)
                ? sprintf(__('%1$d on %2$d'), $count, $total)
                : (string) ($total ?? $count);
        }

        return [
            'url' => URL::sanitizeURL((string) $link['url']),
            'count' => $count_label,
            'icon' => $link['icon'],
            'add_url' => $link['add_url'] !== null ? URL::sanitizeURL((string) $link['add_url']) : null,
        ];
    }

    /**
     * DataTables configuration of a table widget, read by public/scripts/widget-datatable.js.
     *
     * @param Module $widget
     * @param string $widgetindex
     * @param Menu   $menu
     *
     * @return array
     */
    private static function getDatatableConfig($widget, $widgetindex, Menu $menu): array
    {
        $opt = $widget->getOptions();

        return [
            'gsId' => $widgetindex,
            'saveUrl' => PLUGIN_MYDASHBOARD_WEBDIR . '/ajax/state_save.php',
            'loadUrl' => PLUGIN_MYDASHBOARD_WEBDIR . '/ajax/state_load.php',
            // bSort is either a [column, direction] pair or false ("keep the SQL order"):
            // a bare false in the DataTables order breaks both the cell lookup (tn/4) and
            // the state save (e.slice is not a function).
            'order' => isset($opt['bSort']) && is_array($opt['bSort']) ? [$opt['bSort']] : [],
            'columnDefs' => $opt['bDef'] ?? [],
            'language' => $menu->getJsLanguages('datatables'),
            'lengthMenuLabels' => [
                __('5 rows', 'mydashboard'),
                __('10 rows', 'mydashboard'),
                __('25 rows', 'mydashboard'),
                __('50 rows', 'mydashboard'),
                __('Show all', 'mydashboard'),
            ],
            'exportLabel' => __('Export'),
        ];
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
            'title' => Config::getTranslatedField($config, $title_field),
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
