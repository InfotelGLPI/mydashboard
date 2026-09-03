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
use DBConnection;
use DbUtils;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use Migration;
use Plugin;
use Profile;
use ProfileRight;

/**
 * Class ProfileAuthorizedWidget
 */
class ProfileAuthorizedWidget extends CommonDBTM
{
    private $authorized;

    /**
     * @param $profiles_id
     *
     * @return array|bool
     */
    public function getAuthorizedListForProfile($profiles_id)
    {
        $profileright = new ProfileRight();
        $profileright->getFromDBByCrit(['name'        => 'plugin_mydashboard',
            'profiles_id' => $profiles_id]);

        //If profile has right CREATE+UPDATE it means it can see every widgets
        if (isset($profileright->fields['rights'])
            && $profileright->fields['rights'] == (CREATE + UPDATE)) {
            return false;
        }

        //If profile has right READ it means it can see only authorized widgets
        if (isset($profileright->fields['rights']) && $profileright->fields['rights'] == READ) {
            $dbu    = new DbUtils();
            $table  = $dbu->getAllDataFromTable($this->getTable(), ["profiles_id" => $profiles_id]);
            $widget = new Widget();

            $ret = [];
            foreach ($table as $key => $line) {
                $widgetId       = $widget->getWidgetNameById($line['widgets_id']);
                $ret[$widgetId] = $line['id'];
            }
            return $ret;
        } else {
            return [];
        }
    }

    /**
     * @param       $ID
     * @param array $options
     */
    public function showForm($ID, $options = [])
    {
        $this->authorized = $this->getAuthorizedListForProfile($ID);
        $pluginlists      = Widgetlist::getList(false, -1, $options['interface']);

        $plugins = [];
        foreach ($pluginlists as $plugin => $widgetclasses) {
            $fct = 'plugin_version_' . strtolower($plugin);
            $widgets = [];
            foreach ($widgetclasses as $widgetclass => $widgets_of_class) {
                $widgets = array_merge($widgets, $this->getWidgetRows($widgets_of_class, ''));
            }
            $plugins[] = [
                'label' => ucfirst(function_exists($fct) ? $this->getLocalName($plugin) : $plugin),
                'css_class' => 'from_' . $plugin,
                'widgets' => $widgets,
            ];
        }

        echo TemplateRenderer::getInstance()->render('@mydashboard/profileauthorizedwidget_form.html.twig', [
            'form_action' => PLUGIN_MYDASHBOARD_WEBDIR . '/front/profileauthorizedwidget.form.php',
            'plugins' => $plugins,
            'submit_html' => \Html::submit(_sx('button', 'Save'), ['name' => 'update',
                'class' => 'btn btn-primary',
            ]),
            'hidden_html' => \Html::hidden("id", ['value' => $ID]),
            'close_form_html' => \Html::closeForm(false),
            // Delegated handler bound on the data attribute, replacing the inline onclick
            // that used to carry the plugin name into an HTML attribute.
            'toggle_script_html' => \Html::scriptBlock(
                '$(document).on("click", "[data-md-toggle-all]", function () {'
                . 'var $rows = $("." + $(this).data("md-toggle-all"));'
                . 'var next = ($rows.find("select").val() === "0") ? "1" : "0";'
                . '$rows.find("select").val(next).trigger("change");'
                . '});',
            ),
        ]);
    }

    /**
     * Flatten the (possibly nested) widget list into renderable rows.
     *
     * @param array  $widgetlist
     * @param string $category parent category path, empty at the top level
     *
     * @return array
     */
    private function getWidgetRows($widgetlist, $category)
    {
        $widgetlistclass = new Widgetlist();
        $viewNames = $widgetlistclass->getViewNames();

        $rows = [];
        foreach ($widgetlist as $widgetId => $widgetTitle) {
            if (is_array($widgetTitle)) {
                if (!isset($widgetTitle['title'])) {
                    // Nested category: recurse, prefixing the category path
                    $newcategory = $category != '' ? $category . ' > ' : '';
                    if (is_numeric($widgetId)) {
                        $widgetId = $viewNames[$widgetId] ?? 0;
                    }
                    $newcategory .= $widgetId;
                    $rows = array_merge($rows, $this->getWidgetRows($widgetTitle, $newcategory));
                    continue;
                }
                $title = $widgetTitle['title'];
            } else {
                $title = $widgetTitle;
            }

            $authorized = isset($this->authorized[$widgetId]);
            $rows[] = [
                'title' => $title,
                'category' => $category,
                'authorized' => $authorized,
                'yesno_html' => Dropdown::showYesNo(
                    $widgetId,
                    $authorized ? 1 : 0,
                    -1,
                    ['display' => false],
                ),
            ];
        }

        return $rows;
    }

    /**
     * @param $post
     */
    public function save($post)
    {
        if (isset($post['id']) && isset($post['update'])) {
            // Validate the posted target at the sink, like front/menu.php does for
            // profiles_id: the value is written straight into
            // glpi_plugin_mydashboard_profileauthorizedwidgets rows, so a
            // non-numeric or unknown id would only create orphan authorizations.
            $profiles_id = (int) $post['id'];
            if ($profiles_id <= 0) {
                return;
            }
            $profile = new Profile();
            if (!$profile->getFromDB($profiles_id)) {
                return;
            }
            unset($post['id']);
            unset($post['update']);
        } else {
            return;
        }
        $this->authorized = $this->getAuthorizedListForProfile($profiles_id);
        $widget           = new Widget();

        //Newly authorized
        foreach ($post as $widgetName => $authorized) {
            if ($authorized == 1) {
                $widgetId = $widget->getWidgetIdByName($widgetName);
                unset($this->fields['id']);
                $this->getFromDBByCrit(['widgets_id' => $widgetId, 'profiles_id' => $profiles_id]);
                if (!isset($this->fields['id'])
                    && $widgetId != null
                    && !empty($widgetId)) {
                    $this->add([
                        'profiles_id' => $profiles_id,
                        'widgets_id'  => $widgetId,
                    ]);
                }
            } else {
                if (isset($this->authorized[$widgetName])) {
                    $this->getFromDB($this->authorized[$widgetName]);
                    $this->deleteFromDB();
                }
            }
        }
    }

    /**
     * Get the localized name for a plugin
     *
     * @param string $plugin_name
     *
     * @return string
     */
    private function getLocalName($plugin_name)
    {
        $infos = Plugin::getInfo($plugin_name);

        return isset($infos['name']) ? $infos['name'] : $plugin_name;
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
                        `profiles_id` int {$default_key_sign} NOT NULL DEFAULT '0' COMMENT 'RELATION to glpi_profiles (id)',
                        `widgets_id`  int {$default_key_sign} NOT NULL DEFAULT '0' COMMENT 'RELATION to glpi_mydashboard_widgets (id)',
                        PRIMARY KEY (`id`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);

        }

        $migration->changeField($table, "widgets_id", "widgets_id", "int {$default_key_sign} NOT NULL DEFAULT '0' COMMENT 'RELATION to glpi_mydashboard_widgets (id)'");
        $migration->migrationOneTable($table);

        $DB->update(
            $table,
            ['widgets_id' => 0],
            ['widgets_id' => -1],
        );
    }

    public static function uninstall()
    {
        global $DB;

        $DB->dropTable(self::getTable(), true);

    }
}
