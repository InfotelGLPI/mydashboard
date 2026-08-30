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

use Ajax;
use CommonDBChild;
use CommonDBTM;
use CommonGLPI;
use DBConnection;
use DbUtils;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QueryExpression;
use Html;
use GlpiPlugin\Mydashboard\Config;
use Migration;
use Session;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * ConfigTranslation Class
 *
 **/
class ConfigTranslation extends CommonDBChild
{
    public static $itemtype  = 'itemtype';
    public static $items_id  = 'items_id';
    public $dohistory = true;

    public static $rightname = 'plugin_mydashboard_config';

    /**
     * Return the localized name of the current Type
     * Should be overloaded in each new class
     *
     * @param integer $nb Number of items
     *
     * @return string
     **/
    public static function getTypeName($nb = 0)
    {
        return _n('Translation', 'Translations', $nb);
    }

    public static function getIcon()
    {
        return 'ti ti-language';
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
    public function getForbiddenStandardMassiveAction()
    {

        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }

    /**
     * @see CommonGLPI::getTabNameForItem()
     **/
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

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
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
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
    public static function showTranslations($item)
    {
        global $DB, $CFG_GLPI;

        $rand    = mt_rand();
        $canedit = $item->can($item->getID(), UPDATE);
        $container = 'mass' . __CLASS__ . $rand;
        $view_container_id = "viewtranslationconfig" . $item->getID() . $rand;

        $add_function = null;
        $add_script_html = '';
        if ($canedit) {
            $add_function = 'addTranslationconfig' . $item->getID() . $rand;
            $add_script_html = Html::scriptBlock(
                'function ' . $add_function . '() {'
                . Ajax::updateItemJsCode(
                    $view_container_id,
                    $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php",
                    ['type' => __CLASS__,
                        'parenttype' => get_class($item),
                        $item->getForeignKeyField() => $item->getID(),
                        'id' => -1,
                    ],
                    "",
                    false,
                )
                . '};',
            );
        }

        $iterator = $DB->request([
            'FROM'   => getTableForItemType(__CLASS__),
            'WHERE'  => [
                'itemtype'  => $item->getType(),
                'items_id'  => $item->getID(),
                'field'     => ['<>', 'completename'],
            ],
            'ORDER'  => ['language ASC'],
        ]);

        $rows = [];
        $scripts_html = '';
        foreach ($iterator as $data) {
            $edit_function = null;
            $checkbox_html = '';
            if ($canedit) {
                $edit_function = 'viewEditTranslationconfig' . (int) $data['id'] . $rand;
                $checkbox_html = Html::getMassiveActionCheckBox(__CLASS__, $data['id']);
                $scripts_html .= Html::scriptBlock(
                    'function ' . $edit_function . '() {'
                    . Ajax::updateItemJsCode(
                        $view_container_id,
                        $CFG_GLPI["root_doc"] . "/ajax/viewsubitem.php",
                        ['type' => __CLASS__,
                            'parenttype' => get_class($item),
                            $item->getForeignKeyField() => $item->getID(),
                            'id' => $data['id'],
                        ],
                        "",
                        false,
                    )
                    . '};',
                );
            }

            $searchOption = $item->getSearchOptionByField('field', $data['field']);
            $rows[] = [
                'edit_function' => $edit_function,
                'checkbox_html' => $checkbox_html,
                'language' => Dropdown::getLanguageName($data['language']),
                'field' => $searchOption['name'],
                'value' => $data['value'],
            ];
        }

        $ma_open_html = '';
        $ma_top_html = '';
        $ma_bottom_html = '';
        $close_form_html = '';
        $check_all_html = '';
        if ($canedit && count($rows)) {
            // No 'item' key here, as in the legacy code
            $massiveactionparams = ['container' => $container, 'display' => false];
            $ma_open_html = Html::getOpenMassiveActionsForm($container);
            $ma_top_html = Html::showMassiveActions($massiveactionparams);
            // The legacy code called getCheckAllAsCheckbox() without echoing it, so the
            // "check all" box was simply never rendered.
            $check_all_html = Html::getCheckAllAsCheckbox($container);
            // Built after the rows on purpose: showMassiveActions() empties
            // $_SESSION['glpimassiveactionselected'] when it is not the top one, and the
            // row checkboxes read that selection to restore their checked state.
            $massiveactionparams['ontop'] = false;
            $ma_bottom_html = Html::showMassiveActions($massiveactionparams);
            $close_form_html = Html::closeForm(false);
        }

        echo TemplateRenderer::getInstance()->render('@mydashboard/configtranslation_list.html.twig', [
            'canedit' => $canedit,
            'view_container_id' => $view_container_id,
            'add_function' => $add_function,
            'add_script_html' => $add_script_html,
            'rows' => $rows,
            'scripts_html' => $scripts_html,
            'ma_open_html' => $ma_open_html,
            'ma_top_html' => $ma_top_html,
            'ma_bottom_html' => $ma_bottom_html,
            'close_form_html' => $close_form_html,
            'check_all_html' => $check_all_html,
        ]);

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
    public function showForm($ID = -1, $options = [])
    {
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
        // showFormHeader()/showFormButtons() emit the surrounding <form> and <table>
        ob_start();
        $this->showFormHeader($options);
        $form_header_html = ob_get_clean();

        $language_label = null;
        $language_hidden_html = '';
        $language_dropdown_html = '';
        if ($ID > 0) {
            $language_hidden_html = Html::hidden('language', ['value' => $this->fields['language']]);
            $language_label = Dropdown::getLanguageName($this->fields['language']);
        } else {
            // The rand has to be generated here: with 'display' => false the dropdown
            // returns its markup instead of the rand, which the AJAX observer below needs.
            $lang_rand = mt_rand();
            $language_dropdown_html = Dropdown::showLanguages(
                "language",
                ['display_none' => false,
                    'value' => $_SESSION['glpilanguage'],
                    'rand' => $lang_rand,
                    'display' => false,
                ],
            )
            . Ajax::updateItemOnSelectEvent(
                "dropdown_language$lang_rand",
                "span_fields",
                PLUGIN_MYDASHBOARD_WEBDIR . "/ajax/updateTranslationFields.php",
                ['language' => '__VALUE__',
                    'itemtype' => get_class($item),
                    'items_id' => $item->getID(),
                ],
                false,
            );
        }

        $field_label = null;
        $field_hidden_html = '';
        $field_dropdown_html = '';
        if ($ID > 0) {
            $field_hidden_html = Html::hidden('field', ['value' => $this->fields['field']]);
            $searchOption = $item->getSearchOptionByField('field', $this->fields['field']);
            $field_label = $searchOption['name'];
        } else {
            // dropdownFields() writes to the output buffer instead of returning
            ob_start();
            self::dropdownFields($item, $_SESSION['glpilanguage']);
            $field_dropdown_html = ob_get_clean();
        }

        ob_start();
        $this->showFormButtons($options);
        $form_buttons_html = ob_get_clean();

        echo TemplateRenderer::getInstance()->render('@mydashboard/configtranslation_form.html.twig', [
            'form_header_html' => $form_header_html,
            'form_buttons_html' => $form_buttons_html,
            'items_hidden_html' => Html::hidden('items_id', ['value' => $item->getID()])
                . Html::hidden('itemtype', ['value' => get_class($item)]),
            'language_label' => $language_label,
            'language_hidden_html' => $language_hidden_html,
            'language_dropdown_html' => $language_dropdown_html,
            'field_label' => $field_label,
            'field_hidden_html' => $field_hidden_html,
            'field_dropdown_html' => $field_dropdown_html,
            'value_textarea_html' => Html::textarea([
                'name' => 'value',
                'value' => $this->fields["value"],
                'cols' => 80,
                'rows' => 3,
                'enable_richtext' => false,
                'display' => false,
            ]),
        ]);

        return true;
    }

    /**
     * Display a dropdown with fields that can be translated for an itemtype
     *
     * @param $item       a Dropdown item
     * @param $language   language to look for translations (default '')
     * @param $value      field which must be selected by default (default '')
     *
     * @return int|string dropdown's random identifier
     **/
    public static function dropdownFields(CommonDBTM $item, $language = '', $value = '')
    {
        global $DB;
        $options = [];
        foreach ($item->rawSearchOptions() as $id => $field) {
            //Can only translate name, and fields whose datatype is text or string
            $dbu        = new DbUtils();
            if (isset($field['field'])
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
                    'language'  => $language,
                ],
            ]);
            if (count($iterator) > 0) {
                foreach ($iterator as $data) {
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
    public static function canBeTranslated(CommonGLPI $item)
    {

        return ($item instanceof Config);
    }

    /**
     * Return the number of translations for an item
     *
     * @param item
     *
     * @return int number of translations for this item
     **/
    public static function getNumberOfTranslationsForItem($item)
    {
        $dbu = new DbUtils();
        return $dbu->countElementsInTable(
            $dbu->getTableForItemType(__CLASS__),
            ["items_id" => $item->getID()],
        );
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
                        `items_id` int unsigned NOT NULL                   DEFAULT '0',
                        `itemtype` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                        `language` varchar(5) COLLATE utf8mb4_unicode_ci   DEFAULT NULL,
                        `field`    varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                        `value`    text COLLATE utf8mb4_unicode_ci         DEFAULT NULL,
                        PRIMARY KEY (`id`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);

        }

        $DB->update(
            "glpi_plugin_mydashboard_widgets",
            [
                'name' => new QueryExpression(
                    'REPLACE(' . $DB->quoteName('name') . ', "PluginMydashboardConfig", "GlpiPlugin\\\Mydashboard\\\Config")',
                ),
            ],
            [
                1 => 1,
            ],
        );
    }

    public static function uninstall()
    {
        global $DB;

        $DB->dropTable(self::getTable(), true);

    }
}
