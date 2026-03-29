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

namespace GlpiPlugin\Mydashboard;

use CommonDBTM;
use CommonGLPI;
use DBConnection;
use Dropdown;
use Html;
use ITILCategory;
use Migration;
use Session;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * This class handles the general configuration of mydashboard
 *
 */
class Config extends CommonDBTM
{
    /**
     * @param int $nb
     *
     * @return translated
     */
    public static $rightname         = "plugin_mydashboard_config";
    public $can_be_translated = true;

    public static function getTypeName($nb = 0)
    {
        return __('Plugin setup', 'mydashboard');
    }

    public function getName($options = [])
    {
        return __('My Dashboard', 'mydashboard');
    }


    /**
     * @return string
     */
    public static function getIcon()
    {
        return Menu::getIcon();
    }

    /**
     * Config constructor.
     */
    public function __construct()
    {
        global $DB;

        if ($DB->tableExists($this->getTable())) {
            $this->getFromDB(1);
        }
    }

    /**
     * Have I the global right to "view" the Object
     *
     * Default is true and check entity if the objet is entity assign
     *
     * May be overloaded if needed
     *
     * @return booleen
     **/
    public static function canView(): bool
    {
        return (Session::haveRight(self::$rightname, UPDATE));
    }

    /**
     * Have I the global right to "create" the Object
     * May be overloaded if needed (ex KnowbaseItem)
     *
     * @return booleen
     **/
    public static function canCreate(): bool
    {
        return (Session::haveRight(self::$rightname, CREATE));
    }


    /**
     * @param string $interface
     *
     * @return array
     */
    public function getRights($interface = 'central')
    {
        $values = parent::getRights();

        unset($values[READ], $values[DELETE]);
        return $values;
    }


