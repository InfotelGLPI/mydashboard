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

use CommonDBChild;
use CommonDBTM;
use CommonGLPI;
use DBConnection;
use DbUtils;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QueryExpression;
use GlpiPlugin\Mydashboard\Config;
use Migration;
use Session;

/**
 * ConfigTranslation Class
 *
 **/
class ConfigTranslation extends CommonDBChild
{
    public static string $itemtype  = 'itemtype';
    public static string $items_id  = 'items_id';
    public bool $dohistory = true;

    public static string $rightname = 'plugin_mydashboard_config';

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
     * Itemtypes this class may be attached to.
     *
     * The polymorphic parent of a translation comes from the request, so both write paths of
     * the plugin — front/configtranslation.form.php and ajax/updateTranslationFields.php —
     * confront the posted itemtype with this single definition of the allowed domain.
     *
     * @return string[]
     */
    public static function getAllowedItemtypes(): array
    {
        return [Config::class, self::class];
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

        $iterator = $DB->request([
            'FROM'   => getTableForItemType(__CLASS__),
            'WHERE'  => [
                'itemtype'  => $item->getType(),
                'items_id'  => $item->getID(),
                'field'     => ['<>', 'completename'],
            ],
            'ORDER'  => ['language ASC'],
        ]);

        $entries = [];
        foreach ($iterator as $data) {
            $searchOption = $item->getSearchOptionByField('field', $data['field']);
            $entries[] = [
                'itemtype' => __CLASS__,
                'id' => $data['id'],
                // Clicking a row opens its edition form (public/scripts/config-translation.js)
                'row_class' => $canedit ? 'cursor-pointer' : '',
                'language' => Dropdown::getLanguageName($data['language']),
                'field' => $searchOption['name'] ?? $data['field'],
                'value' => $data['value'],
            ];
        }

        TemplateRenderer::getInstance()->display('@mydashboard/configtranslation_list.html.twig', [
            'canedit' => $canedit,
            'rand' => $rand,
            'type' => __CLASS__,
            'view_url' => $CFG_GLPI['root_doc'] . '/ajax/viewsubitem.php',
            'parenttype' => get_class($item),
            'parent_fk' => $item->getForeignKeyField(),
            'parent_id' => $item->getID(),
            'entries' => $entries,
            'container' => 'mass' . __CLASS__ . $rand,
            'script_url' => PLUGIN_MYDASHBOARD_WEBDIR . '/scripts/config-translation.js',
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
        if (!isset($options['parent']) || !($options['parent'] instanceof CommonDBTM)) {
            return false;
        }
        $item = $options['parent'];

        if ($ID > 0) {
            $this->check($ID, UPDATE);
        } else {
            $options['itemtype'] = get_class($item);
            $options['items_id'] = $item->getID();

            // Create item
            $this->check(-1, CREATE, $options);
        }

        $field_label = null;
        if ($ID > 0) {
            $searchOption = $item->getSearchOptionByField('field', $this->fields['field']);
            $field_label = $searchOption['name'] ?? $this->fields['field'];
        }

        TemplateRenderer::getInstance()->display('@mydashboard/configtranslation_form.html.twig', [
            'item' => $this,
            'params' => $options,
            'parent_item' => $item,
            'no_header' => true,
            'language_label' => $ID > 0 ? Dropdown::getLanguageName($this->fields['language']) : null,
            'field_label' => $field_label,
            'languages' => Dropdown::getLanguages(),
            'field_choices' => self::getTranslatableFields($item),
            'used_fields' => self::getUsedFields($item, $_SESSION['glpilanguage']),
            'fields_url' => PLUGIN_MYDASHBOARD_WEBDIR . '/ajax/updateTranslationFields.php',
        ]);

        return true;
    }

    /**
     * Fields of an item that can be translated: its name, and the text or string fields
     *
     * @param CommonDBTM $item
     *
     * @return array field => label
     */
    private static function getTranslatableFields(CommonDBTM $item): array
    {
        $dbu = new DbUtils();
        $options = [];
        foreach ($item->rawSearchOptions() as $field) {
            if (isset($field['field'])
                && ($field['field'] == 'name')
                && ($field['table'] == $dbu->getTableForItemType(get_class($item)))
                || (isset($field['datatype'])
                    && in_array($field['datatype'], ['text', 'string']))) {
                $options[$field['field']] = $field['name'];
            }
        }
        return $options;
    }

    /**
     * Fields of an item already translated in a language
     *
     * @param CommonDBTM $item
     * @param string     $language
     *
     * @return array field => field
     */
    private static function getUsedFields(CommonDBTM $item, $language): array
    {
        global $DB;

        $used = [];
        $iterator = $DB->request([
            'SELECT' => 'field',
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'itemtype'  => $item->getType(),
                'items_id'  => $item->getID(),
                'language'  => $language,
            ],
        ]);
        foreach ($iterator as $data) {
            $used[$data['field']] = $data['field'];
        }
        return $used;
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
        $options = self::getTranslatableFields($item);
        $used = empty($options) ? [] : self::getUsedFields($item, $language);

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
                new QueryExpression('true'),
            ],
        );
    }

    public static function uninstall()
    {
        global $DB;

        $DB->dropTable(self::getTable(), true);

    }
}