    /**
     * @param CommonGLPI $item
     * @param int        $tabnum
     * @param int        $withtemplate
     *
     * @return bool
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item->getType() == __CLASS__) {
            switch ($tabnum) {
                case 1:
                    $item->showForm($item->getID());
                    break;
            }
        }
        return true;
    }

    /**
     * @param CommonGLPI $item
     * @param int        $withtemplate
     *
     * @return string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        return self::createTabEntry(self::getTypeName());
    }

    /**
     * @see CommonGLPI::defineTabs()
     */
    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addStandardTab(__CLASS__, $ong, $options);
        $this->addStandardTab(ConfigTranslation::class, $ong, $options);
        $this->addStandardTab(CheckSchema::class, $ong, $options);
        return $ong;
    }

    /**
     * Get a specific field of the config
     *
     * @param string $fieldname
     *
     * @return mixed
     */
    public static function getConfigField($fieldname)
    {
        $config = new Config();
        if (!$config->getFromDB(Session::getLoginUserID())) {
            $config->initConfig();
        }
        $config->getFromDB("1");

        return (isset($config->fields[$fieldname])) ? $config->fields[$fieldname] : 0;
    }

    /**
     * @return mixed
     */
    public static function getDisplayMenu()
    {
        return Config::getConfigField("display_menu");
    }

    /**
     * @return mixed
     */
    public static function getReplaceCentralConf()
    {
        return Config::getConfigField("replace_central");
    }

    /**
     * Get the Search options for the given Type
     *
     * This should be overloaded in Class
     *
     * @return array an *indexed* array of search options
     *
     * @see https://glpi-developer-documentation.rtfd.io/en/master/devapi/search.html
     **/
    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'   => 'common',
            'name' => self::getTypeName(2),
        ];

        $tab[] = [
            'id'         => '2',
            'table'      => $this->getTable(),
            'field'      => 'title_alerts_widget',
            'name'       => __("Title of alerts widget", "mydashboard"),
            'searchtype' => 'equals',
            'datatype'   => 'text',
        ];

        $tab[] = [
            'id'         => '3',
            'table'      => $this->getTable(),
            'field'      => 'title_maintenances_widget',
            'name'       => __("Title of scheduled maintenances widget", "mydashboard"),
            'searchtype' => 'equals',
            'datatype'   => 'text',
        ];

        $tab[] = [
            'id'         => '4',
            'table'      => $this->getTable(),
            'field'      => 'title_informations_widget',
            'name'       => __("Title of informations widget", "mydashboard"),
            'searchtype' => 'equals',
            'datatype'   => 'text',
        ];

        return $tab;
    }

    /**
     * @param       $ID
     * @param array $options
     */
    public function showForm($ID, $options = [])
    {
        $this->getFromDB("1");

        //If user have no access
        //        if(!plugin_dashboard_haveRight('config', READ)){
        //            return false;
        //        }

        //The configuration is not deletable
        $options['candel']  = false;
        $options['colspan'] = 1;

        $this->showFormHeader($options);

        //canCreate means that user can update the configuration
        //        $canCreate = self::canCreate();
        $canCreate = true;
        $rand      = mt_rand();

        //This array is for those who can't update, it's to display the value of a boolean parameter
        $yesno = [__("No"), __("Yes")];

        echo "<tr class='tab_bg_1'><td>" . __("Enable the possibility to display Dashboard in full screen", "mydashboard") . "</td>";
        echo "<td>";
        if ($canCreate) {
            Dropdown::showYesNo("enable_fullscreen", $this->fields['enable_fullscreen']);
        } else {
            echo $yesno[$this->fields['enable_fullscreen']];
        }
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'><td>" . __("Replace central interface", "mydashboard") . "</td>";
        echo "<td>";
        Dropdown::showYesNo("replace_central", $this->fields['replace_central']);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td>" . __('Impact colors for alerts', 'mydashboard') . "</td>";
        echo "<td colspan='3'>";

        echo "<table><tr>";
        echo "<td><label for='dropdown_priority_1$rand'>1</label>&nbsp;";
        Html::showColorField('impact_1', ['value' => $this->fields["impact_1"], 'rand' => $rand]);
        echo "</td>";
        echo "<td><label for='dropdown_priority_2$rand'>2</label>&nbsp;";
        Html::showColorField('impact_2', ['value' => $this->fields["impact_2"], 'rand' => $rand]);
        echo "</td>";
        echo "<td><label for='dropdown_priority_3$rand'>3</label>&nbsp;";
        Html::showColorField('impact_3', ['value' => $this->fields["impact_3"], 'rand' => $rand]);
        echo "</td>";
        echo "<td><label for='dropdown_priority_4$rand'>4</label>&nbsp;";
        Html::showColorField('impact_4', ['value' => $this->fields["impact_4"], 'rand' => $rand]);
        echo "</td>";
        echo "<td><label for='dropdown_priority_5$rand'>5</label>&nbsp;";
        Html::showColorField('impact_5', ['value' => $this->fields["impact_5"], 'rand' => $rand]);
        echo "</td>";
        echo "</tr></table>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Level of categories to show', 'mydashboard') . "</td>";
        echo "<td>";
        $itilCat        = new ITILCategory();
        $itilCategories = $itilCat->find();
        $levelsCat      = [];
        foreach ($itilCategories as $categorie) {
            $levelsCat[$categorie['level']] = $categorie['level'];
        }
        ksort($levelsCat);
        Dropdown::showFromArray('levelCat', $levelsCat, ['value' => $this->fields["levelCat"]]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'><td>" . __("Title of alerts widget", "mydashboard") . "</td>";
        echo "<td>";
        echo Html::input('title_alerts_widget', ['value' => $this->fields['title_alerts_widget'], 'size' => 70]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'><td>" . __("Title of scheduled maintenances widget", "mydashboard") . "</td>";
        echo "<td>";
        echo Html::input('title_maintenances_widget', ['value' => $this->fields['title_maintenances_widget'], 'size' => 70]);
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'><td>" . __("Title of informations widget", "mydashboard") . "</td>";
        echo "<td>";
        echo Html::input('title_informations_widget', ['value' => $this->fields['title_informations_widget'], 'size' => 70]);
        echo "</td>";
        echo "</tr>";

        echo Html::submit(
            _sx('button', 'Reconstruct global backlog', 'mydashboard'),
            ['name' => 'reconstructBacklog', 'class' => 'btn btn-primary']
        );
        echo "&nbsp;";
        echo Html::submit(
            _sx('button', 'Reconstruct global indicators per week', 'mydashboard'),
            ['name' => 'reconstructIndicators', 'class' => 'btn btn-primary']
        );
        echo "<br/><br/><div class='alert  alert-warning d-flex'>";
        echo  __('Can take many time if you have many tickets', 'mydashboard');
        echo "</div>";

        $this->showFormButtons($options);
    }

    /*
     * Initialize the original configuration
     */
    public function initConfig()
    {
        global $DB;

        //We first check if there is no configuration
        $iterator = $DB->request([
            'SELECT'    => '*',
            'FROM'      => $this->getTable(),
            'LIMIT'    => 1
        ]);

        if (count($iterator) == 0) {
            $input                              = [];
            $input['id']                        = "1";
            $input['enable_fullscreen']         = "1";
            $input['display_menu']              = "1";
            $input['replace_central']           = "0";
            $input['title_alerts_widget']       = _n("Network alert", "Network alerts", 2, 'mydashboard');
            $input['title_maintenances_widget'] = _n("Scheduled maintenance", "Scheduled maintenances", 2, 'mydashboard');
            $input['title_informations_widget'] = _n("Information", "Informations", 2, 'mydashboard');
            $this->add($input);
        }
    }

    /*
     * Get the original config
     */
    public function getConfig()
    {
        if (!$this->getFromDB("1")) {
            $this->initConfig();
            $this->getFromDB("1");
        }
    }

    /**
     * Returns the translation of the field
     *
     * @param type  $item
     * @param type  $field
     *
     * @return type
     * @global type $DB
     *
     */
    public static function displayField($item, $field)
    {
        global $DB;

        // Make new database object and fill variables
        $iterator = $DB->request([
            'FROM'  => 'glpi_plugin_mydashboard_configtranslations',
            'WHERE' => [
                'itemtype' => Config::class,
                'items_id' => '1',
                'field'    => $field,
                'language' => $_SESSION['glpilanguage'],
            ]]);

        if (count($iterator)) {
            foreach ($iterator as $data) {
                return $data['value'];
            }
        }
        return $item->fields[$field];
    }

    /**
     * @return string
     */
    public function getGridTheme(): string
    {
        if (str_contains($_SESSION['glpipalette'], 'darker') == true
            || str_contains($_SESSION['glpipalette'], 'midnight') == true) {
            return '';
        } else {
            return '#fbfbfb!important;';
        }
    }

    /**
     * @return string
     */
    public function getWidgetTheme(): string
    {
        if (str_contains($_SESSION['glpipalette'], 'darker') == true
            || str_contains($_SESSION['glpipalette'], 'midnight') == true) {
            return '';
        } else {
            return '#FFFFFF!important;';
        }
    }

    /**
     * @return string
     */
    public function getSlidePanelTheme(): string
    {
        if (str_contains($_SESSION['glpipalette'], 'darker') == true
            || str_contains($_SESSION['glpipalette'], 'midnight') == true) {
            return '#242323';
        } else {
            return '#FFFFFF!important;';
        }
    }

    /**
     * @return string
     */
    public function getSlideLinkTheme(): string
    {
        if (str_contains($_SESSION['glpipalette'], 'darker') == true
            || str_contains($_SESSION['glpipalette'], 'midnight') == true) {
            return '#FFFFFF';
        } else {
            return '#000000';
        }
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
                        `enable_fullscreen`         tinyint      NOT NULL DEFAULT '1',
                        `display_menu`              tinyint      NOT NULL DEFAULT '1',
                        `replace_central`           int {$default_key_sign} NOT NULL DEFAULT '0',
                        `impact_1`                  varchar(200) NOT NULL DEFAULT '#228b22',
                        `impact_2`                  varchar(200) NOT NULL DEFAULT '#fff03a',
                        `impact_3`                  varchar(200) NOT NULL DEFAULT '#ffa500',
                        `impact_4`                  varchar(200) NOT NULL DEFAULT '#cd5c5c',
                        `impact_5`                  varchar(200) NOT NULL DEFAULT '#8b0000',
                        `levelCat`                  int {$default_key_sign} NOT NULL DEFAULT '2',
                        `title_alerts_widget`       varchar(255) COLLATE utf8mb4_unicode_ci,
                        `title_maintenances_widget` varchar(255) COLLATE utf8mb4_unicode_ci,
                        `title_informations_widget` varchar(255) COLLATE utf8mb4_unicode_ci,
                        PRIMARY KEY (`id`)
               ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->doQuery($query);

        }

        if (!$DB->fieldExists($table, "replace_central")) {
            $migration->addField($table, "replace_central", "int {$default_key_sign} NOT NULL DEFAULT '0'");
            $migration->migrationOneTable($table);
        }

        if (!$DB->fieldExists($table, "impact_1")) {
            $migration->addField($table, "impact_1", "varchar(200) NOT NULL DEFAULT '#228b22'");
            $migration->migrationOneTable($table);
        }

        if (!$DB->fieldExists($table, "impact_2")) {
            $migration->addField($table, "impact_2", "varchar(200) NOT NULL DEFAULT '#fff03a'");
            $migration->migrationOneTable($table);
        }

        if (!$DB->fieldExists($table, "impact_3")) {
            $migration->addField($table, "impact_3", "varchar(200) NOT NULL DEFAULT '#ffa500'");
            $migration->migrationOneTable($table);
        }

        if (!$DB->fieldExists($table, "impact_4")) {
            $migration->addField($table, "impact_4", "varchar(200) NOT NULL DEFAULT '#cd5c5c'");
            $migration->migrationOneTable($table);
        }

        if (!$DB->fieldExists($table, "impact_5")) {
            $migration->addField($table, "impact_5", "varchar(200) NOT NULL DEFAULT '#8b0000'");
            $migration->migrationOneTable($table);
        }

        if (!$DB->fieldExists($table, "levelCat")) {
            $migration->addField($table, "levelCat", "int {$default_key_sign} NOT NULL DEFAULT '2'");
            $migration->migrationOneTable($table);
        }

        if (!$DB->fieldExists($table, "title_alerts_widget")) {
            $migration->addField($table, "title_alerts_widget", "varchar(255) COLLATE utf8mb4_unicode_ci");
            $migration->migrationOneTable($table);
        }

        if (!$DB->fieldExists($table, "title_maintenances_widget")) {
            $migration->addField($table, "title_maintenances_widget", "varchar(255) COLLATE utf8mb4_unicode_ci");
            $migration->migrationOneTable($table);
        }

        if (!$DB->fieldExists($table, "title_informations_widget")) {
            $migration->addField($table, "title_informations_widget", "varchar(255) COLLATE utf8mb4_unicode_ci");
            $migration->migrationOneTable($table);

            $config                             = new self();
            $input['id']                        = "1";
            $input['title_alerts_widget']       = _n("Network alert", "Network alerts", 2, 'mydashboard');
            $input['title_maintenances_widget'] = _n("Scheduled maintenance", "Scheduled maintenances", 2, 'mydashboard');
            $input['title_informations_widget'] = _n("Information", "Informations", 2, 'mydashboard');
            $config->update($input);

        }

        if ($DB->fieldExists($table, "display_plugin_widget")) {
            $migration->dropField($table, "display_plugin_widget");
            $migration->migrationOneTable($table);
        }

        if ($DB->fieldExists($table, "display_special_plugin_widget")) {
        $migration->dropField($table, "display_special_plugin_widget");
            $migration->migrationOneTable($table);
        }

        if ($DB->fieldExists($table, "google_api_key")) {
            $migration->dropField($table, "google_api_key");
            $migration->migrationOneTable($table);
        }

        $migration->changeField($table, "levelCat", "levelCat", "int {$default_key_sign} NOT NULL DEFAULT '2'");
        $migration->migrationOneTable($table);

        $migration->changeField($table, "replace_central", "replace_central", "int {$default_key_sign} NOT NULL DEFAULT '0'");
        $migration->migrationOneTable($table);

        $config = new self();
        if (!$config->getFromDB("1")) {
            $config->initConfig();
        }
    }

    public static function uninstall()
    {
        global $DB;

        $DB->dropTable(self::getTable(), true);

    }
}
